# PDCA: Re-evaluación automática de filtros nativos tras editar celda filtrada

Fecha: 2026-06-01
Método: PDCA (Plan-Do-Check-Act)
Target: `public/js/modules/programacion_intermedia/hot.js`

---

## Ciclo 3 — Re-aplicar filtro nativo de columna tras guardado exitoso

### PLAN

#### Problema

Cuando el usuario activa un filtro nativo de columna en Handsontable (ej. "Responsable AIA: (Celdas vacías)") y edita directamente **la celda sobre la que está filtrado** (ej. asigna un responsable a una actividad que estaba vacía), la fila editada **permanece visible** aunque el nuevo valor ya no coincida con la condición del filtro.

**Comportamiento esperado:** La fila debería desaparecer del listado filtrado porque ya no cumple la condición (el responsable ya no está vacío).

#### Síntomas

1. Filtro activo: "Responsable AIA = (Celdas vacías)" → se muestran N filas sin responsable
2. Usuario edita "Responsable AIA" en una fila visible → asigna un responsable
3. POST exitoso → la fila **sigue visible** con el nuevo responsable
4. Usuario debe resetear el filtro manualmente o recargar la página para que la fila desaparezca

#### Estado actual (baseline)

- Ciclo 1 completado: `afterChange` corrigió mapeo visualRow/physicalRow — guardado funciona con filtros
- Ciclo 2 completado: `refreshCellMetaForVisualRow` usa `removeCellMeta` — coloreado se actualiza inline
- **El row trim map del plugin `filters` nunca se re-evalúa tras un guardado exitoso**
- Flujo post-guardado: `render()` → `updateLegendCounts()` → `showFeedback()`
- El trim map queda congelado con las condiciones que existían ANTES de la edición
- `filters.filter()` re-evaluaría el trim map contra los datos actualizados, pero nunca se llama

#### Causa raíz

En el callback `.done()` del save AJAX (hot.js ~line 2202), tras `hot.render()` no existe ninguna llamada a `filtersPlugin.filter()`. El row trim map de Handsontable solo se actualiza cuando:
1. El usuario interactúa con el dropdown de filtro (selecciona una condición)
2. Se llama explícitamente `filtersPlugin.filter()`
3. Se recarga la página

Ninguno de estos ocurre automáticamente tras `setDataAtRowProp()`, aunque el dato subyacente cambie.

#### Hipótesis

**Si llamamos** `filtersPlugin.filter()` después de `hot.render()` en el callback exitoso del save, **entonces** el row trim map se re-evaluará contra los datos actualizados. Si la fila editada ya no cumple la condición del filtro activo, será ocultada automáticamente.

**Fundamento:** `filtersPlugin.filter()` (HOT 14.6.1):
1. Toma todas las condiciones activas de `conditionCollection.exportAllConditions()`
2. Crea un data filter con `this._createDataFilter()`
3. Evalúa cada fila contra las condiciones → produce array de visual rows que matchean
4. Marca como ocultas (vía `filtersRowsMap`) las filas que NO matchean
5. Llama `this.hot.render()` internamente

Dado que `setDataAtRowProp()` ya actualizó la fuente de datos (`hot.getSourceData()`), el data filter encontrará los nuevos valores.

#### Diseño del experimento

**Cambio único:** Insertar re-evaluación de filtros tras el render post-guardado.

```javascript
// ANTES (Ciclo 2):
hot.render();
updateLegendCounts(getFilteredRows());
showFeedback('success', 'Guardado');

// DESPUÉS (Ciclo 3):
hot.render();

var fp = hot.getPlugin('filters');
if (fp && fp.isEnabled() && fp.conditionCollection && !fp.conditionCollection.isEmpty()) {
    fp.filter();
}

updateLegendCounts(getFilteredRows());
showFeedback('success', 'Guardado');
```

#### Criterios de éxito

