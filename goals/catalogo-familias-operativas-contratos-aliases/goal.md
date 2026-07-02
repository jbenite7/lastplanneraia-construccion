# Goal: Catalogo de familias operativas, contratos y PDC sin legacy obsoleto

Refactorizar `/listado-actividades/`, `/contratos/` y `/pdc/` para que trabajen con conceptos separados y verificables: familias operativas canonicas para actividades, aliases como traducciones hacia esas familias, elementos contractuales para contratos, y PDC como seguimiento de pasos y fechas del proceso de contratacion.

La comprension compartida esta en `facts.md`.

El plan de ejecucion aprobado esta en `plan.md`.

## Done

Este goal queda completo cuando:

- `general_pdc_familias` contiene solo familias operativas canonicas.
- Aliases y elementos contractuales viven fuera de `general_pdc_familias`.
- `/listado-actividades/` no genera propuestas listas con aliases, contratos, capitulos, ubicaciones o contexto como familia.
- `/contratos/` conserva paquetes, fuentes e intervenciones sin duplicar actividades.
- `/pdc/` se genera desde Contratos y sigue pasos y fechas del proceso de contratacion sin crear familias.
- La administracion permite mantener familias, aliases, elementos contractuales, reglas, impacto, auditoria y aprobaciones.
- El legacy directo de Listado, Contratos y PDC queda deprecado o retirado segun el plan aprobado.
- Antes de cualquier borrado destructivo existe backup externo, restauracion local y comparacion de datos.
- La evidencia final cubre Optimización Aeropuerto JMC, Da Porto, un proyecto Metrolinea y Milan Campestre Torre 19 con capturas, recordings, resumen sanitizado, tests PHP y E2E.
