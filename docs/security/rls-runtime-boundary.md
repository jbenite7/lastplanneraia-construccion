---
capa: fuente
tipo: contrato
estado: vigente
fecha: 2026-08-29
areas: [datos, rbac]
fuente: docs/security/rls-runtime-boundary.md
resumen: "Frontera runtime de RLS y lanes de pruebas"
---

# Frontera runtime de RLS y lanes de pruebas

## Decisión

La cuenta MySQL usada por cada request de la aplicación y por las lanes runtime sólo dispone de
privilegios DML-only (`SELECT`, `INSERT`, `UPDATE`, `DELETE`) sobre la base de datos de la
aplicación. El flujo que se pretende proteger es:

```text
runtime request → usuario DML-only → MySQL niega DDL
```

Por tanto, una prueba declarada de forma incorrecta puede fallar por privilegios, pero no obtiene
capacidad para cambiar el schema. Esta cuenta complementa el aislamiento de aplicación por
proyecto; no lo sustituye ni convierte MySQL en un mecanismo RLS nativo.

Las pruebas declaran su lane en un contrato visible. Las lanes runtime son acumulativas en este
orden: `puro`, `db`, `http` y `datos-proyecto`. `admin-db` no es acumulativa: sólo selecciona una
declaración `admin-db` exacta y una lane runtime nunca la selecciona.

El único flujo administrativo permitido por diseño es:

```text
admin-db → DB efímera → credencial one-off
```

La credencial administrativa se limita al proceso del step CI y no se comparte con la aplicación
ni con las lanes runtime. Esta decisión no autoriza ejecutar `admin-db` localmente ni cambiar
grants, usuarios o credenciales.

## Autoridad y diagnóstico

`scripts/lib/php-test-ddl-inventory.php` es advisory: puede señalar SQL, callables o rutas que
merecen revisión, pero no concede, eleva ni deniega autoridad de ejecución. En particular, su
resultado no cambia la cuenta usada por una prueba ni clasifica de manera implícita una prueba como
administrativa. La declaración de lane es el contrato de selección; los privilegios efectivos de
MySQL son la frontera autoritativa.

Los límites de este enfoque son explícitos: una declaración equivocada puede producir una prueba
fallida, y el scanner advisory puede ser incompleto ante código dinámico, providers externos o
flujos indirectos. En concreto, el scanner no garantiza cobertura completa de `callables dinámicos`,
`providers externos` ni `joins de flujo`: puede no demostrar qué callable, proveedor o rama termina
en un sink SQL. Esos límites son una amenaza residual de diagnóstico, no una autorización implícita;
un resultado verde del scanner nunca convierte una prueba en administrativa ni eleva privilegios.
Ninguno de esos límites permite que la cuenta runtime ejecute DDL. La verificación de la cuenta
efectiva se hace read-only con `SHOW GRANTS`, sin almacenar ni imprimir grants o secretos; la falta
de una concesión DML exacta es una atestación fallida, no un permiso para continuar.

### Garantías y fallos esperados

La frontera tiene tres garantías separadas:

1. La cuenta runtime sólo puede hacer DML (`SELECT`, `INSERT`, `UPDATE`, `DELETE`) en la base
   autorizada. Si una request o fixture intenta `CREATE`, `ALTER`, `DROP` u otro DDL, MySQL lo
   rechaza por privilegios; la prueba falla sin cambiar el schema.
2. La declaración de lane decide qué se selecciona. Las lanes runtime acumulan por nivel; `admin-db`
   exige coincidencia exacta, DB efímera y credencial one-off. Un test con DDL mal declarado no
   recibe una excepción del scanner ni privilegios adicionales: falla al ejecutarse con runtime.
3. `SHOW GRANTS` sólo atestigua la cuenta efectiva. Es una consulta read-only para comprobar la
   concesión DML-only y el alcance de la base; sus líneas no se publican ni se usan como secretos.

El scanner advisory puede orientar una revisión, pero no prueba la ausencia de DDL ni la seguridad
de una ruta dinámica. La autoridad permanece en la cuenta MySQL y en la declaración visible de lane.

## Cambios de datos y estado histórico

Una migración se prepara primero como dry-run. `--apply` requiere una autorización separada,
freeze operativo, backup restaurable y restore probado antes de cualquier cambio. `--apply` debe usar
un canal administrativo one-off, separado de la cuenta runtime y de `admin-db`; nunca convierte el
dry-run en una mutación automática. Esos requisitos no se satisfacen con esta decisión ni con una
lane de tests.

El estado histórico del plan anterior permanece `CODE_BLOCKED`: el breaker R5 concluyó que el
scanner no puede ser la frontera de seguridad. Esta frontera runtime y la separación `admin-db`
son la replanificación arquitectónica; no reescriben esa evidencia ni autorizan DDL, DML, grants,
revokes, usuarios, `--apply` o `--enforce`.
