<?php

    namespace app\setup;

    use app\AppBase;

    class AppSetup extends AppBase {



        public function view( ) : never {

            try
            {
                switch( $this->_uri[2] ?? false ) {
                    case 'companies' :
                        if ( empty($this->_uri[3] ?? '' ))
                            \app\AppMaster::viewPage( 'setup.' . $this->_uri[2] . '.list'  , $this->data );
                        break ;
                    default:
                        http_response_code(404) ;
                        break ;
                }

            }
            catch( \Throwable $ex ) {

            }
            exit ;
        }

    }