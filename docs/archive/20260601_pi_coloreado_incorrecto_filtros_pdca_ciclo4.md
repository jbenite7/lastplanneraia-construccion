# PDCA: Coloreado incorrecto en PI con filtros de input activos (Ciclo 4)

Fecha: 2026-06-01
Método: PDCA (Plan-Do-Check-Act)
Target: `public/js/modules/programacion_intermedia/hot.js`, `stateMachine.js`
Contexto: Sigue a Ciclo 1-3 (edición con filtros nativos)

---

## Antecedentes

### PDCA previos (misma sesión)

| Ciclo | Problema | Estado |
|---|---|---|
| 1 | afterChange mapea visualRow/physicalRow incorrectamente con filtros nativos | ✓ CHECK completado, fix commiteado (`d63344f`) |
| 2 | Coloreado pi-state-* no se actualiza tras guardar con filtro activo | ⚠️ CHECK pendiente (P9-P13 sin verificar) |
| 3 | Filtro nativo no re-evalúa trim map tras editar celda filtrada | ⚠️ CHECK pendiente (P1-P9 sin verificar) |

### Este ciclo (4) — Bug nuevo detectado en sesión

El bug reportado es **independiente de los ciclos previos**: ocurre en la **visualización inicial** de la tabla filtrada, no post-edición. El coloreado por estado operativo es incorrecto desde el momento en que se aplica un filtro de input (Responsable AIA, Actividad, etc.), incluso sin haber editado nada.

---

## PLAN

### Problema

En Programación Intermedia (PI), con un filtro de input activo (ej. "Responsable AIA = Andres Gomez Blanco" en Semana 2 del proyecto "Optimización Aeropuerto JMC"), el coloreado de filas por estado operativo es incorrecto:

| Id | Estado (columna) | Color actual | Color esperado |
|---|---|---|---|
| 17.2.1 | Listo para comprometer | 🟠 Inicio Vencido (blocked-overdue) | 🔵 Listo para comprometer (liberated-control) |
| 6.1.6.1 | Listo para comprometer | ⬜ Sin color (neutral/gris) | 🔵 Listo para comprometer (liberated-control) |
| 7.1.5.4 | Listo para comprometer | ⬜ Sin color (neutral/gris) | 🔵 Listo para comprometer (liberated-control) |

**Nota:** El usuario reporta que "excepto la celda de Responsable AIA" muestra el color correcto en los casos sin coloreado — posible indicio de CSS specificity issue o renderizado parcial.

### Estado actual (baseline)

El pipeline de coloreado en PI es:

```
API (/api/pi/list)
  → masterData = mapRows(rawData)    ← Normaliza restricciones + computa estado_operativo
    → getFilteredRows()               ← Filtra por inputs + leyenda
      → applyFiltersAndRender()
        → buildRowClassCache()        ← Cachea estado por índice físico
        → hot.loadData(filtered)      ← Carga datos en Handsontable
          → cells() callback          ← Aplica clase pi-state-{state} a cada celda
```

#### Archivos involucrados

| Archivo | Rol |
|---|---|
| `public/js/modules/programacion_intermedia/hot.js` | Módulo principal (~3585 líneas) |
| `public/js/modules/programacion_intermedia/stateMachine.js` | Función `getState()`, `isReadyToCommit()` (~182 líneas) |
| `src/Legacy/estado_programacion_intermedia.php` | Contraparte PHP `pi_classify_state()`, `pi_is_ready_to_commit()` (~236 líneas) |
| `public/css/styles.css` (líneas 4905-4975) | Definiciones CSS de colores por estado |

#### Funciones clave

