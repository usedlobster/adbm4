<?php

namespace api\traits;

use sys\db\SQL;

trait apiProjectTrait {

    private function canUseProject(int $sid, int $pid , ?string $db = null  ) : bool
    {
        try {

            $red = \sys\Redis::getRedis(2);
            if ( $red ) {
                $rkey = 'can_use_prj:' . $sid . ':' . $pid;
                if ( $red->get($rkey) === '1' )
                    return true ;
            }

            $res = false ;


            $db ??= SQL::Get0(" select db from <DBM>.sys_projects where pid = ? ", [$pid]);
            if ($db)
                $res = SQL::Get0( <<<SQL
    SELECT EXISTS(
        SELECT 1
        FROM <DBM>.sys_sid2pid s2p
         join <DBM>.sys_projects sp on sp.pid = s2p.pid
         join {$db}.users u on u.sid = s2p.sid
         join {$db}.comps c on c.cid = u.cid
        WHERE (s2p.active > 0 )  AND 
            ( s2p.sid = ? and s2p.pid = ? ) AND 
            sp.active > 0 AND
            u.active > 0 AND
            c.active > 0 AND    
            ( sp.valid is null or now() >= sp.valid ) AND
            ( sp.expire is null or now() < sp.expire )
    ) as ok;
SQL, [$sid, $pid]) ?? false;
        }
        catch (\Throwable $ex) {
            error_log($ex);
        }

        if ( $red )
            $red->setex( $rkey , 120 ,  ($res ? '1' : '0')  );

        return $res ;
    }

    private function getProjectList(int $sid) : array | false
    {
        return SQL::GetAllN(<<<SQL
            select s2.pid ,p.title ,p.db from <DBM>.sys_sid2pid as s2
            join <DBM>.sys_projects as p on ( p.pid = s2.pid )
                where s2.sid = ? and s2.active > 0 and p.active > 0  
            ORDER BY COALESCE(s2.lu, p.sos, p.modified) DESC, p.pid DESC
SQL, [$sid]);

    }

    private function availableProjects( int $sid ) : array
    {
        $rawList = $this->getProjectList( $sid ) ;
        $aList = [] ;
        if ( is_array( $rawList )) {
            foreach ($rawList as $prj) {
                if ($this->canUseProject($sid, $prj['pid'] , $prj['db'] ))
                    $aList[] = ['id'=>(int)$prj['pid'], 'name'=>(string) $prj['title'] ?? '' ] ;
            }
        }
        return $aList ;
    }

    private function getAnotherProject( int $sid , int $pid  ) : int
    {
        // check obvious request first
        if ( $pid > 0 && $this->canUseProject($sid, $pid))
            return $pid ;

        $aList = $this->availableProjects( $sid ) ;
        if ( $aList && is_array($aList) && count($aList) > 0 )
            return $aList[0]['id'] ?? -1 ;

        return -1 ;
    }







}