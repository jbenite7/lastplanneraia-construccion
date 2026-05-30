# Plan de Validación con Playwright — Auto-Programar Cascade

> **Versión:** 1.0
> **Proyecto:** Last Planner AIA — Legacy Permisos
> **Módulo:** Auto-Programar Simple (cascada automática PG → PI → PS)
> **Test File:** `tests/browser/auto-program.mjs`

---

## 1. Objetivo

Validar que la cascada automática de Auto-Programar Simple funciona correctamente:

1. **PG → PS**: Actividades en estado "Debe Comprometer" con restricciones OK se auto-comprometen
2. **PI → PS**: Cambios en restricciones duras en PI gatillan descomprometer/re-comprometer en PS
3. **PS sin PG**: Actividades huérfanas se eliminan
4. **PG Terminada**: Actividades marcadas como terminadas se descomprometen de PS
5. **Restricciones blandas**: No afectan el cascade
6. **Log**: Cada acción se registra correctamente y el modal las muestra
7. **Idempotencia**: Re-ejecutar sin cambios produce 0 acciones

---

## 2. Configuración

### 2.1 Proyecto destino

```
Nombre:      Optimizacion Aeropuerto JMC
DB prefix:   optimizacion_aeropuerto_jmc
Rol:         Residente de Obra (R)
URL:         http://localhost:8081
```

### 2.2 Credenciales

```javascript
const CREDENTIALS = { user: 'jbenitez', pass: 'Jbe#1106z' };
```

### 2.3 Selección de proyecto en test

```javascript
// En vez de .first():
await page.locator('.project-item[data-name="optimizacion aeropuerto jmc"] ' +
  'button:has-text("Ingresar al Proyecto")').click();
```

### 2.4 Tablas involucradas

| Tabla | Rol |
|---|---|
| `{db}_programa_consolidado` | PG + PI (misma tabla) |
| `{db}_programacion_semanal` | PS |
| `{db}_auto_program_log` | Log de acciones del cascade |

### 2.5 Columnas clave

#### `{db}_programa_consolidado` (PG / PI)

| Columna | Tipo | Editable en UI | En Handsontable |
|---|---|---|---|
| `Consecutivo_en_Programa` | int | No | ID único |
| `Titulo` | int (0/1) | No | Header si =1 |
| `Estado` | varchar(100) | No (backend) | Col 11 (readOnly) |
| `D_y_E` | varchar(9) | Sí (PI dropdown col 7) | Col 7 |
| `Materiales` | varchar(9) | Sí (PI dropdown col 8) | Col 8 |
| `MdeO` | varchar(9) | Sí (PI dropdown col 9) | Col 9 |
| `Equipos` | varchar(9) | Sí (PI dropdown col 10) | Col 10 |
| `Predecesora` | varchar(9) | Sí (PI dropdown col 11) | Col 11 |
| `Pdto_Cons` | varchar(9) | Sí (PI dropdown col 12) | Col 12 (blanda) |
| `Modelo` | varchar(9) | Sí (PI dropdown col 13) | Col 13 (blanda) |
| `Ejecutado` | float | Sí (PG col 10) | Col 10 (ratio 0.0-1.0) |

#### `{db}_programacion_semanal` (PS)

| Columna | Tipo | Notas |
|---|---|---|
| `Consecutivo_En_Programa` | int | FK a programa_consolidado |
| `Compromiso` | float | > 0 = comprometida |
| `Activa` | varchar(3) | '1'=activa, '0'=inactiva/CNP, 'NA'=manual |
| `Categoria_CNP` | varchar(100) | Causa de No Programación |
| `CNP` | varchar(100) | Detalle CNP |
| `Prog_Sin_Restricciones_100` | int | 0=OK, 1=bloqueado |

### 2.6 Valores de restricciones en DB

Las restricciones se almacenan como VARCHAR(9). Valores posibles:

