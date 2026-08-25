---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-08-03
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-03-saneamiento-deudas-usabilidad.md
resumen: cerrar las tres deudas de entorno y verificación que aparecieron ejecutando las cuatro primeras tareas del goal de usabilidad, antes de recorrer las 23…
---

# Saneamiento de las deudas abiertas del goal de usabilidad — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** cerrar las tres deudas de entorno y verificación que aparecieron ejecutando las cuatro primeras tareas del goal de usabilidad, antes de recorrer las 23 restantes.

**Architecture:** no se toca código de producción. Se modifican dos specs de Playwright para que dejen de fijar el puerto, se documenta el `COMPOSE_FILE` del worktree donde una sesión lo lea, y se completa un aserto que solo miraba la mitad de lo que debía.

**Tech Stack:** Playwright (specs en `tests/browser/`, config en `playwright.config.mjs`), Docker Compose, PHP 8.3 en el contenedor `app`.

**Spec:** [2026-08-03-saneamiento-deudas-usabilidad-design](../specs/2026-08-03-saneamiento-deudas-usabilidad-design.md)

## Global Constraints

- **Base:** rama `worktree-usabilidad-altas-y-medias`, a partir de `cb4a3d7`.
- **No publicar:** ni `push` ni deploy. Los commits se quedan en la rama.
- **Alcance visual:** desktop ≥1180px y dark mode exclusivamente. Viewport canónico `1180×820`. Nada de mobile, tablet ni tema `linen`.
- **Sesión local:** siempre por la puerta de servicio `"{BASE_URL}/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E"`. **Nunca** por `/login`, y nunca pedirle a una persona que inicie sesión.
- **Puerto:** este worktree se sirve en el **8091**; el 8081 es el checkout principal y tiene otro código. El `.env` local ya declara `COMPOSE_FILE`, así que `docker compose` a secas resuelve el stack correcto. Si no está levantado: `docker compose up -d app`.
- **`docker compose exec db` no existe aquí:** el override anula `db` y `adminer` a propósito, porque la base la sirve el stack principal. Para SQL directo: `docker compose exec -T app php -r "…"`.
- **`tests/browser/*` está en `.gitignore`** con una allowlist de negaciones. Un spec nuevo necesita su línea `!tests/browser/<nombre>` — los tres de este plan ya existen y ya están permitidos, así que **no** hace falta tocar `.gitignore`.
- **Medir la línea base con el árbol limpio:** el gate `canonical design-system contracts pass the executable gate` exige `worktree and index must be clean`. Con cambios sin commitear da un rojo que no es real.
- **Rojo heredado conocido:** `npm run test:design-system:static` falla en `la hoja de Programa General no vuelve a pintar el chip por nombre de estado` (falta `public/css/design-system/components/ops-state-chip.css`, que llegó incompleto con el merge de `main`). **Un solo rojo es lo esperado. Un segundo rojo es nuestro.**

---

## File Structure

| Archivo | Responsabilidad | Tarea |
|---|---|---|
| `tests/browser/bi-kpi-copy.spec.mjs` | Que los KPI de BI muestren las unidades legítimas y oculten la interna `count` | 1 y 3 |
| `tests/browser/escalamientos-sin-errores.spec.mjs` | Que el dashboard sin malla no lance errores de JS ni ofrezca lo que no puede cumplir | 1 |
| `docker-compose.usabilidad.yml` | Documentar el arranque del stack de este worktree (archivo local, no versionado) | 2 |
| `.superpowers/sdd/2026-08-03-usabilidad-altas-y-medias/progress.md` | Ledger del goal (archivo local, no versionado) | 2 |

La Tarea 3 modifica un archivo que la Tarea 1 ya tocó: **ejecutar en orden**.

---

### Task 1: Los specs dejan de fijar el puerto

**Files:**
- Modify: `tests/browser/bi-kpi-copy.spec.mjs:6-7`
- Modify: `tests/browser/escalamientos-sin-errores.spec.mjs:10-11`

**Interfaces:**
- Consumes: `BASE_URL` de `tests/browser/fixtures/base-url.mjs` (export nombrado, un string como `"http://localhost:8091"`, sin barra final).
- Produces: nada. Ningún otro archivo depende de estos specs.

