---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, arquitectura]
fuente: public/index.php
resumen: "Módulo Actualizar cronograma: rutas, controladores, servicios y quién puede usarlo"
---
# Actualizar cronograma

**Qué resuelve.** _Pendiente de escribir a mano._

**Dónde encaja.** En el flujo LPS. Ver [[flujo-lps]].

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| GET | `/programa-general-actualizar` | `App\Controllers\Programacion\ProgramaGeneralActualizarController::index` |

### Controladores
- `App\Controllers\Programacion\ProgramaGeneralActualizarController`

### Servicios
_indeterminado_

### Tablas
_indeterminado_

### Quién puede
| Capacidad | Roles que la tienen |
| --- | --- |
| `canEditGeneralProgram` | A, D, R, DCV |
<!-- generado:fin -->
