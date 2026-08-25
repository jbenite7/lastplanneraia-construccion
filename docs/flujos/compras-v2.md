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

Formato y reglas: `docs/flujos/README.md`. Verificado por lectura el **2026-08-04**, ampliado el
**2026-08-25** (Presupuesto y Seguimiento). Sigue abierto: Maestro de insumos, Paquetes,
Subpaquetes, la SPA y las deudas de datos.

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

## PDC-006 · Preview valida todo y no persiste nada

- **Rol:** `lps.pdc.importar`.
- **Pasos:** `preview()` recibe el `.xlsx`, lo valida en cinco capas (tamaño de subida ≤10MB antes
  incluso de leerlo, `UPLOAD_ERR_*`, tamaño real, extensión, MIME real por `finfo`), lo parsea y
  responde con un `importToken` de un solo uso.
- **Resultado esperado:** ninguna de las cinco validaciones escribe en base de datos. Un archivo
  que falla en cualquier capa no deja rastro más allá del temporal de subida de PHP.
- **Verificación:** lectura — `src/Controllers/Api/PlanComprasImportController.php:32-91`.

## PDC-007 · Confirmar consume el token del preview — una sola vez

- **Pasos:** `confirmar()` recibe el `importToken` del preview. `PresupuestoImportService::confirmar()`
  busca el archivo temporal y sus metadatos por token; si no existen, **primero comprueba
  idempotencia** —si ese token ya produjo una versión (reintento tras un timeout con el commit ya
  hecho en el servidor), devuelve esa misma versión marcada `idempotente: true`— y solo si tampoco
  hay versión asociada, responde `TOKEN_EXPIRED` (410).
- **Resultado esperado:** un `importToken` reutilizado nunca crea una segunda versión ni falla de
  forma ambigua para un cliente que reintentó una petición que sí llegó a completarse en servidor.
- **Verificación:** lectura — `src/Services/Pdc/PresupuestoImportService.php:366-378`.

## PDC-008 · Solo una versión activa por proyecto, y el cambio es transaccional

- **Pasos:** `activar()` y `confirmar()` comparten el mismo patrón: dentro de una transacción,
  `UPDATE ... SET activa = 0 WHERE project_id = ? AND activa = 1` **antes** de fijar la nueva con
  `activa = 1`. No hay una restricción `UNIQUE` en `pdc_presupuesto_versiones` que lo garantice a
  nivel de esquema — la invariante «una activa por proyecto» la sostiene el código, en dos rutas
  distintas que hacen exactamente el mismo par de sentencias en el mismo orden.
- **Resultado esperado:** en cualquier momento, `SELECT COUNT(*) FROM pdc_presupuesto_versiones
  WHERE project_id = ? AND activa = 1` da 0 o 1, nunca más.
- **Verificación:** lectura — `src/Services/Pdc/PresupuestoImportService.php:118-126` (`activar`),
  `:407-414` (`confirmar`).

## PDC-009 · Recargar el mismo contenido no crea versión — recargar contenido distinto sí, y no borra la anterior

- **Pasos:** antes de insertar, `confirmar()` calcula un hash del contenido parseado
  (`hashContenido()`, sobre ítems e insumos, no sobre el archivo) y lo compara con el de la versión
  activa.
- **Resultado esperado, dos caminos:**
  - **Contenido idéntico:** `sinCambios: true`, la versión activa no cambia, no se inserta fila
    nueva. Evita que recargar el mismo Excel por error infle el historial de versiones.
  - **Contenido distinto:** nueva fila en `pdc_presupuesto_versiones` con `version_numero` = máximo
    + 1, la anterior pasa a `activa = 0` **pero no se borra** — sigue consultable por
    `PDC-010`/`comparar`.
- **Verificación:** lectura — `src/Services/Pdc/PresupuestoImportService.php:388-435`.

## PDC-010 · La comparación entre versiones y el árbol son de solo lectura, y respetan `versión ninguna`

- **Pasos:** `arbol()` y `comparar()` (`RbacService::can('lps.pdc.ver')`, sin `guardEscritura`) leen
  sin mutar. `arbol()` responde `404 NO_VERSION` si el proyecto no tiene ninguna versión importada
  todavía o si se pide explícitamente una `versionId` que no existe; `comparar()` exige dos
  `versionId` distintos (`422 PARAMS_INVALIDOS` si son iguales o faltan) y `404 NO_VERSION` si
  alguna de las dos no pertenece al proyecto.
- **Resultado esperado:** un proyecto sin presupuesto importado nunca revienta con un error de
  servidor al pedir su árbol — responde un 404 de dominio, consultable y distinguible de «no
  autorizado» (403) o «sin proyecto activo» (409).
- **Verificación:** lectura — `src/Controllers/Api/PlanComprasImportController.php:178-233`.

---

## Seguimiento

`PlanComprasSeguimientoController` — 4 rutas. Permiso propio, distinto del de importar:
`lps.paquetes_contratacion.ver`/`.editar`. Es el módulo declarado «entero» pendiente en la primera
pasada.

## PDC-011 · Registrar un avance exige la cuaterna completa: proyecto, paquete, subpaquete y paso

