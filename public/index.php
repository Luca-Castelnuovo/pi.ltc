<?php

require '../vendor/autoload.php';

use PiPHP\GPIO\GPIO;
use PiPHP\GPIO\Pin\PinInterface;

// TODO: paseto tokens

// TODO: email

// TODO: validate authorization header
// maybe with JWT?

$gpio = new GPIO();

// Retrieve pin 18 and configure it as an output pin
$pin = $gpio->getOutputPin(18);

// Turn it on
$pin->setValue(PinInterface::VALUE_HIGH);

sleep(5);

// Turn it off
$pin->setValue(PinInterface::VALUE_LOW);

echo 'done';
