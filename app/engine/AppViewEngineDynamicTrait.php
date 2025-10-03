<?php

    namespace app\engine;

    trait AppViewEngineDynamicTrait
    {

        use AppViewEngineDyanmicSystemContentTrait;
        use AppViewEngineDynamicUserContentTrait ;


        private function saveContent($mode , $blocks ) {

            // our we allowed to save ?
            if ( ($mode & $_SESSION[ '_editor_allow' ] & 3) === 0 )
                return;

            if ( is_array($blocks)) foreach ( $blocks as $key => $j )
            {
                try
                {
                    $ec = json_decode($j , false);
                    if ( $ec !== false && is_object($ec) )
                    {
                            if ( $mode === 1 )
                                $this->SaveSystemContent($ec);

                    }
                }
                catch ( \Throwable ) {

                }
            }
        }

        protected function secureEditMode()
        {
            if ( !isset($_SESSION[ '_editor_csrf' ]) )
                $_SESSION[ '_editor_csrf' ] = bin2hex(random_bytes(16));

            if ( !isset($_SESSION[ '_editor_allow' ]) )
            {
                // TODO: get from user profile
                $_SESSION[ '_editor_allow' ] = 3; // $user[ 'ea' ] ?? 0;
                $_SESSION[ '_editor_mode' ] = 0;
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

        protected function wrapEditBox($key , $content , $width = 'w-full' , $height = 'h-full' , $border_colour = 'border-gray-300')
        {
            return /** @lang HTML5 */ <<<HTML
            
                <div data-edit-block="{$key}" x-data="{ change:false }" class="p-2 {$border_colour} border-2 border-dotted relative" style="width:{$width};height:{$height};" 
                     role="region" 
                     aria-label="Editable content section"
                     @keydown.escape="cancelEditMode">
                
                    <div x-show="change" class="absolute top-4 right-4 flex gap-2">
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


        public function ShowAdbmSection($view , $exp , $pageData)
        {
            // @adbm( ... )
            $allow = $pageData[ 'allow' ] ?? 0;
            $mode = ($pageData[ 'mode' ] ?? 0) & $allow;
            switch ( $mode )
            {
                case 0 :
                    // $this->showLiveContent($view , $exp , $pageData);
                    break;
                case 1 :
                    // edit sys template
                    $this->showSystemEdit($view , $exp , $pageData);
                    break;
                case 2:
                    $this->showUserEdit($view , $exp , $pageData);
                    break;
            }
        }



    }


