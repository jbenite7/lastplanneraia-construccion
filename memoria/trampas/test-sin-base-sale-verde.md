---
tipo: trampa
estado: vigente
fecha: 2026-08-10
areas: [qa]
fuente: sesion
resumen: "26 tests de la suite PHP salen 0 cuando NO hay base de datos: capturan el fallo de conexión y terminan bien. Correr la suite sin entorno da verdes que no comprobaron nada"
---
Medido el 2026-08-10 sobre los 99 `tests/test_*.php` de `main@13d33af3`, en dos pasadas y **por
código de salida**, nunca por grep del texto ([[suite-php-rojos-preexistentes]] explica por qué):

| Pasada | Entorno | Pasan | Fallan |
|---|---|---|---|
| A | contenedor sin base ni HTTP | 93 | 6 |
| B | stack `docker-compose.ci.yml`, base fixture + app viva | 71 | 28 |

Cruzando ambas aparecen **26 tests que pasan sin base y fallan con base**. No es una paradoja: sin
base capturan el error de conexión, lo imprimen y terminan en 0. Con base llegan a trabajar de
verdad y ahí se ve que les faltan datos.

La consecuencia práctica es la peligrosa: **lanzar la suite donde no hay base no produce un rojo
ruidoso, produce 26 verdes falsos**. Quien lo hiciera concluiría que la suite está sana.

Por eso `scripts/run-php-tests.php` comprueba el entorno del nivel pedido **antes** de ejecutar
nada, y sale 2 sin correr un solo test si falta. La ausencia de entorno es un error del runner,
nunca un resultado verde.

Tres tests más se destaparon al etiquetar, y ninguno se delataba leyendo el código:
`test_bi_programa_general_radar`, `test_bi_restriction_thresholds` y `test_shell_sidebar_partial`
abren la base sin nombrar `Database::getInstance` ni `new PDO` en su fuente. Los cazó correr el
nivel `puro` en un contenedor sin red: imprimían el error de conexión y salían 0. El tercero era el
peor de los tres, porque además imprime `PASS` de otras comprobaciones y así se cuela por cualquier
heurística que solo mire si el test dijo algo.

**Why:** un verde que nadie puede distinguir de un verde real es peor que un rojo, porque cierra la
investigación en vez de abrirla. **How to apply:** no corras `tests/test_*.php` sueltos en un
entorno a medias para «ver si están bien»; usa el runner con su nivel, y si te devuelve 2, léelo
como lo que es —falta entorno—, no como un fallo del test. Relacionado:
[[suite-php-rojos-preexistentes]], [[aislar-stack-docker-por-worktree]].
