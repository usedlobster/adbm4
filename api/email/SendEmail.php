<?php

    namespace api\email;

    class SendEmail {


        use \api\auth\ApiAuthTrait;
        public function __construct() {

        }


        public function send( $sid , $template , $data ) {

            $profile = $this->getUserProfile( $sid , 0) ;
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

//            \sys\wd\SendEmail::sendTemplate( $email , $dname , $template , $tdata );
            \sys\wd\Email\SendEmail::sendTemplate( $email , $dname , $template , $tdata );








            echo '' ;
        }
    }