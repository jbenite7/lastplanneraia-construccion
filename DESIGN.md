---
capa: fuente
tipo: contrato
estado: vigente
fecha: 2026-07-19
areas: [design-system]
tags: [leer-antes-de-tocar]
fuente: DESIGN.md
resumen: Contrato de consumo del design system: que tokens y primitivas aia-* usar y el flujo obligatorio antes de editar una superficie migrada.
name: Last Planner AIA
description: Sistema operativo de Last Planner para anticipar puntos críticos de obra — desktop, dark, denso y accionable.
# Nota de fuente de verdad: este frontmatter refleja tokens.css para consumo de
# herramientas (linter Stitch, live panel). La autoridad ejecutable sigue siendo
# public/css/tokens.css y docs/design-system/. Si divergen, tokens.css gana.
# Los valores se sirven en hex sRGB (compatibilidad Stitch); el canónico OKLCH
# de la paleta base vive en tokens.css y en .impeccable/design.json (colorMeta.canonical).
colors:
  # Submarcas / dominios — cada uno con variante perceptual "on-dark" (matiz preservado, AA)
  corporate: "#1a5633"
  corporate-on-dark: "#6c9077"
  construction: "#b55211"
  construction-on-dark: "#c57247"
  real-estate: "#00a499"
  real-estate-on-dark: "#2caa9f"
  architecture: "#6752bf"
  architecture-on-dark: "#877cd1"
  brand-green-light: "#8cb4a1"       # hover de acción primaria en dark
  text-on-domain-dark: "#141c18"
  # Tema operativo = DARK
  bg-canvas-dark: "#0b100d"
  bg-page-dark: "#111a15"
  surface-dark: "#1c241feb"          # rgba(28,36,31,.92)
  surface-raised-dark: "#233029db"   # rgba(35,48,41,.86)
  surface-glass-dark: "#233029a8"    # rgba(35,48,41,.66)
  text-primary-dark: "#f7faf8"
  text-secondary-dark: "#c7d4cc"
  border-dark: "#ddefe638"           # rgba(221,239,230,.22)
  focus-ring-dark: "#2caa9f"
  # ── CANAL 1 de 2: NIVEL (prioridad de acción) ──
  # Cuatro peldaños, invertidos a oscuro. Es la mitad del contrato de estado:
  # el canal de MATIZ va más abajo (`state-tint-*`). Ojo, esta lista NO es la
  # paleta de estado completa. Contrastes medidos en 1180x820 dark:
  # success 8,88:1 · warning 9,31:1 · critical 10,99:1 · info 8,95:1.
  state-success-bg: "#173d26"
  state-success-text: "#b7e8c6"
  state-warning-bg: "#3a3a0f"
  state-warning-text: "#f2e79c"
  state-critical-bg: "#431414"
  state-critical-text: "#ffcdc8"
  state-info-bg: "#134841"
  state-info-text: "#bbdcfb"
  # Variantes claras de soporte (`--ds-color-state-*-light`): reservadas para
  # impresos y XLSX, NUNCA para pantalla. Hoy declaradas y sin consumidor.
  state-success-bg-light: "#ddefe6"
  state-success-text-light: "#1a5633"
  state-warning-bg-light: "#fff8e1"
  state-warning-text-light: "#5d4200"
  state-critical-bg-light: "#fdecec"
  state-critical-text-light: "#8f1d1d"
  state-info-bg-light: "#e3f9f7"
  state-info-text-light: "#006d66"
  # ── CANAL 2 de 2: MATIZ (identidad del estado) ──
  # Ocho anclas nominales (`--ds-state-tint-*`), sin eje de intensidad. Cuatro
  # coinciden bit a bit con el `-bg` de su nivel; las otras cuatro no tienen
  # token de nivel y solo existen aquí. Un módulo no puede repetir matiz.
  state-tint-teal: "#134841"      # Contexto        (= state-info-bg)
  state-tint-green: "#173d26"     # Controlado      (= state-success-bg)
  state-tint-amber: "#3a3a0f"     # Por resolver    (= state-warning-bg)
  state-tint-red: "#431414"       # Bloqueado o vencido (= state-critical-bg)
  state-tint-blue: "#17334f"      # En marcha
  state-tint-violet: "#33204a"    # Sin datos suficientes
  state-tint-orange: "#452a0d"    # Fuera de plazo
  state-tint-neutral: "#2b2f2d"   # Silencio — único acromático (C=0,0066)
  # Escala de celda — único ancla propia (`--ds-cell-state-bloqueado-*`)
  cell-bloqueado-bg: "oklch(0.35 0.05 260)"
  cell-bloqueado-fg: "oklch(0.85 0.03 260)"
typography:
  display:
    fontFamily: "Montserrat, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
    fontSize: "1.875rem"
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "0"
  headline:
    fontFamily: "Montserrat, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "0"
  title:
    fontFamily: "Montserrat, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
    fontSize: "1.25rem"
    fontWeight: 600
    lineHeight: 1.2
    letterSpacing: "0"
  body:
    fontFamily: "Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: "Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 500
    lineHeight: 1.2
    letterSpacing: "0"
  mono:
    fontFamily: "ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', monospace"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.5
rounded:
  none: "0"
  xs: "0.25rem"
  sm: "0.5rem"
  md: "0.75rem"
  lg: "1rem"
  xl: "1.25rem"
  2xl: "1.5rem"
  3xl: "2rem"
  pill: "9999px"
spacing:
  "0": "0"
  "1": "0.25rem"
  "2": "0.5rem"
  "3": "0.75rem"
  "4": "1rem"
  "6": "1.5rem"
  "8": "2rem"
  "12": "3rem"
