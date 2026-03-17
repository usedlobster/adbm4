<?php

    namespace api\email;

    class SendEmail {

        public function __construct() {

        }

        /*

        public function sendToSid( $sid , $template , $data ) {

            $profile = \sys\db\SQL::RowN( <<<SQL
    select su.firstname,su.lastname,su.displayname,
       coalesce( sa.email , sa.username ) as email ,
       (su.active and sa.active >= 0 ) as active
    from <DBM>.sys_users su
    join <DBM>.sys_auth sa on ( sa.sid = su.sid )
    where su.sid = ?
SQL , [ $sid ] ) ;


            if ( is_array( $profile ))
                $tdata =  ( is_array( $data ) ? [ ...$profile , ...$data ] : $profile );
            else if ( is_array( $data ))
                $tdata = $data ;
            else
                return false ;

            $email = $tdata[ 'email' ] ?? '' ;
            $dname = $tdata[ 'displayname' ] ?? $email ?? '';
            if ( empty($email))
                return false ;


            \sys\wd\Email\SendEmail::sendTemplate( $email , $dname , $template , $tdata );








            echo '' ;
        }
        */
    }