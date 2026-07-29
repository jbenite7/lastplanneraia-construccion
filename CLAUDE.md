# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Estado actual

Rama en curso: **Fases A1, A1.5, A1.6, A1.7, A2, A2.5, A3, A3.1, A3.2 y A3.3 implementadas** (+ fix **A1.8**: el importador ignoraba `Cant APU` e inflaba el valor de los insumos → corregido a `Cant APU × Rend × cantidad`) — importador de presupuesto, visor en árbol; comparativo de versiones (`#/ensamble/comparar`): diff por actividad (jerárquico) e insumo (Pareto), sobrecostos vs ahorros; endpoint `GET /plan-compras/api/presupuesto/comparar`, sin migraciones; versionamiento inteligente del importador (`#/ensamble/importar`): auto-numeración (Versión N · fecha) por proyecto, anti-duplicado por hash de contenido vs la versión activa, y resumen del auto-comparativo tras cargar (reusa A1.6); maestro de insumos global (auto-match + cola de pendientes) y **importador del maestro SINCO** (siembra `general_maestro_insumos` con 3.088 insumos: código, agrupación, tipo de recurso, valor), y **paquetes de contratación** (`#/ensamble/paquetes`): catálogo global `general_paquetes_contratacion` **sembrado con los 188 paquetes reales de AIA** (extraídos del bundle de la app de Tomás; 107 a-todo-costo / 53 suministro / 28 mano-de-obra) + asignación por proyecto `pdc_insumo_paquete` (un insumo un destino — paquete u **omitido**; herencia en re-import), con motor de sugerencias cross-proyecto (exacta/tokens/agrupación SINCO + candidatos filtrados por tipo de recurso, confirmación humana), **grilla masiva y asistente paso a paso** (orden Pareto), y cobertura hacia el 100%; RBAC `lps.paquetes_contratacion.ver/editar`. Todo bajo la navegación Ensamble | Seguimiento. Verificado con Vitest, tests PHP autoejecutables y e2e Playwright. En detalle: importador de presupuesto (preview→confirmar, versionado con única activa, todo-o-nada) sobre 3 tablas `pdc_presupuesto_*` en lps-aia con RBAC `lps.pdc.importar`, visor del presupuesto en árbol jerárquico con selector de versión (`#/ensamble/presupuesto`), y maestro de insumos global (`#/ensamble/maestro`) con RBAC `lps.pdc.maestro`: cola de vínculos pendientes por versión con selección múltiple y creación masiva (cold start), vinculación individual con sugerencias por similitud, y catálogo único de insumos (`general_maestro_insumos`) con búsqueda — auto-match idempotente en cada re-import. Follow-ups del review final A2 aplicados: tolerancia a errno 1062 (carrera/colisión de prefijo → vincula al existente), upsert de vínculos en lotes multi-fila, comodines LIKE escapados, y retiro/reactivación de insumos del catálogo (`activo=0` con reversión global del auto-match, auditoría `actualizado_por`/`updated_at` y UI en el catálogo). Verificado con Vitest (28 tests), tests PHP autoejecutables (RBAC, parser, flujo BD, árbol, maestro, import SINCO) y e2e Playwright (import, fundación, visor, maestro e import del maestro SINCO).

### A3.2 — Modalidad de contratación (4 modalidades)

Dimensión **ortogonal** a `tipo_negociacion` (columna `general_paquetes_contratacion.modalidad_contratacion`,
enum, default `contrato`): el tipo dice *qué* se compra, la modalidad dice **cómo**, que es lo que decide si el
paquete genera proceso y fecha en A4.

| Modalidad | Qué es | ¿Entra al plan de fechas? |
|---|---|---|
| `contrato` | Alcance cerrado con un proveedor | Sí, proceso completo |
| `orden_compra` | Commodity recurrente (concreto, acero, cemento, agregados…): cambia de proveedor en el tiempo | Solo la **primera entrega** — garantizar el arranque de la actividad; el histórico por proveedor/cuantía es del módulo de Seguimiento |
| `consumo_directo` | Ferretería y consumibles pedidos a necesidad contra almacén | No — se controla el gasto, no se contrata |
| `no_contratable` | Nómina propia, imprevistos y provisiones | No — no se le compran a nadie |

