# Plan de Compras (PDC) v2 — conocimiento del módulo

Referencia del módulo PDC v2: modelo de dominio, fases A1–A4, decisiones de datos y las trampas ya
medidas. Vive aquí y no en `CLAUDE.md` porque son 290 líneas que solo necesita quien toca el PDC, y
`CLAUDE.md` se carga en todas las sesiones del repo. La SPA está en `pdc-app/`; su PHP en
`src/Services/Pdc/` y `src/Controllers/Api/PlanCompras*`.

## Estado actual

Rama en curso: **Fases A1, A1.5, A1.6, A1.7, A2, A2.5, A3, A3.1, A3.2, A3.3 y A4.1 implementadas** (+ fix **A1.8**: el importador ignoraba `Cant APU` e inflaba el valor de los insumos → corregido a `Cant APU × Rend × cantidad`) — importador de presupuesto, visor en árbol; comparativo de versiones (`#/ensamble/comparar`): diff por actividad (jerárquico) e insumo (Pareto), sobrecostos vs ahorros; endpoint `GET /plan-compras/api/presupuesto/comparar`, sin migraciones; versionamiento inteligente del importador (`#/ensamble/importar`): auto-numeración (Versión N · fecha) por proyecto, anti-duplicado por hash de contenido vs la versión activa, y resumen del auto-comparativo tras cargar (reusa A1.6); maestro de insumos global (auto-match + cola de pendientes) y **importador del maestro SINCO** (siembra `general_maestro_insumos` con 3.088 insumos: código, agrupación, tipo de recurso, valor), y **paquetes de contratación** (`#/ensamble/paquetes`): catálogo global `general_paquetes_contratacion` **sembrado con los 188 paquetes reales de AIA** (extraídos del bundle de la app de Tomás; 107 a-todo-costo / 53 suministro / 28 mano-de-obra) + asignación por proyecto `pdc_insumo_paquete` (un insumo un destino — paquete u **omitido**; herencia en re-import), con motor de sugerencias cross-proyecto (exacta/tokens/agrupación SINCO + candidatos filtrados por tipo de recurso, confirmación humana), **grilla masiva y asistente paso a paso** (orden Pareto), y cobertura hacia el 100%; RBAC `lps.paquetes_contratacion.ver/editar`. Todo bajo la navegación Ensamble | Seguimiento. Verificado con Vitest, tests PHP autoejecutables y e2e Playwright. En detalle: importador de presupuesto (preview→confirmar, versionado con única activa, todo-o-nada) sobre 3 tablas `pdc_presupuesto_*` en lps-aia con RBAC `lps.pdc.importar`, visor del presupuesto en árbol jerárquico con selector de versión (`#/ensamble/presupuesto`), y maestro de insumos global (`#/ensamble/maestro`) con RBAC `lps.pdc.maestro`: cola de vínculos pendientes por versión con selección múltiple y creación masiva (cold start), vinculación individual con sugerencias por similitud, y catálogo único de insumos (`general_maestro_insumos`) con búsqueda — auto-match idempotente en cada re-import. Follow-ups del review final A2 aplicados: tolerancia a errno 1062 (carrera/colisión de prefijo → vincula al existente), upsert de vínculos en lotes multi-fila, comodines LIKE escapados, y retiro/reactivación de insumos del catálogo (`activo=0` con reversión global del auto-match, auditoría `actualizado_por`/`updated_at` y UI en el catálogo). Verificado con Vitest (28 tests), tests PHP autoejecutables (RBAC, parser, flujo BD, árbol, maestro, import SINCO) y e2e Playwright (import, fundación, visor, maestro e import del maestro SINCO).

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
badge SUMINISTRO, que siempre fue cierto.

