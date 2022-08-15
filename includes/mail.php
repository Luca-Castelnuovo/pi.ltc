<?php

use PHPMailer\PHPMailer\PHPMailer;

function send_mail($name)
{
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = 'mx.email.castelnuovo.dev';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'castelnuovo/production';
    $mail->Password   = $_ENV['MAIL_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 25;

    $mail->setFrom('vosstraat@email.castelnuovo.dev', 'Vosstraat');
    $mail->addAddress('vosstraattwee@gmail.com');

    $time = date('H:i');
    $date = date('F j, Y');

    $mail->Subject = "Deur geopend door {$name} om {$time} op {$date}";
    $mail->Body    = <<<MAIL
Deur geopend door:

Name: $name
Time: $time
Date: $date

User agent: {$_SERVER['HTTP_USER_AGENT']}
MAIL;

    $mail->send();
}
