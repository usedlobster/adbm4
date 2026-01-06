<?php

    namespace api\data;

    class DataComps {


        public function run( $payload , $parts ) {

            switch( $payload->act ) {
                case 'list' :
                    return $this->listComps( $payload ) ;

            }
        }

    }