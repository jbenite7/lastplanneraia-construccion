# S03 Password Reset React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** migrar `/password/reset` a React con validación privada del bearer token, cinco reglas de contraseña, cambio de un solo uso y redirect seguro a S01, sin cambiar la política, el servicio, RLS ni datos durante las pruebas de migración.

**Architecture:** S03 añade dos adaptadores JSON públicos: uno valida el token por POST sin volver a ponerlo en una URL de API y otro delega el cambio a `PasswordResetService::reset(..., 'app', ...)`. React conserva el token solo en estado local/query original, usa `CampoClave` para los dos secretos y nunca reintenta la mutación. El corte method-aware promueve únicamente GET/HEAD canónico; VIEW-03 y POST legacy quedan disponibles hasta el gate post-rollout.

**Tech Stack:** PHP 8.3, React 19, TypeScript 7, react-router-dom 7, Vite 8, Zod 4, Vitest 4, Testing Library, Playwright 1.61, CSS design system AIA.

**Spec:** `docs/superpowers/specs/2026-08-30-s03-restablecer-clave-react-design.md`

## Global Constraints

- Trabajar exclusivamente en `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react`, rama `shell-minimo-react`; no usar el repositorio padre de `/Volumes/Crucial X6`.
- Ejecutar después de completar S01 y S02. Se consumen `ErrorApi`, `MarcoAcceso`, `CampoClave`, `BrowserRouter`, `SpaHostRenderer`, el mapa method-aware de `SpaRouter`, tema y `/api/session` ya entregados.
- Revisar status/diff antes de editar; preservar cambios ajenos y evitar refactors adyacentes.
- `/admin/` queda fuera. No modificar rutas, controladores, servicios, vistas, mensajes, assets o tokens administrativos.
- No modificar RLS, schema, migraciones, grants, usuarios, credenciales, TTL, hashing, correo, tablas o política de contraseña.
- Las pruebas S03 no ejecutan DML: no crean tokens, no cambian passwords, no invalidan filas y no corren `tests/test_password_reset_resultados.php`.
- Contratos PHP usan `PasswordResetService` fake sin constructor padre; navegador intercepta ambas APIs. HTTP real usa solo token sintácticamente inválido, CSRF/body inválido o método incorrecto.
- No llamar `fetch` desde componentes; todo pasa por `frontend/src/lib/api/cliente.ts` mediante `frontend/src/lib/api/auth.ts`.
- Cada endpoint nuevo lleva request/response Zod y prueba contractual PHP.
- React usa CSRF `shell_api` en `X-CSRF-Token`; POST legacy conserva `password_reset` hasta su retiro. Ningún token CSRF cruza contratos.
- PHP acepta solo `{token}` al validar y `{token,password,confirmPassword}` al cambiar; `scope='app'` se fija server-side.
- El token válido tiene 64 hex minúsculos. Nunca se pinta, registra, guarda globalmente, inserta en un endpoint URL, screenshot o nombre de artefacto.
- No hay retry automático de la mutación. Tras cualquier respuesta de update se limpian secretos; 403 solo recarga sesión tras acción explícita y no reenvía.
- Oscuro es fallback inicial; claro y oscuro conservan capacidad en `390×844`, `768×1024`, `1180×820` y `1440×900`.
- Solo tokens de `public/css/tokens.css`; sin colores literales, inline styles, `!important` ni una segunda capa visual.
- Mantener 503 de mantenimiento sin eximir rutas S03.
- Mantener `/login?reset=1` y `/password/forgot` de S01/S02 sin cambiar copy o contrato.
- No versionar candidatos visuales ni regenerar baselines sin aprobación explícita.
- No implementar, commitear, publicar ni desplegar durante la fase documental actual. Los commits descritos son pasos para una ejecución futura autorizada.

## File Structure

### Create

- `frontend/src/shell/auth/tokenReset.ts` — extrae exactamente un token candidato del query.
- `frontend/src/shell/auth/tokenReset.test.ts` — ausente, repetido, mal formado y válido.
- `frontend/src/shell/auth/PantallaRestablecerClave.tsx` — validación de enlace, formulario, secretos, errores y redirect.
- `frontend/src/shell/auth/PantallaRestablecerClave.test.tsx` — S03-UX-01…16 y matriz de seguridad.
- `src/Controllers/Api/PasswordResetApiController.php` — adapters `validateLink()` y `update()`.
- `tests/test_api_password_reset_contract.php` — outcomes puros con fake, cero DB.
- `tests/test_api_password_reset_http.php` — routing/headers pre-mutación.
- `tests/browser/password-reset-react.spec.mjs` — flujo funcional con APIs interceptadas.
- `tests/browser/password-reset-react.visual.mjs` — ocho candidatos temporales sin secretos.

### Modify

- `frontend/src/lib/api/esquemas/auth.ts` y `.test.ts` — requests/responses estrictos S03.
- `frontend/src/lib/api/auth.ts` — `validarEnlaceReset` y `restablecerClave`.
- `frontend/src/shell/auth/CampoClave.tsx` — `forwardRef` y ayuda asociada sin romper S01.
- `frontend/src/App.tsx`, `frontend/src/shell/rutas.tsx` y `rutas.test.tsx` — rutas públicas S03.
- `public/css/auth-react.css` — composición S03 dentro de `@layer module`.
- `public/index.php` — endpoints, headers del host, corte y retiro legacy gateado.
- `src/Core/SpaRouter.php`, `tests/test_spa_frontera.php`, `tests/test_spa_frontera_http.php` — GET/HEAD canónico y rollback.
- `tests/test_login_design_system_contract.mjs`, `docs/design-system/manifests/auth.json`, `docs/design-system/exceptions.json`, `docs/design-system/unlayered-delivery-inventory.json`, `docs/design-system/ui-groups-inventory.json` — transición VIEW-03 → React.
- `tests/browser/design-system-consumer-smoke.mjs` — auth React sin expectativa legacy obsoleta.
- `public/app/index.html` y `public/app/assets/index-*.js` — build Vite generado, nunca editado a mano.

### Preserve

- `src/Services/Auth/PasswordResetService.php`, `UserPasswordService.php` y `PasswordPolicyService.php`.
- `database/patches/20260329_create_password_reset_tokens.sql` y toda persistencia.
- S01/S02 React, `/api/session`, `/api/auth/password/forgot`, `/login?reset=1` y `/password/forgot`.
- Todo `admin/`, incluso archivos con nombres similares.

### Retire only after post-rollout gate

- `views/auth/password-reset.view.php` (VIEW-03).
- `src/Controllers/Auth/PasswordResetController.php`, que queda sin consumidores después de S01/S02.
- Legacy GET/POST route registrations for `/password/reset`.
- `public/css/login-brand-unified.css` y `public/js/modules/aia_ui/auth_forms.js` después de demostrar cero consumidores de aplicación principal.

---

### Task 1: Definir contratos Zod y gateway S03

**Files:**
- Modify: `frontend/src/lib/api/esquemas/auth.ts`
- Modify: `frontend/src/lib/api/esquemas/auth.test.ts`
- Modify: `frontend/src/lib/api/auth.ts`
- Test: `frontend/src/lib/api/esquemas/auth.test.ts`

**Interfaces:**
- Produces: `TOKEN_RESET_PATTERN`, requests `SolicitudValidarReset`/`SolicitudRestablecerClave`.
- Produces: response union `EstadoEnlaceReset` and `RestablecimientoAceptado`.
- Produces: `validarEnlaceReset(token, csrfToken, signal?)` and `restablecerClave(input, csrfToken)`.
- Consumes: `pedir<T>()` and S01 `ErrorApi`; components never import `fetch`.

- [ ] **Step 1: Write failing strict-schema and gateway tests**

