---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-03
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-03-usabilidad-altas-y-medias.md
resumen: Cerrar los 26 hallazgos de severidad alta y media del inventario de usabilidad de superficies sin tabla…
---

# Usabilidad: altas y medias — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cerrar los 26 hallazgos de severidad alta y media del inventario de usabilidad de superficies sin tabla (`goals/repaso-usabilidad-no-tablas/inventario-usabilidad.md`), en cinco fases de riesgo creciente.

**Architecture:** No hay arquitectura nueva. Son correcciones puntuales sobre superficies existentes, agrupadas por el tipo de trabajo que exigen: texto (F1), estados vacíos (F2), consistencia y accesibilidad (F3), layout (F4) y navegación (F5). La única pieza nueva es un componente compartido de estado vacío para Handsontable, que hoy no tiene ninguno; todo lo demás reutiliza patrones ya presentes en el repositorio.

**Tech Stack:** PHP 8.3 sobre Docker Compose, vistas planas en `views/`, DataTables y Handsontable para las mallas, Playwright para la verificación de navegador, tokens CSS en `public/css/tokens.css`.

## Global Constraints

- **Alcance visual: desktop ≥1180 px y dark mode exclusivamente.** Viewport canónico de validación: **1180×820**. Prohibido producir cambios, pruebas o evidencia para mobile, tablet o el tema `linen`, ni siquiera para generar capturas. Si una tarea parece pedirlo, no se hace y se dice.
- **La sesión local se abre solo por la puerta de servicio:** `http://localhost:8081/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E`. Cuentas: `test.A` (Admin), `test.R` (Residente), `test.V` (Visualizador). **Nunca teclear credenciales en `/login` ni pedirle a una persona que entre.**
- **Todo se ejecuta dentro del contenedor `app`.** Nunca un PHP del host.
- **No se regeneran snapshots ni baselines para forzar verde.** Un cambio visual requiere aprobación explícita.
- **Precedencia ante conflictos:** código > `AGENTS.md` > `memoria/`.
- **Vocabulario de dominio:** consultar `GLOSARIO.md` antes de renombrar nada. CNC, CNP, CIC, PPC, PAC, LPS, PDC, restricción y compromiso son términos correctos y no se tocan.
- **Contraste:** usar siempre `tests/browser/support/contrast.mjs`. **No escribir una sonda propia**: la compartida rasteriza por canvas (obligatorio con `oklch()`, que `getComputedStyle` no resuelve) y compone alfa sobre los ancestros. Ya hubo dos sesiones que escribieron copias y tuvieron que sustituirlas.
- **Verificación por fase, no al final.** Cada fase termina con su propio recorrido de navegador a 1180×820 dark sobre las rutas que tocó.
- **Commits atómicos.** No incluir `.env`, evidencia local ni trabajo ajeno.

## Estado del repositorio que NO es de este plan

Antes de empezar, tres rojos conocidos que **no debe arreglar ni contabilizar** quien ejecute:

1. `contracts.test.mjs` falla dos veces por *«worktree and index must be clean»*. Lo ensucian dos archivos `Sin título*.base` que Obsidian dejó sueltos en la raíz del repo. No son de nadie; borrarlos deja la suite en un solo rojo.
2. `shell-navigation.test.mjs` espera `--ds-sidebar-width-expanded: 17.5rem` cuando el token vale `15rem`. Es drift real y **tiene dueño en la línea B2 del reparto**.
3. La sesión de tintes tiene un guard de emparejamiento rojo a propósito, sin publicar. Cuando entre, `tests/design-system/*.test.mjs` tendrá un vigilante más.

**No los maquilles, no los cuentes como tuyos y no los uses como excusa para no verificar lo tuyo.**

## Dependencias externas

- **F3 depende de la línea B** solo para la verificación visual compartida. Las tareas de F3 que no son visuales (labels, `autocomplete`) no esperan a nada.
- **Este plan NO depende de la línea D** (puerta de servicio para `admin/`). H-33 y H-35 tocan `/admin/login` y `/admin/password/forgot`, que son páginas **públicas** y se alcanzan sin sesión — así se auditaron. La línea D hace falta para las 14 pantallas *internas* de admin, que **no están en este plan**.

## Fuera de alcance, explícito

| Hallazgo | Dueño |
|---|---|
| H-03 `/indicadores` | Decisión 7, línea propia |
| H-24, H-39 | Panel de inicio, línea F |
| H-14 | Goal de tablas, ya cerrado |
| Las 9 bajas | H-06 H-11 H-15 H-22 H-23 H-27 H-31 H-32 H-37 — inventariadas, no aprobadas |

> **Contradicción detectada en el reparto.** `docs/superpowers/specs/2026-08-03-reparto-trabajo-pendiente-design.md` §E lista H-03 entre los que *salen* de la línea E y después dice «La decisión 7 entra aquí». **Este plan lo resuelve excluyéndolo**, que es coherente con el recuento de 26 que el propio documento acepta. Si el usuario quería lo contrario, hay que rehacer la aritmética a 27.

## File Structure

| Archivo | Responsabilidad | Fase |
|---|---|---|
| `views/pdc/pdc.view.php` | Etiquetas de los chips de estado del PDC | F1 |
| `public/js/modules/pdc/hot.js` | Cabeceras y anchos de la malla del PDC | F1, F4 |
| `views/control-cambios/controlCambios.view.php` | Estado vacío (DataTables), anchos de filtro, `h1` | F2, F4, F5 |
| `public/js/design-system/ht-empty-state.js` | **NUEVO.** Estado vacío compartido para Handsontable | F2 |
| `public/css/design-system/components/ht-empty-state.css` | **NUEVO.** Estilo del anterior | F2 |
| `admin/views/pages/login.php` | Labels y `autocomplete` | F3 |
| `admin/views/pages/password-forgot.php` | Label del campo de correo | F3 |
| `public/css/bi-control-tower.css` | Contraste del chip, objetivo de 20 px, pestañas | F3, F5 |
| `docs/design-system/state-token-exceptions.json` | Retirar la excepción de `.bi-chip` al resolverla | F3 |
| `tests/browser/bi-chip-contrast.spec.mjs` | **NUEVO.** Gate de contraste del chip | F3 |

---

## Fase F1 · Copia (riesgo nulo)

Cubre **H-28, H-29, H-30**. Solo texto visible. Sin cambios de comportamiento.

### Task 1: Tildes en los chips de estado del PDC (H-28)

**Files:**
- Modify: `views/pdc/pdc.view.php`

**Interfaces:**
- Consumes: nada.
- Produces: nada. Cambio aislado de cadenas.

**Contexto:** siete chips escriben «Informacion», «contratacion» sin tilde, mientras el mensaje de estado vacío de la misma pantalla sí escribe «contratación». Es incoherente dentro de una sola vista.

- [ ] **Step 1: Localizar las cadenas exactas**

```bash
docker compose exec app grep -n "Informacion pendiente\|Inicio de contratacion vencido\|Contratacion atrasada\|Contratacion cerrada tarde\|Contratacion cerrada a tiempo\|Contratacion en curso\|Contratacion pendiente de inicio" views/pdc/pdc.view.php
```

Esperado: siete líneas. Si aparecen menos, algunas cadenas viven en `public/js/modules/pdc/hot.js`; búscalas también ahí antes de seguir.

- [ ] **Step 2: Corregir las siete cadenas**

Sustituciones exactas, respetando mayúsculas iniciales:

| Antes | Después |
|---|---|
| `Informacion pendiente` | `Información pendiente` |
| `Inicio de contratacion vencido` | `Inicio de contratación vencido` |
| `Contratacion atrasada` | `Contratación atrasada` |
| `Contratacion cerrada tarde` | `Contratación cerrada tarde` |
| `Contratacion cerrada a tiempo` | `Contratación cerrada a tiempo` |
| `Contratacion en curso` | `Contratación en curso` |
| `Contratacion pendiente de inicio` | `Contratación pendiente de inicio` |

**No toques** «Plan de Compras y Contrataciones» ni ningún identificador, clase CSS, clave de array o valor de `data-*`. Solo texto que el usuario lee.

- [ ] **Step 3: Verificar que no quedan restos**

```bash
docker compose exec app grep -rn "Contratacion\|Informacion" views/pdc/ | grep -v "class=\|data-\|id="
```

Esperado: sin resultados de texto visible.

- [ ] **Step 4: Comprobar en el navegador**

Abrir `http://localhost:8081/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E`, navegar a `/pdc` a 1180×820 dark y confirmar que los siete chips llevan tilde.

- [ ] **Step 5: Commit**

```bash
git add views/pdc/pdc.view.php
git commit -m "fix(pdc): tildes en los siete chips de estado"
```

### Task 2: Retirar la unidad cruda «count» de los KPI de BI (H-29)

