---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, arquitectura]
fuente: public/index.php
resumen: "Módulo Subcontratistas: rutas, controladores, servicios y quién puede usarlo"
---
# Subcontratistas

**Qué resuelve.** _Pendiente de escribir a mano._

**Dónde encaja.** En los dos flujos. Ver [[flujo-lps]] y [[flujo-pdc]].

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| GET | `/api/subcontratistas/list` | `App\Controllers\Api\SubcontratistasApiController::list` |
| POST | `/api/subcontratistas/list` | `App\Controllers\Api\SubcontratistasApiController::list` |
| POST | `/api/subcontratistas/save` | `App\Controllers\Api\SubcontratistasApiController::save` |
| GET | `/subcontratistas` | `App\Controllers\Gestion\SubcontratistasController::index` |

### Controladores
- `App\Controllers\Api\SubcontratistasApiController`
- `App\Controllers\Gestion\SubcontratistasController`

### Servicios
_indeterminado_

### Tablas
_indeterminado_

### Quién puede
| Capacidad | Roles que la tienen |
| --- | --- |
| `canManageContracts` | A, D, R, OT |
<!-- generado:fin -->
