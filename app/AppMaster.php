<?php

    namespace app;

    class AppMaster
    {

        use AppRenderTrait,AppMenuTrait;

        static protected $_login = null;
        static protected $_blade = null;
        static protected \Redis | null $_redis = null;

        const int EDIT_CACHE_TTL = 60;
        const int EDIT_MASK      = 3 ;

        public function __construct()
        {
            $t = time();
            if ( !isset($_SESSION[ '_check' ]) || ($_SESSION[ '_check' ] + 60) < $t )
            {
                $_SESSION[ '_check' ] = $t;
                if ( \sys\db\SQL::Get0(" SELECT NOW() " , []) === false )
                {
                    echo $this->view('error.db' , []);
                    exit;
                }
            }

            // load login helper
            if ( self::$_login === null )
                self::$_login = new \app\AppLogin($this);

            if ( self::$_redis === null || self::$_redis->ping() === false)
                self::$_redis = \sys\Util::getRedis();


            // load blade viewer
            if ( self::$_blade === null )
                self::$_blade = new \sys\Blade();


        }

        public function postRedirect($rawreq) : void
        {
            // do post-get-redirect
            if ( is_array($_POST) && count($_POST) > 0 )
            {
                $_SESSION[ '_post' ] = $_POST;
                unset($_POST);
                if ( headers_sent() )
                    throw new \Exception('Headers already sent');
                header('Location: '.$rawreq);
                exit;
            }
            elseif ( isset($_SESSION[ '_post' ]) )
            {
                $_POST = $_SESSION[ '_post' ];
                unset($_SESSION[ '_post' ]);
            }
            else
                $_POST = [];
        }

        public function checkCSRF() : bool
        {
            if ( !isset($_SESSION[ '_csrf' ]) || !isset($_POST[ '_token' ]) || $_SESSION[ '_csrf' ] !== $_POST[ '_token' ] )
            {
                \sys\UriUtil::navigateTo('/auth/signout');
                return false; // should never get here ! as exit above
            }

            return true;
        }


        public function view($v , $d = null)
        {
            $def = ['app' => $this , 'view' => $v];
            if ( $d === null )
                $d = $def;
            elseif ( !is_array($d) )
                $d = array_merge($def , [$d]);
            else
                $d = array_merge($def , $d);

            return self::$_blade->getBlade()?->run($v , $d);
        }






        public function route($req)
        {
            if ( $req === '/' )
            {
                echo $this->view('welcome');
                return;
            }

            $uri = \sys\UriUtil::getURIObject($req , $_SERVER[ 'REQUEST_METHOD' ]);
            if ( $uri === null )
                return;

            if ( $uri->_base === 'reset' )
            {
                self::$_login->resetPasswordLogic($uri);
                exit;
            }
            elseif ( $uri->_base === 'auth' )
            {
                $act = $uri->_parts[ 1 ] ?? '';
                $error = '';
                switch ( $act )
                {
                    case 'cancel':
                        self::$_login->signOut();
                        \sys\UriUtil::navigateTo('/portal');
                        break;
                    case 'signout' :
                        self::$_login->signOut();
                        echo \sys\Blade::getBlade()?->run('login.logout' , []);
                        break;
                }

                exit;
            }
            elseif ( !self::$_login->haveLogin($uri) )
            {
                if ( self::$_login->ViewLoginLogic($uri) !== true )
                    exit;
            }

            $this->showContent( $uri ) ;





        }


    }