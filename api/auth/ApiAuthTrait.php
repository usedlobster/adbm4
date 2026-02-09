<?php

namespace api\auth;

trait ApiAuthTrait
{

    private function lookupUser( string | int $account ) : object | null
    {
        $w    = is_numeric( $account ) ? ' a.sid = ? ' : ' a.username = ? ';
        $auth = \sys\db\SQL::RowN( " select a.sid , a.active , a.pwdtype , a.username , a.pwd from <DBM>.sys_auth a WHERE {$w} " , [ $account ] );

        if ( !$auth || \sys\db\SQL::error() )
            return null;

        return is_array( $auth ) ? (object)$auth : null;
    }

    private function renewPassword( int $sid , string $password ) : bool {
        if ( $sid > 0 )
            return \sys\db\SQL::Exec( " update <DBM>.sys_auth set pwd = ? , pwdtype = 1 where sid = ? " , [ password_hash( $password , PASSWORD_DEFAULT ) , $sid ] );
        return false ;
    }

    private function checkPassword( object $user , string $password ) : bool
    {
        if ( !isset( $user->pwd , $user->pwdtype ) )
            return false;

        if ( $user->pwdtype === 0 && hash_equals( hash( 'md5' , $password ) , $user->pwd ) )
            $this->renewPassword( $user->sid , $password );
        elseif ( $user->pwdtype === 1 && password_verify( $password , $user->pwd ) )
        {
            if ( password_needs_rehash( $user->pwd , PASSWORD_DEFAULT ) )
                $this->renewPassword( $user->sid , $password );
        }
        else
            return false;

        return true;
    }

    private function checkOtpPkt( $pkt ) : int {

        try {
            if (is_object($pkt) && isset($pkt->code, $pkt->user, $pkt->t)) {
                if (\sys\Valid::otp($pkt->code) && \sys\Valid::account($pkt->user)) {
                    if (\sys\Audit::rateLimitOK($pkt->user, self::RATE_LIMIT_CHKCODE)) {
                        $t = time();
                        if ($t >= $pkt->t && $t < ($pkt->t + 30)) {
                            $auth = $this->lookupUser($pkt->user);
                            if ($auth && is_object($auth) && (($auth->sid ?? 0) > 0) && (($auth->active ?? 0) > 0))
                                return $auth->sid;
                        }
                    }

                    return -1 ;
                }
            }
        }
        catch( \Throwable $ex) {

        }
        return 0 ;

    }


    private function suggestProject( int $sid ) : int
    {
        // try last used
        $pid = \sys\db\SQL::Get0( " select pid from <DBM>.sys_last_pid where sid = ? " , [ $sid ] );
        if ( $pid < 1 || self::validPID( $sid , $pid ) === false )
            return 0;


        return $pid;
    }

    private function generateAUTHID( int $sid , string $vcode ) : string
    {
        $red = \sys\Redis::getRedis( 2 );
        if ( $red )
        {
            $authdata = \serialize( (object)[
                'sid' => $sid ,
                'vcode' => $vcode
            ] );

            $atempt = 0;
            while ( ++$atempt < 10 )
            {
                $authkey = 'authid:' . ( $authid = base64_encode( random_bytes( 9 ) ) );
                if ( !( $red->exists( $authkey ) ) )
                {
                    // save for 30 seconds , as expected exchange to happen very soon after login.
                    if ( $red->setex( $authkey , 30 , $authdata ) )
                        return $authid;
                }
            }
        }

        return '';
    }

    private function findAUTHID( $authid ) : ?object
    {
        $red = \sys\Redis::getRedis( 2 );
        if ( $red )
        {
            $payload = $red->get( 'authid:' . $authid );
            if ( $payload )
            {
                $red->del( 'authid:' . $authid );
                return \unserialize( $payload );
            }
        }

        return null;
    }

    private function validSID( int $sid ) : bool
    {
        return ( $sid > 0 ) ? true : false;
    }

    private function validPID( int $sid , int $pid ) : bool
    {
        // TODO : lookup right for user to use project pid
        return ( $pid >= 0 ) ? true : false;
    }


