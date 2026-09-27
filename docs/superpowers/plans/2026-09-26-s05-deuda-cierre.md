---
capa: fuente
tipo: plan
estado: abierto
fecha: 2026-09-26
areas: [lps, design-system, qa, proceso]
ejecutor: codex
spec: docs/superpowers/specs/2026-09-26-s05-deuda-cierre-design.md
fuente: docs/superpowers/plans/2026-09-26-s05-deuda-cierre.md
resumen: "Plan corto para la deuda del cierre de S05 ronda 1.2: prueba del tinte crítico con valor fijo, contraste de los íconos del riel en claro, columna de inicio relativo, áreas del frente TNP, pase de veracidad e ingest en la wiki."
---

# S05 — Deuda del cierre de la ronda 1.2 — Plan de implementación

> **Para agentes:** SUB-SKILL OBLIGATORIA: `superpowers:subagent-driven-development` o
> `superpowers:executing-plans`; `superpowers:test-driven-development` en cada tarea de código;
> `superpowers:systematic-debugging` ante cualquier falla; `superpowers:requesting-code-review`
> para el revisor independiente; `superpowers:verification-before-completion` y
> `superpowers:finishing-a-development-branch` al cierre. Pasos con casillas (`- [ ]`).

**Objetivo:** cerrar las cinco deudas que dejó la ronda 1.2 de S05 y registrar sus aprendizajes en
la wiki, sin regresiones y con el CI en verde.

**Arquitectura:** cambios pequeños y separados. Una aserción más en una prueba de navegador, un
posible ajuste de CSS del shell, un formateador puro nuevo para la columna de inicio relativo
(usado por la tabla; el CSV pasa a exportar el número crudo), metadatos de dos documentos y dos
operaciones de wiki (`veracidad` e `ingest`).

**Tech stack:** React 19, TypeScript, Vitest, Playwright, Node (`node --test`), PHP 8.3 en Docker.

**Spec:** `docs/superpowers/specs/2026-09-26-s05-deuda-cierre-design.md` (versión 1.1, aprobada por
Felipe el 2026-09-26). Léela entera antes de la tarea 0; el punto 3 se corrigió en la 1.1.

## Restricciones globales

- **No antes del 2026-09-30** (reinicio semanal del uso de Codex).
- Worktree propio desde `origin/main`; nunca el checkout principal. `.env` enlazado, no copiado.
- **Ninguna prueba se debilita, ningún umbral se sube.** Si una aserción no se puede conservar,
  `BLOCKED` con la razón.
- Módulos migrados: sin hex, sin estilos inline nuevos, sin variantes locales de tokens.
- Cero DDL, DML, migraciones o cambios de RLS y permisos.
- Sin push a `main`, sin merge, sin deploy. Push solo a la rama de este plan.
- La wiki (`memoria/`) la escribe el asistente siguiendo `docs/wiki-operacion.md`; `docs/` no se
  toca desde la wiki salvo los dos documentos TNP de la tarea 4, cuyos cambios son solo de
  frontmatter.
- **Condición de hecho:** `npm run test:wiki` en `RC=0`; las pruebas tocadas en verde en local en
  ambos temas; y en el PR, las 13 variables `G_*` del paso «Summarize gate results» en verde en
  ambos temas.

## Mapa de archivos

