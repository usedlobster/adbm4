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

        public function route(): bool {


                $uri = \sys\UriUtil::getURIObject($_SERVER['REQUEST_URI'] ?? '' , $_SERVER[ 'REQUEST_METHOD' ]);
                if ( empty($uri) )
                    return false;

                if ( empty($uri->_base ))
                    return $this->ViewPage('pages.welcome' ) ;
                if ( $uri->_base === 'auth' )
                    ( new \app\login\wd\AppLoginSystem())->loginTasks( $this , $uri ) ;
                elseif ( !$this->haveLogin() )
                    return (new \app\login\wd\AppLoginSystem())->performLogin( $this )  ;
                else
                    return $this->ViewModel( $uri ) ;

            return true ;
        }



    }