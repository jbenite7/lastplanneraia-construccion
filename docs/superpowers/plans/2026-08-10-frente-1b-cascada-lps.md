# Frente 1 · Tanda 1B — La cascada LPS: plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cerrar los 12 hallazgos abiertos de la cascada LPS — Programa General, Actualizar Cronograma, Programación Intermedia y Programación Semanal —, que es lo que la obra usa a diario.

**Architecture:** Los arreglos se agrupan **por la pieza que gobiernan**, no por puntuación: el acuse de guardado toca las cuatro vistas a la vez, la leyenda de chips toca contadores y accesibilidad a la vez, y el estado vacío toca el mismo punto que los contadores. Cada tarea deja una pieza entera coherente y revisable sola. Dos de los doce hallazgos se cierran **sin tocar código**: al medirlos resultaron ya arreglados (ver «Las dos premisas falsas», abajo), y su ficha se corrige en la Task 9.

**Tech Stack:** PHP 8.3 y JavaScript ES5 en los módulos `public/js/modules/*` (Handsontable + jQuery), CSS por tokens del design system, Node para los gates, Playwright para lo que solo se ve en navegador.

**Spec:** [`2026-08-10-programa-cierre-pendientes-design.md`](../specs/2026-08-10-programa-cierre-pendientes-design.md), Frente 1, tanda 1B.

## Global Constraints

- **Docker Compose es el runtime.** Todo PHP con `docker compose exec app`. Nunca un PHP del host.
  - **En worktree:** exporta `COMPOSE_FILE=docker-compose.wt.yml` **antes de cualquier comando**, incluidos los `npm run test:*`. Sin eso, `docker compose exec app` resuelve al contenedor del árbol principal (el nombre de proyecto está fijado en `docker-compose.yml:1`) y las pruebas comparan **dos árboles distintos**. Medido el 2026-08-10: así es como `tests/design-system/foundation.test.mjs:10-22` daba un falso rojo.
- **La sesión local se abre por la puerta de servicio**, nunca por `/login`: `http://localhost:8091/dev/entrar?u=test.A` (puerto del worktree; en el árbol principal es 8081).
- **Un rol permitido y uno denegado** verificados en toda ruta protegida que se toque (`AGENTS.md` §Routing).
- **Dark a 1180×820** es el tema y viewport de validación. No se valida en claro ni en móvil.
- **Ningún hex suelto, ningún estilo en línea, ninguna variante local** en módulo migrado: se consume token (`DESIGN.md`).
- **CSRF en toda mutación autenticada.** No se retira de ningún endpoint.
- **Prepared statements siempre** a través de la capa `Database`.
- **Empieza cada tarea comprobando su premisa** contra el código de hoy. Los números de línea de `docs/EXPERIMENTS.md` son de fechas anteriores y **varios ya no cuadran**. Si la premisa cambió, corrige el plan en vez de arrastrarlo, y **escribe el resultado en la ficha aunque contradiga lo registrado**.
- **No repitas lógica en varios módulos.** Si un arreglo cae en `programa_general/hot.js`, `programacion_intermedia/hot.js`, `programacion_semanal/hot.js` y `programa_actualizar/hot_actualizar.js`, va a **una** pieza compartida. Copiarlo cuatro veces siembra el defecto que esta tanda arranca. Medido en la tanda 1A: una tarea hubo que devolverla por repetir 26 líneas idénticas en cuatro `hot.js`.
- **Todo cambio visible se valida en navegador** contra el contenedor servido, con consola revisada.
- Commits atómicos, uno por tarea. Nunca `.env`, nunca `docker-compose.wt.yml`.
- **Las decisiones del usuario se acumulan y esta sesión no para nunca** (`docs/coordinacion-sesiones.md`, actualizado el 2026-08-10). Cuando un paso necesite su criterio —cambia alcance, toca un contrato o baseline, borra algo, altera lo que una prueba mide, o se desvía del plan—: **(1)** anótalo en `docs/decisiones-pendientes.md` con la plantilla de ese archivo, **(2)** **sáltate ese hallazgo** sin tocarlo, y **(3)** sigue con los demás.
  - **No lo resuelvas con el supuesto más conservador.** Saltar deja el hallazgo intacto y barato de retomar; suponer deja trabajo que quizá haya que deshacer. El 2026-08-10 hubo que devolver una tarea por exactamente eso.
  - **Mide antes de anotar.** Una entrada que diga «no sé si X o Y» sin datos le devuelve el trabajo al usuario, que es lo que este reparto existe para evitar. Cada entrada debe poder decidirse **sin abrir el código**.

## Los 12 hallazgos y dónde caen

| ICE | Hallazgo | Tarea |
|---|---|---|
| 504 | Importar el cronograma no da ningún acuse de recibo | 1 |
| 480 | Al filtrar a cero filas, la rejilla dice que la semana está vacía | 4 |
| 450 | El acuse de guardado se anuncia en 1 de 4 rejillas | 2 |
| 448 | «⚠ Sin asignar» pesa menos que el dato y su segundo canal está muerto | 5 |
| 432 | Los chips de Semanal no llevan `aria-pressed` y filtrar no se anuncia | 3 |
| 400 | El resumen de cierre corta las listas a 8 sin decirlo | 6 |
| 392 | Paridad PI/PS del aviso de Responsable AIA | **9** — ya arreglado; queda un residuo que absorbe la Task 5 |
| 336 | La rejilla de escritorio no tiene estado «guardando» | 2 |
| 280 | Programación Intermedia avisa después del hecho | **9** — ya arreglado, se cierra sin tocar código |
| 270 | Los contadores de los chips cuentan el filtro, no la semana | 4 |
| 168 | El gate de cierre está duplicado y el error no señala ninguna fila | 7 |
| 150 | Pulido del momento firma (confirmar compromisos) | 8 |

## Las dos premisas falsas, medidas antes de planificar

Se comprobaron contra el código de hoy y **no son ciertas**. Las dos las arregló el commit `7ff39b54` («la celda bloqueada por falta de Responsable AIA dice por qué y ya no deja escribir para deshacer», N-1 Task 38, 2026-08-05), **posterior** a la redacción de sus fichas.

1. **ICE 280 — «PI avisa después del hecho en vez de impedirlo».** Falsa. La celda de restricción **ya nace `readOnly`** cuando falta el Responsable AIA (`programacion_intermedia/hot.js:855-858`, `:997-1028`, `:950`). El `revertCell` de `:4166-4178` sigue ahí a propósito y su propio comentario lo dice: es red de seguridad para **pegados y cambios programáticos**, que no pasan por el editor. El remedio que pedía la ficha —«`readOnly` condicional en las columnas de restricción mientras falte Responsable, con el motivo en la celda»— **es exactamente lo que hay**.
2. **ICE 392 — «solo PS lo marca en la celda; Intermedia no marca esa celda de ninguna forma».** Falsa. PI **sí** la marca: `piResponsableRenderer` (`hot.js:3127-3148`) pinta un `<span class="pi-missing-resp">` con glifo de candado y motivo en `td.title`, y **tiene regla CSS** en `programacion-intermedia.css:532-537`.

**Lo que sí queda del 392, y es lo contrario de lo registrado:** la asimetría sobrevive, pero al revés de como se contó. PI usa `--ds-color-state-warning-text` y **pesa 600**; PS usa `--ds-color-state-critical-text` y **no declara peso** (`programacion-semanal.css:1690-1693`). La misma condición se pinta con dos severidades distintas, y la que sí tiene regla de fondo declarada en el código (`ps-cell-empty-alert`) es justo la que **no existe en el CSS**. Ese residuo lo paga la Task 5, que ya iba a tocar esas dos reglas.

## File Structure

| Archivo | Responsabilidad | Tarea |
|---|---|---|
| `views/programa-general-actualizar/programaGeneralActualizar.view.php` | Submit de `#formCargarExcel` con acuse de recibo | 1 |
| `public/js/design-system/save-status.js` | **Nuevo.** Única pieza que gobierna el chip `#save-status`: estados `guardando`/`guardado`/`error`, debounce y anuncio. La consumen las cuatro rejillas. | 2 |
| `views/programa-general/programa_general.view.php`, `views/programacion-semanal/programacion_semanal.view.php`, `views/programa-general-actualizar/programaGeneralActualizar.view.php` | `role="status"` en `#save-status` | 2 |
| `public/js/modules/programa_general/hot.js`, `programacion_semanal/hot.js`, `programa_actualizar/hot_actualizar.js` | Consumen `save-status.js` | 2 |
| `public/js/modules/programacion_semanal/hot.js` | Leyenda de chips: `aria-pressed`, región viva, contadores sobre `masterData`, texto del vacío filtrado, resumen de cierre, momento firma | 3, 4, 6, 8 |
| `public/css/programacion-semanal.css` | Regla de `.ps-cell-empty-alert` y peso de `.ps-missing-assignment` | 5 |
| `src/Controllers/Api/SemanalApiController.php` | La respuesta `No_Bloqueado` trae los ids de las filas que bloquean | 7 |
| `tests/design-system/cascada-lps-a11y.test.mjs` | **Nuevo.** Candado estático: `#save-status` declara `role="status"` en las cuatro vistas y los chips de PS emiten `aria-pressed`. | 2, 3 |
| `docs/EXPERIMENTS.md` | Disposición escrita de los 12 | 9 |