| Display en dropdown | Valor en DB | parseRestrictionRatio |
|---|---|---|
| `0%` | `'0'` | `0.0` |
| `33%` | `'0.33'` | `0.33` |
| `50%` | `'0.5'` | `0.5` |
| `66%` | `'0.66'` | `0.66` |
| `100%` | `'1'` | `1.0` |
| `N/A` | `'N/A'` | `null` (salta) |

### 2.7 Thresholds de restricciones duras

| Restricción | Threshold | Col index (PI) |
|---|---|---|
| `D_y_E` | ≥ 1.0 (100%) | 7 |
| `Materiales` | ≥ 1.0 (100%) | 8 |
| `MdeO` | ≥ 1.0 (100%) | 9 |
| `Equipos` | ≥ 1.0 (100%) | 10 |
| `Predecesora` | ≥ 0.5 (50%) | 11 |

### 2.8 Grupos de estado PG

| Grupo | Estados |
|---|---|
| `debe_comprometer` | En Curso, Atrasada, Debe Iniciar, A Tiempo, Adelantada, Ya Debió Iniciar y Restricciones Pendientes, Debe Iniciar esta Semana, Debe Iniciar esta Semana y Restricciones Pendientes |
| `terminada` | Terminada, Terminada Antes |
| `actividad_futura` | Actividad Futura, En Liberación de Restricciones, No Requerida |
| `header` | Titulo=1 |
| `desconocido` | Sin Datos, otros |

### 2.9 Acciones del log

| Acción | Badge en UI | Significado |
|---|---|---|
| `comprometer` | `.badge-success` verde | Se auto-agregó a PS con Compromiso=100 |
| `descomprometer` | `.badge-danger` rojo | Se eliminó de PS |
| `insert_cnp` | `.badge-warning` amarillo | Se insertó en PS con Activa='0' + Categoria_CNP/CNP |

---

## 3. Helpers comunes

