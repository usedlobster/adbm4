<?php

    namespace sys;

    class Valid
    {


        public function __construct() {}

        static function username( $username )
        {
            if ( !is_string( $username ) || empty( $username ) )
                return false;
            if ( !preg_match( '/^[a-zA-Z][a-zA-Z0-9_-]{3,50}$/' , $username ) )
                return false;
            return true;
        }

        static function otp( $otp )
        {
            if ( !is_string( $otp ) || empty( $otp ) )
                return false;
            if ( !preg_match( '/^[1-9A-HJ-NPRSTVWXYZ]{4}-[1-9A-HJ-NPRSTVWXYZ]{4}/' , strtoupper( $otp ) ) )
                return false;

            return true;
        }

        static function email( $email , $checkMX = false )
        {
            if ( !is_string( $email ) || empty( $email ) )
                return false;


            if ( !filter_var( $email , FILTER_VALIDATE_EMAIL ) )
                return false;

            // If required, validate existence of MX / A records
            if ( $checkMX )
            {
                [ $local , $domain ] = explode( '@' , $email );

                if ( !checkdnsrr( $domain , 'MX' ) && !checkdnsrr( $domain , 'A' ) )
                    return false;
            }

            return true;
        }

        static function account( $account ) : bool
        {

            return ( !is_numeric( $account ) &&
                is_string($account) &&
                !empty( $account ) &&
                ( self::username( $account ) || self::email( $account ) ) );
        }


    }