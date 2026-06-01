# PDCA: Edición en PI con filtros nativos activos — no guarda, no actualiza estado, no colorea

Fecha: 2026-06-01
Método: PDCA (Plan-Do-Check-Act) Ciclo 1
Target: `public/js/modules/programacion_intermedia/hot.js`

---

## PLAN

### Problema

En Programación Intermedia (PI), cuando el usuario activa un filtro nativo de columna de Handsontable (dropdown en el encabezado, ej. "Responsable AIA: (Celdas vacías)"), al editar cualquier celda editable de una actividad visible ocurre:

1. Los cambios **no se guardan** (no hay POST o los datos se envían a la fila incorrecta)
2. El estado operativo **no se actualiza** tras el guardado
3. El coloreado (clase CSS `pi-state-*`) **no cambia** aunque el estado haya cambiado

### Estado actual (baseline)

- `hot.js` usa `filters: true` + `dropdownMenu` (filtro nativo de columna Handsontable)
- El handler `afterChange` trata incorrectamente `change[0]` como si fuera un physical row
- La función `saveRow` mapea visualRow mediante `getSourceRowDataByVisualRow` que sí resuelve correctamente
- Las caches de estado (`_rowStateCache`, `_rowClassCache`, `_rowMetaCache`, `_stateViewCache`) se indexan por physicalRow
- Existe también filtro por leyenda (estado operativo) que llama `applyFiltersAndRender()` → `hot.loadData(filtered)` — este NO usa el trim map de Handsontable

### Causa raíz

En `hot.js:3137-3138`:

```javascript
// CÓDIGO ACTUAL (BUG)
var physicalRow = change[0];       // change[0] YA es visual row
var visualRow = this.toVisualRow(physicalRow); // Mapeo erróneo physical→visual
```

`change[0]` de Handsontable's `afterChange` es el **visual row** (índice de fila visible). El código lo trata como **physical row** y llama `toVisualRow()`. Con filtros activos (row trim map):

- Si la fila física en ese índice está filtrada → `toVisualRow()` retorna `null` → `continue` → **el cambio se ignora silenciosamente**.
- Si la fila física en ese índice es visible → `toVisualRow()` retorna el visual row de ESA fila → **índice incorrecto** → se opera sobre la fila equivocada.

### Hipótesis

**Si corregimos** el mapeo a `var visualRow = change[0]; var physicalRow = this.toPhysicalRow(visualRow);`:

1. `visualRow` será correcto (índice de fila visible en pantalla)
2. `physicalRow` se obtendrá mediante `toPhysicalRow()` que con filtros resuelve correctamente el índice en `masterData`
3. Las caches indexadas por physicalRow funcionarán correctamente
4. `hot.setDataAtRowProp(visualRow, ...)` usará el índice visual correcto

### Criterios de éxito

- [ ] **CR1**: Editar cualquier celda editable con filtro de columna activo guarda los cambios correctamente
- [ ] **CR2**: El estado operativo se actualiza tras guardar (sin recarga de página)
- [ ] **CR3**: La clase CSS `pi-state-*` cambia reflejando el nuevo estado
- [ ] **CR4**: Sin filtros activos, el comportamiento existente no se altera (regresión cero)
- [ ] **CR5**: No hay errores en consola del navegador al editar con o sin filtros

### Diseño del experimento

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

## DO

### Archivos a modificar

| Archivo | Cambio |
|---|---|
| `public/js/modules/programacion_intermedia/hot.js:3137-3138` | Corregir mapeo visualRow/physicalRow en `afterChange` |

Solo 1 archivo, 2 líneas. Cambio quirúrgico.

### Implementación

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

### Verificación post-cambio

- El resto del handler usa `visualRow` ya declarada (líneas 3142-3208) — todas referencias a `visualRow` son correctas
- `invalidatePIRowCache(physicalRow, rowData)` se llama en línea 3200 con `physicalRow` — ahora correcta
- `saveRow(visualRow, prop, oldValue)` se llama en línea 3208 — `visualRow` ahora correcta

---

## CHECK

### Pruebas de verificación

