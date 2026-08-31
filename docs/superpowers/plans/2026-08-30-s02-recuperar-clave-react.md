# S02 Password Recovery React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** migrar `/password/forgot` a la SPA React con formulario accesible, contrato JSON no enumerativo, error técnico honesto y rollback comprobable, sin cambiar correo, tokens, RLS ni datos.

**Architecture:** S02 consume el acceso y cliente tipado entregados por S01, activa el incremento mínimo de `BrowserRouter` y agrega una ruta pública que prevalece sobre el estado de sesión. React solo maneja presentación; `PasswordRecoveryApiController` valida JSON/CSRF y adapta los tres resultados de `PasswordResetService::request(email, 'app')` a un contrato que nunca filtra `enviado` frente a `ignorado`.

**Tech Stack:** PHP 8.3, React 19, TypeScript 7, react-router-dom 7, Vite 8, Zod 4, Vitest 4, Testing Library, Playwright 1.61, CSS design system AIA.

**Spec:** `docs/superpowers/specs/2026-08-30-s02-recuperar-clave-react-design.md`

## Global Constraints

- Trabajar exclusivamente en `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react`, rama `shell-minimo-react`; no usar `/Volumes/Crucial X6/Developer/lps-aia`.
- Ejecutar S02 únicamente después de completar el plan S01 `docs/superpowers/plans/2026-08-30-s01-login-react.md`; las interfaces `MarcoAcceso`, `ErrorApi`, `auth.ts`, `auth-react.css`, `SpaHostRenderer` y `SpaRouter::coincideConMapa()` son precondiciones.
- Conservar cambios preexistentes; revisar status/diff antes de editar y usar staging selectivo.
- `/admin/` queda fuera: no modificar `admin/public`, `admin/src`, `admin/views`, rutas, mensajes ni recuperación administrativa.
- No modificar ni ejecutar RLS, DDL, DML, schema, migraciones, grants, usuarios, credenciales o datos. No correr `tests/test_password_reset_resultados.php` porque escribe tokens.
- No enviar correo real ni conectar SMTP. PHP usa un `PasswordResetService` fake y navegador intercepta la API.
- No cambiar `PasswordResetService`, `SmtpMailer`, TTL de 3600 segundos, hash, invalidación, plantillas, transporte, `APP_URL`, auditoría o tabla de tokens salvo un ajuste estrictamente mecánico exigido por compilación; cualquier cambio funcional requiere una spec distinta.
- Todo `fetch` productivo permanece en `frontend/src/lib/api/cliente.ts`; componentes llaman el gateway `frontend/src/lib/api/auth.ts`.
- El endpoint nuevo lleva esquema Zod consumidor y prueba contractual PHP sin DB.
- React usa CSRF `shell_api` por `X-CSRF-Token`; el POST legacy mantiene `password_forgot` hasta su retiro y ningún token sirve para el otro contrato.
- El endpoint acepta exactamente `{email}` y PHP fija `scope='app'`; nunca acepta `scope`, username, proyecto, `project_id`, `db`, prefijo o rol.
- `RESULTADO_ENVIADO` y `RESULTADO_IGNORADO` producen exactamente el mismo `200` y body. `RESULTADO_FALLIDO` o excepción producen `503 recovery_unavailable` sin detalle interno.
- No hay retry automático. Un 403 solo actualiza sesión/CSRF tras acción explícita y nunca reenvía el correo.
- En éxito se limpia email y el formulario permanece disponible, decisión de Felipe.
- Estilos solo con tokens de `public/css/tokens.css`; sin colores literales, estilos inline, `!important` ni un segundo design system.
- Oscuro es el fallback inicial; claro y oscuro conservan la misma capacidad.
- Viewports obligatorios: `390×844`, `768×1024`, `1180×820` y `1440×900`; 1180×820 dark es el gate principal.
- No registrar ni capturar correos reales, CSRF, cookies, tokens de reset, rutas ocultas, payloads o excepciones sensibles.
- Mantenimiento conserva 503 público; no eximir `/password/forgot` ni `/api/auth/password/forgot`.
- S03 `/password/reset`, `views/auth/password-reset.view.php`, `login-brand-unified.css` y `auth_forms.js` permanecen hasta su propio corte.
- No regenerar goldens ni reemplazar baselines sin aprobación visual explícita de Felipe. Los candidatos quedan fuera de git hasta aprobarlos.
- No desplegar a producción. Commits/PR solo durante ejecución autorizada y conforme al gate del repositorio.

## File Structure

### Create

- `frontend/src/shell/auth/PantallaRecuperarClave.tsx` — estado de formulario S02, validación, feedback y foco; no conoce HTTP.
- `frontend/src/shell/auth/PantallaRecuperarClave.test.tsx` — S02-UX-01…12, estados de error, single-submit y a11y.
- `src/Controllers/Api/PasswordRecoveryApiController.php` — JSON/CSRF/validación y mapping del servicio inyectable.
- `tests/test_api_password_recovery_contract.php` — contrato puro con servicio/body fake; cero DB y cero SMTP.
- `tests/test_api_password_recovery_http.php` — routing real limitado a CSRF/email inválidos; nunca llama al servicio.
- `tests/browser/password-recovery-react.spec.mjs` — navegación, estados interceptados, teclado, Axe y responsive.
- `tests/browser/password-recovery-react.visual.mjs` — candidatos dark/light en cuatro viewports.

### Modify

- `frontend/src/lib/api/esquemas/auth.ts` — request/response S02 derivados con Zod.
- `frontend/src/lib/api/esquemas/auth.test.ts` — igualdad segura y rechazo de propiedades/outcomes internos.
- `frontend/src/lib/api/auth.ts` — `solicitarRecuperacion(email, csrfToken)`.
- `frontend/src/App.tsx` — `BrowserRouter` único sin basename.
- `frontend/src/shell/rutas.tsx` — ruta piloto/canónica pública antes de ramas de sesión.
- `frontend/src/shell/rutas.test.tsx` — anónimo/autenticado/pendiente y aliases S02.
- `public/css/auth-react.css` — ajustes mínimos de campo/avisos S02 dentro de `@layer module`.
- `public/index.php` — API pública, corte GET/HEAD y retiro legacy gateado.
- `src/Core/SpaRouter.php` — exacta `/password/forgot` después del piloto.
- `tests/test_spa_frontera.php` y `tests/test_spa_frontera_http.php` — matriz/corte/rollback/S03.
- `tests/test_login_design_system_contract.mjs` — fuente React S02 y convivencia S03.
- `docs/design-system/manifests/auth.json` — ruta, componente, estados, temas y retiro de VIEW-02 después del gate.
- `public/app/index.html` y `public/app/assets/index-*.js` — build Vite generado, nunca editado a mano.

### Preserve

- `src/Services/Auth/PasswordResetService.php` y `src/Services/Mail/SmtpMailer.php`.
- `src/Controllers/Auth/PasswordResetController.php::reset()/update()/renderReset()`.
- `views/auth/password-reset.view.php`, `GET/POST /password/reset`.
- `public/css/login-brand-unified.css` y `public/js/modules/aia_ui/auth_forms.js` mientras S03 los consume.
- `database/patches/20260329_create_password_reset_tokens.sql` sin edición ni ejecución.

### Retire only after the post-rollout gate

- `views/auth/password-forgot.view.php` (VIEW-02).
- `PasswordResetController::forgot()`, `sendLink()` y `renderForgot()`.
- Legacy `POST /password/forgot`; GET canónico sigue servido por el host SPA.
- VIEW-02 en `docs/design-system/manifests/auth.json` y en el contrato legacy de auth.

---

### Task 1: Extender contratos Zod y gateway de autenticación

**Files:**
- Modify: `frontend/src/lib/api/esquemas/auth.ts`
- Modify: `frontend/src/lib/api/esquemas/auth.test.ts`
- Modify: `frontend/src/lib/api/auth.ts`
- Test: `frontend/src/lib/api/esquemas/auth.test.ts`

