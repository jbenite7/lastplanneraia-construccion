---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [rbac, arquitectura]
fuente: public/index.php
resumen: "Módulo Selector de proyectos: rutas, controladores, servicios y quién puede usarlo"
---
# Selector de proyectos

**Qué resuelve.** _Pendiente de escribir a mano._

**Dónde encaja.** Fuera de los dos flujos de negocio: es infraestructura de la aplicación.

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| POST | `/proyecto/seleccionar` | `App\Controllers\Core\ProjectSelectorController::select` |
| GET | `/proyectos` | `App\Controllers\Core\ProjectSelectorController::index` |

### Controladores
- `App\Controllers\Core\ProjectSelectorController`

### Servicios
- `ProjectLandingService`

### Tablas
- `general_proyectos_procesos`
- `general_usuarios`
- `project_members`

### Quién puede
_Sin capacidad propia: la ruta exige sesión y proyecto, no una capacidad específica._
<!-- generado:fin -->
