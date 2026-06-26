<?php
session_cache_limiter('' ) ;
try {
    if (session_status() !== PHP_SESSION_ACTIVE && !session_start()) {
        http_response_code(500); // internal server error
        exit;
    }
    $login = $_SESSION['_login'] ?? null ;
    session_write_close() ;

    if ( is_null($login) || !is_object($login) || !isset($login->sid, $login->pid , $login->atkn )) {
        http_response_code(403); // forbidden
        exit;
    }

    $req = $_SERVER['REQUEST_URI'] ?? '';
    $path = explode('?', $req, 2);
    $parts = explode('/', strtolower( $path[0] ?? '/'));
    if ( array_shift($parts) !== '' ) {
        http_response_code(400 ) ;
        exit ;
    }
    $base = array_shift( $parts ) ?? false ;
    if ( $base === 'live' ) {
        $pid = array_shift( $parts ) ?? false ;
        if ( !$pid || !ctype_digit( $pid ) || ( $pid !=0 && (int)$pid !== (int)$login->pid )) {
            http_response_code(403) ;
            exit ;
        }

        $sub = array_shift($parts) ?? false;
        switch( $sub ) {
            case 'img' :
                $path = '/live/' . $login->pid . '/img/' . join( '/' , $parts ) ;
                serveFile( $path ) ;
                break ;

        }
    }

    http_response_code( 404 ) ;
    exit ;

}
catch( \Throwable $ex ) {
    error_log((string)$ex);
    http_response_code(500);
    exit ;
}

function serveFile(string $path, $age = 3600) : never
{
    $root = realpath(__DIR__ . '/../../' );
    $file = realpath($root . '/' . $path);
    if (str_starts_with($file, $root) && file_exists($file)) {
        $pinfo = pathinfo($file);
        $mime = match ($pinfo['extension']) {
            'png' => 'image/png',
            default => null ,
        };
        if ( !$mime ) {
            http_response_code( 400 ) ;
            exit ;
        }

        header('Cache-Control: private, max-age=' . $age);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $age) . ' GMT');
        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }

    http_response_code(404); //  not found
    exit;
}


