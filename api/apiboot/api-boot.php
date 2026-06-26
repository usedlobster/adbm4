<?php

use api\endpoint\login\v1\ApiLogin;
use api\endpoint\system\v1\ApiSystem;


function log_api(string $message) : void
{
    error_log($message."\n", 3, __DIR__.'/logs/api.log');
}

try {

    $path = $_SERVER['REQUEST_URI'] ?? '';
    $split = explode( '?' , $path , 2 ) ;
    $rp = explode( '/' , trim(strtolower( $split[0] ?? '' )),5) ;

    if (array_shift($rp) === '') {
        if (array_shift($rp) === 'api') {
            $ver = array_shift($rp) ?? '';
            $base = array_shift($rp) ?? '';

            require_once(__DIR__.'/../../vendor/autoload.php');

            $api = match ($ver.'@'.$base) {
                'v1@login'  => new ApiLogin(),
                'v1@system' => new ApiSystem() ,
                 default    => null
            };

            if ($api !== null) {

                $method = $_SERVER['REQUEST_METHOD'] ?? '';
                if ( $method === 'POST' || $method === 'OPTIONS' ) {
                    // CORS Headers for Stateless API
                    header('Access-Control-Allow-Origin: *'); // Safe since we don't use cookies/sessions
                    header('Access-Control-Allow-Methods: POST, OPTIONS');
                    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
                    if ($method === 'OPTIONS') {
                        // ok nothing else required
                        http_response_code(204);
                        exit;
                    }
                }
                else {
                    // invalid method
                    header('Allow: POST, OPTIONS');
                    http_response_code(405);
                    exit;
                }

                \Dotenv\Dotenv::createImmutable( dirname( __DIR__ . '/../../api/.env' ) )->safeLoad();
                if ( ( $_ENV[ 'X_TYPE' ] ?? false ) !== 'api' )
                    return;

                $api->run($rp, $split);
            }
            else
                http_response_code(404); // not found
            exit;
        }
    }

    http_response_code(400); // bad request
} catch ( \Throwable $ex ) {
    http_response_code(500); // internal server error
    log_api($ex->getMessage());
}

exit;   // implied anyway