**Contexto:** ambos specs llevan `http://localhost:8091` escrito a fuego. Ese puerto lo publica el stack **de este worktree**; en `main` no existe. Al fusionar, atacarán un puerto muerto. `tests/browser/escalamientos-acciones.spec.mjs` ya usa `BASE_URL` — este es el patrón a copiar.

- [ ] **Step 1: Comprobar a qué puerto apuntan hoy**

```bash
grep -n "localhost:8091" tests/browser/bi-kpi-copy.spec.mjs tests/browser/escalamientos-sin-errores.spec.mjs
```

Esperado: cuatro líneas (dos por archivo). Si aparecen más, corrígelas todas.

- [ ] **Step 2: Confirmar que `BASE_URL` resuelve al stack de este worktree**

```bash
node -e "import('./tests/browser/fixtures/base-url.mjs').then(m => console.log(m.BASE_URL))"
```

Esperado: `http://localhost:8091`.

**Si imprime `http://localhost:8081`, PARA.** Significa que el `.env` de este worktree perdió su línea `COMPOSE_FILE` y todo el plan mediría la rama equivocada. Arréglalo antes de seguir: `docker compose port app 80` debe responder `0.0.0.0:8091`.

- [ ] **Step 3: Migrar `bi-kpi-copy.spec.mjs`**

Sustituye el archivo entero por:

```js
import { test, expect } from '@playwright/test';
import { BASE_URL } from './fixtures/base-url.mjs';

test('los KPI del control tower no muestran la unidad cruda "count"', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.emulateMedia({ colorScheme: 'dark' });
  await page.goto(`${BASE_URL}/dev/entrar?u=test.R&p=${encodeURIComponent('PDC Sandbox E2E')}`);
  await page.goto(`${BASE_URL}/bi/control-tower`);
  await page.waitForLoadState('networkidle');
  const texto = await page.locator('body').innerText();
  expect(texto).not.toMatch(/\bcount\b/i);
});
```

- [ ] **Step 4: Migrar `escalamientos-sin-errores.spec.mjs`**

Añade el import bajo la primera línea:

```js
import { BASE_URL } from './fixtures/base-url.mjs';
```

Y sustituye las dos navegaciones del helper `irAEscalamientos` por:

```js
  await page.goto(`${BASE_URL}/dev/entrar?u=test.R&p=${encodeURIComponent('PDC Sandbox E2E')}`);
  await page.goto(`${BASE_URL}/dashboard/escalamientos`);
```

**No toques nada más de ese archivo:** sus tres tests y sus comentarios se quedan como están.

- [ ] **Step 5: Comprobar que no queda ningún puerto fijo**

```bash
grep -rn "localhost:8091\|localhost:8081" tests/browser/*.spec.mjs
```

Esperado: **sin resultados**.

- [ ] **Step 6: Ejecutar los cuatro specs**

```bash
npx playwright test tests/browser/bi-kpi-copy.spec.mjs tests/browser/escalamientos-sin-errores.spec.mjs tests/browser/escalamientos-acciones.spec.mjs --workers=1 --reporter=list
```

Esperado: **`7 passed`** — 1 de `bi-kpi-copy`, 3 de `escalamientos-sin-errores` y 3 de `escalamientos-acciones`.

- [ ] **Step 7: Comprobar que atacan el 8091 y no el 8081**

Verde no basta: el fallo original consistía justamente en pasar en verde contra el stack equivocado.

Añade **temporalmente** como primera línea del cuerpo del test de `bi-kpi-copy.spec.mjs`:

```js
  page.on('request', (r) => { if (r.url().includes('/bi/')) console.log('PIDE:', r.url()); });
```

Ejecuta:

```bash
npx playwright test tests/browser/bi-kpi-copy.spec.mjs --workers=1 --reporter=list 2>&1 | grep "PIDE:" | head -3
```

Esperado: todas las URL empiezan por `http://localhost:8091`. Si aparece `8081`, para: `BASE_URL` está resolviendo al checkout principal y el paso 2 debería haberlo detectado.

**Quita esa línea antes de commitear** y confirma que no queda:

