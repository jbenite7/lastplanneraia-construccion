<?php

declare(strict_types=1);

/**
 * El transporte de correo es elegible, y `sendmail` no exige credenciales SMTP.
 *
 * Por que existe esta red (2026-08-18): los correos de restablecimiento salian por un relay
 * externo (Brevo) y el filtro corporativo del destinatario los aceptaba y los hacia desaparecer
 * —sin rebote, sin cuarentena visible—. Medido ese dia: por Brevo no llegaban; por el `sendmail`
 * local del hosting, con el mismo remitente, SI llegaban. La diferencia no es la autenticacion
 * (ambos caminos firman igual) sino la categoria de la IP: pool de envio masivo frente a IP de
 * hosting.
 *
 * Lo que esta prueba protege es que `MAIL_TRANSPORT=sendmail` NO pida MAIL_HOST/USERNAME/PASSWORD.
 * Si alguien vuelve a meter esas tres en el camino obligatorio, produccion se queda sin correo:
 * ahi no hay credenciales SMTP que dar, porque el MTA local no las pide.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\Mail\SmtpMailer;

if (file_exists(__DIR__ . '/../.env')) {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
}

/** Expone el emisor construido sin llegar a enviar nada. */
final class MailerInspeccionable extends SmtpMailer
{
    public function inspeccionar(): PHPMailer\PHPMailer\PHPMailer
    {
        return $this->buildMailer();
    }
}

/**
 * Limpia una variable de los TRES sitios de los que lee `SmtpMailer::env()`.
 *
 * `Dotenv::safeLoad()` puebla `$_ENV` y `$_SERVER`, asi que borrar solo `$_ENV` deja el valor
 * vivo y la prueba pasa en falso. Se descubrio el 2026-08-18 al re-verificar tras integrar: el
 * caso 3 daba verde donde el .env no cargaba y rojo donde si.
 */
function limpiar(string $clave): void
{
    unset($_ENV[$clave], $_SERVER[$clave]);
    putenv($clave);
}

/** Fija una variable en los mismos tres sitios, para que no gane un valor residual. */
function poner(string $clave, string $valor): void
{
    $_ENV[$clave] = $valor;
    $_SERVER[$clave] = $valor;
    putenv("{$clave}={$valor}");
}

$fallos = [];
$originales = $_ENV;

function caso(string $nombre, callable $fn, array &$fallos): void
{
    try {
        $fn();
        echo "  ok  {$nombre}\n";
    } catch (\Throwable $e) {
        $fallos[] = "{$nombre}: " . $e->getMessage();
        echo "  FALLA  {$nombre}: " . $e->getMessage() . "\n";
    }
}

function afirmar(bool $cond, string $mensaje): void
{
    if (!$cond) {
        throw new \RuntimeException($mensaje);
    }
}

echo "=== Transporte de correo ===\n";

// Caso 1: sendmail no necesita credenciales SMTP.
caso('MAIL_TRANSPORT=sendmail construye sin host ni credenciales', function (): void {
    poner('MAIL_TRANSPORT', 'sendmail');
    poner('MAIL_FROM_ADDRESS', 'no-responder@ejemplo.test');
    limpiar('MAIL_HOST'); limpiar('MAIL_USERNAME'); limpiar('MAIL_PASSWORD');
    $m = (new MailerInspeccionable())->inspeccionar();
    afirmar($m->Mailer === 'sendmail', "esperaba Mailer=sendmail, obtuve '{$m->Mailer}'");
}, $fallos);

// Caso 2: el remitente se respeta en sendmail.
caso('sendmail conserva MAIL_FROM_ADDRESS', function (): void {
    poner('MAIL_TRANSPORT', 'sendmail');
    poner('MAIL_FROM_ADDRESS', 'no-responder@ejemplo.test');
    limpiar('MAIL_HOST'); limpiar('MAIL_USERNAME'); limpiar('MAIL_PASSWORD');
    $m = (new MailerInspeccionable())->inspeccionar();
    afirmar($m->From === 'no-responder@ejemplo.test', "esperaba el remitente configurado, obtuve '{$m->From}'");
}, $fallos);

// Caso 3: el camino SMTP sigue exigiendo sus credenciales (no se afloja la validacion).
caso('SMTP sigue exigiendo MAIL_HOST', function (): void {
    poner('MAIL_TRANSPORT', 'smtp');
    limpiar('MAIL_HOST');
    poner('MAIL_USERNAME', 'u');
    poner('MAIL_PASSWORD', 'p');
    try {
        (new MailerInspeccionable())->inspeccionar();
    } catch (\RuntimeException $e) {
        afirmar(str_contains($e->getMessage(), 'MAIL_HOST'), 'el error deberia nombrar MAIL_HOST');
        return;
    }
    throw new \RuntimeException('esperaba que faltar MAIL_HOST lanzara');
}, $fallos);

// Caso 4: sin MAIL_TRANSPORT el comportamiento historico (SMTP) se mantiene.
caso('sin MAIL_TRANSPORT el defecto sigue siendo SMTP', function (): void {
    limpiar('MAIL_TRANSPORT');
    poner('MAIL_HOST', 'smtp.ejemplo.test');
    poner('MAIL_USERNAME', 'u');
    poner('MAIL_PASSWORD', 'p');
    $m = (new MailerInspeccionable())->inspeccionar();
    afirmar($m->Mailer === 'smtp', "esperaba Mailer=smtp, obtuve '{$m->Mailer}'");
}, $fallos);

$_ENV = $originales;

if ($fallos) {
    echo "\n=== Transporte de correo: FALLA (" . count($fallos) . ") ===\n";
    exit(1);
}
echo "\n=== Transporte de correo: OK ===\n";
