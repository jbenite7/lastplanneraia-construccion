---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-09-06
areas: [design-system]
fuente: goals/bloqueo-tema-claro/goal.md
resumen: "Levantar el bloqueo del tema claro: el guion que fuerza el oscuro y la hoja clara que ningún módulo carga, con el laboratorio rindiendo en claro y sus goldens en CI."
---

<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: bloqueo-tema-claro

## Fase del plan
Plan: docs/superpowers/plans/2026-09-06-bloqueo-tema-claro.md
Fase: única
Sha de arranque: 30794901 (`main` tras el merge del PR #20)
Rama: `fix/bloqueo-tema-claro`, worktree `.claude/worktrees/shell-minimo-react`
Presupuesto: 3 días (2 estimados el 2026-09-06 antes de medir; la medición sumó una causa)

## Objetivo
Que toda pantalla servida por PHP y el laboratorio del design system honren el tema que
`theme-bootstrap.js` decide —claro de entrada (D12), preferencia por aparato (D14)—, y que el
carril visual del CI mida el laboratorio en los dos temas (D16) con goldens claros aprobados.

## Lo que se midió antes de declararlo (2026-09-06, sobre `main` en `30794901`)

> **Al ejecutarlo aparecieron TRES, no dos.** La tercera —el vocabulario de estado leído en
> crudo, sin pasar por `--ds-active-*`— no se veía desde donde se midió, y es la que Felipe
> señaló al rechazar la primera galería de goldens. Está medida y explicada en el `## Cierre`.
> Las dos de abajo se conservan tal como se escribieron: eran correctas, solo incompletas.

Dos causas, no una, y la segunda no estaba en `TASKS.md`:

1. **`public/js/modules/aia_ui/theme.js` fuerza `data-aia-theme="dark"` sin condición** después
   de que `theme-bootstrap.js` ya aplicó el tema elegido. Lo cargan **7 vistas de forma estática**
   (`login`, `password-forgot`, `password-reset`, `project_selector`, `plan-compras/app`,
   `bi/_layout`, `bi/control-tower-piloto`) y **12 más de forma dinámica** vía
   `public/js/linksComunesHead2.js:60` (PG, PI, PS, CNP, CNC, CIC, profesionales, subcontratistas,
   control de cambios, indicadores, escalamientos, PG-actualizar). Las 19 emiten el bootstrap
   por `DesignSystemHeadComponent`, así que al retirar el forzado ninguna queda sin tema.
   Reproducido en `/programa-general` con `localStorage.aia-theme = "light"` y recarga:
   `{"attr":"dark","clases":"aia-theme-dark","stored":"light","bg":"rgb(17, 26, 21)"}`.
2. **Las re-vinculaciones claras viven solo en `public/css/design-system/theme-claro.css`, y
   ningún entrypoint la importa.** `entrypoints/theme-overrides.css`, `aia-design-system.css` y
   `design-system/laboratory-foundation.css` atan los tokens oscuros a `:root`. Solo el shell
   React (`public/app/index.html:19`) y la Torre piloto (`views/bi/control-tower-piloto.php:37`)
   la enlazan a mano. Reproducido: con el atributo forzado a `light` en `/programa-general`,
   `--ds-active-bg-page` sigue en `#111a15`; al inyectar la hoja clara, pasa a `#ffffff`, texto
   `#18181b`, `color-scheme: light`.

Consumidores del global `window.AiaDesignSystem.getTheme()` que `theme.js` publica: **ninguno en
código de producto** (`grep` en `public/js`, `pdc-app/src`, `ct-app/src`, `frontend/src`); solo
`tests/browser/shell-sidebar-rollout.mjs:110` lo espera, y `design_system_lab.js:8` define el suyo.

## Condición de hecho
1. En las 19 pantallas PHP y en `/internal/design-system`, sin preferencia guardada el
   documento arranca con `data-aia-theme="light"` y fondo claro; con `aia-theme = "dark"` guardado,
   oscuro. Medido en navegador contra el árbol de la rama (no el de la raíz).
2. `node --test tests/design-system/theme-default.test.mjs tests/design-system/theme-default-claro.test.mjs tests/design-system/theme-claro-tokens.test.mjs tests/design-system/linen-removal.test.mjs tests/design-system/dead-theme-removal.test.mjs tests/design-system/visual-ci-contract.test.mjs` y `node tests/test_foundation_shell_contract.mjs` en `RC=0`.
3. `docs/design-system/manifests/laboratory.json` declara 20 escenarios `light` con golden y
   `sha256`, aprobados por Felipe sobre la galería; `visual-ci-contract.test.mjs` exige
   `light: 10`.
4. El CI del PR en verde: `design-system-static`, `design-system-runtime (light)` y
   `design-system-runtime (dark)`.
5. El frente entra a `main` por Pull Request. Producción fuera de alcance.

## Posture

> **Ampliada el 2026-09-07 por decisión de Felipe** («quiero que el color se resuelva ahora,
> dentro de este mismo frente»), tras rechazar la primera galería porque el sidebar y el botón
> crítico seguían oscuros. La primera regla de abajo queda **superada en parte**: el frente sí
> tocó cuatro componentes compartidos —etiqueta, aviso, botón crítico y marca de severidad—
> aunque **sin migrar ningún módulo**, porque el arreglo re-vincula los tokens en la hoja clara
> en vez de recablear a sus 302 consumidores. El resto de la Posture se cumplió entera: ningún
> golden oscuro se regeneró, ningún assert se aflojó, y `admin/`, `ct-app/` y el contenedor
> compartido quedaron intactos.

- No migrar ningún módulo al claro: este frente **destraba**; el primer módulo (Programa General,
  D23) tiene su propio plan.
- No regenerar goldens oscuros. Si un golden oscuro cambia, es hallazgo, no ajuste.
- Los goldens claros nuevos solo se commitean con el visto de Felipe sobre la galería.
- No tocar `admin/` (D24 tiene su vía) ni `ct-app/src/lib/theme.ts` (clave propia
  `ct-piloto-theme`; se anota como pendiente para el cierre de la Torre, D23).
- No reapuntar `LPS_CODE_ROOT` del contenedor compartido: la verificación en navegador corre en
  la pila aislada de CI (`docker-compose.ci.yml`, puerto 18081).
- Los tests que daban el oscuro por sentado se cambian **con intención declarada** (materializan
  el tema que afirman); ningún assert se afloja para ponerlo verde.

## Leer primero
- `docs/superpowers/specs/2026-08-28-temas-claro-oscuro-end-to-end-design.md` (D12, D14, D16, D18, D19)
- `TASKS.md` §Bloqueantes, entradas del 2026-08-28 sobre `theme.js` y el laboratorio
- `tests/design-system/visual-ci-contract.test.mjs` (el censo por módulo y tema)
- `docs/coordinacion-sesiones.md` reglas 4, 5 y 7 (contenedor compartido, efímero, paso 0)

## Archivos de este goal
- [[goals/bloqueo-tema-claro/goal]] — este archivo
- [[docs/superpowers/plans/2026-09-06-bloqueo-tema-claro]] — el plan
- [[memoria/goals/estado]] — estado de todos los goals

## Cierre

**Ejecutado el 2026-09-07**, rama `fix/bloqueo-tema-claro`, worktree
`.claude/worktrees/shell-minimo-react`. Integrado `origin/main` (`21de7804`) al arrancar y
re-verificado sobre el árbol integrado.

### La medición del 2026-09-06 daba dos causas. Eran tres.

Las dos declaradas eran reales y están arregladas. La tercera apareció al mirar el laboratorio
ya rindiendo en claro, y es la que Felipe señaló al rechazar la primera galería de goldens:

3. **El vocabulario de estado se lee en crudo.** `--ds-color-state-{nivel}-{bg,text}` lo
   consumen 302 puntos en 22 hojas **sin pasar por `--ds-active-*`**, así que ninguna hoja de
   tema podía alcanzarlo: en claro salía con el tinte calibrado para fondo oscuro. Sus gemelos
   `-light` se habían retirado el 2026-08-28 (D17) por no tener consumidor.

   **Por qué no se vio antes:** la medición preguntó quién *pisaba* el tema (`theme.js`) y
   quién *no cargaba* la hoja clara (los entrypoints). Un componente que escribe su color en
   crudo no aparece en ninguna de las dos búsquedas. El mismo punto ciego va a reaparecer en
   cada módulo que se migre al claro.

   **Arreglo:** re-vincular los tokens en la propia hoja clara, no recablear a los 302
   consumidores —eso sería migrar módulos, que la Posture prohíbe—. Funciona porque
   `tokens.css` también vive en `@layer theme` y ahí manda la especificidad:
   `:root:not(...):not(...)` (0,3,0) gana a `:root` (0,1,0).

### Decisiones de Felipe tomadas durante la ejecución

- **Dirección B (tinte suave + tinta oscura)** para los cuatro niveles de estado, elegida sobre
  una comparación pintada en blanco frente a la dirección sólida. Contrastes texto/fondo:
  éxito 9,78:1 · advertencia 6,20:1 · crítico 6,77:1 · información 8,42:1.
- **Sidebar en verde de marca** en tema claro. **Corrección de lo que esta sesión afirmó
  primero:** no deroga la entrada 23 del piloto — eso ya lo hizo **D9** de la spec de temas el
  2026-08-28 («Nav y sidebar cambian con el tema», revirtiendo el patrón Linear/Stripe). Lo que
  hace este frente es **afinar D9 en el valor**: su dirección se cumple entera (la nav cambia con
  el tema, `--ds-active-nav-*` deja de apuntar a `-dark`, la nav gana goldens dobles), pero D9
  pedía nav *clara* y Felipe eligió el verde de marca al verla renderizada el 2026-09-07. Sin
  color nuevo: `--ds-nav-bg` ya existía como la otra variante del mismo token. El guard no se
  borró: se reescribió para afirmar la decisión nueva, y sigue protegiendo lo mismo (anclas
  fijas, nunca `--ds-active-*`).
- **Resolver el color dentro de este frente**, ampliando su alcance frente a la Posture
  original («no migrar ningún módulo al claro»). Queda escrito aquí para que el cambio de
  alcance sea auditable.

### Desvíos del plan, con su porqué medido

- El import de la hoja clara va a `entrypoints/core.css`, no a `theme-overrides.css` como decía
  el paso 5 de la tarea 2: el gate de partición exige que el texto de `theme-overrides.css` sea
  idéntico al bloque inline del agregador, y un `@import` ahí lo rompía.
- El presupuesto de peticiones del laboratorio sube de 18 a 19 (`laboratory-hardening`): entra
  la hoja clara. El tope sigue siendo tope.
- Dos specs de `design-system-lab.mjs` que el plan no había censado afirmaban valores oscuros
  congelados y sí los corre el CI. Materializan el oscuro en vez de heredarlo.
- **18 escenarios claros, no 20.** `states-feedback` sale del spec visual antes de llegar a
  `toHaveScreenshot`, así que no tiene captura clara que aprobar. Su golden oscuro mide
  1102×1649 frente a los 1180×820 del resto: peso muerto de otra época de captura. **No se
  regeneró** — un golden oscuro que cambia es hallazgo, no ajuste (D18).

### Condición de hecho

1. **Cumplida.** Medido en navegador contra la pila aislada, con la imagen construida en el sha
   exacto del árbol (`docker inspect` → `aia.ci.git-sha`, sin mounts). Sin preferencia guardada:
   `/proyectos`, `/programa-general`, `/plan-compras`, `/programacion-semanal`, `/login` y
   `/internal/design-system` arrancan en `data-aia-theme="light"` con `--ds-active-bg-page`
   `#ffffff` y `color-scheme: light`. Con `aia-theme = "dark"` guardado, `/programa-general`
   vuelve a `dark` con `#111a15`. Antes del frente esa misma ruta daba
   `{"attr":"dark","stored":"light","bg":"rgb(17, 26, 21)"}`.
2. **Cumplida.** `npm run test:design-system:static` en `RC=0` (8/8 gates), que incluye los seis
   archivos de la condición y `test_foundation_shell_contract.mjs`.
3. **Cumplida con enmienda.** `laboratory.json` declara **18** escenarios `light` con golden y
   `sha256`, aprobados por Felipe sobre la segunda galería; `visual-ci-contract.test.mjs` exige
   `light: 9`. La enmienda de 20 a 18 es la de `states-feedback`, arriba.
4. **Medido en local; falta el CI del PR.** Docker Desktop se puso en solo lectura a mitad de
   sesión (`read-only file system` al recrear contenedores) y se llevó la base por delante;
   reiniciado con autorización de Felipe, la pila se reconstruyó sobre `5ad97319` y el paso 0
   dio `MOUNTS=[]` con `aia.ci.git-sha` igual al árbol. Sobre esa pila:
   - `design-system-lab.a11y.mjs` — `RC=0` en **claro** y en **oscuro** (1 passed cada uno).
   - `design-system-lab.visual.mjs` — `RC=0`: **18 passed** en claro, **20 passed** en oscuro.
   - Carril de runtime del CI (`lab` + `body-canvas-dark` + `unlayered-delivery` +
     `table-contract.runtime`) — `RC=0`: **31 passed** en cada tema.
   - `git status --porcelain tests/browser/__screenshots__ | grep -- '-dark-'` vacío tras cada
     pase: **ningún golden oscuro se movió** (D18).
   - `npm run test:design-system:static` `RC=0` (8/8) y los seis archivos de la condición 2 más
     `test_foundation_shell_contract.mjs` en `RC=0` (98/98).

   Queda por confirmar el CI del PR sobre los tres jobs. **Al leerlo, el veredicto está en las
   variables `G_*` del paso «Summarize gate results», no en el color del job:** los pasos llevan
   `continue-on-error` y muestran «✓» aunque su gate falle.
5. Entra a `main` por Pull Request. Producción fuera de alcance.
