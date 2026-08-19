---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-08-10
areas: [qa]
tags: [archivo]
fuente: docs/archive/superpowers/plans/2026-08-10-runner-tests-php.md
resumen: Dar a los 99 tests/test.php un runner único con código de salida correcto, categorías de entorno, y conectarlo al CI en lugar de los tres tests listados a mano.
---

# Runner de tests PHP — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dar a los 99 `tests/test_*.php` un runner único con código de salida correcto, categorías de entorno, y conectarlo al CI en lugar de los tres tests listados a mano.

**Architecture:** Un script PHP (`scripts/run-php-tests.php`) ejecutado dentro del contenedor `app`. Descubre `tests/test_*.php`, lee de cada uno una etiqueta `// @requiere: <nivel>`, comprueba que el entorno de ese nivel está disponible, lanza cada test como subproceso y agrega los códigos de salida. El runner se prueba a sí mismo con un test escrito en la convención del repo, contra un directorio de fixtures.

**Tech Stack:** PHP 8.3 (sin PHPUnit — es la fase 2), Docker Compose, GitHub Actions.

## Global Constraints

- **Sin PHPUnit.** La fase 2 lo introduce; este plan no.
- **PHP puro**, sin dependencias nuevas en `composer.json` (`require`/`require-dev` quedan igual).
- **Todo PHP corre dentro del contenedor `app`**, nunca con un PHP del host.
- **Se mide por código de salida, nunca por grep del texto.** Las líneas `FAIL:` van sangradas y `grep -cE "^FAIL"` da 0 falsos (`memoria/trampas/suite-php-rojos-preexistentes.md`).
- **`timeout` no existe en macOS.** No usarlo en comandos de verificación local.
- **Niveles válidos, en orden de exigencia:** `puro` < `db` < `http` < `datos-proyecto`.
- **Ausencia de entorno es error del runner, nunca un verde.**
- **No se toca ningún test** salvo su línea `@requiere` y, en el único caso ciego, sus aserciones.
- **No se enriquece el fixture de CI** para forzar verdes.
- Idioma del repo: español en comentarios, mensajes y documentación.

## Entorno de verificación

Todas las verificaciones locales usan el stack aislado ya levantado en este worktree, **nunca** el
contenedor compartido `last-planner-aia-app-1` (monta el árbol principal, donde trabaja otra sesión):

```bash
export COMPOSE_PROJECT_NAME=lps-aia-runner-wt-aa8725 CI_RUN_ID=wt-aa8725
eval "$(node scripts/design-system-ci-preflight.mjs --print-provenance)"
export CI_GIT_SHA CI_WORKTREE_FINGERPRINT CI_FIXTURE_SHA256
```

Con eso, el prefijo de ejecución es:

```bash
docker compose -p "$COMPOSE_PROJECT_NAME" -f docker-compose.yml -f docker-compose.ci.yml exec -T app
```

En el plan se abrevia como `$EXEC`.

## Estructura de archivos

| Archivo | Responsabilidad |
|---|---|
| `scripts/run-php-tests.php` (crear) | El runner: descubrir, etiquetar, comprobar entorno, ejecutar, agregar |
| `tests/test_php_test_runner.php` (crear) | Prueba del runner, en la convención del repo, nivel `puro` |
| `tests/fixtures/runner/*` (crear) | Tests de mentira para probar el runner sin tocar los reales |
| `tests/test_*.php` (modificar) | Una línea `// @requiere:` en cada uno de los 99 |
| `tests/test_pi_shared_payload_smoke.php` (modificar) | Añadir aserciones reales |
| `composer.json` (modificar) | `scripts.test` |
| `.github/workflows/design-system.yml` (modificar) | Dos pasos con el runner; retirar los tres listados a mano |
| `memoria/mapas/qa-y-gates.md` (modificar) | Registrar que la suite PHP ya tiene runner |

---

### Task 1: El runner descubre tests y exige etiqueta

**Files:**
- Create: `scripts/run-php-tests.php`
- Create: `tests/test_php_test_runner.php`
- Create: `tests/fixtures/runner/con-etiqueta/test_ok.php`
- Create: `tests/fixtures/runner/sin-etiqueta/test_sin.php`

