<?php

    namespace api\auth;

    use \sys\db\SQL;

    class AuthToken extends AuthBase
    {


        public function generateTokenPair( $sid )
        {
            $t = time();

            $aid  = base64_encode( random_bytes( 6 ) );
            $akey = \serialize( (object)[
                    'sid' => $sid ,
                    'id' => $sid ,
                    'y' => 'a' ,
                    'v' => $aid ,
                    't' => $t ,
                    'x' => 3600
            ] );
            $atkn = \sys\wd\Crypto::encrypt( $akey , $_ENV[ 'XTOKEN' ] ?? false );

            // generate refresh token
            $rid  = base64_encode( random_bytes( 6 ) );
            $rkey = \serialize( (object)[
                    'id' => $sid ,
                    'y' => 'r' ,
                    'v' => $rid ,
                    't' => $t ,
                    'x' => 3600 * 24 * 7
            ] );
            $rtkn = \sys\wd\Crypto::encrypt( $rkey , $_ENV[ 'XTOKEN' ] ?? false );
            return [ 'atkn' => $atkn , 'rtkn' => $rtkn , 'sid' => $sid ];
        }

        public function generateResetToken( $sid )
        {
            $t = time();
            // generate refresh token

            $zkey = \serialize( (object)[
                    'sid' => $sid ,
                    'y' => 'z' ,
                    'v' => 'R' ,
                    't' => $t  ,
                    'x' => 600
            ] );
            $ztkn = \sys\wd\Crypto::encrypt( $zkey , $_ENV[ 'XTOKEN' ] ?? false );
            return [ 'ztkn'=>$ztkn ];
        }


        public static function getOTP( $sid ) : string {
            return \sys\db\SQL::Get0( " SELECT otp FROM <DB>.sys_reset_otp WHERE sid = ? AND exp IS NOT NULL AND exp > NOW() " , [ $sid ] ) ;
        }

        public static function setOTP( $sid , $otp ) : bool {
            $hash = password_hash( 'ZZ' . trim(strtolower($otp)) , PASSWORD_DEFAULT ) ;
            return SQL::Exec( " REPLACE INTO <DB>.sys_reset_otp ( sid , otp , exp ) VALUES ( ? , ? , DATE_ADD( NOW() , INTERVAL 10 MINUTE ) ) " , [ $sid , $hash ] ) ;
        }


        // ... existing code ...
        public static function validToken( string $token ) : object | null
        {
            try
            {
                if ( empty( $token ) || mb_strlen( $token ) > 512 )
                    return null;

                $e = \sys\wd\Crypto::decrypt( $token , $_ENV[ 'XTOKEN' ] ?? false );
                if (!$e)
                    return (object)['error' => 'invalid']; // Decryption failed

                $d = \unserialize( $e );
                if (!$d || !is_object($d))
                    return (object)['error' => 'invalid'];

                $tnow = time();
                // Explicitly check for expiration
                if (isset($d->t, $d->x) && ($d->t + $d->x) < $tnow) {
                    return (object)['error' => 'expired'];
                }

                $invalid = ( !isset( $d->y , $d->v , $d->t , $d->x , $d->sid ) ||
                        empty( $d->y ) || empty( $d->v ) ||
                        !is_numeric( $d->sid ) || $d->sid < 1 ||
                        $d->t > $tnow );

                return $invalid ? (object)['error' => 'invalid'] : $d;
            }
            catch ( \Throwable $ex )
            {
                error_log( $ex );
            }

            return null;
        }
        // ... existing code ...


        public static function validAccess( ) : object | null
        {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? false ;
            if ( !$token || !str_starts_with( $token , 'Bearer ' ) )
                return null ;

            // is this an access token
            $a = self::validToken( substr( $token , 7 ) ) ;
            if ( isset($a?->error )) {
                ob_end_clean() ;
                http_response_code(401);
                echo json_encode(['error'=>$a->error]);
                exit ;
            }
            if ( $a && isset($a->y) && $a->y === 'a'  )
                return $a ;

            return null ;
        }

        private function doExchange( $authid , $v ) : object | array | null
        {
            // lookup authinfo
            $auth = $this->getSavedAuthInfo( $authid );
            if ( $auth && is_object( $auth ) && $auth->sid > 0 )
            {
                if ( hash_equals( $auth->vcode , hash( 'sha256' , $v ) ) )
                    return $this->generateTokenPair( $auth->sid );
            }


            return null;
        }

        private function doResetToken( $authid , $v ) : object | array | null
        {
            // lookup authinfo
            $auth = $this->getSavedAuthInfo( $authid );
            if ( $auth && is_object( $auth ) && $auth->sid > 0 )
            {
                if ( hash_equals( $auth->vcode , hash( 'sha256' , $v ) ) )
                    return $this->generateResetToken( $auth->sid );
            }

            return null;
        }


        public function apiExchange( $payload ) : object | array | null
        {
            if ( !$payload || !isset( $payload->authid , $payload->v ) )
                return [ 'error' => 801 ];
            if ( strlen( $payload->authid ) !== 20 || empty( $payload->v ) )
                return [ 'error' => 802 ];

            return $this->doExchange( $payload->authid , $payload->v );
        }

        public function apiGetResetToken( $payload ) : object | array | null
        {
            if ( !$payload || !isset( $payload->authid , $payload->v ) )
                return [ 'error' => 801 ];
            if ( strlen( $payload->authid ) < 20 || empty( $payload->v ) )
                return [ 'error' => 802 ];

            return $this->doResetToken( $payload->authid , $payload->v );
        }

    }