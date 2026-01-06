<?php

    namespace sys\wd\Email;


    use PHPMailer\PHPMailer\PHPMailer;

    class SendEmail {

        protected static string | null  $_error = '' ;

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
                $mail->setFrom( $_ENV['EMAIL_FROM_ADDR'] , $_ENV['EMAIL_FROM_NAME'] );
                $mail->addAddress( $emailAddr , $emailName  ) ;
                $mail->addReplyTo( $_ENV['EMAIL_REPLY_ADDR'] , $_ENV['EMAIL_REPLY_NAME'] );
                $mail->Subject = $_ENV['EMAIL_BASE_SUBJECT'] . $subject ?? '' ;
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
                $f = $_SERVER[ 'DOCUMENT_ROOT' ]. $_ENV['EMAIL_TEMPLATE_DIR'] . $template.'.wd';
                $engine = new \sys\wd\Email\WDScript();
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