**Interfaces:**
- Produces: `EsquemaSolicitudRecuperacion`, `SolicitudRecuperacion`, `EsquemaRecuperacionAceptada`, `RecuperacionAceptada`.
- Produces: `solicitarRecuperacion(email: string, csrfToken: string): Promise<RecuperacionAceptada>`.
- Consumes: `pedir<T>()` y `ErrorApi` entregados por S01.

- [ ] **Step 1: Write failing schema and gateway tests**

Añadir casos de forma estricta, trim, respuesta exacta y request única:

```ts
const MENSAJE_GENERICO = 'Si el correo existe y está habilitado, enviaremos un enlace de restablecimiento en unos minutos.';
const csrfToken = 'a'.repeat(64);

test('la solicitud S02 acepta únicamente un email válido y recortado', () => {
  expect(EsquemaSolicitudRecuperacion.parse({ email: ' persona@empresa.com ' })).toEqual({
    email: 'persona@empresa.com',
  });
  expect(EsquemaSolicitudRecuperacion.safeParse({
    email: 'persona@empresa.com', scope: 'admin',
  }).success).toBe(false);
  expect(EsquemaSolicitudRecuperacion.safeParse({ email: 'sin-formato' }).success).toBe(false);
});

test('la respuesta pública nunca acepta el resultado interno', () => {
  expect(EsquemaRecuperacionAceptada.safeParse({
    success: true,
    message: MENSAJE_GENERICO,
    delivery: 'enviado',
  }).success).toBe(false);
});

test('solicitarRecuperacion usa cliente común y CSRF shell_api', async () => {
  vi.mocked(pedir).mockResolvedValue({ success: true, message: MENSAJE_GENERICO });
  await solicitarRecuperacion(' persona@empresa.com ', csrfToken);
  expect(pedir).toHaveBeenCalledOnce();
  expect(pedir).toHaveBeenCalledWith('/api/auth/password/forgot', EsquemaRecuperacionAceptada, {
    method: 'POST',
    headers: { 'X-CSRF-Token': csrfToken },
    body: JSON.stringify({ email: 'persona@empresa.com' }),
  });
});
```

- [ ] **Step 2: Run focused tests and confirm RED**

Run: `npm --prefix frontend test -- src/lib/api/esquemas/auth.test.ts`

Expected: FAIL porque los esquemas y `solicitarRecuperacion` no existen.

- [ ] **Step 3: Implement strict schemas and gateway**

```ts
export const EsquemaSolicitudRecuperacion = z.object({
  email: z.string().trim().email(),
}).strict();
export type SolicitudRecuperacion = z.infer<typeof EsquemaSolicitudRecuperacion>;

export const EsquemaRecuperacionAceptada = z.object({
  success: z.literal(true),
  message: z.string().min(1),
}).strict();
export type RecuperacionAceptada = z.infer<typeof EsquemaRecuperacionAceptada>;

export async function solicitarRecuperacion(
  email: string,
  csrfToken: string,
): Promise<RecuperacionAceptada> {
  const solicitud = EsquemaSolicitudRecuperacion.parse({ email });
  return pedir('/api/auth/password/forgot', EsquemaRecuperacionAceptada, {
    method: 'POST',
    headers: { 'X-CSRF-Token': csrfToken },
    body: JSON.stringify(solicitud),
  });
}
```

- [ ] **Step 4: Run focused tests, fetch-boundary guard and typecheck**

```bash
npm --prefix frontend test -- src/lib/api/esquemas/auth.test.ts src/lib/api/frontera.test.ts
npm --prefix frontend run typecheck
```

Expected: schemas/gateway/guard PASS y TypeScript RC 0.

- [ ] **Step 5: Commit frontend contracts**

```bash
git add frontend/src/lib/api/esquemas/auth.ts frontend/src/lib/api/esquemas/auth.test.ts frontend/src/lib/api/auth.ts
git commit -m "feat(auth): definir contrato de recuperacion"
```

---

### Task 2: Crear el controlador JSON con contrato puro y sin DB

**Files:**
- Create: `src/Controllers/Api/PasswordRecoveryApiController.php`
- Create: `tests/test_api_password_recovery_contract.php`

**Interfaces:**
- Produces: `PasswordRecoveryApiController::__construct(?PasswordResetService $service = null, ?callable $bodyReader = null)`.
- Produces: `PasswordRecoveryApiController::request(): void`.
- Consumes: `PasswordResetService::request(string $email, string $scope): string` con scope fijo `app`.
- Responses: 200 genérico, 403 `csrf_invalid`, 422 `validation_error`, 503 `recovery_unavailable`.

- [ ] **Step 1: Write the failing controller contract**

Crear un fake sin constructor padre y un helper que capture status/body:

```php
use App\Controllers\Api\PasswordRecoveryApiController;
use App\Security\CsrfTokenManager;
use App\Services\Auth\PasswordResetService;

final class PasswordResetServiceFake extends PasswordResetService
{
    public array $calls = [];

    public function __construct(private string|Throwable $outcome) {}

    public function request(string $email, string $scope): string
    {
        $this->calls[] = [$email, $scope];
        if ($this->outcome instanceof Throwable) {
            throw $this->outcome;
        }
        return $this->outcome;
    }
}

function ejecutar(string $body, ?string $csrf, PasswordResetServiceFake $service): array
{
    $_SERVER['HTTP_X_CSRF_TOKEN'] = $csrf ?? '';
    http_response_code(200);
    ob_start();
    (new PasswordRecoveryApiController($service, static fn(): string => $body))->request();
    return [http_response_code(), json_decode((string) ob_get_clean(), true)];
}

function check(bool $condition, string $label): void
{
    global $failures;
    echo ($condition ? 'OK: ' : 'FAIL: ') . $label . "\n";
    if (!$condition) {
        $failures++;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION = [];
$csrf = CsrfTokenManager::generate('shell_api');
$failures = 0;

$sent = new PasswordResetServiceFake(PasswordResetService::RESULTADO_ENVIADO);
[$sentStatus, $sentBody] = ejecutar('{"email":" persona@example.test "}', $csrf, $sent);
$ignored = new PasswordResetServiceFake(PasswordResetService::RESULTADO_IGNORADO);
[$ignoredStatus, $ignoredBody] = ejecutar('{"email":"persona@example.test"}', $csrf, $ignored);
check($sentStatus === 200 && $ignoredStatus === 200, 'enviado e ignorado responden 200');
check($sentBody === $ignoredBody, 'enviado e ignorado tienen body idéntico');
check($sent->calls === [['persona@example.test', 'app']], 'email recortado y scope app');

$failed = new PasswordResetServiceFake(PasswordResetService::RESULTADO_FALLIDO);
[$failedStatus, $failedBody] = ejecutar('{"email":"persona@example.test"}', $csrf, $failed);
check($failedStatus === 503 && ($failedBody['code'] ?? '') === 'recovery_unavailable', 'fallido responde 503 seguro');

$exception = new PasswordResetServiceFake(new RuntimeException('smtp secreto'));
[$exceptionStatus, $exceptionBody] = ejecutar('{"email":"persona@example.test"}', $csrf, $exception);
check($exceptionStatus === 503 && !str_contains((string) json_encode($exceptionBody), 'smtp secreto'), 'excepción responde 503 sin detalle');

$invalid = new PasswordResetServiceFake(PasswordResetService::RESULTADO_ENVIADO);
[$invalidStatus, $invalidBody] = ejecutar('{"email":"sin-formato"}', $csrf, $invalid);
check($invalidStatus === 422 && isset($invalidBody['fieldErrors']['email']), 'email inválido responde 422');
check($invalid->calls === [], '422 no llama al servicio');

$authority = new PasswordResetServiceFake(PasswordResetService::RESULTADO_ENVIADO);
[$authorityStatus] = ejecutar('{"email":"persona@example.test","scope":"admin"}', $csrf, $authority);
check($authorityStatus === 422 && $authority->calls === [], 'campo de autoridad se rechaza antes del servicio');

$blocked = new PasswordResetServiceFake(PasswordResetService::RESULTADO_ENVIADO);
[$blockedStatus, $blockedBody] = ejecutar('{"email":"persona@example.test"}', 'csrf-invalido', $blocked);
check($blockedStatus === 403 && ($blockedBody['code'] ?? '') === 'csrf_invalid', 'CSRF inválido responde 403');
check($blocked->calls === [], '403 no llama al servicio');

exit($failures === 0 ? 0 : 1);
```

