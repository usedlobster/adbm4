<?php

    namespace app\engine;

    class AppParams {

        private function checkParamValue($v , array $def , &$out) : bool
        {
            $type = $def[ 'type' ] ?? 'string';
            switch ( $type )
            {
                case 'int':
                    $out = (int)$v;
                    if ( !is_numeric($v) )
                        return false;
                    if ( isset($def[ 'min' ]) && $out < $def[ 'min' ] )
                        return false;
                    if ( isset($def[ 'max' ]) && $out > $def[ 'max' ] )
                        return false;
                    return true;

                case 'float':
                    $out = (float)$v;
                    if ( !is_numeric($v) )
                        return false;
                    if ( isset($def[ 'min' ]) && $out < $def[ 'min' ] )
                        return false;
                    if ( isset($def[ 'max' ]) && $out > $def[ 'max' ] )
                        return false;
                    return true;
                case 'size' :
                    // size = 100px | 100% | 100/100 | 100rem
                    $y = str_Replace(' ' , '' , trim($v));
                    if ( str_ends_with($y , '%') && is_numeric(substr($y , 0 , -1)) )
                        return true;
                    else if ( str_ends_with($y , 'px') && is_numeric(substr($y , 0 , -2)) )
                        return true;
                    else if ( str_ends_with($y , 'rem') && is_numeric(substr($y , 0 , -3)) )
                        return true;

                    else
                    {
                        $p = explode('/' , $y , 3);
                        if ( count($p) === 2 && is_numeric($p[ 0 ]) && is_numeric($p[ 1 ]) && $p[ 1 ] > 0 )
                            return true;
                    }

                    return false;

                case 'string':
                    $out = (string)$v;
                    if ( isset($def[ 'maxlen' ]) && strlen($out) > $def[ 'maxlen' ] )
                        return false;
                    return true;

                case 'enum':
                    if ( !isset($def[ 'values' ]) || !in_array($v , $def[ 'values' ]) )
                        return false;
                    $out = $v;
                    return true;
            }

            return false;
        }


        public function getParamsFromString(string $pstr , array $paramsDef) : array
        {
            try
            {
                // Parse the parameter string
                $pattern = '/(\w+)\s*=\s*(?:"([^"]*)"|([^,\s][^,]*))\s*,?/x';
                $result = [];
                preg_match_all($pattern , $pstr , $matches , PREG_SET_ORDER);

                foreach ( $matches as $match )
                {
                    $key = $match[ 1 ];
                    $value = $match[ 2 ] !== '' ? $match[ 2 ] : $match[ 3 ];
                    $result[ $key ] = trim($value);
                }

                $errors = [];
                foreach ( $paramsDef as $key => $def )
                {
                    if ( isset($def[ 'as' ]) )
                        $def = $paramsDef[ $def[ 'as' ] ] ?? false;

                    if ( !$def )
                        $errors [] = 'Invalid parameter definition:'.$key;
                    else
                    {
                        if ( !isset($result[ $key ]) )
                        {
                            if ( $def[ 'required' ] ?? false )
                                $errors[] = "Missing required parameter: $key";
                            elseif ( isset($def[ 'default' ]) )
                            {
                                if ( !$this->checkParamValue($def[ 'default' ] , $def , $v) )
                                    $errors[] = "Invalid default value for $key";
                                else
                                    $result[ $key ] = $v;
                            }
                        }
                        elseif ( !$this->checkParamValue($result[ $key ] , $def , $result[ $key ]) )
                            $errors[] = "Invalid value for $key"; // Changed from = to []

                    }
                }

                return ['err' => $errors , 'res' => $result];
            }
            catch ( \Throwable $ex )
            {
                return ['err' => ['Invalid parameters'] , 'res' => []];
            }
        }

        public function mergeParamArrays(array $p1 , array $p2) : array
        {
            return [
                    'err' => array_merge($p2[ 'err' ] ?? [] , $p1[ 'err' ] ?? []) ,
                    'res' => array_merge($p2[ 'res' ] ?? [] , $p1[ 'res' ] ?? [])
            ];
        }



    }