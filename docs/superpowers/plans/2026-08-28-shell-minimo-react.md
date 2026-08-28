---
capa: fuente
tipo: plan
estado: abierto
fecha: 2026-08-28
areas: [arquitectura, rbac, design-system]
fuente: docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md
resumen: "Frente 1 de la migración a React: el shell mínimo — login, selector de proyecto, navegación, tema y guardas — más los endpoints JSON que hoy no existen y la frontera que decide qué mundo sirve cada ruta."
---

# Shell mínimo React — plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** construir la cáscara React que reemplaza el login y el selector de proyecto del sitio PHP, de modo que el primer módulo migrado tenga dónde montarse.

**Architecture:** el PHP gana cuatro endpoints JSON de sesión y autenticación que hoy no existe ninguno (el login actual redirige). Una SPA nueva en `frontend/` los consume con un cliente HTTP único que valida cada respuesta contra un esquema Zod. `public/index.php` gana un punto de decisión: las rutas migradas entregan el `index.html` de la SPA, las demás siguen sirviendo vistas PHP. Los dos mundos comparten la misma cookie de sesión porque se sirven del mismo origen.

**Tech Stack:** React 19, TypeScript, Vite, Zod, Vitest (mismo stack que `pdc-app/`, ya probado en el repo). PHP 8.3 del lado servidor, sin dependencias nuevas.

**Spec:** `docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md`

## Global Constraints

Copiados de la spec; aplican a todas las tareas.

- **R1:** el PHP no pinta HTML nuevo. Todo lo que el shell necesite se sirve como JSON.
- **R5:** el shell v1 son cinco piezas: login, selector de proyecto, navegación lateral, conmutador de tema, enrutado con guardas. **Nada más.**
- **R7:** ningún componente llama `fetch` directo. Todo pasa por el cliente HTTP, que valida contra Zod. El esquema es la única definición del tipo — no se escriben tipos a mano en paralelo.
- **R8:** cada endpoint nuevo lleva su test de contrato. **No hay gate de cobertura mínima.**
- **R11:** techo de 300 líneas por archivo como guía, no como gate automático.
- **R12:** `password-forgot` y `password-reset` **no** entran en este frente. Siguen en PHP.
- **Tokens:** los componentes React consumen `public/css/tokens.css` tal cual. No se declara ni un color nuevo.
- **Seguridad:** no se inventa autenticación. El login React llama al mismo `AuthService` que ya usa el formulario PHP; CSRF se mantiene con `CsrfTokenManager`.
- **Sesión local:** para probar en el navegador se usa siempre la puerta de servicio (`/dev/entrar?u=test.A`), nunca tecleando credenciales en `/login`.

## Estructura de archivos

**PHP (nuevo):**
- `src/Controllers/Api/SessionApiController.php` — quién soy, qué puedo, en qué proyecto estoy.
- `src/Controllers/Api/AuthApiController.php` — entrar y salir, en JSON.
- `src/Controllers/Api/ProjectApiController.php` — listar proyectos y elegir uno.
- `src/Core/SpaRouter.php` — la frontera: qué rutas sirve la SPA.

**PHP (modificado):**
- `public/index.php` — registrar las rutas nuevas y la frontera.

**Frontend (nuevo), todo bajo `frontend/`:**
- `src/lib/api/cliente.ts` — el único que llama `fetch`.
- `src/lib/api/esquemas/sesion.ts` — esquemas Zod de sesión, proyectos y login.
- `src/shell/rutas.tsx` — enrutado y guardas.
- `src/shell/PantallaLogin.tsx`, `src/shell/SelectorProyecto.tsx`, `src/shell/NavegacionLateral.tsx`, `src/shell/ConmutadorTema.tsx`.
- `AGENTS.md` — reglas del stack nuevo.

---

### Task 1: El endpoint que dice quién soy

Sin esto la SPA no puede decidir si mostrar el login o la aplicación. Es la primera pieza porque todas las demás la consumen.

**Files:**
- Create: `src/Controllers/Api/SessionApiController.php`
- Modify: `public/index.php` (zona de rutas API)
- Test: `tests/test_api_session_contract.php`

**Interfaces:**
- Consumes: `App\Security\RbacManager::getCapabilities(string $role): array`, `App\Security\RbacService::normalizeRole(string $role): string`
- Produces: `GET /api/session` → `{authenticated: bool, user: {username: string, displayName: string, role: string}|null, project: {id: int, name: string}|null, capabilities: object}`. Las tareas 5, 7 y 10 dependen de esta forma exacta.

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/test_api_session_contract.php`:

```php
<?php
// @requiere: http

require_once __DIR__ . '/../vendor/autoload.php';

$base = getenv('APP_URL') ?: 'http://localhost:8081';
$fallos = 0;

function pedirJson(string $url, array $opciones = []): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_COOKIEJAR => $opciones['cookies'] ?? '',
        CURLOPT_COOKIEFILE => $opciones['cookies'] ?? '',
    ]);
    $cuerpo = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['codigo' => $codigo, 'json' => json_decode((string) $cuerpo, true)];
}

// Sin sesión: responde 200 con authenticated=false, NO redirige ni da 401.
$r = pedirJson("$base/api/session");
if ($r['codigo'] !== 200) {
    echo "FALLO: sin sesion esperaba 200, llego {$r['codigo']}\n";
    $fallos++;
}
if (($r['json']['authenticated'] ?? null) !== false) {
    echo "FALLO: sin sesion esperaba authenticated=false\n";
    $fallos++;
}
if (($r['json']['user'] ?? 'ausente') !== null) {
    echo "FALLO: sin sesion user debe ser null\n";
    $fallos++;
}

// Con sesión abierta por la puerta de servicio: trae usuario, rol y capacidades.
$galletas = tempnam(sys_get_temp_dir(), 'sess');
pedirJson("$base/dev/entrar?u=test.A", ['cookies' => $galletas]);
$r = pedirJson("$base/api/session", ['cookies' => $galletas]);

if (($r['json']['authenticated'] ?? null) !== true) {
    echo "FALLO: con sesion esperaba authenticated=true\n";
    $fallos++;
}
if (($r['json']['user']['role'] ?? null) !== 'A') {
    echo "FALLO: test.A debe tener rol A, llego: " . var_export($r['json']['user']['role'] ?? null, true) . "\n";
    $fallos++;
}
if (!array_key_exists('canManageWeeks', $r['json']['capabilities'] ?? [])) {
    echo "FALLO: capabilities debe traer canManageWeeks\n";
    $fallos++;
}
// El rol A administra semanas: si esto sale false, el mapa no viene de RbacManager.
if (($r['json']['capabilities']['canManageWeeks'] ?? null) !== true) {
    echo "FALLO: el rol A debe poder administrar semanas\n";
    $fallos++;
}
// La respuesta NO debe filtrar nada de la sesión que no sea del contrato.
foreach (['db', 'area', 'usuario_temp'] as $prohibido) {
    if (array_key_exists($prohibido, $r['json'])) {
        echo "FALLO: la respuesta filtra '$prohibido', que es interno de la sesion\n";
        $fallos++;
    }
}
@unlink($galletas);

echo $fallos === 0 ? "OK: contrato de /api/session\n" : "$fallos fallo(s)\n";
exit($fallos === 0 ? 0 : 1);
```

- [ ] **Step 2: Correrlo y ver que falla**

```bash
docker compose exec app php tests/test_api_session_contract.php
```

Esperado: FALLO, porque `/api/session` todavía no existe y devuelve 404.

- [ ] **Step 3: Escribir el controlador**

Crear `src/Controllers/Api/SessionApiController.php`:

```php
<?php

namespace App\Controllers\Api;

use App\Security\RbacManager;
use App\Security\RbacService;

/**
 * Lo que la SPA necesita saber al arrancar: si hay sesión, quién es, qué puede
 * y en qué proyecto está.
 *
 * Es deliberadamente PÚBLICO (no pasa por el guard de sesión): la SPA lo llama
 * ANTES de saber si hay sesión, y un 401 aquí obligaría a tratar el caso normal
 * "todavía no entró" como si fuera un error.
 */
