<?php

use CQ\Crypto\Helpers\Token;

function check_auth(?string $token)
{
    $data = Token::decrypt(
        key: $_ENV['TOKEN_KEY'],
        token: $token
    );

    if ($data->expiration < time()) {
        throw new Exception('Token expired');
    }

    return $data;
}
