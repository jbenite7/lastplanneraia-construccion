---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-04
areas: [pdc]
fuente: docs/superpowers/plans/2026-08-04-biblia-t3-pdc.md
resumen: Que la cadena presupuesto → maestro de insumos → paquetes de contratación → plan con fechas → seguimiento tenga cada uno de sus escenarios descrito, verificado…
---

# Biblia de flujos · Tanda T3 (PDC — Plan de Compras v2) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que la cadena presupuesto → maestro de insumos → paquetes de contratación → plan con fechas → seguimiento tenga cada uno de sus escenarios descrito, verificado contra el código con cita, y los críticos cubiertos por prueba ejecutable — incluidas las deudas de datos conocidas de `docs/pdc-v2.md` como escenarios de primera clase.

**Architecture:** Se crean tres documentos en `docs/flujos/`, uno por tramo de la cadena (el módulo es demasiado grande para un solo archivo sin repetir el error que el propio spec advierte: «un escenario que transcribe el código no aporta nada»). Cada escenario lleva un `id` `PDC-NNN` estable. Consumen el formato y la cláusula de autoridad de `docs/flujos/README.md` (creado por el plan de T1, `docs/superpowers/plans/2026-08-04-biblia-t1-transversal.md`); si esa tarea aún no corrió cuando se ejecute esta, Task 1 lo detecta y lo declara bloqueo, no lo reinventa. Los hallazgos no se arreglan: van a `docs/EXPERIMENTS.md`.

**Tech Stack:** Markdown versionado · PHP 8.3 en Docker para inspección (`docker compose exec app php -r ...`) · Playwright contra el sandbox del PDC (`tests/browser/support/pdc-sandbox.mjs`, proyecto 990100 «PDC Sandbox E2E») · la puerta de servicio `/dev/entrar` para abrir sesión con rol real · lectura de TypeScript en `pdc-app/src/` para el comportamiento de la SPA.

## Global Constraints

- **Cláusula de autoridad:** si la biblia y el código divergen, **es un bug de uno de los dos y hay que resolverlo**; no se corrige la biblia en silencio para que cuadre con el código.
- **Verificar, no sospechar:** toda afirmación comprobable lleva cita `archivo:línea` leída en la sesión. Lo que no se pueda comprobar leyendo se declara «no comprobable en lectura»; nunca se da por bueno.
- **Los hallazgos se registran y la pasada continúa.** Nada de arreglar en caliente. Si la duda es *cuál es la conducta correcta*, la decisión es del usuario, no del asistente.
- **La SPA se compila: se lee `pdc-app/src/`, nunca `public/pdc-app/`.** El bundle publicado es un artefacto de build (minificado, sin comentarios, con nombres de variable transformados); citar una línea de ahí no es verificable por un humano ni estable entre builds. Toda cita de comportamiento de pantalla va contra el `.tsx`/`.ts` fuente en `pdc-app/src/pages/`, `pdc-app/src/lib/` o `pdc-app/src/components/`.
- **Sesión local solo por la puerta de servicio:** `http://localhost:8081/dev/entrar?u=<cuenta>&p=<Proyecto_Proceso>`. Para los escenarios de este PDC, la cuenta y el proyecto los fija el sandbox (Task 6): **nunca** abrir sesión contra Da Porto (`project_id=73`) para nada que mute datos.
- **Viewport de validación:** 1180×820, **dark only**. No se genera evidencia de móvil, tablet ni tema `linen`.
- **Rol permitido y rol denegado:** todo escenario de capacidad cubre al menos uno de cada, leído de `RbacCatalog::fallbackPermissionsByRole()` — el PDC usa permisos con clave `lps.pdc.*` y `lps.paquetes_contratacion.*`, **no** las capacidades booleanas de `RbacManager` (esas cubren Programa General/Semanal, no el PDC).
- **Formato del `id`:** `PDC-<NNN>`, tres dígitos, estable para siempre. Un escenario retirado conserva su número; no se reutiliza.
- **Las deudas de datos conocidas son escenarios de primera clase**, no notas al pie: la versión obsoleta de presupuesto por el bug A1.8, la contradicción `unique_id`/remap de frentes, y la exigencia de que `PaquetesService::TIPOS` y `TIPOS_NEGOCIACION` de la SPA coincidan byte a byte.
- **Nunca se corre contra Da Porto ni contra la base del stack principal** para nada que escriba: sandbox 990100 vía `tests/browser/support/pdc-sandbox.mjs`, que resetea antes de cada prueba.
- **No se hace commit sin petición explícita del usuario** (`AGENTS.md` §Publicación).