```bash
grep -n "PIDE:" tests/browser/bi-kpi-copy.spec.mjs
```

Esperado: sin resultados.

- [ ] **Step 8: Commit**

```bash
git add tests/browser/bi-kpi-copy.spec.mjs tests/browser/escalamientos-sin-errores.spec.mjs
git commit -m "test(browser): los specs dejan de fijar el puerto del worktree

Ambos llevaban localhost:8091 escrito a fuego. Ese puerto lo publica el
stack de este worktree y en main no existe: al fusionar habrian atacado
un puerto muerto, fallando sin explicar por que. BASE_URL se deriva del
stack de cada working tree y resuelve solo."
```

---

### Task 2: El entorno del worktree deja de ser un secreto

**Files:**
- Modify: `docker-compose.usabilidad.yml` (cabecera de comentarios; archivo local, no versionado)
- Modify: `.superpowers/sdd/2026-08-03-usabilidad-altas-y-medias/progress.md` (ledger; archivo local, no versionado)

**Interfaces:**
- Consumes: nada.
- Produces: nada ejecutable. Es documentación.

**Contexto:** `docker compose` a secas, desde este worktree, resolvía el stack del checkout principal (8081) porque el override propio solo entra con un tercer `-f`. Eso arrastraba a `BASE_URL`, a `sqlEnApp()` y a cualquier comando escrito de memoria, e hizo que unos tests midieran la rama equivocada. Se corrigió declarando `COMPOSE_FILE` en el `.env`, pero `.env` no se versiona: la próxima sesión no tiene cómo enterarse.

**Ninguno de estos dos archivos se versiona.** Esta tarea **no produce commit**; su entrega es que la información quede escrita donde se lee.

- [ ] **Step 1: Confirmar que el `.env` declara `COMPOSE_FILE`**

```bash
grep -n "COMPOSE_FILE" .env
docker compose port app 80
```

Esperado: la línea existe y el puerto es `0.0.0.0:8091`. Si falta, añádela:

```
COMPOSE_FILE=docker-compose.yml:docker-compose.override.yml:docker-compose.usabilidad.yml
```

- [ ] **Step 2: Documentarlo en la cabecera del compose**

En `docker-compose.usabilidad.yml`, bajo el bloque de comentarios que ya explica el arranque, añade:

```yaml
# El `.env` de este worktree declara COMPOSE_FILE con estos tres archivos, asi que
# `docker compose ...` a secas ya resuelve este stack y NO hace falta repetir los -f.
# Sin esa linea, `docker compose` resuelve el stack del checkout principal (8081):
# BASE_URL de los e2e apunta alli y los specs miden el codigo de OTRA rama, en verde.
#
# Consecuencia de anular db y adminer: `docker compose exec db` no existe aqui. Para
# SQL directo, `docker compose exec -T app php -r "..."` o el checkout principal.
```

- [ ] **Step 3: Dejar constancia en el ledger**

En `.superpowers/sdd/2026-08-03-usabilidad-altas-y-medias/progress.md`, dentro de la sección «Trampas del entorno medidas aqui», comprueba que la trampa del `COMPOSE_FILE` está descrita y que ya **no** dice que los specs lleven el 8091 hardcodeado (la Tarea 1 lo arregló). Actualiza esa línea para que refleje el estado real.

- [ ] **Step 4: Comprobar que una sesión nueva lo encontraría**

```bash
grep -n "COMPOSE_FILE" docker-compose.usabilidad.yml .superpowers/sdd/2026-08-03-usabilidad-altas-y-medias/progress.md
```

Esperado: al menos una coincidencia en cada archivo.

- [ ] **Step 5: Sin commit**

Ambos archivos están en `.gitignore`. Confirma que el árbol sigue limpio:

```bash
git status --short
```

Esperado: **sin resultados**. Si aparece alguno de los dos archivos, no los fuerces al índice: revisa por qué dejaron de estar ignorados.

---

### Task 3: El aserto de BI mira las dos mitades

**Files:**
- Modify: `tests/browser/bi-kpi-copy.spec.mjs`

**Interfaces:**
- Consumes: `BASE_URL` (ya importado por la Tarea 1).
- Produces: nada.

