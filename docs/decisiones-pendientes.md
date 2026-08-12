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

**Añadir una entrada propia es seguro y no hace falta pedir permiso.** Lo que colisiona es editar
lo que no escribiste: cambiar el estado de una entrada ajena, marcar una resolución o reordenar.
Eso lo hace la coordinadora, que es quien habla con el usuario y sabe qué está decidido.

*(Regla afinada el 2026-08-11. La primera versión decía «no toques la cola» a secas, y era
demasiado: la sesión de CI añadió dos entradas impecables creyendo que infringía algo. Añadir es
justo lo que la cola necesita.)*

**Por qué el id lleva su origen:** varias sesiones añaden a esta cola desde ramas distintas y no se
ven entre sí. Con
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
- **Estado:** `resuelta 2026-08-11: (b) — la aserción pasa a comprobar que el CI ejecuta la prueba, no que la nombra. Queda más fuerte. Sin prisa, pero antes de que otra reorganización del CI la vuelva a poner roja.` · **ejecutada 2026-08-11** por la sesión de CI: `visual-ci-contract.test.mjs · **verificado por la coordinadora sobre `e66e7672`**: el paso explícito retirado (`grep -c` da 0), la cadena literal fuera del contrato, suite estática RC=0, y la mutación comprobada de forma independiente — cambiarle el nivel a la prueba a uno que el CI no corre pone el gate en RC=1 y lo restaura en RC=0. El candado nuevo muerde.` cruza el nivel que el workflow invoca con el que la prueba declara, y el paso explícito que la duplicaba se retiró del workflow. Puesta en rojo por tres vías que la aserción vieja no detectaba (nivel cambiado, etiqueta quitada, invocación quitada) y restaurada; suite estática en verde en los 8 gates.

### D-CI-2 · ¿Los tests nuevos deben escribirse en PHPUnit, o conviven los dos estilos?

- **Quién pregunta:** sesión del frente «PHPUnit incremental».
- **Fecha:** 2026-08-11
- **Qué se decide:** si a partir de ahora una prueba nueva **tiene que** ser una clase de PHPUnit en
  `tests/unit/`, o si sigue valiendo escribir un script `tests/test_*.php` como hasta hoy.
- **Qué se midió:** la fase 2 dejó las dos suites funcionando bajo el mismo runner y con las mismas
  garantías; ninguna de las dos está en desventaja técnica. Verificado: `--nivel=puro` corre 22
  scripts **y** la clase de PHPUnit en una sola pasada, rc=0. Los 101 scripts existentes siguen
  ejecutándose igual. Escribir el primer test con PHPUnit costó 18 casos y 40 aserciones en un
  archivo; el equivalente en script habría exigido reimplementar contadores, `verificar()` y el
  `exit()` a mano, sin proveedores de datos.
- **Opciones:**
  - **(a)** PHPUnit obligatorio para todo test nuevo. Unifica el estilo y hace que la suite vieja se
    vacíe sola con el tiempo. Coste: quien solo quiera comprobar algo rápido tiene que aprender el
    andamiaje de PHPUnit.
  - **(b)** Los dos estilos conviven sin regla. **Es la opción segura y es el estado actual:** no
    rompe nada y no obliga a nadie. Coste: dos formas de hacer lo mismo, y la elección se decide por
    costumbre en vez de por criterio.
  - **(c)** PHPUnit obligatorio solo para lógica pura, y scripts para lo que necesite datos o la
    aplicación viva. Reconoce que los tests `db` y `http` del repo son más guiones de integración
    que pruebas unitarias. Coste: la frontera hay que explicarla, y los casos dudosos vuelven.
- **Recomendación:** **(c)**. Es lo que ya pasa de hecho: los 22 de nivel `puro` son los que se
  benefician de proveedores de datos y aserciones ricas, y los de `db`/`http` son guiones que
  dependen del estado de una base y encajan mal en el molde unitario. Pero es una regla de equipo,
  no una decisión técnica: la impone quien va a escribir las pruebas.
- **Qué quedó saltado esperando:** nada. Las dos suites funcionan y el CI corre ambas. No se migró
  ningún test existente, que era condición del encargo.
- **Estado:** `resuelta 2026-08-11: (c) — PHPUnit obligatorio para lógica pura, scripts para lo que necesita base de datos o la aplicación viva. Es lo que ya pasaba de hecho; la decisión lo hace explícito para que la elección deje de depender de quién escriba. Recomendación de la sesión de CI, adoptada.`

### D-F1-1 · La misma falta se pinta como «crítico» en una pantalla y como «aviso» en la otra

- **Quién pregunta:** sesión de ejecución del Frente 1, tanda 1B, Task 5.
- **Fecha:** 2026-08-10
- **Qué se decide:** cuando una actividad no tiene Responsable AIA asignado, ¿eso es un **error
  grave** (rojo) o un **aviso** (ámbar)? Hoy Programación Semanal lo pinta rojo y Programación
  Intermedia lo pinta ámbar, y es exactamente la misma falta.
- **Qué se midió:**
  - Programación Semanal: `.ps-missing-assignment` usa `--ds-color-state-critical-text` y **no
    declara peso de fuente** (`public/css/programacion-semanal.css:1690-1693`).
  - Programación Intermedia: `.pi-missing-resp` usa `--ds-color-state-warning-text` y **pesa 600**
    (`public/css/programacion-intermedia.css:532-537`).
  - Las dos marcan la misma condición y las dos bloquean: en Semanal impide cerrar la semana, en
    Intermedia impide gestionar restricciones.
  - **Esta divergencia no estaba registrada en ninguna parte.** La ficha del backlog (ICE 392)
    afirmaba lo contrario —que Intermedia «no marca esa celda de ninguna forma»—, y es falso desde
    el commit `7ff39b54` del 2026-08-05.
- **Opciones:**
  - **(a) Las dos en «crítico» (rojo).** Coherente con que la falta bloquea en las dos pantallas.
    Hace Intermedia más alarmante de lo que es hoy.
  - **(b) Las dos en «aviso» (ámbar).** Reserva el rojo para lo que ya no tiene arreglo dentro de la
    pantalla. Baja el tono de Semanal, que es donde más consecuencia tiene.
  - **(c) Dejarlas distintas y escribir por qué.** Defendible si el rojo de Semanal es deliberado
    porque ahí la falta bloquea el cierre del ciclo, y en Intermedia solo bloquea una columna.
  - Las tres son reversibles: es un token de color en una hoja de estilo, sin efecto sobre datos.
- **Recomendación:** **(c)**, escribiendo el motivo. La consecuencia real **no** es la misma en las
  dos pantallas —en Semanal la falta frena el cierre de la semana entera, en Intermedia solo impide
  editar unas celdas—, así que dos tonos distintos comunican algo cierto en vez de una
  inconsistencia. Lo que hoy falta no es que coincidan: es que nadie escribió que la diferencia era
  a propósito.
- **Qué quedó saltado esperando:** solo la elección de severidad. **Sí se hizo** lo que el usuario ya
  había aprobado para el ICE 448 —dar regla a la clase de fondo `.ps-cell-empty-alert`, que no tenía
  ninguna en todo el árbol, y subir el peso del glifo de Semanal—, porque esa disposición dice «es
  solo visual» y no depende de esta decisión.
- **Estado:** `resuelta 2026-08-11: (c) — las dos severidades se quedan distintas (crítico en Semanal,
  aviso en Intermedia) y el motivo queda escrito junto a cada regla (programacion-semanal.css y
  programacion-intermedia.css, junto a .ps-cell-empty-alert y .pi-missing-resp). Cero CSS tocado.`

### D-F1-2 · Ningún token de fondo de estado llega a 3:1 contra la superficie base de la tabla

- **Quién pregunta:** sesión de ejecución del Frente 1, tanda 1B, Task 5 (ronda de arreglo 1/5).
- **Fecha:** 2026-08-10
- **Qué se decide:** si vale la pena crear o ajustar un token de fondo de estado para que el aviso
  «⚠ Sin asignar» de Programación Semanal cumpla el 3:1 de WCAG 1.4.11 **fondo-contra-fondo**, o si
  se acepta que ese canal se queda por debajo y la distinción real corre por otra vía (forma + peso
  + contraste de texto).
- **Qué se midió:** en `/programacion-semanal`, dark, 1180×820, sobre el árbol real de Handsontable
  (`--ds-active-surface` resuelto a `rgba(28,36,31,0.92)`, compuesto contra `--ds-active-bg-page`
  `rgb(17,26,21)`, converge a `rgb(28,36,31)` opaco — verificado además leyendo `backgroundColor`
  real de celdas vecinas ya renderizadas en Programación Intermedia, que comparte el mismo stack de
  CSS: dan literalmente `rgba(28, 36, 31, 0.92)`). Contraste de luminancia WCAG de cada token de
  fondo de estado (`tokens.css:241-244`) contra esa superficie base:
  - `--ds-color-state-critical-bg` `rgb(67,20,20)` → **1,02:1**
  - `--ds-color-state-warning-bg` `rgb(58,58,15)` → **1,36:1**
  - `--ds-color-state-success-bg` `rgb(23,61,38)` → **1,31:1**
  - `--ds-color-state-info-bg` `rgb(19,72,65)` → **1,54:1**
  Ninguno de los cuatro tokens de fondo de estado que existen hoy llega ni cerca del 3:1. El elegido
  para esta tarea (`critical-bg`, el que aprobó el plan) es el **peor** de los cuatro por ese criterio
  específico, aunque es el correcto por severidad (ver `D-F1-1`).
- **Cómo se ve, sin jerga —para poder decidir sin abrir el código:** la celda del aviso **sí** se
  distingue de sus vecinas, pero **no por el fondo**. Lo que se ve es el texto «⚠ Sin asignar» en
  rosa claro y en negrita, sobre un fondo rojo tan oscuro que a simple vista parece el mismo negro
  verdoso que el resto de la tabla. El `1,02:1` significa literalmente eso: entre ese fondo y el de
  al lado hay **un 2 % de diferencia de luminosidad**, y la norma pide un 200 %. El texto en sí se
  lee perfectamente (esa es la otra cifra, `10,99:1`).
  - Traducido a la pregunta que importa: **la celda se nota por su letra, no por su color de fondo.**
    Si alguien mira la tabla entornando los ojos o de lejos, no verá un bloque rojo — verá una fila
    con un texto distinto.
  - Lo que esta tarea **sí** mejoró: antes esa letra no era negrita y el fondo no existía en absoluto
    (la regla de estilo estaba escrita en el código pero no llegaba a aplicarse nunca). Ahora la letra
    pesa y el fondo existe, aunque casi no se aprecie.
- **Opciones:**
  - **(a) Aceptar que este canal no llega a 3:1** y dejar que la distinción de la celda dependa de lo
    que sí mide bien: la marca sobre su propio fondo da 10,99:1 (1.4.3, texto) y el peso 600 añade una
    señal de forma. Es lo que hay hoy tras esta tarea. No toca ningún token.
  - **(b) Subir la saturación/luminancia del propio `critical-bg`** (o crear una variante «surface»
    más clara solo para bordes de componente) hasta que separe 3:1 de la superficie base. Cambia un
    token compartido por otras superficies (`--ds-cell-state-critico-bg` lo reutiliza en
    `tokens.css:635`), así que el efecto se propaga fuera de esta celda — alcance de design system,
    no de este módulo.
  - **(c) Añadir un borde o contorno con un token de línea de estado** (`--ds-color-border-*` si
    existe uno de severidad) en vez de depender del fondo para el 3:1 de componente. No se investigó
    si ese token existe; queda para quien tome la decisión.
  - Todas son reversibles: es CSS/tokens, sin dato de por medio.
- **Recomendación:** **(a)** para esta tarea puntual — es la única que no reabre alcance de design
  system ni afecta otras superficies que ya consumen `critical-bg`/`critico-bg`—, pero **(b)** o
  **(c)** merecen mirarse a nivel de sistema porque el patrón se repite: los cuatro tokens de fondo de
  estado están pensados para tinte de superficie completa, no para el contraste de borde de un
  componente pequeño sobre una superficie ya oscura, y probablemente ningún uso futuro de estos
  tokens en una celda pequeña vaya a llegar a 3:1 tal como están calibrados hoy.
- **Qué quedó saltado esperando:** no se tocó ningún token ni se creó uno nuevo. El CSS de esta tarea
  se queda con `--ds-color-state-critical-bg` tal como está.
- **Estado:** `resuelta 2026-08-11: familia nueva --ds-color-cell-*-bg (critical #c43b3b, warning
  #71711d, success #2e7b4d, info #207a6e), calibrada a ≥3:1 contra rgb(28,36,31) (medidos: 3,054 ·
  3,089 · 3,07 · 3,08). Los cuatro --ds-color-state-*-bg no se tocaron. .ps-cell-empty-alert ahora
  usa --ds-color-cell-critical-bg; fondo-alerta contra fondo-vecino subió de 1,02:1 a 3,054:1, que
  cierra el ICE 448.`

### D-F1-3 · Los cinco tokens con reserva hex en `public/js/` no están definidos en ningún CSS

