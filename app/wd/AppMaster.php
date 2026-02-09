<?php

namespace app\wd;

use app\wd;

class AppMaster extends wd\AppMasterBase {


    static private $_instance = null ;

    public function __construct( $req ) {

        parent::__construct( $req );

        self::$_instance = $this ;
    }

    public static function app() : ?AppMaster {
        return self::$_instance ?? null ;
    }



}