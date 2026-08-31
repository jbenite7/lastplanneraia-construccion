# S01 Login React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** migrar `/` y `/login` al shell React con paridad observable del acceso legacy, cambio obligatorio de contraseña, ambos temas y entrada oculta de mantenimiento, sin ampliar autorización ni tocar RLS.

**Architecture:** el incremento vertical amplía primero el `ApiClient`, los esquemas Zod y `GET /api/session`; PHP sigue siendo autoridad de credenciales, política de contraseña, CSRF y sesión. React consume tres estados preproyecto (`anonymous`, `password_change_required`, `authenticated`), comparte una presentación de acceso para modo normal y mantenimiento, y recibe la acción oculta únicamente mediante bootstrap HTML inyectado por servidor. El corte empieza bajo `/app`, promueve solo `GET/HEAD /` y `GET/HEAD /login`, conserva rollback antes de retirar VIEW-01 y no incorpora todavía sidebar, semana ni módulos operativos de T01.

**Tech Stack:** PHP 8.3, MySQL 8.0.40, React 19, TypeScript 7, Vite 8, Zod 4, Vitest 4, Testing Library, Playwright 1.61, CSS design system AIA.

**Spec:** `docs/superpowers/specs/2026-08-30-s01-login-react-design.md` (consume el incremento de `docs/superpowers/specs/2026-08-30-t01-shell-runtime-react-design.md` requerido por S01)

## Global Constraints

- Trabajar exclusivamente en `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react`, rama `shell-minimo-react`; no leer, editar, probar ni publicar desde `/Volumes/Crucial X6/Developer/lps-aia`.
- Conservar cambios preexistentes y usar staging selectivo. No limpiar ni reescribir el worktree.
- `/admin/` queda completamente fuera: no modificar `admin/public`, `admin/src`, `admin/views`, AdminLTE ni sus rutas.
- No modificar RLS, schema, migraciones, grants, usuarios persistentes, credenciales ni fronteras de datos. El único DML previsto por la spec es un fixture de autenticación aleatorio dentro del contrato HTTP, creado y reconciliado en `try/finally`; no se ejecuta durante la redacción de este plan.
- Todo `fetch` productivo vive en `frontend/src/lib/api/cliente.ts`; componentes y gateways de dominio llaman `pedir()`.
- Todo endpoint nuevo lleva esquema Zod consumidor y cobertura contractual PHP. Las mutaciones autenticadas validan `X-CSRF-Token` con la clave `shell_api`.
- Los tipos TypeScript salen de `z.infer`; no crear interfaces paralelas a los esquemas.
- Los estilos consumen tokens de `public/css/tokens.css`; no añadir colores literales, estilos inline ni componentes visuales fuera del design system.
- Oscuro es el fallback inicial. Claro y oscuro deben conservar idéntica capacidad, persistencia y jerarquía.
- Viewports obligatorios: `390×844`, `768×1024`, `1180×820` y `1440×900`; 1180×820 dark es el gate desktop principal.
- No escribir contraseñas, CSRF, cookies, la ruta oculta ni payloads secretos en logs, snapshots, trazas, commits o mensajes de error.
- Las pruebas de UI no autentican cuentas reales por `/login`: usan API controlada. Los flujos autenticados de navegador usan `/dev/entrar`; el contrato PHP real usa fixture temporal y restauración exacta.
- Cualquier fixture DML de los contratos HTTP exige ventana de escritura coordinada, IDs propios, `try/finally` y reconciliación exacta antes de liberar la base compartida.
- Todo PHP/CLI se valida con el worktree montado en `/var/www/html`. Para CLI usar el contenedor efímero indicado en `docs/coordinacion-sesiones.md`; para HTTP/navegador coordinar la ventana del contenedor compartido y comprobar el mount antes de leer un RC.
- No regenerar ni reemplazar goldens sin aprobación visual explícita de Felipe. Guardar candidatos fuera de git hasta ese gate.
- No desplegar a producción. Commit y publicación siguen la política de cierre por PR de `AGENTS.md` cuando Felipe autorice ejecutar/cerrar el frente.

## File Structure

### Create

- `frontend/src/lib/api/esquemas/error.ts` — esquema Zod del error HTTP común y clase `ErrorApi`.
- `frontend/src/lib/api/esquemas/auth.ts` — requests/responses de login, cambio y cancelación.
- `frontend/src/lib/api/esquemas/auth.test.ts` — formas válidas y rechazo del contrato legacy.
- `frontend/src/lib/api/auth.ts` — gateway de los tres endpoints S01; ningún componente de acceso conoce sus rutas HTTP.
- `frontend/src/lib/api/frontera.test.ts` — guard estático: `fetch` productivo solo en `cliente.ts`.
- `frontend/src/lib/runtime/configuracion.ts` — Zod y lectura segura del bootstrap HTML de mantenimiento.
- `frontend/src/lib/runtime/configuracion.test.ts` — ausencia, forma válida y forma corrupta del bootstrap.
- `frontend/src/shell/auth/MarcoAcceso.tsx` — marca, tema, landmarks y footer compartidos por login/cambio obligatorio.
- `frontend/src/shell/auth/CampoClave.tsx` — campo de contraseña y toggle accesible reutilizable.
- `frontend/src/shell/auth/PantallaLogin.tsx` — acceso normal/mantenimiento, avisos y estado ocupado.
- `frontend/src/shell/auth/PantallaLogin.test.tsx` — paridad S01-UX-01…08 y modo mantenimiento.
- `frontend/src/shell/auth/CambioClaveObligatorio.tsx` — diálogo/panel, política, cambio y confirmación de salida.
- `frontend/src/shell/auth/CambioClaveObligatorio.test.tsx` — política, foco, Escape, errores y mutaciones únicas.
- `frontend/src/shell/auth/avisos.ts` — vocabulario seguro de timeout, inactividad y reset, más limpieza de query.
- `frontend/src/shell/auth/avisos.test.ts` — prioridad, copy y consumo único de parámetros.
- `public/css/auth-react.css` — composición responsive S01 en `@layer module`, solo con tokens.
- `src/Services/Auth/ForcedPasswordChangeService.php` — estado pendiente, promoción y cancelación segura.
- `src/Core/SpaHostRenderer.php` — sirve `public/app/index.html`, inyecta JSON seguro y respeta HEAD.
- `src/Controllers/Core/SpaHostController.php` — fallback GET canónico para `/` y `/login` después de retirar VIEW-01.
- `src/Controllers/Auth/MaintenanceLoginController.php` — host y POST ocultos sin llevar el secreto al bundle.
- `tests/unit/PasswordPolicyServiceTest.php` — cinco reglas exactas y compatibilidad del mensaje legacy.
- `tests/unit/ForcedPasswordChangeServiceTest.php` — transición pendiente/promoción/cancelación sin HTTP.
- `tests/test_auth_maintenance_contract.php` — 503 público, host oculto, rol permitido/denegado y secreto ausente del bundle.
- `tests/browser/login-react.spec.mjs` — comportamiento, a11y, temas y reflow con API controlada.
- `tests/browser/login-react.visual.mjs` — escenarios visuales aprobables de dark/light y viewports.

### Modify

- `frontend/index.html` — bootstrap dark-first y hoja `auth-react.css`.
- `frontend/src/lib/api/cliente.ts` y `cliente.test.ts` — errores tipados, JSON inválido, HTML inesperado y red.
- `frontend/src/lib/api/esquemas/sesion.ts` — unión discriminada por `state` y refinamientos.
- `frontend/src/shell/NavegacionLateral.tsx` — aceptar únicamente `SesionAutenticada`, sin cambiar su catálogo en S01.
- `frontend/src/shell/tema.ts`, `tema.test.ts`, `ConmutadorTema.tsx` — fallback oscuro y control preauth.
- `frontend/src/shell/useSesion.ts` — bootstrap canónico sin convertir error técnico en anonimato.
- `frontend/src/shell/rutas.tsx` y `rutas.test.tsx` — máquina de estados S01/T01 y runtime mantenimiento.
- `frontend/src/shell/PantallaLogin.tsx` y `PantallaLogin.test.tsx` — retirar después de mover su responsabilidad a `shell/auth/`.
- `src/Services/Auth/PasswordPolicyService.php` y `UserPasswordService.php` — errores de campo sin romper S02/S03.
- `src/Services/Auth/AuthenticationService.php` — completar/cancelar transición con regeneración y limpieza central.
- `src/Controllers/Api/AuthApiController.php` — `next`, 422 y endpoints de clave.
- `src/Controllers/Api/SessionApiController.php` — estados `anonymous`, `password_change_required`, `authenticated`.
- `src/Core/MaintenanceMode.php` — eximir solo assets del bundle, nunca `/app` ni rutas normales.
- `src/Core/SpaRouter.php` — rutas exactas + prefijos y gate GET/HEAD.
- `public/index.php` — rutas API, host oculto dedicado y corte exacto React.
- `tests/test_api_auth_contract.php`, `tests/test_api_session_contract.php` — contratos aprobados y fixture reconciliado.
- `tests/test_spa_frontera.php`, `tests/test_spa_frontera_http.php` — GET/HEAD React, POST/forgot/reset PHP y rollback.
- `tests/test_maintenance_asset_exemption.php` — assets SPA permitidos sin abrir la app.
- `tests/test_login_design_system_contract.mjs` — fuentes React, CSS layer/tokens y convivencia S02/S03.
- `docs/design-system/manifests/auth.json` — fuentes, estados, temas, layouts y escenarios S01.
- `public/app/index.html` y `public/app/assets/index-*.js` — artefactos generados exclusivamente con `npm run frontend:build`.

### Preserve until their own cut

- `views/auth/password-forgot.view.php`, `views/auth/password-reset.view.php`.
- `public/css/login-brand-unified.css`, `public/js/modules/aia_ui/auth_forms.js` y sus consumidores S02/S03.
- `GET/POST /password/forgot` y `GET/POST /password/reset`.
- El shell/sidebar, selector de proyecto, semana y navegación existentes fuera del incremento S01.

