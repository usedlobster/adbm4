<?php

use app\adbm\AdbmApp;

global $_t0, $_skip;
session_start();

if (session_status() !== PHP_SESSION_ACTIVE) {
    http_response_code(500);
    exit;
}

$_t0 = microtime(true);
$req = $_SERVER['REQUEST_URI'] ?? '';

// very crude post-redirect-get
if (!empty($_POST)) {
    $_SESSION['_app_post_'] = serialize($_POST);
    header('Location: ' . ($req) ?? '');
    session_write_close();
    exit;
}
elseif (isset($_SESSION['_app_post_'])) {
    $_POST = unserialize($_SESSION['_app_post_']);
    unset($_SESSION['_app_post_']);
}

// load autoloader
require_once(__DIR__ . '/../../vendor/autoload.php');
// load .env
\Dotenv\Dotenv::createImmutable(dirname(__DIR__ . '/../../app/.env'))->safeLoad();
if (($_ENV['X_TYPE'] ?? false) !== 'app')
    return;

// set some constants from _ENV
define('_DEV_MODE', ($_ENV['_DEV_MODE'] ?? true));
define('_SSL_MODE', false);
define('_BUILD', $_ENV['APP_BUILD'] ?? (_DEV_MODE ? (intdiv(time(), 15)) : 0));
define('_API_DOMAIN', $_ENV['_API_DOMAIN'] ?? 'https://api.usedlobster.test/api/');

if (_DEV_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
else
    error_reporting(0);

register_shutdown_function(function ()
{
    echo '<br><a href="/auth/signout">Sign Out</a>';
});

global $_app;
try {
    ($_app = new AdbmApp())?->start();
}
catch (\Throwable $ex) {
    $m = ($ex instanceof RuntimeException ) ? $ex->getMessage() : 'Unclassified Error' ;
    echo /** @lang text */
    <<<HTML
<!DOCTYPE html>
<html lang="en" class="h-full" data-theme="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADBM Fatal Error</title>
    </head>
    <body>
  <div style="margin:auto auto;width:1000px">
   <div style="text-align:center">
    <img width="80vw" height="80vh" src="/img/logo.svg" alt="ADBM Logo"/>
        <h1 style="color:#f40">Fatal Error</h1>
        <h2 style="color:#f00">{$m}</h2>
    </div>
  </div>
  </body>
   
HTML;
}

exit;