```javascript
// === Constantes ===
const BASE = 'http://localhost:8081';
const OUTPUT = 'test-output-auto-program';
const CREDENTIALS = { user: 'jbenitez', pass: 'Jbe#1106z' };

// === Estado global ===
let passed = 0, failed = 0;
const results = [];

// === Helpers base ===
function report(name, ok, detail) { ... }
async function shot(page, name) { ... }
async function closeBlockingChangeMonitor(page) { ... }

// === Login con proyecto específico ===
async function login(page) {
  await page.goto(BASE);
  await page.fill('input[placeholder="Usuario"]', CREDENTIALS.user);
  await page.fill('input[placeholder="Contraseña"]', CREDENTIALS.pass);
  await page.click('button:has-text("INICIAR SESIÓN")');
  await page.waitForURL('**/proyectos');
  await page.locator('.project-item[data-name="optimizacion aeropuerto jmc"] ' +
    'button:has-text("Ingresar al Proyecto")').click();
  await page.waitForTimeout(3000);
}

// === Hot Counts ===
async function hotCounts(page, moduleName) { ... }

// === Helper: encontrar actividad en PI con restricciones duras cumplidas ===
async function findPIActivityHardOk(page) {
  return page.evaluate(() => {
    const inst = window.PIHotModule?.getHotInstance();
    if (!inst) return null;
    const count = inst.countRows();
    const hard = [
      { col: 7,  prop: 'D_y_E',        threshold: 1.0 },
      { col: 8,  prop: 'Materiales',    threshold: 1.0 },
      { col: 9,  prop: 'MdeO',          threshold: 1.0 },
      { col: 10, prop: 'Equipos',       threshold: 1.0 },
      { col: 11, prop: 'Predecesora',   threshold: 0.5 },
    ];
    for (let r = 0; r < count; r++) {
      const row = inst.getSourceDataAtRow(r);
      if (!row || row.Titulo === 1) continue;
      let allOk = true;
      for (const h of hard) {
        const val = row[h.prop];
        if (!val || val === 'N/A') continue;
        const ratio = parseFloat(val) || 0;
        if (ratio < h.threshold) { allOk = false; break; }
      }
      if (allOk) return {
        row: r,
        consecutivo: row.Consecutivo_en_Programa,
        restricciones: { D_y_E: row.D_y_E, Materiales: row.Materiales, MdeO: row.MdeO, Equipos: row.Equipos, Predecesora: row.Precesora ?? row.Predecesora }
      };
    }
    return null;
  });
}

// === Helper: encontrar actividad en PI con restricción dura BAJO threshold ===
async function findPIActivityHardNotOk(page) {
  return page.evaluate(() => {
    const inst = window.PIHotModule?.getHotInstance();
    if (!inst) return null;
    const count = inst.countRows();
    const hard = [
      { col: 7,  prop: 'D_y_E',        threshold: 1.0 },
      { col: 8,  prop: 'Materiales',    threshold: 1.0 },
      { col: 9,  prop: 'MdeO',          threshold: 1.0 },
      { col: 10, prop: 'Equipos',       threshold: 1.0 },
      { col: 11, prop: 'Predecesora',   threshold: 0.5 },
    ];
    for (let r = 0; r < count; r++) {
      const row = inst.getSourceDataAtRow(r);
      if (!row || row.Titulo === 1) continue;
      for (const h of hard) {
        const val = row[h.prop];
        if (!val || val === 'N/A') continue;
        const ratio = parseFloat(val) || 0;
        if (ratio < h.threshold) {
          return {
            row: r,
            col: h.col,
            prop: h.prop,
            consecutivo: row.Consecutivo_en_Programa,
            currentValue: val,
            threshold: h.threshold
          };
        }
      }
    }
    return null;
  });
}

// === Helper: setear restricción PI vía Handsontable ===
async function setPIRestriction(page, row, col, value) {
  const displayValue = value === '1' ? '100%' : value === '0.5' ? '50%' : value === '0' ? '0%' : value;
  await page.evaluate(({ r, c, v }) => {
    const inst = window.PIHotModule?.getHotInstance();
    if (inst) inst.setDataAtCell(r, c, v, 'edit');
  }, { r: row, c: col, v: displayValue });
  await page.waitForTimeout(1500); // esperar auto-save
}

// === Helper: verificar actividad en PS ===
async function findInPS(page, consecutivo) {
  return page.evaluate((c) => {
    const inst = window.PSHotModule?.getHotInstance();
    if (!inst) return null;
    const count = inst.countRows();
    for (let r = 0; r < count; r++) {
      if (inst.getDataAtRowProp(r, 'Consecutivo_En_Programa') === c)
        return { row: r, compromiso: inst.getDataAtRowProp(r, 'Compromiso'), activa: inst.getDataAtRowProp(r, 'Activa') };
    }
    return null;
  }, consecutivo);
}

// === Helper: leer badge de auto-program ===
async function getAutoProgramBadge(page) {
  const badge = page.locator('#cm-badge');
  const exists = await badge.count() > 0;
  if (!exists) return 0;
  const text = await badge.textContent();
  return parseInt(text) || 0;
}

// === Helper: abrir modal de log ===
async function openAutoProgramModal(page) {
  const btn = page.locator('#cm-btn-badge');
  if (await btn.count() === 0) return false;
  await btn.click();
  await page.waitForTimeout(1000);
  return await page.locator('#modal_change_monitor').isVisible();
}

// === Helper: cerrar modal de log ===
async function closeAutoProgramModal(page) {
  await page.locator('#modal_change_monitor button:has-text("Cerrar")').click();
  await page.waitForTimeout(500);
}

// === Helper: re-ejecutar cascade vía API directa ===
async function rerunCascade(page) {
  return page.evaluate(() =>
    fetch('/api/semanal/auto-program', { method: 'POST' })
      .then(r => r.json())
  );
}
```

---

## 4. Escenarios de prueba

---

### 4.1 Escenario 1 — PI restricción OK → PS auto-commit

**Objetivo:** Verificar que una actividad en PG con estado "Debe Comprometer" y restricciones OK se auto-compromete en PS.

