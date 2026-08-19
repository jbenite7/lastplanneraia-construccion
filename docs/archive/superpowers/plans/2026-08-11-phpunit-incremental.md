---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-08-11
areas: [qa]
tags: [archivo]
fuente: docs/archive/superpowers/plans/2026-08-11-phpunit-incremental.md
resumen: Que se puedan escribir tests nuevos con PHPUnit sin migrar los 101 scripts existentes, conservando las dos garantías de la fase 1: nada corre sin entorno…
---

# PHPUnit incremental — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que se puedan escribir tests nuevos con PHPUnit sin migrar los 101 scripts existentes, conservando las dos garantías de la fase 1: nada corre sin entorno comprobado, y ningún test queda fuera del CI en silencio.

**Architecture:** PHPUnit entra como dependencia de desarrollo, con sus tests en `tests/unit/` y el nivel de entorno declarado con `#[Group('<nivel>')]` en la clase. `scripts/run-php-tests.php` sigue siendo la puerta única: comprueba el entorno, corre los scripts del nivel y además invoca PHPUnit filtrando por los grupos correspondientes, agregando ambos códigos de salida.

**Tech Stack:** PHP 8.3.33, PHPUnit ^11, Docker Compose, GitHub Actions.

## Global Constraints

- **Ningún test existente se migra ni se toca.**
- **Niveles válidos, en orden:** `puro` < `db` < `http` < `datos-proyecto`. Son los mismos cuatro que los grupos de PHPUnit.
- **Ausencia de entorno o de herramienta es error del runner (salida 2), nunca un verde.**
- **Todo PHP se ejecuta dentro del contenedor**, nunca con un PHP del host.
- **Se mide por código de salida**, y nunca al otro lado de una tubería (`memoria/trampas/el-codigo-de-salida-se-pierde-en-la-tuberia.md`).
- **`docker/php/Dockerfile` y `docker-compose.ci.yml` no se tocan:** `tests/design-system/visual-ci-contract.test.mjs:143-145` fija sus tres líneas de `COMPOSER_INSTALL_FLAGS`.
- Idioma del repo: español en comentarios, mensajes y documentación.

## Entorno de verificación

Stack aislado propio, nunca el contenedor compartido del árbol principal:

```bash
export COMPOSE_PROJECT_NAME=lps-aia-runner-wt-aa8725 CI_RUN_ID=wt-aa8725
eval "$(node scripts/design-system-ci-preflight.mjs --print-provenance)"
export CI_GIT_SHA CI_WORKTREE_FINGERPRINT CI_FIXTURE_SHA256
docker compose -p "$COMPOSE_PROJECT_NAME" -f docker-compose.yml -f docker-compose.ci.yml up -d --build db app
```

Abreviado como `$EXEC` = `docker compose -p "$COMPOSE_PROJECT_NAME" -f docker-compose.yml -f docker-compose.ci.yml exec -T app`.

## Estructura de archivos

| Archivo | Responsabilidad |
|---|---|
| `composer.json` (modificar) | `require-dev` phpunit, `autoload-dev` para `Tests\Unit\`, script `test:unit` |
| `phpunit.xml` (crear) | Suite única apuntando a `tests/unit/`, sin cobertura |
| `tests/unit/RbacCatalogTest.php` (crear) | Test piloto real, nivel `puro` |
| `scripts/run-php-tests.php` (modificar) | Descubrir clases PHPUnit, exigir grupo, invocarlo, agregar salida |
| `tests/test_php_test_runner.php` (modificar) | Cubrir los guardarraíles nuevos |
| `tests/fixtures/runner/unit-*/` (crear) | Clases de mentira para probar el runner |
| `.github/workflows/design-system.yml` (modificar) | Build del job estático con dependencias de desarrollo |
| `memoria/mapas/qa-y-gates.md` (modificar) | Registrar la convivencia |

---

### Task 1: PHPUnit instalado y un test piloto que pasa

**Files:**
- Modify: `composer.json`
- Create: `phpunit.xml`
- Create: `tests/unit/RbacCatalogTest.php`

**Interfaces:**
- Produces: `vendor/bin/phpunit` disponible en la imagen de CI; suite `unit` en `phpunit.xml`; clase `Tests\Unit\RbacCatalogTest` con `#[Group('puro')]`.

- [ ] **Step 1: Añadir PHPUnit y el autoload de tests**

En `composer.json`: `"phpunit/phpunit": "^11"` en `require-dev`, y

