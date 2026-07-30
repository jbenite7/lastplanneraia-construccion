# C1 · Censo de consumidores del PDC v1 y medición de sus datos

- **Fecha:** 2026-07-29
- **Goal:** `goals/pdc-preparar-b1` · tarea 10 (C1)
- **Spec:** `docs/superpowers/specs/2026-07-29-c1-retiro-pdc-viejo-design.md`
- **Cubre:** puntos 1 y 2 de la condición de hecho (censo escrito con el grep como evidencia; medición
  para que el dueño del producto pueda decidir sobre los datos históricos).
- **Base del censo:** `main` en `a083d6c`. Solo lectura: no se retiró, borró ni modificó nada.

**Lo que este documento NO hace:** no autoriza el retiro. La precondición del roadmap —PDC v2 validado en
producción con una obra trabajando de verdad— sigue incumplida; la fila 4 de `estado-olas.md` está
`PENDIENTE`. Esto es el trabajo previo que no depende de esa puerta.

## Método

Grep sobre `public`, `views`, `src`, `pdc-app/src`, `tests`, `e2e`, `admin`, `scripts` y
`docs/design-system`, excluyendo `node_modules` y el bundle compilado `public/pdc-app/`, y **filtrando
las líneas que son comentario** (`//`, `#`, `*`, `/*`) para no confundir una mención histórica con un
consumidor vivo. Esa precaución es la lección del repositorio: un CSS declarado «solo comentarios
históricos» tenía un consumidor vivo en JavaScript. Aquí volvió a pagar (ver hallazgo 2).

## Hallazgo 1 — `/api/pdc/*` NO es todo del módulo viejo. Retirar el prefijo entero rompe cosas vivas

El spec dice «la ruta `/pdc` y las `/api/pdc/*` del front controller». Tomado al pie de la letra es
**demasiado ancho**. Bajo ese prefijo conviven tres grupos con dueños distintos:

| Grupo | Rutas | ¿Es del PDC viejo? |
|---|---|---|
| **A. Núcleo v1** | `/api/pdc/list`, `/save`, `/update-cell`, `/duracion-sugerida` (`public/index.php:180-183`, `PdcApiController`) | **Sí.** Se retira. |
| **B. Plantillas** | `/api/pdc/plantillas`, `/plantillas/{id}`, `/plantillas/{id}/items`, `/categorias-recurso` (`index.php:272-275`, `PdcPlantillaController`) | **Sí, y ya están muertas** — ver hallazgo 3. |
| **C. Semi-automático compartido** | los 13 `/api/pdc/auto/*` (`index.php:276-288`) | **No del todo.** Doce apuntan a `SemiAutoController`, el mismo controlador que sirve a `/contratos` y `/listado-actividades` bajo los contratos `auto/preview·apply·undo·feedback·metrics` que `AGENTS.md` declara compartidos. Retirar el *namespace* `pdc` de ese grupo es una decisión de producto aparte, no un daño colateral del retiro. |

El v2 **no** vive aquí: usa `/plan-compras/api/*` (`index.php:185-270`). El comentario de `index.php:184`
ya lo advertía. Consecuencia práctica: un retiro por prefijo (`/api/pdc/`) es incorrecto; hay que retirar
**ruta por ruta**.

## Hallazgo 2 — la lección se repitió: `hot.js` es un consumidor vivo

`public/js/modules/pdc/hot.js` llama a `/api/pdc/list` (líneas 651 y 743) y a `/api/pdc/save` (951 y
1071). Lo carga `views/pdc/pdc.view.php:1374`. Además la propia vista hace cuatro llamadas directas a
`/api/pdc/save` (líneas 699, 787, 889, 1292) y una a `/api/pdc/auto/apply-from-contratos` (1173).

No es código muerto: son cuatro archivos que caen juntos o no cae ninguno.

## Hallazgo 3 — rutas ya muertas hoy, antes de retirar nada

Las cuatro rutas de **plantillas** (grupo B) no tienen **ningún** consumidor fuera de su propia línea en
`index.php`: ni en `public/js`, ni en `views`, ni en `src`, ni en `pdc-app/src`, ni en tests. Y sus tablas
(`general_pdc_plantillas`, `general_pdc_plantilla_items`, `general_pdc_categoria_recurso`) **no existen en
la base de desarrollo**. Es decir: `PdcPlantillaController` responde 500 si alguien lo llama.

Lo mismo con `/api/pdc/update-cell` y `/api/pdc/duracion-sugerida`: cero consumidores fuera de `index.php`.

Son el trozo más barato del retiro y no dependen de la precondición para estar medidos, aunque sí para
tocarse.

## Hallazgo 4 — `OperationalFamilyPolicy` NO se puede retirar

El spec lo condicionaba a «si no lo consume nada más». **Lo consumen otros, y no son del PDC viejo:**

- `src/Support/ActivityMatcher.php:13,18` — lo instancia en el constructor.
- `src/Services/SemiAutoService.php:9,27,57,60` — lo instancia perezosamente.