**Checklist de cumplimiento (setup):**
- [ ] Existe actividad en `{db}_programa_consolidado` con `Estado ∈ debe_comprometer`
- [ ] La misma actividad tiene TODAS hard restrictions ≥ threshold (o N/A)
- [ ] La actividad NO existe en `{db}_programacion_semanal` con `Compromiso > 0`
- [ ] La semana activa en sesión es la misma que la semana de la actividad

**Pasos:**

| # | Acción | Selector / Código | Espera |
|---|---|---|---|
| 1.1 | Navegar a PI | `page.goto(BASE + '/programacion-intermedia')` | `waitForTimeout(3000)` |
| 1.2 | Esperar carga | `PIHotModule.getHotInstance()` !== null | `waitForTimeout(2000)` |
| 1.3 | Buscar actividad con restricciones bajo threshold | `findPIActivityHardNotOk(page)` | — |
| 1.4 | Si encontró: setear D_y_E → '100%' | `setPIRestriction(page, row, 7, '1')` | `waitForTimeout(1500)` |
| 1.5 | Si NO encontró (todas OK): skip o usar otra columna | — | — |
| 1.6 | Navegar a PS | `page.goto(BASE + '/programacion-semanal')` | `waitForTimeout(3000)` |
| 1.7 | Cerrar modal de cambios si aparece | `closeBlockingChangeMonitor(page)` | — |
| 1.8 | Esperar cascade | `waitForTimeout(2500)` | — |
| 1.9 | Leer badge | `getAutoProgramBadge(page)` | — |
| 1.10 | Abrir modal | `openAutoProgramModal(page)` | — |
| 1.11 | Verificar filas con acción `comprometer` | `#cm-table-body tr.cm-row-comprometer` | — |
| 1.12 | Cerrar modal | `closeAutoProgramModal(page)` | — |

**Checklist de validación:**
- [ ] `1.1-1.2`: PI carga con datos (`hotCounts('PI').visual > 0`)
- [ ] `1.9`: Badge count > 0
- [ ] `1.10`: Modal visible con título "Actividades Auto-gestionadas"
- [ ] `1.11`: Al menos 1 fila `cm-row-comprometer` con badge `.badge-success`
- [ ] `1.11`: Cada fila tiene 4 `<td>`: Consecutivo, Acción, Detalle, Fecha
- [ ] La actividad aparece en PS grid con `Compromiso > 0`
- [ ] `shot('e1-ps-auto-commit')`

---

### 4.2 Escenario 2 — PI restricción pierde threshold → PS descommit

**Objetivo:** Verificar que al romper una restricción dura en PI, la actividad se descompromete de PS.

**Checklist de cumplimiento (setup):**
- [ ] Existe actividad en `{db}_programacion_semanal` con `Compromiso > 0` y `Activa = '1'`
- [ ] La misma actividad tiene hard restrictions ≥ threshold en `{db}_programa_consolidado`
- [ ] No está en estado `Terminada` en PG

**Pasos:**

| # | Acción | Selector / Código | Espera |
|---|---|---|---|
| 2.1 | Navegar a PI | `page.goto(BASE + '/programacion-intermedia')` | `waitForTimeout(3000)` |
| 2.2 | Buscar actividad con restricciones OK | `findPIActivityHardOk(page)` → `{row, col, prop, consecutivo}` | — |
| 2.3 | Si no encontró: SKIP escenario | `report('E2: SKIP', true, 'Sin actividad con restricciones OK')` | — |
| 2.4 | Setear D_y_E → '0%' (break restriction) | `setPIRestriction(page, row, 7, '0')` | `waitForTimeout(1500)` |
| 2.5 | Guardar `consecutivo` de la actividad modificada | Variable local | — |
| 2.6 | Navegar a PS | `page.goto(BASE + '/programacion-semanal')` | `waitForTimeout(3000)` |
| 2.7 | Cerrar modal de cambios | `closeBlockingChangeMonitor(page)` | — |
| 2.8 | Esperar cascade | `waitForTimeout(2500)` | — |
| 2.9 | Leer badge | `getAutoProgramBadge(page)` | — |
| 2.10 | Abrir modal | `openAutoProgramModal(page)` | — |
| 2.11 | Verificar fila `descomprometer` o `insert_cnp` | `#cm-table-body tr.cm-row-descomprometer` o `cm-row-insert_cnp` | — |
| 2.12 | Verificar actividad NO está en PS activa | `findInPS(page, consecutivo)` | retorna `null` o `activa='0'` |
| 2.13 | Cerrar modal | `closeAutoProgramModal(page)` | — |

