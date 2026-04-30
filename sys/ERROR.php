<?php

namespace sys;

final class ERROR
{

    private static string $_lang = 'gb';
    private static ?array $_errors = null;

    // generic api errors
    const int API_FAILED                = 800;
    const int API_PARAM                 = 801;
    const int API_RATE                  = 802;
    const int API_BUSY                  = 805;
    const int API_EXCEPT                = 897;
    const int API_MISSING               = 898;
    const int API_CRASH                 = 899;
    // generic login errors
    const int LOGIN_FAILED              = 900;
    const int LOGIN_MFA_UNKNOWN         = 901;
    const int LOGIN_MFA_CODE_FMT        = 902;
    const int LOGIN_MFA_CODE_BAD        = 903;
    const int LOGIN_MFA_CODE_OK         = 904;
    const int LOGIN_MFA_REG_FAILED      = 905;
    const int LOGIN_MFA_REG_ALREADY     = 906;

    const int LOGIN_NEED_LOGIN          = 999;

    public function __construct()
    {
        self::$_lang = 'gb';//
        self::$_errors = null;
    }

    private static function loadErrors()
    {
        $file = __DIR__.'/errors/'.self::$_lang.'_errors.csv';
        if (!file_exists($file) || !is_readable($file))
            return;

        $fp = fopen($file, "r");
        try {
            if ($fp === false)
                return;

            while (($line = fgetcsv( $fp, null,  ',' , '"' ,  ''  )) !== false) {
                $code = trim($line[0] ?? false);
                if (!str_starts_with($code, '#')) {
                    if (!is_numeric($code)) {
                        if (defined('sys\\Error::'.$code))
                            $code = constant('sys\\Error::'.$code);
                    }

                    if (is_numeric($code)) {
                        $code = (int)$code;
                        $msg = str_replace('\n', "\n", $line[1]);
                        if (is_string($msg) && !empty($msg))
                            self::$_errors[$code] = trim($msg);
                    }

                }
            }
        }
        finally {
            if (isset($fp) && is_resource($fp))
                fclose($fp);
        }
    }

    public static function msg(int | string $code, ?array $vars = null) : string
    {
        try {
            if (!self::$_errors)
                self::loadErrors();

            $err = is_numeric($code) ? (self::$_errors[$code] ?? '#'.$code.'#') : $code;
            if (str_contains($err, '?') && $vars && count($vars) > 0) {
                $nVars = is_array($vars) ? count($vars) : 0;
                if ($nVars === 0)
                    return $err;

                $parts = preg_split("/\?(\d)/", $err, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
                $err = '';
                for ($i = 0; $i < count($parts); $i += 2) {
                    $err .= $parts[$i] ?? '';
                    if (isset($parts[$i + 1]))
                        $err .= $vars[($parts[$i + 1] ?? 0) % $nVars] ?? '';
                }
            }

            return $err;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

}
