<?php

    namespace sys;

    class Util
    {
        private static mixed $responseCode;
        private static $curlHandle = null;


        public static function curlSend(
                string $method ,
                string $url ,
                $data = null ,
                ?string $bearer = null ,
                ?array $extraHeaders = null
        ) : ?string {

            if ( self::$curlHandle === null )
                self::$curlHandle = curl_init();
            $curlHandle = self::$curlHandle;


            try
            {
                curl_reset( $curlHandle );
                self::$responseCode = 0;
                if ( $curlHandle === false )
                    return null;

                // Handle Data Encoding
                $payload = "";
                if ( $method === "GET" )
                {
                    if ( $data )
                    {
                        $query = is_array( $data ) ? http_build_query( $data ) : $data;
                        $url   .= ( str_contains( $url , '?' ) ? '&' : '?' ) . $query;
                    }
                }
                else
                {
                    $payload = is_array( $data ) ? json_encode( $data ) : ( $data ? : '' );
                }

                switch ( $method )
                {
                    case "POST":
                        curl_setopt( $curlHandle , CURLOPT_POST , true );
                        curl_setopt( $curlHandle , CURLOPT_POSTFIELDS , $payload );
                        break;
                    case "PUT":
                        curl_setopt( $curlHandle , CURLOPT_CUSTOMREQUEST , "PUT" );
                        curl_setopt( $curlHandle , CURLOPT_POSTFIELDS , $payload );
                        break;
                    case "GET":
                        // URL already updated above
                        break;
                    default:
                        throw new \RuntimeException( 'invalid method' );
                }

                // Set the URL

                if ( _DEV_MODE && isset( $_COOKIE[ 'XDEBUG_SESSION' ] ) )
                {
                    $separator = strpos( $url , '?' ) !== false ? '&' : '?';
                    $url       .= $separator . 'XDEBUG_SESSION_START=' . $_COOKIE[ 'XDEBUG_SESSION' ];
                }


                curl_setopt( $curlHandle , CURLOPT_URL , $url );
                curl_setopt( $curlHandle , CURLOPT_RETURNTRANSFER , true );
                curl_setopt( $curlHandle , CURLOPT_TIMEOUT , _DEV_MODE ? 600 : 10 );

                $headers = [ 'Content-Type: application/json;charset=utf-8' ];

                if ( is_array( $extraHeaders ) )
                    $headers = array_merge( $headers , $extraHeaders );

                if ( $bearer )
                    array_push( $headers , 'Authorization: Bearer ' . $bearer );

                curl_setopt( $curlHandle , CURLOPT_HTTPHEADER , $headers );

                // turn off ssl checks ( for development )
                if ( _DEV_MODE )
                {
                    curl_setopt( $curlHandle , CURLOPT_SSL_VERIFYHOST , false );
                    curl_setopt( $curlHandle , CURLOPT_SSL_VERIFYPEER , false );
                }
                curl_setopt( $curlHandle , CURLOPT_IPRESOLVE , CURL_IPRESOLVE_V4 );

                if ( ( $raw = curl_exec( $curlHandle ) ) === false )
                    return null;

                self::$responseCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
                if ( self::$responseCode >= 200 && self::$responseCode < 300)
                    return $raw;
            }
            catch ( \Exception $ex )
            {
                error_log( "CURL Error: " . $ex->getMessage() );
            }

            return null;
        }



        public static function constantRunTime( callable $func , $args , float $execTime = .5 )
        {
            $start = microtime(true);
            $result = $func(...$args);
            $end = microtime(true);
            $time = $execTime - ( $end - $start) ;
            if ( $time > 0.05)
                uSleep( floor( $time * 1e6   )) ;
            else
                error_log( 'Over Time' . print_r($func,true)) ;

            return $result ;
        }


    }
