<?php

namespace app\wd;

trait AppToApiTrait {


    // simple post request to api [ no bearer token ]
    public function apiPost0(string $url, array | string $data  ): ?object
    {

        try {
            $url = _API_DOMAIN . $url;
            $res = \sys\Util::curlSend('POST', $url, $data, null, null);
            if ($res && is_string($res)) {
                $obj = @ json_decode($res) ?? null;
                if ( is_object( $obj ) && json_last_error() === JSON_ERROR_NONE )
                    return $obj;
            }
        }
        catch( \Throwable $ex )
        {
            error_log( $ex ) ;
        }

        $this->_checkpoint = 0 ;
        return null ;

    }

    // as above with refresh
    public function apiPostA( string $url , array | string $data , bool $retry = true ) : ?object
    {
        try {
            $url = _API_DOMAIN . $url;
            $res = \sys\Util::curlSend('POST', $url, $data, $this?->_login?->atkn ?? null , null);
            if ($res && is_string($res)) {
                $obj = @ json_decode($res) ?? null;
                if ( is_object( $obj ) && json_last_error() === JSON_ERROR_NONE )  {
                    if ( $obj->expired ?? false ) {
                        if ( $retry ) {
                            if ( $this->doRefresh( ))
                                return $this->apiPostA($url, $data, false);
                        }
                    }
                    return $obj ;
                }
            }
        }
        catch( \Throwable $ex )
        {
            error_log( $ex ) ;
        }


        $this->_checkpoint = 0 ;
        return null ;
    }

    protected function apiCheck() {

        $_t0 = time() ;
        // after 15 / 300 seconds
        if ( (( $_t0 - ( $this->_checkpoint ?? 0) ) > ( $this->_down ? 15 : 30 )))
        {
            // last check point
            $_SESSION[ '_checkpoint' ] = $this->_checkpoint = $_t0;
            // try pinging the api
            $result = $this->apiPost0('v1/system/ping' , []  );
            // only accept 'pong' as response
            if ( is_object( $result ) && (( $result->v ?? '' ) === 'pong' )) {
                $this->_down = $_SESSION['_down'] = false;
                return true;
            }

            $this->_down = $_SESSION['_down'] = true ;
            return false ;
        }


        return !($this->_down ?? false) ;

    }



}