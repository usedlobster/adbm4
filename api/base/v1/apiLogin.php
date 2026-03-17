<?php

namespace api\base\v1;

class apiLogin extends \api\base\apiBase
{

    protected const RATE_LIMIT_USER_LOGIN = [1, [['2 minute', 5], ['1 hour', 20]]];
    protected const RATE_LIMIT_AUTHEXG    = [1, [['2 minute', 5], ['1 hour', 20]]];
    protected const RATE_LIMIT_USER_RESET = [1, ['1 hour', 5]];
    protected const RATE_LIMIT_IP_RESET   = [1, ['1 hour', 100]];

    public function __construct()
    {
        parent::__construct();
    }

    public function run($payload, $parts)
    {
        switch ($parts[0] ?? '') {
            case 'uap' :
                return $this->uap($payload);
            case 'exg' :
                return $this->exg($payload);
            case 'chprj' :
                return $this->chprj($payload);
            case 'sendresetcode' :
                return $this->sendresetcode($payload);
            case 'checkcode' :
                return $this->checkcode($payload);

            default:
                return 899;
        }
    }

    /**
     * Validates and processes the given payload for user authentication.
     *
     * @param  object  $payload  An object containing the following required properties:
     *                           - user (string): The username to be authenticated. Must not be numeric or empty.
     *                           - pass (string): The user's password. Must not be empty.
     *                           - vcode (string): A verification code associated with the user. Must be at least 40 characters long.
     *
     * @return int|null Returns an integer error code in the following cases:
     *                  - 801: If the payload is invalid or any required fields are missing or improperly formatted.
     *                  - 802: If the user exceeds the allowed login rate limit.
     *                  - 800: If an unexpected exception occurs during validation.
     *                  Returns null if the payload passes all validations.
     */
    public function uap($payload)
    {
        return \sys\Util::constantRunTime(function () use ($payload)
        {
            try {
                if (!$payload || empty($payload->user) || is_numeric($payload->user) || empty($payload->pass) || empty($payload->vcode))
                    return 801;

                if (!\sys\Audit::rateLimitOK($payload->user, self::RATE_LIMIT_USER_LOGIN))
                    return 802;

                return $this->authLogin($payload->user, $payload->pass, $payload->vcode);
            } catch (\Throwable $ex) {
                error_log($ex);
                return 800;
            }
        }, [], 0.30);
    }

    public function exg($payload)
    {
        return \sys\Util::constantRunTime(function () use ($payload)
        {
            try {
                if (!is_object($payload) || !isset($payload->authid, $payload->pid, $payload->vcode))
                    return 801;

                $ip = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['X-Forwarded-For'] ?? '';
                if (!\sys\Audit::rateLimitOK($ip, self::RATE_LIMIT_AUTHEXG))
                    return 802;

                return $this->authExchange($payload->authid, $payload->pid, $payload->vcode);
            } catch (\Throwable $ex) {
                return 800;
            }
        }, [], 0.30);
    }

    public function chprj($payload)
    {
        try {
            if (!isset($payload->sid, $payload->pid, $payload->vcode) && $payload->pid > 0)
                return 801;

            return $this->authChangeProject($payload->sid, $payload->pid, $payload->vcode);
        } catch (\Throwable $ex) {
            return 800;
        }
    }

    // API : sendresetcode - generate a code to enable users to reset password
    public function sendresetcode( $payload ) {
        try {
            // need { user )
            if ( !isset( $payload->user ) || !\sys\Valid::account( $payload->user ))
                return 801 ;

            // protect indivdual too many attempts
            if (!\sys\Audit::rateLimitOK($payload->user, self::RATE_LIMIT_USER_RESET))
                return 802;

            // this is very broad , dont trust this too much
            $ip = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '*';
            if (!\sys\Audit::rateLimitOK( $ip  , self::RATE_LIMIT_IP_RESET))
                return 802 ;

            return $this->authSendResetCode( $payload->user ) ;

        }
        catch( \Throwable $ex ) {
            return 800 ;
        }
    }

    public function checkcode( $payload ) {

        try {

            if (!isset( $payload->user,$payload->code,$payload->t ))
                return 801 ;

        }
        catch( \Throwable $ex ) {
            return 800 ;
        }

    }

}