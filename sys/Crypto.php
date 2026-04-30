<?php

    namespace sys;

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
                $plain = sodium_crypto_secretbox_open($ciphertext, $nonce, $ekey ) ;
                return $plain ?: '' ;
            }
            catch (\Throwable $ex )
            {
                error_log($ex);
            }


            return '' ;
        }

        public static function newKey( int $length = 9 ) : string
        {
            return sodium_bin2base64( random_bytes( $length ) , SODIUM_BASE64_VARIANT_URLSAFE);
        }

        public static function newPin( int $length = 9 ) : string {
            $r = '' ;
            for ( $i = 0 ; $i < $length ; $i++ ) {#
                $r .= random_int(0,9) ;
            }
            return $r ;

        }

        private static function base32Encode(string $data): string {
            $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
            $binary = '';
            foreach (str_split($data) as $char) {
                $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
            }

            $output = '';
            foreach (str_split($binary, 5) as $chunk) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
                $output .= $alphabet[bindec($chunk)];
            }

            return $output;
        }

        private static function base32Decode(string $secret): string
        {
            $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
            $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret) ?? '');
            $bits = '';

            foreach (str_split($secret) as $char) {
                $pos = strpos($alphabet, $char);
                if ($pos === false) {
                    continue;
                }

                $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
            }

            $bytes = '';
            foreach (str_split($bits, 8) as $chunk) {
                if (strlen($chunk) === 8) {
                    $bytes .= chr(bindec($chunk));
                }
            }

            return $bytes;
        }
        public static function generateTotpSecret(int $bytes = 20): string {
            return self::base32Encode(random_bytes($bytes));
        }


        public static function totp(string $secret, ?int $timeSlice = null): string {

            $timeSlice ??= intdiv(time(), 30);
            $key = self::base32Decode($secret);
            $binary = pack('N*', 0) . pack('N*', $timeSlice);
            $hash = hash_hmac('sha1', $binary, $key, true);
            $offset = ord($hash[19]) & 0x0F;
            $code = ((ord($hash[$offset]) & 0x7F) << 24)
                | ((ord($hash[$offset + 1]) & 0xFF) << 16)
                | ((ord($hash[$offset + 2]) & 0xFF) << 8)
                | (ord($hash[$offset + 3]) & 0xFF);

            return str_pad((string)($code % 1000000), 6, '0', STR_PAD_LEFT);
        }

        public static  function verifyTOTP(string $secret, string $code, int $window = 1): bool {
            $timeSlice = intdiv(time(), 30);
            for ($i = -$window; $i <= $window; $i++) {
                if (hash_equals( self::totp($secret, $timeSlice + $i), $code)) {
                    return true;
                }
            }

            return false;
        }

        public static function buildOtpAuth(string $issuer, string $accountName, string $secret): string {
            return 'otpauth://totp/'
                . rawurlencode($issuer . ':' . $accountName)
                . '?secret=' . $secret
                . '&issuer=' . rawurlencode($issuer)
                . '&algorithm=SHA1'
                . '&period=30&digits=6';
        }


//        public static function testOTP() {
//            $secret = self::generateTotpSecret();
//            $otp = self::totp($secret);
//            echo "Secret: $secret\n";
//            echo "OTP: $otp\n";
//            echo "URL: " . self::buildOtpAuth('Example', 'Test User', $secret) . "\n";
//        }


    }