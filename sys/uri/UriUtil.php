<?php

    namespace sys\uri;

    class UriUtil {



        private static mixed $responseCode  ;

        public static function curlSend(
                string $method,
                string $url,
                $data = null,
                ?string $bearer = null,
                ?array $extraHeaders = null): ?string
        {
            $curlHandle = curl_init();
            try {
                self::$responseCode = 0;
                if ($curlHandle === false) return null;

                // Handle Data Encoding
                $payload = "";
                if ($method === "GET") {
                    if ($data) {
                        $query = is_array($data) ? http_build_query($data) : $data;
                        $url .= (str_contains($url, '?') ? '&' : '?') . $query;
                    }
                } else {
                    $payload = is_array($data) ? json_encode($data) : ($data ?: '');
                }

                switch ($method) {
                    case "POST":
                        curl_setopt($curlHandle, CURLOPT_POST, true);
                        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $payload);
                        break;
                    case "PUT":
                        curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, "PUT");
                        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $payload);
                        break;
                    case "GET":
                        // URL already updated above
                        break;
                    default:
                        throw new \RuntimeException('invalid method');
                }

                // Set the URL
                if ( _DEV_MODE ) {
                    $separator = strpos( $url , '?') !== false ? '&' : '?';
                    $url .= $separator . 'XDEBUG_SESSION_START=PHPSTORM';
                }

                curl_setopt( $curlHandle , CURLOPT_URL , $url );
                curl_setopt( $curlHandle , CURLOPT_RETURNTRANSFER , true );
                curl_setopt( $curlHandle , CURLOPT_TIMEOUT , 30 );

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

                if (($raw = curl_exec($curlHandle)) === false)
                    return null;

                self::$responseCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
                return $raw;
            } catch (\Exception $ex) {
                error_log("CURL Error: " . $ex->getMessage());
                return null;
            } finally {
                if (is_resource($curlHandle) || $curlHandle instanceof \CurlHandle) {
                    curl_close($curlHandle);
                }
            }
        }



        public static function navigateTo(string $url) : never
        {
            // make sure we remove previous post-data.
            if ( isset($_SESSION[ '_app_post' ]) )
                unset($_SESSION[ '_app_post' ]);
            if ( !headers_sent() )
                header('Location: '.$url);
            else
            {
                echo '<script>window.location=\''.$url.'\';</script>';
                echo '<noscript><a href="'.$url.'">click To continue</a></noscript>';
            }
            exit;
        }

    }