---

## File Structure

| Archivo | Responsabilidad |
|---|---|
| `docs/flujos/pdc-presupuesto-maestro.md` (nuevo) | Escenarios `PDC-001`…`PDC-0NN`: importar presupuesto, versionado y comparativo, tamiz, maestro de insumos (vínculos, catálogo, import SINCO, equipos alquilado/comprado). |
| `docs/flujos/pdc-paquetes.md` (nuevo) | Escenarios `PDC-1NN`: paquetes de contratación, motor de sugerencias, modalidad de contratación, subpaquetes. |
| `docs/flujos/pdc-plan-seguimiento.md` (nuevo) | Escenarios `PDC-2NN`: amarre al cronograma por rama, pasos configurables, plan con fechas, reprogramación, seguimiento, flujo de caja, Torre de Control. |
| `tests/browser/biblia-pdc.spec.mjs` (nuevo) | Las pruebas de los escenarios críticos de T3, cada `test()` titulado con su `id`. Vive en `tests/browser/`, no en `e2e/`, porque ahí es donde vive la infraestructura del sandbox PDC (`support/pdc-sandbox.mjs`, `support/projects.mjs`) que este spec debe reutilizar. |
| `docs/EXPERIMENTS.md` (modificar) | El backlog único compartido con `improve-app`; si T1 ya lo creó, esta tanda solo añade filas. |
| `memoria/mapas/pdc.md` (modificar) | Enlaza los tres documentos de la biblia desde «Qué manda». |

---

### Task 0: Comprueba que el terreno de T1 existe antes de construir encima

**Files:**
- Read: `docs/flujos/README.md`, `docs/EXPERIMENTS.md`

**Interfaces:**
- Consumes: el resultado de T1.
- Produces: la decisión de seguir o de declarar bloqueo.

- [ ] **Step 1: Verifica el contrato de formato**

```bash
test -f docs/flujos/README.md && echo "README existe" || echo "FALTA: correr T1 primero o crearlo aquí"
test -f docs/EXPERIMENTS.md && echo "EXPERIMENTS existe" || echo "FALTA"
```

Si `docs/flujos/README.md` no existe, **no lo inventes desde cero en este plan**: es responsabilidad de T1 (`docs/superpowers/plans/2026-08-04-biblia-t1-transversal.md`, Task 1). Si T3 se ejecuta antes que T1 por decisión explícita del orquestador, copia el formato del escenario y la cláusula de autoridad tal como los describe la sección 2 y 3 de `docs/superpowers/specs/2026-08-04-biblia-de-flujos-design.md` (ya leído en esta sesión) y dilo en el resumen de cierre; no dupliques el archivo si ya existe.

Si `docs/EXPERIMENTS.md` no existe, créalo con el esqueleto exacto del plan T1 (Task 1, Step 2): las secciones `## Experiment Cards` y `## Experiment Backlog` con la tabla `| Idea | Origen | Impact | Confidence | Ease | ICE | Owner | Status |`, sin renombrar columnas.

---

### Task 1: Enumera los caminos reales de la cadena antes de describir ninguno

**Files:**
- Create: nada todavía (insumo para las tareas 2-4)
- Read: `memoria/arquitectura/plan-de-compras.md`, `src/Security/RbacCatalog.php:107-122` (definiciones de permiso), `:147-304` (`fallbackPermissionsByRole()`), `memoria/trampas/pdc-e2e-sandbox.md`, `memoria/trampas/dos-stacks-docker.md`

**Interfaces:**
- Consumes: el inventario de rutas de `memoria/arquitectura/plan-de-compras.md` (regenerado del código, ya leído en esta sesión: 64 rutas bajo `/plan-compras/api/*` + el shell `/plan-compras`).
- Produces: la lista de escenarios que las tareas 2-4 redactan, agrupada por tramo.

- [ ] **Step 1: Fija la matriz de permisos real, leyendo el `array`, no de memoria**

De `RbacCatalog.php:147-304` (ya leído en esta sesión): quién tiene `lps.pdc.ver` (A vía `*`, D, R, DCV, OT — línea 157/225/257/289), `lps.pdc.editar` (D, R, OT — 174/226/290), `lps.pdc.importar` (D, OT — 192), `lps.pdc.maestro` (D, OT — 193/298; **no R**), `lps.paquetes_contratacion.ver` (D, R, DCV, OT — 159/228/258/283), `lps.paquetes_contratacion.editar` (D, OT — 177/284), `lps.paquetes_contratacion.reglas` (D, OT — 178/285). Vuelca esta tabla completa (incluye los roles `G`/`S`/`SG`/`C`/`V` leyendo el resto del archivo, líneas 305 en adelante) en `docs/flujos/pdc-paquetes.md` como sección compartida, porque los tres documentos la citan.