class SessionApiController
{
    public function show(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json; charset=utf-8');

        $usuario = $_SESSION['usuario'] ?? null;

        if (!is_string($usuario) || $usuario === '') {
            echo json_encode([
                'authenticated' => false,
                'user' => null,
                'project' => null,
                'capabilities' => new \stdClass(),
            ]);
            return;
        }

        $rol = RbacService::normalizeRole((string) ($_SESSION['permiso'] ?? ''));

        // project_id y proyecto se fijan juntos al entrar a un proyecto; si falta
        // el id, no hay proyecto activo aunque quede el nombre de un intento previo.
        $proyecto = null;
        if (isset($_SESSION['project_id'], $_SESSION['proyecto'])) {
            $proyecto = [
                'id' => (int) $_SESSION['project_id'],
                'name' => (string) $_SESSION['proyecto'],
            ];
        }

        echo json_encode([
            'authenticated' => true,
            'user' => [
                'username' => $usuario,
                'displayName' => (string) ($_SESSION['nombreUsuario'] ?? $usuario),
                'role' => $rol,
            ],
            'project' => $proyecto,
            'capabilities' => RbacManager::getCapabilities($rol),
        ]);
    }
}
```

- [ ] **Step 4: Registrar la ruta**

En `public/index.php`, añadir a `$publicRoutes` (la lista de la línea 46) la entrada `'/api/session'`, y registrar la ruta junto a las demás de la zona API:

```php
$router->get('/api/session', [\App\Controllers\Api\SessionApiController::class, 'show']);
```

- [ ] **Step 5: Correr el test y verlo pasar**

```bash
docker compose exec app php tests/test_api_session_contract.php
```

Esperado: `OK: contrato de /api/session`

- [ ] **Step 6: Commit**

```bash
git add src/Controllers/Api/SessionApiController.php tests/test_api_session_contract.php public/index.php
git commit -m "feat(api): endpoint de sesion para el shell React"
```

---

### Task 2: Entrar y salir, en JSON

**Files:**
- Create: `src/Controllers/Api/AuthApiController.php`
- Modify: `public/index.php`
- Test: `tests/test_api_auth_contract.php`

**Interfaces:**
- Consumes: el mismo servicio de autenticación que usa `App\Controllers\Auth\LoginController` (leerlo antes de escribir: el método que valida credenciales se reusa tal cual, no se reimplementa).
- Produces: `POST /api/auth/login` con `{username, password}` → `{success: bool, mustChangePassword: bool, message: string|null}`; `POST /api/auth/logout` → `{success: true}`. La tarea 8 depende de esta forma.

- [ ] **Step 1: Leer cómo autentica el login actual**

```bash
sed -n '60,115p' src/Controllers/Auth/LoginController.php
```

Anotar el nombre exacto del servicio y método que valida las credenciales. **El endpoint nuevo lo llama a él** — no se copia la lógica, no se escribe una validación paralela.

- [ ] **Step 2: Escribir el test que falla**

Crear `tests/test_api_auth_contract.php`:

```php
<?php
// @requiere: http

$base = getenv('APP_URL') ?: 'http://localhost:8081';
$fallos = 0;

function postJson(string $url, array $cuerpo, string $galletas): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($cuerpo),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_COOKIEJAR => $galletas,
        CURLOPT_COOKIEFILE => $galletas,
    ]);
    $respuesta = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['codigo' => $codigo, 'json' => json_decode((string) $respuesta, true)];
}

$galletas = tempnam(sys_get_temp_dir(), 'auth');

// Credenciales malas: NO entra, y no dice si falló el usuario o la clave.
$r = postJson("$base/api/auth/login", ['username' => 'noexiste', 'password' => 'malo'], $galletas);
if (($r['json']['success'] ?? null) !== false) {
    echo "FALLO: credenciales invalidas no deben entrar\n";
    $fallos++;
}
if ($r['codigo'] === 500) {
    echo "FALLO: credenciales invalidas dan 500 en vez de respuesta controlada\n";
    $fallos++;
}
$mensaje = strtolower((string) ($r['json']['message'] ?? ''));
if (str_contains($mensaje, 'no existe') || str_contains($mensaje, 'usuario incorrecto')) {
    echo "FALLO: el mensaje revela si el usuario existe (enumeracion de cuentas)\n";
    $fallos++;
}

// Salir siempre responde bien, haya sesión o no.
$r = postJson("$base/api/auth/logout", [], $galletas);
if (($r['json']['success'] ?? null) !== true) {
    echo "FALLO: logout debe responder success=true\n";
    $fallos++;
}

// Tras salir, la sesión quedó cerrada de verdad.
$ch = curl_init("$base/api/session");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
    CURLOPT_COOKIEFILE => $galletas,
]);
$sesion = json_decode((string) curl_exec($ch), true);
curl_close($ch);
if (($sesion['authenticated'] ?? null) !== false) {
    echo "FALLO: tras logout la sesion sigue abierta\n";
    $fallos++;
}
@unlink($galletas);

echo $fallos === 0 ? "OK: contrato de /api/auth\n" : "$fallos fallo(s)\n";
exit($fallos === 0 ? 0 : 1);
```

- [ ] **Step 3: Correrlo y ver que falla**

```bash
docker compose exec app php tests/test_api_auth_contract.php
```

Esperado: FALLO — los endpoints no existen.

- [ ] **Step 4: Escribir el controlador**

Crear `src/Controllers/Api/AuthApiController.php`. La estructura obligatoria, con el servicio real que anotaste en el Step 1 en lugar de `SERVICIO_DEL_STEP_1`:

```php
<?php

namespace App\Controllers\Api;

/**
 * Entrar y salir en JSON, para el shell React.
 *
 * Reusa el MISMO servicio de autenticación que el formulario PHP: dos caminos de
 * login con lógica propia es como se cuelan los agujeros de seguridad.
 */
class AuthApiController
{
    public function login(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $cuerpo = json_decode((string) file_get_contents('php://input'), true);
        $usuario = is_array($cuerpo) ? (string) ($cuerpo['username'] ?? '') : '';
        $clave = is_array($cuerpo) ? (string) ($cuerpo['password'] ?? '') : '';

        if ($usuario === '' || $clave === '') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'mustChangePassword' => false,
                'message' => 'Escribe tu usuario y tu contraseña.',
            ]);
            return;
        }

        // <<< Llamar aquí al servicio del Step 1, exactamente como lo llama
        // LoginController::login(). El resultado dice si entró y si debe cambiar clave.
        $resultado = SERVICIO_DEL_STEP_1;

        if (!$resultado['ok']) {
            http_response_code(401);
            // Mensaje único a propósito: distinguir "no existe" de "clave mala"
            // permite averiguar qué usuarios existen probando nombres.
            echo json_encode([
                'success' => false,
                'mustChangePassword' => false,
                'message' => 'Usuario o contraseña incorrectos.',
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'mustChangePassword' => (bool) ($_SESSION['must_change_password'] ?? false),
            'message' => null,
        ]);
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        session_destroy();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true]);
    }
}
```

- [ ] **Step 5: Registrar las rutas**

En `public/index.php`, añadir `'/api/auth/login'` y `'/api/auth/logout'` a `$publicRoutes`, y registrar:

```php
$router->post('/api/auth/login', [\App\Controllers\Api\AuthApiController::class, 'login']);
$router->post('/api/auth/logout', [\App\Controllers\Api\AuthApiController::class, 'logout']);
```

- [ ] **Step 6: Correr el test y verlo pasar**

```bash
docker compose exec app php tests/test_api_auth_contract.php
```

Esperado: `OK: contrato de /api/auth`

- [ ] **Step 7: Commit**

```bash
git add src/Controllers/Api/AuthApiController.php tests/test_api_auth_contract.php public/index.php
git commit -m "feat(api): entrar y salir en JSON"
```

---

### Task 3: Listar proyectos y elegir uno

**Files:**
- Create: `src/Controllers/Api/ProjectApiController.php`
- Modify: `public/index.php`
- Test: `tests/test_api_projects_contract.php`

**Interfaces:**
- Consumes: `App\Controllers\Core\ProjectSelectorController::enterProject(string $usuario, string $proyectoSeleccionado): void` — ya valida permiso y estado activo; se reusa.
- Produces: `GET /api/proyectos` → `{projects: [{id: int, name: string, role: string}]}`; `POST /api/proyectos/seleccionar` con `{name: string}` → `{success: bool, message: string|null}`. La tarea 9 depende de esta forma.

- [ ] **Step 1: Leer cómo lista proyectos el selector actual**

```bash
sed -n '25,75p' src/Controllers/Core/ProjectSelectorController.php
```

Anotar la consulta SQL exacta y las columnas que devuelve. El endpoint reusa esa consulta.

- [ ] **Step 2: Escribir el test que falla**

Crear `tests/test_api_projects_contract.php`:

```php
<?php
// @requiere: datos-proyecto

