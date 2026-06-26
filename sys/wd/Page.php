<?php

namespace sys\wd;

class Page {

    private static function get_dim( $arg ) : ?array {

        if ( !is_string( $arg )) {
            if ( is_numeric( $arg ))
                $arg = (string)$arg ;
            else
                return null ;
        }
        // should be string now
        $arg = trim( strtolower( $arg )) ;
        if ( preg_match( "/^(\d+)\s*(px|%)?$/" , $arg , $m )) {
            $value = (int)($m[1] ?? 0 ) ;
            $unit = (string)($m[2] ?? '') ;
            return [ $value , $unit ];
        }
        return null ;
    }

    private static function getSize( ?array $u  , int $maxSize ) : string  {
        if ( is_null($u) || !isset( $u[0] , $u[1] ))
            return '' ;
        $v = $u[0]  ;
        if ( $u[1] === 'px' && $v > $maxSize || $u[1] === '%' && $v > $maxSize )
            return '100%' ;

        return $u[0] . ($u[1] ?: 'px' ) ;

    }
    public static function userMap( $args , $root , $id , $view  ) {
        $e = [] ;
        $p = 0 ; // positional args ( when not named ' )
        // name,zx,zy,
        $name = $id . ( $args['name'] ?? $args[$p++] ?? 'map' )  ;
        $zx   = self::get_dim( $args['zx'] ?? $args[$p++] ?? null , true ) ;
        $w    = self::getSize( $zx , 1600) ;
        if ( empty( $w ))
            $e[] =  'missing/bad width ' ;
        $zy   = self::get_dim( $args['zy'] ?? $args[$p++] ?? null , true ) ;
        $h    = self::getSize( $zy , 1200  ) ;
        if ( empty( $h ))
            $e[] = 'missing/bad height' ;





        // get other parameters




        //$zy   = min( max( 30 , $args['zy'] ?? $args[2] ?? 30 ) , 1500 ) ;
        // return '<div class="border-2 border-blue-500" style="width:' . $zx . 'px;height:' . $zy . 'px"></div>' ;

    }

}