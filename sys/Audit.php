<?php

    namespace sys;

    class Audit {

        const int FAIL_TYPE_REG_TOTP    = 5 ;
        public static function getFail( string $idkey , $ft , $iv ) : int {
            return \sys\db\SQL::Get0( " select count(*) from <DBM>.sys_fail where ft = ? and idkey = ? and ( t  > now() - interval ? second )  ",
                [ $ft , md5( $idkey ) , $iv ] );
        }

        public static function setFail( string $idkey , $ft ) : bool
        {
            return \sys\db\SQL::Exec("insert into <DBM>.sys_fail ( `idkey` , `ft` , `t` ) values( md5(?) , ? , now()  )", [$idkey, $ft]);
        }


        /**
         * Implements a rate-limiting mechanism using a Redis-backed counter.
         * Ensures that a specified operation does not exceed the allowed number of executions
         * within a given time frame.
         *
         * @param  string  $key    The unique key to identify the resource or action being limited.
         * @param  int     $limit  The maximum number of allowed executions within the specified duration.
         * @param  int     $dur    The time window duration, in seconds, during which the $limit applies.
         *
         * @return bool Returns true if the operation is within the allowed limit; false otherwise.
         */
        public static function globalLimitOK(string $qkey, int $limit, int $dur): bool
        {

            $red = \sys\Redis::getRedis(2);
            if (!$red)
                return false;

            $key = 'quick:'.$qkey;
            $count = $red->incr($key);
            if ($count === false)
                return false;
            if ($count === 1 )
                $red->expire($key, $dur);
            return ( $count <= $limit ) ;
        }

        public static function rateLimitOK( string $key , array $check ) : bool
        {
            // check array is [ type , [ [ count1 , rate1 ]* {...} ]]
            try
            {
                $type = $check[0] ?? 0 ;
                if ( $type > 0 ) {
                    if ( is_array( $check[1] )) {

                        \sys\Audit::setFail($key, $type);
                        foreach ($check[1] as $chk) {
                            $n = is_numeric($chk['count']) ?  ($chk['count'] ?? 0 ) : 0 ;
                            $t = is_numeric($chk['rate'])  ?  ($chk['rate'] ?? 0 )  : 0 ;
                            if ($n > 0 && $t > 0) {
                                $failCount = \sys\Audit::getFail($key, $type, $t);
                                if ($failCount >= $n)
                                    return false;
                            }
                        }
                    }
                }

               return true;

            }
            catch ( \Throwable $ex )
            {
                // for safety assume was rate limited
                return false;
            }
        }


    }