El bucket único «Indirectos / Administración» de A3.1 se partió por naturaleza en **Nómina de obra**,
**Imprevistos y provisiones** (ambos `no_contratable`) y **Ferretería y consumibles de obra**
(`consumo_directo`), para que no contaminen cobertura ni semáforos. Migraciones
`20260725_pdc_v2_modalidad_contratacion.php` (DDL, cero regresión: los 202 paquetes existentes quedan en
`contrato`) y `20260725_pdc_v2_backfill_modalidades.php` (11 paquetes a `orden_compra`, respaldados por el
catálogo legacy `general_dias_procesos_contratacion`, que ya marcaba ACERO/CONCRETO como orden de compra con
ciclos propios). La SPA la ofrece al crear paquetes y la pinta como badge **solo cuando no es `contrato`**.
Reparto real en DAPORTO v292 (325 asignados, 82,1 % de cobertura): contrato 59,26 % · orden de compra 24,55 % ·
no contratable 12,99 % · consumo directo 0,98 %.

### A3.3 — Motor auditable y generalizable

Al medir el motor contra DAPORTO apareció que el 82,1 % de cobertura medía sobre todo el trabajo manual del
ejercicio: **el 71,4 % del valor asignado lo resolvía la lista curada a mano** (158 overrides, 41 literales de esa
obra). A3.3 convierte esa memoria en conocimiento y hace el motor auditable.

- **Trazabilidad:** `pdc_insumo_paquete` guarda `origen` (capa), `confianza`, `evidencia` y `confirmado_humano`;
  `pdc_correcciones_motor` registra el par (sugerido → elegido) cuando un humano enmienda al motor. Origen y
  confirmación son **ortogonales**: aceptar una sugerencia cuenta como acierto del motor y a la vez la vuelve
  intocable. El resumen expone **tres indicadores** — conteo, valor y tasa de acierto (null mientras no haya base).
- **Overrides destilados: 158 → 8.** Se midió corriendo el motor con los overrides apagados
  (`new PaquetesService($db, false)`): 89 eran redundantes y 69 tapaban huecos que ahora son reglas. Los 8 que
  quedan llevan `alcance` (global/proyecto) y una nota. 11 se borraron porque **el motor acierta mejor** (pilotes
  → cimentación profunda; puertas P1–P15 → PUERTAS EN MADERA/METÁLICAS, las tres categorías vigentes).
- **Desempate por tipo de recurso:** un MATERIAL se decide solo por su descripción (la actividad deja de influir);
  MO y subcontrato siguen el frente, y si la actividad dominante concentra <60 % (`DOMINANCIA_MINIMA`) la
  sugerencia baja a confianza baja. `pdc_insumo_actividades` persiste **todas** las actividades de cada insumo
  porque Seguimiento necesita la fecha de la **primera**, no la de mayor cuantía. (Su `unique_id` decía «NULL
  hasta A4»; A4 no lo llenó y B1 lo resolvió por otra vía — ver abajo.)
- **Doble conteo:** un MATERIAL ya no cae en un paquete `a_todo_costo` salvo `admite_materiales = 1` (dotación,
  planta eléctrica, tanques…). Y prohibir no es redirigir: si el destino correcto queda vetado, el insumo va a
  revisión con la explicación, en vez de caer en el primer fallback.
- **Cola larga:** de 71 insumos sin destino a 1. Se crearon 5 paquetes (Equipos y maquinaria, Tecnología y
  software, Transporte y acarreos, Provisiones y partidas globales, Paisajismo); el resto son reglas hacia
  paquetes que ya existían.
- **Auto-asignación acotada:** confianza alta y valor < `UMBRAL_AUTO_ASIGNACION` ($20M) se aplica sola, con
  preview; el resto va a revisión con el motivo. La confianza la da la evidencia, no la capa: descripción → alta,
  actividad padre → media, reparto sin dominante → baja.
- **Puente con las duraciones legacy** (`duracion_ref`): 162 de 209 paquetes activos apuntan a su fila de
  `general_dias_procesos_contratacion`; los 47 sin equivalente quedan NULL a propósito. Sin esto A4 no derivaría
  fechas, porque el legacy guarda «CONCRETO» y el paquete se llama «Suministro CONCRETO».
