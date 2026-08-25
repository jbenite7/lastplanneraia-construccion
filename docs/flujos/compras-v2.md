---
capa: fuente
tipo: biblia
estado: vigente
fecha: 2026-08-04
areas: [lps]
fuente: docs/flujos/compras-v2.md
resumen: Escenarios PDC-. La cadena presupuesto → maestro de insumos → paquetes de contratación → subpaquetes → plan con fechas → seguimiento.
---

# Biblia · Plan de Compras v2

Escenarios `PDC-*`. La cadena presupuesto → maestro de insumos → paquetes de contratación →
subpaquetes → plan con fechas → seguimiento.

Formato y reglas: `docs/flujos/README.md`. Verificado por lectura el **2026-08-04**.

---

## Qué es v2 y qué no

**Decisión del usuario del 2026-08-04: el PDC v1 queda deprecado.** Esta biblia describe **solo
v2**; la que describía v1 se retiró el mismo día en vez de mantenerse en paralelo.

Distinguirlos es lo primero, porque los nombres se parecen y las rutas no:

| | v1 (deprecado) | **v2 (lo que describe este documento)** |
|---|---|---|
| Vista | `/pdc` → `Gestion\PdcController` | `/plan-compras` → `Gestion\PlanComprasController` |
| API | `/api/pdc/*` (21 rutas) | **`/plan-compras/api/*` (70 rutas)** |
| Controladores | `PdcApiController`, `PdcPlantillaController`, `SemiAutoController`, `PdcAutoGenerateController` | **nueve `PlanCompras*Controller`** |
| Cliente | vistas PHP + `semi_auto_review.js` | **SPA React + Vite + AG Grid en `pdc-app/`** |
| Módulos de wiki que cuelgan | «Listado de Actividades (PDC v1)» y «Contratos» | — |

**Sobre la SPA:** se compila al bundle de `public/pdc-app/`. Cualquier afirmación sobre el
comportamiento del cliente se lee en `pdc-app/src/` (72 archivos), **nunca en el bundle**. El
dominio lo fija `docs/pdc-v2.md`.

Los nueve controladores, por etapa de la cadena:

| Controlador | Cubre |
|---|---|
| `PlanComprasApiController` | Contexto de la SPA |
| `PlanComprasImportController` | Presupuesto: preview, confirmar, versiones, activar, impacto, árbol, comparar |
| `PlanComprasMaestroController` | Maestro de insumos: catálogo, vínculos, sugerencias, equipos |
| `PlanComprasMaestroImportController` | Importación del maestro |
| `PlanComprasPaquetesController` | Paquetes de contratación |
| `PlanComprasSubpaquetesController` | Subpaquetes |
| `PlanComprasPlanController` | Plan con fechas y reglas |
| `PlanComprasSeguimientoController` | Seguimiento |
| `PlanComprasController` | La vista `/plan-compras` |

## PDC-001 · Todo el v2 valida CSRF

- **Resultado esperado:** cada mutación exige token válido; sin él, rechazo sin escribir.
- **Verificación:** lectura — **los nueve controladores importan y usan `CsrfTokenManager`**,
  comprobado con `grep -oE "CsrfTokenManager" src/Controllers/**/PlanCompras*.php`.

> **Contraste que conviene tener presente.** Esta cobertura del 100 % es justo lo contrario de lo
> que ocurre en los seis módulos del hallazgo `SOP-002` (CIC, CNC, CNP, subcontratistas,
> profesionales, control de cambios), que mutan **sin** validar token. El v2 se escribió con la
> defensa puesta desde el principio.

## PDC-002 · La autorización usa `RbacService::can()`, un quinto mecanismo

- **Pasos:** los controladores de API no usan `authorizePermission()` ni `rbac_guard_*`, sino
  `(new RbacService($this->db))->can('<clave>')` devolviendo rechazo si es falso.
- **Resultado esperado:** mismo catálogo de permisos, comprobación explícita por endpoint.
- **Verificación:** lectura — `PlanComprasMaestroController.php:226,240`;
  `PlanComprasPaquetesController.php:236,250`; `PlanComprasPlanController.php:96,267,573,587`.

> Con esto, la aplicación tiene **cinco formas de autorizar**: `authorizePermission` (programa
> general, intermedia), guardias propias (semanal), `rbac_guard` legado (CNP/CNC/CIC y soporte),
> y `RbacService::can()` (PDC v2) — más lo que quede de v1 mientras siga en pie. Todas consultan el
> mismo catálogo; ninguna se parece a la de al lado.

## PDC-003 · Consultar y modificar el maestro son llaves distintas

