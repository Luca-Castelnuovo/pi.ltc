<?php

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();

function respond(string $message = "", int $code = 200)
{
    http_response_code($code);
    echo $message . PHP_EOL;
    exit;
}
