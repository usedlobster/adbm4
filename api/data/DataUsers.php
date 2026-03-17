<?php

namespace api\data;


class DataUsers extends DataHandler {


        public function getDC( $type ,$id = null   ) : \sys\db\DataSource | null
        {
            try {


                $dc = new \sys\db\DataSource()
                    ->col( 'u.level' )
                    ->col( 'u.sid' )
                    ->col( 'u.cid' )
                    ->col( 'c.code' , 'ccode' )
                    ->col( 'c.name' , 'cname' )
                    ->col( 'sa.username' , 'uname' )
                    ->col( 'su.firstname' , 'fname' )
                    ->col( 'su.lastname' , 'lname' )
                    ->col( 'su.displayname' , 'dname' )
                    ->col( 'sa.expire' , 'expire' )
                    ->col( 'sa.valid' , 'valid' )
                    // ->expr( '(SELECT GROUP_CONCAT(ur.roleid) FROM user_roles ur INNER JOIN roles r ON r.roleid = ur.roleid WHERE ur.sid = u.sid)', 'roles')
                    ->join('users' , 'u')
                    ->join('comps' , 'c' , '(c.cid = u.cid)')
                    ->join( '<DBM>.sys_auth' , 'sa' , '( sa.sid = u.sid )')
                    ->join( '<DBM>.sys_users' , 'su' , '( su.sid = u.sid )') ;

                $dc->order( ':' )
                    ->listfn( 'clist' )
                    ->close();


            }

            catch( \Throwable $ex ) {


            }

            return $dc ;
        }
        public function table($payload,$parts) {

            try {

                    $dc = $this->getDC( 'table' , false ) ;
                     if ( $dc )
                        return $this->getTable($dc, $payload);
            }
            catch( \Throwable $ex ) {
                return ['error'=>400 , 'ex'=> $ex->getMessage()] ;

            }

            return ['error' => 400 ];

        }




        public function form( $payload , $parts ) {
            try {


                $dc = $this->getDC( 'form' , $parts[1] ?? false  ) ;

                if ( $dc )
                    return $this->getFormData( $dc , $payload )  ;


            }
            catch( \Throwable $ex ) {
                return ['error' => 400 , 'ex'=> $ex->getMessage()];
            }

            return ['error' => 400 ];

        }




}