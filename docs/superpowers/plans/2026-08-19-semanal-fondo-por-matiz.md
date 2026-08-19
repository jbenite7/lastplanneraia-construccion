---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-19
areas: [lps]
fuente: docs/superpowers/plans/2026-08-19-semanal-fondo-por-matiz.md
resumen: Que el fondo de fila de /programacion-semanal deje de codificar un cubo de alerta y pase a codificar identidad por matiz, con la gravedad en el filete, igual…
---

# Programación Semanal: el fondo pasa a matiz — plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que el fondo de fila de `/programacion-semanal` deje de codificar un cubo de alerta y pase a codificar identidad por matiz, con la gravedad en el filete, igual que Intermedia.

**Architecture:** `WEEKLY_ALERT_MODEL` deja de decidir el color: sigue asignando su clase (que otras reglas usan para forma y tipografía), pero el fondo lo pinta el matiz que el contrato ya declara por estado, y el filete lo pinta el nivel. La fase no se toca: `stateMachine.js:58` sigue resolviendo cuál de las dos mitades está viva, y por eso las repeticiones entre fases son inocuas.

**Tech Stack:** PHP 8.3 sin framework, Handsontable, CSS con `@layer`, Node test runner, Playwright, Docker Compose.

**Spec:** `docs/superpowers/specs/2026-08-19-estados-severidad-contrato-design.md` (no lleva spec propia: ver `goals/semanal-fondo-por-matiz/goal.md` §Por qué no lleva spec propia).

## Global Constraints

- **Los hex de `--ds-state-tint-*` no se tocan.**
- **Ningún golden se regenera sin aprobación visual explícita del usuario, pedida por su nombre.** Bloqueo incondicional.
- **Ningún test se ablanda.** Si cambia porque cambió el contrato, se cambia declarando qué mide ahora.
- **Color computado contra color computado.** Nunca comparar declarado con computado.
- **Medir no es mirar: toda tarea visual termina con una captura mirada**, no solo con asserts. Dos entregas del frente hermano llegaron con los números en verde y la pantalla rota.
- **Sesión local por la puerta de servicio**, nunca `/login`.
- **El contenedor compartido se pide a la coordinadora antes de tomarlo**, y se devuelve a la raíz al terminar. Para servir el worktree con la puerta abierta hace falta un archivo extra, porque el `.env` es un symlink a una ruta del host que no resuelve dentro del contenedor:
  ```
  cat > /tmp/dev-door.yml <<'YML'
  services:
    app:
      environment:
        DEV_DOOR: "${DEV_DOOR}"
        DEV_DOOR_USERS: "${DEV_DOOR_USERS}"
  YML
  LPS_CODE_ROOT="$(pwd)" docker compose -f docker-compose.yml -f docker-compose.override.yml -f /tmp/dev-door.yml up -d --force-recreate app
  ```
- **Verificación del frente:** `bash scripts/publicar.sh --solo-verificar`.

## Estructura de archivos

| Archivo | Responsabilidad |
|---|---|
| `public/js/modules/programacion_semanal/hot.js` | Escribe `data-aia-severity-rail` en la primera celda y la clase de matiz en la fila |
| `public/css/styles.css` | Los bloques `.ps-page … .ps-alert-*` dejan de pintar el fondo por cubo |
| `public/css/programacion-semanal.css` | Fondo por matiz, y los muestrarios de la leyenda alineados |
| `tests/design-system/semanal-matiz.test.mjs` | **Nuevo.** Guard de que ningún par de la misma fase comparte matiz **en el CSS**, no solo en el contrato |

---

### Task 1: El guard que hoy no existe

**Files:**
- Create: `tests/design-system/semanal-matiz.test.mjs`

**Interfaces:**
- Produces: un guard que lee el **CSS** y comprueba que los cinco estados de cada fase resuelvan a cinco `--ds-state-tint-*` distintos.

- [ ] **Step 1: Escribe el test que falla**

