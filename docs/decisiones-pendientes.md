# Decisiones pendientes del usuario

Cola de decisiones que necesitan el criterio del usuario y **no interrumpen el trabajo**. Cualquier
sesión de ejecución añade aquí lo que encuentre, se salta ese punto y sigue. La sesión coordinadora
presenta la cola entera al cerrar cada frente, en una sola tanda de grilleo.

El procedimiento está en [`coordinacion-sesiones.md`](coordinacion-sesiones.md). Regla que lo
sostiene: **una sesión de ejecución nunca para** — anota, salta y continúa.

## El id lleva su origen — no uses números sueltos

**Formato: `D-<origen>-<n>`**, donde `<origen>` identifica a quien pregunta: `F1`, `F1b`, `CI`,
`COORD`… Por ejemplo `D-F1-1`, `D-CI-1`.

Las dos primeras entradas son `D-1` y `D-2` porque se escribieron antes de esta regla; se dejan como
están para no romper lo ya publicado.

**Por qué:** varias sesiones añaden a esta cola desde ramas distintas y no se ven entre sí. Con
números sueltos, dos que empiecen a la vez eligen ambas el siguiente número y colisionan al
integrar. Pasó el 2026-08-10, el mismo día que se creó la cola: dos sesiones escribieron su `D-2`
sin saberlo. Un id que lleva su origen no puede chocar sin coordinación previa, que es justo lo que
no hay.

## Cómo añadir una entrada

Una entrada sirve si el usuario puede decidir **sin abrir el código**. Eso exige haber medido antes
de preguntar. Copia esta forma:

```markdown
### D-<origen>-<n> · <título en una línea>

- **Quién pregunta:** <sesión / frente / tarea>
- **Fecha:** <AAAA-MM-DD>
- **Qué se decide:** una frase, en lenguaje simple.
- **Qué se midió:** los hechos, con `archivo:línea` o salida de comando. Sin esto la pregunta no
  vale: obliga al usuario a investigar lo que debía traerle resuelto.
- **Opciones:** las reales, con su consecuencia. Si hay una segura o reversible, señálala.
- **Recomendación:** cuál y por qué. Se le pide criterio, no que elija a ciegas.
- **Qué quedó saltado esperando:** qué no se tocó, para poder retomarlo.
- **Estado:** `abierta` · `resuelta <AAAA-MM-DD>: <decisión>`
```

---

## Entradas

*(Ninguna abierta ahora mismo: las tres se decidieron el 2026-08-11. Se conservan aquí con su estado; el índice de resueltas está al final.)*

### D-1 · Dos numeraciones de «frente» conviviendo en el repo

- **Quién pregunta:** sesión coordinadora, auditoría del 2026-08-10.
- **Fecha:** 2026-08-10
- **Qué se decide:** cuál de las dos formas de numerar los frentes es la buena, para que los
  documentos dejen de contradecirse.
- **Qué se midió:**
  - `docs/superpowers/specs/2026-08-10-programa-cierre-pendientes-design.md` numera **Frente 0, 1,
    1b, 2, 3, 4 y 5**, donde el 1 son los hallazgos del backlog y el 4 es la matriz diagonal de
    gates.
  - `docs/reportes/estado-desarrollo.html` (commit `0e48bdc8`) llama **«frente 4»** al trabajo de
    RBAC, que en el spec es el **Frente 1, tanda 1A**.
  - Las dos numeraciones están vivas y publicadas a la vez. Quien lea ambas creerá que se habla de
    trabajos distintos, o peor, que el mismo número es dos cosas.
- **Opciones:**
  - **(a)** El informe adopta la numeración del spec. El spec es el contrato del programa y define
    la condición de hecho de cada frente.
  - **(b)** El spec adopta la del informe. Tiene sentido solo si esa numeración es anterior y ya se
    usa en más sitios de los que he visto.
  - **(c)** Cada uno conserva la suya y se añade una tabla de equivalencia. Evita reescribir, pero
    deja dos vocabularios para lo mismo, que es como empezó el problema.
- **Recomendación:** **(a)**. El spec es el documento que gobierna el trabajo y el que las sesiones
  de ejecución leen para saber qué les toca; el informe describe. Cuando una descripción y un
  contrato discrepan, se corrige la descripción. Reversible: renumerar un informe no rompe nada.
- **Qué quedó saltado esperando:** nada. Es documental y no bloquea a nadie.
- **Estado:** `resuelta 2026-08-11: (a) — el informe de estado adopta la numeración del spec. El spec gobierna y el informe describe; cuando discrepan, se corrige la descripción.`

### D-2 · Qué se hace con los 30 tests que el CI no puede correr

- **Quién pregunta:** sesión del frente «runner de tests PHP» (cerrado y publicado en `2eccf15e`).
- **Fecha:** 2026-08-10
- **Qué se decide:** si se les da al CI los datos que les faltan, si se reescriben para no
  necesitarlos, o si algunos se retiran por obsoletos.
