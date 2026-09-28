---
target: Programa General en main (/programa-general)
total_score: 18
max_score: 40
na_heuristics: 
p0_count: 1
p1_count: 3
target_identity: "file:/Volumes/Crucial X6/Developer/lps-aia/.claude/worktrees/shell-minimo-react/frontend/src/modules/programa-general"
timestamp: 2026-09-28T13-49-37Z
slug: frontend-src-modules-programa-general
---
Method: dual-agent (A: revisión de diseño, opus · B: evidencia de navegador, sonnet) · detector corrido en la sesión principal porque B quedó bloqueado por el hook

# Crítica — Programa General en `main` (código de S05 con ronda 1.2 y deuda cerrada), 2026-09-28

Datos reales: «Optimización Aeropuerto JMC», semana 11, 1.475 actividades y 416 capítulos, vía puerta dev en un contenedor propio.

## Puntaje 18/40 (Poor, 45 %)

| # | Heurística | Nota | Hallazgo |
|---|---|---|---|
| 1 | Visibilidad del estado | 2 | «Guardado» miente sobre la observación; sin hora del último lote |
| 2 | Mundo real | 2 | «Drawer LPS», «Cols Reales», «Sem -2», ISO en el menú de semana |
| 3 | Control y libertad | 2 | Esc y el velo descartan la edición sin preguntar |
| 4 | Consistencia | 1 | Semana doble, tokens inexistentes, íconos sin glifo, h3 > h2 |
| 5 | Prevención de errores | 1 | Observación perdida, SOS decorativo, matriz Lean inventada |
| 6 | Reconocer | 2 | Significado de estados solo en un modal |
| 7 | Eficiencia | 2 | ⌘K anunciado y falso; sin orden por columna; 1.475 paradas de Tab |
| 8 | Minimalismo | 2 | Columnas vacías, «Capítulo» ×416, ruta repetida en cada fila |
| 9 | Recuperación | 2 | Reintento bien; alert() nativo al guardar |
| 10 | Ayuda | 2 | Guía lejos del punto de decisión |

## Especificidad

Mitad propia (capítulo, RC, avance con Δ, semana vigente, recursos Lean), mitad plantilla CRUD. La
parte AIA del contrato está escrita pero desconectada: `.row-atrasada`… existen en
`programa-general.css:790-821` y `ProgramaTable.tsx:137` no las aplica; la tabla va en
`.table-wrapper-pro`, no en `.aia-table-shell`; chips tintados, no sólidos; 37 estilos inline, un
hex `#ef4444` y tres tokens inexistentes. Detector: 4 advertencias (2 `side-tab` en
`programa-general.css:1322,1523`, acentos de foco/primario, no el filete de gravedad; 2
`layout-transition` en `:1017,1446`). axe con datos reales: 2 críticos (input sin etiqueta en el
cajón; `role=feed` con hijos `role=button` y `aria-selected` no permitido en tarjetas móviles).

## Problemas prioritarios

- P0 El cajón guarda y afirma cosas falsas: la observación no viaja en el payload
  (`ProgramaGeneralPage.tsx:192-205`) y sale «Cambios guardados con éxito»; «Declarar Crisis SOS»
  no llama a ninguna API; la matriz de 7 recursos está fija en `'Liberado'`
  (`ProgramaDrawer.tsx:204-246`); barra de avance real invisible; Esc descarta sin confirmar.
- P1 Riesgo invisible: orden por ID (la primera pantalla de JMC son 8 terminadas), sin tinte ni
  bandera por fila, estado al final, «Fuera de Ventana» (59 %) pesa igual que «Atrasada»,
  capítulos vacíos al filtrar (`filtros.ts:43`).
- P1 Densidad fuera de §5 bis: fila de 57 px en ambos modos, 8 filas a 1180×820; texto bajo el
  piso (encabezados 9 px, RC 8,5, Δ 9,5); cuerpo en Montserrat en vez de Inter.
- P1 Teclado y foco: 1.475 paradas de Tab; el foco no entra al cajón ni al menú de semana; Tab
  atrapado en la leyenda; foco = selección en chips; alternador de 20,6 px (bajo 24); nada llega a
  44 px bajo 1180; enlace BI sin foco visible; sin `prefers-reduced-motion`.
- P2 Piezas: íconos sin glifo, `<b>` crudo en tarjetas móviles, semana doble, velo lechoso en oscuro,
  tabla y tarjetas montadas a la vez sin virtualizar.

## Frente a la crítica del 2026-09-23

Resuelto: encabezado sticky, cajón superpuesto, cero overflow, contador sin capítulos, primaria con
contraste correcto, fechas dd/mm. Vigente: densidad (mejoró de 4 a 8 filas), riesgo invisible,
chips en 0 llenos, sin orden por columna, leyenda modal, semana doble, riel verde en claro, 44 px.
Nuevo: todo el P0 del cajón, foco, 1.475 Tabs, íconos sin glifo, `<b>` crudo, capítulos vacíos.

## Dirección

«Lo que se cae esta semana»: primero la verdad del cajón; banda de cabecera con una sola primaria y
hora del último lote; tabla sobre `.aia-table-shell` y `--ds-table-*` con fila de 24–28 px, chip
sólido junto a la actividad, tinte y bandera por fila; capítulos colapsables sin vacíos al filtrar;
teclado de hoja de cálculo; leyenda en tooltips y popover; 44 px bajo 1180 y virtualización.
