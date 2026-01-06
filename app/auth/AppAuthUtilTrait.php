<?php

    namespace app\auth;

    use api\auth\AuthToken;

    trait AppAuthUtilTrait
    {

        private static function saveToken( $rtkn , $remember )
        {
            setcookie( '_wd_auth_token' , $remember ? $rtkn : '' , [
                    'expires' => time() + ( $remember ? 30 : -1 ) * 86400 ,
                    'path' => '/' ,
                    'domain' => $_ENV[ 'APP_DOMAIN' ] ,
                    'secure' => true ,
                    'httponly' => true ,
                    'samesite' => 'Lax'
            ] );
        }


        public function tryUserAndPasswordLogin()
        {
            try
            {
                // any login attempt logs out current user
                self::$_id = self::NO_LOGIN;
                if ( isset( $_POST[ '_login' ] ) && \sys\blade\BladeMan::checkCSRF() && isset( $_POST[ 'username' ] ) && isset( $_POST[ 'password' ] ) )
                {
                    if ( !\sys\validate\Valid::username( $_POST[ 'username' ] ) && !\sys\validate\Valid::email( $_POST[ 'username' ] ) )
                        return \sys\Error::msg( 1001 );
                    $vcode  = base64_encode( random_bytes( 63 ) );
                    $result = \app\AppMaster::apiPost( 'v0/login' , [
                            'act' => 'uap' ,
                            'username' => $_POST[ 'username' ] ?? '' ,
                            'password' => $_POST[ 'password' ] ?? '' ,
                            'vcode' => hash( 'sha256' , $vcode ) ,
                    ] );

                    if ( $result && isset( $result->authid ) )
                    {
                        $result = \app\AppMaster::apiPost( 'v0/login' , [
                                'act' => 'exg' ,
                                'authid' => $result->authid ,
                                'v' => $vcode
                        ] );

                        if ( isset( $result , $result->sid , $result->atkn , $result->rtkn ) && is_numeric( $result->sid ) && $result->sid > 0 )
                        {
                            self::$_id[ 'sid' ]  = $result->sid ?? 0;
                            self::$_id[ 'atkn' ] = $result->atkn ?? '';
                            self::$_id[ 'rtkn' ] = $result->rtkn ?? '';
                            $_SESSION[ '_id' ]   = self::$_id;
                            self::saveToken( $result->rtkn , ( $_POST[ 'remember_me' ] ?? '' ) === 'on' );
                            return true;
                        }
                    }

                    return \sys\Error::msg( $result?->error ?? 806 );
                }
            }
            catch ( \Throwable $ex )
            {
                error_log( $ex->getMessage() );
            }

            return false;
        }

        private static function tryRefresh()
        {
            return false;
        }


        public function getProjectList() : ?array
        {

            $result = \app\AppMaster::apiPost('v0/project' , ['act'=>'list']) ;
            if ( !$result || !isset($result->projects) || !is_array($result->projects))
                return [] ;
            return $result->projects ;

        }

    }