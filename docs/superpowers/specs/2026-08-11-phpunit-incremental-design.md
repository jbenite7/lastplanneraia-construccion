# PHPUnit incremental, conviviendo con la suite de scripts

- **Fecha:** 2026-08-11
- **Estado:** diseño
- **Fase:** 2 de 2. La fase 1 (runner y conexión al CI) cerró y está publicada.

## El problema

Los 101 `tests/test_*.php` son scripts autoejecutables. Funcionan y el runner de la fase 1 ya los
gobierna, pero escribir uno nuevo obliga a reimplementar a mano el andamiaje —contadores `$total` y
`$fallos`, una función `verificar()`, el `exit()` final— y no hay aserciones ricas, ni fixtures, ni
aislamiento entre casos. Se quiere PHPUnit **para lo nuevo**, sin migrar los 101 de golpe.

## Lo que no se toca

- **Ningún test existente se migra.** La convivencia se demuestra con tests nuevos, no reescribiendo
  los que ya funcionan. Migrar es un frente aparte, si alguna vez se decide.
- **Las garantías de la fase 1 se conservan tal cual**, y ese es el requisito que manda:
  1. Nada corre sin que su entorno esté comprobado antes.
  2. Un test nuevo no puede quedar fuera del CI en silencio.

## Restricción medida que condiciona todo el diseño

`docker/php/Dockerfile:36` declara `ARG COMPOSER_INSTALL_FLAGS=--no-dev`, y sólo
`docker-compose.ci.yml` lo sobrescribe con `COMPOSER_INSTALL_FLAGS: ""`. Las tres líneas están
fijadas por contrato en `tests/design-system/visual-ci-contract.test.mjs:143-145`.

Consecuencia: **una imagen construida sin `docker-compose.ci.yml` no tiene dependencias de
desarrollo, así que no tendría PHPUnit.** Es exactamente el caso del job `design-system-static`, que
construye con `docker compose build app`. Por eso el diseño tiene que decir explícitamente qué pasa
cuando PHPUnit no está: la respuesta no puede ser «se salta», porque sería el mismo falso verde que
`memoria/trampas/test-sin-base-sale-verde.md` documenta.

## Diseño

### 1. Dónde viven — `tests/unit/`

El glob del runner es `glob($directorio . '/test_*.php')` (`scripts/run-php-tests.php:127`), **no
recursivo**, así que un subdirectorio no colisiona con él. `tests/` ya tiene siete subdirectorios en
minúsculas (`browser/`, `design-system/`, `fixtures/`, `rbac/`, `scripts/`, `support/`, `wiki/`);
`tests/unit/` sigue esa convención.

Las clases se llaman `<Algo>Test.php` y viven bajo el namespace `Tests\Unit\`, mapeado en
`autoload-dev` para no ensuciar el autoload de producción.

### 2. Cómo se invoca — el runner sigue siendo la puerta única

`scripts/run-php-tests.php --nivel=X` pasa a hacer dos cosas, en este orden:

1. Comprobar el entorno del nivel X (ya lo hace).
2. Ejecutar los scripts `tests/test_*.php` de nivel ≤ X (ya lo hace).
3. **Invocar PHPUnit** para los tests de `tests/unit/` cuyo nivel sea ≤ X.

El código de salida agrega ambos mundos: 0 sólo si todo pasa. **El CI no cambia**: sigue llamando al
runner con `--nivel=puro` y `--nivel=http`, y ahora eso cubre también PHPUnit.

Se descarta el diseño alternativo —dos comandos y que el CI corra ambos— porque duplicaría la
comprobación de entorno y abriría la puerta a que uno de los dos se olvide en algún job, que es
literalmente el defecto que la fase 1 vino a cerrar.

### 3. Cómo se declara el nivel — atributo en la clase

```php
#[Group('db')]
final class MiCosaTest extends TestCase { … }
```

Los cuatro grupos válidos son los cuatro niveles: `puro`, `db`, `http`, `datos-proyecto`. El runner
traduce el nivel pedido a `--group` de PHPUnit.

El nivel se declara **en la clase, no en el método**. Un caso que necesite otro entorno va a otra
clase. Es la regla simple que basta hoy; abrirla a granularidad por método se puede hacer luego si
algo lo pide.

### 4. Los dos guardarraíles, trasladados

- **Clase sin grupo de nivel → el runner sale 2** y la nombra, igual que hoy con un script sin
  `// @requiere:`. Se comprueba escaneando los archivos, con el mismo mecanismo que ya existe, sin
  depender de la API de PHPUnit.
- **PHPUnit ausente cuando el nivel pedido incluye tests suyos → el runner sale 2.** Ausencia de
  herramienta es un error del runner, nunca un verde. Si el nivel pedido no selecciona ningún test
  PHPUnit, la ausencia no importa y el runner sigue.

### 5. El job estático pasa a construir con dependencias de desarrollo

Para que el nivel `puro` pueda ejecutar también los tests PHPUnit de ese nivel, el paso de build del
job `design-system-static` pasa a:

```yaml
run: docker compose -f docker-compose.yml build --build-arg COMPOSER_INSTALL_FLAGS="" app
```

No toca el `Dockerfile` ni `docker-compose.ci.yml`, así que las tres aserciones de
`visual-ci-contract.test.mjs:143-145` siguen cumpliéndose: el `ARG` sigue existiendo con su valor por
defecto y el compose de CI sigue declarando el suyo. Se verificará corriendo la suite estática
completa, no leyendo el YAML — es la lección de
`memoria/trampas/el-archivo-que-tocas-puede-tener-un-contrato.md`.

### 6. Un test piloto, no una migración

Se añade **un** test PHPUnit nuevo y real, de nivel `puro`, que cubra algo hoy sin cobertura, para
demostrar la convivencia de punta a punta. No se reescribe ninguno de los 101.

## Condición de hecho

1. `composer test` (y el runner con cualquier nivel) ejecuta **scripts y PHPUnit** en una sola
   pasada, y devuelve 0 sólo si todos pasan.
2. Una clase de test PHPUnit sin grupo de nivel hace salir al runner con 2, nombrándola.
3. Con PHPUnit ausente y tests PHPUnit en el nivel pedido, el runner sale 2 en vez de dar verde.
4. Los dos jobs del CI siguen verdes, y la suite estática del design system con sus 8 gates también.
5. Los 101 scripts existentes siguen corriendo exactamente igual.
6. Todo verificado con salida real de comandos, incluida una mutación que ponga cada guardarraíl en
   rojo.

## Riesgos

- **La imagen del job estático engorda** al instalar dependencias de desarrollo. Se mide el tiempo
  del build y se reporta; si fuese caro, la alternativa es dejar PHPUnit sólo en el job runtime, a
  costa de perder el feedback rápido del nivel `puro`.
- **`visual-ci-contract.test.mjs` vigila el workflow y el Dockerfile.** Ya mordió una vez a este
  frente. Mitigación: correr la suite estática completa antes de publicar, no sólo el runner.

## Archivos de este goal

- Este spec: `docs/superpowers/specs/2026-08-11-phpunit-incremental-design.md`
- Fase 1: `docs/superpowers/specs/2026-08-10-runner-tests-php-design.md`
- Mapa del área: `memoria/mapas/qa-y-gates.md`