Anota explícitamente el hallazgo de lectura: `lps.pdc.maestro` (administrar el maestro global) lo tiene **OT pero no R**, mientras que `lps.pdc.editar`/`lps.pdc.importar` los tiene R. Eso significa que un Residente puede importar presupuesto pero no reclasificar el maestro global de insumos — verifícalo como escenario `PDC` explícito de rol denegado, no lo des por hecho.

- [ ] **Step 2: Enumera los escenarios obligatorios por tramo, con sus caminos de error**

Presupuesto/maestro (mínimo, cada uno se detalla en Task 2): importar un presupuesto nuevo cuando no hay versión activa; reimportar sobre una versión activa (ver impacto antes de confirmar); el hash de contenido detecta un duplicado exacto; el tamiz señala el «globalazo»; vincular un insumo pendiente a mano; crear en bloque desde la cola (cold start); clasificar un equipo entre `ALQUILER`/`COMPRA`/`SIN CLASIFICAR`; importar el Excel de SINCO sin pisar una clasificación humana; **intentar `lps.pdc.maestro` con rol `R` (denegado)**.

Paquetes (Task 3): asignar un insumo a un paquete a mano; aceptar una sugerencia del motor; el motor recomienda por tipo de recurso y desempata por dominancia de actividad; un MATERIAL no cae en un paquete `a_todo_costo` salvo `admite_materiales=1`; partir un paquete en subpaquetes y que el resto quede en `es_resto`; desasignar el último subpaquete y que el paquete se despartga; cambiar `modalidad_contratacion` y que eso decida si entra al plan de fechas; **intentar `lps.paquetes_contratacion.reglas` con rol `OT` (permitido) y con rol `R` (denegado)**.

Plan/seguimiento (Task 4): amarrar un paquete a un frente por rama (override → nombre exacto → similitud Jaccard); calcular el plan de fechas hacia atrás desde `Fecha_Inicio`; reprogramar el cronograma y que el plan se re-ancle; registrar el avance de un paso y que quede acotado por `subpaquete_id`; exportar el CSV de flujo de caja; **el desplegable de frentes queda vacío tras el remap del `unique_id` (deuda de datos, Task 5)**.

- [ ] **Step 3: Descarta transcripción antes de redactar**

Para cada ítem de la lista anterior, pregunta: ¿afirma algo que el código **debe** cumplir, discrepable? Si un candidato solo repite «la función hace X», bórralo de la lista antes de Task 2.

---

### Task 2: Escenarios de presupuesto y maestro de insumos

**Files:**
- Create: `docs/flujos/pdc-presupuesto-maestro.md`
- Read: `src/Controllers/Api/PlanComprasImportController.php`, `src/Services/Pdc/PresupuestoImportService.php`, `src/Services/Pdc/PresupuestoExcelParser.php`, `src/Controllers/Api/PlanComprasMaestroController.php`, `src/Controllers/Api/PlanComprasMaestroImportController.php`, `src/Services/Pdc/MaestroInsumosService.php`, `src/Services/Pdc/MaestroSincoImportService.php`, `src/Services/Pdc/TipoRecursoEquipo.php`, `pdc-app/src/pages/ImportarPresupuesto.tsx`, `pdc-app/src/pages/VisorPresupuesto.tsx`, `pdc-app/src/pages/ComparativoPresupuesto.tsx`, `pdc-app/src/pages/MaestroInsumos.tsx`, `pdc-app/src/lib/tamiz.ts`

**Interfaces:**
- Consumes: la matriz de permisos y la lista de Task 1.
- Produces: los `id` `PDC-001`…`PDC-0NN` (numera hasta agotar la lista de Task 1, Step 2, primer párrafo; no reserves huecos).

- [ ] **Step 1: Verifica el ciclo preview → confirmar → activar**

Lee `PlanComprasImportController::preview()` y `::confirmar()` (cita las líneas exactas tras leer el archivo). El comentario de `docs/pdc-v2.md` sobre `impactoDeReimportar()` dice que `PlanComprasImportController::preview()` **enumera a mano las claves del JSON** — verifica en el archivo real si esa lista incluye el campo de impacto y de avisos del tamiz; si falta uno, es hallazgo (algo calculado en el servicio que la vista nunca puede ver).

