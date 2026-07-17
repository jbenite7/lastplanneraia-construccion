# Registro de fixes y features — Sprint 04

| ID | Tipo | Estado | Registro |
|---|---|---|---|
| PG-FEAT-001 | Adopción | Resuelto | Cards móviles editables y tabla responsive existentes. |
| PG-FEAT-002 | Adopción | Resuelto | Temas dark/linen y estados visuales existentes. |
| PG-FEAT-003 | Adopción | Resuelto | Toolbar, guía operativa, filtros y exportación existentes. |
| PG-FIX-001 | Accesibilidad | Resuelto | Filtros declaran `aria-pressed=false` y lo sincronizan con `activeFilters`. |
| PG-FIX-002 | Geometría | Resuelto | Toolbar, filtros y toggle móvil cumplen target mínimo de 44px. |
| PG-FIX-003 | Runtime | Resuelto | CSS PG usa versión `filemtime` para evitar assets obsoletos. |
| PG-FIX-004 | Persistencia | Resuelto | Card móvil guarda al perder foco y evita envíos duplicados. |
| PG-OBS-001 | Geometría | No defecto | Scroll interno nativo no recorta caja, valor ni acción. |
| PG-OBS-002 | Alcance | Excluido | Rutas BI locales en `public/index.php`; no pertenecen al sprint. |
| PG-OBS-003 | Permisos | No defecto | `test.C` tiene acceso de lectura existente; no puede editar ni ejecutar acciones privilegiadas. |
| PG-OBS-004 | Descargas | No defecto | El navegador integrado no expone evento de archivo; ambas acciones terminaron sin error de aplicación. |
| PG-OBS-005 | Consola | No defecto | Dos errores vendor Handsontable aparecieron al forzar resize/reload; una carga estable limpia no los reprodujo. |

## PG-FIX-001

- Síntoma: el filtro cambiaba los registros y el estilo, pero no exponía su selección.
- Causa: `syncLegendVisualState()` solo administraba `inactive-filter`.
- Rutas: vista PG, módulo Handsontable y contrato estático del sprint.
- TDD: fallo esperado confirmado antes del cambio; prueba verde después del fix.
- Evidencia nativa: click, Enter, Espacio y selección múltiple verificados.

## PG-FIX-002

- Síntoma: reglas compartidas importantes fijaban algunos filtros en 32px.
- Causa: prioridad invertida de `!important` dentro de `@layer components`.
- Rutas: CSS canónico de Programa General.
- Verificación: seis estados responsive/tema con cero fallos en controles PG.

## PG-FIX-003 y PG-FIX-004

- Cache: la vista deriva la versión CSS del `filemtime` real, sin sufijos manuales.
- Guardado móvil: `change` y `blur` comparten una función idempotente; un mismo valor no genera dos guardados.
- TDD: el contrato falló antes del handler de blur y pasó tras el cambio mínimo.
- Runtime: 200 → 201, recarga persistente, restauración a 200 desde Handsontable y nueva recarga.
