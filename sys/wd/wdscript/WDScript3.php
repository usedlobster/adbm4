<?php


namespace sys\wd\wdscript;

use http\Exception\RuntimeException;

class WDScript3 {

    private array $_opcodes;
    private ?object $_lexer = null ;
    private array $_vars = [];
    public array $_data = [];

    const string TOKEN_PATTERN = '/"(?:[^"\\\\]|\\\\.)*"|' .         // double-quoted strings
                                 '\'(?:[^\'\\\\]|\\\\.)*\'|' .       // Single-quoted strings
                                 '%\[|' .                            // Literal %[
                                 ']%;|' .                           // Literal ]%;
                                 ']%|' .                            // Literal ]%
                                 '\/\/|' .                           // Inline comments
                                 '\n|' .                             // Newline as token
                                 '[^\s%\[\]]+|' .                    // Non-whitespace, non-special characters
                                 '\s+/u';

    const array RPN_GRAMMAR = [
        '+' => [ 'p' => -6 ] ,
        '-' => [ 'p' => -6 ] ,
        '*' => [ 'p' => -5 ] ,
        '/' => [ 'p' => -5 ] ,
        '<' => [ 'p' => -9 ] ,
        '<=' => [ 'p' => -9 ] ,
        '>' => [ 'p' => -9 ] ,
        '>=' => [ 'p' => -9 ] ,
        '&&' => [ 'p' => -14 ] ,
        '||' => [ 'p' => -15 ] ,
        '++' => [ 'u' => true , 'up' => 0 ] , // postfix so a++
        ':=' => [ 'p' => -100 , 'a' => 1 ] ,  // assignment x:=4
         'let' => [ 'u' => true , 'up' => 0 ] ,
        // functions
        '@map' => [ 'fn' =>true ] ,
    ];

    public function compile( $content , $name = ''  ) : void {


        /*
        if ( $name !== '' ) {
            $red = \sys\Redis::getRedis(0); // get app redis store
            if ($red) {
                $rkey = 'wdscript:' . $name . ':' . hash('sha256', $content);
                $z = \unserialize($red->get($rkey) ?? false);
                if (is_array($z)) {
                    $this->_opcodes = $z;
                    return;
                }
            }
        }
        */

        $this->_opcodes = [] ;
        preg_match_all(self::TOKEN_PATTERN, $content ?? '', $tokens, PREG_SPLIT_NO_EMPTY);
        if ( is_array( $tokens[0] ?? false )) {
            $tkptr = 0 ;
            if ( $this->encodeLevel( $tokens[0] , $tkptr , 0 ) !== 0 )
                throw new \RuntimeException('Failed to encode script level');
        }
        // keep content in cache
        if ( isset($red,$rkey) && !empty( $rkey ) )
            $red->setex( $rkey , 901 ,  \serialize( $this->_opcodes ) ) ;
    }
    private function addStr( string &$buf , int $level ) : void
    {
        if ( $buf !== '' )
        {
            if ($level === 0)
                $this->_opcodes[] = ['str' => $buf, 'lvl' => $level];
            else {
                $cmd = trim( $buf , " \n\r\t\v" ) ;
                $rpn = $this->encodeExpr( $cmd , $this->_vars );
                if ( isset( $rpn[ 'error' ] ) )
                    throw new \RuntimeException( $rpn[ 'error' ] );
                else
                    $this->_opcodes[] = [ 'rpn' => $rpn , 'lvl' => $level ];
            }
            $buf = '';
        }
    }
    private function getLexer() : ?object
    {
        try
        {
            if ( $this->_lexer === null )
            {
                $tokenNames = array_keys( self::RPN_GRAMMAR );

                // get tokens that need escaping
                $escapedTokens = array_filter( $tokenNames , function ( $t )
                {
                    return preg_quote( $t , '/' );
                } );

                // sort by length , so tokeniser can determine difference between >> and > for example.
                usort( $escapedTokens , function ( $a , $b )
                {
                    return strlen( $b ) <=> strlen( $a );
                } );

                // actually escape them
                $escapeAble = array_map( function ( $et )
                {
                    return preg_quote( $et , '/' );
                } , $escapedTokens );

                $lexer            = new \stdClass();
                $lexer->tokens    = [];
                $lexer->functions = [];
                $lexer->unary     = [];

                foreach ( $tokenNames as $k )
                {
                    $g = self::RPN_GRAMMAR[ $k ];

                    if ( isset( $g[ 'fn' ] ) )
                        $lexer->functions[ $k ] = $g;

                    if ( isset( $g[ 'u' ] ) )
                        $lexer->unary[] = $k;

                    $lexer->tokens[ $k ] = [
                        'p' => $g[ 'p' ] ?? 0 ,                       // precedence
                        'up' => ( $g[ 'up' ] ?? $g[ 'p' ] ?? 0 ) ,    // unary precedence
                        'a' => $g[ 'a' ] ?? -1 ,                      // associativity default left->right
                        'ua' => ( $g[ 'ua' ] ?? $g[ 'a' ] ?? 1 )      // unary associativity
                    ];
                }

                //
                $lexer->pattern = '/"[^"]*"|' . '\'[^\']*\'|' . '[a-z]*:|' . '[A-Za-z_][A-Za-z_0-9]*(\.[A-Za-z_0-9]*|' . '\([^\)]*\))*|' . '\d*\.?\d+|' . '\(|\)|' . ',|' . join( '|' ,
                        $escapeAble ) . '|[^\s]/u';
                $this->_lexer   = $lexer;
            }
        }
        catch ( \Exception $ex )
        {
            throw new \Exception( "Error creating lexer : " . $ex->getMessage() , $ex->getCode() , $ex );
        }

        return $this->_lexer;
    }


