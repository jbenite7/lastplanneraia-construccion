---
capa: fuente
tipo: spec
estado: cerrado
id: CI-CARRILES
fecha: 2026-09-28
superficie: ci
rutas: [".github/workflows/ci.yml"]
depende_de: []
version: 1.2
areas: [proceso, design-system]
fuente: "petición de Felipe del 2026-09-28 («que en cada corrida evalúe lo que de verdad se está editando, no todo»), medición de las últimas 100 corridas de ci.yml y de la corrida 36507477142, decisión D1 de Felipe del mismo día, y tres inventarios de `buscador` (gates por rutas, tests fuera de src, pantallas por carpeta) con las afirmaciones críticas recomprobadas por la sesión principal"
resumen: "El CI corre todo en cada PR (~15 min) aunque el cambio toque una sola zona. Se propone dividirlo en carriles por rutas con `if:` por paso: el PR corre solo lo que su diff alcanza (y todo lo que ningún carril reclame), el push a main corre la suite completa sin cancelarse, y los gates que no dependen del tema corren una vez."
---

# CI por carriles — evaluar lo que se edita

**Estado: cerrada el 2026-09-29.** Se implementó y se mergeó (PR #82, `981ae3d2`); la medición real está
en la decisión de la wiki y en el `## Cierre` del plan. Historia: propuesta 1.2. La 1.1 la aprobó Felipe en el chat el 2026-09-28 (con A2 y A3 como se
recomendaban); la 1.2 recoge dos decisiones suyas del mismo día (D6 y D7), tomadas tras la revisión final
de la rama. El sello formal `/aprobar` es de Felipe y no lo escribe ningún agente. La aprobación de la
spec no autoriza implementación; eso es el visto del plan (paso 04).
Cambios de 1.1 a 1.2: el gate del laboratorio también vigila pantallas de producto (fondo oscuro de siete
rutas y CSS sin capa en 25, medido el 2026-09-29), así que se enciende con CSS, vistas y bundle (D6); `TASKS.md` pasa a `docs` y
`tests/**` se parte en `tests-ds` y `tests-php` (D7); el selector no puede afirmar «el laboratorio es una
vista PHP» como razón para omitirlo; y se corrige V4: una pata de matriz que no se crea no cuenta como
verde para un check obligatorio.
Cambios de 1.0 a 1.1: cerrados V1, V2 y V6 con evidencia; carriles rehechos (el carril `docs` tiene dos
excepciones reales, y el bundle React y el laboratorio del design system quedan en carriles distintos);
el selector será nuevo, no el enrutador existente; matriz gate × carril explícita.

## 1. Trabajo y audiencia

Quien abre un PR en `lps-aia` (Felipe, las sesiones de Claude y Codex) y espera el veredicto del CI para
mergear. Hoy un PR que cambia una línea de `docs/` espera lo mismo que uno que cambia el núcleo PHP.
Lo que necesitan: **que el CI corra los gates que su diff puede haber roto, y que diga sin ambigüedad
cuáles no corrió y por qué.**

## 2. Resultado y prueba

Cada criterio se comprueba con salida real, no con lectura del YAML.

| # | Criterio | Cómo se prueba |
|---|---|---|
| R1 | En `pull_request`, cada gate corre solo si el diff toca un carril que lo dispara. | Tabla de casos ruta → carriles en un test `node --test` del selector. |
| R2 | **Ruta sin dueño → todo corre.** Ninguna carpeta nueva queda sin CI por olvido. | Caso con una ruta inventada; debe activar todos los carriles. |
| R3 | En `push` a `main` corren todos los carriles, y una corrida de `main` **nunca se cancela** por otra posterior. | Corrida real de `main` tras el merge; `cancel-in-progress` solo para `pull_request`. |
| R4 | Los gates que no dependen del tema corren una sola vez, no en las dos patas de la matriz. | Corrida de PR con carril `php`: la pata `dark` no ejecuta esos pasos. |
| R5 | Un gate omitido se ve como **omitido, con su carril**, en «Resultado de los gates». Un omitido no se lee como verde. | Resumen de una corrida con carriles apagados. |
| R6 | Siguen en verde los tests de contrato que leen `ci.yml`: `npm run test:design-system:static` y `tests/test_runtime_boundary_ci_contract.php`. | Salida real de ambos, con su código de salida leído aparte. |
| R7 | Ahorro medido, no estimado: PR solo de documentación, PR solo de `frontend/src`, PR solo de un CSS de módulo. | Duración de tres corridas reales frente a la línea base de ~15 min. **Meta numérica: pendiente de medir** (V5). |
| R8 | Un gate omitido no deja pasos de subida de recibos fallando. | Corrida con `full-app-flow` omitido: los tres `upload-artifact` con `if-no-files-found: error` (`ci.yml:333,375,422`) llevan la misma condición que su gate. |

## 3. Diseño propuesto

**Un job `cambios`** al inicio calcula los carriles con `git diff --name-only` contra la base (PR:
`origin/<base>...HEAD`; push: la revisión anterior), con un script propio, `scripts/ci-carriles.mjs`.
Motivos: es testeable con `node --test`, no añade una acción de terceros que fijar por SHA, y el
enrutador existente `scripts/design-system-router.mjs` **no sirve**: solo conoce 2 de 13 gates, devuelve
lista vacía para `src/**` (un cambio de PHP quedaría sin gate) y solo advierte ante lo desconocido en vez
de fallar hacia lo seguro. De él se reutiliza únicamente `CORE_PATHS`.

**Restricción dura:** los tests de contrato exigen que `runtime-provenance`, `runtime-grants`,
`php-suite`, `php-admin-db`, `blocking-runtime` y el resto vivan **dentro del mismo job**
`design-system-runtime`. Los carriles se aplican con `if:` **por paso**, no partiendo el job. Añadir
`if:` a un paso no rompe ningún contrato, salvo en los pasos que ya llevan uno. No se cambian sangrías,
nombres ni `id` citados por los tests, y no aparecen las palabras `deploy`, `production` ni
`pull_request_target` en el archivo (ni en comentarios).

### Carriles (propuesta; fronteras a afinar en el plan)

| Carril | Rutas que lo activan |
|---|---|
| `docs` | `docs/**` salvo `docs/design-system/**` y `docs/security/**`; `goals/**` salvo `goals/design-system-nucleo-gobernanza/**`; `memoria/**`; `decisiones/**`; `.obsidian/**`; **`TASKS.md`** (D7: ningún gate lo lee) |
| `php` | `src/**` (salvo `src/View/Components/**` y los archivos del design system de más abajo), `admin/**`, `database/**`, `composer.*`, `phpstan*.neon`, `phpunit.xml`, **`public/index.php`** (el front controller), **`docs/security/**`** y **`.superpowers/**`** (ver hechos: un test PHP lee esos archivos) |
| `ds-core` | `public/css/tokens.css`, `public/css/aia-design-system.css`, `public/css/design-system/**`, las hojas que el núcleo o el shell React cargan (`public/css/styles.css`, `buttons.css`, `access.css`, `handsontable-module.css`, `handsontable-header-global.css`, `auth-react.css`, `project-selector-react.css`), `public/js/modules/aia_ui/**`, `src/View/Components/**`, `src/Controllers/Core/DesignSystemAssetController.php` (sirve el CSS del núcleo y del laboratorio), `docs/design-system/**` |
| `ds-lab` | `views/design-system/**`, `src/Controllers/Internal/DesignSystemLabController.php`, `src/Security/DesignSystemLabAccessPolicy.php`. Mismos gates que `ds-modulo` (con D6 el laboratorio ya corre en ambos); se mantiene como carril propio por legibilidad. |
| `ds-modulo` | el resto de `public/css/**`, `public/js/**` y `views/**` |
| `tests-ds` | `tests/design-system/**` (solo `static`: es donde corren esos tests) |
| `tests-php` | `tests/test_*.php` y `tests/unit/**` (`static`, que corre el nivel `puro`, y PHP `http` + `admin-db` para el nivel `db`/`http`) |
| `front-src` | `frontend/**` |
| `front-bundle` | `public/app/**` (el bundle React versionado que sirven `/login`, `/proyectos` y `/programa-general`) |
| `apps` | `pdc-app/**`, `ct-app/**`, `views/plan-compras/**`, `public/pdc-app/**` |
| `todo` | `.github/**`, `package*.json`, `docker*`, `docker/**`, `scripts/**`, `tests/**` **salvo** `tests/design-system/**`, `tests/test_*.php` y `tests/unit/**` (es decir `tests/browser/**`, `tests/fixtures/**`, `tests/scripts/**` y demás siguen corriendo todo), `e2e/**`, `playwright.config.mjs`, `biome.json`, y **cualquier ruta que ningún otro carril reclame** |

### Matriz gate × carril (✔ = corre)

`todo` y `push` a `main` disparan todas las filas. Las celdas marcadas † son conservadoras por
falta de prueba en contrario: ante la duda, corre; el plan las afina con datos (R7).
La fila «build de imagen + PHP `puro`» corre siempre que corre `static` (corregida en el paso 03):
`foundation.test.mjs`, dentro de `static`, ejecuta PHP con docker compose, y sin la imagen precargada
compose la construiría sin la caché de GitHub. Que `front-src` la omita es una mejora futura, no de esta spec.

| Gate | php | ds-core | ds-modulo | front-src | front-bundle | apps | docs |
|---|---|---|---|---|---|---|---|
| `static`: sub-suites, contrato de PG, secreto en bundle | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | — |
| typecheck + vitest del frontend | — | — | — | ✔ | ✔ | — | — |
| build de imagen + PHP `puro` (job static) | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | — |
| PHP `http` + `admin-db` + PHPStan baseline + grants | ✔ | ✔ † | ✔ † | — | ✔ † | — | — |
| PHPStan del PDC | ✔ | — | — | — | — | ✔ | — |
| `css:minify:check` | — | ✔ | ✔ | — | — | — | — |
| e2e funcionales: `full-app-flow`, `semanal-roles-phases`, `pg-interactions` | ✔ | ✔ | ✔ † | — | ✔ | — | — |
| laboratorio (`test:design-system:runtime`) + teclado/reflow, **dos temas** | — (†† ver D6) | ✔ | ✔ (D6) | — | ✔ (D6) | — (†† ver D6) | — |
| piloto de Programa General + presupuesto de runtime, **dos temas** | — | ✔ | — | — | ✔ | — | — |

Los carriles `tests-ds` y `tests-php` (D7) solo disparan lo que su nombre indica: `tests-ds` → `static`;
`tests-php` → `static` (que corre PHP `puro`) más PHP `http` + `admin-db` + PHPStan baseline + grants.
`ds-lab` tiene las mismas banderas que `ds-modulo`.
†† D6: el laboratorio **no** se enciende con un PR de solo `src/**` (carril `php`) ni de solo `pdc-app/ct-app`
(carril `apps`). Es una brecha aceptada por Felipe: la cubre la corrida completa de `main` (D1), y queda
anotada como riesgo en §7.

Además: `concurrency` cancela solo en `pull_request`; la pata `dark` ejecuta únicamente los gates que
leen `E2E_THEME` (laboratorio visual, piloto visual, teclado/reflow); `Summarize gate results` imprime
`omitido (carril X no tocado)` en vez de dejar `skipped` mudo; el job `cambios` hace `fetch-depth: 0`, valida
que su salida traiga todas las claves **antes** de volcarla (si el script no imprimiera nada, el job falla
en rojo y no apaga todo en verde) y escribe su propia tabla en el resumen de la corrida, para que un PR sin
job de runtime también diga qué omitió (R5).

## 4. Hechos y vacíos

**Hechos (fuente directa; los marcados ✓ los recomprobó la sesión principal leyendo el archivo):**

- Duración de una corrida verde ≈ 15 min: `design-system-static` ≈ 3 min y cada pata de `design-system-runtime` ≈ 12–13 min (corrida 36507477142). Pasos pesados por pata: `full-app-flow` ≈ 200 s, `Run laboratory gates` ≈ 225 s, `semanal-roles-phases` ≈ 70 s.
- En las últimas 100 corridas: 6 `push` y 6 `pull_request` canceladas. En las últimas 25, cinco merges a `main` quedaron sin veredicto (0, 1, 5, 7 y 11 min antes de cancelarse), por `concurrency: cancel-in-progress: true` agrupado por `github.ref`.
- Solo 6 archivos leen `E2E_THEME`. `full-app-flow`, `semanal-roles-phases`, `pg-interactions`, las suites PHP, PHPStan y los grants no dependen del tema y hoy corren dos veces.
- La suite `static` lee `docs/design-system/**`, `public/css`, `public/js`, `views`, `src`, `admin`, `pdc-app/src`, `ct-app/src`, `frontend/src`, `goals/design-system-nucleo-gobernanza/`, `DESIGN.md`, `GEMINI.md` y `README.md`. Ningún barrido recorre la raíz del repo, y ninguno mira `docs/**`, `goals/**`, `memoria/**` ni `decisiones/**` fuera de lo listado.
- ✓ **Excepción del carril `docs`:** `tests/test_project_scope_schema_contract.php:374-405` (nivel `db`, entra en `--nivel=http`, gate `php-suite`) falla si falta `docs/security/rls-runtime-boundary.md` o si le quitas alguno de siete literales, y exige tres archivos de `.superpowers/sdd/2026-08-28-rls-aplicacion-fail-closed/` (versionados: `git ls-files .superpowers` devuelve 5 archivos).
- Cinco `.md` de la raíz (`DESIGN.md`, `README.md`, `GEMINI.md`, `CLAUDE.md`, `AGENTS.md`) sí alteran gates de `static` (`design-doc-wiring.test.mjs`, `linen-removal.test.mjs`), pero `paths-ignore: '*.md'` hace que un PR solo de raíz no corra CI; el daño aparece en el siguiente PR con código.
- ✓ `/programa-general`, `/login` y `/proyectos` (GET) los sirve el shell React: `SpaRouter::RUTAS_EXACTAS_MIGRADAS` y `public/index.php:416` cortan con `exit` antes del router PHP. Las demás pantallas de los e2e (`/programacion-*`, CNP/CNC/CIC, `/profesionales`, `/subcontratistas`, `/indicadores`, `/control-cambios`) son vistas PHP con `public/js` y `public/css`. La **página** del laboratorio (`/internal/design-system`) es una vista PHP que no carga el bundle React.
- ✓ **Pero el gate «laboratorio» no es solo esa página:** `npm run test:design-system:runtime` (`package.json:18`, el único gate bloqueante, sin `continue-on-error`) incluye `design-system-body-canvas-dark.mjs`, que revisa el fondo oscuro de `/programa-general`, `/programacion-semanal`, `/programacion-intermedia`, `/indicadores`, `/profesionales`, `/subcontratistas` y `/control-cambios`, y `design-system-unlayered-delivery.mjs`, que recorre las 25 rutas de `docs/design-system/unlayered-delivery-inventory.json` (22 autenticadas y 3 públicas) y falla ante CSS sin capa o un 500. Esto lo destapó la revisión final de la rama (la spec 1.1 lo daba por una vista PHP y lo omitía en `ds-modulo`).
- ✓ Ningún gate lee `TASKS.md` (dos tests lo nombran solo en comentarios): puede ir al carril `docs`.
- ✓ Simulación del selector 1.1 sobre los últimos 40 merges de `main` (2026-09-28): 34 disparan CI; 30 caen en `todo` (22 llevan `TASKS.md`, y `tests/browser/**` aparece 93 veces). Con `TASKS.md` en `docs` serían 8 de 34 los que evitan `todo`; partiendo además `tests/test_*.php`, `tests/unit/**` y `tests/design-system/**`, 14 de 34.
- ✓ El bundle `public/app` está versionado (3 archivos en git) y el CI **nunca** corre `frontend:build`: un cambio en `frontend/src` sin rebuild commiteado no cambia lo que ven los e2e. No existe un gate «bundle commiteado == construido» (oportunidad, fuera de alcance).
- El código llega horneado a las imágenes de CI (`docker/php/Dockerfile:34` `COPY . /var/www/html`, sin volúmenes en `docker-compose.ci.yml`); `.dockerignore` deja dentro solo `docs/design-system` y `docs/security` de todo `docs/`.
- Los tokens (`tokens.css`, `aia-design-system.css`) los cargan la SPA, las vistas PHP y el laboratorio: de ahí el carril `ds-core` que dispara casi todo.
- Contratos sobre `ci.yml`: `visual-ci-contract.test.mjs`, `ci-workflow-provenance.test.mjs`, `phpstan-baseline.test.mjs` y `tests/test_runtime_boundary_ci_contract.php` (este último compara posiciones en **todo el archivo**, no por job). `pilot-e2e-contract`, `ci-preflight` y `design-system-ci-compose-contract` **no** leen `ci.yml`. El parser exige dos espacios por nivel y pasos como `      - clave:`. `visual-ci-contract.test.mjs:153` veta `deploy`, `production` y `pull_request_target`, comentarios incluidos.
- El fingerprint de procedencia (`design-system-ci-preflight.mjs:107-128`) hashea el contenido de los archivos del árbol, no los pasos ejecutados: un `if:` no lo altera entre las patas de la matriz. Los pasos «Restore the worktree…» lo devuelven al valor original tras cada recibo.
- ✓ Tres subidas de recibos usan `if-no-files-found: error` (`ci.yml:333,375,422`): si su gate se omite, su subida debe omitirse con la misma condición.
- No hay ni `dorny/paths-filter` ni `on.paths` en ningún workflow; solo `paths-ignore` en `ci.yml`.
- `pdc-app/` y `ct-app/` no tienen gate propio en el CI. Casi todo `tests/browser/` y `e2e/` fuera de los especificados no corre en CI. 41 tests PHP sueltos y 1 PHPUnit (nivel `datos-proyecto`) nunca corren en CI.
- ✓ **`main` no está protegido (medido el 2026-09-29):** `gh api repos/jbenite7/lastplanneraia-construccion/branches/main` devuelve `protected: false`, el endpoint de protección responde «Branch not protected» y `rules/branches/main` devuelve `[]`. Las versiones 1.0 a 1.2 decían que la API respondía 404 y que eso «no descartaba» checks obligatorios; era un error: se consultó `jbenite7/lps-aia`, un nombre que no existe (el repo es `jbenite7/lastplanneraia-construccion`).

**Vacíos:**

| # | Vacío | Estado |
|---|---|---|
| V1 | ¿Algún gate lee `docs/**`, `goals/**`, etc.? | **Cerrado 2026-09-28.** Sí: `docs/security/**` y `.superpowers/**` (carril `php`). El resto no. |
| V2 | ¿Qué dispara cada e2e? | **Cerrado 2026-09-28** con la matriz de §3; las celdas † siguen conservadoras. |
| V3 | ¿Quién corre `pdc-app/` y `ct-app/`? | Abierto → decisión A3. Esta spec solo los enruta. |
| V4 | ¿`main` tiene checks obligatorios? | **Cerrado 2026-09-29:** no. `main` no está protegido y no hay reglas (ver hechos). Queda una trampa **latente**: si algún día se activan, un job omitido cuenta como éxito, pero una pata de matriz que no se crea (por ejemplo `design-system-runtime (dark)` en un PR sin `lab` ni `pilot`) **no existe** y un check obligatorio con ese nombre esperaría para siempre; y con `cambios` en rojo, `static` y `runtime` quedan `skipped` y cuentan como éxito. Entonces habría que exigir `cambios` y no las patas por nombre. |
| V5 | Tiempos objetivo por carril (R7). | Abierto. Se mide en el paso 05 con corridas reales. |
| V6 | ¿Un `if:` altera el fingerprint entre patas? | **Cerrado 2026-09-28:** no. |
| V7 | Base del diff en `push` (revisión anterior) y en el primer push de una rama nueva o tras un force-push ajeno. Debe fallar hacia «todo». | Abierto. Lo cierra el ejecutor en el plan, con test. |
| V8 | Conteo de tests PHP: ≈164 sueltos y 32 PHPUnit, por grep de cabeceras; `CLAUDE.md` dice 139 y 17. | Abierto, informativo. No bloquea. |

## 5. Decisiones

**Tomadas:**

- **D1 (Felipe, 2026-09-28):** PR por carriles; `main` corre la suite completa como confirmación posterior al merge.
- **D2 (sesión, código):** el selector es un script propio (`scripts/ci-carriles.mjs`), no una acción de terceros ni el enrutador existente; los carriles son `if:` por paso dentro del mismo job por los contratos de §3.
- **D3 (sesión, código, por evidencia de V1):** `docs/**` sin gate, con las excepciones `docs/security/**` y `.superpowers/**` (carril `php`) y `docs/design-system/**` (carril `ds-core`).

- **D4 (Felipe, 2026-09-28), antes A2:** no se toca `paths-ignore` en esta spec. Los cinco `.md` de la raíz siguen sin disparar CI; cambiarlo altera la regla de AGENTS.md sobre quién ordena los merges de `AGENTS.md` y `CLAUDE.md`. Queda anotado como riesgo conocido.
- **D5 (Felipe, 2026-09-28), antes A3:** `pdc-app/` y `ct-app/` no reciben gate propio aquí; su gate sería una spec aparte. Esta spec solo los enruta (carril `apps` → `static` y PHPStan del PDC).

- **D6 (Felipe, 2026-09-28), tras la revisión final:** el laboratorio se enciende cuando el PR toca CSS, vistas o el bundle React (carriles `ds-core`, `ds-modulo`, `ds-lab` y `front-bundle`); no se enciende con solo `src/**` ni con solo `pdc-app`/`ct-app`. La brecha de esos dos carriles la cubre `main` completo (D1) y queda como riesgo aceptado.
- **D7 (Felipe, 2026-09-28):** `TASKS.md` pasa a `docs`; `tests/test_*.php` y `tests/unit/**` van a `tests-php`; `tests/design-system/**` va a `tests-ds`. `tests/browser/**`, `tests/fixtures/**`, `.github/**` y el resto siguen en `todo`.
- **D8 (sesión, código, por hallazgos de la revisión final):** `public/index.php` (front controller) → `php`; las siete hojas CSS que cargan el núcleo o el shell (`styles.css`, `buttons.css`, `access.css`, `handsontable-module.css`, `handsontable-header-global.css`, `auth-react.css`, `project-selector-react.css`) y `DesignSystemAssetController.php` → `ds-core`; `DesignSystemLabAccessPolicy.php` y `DesignSystemLabController.php` → `ds-lab`; el job `cambios` valida su salida antes de volcarla y escribe su tabla en el resumen (I2, I3).

**Abiertas para Felipe:** ninguna.

## 6. Fuera de alcance

Añadir gates nuevos (incluido «bundle commiteado == construido»); tocar goldens, baselines o presupuestos;
cambiar la política de merge de AGENTS.md; desplegar; tocar `paths-ignore`; las dos listas de excepciones
de PHPStan; actualizar los conteos de `CLAUDE.md`.

## 7. Riesgos

- **Un filtro que oculta una regresión cruzada** (un cambio en `src/` que rompe una pantalla). Mitigación: ruta sin dueño → todo (R2), e2e disparados por `php`, celdas † conservadoras y `main` completo (D1).
- **Leer un `skipped` como verde.** Mitigación: R5.
- **Un recibo omitido rompe su subida.** Mitigación: R8.
- **Romper un test de contrato al reformatear el YAML.** Mitigación: R6 y no cambiar sangrías ni nombres de pasos citados.
- **Los filtros envejecen** cuando cambia la estructura del repo. Mitigación: el default es «todo» y el selector tiene test de casos.
- **Brecha aceptada (D6):** un PR de solo `src/**` o de solo `pdc-app`/`ct-app` no corre el laboratorio, que revisa fondos oscuros y CSS sin capa en pantallas de producto; el rojo, si lo hay, aparece en `main` tras el merge. Mitigación: `main` completo (D1) y la corrida de `main` no se cancela (R3).
- **Checks obligatorios y patas de matriz (V4, latente):** hoy `main` no los exige; si Felipe los activa, no debe exigir `design-system-runtime (dark)` por nombre, y sí `cambios`.
- **El ahorro real depende de la mezcla de PR:** en la simulación, la mayoría de los merges recientes tocan `tests/browser/**` o cosas compartidas. R7 se mide en corridas reales antes de prometer una cifra.

## 8. Preguntas para investigar

Todas las del 1.0 quedaron cerradas (V1, V2, V6 y la del enrutador). Queda una para el plan: qué base usar
para el diff en `push` y cómo fallar hacia «todo» si no se puede calcular (V7).
