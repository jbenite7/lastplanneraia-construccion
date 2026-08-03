---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, arquitectura]
fuente: public/index.php
resumen: "Módulo Control de Cambios: rutas, controladores, servicios y quién puede usarlo"
---
# Control de Cambios

**Qué resuelve.** _Pendiente de escribir a mano._

**Dónde encaja.** En el flujo LPS. Ver [[flujo-lps]].

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| POST | `/api/control-cambios/list` | `App\Controllers\Api\ControlCambiosApiController::list` |
| POST | `/api/control-cambios/save` | `App\Controllers\Api\ControlCambiosApiController::save` |
| GET | `/control-cambios` | `App\Controllers\Integracion\ControlCambiosController::index` |

### Controladores
- `App\Controllers\Api\ControlCambiosApiController`
- `App\Controllers\Integracion\ControlCambiosController`

### Servicios
_indeterminado_

### Tablas
- `cambios`
- `general_proyectos_procesos`

### Quién puede
_Sin capacidad propia: la ruta exige sesión y proyecto, no una capacidad específica._
<!-- generado:fin -->
