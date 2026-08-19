---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-18
areas: [proceso]
fuente: goals/dark-mode-todos-los-modulos/HANDOFF-5h-5k.md
resumen: Traspaso de la fase F1 de dark-mode-todos-los-modulos: que leer y en que orden para retomar el trabajo desde el ledger.
---

Continúo la fase F1 del goal `dark-mode-todos-los-modulos` en /Volumes/Crucial X6/Developer/lps-aia.

LEE PRIMERO, EN ESTE ORDEN:
1. .superpowers/sdd/progress.md — el ledger. Es la fuente de verdad del estado: contiene F0, F1
   completa hasta el tramo 5g-bis, y los defectos que se colaron con su causa.
2. goals/dark-mode-todos-los-modulos/plans/F1-styles-css.plan.md — el plan. Las tareas 5a a 5g-bis
   están escritas con su rango medido; 5h a 5k aún no.
3. goals/dark-mode-todos-los-modulos/specs/F1-styles-css.md y facts.md — spec y decisiones
   vinculantes del usuario.
4. goals/dark-mode-todos-los-modulos/validation-log.md — regresiones toleradas ABIERTAS. F1 no
   cierra con entradas abiertas, y el Step 4 de la Task final recorre este archivo.
OJO: goals/ y .superpowers/ están en .gitignore, así que esos archivos existen sólo en disco local.

ESTADO EXACTO
HEAD = 22f4c98, sincronizado con origin/main. F0 cerrada. F1 con los tramos 5a, 5a-bis, 5a-ter, 5b,
5c, 5d, 5e, 5f, 5f-bis, 5g y 5g-bis cerrados, todos con review limpia.
  public/css/styles.css: 6802 → 5404 líneas
  audit totalViolations: 7161 → 6257, VERDE
  biome sobre styles.css: 4 errores (la `}` huérfana del último tramo), 519 warnings
  guard de modales: no existía → 16/16
  capas internas de styles.css: 6 → 3 (@layer theme, layout, components)

TRAMOS QUE QUEDAN, con rangos medidos sobre las 5404 líneas actuales
  5h  3476–4645  Programa General / Programación Intermedia compartido — 1170 líneas, EL MAYOR.
                 El plan pide partirlo en dos; hazlo midiendo dónde se concentra el color, no por
                 la mitad. Rutas: /programa-general (piloto protegido) y /programacion-intermedia.
  5i  4646–4932  Contratos. Ruta: /contratos.
  5j  4933–5281  PDC, indicadores de desviación, leyendas compartidas. Ruta: /pdc.
  5k  5282–5404  «Fin Layer Components» + UNLAYERED OVERRIDE BRIDGE. VA EL ÚLTIMO y contiene la
                 `}` huérfana. Lee abajo por qué no se puede arreglar sola.
  Task final     Borrar el archivo y retirar sus DOS @import (aia-design-system.css y
                 entrypoints/core.css). Cerrar validation-log.md sin entradas abiertas.

CÓMO EJECUTAR
Subagent-driven + TDD: un subagente por tramo, review de spec+calidad después de cada uno, fixer
aparte para Critical/Important, ledger actualizado tras cada cierre. Directo en main (el usuario lo
consintió explícitamente). Usa scripts/task-brief y scripts/review-package de la skill
superpowers:subagent-driven-development.

ANTES DE ESCRIBIR CADA BRIEF: LEE EL RANGO ENTERO
Cinco premisas mías sobre qué había en un rango resultaron falsas al medirlas, y en un caso obedecer
mi propio brief habría borrado 170 líneas de código vivo que un guard cubría. El patrón siempre fue
el mismo: dar por homogéneo un bloque delimitado por un comentario de sección sin comprobar dónde
acaba de verdad, o citar CSS/JS como si fuera markup. Lee el rango completo y verifica el límite
inferior antes de escribir una sola línea del brief.

OCHO TRAMPAS QUE YA COSTARON DEFECTOS REALES EN ESTE GOAL
1. Sujeto contra ancestro: al quitar un segmento de un selector, comprueba si era el SUJETO de la
   regla. Perderlo cambia qué elemento se pinta y ningún gate lo detecta. Dejó dos páginas blancas.
2. Un test que aserta ausencia no es un guardián. Mide el color computado en el navegador.
3. Censo por CLASE EXACTA, nunca por subcadena: `ps-modal-footer` casa con `ps-modal-footer-between`
   y da falsos vivos. Y busca siempre en public/js, views y src, incluida la concatenación de
   cadenas en JS. Un .js inyectaba en runtime una hoja declarada «muerta».
4. UN BARRIDO EN REPOSO NO LO VE TODO. Es la trampa que más defectos ha destapado. Hay CSS que sólo
   vive con un modal ABIERTO (cerrado mide 0x0), al ENTRAR EN EDICIÓN (un control a 1,02:1 que
   ninguna medición en reposo veía), con la TABLA CARGADA (cuatro chips bajo AA que sólo aparecen
   con filas), con un MENÚ DESPLEGADO, o tras DISPARAR UN BOTÓN (una nota a 2,10:1 en un modal que
   ni siquiera se abre con .modal('show')). Abre el estado antes de medir, y autovalida tu sonda
   antes de fiarte de un cero.