- **Quién pregunta:** sesión de ejecución del Frente 1, tanda 1C, Task 3.
- **Fecha:** 2026-08-11
- **Qué se decide:** las cinco ocurrencias `var(--token, #hex)` de `public/js/` (frente a las 12 sin
  reserva) iban a perder su reserva porque esa es la política dominante del repo — pero el paso de
  seguridad exigido por la propia tarea (Step 3: quitar la reserva solo si el token existe) dio
  negativo para **las cinco**. No hay ninguna que se pueda tocar sin cambiar el color en pantalla.
- **Qué se midió:**
  - Recuento repetido y confirmado: `grep -rn "var(--[a-z-]*, *#" public/js/` → 5 ocurrencias con
    reserva; `grep -ro "var(--[a-z-]*)" public/js/ | wc -l` → 12 sin reserva. La dirección «quitar
    reserva» sigue siendo la que gana.
  - Las cinco ocurrencias con reserva, hoy (los números de línea se movieron otra vez):
    - `public/js/modules/programacion_intermedia/hot.js:2088` → `--aia-text-muted`
    - `public/js/modules/programacion_intermedia/hot.js:2829` → `--aia-warning-soft-bg` y
      `--aia-warning-border` (dos en la misma línea)
    - `public/js/modules/programa_actualizar/hot_actualizar.js:1266` → `--aia-red-primary`
    - `public/js/modules/programa_actualizar/hot_actualizar.js:1374` → `--aia-text-muted`
  - Búsqueda de cada uno en `public/css/` (`grep -rn -- "--<token>:" public/css/` y una pasada sin
    ancla por si el nombre variaba): **ninguno de los cuatro nombres existe** —
    `--aia-text-muted`, `--aia-warning-soft-bg`, `--aia-warning-border` y `--aia-red-primary` no
    aparecen definidos en ningún archivo de `public/css/`. Solo el token de la línea vecina sin
    reserva, `--aia-green-primary`, sí está definido (`public/css/tokens.css:7`,
    `oklch(32% 0.07 148.5)`).
  - Es decir: la asimetría original entre `:1374` (con reserva) y `:1377` (sin reserva) **no era
    arbitraria** — la línea con reserva usa un token que de verdad no existe, y la reserva es lo
    único que le pone color hoy. Lo mismo pasa en los otros tres consumos con reserva.
- **Opciones:**
  - **(a) Dejar las cinco líneas como están.** Es lo seguro: nadie pierde color. La asimetría de
    estilo queda, pero documentada, y ya no es un misterio — tiene una causa medida.
  - **(b) Definir los cuatro tokens que faltan** (`--aia-text-muted`, `--aia-warning-soft-bg`,
    `--aia-warning-border`, `--aia-red-primary`) en `public/css/tokens.css` con un valor equivalente
    al hex que hoy los sustituye, y **entonces sí** quitar la reserva en las cinco líneas. Alinea el
    JS con la política del repo sin cambiar ningún color, pero es una tarea de sistema de diseño (dar
    de alta tokens, decidir su valor definitivo, ver si otras superficies ya los necesitan), no un
    refactor de una línea.
  - **(c) Quitar la reserva igual, aceptando el cambio de color a lo que el navegador resuelva por
    defecto** para una variable CSS no definida (que normalmente es `unset`/heredado, no un color
    visible). Descartada: es exactamente lo que la tarea pedía evitar.
- **Recomendación:** **(a)** para esta tarea. **(b)** es la solución de fondo y probablemente valga
  la pena — cuatro tokens usados como reserva-sin-definición es una señal de que faltan en el sistema
  de tokens, no solo en estas cinco líneas — pero es un frente propio con su propio plan, no algo que
  decidir de paso en un refactor de formato.
- **Qué quedó saltado esperando:** no se tocó ninguna de las cinco líneas con reserva. `hot.js` y
  `hot_actualizar.js` quedan sin cambios de esta tarea.
- **Estado:** `resuelta 2026-08-11: definirlos como tokens de verdad, con el valor que hoy tiene su reserva en hexadecimal. Nada cambia en pantalla y el código deja de citar tokens inexistentes. Vino de vuelta al usuario porque ninguno de los cuatro tenía sustituto que conservara el color exacto, y su condición era no mover píxeles.`

### D-F1-4 · Programa General y Programación Semanal tampoco marcan una acción primaria

- **Quién pregunta:** sesión de ejecución del Frente 1, tanda 1C, Task 4.
- **Fecha:** 2026-08-11
- **Qué se decide:** si a las toolbars de Programa General (`/programa-general/actualizar`) y
  Programación Semanal (`/programacion-semanal`) también les conviene declarar una acción primaria,
  como ya se hizo en esta tarea con «Restricción Compartida» de Programación Intermedia por decisión
  explícita del usuario. La ficha original del ICE 180 daba por hecho que «en las otras tres toolbars
  se pudo señalar cuál es la principal» — **eso es falso hoy**, medido al aplicar el cambio.
- **Qué se midió** (dark, código actual, sin tocar ninguno de los dos archivos):
  - **Programa General** (`views/programa-general-actualizar/programaGeneralActualizar.view.php:98-110`,
    `.pg-toolbar-buttons`): **4 botones**. `btn_cargarCronogramaExcel` («Cargar desde Excel») ya lleva
    `aia-btn-primary` (variante guion simple, la misma familia que «Actualizar Cronograma»); los otros
    tres (`btn_eliminarActualizacion`, `btn_toggleFiltroMapeo`, `btn_autoAsociar`) llevan
    `aia-btn-ghost`. Es decir, **Programa General ya tiene una acción marcada como distinta** de las
    demás — no es una toolbar plana como PI lo era. El candidato razonable a «primaria real» sigue
    siendo «Cargar desde Excel»: es la acción que dispara el flujo completo del módulo (las otras tres
    son eliminar, alternar vista y auto-asociar, todas de apoyo).
  - **Programación Semanal** (`views/programacion-semanal/programacion_semanal.view.php:70-110`,
    `.ps-hot-toolbar-actions`, excluyendo el botón de colapso móvil): **7 controles visibles en
    desktop** — `btn_autoprogramar`, `btn_agregar_actividad`, `btn_cerrar_compromisos_semana`,
    `btn_reabrir_semana` (oculto en runtime normal), `btn_tnp` (ídem), `btn-refresh`, el enlace BI
    Semanal, más dos disparadores de dropdown («Más» y «Ver Secciones»). De estos,
    `btn_cerrar_compromisos_semana` («Confirmar Compromisos») **ya lleva `class="aia-btn"` sin
    `--secondary`**, así que hoy ya renderiza con el estilo relleno/primario de `core.css` — es, de
    hecho, el único botón de las tres toolbars que ya se comporta como «primaria» sin que esta tarea
    lo tocara. Los otros seis controles de la barra sí son `aia-btn--secondary`.
  - Conclusión de la medición: la premisa de la ficha («las otras tres ya señalan la suya») es
    correcta a medias — Programa General y Programación Semanal **sí tienen ya, cada una, un botón
    visualmente distinto del resto** (por variante de clase, no por decisión de jerarquía explícita
    documentada), pero Programación Intermedia no tenía ninguno hasta este cambio.
- **Opciones:**
  - **(a) No tocar nada.** PG y PS ya tienen, de hecho, un botón que destaca (aunque llegó ahí por
    variantes de clase preexistentes, no por un rediseño de jerarquía). Aceptar eso como suficiente.
  - **(b) Formalizar la jerarquía en las dos**: migrar `aia-btn-primary` (PG) y el `aia-btn` pelado de
    PS a `aia-btn--primary` (BEM) para que ambas usen la misma convención que PI y evitar la
    convivencia de variantes documentada en `D-F1-`(hallazgo de Task 4, ver informe) sin cambiar el
    botón elegido.
  - **(c) Revisar si el botón que hoy destaca en cada una (`Cargar desde Excel` en PG, `Confirmar
    Compromisos` en PS) es de verdad la acción primaria correcta**, en vez de asumir que la variante
    de clase heredada acertó por accidente.
- **Recomendación:** ninguna — es criterio de negocio sobre cuál debe ser la acción primaria de cada
  pantalla, no algo que esta tarea deba decidir. Sí se anota que **(b)** es de bajo riesgo si se
  decide seguir: no cambia qué botón destaca, solo unifica la clase.
- **Qué quedó saltado esperando:** no se tocó ni `programaGeneralActualizar.view.php` ni
  `programacion_semanal.view.php`. El cambio de esta tarea se limitó a Programación Intermedia.
- **Estado:** `resuelta 2026-08-11: el usuario eligió sin darle vueltas — «Confirmar Compromisos» en
  Programación Semanal y «Actualizar Ejecución» en Programa General pasan a aia-btn--primary (BEM),
  quitando aia-btn--secondary donde existía. Verificado que ambas siguen funcionando (render con
  clases y estilo primario aplicado, sin error de consola). Contraste medido en /programa-general
  (proyecto Da Porto): texto sobre botón rgb(20,28,24) contra rgb(108,144,119) → 4,87:1 (1.4.3, piso
  4.5, pasa); botón contra fondo rgb(108,144,119) contra rgba(28,36,31,0.92) → 4,46:1 (1.4.11, piso
  3, pasa). Mismos valores de token en ambos botones, misma clase.`

### D-F1-5 · El hueco reservado para el botón flotante no llega a cubrirlo del todo

- **Quién pregunta:** sesión de ejecución del Frente 1, tanda 1C, Task 5.
- **Fecha:** 2026-08-11
- **Qué se decide:** si conviene ampliar el token de espacio del sistema (crear `--ds-space-16` u
  otro mayor) para que el hueco reservado al final de las tablas Handsontable cubra por completo al
  botón flotante `.lps-sidebar-trigger`, o si el solape residual medido es aceptable.
- **Qué se midió** (dark, 1180×820, `public/css/handsontable-module.css:670-693`):
  - El botón mide 50px y se posa a 20px del borde inferior: el hueco necesario es de al menos 70px.
  - `public/css/tokens.css` no define `--ds-space-16`; el mayor token de espacio existente es
    `--ds-space-12` (`3rem` = 48px, `tokens.css:459`). Se usó ese, documentado en el propio CSS
    (líneas 671-683 de `handsontable-module.css`).
  - Con `--ds-space-12` aplicado sobre `.wtHider` (con `box-sizing: border-box !important` forzado en
    `.wtHolder` para que el padding no infle también el alto visible — ver el comentario en el propio
    archivo), el `scrollHeight` de `.wtHolder` crece en 48px de forma consistente en ambos módulos
    medidos. El solape se **reduce pero no se elimina**:
    - `/programa-general` (proyecto "Da Porto", 29 filas): antes, última fila
      `{top: 683.58, bottom: 814.58}` vs botón `{top: 750, bottom: 800}` → 50px de solape. Después,
      última fila `{top: 635.58, bottom: 766.58}` → solape residual de **16.6px** (750 a 766.58).
    - `/programacion-semanal` (proyecto "Optimización Aeropuerto JMC", 9 filas): antes, última fila
      `{top: 725.8, bottom: 814.8}` vs botón `{top: 750, bottom: 800}` → 50px de solape (columna
      «Acciones» en el mismo extremo que señalaba la ficha del Task 5, confirmado que **sí existía**).
      Después, última fila `{top: 677.8, bottom: 766.8}` → solape residual de **16.8px**.
- **Opciones:**
  - **(a) Aceptar el solape residual (~17px)** como mejora suficiente: bajó de 50px a 17px, el botón
    ya no cubre el valor completo de la celda, solo roza su borde inferior.
  - **(b) Crear un token de espacio mayor** (p. ej. `--ds-space-16` = 4rem/64px, o uno específico
    para este caso) en `tokens.css` y usarlo aquí, para eliminar el solape del todo.
  - **(c) Combinar dos tokens** (p. ej. `--ds-space-12` + `--ds-space-4`) en la propiedad, aunque esto
    se aparta de "un token, no un número suelto" que pide la ficha.
- **Recomendación:** (b) si el equipo de diseño ya tiene planeado ampliar la escala de espaciado;
  si no, (a) es razonable como cierre de esta tarea — el solape pasó de cubrir el valor completo de
  la celda a rozar apenas su borde.
- **Qué quedó saltado esperando:** no se creó ningún token nuevo ni se tocó `tokens.css`.
- **Estado:** `resuelta 2026-08-11: (b) — se añadió --ds-space-18 (4.5rem/72px) a tokens.css, que
  respeta la progresión N*4px de la escala (72/4=18) y llega al piso de 70px. Aplicado al
  padding-block-end de #hot-container .wtHolder. Solape vuelto a medir con getBoundingClientRect():
  /programa-general (Da Porto, 34 filas) gap de 7,42px entre última fila y el botón flotante;
  /programacion-semanal (proyecto Prueba, 15 filas) gap de 7,20px. overlap: false en ambas — cero
  solape, cierra el ICE 216.`

### D-F1-6 · La cascada necesita una decisión de forma, no más reparaciones

- **Quién pregunta:** sesión de ejecución del Frente 1, al cerrarlo (fase 9, `steve-jobs-design-review`).
- **Fecha:** 2026-08-11
- **Qué se decide:** los 28 arreglos del Frente 1 subieron el suelo de cada pantalla, pero **no
  cambiaron la forma del recorrido**. La pregunta es qué hacer con eso: ¿se abre un frente para
  **acortar y quitar**, o se acepta la cascada como está y el programa sigue al móvil?