**Files:**
- Modify: el archivo que compone el valor del KPI (localizar en Step 1)
- Test: `tests/browser/bi-kpi-copy.spec.mjs` (crear)

**Interfaces:**
- Consumes: nada.
- Produces: nada.

**Contexto:** los cuatro KPI de `/bi/control-tower` muestran «0 count». `count` es el nombre de la unidad en la capa de datos y se está filtrando a la interfaz.

- [ ] **Step 1: Localizar el origen**

```bash
docker compose exec app grep -rn "count" src/Services/Bi/ views/bi/ public/js/ --include=*.php --include=*.js | grep -iE "unidad|unit|suffix|format" | head -20
```

Si no aparece, el sufijo llega desde la base de datos: comprueba con
`docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev -e "SELECT DISTINCT unidad FROM bi_control_tower_summary;"'`.
**No sigas hasta saber de dónde sale.** Si viene de datos, el arreglo es de presentación: mapear la unidad a cadena vacía al pintar, nunca reescribir la fila.

- [ ] **Step 2: Escribir la prueba que falla**

```js
// tests/browser/bi-kpi-copy.spec.mjs
import { test, expect } from '@playwright/test';

test('los KPI del control tower no muestran la unidad cruda "count"', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.emulateMedia({ colorScheme: 'dark' });
  await page.goto('http://localhost:8081/dev/entrar?u=test.R&p=' + encodeURIComponent('PDC Sandbox E2E'));
  await page.goto('http://localhost:8081/bi/control-tower');
  await page.waitForLoadState('networkidle');
  const texto = await page.locator('body').innerText();
  expect(texto).not.toMatch(/\bcount\b/i);
});
```

- [ ] **Step 3: Ejecutar la prueba y confirmar que falla**

```bash
npx playwright test tests/browser/bi-kpi-copy.spec.mjs --workers=1
```

Esperado: FALLA, porque el texto contiene «0 count».

- [ ] **Step 4: Arreglar la presentación**

Mapear la unidad `count` a cadena vacía en el punto donde se compone el valor mostrado. Deja el resto de unidades intactas: `%`, `días` y cualquier otra deben seguir apareciendo.

- [ ] **Step 5: Ejecutar la prueba y confirmar que pasa**

```bash
npx playwright test tests/browser/bi-kpi-copy.spec.mjs --workers=1
```

Esperado: PASA.

- [ ] **Step 6: Commit**

```bash
git add tests/browser/bi-kpi-copy.spec.mjs
git add -u
git commit -m "fix(bi): la unidad interna 'count' deja de llegar a los KPI"
```

### Task 3: Desambiguar las columnas homónimas de control de cambios (H-30)

**Files:**
- Modify: `views/control-cambios/controlCambios.view.php`

**Interfaces:**
- Consumes: nada.
- Produces: nada.

**Contexto:** conviven las columnas «Detalle Solicitante» y «Detalle». Nada indica en qué se diferencian.

- [ ] **Step 1: Averiguar qué contiene cada una**

```bash
docker compose exec app grep -n "Detalle" views/control-cambios/controlCambios.view.php
```

Después mira de qué campo se alimenta cada `<th>`. **No renombres a ciegas:** el nombre nuevo tiene que describir el dato real. Si «Detalle» resulta ser el detalle del cambio, el par correcto es «Solicitante» / «Detalle del cambio».

- [ ] **Step 2: Renombrar solo los `<th>`**

Cambia el texto visible de las dos cabeceras. **No toques** `data-*`, índices de columna de DataTables, ni las claves del JSON que las alimenta: romperías el mapeo de filtros.

- [ ] **Step 3: Comprobar en el navegador**

Ir a `/control-cambios` a 1180×820 dark y confirmar que las dos cabeceras se distinguen y que la tabla sigue pintando las columnas en el mismo orden.

- [ ] **Step 4: Commit**

```bash
git add views/control-cambios/controlCambios.view.php
git commit -m "fix(control-cambios): cabeceras distinguibles para las dos columnas de detalle"
```

---

## Fase F2 · Estados vacíos (riesgo bajo)

Cubre **H-01, H-02, H-04, H-05**.

**Hallazgo técnico que condiciona toda la fase:** el estado vacío modelo de `/cnc` es la opción `sEmptyTable` de **DataTables** (`views/programacion-semanal/CNC.view.php:533`). Solo se puede «copiar el patrón» en superficies DataTables. `sEmptyTable` aparece **4 veces en todo el repositorio**: el patrón bueno nunca se propagó.

| Destino | Tecnología | Vía |
|---|---|---|
| `/control-cambios` (H-02) | DataTables | `sEmptyTable`, una línea |
| `/programacion-semanal` (H-01) | Handsontable | componente nuevo |
| `/pdc` (H-04) | Handsontable | componente nuevo |
| Tarjeta de BI (H-05) | PHP/JS | condicional en la vista |

### Task 4: Estado vacío de control de cambios (H-02)

**Files:**
- Modify: `views/control-cambios/controlCambios.view.php`

**Interfaces:**
- Consumes: nada.
- Produces: el texto que la Task 6 reutiliza como referencia de tono.

- [ ] **Step 1: Leer el patrón modelo**

```bash
docker compose exec app sed -n '525,540p' views/programacion-semanal/CNC.view.php
```

Fíjate en qué lo hace bueno: dice **qué** falta, **cómo** se crea y **dónde**. Las tres cosas.

- [ ] **Step 2: Añadir `sEmptyTable` a la inicialización de DataTables**

En el bloque `language` / `oLanguage` de la tabla de control de cambios:

```js
"sEmptyTable": "No hay solicitudes de cambio registradas. Se crean desde el formulario de nueva solicitud de cambio.",
```

Si la tabla no tiene bloque `language`, créalo dentro de las opciones de inicialización.

> **Dependencia con la Task 15 (H-38).** Ese texto nombra un formulario que **hoy no existe**: no hay forma visible de crear un cambio. Si la Task 15 aún no se ha hecho, usa esta redacción provisional y déjala anotada en el commit: `"No hay solicitudes de cambio registradas para este proyecto."` Vuelve a esta línea al cerrar F5.

- [ ] **Step 3: Comprobar en el navegador**

`/control-cambios` a 1180×820 dark: donde había 550 px de vacío absoluto debe leerse el mensaje.

- [ ] **Step 4: Commit**

```bash
git add views/control-cambios/controlCambios.view.php
git commit -m "fix(control-cambios): estado vacio en la tabla de solicitudes"
```

### Task 5: Componente compartido de estado vacío para Handsontable

**Files:**
- Create: `public/js/design-system/ht-empty-state.js`
- Create: `public/css/design-system/components/ht-empty-state.css`
- Test: `tests/browser/ht-empty-state.spec.mjs`

**Interfaces:**
- Consumes: una instancia de Handsontable ya construida.
- Produces: `attachHtEmptyState(hot, { titulo, cuerpo })` → `void`. Muestra un panel superpuesto cuando `hot.countRows() === 0` y lo oculta cuando hay filas. Lo consumen las Tasks 6 y 7.

**Contexto:** Handsontable no trae mensaje de vacío. Hoy la malla queda como un hueco oscuro. Se hace **una** pieza compartida en vez de tres superposiciones ad hoc.

- [ ] **Step 1: Escribir la prueba que falla**

```js
// tests/browser/ht-empty-state.spec.mjs
import { test, expect } from '@playwright/test';

test('la malla semanal vacia explica que falta y como se crea', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.emulateMedia({ colorScheme: 'dark' });
  await page.goto('http://localhost:8081/dev/entrar?u=test.R&p=' + encodeURIComponent('PDC Sandbox E2E'));
  await page.goto('http://localhost:8081/programacion-semanal');
  await page.waitForLoadState('networkidle');
  const panel = page.locator('.ht-empty-state:visible');
  await expect(panel).toHaveCount(1);
  await expect(panel).toContainText(/actividad/i);
});
```

- [ ] **Step 2: Ejecutar y confirmar que falla**

```bash
npx playwright test tests/browser/ht-empty-state.spec.mjs --workers=1
```

Esperado: FALLA con 0 elementos `.ht-empty-state`.

- [ ] **Step 3: Escribir el componente**

```js
// public/js/design-system/ht-empty-state.js
export function attachHtEmptyState(hot, { titulo, cuerpo }) {
  const host = hot.rootElement.parentElement;
  if (!host) return;

  let panel = host.querySelector(':scope > .ht-empty-state');
  if (!panel) {
    panel = document.createElement('div');
    panel.className = 'ht-empty-state';
    panel.setAttribute('role', 'status');
    panel.setAttribute('aria-live', 'polite');
    panel.innerHTML =
      '<h2 class="ht-empty-state__titulo"></h2><p class="ht-empty-state__cuerpo"></p>';
    host.appendChild(panel);
  }
  panel.querySelector('.ht-empty-state__titulo').textContent = titulo;
  panel.querySelector('.ht-empty-state__cuerpo').textContent = cuerpo;

  const sync = () => { panel.hidden = hot.countRows() > 0; };
  sync();
  hot.addHook('afterLoadData', sync);
  hot.addHook('afterChange', sync);
  hot.addHook('afterRemoveRow', sync);
  hot.addHook('afterCreateRow', sync);
}
```

