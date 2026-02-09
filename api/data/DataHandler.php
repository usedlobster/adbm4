<?php

namespace api\data;

use sys\db\SQL;

class DataHandler
{

    public function __construct() {}

    private function getF(\sys\db\DataSource $dc, $field ) : string
    {
        $def = $dc->findField($field);
        if ($def) {
            $x = $dc->getTerm($def);
            if ($x)
                return $x;
        }

        return '""';
    }

    private function getX(\sys\db\DataSource $dc, $field ) : string
    {
        $def = $dc->findField($field);
        if ($def) {
            $x = $dc->getTermField($def);
            if ($x)
                return $x;
        }

        return '""';
    }


    private function getSelect($dc, $p) : string
    {
        $s = [];

        foreach (array_column($p->defs, 'field') as $field) {
            $s[] = $this->getF($dc, $field);
        }


        return join(',', $s);
    }

    private function getLimit($p) : string
    {
        if (isset($p->offset, $p->limit))
            return "{$p->offset} , {$p->limit}";

        return "";
    }

    private function getSort($dc, $p) : string
    {

        $seen = [];
        $o = [];
        foreach ($dc->_order as $oo) {
            $n = $oo[0];
            if (!in_array($n, $seen)) {
                $seen[] = $n;
                //
                if ($n === ':') {
                    foreach ($p->defs as $d) {

                        if (($d->sortable ?? true) && ($d->sort ?? 0) !== 0) {

                            //$ff = $this->getF($dc, $d->field);
                            $ff = $dc->findField($d->field);
                            if ( $ff && !in_array($ff, $seen)) {


                                $t = $this->getX($dc, $d->field);

                                    $o[] = [$t, $d->sort < 0 ? 'DESC' : 'ASC'];
                                    $seen[] = $ff;

                            }
                        }
                    }
                }
                elseif (!empty($n)) {
                  $o[] = $oo;
                }
            }
        }


        return join( ',' , array_map(fn($x) => $x[0] = "{$x[0]} {$x[1]}", $o)) ;

    }

    public function getTable(\sys\db\DataSource $dc, $p)
    {
        $ext = [
            'fields' => $this->getSelect($dc, $p),
            'limit'  => $this->getLimit($p),
            'sort'   => $this->getSort($dc, $p),
        ];

        if ($dc->database)
            \sys\db\SQL::setDB($dc->database);

        [$sql, $params] = $dc->buildCOUNT($ext);
        $n = \sys\db\SQL::Get0($sql, $params ?? []);

        if ($n > 0 && empty( \sys\db\SQL::error())) {

            [$sql, $params] = $dc->buildSELECT($ext);
            $data = \sys\db\SQL::GetAll0($sql, $params ?? []);
            if ( empty(\sys\db\SQL::error())) {
                return ['data' => $data, 'end' => $n] ;
            }
        }

        return ['error'=>\sys\db\SQL::error()];


    }

}