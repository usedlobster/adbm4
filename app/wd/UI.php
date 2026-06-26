<?php

namespace app\wd;

class UI {


    private static function lookupNote( string | int $note , ?string $hint ) : array
    {
        return match ($note) {
            'account'       => [ 'Account  (i)', 'Your account name, which will usually be your email address'],
            'password'      => [ 'Password (i)' , 'Your password , never share it with anyone'],
            'rem'           => [ 'Remember (i)' , 'Keep signed in , on this device for upto 7 days.Only use on devices you own' ] ,
            default => ['(i)', $hint]
        };
    }


    private static function makeInfoButton( ?string $hint ) {

        if ( !empty($hint ))
            return "<button class='hint text-blue-500' aria-hidden='true' title='{$hint}'><sup>&#9432;</sup></button>";

        return '' ;
    }

    private static function fmtNote( string | int | null $noteid , ?string $hint = null   ) : string {
        $note = self::lookupNote( $noteid , $hint ) ?? null ;
        $label = $note[0] ?? $noteid ?? '(i)' ;
        $hint  = $hint ?:  ($note[1] ?? '') ;
        $button = self::makeInfoButton( $hint ) ;
        return str_replace( '(i)' , $button , $label )  ;
    }

    public static function note( string $code ) {
        echo self::fmtNote( $code , false );
    }
    public static function label( string $id , string | int  $noteid , ?string $hint = null) : void
    {
        $out = self::fmtNote( $noteid , $hint ) ;
        if ( !empty($out))
            echo "<label class=\"wd-label\" for={$id}>{$out}</label>" ;
    }

    public static function errorText( string $msg )
    {
        return '<span class="text-xl text-red-500">' . htmlspecialchars($msg) .  '</span>';
    }


}