components:
  button-primary:
    backgroundColor: "{colors.corporate-on-dark}"
    textColor: "{colors.text-on-domain-dark}"
    rounded: "{rounded.md}"
    padding: "0.65rem 1rem"
    height: "44px"
  button-primary-hover:
    backgroundColor: "{colors.brand-green-light}"
    textColor: "{colors.text-on-domain-dark}"
  button-secondary:
    backgroundColor: "{colors.surface-raised-dark}"
    textColor: "{colors.text-primary-dark}"
    rounded: "{rounded.md}"
    padding: "0.65rem 1rem"
    height: "44px"
  button-construction:
    backgroundColor: "{colors.construction-on-dark}"
    textColor: "{colors.text-on-domain-dark}"
    rounded: "{rounded.md}"
    padding: "0.65rem 1rem"
    height: "44px"
  input:
    backgroundColor: "{colors.surface-dark}"
    textColor: "{colors.text-primary-dark}"
    rounded: "{rounded.md}"
    padding: "0.625rem 0.75rem"
    height: "44px"
  chip-info:
    backgroundColor: "{colors.state-info-bg}"
    textColor: "{colors.state-info-text}"
    rounded: "{rounded.pill}"
    padding: "0.25rem 0.65rem"
  chip-success:
    backgroundColor: "{colors.state-success-bg}"
    textColor: "{colors.state-success-text}"
    rounded: "{rounded.pill}"
    padding: "0.25rem 0.65rem"
  chip-warning:
    backgroundColor: "{colors.state-warning-bg}"
    textColor: "{colors.state-warning-text}"
    rounded: "{rounded.pill}"
    padding: "0.25rem 0.65rem"
  chip-critical:
    backgroundColor: "{colors.state-critical-bg}"
    textColor: "{colors.state-critical-text}"
    rounded: "{rounded.pill}"
    padding: "0.25rem 0.65rem"
  card:
    backgroundColor: "{colors.surface-dark}"
    textColor: "{colors.text-primary-dark}"
    rounded: "{rounded.lg}"
    padding: "1rem"
  alert:
    backgroundColor: "{colors.surface-raised-dark}"
    textColor: "{colors.text-primary-dark}"
    rounded: "{rounded.2xl}"
    padding: "0.75rem 0.875rem"
  table-shell:
    backgroundColor: "{colors.surface-dark}"
    textColor: "{colors.text-primary-dark}"
    rounded: "{rounded.lg}"
  table-header:
    backgroundColor: "{colors.surface-raised-dark}"
    textColor: "{colors.text-secondary-dark}"
    padding: "0.5rem 0.75rem"
    height: "36px"
  table-row:
    backgroundColor: "{colors.surface-dark}"
    textColor: "{colors.text-primary-dark}"
    padding: "0.5rem 0.75rem"
    height: "36px"
  cell-state-ok:
    backgroundColor: "{colors.state-success-bg}"
    textColor: "{colors.state-success-text}"
  cell-state-atencion:
    backgroundColor: "{colors.state-warning-bg}"
    textColor: "{colors.state-warning-text}"
  cell-state-critico:
    backgroundColor: "{colors.state-critical-bg}"
    textColor: "{colors.state-critical-text}"
  cell-state-bloqueado:
    backgroundColor: "{colors.cell-bloqueado-bg}"
    textColor: "{colors.cell-bloqueado-fg}"
  cell-state-sin-datos:
    backgroundColor: "{colors.surface-dark}"
    textColor: "{colors.text-secondary-dark}"
---

# Design System: Last Planner AIA

> **Guía de consumo, no fuente de verdad.** Este archivo describe el sistema para
> desarrolladores y asistentes que generan UI. La autoridad ejecutable vive en
> `public/css/tokens.css`, `public/css/design-system/core.css`,
> `public/css/aia-design-system.css` y `docs/design-system/`. El laboratorio en
> `/internal/design-system` es la referencia visual versionada. El frontmatter de
> arriba refleja `tokens.css` para que el linter/live-panel funcionen; **si el
> frontmatter y `tokens.css` divergen, `tokens.css` gana.** Design System v1.0.0
> (`stable`), piloto `/programa-general`.

## 1. Overview

**Creative North Star: "La Sala de Control Serena"**

Last Planner AIA es la superficie donde un equipo de obra lee el estado de la
planificación y decide la siguiente acción antes de que una restricción escale a
incidente. La estética es la de un puesto de mando en penumbra: fondo oscuro y
tranquilo, superficies que retroceden, y color reservado para lo que exige una
decisión. Nada compite por atención salvo el punto crítico. El sistema comunica
"seguridad, confianza y foco" mediante contención, no ornamento.

El tema **dark es el modo operativo por defecto** (DS-009): cuando el usuario no
ha guardado preferencia, la app arranca en oscuro. Las cuatro submarcas de AIA
—Corporativo, Construcción, Inmobiliario, Arquitectura— viven como acentos de
dominio, cada una con una variante perceptual "on-dark" que preserva el matiz y
cumple contraste AA. Los estados (info, success, warning, critical) forman un
vocabulario semántico estable: el color **refuerza** el significado, nunca lo
sustituye. La densidad es operativa —tablas, filtros, chips— y se adapta: compacta
en desktop ancho, táctil por debajo.

Este sistema rechaza explícitamente lo que PRODUCT.md nombra como anti-referencia:
**no debe verse decorativo, saturado de alertas, ni exigir lectura innecesaria
antes de actuar.** Prohibido el dashboard-cliché de métrica gigante con degradado,
el glass gratuito, el eyebrow en mayúsculas sobre cada sección y cualquier acento a
plena saturación en estados inactivos. La familiaridad con las mejores herramientas
operativas (Linear, Stripe, Raycast) es una virtud aquí: la interfaz debe
desaparecer dentro de la tarea.

**Key Characteristics:**
- Superficie **dark-first** y casi plana; la profundidad se insinúa, no se grita.
- Sistema de submarcas OKLCH con variante `on-dark` perceptual por dominio (matiz preservado, AA).
- Vocabulario semántico de estados que **refuerza** el significado (color + texto + `data-aia-severity`/`data-aia-urgency`), nunca solo color.
- Elevación mínima con tintes fríos; **glass reservado para jerarquía** (shell, nav, modales, paneles, cards).
- Montserrat para jerarquía de decisión; Inter para todo lo operativo. Sin Roboto.
- Objetivos táctiles de `44px`, foco visible de 4px, `prefers-reduced-motion` respetado.
  **Excepción registrada — superficies de datos densas:** ver §Densidad de datos.
- Densidad adaptativa: Compacta desde 1200 px, Táctil por debajo.

## 2. Colors — Submarcas sobre penumbra

La paleta es un sistema de cuatro submarcas de dominio sobre un neutro verde-frío
casi negro, con un léxico de estados de tinte suave que se mantiene constante entre
temas. El acento aparece por significado, no por decoración.

### Primary
- **Verde Corporativo** (`#1a5633` · oklch(32% 0.07 148.5); on-dark `#6c9077`): el
  color de identidad de AIA y de la **acción primaria**. En el tema operativo dark,
  los botones primarios usan la variante on-dark `#6c9077` con texto `#141c18`;
  su hover aclara a `#8cb4a1`. En light usa `#1a5633` con texto blanco.

### Secondary
- **Naranja Construcción** (`#b55211` · oklch(45% 0.16 46.2); on-dark `#c57247`):
  contexto y acciones del dominio de obra. Es el segundo acento con mayor presencia;
  reservado para el registro "construcción" (`.aia-btn--construction`), no para énfasis genérico.

