---
tipo: trampa
estado: vigente
fecha: 2026-08-11
areas: [qa, proceso]
fuente: sesion
resumen: "Una aserción se validó mutando sus entradas y pasó las tres pruebas; el agujero estaba en el supuesto sobre el que se apoyaba, una capa por debajo, y ahí no miró nadie"
---
El 2026-08-11 se reescribió una aserción de `tests/design-system/visual-ci-contract.test.mjs` para
que comprobara que el CI **ejecuta** `tests/test_global_table_safety.php` en vez de que lo **nombra**
(decisión `D-CI-1`). La aserción compara el nivel máximo que el workflow invoca con el nivel que la
prueba declara en su `// @requiere:`.

Se entregó demostrando que sabía fallar, con tres mutaciones ejecutadas: cambiarle el nivel a la
prueba a uno que el CI no corre, quitarle la etiqueta, y quitar la invocación del runner del
workflow. Las tres dan rojo. Parecía suficiente.

**No lo era.** La comparación por nivel solo es correcta si el runner es **acumulativo** —si
`--nivel=http` ejecuta además lo `db` y lo `puro`—, y eso lo decidían dos `<=` en
`scripts/run-php-tests.php`. Cambiándolos por `===`:

- el contrato seguía en **RC=0**,
- y `test_global_table_safety` **dejaba de ejecutarse en el CI**, que es exactamente la frontera que
  el contrato existe para vigilar.

`tests/test_php_test_runner.php` sí se ponía rojo con esa mutación, así que la garantía existía —
pero de refilón: fallaba por una aserción sobre la base de datos ausente, no por una escrita para
la acumulatividad. Un refactor de ese test se la habría llevado por delante sin que nadie lo notara.

**Por qué no se vio:** quien escribió la aserción mutó **sus entradas** —el nivel declarado, la
etiqueta, la invocación—. El agujero estaba en el **supuesto** sobre el que la aserción se apoyaba,
una capa por debajo. Mutar las entradas de una comprobación nunca destapa un supuesto falso: hay que
mutar el supuesto.

Cerrado añadiendo a `tests/test_php_test_runner.php` cuatro comprobaciones que verifican la
acumulatividad de frente, y ampliando `--solo-listar` para que marque `[ejecuta]` u `[omite]` según
el nivel pedido, de modo que se pueda comprobar sin necesitar el entorno de ese nivel. Con la
mutación puesta, ahora falla por su propia razón: «pedir db ejecuta TAMBIEN el de nivel puro».

## El mismo día, la variante de al lado: el test menos estricto que lo que prueba

`tests/test_php_test_runner.php` declaraba `// @requiere: puro` —«solo PHP y autoload»— pero tres de
sus comprobaciones necesitaban el binario de PHPUnit, que es dependencia de desarrollo. En un
contenedor construido con `--no-dev` daba **RC=1 con cinco fallos**, sobre el mismo commit donde en
otra máquina daba RC=0.

Lo llamativo: **el runner era más estricto que su propio test.** Sin PHPUnit, el runner aborta con
RC=2 y dice por qué; el test fallaba con cinco aserciones que no mencionaban PHPUnit por ningún
lado, como «un test etiquetado y verde devuelve 0».

Y al arreglarlo apareció la causa de fondo, que era peor: de esos cinco fallos, **tres no tenían
nada que ver con PHPUnit**. Fallaban porque las comprobaciones no pasaban `--dir-unit` y el runner
miraba entonces el `tests/unit/` **real** del repositorio. El test estaba acoplado a un directorio
vivo que cualquiera puede cambiar — y dos comprobaciones más *pasaban por casualidad* en ese
entorno, que es el fallo silencioso de la misma moneda.

Cerrado aislando con un directorio de fixtures vacío todo lo que no va de PHPUnit, y declarando la
precondición en las tres que sí: ahora salen con **2** nombrando lo que no se pudo comprobar, en vez
de fingir un fallo de otra cosa. **Regla:** el test de un guardarraíl debe ser al menos tan estricto
como el guardarraíl, y sus fixtures no deben tocar árboles vivos.

**Why:** una aserción que se apoya en un invariante no comprobado es tan frágil como el invariante,
y su verde tapa el hueco en vez de señalarlo. **How to apply:** cuando entregues un gate, pregúntate
**de qué tiene que ser cierto para funcionar**, y muta eso además de sus entradas. Si la respuesta
es «de que tal cosa se comporte así», esa cosa necesita su propia aserción, y el gate debería
nombrarla en un comentario para que el siguiente no la rompa sin saberlo. Relacionado:
[[el-archivo-que-tocas-puede-tener-un-contrato]], [[gate-solo-cuenta-elementos-no-los-lee]],
[[guard-valida-declaracion-contra-si-misma]].
