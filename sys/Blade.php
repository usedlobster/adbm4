<?php

    namespace sys;

    use eftec\bladeone\BladeOne;

    class Blade
    {

        private static string    $_blade_dir = '';
        private static string    $_blade_base;
        private static ?BladeOne $_blade     = null;

        public function __construct($base = 'app/view')
        {
            self::$_blade_base = $base;
            self::$_blade_dir = realpath($_SERVER[ 'DOCUMENT_ROOT' ].$base);
            self::$_blade = null;
        }

        public static function getBlade($base = 'app/view') : ?BladeOne
        {
            if ( self::$_blade === null || self::$_blade_base !== $base )
            {
                self::$_blade_base = $base;
                self::$_blade_dir = realpath($_SERVER[ 'DOCUMENT_ROOT' ].'/../'.$base);
                if ( self::$_blade === null )
                {
                    self::$_blade = new BladeOne(self::$_blade_dir , '/tmp' , BladeOne::MODE_DEBUG);
                    self::$_blade->csrf_token = $_SESSION[ '_csrf' ] ?? ($_SESSION[ '_csrf' ] = bin2hex(random_bytes(16)));
                }
                else
                    self::$_blade->setBasePath(self::$_blade_dir);
            }

            return self::$_blade;
        }

        public static function runPage($view , $data = [] , callable|null $callback = null)
        {
            $b = self::getBlade();
            $b->directiveRT('adbm' , function ($expression) use ($callback)
            {
                if ( $callback !== null )
                    $callback($expression);
            });

            return $b->run($view , $data);
        }

        public static function runContent($content , $data = [] , callable|null $callback = null)
        {
            $b = self::getBlade();
            $b->directiveRT('user' , function ($expression) use ($callback)
            {
                if ( $callback !== null )
                    $callback($expression);
            });

            return $b->runString($content , $data);
        }



    }