```json
"autoload-dev": {
    "psr-4": { "Tests\\Unit\\": "tests/unit/" }
}
```

- [ ] **Step 2: Instalar dentro del contenedor**

```bash
$EXEC composer update phpunit/phpunit --no-interaction
```

Esperado: `vendor/bin/phpunit` existe. Comprobar con `$EXEC vendor/bin/phpunit --version`.

- [ ] **Step 3: Crear `phpunit.xml`**

Suite `unit` sobre `tests/unit`, `failOnWarning`, `failOnRisky`, sin cobertura (no hay xdebug ni pcov y no se pide).

- [ ] **Step 4: Escribir el test piloto**

`tests/unit/RbacCatalogTest.php`. Cubre `RbacCatalog::roleAliases()` y la normalización de
`RbacService::normalizeRole()`, que es la función que `AGENTS.md` señala como la correcta y que ya
causó un error documentado. Nivel `puro`: no toca base ni HTTP.

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Security\RbacService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('puro')]
final class RbacCatalogTest extends TestCase
{
    public function testNormalizeRoleDevuelveElCodigoCanonicoDeUnAlias(): void
    {
        self::assertSame('A', RbacService::normalizeRole('Admin'));
    }
}
```

Los alias exactos se leen de `RbacCatalog::roleAliases()` antes de escribir las aserciones: el test
debe reflejar el catálogo real, no uno inventado.

- [ ] **Step 5: Correrlo y verificar que pasa**

```bash
$EXEC vendor/bin/phpunit --group=puro
```

Esperado: OK, 0 fallos.

- [ ] **Step 6: Verificar que sabe fallar**

Romper una aserción a propósito, correr, ver el rojo, restaurarla. Sin esto no está demostrado.

- [ ] **Step 7: Commit**

```bash
git add composer.json composer.lock phpunit.xml tests/unit/
git commit -m "feat(qa): PHPUnit como dependencia de desarrollo, con su primer test"
```

---

### Task 2: El runner exige grupo de nivel a las clases PHPUnit

**Files:**
- Modify: `scripts/run-php-tests.php`
- Modify: `tests/test_php_test_runner.php`
- Create: `tests/fixtures/runner/unit-sin-grupo/SinGrupoTest.php`

**Interfaces:**
- Consumes: el runner de la fase 1.
- Produces: `descubrirTestsUnitarios(string $directorio): array<string,string>` (ruta => nivel), que aborta con 2 si una clase no declara grupo de nivel. Opción `--dir-unit=<ruta>` para poder probarlo con fixtures.

- [ ] **Step 1: Crear el fixture sin grupo**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

// A propósito sin #[Group]: el runner tiene que cazarlo.
final class SinGrupoTest extends TestCase
{
    public function testNada(): void
    {
        self::assertTrue(true);
    }
}
```

- [ ] **Step 2: Añadir la comprobación a la prueba del runner**

```php
// Una clase PHPUnit sin grupo de nivel rompe el runner, igual que un script sin @requiere.
$r = correrRunner($runner, [
    '--dir=' . $fixtures . '/con-etiqueta',
    '--dir-unit=' . $fixtures . '/unit-sin-grupo',
    '--nivel=puro',
]);
verificar('una clase PHPUnit sin grupo devuelve 2', $r['codigo'] === 2);
verificar('el error nombra la clase sin grupo', str_contains($r['salida'], 'SinGrupoTest.php'));
```

- [ ] **Step 3: Correr y verificar que falla**

```bash
$EXEC php tests/test_php_test_runner.php
```

Esperado: FAIL en las dos nuevas.

- [ ] **Step 4: Implementar el descubrimiento**

En `scripts/run-php-tests.php`: recorrer `<dir-unit>/*Test.php` (no recursivo, como el otro glob),
extraer el nivel con
`preg_match('/#\[Group\(\s*[\'"]([a-z-]+)[\'"]\s*\)\]/', $cabecera)` sobre las primeras 40 líneas.
Sin grupo, o con un grupo que no sea uno de los cuatro niveles, se aborta con 2 nombrando el archivo.
`--dir-unit` por defecto es `<raíz>/tests/unit`.

- [ ] **Step 5: Correr y verificar que pasa**

```bash
$EXEC php tests/test_php_test_runner.php
```

Esperado: todas en PASS.

- [ ] **Step 6: Commit**