- **Qué se midió** (revisión en frío sobre `66facd23`, 1180×820 dark, Da Porto, por la puerta de
  servicio). Veredicto **6/10 — «NO ESTÁ HECHO»**; pasan 4 de 7 filas y fallan tres, todas de forma:
  - **Camino hasta el primer dato útil: 13 pasos, 4 pantallas y una semana de calendario.** Los 28
    arreglos **no quitaron ni uno**.
  - **No se quitó nada en todo el ciclo.** Los 28 son **28 adiciones** — un chip, una etiqueta, un
    sello, un contador, un anuncio. Un ciclo entero sin una sola resta.
  - **La página `404` sigue devolviendo 52 bytes sin un solo enlace.** Era la **única fila
    bloqueante** del roadmap del 2026-08-05 y pasaron 28 arreglos por encima sin tocarla.
  - Densidad medida: **49 controles por encima del dato**, y **18 de los 21 chips leían `(0)` a la
    vez**. Programación Intermedia tiene **20 controles sobre una fila de datos**.
  - Conclusión textual de la revisión: **«lista de parches bien hechos», no producto.** Y los parches
    son buenos —arreglados en el sitio correcto, con tres fichas cerradas midiendo que el defecto ni
    existía—; lo que no cambió es la **forma**.
- **Cómo se ve, sin jerga:** es una casa a la que se le lijaron y barnizaron todas las puertas sin
  cambiar que para llegar a la cocina hay que pasar por cuatro habitaciones. Cada puerta abre mejor
  que antes. El camino es el mismo.
- **Opciones:**
  - **(a) Un frente corto de forma, antes del móvil.** Tres cosas concretas y acotadas: las páginas
    de error dentro del shell (dos plantillas sobre el shell que ya existe), unificar los tres
    vocabularios de estado de la cascada, y **una lista de qué se quita**. Retrasa el móvil.
  - **(b) Meterlo dentro del Frente 2 (móvil).** El móvil ya reescribe cómo se presentan esas
    pantallas, así que decidir la forma ahí evita hacerlo dos veces. Riesgo: el móvil tiene su propio
    alcance y esto puede diluirse en él.
  - **(c) Aceptar la cascada como está y seguir.** Defendible: el suelo subió de verdad y el
    despliegue lleva 1.255 commits de retraso. Coste: la próxima revisión en frío dirá lo mismo.
  - **Sea cual sea, lo barato y sin discusión es la `404`**: dos plantillas dentro del shell que ya
    existe. Puede hacerse en cualquiera de las tres.
- **Recomendación:** **(a), pero muy corto**, y con la regla que la propia revisión echa en falta:
  **que el frente no pueda cerrarse sin haber quitado algo.** Un ciclo entero sin una sola resta es
  el hallazgo, no el síntoma. Si hay que elegir una sola cosa, la `404`: es barata, está declarada
  bloqueante desde agosto, y es la única que hoy echa al usuario fuera del producto.
- **Qué quedó saltado esperando:** cinco hallazgos del backlog quedan con dueño **«pendiente de
  `D-F1-6`»** — el acuse de Intermedia que nunca aparece, el estado vacío de Actualizar Cronograma,
  el selector de semana duplicado, los tres vocabularios de chips y el alto máximo de fila. Ninguno
  bloquea al Frente 1b.
- **Estado:** `resuelta` — 2026-08-11, por el usuario, en la tanda de cierre del Frente 1b.
- **Decisión: (a), el frente corto de forma, con la regla de quitar.** El frente **no puede cerrarse
  sin haber eliminado algo**. Esa regla es la decisión, no un adorno de ella: el hallazgo de la
  revisión no fue que faltaran arreglos, sino que **un ciclo entero produjo 28 adiciones y ninguna
  resta**. Un frente de forma que solo añadiera orden repetiría el defecto que viene a corregir.
- **Quién lo ejecuta:** turno propio, **no se pliega en el Frente 2**. Se abre cuando el Frente 1b
  publique, según el gate bloqueante de `AGENTS.md` §Publicación.
- **Qué se desbloquea:** los cinco hallazgos del backlog que esperaban con dueño «pendiente de
  `D-F1-6`» pasan a ese frente. La `404` entra como la primera de la lista: barata, declarada
  bloqueante desde el 2026-08-05, y la única que hoy echa al usuario fuera del producto.

### D-F1-7 · Hasta dónde llega el encargo de una sesión de ejecución

- **Quién pregunta:** sesión de ejecución del Frente 1, al cerrarlo.
- **Fecha:** 2026-08-11
- **Qué se decidió:** una sesión de ejecución **hace el frente que le asignan y se detiene ahí**. No
  encadena los siguientes por su cuenta, aunque su objetivo esté redactado como «termina el plan
  entero».
- **Por qué se preguntó:** el objetivo de la sesión pedía «cumplimiento al 100 % del spec», y el spec
  son **seis** frentes. Eso choca de frente con dos contratos del repo:
  - `docs/coordinacion-sesiones.md:23` — «la coordinadora abre la siguiente… por turnos, no en
    paralelo». Autoasignarse un frente es exactamente lo que ese contrato existe para evitar, y el
    2026-08-10 ya costó tres integraciones y un trabajo sobrescrito.
  - `AGENTS.md:88` — el Frente 5 es el despliegue a producción y «necesita su propia autorización
    explícita, siempre».
  - Y el propio reparto: «lo declara el usuario, no lo reclama nadie» (`coordinacion-sesiones.md:9`).
- **Decisión del usuario, 2026-08-11:**
  - **Alcance:** se sigue el turno. Solo lo que asigne la coordinadora.
  - **Producción:** no se toca sin una autorización aparte. Un objetivo de sesión **no** es esa
    autorización.
- **Qué significa en la práctica:** un objetivo redactado como «termina todo» **no amplía el
  encargo**. Si la condición de terminación de una sesión abarca más frentes de los que tiene
  asignados, la condición se cumple entregando **su** frente y pidiendo el siguiente — no tomándolo.
- **Estado:** `resuelta 2026-08-11: se sigue el turno; producción exige autorización aparte.`

### D-F1b-1 · `git-preservation` es un candado de un solo uso que ya se disparó

- **Quién pregunta:** sesión de ejecución del Frente 1b.
- **Fecha:** 2026-08-11
- **Qué se decide:** ese gate se **retira** de la lista de cierre, o se **rediseña** para medir otra cosa.
- **Qué se midió** (sobre `b313de3f`, `npm run test:design-system:preservation` → **RC=1**): la salida
  es `Worktree preservation: FAIL`, con `unstaged changed`, `status changed`,
  `ignoredControlSurfaces changed` y `classification does not cover the current status exactly once`.
  Compara contra el snapshot del arranque del **Sprint 00**, a más de **1.300 commits** de HEAD.
  Declara `passed` con fecha del 2026-07-15.
- **En simple:** es una foto del día uno usada como examen permanente. Cada commit que se hace lo
  aleja más de pasar, y ningún cierre futuro podrá aprobarlo tal como está.
- **Opciones:**
  - **(a) Retirarlo**, con su motivo escrito en el índice de gates. Un gate que ningún cierre puede
    pasar no informa: solo obliga a ignorarlo, y un gate que se ignora enseña a ignorar los demás.
  - **(b) Rediseñarlo** para que compare contra el cierre **anterior** en vez de contra el Sprint 00.
    Mide algo real —qué se perdió entre dos cierres— pero es un gate nuevo, no una reparación.
  - **(c) Dejarlo rojo y documentado.** Lo desaconsejo: es la vía por la que quince gates acabaron
    declarando `passed` sin que nadie los ejecutara.
- **Recomendación:** **(a)**, y anotar (b) como candidato para más adelante. Retirar con motivo es
  honesto; conservar un candado imposible es lo que produjo el cierre que se avalaba a sí mismo.
- **Qué quedó saltado esperando:** solo este gate. Los demás siguen su curso.
- **Estado:** `resuelta 2026-08-11: (a) — retirado del índice, con su motivo escrito en
  docs/design-system/gates-cierre-frente-1b.md. Candidato (b), rediseñarlo contra el
  cierre anterior en vez del Sprint 00, queda anotado para más adelante.`

### D-F1b-2 · Tres gates distintos ejecutan exactamente el mismo comando

- **Quién pregunta:** sesión de ejecución del Frente 1b.
- **Fecha:** 2026-08-11
- **Qué se decide:** si `pg-roles`, `pg-persistence` y `data-restoration` reciben **objetivo propio**
  o se **funden en uno**.
- **Qué se midió** (`closeout-evidence.json` sobre `b313de3f`): los tres declaran, literalmente,
  `npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1`. Los tres constan `passed`.
- **En simple:** son tres asignaturas con el mismo examen. Sea cual sea la nota, **es la misma nota
  tres veces**: no pueden dar veredictos distintos porque no miden cosas distintas.
- **Opciones:**
  - **(a) Un objetivo propio a cada uno** — roles, persistencia y restauración de datos son tres
    preguntas de verdad distintas, y el spec les pide además una fixture aislada y consentimiento de
    mutación. Es el más caro y el que más cobertura da.
  - **(b) Fundirlos en un gate** llamado por lo que realmente hace, y decir qué cobertura se pierde
    al pasar de tres nombres a uno.
  - **(c) Declararlos no automatizables** y sacarlos de la lista con su motivo.
- **Recomendación:** **(a) para `pg-roles`**, que es el único cuyo objeto —qué ve cada rol— ya se
  verifica en este repo de rutina y es barato de aislar; **(b) o (c) para los otros dos**, que
  necesitan mutar datos y hoy están bloqueados en seguro por eso.
- **Qué quedó saltado esperando:** los tres gates. No bloquean a los otros doce.
- **Estado:** `resuelta 2026-08-11: (b) — fundidos en un solo gate, "full-app-flow",
  nombrado por lo que el spec ejerce (roles, persistencia y restauración en un mismo
  full-app-flow.spec.mjs). Cobertura perdida documentada en
  docs/design-system/gates-cierre-frente-1b.md: con tres gates se podía saber cuál de
  las tres dimensiones falló sin abrir el log; con uno, hay que leer la salida del spec.`

### D-F1b-3 · Cuatro gates invocan herramientas que no existen

- **Quién pregunta:** sesión de ejecución del Frente 1b.
- **Fecha:** 2026-08-11
- **Qué se decide:** qué pasa con `accessibility-insights` (uno) y `local-review` (tres:
  `consolidated-lab`, `consolidated-pilot`, `review`). **Retirarlos cambia lo que el cierre promete
  cubrir**, y eso no es decisión técnica.
- **Qué se midió** (sobre `b313de3f`): ninguno de los dos comandos es un binario del `PATH` ni un
  script del repositorio. Los cuatro gates constan `passed` con fecha del 2026-07-15, así que
  **nunca pudieron ejecutarse tal como están declarados**.
- **En simple:** cuatro de los quince exámenes citan un libro que no está en la biblioteca. Constan
  aprobados igualmente.
- **Opciones:**
  - **(a) Sustituir `accessibility-insights` por el carril de accesibilidad que sí existe** en el
    repo, y **retirar los tres `local-review`** dejando escrito que eran revisiones humanas y que su
    recibo es la aprobación de una persona, no la salida de un comando.
  - **(b) Instalar la herramienta** y cablear los tres `local-review` a algo ejecutable. Es el
    camino más caro y añade una dependencia externa al cierre.
  - **(c) Retirar los cuatro** con su motivo. El más simple y el que más cobertura declarada quita.
- **Recomendación:** **(a)**. Los tres `local-review` son de tipo `human` en el propio índice: su
  problema no es que falte la herramienta, es que un juicio humano **no debería declararse como un
  comando**. Y para accesibilidad existe carril propio, así que sustituir no pierde cobertura real.
- **Qué quedó saltado esperando:** los cuatro. El resto del frente avanza sin ellos.
- **Estado:** `resuelta 2026-08-11: (c) — los cuatro retirados del índice, con motivo
  escrito en docs/design-system/gates-cierre-frente-1b.md. Medido antes de retirar
  accessibility-insights: el repo sí cubre accesibilidad por otra vía (test:a11y:lab y
  test:a11y:pilot, con Playwright + axe, ya corren dentro del gate runtime) — no es
  "nadie lo cubre", es "lo cubre otra cosa con otro nombre", salvo la superficie
  revealed-states, que queda sin sustituto y así se deja escrito. Los tres local-review
  (consolidated-lab, consolidated-pilot, review) no tienen sustituto: era revisión
  humana declarada como comando, y esa ausencia queda visible, no disimulada.`

### D-F1b-4 · Los 6 errores que quedan de `phpstan-global` viven en el archivo más delicado del repo

- **Quién pregunta:** sesión de ejecución del Frente 1b.
- **Fecha:** 2026-08-11
- **Qué se decide:** cómo se pone verde `phpstan-global`: **arreglando** los seis con prueba propia,
  o **anotándolos en el baseline con su motivo escrito**, que es para lo que un baseline sirve.
