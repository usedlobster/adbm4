<?php
    // 1. CORS Headers for Stateless API
    header('Access-Control-Allow-Origin: *'); // Safe since we don't use cookies/sessions
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    require_once( __DIR__.'/../../vendor/autoload.php') ;
    header('Content-Type: application/json');
    ob_start();
    try
    {
        $result = false;
        $req    = strtolower($_SERVER[ 'REQUEST_URI' ]);
        if ( !str_starts_with($req , '/api/v') )
        {
            http_response_code( 404 );
            exit ;
        }
        $strip   = explode( '?' , $req , 2 );
        $parts   = explode('/' , strtolower(substr($strip[0] , 5)));
        $version = array_shift($parts);
        $basecmd = array_shift($parts);
        $name    = '\\api\\'.$version.'\\Api'.ucwords($basecmd , '_');

        if ( class_exists($name , true))
        {
            \Dotenv\Dotenv::createImmutable(dirname(__DIR__.'/../.env'))->safeLoad();
            if ( $_ENV['X_TYPE'] !== 'api' )
                exit ;
            $result = new $name($parts)->exec($parts);
        }
        else
            $result = (object)['error' => 'not found'];

        echo json_encode($result);
    }
    catch ( \Throwable $ex )
    {
        ob_end_clean();
        echo json_encode(['error' => $ex->getMessage()]);
    }
    finally
    {
        ob_end_flush();
    }