```ts
const TOKEN = 'a'.repeat(64);
const CSRF = 'b'.repeat(64);
const INVALID_MESSAGE = 'El enlace no es válido o ya expiró. Solicita uno nuevo.';

test('el enlace S03 solo acepta un token hex exacto', () => {
  expect(EsquemaSolicitudValidarReset.parse({ token: TOKEN })).toEqual({ token: TOKEN });
  expect(EsquemaSolicitudValidarReset.safeParse({ token: TOKEN.toUpperCase() }).success).toBe(false);
  expect(EsquemaSolicitudValidarReset.safeParse({ token: TOKEN, scope: 'admin' }).success).toBe(false);
});

function firstResetIssue(password: string, confirmPassword: string) {
  const result = EsquemaSolicitudRestablecerClave.safeParse({ token: TOKEN, password, confirmPassword });
  expect(result.success).toBe(false);
  if (result.success) throw new Error('Se esperaba una validación S03 fallida');
  return result.error.issues[0];
}

test('la política cliente conserva las cuatro comprobaciones observables', () => {
  expect(firstResetIssue('abc', 'abc').message)
    .toBe('La contraseña debe tener al menos 6 caracteres');
  expect(firstResetIssue('abcdef!', 'abcdef!').message)
    .toBe('Debe contener al menos una letra mayúscula');
  expect(firstResetIssue('Abcdef', 'Abcdef').message)
    .toBe('Debe contener al menos un carácter especial (!@#$%...)');
  expect(firstResetIssue('Abcdef!', 'Otra1!').path).toEqual(['confirmPassword']);
});

test('validar y cambiar usan endpoints constantes y CSRF shell_api', async () => {
  vi.mocked(pedir)
    .mockResolvedValueOnce({ success: true, state: 'valid' })
    .mockResolvedValueOnce({
      success: true, message: 'Contraseña restablecida correctamente.', redirect: '/login?reset=1',
    });
  const controller = new AbortController();
  await validarEnlaceReset(TOKEN, CSRF, controller.signal);
  await restablecerClave({ token: TOKEN, password: 'Abcdef!', confirmPassword: 'Abcdef!' }, CSRF);
  expect(pedir).toHaveBeenNthCalledWith(1, '/api/auth/password/reset/validate', EsquemaEstadoEnlaceReset, {
    method: 'POST', headers: { 'X-CSRF-Token': CSRF }, body: JSON.stringify({ token: TOKEN }),
    signal: controller.signal,
  });
  expect(pedir).toHaveBeenNthCalledWith(2, '/api/auth/password/reset', EsquemaRestablecimientoAceptado, {
    method: 'POST', headers: { 'X-CSRF-Token': CSRF },
    body: JSON.stringify({ token: TOKEN, password: 'Abcdef!', confirmPassword: 'Abcdef!' }),
  });
});

test('invalid nunca acepta identidad o razón interna', () => {
  expect(EsquemaEstadoEnlaceReset.safeParse({
    success: true, state: 'invalid', message: INVALID_MESSAGE, username: 'test.A',
  }).success).toBe(false);
});
```

- [ ] **Step 2: Run focused tests and confirm RED**

Run: `npm --prefix frontend test -- src/lib/api/esquemas/auth.test.ts`

Expected: FAIL because S03 schemas and gateways do not exist.

- [ ] **Step 3: Implement schemas with exact first-error order**

```ts
export const TOKEN_RESET_PATTERN = /^[a-f0-9]{64}$/;
export const MENSAJE_ENLACE_RESET_INVALIDO = 'El enlace no es válido o ya expiró. Solicita uno nuevo.';

export const EsquemaSolicitudValidarReset = z.object({
  token: z.string().regex(TOKEN_RESET_PATTERN),
}).strict();
export type SolicitudValidarReset = z.infer<typeof EsquemaSolicitudValidarReset>;

export const EsquemaEstadoEnlaceReset = z.discriminatedUnion('state', [
  z.object({ success: z.literal(true), state: z.literal('valid') }).strict(),
  z.object({
    success: z.literal(true), state: z.literal('invalid'),
    message: z.literal(MENSAJE_ENLACE_RESET_INVALIDO),
  }).strict(),
]);
export type EstadoEnlaceReset = z.infer<typeof EsquemaEstadoEnlaceReset>;

export const EsquemaSolicitudRestablecerClave = z.object({
  token: z.string().regex(TOKEN_RESET_PATTERN),
  password: z.string(),
  confirmPassword: z.string(),
}).strict().superRefine(({ password, confirmPassword }, context) => {
  if (new TextEncoder().encode(password).length < 6) {
    context.addIssue({ code: 'custom', path: ['password'], message: 'La contraseña debe tener al menos 6 caracteres' });
  } else if (!/[A-Z]/.test(password)) {
    context.addIssue({ code: 'custom', path: ['password'], message: 'Debe contener al menos una letra mayúscula' });
  } else if (!/[^a-zA-Z0-9]/.test(password)) {
    context.addIssue({ code: 'custom', path: ['password'], message: 'Debe contener al menos un carácter especial (!@#$%...)' });
  } else if (password !== confirmPassword) {
    context.addIssue({ code: 'custom', path: ['confirmPassword'], message: 'Las contraseñas no coinciden' });
  }
});
export type SolicitudRestablecerClave = z.infer<typeof EsquemaSolicitudRestablecerClave>;

export const EsquemaRestablecimientoAceptado = z.object({
  success: z.literal(true),
  message: z.literal('Contraseña restablecida correctamente.'),
  redirect: z.literal('/login?reset=1'),
}).strict();
export type RestablecimientoAceptado = z.infer<typeof EsquemaRestablecimientoAceptado>;
```

Implement gateways exactly with parsed inputs and constant endpoints; pass `signal` only to link validation:

```ts
export async function validarEnlaceReset(
  token: string,
  csrfToken: string,
  signal?: AbortSignal,
): Promise<EstadoEnlaceReset> {
  const request = EsquemaSolicitudValidarReset.parse({ token });
  return pedir('/api/auth/password/reset/validate', EsquemaEstadoEnlaceReset, {
    method: 'POST',
    headers: { 'X-CSRF-Token': csrfToken },
    body: JSON.stringify(request),
    signal,
  });
}

export async function restablecerClave(
  input: SolicitudRestablecerClave,
  csrfToken: string,
): Promise<RestablecimientoAceptado> {
  const request = EsquemaSolicitudRestablecerClave.parse(input);
  return pedir('/api/auth/password/reset', EsquemaRestablecimientoAceptado, {
    method: 'POST',
    headers: { 'X-CSRF-Token': csrfToken },
    body: JSON.stringify(request),
  });
}
```

- [ ] **Step 4: Run schema, fetch-boundary and type gates**

```bash
npm --prefix frontend test -- src/lib/api/esquemas/auth.test.ts src/lib/api/frontera.test.ts
npm --prefix frontend run typecheck
```

Expected: request/response/gateway tests PASS, no fetch outside client, typecheck RC 0.

- [ ] **Step 5: Commit typed S03 contracts**

```bash
git add frontend/src/lib/api/esquemas/auth.ts frontend/src/lib/api/esquemas/auth.test.ts frontend/src/lib/api/auth.ts
git commit -m "feat(auth): definir contratos de restablecimiento"
```

---

### Task 2: Crear controlador JSON y contrato puro sin DB

**Files:**
- Create: `src/Controllers/Api/PasswordResetApiController.php`
- Create: `tests/test_api_password_reset_contract.php`

**Interfaces:**
- `__construct(?PasswordResetService $service = null, ?callable $bodyReader = null)`.
- `validateLink(): void` maps valid/invalid/503 without identity.
- `update(): void` maps success, five policy failures, invalid link and unavailable.
- All responses are JSON UTF-8, `no-store`, `nosniff`; CSRF is `shell_api`.

- [ ] **Step 1: Write failing fake-service contract matrix**

```php
use App\Controllers\Api\PasswordResetApiController;
use App\Security\CsrfTokenManager;
use App\Services\Auth\PasswordResetService;

final class PasswordResetServiceFake extends PasswordResetService
{
    public array $findCalls = [];
    public array $resetCalls = [];

    public function __construct(
        private array|Throwable|null $tokenResult,
        private array|Throwable $resetResult = ['success' => false, 'message' => 'unused'],
    ) {}

    public function findValidToken(string $plainToken, string $scope): ?array
    {
        $this->findCalls[] = [$plainToken, $scope];
        if ($this->tokenResult instanceof Throwable) throw $this->tokenResult;
        return $this->tokenResult;
    }

    public function reset(string $plainToken, string $scope, string $password, string $confirm): array
    {
        $this->resetCalls[] = [$plainToken, $scope, $password, $confirm];
        if ($this->resetResult instanceof Throwable) throw $this->resetResult;
        return $this->resetResult;
    }
}

function executeController(string $method, string $body, ?string $csrf, PasswordResetServiceFake $service): array
{
    $_SERVER['HTTP_X_CSRF_TOKEN'] = $csrf ?? '';
    http_response_code(200);
    ob_start();
    $controller = new PasswordResetApiController($service, static fn(): string => $body);
    $controller->{$method}();
    return [http_response_code(), json_decode((string) ob_get_clean(), true)];
}
```

Generate one `shell_api` token and assert this exact matrix:

| Method/input fake | Expected | Call assertion |
|---|---|---|
| validate + token row | 200 `state=valid` | one `findValidToken(TOKEN,'app')`. |
| validate + null | 200 exact invalid body | one find, no identity keys. |
| validate + malformed token | 200 invalid | zero service calls. |
| validate + exception | 503 `reset_unavailable` | no exception detail. |
| update + success | 200 exact success/redirect | one `reset(TOKEN,'app','Abcdef!','Abcdef!')`. |
| update + each of five policy messages | 422 and correct field | one reset per case. |
| update + invalid-link/user-missing | 410 identical public body | one reset. |
| update + storage error/unknown/exception | 503 safe body | no internal detail. |
| either method + invalid CSRF/body extra/list/JSON | 403 or 422 | zero service calls. |

Exit 1 on any failed assertion. The fake never invokes parent constructor, DB or mail.

- [ ] **Step 2: Run pure contract and confirm RED**

Run: `LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_api_password_reset_contract.php`

Expected: FAIL because `PasswordResetApiController` does not exist.

- [ ] **Step 3: Implement strict adapter and result mapping**

