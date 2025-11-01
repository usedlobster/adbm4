<?php

    namespace api\v1;

    class CompaniesApi extends \api\ApiBaseClass {

        // deal with all company related api calls

        use CompanyDataTrait ;

        public function run($args,$payload)
        {
            try
            {
                $user = parent::getValidUser();
                if ( !$user)
                    throw new \Exception('Unknown User');

                $companies = $this->getCompanyList($user, (object)$payload ) ;





            }
            catch (\Exception $e)
            {
                http_response_code(400) ;
                header( 'Content-Type: application/json' ) ;
                echo json_encode( ['error'=>(string)$e->getMessage()] );
                exit ;
            }


            header( 'Content-Type: application/json' ) ;
            echo json_encode( $result ?? false );


        }


    }