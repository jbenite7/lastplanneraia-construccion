---
capa: fuente
tipo: plan
estado: propuesto
fecha: 2026-09-24
areas: [lps, design-system, shell]
ejecutor: codex
spec: docs/superpowers/specs/2026-08-30-s05-programa-general-react-design.md
spec_seccion: "Enmienda del 2026-09-24 (ronda 1.2)"
fuente: docs/superpowers/plans/2026-09-24-s05-ronda-1-2-paridad-legado.md
resumen: "Ronda 1.2 de S05: tabla de Programa General sin desbordamiento y con scroll vertical real, contador correcto, módulo sin tokens globales propios, shell React con el riel y la barra de contexto del legado, y pruebas de CI reescritas contra el DOM React sin rebajar lo que verifican."
---

# S05 ronda 1.2 — Programa General y shell React a la par del legado — Plan de implementación

> **Para agentes:** SUB-SKILL OBLIGATORIA: usa `superpowers:subagent-driven-development` (recomendado)
> o `superpowers:executing-plans` para recorrer este plan tarea por tarea;
> `superpowers:test-driven-development` en cada tarea; `superpowers:systematic-debugging` ante
> cualquier falla; `superpowers:requesting-code-review` para el revisor independiente; y
> `superpowers:verification-before-completion` y `superpowers:finishing-a-development-branch` al
> cierre. Los pasos usan casillas (`- [ ]`).

**Objetivo:** que `/programa-general` en React muestre la tabla completa sin desbordamiento, con
scroll vertical real y cifras correctas, dentro de un shell React que se vea y se comporte igual al
del legado PHP, con todo el CI en verde en los dos temas.

**Arquitectura:** se trabaja sobre `feature/s05-paridad-visual` en `8ad3ca3f` (solo commits; lo
que quedó sin commit en el checkout principal se descarta). La tabla pasa a `table-layout: fixed`
con `<colgroup>` y la columna «Actividad» absorbe el ancho sobrante y parte el texto; el contenedor
de la tabla es dueño de su scroll vertical. El shell React arranca colapsado y recuerda el estado
con la misma clave que el legado, y la semana sale del riel a una barra de contexto superior que
reutiliza el markup y las clases del legado (`.context-bar`, `.context-week-chip`). Las pruebas que
manejaban Handsontable se reescriben contra la interfaz React, verificando lo mismo.

**Tech stack:** React 19, TypeScript, Vite, Vitest + Testing Library, Playwright, PHP 8.3 en Docker
(`app`), MySQL 8.0, tokens del design system AIA (`--ds-*`).

**Spec:** `docs/superpowers/specs/2026-08-30-s05-programa-general-react-design.md`, sección
«Enmienda del 2026-09-24 (ronda 1.2)», con sus requisitos R1.2-1 a R1.2-4, hechos H1 a H14 y vacíos
V1 a V5. Léela entera antes de la tarea 1.

## Restricciones globales

- **Viewport canónico:** 1180×820, **ambos temas** (claro de entrada, oscuro de primera clase).
- **R1.2-1:** cero desbordamiento horizontal de la tabla con 8 **y** con 13 columnas, con el riel
  colapsado **y** expandido. Nunca aparece barra horizontal.
- **R1.2-2:** «Actividad» parte el texto en varias líneas y muestra siempre el texto completo: sin
  `text-overflow: ellipsis`, sin `line-clamp`, sin `overflow: hidden` que recorte.
- **R1.2-3:** el scroll vertical de la tabla alcanza la última fila, con el encabezado visible.
- **R1.2-4:** el shell React se ve y se comporta como el del legado, en **todo** el shell React:
  riel angosto de íconos que se despliega con su botón, estado recordado y semana en la barra
  superior.
- **El mockup `public/mockups/s05-production-mockup.html` es solo de guía.** Donde choque con
  R1.2-1 a R1.2-4, `DESIGN.md` o el legado, pierde el mockup.
- **Nada de imitar el DOM de Handsontable** para pasar pruebas (H13): ni `id="hot-container"` falso,
  ni clase `handsontable` en una `<table>` plana, ni `window.PGHotModule` inventado. Las pruebas se
  reescriben contra la interfaz React.
- **Ninguna prueba se debilita.** Una prueba reescrita verifica lo mismo que la original
  (persistencia UI→API→BD, RBAC con un rol permitido y uno denegado, presupuesto de runtime,
  contraste). Si no puede, se reporta `BLOCKED`, no se borra la aserción.
- **Capturas de referencia:** `AGENTS.md` prohíbe regenerar capturas o baselines para forzar un
  verde; los cambios visuales requieren la aprobación explícita de Felipe. La tarea 9 genera las
  capturas candidatas y **para en `BLOCKED` hasta el visto de Felipe** para ese único paso; el resto
  del sprint sigue.
- **Módulos migrados:** sin hex, sin estilos inline nuevos y sin variantes locales de tokens
  (`AGENTS.md`, `DESIGN.md`). Los estilos del módulo van en `@layer module`.
- Cero DDL, DML, migraciones o backfills. Cero cambios de RLS o permisos.
- Todo PHP y las pruebas PHP corren dentro de `app`: `docker compose exec app …`.
- **Sin push, sin merge, sin deploy.** El sprint termina con el informe; publicar es de Felipe.
- **Worktree propio, nunca el checkout principal compartido.** El `.env` se enlaza, no se copia.
- **Condición de hecho (decisión de Felipe del 2026-09-24):** todas las variables `G_*` del paso
  «Summarize gate results» en verde en los dos temas, y capturas a 1180×820 en claro y oscuro,
  tomadas después del último commit, que prueben R1.2-1 a R1.2-4 (también con 13 columnas).

## Mapa de archivos

```text
# Base y limpieza
src/Controllers/Core/DevDoorController.php                     (volver a la versión de main)
tests/browser/s05-programa-general-react.spec.mjs              (p=1 → nombre de proyecto; nuevas pruebas de layout)
scripts/generate_s05_live_screenshots.mjs                      (p=1 → nombre de proyecto; capturas de la ronda)

# Programa General
frontend/src/modules/programa-general/domain/filtros.ts        (contarTareasVisibles)
frontend/src/modules/programa-general/domain/filtros.test.ts
frontend/src/modules/programa-general/ProgramaGeneralPage.tsx  (usa contarTareasVisibles)
frontend/src/modules/programa-general/components/ProgramaTable.tsx      (<colgroup>, sin anchos inline)
frontend/src/modules/programa-general/components/ProgramaTable.test.tsx
frontend/src/modules/programa-general/programa-general.css     (sin tokens globales; layout fijo; scroll)
tests/design-system/programa-general-sin-tokens-locales.test.mjs        (nuevo)

# Shell React
frontend/src/shell/modoBarraLateral.ts                         (estado inicial + persistencia)
frontend/src/shell/modoBarraLateral.test.ts                    (nuevo o ampliado)
frontend/src/shell/AppShell.tsx                                (riel colapsado; monta BarraContexto)
frontend/src/shell/BarraContexto.tsx                           (nuevo: proyecto / módulo / chip de semana)
frontend/src/shell/BarraContexto.test.tsx                      (nuevo)
frontend/src/shell/DialogosSemana.tsx                          (nuevo: crear y eliminar semana)
frontend/src/shell/ContextoSemana.tsx                          (se elimina; lo reemplazan los dos anteriores)
frontend/src/shell/ContextoSemana.test.tsx                     (se elimina; sus casos pasan a los nuevos tests)
frontend/src/shell/NavegacionLateral.tsx                       (sin prop contextoSemana; acción «Semanas del Proyecto»)
tests/browser/shell-react-paridad-legado.spec.mjs              (nuevo)

# Pruebas de CI atadas a Handsontable
e2e/tests/workflows/pg-interactions.spec.mjs
e2e/support/programa-general-react.mjs                         (nuevo: helpers contra el DOM React)
tests/browser/design-system-runtime-budget.mjs                 (superficie PG)
tests/browser/programa-general-design-system.mjs               (V1)
tests/browser/programa-general.visual.mjs                      (capturas candidatas, tarea 9)
tests/browser/full-app-flow.spec.mjs                           (V2, solo si es la causa)

# Bundle y evidencia
public/app/**                                                  (npm --prefix frontend run build)
docs/superpowers/evidence/s05-ronda-1-2/                       (capturas finales)
docs/superpowers/plans/2026-09-24-s05-ronda-1-2-informe.md     (informe de sprint)
```