---

### Task 1: Importar el cronograma da acuse de recibo

Es el ICE más alto de la tanda (504) y la operación más larga de la app. Hoy, entre pulsar «Guardar» y ver el resultado **no cambia nada en pantalla**, y pulsar dos veces lanza dos importaciones sobre el mismo cronograma.

**Premisa verificada el 2026-08-10:** cierta. El handler `guardarCargarExcel` (`programaGeneralActualizar.view.php:451-537`) hace `e.preventDefault()`, arma un `FormData` (`:456`) y lanza `$.ajax` POST a `/api/general/import` (`:460-466`) con `.done`/`.fail` (`:515-536`). **No hay** `prop('disabled', true)`, ni `#modal_spinner`, ni `.always()`.

El patrón bueno ya existe en el repo y no hay que inventarlo: `public/js/funcionesGenerales6.js:68-69` y sus cierres en `:99`, `:128`, `:140`. El `#modal_spinner` se inyecta desde `funcionesGenerales6.js:11` y ya trae `role='dialog'`, `aria-live='polite'`, `aria-label='Procesando'` y cuerpo «Procesando…».

**Files:**
- Modify: `views/programa-general-actualizar/programaGeneralActualizar.view.php:451-537`

**Interfaces:**
- Consumes: `#modal_spinner` de `public/js/funcionesGenerales6.js` (ya cargado en esta vista — **verifícalo con `grep -n funcionesGenerales6 views/programa-general-actualizar/programaGeneralActualizar.view.php` antes de nada**; si no lo estuviera, cargarlo es parte de esta tarea).
- Produces: nada que consuman otras tareas.

- [ ] **Step 1: Comprobar la premisa y que el spinner está disponible**

```bash
cd "$(git rev-parse --show-toplevel)"
grep -n "funcionesGenerales6" views/programa-general-actualizar/programaGeneralActualizar.view.php
grep -n "prop('disabled'\|modal_spinner\|always(" views/programa-general-actualizar/programaGeneralActualizar.view.php
sed -n '451,470p;510,540p' views/programa-general-actualizar/programaGeneralActualizar.view.php
```

Esperado: el primer `grep` encuentra la carga del script; el segundo **no** encuentra nada dentro del bloque 451-537. Si encuentra algo, la premisa cambió: **para y corrige el plan** antes de seguir.

- [ ] **Step 2: Deshabilitar el submit y abrir el spinner al enviar**

Dentro del handler, justo después de `e.preventDefault()` y antes del `$.ajax`, añade el bloqueo. Usa el `<input type="submit">` de `:170` como objetivo (no un `<button>`: en esta vista es un `input`, y `$(this).find(...)` sobre el form lo alcanza).

```javascript
        e.preventDefault();

        // Acuse de recibo de la operacion mas larga de la app: sin esto, entre
        // pulsar «Guardar» y ver el resultado no cambia nada en pantalla, y un
        // segundo clic lanza una segunda importacion sobre el mismo cronograma.
        // Mismo patron que «Crear Semana LPS» (funcionesGenerales6.js:68-69).
        var $submit = $(this).find('input[type="submit"]');
        $submit.prop('disabled', true);
        $('#modal_spinner').modal('show');
```

- [ ] **Step 3: Reabrir el botón y cerrar el spinner pase lo que pase**

El `.always()` es la parte que no se puede olvidar: si el servidor falla, el usuario debe recuperar el botón. Encadénalo al `$.ajax` **después** de `.done`/`.fail`:

```javascript
        .always(function () {
            // Pase lo que pase —exito, error de red o 500— el spinner se cierra y
            // el boton vuelve. Sin este `always` un fallo deja la pantalla
            // bloqueada para siempre y la unica salida es recargar.
            $('#modal_spinner').modal('hide');
            $submit.prop('disabled', false);
        });
```

- [ ] **Step 4: Verificar en navegador que el acuse aparece y que el doble clic ya no dispara dos importaciones**

```bash
export COMPOSE_FILE=docker-compose.wt.yml
curl -s -o /dev/null -w '%{http_code}\n' -c /tmp/cookie.txt \
  "http://localhost:8091/dev/entrar?u=test.A&p=PDC%20Sandbox%20E2E"
```

Luego, en el navegador integrado a **1180×820 en dark**: entra por la puerta de servicio como `test.A`, ve a `/programa-general-actualizar`, abre «Cargar Excel», elige un XLSX y pulsa «Guardar» **dos veces seguidas**.

Esperado, y las tres cosas hay que verlas:
1. El modal «Procesando…» aparece **antes** de que vuelva el servidor.
2. El botón queda `disabled` — el segundo clic no produce una segunda petición. Compruébalo en la pestaña de red: **una sola** `POST /api/general/import`.
3. Al terminar (o al fallar), el spinner se cierra y el botón vuelve a estar pulsable.

Revisa la consola: sin errores nuevos.

- [ ] **Step 5: Verificar el rol denegado**

El Visualizador no debe poder importar. Entra como `test.V` a la misma ruta y comprueba qué responde el servidor:

```bash
curl -s -o /dev/null -w '%{http_code}\n' -c /tmp/cookieV.txt \
  "http://localhost:8091/dev/entrar?u=test.V&p=PDC%20Sandbox%20E2E"
curl -s -o /dev/null -w 'import como V: %{http_code}\n' -b /tmp/cookieV.txt \
  -X POST http://localhost:8091/api/general/import
```

Esperado: **403** (o 401/302 a login si la sesión no aplica). **Si responde 200, para y consulta hacia arriba**: sería un agujero de permisos, no un pulido, y cambia el alcance de esta tarea.

- [ ] **Step 6: Commit**

```bash
git add views/programa-general-actualizar/programaGeneralActualizar.view.php
git commit -m "fix(actualizar-cronograma): importar el cronograma acusa recibo y ya no admite doble envio

La operacion mas larga de la app no cambiaba nada en pantalla entre el
clic y el resultado, y un segundo clic lanzaba una segunda importacion
sobre el mismo cronograma. Reutiliza el #modal_spinner que ya existe y
que ya trae aria-live, con el mismo patron de «Crear Semana LPS»
(funcionesGenerales6.js:68-69). El always() devuelve el boton tambien
cuando el servidor falla."
```

---

### Task 2: Una sola pieza gobierna el chip de guardado, y las cuatro vistas lo anuncian

Junta dos hallazgos porque tocan **el mismo elemento**: `#save-status`. Separarlos obligaría a editar las mismas cuatro vistas dos veces.

- **ICE 336** — no hay estado «guardando» en la rejilla de escritorio. PG, PI y PS solo señalan al terminar; entre la tecla y el badge hay una ida y vuelta de red sin acuse. El patrón bueno está en `programa_actualizar/hot_actualizar.js:880-901` (chip «Guardando... (n)» con debounce de 800 ms) — pero hoy vive **dentro** de ese módulo.
- **ICE 450** — el mismo `#save-status`, con el mismo papel, lleva `role="status"` **solo** en `programacion_intermedia.view.php:57`.

**Premisa verificada el 2026-08-10:** cierta, con **dos matices que el backlog no registra** y que hay que respetar:
1. PI usa una clase de ocultamiento **distinta** al resto: `pi-status-badge-hidden`, mientras PG, PS y PGA usan `badge-badge-hidden`. La pieza compartida no puede asumir una sola clase.
2. PGA no dice «Guardado» sino «Auto-Guardado», y lleva `data-aia-severity="success"` en vez de `aia-chip--success`.

**Esta es la tarea donde más fácil es sembrar el defecto que la tanda arranca.** No copies el bloque de `hot_actualizar.js` a los otros tres módulos: **extráelo** a una pieza compartida y haz que los cuatro la consuman, `hot_actualizar.js` incluido.

**Files:**
- Create: `public/js/design-system/save-status.js`
- Create: `tests/design-system/cascada-lps-a11y.test.mjs`
- Modify: `views/programa-general/programa_general.view.php:71`, `views/programacion-semanal/programacion_semanal.view.php:101`, `views/programa-general-actualizar/programaGeneralActualizar.view.php:109`
- Modify: `public/js/modules/programa_actualizar/hot_actualizar.js:880-901` y `:483`
- Modify: `public/js/modules/programa_general/hot.js`, `public/js/modules/programacion_semanal/hot.js`

