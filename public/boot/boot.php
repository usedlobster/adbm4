<?php

    global $_app ;
    use Dotenv\Dotenv;
    session_start();
    if ( session_status() !== PHP_SESSION_ACTIVE )
        return;
    try
    {
        // very crude post-redirect-get.
        $req = $_SERVER[ 'REQUEST_URI' ];
        if ( !empty( $_POST ) )
        {
            $_SESSION[ '_app_post_' ] = serialize( $_POST );
            header( 'Location: ' . ( $req ) ?? '' );
            exit;
        }
        elseif ( isset( $_SESSION[ '_app_post_' ] ) )
        {
            $_POST = unserialize( $_SESSION[ '_app_post_' ] );
            unset( $_SESSION[ '_app_post_' ] );
        }
        else
            $_POST = [];

        if ( !isset( $_SESSION[ '_csrf' ] ) )
            $_SESSION[ '_csrf' ] = bin2hex( random_bytes( 20 ) );

        // setup autoloader
        require_once __DIR__ . '/../../vendor/autoload.php';
        Dotenv::createImmutable( dirname( __DIR__ . '/../../.env' ) )->safeLoad();
        define( '_DEV_MODE' , $_ENV[ 'DEV_MODE' ] ?? false );
        define( '_BUILD' , _DEV_MODE ? microtime( true ) : ( $_ENV[ '_BUILD' ] ?? 100 ) );
        if ( !isset($_ENV['APP_DOMAIN']))
            return ;

        if ( _DEV_MODE )
        {
            ini_set( 'log_errors' , 1 );
            ini_set( 'display_errors' , 1 );
            ini_set( 'display_startup_errors' , 1 );
            error_reporting( E_ALL & ~E_PARSE );;
            clearstatcache( true );
            opcache_reset();
        }

        // try to keep session ali
        $_app = new \app\AppMaster();
        if ( $_app )
        {
            $_check = $_SESSION[ '_check' ] ?? false;
            if ( !$_check || time() > $_check + 30 )
            {
                // do sanity api check every 30 seconds
                $apicheck =  \app\AppMaster::checkApi();
                if ( $apicheck !==  true )
                    throw new \Exception( 'Application initialization failed' );
                $_SESSION[ '_check' ] = time();
            }

            @ $_app->route( $req );
        }
        else
            throw new \Exception( 'Application initialization failed' );

    }
    catch ( \Throwable $ex )
    {
        __fatalexit( $ex->getMessage() );
    }
    finally
    {
        if ( _DEV_MODE )
            echo '<br /><a href="/auth/signout">Sign Out</a>';
    }

    function __fatalexit( $msg , $code = 403 ) : never
    {
        http_response_code( $code );
        echo '<pre>' , $msg , '</pre>';
        exit;
    }