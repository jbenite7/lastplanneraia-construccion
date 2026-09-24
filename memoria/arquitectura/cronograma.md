---
capa: wiki
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, arquitectura]
tags: [generado]
fuente: public/index.php
resumen: "Actualizar cronograma: importa desde Excel una nueva versión del Programa General sin perder el histórico"
---
# Actualizar cronograma (Actualizador del Programa General)

**Qué resuelve.** Es el punto de entrada para traer un cronograma maestro nuevo desde Excel / MS Project y
reemplazar o ajustar el vigente en [[programa-general]], sin perder la trazabilidad de lo que
cambió. **No es otra vista del cronograma: es otra herramienta** (ver [[programa-general-actualizar-es-otra-herramienta]]).
Se usa cuando el cronograma contractual se revisa formalmente, no para el ajuste semana a
semana — eso vive en [[programacion-semanal]].

Opera mediante:
- Importación del archivo Excel estructurado (`/api/general/import`).
- Grilla Handsontable interactiva con selector TomSelect para emparejar actividades anteriores con el nuevo borrador (`hot_actualizar.js`).
- Algoritmo de auto-asociación inteligente de actividades (`/api/general/auto-associate`).
- Confirmación y aplicación de cambios por lote a `programa_consolidado` (`/api/general/update-batch`) o descarte (`/api/general/delete-update`).

**Dónde encaja.** En el flujo LPS. Ver [[flujo-lps]] y el inventario global en [[mapa-de-modulos-y-submodulos]].

Su vista está catalogada en [[VISTAS-MODULOS|docs/VISTAS-MODULOS.md]].

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
| `canManageGeneralProgram` | A, D, R, DCV |
<!-- generado:fin -->
