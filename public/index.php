<?php

require '../vendor/autoload.php';

require '../includes/env.php';
require '../includes/auth.php';
require '../includes/mail.php';
require '../includes/door.php';

define('GPIO_PIN', 18);
define('SECONDS_OPEN', 5);

try {
    $data = check_auth(token: $_SERVER["HTTP_AUTHORIZATION"] ?? $_GET['authorization']);
} catch (\Throwable $th) {
    echo 'Authentication failed' . PHP_EOL;
    exit;
}

try {
    send_mail(name: $data->name);
} catch (\Throwable $th) {
    echo 'Email could not be sent' . PHP_EOL;
    exit;
}

try {
    open_door(gpio_pin: GPIO_PIN, seconds_open: SECONDS_OPEN);
} catch (\Throwable $th) {
    echo 'Door could not be opened' . PHP_EOL;
    exit;
}

echo 'ok' . PHP_EOL;
