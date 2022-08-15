<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function check_auth($token)
{
    return JWT::decode(
        $token,
        new Key($_ENV['JWT_KEY'], 'HS256')
    );
}
