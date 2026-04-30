<?php

namespace sys;

class Redis {


    private static $_redis = [];

    public static function getRedis($index = -1) : ?\Redis
    {
        try {
            $index = $index < 0 ? ($_ENV['REDIS_INDEX'] ?? 0) : $index;
            $inst = self::$_redis[$index] ?? false;
            if (!$inst) {
                if (!($inst = new \Redis()))
                    return null;

                $inst->connect($_ENV['REDIS_HOST'] ?? '127.0.0.1',
                    $_ENV['REDIS_PORT'] ?? 6379,
                    $_ENV['REDIS_TIMEOUT'] ?? 5);
                if (($rpw = $_ENV['REDIS_PASSWORD'] ?? false))
                    $inst->auth($rpw);
                $inst->select($index);
                self::$_redis[$index] = $inst;
            }

            // if ( !$inst->isConnected() )
            //    return null ;

            return $inst;
        } catch (\Exception $ex) {
            error_log($ex->getMessage());
        }

        return null;
    }

    public static function saveData( $keybase , $data , $ttl = 300 , $index = 2 ) : string
    {
        $redis = self::getRedis($index);
        if ( $redis ) {
            $attempt = 20 ;
            do {
                $id = base64_encode(random_bytes(24));
                $rkey = $keybase . ':' . $id ;
                if ( !$redis->exists( $rkey )) {
                    if ( $redis->setex( $rkey , $ttl ,  serialize($data) ))
                        return $id ;
                }

                usleep( 10000 ) ;
            } while ( --$attempt > 0 )  ;
        }

        return '' ;
    }

    public static function loadData( $keybase , $id , $index = 2  ) : mixed {
        $redis = self::getRedis($index);
        if ( $redis ) {
            $rkey = $keybase.':'.$id;
            return unserialize($redis->get($rkey));
        }
        return null ;
    }

    public static function deleteData( $keybase , $id , $index = 2 ) : bool {
        $redis = self::getRedis($index);
        if ( $redis ) {
            $rkey = $keybase.':'.$id;
            return $redis->del($rkey);
        }
        return false ;
    }


}

