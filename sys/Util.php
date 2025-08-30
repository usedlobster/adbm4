<?php

    namespace sys;

    class Util
    {

        private static array $_redis = [];

        public static function generateRandomBytes($n)
        {
            $strong = false;
            if ( $n < 1 || (empty(($r = openssl_random_pseudo_bytes($n , $strong)))) || !$strong || mb_strlen($r , '8bit') !== $n )
                die('critical server failure');

            return $r;
        }


        public static function generateRandomCode() : string
        {
            $hex = bin2hex(openssl_random_pseudo_bytes(4));
            return strtoupper(substr($hex , 0 , 4).'-'.substr($hex , 3 , 4));
        }

        public static function generate8DigitCode() : string
        {
            // Get 4 bytes of random data (32 bits)
            $bytes = openssl_random_pseudo_bytes(4);

            // Convert to an unsigned 32-bit integer
            $num = unpack('N' , $bytes)[ 1 ];

            // To avoid modulo bias, we'll reject and retry if the number is too large
            // Maximum value that's divisible by 100000000 without bias
            $max = floor(0xFFFFFFFF / 100000000) * 100000000 - 1;

            while ( $num > $max )
            {
                $bytes = openssl_random_pseudo_bytes(4);
                $num = unpack('N' , $bytes)[ 1 ];
            }

            // Get last 8 digits
            $code = str_pad(($num % 100000000) , 8 , '0' , STR_PAD_LEFT);

            return substr($code , 0 , 4).'-'.substr($code , 4 , 4);
        }

        public static function getRedis(int $index = 0) : bool|\Redis
        {
            if ( $index < 0 )
                return false;

            $inst = self::$_redis[ $index ] ?? false;
            if ( !$inst || !($inst->isConnected()) )
            {
                $inst = new \Redis();
                $inst->connect($_ENV[ 'REDIS_HOST' ] ?? 'localhost' ,
                        $_ENV[ 'REDIS_PORT' ] ?? 6379 ,
                        $_ENV[ 'REDIS_TIMEOUT' ] ?? 5.0);
                if ( ($rpw = $_ENV[ 'REDIS_PASSWORD' ] ?? false) )
                    $inst->auth($rpw);
                $inst->select($index);
                self::$_redis[ $index ] = $inst;
            }

            return $inst;
        }


    }