**Interfaces:**
- Produces: `scripts/run-php-tests.php`, invocable con `--dir=<ruta>`, `--nivel=<nivel>`, `--timeout=<segundos>` y `--solo-listar` (enumera la selección sin ejecutarla). Salida 0 si todo pasa; 1 si algún test falla; 2 si el runner no puede operar (etiqueta ausente, nivel inválido, entorno no disponible).

- [ ] **Step 1: Crear los fixtures**

`tests/fixtures/runner/con-etiqueta/test_ok.php`:

```php
<?php

declare(strict_types=1);

// @requiere: puro

echo "OK: 1 comprobaciones pasaron\n";
exit(0);
```

`tests/fixtures/runner/sin-etiqueta/test_sin.php`:

```php
<?php

declare(strict_types=1);

echo "OK\n";
exit(0);
```

- [ ] **Step 2: Escribir la prueba que falla**

`tests/test_php_test_runner.php`:

```php
<?php

declare(strict_types=1);

// @requiere: puro

/**
 * Verifica el contrato de scripts/run-php-tests.php: descubrimiento, etiqueta
 * obligatoria y código de salida.
 */

$total = 0;
$fallos = 0;

function verificar(string $descripcion, bool $condicion): void
{
    global $total, $fallos;
    $total++;
    if ($condicion) {
        echo "  PASS: {$descripcion}\n";
        return;
    }
    $fallos++;
    echo "  FAIL: {$descripcion}\n";
}

$runner = __DIR__ . '/../scripts/run-php-tests.php';
$fixtures = __DIR__ . '/fixtures/runner';

function correrRunner(string $runner, array $args): array
{
    $comando = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($runner);
    foreach ($args as $arg) {
        $comando .= ' ' . escapeshellarg($arg);
    }
    $salida = [];
    $codigo = 0;
    exec($comando . ' 2>&1', $salida, $codigo);

    return ['codigo' => $codigo, 'salida' => implode("\n", $salida)];
}

// Un directorio con un test etiquetado y verde sale 0.
$r = correrRunner($runner, ['--dir=' . $fixtures . '/con-etiqueta', '--nivel=puro']);
verificar('un test etiquetado y verde devuelve 0', $r['codigo'] === 0);
verificar('el resumen dice cuantos corrieron', str_contains($r['salida'], '1'));

// Un test sin etiqueta rompe el runner con codigo 2.
$r = correrRunner($runner, ['--dir=' . $fixtures . '/sin-etiqueta', '--nivel=puro']);
verificar('un test sin etiqueta devuelve 2', $r['codigo'] === 2);
verificar('el error nombra el archivo sin etiqueta', str_contains($r['salida'], 'test_sin.php'));

// Un nivel inventado se rechaza.
$r = correrRunner($runner, ['--dir=' . $fixtures . '/con-etiqueta', '--nivel=inventado']);
verificar('un nivel invalido devuelve 2', $r['codigo'] === 2);

echo "\n";
if ($fallos > 0) {
    echo "FAIL: {$fallos} de {$total} comprobaciones fallaron\n";
    exit(1);
}
echo "OK: {$total} comprobaciones pasaron\n";
exit(0);
```

- [ ] **Step 3: Correrla y verificar que falla**

```bash
$EXEC php tests/test_php_test_runner.php
```

Esperado: FAIL — `scripts/run-php-tests.php` no existe todavía.

- [ ] **Step 4: Escribir el runner mínimo**

`scripts/run-php-tests.php`. Responsabilidades de esta tarea: parsear argumentos, descubrir `test_*.php` en `--dir`, extraer `// @requiere: <nivel>` de las primeras 40 líneas, rechazar archivos sin etiqueta y niveles inválidos, ejecutar los tests del nivel pedido y los de niveles inferiores, agregar códigos de salida e imprimir resumen. Sin comprobación de entorno todavía — eso es la Task 2.

Puntos de contrato que el implementador no debe inventar:

- Niveles y orden: `['puro' => 0, 'db' => 1, 'http' => 2, 'datos-proyecto' => 3]`.
- `--nivel=X` selecciona los tests cuyo peso sea **menor o igual** al de X.
- Sin `--nivel`, el valor por defecto es `puro`.
- La etiqueta se busca con `preg_match('/^\s*\/\/\s*@requiere:\s*([a-z-]+)\s*$/m', ...)` sobre las primeras 40 líneas del archivo.
- Cada test se lanza como subproceso con `PHP_BINARY`, capturando stdout y stderr juntos.
- Códigos de salida del runner: 0 todo verde, 1 algún test falló, 2 el runner no puede operar.
- El resumen final imprime: cuántos corrieron, cuántos pasaron, cuántos fallaron y cuántos se omitieron por estar por encima del nivel pedido.

- [ ] **Step 5: Correr la prueba y verificar que pasa**

```bash
$EXEC php tests/test_php_test_runner.php
```

Esperado: `OK: 5 comprobaciones pasaron`

- [ ] **Step 6: Commit**

```bash
git add scripts/run-php-tests.php tests/test_php_test_runner.php tests/fixtures/runner
git commit -m "feat(qa): runner de tests PHP con etiqueta de nivel obligatoria"
```

---

### Task 2: El runner comprueba el entorno antes de correr

Es el requisito que nace del hallazgo medido: 26 tests salen 0 sin base de datos. Sin esta tarea, el
runner daría verde en 26 casos que no comprobaron nada.

**Files:**
- Modify: `scripts/run-php-tests.php`
- Modify: `tests/test_php_test_runner.php`

**Interfaces:**
- Consumes: el runner de la Task 1.
- Produces: funciones internas `entornoDisponible(string $nivel): ?string` (devuelve `null` si el entorno está, o un mensaje explicando qué falta) y su uso antes de ejecutar cualquier test.

- [ ] **Step 1: Añadir las comprobaciones a la prueba**

Añadir a `tests/test_php_test_runner.php`, antes del bloque final de resumen:

```php
// Pedir un nivel db sin base de datos alcanzable aborta con 2, no da verde.
$r = correrRunner($runner, [
    '--dir=' . $fixtures . '/con-etiqueta',
    '--nivel=db',
    '--db-host=host.invalido.imposible',
]);
verificar('sin base de datos, el nivel db aborta con 2', $r['codigo'] === 2);
verificar('el error explica que falta la base', stripos($r['salida'], 'base de datos') !== false);
verificar('la ausencia de entorno no se reporta como verde', stripos($r['salida'], 'OK:') === false);
```

- [ ] **Step 2: Correrla y verificar que falla**

```bash
$EXEC php tests/test_php_test_runner.php
```

Esperado: FAIL en las tres nuevas — hoy el runner ignora el entorno.

- [ ] **Step 3: Implementar la comprobación de entorno**

En `scripts/run-php-tests.php`:

- `puro`: siempre disponible.
- `db`: abrir `new PDO('mysql:host=…;port=…;dbname=…', usuario, clave)` con las variables de entorno `DB_HOST` (por defecto `db`), `DB_PORT` (`3306`), `DB_NAME`, `DB_USER`, `DB_PASS`, permitiendo sobrescribir el host con `--db-host=`. Si la conexión lanza, devolver el mensaje `"no hay base de datos alcanzable: <motivo>"`.
- `http`: pedir `http://127.0.0.1/login` con un contexto de 5 segundos. Si no responde, devolver `"la aplicacion no responde por HTTP: <motivo>"`.
- `datos-proyecto`: exige lo mismo que `http`.

La comprobación se hace **una vez por nivel requerido** antes de ejecutar ningún test, y si falla el
runner sale con 2 sin ejecutar nada.

- [ ] **Step 4: Correr la prueba y verificar que pasa**

```bash
$EXEC php tests/test_php_test_runner.php
```

Esperado: `OK: 8 comprobaciones pasaron`

- [ ] **Step 5: Commit**

```bash
git add scripts/run-php-tests.php tests/test_php_test_runner.php
git commit -m "feat(qa): el runner aborta si falta el entorno en vez de dar verde"
```

---

### Task 3: El runner marca el verde sin respaldo

**Files:**
- Modify: `scripts/run-php-tests.php`
- Modify: `tests/test_php_test_runner.php`
- Create: `tests/fixtures/runner/mudo/test_mudo.php`

**Interfaces:**
- Produces: el resumen del runner incluye una sección `SOSPECHOSOS` cuando un test sale 0 sin imprimir ninguna señal de comprobación.

- [ ] **Step 1: Crear el fixture mudo**

