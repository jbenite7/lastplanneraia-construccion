---
tipo: goal
estado: vigente
fecha: 2026-08-02
areas: [goals]
fuente: sesion
resumen: "Estado real de los 16 goals del repo, leído de cada goal.md — cuáles siguen abiertos y cuáles no viajan en git"
---
# Estado de los goals

Leído de los `goal.md` y de la última entrada de cada `validation-log.md` el 2026-08-02. La
fuente sigue siendo `goals/<slug>/`; esta página solo lo resume.

## Abiertos o bloqueados

| Goal | Estado | Qué persigue |
|---|---|---|
| `bi-control-tower-gemini` | **bloqueado** — falta aprobación visual explícita de la matriz de 6 modos; sin ella no hay commit | Validar el dashboard de Torre de Control BI: radar de productividad, eficiencia y PAC, más cronograma de avance |
| `design-system-nucleo-gobernanza` | **indeterminado** — sin sección de cierre; la última entrada deja pendientes la revisión visual, los datos del piloto y el contrato de release | Consolidar el design system como fuente de verdad única, versionada y con gates automáticos, con Programa General de piloto |

## Cerrados

| Goal | Matiz |
|---|---|
| `cierre-dark-mode-y-tablas` | Cerró el dark mode pendiente y unificó Handsontable, DataTables y AG Grid como una sola tabla del sistema |
| `pdc-a41-pasos-configurables` | Pasos de contratación configurables por obra (días, alias, orden, activación) |
| `pdc-a42-frentes-cobertura` | El plan de compras ya sabe a qué frente del cronograma pertenece cada paquete |
| `pdc-preparar-b1` | PDC v2 a producción en tres olas; una tarea quedó diferida a otro goal |
| `pdc-revision-ux` | Los 15 hallazgos de usabilidad del recorrido del dueño de producto |
| `pdc-tanda2-plan-verdad` | Condición de hecho cumplida el 2026-07-29, aunque sin sección de cierre formal |
| `pdc-tanda34-pulido` | Los once pendientes de primera impresión y pulido |
| `retiro-listado-contratos` | Retirados `/listado-actividades` y `/contratos`; 2 de 4 etapas diferidas explícitamente |
| `segmentacion-entrypoint-css` | Núcleo CSS sin vendors más adjuntos por vendor |
| `shell-layout-design-system` | Goal paraguas; se cerró aunque 2 de sus 4 iniciativas pasaron a un sucesor |
| `sidebar-todos-modulos` | Barra lateral canónica en los 11 módulos que usaban navegación antigua. Ojo: su `goal.md` sigue diciendo que Compras queda excluido, y ya no es cierto — ver [[compras-migrado-shell-sidebar]] |
| `validar-migracion-handsontable` | **Descartado**: quedó sin objeto al retirarse las superficies que iba a validar |

## Absorbidos por otro goal

| Goal | Absorbido en |
|---|---|
| `dark-mode-todos-los-modulos` | `cierre-dark-mode-y-tablas`. Ninguna fase llegó a ejecutarse bajo este goal, pero sus decisiones F0–F6 siguen vigentes: ver [[goal-dark-mode-todos-modulos]] |
| `pdc-responsable-usuario` | `pdc-preparar-b1` |

## Cuáles no viajan en git

`.gitignore` ignora `goals/` y va habilitando carpetas una a una con lista blanca (líneas 56–125
y 270–274). Estos goals **existen solo en esta máquina**: en un clon fresco no están, y cualquier
enlace hacia ellos queda roto.

`bi-control-tower-gemini` · `cierre-dark-mode-y-tablas` · `shell-layout-design-system` ·
`sidebar-todos-modulos`

Tampoco viajan enteros los que sí están en la lista blanca: de varios solo se versionan
`goal.md`, `plan.md`, `facts.md` y `validation-log.md`, no la evidencia.

Corregir esa lista es una decisión aparte, no algo que arregle la wiki.
