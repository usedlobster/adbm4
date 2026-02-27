<?php

namespace app\views\site\admin;

use app\views\ModelViewBase;

class mvcUsers extends ModelViewBase
{

    public function exec(array $view, string $base, array $parts) : bool
    {
        //

        $id = ( count($parts) > 1 ) ? $parts[1] : 0 ;
        $b = \app\wd\AppMaster::app()->getBlade();
        $b->share('mvc', $this);
        echo $b->runChild('site.admin.users' , ['id'=>$id ]);

        $b->share('mvc', null);

        return true;
    }

}