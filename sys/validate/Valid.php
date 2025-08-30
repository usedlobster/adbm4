<?php

    namespace sys\validate;

    class Valid
    {

        static function cleanEmail($email)
        {
            if ( is_string($email) && !empty($email) )
            {
                $email = trim($email);
                $parts = explode('@' , $email);
                if ( isset($parts[ 1 ]) )
                {
                    $parts[ 1 ] = strtolower($parts[ 1 ]);  // Lowercase domain part
                    return implode('@' , $parts);
                }
            }

            return $email;
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

