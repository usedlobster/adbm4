<?php

namespace app\wd\auth;

class AuthPasswordReset {

    private ?object $_state = null ;
    public function __construct() {
        // retrieve current state
        $this->_state = $_SESSION['_auth_reset_state'] ?? null ;
    }

    private function setState(?int $step, ?string $user, ?string $check)
    {

        if ( !is_object( $this->_state ) )
            $this->_state = (object)[ 'step'=> 0 , 'user'=>'' , 'check'=>''] ;

        if ( $step !== null )
            $this->_state->step = $step ;
        if ($user !== null)
            $this->_state->user = $user;
        if ($check !== null)
            $this->_state->check = $check;

        $_SESSION['_auth_reset_state'] = $this->_state ;


    }


    public function start( $app )
    {
        $this->setState(0 , '', '') ;

        if ( !$app )
            $error = \sys\Error::msg(800 ) ;
        else {
            $error = '';
            if (isset($_POST['_sendcode']) && $app->checkCSRF()) {
                $user = trim(strtolower($_POST['username'] ?? ''));
                if (\sys\Valid::account($user)) {
                    $result = $app->apiPost0('v1/login/sendresetcode', ['user' => $user]);
                    if ($result && isset($result->ok) && $result->ok === true) {
                        $this->setState(1, $user, null);
                        header('Location: /auth/enter-code');
                        exit;
                    }
                }

                $error = \sys\Error::msg(800);
            }
        }

        $app->showBlade('auth.reset', ['errormsg' => $error ?? false]);
        exit ;
    }

    public function code( $app ) {

        if ( !$app || $this?->_state?->step !== 1 ||
            !\sys\Valid::account($this?->_state?->user ?? '')) {
            $this->start($app);
            exit;
        }

        $error = false ;
        if (isset($_POST['_code']) && $app->checkCSRF()) {
            $code8 = strtoupper(trim($_POST['reset-code'] ?? ''));
            $code9 = substr($code8, 0, 4).'-'.substr($code8, 4);
            if (\sys\Valid::otp($code9))
            {
                $chkpkt = [
                    'user' => $this?->_state?->user ?? '',
                    'code' => $code9,
                    't'    => time()
                ];
                $pkt = \sys\Crypto::encrypt(serialize($chkpkt), $_ENV['P_TOKEN']);
                $result = $app->apiPost0('v1/login/checkcode', ['pkt' => $pkt]);
                if ($result && is_object($result) && isset($result->ok) && $result->ok === true ) {
                    $this->setState(2, $chkpkt['user'], $chkpkt['code']);
                    header('Location: /auth/change-password');
                    exit;
                }
            }
            $error = \sys\Error::msg(802) ;
        }

        $app->showBlade('auth.code', ['user' => $this?->_state?->user ?? '' , 'errormsg' => $error ?? false ]);
        exit ;
    }

    public function change( $app ) {
        if ( !$app ||
            $this?->_state?->step !== 2 ||
                !\sys\Valid::account($this?->_state?->user ?? '') ||
                !\sys\Valid::otp($this?->_state?->check ?? '')
        ) {
            $this->start( $app ) ;
            exit ;
        }
        $error = false ;
        if ( isset($_POST['_change']) && $app->checkCSRF() ) {

            $chkpkt = [
                    'user' => $this?->_state?->user ?? '',
                    't'=>time() ,
                    'code'=> $this?->_state?->check ?? '' ,
                    'pwd1' => $_POST['password1'] ?? ''   ,
                    'pwd2' => $_POST['password2'] ?? ''   ,
                ] ;
                $pkt = \sys\Crypto::encrypt(serialize($chkpkt), $_ENV['P_TOKEN']);
                $result = $app->apiPost0('v1/login/chpwd', ['pkt'=>$pkt]);
                if ( $result && is_object($result)) {
                    if ( $result->ok === true ) {
                        // password changed
                        $this->setState(3, '', '');
                        header('Location: /auth/done');
                        exit;
                    }
                    else if ( $result->msg )
                        $error = \sys\Error::msg( $result->msg ) ;
                    else
                        $error = \sys\Error::msg(804) ;

                }
                else
                    $error = \sys\Error::msg(805) ;
       }
        $app->showBlade('auth.change', ['user' => $this?->_state?->user ?? '' , 'errormsg' => $error ?? false ]);
        exit ;

    }

    public function done( $app ) {
        if ( !$app || $this?->_state?->step !== 3 ) {
            $this->start( $app ) ;
            exit ;
        }

        $app->showBlade('auth.done', []);
    }



}