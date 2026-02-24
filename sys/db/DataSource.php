<?php

namespace sys\db;

class DataSource
{

    public string $database;

    private array $_coldefs;

    private array $_joins;

    private string $_from;

    private string $_where;

    private array $_params;

    public array $_order;

    private string $_limit;

    public function __construct()
    {
        $this->reset();
    }

    public function reset() : void
    {
        $this->database = '';
        $this->_coldefs = [];
        $this->_joins = [];
        $this->_from = '';
        $this->_where = '';
        $this->_params = [];
        $this->_order = [];
        $this->_limit = '';
    }

    public function db($name)
    {
        $this->database = $name;
        return $this;
    }

    public function col(string $tablefield, string $alias = '')
    {
        if (empty($tablefield))
            throw new \Exception('datasource field empty');

        // very simple split , [<table>.]field
        $i = mb_strripos($tablefield, '.');
        if ($i !== false && $i > 0) {
            $tbl = mb_substr($tablefield, 0, $i);
            $fld = mb_substr($tablefield, $i + 1);
        }
        elseif ($tablefield[0] !== '.') {
            $tbl = '';
            $fld = $tablefield;
        }
        else
            throw new \Exception('datasource field syntax error');

        $this->_coldefs[] = ['t' => $tbl, 'f' => $fld, 'a' => $alias, 'x' => false];
        return $this; // so we can daisy chain

    }

    public function expr(string $expr, string $alias = '')
    {
        $this->_coldefs[] = ['t' => '', 'f' => '', 'a' => $alias, 'x' => $expr];
        return $this;
    }

    public function join(string $table, string $alias = '', string $condition = '', string $type = ' LEFT JOIN ')
    {
        $this->_joins[] = ['t' => $table, 'a' => $alias, 'cond' => $condition, 'type' => $type];
        return $this;
    }

    public function where(string $cond)
    {
        $this->_where .= ($this->_where ? ' AND ' : ' ').$cond;
        return $this;
    }

    public function order(string $field, string $dir = 'ASC')
    {
        $this->_order[] = [$field, $dir];
        return $this;
    }

    public function limit(int $limit, int $offset = 0)
    {
        // can only have one limit , so just overide
        $this->_limit = " {$offset} , {$limit} ";
        return $this;
    }

    public function param(mixed $value)
    {
        $this->params[] = $value;
        return $this;
    }

    public function close()
    {
        $from = '';
        foreach ($this->_joins as $j) {
            if (!empty($from))
                $from .= ' '.($j['type'] ? : "LEFT JOIN").' ';

            $from .= " {$j['t']} ";
            if ($j['a'] ?? false)
                $from .= " AS `{$j['a']}`";

            if ($j['cond'] ?? false)
                $from .= " ON {$j['cond']}";
        }
        $this->_from = $from;
    }

    public function findField(string $field) : ?array
    {
        if (empty($field))
            return null;

        $maybe = null;
        foreach ($this->_coldefs as $cd) {
            if ($cd['a'] == $field)
                return $cd;
            elseif (($cd['t'].'.'.$cd['f'] === $field))
                $maybe = $cd;
            elseif ($cd['f'] === $field)
                $maybe = $cd;
        }
        return $maybe;
    }

    public function getTerm(array $cdef) : string
    {
        $term = '';
        if (!empty(($x = $cdef['x']) ?? ''))
            $term = '('.$x.')';
        else {
            $term = '';
            if ($cdef['t'])
                $term .= "{$cdef['t']}.";
            $term .= $cdef['f'];
        }

        if (($cdef['a'] ?? false))
            $term .= " AS `{$cdef['a']}`";

        return $term;
    }

    public function getTermField(array $cdef) : string
    {
        $term = '';
        if (!empty(($x = $cdef['x']) ?? ''))
            $term = '('.$x.')';
        else {
            $term = '';
            if ($cdef['t'])
                $term .= "{$cdef['t']}.";
            $term .= $cdef['f'];
        }

        return $term;
    }

    public function getWhereTerm( $field ) : string
    {
        $cdef = $this->findField($field);
        if (!$cdef)
            return '' ;
        return $this->getTermField($cdef);
    }

    public function getOrderTerm(array $cdef) : string
    {
        return $this->getTerm($cdef);
    }

    public function buildCOUNT(array $ext = []) : array
    {
        $sql = ' SELECT '.(($ext['distinct'] ?? false) ? 'DISTINCT ' : '').' COUNT(*) ';

        if ($this->_from)
            $sql .= " FROM {$this->_from}";

        return [$sql, $this->_params];
    }

    public function buildSELECT(array $ext = []) : array
    {
        if (empty($ext['fields'] ?? false))
            return ['', []];

        $sql = ' SELECT '.(($ext['distinct'] ?? false) ? 'DISTINCT ' : '').$ext['fields'];
        // $sql .= ( $ext['fields'] ?? false ) ?: '*' ;
        // $sql .= ($ext['fields'] ?? false) ? : join(', ', array_map([$this, 'getTerm'], $this->_coldefs));

        if ($this->_from)
            $sql .= " FROM {$this->_from}";

        $w = trim($this->_where);
        if (!empty($ext['search'])) {
            $w .= ($w ? ' AND ' : '')." ( {$ext['search']} ) ";

            $sql .= " WHERE {$w} ";
        }

        if (!empty($ext['sort']))
            $sql .= " ORDER BY {$ext['sort']} ";

        if (!empty($ext['limit']))
            $sql .= " LIMIT {$ext['limit']} ";
        elseif (!empty($this->_limit))
            $sql .= " LIMIT {$this->_limit} ";

        return [$sql, $this->_params];
    }

}
