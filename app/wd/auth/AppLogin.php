<?php

namespace app\wd\auth;

class AppLogin {


    private bool $_down = false    ;
    private int $_checkpoint       ;
    private ?object $_login = null ;

    use \app\wd\AppToApiTrait ;

    public function __construct() {

        $this->_down  = $_SESSION['_down'] ?? false ;
        $this->_checkpoint = $_SESSION['_checkpoint'] ?? 0 ;
        $this->_login = $_SESSION['_login'] ?? null ;
        if ( !($this->apiCheck()))
            throw new \RuntimeException( 'API is down' ) ;
    }



}