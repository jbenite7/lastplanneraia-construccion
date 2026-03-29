<?php

namespace App\Services\Auth;

use App\Services\Mail\SmtpMailer;
use Database;

class PasswordResetService
{
    private const TOKEN_TTL_SECONDS = 3600;

    private $db;
    private $mailer;
    private $passwords;

    public function __construct($db = null, ?SmtpMailer $mailer = null, ?UserPasswordService $passwords = null)
    {
        $this->db = $db ?: Database::getInstance();
        $this->mailer = $mailer ?: new SmtpMailer();
        $this->passwords = $passwords ?: new UserPasswordService($this->db);
    }

    public function request(string $email, string $scope): bool
    {
        $scope = $this->normalizeScope($scope);
        $normalizedEmail = $this->normalizeEmail($email);

        if ($normalizedEmail === '' || !filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
            $this->audit('RESET_CLAVE_SOLICITUD_INVALIDA', "Solicitud de restablecimiento descartada para correo inválido: {$normalizedEmail}");
            return false;
        }

        $user = $this->findEligibleUserByEmail($normalizedEmail);
        if ($user === null) {
            $this->audit('RESET_CLAVE_SOLICITUD_IGNORADA', "Solicitud de restablecimiento ignorada para {$normalizedEmail} ({$scope})");
            return false;
        }

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);
        $expiresAt = date('Y-m-d H:i:s', time() + self::TOKEN_TTL_SECONDS);
        $requestedIp = substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);

        $this->invalidateTokensByUsername((string) $user['usuario'], $scope);

        $stmt = $this->db->prepare(
            'INSERT INTO password_reset_tokens (user_id, scope, token_hash, requested_ip, expires_at) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([(int) $user['id'], $scope, $tokenHash, $requestedIp, $expiresAt]);
        $tokenId = (int) $this->db->lastInsertId();
        $appUrl = $this->resolveAppUrl();
        $resetUrl = $this->buildResetUrl($appUrl, $scope, $plainToken);

        try {
            $this->mailer->send(
                (string) $user['email'],
                (string) ($user['nombre'] ?? $user['usuario']),
                'Restablece tu contraseña en Last Planner AIA',
                $this->buildHtmlMessage((string) ($user['nombre'] ?? $user['usuario']), $scope, $resetUrl, $appUrl),
                $this->buildTextMessage((string) ($user['nombre'] ?? $user['usuario']), $scope, $resetUrl)
            );
        } catch (\Throwable $e) {
            $this->deleteToken($tokenId);
            error_log('PasswordResetService::request ' . $e->getMessage());
            $this->audit('RESET_CLAVE_ENVIO_FALLIDO', "No fue posible enviar el correo de restablecimiento a {$normalizedEmail} ({$scope})");
            return false;
        }

        $this->audit('RESET_CLAVE_ENVIADO', "Se envió un enlace de restablecimiento a {$normalizedEmail} ({$scope})");
        return true;
    }

    public function findValidToken(string $plainToken, string $scope): ?array
    {
        $plainToken = trim($plainToken);
        if ($plainToken === '') {
            return null;
        }

        $scope = $this->normalizeScope($scope);
        $tokenHash = hash('sha256', $plainToken);

        $stmt = $this->db->prepare(
            'SELECT prt.id AS token_id, prt.user_id, prt.scope, prt.expires_at, u.usuario, u.nombre, u.email, u.activo
             FROM password_reset_tokens prt
             INNER JOIN general_usuarios u ON u.id = prt.user_id
             WHERE prt.token_hash = ?
               AND prt.scope = ?
               AND prt.used_at IS NULL
               AND prt.expires_at >= NOW()
             ORDER BY prt.id DESC
             LIMIT 1'
        );
        $stmt->execute([$tokenHash, $scope]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        if ((int) ($row['activo'] ?? 0) !== 1 || trim((string) ($row['usuario'] ?? '')) === '') {
            return null;
        }

        return $row;
    }

    public function reset(string $plainToken, string $scope, string $password, string $confirm): array
    {
        $tokenData = $this->findValidToken($plainToken, $scope);
        if ($tokenData === null) {
            return ['success' => false, 'message' => 'El enlace no es válido o ya expiró. Solicita uno nuevo.'];
        }

        $result = $this->passwords->changePasswordForUsername((string) $tokenData['usuario'], $password, $confirm, true);
        if (!$result['success']) {
            return $result;
        }

        $this->invalidateTokensByUsername((string) $tokenData['usuario'], $this->normalizeScope($scope));
        $this->audit(
            'RESET_CLAVE_COMPLETADO',
            "El usuario {$tokenData['usuario']} restableció su contraseña desde {$this->normalizeScope($scope)}"
        );

        return ['success' => true, 'message' => 'Contraseña restablecida correctamente.'];
    }

    private function findEligibleUserByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, usuario, nombre, email, activo
             FROM general_usuarios
             WHERE LOWER(TRIM(email)) = ?
             ORDER BY activo DESC, id ASC'
        );
        $stmt->execute([$email]);
        $rows = $stmt->fetchAll();

        if (!$rows) {
            return null;
        }

        $activeRows = array_values(array_filter($rows, static function ($row) {
            return (int) ($row['activo'] ?? 0) === 1;
        }));

        if (!$activeRows) {
            return null;
        }

        $usernames = [];
        foreach ($activeRows as $row) {
            $username = trim((string) ($row['usuario'] ?? ''));
            if ($username === '') {
                continue;
            }
            $usernames[strtolower($username)] = $username;
        }

        if (count($usernames) !== 1) {
            return null;
        }

        return $activeRows[0];
    }

    private function invalidateTokensByUsername(string $username, string $scope): void
    {
        $stmt = $this->db->prepare(
            'UPDATE password_reset_tokens prt
             INNER JOIN general_usuarios u ON u.id = prt.user_id
             SET prt.used_at = NOW()
             WHERE u.usuario = ?
               AND prt.scope = ?
               AND prt.used_at IS NULL'
        );
        $stmt->execute([$username, $scope]);
    }

    private function deleteToken(int $tokenId): void
    {
        $stmt = $this->db->prepare('DELETE FROM password_reset_tokens WHERE id = ?');
        $stmt->execute([$tokenId]);
    }

    private function buildResetUrl(string $baseUrl, string $scope, string $plainToken): string
    {
        $path = $this->normalizeScope($scope) === 'admin' ? '/admin/password/reset' : '/password/reset';
        return rtrim($baseUrl, '/') . $path . '?token=' . urlencode($plainToken);
    }

    private function buildHtmlMessage(string $displayName, string $scope, string $resetUrl, string $baseUrl): string
    {
        $portal = $this->portalLabel($scope);
        $portalContext = $this->normalizeScope($scope) === 'admin'
            ? 'Tu acceso al panel administrativo de Last Planner AIA'
            : 'Tu acceso al portal de usuarios de Last Planner AIA';
        $safeName = htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8');
        $safePortal = htmlspecialchars($portal, ENT_QUOTES, 'UTF-8');
        $safePortalContext = htmlspecialchars($portalContext, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
        $safeLogoUrl = htmlspecialchars($this->buildLogoUrl($baseUrl), ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<body style="margin:0;padding:0;background-color:#f4f1ea;color:#1c1c1e;">
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;visibility:hidden;">
    Restablece tu contraseña de forma segura en Last Planner AIA.
  </div>
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;background-color:#f4f1ea;">
    <tr>
      <td align="center" style="padding:32px 16px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;max-width:640px;border-collapse:collapse;">
          <tr>
            <td style="padding:0 0 16px 0;text-align:center;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin:0 auto;background-color:#f7f5ef;border:1px solid #d8d2c4;border-radius:12px;">
                <tr>
                  <td style="padding:14px 18px;text-align:center;">
                    <img src="{$safeLogoUrl}" alt="AIA - Construimos por Naturaleza" width="252" style="display:inline-block;width:100%;max-width:252px;height:auto;border:0;" />
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background-color:#fafafa;border:1px solid #d1d1d1;border-radius:8px;padding:40px 32px;">
              <p style="margin:0 0 12px 0;font-family:Inter,Arial,sans-serif;font-size:14px;line-height:1.5;color:#4a7c64;">
                Seguridad de acceso
              </p>
              <h1 style="margin:0 0 16px 0;font-family:Montserrat,Arial,sans-serif;font-size:32px;line-height:1.2;font-weight:700;letter-spacing:-0.02em;color:#1a5633;">
                Restablece tu contraseña
              </h1>
              <p style="margin:0 0 16px 0;font-family:Inter,Arial,sans-serif;font-size:16px;line-height:1.6;color:#1c1c1e;">
                Hola {$safeName}, recibimos una solicitud para restablecer la contraseña de tu cuenta.
              </p>
              <p style="margin:0 0 24px 0;font-family:Inter,Arial,sans-serif;font-size:16px;line-height:1.6;color:#1c1c1e;">
                En Last Planner AIA construimos con +CERTEZA, por eso este proceso protege tu acceso y solo estará disponible durante 60 minutos.
              </p>
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;background-color:#f4f1ea;border-radius:8px;margin:0 0 24px 0;">
                <tr>
                  <td style="padding:18px 20px;font-family:Inter,Arial,sans-serif;font-size:14px;line-height:1.6;color:#1c1c1e;">
                    <strong style="color:#1a3c2a;">Acceso asociado:</strong> {$safePortalContext}<br />
                    <strong style="color:#1a3c2a;">Canal:</strong> {$safePortal}<br />
                    <strong style="color:#1a3c2a;">Vigencia:</strong> 60 minutos y un solo uso
                  </td>
                </tr>
              </table>
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin:0 0 24px 0;">
                <tr>
                  <td align="center" bgcolor="#1a5633" style="border-radius:4px;">
                    <a href="{$safeUrl}" style="display:inline-block;padding:14px 24px;font-family:Inter,Arial,sans-serif;font-size:16px;font-weight:500;line-height:1.2;letter-spacing:0.02em;color:#fafafa;text-decoration:none;">
                      Restablecer contraseña
                    </a>
                  </td>
                </tr>
              </table>
              <p style="margin:0 0 8px 0;font-family:Inter,Arial,sans-serif;font-size:14px;line-height:1.6;color:#4a4a4d;">
                Si el botón no funciona, copia y pega este enlace en tu navegador:
              </p>
              <p style="margin:0 0 24px 0;font-family:Inter,Arial,sans-serif;font-size:14px;line-height:1.6;color:#1a5633;word-break:break-word;">
                <a href="{$safeUrl}" style="color:#1a5633;text-decoration:underline;">{$safeUrl}</a>
              </p>
              <p style="margin:0 0 12px 0;font-family:Inter,Arial,sans-serif;font-size:15px;line-height:1.6;color:#1c1c1e;">
                Si no solicitaste este cambio, puedes ignorar este correo con tranquilidad. Tu contraseña actual seguirá vigente mientras no completes el restablecimiento.
              </p>
              <p style="margin:0;font-family:Inter,Arial,sans-serif;font-size:14px;line-height:1.6;color:#4a4a4d;">
                Arquitectos e Ingenieros Asociados<br />
                Construimos por Naturaleza
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    private function buildTextMessage(string $displayName, string $scope, string $resetUrl): string
    {
        $portal = $this->portalLabel($scope);

        return "Hola {$displayName},\n\n"
            . "Recibimos una solicitud para restablecer la contraseña de tu cuenta en Last Planner AIA ({$portal}).\n"
            . "Este enlace estará disponible durante 60 minutos y solo puede utilizarse una vez:\n{$resetUrl}\n\n"
            . "Si no solicitaste este cambio, puedes ignorar este mensaje. Tu contraseña actual seguirá vigente mientras no completes el restablecimiento.\n\n"
            . "Arquitectos e Ingenieros Asociados\n"
            . "Construimos por Naturaleza";
    }

    private function buildLogoUrl(string $baseUrl): string
    {
        return rtrim($baseUrl, '/') . '/img/logoHorizontal.png';
    }

    private function portalLabel(string $scope): string
    {
        return $this->normalizeScope($scope) === 'admin' ? 'Panel administrativo' : 'Portal de usuarios';
    }

    private function normalizeScope(string $scope): string
    {
        return strtolower(trim($scope)) === 'admin' ? 'admin' : 'app';
    }

    private function normalizeEmail(string $email): string
    {
        $email = trim($email);
        return function_exists('mb_strtolower') ? mb_strtolower($email, 'UTF-8') : strtolower($email);
    }

    private function resolveAppUrl(): string
    {
        $configured = trim((string) ($_ENV['APP_URL'] ?? $_SERVER['APP_URL'] ?? getenv('APP_URL') ?? ''));

        if ($configured === '') {
            throw new \RuntimeException('Falta configurar APP_URL para generar enlaces de restablecimiento.');
        }

        return rtrim($configured, '/');
    }

    private function audit(string $action, string $description): void
    {
        if (method_exists($this->db, 'logActivity')) {
            $this->db->logActivity('Seguridad', $action, $description);
        }
    }
}
