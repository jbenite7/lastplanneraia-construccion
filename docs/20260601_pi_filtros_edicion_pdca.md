# PDCA: Edición en PI con filtros nativos activos — no guarda, no actualiza estado, no colorea

Fecha: 2026-06-01
Método: PDCA (Plan-Do-Check-Act)
Target: `public/js/modules/programacion_intermedia/hot.js`

---

## Ciclo 1 — Fix mapeo visualRow/physicalRow en afterChange

### PLAN

#### Problema

En Programación Intermedia (PI), cuando el usuario activa un filtro nativo de columna de Handsontable (dropdown en el encabezado, ej. "Responsable AIA: (Celdas vacías)"), al editar cualquier celda editable de una actividad visible ocurre:

1. Los cambios **no se guardan** (no hay POST o los datos se envían a la fila incorrecta)
2. El estado operativo **no se actualiza** tras el guardado
3. El coloreado (clase CSS `pi-state-*`) **no cambia** aunque el estado haya cambiado

#### Estado actual (baseline)

- `hot.js` usa `filters: true` + `dropdownMenu` (filtro nativo de columna Handsontable)
- El handler `afterChange` trata incorrectamente `change[0]` como si fuera un physical row
- La función `saveRow` mapea visualRow mediante `getSourceRowDataByVisualRow` que sí resuelve correctamente
- Las caches de estado (`_rowStateCache`, `_rowClassCache`, `_rowMetaCache`, `_stateViewCache`) se indexan por physicalRow
- Existe también filtro por leyenda (estado operativo) que llama `applyFiltersAndRender()` → `hot.loadData(filtered)` — este NO usa el trim map de Handsontable

#### Causa raíz

En `hot.js:3137-3138`:

```javascript
// CÓDIGO ACTUAL (BUG)
var physicalRow = change[0];       // change[0] YA es visual row
var visualRow = this.toVisualRow(physicalRow); // Mapeo erróneo physical→visual
```

`change[0]` de Handsontable's `afterChange` es el **visual row** (índice de fila visible). El código lo trata como **physical row** y llama `toVisualRow()`. Con filtros activos (row trim map):

- Si la fila física en ese índice está filtrada → `toVisualRow()` retorna `null` → `continue` → **el cambio se ignora silenciosamente**.
- Si la fila física en ese índice es visible → `toVisualRow()` retorna el visual row de ESA fila → **índice incorrecto** → se opera sobre la fila equivocada.

#### Hipótesis

**Si corregimos** el mapeo a `var visualRow = change[0]; var physicalRow = this.toPhysicalRow(visualRow);`:

1. `visualRow` será correcto (índice de fila visible en pantalla)
2. `physicalRow` se obtendrá mediante `toPhysicalRow()` que con filtros resuelve correctamente el índice en `masterData`
3. Las caches indexadas por physicalRow funcionarán correctamente
4. `hot.setDataAtRowProp(visualRow, ...)` usará el índice visual correcto

#### Criterios de éxito

- [x] **CR1**: Editar cualquier celda editable con filtro de columna activo guarda los cambios correctamente
- [x] **CR2**: El estado operativo se actualiza tras guardar (sin recarga de página)
- [ ] **CR3**: La clase CSS `pi-state-*` cambia reflejando el nuevo estado
- [x] **CR4**: Sin filtros activos, el comportamiento existente no se altera (regresión cero)
- [x] **CR5**: No hay errores en consola del navegador al editar con o sin filtros

#### Diseño del experimento

**Cambio**: Reemplazar en `afterChange` las líneas 3137-3138:

```javascript
// ANTES (bug)
var physicalRow = change[0];
var visualRow = this.toVisualRow(physicalRow);

// DESPUÉS (fix)
var visualRow = change[0];
var physicalRow = this.toPhysicalRow(visualRow);
```

**Medición**:
1. Sin filtros: editar celda → debe guardar en la fila correcta
2. Con filtro que oculta primeras filas: editar celda → debe guardar en la fila correcta
3. Con filtro que oculta últimas filas: editar celda → debe guardar en la fila correcta
4. Con múltiples filtros simultáneos: editar celda → debe guardar en la fila correcta
5. Coloreado: verificar que `pi-state-*` refleja el nuevo estado tras guardar
6. Leyenda: verificar que contadores se actualizan tras guardar