---

### Tarea 0: Worktree, base y línea de partida medida

**Archivos:** ninguno de código.

- [ ] **Paso 1: Crear el worktree desde el commit de partida**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia"
git fetch origin
git worktree add .claude/worktrees/s05-ronda-1-2 -b codex/s05-ronda-1-2 8ad3ca3f
cd .claude/worktrees/s05-ronda-1-2
ln -s ~/Developer/lps-aia/.env .env
readlink .env && test -f .env && echo "env OK"
```

Esperado: `env OK`. Si `test -f` falla, el enlace apunta a la nada (ver `CLAUDE.md`): **para en
`BLOCKED`**.

- [ ] **Paso 2: Integrar `main`** (la rama se abrió antes de los últimos merges)

```bash
git merge origin/main
```

Conflictos a la vista, nunca a ciegas. Nunca `push --force`.

- [ ] **Paso 3: Medir la línea de partida** (anota cada código de salida en su propia línea)

```bash
npm --prefix frontend ci
npm --prefix frontend run typecheck; echo "RC typecheck=$?"
npm --prefix frontend run test; echo "RC vitest=$?"
docker compose exec -T app php tests/test_dev_door_guard.php; echo "RC devdoor=$?"
```

Guarda las salidas en el informe (sección «Partida»). No corrijas nada aún.

- [ ] **Paso 4: Servir el worktree en `localhost:8081`**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose up -d app
```

Al terminar el sprint, devuélvelo a la raíz (`docker compose up -d app` desde la raíz).

---

### Tarea 1: Puerta de desarrollo sin el respaldo por posición (V5, H10)

**Archivos:**
- Modificar: `src/Controllers/Core/DevDoorController.php` (volver a `origin/main`)
- Modificar: `tests/browser/s05-programa-general-react.spec.mjs:331`
- Modificar: `scripts/generate_s05_live_screenshots.mjs:29`

**Interfaces:** ninguna nueva. La puerta vuelve a aceptar `p=<Proyecto_Proceso>` y nada más.

- [ ] **Paso 1: Restaurar el controlador de `main`**

```bash
git checkout origin/main -- src/Controllers/Core/DevDoorController.php
git diff --stat origin/main -- src/Controllers/Core/DevDoorController.php
```

Esperado: sin diferencias.

- [ ] **Paso 2: Cambiar los dos llamados `p=1` por el nombre del proyecto**

En los dos archivos, reemplaza:

```js
await page.goto('http://localhost:8081/dev/entrar?u=test.A&p=1');
```

por:

```js
await page.goto('http://localhost:8081/dev/entrar?u=test.A&p=' + encodeURIComponent('Da Porto'));
```

- [ ] **Paso 3: Verificar**

```bash
grep -rn "dev/entrar?u=[^&]*&p=[0-9]" tests e2e scripts; echo "RC grep=$? (1 = sin coincidencias)"
docker compose exec -T app php tests/test_dev_door_guard.php; echo "RC devdoor=$?"
```

Esperado: `RC grep=1` y `RC devdoor=0`.

- [ ] **Paso 4: Commit**

```bash
git add src/Controllers/Core/DevDoorController.php tests/browser/s05-programa-general-react.spec.mjs scripts/generate_s05_live_screenshots.mjs
git commit -m "fix(dev-door): quitar el respaldo por posición de p numérico; pruebas entran por nombre de proyecto"
```

---

### Tarea 2: El contador cuenta tareas, no capítulos (H3)

**Archivos:**
- Modificar: `frontend/src/modules/programa-general/domain/filtros.ts`
- Modificar: `frontend/src/modules/programa-general/domain/filtros.test.ts`
- Modificar: `frontend/src/modules/programa-general/ProgramaGeneralPage.tsx:193-194`

**Interfaces:**
- Produce: `export function contarTareasVisibles(actividades: ActividadUI[]): number` en `domain/filtros.ts`.

- [ ] **Paso 1: Prueba que falla** — agrega al final de `filtros.test.ts`, dentro del `describe`:

```ts
  it('contarTareasVisibles excluye las filas de capítulo, igual que conteos.total', () => {
    const visibles = filtrarActividades(actividadesMock, '', null);
    const conteos = calcularConteosSenales(actividadesMock);
    expect(visibles.some((a) => a.esCapitulo)).toBe(true);
    expect(contarTareasVisibles(visibles)).toBe(conteos.total);
  });

  it('contarTareasVisibles nunca supera conteos.total con un filtro aplicado', () => {
    const visibles = filtrarActividades(actividadesMock, '', 'Atrasada');
    const conteos = calcularConteosSenales(actividadesMock);
    expect(contarTareasVisibles(visibles)).toBeLessThanOrEqual(conteos.total);
    expect(contarTareasVisibles(visibles)).toBe(conteos.atrasadas);
  });
```

y agrega `contarTareasVisibles` al `import` de `./filtros`.

- [ ] **Paso 2: Verificar que falla**

Corre: `npm --prefix frontend run test -- src/modules/programa-general/domain/filtros.test.ts`.
Esperado: FALLA porque `contarTareasVisibles` no existe.

- [ ] **Paso 3: Implementar** — al final de `domain/filtros.ts`:

```ts
/** Cuenta solo tareas operativas: los capítulos agrupan, no son actividades (spec S05 ronda 1.2, H3). */
export function contarTareasVisibles(actividades: ActividadUI[]): number {
  return actividades.filter((a) => !a.esCapitulo).length;
}
```

En `ProgramaGeneralPage.tsx`, importa `contarTareasVisibles` y cambia:

```tsx
totalVisibles={actividadesFiltradas.length}
```

por:

```tsx
totalVisibles={contarTareasVisibles(actividadesFiltradas)}
```

- [ ] **Paso 4: Verificar que pasa**

Corre el mismo comando del paso 2 más `npm --prefix frontend run typecheck`. Esperado: PASA y `RC=0`.

- [ ] **Paso 5: Commit**

