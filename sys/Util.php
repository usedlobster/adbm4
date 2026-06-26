<?php

    namespace sys;

    class Util
    {
        private static mixed $responseCode;
        private static $curlHandle = null;
        public  static $_info = null ;


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
                if ( !_SSL_MODE )
                {
                    curl_setopt( $curlHandle , CURLOPT_SSL_VERIFYHOST , false );
                    curl_setopt( $curlHandle , CURLOPT_SSL_VERIFYPEER , false );
                }
                curl_setopt( $curlHandle , CURLOPT_IPRESOLVE , CURL_IPRESOLVE_V4 );

                if ( ( $raw = curl_exec( $curlHandle ) ) === false )
                    return null;

                // self::$_info = curl_getinfo($curlHandle);
                // self::$responseCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
                // if ( self::$responseCode >= 200 && self::$responseCode < 300)
                    return $raw;
            }
            catch ( \Exception $ex )
            {
                error_log( "CURL Error: " . $ex->getMessage() );
            }

            return null;
        }

        /**
         * Executes a callable function while ensuring the operation takes approximately the specified execution time.
         * If the function completes faster than the desired execution time, the method introduces an appropriate delay.
         * If the function exceeds the target time, it logs an error and continues with a small random delay.
         *
         * @param  callable  $func      The function to be executed.
         * @param  mixed     $args      An array of arguments to be passed to the callable.
         * @param  float     $execTime  The target execution time in seconds (default is 0.5 seconds).
         * @param  string    $name      An optional identifier for the operation, used in the error log if an over-time occurs.
         *
         * @return mixed Returns the result of the executed callable function.
         */
        public static function constantRunTime( callable $func , $args , float $execTime = .5 , $name = '' )
        {

            $start = microtime(true);
            $result = $func(...$args);
            $end = microtime(true);
            $time = $execTime - ( $end - $start) ;
            if ( $time > 0)
                usleep( (int) ( $time * 1000000 ) );

            return $result ;
        }


        public static function recentTime( $t , int $age , int $skew = 2  )
        {
            if ( !is_int($t) || $age < 1 || $skew < 0 )
                return false ;

            $now = time();
            return ( $t >= ( $now - $age - $skew ) && $t <= $now + $skew ) ;
        }

    }
