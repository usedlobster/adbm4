<?php

    namespace api;

    use app\login\wd\AppLoginSystem;
    use sys\Util;

    class ApiBaseClass
    {
        public function __construct() {}

        protected function sendUnauthorized() : never
        {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json');
            echo json_encode([
                    'status' => 401 ,
                    'error' => 'Invalid or expired authentication token'
            ]);
            exit;
        }

        protected function sendError($e) : never
        {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => (string)$e->getMessage()]);
            exit;
        }

        protected function sendResult( $result  ) : never
        {
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode( $result ) ;
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
            return false;; // not error , but not a valid bearer token
        }

        private function getUserFromBearerID($id) : array|bool
        {
            try
            {
                if (
                        is_array($id) && $id[ 'v' ] === 'V4' && (($sid = $id[ 'sid' ] ?? 0) > 0) && (($pid = $id[ 'pid' ] ?? 0) > 0)
                )
                {
                    $red = \sys\Util::getRedis(1);
                    if ( $red )
                    {
                        $s = $red->get(($rkey = 'user:'.$sid.':'.$pid));
                        if ( $s )
                            return \unserialize($s);
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








    }