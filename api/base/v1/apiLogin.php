<?php

namespace api\base\v1;

class apiLogin extends \api\base\apiBase
{

    use \api\auth\ApiAuthTrait;

    public function run($payload, $parts)
    {


        switch ($parts[0] ?? false) {
            case 'uap' :
                return $this->uap($payload);
            case 'exg' :
                return $this->exg($payload);
            case 'sendresetcode' :
                return $this->sendResetCode($payload);
            case 'chpwd' :
                return $this->changePassword($payload);
            case 'checkcode' :
                return $this->checkCode($payload);
            case 'refresh' :
                return $this->refreshToken( $payload ) ;
                break ;

            default :
                return 899;
        }
    }

    // API : uap - authorised by username and password
    public function uap($payload)
    {
        return \sys\Util::constantRunTime(function () use ($payload)
        {
            try {
                if (!$payload || empty($payload->user) || is_numeric($payload->user) || empty($payload->pass) || empty($payload->vcode) ||
                    mb_strlen($payload->vcode) < 40) {
                    return 801;
                }

                if (!\sys\Audit::rateLimitOK($payload->user, self::RATE_LIMIT_USER_LOGIN))
                    return 802;

                if (($authuser = $this->lookupUser(strtolower(trim($payload->user)))) !== null) {
                    if ((($authuser->sid ?? 0) > 0) && (($authuser->active ?? 0) > 0)) {
                        if ($this->checkPassword($authuser, $payload->pass)) {
                            // password ok
                            $authid = $this->generateAUTHID($authuser->sid,
                                $payload->vcode);
                            if (!empty($authid)) {
                                $pid = $this->suggestProject($authuser->sid);
                                return (object)[
                                    'authid' => $authid,
                                    'pid'    => $pid ?? 0,
                                ];
                            }
                        }
                    }
                }
            } catch (\Throwable $ex) {
                return 800;
            }

            return null;
            //
        }, [], 0.25);
    }

    // API : cp - change project
    /*
    public function changeProject($payload)
    {

        if (($a = $this->validUserProjectAccess()) !== null) {
            if (($a->sid ?? 0) === $payload->sid) {
                $authid = $this->generateAUTHID($a->sid, $payload->vcode);
                if (!empty($authid)) {
                    $pid = $this->suggestProject($a->sid);
                    return (object)[
                        'authid' => $authid,
                        'pid'    => $pid ?? 0,
                    ];
                }
            }
        }

        return null;
    }
    */

    // API : exg - exchange authid for tokens

    public function exg($payload)
    {
        return \sys\Util::constantRunTime(function () use ($payload)
        {
            try {
                if (!$payload || empty($payload->authid) || empty($payload->vcode)) {
                    return 801;
                }

                $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                if (!\sys\Audit::rateLimitOK($ip, self::RATE_LIMIT_AUTHID))
                    return 802;

                $authdata = $this->findAuthID($payload->authid);
                if ($authdata && isset($authdata->sid, $authdata->vcode) && hash_equals($authdata->vcode,
                        hash('sha256', $payload->vcode))) {
                    $sid = $authdata->sid ?? 0;
                    $pid = $payload->pid ?? 0;
                    if ($this->validSID($sid) && $this->validPID($sid, $pid)) {
                        $tokens = $this->generateACCESS($sid, $pid);
                        if (is_object($tokens)) {
                            \sys\db\SQL::Exec(" update <DBM>.sys_last_pid set pid = ? where sid = ? ", [$pid, $sid]);
                            return $tokens;
                        }
                    }
                }
            } catch (\Throwable $ex) {
                return 800;
            }

            return null;
            //
        }, [], 0.25);
    }

    // API : sendresetcode - generate a code to enable users to reset password
    public function sendResetCode($payload)
    {
        if (!$payload || empty($payload->user)) {
            return 801;
        }

        try {
            if (($authuser = $this->lookupUser(strtolower(trim($payload->user)))) !== null) {
                if (!empty(\sys\db\SQL::error())) {
                    return 898;
                }
                if (is_object($authuser) && (($authuser->sid ?? 0) > 0) && (($authuser->active ?? 0) > 0)) {
                    // have we got an outstandng OTP code already
                    $otp = $this->getActiveOTP($authuser->sid);
                    if (empty($otp)) {
                        // so lets send an email
                        $otp = $this->makeOTP($authuser->sid);
                        if (!empty($otp) && $this->setOTP($authuser->sid,
                                $otp)) {
                            $emailer = new \api\email\SendEmail();
                            if ($emailer) {
                                $emailer->send($authuser->sid,
                                    'send-reset-code',
                                    ['otp' => $otp]);
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $ex) {
        }

        return ['ok' => true];
    }

    public function checkCode($payload)
    {
        return \sys\Util::constantRunTime(function () use ($payload)
        {
            try {
                if (is_object($payload) && isset($payload->pkt)) {
                    $pkt = (object)\unserialize(\sys\Crypto::decrypt($payload->pkt ?? '', $_ENV['P_TOKEN'] ?? ''));
                    if (($sid = $this->checkOtpPkt($pkt)) > 0) {
                        return ['ok' => true];
                    }
                }
            } catch (\Throwable $ex) {
                error_log($ex);
            }

            return ['ok' => false];
        }, [], 0.25); // 250    ms

    }

    public function changePassword($payload)
    {
        return \sys\Util::constantRunTime(function () use ($payload)
        {
            try {
                if (is_object($payload) && isset($payload->pkt)) {
                    $pkt = (object)\unserialize(\sys\Crypto::decrypt($payload->pkt ?? '', $_ENV['P_TOKEN'] ?? ''));
                    if (($sid = $this->checkOtpPkt($pkt)) > 0) {
                        if (empty($pkt->pwd1) || empty($pkt->pwd2) || $pkt->pwd1 !== $pkt->pwd2 || mb_strlen($pkt->pwd1) < 8 ||
                            mb_strlen($pkt->pwd1) > 128)
                            return ['ok' => false, 'msg' => 803];

                        if ($this->renewPassword($sid, $pkt->pwd1))
                            return ['ok' => true];
                    }
                }
            } catch (\Throwable $ex) {
            }
        }, [], 0.25);

        return null;
    }

    public function refreshToken( $payload )
    {

        if ( isset( $payload->rtkn , $payload->pid ) &&
            !empty($payload->rtkn) && $payload->pid > 0 ) {
            $r = $this->validRefresh( $payload->rtkn ) ;
            if ( is_object($r))  {
                if ( $this->validPID( $r->sid , $payload->pid )) {
                    $o = $this->generateACCESS($r->sid, $payload->pid);
                    if ( is_object( $o )) {
                        return ['ok' => true, 'atkn' => $o->atkn, 'rtkn' => $o->rtkn];
                    }
                }
            }
        }

        return ['ok'=>false];
    }


}
