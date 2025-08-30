<?php

    namespace sys;

    use sys\db\SQL;

    class Audit {

        public static function getFail($idkey , $ft , $iv = '1 minute')
        {
            $fc = SQL::Get0(" select SQL_NO_CACHE count(*) from <DB_FAIL> where ft = ? and idkey = md5(?) and ( t  > now() - interval $iv )  " , [$ft , $idkey]);
            if ( $fc === false || !empty(SQL::error()) )
                return -1;
            return $fc;
        }

        public static function setFail( $idkey , $ft = 0) : bool
        {
            return SQL::Exec( "insert into <DB_FAIL> ( idkey , t , ft ) values( md5(?) , now() ,? )" , [$idkey , $ft]);
        }

        public static function setAttempt( $idkey , $ft = 0) : bool
        {
            return self::setFail( $idkey , $ft);
        }

        public static function failTimeLeft($idkey , $ft = 0 , $iv = '5 minute')
        {
            return SQL::Get0(" select timestampdiff( second ,   now() - interval $iv , max(t) ) from <DB_FAIL> where ft=? and idkey=md5(?) and ( t > now() - interval $iv ) " , [
                    $ft ,
                    $idkey
            ]);
        }

        public static function clearFail($idkey , $ft = 0)
        {
            return SQL::Exec("delete from <DB_FAIL> where idkey = md5(?) and ft = ?" , [$idkey , $ft]);
        }

        public static function failClean()
        {
            SQL::Exec("delete from <DB_FAIL> where t < ( now() - interval 7 day )");
        }


        public static function audit(int $what , int $p1 = 0 , int $p2 = 0 , mixed $data = null)
        {
            /*
            if ( SQL::Exec(
              "insert into adbm4_master.audit ( aid , ref , event , sid , pid , uid , p1 , p2 , str , t  )
             values( null,?,?,?,?,?,?,?,?,now() )" , [
                $object->ref   ?? -1 ,
                $object->event ?? -1 ,
                $object->sid   ?? -1 ,
                $object->pid   ?? -1 ,
                $object->uid   ?? -1 ,
                $object->p1    ?? 0  ,
                $object->p2    ?? 0  ,
                $object->str   ?? '' ] ) === false || !empty( SQL::error()))
                    throw new \RuntimeException( 'audit entry failure' ) ;
            */
        }



    }


