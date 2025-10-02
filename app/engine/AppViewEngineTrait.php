<?php

    namespace app\engine;

    trait AppViewEngineTrait
    {

        private const array SYS_VIEW_P = [
                'ttl' => ['type' => 'int' , 'default' => 300] ,
                'zx' => ['type' => 'size' , 'default' => 'w-full'] ,
                'zy' => ['type' => 'size' , 'default' => 'h-full'] ,
        ];

        // very simple view page, slightly over-cautious
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


        private function getViewModels($uri)
        {
            // seems faster even not cached
            // so just compute model / view

            $t = $uri->_short;

            $v = false;
            $p = false;
            $j = false;
            $m = false;

            if ( is_array($t) )
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


            return [$v , $p , $j , $m];
        }

        private function secureEditMode()
        {
            if ( !isset($_SESSION[ '_editor_csrf' ]) )
                $_SESSION[ '_editor_csrf' ] = bin2hex(random_bytes(16));

            if ( !isset($_SESSION[ '_editor_allow' ]) )
            {
                // er = $this->get
                $_SESSION[ '_editor_allow' ] = 3; // $user[ 'ea' ] ?? 0;
                $_SESSION[ '_editor_mode' ] = 1;
            }

            if ( isset($_POST[ '_editor_token' ]) && ($_POST[ '_editor_token' ] === $_SESSION[ '_editor_csrf' ]) )
            {
                // valid post token
                $allow = (int)($_SESSION[ '_editor_allow' ] ?? 0);
                $old_mode = (int)(($_SESSION[ '_editor_mode' ] ?? 0) & $allow);
                $new_mode = (int)(($_POST[ '_editorMode' ] ?? 0) & $allow);
                if ( $old_mode !== $new_mode )
                {
                    if ( ($old_mode & 1) && !($new_mode & 1) )
                        $this->saveContent(1 , $_POST[ '_editor_content1' ] ?? []);
                    elseif ( ($old_mode & 2) && !($new_mode & 2) )
                        $this->saveContent(2 , $_POST[ '_editor_content2' ] ?? []);

                    $_SESSION[ '_editor_mode' ] = $new_mode;
                }
            }
        }


        public function ViewModel($uri) : bool
        {
            $this->secureEditMode();

            [$view , $php , $js , $model_name] = $this->getViewModels($uri);


            if ( $model_name )
            {
                $model = new $model_name();
                if ( !($model instanceof \model\ModelBase) )
                    throw new \Exception('invalid model class '.$model_name);
            }
            else
                $model = false;

            if ( $php )
            {
                @ require_once($php);
                if ( function_exists('php_model_run') )
                {
                    /** @noinspection PhpUndefinedFunctionInspection */
                    $php_result = @ php_model_run($view , $uri , $model);
                }
            }

            $allow = (int)($_SESSION[ '_editor_allow' ] ?? 0);
            $mode = (int)(($_SESSION[ '_editor_mode' ] ?? 0) & $allow);

            $pageData = [
                    'view' => $view ,
                    'model_name' => $model_name ,
                    'php' => $php_result ?? false ,
                    'allow' => $allow ,
                    'mode' => $mode & $allow ,
                    'link' => $uri->_short ,
                    'param' => $uri->_params ?? [] ,
                    'method' => $uri->_method ,
                    'post' => $_POST ,
                    'js' => $js ? file_get_contents($js) : false ,
                    'id' => $this->getLogin()
            ];


            if ( $model && $model?->render($pageData) === true )
                return true;

            if ( ($blade = \sys\BladeMan::getBlade()) )
            {
                $blade->directiveRT('adbm' , function ($exp) use ($view , $pageData)
                {
                    $this->showAdbmSection($view , $exp , $pageData);
                });
                echo $blade?->run($view , $pageData);
            }


            return false;
        }


        private function wrapEditBox( $key , $content , $width = 'w-full' , $height = 'h-full' , $border_colour = 'border-gray-300')
        {
            return /** @lang HTML5 */ <<<HTML
            
                <div data-edit-block="{$key}" x-data="{ change:false }" class="p-2 {$border_colour} border-2 border-dotted relative" style="width:{$width};height:{$height};" 
                     role="region" 
                     aria-label="Editable content section"
                     @keydown.escape="cancelEditMode">
                
                    <div x-show="changed" class="absolute top-4 right-4 flex gap-2">
                        <button type="button" 
                                class="text-green-500 hover:text-green-700 focus:outline-none" 
                                @click="saveEditAndClose(   )"
                                aria-label="Save changes and exit editor">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                                 aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </button>
                        <button type="button" 
                                class="text-red-500 hover:text-red-700 focus:outline-none" 
                                @click="cancelEditMode()"
                                aria-label="Discard changes and exit editor">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                                 aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                
                    </div>                  
                
                    {$content}
                
                
                </div>
          
        HTML;
        }


        private function getSection($typ , $view , $exp)
        {
            // valid view-name
            if ( !preg_match('/^[a-zA-Z\.0-9_-]+$/' , $view) )
                return false;

            list($name , $param) = explode(':' , $exp , 2);

            if ( !preg_match('/^[a-zA-Z0-9_-]+$/' , $name) )
                return false;

            $pid = $this->getPID();

            $k = [$pid , $typ , ...explode('.' , $view)];

            $dir = join('/' , $k);

            array_push($k , $name);

            $key = join('_' , $k);

            return ['t' => $typ , 'd' => $dir , 'n' => $name , 'p' => $param , 'k' => $key];
        }


        // show system editor
        private function showSystemEditor($view , $exp)
        {
            if ( !($sect = $this->getSection('sys' , $view , $exp)) )
                return;

            $sysP = new AppParams()->getParamsFromString($sect[ 'p' ] ?? '' , self::SYS_VIEW_P);

            if ( !empty($sysP[ 'err' ] ?? []) )
            {
                echo '<pre>' , join('<br/>' , $sysP[ 'err' ]) , '</pre>';
                return;
            }

            $params = $sysP[ 'res' ] ?? [];
            $w = $params[ 'zx' ] ?? 'w-full';
            $h = $params[ 'zy' ] ?? 'h-full';


            $key = $sect[ 'k' ];
            $file = $_SERVER[ 'DOCUMENT_ROOT' ].'/../site/'.$sect[ 'd' ].'/'.$sect[ 'n' ].'.html';

            $content = (file_exists($file)) ? file_get_contents($file) : '';
            $editor = '<textarea spellcheck="false" @input.debounce.200="change=true;changed=true" data-editor="'.$key.'" class="w-full h-full resize-none  " id="'.$key.'">'.$content.'</textarea>';

            echo $this->wrapEditBox( $key , $editor , $w , $h , 'border-red-500');
        }


        private function showAdbmSection($view , $exp , $pageData)
        {
            try
            {
                $mode = ($pageData[ 'mode' ] ?? 0) & 3;
                switch ( $mode )
                {
                    case 1 :
                        $this->showSystemEditor($view , $exp);
                        break;
                }
            }
            catch ( \Throwable $e )
            {
                error_log('Error in showAdbmSection: '.$e->getMessage());
                throw $e; // Re-throw to handle it at a higher level
            }
        }

        private function saveContent($mode , $content) {

            if ( empty($content))
                return ;
            // our we allowed to save ?
            if (( $mode & $_SESSION['_editor_allow'] & 3 ) === 0 )
                return ;



            foreach( $content as $key => $j )
            {
                $ec = json_decode($j, false ) ;
                if ( $ec ) {
                    // eg {"id":"1_sys_pages_portal_portal1","content":"Hello, Portal! x"}
                    if ( $mode === 1 )
                        $this->saveSystemContent( $ec ) ;
                }


            }
        }

        public function saveSystemContent( $ec )
        {
            if ( !isset( $ec->id ))
                return ;
            $id = $ec->id ;
            $content = $ec->content ;
            $dir = str_replace( '_' , '/' , $id ) ;
            $file = $_SERVER[ 'DOCUMENT_ROOT' ].'/../site/'.$dir.'.html' ;
            if ( !file_exists( dirname($file) ))
                mkdir( dirname($file) , 0777 , true ) ;


            file_put_contents($file , $content) ;

        }


    }