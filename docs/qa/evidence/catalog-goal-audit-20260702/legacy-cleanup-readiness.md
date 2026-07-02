# Manifiesto de limpieza legacy LACP

Fecha: 2026-07-02  
Alcance: `/listado-actividades/`, `/contratos/` y `/pdc/`.

## Decision de alcance

El retiro legacy de este goal se limita a Listado, Contratos y PDC. No incluye Programacion Semanal, CIC/CNC/CNP, Control de Cambios, integraciones generales de semana ni otros modulos que aun dependen de rutas o vistas legacy.

## Estado runtime LACP

La app ya no debe ejecutar los flujos legacy retirados dentro del alcance:

| Flujo anterior | Estado actual | Reemplazo |
|---|---|---|
| `/legacy/pdc/actualizar_pdc.php` | Retirado del router; archivo `src/Legacy/actualizar_pdc.php` eliminado. | `/api/pdc/auto/apply-from-contratos` |
| `/api/contratos/auto-define` | Retirado del router. | `/api/contratos/auto/preview` |
| `/api/contratos/auto-define/apply` | Retirado del router. | `/api/contratos/auto/apply` |
| `/api/contratos/auto-define/undo` | Retirado del router. | `/api/contratos/auto/undo` |
| `/api/contratos/auto-define/reanalyze` | Retirado del router. | `/api/contratos/auto/preview` |
| Navegacion `/legacy/cambiar_pagina.php` desde vistas LACP | Retirada de `listadoActividades.view.php`, `contratos.view.php` y `pdc.view.php`. | Rutas modernas `/listado-actividades`, `/contratos`, `/pdc` |
| Modal/JS legacy de auto-definir contratos | Retirado de la vista de Contratos. | Asistente semi-auto moderno |

## Guardas automatizadas

- `tests/test_legacy_absence_for_lacp_runtime.php`: bloquea que los archivos runtime LACP vuelvan a llamar endpoints, modales o funciones legacy retiradas.
- `tests/test_lacp_modern_navigation.php`: valida navegacion moderna entre Listado, Contratos y PDC.
- `tests/test_contratos_modern_assistant_replaces_auto_define_ui.php`: valida que Contratos usa asistente moderno y no modal legacy.
- `tests/test_pdc_modern_replaces_legacy_update.php`: valida que PDC se actualiza desde Contratos y que la ruta/archivo legacy fueron retirados.
- `tests/test_lacp_backup_restore_before_cleanup.php`: valida backup externo, checksum, limpieza temporal, restauracion local y comparacion de datos antes de cualquier borrado destructivo real.

## Limpieza destructiva aprobada y ejecutada

El usuario dio aprobacion explicita de este manifiesto. Antes del retiro se ejecuto `tests/test_lacp_backup_restore_before_cleanup.php`, que creo backup externo local, checksum, limpieza temporal, restauracion local y permitio comparar conteos y huella de datos.

Alcance retirado:

1. Rutas `/api/contratos/auto-define*`.
2. Ruta `/legacy/pdc/actualizar_pdc.php`.
3. Archivo `src/Legacy/actualizar_pdc.php`.

No se borro `_pdc_functions.php` porque `src/Legacy/nueva_semana.php` lo sigue usando fuera del alcance LACP.

## Referencias legacy fuera de alcance

Las referencias legacy restantes detectadas en `views/programacion-semanal/*`, `public/js/funcionesGenerales6.js`, `public/js/cargarDatosGeneralesPagina*.js`, seguridad/RBAC, Control de Cambios y otros modulos no son evidencia de incumplimiento de este goal. Siguen fuera del alcance aprobado salvo que el usuario amplie explicitamente el refactor.

## Estado de cierre

Para Listado, Contratos y PDC, el legacy runtime aprobado queda cubierto por pruebas y retiro de rutas/archivo. Cualquier ampliacion fuera de este alcance requiere aprobacion aparte.
