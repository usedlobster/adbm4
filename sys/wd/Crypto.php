<?php

    namespace sys\wd;

    class Crypto {

        public static function encrypt(string $message, string $ekey): string
        {

            try
            {
                $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
                return sodium_bin2base64( $nonce . sodium_crypto_secretbox($message , $nonce , $ekey) , SODIUM_BASE64_VARIANT_URLSAFE ) ;

            }
            catch (\Throwable $ex )
            {

                error_log($ex);
            }
            return '' ;
        }

        public static function decrypt(string $encrypted, string $ekey): string
        {
            try
            {

                $decoded = sodium_base642bin($encrypted, SODIUM_BASE64_VARIANT_URLSAFE);
                $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
                $ciphertext = mb_substr($decoded,  SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
                $plain = sodium_crypto_secretbox_open($ciphertext, $nonce, $ekey );
                return $plain;
            }
            catch (\Throwable $ex )
            {
                error_log($ex);
            }


            return '' ;
        }






    }