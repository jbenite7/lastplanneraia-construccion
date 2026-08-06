---
tipo: goal
estado: vigente
fecha: 2026-08-02
areas: [proceso]
fuente: sesion
resumen: "Estado real de los 24 goals del repo, leído de cada goal.md — cuáles siguen abiertos y cuáles no viajan en git"
---
# Estado de los goals

Leído de los `goal.md` y de la última entrada de cada `validation-log.md` el 2026-08-02, y
repasado en el pase de veracidad del 2026-08-06 (entonces eran 16 carpetas; hoy son **24**). La
fuente sigue siendo `goals/<slug>/`; esta página solo lo resume.

**Aviso sobre las cinco tandas de la biblia:** sus `goal.md` siguen diciendo «ABIERTO», pero
`memoria/log.md` registra el 2026-08-04 la **primera pasada ejecutada de T1 a T5**, con sus
documentos en `docs/flujos/` y pruebas en verde. Lo que no está cerrado es el goal, no el trabajo:
nadie actualizó la cabecera. Ante la duda, gana `docs/flujos/`.

## Abiertos o bloqueados

| Goal | Estado | Qué persigue |
|---|---|---|
| [[goals/biblia-t1-transversal/goal|biblia-t1-transversal]] | **abierto** — se ejecuta primero: impacto alto y esfuerzo bajo | Describir y verificar los escenarios de entrada a la app: autenticación, selección de proyecto y las 17 capacidades de RBAC |
| [[goals/biblia-t2-cascada-lps/goal|biblia-t2-cascada-lps]] | **abierto** — segundo; requiere T1 | El ciclo Last Planner completo, escenario a escenario: es el cuello de botella de los tres jobs a la vez |
| [[goals/biblia-t3-pdc/goal|biblia-t3-pdc]] | **abierto** — tercero | El Plan de Compras v2, con sus deudas de datos conocidas como escenarios de primera clase |
| [[goals/biblia-t4-soporte/goal|biblia-t4-soporte]] | **abierto** — cuarto | Los seis módulos que alimentan la cascada, y la invariante de los contratos `auto/*` que comparten con el PDC |
| [[goals/biblia-t5-lectura/goal|biblia-t5-lectura]] | **abierto** — quinto; depende de las anteriores | Indicadores y Torre de Control: describir una cifra exige haber descrito su origen |
| [[goals/bi-control-tower-gemini/goal|bi-control-tower-gemini]] | **bloqueado** — falta aprobación visual explícita de la matriz de 6 modos; sin ella no hay commit | Validar el dashboard de Torre de Control BI: radar de productividad, eficiencia y PAC, más cronograma de avance |
| [[goals/pg-chip-de-estado/goal|pg-chip-de-estado]] | **diseño aprobado, sin ejecutar** (abierto el 2026-08-03) | Que `/programa-general` distinga en pantalla los siete estados que su contrato declara, con el chip que PI y PS ya tienen |
| [[goals/cierre-version-1-1-0-design-system/goal|cierre-version-1-1-0-design-system]] | **diseño y plan aprobados, en espera de precondición** (abierto el 2026-08-04): no arranca hasta que la campaña dark mode termine — el traspaso está escrito en el Step 6 de su Task 31 | Publicar la 1.1.0 del design system: pagar o re-vencer las 39 excepciones que vencen en ella (migrando `/proyectos` a primitivas `aia-*`), gates a «al menos 1.0.0» y commit de activación atómico |
| [[goals/cierre-version-1-1-0-design-system/goal|cierre-version-1-1-0-design-system]] | **abierto** — bloqueado hasta que cierre la campaña dark mode | Publicar la 1.1.0 del design system: pagar o re-vencer las 39 excepciones que expiran, flexibilizar los gates de activación. Ver [[subir-la-version-del-ds-cobra-deudas]] |
| [[goals/design-system-nucleo-gobernanza/goal|design-system-nucleo-gobernanza]] | **indeterminado** — sin sección de cierre; la última entrada deja pendientes la revisión visual, los datos del piloto y el contrato de release | Consolidar el design system como fuente de verdad única, versionada y con gates automáticos, con Programa General de piloto |

## Cerrados

