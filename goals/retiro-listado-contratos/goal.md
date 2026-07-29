# Goal — Retiro de `/listado-actividades` y `/contratos`

**Slug:** `retiro-listado-contratos`
**Fecha de apertura:** 2026-07-29
**Estado:** abierto, sin ejecutar — **la etapa 3 exige gate explícito**

## Objetivo

Retirar del producto las dos superficies del PDC viejo que el usuario declaró deprecadas el
2026-07-29: `/listado-actividades` (Familias de Actividades) y `/contratos` (Paquetes de
Contratación). Retirar de verdad, no sólo esconder del menú.

## Por qué existe este goal y no es una tarea suelta

La decisión se tomó dentro del goal `dark-mode-todos-los-modulos`, que es **exclusivamente de
capa visual** (su propio `goal.md` lo dice). Retirar estas dos superficies toca funcionalidad,
RBAC, datos y un servicio compartido de ~3000 líneas. No cabe ahí.

Y no cabe tampoco en la fase **C1** del roadmap del PDC (`docs/pdc-v2.md:44`): C1 describe el
apagado de **`/pdc`** y del modelo de familias, depende de que A+B estén validados **en
producción**, e incluye la decisión sobre el histórico. Estas dos rutas comparten ese modelo pero
no son C1. Este goal es el trozo que sí se puede ejecutar antes, y marca dónde tiene que parar.

## Estado medido al abrir (2026-07-29)

Radio de impacto verificado por lectura del código, no estimado.

### Exclusivo de las dos superficies — borrable

| Artefacto | Rutas |
|---|---|
| Vistas | `views/listado-actividades/listadoActividades.view.php`, `views/contratos/contratos.view.php` |
| Controladores | `src/Controllers/Gestion/{ListadoActividades,Contratos}Controller.php` |
| Controladores API | `src/Controllers/Api/{ListadoActividades,Contratos}ApiController.php` |
| CSS | `public/css/listado-actividades.css`, `public/css/contratos.css` |
| JS | `public/js/modules/{listado_actividades,contratos}/hot.js` |
| Soporte | `src/Support/ActivityMatcher.php` — **ojo**, no confundir con `src/Services/ActivityMatcherService.php`, que sirve a `/api/general/auto-associate` y **sigue vivo** |
| Rutas | `public/index.php:131-132` (vistas), `145-159` y `161-178` (API) |
| Tabla | `contratos_trazabilidad` — sin lector fuera de estos dos controladores |

### Compartido con algo vivo — NO borrable aquí

- **`SemiAutoService`** (~3000 líneas) y `SemiAutoAssistantService`: constantes `MODULE_LISTADO`,
  `MODULE_CONTRATOS`, `MODULE_PDC` y ramas por módulo repartidas por todo el archivo. `/pdc` lo
  sigue usando. AGENTS.md declara `auto/*` contrato compartido y prohíbe flujos paralelos.
- **`public/js/modules/semi_auto_review.js`**: lo comparten los tres módulos.
- **`general_pdc_familias`**, `general_pdc_family_contract_options`,
  `general_pdc_family_contract_option_items`: las lee también
  `src/Support/OperationalFamilyPolicy.php:136`, que es del `/pdc` viejo. Son de C1, no de aquí.
- **`general_dias_procesos_contratacion`**: **la lee la PDC v2 viva** como puente de duraciones
  (`docs/pdc-v2.md:63-65`; 162 de 209 paquetes activos apuntan a ella). Intocable.
- **`actividades`**: tabla operativa central de ambos módulos. No se encontró otro lector, pero el
  nombre es genérico y el barrido de `src/Services/Bi/` no fue exhaustivo. **Verificar antes de
  cualquier decisión sobre los datos.**
- **`programa_consolidado`**: global y viva, sólo se lee por JOIN.

### Deuda de arranque: hoy dan 500