```php
namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Services\Auth\PasswordResetService;
use Closure;
use JsonException;
use Throwable;

final class PasswordResetApiController
{
    private const TOKEN_PATTERN = '/^[a-f0-9]{64}$/D';
    private const INVALID_MESSAGE = 'El enlace no es válido o ya expiró. Solicita uno nuevo.';
    private const VALIDATE_UNAVAILABLE = 'No pudimos validar el enlace en este momento. Intenta nuevamente.';
    private const UPDATE_UNAVAILABLE = 'Error al actualizar la contraseña.';
    private const POLICY_FIELDS = [
        'La contraseña debe tener al menos 6 caracteres' => 'password',
        'Debe contener al menos una letra mayúscula' => 'password',
        'Debe contener al menos un carácter especial (!@#$%...)' => 'password',
        'Las contraseñas no coinciden' => 'confirmPassword',
        'La nueva contraseña no puede ser igual a la anterior' => 'password',
    ];

    private PasswordResetService $service;
    private Closure $bodyReader;

    public function __construct(?PasswordResetService $service = null, ?callable $bodyReader = null)
    {
        $this->service = $service ?? new PasswordResetService();
        $this->bodyReader = $bodyReader === null
            ? static fn(): string => (string) file_get_contents('php://input')
            : Closure::fromCallable($bodyReader);
    }

    public function validateLink(): void
    {
        $this->headers();
        if (!$this->validCsrf()) {
            $this->csrfError();
            return;
        }
        $payload = $this->decodeObject(($this->bodyReader)());
        if ($payload === null || !$this->hasExactKeys($payload, ['token']) || !is_string($payload['token'])) {
            $this->validationError(['token' => ['El enlace recibido no tiene un formato válido.']]);
            return;
        }
        $token = trim($payload['token']);
        if (!preg_match(self::TOKEN_PATTERN, $token)) {
            $this->invalidState();
            return;
        }
        try {
            $valid = $this->service->findValidToken($token, 'app') !== null;
        } catch (Throwable $error) {
            error_log('Password reset validate unavailable: ' . $error::class);
            $this->respond(503, ['success' => false, 'code' => 'reset_unavailable', 'message' => self::VALIDATE_UNAVAILABLE]);
            return;
        }
        if (!$valid) {
            $this->invalidState();
            return;
        }
        $this->respond(200, ['success' => true, 'state' => 'valid']);
    }

    public function update(): void
    {
        $this->headers();
        if (!$this->validCsrf()) {
            $this->csrfError();
            return;
        }
        $payload = $this->decodeObject(($this->bodyReader)());
        if ($payload === null || !$this->hasExactKeys($payload, ['token', 'password', 'confirmPassword'])
            || !is_string($payload['token']) || !is_string($payload['password'])
            || !is_string($payload['confirmPassword'])) {
            $this->validationError([]);
            return;
        }
        $token = trim($payload['token']);
        if (!preg_match(self::TOKEN_PATTERN, $token)) {
            $this->invalidLinkError();
            return;
        }
        try {
            $result = $this->service->reset($token, 'app', $payload['password'], $payload['confirmPassword']);
        } catch (Throwable $error) {
            error_log('Password reset update unavailable: ' . $error::class);
            $this->unavailableError();
            return;
        }
        if (($result['success'] ?? false) === true) {
            $this->respond(200, [
                'success' => true,
                'message' => 'Contraseña restablecida correctamente.',
                'redirect' => '/login?reset=1',
            ]);
            return;
        }
        $message = is_string($result['message'] ?? null) ? $result['message'] : '';
        if (isset(self::POLICY_FIELDS[$message])) {
            $field = self::POLICY_FIELDS[$message];
            $this->validationError([$field => [$message]], $message);
            return;
        }
        if (in_array($message, [self::INVALID_MESSAGE, 'Usuario no encontrado'], true)) {
            $this->invalidLinkError();
            return;
        }
        $this->unavailableError();
    }

    private function validCsrf(): bool
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        return CsrfTokenManager::validate(is_string($token) ? $token : null, 'shell_api');
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $expected
     */
    private function hasExactKeys(array $payload, array $expected): bool
    {
        $actual = array_keys($payload);
        sort($actual);
        sort($expected);
        return $actual === $expected;
    }

    /** @return array<string, mixed>|null */
    private function decodeObject(string $raw): ?array
    {
        try {
            $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
        return is_array($payload) && !array_is_list($payload) ? $payload : null;
    }

    private function headers(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('X-Content-Type-Options: nosniff');
    }

    private function csrfError(): void
    {
        $this->respond(403, ['success' => false, 'code' => 'csrf_invalid', 'message' => 'No fue posible validar la solicitud. Intenta nuevamente.']);
    }

    /** @param array<string, list<string>> $fields */
    private function validationError(array $fields, string $message = 'Revisa los campos.'): void
    {
        $this->respond(422, ['success' => false, 'code' => 'validation_error', 'message' => $message, 'fieldErrors' => $fields]);
    }

    private function invalidState(): void
    {
        $this->respond(200, ['success' => true, 'state' => 'invalid', 'message' => self::INVALID_MESSAGE]);
    }

    private function invalidLinkError(): void
    {
        $this->respond(410, ['success' => false, 'code' => 'reset_link_invalid', 'message' => self::INVALID_MESSAGE]);
    }

    private function unavailableError(): void
    {
        $this->respond(503, ['success' => false, 'code' => 'reset_unavailable', 'message' => self::UPDATE_UNAVAILABLE]);
    }

    /** @param array<string, mixed> $payload */
    private function respond(int $status, array $payload): void
    {
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
```

The key set is exact but JSON object order is irrelevant. Add one pure case with reversed key order
that still reaches the fake, and keep every extra/missing key rejected before the service.

- [ ] **Step 4: Run pure contract and syntax check**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_api_password_reset_contract.php
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php -l src/Controllers/Api/PasswordResetApiController.php
```

Expected: full matrix PASS, syntax RC 0, no DB/mail initialization.

- [ ] **Step 5: Commit API adapter and pure contract**

```bash
git add src/Controllers/Api/PasswordResetApiController.php tests/test_api_password_reset_contract.php
git commit -m "feat(auth): adaptar restablecimiento a JSON"
```

---

### Task 3: Registrar endpoints públicos y probar HTTP pre-mutación

**Files:**
- Modify: `public/index.php`
- Create: `tests/test_api_password_reset_http.php`
- Test: `tests/test_api_password_reset_contract.php`

**Interfaces:**
- POST `/api/auth/password/reset/validate` → `validateLink`.
- POST `/api/auth/password/reset` → `update`.
- Both are public for anonymous CSRF, but not maintenance-exempt.
- HTTP test never sends a syntactically valid token to `update`.

- [ ] **Step 1: Write failing standalone HTTP matrix**

Use a private cookie jar and a curl helper that captures status, content type, headers and decoded JSON. Define `$base = rtrim(getenv('APP_URL') ?: 'http://127.0.0.1', '/')`, abort RC 2 if curl/jar fails and always unlink in `finally`.

```php
function requestJson(
    string $method,
    string $url,
    string $jar,
    array|string|null $body = null,
    array $headers = [],
): array
{
    $curl = curl_init($url);
    if ($curl === false) throw new RuntimeException('No se pudo iniciar HTTP S03');
    $responseHeaders = [];
    $requestHeaders = array_merge(['Accept: application/json'], $headers);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$responseHeaders): int {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
            return strlen($line);
        },
    ];
    if ($body !== null) {
        $requestHeaders[] = 'Content-Type: application/json';
        $options[CURLOPT_POSTFIELDS] = is_string($body)
            ? $body
            : json_encode($body, JSON_THROW_ON_ERROR);
    }
    $options[CURLOPT_HTTPHEADER] = $requestHeaders;
    curl_setopt_array($curl, $options);
    $raw = curl_exec($curl);
    if ($raw === false) {
        $error = curl_error($curl);
        curl_close($curl);
        throw new RuntimeException("La aplicación no respondió: {$error}");
    }
    $result = [
        'code' => (int) curl_getinfo($curl, CURLINFO_HTTP_CODE),
        'type' => (string) curl_getinfo($curl, CURLINFO_CONTENT_TYPE),
        'headers' => $responseHeaders,
        'json' => json_decode($raw, true),
    ];
    curl_close($curl);
    return $result;
}

function check(bool $condition, string $label): void
{
    global $failures;
    if (!$condition) {
        echo "FAIL: {$label}\n";
        $failures++;
    }
}
```

```php
$session = requestJson('GET', "{$base}/api/session", $jar);
$csrf = is_string($session['json']['csrfToken'] ?? null) ? $session['json']['csrfToken'] : '';
$invalidToken = 'not-a-token';

$invalid = requestJson('POST', "{$base}/api/auth/password/reset/validate", $jar,
    ['token' => $invalidToken], ['X-CSRF-Token: ' . $csrf]);
check($invalid['code'] === 200 && ($invalid['json']['state'] ?? '') === 'invalid', 'token mal formado no consulta DB');
check(str_contains(strtolower($invalid['headers']['cache-control'] ?? ''), 'no-store'), 'validate no-store');