**Interfaces:**
- Consumes: nada.
- Produces: el módulo ES `public/js/design-system/save-status.js`, con esta superficie exacta, que consumen las tareas 2 y ninguna otra:
  - `crearSaveStatus({ selector = '#save-status', etiquetaGuardado = 'Guardado', claseOculta = 'badge-badge-hidden' }) → { pendiente(n), guardado(), error(mensaje) }`

- [ ] **Step 1: Escribir el candado antes que el arreglo**

Nace **rojo**: hoy tres de las cuatro vistas no declaran `role="status"`. Crea `tests/design-system/cascada-lps-a11y.test.mjs`:

```javascript
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');

// Las cuatro rejillas de la cascada comparten el mismo chip con el mismo papel.
// Si una sola deja de anunciarlo, guardar en esa pantalla no produce ningun
// anuncio para quien usa lector de pantalla — que es exactamente lo que pasaba
// en tres de las cuatro hasta el 2026-08-10.
const VISTAS_CASCADA = [
  'views/programa-general/programa_general.view.php',
  'views/programa-general-actualizar/programaGeneralActualizar.view.php',
  'views/programacion-intermedia/programacion_intermedia.view.php',
  'views/programacion-semanal/programacion_semanal.view.php',
];

test('el chip de guardado se anuncia en las cuatro rejillas de la cascada', async () => {
  for (const vista of VISTAS_CASCADA) {
    const html = await read(vista);
    const chip = html.match(/<span[^>]*id="save-status"[^>]*>/);
    assert.ok(chip, `${vista}: no declara #save-status`);
    assert.match(chip[0], /role="status"/, `${vista}: #save-status sin role="status"`);
  }
});
```

- [ ] **Step 2: Ejecutar el candado y verlo fallar**

```bash
export COMPOSE_FILE=docker-compose.wt.yml
npx node --test tests/design-system/cascada-lps-a11y.test.mjs
```

Esperado: **FALLA**, con el mensaje `views/programa-general/programa_general.view.php: #save-status sin role="status"`. Anota cuál es la primera vista que reporta: es la prueba de que el candado muerde.

- [ ] **Step 3: Añadir `role="status"` a las tres vistas que no lo tienen**

Solo el atributo, sin tocar clases ni texto (PGA conserva «Auto-Guardado» y su `data-aia-severity`):

```php
<!-- views/programa-general/programa_general.view.php:71 -->
<span id="save-status" class="aia-chip aia-chip--success badge-badge-hidden" role="status">Guardado</span>

<!-- views/programacion-semanal/programacion_semanal.view.php:101 -->
<span id="save-status" class="aia-chip aia-chip--success badge-badge-hidden" role="status">Guardado</span>

<!-- views/programa-general-actualizar/programaGeneralActualizar.view.php:109 -->
<span id="save-status" class="aia-chip badge-badge-hidden" data-aia-severity="success" role="status">Auto-Guardado</span>
```

- [ ] **Step 4: Ejecutar el candado y verlo pasar**

```bash
npx node --test tests/design-system/cascada-lps-a11y.test.mjs
```

Esperado: **PASA**, 1 test.

- [ ] **Step 5: Extraer la pieza compartida del chip**

Crea `public/js/design-system/save-status.js` con el comportamiento que hoy solo tiene Actualizar Cronograma. Nota los dos matices: la clase de ocultamiento y la etiqueta son parámetros, porque PI y PGA difieren del resto.

```javascript
/**
 * Chip de estado de guardado, compartido por las cuatro rejillas de la cascada.
 *
 * Antes del 2026-08-10 este comportamiento vivia solo dentro de
 * `programa_actualizar/hot_actualizar.js`: PG, PI y PS senalaban unicamente al
 * terminar, asi que entre la tecla y el badge habia una ida y vuelta de red sin
 * ningun acuse de recibo — muy por encima de los ~0,1 s que la manipulacion
 * directa admite. Se extrae en vez de copiarse: cuatro copias de estas 30 lineas
 * serian cuatro sitios donde arreglar el proximo defecto.
 *
 * La clase de ocultamiento y la etiqueta son parametros a proposito: PI usa
 * `pi-status-badge-hidden` y PGA dice «Auto-Guardado», no «Guardado».
 */
export function crearSaveStatus({
  selector = '#save-status',
  etiquetaGuardado = 'Guardado',
  claseOculta = 'badge-badge-hidden',
} = {}) {
  const nodo = () => document.querySelector(selector);

  const pintar = (texto, severidad) => {
    const el = nodo();
    if (!el) return;
    el.classList.remove(claseOculta, 'aia-chip--success', 'aia-chip--warning', 'aia-chip--danger');
    if (severidad) el.classList.add(`aia-chip--${severidad}`);
    el.textContent = texto;
    el.hidden = false;
  };

  return {
    // n = cuantas filas hay en cola. El contador importa: guardar tres filas
    // seguidas debe leerse como una sola operacion con tres pendientes, no
    // como tres parpadeos.
    pendiente(n) {
      pintar(`Guardando... (${n})`, 'warning');
    },
    guardado() {
      pintar(etiquetaGuardado, 'success');
    },
    error(mensaje) {
      pintar(mensaje || 'No se pudo guardar', 'danger');
    },
  };
}
```

- [ ] **Step 6: Hacer que Actualizar Cronograma consuma la pieza en vez de su copia**

Es el módulo del que salió el patrón, así que es el que prueba que la extracción no perdió nada. Sustituye el bloque de `hot_actualizar.js:893-900` por la llamada, y el `.text('Guardando...')` de `:483` por `estado.pendiente(...)`. Conserva el debounce de 800 ms de `:889-890`, que es del módulo y no de la pieza.

```javascript
// Al inicio del modulo, junto al resto de imports dinamicos:
var _saveStatus = null;
import('/js/design-system/save-status.js').then(function (mod) {
  _saveStatus = mod.crearSaveStatus({});
});

// Donde antes estaba el bloque :893-900:
var pendingCount = Object.keys(_pendingChanges).length;
if (_saveStatus) { _saveStatus.pendiente(pendingCount); }
```

- [ ] **Step 7: Cablear las otras tres rejillas**

En `programa_general/hot.js`, `programacion_semanal/hot.js` y `programacion_intermedia/hot.js`, importa la pieza con sus parámetros y llama `pendiente(n)` **antes** del AJAX de guardado de fila y `guardado()` en el `done`. PI pasa su clase propia:

```javascript
// programacion_intermedia/hot.js — la clase de ocultamiento es distinta aqui.
import('/js/design-system/save-status.js').then(function (mod) {
  _saveStatus = mod.crearSaveStatus({ claseOculta: 'pi-status-badge-hidden' });
});
```

En `programacion_semanal/hot.js`, el punto de guardado es `saveRow(rowIndex, prop, oldValue)` (llamado en `:2901`) y el acuse actual es `showFeedback('success', 'Guardado')` en `:2409`. **Localiza los números de línea reales antes de editar** — han cambiado varias veces.

- [ ] **Step 8: Verificar en navegador las cuatro pantallas**

A 1180×820 en dark, como `test.A` por la puerta de servicio, en `/programa-general`, `/programa-general-actualizar`, `/programacion-intermedia` y `/programacion-semanal`: edita una celda y observa el chip.

Esperado en las cuatro: aparece «Guardando... (1)» en cuanto sueltas la tecla, y pasa a «Guardado»/«Auto-Guardado» al volver el servidor. Comprueba en el árbol de accesibilidad que el chip tiene `role="status"` y que el cambio se anuncia. Consola sin errores nuevos.

**Restaura los datos que toques** y verifica con una consulta que la celda volvió a su valor:

```bash
docker compose exec -T app php -r '/* SELECT de la fila tocada, con project_id */'
```

- [ ] **Step 9: Commit**

```bash
git add public/js/design-system/save-status.js tests/design-system/cascada-lps-a11y.test.mjs \
        views/programa-general/programa_general.view.php \
        views/programacion-semanal/programacion_semanal.view.php \
        views/programa-general-actualizar/programaGeneralActualizar.view.php \
        public/js/modules/programa_actualizar/hot_actualizar.js \
        public/js/modules/programa_general/hot.js \
        public/js/modules/programacion_semanal/hot.js \
        public/js/modules/programacion_intermedia/hot.js
git commit -m "feat(cascada): una sola pieza gobierna el chip de guardado, y las cuatro rejillas lo anuncian

El estado «Guardando... (n)» existia solo dentro de Actualizar Cronograma;
las otras tres solo senalaban al terminar. Se extrae a
public/js/design-system/save-status.js y lo consumen las cuatro — incluido
el modulo del que salio, que es lo que prueba que la extraccion no perdio
nada. El chip declara role=status en las cuatro vistas (antes en 1 de 4).
El candado nacio rojo y se ejecuto rojo antes de arreglar nada."
```

