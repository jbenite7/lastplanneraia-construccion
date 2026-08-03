---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, bi, arquitectura]
fuente: public/index.php
resumen: "Indicadores LPS: dashboard de KPIs que embebe reportes de Google Data Studio"
---
# Indicadores LPS

**Qué resuelve.** Muestra los KPIs del proyecto (PAC/PPC y afines) embebiendo reportes externos de
Google Data Studio en vez de calcularlos en la app. Si un número no cuadra aquí, primero hay que
mirar el reporte externo, no este módulo — es solo el marco que lo aloja.

**Dónde encaja.** En el flujo LPS. Ver [[lps-dominio]].

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