**`PaquetesService::TIPOS` y `TIPOS_NEGOCIACION` (`types.ts`) tienen que coincidir exactamente** — hoy, los cinco.
`crearPaquete()` valida contra la constante PHP, así que un valor que sobre en la SPA no rompe nada visible hasta
que alguien intenta crear un paquete, y entonces falla con `PAQUETE_INVALIDO` sin explicar por qué. Ya pasó al
agregar `no_aplica`: el enum y la SPA lo tuvieron antes que el PHP (ese archivo estaba en manos de otra tarea),
y hubo que partir la lista de la SPA en dos —`TIPOS_NEGOCIACION_CREABLES`— hasta que el backend se puso al día.
Ahora vuelve a ser una sola, y un test en `paquetesState.test.ts` fija los cinco valores exactos para que la
divergencia no se repita en silencio. Ninguna rama de `tiposCompatibles()` nombra `no_aplica` a propósito: a un
bucket sin proceso no se llega compitiendo por insumos sino por su modalidad.

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

### Equipo alquilado vs comprado (2026-07-29, Ola 2)

`tipo_recurso` **no es un enum**: es `varchar(60)` que siembra el importador SINCO desde la columna
«TIPO DESCRIPCION». Por eso partir «Equipo» no llevó DDL de enum, sino datos + reglas de lectura.

- **Los tres valores** viven en un solo sitio, `App\Services\Pdc\TipoRecursoEquipo`, con espejo en
  `pdc-app/src/lib/tipoRecurso.ts` y un test a cada lado que fija los strings — la misma disciplina
  que fijó los cinco de `TIPOS_NEGOCIACION`.
- **`ALQUILER EQUIPOS` no es un nombre nuevo: SINCO ya lo emitía** (2 filas en el maestro). Adoptarlo
  en vez de inventar «EQUIPO ALQUILADO» es lo que evita que cada carga de SINCO reabra la deuda con un
  sinónimo. Para «comprado» no había nada que adoptar: los de compra llegan como `EQUIPO` con
  `agrupacion` en `COMPRA ELEMENTOS-…`, así que `EQUIPO COMPRADO` sí es valor nuevo.
- **Los 167 preexistentes quedaron en `EQUIPO (SIN CLASIFICAR)`** por decisión explícita del usuario,
  contra la opción barata de mandarlos a «comprado»: nadie afirma lo que no sabe. El tapón se asume —
  «sin clasificar» hereda el cuadro de compatibilidad del viejo `EQUIPO`, así que el módulo se usa
  igual con el tapón puesto. Migración `20260729_pdc_v2_equipo_sin_clasificar.php`, reglada por
  `tipo_recurso` (no por lista de nombres, la lección de A3.2), con `--revertir` probado: revertir
  devuelve el censo exacto de la línea base y **conserva** lo que un humano ya clasificó.
- **La trampa de A3.2, en sitio nuevo.** `PaquetesService::tiposCompatibles()` es un `match` cuyo
  `default` es *no filtrar*. Renombrar los equipos sin nombrar los valores nuevos ahí los habría vuelto
  candidatos de cualquier paquete, mano de obra incluida, y **ningún test lo atrapaba** — se midió: 4
  aserciones nuevas fallaban antes del arreglo. `ALQUILADO` sale además de `suministro` a propósito:
  alquilar no es comprar. El `GENERICO` sigue nombrado porque SINCO lo emite en cada carga.
- **El punto delicado era el importador SINCO, no el de presupuestos.** El de presupuestos nunca
  escribe `tipo_recurso` (un test lo fija sobre sus INSERT reales). El de SINCO sí, a ciegas por
  `codigo_sinco`, y los 167 equipos **todos** tienen código: la siguiente carga habría borrado el
  trabajo humano. `resolverTipoRecurso()` lo acota — la persona gana sólo contra una *degradación*
  (genérico o tránsito sobre una clasificación con autor); si SINCO se pone más preciso, gana SINCO.
  Lo hace verificable el par `clasificado_por` / `clasificado_at`: sin él, «lo dijo una persona» y «lo
  trajo el Excel» son indistinguibles, el mismo problema del NULL mudo de B1.
- **No hay que re-enganchar la cola de vínculos.** `reengancharPendientes()` empareja por
  `descripcion_norm` + `unidad`, y `pdc_insumo_vinculos` no tiene `tipo_recurso`: reetiquetar equipos
  no puede alterar un vínculo. Verificado en código y en comportamiento.
