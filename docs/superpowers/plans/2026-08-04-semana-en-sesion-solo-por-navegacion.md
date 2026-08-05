# La semana en sesión solo la escribe una navegación — Plan de implementación

> **Para agentes ejecutores:** SUB-SKILL OBLIGATORIA: usa `superpowers:subagent-driven-development`
> (recomendado) o `superpowers:executing-plans` para ejecutar este plan tarea por tarea. Los pasos
> usan casillas (`- [ ]`) para el seguimiento.

**Objetivo:** que una petición de fondo con `?semana=` en la URL deje de reescribir la semana de la
sesión del usuario, y que solo la escriban las navegaciones de página, validando antes que la semana
exista en el proyecto.

**Arquitectura:** hoy `SessionMiddleware::check()` corre en TODAS las peticiones y escribe
`$_SESSION['semana']` con cualquier `?semana=` que vea, sin validar. Se elimina esa regla global y se
reparte la responsabilidad: cada controlador de **página** que debe honrar la semana de la URL llama
a `BaseController::syncRequestedWeekContext()`, que ya existe y ya valida contra `semanas_activas`.
Las rutas `/api/*` dejan de poder escribir la semana: ya reciben `?semana=` como dato y la leen ellas
mismas del request.

**Stack:** PHP 8.3 en Docker (`docker compose exec app`), tests PHP autoejecutables en `tests/`,
Playwright para la suite de navegador.

## Restricciones globales

- Todo PHP se ejecuta dentro del contenedor `app`. Nunca un PHP del host.
- La sesión local se abre **siempre** por la puerta de servicio:
  `http://localhost:8081/dev/entrar?u=test.R&p=Prueba`. Nunca `/login`, nunca pedirle a una persona
  que inicie sesión.
- Validación en navegador: desktop **1180×820, dark mode únicamente**. Ni móvil, ni tablet, ni tema
  `linen`.
- Proyectos mutables para pruebas: **«Prueba» (id 27)** y el sandbox del PDC. «Da Porto» y
  «Optimización Aeropuerto JMC» son **solo lectura** — no se tocan.
- No se relaja ni se borra ninguna aserción de test existente.
- Un solo cambio por commit; no hay refactors de cortesía.

## Hechos medidos (2026-08-04, contra el stack local)

Sirven de línea base; no hay que volver a medirlos:

| Secuencia | Semana que queda |
|---|---|
| `POST /context/week {4}` → `GET /api/semanal/list?db=prueba&semana=7` | 7 ❌ |
| `POST /context/week {4}` → `GET /api/general/restriction-config?semana=7` | 7 ❌ |
| `POST /context/week {4}` → misma ruta sin `?semana=` | 4 ✅ |
| `POST /context/week {4}` → `GET /listado-actividades?semana=4` (**404**) | 4 ❗ |

La última fila importa: el middleware escribe **antes** del dispatch, así que hasta un 404 cambia la
semana. (`/listado-actividades` y `/contratos` responden 404 hoy; el menú de
`public/js/modules/info_general_nav.js` sigue enlazándolos. Eso es **otro** problema y queda fuera de
este plan.)

Peticiones de fondo que hoy pisan la semana al aterrizar en Programación Semanal:
`GET /api/semanal/list?db=…&semana=N`, `POST /api/semanal/auto-program?db=…&semana=N`,
`POST /api/pi/save?db=…&semana=N`, `POST /api/general/import?db=…&semana=N`,
`POST /api/pdc/list?db=…&semana=N`, `POST /api/pdc/auto/apply-from-contratos?db=…&semana=N`.

## Estructura de archivos

| Archivo | Responsabilidad tras el cambio |
|---|---|
| `src/Core/SessionMiddleware.php` | Deja de escribir la semana. Solo sesión, timeout y contexto de proyecto. |
| `src/Controllers/BaseController.php` | Sin cambios. `syncRequestedWeekContext()` ya hace lo correcto. |
| `src/Controllers/Programacion/ProgramaGeneralController.php` | Página: honra `?semana=` explícitamente. |
| `src/Controllers/Programacion/ProgramacionSemanalController.php` | Página (×4: index, cnp, cnc, cic). |
| `src/Controllers/Programacion/ProgramacionIntermediaController.php` | Página (`index`) honra; API (`save`) deja de escribir. |
| `src/Controllers/Gestion/IndicadoresController.php` | Página: honra `?semana=`. |
| `tests/test_week_context_write_scope.php` | **Nuevo.** Fija el contrato: quién puede escribir la semana. |
| `tests/browser/support/session.mjs` | Se retira la espera `networkidle` que tapaba el síntoma. |