**Checklist de validación:**
- [ ] `2.2`: Actividad encontrada con restricciones OK (no SKIP)
- [ ] `2.9`: Badge count > 0
- [ ] `2.11`: Fila de log para el `consecutivo` modificado
- [ ] `2.11`: Badge `.badge-danger` (descomprometer) o `.badge-warning` (insert_cnp)
- [ ] `2.12`: La actividad ya no está en PS (o Activa='0')
- [ ] `shot('e2-ps-descommit')`

---

### 4.3 Escenario 3 — PI restricción reparada → PS re-commit

**Objetivo:** Restaurar la restricción rota en Escenario 2 y verificar que la actividad se re-compromete.

**Checklist de cumplimiento (setup):**
- [ ] Escenario 2 se ejecutó (existe `consecutivo` con restricción rota)
- [ ] La actividad sigue existiendo en `{db}_programa_consolidado` con estado "Debe Comprometer"

**Pasos:**

| # | Acción | Selector / Código | Espera |
|---|---|---|---|
| 3.1 | Navegar a PI | `page.goto(BASE + '/programacion-intermedia')` | `waitForTimeout(3000)` |
| 3.2 | Localizar misma actividad por `consecutivo` | `page.evaluate((c) => inst.getSourceDataAtRow(...))` | — |
| 3.3 | Restaurar D_y_E → '100%' | `setPIRestriction(page, row, 7, '1')` | `waitForTimeout(1500)` |
| 3.4 | Navegar a PS | `page.goto(BASE + '/programacion-semanal')` | `waitForTimeout(3000)` |
| 3.5 | Esperar cascade | `waitForTimeout(2500)` | — |
| 3.6 | Leer badge | `getAutoProgramBadge(page)` | — |
| 3.7 | Abrir modal | `openAutoProgramModal(page)` | — |
| 3.8 | Verificar fila `comprometer` | `#cm-table-body tr.cm-row-comprometer` | — |
| 3.9 | Verificar actividad SÍ está en PS activa | `findInPS(page, consecutivo)` | `compromiso > 0` y `activa === '1'` |
| 3.10 | Cerrar modal | `closeAutoProgramModal(page)` | — |

**Checklist de validación:**
- [ ] `3.2`: Actividad localizada sin error
- [ ] `3.6`: Badge count > 0
- [ ] `3.8`: Fila `cm-row-comprometer` con badge `.badge-success`
- [ ] `3.9`: Actividad reaparece en PS con `Compromiso > 0` y `Activa = '1'`
- [ ] `shot('e3-ps-recommit')`

---

### 4.4 Escenario 4 — Múltiples cambios batch → log completo

**Objetivo:** Modificar 3 actividades distintas en PI y verificar que el log refleja todas.

**Checklist de cumplimiento (setup):**
- [ ] Existen al menos 3 actividades no-header en PI
- [ ] Al menos 3 tienen hard restrictions ≥ threshold (para romperlas)

**Pasos:**