`tests/fixtures/runner/mudo/test_mudo.php`:

```php
<?php

declare(strict_types=1);

// @requiere: puro

// Sale verde sin comprobar nada: exactamente el patron que hay que cazar.
exit(0);
```

- [ ] **Step 2: Añadir la comprobación a la prueba**

```php
// Un test que sale 0 sin decir nada se reporta como sospechoso y no da verde global.
$r = correrRunner($runner, ['--dir=' . $fixtures . '/mudo', '--nivel=puro']);
verificar('un verde sin respaldo se marca sospechoso', str_contains($r['salida'], 'SOSPECHOSO'));
verificar('un verde sin respaldo no deja el runner en 0', $r['codigo'] !== 0);
```

- [ ] **Step 3: Correrla y verificar que falla**

```bash
$EXEC php tests/test_php_test_runner.php
```

Esperado: FAIL en las dos nuevas.

- [ ] **Step 4: Implementar la detección**

Un test se considera sospechoso cuando su código de salida es 0 **y** su salida combinada no
contiene ninguna de estas señales, comparadas sin distinguir mayúsculas:

```php
const SENALES_DE_COMPROBACION = ['pass', 'ok', 'comprobacion', 'comprobación', '✓', 'correcto'];
```

Los sospechosos se listan aparte en el resumen y hacen que el runner salga 1.

- [ ] **Step 5: Correr la prueba y verificar que pasa**

```bash
$EXEC php tests/test_php_test_runner.php
```

Esperado: `OK: 10 comprobaciones pasaron`

- [ ] **Step 6: Verificar que la heurística no acusa a los tests reales**

Comprobación empírica, no de inspección: los 99 tests reales no deben aparecer como sospechosos
salvo el ciego conocido.

```bash
$EXEC php scripts/run-php-tests.php --nivel=puro 2>&1 | grep -A20 SOSPECHOSO
```

Esperado: como mucho `test_pi_shared_payload_smoke.php`. Si aparece cualquier otro, ampliar
`SENALES_DE_COMPROBACION` con la señal que ese test sí imprime y repetir.

- [ ] **Step 7: Commit**

```bash
git add scripts/run-php-tests.php tests/test_php_test_runner.php tests/fixtures/runner/mudo
git commit -m "feat(qa): el runner caza el verde sin respaldo"
```

---

### Task 4: Etiquetar los 99 tests

**Files:**
- Modify: los 99 `tests/test_*.php`

**Interfaces:**
- Consumes: el runner de las Tasks 1-3.
- Produces: los 99 archivos con `// @requiere: <nivel>`, de modo que `--nivel=http` seleccione 71 y `datos-proyecto` los 28 restantes.

- [ ] **Step 1: Partir de la medición ya hecha**

Los 28 de `datos-proyecto` están medidos y listados en el spec
(`docs/superpowers/specs/2026-08-10-runner-tests-php-design.md`, Hallazgo 3). Los 71 restantes se
reparten entre `puro`, `db` y `http`.

Los 6 que necesitan la aplicación viva, medidos: `test_admin_dev_door_guard`,
`test_csrf_modulos_api`, `test_dev_door_http`, `test_indicadores_server_gate`,
`test_pg_pasado_servidor`, `test_semanal_sanear_csrf`. De ellos, `test_csrf_modulos_api`,
`test_indicadores_server_gate` y `test_pg_pasado_servidor` están además en los 28, así que su
etiqueta es `datos-proyecto`.

- [ ] **Step 2: Etiquetar por lo que el test necesita, no por lo que sobrevive**

Criterio, en este orden:

1. ¿Está en la lista de 28 del spec? → `datos-proyecto`.
2. ¿Pide la aplicación por HTTP (`http://`, `curl_`, `file_get_contents` sobre una URL)? → `http`.
3. ¿Abre una conexión (`Database::getInstance()`, `new PDO`, `->query(`, `->prepare(`)? → `db`.
4. En otro caso → `puro`.

El paso 3 es la trampa: 26 de esos tests **sobreviven sin base de datos** capturando el error. Se
etiquetan `db` igual, porque es lo que necesitan para ser válidos.

- [ ] **Step 3: Insertar la etiqueta**

