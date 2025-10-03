<?php

    namespace app\engine;

    trait AppViewEngineDyanmicSystemContentTrait {


        private const array SYS_VIEW_P = [
                'zx' => ['type' => 'size' , 'default' => 'w-full'] ,
                'zy' => ['type' => 'size' , 'default' => 'h-full'] ,
        ];


        private function saveSystemContent( object $ec )
        {
            $id = $ec->id ?? '' ;
            $content = $ec->content ?? '' ;
            if ( ( strlen($content) > 65536 * 16 ) && preg_match('/^\d+_(sys|usr)(_[a-zA-Z0-9]+){1,8}$/' , $id))
                return false ;


            $dir = str_replace('_' , '/' , $id);
            $file = $_SERVER[ 'DOCUMENT_ROOT' ].'/../site/'.$dir.'.html';
            if ( !file_exists($file) )
                mkdir( dirname( $_SERVER[ 'DOCUMENT_ROOT' ].'/../site/'.$dir ) , 0775 , true );

            file_put_contents($file , $content);

        }



        private function getSystemTemplate($view , $exp)
        {
            if ( !($sect = $this->getSection('sys' , $view , $exp)) )
                return;

            $sect[ 'sysp' ] = new AppParams()->getParamsFromString($sect[ 'p' ] ?? '' , self::SYS_VIEW_P);
            $file = $_SERVER[ 'DOCUMENT_ROOT' ].'/../site/'.$sect[ 'd' ].'/'.$sect[ 'n' ].'.html';
            $sect[ 'base' ] = (file_exists($file)) ? file_get_contents($file) : '';
            return $sect;
        }


        public function showSystemEdit($view , $exp , $pageData) {

            $sect = $this->getSystemTemplate($view , $exp) ;
            if ( !(empty($sect['sysp']['err'] ?? false )))
                echo  '**error**' ;
            //
            $w = $sect['sysp']['res']['zx'] ?? 'w-full' ;
            $h = $sect['sysp']['res']['zy'] ?? 'h-full' ;
            $key = $sect[ 'k' ] ?? '_' ;
            $content = $sect[ 'base' ] ?? '' ;

            $editor = '<textarea spellcheck="false" @input.debounce.200="change=true;changed=true" data-editor="'.$key.'" class="w-full h-full resize-none  " id="'.$key.'">'. $content . '</textarea>' ;
            echo $this->wrapEditBox($key , $editor , $w , $h , 'border-red-500');


        }


    }