```bash
git add frontend/src/modules/programa-general/domain/filtros.ts frontend/src/modules/programa-general/domain/filtros.test.ts frontend/src/modules/programa-general/ProgramaGeneralPage.tsx
git commit -m "fix(pg): el contador de visibles excluye capítulos, como el total"
```

---

### Tarea 3: El módulo deja de redefinir tokens globales (H12)

**Archivos:**
- Crear: `tests/design-system/programa-general-sin-tokens-locales.test.mjs`
- Modificar: `frontend/src/modules/programa-general/programa-general.css`

**Interfaces:**
- Produce: `programa-general.css` solo **consume** tokens `--ds-*`; no define ninguno, ni en
  `:root` ni en selectores de tema, y no contiene colores hex ni `rgb()`/`rgba()` literales.

- [ ] **Paso 1: Prueba que falla**

```js
// tests/design-system/programa-general-sin-tokens-locales.test.mjs
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const css = readFileSync('frontend/src/modules/programa-general/programa-general.css', 'utf8');
const sinComentarios = css.replace(/\/\*[\s\S]*?\*\//g, '');

test('programa-general.css no define tokens --ds-* (solo los consume)', () => {
  const definiciones = sinComentarios.match(/--ds-[a-z0-9-]+\s*:/gi) ?? [];
  assert.deepEqual(definiciones, []);
});

test('programa-general.css no redefine :root ni selectores de tema', () => {
  assert.doesNotMatch(sinComentarios, /(^|[,{}\s]):root\b/);
  assert.doesNotMatch(sinComentarios, /\[data-(aia-)?theme=/);
});

test('programa-general.css no trae colores literales', () => {
  const hex = sinComentarios.match(/#[0-9a-f]{3,8}\b/gi) ?? [];
  const rgb = sinComentarios.match(/rgba?\(\s*\d/gi) ?? [];
  assert.deepEqual([...hex, ...rgb], []);
});
```

- [ ] **Paso 2: Verificar que falla**

Corre: `node --test tests/design-system/programa-general-sin-tokens-locales.test.mjs`.
Esperado: FALLA en los tres tests (hoy hay 102 hex y un bloque `:root`).

- [ ] **Paso 3: Implementar**

1. Borra el bloque «TOKENS Y CONTRASTE BIPOLAR» (`programa-general.css:6-~110` en `8ad3ca3f`)
   y cualquier otro bloque que defina `--ds-*` o use `:root`/`[data-theme]`/`[data-aia-theme]`.
2. Reemplaza cada color literal por el token canónico equivalente que ya existe en
   `public/css/tokens.css` y en los temas de `public/css/design-system/`. Para escoger, lee
   `DESIGN.md` («contrato de consumo») y usa el token por **rol** (texto primario, borde sutil,
   superficie, estado), no por parecido de color. Si un rol no tiene token, **no** inventes uno
   local: repórtalo en el informe como hallazgo y usa el token de rol más cercano.
3. Los colores de estado (Atrasada, Con alerta, etc.) salen de los tokens de estado del design
   system (`--ds-state-*` o los que declare `docs/design-system/`); los que el módulo pinta inline
   desde `presentacionEstados.ts` (`style={{ backgroundColor: estadoCfg.colorDot }}`) se dejan como
   están en esta tarea: son dato, no hoja.

- [ ] **Paso 4: Verificar**

```bash
node --test tests/design-system/programa-general-sin-tokens-locales.test.mjs; echo "RC=$?"
npm run test:design-system:static; echo "RC static=$?"
npm --prefix frontend run test; echo "RC vitest=$?"
```

Esperado: tres `RC=0`. Luego, en el navegador a 1180×820 con `test.A` en Da Porto, abre
`/programa-general` en claro y en oscuro (conmutador del pie del riel) y comprueba a ojo que la
página sigue el tema elegido: fondo, texto y chips. Anota en el informe lo que viste.

- [ ] **Paso 5: Commit**

```bash
git add tests/design-system/programa-general-sin-tokens-locales.test.mjs frontend/src/modules/programa-general/programa-general.css
git commit -m "fix(pg): el módulo consume tokens del design system y deja de redefinirlos en :root"
```

---

### Tarea 4: Tabla sin desbordamiento y con «Actividad» completa (R1.2-1, R1.2-2, H2)

**Archivos:**
- Modificar: `frontend/src/modules/programa-general/components/ProgramaTable.tsx`
- Modificar: `frontend/src/modules/programa-general/components/ProgramaTable.test.tsx`
- Modificar: `frontend/src/modules/programa-general/programa-general.css`
- Modificar: `tests/browser/s05-programa-general-react.spec.mjs`

**Interfaces:**
- Produce: `ProgramaTable` emite `<colgroup>` con una `<col className="pg-col-<clave>">` por
  columna, en el mismo orden que el `<thead>`. Claves del modo 8: `id, codigo, actividad, inicio,
  fin, ppto, avance, estado`. Claves del modo 13: `id, codigo, actividad, rc, inicio, sem, fin,
  cantidad, unidad, real, teorico, restricciones, estado`. Los `<th>` y `<td>` no llevan `width`
  inline.

- [ ] **Paso 1: Prueba unitaria que falla** — en `ProgramaTable.test.tsx`:

```tsx
it('declara un <colgroup> con una col por columna y sin anchos inline (modo 8)', () => {
  const { container } = render(
    <ProgramaTable actividades={[]} actividadSeleccionadaId={null} onSelectActividad={() => {}} />,
  );
  const cols = [...container.querySelectorAll('colgroup col')].map((c) => c.className);
  expect(cols).toEqual([
    'pg-col-id', 'pg-col-codigo', 'pg-col-actividad', 'pg-col-inicio',
    'pg-col-fin', 'pg-col-ppto', 'pg-col-avance', 'pg-col-estado',
  ]);
  container.querySelectorAll('th').forEach((th) => expect(th.style.width).toBe(''));
});

it('declara 13 cols en modo completo', () => {
  const { container } = render(
    <ProgramaTable actividades={[]} actividadSeleccionadaId={null} onSelectActividad={() => {}} modo13Cols />,
  );
  expect(container.querySelectorAll('colgroup col')).toHaveLength(13);
  expect(container.querySelectorAll('thead th')).toHaveLength(13);
});
```

- [ ] **Paso 2: Verificar que falla**

`npm --prefix frontend run test -- src/modules/programa-general/components/ProgramaTable.test.tsx`
→ FALLA (no hay `<colgroup>`).

- [ ] **Paso 3: Implementar el `<colgroup>`** — en `ProgramaTable.tsx`, antes de `<thead>`:

```tsx
const COLUMNAS_8 = ['id', 'codigo', 'actividad', 'inicio', 'fin', 'ppto', 'avance', 'estado'] as const;
const COLUMNAS_13 = [
  'id', 'codigo', 'actividad', 'rc', 'inicio', 'sem', 'fin',
  'cantidad', 'unidad', 'real', 'teorico', 'restricciones', 'estado',
] as const;
```

(a nivel de módulo) y dentro de `<table>`:

```tsx
<colgroup>
  {(modo13Cols ? COLUMNAS_13 : COLUMNAS_8).map((clave) => (
    <col key={clave} className={`pg-col-${clave}`} />
  ))}
</colgroup>
```