Las dos rutas mueren a media página desde que `views/partials/shell_sidebar.php` retiró sus ítems
del rail el 2026-07-29: los controladores siguen declarando `$shellActive`
(`ListadoActividadesController.php:42`, `ContratosController.php:48`) y
`DesignSystemComponent.php:393` lanza `InvalidArgumentException` cuando el activo no está entre
los ítems renderizados. **Decisión del usuario: no se arregla.** La etapa 1 lo vuelve irrelevante.

## Etapas

### Etapa 1 — Apagar el acceso *(reversible, sin gate)*

Retirar del router las dos rutas de vista y sus rutas API exclusivas. Las URLs pasan a **404 en
vez de 500**, que es la respuesta honesta para algo retirado. Nada se borra todavía: el código
queda en el árbol, y revertir es reponer unas líneas de `public/index.php`.

Arrastra: retirar sus entradas de `pathBudgets` en `docs/design-system/exceptions.json`
(`"contratos"` y `"listado-actividades"`) — si no, el audit falla por rutas inexistentes en cuanto
se borren los archivos.

### Etapa 2 — Recoger el rastro *(reversible, sin gate)*

- Borrar vistas, controladores, controladores API, CSS, JS y `src/Support/ActivityMatcher.php`.
- Tests **exclusivos**, retirar: `tests/browser/{contratos-aviso-global-alert,contratos-cantidad-a11y,contratos-handsontable,contratos-slot-quantities,listado-actividades-handsontable,auto-definir-contratos}.mjs`,
  `tests/test_contratos_modern_assistant_replaces_auto_define_ui.php`,
  `tests/test_auto_definir_contratos.php`,
  `e2e/tests/workflows/{listado-full,contratos-full}.spec.mjs`.
- Tests **compartidos**, reescribir sin borrar: `tests/test_legacy_absence_for_lacp_runtime.php` y
  `tests/test_lacp_legacy_cleanup_readiness.php` (cubren además `/pdc`),
  `e2e/tests/workflows/procurement-flow.spec.mjs` (flujo largo que atraviesa los dos módulos),
  y los helpers `tests/browser/support/*` y `e2e/support/apiPayloads.mjs`.
- Limpiar RBAC: `lps.listado_actividades.*` y `lps.contratos.*` de `RbacCatalog.php` (líneas 8,
  110-114, 162-163, 181-182, 200, 234-238, 271, 288, 306-309) y `RbacService.php:103-105`.
  Cada clave está en varios arrays de roles: es limpieza repartida, no un borrado puntual.
- Actualizar `docs/VISTAS-MODULOS.md:26-27, 323-359`.

### Etapa 3 — Cirugía en `SemiAutoService` *(requiere gate explícito)*

Retirar las ramas `MODULE_LISTADO` y `MODULE_CONTRATOS` del servicio compartido y de
`SemiAutoController`, dejando sólo `MODULE_PDC`. Alto riesgo: archivo grande, contrato declarado
compartido por AGENTS.md, y `/pdc` depende de él. **No se ejecuta sin plan propio y aprobación.**

Se puede posponer indefinidamente: con las rutas apagadas, las ramas quedan inertes.

### Etapa 4 — Datos *(fuera de este goal)*

Qué pasa con `actividades` y `contratos_trazabilidad`. Es la misma pregunta que C1 se hace sobre
el histórico del PDC v1 y se responde ahí, con dry-run, gate de Plannotator, respaldo verificable y
plan de restauración, como exige `docs/global-tables-architecture.md`. **Este goal no borra ni un
registro.**

## Fuera de alcance

- `/pdc` y el modelo de familias: es C1.
- Cualquier borrado de datos.
- Reescribir el flujo `auto/*` o crear uno paralelo.

## Criterio de cierre (etapas 1 y 2)

1. `/listado-actividades` y `/contratos` responden 404.
2. Ningún archivo exclusivo de las dos superficies queda en el árbol.
3. `docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G` en verde.
4. Los tests compartidos reescritos pasan, y ninguno se adaptó para ocultar una regresión.
5. `node scripts/design-system-audit.mjs` pasa sin las dos entradas de `pathBudgets`.
6. Etapas 3 y 4 anotadas como abiertas, con su condición de arranque.
