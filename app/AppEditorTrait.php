<?php

    namespace app;

    trait AppEditorTrait
    {

        const int EDIT_MASK = 0x03;


        private function canEditPage($view , $viewData) : bool
        {
            // for now just make all pages editable
            return true;
        }

        private function getSystemContent($skey)
        {
            $content = \sys\db\SQL::Get0(" SELECT edata FROM <DB>.sys_epages WHERE skey  = ? " , [$skey]);
            if ( !empty(\sys\db\SQL::error()) ) {
                error_log(\sys\db\SQL::error());
                return '][error][' ;
            }
            if ( $content === false )
                $content = '';

            return $content;
        }

        private function saveSystemContent($ec) : void
        {
            try
            {

                        \sys\db\SQL::Exec(<<<SQL
                              REPLACE INTO <DB>.sys_epages 
                                  ( skey , edata , cr ) 
                                  VALUES ( ? , ? ,  NOW())
                              SQL, [$skey , json_encode($edata) , $html]);
            }
            catch ( \Exception $e )
            {
                error_log($e->getMessage());
                return;
            }
        }


        private function secureEditorMode(int $allowed) : void
        {
            // generate csrf
            if ( !isset($_SESSION[ '_editor_csrf' ]) )
                $_SESSION[ '_editor_csrf' ] = base64_encode(random_bytes(18));

            $newMode = (int)($_POST[ '_editorMode' ] ?? ($_SESSION[ '_editor_mode' ] ?? 0)) & $allowed & self::EDIT_MASK;
            // if this was from a post request, check it had  a valid csrf
            if ( isset($_POST[ '_editorMode' ]) )
            {
                if ( !isset($_POST[ '_editor_token' ]) || ($_POST[ '_editor_token' ] !== $_SESSION[ '_editor_csrf' ]) )
                    $newMode = 0;
                elseif ( ($allowed & 1) && isset($_POST[ '_editor_econtent' ]) )
                {
                    // save content
                    //foreach ( $_POST[ '_editor_econtent' ] as $ec )
                    //      $this->saveEditContent($ec);
                }
                $_SESSION[ '_editor_csrf' ] = base64_encode(random_bytes(18));
            }

            $_SESSION[ '_editor_mode' ] = $newMode;
        }


        private function showSystemEditor($skey , $viewData)
        {
            $content = htmlspecialchars($this->getSystemContent($skey)) ;
            echo <<<'HTML'
    <div class="p-2"> 
        <textarea>{$content}</textarea><!-- Keep this on one line -->
    </div>
HTML;
        }
    }