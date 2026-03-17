<?php

    $result = null;
    try
    {

        ob_start() ;
        $req = strtolower( $_SERVER[ 'REQUEST_URI' ] );
        error_log( "API Boot: Request URI: $req" );
        if ( !str_starts_with( $req , '/api/v' ) ) {
            http_response_code( 404 );
            exit;
        }
        // CORS Headers for Stateless API
        header( 'Access-Control-Allow-Origin: *' ); // Safe since we don't use cookies/sessions
        header( 'Access-Control-Allow-Methods: POST, OPTIONS' );
        header( 'Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With' );
        if ( $_SERVER[ 'REQUEST_METHOD' ] === 'OPTIONS' ) {
            http_response_code( 204 );
            exit;
        }
        elseif ( $_SERVER[ 'REQUEST_METHOD' ] !== 'POST' )
        {
            // only post requests currently needed
            http_response_code( 405 );
            exit;
        }

        $strip   = explode( '?' , $req , 2 );
        $parts   = explode( '/' , strtolower( substr( $strip[ 0 ] , 5 ) ) , 5 );
        $version = array_shift( $parts );
        if ( !in_array( $version , [ 'v1' ] ) )
            exit;

        $basecmd = array_shift( $parts );
        if ( !in_array( $basecmd , [ 'login' , 'project' , 'table'  , "form" ] ) )
            exit ;

        $class = '\\api\\base\\' . $version . '\\api' . ucfirst( $basecmd );

        require_once( __DIR__ . '/../../vendor/autoload.php' );
        \Dotenv\Dotenv::createImmutable( dirname( __DIR__ . '/../.env' ) )->safeLoad();
        if ( ( $_ENV[ 'X_TYPE' ] ?? '' ) !== 'api' )
            exit ;

        opcache_reset() ;

        if ( !method_exists( $class , 'exec' ))
            throw new \Exception( 'Class not found: ' . $class ) ;

        $result = ( new $class() )->exec( $parts );
        if ( !is_object($result)) {
            if (is_numeric($result))
                $result = (object)['error' => $result];
            else if ( is_array( $result ))
                $result = (object)$result ;
            else
                $result = (object)['v' => $result];
        }


    }
    catch ( \Throwable $ex )
    {
        //
        http_response_code( 500 );
        $result = (object)['error' => 700 , 'msg'=>(string)$ex->getMessage()];
        error_log( $ex ) ;
    }
    finally
    {
        ob_end_clean() ;
        header( 'Content-Type: application/json' );
        header( 'Cache-Control: no-cache, no-store, must-revalidate' );
        $z = json_encode( $result ) ;
        if ( json_last_error() !== JSON_ERROR_NONE)
            echo '{error:705}' ;
        else
            echo $z ;
        exit ;
    }
