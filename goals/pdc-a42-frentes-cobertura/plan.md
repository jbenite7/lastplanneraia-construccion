# Plan — A4.2: que el plan de compras sepa a qué frente va cada paquete

Hechos acordados: [`facts.md`](facts.md) (33, todos aceptados).
Línea base medida antes de tocar nada: **45 sin propuesta · ALTA 3 · MEDIA 37 · BAJA 0** (2026-07-28 22:38:41).
Coordinación con las otras tres sesiones: [`coordinacion-sesiones.md`](coordinacion-sesiones.md).

## El enfoque, en una frase

Los paquetes hablan de oficios (`CIELOS RASOS`, `CEMENTO`) y el cronograma habla de fases
(`ACABADOS`, `ESTRUCTURA`): no comparten palabras, por eso el motor de parecidos falla en 45 de 85.
Se añade el puente que falta —**subcapítulo del presupuesto → frente del cronograma**, 25 filas que
cubren el 100 %— y el nombre del paquete pasa de ser la única señal a ser el **desempate dentro del
grupo** que el subcapítulo ya acotó.

Decisión de diseño que sostiene lo global: la correspondencia guarda el **nombre** del frente, no su
`unique_id`. El `unique_id` es de una obra concreta; el nombre (`ESTRUCTURA`, `MAMPOSTERÍA`) se
repite entre obras. En cada proyecto se resuelve nombre → `unique_id` de esa obra. Sin esto, «global»
sería imposible.

## Revisión del 2026-07-28, posterior al gate — el puente ya existe curado

Después de aprobar el plan apareció, sin commitear en la rama ajena `pdc-b1-amarre-cronograma`,
`database/seeds/sembrado_ramas_frentes.json`: **26 reglas rama → nodo del cronograma, curadas con
criterio de obra**, más otras 8 que casan solas por nombre. Cotejado una a una: **cubren el 100 % de
los 25 subcapítulos** que necesitaba mi motor. Copiado a `scratchpad/diag-a42/` porque vive sin
commitear y podría perderse.

Esto **elimina la pregunta abierta 1**: ya no hay que sembrar correspondencias deducidas y pedirte que
las confirmes en pantalla para alcanzar f27; nacen confirmadas porque las confirmó una persona en obra.

Y **obliga a dos correcciones**, ambas verificadas por mí contra la base y no aceptadas de palabra:

1. **El destino de una correspondencia puede ser una hoja, no solo un frente** (corrige f14). El
   subcapítulo `CUBIERTA` no tiene frente propio; su ancla es la hoja `LOSA AÉREA CUBIERTA`
   (2027-07-27). El frente `ESTRUCTURA` arranca 2026-08-18: **11 meses y 9 días de adelanto**. Sigue
   en pie lo que medí — comparar nombres contra las 242 hojas produce disparates —, pero una
   correspondencia curada es otra cosa: la eligió un humano, no un Jaccard.
2. **El origen puede ser un `grupo`, no solo un `subcapitulo`** (refina f01). `REVOQUES` es el grupo
   01.05.06 y ancla en `REVOQUE TRADICIONAL`; heredar de su subcapítulo padre lo adelantaría un mes.
   `IMPERMEABILIZACION FILTROS` (grupo 01.06.02) ancla en `ESTRUCTURA`, casi un año antes que su
   hermano `IMPERMEABILIZACIONES`. Mi `subcapitulosDePaquete()` sube hasta el primer `subcapitulo` y
   se saltaría los dos casos: hay que devolver la rama al nivel que la correspondencia use.

Consecuencia en las tareas: la 1 siembra **desde este JSON** en vez de deducir; la 2 resuelve la rama
a nivel grupo **y** subcapítulo; la 4 deja de ser «solo amarre manual» y pasa a sostener también las
propuestas ancladas en hoja.

Nota sobre un consejo recibido que ya estaba cubierto: se me sugirió filtrar por
`modalidad_contratacion` para el denominador de la cobertura. El motor ya lo hace desde A4 — la
consulta de `sugerirFrentes()` filtra `IN ('contrato','orden_compra')`, y por eso mi universo son 96
paquetes y no los 209 activos. No hay cambio que hacer ahí.