- [ ] **Step 2: Redacta cada escenario con el formato del README**

Mismo nivel de detalle que el ejemplo de T1 (`AUTH-004`): rol, precondiciones (versión activa sí/no, hash del archivo), pasos citando el método del controlador o el componente React, resultado esperado en pantalla **y** en datos (fila en `pdc_presupuesto_versiones`, `activo=1` exclusivo por proyecto), y verificación con cita.

Ejemplo de referencia para el escenario de reimporte con impacto:

```markdown
### PDC-005 · Reimportar presupuesto sobre una versión activa muestra el impacto antes de confirmar

- **Rol:** D u OT (`lps.pdc.importar`); R también lo tiene (ver PDC-001, tabla de permisos).
- **Precondiciones:** existe una versión `activo=1` en `pdc_presupuesto_versiones` para el proyecto; se sube un Excel distinto.
- **Pasos:**
  1. `POST /plan-compras/api/presupuesto/preview` parsea el archivo y llama a `PresupuestoImportService::impactoDeReimportar()`.
  2. El impacto cruza la versión activa con la candidata por `(descripcion_norm, unidad)` (misma clave de `consolidarInsumos()` que el comparativo A1.6).
  3. `ImportarPresupuesto.tsx` debe bloquear el botón de confirmar hasta que el impacto se haya mostrado.
- **Resultado esperado:** en pantalla, un resumen de qué insumos se pierden/ganan/cambian de tipo antes de poder confirmar. En datos: ningún cambio hasta `POST .../confirmar`.
- **Verificación:** lectura — pendiente de citar línea exacta tras abrir el archivo. Ejecutable — pendiente (ver Task 6).
```

Sustituye el «pendiente de citar línea exacta» por la cita real tras leer el archivo; no dejes ese texto en la versión final del documento — es una nota de plantilla, no biblia.

- [ ] **Step 3: Verifica la trampa de los tipos que deben coincidir byte a byte**

`docs/pdc-v2.md` (leído en esta sesión) documenta que `PaquetesService::TIPOS` (PHP) y `TIPOS_NEGOCIACION` de `pdc-app/src/` (TypeScript) **deben ser exactamente los mismos cinco valores**, y que ya divergieron una vez con `no_aplica`. Abre ambos archivos:

```bash
docker compose exec -T app php -r 'require "vendor/autoload.php"; var_dump(App\Services\Pdc\PaquetesService::TIPOS);'
grep -n "TIPOS_NEGOCIACION" pdc-app/src/lib/types.ts
```

Redacta esto como escenario de primera clase (no nota al pie): compara las dos listas obtenidas y afirma si coinciden hoy. Si no coinciden, es hallazgo con severidad alta (una creación de paquete falla con `PAQUETE_INVALIDO` sin explicar por qué, según la misma fuente).

- [ ] **Step 4: Verifica la trampa del equipo alquilado/comprado**

Lee `TipoRecursoEquipo.php` y su espejo `pdc-app/src/lib/tipoRecurso.ts`. Redacta el escenario de clasificación (`ALQUILER EQUIPOS`/`EQUIPO COMPRADO`/`EQUIPO (SIN CLASIFICAR)`) citando `resolverTipoRecurso()` y su regla de que una persona solo gana contra una degradación (genérico o tránsito), nunca contra una clasificación más precisa de SINCO.

- [ ] **Step 5: Registra hallazgos y sigue**

Cada divergencia entre lo que el código hace y lo que debería (según `docs/pdc-v2.md` o el propio juicio verificado con lectura) va a `docs/EXPERIMENTS.md` `## Experiment Backlog` con el `id` del escenario en `Origen`. No toques `src/` ni `pdc-app/src/`.

---

### Task 3: Escenarios de paquetes de contratación

**Files:**
- Create: `docs/flujos/pdc-paquetes.md`
- Read: `src/Controllers/Api/PlanComprasPaquetesController.php`, `src/Services/Pdc/PaquetesService.php`, `src/Services/Pdc/SubpaquetesService.php`, `pdc-app/src/pages/PaquetesContratacion.tsx`, `pdc-app/src/pages/PaquetesAsistente.tsx`

**Interfaces:**
- Consumes: la lista de Task 1, Step 2, segundo párrafo.
- Produces: los `id` `PDC-1NN`.

- [ ] **Step 1: Verifica el desempate del motor de sugerencias**

