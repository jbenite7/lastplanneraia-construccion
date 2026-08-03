---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, bi, arquitectura]
fuente: public/index.php
resumen: "Módulo Indicadores LPS: rutas, controladores, servicios y quién puede usarlo"
---
# Indicadores LPS

**Qué resuelve.** _Pendiente de escribir a mano._

**Dónde encaja.** En el flujo LPS. Ver [[flujo-lps]].

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| POST | `/api/indicadores/generar` | `App\Controllers\Api\IndicadoresApiController::generar` |
| GET | `/indicadores` | `App\Controllers\Gestion\IndicadoresController::index` |

### Controladores
- `App\Controllers\Api\IndicadoresApiController`
- `App\Controllers\Gestion\IndicadoresController`

### Servicios
- `LpsService`

### Tablas
- `general_usuarios`

### Quién puede
| Capacidad | Roles que la tienen |
| --- | --- |
| `canSeeReports` | A, D, R, DCV, OT, G, S, SG, C, V |
<!-- generado:fin -->
