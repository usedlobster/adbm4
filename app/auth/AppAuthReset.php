<?php

    namespace app\auth;

    class AppAuthReset
    {

        /**
         * STAGE: 0 Get account name to reset, and send reset code
         *
         * Initiates the reset password process by handling form submission and validating the user input.
         * It processes the user's email or username for account verification and triggers the send-code
         * action if the input is valid. Depending on the server response, it updates the session state
         * and navigates to the next page. If errors occur, an error message is prepared for display.
         *
         * @return void
         */
        public function startResetPasswordPage() : never
        {
            // reset request state
            $_SESSION[ '_reset_stage' ] = 0     ;
            $_SESSION[ '_reset_user'  ]  = null ;
            $_SESSION[ '_reset_token' ]  = null ;
            $errormsg = '' ;
            if ( isset( $_POST[ '_sendcode' ] ) && \sys\blade\BladeMan::checkCSRF() )
            {
                // submitted button - so process
                $user = strtolower( trim( $_POST[ 'email' ] ?? '' ) );
                if ( empty( $user ) || ( !\sys\validate\Valid::email( $user ) && !\sys\validate\Valid::username( $user ) ) )
                    $errormsg = \sys\Error::msg( 1001 );
                else
                {
                    // this will generate a reset code , and send to user ( or not - depending on real or genuine )
                    $result = \app\AppMaster::apiPost( 'v0/login' , [
                            'act' => 'sendcode' ,
                            'user' => $user ,
                            'ip' => $_SERVER[ 'REMOTE_ADDR' ] ?? '' ,
                    ] );

                    // Under most circumstances the result should be ok
                    // unless api was unavailable. Result will be ok for invalid accounts , and/or inactive.
                    // or have been attempted to many times
                    if ( $result && isset( $result->ok ) && $result->ok )
                    {
                        $_SESSION[ '_reset_stage' ] = 1;
                        $_SESSION[ '_reset_user' ]  = $user;
                        $_SESSION[ '_reset_token']  = '' ;
                        \sys\uri\UriUtil::navigateTo( '/auth/enter-code' );
                    }
                    elseif ( $result->error ?? false )
                        $errormsg = \sys\Error::msg( $result->error );
                }
            }

            // show start reset password page
            \app\AppMaster::viewPage( 'layout.auth.reset.start' , [ 'errormsg' => $errormsg ?? '' ] );
            exit ;
        }

        /**
         * STAGE : 1 - Enter the reset code
         *
         * Handles the logic for the "Enter Code Page" of the password reset process.
         *
         * This method verifies the reset stage and user session. If the session is not in the proper state,
         * it redirects to the reset-password page. Upon receiving a submission, it validates the CSRF token,
         * checks the input code, and communicates with the backend API to verify and process the reset code.
         * Depending on the API response, the user is either directed to the change-password page or shown an
         * error message.
         *
         * @return void
         */
        public function enterCodePage() : never
        {
            if ( !( ( $_SESSION[ '_reset_stage' ] ?? 0 ) === 1 ) || !isset( $_SESSION[ '_reset_user' ] ) )
            {
                // reset state , not needed if navigation was successful but to be sure.
                unset( $_SESSION[ '_reset_stage' ] ) ;
                unset( $_SESSION[ '_reset_user' ] )  ;
                unset( $_SESSION[ '_reset_token'] )  ;
                \sys\uri\UriUtil::navigateTo( '/auth/reset-password' );
            }

            $errormsg = '';
            if ( isset( $_POST[ 'check-code_submit' ] ) && \sys\blade\BladeMan::checkCSRF() )
            {
                $user = $_SESSION[ '_reset_user' ] ?? '';
                $code = $_POST[ 'check-code' ] ?? '';
                if ( !empty( $user ) && mb_strlen($code) === 8)
                {
                    // user has entered a code, so genereate a challenge
                    // send + hash to api to validate

                    $vcode  = base64_encode( random_bytes( 63 ) );
                    $result = \app\AppMaster::apiPost( 'v0/login' , [
                            'act' => 'resetauth' ,
                            'user' => $user ,
                            'otp' => $code ,
                            'vcode' => hash( 'sha256' , $vcode ) ,
                    ] );


                    if ( $result && isset( $result->authid ) )
                    {
                        // we have an authid code
                        // try to exchange authid for reset token
                        $result = \app\AppMaster::apiPost( 'v0/login' , [
                                'act' => 'resettoken' ,
                                'authid' => $result->authid ,
                                'v' => $vcode
                        ] );

                        if ( $result && isset( $result->ztkn ) )
                        {
                            $_SESSION[ '_reset_stage' ] = 2;
                            $_SESSION[ '_reset_token' ] = $result->ztkn;
                            \sys\uri\UriUtil::navigateTo( '/auth/change-password' );
                        }
                    }
                }

                $errormsg = 'Reset Code Expired or Invalid';
            }

            \app\AppMaster::viewPage( 'layout.auth.reset.code' , [ 'errormsg' => $errormsg ?? '' ] );

        }

        /**
         * Displays and processes the change password page functionality.
         *
         * This method validates the session state and required conditions for password reset.
         * If the session is not valid, the user is redirected to the reset password initiation page.
         * Processes the submitted form data to change the password by sending a request to the server.
         * Handles validation errors and displays appropriate error messages on the page.
         *
         * @return void
         */
        public function changePasswordPage() : never
        {
            if ( !( ( $_SESSION[ '_reset_stage' ] ?? 0 ) === 2 ) || !isset( $_SESSION[ '_reset_user' ] , $_SESSION[ '_reset_token' ] ) || empty( $_SESSION[ '_reset_token' ] ) )
            {
                unset( $_SESSION[ '_reset_stage' ] );
                unset( $_SESSION[ '_reset_token' ] );
                unset( $_SESSION[ '_reset_user' ] );
                \sys\uri\UriUtil::navigateTo( '/auth/reset-password' );

            }

            $errormsg = '';
            if ( isset( $_POST[ 'cpw_submit' ] ) && \sys\blade\BladeMan::checkCSRF() )
            {
                $result = \app\AppMaster::apiPost( 'v0/login' , [
                        'act'   => 'changepassword' ,
                        'ztkn'  => $_SESSION[ '_reset_token' ] ?? '' ,
                        'user'  => $_SESSION[ '_reset_user']   ?? '' ,
                        'pass1' => $_POST[ '_pwd1' ] ?? '' ,
                        'pass2' => $_POST[ '_pwd2' ] ?? ''
                ] );

                if ( $result && isset( $result->ok ) && $result->ok ) {

                    // at this stage password has been reset
                    $_SESSION[ '_reset_stage' ] = 3;
                    \sys\uri\UriUtil::navigateTo( '/auth/reset-success' );

                }
                else
                    $errormsg = \sys\Error::msg( $result->error ?? 800 );
            }


            \app\AppMaster::viewPage( 'layout.auth.reset.cpw' , [ 'errormsg' => $errormsg ?? '' ] );
        }


    }