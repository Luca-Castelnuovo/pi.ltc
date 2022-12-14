<?php

require '../vendor/autoload.php';

require '../includes/env.php';
require '../includes/auth.php';
require '../includes/mail.php';
require '../includes/door.php';

define('GPIO_PIN', 18);
define('SECONDS_OPEN', 2);

try {
    $data = check_auth($_SERVER["HTTP_AUTHORIZATION"] ?? $_GET['authorization']);
} catch (\Throwable $th) {
    respond('Authentication failed', 401);
}

try {
    open_door(GPIO_PIN, SECONDS_OPEN);
} catch (\Throwable $th) {
    respond('Door could not be opened', 500);
}

try {
    send_mail($data->sub);
} catch (\Throwable $th) {
    respond('Email could not be sent', 500);
}

respond('ok');
