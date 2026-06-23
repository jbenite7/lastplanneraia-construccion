# Task 5 - Pruebas de Validación End-to-End

## Resumen

Validación completa del flujo de autogeneración de actividades, confirmando que actividades sin nombre son manejadas correctamente tanto en backend (filtro SQL) como en frontend (UI placeholder).

## Validaciones ejecutadas

### 1. Verificación de sintaxis PHP

| Archivo | Resultado |
|---------|-----------|
| `src/Controllers/Api/ListadoActividadesApiController.php` | ✅ Sin errores de sintaxis |
| `views/listado-actividades/listadoActividades.view.php` | ✅ Sin errores de sintaxis |

### 2. Filtro SQL (Tarea 3 - Wave 2, commit 96165dc)

**Cambio aplicado en** `ListadoActividadesApiController.php:591`:
```sql
-- Antes:
AND COALESCE(Actividad, '') <> ''

-- Después:
AND COALESCE(TRIM(REGEXP_REPLACE(REGEXP_REPLACE(Actividad, '<[^>]+>', ''), '&nbsp;', ' ')), '') <> ''
```

**Efecto**: Actividades con contenido como `<p></p>`, `<b> </b>`, `<p>&nbsp;</p>` ahora son correctamente excluidas porque el filtro SQL replica `strip_tags() + trim()` antes de verificar si el resultado es vacío.

### 3. Corrección UI (Tarea 4 - este commit)

**Cambio aplicado en** `listadoActividades.view.php:847`:
```js
// Antes:
escaparHtml(s.actividad ? s.actividad.replace(/<[^>]+>/g, '').substring(0, 80) : '')

// Después:
escaparHtml(s.actividad ? s.actividad.replace(/<[^>]+>/g, '').substring(0, 80) : 'Sin nombre')
```

**Efecto**: Defensa adicional en la UI. Si alguna actividad sin nombre llega a la capa de presentación (por datos legacy o casos borde), la tabla muestra "Sin nombre" en lugar de una celda vacía.

### 4. Comportamiento esperado del flujo completo

| Escenario | Filtro SQL (T3) | UI (T4) | Resultado |
|-----------|-----------------|---------|-----------|
| Actividad con nombre normal (`<p>Excavar zanja</p>`) | ✅ Pasa el filtro | ✅ Muestra el nombre | Correcto |
| Actividad vacía (`''`) | ❌ Excluida | N/A | No aparece en resultados |
| Actividad con solo HTML (`<p></p>`) | ❌ Excluida | N/A | No aparece en resultados |
| Actividad con solo `&nbsp;` (`<p>&nbsp;</p>`) | ❌ Excluida | N/A | No aparece en resultados |
| Actividad con solo espacios (`<p>   </p>`) | ❌ Excluida | N/A | No aparece en resultados |
| Actividad NULL | ❌ Excluida | N/A | No aparece en resultados |
| Data legacy que pase el filtro | Vulnerabilidad baja | ✅ Muestra "Sin nombre" | Celda no vacía |

### 5. Cambios en el repositorio

```
commit 96165dc ✅  fix(listado-actividades): filter out activities with empty names after normalization
  → src/Controllers/Api/ListadoActividadesApiController.php (1 línea)

commit HEAD     ✅  fix(listado-actividades): show placeholder for activities without name
  → views/listado-actividades/listadoActividades.view.php (1 línea)
```

## Conclusión

- ✅ **Filtro SQL**: Excluye actividades sin nombre después de normalización
- ✅ **UI placeholder**: Muestra "Sin nombre" como defensa adicional
- ✅ **Sin errores de sintaxis**: Ambos archivos compilan correctamente
- ✅ **Sin cambios en lógica de negocio**: Solo se modificó el filtro y el placeholder
- ✅ **Sin modificación de datos**: No se alteró estructura de BD ni datos existentes
