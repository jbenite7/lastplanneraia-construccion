---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-08-24
areas: [design-system, bi, qa]
fuente: docs/superpowers/plans/2026-08-24-pendientes-frente-tablas.md
resumen: Cerrar cuatro pendientes diferibles del frente de tablas — dos fugas de tipografia, el color de rol en los anillos de BI, los roles de las listas de SQL de CI…
---

# Pendientes del frente de tablas — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cerrar cuatro pendientes diferibles del frente de tablas — dos fugas de tipografia, el color de rol en los anillos de BI, los roles de las listas de SQL de CI y el gemelo callado del filtro de cabecera — dejando cada uno hecho y verificado, o devuelto a `TASKS.md` con su medicion.

**Architecture:** Cuatro tareas independientes entre si, sin orden forzado salvo que T1 va primera por barata. Cada una toca una capa distinta (CSS de tokens, servicio PHP de BI, listas de JS en CI, JS de tabla) y cada una cierra con el gate que gobierna su archivo, no con el gate que quede a mano.

**Tech Stack:** PHP 8.3 sobre Docker Compose, Handsontable 14.6.1, Chart.js, Node test runner, Playwright, PHPStan.

**Spec:** `docs/superpowers/specs/2026-08-24-pendientes-frente-tablas-design.md`

## Global Constraints

- **Todo PHP y todo test corre dentro del contenedor:** `docker compose exec app ...`. Nunca un PHP del host.
- **El contenedor `app` compartido NO monta este arbol.** Monta `.claude/worktrees/validate-session-coordination-dca393` (medido 2026-08-24, otra sesion trabajando ahi). **No se reapunta.** Para navegador: contenedor efimero en otro puerto, `LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps -d -p 18081:80 --name pendientes-tablas app`.
- **`LPS_CODE_ROOT="$(pwd)"` no es opcional en ninguna de esas invocaciones.** `docker-compose.override.yml:27` monta el codigo por ruta absoluta y sin esa variable cae por defecto a la raiz del repo: se verificaria OTRO arbol creyendo que es este. Es el fallo medido el 2026-08-18 — tres verdes desde un worktree mientras el contenedor servia el principal.
- **Para tests de linea de comandos** hace falta que el contenedor monte ESTE arbol. Como no lo hace, los tests PHP se corren en el contenedor efimero: `LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php <ruta>`.
- **El presupuesto `hardcoded-hex` de `programacion-semanal` es 0 y el audit lee tambien los comentarios**, de CSS y de JS. No escribir NINGUN hex, ni dentro de un comentario explicativo. Ya puso el gate en rojo dos veces.
- **Limpieza del contenedor efimero: `docker rm -f <nombre-de-ESTA-tarea>` y NADA MAS en la misma linea.** El 2026-08-24 un implementador anadio el contenedor compartido a esa misma orden y tumbo `app`, `db` y `adminer` de otra sesion. El plan prohibia reapuntarlo pero no prohibia borrarlo, y por ahi se colo. Cada tarea usa su propio `--name`.
- **No regenerar baselines ni snapshots para forzar verde.** Un cambio visual necesita aprobacion explicita.
- **Sesion local siempre por `/dev/entrar`**, nunca por `/login`: `http://127.0.0.1:18081/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E`.
- **Commits atomicos con staging selectivo.** Nunca `.env`, evidencia local ni trabajo ajeno.
- **Mapa de color aprobado (D-1):** critico → `critical`, alerta → `brand-construction`, bien → `brand-primary`. Los tres son claves de `CHART_COLOR_TOKENS` en `public/js/modules/bi-spa.js:84-97` y los tres tienen variante dark propia en `public/css/bi-control-tower.css:145-155`.
- **Regla de tokens (D-2):** un frente puede crear tokens cuyo valor nadie discutiria. Los que cargan significado son de DS-F1.

---

### Task 1: Las dos fugas de tipografia en las tablas

**Files:**
- Modify: `public/css/tokens.css:135` (anadir `--ds-font-icon` justo despues de `--ds-font-mono`)
- Modify: `public/css/handsontable-module.css:579`
- **`LPS_CODE_ROOT="$(pwd)"` no es opcional en ninguna de esas invocaciones.** `docker-compose.override.yml:27` monta el codigo por ruta absoluta y sin esa variable cae por defecto a la raiz del repo: se verificaria OTRO arbol creyendo que es este. Es el fallo medido el 2026-08-18 — tres verdes desde un worktree mientras el contenedor servia el principal.
- Modify: `public/css/handsontable-header-global.css:167`
- Modify: `public/css/design-system/components/table-filter-trigger.css:72`
- Test: `npm run test:design-system:static`

