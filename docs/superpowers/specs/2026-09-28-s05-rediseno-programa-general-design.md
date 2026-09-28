---
capa: fuente
tipo: spec
estado: abierto
id: S05-REDISENO
fecha: 2026-09-28
superficie: programa-general
rutas: ["/programa-general"]
depende_de: [S05, S05-DEUDA]
version: 1.0
areas: [lps, design-system]
fuente: "crítica de diseño sobre main del 2026-09-28 (.impeccable/critique/2026-09-28T13-49-37Z__frontend-src-modules-programa-general.md), decisiones de Felipe del 2026-09-23 y del 2026-09-28, y el frente goals/s05-cajon-verdad (PR #65)"
resumen: "Ronda de rediseño de Programa General sobre lo que hay en main: la pantalla responde qué se cae esta semana sin perder el orden del cronograma — cabecera en una banda, tabla densa sobre el contrato del DS, riesgo visible en la fila, edición solo en el cajón."
---

# S05 — Rediseño de Programa General (ronda sobre `main`)

**Estado: propuesta, pendiente del visto de Felipe.** No autoriza implementación. Mockup:
`public/mockups/s05-rediseno-2026-09-28.html` (datos ficticios; el repo es público).

## 1. Trabajo y audiencia

Residente y equipo de obra que abren Programa General en la revisión semanal, antes del comité
(escritorio, 1180×820 canónico; proyector en el comité) y que actualizan avance y fechas de una
actividad. Modo **Operate**. Lo que necesitan en 90 segundos: **qué se cae esta semana**, sin perder
la lectura por capítulos del cronograma.

## 2. Resultado y prueba

- A 1180×820, con nombres reales de 50–60 caracteres, la tabla muestra **≥ 20 actividades**
  (hoy 8) y empieza en **y ≤ 150** (hoy 266).
- Una actividad atrasada, vencida o con restricción dura **se distingue sin leer la columna de
  estado**: bandera de gravedad y tinte en la fila.
- Nada de lo que se muestra es inventado: cada dato visible tiene fuente en la fila o en el
  contexto del servidor (regla del frente `goals/s05-cajon-verdad/`).

## 3. Dirección elegida

Construida sobre lo que ya funciona en `main` (encabezado fijo, cero desborde, cajón superpuesto,
guardado seguro), conectando las primitivas que el DS ya tiene y hoy no se usan.

1. **Cabecera en una sola banda (≤ 56 px) más franja de señales (≤ 36 px).** Título de pantalla en
   la escala densa (§5 bis), un solo control de semana («Semana 11 · vigente · 20–26 jul», menú con
   fechas dd/mm que abre en la vigente y le pone el foco), búsqueda compacta con atajo real, alternador
   «Resumen · Detalle» (8 y 13 columnas), **una sola acción principal** «Actualizar ejecución» con la
   hora de la última corrida, y «Exportar ▾» (CSV y corte) y «Recargar» como acciones secundarias. «BI
   Programa» en «Más». La semana deja de repetirse en la cabecera.
2. **Franja de señales.** Chips agrupados por intención: **Actuar** (Atrasada, Debe iniciar, Con
   alerta de restricciones) en sólido cuando el conteo es mayor que 0; **Seguir** y **Contexto**
   (incluido «Fuera de ventana») neutros y más chicos. Chips en 0 atenuados. Selección con relleno,
   foco con anillo: nunca iguales. Un solo punto de tabulación con flechas (`role="toolbar"`).
3. **Tabla sobre el contrato del DS.** `.aia-table-shell` con la métrica `--ds-table-*`, fila de
   24–28 px en una línea, rampa tipográfica 13/12/11 en Inter. Por fila: bandera `aia-flag` y tinte
   `--ds-state-row-<hue>` según gravedad; chip de estado sólido **junto a la actividad**, no al final;
   la ruta del capítulo deja de repetirse en cada fila (queda en el tooltip y en el cajón). Avance real
   frente a teórico en una barra del matiz del estado, con Δ solo cuando importa (≥ 5 p. p. o
   negativo). Fechas vencidas marcadas. Columnas vacías en todo el proyecto se ocultan.