| # | Acción | Selector / Código | Espera |
|---|---|---|---|
| 4.1 | Navegar a PI | `page.goto(BASE + '/programacion-intermedia')` | `waitForTimeout(3000)` |
| 4.2 | Colectar 3 actividades con restricciones OK | Loop `findPIActivityHardOk(page)` x3 | — |
| 4.3 | Para cada una: cambiar restriction diferente | Act1: D_y_E → '0%', Act2: Materiales → '0%', Act3: MdeO → '0%' | 1500ms c/u |
| 4.4 | Navegar a PS | `page.goto(BASE + '/programacion-semanal')` | `waitForTimeout(3000)` |
| 4.5 | Esperar cascade | `waitForTimeout(2500)` | — |
| 4.6 | Leer badge | `getAutoProgramBadge(page)` | — |
| 4.7 | Abrir modal | `openAutoProgramModal(page)` | — |
| 4.8 | Contar filas en tbody | `page.locator('#cm-table-body tr').count()` | — |
| 4.9 | Verificar 3 filas o mensaje de consolidación | — | — |
| 4.10 | Cerrar modal | `closeAutoProgramModal(page)` | — |

**Checklist de validación:**
- [ ] `4.2`: Se encontraron 3 actividades (si < 3: SKIP parcial)
- [ ] `4.6`: Badge count ≤ 3 (puede ser menos si algunas ya estaban descommitted)
- [ ] `4.8`: `rowCount > 0`
- [ ] Cada fila tiene acción, badge de color, y datos no vacíos
- [ ] `shot('e4-batch')`

---

### 4.5 Escenario 5 — Idempotencia: re-ejecutar cascade sin cambios

**Objetivo:** Verificar que re-ejecutar el cascade inmediatamente después produce 0 acciones.

**Checklist de cumplimiento (setup):**
- [ ] Cascade ya se ejecutó al menos una vez en la sesión actual
- [ ] No se hicieron cambios en PG/PI entre la ejecución anterior y esta

**Pasos:**

| # | Acción | Selector / Código | Espera |
|---|---|---|---|
| 5.1 | Llamar cascade vía API directa | `rerunCascade(page)` | Promesa resuelta |
| 5.2 | Verificar `response.success === true` | `result.success` | — |
| 5.3 | Verificar `response.total_acciones === 0` | `result.total_acciones` | — |
| 5.4 | Verificar badge oculto o = 0 | `getAutoProgramBadge(page) === 0` | — |

**Checklist de validación:**
- [ ] `5.2`: `success === true`
- [ ] `5.3`: `total_acciones === 0`
- [ ] `5.4`: Badge invisible o texto = "0"
- [ ] `shot('e5-idempotent')`

---

### 4.6 Escenario 6 — Restricción blanda NO bloquea

**Objetivo:** Verificar que cambiar Pdto_Cons o Modelo (restricciones blandas) no afecta el cascade.

**Checklist de cumplimiento (setup):**
- [ ] Existe actividad con hard restrictions OK y Pdto_Cons > 0%
- [ ] La actividad está en PS con Compromiso > 0 (opcional)

**Pasos:**

| # | Acción | Selector / Código | Espera |
|---|---|---|---|
| 6.1 | Navegar a PI | `page.goto(BASE + '/programacion-intermedia')` | `waitForTimeout(3000)` |
| 6.2 | Buscar actividad con hard OK + Pdto_Cons > 0% | `page.evaluate(...)` | — |
| 6.3 | Setear Pdto_Cons → '0%' | `setPIRestriction(page, row, 12, '0')` | `waitForTimeout(1500)` |
| 6.4 | Navegar a PS | `page.goto(BASE + '/programacion-semanal')` | `waitForTimeout(3000)` |
| 6.5 | Esperar cascade | `waitForTimeout(2500)` | — |
| 6.6 | Verificar badge = 0 (sin cambios) | `getAutoProgramBadge(page) === 0` | — |

**Checklist de validación:**
- [ ] `6.2`: Actividad encontrada con hard OK + Pdto_Cons positivo
- [ ] `6.6`: Badge = 0 o badge solo refleja cambios NO relacionados con la restricción blanda
- [ ] `shot('e6-soft-restriction')`

---

### 4.7 Escenario 7 — PG estado Terminada → PS descommit (vía API directa)

**Objetivo:** Verificar que cambiar estado PG a "Terminada" (vía API directa porque UI no permite) descompromete la actividad de PS.

**Checklist de cumplimiento (setup):**
- [ ] Existe actividad en PS con `Compromiso > 0` y `Activa = '1'`
- [ ] La misma actividad en PG tiene `Estado ≠ Terminada` y `Estado ≠ Terminada Antes`