- **Qué se midió** (sobre `1f1df71b`, comando canónico de `AGENTS.md`): de los 8 iniciales quedan
  **6**, repartidos así:
  - `src/Core/Database.php` **:496, :502, :955** — la capa que garantiza el aislamiento por
    `project_id` y las transacciones.
  - `src/Legacy/estado_programacion_intermedia.php:237` — legado, que `AGENTS.md` manda tocar solo
    con el cambio mínimo.
  - `src/Services/ActivityMatcherService.php:465` y `src/Services/ControlTowerService.php:2746`.
  - **Ninguno lo introdujo el Frente 1:** cero commits suyos en los cuatro archivos.
- **El dato que cambia la pregunta, y por el que esto no es trabajo mecánico:** al menos uno de los
  seis **es un falso positivo del analizador, y "arreglarlo" rompería el código**.
  `Database.php:955` avisa «Right side of && is always false» sobre
  `$ownsTransaction && $this->pdo->inTransaction()` dentro de un `catch`. PHPStan recuerda de `:930`
  (`$ownsTransaction = !$this->pdo->inTransaction()`) que `inTransaction()` valía `false`, y **no
  modela que `beginTransaction()` de `:932` lo cambia**. En ejecución real la condición sí se cumple
  y el `rollBack()` sí ocurre. Quien tomara ese aviso al pie de la letra **quitaría el rollback de
  una transacción fallida** en la capa de datos.
- **En simple:** el analizador avisa de seis cosas en el archivo que protege que un proyecto no vea
  los datos de otro. Al menos una de esas seis es equivocada, y hacerle caso causaría el problema en
  vez de evitarlo.
- **Opciones:**
  - **(a) Anotar los seis en `phpstan-baseline.neon` con un motivo escrito por cada uno**, y dejar el
    gate verde sobre esa base. Es el uso legítimo de un baseline: deuda reconocida, no oculta.
    Barato y reversible. Riesgo: un baseline crece con facilidad y ya tuvimos una entrada caducada.
  - **(b) Arreglar los seis con prueba de comportamiento por cada uno**, empezando por descartar
    cuáles son falsos positivos. Es lo correcto de fondo, pero toca `Database.php`, y ahí un cambio
    equivocado no da error: da datos de otro proyecto o una transacción sin deshacer.
  - **(c) Mezcla: arreglar los tres que no están en `Database.php` ni en legado, y anotar los tres
    restantes** con su motivo. Reduce la superficie de riesgo a la mitad sin tocar lo peligroso.
- **Recomendación:** **(c)**, y **con la anotación de `:955` diciendo explícitamente que es un falso
  positivo y que el `rollBack` debe conservarse**. Ese comentario vale más que el arreglo: protege
  contra el próximo que pase por ahí con buena intención.
- **Qué quedó saltado esperando:** nada ya — ver el estado.
- **Estado:** `resuelta 2026-08-11: (c), y estaba ejecutada antes de que nadie la marcara.` Medido el
  2026-08-11 con el comando canónico de `AGENTS.md` sobre el HEAD del frente:
  **`[OK] No errors`** — los 6 que esta ficha censaba son **cero**. Los cerró `3d72a8df` («el gate
  phpstan-global pasa a verde — de 8 errores a 0»), con `96d194b9` y `9011c99c` antes, y el falso
  positivo de `Database.php:955` **quedó anotado en `phpstan-baseline.neon:16-19` con el aviso
  escrito de que el `rollBack` debe conservarse**, que era la parte que yo había señalado como más
  valiosa que el arreglo. `bdffe9da` movió después ese comentario «donde lee quien puede romperlo».
  Es decir: se hizo exactamente la opción (c) recomendada, y la contradicción del registro era solo
  que **nadie volvió a escribir el resultado**. Manda el código: la ficha se corrige, no al revés.
- **Qué se hizo exactamente**, recuperado del commit `61364926` del checkout principal, que quedó sin
  publicar y decía cosas que este registro no: **2 arreglados** con comportamiento verificado
  (`ActivityMatcherService:465` y `ControlTowerService:2746`) y **4 anotados con su motivo escrito**
  en `phpstan-baseline.neon` — `Database.php:955` (el falso positivo, con el aviso en mayúsculas de
  no tocarlo), `Database.php:496` y `:502` (redundancias sin efecto en la capa que garantiza el
  aislamiento por `project_id`), y `estado_programacion_intermedia.php:237` (legado, cambio mínimo
  por `AGENTS.md`).
- **Por qué el baseline creciendo de 51 a 55 entradas no es un silenciamiento:** cada entrada añadida
  lleva su razón por escrito, que es exactamente lo que la opción (c) pedía. Se auditó el 2026-08-11
  sobre `d5311fae` **precisamente por sospecha de lo contrario**, y la sospecha no se sostuvo.

### D-F1b-5 · El contrato obliga a declarar todos los gates «passed», o la suite entera se pone roja

- **Quién pregunta:** sesión de ejecución del Frente 1b, al hacer honesto el índice.
- **Fecha:** 2026-08-11
- **Qué se decide:** si el estado «activado» del design system puede convivir con gates que hoy no
  pasan, o si cualquier rojo honesto debe tumbar la suite estática entera.
- **Qué se midió, y es la causa raíz de todo este frente:** al poner `runtime`, `runtime-budgets` y
  `full-app-flow` en `blocked` —que es la verdad, medida— la suite estática pasa de RC=0 a **RC=1**,
  con `activation: gates, version and stable API must activate together`.
  - `scripts/design-system-closeout-contract.mjs:96` — `allPassed` exige que **todos** los gates
    estén `passed`.
  - `:113` — la activación exige que `allPassed`, la versión y la API estable **valgan lo mismo**.
    La versión es 1.x estable y la API está garantizada, así que en cuanto **un solo gate** deja de
    estar `passed`, los tres divergen y la suite cae.
- **Lo que eso significa, dicho sin rodeos:** el contrato **no deja declarar la verdad y seguir en
  verde**. Con ocho gates de los que tres no pasan, la única forma de tener la suite verde es
  **afirmar que los ocho pasan**. Es decir: **la razón por la que quince recibos decían `passed` sin
  haberse ejecutado no era solo descuido — era la única salida que el contrato dejaba abierta.**
- **Y el propio contrato se contradice**, lo que hace la decisión más fácil: `:122-124` documenta que
  «la activación del design system fue un hito **ÚNICO**, cumplido en 1.0.0 (D2 del spec
  2026-08-04). A partir de ahí el sistema no se "reactiva" en cada versión». Si la activación es un
  hito histórico ya cumplido, **no debería depender de que todos los gates estén verdes hoy**.
- **Opciones:**
  - **(a) Desacoplar la activación del estado de hoy.** «Activado» pasa a significar lo que su propio
    comentario dice —un hito alcanzado en 1.0.0—, y el estado diario de los gates se informa aparte.
    Un gate rojo pondría rojo **su** carril, no la activación entera.
  - **(b) Dejarlo como está y no declarar `blocked` nunca.** Es el estado anterior, y es el que
    produjo quince `passed` falsos. No lo recomiendo, pero hay que nombrarlo para descartarlo a
    propósito.
  - **(c) Arreglar o retirar los tres rojos hasta que los ocho pasen de verdad**, y solo entonces
    publicar. Es lo más limpio de fondo, pero `runtime` depende de una decisión de producto abierta,
    y `full-app-flow` de infraestructura: el frente quedaría bloqueado hasta las dos.
- **Recomendación:** **(a)**, y con urgencia, porque hasta que se decida **el repositorio premia
  mentir**: cualquier sesión que mida un gate y escriba la verdad verá su suite en rojo, y la salida
  cómoda será volver a poner `passed`. Es exactamente el incentivo que creó el problema.
- **Qué quedó saltado esperando:** la **publicación** del índice honesto. El trabajo está hecho y
  commiteado en la rama, pero **no se publica en rojo** — hacerlo sería lo mismo que este programa
  vino a desmontar. Los ocho recibos ya son reales y el techo de recibos sin migrar está en cero.
- **Estado:** `resuelta` — 2026-08-11, opción **(a)**, ratificada por el usuario.
- **Cómo se decidió, que importa tanto como la decisión:** se ejecutó y publicó (`ef06e5b2`) por un
  **canal equivocado** — la sesión coordinadora de **otro repositorio** (`visor-gantt`) estuvo
  dirigiendo a la sesión del Frente 1b, que tomó sus indicaciones por buenas. El usuario lo detectó
  y corrigió el reparto el 2026-08-11; la propia sesión del Frente 1b lo levantó después y lo
  devolvió para ratificación en vez de darlo por hecho.
- **Qué decidió el usuario al respecto:** **ratificarla**, sin revertir. El trabajo se sostiene solo
  —el contrato se contradecía a sí mismo, documentando la activación como hito único de 1.0.0 y
  atándola a la vez al estado diario de los gates— y se entregó con su mutación en rojo ejecutada.
  Se ratifica el resultado y se deja escrito quién la decidió de verdad, que es lo que faltaba.
- **Qué se consiguió, medido:** el índice declara hoy **5 `passed` y 3 `blocked`** y la suite
  estática sigue en **RC=0**. Antes eso era imposible: declarar un solo gate honesto tumbaba la
  suite entera. Es el fin del incentivo que creó los quince `passed` falsos.

### D-RES-1 · `--aia-green` no existe: su reserva es lo único que pinta el hover del disparador LPS

- **Quién pregunta:** sesión de ejecución del frente «reservas contradictorias» (a187ccda).
- **Fecha:** 2026-08-11.
- **Qué se decide:** qué hacer con `public/css/handsontable-module.css:777`,
  `background: var(--aia-green, oklch(43.86% 0.084 142.5))` — el estado hover del botón circular que
  abre el cajón LPS. Es el único caso de los siete medidos que queda sin resolver.
- **Qué se midió** (en vivo, `/programacion-semanal`, 1180×820, tema dark, cuenta `test.A`):
  - `getComputedStyle(document.documentElement).getPropertyValue('--aia-green')` devuelve **cadena
    vacía**: el token no está definido en ningún archivo de `public/css/`. La reserva es el color
    real del hover, no una duplicación.
  - No es lo mismo que el token vecino `--aia-green-dark`, que sí existe
    (`public/css/tokens.css:6`, `oklch(27.8% 0.05 147.1)`) y es el fondo en reposo del mismo botón.
  - El otro token inexistente del archivo, `--hot-table-width` (líneas 155–156), **no es un caso
    equivalente y no entra aquí**: lo fija JavaScript en tiempo de ejecución sobre el contenedor
    (`public/js/modules/aia_ui/hot_table_width.js:66` y cuatro módulos más), y su reserva `100%` es
    el valor correcto mientras no se haya medido el ancho. Ahí la reserva no miente: está bien y se
    deja.
- **Opciones:**
  - **(a) Dejar la línea como está.** Cero riesgo, nada cambia en pantalla. El código sigue citando
    un token que no existe, que es justo lo que este programa desmonta.
  - **(b) Dar de alta `--aia-green` en `public/css/tokens.css`** con el valor que hoy tiene su
    reserva (`oklch(43.86% 0.084 142.5)`) y quitar la reserva. Es el mismo criterio que el usuario
    ya eligió en `D-F1-3`: definir el token con el valor de su reserva, sin mover un píxel. Pero dar
    de alta un token es tocar el sistema de diseño, y hay que decidir si `--aia-green` es un nombre
    que el sistema quiere (ya existen `--aia-green-primary`, `--aia-green-dark`, `--aia-green-light`,
    `--aia-green-medium`; un `--aia-green` a secas puede sobrar o pedir otro nombre).
- **Recomendación:** **(b)**, por coherencia con `D-F1-3`, pero decidiendo antes el nombre: lo más
  probable es que el hover deba apuntar a `--aia-green-medium` o `--aia-green-primary` en vez de
  crear un nombre nuevo. Eso sí movería el color, así que necesita el visto del usuario.
- **Qué quedó saltado esperando:** la línea 777 no se tocó. Tampoco las 155–156.
- **Estado:** `resuelta 2026-08-11: el foco lo señala el anillo, no el relleno; el relleno pasa a
  --aia-green-primary. Aplicado en el frente focus-visible-verde.`

#### Cómo se resolvió, y por qué la primera respuesta era la equivocada

La primera vuelta eligió `--aia-green-medium` (52%) sobre una premisa que resultó falsa: que ese
verde **era** el indicador de foco de teclado. No lo es. Medido con `Tab` real a 1180×820 en dark, el
foco lo señala el **anillo teal del sistema** —`outline: 2px solid var(--ds-active-focus-ring)`
(`#2caa9f`) más `box-shadow` de 4px, en `public/css/design-system/adapters/lps-drawer.css:182`,
compartido con todo el shell LPS—. El verde de `:777` es el **relleno** del botón, y su regla está
compartida con `:hover`, no es exclusiva del foco.

Eso invierte el criterio. Si el anillo carga la señal, el relleno debe **apartarse** del anillo, no
acercársele. Aclarar el verde hacía lo contrario. Las tres candidatas, medidas con la misma fórmula
WCAG sobre sRGB:

