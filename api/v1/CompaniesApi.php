<?php

    namespace api\v1;

    class CompaniesApi extends \api\ApiBaseClass {

        // deal with all company related api calls

        use CompanyDataTrait ;

        public function run($args,$payload)
        {
//
//            try
//            {
//                $user = parent::getValidUser();
//                if ( !$user)
//                    throw new \Exception('Unknown User');
//
//
//               $result = false ;
//               $list = $this->getCompanyList($user, (object)$payload ) ;
//               if ( $args[0] === 'checkcode') {
//                   if ( $list?->data )
//                        $result = array_column( $list->data , 1 ) ;
//
//               }
//
//            }
//            catch (\Exception $e)
//            {
//                http_response_code(400) ;
//                header( 'Content-Type: application/json' ) ;
//                echo json_encode( ['error'=>(string)$e->getMessage()] );
//                exit ;
//            }
//
//
//            header( 'Content-Type: application/json' ) ;
//            echo json_encode( $result ?? false );
//
            $result = false ;
            try
            {
                switch ( $args[ 0 ] )
                {
                    case 'codelist':
                        $this->sendResult(['ALL','TWO','GOOD']);
                }
            }
            catch( \Exception $e ) {
                $this->sendError($e->getMessage());
                exit ;
            }


        }


    }