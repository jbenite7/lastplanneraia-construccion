---
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
  # Tema light (alcance secundario; enviado)
  bg-canvas: "#f7faf8"
  bg-page: "#fbfdfc"
  text-primary: "#141c18"
  text-secondary: "#52645a"
  text-tertiary: "#72857a"
  # Estados semánticos (tintes fijos, independientes del tema)
  state-success-bg: "#ddefe6"
  state-success-text: "#1a5633"
  state-warning-bg: "#fff8e1"
  state-warning-text: "#5d4200"
  state-critical-bg: "#fdecec"
  state-critical-text: "#8f1d1d"
  state-info-bg: "#e3f9f7"
  state-info-text: "#006d66"
  # Deuda consciente (shipped-but-ungated)
  bg-linen: "#f4f1ea"
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
- **Lienzo Light** (`#f7faf8` canvas · `#fbfdfc` page) y **Tinta Light** (`#141c18` / `#52645a` / `#72857a`): alcance secundario enviado.

### Estados semánticos
- **Info** (bg `#e3f9f7` / text `#006d66`): contexto o falta de datos, sin corrección inmediata.
- **Success** (bg `#ddefe6` / text `#1a5633`): controlado o completado, continuar el ciclo normal.
- **Warning** (bg `#fff8e1` / text `#5d4200`): atención, revisar antes del siguiente hito.
- **Critical** (bg `#fdecec` / text `#8f1d1d`): acción inmediata — bloqueo, atraso, riesgo o error recuperable.

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
- **Border:** `1px` del borde activo (`--ds-active-border`).
- **Internal Padding:** `--ds-card-padding` (`clamp(0.875rem, 1.8vw, 1.25rem)`).

### Inputs / Fields
- **Style:** ancho completo, altura mínima `44px`, radio de control, superficie del tema, borde activo `1px`.
- **Focus:** `outline` sólido del anillo activo + `box-shadow` de foco de 4px; sin glow decorativo.
- **Vocabulario:** `.aia-input`, `.aia-select`, `.aia-textarea` comparten un mismo contrato.

### Navigation
- **Shell global:** el **sidebar colapsable canónico** reemplaza al navbar superior como navegación global (DS-027). Rail visible colapsado por defecto en vistas de grilla, con persistencia local (`aia-sidebar-state`). El estado colapsado pulido (sin-scroll, píldora de label, separadores, iconos 20px) es primitiva versionada de `navigation.css` (DS-029).
- **Context-bar:** el cambio de semana ocurre en el chip de la context-bar; los grupos de navegación se filtran por rol en servidor.
- **Compatibilidad:** el drawer táctil adaptativo permanece como fallback hasta cerrar aprobación visual; misma lista, distinta composición según ancho (DS-011/DS-026).

### States & Feedback
- **`.aia-alert`:** mensaje contextual sobre superficie elevada, radio de panel.
- **`.aia-empty`:** estado vacío que **enseña la interfaz** (borde dashed, centrado), no un "nada aquí".

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
`public/js/modules/aia_ui/theme-bootstrap.js` (dark sin flash); la API interactiva
linen/dark y reduced-motion vive en `public/js/modules/aia_ui/theme.js`.

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

## 6. Do's and Don'ts

### Do:
- **Usa** tokens `--ds-*`/`--aia-*` y primitivas `aia-*`. Cambia primero tokens, componentes o capas canónicas.
- **Usa** Montserrat para títulos, métricas y jerarquía de decisión; Inter para cuerpo, navegación, formularios, tablas y ayudas.
- **Diseña y valida solo en desktop dark** de al menos 1180 px. Viewport canónico `1180x820`; secundario `1440x900`.
- **Mantén** WCAG AA, foco visible de 4px, objetivos táctiles de `44px` y una alternativa `prefers-reduced-motion` en toda animación.
- **Reserva** el glass para jerarquía (shell, nav, modales, paneles, cards); tablas y grillas van opacas y legibles.
- **Expresa** el estado con superficie + borde + texto del tema destino; anima solo `transform` y `box-shadow`, nunca `color`/`background` (DS-023/DS-025).
- **Registra** en `exceptions.json` cualquier excepción temporal, y mantén manifiesto + pruebas + evidencia juntos.

### Don't:
- **Nunca** introduzcas hex sueltos, estilos inline, bloques `<style>`, gradientes decorativos, skins de vendors ni nuevas CDN en módulos migrados.
- **Nunca** hagas que la interfaz se vea **decorativa, saturada de alertas o que exija lectura innecesaria antes de actuar** (anti-referencia de PRODUCT.md).
- **Nunca** uses el color como único canal de significado, ni acentos a plena saturación en estados inactivos.
- **Nunca** trabajes, generes cambios, pruebas o evidencia para **mobile, tablet o el tema `linen`**: fuera del alcance visual vigente. `linen` se envía y el usuario puede activarlo con el toggle (`public/js/modules/aia_ui/theme.js`), pero **ningún gate lo valida** (deuda consciente); no lo tomes como cubierto.
- **Nunca** uses `clamp()` fluido para tamaños de texto de UI, ni fragmentes una palabra en chips o estados.
- **Nunca** uses Roboto ni radios hardcodeados: Login, Projects, Programa General, PDC, Contratos, Listado de Actividades y el módulo de tema tienen presupuesto **cero** para hex sueltos, inline styles, `<style>`, Roboto y radios hardcodeados.
- **Nunca** regeneres snapshots ni baselines para forzar verde: requieren decisión visual aprobada con hashes before/after.
- **Nunca** modifiques Programa General ni sus archivos protegidos desde la migración de otra superficie.
