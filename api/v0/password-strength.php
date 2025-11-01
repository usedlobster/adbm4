<?php

    header('Content-Type: application/json');
    // Limit input size to 256 bytes
    $input = file_get_contents('php://input' , false , null , 0 , 512);
    if ( $input === false )
    {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input' , 'isValid' => false]);
        exit;
    }
    elseif ( strlen($input) >= 512 )
    {
        http_response_code(413);
        echo json_encode(['error' => 'Input too large' , 'isValid' => false]);
        exit;
    }

    try
    {
        $data = json_decode($input , true);
        if ( !is_array($data) || !isset($data[ 'password' ]) || !is_string($data[ 'password' ]) )
        {
            throw new Exception('Invalid JSON structure');
        }

        // Limit password length to 128 characters
        $password = substr($data[ 'password' ] , 0 , 128);
        require_once('../../vendor/bjeavons/zxcvbn-php');
        $zxcvbn = new Zxcvbn();

        $strength = $zxcvbn->passwordStrength($password);
        echo json_encode([
                'score' => $strength[ 'score' ] ,
                'feedback' => $strength[ 'feedback' ] ,
                'isValid' => $strength[ 'score' ] >= 3
        ]);
    }
    catch ( Exception $e )
    {
        http_response_code(400);
        echo json_encode([
                'error' => 'Unable to check password strength' ,
                'x' => $e->getMessage() ,
                'isValid' => false
        ]);
    }