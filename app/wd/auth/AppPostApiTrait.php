<?php

namespace app\wd\auth;

trait AppPostApiTrait {



    public function apiPost0(string $url, array | string $data  ): ?object
    {
        try
        {
            $url = _API_DOMAIN . $url;
            $res = \sys\Util::curlSend( 'POST' , $url , $data , null , null );
            return $res ? (object)json_decode( $res ) : null ;
        }
        catch( \Throwable $ex )
        {
            error_log( $ex ) ;
        }

        return null ;
    }

    private function doRefresh() : bool
    {
        // try to refresh
        return false ;
    }

    public function apiPostX(string $url, array | string $data , bool $retry = true   ): ?object
    {

        try
        {
            $url = _API_DOMAIN . $url;
            $res = \sys\Util::curlSend( 'POST' , $url , $data ,
                $this->_user?->atkn ?? null , null ) ;

            @ $obj = (object)json_decode( $res ) ?? null ;
            if ( json_last_error() !== JSON_ERROR_NONE)
                $obj = null ;

            if ( isset($obj->expired ) ) {
                // do we attempt another try
                if ( $retry ) {
                    if ( self::doRefresh())
                        return self::apiPostX( $url , $data , false ) ;
                }
            }

            return $obj ;
        }
        catch( \Throwable $ex )
        {
            error_log( $ex ) ;
        }

        return null ;
    }





}