Quita `style={{ width: … }}` de todos los `<th>`. Conserva `textAlign` pasándolo a clases
(`pg-num`, `pg-center`) definidas en la hoja, porque los módulos migrados no llevan estilos inline
nuevos. El `colSpan` de la fila de capítulo sale de `(modo13Cols ? COLUMNAS_13 : COLUMNAS_8).length`.

- [ ] **Paso 4: Implementar el layout** — en `programa-general.css`, dentro de `@layer module`:

```css
  .programa-table-pro {
    table-layout: fixed;
    width: 100%;
  }

  /* Anchos en rem para que escalen con la tipografía; «Actividad» no lleva ancho: absorbe el resto. */
  .programa-table-pro .pg-col-id            { width: 3rem; }
  .programa-table-pro .pg-col-codigo        { width: 4rem; }
  .programa-table-pro .pg-col-inicio,
  .programa-table-pro .pg-col-fin           { width: 5.5rem; }
  .programa-table-pro .pg-col-ppto          { width: 6rem; }
  .programa-table-pro .pg-col-avance        { width: 9rem; }
  .programa-table-pro .pg-col-estado        { width: 7rem; }
  .programa-table-pro .pg-col-rc            { width: 2.5rem; }
  .programa-table-pro .pg-col-sem           { width: 3.5rem; }
  .programa-table-pro .pg-col-cantidad      { width: 4.5rem; }
  .programa-table-pro .pg-col-unidad        { width: 3rem; }
  .programa-table-pro .pg-col-real,
  .programa-table-pro .pg-col-teorico       { width: 4rem; }
  .programa-table-pro .pg-col-restricciones { width: 4.5rem; }

  /* Encabezados: pueden partir en dos líneas; nunca empujan el ancho de la tabla. */
  .programa-table-pro thead th {
    white-space: normal;
    overflow-wrap: anywhere;
    hyphens: auto;
  }

  /* R1.2-2: «Actividad» parte el texto y lo muestra completo. */
  .programa-table-pro .activity-cell-name,
  .programa-table-pro .activity-title,
  .programa-table-pro .activity-subtitle {
    white-space: normal;
    overflow-wrap: anywhere;
    overflow: visible;
    text-overflow: clip;
    min-width: 0;
  }
```

Los valores de ancho son el punto de partida: ajústalos si la prueba del paso 5 lo exige en 13
columnas con el riel expandido, **sin** bajar «Actividad» del mínimo que fija esa prueba. Anota en
el informe los valores finales. Quita de la hoja cualquier `white-space: nowrap`,
`text-overflow: ellipsis` o `-webkit-line-clamp` que aplique a celdas de la tabla.

- [ ] **Paso 5: Prueba de navegador que falla antes y pasa después** — en
  `tests/browser/s05-programa-general-react.spec.mjs`, un `describe` nuevo contra el Docker real:

```js
test.describe('Ronda 1.2 — layout de la tabla (R1.2-1, R1.2-2)', () => {
  for (const tema of ['light', 'dark']) {
    for (const modo of ['8', '13']) {
      for (const riel of ['collapsed', 'expanded']) {
        test(`sin desbordamiento · tema ${tema} · ${modo} columnas · riel ${riel}`, async ({ page }) => {
          await page.setViewportSize({ width: 1180, height: 820 });
          await page.addInitScript(([t, r]) => {
            localStorage.setItem('aia-theme', t);
            localStorage.setItem('aia-sidebar-state', r);
          }, [tema, riel]);
          await page.goto('http://localhost:8081/dev/entrar?u=test.A&p=' + encodeURIComponent('Da Porto'));
          await page.goto('http://localhost:8081/programa-general');
          await page.locator('table.programa-table-pro tbody tr.row-activity').first().waitFor();
          if (modo === '13') await page.getByRole('button', { name: /13 Cols/i }).click();

          const m = await page.evaluate(() => {
            const tabla = document.querySelector('table.programa-table-pro');
            const vista = tabla.parentElement;
            const actividad = tabla.querySelector('col.pg-col-actividad');
            const celdas = [...tabla.querySelectorAll('.activity-title, .activity-subtitle')];
            return {
              tablaAncho: tabla.scrollWidth,
              vistaAncho: vista.clientWidth,
              docAncho: document.documentElement.scrollWidth,
              ventana: window.innerWidth,
              anchoActividad: actividad.getBoundingClientRect().width,
              recortadas: celdas.filter((c) => c.scrollWidth > c.clientWidth + 1).length,
            };
          });
          expect(m.tablaAncho).toBeLessThanOrEqual(m.vistaAncho);
          expect(m.docAncho).toBeLessThanOrEqual(m.ventana);
          expect(m.recortadas).toBe(0);
          expect(m.anchoActividad).toBeGreaterThanOrEqual(modo === '8' ? 280 : 160);
        });
      }
    }
  }
});
```

Antes de escribir la prueba, confirma en `frontend/src/shell/tema.ts` la clave real de
`localStorage` del tema (aquí se asume `aia-theme`) y úsala. La clave del riel `aia-sidebar-state`
la fija la tarea 6; mientras la tarea 6 no esté, los casos `collapsed` pueden fallar: eso es
esperado y se cierra en la tarea 6.

Corre: `npx playwright test tests/browser/s05-programa-general-react.spec.mjs -g "Ronda 1.2 — layout" --workers=1`.
Antes del paso 4: FALLA (`tablaAncho` 1375 > 851). Después: PASA en los casos `expanded`.

- [ ] **Paso 6: Reconstruir el bundle y commit**

```bash
npm --prefix frontend run build; echo "RC build=$?"
git add frontend/src/modules/programa-general public/app tests/browser/s05-programa-general-react.spec.mjs
git commit -m "fix(pg): tabla de ancho fijo con colgroup; Actividad parte el texto; cero desbordamiento en 8 y 13 columnas"
```

---

### Tarea 5: Scroll vertical real en la tabla (R1.2-3, H1)

**Archivos:**
- Modificar: `frontend/src/modules/programa-general/programa-general.css`
- Modificar: `tests/browser/s05-programa-general-react.spec.mjs`

**Interfaces:**
- Produce: `.table-wrapper-pro` es el único contenedor con scroll vertical de la tabla
  (`overflow-y: auto`, alto acotado al espacio que queda en la ventana) y el `<thead>` queda fijo
  arriba (`position: sticky`). La página no hace scroll vertical propio por la tabla.

- [ ] **Paso 1: Prueba que falla**

```js
test('Ronda 1.2 — el scroll vertical de la tabla alcanza la última fila (R1.2-3)', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.goto('http://localhost:8081/dev/entrar?u=test.A&p=' + encodeURIComponent('Da Porto'));
  await page.goto('http://localhost:8081/programa-general');
  const filas = page.locator('table.programa-table-pro tbody tr');
  await filas.first().waitFor();

  const vista = page.locator('.table-wrapper-pro');
  const estilo = await vista.evaluate((el) => {
    const cs = getComputedStyle(el);
    return { oy: cs.overflowY, h: el.clientHeight, sh: el.scrollHeight };
  });
  expect(['auto', 'scroll']).toContain(estilo.oy);
  expect(estilo.sh).toBeGreaterThan(estilo.h);

  await vista.hover();
  await page.mouse.wheel(0, estilo.sh);
  await expect(filas.last()).toBeInViewport();
  await expect(page.locator('table.programa-table-pro thead')).toBeInViewport();
});
```