`/pdc` ya llama a `syncRequestedWeekContext()` y no se toca. `/reportes/{tipo}` y `/bi/*` leen
`?semana=` por su cuenta como valor local y nunca dependieron del middleware: tampoco se tocan.

---

### Tarea 1: El middleware deja de escribir la semana

**Archivos:**
- Crear: `tests/test_week_context_write_scope.php`
- Modificar: `src/Core/SessionMiddleware.php:66-69`

**Interfaces:**
- Consume: nada.
- Produce: la garantía de que `SessionMiddleware::check()` no toca `$_SESSION['semana']`. Las tareas
  2 y 3 dependen de que esta escritura ya no exista.

- [ ] **Paso 1: escribir el test que falla**

Crear `tests/test_week_context_write_scope.php`. El test lee el **código fuente** del middleware en
vez de ejecutarlo, porque `check()` arranca sesión, consulta la base y hace `exit` en varias ramas:
montar todo eso costaría más que lo que mide, y lo que queremos fijar es justamente que la línea no
esté.

```php
<?php

declare(strict_types=1);

/**
 * Fija QUIÉN puede escribir la semana de la sesión.
 *
 * Hasta el 2026-08-04, `SessionMiddleware::check()` escribía `$_SESSION['semana']` con cualquier
 * `?semana=` de CUALQUIER petición, incluidas las XHR de fondo. Eso hacía que una petición rezagada
 * de la carga anterior devolviera al usuario a una semana que no pidió — medido en el navegador con
 * `/api/general/restriction-config?semana=7`, una ruta que no tiene nada que ver con semanas.
 *
 * La regla vigente: la semana de la URL solo la aplica un controlador de PÁGINA, vía
 * `BaseController::syncRequestedWeekContext()`, que además valida contra `semanas_activas`.
 */

require_once __DIR__ . '/../vendor/autoload.php';

$fallos = 0;
$total = 0;

function comprobar(string $descripcion, bool $condicion): void
{
    global $fallos, $total;
    $total++;
    if ($condicion) {
        echo "  OK   {$descripcion}\n";
        return;
    }
    $fallos++;
    echo "  FALLA {$descripcion}\n";
}

$raiz = dirname(__DIR__);

// 1. El middleware global no escribe la semana.
$middleware = (string) file_get_contents($raiz . '/src/Core/SessionMiddleware.php');
comprobar(
    'SessionMiddleware no asigna $_SESSION[\'semana\']',
    preg_match('/\$_SESSION\s*\[\s*[\'"]semana[\'"]\s*\]\s*=/', $middleware) !== 1,
);

// 2. Los controladores de página que honran ?semana= lo hacen por la vía validada.
$paginas = [
    'src/Controllers/Programacion/ProgramaGeneralController.php',
    'src/Controllers/Programacion/ProgramacionSemanalController.php',
    'src/Controllers/Programacion/ProgramacionIntermediaController.php',
    'src/Controllers/Gestion/IndicadoresController.php',
    'src/Controllers/Gestion/PdcController.php',
];
foreach ($paginas as $ruta) {
    $fuente = (string) file_get_contents($raiz . '/' . $ruta);
    comprobar(
        basename($ruta) . ' llama a syncRequestedWeekContext()',
        str_contains($fuente, 'syncRequestedWeekContext()'),
    );
}

// 3. Las APIs no persisten la semana del request.
$apis = [
    'src/Controllers/Api/SemanalApiController.php',
    'src/Controllers/Api/GeneralApiController.php',
];
foreach ($apis as $ruta) {
    $fuente = (string) file_get_contents($raiz . '/' . $ruta);
    comprobar(
        basename($ruta) . ' no asigna $_SESSION[\'semana\']',
        preg_match('/\$_SESSION\s*\[\s*[\'"]semana[\'"]\s*\]\s*=/', $fuente) !== 1,
    );
}

echo "\n{$total} comprobaciones, {$fallos} fallidas\n";
exit($fallos === 0 ? 0 : 1);
```

