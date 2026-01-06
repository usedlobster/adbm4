<?php

    namespace api\auth;

    use sys\db\SQL;

    class AuthBase
    {


        // rate limits
        protected const array FT_LOGIN_USER = [ 1 , [ [ '5 minute' , 10 ] , [ '30 second' , 3 ] ] ];
        protected const array FT_LOGIN_IP   = [ 2 , [ [ '5 minute' , 50 ] , [ '30 second' , 10 ] ] ];
        protected const array FT_RESET_USER = [ 3 , [ [ '5 minute' , 5 ]  , [ '10 second' ] , 1 ] ];
        protected const array FT_RESET_CODE = [ 4 , [ [ '5 minute' , 5 ]  , [ '30 second' ] , 1 ] ];
        protected const array FT_RESET_IP   = [ 5 , [ [ '5 minute' , 50 ] , [ '30 second' ] , 10 ] ];

        protected function getAuth( string $username ) : object | null
        {
            $auth = SQL::RowN( /** @lang MySQL */ <<<SQL
                                                         SELECT a.sid, a.pwd , a.active , a.pwdtype , u.email , u.username , u.name 
                                                         FROM adbm4_master.sys_auth as a
                                                         LEFT join adbm4_master.sys_users as u on u.sid = a.sid
                                                         WHERE a.active >= 0 and ( u.email = ? or u.username = ? ) 
                                                         SQL, [ $username , $username ] );
            if ( $auth )
                return (object)$auth;
            else {
                $err = \sys\db\SQL::error() ;
                if ( !empty($err))
                    return (object)['error'=>$err];
            }

            return null;
        }


        protected function setPassword( int $sid , string $pwd ) : bool
        {

            return SQL::LockExec( " update adbm4_master.sys_auth set pwd = ? , changed = now() where sid = ? " ,
                    [ password_hash( $pwd , PASSWORD_DEFAULT ) , $sid ] , 'adbm4_master.sys_auth write' ) ;
        }

        protected function makeAuthID( int $sid , string $vcode ) : string | null
        {
            if ( ( $red = \sys\Util::getRedis( 1 ) ) )
            {
                do
                {
                    $authkey = 'authid:' . ( ( $authid = base64_encode( \random_bytes( 15 ) ) ) );
                } while ( $red->exists( 'authid:' . $authid ) );

                // keep for 30 seconds
                if ( $red->setex( $authkey , 30 , \serialize( (object)[ 'sid' => $sid , 'vcode' => $vcode ] ) ) )
                    return $authid;
            }

            return null;
        }

        protected function getSavedAuthInfo( string $authid ) : object | null
        {
            if ( ( $red = \sys\Util::getRedis( 1 ) ) )
            {
                $rkey = 'authid:' . $authid;
                $info = $red->get( $rkey );
                if ( $info )
                    $red->del( $rkey);
                return $info ? \unserialize( $info ) : null;
            }
            return null;
        }


        protected static function getBearerToken() : string
        {
            return '';
        }
    }
