<?php


    namespace sys;

    use eftec\bladeone\BladeOne;

    class BladeMan {

        private static array $_blades = [] ;
        private static int $_stack= 0 ;

        public static  function getBlade(  $base = 'app/view'  ) {
            /*

            self::$_stack = ( self::$_stack + 1 ) & 3 ;
            $index = self::$_stack ;

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
            */
            $dir = realpath($_SERVER[ 'DOCUMENT_ROOT' ].'/../'.$base);
            $b =  new BladeOne( $dir , '/tmp' , BladeOne::MODE_DEBUG) ;
            if ( !isset($_SESSION['_csrf']))
                $_SESSION['_csrf'] = base64( random_bytes(18)) ;
            $b->csrf_token = $_SESSION['_csrf'] ;
            return $b ;

        }

        public static function checkCSRF() {
            return (( $_POST['_token'] ?? '' )  === $_SESSION[ '_csrf' ] ?? 'none'  ) ;
        }

    }