Va como línea propia tras `declare(strict_types=1);`, o tras `<?php` si el archivo no la tiene.

- [ ] **Step 4: Verificar que no queda ninguno sin etiqueta**

```bash
$EXEC php scripts/run-php-tests.php --nivel=datos-proyecto --solo-listar
```

Esperado: sale 0 y lista los 99 repartidos por nivel. Si alguno no tiene etiqueta, sale 2 y lo
nombra.

- [ ] **Step 5: Verificar la asignación corriendo cada nivel en un entorno que sólo ofrece ese nivel**

Es la mitigación del riesgo «la etiqueta se asigna mal». Un `puro` que en realidad necesita base de
datos falla aquí, porque el contenedor efímero no tiene red al servicio `db`:

```bash
docker run --rm -v "$PWD:/var/www/html" -w /var/www/html last-planner-aia-app:latest \
  php scripts/run-php-tests.php --nivel=puro
```

Esperado: sale 0. Si algún test falla, su etiqueta está mal: corregirla a `db` y repetir.

- [ ] **Step 6: Verificar el nivel http completo**

```bash
$EXEC php scripts/run-php-tests.php --nivel=http
```

Esperado: sale 0, con 71 corridos y 28 omitidos por nivel.

- [ ] **Step 7: Commit**

```bash
git add tests/
git commit -m "chore(qa): etiquetar los 99 tests con el entorno que necesitan"
```

---

### Task 5: Dar aserciones al único test ciego

**Files:**
- Modify: `tests/test_pi_shared_payload_smoke.php`

**Interfaces:**
- Consumes: el detector de sospechosos de la Task 3.
- Produces: el test deja de aparecer en `SOSPECHOSOS` y falla de verdad cuando su expectativa no se cumple.

- [ ] **Step 1: Leer las expectativas ya escritas**

El archivo llama a `runTest(...)` con rótulos que contienen la expectativa: `(debe FALLAR)`,
`(debe OK, sub vacío en respAia)`, `(debe OK)`. Hoy sólo imprime `ok: true/false` y termina en 0.

- [ ] **Step 2: Convertir el rótulo en aserción**

`runTest` recibe un parámetro nuevo `bool $seEspera` y compara contra el `ok` real, llevando
contadores `$total` y `$fallos` como el resto de la suite. Al cerrar:

```php
echo "\n";
if ($fallos > 0) {
    echo "FAIL: {$fallos} de {$total} comprobaciones fallaron\n";
    exit(1);
}
echo "OK: {$total} comprobaciones pasaron\n";
exit(0);
```

- [ ] **Step 3: Verificar que pasa cuando debe**

```bash
$EXEC php tests/test_pi_shared_payload_smoke.php; echo "rc=$?"
```

Esperado: `rc=0` y un `OK: N comprobaciones pasaron`.

- [ ] **Step 4: Verificar que ahora sabe fallar**

Invertir temporalmente una expectativa en el archivo, correr, confirmar `rc=1`, y **deshacer la
inversión**. Sin este paso no está demostrado que el test pueda fallar.

```bash
$EXEC php tests/test_pi_shared_payload_smoke.php; echo "rc=$?"
git checkout -- tests/test_pi_shared_payload_smoke.php  # sólo si se dejó invertido
```

- [ ] **Step 5: Verificar que ya no es sospechoso**

```bash
$EXEC php scripts/run-php-tests.php --nivel=http 2>&1 | grep -c SOSPECHOSO
```

Esperado: `0`.

- [ ] **Step 6: Commit**

```bash
git add tests/test_pi_shared_payload_smoke.php
git commit -m "fix(qa): el smoke de payload compartido comprueba en vez de imprimir"
```

---

### Task 6: Conectar el CI y dar el atajo

**Files:**
- Modify: `composer.json`
- Modify: `.github/workflows/design-system.yml`

**Interfaces:**
- Consumes: el runner completo de las Tasks 1-5.
- Produces: el CI ejecuta el runner en dos puntos y ya no lista tests a mano.

- [ ] **Step 1: Añadir el atajo en composer.json**

`"scripts": {}` está vacío. Pasa a:

```json
"scripts": {
    "test": "php scripts/run-php-tests.php --nivel=http"
}
```

- [ ] **Step 2: Añadir el paso al job estático**