```text
tests/browser/design-system-body-canvas-dark.mjs                   (tarea 1: valor fijo del tinte)
tests/browser/shell-react-paridad-legado.spec.mjs                  (tarea 2: contraste de íconos en claro)
public/css/design-system/adapters/shell-sidebar.css                 (tarea 2: solo si el legado se ve mejor)
frontend/src/modules/programa-general/domain/inicioRelativo.ts      (tarea 3: nuevo)
frontend/src/modules/programa-general/domain/inicioRelativo.test.ts (tarea 3: nuevo)
frontend/src/modules/programa-general/components/ProgramaTable.tsx  (tarea 3)
frontend/src/modules/programa-general/components/ProgramaTable.test.tsx (tarea 3)
frontend/src/modules/programa-general/domain/exportarCsv.ts         (tarea 3)
frontend/src/modules/programa-general/domain/exportarCsv.test.ts    (tarea 3: nuevo si no existe)
public/app/**                                                        (tarea 3: bundle)
docs/superpowers/specs/2026-09-24-calificacion-tnp-semana-confirmada-design.md (tarea 4: frontmatter)
docs/superpowers/plans/2026-09-24-calificacion-tnp-semana-confirmada.md        (tarea 4: frontmatter)
memoria/**                                                           (tareas 5 y 6)
docs/superpowers/plans/2026-09-26-s05-deuda-cierre-informe.md       (tarea 7)
```

---

### Tarea 0: Worktree y línea de partida

- [ ] **Paso 1:**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia"
git fetch origin
git worktree add .claude/worktrees/s05-deuda-cierre -b codex/s05-deuda-cierre origin/main
cd .claude/worktrees/s05-deuda-cierre
ln -s ~/Developer/lps-aia/.env .env && test -f .env && echo "env OK"
npm --prefix frontend ci
docker compose exec -T app composer install
```

Si `test -f .env` falla, `BLOCKED` (el enlace apunta a la nada; ver `CLAUDE.md`).

- [ ] **Paso 2: línea de partida**, cada código en su propia línea:

```bash
npm --prefix frontend run typecheck; echo "RC typecheck=$?"
npm --prefix frontend run test; echo "RC vitest=$?"
npm run test:wiki > /tmp/deuda-wiki-partida.log 2>&1; echo "RC wiki=$?"
grep -E '^(FUENTE|AREA|FRONTMATTER|VERACIDAD)' /tmp/deuda-wiki-partida.log
```

Esperado: typecheck y Vitest en 0; wiki en 1 con los hallazgos TNP y `VERACIDAD`. Anótalo en el
informe.

---

### Tarea 1: Prueba del tinte crítico con valor fijo en oscuro (deuda 1)

**Archivos:** Modificar `tests/browser/design-system-body-canvas-dark.mjs` (bloque de
`EXPECTED_STATE_TOKEN` y comparación de `colors`, hoy en las líneas ~55-131).

**Interfaces:** ninguna nueva.

- [ ] **Paso 1: agregar la aserción literal.** En la entrada de `/programa-general` de
  `EXPECTED_STATE_TOKEN`, agrega `darkValue: 'rgb(67, 20, 20)'` (es `#431414`, el valor de
  `--ds-state-tint-red` en oscuro: `public/css/tokens.css:411`). Dentro de `if (colors) { … }`,
  **después** de la comparación actual, agrega:

```js
            if (stateToken.darkValue) {
              expect.soft(
                colors.expected,
                `${route}: ${stateToken.token} debe resolver en oscuro a ${stateToken.darkValue}; ` +
                  'si resuelve a otro valor, una regla perdió su condición de tema',
              ).toBe(stateToken.darkValue);
            }
```

No quites la comparación existente (`colors.actual` contra `colors.expected`).

- [ ] **Paso 2: comprobar que la aserción detecta el defecto.** Cambia temporalmente, solo en tu
  árbol y sin commit, `--ds-state-tint-red` en oscuro de `public/css/tokens.css:411` por otro
  valor. Corre la prueba y confirma que falla por la aserción nueva. Revierte el cambio de
  `tokens.css` con `git checkout -- public/css/tokens.css`.

- [ ] **Paso 3: verde.** Con el `app` sirviendo tu worktree
  (`LPS_CODE_ROOT="$(pwd)" docker compose up -d app`):

```bash
npx playwright test tests/browser/design-system-body-canvas-dark.mjs --workers=1; echo "RC=$?"
```

Esperado: `RC=0`.

- [ ] **Paso 4: commit.**