| Goal | Matiz |
|---|---|
| [[goals/cierre-dark-mode-y-tablas/goal|cierre-dark-mode-y-tablas]] | Cerró el dark mode pendiente y unificó Handsontable, DataTables y AG Grid como una sola tabla del sistema |
| [[goals/pdc-a41-pasos-configurables/goal|pdc-a41-pasos-configurables]] | Pasos de contratación configurables por obra (días, alias, orden, activación) |
| [[goals/pdc-a42-frentes-cobertura/goal|pdc-a42-frentes-cobertura]] | El plan de compras ya sabe a qué frente del cronograma pertenece cada paquete |
| [[goals/pdc-preparar-b1/goal|pdc-preparar-b1]] | PDC v2 a producción en tres olas; una tarea quedó diferida a otro goal |
| [[goals/pdc-revision-ux/goal|pdc-revision-ux]] | Los 15 hallazgos de usabilidad del recorrido del dueño de producto |
| [[goals/pdc-tanda2-plan-verdad/goal|pdc-tanda2-plan-verdad]] | Condición de hecho cumplida el 2026-07-29, aunque sin sección de cierre formal |
| [[goals/pdc-tanda34-pulido/goal|pdc-tanda34-pulido]] | Los once pendientes de primera impresión y pulido |
| [[goals/repaso-usabilidad-no-tablas/goal|repaso-usabilidad-no-tablas]] | Cerrado el 2026-08-03: 39 hallazgos (15 altas, 15 medias, 9 bajas), el usuario aprobó atacar 30. Diagnóstico puro, no toca código; cubrió 26 de 45 superficies — las 14 de `admin/` quedaron fuera porque la puerta de servicio no abre su sesión |
| [[goals/retiro-listado-contratos/goal|retiro-listado-contratos]] | Retirados `/listado-actividades` y `/contratos`; 2 de 4 etapas diferidas explícitamente |
| [[goals/segmentacion-entrypoint-css/goal|segmentacion-entrypoint-css]] | Núcleo CSS sin vendors más adjuntos por vendor |
| [[goals/shell-layout-design-system/goal|shell-layout-design-system]] | Goal paraguas; se cerró aunque 2 de sus 4 iniciativas pasaron a un sucesor |
| [[goals/sidebar-todos-modulos/goal|sidebar-todos-modulos]] | Barra lateral canónica en los 11 módulos que usaban navegación antigua. Ojo: su `goal.md` sigue diciendo que Compras queda excluido, y ya no es cierto — ver [[compras-migrado-shell-sidebar]] |
| [[goals/validar-migracion-handsontable/goal|validar-migracion-handsontable]] | **Descartado**: quedó sin objeto al retirarse las superficies que iba a validar |

## Absorbidos por otro goal

| Goal | Absorbido en |
|---|---|
| [[goals/dark-mode-todos-los-modulos/goal|dark-mode-todos-los-modulos]] | [[goals/cierre-dark-mode-y-tablas/goal|cierre-dark-mode-y-tablas]]. Ninguna fase llegó a ejecutarse bajo este goal, pero sus decisiones F0–F6 siguen vigentes: ver [[goal-dark-mode-todos-modulos]] |
| [[goals/pdc-responsable-usuario/goal|pdc-responsable-usuario]] | [[goals/pdc-preparar-b1/goal|pdc-preparar-b1]] |

## Qué viaja en git

`.gitignore` ignora `goals/` entero y va habilitando carpetas una a una con lista blanca. Tras el
repaso del 2026-08-02, **los 16 goals de entonces viajaban**: 97 archivos `.md` llegaban a un clon
fresco. Recontado el 2026-08-06: hay **24 carpetas de goal** y **106 de los 108 `.md` están
versionados** (`git ls-files 'goals/*.md'`), así que la lista blanca se ha ido manteniendo — los
tres goals nuevos (`repaso-usabilidad-no-tablas`, `pg-chip-de-estado`,
`cierre-version-1-1-0-design-system`) tienen su excepción en `.gitignore`.

Antes de ese repaso faltaban tres cosas, y conviene saber por qué, porque el mismo patrón puede
repetirse con cualquier goal nuevo:

- [[goals/shell-layout-design-system/goal|shell-layout-design-system]] y [[goals/sidebar-todos-modulos/goal|sidebar-todos-modulos]] no tenían **ningún** archivo en git: se
  crearon después de que existiera la lista blanca y nadie añadió su excepción. De
  [[goals/sidebar-todos-modulos/goal|sidebar-todos-modulos]] se versionaron también `briefs/` y `reports/`, porque cada report
  registra el commit, el resultado de los tests y cómo se resolvió ese módulo del rollout.
- `cierre-dark-mode-y-tablas/specs/diseno.md` estaba suelto fuera. Reincluirlo exigió reabrir
  antes la carpeta del goal: git no desciende a un directorio ya excluido por `goals/*`, así que
  una excepción a un archivo interior no basta por sí sola.
- [[goals/bi-control-tower-gemini/goal|bi-control-tower-gemini]] **sí viajaba**, pero por accidente: sus archivos ya estaban rastreados
  antes de que existiera la lista blanca, y `.gitignore` no desversiona lo que git ya sigue. Se le
  añadió su excepción explícita para que deje de depender de eso.

Lo que sigue deliberadamente fuera: los JSON de entrevista y `facts-result*`, la evidencia en
imagen, y `dark-mode-todos-los-modulos/HANDOFF-5h-5k.md` —un traspaso entre sesiones que apunta a
`.superpowers/`, scratch que nunca viaja, así que en un clon sería un puntero roto—.

**Al crear un goal nuevo, añade su excepción en `.gitignore` o desaparecerá en silencio.**
