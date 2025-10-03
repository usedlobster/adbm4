<?php

    namespace app\engine;

    trait AppViewEngineDynamicUserContentTrait {


        use AppUserMapsTrait ;

        public function showUserEdit($view , $exp , $pageData) {

            $sect = $this->getSystemTemplate($view , $exp) ;
            if ( !(empty($sect['sysp']['err'] ?? false )))
                echo  '**error**' ;
            else
            {

                $w = $sect[ 'sysp' ][ 'res' ][ 'zx' ] ?? 'w-full';
                $h = $sect[ 'sysp' ][ 'res' ][ 'zy' ] ?? 'h-full';
                $key = $sect[ 'k' ] ?? '_';
                $content = $sect[ 'base' ] ?? '';
                echo "<div class=\"p-2 border-gray-300 border-2 border-dotted relative\" style=\"width:{$w};height:{$h};\">" ;
                try
                {
                    if ( ($blade = \sys\BladeMan::getBlade()) )
                    {
                        $blade->directiveRT('adbm' , function () {});
                        $blade->directiveRT('user' , function ($exp)
                        {
                            $this->showUserEditableRegion( trim($exp) ) ;
                        });

                        echo $blade?->runString($content , $pageData);
                    }
                }
                catch ( \Throwable $ex ) {
                    echo '<b class="text-red-500">' , $ex->getMessage() , '</b>' ;
                }
                finally
                {
                    echo "</div>";
                }
            }

            // $editor = '<textarea spellcheck="false" @input.debounce.200="change=true;changed=true" data-editor="'.$key.'" class="w-full h-full resize-none  " id="'.$key.'">'. $content . '</textarea>' ;
            // echo $this->wrapEditBox($key , $editor , $w , $h , 'border-green-500');


        }


        private function showUserEditableRegion($exp) {
            $u = explode( ':' ,$exp  , 3  ) ;
            if ( count( $u ) < 2 )
                throw new \Exception('invalid @user expression => ' . htmlspecialchars($exp) ) ;
            $t = $u[0] ?? '' ;
            if ( !in_array( $t , ['map' ]))
                throw new \Exception('invalid @user type  => ' . htmlspecialchars($t) ) ;
            $n = $u[1] ?? '' ;
            if ( empty($n) || !preg_match('/^[a-z0-9_]+$/i' , $n) )
                throw new \Exception('invalid @user name  => ' . htmlspecialchars($n) ) ;

            switch( $t ) {
                case 'map' :
                    $this->showUserEditableMap( $n , $exp ) ;
                    break ;
            }


        }

    }