Corre: `npx playwright test tests/browser/s05-programa-general-react.spec.mjs -g "scroll vertical" --workers=1`.
Esperado antes: FALLA (`overflowY` es `hidden`, o la última fila no entra en la vista).

- [ ] **Paso 2: Implementar** — en `programa-general.css`, dentro de `@layer module`:

```css
  /* R1.2-3 (H1): core.css pone `overflow: hidden` en .aia-grid-shell/.aia-table-shell; aquí la
     vista de la tabla declara los dos ejes, así no hereda el recorte vertical. */
  .programa-general-container .table-wrapper-pro {
    overflow-x: hidden;
    overflow-y: auto;
    max-height: calc(100dvh - var(--pg-offset-tabla, 16rem));
    overscroll-behavior: contain;
  }

  .programa-table-pro thead th {
    position: sticky;
    top: 0;
    z-index: 1;
    background: var(--ds-table-header-bg);
  }
```

`--pg-offset-tabla` es el alto de lo que va encima de la tabla (barra de contexto, toolbar,
señales, filtros). Si no puedes fijarlo con CSS (por ejemplo, con un contenedor flex en columna de
`height: 100dvh` donde la tabla tiene `flex: 1; min-height: 0`), prefiere esa estructura y quita el
`max-height`: es más robusta. La decisión es tuya y va al informe. Revisa también
`.programa-content-area` (hoy `overflow: auto`): no puede quedar un segundo contenedor de scroll
vertical anidado que atrape la rueda antes que la tabla.

- [ ] **Paso 3: Verificar**

Corre el mismo comando del paso 1: PASA. Corre también el `describe` de la tarea 4: sigue en verde.

- [ ] **Paso 4: Reconstruir y commit**

```bash
npm --prefix frontend run build; echo "RC build=$?"
git add frontend/src/modules/programa-general/programa-general.css public/app tests/browser/s05-programa-general-react.spec.mjs
git commit -m "fix(pg): la vista de la tabla es dueña de su scroll vertical, con encabezado fijo"
```

---

### Tarea 6: Riel del shell React como el del legado (R1.2-4, H4, H5, H14)

**Archivos:**
- Modificar: `frontend/src/shell/modoBarraLateral.ts`
- Crear o ampliar: `frontend/src/shell/modoBarraLateral.test.ts`
- Modificar: `frontend/src/shell/AppShell.tsx:77, 239`
- Modificar: `frontend/src/shell/AppShell.test.tsx`
- Crear: `tests/browser/shell-react-paridad-legado.spec.mjs`

**Interfaces:**
- Produce en `modoBarraLateral.ts`:
  - `export const CLAVE_ESTADO_RIEL = 'aia-sidebar-state';` (la misma del legado,
    `public/js/modules/aia_ui/sidebar_navigation.js:41`, para que el estado se comparta entre
    páginas PHP y React)
  - `export function leerEstadoRiel(): 'collapsed' | 'expanded'` — lo guardado si es válido; si no,
    `'collapsed'` (el `initialState` del legado); si `localStorage` lanza, `'collapsed'`.
  - `export function guardarEstadoRiel(estado: 'collapsed' | 'expanded'): void` — escribe en
    `try/catch` y nunca lanza.

- [ ] **Paso 1: Pruebas que fallan** — `modoBarraLateral.test.ts`:

```ts
import { afterEach, describe, expect, it, vi } from 'vitest';
import { CLAVE_ESTADO_RIEL, guardarEstadoRiel, leerEstadoRiel } from './modoBarraLateral';

describe('estado del riel (paridad con el legado)', () => {
  afterEach(() => {
    localStorage.clear();
    vi.restoreAllMocks();
  });

  it('arranca colapsado cuando no hay nada guardado', () => {
    expect(leerEstadoRiel()).toBe('collapsed');
  });

  it('usa la misma clave que el legado y respeta lo guardado', () => {
    localStorage.setItem(CLAVE_ESTADO_RIEL, 'expanded');
    expect(CLAVE_ESTADO_RIEL).toBe('aia-sidebar-state');
    expect(leerEstadoRiel()).toBe('expanded');
  });

  it('ignora valores inválidos', () => {
    localStorage.setItem(CLAVE_ESTADO_RIEL, 'abierto');
    expect(leerEstadoRiel()).toBe('collapsed');
  });

  it('no lanza si localStorage falla', () => {
    vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => { throw new Error('bloqueado'); });
    vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => { throw new Error('bloqueado'); });
    expect(leerEstadoRiel()).toBe('collapsed');
    expect(() => guardarEstadoRiel('expanded')).not.toThrow();
  });
});
```

En `AppShell.test.tsx`, agrega un caso que monte el shell con `localStorage` vacío y compruebe
`data-sidebar-state="collapsed"` en `aside#app-shell-nav`, y otro que haga clic en el botón de
alternar y compruebe `localStorage.getItem('aia-sidebar-state') === 'expanded'`. Usa los helpers de
montaje que ya tiene ese archivo.

- [ ] **Paso 2: Verificar que fallan** —
  `npm --prefix frontend run test -- src/shell/modoBarraLateral.test.ts src/shell/AppShell.test.tsx`.

- [ ] **Paso 3: Implementar** — en `modoBarraLateral.ts`:

```ts
export type EstadoRiel = 'collapsed' | 'expanded';
export const CLAVE_ESTADO_RIEL = 'aia-sidebar-state';

export function leerEstadoRiel(): EstadoRiel {
  try {
    const guardado = window.localStorage.getItem(CLAVE_ESTADO_RIEL);
    return guardado === 'expanded' || guardado === 'collapsed' ? guardado : 'collapsed';
  } catch {
    return 'collapsed';
  }
}

export function guardarEstadoRiel(estado: EstadoRiel): void {
  try {
    window.localStorage.setItem(CLAVE_ESTADO_RIEL, estado);
  } catch {
    /* sin almacenamiento: el riel funciona igual, solo no recuerda */
  }
}
```

En `AppShell.tsx` cambia `useState(false)` por
`useState(() => leerEstadoRiel() === 'collapsed')` y, en `alAlternarEstado`, guarda el nuevo valor:

```tsx
alAlternarEstado={() =>
  setColapsado((valor) => {
    const siguiente = !valor;
    guardarEstadoRiel(siguiente ? 'collapsed' : 'expanded');
    return siguiente;
  })
}
```

En modo flotante (`flotante`, menos de 1180px) el comportamiento de cajón no cambia.

- [ ] **Paso 4: Ítem activo legible y apariencia del legado**

Con la tarea 3 hecha, mide de nuevo el ítem activo (`aria-current="page"`) en claro y oscuro. Si
su contraste de texto contra su fondo es menor que 4.5:1, la causa está en qué reglas de
`shell-sidebar.css` aplican al markup React. Compara el markup de `NavegacionLateral` con el que
emite `DesignSystemComponent::navigation()` para el legado y **alinea el markup React con el del
legado** (clases y atributos), en vez de agregar reglas nuevas o estilos inline. Quita el estilo
inline compensatorio de `AppShell.tsx:96-108` (H5) si deja de hacer falta.

