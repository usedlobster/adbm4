<?php

    namespace api\v1;

    use api\ApiBaseClass;

    class RefreshTokenApi extends ApiBaseClass
    {
        public function run($args , $payload)
        {
            try
            {

                $id = parent::getBearerID();
                if ( !$id)
                    throw new \Exception('Invalid Bearer Token');
                $left = ($id['exp'] ?? 0 ) - time() ;
                if ( $left < -7200 )
                    throw new \Exception('Token Expired');


                $id['exp'] = (time() + $id['ttl'] ?? 300) ;
                $newToken = \sys\SecureToken::encrypt( \serialize($id) , 'BTOKEN' );
                header( 'Content-Type: application/json' ) ;
                echo json_encode( ['token'=>$newToken] ?? false );
                exit ;



            }
            catch (\Exception $e)
            {
                http_response_code(400) ;
                header( 'Content-Type: application/json' ) ;
                echo json_encode( ['error'=>(string)$e->getMessage()] );
                exit ;
            }




        }
    }