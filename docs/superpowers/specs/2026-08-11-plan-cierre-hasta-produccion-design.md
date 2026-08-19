---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-11
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-11-plan-cierre-hasta-produccion-design.md
resumen: opción (a). Cada entrada de moduleMappings declara dónde se pintan sus estados (ruta, vista o selector), obligatorio, y el gate estático lo hace cumplir. - Por…
---

# Plan de cierre hasta producción — diseño

- **Fecha:** 2026-08-11
- **Quién lo escribe:** sesión coordinadora de `lps-aia`.
- **Estado medido:** `2e73013b`, árbol limpio, sin divergencia con `origin/main`.
- **Decidido por el usuario** en la tanda de grilleo de esta sesión (2026-08-11): alcance hasta
  producción, `runtime-budgets` verde solo desde CI real, despliegue antes que el frente de forma,
  `D-CEF-1` opción (a), `D-BTN-1` opción (2).

## Qué cierra este plan

Lleva el repositorio desde su estado de hoy —seis de ocho gates del design system en verde y dos
decisiones del usuario abiertas— hasta tener **los ocho gates verdes, la cola de decisiones vacía y
el trabajo publicado en producción**.

**Qué deja fuera, dicho antes que nada:** el frente de forma decidido en `D-F1-6` (las páginas de
error dentro del shell, unificar los vocabularios de estado de la cascada, y la regla de que ese
frente no cierra sin haber quitado algo) **va después del despliegue**, con su propio spec y su
propio plan. Este documento no lo cubre y no debe leerse como si lo cubriera.

## Estado de partida, medido

Recibos de `docs/design-system/closeout-evidence.json` sobre `2e73013b`:

| Gate | Estado | Recibo |
|---|---|---|
| `static` | `passed` | 2026-08-11T17:27:34Z |
| `runtime` | `passed` | 2026-08-11T23:45:04Z |
| `phpstan-scoped` | `passed` | 2026-08-11T17:08:41Z |
| `phpstan-global` | `passed` | 2026-08-11T17:09:54Z |
| `global-table-safety` | `passed` | 2026-08-11T17:08:39Z |
| `atomic-commit` | `passed` | 2026-08-11T17:08:39Z |
| `runtime-budgets` | **`blocked`** | sin recibo |
| `full-app-flow` | **`blocked`** | sin recibo |

Decisiones abiertas en `docs/decisiones-pendientes.md` sobre ese mismo sha: **dos**, `D-CEF-1` y
`D-BTN-1`. Las demás están resueltas; no quedó ninguna ficha marcada `abierta` con la decisión ya
tomada.

Sesiones vivas sobre este repositorio: **una**, la coordinadora. Ninguna otra sesión tiene su `cwd`
en `/Volumes/Crucial X6/Developer/lps-aia` — verificado con `list_sessions` y contra
`.claude/sesiones.md`, que lista una sola fila. El mapa de contención que el hook de arranque anunció
—dos sesiones, una con frente abierto— **no se sostuvo al medirlo**, y es un caso más del patrón
recogido en `memoria/trampas/`: un instrumento que ante «no hay dato» devuelve algo verosímil.

## Las cuatro fases

Cada fase es **un frente**, con su worktree propio, su ciclo de visto y el gate de nueve pasos de
`AGENTS.md` §Publicación. **Ninguna empieza hasta que la anterior está publicada y anotada** — el
paso 9, no el 8.

### F-AB · Cablear los dos gates bloqueados al CI que ya existe

> **Corrección del 2026-08-11, antes de escribir el plan.** Este spec se commiteó en `ef6d55dd` con
> **dos** fases aquí —«montar una base aislada» y «hacer correr `runtime-budgets`»— y las declaraba
> disjuntas por tocar ficheros distintos. **Las dos afirmaciones eran falsas**, y se cayeron al medir
> el árbol para escribir los pasos. El commit `ef6d55dd` queda inexacto en este punto; se corrige
> aquí, que es donde se lee. Fundidas en una sola fase por decisión del usuario.