### Tertiary
- **Aqua Inmobiliario** (`#00a499`; on-dark `#2caa9f`): dominio inmobiliario y, en
  datos, la serie **plan** (`--ds-active-data-plan`). El de mayor contraste on-dark (6.09:1).
- **Violeta Arquitectura** (`#6752bf`; on-dark `#877cd1`): dominio de arquitectura.
  Acento de dominio, nunca acción primaria.

### Neutral
- **Lienzo Dark** (`#0b100d` canvas · `#111a15` page): los dos fondos del tema operativo.
- **Superficies Dark** (`rgba(28,36,31,.92)` surface · `rgba(35,48,41,.86)` raised · `rgba(35,48,41,.66)` glass): capas de contenido semitranslúcidas.
- **Tinta Dark** (`#f7faf8` primaria · `#c7d4cc` secundaria): texto sobre penumbra.
- **Borde Dark** (`rgba(221,239,230,.22)`): separadores tenues; el foco usa `#2caa9f`.

### Estados semánticos — dos canales, no una lista

Aquí está el error más caro que puedes cometer leyendo este archivo: **la paleta de
estado no es una lista plana de tintes.** Son **dos canales ortogonales** y hacen falta
los dos, porque uno solo no puede decir las dos cosas. Toda la escala está **invertida a
oscuro**: tintes profundos con tinta clara encima, nunca los pasteles de un tema claro.

**Canal 1 — NIVEL: qué tan urgente es.** Cuatro peldaños
(`--ds-color-state-{info,success,warning,critical}-*`), definidos en
`docs/design-system/state-semantics.json` como `neutral` → info, `healthy` → success,
`attention` → warning, `urgent` → critical, cada uno con su par `severity`/`urgency`.
**Se pinta en el acento** —borde o punto— y es lo que consumen chips y alertas.

- **Info**: contexto o falta de datos, sin corrección inmediata.
- **Success**: controlado o completado, continuar el ciclo normal.
- **Warning**: atención, revisar antes del siguiente hito.
- **Critical**: acción inmediata — bloqueo, atraso, riesgo o error recuperable.

**Canal 2 — MATIZ: de qué estado se trata.** Ocho anclas
(`--ds-state-tint-{teal,green,amber,red,blue,violet,orange,neutral}`), con etiqueta
propia en `state-semantics.json`: Contexto, Controlado, Por resolver, Bloqueado o
vencido, En marcha, Sin datos suficientes, Fuera de plazo, Silencio. **Se pinta en el
fondo.**

**Por qué no se pueden fundir.** `missing` (violeta) y `completed-late` (ámbar) son
ambos `attention`; `critical` (rojo) y `delayed` (naranja) son ambos `urgent`. Con el
matiz separado del nivel, los tres `attention` de Plan de Compras siguen
distinguiéndose entre sí y la regla del contrato —«urgencia `now` siempre usa
`critical`»— se cumple igual, porque vive en el acento.

**La trampa al leer el frontmatter.** `colors.state-*` es **el canal de nivel, no la
paleta entera**: cuatro de las ocho anclas de matiz coinciden bit a bit con el `-bg` de
su nivel (`red`=critical, `amber`=warning, `green`=success, `teal`=info), así que una
lista de cuatro pares *parece* cubrirlo todo y cubre la mitad. `blue`, `violet`,
`orange` y `neutral` **no tienen token de nivel** y solo existen como matiz. Por eso el
frontmatter lleva los ocho `state-tint-*` aparte y con los dos canales rotulados.

**La paleta es NOMINAL, no ordinal.** Ocho anclas y nada más. Hubo una versión con tres
pasos por familia derivados del ancla: medida en el navegador, la separación máxima
entre dos pasos consecutivos era 1,012:1 de contraste y ΔE-OK 0,0168 —bajo el umbral de
percepción—, o sea un solo color disfrazado de tres, y dos entradas de leyenda que el
usuario filtra por separado pintaban fondos bit-idénticos. Sobre este lienzo no hay eje
de intensidad que gastar: WCAG es ciego al croma, y entre el ancla más oscura
(`#431414`, L=0,268 OKLCH) y el fondo de página (`#111a15`, L=0,207) quedan 0,061 de
margen. **Queda un solo eje útil: el matiz.**

**Las variantes `-light` no son de pantalla.** `--ds-color-state-*-light` se pensó como
juego de soporte para **impresos y XLSX**, donde el fondo es papel. Si escribes una de
ellas en una hoja de la aplicación, estás pintando un pastel claro sobre penumbra.

Son **reserva sin cablear**, y conviene saber por qué: la exportación a Excel existe
—`src/Controllers/Gestion/ReportController.php`, vía PhpSpreadsheet, pinta el estado de
cada fila— pero **no las consume**. Lleva su propia paleta ARGB escrita a mano, con
valores que además no coinciden con estos. Hay dos paletas claras para lo mismo.
Unificarlas cambiaría el aspecto de los Excel ya repartidos, así que es decisión aparte;
el usuario resolvió el 2026-08-03 dejarlas y decir la verdad en el comentario de
`tokens.css`, no tomarla de oficio.

### Named Rules

**La Regla On-Dark.** Toda submarca tiene una variante `on-dark` derivada por
mezcla perceptual OKLCH con blanco, con el **matiz oficial preservado** y contraste
`≥4.5:1` del texto `#141c18` encima (DS-008). En dark los fondos retroceden y los
acentos suben en luminosidad; **nunca se invierte el color mecánicamente**.

**La Regla del Color que Refuerza.** El color nunca es el único canal. La gravedad
y la urgencia se expresan con `data-aia-severity` y `data-aia-urgency` y el texto
siempre nombra el significado. Los estados `now` usan siempre `critical`; un estado
sin datos **no** escala a `warning` por sí solo.

**La Regla del Acento Escaso.** El acento es para acción primaria, selección actual
e indicadores de estado. Prohibido a plena saturación en estados inactivos y prohibido como relleno decorativo.

**La Regla de Un Matiz por Estado.** Dentro de un mismo módulo, **dos estados distintos
nunca comparten matiz**. Es contractual, no estilístico: sin eje de intensidad, el matiz
es lo único que los separa, y dos leyendas que el usuario filtra por separado no pueden
pintar el mismo fondo. `/programacion-intermedia` (8 estados) y `/programa-general` (7)
se reasignaron para cumplirlo **en la declaración**.

