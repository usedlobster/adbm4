<?php

namespace app;

class AdbmApp extends wd\AppMaster {


    private static $_instance = null ;
    public function __construct( $req ) {
        parent::__construct( $req );
        self::$_instance = $this ;

    }

    public static function app() : ?AdbmApp {
        return self::$_instance ?? null ;
    }

    public function route(   ) {

        try {
            if (empty(self::$_base))
                $this->viewModel('',['welcome']);
            elseif (self::$_base === 'auth')
                $this->authActionPage(self::$_parts[0] ?? false);
            elseif (!$this->haveLogin())
                $this->authLoginPage();
            else
                $this->viewModel( self::$_base , self::$_parts);
        } catch (\Throwable $ex) {
            echo '<pre>!', print_r($ex,true), '</pre>';
            echo '<hr /><pre>';
            debug_print_backtrace()  ;
            echo '</pre>';
        }
    }

}