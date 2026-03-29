<?php

namespace App\Services\Mail;

use PHPMailer\PHPMailer\PHPMailer;

class SmtpMailer
{
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): void
    {
        $host = $this->requireConfig('MAIL_HOST');
        $username = $this->requireConfig('MAIL_USERNAME');
        $password = $this->requireConfig('MAIL_PASSWORD');
        $fromAddress = $this->env('MAIL_FROM_ADDRESS', $username);
        $fromName = $this->env('MAIL_FROM_NAME', 'Last Planner AIA');
        $encryption = strtolower($this->env('MAIL_ENCRYPTION', 'tls'));
        $port = (int) $this->env('MAIL_PORT', $encryption === 'ssl' ? '465' : '587');

        $mailer = new PHPMailer(true);
        $mailer->CharSet = 'UTF-8';
        $mailer->isSMTP();
        $mailer->Host = $host;
        $mailer->Port = $port;
        $mailer->SMTPAuth = true;
        $mailer->Username = $username;
        $mailer->Password = $password;
        $mailer->SMTPAutoTLS = true;
        $mailer->Timeout = 15;

        if ($encryption === 'ssl') {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mailer->setFrom($fromAddress, $fromName);
        $mailer->addAddress($toEmail, $toName);
        $mailer->isHTML(true);
        $mailer->Subject = $subject;
        $mailer->Body = $htmlBody;
        $mailer->AltBody = $textBody !== '' ? $textBody : trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody)));
        $mailer->send();
    }

    private function requireConfig(string $key): string
    {
        $value = trim($this->env($key, ''));
        if ($value === '') {
            throw new \RuntimeException("Falta configurar {$key} para el envio SMTP.");
        }

        return $value;
    }

    private function env(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return (string) $value;
    }
}