$extra = requestJson('POST', "{$base}/api/auth/password/reset/validate", $jar,
    ['token' => $invalidToken, 'scope' => 'admin'], ['X-CSRF-Token: ' . $csrf]);
check($extra['code'] === 422, 'validate rechaza autoridad cliente');

$badCsrf = requestJson('POST', "{$base}/api/auth/password/reset", $jar, [
    'token' => $invalidToken, 'password' => 'Abcdef!', 'confirmPassword' => 'Abcdef!',
], ['X-CSRF-Token: invalid']);
check($badCsrf['code'] === 403 && ($badCsrf['json']['code'] ?? '') === 'csrf_invalid', 'update exige CSRF');

$invalidUpdate = requestJson('POST', "{$base}/api/auth/password/reset", $jar, [
    'token' => $invalidToken, 'password' => 'Abcdef!', 'confirmPassword' => 'Abcdef!',
], ['X-CSRF-Token: ' . $csrf]);
check($invalidUpdate['code'] === 410 && ($invalidUpdate['json']['code'] ?? '') === 'reset_link_invalid', 'update corta token sintáctico');

$wrongMethod = requestJson('GET', "{$base}/api/auth/password/reset/validate", $jar);
check($wrongMethod['code'] === 405, 'método incorrecto conserva el 405 del router');
```

Wrap all cases in `try/finally`, unlink the jar and exit with `$failures === 0 ? 0 : 1`. Also cover
broken JSON/list/missing string as 422 and `X-Content-Type-Options: nosniff`. Never use 64 hex on
`update`.

- [ ] **Step 2: Run HTTP test and confirm RED**

Run: `docker compose exec -T app php tests/test_api_password_reset_http.php`

Expected: FAIL 404 because endpoints are not registered.

- [ ] **Step 3: Register both routes without maintenance bypass**

```php
$router->post('/api/auth/password/reset/validate', [
    \App\Controllers\Api\PasswordResetApiController::class,
    'validateLink',
]);
$router->post('/api/auth/password/reset', [
    \App\Controllers\Api\PasswordResetApiController::class,
    'update',
]);
```

Add both exact paths to `$publicRoutes`; do not add them to `MaintenanceMode::isExemptRoute()`. Keep legacy GET/POST `/password/reset` untouched.

- [ ] **Step 4: Run pure/HTTP/session/maintenance contracts**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_api_password_reset_contract.php
docker compose exec -T app php tests/test_api_password_reset_http.php
docker compose exec -T app php tests/test_api_session_contract.php
docker compose exec -T app php tests/test_maintenance_asset_exemption.php
```

Expected: all PASS; DB write log remains empty and no reset/mail test runs.

- [ ] **Step 5: Commit public endpoints and safe HTTP contract**

```bash
git add public/index.php tests/test_api_password_reset_http.php
git commit -m "feat(auth): publicar endpoints de restablecimiento"
```

---

### Task 4: Parsear bearer token y enrutar S03 con precedencia pública

**Files:**
- Create: `frontend/src/shell/auth/tokenReset.ts`
- Create: `frontend/src/shell/auth/tokenReset.test.ts`
- Modify: `frontend/src/App.tsx`
- Modify: `frontend/src/shell/rutas.tsx`
- Modify: `frontend/src/shell/rutas.test.tsx`

**Interfaces:**
- `leerTokenReset(search: string): {kind:'candidate';token:string}|{kind:'invalid'}`.
- `/password/reset` and `/app/password/reset` render S03 for anonymous, pending or authenticated session.
- S03 receives `csrfToken`, `token` and `alRevalidar`; it never receives user/project.

- [ ] **Step 1: Write failing parser/route tests**

```ts
const TOKEN = 'a'.repeat(64);

test.each([
  ['', { kind: 'invalid' }],
  ['?token=', { kind: 'invalid' }],
  ['?token=abc', { kind: 'invalid' }],
  [`?token=${TOKEN}&token=${TOKEN}`, { kind: 'invalid' }],
  [`?token=${TOKEN}`, { kind: 'candidate', token: TOKEN }],
  [`?utm=email&token=${TOKEN}`, { kind: 'candidate', token: TOKEN }],
])('leerTokenReset(%s)', (search, expected) => {
  expect(leerTokenReset(search)).toEqual(expected);
});

test.each(['anonymous', 'password_change_required', 'authenticated'])('S03 prevalece en %s', async (state) => {
  mockSesion(state);
  window.history.pushState({}, '', `/password/reset?token=${TOKEN}`);
  render(<App />);
  expect(await screen.findByRole('heading', { name: 'Define tu nueva contraseña' })).toBeVisible();
  expect(screen.queryByText('test.A')).not.toBeInTheDocument();
});
```

Repeat the canonical assertions for `/app/password/reset`; missing/repeated token renders the invalid state and does not call `validarEnlaceReset`. Keep all S01/S02 route tests unchanged.

- [ ] **Step 2: Run parser/router tests and confirm RED**

Run: `npm --prefix frontend test -- src/shell/auth/tokenReset.test.ts src/shell/rutas.test.tsx`

Expected: FAIL because parser/route/component do not exist.

- [ ] **Step 3: Implement exact parser and route branch**

```ts
export function leerTokenReset(search: string):
  | { kind: 'candidate'; token: string }
  | { kind: 'invalid' } {
  const values = new URLSearchParams(search).getAll('token');
  if (values.length !== 1 || !TOKEN_RESET_PATTERN.test(values[0])) return { kind: 'invalid' };
  return { kind: 'candidate', token: values[0] };
}
```

In `Rutas`, place both S03 routes beside S02 and before the wildcard/session branches. `RutaRestablecimiento` preserves loading/error from `useSesion`; with CSRF available it renders `PantallaRestablecerClave` using only parser result, CSRF and `estado.recargar`.

- [ ] **Step 4: Run route regression and typecheck**

```bash
npm --prefix frontend test -- src/shell/auth/tokenReset.test.ts src/shell/rutas.test.tsx src/shell/auth/PantallaLogin.test.tsx src/shell/auth/PantallaRecuperarClave.test.tsx
npm --prefix frontend run typecheck
```

Expected: S01/S02/S03 paths and session precedence PASS; typecheck RC 0.

- [ ] **Step 5: Commit public route slice**

```bash
git add frontend/src/App.tsx frontend/src/shell/rutas.tsx frontend/src/shell/rutas.test.tsx frontend/src/shell/auth/tokenReset.ts frontend/src/shell/auth/tokenReset.test.ts
git commit -m "feat(shell): enrutar restablecimiento publico"
```

---

### Task 5: Implementar validación del enlace y estados terminales

**Files:**
- Create: `frontend/src/shell/auth/PantallaRestablecerClave.tsx`
- Create: `frontend/src/shell/auth/PantallaRestablecerClave.test.tsx`
- Modify: `frontend/src/shell/rutas.tsx`

**Interfaces:**
- Props: `{token:string|null;csrfToken:string;alRevalidar:()=>Promise<void>;alCompletar:(redirect:'/login?reset=1')=>void}`.
- Validates once per mounted token with AbortController; aborted response never updates state.
- Invalid state never renders form or identity.
- 403/503/network validation errors have explicit actions; no timer/retry loop.

- [ ] **Step 1: Write failing link-state tests**

```tsx
const TOKEN = 'a'.repeat(64);
const props = {
  token: TOKEN,
  csrfToken: 'b'.repeat(64),
  alRevalidar: vi.fn().mockResolvedValue(undefined),
  alCompletar: vi.fn(),
};

test('valida token y solo después muestra el formulario', async () => {
  let resolve!: (value: EstadoEnlaceReset) => void;
  vi.mocked(validarEnlaceReset).mockReturnValue(new Promise((done) => { resolve = done; }));
  render(<PantallaRestablecerClave {...props} />);
  expect(screen.getByRole('status')).toHaveTextContent('Validando enlace…');
  expect(screen.queryByLabelText('Nueva contraseña')).not.toBeInTheDocument();
  resolve({ success: true, state: 'valid' });
  expect(await screen.findByLabelText('Nueva contraseña')).toBeVisible();
});

test('token inválido no llama API y ofrece S02/S01', () => {
  render(<PantallaRestablecerClave {...props} token={null} />);
  expect(validarEnlaceReset).not.toHaveBeenCalled();
  expect(screen.getByRole('alert')).toHaveTextContent(MENSAJE_ENLACE_RESET_INVALIDO);
  expect(screen.getByRole('link', { name: 'Solicitar un nuevo enlace' })).toHaveAttribute('href', '/password/forgot');
  expect(screen.getByRole('link', { name: 'Volver al inicio de sesión' })).toHaveAttribute('href', '/login');
});
```

Add tests for API invalid, 403 action, 503/retry, network/contract copy, abort on unmount and no user/token text.

- [ ] **Step 2: Run component tests and confirm RED**

Run: `npm --prefix frontend test -- src/shell/auth/PantallaRestablecerClave.test.tsx`

Expected: FAIL because component is absent.

