<?php

namespace app\views\site\admin;

use app\views\ModelViewBase;

class mvcUsers extends ModelViewBase
{

    public function exec( array $view , string $base , array $parts ) : bool
    {
        //
        if ( count($parts) === 1 ) {
            $b = \app\wd\AppMaster::app()->getBlade();
            $b->share( 'mvc' , $this ) ;
            echo $b->runChild( 'site.admin.users' );
            $b->share( 'mvc' , null ) ;
        }








        return true ;
    }

}