---

### Task 3: Los chips de Semanal se anuncian como lo que son

**ICE 432.** `renderAlertLegend()` (`hot.js:3033-3042`) emite `role='button' tabindex='0'` y **ningún `aria-pressed`**: medido en vivo, `null` en los cinco chips. PI lo trae en el markup y PG lo escribe en JS, así que **es paridad, no invención** — y el comentario de `buttons.css:977` ya afirma que estos chips lo conservan, o sea que el contrato escrito no se cumple. Además `toggleWeeklyAlertFilter()` (`:3124-3142`) no toca ninguna región viva: pasar de 57 filas a 0 no produce anuncio (WCAG 4.1.3).

**Premisa verificada el 2026-08-10:** cierta, con los números de línea actualizados arriba. El estado activo hoy vive **solo** en la clase `inactive-filter`, que `syncLegendVisualState()` (`:3110-3122`) pone y quita.

**Files:**
- Modify: `public/js/modules/programacion_semanal/hot.js:3033-3042` (`renderAlertLegend`), `:3110-3122` (`syncLegendVisualState`), `:3124-3142` (`toggleWeeklyAlertFilter`)
- Modify: `tests/design-system/cascada-lps-a11y.test.mjs` (creado en la Task 2)

**Interfaces:**
- Consumes: el archivo de prueba de la Task 2.
- Produces: nada.

- [ ] **Step 1: Ampliar el candado y verlo fallar**

Añade a `tests/design-system/cascada-lps-a11y.test.mjs`:

```javascript
test('los chips de filtro de Programacion Semanal declaran su estado', async () => {
  const js = await read('public/js/modules/programacion_semanal/hot.js');
  // renderAlertLegend arma el chip como cadena; el atributo tiene que salir de ahi.
  const legend = js.match(/function renderAlertLegend\(\)[\s\S]*?\n  \}/);
  assert.ok(legend, 'no se encontro renderAlertLegend()');
  assert.match(legend[0], /aria-pressed/, 'los chips de PS no emiten aria-pressed');

  // Y el estado tiene que seguir al filtro, no quedarse fijo en el markup inicial.
  const sync = js.match(/function syncLegendVisualState\(\)[\s\S]*?\n  \}/);
  assert.ok(sync, 'no se encontro syncLegendVisualState()');
  assert.match(sync[0], /aria-pressed/, 'syncLegendVisualState no actualiza aria-pressed');
});
```

```bash
npx node --test tests/design-system/cascada-lps-a11y.test.mjs
```

Esperado: **FALLA** con `los chips de PS no emiten aria-pressed`.

- [ ] **Step 2: Emitir `aria-pressed` en el markup del chip**

En `renderAlertLegend()`, el chip nace sin filtro activo, así que arranca en `false`:

```javascript
      html += "<span class='pdc-legend-item " + escapeHtml(item.className) + "' data-filter='" + escapeHtml(item.key) + "' role='button' tabindex='0' aria-pressed='false'><span class='indicator'></span>" +
        escapeHtml(item.label) + " <span id='count-" + escapeHtml(item.key) + "' class='count-badge'>(...)</span></span>";
```

- [ ] **Step 3: Mantener `aria-pressed` sincronizado con el filtro**

`syncLegendVisualState()` ya es el único sitio que sabe qué chip está activo. El estado accesible se calcula **ahí**, junto al visual, para que no puedan divergir:

```javascript
  function syncLegendVisualState() {
    var $items = $('#psAlertsLegend .pdc-legend-item');
    if (weeklyAlertFilters.length === 0) {
      $items.removeClass('inactive-filter');
    } else {
      $items.addClass('inactive-filter');
      for (var i = 0; i < weeklyAlertFilters.length; i++) {
        $("#psAlertsLegend .pdc-legend-item[data-filter='" + weeklyAlertFilters[i] + "']").removeClass('inactive-filter');
      }
    }

    // El estado accesible se calcula en el mismo sitio que el visual, para que no
    // puedan divergir: `inactive-filter` y `aria-pressed` son la misma verdad
    // contada a dos publicos distintos.
    $items.each(function () {
      var clave = $(this).attr('data-filter');
      $(this).attr('aria-pressed', weeklyAlertFilters.indexOf(clave) > -1 ? 'true' : 'false');
    });

    $('#mobileAlertCount').text(weeklyAlertFilters.length);
  }
```

- [ ] **Step 4: Anunciar el resultado del filtro**

Filtrar cambia el contenido de la rejilla sin mover el foco: eso es WCAG 4.1.3 y necesita una región viva. Reutiliza `#save-status`, no: ese chip habla de guardado. Añade una región propia junto a la leyenda, en `views/programacion-semanal/programacion_semanal.view.php`, al lado de `#psAlertsLegend`:

```php
<span id="psFilterAnnounce" class="aia-sr-only" role="status" aria-live="polite"></span>
```

Y anúnciala al final de `applyFiltersAndRender()` (`hot.js:3025-3031`), que es el único punto por el que pasan **todos** los cambios de filtro:

```javascript
  function applyFiltersAndRender() {
    var filtered = getFilteredRows();

    updateLegendCounts(masterData);
    updateOrInitHot(filtered);
    renderMobileCards(filtered);

    // WCAG 4.1.3: pasar de 57 filas a 0 cambia toda la pantalla sin mover el
    // foco. Sin este anuncio, quien usa lector de pantalla no se entera de que
    // el filtro hizo algo.
    var texto = weeklyAlertFilters.length === 0
      ? filtered.length + ' actividades, sin filtros'
      : filtered.length + ' de ' + masterData.length + ' actividades con el filtro aplicado';
    $('#psFilterAnnounce').text(texto);
  }
```

> **Ojo:** el cambio de `updateLegendCounts(filtered)` a `updateLegendCounts(masterData)` de este bloque es de la **Task 4**. Si haces las tareas en orden, déjalo como `filtered` aquí y cámbialo allí. Se muestra ya en su forma final para que el bloque no haya que escribirlo dos veces.

- [ ] **Step 5: Comprobar que `aia-sr-only` existe**

```bash
grep -rn "aia-sr-only\|\.sr-only" public/css/ | head
```

Si la clase **no existe** con ese nombre, usa la que sí exista en el design system. **No inventes una clase nueva ni escribas estilos en línea**: eso es variante local en módulo migrado y `DESIGN.md` lo prohíbe. Si no hubiera ninguna, para y consulta hacia arriba.

- [ ] **Step 6: Ejecutar el candado y verlo pasar**

```bash
npx node --test tests/design-system/cascada-lps-a11y.test.mjs
```

Esperado: **PASA**, 2 tests.

- [ ] **Step 7: Verificar en navegador**

A 1180×820 en dark, como `test.A`, en `/programacion-semanal` con una semana que tenga actividades: pulsa un chip y luego otro.

Esperado:
1. En el árbol de accesibilidad, el chip pulsado tiene `aria-pressed="true"` y los demás `"false"`.
2. Al despulsar, todos vuelven a `"false"`.
3. La región `#psFilterAnnounce` cambia de texto en cada filtrado, y **no es visible** en pantalla.

- [ ] **Step 8: Commit**

```bash
git add public/js/modules/programacion_semanal/hot.js \
        views/programacion-semanal/programacion_semanal.view.php \
        tests/design-system/cascada-lps-a11y.test.mjs
git commit -m "fix(programacion-semanal): los chips de filtro declaran su estado y filtrar se anuncia

Los cinco chips emitian role=button sin aria-pressed —medido en vivo:
null en los cinco— en el unico modulo de los tres que no lo hacia, y que
ademas se citaba como ejemplo correcto en buttons.css:977. El estado
accesible se calcula en syncLegendVisualState, el mismo sitio que el
visual, para que no puedan divergir. Filtrar pasa de 57 filas a 0 sin
mover el foco, asi que ahora lo dice una region viva (WCAG 4.1.3)."
```

---

### Task 4: El vacío del filtro deja de hacerse pasar por el vacío de la semana

Junta dos hallazgos porque los dos mienten sobre **lo mismo** —qué hay detrás del filtro— y los dos se arreglan en `applyFiltersAndRender()`.

