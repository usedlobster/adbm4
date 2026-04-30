<?php

use app\adbm\AdbmApp;

session_start();

    if ( session_status() !== PHP_SESSION_ACTIVE ) {
        http_response_code( 500 )  ;
        exit ;
    }

    $_t0 = microtime( true );
    $req = $_SERVER[ 'REQUEST_URI' ] ?? false;
    // very crude post-redirect-get
    if ( !empty( $_POST ) ) {
        $_SESSION[ '_app_post_' ] = serialize( $_POST );
        header( 'Location: ' . ( $req ) ?? '' );
        session_write_close();
        exit;
    }
    elseif ( isset( $_SESSION[ '_app_post_' ] ) )
    {
        $_POST = unserialize( $_SESSION[ '_app_post_' ] );
        unset( $_SESSION[ '_app_post_' ] );
    }

    // load autoloader
    require_once( __DIR__ . '/../../vendor/autoload.php' );
    // load environment
    \Dotenv\Dotenv::createImmutable( dirname( __DIR__ . '/../../app/.env' ) )->safeLoad();
    if ( ( $_ENV[ 'X_TYPE' ] ?? false ) !== 'app' )
        return;


    // set some constants from _ENV
    define( '_DEV_MODE' , $_ENV['_DEV_MODE'] ?? true );
    define( '_BUILD' , $_ENV['APP_BUILD'] ?? time() );
    define( '_API_DOMAIN' , $_ENV['_API_DOMAIN'] ?? 'https://api.usedlobster.test/api/' );


    if ( _DEV_MODE )
    {
        error_reporting( E_ALL );
        ini_set( 'display_errors' , 1 );
    }
    else
        error_reporting( 0 );

    register_shutdown_function( function ()
    {
        if (_DEV_MODE) {
            global $_t0 , $_api_time ;
            session_write_close();
            $dt = round((microtime(true) - $_t0) * 1e3, 3);
            echo '<hr />', $dt, 'ms', '<hr />';
            echo '<a href="/auth/signout">Sign Out</a>';
            echo '<hr />' , ($_api_time * 1e3 ) ?? 0 , 'ms';
            echo '<hr /><pre>' , print_r( $_SESSION['_login'] ?? null , true ) , '<hr /></pre>';
        }
        // not strictly necessary
        session_write_close();
        $_app = null;
        unset( $_app );

    });

    try {
        ($_app = new AdbmApp($req))?->start();
    }
    catch ( \RuntimeException $ex )
    {
        echo $ex->getMessage();
        exit ;
    }
    exit;