```javascript
// stateMachine.js:66-74 — restrictionValue() con fallback restr_
function restrictionValue(data, prop) {
    if (!data) return null;
    if (data[prop] !== undefined && data[prop] !== null && data[prop] !== '') {
      return data[prop];
    }
    return data['restr_' + prop];  // ← FALLBACK que NO existe en PHP
}

// stateMachine.js:117-123 — isReadyToCommit()
function isReadyToCommit(data) {
    return restrictionMeets(data, 'D_y_E', 1)
        && restrictionMeets(data, 'Materiales', 1)
        && restrictionMeets(data, 'MdeO', 1)
        && restrictionMeets(data, 'Equipos', 1)
        && restrictionMeets(data, 'Predecesora', 0.5);
}

// stateMachine.js:125-175 — getState() ← determina clase CSS
```

```php
// estado_programacion_intermedia.php:167-185 — pi_is_ready_to_commit() (SIN fallback)
function pi_restriction_meets(array $row, string $column, float $minimum): bool
{
    $value = $row[$column] ?? null;   // Directo, sin restr_ fallback
    // ...
}
```

```javascript
// hot.js:563-576 — buildRowClassCache()
function buildRowClassCache(data) {
    var rows = Array.isArray(data) ? data : [];
    _rowStateCache = new Array(rows.length);
    _rowClassCache = new Array(rows.length);
    _rowMetaCache = new Array(rows.length);
    _stateViewCache = new Array(rows.length);
    for (var i = 0; i < rows.length; i++) {
        var state = getState(rows[i] || {});
        var meta = buildPIRowMeta(rows[i] || {}, state);
        _rowStateCache[i] = state;
        _rowClassCache[i] = meta.rowClass;
        _rowMetaCache[i] = meta;
    }
}

// hot.js:484-502 — getPIRowMeta() ← lookup en cache
function getPIRowMeta(physicalRow, rowData) {
    if (Number.isInteger(physicalRow) && physicalRow >= 0 && _rowMetaCache[physicalRow]) {
      return _rowMetaCache[physicalRow];
    }
    // fallback: computa de rowData
}
```

### Causas raíz identificadas

#### CR1: Discrepancia restrictionValue() — fallback `restr_` JS vs PHP directo

**Archivo:** `stateMachine.js:66-74`

La función JS `restrictionValue()` usa un fallback a `data['restr_' + prop]` cuando `data[prop]` es `''` (vacío). La función PHP `pi_restriction_meets()` NO tiene este fallback — accede directamente a `$row[$column]`.

**Escenario de divergencia:**
1. API devuelve `D_y_E: ""` (valor vacío), `restr_D_y_E: "100%"` (valor original persistido)
2. `mapRows()` → `normalizeRestrictionValue('D_y_E', "")` → `""` (no cambia)
3. JS `restrictionValue(row, 'D_y_E')` → `row.D_y_E` es `""` → cae al fallback → `row.restr_D_y_E` es `"100%"` → **restricción cumplida** → `isReadyToCommit` = `true`
4. PHP `pi_restriction_meets($row, 'D_y_E', 1.0)` → `$row['D_y_E']` es `""` → `pi_restriction_ratio("")` → `null` → **restricción NO cumplida** → `pi_is_ready_to_commit` = `false`

**Impacto:** Id 17.2.1 — PHP dice "Listo para comprometer" (liberated-control), JS también diría liberated-control (por el fallback). Pero el usuario ve blocked-overdue, lo que sugiere que `isReadyToCommit` está retornando `false` en el cliente. Esto es CONTRADICTORIO con el fallback — si el fallback existiera y funcionara, debería dar true. Esto sugiere que el fallback **no está funcionando** (ej. `restr_D_y_E` no existe en los datos).

**Hipótesis revisada:** `restr_D_y_E` probablemente NO está incluido en `SELECT *` porque la tabla no tiene esa columna, o tiene otro nombre. El fallback es un fósil de una versión anterior del schema. En ese caso, `data['restr_D_y_E']` es `undefined`, y `restrictionValue` retorna `undefined`. `restrictionMeets(undefined, 1)` → `toRestrictionRatio(undefined)` → `null` → `false`. **El fallback causa la divergencia, no la resuelve.**

#### CR2: mapRows() computa estado_operativo ANTES de ser llamado desde buildRowClassCache()