- [ ] **Step 3: Implement finite link-validation states**

```tsx
type LinkState =
  | { kind: 'invalid'; message: string }
  | { kind: 'validating' }
  | { kind: 'valid' }
  | { kind: 'error'; message: string; csrf: boolean };

const [validationAttempt, setValidationAttempt] = useState(0);

useEffect(() => {
  if (token === null) {
    setLinkState({ kind: 'invalid', message: MENSAJE_ENLACE_RESET_INVALIDO });
    return;
  }
  const controller = new AbortController();
  setLinkState({ kind: 'validating' });
  validarEnlaceReset(token, csrfToken, controller.signal)
    .then((response) => setLinkState(response.state === 'valid'
      ? { kind: 'valid' }
      : { kind: 'invalid', message: response.message }))
    .catch((cause) => {
      if (controller.signal.aborted) return;
      const csrf = esErrorApi(cause) && cause.status === 403;
      setLinkState({
        kind: 'error', csrf,
        message: esErrorApi(cause) && cause.status === 503
          ? cause.message
          : 'No pudimos validar el enlace. Intenta nuevamente.',
      });
    });
  return () => controller.abort();
}, [token, csrfToken, validationAttempt]);
```

`validationAttempt` changes only after a user clicks retry or after an explicit CSRF session refresh:

```tsx
async function retryValidation() {
  if (linkState.kind === 'error' && linkState.csrf) {
    await alRevalidar();
  }
  setValidationAttempt((current) => current + 1);
}
```

Guard this action with its own busy boolean so double click increments once; on refresh failure keep
the action and show «No pudimos actualizar la sesión. Intenta nuevamente.». Use `MarcoAcceso`, one
h1, status/alert and links. Do not render token.

- [ ] **Step 4: Run link-state, router and type gates**

```bash
npm --prefix frontend test -- src/shell/auth/PantallaRestablecerClave.test.tsx src/shell/rutas.test.tsx
npm --prefix frontend run typecheck
```

Expected: validation/invalid/error/actions/abort PASS, no unhandled promise, typecheck RC 0.

- [ ] **Step 5: Commit safe link validation UI**

```bash
git add frontend/src/shell/auth/PantallaRestablecerClave.tsx frontend/src/shell/auth/PantallaRestablecerClave.test.tsx frontend/src/shell/rutas.tsx
git commit -m "feat(auth): validar enlace de restablecimiento"
```

---

### Task 6: Construir política, campos secretos y toggles accesibles

**Files:**
- Modify: `frontend/src/shell/auth/CampoClave.tsx`
- Modify: `frontend/src/shell/auth/PantallaRestablecerClave.tsx`
- Modify: `frontend/src/shell/auth/PantallaRestablecerClave.test.tsx`
- Test: existing S01 `CampoClave` consumers

**Interfaces:**
- `CampoClave` keeps all S01 props and adds `ref` to input plus optional `describedBy` and
  `placeholder`.
- Parent can reset internal visibility by changing React `key`; no password visibility lives globally.
- Local validation preserves typed values and focuses the first policy/mismatch field.
- No gateway call occurs until all four client-visible rules pass.

- [ ] **Step 1: Write failing field, policy and toggle tests**

```tsx
test('presenta dos secretos con política y toggles independientes', async () => {
  const user = userEvent.setup();
  vi.mocked(validarEnlaceReset).mockResolvedValue({ success: true, state: 'valid' });
  render(<PantallaRestablecerClave {...props} />);
  const password = await screen.findByLabelText('Nueva contraseña');
  const confirm = screen.getByLabelText('Confirmar contraseña');
  expect(password).toHaveAttribute('autocomplete', 'new-password');
  expect(password).toHaveAttribute('aria-describedby', 'reset-password-policy');
  expect(confirm).toHaveAttribute('autocomplete', 'new-password');
  await user.click(screen.getAllByRole('button', { name: 'Mostrar contraseña' })[0]);
  expect(password).toHaveAttribute('type', 'text');
  expect(confirm).toHaveAttribute('type', 'password');
  expect(screen.getByRole('button', { name: 'Ocultar contraseña' })).toHaveAttribute('aria-pressed', 'true');
  expect(password).toHaveFocus();
});

test.each([
  ['abc', 'abc', 'La contraseña debe tener al menos 6 caracteres', 'Nueva contraseña'],
  ['abcdef!', 'abcdef!', 'Debe contener al menos una letra mayúscula', 'Nueva contraseña'],
  ['Abcdef', 'Abcdef', 'Debe contener al menos un carácter especial (!@#$%...)', 'Nueva contraseña'],
  ['Abcdef!', 'Otra1!', 'Las contraseñas no coinciden', 'Confirmar contraseña'],
])('valida %s sin llamar API', async (password, confirm, message, label) => {
  const user = userEvent.setup();
  render(<PantallaRestablecerClave {...props} />);
  await user.type(await screen.findByLabelText('Nueva contraseña'), password);
  await user.type(screen.getByLabelText('Confirmar contraseña'), confirm);
  await user.click(screen.getByRole('button', { name: 'Actualizar contraseña' }));
  expect(restablecerClave).not.toHaveBeenCalled();
  expect(screen.getByText(message)).toBeVisible();
  expect(screen.getByLabelText(label)).toHaveFocus();
});
```

Also assert editing the affected field clears only its error and both values remain after local failure.

- [ ] **Step 2: Run component/S01 regression and confirm RED**

Run: `npm --prefix frontend test -- src/shell/auth/PantallaRestablecerClave.test.tsx src/shell/auth/PantallaLogin.test.tsx src/shell/auth/CambioClaveObligatorio.test.tsx`

Expected: FAIL because reset form and ref/describedBy support are absent.

- [ ] **Step 3: Implement backward-compatible CampoClave and local form**

Change `CampoClave` to `forwardRef<HTMLInputElement, CampoClaveProps>` and merge the existing error id with `describedBy`:

```tsx
const describedByValue = [describedBy, error ? `${id}-error` : null].filter(Boolean).join(' ') || undefined;

<input
  ref={ref}
  id={id}
  name={name}
  type={visible ? 'text' : 'password'}
  value={value}
  onChange={onChange}
  autoComplete={autoComplete}
  placeholder={placeholder}
  required
  aria-describedby={describedByValue}
  aria-invalid={error !== null}
  disabled={disabled}
/>
```

Keep the existing toggle semantics, focus return and S01 class names unchanged. In S03 render both fields with keys derived from `secretVersion`, refs and exact policy:

```tsx
<ul id="reset-password-policy" className="aia-auth__policy">
  <li>Mínimo 6 caracteres</li>
  <li>Al menos una letra mayúscula</li>
  <li>Al menos un carácter especial</li>
</ul>
<CampoClave key={`password-${secretVersion}`} ref={passwordRef}
  id="reset-password" name="password" label="Nueva contraseña"
  value={password} onChange={onPasswordChange} autoComplete="new-password"
  placeholder="Nueva contraseña"
  describedBy="reset-password-policy" error={fieldErrors.password ?? null} disabled={submitting} />
<CampoClave key={`confirm-${secretVersion}`} ref={confirmRef}
  id="reset-confirm" name="confirmPassword" label="Confirmar contraseña"
  value={confirmPassword} onChange={onConfirmChange} autoComplete="new-password"
  placeholder="Confirma tu contraseña"
  error={fieldErrors.confirmPassword ?? null} disabled={submitting} />
```

On submit, call `EsquemaSolicitudRestablecerClave.safeParse`; use the first issue path/message, set only that field error and focus its ref. Do not call gateway on local failure.

```tsx
function applyLocalIssue(issue: { path: PropertyKey[]; message: string }) {
  const field = issue.path[0] === 'confirmPassword' ? 'confirmPassword' : 'password';
  setFieldErrors({
    password: field === 'password' ? issue.message : null,
    confirmPassword: field === 'confirmPassword' ? issue.message : null,
  });
  requestAnimationFrame(() => {
    (field === 'confirmPassword' ? confirmRef.current : passwordRef.current)?.focus();
  });
}
```

- [ ] **Step 4: Run form, shared-field and type gates**

```bash
npm --prefix frontend test -- src/shell/auth/PantallaRestablecerClave.test.tsx src/shell/auth/PantallaLogin.test.tsx src/shell/auth/CambioClaveObligatorio.test.tsx
npm --prefix frontend run typecheck
```

Expected: policy/toggles/focus and S01 shared consumers PASS; typecheck RC 0.

- [ ] **Step 5: Commit accessible reset form**

```bash
git add frontend/src/shell/auth/CampoClave.tsx frontend/src/shell/auth/PantallaRestablecerClave.tsx frontend/src/shell/auth/PantallaRestablecerClave.test.tsx
git commit -m "feat(auth): construir formulario de nueva clave"
```

---

### Task 7: Completar mutación, errores y navegación segura

**Files:**
- Modify: `frontend/src/shell/auth/PantallaRestablecerClave.tsx`
- Modify: `frontend/src/shell/auth/PantallaRestablecerClave.test.tsx`
- Modify: `frontend/src/shell/rutas.tsx`
- Test: `frontend/src/shell/auth/PantallaLogin.test.tsx`

