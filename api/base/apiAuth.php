<?php

namespace api\base;

class apiAuth
{

    use \api\auth\ApiAuthEngineTrait;

    private ?object $_auth;

    private ?\sys\Redis $_red;

    private ?string $_authkey;

    public function __construct()
    {
        $this->_auth = null;
    }

    public function sendExpire() : never
    {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            echo json_encode((object)['expired' => true]);
        }
        exit;
    }

    public function auth() : ?object
    {
        if ($this->_auth === null) {

            $bearerhead = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if ($bearerhead && str_starts_with($bearerhead, 'Bearer ')) {
                $bearer = substr($bearerhead, 7);
                if (empty($bearer) || strlen($bearer) > 256)
                    return null;

                $red = \sys\Redis::getRedis(2);
                if ($red) {
                    $bkey = 'bkey:'.hash('sha256', $bearer);
                    $v = $red->get($bkey);
                    if (is_string($v) && !empty($v)) {
                        $d = json_decode($v);
                        if (json_last_error() === JSON_ERROR_NONE && is_object($d) && isset($d->atkn)) {
                            $this->_auth = $d;
                            return $d;
                        }
                    }
                }

                $d = $this->decodeAccessToken($bearer);
                if ($d->expired ?? false)
                    $this->sendExpire();

                if (is_object($d) && isset($d->sid)) {
                    if ($red) {
                        $left = $d->t + $d->x - time() ;
                        if ($left > 5)
                            $red->setex($bkey, $left, json_encode($d));
                    }
                    $this->_auth = $d;
                }

            }
        }

        return $this->_auth ;
    }

}