- **La pista sugiere, no escribe.** 145 de los 167 traen la respuesta en `agrupacion` (89 `ALQUILER…`,
  53 `COMPRA…`, 3 `COMPRAS…`). La cola la muestra como evidencia en una columna «SINCO dice» y
  preordena por ella, y un botón *selecciona* el lote — pero la escritura siempre la dispara una
  persona. Adivinar por la **descripción** del insumo sigue descartado.
- **Sin pantalla nueva:** una sección más en las pestañas que el maestro ya tenía, que desaparece
  cuando la cola llega a 0. RBAC `lps.pdc.maestro` (A, D) — el maestro es global, y clasificar no es
  una capacidad de obra. Los roles con ella son **A, D y OT**: OT (Oficina Técnica / **Compras**)
  entró el 2026-07-30 por decisión de Felipe, porque decidir si un equipo se alquila o se compra es una
  decisión de compra. La capacidad es **única y abre todo el maestro** —clasificar, crear a mano,
  vincular, retirar/reactivar e importar el Excel de SINCO—; se asumió ese alcance en vez de inventar
  una capacidad nueva porque OT ya tenía `lps.paquetes_contratacion.reglas`, que redirige insumos en
  todos los proyectos: alcance comparable, no mayor. `test_pdc_v2_rbac_maestro.php` afirmaba justo lo
  contrario y se actualizó con el motivo escrito, no en silencio.
- **Dos huecos que sólo vio el navegador.** La pantalla del maestro hace *early return* cuando la obra
  no tiene presupuesto importado, y eso escondía la cola —que es del catálogo global y no depende del
  presupuesto de ninguna obra— y también el acuse de «N equipos clasificados». Corregido en las dos
  ramas del render. Ningún test de PHP podía verlo; lo cazó `tests/browser/pdc-v2-equipos.spec.mjs`.

**Hecho aparte, no implementado — gastos generales.** El comité pidió revisar si el presupuesto trae
categorías de gastos generales que el maestro no distinga. **No las trae:** los capítulos de nivel 1
son exactamente `COSTO DIRECTO` y `COSTO INDIRECTO`. Lo que Tomás llama «categorías de gastos
generales» llega por el **`agrupacion` de SINCO**, que el maestro **ya guarda** (`GASTOS MEDICOS Y
DROGAS PERSONAL OBRA`, `COMPRA ELEMENTOS- EQUIPO DE OFICINA`, …). No hay dato perdido: hay dato **no
explotado** — ninguna pantalla agrupa ni filtra por `agrupacion`. Es un entregable distinto (una vista
del maestro por agrupación) y necesita grilleo con Tomás para no duplicar lo que él ya tiene.

⚠️ **El volumen de MySQL del compose es `external` con nombre fijo `htdocs_db_data`.** Un
`COMPOSE_PROJECT_NAME` propio **no** da base propia: da un segundo MySQL apuntando a los archivos de la
base de desarrollo principal. Si el principal está vivo, el nuevo muere con «Unable to lock ./ibdata1»
(lo que pasó aquí, sin daño); si está apagado, **el nuevo abre la base ajena y le escribe**. Con una
migración destructiva de por medio eso corrompe el trabajo de otra sesión. Las sesiones paralelas se
lo saltan con un override local que declara un volumen propio (`!reset` sobre `volumes` del servicio).

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

### Ola 3 — Subpaquetes de obra y flujo de caja (2026-07-29)

**Subpaquetes.** Un paquete de preconstrucción puede partirse en los lotes que la obra de verdad
contrata («Pisos» → porcelanato, tableta gres, cerámica), cada uno con su modalidad, su frente, su
responsable y su proceso. El sombrilla se conserva y **resume**; el que se contrata es el lote.

- Tabla `pdc_subpaquete` (por proyecto, FK al catálogo global, **nunca escribe en él**) y columna
  `subpaquete_id BIGINT NOT NULL DEFAULT 0` en `pdc_insumo_paquete`, `pdc_paquete_frente`,
  `pdc_plan_paquete` y `pdc_plan_paso`, con las claves únicas extendidas. Migración
  `20260729_pdc_v2_subpaquetes.sql` (convergente, sin backfill).