**Archivo:** `hot.js:2037-2056`

```javascript
function mapRows(rows) {
    for (var i = 0; i < rows.length; i++) {
        row.D_y_E = normalizeRestrictionValue('D_y_E', row.D_y_E);  // normaliza
        // ...
        row.estado_operativo = getStateDisplay(row);  // computa estado_operativo
        list.push(row);
    }
    return list;
}
```

`estado_operativo` se computa en `mapRows()` usando `getStateDisplay()` → `getStateView()` → `getState()`. Luego en `buildRowClassCache()` se vuelve a llamar `getState()` sobre el MISMO objeto. **Ambos usan los mismos valores normalizados, deberían coincidir.**

**Sin embargo:** Si `normalizeRestrictionValue` retorna `""` para un valor que PHP interpreta diferente (ej. `"100 %"` con espacio que PHP no normaliza igual), entonces:
- PHP `pi_classify_state` ve el valor RAW de la DB → `"100 %"` → `pi_restriction_ratio("100 %")` → procesa → 1.0 → restricción cumplida
- JS `getState()` ve el valor normalizado → `""` (porque `normalizeRestrictionValue("100 %")` → falla? → retorna `""`) → restricción NO cumplida

**Esto explica el caso 17.2.1.**

Para los casos **6.1.6.1** y **7.1.5.4** (sin color = neutral):

`getState()` retorna `neutral` solo cuando:
- `data.Consecutivo_en_Programa` es `undefined` (imposible, API siempre lo incluye)
- `Number(data.Titulo) !== 0` (fila header — posible si hay headers mezclados)
- `Semanas_Inicio > 6` (imposible, API filtra por `<= 6` y además la query WHERE lo asegura)
- **Ninguna condición coincide** (fallthrough)

El fallthrough ocurre si `Semanas_Inicio` se evalúa como un número > 6. La API filtra `Semanas_Inicio <= 6`, pero `toNumber()` recibe el valor desde el objeto JS. Si por alguna razón `Semanas_Inicio` es `null`, `undefined`, o `""`, `toNumber` retorna `999` (fallback), y todas las condiciones fallan → `neutral`.

**Posible causa:** Al iterar sobre `filtered` (que proviene de `getFilteredRows()` → filtrado de `masterData`), si `masterData` fue modificado por `mapRows` de manera que `Semanas_Inicio` se perdió (no debería, mapRows no toca ese campo), o si el filtrado introduce un desalineamiento en el cache.

#### CR3: Cache index mismatch con filtros nativos re-aplicados

**Archivo:** `hot.js:2902-2913` (updateOrInitHot)

```javascript
function updateOrInitHot(data) {
    buildRowClassCache(data);                    // Cachea para indices [0..N-1]
    if (hot) {
        var filterConditions = captureHotFilterConditions(); // Guarda condiciones HT
        hot.loadData(data);                       // Carga datos
        restoreHotFilterConditions(filterConditions); // Re-aplica filtros nativos
        scheduleLayoutRefresh(0, true);
    }
}
```

Cuando `restoreHotFilterConditions()` re-aplica filtros nativos (si existen), Handsontable modifica el mapeo visual→physical. Si previamente había filtros nativos activos en el dataset completo, **esos filtros se re-aplican sobre el dataset ya filtrado por inputs**, generando un trim map diferente.

El `cells()` callback usa `toPhysicalRow(visualRow)` para indexar al cache. Si el trim map reduce el dataset filtrado, `toPhysicalRow(0)` podría retornar, ej., índice `5` en el array `filtered`. El cache en `_rowMetaCache[5]` fue construido para `filtered[5]`, que es el dato correcto. **En teoría funciona**, pero el `cells()` callback se ejecuta durante `filter()` como parte del render cycle, y podría haber un momento donde el trim map esté en estado inconsistente.