- [ ] **Paso 5: Prueba de navegador de paridad** — `tests/browser/shell-react-paridad-legado.spec.mjs`:

```js
import { expect, test } from '@playwright/test';

const ENTRAR = 'http://localhost:8081/dev/entrar?u=test.A&p=' + encodeURIComponent('Da Porto');

async function medirRiel(page, ruta) {
  await page.goto(ruta);
  const riel = page.locator('aside.aia-navigation--sidebar');
  await riel.waitFor();
  return riel.evaluate((el) => {
    const activo = el.querySelector('[aria-current="page"]');
    const cs = activo ? getComputedStyle(activo) : null;
    return {
      estado: el.getAttribute('data-sidebar-state'),
      ancho: Math.round(el.getBoundingClientRect().width),
      etiquetas: [...el.querySelectorAll('a, button')]
        .map((n) => (n.getAttribute('aria-label') || n.textContent || '').trim())
        .filter(Boolean),
      colorActivo: cs?.color ?? null,
    };
  });
}

for (const tema of ['light', 'dark']) {
  test(`el riel React se comporta como el del legado · ${tema}`, async ({ page }) => {
    await page.setViewportSize({ width: 1180, height: 820 });
    await page.addInitScript((t) => localStorage.setItem('aia-theme', t), tema);
    await page.goto(ENTRAR);
    await page.evaluate(() => localStorage.removeItem('aia-sidebar-state'));

    const legado = await medirRiel(page, 'http://localhost:8081/programacion-semanal');
    const react = await medirRiel(page, 'http://localhost:8081/programa-general');

    expect(legado.estado).toBe('collapsed');
    expect(react.estado).toBe('collapsed');
    expect(Math.abs(react.ancho - legado.ancho)).toBeLessThanOrEqual(2);
    expect(react.etiquetas).toEqual(legado.etiquetas);
  });
}

test('el estado del riel se comparte entre legado y React', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.goto(ENTRAR);
  await page.goto('http://localhost:8081/programacion-semanal');
  await page.evaluate(() => localStorage.setItem('aia-sidebar-state', 'expanded'));
  await page.goto('http://localhost:8081/programa-general');
  await expect(page.locator('aside.aia-navigation--sidebar')).toHaveAttribute('data-sidebar-state', 'expanded');
});
```

Si `etiquetas` difiere por el ítem activo propio de cada página (el legado nunca oculta el módulo en
el que estás), compara contra el legado **en la misma ruta lógica** o excluye solo ese ítem, y
explícalo en un comentario de la prueba. La comprobación de contraste del ítem activo va en
`tests/browser/programa-general-design-system.mjs` (tarea 8), que ya corre `axe`.

Corre: `npx playwright test tests/browser/shell-react-paridad-legado.spec.mjs --workers=1` → PASA.
Corre también el `describe` de la tarea 4: ahora pasan los casos `collapsed`.

- [ ] **Paso 6: Reconstruir y commit**

```bash
npm --prefix frontend run test; echo "RC vitest=$?"
npm --prefix frontend run build; echo "RC build=$?"
git add frontend/src/shell public/app tests/browser/shell-react-paridad-legado.spec.mjs
git commit -m "feat(shell): riel React colapsado por defecto y con el estado compartido con el legado"
```

---

### Tarea 7: La semana sale del riel a la barra de contexto (R1.2-4, H14)

**Archivos:**
- Crear: `frontend/src/shell/BarraContexto.tsx`, `frontend/src/shell/BarraContexto.test.tsx`
- Crear: `frontend/src/shell/DialogosSemana.tsx`
- Modificar: `frontend/src/shell/AppShell.tsx`, `frontend/src/shell/NavegacionLateral.tsx`
- Borrar: `frontend/src/shell/ContextoSemana.tsx`, `frontend/src/shell/ContextoSemana.test.tsx`
  (sus casos de comportamiento se mudan a `BarraContexto.test.tsx`)
- Modificar: `tests/browser/shell-react-paridad-legado.spec.mjs`

**Interfaces:**
- Consume: `useContextoSemana(csrfToken, recargar)` (sin cambios: `seleccionar`, `crear`,
  `eliminarUltima`, `seleccionando`, `creando`, `eliminando`, `error`), `SemanaActiva` de
  `lib/api/esquemas/contexto`, y `sesion.project.name`.
- Produce:
  - `BarraContexto({ proyecto, modulo, semana, csrfToken, recargar })`: renderiza el mismo markup
    que el legado (`views/partials/shell_sidebar.php:153-186`) con las mismas clases:
    `div.context-bar#shellContextBar` › `span#ctxProyecto` / `span#ctxModulo` / `div.aia-menu.context-week-menu`
    con `button.context-week-chip#ctxSemanaBadge` (`aria-haspopup="menu"`, `aria-expanded`) y un
    panel `role="menu"` con un `menuitem` por semana (`Semana N` + `<small>Del … al …</small>`,
    `aria-current="true"` en la vigente). Seleccionar llama `seleccionar(N)`. Solo se pinta si
    `semana !== null`.
  - `DialogosSemana({ semana, csrfToken, recargar, abierto, alCerrar })`: los diálogos de crear y
    eliminar con el markup del legado (`dialog.aia-modal-surface.shell-week-dialog`, sección
    `shell_sidebar.php:187-230`), respetando `semana.actions.create` y `semana.actions.deleteLast`.
  - El riel ya no recibe `contextoSemana`. La acción «Semanas del Proyecto» del riel (ítem de
    acción que llega de `ShellNavigationService`) abre `DialogosSemana`, como en el legado.
  - `modulo` sale del título de la ruta activa que ya calcula `useTituloDocumento`.

- [ ] **Paso 1: Pruebas que fallan** — `BarraContexto.test.tsx` (Testing Library):

```tsx
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { BarraContexto } from './BarraContexto';

const semana = {
  current: 2,
  options: [
    { number: 1, startsOn: '2026-08-18', endsOn: '2026-08-24' },
    { number: 2, startsOn: '2026-08-25', endsOn: '2026-08-31' },
  ],
  actions: { select: true, create: true, deleteLast: true },
};

vi.mock('./useContextoSemana', () => ({
  useContextoSemana: () => ({
    seleccionar: mockSeleccionar, crear: vi.fn(), eliminarUltima: vi.fn(),
    seleccionando: false, creando: false, eliminando: false, error: null,
  }),
}));
const mockSeleccionar = vi.fn();

describe('BarraContexto (paridad con .context-bar del legado)', () => {
  it('muestra proyecto, módulo y el chip de la semana vigente', () => {
    render(<BarraContexto proyecto="Da Porto" modulo="Programa General" semana={semana} csrfToken="t" recargar={vi.fn()} />);
    expect(document.querySelector('#shellContextBar.context-bar')).not.toBeNull();
    expect(screen.getByText('Da Porto')).toBeInTheDocument();
    expect(screen.getByText('Programa General')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Semana 2/ })).toHaveAttribute('aria-haspopup', 'menu');
  });

  it('abre el menú y cambia de semana', async () => {
    render(<BarraContexto proyecto="Da Porto" modulo="Programa General" semana={semana} csrfToken="t" recargar={vi.fn()} />);
    await userEvent.click(screen.getByRole('button', { name: /Semana 2/ }));
    expect(screen.getByRole('menuitem', { name: /Semana 2/ })).toHaveAttribute('aria-current', 'true');
    await userEvent.click(screen.getByRole('menuitem', { name: /Semana 1/ }));
    expect(mockSeleccionar).toHaveBeenCalledWith(1);
  });

  it('no se pinta sin semana', () => {
    const { container } = render(<BarraContexto proyecto="P" modulo="M" semana={null} csrfToken="t" recargar={vi.fn()} />);
    expect(container.querySelector('.context-week-chip')).toBeNull();
  });
});
```

