<?php
    
    namespace sys\db;
    
    use \Exception;
    
    class SqlSource
    {
        
        private array  $colnames;
        private array  $coldefs;
        private string $database;
        private array  $params;
        private array  $joins;
        private array  $wheres;
        private array  $orders;
        //
        public string $_db;
        public string $_select;
        public string $_from;
        public string $_where;
        public string $_order;
        public array  $_params;
        
        public function __construct()
        {
            $this->reset();
        }
        
        private function reset()
        {
            $this->colnames = [];
            $this->coldefs  = [];
            $this->database = '';
            $this->params   = [];
            $this->joins    = [];
            $this->wheres   = [];
            $this->orders   = [];
            
            $this->_db     = '' ;
            $this->_select = '' ;
            $this->_from   = '' ;
            $this->_where  = '' ;
            $this->_order  = '' ;
            $this->_params = [] ;
        }
        
        public function db( $name )
        {
            $this->database = $name;
            return $this;
        }
        
        public function col( string $tablefield , string $alias = '' )
        {
            if ( empty( $tablefield ) )
                throw new Exception( 'datasource field empty' );
            // split into table.field
            $i = mb_strripos( $tablefield , '.' );
            if ( $i !== false )
            {
                $tbl = mb_substr( $tablefield , 0 , $i );
                $fld = mb_substr( $tablefield , $i + 1 );
            }
            else
            {
                $tbl = '';
                $fld = $tablefield;
            }
            
            $name = empty( $alias ) ? $fld : $alias;
            if ( in_array( $name , $this->colnames ) )
                throw new \Exception( 'duplicate column name ' );
            
            $this->colnames[] = $name;
            $this->coldefs[ $name ] = [ 't' => $tbl , 'f' => $fld , 'a' => $alias , 'x' => false  ];
            return $this; // chainable
        }
        
        public function expr( string $expr , string $alias )
        {
            if ( empty( $expr ) )
                throw new \Exception( 'empty expression' );
            if ( empty( $alias ) )
                throw new \Exception( 'alias required for expression ' );
            if ( in_array( $alias , $this->colnames ) )
                throw new \Exception( 'duplication expression ' );
            
            $this->colnames []       = $alias;
            $this->coldefs[ $alias ] = [ 't' => false , 'f' => false , 'a' => $alias , 'x' => $expr ];
            
            return $this;
        }
        
        
        public function join( string $table , string $alias , string $condition = '' )
        {
            if ( empty( $table ) || empty( $alias ) )
                throw new \Exception( 'invalid join parameters' );
            
            $this->joins[] = [ $table , $alias , $condition ];
            
            return $this;
            
        }
        
        public function where( $w )
        {
            if ( empty( $w ) )
                throw new \Exception( 'invalid where clause' );
            $this->wheres [] = $w;
            
            return $this;
        }
        
        
        public function param( mixed $value )
        {
            $this->params[] = $value;
            
            return $this;
        }
        
        public function getFromTerm()
        {
            $from = '';
            foreach ( $this->joins as $j )
            {
                if ( ! empty( $from ) )
                    $from .= " LEFT JOIN";
                
                $from .= " {$j[0]} AS {$j[1]} ";
                if ( $j[ 2 ] )
                    $from .= " ON {$j[2]}";
                
            }
            
            return $from;
        }
        
        public function prepare()
        {
            if ( empty( $this->_from ) )
                $this->_from = $this->getFromTerm();
            
            if ( empty( $this->_where ) )
                $this->_where = join( ' AND ' , $this->wheres );
            
            if ( empty( $this->_order ) )
                $this->_order = join( ',' , $this->orders );
            
            if ( empty( $this->_params ) )
                $this->_params = $this->params;
            
        }
        
        
        public function getTerm( $f , $withAlias = true )
        {
            $term  = false;
            $alias = false;
            if ( in_array( $f , $this->colnames ) )
            {
                if ( ( $col = $this->coldefs[ $f ] ?? false ) )
                {
                    if ( $col[ 'x' ] ?? false )
                    {
                        $term  = '('.$col[ 'x' ].')';
                        $alias = $f;
                    }
                    else if ( $col[ 'f' ] ?? false )
                    {
                        $term = ( $col[ 't' ] ?? false ) ?: '';
                        if ( ! empty( $term ) )
                            $term .= '.';
                        $term .= $col[ 'f' ];
                        
                        $alias = $col[ 'a' ] ?? false;
                        
                    }
                }
            }
            
            if ( $term )
            {
                if ( $withAlias && $alias )
                    return "$term AS `$alias`";
                else
                    return $term;
            }
            
            return '';
        }
        
        private function getSQL( array $xp = [] )
        {
            $sql = $xp[ 's' ] ?? 'SELECT *';
            
            SQL::setDB( $this->database );
            $params = $this->_params;
            $from   = $this->_from;
            if ( ! empty( $from ) )
                $sql .= " FROM {$from} ";
            
            $where = $this->_where;
            if ( $xp[ 'w' ] ?? false )
            {
                foreach ( ( $xp[ 'w' ] ?? [] ) as $w )
                {
                    if ( isset( $w[ 0 ] , $w[ 1 ] ) )
                    {
                        if ( ! empty( $w[ 0 ] ) )
                        {
                            if ( ! empty( $where ) )
                                $where .= ' AND ';
                            $where  .= "($w[0])";
                            $params = array_merge( $params , $w[ 1 ] ?? [] );
                            
                        }
                    }
                }
            }
            
            $order = $this->_order;
            if ( $xp[ 'o' ] ?? false )
            {
                foreach ( ( $xp[ 'o' ] ?? [] ) as $o )
                {
                    if ( ! empty( $o ) )
                    {
                        if ( ! empty( $order ) )
                            $order .= ',';
                        $order .= $o;
                    }
                }
            }
            
            $limit = ( $xp[ 'l' ] ?? false ) ?: '';
            
            if ( ! empty( $where ) )
                $sql .= " WHERE {$where} ";
            
            if ( ! empty( $order ) )
                $sql .= " ORDER BY {$order} ";
            
            if ( ! empty( $limit ) )
                $sql .= " LIMIT {$limit}";
            
            return [ $sql , $params ];
        }
        
        public function getCount( array $xp = [] )
        {
            $sql = $this->getSQL( [ 's' => " SELECT COUNT(*)" ] + $xp );
            return SQL::Get0( $sql[ 0 ] ?? '0' , $sql[ 1 ] ?? [] );
        }
        
        public function getAll( array $xp = [] )
        {
            $fields    = ( $xp[ 'f' ] ?? false ) ?: '0';
            $xp[ 's' ] = ( $xp[ 's' ] ?? false ) ?: "SELECT {$fields}";
            $sql       = $this->getSQL( $xp ) ;
            return SQL::GetAllN( $sql[ 0 ] ?? '' , $sql[ 1 ] ?? [] );
        }


        
        
        public function getSelect( $f )
        {
            $fields = [];
            foreach ( ($f ?? []) as $c) {
                $x = $this->getTerm($c, true);
                if ($x)
                    $fields[] = $x;
                else if ( $c[0] === '+' )
                    $fields[] = mb_substr( $c, 1 );
            }
            
            return join(',', $fields);
        }
        
        
        public function getRow( array $xp = [] )
        {
            $fields    = ( $xp[ 'f' ] ?? false ) ?: '0';
            $xp[ 's' ] = ( $xp[ 's' ] ?? false ) ?: "SELECT {$fields}";
            $sql       = $this->getSQL( $xp );
            return SQL::RowN( $sql[ 0 ] ?? '' , $sql[ 1 ] ?? [] );
        }



    }