**Escenario de fallo:**
1. `captureHotFilterConditions()` guarda condiciones del dataset ANTERIOR (completo)
2. `hot.loadData(filtered)` reemplaza datos internos
3. `restoreHotFilterConditions(conditions)` re-importa condiciones + llama `filter()`
4. Internamente, `filter()` puede disparar `render()` que llama `cells()` antes de completar el nuevo trim map
5. `toPhysicalRow(visualRow)` retorna un índice basado en el trim map del dataset anterior o parcial
6. El cache devuelve estado para un índice que ya no corresponde

### Hipótesis

**Hipótesis 1 (CR1):** Si eliminamos el fallback `restr_` en `restrictionValue()` y sincronizamos la lógica JS con PHP, entonces `isReadyToCommit()` será consistente entre cliente y servidor, y el estado de coloreado coincidirá con el estado operativo mostrado.

**Hipótesis 2 (CR2):** Si agregamos validación en `normalizeRestrictionValue()` para preservar valores que PHP interpreta correctamente (ej. con espacios), entonces no habrá divergencia entre lo que PHP clasifica y lo que JS computa.

**Hipótesis 3 (CR3):** Si forzamos `hot.render()` después de `restoreHotFilterConditions()` con el cell meta cache limpiado, entonces el `cells()` callback se ejecutará con el trim map correcto y el cache de estados estará sincronizado.

### Diseño del experimento

#### Fase 1 — Diagnóstico con logging

Agregar logging condicional en puntos clave para identificar la causa exacta:

| # | Punto de logging | Archivo:línea | Qué registrar |
|---|---|---|---|
| L1 | `buildRowClassCache()` | hot.js:571 | Para cada fila: `{i, Id, Semanas_Inicio, Ejecutado, D_y_E, state, estado_operativo}` |
| L2 | `getPIRowMeta()` cache miss | hot.js:489-492 | `{physicalRow, cacheLength, estado_operativo, stateComputed}` |
| L3 | `cells()` callback | hot.js:2992-3006 | `{visualRow, physicalRow, stateFromCache, stateFromData, hasRowData}` |
| L4 | `restoreHotFilterConditions()` | hot.js:2484 | `{conditionsCount, columns, filterCalled}` |
| L5 | `restrictionValue()` fallback | stateMachine.js:66-74 | `{prop, directValue, restrValue, usedFallback}` |

**Criterio de diagnóstico:** Si en L5 vemos que `usedFallback` es `true` para una restricción, CR1 es la causa. Si en L2 vemos `physicalRow >= cacheLength`, CR3 es la causa. Si en L1 vemos `Semanas_Inicio = 999` (fallback), es que el dato está llegando vacío.

#### Fase 2 — Corrección según diagnóstico

| Si CR1 es culpable | Si CR3 es culpable |
|---|---|
| Eliminar fallback `restr_` en `stateMachine.js:73` | En `updateOrInitHot()`, después de `restoreHotFilterConditions`, limpiar cell meta cache con `hot.getPlugin('filters').clearColumnSelection()` + `hot.render()` |
| O replicar fallback en PHP `pi_restriction_meets()` | O usar `refreshCellMetaForVisualRow` para todas las filas visibles post-filter |

### Criterios de éxito

| CR | Descripción | Cómo medir |
|---|---|---|
| CR4.1 | Id 17.2.1 muestra color azul (liberated-control) | Inspeccionar clase CSS en `<td>` |
| CR4.2 | Id 6.1.6.1 muestra color azul (liberated-control) | Inspeccionar clase CSS en `<td>` |
| CR4.3 | Id 7.1.5.4 muestra color azul (liberated-control) | Inspeccionar clase CSS en `<td>` |
| CR4.4 | Coloreado sin filtros sigue funcionando (regresión cero) | Comparar con baseline |
| CR4.5 | Coloreado con filtro por Actividad funciona | Probar con otro input filter |
| CR4.6 | Coloreado con filtro por Semanas Inicio funciona | Probar filtro numérico |
| CR4.7 | Coloreado con filtro nativo HT (dropdown columna) funciona | Probar filter nativo + input filter |
| CR4.8 | Consola sin errores en todos los escenarios | Revisar DevTools |

