<?php

    namespace app\engine;

    class AppAssetStore
    {


        private static string|false $_site_dir;

        public function __construct( ) {

            self::$_site_dir = realpath(  $_SERVER['DOCUMENT_ROOT']  . '/../site/'   );


        }


        public function getAsset( $dir , $name ) : string | bool {
            $key = $dir . '/' . $name ;
            $path = self::$_site_dir . '/' .   $key . '.blade.php' ;
            if ( file_exists( $path ) )
                return file_get_contents( $path ) ;

            return $path ;
        }

        public function putAsset( $dir , $name , $content = '' ) : bool
        {
            $key = $dir . '/' . $name ;
            $path = ($dpath = self::$_site_dir . '/' .   $key ) . '.blade.php' ;


            return $path ;
        }



        /*
        public function getAsset($name , $type , $skip_db = false) : array|bool
        {

            try
            {
                $akey = $name.':'.$type;

                $red = \sys\Util::getRedis();
                if ( $red )
                {
                    $content = $red->get($akey);
                    if ( $content !== false )
                        return array_merge(unserialize($content) , ['from' => 'redis']);
                }


                // let's try to get from the database
                if ( !$skip_db )
                {
                    $row = \sys\db\SQL::RowN(" SELECT data,lu,ttl FROM <DB>.sys_assets WHERE id = ? AND t =  ? " , [$name , $type]);

                    if ( ($row[ 'lu' ] ?? '') != date('Y-m-d') )
                        \sys\db\SQL::Exec(" UPDATE <DB>.sys_assets SET lu = CURRENT_DATE WHERE id = ? AND t = ?  " , [$name , $type]);

                    //if ( $red )
                    //    $red->setex($akey , $row[ 'ttl' ] ?? 90 , $row[ 'data' ] ?? '');

                    return array_merge(unserialize($row[ 'data' ] ?? '') , ['from' => 'db']);
                }
            }
            catch ( \Throwable $ex )
            {
                error_log(print_r($ex , true));
            }
            return false;
        }

        public function setAsset($name , $type , array $data , $ttl = -1 , $skip_db = false) : bool
        {
            try
            {
                $akey = $name.':'.$type;
                $s = serialize($data);
                if ( $skip_db || (\sys\db\SQL::Exec(" REPLACE INTO <DB>.sys_assets ( id , t , data , ttl , cr , lu  ) VALUES ( ? , ? , ? , ? , NOW() , NOW()  ) " , [$name , $type , $s , $ttl]) === true) )
                {
                    $red = \sys\Util::getRedis();
                    if ( $red )
                        $red->setex($akey , $ttl > 30 ? $ttl : 30 , $s);
                    return true;
                }
            }
            catch ( \Throwable $ex )
            {
                error_log(print_r($ex , true));
            }
            return false;
        }
        */



    }