| | anillo vs relleno (**la que manda**, 3:1) | relleno vs fondo (3:1) | enfocado vs reposo (3:1) | icono blanco (4,5:1) |
|---|---|---|---|---|
| `--aia-green-primary` (32%) | **4,34** ✅ | 1,44 | 1,17 | 12,37 |
| verde suelto de hoy (43,86%) | 2,63 | 2,37 | 1,92 | 7,5 |
| `--aia-green-medium` (52%) | 1,86 | 3,35 | 2,72 | 5,3 |

`--aia-green-primary` es la única que cruza el umbral en la fila que gobierna, y con holgura. Se
aplica esa.

**El coste, dicho sin adornos:** con este verde el relleno enfocado queda casi indistinguible del de
reposo (1,17). Es coherente con la decisión —la señal la lleva el anillo— pero significa que el
**hover con ratón**, que no tiene anillo, se queda sin apenas cambio de color y se apoya en el
`scale(1.08)` y la sombra. Si alguien decide más adelante que el hover necesita señal cromática
propia, la salida es darle su propia regla, no volver a aclarar la compartida.

**Y se retira la reserva:** `--aia-green-primary` sí existe (`public/css/tokens.css:7`), así que la
declaración queda `var(--aia-green-primary)` sin reserva, en línea con el criterio del frente
anterior. Con ello desaparece el último literal de color suelto de esta regla.

### D-FORMA-1 · La lista de qué se quita: cuatro candidatos medidos, y ninguno lo puede decidir el ejecutor

- **Quién pregunta:** sesión de ejecución del frente corto de forma (`forma-quitar-pasos`, a187ccda).
- **Fecha:** 2026-08-11.
- **Qué se decide:** cuál de los cuatro candidatos medidos se quita. Es la **tercera pieza** del
  alcance de `D-F1-6` —«una lista explícita de qué se quita»— y la que hace que el frente pueda
  cerrarse, porque su condición es que **no cierra sin haber eliminado algo**.
- **El problema, dicho claro:** la regla obliga a quitar, pero **los cuatro candidatos que la
  medición encuentra están marcados en el backlog como decisión de usuario o de navegación**. El
  ejecutor no puede resolverlo sin invadir eso, y quitar «algo» solo para cumplir la regla sería
  peor que no quitar nada: convertiría una decisión de producto en un trámite.

**Qué se midió** (en vivo, `/programacion-intermedia`, 1180×820, dark, `test.A`, sirviendo el
worktree en un contenedor propio):

| Candidato | Medida | Quién decide, y por qué |
|---|---|---|
| **Chips en cero** | 8 chips de estado, **7 leen `(0)`**. Solo «Listo para Comprometer (1)» tiene dato | **Usuario.** Un chip en cero no es ruido puro: dice que la categoría existe y está vacía. Quitarlos u ocultarlos cambia qué se aprende de un vistazo |
| **Selector de semana repetido** | **4** elementos `.shell-week-flyout`, uno por ítem de navegación con submenú, más `ctxWeekMenu` en la cabecera: **5 sitios** para cambiar de semana, y 16 botones «Semana N» en el DOM para 4 semanas | **Navegación.** No es un fallo: cada flyout cuelga de su propio ítem. Unificarlos quita capacidad a tres de ellos, y el del carril tiene «+ Nueva semana», que los otros no |
| **Término duplicado en pantalla** | «Listo para Comprometer (1)» como chip **y** «Listo para comprometer» como leyenda, a la vez | **Usuario**, aunque es el más barato: dos rótulos para lo mismo en la misma pantalla |
| **Tres vocabularios de estado** | 21 términos para el mismo ciclo (7 en PG, 9 en PI, 5 en PS) | **Usuario, ya declarado así en el backlog:** toca vocabulario compartido con la obra (`GLOSARIO.md`) |

- **Opciones:**
  - **(a) Quitar el término duplicado** (chip y leyenda dicen lo mismo). Es el más barato, no toca
    vocabulario ni navegación, y cumple la regla con una resta real y visible.
  - **(b) Ocultar los chips que leen `(0)`.** El de mayor efecto sobre la densidad —quita 7 de 8
    controles de esa fila— pero cambia qué se aprende de un vistazo, y es reversible.
  - **(c) Unificar los cinco sitios donde se cambia de semana.** El de más peso de mantenimiento y
    el más arriesgado: toca el shell compartido por todas las pantallas.
  - **(d) Declarar que la primera pieza ya cumple la regla.** Las páginas de error quitaron código
    muerto —la variable `$allowedMethods` sin usar y el `<pre>` de la excepción sobre un `<h1>`
    pelado—. Defendible, pero **no es una resta del recorrido**, que es lo que la revisión señalaba:
    el usuario sigue dando los mismos 13 pasos.
- **Recomendación:** **(a) y (b) juntas**, en ese orden. (a) es gratis y no discute con nadie. (b) es
  la que de verdad mueve la densidad, y su riesgo es bajo porque se deshace cambiando una línea. (c)
  merece frente propio, no la cola de este. **(d) no**, y conviene decirlo: aceptar código muerto
  como «la resta» vacía la regla justo en su primera aplicación.
- **Qué quedó saltado esperando:** los cuatro candidatos siguen en pie, sin tocar. La primera pieza
  del frente —las páginas de error— sí está hecha y commiteada; no depende de esta decisión.
- **Estado:** `resuelta 2026-08-11: las tres primeras salen a frentes propios con spec y plan; la
  cuarta no se toca porque la premisa era falsa. Este frente cierra como «medir y repartir».`

#### Cómo se resolvió

| Candidato | Destino |
|---|---|
| Término duplicado (chip y leyenda) | **Frente propio** |
| Contadores en cero (7 de 8 chips) | **Frente propio** |
| Los 21 términos / tres vocabularios | **Frente propio**, el grande |
| Selector de semana | **No se toca** — ver abajo |

**El selector de semana no tenía nada que quitar, y la premisa venía mal desde el backlog.** El
backlog lo describía como «dos menús que rinden las mismas semanas». Esta sesión midió que eran
cuatro flyouts más el de cabecera, y **también se quedó corta**. Verificado sobre
`views/partials/shell_sidebar.php`, son **tres cosas distintas que se parecen por fuera**:

- **`:140`, `ctxWeekMenu`** — la pastilla «Semana N» de la barra superior. Dice **dónde estás**.
- **`:258`** — un bucle sobre los módulos que crea un flyout **por módulo**, rotulado «Semanas de
  {módulo}», con las semanas **de ese módulo**.
- **`:317`** — el que cuelga de «Semanas del Proyecto». Es el de **gestión**: incluye el botón de
  crear, bajo `$shellCanCreate`.

No hay duplicados. Ni siquiera es «arriesgado unificarlos»: es que no había nada que unificar. Si un
selector por módulo aporta o no es **rediseño de navegación**, no limpieza, y no entra aquí.

#### La condición de resta se cumple fuera de este frente, no dentro

`D-F1-6` exigía que el frente **no cerrara sin haber eliminado algo**. Con las tres candidatas
derivadas a frentes propios, **este frente no elimina nada del recorrido**: entrega las páginas de
error, que **añaden**, y esta lista, que mide. La regla **no se relajó: se trasladó** — cada frente
derivado nace con ella dentro, con el conteo antes/después como resultado, así que habrá tres restas
medidas en vez de una.

Queda dicho sin maquillar, porque escribirlo como si este frente hubiera quitado algo vaciaría la
regla por la vía de la redacción, que es la más difícil de detectar. **Lo que no cuenta como resta:**
el código muerto que sí quitó la pieza 1 —la variable `$allowedMethods` sin usar y el `<pre>` de la
excepción sobre un `<h1>` pelado—. Borrar código muerto no le quita **ni un paso** al usuario, y el
hallazgo de la revisión en frío era que sigue dando los mismos 13.

---

### D-VOC-1 · ¿Un vocabulario único para toda la cascada, o tres explícitamente distintos?

- **Quién pregunta:** sesión de ejecución del frente `vocabulario-estados-cascada`.
- **Fecha:** 2026-08-11
- **Qué se decide:** si los tres vocabularios se funden en uno solo que atraviese Programa General,
  Intermedia y Semanal, o si cada eslabón nombra un eje distinto (avance / alistamiento / compromiso)
  y lo que se unifica es solo la **forma** de nombrarlos.
- **Qué se midió** (sobre `de02471a`, contrato `docs/design-system/state-semantics.json` + literales
  del código): **35 cadenas distintas** en los cuatro módulos. Siete momentos del ciclo se nombran
  dos o tres veces — «le toca y no puede arrancar» es `Debe Iniciar` (PG), `Inicio por Habilitar`
  (PI) y `Condiciones Pendientes` (PS).
- **Opciones:**
  - **(a) Vocabulario único de ~7 estados** para toda la cascada. Máxima reducción (35 → ~10).
    Cambia el idioma de tres pantallas a la vez para la obra.
  - **(b) Tres ejes declarados, un vocabulario por eje**, sin solapes. Reducción media; cada
    pantalla sigue hablando de lo suyo y desaparecen los sinónimos dentro de cada eje.
  - **(c) Solo higiene**: cerrar desviaciones y quitar duplicados, sin tocar nombres aprendidos.
- **Recomendación:** **(b)**. Los tres ejes son reales en LPS —avance físico, liberación de
  restricciones y confiabilidad del compromiso son cosas distintas, y `GLOSARIO.md` las distingue—,
  así que fundirlos perdería información. Lo que sobra no son los ejes: son los sinónimos dentro de
  cada uno.
- **Qué quedó saltado esperando:** cualquier renombrado. Solo se ejecuta la higiene que no elige
  vocabulario.
- **Estado:** `resuelta` — 2026-08-11, opción **(b)**, por el usuario.
- **Decisión: tres ejes declarados, un vocabulario por eje.** Avance físico, liberación de
  restricciones y confiabilidad del compromiso son tres cosas distintas y `GLOSARIO.md` las
  distingue; fundirlas perdería información. Lo que sobra no son los ejes, son **los sinónimos dentro
  de cada uno**.
- **Qué arrastra:** al no unificar, **`D-VOC-2` se resuelve sola en (c)** — no se renombra la columna
  persistida y **no hay migración de datos**.

### D-VOC-2 · La columna `Estado` de Programa General está persistida

- **Quién pregunta:** sesión de ejecución del frente `vocabulario-estados-cascada`.
- **Fecha:** 2026-08-11
- **Qué se decide:** si el vocabulario de Programa General puede renombrarse, dado que sus valores
  **no son solo etiquetas de pantalla**: se escriben en la base de datos en cada guardado.
- **Qué se midió** (sobre `de02471a`): `src/Legacy/guardar_programacion_intermedia.php:361` hace
  `UPDATE ... SET Semanas_Inicio = ?, Estado = ?`. Los siete valores (`Actividad Futura`, `Debe
  Iniciar`, `En Curso`, `Atrasada`, `Terminada`, `Sin Datos`, `Capítulo`) los leen además
  `LpsService`, `GeneralApiController`, `SemanalApiController`, `ProgramChangeDetector`,
  `ReportProcessor` y `tests/test_weekly_governance.php`.
- **Opciones:**
  - **(a)** Renombrar solo la etiqueta y dejar el valor guardado. Introduce una capa de traducción:
    **suma** un vocabulario en vez de restarlo.
  - **(b)** Renombrar valor y etiqueta **con migración de datos** — `UPDATE` sobre histórico, con
    dry-run, respaldo y gate de Plannotator según `docs/global-tables-architecture.md`.
  - **(c)** No tocarlo.
- **Recomendación:** **(c) en esta pasada**, y (b) como frente propio si `D-VOC-1` sale «unificar».
  La (a) está descartada: contradice la condición de cierre, que exige **menos** términos.
- **Qué quedó saltado esperando:** todo el vocabulario de Programa General.
- **Estado:** `resuelta` — 2026-08-11, opción **(c)**, por arrastre de `D-VOC-1`.
- **Decisión: no se toca el vocabulario de Programa General.** Su recomendación decía «(c) en esta
  pasada, y (b) como frente propio **si `D-VOC-1` sale unificar**». Salió «tres ejes», así que la
  condición no se cumple: la columna `Estado` de `{prog_consolidado}` **se queda como está y no hay
  migración**.

### D-VOC-3 · `Bloqueado` existe en Actualizar y no en Programa General

- **Quién pregunta:** sesión de ejecución del frente `vocabulario-estados-cascada`.
- **Fecha:** 2026-08-11
- **Qué se decide:** si `Bloqueado` se conserva o se absorbe en el `Debe Iniciar` / `Con Alerta
  Restricciones` de Programa General.
- **Qué se midió** (sobre `de02471a`): el contrato declara seis estados para
  `programa-general-actualizar`; cinco son idénticos a los de PG y el sexto es `Bloqueado`, que en PG
  no existe. **Es la única divergencia entre dos pantallas que muestran la misma tabla.**
- **Opciones:** (a) absorberlo y quedar en cinco; (b) añadirlo también a PG y tener dos listas
  iguales; (c) dejarlo.
- **Recomendación:** **(a)** — dos vistas de la misma tabla no deberían clasificar distinto. Pero es
  un término que la obra ve a diario, así que no se toca sin decisión del usuario.
- **Qué quedó saltado esperando:** la vista de Programa General Actualizar y su entrada en el
  contrato.