**Interfaces:**
- One explicit submit calls `restablecerClave` once; a synchronous ref closes the double-event window.
- Any server/network settle clears both secrets and resets both toggles to hidden.
- 422 maps fields; 403 refreshes session without resend; 410 invalidates screen; 503/network is honest.
- Success calls `navigate('/login?reset=1', {replace:true})` through `alCompletar`.

- [ ] **Step 1: Write failing update/error matrix tests**

```tsx
const POLICY_REUSE = 'La nueva contraseña no puede ser igual a la anterior';

test('doble click produce una mutación y success reemplaza ruta', async () => {
  const user = userEvent.setup();
  let resolve!: (value: RestablecimientoAceptado) => void;
  vi.mocked(restablecerClave).mockReturnValue(new Promise((done) => { resolve = done; }));
  render(<PantallaRestablecerClave {...props} />);
  await fillValidSecrets(user);
  await user.dblClick(screen.getByRole('button', { name: 'Actualizar contraseña' }));
  expect(restablecerClave).toHaveBeenCalledOnce();
  expect(screen.getByRole('button', { name: 'Actualizando…' })).toBeDisabled();
  resolve({ success: true, message: 'Contraseña restablecida correctamente.', redirect: '/login?reset=1' });
  await waitFor(() => expect(props.alCompletar).toHaveBeenCalledWith('/login?reset=1'));
});

test('403 limpia secretos, refresca sesión y nunca reenvía', async () => {
  const user = userEvent.setup();
  vi.mocked(restablecerClave).mockRejectedValue(apiError(403, 'csrf_invalid', 'No fue posible validar la solicitud. Intenta nuevamente.'));
  render(<PantallaRestablecerClave {...props} />);
  await fillValidSecrets(user);
  await user.click(screen.getByRole('button', { name: 'Actualizar contraseña' }));
  expect(await screen.findByLabelText('Nueva contraseña')).toHaveValue('');
  await user.click(screen.getByRole('button', { name: 'Actualizar sesión' }));
  expect(props.alRevalidar).toHaveBeenCalledOnce();
  expect(restablecerClave).toHaveBeenCalledOnce();
});

function apiError(
  status: number,
  code: string | null,
  message: string,
  fieldErrors: Record<string, string[]> = {},
) {
  return new ErrorApi({
    endpoint: '/api/auth/password/reset', status, code, message, fieldErrors,
    redirect: null, correlationId: null, kind: status === 0 ? 'network' : 'http',
  });
}

async function fillAndSubmit() {
  const user = userEvent.setup();
  await fillValidSecrets(user);
  await user.click(screen.getByRole('button', { name: 'Actualizar contraseña' }));
}

test.each([
  [422, 'validation_error', POLICY_REUSE, 'password', 'Nueva contraseña'],
  [422, 'validation_error', 'Las contraseñas no coinciden', 'confirmPassword', 'Confirmar contraseña'],
  [503, 'reset_unavailable', 'Error al actualizar la contraseña.', null, 'alert'],
  [0, null, 'network', null, 'alert'],
])('maneja status %s y limpia secretos', async (status, code, message, field, focusTarget) => {
  vi.mocked(restablecerClave).mockRejectedValue(apiError(status, code, message,
    field ? { [field]: [message] } : {}));
  render(<PantallaRestablecerClave {...props} />);
  await fillAndSubmit();
  expect(await screen.findByLabelText('Nueva contraseña')).toHaveValue('');
  expect(screen.getByLabelText('Confirmar contraseña')).toHaveValue('');
  if (focusTarget === 'alert') expect(screen.getByRole('alert')).toHaveFocus();
  else expect(screen.getByLabelText(focusTarget)).toHaveFocus();
});
```

Add 410 → invalid state/no form, malformed 2xx → contract copy, update-session failure, Enter while busy and toggle reset-to-password assertions. `apiError` creates S01 `ErrorApi` with endpoint constant and no token.

- [ ] **Step 2: Run focused tests and confirm RED**

Run: `npm --prefix frontend test -- src/shell/auth/PantallaRestablecerClave.test.tsx src/shell/rutas.test.tsx`

Expected: FAIL because update mapping/redirect do not exist.

- [ ] **Step 3: Implement guarded submit, cleanup and exact catch mapping**

```tsx
const submitGuard = useRef(false);

function clearSecrets() {
  setPassword('');
  setConfirmPassword('');
  setSecretVersion((current) => current + 1);
}

function focusAfterSecretReset(field: 'password' | 'confirm') {
  requestAnimationFrame(() => {
    (field === 'confirm' ? confirmRef.current : passwordRef.current)?.focus();
  });
}

function focusRefreshAction() {
  requestAnimationFrame(() => refreshRef.current?.focus());
}

function focusAlertAfterSecretReset() {
  requestAnimationFrame(() => alertRef.current?.focus());
}

async function submit(event: FormEvent<HTMLFormElement>) {
  event.preventDefault();
  if (submitGuard.current) return;
  const parsed = EsquemaSolicitudRestablecerClave.safeParse({ token, password, confirmPassword });
  if (!parsed.success) {
    applyLocalIssue(parsed.error.issues[0]);
    return;
  }
  submitGuard.current = true;
  setSubmitting(true);
  setGeneralError(null);
  setFieldErrors({});
  try {
    const response = await restablecerClave(parsed.data, csrfToken);
    clearSecrets();
    alCompletar(response.redirect);
  } catch (cause) {
    clearSecrets();
    if (esErrorApi(cause) && cause.status === 422) {
      setFieldErrors({
        password: cause.fieldErrors.password?.[0] ?? null,
        confirmPassword: cause.fieldErrors.confirmPassword?.[0] ?? null,
      });
      focusAfterSecretReset(cause.fieldErrors.confirmPassword ? 'confirm' : 'password');
    } else if (esErrorApi(cause) && cause.status === 403) {
      setNeedsSessionRefresh(true);
      setGeneralError(cause.message);
      focusRefreshAction();
    } else if (esErrorApi(cause) && cause.status === 410) {
      setLinkState({ kind: 'invalid', message: MENSAJE_ENLACE_RESET_INVALIDO });
      requestAnimationFrame(() => requestLinkRef.current?.focus());
    } else {
      setGeneralError(esErrorApi(cause) && cause.status === 503
        ? cause.message
        : 'No pudimos confirmar el cambio. Intenta iniciar sesión; si no funciona, solicita un enlace nuevo.');
      focusAlertAfterSecretReset();
    }
  } finally {
    submitGuard.current = false;
    setSubmitting(false);
  }
}
```

Implement focus helpers with `requestAnimationFrame` after keyed fields remount. `Actualizar sesión` calls only `alRevalidar`, clears the 403 action on success and focuses empty password; on failure it keeps the action. In `RutaRestablecimiento`, implement `alCompletar={(redirect) => navigate(redirect, {replace:true})}`.

- [ ] **Step 4: Run S03/S01 route and no-fetch gates**

```bash
npm --prefix frontend test -- src/shell/auth/PantallaRestablecerClave.test.tsx src/shell/rutas.test.tsx src/shell/auth/PantallaLogin.test.tsx src/lib/api/frontera.test.ts
npm --prefix frontend run typecheck
```

Expected: all update outcomes, single-submit, cleanup, replace and S01 reset notice PASS.

- [ ] **Step 5: Commit mutation and secure navigation**

```bash
git add frontend/src/shell/auth/PantallaRestablecerClave.tsx frontend/src/shell/auth/PantallaRestablecerClave.test.tsx frontend/src/shell/rutas.tsx frontend/src/shell/rutas.test.tsx
git commit -m "feat(auth): completar cambio de clave React"
```

---

### Task 8: Integrar presentación, build y corte GET/HEAD canónico

**Files:**
- Modify: `public/css/auth-react.css`
- Modify: `docs/design-system/manifests/auth.json`
- Modify: `tests/test_login_design_system_contract.mjs`
- Modify: `src/Core/SpaRouter.php`
- Modify: `public/index.php`
- Modify: `tests/test_spa_frontera.php`
- Modify: `tests/test_spa_frontera_http.php`
- Generate: `public/app/index.html`, `public/app/assets/index-*.js`
- Preserve: legacy POST `/password/reset`, VIEW-03, `login-brand-unified.css`, `auth_forms.js`

**Interfaces:**
- Pilot `/app/password/reset`; canonical exact GET/HEAD `/password/reset` after gate.
- POST `/password/reset` remains legacy during rollback window.
- Canonical host sends `Referrer-Policy: no-referrer` and `Cache-Control: no-store`.
- Manifest declares React S03, both themes, four viewports and all states while VIEW-03 is retained as rollback source.

- [ ] **Step 1: Write failing style/manifest/boundary contracts**

Extend static and PHP tests to require:

```js
assert.match(read('frontend/src/shell/auth/PantallaRestablecerClave.tsx'), /MarcoAcceso/);
assert.match(read('frontend/src/shell/auth/PantallaRestablecerClave.tsx'), /CampoClave/);
assert.match(read('public/css/auth-react.css'), /\.aia-auth__policy/);
assert.doesNotMatch(read('public/css/auth-react.css'), /#[0-9a-f]{3,8}\b|rgba?\(|hsla?\(|!important/i);
```

