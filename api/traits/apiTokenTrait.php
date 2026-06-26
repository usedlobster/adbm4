<?php

namespace api\traits;

use sys\Crypto;

trait apiTokenTrait {

    // messed up for debug purposes
    private const int ACCESS_TOKEN_LIFE  = 1800 ;
    private const int REFRESH_TOKEN_LIFE = 3600 ;
    private const int SAVE_LOGIN_LIFE    = 3600 * 24 ;

    private function genLoginTokens( int $sid , int $pid , bool $remember = false ) : ?object
    {
        try {

            // generate 12 character random id, technicaly should
            // check db for uniqueness but i like the odds
            //
            $h = base64_encode( \random_bytes(9 ));
            $t = time();
            $adata = (object)[
                'y'   => 'a',
                'h'   => $h ,
                't'   => $t ,
                'iss' => $t ,
                'x'   => self::ACCESS_TOKEN_LIFE,
                'sid' => $sid ,
                'pid' => $pid , // for hint only, as may have changed
            ];
            $atkn = Crypto::encrypt(\serialize($adata), $_ENV['A_TOKEN'] ?? false) ?? false;
            if (!$atkn)
                return null;

            $rdata = (object)[
                'y'   => 'r',
                'h'   => $h ,
                't'   => $t,
                'sid' => $sid,
                'x'   => self::REFRESH_TOKEN_LIFE,
            ];

            $rtkn = Crypto::encrypt(\serialize($rdata), $_ENV['R_TOKEN'] ?? false) ?? false;
            if (!$rtkn)
                return null;

            if ( $remember ) {
                $mdata = (object)[
                    'y'   => 'm',
                    'h'   => $h ,
                    'sid'   => $sid ,
                    't'   => $t,
                    'x'   => self::SAVE_LOGIN_LIFE
                ] ;

                $mtkn = Crypto::encrypt(\serialize($mdata), $_ENV['M_TOKEN'] ?? false) ?? false;

            }

// record date tokens created
//            if ( \sys\db\SQL::Exec( "replace into <DBM>.sys_tokens( sid , h , iss ) values ( ? , ? , now())",
//                [$sid , $h ]) === false ) {
//                return null ;
//            }


            return (object)[
                'sid'  => $sid,
                'pid'  => $pid ,
                'atkn' => $atkn,
                'rtkn' => $rtkn,
                'mtkn' => $mtkn ?? null
            ];
        }
        catch (\Throwable $ex) {
        }

        return null;
    }


    // decode generic token
    private function decodeToken( string $tkn , string $key , string $exptype  ) : ?object
    {
        // tkn / key already strings
        if ( empty( $tkn ) || empty($key ) || mb_strlen($tkn) > 512 || mb_strlen($tkn) < 50 )
            return null ;

        $dstr  = Crypto::decrypt( $tkn, $key ) ?? false  ;
        if ( !$dstr )
            return null ;
        $d = @unserialize($dstr) ?? null ;
        if ( !$d || !is_object($d) || !isset($d->t,$d->h, $d->x,$d->y,$d->sid))
            return null ;
        if ( $d->y !== $exptype )
            return null ;
        // check if expired
        if ( !(\sys\Util::recentTime($d->t , $d->x , 2 )))
            return null ;
        return $d ;

    }

    private function tokenRevoked ( ?object $t ) : bool {
        if ( !$t || !isset($t->sid,$t->h , $t->t ) )
            return true ;
        // check if token or user has been revoked
        return (bool) \sys\db\SQL::Get0("SELECT sid FROM <DBM>.sys_revoked WHERE iss IS NOT NULL AND sid = ? AND ( iss > ? ) AND ( h = '' OR h = ? ) LIMIT 1 ",
            [$t->sid , $t->t , $t->h ]) ?? 0  ;
    }
    private function decodeAccessToken( string $atkn ) : ?object
    {
        try {
            $t = $this->decodeToken( $atkn , $_ENV['A_TOKEN'] ?? false , 'a' ) ;
            if ( !is_object($t) || !isset($t->sid, $t->pid ) || $this->tokenRevoked($t))
                return null ;
            return $t ;
        }
        catch( \Throwable $ex ) {
            // catch dont log ( sensitive )

        }

        return null ;
    }

    private function decodeRefreshToken( string $rtkn ) : ?object
    {
        try {
            $t = $this->decodeToken( $rtkn , $_ENV['R_TOKEN'] ?? false , 'r' ) ;
            if ( !is_object($t) || !isset($t->sid) || $this->tokenRevoked($t))
                return null ;
            return $t ;
        }
        catch( \Throwable $ex ) {
            // catch dont log ( sensitive )

        }

        return null ;
    }

    private function decodeMemoryToken( string $mtkn ) : ?object
    {
        try {
            $t = $this->decodeToken( $mtkn , $_ENV['M_TOKEN'] ?? false , 'm' ) ;
            if ( !is_object($t) || !isset($t->h) || !isset($t->sid) || $this->tokenRevoked($t))
                return null ;
            // we have valid token , check if not revoked
            if ( $this->tokenRevoked($t))
                return null ;
            return $t ;
        }
        catch( \Throwable $ex ) {
            // catch dont log ( sensitive )

        }

        return null ;
    }

}