```bash
git add tests/browser/design-system-body-canvas-dark.mjs
git commit -m "test(pg): fijar el valor oscuro del tinte crítico de «Atrasada»"
```

---

### Tarea 2: Contraste de los íconos inactivos del riel en tema claro (deuda 2)

**Archivos:** Modificar `tests/browser/shell-react-paridad-legado.spec.mjs`; modificar
`public/css/design-system/adapters/shell-sidebar.css` **solo** si el paso 2 lo justifica.

**Interfaces:** ninguna nueva.

- [ ] **Paso 1: prueba que mide el contraste en React y en el legado.** Agrega al final del spec:

```js
function luminancia([r, g, b]) {
  const canal = (c) => {
    const s = c / 255;
    return s <= 0.03928 ? s / 12.92 : ((s + 0.055) / 1.055) ** 2.4;
  };
  return 0.2126 * canal(r) + 0.7152 * canal(g) + 0.0722 * canal(b);
}
function contraste(a, b) {
  const [l1, l2] = [luminancia(a), luminancia(b)].sort((x, y) => y - x);
  return (l1 + 0.05) / (l2 + 0.05);
}
async function contrasteIconosInactivos(page, ruta) {
  await page.goto(ruta);
  const riel = page.locator('aside.aia-navigation--sidebar');
  await riel.waitFor();
  const pares = await riel.evaluate((el) => {
    const rgb = (v) => (v.match(/\d+(\.\d+)?/g) || []).slice(0, 3).map(Number);
    const fondoDe = (n) => {
      for (let x = n; x; x = x.parentElement) {
        const bg = getComputedStyle(x).backgroundColor;
        if (bg && !/rgba?\(0, 0, 0, 0\)|transparent/.test(bg)) return rgb(bg);
      }
      return [255, 255, 255];
    };
    return [...el.querySelectorAll('a:not([aria-current]) svg, button:not([aria-current]) svg')]
      .filter((svg) => svg.getBoundingClientRect().width > 0)
      .map((svg) => {
        const cs = getComputedStyle(svg);
        const color = cs.stroke && cs.stroke !== 'none' ? cs.stroke : cs.color;
        return { color: rgb(color), fondo: fondoDe(svg.parentElement) };
      });
  });
  return pares.map(({ color, fondo }) => contraste(color, fondo));
}

test('íconos inactivos del riel en claro: contraste de React frente al legado', async ({ page }) => {
  await page.setViewportSize({ width: 1180, height: 820 });
  await page.addInitScript(() => localStorage.setItem('aia-theme', 'light'));
  await page.goto(ENTRAR);
  const legado = await contrasteIconosInactivos(page, 'http://localhost:8081/programacion-semanal');
  const react = await contrasteIconosInactivos(page, 'http://localhost:8081/programa-general');
  test.info().annotations.push({
    type: 'contraste',
    description: `legado min=${Math.min(...legado).toFixed(2)} · react min=${Math.min(...react).toFixed(2)}`,
  });
  expect(react.length).toBeGreaterThan(0);
  expect(Math.min(...react)).toBeGreaterThanOrEqual(Math.min(3, Math.min(...legado)));
});
```

`ENTRAR` ya existe en ese spec. Si el color del ícono sale de un atributo SVG y no de CSS, ajusta
solo la lectura de `color` para que refleje lo que se pinta, y explícalo en un comentario.

- [ ] **Paso 2: correr y decidir.**

```bash
npx playwright test tests/browser/shell-react-paridad-legado.spec.mjs -g "íconos inactivos" --workers=1 --reporter=list; echo "RC=$?"
```

Lee la anotación `contraste`.
- Si **React ≥ 3:1**, o React ≥ legado y el legado < 3:1: no hay cambio de producto. Si el
  legado < 3:1, anótalo en el informe como hallazgo del design system para su propio frente
  (no se corrige aquí).
