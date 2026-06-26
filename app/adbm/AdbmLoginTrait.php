<?php

namespace app\adbm;

trait AdbmLoginTrait
{

    const int AUTH_STEP_START    = 0;
    const int AUTH_STEP_LOGIN    = 1;
    const int AUTH_STEP_PASSWORD = 2;
    const int AUTH_STEP_TOTP     = 3;
    const int AUTH_STEP_CHECK    = 4;
    const int AUTH_STEP_EXG      = 5;


    protected function authPage()
    {
        match ((self::$_parts[0] ?? '')) {
            'start'   => $this->authStart(),
            'login' => $this->loginPage(),
            'signout' => $this->authLogout() ,
            'force-logout'=> $this->authForcedOut() ,
            default => throw new \Exception('Auth Page Not Found'),
        };
    }

    private function gotoStep(object &$auth, int $step) : never
    {
        if (session_status() !== PHP_SESSION_ACTIVE)
            throw new \RuntimeException('Session Not Active');

        $auth->step = $step;
        header('Location: /auth/login');
        exit;
    }

    protected function authStart() : never
    {
        $target = $_SESSION['_auth']?->target ?? null ;
        if ( !$target )
            header( 'Location: /'  );
        else {
            $_SESSION['_auth'] = (object)['target' => $target , 'step' => 1];
            header('Location: /auth/login' );
        }
        exit ;
    }

    protected function authLogout() {
        unset( $_SESSION['_auth'] );
        $this->setLogin( null ) ;
        header( 'Location: /'  );
        exit ;
    }

    protected function askLogin(?object &$auth) : never
    {
        if ( !$auth )
            exit ;


        $mtkn = $_COOKIE['_login_token'] ?? null ;
        if ( $mtkn && is_string($mtkn) && !empty($mtkn)) {

            // ask api , if this cookie tooken is valid
            // that is decode ok.
            $auth->vcode = base64_encode(random_bytes(54));
            $cke = $this->apiPostE('v1/login/cke', (object)[
                'mtkn' => $mtkn ,
                'vcode'=> hash( 'sha256' , $auth->vcode )
            ] ) ;
            if ( is_object($cke))
            {
                if ( isset($cke->authid, $cke->sid) && !empty($cke->authid) && $cke->sid > 0) {
                    $auth->sid = $cke->sid;
                    $auth->pid = $cke->pid ?? 0;
                    $auth->authid = $cke->authid;
                    $this->gotoStep($auth, self::AUTH_STEP_EXG);
                }



            }




        }

        // get previous error
        $error = $auth->error ?? 0 ;
        $auth->error = 0;
        $user = $_POST['username'] ?? $auth->user ?? '' ;
        if (isset($_POST['username']) && ($_POST['_login'] ?? '') === 'li' && $this->checkCSRF()) {
            // have posted new username , check its well-formed
            if (!(\sys\Valid::account($user)))
                $error = \sys\ERROR::LOGIN_BAD_ACCOUNT_NAME;
            else {
                // can goto ask for password step
                $auth->user = $user;
                $this->gotoStep($auth, self::AUTH_STEP_PASSWORD);
            }
        }
        // show form
        $this->showBlade('auth.login', [
            'errormsg' => $error ? \sys\ERROR::msg($error) : '',
            'username' => $user,
        ]);
        exit;
    }

    protected function askPassword(object &$auth) : never
    {
        $error = $auth->error ?? 0;
        $auth->error = 0;
        $user = $auth->user ?? '';
        if (isset($_POST['password']) && $this->checkCSRF()) {
            $password = mb_trim($_POST['password'] ?? '');
            if (mb_strlen($password) < 8 || mb_strlen($password) > 125)
                $error = \sys\ERROR::LOGIN_BAD_PASSWORD;
            else {
                $auth->pass = $password;
                $auth->remember = ( ( $_POST['_remember'] ?? false ) === 'on' ) ;
                $this->gotoStep($auth, self::AUTH_STEP_CHECK);
            }
        }

        $this->showBlade('auth.password', [
            'errormsg' => $error ? \sys\ERROR::msg($error) : '',
            'username' => $user,
        ]);

        exit;
    }

