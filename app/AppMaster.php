<?php

    namespace app;

    use Dotenv\Dotenv;
    use sys\db\SQL;

    class AppMaster extends auth\AppAuthManager
    {

        use AppMasterMenuTrait ;

        private static bool                   $_env_loaded = false;
        private static ?\app\blade\PageViewer $_viewer = null ;

        public function __construct()
        {
            parent::__construct();
        }

        public static function checkApi() : bool  {

            return true ;
        }


        public static function viewPage($page, $data  ) : never
        {
            if ( !(self::$_viewer ?? false) )
                self::$_viewer = new \app\blade\PageViewer();

            self::$_viewer?->view($page , $data );
            exit ;
        }

        public static function apiPost(string $url, array | string $data  ): ?object
        {
            $url = $_ENV['API_DOMAIN'] . $url ;
            $res = \sys\uri\UriUtil::curlSend( 'POST' , $url , $data , self::$_id['atkn'] ?? null  , null );
            if ( $res && isset($res->error ))
                throw new \Exception($res->error);

            return $res ? (object)json_decode($res ) : null ;
        }


        public function route($req)
        {
            try
            {
                // very simple routing
                $uri_parts = explode('/' , strtolower($req));
                $base = $uri_parts[ 1 ] ?? false ;
                if ( empty($base) )
                    self::viewPage('welcome' , ['_login' => self::haveLogin()]);
                elseif ( $base === 'auth' )
                    self::authSystem($uri_parts);
                elseif ( !self::haveLogin() )
                    self::showLogin($uri_parts); // show login pages
                else {

                    // very simple switcher - only defined appbases
                     switch( $base ) {
                         case 'portal' :
                             new \app\portal\AppPortal()->run( $base , $uri_parts ) ;
                             break ;
                         case 'setup':
                             new \app\setup\AppSetup()->run( $base , $uri_parts ) ;
                             break ;
                         default:
                             http_response_code(404) ;

                     }


                }
            }
            catch( \Throwable $ex ) {

                echo $ex->getMessage();
            }

        }

    }


    /*
    class AppMaster extends auth\AuthManager
    {

        private const NO_LOGIN = ['sid' => 0 , 'pid' => 0 , 'db' => false , 'info' => false];
        private static                        $_id      = null;
        private static ?\app\blade\PageViewer $_viewer  = null;
        private static ?\app\auth\AuthManager     $_authman = null;
        private static bool $_env_loaded = false ;
        public function __construct()
        {
            self::$_id = $_SESSION[ '_id' ] ?? self::NO_LOGIN;
        }

        public static function getEnv(string $name) {
            $e = $_ENV[ $name ] ?? null ;
            if ( is_null($e ))
            {
                if ( !(self::$_env_loaded) )
                {
                    Dotenv::createImmutable(dirname(__DIR__.'/../.env'))->safeLoad();
                    self::$_env_loaded = true;
                    $e = $_ENV[ $name ] ?? null ;
                }
            }

            return $e ;
        }

        private static function haveLogin() : bool
        {
            return self::$_id && is_array(self::$_id) && (self::$_id[ 'sid' ] ?? 0) > 0 && (self::$_id[ 'pid' ] ?? 0) > 0;
        }

        private static function viewPage($page)
        {
            if ( !(self::$_viewer ?? false ))
                self::$_viewer = new \app\blade\PageViewer();
            self::$_viewer?->view($page , func_get_args());
        }

        private static function getAuthMan()
        {
            if ( !(self::$_authman ?? false ))
                self::$_authman = new \app\auth\AuthManager();
            return self::$_authman ;
        }

        private static function showLogin()
        {

            if ( (self::$_id[ 'sid' ] ?? -1) < 1 && (($tkn = $_COOKIE[ '_wd_auth_token' ] ?? false)) )
                self::getAuthMan()?->useLoginToken( self::$_id , $tkn ) ;

            if ( (self::$_id[ 'sid' ] ?? -1) < 1 )
            {
                // we need , so show the login form
                $error = '';

                if ( isset($_POST[ '_login' ]) && \sys\blade\BladeMan::checkCSRF() )
                {
                    $_SESSION[ '_remember_me_' ] = (($_POST[ 'remember_me' ] ?? '') === 'on');
                    // $error = $this->attemptLogin($app , $_POST[ 'email' ] ?? '' , $_POST[ 'password' ] ?? '');
                    $error = self::getAuthMan()?->attemptLogin( self::$_id , $_POST[ 'email' ] ?? '' , $_POST[ 'password' ] ?? '');

                    if ( (self::$_id[ 'sid' ] ?? -1) > 0 )
                    {
                        header('Location: '.$_SERVER[ 'REQUEST_URI' ]);
                        exit;
                    }
                }

                self::viewPage( 'layout.auth.login' , ['error'=> $error ]);
            }
        }


        public function route($req)
        {
            $uri_parts = explode('/' , strtolower($req));
            if ( empty($uri_parts[ 1 ] ?? false) )
                self::viewPage('welcome' , ['_login' => self::haveLogin()]);
            elseif ( $uri_parts[ 1 ] === 'auth' )
                self::authSystem($uri_parts);
            elseif ( !self::haveLogin() )
                self::showLogin($uri_parts);
            else
                echo 'n/i yet';

        }
    }
    */
    /*
     *
     *  Dotenv\Dotenv::createImmutable(__DIR__ . '/../../' )->safeLoad();

        define( '_DEV_MODE' , $_ENV[ 'DEV_MODE' ] ?? false  ) ;

        if ( _DEV_MODE ) {
            // set dev mode settings
            ini_set('log_errors' , 1);
            ini_set('display_errors' , 1);
            ini_set('display_startup_errors' , 1);
            error_reporting(E_ALL & ~E_PARSE);;
            define(  '_BUILD' ,microtime(true)   ) ;
        }
        else
            define(  '_BUILD' , $_ENV['BUILD'] ?? 1000  ) ;


    <?php

    namespace app ;


    class AppMaster {

        use engine\AppViewEngineTrait ;

        private static $_id = null;

        private const NO_LOGIN = [
                'sid' => 0 , 'pid' => 0 , 'db' => false , 'info' => false ];

        private static ?\app\engine\AppAssetStore $_store = null ;

        public function __construct() {
            self::$_id = $_SESSION[ '_id' ] ?? self::NO_LOGIN ;
        }

        public function getLogin() : array {
            return self::$_id ?? $_SESSION[ '_id' ] ?? self::NO_LOGIN ;
        }

        public function setLogin( $sid , $pid , $db , $info )
        {
            self::$_id = $_SESSION[ '_id' ] = ['sid' => $sid , 'pid' => $pid , 'db' => $db , 'info' => $info];
            if ( $sid > 0 && $pid > 0 && $_SESSION[ '_remember_me_' ] ?? false )
               (new \app\login\wd\AppLoginSystem())->rememberLogin($sid );
        }

        public function signOut() {
            $sid = self::$_id[ 'sid' ] ?? -1 ;
            self::$_id = $_SESSION[ '_id' ] = self::NO_LOGIN ;
            ( new \app\login\wd\AppLoginSystem())->persistSignOut( $sid );
        }

        private function haveLogin()
        {
            return self::$_id && is_array(self::$_id) && (self::$_id[ 'sid' ] ?? 0) > 0 && (self::$_id[ 'pid' ] ?? 0) > 0;
        }

        public function getSID() : int
        {
            return self::$_id[ 'sid' ] ?? -1;
        }

        public function getPID() : int
        {
            return self::$_id[ 'pid'] ?? -1 ;
        }

        public function generateBearer() {

            $sid = self::$_id['sid'] ?? -1 ;
            $pid = self::$_id['pid'] ?? -1 ;
            $ttl = 5 ;
            if ( $sid > 0 && $pid > 0 )
                return \sys\SecureToken::encrypt(\serialize([
                        'v'=>'V4' ,
                        'sid' => $sid ,
                        'pid' => $pid ,
                        'ttl'=> $ttl  ,
                        'exp' => time() + $ttl ]) , 'BTOKEN');
            return '' ;
        }

        public function route(): bool {


                $uri = \sys\UriUtil::getURIObject($_SERVER['REQUEST_URI'] ?? '' , $_SERVER[ 'REQUEST_METHOD' ]);
                if ( empty($uri) )
                    return false;

                if ( empty($uri->_base ))
                {
                    if ( !($this->haveLogin()))
                        return $this->ViewPage('pages.welcome');
                    else
                        \sys\UriUtil::navigateTo( '/portal' ) ;

                    return false ;
                }


                if ( $uri->_base === 'auth' )
                    ( new \app\login\wd\AppLoginSystem())->loginTasks( $this , $uri ) ;
                elseif ( !$this->haveLogin() )
                    return (new \app\login\wd\AppLoginSystem())->performLogin( $this )  ;
                else
                    return $this->ViewModel( $uri ) ;

            return true ;
        }



    }
     */