$base = getenv('APP_URL') ?: 'http://localhost:8081';
$fallos = 0;
$galletas = tempnam(sys_get_temp_dir(), 'proy');

function obtener(string $url, string $galletas): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_COOKIEJAR => $galletas,
        CURLOPT_COOKIEFILE => $galletas,
    ]);
    $cuerpo = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['codigo' => $codigo, 'json' => json_decode((string) $cuerpo, true)];
}

// Sin sesión: 401, no una lista vacía (que se leería como "no tienes proyectos").
$r = obtener("$base/api/proyectos", $galletas);
if ($r['codigo'] !== 401) {
    echo "FALLO: sin sesion esperaba 401, llego {$r['codigo']}\n";
    $fallos++;
}

// Con sesión: lista con la forma del contrato.
obtener("$base/dev/entrar?u=test.A", $galletas);
$r = obtener("$base/api/proyectos", $galletas);

if (!isset($r['json']['projects']) || !is_array($r['json']['projects'])) {
    echo "FALLO: falta la clave 'projects' o no es lista\n";
    $fallos++;
} elseif (count($r['json']['projects']) === 0) {
    echo "FALLO: test.A deberia ver al menos un proyecto sembrado\n";
    $fallos++;
} else {
    $primero = $r['json']['projects'][0];
    foreach (['id', 'name', 'role'] as $clave) {
        if (!array_key_exists($clave, $primero)) {
            echo "FALLO: al proyecto le falta la clave '$clave'\n";
            $fallos++;
        }
    }
    if (!is_int($primero['id'] ?? null)) {
        echo "FALLO: id debe ser entero, no texto\n";
        $fallos++;
    }
}
@unlink($galletas);

echo $fallos === 0 ? "OK: contrato de /api/proyectos\n" : "$fallos fallo(s)\n";
exit($fallos === 0 ? 0 : 1);
```

- [ ] **Step 3: Correrlo y ver que falla**

```bash
docker compose exec app php tests/test_api_projects_contract.php
```

Esperado: FALLO — la ruta no existe.

- [ ] **Step 4: Escribir el controlador**

Crear `src/Controllers/Api/ProjectApiController.php` con dos métodos, `index()` y `select()`, usando la consulta que anotaste en el Step 1. `index()` responde 401 y termina si `$_SESSION['usuario']` no existe; devuelve `['projects' => [...]]` con `id` casteado a `int`. `select()` lee `{name}` del cuerpo JSON y delega en `ProjectSelectorController::enterProject()`, que ya valida permiso y estado activo — **no se reimplementa esa validación**; luego responde `{success, message}` según haya quedado `$_SESSION['error']`.

- [ ] **Step 5: Registrar las rutas**

En `public/index.php`, junto a las demás rutas API (estas **no** van a `$publicRoutes`: exigen sesión):

```php
$router->get('/api/proyectos', [\App\Controllers\Api\ProjectApiController::class, 'index']);
$router->post('/api/proyectos/seleccionar', [\App\Controllers\Api\ProjectApiController::class, 'select']);
```

- [ ] **Step 6: Correr el test y verlo pasar**

```bash
docker compose exec app php tests/test_api_projects_contract.php
```

Esperado: `OK: contrato de /api/proyectos`

- [ ] **Step 7: Commit**

```bash
git add src/Controllers/Api/ProjectApiController.php tests/test_api_projects_contract.php public/index.php
git commit -m "feat(api): listar proyectos y elegir uno en JSON"
```

---

### Task 4: El esqueleto de la aplicación nueva

**Files:**
- Create: `frontend/package.json`, `frontend/vite.config.ts`, `frontend/tsconfig.json`, `frontend/index.html`, `frontend/src/main.tsx`, `frontend/src/App.tsx`, `frontend/AGENTS.md`
- Test: `frontend/src/App.test.tsx`

**Interfaces:**
- Produces: `npm --prefix frontend run build` deja el bundle en `public/app/`. `npm --prefix frontend test` corre Vitest. Las tareas 5 a 12 trabajan dentro de `frontend/`.

- [ ] **Step 1: Leer el patrón que ya funciona**

```bash
cat pdc-app/package.json
cat pdc-app/vite.config.ts
```

`frontend/` copia este patrón (mismo Vite, mismo destino bajo `public/`, mismo Vitest). Lo que cambia: el nombre, la carpeta de salida (`public/app/`), y que se añade Zod.

- [ ] **Step 2: Crear el proyecto**

```bash
cd frontend
npm init -y
npm install react react-dom react-router-dom zod
npm install -D vite @vitejs/plugin-react typescript @types/react @types/react-dom vitest @testing-library/react @testing-library/jest-dom jsdom
```

- [ ] **Step 3: Configurar Vite**

Crear `frontend/vite.config.ts`:

```ts
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// El bundle se publica bajo public/app/ para que el PHP lo sirva del mismo origen:
// misma cookie de sesión, sin CORS. Mismo patrón que pdc-app.
export default defineConfig({
  plugins: [react()],
  base: '/app/',
  build: {
    outDir: '../public/app',
    emptyOutDir: true,
  },
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./src/test-setup.ts'],
  },
});
```

Crear `frontend/src/test-setup.ts`:

```ts
import '@testing-library/jest-dom';
```

Añadir a `frontend/package.json` en `scripts`:

```json
{
  "dev": "vite",
  "build": "tsc && vite build",
  "test": "vitest run",
  "typecheck": "tsc --noEmit"
}
```

- [ ] **Step 4: Escribir el test que falla**

Crear `frontend/src/App.test.tsx`:

```tsx
import { render, screen } from '@testing-library/react';
import { App } from './App';

test('la aplicación monta y anuncia su nombre', () => {
  render(<App />);
  expect(screen.getByRole('heading', { name: /last planner/i })).toBeInTheDocument();
});
```

- [ ] **Step 5: Correrlo y ver que falla**

```bash
npm --prefix frontend test
```

Esperado: FALLO — `App` no existe.

- [ ] **Step 6: Escribir lo mínimo**

Crear `frontend/src/App.tsx`:

```tsx
export function App() {
  return <h1>Last Planner AIA</h1>;
}
```

Crear `frontend/src/main.tsx`:

```tsx
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { App } from './App';

const raiz = document.getElementById('root');
if (!raiz) throw new Error('Falta el nodo #root en index.html');

createRoot(raiz).render(
  <StrictMode>
    <App />
  </StrictMode>,
);
```

Crear `frontend/index.html`:

```html
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Last Planner AIA</title>
    <link rel="stylesheet" href="/css/tokens.css" />
    <link rel="stylesheet" href="/css/aia-design-system.css" />
  </head>
  <body>
    <div id="root"></div>
    <script type="module" src="/src/main.tsx"></script>
  </body>