    private function encodeExpr( string $expr , array &$vars = [] )
    {
        try
        {
            $lex = $this->getLexer();
            preg_match_all( $lex->pattern , $expr , $matchedTokens , PREG_UNMATCHED_AS_NULL );
            $tokens       = $matchedTokens[ 0 ] ?? [];
            $opstack      = [];
            $output       = [];
            $bracketCount = 0;
            $i            = 0;
            $prev         = null;
            $tokenCount   = count( $tokens );
            while ( $i < $tokenCount )
            {
                // get next token
                $token = $tokens[ $i++ ];
                if ( $token === null )
                    break;
                elseif ( $token === '' )
                    continue;

                if ( $token === '(' )
                {
                    $opstack[] = [ 't' => ( $prev = 'par_open' ) , 'v' => $token ];
                    $bracketCount++;
                }
                elseif ( $token === ')' )
                {
                    $bracketCount--;
                    if ( $bracketCount < 0 )
                        throw new \RuntimeException( 'Too many closing parentheses' );
                    else
                    {
                        while ( !empty( $opstack ) && end( $opstack )[ 't' ] !== 'par_open' )
                        {
                            $output[] = array_pop( $opstack );
                        }

                        if ( empty( $opstack ) )
                            throw new \RuntimeException( 'stack empty @' );

                        array_pop( $opstack );
                        $opTop = end( $opstack );
                        if ( $opTop && $opTop[ 't' ] === 'fn' )
                            $output[] = array_pop( $opstack );

                        $prev = 'par_close';
                    }
                }
                elseif ( $token === ',' )
                {
                    while ( !empty( $opstack ) )
                    {
                        $opTop = end( $opstack );
                        if ( $opTop[ 't' ] !== 'par_open' )
                            $output[] = array_pop( $opstack );
                        else
                            break;
                    }
                    $prev = ',';
                }
                elseif ( $token === 'let' )
                    $output[] = [ 't' => 'let' , 'v' => 0 ];
                elseif ( isset( $lex->functions[ $token] ) )
                {
                    // its a function
                    $opstack[] = [ 't' => ( $prev = 'fn' ) , 'v' => $token ];
                    $output[]  = [ 't' => 'args' , 'v' => 0 ];
                }
                else
                {
                    $fc = $token[ 0 ];
                    $lc = substr( $token , -1 );
                    if ( ( $fc === '"' && $lc === '"' ) || ( $fc === '\'' && $lc === '\'' ) )
                        $output[] = [ 't' => ( $prev = 'str' ) , 'v' => substr( $token , 1 , -1 ) ];
                    else
                    {
                        $stripToken = str_replace( ' ' , '' , $token );
                        if ( is_numeric( $stripToken ) )
                        {
                            $output[] = [ 't' => 'num' , 'v' => (float)$stripToken ];

                            // Apply any pending unary operations
                            while ( !empty( $pendingUnaryOps ) )
                            {
                                $output[] = [ 't' => 'uop' , 'v' => array_pop( $pendingUnaryOps ) ];
                            }

                            $prev = 'num';
                        }
                        elseif ( in_array( $token , $lex->unary ) && ( $prev === null || in_array( $prev , [ 'par_open' , ',' , 'var' , 'op' ] ) ) === false )
                        {
                            // is a unary operator
                            // mark to apply the unary operation on the next number or variable
                            $op1 = $lex->tokens[ $token ];
                            while ( !empty( $opstack ) )
                            {
                                $top = end( $opstack );
                                if ( !$top || $top[ 't' ] === 'par_open' )
                                    break;
                                $op2 = $lex->tokens[ $top[ 'v' ] ];
                                if ( $op2[ 'p' ] > $op1[ 'up' ] || ( $op1[ 'up' ] === $op2[ 'p' ] && $op1[ 'ua' ] < 0 ) )
                                    $output[] = array_pop( $opstack );
                                else
                                    break;
                            }
                            $opstack[] = [ 't' => ( $prev = 'uop' ) , 'v' => $token ];
                        }
                        elseif ( isset( $lex->tokens[ $token ] ) )
                        {
                            $op1 = $lex->tokens[ $token ];
                            while ( !empty( $opstack ) )
                            {
                                $top = end( $opstack );
                                if ( !$top || $top[ 't' ] === 'par_open' )
                                    break;

                                $op2 = $lex->tokens[ $top[ 'v' ] ];
                                if ( $op2[ 'p' ] > $op1[ 'p' ] || ( $op1[ 'p' ] === $op2[ 'p' ] && $op1[ 'a' ] < 0 ) )
                                    $output[] = array_pop( $opstack );
                                else
                                    break;
                            }
                            $opstack[] = [ 't' => ( $prev = 'op' ) , 'v' => $token ];
                        }
                        elseif ( ctype_alpha( $fc ) )
                        {
                            if ( str_ends_with( $token , ':' )) {
                                $output[] = [ 't'=>'np' , 'v'=>substr($token , 0 , -1 )] ;
                            }
                            else {
                                // we have a variable name
                                $vbase = explode('.', $token)[0] ?? $token;
                                if (!in_array($vbase, $vars))
                                    $vars[] = $vbase;

                                $output[] = ['t' => 'var', 'v' => $token];
                                while (!empty($pendingUnaryOps)) {
                                    $output[] = ['t' => 'uop', 'v' => array_pop($pendingUnaryOps)];
                                }

                                $prev = 'var';
                            }
                        }
                        else
                            throw new \RuntimeException( ' unknown token: `' . print_r($token,true) . '`' );
                    }
                }
            }

            while ( ( $op = array_pop( $opstack ) ) !== null )
            {
                if ( $op[ 't' ] === '(' )
                    throw new \Exception( "unclosed (" );
                else
                    $output[] = $op;
            }

            return $output;
        }
        catch ( \Exception $ex )
        {
            return [ 'error' => $ex->getMessage() ];
        }
    }
    private function encodeLevel( array $tokens , int &$ptr , int $level ) : int {

        $buf = '' ;
        while ( ( $t = $tokens[ $ptr++ ] ?? false ) !== false )
        {
            if ( $t === '%[' )
            {
                $this->addStr( $buf , $level );
                return $this->encodeLevel( $tokens , $ptr , $level + 1 );
            }
            elseif ( $t === ']%' )
            {
                $this->addStr( $buf , $level );
                return $this->encodeLevel( $tokens , $ptr , $level - 1 );
            }
            else
                $buf .= $t ;

        }
        $this->addStr( $buf , $level ) ;
        return $level ;
    }
    private function expandVar( string $vname ) : ?array
    {
        // cacheable?
        $vin  = explode( '.' , $vname );
        $vout = [];
        foreach ( $vin as $k )
        {
            // are we a simple variable name
            if ( preg_match( '/^[a-zA-Z0-9_]+$/' , $k ) === 1 )
                $vout[] = $k;
            elseif ( preg_match( '/^\(([^()]*)\)$/' , $k , $bc ) === 1 )
            {
                // have expression inside ( )
                $expr = $bc[ 1 ] ?? '';
                if ( empty( $expr ) )
                    throw new \RuntimeException( 'empty expression : ' );
                elseif ( ctype_digit( $expr ) )
                    $vout[] = (int)$expr; // simple expression is just number
                elseif ( preg_match( '/^[a-zA-Z0-9_]+$/' , $expr ) === 1 )
                    $vout[] = [ [ 't' => 'var' , 'v' => $expr ] ]; // fake rpn , as it's just a variable name inside
                else
                {
                    // have an expression inside
                    $v   = [];
                    $rpn = $this->encodeExpr( $expr , $v );
                    if ( isset( $rpn[ 'error' ] ) )
                        throw new \RuntimeException( 'invalid expression : ' . $expr );
                    // save rpn of expression inside bracket.
                    $vout[] = $rpn;
                }
            }
            else
                return throw new \RuntimeException( 'invalid variable name : ' . $vname );
        }

        return $vout;
    }
    private function getVar( string $vname ) : array
    {
        $dx = $this->expandVar( $vname );
        $d  = $this->_data ?? null;
        foreach ( $dx as $k )
        {
            if ( !$d )
                break;

            if ( !is_array( $k ) )
                $d = $d[ $k ] ?? null;
            else
            {
                $res = $this->evalRPN( $k ) ?? null;
                if ( isset( $res[ 'error' ] ) )
                    throw new \RuntimeException( 'bad rpn :' . $res[ 'error' ] . ' ' . $vname );

                $v = $res[ 0 ][ 'v' ] ?? null;
                if ( $v !== null )
                    $d = $d[ $v ] ?? null;
                else
                    throw new \RuntimeException( 'bad idx :' . ( $res[ 'v' ] ?? '' ) . '  in ' . $vname );
            }
        }

        if ( is_array( $d ) )
            return $d;
        elseif ( is_numeric( $d ) )
            return [ 't' => 'num' , 'v' => (float)$d ];
        elseif ( is_string( $d ) )
            return [ 't' => 'str' , 'v' => $d ];
        elseif ( is_bool( $d ) )
            return [ 't' => 'num' , 'v' => $d ? 1 : 0 ];

        return [ 't' => 'undef' , 'v' => null , 'n' => $vname ];
    }
    private function setVar( string $vname , $value )
    {
        $dx = $this->expandVar( $vname );
        $d  = &$this->_data;
        foreach ( $dx as $k )
        {
            if ( !isset( $d[ $k ] ) )
                $d[ $k ] = [];
            $d = &$d[ $k ];
        }
        $d = $value;
    }
    private function runFn( $fn , array $args , ?callable $onFn = null ) {
        return match( $fn ) {
            '@map'   => $onFn ? ($onFn($fn,$args)) : '' ,
            default => ['error'=>'Unknown function:' . $fn ]
        } ;
    }