- **Gobernanza:** permiso `lps.paquetes_contratacion.reglas` (Oficina Técnica / Compras y Director de Obra) para
  aprobar reglas y overrides globales, distinto de asignar insumos en un proyecto.

### Dos deudas de datos saldadas (2026-07-28)

**`tipo_negociacion` de los buckets no contratables.** Los cuatro paquetes que no se le compran a nadie
(Nómina de obra, Imprevistos y provisiones, Indirectos / Administración, Provisiones y partidas globales)
arrastraban el tipo `consumibles`, heredado al partir el bucket de A3.1. Ninguno de los cuatro valores del enum
los describía, así que se agregó un quinto —**`no_aplica`**— y los cuatro pasaron ahí
(`20260728_pdc_v2_tipo_no_aplica.php`, regla por `modalidad_contratacion = 'no_contratable'`, no por lista de
nombres). Cero regresión medida: los dos únicos puntos que leen ese campo (`PaquetesService::tipoRecursoAdmitido()`
y `::resolverPaquete()`) hacen bypass antes por `MODALIDADES_SIN_PROCESO`, y el plan de fechas excluye lo no
contratable por modalidad. Con el dato honesto, el parche de UI que escondía el badge dejó de decidir por
modalidad y pasa a decidir por tipo (`muestraTipoNegociacion`): «Ferretería y consumibles de obra» recupera su
badge SUMINISTRO, que siempre fue cierto. **Pendiente deliberado:** `PaquetesService::TIPOS` todavía no lista
`no_aplica`, así que el formulario de crear paquete no lo ofrece; se dejó fuera porque ese archivo estaba en
manos de otra tarea en curso.

**La V1 del presupuesto de Da Porto es un artefacto del bug A1.8.** Confirmado con datos, no por hipótesis: de
las 323 filas cuya `cantidad_total` permite distinguir las dos fórmulas, **las 323 cuadran con la defectuosa
(`Rend × cantidad`) y ninguna con la correcta** (`Cant APU × Rend × cantidad`); el factor por fila es exactamente
`1/Cant APU` (un insumo con coeficiente 0,002 quedó ×500). Por eso el mismo archivo aparece dos veces con 403
actividades y 820 insumos pero $74.974.013.394,31 contra $29.492.804.353,65. La versión **no se borra**: se marca
(`obsoleta`, `obsoleta_motivo`, `obsoleta_marcada_at` en `pdc_presupuesto_versiones`) y el comparativo advierte
**antes del resumen**, porque su Δ de −$45 mil millones se lee como una caída del presupuesto que nunca ocurrió.
La detección de `20260728_pdc_v2_versiones_obsoletas.php` no usa ids fijos: recalcula ambas fórmulas por fila, así
que sirve para cualquier proyecto de AIA con el mismo problema. Solo cuentan las filas donde las dos fórmulas se
separan más que la tolerancia — sin ese filtro, las 442 de coeficiente 1 y las 53 de actividades con cantidad 0
hacían pasar por «ambigua» una versión que no lo es.

### B1 — El amarre al cronograma es por RAMA, no por actividad (2026-07-28)

`pdc_insumo_actividades.unique_id` llegó a B1 con **820 de 820 filas en NULL** en Da Porto. La nota que decía
«NULL hasta A4» daba por hecho un emparejamiento 1:1 que **no existe en los datos**: de las 820 filas, **UNA**
casa por nombre con una actividad del cronograma (`RED DE GAS TODO COSTO` → `RED DE GAS`), y ninguna por código
—`programa_consolidado.codigo_actividad` está vacío en las 273 filas de las 4 semanas—. No es un problema de
tildes: presupuesto y cronograma hablan idiomas distintos y a distinta granularidad. El presupuesto (401
actividades) dice lo que se mide y se paga (`ACERO ESTRUCTURA`); el cronograma (242 hojas) dice la secuencia
constructiva (`COLUMNAS PISO 5`, `LOSA AÉREA SÓTANO 2`). `ACERO ESTRUCTURA` alimenta ~30 actividades: la
relación es muchos-a-muchos.

