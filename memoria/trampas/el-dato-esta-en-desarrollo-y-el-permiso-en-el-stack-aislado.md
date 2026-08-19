---
capa: wiki
tipo: trampa
estado: derogada
fecha: 2026-08-13
areas: [qa, docker, datos]
fuente: tests/browser/support/dbSnapshot.mjs, database/fixtures/design-system-ci.sql, corrida de programacion-semanal-roles-phases.mjs
resumen: DEROGADA 2026-08-14 — se amplió el fixture (commit 8a0d5e46); el hueco que describe ya no existe. Diagnóstico conservado como referencia de qué mirar ante el mismo síntoma.
---

**Derogada el 2026-08-14**: el hueco que esta nota describía se cerró ampliando
`database/fixtures/design-system-ci.sql` (commit `8a0d5e46`) — JMC ganó las semanas 1-4
confirmadas con filas que cumplen la precondición de cada caso, y el caso de CNP se movió al
proyecto 68. El diagnóstico de abajo queda como referencia: la misma forma de trampa puede repetirse
con otro spec que necesite dato que el fixture no trae todavía.

**Recuento actualizado el 2026-08-18.** Esta nota decía «los 14 casos corren y pasan», y la cifra se
movió dos veces desde entonces, así que conviene leerla desglosada en vez de como un número suelto.
Hoy el spec declara **15 casos**: doce `test(...)`, uno de ellos dentro del bucle de cuatro roles de
`ROLE_CASES`. **Ninguno está saltado en firme** — los dos de tabla en tablet llegaron a estarlo por
el retiro de esa tabla y volvieron al gate cuando E3 cerró el hueco. Los cuatro `test.skip` que
siguen en el archivo son **condicionales dentro de un caso**, no casos excluidos: saltan solo fuera
del stack aislado, que es exactamente lo que esta página explica y sigue siendo correcto.
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