Ajusta la forma exacta de `SemanaActiva` a la de `lib/api/esquemas/contexto.ts` si difiere. Muda
aquí también los casos de crear y eliminar que hoy prueba `ContextoSemana.test.tsx` (misma
aserción, nuevo componente `DialogosSemana`).

- [ ] **Paso 2: Verificar que fallan** —
  `npm --prefix frontend run test -- src/shell/BarraContexto.test.tsx` → FALLA (no existe).

- [ ] **Paso 3: Implementar** `BarraContexto.tsx` y `DialogosSemana.tsx` con el markup y las
  clases del legado citadas arriba, para que `shell-sidebar.css` y los adaptadores del legado los
  estilicen sin reglas nuevas. Menú accesible: `Escape` cierra y devuelve el foco al chip; flechas
  arriba y abajo recorren los `menuitem`. Monta `BarraContexto` en `AppShell.tsx` dentro de
  `<main>`, antes del `<Outlet />`, y `DialogosSemana` una sola vez en el shell. Borra
  `ContextoSemana.tsx`, su test y la prop `contextoSemana` de `NavegacionLateral`. Si alguna clase
  del legado no se aplica en React porque su selector exige `body.aia-shell--sidebar` u otro
  ancestro, alinea el ancestro en el markup React, no dupliques la regla.

- [ ] **Paso 4: Prueba de navegador** — agrega a `shell-react-paridad-legado.spec.mjs`:

```js
test('la semana vive en la barra de contexto, como en el legado', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.goto(ENTRAR);
  await page.goto('http://localhost:8081/programa-general');
  await expect(page.locator('aside.aia-navigation--sidebar select')).toHaveCount(0);
  await expect(page.locator('aside.aia-navigation--sidebar .aia-sidebar__week')).toHaveCount(0);
  const chip = page.locator('#shellContextBar .context-week-chip');
  await expect(chip).toBeVisible();
  await chip.click();
  await expect(page.getByRole('menu')).toBeVisible();
  await page.keyboard.press('Escape');
  await expect(chip).toBeFocused();
});
```

Además, verifica en el navegador que el cambio de semana desde el chip recarga Programa General con
la semana nueva y que crear o eliminar semana desde «Semanas del Proyecto» funciona con `test.A`
(rol permitido) y **no aparece** con `test.V` (rol sin la acción). Anota ambas cosas en el informe.
Crear o eliminar una semana en la base de desarrollo es una escritura: hazlo solo si la semana que
creas la eliminas después, y reporta las dos operaciones.

- [ ] **Paso 5: Verificar y commit**

```bash
npm --prefix frontend run test; echo "RC vitest=$?"
npm --prefix frontend run typecheck; echo "RC typecheck=$?"
npm --prefix frontend run build; echo "RC build=$?"
npx playwright test tests/browser/shell-react-paridad-legado.spec.mjs --workers=1; echo "RC paridad=$?"
git add frontend/src/shell public/app tests/browser/shell-react-paridad-legado.spec.mjs
git commit -m "feat(shell): la semana pasa del riel a la barra de contexto con el markup del legado"
```

---

### Tarea 8: Pruebas de CI contra el DOM React, sin rebajarlas (H6, H7, V1, V2, V3)

**Archivos:**
- Crear: `e2e/support/programa-general-react.mjs`
- Modificar: `e2e/tests/workflows/pg-interactions.spec.mjs`
- Modificar: `tests/browser/design-system-runtime-budget.mjs:106-120`
- Modificar: `tests/browser/programa-general-design-system.mjs`
- Modificar, solo si es la causa: `tests/browser/full-app-flow.spec.mjs` o sus helpers

**Interfaces:**
- Produce en `e2e/support/programa-general-react.mjs`:
  - `esperarTablaPg(page): Promise<void>` — espera `table.programa-table-pro tbody tr.row-activity`.
  - `contarFilasPg(page): Promise<number>` — filas `tr.row-activity`.
  - `abrirActividadPg(page, uniqueId: number): Promise<void>` — clic en `tr[data-unique-id="<id>"]`
    (agrega ese atributo en `ProgramaTable.tsx` si no está: es un gancho de prueba legítimo, no una
    imitación de Handsontable) y espera el Drawer.
  - `editarCampoDrawerPg(page, etiqueta: string, valor: string): Promise<void>` y
    `guardarDrawerPg(page): Promise<void>` — edita por la etiqueta accesible del campo y guarda con
    el botón «Guardar cambios».
  - `leerCampoFilaPg(page, uniqueId: number, columna: string): Promise<string>`.

- [ ] **Paso 1: Reproducir cada falla antes de tocar nada** (`systematic-debugging`)

```bash
npx playwright test --config=e2e/playwright.config.mjs e2e/tests/workflows/pg-interactions.spec.mjs --workers=1; echo "RC pg=$?"
npm run test:runtime-budget:measure; echo "RC budget=$?"
npm run test:a11y:pilot; echo "RC a11y=$?"
npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1; echo "RC flow=$?"
```

Para cada falla anota la aserción exacta y su causa (cierra V1 y V2 con evidencia). No sigas al
paso 2 con una causa supuesta.

- [ ] **Paso 2: `pg-interactions.spec.mjs` contra React (H6)** — reemplaza cada uso de
  `window.PGHotModule`, `getHotInstance`, `hot.*` y de `e2e/support/handsontable.mjs` por los
  helpers de arriba, **conservando cada aserción de negocio**:
  - Admin (línea 68): edita un campo por el Drawer, guarda, **recarga la página** y comprueba que
    el valor persiste (UI→API→BD), exporta CSV y comprueba el contenido, y abre el cajón LPS.
  - Residente (línea 183): lo mismo con los campos que su rol permite.
  - Roles de solo lectura (línea 248): el Drawer no permite editar (campos deshabilitados o sin
    botón de guardar) **y** un `POST` manipulado al API de guardado responde con negación
    (403/422 según el contrato de `GeneralApiController`). Para el rol sin permiso de ver
    (Subcontratista, `canView:false`) la página responde 200 por el host SPA: la prueba debe
    comprobar que **el API de la lista niega los datos** y que la interfaz muestra el estado de
    acceso denegado. Esto cierra V3: la negación se prueba en el dato, no solo en el código HTTP.