**Contexto:** el spec solo afirma que la cadena `count` **no** aparece. El brief de la Task 2 del goal pedía además garantizar que las unidades legítimas **siguen** viéndose; sin eso, un cambio que borrase todas las unidades pasaría en verde.

**Dato medido, que corrige un supuesto del spec de diseño:** en el sandbox **todos** los KPI del scorecard llegan con `unit: "count"` — ninguno emite `%`. Un aserto contra los datos reales no puede comprobar la mitad positiva. Por eso el test **inyecta** la unidad: intercepta la respuesta real y le cambia la unidad a dos KPI. Así prueba las dos mitades y además deja de depender de lo que el sandbox tenga sembrado.

El código bajo prueba es `valueWithUnit()` (`public/js/modules/bi-spa.js:3661`), que devuelve `` `${value} ${unit}` `` salvo cuando la unidad es `count`, en cuyo caso omite la unidad. Los cuatro KPI del scorecard se pintan en los elementos `#kpi-ppc`, `#kpi-programadas`, `#kpi-ejecutadas` y `#kpi-brecha`.

- [ ] **Step 1: Escribir el test que falla**

Añade al final de `tests/browser/bi-kpi-copy.spec.mjs`:

```js
test('los KPI de BI siguen mostrando las unidades legitimas', async ({ page }) => {
  // El sandbox emite `count` en todos los KPI, asi que la unidad legitima se inyecta: si no, no
  // habria nada que afirmar y el test solo cubriria la mitad negativa, que es el hueco que tapa.
  await page.route('**/api/bi/control-tower**', async (route) => {
    const respuesta = await route.fetch();
    const datos = await respuesta.json();
    if (Array.isArray(datos.scorecard) && datos.scorecard.length >= 2) {
      datos.scorecard[0] = { ...datos.scorecard[0], value: 87, unit: '%' };
      datos.scorecard[1] = { ...datos.scorecard[1], value: 12, unit: 'count' };
    }
    await route.fulfill({ response: respuesta, json: datos });
  });

  await page.setViewportSize({ width: 1180, height: 820 });
  await page.emulateMedia({ colorScheme: 'dark' });
  await page.goto(`${BASE_URL}/dev/entrar?u=test.R&p=${encodeURIComponent('PDC Sandbox E2E')}`);
  await page.goto(`${BASE_URL}/bi/control-tower`);
  await page.waitForLoadState('networkidle');

  // La unidad legitima se ve...
  await expect(page.locator('#kpi-ppc')).toHaveText('87 %');
  // ...y la interna sigue sin verse, sobre el mismo payload.
  await expect(page.locator('#kpi-programadas')).toHaveText('12');
});
```

- [ ] **Step 2: Ejecutar y confirmar que pasa**

```bash
npx playwright test tests/browser/bi-kpi-copy.spec.mjs --workers=1 --reporter=list
```

Esperado: `2 passed`.

**Si el nuevo test falla porque `#kpi-ppc` está vacío o no recibe el payload inyectado**, el patrón de la ruta no coincide con la URL real que pide la SPA. Diagnostica antes de tocar el aserto:

```js
  page.on('request', (r) => { if (r.url().includes('control-tower')) console.log('PIDE:', r.url()); });
```

Ajusta el patrón de `page.route` a la URL que imprima. **No ajustes el valor esperado para que pase.**

- [ ] **Step 3: Comprobar que el test protege de verdad**

Un test que pasa no demuestra que sirva. Rómpelo a propósito: en `public/js/modules/bi-spa.js:3665`, cambia

```js
  const unit = rawUnit === 'count' ? '' : rawUnit;
```

por

```js
  const unit = '';
```

- [ ] **Step 4: Ejecutar y confirmar que ahora falla**

```bash
npx playwright test tests/browser/bi-kpi-copy.spec.mjs --workers=1 --reporter=list
```

Esperado: **falla** el test nuevo (`#kpi-ppc` mostraría `87` sin `%`), y el viejo sigue pasando. Eso demuestra exactamente el hueco que se estaba tapando.

- [ ] **Step 5: Deshacer la rotura**

```bash
git checkout -- public/js/modules/bi-spa.js
git diff --stat
```

