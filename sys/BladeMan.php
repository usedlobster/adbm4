<?php


    namespace sys;

    use eftec\bladeone\BladeOne;

    class BladeMan {

        private static array $_blades = [] ;

        public static  function getBlade( $index = 0 , $base = 'app/view' ) {


            if (( $b = self::$_blades[ $base . $index ] ?? false ) !== false )
                return $b ;

            $dir = realpath($_SERVER[ 'DOCUMENT_ROOT' ].'/../'.$base);
            if ( $dir ) {
                $b =  new BladeOne( $dir , '/tmp' , BladeOne::MODE_DEBUG) ;
                if ( $b )
                    $b->csrf_token = $_SESSION[ '_csrf' ] ?? ($_SESSION[ '_csrf' ] = base64(random_bytes(18)));
                $blade[ $base.$index ] = $b;

                return $b ?? false ;

            }

            return false ;
        }

        public static function checkCSRF() {
            return (( $_POST['_token'] ?? '' )  === $_SESSION[ '_csrf' ] ?? 'none'  ) ;
        }

    }