El test no instancia el servicio real, no abre DB y no envía correo. Añadir también JSON roto y body
lista como dos casos 422 con `calls === []`; nunca imprimir el body de una excepción.

- [ ] **Step 2: Run the pure contract and confirm RED**

Run: `LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_api_password_recovery_contract.php`

Expected: FAIL porque `PasswordRecoveryApiController` no existe.

- [ ] **Step 3: Implement validation, result mapping and safe exceptions**

```php
namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Services\Auth\PasswordResetService;
use Closure;
use JsonException;
use Throwable;

final class PasswordRecoveryApiController
{
    private const GENERIC_MESSAGE = 'Si el correo existe y está habilitado, enviaremos un enlace de restablecimiento en unos minutos.';
    private const UNAVAILABLE_MESSAGE = 'No pudimos enviar el correo en este momento por un problema técnico. Vuelve a intentarlo en unos minutos; si sigue fallando, avisa al administrador.';

    private PasswordResetService $service;
    private Closure $bodyReader;

    public function __construct(?PasswordResetService $service = null, ?callable $bodyReader = null)
    {
        $this->service = $service ?? new PasswordResetService();
        $this->bodyReader = $bodyReader === null
            ? static fn(): string => (string) file_get_contents('php://input')
            : Closure::fromCallable($bodyReader);
    }

    public function request(): void
    {
        $this->headers();
        $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!CsrfTokenManager::validate(is_string($csrf) ? $csrf : null, 'shell_api')) {
            $this->respond(403, [
                'success' => false,
                'code' => 'csrf_invalid',
                'message' => 'No fue posible validar la solicitud. Intenta nuevamente.',
            ]);
            return;
        }

        $payload = $this->decodeStrictPayload(($this->bodyReader)());
        $emailValue = is_array($payload) ? ($payload['email'] ?? null) : null;
        $email = is_string($emailValue) ? trim($emailValue) : '';
        if ($payload === null || array_keys($payload) !== ['email'] || !is_string($emailValue) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->respond(422, [
                'success' => false,
                'code' => 'validation_error',
                'message' => 'Revisa el correo electrónico.',
                'fieldErrors' => ['email' => ['Ingresa un correo electrónico válido.']],
            ]);
            return;
        }

        try {
            $outcome = $this->service->request($email, 'app');
        } catch (Throwable $error) {
            error_log('Password recovery unavailable: ' . $error::class);
            $this->respondUnavailable();
            return;
        }

        if (in_array($outcome, [PasswordResetService::RESULTADO_ENVIADO, PasswordResetService::RESULTADO_IGNORADO], true)) {
            $this->respond(200, ['success' => true, 'message' => self::GENERIC_MESSAGE]);
            return;
        }
        $this->respondUnavailable();
    }

    /** @return array<string, mixed>|null */
    private function decodeStrictPayload(string $raw): ?array
    {
        try {
            $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($payload) && !array_is_list($payload) ? $payload : null;
    }

    private function respondUnavailable(): void
    {
        $this->respond(503, [
            'success' => false,
            'code' => 'recovery_unavailable',
            'message' => self::UNAVAILABLE_MESSAGE,
        ]);
    }

    /** @param array<string, mixed> $payload */
    private function respond(int $status, array $payload): void
    {
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function headers(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    }
}
```

Mantener estos helpers privados en el mismo archivo: el controlador es la única unidad que necesita
traducir body, outcomes y headers S02.

- [ ] **Step 4: Run pure contract and PHP syntax check**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_api_password_recovery_contract.php
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php -l src/Controllers/Api/PasswordRecoveryApiController.php
```

Expected: todos los outcomes, igualdad de 200, orden CSRF/validación y scope fijo PASS; syntax RC 0.

- [ ] **Step 5: Commit controller and pure contract**

```bash
git add src/Controllers/Api/PasswordRecoveryApiController.php tests/test_api_password_recovery_contract.php
git commit -m "feat(auth): exponer recuperacion JSON segura"
```

---

### Task 3: Registrar el endpoint público y probar HTTP sin invocar correo

**Files:**
- Modify: `public/index.php`
- Create: `tests/test_api_password_recovery_http.php`
- Test: `tests/test_api_password_recovery_contract.php`

**Interfaces:**
- Route: `POST /api/auth/password/forgot` → `PasswordRecoveryApiController::request`.
- Middleware: ruta pública para obtener CSRF anónimo; mantenimiento no la exime.
- HTTP test only exercises 403, 422 and 405; none reaches `PasswordResetService`.

- [ ] **Step 1: Write failing safe HTTP routing cases**

Crear cookie jar propio, helper HTTP y contador de fallos:

```php
function requestJson(string $method, string $url, string $jar, ?array $body = null, array $headers = []): array
{
    $ch = curl_init($url);
    if ($ch === false) {
        throw new RuntimeException('No se pudo iniciar HTTP S02');
    }
    $responseHeaders = [];
    $httpHeaders = array_merge(['Accept: application/json'], $headers);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$responseHeaders): int {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
            return strlen($line);
        },
    ];
    if ($body !== null) {
        $httpHeaders[] = 'Content-Type: application/json';
        $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_THROW_ON_ERROR);
    }
    $options[CURLOPT_HTTPHEADER] = $httpHeaders;
    curl_setopt_array($ch, $options);
    $raw = curl_exec($ch);
    if ($raw === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException("La aplicación no respondió: {$error}");
    }
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    return [
        'code' => $code,
        'type' => $type,
        'headers' => $responseHeaders,
        'json' => json_decode((string) $raw, true),
    ];
}

function check(bool $condition, string $label): void
{
    global $failures;
    echo ($condition ? 'OK: ' : 'FAIL: ') . $label . "\n";
    if (!$condition) $failures++;
}

$base = rtrim(getenv('APP_URL') ?: 'http://127.0.0.1', '/');
$jar = tempnam(sys_get_temp_dir(), 's02_http_');
if ($jar === false) {
    fwrite(STDERR, "No se pudo crear cookie jar S02\n");
    exit(2);
}
$failures = 0;
try {
    $session = requestJson('GET', "{$base}/api/session", $jar);
    $csrf = $session['json']['csrfToken'] ?? '';

    $badCsrf = requestJson('POST', "{$base}/api/auth/password/forgot", $jar, [
        'email' => 'persona@example.test',
    ], ['X-CSRF-Token: invalid']);
    check($badCsrf['code'] === 403 && ($badCsrf['json']['code'] ?? '') === 'csrf_invalid', 'CSRF inválido');

    $badEmail = requestJson('POST', "{$base}/api/auth/password/forgot", $jar, [
        'email' => 'sin-formato',
    ], ['X-CSRF-Token: ' . $csrf]);
    check($badEmail['code'] === 422 && isset($badEmail['json']['fieldErrors']['email']), 'email inválido');
    check(str_contains(strtolower($badEmail['headers']['cache-control'] ?? ''), 'no-store'), 'respuesta no-store');

    $authorityField = requestJson('POST', "{$base}/api/auth/password/forgot", $jar, [
        'email' => 'persona@example.test', 'scope' => 'admin',
    ], ['X-CSRF-Token: ' . $csrf]);
    check($authorityField['code'] === 422, 'scope cliente rechazado');

$wrongMethod = requestJson('GET', "{$base}/api/auth/password/forgot", $jar);
check($wrongMethod['code'] === 405, 'GET conserva el 405 controlado del router');
} finally {
    unlink($jar);
}

