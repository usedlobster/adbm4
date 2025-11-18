<?php

    namespace app\model;

    use sys\db\SQL;

    class ApiDataFetch {


        private \sys\db\SqlSource $dc;

        public function __construct(\sys\db\SqlSource $dc) {
            $this->dc = $dc ;
        }

        private function getSelect( $dc , $p ) {

            $fields = [];
            foreach ( ($p->col ?? []) as $c )
            {
                $x = $dc->getTerm($c['f'] ?? false , true);
                if ( $x )
                    $fields[] = $x;
            }
            if ( empty($fields))
                return '*' ;

            return join(',' , $fields);
        }


        private function getSearch($dc , $p)
        {
            $w = '';
            $wp = [];

            $sTerm = $p->sterm ?? '';
            if ( !empty($sTerm) && mb_strlen($sTerm) < 500 )
            {
                // escape search term as string
                $sp = '%' .str_replace(['%' , '_'] , ['\%' , '\_'] , $sTerm).'%' ;

                foreach ( ($p?->col ?? []) as $c )
                {
                    // searchable and visible ( otherwise dont search )
                    // assume text fields
                    if ( ($c['searchable'] ?? false) && ($c['vis'] ?? false) )
                    {
                        $t = $dc->getTerm($c['f'] ?? false , false);
                        if ( $t !== false )
                        {
                            if ( $w != '' )
                                $w .= ' OR ';
                            $w .= " ({$t} LIKE ?) ";
                            $wp[] = $sp;
                        }
                    }
                }
            }

            return [$w , $wp]; // where + parameters

        }

        private function getLimit( $p)
        {
            if ( isset($p->offset , $p->limit) && ($p->limit ?? -1) >= 0 )
                return intval($p->offset).','.intval($p->limit);
            return '';
        }

        private function getOrder($dc , $p)
        {
            $ord = [];
            // create order clause
            foreach ( ($p->col ?? []) as $c )
            {
                if ( $c['sortable'] ?? false )
                {
                    $sort = $c[ 'sort' ] ?? 0;
                    if ( $sort !== 0 )
                    {
                        $t = $dc->getTerm($c[ 'f' ] ?? false , false);
                        if ( !empty($t) )
                        {
                            if ( $sort > 0 )
                                $ord[] = $t.' ASC';
                            else
                                $ord[] = $t.' DESC';
                        }
                    }
                }
            }

            return join(',' , $ord);
        }



        public function getList( $payload )
        {


            $sFields = $this->getSelect($this->dc , $payload);
            $sFilter = $this->getSearch($this->dc , $payload);
            $sLimit  = $this->getLimit( $payload);
            $sOrder  = $this->getOrder($this->dc , $payload);
            $nFilter = $this->dc->getCount(['w' => [$sFilter]]);
            $req = ['f' => $sFields , 'w' => [$sFilter] , 'o' => [$sOrder] , 'l' => $sLimit] ;
            $data = $this->dc->getAll($req);


            return (object)[
                    'refresh' => $payload->refresh ?? -1 ,
                    'total' => $nFilter ,
                    'data' => $data ] ;
        }

    }