- **Qué se midió:** los 101 `tests/test_*.php` corridos por código de salida contra el stack de
  `docker-compose.ci.yml` (`php scripts/run-php-tests.php --nivel=datos-proyecto`): **71 pasan, 28
  fallan, 1 se salta solo**. Ninguno de los 28 es código de producción roto; a todos les falta
  entorno:
  - **20** piden datos que el fixture no tiene — 14 son `test_pdc_v2_*`.
  - **4** piden tablas que el fixture no crea (p. ej. `test_password_reset_resultados`).
  - **4** leen evidencia de `docs/qa/evidence/` que no viaja en git
    (`test_goal_close_blockers_manifest`, `test_human_decision_actions_package`,
    `test_human_decision_approval_checklist`, `test_human_decision_matrix_coverage`).
  - **2** se saltan solos cuando falta el proyecto 73 (`test_pdc_v2_amarre_cronograma`,
    `test_pdc_v2_brecha_daporto`).
  - `memoria/trampas/suite-php-rojos-preexistentes.md` ya da por obsoletos a
    `test_pdc_v2_brecha_daporto` (fija la versión 292 de Da Porto, que desapareció al reimportarse
    el presupuesto el 2026-07-29) y a `test_human_validation_matrix`.
- **Opciones:**
  - **(a)** Enriquecer el fixture de CI hasta que corran. Gana cobertura real, pero es un frente
    propio y grande, y roza `memoria/trampas/no-enriquecer-daporto-para-medir.md`.
  - **(b)** Triarlos uno a uno: fixture para los que aportan, reescritura para los que dependen de
    datos reales sin necesitarlo, retirada para los obsoletos. Más lento, deja la suite honesta.
  - **(c)** Dejarlos como están, declarados y fuera del CI. **Es la opción segura y reversible:** es
    el estado actual, no rompe nada y su número sale contado en cada corrida del CI, así que no se
    esconde.
- **Recomendación:** **(b)**, pero sin urgencia. Hoy (c) ya evita el daño —nadie los confunde con
  verdes— y (a) gastaría un frente entero en datos de prueba antes de saber cuáles de los 30 merecen
  seguir vivos. El triaje es lo único que responde esa pregunta, y puede hacerse por tandas.
- **Qué quedó saltado esperando:** nada del frente cerrado. Los 30 están etiquetados
  `// @requiere: datos-proyecto`, el CI no los corre y su número aparece en el resumen de cada
  corrida. No se tocó ningún fixture ni ningún dato.
- **Estado:** `resuelta 2026-08-11: (b) — triarlos por tandas, sin urgencia. Fixture para los que aportan, reescritura para los que dependen de datos sin necesitarlo, retirada para los obsoletos. No es un frente propio: se hace por tandas cuando haya ocasión.`

### D-CI-1 · El contrato visual fija una forma donde debería medir un resultado

- **Quién pregunta:** sesión del frente «runner de tests PHP».
- **Fecha:** 2026-08-10
- **Qué se decide:** si una aserción del contrato del design system debe seguir exigiendo que el
  workflow **nombre** un comando concreto, o pasar a comprobar que el CI **ejecuta** ese test.
- **Qué se midió:**
  - `tests/design-system/visual-ci-contract.test.mjs:156` exige
    `assert.match(workflow, /php tests\/test_global_table_safety\.php/)`: una cadena literal.
  - Al sustituir los tres tests listados a mano por el runner, esa cadena desapareció y el gate
    `node-tests` quedó **rojo en `main`**, aunque el test seguía ejecutándose dentro de la selección
    del runner. Es decir: el gate se puso rojo por un cambio que **aumentó** la cobertura de 3 tests
    a 71.
  - Arreglado por ahora conservando el paso explícito en `.github/workflows/design-system.yml`
    además del runner. El test corre dos veces; cuesta menos de 1 s. Suite estática completa
    verificada después: **los 8 gates en verde, RC=0**.
- **Opciones:**
  - **(a)** Dejarlo como está: paso explícito y runner conviviendo. **Es la opción segura:** es el
    estado actual, está en verde y no toca ningún contrato. Coste: el workflow lleva un paso
    redundante que hay que explicar a quien lo lea.
  - **(b)** Cambiar la aserción para que compruebe que el CI invoca el runner y que su selección
    incluye ese test. Queda **más fuerte** que hoy: comprobaría el resultado y no la forma. Coste:
    toca un contrato del design system, y esos no se tocan sin decisión explícita.
  - **(c)** Quitar la aserción. **No recomendable**: pierde la garantía de que esa frontera se
    vigila en CI, que es justo lo que el contrato existe para sostener.
- **Recomendación:** **(b)**, cuando haya ocasión. La aserción actual tiene un defecto real —premia
  que el workflow escriba una cadena, no que ejecute la prueba—, y con el runner en medio ese
  defecto va a volver a morder: cualquier reorganización futura del CI que siga ejecutando el test
  volverá a poner el gate rojo. Pero es un contrato ajeno a este frente y no urge: hoy está verde.
- **Qué quedó saltado esperando:** no se tocó `visual-ci-contract.test.mjs`. El workflow quedó con
  el paso explícito, que es lo que el contrato pide hoy.
- **Estado:** `resuelta 2026-08-11: (b) — la aserción pasa a comprobar que el CI ejecuta la prueba, no que la nombra. Queda más fuerte. Sin prisa, pero antes de que otra reorganización del CI la vuelva a poner roja.`

---

## Resueltas

Se quedan arriba, en su sitio, con el estado cambiado: mover una entrada resuelta rompe los enlaces
que la citan y pierde el contexto que la rodea. Este índice es para encontrarlas rápido.

| Id | Decisión | Quién la ejecuta |
|---|---|---|
| `D-1` | El informe adopta la numeración del spec | La sesión que mantiene `docs/reportes/estado-desarrollo.html` |
| `D-2` | Triar los 30 tests por tandas, sin urgencia | Sin dueño asignado; se recoge cuando haya ocasión |
| `D-CI-1` | La aserción comprueba que el CI ejecuta la prueba | Quien retome el contrato del design system, o el Frente 1b |