```bash
git add scripts/run-php-tests.php tests/test_php_test_runner.php tests/fixtures/runner/unit-sin-grupo
git commit -m "feat(qa): el runner exige grupo de nivel a las clases PHPUnit"
```

---

### Task 3: El runner invoca PHPUnit y agrega su resultado

**Files:**
- Modify: `scripts/run-php-tests.php`
- Modify: `tests/test_php_test_runner.php`
- Create: `tests/fixtures/runner/unit-rojo/RojoTest.php`
- Create: `tests/fixtures/runner/unit-verde/VerdeTest.php`

**Interfaces:**
- Produces: el runner ejecuta `vendor/bin/phpunit --group=<niveles seleccionados>` y suma su código de salida al agregado. El resumen dice cuántos tests PHPUnit corrieron.

- [ ] **Step 1: Crear los dos fixtures**

`VerdeTest.php` con `#[Group('puro')]` y una aserción que pasa; `RojoTest.php` con `#[Group('puro')]`
y una que falla (`self::assertSame(1, 2)`).

- [ ] **Step 2: Añadir las comprobaciones**

```php
// Un test PHPUnit verde no altera el resultado; uno rojo hace fallar al runner.
$r = correrRunner($runner, [
    '--dir=' . $fixtures . '/con-etiqueta',
    '--dir-unit=' . $fixtures . '/unit-verde',
    '--nivel=puro',
]);
verificar('con PHPUnit en verde el runner sale 0', $r['codigo'] === 0);
verificar('el resumen cuenta los tests PHPUnit', str_contains($r['salida'], 'PHPUnit'));

$r = correrRunner($runner, [
    '--dir=' . $fixtures . '/con-etiqueta',
    '--dir-unit=' . $fixtures . '/unit-rojo',
    '--nivel=puro',
]);
verificar('un test PHPUnit rojo hace fallar al runner', $r['codigo'] === 1);
```

- [ ] **Step 3: Correr y verificar que falla**

Esperado: FAIL en las tres nuevas.

- [ ] **Step 4: Implementar la invocación**

Construir la lista de grupos a partir de los niveles seleccionados y ejecutar
`vendor/bin/phpunit --group=<g1,g2,…>` con el mismo mecanismo de subproceso que ya usa
`ejecutarTest()`, desde la raíz del repositorio. Si `tests/unit` (o `--dir-unit`) no selecciona
ninguna clase para el nivel pedido, no se invoca PHPUnit y se dice en el resumen.

Para los fixtures hace falta pasarle a PHPUnit un directorio distinto al de `phpunit.xml`: se
invoca con `--no-configuration` y la ruta del directorio cuando `--dir-unit` es explícito, y con la
configuración del repositorio cuando es el valor por defecto.

- [ ] **Step 5: Correr y verificar que pasa**

Esperado: todas en PASS.

- [ ] **Step 6: Commit**

```bash
git add scripts/run-php-tests.php tests/test_php_test_runner.php tests/fixtures/runner/unit-verde tests/fixtures/runner/unit-rojo
git commit -m "feat(qa): el runner ejecuta PHPUnit junto a los scripts, en una sola pasada"
```

---

### Task 4: PHPUnit ausente no puede dar verde

**Files:**
- Modify: `scripts/run-php-tests.php`
- Modify: `tests/test_php_test_runner.php`

**Interfaces:**
- Produces: opción `--phpunit=<ruta>` para poder apuntar a un binario inexistente en la prueba. Si el nivel pedido selecciona tests PHPUnit y el binario no existe, el runner sale 2.

- [ ] **Step 1: Añadir la comprobación**

```php
// Sin el binario de PHPUnit y con tests suyos en el nivel, el runner aborta: no da verde.
$r = correrRunner($runner, [
    '--dir=' . $fixtures . '/con-etiqueta',
    '--dir-unit=' . $fixtures . '/unit-verde',
    '--nivel=puro',
    '--phpunit=/ruta/que/no/existe/phpunit',
]);
verificar('sin el binario de PHPUnit el runner aborta con 2', $r['codigo'] === 2);
verificar('el error explica que falta PHPUnit', stripos($r['salida'], 'phpunit') !== false);
verificar('la ausencia de PHPUnit no se reporta como verde', stripos($r['salida'], 'OK:') === false);

// Pero si el nivel no selecciona ningún test PHPUnit, su ausencia da igual.
$r = correrRunner($runner, [
    '--dir=' . $fixtures . '/con-etiqueta',
    '--dir-unit=' . $fixtures . '/unit-vacio',
    '--nivel=puro',
    '--phpunit=/ruta/que/no/existe/phpunit',
]);
verificar('sin tests PHPUnit seleccionados, su ausencia no estorba', $r['codigo'] === 0);
```

