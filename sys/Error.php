<?php

    namespace sys;

    class Error
    {

        private static string $_lang   = 'gb';
        private static ?array $_errors = null;

        public function __construct()
        {
            self::$_lang   = 'gb';
            self::$_errors = null;
        }

        private static function loadErrors()
        {
            $file = __DIR__.'/errors/' . self::$_lang . '/errorlist.csv';
            if ( !file_exists($file) || !is_readable($file) )
                return;

            $fp = fopen($file , "r");
            try
            {
                if ( $fp === false )
                    return ;

                while ( ($line = fgetcsv($fp,null,',',"\"" , "\\" )) !== false )
                {
                    $code = $line[ 0 ] ?? false;
                    if ( is_numeric($code) )
                    {
                        $code = (int)$code;
                        $msg  = str_replace('\n' , "\n" , $line[ 1 ]);
                        if ( is_string($msg) && !empty($msg) )
                            self::$_errors[ $code ] = trim($msg);
                    }
                }
            }
            finally
            {
                if ( isset($fp) && is_resource($fp) )
                    fclose($fp);
            }
        }

        public static function msg( int | string  $code , ?array $vars = null) : string
        {
            try
            {
                if ( is_string($code)  && !is_numeric($code))
                    return $code ;

                if ( !self::$_errors )
                    self::loadErrors();

                $err = self::$_errors[$code] ?? '#'.$code ;
                if (str_contains( $err, '?')) {

                    $nVars = is_array($vars) ? count($vars) : 0 ;
                    if ($nVars === 0)
                        return $err;

                    $parts = preg_split("/\?(\d)/", $err, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
                    $msg = $code ;
                    for ($i = 0; $i < count($parts); $i += 2) {
                        $msg .= $parts[$i] ?? '';
                        if (isset($parts[$i + 1]))
                            $msg .= $vars[($parts[$i + 1] ?? 0) % $nVars] ?? '';
                    }

                    return $code . ':' . $msg;

                }

                 return $code .':' . $err ;
            }
            catch ( \Throwable $e )
            {
                return $e->getMessage() ;
            }

            return '?' ;
        }


    }

    /*
     * <?php

    namespace sys\lang;

    class Error {



        public function __construct() {
            self::$_errors = null ;
            self::$_lang = 'gb' ;
        }


//        private static function loadErrors()
//        {
//            try
//            {
//                if ( is_null(self::$_errors) )
//                {
//                    $file = __DIR__.'gb/errors.csv';
//                    if ( !file_exists($file) || !is_readable($file) )
//                        return;
//
//                    $fp = fopen($file , "r");
//                    if ( $fp === false )
//                        throw new \RuntimeException("Unable to open error codes file {$file}.");
//
//                    while ( ($line = fgetcsv($fp)) !== false )
//                    {
//                        $code = $line[ 0 ] ?? false;
//                        if ( is_numeric($code) )
//                        {
//                            $code = (int)$code;
//                            $msg  = str_replace('\n' , "\n" , $line[ 1 ]);
//                            if ( is_string($msg) && !empty($msg) )
//                                self::$_errors[ $code ] = trim($msg);
//                        }
//                    }
//                }
//            }
//            finally {
//                    if ( isset($fp) && is_resource($fp) )
//                        fclose($fp);
//                }
//            }


//        public static function msg( $result , ?array $data = null ): string
//        {
//            if ( self::$_errors === null )
//                self::loadErrors();
//
//            if ( is_array($result))
//                return self::msg((object)$result, $data ) ;
//            else if (!is_object($result) ) {
//                if ( is_string($result) || is_int($result))
//                    return self::msg((object)['error' => $result, 'data' => $data], null);
//                else
//                    return '?' ;
//            }
//
//            if ( isset($result->expired ))
//                return 'expired' ;
//
//            if ( !isset($result->error ))
//                return 'error' ;
//
//            $msg = self::$_errors[$result->error] ?? '#'.$result->error  ;
//            if (str_contains( $msg, '?'))
//                $msg = self::replaceWithVars( $msg , array_merge($result->data ?? [] , $data ?? [] )) ; ;
//
//            // append any debug message when in development
//            if ( _DEV_MODE && isset($result->debug))
//                $msg .= '<br />' . print_r($result->debug ?? '', true);
//
//            return $msg;
//        }
//
//

        private static function loadErrors() {

        }

        public function msg( int | string $code , ?array $data = null  ) {

            if ( is_numeric($code) && $code > 100 ) {
                if ( self::$_errors === null )
                    self::loadErrors();
            }

        }

    }
     */