<?php

namespace app\wd\auth;



trait AuthLoginApiTrait {

    use \app\wd\auth\AppPostApiTrait;

    // given username and password
    public function doLogin( $user , $pass ) : string
    {
        try {
            $vcode = base64_encode(random_bytes(63));
            $result = $this->apiPost0('v1/login/uap', ['user' => $user, 'pass' => $pass , 'vcode' => hash('sha256', $vcode)]);
            if ( is_object($result) )
            {
                if ( !isset( $result->error) && isset($result->authid , $result->pid )) {
                    // got valid authid , try to exchange for tokens
                    $result = $this->apiPost0('v1/login/exg', ['authid' => $result->authid, 'pid' => $result->pid, 'vcode' => $vcode]);
                    if (is_object($result) && !isset($result->error) && isset($result->sid, $result->pid, $result->atkn, $result->rtkn)) {
                        $this->setLogin($result);
                        return '';
                    }
                }
                else if ( isset($result->error)) {
                    return \sys\Error::msg( 900 , [$result->error]  );
                }
            }

        }
        catch( \Throwable $ex )
        {
            //
            return \sys\Error::msg(900 );
        }


        $this->setLogin( null ) ;
        return \sys\Error::msg(901 );

    }



}