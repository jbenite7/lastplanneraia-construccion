# Evidencia Sprint 04 — Programa General

Matriz visual: Da Porto. Flujo de persistencia: Optimización Aeropuerto JMC (`project_id=68`), semana 6.

| Viewport | Dark | Linen | Superficie | Estado |
|---|---|---|---|---|
| 390x844 | `mobile-dark.png` | `mobile-linen.png` | Cards | PASS |
| 1180x820 | `tablet-horizontal-dark.png` | `tablet-horizontal-linen.png` | Tabla | PASS |
| 1440x900 | `desktop-dark.png` | `desktop-linen.png` | Tabla | PASS |

## Flujos nativos

- Filtros: click, Enter, Espacio, Control+click, combinación múltiple, contador y `Sin Datos` verificados; `aria-pressed` coincide con el estado aplicado.
- Leyenda: guía P1/P2/P3 abre y cierra sin overflow. Recargar restablece filas/filtros.
- Exportar CSV: acción ejecutada sin error de consola; el navegador integrado no expuso evento de descarga.
- Descargar Corte: transición `Generando…` → `Descargar Corte`, sin error visible.
- Actualizar Ejecución: terminó con `Ejecución actualizada`; checksum previo/post de su corte fue idéntico.
- Persistencia: `unique_id=11044`, Id 1, cantidad `200 → 201`; recarga móvil mostró 201; tabla desktop restauró 200 y la recarga volvió a mostrar `200.0`.
- Flujo nativo: `flow-01-filter.jpg` a `flow-05-desktop-reload.jpg` y `programa-general-native-flow.mp4`.
- Permisos: administrador editó y persistió. `test.C` abre PG en solo lectura; no ve Actualizar/Descargar y `RbacManager` lo clasifica `isReadOnly`. Se registra como contrato existente, sin tocar RBAC.

## Integridad y métricas

- Snapshot: 1.891 filas de `project_id=68`, semana 6.
- Hash canónico final ordenado: vivo y snapshot = `f42f80a27efd462906b39e1653d8f57c513332325346504687142af1d263be82`.
- La recarga automática recalculó `Estado` y `medir_productividad` en 416 filas; ambas columnas se repusieron desde el snapshot antes del hash final.
- `native-metrics.json`: seis estados, overflow 0, recortes 0 y fallos de target PG 0.
- Consola: el tab de estrés registró dos `startRow` del vendor Handsontable durante resize/reload forzado; una carga nueva y estable a 1440x900 terminó con cero warnings/errores.

Las capturas y el video proceden exclusivamente del navegador nativo. Playwright se usó como apoyo autorizado para el flujo de trabajo; el resultado final quedó visible en el navegador integrado.
