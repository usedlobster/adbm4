<?php

    namespace app\portal;

    class AppPortal extends \app\AppBase {



        public function view( ) : never {


            \app\AppMaster::viewPage( 'portal'  , $this->data );



        }


    }