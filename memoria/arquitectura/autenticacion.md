---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [rbac, arquitectura]
fuente: public/index.php
resumen: "Autenticación: login, recuperar contraseña y la puerta de desarrollo que evita teclear credenciales en local"
---
# Autenticación

**Qué resuelve.** Es la puerta de entrada: login, recuperación de contraseña y, solo en local, la
puerta de desarrollo (`/dev/entrar`) que abre sesión sin pasar por el formulario. En desarrollo
**nunca** se usa `/login` a mano — ver [[dev-door-acceso-local]] para el porqué y el candado que la
cierra en producción.

**Dónde encaja.** Fuera de los dos flujos de negocio: es infraestructura de la aplicación.

Su vista está catalogada en [[VISTAS-MODULOS|docs/VISTAS-MODULOS.md]] (login y recuperación de contraseña).

**Nota del manifiesto.** La puerta de servicio /dev/entrar solo se registra en desarrollo. /_aia/operacion/7f3c9b es la ruta secreta de acceso en mantenimiento (MaintenanceMode::SECRET_PATH, ver src/Core/MaintenanceMode.php); sirve el mismo LoginController.

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| GET | `/_aia/operacion/7f3c9b` | `App\Controllers\Auth\LoginController::index` |
| POST | `/_aia/operacion/7f3c9b` | `App\Controllers\Auth\LoginController::maintenanceLogin` |
| GET | `/dev/entrar` | `App\Controllers\Core\DevDoorController::enter` |
| GET | `/` | `App\Controllers\Auth\LoginController::index` |
| GET | `/login/cancelar` | `App\Controllers\Auth\LoginController::cancelPasswordChange` |
| GET | `/login` | `App\Controllers\Auth\LoginController::index` |
| POST | `/login` | `App\Controllers\Auth\LoginController::login` |
| GET | `/logout` | `App\Controllers\Auth\LoginController::logout` |
| GET | `/password/forgot` | `App\Controllers\Auth\PasswordResetController::forgot` |
| POST | `/password/forgot` | `App\Controllers\Auth\PasswordResetController::sendLink` |
| GET | `/password/reset` | `App\Controllers\Auth\PasswordResetController::reset` |
| POST | `/password/reset` | `App\Controllers\Auth\PasswordResetController::update` |
| POST | `/password/update` | `App\Controllers\Auth\LoginController::updatePassword` |

### Controladores
- `App\Controllers\Auth\LoginController`
- `App\Controllers\Auth\PasswordResetController`
- `App\Controllers\Core\DevDoorController`

### Servicios
- `PasswordResetService`
- `UserPasswordService`

### Tablas
- `general_proyectos_procesos`
- `general_usuarios`
- `password_reset_tokens`
- `project_members`

### Quién puede
_Sin capacidad propia: la ruta exige sesión y proyecto, no una capacidad específica._
<!-- generado:fin -->
