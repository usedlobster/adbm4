<?php

    namespace sys\wd;

    class GoogleMap {

        public static function GenStaticMap(  $gParams ) : array
        {

            $tmp = tmpfile() ;
            if ( $tmp === false && _DEV_MODE )
                return ['error'=>'Could not create temp file'] ;
            else if ( $tmp !== false ) try
            {
                $url = 'https://maps.googleapis.com/maps/api/staticmap?';
                $g = [] ;
                if ( isset( $gParams[ 'size' ] ) )
                    $g['size'] =  $gParams[ 'size' ] ;
                else
                {
                    $zx = $gParams[ 'zx' ] ?? 400 ;
                    $zy = $gParams[ 'zy' ] ?? 300 ;
                    $g['size'] = $zx . 'x' . $zy ;
                }

                if ( isset( $gParams[ 'center' ] ) )
                    $g['center'] = $gParams[ 'center' ] ;
                else {
                    $lat = $gParams[ 'lat' ] ?? $gParams[ 'n' ] ?? 51.47722 ;
                    $lng = $gParams[ 'lng' ] ?? $gParams[ 'e' ] ?? -0.00000 ;
                    $g['center'] = $lat . ',' . $lng ;
                }

                $g['scale'] = 2 ;
                $g['format'] = 'png' ;
                $g['zoom'] = $gParams[ 'zoom' ] ?? 10 ;
                $g['maptype'] = $gParams[ 'maptype' ] ?? 'roadmap' ;

                $g['key'] = $_ENV[ 'GOOGLE_MAPS_API_KEY' ] ?? '' ;
                $url  .= http_build_query( $g ) ;

                $ch = curl_init($url);
                if ($ch !== false )
                {
                    if ($tmp !== false)
                    {
                        curl_setopt($ch, CURLOPT_FILE, $tmp );
                        curl_setopt($ch, CURLOPT_HEADER, 0);
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                        curl_exec($ch);
                        curl_close($ch);
                        rewind( $tmp ) ;
                        $data = stream_get_contents( $tmp ) ?? false ;
                        if ( substr( $data , 0 , 8 ) === "\x89PNG\r\n\x1a\n")
                            return ['map'=>base64_encode( $data )] ;
                        else if ( _DEV_MODE )
                            return ['error'=>(string)substr( $data, 0  , 200 ) ] ;

                    }
                }
            }
            catch( \Exception $e )
            {
               return ['err'=>'Error generating map: ' . ( _DEV_MODE ? $e->getMessage() : '')] ;
            }
            finally {
                if ( $tmp )
                    fclose( $tmp ) ;
            }

            return ['error'=>'Map could not be generated' ] ;
        }



    }