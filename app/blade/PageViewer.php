<?php

    namespace app\blade;

    class PageViewer {

        public function view($viewName , $data = []) : bool
        {
            try
            {
                if ( ($blade = \sys\blade\BladeMan::getBlade()) )
                {
                    echo $blade?->run($viewName , $data);
                    return true;
                }
            }
            catch( \Throwable $ex ) {
                error_log( $ex->getMessage() );
                echo '<pre>' , $ex->getMessage() , '</pre>' ;
                exit ;
            }

            return false;
        }


    }