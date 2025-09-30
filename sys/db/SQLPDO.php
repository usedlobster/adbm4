<?php

    namespace sys\db;

    use PDO;
    use PDOException;

    class SQLPDO
    {

        private string|false        $_dberror;
        private string              $_dsn;
        private ?PDO                $_pdo;
        private                     $_db;
        private \PDOStatement|false $_stmt;

        public function __construct()
        {
            $this->_dberror = false;
            $this->_dsn = false;
            $this->_db = false;
            $this->_pdo = null;
        }


        private function makePDO( )
        {
            if ( empty($this->_dsn) )
            {
                $db = $this->_db ?? 'adbm4_master';
                $this->_dsn = "mysql:host=127.0.0.8;port=8001;dbname={$db};charset=utf8mb4";
                $this->_pdo = null ;
            }

            if ( !$this->_pdo )
            {
                try
                {
                    $this->_pdo = new PDO($this->_dsn , 'adbm4' , $_ENV['DB_PASS'] , [
                            PDO::ATTR_PERSISTENT => true ,
                            PDO::ATTR_EMULATE_PREPARES => false ,
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);
                }
                catch ( PDOException $e )
                {
                    sleep(0 ) ;
                    $this->_pdo = null ;
                    // echo 'Database connection error: ' . $e->getMessage() ;
                    error_log( $e ) ;
                    echo '<h1>Database connection error</h1>' ;
                    echo '<h2>Please try again in a few minutes</h2>' ;
                    echo '<a href="/">Home</a>' ;
                    exit ;
                }
            }


            return $this->_pdo;
        }

        const DB_TOKENS = ['<DB>','<DB_FAIL>' ] ;
        const DB_TABLES = ['adbm4_master','adbm4_master.sys_fail'] ;

        private function _exec($qry , $args) : bool
        {
            // clear error each execution
            $this->_dberror = false;
            try
            {
                $pdo = $this->_pdo ?: $this->makePDO();
                $qry = str_replace( self::DB_TOKENS , self::DB_TABLES , $qry );
                $this->_stmt = $pdo->prepare($qry);
                if ( $this->_stmt !== false )
                    return $this->_stmt->execute($args);
            }
            catch ( PDOException $e )
            {
                $this->_dberror = $e->getMessage() ?? true ;
            }

            return false;
        }

        public function setDB($db)
        {
            if ( $this->_db !== $db )
            {
                $this->_db = $db;
                $this->_dsn = '';
                $this->_pdo = null;
            }
        }

        public function error() : string|false
        {
            return $this->_dberror;
        }

        /*
         * Functions
         */

        public function Get0($sql , $args = []) : mixed
        {
            return ($this->_exec($sql , $args)) ? $this->_stmt->fetchColumn(0) : false;
        }

        public function GetAll0($sql , $args = []) : mixed
        {
            return ($this->_exec($sql , $args)) ? $this->_stmt->fetchAll(PDO::FETCH_NUM) : false;
        }

        public function GetAllN($sql , $args = [])
        {
            return ($this->_exec($sql , $args)) ? $this->_stmt->fetchAll(PDO::FETCH_NAMED) : false;
        }

        public function Row0($q , $args = [])
        {
            return ($this->_exec($q , $args)) ? $this->_stmt->fetch(PDO::FETCH_NUM) : false;
        }

        public function RowN($q , $args = [])
        {
            return ($this->_exec($q , $args)) ? $this->_stmt->fetch(PDO::FETCH_NAMED) : false;
        }

        public function Col($q , $n , $args = [])
        {
            return ($this->_exec($q , $args)) ? $this->_stmt->fetchAll(PDO::FETCH_COLUMN , $n) : false;
        }


        public function Exec($sql , $args = []) : bool
        {
            return ($this->_exec($sql , $args));
        }

        public function Insert($sql , $args = [])
        {
            if ( $this->_exec($sql , $args) )
                return $this->_pdo->lastInsertId();

            return false;
        }

        public function ExecTree($parentKey , $itemKey , $table , $where , $id , $sql , $args = []) : bool
        {
            // recursive executeTrait function
            // NB: itemKey,wdtableform,parentKey,where  - must not be provided by user input
            if ( !empty($where) )
                $w = ' and ( '.$where.' )';
            else
                $w = '';

            $a = self::GetAll0(" select {$itemKey} from {$table} where {$parentKey}=? $w  " , [$id]);
            if ( is_array($a) )
            {
                foreach ( $a as $p )
                {
                    if ( $p[ 0 ] != $id )
                        $this->ExecTree($parentKey , $itemKey , $table , $where , $p[ 0 ] , $sql , $args);
                }
            }
            else
                return false; // important - otherwise we delete all the parents  as well

            if ( is_string($sql) )
            {
                if ( !$this->_exec($sql , array_merge([$id] , $args)) )
                    return false;
            }
            return true;
        }

        public function beginTransaction() : bool
        {
            return $this->makePDO()->beginTransaction();
        }

        public function commit() : bool
        {
            return $this->makePDO()->commit();
        }

        public function rollBack() : bool
        {
            return $this->makePDO()->rollBack();
        }

        //
        public function CopyTree(
                $srcId ,
                $dstId ,
                $itemKeyName ,
                $parentKeyName ,
                $tblName ,
                $tblOtherColumnNames ,
                $whereOther = '' ,
                $args = []
        ) : bool|string {
            $w = " where ( $itemKeyName = ? ) ";
            // append additional where
            if ( !empty($whereOther) )
                $w .= " and ( $whereOther ) ";


            if (
                    ($newId = self::Insert(<<<SQL
                                               insert into $tblName  ($itemKeyName , $parentKeyName , $tblOtherColumnNames)
                                                   select null,?,$tblOtherColumnNames
                                                       from $tblName $w
                                               SQL, array_merge([$dstId , $srcId] , $args))) !== false
            )
            {
                $w2 = " where ( $parentKeyName = ? ) ";
                if ( !empty($whereOther) )
                    $w2 .= " and ( $whereOther ) ";

                $nodes = self::GetAll0($Q = " select $itemKeyName from $tblName $w2 " , array_merge([$srcId] , $args));
                if ( is_array($nodes) )
                {
                    foreach ( $nodes as $node )
                    {
                        if ( !$this->CopyTree($node[ 0 ] , $newId , $itemKeyName , $parentKeyName , $tblName , $tblOtherColumnNames , $whereOther , $args) )
                            return false;
                    }
                }

                return $newId;
            }

            return false;
        }
    }