---
capa: wiki
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [rbac, arquitectura]
tags: [generado]
fuente: public/index.php
resumen: "Autenticación: login y recuperar contraseña —cuyas pantallas GET ya sirve el shell React— más la puerta de desarrollo que evita teclear credenciales en local"
---
# Autenticación

**Qué resuelve.** Es la puerta de entrada: login, recuperación de contraseña y, solo en local, la
puerta de desarrollo (`/dev/entrar`) que abre sesión sin pasar por el formulario. En desarrollo
**nunca** se usa `/login` a mano — ver [[dev-door-acceso-local]] para el porqué y el candado que la
cierra en producción.

**Dónde encaja.** Fuera de los dos flujos de negocio: es infraestructura de la aplicación.

**Quién pinta cada pantalla (verificado el 2026-09-17, actualizado el 2026-09-21).** La tabla
generada de abajo lista lo que registra el router, y para varias rutas **no es lo que se sirve**:
los `GET`/`HEAD` de `/`, `/login`, `/password/forgot` y `/password/reset` los intercepta antes
`SpaRouter::sirveLaSpa()` (`RUTAS_EXACTAS_MIGRADAS`, `src/Core/SpaRouter.php:18`) y los pinta el
shell React con `SpaHostRenderer::render()` (`public/index.php:403-406`), sin llegar a
`LoginController::index`. `/login` se cortó el 2026-09-01 (`36b7df22`, S01), `/password/forgot` el
2026-09-16 (S02, PR #43) —que además retiró `views/auth/password-forgot.view.php` y el
`POST /password/forgot` legado: la recuperación va por `POST /api/auth/password/forgot` →
`PasswordRecoveryApiController::request`— y `/password/reset` después, en S03 (Tarea 10,
`ff50fe68`): se retiró de la lista de rutas del router (`public/index.php:107-112`) pero
**permanece** en `$publicRoutes` porque el restablecimiento real vive en
`POST /api/auth/password/reset/validate` y `POST /api/auth/password/reset` →
`PasswordResetApiController`. `views/auth/password-reset.view.php` sigue en el repo sin ruta que la
alcance. Solo cruzan lectura: `POST /login` sigue en `LoginController::login`, que ante un error
vuelve a pintar `views/auth/login.view.php`. Por decisión de Felipe del 2026-09-16 (`TASKS.md`), el
login legado **no** necesita poder reactivarse como respaldo.

Su vista está catalogada en [[VISTAS-MODULOS|docs/VISTAS-MODULOS.md]] (login y recuperación de
contraseña), pero ese catálogo va atrás: aún describe `auth/password-forgot.view.php`, que ya no existe.

**Nota del manifiesto.** La puerta de servicio /dev/entrar solo se registra en desarrollo. /_aia/operacion/7f3c9b es la ruta secreta de acceso en mantenimiento (MaintenanceMode::SECRET_PATH, ver src/Core/MaintenanceMode.php). **Corregido el 2026-09-17:** esta nota decía que «sirve el mismo LoginController», y desde el 2026-09-01 (`4b3c891c`, Tarea 12 de S01) la sirve `MaintenanceLoginController` con el shell React, como ya muestra la tabla generada. /api/session y /api/auth/* son la sesión JSON del shell React (2026-08-28).

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| GET | `/_aia/operacion/7f3c9b` | `App\Controllers\Auth\MaintenanceLoginController::show` |
| POST | `/_aia/operacion/7f3c9b` | `App\Controllers\Auth\MaintenanceLoginController::submit` |
| POST | `/api/auth/login` | `App\Controllers\Api\AuthApiController::login` |
| POST | `/api/auth/logout` | `App\Controllers\Api\AuthApiController::logout` |
| POST | `/api/auth/password/cancel` | `App\Controllers\Api\AuthApiController::cancelPasswordChange` |
| POST | `/api/auth/password/change` | `App\Controllers\Api\AuthApiController::changePassword` |
| POST | `/api/auth/password/forgot` | `App\Controllers\Api\PasswordRecoveryApiController::request` |
| POST | `/api/auth/password/reset/validate` | `App\Controllers\Api\PasswordResetApiController::validateLink` |
| POST | `/api/auth/password/reset` | `App\Controllers\Api\PasswordResetApiController::update` |
| GET | `/api/session` | `App\Controllers\Api\SessionApiController::show` |
| GET | `/dev/entrar` | `App\Controllers\Core\DevDoorController::enter` |
| GET | `/` | `App\Controllers\Auth\LoginController::index` |
| GET | `/login/cancelar` | `App\Controllers\Auth\LoginController::cancelPasswordChange` |
| GET | `/login` | `App\Controllers\Auth\LoginController::index` |
| POST | `/login` | `App\Controllers\Auth\LoginController::login` |
| GET | `/logout` | `App\Controllers\Auth\LoginController::logout` |
| POST | `/password/update` | `App\Controllers\Auth\LoginController::updatePassword` |

### Controladores
- `App\Controllers\Api\AuthApiController`
- `App\Controllers\Api\PasswordRecoveryApiController`
- `App\Controllers\Api\PasswordResetApiController`
- `App\Controllers\Api\SessionApiController`
- `App\Controllers\Auth\LoginController`
- `App\Controllers\Auth\MaintenanceLoginController`
- `App\Controllers\Core\DevDoorController`

### Servicios
- `AuthenticationService`
- `DatabaseWeekAdministrationRepository`
- `ForcedPasswordChangeService`
- `PasswordResetService`
- `ProjectAccessService`
- `ShellNavigationService`
- `UserPasswordService`
- `WeekContextService`

### Tablas
- `general_proyectos_procesos`
- `general_usuarios`
- `password_reset_tokens`
- `project_members`

### Quién puede
_Sin capacidad propia: la ruta exige sesión y proyecto, no una capacidad específica._
<!-- generado:fin -->
