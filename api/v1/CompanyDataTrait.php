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


            $dc = new \sys\db\SqlSource();
            $dcx =$dc->db($db)
                    ->col('c.cid')
                    ->col('c.code')
                    ->col('c.name')
                    ->col('c.postcode')
                    ->col( 'c.d_cr' )
                    ->col( 'c.active' )
                    ->join('comps' , 'c');

            foreach ( $payload->qbar as $q )
            {
                if ( $q['n'] === 'qbar_comp' )
                {
                    switch ( $q['v'] ?? false )
                    {
                        case 1 :
                            $dcx->where('c.active > 0');
                            break;
                        case 2:
                            $dcx->where('c.active = 0');
                            break;
                    }
                }
            }



            $dcx->prepare();
            $result = new \app\model\ApiDataFetch( $dc )->getList( $payload ) ;



            header( 'Content-Type: application/json');
            echo json_encode( $result ?? false );
            exit ;
        }


    }