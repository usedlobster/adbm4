<?php
    session_start();
    try
    {
        $req = $_SERVER[ 'REQUEST_URI' ] ;
        if ( !empty($_POST) ) {
            $_SESSION[ '_app_post_' ] = $_POST;
            header('Location: '. ($req) ?? '') ;
            exit;
        }
        else if ( isset($_SESSION[ '_app_post_' ]) )
        {
            $_POST = $_SESSION[ '_app_post_' ];
            unset($_SESSION[ '_app_post_' ]);
        }
        else
            $_POST = [];

        $t0 = microtime( true ) ;

        require_once __DIR__ . '/../../vendor/autoload.php' ;
        Dotenv\Dotenv::createImmutable(__DIR__ . '/../../' )->safeLoad();

        if ( !isset($_SESSION['_start'] ))
        {
            if ( !isset($_SESSION[ '_csrf' ]) )
                $_SESSION[ '_csrf' ] = base64_encode(random_bytes(18));
            $_SESSION[ '_start' ] = $t0 ;
        }
        else if (( $t0 - ( $_SESSION['_check']  ?? 0 )) > 30 )
            $_SESSION[ '_check' ] = $t0;

        // get dev mode , default is false
        define( '_DEV_MODE' , $_ENV[ 'DEV_MODE' ] ?? false  ) ;


        if ( _DEV_MODE ) {
                ini_set('log_errors' , 1);
                ini_set('display_errors' , 1);
                ini_set('display_startup_errors' , 1);
                error_reporting(E_ALL & ~E_PARSE);;
                clearstatcache(true);
                opcache_reset();
                define(  '_BUILD' ,microtime(true)   ) ;
        }
        else
            define(  '_BUILD' , $_ENV['BUILD'] ?? 1000  ) ;


        ($_app = new \app\AppMaster())->route() ;

    }
    catch ( \Throwable $ex )
    {
        __fatalexit( $ex->getMessage() ) ;
    }
    finally
    {
        echo '<hr />' , round((microtime(true) - $t0 ) * 1e3 , 3), ' ms' ;
        echo '<a href="/auth/signout">Sign Out</a>' ;
    }


    function __fatalexit( $msg )
    {
        echo '<pre>' , $msg , '</pre>' ;
        echo '<pre>' , nl2br( print_r( debug_backtrace() , true ) ) , '</pre>' ;
        exit ;
    }