> **Cuidado: la regla está vigilada en el papel, no en el píxel.** Sus dos guards
> recorren `state-semantics.json` y comprueban que no se repita `hue` **dentro de ese
> mismo archivo** — validan una declaración contra sí misma y **nunca leen el CSS**. Hoy
> `/programa-general` diverge: el contrato declara «Actividad Futura» → `green` y «En
> Curso» → `blue`, y `public/css/styles.css` pinta **las dos** con
> `--ds-cell-state-ok-*`. El mismo par, medido 8,88:1 en ambas porque es el mismo color.
> Se fundieron al migrar la grilla a la escala de celda, que es una escala de **nivel**,
> y ambos estados son `healthy`: migrar por nivel descarta el matiz, que es justo el eje
> que la regla existe para conservar. Peor: la leyenda **sí** los distingue
> (`--pg-dot-future` deriva de `success`, `--pg-dot-progress` de `info`), así que promete
> una diferencia que la grilla no cumple. Antes de fiarte de la regla en un módulo,
> **comprueba el CSS**, no solo el JSON.

Lo que **sí** sigue permitido es derivar dos intensidades
del mismo matiz para dos superficies del **mismo** estado —el chip de la leyenda y la
fila de la grilla en `/programacion-semanal`—. Vigilan la regla
`tests/design-system/state-tint-ladder.test.mjs` (texto del CSS y del contrato) y
`tests/browser/state-tint-ladder.mjs` (valor resuelto, separación entre los 28 pares y
unicidad por módulo).

## 3. Typography — Decisión y operación

**Display Font:** Montserrat (fallback -apple-system, "Segoe UI", Roboto, sans-serif) — servida local, pesos 600–700.
**Body Font:** Inter (fallback -apple-system, "Segoe UI", Roboto, sans-serif) — servida local, pesos 400–800.
**Mono Font:** `ui-monospace` (SFMono-Regular, Menlo, Consolas) — cifras y datos técnicos puntuales.

**Character:** un contraste de dos sans humanistas por **función, no por decoración**:
Montserrat aporta peso y autoridad a las decisiones de alto impacto; Inter desaparece
en la lectura densa de tablas, formularios y ayudas. `letter-spacing` fijado en `0`:
nada de tracking exagerado. Fuentes latinas servidas localmente con hash y licencia
OFL (Inter v20, Montserrat v31; DS-007).

### Hierarchy
- **Display** (Montserrat 700, `1.875rem`, lh 1.2): título de página y valores KPI (`.aia-title`, `.aia-kpi-value`).
- **Headline** (Montserrat 700, `1.5rem`, lh 1.2): encabezados de sección de alto impacto.
- **Title** (Montserrat 600, `1.25rem`, lh 1.2): títulos de card, panel y modal.
- **Body** (Inter 400, `1rem`, lh 1.5): cuerpo operativo; prosa a 65–75ch, datos densos pueden correr más.
- **Label** (Inter 500, `0.875rem`, lh 1.2, sin uppercase): etiquetas de control, ayudas (`.aia-label`, `.aia-helper`).

La escala es **rem fija**, no fluida: `--ds-type-size-xs`…`3xl` (0.75 → 1.875rem).

### Rampa densa de tablas (consagrada 2026-08-20)

La familia de tablas desktop opera desde hace tiempo tres pasos por debajo de
`--ds-type-size-xs` que este documento no declaraba y el detector marcaba en cada
edición. Se **consagran** como rampa densa oficial — son la realidad medida de la
excepción de densidad (piso duro 11px de PRODUCT.md):

- **Dense-label** `0.72rem` (~11.5px): chip de estado en celda densa (PS), texto del
  tooltip de estado (`--ds-chip-font-size`).
- **Dense-meta** `0.70rem` (~11.2px): metadatos de leyenda y cabeceras auxiliares.
- **Dense-floor** `0.62rem` (~9.9px): **solo** elementos secundarios no textuales-clave
  ya existentes (contadores del drawer); ningún dato principal baja aquí. Está bajo el
  piso de 11px: no se le suman usos nuevos — se hereda, no se imita.

Pendiente declarado: tokenizarlos (`--ds-type-dense-*`) cuando un frente tipográfico
recorra sus usos; hoy se documentan para que el sistema y el detector digan lo mismo.

### Named Rules

**La Regla de Escala Fija.** Los tamaños son rem fijos, nunca `clamp()` fluido en UI
de producto. Un h1 que encoge dentro de un sidebar se ve peor, no mejor. El fluido
queda para la marca, no para la operación.

**La Regla de Palabra Íntegra.** El texto de estados y chips envuelve **entre
palabras** y nunca fragmenta una palabra (DS-001/DS-015): `overflow-wrap: normal`,
`word-break: normal`, `hyphens: none`. Un chip nunca parte "Programación" a mitad.

## 4. Elevation — Casi plano, tinte frío

El sistema es casi plano. La profundidad se construye por **capas tonales** (canvas →
surface → raised) y bordes tenues, no por sombras profundas. Las sombras existen pero
son sutiles y tintadas con la tinta fría de la marca (`rgba(20,28,24,·)`), no negro
puro. Las cards y paneles descansan en `--ds-shadow-xs`; las sombras mayores se
reservan para material que flota (modales, popovers, glass).

### Shadow Vocabulary
- **xs** (`0 1px 2px rgba(20,28,24,.06)`): reposo de cards, paneles, toolbars, tablas.
- **sm** (`0 2px 6px rgba(20,28,24,.08)`): hover discreto y controles elevados.
- **md** (`0 8px 24px rgba(20,28,24,.10)`): material glass y nav.
- **lg** (`0 16px 48px rgba(20,28,24,.14)`): popovers y menús.
- **xl** (`0 24px 72px rgba(20,28,24,.18)`): modales y capas superiores.
- **focus** (`0 0 0 4px var(--ds-active-focus-ring)`): anillo de foco visible, 4px.

### Named Rules

**La Regla del Vidrio Jerárquico.** El glass (`backdrop-filter: blur(18px)`, `.aia-glass`)
se usa **solo para jerarquía**: shell, nav, modales, paneles y cards. **Tablas y
grillas priorizan legibilidad** con superficies opacas — nunca glass detrás de datos.

**La Regla Plana por Defecto.** Las superficies están planas en reposo (`xs`). Una
sombra mayor es respuesta a un estado (flotar, elevarse, enfocar), no decoración fija.

## 5. Components

La API pública es un conjunto de clases `aia-*` que consumen exclusivamente tokens
`--ds-*`/`--aia-*`. Todo estado interactivo se resuelve contra el tema activo vía
`--ds-active-*`. Ningún componente migrado introduce color, tipografía, radio, sombra,
spacing o estado local.

