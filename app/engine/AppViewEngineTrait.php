<?php


    namespace app\engine;

    trait AppViewEngineTrait    {


        use AppViewEngineDynamicTrait ;
        use AppViewModelTrait ;

        public function ViewPage($viewName , $data = []) : bool
        {
            if ( ($blade = \sys\BladeMan::getBlade()) )
            {
                echo $blade?->run($viewName , $data);
                return true;
            }
            return false;
        }

        public function ErrorPage($view , $response = 403 , $detail = [])
        {
            http_response_code($response ?: 401);
            try
            {
                if ( ($blade = \sys\BladeMan::getBlade()) )
                    echo $blade?->run($view , $detail);
            }
            catch ( \Throwable $ex )
            {
                echo '<h1>Error' , $response , '</h1>';
                echo '<hr><pre>' , print_r($detail , true) , '</pre>';
            }

            exit;
        }




    }