- **Estado:** `resuelta` — 2026-08-11, y **la premisa era falsa**.
- **El usuario corrigió la pregunta en vez de elegir opción:** «en `/programa-general-actualizar` no
  hay estados». Verificado: la vista no menciona «estado» ni una vez, el JS no contiene ninguna de las
  seis etiquetas, la única aparición de «Bloqueado» es un **título de modal**, y la única columna
  parecida (`hot_actualizar.js:717`) es un **porcentaje** (`type: 'numeric'`, `pgPercentRenderer`).
- **No eran seis estados de más: eran seis entradas fantasma.** Entraron en `3a139499` (2026-07-15) y
  **ese mismo día** la vista tampoco las pintaba. Nunca existieron.
- **Ejecutado** en el frente `contrato-estados-modulo-fantasma` (`c25ac63d`): el módulo sale del
  contrato y la aserción del censo pasa de suelo (`>= 13`) a **igualdad (`=== 12`)**. Censo del
  vocabulario **29 → 28** — la resta es **una**, `Bloqueado`, porque las otras cinco etiquetas ya se
  contaban por `programa-general`.
- **Lo que destapó, medido:** con el suelo antiguo restaurado y el módulo de vuelta, la suite **pasa
  en verde**. El contrato admitía módulos fantasma sin que nada lo notara. La corrección de fondo es
  **`D-CEF-1`**, que sigue abierta.

### D-VOC-4 · Tres estados que se muestran y no existen en el contrato

- **Quién pregunta:** sesión de ejecución del frente `vocabulario-estados-cascada`.
- **Fecha:** 2026-08-11
- **Qué se decide:** si `Control` (Intermedia), `Capítulo` (Intermedia y PG) y `Programada
  Manualmente` (Semanal) se declaran en el contrato o se retiran de la interfaz.
- **Qué se midió** (sobre `de02471a`): los tres se muestran al usuario y ninguno está en
  `state-semantics.json`; `ops-state-contract.test.mjs` exceptúa `neutral` a mano por eso. `Capítulo`
  además es un **valor persistido** (ver `D-VOC-2`), no un estado de interfaz.
- **Recomendación:** `Capítulo` **no es un estado, es un tipo de fila**, y mezclarlo con los estados
  es parte de por qué hay 35 términos. Separarlo sería la resta más limpia del frente — y toca datos
  guardados, así que se consulta.
- **Qué quedó saltado esperando:** los tres términos.
- **Estado:** `resuelta` — 2026-08-11, opción **(a)**, por el usuario.
- **Decisión: sí se separa `Capítulo` del eje de estado, pero en frente propio y con autorización
  aparte.** Es un **tipo de fila**, no un estado, y mezclarlo es parte de por qué hay tantos términos
  — sería la resta más limpia. Pero **está persistido en datos reales de obra**, así que exige
  migración con dry-run, respaldo verificable y gate propio según
  `docs/global-tables-architecture.md`. **No se mete dentro de otro frente.**


### D-CEF-1 · ¿El contrato de estados debe exigir que cada módulo declare su superficie?

- **Quién pregunta:** sesión de ejecución del frente `contrato-estados-modulo-fantasma`.
- **Fecha:** 2026-08-11
- **Qué se decide:** si una entrada de `moduleMappings` en
  `docs/design-system/state-semantics.json` debe declarar **dónde se pintan** sus estados —una
  ruta, una vista o un selector— de modo que un módulo que no pinta ninguno no pueda entrar en el
  contrato. Cambia el esquema para los **trece** módulos, no solo para el que este frente retira.
- **Qué se midió** (sobre `44917bc1`): el módulo `programa-general-actualizar` declaraba seis
  estados que **ninguna pantalla pinta**. La vista no menciona «estado» ni una vez
  (`grep -c "estado\|Estado" views/programa-general-actualizar/*.php` → `0`); el JS del módulo no
  contiene ninguna de las seis etiquetas; la única «Bloqueado» de la vista es el título de un modal
  (`:303`, «Programa General Bloqueado»); la única columna parecida a un estado,
  `hot_actualizar.js:717`, es `type: 'numeric'` con `pgPercentRenderer` — un porcentaje. Entraron
  en `3a139499` (2026-07-15) y **ese mismo día** la vista ya solo tenía el título de modal: no
  caducaron, se inventaron.
- **Por qué nada lo detectó:** el esquema (`state-semantics.schema.json`) es
  `additionalProperties: false` y las claves de una entrada son exactamente `module` y `states`.
  **Sin superficie declarada no hay nada contra lo que contrastar**, así que ningún gate podía
  notarlo. Lo que sí detecta el contrato es la retirada de un módulo, porque
  `states-feedback.test.mjs` fija el censo a mano: la asimetría es que se vigila la resta y no la
  suma.
- **Opciones:** (a) añadir al esquema un campo de superficie **obligatorio** por módulo y hacerlo
  cumplir en el gate estático, censando los trece; (b) hacerlo **opcional** y verificarlo solo
  donde exista, que atrapa menos pero no obliga a un censo previo; (c) no cambiar el esquema y
  aceptar que un módulo fantasma solo se descubre a mano.
- **Recomendación:** la **(a)**, pero como frente propio con su medición: cada uno de los trece
  módulos tendría que declarar su superficie, y en varios eso no es un dato que ya exista escrito
  —hay que ir a buscarlo—. Es la corrección de fondo del defecto, y por eso no se pliega dentro
  del frente que solo retira una entrada.
- **Qué quedó saltado esperando:** el esquema entero. Este frente **no lo toca ni lo insinúa**.
- **Relación con `D-VOC-3`:** aquella entrada pregunta si `Bloqueado` se conserva o se absorbe, y
  se apoya en que es «un término que la obra ve a diario». Medido sobre `44917bc1`, **no se ve en
  ninguna parte**: la pregunta se cae con la premisa. No cambio su estado —eso lo hace la
  coordinadora—, pero conviene leerlas juntas.
- **Estado:** `resuelta 2026-08-12: (a) — superficie obligatoria, y ejecutada.` El esquema exige
  `surface` (`state-semantics.schema.json`, `required: [module, surface, states]`), los **10** módulos
  vivos la declaran con su ruta medida en `public/index.php`, y el gate comprueba que **esa ruta
  existe de verdad**, no solo que el campo esté.

  **Corrección a esta ficha:** decía «los trece módulos». Eran **12** al escribirla —el
  decimotercero era el fantasma que ese mismo frente retiró— y quedan **10**.

  **Se retiran `contratos` y `listado-actividades`**, la interfaz del PDC v1 eliminado el 2026-08-04:
  `grep -c` da 0 en `public/index.php` y `shell_sidebar.php:93-96` lo dice por escrito.
  **Cobertura que se pierde, medida: 7 estados** — que no cubrían nada, porque no los pintaba nadie.
  El matiz que justifica la fase entera: dos de esas etiquetas —«Cambios guardados», «Error de
  conexión»— **sí existen** en el repo, pero en `ProfesionalesApiController`,
  `SubcontratistasApiController` y el laboratorio. **Por la etiqueta sola no se sabe de quién es un
  estado.**

  **Cuatro mutaciones ejecutadas:** (A) módulo con superficie inexistente → rojo; (B) sustituir un
  módulo real por uno inventado **manteniendo el total en 10** → rojo, nombrando al intruso; (C)
  quitarle la superficie a un módulo real → rojo; (D) lo mismo contra el validador de esquema → rojo,
  «falta el campo obligatorio surface». La **(B)** es la que vale: el censo pasó de `length === N` más
  un bucle de `includes` a un `deepEqual` del conjunto, así que ahora **cambiar qué se cuenta también
  cae**, no solo cambiar cuántos.

  Ejecutada por la coordinadora bajo la autonomía delegada, con la misma excepción registrada en
  `D-GAC-3`.


### D-BTN-1 · Un `!important` que no gana, y quizá la regla que lo pisa tampoco hace falta

- **Quién pregunta:** sesión de ejecución del frente `buttons-important-leyenda`.
- **Fecha:** 2026-08-11
- **Qué se decide:** si se investiga y se retira el par «declaración que no gana + regla que la
  pisa», o si el frente se queda en las declaraciones que sí compiten.
- **Qué se midió** (sobre `f1f5bd87`, 1180×820 dark, las tres pantallas): `public/css/buttons.css:970`
  declara `display: inline-flex !important` para `.pdc-legend-item`, y el valor **computado** en
  Programa General, Intermedia y Semanal es **`flex`**. Es decir, ese `!important` **no gana a
  nadie**. No estaba en la lista de doce sobrantes con la que se encargó el frente.
- **Opciones:**
  1. **Dejarlo anotado y seguir** (lo que se está haciendo). Seguro y reversible.
  2. **Averiguar quién gana y retirar los dos si la otra regla existe solo para vencer a esta.**
     Sería la resta más grande del frente, pero entra en la cascada de capas — el mecanismo
     `components.components` de `memoria/trampas/css-layer-cascade.md`, donde para `!important` el
     orden de capas se invierte— que este frente excluyó a propósito por riesgo.
- **Recomendación:** la **1** dentro de este frente, y la 2 como frente propio con su enunciado. Es
  tentador precisamente porque está a la vista y luce; por eso conviene que lo decida alguien que no
  esté dentro del frente.
- **Qué quedó saltado esperando:** `display` de `buttons.css:970` y quienquiera que lo esté pisando.
- **Estado:** `resuelta 2026-08-12: la opción 2 la eligió el usuario — y ya estaba ejecutada antes de
  que nadie la marcara.` Medido por la coordinadora el 2026-08-12, antes de asignar la fase, para no
  repetir el encargo caducado: **el `display: inline-flex !important` de `:970` ya no existe.** Lo
  retiró `0a228a39` (2026-08-11), verificado ancestro de `origin/main` con
  `git merge-base --is-ancestor`. Y lo hizo mejor de lo que esta ficha pedía: retiró **los dieciséis**
  del chip de golpe, midió el valor computado, **repuso los seis que sí hacen trabajo** y verificó en
  las tres pantallas a 1180 y 900 — idéntico en las 17 propiedades observadas. La lista heredada de
  «cuatro que ganan» se quedaba corta por dos (`flex-shrink` y `transition`), y **medir en una sola
  pantalla habría dejado dos regresiones fuera del radar**. Al chip le quedan seis `!important`, los
  seis medidos como necesarios.
  **Consecuencia que cierra el círculo:** tres de los diez retirados —`display`, `overflow-wrap`,
  `word-break`— eran justo los que `test_programa_general_sprint_contract.mjs:150` exigía con
  `!important`. Ese contrato se rompió **el día en que alguien hizo lo correcto**, y quedó escondido
  detrás de otros dos rojos hasta hoy. Ver `D-GAC-3`.


### D-GAC-1 · Dos contratos del repo se contradicen y llevan el CI un mes en rojo

- **Quién pregunta:** sesión de ejecución del frente `gates-al-ci` (fase F-AB), escalado el
  2026-08-12; verificado de forma independiente por la coordinadora antes de llevarlo al usuario.
- **Fecha:** 2026-08-12
- **Qué se decide:** si cede el contrato que prohíbe `!important` en la hoja de Programa General, o
  si cede el CSS que lo usa.
- **Qué se midió** (sobre `4dc4631a`, salida real de dos sesiones distintas):
  - `tests/test_programa_general_sprint_contract.mjs:41` exige
    `assert.doesNotMatch(css, /!important/)` — **sin excepción por capa**.
  - `public/css/programa-general.css:620-624` declara tres `!important` dentro de
    `@layer components`, y el comentario que los precede razona por qué: las reglas de estado viven
    en `module.components`, una capa posterior, y en declaraciones normales ganarían. Los introdujo
    `20f08dd2` (2026-08-07) como «copia literal de la receta ya validada en
    `programacion-intermedia.css`».
  - `node tests/test_programa_general_sprint_contract.mjs` → **RC=1**, mismo fallo que el log de CI.
  - **La última corrida verde del workflow es del 2026-07-17** (id `29577390968`). De las 30 últimas:
    22 `failure`, 7 `cancelled`, 0 `success`.
  - `design-system-runtime` lleva `needs: design-system-static`
    (`.github/workflows/design-system.yml:51`), así que el job donde F-AB tenía que enchufar los dos
    gates **no llega a ejecutarse nunca**. El bloqueo es total, no una molestia.
  - **Corrección del 2026-08-12, error de la coordinadora:** esta ficha atribuía el rojo a «la suite
    estática». **Es falso.** `npm run test:design-system:static` da `RC=0`, 8/8, también con
    `DS_ACTIVATION_STRICT=1`. Lo que tumba el job es un paso aparte, `Enforce Programa General pilot
    contract` (`design-system.yml:46-47`). Lo midió la sesión de F-0 al llegar. La conclusión
    aguanta; la atribución no.
  - **El dato que reencuadra la pregunta:** `!important` por hoja —
    `programacion-semanal.css` **433**, `programacion-intermedia.css` **182**, `buttons.css` **138**,
    `programa-general.css` **4**. Y existe **un solo** contrato de este tipo en el repo, que aplica
    solo a PG. La prohibición no es un principio del repositorio que alguien viole: es la disciplina
    de un **piloto** chocando con una receta copiada de un módulo que tiene 182.