exit($failures === 0 ? 0 : 1);
```

No usar un email válido sin campo extra junto a CSRF válido: ese camino sí llamaría el servicio y
queda prohibido en esta prueba.

- [ ] **Step 2: Run HTTP contract and confirm RED**

Tras comprobar que el contenedor compartido monta el worktree:

Run: `docker compose exec -T app php tests/test_api_password_recovery_http.php`

Expected: FAIL con 404 porque la ruta no está registrada.

- [ ] **Step 3: Register public route without maintenance exemption**

```php
$router->post('/api/auth/password/forgot', [
    \App\Controllers\Api\PasswordRecoveryApiController::class,
    'request',
]);
```

Insertar `'/api/auth/password/forgot'` junto a `'/api/auth/logout'` dentro del literal existente de
`$publicRoutes`. No añadir la ruta a `MaintenanceMode::isExemptRoute()`. Mantener todavía GET/POST
legacy `/password/forgot` sin cambios.

- [ ] **Step 4: Run pure/HTTP/auth/maintenance contracts**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_api_password_recovery_contract.php
docker compose exec -T app php tests/test_api_password_recovery_http.php
docker compose exec -T app php tests/test_api_auth_contract.php
docker compose exec -T app php tests/test_maintenance_asset_exemption.php
```

Expected: S02 pure/HTTP, S01 auth y mantenimiento PASS, sin tokens ni correos creados.

- [ ] **Step 5: Commit endpoint registration**

```bash
git add public/index.php tests/test_api_password_recovery_http.php
git commit -m "feat(auth): registrar solicitud de recuperacion"
```

---

### Task 4: Activar BrowserRouter y dar precedencia a la ruta pública

**Files:**
- Modify: `frontend/src/App.tsx`
- Modify: `frontend/src/shell/rutas.tsx`
- Modify: `frontend/src/shell/rutas.test.tsx`

**Interfaces:**
- `App()` owns one `<BrowserRouter>` with no basename.
- `Rutas()` obtains session once and maps `/password/forgot` plus `/app/password/forgot` to S02.
- `RutaRecuperacion` receives the same `useSesion()` result but ignores authenticated/pending branches after bootstrap.
- All other paths retain the S01 state machine.

- [ ] **Step 1: Write failing alias and session-precedence tests**

Usar `window.history.pushState()` antes de render y definir las tres sesiones completas que S01
acepta:

```tsx
const csrfToken = 'a'.repeat(64);
const recargar = vi.fn().mockResolvedValue(undefined);

function mockSesion(state: 'anonymous' | 'password_change_required' | 'authenticated') {
  const common = { capabilities: {}, navigation: { bi: null }, csrfToken };
  const sesion = EsquemaSesion.parse(state === 'anonymous'
    ? { ...common, state, authenticated: false, reason: 'missing_session', user: null, project: null }
    : state === 'password_change_required'
      ? { ...common, state, authenticated: false, reason: null, user: null, project: null }
      : {
          ...common, state, authenticated: true, reason: null,
          user: { username: 'test.A', displayName: 'Test A', role: 'A' }, project: null,
        });
  vi.mocked(useSesion).mockReturnValue({ sesion, cargando: false, error: null, recargar });
}

for (const pathname of ['/password/forgot', '/app/password/forgot']) {
  mockSesion('anonymous');
  window.history.pushState({}, '', pathname);
  render(<App />);
  expect(await screen.findByRole('heading', { name: 'Restablecer contraseña' })).toBeVisible();
  cleanup();
}

test.each(['anonymous', 'password_change_required', 'authenticated'])(
  'S02 prevalece sobre estado %s', async (state) => {
    mockSesion(state as 'anonymous' | 'password_change_required' | 'authenticated');
    window.history.pushState({}, '', '/password/forgot');
    render(<App />);
    expect(screen.getByRole('heading', { name: 'Restablecer contraseña' })).toBeVisible();
    expect(screen.queryByRole('heading', { name: 'Entrar' })).not.toBeInTheDocument();
  },
);
```

Mockear `PantallaRecuperarClave` dentro del test como un componente que renderiza un `h1` con ese
título; importar `EsquemaSesion` desde `../lib/api/esquemas/sesion`; el archivo productivo se crea en
Task 5. Mantener sin cambios las aserciones S01 para `/`, `/login` y `/app`.

- [ ] **Step 2: Run router tests and confirm RED**

Run: `npm --prefix frontend test -- src/shell/rutas.test.tsx`

Expected: FAIL porque `App` no monta BrowserRouter y S02 no está mapeada.

- [ ] **Step 3: Implement a single router around the existing state machine**

```tsx
export function App() {
  return (
    <BrowserRouter>
      <Rutas />
    </BrowserRouter>
  );
}

export function Rutas() {
  const estado = useSesion();
  return (
    <Routes>
      <Route path="/password/forgot" element={<RutaRecuperacion estado={estado} />} />
      <Route path="/app/password/forgot" element={<RutaRecuperacion estado={estado} />} />
      <Route path="*" element={<RutaEntrada estado={estado} />} />
    </Routes>
  );
}
```

Extraer el orden S01 a `RutaEntrada` sin cambiarlo. `RutaRecuperacion` comparte loading/error y,
cuando existe `sesion.csrfToken`, renderiza `PantallaRecuperarClave` aunque state sea anonymous,
pending o authenticated. Su `alRevalidar` es `estado.recargar`.

- [ ] **Step 4: Run router, S01 UI and typecheck**

```bash
npm --prefix frontend test -- src/shell/rutas.test.tsx src/shell/auth/PantallaLogin.test.tsx src/shell/auth/CambioClaveObligatorio.test.tsx
npm --prefix frontend run typecheck
```

Expected: aliases/prevalencia y regresión S01 PASS; TypeScript RC 0.

- [ ] **Step 5: Commit the routing slice**

```bash
git add frontend/src/App.tsx frontend/src/shell/rutas.tsx frontend/src/shell/rutas.test.tsx
git commit -m "feat(shell): enrutar recuperacion publica"
```

---

### Task 5: Construir formulario, validación y éxito genérico

**Files:**
- Create: `frontend/src/shell/auth/PantallaRecuperarClave.tsx`
- Create: `frontend/src/shell/auth/PantallaRecuperarClave.test.tsx`
- Modify: `frontend/src/shell/rutas.tsx`

**Interfaces:**
- Produces: `PantallaRecuperarClave({csrfToken, alRevalidar})`.
- Consumes: `MarcoAcceso`, `solicitarRecuperacion`, `esErrorApi`.
- On success: clears email, preserves form, shows server generic message.
- No client state contains service outcome or reset token.

- [ ] **Step 1: Write failing initial, validation, busy and success tests**

```tsx
const props = { csrfToken, alRevalidar: vi.fn().mockResolvedValue(undefined) };

test('presenta el formulario completo y accesible', () => {
  render(<PantallaRecuperarClave {...props} />);
  expect(screen.getByRole('heading', { name: 'Restablecer contraseña' })).toBeVisible();
  expect(screen.getByLabelText('Correo electrónico')).toHaveAttribute('autocomplete', 'email');
  expect(screen.getByRole('button', { name: 'Enviar enlace' })).toBeEnabled();
  expect(screen.getByRole('link', { name: 'Volver al inicio de sesión' })).toHaveAttribute('href', '/login');
});

test('un éxito limpia email pero mantiene formulario', async () => {
  vi.mocked(solicitarRecuperacion).mockResolvedValue({ success: true, message: MENSAJE_GENERICO });
  render(<PantallaRecuperarClave {...props} />);
  await user.type(screen.getByLabelText('Correo electrónico'), 'persona@empresa.com');
  await user.click(screen.getByRole('button', { name: 'Enviar enlace' }));
  expect(await screen.findByRole('status')).toHaveTextContent(MENSAJE_GENERICO);
  expect(screen.getByLabelText('Correo electrónico')).toHaveValue('');
  expect(screen.getByRole('button', { name: 'Enviar enlace' })).toBeEnabled();
});
```