### Buttons
- **Shape:** radio de control `--ds-radius-md` (`0.75rem`), altura mínima `44px`, `inline-flex` centrado.
- **Primary** (`.aia-btn`): en dark, fondo `#6c9077` y texto `#141c18`; padding `0.65rem 1rem`.
- **Hover / Focus:** hover aclara el fondo a `#8cb4a1` (solo `background`/`transform`/`box-shadow`, nunca reflow); focus-visible aplica `outline: 2px solid` del anillo activo + `box-shadow` de foco de 4px.
- **Secondary** (`.aia-btn--secondary`): superficie elevada + borde activo + texto primario.
- **Construction** (`.aia-btn--construction`): dominio obra, fondo `#c57247` / texto `#141c18`.

### Chips (estados)
- **Style:** píldora (`--ds-radius-pill`), `font-weight` 800, altura mínima `1.75rem`, con `data-aia-severity`/`data-aia-urgency` como fuente de significado.
- **State:** cuatro niveles con tinte fijo (info/success/warning/critical) constantes entre temas; el texto siempre nombra el estado. Envuelven entre palabras, nunca fragmentan.

### Cards / Containers
- **Corner Style:** `--ds-radius-card` = `--ds-radius-lg` (`1rem`); paneles y modales a `--ds-radius-2xl`.
- **Background:** superficie del tema activo (`--ds-active-surface`), semitranslúcida en dark.
- **Shadow Strategy:** `--ds-shadow-xs` en reposo (ver Elevation).
- **Border:** `1px` del borde activo (`--ds-active-border`). Aquí resuelve al **separador**: una
  tarjeta no es un control y WCAG 1.4.11 no la gobierna (ver «El par de bordes»).
- **Internal Padding:** `--ds-card-padding` (`clamp(0.875rem, 1.8vw, 1.25rem)`).

### El par de bordes — control contra separador

`--ds-active-border` **significa dos cosas distintas según el elemento**, y no hace falta que elijas:
el design system lo re-vincula solo.

- **CONTROL** — frontera de algo con lo que el usuario interactúa (campo, botón, selector, chip
  accionable). Debe alcanzar **3:1** contra las dos superficies que toca (WCAG 1.4.11). Medido sobre
  los quince fondos oscuros reales de la app: **3,14–3,39:1**.
- **SEPARADOR** — filete decorativo entre regiones cuyos rellenos ya difieren (divisores, cabeceras
  de drawer, bordes de tarjeta, chips de conteo). 1.4.11 **no** lo gobierna: se mantiene discreto a
  propósito.

**Regla de uso:** si delimita algo con lo que se interactúa, es CONTROL; si sólo separa dos zonas, es
SEPARADOR. **Nunca uses el separador para un control.**

En la práctica escribes `var(--ds-active-border)` como siempre: `core.css` re-vincula la variable
sobre el propio elemento, así que sobre un control resuelve al valor de control **sin importar el
archivo, la capa o el `!important`** desde donde lo declares. Los descendientes de un control vuelven
al separador, para que un contador dentro de un chip no herede la frontera del chip.

**Límite conocido:** el mecanismo actúa sobre elementos, así que **no alcanza los controles cuyo
borde se pinta en un pseudo-elemento** — el caso de los interruptores de Bootstrap, que dibujan su
frontera en `label::before`. Ésos siguen en el valor de separador y son deuda aparte.

### Tablas y grillas — el contrato de tabla

Éste es el camino único para poner datos tabulares en pantalla. No hay un segundo.

**1. Envuelve la grilla en `.aia-grid-shell`.** Es el envoltorio canónico, definido en
`public/css/design-system/core.css` y servido por
`entrypoints/core.css`. `.aia-table-shell` es su sinónimo exacto y comparte la misma
regla; usa `grid-shell` para grillas de vendor (Handsontable, DataTables, AG Grid) y
`table-shell` para una `<table>` propia. El shell aporta superficie, borde, sombra
`xs`, radio de tabla (`--ds-radius-table`, `1rem`), `overflow: hidden` y —lo que
importa— **re-publica los tokens de tabla como variables privadas del subárbol**
(`--_row-h`, `--_cell-px`, `--_cell-py`, `--_header-bg`, `--_header-fg`, `--_border`),
que es de donde los adaptadores tiran. Sin el shell, un adaptador queda sin métrica.

**2. Consume los doce `--ds-table-*`.** Son la métrica y el color de cualquier tabla;
ninguno se sustituye por un valor propio.

| Token | Qué gobierna |
|---|---|
| `--ds-table-row-h` | alto de fila (`2.25rem` = 36 px) |
| `--ds-table-cell-pad-x` / `-pad-y` | relleno de celda (`0.75rem` / `0.5rem`) |
| `--ds-table-header-bg` / `-header-fg` | cabecera: superficie elevada + tinta secundaria |
| `--ds-table-border` | filete de la rejilla (borde activo → **separador**, no control) |
| `--ds-table-zebra` | banda alterna, mezcla de superficie con tinta primaria |
| `--ds-table-row-hover` / `-row-selected` | fila apuntada y fila elegida, ambas mezcladas con el verde de marca |
| `--ds-table-cell-focus` | anillo de celda enfocada (`inset`, verde de marca) |
| `--ds-table-empty-bg` / `-empty-fg` | estado vacío de la tabla |

Los cinco de fondo se derivan con `color-mix(in oklch, …)` sobre la superficie activa:
**su valor final no existe en ningún archivo**, solo en el navegador. Por eso el gate
que los vigila es de runtime.

**3. Pinta el significado con la escala `--ds-cell-state-*`.** Siete peldaños, cada uno
con par `bg`/`fg`: `neutral`, `ok`, `atencion`, `riesgo`, `critico`, `bloqueado`,
`sin-datos`. **No es una paleta nueva**: es la capa de significado de tabla montada
sobre `--ds-color-state-*`, y desde el 2026-08-03 deriva de ella:

- **Tres alias directos** — `ok` → success, `atencion` → warning, `critico` → critical.
- **Uno derivado** — `riesgo` = `color-mix()` al 45 % entre warning y critical, porque
  vive literalmente entre los dos y no se inventa.
- **Dos heredan la superficie activa** — `neutral` (tinta primaria) y `sin-datos`
  (tinta secundaria): una celda sin dato no tiene color propio, tiene menos voz.
- **Una sola ancla propia** — `bloqueado`, en azul OKLCH. Existe porque el canal de
  **nivel** no tiene un peldaño de «detenido por otro», y el informativo significa otra
  cosa. Ese razonamiento sigue en pie para el nivel, pero no se hizo contra el canal de
  **matiz**, que entonces no estaba a la vista.

  **Deuda registrada (2026-08-03).** `--ds-cell-state-bloqueado-*` ancla color propio
  pese a que `--ds-state-tint-blue` es un candidato perceptualmente cercano; queda
  pendiente de decisión del usuario porque la paleta actual tiene **aprobación visual
  explícita** del 2026-08-03. Medido: `#17334f` es OKLCH L 0,314 · C 0,061 · H 250,4°
  frente al `oklch(0.35 0.05 260)` de `bloqueado` — 0,036 de luminosidad y 9,6° de matiz
  de separación. No lo corrijas de oficio: cambiarlo invalidaría en silencio una paleta
  ya aprobada lado a lado.

