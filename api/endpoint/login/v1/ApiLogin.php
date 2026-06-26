<?php

namespace api\endpoint\login\v1;

use api\endpoint\ApiBase;
use api\traits\apiCompTrait;
use api\traits\apiInfoTrait;
use api\traits\apiProjectTrait;
use api\traits\apiTokenTrait;
use api\traits\apiUserTrait;
use sys\ERROR;

class ApiLogin extends ApiBase
{

    private const int AUTH_TYPE_MD5  = 1;
    private const int AUTH_TYPE_PHP  = 2;
    private const int AUTH_TYPE_TOTP = 4;
    use apiUserTrait, apiTokenTrait, apiProjectTrait, apiCompTrait , apiInfoTrait ;

    private const array RATE_LIMIT_LOGIN_USERNAME = [1, 12, 120];

    public function run(?array $parts = null, ?array $split = null)
    {
        try {
            $res = match ($parts[0] ?? '') {
                'uap'   => $this->loginUap(),
                'cke'   => $this->loginCookie(),
                'exg'   => $this->loginExg(),

                'info'  => $this->loginInfo(),
                default => ERROR::API_MISSING,
            };
        }
        catch (\Throwable $ex) {
            $res = ERROR::API_EXCEPT;
        }

        $this->outputResult($res);
        exit;
    }



    private function unpackPayload( ?object $payload )
    {
        if ( !is_object($payload) || !isset($payload->id, $payload->e) || !is_string($payload->id) || strlen($payload->id) !== 10 )
            return null;
        $key = $_ENV['APP_ID' . $payload->id . '_KEY' ] ?? false ;
        if ( !empty($key)) {
            $d = \sys\Crypto::decrypt($payload->e, $key) ;
            if ( $d )
                return \unserialize($d) ?? null ;
        }

        return null ;
    }

    // ------------ endpoints -----------------
    private function loginUap() : int | object | null
    {
        // decrypt payload packet ,
        $pkt = $this->unpackPayload($this->getPayload());
        if (
            !is_object($pkt) ||
            !isset($pkt->user, $pkt->pass, $pkt->vcode) ||
            !\sys\Valid::account($pkt->user) ||
            !\sys\Valid::password($pkt->pass) ||
            !\sys\Valid::vCode( $pkt->vcode )
        )
            return ERROR::API_PARAM;

        if ( !(\sys\Audit::globalLimitOK( "_uap", 200 , 20 )))
            return ERROR::API_BUSY ;

        return \sys\Util::constantRunTime(function () use ($pkt)
        {
            try {
                // we have to be careful about leaking, any facts about an account
                $user = $this->getUserAccount($pkt->user);
                if (!$user || $user->active < 1 || (($user->reg ?? 0) & 1) !== 1)
                    return ERROR::LOGIN_FAILED; //
                $pwdType = ($user->auth1 ?? 0) & (self::AUTH_TYPE_MD5 | self::AUTH_TYPE_PHP);
                if (
                    ($pwdType === self::AUTH_TYPE_MD5 && $this->checkPasswordMD5($user->sid, $pkt->pass)) ||
                    ($pwdType === self::AUTH_TYPE_PHP && $this->checkPasswordPHP($user->sid, $pkt->pass))
                ) {
                    // valid username + password , so partly let in
                    $authid = \sys\Redis::saveDataBlock('authid',
                        (object)[
                            'sid'   => $user->sid,
                            'vcode' => $pkt->vcode,
                            'auth1' => $user->auth1,
                            'remember'=>$pkt?->remember ?? false,
                        ]);
                    if ($authid)
                        return (object)[
                            'sid'    => (int)$user->sid ?? 0,
                            'authid' => $authid,
                        ];
                }
            }
            catch (\Throwable $ex) {
                error_log($ex);
                return ERROR::API_EXCEPT;
            }

            return ERROR::API_FAILED;
        }, [], 0.25);
    }

