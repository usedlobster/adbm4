<?php

    namespace api\base\v1;

    use api\base\apiBase;

    class apiProject extends apiBase
    {


        public function run( $payload , $parts )
        {
            switch ( $parts[ 0 ] ?? false )
            {
                case 'list' :

                    return $this->list() ;
                    break ;

                default :
                    return 899;
            }
        }

        private function list()
        {

            $auth = $this->auth() ;
            $list = $this->getProjectList($auth->sid ?? 0 )  ;

            return (object)['list'=>$list ?? [] ]  ;






        }

    }