    private function evalRPN( array $rpn , ?callable $onFn = null )
    {
        $stack = [];
        try
        {
            reset( $rpn );
            while ( ( $opcode = current( $rpn ) ) !== false )
            {
                $ans = null;
                $op  = $opcode[ 't' ] ?? false;
                switch ( $op )
                {
                    case 'num':
                        $ans = $opcode;
                        break;
                    case 'str' :
                        $ans = $opcode;
                        break;
                    case 'bool' :
                        $ans = $opcode;
                        break;
                    case 'var' :
                        $vn         = $opcode[ 'v' ] ?? '';
                        $ans        = $this->getVar( $vn );
                        $ans[ 'n' ] = $vn;
                        break;
                    case 'let' :
                        $v = next( $rpn );
                        if ( ( $v[ 't' ] ?? false ) !== 'var' )
                            throw new \RuntimeException( 'invalid assign' . ( $v[ 't' ] ?? '' ) );
                        $this->setVar( $v[ 'v' ] ?? '' , null );
                        array_push( $stack , $v );
                        break;
                    case 'args' :
                        array_push( $stack , ['t'=>'top']  ) ;
                        break ;
                    case 'fn' :
                        // stack should have all the arguments upto top
                        // but we scan forward ,
                        $args = [] ;
                        $v1 = array_pop( $stack ) ;
                        while ( $v1  && ( $v1['t'] ?? '' ) !== 'top' ) {
                            $v1t = $v1['t'] ?? ''    ; // type of value
                            $v1v = $v1['v'] ?? false ; // actual value
                            if ( $v1v === false || ( $v1t !== 'str' && $v1t !== 'num' ))
                                throw new \RuntimeException( 'invalid argument type' ) ;
                            // lookahead next on stack
                            $v2 = array_pop( $stack ) ?? false ;
                            if ( $v2 && ( $v2['t'] ?? '' )  === 'np' ) {
                                // its a named parameter
                                $vn = $v2['v'] ?? false ;
                                if ( $vn && ctype_alnum( $vn ) )
                                    $args[$vn] = $v1v ;
                                else
                                    throw new \RuntimeException( 'invalid argument name' ) ;
                                $v1 = array_pop( $stack ) ;
                            }
                            else {
                                $args[] = $v1v ;
                                $v1 = $v2 ;
                            }

                        }
                        // args are no reversed , but function can deal with that
                        $ans = $this->runFn( $opcode['v'] , array_reverse( $args ) , $onFn );
                        break ;
                    case 'np' :
                        array_push( $stack , $opcode ) ;
                        break ;
                    case 'uop' :
                        // unary ops ( other than -x or +x )
                        switch ( $opcode[ 'v' ] ?? false )
                        {
                            case '++' :
                                if ( ( ( $a1 = array_pop( $stack ) ) === null ) || !isset( $a1[ 'n' ] ) )
                                    throw new \RuntimeException( 'not rvalue for ++' );
                                if ( $a1[ 't' ] !== 'num' )
                                    throw new \RuntimeException( '++ rvalue is not number' );
                                $this->setVar( $a1[ 'n' ] , $a1[ 'v' ] + 1.0 );
                                break;
                        }

                        break;
                    case 'op' :
                        // pure binary op's a2 (op) a1
                        if ( ( ( $a1 = array_pop( $stack ) ) === null ) || !isset( $a1[ 'v' ] ) )
                            throw new \RuntimeException( 'missing rhs ' . $a1 );
                        if ( ( ( $a2 = array_pop( $stack ) ) === null ) || !isset( $a2[ 'v' ] ) )
                            throw new \RuntimeException( 'missing lhs ' . $a2 );
                        switch ( $opcode[ 'v' ] ?? false )
                        {
                            case ':=' :
                                if ( $a2[ 't' ] !== 'var' )
                                    throw new \RuntimeException( 'must assign to var' );
                                $this->setVar( $a2[ 'v' ] ?? '' , $a1 );
                                break;
                            case '+' :
                                // only concat two strings / two numbers , mixed not allowed
                                if ( $a2[ 't' ] === 'str' || $a1[ 't' ] === 'str' )
                                    $ans = $a2[ 'v' ] . $a1[ 'v' ];
                                elseif ( $a2[ 't' ] === 'num' )
                                    $ans = $a2[ 'v' ] + $a1[ 'v' ];
                                else
                                    throw new \RuntimeException( 'invalid must be str+str or num+num' );
                                break;
                            case '-' :
                                $ans = $a2[ 'v' ] - $a1[ 'v' ];
                                break;
                            case '*' :
                                $ans = $a2[ 'v' ] * $a1[ 'v' ];
                                break;
                            case '/' :
                                $ans = $a2[ 'v' ] / $a1[ 'v' ];
                                break;
                            case '<':
                                $ans = [ 't' => 'bool' , 'v' => ( ( $a2[ 'v' ] < $a1[ 'v' ] ) ? 1 : 0 ) ];
                                break;
                            case '<=':
                                $ans = [ 't' => 'bool' , 'v' => ( ( $a2[ 'v' ] <= $a1[ 'v' ] ) ? 1 : 0 ) ];
                                break;
                            case '>=':
                                $ans = [ 't' => 'bool' , 'v' => ( ( $a2[ 'v' ] >= $a1[ 'v' ] ) ? 1 : 0 ) ];
                                break;
                            case '>':
                                $ans = [ 't' => 'bool' , 'v' => ( ( $a2[ 'v' ] > $a1[ 'v' ] ) ? 1 : 0 ) ];
                                break;
                            case '&&' :
                                $ans = [ 't' => 'bool' , 'v' => ( ( $a2[ 'v' ] && $a1[ 'v' ] ) ? 1 : 0 ) ];
                                break;
                            case '||' :
                                $ans = [ 't' => 'bool' , 'v' => ( ( $a2[ 'v' ] || $a1[ 'v' ] ) ? 1 : 0 ) ];
                                break;
                            default:
                                throw new \RuntimeException( 'n/i op : ' . ( $opcode[ 'v' ] ?? '' ) );
                        }
                        break;
                    default:
                        throw new \RuntimeException( 'invalid opcode : ' . $op );
                }

                if ( $ans !== null )
                {
                    if ( isset( $ans[ 'error' ] ) )
                        throw new \RuntimeException( $ans[ 'error' ] );
                    elseif ( is_numeric( $ans ) )
                        array_push( $stack , [ 't' => 'num' , 'v' => $ans ] );
                    elseif ( is_string( $ans ) )
                        array_push( $stack , [ 't' => 'str' , 'v' => $ans ] );
                    elseif ( is_array( $ans ) && isset( $ans[ 't' ] ) )
                        array_push( $stack , $ans );
                    else
                        array_push( $stack , ['error'=> print_r($ans , true )]) ;

                }

                next( $rpn );
            }
        }
        catch ( \Throwable $ex )
        {
            return [ 'error' => $ex->getMessage() ];
        }

        return $stack;
    }
    public function output( ?callable $onOut = null , ?callable $onFn = null )
    {
        //
        $t1 = time();
        $pc = 0;
        $step = 5000;
        while (($op = $this->_opcodes[$pc++] ?? null) !== null && (--$step) > 0) {
            if ((time() > $t1 + 20))
                throw new \RuntimeException('email render timeout');
            // echo '<pre>' ,htmlspecialchars( print_r($op,true)) , '</pre><br />' ;
            if ( isset( $op[ 'str' ] ) ) {
                if ( $onOut )
                    $onOut( $op ) ;
            }
            elseif ( isset( $op[ 'rpn' ] ) )
            {
                $result = $this->evalRPN( $op[ 'rpn' ] , $onFn );
                if ( isset( $result[ 'error' ] ) )
                     throw new \RuntimeException( $result[ 'error' ] );
                else if ( !is_array( $result ) )
                    throw new \RuntimeException( 'bad result' ) ;
                else
                    $onOut( $result  );
            }
        }
    }
}