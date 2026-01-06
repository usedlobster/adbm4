<?php

    namespace app\auth;

    class AppAuthManager
    {

        use AppAuthUtilTrait;

        // cached state
        private const NO_LOGIN = [
                'sid' => 0 ,
                'pid' => 0 ,
                'db' => false ,
                'title'=>false,
                'atkn' => false ,
                'rtkn' => false ,
                'info' => false ,
        ];

        protected static array|null $_id = null;

        public function __construct()
        {
            self::$_id = $_SESSION[ '_id' ] ?? self::NO_LOGIN;
        }

        protected function haveLogin() : bool
        {
            return self::$_id &&
                    is_array(self::$_id) &&
                    (self::$_id[ 'sid' ] ?? 0) > 0 &&
                    (self::$_id[ 'pid' ] ?? 0) > 0 &&
                    !empty(self::$_id['atkn'] ?? '' );
        }

        protected function setProject( $find )
        {
            if ( $find && is_object($find))
            {
                self::$_id[ 'pid' ]   = $find->pid ?? -1;
                self::$_id[ 'db' ]    = $find->db ?? false ;
                self::$_id[ 'title' ] = $find->title ?? '' ;
                $_SESSION[ '_id' ]    = self::$_id;
                header('Location: '.$_SERVER[ 'REQUEST_URI' ]);
                exit;
            }
        }

        protected function showLogin()
        {
            try
            {

                if ( ( self::$_id[ 'sid' ] ?? 0 ) < 1 )
                {
                    $errormsg = $this->tryUserAndPasswordLogin();
                    if ( $errormsg === true && ( ( self::$_id[ 'sid' ] ?? 0 ) > 0 ) )
                    {
                        header( 'Location: ' . $_SERVER[ 'REQUEST_URI' ] );
                        exit;
                    }

                    \app\AppMaster::viewPage( 'layout.auth.login' , [ 'errormsg' => $errormsg ?? false ] );
                    exit;
                }

                if ( ( self::$_id[ 'pid' ] ?? 0 ) < 1 )
                {
                    $picked = ( isset( $_POST[ 'pick-project' ] ) && \sys\blade\BladeMan::checkCSRF() ) ? $_POST[ 'pick-project' ] ?? -1 : -1;
                    $list   = $this->getProjectList();
                    if ( empty( $list ) )
                        $errormsg = 'No Projects Available';
                    else if ( $picked > 0 )
                    {
                        $find = array_find( $list , function ( $v ) use ( $picked )
                        {
                            return $v?->pid == $picked;
                        } );
                        if ( $find )
                            $this->setProject( $find );
                    }
                    elseif ( count( $list ) == 1 )
                    {
                        $this->setProject( $list[ 0 ] );
                    }

                    \app\AppMaster::viewPage( 'layout.auth.pick' , [ 'errormsg' => $errormsg ?? false , 'list' => $list ] );
                }
            }
            catch ( \Exception $e )
            {
                die( $e->getMessage() );
            }


        }

        protected function authSystem( $uri_parts )
        {
            if ( $uri_parts[1] === 'auth' ) switch ( $uri_parts[ 2 ] ?? '' )
            {
                case 'signout' :
                    self::$_id = self::NO_LOGIN;
                    session_destroy();
                    header( 'Location: /' );
                    exit;
                case 'cancel':
                    self::$_id = $_SESSION[ '_id' ] = self::NO_LOGIN;
                    header( 'Location: /' );
                    exit;
                case 'reset-password' :
                    new \app\auth\AppAuthReset()->startResetPasswordPage();
                    break;
                case 'enter-code':
                    new \app\auth\AppAuthReset()->enterCodePage();
                    break;
                case 'change-password' :
                    new \app\auth\AppAuthReset()->changePasswordPage();
                    break;
                default:
                    echo 'n/i:';
            }

            exit;
        }

    }