- [ ] **Paso 2: correrlo y verlo fallar**

```bash
docker compose exec app php tests/test_week_context_write_scope.php
```

Esperado: FALLA en «SessionMiddleware no asigna $_SESSION['semana']» y en los cuatro controladores de
página que aún no llaman a `syncRequestedWeekContext()` (PdcController sí pasa desde ya). Sale con
código 1.

- [ ] **Paso 3: borrar el bloque del middleware**

En `src/Core/SessionMiddleware.php`, eliminar estas cuatro líneas completas (66-69):

```php
        // Actualizar semana en sesión si viene por parámetro GET (patrón legacy)
        if (isset($_GET['semana'])) {
            $_SESSION['semana'] = (int) $_GET['semana'];
        }
```

No dejar comentario en su lugar: el porqué vive en el test y en este plan.

- [ ] **Paso 4: correr el test y ver pasar SOLO la primera comprobación**

```bash
docker compose exec app php tests/test_week_context_write_scope.php
```

Esperado: «OK SessionMiddleware no asigna…», y siguen fallando los cuatro controladores de página.
Eso es correcto — los arregla la tarea 2. Aún sale con código 1.

- [ ] **Paso 5: commit**

```bash
git add tests/test_week_context_write_scope.php src/Core/SessionMiddleware.php
git commit -m "fix(sesion): la semana de la URL deja de escribirse en cada peticion"
```

---

### Tarea 2: Las páginas recuperan el honrado de `?semana=`

**Archivos:**
- Modificar: `src/Controllers/Programacion/ProgramaGeneralController.php:14`
- Modificar: `src/Controllers/Programacion/ProgramacionSemanalController.php` (4 métodos: `index`,
  `cnp`, `cnc`, `cic` — cada uno tiene hoy `$this->requireAuth();` seguido de
  `$this->healWeeklyContext();`)
- Modificar: `src/Controllers/Programacion/ProgramacionIntermediaController.php:18`
- Modificar: `src/Controllers/Gestion/IndicadoresController.php:14`
- Test: `tests/test_week_context_write_scope.php` (ya existe, de la tarea 1)

**Interfaces:**
- Consume: `BaseController::syncRequestedWeekContext(): bool` — lee `$_GET['semana']`, exige entero
  ≥ 1, comprueba `SELECT COUNT(*) FROM semanas_activas WHERE project_id = ? AND Semana = ?` y solo
  entonces escribe `$_SESSION['semana']`. Devuelve `true` si escribió, `false` si ignoró.
- Produce: seis rutas de página que vuelven a responder a `?semana=`, ahora validando.

- [ ] **Paso 1: insertar la llamada en Programa General**

En `ProgramaGeneralController::index()`, **antes** de `healWeeklyContext()` — el orden importa:
primero se aplica lo que pidió la URL, después `healWeeklyContext()` sanea lo que quede.

```php
    public function index()
    {
        $this->requireAuth();
        $this->syncRequestedWeekContext();
        $this->healWeeklyContext();
```

- [ ] **Paso 2: insertar la llamada en los cuatro métodos de Programación Semanal**

En `ProgramacionSemanalController`, en `index()`, `cnp()`, `cnc()` y `cic()`. Los cuatro tienen el
mismo par de líneas; hay que tocar los cuatro, uno por uno:

```php
        $this->requireAuth();
        $this->syncRequestedWeekContext();
        $this->healWeeklyContext();
```

- [ ] **Paso 3: insertar la llamada en Programación Intermedia e Indicadores**

En `ProgramacionIntermediaController::index()`, justo después de `requireAuth()` y **antes** de
`$vars = $this->getSessionVars();` (que ya lee la semana de la sesión):

```php
        $this->requireAuth();
        $this->syncRequestedWeekContext();

        // Obtener variables de sesión comunes
        $vars = $this->getSessionVars();
```

Lo mismo en `IndicadoresController::index()`:

```php
        // Validar autenticación
        $this->requireAuth();
        $this->syncRequestedWeekContext();

        // Obtener variables de sesión
        $vars = $this->getSessionVars();
```

- [ ] **Paso 4: correr el test y verlo pasar entero**

```bash
docker compose exec app php tests/test_week_context_write_scope.php
```

Esperado: todas las comprobaciones OK salvo, quizá, las dos de APIs — esas las cierra la tarea 3.