- **Qué se midió** (sobre el árbol de `ef6d55dd`):
  - `.github/workflows/design-system.yml:85-94` **ya levanta un entorno aislado**: `docker-compose.ci.yml`,
    un `COMPOSE_PROJECT_NAME` propio por corrida, puerto 18081, y exporta `E2E_REQUIRE_ISOLATED_DB=1`
    y `E2E_ALLOW_DB_MUTATION=design-system-ci` — las dos variables exactas que
    `tests/browser/support/restoration.mjs:14-19` exige para dejar correr `full-app-flow`.
  - El mismo workflow (`:60-83`) **ya calcula las cuatro variables de procedencia** que
    `runtime-budgets` pide, con `scripts/design-system-ci-preflight.mjs --print-provenance`.
  - Y aun así: `grep -n "full-app-flow" .github/workflows/design-system.yml` → **cero**.
    `grep -n "budget"` en el mismo archivo → **cero**. Los scripts existen (`package.json:24-25`);
    nadie los llama desde CI.
- **Por qué es una fase y no dos:** los dos gates comparten causa —están sin enchufar al job que ya
  tiene lo que necesitan— y comparten fichero. Dos frentes en fila sobre el mismo archivo gastan dos
  ciclos de visto y dos corridas de CI para nada.
- **No hay ninguna base de datos que montar.** Esa era la premisa caducada.
- **Prohibido, y esto es lo que más importa de la fase:** fabricar `CI_RUN_ID`, `CI_GIT_SHA`,
  `CI_WORKTREE_FINGERPRINT` o `CI_FIXTURE_SHA256` en local para ver verde. Sería inventar una
  procedencia de CI, que es exactamente el fraude que el gate existe para impedir. El motivo está en
  `docs/design-system/gates-cierre-frente-1b.md`.
- **Condición de hecho:** una corrida real de GitHub Actions sobre un sha de `main` ejecuta los dos
  gates y los dos pasan; sus recibos en `closeout-evidence.json` pasan a `passed` con la procedencia
  de esa corrida. Ocho de ocho.
- **Mutación exigida, una por gate:** para `full-app-flow`, retirar uno de los tres ejes que el gate
  fundió (roles, persistencia, restauración) tiene que ponerlo rojo. Para `runtime-budgets`, una
  muestra fuera de presupuesto tiene que ponerlo rojo. Las dos, restauradas después.
- **Comprobación previa, antes de asignar la fase:** que el workflow corra verde hoy tal como está.
  **No está medido** — desde aquí no se puede disparar Actions. Si resulta que el workflow ya falla
  por otra causa, la fase cambia de tamaño y hay que volver a hablarlo con el usuario.
- **Archivos previstos:** `.github/workflows/design-system.yml` y, si hiciera falta,
  `package.json`. Nada de `src/`.
- **Riesgo aceptado:** `full-app-flow` funde tres dimensiones en una (`D-F1b-2`), así que un rojo no
  dice cuál falló. Está documentado y no se reabre aquí.

### F-C · `D-CEF-1` — superficie obligatoria en el contrato de estados

- **Decisión del usuario:** opción **(a)**. Cada entrada de `moduleMappings` declara **dónde se
  pintan** sus estados (ruta, vista o selector), obligatorio, y el gate estático lo hace cumplir.
- **Por qué:** `programa-general-actualizar` declaraba seis estados que ninguna pantalla pinta, y
  estuvo en verde desde el 2026-07-15. No caducaron: se inventaron. Sin superficie declarada no hay
  nada contra lo que contrastar, así que ningún gate podía notarlo. El contrato vigilaba la resta y
  no la suma.
- **Condición de hecho:** los **trece** módulos declaran su superficie, el esquema la exige
  (`state-semantics.schema.json` es `additionalProperties: false`, así que el campo hay que añadirlo
  explícitamente) y la suite estática pasa con el censo completo.
- **Mutación exigida:** añadir un módulo con estados y sin superficie real tiene que poner el gate
  rojo. **En la aserción de censo, la mutación útil no es cambiar el número: es cambiar qué se
  cuenta.**
- **Lo caro de esta fase, dicho antes:** para varios de los trece la superficie **no es un dato que
  ya exista escrito** — hay que ir al código a buscarlo. Es trabajo de censo, no de esquema.
- **Archivos previstos:** `docs/design-system/state-semantics.json`,
  `docs/design-system/state-semantics.schema.json`, `tests/design-system/states-feedback.test.mjs`.
- **Leer junto a:** `D-VOC-3`, cuya premisa se cayó al medir — `Bloqueado` no se ve en ninguna parte.

### F-D · `D-BTN-1` — la resta del `!important`

- **Decisión del usuario:** opción **(2)**. Se investiga quién gana de verdad y **se retiran los dos**
  —la declaración que no gana y la regla que la pisa— si esa otra regla existe solo para vencer a
  esta.
