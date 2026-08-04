# Biblia · Plan de Compras v2

Escenarios `PDC-*`. La cadena presupuesto → maestro de insumos → paquetes de contratación → plan con
fechas → seguimiento.

Formato y reglas: `docs/flujos/README.md`. Verificado por lectura el **2026-08-04**.

**Dónde vive el código:** PHP en `src/Services/Pdc/` (13 servicios) y
`src/Controllers/Api/Pdc*.php`; la SPA en `pdc-app/` (React + Vite + AG Grid), que **se compila**
al bundle de `public/pdc-app/`. Cualquier afirmación sobre el comportamiento del cliente se lee en
`pdc-app/src/`, **nunca en el bundle**. El dominio lo fija `docs/pdc-v2.md`.

---

## PDC-001 · La autorización distingue lectura de escritura, y ambas dentro del mismo endpoint

- **Roles:** los que tengan `lps.pdc.ver` para consultar; `lps.pdc.editar` para modificar.
- **Pasos, en `PdcApiController::save()`:**
  1. Se exige siempre `lps.pdc.ver` (`:202`).
  2. Se calcula `$isEditOperation`: pertenece a una lista de nueve opciones
     (`nueva_sem`, `eliminar_sem`, `eliminar_actividad_pdc`, `restaurar_actividad_pdc`,
     `guardar_actividad_pdc`, `adjudicar_pdc`, `modificar`, `guardar_DefinirContratos`,
     `adjudicar_contrato`) **o** la petición trae `columna` (`:204-214`).
  3. Si es edición: **CSRF** (`requireValidCsrf`) y después `lps.pdc.editar` (`:216-219`).
- **Resultado esperado:** una lectura pasa con solo `ver`; una escritura exige token válido y
  permiso de edición. **En datos:** nada si falla cualquiera de los dos.
- **Verificación:** lectura — `src/Controllers/Api/PdcApiController.php:202-219`,
  `:1063` y `:1069` (los helpers `requirePermission` y `requireValidCsrf`).

> **Contraste que vale la pena tener escrito.** Aquí la lista de operaciones que exigen CSRF lleva
> una red: `|| isset($_POST['columna'])` atrapa cualquier edición de celda aunque su nombre no esté
> enumerado. Programación Semanal **no tiene esa red**, y por eso `sanear` se le escapó (ver
> `PS-002`). Mismo problema, dos módulos, y solo uno lo previó.

## PDC-002 · Crear y eliminar semanas exige permisos propios, además del de edición

- **Pasos:** dentro del `switch`, `nueva_sem` exige `lps.semana.crear` (`:223`) y `eliminar_sem`
  exige `lps.semana.eliminar` (`:230`) — **encima** del `lps.pdc.editar` ya comprobado.
- **Resultado esperado:** dos llaves para las operaciones que alteran el calendario del proyecto.
  Un rol puede editar el plan de compras sin poder crear ni borrar semanas.
- **Verificación:** lectura — `PdcApiController.php:223`, `:230`;
  `src/Security/RbacCatalog.php` (ambas claves en la lista de `D` y `R`).

## PDC-003 · La edición de una celda exige token y permiso antes de tocar nada

- **Pasos:** `updateCell()` llama a `requireValidCsrf()` y luego a
  `requirePermission('lps.pdc.editar')` **como primeras instrucciones**, antes incluso de validar
  parámetros.
- **Resultado esperado:** el orden importa y es el correcto: se rechaza al no autorizado antes de
  procesar su entrada.
- **Verificación:** lectura — `PdcApiController.php:281` y siguientes.

## PDC-004 · Consultar duraciones sugeridas es solo lectura

- **Pasos:** `duracionSugerida()` exige `lps.pdc.ver` y requiere el parámetro `paquete`.
- **Resultado esperado:** sin `paquete`, error de parámetros; sin permiso, rechazo. Nunca escribe.
- **Verificación:** lectura — `PdcApiController.php:1149` y siguientes.

## PDC-005 · Las plantillas son de consulta y no mutan

- **Pasos:** `PdcPlantillaController` expone `list`, `show`, `items` y `categorias`, todas tras
  `rbac_guard_require_permission` (`:197`).
- **Resultado esperado:** ninguna escritura. **Medido:** cero sentencias `INSERT`, `UPDATE` o
  `DELETE` en todo el controlador — por eso no valida CSRF, y es correcto que no lo haga.
- **Verificación:** lectura — `src/Controllers/Api/PdcPlantillaController.php:21,48,87,141,197`.

## PDC-006 · Dentro del propio PDC conviven dos mecanismos de autorización

Dato medido, y conviene conocerlo antes de tocar cualquiera de estos archivos:

| Controlador | Autoriza con | ¿CSRF? |
|---|---|---|
| `PdcApiController` | `rbac_guard_require_permission` (legado) | sí |
| `PdcPlantillaController` | `rbac_guard_require_permission` (legado) | no (no muta) |
| `PdcAutoGenerateController` | `authorizePermission` (moderno) | sí |
| `SemiAutoController` | `authorizePermission` (moderno) | sí |

- **Resultado esperado:** ambos caminos acaban consultando el mismo catálogo de permisos, así que el
  resultado debe ser idéntico para el mismo rol y clave. Si divergen, es hallazgo.
- **Verificación:** lectura — `grep -oE "authorizePermission|rbac_guard_require_permission"` sobre
  los cuatro archivos.

> Sumado a lo de T2, la aplicación tiene **cuatro formas de autorizar** y el PDC usa dos de ellas
> **dentro del mismo módulo**. No es un bug, pero es la razón por la que no se puede responder
> «¿quién puede hacer X?» leyendo un solo sitio.

## PDC-007 · Los contratos semi-automáticos son hoy exclusivos del PDC

- **Pasos:** las 13 rutas `auto/*` (`public/index.php:256-268`) son todas `/api/pdc/…`.
- **Resultado esperado según `AGENTS.md:23`:** deberían compartirse con Listado de Actividades y
  Contratos. **Hoy no es así**, y `SemiAutoController` conserva 12 métodos `*Listado` sin ruta.
- **Verificación:** lectura — ver la trampa `memoria/trampas/semi-auto-solo-lo-usa-pdc.md` y el
  hallazgo ya registrado en `docs/EXPERIMENTS.md`.

## PDC-008 · Toda escritura del plan de fechas se acota por subpaquete

El escenario que `docs/pdc-v2.md` marca como «el borrado más peligroso de `PlanFechasService`»:
los lotes de un mismo paquete **comparten `paso_id`**, así que una escritura acotada solo por
paquete arrastraría los pasos de sus hermanos.

- **Resultado esperado:** toda sentencia de borrado o actualización sobre `pdc_plan_paso`,
  `pdc_plan_paquete` y `pdc_paquete_frente` filtra por `project_id`, `paquete_id` **y
  `subpaquete_id`**.
- **Verificación — comprobado una a una el 2026-08-04, las seis cumplen:**

| Línea | Sentencia | Acotación |
|---|---|---|
| `:1083` | `DELETE FROM pdc_paquete_frente` | `project_id` + `paquete_id` + `subpaquete_id` |
| `:1119` | `DELETE FROM pdc_plan_paso` | los tres + `fecha_real IS NULL` |
| `:1129` | `UPDATE pdc_plan_paso SET fecha_inicio/fin = NULL` | los tres |
| `:1146` | `DELETE FROM pdc_plan_paquete` | los tres + `responsable_user_id IS NULL` + `NOT EXISTS` |
| `:1538` | `DELETE FROM pdc_plan_paso` (sobrantes) | los tres + `fecha_real IS NULL` |
| `:1549` | `UPDATE pdc_plan_paso` (sobrantes) | los tres + `fecha_real IS NOT NULL` |

> **Resultado positivo, y por eso merece estar escrito.** La deuda que el documento de dominio
> señalaba **está atendida**: ninguna de las seis escapa. Además, los borrados protegen lo ya
> ejecutado con `fecha_real IS NULL` —no se borra un paso que ya ocurrió— y el de `pdc_plan_paquete`
> respeta al responsable asignado. Que la biblia confirme lo correcto vale tanto como que encuentre
> lo roto: sin esta comprobación, la deuda seguiría figurando como pendiente.

---

## Escenarios pendientes de esta pasada

Esta primera pasada cubre **la puerta**: quién puede entrar a cada operación. Falta describir **la
cadena de dominio**, que es donde `docs/pdc-v2.md` ya avisa de deudas conocidas:

- **Presupuesto:** importar una versión, qué la marca `activo=1` y por qué solo puede haber una por
  proyecto; qué pasa al recargar un presupuesto sobre datos ya usados.
- **Maestro de insumos:** cómo se construye desde el presupuesto y qué ocurre con los insumos
  huérfanos.
- **Paquetes:** asignación manual frente a sugerencia del motor; la regla de que un MATERIAL no
  entra en un paquete `a_todo_costo` salvo que lo admita; partir en subpaquetes y el `es_resto`.
- **Plan con fechas:** el borrado que `docs/pdc-v2.md` marca como el más peligroso de
  `PlanFechasService` — toda escritura debe ir acotada por `subpaquete_id`, porque los lotes de un
  mismo paquete comparten `paso_id`. **Verificarlo es prioritario** y no se hizo en esta pasada.
- **Las deudas de datos** que `docs/pdc-v2.md` enumera, como escenarios de primera clase.
- **La SPA**: comportamiento de AG Grid leído de `pdc-app/src/`.