- **ICE 480** — con 57 actividades en la semana, un chip en cero deja la rejilla en 0 filas y `attachHtEmptyState` pinta «Sin actividades programadas esta semana · Usa «Agregar Actividad»…». El vacío del **conjunto filtrado** se presenta como el vacío de la **semana**, y las dos salidas que ofrece añaden actividades en vez de la única que recupera el dato: quitar el filtro.
- **ICE 270** — `applyFiltersAndRender()` llama `updateLegendCounts(filtered)` (`hot.js:3028`) en vez de sobre `masterData`. Medido en vivo: al activar «Por Comprometer», «Lista para Confirmar» cae de (1) a **(0)**. Si el chip elegido no devuelve filas, los cinco leen (0) a la vez y la única salida es volver a pulsar el mismo chip.

**Premisa verificada el 2026-08-10:** las dos ciertas. `attachHtEmptyState` se llama una sola vez, en `hot.js:2906-2912`, con textos fijos. **Dato que ahorra trabajo:** el componente (`public/js/design-system/ht-empty-state.js:32-34`) **reescribe título y cuerpo en cada llamada** a propósito — su comentario lo dice. Así que no hay que tocar el componente: basta volver a llamarlo con otro texto.

**Files:**
- Modify: `public/js/modules/programacion_semanal/hot.js:2906-2912` y `:3025-3031`

**Interfaces:**
- Consumes: `attachHtEmptyState(hot, { titulo, cuerpo })` de `public/js/design-system/ht-empty-state.js`.
- Produces: nada.

- [ ] **Step 1: Comprobar las dos premisas**

```bash
export COMPOSE_FILE=docker-compose.wt.yml
grep -n "updateLegendCounts(" public/js/modules/programacion_semanal/hot.js
sed -n '2906,2912p' public/js/modules/programacion_semanal/hot.js
sed -n '30,36p' public/js/design-system/ht-empty-state.js
```

Esperado: `updateLegendCounts(filtered)` en `applyFiltersAndRender`; el `attachHtEmptyState` con textos fijos; y el componente reescribiendo los textos en cada llamada. Si el componente **ya no** los reescribe, la Step 3 cambia: habría que tocar el componente, y eso es design system compartido — **consulta hacia arriba antes**.

- [ ] **Step 2: Contar sobre la semana, no sobre el filtro**

Un solo carácter de fondo, pero es el que hace que el chip vuelva a decir lo que el usuario cree leer. El estado del filtro ya se comunica por `inactive-filter` y por el `aria-pressed` de la Task 3, así que el contador no tiene que cargar también con eso:

```javascript
    // Los contadores dicen cuanto hay DETRAS de cada estado esta semana, no
    // cuanto sobrevive al filtro actual. Contar sobre `filtered` hacia que los
    // cinco chips leyeran (0) a la vez en cuanto un filtro no devolvia filas, y
    // la unica salida era volver a pulsar el mismo chip. Que un filtro este
    // activo ya lo dicen `inactive-filter` y `aria-pressed`.
    updateLegendCounts(masterData);
```

- [ ] **Step 3: Que el vacío diga cuál de los dos vacíos es**

Extrae la llamada a una función con nombre y llámala también desde `applyFiltersAndRender()`:

```javascript
  // Dos vacios distintos que hasta el 2026-08-10 se contaban igual: la semana
  // sin actividades, y el filtro que no devuelve ninguna. El segundo se
  // presentaba como el primero y ofrecia «Agregar Actividad» y «Autoprogramar»,
  // que anaden actividades — cuando la unica salida que recupera el dato es
  // quitar el filtro.
  function syncEmptyState() {
    if (!_htEmptyState) { return; }
    if (weeklyAlertFilters.length > 0) {
      _htEmptyState(hot, {
        titulo: 'Ninguna actividad coincide con el filtro',
        cuerpo: 'Esta semana tiene ' + masterData.length + ' actividades. Quita el filtro pulsando de nuevo el chip activo para volver a verlas.',
      });
      return;
    }
    _htEmptyState(hot, {
      titulo: 'Sin actividades programadas esta semana',
      cuerpo: 'Usa «Agregar Actividad» para programar una, o «Autoprogramar Actividades» para traerlas desde la programación intermedia.',
    });
  }
```

Guarda la función importada en `_htEmptyState` donde hoy está el `import(...)` de `:2906-2912`, y llama `syncEmptyState()` una vez ahí y otra al final de `applyFiltersAndRender()`.

- [ ] **Step 4: Verificar en navegador los dos vacíos, que es el punto de la tarea**

A 1180×820 en dark, como `test.A`, en `/programacion-semanal`:

1. **Semana con actividades y un filtro que no devuelve ninguna** → el panel debe decir «Ninguna actividad coincide con el filtro» y nombrar cuántas hay detrás. Los cinco contadores deben seguir mostrando el total de la semana, **no (0)**.
2. **Semana realmente vacía** (sin filtros) → el panel debe seguir diciendo «Sin actividades programadas esta semana» con las dos salidas de siempre.
3. Quita el filtro → vuelven las filas.

Los dos casos hay que verlos. Comprobar solo el primero deja sin medir que no se rompió el vacío legítimo. Consola sin errores.

- [ ] **Step 5: Commit**

```bash
git add public/js/modules/programacion_semanal/hot.js
git commit -m "fix(programacion-semanal): el vacio del filtro deja de hacerse pasar por el vacio de la semana

Con 57 actividades, un chip en cero pintaba «Sin actividades programadas
esta semana» y ofrecia las dos acciones que anaden actividades, en vez de
la unica que recupera el dato: quitar el filtro. Y los contadores se
calculaban sobre las filas ya filtradas, asi que los cinco chips leian (0)
a la vez. Ahora cuentan sobre masterData y el panel distingue los dos
vacios. Que un filtro este activo ya lo dicen inactive-filter y
aria-pressed, que es su trabajo."
```

---

### Task 5: «⚠ Sin asignar» gana el fondo que el código ya creía tener

**ICE 448**, más el residuo del **392** que la medición reubicó aquí.

`ps-cell-empty-alert` se añade en `programacion_semanal/hot.js:2592` y **no tiene ninguna regla CSS en todo el árbol** (verificado con `grep` recursivo sobre `public/css/`): el canal de fondo que el código pretende **no existe**. Es el mismo mecanismo que el chip «Auto-Guardado» de la tanda 1C: una clase que el código cree que hace algo y no hace nada.

`.ps-missing-assignment` (`programacion-semanal.css:1690-1693`) declara color y tamaño y **no declara `font-weight`**: mismo tamaño y mismo peso que la tinta de datos. Da 12,53:1 contra el fondo de celda, pero el texto normal da **16,90:1**, así que la marca es más apagada que el dato corriente y entre ambos solo hay **1,35:1**, bajo el 3:1 de WCAG 1.4.11.

**El residuo del 392, medido:** PI pinta la misma condición con `--ds-color-state-warning-text` y **peso 600** (`programacion-intermedia.css:532-537`); PS, con `--ds-color-state-critical-text` y sin peso.

**Y aquí no se unifica la severidad.** Tentaba hacerlo —la misma condición pintada con dos severidades distintas chirría— pero la disposición que el usuario aprobó para el ICE 448 dice literalmente «dar regla a `.ps-cell-empty-alert` y subir el peso del glifo; **es solo visual**». Cambiar `critical` por `warning` (o al revés) cambiaría qué severidad comunica un módulo entero: eso excede lo aprobado y sería decidir por el usuario en vez de ejecutar lo que decidió. Se hace lo aprobado —fondo y peso— y **la divergencia de severidad se anota como hallazgo nuevo** en la Task 9, con su medición, para que la decida quien puede.

**Files:**
- Modify: `public/css/programacion-semanal.css:1690-1693`
- Read: `public/css/programacion-intermedia.css:532-543`, `public/css/tokens.css`

**Interfaces:**
- Consumes: los tokens `--ds-color-state-*`.
- Produces: nada.

- [ ] **Step 1: Comprobar la premisa de la clase huérfana**

```bash
grep -rn "ps-cell-empty-alert" public/ views/ | grep -v node_modules
```

Esperado: **solo** apariciones en `public/js/modules/programacion_semanal/hot.js`, ninguna en `public/css/`. Si aparece una regla CSS, la premisa cambió: mide qué hace hoy y corrige el plan.

- [ ] **Step 2: Localizar el token de superficie de estado**

La regla nueva tiene que consumir un token de **fondo** ya existente. No inventes color ni escribas hex:

```bash
grep -n "state-critical\|state-warning" public/css/tokens.css
```

Anota el nombre exacto del token de superficie (algo como `--ds-color-state-critical-surface`). Si **no existe** un token de fondo para estado, para y consulta hacia arriba: crear un token es cambio del design system, no de este módulo.

- [ ] **Step 3: Dar regla a la clase huérfana y peso al glifo**

Se conserva `critical`, que es la severidad que PS tiene hoy: lo aprobado es «fondo y peso», no cambiar de severidad.

