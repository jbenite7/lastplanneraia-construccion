---
capa: wiki
tipo: decision
estado: vigente
fecha: 2026-09-28
areas: [qa, proceso]
fuente: "petición de Felipe y encuestas en el chat, 2026-09-28 (D1, D6, D7); docs/superpowers/specs/2026-09-28-ci-por-carriles-design.md v1.2; simulación sobre los últimos 40 merges de main"
resumen: "En un PR el CI corre solo los gates de los carriles que el diff alcanza y una ruta sin dueño corre todo; main corre siempre la suite completa y sin cancelarse. Vigente desde el merge del PR #82 (2026-09-29); el ahorro depende de la mezcla de PR"
---
# CI por carriles: el PR corre lo que alcanza y `main` corre todo

**Qué se decidió.** En `pull_request`, el CI corre solo los gates de los carriles que el diff toca; una
ruta que ningún carril reclame activa todo. En `push` a `main`, corre siempre la suite completa y una
corrida de `main` nunca se cancela por otra posterior. Los gates que no dependen del tema corren una
vez, no en las dos patas de la matriz.

**Cuándo y quién.** Felipe, en el chat, el 2026-09-28: D1 (PR por carriles y `main` completo), D6 (el
gate del laboratorio se enciende con CSS, vistas y bundle) y D7 (`TASKS.md` va a `docs` y `tests/**` se
parte). Se mergeó el 2026-09-29 (PR #82, `981ae3d2`) por orden de Felipe.

**Por qué así.** Cada PR esperaba unos 15 minutos y corría todo dos veces, aunque tocara una línea de
documentación. `static` casi nunca se puede saltar (lee `src`, `views`, `public/*` y tres SPA), así que
el ahorro está en los PR de documentación, de solo `frontend/src` y en no repetir en el tema oscuro lo
que no depende del tema.

**Qué se descartó y por qué.**
- **Partir `design-system-runtime` en varios jobs.** Tres tests de contrato exigen que
  `runtime-provenance`, `runtime-grants`, `php-suite` y el resto vivan dentro de ese job; por eso los
  carriles son un `if:` por paso.
- **Una acción de terceros (`dorny/paths-filter`).** Habría que fijarla por SHA y no se prueba en
  local. El selector es un script propio con tests (`scripts/ci-carriles.mjs`).
- **Reutilizar `scripts/design-system-router.mjs`.** Conoce 2 de 13 gates y devuelve lista vacía para
  `src/**`, que es lo contrario de fallar hacia lo seguro.
- **Omitir el laboratorio en un PR de CSS.** Ver [[el-gate-de-laboratorio-tambien-revisa-pantallas-de-producto]].

**Qué se midió (2026-09-29, corridas reales, línea base ~15 min y ~1.680 s de suma de jobs).**

| PR de prueba | Qué tocó | Reloj | Suma de jobs |
|---|---|---|---|
| #84 | 1 archivo de documentación | 23 s | 18 s |
| #85 | 1 archivo de `frontend/src` | 198 s | 189 s |
| #86 | 1 CSS de módulo (laboratorio en los dos temas) | 911 s | 1.292 s |
| #82 | el propio CI (carril `todo`) | 994 s | 1.393 s |
| `main` tras el merge | corrida de `push`: selector `completo`, nueve gates y dos temas | 964 s | 1.341 s |

El ahorro grande está en los PR chicos. Con CSS o vistas el reloj casi no baja, porque D6 hace correr el
laboratorio, y con lo compartido sube ~1 minuto por el job `cambios`, aunque la suma de jobs baja
17 a 23 %, sobre todo por la pata `dark` (de ~730 s a ~400 s).

**Qué la desmentiría.** Que en un mes de PR reales la mayoría siga cayendo en `todo` o en carriles con
el laboratorio encendido, de modo que la espera media no baje; la simulación sobre los últimos 40
merges daba 14 de 34 PR que evitan correr todo y 7 que no correrían ningún gate, y `tests/browser/**`
era lo que más empujaba a `todo`. También, un rojo de `main` que el filtro del PR habría atrapado con
frecuencia: la brecha aceptada es que un PR de solo `src/**` o de solo `pdc-app`/`ct-app` no corre el
laboratorio.

Relacionadas: [[la-concurrencia-por-rama-deja-merges-de-main-sin-veredicto]] ·
[[un-check-obligatorio-no-cubre-una-pata-de-matriz-que-no-existe]] · [[qa-y-gates]] ·
[[docs/superpowers/specs/2026-09-28-ci-por-carriles-design|spec]] ·
[[docs/superpowers/plans/2026-09-28-ci-por-carriles|plan]] · [[goals/ci-por-carriles/goal|goal]]
