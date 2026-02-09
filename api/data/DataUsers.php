<?php

namespace api\data;


class DataUsers extends DataHandler {


        use \api\auth\ApiAuthTrait;

        /*
         *             if ( ! isset( $user->db , $user->pid , $user->level ) )
                return null;
            // get format value for date_time function ,
            $date_time_format = '%d/%m/%y %H:%i';
            $dc = new SqlSource();
            $dc->db( $user->db )
               ->col( 'su.usr' , 'username' )
               ->col( 'sp.firstname' )
               ->col( 'sp.lastname' )
               ->col( 'sp.displayname' )
               ->col( 'sp.avinits' )
               ->col( 'u.level' )
               ->col( 'c.ctx' )
               ->col( 'c.name' , 'company' )
               ->expr( "coalesce( date_format(s2p.last,'$date_time_format') , '' )" , 'last'  )
               ->expr( 'if (su.active > 0 , 4 , 0 ) + if ( c.active > 0, 2 , 0  ) + if( u.active > 0 , 1 , 0)' , 'status' )
               ->col( 'su.sid' )
               ->join( 'users' , 'u' )
               ->join( 'ul_cat.profile' , 'sp' , 'sp.sid = u.sid' )
               ->join( 'ul_cat.sysusers' , 'su' , 'su.sid = sp.sid ' )
               ->join( 'comps' , 'c' , 'c.cid = u.cid' )
               ->join( 'ul_cat.sid2pid' , 's2p' , '(s2p.sid=u.sid and s2p.pid = ? ) ' )
               ->param( $user->pid )
               ->where( '( su.active >=0 and u.active >=0 and c.active >=0 )' )
               ->where( 'u.level < ? ' )
               ->param( $user->level )
               ->prepare();

            return $dc ;

        }




    }
         */

        public function getDC( $db )
        {
            try {
                $dc = new \sys\db\DataSource();

                $dc->db( $db )
                    ->col( 'u.level' )
                    ->col( 'u.sid' )
                    ->col( 'c.code' , 'ccode' )
                    ->col( 'c.name' , 'cname' )
                    ->col( 'sa.username' , 'uname' )
                    ->col( 'su.firstname' , 'fname' )
                    ->col( 'su.lastname' , 'lname' )
                    ->col( 'su.displayname' , 'dname' )
                    ->col( 'sa.expire' , 'expire' )
                    ->col( 'sa.valid' , 'valid' )
                    ->join('users' , 'u')
                    ->join('comps' , 'c' , '(c.cid = u.cid)')
                    ->join( 'roles' , 'r' , '( r.id = u.role )')
                    ->join( '<DBM>.sys_auth' , 'sa' , '( sa.sid = u.sid )')
                    ->join( '<DBM>.sys_users' , 'su' , '( su.sid = u.sid )')
                    ->where( 'u.level > 3 ')
                    ->order( ':' )
                    ->close();

            }
            catch( \Throwable $ex ) {


            }

            return $dc ;

        }
        public function table($payload,$parts) {

            try {

                if (($a = $this->validUserProjectAccess()) !== null) {
                    $dc = $this->getDC($a->db );

                    if ( $dc )
                        return $this->getTable($dc, $payload);
                }
            }
            catch( \Throwable $ex ) {

            }

            return ['error' => 400 ];

        }




}