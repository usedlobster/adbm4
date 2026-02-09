<?php

namespace app\wd\auth;

class AppLogin {

    use \app\wd\auth\AuthLoginViewTrait;
    use \app\wd\auth\AuthLoginApiTrait;


    private ?object $_user;
    private ?object $_info;
    public function __construct() {
        $this->_user = $_SESSION['_user'] ?? null ;
        $this->_info = null ;
    }

    public function haveLogin() : bool {

        if ( is_object($this->_user) &&
            (($this->_user->sid ?? -1) > 0) &&
            (($this->_user->pid ?? -1) > 0 &&
                !empty($this->_user->atkn)))
            return true;
        return false;
    }


    protected function setLogin( ?object $login )
    {
        $this->_user = ( $_SESSION['_user'] = $login ) ;
        $this->_info = ( $_SESSION['_info'] = null ) ;
    }



}