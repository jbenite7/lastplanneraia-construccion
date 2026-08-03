---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, arquitectura]
fuente: public/index.php
resumen: "Actualizar cronograma: importa desde Excel una nueva versión del Programa General sin perder el histórico"
---
# Actualizar cronograma

**Qué resuelve.** Es el punto de entrada para traer un cronograma maestro nuevo desde Excel y
reemplazar o ajustar el vigente en [[programa-general]], sin perder la trazabilidad de lo que
cambió. Se usa cuando el cronograma contractual se revisa formalmente, no para el ajuste semana a
semana — eso vive en [[programacion-semanal]].

**Dónde encaja.** En el flujo LPS. Ver [[lps-dominio]].

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