    private function getActiveOTP(int $sid) : ?string
    {
        $otp_hash = \sys\db\SQL::Get0(" SELECT otp FROM <DBM>.sys_reset_otp WHERE sid = ? AND (  NOW() <=exp  ) ", [$sid]);
        if (!empty($otp_hash) || !empty(\sys\db\SQL::error())) {
            return $otp_hash;
        }
        return null;
    }

    private function makeOTP(int $sid) : string
    {
        $chars = '123456789ABCDEFGHJKLMNPRSTVWXYZ';
        $code = '';

        for ($i = 0; $i < 8; $i++) {
            if ($i == 4) {
                $code .= '-';
            }

            $code .= $chars[\random_int(0, strlen($chars) - 1)];
        }

        return $code;
    }

    private function setOTP(int $sid, string $otp) : bool
    {
        if ($sid > 0) {
            return \sys\db\SQL::Exec(" REPLACE INTO <DBM>.sys_reset_otp (sid,otp,exp) VALUES (?,?,now() + INTERVAL 15 minute)  ",
                [$sid, password_hash($otp, PASSWORD_DEFAULT)]);
            false;
        }
        return false;
    }




    private function generateACCESS( int $sid , int $pid ) : ?object
    {
        try
        {
            $t     = time();
            $adata = [
                'y' => 'a' ,
                'sid' => $sid ,
                'pid' => $pid ,
                't' => $t ,
                'x' => 421 // 421 , // 7 minutes 1 second
            ];

            $atkn = \sys\Crypto::encrypt( \serialize( $adata ) , $_ENV[ 'A_TOKEN' ] ?? false ) ?? false;
            if ( !$atkn )
                return null;

            $rdata = [
                'y' => 'r' ,
                'sid' => $sid ,
                't' => $t ,
                'x' => 1382419 ,
            ];
            $rtkn  = \sys\Crypto::encrypt( \serialize( $rdata ) , $_ENV[ 'R_TOKEN' ] ?? false ) ?? false;
            if ( !$rtkn )
                return null;

            return (object)[ 'sid' => $sid , 'pid' => $pid , 'atkn' => $atkn , 'rtkn' => $rtkn ];
        }
        catch ( \Throwable $ex )
        {
        }

        return null;
    }

    private function validRefresh( $token ) {

        @ $d = (object)\unserialize( \sys\Crypto::decrypt( $token , $_ENV[ 'R_TOKEN' ] ?? false ) ?? false );
        if ( !$d || !isset( $d->y , $d->sid , $d->t , $d->x ) || $d->y != 'r' || $d->sid < 1 )
            return null;

        $tnow = time();
        if ( $tnow < $d->t || $tnow > ( $d->t + $d->x ) )
            return null  ;


        return $d ;
    }

    public function validAccess() : ?object
    {
        $bearer = $_SERVER[ 'HTTP_AUTHORIZATION' ] ?? null;
        if ( !$bearer || !str_starts_with( $bearer , 'Bearer ' ) )
            return null;

        @ $d = (object)\unserialize( \sys\Crypto::decrypt( substr( $bearer , 7 ) , $_ENV[ 'A_TOKEN' ] ?? false ) ?? false );
        if ( !$d || !isset( $d->y , $d->sid , $d->pid , $d->t , $d->x ) || $d->y != 'a' || $d->sid < 1 )
            return null;

        $tnow = time();
        if ( $tnow < $d->t || $tnow > ( $d->t + $d->x ) )
        {
            header( 'Content-Type: application/json' );
            header( 'Cache-Control: no-cache, no-store, must-revalidate' );
            echo json_encode( (object)[ 'expired' => true ]) ;
            exit;
        }


        return $d;
    }


    // is bearer token valid access token
    // and has it given a valid project id for this user

    public function validUserProjectAccess( ) : ?object
    {
        try {

            $a = $this->validAccess();
            if ($a && is_object($a) && (($a?->sid ?? 0) > 0)) {
                $pid = $a->pid ?? 0;
                $db = $this->getProjectDB( $pid );
                if ( $db ) {
                    if ($pid !== 0 && $this->canUseProject($a->sid, $pid, $db)) {
                        $a->db = $db;
                        return $a;
                    }
                }
            }
        }
        catch( \Throwable $ex )
        {

        }
        return null ;
    }