- ⚠️ **`0` significa «sin partir» y NO es nulable.** En un índice UNIQUE de MySQL dos `NULL` se
  consideran distintos: con una columna nulable, el `ON DUPLICATE KEY` de `calcular()` deja de
  dispararse y cada recálculo inserta cabeceras nuevas en vez de actualizar las suyas. Es el mismo
  fallo que A4.1 pagó con `paso_id`. Precio aceptado: la columna no lleva FK, porque el `0` no existe
  en `pdc_subpaquete` y una fila fantasma con id 0 sería el «lote de compatibilidad» que el alcance
  prohíbe.
- **`SubpaquetesService::destinos()` es la ÚNICA definición de «unidad contratable»** del módulo, y la
  respuesta trae la etiqueta («procesos de contratación») para que ninguna vista la reinvente. Resuelve
  la ambigüedad de «11 de 96 paquetes o 11 de 130 lotes». Un lote vacío no aparece ahí —no tiene valor
  que repartir— pero sí en `listar()`.
- **Un paquete partido nunca se contrata a sí mismo:** lo que nadie mueve cae en el lote `es_resto`,
  que nace en la misma transacción que la partición. Y al borrar el último lote de verdad, el paquete
  **se desparte** solo y todo vuelve a `subpaquete_id = 0`.
- ⚠️ **Todo borrado y actualización del plan va acotado por `subpaquete_id`.** Los lotes de un mismo
  paquete comparten `paso_id`, así que sin esa condición recalcular un lote se lleva los pasos de sus
  hermanos, y registrar un avance los marca a todos. Es el borrado más peligroso de
  `PlanFechasService`.
- La herencia del frente **se materializa al partir** (el amarre del paquete pasa al «Resto») en vez de
  resolverse en cada consulta: así ninguna unión necesita un caso especial. Y las uniones a
  `pdc_paquete_frente` / `pdc_plan_paquete` van por **destino** (paquete + lote): unir solo por paquete
  multiplica cada fila por el número de lotes.
- El motor de sugerencias sigue a nivel de paquete grande y **no aprende de lotes**;
  `destinoDeAsignacion()` hace que sus asignaciones aterricen en el «Resto» si el paquete está partido.
- RBAC: `lps.paquetes_contratacion.editar` (el de la obra), no `...reglas`.

**Flujo de caja.** `FlujoCajaService`: curva mensual **derivada y nunca almacenada** que cuenta el
presupuesto ENTERO en tres orígenes (decisión del dueño del producto el 2026-07-30 — «debería contar
todo, lo que no se contrata distribuirlo en toda la duración de la obra»):
`contratado` (lineal sobre las fechas de su frente) · `permanente` (nómina, imprevistos, provisiones,
ferretería: lineal sobre toda la duración de la obra) · `provisional` (se contratará pero no tiene
frente: lineal sobre toda la obra y **contado aparte**, porque esa parte se moverá). Sin condiciones de
pago: eso es una fase propia. Endpoints `GET /plan-compras/api/seguimiento/flujo-caja[.csv]`, pantalla
en la pestaña «Flujo de caja» de Seguimiento.

- ⚠️ **`provisional` NO se mezcla con `permanente`.** El primero es un relleno que se reacomodará; el
  segundo es un gasto continuo de verdad. Juntarlos daría una curva que parece igual de firme en las
  dos mitades. La pantalla y el CSV le dan columna propia, y hay una cifra de «% con fecha propia».
- La **duración de obra** sale de `programa_consolidado` (`MIN(Fecha_Inicio)`→`MAX(Fecha_Fin)` de la
  última semana), con `fechaInicioLineaBase`/`fechaFinLineaBase` como respaldo. Sin ninguna de las dos,
  lo que no tiene frente propio queda declarado fuera con su motivo en vez de repartido sobre un rango
  inventado.
- ⚠️ En los tests, **no borrar `semanas_activas` para simular «obra sin fechas»**: `programa_consolidado`
  cuelga de ella por FK en cascada y se lleva el cronograma entero. Usar un proyecto aparte.

- El **fin** del frente no está en `pdc_paquete_frente` (solo guarda el ancla): se lee de
  `programa_consolidado.Fecha_Fin` por `unique_id` en la última semana consolidada. El **inicio**
  también se lee del cronograma en vivo y no del `fecha_ancla` guardado, que es una copia congelada.
