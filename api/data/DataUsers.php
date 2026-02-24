<?php

namespace api\data;


class DataUsers extends DataHandler {


        use \api\auth\ApiAuthTrait;

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