### Retire only after the rollback gate

- `views/auth/login.view.php` (VIEW-01).
- `LoginController::index()`, `login()`, `updatePassword()` y `cancelPasswordChange()`.
- `POST /login`, `POST /password/update` y `GET /login/cancelar`.
- jQuery/SweetAlert inline exclusivo de VIEW-01; no retirar los assets compartidos por S02/S03.

---

### Task 1: Tipar todos los errores del cliente HTTP

**Files:**
- Create: `frontend/src/lib/api/esquemas/error.ts`
- Modify: `frontend/src/lib/api/cliente.ts`
- Modify: `frontend/src/lib/api/cliente.test.ts`

**Interfaces:**
- Produces: `EsquemaErrorApi`, `type DetalleErrorApi`, `class ErrorApi`, `esErrorApi(causa): causa is ErrorApi`.
- Produces: `pedir<T>(ruta, esquema, opciones): Promise<T>` lanza siempre `ErrorApi` para red, HTTP no exitoso, JSON inválido o contrato Zod roto.
- Error fields: `endpoint`, `status`, `code`, `message`, `fieldErrors`, `redirect`, `correlationId`, `kind`.

- [ ] **Step 1: Write the failing client tests**

Añadir casos que exijan el cuerpo tipado de un `422`, el mensaje genérico de un `401`, un `500`
HTML sin insertar su cuerpo y un rechazo de red con `status=0`:

```ts
test('preserva errores de campo de un 422 sin pasarlos por el esquema de éxito', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(JSON.stringify({
    success: false,
    code: 'validation_error',
    message: 'Revisa los campos.',
    fieldErrors: { username: ['Escribe tu usuario.'] },
    redirect: null,
    correlationId: 'corr-1',
  }), { status: 422, headers: { 'Content-Type': 'application/json' } })));

  const error = await pedir('/api/auth/login', esquemaDePrueba).catch((causa) => causa);
  expect(error).toMatchObject({
    endpoint: '/api/auth/login', status: 422, code: 'validation_error',
    fieldErrors: { username: ['Escribe tu usuario.'] }, correlationId: 'corr-1',
    kind: 'http',
  });
});

test('un 500 HTML no filtra el cuerpo en el mensaje', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response('<b>stack secreto</b>', {
    status: 500,
    headers: { 'Content-Type': 'text/html' },
  })));
  const error = await pedir('/api/x', esquemaDePrueba).catch((causa) => causa as ErrorApi);
  expect(error.kind).toBe('unexpected_response');
  expect(error.message).not.toContain('stack secreto');
});
```

- [ ] **Step 2: Run the focused tests and confirm RED**

Run: `npm --prefix frontend test -- src/lib/api/cliente.test.ts`

Expected: FAIL porque `ErrorApi`, `fieldErrors` y `kind` todavía no existen.

- [ ] **Step 3: Implement the error schema and parser**

Crear el contrato exactamente así y hacer que `pedir()` lea una sola vez `respuesta.text()`,
parsee JSON de forma segura y nunca inserte el cuerpo crudo en la UI:

```ts
export const EsquemaErrorApi = z.object({
  success: z.literal(false).optional(),
  code: z.string().nullable().optional(),
  message: z.string().min(1).optional(),
  fieldErrors: z.record(z.string(), z.array(z.string())).optional(),
  redirect: z.string().startsWith('/').nullable().optional(),
  correlationId: z.string().nullable().optional(),
});

export type TipoErrorApi = 'network' | 'http' | 'unexpected_response' | 'contract';

export type DetalleErrorApi = {
  endpoint: string;
  status: number;
  code: string | null;
  fieldErrors: Record<string, string[]>;
  redirect: string | null;
  correlationId: string | null;
  kind: TipoErrorApi;
  message: string;
};

export class ErrorApi extends Error {
  constructor(public readonly detail: DetalleErrorApi) {
    super(detail.message);
    this.name = 'ErrorApi';
  }
  get endpoint() { return this.detail.endpoint; }
  get status() { return this.detail.status; }
  get code() { return this.detail.code; }
  get fieldErrors() { return this.detail.fieldErrors; }
  get redirect() { return this.detail.redirect; }
  get correlationId() { return this.detail.correlationId; }
  get kind() { return this.detail.kind; }
}

export function esErrorApi(causa: unknown): causa is ErrorApi {
  return causa instanceof ErrorApi;
}
```

`pedir()` usa un copy genérico por status cuando el cuerpo no trae `message`; envuelve el fallo de
`fetch` como `network`, un no-2xx con error válido como `http`, un
no-2xx no JSON como `unexpected_response`, y un 2xx que no pasa Zod como `contract`.

- [ ] **Step 4: Run the client suite and typecheck**

```bash
npm --prefix frontend test -- src/lib/api/cliente.test.ts
npm --prefix frontend run typecheck
```

Expected: todos los casos de `cliente.test.ts` PASS y TypeScript RC 0.

- [ ] **Step 5: Commit the transport boundary**

```bash
git add frontend/src/lib/api/cliente.ts frontend/src/lib/api/cliente.test.ts frontend/src/lib/api/esquemas/error.ts
git commit -m "feat(frontend): tipar errores del cliente HTTP"
```

---

### Task 2: Definir los contratos Zod y el gateway de autenticación

**Files:**
- Create: `frontend/src/lib/api/esquemas/auth.ts`
- Create: `frontend/src/lib/api/esquemas/auth.test.ts`
- Create: `frontend/src/lib/api/auth.ts`
- Create: `frontend/src/lib/api/frontera.test.ts`
- Test: `frontend/src/lib/api/cliente.test.ts`

**Interfaces:**
- `iniciarSesion({username,password}, csrfToken): Promise<RespuestaLogin>`.
- `cambiarClave({password,confirmation}, csrfToken): Promise<RespuestaCambioClave>`.
- `cancelarCambioClave(csrfToken): Promise<RespuestaCancelacionClave>`.
- `RespuestaLogin.next` es exactamente `'projects' | 'password_change'`.

- [ ] **Step 1: Write failing schema and boundary tests**

```ts
test('login exitoso exige next y no acepta mustChangePassword legacy', () => {
  expect(EsquemaRespuestaLogin.safeParse({ success: true, next: 'projects', message: null }).success).toBe(true);
  expect(EsquemaRespuestaLogin.safeParse({ success: true, mustChangePassword: false, message: null }).success).toBe(false);
});

test('ningún archivo productivo salvo cliente.ts invoca fetch', () => {
  const fuentes = import.meta.glob('../../**/*.{ts,tsx}', {
    query: '?raw', import: 'default', eager: true,
  }) as Record<string, string>;
  for (const [ruta, codigo] of Object.entries(fuentes)) {
    if (ruta.endsWith('/cliente.ts') || ruta.includes('.test.')) continue;
    expect(codigo, ruta).not.toMatch(/\bfetch\s*\(/);
  }
});
```

- [ ] **Step 2: Run the focused tests and confirm RED**

Run: `npm --prefix frontend test -- src/lib/api/frontera.test.ts src/lib/api/esquemas/auth.test.ts`

Expected: FAIL porque `auth.ts`, `esquemas/auth.ts` y `esquemas/auth.test.ts` todavía no existen.

- [ ] **Step 3: Implement schemas and gateway with inferred types**

```ts
export const EsquemaSolicitudLogin = z.object({
  username: z.string().trim().min(1),
  password: z.string().min(1),
});
export const EsquemaRespuestaLogin = z.object({
  success: z.literal(true),
  next: z.enum(['projects', 'password_change']),
  message: z.null(),
});
export const EsquemaSolicitudCambioClave = z.object({
  password: z.string(),
  confirmation: z.string(),
});
export const EsquemaRespuestaCambioClave = z.object({
  success: z.literal(true),
  next: z.literal('projects'),
});
export const EsquemaRespuestaCancelacionClave = z.object({
  success: z.literal(true),
  next: z.literal('login'),
});
export type SolicitudLogin = z.infer<typeof EsquemaSolicitudLogin>;
export type RespuestaLogin = z.infer<typeof EsquemaRespuestaLogin>;
export type SolicitudCambioClave = z.infer<typeof EsquemaSolicitudCambioClave>;
export type RespuestaCambioClave = z.infer<typeof EsquemaRespuestaCambioClave>;
export type RespuestaCancelacionClave = z.infer<typeof EsquemaRespuestaCancelacionClave>;
```

En `auth.ts`, cada función llama `pedir()` con JSON, `POST` y `X-CSRF-Token`; ninguna reintenta una
mutación automáticamente. Exportar tipos solo mediante `z.infer` desde `esquemas/auth.ts`.

```ts
export async function iniciarSesion(solicitud: SolicitudLogin, csrfToken: string): Promise<RespuestaLogin> {
  const body = EsquemaSolicitudLogin.parse(solicitud);
  return pedir('/api/auth/login', EsquemaRespuestaLogin, {
    method: 'POST',
    headers: { 'X-CSRF-Token': csrfToken },
    body: JSON.stringify(body),
  });
}

export async function cambiarClave(solicitud: SolicitudCambioClave, csrfToken: string): Promise<RespuestaCambioClave> {
  const body = EsquemaSolicitudCambioClave.parse(solicitud);
  return pedir('/api/auth/password/change', EsquemaRespuestaCambioClave, {
    method: 'POST', headers: { 'X-CSRF-Token': csrfToken }, body: JSON.stringify(body),
  });
}

export async function cancelarCambioClave(csrfToken: string): Promise<RespuestaCancelacionClave> {
  return pedir('/api/auth/password/cancel', EsquemaRespuestaCancelacionClave, {
    method: 'POST', headers: { 'X-CSRF-Token': csrfToken }, body: JSON.stringify({}),
  });
}
```

- [ ] **Step 4: Run schemas, boundary and complete frontend tests**

