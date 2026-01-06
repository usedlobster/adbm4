<?php

    namespace app;

    abstract class AppBase {

        protected array $data = []  ;
        private string  $_base = '' ;
        protected array  $_uri = []  ;

        abstract public function view(  ) : never ;
        public function run( $base , $uri_parts ) : never {
            global $_app ;
            $this->_base = $base      ;
            $this->_uri  = $uri_parts ;
            $this->data = [ 'app' => $_app , 'base' => $this->_base , 'uri' => $this->_uri ] ;
            $this->view() ;
        }

    }