## Riesgo principal, declarado por delante

**f27 (≥30 propuestas de confianza ALTA) depende de un acto humano.** Una correspondencia solo da
ALTA cuando una persona la confirmó (f05/f06). Si me limito a sembrar las 25 deducidas, todas nacen
sin confirmar y el resultado sería «45 → ~5 sin propuesta, pero ALTA sigue en 3»: cobertura sí,
confianza no.

Salida propuesta, que no inventa confirmaciones: se siembran como **ya confirmadas** solo las
correspondencias respaldadas por evidencia humana real —
1. las que se derivan de los **11 amarres que una persona ya hizo** (`ESTRUCTURA→ESTRUCTURA`,
   `MAMPOSTERIA Y REVOQUE→MAMPOSTERÍA`, `RED ELECTRICA→RED ELÉCTRICA`, `CARPINTERIA DE MADERA→
   CARPINTERIA EN MADERA`, `RED CONTRA INCENDIOS→REDES`, `CARPINTERIA METALICA→VENTANERÍA`,
   `PISOS Y ENCHAPES→MORTEROS DE PISOS`), y
2. las de **coincidencia exacta de nombre** entre subcapítulo y frente.

El resto nace sin confirmar (MEDIA) y sube a ALTA cuando alguien pase por el panel. **Esto se valida
en el gate**: si prefieres que ninguna nazca confirmada, f27 se mide después de tu pasada por el
panel y lo digo así en el cierre.

## Modelo de datos

| Objeto | Qué guarda |
|---|---|
| `general_subcapitulo_frente` (nueva, global) | `subcapitulo_norm` (único), `frente_nombre_norm`, `confirmado_humano`, `nota`, `creado_por`, `actualizado_por`, `updated_at` |
| `pdc_subcapitulo_frente` (nueva, por proyecto) | `project_id`, `subcapitulo_norm`, `frente_nombre_norm`, `confirmado_humano`, `asignado_por`, `updated_at`; único `(project_id, subcapitulo_norm)`, índice liderado por `project_id` — es la excepción de f03 y gana sobre la global |
| `pdc_paquete_frente.origen` | el enum `('similitud','rama','humano')` gana el valor `'correspondencia'` |
| `pdc_correcciones_frente` (nueva) | `project_id`, `paquete_id`, `unique_id_sugerido`, `unique_id_elegido`, `capa_sugerida`, `confianza_sugerida`, `usuario`, `created_at` — es f24; no cabe en `pdc_correcciones_motor`, que está atada a `(descripcion_norm, unidad)` de insumos |

## Tareas

### 1 · Migraciones y siembra — `database/migrations/`
DDL de las tres tablas nuevas y del enum. Backfill dry-run → `--apply` que siembra las
correspondencias: deducidas por nombre (sin confirmar) + las derivadas de los 11 amarres humanos y de
la coincidencia exacta (confirmadas).
**Aviso explícito antes del `--apply`, como pidió la sesión coordinadora.**
*Verificación:* `SHOW COLUMNS`; conteo de filas sembradas; `php tests/test_global_table_safety.php` y
`test_global_table_reconciliation.php` en verde (f02 exige que la global no lleve `project_id`).

### 2 · El puente en el motor — `PlanFechasService.php`, solo líneas 94–540
`correspondenciasEfectivas()` nueva (global ∪ excepción de proyecto, gana la excepción) y
`sugerirFrentes()` reescrito: el subcapítulo acota candidatos, el nombre desempata dentro (f08); fuera
del grupo → MEDIA con evidencia de desacuerdo (f09); varios subcapítulos → el que arranca antes
(f10); confirmada → ALTA, deducida → MEDIA (f06/f07); evidencia nombrando subcapítulo y frente (f11).
La capa de similitud pura queda como respaldo. **No se tocan `const PASOS`, `calcular()`,
`medianasPorTipo()`, `pesosDelCatalogo()` ni `PESOS_REPARTO`** (f30 — son de A4.1).
*Verificación:* `tests/test_pdc_v2_plan_fechas_correspondencias.php`, archivo **nuevo** para no chocar
con A4.1 en el existente, sobre MySQL real con proyecto sintético.