- **Opciones:** (a) afinar la aserción para permitir `!important` **solo dentro de `@layer`**,
  manteniendo la prohibición fuera; (b) retirar los tres y resolver la cascada sin `!important`,
  aceptando que puede cambiar lo que se ve; (c) una tanda corta que solo mida cuál de los dos
  contratos está equivocado.
- **Recomendación:** **(a)**, en su forma estrecha — no «permitir `!important` en capa» a secas, que
  abriría PG a las 400 de sus vecinas, sino exactamente la distinción que el comentario del código ya
  hace: dentro de capa se permite, fuera se prohíbe. Es lo que separa «gano la cascada donde la
  arquitectura me obliga y lo dejo escrito» de «tapo un problema a martillazos». **(b)** cambia
  píxeles —los chips en cero dejarían de atenuarse— y eso exige aprobación aparte; **(c)** mediría
  algo que ya está medido.
- **Límite reconocido de la medición:** no se comprobó **en pantalla** que sin los tres `!important`
  los chips dejen de atenuarse. Se toma del comentario del código y del mecanismo de capas, no de una
  observación en el navegador.
- **Qué quedó saltado esperando:** las Tareas 2 a 5 de F-AB. La sesión paró en la Tarea 1 con el
  worktree limpio y nada commiteado, que es lo que el plan ordenaba para este caso.
- **Estado:** `resuelta 2026-08-12: (a) en su forma estrecha — la aserción pasa a permitir
  !important dentro de @layer y a seguir prohibiéndolo fuera. Se entrega con su mutación: un
  !important sin capa tiene que poner el gate rojo, y si no lo pone, la aserción nueva no vigila nada
  y se rehace. Reordena el plan: la reparación del CI pasa a ser la fase 1 y F-AB baja a segunda.`

### D-GAC-2 · La regex de los chips de PG quedó vieja y contaba cero de catorce

- **Quién pregunta:** sesión de ejecución del frente `ci-en-verde` (fase F-0), 2026-08-12;
  confirmado de forma independiente por la coordinadora.
- **Fecha:** 2026-08-12
- **Qué se decide:** si cede la prueba o cede el markup, en el segundo rojo que apareció detrás del
  primero al resolver `D-GAC-1`.
- **Qué se midió** (sobre `f743c29b`): `tests/test_programa_general_sprint_contract.mjs:21` exige
  `class="aia-chip pg-filter-chip[^"]*"` con las dos clases **contiguas**, y el markup real es
  `class="aia-chip pdc-legend-item pg-filter-chip …"`. Los 14 chips existen (`grep -c pg-filter-chip`
  → 14); lo que da `0` es la forma contigua, y la aserción de `:101` falla con `0 !== 14`. Se rompió
  en **`47dda844` (2026-08-04)**, ancestro de `main`; el fallo del `!important` lo tapaba porque
  dispara antes en el archivo.
- **Opciones:** (a) la regex tolera clases intermedias; (b) se quita `pdc-legend-item` del markup
  para que la regex vuelva a casar; (c) frente propio.
- **Recomendación:** **(a)**. El markup no está mal — `pdc-legend-item` es la clase canónica del
  chip; la que se quedó atrás es la prueba, y lleva ocho días midiendo una forma que ya nadie
  escribe. **(b)** sería borrar la clase canónica para complacer a una expresión regular. **(c)** es
  honesto pero parte en dos un frente sobre el mismo archivo, que ya fundimos una vez en este plan.
- **Qué quedó saltado esperando:** la Tarea 3 de F-0 entera — el gate de cierre y el verde en
  Actions. Nada publicado.
- **Estado:** `resuelta 2026-08-12: (a) — la regex tolera clases intermedias; el markup no se toca.
  Se pliega en F-0 como Tarea 2b, con dos mutaciones exigidas: quitar un chip real debe dar 13≠14, y
  quitarle la clase pg-filter-chip a un chip debe dar rojo también. Cambiar el número de la aserción
  no cuenta como mutación.`

### D-GAC-3 · Tercer rojo: el contrato exige `!important` donde debería exigir el resultado

- **Quién decide:** la coordinadora, **con la autonomía delegada por el usuario el 2026-08-12**
  («termina todas las fases, tienes autonomía para decidir»), y aplicando el precedente que él ya
  fijó en `D-CI-1`. No se le devuelve porque no depende de su criterio de negocio: es la misma
  pregunta ya resuelta, en otro archivo.
- **Fecha:** 2026-08-12
- **Qué se midió** (sobre `54e5f474`, worktree `beautiful-blackwell-414f09`):
  - Al caer el segundo rojo apareció un **tercero**: `:150` exige que `.pdc-legend-item` declare
    `display`, `white-space`, `overflow-wrap` y `word-break` **con `!important`**.
  - `public/css/buttons.css:977-993` **tiene las cuatro declaraciones con sus valores correctos**;
    lo que no tienen es el `!important`. Lo retiró a propósito el frente `buttons-important-leyenda`
    el **2026-08-11**, dejando escrita en el archivo la sonda que lo justifica (`:52-58`).
  - **Cuántos rojos quedan escondidos: exactamente uno, éste.** Medido neutralizando aserciones una
    a una sobre una copia en `scratchpad/`, sin tocar el repo: **1 rota de 28**. El archivo aborta en
    la primera, así que sin esta medición cada arreglo destapaba otro y nadie sabía cuántos faltaban.
- **Qué se decide y por qué:** la aserción pasa a exigir **los valores** (`white-space: normal`,
  `overflow-wrap: normal`, `word-break: normal`) y **no el `!important`**. El objetivo declarado de
  esa aserción es que los chips no fragmenten palabras; el `!important` es el mecanismo, no el
  resultado. Es literalmente el defecto de `D-CI-1` —«el contrato fija una forma donde debería medir
  un resultado»— que el usuario resolvió con (b).
- **Lo que se añade y el contrato no tenía:** exigir los valores en la hoja **no prueba que ganen**.
  Se comprueba en navegador, a 1180×820 dark, el **valor computado** de `overflow-wrap` y
  `word-break` sobre un chip real. Si no ganan, el hallazgo es otro y se escala.
- **Relación con `D-BTN-1` (fase F-D):** esta ficha **cambia el enunciado de esa fase**. F-D iba a
  retirar un `!important` de `.pdc-legend-item` creyendo que no lo protegía nadie; en realidad este
  contrato lo exigía. Al quitar esa exigencia, F-D deja de chocar con él.
- **Estado:** `resuelta 2026-08-12: la aserción mide los valores, no el !important, y se añade la
  comprobación del valor computado en navegador. Decidida por la coordinadora bajo autonomía
  delegada, con el precedente de D-CI-1.` · **ejecutada en `b10a3298`.**

**EXCEPCIÓN AL PROTOCOLO, AUTORIZADA Y ESCRITA PARA QUE NO SIENTE PRECEDENTE EN SILENCIO.**
Este cambio lo **implementó la coordinadora**, que es también quien dio el visto. Es el «autor que
avala su propio trabajo» contra el que está construido el resto de este protocolo, y no debe repetirse
por costumbre.

- **Por qué:** las dos sesiones de ejecución dejaron de producir avance —tres reactivaciones, dos
  chips, ~40 min con los worktrees limpios y sin merge—, y abrir una sesión nueva exige una acción del
  usuario que no llegaba. El usuario había dado sus autorizaciones **por adelantado salvo el
  despliegue a pruebas y producción** (2026-08-12) y mantuvo la directiva de terminar las fases
  después de que la coordinadora **levantara expresamente esta objeción**. Eso es una decisión suya,
  no un descuido.
- **Qué se hizo para compensar la falta de un segundo par de ojos**, ya que no había revisor
  independiente: **tres** mutaciones ejecutadas en vez de una —quitar el valor (rojo, y cae la
  aserción que lo nombra), ponerle `!important` (sigue verde, luego la regla no se invirtió), y el
  valor **fuera** del bloque del chip (rojo, que es lo que descubre una regex laxa)— más la
  comprobación en navegador que el contrato no tenía: **7 chips a 1180×820 dark, los 7 con
  `overflow-wrap`, `word-break` y `white-space` en `normal`**. Ganan sin `!important`.
- **Hallazgo reportado en contra del propio cambio:** `display` computa **`flex`**, no `inline-flex`.
  La aserción exige que `inline-flex` esté *declarado* y lo está, pero **en pantalla pierde**: esa
  línea del contrato vigila una declaración inerte. Es previo a este cambio —lo midió `D-BTN-1`— y
  **no se arregló de paso**. Queda anotado.
- **Lo que esta excepción NO cubre:** el despliegue a pruebas y a producción, que siguen necesitando
  autorización propia y explícita, cada uno por separado.

### D-COORD-1 · Las colas de decisiones de las sesiones no están versionadas

- **Quién pregunta:** levantado por la sesión del frente `gates-al-ci` fuera de su encargo, el
  2026-08-12; **remedido por la coordinadora, que encontró la afirmación a medias.**
- **Fecha:** 2026-08-12
- **Qué se decide:** si `decisiones/` sale del `.gitignore` para que git detecte las colisiones entre
  sesiones, o se acepta el riesgo.
- **Qué se midió** (`git check-ignore -v`, sobre `3fa087b8`):
  - `decisiones/` → **ignorado** (`.gitignore:404`).
  - `.claude/` → **ignorado** (`.gitignore:219`).
  - `goals/` → **NO ignorado.** Está versionado, con 40 entradas.
  - **Corrección:** la escalada original decía que los tres estaban ignorados y citaba `.gitignore:62`
    para `goals/`. Es falso, y **la coordinadora lo repitió al usuario antes de comprobarlo** — el
    defecto exacto que esta cola persigue: una cifra ajena se adopta como propia y pierde su
    procedencia. Quien la repite es quien la afirma.
  - Hay **10 colas vivas** en `decisiones/`, una por frente, ninguna versionada.
- **Por qué importa, y no es teórico:** el 2026-08-11 una sesión escribió con `Write` sobre la cola de
  otra y **se llevó doce hallazgos sin conflicto, sin diff y sin rastro** — precisamente porque el
  archivo no estaba versionado. Versionarlo convierte una pérdida muda en un conflicto ruidoso. La
  convención de nombres por rol ayuda, pero **depende de que todos la recuerden; git no depende de que
  nadie recuerde nada**. La cola compartida de este repo acumuló 27 commits en un día entre varias
  sesiones sin perder nada, por estar versionada.
- **Opciones:** (a) sacar `decisiones/` del `.gitignore` y versionarla; (b) dejarlo y confiar en la
  convención de nombres; (c) suprimir `decisiones/` y que todo vaya a esta cola única, que ya está
  versionada.
- **Recomendación:** **(a)**, y **(c) merece mirarse**: este repo ya tiene una cola canónica
  versionada, y el andamiaje del plugin crea una segunda en `decisiones/` que está ignorada a
  propósito. **Dos colas es cómo se pierde una decisión**, y una de las dos además es invisible para
  git. Pero `(c)` toca el andamiaje del plugin, que es otro repositorio y otro mando.
- **Qué quedó saltado esperando:** nada. Ninguna sesión está bloqueada por esto.
- **Estado:** `abierta`

### D-COORD-2 · El mapa de estado devuelve 40 filas y ningún dato dentro

- **Quién pregunta:** levantado por la sesión `Linen mode y mobile + tablet` fuera de su encargo, el
  2026-08-12, mientras esperaba turno. **No verificado aún por la coordinadora**, y así se declara:
  lo que sigue es la medición de esa sesión, atribuida, no adoptada como propia.
- **Fecha:** 2026-08-12
- **Qué se decide:** si el mapa de estado del plugin se arregla, se declara inservible en este repo, o
  se retira de la documentación como fuente.
- **Qué midió esa sesión** (sobre `58f7263c`): `cas-estado.sh` devuelve **RC=0** y lista **40
  frentes**; los 40 salen con señal «sin rama resoluble» y avance «❓ no medible».
- **Por qué importa, y no es que falte una función:** el script **sale en verde**. Devuelve cuarenta
  filas con forma de inventario y ningún dato dentro. **Un mapa vacío se nota; uno que responde a todo
  «no medible» con RC=0 se lee como si midiera** y se cita como fuente. Es la misma criatura que
  atravesó toda la jornada del 2026-08-11 —el instrumento que ante «no hay dato» devuelve algo con
  forma de resultado— en su versión más grande.
- **Lo que NO se sostiene, y conviene dejarlo escrito:** esa sesión añadió que «la auditoría de la
  coordinadora depende de él». **Es falso.** La coordinadora no lo usó ni una vez en toda la jornada:
  midió contra git directamente (`git log`, `git status -sb`, `git worktree list`,
  `merge-base --is-ancestor`) y contra la salida real de cada sesión. El mapa ciego **no deja ciega a
  la coordinación**, y creerlo podría llevar a alguien a pararse pensando que se quedó sin
  instrumentos.
- **Opciones:** (a) arreglar la resolución de ramas; (b) que el script **falle ruidosamente** cuando
  no puede resolver, en vez de devolver RC=0 con filas vacías —es la corrección de fondo, y vale
  aunque (a) no se haga—; (c) declararlo no aplicable a este repo y quitarlo de la documentación.
