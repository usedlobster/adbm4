<?php

namespace sys;

class Valid {

    public function __construct() {}

    static function otp($otp)
    {
        if (!is_string($otp) || empty($otp) || strlen($otp) !== 9)
            return false;
        if (!preg_match('/^[1-9A-HJ-NPRSTVWXYZ]{4}-[1-9A-HJ-NPRSTVWXYZ]{4}$/', strtoupper($otp)))
            return false;

        return true;
    }

    static function otp8($otp)
    {
        if (!is_string($otp) || empty($otp) || strlen($otp) !== 8)
            return false;
        if (!preg_match('/^[1-9A-HJ-NPRSTVWXYZ]{4}[1-9A-HJ-NPRSTVWXYZ]{4}$/', strtoupper($otp)))
            return false;

        return true;
    }

    static function email($email, $checkMX = false)
    {

        // NB : technically an email can be > 128 but lets be sensible
        if (!is_string($email) || empty($email) || strlen($email) > 128 )
            return false;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            return false;

        // If required, validate existence of MX / A records
        if ($checkMX) {
            [$local, $domain] = explode('@', $email , 2 );
            // must have mx , or a record to be certain
            if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A'))
                return false;
        }

        return true;
    }

    static function username($username)
    {
        if (!is_string($username) || empty($username))
            return false;
        // username must start with alpha, and be 3-50
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]{3,50}$/', $username))
            return false;
        return true;
    }



    static function account($account) : bool
    {
        return (\sys\Valid::username($account) || \sys\Valid::email($account));
    }

}
