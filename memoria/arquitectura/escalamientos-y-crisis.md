---
capa: wiki
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, arquitectura]
tags: [generado]
fuente: public/index.php
resumen: "Escalamientos y crisis: comentarios y avisos que suben un problema de actividad a Dirección"
---
# Escalamientos, crisis y avisos

**Qué resuelve.** Cuando una actividad no puede resolverse en el nivel donde surgió, este módulo
es el canal para escalarla: guarda comentarios asociados a un `escalamiento_id` y avisa a quien
corresponda. Es infraestructura de comunicación del LPS, no una vista con ruta propia — se consume
desde dentro de otros módulos vía `LpsApiController`.

**Dónde encaja.** En el flujo LPS. Ver [[flujo-lps]].

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| POST | `/api/lps/comments/add` | `App\Controllers\Api\LpsApiController::addComment` |
| GET | `/api/lps/comments` | `App\Controllers\Api\LpsApiController::comments` |
| POST | `/api/lps/comments` | `App\Controllers\Api\LpsApiController::addComment` |
| POST | `/api/lps/crisis/close` | `App\Controllers\Api\LpsApiController::closeCrisis` |
| POST | `/api/lps/crisis/register` | `App\Controllers\Api\LpsApiController::registerCrisis` |
| POST | `/api/lps/crisis` | `App\Controllers\Api\LpsApiController::registerCrisis` |
| POST | `/api/notifications/read` | `App\Controllers\Core\NotificationController::markAsRead` |
| GET | `/api/notifications/unread` | `App\Controllers\Core\NotificationController::getUnread` |
| GET | `/dashboard/escalamientos` | `App\Controllers\Core\DashboardController::escalamientos` |
| GET | `/dashboard` | `App\Controllers\Core\DashboardController::index` |

### Controladores
- `App\Controllers\Api\LpsApiController`
- `App\Controllers\Core\DashboardController`
- `App\Controllers\Core\NotificationController`

### Servicios
- `LpsService`
- `NotificationService`
- `ProjectLandingService`

### Tablas
- `general_proyectos_procesos`
- `general_usuarios`
- `project_members`
- `system_notifications`

### Quién puede
_Sin capacidad propia: la ruta exige sesión y proyecto, no una capacidad específica._
<!-- generado:fin -->