```javascript
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (file) => readFile(new URL(`../../${file}`, import.meta.url), 'utf8');

// Este guard lee el CSS, no el contrato. Es la leccion de
// `guard-valida-declaracion-contra-si-misma`: comprobar el JSON contra el JSON
// deja verde una divergencia entre contrato y hoja. Aqui se comprueba que la
// HOJA pinte lo que el contrato dice.
//
// Limite conocido y dicho: leer el texto del CSS tampoco garantiza el render
// —ver `guard-de-texto-no-ve-el-parseo`—, asi que este guard NO sustituye la
// medicion en navegador de la Task 3; la complementa cazando la divergencia
// barata sin abrir Docker.
const FASES = { prog: 'programacion', cal: 'calificacion' };

test('cada fase de Semanal pinta cinco matices distintos en el CSS', async () => {
  const semantics = JSON.parse(await read('docs/design-system/state-semantics.json'));
  const css = await read('public/css/programacion-semanal.css');
  const estados = semantics.moduleMappings.find((m) => m.module === 'programacion-semanal').states;

  for (const prefijo of Object.keys(FASES)) {
    const deLaFase = estados.filter(({ key }) => key.startsWith(`${prefijo}-`));
    assert.equal(deLaFase.length, 5, `la fase ${FASES[prefijo]} debe tener cinco estados`);

    const tintes = deLaFase.map(({ key, hue }) => {
      const regla = css.match(new RegExp(`\\.ps-state-${key}\\b[^{]*\\{([^}]*)\\}`))?.[1];
      assert.ok(regla, `programacion-semanal.css no pinta .ps-state-${key}`);
      const tinte = regla.match(/--ds-state-tint-([a-z]+)/)?.[1];
      assert.equal(tinte, hue, `.ps-state-${key} pinta ${tinte} y el contrato dice ${hue}`);
      return tinte;
    });

    assert.equal(
      new Set(tintes).size, 5,
      `la fase ${FASES[prefijo]} pinta ${new Set(tintes).size} colores para cinco estados: ${tintes}`,
    );
  }
});
```

- [ ] **Step 2: Corre el test y comprueba que falla**

```bash
node --test tests/design-system/semanal-matiz.test.mjs
```

Esperado: FAIL — `programacion-semanal.css no pinta .ps-state-prog-bloqueo-critico-sin-compromiso`. Hoy la hoja no tiene ninguna regla por estado: pinta por cubo.

- [ ] **Step 3: Commit del guard en rojo**

```bash
git add tests/design-system/semanal-matiz.test.mjs
git commit -m "test(ps): el guard del matiz por fase, en rojo a proposito"
```

---

### Task 2: La fila declara estado y nivel

**Files:**
- Modify: `public/js/modules/programacion_semanal/hot.js`

**Interfaces:**
- Consumes: `statePresentation` (ya existente, con los matices desempatados) y `LEVEL_ATTRS`.
- Produces: en cada `<tr>` la clase `ps-state-<key>`, y `data-aia-severity-rail="<nivel>"` **solo en la primera celda**.

- [ ] **Step 1: Añade la clase de estado junto a la de cubo**

La clase de cubo (`ps-alert-*`) **no se retira**: otras reglas la usan para forma y tipografía, y quitarla es un cambio mayor que este frente no necesita. Se **añade** `ps-state-<key>` en el mismo sitio donde se compone la clase de fila.

- [ ] **Step 2: Escribe el atributo de nivel SOLO en la primera celda**

Copia el mecanismo de Intermedia (`applyPIRowSeverityAttr`, `programacion_intermedia/hot.js`): el `<tr>` lleva el atributo, y de las celdas **solo `cells[0]`**.

> **Esto no es un detalle de estilo.** Puesto en todas las celdas, la primitiva dibuja una barra vertical en cada columna y la tabla se lee como un pijama. Pasó el 2026-08-19 en Intermedia, con `railGrosor` dando los cuatro grosores correctos y «pares idénticos: ninguno» en verde. Lo cazó una captura.