**Pasos:**

| # | Acción | Selector / Código | Espera |
|---|---|---|---|
| 7.1 | Identificar consecutivo en PS con compromiso | `findInPS(page, ...)` desde sesión PS | — |
| 7.2 | Actualizar Estado vía API directa | `page.evaluate((c) => fetch('/api/general/update', {method:'POST', body: JSON.stringify({consecutivo: c, estado: 'Terminada'})...}))` | — |
| 7.3 | Navegar a PS | `page.goto(BASE + '/programacion-semanal')` | `waitForTimeout(3000)` |
| 7.4 | Esperar cascade | `waitForTimeout(2500)` | — |
| 7.5 | Verificar badge > 0 | `getAutoProgramBadge(page) > 0` | — |
| 7.6 | Verificar actividad descommitted | `findInPS(page, consecutivo)` retorna null | — |

**Checklist de validación:**
- [ ] `7.2`: API responde con `{success: true}`
- [ ] `7.5`: Badge > 0
- [ ] `7.6`: Actividad eliminada de PS
- [ ] `shot('e7-terminada-descommit')`

---

## 5. Flujo de ejecución del test completo

```
main()
│
├── 0. Setup
│   ├── launch chromium { headless: true }
│   ├── newContext({ viewport: { width: 1400, height: 900 } })
│   ├── page.on('console') → errors[]
│   ├── page.on('pageerror') → errors[]
│   └── login(page)
│
├── 1. Escenario 1 — PI restricción OK → PS auto-commit
│   ├── PI: set restriction → '100%' (si estaba bajo)
│   ├── PS: verify badge + modal + grid
│   └── shot('e1-ps-auto-commit')
│
├── 2. Escenario 2 — PI restricción rota → PS descommit
│   ├── PI: set restriction → '0%'
│   ├── PS: verify badge + modal + grid
│   └── shot('e2-ps-descommit')
│
├── 3. Escenario 3 — PI restricción reparada → PS re-commit
│   ├── PI: set restriction → '100%'
│   ├── PS: verify badge + modal + grid
│   └── shot('e3-ps-recommit')
│
├── 4. Escenario 4 — Múltiples cambios batch
│   ├── PI: 3 activities modified
│   ├── PS: verify badge + modal row count
│   └── shot('e4-batch')
│
├── 5. Escenario 5 — Idempotencia
│   ├── re-run cascade via fetch
│   └── verify 0 acciones
│
├── 6. Escenario 6 — Restricción blanda NO bloquea
│   ├── PI: set Pdto_Cons → '0%'
│   ├── PS: verify badge = 0
│   └── shot('e6-soft-restriction')
│
├── 7. Escenario 7 — PG Terminada vía API → PS descommit
│   ├── API: update estado to 'Terminada'
│   ├── PS: verify badge > 0 + activity gone
│   └── shot('e7-terminada-descommit')
│
├── 8. Reporte
│   ├── Generar REPORTE-AUTO-PROGRAM.md
│   ├── console.log resultados
│   └── if failed > 0 process.exit(1)
│
└── 9. Cleanup
    └── browser.close()
```

---

## 6. Criterios de aceptación

### 6.1 Todos los escenarios deben pasar

| Escenario | Mínimo esperado | Crítico |
|---|---|---|
| E1: auto-commit | Badge > 0 y fila `comprometer` en modal | Sí |
| E2: descommit | Badge > 0 y actividad removida de PS | Sí |
| E3: re-commit | Badge > 0 y actividad reaparece en PS | Sí |
| E4: batch | Badge count refleja cambios múltiples | No (si < 3 act) |
| E5: idempotencia | `total_acciones === 0` | Sí |
| E6: soft restriction | Badge = 0 (sin cambios por blanda) | Sí |
| E7: Terminada | Actividad removida de PS | Sí |

### 6.2 Condiciones de SKIP