Probar email vacío/formato inválido sin llamar gateway, Enter válido una vez y busy con email/botón
disabled más copy «Enviando…».

- [ ] **Step 2: Run component tests and confirm RED**

Run: `npm --prefix frontend test -- src/shell/auth/PantallaRecuperarClave.test.tsx`

Expected: FAIL porque el componente no existe.

- [ ] **Step 3: Implement minimal controlled form and success semantics**

```tsx
export function PantallaRecuperarClave({ csrfToken }: Props) {
  const [email, setEmail] = useState('');
  const [enviando, setEnviando] = useState(false);
  const [exito, setExito] = useState<string | null>(null);
  const [errorEmail, setErrorEmail] = useState<string | null>(null);
  const [errorGeneral, setErrorGeneral] = useState<string | null>(null);

  async function enviar(evento: FormEvent<HTMLFormElement>) {
    evento.preventDefault();
    if (enviando) return;
    const validacion = EsquemaSolicitudRecuperacion.safeParse({ email });
    if (!validacion.success) {
      setErrorEmail('Ingresa un correo electrónico válido.');
      return;
    }
    setEnviando(true);
    setExito(null);
    setErrorEmail(null);
    setErrorGeneral(null);
    try {
      const respuesta = await solicitarRecuperacion(validacion.data.email, csrfToken);
      setEmail('');
      setExito(respuesta.message);
    } catch {
      setErrorGeneral('No pudimos conectar. Intenta nuevamente.');
    } finally {
      setEnviando(false);
    }
  }

  return (
    <MarcoAcceso titulo="Restablecer contraseña">
      <p id="recuperacion-ayuda">
        Ingresa tu correo y te enviaremos un enlace seguro para crear una nueva contraseña.
      </p>
      <form className="aia-auth__actions" onSubmit={enviar} aria-busy={enviando} noValidate>
        <div className="aia-auth__field">
          <label htmlFor="recuperacion-email">Correo electrónico</label>
          <input
            id="recuperacion-email"
            type="email"
            value={email}
            onChange={(evento) => {
              setEmail(evento.target.value);
              setErrorEmail(null);
            }}
            placeholder="nombre@empresa.com"
            autoComplete="email"
            inputMode="email"
            autoCapitalize="none"
            spellCheck={false}
            required
            disabled={enviando}
            aria-invalid={errorEmail !== null}
            aria-describedby={errorEmail ? 'recuperacion-ayuda recuperacion-email-error' : 'recuperacion-ayuda'}
          />
          {errorEmail && <p id="recuperacion-email-error" className="aia-auth__field-error">{errorEmail}</p>}
        </div>
        {exito && <p className="aia-auth__feedback" role="status" aria-live="polite">{exito}</p>}
        {errorGeneral && <p className="aia-auth__feedback" role="alert">{errorGeneral}</p>}
        <button type="submit" disabled={enviando}>
          {enviando ? 'Enviando…' : 'Enviar enlace'}
        </button>
      </form>
      <Link className="aia-auth__link" to="/login">Volver al inicio de sesión</Link>
    </MarcoAcceso>
  );
}
```

Definir `Props` como `{csrfToken:string; alRevalidar:()=>Promise<void>}` e importar `Link`,
`MarcoAcceso`, Zod/gateway y tipos React. Task 5 conserva `alRevalidar` en la interfaz para que Task 6
lo conecte sin cambiar consumidores.

- [ ] **Step 4: Run component, router and typecheck**

```bash
npm --prefix frontend test -- src/shell/auth/PantallaRecuperarClave.test.tsx src/shell/rutas.test.tsx
npm --prefix frontend run typecheck
```

Expected: initial/validation/busy/success/route PASS y TypeScript RC 0.

- [ ] **Step 5: Commit the usable recovery form**

```bash
git add frontend/src/shell/auth/PantallaRecuperarClave.tsx frontend/src/shell/auth/PantallaRecuperarClave.test.tsx frontend/src/shell/rutas.tsx
git commit -m "feat(auth): construir solicitud de recuperacion"
```

---

### Task 6: Completar errores, actualización CSRF y foco accesible

**Files:**
- Modify: `frontend/src/shell/auth/PantallaRecuperarClave.tsx`
- Modify: `frontend/src/shell/auth/PantallaRecuperarClave.test.tsx`

**Interfaces:**
- 422 reads `ErrorApi.fieldErrors.email[0]` with contractual fallback.
- 403 renders `Actualizar sesión`; calls `alRevalidar()` once and never calls `solicitarRecuperacion` again.
- 503 uses server safe message; network/contract use fixed frontend messages.
- Error keeps email; only 200 clears it.

- [ ] **Step 1: Write failing error-matrix and focus tests**

```tsx
const MENSAJE_TECNICO = 'No pudimos enviar el correo en este momento por un problema técnico. Vuelve a intentarlo en unos minutos; si sigue fallando, avisa al administrador.';

async function enviarEmail(user: ReturnType<typeof userEvent.setup>, email: string) {
  await user.type(screen.getByLabelText('Correo electrónico'), email);
  await user.click(screen.getByRole('button', { name: 'Enviar enlace' }));
}

function errorApi(status: number, message: string, fieldErrors: Record<string, string[]> = {}) {
  return new ErrorApi({
    endpoint: '/api/auth/password/forgot', status,
    code: status === 422 ? 'validation_error' : status === 503 ? 'recovery_unavailable' : null,
    message, fieldErrors, redirect: null, correlationId: null,
    kind: status === 0 ? 'network' : 'http',
  });
}

test('403 actualiza sesión pero no reenvía', async () => {
  vi.mocked(solicitarRecuperacion).mockRejectedValue(new ErrorApi({
    endpoint: '/api/auth/password/forgot', status: 403, code: 'csrf_invalid',
    message: 'No fue posible validar la solicitud. Intenta nuevamente.',
    fieldErrors: {}, redirect: null, correlationId: null, kind: 'http',
  }));
  render(<PantallaRecuperarClave {...props} />);
  await enviarEmail(user, 'persona@empresa.com');
  const actualizar = await screen.findByRole('button', { name: 'Actualizar sesión' });
  expect(screen.getByLabelText('Correo electrónico')).toHaveValue('persona@empresa.com');
  await user.click(actualizar);
  expect(props.alRevalidar).toHaveBeenCalledOnce();
  expect(solicitarRecuperacion).toHaveBeenCalledOnce();
});

test.each([
  [422, 'Revisa el correo electrónico.', { email: ['Ingresa un correo electrónico válido.'] }],
  [503, MENSAJE_TECNICO, {}],
  [0, 'fallo de red', {}],
])('maneja status %s conservando email', async (status, message, fieldErrors) => {
  const user = userEvent.setup();
  vi.mocked(solicitarRecuperacion).mockRejectedValue(errorApi(status, message, fieldErrors));
  render(<PantallaRecuperarClave {...props} />);
  await enviarEmail(user, 'persona@empresa.com');
  expect(screen.getByLabelText('Correo electrónico')).toHaveValue('persona@empresa.com');
  if (status === 422) {
    expect(screen.getByText('Ingresa un correo electrónico válido.')).toBeVisible();
    expect(screen.getByLabelText('Correo electrónico')).toHaveFocus();
  } else {
    const expected = status === 503 ? MENSAJE_TECNICO : 'No pudimos conectar. Intenta nuevamente.';
    expect(await screen.findByRole('alert')).toHaveTextContent(expected);
  }
});
```

Probar foco email en 422, foco alert/acción en 403/503, edición limpia error de campo, doble click y
Enter durante busy mantienen una sola mutación.