- [ ] **Step 3: Comprueba que no rompiste el chip**

```bash
node --test tests/design-system/ops-state-contract.test.mjs tests/design-system/state-tint-ladder.test.mjs
```

Esperado: PASS. El chip ya consumía matiz y no se toca.

- [ ] **Step 4: Commit**

---

### Task 3: El fondo pasa a matiz

**Files:**
- Modify: `public/css/programacion-semanal.css`
- Modify: `public/css/styles.css` (los bloques `.ps-page … td.ps-row-state.ps-alert-*`)

- [ ] **Step 1: Escribe una regla de fondo por estado**

Diez reglas `.ps-page … td.ps-row-state.ps-state-<key>`, cada una con
`background-color: var(--ds-state-tint-<matiz>)` y `color: var(--ds-active-text-primary)`.

Los matices salen del contrato, no se inventan: `prog-bloqueo-critico-sin-compromiso` red ·
`prog-ejecucion-con-restricciones` orange · `prog-condiciones-pendientes` amber ·
`prog-sin-compromiso` **violet** · `prog-lista-para-confirmar` green ·
`cal-incumplida-critica` red · `cal-incumplida` amber · `cal-sin-calificar` **neutral** ·
`cal-cumplida-control` green · `cal-tnp` blue.

> **Declara el `color:` en la misma regla.** El guard `state-tint-pairing` exige que toda regla que
> pinte un fondo de matiz declare su tinta; sin eso se pone rojo, y con razón.

- [ ] **Step 2: Retira el fondo de los bloques por cubo**

En `styles.css`, los bloques `td.ps-row-state.ps-alert-*` dejan de declarar `background-color`. El
resto de lo que declaren (bordes, peso) se conserva salvo que contradiga al matiz.

- [ ] **Step 3: Alinea los muestrarios de la leyenda**

`.ps-legend-modal-swatch.*` pasa a usar el mismo matiz que su fila, o la leyenda deja de describir
la tabla — el defecto F0-012.

- [ ] **Step 4: El guard de la Task 1 pasa a verde**

```bash
node --test tests/design-system/semanal-matiz.test.mjs
```

- [ ] **Step 5: MIDE Y MIRA, en las dos fases**

Pide la ventana de contenedor a la coordinadora. Adapta la sonda de Intermedia
(`goals/ds-f1a-estados-severidad/evidence/sonda-despues.mjs`) a `/programacion-semanal` y córrela
**dos veces**: con la semana sin confirmar (fase Programación) y confirmada (fase Calificación).

Esperado en cada una: **cinco fondos distintos, cero pares idénticos**, filete solo en `urgent` y
`attention`, y **una captura mirada** — no solo el JSON.

- [ ] **Step 6: Anota el hallazgo que pidió la coordinadora**

Si al recorrer los estados aparece cuántas filas caerían en «detenido por otro», anótalo en el
`goal.md`: alimenta la decisión del `r0` de Programa General, hoy en la mesa de Felipe.

- [ ] **Step 7: Commit sin tocar goldens**

Los goldens de Semanal **van a fallar**. No los regeneres.

---

### Task 4: Conciliar goldens y cerrar

- [ ] **Step 1:** Enseña al usuario el antes y el después **a tamaño real**, de las dos fases.
- [ ] **Step 2:** Pide aprobación visual **por el nombre de cada golden**. Sin un sí explícito, para aquí.
- [ ] **Step 3:** Solo con el sí, `--update-snapshots`, y actualiza los `sha256` del manifiesto si los ancla.
- [ ] **Step 4:** `bash scripts/publicar.sh --solo-verificar`, escribe `## Publicaciones` y `## Cierre` en el `goal.md`, y entrega el sha a la coordinadora para visto.
- [ ] **Step 5:** Con el visto, publica con `bash scripts/publicar.sh` y devuelve el contenedor a la raíz.