- **Roles:** `lps.pdc.ver` para consultar el catálogo; **`lps.pdc.maestro`** para modificarlo.
- **Resultado esperado:** el maestro de insumos tiene su **propia llave**, separada de
  `lps.pdc.editar`. Quien puede editar el plan no necesariamente puede tocar el maestro, que es la
  fuente de la que todo lo demás deriva.
- **Verificación:** lectura — `PlanComprasMaestroController.php:226` (`lps.pdc.ver`) y `:240`
  (`lps.pdc.maestro`).

> `lps.pdc.maestro` la tienen `A` y `D` en `RbacCatalog::fallbackPermissionsByRole()`; **el
> Residente no**. Es coherente con que el maestro sea infraestructura del proyecto y no trabajo
> semanal.

## PDC-004 · Los paquetes separan ver, editar y definir reglas

- **Claves:** `lps.paquetes_contratacion.ver`, `.editar` y **`.reglas`**.
- **Resultado esperado:** cambiar una asignación puntual y cambiar **la regla que gobierna todas
  las asignaciones** son permisos distintos. La tercera llave solo se exige cuando el alcance de la
  operación es `global`.
- **Verificación:** lectura — `PlanComprasPaquetesController.php:236,250`;
  `PlanComprasPlanController.php:96` (la comprobación condicionada a `$alcance === 'global'`).

> Es la distinción de permisos mejor pensada que hemos encontrado en toda la biblia: el mismo
> endpoint pide más llave cuando el efecto es más ancho.

## PDC-005 · Toda escritura del plan de fechas se acota por subpaquete

El escenario que `docs/pdc-v2.md` marca como «el borrado más peligroso de `PlanFechasService`»: los
lotes de un mismo paquete **comparten `paso_id`**, así que una escritura acotada solo por paquete
arrastraría los pasos de sus hermanos.

- **Resultado esperado:** toda sentencia de borrado o actualización sobre `pdc_plan_paso`,
  `pdc_plan_paquete` y `pdc_paquete_frente` filtra por `project_id`, `paquete_id` **y
  `subpaquete_id`**.
- **Verificación — comprobadas una a una el 2026-08-04, las seis cumplen:**

| Línea de `src/Services/Pdc/PlanFechasService.php` | Sentencia | Acotación |
|---|---|---|
| `:1083` | `DELETE FROM pdc_paquete_frente` | los tres |
| `:1119` | `DELETE FROM pdc_plan_paso` | los tres + `fecha_real IS NULL` |
| `:1129` | `UPDATE pdc_plan_paso` (fechas a NULL) | los tres |
| `:1146` | `DELETE FROM pdc_plan_paquete` | los tres + `responsable_user_id IS NULL` + `NOT EXISTS` |
| `:1538` | `DELETE FROM pdc_plan_paso` (sobrantes) | los tres + `fecha_real IS NULL` |
| `:1549` | `UPDATE pdc_plan_paso` (sobrantes) | los tres + `fecha_real IS NOT NULL` |

> **Resultado positivo y por eso escrito.** La deuda que el documento de dominio señalaba **está
> atendida**: ninguna de las seis se escapa, los borrados protegen lo ya ejecutado con `fecha_real`
> y el de `pdc_plan_paquete` respeta al responsable asignado. `PlanFechasService` es de v2 —vive en
> `src/Services/Pdc/` y lo consume `PlanComprasPlanController`—, así que esta comprobación **sobrevive
> a la deprecación de v1**.

---

## Escenarios pendientes de esta pasada

Esta primera pasada cubre **la puerta** —quién entra a cada operación y con qué llave— y la
acotación del plan de fechas. Falta la cadena de dominio, que es donde `docs/pdc-v2.md` avisa de
deudas conocidas:

- **Presupuesto:** las siete rutas de `PlanComprasImportController` (preview, confirmar, versiones,
  activar, impacto, árbol, comparar); qué marca una versión como activa y por qué solo puede haber
  una por proyecto; qué pasa al recargar sobre datos ya usados.
- **Maestro de insumos:** cómo se construye desde el presupuesto, los vínculos pendientes, y el
  contador de «reenganchados» que el propio código explica en `PlanComprasMaestroController:172`.
- **Paquetes y subpaquetes:** asignación manual frente a sugerencia; la regla del MATERIAL en
  paquete `a_todo_costo`; partir en subpaquetes y el `es_resto`.
- **Seguimiento:** entero.
- **La SPA:** comportamiento de AG Grid leído de `pdc-app/src/`.
- **Las deudas de datos** de `docs/pdc-v2.md`, como escenarios de primera clase.
