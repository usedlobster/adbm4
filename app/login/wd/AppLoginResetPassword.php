<?php

    namespace app\login\wd;

    class AppLoginResetPassword
    {

        use AppLoginBackEndTrait;

        // se
        private const array FT_RESET_EMAIL  = [3 , [['5 minute' , 10]]];
        private const array FT_RESET_IP     = [4 , [['5 minute' , 10]]];
        private const array FT_VERIFY_EMAIL = [5 , [['5 minute' , 10]]];

        public function startResetPasswordPage($app)
        {
            $error = '';
            $_SESSION[ '_reset_stage' ] = 0;
            $_SESSION[ '_reset_email' ] = null ;
            $_SESSION[ '_reset_check' ] = null ;
            if ( isset($_POST[ '_sendcode' ]) && \sys\BladeMan::checkCSRF() )
            {
                $email = strtolower(trim($_POST[ 'email' ] ?? ''));
                if ( empty($email) )
                    $error = 'Email Address Required';
                elseif ( !\sys\validate\Valid::email($email) )
                    $error = 'Invalid Email Address';
                else
                {
                    if ( !(\sys\Audit::rateLimit($email , self::FT_RESET_EMAIL , 0)) || !(\sys\Audit::rateLimit($_SERVER[ 'REMOTE_ADDR' ] , self::FT_RESET_IP , 0)) )
                        $error = 'Too many recent requests';
                    else
                    {
                        $this->generateResetCodeIfNecessary($email);
                        $_SESSION[ '_reset_email' ] = $email;
                        $_SESSION[ '_reset_stage' ] = 1;
                        \sys\UriUtil::navigateTo('/auth/reset-verify-code');
                    }
                }
            }

            return $app?->ViewPage('auth.reset.start' , ['error' => $error ?? '-']);
        }

        public function verifyResetCodePage($app)
        {
            $email = $_SESSION[ '_reset_email' ] ?? '';
            if ( $_SESSION[ '_reset_stage' ] !== 1 || empty($email) || \sys\validate\Valid::email($email) !== true )
                \sys\UriUtil::navigateTo('/auth/reset-password');

            $error = '';
            if ( !(\sys\Audit::rateLimit($email , self::FT_VERIFY_EMAIL , 0)) )
                $error = 'Too many requests';
            elseif ( isset($_POST[ '_verify' ]) && \sys\BladeMan::checkCSRF() )
            {
                $otp = trim($_POST[ 'otp' ] ?? '');
                // put back '-' in code , and make all uppercase.
                $code = strtoupper(substr($otp , 0 , 4).'-'.substr($otp , 4 , 4));
                // is this a valid code format.
                if ( preg_match('/^[1-9A-D]{4}-[1-9A-D]{4}$/' , $code) !== 1 )
                    $error = 'Invalid Reset Code';
                else
                {
                    // check if code is valid and active
                    $error = $this->checkResetCode($email , $code);
                    if ( empty($error) )
                    {
                        $_SESSION[ '_reset_stage' ] = 2;
                        $_SESSION[ '_reset_check' ] = \sys\SecureToken::encrypt(serialize(['email' => $email , 'time' => time()]) , 'ITOKEN');
                        \sys\UriUtil::navigateTo('/auth/reset-change');
                    }
                }
            }

            return $app?->ViewPage('auth.reset.verify' , ['error' => $error]);
        }

        public function changePasswordPage($app)
        {
            $error = '';

            $email = $_SESSION[ '_reset_email' ] ?? '';
            if ( $_SESSION[ '_reset_stage' ] !== 2 || empty($email) || \sys\validate\Valid::email($email) !== true )
                \sys\UriUtil::navigateTo('/auth/reset-password');

            //  check rate limit of this page
            if ( !(\sys\Audit::rateLimit($email , self::FT_VERIFY_EMAIL , 0)) )
                $error = 'Too many requests';
            else try
            {
                $check = $_SESSION[ '_reset_check' ] ?? '';
                $j = unserialize(\sys\SecureToken::decrypt($check , 'ITOKEN'));
                if ( $j === false || (($j[ 'email' ] ?? '') !== $email) )
                    $error = 'Invalid Reset Code';
                elseif ( (time() - $j[ 'time' ]) > 600 )
                    $error = 'Reset Code Expired';
                elseif ( isset($_POST[ '_change' ]) && \sys\BladeMan::checkCSRF() )
                {
                    $pwd1 = trim($_POST[ 'password' ] ?? '');
                    $pwd2 = trim($_POST[ 'password_confirm' ] ?? '');
                    $error = $this->attemptPasswordChange($email , $pwd1 , $pwd2);
                    if ( empty($error) )
                    {
                        $_SESSION['_reset_stage'] = 3;
                        \sys\UriUtil::navigateTo('/auth/reset-done');
                    }
                }
            }
            catch ( \Throwable ) {
                $error = 'An error occurred during password reset';
            }
            return $app?->ViewPage('auth.reset.change' , ['error' => $error]);
        }

        public function resetDonePage($app) {

            if ( $_SESSION[ '_reset_stage' ] !== 3 )
                \sys\UriUtil::navigateTo('/auth/reset-password');

            $_SESSION['_reset_stage'] = 0 ;
            $_SESSION['_reset_email'] = null ;
            $app->ViewPage( 'auth.reset.done' , [] );

        }

        public function clearLoginSessions( $sid ) : void  {


        }

    }