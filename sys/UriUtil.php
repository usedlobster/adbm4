<?php

    namespace sys;

    class UriUtil
    {

        public static function getURIObject( $req , $method ) : object|null
        {
            try
            {
                /*
                $red = \sys\Util::getRedis(0,false )  ;
                if ( $red ) {
                    $u = $red->get( $ukey = 'uri:' . $req );
                    if ( $u )
                        return unserialize($u);
                }
                */

                $uri = new \stdClass();
                $uri->_orig = $req;
                $uri->_full = rtrim($req , DIRECTORY_SEPARATOR);
                $uri->_req = explode('?' , $uri->_full , 2);
                $uri->_path = strtolower($uri->_req[ 0 ] ?? '');
                // drop .php extension to be safe
                if ( str_ends_with($uri->_path , '.php') )
                    $uri->_path = substr($uri->_path , 0 , -4);

                $uri->_query = $uri->_req[ 1 ] ?? '';
                $uri->_parts = explode(DIRECTORY_SEPARATOR , $uri->_path);
                if ( array_shift($uri->_parts) !== '' )
                    return null;

                $uri->_action = $uri->_parts[1] ?? false ;
                $uri->_params = [];
                $uri->_short = [];

                // skip any numeric parts as they are considered params
                foreach ( $uri->_parts as $part )
                {
                    if ( is_numeric($part) )
                        $uri->_params[] = $part;
                    else
                        $uri->_short[] = $part;
                }

                $uri->_base = $uri->_parts[ 0 ] ?? false;
                $uri->_method = $method;
                //if ( $red )
                //    $red->setex( $ukey , 180 , serialize($uri)) ;
            }
            catch ( \Throwable $e )
            {
                $uri = null ;
            }

            return $uri;
        }

        public static function navigateTo(string $url) : never
        {
            // make sure we remove previous post-data.
            if ( isset($_SESSION[ '_post' ]) )
                unset($_SESSION[ '_post' ]);
            if ( !headers_sent() )
                header('Location: '.$url);
            else
            {
                echo '<script>window.location=\''.$url.'\';</script>';
                echo '<noscript><a href="'.$url.'">click To continue</a></noscript>';
            }
            exit;
        }

    }