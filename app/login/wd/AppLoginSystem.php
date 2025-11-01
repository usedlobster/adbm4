<?php

    namespace app\login\wd;

    class AppLoginSystem
    {

        use AppLoginBackEndTrait;
        use AppLoginPersistTrait;
        use AppLoginApiTrait;

        private const array FT_LOGIN_IP    = [1 , [['5 minute' , 30] , ['30 second' , 10]]];
        private const array FT_LOGIN_EMAIL = [2 , [['5 minute' , 10] , ['30 second' , 3]]];


        public function __construct() {}


        // show login pages , until we have [ sid / pid ]
        public function performLogin(\app\AppMaster $app) : bool
        {
            if ( $app->getSID() < 1 )
            {
                if ( isset($_COOKIE[ '_adbm_token_' ]) )
                {
                    if ( ($sid = $this->useLoginToken()) > 0 )
                        $app?->setLogin($sid , 0 , false , false);
                }
            }

            if ( ($sid = $app->getSID()) < 1 )
            {
                // we need , so show the login form
                $error = '';
                if ( isset($_POST[ '_login' ]) && \sys\BladeMan::checkCSRF() )
                {
                    $_SESSION[ '_remember_me_' ] = (($_POST[ 'remember_me' ] ?? '') === 'on');
                    $error = $this->attemptLogin($app , $_POST[ 'email' ] ?? '' , $_POST[ 'password' ] ?? '');


                    if ( ($sid = $app->getSID()) > 0 )
                    {
                        header('Location: '.$_SERVER[ 'REQUEST_URI' ]);
                        exit;
                    }
                }

                return $app?->ViewPage('auth.login' , ['error' => $error]);
            }

            if ( ($pid = $app->getPID()) < 1 )
            {
                // we need , so show the login form
                $error = '';
                $projects = $this->getUserProjectsAvailable($sid);
                $picked = (isset($_POST[ 'pick-project' ]) && \sys\BladeMan::checkCSRF()) ? $_POST[ 'pick-project' ] ?? -1 : -1;

                $error = $this->attemptPickProject($app , $sid , $picked , $projects);

                if ( ($pid = $app->getPID()) > 0 )
                {
                    header('Location: '.$_SERVER[ 'REQUEST_URI' ]);
                    exit;
                }

                return $app?->ViewPage('auth.project' , ['error' => $error , 'list' => $projects]);
            }

            return true;
        }

        private function attemptLogin(\app\AppMaster $app , $email , $pwd) : string
        {
            try
            {
                if ( !empty($email) && !empty($pwd) )
                {
                    // rate limit request

                    if ( !\sys\Audit::rateLimit($email , self::FT_LOGIN_EMAIL , 5) || !\sys\Audit::rateLimit($_SERVER[ 'REMOTE_ADDR' ] , self::FT_LOGIN_IP , 5) )
                        return 'Too Many Attempts, try again later';

                    $sid = $this->getSIDByEmailAndPassword($email , $pwd);
                    if ( !$sid || $sid < 1 )
                        return 'Invalid Credentials';

                    $app?->setLogin($sid , 0 , false , false);

                    return '';
                }
            }
            catch ( \Throwable $ex )
            {
                return 'Unable to login'.(_DEV_MODE ? $ex->getMessage() : '');
            }

            return 'Failed Login';
        }

        private function attemptPickProject(\app\AppMaster $app , int $sid , int $pick , array $list) : string
        {
            if ( $sid < 0 || !is_array($list) || count($list) === 0 )
                return 'No projects found';

            if ( count($list) === 1 )
                $pid = $list[ 0 ][ 'pid' ] ?? 0;
            elseif ( $pick > 0 && in_array($pick , array_column($list , 'pid')) )
                $pid = $pick;
            else
                $pid = 0;

            $app->setLogin($sid , $pid , false , false);
            return $pid >= 0 ? '' : 'Invalid Project';
        }

        public function loginTasks(\app\AppMaster $app , object $uri)
        {
            switch ( $uri->_parts[ 1 ] ?? false )
            {
                case 'cancel'  :
                    $app->setLogin(0 , 0 , false , false);
                    $_SESSION[ '_reset_stage' ] = 0;
                    \sys\UriUtil::navigateTo('/portal');
                    break;
                case 'signout' :
                    $sid = $app?->getSID() ?? 0;
                    if ( $sid > 0 )
                    {
                        $app?->signOut();
                        $app->ViewPage('auth.signout' , ['uri' => $uri]);
                        exit;
                    }
                    else
                        \sys\UriUtil::navigateTo('/');


                    break;
                case 'reset-password'  :
                    new \app\login\wd\AppLoginResetPassword()->startResetPasswordPage($app);
                    break;
                case 'reset-verify-code' :
                    new \app\login\wd\AppLoginResetPassword()->verifyResetCodePage($app);
                    break;
                case 'reset-change' :
                    new \app\login\wd\AppLoginResetPassword()->changePasswordPage($app);
                    break;
                case 'reset-done' :
                    new \app\login\wd\AppLoginResetPassword()->resetDonePage($app);
                    break;
                default :
                    $app->ErrorPage('error.404' , 404 , ['msg' => $uri->_path]);
                    break;
            }


            exit;
        }

        //


    }