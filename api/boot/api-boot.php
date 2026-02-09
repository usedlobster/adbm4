<?php

    $result = null;
    try
    {
        ob_start() ;
        $req = strtolower( $_SERVER[ 'REQUEST_URI' ] );
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
        if ( !in_array( $basecmd , [ 'login' , 'project' , 'table' ] ) )
            exit ;

        $class = '\\api\\base\\' . $version . '\\api' . ucfirst( $basecmd );
        require_once( __DIR__ . '/../../vendor/autoload.php' );
        \Dotenv\Dotenv::createImmutable( dirname( __DIR__ . '/../.env' ) )->safeLoad();
        if ( ( $_ENV[ 'X_TYPE' ] ?? '' ) !== 'api' )
            exit ;

        $result = new $class()->exec( $parts ) ;
        if ( !is_object( $result )) {
            if ( is_array( $result ))
                $result = (object)$result ;
            else if ( is_numeric( $result ))
                $result = (object)['error'=>(int)$result] ;
            else
                $result = (object)['error'=>701] ;
        }

    }
    catch ( \Throwable $ex )
    {
        //
        $result = (object)['error' => 700];
    }
    finally
    {
        ob_end_clean() ;
        header( 'Content-Type: application/json' );
        header( 'Cache-Control: no-cache, no-store, must-revalidate' );
        echo json_encode($result);
        exit ;
    }
