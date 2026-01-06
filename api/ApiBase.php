<?php

    namespace api;

    use api\auth\AuthBase;

    abstract class ApiBase
    {
        protected ?array $_parts = null;
        protected ?object $_payload = null;

        public function __construct( $parts )
        {
            $this->_parts   = $parts;
            $this->_payload = null;
        }

        public function init() : bool
        {
            $this->_payload = json_decode( file_get_contents( 'php://input' , length:4096 ) );
            return true;
        }

        public function exec() : object | null
        {
            if ( $this->init() )
            {
                $result = $this->api( $this->_payload );
                if ( $result)
                {
                    if ( is_array( $result ) )
                        return (object)$result;
                    return $result;
                }
            }
            return null ;
        }


        abstract protected function api( ?object $payload ) : object | array | null;


    }