**Interfaces:**
- Consumes: nada de tareas anteriores.
- Produces: el token `--ds-font-icon`, disponible para cualquier hoja. Ninguna tarea posterior de este plan lo usa.

- [ ] **Step 1: Comprobar el estado de partida del gate**

```bash
npm run test:design-system:static
```

Anotar cuantos pasos pasan y cuantos fallan ANTES de tocar nada. Si algo ya esta rojo, es de otro frente: no arreglarlo aqui, anotarlo.

- [ ] **Step 2: Crear el token de fuente de iconos**

En `public/css/tokens.css`, inmediatamente despues de la linea `--ds-font-mono: ...`:

```css
    /* La familia de iconos. Existia copiada a mano en dos hojas (la cabecera global de
       Handsontable y la primitiva del filtro de tabla); un token la deja en un sitio. No lleva
       significado —nadie discute cual es la fuente de iconos— asi que no es contrato de DS-F1. */
    --ds-font-icon: "Font Awesome 5 Free";
```

- [ ] **Step 3: Cambiar el monospace literal**

En `public/css/handsontable-module.css:579`, dentro de `.lps-digest-preview`:

```css
  font-family: var(--ds-font-mono);
```

- [ ] **Step 4: Aplicar el token en las dos hojas que llamaban a Font Awesome a mano**

En `public/css/handsontable-header-global.css:167`:

```css
  font-family: var(--ds-font-icon) !important;
```

En `public/css/design-system/components/table-filter-trigger.css:72`:

```css
    font-family: var(--ds-font-icon);
```

**Limite:** en `table-filter-trigger.css` se cambia esta linea y nada mas. Es archivo del design system con contratos ejecutables encima.

- [ ] **Step 5: Verificar que el gate sigue igual o mejor**

```bash
npm run test:design-system:static
```

Esperado: el mismo numero de pasos en verde que en el Step 1. Si baja, el cambio lo causo: leer que paso rechaza y por que, no regenerar nada.

- [ ] **Step 6: Verificar en pantalla que el icono del filtro sigue apareciendo**

Levantar el contenedor efimero y mirar la cabecera de Programa General:

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps -d -p 18081:80 --name pendientes-tablas app
```

Abrir `http://127.0.0.1:18081/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E` y navegar a Programa General. El embudo de filtro debe verse igual que antes en la cabecera. **Este paso no es opcional:** si el token quedara mal escrito, el icono desaparece sin que ningun test estatico lo note — un `content` con glifo de Font Awesome sobre una familia que no carga rinde un cuadro vacio o nada.

- [ ] **Step 7: Commit**

```bash
git add public/css/tokens.css public/css/handsontable-module.css public/css/handsontable-header-global.css public/css/design-system/components/table-filter-trigger.css
git commit -m "fix(ds): las tablas dejan de saltarse el sistema para la fuente mono y la de iconos

El monospace literal pasa a --ds-font-mono, que ya existia. La familia de
Font Awesome estaba copiada a mano en DOS hojas, no en una: se crea
--ds-font-icon y se aplica en las dos, porque dejar una mantiene la fuga
abierta y la proxima auditoria la vuelve a medir."
```

---

### Task 2: El color de los anillos de BI

**Files:**
- Modify: `src/Services/ControlTowerService.php:2891-2908` (`semanticMetricRange()` y `schedulePerformanceRange()`)
- Modify: `public/js/modules/bi-spa.js:3707`
- Test: `LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app vendor/bin/phpstan analyse src admin/src --memory-limit=1G`

**Interfaces:**
- Consumes: nada de tareas anteriores.
- Produces: `semanticMetricRange(float $value, string $vocabulary): array` y `schedulePerformanceRange(float $real, float $planned): array` siguen devolviendo `['key' => string, 'label' => string, 'color_token' => string]` — misma firma, misma forma; **solo cambian los valores de `color_token`**. Ningun consumidor necesita cambiar.

- [ ] **Step 1: Mirar los dos anillos ANTES de tocarlos**

