---
capa: fuente
tipo: plan
estado: abierto
fecha: 2026-09-28
areas: [lps, design-system]
fuente: docs/superpowers/plans/2026-09-28-s05-rediseno-encargo-mockup.md
resumen: "Encargo para Codex: construir el mockup del rediseño de Programa General según la spec S05-REDISENO, con capturas en ambos temas, para el visto de Felipe. Sin tocar código de producción."
---

# Encargo para Codex — mockup del rediseño de Programa General

Emite: la sesión de Claude que planea (flujo maestro §2b). Ejecuta: Codex. Felipe lo ordenó el
2026-09-28. Te rige `AGENTS.md`; léelo completo, más `docs/coordinacion-sesiones.md`, `PRODUCT.md` y
`DESIGN.md` (§5 bis densidad, estados «Replanteo 2026-08-20», primitivas nuevas).

## Qué hacer

Construir **un mockup HTML estático** de la pantalla que describe la spec
`docs/superpowers/specs/2026-09-28-s05-rediseno-programa-general-design.md` (léela completa: es el
contrato). Es la pieza para el visto de Felipe: **no se toca código de producción** (`frontend/`,
`src/`, `views/`, `public/app/`, `public/css/` quedan intactos).

- Archivo: `public/mockups/s05-rediseno-2026-09-28.html` (no modifiques
  `s05-redesign-mockup.html` ni `s05-production-mockup.html`).
- Estilos: enlaza `/css/tokens.css`, `/css/aia-design-system.css` y
  `/css/design-system/theme-claro.css` como hace el mockup existente, y agrega solo CSS local que use
  tokens `--ds-*` y primitivas `aia-*` reales (verifica cada nombre en `public/css/tokens.css` y en
  `public/css/design-system/`; no inventes tokens). Sin hex, sin `!important`, sin CDN de íconos
  (hoy seis íconos de Font Awesome no se dibujan en producción: usa SVG en línea o texto).
- Tema: interruptor claro/oscuro con `data-aia-theme`, claro por defecto.
- Datos **ficticios** (el repo es público): un proyecto de obra inventado, 3–4 capítulos, ~60
  actividades con nombres realistas de 40–60 caracteres, todos los estados (Atrasada, Debe iniciar,
  Con alerta de restricciones, En curso, Actividad futura, Terminada, Fuera de ventana, Sin datos),
  fechas vencidas, avance con Δ positivo y negativo. Orden de cronograma puro.
- Debe mostrar, como piezas visibles: la banda de cabecera (≤ 56 px) con un solo control de semana,
  búsqueda, alternador Resumen/Detalle, acción principal con hora del último lote, «Exportar ▾»; la
  franja de señales (Actuar / Seguir / Contexto, chips en 0 atenuados, selección ≠ foco); la tabla
  densa con bandera de gravedad y tinte por fila, chip de estado junto a la actividad, avance con Δ,
  capítulos colapsables con resumen; el popover «?» de leyenda; el cajón de 440 px abierto sobre una
  actividad atrasada (solo lectura del contenido, mismo contenido real que hoy: sin campo de
  observación editable, recursos solo con dato); y la vista móvil de tarjetas de 3 líneas.
- Metas medibles que debes reportar medidas en navegador: a 1180×820 la tabla empieza en y ≤ 150 y
  se ven ≥ 20 actividades; fila de 24–28 px; objetivos de 44 px bajo 1180.

## Dónde trabajar

- Worktree propio desde `origin/docs/s05-rediseno-spec`, rama `codex/s05-rediseno-mockup`.
- Para verlo en el navegador, un contenedor propio de tu worktree (nunca reapuntes el `app`
  compartido): `LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps -d -p 8094:80 --name codex-mockup app`,
  y `http://localhost:8094/mockups/s05-rediseno-2026-09-28.html`. Apágalo al terminar.

## Capturas para el visto

Con Playwright, 8 capturas a página completa en `test-output/s05-rediseno-mockup/` y **cópialas**
a `docs/superpowers/plans/2026-09-28-s05-rediseno-capturas/` (Playwright vacía `test-output/` en
cada corrida): claro y oscuro × 1180×820 y 390×844, más cajón abierto (claro y oscuro, 1180) y
franja con un chip seleccionado y otro con foco (1180, claro).

## Verificación y cierre

- axe sobre el mockup a 1180 y 390, ambos temas: sin críticos ni serios; reporta el resto.
- `npm run test:design-system:static` RC 0 y `npm run test:wiki` RC 0, cada RC en su línea.
- Commits atómicos. Push de la rama y PR contra `main` con la condición de hecho declarada antes
  del CI (las 13 `G_*` en `success` en ambos temas). **No hagas merge**: lo decide Claude tras
  auditar y Felipe tras ver las capturas.
- Reporta en `docs/superpowers/plans/2026-09-28-s05-rediseno-mockup-informe.md`: qué se ve, las
  medidas, decisiones de diseño con su porqué y lo que no pudiste resolver.

## Paras solo en tres casos

(1) algo irreversible o prohibido (datos, RLS, `admin/`, deploy, borrar); (2) una decisión de
producto que la spec no resuelve; (3) el mismo fallo tres veces. Las decisiones de diseño y de
código dentro de la spec las tomas tú y las anotas. Te va a monitorear la sesión de Claude cada
5 minutos: si te escribe una corrección en el chat, aplícala.
