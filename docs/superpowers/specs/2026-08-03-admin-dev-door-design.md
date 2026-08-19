---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-03
areas: [admin]
fuente: docs/superpowers/specs/2026-08-03-admin-dev-door-design.md
resumen: Igual que src/Core/DevDoor.php (GET /dev/entrar) para la app principal, una ruta GET /admin/dev/entrar?u=<cuenta> que abra una sesión válida del panel admin/…
---

# Puerta de servicio de desarrollo para `admin/`

Fecha: 2026-08-03
Estado: propuesta, pendiente de aprobación explícita del usuario

## Objetivo

Igual que `src/Core/DevDoor.php` (`GET /dev/entrar`) para la app principal, una ruta
`GET /admin/dev/entrar?u=<cuenta>` que abra una sesión válida del panel `admin/` sin teclear
credenciales, imposible de alcanzar fuera de desarrollo, sin permisos por encima de los de
la cuenta usada.

## admin/ comparte `.env` y `APP_ENV` con la app principal

`admin/public/index.php:14` define `ADMIN_PROJECT_ROOT = __DIR__ . '/../..'`, que resuelve a
la **raíz del repo** (no a `admin/.env`). `admin/public/index.php:17` carga
`Dotenv\Dotenv::createImmutable(ADMIN_PROJECT_ROOT)` — el mismo `.env` que usa
`public/index.php:41`. No hay `admin/.env` propio.

`docker-compose.yml:12` inyecta `APP_ENV: ${APP_ENV:-development}` como variable de entorno
del contenedor `app`, que sirve tanto `public/` como `admin/`. Por eso la trampa medida en
`docs/superpowers/specs/2026-07-30-dev-door-design.md` ("editar `APP_ENV` en `.env` es un
no-op bajo Docker, hay que usar `DEV_DOOR=0`") aplica igual aquí: los tres candados de
`admin/` deben leer el mismo `App\Core\AppEnvironment::allowsInternalTools()` y el mismo
par `DEV_DOOR` / `DEV_DOOR_USERS`, no una copia — comparten proceso, `$_ENV` y `.env`.

## Los tres candados (calcados de `src/Core/DevDoor.php`)

Reutilizar `App\Core\DevDoor::isOpen()` tal cual, sin duplicar lógica:

1. `AppEnvironment::allowsInternalTools()` — `APP_ENV` es `development` o `testing`;
   cualquier valor desconocido o ausente cae a `production` (cerrada).
2. `$_SERVER['REMOTE_ADDR']` es `127.0.0.1`/`::1` o pertenece a la red privada de Docker
   Compose (`DevDoor::requestIsLocal()`).
3. `DEV_DOOR=1` explícito y `DEV_DOOR_USERS` no vacío.

Quien registre la ruta en `admin/public/index.php` debe consultar
`App\Core\DevDoor::isOpen()` **antes** de `$router->add('GET', '/dev/entrar', ...)`
(análogo a `public/index.php:49` y su bloque condicional). Fuera de desarrollo la ruta no
se registra: `Admin\Core\Router::dispatch()` cae al `404` genérico que ya emite
(`admin/src/Core/Router.php:75-76`) — nunca un `403`.

`DevDoor::allowedUsers()` sigue siendo la misma lista de `.env` (`test.A`, `test.R`,
`test.V`, …); no hace falta una lista separada para `admin/`.

## Mapa exacto de `$_SESSION` que debe producir

El único login real de `admin/` es `AuthController::login()`. Tras validar contraseña y
`activo`, escribe exactamente esta clave:

```php
// admin/src/Controllers/AuthController.php:86-91
$_SESSION['admin_user'] = [
    'id'      => $user['id'],
    'nombre'  => $user['nombre'],
    'usuario' => $user['usuario'],
    'permiso' => $this->rbac->normalizeRole($user['permiso']),
];
```

seguido de `Security::regenerateSession()` (`AuthController.php:93`, envuelve
`session_regenerate_id(true)` — `admin/src/Core/Security.php:70-73`).

`$user` sale de `Admin\Models\User::findByUsername()`
(`admin/src/Models/User.php:99`), que resuelve `permiso` como el rol más alto real del
usuario (`getHighestRoleForUser`, `User.php:104`) — no hay campo `permiso` fijo en la fila,
así que la puerta debe llamar a ese mismo método para quedar con el rol real, igual que
`ProjectSelectorController::enterProject()` hace en la app principal.

`Security::initSession()` (`admin/src/Core/Security.php:10-30`) ya corre en el front
controller antes de despachar (`admin/public/index.php:31`), así que la puerta no
necesita tocar `session_set_cookie_params` ni `session_start`.

**La puerta debe escribir exactamente esa clave `$_SESSION['admin_user']` con esas cuatro
subclaves, ni una más**, resuelta contra `general_usuarios` con `activo = 1` (mismo
criterio que ya aplica `AuthController::login()` en `AuthController.php:72`), y regenerar
la sesión igual que el login real.

## Comportamiento

`GET /admin/dev/entrar?u=<login>`

1. Verifica `DevDoor::allows($login)` (candado + lista).
2. Busca el usuario con `User::findByUsername($login)`; si no existe o `activo !== 1`,
   404.
3. Escribe `$_SESSION['admin_user']` con el mapa de arriba.
4. `Security::regenerateSession()`.
5. Redirige a `/admin/` (dashboard), igual que el login real
   (`AuthController.php:98`, `redirect: '/admin/'`).

Sin `p`/proyecto: `admin/` no tiene selector de proyecto propio, así que no aplica ese
paso del diseño original.

## Qué NO hace

- No crea cuentas ni las modifica.
- No salta RBAC: el rol que queda en sesión es `getHighestRoleForUser()`, el mismo que
  calcula el login real; `requireAdminRole()` (`DashboardController.php:20`) y el resto de
  guards de `admin/` se aplican exactamente igual después.
- No existe en producción ni en ningún entorno donde `AppEnvironment::allowsInternalTools()`
  sea `false` — la misma condición 1 que cierra `/dev/entrar` en la app principal.
- No añade una lista de usuarios ni claves de `.env` nuevas: reutiliza `DEV_DOOR_USERS`.
- No toca `AuthController::login()`, `logout()`, ni el flujo de contraseña.

## Plan de prueba

- **Guard**: extender (o replicar el patrón de) `tests/test_dev_door_guard.php` para
  confirmar que la ruta de `admin/` no se registra fuera de las tres condiciones — mismo
  candado, mismos casos ya cubiertos (`APP_ENV` desconocido/ausente, IP pública,
  `DEV_DOOR` ausente o `≠1`, `DEV_DOOR_USERS` vacío).
- **Rol permitido**: `GET /admin/dev/entrar?u=test.A` con el candado abierto → aterriza en
  `/admin/` con `$_SESSION['admin_user']['permiso']` = rol real de `test.A` (`A`), acceso
  al dashboard.
- **Rol denegado**: `GET /admin/dev/entrar?u=test.V` (rol `V`, sin
  `requireAdminRole()`) → `AdminController::requireAdminRole()` debe rechazarlo en
  `DashboardController` igual que rechazaría un login real con ese rol, confirmando que la
  puerta no otorga nada por encima de la cuenta.
- Candado cerrado (`DEV_DOOR=0` o `APP_ENV=production`): `/admin/dev/entrar?u=test.A` →
  `404`, nunca `403`.
