---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [datos, arquitectura]
fuente: public/index.php
resumen: "Módulo Integración de reportes: rutas, controladores, servicios y quién puede usarlo"
---
# Integración de reportes

**Qué resuelve.** _Pendiente de escribir a mano._

**Dónde encaja.** Fuera de los dos flujos de negocio: es infraestructura de la aplicación.

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| GET | `/reportes/{tipo}` | `App\Controllers\Gestion\ReportController::generate` |
| POST | `/reportes/{tipo}` | `App\Controllers\Gestion\ReportController::generate` |

### Controladores
- `App\Controllers\Gestion\ReportController`

### Servicios
- `NotificationService`
- `ReportProcessor`
- `RestrictionConfigResolver`

### Tablas
- `cambios`
- `general_curvas`
- `general_curvas_pdc`
- `general_informe_consolidado`
- `general_informe_pdc`
- `general_informe_restricciones_consolidado`
- `general_informe_subcontratistas`
- `general_proyectos_procesos`
- `general_usuarios`
- `project_members`
- `system_notifications`

### Quién puede
| Capacidad | Roles que la tienen |
| --- | --- |
| `canSeeReports` | A, D, R, DCV, OT, G, S, SG, C, V |
<!-- generado:fin -->