> **Cuidado con `addHook`.** `/dashboard/escalamientos` lanza hoy `hot.addHook is not a function` (H-26, Task 11). Si esa tarea aún no se ha hecho, comprueba `typeof hot.addHook === 'function'` antes de registrar, y **no ocultes el fallo**: si no es función, deja el panel visible en su estado inicial y anota el motivo.

- [ ] **Step 4: Escribir el estilo, solo con tokens**

```css
/* public/css/design-system/components/ht-empty-state.css */
.ht-empty-state {
  position: absolute;
  inset: 0;
  display: grid;
  place-content: center;
  gap: var(--spacing-xs);
  padding: var(--spacing-lg);
  text-align: center;
  pointer-events: none;
}
.ht-empty-state[hidden] { display: none; }
.ht-empty-state__titulo {
  margin: 0;
  color: var(--ds-active-text-primary);
  font-size: 1rem;
  font-weight: 700;
}
.ht-empty-state__cuerpo {
  margin: 0;
  max-width: 46ch;
  color: var(--ds-active-text-secondary);
}
```

**Nada de hex ni de estilos en línea.** La escala activa llega hasta `secondary`: `--ds-active-text-tertiary` **no existe** y usarlo deja el texto en color heredado sin que ningún gate estático lo vea (ver `memoria/trampas/gate-estatico-no-ve-tokens-rotos.md`).

- [ ] **Step 5: Registrar el CSS en su entrypoint**

Añádelo al entrypoint que ya sirve los componentes del sistema de diseño. Localízalo con:

```bash
docker compose exec app grep -rn "components/" public/css/design-system/entrypoints/core.css | head
```

- [ ] **Step 6: Ejecutar la prueba y confirmar que pasa**

```bash
npx playwright test tests/browser/ht-empty-state.spec.mjs --workers=1
```

Esperado: PASA.

- [ ] **Step 7: Commit**

```bash
git add public/js/design-system/ht-empty-state.js public/css/design-system/components/ht-empty-state.css tests/browser/ht-empty-state.spec.mjs
git add -u
git commit -m "feat(design-system): estado vacio compartido para las mallas Handsontable"
```

### Task 6: Estado vacío de la malla semanal (H-01)

**Files:**
- Modify: la inicialización de Handsontable de `views/programacion-semanal/programacion_semanal.view.php` o su partial en `views/programacion-semanal/partials/`

**Interfaces:**
- Consumes: `attachHtEmptyState(hot, { titulo, cuerpo })` de la Task 5.
- Produces: nada.

> **TRAMPA MEDIDA — léela antes de abrir la ruta.** Abrir `/programacion-semanal` dispara un POST de guardado y una auto-programación **automáticos, sin interacción**. Ver `memoria/trampas/semanal-auto-dispara-mutaciones.md`. Consecuencias para esta tarea: abre la ruta **las veces mínimas**, no la uses para pruebas exploratorias, y **no interpretes las escrituras que veas en el log como efecto de tu cambio**.

- [ ] **Step 1: Localizar la construcción de la instancia**

```bash
docker compose exec app grep -rn "new Handsontable" views/programacion-semanal/ public/js/ | head
```

- [ ] **Step 2: Enganchar el componente**

Justo después de construir la instancia:

```js
attachHtEmptyState(hot, {
  titulo: 'Sin actividades programadas esta semana',
  cuerpo: 'Usa «Agregar Actividad» para programar una, o «Autoprogramar Actividades» para traerlas desde la programación intermedia.',
});
```

El cuerpo nombra dos botones que **existen** en esa barra. Si al verificar no los ves con esos nombres exactos, corrige el texto para que coincida con la interfaz, no al revés.

- [ ] **Step 3: Comprobar en el navegador**

Una sola visita a `/programacion-semanal` a 1180×820 dark. Los ~700 px de vacío deben mostrar el mensaje.

- [ ] **Step 4: Commit**

```bash
git add -u
git commit -m "fix(semanal): estado vacio en la malla cuando no hay actividades"
```

### Task 7: Estado vacío del PDC (H-04)

**Files:**
- Modify: `public/js/modules/pdc/hot.js`
- Modify: `views/pdc/pdc.view.php`

**Interfaces:**
- Consumes: `attachHtEmptyState(hot, { titulo, cuerpo })` de la Task 5.
- Produces: nada.

**Contexto:** hoy el mensaje «No hay paquetes de contratación para mostrar.» flota suelto **encima** de una malla vacía que se dibuja igual, cabeceras incluidas. Hay que unificar: un solo estado vacío, dentro de la malla.

- [ ] **Step 1: Retirar el mensaje suelto**

Localiza y elimina el párrafo actual en `views/pdc/pdc.view.php`:

```bash
docker compose exec app grep -n "No hay paquetes de contratación" views/pdc/pdc.view.php
```

- [ ] **Step 2: Enganchar el componente en la malla**

En `public/js/modules/pdc/hot.js`, tras construir la instancia:

```js
attachHtEmptyState(hot, {
  titulo: 'No hay paquetes de contratación',
  cuerpo: 'Los paquetes se arman desde el maestro de insumos, en la pestaña «Paquetes» del plan de compras.',
});
```

- [ ] **Step 3: Comprobar en el navegador**

`/pdc` a 1180×820 dark: un único mensaje, dentro de la malla, sin el párrafo suelto anterior.

- [ ] **Step 4: Commit**

```bash
git add -u
git commit -m "fix(pdc): un unico estado vacio, dentro de la malla"
```

### Task 8: La tarjeta «Resumen Ejecutivo» deja de mostrar «--» (H-05)

**Files:**
- Modify: la vista de la tarjeta en `views/bi/`

**Interfaces:**
- Consumes: nada.
- Produces: nada.

**Contexto:** la tarjeta muestra `--` en 150 px de alto. Un guion doble no distingue «vacío» de «error» de «cargando».

- [ ] **Step 1: Localizar el marcador**

```bash
docker compose exec app grep -rn '"--"\|>--<\|&mdash;&mdash;' views/bi/ | head
```

- [ ] **Step 2: Sustituir por un mensaje que diga cuál de los tres casos es**

Cuando no hay datos:

```
Sin datos para los filtros activos.
```

Sigue el tono que `/bi/programa-general` ya usa («No hay actividades que coincidan con los filtros para este proyecto»), que es el mejor del módulo. **Si la tarjeta puede estar además cargando o en error, distínguelos**; si solo puede estar vacía, un único mensaje basta.

- [ ] **Step 3: Comprobar en el navegador**

`/bi/control-tower` a 1180×820 dark.

- [ ] **Step 4: Commit**

```bash
git add -u
git commit -m "fix(bi): la tarjeta de resumen dice por que esta vacia"
```

---

## Fase F3 · Consistencia y accesibilidad (riesgo bajo, exige gates)

Cubre **H-25, H-26, H-33, H-34, H-35, H-36**. **Cada arreglo lleva su comprobación**: sin gate, vuelve a romperse sin que nadie lo vea.

### Task 9: Labels y `autocomplete` en el acceso de admin (H-33)

**Files:**
- Modify: `admin/views/pages/login.php`
- Test: `tests/browser/admin-login-a11y.spec.mjs` (crear)

**Interfaces:**
- Consumes: nada.
- Produces: nada.

**Contexto medido:** `/admin/login` tiene los campos `usuario` y `password` **sin `<label>`** (solo `placeholder`, que desaparece al escribir) y con **`autocomplete=""` vacío**, así que ningún gestor de contraseñas los rellena. `/login` de la app hace las dos cosas bien. Mismo trabajo, dos calidades.

- [ ] **Step 1: Leer cómo lo hace bien la app**

```bash
docker compose exec app grep -n "label\|autocomplete" views/auth/login.view.php | head -20
```

- [ ] **Step 2: Escribir la prueba que falla**

```js
// tests/browser/admin-login-a11y.spec.mjs
import { test, expect } from '@playwright/test';

test('el acceso de admin etiqueta sus campos y declara autocomplete', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.emulateMedia({ colorScheme: 'dark' });
  await page.goto('http://localhost:8081/admin/login');
  for (const [name, ac] of [['usuario', 'username'], ['password', 'current-password']]) {
    const campo = page.locator(`input[name="${name}"]`);
    await expect(campo).toHaveAttribute('autocomplete', ac);
    const id = await campo.getAttribute('id');
    expect(id, `el campo ${name} necesita id para que <label for> lo alcance`).toBeTruthy();
    await expect(page.locator(`label[for="${id}"]`)).toHaveCount(1);
  }
});
```