El comentario del propio código lo explica mejor que cualquier paráfrasis: en un paquete partido en
lotes, **los lotes comparten el mismo `paso_id`** — sin acotar también por `subpaqueteId`, registrar
«propuestas recibidas» en el lote de porcelanato lo marcaría también en el de cerámica, que no ha
recibido nada.

- **Pasos:** `registrarPaso()` primero comprueba que la cuaterna
  `(project_id, paqueteId, subpaqueteId, pasoId)` exista en `pdc_plan_paso` — no que el paso exista
  en el catálogo, sino que **ese** paso pertenezca al plan de **ese** destino en **este** proyecto.
  Si no, `422 PASO_INVALIDO` antes de tocar nada.
- **Resultado esperado:** un `pasoId` válido en general pero que no pertenece al paquete/subpaquete
  pedido se rechaza, no se acepta contra el paquete equivocado.
- **Verificación:** lectura — `src/Services/Pdc/SeguimientoService.php:218-235`.

## PDC-012 · Deshacer un registro borra también quién lo registró

- **Pasos:** `fechaReal: null` es un valor legítimo del contrato (deshacer), distinto de «no vino el
  campo» — el controlador ya lo distingue antes de llamar al servicio (`crudo === null || crudo ===
  ''` → `null` explícito). El `UPDATE` siempre escribe las tres columnas juntas:
  `fecha_real`, `registrado_por`, `registrado_at`.
- **Resultado esperado:** deshacer una fecha deshace también su auditoría. No queda un
  «lo registró Fulano» huérfano sobre una casilla vacía, que generaría preguntas sin respuesta sobre
  quién tocó qué.
- **Verificación:** lectura — `src/Services/Pdc/SeguimientoService.php:237-247`;
  `src/Controllers/Api/PlanComprasSeguimientoController.php:133-137` (el `null`/cadena vacía se
  normalizan antes de llegar al servicio).

## PDC-013 · Las fechas de avance exigen formato estricto — no lo que `strtotime` adivine

- **Pasos:** `registrarPaso()` valida con `DateTimeImmutable::createFromFormat('!Y-m-d', ...)` y
  además compara que el `format('Y-m-d')` resultante sea idéntico a la entrada.
- **Resultado esperado:** `'15/04/2026'` se rechaza (`422 FECHA_INVALIDA`) en vez de interpretarse al
  revés en silencio. El comentario del propio código lo dice: una fecha «silenciosamente equivocada»
  no la detecta nadie hasta que la proyección sale rara semanas después.
- **Verificación:** lectura — `src/Services/Pdc/SeguimientoService.php:209-216`.

## PDC-014 · El resumen distingue «desactualizado por cronograma» de «sin avance»

- **Pasos:** `resumen()` devuelve dos cosas separadas: el resumen de avance y una lista aparte de
  `paquetesDesactualizados()` — paquetes cuyo amarre se calculó contra un cronograma que ya cambió.
- **Resultado esperado:** «este paquete se calculó contra un cronograma viejo» es una propiedad del
  **amarre**, no del avance, y el tablero puede decirlo sin mezclarla con el progreso real de pasos.
- **Verificación:** lectura — `src/Controllers/Api/PlanComprasSeguimientoController.php:33-45`
  (comentario propio del código, no inferencia).

## PDC-015 · «Responsable = sin dueño» es un filtro de primera clase, no la ausencia de filtro

- **Pasos:** `vencimientos()` distingue tres estados del parámetro `responsable`: ausente (no
  filtrar), `'sin'` (filtrar por **sin** responsable asignado, valor especial reservado), o un
  entero (filtrar por ese usuario). Sin el valor especial `'sin'`, no habría forma de pedir «los
  paquetes sin dueño» — sería indistinguible de «no filtres».
- **Verificación:** lectura — `src/Controllers/Api/PlanComprasSeguimientoController.php:53-58`.

---

## Escenarios pendientes de esta pasada

Esta primera pasada cubre **la puerta** —quién entra a cada operación y con qué llave— y la
acotación del plan de fechas. Falta la cadena de dominio, que es donde `docs/pdc-v2.md` avisa de
deudas conocidas. **Cubierto el 2026-08-25:** Presupuesto (`PDC-006` a `PDC-010`) y Seguimiento
(`PDC-011` a `PDC-015`) — 11 de las 70 rutas de `/plan-compras`. Queda entero lo demás:

- **Maestro de insumos:** cómo se construye desde el presupuesto, los vínculos pendientes, y el
  contador de «reenganchados» que el propio código explica en `PlanComprasMaestroController:172`.
  13 rutas (`PlanComprasMaestroController` + `PlanComprasMaestroImportController`).
- **Paquetes y subpaquetes:** asignación manual frente a sugerencia; la regla del MATERIAL en
  paquete `a_todo_costo`; partir en subpaquetes y el `es_resto`. 21 rutas
  (`PlanComprasPaquetesController` + `PlanComprasSubpaquetesController`).
- **La SPA:** comportamiento de AG Grid leído de `pdc-app/src/`.
- **Las deudas de datos** de `docs/pdc-v2.md`, como escenarios de primera clase.

`PlanComprasPlanController` (23 rutas, plan de fechas) ya tiene su invariante más peligrosa cubierta
por `PDC-005`; el resto de sus rutas —lectura del plan, reprogramación, re-matching— sigue sin
escenario propio.