    private function loginCookie() : int | object | null
    {
        // decrypt payload packet ,
        $pkt = $this->unpackPayload($this->getPayload());
        if (
            !is_object($pkt) ||
            !isset($pkt->mtkn , $pkt->vcode) ||
            !\sys\Valid::vCode( $pkt->vcode )
        ) return ERROR::API_PARAM;

        // simple rate limit - cloudflare / apache should have stopped already
        if ( !( \sys\Audit::globalLimitOK( "_cookie", 200 , 20 )))
            return ERROR::API_BUSY ;

        return \sys\Util::constantRunTime(function () use ($pkt)
        {
            try {
                $t = $this->decodeMemoryToken($pkt->mtkn);
                if ( !is_object( $t ))
                    return null ;
                // we think we are $t->sid
                if ( isset($t->sid) && is_int($t->sid) && $t->sid > 0 ) {
                    $user = $this->getAccountFromSid( $t->sid ) ;
                    if (!$user || $user->active < 1 || (($user->reg ?? 0) & 1) !== 1)
                        return ERROR::LOGIN_FAILED;

                    // do we have to use totp as well
                    if ( $user->auth1 & self::AUTH_TYPE_TOTP ) {
                        // for now just cancel requirement , should
                        $auth1 = $user->auth1 & ~self::AUTH_TYPE_TOTP;
                    }
                    $authid = \sys\Redis::saveDataBlock('authid',
                        (object)[
                            'sid'   => $user->sid,
                            'vcode' => $pkt->vcode,
                            'auth1' => $auth1   ,
                            'remember'=>false,
                        ]);
                    if ($authid)
                        return (object)[
                            'sid'    => (int)$user->sid ?? 0,
                            'authid' => $authid,
                        ];

                }

            }
            catch (\Throwable $ex) {
                error_log($ex);
                return ERROR::API_EXCEPT;
            }

            return ERROR::API_FAILED;
        }, [], 0.25);
    }

    private function loginExg() : int | object | null
    {
        // decrypt payload packet ,
        $pkt = $this->unpackPayload($this->getPayload());
        if (!is_object($pkt) || !isset($pkt->sid, $pkt->authid))
            return ERROR::LOGIN_FAILED;

        $authcheck = \sys\Redis::loadDataBlock('authid', $pkt->authid);
        if (
            !is_object($authcheck) ||
            !isset($authcheck->sid, $authcheck->vcode, $authcheck->auth1) ||
            $authcheck->sid !== $pkt->sid ||
            !hash_equals($authcheck->vcode, hash('sha256', $pkt->vcode))
        )
            return ERROR::LOGIN_FAILED;

        $sid = $authcheck->sid ?? 0;
        if ($sid < 1)
            return ERROR::LOGIN_FAILED;

        // do, we need totp code - and is it correct?
        if ($authcheck->auth1 & self::AUTH_TYPE_TOTP) {
            if (!isset($pkt?->other?->totp))
                return (object)['need' => 'totp', 'reason' => 0];
            $totp = \sys\Clean::totp($pkt?->other?->totp ?? null);
            if (is_null($totp))
                return (object)['need' => 'totp', 'reason' => \sys\ERROR::LOGIN_TOTP_BAD];

            $skey = $this->getTotpKey($authcheck->sid);
            if (empty($skey) || strlen($skey) !== 32)
                return (object)['need' => 'totp', 'reason' => \sys\ERROR::LOGIN_METHOD_NOT_ALLOWED];
            elseif (!\sys\Crypto::verifyTOTP($skey, $totp))
                return (object)['need' => 'totp', 'reason' => \sys\ERROR::LOGIN_TOTP_INVALID];
        }

        // do our best to find a project ( packet may contain hint )
        $pid = $this->getAnotherProject($authcheck->sid, $pkt?->pid ?? -1);
        if ($pid >= 0) {
            $gen = $this->genLoginTokens(
                $authcheck->sid ?? 0,
                    $pid ,
                    $authcheck->remember ?? false ) ;

            if (is_object($gen) && isset($gen->atkn)) {
                $this->updateLastLogin($sid, $pid);
                \sys\Redis::deleteDataBlock('authid', $pkt->authid);
                return $gen;
            }
        }
        else
            return ERROR::LOGIN_NO_PROJECTS;

        return null;
    }

    private function loginInfo() : int | object | null
    {
        //

        try {
            if (!($a = $this->getBearer()) || !is_object($a))
                return (object)['expired'=>true] ;
            if ( isset($a->sid, $a->pid )) {
                if ($this->canUseProject($a->sid ?? 0, $a->pid ?? 0))
                    return $this->infoAll($a->sid ?? 0, $a->pid ?? 0);
            }
        }
        catch (\Throwable $ex) {
            error_log($ex);
        }

        return null ;
    }

}