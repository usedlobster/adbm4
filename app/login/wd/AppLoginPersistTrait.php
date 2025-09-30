<?php

    namespace app\login\wd;

    trait AppLoginPersistTrait {

        // keep login token for 30 days.
        private const int EXPIRE_LOGIN = 86400 * 30 ; // 30 days

        private function createLoginToken( int   $sid ) : string
        {
            try
            {
                // create a login token in base64 format
                // of form selector:validator:
                $s = bin2hex(random_bytes(16));
                $v = random_bytes(32);
                $t = bin2hex(random_bytes(32));
                if ( \sys\db\SQL::Exec( /** @lang sql */ <<<SQL
 insert into <DB>.sys_login_tokens 
     (token, sid ,  s, v, exp, cr)
     values( ? ,? ,? , ? , now() + INTERVAL 30 day  , now())
SQL, [$t , $sid , $s , hash('sha256' , $v)]) === true  )
                    return base64_encode($s.':'.$v.':') ;
            }
            catch ( \Throwable ) {

            }
            return '' ;
        }



        public function rememberLogin( $sid  ) {

            $token = $this->createLoginToken($sid);
            if ( !empty($token)  )
                \sys\Util::saveCookie('_adbm_token_' , $token , self::EXPIRE_LOGIN);
            else
                \sys\Util::saveCookie('_adbm_token_' , '' , -3600);

        }

        public function persistSignOut( $sid ) {
            \sys\Util::saveCookie('_adbm_token_' , '' , -3600);
            if ( $sid > 0 )
                \sys\db\SQL::Exec(" DELETE FROM <DB>.sys_login_tokens WHERE sid = ?" , [$sid]);

        }


        public function useLoginToken() : int
        {
            try
            {
                $token = $_COOKIE['_adbm_token_'] ?? '' ;
                if ( empty($token))
                    return -1 ;

                $u = explode( ':' , base64_decode($token) , 3 ) ;
                $s = $u[0] ?? '' ;
                $v = $u[1] ?? '' ;
                // make sure cookie in correct format , ie (selector:validator:)
                if ( empty($s) || empty($v) || count($u) !== 3 ||
                     !is_string($s) || !is_string($v) ||
                     strlen( $s ) !== 32 || strlen($v ) !== 32 )
                    throw new \Exception('invalid token');

                // lookup token in db - may have been deleted
                $row = \sys\db\SQL::RowN(" SELECT * from <DB>.sys_login_tokens where s = ? and exp > now() " , [$s]);
                if ( !$row || !is_array($row) || !empty(\sys\db\SQL::error()) || !hash_equals($row['v'] ?? '' , hash('sha256', $u[1] ?? '' )))
                    throw new \Exception('invalid token');

                // is this a valid sid?
                $sid = $row['sid'] ?? 0 ;
                if ( $sid > 0 )
                {
                    $this->rememberLogin($sid); // simply regenerate a new token
                    \sys\db\SQL::Exec(" DELETE FROM <DB>.sys_login_tokens WHERE s = ?" , [$s]);
                    return $sid;
                }
                else
                    throw new \Exception('invalid token');


            }
            catch ( \Throwable $ex )
            {
                \sys\Util::saveCookie('_adbm_token_' , '' , -3600);
                if ( isset($s))
                    \sys\db\SQL::Exec( " DELETE FROM <DB>.sys_login_tokens WHERE s = ?", [$s]);
                return -1 ;
            }

            return $sid ?? 0  ;
        }



    }

