<?php

    namespace sys;

    class Audit {

        public static function getFail( $idkey , $ft , $t ) : int {
            return 0 ;
        }

        public static function setFail( $idkey , $ft , $t ) : bool {
            return true ;
        }

        public static function rateLimitOK( string $key , array $check ) : bool
        {
            return true ;
        }


    }