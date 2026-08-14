---
tipo: trampa
estado: vigente
fecha: 2026-08-13
areas: [qa, docker, datos]
fuente: tests/browser/support/dbSnapshot.mjs, database/fixtures/design-system-ci.sql, corrida de programacion-semanal-roles-phases.mjs
resumen: un e2e que escribe en la base solo puede correr en el stack aislado de CI, pero el fixture de ese stack no trae el dato rico de desarrollo, así que no hay ningún entorno donde pase
---
`runSql` (`tests/browser/support/dbSnapshot.mjs`) enruta **todo** comando de base por
`isolatedComposeArgs`, que llama a `assertIsolatedComposeEnvironment` y revienta si
`COMPOSE_PROJECT_NAME` no empieza por `lps-aia-design-system-ci-`. El candado es deliberado: evita
que un e2e escriba sobre la base de desarrollo compartida.

La trampa es que el fixture versionado del stack que sí tiene permiso —
`database/fixtures/design-system-ci.sql`— es **sintético y mínimo**. Medido el 2026-08-13: del
proyecto 68 siembra **una** semana (la 5) sin confirmar y una sola fila, y el proyecto 27
(«Prueba») no existe allí. Así que una prueba que necesite una semana confirmada, una semana
histórica o filas CNP se queda sin sitio donde correr:

| | Dato rico | Permiso de escritura |
|---|---|---|
| Stack de desarrollo | sí | **no** (el candado lo bloquea) |
| Stack aislado de CI | **no** (fixture mínimo) | sí |

Cuatro casos de `programacion-semanal-roles-phases.mjs` cayeron exactamente ahí y quedaron con
`test.skip` y motivo escrito, no borrados. **La salida no es relajar el candado** —eso devuelve la
escritura a la base compartida—, sino **ampliar el fixture**, que es contrato versionado y por
tanto trabajo con plan propio.

Antes de escribir un e2e que muta datos, mira qué siembra el fixture: si el dato que necesitas solo
existe porque alguien lo creó a mano en desarrollo, la prueba nace sin entorno.

Ver también [[exec-en-contenedor-vivo-corre-el-repo-ajeno]] y
[[fijar-un-dato-de-la-base-en-un-test-lo-podre]].
