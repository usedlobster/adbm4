<?php

    namespace sys\blade;

    use eftec\bladeone\BladeOne;

    class BladeMan {
        public static  function getBlade(  $base = 'page'  ) {

            $dir = realpath($_SERVER[ 'DOCUMENT_ROOT' ].'/../'.$base);
            $b =  new BladeOne( $dir , '/tmp' , BladeOne::MODE_DEBUG) ;
            $b->share( 'ui' , new \sys\blade\UI() );
            if ( !isset($_SESSION['_csrf']))
                $_SESSION['_csrf'] = base64( random_bytes(18)) ;
            $b->csrf_token = $_SESSION['_csrf'] ;
            return $b ;

        }

        public static function checkCSRF() {
            return (( $_POST['_token'] ?? '' )  === $_SESSION[ '_csrf' ] ?? 'none'  ) ;
        }




    }