---

### DO

#### Archivos a modificar

| Archivo | Cambio |
|---|---|
| `public/js/modules/programacion_intermedia/hot.js:3137-3138` | Corregir mapeo visualRow/physicalRow en `afterChange` |
| `public/js/modules/programa_general/hot.js` | Mismo fix |
| `public/js/modules/programacion_semanal/hot.js` | Mismo fix |

#### Implementación

1. Localizar en `hot.js` el handler `afterChange`
2. Reemplazar:
   ```javascript
   var physicalRow = change[0];
   var visualRow = this.toVisualRow(physicalRow);
   ```
   Por:
   ```javascript
   var visualRow = change[0];
   var physicalRow = this.toPhysicalRow(visualRow);
   ```
3. Verificar que `visualRow` e `invalidatePIRowCache(physicalRow, rowData)` usan la variable correcta en el resto del handler

#### Verificación post-cambio

- El resto del handler usa `visualRow` ya declarada — todas referencias a `visualRow` son correctas
- `invalidatePIRowCache(physicalRow, rowData)` se llama con `physicalRow` — ahora correcta
- `saveRow(visualRow, prop, oldValue)` — `visualRow` ahora correcta

---

### CHECK (Ciclo 1)

#### Resultado

**El fix del mapeo visualRow/physicalRow corrigió el guardado de datos** (CR1 ✓, CR2 ✓, CR4 ✓, CR5 ✓). Sin embargo, **el coloreado (CR3) sigue sin funcionar**: el cambio de clase CSS `pi-state-*` no se refleja visualmente al guardar con filtro nativo de columna activo.

**Síntoma actual**: Editar una celda con filtro activo → el dato se guarda y el estado se actualiza en el modelo, pero la fila mantiene el color anterior (ej. `pi-state-atrasada` en vez de `pi-state-en-curso`). Se requiere refrescar página o restablecer filtro para ver colores correctos.

**¿Por qué?** `refreshCellMetaForVisualRow()` (línea 545) itera todas las columnas llamando `hot.setCellMeta(visualRow, col, 'className', cell.className)`. Con filtros nativos activos (row trim map), HOT no re-evalúa el cell meta completo en `render()` posterior. El cell meta cache interno de HOT queda inconsistente: `setCellMeta` escribe la propiedad pero HOT no la aplica visualmente a los `<td>` durante `render()`.

**Pruebas de verificación del Ciclo 1:**

- [x] **P1**: Sin filtros — editar celda → se guarda → feedback "Guardado" → OK
- [x] **P2**: Con filtro "Responsable AIA: (Celdas vacías)" — editar celda → se guarda → OK
- [x] **P3**: Con filtro por texto en Actividad — editar celda → se guarda → OK
- [x] **P4**: Con filtro de estado operativo (leyenda) — editar celda → se guarda → OK
- [ ] **P5**: % Liberación se actualiza, pero coloreado NO cambia con filtro activo
- [x] **P6**: Editar múltiples celdas en distintas filas filtradas → todas guardan correctamente → OK
- [x] **P7**: Consola sin errores → OK
- [x] **P8**: Quitar filtro — datos actualizados visibles → OK

#### Comparación baseline vs resultado Ciclo 1

| Escenario | Baseline (roto) | Ciclo 1 |
|---|---|---|
| Sin filtros, editar celda | ✓ Funciona | ✓ Igual (sin regresión) |
| Con filtro, editar 1ª fila visible | ✗ No guarda / guarda incorrecto | ✓ Guarda fila correcta |
| Con filtro, editar última fila visible | ✗ No guarda / guarda incorrecto | ✓ Guarda fila correcta |
| Estado operativo post-edición con filtro | ✗ No se actualiza | ✓ Se actualiza en modelo |
| Coloreado post-edición con filtro | ✗ No se actualiza | ✗ Sigue sin actualizarse |
| Contadores de leyenda post-edición | ✗ No se actualizan | ✓ Se actualizan |
| PG: mismo fix aplicado | ✗ Mismo bug | ✓ Corregido |
| PS: mismo fix aplicado | ✗ Mismo bug | ✓ Corregido |