- [ ] **CR1**: Editar una celda en una columna con filtro activo → si el nuevo valor no coincide, la fila desaparece
- [ ] **CR2**: Editar una celda en una columna SIN filtro activo → la fila NO desaparece (no hay condiciones que re-evaluar)
- [ ] **CR3**: Editar múltiples filas consecutivas con filtro activo → cada una desaparece si corresponde
- [ ] **CR4**: Sin filtros activos, editar cualquier celda → comportamiento normal (sin regresión)
- [ ] **CR5**: Consola sin errores en todos los escenarios
- [ ] **CR6**: Ciclo 1 (guardado) y Ciclo 2 (coloreado) siguen funcionando

#### Medición

| Escenario | Baseline | Post-fix (esperado) |
|---|---|---|
| Filtro "Responsable AIA: vacío" + editar responsable → asignar valor | Fila sigue visible | Fila desaparece |
| Filtro "Responsable AIA: vacío" + editar % Liberación | Fila visible (correcto, no afecta filtro) | Fila visible (sin cambio) |
| Sin filtros + editar cualquier celda | Guarda normal | Guarda normal (sin regresión) |
| Filtro por texto "Actividad contiene 'rampa'" + editar nombre actividad | Fila sigue visible | Fila desaparece si ya no contiene 'rampa' |
| Múltiples filtros simultáneos + editar columna filtrada | Fila visible | Fila desaparece si no cumple TODOS los filtros |

---

### DO

#### Archivos a modificar

| Archivo | Cambio |
|---|---|
| `public/js/modules/programacion_intermedia/hot.js` (~line 2202) | Insertar re-evaluación de filtros tras `hot.render()` |
| `public/js/modules/programa_general/hot.js` | Mismo fix si aplica |
| `public/js/modules/programacion_semanal/hot.js` | Mismo fix si aplica |
| `docs/20260601_pi_filtros_edicion_pdca.md` | Agregar Ciclo 3 |
| `docs/20260601_pi_filtros_edicion_pdca_ciclo3.md` | Este documento |

#### Implementación detallada

En `hot.js:2202`, reemplazar:

```javascript
        hot.render();
        updateLegendCounts(getFilteredRows());
        showFeedback('success', 'Guardado');
```

Por:

```javascript
        hot.render();

        var fp = hot.getPlugin('filters');
        if (fp && fp.isEnabled() && fp.conditionCollection && typeof fp.conditionCollection.isEmpty === 'function' && !fp.conditionCollection.isEmpty()) {
            fp.filter();
        }

        updateLegendCounts(getFilteredRows());
        showFeedback('success', 'Guardado');
```

**Nota técnica:** `fp.filter()` internamente llama `this.hot.render()` (verificado en HOT 14.6.1), por lo que podría haber doble render si hay filtros activos. Sin embargo, como `filter()` se ejecuta sincrónicamente dentro de `batchExecution` (suspendRender → work → resumeRender → render), el segundo render es correcto y no produce flicker porque el browser no ha pintado entre medias.

#### Verificación post-cambio

- `conditionCollection.isEmpty()` retorna `true` si no hay filtros activos → no hay sobrecarga en el caso común (sin filtros)
- `fp.filter()` solo se llama si hay condiciones activas
- `getFilteredRows()` (usado por `updateLegendCounts`) debe llamarse DESPUÉS de `fp.filter()` para que cuente correctamente
- El orden: `render()` → (opcional) `filter()` → `updateLegendCounts()`

---

### CHECK

#### Pruebas de verificación

- [ ] **P1**: Filtro "Responsable AIA = (Celdas vacías)" — editar responsable en una fila → la fila desaparece
- [ ] **P2**: Filtro activo + editar columna NO filtrada (ej. % Liberación) → la fila permanece visible
- [ ] **P3**: Sin filtros — editar celda → guarda normal (regresión Ciclo 1)
- [ ] **P4**: Sin filtros — editar celda → coloreado se actualiza (regresión Ciclo 2)
- [ ] **P5**: Filtro por texto "Actividad contiene 'rampa'" — editar actividad → si ya no contiene 'rampa', desaparece
- [ ] **P6**: Múltiples filtros simultáneos — editar columna filtrada → desaparece si no cumple TODOS
- [ ] **P7**: Editar 3 filas consecutivas con filtro activo → cada una se comporta correctamente
- [ ] **P8**: Consola sin errores en todos los scenarios
- [ ] **P9**: PG y PS con el mismo fix (si aplica) — mismos escenarios