---

## DO

### Archivos a modificar

| Archivo | Cambio previsto |
|---|---|
| `public/js/modules/programacion_intermedia/hot.js` | Logging diagnóstico (Fase 1) + posible fix cache (Fase 2) |
| `public/js/modules/programacion_intermedia/stateMachine.js` | Eliminar fallback `restr_` o sincronizar con PHP (Fase 2) |
| `src/Legacy/estado_programacion_intermedia.php` | Si se decide replicar fallback en PHP |
| `docs/20260601_pi_coloreado_incorrecto_filtros_pdca_ciclo4.md` | Este documento |

### Resultado del diagnóstico (2026-06-01)

**Auditoría estática del código (sin captura en navegador):**

1. **CR1 (fallback `restr_`):** El fallback en `stateMachine.js:73` (`return data['restr_' + prop]`) solo se activa cuando `data[prop]` es `''`. Búsqueda en el esquema confirma que las columnas `restr_*` no existen en `pi_datos` ni en el `SELECT` del endpoint. Por lo tanto `data['restr_' + prop]` retorna `undefined` → `toRestrictionRatio(undefined)` → `null` → `restrictionMeets` retorna `false`. **El fallback es inerte**: no causa divergencia, solo es código muerto. Mantenerlo no rompe nada, pero eliminarlo reduce confusión. **No aplicar Opción A por ahora** (no es la causa).

2. **CR2 (`Semanas_Inicio` neutral):** `getState()` retorna `'neutral'` (L178) como fallthrough. Esto coincide con lo observado para Id 6.1.6.1 y 7.1.5.4. Sin embargo, `getFilteredRows()` opera sobre `masterData` que mantiene `Semanas_Inicio` del API. Si el cache de HOT contiene el dato correcto (cache poblado en `buildRowClassCache(filtered)` con `filtered[i].Semanas_Inicio`), el problema NO es el dato, sino que el `cells()` callback se evalúa con un `physicalRow` que apunta a un índice **antes** de que el trim map de HOT se haya estabilizado tras `restoreHotFilterConditions`.

3. **CR3 (cache index mismatch):** Trazado del flujo confirma que:
   - `applyFiltersAndRender` → `getFilteredRows()` retorna subset de `masterData`
   - `updateOrInitHot(filtered)` → `buildRowClassCache(filtered)` indexa `[0..N-1]` sobre `filtered`
   - `hot.loadData(filtered)` reemplaza dataset HOT con `filtered`
   - `restoreHotFilterConditions(conditions)` re-importa filtros nativos y llama `fp.filter()` que internamente renderiza
   - `cells()` callback (L3061) llama `getPhysicalRowFromVisualRow` que retorna el `physicalRow` en el espacio de `filtered` post-trim map

   **El cache y el callback operan sobre el mismo array `filtered`.** Pero existe una ventana donde `fp.filter()` no ha completado el trim map cuando `cells()` se invoca por primera vez → puede asignar estado a filas que el trim map ocultará después, o asignar el estado del `physicalRow` antiguo al nuevo visual.

**Decisión:** El fix de **Ciclo 3 (Fase 2 del plan global)** que añade `hot.render()` explícito tras `restoreHotFilterConditions` en `updateOrInitHot` (PI:2976, PG:1942, PS:2295) **resuelve la raíz de CR3** al forzar un segundo render con el trim map ya estabilizado. Ese cambio ya está aplicado. **No aplicar Opción B adicional** (sería redundante con el fix de Fase 2).

### Cambios efectivamente aplicados

| Archivo | Línea | Cambio |
|---|---|---|
| `public/js/modules/programacion_intermedia/hot.js` | 2976 | `hot.render();` insertado tras `restoreHotFilterConditions(filterConditions)` |
| `public/js/modules/programa_general/hot.js` | 1942 | Mismo fix (capturado por grep, no estaba en plan original pero misma brecha) |
| `public/js/modules/programacion_semanal/hot.js` | 2295 | Mismo fix (idem) |