Antes esta escala anclaba cinco colores propios y duplicaba la escala del design
system. Si añades un peldaño, la pregunta correcta es de qué estado existente deriva.

**4. Traduce los nombres del módulo con el vocabulario compartido.** Cada módulo tiene
sus propios nombres de estado (`pg-state-atrasada`, `ps-alert-critical`,
`pdc-completed-late`…). El mapa único vive en
`public/js/modules/shared/cell-state-vocabulary.mjs` y expone `CELL_STATE`, `STATE_MAP`
y `getCanonicalCellState(className)`. **No escribas una clase `ds-cell-*` a mano**: pide
la canónica al mapa y añade allí el alias nuevo.

**Pero el mapa traduce por nivel, y eso pierde matiz.** `STATE_MAP` lleva
`pg-state-actividad-futura` y `pg-state-en-curso` **los dos a `CELL_STATE.OK`**, cuando
el contrato les asigna matices distintos (`green` y `blue`). Es el mismo defecto que
tiene el CSS de Programa General, en el otro extremo del cable. Si añades un alias,
comprueba en `state-semantics.json` qué **matiz** le toca: si dos estados de tu módulo
caen en el mismo peldaño de celda, la escala de siete no te alcanza y estás a punto de
pintar dos cosas distintas del mismo color.

**5. Usa el adaptador de tu librería, no un skin.** Cada vendor entra por
`entrypoints/attach-*.css` (que lo importa con `layer(vendor)`) y luego su adaptador:

- **Handsontable** — `adapters/handsontable.css` (más
  `adapters/programa-general-handsontable.css` para el piloto).
- **DataTables** — `adapters/datatables.css`.
- **AG Grid** (`ag-grid-community`) — no lleva adaptador CSS: la SPA de Plan de Compras
  configura el tema en `pdc-app/src/lib/agGrid.ts`, donde `themeQuartz.withParams()`
  recibe los mismos tokens como `var()` con respaldo literal para `npm run dev`. El
  registro de módulos es selectivo a propósito (nada de `AllCommunityModule`).

Un skin de vendor descargado, una hoja fuera de capa o un hex suelto en el módulo están
vetados por el audit y por el gate de entregas sin capa.

**Ojo con la métrica: hay dos, y no se mezclan.** `--ds-table-row-h` (36 px) es la de
las tablas generales. Plan de Compras corre a `28/32/13/10` por la **excepción de
densidad registrada** (§5 bis), que aplica solo a esa superficie.

### Named Rules

**La Regla del Shell Único.** Toda grilla vive dentro de `.aia-grid-shell` (o
`.aia-table-shell`). Es lo que convierte los tokens de tabla en variables que el
adaptador puede leer; una grilla suelta se queda sin métrica y hereda lo que pille.

**La Regla de la Escala Derivada.** `--ds-cell-state-*` no inventa color: deriva de
`--ds-color-state-*`. Un peldaño nuevo se justifica nombrando de qué estado sale, y
`bloqueado` es la única excepción viva.

**La Regla de los Dos Gates.** El contrato de tabla se vigila en dos superficies y
ninguna sustituye a la otra: `scripts/design-system-table-contract.mjs` (estático,
enrutado en `npm run test:design-system:static`) comprueba que los doce tokens, los
catorce de la escala, la regla del shell y el módulo de vocabulario **existan**;
`tests/browser/design-system-table-contract.runtime.mjs` (en
`npm run test:design-system:runtime`, sobre `/internal/design-system` con filas
cargadas) comprueba que **resuelvan** en el motor y que cada peldaño cumpla AA sobre su
propio fondo. Un token puede existir en `tokens.css` y llegar vacío a la celda; el gate
estático daba verde sobre una tabla sin filas.

### Inputs / Fields
- **Style:** ancho completo, altura mínima `44px`, radio de control, superficie del tema, borde activo
  `1px` — que aquí resuelve al **control**, a 3:1 (ver «El par de bordes»).
- **Focus:** `outline` sólido del anillo activo + `box-shadow` de foco de 4px; sin glow decorativo.
- **Vocabulario:** `.aia-input`, `.aia-select`, `.aia-textarea` comparten un mismo contrato.

### Navigation
- **Shell global:** el **sidebar colapsable canónico** reemplaza al navbar superior como navegación global (DS-027). Rail visible colapsado por defecto en vistas de grilla, con persistencia local (`aia-sidebar-state`). El estado colapsado pulido (sin-scroll, píldora de label, separadores, iconos 20px) es primitiva versionada de `navigation.css` (DS-029).
- **Context-bar:** el cambio de semana ocurre en el chip de la context-bar; los grupos de navegación se filtran por rol en servidor.
- **Compatibilidad:** el drawer táctil adaptativo permanece como fallback hasta cerrar aprobación visual; misma lista, distinta composición según ancho (DS-011/DS-026).

### States & Feedback
- **`.aia-alert`:** mensaje contextual sobre superficie elevada, radio de panel.
- **`.aia-empty`:** estado vacío que **enseña la interfaz** (borde dashed, centrado), no un "nada aquí".

### Replanteo de estados 2026-08-20 — chip sólido, fila sutil, filete, tooltip

**Deroga, donde contradiga, lo dicho arriba en «Estados semánticos» y «Chips».**
Decisión de Felipe (2026-08-20), auditada WCAG AA + manual AIA; contrato ejecutable en
`docs/design-system/state-semantics.json` (`hues[].solid/solidText/row`, `brandAudit`)
con guard `tests/design-system/state-solid-contract.test.mjs`.

- **El chip sólido es el portador fuerte de identidad**: fondo
  `--ds-state-solid-<hue>` con texto oscuro de su familia
  (`--ds-state-solid-<hue>-text`), AA ≥4.5:1 garantizado por guard. Naranja, ámbar,
  violeta y teal son los hex del manual AIA; el rojo es custom (`#e15a52`) porque el
  del manual falla AA; el azul es desviación registrada (AIA no tiene azul).
- **La fila baja a tinte sutil** `--ds-state-row-<hue>` (identidad de apoyo, texto
  `--ds-active-text-primary`). Los `--ds-state-tint-*` viejos quedan para el PDC y no
  se usan en filas de estado nuevas.
