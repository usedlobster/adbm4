<?php

namespace sys;

class Clean {

    private static ?\HTMLPurifier $_purifier = null;

    static function totp($in) : ?string
    {
        if (!is_string($in) || $in === '' || strlen($in) > 64)
            return null;

        if (preg_match('/\p{L}/u', $in))
            return null;

        $digits = preg_replace('/[^0-9]/', '', $in);

        return strlen($digits) === 6 ? $digits : null;
    }

    public static function html(string $html) : string
    {
        try {
            if (self::$_purifier === null) {
                $config = \HTMLPurifier_Config::createDefault();
                $config->set('Cache.SerializerPath', '/app/htmlpurifier-cache');
                $config->set('HTML.Allowed', '' ) ; // 'div[style],span[style],p[style],b,strong,i,em,u,a[href|title],ul,ol,li,br,h1,h2,h3,blockquote,img[src|alt|width|height]');
                $config->set('URI.AllowedSchemes', [
                    'https' => true,
                ]);

                self::$_purifier = new \HTMLPurifier($config);
            }

            return self::$_purifier->purify($html);
        }
        catch( \Throwable $ex ) {
            error_log( $ex->getMessage() );
            return '' ;
        }
    }

}