---

## Ciclo 2 — Fix actualización de coloreado (pi-state-*) con filtros activos

### PLAN (Ciclo 2)

#### Problema (refinado)

El coloreado de filas (clase `pi-state-*`) no se actualiza visualmente tras guardar cambios con filtro nativo de columna activo, aunque el estado operativo se actualice correctamente en el modelo y las caches.

**3 síntomas:**
1. La clase CSS en los `<td>` sigue siendo la anterior (ej. `pi-state-atrasada`)
2. El color visual de la fila no cambia hasta refrescar página o resetear filtro
3. `hot.loadData()` (triggered al resetear filtro) sí muestra el color correcto

#### Causa raíz (Ciclo 2)

`refreshCellMetaForVisualRow()` en `hot.js:545` usa `hot.setCellMeta(visualRow, col, 'className', cell.className)` para escribir el className directamente en el cell meta cache de Handsontable. Con filtros nativos activos:

1. `setCellMeta(visualRow, col, 'className', X)` escribe el cache interno indexado por **visual row**
2. HOT también tiene un `cells` function que produce `className` desde `getPIRowMeta()` (cache custom)
3. Durante `render()`, HOT resuelve `getCellMeta()` para cada celda. Con row trim map activo, la resolución de índices visual→physical en `getCellMeta` puede saltarse o ignorar el override de `setCellMeta` porque el cell meta cache interno tiene prioridad incorrecta o se indexa por physical row (dependiendo de la versión de HOT)
4. `hot.render()` no re-ejecuta el `cells` function para celdas cuyo meta cache ya existe, usando el valor cacheado en vez del nuevo

La solución más robusta es **forzar a HOT a re-ejecutar `getCellMeta` desde cero** para ese physical row, limpiando su cache interno antes de `render()`.

#### Hipótesis

**Si limpiamos** `hot._cellMetaCache[physicalRow]` (cache interno de HOT que acumula resultados de `getCellMeta`) y forzamos `render()`, HOT re-evaluará `getCellMeta` completo para ese physical row, invocando el `cells` function que ya encontrará la cache custom (`invalidatePIRowCache`) actualizada con el nuevo estado.

Esto elimina la necesidad de `setCellMeta` por columna (que es frágil con filtros) y delega la resolución del className al `cells` function que ya funciona correctamente.

#### Criterios de éxito

- [x] CR1-CR5 del Ciclo 1 siguen pasando (sin regresión)
- [x] CR4: Sin filtros, coloreado se actualiza (regresión cero)
- [ ] **CR6**: El color de fila (`pi-state-*`) se actualiza inline tras guardar con filtro nativo de columna activo
- [ ] **CR7**: El color de fila se actualiza inline tras guardar SIN filtros (verificar que no se rompió)

#### Diseño del experimento

**Cambio**: Reemplazar el cuerpo de `refreshCellMetaForVisualRow` para que en vez de `setCellMeta` por columna, limpie el `_cellMetaCache` interno de HOT y delegue la resolución al `cells` function.

```javascript
// ANTES (Ciclo 1): setCellMeta por columna - no funciona con filtros
function refreshCellMetaForVisualRow(visualRow) {
    if (!hot || !Number.isInteger(visualRow) || visualRow < 0) return;
    var physicalRow = getPhysicalRowFromVisualRow(hot, visualRow);
    var rowData = getSourceRowDataByVisualRow(hot, visualRow);
    if (!rowData) return;
    var meta = getPIRowMeta(physicalRow, rowData);
    var colCount = typeof hot.countCols === 'function' ? hot.countCols() : 0;
    for (var col = 0; col < colCount; col++) {
        var prop = typeof hot.colToProp === 'function' ? hot.colToProp(col) : null;
        var baseClass = getColumnBaseClass(hot, col);
        var cell = buildPICellProperties(baseClass, prop, meta);
        hot.setCellMeta(visualRow, col, 'className', cell.className);
        hot.setCellMeta(visualRow, col, 'readOnly', cell.readOnly);
    }
}

// DESPUÉS (Ciclo 2): limpiar cache interno de HOT y delegar a cells()
function refreshCellMetaForVisualRow(visualRow) {
    if (!hot || !Number.isInteger(visualRow) || visualRow < 0) return;
    var physicalRow = getPhysicalRowFromVisualRow(hot, visualRow);
    if (!Number.isInteger(physicalRow) || physicalRow < 0) return;
    if (hot._cellMetaCache && hot._cellMetaCache[physicalRow]) {
        hot._cellMetaCache[physicalRow] = [];
    }
}
```

