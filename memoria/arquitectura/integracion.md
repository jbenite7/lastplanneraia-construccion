---
capa: wiki
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [datos, arquitectura]
tags: [generado]
fuente: public/index.php
resumen: "Integración: procesa reportes externos (ReportProcessor) para alimentar otros módulos, sin vista propia"
---
# Integración de reportes

**Qué resuelve.** Es la capa que procesa reportes que llegan de fuera de la app (vía
`ReportProcessor`) y los deja listos para que otros módulos los consuman. No tiene pantalla propia
que alguien navegue: es tubería interna, se entiende leyendo quién la llama, no quién la abre.

**Dónde encaja.** Fuera de los dos flujos de negocio ([[flujo-lps]] y [[flujo-pdc]]): es
infraestructura de la aplicación, así que se lee junto al mapa de [[arquitectura]].

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| GET | `/reportes/{tipo}` | `App\Controllers\Gestion\ReportController::generate` |
| POST | `/reportes/{tipo}` | `App\Controllers\Gestion\ReportController::generate` |

### Controladores
- `App\Controllers\Gestion\ReportController`

### Servicios
- `NotificationService`
- `ReportProcessor`
- `RestrictionConfigResolver`

### Tablas
- `cambios`
- `general_curvas`
- `general_informe_consolidado`
- `general_informe_restricciones_consolidado`
- `general_informe_subcontratistas`
- `general_proyectos_procesos`
- `general_usuarios`
- `project_members`
- `system_notifications`

### Quién puede
_Sin capacidad propia: la ruta exige sesión y proyecto, no una capacidad específica._
<!-- generado:fin -->
