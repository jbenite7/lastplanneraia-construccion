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

## Qué viaja en git

`.gitignore` ignora `goals/` entero y va habilitando carpetas una a una con lista blanca. Tras el
repaso del 2026-08-02, **los 16 goals viajan**: 97 archivos `.md` llegan a un clon fresco.

Antes de ese repaso faltaban tres cosas, y conviene saber por qué, porque el mismo patrón puede
repetirse con cualquier goal nuevo:

- `shell-layout-design-system` y `sidebar-todos-modulos` no tenían **ningún** archivo en git: se
  crearon después de que existiera la lista blanca y nadie añadió su excepción. De
  `sidebar-todos-modulos` se versionaron también `briefs/` y `reports/`, porque cada report
  registra el commit, el resultado de los tests y cómo se resolvió ese módulo del rollout.
- `cierre-dark-mode-y-tablas/specs/diseno.md` estaba suelto fuera. Reincluirlo exigió reabrir
  antes la carpeta del goal: git no desciende a un directorio ya excluido por `goals/*`, así que
  una excepción a un archivo interior no basta por sí sola.
- `bi-control-tower-gemini` **sí viajaba**, pero por accidente: sus archivos ya estaban rastreados
  antes de que existiera la lista blanca, y `.gitignore` no desversiona lo que git ya sigue. Se le
  añadió su excepción explícita para que deje de depender de eso.

Lo que sigue deliberadamente fuera: los JSON de entrevista y `facts-result*`, la evidencia en
imagen, y `dark-mode-todos-los-modulos/HANDOFF-5h-5k.md` —un traspaso entre sesiones que apunta a
`.superpowers/`, scratch que nunca viaja, así que en un clon sería un puntero roto—.

**Al crear un goal nuevo, añade su excepción en `.gitignore` o desaparecerá en silencio.**
