---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, bi, arquitectura]
fuente: public/index.php
resumen: "Indicadores LPS: dashboard de KPIs que embebe un informe de Power BI publicado en la web"
---
# Indicadores LPS

**Qué resuelve.** Muestra los KPIs del proyecto (PAC/PPC y afines) embebiendo un informe externo en
vez de calcularlos en la app. Si un número no cuadra aquí, primero hay que mirar el informe externo,
no este módulo — es solo el marco que lo aloja.

El informe es de **Power BI** (`publish-to-web`), no de Google Data Studio: la migración se decidió
el 2026-07-23 y está registrada en [[powerbi-indicadores]]; la vista construye la URL
`app.powerbi.com/view?r=…` en `views/indicadores/indicadores.view.php:99-111`. Esta página lo dijo
mal durante tres días; corregido en el pase de veracidad del 2026-08-06.

**Dónde encaja.** En el flujo LPS. Ver [[flujo-lps]].

Su vista está catalogada en [[VISTAS-MODULOS|docs/VISTAS-MODULOS.md]].

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