En `design-system.yml`, job `design-system-static`, después de «Build the PHP test runtime»:

```yaml
      - name: Correr los tests PHP que no necesitan entorno
        run: docker compose run --rm --no-deps app php scripts/run-php-tests.php --nivel=puro
```

- [ ] **Step 3: Sustituir los tres pasos manuales en el job runtime**

Retirar los tres pasos «Enforce global-table safety», «Enforce dev-door guard (prod must stay
closed)» y «Enforce dev-door end-to-end behaviour», y poner en su lugar:

```yaml
      - name: Correr la suite PHP completa que el CI puede honrar
        run: docker compose -p "$COMPOSE_PROJECT_NAME" -f docker-compose.yml -f docker-compose.ci.yml exec -T app php scripts/run-php-tests.php --nivel=http
```

Los tres tests retirados quedan cubiertos: son `db`/`http` y entran en la selección. Confirmarlo en
la salida del runner antes de dar el paso por bueno.

- [ ] **Step 4: Verificar localmente el comando exacto del job runtime**

```bash
$EXEC php scripts/run-php-tests.php --nivel=http; echo "rc=$?"
```

Esperado: `rc=0`, y en el resumen aparecen `test_global_table_safety.php`, `test_dev_door_guard.php`
y `test_dev_door_http.php` entre los corridos.

- [ ] **Step 5: Verificar localmente el comando exacto del job estático**

```bash
docker compose run --rm --no-deps app php scripts/run-php-tests.php --nivel=puro; echo "rc=$?"
```

Esperado: `rc=0`.

- [ ] **Step 6: Medir cuánto tarda, para el riesgo «el CI se alarga»**

```bash
time $EXEC php scripts/run-php-tests.php --nivel=http
```

Anotar el tiempo real en el informe de cierre.

- [ ] **Step 7: Commit**

```bash
git add composer.json .github/workflows/design-system.yml
git commit -m "ci(qa): el CI corre el runner en vez de tres tests listados a mano"
```

---

### Task 7: Cierre — verificación y wiki

**Files:**
- Modify: `memoria/mapas/qa-y-gates.md`

- [ ] **Step 1: Verificar la condición de hecho completa, con salida real**

```bash
$EXEC php scripts/run-php-tests.php --nivel=puro;           echo "puro rc=$?"
$EXEC php scripts/run-php-tests.php --nivel=http;           echo "http rc=$?"
$EXEC php scripts/run-php-tests.php --nivel=datos-proyecto; echo "datos rc=$?"
```

Esperado: `puro rc=0`, `http rc=0`. El tercero **no** tiene por qué salir 0: son los 28 declarados,
y su rojo es información, no regresión. Anotar su número real.

- [ ] **Step 2: Verificar que el runner sigue exigiendo etiqueta**

Prueba viva del guardarraíl: crear un test sin etiqueta, comprobar que el runner sale 2, borrarlo.

```bash
printf '<?php\nexit(0);\n' > tests/test_zz_sin_etiqueta_temporal.php
$EXEC php scripts/run-php-tests.php --nivel=puro; echo "rc=$?"   # esperado: 2
rm tests/test_zz_sin_etiqueta_temporal.php
```

- [ ] **Step 3: Actualizar el mapa de la wiki**

En `memoria/mapas/qa-y-gates.md`, la línea de la sección «Las suites» que dice que los tests PHP se
corren uno a uno pasa a describir el runner, su comando y los cuatro niveles, con la fecha del
cambio. Añadir una línea sobre el falso verde por ausencia de base de datos, que es la trampa nueva
que este frente destapó.

- [ ] **Step 4: Correr el lint de la wiki**

```bash
npm run test:wiki
```

Esperado: verde.

- [ ] **Step 5: Commit**

```bash
git add memoria/
git commit -m "docs(memoria): la suite PHP ya tiene runner y niveles de entorno"
```

---

## Gate de cierre de frente

Los ocho pasos de `AGENTS.md` §Publicación, en orden, sin saltarse ninguno. En particular el paso 5:
**re-verificar después de integrar `origin/main`, no antes** — traer trabajo ajeno puede romper un
verde propio sin tocar el diff. Hay otra sesión escribiendo a `origin/main` en esta misma jornada,
así que la divergencia es probable, no hipotética.