5. Fondo y texto se reasignan JUNTOS y se miden. Es el defecto más repetido de la fase: voltear la
   tinta donde el fondo lo fija otro archivo dejó 19 etiquetas a 1,00:1 en un formulario de
   producción y costó seis commits de otra sesión. Si la regla toca borde, son tres declaraciones.
6. No uses !important ni subas especificidad para ganar precedencia: la capa ya la da.
7. No cambies matices por tu cuenta: es diseño, y el spec de F1 lo prohíbe. Hubo que revertir un
   azul convertido en teal y un borde gris oscurecido «para mejorar la jerarquía».
8. Un borde translúcido no es una frontera. WCAG 1.4.11 pide 3:1 para bordes de control e
   indicadores de estado, no sólo para texto.

MECÁNICA DE CASCADA QUE VAS A NECESITAR (medida, no teórica)
- Cascada canónica DS-006: reset, vendor, theme, base, layout, components, utilities, module,
  legacy-overrides. styles.css se importa en layer(module) y declara internamente
  `@layer theme, layout, components;` → sus reglas resuelven como module.components.
- PARA DECLARACIONES !important EL ORDEN DE CAPAS SE INVIERTE: gana la capa más TEMPRANA. Esto hizo
  fracasar dos movimientos y dejó muertas más de 30 declaraciones que parecían vivas.
- Una hoja con <link> SIN CAPA gana a cualquier @layer. Y ojo: una hoja puede llegar por <link>
  propio y aun así DECLARAR `@layer components` — como los nombres de capa son globales al
  documento, sus reglas caen en la capa top-level `components`, anterior a `module`, y con
  !important ganan a styles.css. Es el caso de programacion-semanal.css y de pdc.css.
- LA CASCADA PUEDE GANAR EN ARCHIVOS DISTINTOS SEGÚN LA RUTA. Comprobado con experimento 2x2: en un
  tramo, arreglar sólo uno de los dos archivos habría dejado el fix invisible en una de tres rutas.
  Mide ruta por ruta, no una vez.
- Si algo tiene que MOVERSE de archivo, el destino que conserva la precedencia de origen es
  `@layer module.components` dentro de adapters/legacy-bridge.css, precedido de
  `@layer module.theme, module.layout, module.components;` — esa línea es LOAD-BEARING: sin ella el
  orden de subcapas se voltea. `legacy-overrides` parece el destino natural y es el equivocado:
  allí las !important quedarían las MÁS DÉBILES de toda la cascada.
- foundation.css lo importan TRES entrypoints (incluido el del laboratorio) y styles.css sólo DOS:
  mover algo allí AMPLÍA su alcance, y --surface-card/--surface-bg/--text-main/--font-main no
  existen fuera de styles.css.

EL AUDIT NO SALTA COMENTARIOS
`hardcoded-color-function` no los salta en absoluto. `hardcoded-hex` sólo omite un hex si `/*` o
`//` cae en los OCHO caracteres previos — no es un parser de comentarios: `/* #abc123 */` se salta,
pero un hex más adentro de un comentario largo SÍ cuenta y rompe el gate. Ha pasado tres veces.
Documenta con nombres de token y ratios, nunca con literales de color.