```css
/* La clase la aplicaba `hot.js:2592` desde siempre y no existia ninguna regla
   para ella en todo el arbol: el segundo canal que el codigo pretendia —el
   fondo— nunca llego a pintarse. Con solo color, la marca daba 1,35:1 contra
   el dato que la rodea, por debajo del 3:1 que pide WCAG 1.4.11. */
.ps-page #hot-container td.ps-cell-empty-alert {
  background-color: var(--ds-color-state-critical-surface);
}

.ps-missing-assignment {
  color: var(--ds-color-state-critical-text);
  font-size: var(--ds-type-size-sm);
  /* Sin peso, la marca tenia el mismo tamano y el mismo peso que la tinta de
     datos: ganaba solo por matiz. Paridad con `.pi-missing-resp`
     (programacion-intermedia.css:532-537), que ya pesa 600. */
  font-weight: 600;
}
```

- [ ] **Step 4: Medir el contraste, no estimarlo**

En el navegador, a 1180×820 en dark, en `/programacion-semanal` con una semana que tenga al menos una fila sin Responsable AIA:

```javascript
// En la consola del navegador:
const celda = document.querySelector('td.ps-cell-empty-alert');
const marca = celda.querySelector('.ps-missing-assignment');
console.log({
  fondoCelda: getComputedStyle(celda).backgroundColor,
  colorMarca: getComputedStyle(marca).color,
  pesoMarca: getComputedStyle(marca).fontWeight,
});
```

Esperado: el fondo de la celda **difiere** del de las celdas vecinas, y `pesoMarca` es `600`.

**Cuidado con qué norma mide cada cifra — el propio backlog las confunde y este plan heredó la confusión hasta corregirla el 2026-08-10:**

- **WCAG 1.4.3** (contraste de **texto**) mide la tinta contra el fondo sobre el que se lee. Aquí: la marca contra el fondo nuevo de su celda. Es la cifra fácil de sacar y **no** es la que la ficha exige.
- **WCAG 1.4.11** (contraste de elementos **no textuales**) es la que la ficha exige, y mide el componente contra **lo que lo rodea**. Aquí: **el fondo de la celda de alerta contra el fondo de una celda normal vecina**. Ese es el número que sostiene la afirmación de la tarea —que la marca gana por fondo y no solo por matiz— y es el que hay que dar.

Saca **las dos** y di cuál responde a cuál. El 1,35:1 que la ficha registra entre la marca y el texto de datos vecino **no se va a mover**, porque el contraste texto-a-texto es ciego al `font-weight`; dilo también, en vez de dejar creer que sí se movió.

**Si la cifra fondo-contra-fondo no llega a 3:1, no cambies el token para que llegue.** Elegir otro fondo es decisión de design system: anótala en `docs/decisiones-pendientes.md`, deja el CSS como está y dilo. Un hallazgo honesto con su número vale más que un verde fabricado.

- [ ] **Step 5: Verificar que no se rompió el golden visual**

Este cambio mueve píxeles en un módulo que tiene evidencia visual congelada.

```bash
export COMPOSE_FILE=docker-compose.wt.yml
npm run test:design-system:static
```

Esperado: **8/8**. Si el carril visual pide recaptura del golden de PS, **no la regeneres por tu cuenta**: `AGENTS.md` §Verificación dice que los cambios visuales requieren aprobación explícita. Consulta hacia arriba.

- [ ] **Step 6: Commit**

```bash
git add public/css/programacion-semanal.css
git commit -m "fix(programacion-semanal): el aviso «Sin asignar» gana el fondo que el codigo ya creia tener

hot.js:2592 anadia `ps-cell-empty-alert` desde siempre y esa clase no tenia
ninguna regla en todo el arbol: el segundo canal —el fondo— no existia. Y
la marca no declaraba peso, asi que tenia el mismo tamano y el mismo peso
que la tinta de datos: 1,35:1 entre ambos, bajo el 3:1 de WCAG 1.4.11.
Ahora gana por forma y por fondo, no solo por matiz, y pesa lo mismo que
su hermana `.pi-missing-resp` de Intermedia."
```

---

### Task 6: El resumen de cierre dice cuántos no está enseñando

**ICE 400.** Es el momento de mayor consecuencia del ciclo semanal y el que peor informa: con 30 actividades bloqueadas el KPI dice 30 y el detalle enseña 8, así que el usuario cree que arreglando esas ocho termina.

**Premisa verificada el 2026-08-10:** cierta, y **hay cuatro cortes, no uno**. `buildCloseSummary` (`hot.js:3443-3537`) acota con `items.length < 8` en `:3488` (bloqueantes), `:3502` (aviso por avance bajo), `:3512` (aviso por restricción) y `:3525` (restricciones de ejecución). Los contadores **sí** siguen incrementándose por encima de 8. `renderSummaryList` (`:3542-3553`) emite solo `<li><strong>actividad</strong><br><small>detalle</small></li>` y no añade nada.

Arreglar solo la lista de bloqueantes dejaría las otras tres mintiendo igual. El remedio va en `renderSummaryList`, que es por donde pasan las cuatro.

**Files:**
- Modify: `public/js/modules/programacion_semanal/hot.js:3443-3553`

**Interfaces:**
- Consumes: nada.
- Produces: nada.

- [ ] **Step 1: Comprobar los cuatro cortes**

```bash
grep -n "length < 8" public/js/modules/programacion_semanal/hot.js
sed -n '3542,3553p' public/js/modules/programacion_semanal/hot.js
```

Esperado: cuatro coincidencias. Si son más o menos, ajusta el alcance y dilo en el informe.

- [ ] **Step 2: Pasar el total a `renderSummaryList`**

Cada lista ya tiene su contador al lado. Lo que falta es que la lista lo conozca. Cambia la firma para que reciba el total y lo compare con lo que va a pintar:

```javascript
  // Las cuatro listas se cortan a 8 elementos mientras su contador sigue
  // subiendo. Con 30 bloqueantes el KPI decia 30 y el detalle ensenaba 8, asi
  // que el usuario creia que arreglando esas ocho terminaba — en el momento de
  // mayor consecuencia del ciclo semanal.
  function renderSummaryList(items, total) {
    var html = '';
    for (var i = 0; i < items.length; i++) {
      html += '<li><strong>' + escapeHtml(items[i].actividad) + '</strong><br><small>' + escapeHtml(items[i].detalle) + '</small></li>';
    }
    var ocultas = (typeof total === 'number' ? total : items.length) - items.length;
    if (ocultas > 0) {
      html += '<li class="ps-close-summary__mas">y ' + ocultas + (ocultas === 1 ? ' más' : ' más') + ' que no caben en esta lista</li>';
    }
    return html;
  }
```

> Conserva la forma de retorno actual (cadena o inserción en el DOM): **léela antes de editar** y no la cambies. Si `renderSummaryList` hoy inserta directamente en vez de devolver, adapta el bloque manteniendo su contrato.

- [ ] **Step 3: Pasar el total en las cuatro llamadas**

Localiza las cuatro invocaciones de `renderSummaryList` y añade el contador que ya existe junto a cada lista (`summary.blockingCount` y sus hermanos). **No inventes el nombre del contador: léelo de `buildCloseSummary`.**

- [ ] **Step 4: Verificar en navegador con más de ocho**

A 1180×820 en dark, como `test.A`, en `/programacion-semanal` con una semana que tenga **más de 8** actividades bloqueantes: abre el modal de cierre.

Esperado: el KPI y la lista cuadran — la lista enseña 8 y remata con «y N más que no caben en esta lista», y `8 + N` es exactamente el número del KPI. Compruébalo **con la aritmética**, no de vista.

Si no hay ninguna semana con más de 8 bloqueantes en el sandbox, **no cambies datos de producción para fabricarla**: crea la condición en el proyecto de sandbox, mídela, y **restaura**, verificando la restauración con una consulta.

- [ ] **Step 5: Commit**

```bash
git add public/js/modules/programacion_semanal/hot.js
git commit -m "fix(programacion-semanal): el resumen de cierre dice cuantos no esta ensenando

Las cuatro listas se cortaban a 8 mientras su contador seguia subiendo: con
30 bloqueantes el KPI decia 30 y el detalle ensenaba 8. El remate va en
renderSummaryList, que es por donde pasan las cuatro — arreglar solo la de
bloqueantes habria dejado las otras tres mintiendo igual."
```

---

### Task 7: Cuando el servidor bloquea el cierre, dice qué filas

**ICE 168.** El cliente deshabilita «Confirmar» con `hasBlocking` (`hot.js:3558`, aplicado en `:3576`); el servidor puede aun así responder `No_Bloqueado`, que la UI traduce en un texto que **no dice cuáles**. El usuario atraviesa el modal entero creyendo que estaba listo y sale sin saber dónde mirar.