Con el contenedor efimero levantado, abrir la torre de control con `test.R` y capturar los anillos «Avance fisico» y «Cumplimiento cronograma» en 1180x820, tema dark. Guardar en `goals/pendientes-frente-tablas/evidence/`.

**Por que primero:** el diagnostico se hizo leyendo codigo. Un texto palido usado como relleno puede verse mal o puede llevar meses viendose simplemente suave. Sin el antes no hay con que comparar el despues, y sin comparacion no se puede afirmar que mejoro.

- [ ] **Step 2: Cambiar los color_token en el servicio**

En `src/Services/ControlTowerService.php`, dentro de `semanticMetricRange()`:

```php
    private function semanticMetricRange(float $value, string $vocabulary): array
    {
        // 2026-08-24: estos `color_token` PINTAN el relleno de dos donas (el anillo de «Avance
        // fisico» y el de «Cumplimiento cronograma»), asi que tienen que ser colores de DATO.
        // Devolvian `status-*`, que resuelve a `--ds-color-state-*-text`: tinta pensada para
        // leerse SOBRE un fondo, no para rellenar un area. El semaforo se conserva —el color
        // sigue diciendo si va mal o bien—, cambia con que se pinta.
```

y sustituir los tres `color_token`:

- `'status-critical'` → `'critical'`
- `'status-warning'` → `'brand-construction'`
- `'status-success'` → `'brand-primary'`

Hacer exactamente la misma sustitucion en `schedulePerformanceRange()`, que tiene los mismos tres.

**No tocar `key` ni `label`.** `key` lo consumen otros sitios para decidir clases de chip, donde `status-*` SI es lo correcto: alli el color es tinta sobre fondo, que es justo su oficio.

- [ ] **Step 3: Corregir el fallback del JS por lo que es**

En `public/js/modules/bi-spa.js:3707`:

```js
  const colors = source.map((dataset, index) => resolveChartColor(dataset.color || (index === 0 ? 'critical' : 'brand-aqua-medium')));
```

Anadir encima:

```js
  // El servidor SIEMPRE manda `dataset.color` para este medidor, asi que este fallback casi no
  // corre; aun asi decia `status-critical`, que es tinta de estado y no color de serie. La causa
  // del anillo palido no estaba aqui sino en ControlTowerService::semanticMetricRange().
```

- [ ] **Step 4: Verificar que el PHP sigue sano**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php scripts/run-php-tests.php --nivel=puro
```

Esperado: el mismo resultado que antes del cambio. Son cambios de literal de cadena; si algo se pone rojo, es que un test afirmaba el token viejo — leerlo antes de decidir si el test o el codigo tienen razon.

- [ ] **Step 5: Mirar los dos anillos DESPUES**

Misma ruta, mismo viewport, mismo tema. Comparar contra las capturas del Step 1.

Comprobar tres cosas y dejarlas escritas:
1. El anillo cambia de color segun el estado (el semaforo sigue vivo).
2. El relleno tiene cuerpo, no se lee lavado sobre el fondo dark.
3. El caso «Excelente» no se lee pesado. En dark `brand-primary` mapea a `--aia-green-light`, no al verde corporativo oscuro, asi que no deberia — pero se mira, no se supone.

Si algo de esto falla, **parar y decirlo**. No ajustar el mapa de color por cuenta propia: lo aprobo Felipe.

- [ ] **Step 6: Commit**

```bash
git add src/Services/ControlTowerService.php public/js/modules/bi-spa.js
git commit -m "fix(bi): los anillos de avance se pintan con color de dato, no con tinta de estado

