<?php

use PiPHP\GPIO\GPIO;
use PiPHP\GPIO\Pin\PinInterface;

function open_door($gpio_pin, $seconds_open)
{
    $gpio = new GPIO();
    $pin = $gpio->getOutputPin($gpio_pin);

    $pin->setValue(PinInterface::VALUE_HIGH);
    sleep($seconds_open);
    $pin->setValue(PinInterface::VALUE_LOW);
}
