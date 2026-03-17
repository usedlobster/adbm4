<?php

namespace api\auth;

trait ApiAuthEngineTrait
{

    private const int ACCESS_TOKEN_LIFE  = 421;

    private const int REFRESH_TOKEN_LIFE = 1382419;

    /**
     * Retrieves user authentication details based on the provided account information.
     *
     * @param  string|int  $account  The account identifier, which can be either a numeric SID or a username string.
     *
     * @return object|null Returns an object containing the user's authentication details on success, or null if no matching record is found or an
     *                     error occurs.
     *
     */
    private function lookupUser(string | int $account) : object | null
    {
        try {
            $w = is_numeric($account) ? ' a.sid = ? ' : ' a.username = ? ';
            $auth = \sys\db\SQL::RowN(" select a.sid , a.active , a.pwdtype , a.username , a.pwd from <DBM>.sys_auth a WHERE {$w} ", [$account]);
            if (!$auth || \sys\db\SQL::error())
                return null;
            return is_array($auth) ? (object)$auth : null;
        } catch (\Throwable $ex) {
            error_log($ex);
        }

        return null;
    }

    private function generateAUTHID(int $sid, string $vcode) : string
    {
        $red = \sys\Redis::getRedis(2);
        if ($red) {
            $authdata = \serialize((object)[
                'sid'   => $sid,
                'vcode' => $vcode,
            ]);

            $atempt = 0;
            while (++$atempt < 25) {
                $authkey = 'authid:'.($authid = base64_encode(random_bytes(9)));
                if (!($red->exists($authkey))) {
                    // save for 60 seconds ,
                    if ($red->setex($authkey, 60, $authdata))
                        return $authid;
                }
            }
        }

        return '';
    }

    protected function findAUTHID($authid) : ?object
    {
        $red = \sys\Redis::getRedis(2);
        if ($red) {
            $payload = $red->get('authid:'.$authid);
            if (!empty($payload) && is_string($payload)) {
                $red->del('authid:'.$authid);
                return \unserialize($payload);
            }
        }

        return null;
    }

    private function getProjectDB(int $pid)
    {
        return \sys\db\SQL::Get0(" SELECT db FROM <DBM>.sys_projects WHERE pid = ?", [$pid]) ?? '';
    }

    // we pass $db as hint , as we may already know this , so dont need to lookup again.
    protected function canUseProject(int $sid, int $pid, ?string $db) : bool
    {
        try {
            $db = $db ?? $this->getProjectDB($pid);
            if ($db)
                return \sys\db\SQL::Get0(<<<SQL
SELECT EXISTS(
    select 1
    from <DBM>.sys_sid2pid s2p
     join <DBM>.sys_projects sp on sp.pid = s2p.pid
     join {$db}.users u on u.sid = s2p.sid
     join {$db}.comps c on c.cid = u.cid
    WHERE s2p.sid = ?
        and s2p.pid = ?
        and sp.active > 0
        and u.active > 0
        and c.active > 0    
        and ( sp.valid is null or now() > sp.valid )
        and ( sp.expire is null or now() < sp.expire )
) as ok;
SQL,
                    [$sid, $pid]) ?? false;
        } catch (\Throwable $ex) {
        }

        return false;
    }

    protected function getProjectList($sid)
    {
        $plist = \sys\db\SQL::GetAllN(<<<SQL

SELECT p.pid,p.db,p.title FROM <DBM>.sys_sid2pid AS s2p
    JOIN <DBM>.sys_projects AS p ON ( p.pid = s2p.pid )
    WHERE s2p.sid = ?


SQL,
            [$sid]);

        $out = [];
        if (is_array($plist))
            foreach ($plist as $p) {
                $pid = $p['pid'] ?? false;
                $db = $p['db'] ?? false;
                if ($pid && $db)
                    if ($this->canUseProject($sid, $pid, $db))
                        $out[] = ['id' => $pid, 'text' => $p['title'] ?? ''];
            }

        return $out;
    }

    private function suggestProject(int $sid) : int
    {
        // try the last used project

        $pid = \sys\db\SQL::Get0(" select pid from <DBM>.sys_last_pid where sid = ? ", [$sid]) ?? 0;
        if ($pid < 1 || $this->canUseProject($sid, $pid, null) === false)
            return 0;

        return $pid;
    }

