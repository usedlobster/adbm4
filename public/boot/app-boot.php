<?php

    global $_t0 ;
    $_t0 = microtime( true );

    session_start();
    if ( session_status() !== PHP_SESSION_ACTIVE )
        return;

    $req = $_SERVER[ 'REQUEST_URI' ] ?? false;

    if ( !empty( $_POST ) )
    {
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

    register_shutdown_function( function ()
    {
        if ( _DEV_MODE )
        {
            global $_t0;
            session_write_close();
            $dt = round( ( microtime( true ) - $_t0 ) * 1e3 , 3 );
            echo '<hr />' , $dt , '<hr />';
            echo '<a href="/auth/signout">Sign Out</a>';
            session_write_close();
        }
        if ( http_response_code() === 404 )
            echo '<hr /><b>404 Not Found</b><hr />';
        exit;
    } );

    define( '_DEV_MODE' , true );
    define( '_BUILD' , time() );
    define( '_API_DOMAIN' , 'https://api.usedlobster.test/api/' );

    if ( _DEV_MODE )
    {
        error_reporting( E_ALL );
        ini_set( 'display_errors' , 1 );
    }

    require_once( __DIR__ . '/../../vendor/autoload.php' );
    \Dotenv\Dotenv::createImmutable( dirname( __DIR__ . '/../../app/.env' ) )->safeLoad();
    if ( ( $_ENV[ 'X_TYPE' ] ?? false ) !== 'app' )
        return;
    //

    ( $_app = new \app\AdbmApp($req) )?->route();