Boundary matrix: `/app/password/reset` true for GET/HEAD; `/password/reset` true only in an injected map, canonical constant still false before cut; POST canonical false; `/password/reset-extra` false; `/api/auth/password/reset` false. HTTP requires HEAD body empty, GET host root, POST legacy form response, S01/S02 unchanged and rollback map without canonical returns false.

- [ ] **Step 2: Run static/boundary tests and confirm RED**

```bash
node tests/test_login_design_system_contract.mjs
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_spa_frontera.php
```

Expected: FAIL because S03 styles/manifest/canonical map are absent.

- [ ] **Step 3: Add token-based presentation, build and exact host route**

Add only S03 layout selectors inside the existing `@layer module`; use design tokens for policy list,
field errors, alert spacing, target sizes and narrow viewport wrapping. Update the manifest's existing
`sources`, `components`, `layouts`, `states` and `tests` arrays, retaining VIEW-03 in `sources` until
Task 10. Do not add a `rollbackSource` or `viewports` property: the current schema forbids them. Do
not invent a scenario hash; the existing approved auth scenario remains until Task 9's visual gate.

Build and verify pilot first:

```bash
npm --prefix frontend run build
```

Then add `'/password/reset'` to `SpaRouter::RUTAS_EXACTAS_MIGRADAS`; leave
`PREFIJOS_MIGRADOS` unchanged. In the SPA host branch before `SpaHostRenderer::render`:

```php
if ($requestUri === '/password/reset') {
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
}
```

Pass request method to the method-aware router/renderer. Do not remove legacy route registrations or assets.

- [ ] **Step 4: Run build, static, boundary and HTTP rollback gates**

```bash
npm --prefix frontend test
npm --prefix frontend run typecheck
npm --prefix frontend run build
node tests/test_login_design_system_contract.mjs
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_spa_frontera.php
docker compose exec -T app php tests/test_spa_frontera_http.php
docker compose exec -T app php tests/test_api_password_reset_http.php
git diff --check
```

Expected: frontend/build/static/PHP/HTTP PASS; GET/HEAD React, POST legacy, no-referrer/no-store present, rollback simulation PASS.

- [ ] **Step 5: Commit presentation and canonical cut**

```bash
git add frontend/src/shell/auth/PantallaRestablecerClave.tsx frontend/src/shell/auth/PantallaRestablecerClave.test.tsx public/css/auth-react.css docs/design-system/manifests/auth.json tests/test_login_design_system_contract.mjs src/Core/SpaRouter.php public/index.php tests/test_spa_frontera.php tests/test_spa_frontera_http.php public/app
git commit -m "feat(auth): cortar restablecimiento a React"
```

---

### Task 9: Verificar navegador, accesibilidad y visuales temporales

**Files:**
- Create: `tests/browser/password-reset-react.spec.mjs`
- Create: `tests/browser/password-reset-react.visual.mjs`
- Modify: `docs/design-system/manifests/auth.json` only after explicit visual approval
- Modify: S03 component/CSS only for demonstrated defects

**Interfaces:**
- Every session/API response is intercepted; no real token, password change, DB or SMTP.
- Functional matrix covers S03-UX-01…16 and S03-AC-01…15.
- Visual matrix is dark/light × four viewports with empty secrets and a synthetic token.

- [ ] **Step 1: Write failing controlled browser scenarios**

```js
const TOKEN = 'a'.repeat(64);
const CSRF = 'b'.repeat(64);
const session = {
  state: 'anonymous', authenticated: false, reason: 'missing_session', user: null, project: null,
  capabilities: {}, navigation: { bi: null }, csrfToken: CSRF,
};

await page.route('**/api/session', (route) => route.fulfill({
  status: 200, contentType: 'application/json', body: JSON.stringify(session),
}));
await page.route('**/api/auth/password/reset/validate', async (route) => {
  expect(route.request().postDataJSON()).toEqual({ token: TOKEN });
  await route.fulfill({ status: 200, contentType: 'application/json',
    body: JSON.stringify({ success: true, state: 'valid' }) });
});
let updates = 0;
await page.route('**/api/auth/password/reset', async (route) => {
  updates += 1;
  expect(route.request().postDataJSON()).toEqual({
    token: TOKEN, password: 'Abcdef!', confirmPassword: 'Abcdef!',
  });
  await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({
    success: true, message: 'Contraseña restablecida correctamente.', redirect: '/login?reset=1',
  }) });
});

await page.goto(`/password/reset?token=${TOKEN}`);
await page.getByLabel('Nueva contraseña').fill('Abcdef!');
await page.getByLabel('Confirmar contraseña').fill('Abcdef!');
await page.getByRole('button', { name: 'Actualizar contraseña' }).dblclick();
await expect(page.getByRole('status')).toContainText('Tu contraseña fue restablecida correctamente. Ya puedes iniciar sesión.');
await expect(page).toHaveURL(/\/login$/);
expect(updates).toBe(1);
await page.goBack();
expect(page.url()).not.toContain('token=');
```

Implement this exact additional matrix:

| Case | Controlled response/action | Required assertion |
|---|---|---|
| Missing/repeated token | no validate route hit | invalid alert, S02/S01 links, no form. |
| API invalid | 200 `state=invalid` | same public alert, zero identity/token text. |
| Sessions | anonymous/authenticated/pending payloads | same S03, no user/project. |
| Policy | four local failures | correct field message/focus, zero update. |
| Reuse | 422 password field | secrets cleared, server message, password focus. |
| Mismatch server | 422 confirm field | secrets cleared, confirm focus. |
| Validate 403 | refresh action | one extra session GET, one new validate only after click. |
| Update 403 | refresh action | secrets cleared and updates remains 1 after refresh. |
| 410 | link invalid | form removed, S02 focus/action. |
| 503 | safe server message | secrets cleared, alert focus, manual submit only. |
| Network/contract | abort or malformed 200 | ambiguous safe copy, no token/body detail. |
| Toggles | independent clicks/keyboard | dynamic names/pressed and focus return. |
| Navigation | S02/S01 Links, refresh, back | client navigation; success back has no bearer. |
| A11y | Axe, Tab/Shift+Tab/Enter, 200 % | zero serious/critical, logical order, no hidden control. |
| Responsive/themes | 8 combinations | no horizontal overflow, console clean, expected requests only. |

- [ ] **Step 2: Run functional browser spec and confirm defects**

Run: `npx playwright test tests/browser/password-reset-react.spec.mjs --workers=1`

Expected: any real defect is RED with named scenario; no baseline change.

- [ ] **Step 3: Fix demonstrated S03 defects and add candidate capture**

```js
import { mkdir } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const dir = process.env.S03_VISUAL_DIR ?? join(tmpdir(), 'lps-aia-s03-visual');
const viewports = [
  { width: 390, height: 844 }, { width: 768, height: 1024 },
  { width: 1180, height: 820 }, { width: 1440, height: 900 },
];
await mkdir(dir, { recursive: true });
for (const theme of ['dark', 'light']) {
  for (const viewport of viewports) {
    await page.setViewportSize(viewport);
    await page.addInitScript((value) => localStorage.setItem('aia-theme', value), theme);
    await page.goto(`/password/reset?token=${'a'.repeat(64)}`);
    await page.getByLabel('Nueva contraseña').waitFor();
    await page.screenshot({
      path: join(dir, `password-reset-${theme}-${viewport.width}x${viewport.height}.png`),
      fullPage: true,
    });
  }
}
```

Intercept validate as valid, leave both password fields empty and never include token in filename. Fix only evidence-backed S03 code/CSS with existing tokens.

- [ ] **Step 4: Run browser, visual, frontend and static gates**

```bash
npx playwright test tests/browser/password-reset-react.spec.mjs --workers=1
npx playwright test tests/browser/password-reset-react.visual.mjs --workers=1
npm --prefix frontend test
npm --prefix frontend run typecheck
node tests/test_login_design_system_contract.mjs
git diff --check
```

Expected: functional/Axe/frontend/static PASS; eight untracked candidates outside repo for visual
approval. Task 10 cannot start until Felipe explicitly approves the candidate chosen for the auth
manifest or explicitly accepts functional evidence without a new golden.

- [ ] **Step 5: Commit test code, never candidate images**

If a new golden is approved, copy only the approved candidate to
`tests/browser/__screenshots__/auth/password-reset-dark-1180x820.png`, calculate its SHA-256 and add
one schema-valid S03 scenario to `docs/design-system/manifests/auth.json`. Never generate the hash by
guessing or silently replace another baseline.

```bash
git add tests/browser/password-reset-react.spec.mjs tests/browser/password-reset-react.visual.mjs frontend/src/shell/auth/PantallaRestablecerClave.tsx public/css/auth-react.css docs/design-system/manifests/auth.json
git commit -m "test(auth): verificar restablecimiento React"
```