```bash
npm --prefix frontend test -- src/lib/api
npm --prefix frontend run typecheck
```

Expected: schemas válidos, formas legacy rechazadas, guard de `fetch` verde y TypeScript RC 0.

- [ ] **Step 5: Commit the auth gateway**

```bash
git add frontend/src/lib/api/auth.ts frontend/src/lib/api/esquemas/auth.ts frontend/src/lib/api/esquemas/auth.test.ts frontend/src/lib/api/frontera.test.ts
git commit -m "feat(frontend): declarar contratos de autenticacion"
```

---

### Task 3: Hacer explícitas las cinco reglas de contraseña en PHP

**Files:**
- Create: `tests/unit/PasswordPolicyServiceTest.php`
- Modify: `src/Services/Auth/PasswordPolicyService.php`
- Modify: `src/Services/Auth/UserPasswordService.php`
- Test: `tests/test_password_reset_resultados.php`

**Interfaces:**
- `PasswordPolicyService::validateFields(string $password, string $confirm, ?string $currentHash): array<string,list<string>>`.
- `PasswordPolicyService::validate(...)` conserva el primer mensaje legacy para S02/S03.
- `UserPasswordService::changePasswordForUsername(...)` añade `fieldErrors` sin quitar `success` ni `message`.

- [ ] **Step 1: Write the failing pure policy tests**

```php
#[Group('puro')]
final class PasswordPolicyServiceTest extends TestCase
{
    #[DataProvider('invalidPasswords')]
    public function testReportsTheExactField(string $password, string $confirmation, string $field): void
    {
        $errors = (new PasswordPolicyService())->validateFields($password, $confirmation, null);
        self::assertArrayHasKey($field, $errors);
    }

    public static function invalidPasswords(): array
    {
        return [
            'minimum' => ['Aa!', 'Aa!', 'password'],
            'uppercase' => ['abcdef!', 'abcdef!', 'password'],
            'special' => ['Abcdef1', 'Abcdef1', 'password'],
            'confirmation' => ['Abcdef!', 'Abcdef?', 'confirmation'],
        ];
    }

    public function testRejectsTheCurrentPasswordForModernAndSha512Hashes(): void
    {
        $policy = new PasswordPolicyService();
        self::assertArrayHasKey('password', $policy->validateFields('Nueva!', 'Nueva!', password_hash('Nueva!', PASSWORD_DEFAULT)));
        self::assertArrayHasKey('password', $policy->validateFields('Nueva!', 'Nueva!', hash('sha512', 'Nueva!')));
    }
}
```

- [ ] **Step 2: Run the pure unit test and confirm RED**

Run: `LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app vendor/bin/phpunit tests/unit/PasswordPolicyServiceTest.php`

Expected: FAIL porque `validateFields()` no existe.

- [ ] **Step 3: Implement structured policy errors with legacy compatibility**

`validateFields()` acumula en orden longitud, mayúscula, especial, confirmación e igualdad con la
clave previa. `validate()` devuelve el primer mensaje de `password`, luego `confirmation`, o
`null`. `UserPasswordService` retorna:

```php
return [
    'success' => false,
    'message' => $firstMessage,
    'fieldErrors' => $fieldErrors,
];
```

En éxito retorna `fieldErrors => []`. No concatenar excepciones; conservar el `error_log` sin
secreto ni payload.

- [ ] **Step 4: Run policy and password-reset compatibility tests**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app vendor/bin/phpunit tests/unit/PasswordPolicyServiceTest.php
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_password_reset_resultados.php
```

Expected: PHPUnit PASS; el contrato de reset conserva sus resultados y reconcilia sus filas.

- [ ] **Step 5: Commit the password policy**

```bash
git add src/Services/Auth/PasswordPolicyService.php src/Services/Auth/UserPasswordService.php tests/unit/PasswordPolicyServiceTest.php
git commit -m "refactor(auth): exponer errores de politica de clave"
```

---

### Task 4: Centralizar la transición de cambio obligatorio

**Files:**
- Create: `src/Services/Auth/ForcedPasswordChangeService.php`
- Create: `tests/unit/ForcedPasswordChangeServiceTest.php`
- Modify: `src/Services/Auth/AuthenticationService.php`

**Interfaces:**
- `ForcedPasswordChangeService::isPending(): bool` exige `must_change_password`, `usuario_temp` y ausencia de `usuario`.
- `change(string $password, string $confirmation): array{success:bool,message:?string,fieldErrors:array<string,list<string>>}`.
- `cancel(): bool` destruye solo una sesión pendiente y es no-op sobre una sesión completa.
- `AuthenticationService::completePasswordChange(string $username): void` regenera ID, promueve identidad y limpia proyecto.

- [ ] **Step 1: Write failing isolated transition tests**

Crear un PHPUnit `#[Group('puro')]` que usa mocks de los dos colaboradores y reinicia `$_SESSION`
en `setUp()`/`tearDown()`:

```php
public function testChangePromotesOnlyAfterPasswordWasPersisted(): void
{
    $_SESSION = ['usuario_temp' => 'fixture', 'must_change_password' => true];
    $passwords = $this->createMock(UserPasswordService::class);
    $authentication = $this->createMock(AuthenticationService::class);
    $passwords->expects(self::once())->method('changePasswordForUsername')
        ->with('fixture', 'Nueva!', 'Nueva!', true)
        ->willReturn(['success' => true, 'message' => null, 'fieldErrors' => []]);
    $authentication->expects(self::once())->method('completePasswordChange')->with('fixture');

    $result = (new ForcedPasswordChangeService($passwords, $authentication))
        ->change('Nueva!', 'Nueva!');
    self::assertTrue($result['success']);
}

public function testCancelDoesNotDestroyACompleteSession(): void
{
    $_SESSION = ['usuario' => 'autenticado', 'project_id' => 73];
    $service = new ForcedPasswordChangeService(
        $this->createStub(UserPasswordService::class),
        $this->createStub(AuthenticationService::class),
    );
    self::assertFalse($service->cancel());
    self::assertSame('autenticado', $_SESSION['usuario']);
    self::assertSame(73, $_SESSION['project_id']);
}
```

Añadir casos de no-pendiente, fallo de persistencia sin promoción, cancelación pendiente y
`AuthenticationService::completePasswordChange()` limpiando proyecto/flags y promoviendo usuario.

- [ ] **Step 2: Run the isolated test and confirm RED**

Run: `LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app vendor/bin/phpunit tests/unit/ForcedPasswordChangeServiceTest.php`

Expected: FAIL porque el servicio y `completePasswordChange()` no existen; no se abre HTTP ni DB.

- [ ] **Step 3: Implement the pending-session service**

```php
final class ForcedPasswordChangeService
{
    public function __construct(
        private UserPasswordService $passwords,
        private AuthenticationService $authentication,
    ) {}

    public function isPending(): bool
    {
        return !empty($_SESSION['must_change_password'])
            && is_string($_SESSION['usuario_temp'] ?? null)
            && ($_SESSION['usuario_temp'] ?? '') !== ''
            && empty($_SESSION['usuario']);
    }

    public function change(string $password, string $confirmation): array
    {
        if (!$this->isPending()) {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'fieldErrors' => []];
        }
        $username = (string) $_SESSION['usuario_temp'];
        $result = $this->passwords->changePasswordForUsername($username, $password, $confirmation, true);
        if ($result['success']) $this->authentication->completePasswordChange($username);
        return $result;
    }
}
```

`cancel()` vacía/destruye la sesión únicamente si `isPending()` y devuelve si hubo cancelación; el
controlador captura el username del estado servidor antes de llamar al servicio y nunca lo incluye
en la respuesta. `completePasswordChange()` exige que el argumento coincida con `usuario_temp`,
llama la regeneración privada, asigna `usuario`, elimina `usuario_temp`/`must_change_password` y
llama `clearProjectContext()`; no elimina `maintenance_bypass`. `beginPasswordChange()` elimina
identidad/proyecto previos antes de crear la sesión temporal.

```php
public function completePasswordChange(string $username): void
{
    $pending = (string) ($_SESSION['usuario_temp'] ?? '');
    if ($pending === '' || !hash_equals($pending, $username)) {
        throw new \LogicException('La sesión no corresponde al cambio pendiente.');
    }
    $this->regenerateSessionId();
    $_SESSION['usuario'] = $username;
    unset($_SESSION['usuario_temp'], $_SESSION['must_change_password']);
    $this->clearProjectContext();
}
```

- [ ] **Step 4: Run transition and policy unit tests**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app vendor/bin/phpunit tests/unit/ForcedPasswordChangeServiceTest.php
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app vendor/bin/phpunit tests/unit/PasswordPolicyServiceTest.php
```

Expected: ambos PASS; promoción solo después de persistir, cancelación pendiente aislada y sesión
completa intacta.

- [ ] **Step 5: Commit the session transition**

```bash
git add src/Services/Auth/ForcedPasswordChangeService.php src/Services/Auth/AuthenticationService.php tests/unit/ForcedPasswordChangeServiceTest.php
git commit -m "feat(auth): centralizar cambio obligatorio de clave"
```

---

### Task 5: Publicar los tres contratos HTTP S01

**Files:**
- Modify: `src/Controllers/Api/AuthApiController.php`
- Modify: `public/index.php`
- Modify: `tests/test_api_auth_contract.php`

**Interfaces:**
- `POST /api/auth/login`: éxito `{success:true,next:'projects'|'password_change',message:null}`.
- `POST /api/auth/password/change`: éxito `{success:true,next:'projects'}`.
- `POST /api/auth/password/cancel`: éxito idempotente `{success:true,next:'login'}`.
- Errores: `{success:false,code,message,fieldErrors,redirect:null,correlationId:null}`.
- Codes exactos: `csrf_invalid` (403), `validation_error` (422), `invalid_credentials` (401), `password_change_not_pending` (401) e `internal_error` (5xx).

- [ ] **Step 1: Complete the failing endpoint matrix**

En `tests/test_api_auth_contract.php` cubrir exactamente:

```php
comprobar('forma inválida responde 422',
    $vacio['codigo'] === 422
    && ($vacio['json']['code'] ?? null) === 'validation_error'
    && isset($vacio['json']['fieldErrors']['username'])
    && isset($vacio['json']['fieldErrors']['password']));