Lo que se amarra es la **rama**: el subcapítulo (o el grupo) del presupuesto contra el frente del cronograma
donde esa rama se construye — la misma ruta que A4 ya usaba para los paquetes en `pdc_paquete_frente`
(`origen = 'rama'`), un nivel más abajo. **`unique_id` NO significa «la actividad que consume el insumo»**, sino
«el nodo que marca cuándo arranca la rama que lo consume». Como la `Fecha_Inicio` de un frente es la mínima de
sus hijos, esa fecha ES la del primer consumo, que es justo lo que Seguimiento pide para la primera entrega.

- **Resultado medido: 820 → 2 NULL.** Las 2 restantes son IMPREVISTOS ($1.272M): provisiones que ninguna
  actividad consume, y quedan NULL **con motivo escrito**, no mudas.
- **Orden de resolución** (`AmarreCronogramaService`): override de grupo → override de subcapítulo → nombre
  exacto → similitud de palabras (Jaccard ≥ 0,33, el mismo umbral de `PlanFechasService`). El capítulo queda
  fuera: solo dice «COSTO DIRECTO»/«COSTO INDIRECTO». Los overrides van **antes** que lo automático porque el
  texto engaña: `CARPINTERIA METALICA` se parece a `CARPINTERIA EN MADERA` pero su frente real es `VENTANERÍA`.
- **Mapa curado de 25 reglas** (`database/seeds/sembrado_ramas_frentes.json`), confirmado en obra. Se podó con
  la disciplina de A3.3 —correr el motor con y sin el mapa (`new AmarreCronogramaService($db, false)`)—: las 25
  cambian el destino de su rama; se borró `URBANISMO Y OBRAS EXTERIORES` porque el motor llega solo. Reparto
  final: 372 filas por override, 260 exactas, 186 por similitud.
- **Trazabilidad:** `origen_amarre`, `evidencia_amarre` y `semana_amarre`, el mismo trío de A3.3/A4. Un NULL
  mudo es indistinguible de un cálculo que nunca corrió — así se llegó a las 820.
- **Nace amarrado:** `PaquetesService::materializarActividades()` resuelve el `unique_id` al escribir, con la
  **misma rutina** que el backfill (`amarrarVersion()`). Si divergieran, una reimportación reabriría la deuda.
- Migración `20260729_pdc_v2_amarre_cronograma.php` (dry-run → `--apply`, idempotente: la segunda corrida
  escribe 0). Test `tests/test_pdc_v2_amarre_cronograma.php`.
- **Nota para B2:** el re-matching al reprogramar funciona porque `unique_id` es estable; lo que se mueve es la
  `Fecha_Inicio` del frente. Un amarre más fino exigiría que planeación llene `codigo_actividad` en el programa.

⚠️ El e2e `tests/browser/pdc-v2-paquetes.spec.mjs` es **destructivo** (importa un presupuesto de juguete en el
proyecto real): exige `PDC_E2E_DESTRUCTIVO=1`. Y el stack del worktree publica **8091**, no 8081.

El desarrollo sigue el **roadmap maestro** `docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md` (fases A1→A4, B1→B3, C1); cada fase recibe su propio spec y plan detallado antes de ejecutarse.

## Producto: 2 submódulos de UI

La app se organiza en exactamente **dos submódulos** (decisión 2026-07-22):

1. **Ensamble del Plan de Compras** — construir el plan: importar presupuesto → maestro de insumos → paquetes de contratación → matching con cronograma → plan con fechas. (Fases A1–A4.)
2. **Seguimiento al Plan de Compras** — operar el plan: avance por pasos de contratación (fechas reales vs programadas), alertas/semáforos, re-matching automático al reprogramar, responsables y gestión, y Torre de Control (BI). (Fases B1–B3.)

No existe una vista de Pareto en este desarrollo.

## Hechos del modelo de datos LPS (verificados en lps-aia — vinculantes)

