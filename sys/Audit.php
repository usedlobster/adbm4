<?php

    namespace sys;

    use sys\db\SQL;


    class Audit {
        public static function getFail($idkey , $ft , $iv = '1 minute')
        {
            return SQL::Get0(" select count(*) from <DB_FAIL> where ft = ? and idkey = ? and ( t  > now() - interval $iv )  " , [$ft , md5($idkey) ])  ;
        }

        public static function setFail($idkey , $ft = 0) : bool
        {
            return SQL::Exec("insert into <DB>.sys_fail ( idkey , ft , t ) values( md5(?) , ? , now()  )" , [$idkey , $ft]);
        }


        public static function rateLimit( string $key  , array $check )
        {
            try
            {
                if ( !empty($check) && is_array($check) )
                {
                    $ft = $check[ 0 ] ?? 0;
                    foreach ( $check[ 1 ] as $chk )
                    {
                        $failCount = \sys\Audit::getFail($key , $ft , $chk[ 0 ] ?? '5 minute');
                        if ( $failCount > ( $chk[ 1 ] ?? 1000 ) )
                            return false;
                    }
                    \sys\Audit::setFail( $key , $ft );
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