</html>
```

- [ ] **Step 7: Correr el test y verlo pasar**

```bash
npm --prefix frontend test
```

Esperado: PASS, 1 test.

- [ ] **Step 8: Escribir las reglas del stack**

Crear `frontend/AGENTS.md`:

```markdown
# AGENTS.md — frontend React

Reglas propias de esta carpeta. Complementan el AGENTS.md de la raíz, que sigue mandando.

## Contratos

- **Nadie llama `fetch` directo.** Todo pasa por `src/lib/api/cliente.ts`, que valida la
  respuesta contra su esquema Zod y lanza un error que nombra endpoint y campo si no cuadra.
- **El esquema Zod es la única definición del tipo.** Los tipos TypeScript salen de él con
  `z.infer`. Nunca se escribe una interfaz a mano en paralelo a un esquema: se desincronizan.
- **Los colores, radios y sombras salen de `public/css/tokens.css`.** No se declara un color
  literal en ningún componente. Si falta un token, se añade allá y se documenta, no se inventa aquí.

## Estilo

- Archivos de ~300 líneas como guía, no como regla dura. Un archivo cohesionado de 340 está bien;
  uno de 800 casi nunca lo está.
- Nombres del dominio, en español, como en `GLOSARIO.md`: `SemanaComprometida`, no `WeekData`.
- Un archivo, una responsabilidad.

## Pruebas

- Vitest para lógica y componentes: `npm --prefix frontend test`.
- Cada endpoint nuevo lleva su prueba de contrato del lado PHP (`tests/test_api_*_contract.php`).
- **No hay gate de cobertura mínima**, a propósito: empuja a escribir pruebas que suben el número
  sin atrapar nada.
```

- [ ] **Step 9: Verificar que el build sale donde debe**

```bash
npm --prefix frontend run build && ls public/app/
```

Esperado: `index.html` y una carpeta `assets/`.

- [ ] **Step 10: Commit**

```bash
git add frontend/ public/app/ .gitignore
git commit -m "feat(frontend): esqueleto de la SPA con Vite, React, TS y Vitest"
```

---

### Task 5: El cliente HTTP que valida lo que llega

Es la pieza que hace que un cambio en el PHP se note en el acto y no en producción.

**Files:**
- Create: `frontend/src/lib/api/esquemas/sesion.ts`, `frontend/src/lib/api/cliente.ts`
- Test: `frontend/src/lib/api/cliente.test.ts`

**Interfaces:**
- Consumes: la forma de `GET /api/session` de la Task 1.
- Produces: `pedir<T>(ruta: string, esquema: ZodType<T>, opciones?: RequestInit): Promise<T>`, `EsquemaSesion`, y el tipo `Sesion`. Las tareas 7 a 11 lo usan.

- [ ] **Step 1: Escribir el test que falla**

Crear `frontend/src/lib/api/cliente.test.ts`:

```ts
import { z } from 'zod';
import { pedir } from './cliente';

const esquemaDePrueba = z.object({ nombre: z.string() });

afterEach(() => {
  vi.unstubAllGlobals();
});

test('devuelve los datos cuando la respuesta cumple el esquema', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response(JSON.stringify({ nombre: 'obra' }), { status: 200 }),
  ));

  await expect(pedir('/api/x', esquemaDePrueba)).resolves.toEqual({ nombre: 'obra' });
});

test('falla nombrando la ruta y el campo cuando el backend cambia la forma', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response(JSON.stringify({ nombre: 123 }), { status: 200 }),
  ));

  // El mensaje debe alcanzar para diagnosticar sin abrir el navegador: es el
  // valor entero de tener esquemas en la frontera.
  await expect(pedir('/api/x', esquemaDePrueba)).rejects.toThrow(/\/api\/x/);
  await expect(pedir('/api/x', esquemaDePrueba)).rejects.toThrow(/nombre/);
});

test('un 500 falla como error de red, no como forma inválida', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response('se cayo', { status: 500 }),
  ));

  await expect(pedir('/api/x', esquemaDePrueba)).rejects.toThrow(/500/);
});
```

- [ ] **Step 2: Correrlo y ver que falla**

```bash
npm --prefix frontend test
```

Esperado: FALLO — `cliente.ts` no existe.

- [ ] **Step 3: Escribir el cliente**

Crear `frontend/src/lib/api/cliente.ts`:

```ts
import type { ZodType } from 'zod';

/**
 * El único sitio que llama `fetch`.
 *
 * Valida cada respuesta contra su esquema: si el PHP cambia un campo, esto falla
 * aquí y con nombre propio, en vez de romperse tres pantallas más allá.
 */
export async function pedir<T>(
  ruta: string,
  esquema: ZodType<T>,
  opciones: RequestInit = {},
): Promise<T> {
  const respuesta = await fetch(ruta, {
    ...opciones,
    headers: {
      Accept: 'application/json',
      ...(opciones.body ? { 'Content-Type': 'application/json' } : {}),
      ...opciones.headers,
    },
    // Mismo origen: la cookie de sesión del PHP viaja sola.
    credentials: 'same-origin',
  });

  if (!respuesta.ok) {
    throw new Error(`${ruta} respondió ${respuesta.status}`);
  }

  const crudo = await respuesta.json();
  const resultado = esquema.safeParse(crudo);

  if (!resultado.success) {
    const campos = resultado.error.issues
      .map((issue) => `${issue.path.join('.') || '(raíz)'}: ${issue.message}`)
      .join('; ');
    throw new Error(`${ruta} devolvió una forma inesperada — ${campos}`);
  }

  return resultado.data;
}
```

Crear `frontend/src/lib/api/esquemas/sesion.ts`:

```ts
import { z } from 'zod';

export const EsquemaUsuario = z.object({
  username: z.string(),
  displayName: z.string(),
  role: z.string(),
});

export const EsquemaProyecto = z.object({
  id: z.number().int(),
  name: z.string(),
});

export const EsquemaSesion = z.object({
  authenticated: z.boolean(),
  user: EsquemaUsuario.nullable(),
  project: EsquemaProyecto.nullable(),
  // Las capacidades crecen con el tiempo; se aceptan todas las booleanas que
  // lleguen en vez de fijar la lista aquí y romper cada vez que RbacManager suma una.
  capabilities: z.record(z.string(), z.boolean()),
});

export type Sesion = z.infer<typeof EsquemaSesion>;
export type Proyecto = z.infer<typeof EsquemaProyecto>;
```

- [ ] **Step 4: Correr el test y verlo pasar**

```bash
npm --prefix frontend test
```

Esperado: PASS, 4 tests.

- [ ] **Step 5: Commit**

```bash
git add frontend/src/lib/
git commit -m "feat(frontend): cliente HTTP que valida contra esquemas Zod"
```

---

### Task 6: La frontera entre los dos mundos

**Files:**
- Create: `src/Core/SpaRouter.php`
- Modify: `public/index.php`
- Test: `tests/test_spa_frontera.php`

**Interfaces:**
- Produces: `App\Core\SpaRouter::sirveLaSpa(string $ruta): bool` y `App\Core\SpaRouter::RUTAS_MIGRADAS`. La Task 12 y los frentes siguientes añaden rutas a esa constante.

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/test_spa_frontera.php`:

```php
<?php
// @requiere: puro

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\SpaRouter;

$fallos = 0;

// Las rutas del shell las sirve la SPA.
foreach (['/app', '/app/login', '/app/proyectos'] as $ruta) {
    if (!SpaRouter::sirveLaSpa($ruta)) {
        echo "FALLO: '$ruta' deberia servirla la SPA\n";
        $fallos++;
    }
}

// Las viejas NO. Si esto se rompe, el sitio entero deja de funcionar.
foreach (['/login', '/proyectos', '/programa-general', '/plan-compras', '/dashboard'] as $ruta) {
    if (SpaRouter::sirveLaSpa($ruta)) {
        echo "FALLO: '$ruta' es del sitio PHP y la SPA se la esta robando\n";
        $fallos++;
    }
}

// La API nunca la sirve la SPA, ni aunque empiece por /app.
foreach (['/api/session', '/api/proyectos'] as $ruta) {
    if (SpaRouter::sirveLaSpa($ruta)) {
        echo "FALLO: '$ruta' es API, no debe devolver el HTML de la SPA\n";
        $fallos++;
    }
}

// Los assets del bundle tampoco: los sirve el servidor como archivos.
if (SpaRouter::sirveLaSpa('/app/assets/index.js')) {
    echo "FALLO: los assets del bundle no deben devolver el HTML\n";
    $fallos++;
}

echo $fallos === 0 ? "OK: frontera SPA/PHP\n" : "$fallos fallo(s)\n";
exit($fallos === 0 ? 0 : 1);
```