    private function projetList( int $sid )
    {
        return \sys\db\SQL::GetAllN( <<<SQL
            SELECT s2.pid , p.db , p.title 
            FROM <DBM>.sys_sid2pid as s2
            INNER JOIN <DBM>.sys_projects as p ON (p.pid = s2.pid)
            WHERE s2.sid = ? AND p.active > 0 AND (p.from IS NULL OR p.from <= NOW() ) AND ( p.upto IS NULL OR p.upto >= NOW())
            ORDER BY p.title  
SQL, [ $sid ] ) ?? [];
    }

    private function getProjectDB( int $pid )
    {
        return \sys\db\SQL::Get0( " SELECT db FROM <DBM>.sys_projects WHERE pid = ?" , [ $pid ]) ;
    }

    private function canUseProject( int $sid , int $pid , ?string $db ) : bool
    {
        try
        {
            if ( $pid < 1 )
                return false;
            if ( !$db )
                $db = $this->getProjectDB( $pid );


            if ( empty( $db ) )
                return false;

            $allow = \sys\db\SQL::RowN( <<<SQL
            
    SELECT 1 FROM {$db}.users as u
        INNER JOIN {$db}.comps as c on ( c.cid = u.cid )
        WHERE u.sid = ? and u.active > 0 and c.active > 0

SQL, [ $sid ] );

            if ( $allow )
                return true;
        }
        catch ( \Exception $ex )
        {
            return false;
        }

        return false;
    }


    protected function activeProjects( int $sid )
    {
        $list = $this->projetList( $sid );
        if ( $list && is_array( $list ) )
        {
            $active = [];
            foreach ( $list as $prj )
            {
                if ( $this->canUseProject( $sid , $prj[ 'pid' ] ?? 0 , $prj[ 'db' ] ?? null ) )
                    $active[] = [ $prj[ 'pid' ] , $prj[ 'title' ] ?? $prj[ 'pid' ] ?? '?' ];
            }
            return $active;
        }

        return null;
    }


    protected function getUserProfile( int $sid , int $pid = 0 ) : ?array
    {
        $user = \sys\db\SQL::RowN( <<<SQL

    SELECT a.sid , a.active , a.username ,  a.email  , u.firstname , u.lastname , u.displayname , a.valid , a.expire 
        FROM <DBM>.sys_auth as a
        INNER join <DBM>.sys_users as u on ( a.sid = u.sid )
        WHERE a.sid = ? and a.active >= 0
    
SQL, [ $sid ] );

        if ( !$user || !is_array( $user ) || !empty( \sys\db\SQL::error() ) )
            return null;

        if ( empty( $user[ 'email' ] ) && str_contains( $user[ 'username' ] , '@' ) )
            $user[ 'email' ] = $a[ 'username' ] ?? '';

        if ( $pid < 1 )
            return $user ;
        else
        {
            $prj = \sys\db\SQL::RowN( <<<SQL

    SELECT p.pid , p.title , p.db , p.active  
        FROM <DBM>.sys_projects as p
        WHERE p.pid = ? and p.active >= 0
    
SQL, [ $pid ] );

            if ( !$prj || !is_array( $prj ) || !isset( $prj[ 'db' ] ) || !empty( \sys\db\SQL::error() ) )
                return null;

            $db    = $prj[ 'db' ];
            $local = \sys\db\SQL::RowN( <<<SQL

    SELECT u.sid , u.active , u.level , c.code , c.name , c.postcode 
        FROM {$db}.users as u
        INNER JOIN {$db}.comps as c on ( c.cid = u.cid )
        WHERE u.sid = ? and u.active >= 0
    
SQL, [ $sid ] );

            if ( !$local || !is_array( $local ) || !empty( \sys\db\SQL::error() ) )
                return null;

            return [ ...$user , ...$prj , ...$local ];
        }



    }


}