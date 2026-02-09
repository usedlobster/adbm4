<?php

namespace app\views;

abstract class ModelViewBase {

    public  ?string $_json = null ;
    public function __construct( $view ) {

        if ( !empty($view['json'] ))
            $this->_json = file_get_contents( $view['dirbase'] . $view['json']) ;
        else
            $this->_json = null ;

    }

    abstract public function exec( array $view , string $base , array $parts ) : bool ;



}