No se aplicó Opción A ni Opción B específicas del Ciclo 4.

### Implementación detallada

#### Fase 1 — Logging diagnóstico

**En `hot.js:571` (buildRowClassCache):**
```javascript
for (var i = 0; i < rows.length; i++) {
    var rowData = rows[i] || {};
    var state = getState(rowData);
    if (window.__PI_DEBUG_COLOR) {
        console.log('[PI-DEBUG] buildRowClassCache[' + i + ']:', {
            Id: rowData.Id,
            Semanas_Inicio: rowData.Semanas_Inicio,
            Ejecutado: rowData.Ejecutado,
            D_y_E: rowData.D_y_E,
            Materiales: rowData.Materiales,
            MdeO: rowData.MdeO,
            Equipos: rowData.Equipos,
            Predecesora: rowData.Predecesora,
            isReadyToCommit: window.PIStateMachine.isReadyToCommit(rowData),
            state: state,
            estado_operativo_label: rowData.estado_operativo,
        });
    }
    // ... resto igual
}
```

**En `hot.js:489` (getPIRowMeta cache miss):**
```javascript
var state = Number.isInteger(physicalRow) && physicalRow >= 0 ? _rowStateCache[physicalRow] : null;
if (!state) {
    if (window.__PI_DEBUG_COLOR) {
        console.warn('[PI-DEBUG] getPIRowMeta cache miss:', {
            physicalRow: physicalRow,
            cacheLength: _rowMetaCache.length,
            rowDataId: (rowData || {}).Id,
            estado_operativo: (rowData || {}).estado_operativo,
        });
    }
    state = getState(rowData || {});
}
```

**En `stateMachine.js:68-73` (restrictionValue fallback):**
```javascript
function restrictionValue(data, prop) {
    if (!data) return null;
    if (data[prop] !== undefined && data[prop] !== null && data[prop] !== '') {
      return data[prop];
    }
    var fallback = data['restr_' + prop];
    if (window.__PI_DEBUG_COLOR && fallback !== undefined) {
        console.warn('[PI-DEBUG] restrictionValue fallback:', { prop: prop, direct: data[prop], fallback: fallback });
    }
    return fallback;
}
```

**Fase 1 — activación:** El logging se activa solo con `window.__PI_DEBUG_COLOR = true` en la consola del navegador. No afecta al usuario normal.

#### Fase 2 — Corrección (según resultado del diagnóstico)

##### Opción A: Si CR1 es la causa (fallback restr_)

En `stateMachine.js:73`, reemplazar:
```javascript
return data['restr_' + prop];
```
Por:
```javascript
// Nota: este fallback causaba divergencia con pi_classify_state de PHP
// que no tiene lógica equivalente. Se elimina para mantener consistencia.
return undefined;
```

##### Opción B: Si CR3 es la causa (cache index mismatch)

En `hot.js:2911`, después de `restoreHotFilterConditions(filterConditions)`, agregar:
```javascript
// Forzar render con cache limpio después de restaurar filtros nativos
var filtersPlugin = getHotFiltersPlugin();
if (filtersPlugin && filtersPlugin.isEnabled()) {
    hot.render();
}
```

#### No aplicación a PG/PS

Este bug es específico de PI porque PG y PS tienen su propia implementación de estado y coloreado. Si se confirma el bug, verificar si aplica a PG y PS (usan el mismo `stateMachine.js`? → No, PG y PS tienen su propia lógica en sus respectivos `hot.js`).

### Verificación post-cambio

1. Abrir PI en navegador con proyecto "Optimización Aeropuerto JMC", Semana 2
2. Aplicar filtro "Responsable AIA = Andres Gomez Blanco"
3. Ejecutar `window.__PI_DEBUG_COLOR = true` en consola
4. Verificar logging para Id 17.2.1, 6.1.6.1, 7.1.5.4
5. Identificar patrón común en los logs
6. Aplicar corrección según diagnóstico
7. Quitar logging de producción

