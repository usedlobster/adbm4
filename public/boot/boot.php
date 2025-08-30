<?php

    require_once "../../vendor/autoload.php";
    const _DEV_MODE = true;

    if ( session_status() !== PHP_SESSION_ACTIVE )
        session_start();

    try
    {
        // development mode - testing
        ini_set('log_errors' , 1);
        ini_set('display_errors' , 1);
        ini_set('display_startup_errors' , 1);
        error_reporting(E_ALL & ~E_PARSE);;
        clearstatcache(true);
        opcache_reset();

        // build version
        define('_BUILD' , microtime(true));

        // get full request uri
        $rawreq = $_SERVER[ 'REQUEST_URI' ] ?? '';
        // sanitize request
        $req = htmlspecialchars(filter_var($rawreq , FILTER_SANITIZE_URL) , ENT_QUOTES , 'UTF-8');
        // create app
        $app = new \app\AppMaster();
        // do post-save-redirect-get
        $app->postRedirect($rawreq);
        // route request
        $app->route($req);
    }
    catch ( \Throwable $ex )
    {

    }
    finally
    {
        echo '<hr>done';
    }