- [ ] **Step 2: Run focused tests and confirm RED**

Run: `npm --prefix frontend test -- src/shell/auth/PantallaRecuperarClave.test.tsx`

Expected: FAIL porque el catch no clasifica `ErrorApi` ni controla foco/actualización.

- [ ] **Step 3: Implement exact error mapping without retry**

```tsx
catch (causa) {
  if (esErrorApi(causa) && causa.status === 422) {
    setErrorEmail(causa.fieldErrors.email?.[0] ?? 'Ingresa un correo electrónico válido.');
    requestAnimationFrame(() => emailRef.current?.focus());
  } else if (esErrorApi(causa) && causa.status === 403) {
    setRequiereRevalidar(true);
    setErrorGeneral(causa.message);
    requestAnimationFrame(() => revalidarRef.current?.focus());
  } else if (esErrorApi(causa) && causa.status === 503) {
    setErrorGeneral(causa.message);
    requestAnimationFrame(() => alertaRef.current?.focus());
  } else {
    setErrorGeneral('No pudimos conectar. Intenta nuevamente.');
    requestAnimationFrame(() => alertaRef.current?.focus());
  }
}

async function actualizarSesion() {
  if (revalidando) return;
  setRevalidando(true);
  try {
    await alRevalidar();
    setRequiereRevalidar(false);
    setErrorGeneral(null);
    requestAnimationFrame(() => emailRef.current?.focus());
  } catch {
    setErrorGeneral('No pudimos actualizar la sesión. Intenta nuevamente.');
    requestAnimationFrame(() => revalidarRef.current?.focus());
  } finally {
    setRevalidando(false);
  }
}
```

Cambiar la firma del componente a `({csrfToken, alRevalidar}: Props)`. Añadir estados booleanos
`requiereRevalidar`/`revalidando`, refs `emailRef`, `alertaRef`, `revalidarRef` y este bloque dentro
del feedback 403:

```tsx
{requiereRevalidar && (
  <button ref={revalidarRef} type="button" onClick={() => void actualizarSesion()} disabled={revalidando}>
    {revalidando ? 'Actualizando…' : 'Actualizar sesión'}
  </button>
)}
```

El alert tiene `tabIndex={-1}` solo para foco programático. `actualizarSesion()` deja email intacto,
no referencia el payload y no llama al gateway. Mantener `finally` y guard `enviando` frente a
eventos repetidos.

- [ ] **Step 4: Run full component/gateway/router tests**

```bash
npm --prefix frontend test -- src/shell/auth/PantallaRecuperarClave.test.tsx src/lib/api/esquemas/auth.test.ts src/shell/rutas.test.tsx
npm --prefix frontend run typecheck
```

Expected: 422/403/503/red/contrato, foco, single-submit y no-retry PASS.

- [ ] **Step 5: Commit resilient recovery states**

```bash
git add frontend/src/shell/auth/PantallaRecuperarClave.tsx frontend/src/shell/auth/PantallaRecuperarClave.test.tsx
git commit -m "feat(auth): completar errores de recuperacion"
```

---

### Task 7: Integrar presentación responsive y manifiesto de auth

**Files:**
- Modify: `public/css/auth-react.css`
- Modify: `tests/test_login_design_system_contract.mjs`
- Modify: `docs/design-system/manifests/auth.json`
- Modify: `frontend/src/shell/auth/PantallaRecuperarClave.test.tsx`

**Interfaces:**
- S02 uses only `aia-auth__field`, `__field-error`, `__feedback`, `__actions` within existing `@layer module`.
- Manifest declares route, component, 12 UX states, light/dark and four layouts.
- VIEW-02 remains listed until Task 10; VIEW-03 always remains.

- [ ] **Step 1: Strengthen static and semantic tests before CSS/manifest changes**

```js
assert.match(authCss, /^@layer module\s*\{/);
assert.doesNotMatch(authCss, /#[0-9a-f]{3,8}\b|rgba?\(|!important/i);
assert.ok(manifest.routes.includes('/password/forgot'));
assert.ok(manifest.sources.includes('frontend/src/shell/auth/PantallaRecuperarClave.tsx'));
assert.ok(manifest.sources.includes('views/auth/password-forgot.view.php'));
assert.ok(manifest.sources.includes('views/auth/password-reset.view.php'));
assert.deepEqual(manifest.layouts.sort(), ['desktop', 'mobile', 'tablet', 'wide']);
```

En el test React exigir target mínimo por clase contractual, alert/status separados, `aria-busy`,
`aria-invalid` y link accesible.

- [ ] **Step 2: Run design contract and confirm RED**

Run: `node tests/test_login_design_system_contract.mjs`

Expected: FAIL porque S02 React no está en manifest y faltan selectores/estados.

- [ ] **Step 3: Add token-only S02 styles and manifest entries**

```css
@layer module {
  .aia-auth__field { display: grid; gap: var(--ds-space-2); }
  .aia-auth__field :where(input, button) { min-block-size: var(--ds-target-min); }
  .aia-auth__field-error { color: var(--ds-color-state-critical-text); }
  .aia-auth__feedback { border: var(--ds-border-width) solid var(--ds-active-border); border-radius: var(--ds-radius-md); }
  .aia-auth__actions { display: grid; gap: var(--ds-space-3); }
  @media (max-width: 30rem) { .aia-auth__actions { inline-size: 100%; } }
}
```

`--ds-border-width`, `--ds-space-2`, `--ds-space-3`, `--ds-target-min`, `--ds-radius-md`,
`--ds-active-border` y `--ds-color-state-critical-text` existen en `tokens.css`; usar exactamente
esos nombres. Añadir al manifest estados
`recovery-initial`, `recovery-invalid`, `recovery-busy`, `recovery-accepted`, `recovery-csrf`,
`recovery-unavailable`, `recovery-network`; ambos temas y viewports. Mantener fuentes legacy S02/S03.

- [ ] **Step 4: Run static, frontend, build and diff checks**

```bash
node tests/test_login_design_system_contract.mjs
npm --prefix frontend test
npm --prefix frontend run typecheck
npm --prefix frontend run build
git diff --check
```

Expected: contrato estático, frontend completo, typecheck/build y diff PASS.

- [ ] **Step 5: Commit presentation, manifest and generated bundle**

```bash
git add public/css/auth-react.css tests/test_login_design_system_contract.mjs docs/design-system/manifests/auth.json frontend/src/shell/auth/PantallaRecuperarClave.test.tsx public/app
git commit -m "feat(auth): integrar recuperacion al design system"
```

---

### Task 8: Cortar GET/HEAD canónico con rollback puro

**Files:**
- Modify: `src/Core/SpaRouter.php`
- Modify: `public/index.php`
- Modify: `tests/test_spa_frontera.php`
- Modify: `tests/test_spa_frontera_http.php`
- Modify: `tests/test_api_password_recovery_http.php`

**Interfaces:**
- Adds exact migrated route `/password/forgot` for GET/HEAD only.
- Pilot prefix `/app` continues to serve `/app/password/forgot`.
- Legacy POST `/password/forgot` remains registered during rollback window.
- `SpaRouter::coincideConMapa()` proves removal without editing production constants.

- [ ] **Step 1: Write failing pure and HTTP cut matrices**

```php
$matrix = [
    ['GET', '/password/forgot', true],
    ['HEAD', '/password/forgot', true],
    ['POST', '/password/forgot', false],
    ['GET', '/password/reset', false],
    ['POST', '/api/auth/password/forgot', false],
    ['GET', '/app/password/forgot', true],
];
```

HTTP exige `#root` y ausencia de `data-auth-form` legacy en GET canónico, HEAD 200 sin body, POST
legacy todavía alcanzable con CSRF inválido sin llamar servicio, S03 aún HTML y rollback puro con
`exactas` sin `/password/forgot`.