comprobar('inactivo e inexistente no enumeran',
    $inactivo['codigo'] === 401
    && $inactivo['json'] === $inexistente['json']);
comprobar('cambio sin pendiente responde 401',
    $sinPendiente['codigo'] === 401
    && ($sinPendiente['json']['code'] ?? null) === 'password_change_not_pending');
```

El fixture crea tres usernames aleatorios: activo normal, activo con
`force_password_change=1` e inactivo. Guarda hashes en DB, mantiene las claves solo en variables de
proceso, usa cookie jars separados, registra los IDs creados y borra exactamente esas filas en
`finally`. Captura conteos antes/después y hace fallar el test si la reconciliación no es exacta.
Comprobar además que completar rota el ID/cookie de sesión, que el bootstrap no expone
`usuario_temp` y que cancelar una sesión completa no la destruye.

Añadir ausencia/token CSRF incorrecto para los tres endpoints, las cinco reglas como 422, éxito y
cancelación idempotente.

- [ ] **Step 2: Run the HTTP contract and confirm RED**

Run: `docker compose exec -T app php tests/test_api_auth_contract.php`

Expected: FAIL por rutas inexistentes, `mustChangePassword` legacy y status 400 actual.

- [ ] **Step 3: Implement controller methods and public routes**

Cambiar `login()` para responder 422 en forma inválida, 401 genérico idéntico para ausente/mala/inactiva
y `next` en éxito. Añadir `changePassword()` y `cancelPasswordChange()`; ambos validan CSRF antes de
leer estado. Registrar:

```php
$router->post('/api/auth/password/change', [AuthApiController::class, 'changePassword']);
$router->post('/api/auth/password/cancel', [AuthApiController::class, 'cancelPasswordChange']);
```

En `changePassword()`, `fieldErrors !== []` se serializa como 422 `validation_error`; un fallo de
persistencia con `fieldErrors === []` se serializa como 500 `internal_error` y mensaje genérico. La
excepción se registra sin username, contraseña, confirmación, cookie ni CSRF.

Añadir ambas rutas a `$publicRoutes` porque una sesión pendiente todavía no es autenticación
completa. El controlador jamás acepta `username`, `project_id`, `db`, prefijo ni rol en los cuerpos
de cambio/cancelación.

- [ ] **Step 4: Run auth contract, lint and forbidden-field scan**

```bash
docker compose exec -T app php tests/test_api_auth_contract.php
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php -l src/Controllers/Api/AuthApiController.php
rg -n "project_id|Base_de_Datos|dbPrefix|permiso" src/Controllers/Api/AuthApiController.php src/Services/Auth/ForcedPasswordChangeService.php
```

Expected: contrato PASS, lint RC 0 y el scan no muestra campos cliente/autorización dentro de los
nuevos métodos.

- [ ] **Step 5: Commit the HTTP auth contracts**

```bash
git add public/index.php src/Controllers/Api/AuthApiController.php tests/test_api_auth_contract.php
git commit -m "feat(api): completar contratos de acceso React"
```

---

### Task 6: Convertir `/api/session` en la máquina de estados preproyecto

**Files:**
- Modify: `src/Controllers/Api/SessionApiController.php`
- Modify: `tests/test_api_session_contract.php`
- Modify: `frontend/src/lib/api/esquemas/sesion.ts`
- Create: `frontend/src/lib/api/esquemas/sesion.test.ts`
- Modify: `frontend/src/shell/NavegacionLateral.tsx`

**Interfaces:**
- `Sesion` es unión discriminada por `state`.
- `SesionAutenticada = Extract<Sesion,{state:'authenticated'}>` es el único tipo aceptado por `NavegacionLateral`.
- `anonymous`: `authenticated=false`, razón segura, identidad/proyecto nulos.
- `password_change_required`: `authenticated=false`, `reason=null`, sin identidad/proyecto/navegación.
- `authenticated`: `authenticated=true`, `reason=null`, usuario presente y proyecto nullable.

- [ ] **Step 1: Write failing PHP and Zod state-matrix tests**

```ts
const base = { capabilities: {}, navigation: { bi: null }, csrfToken };
expect(EsquemaSesion.safeParse({
  ...base, state: 'password_change_required', authenticated: false,
  reason: null, user: null, project: null,
}).success).toBe(true);
expect(EsquemaSesion.safeParse({
  ...base, state: 'password_change_required', authenticated: false,
  reason: null, user: { username: 'filtrado', displayName: '', role: '' }, project: null,
}).success).toBe(false);
expect(EsquemaSesion.safeParse({
  ...base, state: 'authenticated', authenticated: true,
  reason: null, user: null, project: null,
}).success).toBe(false);
```

En PHP crear una sesión temporal en proceso y comprobar `state`, ausencia de `usuario_temp`,
`user=null`, `project=null`, capabilities vacío y CSRF válido.

- [ ] **Step 2: Run both contracts and confirm RED**

```bash
npm --prefix frontend test -- src/lib/api/esquemas/sesion.test.ts
docker compose exec -T app php tests/test_api_session_contract.php
```

Expected: ambos FAIL porque falta `state` y el bootstrap presenta pendiente como `missing_session`.

- [ ] **Step 3: Implement the server and discriminated union**

Antes de serializar `SessionMiddleware::requestFailureReason()`, `SessionApiController::show()`
comprueba `ForcedPasswordChangeService::isPending()` y responde el estado pendiente sin identidad.
El Zod usa `z.discriminatedUnion('state', [...])`; mantiene temporalmente `navigation.bi` para no
implementar sidebar T01 en S01.

El vocabulario de razón anónima es:

```ts
export const EsquemaRazonAnonima = z.enum([
  'missing_session', 'timeout', 'inactive', 'stale_session', 'session_unverified',
]);
export type Sesion = z.infer<typeof EsquemaSesion>;
export type SesionAutenticada = Extract<Sesion, { state: 'authenticated' }>;
```

Cambiar solo el tipo de prop/sidebar a `SesionAutenticada`; `ocultasPorRol` permanece hasta el
incremento completo de T01 y no se reabre en S01.

- [ ] **Step 4: Run session contracts and existing shell tests**

```bash
npm --prefix frontend test -- src/lib/api/esquemas/sesion.test.ts src/shell/rutas.test.tsx src/shell/NavegacionLateral.test.tsx src/shell/SelectorProyecto.test.tsx
docker compose exec -T app php tests/test_api_session_contract.php
```

Expected: estado pendiente seguro, estados existentes adaptados y ningún consumidor del shell roto.

- [ ] **Step 5: Commit the session state machine**

```bash
git add src/Controllers/Api/SessionApiController.php tests/test_api_session_contract.php frontend/src/lib/api/esquemas/sesion.ts frontend/src/lib/api/esquemas/sesion.test.ts frontend/src/shell/NavegacionLateral.tsx
git commit -m "feat(session): declarar estados de arranque del acceso"
```

---

### Task 7: Hacer oscuro el fallback y exponer tema antes del login

**Files:**
- Modify: `frontend/index.html`
- Modify: `frontend/src/shell/tema.ts`
- Modify: `frontend/src/shell/tema.test.ts`
- Modify: `frontend/src/shell/ConmutadorTema.tsx`

**Interfaces:**
- `leerTemaGuardado(): 'oscuro' | 'claro'` cae a `oscuro` si falta/corrompe/bloquea storage.
- `aplicarTema()` persiste `dark|light` y sincroniza atributo/clase.
- El script inline aplica dark antes de la primera hoja CSS.

- [ ] **Step 1: Flip the theme tests to the approved contract**

Cambiar las aserciones D12 heredadas:

```ts
test('el tema de entrada es oscuro', () => {
  expect(leerTemaGuardado()).toBe('oscuro');
});
test('un valor corrupto cae a oscuro', () => {
  localStorage.setItem('aia-theme', 'fucsia');
  expect(leerTemaGuardado()).toBe('oscuro');
});
test('el bootstrap escribe dark antes de leer storage y antes de CSS', () => {
  expect(bootstrap).toContain("setAttribute('data-aia-theme', 'dark')");
  expect(indiceBootstrap).toBeLessThan(indiceTokens);
});
```

- [ ] **Step 2: Run theme tests and confirm RED**

Run: `npm --prefix frontend test -- src/shell/tema.test.ts`

Expected: FAIL porque el código y HTML todavía caen a claro.

- [ ] **Step 3: Implement dark-first bootstrap without flash**

En `index.html` escribir `data-aia-theme=dark` y `aia-theme-dark` antes del `try`; dentro del
`try` cambiar a light solo si storage vale exactamente `light`. En `tema.ts`, cualquier valor que
no sea `light` retorna `oscuro`. Mantener el botón con `aria-pressed`, nombre de destino y texto de
estado.

```ts
export function leerTemaGuardado(): Tema {
  try {
    return localStorage.getItem(CLAVE_TEMA) === 'light' ? 'claro' : 'oscuro';
  } catch {
    return 'oscuro';
  }
}
```

```html
<script>
  document.documentElement.setAttribute('data-aia-theme', 'dark');
  document.documentElement.classList.add('aia-theme-dark');
  try {
    if (localStorage.getItem('aia-theme') === 'light') {
      document.documentElement.setAttribute('data-aia-theme', 'light');
      document.documentElement.classList.remove('aia-theme-dark');
    }
  } catch (error) {}