- El residuo del reparto va al último mes, para que la suma de los meses sea exactamente el valor.
- Exportación CSV con `;` y BOM UTF-8 (el Excel en español lee la coma como decimal), con la
  advertencia del método **dentro del archivo**: viaja a un comité sin la pantalla al lado.

⚠️ **La pantalla del flujo de caja SÍ está construida y verificada; la de subpaquetes NO.** Falta
partir un paquete y repartirle insumos desde la interfaz. Tests:
`tests/test_pdc_v2_subpaquetes.php` (30 asserts) y `tests/test_pdc_v2_flujo_caja.php` (31). La cero
regresión se defiende con `tests/foto_plan_fechas.php` contra
`goals/pdc-preparar-b1/evidence/linea-base-plan-antes-subpaquetes.txt`.

### A4.1 — Pasos del proceso de contratación configurables por proyecto

Los siete pasos dejaron de estar escritos en el código. Catálogo global `general_pasos_contratacion`
(9 pasos: los siete de siempre + `licify` + `aprobacion_cliente`) y configuración por obra
`pdc_proyecto_pasos` (orden, alias, días fijos). **Cero filas para un proyecto = los siete de siempre**:
DAPORTO no recibió configuración y sus 11 paquetes dan las mismas fechas — verificado comparando las 11
cabeceras y las 77 filas de paso contra una foto con marca de tiempo
(`goals/pdc-a41-pasos-configurables/linea-base.txt`) y, dentro de cada corrida del test, antes/después de
recalcular. `PlanFechasService::PASOS` se conserva como **respaldo en código**: es lo que garantiza esa
invariancia aunque el catálogo estuviera vacío.

- **Identidad, no posición:** `pdc_plan_paso` gana `paso_id` y su clave única pasa de
  `(project_id, paquete_id, orden)` a `(project_id, paquete_id, paso_id)`. Sin esto, meter un paso en
  medio haría que el upsert escribiera encima de la fila del vecino y el avance real que B1 cuelgue ahí
  se leería como si fuera de otro paso. El borrado de sobrantes es por identidad y lleva
  `paso_id IS NULL OR ...` a propósito: `NULL NOT IN (...)` vale NULL y dejaría vivas para siempre las
  filas sin identidad (es además lo que sanó las 77 filas que dejó el `calcular()` viejo contra el
  esquema nuevo). `exigirIdentidad()` **para el cálculo en seco** si algún paso no está en el catálogo:
  sin ids, el upsert duplicaría filas *y* el DELETE de sobrantes se quedaría sin condición borrando lo
  recién insertado — cabecera de N días con cero pasos, en silencio. Cubierto por un test que recalcula
  dos veces y exige 7 filas, no 14.
- **De dónde salen los días:** un paso con `col_legacy` los saca del catálogo legacy **por paquete**; uno
  sin ella lleva **días fijos por obra** (no se le agregan columnas a la tabla legacy compartida, y las
  de Licify se dropearon a propósito en jun-2026). `col_legacy` se filtra contra una lista blanca
  derivada de `PASOS` antes de interpolarse en el SQL.
- **Con desglose real el proceso se alarga** (cada número legacy es una medición de su paso); en los
  **provisionales** la mediana es el sobre completo: los días fijos se respetan y el resto se reparte
  entre los pasos con peso, re-normalizados. `total = max(mediana, Σ días fijos)`, nunca días negativos.
- `medianasPorTipo()` y `pesosDelCatalogo()` siguen midiéndose sobre las siete columnas legacy: son
  estadísticas de la **empresa**, no de una obra.
- `orden_default` va **de diez en diez** (elaboración 0 · Licify 5 · entrega 10 · … · aprobación 35 ·
  legalización 40 · … · insumos 60): la pantalla lo usa para insertar un paso donde le toca en el proceso
  canónico. Con numeración compacta, «Aprobación del cliente» aterrizaba al final y había que subirla a
  mano cuatro veces. Las dos posiciones salen del histórico real: Licify era el paso 2 de la «Variante A»
  y la aprobación del cliente iba entre cuadros y legalización en la «Variante B» (2 de 6 proyectos).
