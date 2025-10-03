<?php

    namespace app\engine;

    trait AppViewModelTrait {



        private function getViewModels($uri)
        {

            $t = $uri->_short;

            $v = false;
            $p = false;
            $j = false;
            $m = false;

            if ( is_array($t) )
            {
                while ( count($t) > 0 )
                {
                    $x = join('/' , $t);
                    $b = __DIR__.'/../view/pages/'.$x;

                    if ( !$v && file_exists(($f = $b.'.blade.php')) )
                        $v = 'pages.'.join('.' , $t);

                    if ( !$p && file_exists(($f = $b.'.php')) )
                        $p = $f;

                    if ( !$j && file_exists(($f = $b.'.min.js')) )
                        $j = $f;

                    if ( !$m && file_exists(($f = __DIR__.'/model/'.$x.'.class.php')) )
                        $m = '\\app\\model\\'.join('\\' , $x);

                    array_pop($t);
                }
            }

            return [$v , $p , $j , $m];
        }


        public function ViewModel( $uri  ) : bool
        {
            try {

                $this->secureEditMode() ;

                [$view_name,$php_script,$js_file , $model_name ] = $models = $this->getViewModels($uri);

                $pageData = [
                        'view' => $view_name ,
                        'model_name' => $model_name ,
                        'php_script' => $php_script,
                        'allow' => $_SESSION['_editor_allow'] ?? 0 ,
                        'mode' => $_SESSION['_editor_mode'] ?? 0 ,
                        'link' => $uri->_short ,
                        'param' => $uri->_params ?? [] ,
                        'method' => $uri->_method ,
                        'post' => $_POST ,
                        'js' => $js_file ,
                        'id' => $this->getLogin()
                ];

                if ( file_exists($php_script) )
                    require_once($php_script);


                // should model do the rendering? , alone
                $model = $model_name ? new $model_name($this) : false ;
                if ( $model && $model instanceof \app\model\ModelBase && $model?->init($pageData) === true )
                    return true ;

                if ( !$view_name ) {

                    $this->ErrorPage( 'error.404', 404 , 'Page Not Found');
                    return false ;

                }


                if ( ($blade = \sys\BladeMan::getBlade()) )
                {
                    $blade->directiveRT('user' , function () {});
                    $blade->directiveRT('adbm' , function ($exp) use ($view_name , $pageData)
                    {
                        $this->ShowAdbmSection($view_name, trim($exp) , $pageData);
                    });
                    echo $blade?->run($view_name , $pageData);
                }
                else
                    throw new \Exception('Blade not initialized');

                return true ;

            }
            catch( \Throwable $ex) {
                $this->ErrorPage( 'error.500', 500 , 'Internal Server Error<br>'.$ex->getMessage());
            }

            return false ;
        }
    }