- [ ] **Step 3: Ejecutar y confirmar que falla**

```bash
npx playwright test tests/browser/admin-login-a11y.spec.mjs --workers=1
```

Esperado: FALLA en el primer `toHaveAttribute`.

- [ ] **Step 4: Añadir `id`, `<label>` y `autocomplete`**

```html
<label for="admin-usuario">Usuario</label>
<input type="text" id="admin-usuario" name="usuario" autocomplete="username" placeholder="Usuario" required>

<label for="admin-password">Contraseña</label>
<input type="password" id="admin-password" name="password" autocomplete="current-password" placeholder="Contraseña" required>
```

Conserva el `placeholder`: complementa al label, no lo sustituye. Si el diseño no tiene sitio para el label visible, usa la clase de solo-lectores que la app ya emplea (`aia-visually-hidden`) — **nunca** `aria-label` sin más, para no perder la asociación con el `for`.

- [ ] **Step 5: Ejecutar y confirmar que pasa**

```bash
npx playwright test tests/browser/admin-login-a11y.spec.mjs --workers=1
```

Esperado: PASA.

- [ ] **Step 6: Commit**

```bash
git add admin/views/pages/login.php tests/browser/admin-login-a11y.spec.mjs
git commit -m "fix(admin): etiquetas y autocomplete en el formulario de acceso"
```

### Task 10: Label del correo en la recuperación de admin (H-35)

**Files:**
- Modify: `admin/views/pages/password-forgot.php`
- Modify: `tests/browser/admin-login-a11y.spec.mjs`

**Interfaces:**
- Consumes: el spec de la Task 9.
- Produces: nada.

**Contexto:** el campo de correo no tiene `<label>`; el de la app sí. Además el botón es «Enviar enlace» y en la app «ENVIAR ENLACE».

- [ ] **Step 1: Añadir el caso al spec existente**

```js
test('la recuperacion de admin etiqueta el campo de correo', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.goto('http://localhost:8081/admin/password/forgot');
  const campo = page.locator('input[name="email"]');
  await expect(campo).toHaveAttribute('autocomplete', 'email');
  const id = await campo.getAttribute('id');
  expect(id).toBeTruthy();
  await expect(page.locator(`label[for="${id}"]`)).toHaveCount(1);
});
```

- [ ] **Step 2: Ejecutar y confirmar que falla**

```bash
npx playwright test tests/browser/admin-login-a11y.spec.mjs --workers=1
```

- [ ] **Step 3: Añadir `id` y `<label>`**

```html
<label for="admin-email">Correo electrónico</label>
<input type="email" id="admin-email" name="email" autocomplete="email" placeholder="nombre@empresa.com" required>
```

Aprovecha para alinear el `placeholder` con el de la app (`nombre@empresa.com`), que da un ejemplo útil en vez de repetir la etiqueta.

- [ ] **Step 4: Ejecutar y confirmar que pasa**

```bash
npx playwright test tests/browser/admin-login-a11y.spec.mjs --workers=1
```

- [ ] **Step 5: Decidir la capitalización del botón**

La app usa «ENVIAR ENLACE» y admin «Enviar enlace». **Unifica hacia la de la app** para que las dos pantallas se lean igual. Si al mirarlo resulta que la mayúscula la aplica el CSS (`text-transform`) y no el HTML, no toques el texto: iguala el CSS.

- [ ] **Step 6: Commit**

```bash
git add -u
git commit -m "fix(admin): etiqueta y placeholder del correo en recuperacion de clave"
```

### Task 11: El error de JS de escalamientos deja de fallar en silencio (H-26)

**Files:**
- Modify: el módulo que llama a `hot.addHook` en la ruta de escalamientos
- Test: `tests/browser/escalamientos-sin-errores.spec.mjs` (crear)

**Interfaces:**
- Consumes: nada.
- Produces: garantiza que `hot.addHook` existe, del que depende la Task 5.

**Contexto medido:** `/dashboard/escalamientos` lanza `hot.addHook is not a function` al cargar. La página parece correcta, así que nadie lo nota.

- [ ] **Step 1: Escribir la prueba que falla**

```js
// tests/browser/escalamientos-sin-errores.spec.mjs
import { test, expect } from '@playwright/test';

test('escalamientos carga sin errores de JS', async ({ page }) => {
  const errores = [];
  page.on('pageerror', (e) => errores.push(e.message));
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.emulateMedia({ colorScheme: 'dark' });
  await page.goto('http://localhost:8081/dev/entrar?u=test.R&p=' + encodeURIComponent('PDC Sandbox E2E'));
  await page.goto('http://localhost:8081/dashboard/escalamientos');
  await page.waitForLoadState('networkidle');
  expect(errores).toEqual([]);
});
```

- [ ] **Step 2: Ejecutar y confirmar que falla**

```bash
npx playwright test tests/browser/escalamientos-sin-errores.spec.mjs --workers=1
```

Esperado: FALLA con `hot.addHook is not a function`.

- [ ] **Step 3: Diagnosticar antes de tocar nada**

**REQUIRED SUB-SKILL: `superpowers:systematic-debugging`.** No adivines. `addHook` no existe cuando `hot` no es una instancia de Handsontable: puede ser `null`, un objeto vacío, o la instancia aún no construida. Averigua **cuál de los tres** antes de escribir el arreglo.

- [ ] **Step 4: Arreglar la causa, no el síntoma**

Un `if (hot && typeof hot.addHook === 'function')` que silencie el error **no vale**: dejaría el drawer sin sus hooks y el fallo pasaría a ser invisible del todo. Arregla el orden de construcción o la referencia rota que hayas encontrado.

- [ ] **Step 5: Ejecutar y confirmar que pasa**

```bash
npx playwright test tests/browser/escalamientos-sin-errores.spec.mjs --workers=1
```

- [ ] **Step 6: Commit**

```bash
git add tests/browser/escalamientos-sin-errores.spec.mjs
git add -u
git commit -m "fix(escalamientos): corrige la referencia rota que rompia los hooks del drawer"
```

### Task 12: Contraste del chip de BI (H-34)

**Files:**
- Modify: `public/css/bi-control-tower.css:117`
- Modify: `public/css/tokens.css:34` (comentario)
- Modify: `docs/design-system/state-token-exceptions.json`
- Test: `tests/browser/bi-chip-contrast.spec.mjs` (crear)

**Interfaces:**
- Consumes: `tests/browser/support/contrast.mjs`.
- Produces: nada.

**Contexto y decisión ya tomada.** El chip pinta `--ds-color-brand-aqua` sobre `--ds-color-state-info-bg` a 0,75rem/700: **3,01:1 frente al 4,5:1 exigido**. `state-token-exceptions.json` lo tenía fichado como `at-risk` con la salida sin decidir.