    /**
     * Authenticates a user by validating the provided credentials and generates an authorization ID.
     *
     * @param  string  $user   The username provided by the user for authentication.
     * @param  string  $pass   The password associated with the username.
     * @param  string  $vcode  The verification code
     *
     * @return object|null Returns an object containing 'authid' (authorization ID) and 'pid' (project ID) on successful authentication, or null if
     *                     authentication fails.
     */
    protected function authLogin($user, $pass, $vcode)
    {
        if (($authuser = $this->lookupUser(strtolower(trim($user)))) !== null) {
            // do we have valid and active sid
            if ((($authuser->sid ?? 0) > 0) && (($authuser->active ?? 0) > 0)) {
                // did we guess the right password
                if ($this->checkPassword($authuser, $pass)) {
                    // password ok,
                    $authid = $this->generateAUTHID($authuser->sid, $vcode);
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

        return null;
    }

    protected function authExchange($authid, $pid, $vcode)
    {
        $authdata = $this->findAuthID($authid);
        if ($authdata && isset($authdata->sid, $authdata->vcode) && hash_equals($authdata->vcode, hash('sha256', $vcode))) {
            $sid = $authdata->sid ?? 0;
            if ($sid > 0) {
                if (!$this->canUseProject($sid, $pid, null))
                    $pid = 0;

                $tokens = $this->generateACCESS($sid, $pid);
                if (is_object($tokens) && $pid > 0)
                    \sys\db\SQL::Exec(" update <DBM>.sys_last_pid set pid = ? where sid = ? ", [$pid, $sid]);

                return $tokens;
            }
        }

        return null;
    }

    protected function authChangeProject($sid, $pid, $vcode)
    {
        if ($this->canUseProject($sid, $pid, null)) {
            $authid = $this->generateAUTHID($sid, $vcode);
            if (!empty($authid)) {
                return (object)[
                    'authid' => $authid,
                    'pid'    => $pid,
                ];
            }
        }

        return 902;
    }

    private function renewPassword(int $sid, string $password) : bool
    {
        if ($sid > 0)
            return \sys\db\SQL::Exec(" update <DBM>.sys_auth set pwd = ? , pwdtype = 1 where sid = ? ",
                [password_hash($password, PASSWORD_DEFAULT), $sid]);
        return false;
    }

    /* check password hash matches */
    private function checkPassword(object $user, string $password) : bool
    {
        if (!isset($user->pwd, $user->pwdtype))
            return false;

        if ($user->pwdtype === 0 && hash_equals(hash('md5', $password), $user->pwd))
            $this->renewPassword($user->sid, $password);
        elseif ($user->pwdtype === 1 && password_verify($password, $user->pwd)) {
            if (password_needs_rehash($user->pwd, PASSWORD_DEFAULT))
                $this->renewPassword($user->sid, $password);
        }
        else
            return false;

        return true;
    }

    private function generateACCESS(int $sid, int $pid) : ?object
    {
        try {
            $t = time();

            $adata = [
                'y'   => 'a',
                'sid' => $sid,
                'pid' => $pid,
                't'   => $t,
                'x'   => self::ACCESS_TOKEN_LIFE,
            ];

            $atkn = \sys\Crypto::encrypt(\serialize($adata), $_ENV['A_TOKEN'] ?? false) ?? false;
            if (!$atkn)
                return null;

            $rdata = [
                'y'   => 'r',
                'sid' => $sid,
                't'   => $t,
                'x'   => self::REFRESH_TOKEN_LIFE,
            ];
            $rtkn = \sys\Crypto::encrypt(\serialize($rdata), $_ENV['R_TOKEN'] ?? false) ?? false;
            if (!$rtkn)
                return null;

            return (object)['sid' => $sid, 'pid' => $pid, 'atkn' => $atkn, 'rtkn' => $rtkn];
        } catch (\Throwable $ex) {
        }

        return null;
    }

    public function decodeAccessToken($token) : ?object
    {
        // decode
        try {
            @ $d = (object)\unserialize(\sys\Crypto::decrypt($token, $_ENV['A_TOKEN'] ?? false) ?? false);
            if (!$d || !isset($d->y, $d->sid, $d->pid, $d->t, $d->x) || $d->y != 'a' || $d->sid < 1)
                return null;
            $tnow = time();
            if ($tnow < $d->t || $tnow > ($d->t + $d->x))
                return (object)['expired' => true];
        } catch (\Throwable $ex) {
            return null;
        }
        return $d;
    }

    protected function getActiveOTP(int $sid) : ?string
    {
        $otp_hash = \sys\db\SQL::Get0(" SELECT otp FROM <DBM>.sys_reset_otp WHERE sid = ? AND (  NOW() <=exp  ) ", [$sid]);
        if (!empty($otp_hash) || !empty(\sys\db\SQL::error())) {
            return $otp_hash;
        }
        return null;
    }

    private function makeOTP(int $sid) : string
    {
        $chars = '123456789ABCDEFGHJKLMNPRSTVWXYZ';
        $code = '';

        for ($i = 0; $i < 8; $i++) {
            if ($i == 4) {
                $code .= '-';
            }

            // NB: random_int is a lot better these days
            $code .= $chars[\random_int(0, strlen($chars) - 1)];
        }

        return $code;
    }

    private function setOTP(int $sid, string $otp) : bool
    {
        if ($sid > 0) {
            return \sys\db\SQL::Exec(" REPLACE INTO <DBM>.sys_reset_otp (sid,otp,exp) VALUES (?,?,now() + INTERVAL 15 minute)  ",
                [$sid, password_hash($otp, PASSWORD_DEFAULT)]);
        }
        return false;
    }

    private function getEmailPro(int $sid) : ?array
    {

        return  \sys\db\SQL::RowN(<<<SQL
    select su.firstname,su.lastname,su.displayname,
       coalesce( sa.email , sa.username ) as email ,
       (su.active and sa.active >= 0 ) as active 
    from <DBM>.sys_users su
    join <DBM>.sys_auth sa on ( sa.sid = su.sid )
    where su.sid = ?
SQL,
            [$sid]) ?? null;

    }

    private function sendEmailToSID($sid, $template, $data)
    {
        xdebug_break() ;
        $pro = $this->getEmailPro($sid) ;
        if ( $pro && is_array($pro)) {
            $email = $pro['email'] ?? false ;
            if ( \sys\Valid::email( $email ))
            {
                $tdata = is_array($data) ? [ ...$pro , ...$data ] : [...$pro] ;
                if ( is_array($tdata)) {

                    $dname = $pro['displayname'] ?? '' ;
                    if ( empty( $dname ))
                        $dname = ( $pro['firstname'] ?? '' ) . ' ' . ( $pro['lastname'] ?? '' ) ;

                    if (\sys\wd\Email\SendEmail::sendTemplate($email, $dname , $template, $tdata))
                        return true;
                }
            }
        }

        return false;
    }

    /**
     * Sends a reset code (OTP) to the user if the account is valid and active.
     *
     * @param  string  $user  The username or email address of the account for which the reset code is being requested.
     *
     * @return object|null Returns null if user account given is not a valid format
     *                     otherwise returns ['ok'=>true] even if no email is sent
     *
     *                     * NB : only sends an email when account is found and active, and no still active OTP's
     *
     */
    protected function authSendResetCode($user)
    {
        if (!isset($user) || !\sys\valid::account($user))
            return null;

        try {
            if (($authuser = $this->lookupUser(strtolower(trim($user)))) !== null) {
                if (is_object($authuser) && (($authuser->sid ?? 0) > 0) && (($authuser->active ?? 0) > 0)) {
                    // code have valid sid
                    $otp = $this->getActiveOTP($authuser->sid);
                    if (empty($otp)) {
                        $otp = $this->makeOTP($authuser->sid);
                        if (!empty($otp)) //  && $this->setOTP($authuser->sid, $otp))
                            $this->sendEmailToSid($authuser->sid, 'send-reset-code', ['otp' => $otp]);
                    }
                }
            }
            return (object)['ok' => true];
        }
        catch( \Throwable $ex ) {
            error_log($ex) ;
        }
    }

}