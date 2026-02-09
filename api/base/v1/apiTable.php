<?php

namespace api\base\v1;

use api\base\apiBase;

class apiTable extends apiBase
{

    use \api\auth\ApiAuthTrait;
    use \api\table\ApiTableTrait;


    public function run( $payload , $parts )
    {

        $dataClass = '\\api\\data\\Data' . ucfirst( $parts[0] ?? 'x' )  ;
        if ( class_exists( $dataClass , true )) {
            $c = new $dataClass($this);

            return $c?->table($payload , $parts );

        }


    }




}
