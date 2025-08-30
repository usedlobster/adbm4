<?php

    namespace app\model;

    trait ViewLoginTrait {

        public function ViewLoginLogic() {

            $error = '';
            // do we have sid?
            if ( (self::$_id['sid'] ?? 0) < 1 )
            {
                // did we submit login request ?
                if ( isset($_POST[ '_login' ]) && self::$_app->checkCSRF() )
                {
                    $email = strtolower(trim($_POST[ 'email' ] ?? '')) ;
                    $pwd   = trim( $_POST['password'] ?? '' ) ;
                    $error = $this->loginWithEmailAndPassword( $email , $pwd );
                }

                if ( (self::$_id[ 'sid' ] ?? 0) > 0 )
                    \sys\UriUtil::navigateTo('/portal');

                echo self::$_app->view('login.login' , ['error' => $error]);
            }
            elseif ( (self::$_id[ 'pid' ] ?? 0) < 1 )
            {
                $list = $this->getActiveProjects( self::$_id['sid'] ?? 0 ) ?? [] ;

                if ( isset($_POST[ 'pick-project' ]) && self::$_app->checkCSRF() )
                {
                    $pid = (int) $_POST[ 'pick-project' ] ?? 0 ;
                    // is this in list of active
                    if ( in_array($pid , array_column( $list , 'pid' ),true ))
                        $this->setUser( self::$_id['sid'] ?? 0 , $pid , true ) ;
                }
                else
                {
                    $pid = $this->autoPick($list);
                    if ( ($pid ?? 0) > 0 )
                        $this->setUser(self::$_id[ 'sid' ] ?? 0 , $pid , true);
                }


                if ( (self::$_id[ 'pid' ] ?? 0) > 0 )
                    \sys\UriUtil::navigateTo('/portal');



                echo self::$_app->view('login.project' , ['error' => $error , 'list' => $list ?? [] ]);

            }
            else
                return true ;


            return false;
        }

    }