<?php

    namespace sys;

    class Redis {

        private static  $_redis = [] ;

        public static function getRedis( $index = -1 ) : ?\Redis
        {
            try
            {
                $index = $index < 0 ? ($_ENV[ 'REDIS_INDEX' ] ?? 0 ) : $index ;
                $inst = self::$_redis[ $index ] ?? false;
                if ( !$inst )
                {
                    if ( !($inst = new \Redis()) )
                        return null ;

                    $inst->connect($_ENV[ 'REDIS_HOST' ] ?? '127.0.0.1' ,
                            $_ENV[ 'REDIS_PORT' ] ?? 6379 ,
                            $_ENV[ 'REDIS_TIMEOUT' ] ?? 5);
                    if ( ($rpw = $_ENV[ 'REDIS_PASSWORD' ] ?? false) )
                        $inst->auth($rpw);
                    $inst->select($index);
                    self::$_redis[ $index ] =  $inst;
                }

                // if ( !$inst->isConnected() )
                //    return null ;

                return $inst;
            }
            catch ( \Exception $ex )
            {
                error_log($ex->getMessage());
            }

            return null ;
        }
    }