- [ ] **Step 2: Run route tests and confirm RED**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_spa_frontera.php
docker compose exec -T app php tests/test_spa_frontera_http.php
```

Expected: FAIL porque la ruta canónica S02 aún no está en exactas.

- [ ] **Step 3: Add only the exact screen route**

```php
private const RUTAS_EXACTAS_MIGRADAS = [
    '/',
    '/login',
    '/password/forgot',
];
```

No añadir `/password` como prefijo. Pasar método real a `sirveLaSpa()` en guard/despacho como ya
exige S01. Mantener los registros GET/POST legacy para rollback; el host intercepta solo GET/HEAD.

- [ ] **Step 4: Run route, S02 API, S01 and S03 smoke gates**

```bash
npm --prefix frontend run build
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_spa_frontera.php
docker compose exec -T app php tests/test_spa_frontera_http.php
docker compose exec -T app php tests/test_api_password_recovery_http.php
docker compose exec -T app php tests/test_api_auth_contract.php
node tests/test_login_design_system_contract.mjs
```

Expected: exactas/métodos/rollback PASS, S02 API segura, S01 intacta y `/password/reset` legacy.

- [ ] **Step 5: Commit the canonical S02 cut**

```bash
git add src/Core/SpaRouter.php public/index.php tests/test_spa_frontera.php tests/test_spa_frontera_http.php tests/test_api_password_recovery_http.php public/app
git commit -m "feat(auth): cortar recuperacion a React"
```

---

### Task 9: Verificar navegador, accesibilidad y candidatos visuales

**Files:**
- Create: `tests/browser/password-recovery-react.spec.mjs`
- Create: `tests/browser/password-recovery-react.visual.mjs`
- Modify: `frontend/src/shell/auth/PantallaRecuperarClave.tsx` only if a demonstrated defect requires it
- Modify: `public/css/auth-react.css` only if a demonstrated defect requires it

**Interfaces:**
- Browser tests intercept `/api/session` and `/api/auth/password/forgot`; no SMTP/DB.
- Functional matrix covers 200/422/403/503/network/malformed, session states, navigation and no duplicates.
- Visual matrix: dark/light × 390/768/1180/1440; approval remains external gate.

- [ ] **Step 1: Write failing browser scenarios with controlled API**

```js
const CSRF = 'a'.repeat(64);
const MENSAJE_GENERICO = 'Si el correo existe y está habilitado, enviaremos un enlace de restablecimiento en unos minutos.';
const anonymousSession = {
  state: 'anonymous', authenticated: false, reason: 'missing_session',
  user: null, project: null, capabilities: {}, navigation: { bi: null }, csrfToken: CSRF,
};

await page.route('**/api/session', (route) => route.fulfill({
  status: 200,
  contentType: 'application/json',
  body: JSON.stringify(anonymousSession),
}));

let posts = 0;
await page.route('**/api/auth/password/forgot', async (route) => {
  posts += 1;
  expect(route.request().postDataJSON()).toEqual({ email: 'persona@example.test' });
  await route.fulfill({
    status: 200,
    contentType: 'application/json',
    body: JSON.stringify({ success: true, message: MENSAJE_GENERICO }),
  });
});

await page.goto('/password/forgot');
await page.getByLabel('Correo electrónico').fill('persona@example.test');
await page.getByRole('button', { name: 'Enviar enlace' }).dblclick();
await expect(page.getByRole('status')).toContainText(MENSAJE_GENERICO);
expect(posts).toBe(1);
```

Implementar además esta matriz exacta, reiniciando rutas, contadores y storage en `beforeEach`:

| Caso | Intercepción controlada | Aserción obligatoria |
|---|---|---|
| Alias | abrir `/app/password/forgot`, recargar y volver/avanzar | El mismo `h1`; URL y formulario estables, sin flash de login. |
| Sesión | `anonymousSession`; variantes `authenticated` con usuario y `password_change_required` sin usuario | Los tres estados muestran S02 y nunca identidad/proyecto. |
| 422 | `422 {success:false,code:'validation_error',message:'Revisa el correo electrónico.',fieldErrors:{email:['Ingresa un correo electrónico válido.']}}` | Email preservado, mensaje asociado y foco en input. |
| 403 | `403 {success:false,code:'csrf_invalid',message:'No fue posible validar la solicitud. Intenta nuevamente.'}` | Acción `Actualizar sesión`; al pulsarla hay una nueva GET `/api/session` y el contador POST sigue en 1. |
| 503 | `503 {success:false,code:'recovery_unavailable',message:MENSAJE_TECNICO}` | Alert con copy técnico seguro, foco en alert y email preservado. |
| Red | `route.abort('failed')` | Alert «No pudimos conectar. Intenta nuevamente.» y email preservado. |
| Contrato roto | `200 {success:true}` sin `message` | Mismo alert genérico de transporte/contrato; sin body técnico y email preservado. |
| Navegación | click `Volver al inicio de sesión` | URL `/login` y heading S01, sin recarga documental. |
| Accesibilidad | Tab/Shift+Tab/Enter, `axe`, zoom CSS 200 % | Orden lógico, foco visible, cero violación grave/crítica y ningún control oculto. |
| Responsive/tema | dark/light × `390×844`, `768×1024`, `1180×820`, `1440×900` | Sin `scrollWidth > innerWidth`; consola sin error y solo requests esperadas. |

Definir `MENSAJE_TECNICO` con el literal contractual de Task 6. Para cada caso fallido afirmar
`posts === 1`; el único request adicional permitido es la GET de sesión iniciada por la acción 403.

- [ ] **Step 2: Run functional browser spec and confirm failures**

Run: `npx playwright test tests/browser/password-recovery-react.spec.mjs --workers=1`

Expected: cualquier defecto real queda rojo con escenario nominal; no cambiar baselines.

- [ ] **Step 3: Fix only demonstrated S02 defects and add visual candidate script**

```js
import { mkdir } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const candidateDir = process.env.S02_VISUAL_DIR ?? join(tmpdir(), 'lps-aia-s02-visual');
const candidatePath = (name) => join(candidateDir, name);
const VIEWPORTS = [
  { width: 390, height: 844 },
  { width: 768, height: 1024 },
  { width: 1180, height: 820 },
  { width: 1440, height: 900 },
];
await mkdir(candidateDir, { recursive: true });

