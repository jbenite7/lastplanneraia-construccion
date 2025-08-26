<?php

use PHPMailer\PHPMailer\PHPMailer;
require '../../../../vendor/autoload.php';
$mail = new PHPMailer;
$mail->isSMTP();
$mail->SMTPDebug = 2;
$mail->Host = 'smtp.hostinger.com';
$mail->Port = 465;
$mail->SMTPAuth = true;
$mail->Username = 'notificaciones@lastplanneraia.com';
$mail->Password = 'Jbe#1106z';
$mail->setFrom('notificaciones@lastplanneraia.com', 'Last Planner AIA');
$mail->addReplyTo('notificaciones@lastplanneraia.com', 'Last Planner AIA');
$mail->addAddress('jbenitez@aia.com.co');
$mail->addAddress('jbenite7@hotmail.com');
$mail->Subject = 'Testing PHPMailer';
$mail->msgHTML(file_get_contents('message.html'), __DIR__);
$mail->Body = 'This is a plain text message body';
//$mail->addAttachment('test.txt');
if (!$mail->send()) {
    echo 'Mailer Error: ' . $mail->ErrorInfo;
} else {
    echo 'The email message was sent.';
}
?>
