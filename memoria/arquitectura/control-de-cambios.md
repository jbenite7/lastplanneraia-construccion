---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, arquitectura]
fuente: public/index.php
resumen: "Control de Cambios: registra órdenes de cambio formales sobre el alcance del proyecto"
---
# Control de Cambios

**Qué resuelve.** Registra las órdenes de cambio: cuando el alcance o el presupuesto del proyecto
se modifica formalmente, queda constancia aquí en vez de perderse en un correo o un chat. Es
consulta habitual de Dirección y Residencia cuando algo del cronograma ya no calza con lo
originalmente pactado.

**Dónde encaja.** En el flujo LPS. Ver [[lps-dominio]].

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