for (const theme of ['dark', 'light']) {
  for (const viewport of VIEWPORTS) {
    await page.setViewportSize(viewport);
    await page.addInitScript((value) => localStorage.setItem('aia-theme', value), theme);
    await page.goto('/password/forgot');
    await page.screenshot({
      path: candidatePath(`password-forgot-${theme}-${viewport.width}x${viewport.height}.png`),
      fullPage: true,
    });
  }
}
```

`S02_VISUAL_DIR` permite elegir un directorio local; sin override usa el temporal del sistema, nunca
`__screenshots__` ni evidence versionada. Corregir solo overflow/foco/copy demostrado, usando tokens
existentes.

- [ ] **Step 4: Run browser, Axe, frontend and static gates**

```bash
npx playwright test tests/browser/password-recovery-react.spec.mjs --workers=1
npx playwright test tests/browser/password-recovery-react.visual.mjs --workers=1
npm --prefix frontend test
npm --prefix frontend run typecheck
node tests/test_login_design_system_contract.mjs
git diff --check
```

Expected: funcional/Axe/frontend/static PASS; ocho candidatos generados fuera de git. Felipe revisa
los candidatos antes de autorizar cualquier baseline.

- [ ] **Step 5: Commit browser evidence code, not candidate images**

```bash
git add tests/browser/password-recovery-react.spec.mjs tests/browser/password-recovery-react.visual.mjs frontend/src/shell/auth/PantallaRecuperarClave.tsx public/css/auth-react.css
git commit -m "test(auth): verificar recuperacion React"
```

---

### Task 10: Retirar VIEW-02 después del gate y cerrar S02

**Files:**
- Modify: `public/index.php`
- Modify: `src/Controllers/Auth/PasswordResetController.php`
- Delete: `views/auth/password-forgot.view.php`
- Modify: `tests/test_login_design_system_contract.mjs`
- Modify: `docs/design-system/manifests/auth.json`
- Modify: `tests/test_spa_frontera_http.php`
- Preserve: `views/auth/password-reset.view.php`, `public/css/login-brand-unified.css`, `public/js/modules/aia_ui/auth_forms.js`

**Interfaces:**
- Canonical GET/HEAD remains SPA through `SpaRouter`/`SpaHostRenderer`.
- Legacy POST `/password/forgot` becomes the controlled product 404 after both legacy registrations
  are removed; GET/HEAD remain SPA through the method-aware host.
- `PasswordResetController` keeps `reset()`, `update()` and `renderReset()` for S03.
- `MIGRATION_COMPLETE` requires explicit post-rollout/visual gates; local green alone is `CODE_COMPLETE`.

- [ ] **Step 1: Change contracts to expect React-only S02 and legacy S03**

Actualizar pruebas/manifiesto para exigir:

```js
assert.ok(manifest.sources.includes('frontend/src/shell/auth/PantallaRecuperarClave.tsx'));
assert.ok(!manifest.sources.includes('views/auth/password-forgot.view.php'));
assert.ok(manifest.sources.includes('views/auth/password-reset.view.php'));
assert.match(read('views/auth/password-reset.view.php'), /data-auth-form/);
```

HTTP: GET `/password/forgot` React, POST `/password/forgot` 404, GET/POST `/password/reset` siguen
registrados, API S02 403/422 segura y mantenimiento sin bypass 503.

- [ ] **Step 2: Run closeout tests and confirm RED before retirement**

```bash
node tests/test_login_design_system_contract.mjs
docker compose exec -T app php tests/test_spa_frontera_http.php
```

Expected: FAIL porque VIEW-02/POST/métodos legacy todavía existen y manifest aún los declara.

- [ ] **Step 3: Remove only S02 legacy consumers**

Eliminar exactamente `forgot()`, `sendLink()` y `renderForgot()` de `PasswordResetController`.
Desregistrar GET/POST legacy `/password/forgot` del bloque Auth; GET/HEAD sigue interceptado por
`SpaRouter` antes de dispatch. Borrar VIEW-02. No tocar `reset`, `update`, `renderReset`, servicio,
CSS/JS compartidos ni rutas S03. Actualizar manifest/contrato para la fuente React única de S02 y
comprobar los símbolos restantes:

Mantener `/password/forgot` en el allowlist público basado en path para que un POST anónimo retirado
llegue al 404 controlado del producto, no a un redirect de autenticación.

```bash
rg -n 'function (forgot|sendLink|renderForgot|reset|update|renderReset)' src/Controllers/Auth/PasswordResetController.php
rg -n "password/(forgot|reset)" public/index.php
```

Expected: primer comando lista solo `reset`, `update`, `renderReset`; segundo lista API S02 y
GET/POST S03, pero ningún POST legacy S02.

- [ ] **Step 4: Run the full S02 completion gate on one worktree tree/mount**

```bash
npm --prefix frontend test
npm --prefix frontend run typecheck
npm --prefix frontend run build
node tests/test_login_design_system_contract.mjs
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_api_password_recovery_contract.php
docker compose exec -T app php tests/test_api_password_recovery_http.php
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_spa_frontera.php
docker compose exec -T app php tests/test_spa_frontera_http.php
docker compose exec -T app php tests/test_api_auth_contract.php
docker compose exec -T app php tests/test_maintenance_asset_exemption.php
npx playwright test tests/browser/password-recovery-react.spec.mjs --workers=1
git diff --check
```

Expected: todos RC 0 sobre el mismo contenido del worktree y el mount verificado; ninguna prueba
ejecuta DML/SMTP. Verificar por `git diff --name-only` que no aparecen `admin/`, database,
migrations, RLS, servicio de reset, mailer ni VIEW-03.

- [ ] **Step 5: Commit retirement after explicit gates**

```bash
git add public/index.php src/Controllers/Auth/PasswordResetController.php tests/test_login_design_system_contract.mjs docs/design-system/manifests/auth.json tests/test_spa_frontera_http.php public/app
git add -u views/auth/password-forgot.view.php
git commit -m "feat(auth): cerrar migracion de recuperacion"
git rev-parse HEAD
```

Registrar ese SHA como commit S02. El gate de cierre de rama debe reejecutarse sobre ese commit antes
de PR/publicación; el verde previo no autoriza commit, push, merge ni deploy por sí solo.

## Observable Parity Traceability

| Capacidad | Evidencia del plan |
|---|---|
| S02-UX-01 | Tasks 5 y 7: MarcoAcceso, marca, título y copy. |
| S02-UX-02 | Task 5: label, placeholder y autocomplete. |
| S02-UX-03 | Tasks 2, 5 y 6: validación cliente/PHP y errores. |
| S02-UX-04 | Tasks 5, 6 y 9: busy y single-submit. |
| S02-UX-05 | Tasks 1–2: un schema/body para enviado e ignorado. |
| S02-UX-06 | Tasks 2, 3 y 6: CSRF, email preservado y actualización manual. |
| S02-UX-07 | Tasks 2, 5 y 6: 422 asociado al campo. |
| S02-UX-08 | Tasks 2 y 6: 503 honesto, email preservado. |
| S02-UX-09 | Tasks 5 y 9: éxito limpia campo y conserva formulario. |
| S02-UX-10 | Tasks 4–5 y 9: Link `/login` y navegación. |
| S02-UX-11 | Tasks 5 y 7: footer en marco compartido. |
| S02-UX-12 | Tasks 4 y 9: prevalencia anónimo/autenticado/pendiente. |

## Acceptance Traceability

| Criterio | Evidencia del plan |
|---|---|
| S02-AC-01 | Tasks 4, 8 y 9: piloto/canónica/deep link/refresh. |
| S02-AC-02 | Tasks 5–10: UX completa y retiro gateado. |
| S02-AC-03 | Tasks 1–3: body estricto y scope `app`. |
| S02-AC-04 | Tasks 2–3 y 6: CSRF shell_api 403. |
| S02-AC-05 | Tasks 2, 5–6: 422, valor y foco. |
| S02-AC-06 | Tasks 1–2: igualdad exacta de enviado/ignorado. |
| S02-AC-07 | Tasks 2 y 6: fallido/excepción 503. |
| S02-AC-08 | Tasks 5 y 9: limpiar campo, conservar formulario. |
| S02-AC-09 | Tasks 5–6 y 9: no retry/doble mutación. |
| S02-AC-10 | Tasks 4 y 9: tres estados de sesión. |
| S02-AC-11 | Tasks 7 y 9: dark/light × cuatro viewports. |
| S02-AC-12 | Tasks 5–7 y 9: teclado, foco, anuncios, zoom, Axe. |
| S02-AC-13 | Tasks 1, 5 y 7: cliente único, Zod y guard. |
| S02-AC-14 | Tasks 2–3: PHP contract fake y HTTP pre-service sin DML. |
| S02-AC-15 | Global constraints y Tasks 3, 8–10: mantenimiento/S03/admin/RLS intactos. |
| S02-AC-16 | Tasks 8 y 10: rollback antes del retiro. |

## Completion Gate

S02 queda `CODE_COMPLETE` cuando Tasks 1–9 pasan sobre un contenido único del worktree y los
candidatos visuales están aprobados sin regeneración silenciosa. Queda `MIGRATION_COMPLETE`
únicamente después del gate post-rollout de Task 10, su commit y una reverificación del SHA que
autoriza retirar VIEW-02 y el POST legacy. Ninguno de esos estados autoriza deploy, DDL/DML, cambios
RLS, `/admin/`, correo real o modificación de S03.