`docs/pdc-v2.md` describe: MATERIAL se decide solo por descripción; MO/subcontrato siguen el frente; si el frente dominante concentra menos del 60 % (`DOMINANCIA_MINIMA`), la confianza baja. Localiza esa constante y esa lógica en `PaquetesService.php` con `grep -n "DOMINANCIA_MINIMA" src/Services/Pdc/PaquetesService.php` y cita la línea exacta en el escenario. Si el umbral leído no es 60 %, es un hallazgo (la wiki/el spec quedaron desactualizados respecto al código, o al revés).

- [ ] **Step 2: Verifica el doble conteo (`admite_materiales`)**

Localiza dónde `PaquetesService` decide que un MATERIAL no entra a un paquete `a_todo_costo` salvo que `admite_materiales = 1`. Redacta el escenario con un paquete que sí lo admite (dotación/planta eléctrica/tanques, según la wiki) y uno que no, como camino feliz y camino de rechazo.

- [ ] **Step 3: Verifica la invariante de subpaquetes acotada por `subpaquete_id`**

`docs/pdc-v2.md` marca esto como «el borrado más peligroso de `PlanFechasService`»: todo borrado/actualización del plan debe ir acotado por `subpaquete_id` porque los lotes de un mismo paquete comparten `paso_id`. Busca en `PlanFechasService.php` las consultas de borrado/actualización de `pdc_plan_paso` y confirma que llevan la condición. Si alguna no la lleva, es un hallazgo de severidad alta (un recálculo de un lote arrastraría los pasos de sus hermanos).

- [ ] **Step 4: Redacta un escenario por operación, con rol permitido y denegado**

Cada uno con la tabla de roles de Task 1 aplicada: p. ej. `lps.paquetes_contratacion.editar` (D, OT) frente a `lps.paquetes_contratacion.ver` (D, R, DCV, OT) — un R puede **ver** el catálogo pero no asignar/mover subpaquetes.

- [ ] **Step 5: Registra hallazgos y sigue**

---

### Task 4: Escenarios de plan con fechas y seguimiento

**Files:**
- Create: `docs/flujos/pdc-plan-seguimiento.md`
- Read: `src/Controllers/Api/PlanComprasPlanController.php`, `src/Services/Pdc/PlanFechasService.php`, `src/Services/Pdc/AmarreCronogramaService.php`, `src/Services/Pdc/PasosContratacionService.php`, `src/Services/Pdc/FlujoCajaService.php`, `src/Controllers/Api/PlanComprasSeguimientoController.php`, `src/Services/Pdc/SeguimientoService.php`, `pdc-app/src/pages/PlanFechas.tsx`, `pdc-app/src/pages/Seguimiento.tsx`, `pdc-app/src/pages/PasosContratacion.tsx`

**Interfaces:**
- Consumes: la lista de Task 1, Step 2, tercer párrafo, y la contradicción del `unique_id` que Task 5 profundiza.
- Produces: los `id` `PDC-2NN`.

- [ ] **Step 1: Verifica el orden de resolución del amarre por rama**

`docs/pdc-v2.md` (sección B1) da el orden: override de grupo → override de subcapítulo → nombre exacto → similitud Jaccard ≥ 0,33. Ábrelo en `AmarreCronogramaService.php` y cita las líneas de cada paso del orden. Si el umbral de similitud leído no es 0,33 o el orden difiere, hallazgo.

- [ ] **Step 2: Verifica la programación hacia atrás y los pasos configurables**

Lee `PlanFechasService.php` para la fórmula de fecha (`Fecha_Inicio` del ancla menos la suma de duraciones de los pasos activos del proyecto en `pdc_proyecto_pasos`, o los siete de siempre si la obra no tiene configuración — `docs/pdc-v2.md`, A4.1). Verifica el caso de cero filas de configuración citando dónde el servicio hace ese fallback.

- [ ] **Step 3: Verifica el flujo de caja y su separación `provisional`/`permanente`**

`FlujoCajaService.php`: confirma en el código que `provisional` no se suma a `permanente` en ninguna consulta ni total (`docs/pdc-v2.md` lo marca con advertencia explícita: mezclarlos «daría una curva que parece igual de firme en las dos mitades»). Redacta el escenario de exportación CSV citando el separador `;` y el BOM UTF-8, y el escenario de «obra sin fechas» (sin `semanas_activas`) con su motivo declarado en vez de un rango inventado.

- [ ] **Step 4: Redacta el escenario de reprogramación**

