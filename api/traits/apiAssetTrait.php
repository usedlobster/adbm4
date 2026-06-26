<?php

namespace api\traits;

/*
 * create table adbm4_master.sys_filemap
(
    id       int auto_increment
        primary key,
    d        varchar(50)                        null,
    name     varchar(50)                        null,
    x        varchar(10)                        null,
    v        int      default 0                 not null,
    h        char(24) collate latin1_bin        null,
    active   tinyint  default 1                 not null,
    modified datetime default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP
);

create index idx_sys_filemap_lookup_latest
    on adbm4_master.sys_filemap (d asc, name asc, active asc, v desc);


 */

trait apiAssetTrait
{

    public function findAsset(int $pid, string $d, string $name, string $x) : array | false
    {
        $a = \sys\db\SQL::RowN(<<<SQL
 SELECT concat( '/', pid , d , '/' , name , '_' , h , x ) as src  , v , alt 
FROM adbm4_master.sys_filemap
WHERE 
      pid = ?
  AND d = ?
  AND name = ?
  AND x = ?
  AND active = 1  
ORDER BY v DESC, id DESC
LIMIT 1;  
SQL,
            [$pid , $d, $name, $x]) ?? false;
        if ( $a )
            return $a ;

        // try pid = 0 ( if not already )
        if ( $pid !== 0 && empty( \sys\db\SQL::error()))
            return $this->findAsset(0, $d, $name, $x) ;

        return false;
    }

}