- [ ] **Step 2: Correrlo y ver que falla**

```bash
docker compose exec app php tests/test_spa_frontera.php
```

Esperado: FALLO — `SpaRouter` no existe.

- [ ] **Step 3: Escribir la frontera**

Crear `src/Core/SpaRouter.php`:

```php
<?php

namespace App\Core;

/**
 * Decide qué rutas sirve la SPA y cuáles siguen siendo del sitio PHP.
 *
 * Es el ÚNICO punto de decisión de la convivencia. Cada módulo que cruza a React
 * añade su prefijo a RUTAS_MIGRADAS y no toca nada más.
 *
 * Convención deliberada: todo lo de la SPA vive bajo /app. Así el sitio viejo
 * conserva sus URLs exactas y ningún enlace existente se rompe durante la
 * convivencia, que puede durar meses.
 */
class SpaRouter
{
    /** Prefijos que ya sirve la SPA. Crece un renglón por módulo migrado. */
    public const RUTAS_MIGRADAS = ['/app'];

    public static function sirveLaSpa(string $ruta): bool
    {
        // La API responde JSON siempre, aunque cuelgue de un prefijo migrado.
        if (str_starts_with($ruta, '/api/')) {
            return false;
        }

        // Los archivos del bundle los sirve el servidor web, no el front controller.
        if (str_contains($ruta, '/assets/')) {
            return false;
        }

        foreach (self::RUTAS_MIGRADAS as $prefijo) {
            if ($ruta === $prefijo || str_starts_with($ruta, $prefijo . '/')) {
                return true;
            }
        }

        return false;
    }
}
```

- [ ] **Step 4: Conectarla al front controller**

En `public/index.php`, **justo antes** del despacho del router (y después de `SessionMiddleware::check()`), añadir:

```php
// La SPA maneja su propio enrutado en el navegador: cualquier ruta suya
// devuelve el mismo HTML, y React decide qué pantalla mostrar.
if (\App\Core\SpaRouter::sirveLaSpa($uri)) {
    require PROJECT_ROOT . '/public/app/index.html';
    exit;
}
```

Usar el nombre de variable que `index.php` ya tenga para la ruta pedida (búscalo: es el que se le pasa al router). No introducir una variable nueva.

- [ ] **Step 5: Correr el test y verlo pasar**

```bash
docker compose exec app php tests/test_spa_frontera.php
```

Esperado: `OK: frontera SPA/PHP`

- [ ] **Step 6: Verificar que el sitio viejo sigue vivo**

```bash
docker compose exec app php scripts/run-php-tests.php --nivel=http
```

Esperado: la suite en verde. Esta tarea toca el front controller, que es de donde cuelga todo: si algo se rompió, es aquí.

- [ ] **Step 7: Commit**

```bash
git add src/Core/SpaRouter.php tests/test_spa_frontera.php public/index.php
git commit -m "feat(core): frontera que decide si una ruta la sirve la SPA o el PHP"
```

---

### Task 7: Enrutado y guardas de sesión

**Files:**
- Create: `frontend/src/shell/rutas.tsx`, `frontend/src/shell/useSesion.ts`
- Modify: `frontend/src/App.tsx`
- Test: `frontend/src/shell/rutas.test.tsx`

**Interfaces:**
- Consumes: `pedir()` y `EsquemaSesion` de la Task 5.
- Produces: `useSesion(): {sesion: Sesion|null, cargando: boolean, recargar: () => Promise<void>}`. Las tareas 8 a 11 lo consumen.

- [ ] **Step 1: Escribir el test que falla**

Crear `frontend/src/shell/rutas.test.tsx`:

```tsx
import { render, screen, waitFor } from '@testing-library/react';
import { Rutas } from './rutas';

function responderSesion(cuerpo: unknown) {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response(JSON.stringify(cuerpo), { status: 200 }),
  ));
}

afterEach(() => vi.unstubAllGlobals());

test('sin sesión muestra el login', async () => {
  responderSesion({ authenticated: false, user: null, project: null, capabilities: {} });
  render(<Rutas />);
  await waitFor(() => expect(screen.getByRole('heading', { name: /entrar/i })).toBeInTheDocument());
});

test('con sesión pero sin proyecto muestra el selector', async () => {
  responderSesion({
    authenticated: true,
    user: { username: 'test.A', displayName: 'Ana', role: 'A' },
    project: null,
    capabilities: { canManageWeeks: true },
  });
  render(<Rutas />);
  await waitFor(() => expect(screen.getByRole('heading', { name: /proyecto/i })).toBeInTheDocument());
});

test('con sesión y proyecto muestra la aplicación', async () => {
  responderSesion({
    authenticated: true,
    user: { username: 'test.A', displayName: 'Ana', role: 'A' },
    project: { id: 1, name: 'Da Porto' },
    capabilities: { canManageWeeks: true },
  });
  render(<Rutas />);
  await waitFor(() => expect(screen.getByRole('navigation')).toBeInTheDocument());
});
```

- [ ] **Step 2: Correrlo y ver que falla**

```bash
npm --prefix frontend test
```

Esperado: FALLO — `rutas.tsx` no existe.

- [ ] **Step 3: Escribir el hook de sesión**

Crear `frontend/src/shell/useSesion.ts`:

```ts
import { useCallback, useEffect, useState } from 'react';
import { pedir } from '../lib/api/cliente';
import { EsquemaSesion, type Sesion } from '../lib/api/esquemas/sesion';

/**
 * La sesión que el PHP reporta. Se consulta al arrancar y se recarga después de
 * entrar o de elegir proyecto — es la fuente de verdad, no el estado local.
 */
export function useSesion() {
  const [sesion, setSesion] = useState<Sesion | null>(null);
  const [cargando, setCargando] = useState(true);

  const recargar = useCallback(async () => {
    setCargando(true);
    try {
      setSesion(await pedir('/api/session', EsquemaSesion));
    } finally {
      setCargando(false);
    }
  }, []);

  useEffect(() => {
    void recargar();
  }, [recargar]);

  return { sesion, cargando, recargar };
}
```

- [ ] **Step 4: Escribir el enrutado**

Crear `frontend/src/shell/rutas.tsx`:

```tsx
import { useSesion } from './useSesion';
import { PantallaLogin } from './PantallaLogin';
import { SelectorProyecto } from './SelectorProyecto';
import { NavegacionLateral } from './NavegacionLateral';

/**
 * Tres estados, en este orden: sin sesión, sesión sin proyecto, y todo listo.
 * El orden importa — sin proyecto en sesión, ningún módulo puede consultar datos.
 */
export function Rutas() {
  const { sesion, cargando, recargar } = useSesion();

  if (cargando) {
    return <p role="status">Cargando…</p>;
  }

  if (!sesion?.authenticated) {
    return <PantallaLogin alEntrar={recargar} />;
  }

  if (!sesion.project) {
    return <SelectorProyecto alElegir={recargar} />;
  }

  return (
    <>
      <NavegacionLateral sesion={sesion} />
      <main>
        <h1>{sesion.project.name}</h1>
      </main>
    </>
  );
}
```

Modificar `frontend/src/App.tsx`:

```tsx
import { Rutas } from './shell/rutas';

export function App() {
  return <Rutas />;
}
```

