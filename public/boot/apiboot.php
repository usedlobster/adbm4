<?php
session_start();
try
{
    $req = filter_var($_SERVER[ 'REQUEST_URI' ] , FILTER_SANITIZE_URL);
    $parts = explode( '/' , strtolower( trim($req) ));
    if ( empty($parts[ 0 ]) && $parts[ 1 ] === 'api' )
    {
        $payload = json_decode(file_get_contents('php://input'), true);
        if ( $parts[2] === 'v0'  )
        {
            $file = __DIR__ . '/../../api/v0/' . $parts[3] . '.php';
            //if ( file_exists($file) )
            //{
            @ require_once($file);
            exit;
        }
        else if ( $parts[2] === 'v1' && !empty($parts[3] ))
        {
            require_once __DIR__ . '/../../vendor/autoload.php' ;
            Dotenv\Dotenv::createImmutable(__DIR__ . '/../../' )->safeLoad();

            // v1 calls have a class, for each root part of a url path
            $base = ucwords( $parts[3], '-_ ' ) ;
            $class = str_replace( ['_','-',' '] , '' , '\\api\\v1\\' . $base . 'Api') ;
            if ( class_exists($class) && method_exists($class , 'run'))
            {

                (new $class())->run(array_slice( $parts , 4 ), $payload );
                exit;
            }
            else
                throw new \RuntimeException('missing api class method ' . $class  ) ;
        }
    }

    throw new \RuntimeException('unknown api call ');

}
catch (\RunTimeException $e)
{
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => $e->getMessage()]);
}
catch ( \Throwable $e ) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
exit ;