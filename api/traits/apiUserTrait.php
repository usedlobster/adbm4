<?php

namespace api\traits;

use sys\db\SQL;

trait apiUserTrait {


    // get given account , by username or email
    // and status >= active
    private function getUserAccount( ?string $account , int $active = 0 ) : ?object
    {
        $a = SQL::rowN( "SELECT * FROM <DBM>.sys_users WHERE active >= :active  AND  ( username = :account OR email = :email )" , [
            'account' => $account ,
            'email' => $account,
            'active'=> $active] ) ?? false ;
        if ( is_array($a) )
            return (object)$a ;

        return null ;
    }

    private function getAccountFromSid( ?int $sid , int $active = 0 ) : ?object
    {
        if ( $sid  && $sid > 0 ) {
            $a = SQL::rowN("SELECT * FROM <DBM>.sys_users WHERE active >= :active  AND  sid = :sid ", [
                'sid'    => $sid,
                'active' => $active
            ]) ?? false;
            if ( is_array($a) )
                return (object)$a;
        }

        return null ;
    }


    private function getTotpKey( int $sid ) : ?string
    {
        return SQL::Get0( " SELECT public_key FROM <DBM>.sys_auth_totp WHERE sid = :sid " , ['sid' => $sid] ) ?? null ;
    }

    private function changePassword( int $sid , string $pass ) : bool
    {
        $h = password_hash( $pass , PASSWORD_DEFAULT ) ;
        if ( !$h || empty($h) )
            return false ;

        if ( SQL::Exec( " UPDATE <DBM>.sys_users SET auth1 = ( auth1 & 3) | 2  WHERE sid = :sid " , ['sid' => $sid] ))
            if ( SQL::Exec( " REPLACE INTO <DBM>.sys_auth_php( sid , h ) VALUES( :sid , :h )" , ['sid' => $sid , 'h' => $h ] ))
                return true ;

        return false ;

    }

    private function checkPasswordMD5( int $sid , string $pass ) : bool
    {
       $h = SQL::Get0( " SELECT h FROM <DBM>.sys_auth_md5 WHERE sid = :sid " , ['sid' => $sid] ) ;
       if ( !$h || !empty( SQL::error()) || !(hash_equals( md5( $pass ) , $h )))
           return false ;
       // password matches, lets upgrade it forever .
       if ( $this->changePassword( $sid , $pass ))
           SQL::Exec( " DELETE FROM <DBM>.sys_auth_md5 WHERE sid = :sid " , ['sid' => $sid] ) ;


       return true ;


    }

    private function checkPasswordPHP( int $sid , string $pass ) : bool
    {
        $h = SQL::Get0( " SELECT h FROM <DBM>.sys_auth_php WHERE sid = :sid " , ['sid' => $sid] ) ;
        if ( !$h || !empty( SQL::error()) || !(password_verify( $pass , $h )))
            return false ;
        // password matches, check for rehash
        if ( password_needs_rehash( $h , PASSWORD_DEFAULT ) )
            return $this->changePassword( $sid , $pass ) ;



        return true ;


    }
    private function updateLastLogin( int $sid , int $pid ) : bool
    {
        return SQL::Exec( " UPDATE <DBM>.sys_sid2pid SET lu = NOW() WHERE sid = :sid AND pid = :pid " , ['sid' => $sid , 'pid' => $pid] ) ;
    }




}