`POST /plan-compras/api/plan/reprogramacion/simular` y `.../aplicar`: qué re-matching automático ocurre y qué queda igual (`unique_id` estable, `Fecha_Inicio` del frente la que se mueve — nota de B2 en `docs/pdc-v2.md`).

- [ ] **Step 5: Registra hallazgos y sigue**

---

### Task 5: Las deudas de datos conocidas, como escenarios de primera clase

**Files:**
- Modify: `docs/flujos/pdc-presupuesto-maestro.md` (la versión obsoleta de Da Porto), `docs/flujos/pdc-plan-seguimiento.md` (la contradicción del `unique_id`)
- Read: `database/migrations/20260728_pdc_v2_versiones_obsoletas.php`, `database/migrations/20260712_remap_consolidado_unique_id.php`, `src/Services/Pdc/PlanFechasService.php` (el método `semanaYFrentes()`)

**Interfaces:**
- Consumes: las secciones «Dos deudas de datos saldadas» y «Los frentes y el remap del `unique_id` — contradicción abierta» de `docs/pdc-v2.md` (ya leídas en esta sesión).
- Produces: dos escenarios `PDC-*` adicionales que documentan una deuda saldada y una deuda **abierta**, distinguiendo explícitamente el estado de cada una.

- [ ] **Step 1: Escenario de la versión obsoleta por el bug A1.8 (deuda saldada)**

Añade a `docs/flujos/pdc-presupuesto-maestro.md` un escenario que describa: una versión de presupuesto con la fórmula defectuosa (`Rend × cantidad` sin `Cant APU`) debe marcarse `obsoleta` con `obsoleta_motivo` y `obsoleta_marcada_at`, **no borrarse**, y el comparativo debe advertir **antes** del resumen. Verifica en `20260728_pdc_v2_versiones_obsoletas.php` que la detección recalcula ambas fórmulas por fila (no usa ids fijos) y que filtra por tolerancia, no por conteo de coeficiente 1. Cita las líneas.

- [ ] **Step 2: Escenario de la contradicción `unique_id`/remap de frentes (deuda ABIERTA)**

Añade a `docs/flujos/pdc-plan-seguimiento.md` un escenario que documente la contradicción tal como está, **sin resolverla ni fingir que está resuelta**:

- `database/migrations/20260712_remap_consolidado_unique_id.php` deja `unique_id` en NULL para los `Titulo=1` (encabezados).
- `PlanFechasService::semanaYFrentes()` exige `unique_id IS NOT NULL` para considerar un nodo frente, y marca `esFrente` con `Titulo === 1` — verifica esto abriendo el método y citando la línea exacta.
- **Resultado esperado si el remap corrió:** el desplegable «Elegir frente…» puede listar opciones pero ninguna marcada como frente real (medido en `prueba-lps`, proyecto 27, semana 7: 0→155 opciones, 0 frentes verdaderos).
- **Precondición crítica:** la base local usada para desarrollar y validar el módulo **no está remapeada** (los encabezados sí tienen `unique_id` ahí), así que este escenario se comporta distinto en local que en el servidor con el remap aplicado — decláralo explícitamente en el campo de precondiciones, no lo omitas.
- **Verificación:** el escenario en sí ya es la verificación — la contradicción está en el código, citada arriba, no en una interpretación. Marca la columna `Origen` del backlog con severidad alta y **confianza baja para decidir la solución** (es de producto, no mecánica, según la propia nota de `docs/pdc-v2.md`): la decisión de qué lado ceder es del usuario.

- [ ] **Step 3: No marques ninguna de las dos como cerrada sin releer el estado actual del código**

Antes de escribir «saldada» para el Step 1, confirma que no hay una migración posterior que la revierta: `git log --oneline -- database/migrations/ | grep -i "obsoleta\|remap"`. Si el estado cambió desde que se escribió `docs/pdc-v2.md`, la biblia registra el estado **medido ahora**, y se anota la discrepancia con la wiki como hallazgo aparte (la wiki no es contrato, pero si dice algo falso hay que marcarla `derogada` según su propia regla — eso lo hace quien mantenga `memoria/`, no este plan; aquí solo se anota).

---

### Task 6: Las pruebas ejecutables de los críticos

**Files:**
- Create: `tests/browser/biblia-pdc.spec.mjs`
- Read: `tests/browser/support/pdc-sandbox.mjs`, `tests/browser/fixtures/projects.mjs`, `tests/browser/pdc-v2-paquetes.spec.mjs` (patrón de sandbox + selección de proyecto), `tests/browser/support/session.mjs` (para `silenciarRecorridoPdc`)