**Premisa verificada el 2026-08-10:** cierta. `SemanalApiController::bloquearCompromisos()` (`:925-939`) hace un **`COUNT(*)`** y devuelve `["respuesta" => "No_Bloqueado", "mensaje" => "Hay actividades sin compromiso o sin asignaciones obligatorias."]`. No selecciona ni devuelve ningún `Id`. En el cliente, `confirmCommitments` (`:4062-4116`) pinta un párrafo genérico en la rama `No_Bloqueado` (`:4108-4110`).

**La ficha ya eligió el camino barato y hay que respetarlo:** no unificar los dos gates, sino que **la respuesta traiga los ids y la UI los filtre en la rejilla**.

**Files:**
- Modify: `src/Controllers/Api/SemanalApiController.php:925-939`
- Modify: `public/js/modules/programacion_semanal/hot.js:4108-4110`

**Interfaces:**
- Consumes: nada.
- Produces: la respuesta JSON de `bloquearCompromisos` gana la clave `ids` (array de enteros) en la rama `No_Bloqueado`. **Es un cambio de forma de una respuesta de API**: es aditivo (no quita ninguna clave existente), pero **verifica que ningún otro consumidor la lea** antes de tocarla.

- [ ] **Step 1: Comprobar quién consume esa respuesta**

```bash
grep -rn "No_Bloqueado" --include=*.js --include=*.php --include=*.mjs . | grep -v node_modules
```

Esperado: el controlador y `programacion_semanal/hot.js`. **Si aparece un tercer consumidor** (una prueba, otro módulo, la vista móvil), para y consulta hacia arriba: añadir una clave sería seguro, pero cambiar lo que una prueba mide no lo es sin preguntar.

- [ ] **Step 2: Que la consulta traiga los ids en vez de contarlos**

El `COUNT(*)` de `:928` se convierte en un `SELECT` de la clave, con el **mismo** criterio y el **mismo** aislamiento por `project_id`. Prepared statement, como manda `AGENTS.md`:

```php
// Antes se hacia COUNT(*) y se respondia «hay actividades sin compromiso», sin
// decir cuales: el usuario atravesaba el modal entero y salia sin saber donde
// mirar. Mismo criterio, mismo aislamiento por proyecto; lo que cambia es que
// ahora se devuelve la clave de cada fila para que la rejilla pueda senalarlas.
$stmt = $db->prepare(
    'SELECT Id FROM ' . $tabla . '
     WHERE project_id = :project_id AND Semana = :semana AND Activa = 1
       AND (Compromiso IS NULL OR Compromiso <= 0
            OR Sub_Contratista IS NULL OR Sub_Contratista = "" OR Sub_Contratista = "null"
            OR Responsable_AIA IS NULL OR Responsable_AIA = "" OR Responsable_AIA = "null")'
);
```

> **Copia el criterio del `WHERE` que hay hoy en `:928`, no el de este ejemplo.** Este bloque reproduce lo que el reconocimiento leyó, pero el criterio vigente manda: si difieren, gana el del código, y cambiarlo sería alterar el gate, no informarlo — eso se consulta.

Y la respuesta:

```php
echo json_encode([
    "respuesta" => "No_Bloqueado",
    "mensaje"   => "Hay actividades sin compromiso o sin asignaciones obligatorias.",
    "ids"       => array_map('intval', $ids),
]);
```

- [ ] **Step 3: Que la UI señale las filas en vez de describirlas**

En la rama `No_Bloqueado` de `confirmCommitments` (`:4108-4110`), además del texto, filtra la rejilla a esas filas:

```javascript
        // El texto generico obligaba a buscar a mano cual de las 57 filas
        // faltaba. Ahora el servidor dice cuales y la rejilla las ensena.
        var ids = (respuesta && Array.isArray(respuesta.ids)) ? respuesta.ids : [];
        if (ids.length > 0) {
          mostrarFilasBloqueantes(ids);
        }
```

Implementa `mostrarFilasBloqueantes(ids)` reutilizando el camino de filtrado que ya existe en el módulo (`getFilteredRows` / `updateOrInitHot`). **No dupliques la lógica de filtrado**: si la única vía limpia exige tocar `getFilteredRows`, hazlo ahí.

- [ ] **Step 4: Verificar los dos roles y las dos ramas**

Como `test.A` en `/programacion-semanal`, con una semana que tenga al menos una actividad sin compromiso: intenta cerrar.

Esperado: el mensaje sigue apareciendo **y** la rejilla queda mostrando exactamente esas filas. Comprueba en la pestaña de red que la respuesta trae `ids` y que su longitud coincide con las filas mostradas.

Y la rama contraria, que es la que más se olvida: con una semana **sin** bloqueantes, el cierre debe seguir funcionando igual que antes.

Rol denegado — el Visualizador no debe poder bloquear compromisos:

```bash
curl -s -o /dev/null -w 'bloquear como V: %{http_code}\n' -b /tmp/cookieV.txt \
  -X POST http://localhost:8091/api/semanal/... # ruta real, leela de public/index.php
```

Esperado: 403. Si responde 200, **para y consulta**: es un agujero de permisos.

- [ ] **Step 5: Restaurar los datos y verificarlo**

Si creaste la condición bloqueante, deshazla y **compruébalo con una consulta**, no de memoria.

- [ ] **Step 6: Commit**

```bash
git add src/Controllers/Api/SemanalApiController.php public/js/modules/programacion_semanal/hot.js
git commit -m "fix(programacion-semanal): cuando el servidor bloquea el cierre, dice que filas

bloquearCompromisos() hacia COUNT(*) y respondia «hay actividades sin
compromiso» sin decir cuales, en el momento de mayor consecuencia del ciclo
semanal. Ahora devuelve los Id —mismo criterio, mismo aislamiento por
project_id, prepared statement— y la rejilla los ensena. La clave `ids` es
aditiva: ningun consumidor existente pierde nada."
```

---

### Task 8: El momento firma dice por qué está bloqueado

**ICE 150.** Elegido por el usuario como el momento firma del ciclo, y su ficha está aprobada con las dos mitades: **(a)** atar el botón a su causa, que es contrato de accesibilidad, y **(b)** un cierre memorable, que es animación nueva.

**Premisa verificada el 2026-08-10:** cierta. `#btn_confirmar_compromisos_semana` (`programacion_semanal.view.php:156`) es `<input type="button" class="aia-btn aia-btn--primary btn-lg" value="Confirmar" aria-label="Confirmar cerrar compromisos">`: **sin** `disabled` inicial, **sin** `aria-describedby`, **sin** `aria-busy`. Se deshabilita desde JS en `:3576` por `hasBlocking`, y el motivo (`56 · Por completar`) vive en un KPI a media pantalla.

**Files:**
- Modify: `views/programacion-semanal/programacion_semanal.view.php:156`
- Modify: `public/js/modules/programacion_semanal/hot.js:3556-3576` y `:4062-4116`
- Modify: `public/css/programacion-semanal.css`

**Interfaces:**
- Consumes: el resumen de la Task 6 (`summary.blockingCount`).
- Produces: nada.

- [ ] **Step 1: Atar el botón a su causa (mitad `a`)**

En `renderCloseSummary`, donde ya se calcula `hasBlocking`, añade la etiqueta que dice **por qué**, no solo que sí:

```javascript
    var hasBlocking = summary.blockingCount > 0;
    var $btn = $('#btn_confirmar_compromisos_semana');
    $btn.prop('disabled', hasBlocking).toggleClass('disabled', hasBlocking);

    // El motivo vivia en un KPI a media pantalla y el boton no lo nombraba: se
    // sabia que estaba bloqueado, no por que. `aria-describedby` lo ata al KPI
    // que lo causa, y la etiqueta propia lo dice en claro para quien no navega
    // por la pagina entera.
    if (hasBlocking) {
      $btn.attr('aria-describedby', 'kpi-por-completar');
      $btn.attr('aria-label', 'Confirmar cerrar compromisos. Faltan ' + summary.blockingCount + ' por completar');
    } else {
      $btn.removeAttr('aria-describedby');
      $btn.attr('aria-label', 'Confirmar cerrar compromisos');
    }
```

> **`kpi-por-completar` es un id de ejemplo.** Léelo de la vista: es el KPI que muestra `56 · Por completar`. Si ese elemento **no tiene id**, dáselo en la vista — un id estable es parte de esta tarea.

- [ ] **Step 2: `aria-busy` mientras confirma**

`confirmCommitments` (`:4068`) ya hace `prop('disabled', true).val('Confirmando...')`. Añade el estado ocupado y quítalo en el `.always()` de `:4114`:

```javascript
        $('#btn_confirmar_compromisos_semana').attr('aria-busy', 'true');
        // ... y en el .always():
        $('#btn_confirmar_compromisos_semana').removeAttr('aria-busy');
```

