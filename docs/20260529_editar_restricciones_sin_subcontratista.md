# Editar restricciones sin Sub_Contratista asignado en PI

**Fecha**: 2026-05-29
**Motivación**: Permitir que actividades en Programación Intermedia puedan gestionar restricciones sin tener un Subcontratista asignado. El Responsable AIA sigue siendo obligatorio.

---

## Cambios realizados

### 1. `public/js/modules/programacion_intermedia/hot.js` — Validación edición individual (línea 2969)

**Antes**: `afterChange` rechazaba editar restricciones si faltaba Sub_Contratista **o** Responsable_AIA.

```javascript
if (!hasSub || !hasResp) {
    revertCell(row, prop, oldValue);
    showFeedback('error', 'No puede gestionar restricciones de una actividad sin asignar Responsable y Subcontratista');
    continue;
}
```

**Después**: Solo valida Responsable_AIA.

```javascript
if (!hasResp) {
    revertCell(row, prop, oldValue);
    showFeedback('error', 'No puede gestionar restricciones de una actividad sin asignar Responsable AIA');
    continue;
}
```

### 2. `public/js/modules/programacion_intermedia/hot.js` — Validación batch frontend (líneas 1507-1508, 1522-1524)

- `responsableAia` se lee del formulario siempre (no solo cuando `applyAssignments` está activo).
- Nueva validación: si se aplican restricciones y no hay `responsableAia`, se rechaza.

**Antes** (línea 1508):
```javascript
var responsableAia = applyAssignments ? String($('#piSharedResponsableAIA').val() || '').trim() : '';
```

**Después**:
```javascript
var responsableAia = String($('#piSharedResponsableAIA').val() || '').trim();
```

Nueva validación agregada:
```javascript
if (applyRestriction && !responsableAia) {
    return { valid: false, error: 'Responsable AIA es obligatorio para aplicar restricciones en lote.' };
}
```

### 3. `src/Controllers/Programacion/ProgramacionIntermediaController.php` — Validación server-side batch (líneas 644, 409, nueva validación)

- `$responsableAia` se lee del POST siempre (no condicionado a `$applyAssignments`).
- Nueva validación: si `$applyRestriction` y `$responsableAia` vacío, se rechaza.
- `Responsable_AIA` se incluye en SET clauses si no está vacío (independientemente de `$applyAssignments`).

---

## Archivos modificados

| Archivo | Cambio |
|---|---|
| `public/js/modules/programacion_intermedia/hot.js` | Relajar validación edición individual |
| `public/js/modules/programacion_intermedia/hot.js` | Batch: leer responsableAia siempre + validación |
| `src/Controllers/Programacion/ProgramacionIntermediaController.php` | Server-side: leer responsableAia siempre + validación + SET clause |

---

## Scope excluido

- `SemanalApiController.php` no se modificó (validación de confirmación semanal sigue exigiendo Sub_Contratista).
- Vista HTML del modal batch no se modificó.
- No hay cambios en el servidor para guardado individual (`guardar_programacion_intermedia.php`), porque nunca validó estos campos.