- **Última semana activa** = `MAX(Semana)` de `semanas_activas` (no hay flag).
- El programa se versiona por semana: `programa` (viva) vs **`programa_consolidado`** (snapshot semanal). El matching de v2 va contra `programa_consolidado WHERE Semana = MAX` y persiste **`unique_id`** (identidad de actividad estable ante reprogramaciones).
- **Fechas:** programación hacia atrás desde `Fecha_Inicio` de la actividad ancla, con duraciones por paso del catálogo global `general_dias_procesos_contratacion` (pasos configurables por proyecto). **No usar** `general_pdc_plantillas` (dropeada).
- Tablas nuevas: operativas con `project_id int NOT NULL` + índice liderado por `project_id` + `utf8mb4_unicode_ci`; catálogos `general_*` sin `project_id`; migraciones en `lps-aia/database/migrations/` (DDL `.sql`; backfills `.php` dry-run→`--apply`).
- **Verificación de BD por fase sobre el MySQL real de Docker (nunca mocks):** migraciones aplicadas, asserts de integridad en tests PHP, y gates `test_global_table_safety` + `test_global_table_reconciliation` en verde.

## Propósito del proyecto

Herramienta de **plan de compras** (procurement de obra) para **AIA – Arquitectos e Ingenieros Asociados**.

Es una **implementación global y multiproyecto**, no específica de un proyecto. *DAPORTO – Rionegro* es solo el proyecto de ejemplo con el que se corre el ejercicio; la meta es que sirva para todos los proyectos de AIA (aeropuerto, etc.) sobre un **maestro de insumos único** de la empresa.

## Relación con `lastplanneraia-construccion` (lps-aia)

Este repo **no es autónomo**: es la reimplementación (modelo nuevo, ver abajo) del módulo de **Plan de Compras (PDC)** que se integrará a la plataforma **Last Planner System de AIA**.

- **Repo destino:** `lastplanneraia-construccion` — GitHub `jbenite7/lastplanneraia-construccion`; en local es el repo hermano `../lps-aia`.
- **Qué es lps-aia:** app web PHP/MySQL madura de Last Planner System (planificación y control de obra). **Ya tiene un módulo PDC en producción** (SiteGround), construido sobre el **modelo viejo de "familias"** (`OperationalFamilyPolicy` en `src/Support/`, vista PDC en Handsontable, tabla `general_dias_procesos_contratacion`, automatización PDCA v4.0 de jun-2026). Este repo produce el **reemplazo** de ese módulo con el modelo revisado que elimina "familias".
- **Decisión de stack (2026-07-21, ver spec en `docs/superpowers/specs/`):** este repo desarrolla el **frontend** del módulo como SPA **React + Vite + AG Grid Community**; el "glue" PHP (vista shell, endpoints JSON, migraciones) se agrega a **lps-aia**. No cambies este reparto sin confirmarlo.
- **Documentos autoritativos de lps-aia** (léelos antes de decisiones de arquitectura, dominio o UI): `AGENTS.md` (contrato del repo), `GLOSARIO.md` (terminología LPS/Lean), `docs/VISTAS-MODULOS.md` (módulos de UI, incl. PDC), `docs/pdca-automatizacion-plan-compras.md` (histórico del PDC actual y duraciones por categoría), `docs/global-tables-architecture.md` (tablas globales por `project_id`), `docs/design-system/`.

## Flujo de negocio (modelo de dominio)

Entender esta cadena es clave para cualquier funcionalidad. Anatomía de un presupuesto:
`capítulos > subcapítulos > grupos > actividades`. Cada **actividad** tiene un **APU** (Análisis de Precios Unitarios) que la descompone en **insumos** (mano de obra, materiales, equipos, transporte, subcontratos), cada uno con tipo, unidad, cantidad (**Cant APU × rendimiento × cantidad de actividad** — el coeficiente de consumo vive en `Cant APU`; en el export real de AIA `Rend` es 1. Ver fix A1.8: omitir `Cant APU` inflaba los insumos de coeficiente pequeño y el costo total) y valor.

Flujo acordado (orden importante — refleja el "cambiazo" decidido en la reunión de 2026-07-16):