</script>
```

- [ ] **Step 4: Run theme suite and build**

```bash
npm --prefix frontend test -- src/shell/tema.test.ts
npm --prefix frontend run build
```

Expected: tests PASS; `public/app/index.html` contiene bootstrap dark antes de links CSS.

- [ ] **Step 5: Commit source and generated bundle**

```bash
git add frontend/index.html frontend/src/shell/tema.ts frontend/src/shell/tema.test.ts frontend/src/shell/ConmutadorTema.tsx public/app
git commit -m "feat(theme): iniciar el shell React en oscuro"
```

---

### Task 8: Construir el login React normal con avisos consumibles una vez

**Files:**
- Create: `frontend/src/shell/auth/MarcoAcceso.tsx`
- Create: `frontend/src/shell/auth/CampoClave.tsx`
- Create: `frontend/src/shell/auth/avisos.ts`
- Create: `frontend/src/shell/auth/avisos.test.ts`
- Create: `frontend/src/shell/auth/PantallaLogin.tsx`
- Create: `frontend/src/shell/auth/PantallaLogin.test.tsx`
- Modify: `frontend/src/shell/PantallaLogin.tsx`
- Modify: `frontend/src/shell/PantallaLogin.test.tsx`

**Interfaces:**
- `resolverAvisoAcceso(reason, search): AvisoAcceso | null` usa vocabulario fijo.
- `limpiarParametrosAviso(url): string` elimina solo `timeout`, `inactive`, `reset`.
- `MarcoAcceso({titulo,children})` contiene skip link, marca, `ConmutadorTema`, un `main`, el único `h1` y footer; envuelve login y cambio obligatorio.
- `PantallaLogin` recibe `csrfToken`, `aviso`, `alResolver(next)`, `alRevalidar()` y modo normal/mantenimiento.
- `CampoClave` recibe `id`, `name`, `label`, `value`, `onChange`, `autoComplete`, error y disabled.

Prioridad/copy exactos: razón servidor `timeout` → “Su sesión expiró por inactividad. Ingresa de
nuevo.”; `inactive` → “Tu cuenta está inactiva. Contacta al administrador.”;
`stale_session|session_unverified` → “Tu sesión ya no es válida. Ingresa de nuevo.”; luego query
`reset=1` → “Tu contraseña fue restablecida correctamente. Ya puedes iniciar sesión.”; finalmente
queries legacy `timeout=1|inactive=1`. `missing_session` no muestra aviso.

- [ ] **Step 1: Write failing behavior tests S01-UX-01…08**

Cubrir marca/footer, atributos de usuario, toggle, Enter/click único, busy, username preservado,
password limpio, 401 genérico, 403 recuperable sin reenvío, 422 por campo, red/5xx recuperable,
enlace S02 y tema. Ejemplo:

```tsx
const csrfToken = '0'.repeat(64);
const props = {
  csrfToken,
  aviso: null,
  alResolver: vi.fn().mockResolvedValue(undefined),
  alRevalidar: vi.fn().mockResolvedValue(undefined),
  modo: { tipo: 'normal' as const },
};
async function enviarFormularioValido(user: ReturnType<typeof userEvent.setup>) {
  await user.type(screen.getByLabelText('Usuario'), 'fixture');
  await user.type(screen.getByLabelText('Contraseña'), 'Clave!');
  await user.click(screen.getByRole('button', { name: 'Entrar' }));
}

test('toggle y doble click producen una sola mutación', async () => {
  const user = userEvent.setup();
  vi.mocked(iniciarSesion).mockResolvedValue({ success: true, next: 'projects', message: null });
  render(<PantallaLogin {...props} />);
  expect(screen.getByLabelText('Usuario')).toHaveAttribute('autocapitalize', 'none');
  await user.click(screen.getByRole('button', { name: 'Mostrar contraseña' }));
  expect(screen.getByLabelText('Contraseña')).toHaveAttribute('type', 'text');
  await user.type(screen.getByLabelText('Usuario'), 'fixture');
  await user.type(screen.getByLabelText('Contraseña'), 'Clave!');
  await user.dblClick(screen.getByRole('button', { name: 'Entrar' }));
  expect(iniciarSesion).toHaveBeenCalledOnce();
});

test('un 403 revalida sesión sin reenviar la contraseña', async () => {
  const user = userEvent.setup();
  const alRevalidar = vi.fn().mockResolvedValue(undefined);
  vi.mocked(iniciarSesion).mockRejectedValue(new ErrorApi({ endpoint: '/api/auth/login', status: 403,
    code: 'csrf_invalid', fieldErrors: {}, redirect: null, correlationId: null,
    kind: 'http', message: 'Solicitud no permitida.' }));
  render(<PantallaLogin {...props} alRevalidar={alRevalidar} />);
  await enviarFormularioValido(user);
  await user.click(await screen.findByRole('button', { name: 'Actualizar sesión' }));
  expect(alRevalidar).toHaveBeenCalledOnce();
  expect(iniciarSesion).toHaveBeenCalledOnce();
});
```

Mockear `frontend/src/lib/api/auth.ts`, no `fetch`.

- [ ] **Step 2: Run auth component tests and confirm RED**

Run: `npm --prefix frontend test -- src/shell/auth`

Expected: FAIL porque los componentes y avisos no existen.

- [ ] **Step 3: Implement the accessible form and safe notices**

`MarcoAcceso` renderiza `main`, skip link, panel de contexto, `ConmutadorTema`, un solo `h1` y
footer; `PantallaLogin` aporta formulario/alertas. Para modo normal previene submit y llama:

```ts
const response = await iniciarSesion({ username: usuario, password: clave }, csrfToken);
await alResolver(response.next);
```

El `<form aria-busy={enviando}>` deshabilita ambos campos y submit mientras espera; el botón cambia
exactamente de `Entrar` a `Entrando…` sin cambiar labels ni desmontar el formulario.

En `ErrorApi` 401 usa copy fijo; 403 muestra acción `Actualizar sesión` que llama `alRevalidar` y no
reenvía credenciales; 422 asigna `fieldErrors`; red/5xx/contract usa copy técnico seguro.
Toda salida de error limpia secretos, conserva usuario y enfoca el primer campo inválido o resumen.
Mover el consumidor de `shell/PantallaLogin` a `shell/auth/PantallaLogin` y eliminar los archivos
viejos solo cuando imports/tests ya apunten al nuevo lugar.

- [ ] **Step 4: Run component, API boundary and type tests**

```bash
npm --prefix frontend test -- src/shell/auth src/lib/api/frontera.test.ts
npm --prefix frontend run typecheck
```

Expected: S01-UX-01…08 PASS, cero `fetch` fuera del cliente y TypeScript RC 0.

- [ ] **Step 5: Commit the normal login**

```bash
git add frontend/src/shell/auth/MarcoAcceso.tsx frontend/src/shell/auth/CampoClave.tsx frontend/src/shell/auth/avisos.ts frontend/src/shell/auth/avisos.test.ts frontend/src/shell/auth/PantallaLogin.tsx frontend/src/shell/auth/PantallaLogin.test.tsx frontend/src/shell/PantallaLogin.tsx frontend/src/shell/PantallaLogin.test.tsx
git commit -m "feat(auth): migrar formulario de acceso a React"
```

---

### Task 9: Construir el cambio obligatorio y la cancelación confirmada

**Files:**
- Create: `frontend/src/shell/auth/CambioClaveObligatorio.tsx`
- Create: `frontend/src/shell/auth/CambioClaveObligatorio.test.tsx`
- Modify: `frontend/src/shell/rutas.tsx`
- Modify: `frontend/src/shell/rutas.test.tsx`

**Interfaces:**
- `CambioClaveObligatorio({csrfToken,alCompletar,alSalir})` controla cambio/cancelación dentro de `MarcoAcceso`, con tema disponible.
- `Escape` y acción Salir cambian a `confirmandoSalida`; no llaman API todavía.
- `Actualizar y continuar` y `Confirmar salida` son mutaciones de un solo disparo.

- [ ] **Step 1: Write failing modal/panel tests**

Cubrir los cinco requisitos visibles, toggles independientes, `aria-describedby`, error servidor,
busy, backdrop inocuo, Escape con confirmación, Tab contenido, foco inicial, tema y cancelación. Ejemplo:

```tsx
fireEvent.keyDown(screen.getByRole('dialog'), { key: 'Escape' });
expect(cancelarCambioClave).not.toHaveBeenCalled();
expect(screen.getByRole('heading', { name: '¿Salir del cambio de contraseña?' })).toBeVisible();
await user.click(screen.getByRole('button', { name: 'Confirmar salida' }));
expect(cancelarCambioClave).toHaveBeenCalledOnce();
```

En `rutas.test.tsx`, bootstrap pendiente debe mostrar este panel sin login, selector, sidebar ni
identidad.

- [ ] **Step 2: Run forced-change and router tests and confirm RED**

Run: `npm --prefix frontend test -- src/shell/auth/CambioClaveObligatorio.test.tsx src/shell/rutas.test.tsx`

Expected: FAIL porque el estado pendiente todavía no tiene salida React.

- [ ] **Step 3: Implement native dialog semantics and router branch**

Usar `<dialog>` con `showModal()`; prevenir el evento `cancel`, activar confirmación y mantener el
modal abierto. En móvil CSS lo convertirá en panel de página, pero conserva `role=dialog` y
`aria-modal`. El submit llama `cambiarClave`; la salida confirmada llama `cancelarCambioClave`.

```tsx
useEffect(() => {
  const dialogo = dialogoRef.current;
  if (dialogo && !dialogo.open) dialogo.showModal();
  return () => { if (dialogo?.open) dialogo.close(); };
}, []);

<dialog ref={dialogoRef} onCancel={(evento) => {
  evento.preventDefault();
  if (!enviando) setConfirmandoSalida(true);
}} aria-labelledby="titulo-cambio-clave">
  {confirmandoSalida ? <ConfirmacionSalida /> : <FormularioCambioClave />}
