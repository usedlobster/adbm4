<?php

namespace api\endpoint;

use api\traits\apiTokenTrait;

abstract class ApiBase {


    protected static object | bool | null  $_payload     = false ;
    protected static object | bool | null  $_bearer      = false ;

    use apiTokenTrait ;


    abstract public function run( ?array $parts = null);

    protected function outputResult( $result ) {

        header( 'Cache-Control: no-cache, must-revalidate;max-age=0' );
        header('Content-Type: application/json;charset=utf-8');
        if ( is_object( $result ))
            echo json_encode( $result ) ;
        else if ( is_int( $result ))
            echo json_encode( (object)[ 'error'=>$result ] );
        else if ( is_string( $result ))
            echo json_encode( (object)[ 'str'=>$result ] );
        else
            echo 'null' ;

    }

    protected function getPayload() : ?object {
        if ( self::$_payload === false  )
        {
            $input = file_get_contents('php://input');
            if (!empty($input)) {
                @ $j = json_decode($input);
                if (is_object($j) && json_last_error() === JSON_ERROR_NONE)
                    return (self::$_payload = $j) ;
            }

        }

        return ( self::$_payload = null ) ;
    }


    protected function getBearer() : ?object
    {
        if ( self::$_bearer !== false )
            return self::$_bearer ;

        $auth = explode( ' '  , $_SERVER['HTTP_AUTHORIZATION'] ?? '' ) ;
        if ( count( $auth ) !==2 || $auth[0]  !== 'Bearer' || empty($auth[1]))
                return null ;

        $a = $this->decodeAccessToken( $auth[1] ) ;
        if ( !$a ) {
            header('HTTP/1.1 401 Unauthorized');
            return null;
        }



        return $a ;

    }

}