1. **Presupuesto** (Excel exportado del software de presupuestos) → es el punto de partida.
2. **Maestro de insumos**: lista única y normalizada de insumos para *todos* los proyectos de AIA (concepto tipo ERP que hoy no existe).
3. **Pareto de insumos**: consolida cada insumo sumando sus fracciones a lo largo de todas las actividades que lo usan → costo total por insumo. Habilita decisiones estratégicas de compra.
4. **Paquetes de contratación**: se agrupan insumos en paquetes (ej. suministro de acero, mano de obra estructura). Principio: *no se compran actividades, se compran/negocian insumos*. **Meta central: que el 100% de los insumos del presupuesto quede asignado a algún paquete** (que "no quede nada suelto").
5. **Plan de compras final** = paquetes **+ fechas**, obtenidas por *matching* contra el **cronograma** (amarrado por código, para que reprogramar el cronograma actualice fechas automáticamente). Cada paquete lleva una **duración** del proceso de contratación y un **responsable**.

**Cambio de enfoque importante (no reintroducir el modelo viejo):** antes el flujo agrupaba actividades en "familias" y las explotaba a insumos apoyándose en el cronograma. El modelo vigente parte del presupuesto → maestro de insumos → empaqueta insumos directamente → luego hace matching con el cronograma solo para las fechas. **El concepto de "familias" queda eliminado** (en lps-aia todavía vive como `OperationalFamilyPolicy`; aquí no se replica).

Detalles del proceso de contratación (heredados del PDC actual de lps-aia, útiles para las fechas): los pasos típicos son *Elaboración → (Envío pliegos / Licify / Aprobación cliente) → Entrega → Recibo → Cuadros → Legalización → Fabricación*; los pasos intermedios son **configurables por proyecto** (no hardcodear). Las **duraciones** varían por categoría de recurso (*A todo costo, Mano de obra, Equipos, Insumos*).

Conceptos adicionales:
- **Cuatro tipos de negociación** por paquete: (a) suministro e instalación ("a todo costo"), (b) mano de obra, (c) suministro (material / órdenes de compra), (d) consumibles. Contratar "suministro+instalación" bloquea los otros para ese alcance.
- **Ecosistema adyacente de AIA** (contexto, otras vistas de lps-aia): visor de presupuestos, visor de cronogramas, definición de alcance, matriz de riesgos, e integración futura con modelos **Revit/BIM** (matching por clasificación, no exacto).

## Stack técnico (decidido 2026-07-21 — spec: `docs/superpowers/specs/2026-07-21-stack-plan-de-compras-design.md`)

Arquitectura de **"isla moderna" dentro de lps-aia**, pensada para SiteGround hosting compartido (el build corre local/CI; al servidor solo llegan estáticos + PHP):

- **Este repo (`plan-de-compras`):** SPA **React + Vite + AG Grid Community** (MIT — no usar features Enterprise ni Handsontable, cuyo tier gratis es solo no-comercial). Vistas: importar presupuesto, maestro de insumos, Pareto, paquetes, plan final. Recibe contexto por `window.__PDC_BOOTSTRAP__` (projectId, proyectoNombre, usuario, rol, csrfToken) y consume los tokens `aia-*` de lps-aia. El build (`dist/`) se despliega a `lps-aia/public/pdc-app/` (nombre distinto de la ruta `/plan-compras` para no romper el ruteo de Apache).
- **En `../lps-aia` (glue PHP):** vista shell `views/plan-compras/app.view.php` tras `SessionMiddleware`; rutas y **endpoints JSON delgados** (`/plan-compras/api/...`, envelope `{ok,data|error}`) vía FastRoute con CSRF (form key `plan_compras_v2`) + RBAC (`lps.pdc.ver`); import de Excel con `phpoffice/phpspreadsheet`; tablas nuevas aisladas por `project_id` con migraciones en `database/migrations/` (ver `docs/global-tables-architecture.md`).
- **Testing:** Vitest (lógica SPA) y `npm run build` como gate aquí; en lps-aia, scripts `tests/test_pdc_*.php` autoejecutables (no hay PHPUnit), PHPStan, y e2e Playwright en `tests/browser/`.
- **Deploy:** rutina de lps-aia (`docs/siteground-deploy-routine.md`). Watch-items SiteGround: verificar `upload_max_filesize`/`post_max_size` ≥ 10M (límite del importador) y `memory_limit` de PhpSpreadsheet con presupuestos grandes — el parser usa `toArray()` sobre la hoja completa (read-only, medido OK a escala DAPORTO: parse 0.13s / confirmar 0.42s); migrar a lectura por chunks solo si un presupuesto real lo exige.