- RBAC `lps.paquetes_contratacion.reglas` (el de A3.3) para cambiar los pasos — mueve las fechas de toda
  la obra, así que no basta con poder asignar insumos. Pantalla en `#/ensamble/plan/pasos`, **fuera de la
  barra de pestañas**, accesible desde «Configurar pasos» en el Plan de compras.
- Migración `20260728_pdc_v2_pasos_configurables.php` (dry-run → `--apply`, convergente e idempotente).
  Tests `tests/test_pdc_v2_pasos_configurables.php` y `tests/test_pdc_v2_rbac_pasos.php`; e2e
  `tests/browser/pdc-v2-pasos.spec.mjs` (contra el **sandbox**, no destructivo).

⚠️ El e2e `tests/browser/pdc-v2-paquetes.spec.mjs` es **destructivo** (importa un presupuesto de juguete en el
proyecto real): exige `PDC_E2E_DESTRUCTIVO=1`. Y el stack del worktree publica **8091**, no 8081.

⚠️ Los nombres de **FOREIGN KEY son únicos en todo el esquema**, no por tabla: `fk_pps_paso` en
`pdc_proyecto_pasos` hizo fallar con un 1826 al `fk_pps_paso` que iba a llevar `pdc_plan_paso`. Al
comprobar si una constraint existe, mirar `information_schema.TABLE_CONSTRAINTS` (global), no
`STATISTICS` de una tabla.

### Impacto al recargar + tamiz del presupuesto (Ola 1 del comité, 2026-07-29)

Dos entregables de **lectura pura: sin migraciones, sin tablas y sin endpoint nuevo**. Bitácora con la
medición completa: `goals/pdc-preparar-b1/evidence/impacto-y-tamiz-validacion.md`.

- **El impacto viaja dentro de `preview`**, no en una ruta aparte: la pantalla no debe poder ofrecer el
  botón de confirmar sin haber podido decir qué se pierde. `PresupuestoImportService::impactoDeReimportar()`
  cruza la versión activa con la candidata recién parseada usando `consolidarInsumos()` —la misma clave
  `(descripcion_norm, unidad)` del comparativo de A1.6, que es además la clave única de
  `pdc_insumo_paquete`— así que es un join más, no un motor nuevo. **Ojo:** `PlanComprasImportController::preview()`
  enumera a mano las claves del JSON; añadir algo al servicio y no a esa lista lo hace desaparecer sin
  que ningún test PHP lo note.
- **Los avisos del tamiz viajan dentro de `arbol()`** (`avisosDelPresupuesto()`), para que un presupuesto
  no pueda mostrarse sin ellos. **Ninguno bloquea nada.**
- **El umbral del «globalazo» no lo aplica el servidor:** devuelve los candidatos con su valor y el
  costo total de la versión, y la vista filtra con lo que el usuario pone en el visor
  (`pdc-app/src/lib/tamiz.ts`, persistido en `localStorage` por proyecto; arranca en el 0,25 % del costo
  redondeado al millón). Un umbral cocinado en el código sería un juicio disfrazado de constante.
- **`cambianTipo` compara `tipo_insumo`, no la agrupación de SINCO:** esa columna del Excel se lee y se
  descarta, y la `agrupacion` real vive en el maestro indexada por `(descripcion_norm, unidad)`, o sea
  que es propiedad de la identidad del insumo y no cambia entre versiones. Diferenciarla exigiría una
  migración.
- **Toda cifra de insumos declara su magnitud**, con las dos palabras en un solo sitio
  (`pdc-app/src/lib/texto.ts`: `contarInsumos`): **«apariciones en APU»** (820 en Da Porto) e
  **«insumos distintos»** (396). No usar «insumos» a secas para un conteo.
- **Trampa de los fixtures:** `fromArray()` de PhpSpreadsheet omite las celdas cuyo valor es `== null`,
  y en PHP `0 == null` es verdadero → los ceros escritos como `int` no llegan al .xlsx y el parser
  rechaza la fila. Escribirlos como cadena `'0'`.

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
- Tablas nuevas: operativas con `project_id int NOT NULL` + índice liderado por `project_id` + `utf8mb4_unicode_ci`; catálogos `general_*` sin `project_id`; migraciones en `database/migrations/` (DDL `.sql`; backfills `.php` dry-run→`--apply`).
- **Verificación de BD por fase sobre el MySQL real de Docker (nunca mocks):** migraciones aplicadas, asserts de integridad en tests PHP, y gates `test_global_table_safety` + `test_global_table_reconciliation` en verde.

