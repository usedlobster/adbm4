<?php

namespace api\base\v1;

use api\base\apiBase;

class apiForm extends apiBase
{

    public function run( $payload , $parts )
    {

        $dataClass = '\\api\\data\\Data' . ucfirst( $parts[0] ?? '' )  ;
        if ( class_exists( $dataClass , true )) {
            $c = new $dataClass($this);
            return $c?->form($payload , $parts );

        }


    }




}