- [ ] **Step 3: El sello sobre la barra de fase (mitad `b`)**

La barra de fase **ya cambia** de `Programación` a `Calificación` al confirmar. La animación marca ese cambio; no inventa uno nuevo. Consume el token de motion del design system y **respeta `prefers-reduced-motion`**, que no es opcional:

```css
/* El acto mas consecuente del ciclo semanal se acusaba con un modal de texto
   plano. El sello marca el cambio de fase que YA ocurre —Programacion pasa a
   Calificacion— en vez de anadir un momento nuevo. */
@keyframes ps-sello-fase {
  from { transform: scale(1.06); opacity: 0.4; }
  to   { transform: scale(1);    opacity: 1;   }
}

.ps-page .ps-phase-bar.is-sellada {
  animation: ps-sello-fase var(--ds-motion-duration-md) var(--ds-motion-easing-standard) both;
}

@media (prefers-reduced-motion: reduce) {
  .ps-page .ps-phase-bar.is-sellada { animation: none; }
}
```

Comprueba los nombres reales de los tokens de motion (`grep -n "ds-motion" public/css/tokens.css`) y el selector real de la barra de fase antes de escribir. **Si no existen tokens de motion, para y consulta**: inventar duraciones es variante local.

- [ ] **Step 4: Verificar en navegador las dos mitades y los dos estados**

A 1180×820 en dark, como `test.A`, en `/programacion-semanal`:

1. **Con bloqueantes:** el botón está deshabilitado, su nombre accesible dice «Faltan N por completar» con la N correcta, y `aria-describedby` apunta al KPI. Compruébalo en el árbol de accesibilidad.
2. **Sin bloqueantes:** el botón está activo y su nombre vuelve al genérico, sin `aria-describedby` colgando.
3. **Al confirmar:** la barra de fase pasa a `Calificación` con el sello, y el botón lleva `aria-busy` mientras dura.
4. **Con `prefers-reduced-motion: reduce`** activado en el navegador: el cambio de fase ocurre igual, **sin** animación.

- [ ] **Step 5: Suite estática**

```bash
export COMPOSE_FILE=docker-compose.wt.yml
npm run test:design-system:static
```

Esperado: 8/8. Si el carril visual pide recaptura, **consulta antes de regenerar**.

- [ ] **Step 6: Commit**

```bash
git add views/programacion-semanal/programacion_semanal.view.php \
        public/js/modules/programacion_semanal/hot.js \
        public/css/programacion-semanal.css
git commit -m "feat(programacion-semanal): el momento firma dice por que esta bloqueado y acusa el cambio de fase

El acto mas consecuente del ciclo semanal abria con el boton disabled sin
title, sin aria-describedby y sin aria-busy, y el motivo vivia en un KPI a
media pantalla. Ahora el boton nombra su causa y la barra de fase marca el
paso de Programacion a Calificacion que ya ocurria. Respeta
prefers-reduced-motion."
```

---

### Task 9: Los doce hallazgos quedan con disposición escrita

Ninguno puede quedarse mudo. Dos se cierran **sin tocar código** porque al medirlos resultaron ya arreglados, y eso hay que escribirlo aunque contradiga lo registrado.

**Files:**
- Modify: `docs/EXPERIMENTS.md`

**Interfaces:**
- Consumes: los commits de las tareas 1-8.
- Produces: nada.

- [ ] **Step 1: Recoger los hashes reales**

```bash
git log --oneline 0e48bdc8..HEAD
```

**Usa los hashes que salgan**, no los que imagines. La tanda 1A escribió sus cierres con hashes verificados y por eso se pueden auditar.

- [ ] **Step 2: Escribir la disposición de los diez arreglados**

En cada fila de la tabla de `docs/EXPERIMENTS.md`, sustituye `abierto` (o `abierto · aprobado`) por el cierre, con el mismo formato que usó la tanda 1A: `cerrado <hash> — <qué se hizo y qué se midió>`. Donde la medición contradiga la ficha, **dilo en el propio cierre**, como hizo `PROY-001` («Menos grave de lo registrado»).

- [ ] **Step 3: Escribir los dos cierres que no llevan código**

Estos dos son el valor de la tanda, porque el backlog afirmaba lo contrario:

- **ICE 280 (PI avisa después del hecho):**
  `cerrado — ya estaba arreglado. Medido el 2026-08-10: la celda de restricción nace `readOnly` cuando falta el Responsable AIA (`programacion_intermedia/hot.js:855-858`, `:950`, `:997-1028`), que es exactamente el remedio que pedía la ficha. El `revertCell` de `:4166-4178` no es el aviso tardío que se registró: su propio comentario lo declara red de seguridad para pegados y cambios programáticos, que no pasan por el editor. Lo arregló `7ff39b54` (N-1, Task 38, 2026-08-05), **después** de escribirse esta ficha.`
- **ICE 392 (paridad PI/PS del Responsable AIA):**
  `cerrado <hash Task 5> — la ficha decía que «Intermedia no marca esa celda de ninguna forma» y es falso: `piResponsableRenderer` (`hot.js:3127-3148`) pinta `.pi-missing-resp` con glifo y motivo, y tiene regla en `programacion-intermedia.css:532-537`. La asimetría real era la contraria a la registrada — PS era el módulo **peor** servido: sin peso de fuente y con su clase de fondo `ps-cell-empty-alert` huérfana. Se cerró junto al ICE 448.`

- [ ] **Step 4: Comprobar que no queda ninguno mudo**

```bash
grep -c "| abierto" docs/EXPERIMENTS.md
grep -n "abierto" docs/EXPERIMENTS.md | grep -viE "1C|pulido"
```

Esperado: de los 12 de esta tanda, **cero** siguen como `abierto`. Los 16 de la tanda 1C **siguen abiertos a propósito** — no los toques aquí.

- [ ] **Step 5: Wiki y gates**

```bash
export COMPOSE_FILE=docker-compose.wt.yml
npm run test:wiki
npm run test:rbac-parity
npm run test:design-system:static
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
```

Esperado: los cuatro en verde, DS en 8/8. **Pega la salida real en el informe**: nada se declara hecho sin ella.

- [ ] **Step 6: Commit**

```bash
git add docs/EXPERIMENTS.md
git commit -m "docs(backlog): la tanda 1B cierra sus doce hallazgos de la cascada LPS

Diez con codigo y dos sin tocarlo: al medirlos resultaron ya arreglados por
7ff39b54 (N-1, Task 38), posterior a la redaccion de sus fichas. El del
ICE 392 ademas estaba contado al reves — el modulo peor servido era
Programacion Semanal, no Intermedia."
```

---

## Self-Review

**Cobertura de los 12 hallazgos:** 504→T1, 480→T4, 450→T2, 448→T5, 432→T3, 400→T6, 392→T5+T9, 336→T2, 280→T9, 270→T4, 168→T7, 150→T8. Los doce tienen tarea, y los doce tienen disposición escrita en la T9.

**Consistencia de nombres entre tareas:** `crearSaveStatus({selector, etiquetaGuardado, claseOculta})` se define en la T2 Step 5 y se consume en las Steps 6 y 7 con esa firma. `renderSummaryList(items, total)` se redefine en la T6 Step 2 y sus cuatro llamadas se actualizan en la Step 3. `syncEmptyState()` se define y se llama dentro de la T4. `mostrarFilasBloqueantes(ids)` se define y se llama dentro de la T7. `summary.blockingCount` lo produce `buildCloseSummary` y lo consumen la T6 y la T8.

**Solape declarado:** la T3 Step 4 y la T4 Step 2 editan **el mismo bloque** (`applyFiltersAndRender`). La T3 lo muestra ya en su forma final con una nota explícita para que no haya que escribirlo dos veces ni se pisen entre sí.

**Puntos que van a la cola de decisiones y se saltan, marcados en su paso y no al final:** la divergencia de severidad entre PI y PS (anotada ya como D-F1-1, no se toca), un tercer consumidor de `No_Bloqueado` (T7 Step 1), la ausencia de token de superficie de estado (T5 Step 2) o de motion (T8 Step 3), y cualquier recaptura de golden (T5 Step 5, T8 Step 5).

**La única excepción a «anota y sigue»:** un `200` donde se espera `403` (T1 Step 5, T7 Step 4). Eso no es una decisión de producto, es un agujero de permisos abierto, y se avisa **de inmediato** hacia arriba en vez de encolarse.

**Lo que este plan no cubre:** los 16 hallazgos de la tanda 1C, que reciben su propio plan al cerrar esta —escribirlo por adelantado repetiría el error de la 1A, cuyo plan se quedó obsoleto a mitad porque el repo se movió mientras se ejecutaba—. Tampoco la fase 9 de `improve-app`, que corre en frío al cerrar el frente entero.