</dialog>
```

En `Rutas`, evaluar `sesion.state === 'password_change_required'` antes de proyecto/shell. Tras
cambio, `recargar()`; tras cancelación normal, recargar bootstrap anónimo y enfocar usuario.

- [ ] **Step 4: Run forced-change, router and a11y-focused tests**

```bash
npm --prefix frontend test -- src/shell/auth/CambioClaveObligatorio.test.tsx src/shell/rutas.test.tsx
npm --prefix frontend run typecheck
```

Expected: política, foco, confirmación, single-submit y estados de router PASS.

- [ ] **Step 5: Commit forced password change UI**

```bash
git add frontend/src/shell/auth/MarcoAcceso.tsx frontend/src/shell/auth/CambioClaveObligatorio.tsx frontend/src/shell/auth/CambioClaveObligatorio.test.tsx frontend/src/shell/rutas.tsx frontend/src/shell/rutas.test.tsx
git commit -m "feat(auth): completar cambio obligatorio en React"
```

---

### Task 10: Integrar bootstrap, avisos y recuperación T01 en las rutas React

**Files:**
- Modify: `frontend/src/shell/useSesion.ts`
- Modify: `frontend/src/shell/rutas.tsx`
- Modify: `frontend/src/shell/rutas.test.tsx`
- Modify: `frontend/src/App.tsx`

**Interfaces:**
- Bootstrap técnico fallido produce `recoverable_error`, nunca login por descarte.
- `alResolver('password_change')` y `alResolver('projects')` recargan `/api/session`; no navegan a PHP.
- Avisos se derivan de sesión/query y se eliminan con `history.replaceState` sin recarga.

- [ ] **Step 1: Add failing startup and notice tests**

Añadir casos para loading sin flash, red/5xx con reintento, contrato roto, timeout/inactive/reset
mostrado una vez, query stale eliminado y éxito de login seguido por fallo de bootstrap sin segundo
POST de credenciales.

```tsx
expect(screen.queryByRole('heading', { name: 'Entrar' })).not.toBeInTheDocument();
expect(await screen.findByRole('alert')).toHaveTextContent('No pudimos conectar');
expect(iniciarSesion).toHaveBeenCalledOnce();
expect(pedir).toHaveBeenCalledTimes(2);
```

- [ ] **Step 2: Run router tests and confirm RED**

Run: `npm --prefix frontend test -- src/shell/rutas.test.tsx src/shell/auth/avisos.test.ts`

Expected: FAIL en estados/avisos todavía no integrados.

- [ ] **Step 3: Implement ordered startup rendering**

Separar `RutasAplicacion` para que hooks de sesión solo se monten en runtime normal. Orden exacto:
loading → recoverable error → password change → anonymous login → authenticated without project →
shell existente. `useEffect` consume query segura y llama `replaceState` conservando otros parámetros
y hash. Nunca reenvía automáticamente una contraseña después de 403 o fallo de bootstrap.

```tsx
if (cargando) return <p role="status">Cargando…</p>;
if (error) return (
  <section role="alert">
    <p>No pudimos conectar con la aplicación.</p>
    <button type="button" onClick={() => void recargar()}>Reintentar</button>
  </section>
);
if (sesion?.state === 'password_change_required') {
  return (
    <MarcoAcceso titulo="Cambio de contraseña obligatorio">
      <CambioClaveObligatorio csrfToken={sesion.csrfToken} alCompletar={recargar} alSalir={recargar} />
    </MarcoAcceso>
  );
}
if (!sesion || sesion.state === 'anonymous') {
  return <PantallaLogin csrfToken={sesion?.csrfToken ?? ''} alResolver={async () => recargar()} alRevalidar={recargar} />;
}
if (!sesion.project) return <SelectorProyecto alElegir={recargar} csrfToken={sesion.csrfToken} />;
return <><NavegacionLateral sesion={sesion} /><main><h1>{sesion.project.name}</h1></main></>;
```

- [ ] **Step 4: Run all frontend tests and typecheck**

```bash
npm --prefix frontend test
npm --prefix frontend run typecheck
```

Expected: suite completa PASS, sin flash de pantalla incorrecta ni llamadas duplicadas bajo
`StrictMode`.

- [ ] **Step 5: Commit the startup integration**

```bash
git add frontend/src/App.tsx frontend/src/shell/useSesion.ts frontend/src/shell/rutas.tsx frontend/src/shell/rutas.test.tsx frontend/src/shell/auth/avisos.ts frontend/src/shell/auth/avisos.test.ts
git commit -m "feat(shell): integrar estados de acceso y recuperacion"
```

---

### Task 11: Aplicar la presentación responsive y registrar el design system

**Files:**
- Create: `public/css/auth-react.css`
- Modify: `frontend/index.html`
- Modify: `tests/test_login_design_system_contract.mjs`
- Modify: `docs/design-system/manifests/auth.json`
- Modify: `frontend/src/shell/auth/PantallaLogin.test.tsx`
- Modify: `frontend/src/shell/auth/CambioClaveObligatorio.test.tsx`

**Interfaces:**
- Selectores `aia-auth*` viven solo en `@layer module` y consumen `--ds-*`/`--aia-*`.
- Desktop 1180/1440: dos paneles; tablet: contexto reducido; 390: una columna y modal como panel.
- `auth.json` declara React y legacy S02/S03 sin afirmar que estas últimas ya migraron.

- [ ] **Step 1: Strengthen the static design contract before CSS exists**

Añadir aserciones de link, capa, ausencia de colores literales/`!important`, tokens, media queries y
fuentes en manifest:

```js
assert.match(frontendHtml, /\/css\/auth-react\.css/);
assert.match(authCss, /^@layer module\s*\{/);
assert.doesNotMatch(authCss, /#[0-9a-f]{3,8}\b|rgba?\(/i);
assert.doesNotMatch(authCss, /!important/);
assert.ok(manifest.sources.includes('frontend/src/shell/auth/PantallaLogin.tsx'));
assert.deepEqual(manifest.layouts.sort(), ['desktop', 'mobile', 'tablet', 'wide']);
```

- [ ] **Step 2: Run static contract and confirm RED**

Run: `node tests/test_login_design_system_contract.mjs`

Expected: FAIL porque la hoja y fuentes React todavía no están declaradas.

- [ ] **Step 3: Implement token-only responsive CSS and manifest**

Crear `@layer module` con grid de dos columnas desde 64rem, una columna debajo, ancho máximo
tokenizado, targets `var(--ds-target-min)`, foco del core, `prefers-reduced-motion`, dialog backdrop
y panel móvil. Cargar la hoja después de theme claro. Actualizar `auth.json` con estados
`normal,error,focus,busy,password-change,cancel-confirmation`, roles `anonymous,pending-password`,
tema persistente dark/light y fuentes React; conservar rutas/fuentes legacy de S02/S03.

```css
@layer module {
  .aia-auth { min-block-size: 100dvh; background: var(--ds-active-bg-canvas); color: var(--ds-active-text-primary); }
  .aia-auth__layout { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(20rem, 0.9fr); }
  .aia-auth :where(input, button, a) { min-block-size: var(--ds-target-min); }
  @media (max-width: 63.999rem) { .aia-auth__layout { grid-template-columns: 1fr; } }
  @media (max-width: 30rem) { .aia-auth__dialog { inline-size: 100%; min-block-size: 100dvh; } }
  @media (prefers-reduced-motion: reduce) { .aia-auth * { scroll-behavior: auto; transition-duration: 0.01ms; } }
}
```

- [ ] **Step 4: Run static gates, frontend suite and build**

```bash
node tests/test_login_design_system_contract.mjs
npm --prefix frontend test
npm --prefix frontend run build
git diff --check
```

Expected: contratos estáticos/frontend/build PASS y bundle generado con `auth-react.css` enlazado.

- [ ] **Step 5: Commit presentation and generated assets**

```bash
git add public/css/auth-react.css frontend/index.html frontend/src/shell/auth tests/test_login_design_system_contract.mjs docs/design-system/manifests/auth.json public/app
git commit -m "feat(auth): aplicar presentacion responsive del acceso"
```

---

### Task 12: Servir la entrada oculta React sin publicar el secreto en el bundle

**Files:**
- Create: `frontend/src/lib/runtime/configuracion.ts`
- Create: `frontend/src/lib/runtime/configuracion.test.ts`
- Create: `src/Core/SpaHostRenderer.php`
- Create: `src/Controllers/Auth/MaintenanceLoginController.php`
- Create: `tests/test_auth_maintenance_contract.php`
- Modify: `frontend/src/App.tsx`
- Modify: `frontend/src/shell/rutas.tsx`
- Modify: `frontend/src/shell/auth/PantallaLogin.tsx`
- Modify: `frontend/src/shell/auth/PantallaLogin.test.tsx`
- Modify: `src/Core/MaintenanceMode.php`
- Modify: `public/index.php`
- Modify: `tests/test_maintenance_asset_exemption.php`

**Interfaces:**
- HTML opcional `#aia-runtime-config[type=application/json]` validado por Zod.
- Runtime válido: `{mode:'maintenance',action,error,state,csrfToken}`; el bundle solo conoce la forma.
- Runtime ausente: `{mode:'application'}`; runtime corrupto: `{mode:'invalid'}` con pantalla recuperable.
- `SpaHostRenderer::render(array $config = [], int $status = 200, string $method = 'GET'): void`.
- Host oculto GET responde 200; rechazo de POST vuelve al mismo host con 401/error genérico; éxito o cambio pendiente usan 303 a `/proyectos` o a la misma acción oculta.

- [ ] **Step 1: Write failing runtime, maintenance and secret-leak tests**

En Vitest comprobar config ausente/válida/corrupta y que modo mantenimiento no llama `/api/session`.
En PHP comprobar:

```php
comprobar('bundle no contiene ruta oculta', !str_contains($bundle, MaintenanceMode::SECRET_PATH));
comprobar('app sigue cerrada en mantenimiento', !MaintenanceMode::isExemptRoute('/app'));
comprobar('asset del bundle queda exento', MaintenanceMode::isExemptRoute('/app/assets/app.js'));
```

El contrato HTTP activa `.maintenance` bajo `try/finally`, guarda su estado previo, prueba 503 en
`/login`, host oculto sin error, rechazo genérico para credenciales/rol, permitido A global en
Construcción y restaura exactamente archivo, sesiones y fixture. No imprime la ruta.

- [ ] **Step 2: Run focused tests and confirm RED**

```bash
npm --prefix frontend test -- src/lib/runtime src/shell/auth/PantallaLogin.test.tsx src/shell/rutas.test.tsx
docker compose exec -T app php tests/test_maintenance_asset_exemption.php
docker compose exec -T app php tests/test_auth_maintenance_contract.php
```

Expected: FAIL porque renderer, config y controlador dedicado no existen.

- [ ] **Step 3: Implement server-injected maintenance mode**

`SpaHostRenderer` lee el HTML construido, inserta JSON con
`JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT`, responde HEAD sin cuerpo y nunca evalúa
HTML del payload. `MaintenanceLoginController::show()` inyecta la URI actual tomada del request,
estado pendiente/anonymous, CSRF y error booleano. `submit()` conserva exactamente credenciales →
activo → A global/Construcción/proyecto activo; solo después fija `maintenance_bypass=true` y entra
en cambio obligatorio o sesión autenticada. El bypass debe existir antes del redirect pendiente
para que `/api/auth/password/change|cancel` no reciban el 503 público.

En mantenimiento, `PantallaLogin` usa `<form method="post" action={action}>` y nombres legacy
`usuario/password`; no invoca `/api/auth/login`. Si hay cambio pendiente, el runtime renderiza
`CambioClaveObligatorio`; al completar navega `/proyectos`, al cancelar vuelve a `action` recibida.

Registrar GET/POST ocultos en `MaintenanceLoginController`; no escribir el literal en TypeScript.
Eximir únicamente `/app/assets/*` en `MaintenanceMode`, no `/app`, `/login` ni APIs sin bypass.

```ts
export const EsquemaConfiguracionRuntime = z.discriminatedUnion('mode', [
  z.object({ mode: z.literal('application') }),
  z.object({
    mode: z.literal('maintenance'),
    action: z.string().startsWith('/'),
    error: z.boolean(),
    state: z.enum(['anonymous', 'password_change_required']),
    csrfToken: z.string().regex(/^[a-f0-9]{64}$/),
  }),
]);
```

```php
$json = json_encode(
    $config,
    JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
);
$bootstrap = '<script id="aia-runtime-config" type="application/json">' . $json . '</script>';
$html = str_replace('<div id="root"></div>', $bootstrap . '<div id="root"></div>', $html);
http_response_code($status);
if (strtoupper($method) !== 'HEAD') {
    echo $html;
}
```

```ts
try {
  const resultado = EsquemaConfiguracionRuntime.safeParse(JSON.parse(nodo.textContent ?? ''));
  return resultado.success ? resultado.data : { mode: 'invalid' as const };
} catch {
  return { mode: 'invalid' as const };
}
```

- [ ] **Step 4: Run maintenance contracts twice and scan artifacts**

```bash
npm --prefix frontend run build
npm --prefix frontend test -- src/lib/runtime src/shell/auth src/shell/rutas.test.tsx
docker compose exec -T app php tests/test_maintenance_asset_exemption.php
docker compose exec -T app php tests/test_auth_maintenance_contract.php
docker compose exec -T app php tests/test_auth_maintenance_contract.php
```

Expected: dos corridas PASS con restauración, público 503, rol denegado, A permitido, cambio
pendiente seguro y `SECRET_PATH` ausente de `public/app/assets/*.js`.

- [ ] **Step 5: Commit maintenance entry without its fixtures**

```bash
git add frontend/src/lib/runtime frontend/src/App.tsx frontend/src/shell/rutas.tsx frontend/src/shell/auth src/Core/SpaHostRenderer.php src/Controllers/Auth/MaintenanceLoginController.php src/Core/MaintenanceMode.php public/index.php tests/test_auth_maintenance_contract.php tests/test_maintenance_asset_exemption.php public/app
git commit -m "feat(auth): servir acceso React durante mantenimiento"
```

---

### Task 13: Cortar GET/HEAD de acceso a React con rollback comprobable

**Files:**
- Modify: `src/Core/SpaRouter.php`
- Modify: `src/Core/SpaHostRenderer.php`
- Modify: `public/index.php`
- Modify: `tests/test_spa_frontera.php`
- Modify: `tests/test_spa_frontera_http.php`
- Modify: `tests/test_api_auth_contract.php`

**Interfaces:**
- `SpaRouter::sirveLaSpa(string $ruta, string $metodo = 'GET'): bool`.
- `SpaRouter::coincideConMapa(string $ruta, string $metodo, array $exactas, array $prefijos): bool` permite probar el rollback sin editar constantes.
- Exactas S01: `/`, `/login`; prefijo piloto: `/app`.
- Solo GET/HEAD; POST `/login` continúa legacy durante la ventana de rollback.
- `/password/forgot`, `/password/reset`, `/api/*`, `/app/assets*` nunca pasan al host SPA.

- [ ] **Step 1: Write failing pure and HTTP route matrices**

```php
$matrix = [
    ['GET', '/', true], ['HEAD', '/', true], ['POST', '/', false],
    ['GET', '/login', true], ['HEAD', '/login', true], ['POST', '/login', false],
    ['GET', '/password/forgot', false], ['GET', '/password/reset', false],
    ['GET', '/api/session', false], ['GET', '/app/assets/x.js', false],
];
```

Por HTTP exigir `#root` en GET `/` y `/login`, HEAD 200 sin formulario legacy, refresh/deep link
`/app/login`, y S02/S03 todavía con sus forms PHP. Verificar que POST `/login` aún llega al
adaptador legacy durante rollback.

- [ ] **Step 2: Run route tests and confirm RED**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_spa_frontera.php
docker compose exec -T app php tests/test_spa_frontera_http.php
```

Expected: FAIL porque `SpaRouter` solo conoce el prefijo `/app`.

- [ ] **Step 3: Implement method-aware exact routes and host renderer**

Separar `RUTAS_EXACTAS_MIGRADAS=['/','/login']` de `PREFIJOS_MIGRADOS=['/app']`.
`sirveLaSpa()` delega en `coincideConMapa()` con esas constantes. El front controller pasa
`$_SERVER['REQUEST_METHOD']` tanto al guard como al despacho y llama
`SpaHostRenderer::render([], 200, $method)`. `/` nunca se evalúa con `str_starts_with`.

```php
public static function coincideConMapa(
    string $ruta,
    string $metodo,
    array $exactas,
    array $prefijos,
): bool {
    if (!in_array(strtoupper($metodo), ['GET', 'HEAD'], true)) {
        return false;
    }
    if (str_starts_with($ruta, '/api/') || $ruta === '/app/assets' || str_starts_with($ruta, '/app/assets/')) {
        return false;
    }
    if (in_array($ruta, $exactas, true)) {
        return true;
    }
    foreach ($prefijos as $prefijo) {
        if ($ruta === $prefijo || str_starts_with($ruta, $prefijo . '/')) {
            return true;
        }
    }
    return false;
}
```

- [ ] **Step 4: Run route, auth, build and rollback drill**

```bash
npm --prefix frontend run build
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_spa_frontera.php
docker compose exec -T app php tests/test_spa_frontera_http.php
docker compose exec -T app php tests/test_api_auth_contract.php
```

Para el drill, `test_spa_frontera.php` llama `coincideConMapa()` con `exactas=[]` y
`prefijos=['/app']`: `/app` sigue React mientras `/`/`/login` vuelven a dispatch PHP. Es una prueba
pura; no cambia sesión, DB ni RLS.

Expected: matrices PASS, auth PASS, S02/S03 intactas y rollback lógico demostrado.

- [ ] **Step 5: Commit the canonical route cut**

```bash
git add src/Core/SpaRouter.php src/Core/SpaHostRenderer.php public/index.php tests/test_spa_frontera.php tests/test_spa_frontera_http.php tests/test_api_auth_contract.php public/app
git commit -m "feat(auth): servir login React en rutas canonicas"
```

---

### Task 14: Verificar navegador, aprobar visual y retirar VIEW-01 tras el gate

**Files:**
- Create: `tests/browser/login-react.spec.mjs`
- Create: `tests/browser/login-react.visual.mjs`
- Modify after explicit visual approval: `docs/design-system/manifests/auth.json`
- Modify after rollback window: `public/index.php`
- Modify after rollback window: `src/Controllers/Auth/LoginController.php`
- Create after rollback window: `src/Controllers/Core/SpaHostController.php`
- Delete after rollback window: `views/auth/login.view.php`
- Modify after rollback window: `tests/test_login_design_system_contract.mjs`
- Modify after rollback window: `tests/test_spa_frontera_http.php`

**Interfaces:**
- Playwright funcional intercepta `/api/session` y `/api/auth/*`; nunca escribe credenciales reales.
- Goldens candidatos: dark/light × `390×844`, `768×1024`, `1180×820`; `1440×900` para wide.
- Retiro final cambia POST `/login` a 405 y deja mantenimiento en su controlador dedicado.
- `SpaHostController::show(): void` llama `SpaHostRenderer` y mantiene una ruta GET registrada para que FastRoute responda 405 al POST retirado.

- [ ] **Step 1: Write browser behavior and accessibility scenarios**

Crear rutas controladas con `page.route()` para anonymous, 401, 422, password-change, cambio exitoso,
cancelación y error de bootstrap. En cada viewport/tema comprobar un `h1`, labels, toggles,
focus-visible, 44px, 200% zoom, `scrollWidth-clientWidth <= 1`, consola sin error y Axe sin violación
seria/crítica. No registrar cuerpos de auth en trazas.

```js
await page.route('**/api/session', (route) => route.fulfill({
  status: 200,
  contentType: 'application/json',
  body: JSON.stringify({
    state: 'anonymous', authenticated: false, reason: 'missing_session',
    user: null, project: null, capabilities: {}, navigation: { bi: null }, csrfToken,
  }),
}));
await page.goto('/login');
await expect(page.getByRole('heading', { level: 1, name: /entrar/i })).toBeVisible();
const layout = await page.evaluate(() => ({
  overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
  target: parseFloat(getComputedStyle(document.querySelector('button[type="submit"]')).minHeight),
}));
expect(layout.overflow).toBeLessThanOrEqual(1);
expect(layout.target).toBeGreaterThanOrEqual(44);
```

- [ ] **Step 2: Run functional browser suite and capture non-versioned candidates**

Después de verificar el mount:

```bash
npx playwright test tests/browser/login-react.spec.mjs --workers=1
npx playwright test tests/browser/login-react.visual.mjs --workers=1 --grep "candidate"
```

Expected: funcional PASS. Los candidatos se guardan bajo `test-output/s01-login-candidates/`, que no
se añade a git. Revisar red: una mutación por acción, cero HTML donde se espera JSON.

- [ ] **Step 3: Stop for Felipe's explicit visual approval before baselines**

Presentar las ocho capturas con ruta, tema y viewport. Solo con aprobación explícita ejecutar:

```bash
npx playwright test tests/browser/login-react.visual.mjs --workers=1 --update-snapshots
node scripts/design-system-static-suite.mjs
```

Actualizar en `auth.json` cada `golden` y `sha256` con el archivo aprobado. Si no se aprueba, ajustar
únicamente `auth-react.css`/componentes, regenerar candidatos fuera de git y repetir este gate.

- [ ] **Step 4: Run the complete pre-retirement gate and observe rollback window**

```bash
npm --prefix frontend test
npm --prefix frontend run typecheck
npm --prefix frontend run build
docker compose exec -T app php tests/test_api_session_contract.php
docker compose exec -T app php tests/test_api_auth_contract.php
docker compose exec -T app php tests/test_auth_maintenance_contract.php
docker compose exec -T app php tests/test_spa_frontera_http.php
node tests/test_login_design_system_contract.mjs
npx playwright test tests/browser/login-react.spec.mjs tests/browser/login-react.visual.mjs --workers=1
git diff --check
```

Expected: todo RC 0 sobre el SHA y mount anotados. Mantener VIEW-01/POST legacy durante la ventana
de rollback aprobada; el deploy/observación requiere autorización separada y no forma parte de una
corrida local.

- [ ] **Step 5: Retire legacy only after the explicit post-rollout gate, then commit**

Con confirmación de que rollback ya no es necesario: eliminar VIEW-01; quitar GET legacy,
`POST /login`, `/password/update`, `/login/cancelar`; dejar FastRoute responder 405 a POST `/login`;
reducir `LoginController` a `logout()` porque los shells PHP residuales todavía consumen
`GET /logout`; registrar GET `/` y `/login` en `SpaHostController::show()` y conservar
`MaintenanceLoginController`. Volver a correr el gate de Step 4 y
comprobar que S02/S03 conservan `login-brand-unified.css`/`auth_forms.js` y que `/logout` sigue
registrado hasta el retiro final de T01. Quitar `views/auth/login.view.php` de `auth.json.sources`
sin quitar `password-forgot.view.php`, `password-reset.view.php` ni la hoja compartida.

```php
namespace App\Controllers\Core;

