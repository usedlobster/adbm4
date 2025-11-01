<?php

    namespace api\v1;

    trait CompanyDataTrait
    {


        private function getCompanyList($user , object $payload)
        {
            if ( !$user )
                return false;

            if ( !($db = $user[ 'db' ] ?? false) )
                return false;

            /*
            $dc->db( $user->db )
                    ->col( 'su.email' )
                    ->expr( 'if (su.active > 0 , 4 , 0 ) + if ( c.active > 0, 2 , 0  ) + if( u.active > 0 , 1 , 0)' , 'status' )
                    ->join( 'users' , 'u' )
                    ->param( $user->pid )
                    ->where( '( su.active >=0 and u.active >=0 and c.active >=0 )' )
                    ->prepare();
            */

            $dc = new \sys\db\SqlSource();
            $dc->db($db)
                    ->col('c.cid')
                    ->col('c.code')
                    ->col('c.name')
                    ->col('c.postcode')
                    ->col( 'c.active' )
                    ->join('comps' , 'c')
                    ->prepare();

            $result = new \app\model\ApiDataFetch( $dc )->getList( $payload ) ;



            header( 'Content-Type: application/json');
            echo json_encode( $result ?? false );
            exit ;
        }


    }