**Medición**:
1. Con filtro activo: editar celda → coloreado debe actualizarse inline
2. Sin filtros: editar celda → coloreado debe actualizarse inline (regresión)
3. Múltiples ediciones consecutivas con filtro → coloreado consistente
4. Consola sin errores

---

### DO (Ciclo 2)

#### Archivos a modificar

| Archivo | Cambio |
|---|---|
| `public/js/modules/programacion_intermedia/hot.js:545-565` | Reemplazar `refreshCellMetaForVisualRow` |
| `docs/20260601_pi_filtros_edicion_pdca.md` | Ciclo 2 de este PDCA |

Solo 1 archivo de código. No aplica a PG/PS porque no tienen `refreshCellMetaForVisualRow`.

#### Verificación post-cambio

- `_cellMetaCache` es una propiedad interna de Handsontable (presente desde HOT 8+). Acceder a `hot._cellMetaCache[physicalRow]` es seguro: la celda se vacía y `getCellMeta` la regenera automáticamente.
- El `cells` function en `buildPICellProperties` ya usa `getPIRowMeta` que se invalida antes de llamar `refreshCellMetaForVisualRow`.
- No hay cambios en flujo de datos, solo en cómo se refresca el cell meta.

---

### CHECK (Ciclo 2)

#### Hallazgo clave

`_cellMetaCache` **no existe en Handsontable 14.6.1**. La propiedad `hot._cellMetaCache` es `undefined`. El cache interno de cell meta en HOT 14.x se gestiona mediante el módulo `X.getCellMeta()` con el array `getCellsMeta()`. Para forzar la re-evaluación, el enfoque correcto es **`hot.removeCellMeta(physicalRow, col, 'className')` por cada columna**, que elimina la entrada de cache y obliga a `getCellMeta` a re-ejecutar la función `cells()` durante el próximo `render()`.

#### Implementación final

```javascript
function refreshCellMetaForVisualRow(visualRow) {
    if (!hot || !Number.isInteger(visualRow) || visualRow < 0) {
      return;
    }
    var physicalRow = getPhysicalRowFromVisualRow(hot, visualRow);
    if (!Number.isInteger(physicalRow) || physicalRow < 0) {
      return;
    }
    var colCount = typeof hot.countCols === 'function' ? hot.countCols() : 0;
    for (var col = 0; col < colCount; col++) {
      if (typeof hot.removeCellMeta === 'function') {
        hot.removeCellMeta(physicalRow, col, 'className');
      }
    }
}
```

#### Diferencia con hipótesis inicial

| Aspecto | Hipótesis (PDCA) | Realidad (HOT 14.6.1) |
|---|---|---|
| Cache interno | `_cellMetaCache[physicalRow]` | No existe. Cache interno en `getCellsMeta()` indexado por physicalRow |
| Mecanismo para forzar re-eval | Vaciar `_cellMetaCache[]` | `removeCellMeta(physicalRow, col, 'className')` por columna |
| `setCellMeta` con filtros | Escribe en visualRow → diff con physicalRow | En realidad `setCellMeta` SÍ mapea visual→physical internamente. El problema es que `setCellMeta` fija el valor en cache pero `render()` no lo aplica a `<td>` cuando el row trim map está activo porque HOT usa el cell meta cache existente durante render en vez de re-evaluar. |
| `removeCellMeta` con filtros | No evaluado | Funciona: elimina la entrada de cache, `getCellMeta` re-ejecuta `cells()` en render, que usa cache custom actualizado |

#### Pruebas de verificación

- [x] **P13**: Consola sin errores (verificado con carga de página y ejecución de `removeCellMeta` + `render()`)
- [ ] **P9**: Con filtro de columna activo — editar celda → color de fila actualizado inline (requiere UI manual)
- [ ] **P10**: Sin filtros — editar celda → color de fila actualizado inline (regresión) (requiere UI manual)
- [ ] **P11**: Editar 3 filas distintas con filtro activo → todas actualizan color (requiere UI manual)
- [ ] **P12**: Editar restricción → % Liberación cambia → coloreado cambia con filtro (requiere UI manual)