4. **Orden: cronograma puro** (decisión de Felipe, 2026-09-28). Sin filtro por defecto. Capítulos
   colapsables con resumen («14 act · 2 atrasadas · 43 %») y **sin capítulos vacíos al filtrar**.
   Orden opcional por Fin y por Δ desde el encabezado.
5. **Edición solo en el cajón** (decisión de Felipe, 2026-09-28). La tabla es de lectura. Enter o
   clic en la fila abre el cajón de 440 px existente (con lo que ya dice la verdad desde PR #65).
6. **Teclado de hoja de cálculo.** La tabla es un solo punto de tabulación con flechas entre filas;
   Enter abre el cajón; foco que vuelve a la fila. Hoy son 1.475 paradas de Tab.
7. **Leyenda donde se decide:** tooltips de estado (`state-tooltip.js`) y un popover «?» no modal
   junto a la franja de señales. Sin modal.
8. **Móvil (390 px): tarjetas completas saneadas** (decisión de Felipe, 2026-09-28). Tarjeta de 3
   líneas (bandera · nombre · estado / avance y Δ / fin), sin HTML crudo, 44 px, detalle al tocar,
   filtros en una hoja inferior.

## 4. Alcance y límites

- **Dentro:** el módulo `frontend/src/modules/programa-general/` y el shell de T01 que se ve en esta
  pantalla (selector de semana, proyecto duplicado, riel verde en claro, ítem activo).
- **Fuera:** los tokens del DS (primario oscuro, token de 13 px, `:disabled`) van a su frente aparte
  (`TASKS.md`); la lógica de guardado y el contrato HTTP no cambian; sin DDL ni DML; sin deploy.
- **Anti-objetivos:** nada decorativo ni saturado de alertas (anti-referencia de PRODUCT.md); no
  reintroducir el editor de fila; no romper la paridad funcional con el legado; ninguna sección del
  cajón con datos sin fuente.

## 5. Estados y rangos

- Proyectos de 2 a 1.500+ actividades y 400+ capítulos (medido en JMC); virtualización desde ~300
  filas; tabla o tarjetas, nunca ambas montadas.
- Estados: carga, vacío (sin actividades / sin semanas), filtro sin resultados, datos
  desactualizados con reintento, lote en curso, cajón con cambios sin guardar, Visualizador (solo
  lectura).

## 6. Interacción y composición

Jerarquía: riesgo de la fila > actividad > avance y fechas > el resto. Tres zonas verticales:
banda, franja, tabla. Bajo 1180 px, objetivos de 44 px sin excepción (PRODUCT.md, acotación
2026-08-14). `prefers-reduced-motion` respetado; ninguna animación de propiedades de layout.

## 7. Restricciones y decisiones abiertas

- Solo tokens y primitivas existentes (`--ds-state-solid-*`, `--ds-state-row-*`, `aia-flag`,
  `aia-toolbar`, `aia-table-shell`, `state-tooltip.js`, `[data-density]`). Sin hex, sin estilos
  inline, sin `!important`. Íconos que se dibujen (hoy seis no tienen glifo).
- Ambos temas contractuales; goldens nuevos solo con aprobación de Felipe.
- **Abiertas, para el visto:** (a) si «Fuera de ventana» se atenúa o se oculta por defecto dentro de
  la vista de cronograma puro; (b) si el modo proyector del DS entra en esta ronda.

## Archivos relacionados

- Crítica: `.impeccable/critique/2026-09-28T13-49-37Z__frontend-src-modules-programa-general.md`
- Spec base: [[docs/superpowers/specs/2026-08-30-s05-programa-general-react-design]]
- Frente previo: `goals/s05-cajon-verdad/goal.md`
