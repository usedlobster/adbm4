<?php

namespace sys;

class Valid {

    public function __construct() {}

    static function email($email, $checkMX = false) : bool
    {

        // NB : technically an email can be > 128 but lets be sensible
        if (!is_string($email) || empty($email) || strlen($email) > 128 )
            return false;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            return false;

        // If required, validate existence of MX / A records
        if ($checkMX) {
            [$local, $domain] = explode('@', $email , 2 );
            // must have mx, or a record to be certain
            if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A'))
                return false;
        }

        return true;
    }

    static function username($username) : bool
    {
        if (!is_string($username) || empty($username))
            return false;
        // username must start with alpha , and be 4-50
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]{3,49}$/', $username))
            return false;
        return true;
    }

    static function account($account) : bool
    {
        return (\sys\Valid::username($account) || \sys\Valid::email($account));
    }

    static function password( $pwd ) : bool {
        return !( !is_string( $pwd ) || empty( $pwd ) || mb_strlen( $pwd ) > 150 ) ;
    }

    static function vCode( $vCode ) : bool {
        return !( !is_string( $vCode ) ||
                  empty( $vCode ) ||
                  mb_strlen( $vCode ) !== 64 ||
                  !preg_match( '/[0-9a-fA-F]{64}/', $vCode )
        ) ;
    }



}