## Propósito del proyecto

Herramienta de **plan de compras** (procurement de obra) para **AIA – Arquitectos e Ingenieros Asociados**.

Es una **implementación global y multiproyecto**, no específica de un proyecto. *DAPORTO – Rionegro* es solo el proyecto de ejemplo con el que se corre el ejercicio; la meta es que sirva para todos los proyectos de AIA (aeropuerto, etc.) sobre un **maestro de insumos único** de la empresa.

## Relación con `lastplanneraia-construccion` (lps-aia)

El PDC v2 es la reimplementación (modelo nuevo, ver abajo) del módulo de Plan de Compras que reemplaza al de familias en esta misma plataforma.

- **Qué es lps-aia:** app web PHP/MySQL madura de Last Planner System (planificación y control de obra). **Ya tiene un módulo PDC en producción** (SiteGround), construido sobre el **modelo viejo de "familias"** (`OperationalFamilyPolicy` en `src/Support/`, vista PDC en Handsontable, tabla `general_dias_procesos_contratacion`, automatización PDCA v4.0 de jun-2026). El PDC v2 es el **reemplazo** de ese módulo con el modelo revisado que elimina "familias".
- **Decisión de stack (2026-07-21, ver spec en `docs/superpowers/specs/`):** el frontend es una SPA React + Vite + AG Grid Community en `pdc-app/`; el glue PHP (vista shell, endpoints JSON, migraciones) vive en `src/` y `database/`. **Unificados en un repo el 2026-07-29** (ver `docs/superpowers/specs/2026-07-29-unificacion-repos-design.md`).
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

- **La SPA (`pdc-app/`):** SPA **React + Vite + AG Grid Community** (MIT — no usar features Enterprise ni Handsontable, cuyo tier gratis es solo no-comercial). Vistas: importar presupuesto, maestro de insumos, Pareto, paquetes, plan final. Recibe contexto por `window.__PDC_BOOTSTRAP__` (projectId, proyectoNombre, usuario, rol, csrfToken) y consume los tokens `aia-*` de lps-aia. El build escribe directo a `public/pdc-app/` (nombre distinto de la ruta `/plan-compras` para no romper el ruteo de Apache).
- **El glue PHP:** vista shell `views/plan-compras/app.view.php` tras `SessionMiddleware`; rutas y **endpoints JSON delgados** (`/plan-compras/api/...`, envelope `{ok,data|error}`) vía FastRoute con CSRF (form key `plan_compras_v2`) + RBAC (`lps.pdc.ver`); import de Excel con `phpoffice/phpspreadsheet`; tablas nuevas aisladas por `project_id` con migraciones en `database/migrations/` (ver `docs/global-tables-architecture.md`).
- **Testing:** Vitest (lógica SPA) y `npm run build` como gate aquí; en lps-aia, scripts `tests/test_pdc_*.php` autoejecutables (no hay PHPUnit), PHPStan, y e2e Playwright en `tests/browser/`.
- **Deploy:** rutina de lps-aia (`docs/siteground-deploy-routine.md`). Watch-items SiteGround: verificar `upload_max_filesize`/`post_max_size` ≥ 10M (límite del importador) y `memory_limit` de PhpSpreadsheet con presupuestos grandes — el parser usa `toArray()` sobre la hoja completa (read-only, medido OK a escala DAPORTO: parse 0.13s / confirmar 0.42s); migrar a lectura por chunks solo si un presupuesto real lo exige.

### Worktree dedicado en lps-aia (sesiones paralelas)

El working tree principal de lps-aia (`/Volumes/Crucial X6/Developer/lps-aia`) lo comparten otras sesiones activas (indicadores/Power BI, design-system/sidebar) que lo dejan con cambios sin commitear y bloquean checkouts/merges. Por eso **el trabajo PDC en lps-aia se hace en un git worktree dedicado**, no en el principal:

- **Worktree PDC:** `/Volumes/Crucial X6/Developer/lps-aia-pdc`, rama base `pdc-dev` (desde `main`). Crear ahí las ramas de feature por fase (`git checkout -b pdc-a3-paquetes`, etc.). NO trabajar PDC en `/Volumes/Crucial X6/Developer/lps-aia` (es de las otras sesiones); tampoco tocar `../lps-aia/.claude/worktrees/lab-preview` (locked, ajeno).
- **Docker:** el `docker-compose.override.yml` (versionado) monta `./` (relativo), así que `docker compose` **desde el worktree** monta el código del worktree. Levantar el stack del worktree con `COMPOSE_PROJECT_NAME` y puertos propios para no chocar con el principal (app `8081` es fijo en `docker-compose.yml`; db/adminer son `${DOCKER_DB_PORT:-3307}`/`${DOCKER_ADMINER_PORT:-8082}`).
- **Integración:** consolidar en `origin/main` (decisión 2026-07-23; ya no la rama `desarrollo-pdc-v2`). Antes de mergear: `git fetch` y FF `main` a `origin/main` — las sesiones ajenas pushean seguido, así que main avanza en horas. Si el principal está bloqueado, mergear vía worktree temporal aislado.

Comandos de lps-aia para la parte PHP/e2e (se ejecutan en el **worktree** `/Volumes/Crucial X6/Developer/lps-aia-pdc`):

```bash
docker compose up -d --build db app adminer   # levantar stack (app: localhost:8081, adminer: 8082)
docker compose exec app composer install
docker compose exec app php tests/test_global_table_safety.php   # correr un solo test PHP
npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1
```

Comandos de la SPA (desde `pdc-app/`):

```bash
npm run dev     # Vite dev server con proxy /plan-compras/api → Docker (PDC_API_PORT, por defecto 8091)
npm run build   # tsc + vite build → ../public/pdc-app/assets/{pdc.js,pdc.css}, listo para commitear
npm run test    # Vitest
```

## Materiales de referencia (locales, no versionados) — van en `docs/pdc/`

**Ojo, cambió con la unificación de repos del 2026-07-29.** En `plan-de-compras` la carpeta `docs/`
entera estaba ignorada y estos archivos se dejaban ahí sin riesgo. **Aquí `docs/` SÍ se versiona** —
este mismo archivo lo está—, y lo ignorado es `docs/pdc/` (`.gitignore:121`). Deja los materiales
ahí: el presupuesto fuente lleva datos del cliente y la grabación pesa cientos de megas, y en `docs/`
a secas se commitean sin que nadie lo note.

Son insumos de trabajo, no artefactos del repo:

- `102 - 2026 09 DAPORTO - RIONEGRO - PI_Version_3 (4).xlsx` — **presupuesto fuente**. Hoja `Presupuesto`; columnas clave: `Código, Descripción, Padre, UM, CANTIDAD, SUBCAPITULO, ID PROYECTO, VERSION, ID APU, Cant APU, Rend, IVA, VrUnit, Tipo Insumo, Agrupacion, ...`. Jerarquía por código (`01`, `01.01`, `01.01.01.01`).
- `pareto-insumos-...-DAPORTO-RIONEGRO-...xlsx` — **Pareto de insumos** ya procesado. Hoja `Insumos`; columnas: `Insumo, Unidad, Valor Total`. Es el punto de partida para armar los paquetes.
- `Innovación y Procesos.docx` + grabación `.mp4` — transcripción/notas de la reunión donde se define el flujo.

## Convenciones

- **Idioma:** el proyecto y su dominio son en español. Documentación y comentarios en español; identificadores de código, rutas y comandos en su idioma original.
- Preserva la terminología de dominio del equipo (confírmala en `GLOSARIO.md`, en la raíz): *maestro de insumos*, *Pareto de insumos*, *paquetes de contratación*, *APU*, *plan de compras (PDC)*.
- `.omo/` (continuaciones de sesión), `.claude/`, `docs/pdc/` y `.DS_Store` están ignorados y no deben versionarse. `docs/` **no** lo está: ver el aviso de «Materiales de referencia» más arriba.
