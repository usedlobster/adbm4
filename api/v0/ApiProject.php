<?php

    namespace api\v0;

    use api\ApiBase;

    class ApiProject extends ApiBase
    {
        protected function api($payload) : object|null
        {
            // TODO: Implement api() method.

            switch( $payload?->act ?? '' ) {
                case 'list' :
                    return new \api\auth\AuthProject()?->apiListProjects( $payload ) ;
                    break ;
                default:
                    return (object)['error'=>'Unknown Project Action'];
            }
            return null ;
        }
    }