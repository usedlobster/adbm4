<?php

    namespace sys\validate;

    class Valid
    {

        static function phone($phone) {
            if ( !is_string($phone) || empty($phone) )
                return false;

            if ( !preg_match('/^\+?[0-9\-\(\) ]+$/', $phone) )
                return false;

            return true;
        }

        static function username( $username ) {
            if ( !is_string($username) || empty($username) )
                return false;
            if ( !preg_match('/^[a-zA-Z0-9_-]{3,16}$/', $username) )
                return false;
            return true;
        }

        static function otpcode( $code )  {
            if ( !is_string($code) || empty($code) )
                return false;
            if ( !preg_match('/^[a-h1-9]{4}-[a-h1-9]{4}$/', strtolower($code) ) )
                return false;
            return true ;
        }

        static function email($email , $checkMX = false)
        {

            if ( !is_string($email) || empty($email) )
                return false;


            if ( !filter_var($email , FILTER_VALIDATE_EMAIL) )
                return false;

            // If required, validate existence of MX records
            if ( $checkMX )
            {
                [$local , $domain] = explode('@' , $email);
                if ( !checkdnsrr($domain , 'MX') && !checkdnsrr($domain , 'A') )
                    return false;
            }

            return true;
        }

    }

