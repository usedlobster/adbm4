<?php
$result = null ;
try {

    session_start() ;

    $rtkn = $_SESSION['_user']?->rtkn ?? '' ;
    $pid  = $_SESSION['_user']?->pid ?? 0  ;

    if ( $rtkn && $pid > 0 ) {

        require_once( __DIR__ . '/../../vendor/autoload.php' );
        \Dotenv\Dotenv::createImmutable( dirname( __DIR__ . '/../../app/.env' ) )->safeLoad();
        if ( ( $_ENV[ 'X_TYPE' ] ?? false ) !== 'app' )
            return;

        define( '_DEV_MODE'   , true ) ;
        define( '_API_DOMAIN' , 'https://api.usedlobster.test/api/' );
        $res = \sys\Util::curlSend( 'POST' , _API_DOMAIN . 'v1/login/refresh' ,
            ['rtkn' => $rtkn , 'pid' => $pid ] , null , null ) ;
        if ( $res ) {
            $pkt = json_decode( $res ) ;
            if ( is_object( $pkt ) &&
                 !isset( $pkt->error ) &&
                 isset( $pkt->ok , $pkt->atkn , $pkt->rtkn ) && !empty($pkt->atkn) && $pkt->ok === true  ) {

                // create a n
                $_SESSION['_user']->atkn = $pkt->atkn ;
                $_SESSION['_user']->rtkn = $pkt->rtkn ;
                $result = ['ok'=>true , 'atkn'=>$pkt->atkn , 'rtkn'=>$pkt->rtkn] ;
            }
        }
    }



}
catch( \Throwable $ex )
{
    $result = null ;
}
finally {
    header('Content-Type: application/json');
    echo json_encode( $result ) ;
}