- Si **React < legado**: el markup o las clases de React difieren del legado. Alinea el markup
  React con el del legado en `frontend/src/shell/navegacion/BarraLateral.tsx` antes de tocar CSS.
  Solo si con el markup igual sigue por debajo, ajusta `shell-sidebar.css` con un token de
  navegación existente (`--ds-active-nav-text-muted` o equivalente), sin hex. Si tocas
  `shell-sidebar.css`, afecta también al legado: corre `npm run test:design-system:static` y el
  grupo `G_LABORATORY_GATES` en claro.

- [ ] **Paso 3: verde y commit.** Repite el comando del paso 2 hasta `RC=0`.

```bash
npm --prefix frontend run build; echo "RC build=$?"
git add tests/browser/shell-react-paridad-legado.spec.mjs frontend/src/shell public/css/design-system/adapters public/app
git commit -m "test(shell): contraste de íconos inactivos del riel en claro frente al legado"
```

---

### Tarea 3: Columna de inicio relativo y CSV con el número crudo (deuda 3)

**Archivos:**
- Crear: `frontend/src/modules/programa-general/domain/inicioRelativo.ts` y su `.test.ts`
- Modificar: `ProgramaTable.tsx:63` (encabezado) y `:171-175` (celda); `ProgramaTable.test.tsx:242, 453`
- Modificar: `domain/exportarCsv.ts:57`; su prueba

**Interfaces:**
- Produce: `export function formatearInicioRelativo(semanas: number | null | undefined): string`
  - `null`/`undefined`/no finito → `'–'`
  - `0` → `'Esta sem'`
  - negativo `n` → `` `Hace ${Math.abs(n)} sem` ``
  - positivo `n` → `` `En ${n} sem` ``
  - redondea con `Math.round` antes de formatear (el legado usa `Math.round`, `hot.js:680`).

- [ ] **Paso 1: prueba que falla.**

```ts
// frontend/src/modules/programa-general/domain/inicioRelativo.test.ts
import { describe, expect, it } from 'vitest';
import { formatearInicioRelativo } from './inicioRelativo';

describe('formatearInicioRelativo (desfase respecto de la semana vigente)', () => {
  it('sin valor muestra un guion', () => {
    expect(formatearInicioRelativo(null)).toBe('–');
    expect(formatearInicioRelativo(undefined)).toBe('–');
    expect(formatearInicioRelativo(Number.NaN)).toBe('–');
  });
  it('cero es esta semana', () => {
    expect(formatearInicioRelativo(0)).toBe('Esta sem');
  });
  it('negativo es hace N semanas', () => {
    expect(formatearInicioRelativo(-9)).toBe('Hace 9 sem');
    expect(formatearInicioRelativo(-1)).toBe('Hace 1 sem');
  });
  it('positivo es en N semanas', () => {
    expect(formatearInicioRelativo(3)).toBe('En 3 sem');
  });
  it('redondea como el legado', () => {
    expect(formatearInicioRelativo(2.6)).toBe('En 3 sem');
    expect(formatearInicioRelativo(-0.4)).toBe('Esta sem');
  });
});
```

Corre `npm --prefix frontend run test -- src/modules/programa-general/domain/inicioRelativo.test.ts`:
FALLA (no existe el módulo).

- [ ] **Paso 2: implementación.**

```ts
// frontend/src/modules/programa-general/domain/inicioRelativo.ts
/**
 * `Semanas_Inicio` es un desfase respecto de la semana vigente, no un número de semana
 * (`src/Legacy/modificar_sem_estado.php:50`, `pg_calculate_week_offset`). Spec S05-DEUDA 1.1, punto 3.
 */
export function formatearInicioRelativo(semanas: number | null | undefined): string {
  if (semanas === null || semanas === undefined || !Number.isFinite(semanas)) return '–';
  const n = Math.round(semanas);
  if (n === 0) return 'Esta sem'; // también cubre -0
  return n < 0 ? `Hace ${Math.abs(n)} sem` : `En ${n} sem`;
}
```