AL REPORTAR EL AUDIT, SEPARA DOS CIFRAS (decisión del usuario, 2026-07-27)
scripts/design-system-audit.mjs EXIME a todo public/css/design-system/** de siete reglas
(off-scale-spacing, off-scale-typography, off-scale-shadow, raw-token-in-module,
global-module-selector, local-vendor-override, duplicate-canonical-primitive). Mover una declaración
legacy al design system BAJA EL CONTADOR SIN MEJORAR NADA. Di cuánto de tu descenso es borrado real
y cuánto relocalización. `unauthorized-important` y `hardcoded-hex` no están exentas y sí son
honestas.

SÉ BREVE EN LOS COMENTARIOS
Un tramo fue el único que hizo CRECER el archivo (+59 líneas netas, 81 de prosa para 11
declaraciones) y hubo que podarlo. Deja 1–2 líneas por bloque y que el detalle viva en tu reporte.
El objetivo del goal es que este archivo llegue a cero líneas.

ROJOS CONOCIDOS — NO LOS PERSIGAS NI LOS "ARREGLES"
1. contracts.test.mjs «worktree and index must be clean»: lo causan archivos sucios de sesiones
   paralelas. Hoy son src/View/Components/DesignSystemComponent.php,
   tests/browser/design-system-lab-sidebar.mjs y tests/browser/design-system-lab.performance.mjs,
   más lo que aparezca. NUNCA `git add -A`: stagea rutas explícitas.
2. design-system-compliance.mjs «fillsDesktopShell»: rojo DELIBERADO, detectó un defecto real de
   layout y tiene tarea propia. NO relajes el umbral.
3. design-system-body-canvas-dark.mjs, SEGUNDO test: rojo real y conocido en /profesionales y
   /subcontratistas (#hot-container en rgb(255,255,255) blanco puro). Lo cierra un tramo posterior.
   El PRIMER test (body) debe seguir VERDE en 10/10.
4. `npx biome check public/css/styles.css` da 4 errores por la `}` huérfana. Debe seguir en 4.
5. NO ejecutes `npm run test:visual:lab`: sus goldens se rebaselinaron a CI en 6fe152f y en macOS
   fallan por métricas de fuente. Ese gate no es ejecutable en este host.
6. Tres tests de módulo (contratos-handsontable, listado-actividades-handsontable, test-pdc) fallan
   por causas preexistentes: un radio de 14px, un clic a 390x844 (mobile, fuera de alcance) y falta
   de contexto Compose de CI.
PROHIBIDO regenerar baselines o goldens para forzar verde. audit-baseline.json está protegido por
hash y exige archivo de aprobación. El audit está HOY VERDE: cualquier fallo nuevo es tuyo.

GUARDS VIVOS QUE DEBEN SEGUIR VERDES
tests/browser/modales-dark-homologacion.mjs (16/16), programacion-semanal-legend-honesty.mjs (3/3),
pdc-chips-dark.mjs, state-tint-ladder.mjs, ops-state-chip-hue.mjs, shell-sidebar-rollout.mjs
(135/135), y la suite estática (334 pass / 1 fail, el de árbol limpio).
Si añades un caso a un guard: verifícalo EN ROJO contra el CSS anterior, usa markup FIEL al que
emite el JS de producción (no sintético), y NO escribas aserciones que congelen el mecanismo actual
— hubo que retirar una que habría puesto el guard en rojo ante una mejora futura.

CONTRATO DE CADA TRAMO
- Contraste MEDIDO, no razonado, en la ruta del tramo (AA 4.5:1 texto, 3:1 texto grande, 3:1 bordes
  de control por 1.4.11).
- Si el design system ya cubre la regla: borrar, no duplicar. Si está muerta: borrar, no tokenizar.
- No ampliar un selector para facilitar el movimiento.
- /programa-general se verifica en TODOS los tramos: es el piloto protegido por DESIGN.md.

POR QUÉ 5k VA EL ÚLTIMO Y NO SE PUEDE ADELANTAR
Se intentó arreglar la `}` huérfana como higiene aislada y se REVIRTIÓ por decisión del usuario.
Medido: la llave SOBRA (balance del archivo −1, y el desbalance aparece justo ahí), pero borrarla
sola SUBE el audit de 6851 a 6898 y lo pone en rojo por `css-outside-layer 841 > baseline 829`. El
mecanismo: la `}` de más hacía que el parser del audit creyera que ~47 reglas estaban dentro de un
bloque; sin ella ve la verdad, que están fuera de capa — exactamente lo que el propio comentario de
la sección declara en mayúsculas. NO es deuda nueva: es deuda que el error de sintaxis ocultaba.
Por eso 5k debe arreglar la llave Y reubicar las reglas EN LA MISMA TAREA: sólo así el contador baja
en vez de subir, y no hace falta tocar el baseline protegido.
Corrección útil: el parse error NO ocultaba lint. Medido: 539 warnings idénticos antes y después, y
la última línea diagnosticada es la 5690. Lo único que abortaba era el FORMATEO de biome.

DEUDA ABIERTA QUE F1 DEBE CERRAR O ESCALAR
Está toda en validation-log.md. La de mayor alcance: el design system NO declara hoy ningún token de
borde que alcance 3:1 sobre oscuro — `--ds-active-border` rinde 1,86–1,91:1 y así están 221 de 278
controles de la app; `--ds-color-border-strong` 1,21:1 y `--ds-color-border-default` 1,10:1. Cuando
un tramo ha necesitado frontera real ha usado `--ds-active-text-secondary` documentando el desajuste
semántico. Eso NO se puede cerrar dentro de F1: exige un token nuevo en el DS.

ENTORNO
Docker Compose, app en http://localhost:8081, credenciales test.A / aia2026, proyecto "Da Porto".
PHP y PHPStan SIEMPRE dentro del contenedor `app`, nunca del host. Viewport canónico 1180x820, dark
únicamente; el tema linen fue retirado del producto en F0. Sin mobile ni tablet (AGENTS.md).
El navegador integrado PIERDE LA COOKIE de sesión entre turnos: usa Playwright contra el mismo
contenedor para medir autenticado.
Commits locales; push sólo con petición explícita del usuario.

CONCURRENCIA
Hay sesiones paralelas trabajando en el mismo worktree y pusheando a main. Antes de arrancar un
tramo comprueba que styles.css no esté sucio: si otra sesión lo tiene abierto, ESPERA en vez de
editar en conflicto. Si un subagente termina con trabajo hecho pero el árbol está contaminado por
otra sesión, que reporte BLOCKED en vez de commitear arrastrando trabajo ajeno — ya pasó y fue la
decisión correcta.

EMPIEZA confirmando el estado con `git log --oneline -3`, `git status --short`,
`wc -l public/css/styles.css` y `node scripts/design-system-audit.mjs`, y contrástalo con lo de
arriba antes de tocar nada.
