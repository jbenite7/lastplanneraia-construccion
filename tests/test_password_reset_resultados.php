<?php

declare(strict_types=1);

/**
 * Caracteriza los tres resultados de PasswordResetService::request() (hallazgo B-10).
 *
 * Antes devolvía `bool` y el controlador lo descartaba, así que una caída total del correo
 * se veía igual que un envío correcto. Esta red existe para que esa distinción no se pierda
 * en un refactor: si `request()` vuelve a colapsar «falló el envío» con «no hay a quién
 * enviar», el caso 2 se pone rojo.
 *
 * Ver docs/superpowers/specs/2026-08-10-programa-cierre-pendientes-design.md
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\Auth\PasswordResetService;
use App\Services\Mail\SmtpMailer;

// El servicio necesita APP_URL para construir el enlace de restablecimiento. `Database`
// resuelve sus credenciales de las variables de entorno del contenedor, pero APP_URL solo
// vive en `.env` y normalmente la carga el front controller (`public/index.php`), que este
// script autoejecutable no pasa por ahí. Se replica esa carga aquí, igual que el patrón.
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}

/** Emisor que siempre entrega. */
final class MailerQueEntrega extends SmtpMailer
{
    public int $enviados = 0;

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): void
    {
        $this->enviados++;
    }
}

/** Emisor que simula una caída total del relay. */
final class MailerRoto extends SmtpMailer
{
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): void
    {
        throw new \RuntimeException('relay caído (simulado)');
    }
}

$fallos = 0;
$total  = 0;

function comprobar(string $caso, string $esperado, string $obtenido): void
{
    global $fallos, $total;
    $total++;
    if ($esperado === $obtenido) {
        echo "  OK   {$caso}: {$obtenido}\n";
        return;
    }
    $fallos++;
    echo "  FALLA {$caso}: esperaba «{$esperado}», obtuvo «{$obtenido}»\n";
}

$db = Database::getInstance();

// Cuenta real y habilitada del entorno de desarrollo. Se lee de la base en vez de
// escribirse a mano para que el test no invente un usuario que no existe.
// Nota: `general_usuarios` no tiene columna `correo`; el correo vive en `email` (confirmado
// con DESCRIBE general_usuarios). El brief decía «correo» pero eso no existe en el esquema
// real, así que se usa `email` en toda la prueba.
$stmt = $db->prepare(
    "SELECT email FROM general_usuarios WHERE email IS NOT NULL AND email <> '' AND activo = 1 LIMIT 1"
);
$stmt->execute();
$correoRegistrado = (string) ($stmt->fetchColumn() ?: '');

if ($correoRegistrado === '') {
    echo "SALTADO: no hay ningún usuario activo con correo en general_usuarios.\n";
    exit(0);
}

function tokensDe(string $correo): int
{
    $db = Database::getInstance();
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM password_reset_tokens prt
         JOIN general_usuarios u ON u.id = prt.user_id
         WHERE u.email = ?'
    );
    $stmt->execute([$correo]);
    return (int) $stmt->fetchColumn();
}

/** IDs existentes antes de que el test cree nada, para poder restaurar exactamente. */
function idsDe(string $correo): array
{
    $db = Database::getInstance();
    $stmt = $db->prepare(
        'SELECT prt.id FROM password_reset_tokens prt
         JOIN general_usuarios u ON u.id = prt.user_id
         WHERE u.email = ?'
    );
    $stmt->execute([$correo]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

$idsAntes = idsDe($correoRegistrado);
$tokensAntes = count($idsAntes);

echo "Caso 1 — registrado y el correo sale:\n";
$mailerOk = new MailerQueEntrega();
$servicio = new PasswordResetService($db, $mailerOk);
comprobar('resultado', PasswordResetService::RESULTADO_ENVIADO, $servicio->request($correoRegistrado, 'app'));
comprobar('se intentó enviar', '1', (string) $mailerOk->enviados);

echo "Caso 2 — registrado y el correo FALLA (el corazón de B-10):\n";
$servicioRoto = new PasswordResetService($db, new MailerRoto());
comprobar('resultado', PasswordResetService::RESULTADO_FALLIDO, $servicioRoto->request($correoRegistrado, 'app'));

echo "Caso 3 — dirección no registrada: se calla, no se distingue:\n";
$servicio3 = new PasswordResetService($db, new MailerQueEntrega());
comprobar('resultado', PasswordResetService::RESULTADO_IGNORADO, $servicio3->request('no-existe-jamas@ejemplo.invalid', 'app'));

echo "Caso 4 — formato inválido:\n";
$servicio4 = new PasswordResetService($db, new MailerQueEntrega());
comprobar('resultado', PasswordResetService::RESULTADO_IGNORADO, $servicio4->request('esto-no-es-un-correo', 'app'));

echo "Caso 5 — un envío fallido no deja el token huérfano:\n";
// El caso 1 inserta un token nuevo. `invalidateTokensByUsername()` en el caso 2 marca
// `used_at` sobre los tokens previos (incluido el del caso 1) pero NO los borra; luego inserta
// su propio token y el `catch` de PasswordResetService::request() lo borra por su `id` cuando
// el envío falla. Insertar-y-borrar en el caso 2 se cancela, así que el único token que queda
// vivo en la tabla es el que dejó el caso 1: el conteo neto es tokensAntes + 1, no +2 ni +0.
// Los casos 3 y 4 nunca llegan a invalidateTokensByUsername() porque el usuario no se
// encuentra (correo ajeno o formato inválido), así que no tocan las filas de este usuario.
comprobar('tokens tras los cuatro casos', (string) ($tokensAntes + 1), (string) tokensDe($correoRegistrado));

// Restauración: se borran exactamente los ids que este test creó (los que no estaban en
// idsAntes) y se verifica que la cuenta vuelve al valor de partida. No se supone: se mide.
$idsDespues = idsDe($correoRegistrado);
$idsACrear = array_diff($idsDespues, $idsAntes);
if ($idsACrear !== []) {
    $marcadores = implode(',', array_fill(0, count($idsACrear), '?'));
    $limpieza = $db->prepare("DELETE FROM password_reset_tokens WHERE id IN ({$marcadores})");
    $limpieza->execute(array_values($idsACrear));
}
comprobar('base restaurada', (string) $tokensAntes, (string) tokensDe($correoRegistrado));

echo "\n{$total} comprobaciones, {$fallos} fallos.\n";
exit($fallos === 0 ? 0 : 1);