Corre la prueba del paso 1: PASA.

- [ ] **Paso 3: la tabla usa el formateador.** En `ProgramaTable.tsx`:
  - encabezado (`:63`): `<th aria-label="Inicio relativo a la semana vigente" title="Inicio relativo a la semana vigente">INICIO REL.</th>`
  - celda (`:171-175`): reemplaza el ternario por
    `formatearInicioRelativo(act.Semanas_Inicio)` y pon la fecha real en el título de la celda:
    `<span className="cell-date-value" title={act.Fecha_Inicio ? formatearFechaObra(act.Fecha_Inicio) : undefined}>`.
  - En `ProgramaTable.test.tsx:242` y `:453`, cambia `'Sem 33'` por `'En 33 sem'` y `'Sem 35'`
    por `'En 35 sem'` (los fixtures tienen desfases positivos). Es el cambio de comportamiento que
    decidió Felipe, no un ajuste para pasar. Agrega un caso con `Semanas_Inicio: -9` que espere
    `'Hace 9 sem'`.

- [ ] **Paso 4: el CSV exporta el número crudo, incluido el 0.** En `exportarCsv.ts:57`:

```ts
    const semInicioStr =
      act.Semanas_Inicio === null || act.Semanas_Inicio === undefined ? '' : String(act.Semanas_Inicio);
```

Prueba (en el `.test.ts` de `exportarCsv`, créalo si no existe, siguiendo el estilo de
`filtros.test.ts`): tres actividades con `Semanas_Inicio` `-9`, `0` y `3` producen `-9`, `0` y `3`
en la sexta columna del CSV; una con `null`, cadena vacía.

- [ ] **Paso 5: verificar y commit.**

```bash
npm --prefix frontend run test; echo "RC vitest=$?"
npm --prefix frontend run typecheck; echo "RC typecheck=$?"
npm --prefix frontend run build; echo "RC build=$?"
npx playwright test tests/browser/s05-programa-general-react.spec.mjs --workers=1; echo "RC browser=$?"
git add frontend/src/modules/programa-general public/app
git commit -m "feat(pg): inicio relativo legible en la tabla; el CSV conserva el desfase crudo"
```

La prueba de layout de 13 columnas debe seguir en verde: «Hace 12 sem» es más largo que «Sem -12»;
si recorta, ajusta el ancho de `pg-col-sem` en `programa-general.css` sin bajar el mínimo de
«Actividad» que fija la prueba.

---

### Tarea 4: Áreas válidas en los documentos del frente TNP (deuda 4)

**Archivos:** frontmatter de
`docs/superpowers/specs/2026-09-24-calificacion-tnp-semana-confirmada-design.md` y
`docs/superpowers/plans/2026-09-24-calificacion-tnp-semana-confirmada.md`.

- [ ] **Paso 1:** en ambos, cambia `areas: [lps, programacion_semanal, tnp, backend, frontend]` por
  `areas: [lps]` (lista cerrada en `scripts/wiki-esquema.mjs:7-8`). Solo esa línea: el cuerpo
  no se toca.
- [ ] **Paso 2:**

```bash
npm run test:wiki > /tmp/deuda-wiki-t4.log 2>&1; echo "RC wiki=$?"
grep -E '^(FUENTE|AREA|FRONTMATTER)' /tmp/deuda-wiki-t4.log
```

Esperado: sin hallazgos `FUENTE`/`AREA`/`FRONTMATTER` (queda solo `VERACIDAD`, que cierra la
tarea 5).

- [ ] **Paso 3:** `git commit -m "docs(tnp): áreas de la lista cerrada en la spec y el plan de TNP"`.

---

### Tarea 5: Pase de veracidad de la wiki (deuda 5)

**Archivos:** páginas de `memoria/` de las áreas que cambiaron; `memoria/log.md`.

- [ ] **Paso 1:** lee `docs/wiki-operacion.md` (secciones «`veracidad`» y «La alarma de
  veracidad») y los mapas de `memoria/mapas/` de `lps`, `design-system`, `qa` y `arquitectura`.
