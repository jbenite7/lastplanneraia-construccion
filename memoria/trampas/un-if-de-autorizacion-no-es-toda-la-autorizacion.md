---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-13
areas: [rbac, qa, lps]
fuente: sesión del 2026-08-13, revisión de los hallazgos de la red F2a-2b-1
resumen: en la semanal la autorización vive en dos capas —el candado de semana y la separación de fases por campo—; leer solo la primera produce sospechas falsas de brecha
---
Al revisar por qué el cliente deja editar `Ejecutado_Real` en una semana histórica, se leyó
`LpsWeekEditPolicy::allows()` (`src/Security/LpsWeekEditPolicy.php:16-48`) y se concluyó —mal—
que había una brecha: el guard autoriza por **semana y rol**, sin mirar qué campo se modifica,
y `SemanalApiController.php:156-158` lo invoca con `$qualification = true` para toda la opción
`modificar`. De ahí salía la sospecha de que por API se podría cambiar `Compromiso` en una
semana ya cerrada.

**Es falso, y la razón es que la autorización de la semanal vive en dos capas:**

| Capa | Dónde | Qué decide |
|---|---|---|
| Candado de semana | `LpsWeekEditPolicy::allows()` | Si este rol puede tocar **esta semana**. Con `$qualification = true`, abre la histórica **confirmada** a quien puede calificar (A/D/R/DCV). |
| Separación de fases | `SemanalApiController.php:309-315` | Si **este campo** se puede tocar en la fase actual. Con la semana confirmada, devuelve **409** ante compromiso, responsables o planificación; solo pasa el avance real. |

La segunda es la que acota por campo, y dibuja exactamente la misma frontera que la interfaz.
Ya estaba cubierta de extremo a extremo por
`tests/browser/programacion-semanal-roles-phases.mjs:316` («rol R histórico solo puede calificar
el compromiso confirmado»), que afirma 200 en la calificación y 409 al tocar planificación.

**El criterio que deja esta página:** antes de reportar una brecha de permisos en la semanal,
busca la segunda capa. Un `if` de autorización rara vez es toda la autorización, y aquí las dos
están en archivos distintos —`src/Security/` y el controlador—, así que leer solo una da un
cuadro incompleto y convincente. El coste de no hacerlo no es solo el susto: casi se encarga una
tarea entera sobre una premisa que no existía.

**Cerrado el 2026-08-13** (`c9f602e4`): se extrajo `LpsWeekEditPolicy::decide()`
(`src/Security/LpsWeekEditPolicy.php:55`), la composición pura de las dos reglas sin sesión ni
base, y `tests/test_lps_week_edit_policy.php` la prueba directamente — incluido el caso «R/DCV
califican avance en semana histórica confirmada aunque `canEditLpsWeek()` los deniegue». Ya no
depende solo del e2e.

Ver también [[regla-inalcanzable-parece-regla-sin-probar]], de la misma sesión: el otro caso en
que lo que parecía un agujero de pruebas era en realidad una propiedad del diseño.
