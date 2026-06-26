<?php

    namespace sys;

    class Audit {

        const int FAIL_TYPE_REG_TOTP    = 5 ;
        public static function getFail( string $idkey , $ft , $s ) : int {
            return \sys\db\SQL::Get0( " select count(*) from <DBM>.sys_fail where ft = ? and idkey = ? and ( t  > now() - interval ? second )  ",
                [ $ft , md5(  'wd84' . $idkey ) , $s ] );
        }

        public static function setFail( string $idkey , $ft ) : bool
        {
            return \sys\db\SQL::Exec("insert into <DBM>.sys_fail ( `idkey` , `ft` , `t` ) values( ? , ? , now()  )", [md5('wd84'.$idkey), $ft]);
        }

        public static function globalLimitOK(string $qkey, int $limit, int $dur): bool
        {
            $red = \sys\Redis::getRedis(2);
            if (!$red)
                return false;
            $key = '_glb:'.$qkey;
            $count = $red->incr($key);
            if ($count === false)
                return false;
            if ($count === 1 || $red->ttl($key) < 0 )
                $red->expire($key, $dur);
            return ( $count <= $limit ) ;
        }


        public static function rateLimitOK( string $key , array $check ) : bool
        {
            try
            {
                /*
                $type = $check[0] ?? 0 ;
                if ( $type > 0 ) {
                    if ( ! \sys\Audit::setFail( $key , $type ))
                        return false ;
                    for ( $i = 1 ; $i < count( $check ) ; $i+=2 ) {
                        $n = $check[$i]   ?? 0   ;
                        $s = $check[$i+1] ?? 0  ;
                        if ( $n > 0 && $s > 0 ) {
                            $failCount = \sys\Audit::getFail($key, $type, $s );
                            if ( $failCount >= $n)
                                return false;
                        }
                    }
                }
                */
               return true;

            }
            catch ( \Throwable $ex )
            {
                error_log( $ex ) ;
                // for safety assume was rate limited
                return false;
            }
        }


    }