**El usuario decidió el 2026-08-03: usar `--aia-aqua-medium` (#5ec9bd), que da 5,2:1.** Sigue siendo aqua de marca —mismo tono 189, solo más claro— y **no es un color nuevo en el módulo**: el propio chip ya lo usa en su borde.

Medidas verificadas sobre el fondo `#134841`:

| Color | Contraste | ¿Cumple 4,5:1? |
|---|---|---|
| `#00a499` (hex del comentario de `tokens.css:34`) | 3,33:1 | no |
| `oklch(61% 0.13 189)` = `#009b93` (**valor real pintado**) | **3,01:1** | no |
| `#5ec9bd` = `--aia-aqua-medium` (**elegido**) | **5,20:1** | sí |
| `#bbdcfb` = `--ds-color-state-info-text` (descartado) | 7,26:1 | sí, pero pierde el acento de marca |

- [ ] **Step 1: Escribir el gate que falla**

```js
// tests/browser/bi-chip-contrast.spec.mjs
import { test, expect } from '@playwright/test';
import { VIEWPORT, installContrastProbe, measure } from './support/contrast.mjs';

test('el chip de filtro activo de BI cumple AA', async ({ page }) => {
  await page.setViewportSize(VIEWPORT);
  await page.emulateMedia({ colorScheme: 'dark' });
  await installContrastProbe(page);
  await page.goto('http://localhost:8081/dev/entrar?u=test.R&p=' + encodeURIComponent('PDC Sandbox E2E'));
  await page.goto('http://localhost:8081/bi/control-tower');
  await page.waitForLoadState('networkidle');
  const { ratio, fg, bg } = await measure(page, '.bi-chip strong');
  expect(ratio, `texto de 12px y peso 900 no califica como texto grande: exige 4,5:1 (fg ${fg} sobre bg ${bg})`)
    .toBeGreaterThanOrEqual(4.5);
});
```

> **API real del ayudante compartido, verificada:** `installContrastProbe(page)` instala la sonda y
> `measure(page, selector)` devuelve `{ ratio, fg, bg }`. `VIEWPORT` ya vale `1180×820`. **No
> escribas una sonda propia**: la compartida rasteriza por canvas y compone alfa sobre los
> ancestros, y ya hubo dos sesiones que perdieron el tiempo reimplementándola.

- [ ] **Step 2: Ejecutar y confirmar que falla**

```bash
npx playwright test tests/browser/bi-chip-contrast.spec.mjs --workers=1
```

Esperado: FALLA con ≈3,01.

- [ ] **Step 3: Cambiar el color del texto del chip**

En `public/css/bi-control-tower.css`, dentro de `.bi-chip`:

```css
    color: var(--aia-aqua-medium);
```

- [ ] **Step 4: Ejecutar y confirmar que pasa**

```bash
npx playwright test tests/browser/bi-chip-contrast.spec.mjs --workers=1
```

Esperado: PASA con ≈5,20.

- [ ] **Step 5: Corregir el comentario desfasado del token**

`public/css/tokens.css:34` dice `/* #00a499 */` pero `oklch(61% 0.13 189)` rasteriza a `#009b93`. Ese comentario es el origen del 3,33:1 publicado en el fichero de excepciones. Corrígelo:

```css
    --aia-aqua-primary: oklch(61% 0.13 189); /* #009b93 */
```

- [ ] **Step 6: Retirar la excepción resuelta**

En `docs/design-system/state-token-exceptions.json`, elimina la entrada de `.bi-chip` en `public/css/bi-control-tower.css` línea 115. **Ya no es una excepción: está resuelta.** Si el fichero lleva `version`, súbela.

- [ ] **Step 7: Confirmar que el guard de emparejamiento sigue conforme**

```bash
npx vitest run tests/design-system/state-token-pairing.test.mjs 2>/dev/null || node --test tests/design-system/state-token-pairing.test.mjs
```

Si ese guard aún no está en `main` (llega de la sesión de tintes), anótalo y sigue.

- [ ] **Step 8: Commit**

```bash
git add public/css/bi-control-tower.css public/css/tokens.css docs/design-system/state-token-exceptions.json tests/browser/bi-chip-contrast.spec.mjs
git commit -m "fix(bi): el chip de filtro cumple AA con aqua-medium y deja de ser excepcion"
```

### Task 13: Objetivo de «Quitar filtro» y contador de filtros (H-36, H-25)

**Files:**
- Modify: `public/css/bi-control-tower.css:128-131`
- Modify: la vista o el JS que compone el contador de `.bi-filter-trigger`
- Test: `tests/browser/bi-filtros.spec.mjs` (crear)

**Interfaces:**
- Consumes: nada.
- Produces: nada.

**Contexto medido:** el botón de quitar filtro mide **20×20 px** (`min-width`/`min-height: 1.25rem`) y es la única forma de deshacer un filtro. Y el disparador dice **«Filtros 0»** mientras justo debajo se muestra el chip activo «Proyectos: PDC Sandbox E2E»: el contador y la realidad se contradicen en pantalla.

- [ ] **Step 1: Escribir las dos pruebas que fallan**

```js
// tests/browser/bi-filtros.spec.mjs
import { test, expect } from '@playwright/test';

const entrar = async (page) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.emulateMedia({ colorScheme: 'dark' });
  await page.goto('http://localhost:8081/dev/entrar?u=test.R&p=' + encodeURIComponent('PDC Sandbox E2E'));
  await page.goto('http://localhost:8081/bi/control-tower');
  await page.waitForLoadState('networkidle');
};

test('el boton de quitar filtro mide al menos 24x24', async ({ page }) => {
  await entrar(page);
  const caja = await page.locator('.bi-chip button').first().boundingBox();
  expect(caja.width).toBeGreaterThanOrEqual(24);
  expect(caja.height).toBeGreaterThanOrEqual(24);
});

test('el contador de filtros coincide con los chips visibles', async ({ page }) => {
  await entrar(page);
  const chips = await page.locator('.bi-chip').count();
  const texto = await page.locator('.bi-filter-trigger').innerText();
  expect(texto).toContain(String(chips));
});
```

- [ ] **Step 2: Ejecutar y confirmar que fallan las dos**

```bash
npx playwright test tests/browser/bi-filtros.spec.mjs --workers=1
```

Esperado: la primera falla con 20; la segunda falla porque dice 0 y hay 1 chip.

- [ ] **Step 3: Agrandar el objetivo**

```css
  .bi-chip button {
    min-width: 1.5rem;
    min-height: 1.5rem;
```

Comprueba que el chip no crece de alto: si lo hace, sube también su `min-height` para que no se desalinee.

- [ ] **Step 4: Hacer que el contador cuente el filtro de proyecto**

El contador excluye hoy el filtro de proyecto. Inclúyelo. **No borres el chip para cuadrar el número**: el chip es correcto y el contador es el que miente.

- [ ] **Step 5: Ejecutar y confirmar que pasan**

```bash
npx playwright test tests/browser/bi-filtros.spec.mjs --workers=1
```

- [ ] **Step 6: Commit**

```bash
git add tests/browser/bi-filtros.spec.mjs
git add -u
git commit -m "fix(bi): el contador de filtros dice la verdad y el boton de quitar es alcanzable"
```

### Task 14: Verificación de fase F3

- [ ] **Step 1: Correr todo lo que F3 añadió**

```bash
npx playwright test tests/browser/admin-login-a11y.spec.mjs tests/browser/escalamientos-sin-errores.spec.mjs tests/browser/bi-chip-contrast.spec.mjs tests/browser/bi-filtros.spec.mjs --workers=1
```

Esperado: todo en verde. **Si algo falla, arréglalo; no lo declares «preexistente» sin comprobarlo contra `git stash`.**

- [ ] **Step 2: Recorrido visual de las rutas tocadas**

A 1180×820 dark: `/admin/login`, `/admin/password/forgot`, `/dashboard/escalamientos`, `/bi/control-tower`. Revisa además la consola en cada una.

- [ ] **Step 3: Confirmar que no se rompió el resto**

```bash
npm run test:design-system:static
```

Esperado: los **tres rojos conocidos** del apartado «Estado del repositorio», ni uno más. Si aparece un cuarto, es tuyo.

---

## Fase F4 · Etiquetas y solapes (riesgo medio)

Cubre **H-07, H-08, H-09, H-10, H-12, H-13**. Todo es CSS de layout y **exige comprobación visual**: son los cambios con más riesgo de romper algo adyacente.

### Task 15: Cabeceras del PDC legibles y distinguibles (H-07)

**Files:**
- Modify: `public/js/modules/pdc/hot.js`
- Test: `tests/browser/pdc-cabeceras.spec.mjs` (crear)

**Interfaces:**
- Consumes: nada.
- Produces: nada.

**Contexto medido, y es el peor de la fase:** nueve cabeceras se truncan sin elipsis ni tooltip, y **tres columnas contiguas quedan como «INICIO EN O…», indistinguibles entre sí**. «INICIO DEL PROCESO DE CONTRATACIÓN» dispone de 75 px de los 262 que necesita (29 %).

- [ ] **Step 1: Escribir la prueba que falla**

```js
// tests/browser/pdc-cabeceras.spec.mjs
import { test, expect } from '@playwright/test';

test('ninguna cabecera del PDC queda ambigua por truncado', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.emulateMedia({ colorScheme: 'dark' });
  await page.goto('http://localhost:8081/dev/entrar?u=test.R&p=' + encodeURIComponent('PDC Sandbox E2E'));
  await page.goto('http://localhost:8081/pdc');
  await page.waitForLoadState('networkidle');
  const visibles = await page.locator('.ht_clone_top th, .handsontable thead th').allInnerTexts();
  const utiles = visibles.map((t) => t.trim()).filter(Boolean);
  const repetidas = utiles.filter((t, i) => utiles.indexOf(t) !== i);
  expect(repetidas, `cabeceras indistinguibles: ${repetidas.join(', ')}`).toEqual([]);
});
```

- [ ] **Step 2: Ejecutar y confirmar que falla**

```bash
npx playwright test tests/browser/pdc-cabeceras.spec.mjs --workers=1
```

Esperado: FALLA nombrando las cabeceras repetidas.

- [ ] **Step 3: Acortar los nombres, no solo ensanchar**

Ensanchar nueve columnas no cabe en 1180 px. **Acorta los nombres a algo que quepa y siga siendo unívoco**, y pon el nombre completo en `title` para quien pase el ratón. Ejemplo de los tres conflictivos:

| Antes | Después |
|---|---|
| `INICIO DEL PROCESO DE CONTRATACIÓN` | `INICIO PROCESO` |
| `INICIO EN OBRA SEGÚN CRONOGRAMA` | `INICIO OBRA (CRONO.)` |
| la tercera `INICIO EN O…` — **identifícala leyendo el código** | nombre unívoco propio |

**Comprueba contra `GLOSARIO.md`** que ningún nombre nuevo choque con un término del dominio con otro significado.

- [ ] **Step 4: Añadir elipsis al truncado inevitable**

```css
overflow: hidden;
text-overflow: ellipsis;
white-space: nowrap;
```

Una cabecera cortada debe **verse** cortada: sin elipsis, «INICIO EN O» parece un nombre completo.

- [ ] **Step 5: Ejecutar y confirmar que pasa**

```bash
npx playwright test tests/browser/pdc-cabeceras.spec.mjs --workers=1
```

- [ ] **Step 6: Comprobar en el navegador**

`/pdc` a 1180×820 dark: sin desbordamiento horizontal y con las tres columnas distinguibles.

- [ ] **Step 7: Commit**

```bash
git add tests/browser/pdc-cabeceras.spec.mjs
git add -u
git commit -m "fix(pdc): cabeceras univocas y truncado visible"
```

### Task 16: Filtros legibles en control de cambios (H-08)

**Files:**
- Modify: `views/control-cambios/controlCambios.view.php`

**Interfaces:**
- Consumes: nada.
- Produces: nada.

**Contexto medido:** los cuatro filtros son más estrechos que su propio valor y se leen «Tod⌄», «Toda⌄», «Bu». Un filtro que no muestra por qué está filtrando no es un filtro. Además la fila de filtros **no se alinea con las columnas** que filtra.

- [ ] **Step 1: Medir el ancho que necesita cada control**

```bash
docker compose exec app grep -n "select\|input" views/control-cambios/controlCambios.view.php | head -20
```

Abre la ruta y mide con `scrollWidth` vs `clientWidth` sobre cada control, igual que se midió el hallazgo.

- [ ] **Step 2: Dar a cada filtro el ancho de su contenido**

Usa `min-width` en `ch` según la opción más larga de cada `select`. **No uses anchos fijos en píxeles**: la opción más larga cambia con los datos.

- [ ] **Step 3: Alinear la fila de filtros con sus columnas**

Cada filtro debe caer bajo la columna que filtra. Las columnas sin filtro llevan celda vacía, no se saltan: saltarlas es lo que descoloca la fila entera hoy.

- [ ] **Step 4: Comprobar en el navegador**

`/control-cambios` a 1180×820 dark: los cuatro valores legibles enteros, cada uno bajo su columna, y sin barra horizontal.

- [ ] **Step 5: Commit**

```bash
git add views/control-cambios/controlCambios.view.php
git commit -m "fix(control-cambios): filtros legibles y alineados con sus columnas"
```

### Task 17: Botones de la barra semanal sin truncar (H-09)

**Files:**
- Modify: el CSS de la barra de acciones de programación semanal

**Interfaces:**
- Consumes: nada.
- Produces: nada.

**Contexto medido:** «Leyenda» se pinta en 40 px de los 67 que necesita («Leyend») y «Recargar» en 43 de 71 («Recarga»). En `/cnc` el mismo botón «Leyenda» se ve entero: superficies hermanas, resultados distintos.

> **TRAMPA:** abrir `/programacion-semanal` dispara escrituras automáticas. Minimiza las visitas.

- [ ] **Step 1: Averiguar por qué en `/cnc` sí cabe**

Compara las reglas que aplican al botón en las dos rutas. La diferencia es la causa; arréglala ahí en vez de forzar un ancho en la semanal.

- [ ] **Step 2: Quitar la restricción que corta el texto**

Lo más probable es un `width` o `flex-basis` fijo en una barra que ya no cabe. Deja que el botón tome el ancho de su contenido y que la barra envuelva si hace falta.

- [ ] **Step 3: Comprobar en el navegador**

Una visita a `/programacion-semanal` a 1180×820 dark. Los siete botones legibles enteros, sin desbordamiento horizontal.

- [ ] **Step 4: Commit**

```bash
git add -u
git commit -m "fix(semanal): los botones de la barra dejan de cortarse"
```

### Task 18: Cabeceras del programa general (H-10)

**Files:**
- Modify: el CSS o la configuración de columnas de programa general

**Interfaces:**
- Consumes: la técnica de elipsis de la Task 15.
- Produces: nada.

**Contexto medido:** siete cabeceras cortadas («Sem. Inicio», «Crítica», «Cant. PPTO», «Ej. Teórico»).

- [ ] **Step 1: Aplicar el mismo criterio de la Task 15**

Nombres unívocos que quepan, `title` con el nombre completo, y elipsis para que el corte se vea. **Aquí no hay ambigüedad como en el PDC** —ninguna pareja queda idéntica—, así que basta con la elipsis y el `title` si acortar empeora la claridad.

- [ ] **Step 2: Comprobar en el navegador**

`/programa-general` a 1180×820 dark.

- [ ] **Step 3: Commit**

```bash
git add -u
git commit -m "fix(programa-general): truncado visible en las cabeceras"
```

### Task 19: El rail «CONCURRENCIA LPS» deja de tapar controles (H-12, H-13)

**Files:**
- Modify: el CSS del rail de concurrencia

**Interfaces:**
- Consumes: nada.
- Produces: nada.

**Contexto medido, con geometría real:**

- En `/programacion-semanal` el rail **cubre el botón «Ver Secciones» en 44×45 px**, es decir el botón entero.
- En `/programacion-intermedia` solapa **cabeceras de columna en 42×144 px**, incluida «Estado Operativo».

**Un solo arreglo de posicionamiento resuelve las dos.**

- [ ] **Step 1: Escribir la prueba que falla**

```js
// tests/browser/rail-concurrencia-no-tapa.spec.mjs
import { test, expect } from '@playwright/test';

test('el rail de concurrencia no solapa controles en intermedia', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.emulateMedia({ colorScheme: 'dark' });
  await page.goto('http://localhost:8081/dev/entrar?u=test.R&p=' + encodeURIComponent('PDC Sandbox E2E'));
  await page.goto('http://localhost:8081/programacion-intermedia');
  await page.waitForLoadState('networkidle');
  const solapes = await page.evaluate(() => {
    const rail = document.querySelector('[class*="concurrencia"], [class*="lps-rail"]');
    if (!rail) return ['rail no encontrado: ajusta el selector'];
    const R = rail.getBoundingClientRect();
    return [...document.querySelectorAll('button,a[href],th')]
      .filter((e) => { const B = e.getBoundingClientRect(); return B.width && B.height; })
      .filter((e) => {
        const B = e.getBoundingClientRect();
        return Math.min(R.right, B.right) - Math.max(R.left, B.left) > 2
            && Math.min(R.bottom, B.bottom) - Math.max(R.top, B.top) > 2;
      })
      .map((e) => (e.textContent || '').trim().slice(0, 30));
  });
  expect(solapes, `el rail tapa: ${solapes.join(' | ')}`).toEqual([]);
});
```

Ajusta el selector del rail leyendo el DOM real antes de correrlo.

- [ ] **Step 2: Ejecutar y confirmar que falla**

```bash
npx playwright test tests/browser/rail-concurrencia-no-tapa.spec.mjs --workers=1
```

- [ ] **Step 3: Reservarle sitio al rail en vez de superponerlo**

El rail está superpuesto sobre el contenido. Que el contenedor le reserve su ancho (columna de rejilla o `padding-inline-end` del área de contenido) para que nada quede debajo. **No lo arregles subiendo el `z-index` del botón**: seguiría tapando las cabeceras.

- [ ] **Step 4: Ejecutar y confirmar que pasa**

```bash
npx playwright test tests/browser/rail-concurrencia-no-tapa.spec.mjs --workers=1
```

- [ ] **Step 5: Comprobar las dos rutas**

`/programacion-intermedia` y **una** visita a `/programacion-semanal`, a 1180×820 dark. En la semanal, «Ver Secciones» debe ser visible y pulsable.

- [ ] **Step 6: Commit**

```bash
git add tests/browser/rail-concurrencia-no-tapa.spec.mjs
git add -u
git commit -m "fix(lps): el rail de concurrencia reserva sitio en vez de tapar controles"
```

### Task 20: Verificación de fase F4

- [ ] **Step 1: Correr lo que F4 añadió**

```bash
npx playwright test tests/browser/pdc-cabeceras.spec.mjs tests/browser/rail-concurrencia-no-tapa.spec.mjs --workers=1
```

- [ ] **Step 2: Comprobar que ninguna ruta desborda en horizontal**

En cada una de `/pdc`, `/control-cambios`, `/programa-general`, `/programacion-intermedia` y `/programacion-semanal`, a 1180×820:

```js
document.documentElement.scrollWidth <= document.documentElement.clientWidth
```

Esperado: `true` en las cinco.

- [ ] **Step 3: Comprobar que no se movieron las baselines visuales**

```bash
npm run test:design-system:runtime
```

**No regeneres ninguna baseline.** Si una cambió, es que F4 alteró algo que no debía: investígalo. Ten en cuenta que las baselines del laboratorio ya estaban rojas de antes (`memoria/trampas/visual-baselines-estado-real.md`): compara contra ese estado, no contra verde.

---

## Fase F5 · Navegación y jerarquía (riesgo más alto)

Cubre **H-16, H-17, H-18, H-19, H-20, H-21**, más el H-38 que arrastra la Task 4.

### Task 21: Las pestañas de BI dejan de ocultar módulos (H-16)

**Files:**
- Modify: `public/css/bi-control-tower.css`
- Test: `tests/browser/bi-tabs-alcanzables.spec.mjs` (crear)

**Interfaces:**
- Consumes: nada.
- Produces: nada.

**Contexto medido:** `.bi-tabs-nav` tiene **1626 px de contenido en 1116 visibles**, con `overflow-x: auto` pero **sin flecha, sin degradado y sin barra visible**. ~3 de 8 módulos de BI son inalcanzables salvo que el usuario adivine que puede desplazar. Es el hallazgo de mayor impacto de la fase: es funcionalidad terminada que nadie encuentra.

- [ ] **Step 1: Escribir la prueba que falla**

```js
// tests/browser/bi-tabs-alcanzables.spec.mjs
import { test, expect } from '@playwright/test';

test('todas las pestanas de BI son visibles sin desplazamiento oculto', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.emulateMedia({ colorScheme: 'dark' });
  await page.goto('http://localhost:8081/dev/entrar?u=test.R&p=' + encodeURIComponent('PDC Sandbox E2E'));
  await page.goto('http://localhost:8081/bi/control-tower');
  await page.waitForLoadState('networkidle');
  const r = await page.evaluate(() => {
    const nav = document.querySelector('.bi-tabs-nav');
    return { sw: nav.scrollWidth, cw: nav.clientWidth };
  });
  expect(r.sw, `se ocultan ${r.sw - r.cw}px de pestanas`).toBeLessThanOrEqual(r.cw + 1);
});
```

- [ ] **Step 2: Ejecutar y confirmar que falla**

```bash
npx playwright test tests/browser/bi-tabs-alcanzables.spec.mjs --workers=1
```

Esperado: FALLA indicando ~510 px ocultos.

- [ ] **Step 3: Hacer que las ocho quepan**

Preferencia, en orden: **(a)** envolver a dos filas (`flex-wrap: wrap`), que las hace todas visibles sin gesto; **(b)** reducir el `padding` y el tamaño de las pestañas hasta que quepan en una fila. **No dejes el desplazamiento horizontal como solución**, aunque le añadas una flecha: en desktop una barra de pestañas que se desplaza sigue escondiendo destinos.

- [ ] **Step 4: Ejecutar y confirmar que pasa**

```bash
npx playwright test tests/browser/bi-tabs-alcanzables.spec.mjs --workers=1
```

- [ ] **Step 5: Comprobar que la pestaña activa sigue marcada**

Recorre las 8 rutas de `/bi/*` a 1180×820 dark y confirma en cada una que su pestaña se distingue de las demás.

- [ ] **Step 6: Commit**

```bash
git add tests/browser/bi-tabs-alcanzables.spec.mjs public/css/bi-control-tower.css
git commit -m "fix(bi): las ocho pestanas caben y ningun modulo queda oculto"
```

### Task 22: El tour de plan de compras deja de taparse a sí mismo (H-17, H-20)

**Files:**
- Modify: el CSS y el componente del coach-mark en `pdc-app/` o `public/pdc-app/`

**Interfaces:**
- Consumes: nada.
- Produces: nada.

**Contexto:** el coach-mark **tapa justo lo que explica**: cubre el `h1` «Importar presupuesto», el breadcrumb, dos pestañas y la zona de carga, cuyo texto queda cortado a media frase. Y la jerarquía está invertida: **«Omitir» va resaltado y «Siguiente» apagado**, así que la acción de abandono pesa más que la de continuar.

> El texto del tour **es bueno** y no se toca. Esto es posicionamiento y jerarquía, no redacción.

- [ ] **Step 1: Recolocar el panel**

Debe quedar **junto** al elemento que explica, sin taparlo. Si el paso 1 habla de la zona de carga, el panel va al lado o debajo, con una flecha que apunte. Añade un velo que atenúe el resto para que se entienda a qué se refiere.

- [ ] **Step 2: Corregir la jerarquía de los botones**

«Siguiente» toma el estilo primario y «Omitir» el de enlace o secundario discreto. Es el intercambio exacto de lo que hay hoy.

- [ ] **Step 3: Comprobar los seis pasos**

`/plan-compras` a 1180×820 dark. En **cada uno de los seis pasos**, confirma que el panel no tapa su propio objetivo y que «Siguiente» destaca sobre «Omitir».

- [ ] **Step 4: Comprobar que se puede recuperar el tour**

Si al omitir no hay forma de volver a lanzarlo, añade el acceso. Un tour que solo se ve una vez y por accidente no cumple su función.

- [ ] **Step 5: Commit**

```bash
git add -u
git commit -m "fix(plan-compras): el tour no tapa lo que explica y prioriza avanzar"
```

### Task 23: Encabezado de página en las seis superficies que no lo tienen (H-19)

**Files:**
- Modify: `views/indicadores/indicadores.view.php`
- Modify: `views/control-cambios/controlCambios.view.php`
- Modify: `views/programacion-semanal/CIC.view.php`
- Modify: `views/programacion-semanal/CNC.view.php`
- Modify: `views/programacion-semanal/CNP.view.php`
- Modify: `views/pdc/pdc.view.php`
- Test: `tests/browser/superficies-con-h1.spec.mjs` (crear)

**Interfaces:**
- Consumes: nada.
- Produces: nada.

**Contexto medido:** seis superficies **no tienen ningún encabezado** (`h1`–`h4`). El único título es el breadcrumb. Para un lector de pantalla la página no tiene nombre.

- [ ] **Step 1: Escribir la prueba que falla**

```js
// tests/browser/superficies-con-h1.spec.mjs
import { test, expect } from '@playwright/test';

const RUTAS = ['/indicadores', '/control-cambios', '/programacion-semanal/cic',
               '/programacion-semanal/cnc', '/programacion-semanal/cnp', '/pdc'];

test('cada superficie declara su nombre en un h1', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.emulateMedia({ colorScheme: 'dark' });
  await page.goto('http://localhost:8081/dev/entrar?u=test.R&p=' + encodeURIComponent('PDC Sandbox E2E'));
  for (const ruta of RUTAS) {
    await page.goto('http://localhost:8081' + ruta);
    await page.waitForLoadState('networkidle');
    await expect(page.locator('h1'), `${ruta} no tiene h1`).toHaveCount(1);
  }
});
```

- [ ] **Step 2: Ejecutar y confirmar que falla en las seis**

```bash
npx playwright test tests/browser/superficies-con-h1.spec.mjs --workers=1
```

- [ ] **Step 3: Añadir el `h1` a cada una**

| Ruta | `h1` |
|---|---|
| `/indicadores` | `Indicadores LPS` |
| `/control-cambios` | `Control de Cambios` |
| `/programacion-semanal/cic` | `Cumplimiento por Subcontratista (CIC)` |
| `/programacion-semanal/cnc` | `Causas de No Cumplimiento (CNC)` |
| `/programacion-semanal/cnp` | `Causas de No Programación (CNP)` |
| `/pdc` | `Plan de Compras` |

**Las siglas se expanden y se conservan.** CIC, CNC y CNP son vocabulario correcto de `GLOSARIO.md`; el problema no era la sigla sino usarla sola como único título. Contrasta cada expansión con `GLOSARIO.md` antes de escribirla.

Si el diseño no admite un título visible, usa `aia-visually-hidden` — pero **prefiere el visible**: la página gana orientación, no solo accesibilidad.

- [ ] **Step 4: Ejecutar y confirmar que pasa**

```bash
npx playwright test tests/browser/superficies-con-h1.spec.mjs --workers=1
```

- [ ] **Step 5: Commit**

```bash
git add tests/browser/superficies-con-h1.spec.mjs
git add -u
git commit -m "fix(a11y): las seis superficies sin encabezado declaran su nombre"
```

### Task 24: Los dos proyectos dejan de ser indistinguibles (H-21)

**Files:**
- Modify: `views/core/project_selector.view.php`

**Interfaces:**
- Consumes: nada.
- Produces: nada.

**Contexto medido:** los dos botones tienen el **nombre accesible idéntico** («Ingresar al proyecto») y el título de la tarjeta no es enlace. Con lector de pantalla los proyectos son indistinguibles.

- [ ] **Step 1: Nombrar cada botón por su proyecto**

```php
<button type="submit" class="..." aria-label="Ingresar al proyecto <?php echo htmlspecialchars($proyecto['Proyecto_Proceso'], ENT_QUOTES, 'UTF-8'); ?>">
  Ingresar al proyecto
</button>
```

El texto visible no cambia: solo el nombre accesible. La tarjeta ya muestra el nombre del proyecto para quien ve.

- [ ] **Step 2: Comprobar**

```bash
npx playwright test --workers=1 -g "proyecto"
```

Y en el navegador, confirmar con el árbol de accesibilidad que los dos botones tienen nombres distintos.

- [ ] **Step 3: Commit**

```bash
git add views/core/project_selector.view.php
git commit -m "fix(proyectos): cada boton de ingreso nombra su proyecto"
```

### Task 25: Devolver el shell a escalamientos (H-18) — CON FRENO

**Files:**
- Modify: `views/dashboard/escalamientos.php`

**Interfaces:**
- Consumes: el layout compartido que usan las demás superficies internas.
- Produces: nada.

**Contexto medido:** `/dashboard/escalamientos` **rompe el shell entero**: sin sidebar, sin breadcrumb, sin nombre de proyecto. Único escape: «Volver a Planificación». Falla el Trunk Test en las seis preguntas y es la única superficie interna que lo hace.

> **FRENO ACORDADO.** Si envolverla en el layout existente resulta ser más que eso —si hay que reescribir su CSS, si el layout choca con su rejilla de cuatro tarjetas, o si aparece cualquier cosa que no sea envolver— **para, no lo fuerces, y sácalo a goal propio**. Esta tarea vale hasta ahí y no más.

- [ ] **Step 1: Ver cómo envuelven las demás**

```bash
docker compose exec app grep -rn "layout\|header.php\|sidebar" views/control-cambios/controlCambios.view.php | head
```

- [ ] **Step 2: Envolver la vista en el mismo layout**

Sin tocar el contenido de las cuatro tarjetas.

- [ ] **Step 3: Evaluar el freno — punto de decisión**

Si en este punto has tenido que tocar el CSS de las tarjetas o pelearte con la rejilla: **revierte, anota lo aprendido y crea el goal propio.** Dilo claramente en el informe. No es un fracaso: es el freno funcionando.

- [ ] **Step 4: Comprobar en el navegador**

`/dashboard/escalamientos` a 1180×820 dark: sidebar presente, breadcrumb con el nombre del proyecto, y las cuatro tarjetas intactas. Consola sin errores (la Task 11 ya arregló el que había).

- [ ] **Step 5: Commit**

```bash
git add -u
git commit -m "fix(escalamientos): la superficie recupera el shell de la aplicacion"
```

### Task 26: Dar salida a control de cambios (H-38)

**Files:**
- Modify: `views/control-cambios/controlCambios.view.php`

**Interfaces:**
- Consumes: la Task 4 (estado vacío), cuyo texto hay que cerrar aquí.
- Produces: nada.

**Contexto:** **no hay ninguna forma visible de crear un cambio.** La pantalla solo permite filtrar y leer una tabla vacía. Sumado a H-02 y H-08, era la superficie en peor estado de las 26.

- [ ] **Step 1: Averiguar si el alta existe en el backend**

```bash
docker compose exec app grep -rn "control-cambios\|controlCambios" public/index.php
```

**Punto de decisión:** si existe una ruta POST de alta sin interfaz, esta tarea es añadir el botón que la invoca. **Si no existe alta ninguna**, crear el flujo completo es funcionalidad nueva: **para y sácalo a goal propio con su propio grilleo.** No lo improvises dentro de este plan.

- [ ] **Step 2: Si existe — añadir la acción primaria**

Un botón «Nueva solicitud de cambio» en la barra de la pantalla, con el estilo primario del sistema de diseño.

- [ ] **Step 3: Cerrar el texto provisional de la Task 4**

Sustituye el mensaje provisional por el definitivo, que ya puede nombrar la acción:

```js
"sEmptyTable": "No hay solicitudes de cambio registradas. Usa «Nueva solicitud de cambio» para crear la primera.",
```

- [ ] **Step 4: Comprobar en el navegador**

`/control-cambios` a 1180×820 dark: la acción visible, y el estado vacío nombrándola.

- [ ] **Step 5: Commit**

```bash
git add -u
git commit -m "fix(control-cambios): accion para crear una solicitud y estado vacio definitivo"
```

### Task 27: Verificación de fase F5 y cierre del goal

- [ ] **Step 1: Correr toda la batería de este plan**

```bash
npx playwright test tests/browser/bi-kpi-copy.spec.mjs tests/browser/ht-empty-state.spec.mjs tests/browser/admin-login-a11y.spec.mjs tests/browser/escalamientos-sin-errores.spec.mjs tests/browser/bi-chip-contrast.spec.mjs tests/browser/bi-filtros.spec.mjs tests/browser/pdc-cabeceras.spec.mjs tests/browser/rail-concurrencia-no-tapa.spec.mjs tests/browser/bi-tabs-alcanzables.spec.mjs tests/browser/superficies-con-h1.spec.mjs --workers=1
```

Esperado: todo verde.

- [ ] **Step 2: Correr las suites del repositorio**

```bash
npm run test:design-system:static
docker compose exec app php tests/test_global_table_safety.php
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
```

Esperado: los tres rojos conocidos y ni uno más.

- [ ] **Step 3: Rehacer el barrido del inventario**

Vuelve a recorrer las 22 superficies a 1180×820 dark y comprueba **hallazgo por hallazgo** contra `goals/repaso-usabilidad-no-tablas/inventario-usabilidad.md` cuáles de los 26 quedaron cerrados. **Verifica cada uno; no los des por cerrados porque su tarea esté marcada.**

- [ ] **Step 4: Anotar el resultado en el goal**

Escribe en `goals/usabilidad-altas-y-medias/validation-log.md` qué se cerró, qué no y por qué. **Si algo salió a goal propio por el freno —H-18 o H-38—, dilo explícitamente**: un inventario parcial declarado vale mucho más que uno completo fingido.

- [ ] **Step 5: Ingesta en la wiki**

Actualiza `memoria/` según el esquema de `CLAUDE.md`: página o páginas afectadas, `memoria/index.md`, y una línea en `memoria/log.md`. Después:

```bash
node scripts/wiki-lint.mjs
```

Esperado: sin hallazgos.

---

## Self-Review

**Cobertura contra el inventario.** Los 26 aprobados tienen tarea:

| Fase | Hallazgos | Tareas |
|---|---|---|
| F1 | H-28 H-29 H-30 | 1, 2, 3 |
| F2 | H-02 H-01 H-04 H-05 | 4, 5, 6, 7, 8 |
| F3 | H-33 H-35 H-26 H-34 H-36 H-25 | 9, 10, 11, 12, 13, 14 |
| F4 | H-07 H-08 H-09 H-10 H-12 H-13 | 15, 16, 17, 18, 19, 20 |
| F5 | H-16 H-17 H-20 H-19 H-21 H-18 H-38 | 21, 22, 23, 24, 25, 26, 27 |

26 de 26. Los excluidos (H-03, H-24, H-39, H-14 y las 9 bajas) están declarados en «Fuera de alcance».

**Consistencia de nombres.** `attachHtEmptyState(hot, { titulo, cuerpo })` se define en la Task 5 y se consume con esa firma exacta en las Tasks 6 y 7. La clase `.ht-empty-state` es la misma en JS, CSS y prueba.

**Dos dependencias de orden que el ejecutor debe respetar:**

1. **Task 11 antes que Task 5.** La Task 5 usa `hot.addHook`, que hoy falla en escalamientos (H-26). Si se ejecutan en orden de plan, F3 va después de F2: por eso la Task 5 lleva la comprobación defensiva y la nota. **Alternativa recomendada: adelantar la Task 11 al principio de F2.**
2. **Task 4 antes que Task 26, y volver a la Task 4 al terminar.** El estado vacío de control de cambios nombra una acción que la Task 26 crea. La Task 4 usa texto provisional y la Task 26 lo cierra.

**Dos puntos donde el plan se detiene a propósito:** la Task 25 (freno de H-18) y el Step 1 de la Task 26 (si no existe alta en el backend). En ambos, parar y abrir goal propio es el resultado correcto, no un fallo.

---

## Estado verificado — sigue vigente

Verificado contra el código el 2026-08-25. **`estado: vigente` aquí significa que el trabajo sigue abierto** — es una afirmación deliberada, no el valor por defecto del backfill.

**Qué falta:** de los ~10 archivos de prueba que manda crear solo existe tests/browser/ht-empty-state.spec.mjs. Las fases F3, F4 y F5 (18 de 26 hallazgos) sin ejecutar, y goals/usabilidad-altas-y-medias/ no existe

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
