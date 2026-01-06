<?php

    namespace sys;

    class Util
    {


        private static array $_redis = [];

        public static function getRedis(int $index = 0) : bool|\Redis
        {
            if ( $index < 0 )
                return false;

            try
            {
                $inst = self::$_redis[ $index ] ?? false;
                if ( !$inst )
                {
                    if ( !($inst = new \Redis()) )
                        return false;

                    $inst->connect($_ENV[ 'REDIS_HOST' ] ?? 'localhost' ,
                            $_ENV[ 'REDIS_PORT' ] ?? 6379 ,
                            $_ENV[ 'REDIS_TIMEOUT' ] ?? 5);
                    if ( ($rpw = $_ENV[ 'REDIS_PASSWORD' ] ?? false) )
                        $inst->auth($rpw);
                    $inst->select($index);
                    self::$_redis[ $index ] =  $inst;
                }

                if ( !$inst->isConnected() )
                    return false;
                return $inst;
            }
            catch ( \Exception $ex )
            {
                error_log($ex->getMessage());
            }
            return false;
        }

        public static function constantRunTime(callable $func , $args , float $execTime = .5)
        {
            $start  = microtime(true);
            $result = $func($args);
            $end    = microtime(true);
            $time   = $execTime - ($end - $start);
            if ( $time > 0.1 )
                usleep(floor($time * 1e6));
            return $result;
        }

        public static function saveCookie(string $n , string $v , int $h = 3600)
        {
            setcookie($n , $v , [
                    'expires' => time() + $h ,
                    'path' => '/' ,
                    'domain' => $_ENV[ 'APP_DOMAIN' ] ,
                    'secure' => true ,
                    'httponly' => true ,
                    'samesite' => 'Lax'
            ]);
            $_COOKIE[ $n ] = $v; // update instantly
        }

        public static function generateCode( ) : string {

            $chars = '123456789ABCDEFGH';

            $code = '';

            for ($i = 0; $i < 8; $i++) {
                if ( $i == 4 )
                    $code .= '-' ;

                $code .= $chars[\random_int(0,16)];
            }





            return $code;
        }

    }