Los dos medidores rellenaban su arco con --ds-color-state-*-text, que es
tinta para leerse sobre un fondo. El semaforo se conserva; cambia con que
se pinta. La causa estaba en el servicio, no en el fallback del JS que
senalaba la nota."
```

---

### Task 3: Los roles de las listas de SQL de CI

**Files:**
- Modify: `tests/design-system/ci-preflight.test.mjs:27-32` (derivar `INIT_COPIES` en vez de copiarla)
- Modify: `tests/design-system/visual-ci-contract.test.mjs` (solo el comentario que explica por que sigue duplicada)
- Test: `node --test tests/design-system/ci-preflight.test.mjs tests/design-system/visual-ci-contract.test.mjs`

**Interfaces:**
- Consumes: `EXPECTED_INIT_COPIES` de `scripts/design-system-ci-preflight.mjs`, que **hoy no se exporta**. Esta tarea la exporta.
- Produces: `EXPECTED_INIT_COPIES` como named export del modulo de preflight, un array de pares `[rutaOrigen: string, nombreDestino: string]`.

- [ ] **Step 1: Correr los dos tests ANTES de tocarlos**

```bash
node --test tests/design-system/ci-preflight.test.mjs tests/design-system/visual-ci-contract.test.mjs
```

Anotar el resultado. Es la referencia contra la que se compara el Step 5.

- [ ] **Step 2: Exportar la lista blanca**

En `scripts/design-system-ci-preflight.mjs`, cambiar la declaracion de la linea 10:

```js
export const EXPECTED_INIT_COPIES = [
```

El resto del array queda igual. El modulo ya usa `export` para otras cosas (`assertSafeCiComposeConfig`, `assertDbInitDockerfile`), asi que no hay nada mas que ajustar.

- [ ] **Step 3: Derivar la copia del test y explicar por que esta se deriva y la otra no**

En `tests/design-system/ci-preflight.test.mjs`, sustituir el bloque `INIT_COPIES` de las lineas 27-32 por:

```js
// 2026-08-24: esta lista era una copia a mano de la de scripts/design-system-ci-preflight.mjs y
// ahora se deriva de ella. Se puede derivar sin perder nada porque su unico oficio es armar un
// Dockerfile SINTETICO con el que probar el validador: prueba la mecanica, no el contenido. Lo
// que si comprueba el contenido es el test «the real db init Dockerfile satisfies the allowlist
// it is built from», mas abajo en este mismo archivo, que lee el Dockerfile REAL.
//
// La lista de tests/design-system/visual-ci-contract.test.mjs NO se deriva, a proposito: es un
// segundo testigo independiente sobre el Dockerfile real, y su valor esta justamente en no
// depender de la lista blanca que vigila.
import { EXPECTED_INIT_COPIES as INIT_COPIES } from '../../scripts/design-system-ci-preflight.mjs';
```

Mover ese `import` junto a los demas del principio del archivo — un `import` no puede ir en medio del cuerpo aunque el comentario lo acompane. El comentario si se queda donde estaba la lista.

- [ ] **Step 4: Poner al dia el comentario que hablaba de «las dos listas»**

En `tests/design-system/visual-ci-contract.test.mjs`, junto a la lista de nombres destino:

```js
// Tercera de las tres listas de SQL de CI, y la unica que se conserva duplicada a proposito.
// Las otras dos: la lista blanca de scripts/design-system-ci-preflight.mjs (el guardarrail
// fail-closed) y la de tests/design-system/ci-preflight.test.mjs, que desde el 2026-08-24 se
// deriva de aquella. Esta mira el Dockerfile real por su cuenta, sin consultar a la lista
// blanca, y por eso lo caza si las dos se desalinean.
//
// Al sembrar `general_flags` el 2026-08-24 el gate rechazo TRES veces seguidas, una por lista.
// Eso es la red funcionando, no friccion que haya que quitar.
```

- [ ] **Step 5: Verificar que la red sigue siendo red**

```bash
node --test tests/design-system/ci-preflight.test.mjs tests/design-system/visual-ci-contract.test.mjs
```

Esperado: mismo resultado que el Step 1.

- [ ] **Step 6: Comprobar que el guardarrail todavia muerde**

Esta es la comprobacion que da sentido a la tarea. Anadir una entrada falsa al Dockerfile y confirmar que lo rechazan:

```bash
cp database/fixtures/design-system-ci.Dockerfile /tmp/dockerfile-respaldo
echo 'COPY database/migrations/inventada.sql /docker-entrypoint-initdb.d/999-inventada.sql' >> database/fixtures/design-system-ci.Dockerfile
node --test tests/design-system/ci-preflight.test.mjs tests/design-system/visual-ci-contract.test.mjs
```

Esperado: **FALLA**, y falla en los dos archivos — uno por la lista blanca, otro por el testigo independiente. Si solo falla uno, la derivacion rompio algo: parar y decirlo.

Restaurar sin falta:

```bash
cp /tmp/dockerfile-respaldo database/fixtures/design-system-ci.Dockerfile
git diff --exit-code database/fixtures/design-system-ci.Dockerfile && echo "restaurado limpio"
```

- [ ] **Step 7: Commit**

```bash
git add scripts/design-system-ci-preflight.mjs tests/design-system/ci-preflight.test.mjs tests/design-system/visual-ci-contract.test.mjs
git commit -m "refactor(ci): tres listas de SQL con roles distintos, en vez de cuatro que fingen ser una

La copia del test de preflight se deriva de la lista blanca: solo arma un
Dockerfile sintetico para probar el validador. La de visual-ci-contract se
conserva duplicada A PROPOSITO — es el segundo testigo y su valor esta en
no depender de lo que vigila.

No se unifican las cuatro. Esa redundancia rechazo el cambio tres veces
seguidas al sembrar general_flags, una por lista."
```

---

### Task 4: El gemelo callado del filtro de cabecera

**REQUIRED SUB-SKILL: `superpowers:systematic-debugging`.** Esta tarea no tiene arreglo conocido. Tiene un sintoma medido y una causa por encontrar. Quien la ejecute entra por systematic-debugging y NO propone arreglo antes de haber reproducido.

**Files:**
- Investigate: `public/js/modules/programa_general/hot.js:2395-2465`
- Modify: por determinar — depende de la causa
- Test: navegador + `npm run test:design-system:static`

**Interfaces:**
- Consumes: nada de tareas anteriores.
- Produces: nada que otras tareas usen.

- [ ] **Step 1: Reproducir y medir**

Con el contenedor efimero y sesion `test.R`, abrir Programa General y contar en la consola:

```js
document.querySelectorAll('#hot-container thead th .changeType').length
document.querySelectorAll('#hot-container thead th .changeType[aria-hidden="true"]').length
```

Esperado segun la medicion del 2026-08-24: 24 y 12.

**Si sale 24 y 24, el defecto no se reproduce.** Ir directo al Step 5 y devolverlo a `TASKS.md`. No arreglar lo que no falla.

- [ ] **Step 2: Averiguar CUALES 12 son los callados**

```js
['ht_master', 'ht_clone_top'].forEach((c) => {
  const nodos = document.querySelectorAll(`.${c} thead th .changeType`);
  const marcados = document.querySelectorAll(`.${c} thead th .changeType[aria-hidden="true"]`);
  console.log(c, marcados.length + '/' + nodos.length);
});
```

Esto parte el problema en dos hipotesis excluyentes:
- **12/12 y 0/12** → un contenedor entero se queda fuera. La causa esta en el alcance de `#hot-container` o en cuando se engancha el observer.
- **6/12 y 6/12** → se pierden salteados. La causa es de tiempo: algo repone nodos despues del ultimo barrido.

Anotar cual de las dos es ANTES de leer mas codigo.

- [ ] **Step 3: Comprobar si el observer esta vivo**

```js
document.querySelectorAll('#hot-container thead th .changeType[aria-hidden="true"]')
  .forEach((n) => n.removeAttribute('aria-hidden'));
setTimeout(() => console.log(
  document.querySelectorAll('#hot-container thead th .changeType[aria-hidden="true"]').length
), 500);
```

- Si vuelve a 12: el observer funciona y repone lo que ya marcaba. La causa es de alcance, no de vigilancia.
- Si vuelve a 0: el observer no esta enganchado donde se cree. Mirar la llamada de `hot.js:3437` y con que `container` se invoca.
- Si vuelve a 24: el barrido si alcanza a todos cuando corre, y el problema es que algo lo pisa despues — mirar que hace Handsontable al renderizar el clon.

- [ ] **Step 4: Arreglar donde este la causa, no donde este el sintoma**

Sin la medicion de los pasos anteriores no se puede escribir aqui el arreglo, y **inventarlo seria repetir el error que produjo este pendiente**: el comentario de `markDecorativeHeaderTriggers` ya afirmaba haber cerrado exactamente este defecto.

Reglas para el arreglo, sea cual sea:
- Escritura condicional siempre (`if (attr !== 'true')`), como ya hace el archivo, o el MutationObserver entra en bucle.
- No anadir `aria-hidden` a nada tabulable: dispara `aria-hidden-focus` en Axe. Estos botones nacen con `tabindex="-1"`, asi que no aplica — pero comprobarlo si el arreglo toca otros nodos.
- Volver a correr el conteo del Step 1 y esperar 24/24.
- Recargar la pagina y contar otra vez. Un arreglo que solo aguanta hasta el siguiente `render()` no es arreglo: ya paso una vez (medido: 24/24 → `render()` → 12/24).

- [ ] **Step 5: Actualizar el comentario para que diga la verdad**

El bloque de `hot.js:2408-2413` declara el pendiente como abierto. Si se arreglo, se sustituye por la causa encontrada y como se cerro. Si NO se reprodujo, se anota eso con fecha — que la medicion del 2026-08-24 no se reprodujo es un dato, no un silencio.

**No escribir ningun hex en este comentario.** El audit lee los comentarios de JS y el presupuesto de `programacion-semanal` es 0.

- [ ] **Step 6: Verificar el gate del archivo tocado**

```bash
npm run test:design-system:static
```

- [ ] **Step 7: Commit**

Si hubo arreglo:

```bash
git add public/js/modules/programa_general/hot.js
git commit -m "fix(a11y): los 24 gatillos de filtro quedan marcados, no 12

<la causa encontrada, en una frase>"
```

Si no se reprodujo, no hay commit de codigo: solo la anotacion del Step 5 y la vuelta a `TASKS.md` en el cierre.

---

### Task 5: Cierre del frente

**Files:**
- Modify: `TASKS.md` (§Diferibles)
- Modify: `CHANGELOG.md`
- Create: `goals/pendientes-frente-tablas/goal.md`

- [ ] **Step 1: Anotar en `TASKS.md` lo hecho y lo no hecho**

Quitar de §Diferibles lo que quedo cerrado. Dejar, con su porque:
- **DataTables**, intacto: es decision de rumbo sin fecha y Felipe la mantuvo.
- **Los tokens de relleno de estado**, hallazgo nuevo dirigido a DS-F1: los anillos de BI ya no usan tinta de estado, pero el sistema sigue sin ofrecer un color de estado para rellenar area. Quien decida la escala de severidad decide esto.
- **T4 si no se reprodujo**, con la medicion de que fue lo que salio.

- [ ] **Step 2: Anotar el cierre en `CHANGELOG.md`**

Bajo `[Sin publicar]`. **No reordenar el archivo**: esta desordenado y eso es un pendiente propio ya anotado, con su propia pasada de verificacion.

- [ ] **Step 3: Crear el goal con su seccion de cierre**

`goals/pendientes-frente-tablas/goal.md` con objetivo, condicion de hecho, enlace al spec y a este plan, la seccion «Archivos de este goal» que pide `CLAUDE.md`, y un `## Cierre` con la salida real de los comandos. **Sin `## Cierre` el mapa de estado no lo lee como cerrado** — paso dos veces el 2026-08-19.

- [ ] **Step 4: Commit de la documentacion**

```bash
git add TASKS.md CHANGELOG.md goals/pendientes-frente-tablas/
git commit -m "docs: cierre del frente de pendientes de tablas"
```

- [ ] **Step 5: Publicar con el script DEL REPO**

```bash
bash scripts/publicar.sh
```

**Es el del repositorio, no el generico de `~/.claude/scripts/`.** Acepta `--solo-verificar` y `--con-merges`; **no** acepta `-v/-p/-m` — los ignora en silencio, que es como el 2026-08-24 tres banderas se fueron al vacio sin un solo error. **No commitea:** deniega si el arbol esta sucio, asi que los Steps 1-4 tienen que dejar `git status` limpio.

Comprueba algo que el generico no puede: que el contenedor compartido monte el arbol que se esta verificando. **Aqui NO lo monta** (monta `validate-session-coordination-dca393`), asi que es previsible que el script deniegue. Si lo hace, **no reapuntar el contenedor** — hay otra sesion usandolo. Coordinar con Felipe: o se espera una ventana, o se le pide a el la decision. Destrabarse reapuntando estado compartido es la falta, no la solucion.

- [ ] **Step 6: Confirmar que quedo publicado**

```bash
git fetch origin && git rev-parse origin/main && git rev-parse HEAD
```

Los dos SHA deben coincidir. Si no, no esta publicado y el frente no esta cerrado.

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** idem; ademas ci-preflight.mjs:10 exporta EXPECTED_INIT_COPIES y su test la importa; goal con Cierre y sha anotado

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