Borrar de `frontend/src/App.test.tsx` el test del encabezado: ya no aplica, `App` delega en `Rutas` y sus estados los cubre `rutas.test.tsx`.

- [ ] **Step 5: Correr el test y verlo pasar (tras las tareas 8-10)**

Estos tres tests **fallarán hasta que existan `PantallaLogin`, `SelectorProyecto` y `NavegacionLateral`** (tareas 8, 9 y 10). Es esperado y deliberado: son el contrato que esas tres tareas deben cumplir.

```bash
npm --prefix frontend test -- rutas
```

Esperado ahora: FALLO por importaciones que no existen. Esperado tras la Task 10: PASS, 3 tests.

- [ ] **Step 6: Commit**

```bash
git add frontend/src/shell/rutas.tsx frontend/src/shell/useSesion.ts frontend/src/shell/rutas.test.tsx frontend/src/App.tsx frontend/src/App.test.tsx
git commit -m "feat(shell): enrutado por estado de sesion y proyecto"
```

---

### Task 8: La pantalla de entrar

**Files:**
- Create: `frontend/src/shell/PantallaLogin.tsx`
- Test: `frontend/src/shell/PantallaLogin.test.tsx`

**Interfaces:**
- Consumes: `pedir()` (Task 5), `POST /api/auth/login` (Task 2).
- Produces: `<PantallaLogin alEntrar={() => Promise<void>} />` — el contrato que `rutas.tsx` ya usa.

- [ ] **Step 1: Escribir el test que falla**

Crear `frontend/src/shell/PantallaLogin.test.tsx`:

```tsx
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { PantallaLogin } from './PantallaLogin';

afterEach(() => vi.unstubAllGlobals());

test('entra y avisa al shell cuando las credenciales sirven', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response(JSON.stringify({ success: true, mustChangePassword: false, message: null }), { status: 200 }),
  ));
  const alEntrar = vi.fn();
  render(<PantallaLogin alEntrar={alEntrar} />);

  await userEvent.type(screen.getByLabelText(/usuario/i), 'test.A');
  await userEvent.type(screen.getByLabelText(/contraseña/i), 'clave');
  await userEvent.click(screen.getByRole('button', { name: /entrar/i }));

  await waitFor(() => expect(alEntrar).toHaveBeenCalled());
});

test('muestra el mensaje del servidor cuando no sirven', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response(JSON.stringify({ success: false, mustChangePassword: false, message: 'Usuario o contraseña incorrectos.' }), { status: 401 }),
  ));
  const alEntrar = vi.fn();
  render(<PantallaLogin alEntrar={alEntrar} />);

  await userEvent.type(screen.getByLabelText(/usuario/i), 'test.A');
  await userEvent.type(screen.getByLabelText(/contraseña/i), 'mala');
  await userEvent.click(screen.getByRole('button', { name: /entrar/i }));

  await waitFor(() => expect(screen.getByRole('alert')).toHaveTextContent(/incorrectos/i));
  expect(alEntrar).not.toHaveBeenCalled();
});

test('el enlace de recuperar clave lleva a la pantalla PHP, que no se migró', () => {
  render(<PantallaLogin alEntrar={vi.fn()} />);
  expect(screen.getByRole('link', { name: /olvid/i })).toHaveAttribute('href', '/password/forgot');
});
```

- [ ] **Step 2: Correrlo y ver que falla**

```bash
npm --prefix frontend test -- PantallaLogin
```

Esperado: FALLO — el componente no existe.

- [ ] **Step 3: Escribir el componente**

Crear `frontend/src/shell/PantallaLogin.tsx`:

```tsx
import { useState, type FormEvent } from 'react';
import { z } from 'zod';
import { pedir } from '../lib/api/cliente';

const EsquemaLogin = z.object({
  success: z.boolean(),
  mustChangePassword: z.boolean(),
  message: z.string().nullable(),
});

export function PantallaLogin({ alEntrar }: { alEntrar: () => Promise<void> }) {
  const [usuario, setUsuario] = useState('');
  const [clave, setClave] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [enviando, setEnviando] = useState(false);

  async function enviar(evento: FormEvent) {
    evento.preventDefault();
    setEnviando(true);
    setError(null);

    try {
      const respuesta = await pedir('/api/auth/login', EsquemaLogin, {
        method: 'POST',
        body: JSON.stringify({ username: usuario, password: clave }),
      });

      if (!respuesta.success) {
        setError(respuesta.message ?? 'No se pudo entrar.');
        return;
      }

      // El cambio de clave obligatorio vive en el PHP (R12): se sale del shell.
      if (respuesta.mustChangePassword) {
        window.location.href = '/login';
        return;
      }

      await alEntrar();
    } catch (fallo) {
      // Un 401 del cliente llega como excepción; el mensaje del servidor se
      // perdió, así que se dice lo genérico en vez de mostrar un error técnico.
      setError(fallo instanceof Error && fallo.message.includes('401')
        ? 'Usuario o contraseña incorrectos.'
        : 'No pudimos conectar. Intenta de nuevo.');
    } finally {
      setEnviando(false);
    }
  }

  return (
    <form onSubmit={enviar} className="aia-card">
      <h1>Entrar</h1>

      {error && <p role="alert" className="aia-alert aia-alert--error">{error}</p>}

      <label htmlFor="usuario">Usuario</label>
      <input
        id="usuario"
        className="aia-input"
        value={usuario}
        onChange={(e) => setUsuario(e.target.value)}
        autoComplete="username"
        required
      />

      <label htmlFor="clave">Contraseña</label>
      <input
        id="clave"
        className="aia-input"
        type="password"
        value={clave}
        onChange={(e) => setClave(e.target.value)}
        autoComplete="current-password"
        required
      />

      <button type="submit" className="aia-btn aia-btn--primary" disabled={enviando}>
        {enviando ? 'Entrando…' : 'Entrar'}
      </button>

      {/* Sigue en PHP a propósito (R12): el camino de correo y tokens no se
          migra en este frente. */}
      <a href="/password/forgot">¿Olvidaste tu contraseña?</a>
    </form>
  );
}
```

- [ ] **Step 4: Correr el test y verlo pasar**

```bash
npm --prefix frontend test -- PantallaLogin
```

Esperado: PASS, 3 tests.

- [ ] **Step 5: Commit**

```bash
git add frontend/src/shell/PantallaLogin.tsx frontend/src/shell/PantallaLogin.test.tsx
git commit -m "feat(shell): pantalla de entrar"
```

---

### Task 9: El selector de proyecto

**Files:**
- Create: `frontend/src/shell/SelectorProyecto.tsx`
- Test: `frontend/src/shell/SelectorProyecto.test.tsx`

**Interfaces:**
- Consumes: `pedir()` (Task 5), `GET /api/proyectos` y `POST /api/proyectos/seleccionar` (Task 3).
- Produces: `<SelectorProyecto alElegir={() => Promise<void>} />` — el contrato que `rutas.tsx` ya usa.

- [ ] **Step 1: Escribir el test que falla**

Crear `frontend/src/shell/SelectorProyecto.test.tsx`:

