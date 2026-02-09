<?php

    namespace api\base\v1;

    use api\base\apiBase;

    class apiProject extends apiBase
    {

        use \api\auth\ApiAuthTrait;


        public function run( $payload , $parts )
        {
            switch ( $parts[ 0 ] ?? false )
            {
                case 'list' :
                    return $this->listProjects( $payload );

                default :
                    return 899;
            }
        }


        private function getUserProjects( int $sid ) {}

        private function listProjects( $payload )
        {
            if ( ( $payload->sid ?? 0 ) > 0 )
            {
                $a = $this->validAccess();
                // can only list are own projects
                if ( $a->sid === $payload->sid )
                    return ['projects'=>$this->activeProjects( $a->sid )];

                return null;
            }
        }
    }