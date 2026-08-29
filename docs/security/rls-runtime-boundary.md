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
flujos indirectos. Ninguno de esos límites permite que la cuenta runtime ejecute DDL. La
verificación de la cuenta efectiva se hace read-only con `SHOW GRANTS`, sin almacenar ni imprimir
grants o secretos.

## Cambios de datos y estado histórico

Una migración se prepara primero como dry-run. `--apply` requiere una autorización separada,
freeze operativo, backup restaurable y restore probado antes de cualquier cambio. Esos requisitos
no se satisfacen con esta decisión ni con una lane de tests.

El estado histórico del plan anterior permanece `CODE_BLOCKED`: el breaker R5 concluyó que el
scanner no puede ser la frontera de seguridad. Esta frontera runtime y la separación `admin-db`
son la replanificación arquitectónica; no reescriben esa evidencia ni autorizan DDL, DML, grants,
revokes, usuarios, `--apply` o `--enforce`.