#### Resultados esperados (por confirmar)

| Escenario | Antes | Después (esperado) |
|---|---|---|
| Editar columna filtrada — valor deja de coincidir | Fila visible (bug) | Fila desaparece |
| Editar columna filtrada — valor sigue coincidiendo | Fila visible | Fila visible (correcto) |
| Editar columna NO filtrada | Fila visible | Fila visible (sin cambio) |
| Sin filtros activos | Funciona normal | Funciona normal |
| Coloreado post-edición | Se actualiza (Ciclo 2) | Se actualiza (sin regresión) |

---

### ACT

#### Ejecutado vía Fase 2 + Fase 5

- [x] Validar PG y PS: el callback equivalente existe en `programa_general/hot.js:1077-1080` y `programacion_semanal/hot.js:2115-2118`. Mismo patrón aplicado en ambos.
- [x] **Fix complementario detectado en auditoría 2026-06-01**: `updateOrInitHot` también requiere `hot.render()` tras `restoreHotFilterConditions` cuando se re-cargan datos (no solo en save callback). Aplicado en PI:2976, PG:1942, PS:2295. Sin este render, el row trim map queda desincronizado con el cell meta cache al recargar con filtros nativos activos.
- [x] Entrada en `ROADMAP.md` (ver hito "Re-evaluación Automática de Filtros Nativos Tras Editar Celda Filtrada — Ciclo 3")
- [x] Documentar comportamiento: "Al editar una celda filtrada, el filtro se re-evalúa automáticamente. Al recargar con filtros nativos activos, el render se fuerza para estabilizar el row trim map."

#### Pendiente (Fase 4 del plan global)

- [ ] Verificación UI manual P1-P9 con sesión real en navegador

#### Si no es exitoso

- [ ] Revisar si `fp.filter()` causa efecto secundario inesperado (pérdida de selección, scroll)
- [ ] Alternativa: reemplazar `fp.filter()` con `fp.conditionCollection.clean()` + `fp.filter()`
- [ ] Alternativa: solo re-aplicar filtro si la columna editada tiene condición activa (detectar via `fp.conditionCollection.getFilteredColumns()`)
- [ ] Alternativa: usar `hot.getPlugin('hiddenRows')` directamente en vez del trim map

#### Si es parcialmente exitoso

- [ ] Identificar con qué tipos de filtro falla (by_value vs by_condition vs by_function)
- [ ] Ajustar estrategia por tipo de filtro

---

## Checklist de cumplimiento

### Ciclo 3 — Re-evaluación de filtros tras guardado

#### PLAN
- [x] Problema documentado: fila no desaparece tras editar columna filtrada
- [x] Causa raíz identificada: `filter()` nunca llamado tras guardado
- [x] Hipótesis formulada: llamar `fp.filter()` tras `hot.render()` re-evalúa trim map
- [x] 6 criterios de éxito definidos (CR1-CR6)
- [x] Diseño del experimento detallado

#### DO — completado ✓
- [x] PI: código modificado (~line 2202)
- [x] PG: mismo fix aplicado (hot.js:1077-1080)
- [x] PS: mismo fix aplicado (hot.js:2115-2118)
- [x] PDCA actualizado (ROADMAP.md + este checklist)

#### CHECK — pendiente (requiere navegador)
- [ ] P1-P9 verificar manualmente en navegador
- [ ] P9: PG y PS — mismos escenarios que PI

#### ACT — completado ✓
- [x] ROADMAP.md actualizado con entrada Ciclo 3
- [x] PG/PS validados (fix aplicado a ambos)