    protected function askTotp(object &$auth) : never
    {
        $error = $auth->error ?? 0;
        $auth->error = 0;
        $auth->code = $_POST['code'] ?? $auth->code ?? '';;
        if (isset($_POST['code']) && $this->checkCSRF()) {
            $code = \sys\Clean::totp($_POST['code'] ?? '');
            if (empty($code) || mb_strlen($code) !== 6)
                $error = \sys\ERROR::LOGIN_TOTP_BAD;
            else {
                $auth->other = (object)['totp' => $code];
                $this->gotoStep($auth, self::AUTH_STEP_EXG);
            }
        }

        $this->showBlade('auth.totp', [
            'errormsg' => $error ? \sys\ERROR::msg($error) : '',
            'code'     => (string)$auth->code,
        ]);
        exit;
    }
    protected function checkPassword(?object &$auth) : never
    {
        if ($auth === null || empty($auth->user ?? '') || empty($auth->pass ?? ''))
            $this->gotoStep($auth, 0);

        // try to get authid
        $auth->vcode = base64_encode(random_bytes(54));
        $uap = $this->apiPostE('v1/login/uap', (object)[
            'user'  => $auth->user,
            'vcode' => hash('sha256', $auth->vcode),
            'pass' => $auth->pass , #
            'remember'=>$auth?->remember ]);

        // got authid
        if (is_object($uap) && isset($uap->authid, $uap->sid) && !empty($uap->authid) && $uap->sid > 0) {
            $auth->sid = $uap->sid;
            $auth->pid = $uap->pid ?? 0;
            $auth->authid = $uap->authid;
            $this->gotoStep($auth, self::AUTH_STEP_EXG);
        }
        // map errors to user ,
        $auth->error = match ($uap?->error ?? 0) {
            default => \sys\ERROR::LOGIN_FAILED,
        };

        // ask for password again
        $this->gotoStep($auth, self::AUTH_STEP_PASSWORD);
    }

    protected function loginPage() : never
    {
        try {
            $auth = &$_SESSION['_auth'];
            if ($auth === null || !isset($auth->target))
                throw new \RuntimeException(\sys\ERROR::msg(\sys\ERROR::SESSION_EXPIRED));
            else match ($auth?->step ?? 0) {
                self::AUTH_STEP_START    => $this->authStart($auth),
                self::AUTH_STEP_LOGIN    => $this->askLogin($auth),
                self::AUTH_STEP_PASSWORD => $this->askPassword($auth),
                self::AUTH_STEP_TOTP     => $this->askTotp($auth),
                self::AUTH_STEP_CHECK    => $this->checkPassword($auth),
                self::AUTH_STEP_EXG      => $this->tryExchange($auth),
                default                  => throw new \RuntimeException('Auth Step' . $auth?->step . ' Not Found'),
            };
        }
        catch (\Throwable $ex) {
            unset($_SESSION['_auth']);
            if ($ex instanceof \RuntimeException)
                $this->showBlade('auth.error', ['errormsg' => $ex->getMessage()]);
            else {
                error_log($ex);
                $this->showBlade('auth.error', [
                    'errormsg' => \sys\ERROR::msg(\sys\ERROR::LOGIN_FAULT),
                ]);
            }
        }

        exit;
    }

    protected function tryExchange(object &$auth) : never
    {
        if (!isset($auth->id, $auth->sid)) {
            $auth->error = 0;

            $exg = $this->apiPostE('v1/login/exg', (object)[
                'authid' => $auth->authid,
                'sid'    => $auth->sid ?? 0 ,
                'pid'    => $auth->pid ?? 0 ,
                'vcode'  => $auth->vcode,
                'other'  => $auth->other ?? null,
            ]);

            if (is_object($exg)) {
                if (isset($exg->need)) {
                    if ($exg->need === 'totp') {
                        $auth->error = $exg->reason ?? 0;
                        $this->gotoStep($auth, self::AUTH_STEP_TOTP);
                    }

                }
                elseif (isset($exg->atkn) && $this->setLogin($exg)) {
                    // have Login
                    $target = $auth->target ?? '/';
                    unset($auth);
                    header('Location: ' . $target);
                    exit;
                }
                elseif ( isset($exg->error))
                    $auth->error = $exg->error;
            }
            $auth->error = \sys\ERROR::msg(\sys\ERROR::LOGIN_FAULT);
        }
        else
            $auth->error = \sys\ERROR::LOGIN_EXPIRED;

        $this->gotoStep($auth, self::AUTH_STEP_LOGIN);
        exit;
    }

    private function authForcedOut() : never {
        unset( $_SESSION['_auth'] );
        $this->setLogin( null ) ;
        $this->showBlade('auth.forced', [] , 0 );


        exit ;
    }

}