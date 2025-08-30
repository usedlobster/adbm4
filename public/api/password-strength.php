<?php
    // api/password-strength.php
    header('Content-Type: application/json');
    require_once '../../vendor/autoload.php';

    use ZxcvbnPhp\Zxcvbn;

    function checkPasswordStrength($password) {
        try {
            $zxcvbn = new Zxcvbn();
            $strength = $zxcvbn->passwordStrength($password);
            return [
                    'score' => $strength['score'],
                    'feedback' => $strength['feedback'],
                    'isValid' => $strength['score'] >= 3
            ];
        } catch (Exception $e) {
            return [
                    'error' => 'Unable to check password strength',
                    'isValid' => false
            ];
        }
    }

    try
    {
        $redis = new Redis();
        $redis->connect('127.0.0.1' , 6379);

        $ip = $_SERVER['REMOTE_ADDR'];

        $key = "password_strength_rate:{$ip}";
        $requests = $redis->incr($key);
        if ($requests === 1) {
            $redis->expire($key, 30); // Reset after 30 seconds
        }
        if ( $requests > 30 )
        {
            http_response_code(429) ;
            throw new \Exception('Too many requests') ;
        }

        // Simple input sanitization
        $data = json_decode(file_get_contents('php://input') , true) ?? [];
        $password = $data[ 'password' ] ?? '';
        //
        $result = checkPasswordStrength($password);
        echo json_encode($result);
    }
    catch( Throwable $ex ) {
        echo json_encode([
                'error' => $ex->getMessage()
        ]);
    }
    exit ;