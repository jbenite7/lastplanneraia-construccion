---
capa: wiki
tipo: decision
estado: abierto
fecha: 2026-09-28
areas: [qa, proceso]
fuente: "petición de Felipe y encuestas en el chat, 2026-09-28 (D1, D6, D7); docs/superpowers/specs/2026-09-28-ci-por-carriles-design.md v1.2; simulación sobre los últimos 40 merges de main"
resumen: "En un PR el CI corre solo los gates de los carriles que el diff alcanza y una ruta sin dueño corre todo; main corre siempre la suite completa y sin cancelarse. Abierta hasta el merge y la medición real"
---
# CI por carriles: el PR corre lo que alcanza y `main` corre todo

**Qué se decidió.** En `pull_request`, el CI corre solo los gates de los carriles que el diff toca; una
ruta que ningún carril reclame activa todo. En `push` a `main`, corre siempre la suite completa y una
corrida de `main` nunca se cancela por otra posterior. Los gates que no dependen del tema corren una
vez, no en las dos patas de la matriz.

**Cuándo y quién.** Felipe, en el chat, el 2026-09-28: D1 (PR por carriles y `main` completo), D6 (el
gate del laboratorio se enciende con CSS, vistas y bundle) y D7 (`TASKS.md` va a `docs` y `tests/**` se
parte). Sigue **abierta**: falta el merge, que es de Felipe, y medir el ahorro en corridas reales.

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

**Qué la desmentiría.** Que los tres PR de prueba (solo docs, solo `frontend/src`, solo un CSS de
módulo) no muestren un ahorro medible frente a los ~15 minutos de partida, o que un rojo de `main`
resulte ser algo que el filtro del PR habría ocultado con frecuencia. La simulación sobre los últimos 40
merges de `main` da: de 34 que disparan CI, 14 evitan correr todo y 7 no correrían ningún gate; antes
de partir `tests/**` y mover `TASKS.md`, 30 de 34 corrían todo.

Relacionadas: [[la-concurrencia-por-rama-deja-merges-de-main-sin-veredicto]] ·
[[un-check-obligatorio-no-cubre-una-pata-de-matriz-que-no-existe]] · [[qa-y-gates]] ·
[[docs/superpowers/specs/2026-09-28-ci-por-carriles-design|spec]] ·
[[docs/superpowers/plans/2026-09-28-ci-por-carriles|plan]] · [[goals/ci-por-carriles/goal|goal]]
