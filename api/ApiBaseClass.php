<?php

    namespace api;

    use app\login\wd\AppLoginSystem;
    use sys\Util;

    class ApiBaseClass
    {
        public function __construct() {}

        private function sendUnauthorized() : never
        {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json');
            echo json_encode([
                    'status' => 401 ,
                    'error' => 'Invalid or expired authentication token'
            ]);
            exit;
        }

        protected function getBearerID() : array|bool
        {
            try
            {
                $b = $_SERVER[ 'HTTP_AUTHORIZATION' ] ?? false;
                if ( !empty($b) && strlen($b) < 512 && str_starts_with($b , 'Bearer ') )
                {
                    // get bearer token string from header
                    $bearer = substr($b , 7);
                    if ( empty($bearer) )
                        return false;

                    $red = \sys\Util::getRedis(1);
                    if ( $red )
                    {
                        $id = \unserialize($red->get('bear:'.$bearer));
                        if ( $id && is_array($id) )
                            return $id;
                    }

                    $s  = \sys\SecureToken::decrypt($bearer , 'BTOKEN');
                    $id = \unserialize($s);
                    if ( $red )
                        $red->setex('bear:'.$bearer , 600 , $s);

                    return $id;
                }
            }
            catch ( \Throwable  $ex )
            {
                // throw new \Exception('Bearer token error');
            }
            return false;;
        }

        private function getUserFromBearerID($id) : array|bool
        {
            try
            {
                if ( is_array($id) &&
                    $id[ 'v' ] === 'V4' &&
                    (($sid = $id[ 'sid' ] ?? 0) > 0) &&
                    (($pid = $id[ 'pid' ] ?? 0) > 0) )
                {
                    $red = \sys\Util::getRedis(1);
                    if ( $red )
                    {
                        $s = $red->get(($rkey = 'user:'.$sid.':'.$pid));
                        if ( $s )
                            return \unserialize($s) ;

                    }

                    $uinf = (new AppLoginSystem())->getUserProjectInfo($sid , $pid , false);
                    if ( $red && $uinf )
                        $red->setex($rkey , 300 , serialize($uinf));

                    return is_array($uinf) ? $uinf : false;
                }
            }
            catch ( \Throwable )
            {
            }
            return false;
        }


        public function getValidUser()
        {
            try
            {
                $id   = $this->getBearerID();
                if ( is_array($id) && $id[ 'v' ] === 'V4' )
                {
                    $exp = ($id[ 'exp' ] ?? 0);
                    $left = $exp > 0 ? ($exp - time()) : 0;
                    if ( $left > -5 )
                    {
                        $user = $this->getUserFromBearerID($id);
                        if ( $user )
                            return $user;
                    }
                }
            }
            catch ( \Throwable )
            {
            }

            $this->sendUnauthorized();
        }

        public function getRefreshToken() {


            $id   = $this->getBearerID();
            $exp  = ($id[ 'exp' ] ?? 0);
            $left = $exp > 0 ? ($exp - time()) : 0;
            if ( $left > -7200  )
            {
                $user = $this->getUserFromBearerID($id);
                if ( $user ) {

                }

            }
        }



    }