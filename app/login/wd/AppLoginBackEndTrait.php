<?php

    namespace app\login\wd;

    trait AppLoginBackEndTrait
    {
        /* back end operations on our login system */



        private function setPassword(int $sid , string $password) : bool
        {
            try
            {
                $newHash = password_hash($password , PASSWORD_DEFAULT);
                if ( \sys\db\SQL::Exec(" update <DB>.sys_auth set pwd = ? , pwdtype = 1 where sid = ?" , [$newHash , $sid]) !== true )
                    return false;
            }
            catch ( \Throwable )
            {
                return false;
            }

            return true;
        }

        private function getSIDByEmailAndPassword(string $email , string $pwd) : int
        {
            try
            {
                // look up auth information by email
                $auth = \sys\db\SQL::RowN(/** @lang MySQL */ <<<SQL
                                                                 select a.sid, a.pwd , a.pwdtype
                                                                 from adbm4_master.sys_auth as a
                                                                 left join adbm4_master.sys_users as u on u.sid = a.sid
                                                                 where u.email = ? and a.active > 0
                                                                 SQL, [$email]);


                if ( $auth !== false && (($sid = $auth[ 'sid' ] ?? 0) > 0) )
                {
                    $pwdtype = $auth[ 'pwdtype' ] ?? 0;
                    $pwdhash = $auth[ 'pwd' ] ?? '';
                    if ( $pwdtype === 0 && hash_equals(hash('md5' , $pwd) , $pwdhash) )
                    {
                        // upgrade password type
                        $this->setPassword($sid , $pwd);
                        return $sid;
                    }
                    elseif ( $pwdtype === 1 && password_verify($pwd , $pwdhash) )
                    {
                        if ( password_needs_rehash($pwdhash , PASSWORD_DEFAULT) )
                            $this->setPassword($sid , $pwd);
                        return $sid;
                    }
                }
            }
            catch ( \Throwable )
            {
            }

            return -1;
        }


        // can sid ,, use pr9oject pid ( on $db )
        protected function canUseProject(int $sid , int $pid , $db = false) : bool
        {

            $red = \sys\Util::getRedis();
            if ( $red ) {
                $key = 'canuseprj:'.$sid.':'.$pid;
                if ( $red->exists($key) )
                    return true ;
            }


            if ( $sid > 0 && $pid > 0 )
            {
                $db = $db ?: $db = \sys\db\SQL::Get0(" select db from <DB>.sys_projects where pid = ? and active > 0 " , [$pid]);
                if (
                        $db && \sys\db\SQL::Get0(/** @lang sql */ <<<SQL
                                                                      SELECT u.sid
                                                                          FROM <DB>.sys_sid2pid as s2p
                                                                          LEFT JOIN <DB>.sys_projects as p ON ( p.pid = s2p.pid )
                                                                          LEFT JOIN {$db}.users as u ON ( u.sid = s2p.sid )
                                                                          LEFT JOIN {$db}.comps as c ON ( c.cid = u.cid )
                                                                          WHERE s2p.sid = ? AND
                                                                                s2p.pid = ? AND
                                                                                p.active > 0 AND
                                                                                u.active > 0 AND
                                                                                c.active > 0 AND
                                                                                (p.`from` is NULL or p.`from` > NOW() ) and
                                                                                (p.`upto` is NULL or p.`upto` < NOW())
                                                                      SQL, [$sid , $pid]) === $sid
                )
                {
                    if ( $red )
                        $red->setex($key , 90 , 1);
                    return true;
                }

            }
            return false;
        }


        public function getUserProjectInfo(int $sid , int $pid , $db = false) : array | bool
        {


            if ( $sid > 0 && $pid > 0 )
            {
                $db = $db ?: $db = \sys\db\SQL::Get0(" select db from <DB>.sys_projects where pid = ? and active > 0 " , [$pid]);

                if ( $db )
                    $row = \sys\db\SQL::RowN(/** @lang sql */ <<<SQL
                                SELECT  u.sid , p.pid , p.db , u.cid , u.level , u.ea , u.opt1 , u.opt2 , u.opt3 , u.opt4  
                                  FROM <DB>.sys_sid2pid as s2p
                                  LEFT JOIN <DB>.sys_projects as p ON ( p.pid = s2p.pid )
                                  LEFT JOIN {$db}.users as u ON ( u.sid = s2p.sid )
                                  LEFT JOIN {$db}.comps as c ON ( c.cid = u.cid )
                                  WHERE s2p.sid = ? AND
                                        s2p.pid = ? AND
                                        p.active > 0 AND
                                        u.active > 0 AND
                                        c.active > 0 AND
                                        (p.`from` is NULL or p.`from` > NOW() ) and
                                        (p.`upto` is NULL or p.`upto` < NOW())
                                SQL, [$sid , $pid]) ;

                if ( $row && is_array($row) && empty( \sys\db\SQL::error() ))
                    return $row ;
            }
            return false;
        }


        public function getUserProjectsAvailable($sid) : array
        {
            $all = \sys\db\SQL::GetAllN(/** @lang sql */ <<<SQL
                                                             select p.pid,p.db,p.title,l.t, ( l.t is not null and ( l.t > now() - interval 14 day )) as recent
                                                                    from <DB>.sys_sid2pid as s2p
                                                                        left join <DB>.sys_projects as p on ( p.pid = s2p.pid )
                                                                        left join <DB>.sys_login as l on ( l.sid = s2p.sid and l.pid = p.pid)
                                                                    where s2p.sid = ? and 
                                                                           active > 0 and
                                                                           (p.from is null or p.from >= now()) and 
                                                                           (p.upto is null or p.upto <= now()) 
                                                                    order by l.t desc
                                                             SQL, [$sid]) ?: [];

            // filter out unusable projects
            $useable = [];
            foreach ( $all as $i => $row )
                if ( $this->canUseProject($sid , $row[ 'pid' ] , $row[ 'db' ]) )
                    $useable[] = $row;

            return $useable;
        }

        private function getBasicAuthInfo(string $email) : array | false
        {
            return \sys\db\SQL::RowN(<<<SQL
                                         SELECT sa.sid, su.name 
                                             FROM <DB>.sys_auth sa 
                                                 LEFT JOIN <DB>.sys_users su ON (su.sid=sa.sid) 
                                                     WHERE su.email = ? AND sa.active > 0
                                         SQL, [$email]) ;
        }

        private function getActiveOTPCode(int $sid) : string
        {
            $otp_hash = \sys\db\SQL::Get0(" SELECT otp FROM <DB>.sys_reset_otp WHERE sid = ? AND  exp > ( now() - INTERVAL 10 minute ) " , [$sid]);
            if ( !empty($otp_hash) && empty(\sys\db\SQL::error()) )
                return $otp_hash;
            return '';
        }

        private function getSIDfromEmail( string $email ) : int
        {
            $email = trim(strtolower($email));
            $info = $this->getBasicAuthInfo($email) ?? [];
            return $info[ 'sid' ] ?? -1 ;
        }

        /**
         * Generates a reset code and sends it via email if necessary. This method checks the provided email,
         * retrieves the corresponding user record, and ensures a reset code is created and sent only when needed.
         * if a code has been sent in last 10 minutes , or the email is not found will not send a new code.
         *
         * @param  string  $email  The email address of the user requesting the reset code.
         * @return void This method does not return a value.
         */
        private function generateResetCodeIfNecessary($email) : void
        {
            try
            {
                $sid = $this->getSIDfromEmail($email);
                if ( $sid < 1 )
                    return;

                $otp = $this->getActiveOTPCode($sid);
                if ( !empty($otp) )
                    return; // already have an active otp code


                // get user details for email

                $resetCode = \sys\SecureToken::SecureCode();
                if (
                        \sys\db\SQL::Exec(<<<SQL
                                              REPLACE INTO <DB>.sys_reset_otp (sid, otp ,exp) VALUES (?,sha2( concat( '_W' , ?  , '_D' ) , 256 ),now())
                                              SQL, [$sid , $resetCode]) !== true
                )
                {
                    error_log('failed to save reset code for '.$email);
                    return;
                }

                // need to send email now with code
                $auth = $this->getBasicAuthInfo($email) ?? [];
                $data = ['name' => $auth['name'] ?? 'User' , 'code' => $resetCode];
                \sys\wd\SendEmail::sendTemplate($email , $data['name'] , 'reset-code' , $data);
            }
            catch ( \Throwable $ex )
            {
            }
        }

        public function checkResetCode( string $email , string $code) : string {
            usleep( random_int( 50000 , 100000)) ;

            $sid = $this->getSIDfromEmail( $email ) ;
            if ( $sid < 1 )
                return 'Invalid User' ;

            $otp = $this->getActiveOTPCode($sid);
            if ( empty($otp))
                return 'Code Expired' ;

            if ( $sid > 0 && hash_equals(hash('sha256' , '_W' . $code . '_D' ) , $otp) )
                return '';


            return 'Invalid Code';
        }

        public function attemptPasswordChange( string $email , string $pwd1 , string $pwd2 ) : string {

            $auth = $this->getBasicAuthInfo($email) ?? [];
            $sid = $auth[ 'sid'] ?? 0 ;
            if ( $sid < 1 )
                return 'Invalid User' ;

            if ( empty($pwd1) || empty($pwd2) || empty($email)  )
                return 'Empty Password' ;

            if ( $pwd1 !== $pwd2 )
                return 'Passwords do not match';

            if ( mb_strlen($pwd1) > 128 )
                return 'Password too long';

            if ( mb_strlen($pwd1) <= 8 )
                return 'Password too short';

            if ( preg_match('/^[1-9A-D]{4}-[1-9A-D]{4}$/' , $pwd1) === 1 )
                return 'Password not allowed';

            if ( $this->setPassword( $sid , $pwd1 ))
            {
                $data = ['name' => $auth['name'] ?? 'User' ];
                \sys\wd\SendEmail::sendTemplate($email , $data['name'] , 'reset-password-changed' , $data);
            }
            else
                return 'Problem resetting your password' ;



            return '' ;
        }



    }