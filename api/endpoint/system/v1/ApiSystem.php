<?php

namespace api\endpoint\system\v1;

use api\endpoint\ApiBase;

class ApiSystem extends ApiBase
{

    public function run(?array $parts = null,?array $split = null )
    {
        // TODO: Implement run() method.

        $result = match ( $parts[0] ?? false ) {
            'ping' => $this->ping() ? 'pong' : null ,
            default => null
        } ;
        $this->outputResult( $result ) ;

   }

    private function ping() : bool
    {
            try {
                $red = \sys\Redis::getRedis(2);
                if ($red && $red->ping()) {
                    $q = \sys\db\SQL::Row0(" SELECT SQL_NO_CACHE CONNECTION_ID(), NOW(6), DATABASE();");
                    if ($q && empty(\sys\db\SQL::error()) && is_array($q) && count($q) === 3 )
                        return true ;
                }

            } catch (\Throwable $ex) {
                error_log($ex->getMessage());
            }
            return false ;
    }


}