`SemiAutoService` es el motor semi-automático **compartido** por Listado de Actividades, Contratos y PDC
(`SemiAutoController`, `GeneralApiController:1324`). `ActivityMatcher` es transversal. Además tres tests
lo cubren directamente: `test_operational_family_policy.php`, `test_contractual_family_routing.php`,
`test_listado_contractual_exclusion_real_projects.php` — y este último se llama, literalmente, «listado
contractual», no «pdc».

Sus tablas tampoco están vacías: `general_pdc_familias` 205 filas, `general_pdc_contractual_elements` 28,
`general_pdc_family_aliases` 13, y `general_pdc_project_family_strategy` tiene 18 filas de la obra 74.

**Conclusión: el modelo de «familias» sale del alcance de C1.** El prefijo `general_pdc_*` de sus tablas
es engañoso; el dueño real es el motor semi-automático. Retirarlo rompería Listado de Actividades y
Contratos, que no están en retiro.

## Hallazgo 5 — 35 archivos de prueba tocan la superficie del retiro

`tests/browser/pdc-handsontable.mjs`, `tests/browser/test-pdc.mjs`, `e2e/tests/workflows/pdc-full.spec.mjs`
y `tests/test_pdc_security_and_restore_contract.php` son específicos del v1 y caen con él. Pero hay
**fixtures compartidos** que lo dan por vivo y romperían a otros módulos si se borra sin más:

- `tests/browser/support/operationalCycle.mjs:695-740` — el ciclo operativo completo navega a `/pdc`.
- `tests/browser/support/moduleFlows.mjs:298-303` — flujo por módulo.
- `e2e/support/apiPayloads.mjs:89-92` y `e2e/tests/smoke/routes.spec.mjs:35`.
- `tests/browser/shell-sidebar-rollout.mjs:29,40` — `/pdc` está en la lista `MIGRATED`.
- Suites de diseño que iteran rutas: `design-system-body-canvas-dark.mjs`, `design-system-compliance.mjs`,
  `design-system-consumer-smoke.mjs`, `modales-dark-homologacion.mjs`, `state-tint-ladder.mjs`,
  `info-nav-focus-visible.mjs`, `pdc-chips-dark.mjs`, `runtime-budget.test.mjs`.

Y dos que afirman lo contrario y hay que releer, no borrar: `test_shell_sidebar_partial.php:44` asegura que
la navegación de Admin **ya no** enlaza al módulo viejo, y `test_lacp_legacy_cleanup_readiness.php:37`
mapea el legado `/legacy/pdc/actualizar_pdc.php` hacia `/api/pdc/auto/apply-from-contratos` — o sea, esa
ruta del grupo C es la **sucesora moderna** de un legado ya retirado. Retirarla resucita la deuda.

## Hallazgo 6 — dos entradas contradictorias del PDC en el inventario de diseño

`docs/design-system/manifests/inventory.json` lista `moduleId: "pdc"` **dos veces** en `modules`:

- una como `"status": "inventory-only", "manifest": null`
- otra como `"status": "pilot", "manifest": "pdc.json"` (con la nota de cierre de F2)

`docs/design-system/manifests/pdc.json` existe y **sí** tiene escenario con golden real
(`pdc-dark-1180x820.png`, `sha256: ec509b5…`). O sea: el módulo **viejo** tiene manifiesto de piloto; el
**nuevo** (`plan-compras-v2`) es el que está `inventory-only` con `manifest: null`.

Esto tiene dos consecuencias, y conviene no confundirlas:

1. La duplicación es un defecto del inventario, previo a C1 y ajeno a él. Se reporta, no se arregla aquí.
2. Cuando C1 retire el v1 habrá que **retirar también `pdc.json`** y su golden — y ese manifiesto es hoy
   el único ejemplo trabajado de la familia `page-structure` para esta superficie. Conviene cerrar la deuda
   del v2 **antes** de retirar el del v1, no después.

## Medición de los datos históricos del v1

**Advertencia que condiciona todo lo que sigue: estos números son de la base de DESARROLLO**
(`lastplanneraia_dev`, stack `last-planner-aia`, puerto 3307). **No son los de producción.** No tengo
acceso de lectura a la base de producción desde aquí, y la decisión del dueño del producto debería tomarse
sobre los números reales. Sirven para dimensionar el problema y para saber qué preguntar.

### Tabla `pdc` — el dato operativo del v1

370 filas, **4 obras**, 176 KB en total (datos + índices).

| `project_id` | Filas | Semanas | Nº contrato | Legalizados | Valor adjudicado | Observaciones |
|---|---|---|---|---|---|---|
| 73 | 292 | 4 | 0 | 4 | 0 | 4 |
| 27 | 33 | 6 | 0 | 0 | 0 | 0 |
| 68 | 24 | 5 | 0 | 0 | 0 | 0 |
| 74 | 21 | 5 | 0 | 0 | 0 | 0 |

`papelera_pdc` (la papelera del módulo) está **vacía**: 0 filas.

**Lectura honesta de esta tabla:** en desarrollo el contenido es esquelético. Ninguna fila tiene número de
contrato ni valor adjudicado; solo la obra 73 tiene cuatro filas con fecha real de legalización y
observaciones. Parece dato de prueba, no de una obra trabajando. Pero **eso no autoriza a concluir que en
producción pase lo mismo** — es exactamente la pregunta que hay que medir allí.