### Worktree dedicado en lps-aia (sesiones paralelas)

El working tree principal de lps-aia (`../lps-aia`) lo comparten otras sesiones activas (indicadores/Power BI, design-system/sidebar) que lo dejan con cambios sin commitear y bloquean checkouts/merges. Por eso **el trabajo PDC en lps-aia se hace en un git worktree dedicado**, no en el principal:

- **Worktree PDC:** `/Volumes/Crucial X6/Developer/lps-aia-pdc`, rama base `pdc-dev` (desde `main`). Crear ahí las ramas de feature por fase (`git checkout -b pdc-a3-paquetes`, etc.). NO trabajar PDC en `../lps-aia` (es de las otras sesiones); tampoco tocar `../lps-aia/.claude/worktrees/lab-preview` (locked, ajeno).
- **Docker:** el `docker-compose.override.yml` (versionado) monta `./` (relativo), así que `docker compose` **desde el worktree** monta el código del worktree. Levantar el stack del worktree con `COMPOSE_PROJECT_NAME` y puertos propios para no chocar con el principal (app `8081` es fijo en `docker-compose.yml`; db/adminer son `${DOCKER_DB_PORT:-3307}`/`${DOCKER_ADMINER_PORT:-8082}`).
- **Integración:** consolidar en `origin/main` (decisión 2026-07-23; ya no la rama `desarrollo-pdc-v2`). Antes de mergear: `git fetch` y FF `main` a `origin/main` — las sesiones ajenas pushean seguido, así que main avanza en horas. Si el principal está bloqueado, mergear vía worktree temporal aislado.

Comandos de lps-aia para la parte PHP/e2e (se ejecutan en el **worktree** `/Volumes/Crucial X6/Developer/lps-aia-pdc`):

```bash
docker compose up -d --build db app adminer   # levantar stack (app: localhost:8081, adminer: 8082)
docker compose exec app composer install
docker compose exec app php tests/test_global_table_safety.php   # correr un solo test PHP
npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1
```

Comandos de este repo:

```bash
npm run dev     # Vite dev server con proxy /plan-compras/api → localhost:8081 (Docker de lps-aia)
npm run build   # tsc + vite build → dist/ con nombres fijos (assets/pdc.js, assets/pdc.css)
npm run test    # Vitest (src/lib/*.test.ts)
npm run sync    # build + copia dist/ a ../lps-aia/public/pdc-app/ (commitear allá: deploy = git pull)
                # OJO: apunta a ../lps-aia (principal). Trabajando en el worktree, copiar el bundle
                # a /Volumes/Crucial X6/Developer/lps-aia-pdc/public/pdc-app/ y commitear en la rama del worktree.
```

## Materiales de referencia (locales, no versionados)

`docs/` está en `.gitignore`; son insumos de trabajo, no artefactos del repo:

- `102 - 2026 09 DAPORTO - RIONEGRO - PI_Version_3 (4).xlsx` — **presupuesto fuente**. Hoja `Presupuesto`; columnas clave: `Código, Descripción, Padre, UM, CANTIDAD, SUBCAPITULO, ID PROYECTO, VERSION, ID APU, Cant APU, Rend, IVA, VrUnit, Tipo Insumo, Agrupacion, ...`. Jerarquía por código (`01`, `01.01`, `01.01.01.01`).
- `pareto-insumos-...-DAPORTO-RIONEGRO-...xlsx` — **Pareto de insumos** ya procesado. Hoja `Insumos`; columnas: `Insumo, Unidad, Valor Total`. Es el punto de partida para armar los paquetes.
- `Innovación y Procesos.docx` + grabación `.mp4` — transcripción/notas de la reunión donde se define el flujo.

## Convenciones

- **Idioma:** el proyecto y su dominio son en español. Documentación y comentarios en español; identificadores de código, rutas y comandos en su idioma original.
- Preserva la terminología de dominio del equipo (confírmala en `lps-aia/GLOSARIO.md`): *maestro de insumos*, *Pareto de insumos*, *paquetes de contratación*, *APU*, *plan de compras (PDC)*.
- `.omo/` (continuaciones de sesión), `.claude/`, `docs/`, `.DS_Store` y `*.mp4` están ignorados y no deben versionarse.