Crear también `tests/fixtures/runner/unit-vacio/.gitkeep`.

- [ ] **Step 2: Correr y verificar que falla**

- [ ] **Step 3: Implementar**

Antes de invocar, comprobar `is_executable($rutaPhpunit)`. Si no lo es y hay clases seleccionadas,
abortar con 2 y un mensaje que diga qué falta y por qué eso no es un verde.

- [ ] **Step 4: Correr y verificar que pasa**

- [ ] **Step 5: Commit**

```bash
git add scripts/run-php-tests.php tests/test_php_test_runner.php tests/fixtures/runner/unit-vacio
git commit -m "feat(qa): sin PHPUnit instalado el runner aborta en vez de dar verde"
```

---

### Task 5: El CI construye el job estático con dependencias de desarrollo

**Files:**
- Modify: `.github/workflows/design-system.yml`

- [ ] **Step 1: Cambiar el paso de build**

```yaml
      # --build-arg COMPOSER_INSTALL_FLAGS="" para que la imagen traiga PHPUnit, que es
      # dependencia de desarrollo. No se toca el Dockerfile ni docker-compose.ci.yml: sus
      # tres líneas están fijadas por tests/design-system/visual-ci-contract.test.mjs:143-145.
      - name: Build the PHP test runtime
        run: docker compose -f docker-compose.yml build --build-arg COMPOSER_INSTALL_FLAGS="" app
```

- [ ] **Step 2: Verificar el comando exacto en local, con build fresco**

```bash
COMPOSE_PROJECT_NAME=lps-aia-static-wt-aa8725 docker compose -f docker-compose.yml build --build-arg COMPOSER_INSTALL_FLAGS="" app
COMPOSE_PROJECT_NAME=lps-aia-static-wt-aa8725 docker compose -f docker-compose.yml run --rm --no-deps app php scripts/run-php-tests.php --nivel=puro
echo "rc=$?"
```

Esperado: `rc=0`, y el resumen incluye los tests PHPUnit del nivel `puro`.

- [ ] **Step 3: Medir cuánto tarda el build**

Es el riesgo declarado en el spec. Anotar el tiempo real para el informe.

- [ ] **Step 4: Correr la suite estática completa**

Es la lección de `memoria/trampas/el-archivo-que-tocas-puede-tener-un-contrato.md`: tres contratos
vigilan este workflow.

```bash
DS_ACTIVATION_STRICT=1 npm run test:design-system:static
```

Esperado: los 8 gates en verde, `RC=0`.

- [ ] **Step 5: Commit**

---

### Task 6: Cierre — verificación, wiki y gate

- [ ] **Step 1: Verificar la condición de hecho completa, con salida real**

Los seis puntos del spec, cada uno con su comando y su código de salida leído **sin tubería**.

- [ ] **Step 2: Poner en rojo cada guardarraíl nuevo, ejecutado**

Regla heredada de `docs/coordinacion-sesiones.md`: un gate se entrega con la mutación que lo pone
rojo. Son tres: clase sin grupo, PHPUnit rojo, PHPUnit ausente.

- [ ] **Step 3: Actualizar `memoria/mapas/qa-y-gates.md`**

La línea que dice «no hay PHPUnit —sigue sin haberlo—» deja de ser cierta. Documentar la
convivencia, dónde va cada cosa y el criterio para elegir. Añadir la línea de bitácora en
`memoria/log.md`.

- [ ] **Step 4: `npm run test:wiki`**

Esperado: verde salvo los 2 hallazgos preexistentes de `estado.md`.

- [ ] **Step 5: Gate de cierre de frente**

Los ocho pasos de `AGENTS.md` §Publicación. El paso 5 —re-verificar **después** de integrar— incluye
esta vez la suite estática completa, no sólo el runner.

## Lo que va a la cola de decisiones

No es una tarea: es lo que **no** se decide aquí. Si los tests nuevos **deben** escribirse en PHPUnit
de ahora en adelante o los dos estilos conviven indefinidamente es política de equipo, no una
decisión técnica. Se anota en `docs/decisiones-pendientes.md` como `D-CI-2` y se sigue.