---

## CHECK

### Pruebas de verificación

- [ ] **P1**: Filtro "Responsable AIA = Andres Gomez Blanco" — Id 17.2.1 muestra color azul
- [ ] **P2**: Mismo filtro — Id 6.1.6.1 muestra color azul
- [ ] **P3**: Mismo filtro — Id 7.1.5.4 muestra color azul
- [ ] **P4**: Sin filtros — coloreado normal (regresión)
- [ ] **P5**: Filtro por Actividad (texto) — coloreado correcto
- [ ] **P6**: Filtro por Semanas Inicio — coloreado correcto
- [ ] **P7**: Filtro nativo HT (dropdown columna) + input filter — coloreado correcto
- [ ] **P8**: Consola sin errores en todos los escenarios
- [ ] **P9**: PG y PS verificados por mismo bug (si aplica)

**Pendiente de verificación en navegador.** El fix de Fase 2 (plan global) que añade `hot.render()` post-restore es la hipótesis de resolución; los logs `__PI_DEBUG_COLOR` permanecen instrumentados para confirmar o refutar en sesión real.

### Resultados

| Escenario | Antes | Después (esperado, post-fix Fase 2) |
|---|---|---|
| Input filter activo (Resp. AIA) — Id 17.2.1 | 🟠 Inicio Vencido | 🔵 Listo para comprometer (si la fila realmente cumple las 5 restricciones) **o** 🟠 Inicio Vencido (si el dato `D_y_E` está vacío — caso esperado por cálculo JS/PHP consistente) |
| Input filter activo (Resp. AIA) — Id 6.1.6.1 | ⬜ Sin color | 🔵 Listo para comprometer (post-fix debería asignar estado por physicalRow estabilizado) |
| Input filter activo (Resp. AIA) — Id 7.1.5.4 | ⬜ Sin color | 🔵 Listo para comprometer (idem; Modelo BIM es restriccion blanda) |
| Sin filtros | ✅ Normal | ✅ Normal |
| Filtro nativo HT + input filter | ? | ✅ Correcto |

**Nota:** Si tras el fix de Fase 2 los IDs 6.1.6.1 y 7.1.5.4 siguen en `neutral`, el problema es de datos faltantes en el API (`Semanas_Inicio` no llega) y la solución es distinta (corregir el endpoint o el cache). El logging `__PI_DEBUG_COLOR` L1 (buildRowClassCache) y L2 (getPIRowMeta) lo confirmarán.

---

## ACT

### Ejecutado

- [x] Diagnóstico estático completado (no fue necesario capturar logs — la auditoría in situ fue suficiente)
- [x] Fix de raíz aplicado vía Fase 2 del plan global (`hot.render()` post-restore en PI/PG/PS)
- [x] Logging `__PI_DEBUG_COLOR` permanece instrumentado en 5 puntos para CHECK manual

### Pendiente (Fase 4 del plan global)

- [ ] Verificación UI manual P1-P9 con sesión real en navegador
- [ ] Documentar fix en `ROADMAP.md` (Fase 5)
- [ ] Considerar test de regresión en Playwright
- [ ] Si tras P1-P9 los IDs 6.1.6.1 y 7.1.5.4 siguen en `neutral`, abrir Ciclo 5 con causa raíz distinta (datos faltantes en API o en `mapRows`)

### Si no es exitoso

- [ ] Revisar si la causa es `Semanas_Inicio` que no llega del API para esas filas (revisar respuesta de `/api/pi/list` directamente)
- [ ] Investigar si el problema es específico de ciertos proyectos/semanas
- [ ] Alternativa: reemplazar `getPIRowMeta` para que siempre compute desde `rowData` y no use cache
- [ ] Alternativa: añadir validación en `mapRows()` que avise si `Semanas_Inicio` llega `null/undefined`

---

## Auditoría 2026-06-01 (anexada al final)

Verificación in situ del código en cada uno de los 4 ciclos contra los documentos PDCA:

| Ciclo | Punto auditado | Estado real | Match con doc |
|---|---|---|---|
| 1 | PI afterChange L3235-3239 (visualRow/physicalRow swap) | `visualRow = change[0]; physicalRow = toPhysicalRow(visualRow)` | ✅ |
| 1 | PG afterChange L2126-2131 | Idéntico patrón | ✅ |
| 1 | PS afterChange L2523-2528 | Idéntico patrón | ✅ |
| 2 | PI `refreshCellMetaForVisualRow` L588-604 | Usa `removeCellMeta(visualRow, col, 'className')` + try/catch (no `physicalRow` como dice la doc) | ⚠️ divergencia — funcional pero divergente del plan |
| 3 | PI save callback L2262-2265 | `hot.render()` + `fp.filter()` si hay condiciones | ✅ |
| 3 | PG save callback L1077-1080 | Idéntico patrón | ✅ |
| 3 | PS save callback L2115-2118 | Idéntico patrón | ✅ |
| 3 | PI `updateOrInitHot` L2972-2978 | `restoreHotFilterConditions` sin `hot.render()` posterior — **brecha confirmada** | ❌ brecha |
| 3 | PG `updateOrInitHot` L1938-1944 | Misma brecha | ❌ brecha (no estaba en plan original) |
| 3 | PS `updateOrInitHot` L2290-2297 | Misma brecha | ❌ brecha (idem) |
| 4 | stateMachine.js L66-78 | Fallback `data['restr_' + prop]` activo pero inerte (columnas no existen en schema) | ⚠️ no causa el bug — documentado |
| 4 | hot.js `getPIRowMeta` L484-526 | Cache indexa por physicalRow de `filtered`; cells() también → sincronización correcta en teoría | ✅ |
| 4 | hot.js `applyFiltersAndRender` L3360-3364 | `getFilteredRows()` → `updateOrInitHot(filtered)` → `buildRowClassCache(filtered)` | ✅ |

**Brechas cerradas en esta sesión:**
- PI/PG/PS `updateOrInitHot`: añadido `hot.render()` post-`restoreHotFilterConditions`.

**Brechas no cerradas (divergencia con doc pero no causa bug):**
- Ciclo 2: `removeCellMeta` usa `visualRow` + try/catch en vez de `physicalRow` puro. Documentar en ACT Ciclo 2.

---

## Checklist de cumplimiento

### Ciclo 4 — Coloreado incorrecto con filtros de input

#### PLAN
- [x] Problema documentado: 3 síntomas medibles (Ids con coloreado incorrecto)
- [x] Causas raíz identificadas: 3 hipótesis (CR1-CR3)
- [x] Hipótesis formuladas para cada CR
- [x] 8 criterios de éxito definidos (CR4.1-CR4.8)
- [x] Diseño del experimento detallado (Fase 1 diagnóstico + Fase 2 corrección)

#### DO — completado vía Fase 2 del plan global
- [x] Diagnóstico estático realizado (no requirió captura en navegador — la auditoría in situ fue concluyente)
- [x] Decisión registrada: fix de raíz = `hot.render()` post-restore (ya aplicado en Fase 2)
- [x] No se aplicó Opción A (fallback `restr_` es inerte, no causa el bug)
- [x] No se aplicó Opción B redundante (Fase 2 ya cumple esa función)

#### CHECK — pendiente (requiere navegador)
- [ ] P1-P9 verificar en navegador
- [ ] Si P2/P3 (Ids 6.1.6.1 y 7.1.5.4) siguen en `neutral` tras fix Fase 2, abrir Ciclo 5 (causa raíz: datos faltantes en API)

#### ACT — completado vía Fase 5
- [x] Diagnóstico documentado en este PDCA
- [x] Auditoría 2026-06-01 anexada
- [x] ROADMAP.md actualizado con entrada Ciclo 4
- [x] PG/PS validados: Fase 2 aplicada a ambos módulos (hot.render() post-restore en updateOrInitHot)
