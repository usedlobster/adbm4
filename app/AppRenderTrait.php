<?php

    namespace app;

    use sys\db\SQL;

    trait AppRenderTrait
    {

        use AppEditorTrait;

        private function showUserSection($exp , $view , $viewData)
        {
            echo '#USER' . $exp ;
        }

        private function showSystemSection($exp , $view , $viewData)
        {
            if ( empty($exp) )
                return;

            if ( !($info = $viewData[ 'info' ] ?? false) )
                return;
            if ( !($id = $info[ '_id' ] ?? false) )
                return;

            if ((  $id['sid'] ?? 0 ) < 1 )
                return ;

            if (( $pid = $id[ 'pid' ] ?? 0 ) < 1 )
                return ;

            $skey = $pid . ':' . $view . $exp ;
            $mode = $_SESSION[ '_editor_mode' ] ?? 0 && ($info['editor_allowed'] ?? 0) ;

            if (( $mode & 3 ) === 0 ) {

            }
            else if (( $mode & 1 ))
                $this->showSystemEditor( 'sys:' . $skey ,  $viewData ) ;
        }


        private function showContent($uri)
        {
            try
            {
                /* get the base blade file */
                $filebase = join(DIRECTORY_SEPARATOR , $uri->_short);
                if ( !file_exists(__DIR__.'/view/view/'.$filebase.'.blade.php') )
                {
                    http_response_code(404);
                    echo \sys\Blade::getBlade()?->run('error.404' , []) ?? 'error-404';
                    exit;
                }

                $viewData = [];
                $info = self::$_login->getUserInfo();
                if ( empty($info) )
                    return;


                $view = 'view.'.join('.' , $uri->_short);
                $viewData = [
                        'app' => $this ,
                        'info' => $info ,
                        'uri' => $uri ,
                        'editable' => false ,
                        'base' => $filebase ,
                        'editable' => false ,
                        'view' => $view ,
                ];
                // are we allowed to edit pages - and does this page have any editable content ?

                $viewData[ 'editable' ] = $this->canEditPage($view , $viewData);
                $this->secureEditorMode($info[ 'editor_allowed' ] ?? 0);

                echo \sys\Blade::runPage($view , $viewData , function ($exp) use ($view , &$viewData) {
                    $this->showSystemSection(trim($exp) , $view , $viewData);
                });
            }
            catch ( \Throwable $ex )
            {
                echo '?' .__FILE__. ' ' .__LINE__. ' ' . $ex->getMessage();;
            }
        }

    }