- **Recomendación:** **(b) primero**, y luego (a). Un instrumento que no puede medir debe decirlo con
  un código de salida distinto de cero; mientras devuelva verde, cualquier arreglo posterior seguirá
  siendo indistinguible de un fallo silencioso.
- **De quién es:** el script vive en el repositorio `coordinating-agent-sessions`, **otro mando**.
  Aquí solo se registra el hallazgo; no se toca desde `lps-aia`.
- **Qué quedó saltado esperando:** nada. Ninguna sesión está bloqueada.
- **Estado:** `abierta`

### D-GAC-4 · El gate `runtime` tiene un recibo verde que solo vale en la máquina que lo midió

- **Quién pregunta:** la coordinadora, 2026-08-12, al ver la primera corrida de CI que llegó al job
  de runtime desde el 2026-07-17.
- **Fecha:** 2026-08-12
- **Qué se decide:** qué se hace con los goldens visuales, que pasan en local y fallan en CI.
- **Qué se midió** (corrida `31561660136`, sobre `65c44435`):
  - `design-system-static` → **success**, por primera vez desde el 2026-07-17.
  - `design-system-runtime` → **failure**, en el paso «Run laboratory gates»
    (`npm run test:design-system:runtime`): **18 pruebas visuales fallidas de 20.**
  - Las diferencias: **`ratio 0.03`** contra una tolerancia de `maxDiffPixelRatio: 0.002`
    (`playwright.config.mjs:39`) — **quince veces el umbral**, ~27.000 píxeles.
  - **La uniformidad es el dato clave:** 26952, 27211, 25536, 27012… todas las familias difieren casi
    en la misma cantidad. Un cambio de diseño real variaría mucho entre una pantalla de formularios y
    una de overlays; esto no. Es coherente con un render de plataforma distinto, **aunque no está
    probado**: probarlo exige capturar en CI y comparar, que es parte de lo que se decide aquí.
  - **Los 60 goldens están versionados y no llevan sufijo de plataforma**
    (`tests/browser/__screenshots__/auth/login-dark-1180x820.png`): un solo juego para macOS y Linux.
  - **El recibo de `runtime` es honesto, no falso:** `closeout-evidence.json` lo declara `passed` con
    `exitCode: 0`, `verifiedAt: 2026-08-11T23:45:04Z`, `sourceRef: 1cb83128`. Pasó de verdad — en la
    máquina donde se midió.
- **Por qué no se vio antes, y es lo que más enseña:** el job de runtime **no se ejecutaba desde el
  2026-07-17** porque `needs: design-system-static` lo mantenía apagado. El recibo verde y la
  imposibilidad de contrastarlo convivieron un mes. **Arreglar el static es lo que lo destapó**: no es
  una regresión nueva, es una ceguera vieja que acaba de abrir los ojos.
- **Opciones:**
  - **(a) Goldens por plataforma** — Playwright admite `{platform}` en `snapshotPathTemplate`. Local
    y CI conservan cada uno su juego y los dos vigilan de verdad. Coste: una pasada de captura en CI,
    y el doble de archivos.
  - **(b) Recapturar los goldens en CI** y que Linux sea la referencia única. Más simple, pero
    **invierte el problema**: pasarían a fallar en local, que es donde se trabaja a diario.
  - **(c) Declarar el carril visual no ejecutable en CI**, como `runtime-budgets`, con su motivo
    escrito. Honesto, pero renuncia a vigilar lo visual donde de verdad importa.
  - **(d) Subir la tolerancia hasta que pase.** **Descartada, y conviene decir por qué:** haría falta
    pasar de `0.002` a más de `0.03`, quince veces, y eso deja pasar cambios de diseño reales. El
    comentario del propio archivo dice que la tolerancia se **bajó** de `0.005` a `0.002` precisamente
    porque una amplia dejaba pasar cambios ciertos. Sería deshacer una decisión medida para tapar
    esto.
- **Recomendación:** **(a)**. Es lo único que deja los dos entornos vigilando de verdad, y no fabrica
  ningún verde.
- **Lo que NO se ha hecho, y no se hará sin decisión explícita:** regenerar goldens.
  `AGENTS.md` §Verificación lo prohíbe —«no regeneres snapshots ni baselines para forzar un resultado
  verde; los cambios visuales requieren aprobación explícita»— y aquí sería exactamente eso.
- **Qué queda saltado esperando:** el gate `runtime` sigue declarado `passed` con su recibo local.
  **No se toca ese recibo:** no es falso, es de otra plataforma, y cambiarlo sin decidir esto sería
  sustituir un dato honesto por otro.

**Dos mediciones añadidas el 2026-08-12, y la segunda corrige a la coordinadora.**

1. **La causa está probada, no supuesta.** `npm run test:visual:lab` en local, sobre el **mismo sha**
   que falla en CI → **`20 passed`, RC=0**. Mismo código, mismos goldens: verde en macOS, rojo en
   Linux. Corolario que importa para elegir: **si hubiera un cambio visual real escondido, local
   también fallaría**, porque los goldens son la referencia aprobada.

2. **El coste de (a) es mucho mayor de lo que esta ficha decía, y el error era mío.** Escribí que era
   «una pasada de captura y el doble de archivos». Lo intenté —`snapshotPathTemplate` con
   `{platform}` y los 59 movidos con `git mv`, sin alterar un byte: hash idéntico al de `HEAD`— y el
   **gate estático se puso rojo**: `golden hash mismatch` y `missing golden` en cadena. **Un
   manifiesto del contrato del design system ancla cada golden por ruta y por hash.** Mover la carpeta
   rompe ese contrato.
   - Revertido en `949bb644`. Medido después: `STATIC_RC=0`, `PILOTO_RC=0`, 60 goldens en su sitio,
     árbol limpio. Nada publicado.
   - **Coste real de (a):** cambiar la plantilla **y** el manifiesto que ancla los 59 — es decir,
     **tocar un contrato**, de las cosas que escalan siempre. Sigue siendo la opción correcta de
     fondo, pero es **un frente con su propio plan**, no un retoque.
   - **Cómo se coló:** encadené la verificación al `commit` en la misma línea, así que el commit se
     hizo con el gate en rojo. Es la trampa que `AGENTS.md` documenta para el `push`, en su versión de
     `commit`. Lo cazó la verificación posterior, no yo al escribirlo.

- **Estado:** `abierta` — con el coste corregido. La opción (a) sigue siendo la recomendada, pero
  ahora se sabe que es un frente, no una línea.

### D-GAC-5 · El presupuesto de runtime se congeló el día que el CI se rompió

- **Quién pregunta:** la coordinadora, 2026-08-12, tras enchufar `runtime-budgets` al CI (fase F-AB).
- **Fecha:** 2026-08-12
- **Qué se decide:** si el baseline `runtime-baseline-0.3.3.json` se re-aprueba con las cifras de hoy,
  o si estas violaciones se tratan como deuda de rendimiento a corregir.
- **Qué se midió** (corrida `31563364701`, sobre `0b2cb1f8`, procedencia real de CI):
  - `Measure runtime budgets` → **success**. `Check runtime budgets against the baseline` →
    **failure**, con `"pass": false` y **violaciones reales, no de procedencia**:

    | Métrica | Baseline | Máximo | Medido |
    |---|---|---|---|
    | `cssGzipBytes` | 136.933 | 138.981 | **194.554** (+42 % sobre el baseline) |
    | `initializationMs` | 991 | 1.101,5 | **1.644,4** (+66 %) |
    | `adapterAssets` | 7 rutas | tolerancia 0 | lista distinta |

  - En `adapterAssets`, el baseline **todavía lista `semi-auto-review.css`**, que se eliminó con el
    **PDC v1 el 2026-08-04** (`AGENTS.md`). Y faltan dos que sí existen hoy: `shell-sidebar.css` y
    `datatables.css`. Verificado contra `public/css/design-system/adapters/`.
  - **El dato que lo explica todo:** el último commit del baseline es `1cfc6e2c`, del **2026-07-17** —
    **el mismo día de la última corrida verde del workflow**. El gate perdió la capacidad de correr y
    el baseline dejó de actualizarse **la misma fecha**. Un mes de cambios legítimos quedó fuera de su
    vista.
- **Lo que NO se puede concluir, y conviene decirlo:** **no** que la aplicación «se degradó un 42 %».
  Lo medido es la **divergencia contra un baseline de hace un mes**, que incluye altas y bajas
  legítimas. Cuánto de ese 42 % es crecimiento real y cuánto es baseline obsoleto **no está medido**.
- **Opciones:**
  - **(a) Re-aprobar el baseline con las cifras de hoy**, dejando escrito qué cambió y por qué. Pone
    el gate en verde y lo devuelve a vigilar desde hoy. **Riesgo:** si parte del 42 % es una
    regresión real, se hornea como normal — y eso es exactamente lo que un baseline no debe hacer.
  - **(b) Tratarlo como deuda:** investigar de dónde salen los 57 KB y los 650 ms antes de tocar el
    baseline. Es lo correcto de fondo y es un frente propio con medición por módulo.
  - **(c) Mezcla:** corregir `adapterAssets` ya —es un hecho, no una opinión: un archivo que no existe
    y dos que sí— y dejar las dos métricas numéricas abiertas hasta (b).
- **Recomendación:** **(c)**, y luego (b). La lista de adaptadores no es un juicio: está objetivamente
  desfasada. Las dos cifras sí necesitan saber cuánto es crecimiento y cuánto es baseline viejo, y
  aprobarlas a ciegas convertiría una posible regresión en la nueva normalidad.
- **Por qué no lo decido yo, teniendo autonomía:** **tocar un baseline escala siempre**, sin aplicar
  la prueba del bloqueo (`AGENTS.md`, `coordinacion-sesiones.md`). Y aquí el riesgo es el del propio
  protocolo: sería fabricar el verde con el gesto más inocente que existe.
- **Qué queda saltado esperando:** el gate `runtime-budgets` se queda **rojo y honesto**. No se toca
  el baseline ni el recibo.
- **Estado:** `abierta`

## Resueltas

Se quedan arriba, en su sitio, con el estado cambiado: mover una entrada resuelta rompe los enlaces
que la citan y pierde el contexto que la rodea. Este índice es para encontrarlas rápido.

| Id | Decisión | Quién la ejecuta |
|---|---|---|
| `D-1` | El informe adopta la numeración del spec | La sesión que mantiene `docs/reportes/estado-desarrollo.html` |
| `D-2` | Triar los 30 tests por tandas, sin urgencia | Sin dueño asignado; se recoge cuando haya ocasión |
| `D-CI-1` | La aserción comprueba que el CI ejecuta la prueba | **Sesión de CI** — ejecutada y verificada, sesión archivada |
| `D-CI-2` | PHPUnit para lógica pura, scripts para lo que necesita entorno | Sin dueño: es una regla, se aplica al escribir el próximo test |
| `D-F1-1` | Las dos severidades se quedan distintas, y se escribe por qué | Frente 1 · aplicado en `66facd23` |
| `D-F1-2` | Familia nueva de tokens de fondo para destacar celdas, calibrada a 3:1 | Frente 1 · aplicado en `66facd23` |
| `D-F1-3` | Apuntar los cuatro `--aia-*` al token real y retirar la reserva hex | **Sin aplicar**: ningún token conserva el color; vuelve al usuario |
| `D-F1-4` | «Confirmar Compromisos» y «Actualizar Ejecución» pasan a primaria | Frente 1 · aplicado en `66facd23` |
| `D-F1-7` | Una sesión de ejecución hace su frente y se detiene; no encadena los siguientes | Regla general · aplica a todas las sesiones de ejecución |
| `D-F1-5` | Añadir un token de espacio de 72 px (`--ds-space-18`) | Frente 1 · aplicado en `66facd23` |
| `D-F1b-1` | `git-preservation` se retira, con motivo escrito | Frente 1b · ejecutado en esta sesión |
| `D-F1b-2` | `pg-roles`+`pg-persistence`+`data-restoration` se funden en `full-app-flow` | Frente 1b · ejecutado en esta sesión |
| `D-F1b-3` | Los cuatro gates de herramienta inexistente se retiran | Frente 1b · ejecutado en esta sesión |
| `D-F1b-4` | Arreglar los que no tocan `Database.php` ni legado; anotar el resto **con su motivo** | Frente 1b · aplicado en `3d72a8df` (2 arreglados, 4 anotados) |
| `D-F1-6` | Frente corto de forma, **con la regla de no cerrar sin haber quitado algo** | Turno propio, al publicar el Frente 1b · no se pliega en el Frente 2 |
| `D-GAC-1` | `!important` permitido dentro de `@layer`, prohibido fuera — para desbloquear el CI | Fase **F-0** del plan de cierre, frente `ci-en-verde` |
| `D-GAC-2` | La regex de los chips tolera clases intermedias; el markup no se toca | Fase **F-0**, Tarea 2b · mismo frente |
| `D-GAC-3` | La aserción mide los valores, no el `!important` — y se comprueba en navegador | Fase **F-0**, Tarea 2c · mismo frente |
| `D-BTN-1` | Investigar y retirar lo que no gana — **ya ejecutado** en `0a228a39` | Frente `buttons-important-leyenda` · F-D se retira del plan |