```tsx
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { SelectorProyecto } from './SelectorProyecto';

function responder(porRuta: Record<string, unknown>) {
  vi.stubGlobal('fetch', vi.fn((ruta: string) =>
    Promise.resolve(new Response(JSON.stringify(porRuta[ruta] ?? {}), { status: 200 })),
  ));
}

afterEach(() => vi.unstubAllGlobals());

test('lista los proyectos del usuario', async () => {
  responder({
    '/api/proyectos': { projects: [
      { id: 1, name: 'Da Porto', role: 'A' },
      { id: 2, name: 'Aeropuerto', role: 'R' },
    ] },
  });
  render(<SelectorProyecto alElegir={vi.fn()} />);

  await waitFor(() => expect(screen.getByRole('button', { name: /da porto/i })).toBeInTheDocument());
  expect(screen.getByRole('button', { name: /aeropuerto/i })).toBeInTheDocument();
});

test('al elegir uno, avisa al shell', async () => {
  responder({
    '/api/proyectos': { projects: [{ id: 1, name: 'Da Porto', role: 'A' }] },
    '/api/proyectos/seleccionar': { success: true, message: null },
  });
  const alElegir = vi.fn();
  render(<SelectorProyecto alElegir={alElegir} />);

  await waitFor(() => screen.getByRole('button', { name: /da porto/i }));
  await userEvent.click(screen.getByRole('button', { name: /da porto/i }));

  await waitFor(() => expect(alElegir).toHaveBeenCalled());
});

test('sin proyectos lo dice, en vez de mostrar una lista vacía', async () => {
  responder({ '/api/proyectos': { projects: [] } });
  render(<SelectorProyecto alElegir={vi.fn()} />);

  await waitFor(() => expect(screen.getByText(/no tienes proyectos/i)).toBeInTheDocument());
});
```

- [ ] **Step 2: Correrlo y ver que falla**

```bash
npm --prefix frontend test -- SelectorProyecto
```

Esperado: FALLO — el componente no existe.

- [ ] **Step 3: Escribir el componente**

Crear `frontend/src/shell/SelectorProyecto.tsx`:

```tsx
import { useEffect, useState } from 'react';
import { z } from 'zod';
import { pedir } from '../lib/api/cliente';

const EsquemaListaProyectos = z.object({
  projects: z.array(z.object({
    id: z.number().int(),
    name: z.string(),
    role: z.string(),
  })),
});

const EsquemaSeleccion = z.object({
  success: z.boolean(),
  message: z.string().nullable(),
});

type ProyectoDeLista = z.infer<typeof EsquemaListaProyectos>['projects'][number];

export function SelectorProyecto({ alElegir }: { alElegir: () => Promise<void> }) {
  const [proyectos, setProyectos] = useState<ProyectoDeLista[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    void (async () => {
      try {
        const respuesta = await pedir('/api/proyectos', EsquemaListaProyectos);
        setProyectos(respuesta.projects);
      } catch {
        setError('No pudimos cargar tus proyectos.');
      }
    })();
  }, []);

  async function elegir(nombre: string) {
    setError(null);
    try {
      const respuesta = await pedir('/api/proyectos/seleccionar', EsquemaSeleccion, {
        method: 'POST',
        body: JSON.stringify({ name: nombre }),
      });

      if (!respuesta.success) {
        setError(respuesta.message ?? 'No pudimos abrir ese proyecto.');
        return;
      }

      await alElegir();
    } catch {
      setError('No pudimos abrir ese proyecto.');
    }
  }

  if (error) return <p role="alert">{error}</p>;
  if (proyectos === null) return <p role="status">Cargando proyectos…</p>;

  return (
    <section className="aia-card">
      <h1>Elige un proyecto</h1>

      {proyectos.length === 0 ? (
        <p>No tienes proyectos asignados. Pídele acceso a un administrador.</p>
      ) : (
        <ul>
          {proyectos.map((proyecto) => (
            <li key={proyecto.id}>
              <button
                type="button"
                className="aia-btn aia-btn--secondary"
                onClick={() => void elegir(proyecto.name)}
              >
                {proyecto.name}
              </button>
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}
```

- [ ] **Step 4: Correr el test y verlo pasar**

```bash
npm --prefix frontend test -- SelectorProyecto
```

Esperado: PASS, 3 tests.

- [ ] **Step 5: Commit**

```bash
git add frontend/src/shell/SelectorProyecto.tsx frontend/src/shell/SelectorProyecto.test.tsx
git commit -m "feat(shell): selector de proyecto"
```

---

### Task 10: La navegación lateral

**Files:**
- Create: `frontend/src/shell/NavegacionLateral.tsx`
- Test: `frontend/src/shell/NavegacionLateral.test.tsx`

**Interfaces:**
- Consumes: el tipo `Sesion` de la Task 5.
- Produces: `<NavegacionLateral sesion={Sesion} />` — el contrato que `rutas.tsx` ya usa.

- [ ] **Step 1: Leer qué entradas tiene el sidebar actual**

```bash
grep -oE "'[a-zA-Z ]+'" views/partials/shell_sidebar.php | head -20
```

Anotar las entradas reales y a qué ruta apunta cada una. La navegación nueva las reproduce; **no se inventan entradas ni se renombran**.

- [ ] **Step 2: Escribir el test que falla**

Crear `frontend/src/shell/NavegacionLateral.test.tsx`:

```tsx
import { render, screen } from '@testing-library/react';
import { NavegacionLateral } from './NavegacionLateral';
import type { Sesion } from '../lib/api/esquemas/sesion';

const sesionAdmin: Sesion = {
  authenticated: true,
  user: { username: 'test.A', displayName: 'Ana', role: 'A' },
  project: { id: 1, name: 'Da Porto' },
  capabilities: { canManageWeeks: true, canManageGeneralProgram: true },
};

const sesionVisualizador: Sesion = {
  ...sesionAdmin,
  user: { username: 'test.V', displayName: 'Víctor', role: 'V' },
  capabilities: { canManageWeeks: false, canManageGeneralProgram: false },
};

test('es un landmark de navegación y muestra el proyecto activo', () => {
  render(<NavegacionLateral sesion={sesionAdmin} />);
  expect(screen.getByRole('navigation')).toBeInTheDocument();
  expect(screen.getByText('Da Porto')).toBeInTheDocument();
});

test('los módulos aún no migrados enlazan al sitio PHP', () => {
  render(<NavegacionLateral sesion={sesionAdmin} />);
  const enlace = screen.getByRole('link', { name: /programa general/i });
  // Sin /app delante: durante la convivencia se cruza la frontera por URL.
  expect(enlace).toHaveAttribute('href', '/programa-general');
});

test('esconde lo que el rol no puede hacer', () => {
  render(<NavegacionLateral sesion={sesionVisualizador} />);
  expect(screen.queryByRole('link', { name: /programa general/i })).not.toBeInTheDocument();
});
```

- [ ] **Step 3: Correrlo y ver que falla**

```bash
npm --prefix frontend test -- NavegacionLateral
```

Esperado: FALLO — el componente no existe.

- [ ] **Step 4: Escribir el componente**

Crear `frontend/src/shell/NavegacionLateral.tsx`. Debe: declarar las entradas del Step 1 como una lista de `{etiqueta, ruta, capacidad}`; filtrar por `sesion.capabilities[capacidad]`; usar `<nav>` con `aria-label`; mostrar `sesion.project.name` y `sesion.user.displayName`; y usar las clases `aia-navigation` y `aia-sidebar__*` que el design system ya define — **ningún color literal**. Las rutas de módulos no migrados van sin prefijo `/app`.

- [ ] **Step 5: Correr el test y verlo pasar**

```bash
npm --prefix frontend test -- NavegacionLateral
```

Esperado: PASS, 3 tests.

- [ ] **Step 6: Ahora sí, el enrutado completo**

```bash
npm --prefix frontend test
```

Esperado: PASS en todo, incluidos los 3 tests de `rutas.test.tsx` que quedaron esperando desde la Task 7.

- [ ] **Step 7: Commit**

```bash
git add frontend/src/shell/NavegacionLateral.tsx frontend/src/shell/NavegacionLateral.test.tsx
git commit -m "feat(shell): navegacion lateral con filtro por capacidades"
```

---

### Task 11: El conmutador de tema

Cierra D12 de la spec de temas: claro es el tema de entrada, y por primera vez se cumple de verdad — el shell React no carga `theme.js`, que es lo que lo deshacía en el sitio viejo.

**Files:**
- Create: `frontend/src/shell/ConmutadorTema.tsx`, `frontend/src/shell/tema.ts`
- Modify: `frontend/src/shell/NavegacionLateral.tsx`, `frontend/index.html`
- Test: `frontend/src/shell/tema.test.ts`

