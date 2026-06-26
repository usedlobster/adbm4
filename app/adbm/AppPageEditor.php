<?php

namespace app\adbm;

use sys\wd\wdscript\WDScript3;

class AppPageEditor
{


    private function showSystemEditor( $z  , $v  ) : void
    {
        $content = print_r($v,true) ;
        $id = '' ; $csrf2 = '' ;
        echo '<form method="post" action="" id="', $id, '" name="', $id, '">';
        echo '<input type="hidden" name="_token" value="', $csrf2, '" />';
        echo '<div id="x', $id, '" class="syseditor border-2 border-red-500 border-dotted ">', $content , '</div>';
        echo '</form>';

    }



    /*
     *  private function showSystemEditor($root, $id, $view) : void
    {
        // quick check allowed to do edit
        // this can be cached quick , as will check when saving
        if ( !$this->checkSysEdit(true)) {
            echo \app\wd\UI::errorText('No permission for ' . $view ) . '<br />' ;
            return;
        }

        // generate csrf token
        $csrf2 = $_SESSION['_csrf2'] ?? ($_SESSION['_csrf2'] = bin2hex(random_bytes(32)));
        $file = realpath( $root . '/' . str_replace('.', '/', $view) . '.wd2' ) ;
        if ( !str_starts_with( $file , $root ))
            return ;

        if ( file_exists($file) && is_writeable( $file ))
        {
            if (count($_POST) > 0 && $this->checkCsrf($csrf2) && $this->checkSysEdit(false)) {
                if (isset($_POST['x' . $id])) {
                    $current = $_POST['x' . $id] ?? '';
                    $current = \sys\Clean::html($current);

                    // we save it even if error above , as makes fixing edit less anoying
                    if (file_put_contents($file, $current) === false)
                        echo \app\wd\UI::errorText('File save failed') . '<br />' ;
                }
            }
        }

        $content = is_readable($file) ? ( file_get_contents($file) ?: '' ) : '' ;
        // show system editor
        echo '<form method="post" action="" id="', $id, '" name="', $id, '">';
        echo '<input type="hidden" name="_token" value="', $csrf2, '" />';
        echo '<div id="x', $id, '" class="syseditor border-2 border-red-500 border-dotted ">', $content , '</div>';
        echo '</form>';
    }


    private function showUserEditor($root, $id, $view) : void {
        if ( !$this->checkUsrEdit(true)) {
            echo \app\wd\UI::errorText('No permission for ' . $view ) . '<br />' ;
            return;
        }

        $file = realpath( $root . '/' . str_replace('.', '/', $view) . '.wd2' ) ;
        if ( !str_starts_with( $file , $root ))
            return ;
        $template = file_exists( $file) ? (file_get_contents( $file ) ?? '' )  : '' ;
        $csrf3 = $_SESSION['_csrf3'] ?? ($_SESSION['_csrf3'] = bin2hex(random_bytes(32)));
        //

        $content = '' ;
        try {

            $wd3 = new WDScript3();
            $wd3->compile( $template , $view ) ;

            $wd3->output( function( $x )  use( &$content ) {
                if ( isset($x['str'] ))
                    $content .= $x['str'] ;
            },
            function( $fn,$args ) use ( &$content, $root,$id,$view  )  {
            //
                switch ( $fn ) {
                    case '@map' :
                        $content .= \sys\wd\Page::map( $args , $root , $id , $view  ) ;
                        break ;
                    default:
                        $content .= '???' ;
                        break ;

                }



            }) ;

        }
        catch( \RunTimeException $e) {
            echo \app\wd\UI::errorText('Failed : ' . $e->getMessage()) . '<br />' ;
        }

        echo '<div id="x', $id, '" class="syseditor border-2 border-green-500 border-dotted ">', $content , '</div>';


    }

    private function showLiveContent($root, $id, $view) : void {
    }
     */

    private function findDynTemplate( $pid , $root, $view) : ?array
    {
        if (!$root || !$view)
            return null;

        $rp = realpath($root) ;
        $v1 = $pid . '.' . $view;
        $f1 = realpath( $root . '/' . str_replace('.', '/', $v1) . '.wd2' );
        if ( str_starts_with( $f1 , $rp ) && file_exists($f1) )
            return [$v1,$f1,null] ; //

        if ( $pid !== 0 )
        {
            $v2 = '0' . '.' . $view ;
            $f2 = realpath(  $root . '/' . str_replace('.', '/',   $v2) . '.wd2' );
            if ( str_starts_with( $f2 , $rp ) && file_exists($f2) )
                return [$v1,$f2,$f1] ;
        }

        return null ;
    }

    public function show( int $edit , int $sid , int $pid , string $view , string $exp)
    {
        try {
            if ( $sid < 1 || $pid < 1 )
                return ;

            $root = $_SERVER['DOCUMENT_ROOT'] . '/../dyn' ;
            $v = $this->findDynTemplate( $pid , $root, $exp);
            if ($v !== null && is_array($v) && count($v) === 3 ) {
                //
                $z = file_get_contents( $v[1] ) ?? ''  ;
                switch ($edit) {
                    case 0 :
                        break;
                    case 1 :
                        $this->showSystemEditor( $z , $v );
                        break;
                    case 2 :
                        break;
                    case 3 :
                        break;
                    default :
                        throw new \RuntimeException('Invalid edit value');
                }
            }
            else {
                // missing
                echo '$' , $exp , '$' ;

            }
        }
        catch( \RuntimeException $ex ) {
            echo 'Error: ' . $ex->getMessage();
            exit ;
        }
        catch( \Throwable $ex ) {
            error_log( $ex ) ;
            echo 'Opps' ;
            exit ;
        }




    }


}