Stage the PNG only when the explicit approval selected it; otherwise omit the manifest/PNG from this
commit and record the accepted functional-evidence decision in the plan closeout before Task 10.

---

### Task 10: Retirar VIEW-03 y cerrar el último auth legacy

**Files:**
- Modify: `public/index.php`
- Delete: `src/Controllers/Auth/PasswordResetController.php`
- Delete: `views/auth/password-reset.view.php`
- Delete: `public/css/login-brand-unified.css`
- Delete: `public/js/modules/aia_ui/auth_forms.js`
- Modify: `tests/test_login_design_system_contract.mjs`
- Modify: `tests/browser/design-system-consumer-smoke.mjs`
- Modify: `docs/design-system/manifests/auth.json`
- Modify: `docs/design-system/exceptions.json`
- Modify: `docs/design-system/unlayered-delivery-inventory.json`
- Modify: `docs/design-system/ui-groups-inventory.json`
- Modify: `tests/test_spa_frontera_http.php`

**Interfaces:**
- GET/HEAD canonical remains SPA; API validate/update remain public JSON.
- Legacy POST `/password/reset` no longer mutates and responds with the controlled product 404.
- No main-app consumer remains for VIEW-03/controller/auth legacy CSS/JS.
- `/admin/` assets remain untouched even if names are similar.

- [ ] **Step 1: Change closeout contracts to React-only auth**

Require static contracts to assert:

```js
assert.ok(manifest.sources.includes('frontend/src/shell/auth/PantallaRestablecerClave.tsx'));
assert.ok(!manifest.sources.includes('views/auth/password-reset.view.php'));
assert.ok(!existsSync(new URL('../views/auth/password-reset.view.php', import.meta.url)));
assert.ok(!existsSync(new URL('../public/css/login-brand-unified.css', import.meta.url)));
assert.ok(!existsSync(new URL('../public/js/modules/aia_ui/auth_forms.js', import.meta.url)));
```

HTTP requires GET/HEAD React with no-referrer/no-store, POST legacy 404, APIs still
403/200-invalid safely, S01/S02 links/routes intact and maintenance 503. Inventory expectations:
remove `/css/login-brand-unified.css` from `static`, set auth runtime routes to `unlayered: []`, mark
known case 7 closed, replace `field-help.sources` with the React component and remove the obsolete
`login` path budget.

- [ ] **Step 2: Run closeout tests and confirm RED before deletion**

```bash
node tests/test_login_design_system_contract.mjs
node scripts/design-system-unlayered-delivery.mjs
docker compose exec -T app php tests/test_spa_frontera_http.php
```

Expected: FAIL because VIEW-03/controller/POST/assets/inventory entries still exist.

- [ ] **Step 3: Remove only proven zero-consumer legacy auth files**

Before deletion run:

```bash
rg -n "PasswordResetController|password-reset\.view|login-brand-unified|aia_ui/auth_forms" public/index.php src views frontend tests docs/design-system/manifests docs/design-system/exceptions.json docs/design-system/unlayered-delivery-inventory.json docs/design-system/ui-groups-inventory.json
```

Expected after S01/S02 execution: matches only S03 legacy registrations/files and active contracts named in this task. Remove the two legacy route registrations, controller/view/CSS/JS, then update manifest/inventories/tests exactly as Step 1. Preserve API routes, services, theme assets and all `admin/` files. Re-run the scan; only historical audit documents outside the active scan may mention retired paths.

Keep `/password/reset` in the path-based public allowlist after removing its legacy handlers so an
anonymous POST reaches the controlled product 404 instead of an authentication redirect. The
method-aware SPA host continues to own only GET/HEAD.

- [ ] **Step 4: Run full S03 completion gate on one worktree tree/mount**

```bash
npm --prefix frontend test
npm --prefix frontend run typecheck
npm --prefix frontend run build
node tests/test_login_design_system_contract.mjs
node scripts/design-system-contracts.mjs
node scripts/design-system-unlayered-delivery.mjs
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_api_password_reset_contract.php
docker compose exec -T app php tests/test_api_password_reset_http.php
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_spa_frontera.php
docker compose exec -T app php tests/test_spa_frontera_http.php
docker compose exec -T app php tests/test_api_session_contract.php
docker compose exec -T app php tests/test_maintenance_asset_exemption.php
npx playwright test tests/browser/password-reset-react.spec.mjs --workers=1
git diff --check
```

Expected: all RC 0 on the mounted worktree. Do not run any data-tagged reset test. Confirm `git diff --name-only` has no `admin/`, database, migrations, RLS, reset services, mailer, user rows or credentials.

- [ ] **Step 5: Commit retirement and record the future verification SHA**

```bash
git add public/index.php tests/test_login_design_system_contract.mjs tests/browser/design-system-consumer-smoke.mjs docs/design-system/manifests/auth.json docs/design-system/exceptions.json docs/design-system/unlayered-delivery-inventory.json docs/design-system/ui-groups-inventory.json tests/test_spa_frontera_http.php public/app
git add -u src/Controllers/Auth/PasswordResetController.php views/auth/password-reset.view.php public/css/login-brand-unified.css public/js/modules/aia_ui/auth_forms.js
git commit -m "feat(auth): cerrar restablecimiento legacy"
git rev-parse HEAD
```

Record that SHA. Branch closeout must re-run the repository gate on the committed SHA before PR/publication; local green does not authorize push, merge or deploy.

## Observable Parity Traceability

| Capability | Plan evidence |
|---|---|
| S03-UX-01 | Tasks 5, 6 and 8: shared access frame and branded copy. |
| S03-UX-02 | Tasks 4–5 and 9: loading/valid/invalid without identity. |
| S03-UX-03 | Task 6: new password label, autocomplete and policy help. |
| S03-UX-04 | Task 6: confirmation label and autocomplete. |
| S03-UX-05 | Tasks 6–7 and 9: independent toggles, pressed state, focus and reset. |
| S03-UX-06 | Tasks 1, 2 and 6: length/uppercase/special client and PHP. |
| S03-UX-07 | Tasks 1, 2, 6–7: mismatch and confirmation focus. |
| S03-UX-08 | Tasks 2 and 7: previous-password rejection from server. |
| S03-UX-09 | Tasks 6–7 and 9: Enter/click/busy/single-submit. |
| S03-UX-10 | Tasks 2, 5, 7 and 9: explicit CSRF recovery without resend. |
| S03-UX-11 | Tasks 2, 7 and 9: 410 becomes invalid state. |
| S03-UX-12 | Tasks 5, 7 and 9: 503/network/contract honest and secrets cleared. |
| S03-UX-13 | Tasks 1, 7 and 9: exact replace redirect and S01 notice. |
| S03-UX-14 | Tasks 5 and 9: S02/S01 links. |
| S03-UX-15 | Tasks 8–9: dark/light parity. |
| S03-UX-16 | Tasks 6, 8 and 9: keyboard, focus, announcements, zoom and Axe. |

## Acceptance Traceability

| Criterion | Plan evidence |
|---|---|
| S03-AC-01 | Tasks 4, 8 and 9: pilot/canonical/deep link/refresh. |
| S03-AC-02 | Tasks 5–10: all UX and gate-retained VIEW-03. |
| S03-AC-03 | Tasks 1–3: constant validate endpoint, body token, Zod/CSRF. |
| S03-AC-04 | Tasks 2, 5 and 9: one invalid state without identity. |
| S03-AC-05 | Tasks 1–3: strict update body and scope app. |
| S03-AC-06 | Tasks 1–2, 6–7: five rules remain PHP authority. |
| S03-AC-07 | Tasks 2 and 7: exact 422 field mapping. |
| S03-AC-08 | Tasks 5, 7 and 9: 403 clears/no resend. |
| S03-AC-09 | Tasks 2, 7 and 9: token race → 410/invalid. |
| S03-AC-10 | Tasks 2, 5, 7 and 9: safe 503/red/contract. |
| S03-AC-11 | Tasks 7 and 9: synchronous guard and browser count. |
| S03-AC-12 | Tasks 1, 7 and 9: `/login?reset=1` exact notice. |
| S03-AC-13 | Tasks 7 and 9: replace and back without bearer. |
| S03-AC-14 | Tasks 8–9: 8 theme/viewport combinations. |
| S03-AC-15 | Tasks 6, 8–9: keyboard/focus/Axe/overflow. |
| S03-AC-16 | Tasks 1–3: Zod and PHP fake/HTTP pre-mutation. |
| S03-AC-17 | Global constraints and Tasks 3, 8–10: maintenance/admin/RLS/data untouched. |
| S03-AC-18 | Tasks 8 and 10: rollback before retirement and zero-consumer assets. |

## Completion Gate

S03 is `CODE_COMPLETE` when Tasks 1–9 pass on one worktree content set and visual candidates are approved without silent baseline regeneration. It is `MIGRATION_COMPLETE` only after Task 10 retires VIEW-03/legacy POST/assets, commits the result and re-verifies that exact SHA. Neither state authorizes deploy, DDL/DML, RLS changes, `/admin/` edits, real tokens or real password changes.
