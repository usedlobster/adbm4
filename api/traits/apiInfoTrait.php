<?php

namespace api\traits;

use sys\db\SQL;

trait apiInfoTrait
{
    use apiAssetTrait;

    // info - constrained database queries not to give
    // end user more information them they need, like passwor hashes
    // or option flags , permission ids etc , unless needed for ui specific reasons

    private function infoSystemUser(int $sid) : ?array
    {
        $a = SQL::rowN("SELECT sid,username,email,firstname,lastname,displayname FROM <DBM>.sys_users WHERE sid = :sid AND active > 0 ",
            ['sid' => $sid]) ?? false;
        if ($a && is_array($a) && empty(SQL::error()))
            return $a;
        return null;
    }

    private function infoProject(int $pid) : ?array
    {
        $a = SQL::RowN(" SELECT pid,title,db,expire,popts,mods FROM <DBM>.sys_projects WHERE pid = :pid AND active > 0  ", ['pid' => $pid]);
        if ($a && is_array($a) && empty(SQL::error()))
            return $a;
        return null;
    }

    private function infoUser(int $sid, int $pid, ?string $db = null) : ?array
    {
        $db ??= SQL::Get0(" select db from <DBM>.sys_projects where pid = ? ", [$pid]);
        if (empty($db))
            return null;
        $a = SQL::RowN(" select sid,cid,level from {$db}.users where sid = ? and active > 0 ", [$sid]);
        if ($a && is_array($a) && empty(SQL::error()))
            return $a;
        return null;
    }

    private function infoCompany(int $cid, int $pid, ?string $db = null) : ?array
    {
        $db ??= SQL::Get0(" select db from <DBM>.sys_projects where pid = ? ", [$pid]);
        if (empty($db))
            return null;

        $a = SQL::RowN(" select code,name,postcode from {$db}.comps where cid = ? and active > 0 ", [$cid]);
        if ($a && is_array($a) && empty(SQL::error()))
            return $a;
        return null;
    }

    private function infoGroups(int $cid, int $sid, int $pid, ?string $db = null) : ?array
    {
        $db ??= SQL::Get0(" select db from <DBM>.sys_projects where pid = ? ", [$pid]);
        if (empty($db))
            return null;

        $a = SQL::Col(<<<SQL
select distinct name from {$db}.groupList as gl
    join {$db}.groupMap as gm on ( gm.gid = gl.gid )
    where gm.active > 0 and gl.active > 0 and ( gm.cid = :cid ) or ( gm.sid = :sid )
    order by name

SQL,
            0,
            ['cid' => $cid, 'sid' => $sid]);

        if ($a && is_array($a) && empty(SQL::error()))
            return $a;
        return null;
    }

    private function infoRoles( array $user, array $proj , ?string $db = null  ) : ?array
    {
        $db  = $db ?: $proj['db'] ?? false ;
        $sid = $user['sid'] ?? false ;
        $lvl = $user['level'] ?? false ;
        $mods = $proj['mods'] ?? 0 ;

        $a = \sys\db\SQL::Col( <<<SQL
select coalesce( rl.altname , sr.name , '?')  from {$db}.roleMap as rm
         join {$db}.roleList as rl on ( rl.roleid = rm.roleid   )
         join <DBM>.sys_roles as sr on ( sr.roleid = rl.roleid )
where rm.sid = ? and
      rm.active = 1 and     
      rl.active = 1 and
      sr.active = 1 and
      sr.lvl <= ? and
      sr.mods = 0 or ( sr.mods and ? ) <> 0
SQL , 0 ,[ $sid , $lvl , $mods ] ) ;


        if ( is_array($a) && empty(SQL::error()))
            return $a;

        return [] ;
    }

    private function infoAll(int $sid, int $pid) : ?object
    {
        // TODO : cache ?

        // get user system profile
        $prof = $this->infoSystemUser($sid);
        if (!$prof)
            return null;

        // get project info from (pid)
        $proj = $this->infoProject($pid);
        if (!$proj)
            return null;

        //
        $db = $proj['db'] ?? \sys\db\SQL::Get0(" select db from <DBM>.sys_projects where pid = ? AND active > 0  ", [$pid]);
        if ( empty($db))
            return null ;

        // get local user info
        $user = $this->infoUser($sid, $pid, $db);
        if (!$user )
            return null ;

        // get local company info
        $cid = $user['cid'] ?? 0 ;
        $comp = $this->infoCompany($cid, $pid, $db);
        if ( !$comp )
            return null ;

        // get group names for local user / company
        $grps = $this->infoGroups( $cid , $sid , $pid , $db ) ;
        if ( !is_array($grps ))
            return null ;

        $roles = $this->infoRoles( $user , $proj, $db  ) ;
        if ( !is_array($roles ))
            return null ;
        // upto 3 logos
        $logos = [] ;
        for ( $i = 1 ; $i < 3 ; $i ++ )
        {
            $a = $this->findAsset(  $pid ,  '/img' , "logo$i" ,  '.png' ) ;
            if ( is_array($a))
                $logos[] = (object)$a ;
        }

        $m = array_merge( $prof , $proj , $user , $comp , [
            'groups'=>join( ',' , $grps  ),
            'roles' =>join( ',' , $roles ),
            'logos' => $logos ,
            'allow' => 3 ,
        ] ) ;


        return $m ? (object)$m : null ;
    }

}
