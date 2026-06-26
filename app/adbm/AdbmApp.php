<?php

namespace app\adbm;

use app\wd\AppMaster;

class AdbmApp extends AppMaster
{

    use AdbmLoginTrait;

    public function __construct()
    {
        parent::__construct();
    }

    private function getView(string $base, ?array $parts) : ?array
    {
        $t = ['page', $base, ...$parts ?? []];
        $red = \sys\Redis::getRedis();
        if ($red) {
            $rkey = 'pagemap:' . join(':', $t);
            $pagemap = json_decode($red->get($rkey));
            if (json_last_error() === JSON_ERROR_NONE && is_array($pagemap))
                return $pagemap;
        }

        $vroot = __DIR__ . '/view/';
        $p = false;
        $b = false;
        $j = false;
        $s = false;

        while (is_array($t) && count($t) > 0) {
            $n = array_pop($t);
            if (is_numeric($n))
                continue;

            $x = join('/', $t) . '/';
            if (!$b) {
                if (file_exists($vroot . $x . $n . '.blade.php'))
                    $b = join('.', $t) . '.' . $n;
                elseif (file_exists($vroot . $x . '_root.blade.php'))
                    $b = join('.', $t) . '._root';
            }

            // find php file
            if (!$p) {
                if (file_exists($vroot . $x . $n . '.php'))
                    $p = join('/', $t) . '/' . $n . '.php';
                elseif (file_exists($vroot . $x . '_root.php'))
                    $p = join('/', $t) . '._root.php';
            }
            // find json file
            if (!$j) {
                if (file_exists($vroot . $x . $n . '.json'))
                    $j = join('/', $t) . '/' . $n . '.json';
                elseif (file_exists($vroot . $x . '_root.json'))
                    $j = join('/', $t) . '._root.json';
            }
            // find script
            if (!$s) {
                if (file_exists($vroot . $x . $n . '.min.js'))
                    $s = join('/', $t) . '/' . $n . '.min.js';
                elseif (file_exists($vroot . $x . '_root.min.js'))
                    $s = join('/', $t) . '._root.min.js';
            }
        }

        if ($b || $p || $j || $s)
            $map = [
                'blade' => $b,
                'php' => $p,
                'json' => $j,
                'script' => $s

            ];
        else
            $map = null;

        if ($red)
            $red->setex($rkey, 15, json_encode($map));

        return $map;
    }

    private function cookieLogin( ?string $mtkn ) : bool {

        return false ;
    }
    private function securePage($b)
    {
        if (!($this->haveLogin())) {
                $_SESSION['_auth'] = (object)['target' => self::$_req, 'step' => 0];
                header('Location: /auth/login');
                exit;
        }

        try {
            $page = $this->getView($b, self::$_parts);
            if (!$page)
                throw new \Exception('Page Not Found');
            // add info
            $page['info'] = $this->fetchInfo();
            // add edit modes allowed
            $allow = (int)($page['info']?->allow ?? 0)&3 ;
            $edit = ((int)($_GET['e'] ?? 0 )) & $allow & 3  ;
            $page['edit'] = (object)['allow'=>$allow , 'mode'=>$edit] ;
            // always load php script if found
            if ($page['php'] ?? false)
                @require(__DIR__ . '/view/' . $page['php']);

            if ($page['blade'] ?? false)
                $this->showBlade( $page['blade'], ['page' => (object)$page] , $edit  );

        }
        catch (\Throwable $ex) {
            error_log($ex);
            http_response_code(404);
            $this->showBlade('error.404', ['errormsg' => $ex->getMessage()]);

            exit;
        }
        exit;
    }

    public function start()
    {
        try {
            match (($b = self::$_base ?? '')) {
                ''        => $this->showBlade('welcome'),
                'auth'    => $this->authPage(),
                'privacy' => $this->showBlade('privacy'),
                default   => $this->securePage($b),
            };
        }
        catch (\Throwable $ex) {
            echo $ex->getMessage();
            exit;
        }
    }


}