**Interfaces:**
- Produces: `leerTemaGuardado(): 'claro'|'oscuro'`, `aplicarTema(tema): void`.

- [ ] **Step 1: Escribir el test que falla**

Crear `frontend/src/shell/tema.test.ts`:

```ts
import { aplicarTema, leerTemaGuardado } from './tema';

beforeEach(() => {
  localStorage.clear();
  document.documentElement.removeAttribute('data-aia-theme');
});

test('el tema de entrada es claro (D12)', () => {
  expect(leerTemaGuardado()).toBe('claro');
});

test('recuerda lo que el usuario eligió', () => {
  aplicarTema('oscuro');
  expect(leerTemaGuardado()).toBe('oscuro');
});

test('escribe el atributo que el CSS lee', () => {
  aplicarTema('oscuro');
  expect(document.documentElement.getAttribute('data-aia-theme')).toBe('dark');
  aplicarTema('claro');
  expect(document.documentElement.getAttribute('data-aia-theme')).toBe('light');
});

test('un valor corrupto en el almacenamiento no rompe: cae al claro', () => {
  localStorage.setItem('aia-theme', 'fucsia');
  expect(leerTemaGuardado()).toBe('claro');
});
```

- [ ] **Step 2: Correrlo y ver que falla**

```bash
npm --prefix frontend test -- tema
```

Esperado: FALLO — `tema.ts` no existe.

- [ ] **Step 3: Escribir la lógica**

Crear `frontend/src/shell/tema.ts`:

```ts
export type Tema = 'claro' | 'oscuro';

// La misma clave que usa el sitio viejo, para que la preferencia sobreviva al
// cruzar la frontera durante la convivencia.
const CLAVE = 'aia-theme';

// El CSS lee 'light'/'dark'; adentro se habla en español.
const AL_CSS: Record<Tema, string> = { claro: 'light', oscuro: 'dark' };

export function leerTemaGuardado(): Tema {
  try {
    return localStorage.getItem(CLAVE) === 'dark' ? 'oscuro' : 'claro';
  } catch {
    // Navegación privada o almacenamiento bloqueado: el default manda.
    return 'claro';
  }
}

export function aplicarTema(tema: Tema): void {
  document.documentElement.setAttribute('data-aia-theme', AL_CSS[tema]);
  document.documentElement.classList.toggle('aia-theme-dark', tema === 'oscuro');
  try {
    localStorage.setItem(CLAVE, AL_CSS[tema]);
  } catch {
    // Sin persistencia, pero la página actual queda bien.
  }
}
```

- [ ] **Step 4: Correr el test y verlo pasar**

```bash
npm --prefix frontend test -- tema
```

Esperado: PASS, 4 tests.

- [ ] **Step 5: Escribir el botón y montarlo**

Crear `frontend/src/shell/ConmutadorTema.tsx`: un `<button>` con `aria-label` que alterne entre los dos temas usando `aplicarTema`, con estado inicial de `leerTemaGuardado()`. Montarlo dentro de `NavegacionLateral`.

Añadir a `frontend/index.html`, **dentro de `<head>` y antes de los `<link>` de CSS**, el guion anti-parpadeo:

```html
<script>
  // Antes de pintar: sin esto, la página nace clara y salta a oscura.
  try {
    var t = localStorage.getItem('aia-theme');
    document.documentElement.setAttribute('data-aia-theme', t === 'dark' ? 'dark' : 'light');
    if (t === 'dark') document.documentElement.classList.add('aia-theme-dark');
  } catch (e) {}
</script>
```

- [ ] **Step 6: Verificar en el navegador**

```bash
npm --prefix frontend run build
```

Abrir `http://localhost:8081/dev/entrar?u=test.A` y luego `http://localhost:8081/app`. Comprobar: arranca en claro, el botón lo cambia, y al recargar se conserva la elección sin parpadeo.

- [ ] **Step 7: Commit**

```bash
git add frontend/src/shell/tema.ts frontend/src/shell/tema.test.ts frontend/src/shell/ConmutadorTema.tsx frontend/src/shell/NavegacionLateral.tsx frontend/index.html
git commit -m "feat(shell): conmutador de tema con claro de entrada"
```

---

### Task 12: El CI y el cierre

**Files:**
- Modify: `.github/workflows/ci.yml`, `package.json` (raíz), `CHANGELOG.md`, `TASKS.md`, `README.md`

- [ ] **Step 1: Atajos desde la raíz**

Añadir a `package.json` de la raíz, en `scripts`:

```json
{
  "frontend:build": "npm --prefix frontend run build",
  "frontend:test": "npm --prefix frontend test",
  "frontend:typecheck": "npm --prefix frontend run typecheck"
}
```

- [ ] **Step 2: Añadir el gate al CI**

En `.github/workflows/ci.yml`, dentro del job `design-system-static` (que ya corre en Node y no necesita Docker), añadir después del paso de `npm ci`:

```yaml
      - name: Instalar dependencias del frontend
        run: npm ci --prefix frontend

      - name: Comprobar tipos del frontend
        run: npm run frontend:typecheck

      - name: Correr las pruebas del frontend
        run: npm run frontend:test
```

- [ ] **Step 3: Verificar la suite completa, local**

```bash
npm --prefix frontend test
npm --prefix frontend run typecheck
docker compose exec app php scripts/run-php-tests.php --nivel=http
npm run test:design-system:static
```

Los cuatro en verde. **Leer cada código de salida por separado**, sin encadenar con `&&`.

- [ ] **Step 4: Verificar el flujo completo en el navegador**

Con la pila corriendo, comprobar en este orden:

1. `http://localhost:8081/app` sin sesión → muestra la pantalla de entrar.
2. `http://localhost:8081/dev/entrar?u=test.A` y volver a `/app` → muestra el selector de proyecto.
3. Elegir un proyecto → aparece la navegación lateral con el proyecto activo.
4. `http://localhost:8081/programa-general` → sigue sirviendo la vista PHP de siempre, intacta.
5. Repetir el paso 3 con `?u=test.V` → la navegación esconde lo que ese rol no puede hacer.

El paso 4 es el importante: comprueba que la frontera no se robó el sitio viejo. El 5 cubre el rol permitido y el denegado que exige `AGENTS.md` cuando se toca RBAC.

- [ ] **Step 5: Actualizar la wiki del proyecto**

- `CHANGELOG.md`: entrada del shell mínimo bajo `Added`, formato Keep a Changelog.
- `TASKS.md`: cerrar la entrada del frente y anotar lo que queda (migrar `password-forgot`/`password-reset`, decidir los goldens durante la convivencia).
- `README.md`: cómo correr `frontend/` en desarrollo.

Invocar la skill `llm-wiki` antes de escribir, como manda `CLAUDE.md`.

- [ ] **Step 6: Commit y PR**

```bash
git add -A
git commit -m "chore(ci): gate de tipos y pruebas del frontend"
git push -u origin shell-minimo-react
gh pr create --base main --title "feat: shell minimo React" --body "..."
```

El cuerpo del PR resume qué se verificó y con qué salida. **CI en verde es el gate** — un PR rojo no se mergea.

---

## Notas para quien ejecute

**El orden importa.** Las tareas 1-3 (endpoints PHP) no dependen de nada y pueden hacerse en cualquier orden entre sí, pero las 5-11 sí dependen de ellas. La Task 7 deja tres tests fallando a propósito hasta la Task 10 — está documentado en su Step 5, no es un error.

**Lo que este plan NO hace, y es deliberado:** migrar módulos (eso es el frente 2), tocar `pdc-app` o `ct-app` (R4), migrar recuperación de clave (R12), ni montar un catálogo visual de componentes (R11).

**Si algo del código real contradice este plan**, gana el código: anótalo y sigue. Los tres puntos más probables son la firma del servicio de autenticación (Task 2, Step 1), la consulta de proyectos (Task 3, Step 1) y las entradas del sidebar (Task 10, Step 1) — por eso los tres empiezan leyendo el código en vez de asumir.