### Dos límites del propio esquema, que importan para decidir

1. **La tabla `pdc` no tiene `created_at` ni `updated_at`.** No existe forma de saber desde los datos
   cuándo se tocó por última vez una obra. La pregunta «¿alguien todavía los mira?» **no se puede responder
   con SQL**: hay que preguntárselo a las obras, o mirar accesos en el servidor.
2. **No hay tabla de proyectos en esta base** (`projects`/`proyectos`/`obras` no existen en ningún esquema
   del contenedor). No puedo traducir los `project_id` 27, 68, 73 y 74 a nombres de obra. En producción sí
   debería poderse.

### Lo que hay que preguntar en producción, antes de decidir

Consultas de solo lectura, sin efectos:

```sql
SELECT COUNT(*) filas, COUNT(DISTINCT project_id) obras FROM pdc;
SELECT project_id, COUNT(*) filas, COUNT(DISTINCT semana) semanas,
       SUM(numeroContrato IS NOT NULL AND numeroContrato<>'') con_num_contrato,
       SUM(valorAdjudicado > 0) con_valor,
       SUM(fechaRealLegalizacionContrato IS NOT NULL) legalizados
FROM pdc GROUP BY project_id ORDER BY filas DESC;
SELECT COUNT(*) FROM papelera_pdc;
```

## Decisión que corresponde al dueño del producto (Felipe), no al criterio técnico

Con lo medido, la pregunta se puede formular en concreto:

> De las obras que tengan filas en la tabla `pdc` de producción, ¿alguna guarda ahí información que
> todavía se consulte —valores adjudicados, números de contrato, observaciones— y que **no** esté ya en el
> PDC v2? ¿Hace falta conservarla en frío, o basta con el respaldo de la base?

Tres opciones, con lo que cuesta cada una:

| Opción | Qué implica | Coste | Reversible |
|---|---|---|---|
| **A. Conservar en frío y no borrar** | Se retiran rutas, vista, CSS y JS; las tablas `pdc` y `papelera_pdc` se quedan quietas, sin lectores | Casi cero — 176 KB en dev | Sí |
| **B. Exportar y luego borrar** | Volcado a CSV/SQL archivado fuera de la base, y `DROP` con respaldo verificable y plan de restauración | Medio; exige el gate destructivo de `AGENTS.md` | Sí, si el respaldo se verifica |
| **C. Borrar sin más** | — | — | **No.** Contraviene `docs/global-tables-architecture.md` |

**Mi recomendación, y es solo eso:** la **A**. El coste de conservar es despreciable y el retiro del
código —que es lo que de verdad estorba— no necesita que las tablas desaparezcan. Separar «retirar la
pantalla» de «borrar los datos» permite hacer lo primero sin arriesgar lo segundo, y deja la decisión
sobre los datos para cuando haya certeza de que nadie los mira. Cualquier borrado, si se elige, exige el
gate de Plannotator, respaldo verificable y estrategia de restauración.

## Alcance corregido de C1, según lo medido

**Se retira** (cuando la precondición se cumpla):

- `/pdc` (`index.php:127`) y `PdcController`.
- Grupo A: `/api/pdc/list`, `/save`, `/update-cell`, `/duracion-sugerida` y `PdcApiController`.
- Grupo B: las cuatro rutas de plantillas y `PdcPlantillaController` (ya muertas).
- `views/pdc/pdc.view.php`, `public/css/pdc.css`, `public/js/modules/pdc/hot.js`.
- La entrada de navegación: `views/design-system/families/shell-navigation.php:45` y
  `public/js/modules/info_general_nav.js:23,42`.
- `docs/design-system/manifests/pdc.json`, su golden, y las entradas de `pdc` en `inventory.json`,
  `exceptions.json`, `state-token-exceptions.json`, `ui-groups-inventory.json`,
  `unlayered-delivery-inventory.json`.
- Los tests específicos del v1, y la poda de `/pdc` en los fixtures compartidos del hallazgo 5.

**NO se retira, contra lo que suponía el spec:**

- `OperationalFamilyPolicy` y el modelo de familias (hallazgo 4). Sale del alcance.
- El grupo C (`/api/pdc/auto/*`): decisión de producto aparte. Y
  `/api/pdc/auto/apply-from-contratos` es la sucesora moderna de un legado ya retirado.
- `PdcResetService` y `PdcMaintenanceController` — son del **v2**, punto 5 de la condición de hecho.

## Estado de la condición de hecho

| # | Punto | Estado |
|---|---|---|
| 1 | Censo escrito con el grep como evidencia | **Hecho** — este documento |
| 2 | Decisión escrita del dueño sobre los datos históricos | **Medido y planteado; falta la respuesta de Felipe** |
| 3 | Rutas retiradas, app arranca, suite pasa, nada enlaza a `/pdc` | Bloqueado por la precondición |
| 4 | Manifiesto de piloto del v2 | Pendiente — no depende de la precondición |
| 5 | `PdcResetService` sigue vivo | Pendiente de verificar tras el retiro |
