<?php

    namespace sys\wd\Email;
    use \sys\wd\Email\Parsedown;

    /**
     * The wdScript class is responsible for processing and parsing wdScript content.
     *
     * Given a wdscript filename or string of content
     *
     */
    class WDScript
    {
        private string $_dirbase;
        private array $_vars;
        private array $_opcodes;
        private ?\stdClass $_lexer = null;
        public array $_data = [];
        private array $_init = [];
        private int $_block = 0;
        private int $_flag = 0; // 0=>both html/text , 1 => html only , 2=> text only
        private int $_oflag = 0;
        public string $_html = '';
        public string $_text = '';
        public string $_md = '';


        const OVERLAY_TOKEN = '<!-- Overlay -->';

        // break script into sections "" , '' , %[ .. ]% , // .... , \n , .....
        const string FILE_PATTERN = '/"(?:[^"\\\\]|\\\\.)*"|' .         // double-quoted strings
        '\'(?:[^\'\\\\]|\\\\.)*\'|' .       // Single-quoted strings
        '%\[|' .                            // Literal %[
        '\]%;|' .                           // Literal ]%;
        '\]%|' .                            // Literal ]%
        '\/\/|' .                           // Inline comments
        '\n|' .                             // Newline
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
                ':=' => [ 'p' => -100 , 'a' => 1 ] ,  // assignment
                'let' => [ 'u' => true , 'up' => 0 ] ,
        ];


        public function __construct()
        {
            $this->reset();
        }

        public function reset()
        {
            $this->_vars    = [];
            $this->_dirbase = __DIR__;
            $this->_opcodes = [];
            $this->_block   = 0;
        }

        public function addFile( $file )
        {
            if ( !file_exists( $file ) )
                throw new \Exception( "File not found : $file" );

            $this->_dirbase = dirname( realpath( $file ) );
            $content        = file_get_contents( $file );
            $this->addContent( $content );
        }

        public function addContent( $content )
        {
            preg_match_all( self::FILE_PATTERN , $content ?? '' , $tokens , PREG_SPLIT_NO_EMPTY );
            $id = 0;
            if ( is_array( $tokens[ 0 ] ) )
                $this->encodeBlock( $tokens[ 0 ] , $id , 0 );
        }

        private function encodeBlock( $matches , &$id , $level )
        {
            $buf = '';
            while ( ( $m = $matches[ $id++ ] ?? false ) !== false )
            {
                if ( $m === '%[' )
                {
                    $this->addBuf( $buf , $level );
                    return $this->encodeBlock( $matches , $id , $level + 1 );
                }
                elseif ( $m === ']%;' )
                {
                    $this->addBuf( $buf , $level );
                    // skip any \n after ;
                    if ( ( $matches[ $id ] ?? false ) === "\n" )
                        $id++;
                    return $this->encodeBlock( $matches , $id , $level - 1 );
                }
                elseif ( $m === ']%' )
                {
                    $this->addBuf( $buf , $level );
                    return $this->encodeBlock( $matches , $id , $level - 1 );
                }
                elseif ( $m === '//' )
                {
                    // scan forward to \n
                    while ( ( $m = $matches[ $id ] ?? false ) !== false && ( $m !== "\n" ) )
                    {
                        $id++;
                    }
                    // and skip this aswell
                    $id++;
                }
                else
                    $buf .= $m;
            }
            $this->addBuf( $buf , $level );
            return $level;
        }

        private function getLexer()
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
                    $lexer->pattern = '/"[^"]*"|' . '\'[^\']*\'|' . '[A-Za-z_][A-Za-z_0-9]*(\.[A-Za-z_0-9]*|' . '\([^\)]*\))*|' . '\d*\.?\d+|' . '\(|\)|' . ',|' . join( '|' ,
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


        private function addCmd( string $cmd , $level ) : void
        {
            $pattern = '/^@([\w|\+|\-]+)\s*(?:\((.*?)\))?(?:\s+(.*))?$/';
            $matches = [];
            if ( !preg_match( $pattern , $cmd , $matches ) )
                throw new \Exception( "@ command syntax error : $cmd" );

            $atCmd = trim( $matches[ 1 ] ?? false );
            switch ( $atCmd )
            {
                case 'include' :
                    $this->includeContent( trim( str_replace( [ "'" , '"' ] , '' , $matches[ 3 ] ?? $matches[ 2 ] ) ) );
                    break;
                case 'for' :
                    // special kind of loop ( could be simulated with ) [r1] while ( r2 ) { ... do something r3 }
                    $args = explode( ';' , str_replace( [ '(' , ')' ] , '' , $matches[ 3 ] ) );
                    if ( count( $args ) !== 3 )
                        throw new \RuntimeException( 'invalid for' );

                    $r1 = $this->encodeExpr( $args[ 0 ] );
                    if ( isset( $r1[ 'error' ] ) )
                        throw new \RuntimeException( 'for init: ' . $r1[ 'error' ] );
                    $r2 = $this->encodeExpr( $args[ 1 ] );
                    if ( isset( $r2[ 'error' ] ) )
                        throw new \RuntimeException( 'for cond: ' . $r2[ 'error' ] );

                    $r3 = $this->encodeExpr( $args[ 2 ] );
                    if ( isset( $r3[ 'error' ] ) )
                        throw new \RuntimeException( 'for iter: ' . $r3[ 'error' ] );

                    $this->_opcodes[] = [ 'for' => [ $r1 , $r2 , $r3 ] , 'lvl' => $level , 'blk' => ( ++$this->_block ) ];
                    break;

                case 'if' :
                    $cond             = str_replace( [ '(' , ')' ] , '' , $matches[ 3 ] ?? $matches[ 2 ] ?? '' );
                    $r1               = $this->encodeExpr( $cond );
                    $this->_opcodes[] = [ 'if' => $r1 , 'lvl' => $level , 'blk' => ( ++$this->_block ) ];
                    break;

                case 'end' :
                    $this->_opcodes[] = [ 'end' => $atCmd , 'lvl' => $level , 'blk' => ( $this->_block-- ) ];
                    break;

                default :
                    if ( in_array( $atCmd , [ 'h+' , 'h-' , 't+' , 't-' ] ) )
                        $this->_opcodes[] = [ 'flag' => $atCmd , 'lvl' => $level ];
                    else
                        throw new \Exception( "unknown @ : $cmd" );
            }
        }

        // same as add file,  but also add directory base
        private function includeContent( string $path )
        {
            if ( file_exists( $path ) )
                $this->addFile( $path );
            elseif ( file_exists( $this->_dirbase . '/' . $path ) )
                $this->addFile( $this->_dirbase . '/' . $path );
            else
                throw new \Exception( "file not found : $path" );
        }

        private function addBuf( string &$buf , $level )
        {
            if ( $buf !== '' )
            {
                if ( $level > 0 )
                {
                    $cmd = trim( $buf , " \n\r\t\v" );
                    if ( ( $cmd[ 0 ] ?? '' ) === '@' )
                        $this->addCmd( $cmd , $level );
                    else
                    {
                        $rpn = $this->encodeExpr( $cmd , $this->_vars );
                        if ( isset( $rpn[ 'error' ] ) )
                            throw new \RuntimeException( $rpn[ 'error' ] );
                        else
                            $this->_opcodes[] = [ 'rpn' => $rpn , 'lvl' => $level ];
                    }
                }
                else
                    $this->_opcodes[] = [ 'buf' => $buf , 'lvl' => $level ];
            }
            $buf = '';
        }

        // convert expression to rpn
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
                    elseif ( isset( $lexer->functions[ $token ] ) )
                    {
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
                            elseif (
                                    in_array( $token , $lex->unary ) && ( $prev === null || in_array( $prev , [ 'par_open' , ',' , 'var' , 'op' ] ) )
                            )
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
                                // we have a variable name
                                $vbase = explode( '.' , $token )[ 0 ] ?? $token;
                                if ( !in_array( $vbase , $vars ) )
                                    $vars[] = $vbase;

                                $output[] = [ 't' => 'var' , 'v' => $token ];
                                while ( !empty( $pendingUnaryOps ) )
                                {
                                    $output[] = [ 't' => 'uop' , 'v' => array_pop( $pendingUnaryOps ) ];
                                }

                                $prev = 'var';
                            }
                            else
                                throw new \RuntimeException( 'unknown token : ' . $token );
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

        private function addText( $text ) : void
        {
            if ( is_array( $text ) )
            {
                $this->addText( $text[ 'n' ] ?? '' );
                return;
            }

            if ( $text === "" )
                return;

            if ( !( $this->_flag & 2 ) && !( $this->_flag & 1 ) )
                $this->_text .= $text;
            elseif ( ( $this->_flag & 2 ) )
                $this->_text .= $text;

            if ( !( $this->_flag & 2 ) )
            {
                if ( $this->_flag & 1 )
                {
                    $parsedown   = new Parsedown();
                    $this->_html .= ( $parsedown->text( nl2br( $this->_md ) ) ) . '<div>' . $text . '</div>';
                    $this->_md   = "";
                }
                else
                    $this->_md .= nl2br( $text );
            }
        }

        private function addResult( $result )
        {
            $z = '';
            foreach ( $result as $r )
            {
                if ( isset( $r[ 'v' ] ) )
                    $z .= $r[ 'v' ] . ' ';
            }

            $this->addText( rtrim( $z , '| ' ) );
        }


        /**
         * Expands a variable name into an array of components, handling expressions contained within parentheses.
         *
         * @param  string  $vname  The variable name to expand.
         * @return array|null An array of component strings or parsed expressions, or null if the input is invalid.
         */
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

        /*
         *
         */

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

        private function evalRPN( array $rpn )
        {
            $stack = [];
            try
            {
                reset( $rpn );
                while ( ( $opcode = current( $rpn ) ) !== false )
                {
                    $ans = null;
                    $op  = $opcode[ 't' ] ?? false;
                    $ov  = $opcode[ 'v' ] ?? false;
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
                        case 'uop' :
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
                                    if ( $a2[ 't' ] === 'str' || $a1[ 't' ] === 'str' )
                                        $ans = $a2[ 'v' ] . $a1[ 'v' ];
                                    elseif ( $a2[ 't' ] === 'num' )
                                        $ans = $a2[ 'v' ] + $a1[ 'v' ];
                                    else
                                        throw new \RuntimeException( 'invalid +' );
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
                                    break;
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
                            return [ 'error' => 'invalid result' ];
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

        protected function getResult( $rpn )
        {
            if ( !is_array( $rpn ) )
                return null;

            $result = $this->evalRPN( $rpn ?? [] );
            if ( isset( $result[ 'error' ] ) )
                throw new \RuntimeException( $result[ 'error' ] );

            return $result;
        }

        private function getBool( $rpn ) : bool
        {
            $result = $this->getResult( $rpn );
            if ( count( $result ) !== 1 )
                throw new \RuntimeException( 'invalid bool result' );
            if ( !empty( $result[ 0 ][ 'v' ] ) )
                return true;
            return false;
        }

        private function skipBlock( int &$pc , int $blk ) : void
        {
            // skip all opcodes tell we find end
            while ( ( $w = $this->_opcodes[ $pc++ ] ?? null ) !== null )
            {
                if ( isset( $w[ 'end' ] ) && ( $w[ 'blk' ] === $blk ) )
                    break;
            }
            if ( $w === null )
                throw new \RuntimeException( 'missing end' );
        }


        public function exec( array $data )
        {
            $this->_flag = 0;
            $this->_data = $data;
            $this->_init = [];
            $pc          = 0;


            //
            $t1 = time();
            while ( ( $op = $this->_opcodes[ $pc++ ] ?? null ) !== null )
            {
                if ( ( time() > $t1 + 20 ) )
                    throw new \RuntimeException( 'email render timeout' );

                if ( isset( $op[ 'buf' ] ) )
                    $this->addText( $op[ 'buf' ] );
                elseif ( isset( $op[ 'rpn' ] ) )
                {
                    $result = $this->evalRPN( $op[ 'rpn' ] );
                    if ( isset( $result[ 'error' ] ) )
                        throw new \RuntimeException( $result[ 'error' ] );
                    $this->addResult( $result );
                }
                elseif ( isset( $op[ 'for' ] ) )
                {
                    $fop = $op[ 'for' ];
                    $blk = $op[ 'blk' ] ?? -1;
                    //
                    if ( !( $init[ $pc ] ?? false ) )
                    {
                        $loop[ $blk ] = $pc - 1; // record return
                        $init[ $pc ]  = true;
                        $this->getResult( $fop[ 0 ] );
                    }

                    // check termination condition
                    if ( !$this->getBool( $fop[ 1 ] ?? false ) )
                    {
                        $init[ $pc ] = false;
                        $this->skipBlock( $pc , $blk );
                    }
                }
                elseif ( isset( $op[ 'if' ] ) )
                {
                    $ifop = $op[ 'if' ];
                    if ( !$this->getBool( $ifop ?? false ) )
                        $this->skipBlock( $pc , ( $op[ 'blk' ] ?? -1 ) );
                }
                elseif ( isset( $op[ 'end' ] ) )
                {
                    if ( ( $blk = $op[ 'blk' ] ?? -1 ) < 0 )
                        throw new \RuntimeException( 'bad block nesting' );
                    $l = $loop[ $blk ] ?? -1;
                    if ( $l >= 0 )
                    {
                        $oo = $this->_opcodes[ $l ];
                        if ( isset( $oo[ 'for' ] ) )
                        {
                            $this->getResult( $oo[ 'for' ][ 2 ] ?? false );
                            $pc = $l;
                        }
                    }
                }
                elseif ( isset( $op[ 'flag' ] ) )
                {
                    switch ( $op[ 'flag' ] ?? false )
                    {
                        case 'h+' :
                            $this->_flag |= 1;
                            break;
                        case 'h-' :
                            $this->_flag &= ~1;
                            break;
                        case 't+' :
                            $this->_flag |= 2;
                            break;
                        case 't-' :
                            $this->_flag &= ~2;
                            break;
                        default:
                            throw new \RuntimeException( 'invalid flag : ' . ( $op[ 'flag' ] ?? '' ) );
                            break;
                    }
                }
            } // end exec

            $parsedown = new Parsedown();

            if ( !empty( $this->_md ) )
                $this->_html .= $parsedown->text( $this->_md );
        }
    }