- [ ] **Paso 5: comprobar en el navegador que la navegación sigue funcionando**

Abrir la puerta de servicio y comprobar que un enlace con semana explícita sigue mandando, y que una
semana inexistente ya **no** rompe la sesión:

```bash
open "http://localhost:8081/dev/entrar?u=test.R&p=Prueba"
```

En la consola del navegador (viewport 1180×820, dark):

```js
const set = w => fetch('/context/week', {method:'POST',credentials:'same-origin',
  headers:{'Content-Type':'application/json'},body:JSON.stringify({semana:w})});
const leer = async () => {
  const html = await (await fetch('/programacion-semanal',{credentials:'same-origin'})).text();
  return (html.match(/id="semana_PHP"[^>]*value="(\d+)"/) || [])[1];
};
await set(7); await fetch('/programa-general?semana=5',{credentials:'same-origin'});
console.log('navegacion a semana 5 ->', await leer());      // esperado: 5
await set(7); await fetch('/programa-general?semana=999',{credentials:'same-origin'});
console.log('semana inexistente   ->', await leer());       // esperado: 7 (se ignora)
await set(7); await fetch('/api/semanal/list?db=prueba&semana=4',{credentials:'same-origin'});
console.log('XHR rezagada         ->', await leer());       // esperado: 7 (ya no pisa)
await set(7);
```

El proyecto 27 «Prueba» tiene sembradas las semanas 5, 6 y 7, así que 5 es válida y 999 no.

- [ ] **Paso 6: commit**

```bash
git add src/Controllers/Programacion/ProgramaGeneralController.php \
        src/Controllers/Programacion/ProgramacionSemanalController.php \
        src/Controllers/Programacion/ProgramacionIntermediaController.php \
        src/Controllers/Gestion/IndicadoresController.php
git commit -m "fix(sesion): las paginas honran ?semana= por la via que valida"
```

---

### Tarea 3: `/api/pi/save` deja de persistir la semana del request

**Archivos:**
- Modificar: `src/Controllers/Programacion/ProgramacionIntermediaController.php:226-238`
- Test: `tests/test_week_context_write_scope.php` (ya existe)

**Interfaces:**
- Consume: la garantía de la tarea 1.
- Produce: `/api/pi/save` usa la semana del request como valor **local** para el guard de bloqueo, sin
  tocar la sesión.

**Por qué:** `save()` escribe `$_SESSION['semana'] = (int) $semanaReq;` antes de delegar en
`src/Legacy/guardar_programacion_intermedia.php`. Ese script legacy tiene su propio guard: responde
409 «Conflicto de contexto» si la semana del request no coincide con la de la sesión. Como el
controlador ya igualó ambas, **ese guard nunca puede dispararse hoy**. Quitar la escritura lo revive
además de cerrar el agujero.

- [ ] **Paso 1: añadir la comprobación al test**

En `tests/test_week_context_write_scope.php`, justo antes del bloque `echo "\n{$total}…`, añadir:

```php
// 4. La API de Programación Intermedia no persiste la semana del request: el guard 409 del
//    script legacy solo tiene sentido si la sesión conserva su propio valor.
$pi = (string) file_get_contents($raiz . '/src/Controllers/Programacion/ProgramacionIntermediaController.php');
$save = substr($pi, (int) strpos($pi, 'public function save()'));
$save = substr($save, 0, (int) strpos($save, 'public function getFilters()'));
comprobar(
    'ProgramacionIntermediaController::save() no asigna $_SESSION[\'semana\']',
    preg_match('/\$_SESSION\s*\[\s*[\'"]semana[\'"]\s*\]\s*=/', $save) !== 1,
);
```

- [ ] **Paso 2: correrlo y verlo fallar**

```bash
docker compose exec app php tests/test_week_context_write_scope.php
```

Esperado: FALLA en «ProgramacionIntermediaController::save() no asigna…».

- [ ] **Paso 3: sustituir la escritura por un valor local**

En `save()`, reemplazar este bloque:

```php
        $semanaReq = $_POST['semana'] ?? $_GET['semana'] ?? null;

        if ($semanaReq !== null && $semanaReq !== '') {
            $_SESSION['semana'] = (int) $semanaReq;
        }
```

por:

