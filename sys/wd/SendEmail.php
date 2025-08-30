<?php

    namespace sys\wd;

    use PHPMailer\PHPMailer\PHPMailer;

    class SendEmail {

        protected static string | null  $_error = '' ;

        const _FROM_ADDR = 'bookings@adbm.co' ;
        const _FROM_NAME = 'ADBM Delivery Booking System' ;
        const _BASE_SUBJECT = '[ADBM] ' ;
        const _REPLY_ADDR = 'noreply@localhost.test' ;
        const _REPLY_NAME = 'ADBM - No Reply' ;
        const _TEMPLATE_DIR = '/../app/email-templates/' ;

        private static function send( string  $emailAddr , string $emailName  , string $htmlString , string $textString , string $subject )
        {
            try
            {

                $mail = new PHPMailer();

                // setup for debug email system - change later to match host server

                $mail->isSMTP();
                $mail->Host       = 'localhost';
                $mail->SMTPAuth   = false;
                $mail->Port       = 1025;
                $mail->XMailer    = "ADBM-Mailer" ;
                //
                $mail->setFrom( self::_FROM_ADDR , self::_FROM_NAME );
                $mail->addAddress( $emailAddr , $emailName  ) ;
                $mail->addReplyTo( self::_REPLY_ADDR , self::_REPLY_NAME );
                $mail->Subject = self::_BASE_SUBJECT . $subject ?? '' ;
                // Set HTML
                $mail->isHTML( !empty($htmlString)  );
                $mail->Body    = $htmlString ;
                $mail->AltBody = $textString ;
                return $mail->send() ? true : false ;


            }
            catch( \Exception $ex )
            {
                error_log( $ex );
                if ( _DEV_MODE )
                    throw $ex ;
            }

            return false ;
        }



        public static function sendTemplate( string $email , string $name , string $template , array $data)
        {
            try
            {
                self::$_error = null;
                $f = $_SERVER[ 'DOCUMENT_ROOT' ]. self::_TEMPLATE_DIR . $template.'.wd';
                $engine = new \sys\wd\wdScript();
                $engine->reset();
                $engine->addFile($f);
                $engine->exec($data);
                $subject = $engine->_data[ 'subject' ][ 'v' ] ?? '';
                return self::send( $email , $name , $engine->_html , $engine->_text , $subject ?? '' );
            }
            catch ( \Exception $ex )
            {
                error_log($ex);
                self::$_error = $ex;
                return false;
            }
        }



    }