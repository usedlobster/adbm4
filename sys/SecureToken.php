<?php

    namespace sys;

    class SecureToken
    {

        public static function encrypt(string $message, string $ekey): string|bool
        {
            try
            {
                // Get cached key from session or decode from env and cache it
                $k = ($_SESSION['_K' . $ekey] ?? false) ?: ($_SESSION['_K'.$ekey] = base64_decode($_ENV[$ekey] ?? ''));
                $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
                return sodium_bin2base64( $nonce . sodium_crypto_secretbox($message , $nonce , $k) , SODIUM_BASE64_VARIANT_URLSAFE ) ;
            }
            catch (\Throwable )
            {
            }
            return false ;
        }

        /**
         * Decrypts an encrypted message using a given key.
         *
         * @param  string  $encrypted  The encrypted message to be decrypted.
         * @param  string  $key  The encryption key.
         *
         * @return  string|bool  The decrypted message, or false if decryption failed.
         *
         */
        public static function decrypt(string $encrypted, string $ekey): string|bool
        {
            try
            {
                $k = ($_SESSION['_K' . $ekey] ?? false) ?: ($_SESSION['_K'.$ekey] = base64_decode($_ENV[$ekey] ?? ''));
                $decoded = sodium_base642bin($encrypted, SODIUM_BASE64_VARIANT_URLSAFE);
                $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
                $ciphertext = mb_substr($decoded,  SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
                $plain = sodium_crypto_secretbox_open($ciphertext, $nonce, $k );
                return $plain;
            }
            catch (\Throwable )
            {
            }

            return false ;
        }

        // secure as in digits are well distributed , so unlikely guessable !
        public static function SecureCode( ) : string {
            $chars = '123456789ABCD';
            $bytes = random_bytes(8);
            $code = '';
            $maxMultiple = intdiv(255, 13) * 13 - 1; // 247
            for ($i = 0; $i < 8; $i++) {
                do {
                    $byte = ord( $bytes[$i] );
                } while ($byte > $maxMultiple );

                if ( $i == 4 )
                    $code .= '-' ;

                $code .= $chars[$byte % 13];
            }

            return $code;
        }
    }