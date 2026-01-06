<?php

    namespace api\auth;

    use api\auth\AuthBase;

    class AuthReset extends AuthBase
    {

        /**
         * Attempt to send reset code to user
         *
         * @param string $username
         * @return object|array|null
         */
        protected function sendResetCode( $username ) : object | array | null
        {
            try
            {
                //
                $auth = $this->getAuth( $username );
                if ( $auth->sid > 0 && $auth->active > 0 )
                {
                    // have an active account
                    $otp = \api\auth\AuthToken::getOTP( $auth->sid );
                    if ( empty( $otp ) )
                    {
                        $otp = \sys\Util::generateCode();
                        if ( !empty( $otp ) )
                        {
                            if ( \api\auth\AuthToken::setOTP( $auth->sid , $otp ) )
                            {
                                $auth->otp = $otp;
                                \sys\wd\Email\SendEmail::sendTemplate( $auth->email , $auth->name ?? $auth->email ?? 'user' , 'reset-code' , (array)$auth );
                            }
                        }
                    }
                }
            }
            catch ( \Throwable $ex )
            {
                return [ 'error' => 805 ];
            }

            return [ 'ok' => true ];
        }


        /**
         * Check given reset code matches user, and sand send authid if so
         *
         * @param string $username
         * @return string|null
         */
        protected function resetCodeCheck( $username , $otp , $vcode ) : string | null
        {
            $auth = $this->getAuth( $username );
            if ( $auth && is_object($auth) && is_numeric($auth->sid) && $auth->sid > 0 && $auth->active > 0 ) {
                    $curOtp = \api\auth\AuthToken::getOTP( $auth->sid );
                    if ( !empty( $curOtp ) && password_verify( 'ZZ' . trim( strtolower( $otp ) ) , $curOtp ) )
                    {
                        $authid = $this->makeAuthID( $auth->sid , $vcode );
                        if ( !empty( $authid ) )
                            return $authid;
                    }
                }


            return null ;
        }

        public function exgResetToken( $authid , $vcode ) : object | array | null
        {
            // lookup authinfo
            $auth = $this->getSavedAuthInfo( $authid );
            if ( $auth && is_object( $auth ) && $auth->sid > 0 )
            {
                if ( hash_equals( $auth->vcode , hash( 'sha256' , $vcode ) ) )
                    return $this->generateResetToken( $auth->sid );
            }


            return null;

        }




        public function apiSendCode( $payload ) : object | array | null
        {

            if ( !is_object( $payload ) || !isset( $payload->user , $payload->ip ) || empty( $payload->user ) || empty( $payload->ip ) )
                return [ 'error' => 802 ];

            if ( !\sys\Audit::rateLimit( $payload->user , self::FT_RESET_USER ) )
                return [ 'error' => 803 ];
            if ( !\sys\Audit::rateLimit( $payload->ip , self::FT_RESET_IP ) )
                return [ 'error' => 804 ];
            return $this->sendResetCode( $payload->user );
        }


        public function apiGetResetAuth( $payload ) : object | array | null
        {
            // check payload
            if ( !$payload || !isset( $payload->user , $payload->otp , $payload->vcode ) )
                 return ['error'=>801] ;

            if ( !\sys\validate\Valid::email( $payload->user ) && !\sys\validate\Valid::username( $payload->user ))
                return ['error'=>802] ;

            $otp = substr( $payload->otp , 0 , 4 ) . '-' . substr( $payload->otp , 4 , 4 ) ;
            if ( !\sys\validate\Valid::otpcode( $otp ))
                return ['error'=>806] ;

            if ( !\sys\Audit::rateLimit( $payload->user , self::FT_RESET_USER ))
                return ['error'=>803] ;

            if ( !\sys\Audit::rateLimit( $otp , self::FT_RESET_CODE ))
                return ['error'=>804] ;

            // get authid
            $authid =  $this->ResetCodeCheck( $payload->user , $otp , $payload->vcode ) ;
            if ( !empty( $authid ) )
                return (object)['authid' => $authid  ];


            return (object)['error'=>808];

        }



    }