- [ ] **Paso 2: alcance.** Las áreas cuyo código cambió desde el último pase (2026-09-21), vistas con
  `git log --since=2026-09-21 --name-only -- src public frontend tests scripts`, más las páginas
  más antiguas sin revisar hasta un tope de 10.
- [ ] **Paso 3:** verifica **cada afirmación** de esas páginas contra el código, citando archivo y
  línea; corrige lo desmentido (y su `resumen`), marca `estado: derogada` lo que ya no aplica, nunca
  borres. Delegable a un subagente de bajo costo, exigiéndole que verifique y no que sospeche.
- [ ] **Paso 4:** agrega la línea de bitácora con el formato de `docs/wiki-operacion.md`:
  `- <fecha> · veracidad · áreas revisadas: … · N páginas · M corregidas, K derogadas · [[…]]`.
- [ ] **Paso 5:** `npm run test:wiki; echo "RC wiki=$?"` → `RC=0`. Commit
  `docs(wiki): pase de veracidad tras S05 ronda 1.2`.

---

### Tarea 6: Ingest de los aprendizajes de la ronda 1.2

**Archivos:** `memoria/` (página de trampas o del área que corresponda según `memoria/index.md`);
`memoria/log.md`.

- [ ] **Paso 1:** registra como `ingest`, siguiendo el esquema de `docs/wiki-operacion.md`, los
  cuatro aprendizajes de la sección «Hallazgos de proceso para la wiki (ingest)» de la spec, cada
  uno con su fuente (commit o archivo). No los copies literal: escríbelos como trampas accionables.
- [ ] **Paso 2:** `npm run test:wiki; echo "RC wiki=$?"` → `RC=0`. Commit
  `docs(wiki): ingest de la ronda 1.2 de S05`.

---

### Tarea 7: Verificación final, revisión e informe

- [ ] **Paso 1: suite local**, cada código en su línea:

```bash
npm --prefix frontend run typecheck; echo "RC typecheck=$?"
npm --prefix frontend run test; echo "RC vitest=$?"
npm run test:design-system:static; echo "RC static=$?"
npm run test:wiki; echo "RC wiki=$?"
npx playwright test tests/browser/s05-programa-general-react.spec.mjs tests/browser/shell-react-paridad-legado.spec.mjs tests/browser/design-system-body-canvas-dark.mjs --workers=1; echo "RC browser=$?"
```

Y, en la base aislada de CI y en ambos temas, los grupos que corre el CI para
`G_LABORATORY_GATES`, `G_PILOT_LAB_GATES` y `G_PG_PERSISTENCE_RBAC` (ver `.github/workflows/ci.yml`),
como en la ronda 1.2. Devuelve el `app` a la raíz al terminar.

- [ ] **Paso 2: revisión independiente** (`requesting-code-review`) con contexto limpio.
- [ ] **Paso 3: informe** en `docs/superpowers/plans/2026-09-26-s05-deuda-cierre-informe.md`: partida,
  commits por tarea, decisión de la tarea 2 con los números de contraste, salida real del paso 1,
  hallazgos y `BLOCKED`.
- [ ] **Paso 4: push y PR.** `git push -u origin codex/s05-deuda-cierre` y PR contra `main` con la
  condición de hecho declarada antes del CI. Lee la corrida por las `G_*` de ambos temas. Sin merge.

---

## Cobertura de la spec

| Punto de la spec | Tarea |
|---|---|
| 1 · valor fijo del tinte crítico | 1 |
| 2 · íconos inactivos del riel en claro | 2 |
| 3 · inicio relativo y CSV crudo (versión 1.1) | 3 |
| 4 · áreas del frente TNP | 4 |
| 5 · pase de veracidad | 5 |
| Hallazgos de proceso (ingest) | 6 |
| Condición de hecho | 7 |
