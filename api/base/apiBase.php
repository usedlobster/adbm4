<?php

namespace api\base;


abstract class apiBase extends apiAuth
{

    use \api\auth\ApiAuthEngineTrait;

    private ?object $_payload = null;

    /**
     * @param  object|null  $_payload
     */
    public function __construct()
    {
        parent::__construct();
    }


    public function exec($parts)
    {
        try {
            $input = file_get_contents('php://input');
            if ( empty($input ))
                $this->_payload = null ;
            else {
                $j = json_decode( $input ) ;
                if ( is_object($j) && json_last_error() === JSON_ERROR_NONE)
                    $this->_payload = $j ;
                else
                    $this->_payload = null ;
            }

            return $this->run($this->_payload, $parts);
        } catch (\Throwable $ex) {
            return (object)['error' => 700 ] ;
        }

    }


    abstract public function run($payload, $parts) ;

}