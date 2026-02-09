<?php

namespace app\wd;

use app\wd\auth\AppLogin;
use eftec\bladeone\BladeOne;

class AppMasterBase extends AppLogin {

    private static string   $_req;
    private static array    $_split;
    protected static array  $_parts;
    protected static string $_base;
    private static ?BladeOne $_blade = null ;

    public function __construct( $req = null) {
        self::$_req = $req ?? $_SERVER['REQUEST_URI'] ?? '' ;
        self::$_split = explode('?', self::$_req, 2);
        self::$_parts = explode('/', strtolower(self::$_split[0]));
        if (!empty(array_shift(self::$_parts)))
            throw new \Exception('Invalid URL');
        self::$_base = array_shift(self::$_parts) ?? '';
        parent::__construct( $req );

    }

    private function getModel( string $base , array $parts ) : array
    {
        $t = [ $base , ...$parts ?? []];
        $rkey = 'view:'. serialize( $t ) ;

        $red = \sys\Redis::getRedis(  ) ;
        if ( $red ) {
            if ( $red->exists( $rkey )) {
                $view = unserialize($red->get($rkey));
                if (is_array($view))
                    return $view;
            }
        }

        $b = false ;
        $p = false ;
        $j = false ;
        $s = false ;
        $m = false ;

        if ( _DEV_MODE )
            clearstatcache();

        $base = __DIR__. '/../views/site/';

        while (is_array($t) && count($t) > 0) {

            $n = array_pop($t);
            $x = join('/', $t) . '/' ;

            if (!$b && file_exists($base.$x.$n.'.blade.php'))
                $b = 'site.'.join('.', $t).'.' . $n;
            else if ( !$b && file_exists($base.$x.'_root.blade.php'))
                $b = 'site.'.join('.', $t).'._root';

            if (!$p && file_exists(($f = $base.$x.$n.'.php')))
                $p = $f;
            else if (!$p && file_exists(($f = $base.$x.'_root.blade.php')))
                $p = $f ;

            if (!$j && file_exists($base.($f = $x.$n.'.min.js')))
                $j = $f;
            else if ( !$j && file_exists($base.($f = $x.'_root.js')))
                $j = $f ;

            if ( !$s && file_exists($base.($f = $x.$n.'.json')))
                $s = $f ;
            else if ( !$s && file_exists($base.($f = $x.'_root.json')))
                $s = $f ;

            if (!$m && file_exists($base.($f = $x.'mvc'.ucfirst($n)).'.php'))
                $m = '\\app\\views\\site\\'.str_replace('/', '\\', $f ) ;
            else if (!$m && file_exists($base.($f = $x.'mvcRoot').'.php'))
                $m = '\\app\\views\\site\\'.str_replace('/', '\\', $f ) ;

        }

        $view = ['blade' => $b, 'php' => $p, 'js' => $j , 'json'=>$s , 'model' => $m, 'dirbase' => $base];
        if ( $red ) {
            $red->setex( 'view:' . $rkey , _DEV_MODE ? 5 : 300  , serialize( $view ) );
        }
        return $view  ;
    }

    public function viewModel( string $base , array $parts , array $extra = [] ) : never
    {
        try {

            $view = $this->getModel( $base , $parts);
            if ( !is_array( $view ))
                throw new \Exception('Invalid view');

            $data = [... $view , ... $extra ];

            // echo '<pre>' , print_r($data , true ) , '</pre>' ;
            // mvc controller
            if (!empty($view['model'] ?? '') && class_exists($view['model'], true)) {
                $n = new $view['model']( $view );
                if ($n instanceof \app\views\ModelViewBase) {
                    if ( $n->exec($view,$base,$parts) === true )
                        exit;
                }
            }

            // load php script direct
            if (!empty($view['php'] ?? ''))
                require_once($view['php']);

            // view blade
            if (!empty($view['blade']))
                $this->showBlade($view['blade'], $data);

            http_response_code(404);
            exit;
        } catch (\Throwable $ex) {
            throw $ex;
        }
        finally {
        }
        exit;
    }


    public function getBlade() : ?BladeOne
    {
        try {
            if ( self::$_blade === null ) {
                $b = new BladeOne(__DIR__.'/../views', '/tmp', BladeOne::MODE_DEBUG);
                $b->csrf_token = $_SESSION['_csrf'] ?? ($_SESSION['_csrf'] = bin2hex(random_bytes(32)));
                $b->share('ui', new \app\ui\Helper());
//                $b->directiveRT( 'adbm' , function ($exp)  {
//                    echo '<pre>@adbm(', $exp , ')</pre>'  ;
//                });
                self::$_blade = $b;
            }

        } catch (\Exception $ex) {
            echo $ex->getMessage();
            http_response_code(404);
        }

        return self::$_blade ;
    }

    public  function showBlade($temp, $data = []) : never
    {
        echo self::getBlade()?->run($temp, $data);
        exit;
    }

    public function checkCSRF() : bool
    {
        return !empty($_POST['_token']) && !empty($_SESSION['_csrf']) && ($_POST['_token'] === $_SESSION['_csrf']);
    }
}