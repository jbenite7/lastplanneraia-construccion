# Evidencia: PDC desde Contratos

Fecha: 2026-07-02  
Alcance: correccion de lenguaje, ruta y flujo visible para que Plan de Compras se entienda como generado desde Contratos, no desde Actividades.

## Cambios verificados

- Boton visible en PDC: `Auto-Generar desde Contratos`.
- Endpoint moderno: `/api/pdc/auto/apply-from-contratos`.
- Controlador: `PdcAutoGenerateController::applyFromContratos`.
- Goal actualizado: PDC se genera desde Contratos y no crea familias.
- Asistente PDC actualizado para mostrar pasos de usuario final: leer Contratos, estimar fechas y comparar contra Plan de Compras.

## Auditoria de proyectos obligatorios

| Proyecto | Semana auditada | Rango | Actividades con paquetes de contrato | PDC titulos | PDC items | Lectura |
|---|---:|---|---:|---:|---:|---|
| Optimización Aeropuerto JMC | 5 | 2026-06-17 a 2026-06-23 | 0 | 9 | 0 | No puede quedar PDC perfecto sin completar antes Listado y Contratos. |
| Da Porto | 1 | 2026-05-12 a 2026-05-18 | 30 | 3 | 18 | Tiene base contractual y PDC con items. |
| Milan Campestre Torre 19 | 5 | 2026-06-22 a 2026-06-28 | 0 | 6 | 0 | No puede quedar PDC perfecto sin completar antes Listado y Contratos. |

## Pruebas ejecutadas

- `node --check public/js/modules/semi_auto_review.js`: OK.
- `docker compose exec app php -l src/Services/SemiAutoService.php`: OK.
- `docker compose exec app php -l src/Controllers/Api/PdcAutoGenerateController.php`: OK.
- `docker compose exec app php tests/test_pdc_duration_catalog.php`: 0 fallas.
- `docker compose exec app php tests/test_operational_family_policy.php`: 0 fallas.
- `docker compose exec app php tests/test_contractual_family_routing.php`: 0 fallas.
- `docker compose exec app php tests/test_semi_auto_quality_gate.php`: 0 fallas.
- `docker compose exec app php tests/test_activity_program_sources_traceability.php`: 0 fallas.
- `docker compose exec app php tests/test_semi_auto_service.php`: 0 fallas.
- `npx playwright test tests/browser/test-pdc.mjs`: 5/5 pruebas pasaron.

## Nota de evidencia visual

La configuracion actual de `playwright.config.mjs` no activa video, screenshot ni trace por defecto. La corrida valida el flujo en navegador headless y deja resultado en `test-results/.last-run.json`, pero no produjo recording nuevo.

## Hallazgo pendiente

El flujo de negocio queda corregido como PDC desde Contratos. La deuda estructural sigue siendo que los campos de Contratos viven hoy dentro de la tabla `actividades`; para dejar el sistema 100% limpio, el refactor mayor debe extraer o encapsular esa persistencia sin romper compatibilidad.