- **La gravedad vive completa en el filete** (`severity-rail`): urgente 6px
  `#ff7a6e`, atención 4px `#ffd23f`, y el marcador positivo escaso `ready` 3px
  `#7ee2a8` solo en lo activamente listo (`rail: 'ready'` declarado por estado, nunca
  derivado de nivel+matiz). `Controlado` sigue sin barra: la ausencia es la señal. El
  chip **ya no codifica nivel** — la excepción crítica del 2026-08-11 se retiró.
- **Todo chip de estado lleva tooltip explicativo** (`state-tooltip.css` +
  `public/js/design-system/state-tooltip.js`): hover **y** foco muestran el porqué
  (mismas frases que la Guía Operativa), Escape lo descarta, y se voltea
  vertical/horizontal cuando no cabe (WCAG 1.4.13). En PS/PI el clic conserva el
  drawer (móvil/tablet); en PG el chip es focuseable con nombre accesible
  (`role="note"` + `aria-label` con etiqueta y porqué) y anillo `--ds-shadow-focus`.
  Bajo 1180px el área táctil del chip-botón sube a 44px sin crecer visualmente.
- En celda densa de PS el chip corre a `0.72rem`/600 en una línea con elipsis
  (excepción declarada a la Regla de Palabra Íntegra: el label entero vive en
  tooltip, `aria-label` y drawer).

### Arquitectura de consumo (contrato)

El entrypoint productivo está **segmentado**. Las superficies migradas consumen
`DesignSystemHeadComponent::renderForModule('<moduleId>')`, que emite
`public/css/design-system/entrypoints/core.css` más los adjuntos declarados en
`vendors[]` del manifiesto del módulo (`docs/design-system/manifests/`), validado por
el gate de partición/coherencia (`scripts/design-system-entrypoint-partition.mjs`),
con fallback fail-safe al agregador si el manifiesto falta o declara un vendor
desconocido. Las superficies **no** migradas siguen consumiendo el agregador
congelado y equivalente (ver `goals/segmentacion-entrypoint-css/`). La cascada de
capas es fija: `reset, vendor, theme, base, layout, components, utilities, module,
legacy-overrides` (DS-006). El tema por defecto lo aplica
`public/js/modules/aia_ui/theme-bootstrap.js` (dark sin flash); dark queda aplicado
de forma incondicional y sin conmutador, y el manejo de `prefers-reduced-motion`
vive en `public/js/modules/aia_ui/theme.js`.

#### Entregas sin capa

Una hoja que entra al documento **sin capa** gana a **todas** las capas en
declaraciones normales, así que derrota al design system por mucho que éste
declare lo correcto (para `!important` el orden se invierte y lo no capado pasa a
ser lo más débil; el gate razona sobre lo primero). El audit veta
`css-outside-layer` **dentro** de los archivos del repo; quien vigila **cómo
llegan** las hojas al documento es el gate de entregas sin capa, en dos carriles
contra un mismo inventario (`docs/design-system/unlayered-delivery-inventory.json`):

- **Estático** — `scripts/design-system-unlayered-delivery.mjs`: cada
  `<link rel="stylesheet">` escrito a mano en `views/` y cada hoja que `public/js/`
  monta con `rel = 'stylesheet'`.
- **Runtime** — `tests/browser/design-system-unlayered-delivery.mjs`: recorre
  `document.styleSheets` por ruta, incluidas las hojas que un vendor inyecta en un
  `<style>` y que no existen como archivo del repo.

La vía sancionada para un vendor es `entrypoints/attach-*.css`, que lo importa con
`layer(vendor)` y luego su adaptador. La salida por defecto ante un hallazgo es
**eliminar la entrega sin capa**, no declararla: añadir `!important` o CSS fuera de
capa para ganar la partida está vetado por el audit. `admin/` queda fuera de alcance
por `AGENTS.md` (AdminLTE no se migra).

### Flujo obligatorio antes de editar una superficie declarada

1. Ejecutar `$impeccable audit <superficie>` y revisar su manifiesto.
2. Mantener manifiesto, pruebas y evidencia en el **mismo** cambio.
3. Ejecutar el contrato estático fail-closed, validación funcional, Axe, consola/red, foco, targets de `44px` y QA visual **desktop dark**.
4. Obtener **aprobación humana** antes de reconciliar goldens.

Solo las superficies registradas como migradas fallan cerradamente. Un módulo legacy
no declarado queda congelado: para editarlo primero hay que crear su manifiesto y
activar sus gates. El enrutador `scripts/design-system-router.mjs` traduce "estos
archivos cambiaron" a "corre estos gates" (para UI sin manifiesto advierte, no
bloquea, y recuerda no subir `docs/design-system/audit-baseline.json`). La garantía
compartida real es CI (`.github/workflows/design-system.yml`) más los gates en
`scripts/`; el hook local `PostToolUse` es solo una ayuda temprana y vive en
configuración por máquina (`.claude/settings.json` / `.codex/hooks.json`, en `.gitignore`).

## 5 bis. Densidad de datos — superficies de hoja de cálculo

Decisión del dueño del producto (2026-07-29), tomada sobre medición en `/plan-compras`:
una tabla de 42 px de fila y 48 px de encabezado dejaba **17 filas del presupuesto** en
un viewport de 1180×820, y el módulo convivía con **trece tamaños de letra distintos**
(11 a 26 px) más treinta y cuatro elementos sin regla que heredaban el 16 px del
navegador. La referencia es Excel: **la letra no se encoge, el aire alrededor sí**
(Excel usa 11 pt ≈ 14,7 px con filas de 20 px).

### Escala tipográfica de superficie densa

| Paso | Valor | Uso |
|---|---|---|
| `xs` | 11 px | rótulos en versalitas |
| `sm` | 12 px | ayudas, notas al pie de un dato, encabezado de tabla |
| `md` | 13 px | **cuerpo**: controles, celdas, navegación |
| `lg` | 15 px | subtítulos de sección |
| `xl` | 18 px | título de pantalla |
| cifra | 22 px | el único número que manda en la pantalla |

Piso duro: **11 px**. Nada por debajo, y el contraste AA (4,5:1) se verifica medido,
no estimado.

### Métrica de tabla

**Actualizado 2026-08-03 (Task 19, corrección de rumbo del dueño del producto):** el
corte del 2026-07-29 fijaba fila y control en `28px` por calco directo de la medición de
`/plan-compras`, no porque `28px` fuera el suelo real. La premisa pasó a ser **maximizar
filas visibles** contra el suelo real de accesibilidad — no quedarse arriba de él por
inercia. Fila y encabezado (base) `24px` — **exactamente el mínimo de WCAG 2.2 SC 2.5.8
(AA), sin margen**: ningún control futuro dentro de una fila puede medir menos sin
incumplir de verdad —, celda `13px`, padding horizontal `10px`, padding vertical `2px`.
Las filas que envuelven texto (`autoHeight`) crecen lo que necesiten, y el encabezado
crece con `height: auto` si el texto necesita dos líneas legibles: comprimir rompiendo
palabras a mitad o truncando en ambigüedad es empeorar, por mucha fila que se gane.
Resultado medido en `/plan-compras`: 25 filas donde había 17 con la métrica antigua de
`42px`; con `24px` de fila la cuenta sube más — ver medición en el reporte del Task 19.