### 3 · Higiene de nombres — mismo archivo, `tokens()`
Ignorar palabras vacías (f13) y dejar el umbral en 0,33 (f12).
*Verificación:* test que corre el motor antes y después sobre Da Porto y comprueba que **las 28
propuestas por similitud existentes siguen existiendo** — es la garantía de no regresión de f13.

### 4 · Amarrar a actividades sueltas — mismo archivo + controlador
`anclasDisponibles()` nueva (encabezados **y** hojas) para el selector y para validar `amarrar()`
(f15/f16). `frentesDisponibles()` sigue devolviendo solo encabezados, que es lo que `sugerirFrentes()`
consume: así el motor nunca propone una hoja (f14). El endpoint marca cada opción con si es frente o
actividad.
*Verificación:* test que amarra a una hoja y comprueba que la fecha ancla es la de esa hoja; y que
`sugerirFrentes()` no devuelve ningún `uniqueId` de hoja.

### 5 · Endpoints y permisos — `PlanComprasPlanController.php`, `public/index.php`
`GET /plan-compras/api/plan/correspondencias` y `POST .../correspondencias` (CSRF `plan_compras_v2`).
Editar la **global** exige `lps.paquetes_contratacion.reglas` (el permiso de gobernanza que A3.3 ya
creó); la **excepción de proyecto**, `lps.paquetes_contratacion.editar`.
*Verificación:* test de RBAC que comprueba que sin `reglas` no se puede tocar la global.

### 6 · La pantalla — `PlanFechas.tsx` (solo pestaña «Sin frente») y `planFechas.ts`
Panel plegable cerrado por defecto con las correspondencias, su conteo de confirmadas/pendientes y su
edición (f17–f19). Fila sin propuesta que dice el motivo y ofrece el atajo (f20/f21). El botón
principal sigue aceptando solo ALTA (f22) y las MEDIA siguen pasando por la confirmación con importe
(f23). **No se reestructura el archivo ni se toca la pestaña «Plan»** (f30).
*Verificación:* Vitest sobre la lógica nueva de `planFechas.ts`; `npm run build`.

### 7 · Registro de correcciones — servicio + controlador
Cuando el frente elegido difiere del propuesto, se escribe el par (f24); aceptar tal cual conserva la
capa que lo produjo (f25, que ya funciona así vía `procedencia`).
*Verificación:* test que amarra a un frente distinto del sugerido y comprueba la fila escrita.

### 8 · Medición final y cierre
Se vuelve a correr **el mismo script de la línea base**, sin modificarlo, y se comparan las cifras
(f26/f27) más los 11 amarres intactos (f28). E2e Playwright de la pestaña. Bundle a
`lps-aia-pdc/public/pdc-app/`.
*Verificación:* salida de comandos pegada en el cierre; Vitest y `npm run build` en verde (f33).

## Cómo se trabaja, dado que somos cuatro sesiones

**Worktree propio** con `git worktree add` desde `origin/main` — no `checkout -b` en el compartido,
que hoy está en `pdc-revision-ux` y con un archivo ajeno sin trackear
(`20260728_pdc_v2_tipo_no_aplica.php`). La base MySQL sí es compartida: **re-mido la línea base justo
antes de implementar** por si otra sesión escribió en medio, y lo digo en el cierre.

## Preguntas abiertas

1. **La siembra confirmada** (arriba). Es la única decisión que cambia si f27 se cumple sola o
   necesita tu pasada por el panel.
2. **273 opciones en el selector de frente.** Habilitar las 242 actividades (f15) engorda el
   desplegable de 31 a 273 entradas. Van agrupadas y con los frentes primero; si en pantalla resulta
   incómodo, lo digo en el cierre en vez de cambiar el alcance por mi cuenta.
3. `mejorFrente()` tiene un desempate que solo se activa cuando ya hay un candidato previo, así que
   el primer empate no se resuelve por fecha. No lo toco salvo que estorbe a f10; queda anotado.
