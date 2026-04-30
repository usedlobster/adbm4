<?php

namespace app\wd;

class AppMaster extends \app\wd\auth\AppLogin {


    protected   static string  $_req;
    protected   static array   $_split;
    protected   static array   $_parts;
    protected   static string  $_base;


    public function __construct()
    {
        // break down request uri
        self::$_req   = $_SERVER['REQUEST_URI'] ?? '';
        self::$_split = explode('?', self::$_req, 2);
        self::$_parts = explode('/', strtolower(trim(self::$_split[0])));
        self::$_base  = array_shift(self::$_parts) ?? '';
        parent::__construct();
    }








}