- **Qué está medido:** `public/css/buttons.css:970` declara `display: inline-flex !important` para
  `.pdc-legend-item`, y el valor **computado** en Programa General, Intermedia y Semanal es `flex`.
  Ese `!important` no le gana a nadie.
- **Condición de hecho:** identificado quién gana, con valor computado medido en las tres pantallas a
  1180×820 dark; retirados los dos si procede; las tres pantallas siguen renderizando igual, medido
  antes y después. Si al medir resulta que la otra regla sí hace falta por sí misma, **se retira solo
  la declaración muerta y se escribe por qué** — eso no es incumplir la decisión, es lo que la
  medición permita.
- **Riesgo declarado:** entra en la cascada de capas (`memoria/trampas/css-layer-cascade.md`), donde
  para `!important` el orden de capas **se invierte**. El frente que lo levantó excluyó esa zona a
  propósito. Se mide, no se supone.
- **Archivos previstos:** `public/css/buttons.css` y la hoja de quien lo pise.

### F-E · Despliegue a producción

- **Autorización:** **explícita, propia y en el momento.** Que esta fase esté escrita aquí **no la
  concede**, y ni este spec ni el plan que salga de él cuentan como tal. `AGENTS.md` §Publicación,
  siempre.
- **Procedimiento:** `docs/siteground-deploy-routine.md` — pruebas antes que producción, respaldo
  previo verificable, `pull --ff-only`, Composer ejecutado con PHP 8.3, smoke funcional del flujo
  afectado.
- **Contexto:** ~1.255 commits de retraso. Es la única fase no reversible del plan, y por eso lleva
  respaldo y plan de restauración **antes** de empezar.
- **Lo que la autorización no cubre:** limpiar drift del servidor ni desplegar otros cambios. Una
  publicación aprobada aprueba esa publicación.

## Cómo se ejecuta

**Reparto.** La coordinadora no implementa: abre un frente por fase y da el visto sobre un sha antes
de cada publicación, escribiéndolo **dentro** de `.claude/vistos/<frente>`. Si el sha cambia después,
el visto se reemite. Cada fase necesita que el usuario abra una sesión de ejecución; hoy no hay
ninguna viva sobre este repo.

**Contención.** Con una fase a la vez y los ficheros previstos arriba, las tres primeras son
disjuntas entre sí: F-AB toca el workflow, F-C el contrato de estados y su test, F-D el CSS de
botones. La única expuesta a trabajo ajeno es F-E, que por eso integra inmediatamente antes de
publicar.

**Verificación, igual en las tres primeras:**

- Condición de hecho con **salida real de comandos** de esa sesión.
- **La mutación en rojo, ejecutada.** No basta con que el gate pase: hay que ver que sabe fallar, y
  que cae la aserción que se esperaba. Si cae otra, la que se esperaba no servía.
- **Re-verificar después de integrar, no antes.** Traer trabajo ajeno puede romper un verde propio
  sin tocar el diff de uno.
- El `push` en **comando aparte**, nunca encadenado a la verificación con `&&` ni detrás de un
  `echo`: un gate solo gobierna si puede impedir la publicación.
- Nadie fabrica evidencia para poner algo en verde: ni goldens, ni recibos, ni variables de CI, ni
  `ignore-file` sobre hallazgos ciertos.

**Dos candados de proceso, escritos como candado y no como buena intención:**

1. Ninguna fase cierra por «funciona». Cierra en el **paso 9**, anotada. `[PUBLICADA]` no es
   `[CERRADA]`.
2. **Antes de abrir cada fase se comprueba que sigue haciendo falta**, comparando la fecha del recibo
   con la del último commit del área. Un rojo puede serlo porque algo está roto o porque nadie lo
   volvió a medir tras arreglarlo, y las dos cosas se ven igual desde fuera. La coordinadora anterior
   falló en esto dos veces el mismo día, y las dos las paró quien recibió el encargo.

## Condición de hecho del plan entero

1. `closeout-evidence.json` declara los **ocho** gates `passed`, cada uno con recibo y procedencia
   real.
2. `docs/decisiones-pendientes.md` no tiene ninguna entrada `abierta`.
3. El trabajo está en producción, desplegado por la rutina de SiteGround, con smoke del flujo
   afectado y respaldo previo verificable.
4. Las cuatro fases tienen su `## Cierre` anotado.

## Archivos de este goal

Este spec vive en `docs/superpowers/specs/`. El plan de implementación que salga de él se enlazará
aquí al escribirse.