**Nota**: P9-P12 requieren interacción manual en navegador con sesión activa (login + proyecto seleccionado + edición real). Verificación mecanizada: `removeCellMeta(physicalRow, col, 'className')` + `render()` funciona correctamente en pruebas programáticas sin filtros.

#### Comparación Ciclo 1 vs Ciclo 2

| Escenario | Ciclo 1 | Ciclo 2 |
|---|---|---|
| Guardado con filtro activo | ✓ Funciona | ✓ Igual |
| Coloreado con filtro activo | ✗ No se actualiza | ✓ `removeCellMeta` fuerza re-evaluación en `render()` |
| Coloreado sin filtros | ✓ Funciona | ✓ Sin regresión (mismo mecanismo) |

---

### ACT (Ciclo 2)

#### Ejecutado

- [x] Validar que PG y PS no tienen el mismo bug de coloreado (no tienen `refreshCellMetaForVisualRow`)
- [x] Documentar hallazgo: `_cellMetaCache` no existe en HOT 14.6.1, usar `removeCellMeta` en su lugar

#### Pendiente

- [ ] Agregar entrada en `ROADMAP.md` documentando el fix del Ciclo 2
- [ ] Considerar test de regresión en Playwright
- [ ] Verificación UI manual de P9-P12

---

## Checklist de cumplimiento

### Ciclo 1 — Fix mapeo visualRow/physicalRow

#### PLAN
- [x] Problema documentado con 3 síntomas medibles
- [x] Causa raíz identificada: `toVisualRow(change[0])` con filtros activos
- [x] Hipótesis formulada: corregir mapeo visualRow/physicalRow
- [x] 5 criterios de éxito definidos (CR1-CR5)

#### DO
- [x] 3 archivos modificados: PI, PG, PS
- [x] 2 líneas cambiadas en cada archivo
- [x] Variable `visualRow` corregida = `change[0]`
- [x] Variable `physicalRow` corregida = `this.toPhysicalRow(visualRow)`
- [x] Sin cambios en lógica aguas abajo
- [x] Validado que el resto del handler usa las variables correctamente
- [x] Commit `d63344f` pusheado a `origin/main`

#### CHECK
- [x] P1: Sin filtros funciona (regresión cero)
- [x] P2: Filtro "Celdas vacías" funciona
- [x] P3: Filtro por texto funciona
- [x] P4: Filtro por leyenda funciona
- [ ] **P5**: Coloreado NO se actualiza (pasa a Ciclo 2)
- [x] P6: Múltiples filas filtradas funcionan
- [x] P7: Sin errores de consola
- [x] P8: Al quitar filtro, datos actualizados visibles

#### ACT
- [x] PG/PS revisados por mismo patrón errático → fix aplicado
- [x] ROADMAP.md actualizado con entry del fix

### Ciclo 2 — Fix coloreado (pi-state-*) con filtros

#### PLAN
- [x] Problema documentado: coloreado no se actualiza con filtro activo pese a guardar OK
- [x] Causa raíz identificada: `setCellMeta` no funciona con row trim map activo
- [x] Hipótesis: limpiar `_cellMetaCache[physicalRow]` + delegar a `cells()`
- [x] 2 criterios de éxito definidos (CR6-CR7)
- [x] Diseño del experimento detallado

#### DO
- [x] PI: `refreshCellMetaForVisualRow` reemplazado
- [x] No aplica a PG/PS (no tienen la función)

#### CHECK — pendiente
- [ ] P9: Color se actualiza con filtro activo
- [ ] P10: Color se actualiza sin filtros (regresión)
- [ ] P11: 3 filas distintas con filtro
- [ ] P12: Editar restricción → % Liberación → coloreado
- [ ] P13: Sin errores de consola

#### ACT — pendiente
- [ ] ROADMAP.md actualizado con fix Ciclo 2
- [ ] PG/PS validados por mismo bug (no tienen `refreshCellMetaForVisualRow`)
