---
tipo: trampa
estado: vigente
fecha: 2026-08-14
areas: [qa, docker, datos, lps]
fuente: src/Services/ProgramChangeDetector.php, public/js/modules/programacion_semanal/changeMonitor.js, corrida de programacion-semanal-roles-phases.mjs
resumen: una fila sembrada solo en programacion_semanal y programa, sin su pareja en programa_consolidado, se borra sola en el primer render — sin aviso, sin importar si la semana está confirmada
---
Al ampliar `database/fixtures/design-system-ci.sql` con filas nuevas de `programacion_semanal`
(2026-08-14, ver [[fijar-un-dato-de-la-base-en-un-test-lo-podre]]), dos de las cuatro filas
desaparecían solas entre una corrida y la siguiente, sin ningún `DELETE` explícito en el test.
El síntoma inicial fue peor que un borrado limpio: un **hang de 120s** en la primera llamada a
`/api/semanal/list`, con la fila ya desaparecida al inspeccionar la base después.

La causa: `changeMonitor.js` dispara `POST /api/semanal/auto-program` **automáticamente en cada
carga de página** (una vez por sesión de módulo, sin esperar a que el usuario pulse nada).
Ese endpoint corre `ProgramChangeDetector::run()`, cuyo «PASO 1: PS huérfanas» borra toda fila de
`programacion_semanal` cuyo `unique_id` no tenga pareja en `programa_consolidado` para esa
`Semana` — **sin mirar si la semana está confirmada**. `requireWeekEditPolicy` en `autoProgram()`
tampoco lo bloquea para el rol Admin, porque `canEditLpsWeek('A', ...)` es `true` siempre.

Consecuencia práctica: **toda fila que se siembra en `programacion_semanal` necesita su pareja
en `programa_consolidado`** (mismo `unique_id`, misma `Semana`), o desaparece en el primer
`GET`/navegación de esa semana. La única excepción es una fila con `Activa='0'` (p. ej. una fila
de CNP): `loadPsConsecutivos()` —la que arma el conjunto de "vivas" para el paso 1— filtra
`Activa IN ('1','NA')`, así que una fila `Activa='0'` nunca entra en el chequeo de huérfanas y no
necesita pareja en `programa_consolidado`.

**Cómo depurarlo si vuelve a aparecer:** un hang inexplicable en un endpoint de solo lectura,
con datos que faltan después, casi nunca es de verdad un hang — es una petición previa que sigue
corriendo en el único worker de PHP-FPM disponible. Extraer `0-trace.network` del `trace.zip` de
Playwright (`unzip`, luego JSON por línea con `snapshot.request`/`snapshot.response`) muestra la
secuencia real de peticiones, incluidas las automáticas que el test nunca pidió.

Ver también [[el-dato-esta-en-desarrollo-y-el-permiso-en-el-stack-aislado]] (derogada, mismo
fixture) y [[exec-en-contenedor-vivo-corre-el-repo-ajeno]].
