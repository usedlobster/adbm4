<?php

    namespace api\v0;

    use api\ApiBase;

    class ApiLogin extends ApiBase {

        public function api( $payload ) : object | array | null   {

            try
            {
                switch ( $payload->act )
                {
                    case 'uap':
                        return new \api\auth\AuthUser()?->apiLoginUAP( $payload );
                    case 'exg' :
                        return new \api\auth\AuthToken()?->apiExchange( $payload );
                    case 'sendcode' :
                        return new \api\auth\AuthReset()?->apiSendCode( $payload );
                    case 'resetauth' :
                        return new \api\auth\AuthReset()?->apiGetResetAuth( $payload );
                    case 'resettoken' :
                        return new \api\auth\AuthToken()?->apiGetResetToken( $payload );
                    case 'changepassword' :
                        return new \api\auth\AuthUser()?->apiChangePassword( $payload );
                    default:
                        return [ 'error' => 800 ];
                }
            }
            catch( \Throwable $ex )
            {return (object)[ 'error' => $ex->getMessage() ];
            }

        }


    }