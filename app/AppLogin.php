<?php

    namespace app;

    use app\model\ViewLoginTrait;
    use app\model\ViewResetTrait;

    class AppLogin
    {
        const int FT_LOGIN_EMAIL         = 1;
        const int FT_LOGIN_IP            = 2;
        const int FT_IP_RESET_REQUEST    = 3;
        const int FT_EMAIL_RESET_REQUEST = 4;
        const int FT_VERIFY_CODE         = 5;


        use ViewLoginTrait,ViewResetTrait ;

        protected static AppMaster|null $_app = null;
        public static array          $_id  = [];

        public function __construct($app)
        {
            self::$_app = $app;
            self::$_id  = [];
        }

        // very simple login test
        public function haveLogin()
        {
            if ( empty(self::$_id) )
                self::$_id = $_SESSION[ '_login' ] ?? ['sid' => 0 , 'pid' => 0 , 'db' => false];

            return ((self::$_id[ 'sid' ] ?? 0 > 0) && (self::$_id[ 'pid' ] ?? 0 > 0) && (self::$_id[ 'db' ] ?? false) !== false);
        }

        private function rateLimitCheck($email)
        {
            try
            {
                $failCount = \sys\Audit::getFail($email , self::FT_LOGIN_EMAIL , '5 minute');
                if ( $failCount >= 10 )
                    return false;
                $failCount = \sys\Audit::getFail($email , self::FT_LOGIN_EMAIL , '30 second');
                if ( $failCount >= 3 )
                    return false;

                \sys\Audit::setAttempt($email , self::FT_LOGIN_EMAIL);


                $ip = $_SERVER[ 'REMOTE_ADDR' ] ?? '';
                $failCount = \sys\Audit::getFail($ip , self::FT_LOGIN_IP , '5 minute');
                if ( $failCount >= 30 )
                    return false;
                $failCount = \sys\Audit::getFail($ip , self::FT_LOGIN_IP , '30 second');
                if ( $failCount >= 10 )
                    return false;

                \sys\Audit::setAttempt($ip , self::FT_LOGIN_IP);
            }
            catch ( \Throwable $ex )
            {
                return false;
            }

            return true; // not rate limited
        }

        private function lookupSID(string $email , string $password) : int
        {
            try
            {
                if ( empty($email) || empty($password) )
                    return -1;

                $auth = \sys\db\SQL::RowN(
<<< SQL
      select a.sid, a.pwd   
      from adbm4_master.sys_auth as a
      left join adbm4_master.sys_users as u on u.sid = a.sid
      where u.email = ? and a.active > 0  
SQL, [$email]);

                if ( $auth === false || !is_array($auth) )
                    return -1;

                $sid = $auth[ 'sid' ] ?? -1;
                if ( is_numeric($sid) && $sid > 0 )
                {
                    $storedHash = $auth[ 'pwd' ] ?? '';
                    if ( !empty($storedHash) && password_verify($password , $storedHash) )
                    {
                        // Only rehash if needed
                        if ( password_needs_rehash($storedHash , PASSWORD_DEFAULT) )
                        {
                            $newHash = password_hash($password , PASSWORD_DEFAULT);
                            \sys\db\SQL::Exec(<<< SQL
                                                  update <DB>.sys_auth 
                                                  set pwd = ? 
                                                  where sid = ?  
                                                  SQL, [$newHash , $sid]);
                        }
                        return $sid;
                    }
                }
            }
            catch ( \Throwable $e )
            {
                error_log("Login failure: ".$e->getMessage());
            }

            return -1;
        }

        /**
         * Retrieves the database name for a given project ID.
         *
         * @param  int  $pid  The project ID for which the database name is requested. Must be greater than 0.
         * @return bool|string Returns the database name as a string if the project ID is valid and exists, or false if the input is invalid or the project is not found.
         */
        private function getProjectDatabase(int $pid) : bool|string
        {
            return ($pid > 0) ? \sys\db\SQL::Get0(" select db from <DB>.sys_projects where pid = ? " , [$pid]) : false;
        }

        private function setUser(int $sid , int $pid = 0 , $db = true) : bool
        {
            try
            {
                if ( $sid > 0 && $pid > 0 )
                    self::$_id = [
                            'sid' => $sid ,
                            'pid' => $pid ,
                            'db' => (($db === true) ? $this->getProjectDatabase($pid) : $db)
                    ];
                elseif ( $sid > 0 )
                    self::$_id = ['sid' => $sid , 'pid' => 0 , 'db' => false];
                else
                    self::$_id = [];
            }
            catch ( \Throwable $ex )
            {
                self::$_id = [];
                return false;
            }

            finally
            {
                $_SESSION[ '_login' ] = self::$_id;
                if ( ($_SESSION[ '_login' ] ?? []) !== (self::$_id ?? []) )
                    return false;
            }

            return true; // saved ok
        }

        private function autoPick($list) : int
        {
            if ( !is_array($list) || count($list) === 0 )
                return 0;
            // if last was recent, or there is only 1 choice
            if ( ($list[ 0 ][ 'recent' ] ?? 0) > 0 || count($list) === 1 )
                return $list[ 0 ][ 'pid' ] ?? 0;

            return 0;
        }

        public function canUse(int $sid , int $pid , $db) : bool
        {
            if ( $sid > 0 && $pid > 0 && is_string($db) && !empty($db) )
            {
                if (
                        \sys\db\SQL::Get0(<<<SQL
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
                    return true;
            }
            return false;
        }

        /**
         * Attempts to authenticate a user with the provided email and password.
         * Incorporates mechanisms for rate limiting, logging, and security.
         *
         * @param  string  $email  The email address provided by the user for authentication.
         * @param  string  $password  The password provided by the user for authentication. Must not exceed 128 characters.
         * @return bool|string Returns true on successful login and navigation,
         *                     a string error message for specific failure cases,
         *                     or 'Invalid credentials' if authentication fails.
         */
        private function loginWithEmailAndPassword(string $email , string $password) : bool|string
        {
            // let us try and confuse hackers or at least slow them down
            usleep(random_int(50000 , 100000));
            try
            {
                if ( !empty($email) && !empty($password) && mb_strlen($password) <= 128 )
                {
                    // lets do some rate checks
                    if ( $this->rateLimitCheck($email) !== true )
                        return 'Too many login attempts';

                    $sid = $this->lookupSID($email , $password);
                    if ( $this->setUser($sid , 0 , false) !== true )
                        return 'Login Unavailable - Try again later';

                    return true;
                }
            }
            catch ( \Throwable $ex )
            {
                error_log($ex);
                // any exception error is likely down to system errors,
                return 'Login Unavailable - Try again later';
            }

            return 'Invalid credentials';
        }

        /**
         * Retrieves a list of active and usable projects for a given session ID.
         *
         * @param  mixed  $sid  The system user id  to retrieve active projects for.
         * @return array Returns an array of active and usable projects for the given session ID. Each project is represented as
         *               an associative array with details such as project ID, database name, title, and recent login status.
         */
        public function getActiveProjects($sid) : array
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
                if ( $this->canUse($sid , $row[ 'pid' ] , $row[ 'db' ]) )
                    $useable[] = $row;

            return $useable;
        }

        public function getUserInfo()
        {
            $info =[] ;
            if ( true )
            {
                $info = ['_id'=>self::$_id , 'fetch'=>time()];
                if (( $sid = self::$_id[ 'sid' ] ?? 0 ) > 0 )
                {
                    if (( $pid = self::$_id[ 'pid' ] ?? 0 ) > 0 )
                    {
                        if (( $db = self::$_id[ 'db' ] ?? '' )  !== ''  )
                        {
                            // fake rest of details for now
                            $info[ 'dname']  = 'Wooster Dennis';
                            $info[ 'cname' ] = 'Alandale Logistics' ;
                            $info[ 'pname'] = 'Test Project 1' ;
                            $info[ 'editor_allowed'] = 1 ;
                        }
                    }
                }

                $_SESSION[ '_info' ] = $info;
            }

            return $info;
        }
    }