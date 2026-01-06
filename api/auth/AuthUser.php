<?php

    namespace api\auth;

    use sys\db\SQL;

    class AuthUser extends AuthBase
    {


        private function loginUAP( string $username , string $password ) : object | null
        {
            try
            {
                $auth = $this->getAuth( $username );
                if ( $auth && is_object( $auth ) )
                {
                    if ( !isset($auth->error)  && ($auth?->active ?? 0)> 0 && ($auth?->sid ?? 0 ) > 0 )
                    {
                        // check passwords
                        if ( $auth->pwdtype === 0 && hash_equals( hash( 'md5' , $password ) , $auth->pwd ) )
                            $this->setPassword( $auth->sid , $password );
                        elseif ( $auth->pwdtype === 1 && password_verify( $password , $auth->pwd ) )
                        {
                            if ( password_needs_rehash( $auth->pwd , PASSWORD_DEFAULT ) )
                                $this->setPassword( $auth->sid , $password );
                        }
                    }

                    return $auth;
                }
            }
            catch ( \Throwable $ex )
            {
            }

            return null;
        }


        public function apiLoginUAP( $payload ) : object | array | null
        {
            // check payload
            if ( !$payload || !isset( $payload->username , $payload->password , $payload->vcode ) )
                return [ 'error' => 801 ];

            if ( !\sys\validate\Valid::email( $payload->username ) && !\sys\validate\Valid::username( $payload->username ) )
                return [ 'error' => 802 ];

            if ( !\sys\Audit::rateLimit( $payload->username , self::FT_LOGIN_USER ) )
                return [ 'error' => 803 ];

            if ( !\sys\Audit::rateLimit( $_SERVER[ 'REMOTE_ADDR' ] ?? $_SERVER[ 'HTTP_REFERER' ] ?? '' , self::FT_LOGIN_IP ) )
                return [ 'error' => 803 ];

            $auth = $this->loginUAP( $payload->username , $payload->password );
            if ( $auth )
            {
                if ( isset( $auth->error ))
                    return (object)['error'=>806] ;
                $authid = $this->makeAuthID( $auth->sid , $payload->vcode );
                if ( $authid )
                    return (object)[ 'authid' => $authid ];
            }



            return null;
        }

        private function changePassword( int $sid , string $newpwd , string $ztkn ) : bool
        {
            $z = \api\auth\AuthToken::validToken( $ztkn );

            if ( $z && $z->y === 'z' && $z->sid === $sid )
            {
                return $this->setPassword( $sid , $newpwd );
            }

            return false;
        }


        public function apiChangePassword( $payload ) : object | array | null
        {
            if ( !$payload || !isset( $payload->ztkn , $payload->user , $payload->pass1 , $payload->pass2 ) )
                return [ 'error' => 801 ];
            if ( empty( $payload->ztkn ) || empty( $payload->user ) || empty( $payload->pass1 ) || empty( $payload->pass2 ) || $payload->pass1 !== $payload->pass2 )
                return [ 'error' => 802 ];

            $auth = $this->getAuth( $payload->user );
            if ( !$auth || !is_object( $auth ) || $auth->active < 1 || !is_numeric( $auth->sid ) || $auth->sid < 1 )
                return [ 'error' => 802 ];


            if ( $this->changePassword( $auth->sid , $payload->pass1 , $payload->ztkn ) )
                return [ 'ok' => true ];

            return [ 'error' => 809 ];
        }

    }