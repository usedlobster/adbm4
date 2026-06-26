<?php

namespace app\wd;

use eftec\bladeone\BladeOne;
use sys\wd\wdscript\WDScript3;

class AppMaster
{

    protected string $_viewroot;

    protected static string $_req;

    protected static array $_split;

    protected static array $_parts;

    protected static string $_base;

    protected bool $_down = false;

    protected int $_checkpoint;

    protected ?object $_login = null;

    protected ?object $_info = null;

    private array $_errors;

    public function __construct()
    {
        // break down request uri
        self::$_req = $_SERVER['REQUEST_URI'] ?? '';
        self::$_split = explode('?', self::$_req, 2);
        self::$_parts = explode('/', strtolower(trim(self::$_split[0])));
        if ( (array_shift(self::$_parts) ?? '') !== '' )
            throw new \RuntimeException('Invalid request');
        self::$_base = array_shift(self::$_parts) ?? '';
        $this->_viewroot = $_SERVER['DOCUMENT_ROOT'] . '/../app/adbm/view';
        $this->_down = $_SESSION['_down'] ?? true;
        $this->_checkpoint = $_SESSION['_checkpoint'] ?? time() - 3600;
        $this->_login = $_SESSION['_login'] ?? null;
        $this->_info = $_SESSION['_info'] ?? null;
        if ( !$this->systemPing() )
            throw new \RuntimeException('Website may be down');
    }

    public function isLogin(?object $o) : bool
    {
        // valid means has atkn , rtkn and sid/pid values


        return !is_null($o) &&
               isset($o->atkn, $o->rtkn, $o->sid, $o->pid) &&
               !empty($o->atkn) && is_int($o->sid) && $o->sid > 0 &&
               is_int($o->pid) && $o->pid > 0;
    }

    // generic
    public function setCookie(string $name, ?string $value, float $hours = 168.0)
    {
        if ( $value && is_string($value) && !empty($value) && strlen($value) < 512 ) {
            setcookie($name,
                $value ,
                time() + (3600 * $hours),
                '/',
                $_ENV['APP_DOMAIN'] ?? '',
                true,
                true);
            $_COOKIE[$name] = $value;
        }
        else {
            setcookie($name, '', time() - 3600, '/', $_ENV['APP_DOMAIN'], true, true);
            $_COOKIE[$name] = null;
        }
    }

    public function setLogin(?object $login) : bool
    {
        // clear info, so fresh is given
        session_regenerate_id(true);
        $_SESSION['_info'] = $this->_info = null;
        if ( $this->isLogin($login) ) {
            $_SESSION['_login'] = $this->_login = $login;
            if ( isset( $login->mtkn ) )
                $this->setCookie('_login_token', $login->mtkn);

            return true ;
        }

        $_SESSION['_login'] = $this->_login = null;
        return false ;
    }

    public function haveLogin() : bool
    {
        return isset($_SESSION['_login']) || ($this->isLogin($this->_login));
    }

    public function checkSysEdit(bool $quick) : bool
    {
        return true;
    }

    public function checkUsrEdit(bool $quick) : bool
    {
        return true;
    }

    public function fetchInfo() : ?object
    {
        // no login then no info
        if ( $this->_login === null || !isset($this->_login->sid) )
            return $_SESSION['_info'] = $this->_info = null;

        $inf = $this->_info ?? false;
        if ( !$inf || !isset($inf->last, $inf->sid) || $inf->sid !== $this->_login->sid || (time() - $inf->last) >= 15 ) {
            // need to ask api again
            $inf = $this->apiPostA('v1/login/info', []);
            if ( is_null($inf) || !is_object($inf) || isset($inf->expired) || !isset($inf->sid) || $inf->sid !== $this->_login->sid ) {
                $this->setLogin(null);
                header('Location: /auth/force-logout');
                exit;
            }

            $inf->last = time();
            return $_SESSION['_info'] = $this->_info = $inf;
        }

        return $inf;
    }

    public function showBlade(string $view, array $data = [], int $edit = 0) : void
    {
        if ( !empty($view) ) {
            $blade = new BladeOne($this->_viewroot, '/app/blade-cache', BladeOne::MODE_AUTO);
            $blade->csrf_token = $_SESSION['_csrf'] ?? ($_SESSION['_csrf'] = bin2hex(random_bytes(32)));
            $blade->share('_app', $this);
            // add @sys directive
            if ( $this->haveLogin() ) {
                $sid = $this->_login->sid ?? 0;
                $pid = $this->_login->pid ?? 0;
                $blade->directiveRT('sys', function ($exp = '') use ($edit, $sid, $pid, $view)
                {
                    try {
                        // found a @sys( ) directive in main blade file
                        new \app\adbm\AppPageEditor()?->show($edit, $sid, $pid, $view, $exp);
                    }
                    catch (\Throwable $ex) {
                        echo('!!<span style="color:red">' . htmlspecialchars($ex->getMessage()) . '</span>!!');
                    }
                });
            }
            echo $blade->run($view, $data);

            unset($blade);
        }
    }

    public function checkCSRF(?string $exp = null)
    {
        if ( ($a = $_POST['_token'] ?? false) && ($b = $exp ?? $_SESSION['_csrf'] ?? false) )
            return hash_equals($a, $b);
        return false;
    }

    public function apiPost0(string $url, array | string $data) : ?object
    {
        try {
            $url = _API_DOMAIN . $url;
            $res = \sys\Util::curlSend('POST', $url, $data, null, null);
            if ( $res && is_string($res) ) {
                $obj = @ json_decode($res) ?? null;
                if ( is_object($obj) && json_last_error() === JSON_ERROR_NONE )
                    return $obj;
            }
        }
        catch (\Throwable $ex) {
            error_log($ex);
        }

        return null;
    }

    public function apiPostE(string $url, mixed $data)
    {
        if ( !($id = $_ENV['APP_ID'] ?? false) )
            return null;

        if ( !($key = $_ENV['APP_KEY'] ?? false) )
            return null;
        $e = \sys\Crypto::encrypt(serialize($data), $key);
        if ( $e ) {
            return $this->apiPost0($url, [
                'id' => $id,
                'e'  => $e,
            ]);
        }

        return null;
    }

    public function doRefresh() : bool
    {
        return false;
    }

    public function apiPostA(string $url, array | string $data, bool $retry = true) : ?object
    {
        try {
            $url = _API_DOMAIN . $url;
            $res = \sys\Util::curlSend('POST', $url, $data, $this?->_login?->atkn ?? null, null);
            if ( $res && is_string($res) ) {
                $obj = @ json_decode($res) ?? null;
                if ( is_object($obj) && json_last_error() === JSON_ERROR_NONE ) {
                    if ( $obj->expired ?? false ) {
                        if ( $retry ) {
                            if ( $this->doRefresh() )
                                return $this->apiPostA($url, $data, false);
                        }
                    }
                    return $obj;
                }
            }
        }
        catch (\Throwable $ex) {
            error_log($ex);
        }

        return null;
    }

    private function systemPing() : bool
    {
        $_t0 = time();
        if ( (($_t0 - ($this->_checkpoint ?? 0)) > ($this->_down ? 15 : 30)) ) {
            $_SESSION['_checkpoint'] = $this->_checkpoint = $_t0;
            $result = $this->apiPost0('v1/system/ping', []);
            $this->_down = $_SESSION['_down'] = !(is_object($result)) || (($result->str ?? '') !== 'pong');
        }

        return !($this->_down ?? false);
    }

}