### Excepción al mínimo de 44 px

En una superficie de datos densa, **operada con ratón en desktop y sin equivalente
móvil**, fila y controles miden `24px` de alto — el suelo real de **WCAG 2.2 SC 2.5.8
(AA): 24×24px** para objetivos de interacción, no un valor arbitrario más compacto que
Excel o Figma. Es lo que hace posible maximizar la densidad sin cruzar accesibilidad.

- **Alcance:** familia de tablas desktop (`/programa-general`, `/programacion-intermedia`,
  `/programacion-semanal`, `/pdc`, `/plan-compras`) vía el contrato `--ds-table-*` del
  design system — ampliado el 2026-08-03 desde `/plan-compras` únicamente. Ver
  PRODUCT.md §Accessibility & Inclusion.
- **Lo que NO se relaja:** contraste AA (4,5:1), foco visible de 4px, orden de foco,
  navegación por teclado y `prefers-reduced-motion` siguen siendo obligatorios y
  verificados. El piso duro de fuente (`11px`) es solo para elementos secundarios; el
  dato principal no baja de `13px`.
- **Por qué es admisible:** el criterio de 44 px protege el acierto del dedo sobre un
  cristal. Esta familia se valida en el viewport canónico desktop (`1180x820`, dark) y
  hoy no se expone a táctil, así que el riesgo que el mínimo de 44px previene no existe
  aquí — pero el suelo de **24×24px sí aplica y no se cruza**.
- **Si alguna vez se abre a táctil:** esta excepción caduca y los controles vuelven a
  `44px` antes de exponer la superficie.

---

## 6. Do's and Don'ts

### Do:
- **Usa** tokens `--ds-*`/`--aia-*` y primitivas `aia-*`. Cambia primero tokens, componentes o capas canónicas.
- **Usa** Montserrat para títulos, métricas y jerarquía de decisión; Inter para cuerpo, navegación, formularios, tablas y ayudas.
- **Diseña y valida solo en desktop dark** de al menos 1180 px. Viewport canónico `1180x820`; secundario `1440x900`.
- **Mantén** WCAG AA, foco visible de 4px, objetivos táctiles de `44px` y una alternativa `prefers-reduced-motion` en toda animación.
  En superficies de datos densas el alto de fila/control es `24px` — el mínimo real de WCAG 2.2 SC 2.5.8 (AA), por la excepción registrada al mínimo de 44 px que documenta §5 bis; el resto no se relaja.
- **Reserva** el glass para jerarquía (shell, nav, modales, paneles, cards); tablas y grillas van opacas y legibles.
- **Envuelve** toda grilla en `.aia-grid-shell` / `.aia-table-shell`, consume los doce `--ds-table-*` y pide el peldaño de celda a `getCanonicalCellState()`, nunca escribas la clase `ds-cell-*` a mano.
- **Expresa** el estado con superficie + borde + texto del tema destino; anima solo `transform` y `box-shadow`, nunca `color`/`background` (DS-023/DS-025).
- **Registra** en `exceptions.json` cualquier excepción temporal, y mantén manifiesto + pruebas + evidencia juntos.
- **No cierres una pantalla sin su ayuda, y cambiarla cuenta como cerrarla otra vez.** Si tocas una
  pantalla del Plan de Compras y no tocas su entrada en `pdc-app/src/lib/ayuda.ts`, el cambio no
  está terminado. Una ayuda que miente es peor que ninguna, y lo único que lo evita es que la
  pantalla y su texto viajen en el mismo commit. `pdc-app/src/lib/ayuda.test.ts` atrapa la pantalla
  sin ayuda y la jerga; que el texto siga siendo **verdad** solo lo puede comprobar quien hace el
  cambio.
- **No repitas en la ayuda un mensaje que la pantalla ya da en el momento.** Un aviso que aparece
  cuando pasa la cosa llega mejor que el mismo texto detrás de un botón, y dos copias del mismo
  aviso envejecen por separado. La ayuda lo señala y dice qué hacer con él.

### Don't:
- **Nunca** introduzcas hex sueltos, estilos inline, bloques `<style>`, gradientes decorativos, skins de vendors ni nuevas CDN en módulos migrados.
- **Nunca** hagas que la interfaz se vea **decorativa, saturada de alertas o que exija lectura innecesaria antes de actuar** (anti-referencia de PRODUCT.md).
- **Nunca** uses el color como único canal de significado, ni acentos a plena saturación en estados inactivos.
- **Ojo con el tema claro:** `linen` quedó **retirado del producto** en F0 del goal `dark-mode-todos-los-modulos`; hoy dark es el único tema y no existe conmutador. Ya no está prohibido trabajar en claro, pero hacerlo es reconstruirlo desde los tokens, no reactivar una variante viva.
- **Nunca** uses `clamp()` fluido para tamaños de texto de UI, ni fragmentes una palabra en chips o estados.
- **Nunca** pintes en pantalla las variantes `--ds-color-state-*-light`: son reserva para impresos y XLSX. La escala de pantalla está invertida a oscuro.
- **Nunca** trates la paleta de estado como una lista plana de cuatro tintes ni repitas matiz entre dos estados de un mismo módulo: son dos canales —nivel en el acento, matiz en el fondo— y el matiz es el único eje que los separa. Tampoco derives pasos de intensidad de un ancla: la paleta es nominal, se midió, y los pasos resultan indistinguibles.
- **Nunca** ancles un color propio en `--ds-cell-state-*` ni instales un skin de vendor para una grilla: la escala deriva de `--ds-color-state-*` (salvo `bloqueado`) y cada librería entra por su adaptador en `layer(vendor)`.
- **Nunca** uses Roboto ni radios hardcodeados: Login, Projects, Programa General, Plan de Compras y el módulo de tema tienen presupuesto **cero** para hex sueltos, inline styles, `<style>`, Roboto y radios hardcodeados. (PDC, Contratos y Listado de Actividades salieron de esta lista el 2026-08-04 al eliminarse el PDC v1.)
- **Nunca** regeneres snapshots ni baselines para forzar verde: requieren decisión visual aprobada con hashes before/after.
- **Nunca** modifiques Programa General ni sus archivos protegidos desde la migración de otra superficie.
