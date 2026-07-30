# Validar integralmente la migración a Handsontable

**Estado:** descartado / no aplica (2026-07-30) — el retiro de `/listado-actividades` y `/contratos` dejó sin efecto esta validación; las tablas restantes se gobiernan mediante PDC V2 y `cierre-dark-mode-y-tablas`.

## Goal

Completar, corregir y demostrar la migración de DataTables a Handsontable en Listado de Actividades, Contratos y PDC, incluyendo la tabla principal y `dt_definirContratos`. El trabajo se ejecuta como un único sprint continuo, con pruebas funcionales, persistencia, restauración de datos y matriz responsive Dark/Linen antes de una sola revisión y aprobación final.

BI queda fuera del alcance. PG, PI y PS solo participan en una comprobación final de regresiones.

## Shared Understanding

Los 57 hechos aceptados y sus verificaciones están en [facts.md](facts.md). La metadata de automatización está en [facts.meta.json](facts.meta.json).

El avance y la evidencia del sprint están consolidados en [validation-log.md](validation-log.md).

## Execution Plan

El orden de trabajo, archivos, pruebas, riesgos y puertas de aprobación están en [plan.md](plan.md).

## Done Condition

- [ ] Listado de Actividades cumple todos sus hechos funcionales y visuales con evidencia nueva del goal actualizado.
- [ ] Contratos cumple todos sus hechos funcionales y visuales con evidencia nueva del goal actualizado.
- [ ] PDC y `dt_definirContratos` funcionan exclusivamente con Handsontable y cumplen todos sus hechos funcionales y visuales.
- [ ] Ninguna tabla migrada descarga, inicializa o genera runtime DataTables; los recursos compartidos restantes tienen consumidores activos fuera del alcance.
- [ ] Ediciones, selectores, filtros, toolbars, modales, eliminación y automatización tienen evidencia actual y persistencia comprobada.
- [ ] Todas las pruebas destructivas restauraron el estado inicial de los datos.
- [ ] Las seis combinaciones de viewport y tema no presentan overflow, scroll-x, desalineación, mezcla de temas ni texto fuera de controles.
- [ ] Los estilos afectados utilizan el design system AIA y no introducen valores visuales ajenos a sus tokens o patrones.
- [ ] PG, PI y PS superan la regresión acotada de carga, filtros y edición persistente representativa.
- [ ] Los tres módulos y las seis combinaciones visuales fueron mostrados en una única revisión final del navegador integrado.
- [ ] El usuario dio una única aprobación final después de revisar toda la migración.
- [ ] Después de esa aprobación, las correcciones quedaron en commits locales atómicos, sin commits vacíos, cambios ajenos, push ni despliegue.
- [ ] El usuario declara explícitamente que el goal está completo.
