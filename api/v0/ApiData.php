<?php

    namespace api\v0;

    use api\ApiBase;

    class ApiData extends ApiBase
    {
        protected function api( ?object $payload ) : object | array | null
        {
            // TODO: Implement api() method.
            $dataset = $payload->dataset ?? '' ;
            if ( $dataset )
            {
                $n = '\api\data\Data' . ucfirst( $dataset ) ;
                if ( class_exists( $n ) )
                    return new $n()->run( $payload , $this->_parts ) ;
            }


            return null ;
        }
    }