- [ ] **P1**: Sin filtros — editar celda → se guarda → feedback "Guardado" → OK
- [ ] **P2**: Con filtro "Responsable AIA: (Celdas vacías)" — editar celda → se guarda → OK
- [ ] **P3**: Con filtro por texto en Actividad — editar celda → se guarda → OK
- [ ] **P4**: Con filtro de estado operativo (leyenda) — editar celda → se guarda → OK
- [ ] **P5**: Editar restricción → % Liberación se actualiza → coloreado cambia → OK
- [ ] **P6**: Editar múltiples celdas en distintas filas filtradas → todas guardan correctamente → OK
- [ ] **P7**: Consola sin errores → OK
- [ ] **P8**: Quitar filtro — datos actualizados visibles → OK

### Comparación baseline vs resultado

| Escenario | Baseline (roto) | Esperado |
|---|---|---|
| Sin filtros, editar celda | ✓ Funciona | ✓ Igual (sin regresión) |
| Con filtro, editar 1ª fila visible | ✗ No guarda / guarda incorrecto | ✓ Guarda fila correcta |
| Con filtro, editar última fila visible | ✗ No guarda / guarda incorrecto | ✓ Guarda fila correcta |
| Estado/coloreado post-edición con filtro | ✗ No se actualiza | ✓ Se actualiza inline |
| Contadores de leyenda post-edición | ✗ No se actualizan | ✓ Se actualizan |

---

## ACT

### Si es exitoso

- [ ] Estandarizar: verificar que PG (`public/js/modules/programa_general/hot.js`) y PS (`public/js/modules/programacion_semanal/hot.js`) no tengan el mismo patrón errático en sus handlers `afterChange`
- [ ] Agregar entrada en `ROADMAP.md` documentando el fix
- [ ] Considerar test de regresión en Playwright si existe suite para PI

### Si no es exitoso

- [ ] Revisar si hay otros lugares en `afterChange` o `saveRow` que usen índices incorrectos
- [ ] Revisar si `getSourceRowDataByVisualRow` tiene el mismo error de mapeo (línea 422: `instance.toPhysicalRow(visualRow)` — esta SÍ es correcta)
- [ ] Revisar si PG y PS tienen el mismo bug

### Si es parcialmente exitoso

- [ ] Identificar qué escenarios fallan
- [ ] Ajustar hipótesis y repetir ciclo

---

## Checklist atómico de cumplimiento

### PLAN

- [ ] Problema documentado con 3 síntomas medibles
- [ ] Causa raíz identificada: `toVisualRow(change[0])` con filtros activos
- [ ] Hipótesis formulada: corregir mapeo visualRow/physicalRow
- [ ] 5 criterios de éxito definidos (CR1-CR5)
- [ ] Diseño del experimento detallado con escenarios

### DO

- [ ] Solo 1 archivo modificado: `hot.js`
- [ ] Solo 2 líneas cambiadas (3137-3138)
- [ ] Variable `visualRow` corregida = `change[0]`
- [ ] Variable `physicalRow` corregida = `this.toPhysicalRow(visualRow)`
- [ ] Sin cambios en lógica aguas abajo
- [ ] Validado que el resto del handler usa `visualRow` y `physicalRow` correctamente

### CHECK

- [ ] P1: Sin filtros funciona (regresión cero)
- [ ] P2: Filtro "Celdas vacías" funciona
- [ ] P3: Filtro por texto funciona
- [ ] P4: Filtro por leyenda funciona
- [ ] P5: % Liberación y coloreado se actualizan
- [ ] P6: Múltiples filas filtradas funcionan
- [ ] P7: Sin errores de consola
- [ ] P8: Al quitar filtro, datos actualizados visibles

### ACT

- [ ] PG/PS revisados por mismo patrón errático
- [ ] ROADMAP.md actualizado
- [ ] Regresión validada en PG y PS si aplica cambio

---

## Puertas

- [ ] No cerrar DO sin validar que las variables se usan correctamente en el resto del handler
- [ ] No cerrar CHECK sin pasar las 8 pruebas (P1-P8)
- [ ] No cerrar ACT sin revisar PG y PS
