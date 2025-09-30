<?php

    namespace app\traits;


    trait AppEditorContentTrait
    {

        use AppEditorContentSaveTrait;

        // fix editor mode

        public function secureEditorMode() : bool
        {
            // any request will need this csrf token to be even considered,
            // so need to generate it if it doesn't exist yet.
            if ( !isset($_SESSION[ '_editor_csrf' ]) )
                $_SESSION[ '_editor_csrf' ] = bin2hex(random_bytes(16));

            $allow = (int)($_SESSION[ '_editor_allow' ]  ?? -1) ;
            if ( $allow < 0 )
            {
                $user = $this->getInfo();
                $_SESSION[ '_editor_allow' ] = ($allow = ($user[ 'ea' ] ?? 0));
                $_SESSION[ '_editor_mode' ] = 0;
            }

            if ( $allow === 0 )
                return true;

            if ( isset($_POST[ '_editor_token' ]) && ($_POST[ '_editor_token' ] === $_SESSION[ '_editor_csrf' ]) )
            {

                if ( isset($_POST[ '_editorMode' ]) )
                {
                    $old_mode = (int)(($_SESSION[ '_editor_mode' ] ?? 0) & $allow);
                    $new_mode = (int)(($_POST[ '_editorMode' ] ?? 0) & $allow);
                    if ( $old_mode !== $new_mode )
                        $_SESSION[ '_editor_mode' ] = $new_mode;

                    if ( ($old_mode & 1) && !($new_mode & 1) )
                        $this->saveSystemTemplate( $_POST[ '_editor_content1' ] ?? []);
                    elseif ( ($old_mode & 2) && !($new_mode & 2) )
                        $this->saveUserTemplate( $_POST[ '_editor_content2' ] ?? []);

                }
            }


            return true;
        }


        private function showTemplate( $view , $exp , $pageData)
        {
            //
            $key = $view . ':' .  $exp ;
            $sys = self::$_store->getAsset($key,1 , false  );
            if ( !$sys )
                $sys = '' ;

            echo '[' . $key . ']' ;
        }

        public function ViewBasePage($uri , $model , $pageData)
        {
            try
            {
                $view = $pageData[ 'view' ] ?? false;
                if ( !$view )
                    return;

                $pageData[ 'skey' ]  = hash('sha384' , 'view'.serialize($pageData));
                $pageData[ 'app' ]   = $this;
                $pageData[ 'model' ] = $model;
                $pageData[ 'uri' ]   = $uri;

                // $b = self::$_blademan->getBlade();
                $b = null ;

                if ( $b ) {
                    $b->directiveRT('adbm' , function ($exp) use ($view,$pageData) {

                        showADBM( $view , $exp , $pageData ) ;
                    });
                    echo $b->run($view , $pageData);
                }
            }
            catch ( \Throwable $ex )
            {
            }
        }

    }