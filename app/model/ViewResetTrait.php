<?php

    namespace app\model;

    use ZxcvbnPhp\Zxcvbn;

    trait ViewResetTrait {


        private function removeLoginCookie(bool $allDevices = false) : void
        {
            if ( $allDevices )
                \sys\db\SQL::Exec("DELETE FROM <DB>.sys_tokens WHERE sid = ? AND type = 0" , [self::$_info[ 'sid' ] ?? 0]);
            else
            {
                // Remove only the current token
                $currentToken = $_COOKIE[ '_login' ] ?? '';
                if ( $currentToken )
                    \sys\db\SQL::Exec("DELETE FROM <DB>.sys_tokens WHERE tkn = ? AND type = 0" , [ $currentToken]);
            }

            $cookie_options = [
                    'expires' => -1 , // Past time to ensure deletion
                    'path' => '/' ,
                    'secure' => true ,
                    'httponly' => true ,
                    'samesite' => 'Lax'
            ];
            setcookie('_login' , '' , $cookie_options);
            $_COOKIE[ '_login' ] = '';
        }


        public function signOut(bool $allDevices = false) : void
        {
            // Always clear the current session
            session_destroy();
            session_start();
            $this->removeLoginCookie( $allDevices );
        }



        private function generateResetCodeIfNecessary($email) : void
        {
            // random delay
            usleep(random_int(50000 , 150000));

            // who is requesting
            $info = \sys\db\SQL::RowN(<<<SQL
                                          SELECT sa.sid, su.name 
                                              FROM <DB>.sys_auth sa 
                                                  LEFT JOIN <DB>.sys_users su ON (su.sid=sa.sid) 
                                                      WHERE su.email = ? AND sa.active > 0
                                          SQL, [$email]);

            $sid = $info[ 'sid' ] ?? 0;
            if ( $sid < 1 )
                return; // silently ignore - unknown email

            // get user's name
            $name = $info[ 'name' ] ?? 'user';
            // do we already have an otp code
            $otp_hash = \sys\db\SQL::Get0(" SELECT otp FROM <DB>.sys_reset_otp WHERE sid = ? AND  exp > ( now() - INTERVAL 10 minute ) " , [$sid]);
            if ( !empty($otp_hash) )
                return; // code already sent

            $resetCode = \sys\Util::generate8DigitCode();
            if (
                    \sys\db\SQL::Exec(<<<SQL
                                          REPLACE INTO <DB>.sys_reset_otp (sid, otp ,exp) VALUES (?,sha2( concat( '_W' , ?  , '_D' ) , 256 ),now())
                                          SQL, [$sid , $resetCode]) !== true
            )
                return; // silently drop

            // need to send email now with code
            $email = 'wooster@alandaleuk.com'; // TODO : safety
            $data = ['name' => $name , 'code' => $resetCode];
            \sys\wd\SendEmail::sendTemplate($email , $name , 'reset-code' , $data);
        }

        private function changePassword(string $email , int $sid , string $pwd1 , string $pwd2) : bool|string
        {
            if ( empty($email) || empty($pwd1) || empty($pwd2) || $sid < 1 )
                return 'Invalid Password';
            elseif ( $pwd1 !== $pwd2 )
                return 'New and confirmed passwords do not match';
            else
            {
                $z = new Zxcvbn();
                $result = $z->passwordStrength($pwd1);
                if ( ($result[ 'score' ] ?? 0) < 3 )
                    return 'Password is too weak. Please use a stronger password';

                $hash = password_hash($pwd1 , PASSWORD_DEFAULT);
                if (
                        \sys\db\SQL::Exec(<<<SQL
                                              UPDATE <DB>.sys_auth SET pwd = ? , changed = now()  WHERE sid = ? 
                                              SQL, [$hash , $sid]) !== true
                )
                    return 'Unable to change password';

            }

            return false;
        }



        public function resetPasswordLogic($uri) : void
        {
            try
            {
                $error = '';
                $act = $uri->_parts[ 1 ] ?? '';
                switch ( $act )
                {
                    case 'reset-password':
                        // forget password
                        $_SESSION[ '_reset_stage' ] = 0;
                        $_SESSION[ '_reset_email' ] = '';
                        if ( isset($_POST[ '_sendcode' ]) && self::$_app->checkCSRF() )
                        {
                            $email = strtolower(trim($_POST[ 'email-address' ] ?? ''));
                            if ( \sys\validate\Valid::email($email) !== true )
                                $error = empty($email) ? '' : 'Invalid email address';
                            else
                            {
                                $ip = $_SERVER[ 'REMOTE_ADDR' ] ?? '127.0.0.1';
                                $failCount1 = \sys\Audit::getFail($ip , self::FT_IP_RESET_REQUEST , '1 minute');
                                $failCount2 = \sys\Audit::getFail($email , self::FT_EMAIL_RESET_REQUEST , '1 minute');
                                if ( $failCount1 >= 10 || $failCount2 >= 3 )
                                    $error = 'Too many reset requests. Please try again in a few minutes';
                                else
                                {
                                    \sys\Audit::setAttempt($ip , self::FT_IP_RESET_REQUEST);
                                    \sys\Audit::setAttempt($email , self::FT_EMAIL_RESET_REQUEST);

                                    $this->generateResetCodeIfNecessary($email);

                                    $_SESSION[ '_reset_email' ] = $email;
                                    $_SESSION[ '_reset_stage' ] = 1;
                                    \sys\UriUtil::navigateTo('/reset/reset-verify');
                                }
                            }
                        }
                        // show reset page
                        echo \sys\Blade::getBlade()?->run('login.reset.start' , ['error' => $error]);
                        break;
                    case 'reset-verify' :
                        // enter reset code on this page
                        $email = $_SESSION[ '_reset_email' ] ?? '';
                        if ( $_SESSION[ '_reset_stage' ] !== 1 || empty($email) || \sys\validate\Valid::email($email) !== true )
                            \sys\UriUtil::navigateTo('/reset/reset-password');
                        elseif ( isset($_POST[ '_verify' ]) && self::$_app->checkCSRF() )
                        {
                            $failCount1 = \sys\Audit::getFail($email , self::FT_VERIFY_CODE , '1 minute');
                            if ( $failCount1 >= 4 )
                                $error = 'Too many reset requests. Please try again in a few minutes';
                            else
                            {
                                $otp = trim($_POST[ 'otp' ] ?? '');
                                $code = substr($otp , 0 , 4).'-'.substr($otp , 4 , 4);
                                if ( preg_match('/^[0-9]{4}-[0-9]{4}$/' , $code) !== 1 )
                                    $error = 'Invalid Reset Code';
                                else
                                {
                                    $sid = \sys\db\SQL::Get0(<<< SQL
                                                                 SELECT sid FROM <DB>.sys_reset_otp 
                                                                    WHERE otp = sha2( concat( '_W' , ? , '_D' ) , 256 ) AND 
                                                                          now() > exp 
                                                                 SQL, [$code]);
                                    if ( $sid === false || ($sid ?? 0) < 1 )
                                        $error = 'Invalid Reset Code';
                                    else
                                    {
                                        $_SESSION[ '_reset_stage' ] = 2;
                                        $_SESSION[ '_reset_sid' ] = $sid;
                                        \sys\UriUtil::navigateTo('/reset/reset-change');
                                    }
                                }
                            }
                        }
                        echo \sys\Blade::getBlade()?->run('login.reset.verify' , ['error' => $error]);
                        break;

                    case 'reset-change' :
                        $email = $_SESSION[ '_reset_email' ] ?? '';
                        $sid = $_SESSION[ '_reset_sid' ] ?? 0;
                        if ( $_SESSION[ '_reset_stage' ] !== 2 || $sid < 1 || empty($email) )
                            \sys\UriUtil::navigateTo('/reset/reset-password');
                        elseif ( isset($_POST[ '_change' ]) && self::$_app->checkCSRF() )
                        {
                            $error = $this->changePassword($email , $sid , $_POST[ 'password1' ] ?? '' , $_POST[ 'password2' ] ?? '');
                            if ( $error === false )
                            {
                                $_SESSION[ '_reset_stage' ] = 3;
                                \sys\UriUtil::navigateTo('/reset/reset-complete');
                            }
                        }
                        echo \sys\Blade::getBlade()?->run('login.reset.changepwd' , ['error' => $error , 'email' => $email]);
                        break;
                    case 'reset-complete' :
                        if ( $_SESSION[ '_reset_stage' ] !== 3 )
                            \sys\UriUtil::navigateTo('/portal');
                        echo \sys\Blade::getBlade()?->run('login.reset.complete' , []);
                        $_SESSION[ '_reset_stage' ] = 0;
                        break;
                    default:
                        http_response_code(404);
                        break;
                }
            }
            catch ( \Throwable $ex )
            {
                error_log($ex->getMessage());
                echo $ex->getMessage();
                exit;
            }
        }



    }