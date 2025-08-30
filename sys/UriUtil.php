<?php

    namespace sys;

    class UriUtil
    {

        public static function getURIObject($req , $method) : object|null
        {
            $req = filter_var($req , FILTER_SANITIZE_URL) ;
            if ( empty($req) || !is_string($req) )
                return null;

            if ( !in_array($method , ['GET' , 'POST' , 'PUT' , 'DELETE' , 'OPTIONS' , 'HEAD' , 'PATCH']) )
                return null;

            $uri = (object)[];
            try
            {
                $uri->_orig = $req;
                $uri->_full = rtrim($req , DIRECTORY_SEPARATOR);
                $uri->_req = explode('?' , $uri->_full , 2);
                // all paths upto query  are considered lowercase
                $uri->_path = strtolower($uri->_req[ 0 ] ?? '');
                // drop extension ( if given )

                if ( str_ends_with($uri->_path , '.php') )
                    $uri->_path = substr($uri->_path , 0 , -4);

                $uri->_query = $uri->_req[ 1 ] ?? '';
                $uri->_parts = explode(DIRECTORY_SEPARATOR , $uri->_path);
                if ( array_shift($uri->_parts) !== '' )
                    return null;

                $uri->_params = [] ;
                $uri->_short = [] ;
                foreach( $uri->_parts as $part ) {
                    if ( !empty($part) && ( is_numeric($part) || $part[0]=='_'  ))
                    {
                        $uri->_params[substr($part,1)] = $part;
                        $uri->_short[] = '_';
                    }
                    else
                    {
                        $uri->short[] = $part;
                        $uri->_short[] = $part;
                    }
                }


                $uri->_base = $uri->_parts[ 0 ] ?? false;
                $uri->_method = $method;
                return $uri;
            }
            catch ( \Throwable $e )
            {
            }

            return null;
        }

        public static function navigateTo(string $url) : void
        {
            // make sure we loose post of other page
            if ( isset($_SESSION[ '_post' ]) )
                unset($_SESSION[ '_post' ]);
            if ( !headers_sent() )
                header('Location: '.$url);
            else
                echo '<script>window.location=\''.$url.'\';</script>';
            exit;
        }

    }