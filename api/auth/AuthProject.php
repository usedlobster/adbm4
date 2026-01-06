<?php

    namespace api\auth;

    use api\auth\AuthBase;

    class AuthProject extends AuthBase {


        private function canUseProject($sid , $pid , $db) : bool
        {

            if ( $sid > 0 && $pid > 0 )
            {
                $db = $db ?: \sys\db\SQL::Get0(" select db from <DB>.sys_projects where pid = ? and active > 0 " , [$pid]);
                if ( $db && \sys\db\SQL::Get0(/** @lang */
                                <<<SQL
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
SQL, [$sid , $pid]) === $sid ) {

                    return true;
                }
            }

            return false;
        }


        protected function getProjectList( $sid ) : array {

            $all = \sys\db\SQL::GetAllN( /** @lang sql */ <<<SQL
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



            $available = [] ;
            foreach ($all as $p) {
                if ( $this->canUseProject($sid , $p['pid']  , $p['db'] ) )
                    $available[] = (object)$p;
            }


            return $available ;
        }

        public function apiListProjects( $payload ) : object |  null
        {
            $a = \api\auth\AuthToken::validAccess( );
            if ( $a )
                return (object) ['projects'=>$this->getProjectList( $a->sid )];



            return null ;
        }





    }