**Interfaces:**
- Consumes: los `id` de las tareas 2-5.
- Produces: pruebas cuyo título empieza por el `id` del escenario que cubren.

- [ ] **Step 1: Elige qué sube al nivel ejecutable**

Criterio del spec: toca permisos, muta datos, o cierra/abre un periodo. Para T3, como mínimo:

- `lps.pdc.maestro` con OT (permitido) y R (denegado) — el hallazgo de Task 1, Step 1.
- Importar presupuesto y ver el impacto antes de confirmar (muta datos, sandbox).
- Asignar un insumo a un paquete y verlo reflejado en el resumen de cobertura (muta datos).
- El desplegable de frentes en el sandbox — documenta si el sandbox está remapeado o no, y ajusta la aserción al estado real medido, no al esperado en abstracto.

Escribe en el documento **por qué** cada uno sube y por qué los demás no.

- [ ] **Step 2: Lee las fixtures antes de escribir**

```bash
ls tests/browser/support/; sed -n '1,40p' tests/browser/fixtures/projects.mjs
```

Reutiliza `PDC_SANDBOX_PROJECT`, `SANDBOX_LOCAL` y el `beforeEach` de reseteo de `pdc-sandbox.mjs`. **No** dupliques la lógica de guardarraíl de puerto/stack local que ese archivo ya implementa.

- [ ] **Step 3: Escribe las pruebas con el `id` en el título, silenciando el recorrido**

```javascript
import { test, expect } from '@playwright/test';
import { PDC_SANDBOX_PROJECT } from './support/pdc-sandbox.mjs';

test.use({ viewport: { width: 1180, height: 820 }, colorScheme: 'dark' });

test('PDC-999 · Residente no ve el maestro global, Oficina Técnica sí', async ({ page }) => {
  await page.goto(`/dev/entrar?u=test.R&p=${encodeURIComponent(PDC_SANDBOX_PROJECT)}`);
  await page.goto('/plan-compras#/ensamble/maestro');
  await expect(page.getByRole('link', { name: /maestro/i })).toHaveCount(0);
  // repetir con test.OT si existe una cuenta sembrada; si no, documentarlo como límite de fixtures.
});
```

Sustituye `PDC-999` por el `id` real asignado en las tareas 2-5, y ajusta selectores tras inspeccionar la pantalla real — el ejemplo es el patrón, no una promesa. **Nota de fixtures:** `AGENTS.md` solo documenta `test.A`/`test.R`/`test.V` como cuentas seedadas por defecto; si no hay cuenta `OT` sembrada, decláralo como límite y ejecuta ese lado del escenario por lectura únicamente, sin inventar una cuenta.

- [ ] **Step 4: Corre las pruebas contra el sandbox**

```bash
E2E_BASE_URL=http://localhost:8081 npx playwright test tests/browser/biblia-pdc.spec.mjs --workers=1
```

Ajusta `E2E_BASE_URL` al puerto real del stack desde el que se ejecuta (8081 en el worktree principal, 8091 en `lps-aia-pdc` — ver `memoria/trampas/dos-stacks-docker.md`). Esperado: verde. **Si una falla, no toques la prueba para que pase**: o el escenario está mal descrito (corrige la biblia) o el código incumple (hallazgo al backlog).

- [ ] **Step 5: Anota en cada escenario cubierto su prueba**

En los tres documentos de las tareas 2-5, el campo «Verificación» de los escenarios cubiertos pasa de «ejecutable — pendiente» a citar `tests/browser/biblia-pdc.spec.mjs` y el título del test.

---

### Task 7: Cierre de la tanda

**Files:**
- Modify: `docs/EXPERIMENTS.md`, `docs/flujos/README.md`, `memoria/mapas/pdc.md`, `memoria/log.md`, `docs/IMPROVE-APP-PLAN.md`

**Interfaces:**
- Consumes: los hallazgos de las tareas 1-6.
- Produces: la tanda T3 cerrada y medible.

- [ ] **Step 1: Prioriza el backlog**

Cada hallazgo con Impact, Confidence y Ease de 1 a 10 y su ICE calculado. Ordena por ICE descendente. El hallazgo de la contradicción `unique_id`/remap (Task 5, Step 2) lleva confianza baja marcada para decisión del usuario — no le calcules una prioridad de ejecución, solo de visibilidad.

- [ ] **Step 2: Teje la biblia en la wiki**