- E2: Si no hay actividad con restricciones OK → SKIP (no fatal)
- E3: Si E2 fue SKIP → SKIP automático
- E4: Si no hay 3 actividades → ejecutar con las disponibles (no fatal)
- E6: Si no hay actividad con hard OK + Pdto_Cons > 0 → SKIP (no fatal)
- E7: Si no hay actividad en PS con compromiso → SKIP (no fatal)

### 6.3 Errores fatales (detienen el test)

- Login falla
- PI no carga (no hay `PIHotModule`)
- PS no carga (no hay `PSHotModule`)
- Cascade endpoint retorna error 500

---

## 7. Posibles fallas y mitigación

| Falla | Causa | Mitigación |
|---|---|---|
| `findPIActivityHardOk` retorna null | Todas las actividades ya tienen restricciones rotas | En E1, crear una actividad con restricciones OK manualmente vía API |
| Badge = 0 cuando esperábamos > 0 | Cascade ya se ejecutó previamente (cache) | Re-ejecutar cascade y verificar log |
| Modal no se abre | Selector incorrecto | Verificar `#cm-btn-badge` existe; fallback a selector alternativo |
| Actividad no está en PS | No se auto-committed porque estado PG no es "Debe Comprometer" | Verificar estado PG antes de modificar PI |
| `setDataAtCell` no gatilla auto-save | `afterChange` no se dispara | Forzar save vía `inst.saveRow()` manual o esperar más tiempo |
| Predecesora columna 11 tiene typo `Precesora` vs `Predecesora` | Legacy naming | Verificar prop real en sourceData con `Object.keys(row)` |

---

## 8. Output esperado

```
=== AUTO-PROGRAMAR TEST SUITE (v1.0) ===

✅ Login exitoso — Optimizacion Aeropuerto JMC

========== E1: PI → PS auto-commit ==========
  PI: 85 filas, D_y_E → 100% en actividad #42
  PS: Badge: 3 acciones
  ✅ E1a: Badge visible con count > 0
  ✅ E1b: Modal contiene al menos 1 fila cm-row-comprometer
  ✅ E1c: Actividad #42 aparece en PS con Compromiso=100
📸 e1-ps-auto-commit.png

========== E2: PI → PS descommit ==========
  PI: Actividad #42 con restricciones OK, D_y_E → 0%
  PS: Badge: 1 acción
  ✅ E2a: Fila cm-row-insert_cnp para actividad #42
  ✅ E2b: Actividad #42 con Activa='0' y Categoria_CNP='Programación'
📸 e2-ps-descommit.png

...

========== RESULTADOS ==========
Total: 18 | ✅ Pasaron: 16 | ❌ Fallaron: 2 | ⏭️ SKIP: 1

⚠️ Errores de consola (3):
  Failed to load resource: net::ERR_CONNECTION_REFUSED
  ...

📄 Reporte: test-output-auto-program/REPORTE-AUTO-PROGRAM.md
📸 Screenshots: test-output-auto-program/
```

---

## 9. Apéndice: Estructura del reporte markdown

```markdown
# Reporte de Pruebas — Auto-Programar Cascade

Fecha: 2026-05-29T...
Total: 18 | ✅ Pasaron: 16 | ❌ Fallaron: 2

| # | Resultado | Detalle |
|---|-----------|---|
| 1 | ✅ E1a: Badge visible | count=3 |
| 2 | ✅ E1b: Modal con fila comprometer | row encontrada |
| 3 | ✅ E1c: Actividad en PS con compromiso | Compromiso=100 |
| ... | ... | ... |

### ⚠️ Errores de consola
```
...

### 📸 Screenshots
- e1-ps-auto-commit.png
- e2-ps-descommit.png
- ...
```

---

## 10. Ejecución

```bash
node tests/browser/auto-program.mjs
```

Prerequisitos:
- Docker app running (`docker compose up -d app`)
- DB seeded con proyecto "Optimizacion Aeropuerto JMC"
- Usuario `jbenitez` con acceso al proyecto
- Playwright instalado (dependencia Composer)
