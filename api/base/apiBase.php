<?php

    namespace api\base;

    abstract class apiBase
    {


        private ?object $_payload = null ;


        protected const RATE_LIMIT_USER_LOGIN = [ 1 , [ [ '2 minute' , 5 ] , [ '1 hour' , 20 ] ] ];
        protected const RATE_LIMIT_CHKCODE    = [ 2 , [ [ '1 minute' , 10 ] , [ '1 hour' , 50 ] ] ];
        protected const RATE_LIMIT_AUTHID     = [ 3 , [ [ '1 minute' , 10 ]]];




        public function exec( $parts )
        {
            try
            {
                $input = file_get_contents('php://input');
                $this->_payload = $input ? json_decode($input) : null;
                return $this->run( $this->_payload , $parts ) ;
            }
            catch ( \Throwable $ex )
            {
                return (object)[ 'error' => 700 ];
            }

            return null;
        }



        abstract public function run( $payload , $parts );

    }