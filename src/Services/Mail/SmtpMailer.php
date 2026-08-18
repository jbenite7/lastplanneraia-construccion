<?php

namespace App\Services\Mail;

use PHPMailer\PHPMailer\PHPMailer;

class SmtpMailer
{
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): void
    {
        $mailer = $this->buildMailer();

        $mailer->addAddress($toEmail, $toName);
        $mailer->isHTML(true);
        $mailer->Subject = $subject;
        $mailer->Body = $htmlBody;
        $mailer->AltBody = $textBody !== '' ? $textBody : trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody)));
        $mailer->send();
    }

    /**
     * Construye el emisor ya configurado, sin destinatario ni contenido.
     *
     * El transporte se elige con `MAIL_TRANSPORT`. Existe porque el 2026-08-18 se midio que el
     * relay externo (Brevo) no llegaba a los buzones corporativos del destinatario: su filtro
     * aceptaba el mensaje con un 250 y lo hacia desaparecer —sin rebote y sin cuarentena visible—
     * mientras el MISMO remitente, enviado por el `sendmail` local del hosting, si entraba en
     * bandeja. Ambos caminos firman igual (SPF por `+a`, DKIM del propio hosting); lo que cambia
     * es la categoria de la IP de origen: pool de envio masivo frente a IP de hosting.
     *
     * `sendmail` NO pide credenciales, y esa es la parte que hay que respetar: el MTA local no
     * tiene usuario ni contrasena que dar. Si se vuelven a exigir MAIL_HOST/USERNAME/PASSWORD en
     * el camino comun, produccion se queda muda. Lo cubre `tests/test_mail_transport.php`.
     */
    protected function buildMailer(): PHPMailer
    {
        $fromName = $this->env('MAIL_FROM_NAME', 'Last Planner AIA');
        $transport = strtolower(trim($this->env('MAIL_TRANSPORT', 'smtp')));

        $mailer = new PHPMailer(true);
        $mailer->CharSet = 'UTF-8';

        if ($transport === 'sendmail') {
            $mailer->isSendmail();
            $fromAddress = $this->requireConfig('MAIL_FROM_ADDRESS');
            $mailer->setFrom($fromAddress, $fromName);
            $mailer->isHTML(true);

            return $mailer;
        }

        $host = $this->requireConfig('MAIL_HOST');
        $username = $this->requireConfig('MAIL_USERNAME');
        $password = $this->requireConfig('MAIL_PASSWORD');
        $fromAddress = $this->env('MAIL_FROM_ADDRESS', $username);
        $encryption = strtolower($this->env('MAIL_ENCRYPTION', 'tls'));
        $port = (int) $this->env('MAIL_PORT', $encryption === 'ssl' ? '465' : '587');

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
        $mailer->isHTML(true);

        return $mailer;
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
