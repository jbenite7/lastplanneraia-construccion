---
capa: wiki
tipo: goal
estado: vigente
fecha: 2026-08-02
areas: [proceso]
fuente: sesion
resumen: "Estado real de los 26 goals del repo, leído de cada goal.md — cuáles siguen abiertos y cuáles no viajan en git. Corte del 2026-08-10, Frente 0"
---
# Estado de los goals

Leído de los `goal.md` y de la última entrada de cada `validation-log.md` el 2026-08-02, repasado
en el pase de veracidad del 2026-08-06 (entonces eran 16 carpetas; después 24) y actualizado el
2026-08-10 tras el Frente 0 de higiene y decisiones (hoy **26 carpetas**). La fuente sigue siendo
`goals/<slug>/`; esta página solo lo resume.

## Abiertos o bloqueados

| Goal | Estado | Qué persigue |
|---|---|---|
| [[goals/bi-control-tower-gemini/goal|bi-control-tower-gemini]] | **bloqueado por dependencia, no por olvido** (corregido el 2026-08-10): la condición de hecho pedía aprobar seis modos, tres del tema `linen`, retirado el 2026-07-25. Nadie podía cumplirla. El usuario decidió esperar a que la fase F3 de `reapertura-movil-y-tema-claro` entregue el tema claro nuevo y aprobar los seis de verdad, en vez de recortar el alcance. Ver [[condicion-de-hecho-caduca-sin-aviso]] | Validar el dashboard de Torre de Control BI: radar de productividad, eficiencia y PAC, más cronograma de avance |
| [[goals/design-system-nucleo-gobernanza/goal|design-system-nucleo-gobernanza]] | **NO se cierra** (medido el 2026-08-10, no solo redactado): de los 15 gates de cierre, solo 2 pasan de verdad, 4 fallan con evidencia real, 8 no son ejecutables en una sesión ad hoc y 1 (`accessibility-insights`) es un recibo sin comando real detrás. Los 14 artefactos de `docs/design-system/evidence/` resultaron ser stubs de dos claves — nunca fueron evidencia real. Dos hallazgos de PHPStan arreglados en el proceso (`9011c99c`). El usuario decidió reconstruir los 15 gates en un frente propio después del Frente 1 (Frente 1b). Ver [[gate-solo-cuenta-elementos-no-los-lee]] y [[condicion-de-hecho-caduca-sin-aviso]] | Consolidar el design system como fuente de verdad única, versionada y con gates automáticos, con Programa General de piloto |
| [[goals/reapertura-movil-y-tema-claro/goal|reapertura-movil-y-tema-claro]] | **abierto** — cuatro fases cerradas (F1 destrabar, F2a-1 precondiciones, F2a-2a deudas de arranque, 2026-08-07; F2a-2b piloto móvil, 2026-08-14), tres pendientes (F2b resto de módulos, F3 tema claro, F4 matriz diagonal). F2a-2b cerró con la extracción de reglas de habilitación, el umbral único de 1180 y el menú flotante del shell —cuyo sidebar resultó ser la causa real de que móvil fuera inusable, no las tarjetas—. Condición de hecho del goal completo: las cuatro fases con `npm run test:design-system:static` en 8/8 | Devolver al producto móvil, tablet y un tema claro, empezando por contratos, siguiendo por gates y terminando por interfaz |

## Cerrados

| Goal | Matiz |
|---|---|
| `adopcion-logo-construccion` | Adoptó el ícono del kit Construcción en favicon, sidebar del shell, login y Admin (`4437fcfa`, `6b618964`). Sin `validation-log.md` propio; cerrado por evidencia de commit, no por sección de cierre formal. **No viaja en git**: sin excepción en `.gitignore` |
| [[goals/cierre-version-1-1-0-design-system/goal|cierre-version-1-1-0-design-system]] | Cerrado el 2026-08-07 con la publicación de la 1.1.0 (`a5223a0c`): suite estática 8/8 y cero excepciones venciendo en 1.1.0. De las 39, **7 pagadas y 32 re-vencidas a `1.2.0`** con evidencia medida. Su decisión D1 resultó falsa —las 15 de `theme-overrides.css` no eran del selector de proyecto sino normalizaciones globales del DS— así que ese grupo se resolvió con la regla de D3 en vez de migrando `/proyectos`, que no habría pagado ninguna. Detalle en [[subir-la-version-del-ds-cobra-deudas]] y [[version-escrita-a-mano-rompe-el-bump]] |
| [[goals/pg-chip-de-estado/goal|pg-chip-de-estado]] | Cerrado el 2026-08-06 sin ejecución propia: lo resolvió `51ccd5ca` de la campaña dark mode. Verificado antes de cerrar — `hot.js:1658` pinta el `ops-state-chip` con su matiz y `programa-general-state-hue.mjs` pasa |
| [[goals/biblia-t1-transversal/goal|biblia-t1-transversal]] | Cerrado el 2026-08-06 formalizando el trabajo ya hecho el 2026-08-04: entrada a la app (autenticación, proyecto, RBAC), 17 capacidades con escenario y 7 pruebas en verde |
| [[goals/biblia-t2-cascada-lps/goal|biblia-t2-cascada-lps]] | Cerrado el 2026-08-06 formalizando el trabajo del 2026-08-04: ciclo Last Planner completo, 26 escenarios, 5 pruebas en verde |
| [[goals/biblia-t3-pdc/goal|biblia-t3-pdc]] | Cerrado el 2026-08-06 formalizando el trabajo del 2026-08-04: se rehízo a mitad de camino cuando el usuario deprecó el PDC v1 el mismo día; quedó `docs/flujos/compras-v2.md` sobre el PDC v2 vivo, 3 pruebas en verde |
| [[goals/biblia-t4-soporte/goal|biblia-t4-soporte]] | Cerrado el 2026-08-06 formalizando el trabajo del 2026-08-04: 6 módulos de soporte con su hallazgo mayor —CSRF ausente— ya arreglado (`88ba6e0d`+`ca642189`) |
| [[goals/biblia-t5-lectura/goal|biblia-t5-lectura]] | Cerrado el 2026-08-06 formalizando el trabajo del 2026-08-04: indicadores y Torre de Control, 6 escenarios; el hallazgo de `/indicadores` ocultando en cliente se arregló después (`4b1a2be0`) |
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

**Actualizado el 2026-08-10:** dos carpetas más aparecieron desde entonces, con destinos
opuestos. [[goals/reapertura-movil-y-tema-claro/goal|reapertura-movil-y-tema-claro]] sí tiene su
excepción (`.gitignore:393-395`, solo `goal.md`, sin `facts.md`/`plan.md`/evidencia).
`goals/adopcion-logo-construccion/goal.md` **no tiene ninguna**: `git
ls-files goals/adopcion-logo-construccion` no devuelve nada pese a estar cerrado y ejecutado. Un
clon fresco no lo ve. Mismo patrón que el que ya documentaba esta página con
`shell-layout-design-system` y `sidebar-todos-modulos` antes del repaso del 2026-08-02.

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