```php
        $semanaReq = $_POST['semana'] ?? $_GET['semana'] ?? null;
```

y unas líneas más abajo, reemplazar:

```php
        $semana = (int) ($_SESSION['semana'] ?? 0);
```

por:

```php
        // La semana del request manda para el guard de bloqueo, pero NO se persiste: si difiere de
        // la de la sesión, guardar_programacion_intermedia.php responde 409 y aborta.
        $semana = ($semanaReq !== null && $semanaReq !== '')
            ? (int) $semanaReq
            : (int) ($_SESSION['semana'] ?? 0);
```

- [ ] **Paso 4: correr el test y verlo pasar entero**

```bash
docker compose exec app php tests/test_week_context_write_scope.php
```

Esperado: todas OK, código de salida 0.

- [ ] **Paso 5: comprobar que guardar en Programación Intermedia sigue funcionando**

Sobre el proyecto 27 «Prueba» (mutable), en la semana activa: abrir
`http://localhost:8081/dev/entrar?u=test.R&p=Prueba`, navegar a `/programacion-intermedia`, editar
una celda, guardar, **recargar** y confirmar que el valor persiste. Después comprobar que el guard
revivió: con la sesión en la semana 7, un `POST /api/pi/save?db=prueba&semana=5` debe responder
**409**, no 200.

- [ ] **Paso 6: commit**

```bash
git add src/Controllers/Programacion/ProgramacionIntermediaController.php \
        tests/test_week_context_write_scope.php
git commit -m "fix(pi): la API de guardado deja de pisar la semana de la sesion"
```

---

### Tarea 4: La suite de navegador vuelve a vigilar la carrera

**Archivos:**
- Modificar: `tests/browser/support/session.mjs:26-33`

**Interfaces:**
- Consume: el arreglo completo de las tareas 1-3.
- Produce: nueve casos de `programacion-semanal-*` que vuelven a ser el test de regresión de este bug.

**Por qué:** el commit `9e11f612` añadió `await page.waitForLoadState('networkidle')` al final de
`selectProject()` para tapar el síntoma en las pruebas. Si el arreglo es correcto, la suite debe
pasar **sin** esa espera. Quitarla es la verificación.

- [ ] **Paso 1: retirar la espera y su comentario**

En `tests/browser/support/session.mjs`, borrar el comentario de siete líneas que empieza con «El
módulo al que aterriza la selección…» y la línea que le sigue:

```js
  await page.waitForLoadState('networkidle', { timeout: 45000 });
```

`selectProject()` queda terminando en su `page.waitForURL(...)`.

- [ ] **Paso 2: correr la suite**

```bash
npx playwright test tests/browser/programacion-semanal-*.mjs --workers=1
```

Esperado: **como mucho 38 fallidos / 22 pasados**, la misma marca que dejó `9e11f612`. Ninguno de los
fallos puede ser un «Expected N / Received M» sobre `#semana_PHP`: si aparece uno, el arreglo no
sirvió — **PARAR** y volver a la fase 1 de `superpowers:systematic-debugging`, no añadir esperas.

Los 38 fallos restantes son un problema distinto y ya documentado (`.ps-weekly-phase-title` ya no
existe en el DOM). No se tocan aquí.

- [ ] **Paso 3: correr las verificaciones transversales**

```bash
docker compose exec app php tests/test_global_table_safety.php
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
```

Esperado: ambos como estaban antes del cambio. Si phpstan tenía errores previos, comparar contra esa
línea base, no exigir cero.

- [ ] **Paso 4: commit**

```bash
git add tests/browser/support/session.mjs
git commit -m "test(ps): la suite vuelve a vigilar la carrera de semana sin esperar a la red"
```

---

## Fuera de alcance (anotado, no se hace aquí)

- `/listado-actividades` y `/contratos` responden **404** y `public/js/modules/info_general_nav.js`
  los sigue enlazando. Es un enlace roto en el menú de Información General, no tiene que ver con esta
  carrera.
- `src/Legacy/Endpoints/cambiar_pagina.php:11` escribe `$_SESSION['semana']` sin validar y sin
  castear a entero. Es una navegación legítima, así que el agujero de este plan no la incluye, pero
  merece pasar por el mismo tamiz algún día.
- Los demás fallos de la suite de Programación Semanal (`.ps-weekly-phase-title`).
