# Evidencia: PDC desde Contratos al 2026-07-02

Fecha de ejecucion: 2026-07-02  
Alcance: Optimización Aeropuerto JMC, Da Porto y Milán Campestre Torre 19.

## Regla validada

El PDC se genera y se valida desde los paquetes definidos en Contratos.  
No se genera directamente desde Actividades.

## Respaldo previo

- Backup externo: `backup/project-rows-before.sql`
- Verificacion SHA-256: `backup/project-rows-before.sql.sha256`
- Estado: verificado con `shasum -a 256 -c`.

## Semanas objetivo

| Proyecto | Semana | Rango |
|---|---:|---|
| Optimización Aeropuerto JMC | 7 | 2026-07-01 a 2026-07-07 |
| Da Porto | 8 | 2026-06-30 a 2026-07-06 |
| Milán Campestre Torre 19 | 6 | 2026-06-29 a 2026-07-05 |

## Resultado de paquetes

| Proyecto | Paquetes en Contratos | Paquetes en PDC | Faltantes | Duplicados | Estado |
|---|---:|---:|---:|---:|---|
| Optimización Aeropuerto JMC | 50 | 50 | 0 | 0 | OK |
| Da Porto | 25 | 25 | 0 | 0 | OK |
| Milán Campestre Torre 19 | 4 | 4 | 0 | 0 | OK |

Nota: la UI puede mostrar “Datos Faltantes” porque no hay fechas reales de seguimiento registradas. Eso no significa que falten paquetes desde Contratos.

## Archivos generados

- Resumen de ejecución actualizado: `goal-runner-summary.json` (`2026-07-02T08:25:38-05:00`)
- Resumen visual actualizado: `browser-evidence-summary.json` (`2026-07-02T13:26:55Z`)
- Capturas: `screenshots/*.png`
- Videos: `videos/*.webm`
- Traces de navegador: `traces/*.zip`

## Verificaciones clave

- El segundo pase de PDC queda en 0 propuestas para los tres proyectos.
- No hay paquetes definidos en Contratos que falten en PDC.
- No hay paquetes PDC duplicados por tipo y nombre.
- No hay paquetes extra en PDC frente a los paquetes definidos en Contratos.
- No hay filas PDC incompletas para paquete, contrato o fecha de inicio.
- El botón visible queda como `Auto-Generar desde Contratos`.
- El botón `Actualizar` de PDC usa `/api/pdc/auto/apply-from-contratos`; la vista ya no llama `/legacy/pdc/actualizar_pdc.php`.
- La ruta `/legacy/pdc/actualizar_pdc.php` queda deprecada y responde con mensaje de retiro; no ejecuta la actualización vieja.
- PDC no crea familias ni actividades; solo crea o actualiza paquetes PDC derivados de Contratos.

## Reverificacion del objetivo de 3 proyectos

Comando: `docker compose exec app php tests/test_pdc_three_projects_perfect_20260702.php`

Resultado: OK, 0 fallas.

Cobertura de la prueba:

- Cada proyecto tiene una semana que cubre el 2026-07-02.
- Cada proyecto tiene paquetes definidos en Contratos.
- Contratos y PDC tienen el mismo numero de paquetes.
- PDC no tiene faltantes frente a Contratos.
- PDC no tiene extras frente a Contratos.
- PDC no tiene paquetes duplicados.
- PDC no tiene filas incompletas para paquete, contrato o fecha de inicio.
- El segundo pase del asistente PDC queda sin propuestas pendientes.

## Pruebas ejecutadas

- `docker compose exec app php tests/test_activity_program_sources_traceability.php`: OK.
- `docker compose exec app php tests/test_operational_family_policy.php`: OK.
- `docker compose exec app php tests/test_contractual_family_routing.php`: OK.
- `docker compose exec app php tests/test_pdc_duration_catalog.php`: OK.
- `docker compose exec app php tests/test_semi_auto_quality_gate.php`: OK.
- `docker compose exec app php tests/test_semi_auto_service.php`: OK.
- `docker compose exec app php tests/test_listado_contractual_exclusion_real_projects.php`: OK.
- `docker compose exec app php tests/test_pdc_modern_replaces_legacy_update.php`: OK.
- `docker compose exec app php tests/test_pdc_three_projects_perfect_20260702.php`: OK.
- `docker compose exec app php tests/test_pdc_does_not_create_families.php`: OK.
- `docker compose exec app php tests/test_lacp_modern_navigation.php`: OK.
- `docker compose exec app php tests/test_contratos_modern_assistant_replaces_auto_define_ui.php`: OK.
- `npx playwright test tests/browser/auto-definir-contratos.mjs`: 2/2 OK.
- `npx playwright test tests/browser/test-pdc.mjs`: 6/6 OK.
- `docker compose exec app vendor/bin/phpstan analyse src public/index.php --memory-limit=1G`: OK.