- [ ] **Paso 3: Presupuesto de runtime (H7)** — en `design-system-runtime-budget.mjs`, la
  superficie PG mide el arranque de la tabla React (desde `goto` hasta la primera
  `tr.row-activity` visible) y una interacción equivalente al menú de filtro de Handsontable: clic
  en un chip de estado de la barra de señales y espera de la tabla filtrada. Conserva el umbral del
  presupuesto: si React no lo cumple, es un hallazgo del informe, **no** se sube el umbral.

- [ ] **Paso 4: `programa-general-design-system.mjs` (V1)** — corrige lo que el paso 1 mostró.
  Añade la comprobación de contraste del ítem activo del riel (4.5:1) si `axe` no la cubre ya.

- [ ] **Paso 5: `full-app-flow` (V2)** — corrige solo si el paso 1 mostró que la causa es PG o el
  shell React de esta ronda. Si la causa es ajena, anótala en el informe y no la toques.

- [ ] **Paso 6: Verificar y commit**

Repite los cuatro comandos del paso 1: cuatro `RC=0`.

```bash
git add e2e tests/browser frontend/src/modules/programa-general/components/ProgramaTable.tsx public/app
git commit -m "test(pg): persistencia, RBAC y presupuesto de runtime contra la tabla React, sin rebajar aserciones"
```

---

### Tarea 9: Capturas de referencia candidatas — PARA hasta el visto de Felipe (H8)

**Archivos:**
- Modificar: `tests/browser/programa-general.visual.mjs` (solo si su recorrido depende de
  Handsontable)
- Candidatas en: `tests/browser/__screenshots__/programa-general.visual.mjs/` (sin commit)

- [ ] **Paso 1:** adapta el recorrido de `programa-general.visual.mjs` al DOM React si depende de
  Handsontable (H8), sin cambiar escenarios, temas ni viewports del manifiesto
  `docs/design-system/manifests/programa-general.json`.
- [ ] **Paso 2:** genera las candidatas con `--update-snapshots` **en un directorio aparte** o sin
  commitearlas, y arma una comparación lado a lado (captura vieja y candidata) por escenario y tema.
- [ ] **Paso 3: `BLOCKED` — visto de Felipe.** `AGENTS.md`: regenerar capturas de referencia exige
  su aprobación explícita. Deja las comparaciones en
  `docs/superpowers/evidence/s05-ronda-1-2/goldens-candidatas/` y sigue con las demás tareas. Solo
  con el visto de Felipe se commitean las capturas y `test:visual:pilot` pasa a ser parte de la
  verificación final.

---

### Tarea 10: Verificación final, evidencia e informe

**Archivos:**
- Crear: `docs/superpowers/evidence/s05-ronda-1-2/*.png`
- Crear: `docs/superpowers/plans/2026-09-24-s05-ronda-1-2-informe.md` (plantilla
  `contratos/plantillas/informe-sprint.md` si existe; si no, las secciones de abajo)

- [ ] **Paso 1: Suite local completa, cada código en su propia línea**

```bash
npm --prefix frontend run typecheck; echo "RC typecheck=$?"
npm --prefix frontend run test; echo "RC vitest=$?"
npm run test:design-system:static; echo "RC static=$?"
docker compose exec -T app php scripts/run-php-tests.php --nivel=http; echo "RC php=$?"
docker compose exec -T app vendor/bin/phpstan analyse src admin/src --memory-limit=1G; echo "RC phpstan=$?"
npx playwright test tests/browser/s05-programa-general-react.spec.mjs tests/browser/shell-react-paridad-legado.spec.mjs --workers=1; echo "RC browser=$?"
npm run test:a11y:pilot; echo "RC a11y=$?"
npm run test:hue:pilot; echo "RC hue=$?"
npm run test:runtime-budget:measure; echo "RC budget=$?"
npx playwright test --config=e2e/playwright.config.mjs e2e/tests/workflows/pg-interactions.spec.mjs --workers=1; echo "RC pg=$?"
npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1; echo "RC flow=$?"
```

- [ ] **Paso 2: El shell en los demás módulos React.** El riel y la barra de contexto son
  compartidos: recorre cada ruta React con shell de `frontend/src/shell/rutas.tsx` a 1180×820 en
  claro y oscuro, con riel colapsado y expandido, y confirma que no hay desbordamiento horizontal,
  que el chip de semana aparece donde el módulo tiene semana y que nada se rompió. Anota la lista de
  rutas recorridas y el resultado de cada una.

- [ ] **Paso 3: Capturas finales** (después del último commit), a 1180×820, en claro y oscuro:
  tabla con 8 columnas; tabla con 13 columnas; tabla desplazada hasta la última fila; riel
  colapsado; riel expandido; menú de semana abierto; Drawer abierto. Actualiza
  `scripts/generate_s05_live_screenshots.mjs` para que las genere en
  `docs/superpowers/evidence/s05-ronda-1-2/` y anota el SHA con el que se tomaron.

- [ ] **Paso 4: Revisión independiente** (`requesting-code-review`) con un revisor de contexto
  limpio del mismo arnés, más `security-reviewer` sobre la tarea 1 y la parte de RBAC de la
  tarea 8. Corrige lo que devuelvan y repite el paso 1.

- [ ] **Paso 5: Informe de sprint** con estas secciones: Partida (salidas de la tarea 0);
  qué se hizo por tarea con su commit; decisiones de código tomadas (anchos finales, estructura del
  scroll, alineación del markup del riel); la salida real del paso 1; las rutas del paso 2; las
  capturas del paso 3 con su SHA; hallazgos (tokens de rol que falten, umbrales de presupuesto no
  cumplidos, causas ajenas de `full-app-flow`); `BLOCKED` pendientes (tarea 9 hasta el visto de
  Felipe); y la **condición de hecho del PR, escrita antes de correr el CI**: todas las `G_*` del
  paso «Summarize gate results» en verde en los dos temas.

- [ ] **Paso 6: Commit del cierre**

```bash
git add docs/superpowers/evidence/s05-ronda-1-2 docs/superpowers/plans/2026-09-24-s05-ronda-1-2-informe.md scripts/generate_s05_live_screenshots.mjs
git commit -m "docs(s05): informe y evidencia de la ronda 1.2"
LPS_CODE_ROOT="/Volumes/Crucial X6/Developer/lps-aia" docker compose up -d app
```

Sin push. El informe queda listo para que Claude cierre con Felipe (paso 08).

---

## Cobertura de la spec

| Requisito o hallazgo | Tarea |
|---|---|
| R1.2-1 cero desbordamiento (8 y 13, riel colapsado y expandido, dos temas) | 4, 6, 10 |
| R1.2-2 «Actividad» completa con salto de línea | 4 |
| R1.2-3 scroll vertical hasta la última fila | 5 |
| R1.2-4 riel y semana como el legado, en todo el shell React | 6, 7, 10 |
| H3 contador | 2 |
| H10 / V5 puerta de desarrollo | 1 |
| H12 tokens globales pisados | 3 |
| H13 nada de imitar Handsontable | Restricciones globales, 8 |
| H6, H7, V1, V2, V3 pruebas de CI | 8 |
| H8 capturas de referencia (con visto de Felipe) | 9 |
| Condición de hecho y evidencia | 10 |