use App\Core\SpaHostRenderer;

final class SpaHostController
{
    public function show(): void
    {
        SpaHostRenderer::render([], 200, (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }
}

$router->get('/', [\App\Controllers\Core\SpaHostController::class, 'show']);
$router->get('/login', [\App\Controllers\Core\SpaHostController::class, 'show']);
// No registrar POST /login: FastRoute observa los GET anteriores y responde 405.
```

```bash
git add frontend/index.html frontend/src/App.tsx frontend/src/lib/api/auth.ts frontend/src/lib/api/cliente.ts frontend/src/lib/api/cliente.test.ts frontend/src/lib/api/esquemas/auth.ts frontend/src/lib/api/esquemas/auth.test.ts frontend/src/lib/api/esquemas/error.ts frontend/src/lib/api/esquemas/sesion.ts frontend/src/lib/api/esquemas/sesion.test.ts frontend/src/lib/api/frontera.test.ts frontend/src/lib/runtime/configuracion.ts frontend/src/lib/runtime/configuracion.test.ts frontend/src/shell/ConmutadorTema.tsx frontend/src/shell/NavegacionLateral.tsx frontend/src/shell/PantallaLogin.tsx frontend/src/shell/PantallaLogin.test.tsx frontend/src/shell/auth/MarcoAcceso.tsx frontend/src/shell/auth/CampoClave.tsx frontend/src/shell/auth/avisos.ts frontend/src/shell/auth/avisos.test.ts frontend/src/shell/auth/PantallaLogin.tsx frontend/src/shell/auth/PantallaLogin.test.tsx frontend/src/shell/auth/CambioClaveObligatorio.tsx frontend/src/shell/auth/CambioClaveObligatorio.test.tsx frontend/src/shell/rutas.tsx frontend/src/shell/rutas.test.tsx frontend/src/shell/tema.ts frontend/src/shell/tema.test.ts frontend/src/shell/useSesion.ts public/app public/css/auth-react.css src/Controllers/Api/AuthApiController.php src/Controllers/Api/SessionApiController.php src/Controllers/Auth/LoginController.php src/Controllers/Auth/MaintenanceLoginController.php src/Controllers/Core/SpaHostController.php src/Core/MaintenanceMode.php src/Core/SpaHostRenderer.php src/Core/SpaRouter.php src/Services/Auth/AuthenticationService.php src/Services/Auth/ForcedPasswordChangeService.php src/Services/Auth/PasswordPolicyService.php src/Services/Auth/UserPasswordService.php public/index.php tests/test_api_auth_contract.php tests/test_api_session_contract.php tests/test_auth_maintenance_contract.php tests/test_login_design_system_contract.mjs tests/test_maintenance_asset_exemption.php tests/test_spa_frontera.php tests/test_spa_frontera_http.php tests/unit/ForcedPasswordChangeServiceTest.php tests/unit/PasswordPolicyServiceTest.php tests/browser/login-react.spec.mjs tests/browser/login-react.visual.mjs docs/design-system/manifests/auth.json
git add -u views/auth/login.view.php
git commit -m "feat(auth): cerrar migracion React del acceso"
```

## Observable Parity Traceability

| Capacidad | Evidencia del plan |
|---|---|
| S01-UX-01 | Tasks 8 y 11: marca, contexto Construcción y footer compartido. |
| S01-UX-02 | Task 8: label/autocomplete/autocapitalize/spellcheck de usuario. |
| S01-UX-03 | Task 8: contraseña y toggle con `aria-pressed`. |
| S01-UX-04 | Tasks 8 y 14: Enter/click único, busy y petición única. |
| S01-UX-05 | Tasks 5, 8 y 14: 401 no enumerable y copy fijo. |
| S01-UX-06 | Tasks 8 y 10: reason/query seguro, prioridad y consumo único. |
| S01-UX-07 | Tasks 8 y 10: confirmación reset y limpieza con history. |
| S01-UX-08 | Tasks 8, 13 y 14: enlace/ruta S02 preservados. |
| S01-UX-09 | Tasks 3–6 y 9: reglas, endpoint, bootstrap y panel obligatorio. |
| S01-UX-10 | Tasks 4, 5 y 9: confirmación y cancelación aislada. |
| S01-UX-11 | Task 12: host React oculto y autorización servidor. |
| S01-UX-12 | Tasks 7, 9, 11 y 14: conmutador disponible y ambos temas. |
| S01-UX-13 | Tasks 1, 8 y 10: red/5xx/contrato recuperables, distintos de 401. |

## Acceptance Traceability

| Criterio | Evidencia del plan |
|---|---|
| S01-AC-01 | Tasks 13–14: GET/HEAD exactos, refresh, deep link y HTTP real. |
| S01-AC-02 | Tasks 8, 11 y 14: S01-UX completo, contrato visual y retiro gateado de VIEW-01. |
| S01-AC-03 | Tasks 4–6 y 10: regeneración, `next=projects`, bootstrap autenticado sin proyecto. |
| S01-AC-04 | Tasks 1, 5 y 14: 401 genérico, fixture inactivo/inexistente y UI controlada. |
| S01-AC-05 | Tasks 8 y 10: timeout/inactive/reset tipados y query consumida una vez. |
| S01-AC-06 | Tasks 8 y 14: toggle, Enter, busy, teclado y enlace S02. |
| S01-AC-07 | Tasks 3–6 y 9: cinco reglas, endpoint, estado pendiente y panel React. |
| S01-AC-08 | Tasks 4, 5 y 9: confirmación, cancelación idempotente y sesión completa intacta. |
| S01-AC-09 | Task 12: 503 público, host oculto, A global, Construcción, bypass y secreto fuera del bundle. |
| S01-AC-10 | Tasks 1, 2, 5 y 6: Zod, CSRF y contratos PHP. |
| S01-AC-11 | Tasks 7, 11 y 14: dark inicial y matriz light/dark responsive. |
| S01-AC-12 | Tasks 8–11 y 14: single-submit, foco, Axe, zoom y overflow. |
| S01-AC-13 | Tasks 5–6: cuerpos sin proyecto/prefijo/rol y bootstrap sin datos operativos. |
| S01-AC-14 | Tasks 13–14: matriz de rollback antes del retiro; RLS/datos intactos. |
| S01-AC-15 | Global constraints y Tasks 11/14: `/admin/`, S02 y S03 preservados. |

## Completion Gate

S01 queda `CODE_COMPLETE` solo cuando Tasks 1–13 y el gate funcional/visual de Task 14 están en
verde sobre el mismo SHA. Queda `MIGRATION_COMPLETE` únicamente después del gate post-rollout que
autoriza retirar VIEW-01. Ninguno de esos estados autoriza deploy a producción, cambios RLS,
schema, grants, usuarios persistentes o credenciales.