En `memoria/mapas/pdc.md`, sección «Qué manda», añade los tres enlaces (`[[docs/flujos/pdc-presupuesto-maestro]]`, `[[docs/flujos/pdc-paquetes]]`, `[[docs/flujos/pdc-plan-seguimiento]]`) explicando que la biblia describe el comportamiento esperado y el mapa describe dónde vive el código: capas distintas, no duplicadas.

- [ ] **Step 3: Actualiza los dos trackers**

En `docs/flujos/README.md`, marca T3 como cerrada con su recuento de escenarios (si el índice de tandas todavía no existe porque T1 no corrió, créalo con las cinco tandas y marca solo T3). En `docs/IMPROVE-APP-PLAN.md`, añade a `## Key Decisions` la fila del cierre de T3 y su aporte a las fases 3 y 9 (el paso a paso de Ensamble→Seguimiento es justo el insumo que esas fases piden y hoy no tienen).

- [ ] **Step 4: Corre el lint de la wiki**

```bash
npm run test:wiki
```

Esperado: `Sin hallazgos`. Los wikilinks nuevos no llevan extensión.

- [ ] **Step 5: Deja la línea de bitácora**

Una línea `ingest` en `memoria/log.md` con: cuántos escenarios describe T3, cuántos se verificaron por lectura, cuántos subieron a ejecutable, y cuántos hallazgos entraron al backlog (incluidas las dos deudas de datos de Task 5). Números medidos, no estimados.

---

## Verificación final de T3

```bash
E2E_BASE_URL=http://localhost:8081 npx playwright test tests/browser/biblia-pdc.spec.mjs --workers=1
npm run test:wiki
```

Y comprueba las condiciones de hecho del spec (`docs/superpowers/specs/2026-08-04-biblia-de-flujos-design.md` §Condición de hecho) que aplican a T3: escenarios descritos y verificados con cita (incluidas las deudas de datos), críticos con prueba citando su `id`, hallazgos en el backlog sin arreglar, wiki enlazada y en verde.

**Sobre la validación en navegador:** T3 toca superficie observable y mutación de datos reales del sandbox, así que la evidencia de Playwright contra el proyecto 990100 es la validación exigida. No se corre nada contra Da Porto ni contra otro proyecto real.

---

## Estado verificado — sigue vigente (ampliado)

Verificado contra el código el 2026-08-25 (dos pasadas). La primera encontró: manda 3 documentos
(`pdc-presupuesto-maestro`/`pdc-paquetes`/`pdc-plan-seguimiento`) y ninguno existe —solo
`docs/flujos/compras-v2.md`, consolidado (mismo criterio de alcance ya aceptado en T4/T5)— con
«falta la cadena de dominio» (`README.md:95`). Ver
[[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]] y
[[memoria/trampas/el-goal-cierra-un-alcance-menor-que-el-del-plan]].

**Sigue vigente, deliberadamente: es el frente más grande de los tres y no cabía completo en esta
sesión.** Se avanzó una parte real y verificada, no se cerró de más:

- **Presupuesto** (`PDC-006` a `PDC-010`, 7 rutas de `PlanComprasImportController`): preview sin
  persistir, confirmar idempotente por token, una sola versión activa por proyecto con el cambio
  transaccional, recargar contenido idéntico no crea versión y recargar contenido distinto no
  borra la anterior, árbol/comparar de solo lectura con 404 de dominio.
- **Seguimiento** (`PDC-011` a `PDC-015`, 4 rutas de `PlanComprasSeguimientoController`): la
  tanda que la primera pasada dejó «entera». Cuaterna completa contra cruce entre lotes del mismo
  paquete, deshacer borra también la auditoría, fecha en formato estricto, «sin responsable» como
  filtro de primera clase.

11 de las 70 rutas de `/plan-compras` quedan cubiertas con cita. **Sigue faltando, en el orden en
que el plan original lo dimensionaba:** Maestro de insumos (13 rutas), Paquetes y subpaquetes (21
rutas), la SPA (`pdc-app/src/`) y las deudas de datos de `docs/pdc-v2.md` como escenarios de
primera clase. `PlanComprasPlanController` (23 rutas, plan de fechas) ya tenía su invariante más
peligrosa cubierta por `PDC-005` desde la primera pasada; el resto de sus rutas sigue sin
escenario propio.

**Recomendación de este cierre parcial:** Maestro de insumos es el siguiente candidato natural —
es el eslabón entre lo ya cubierto (Presupuesto) y lo que falta (Paquetes), y el propio código deja
una pista ya citada (`PlanComprasMaestroController:172`, el contador de «reenganchados») que merece
verificarse antes que adivinarse.

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