Esperado: `bi-spa.js` **no** aparece en el diff. Solo debe figurar el spec.

- [ ] **Step 6: Ejecutar de nuevo y confirmar verde**

```bash
npx playwright test tests/browser/bi-kpi-copy.spec.mjs --workers=1 --reporter=list
```

Esperado: `2 passed`.

- [ ] **Step 7: Commit**

```bash
git add tests/browser/bi-kpi-copy.spec.mjs
git commit -m "test(bi): el spec de los KPI comprueba tambien la mitad positiva

Solo afirmaba que 'count' no aparece, asi que un cambio que borrara TODAS
las unidades habria pasado en verde. El sandbox emite 'count' en los cuatro
KPI y ninguno trae '%', de modo que la unidad legitima se inyecta
interceptando la respuesta: cubre las dos mitades y deja de depender de lo
que el sandbox tenga sembrado."
```

---

### Task 4: Verificación de cierre

**Files:** ninguno. Solo se ejecuta y se reporta.

**Interfaces:**
- Consumes: el resultado de las tareas 1 a 3.
- Produces: la evidencia con la que se cierra el saneamiento.

- [ ] **Step 1: Confirmar que el árbol está limpio**

```bash
git status --short
```

Esperado: **sin resultados**. El siguiente paso lo exige: medir con cambios sin commitear cuenta un rojo que no es real.

- [ ] **Step 2: Suite estático**

```bash
npm run test:design-system:static 2>&1 | grep -E "✖" | head
```

Esperado: **exactamente un** `✖`, el de `la hoja de Programa General no vuelve a pintar el chip por nombre de estado`. Un segundo rojo es nuestro y hay que diagnosticarlo antes de cerrar.

- [ ] **Step 3: Los cuatro specs del goal**

```bash
npx playwright test tests/browser/bi-kpi-copy.spec.mjs tests/browser/escalamientos-sin-errores.spec.mjs tests/browser/escalamientos-acciones.spec.mjs --workers=1 --reporter=list
```

Esperado: `8 passed` (2 de BI + 3 + 3).

- [ ] **Step 4: Confirmar que no quedó ningún puerto fijo**

```bash
grep -rn "localhost:80" tests/browser/*.spec.mjs
```

Esperado: **sin resultados**.

- [ ] **Step 5: Confirmar que los datos quedan como estaban**

```bash
docker compose exec -T app php -r "require '/var/www/html/vendor/autoload.php'; require '/var/www/html/src/Core/Database.php'; \$db = Database::getInstance(); echo 'lps_escalamientos: ' . \$db->query('SELECT COUNT(*) c FROM lps_escalamientos')->fetch(PDO::FETCH_ASSOC)['c'];"
```

Esperado: `lps_escalamientos: 0`. El spec de escalamientos siembra y borra su alerta; si queda residuo, su `afterAll` no corrió y hay que limpiarlo a mano.

- [ ] **Step 6: Reportar**

Deja en el ledger `.superpowers/sdd/2026-08-03-usabilidad-altas-y-medias/progress.md` el resultado de los pasos 2, 3 y 5, y la base para retomar el plan del goal: **Task 4 de las 27, con base en el último commit de este saneamiento**.

---

## Fuera de este plan

Las tres deudas restantes salieron del alcance en el spec, cada una con su acción:

- **El CSS `ops-state-chip.css` que falta** — es del goal `pg-chip-de-estado`, llegó incompleto con el merge de `main`. Chip creado. No lo toques aquí: crear una hoja del design system sin las decisiones visuales de ese goal es lo que prohíbe `docs/design-system/`.
- **La FK que impide cerrar una crisis** — exige decidir a quién representa «quién cerró la crisis» y puede requerir migración de esquema, con las reglas duras de `AGENTS.md`. Chip creado, con su propio ciclo spec → plan.
- **Las dos políticas de unidades en BI** (`valueWithUnit` con lista negra, `renderPDC` con lista blanca) — unificarlas cambia lo que se ve, y los cambios visuales exigen aprobación explícita. Queda documentado como decisión de producto.

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** bi-kpi-copy.spec.mjs y escalamientos-sin-errores.spec.mjs usan BASE_URL; cero localhost:8081/8091 fijos

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
