---
capa: fuente
tipo: spec
estado: vigente
id: S19
fecha: 2026-08-31
superficie: bi-curva-s
rutas:
  - "/bi/curva-s"
  - "/api/bi/report/curva-s"
depende_de: [T01, T03, S05, S06, S17, S18]
views: [VIEW-04, VIEW-05, VIEW-06, VIEW-08]
areas: [bi, design-system]
fuente: "auditoria de public/index.php, BiViewController, BiControlTowerApiController, ControlTowerService, ForecastService, StorytellingService, ActionRecommendationService, MetricDictionaryService, LineageService, bi_curva_s_duracion, VIEW-04/05/06/08, bi-spa.js, CSS, pruebas, respuesta read-only servida, specs CT-8.5/N6, S17, S18 y frontend actual en shell-minimo-react, 2026-08-31"
resumen: "Migracion vertical S19 de la hoja BI Curva S a la SPA React: plan vigente, avance real, proyeccion con banda de incertidumbre, fechas clave, tendencia, gatillo de replanificacion de 30 dias sostenido dos cortes, filtros, multi-proyecto, detalle accesible y linaje, sin mutaciones, RLS, schema ni datos."
---

# S19 — Hoja BI Curva S en React

> **Estado:** diseño tecnico autorrevisado y decision-complete. No quedan decisiones de negocio,
> producto, estrategia o PM que impidan escribir el plan. Esta spec no autoriza implementacion,
> commits, DDL/DML, cambios RLS, cambios de permisos, deploy, publicacion ni trabajo en
> `/admin/`. Su plan se escribe a continuacion con `superpowers:writing-plans`, conforme al
> programa aprobado de 27 specs y 27 planes.

## Relacion con el programa

S19 desarrolla la hoja CT-8.5 aprobada para los dos lienzos BI:

- Gerencia A: Resumen Ejecutivo, Programa General, Curva S y Proveedores.
- Obra D/R: Intermedia, Programa General, Semanal, Curva S, PDC, Proveedores y Responsables.

La hoja se construye una sola vez, sin variante por audiencia. A, D y R ven la misma semantica; el
alcance cambia exclusivamente por proyectos, periodo y filtros autorizados.

Consume:

- T01 para sesion, proyecto activo, shell, sidebar, tema, route outlet y cliente HTTP;
- T03 para politica por hoja, query canonica, filtros, marco BI, estados, drawer y linaje;
- S17 para navegacion entre hojas y destinos del panorama;
- S18 para el modelo de progreso por duracion, la serie de ejecucion, el algoritmo probabilistico,
  la linea base contractual declarada y el lenguaje de fecha probable;
- S05 para el destino operativo de Programa General;
- S06 para el destino autorizado de Actualizar Cronograma cuando la señal convoca a replanificar.

S19 no es la mini curva incluida en S18. S18 ayuda a ordenar la ventana de seis semanas y presenta
la fecha probable como parte de una decision operativa amplia. S19 es la hoja de rendicion hacia
arriba y hacia afuera: explica la trayectoria completa, el contrato, la incertidumbre y si ya se
cumplio el gatillo aprobado para convocar replanificacion.

## Resultado buscado

`/bi/curva-s` pasa a la SPA principal y:

1. muestra la trayectoria del plan vigente y del avance real por fecha de corte;
2. extiende la trayectoria con una proyeccion probable y una banda de incertidumbre;
3. dice en palabras la fecha probable, su rango y la brecha frente al fin contractual;
4. distingue plan vigente, linea base contractual y pronostico;
5. muestra fechas clave verificables sin inventar hitos intermedios;
6. explica si la brecha mejora o empeora frente al corte anterior;
7. convoca a replanificar cuando la probable terminacion supera la contractual por al menos 30
   dias calendario en los dos ultimos cortes consecutivos comparables;
8. deja la señal en observacion cuando solo el ultimo corte cruza el umbral;
9. declara insuficiencia cuando faltan historia, linea base o dos cortes comparables;
10. permite seleccionar uno o varios proyectos autorizados sin promediar porcentajes por obra;
11. conserva semana, rango, subcontratista, responsable y etapa;
12. ofrece lectura equivalente de cada punto y detalle de corte sin depender del grafico;
13. muestra linaje, denominadores, cobertura, metodo, muestra, corte y limitaciones;
14. resuelve desde el servidor los enlaces a Programa General y Actualizar Cronograma;
15. funciona en desktop, tablet y movil, oscuro y claro, teclado, touch, zoom y lector de pantalla;
16. permanece completamente read-only.

El ritmo de uso se declara en la pantalla: mensual o por hito, en revision de gerencia o rendicion
al cliente. La hoja no genera correo semanal y no se evalua con un ritual semanal.

## Alcance

### Incluido

- `GET /bi/curva-s` como ruta SPA al corte.
- El `GET /api/bi/report/curva-s` existente, estabilizado con envelope canonico.
- A/D/R conforme al gate BI y a la capacidad por proyecto.
- Proyecto activo por defecto y seleccion multi-proyecto explicita.
- Semana como corte terminal de una historia, no como un unico punto.
- Rango visible con un punto de contexto anterior cuando exista.
- Filtros subcontratista, responsable y etapa sobre cohorte, denominador y pronostico.
- Curva del plan vigente ponderada por duracion inclusiva.
- Curva real acumulada desde snapshots operativos.
- Brecha real menos plan y cambio frente al corte anterior.
- Banda de proyeccion, fecha probable y rango con la misma matematica de S18.
- Fin contractual exclusivamente desde la linea base declarada.
- Evaluacion N6 de 30 dias sostenidos durante dos cortes.
- Evaluacion y desglose por proyecto en scope multiple.
- Fechas clave: inicio contractual, corte, fin del plan vigente, fin contractual y rango probable.
- SVG responsivo, tabla o tarjetas equivalentes y detalle contextual de corte.
- Titular narrativo por plantillas finitas.
- Acciones read-only con href a S18/S05/S06 segun capacidad.
- Carga, listo, parcial, vacio, insuficiente, offline, query invalida y error.
- Linaje visible y cobertura de actividades/duracion/snapshots.
- Oscuro/claro, cinco viewports, zoom 200 por ciento, reduced motion y accesibilidad.
- Contrato PHP, Zod, pruebas puras, componentes y navegador interceptado.
- Convivencia, corte, rollback y retiro diferido de piezas legacy compartidas.

### Fuera de alcance

- Todo `/admin/`.
- Cambiar RLS, runtime boundary, ProjectScope, grants, usuarios, credenciales, schema, vistas SQL,
  tablas, columnas, indices, triggers, datos o linea base.
- Ejecutar DDL/DML, seeds, backfills o tests que escriban y hagan rollback.
- Crear o editar una reprogramacion desde la hoja BI.
- Escribir en `bi_action_queue`, crear acciones, cerrarlas o guardar comentarios.
- Cambiar el algoritmo de 240 simulaciones aprobado en S18.
- Crear una segunda definicion de progreso, forecast o fecha contractual.
- Convertir porcentaje de brecha en dias mediante una regla inventada.
- Usar costo, presupuesto, valor ganado, CPI o flujo de caja.
- Tratar encabezados `Titulo=1` como hitos: son frentes/capitulos, no hitos contractuales.
- Inventar hitos intermedios a partir de nombres, ruta critica o fechas de actividades.
- Crear un catalogo o tabla de hitos.
- Mostrar una curva contractual completa: hoy solo existe inicio/fin contractual declarado, no una
  serie contractual versionada.
- Añadir exportacion, impresion o descarga que legacy no ofrece.
- Añadir libreria de graficos.
- Cambiar S18, S05 o S06 dentro de esta entrega salvo el minimo enlace tipado acordado.
- Eliminar el layout/vista/JS/CSS BI compartido antes del gate conjunto T03/S17–S24.
- Regenerar goldens sin aprobacion explicita.

## Punto de partida medido

### React

- No existe pagina, modulo, schema, gateway ni ruta React de Curva S.
- El frontend actual solo contiene el shell minimo, login, selector de proyecto, sidebar y tema.
- La sidebar recibe un destino BI autorizado por el servidor; no expone una entrada Curva S
  independiente.
- Los modulos T03, S17 y S18 existen como specs/planes, no como codigo implementado.

### Rutas y acceso

Rutas actuales:

| Verbo | Ruta | Controlador |
|---|---|---|
| GET | `/bi/curva-s` | `BiViewController::curvaS` |
| GET | `/api/bi/report/curva-s` | `BiControlTowerApiController::curvaS` |

Ambas pasan por:

- sesion;
- `BiPreviewAccessPolicy`;
- `BiProjectScope`;
- membresia visible;
- `lps.indicadores.ver` en el rol real de cada proyecto.

Admin A entra siempre al preview. D/R dependen del flag global vigente. Quien no supera el gate
recibe 404 para no revelar el modulo. Un project scope no autorizado produce 403.

Brechas:

- el montaje actual no aplica todavia una politica declarativa por hoja;
- el endpoint acepta aliases y authority-like keys que el parser T03 debe rechazar;
- el cliente legacy envia tanto `project_id` como `project_ids[]`;
- el rol viaja en la respuesta aunque React no debe usarlo como autoridad.

### Payload servido

La lectura read-only contra el contenedor montado confirmo estas claves:

- `respuesta`;
- `project_ids/project_id`;
- `semana/report_key/role/filters`;
- `data_source/raw_row_count`;
- `pdc_breakdown/pdc_items/activity_snapshot` en null;
- `executive_brief`;
- cuatro filas de `scorecard`;
- `charts.chart-curva-s`;
- `drivers/risks/recommended_actions/lineage`.

`chart-curva-s` contiene:

- `type=line`;
- labels;
- dos datasets: Curva teorica y Curva real;
- source_relations;
- grain.

No contiene:

- proyeccion;
- banda;
- fecha probable;
- linea contractual;
- hitos/fechas clave;
- desglose por proyecto;
- señal N6;
- cobertura;
- semantica de punto observado/futuro.

Con `semana=6` la respuesta real medida produjo una fila y un punto. Con un rango amplio produjo
11 puntos para un proyecto y 13 para dos proyectos. Por tanto el selector Semana actual convierte
una curva en un punto aislado; solo un rango permite observar tendencia.

### Fuente y formula actual

`fetchCurvaS` construye un CTE directo sobre:

- `programa_consolidado`;
- `semanas_activas`.

Reglas:

- `Titulo=0`;
- fechas inicio/fin no nulas;
- duracion `DATEDIFF(fin,inicio)+1` positiva;
- corte `Fecha_Fin_Sem` o `Fecha_Inicio_Sem`;
- actividad base = snapshot mas reciente del proyecto dentro de la consulta;
- real = `clamp(Ejecutado,0,1) * duracion`;
- teorico = dias transcurridos inclusivos acotados a la duracion;
- agregado = suma ponderada / suma de duraciones;
- desviacion = real menos teorico;
- criticas atrasadas = ruta critica, fin anterior al corte y ejecucion menor que uno.

La vista SQL `bi_curva_s_duracion` declara la misma intencion por
`project_id + Semana`, pero el endpoint vivo usa el CTE de `ControlTowerService`, no esa vista.
El catalogo aun declara `execution_source=bi_curva_s_duracion`: el linaje debe reconciliarse con
la ruta ejecutada, no repetir una fuente nominal inexacta.

La agregacion multi-proyecto suma duraciones y avances antes de dividir. Eso es correcto para una
curva consolidada; no equivale a promediar porcentajes. Sin embargo la respuesta actual pierde el
desglose y publica `project_id=0` dentro de la fila agregada.

### Scorecard, titular y accion actuales

Scorecard toma la ultima fila:

- Avance real;
- Avance teorico;
- Desviacion;
- Criticas atrasadas.

`StorytellingService::briefCurvaS` y
`ActionRecommendationService::actionsFromCurvaS` toman `data[0]`, la primera fila. En un rango:

- el scorecard habla del ultimo corte;
- el titular y la accion hablan del primero;
- la pagina no muestra ninguno de los tres, porque su seccion solo monta el canvas.

La accion legacy se activa con desviacion menor a -5 puntos porcentuales. Este umbral contradice la
decision posterior N6: replanificar se convoca por 30 dias de brecha probable sostenidos dos
cortes. El adapter legacy puede conservar la forma, pero el producto React debe usar N6.

### Render legacy

VIEW-08 comparte una sola seccion Curva S:

- una tarjeta;
- titulo Programado vs Ejecutado;
- un `canvas` Chart.js de 280 px;
- ninguna cifra, titular, accion, fecha clave o leyenda propia fuera de Chart.js.

`renderCurvaS` obtiene el chart y llama `renderLineChart`. No hay interaccion de punto, drawer,
toggle de proyeccion ni detalle.

`syncChartDataTable` crea una tabla equivalente desde los arrays del chart, pero la deja dentro de
`aia-visually-hidden`. Es util para lector de pantalla, no para una persona que no puede leer el
grafico visual.

El grafico:

- limita eje a 0–100;
- no dibuja puntos;
- no anima;
- adapta aspect ratio;
- ofrece tooltip solo por hover/puntero;
- depende de Chart.js y de un canvas.

### Filtros y periodo actuales

Filtros:

- proyecto multi-select;
- semana para un proyecto;
- rango para multiples;
- subcontratista;
- responsable;
- etapa/torre/intervencion.

El servidor permite semana o rango. Si hay rango, este reemplaza semana y se declara
`date_range_overrides_semana=true`.

Problemas:

- una semana filtra el SQL a esa semana exacta y destruye la trayectoria;
- multi-proyecto obliga rango en cliente aunque el servidor acepta semana;
- no se explica que filtrar sub/responsable/etapa cambia cohorte y denominador;
- no se incluye el punto inmediatamente anterior a un rango para leer direccion;
- dos proyectos con igual numero de semana pueden tener fechas de corte distintas.

### Responsive y accesibilidad actuales

- El layout compartido tiene drawer de filtros y tabs con teclado.
- El canvas responde al ancho y el documento evita overflow horizontal.
- En movil se reduce el aspect ratio, pero la informacion sigue siendo un grafico unico.
- No hay vista de tarjetas por punto.
- No hay accion accesible sobre un corte.
- La tabla equivalente esta oculta visualmente.
- No hay texto de banda, fechas clave, insuficiencia o señal N6.
- No existe matriz propia de S19 para 390, 480, 768, 1180 y 1440, oscuro/claro y zoom.

### Estado React faltante

Falta toda la superficie:

- ruta;
- schema;
- gateway;
- hook;
- state machine;
- titular;
- cifras actuales;
- SVG;
- banda;
- fechas clave;
- señal de replanificacion;
- tabla/tarjetas;
- detalle de corte;
- linaje;
- tests.

## Diseño funcional objetivo

### Decision y ritmo

La pregunta principal es:

> ¿Debemos convocar una replanificacion del cronograma?

Las preguntas de apoyo son:

1. ¿como se mueve el avance real frente al plan vigente?;
2. ¿cuando terminaria probablemente y dentro de que rango?;
3. ¿la brecha contractual ya supero 30 dias durante dos cortes?;
4. ¿que cambió desde el corte anterior?;
5. ¿que datos, cohorte y limitaciones sostienen la conclusion?

Ritmo visible: mensual o por hito. La hoja se puede consultar cualquier dia, pero no produce una
alarma semanal ni un correo automatico.

### Orden del lienzo

1. Encabezado T03: hoja, proyectos, periodo, filtros y corte.
2. Titular de decision N6.
3. Fecha probable, rango, fin contractual y estado de evidencia.
4. Curva principal plan/real/proyeccion/banda.
5. Tendencia del ultimo corte y cuatro cifras actuales.
6. Fechas clave y desglose por proyecto.
7. Tabla o tarjetas equivalentes de cortes.
8. Acciones read-only.
9. Evidencia, cobertura, metodo y linaje.

No se anteponen riesgos genericos ni diez recomendaciones a la curva. La hoja debe poder proyectarse
ante un cliente sin ruido operativo.

## Periodo y cohorte

### Semana

Una `semana` seleccionada significa:

- corte terminal = fecha de esa semana en cada proyecto;
- historia visible = cortes validos desde el inicio disponible hasta ese terminal;
- plan de comparacion = snapshot vigente mas reciente a ese terminal;
- forecast = historia disponible hasta ese terminal;
- N6 = los dos ultimos cortes consecutivos a ese terminal.

Nunca significa una curva de un solo punto.

### Rango

`desde/hasta` significa:

- terminal = ultimo corte a o antes de `hasta`;
- puntos visibles dentro del intervalo;
- si existe, se añade el punto inmediatamente anterior a `desde` con
  `contextPoint=true` para continuidad y tendencia;
- N6 evalua los dos ultimos cortes a o antes del terminal, aunque el anterior sea el punto de
  contexto;
- el periodo y la tabla rotulan claramente el punto contextual.

React nunca envia semana y rango juntos. Durante convivencia, el adapter acepta la combinacion
legacy y conserva que rango reemplace semana.

### Filtros de cohorte

Subcontratista, responsable y etapa se aplican a:

- la cohorte del plan vigente;
- los matches historicos por `project_id + unique_id`;
- denominador de duracion;
- real/teorico;
- forecast;
- evaluacion N6;
- desglose.

La pantalla muestra `Alcance filtrado` cuando alguno esta activo. No presenta la cifra como avance
total de obra.

### Baseline de la serie

La curva teorica representa el **plan vigente al corte terminal**:

- snapshot mas reciente a o antes del terminal por proyecto;
- actividades `Titulo=0`;
- fechas validas;
- duracion inclusiva;
- cohorte filtrada.

No se llama linea contractual. La linea base contractual aporta inicio/fin declarados y el patron
para medir fecha probable, pero no existe una serie contractual versionada que se pueda dibujar.

La historia real usa, para cada fecha, el snapshot mas reciente a o antes del punto y cruza la
cohorte vigente por `project_id + unique_id`. Una actividad sin match en ese corte aporta cero
real. Este caracter retrospectivo se declara en linaje: reprogramar puede cambiar la cohorte con la
que se relee el pasado.

### Formulas

Por punto:

    duration_i = DATEDIFF(Fecha_Fin, Fecha_Inicio) + 1
    real_i = clamp(Ejecutado_i, 0, 1)
    planned_i(t) = clamp((t - Fecha_Inicio_i + 1) / duration_i, 0, 1)

Agregado:

    real_pct(t) = 100 * sum(duration_i * real_i(t)) / sum(duration_i)
    planned_pct(t) = 100 * sum(duration_i * planned_i(t)) / sum(duration_i)
    gap_pp(t) = real_pct(t) - planned_pct(t)
    gap_delta_pp = gap_pp(ultimo) - gap_pp(anterior)

Real y plan son acumulados, acotados 0–100 y no decrecientes como el motor S18. El cambio se expresa
con el delta redondeado a una decimal:

- positivo: la brecha mejoro;
- negativo: la brecha empeoro;
- cero tras redondeo: no cambio de forma observable.

Si el denominador es cero, el estado es `insufficient` y los porcentajes son null. Nunca se
fabrica cero.

## Proyeccion y banda

S19 consume el mismo motor extraido por S18:

- minimo tres incrementos reales positivos;
- 240 simulaciones deterministicas;
- curva S interpolada;
- banda de avance inferior/mediana/superior por fecha;
- distribucion de fecha de terminacion;
- P10 <= P50 <= P90 para fechas;
- rango probable del 80 por ciento;
- linea base contractual declarada por proyecto;
- scope y filtros identicos.

Presentacion:

- linea Plan vigente;
- linea Avance real;
- banda Rango probable;
- linea central Proyeccion probable;
- real termina en el corte y queda null en futuro;
- banda empieza en el corte, no reescribe puntos observados.

El titular no dice P10, P50, P90 ni Monte Carlo. Dice:

> Terminacion probable: 15 de marzo — entre el 2 de marzo y el 4 de abril. 88 dias despues de la
> comprometida.

El detalle tecnico si muestra percentiles, 240 simulaciones, muestra minima, ritmo y metodo.

Cuando falta historia:

- plan/real siguen visibles;
- forecast.status=unavailable;
- banda y fechas probables son null;
- se explica cuantos incrementos existen y cuantos se requieren.

Cuando falta linea base declarada:

- curva plan/real sigue visible;
- puede existir trayectoria proyectada de avance;
- no se publica variacion contractual ni señal N6;
- no se deduce fin contractual del primer o ultimo corte.

## Señal N6 de replanificacion

### Regla

Por cada proyecto:

1. tomar los dos ultimos cortes observados consecutivos a o antes del terminal;
2. reconstruir para cada corte el forecast usando solo historia disponible hasta ese corte y el
   snapshot vigente entonces;
3. obtener `p50_finish`;
4. calcular dias calendario entre fin contractual declarado y esa fecha;
5. recomendar replanificacion si ambos valores son mayores o iguales a 30.

Contrato:

    threshold_days = 30
    required_consecutive_cuts = 2
    current_variance_days >= 30
    previous_variance_days >= 30

Estados:

- `recommended`: ambos cortes cumplen;
- `watch`: el ultimo cumple y el anterior valido no;
- `not_required`: el ultimo no cumple;
- `insufficient`: faltan dos cortes, historia, forecast o linea base.

Un corte mayor o igual a 30 no basta. Un porcentaje negativo de la curva no se convierte a dias.

### Multi-proyecto

N6 se evalua por proyecto. No:

- promedia dias;
- usa un proyecto para completar el segundo corte de otro;
- declara que todo el portafolio necesita replanificar porque una obra cruza.

El titular multiproyecto dice:

- cuantos proyectos estan `recommended`;
- cuales, por nombre autorizado;
- cuantos estan `watch` o `insufficient`;
- que la accion se decide por obra.

La banda de fecha consolidada solo se publica cuando todos los proyectos seleccionados tienen
forecast comparable. Se agrega por simulacion tomando la fecha de finalizacion mas tardia y luego
percentiles, igual que S18. Si uno falta, el consolidado es parcial/insuficiente y se conserva el
desglose disponible sin sesgo.

### Tratamiento visual y narrativo

La convocatoria es una plantilla N1, no una alarma roja:

- texto explicito;
- estado `recommended`;
- rail/tint de atencion del design system;
- evidencia de los dos cortes;
- CTA ordinario.

No parpadea, no usa solo color y no dispara notificacion automatica.

Plantillas:

- recommended single;
- watch single;
- not_required single;
- insufficient single;
- recommended multi;
- mixed multi;
- insufficient multi.

Cada titular incluye `templateKey`, facts, cutoff y confidence. React no recompone frases.

## Fechas clave e hitos

La seccion se llama `Fechas clave` y distingue:

| Tipo | Fuente | Semantica |
|---|---|---|
| Inicio contractual | linea base declarada | fecha comprometida de inicio |
| Corte | semanas_activas | hasta donde llega el real |
| Fin del plan vigente | max Fecha_Fin de cohorte terminal | fin del cronograma vigente filtrado |
| Fin contractual | linea base declarada | patron de desviacion |
| Rango probable | forecast | intervalo, no compromiso |

`Titulo=1` son frentes/capitulos y no entran como hitos. Una actividad de un dia tampoco se llama
hito por su duracion. No hay fuente de hitos contractuales intermedios en el repo auditado.

Por tanto:

- S19 cumple la lectura de hitos con las fechas contractuales disponibles;
- muestra `No hay hitos intermedios declarados`;
- no inventa nombres ni fechas;
- una futura fuente explicita exige enmienda de spec, contrato y prueba antes de pintarse.

## Cifras y tendencia

El bloque actual contiene:

- avance real con denominador;
- avance del plan vigente con denominador;
- brecha en puntos porcentuales;
- mejora/empeoramiento frente al corte anterior;
- actividades criticas atrasadas;
- total de actividades validas;
- duracion total ponderada;
- fecha de corte.

No se muestran cuatro KPI genericos sin contexto. Cada cifra declara unidad, base, scope y cutoff.

El conteo de criticas atrasadas suma actividades; no se presenta como causa unica de la brecha. Un
link a S18 abre el detalle operativo con el mismo scope/periodo/filtros.

## Detalle de corte

Cada fila/tarjeta de la lectura equivalente ofrece `Ver detalle`. El drawer T03 muestra:

- fecha/corte y si es contexto;
- plan;
- real;
- brecha;
- delta frente al anterior;
- banda/proyeccion si aplica;
- denominador de actividades y duracion;
- cutoffs por proyecto;
- desglose por proyecto;
- cobertura/exclusiones;
- metodo y linaje;
- href a S18.

No hay un endpoint de detalle nuevo: la informacion viaja en el snapshot principal. Abrir/cerrar el
drawer no hace red.

El drawer:

- toma foco;
- atrapa Tab;
- cierra con Escape y boton visible;
- devuelve foco al trigger;
- soporta deep link `focus=cutoff&date=YYYY-MM-DD`;
- si la fecha no pertenece al payload, muestra query invalida sin seleccionar otra en silencio.

## Acciones

Acciones server-authored:

1. `Ver Programa General`: S18/S05 con scope, periodo y filtros.
2. `Convocar replanificacion`: visible cuando N6 es recommended.
3. `Abrir Actualizar Cronograma`: solo si el servidor resuelve
   `lps.programa_general_actualizar.ver`.

Si puede ver pero no editar S06, el CTA se rotula de consulta. Si no puede verlo, no se muestra un
boton muerto; la accion dice que debe coordinarse con el Director de obra.

S19 no llama:

- `createAction`;
- `closeAction`;
- POST/PUT/PATCH/DELETE;
- endpoints de S06.

El href navega; la mutacion, si existe despues, ocurre dentro de S06 con sus permisos, CSRF,
preview y confirmacion.

## Contrato HTTP target

### Ruta

Se conserva:

    GET /api/bi/report/curva-s

No se crea `/react`, `/v2`, `/detail` ni endpoint paralelo.

### Envelope

Exito:

    { "ok": true, "data": { } }

Error:

    {
      "ok": false,
      "error": {
        "code": "BI_CURVE_UNAVAILABLE",
        "message": "No pudimos cargar la Curva S.",
        "retryable": true,
        "fieldErrors": {}
      }
    }

Durante convivencia puede incluir `respuesta=BIEN` y las claves legacy fuera de `data`.

### Data canonico

- `reportKey=curva-s`;
- `scope`;
- `period`;
- `filters`;
- `headline`;
- `decision`;
- `current`;
- `curve`;
- `forecast`;
- `replanning`;
- `keyDates`;
- `projectBreakdown`;
- `actions`;
- `lineage`;
- `meta`.

### Scope

- projectIds positivos y autorizados;
- projectCount;
- single/multi;
- nombres ya autorizados;
- cutoff por proyecto;
- capability-resolved actions;
- nunca role/db/prefix como autoridad.

### Curve

`curve.points[]`:

- date ISO;
- label;
- contextPoint;
- observed;
- cutoff;
- plannedPct;
- actualPct;
- gapPp;
- gapDeltaPp;
- forecastLowerPct;
- forecastMedianPct;
- forecastUpperPct;
- activityCount;
- durationDays;
- projectValues[];
- completeness;

`curve.coverage`:

- eligibleActivityCount;
- excludedActivityCount;
- totalDurationDays;
- matchedSnapshotCount;
- missingSnapshotCount;
- filterScope;
- limitations.

No incluye configuracion Chart.js.

### Forecast

- status available/unavailable/partial;
- reason;
- method;
- simulationCount;
- positiveIncrementCount;
- minimumPositiveIncrements;
- contractualFinish;
- currentPlanFinish;
- p10Finish;
- p50Finish;
- p90Finish;
- variationDays;
- probableRange80;
- projectBreakdown.

### Replanning

- thresholdDays=30;
- requiredConsecutiveCuts=2;
- status;
- recommended;
- currentCut;
- previousCut;
- projects[];
- summary;
- templateKey/facts;

Cada corte conserva:

- cutoff;
- forecastStatus;
- probableFinish;
- contractualFinish;
- varianceDays;
- qualifies;
- reason.

### Compatibilidad

Hasta cero callers:

- respuesta;
- project_ids/project_id;
- semana/report_key/role/filters;
- raw_row_count;
- executive_brief;
- scorecard;
- charts.chart-curva-s con dos datasets;
- drivers/risks/recommended_actions/lineage.

El adapter legacy deriva sus dos lineas del read model canonico. No ejecuta un segundo calculo.

### Validacion de query

- project IDs enteros positivos;
- semana o rango canonico;
- fechas ISO y desde <= hasta;
- sub/resp/etapa con trim y limites T03;
- focus=cutoff solo con date ISO;
- authority-like keys rechazadas;
- aliases legacy normalizados solo en adapter;
- scope revalidado antes de leer.

### Errores

| HTTP | code |
|---:|---|
| 400/422 | BI_CURVE_QUERY_INVALID |
| 403 | BI_PROJECT_SCOPE_DENIED |
| 404 | NOT_FOUND |
| 409 | BI_CURVE_PERIOD_NOT_COMPARABLE |
| 500 | BI_CURVE_UNAVAILABLE |

Forecast o N6 insuficiente no es 500: es success con seccion unavailable. Un fallo opcional de
project breakdown puede producir partial si el snapshot principal conserva coherencia y corte.

## Zod y gateway

Schema:

- `frontend/src/lib/api/esquemas/biCurvaS.ts`.

Refinamientos:

- reportKey exacto;
- project IDs positivos/unicos;
- fechas ISO;
- puntos ordenados y unicos;
- maximo un contextPoint y anterior al rango;
- planned/actual/banda finitos 0–100 o null segun estado;
- actual null en futuro;
- banda null antes del corte y completa despues si available;
- lower <= median <= upper por punto;
- gap = actual - planned dentro de tolerancia;
- delta = gap actual - gap anterior;
- denominador cero exige insufficient/null;
- p10Finish <= p50Finish <= p90Finish;
- forecast available exige 240 simulaciones y minimo tres incrementos;
- contractual variation exige fin contractual declarado;
- threshold 30 y cuts 2 son constantes;
- recommended exige dos cortes qualifies;
- watch exige ultimo qualifies y anterior no;
- insufficient no contiene recommendation=true;
- multi conserva una evaluacion por proyecto;
- action href es ruta interna permitida;
- no campos de costo ni mutacion.

Tipos solo con `z.infer`.

Gateway:

- `frontend/src/lib/api/biCurvaS.ts`;
- usa solo `frontend/src/lib/api/cliente.ts`;
- acepta query T03 y AbortSignal;
- llama la ruta existente;
- parsea antes de devolver;
- no cachea entre usuarios/scope.

Ningun componente llama `fetch`.

## Estado frontend

`useCurvaS`:

- queryKey canonica;
- requestId;
- AbortController;
- reemplazo atomico;
- stale response ignorada;
- refetch visible;
- no mezcla cortes;
- cache ligada a identidad/scope/periodo/filtros.

Estados:

- loading;
- ready;
- partial;
- empty;
- insufficient plan denominator;
- forecast unavailable;
- replanning insufficient;
- offline;
- invalid query;
- denied;
- server error.

Un cero valido no se confunde con sin datos.

Selecciones locales:

- banda visible/oculta;
- tabla/tarjetas expandida;
- corte seleccionado;
- drawer abierto.

Cambiar scope/periodo/filtros:

- aborta;
- limpia seleccion no valida;
- conserva focus solo si el corte existe;
- reemplaza snapshot;
- no concatena puntos.

## Responsive

### 1180x820

- titular y decision arriba;
- fecha probable/contractual en una fila compacta;
- grafico dominante sin empujar la decision bajo el fold;
- tendencia y fechas clave al lado o debajo;
- tabla accesible en disclosure;
- drawer lateral.

### 1440x900

- grafico con mayor aire, no mas ruido;
- desglose multiproyecto puede ocupar segunda columna;
- linaje permanece secundario.

### 768x1024

- una columna;
- SVG refluye;
- tabla semantica preservada;
- drawer amplio;
- filtros T03 en drawer.

### 390x844 y 480x900

- titular en frases cortas;
- cifras y fechas clave en cards;
- SVG simplifica labels sin perder tabla;
- puntos se leen como cards, no tabla encogida;
- ultimos cortes primero en la lista visual, con orden cronologico anunciado;
- drawer full-height;
- CTA visible;
- no overflow de pagina.

Regla de montaje:

- >=768: tabla;
- <768: cards;
- nunca ambos montados y ocultos solo por CSS.

## Accesibilidad

- un h1;
- jerarquia de headings;
- titular como texto, no live region inicial;
- SVG con title/desc y nombres de series;
- banda descrita como rango, no solo area;
- fechas con `datetime`;
- cifras con unidad/base;
- tabla/card equivalente para todos los puntos;
- contextPoint rotulado;
- acciones con href;
- detalle por boton, no por hover;
- no color-only;
- contraste de lineas/banda en ambos temas;
- foco visible;
- dialog focus trap/return/Escape;
- controles 44x44;
- teclado y touch;
- zoom 200%;
- reduced motion;
- axe serious/critical cero;
- consola limpia.

El SVG no necesita hacer focusable cada punto. La interaccion completa vive en botones de
fila/tarjeta; el grafico es una representacion y nunca la unica puerta al detalle.

## Design system

- `public/css/tokens.css` exclusivamente;
- oscuro default/fallback;
- claro con capacidad identica;
- sin color literal;
- sin `!important` nuevo;
- sin estilos inline de color;
- sin Chart.js nuevo;
- SVG con custom properties/tokens;
- plan, real, banda y contractual distinguibles por texto, patron/linea y color;
- recommended usa semantica de atencion, no una variante roja inventada;
- insufficient usa neutro;
- cualquier token nuevo se documenta y prueba.

## Seguridad, aislamiento y RLS

- T03 sheet policy autoriza A/D/R.
- `BiPreviewAccessPolicy` mantiene 404.
- `BiProjectScope` decide proyectos.
- Cada lectura se filtra por project_id.
- `unique_id` nunca se cruza sin project_id.
- `semanas_activas`, programa y linea base se leen por el mismo proyecto.
- Multi-proyecto conserva cutoffs separados.
- Query no acepta role, permiso, db, prefix, usuario ni capability.
- Acciones/capacidades vienen del servidor.
- Error no filtra proyectos ni fuentes internas.
- Endpoint es GET.
- Browser falla ante cualquier mutacion.
- No se toca RLS, schema, SQL views, grants, usuarios, credenciales o datos.

Tests nuevos usan fakes/static fixtures. No MySQL. Los tests BI existentes que siembran y revierten
sirven como referencia de formula, no como gate permitido de S19.

## Arquitectura objetivo

### Backend

Reutiliza de S18:

- `BiProgramProjectReader`;
- `BiProgramProgressAdapter`;
- `BiProgramForecastAdapter`;
- value objects de scope, cutoff, progress y forecast.

S19 añade:

- `BiCurveReadService`;
- `BiCurveSeriesBuilder`;
- `BiCurveReplanningPolicy`;
- `BiCurveHeadline`;
- `BiCurvePresenter`;
- adapter de compatibilidad legacy.

`BiCurveSeriesBuilder` recibe snapshots ya autorizados; no consulta DB. La policy N6 recibe dos
forecasts por proyecto y devuelve un resultado puro. El servicio orquesta un solo snapshot. El
controller queda thin.

`ControlTowerService::fetchCurvaS`, `scorecardCurvaS`,
`StorytellingService::briefCurvaS` y `actionsFromCurvaS` delegan gradualmente o quedan detras
del presenter compatible. No se duplica el algoritmo S18 ni se copia el monolito.

### Frontend

Propuesta:

- `frontend/src/modulos/bi/CurvaSPagina.tsx`;
- `frontend/src/modulos/bi/curva/TitularCurvaS.tsx`;
- `frontend/src/modulos/bi/curva/ResumenCurvaS.tsx`;
- `frontend/src/modulos/bi/curva/GraficoCurvaS.tsx`;
- `frontend/src/modulos/bi/curva/FechasClaveCurvaS.tsx`;
- `frontend/src/modulos/bi/curva/TendenciaCurvaS.tsx`;
- `frontend/src/modulos/bi/curva/DesgloseProyectosCurvaS.tsx`;
- `frontend/src/modulos/bi/curva/PuntosCurvaS.tsx`;
- `frontend/src/modulos/bi/curva/DetalleCorteCurvaS.tsx`;
- `frontend/src/modulos/bi/curva/AccionesCurvaS.tsx`;
- `frontend/src/modulos/bi/curva/useCurvaS.ts`;
- `frontend/src/modulos/bi/curva/curva-s.css`;
- schema/gateway indicados arriba.

T03 aporta marco, filtros, estado, drawer y linaje. React no calcula progreso, forecast, N6,
headline, permisos ni href.

## Convivencia y retiro

### Corte

1. caracterizar endpoint y respuesta real;
2. extraer/reusar motor S18;
3. estabilizar envelope/Zod;
4. montar shadow route;
5. comparar plan/real legacy;
6. probar N6/banda/fechas clave;
7. probar roles/temas/viewports;
8. cortar `/bi/curva-s`;
9. conservar adapter legacy.

### Retiro diferido

S19 no elimina por si solo:

- section `view-curva-s` de VIEW-08;
- `renderCurvaS`;
- entradas VIEW_META/VIEW_FROM_PATH;
- canvas/chart helpers;
- CSS compartido;
- Chart.js;
- VIEW-04/05/06.

T03 los retira despues de S17–S24 y cero callers. Si Chart.js aun sirve otra hoja, permanece.

### Rollback

- devolver solo `/bi/curva-s` al render legacy;
- conservar endpoint canonico y adapter;
- verificar A/D/R, 404 y 403;
- verificar semana y rango;
- verificar plan/real;
- restaurar React;
- no restaurar datos.

## Estrategia de pruebas

### PHP puro

- politica A/D/R;
- query semana/rango/filtros;
- week terminal produce historia;
- range + context point;
- cohorte terminal;
- duration inclusiva;
- Titulo=0/fechas validas;
- real/plan/gap/delta;
- zero denominator insufficient;
- snapshot matching project_id+unique_id;
- multi aggregate from sums;
- cutoffs distintos;
- project breakdown;
- forecast 240/minimo 3;
- band point ordering;
- declared baseline only;
- missing baseline;
- current plan finish distinct from contractual;
- N6 recommended/watch/not_required/insufficient;
- exactly 30 days qualifies;
- two consecutive;
- multi evaluated per project;
- headline templates;
- key dates;
- no synthetic milestone;
- capabilities/actions/hrefs;
- legacy adapter;
- source invariants read-only/project-scoped.

No Database.

### Frontend

- schema refinements;
- gateway only client;
- hook race/abort;
- all main states;
- headline templates;
- current metrics;
- SVG series/band;
- band toggle;
- table/cards;
- detail drawer;
- dates;
- multi breakdown;
- actions;
- lineage;
- dark/light/responsive/a11y.

### Browser interceptado

- T01/T03 antes de navegar;
- Curva GET interceptado;
- toda mutacion bloqueada;
- A/D/R allowed;
- V/hidden denied;
- project denied;
- week history;
- range/context;
- filters;
- forecast available/unavailable;
- baseline missing;
- N6 four states;
- exact threshold;
- single/multi;
- distinct cutoffs;
- deep link cutoff;
- five viewports;
- dark/light;
- 200 zoom;
- reduced motion;
- keyboard/touch/focus;
- no overflow;
- axe/consola.

## Criterios de aceptacion

1. S19-AC-01: /admin/ queda fuera.
2. S19-AC-02: /bi/curva-s es ruta SPA al corte.
3. S19-AC-03: S19 reutiliza T01/T03/S18 y no duplica shell, query, progreso o forecast.
4. S19-AC-04: A/D/R entran segun gate y lps.indicadores.ver.
5. S19-AC-05: roles no autorizados reciben 404.
6. S19-AC-06: proyecto no autorizado recibe 403 sin datos.
7. S19-AC-07: project/role/permiso cliente no conceden autoridad.
8. S19-AC-08: la hoja compartida no crea variantes por audiencia.
9. S19-AC-09: el proyecto activo autorizado es default.
10. S19-AC-10: multi-proyecto requiere seleccion explicita.
11. S19-AC-11: semana/rango/sub/resp/etapa se preservan.
12. S19-AC-12: semana selecciona terminal y devuelve historia, no un punto.
13. S19-AC-13: rango incluye puntos visibles y un contexto anterior cuando existe.
14. S19-AC-14: adapter legacy conserva que rango reemplace semana.
15. S19-AC-15: el unico GET tiene prueba PHP y schema Zod.
16. S19-AC-16: no se crea endpoint paralelo.
17. S19-AC-17: authority-like query keys se rechazan.
18. S19-AC-18: plan/real ponderan duracion calendario inclusiva.
19. S19-AC-19: solo Titulo=0 con fechas/duracion validas entra.
20. S19-AC-20: la teorica se rotula Plan vigente, no linea contractual.
21. S19-AC-21: real cruza snapshot por project_id+unique_id.
22. S19-AC-22: plan/real son acumulados, acotados 0–100 y no decrecientes.
23. S19-AC-23: denominador cero produce insufficient/null, no cero.
24. S19-AC-24: cifras actuales reconcilian con el ultimo punto.
25. S19-AC-25: gap es real menos plan.
26. S19-AC-26: tendencia es delta del gap contra corte anterior.
27. S19-AC-27: multi agrega sumas ponderadas y nunca promedia porcentajes de proyecto.
28. S19-AC-28: iguales semanas con distintos cutoffs conservan fechas propias.
29. S19-AC-29: multi conserva desglose completo por proyecto.
30. S19-AC-30: puntos son ISO, ordenados y unicos.
31. S19-AC-31: curva contiene plan, real, probable y banda inferior/superior.
32. S19-AC-32: real es null despues del corte.
33. S19-AC-33: banda empieza en el corte y no reescribe observados.
34. S19-AC-34: forecast usa 240 simulaciones deterministicas.
35. S19-AC-35: forecast exige minimo tres incrementos positivos.
36. S19-AC-36: lower<=median<=upper en cada punto proyectado.
37. S19-AC-37: fechas cumplen P10<=P50<=P90.
38. S19-AC-38: fin contractual sale solo de linea base declarada.
39. S19-AC-39: sin linea base no se inventa variacion ni señal N6.
40. S19-AC-40: titular dice fecha+rango+brecha en palabras, no P50.
41. S19-AC-41: fechas clave distinguen contrato, corte, plan vigente y forecast.
42. S19-AC-42: Titulo=1 no se trata como hito.
43. S19-AC-43: sin fuente explicita se declara ausencia de hitos intermedios.
44. S19-AC-44: N6 usa umbral >=30 dias calendario.
45. S19-AC-45: N6 exige dos cortes consecutivos.
46. S19-AC-46: N6 se calcula por proyecto.
47. S19-AC-47: un solo corte sobre umbral produce watch, no recommended.
48. S19-AC-48: historia/baseline/cortes insuficientes producen insufficient.
49. S19-AC-49: convocatoria usa plantilla N1 y no alarma roja.
50. S19-AC-50: headline/decision/actions vienen del servidor.
51. S19-AC-51: S19 no llama action queue ni mutaciones.
52. S19-AC-52: CTA S06 depende de capacidades resueltas por servidor.
53. S19-AC-53: enlace S18/S05 conserva scope, periodo y filtros.
54. S19-AC-54: cada corte abre detalle sin request adicional.
55. S19-AC-55: detalle conserva valores, denominadores, breakdown, cobertura y linaje.
56. S19-AC-56: drawer cumple foco, trap, Escape y retorno.
57. S19-AC-57: >=768 monta tabla y <768 monta cards.
58. S19-AC-58: SVG tiene title/desc y banda descrita.
59. S19-AC-59: todos los puntos tienen alternativa textual visible.
60. S19-AC-60: no hay accion color-only, hover-only o canvas-only.
61. S19-AC-61: oscuro/claro tienen capacidad identica.
62. S19-AC-62: cinco viewports no tienen page overflow.
63. S19-AC-63: zoom 200 por ciento conserva lectura y acciones.
64. S19-AC-64: teclado, touch, foco y reduced motion cumplen.
65. S19-AC-65: axe serious/critical cero y consola limpia.
66. S19-AC-66: solo tokens, sin color literal/important ni nueva libreria chart.
67. S19-AC-67: loading/ready/partial/empty/insufficient/offline/invalid/error son visibles.
68. S19-AC-68: forecast parcial no borra plan/real coherentes.
69. S19-AC-69: stale response se ignora.
70. S19-AC-70: cambio de query aborta y reemplaza atomicamente.
71. S19-AC-71: cache no cruza usuario/scope/periodo/filtros.
72. S19-AC-72: cada cifra/serie/decision tiene linaje visible.
73. S19-AC-73: cobertura declara elegibles, excluidas, duracion y snapshots.
74. S19-AC-74: errores usan codigos canonicos sin filtrar internals.
75. S19-AC-75: solo cliente.ts llama fetch.
76. S19-AC-76: tipos frontend derivan de z.infer.
77. S19-AC-77: tests PHP nuevos usan fakes y no MySQL.
78. S19-AC-78: browser intercepta GET y falla en cualquier mutacion.
79. S19-AC-79: RLS/schema/grants/usuarios/credenciales/datos no cambian.
80. S19-AC-80: adapter conserva contrato legacy hasta cero callers.
81. S19-AC-81: legacy compartido se retira solo con gate T03/S17–S24.
82. S19-AC-82: rollback cambia ruta/codigo y no restaura datos.
83. S19-AC-83: no se regenera golden sin aprobacion.
84. S19-AC-84: la pantalla declara ritmo mensual o por hito y no correo semanal.
85. S19-AC-85: corte y alcance filtrado son visibles junto a las cifras.
86. S19-AC-86: multi no publica banda consolidada si falta forecast de una obra.
87. S19-AC-87: estado recomendado exacto muestra evidencia de ambos cortes.
88. S19-AC-88: cero valido se distingue de dato ausente en grafico y texto.

## Entregas verticales

### Entrega 1 — Contrato, periodo y curva base

- access/query;
- week-as-history;
- range/context;
- plan/real/gap/tendencia;
- endpoint/Zod;
- linaje/cobertura.

### Entrega 2 — Pronostico y decision

- banda;
- fecha probable/rango;
- baseline declarada;
- N6 cuatro estados;
- multi por proyecto;
- titular.

### Entrega 3 — Lectura y accion

- SVG;
- cifras/fechas clave;
- tabla/cards;
- drawer de corte;
- links S18/S05/S06.

### Entrega 4 — Calidad y corte

- responsive;
- dark/light;
- a11y;
- browser;
- convivencia/rollback/retiro diferido.

## Riesgos y mitigaciones

| Riesgo | Mitigacion |
|---|---|
| semana deja una curva de un punto | terminal + historia |
| plan vigente se lee como contrato | etiquetas/fuentes separadas |
| primer corte contamina titular | un read model con terminal explicito |
| -5 pp vuelve a disparar replan | N6 unica policy |
| 30 dias se deriva de pp | usar fecha probable vs contractual |
| un mal corte dispara accion | dos consecutivos |
| falta historia se lee como no riesgo | insufficient, no false |
| multi promedia fechas/porcentajes | sumas ponderadas + policy por proyecto |
| una obra sin forecast desaparece | breakdown completo + consolidado unavailable |
| banda parece certeza | rango, muestra, metodo, copy |
| Titulo=1 parece hito | exclusion explicita |
| se inventan hitos | solo fuentes declaradas |
| filtro parcial se lee como obra | alcance filtrado visible |
| SVG inaccesible | tabla/cards equivalentes |
| detalle exige nuevo endpoint | snapshot principal |
| S19 escribe replan | href a S06, cero mutaciones |
| adapter diverge | presenter unico |
| tests mutan base | fakes/interception |
| retiro rompe otras hojas | gate T03 conjunto |

## Decisiones descartadas

- Copiar el canvas legacy: no cumple decision, banda ni lectura visible.
- Mantener semana como punto exacto: destruye tendencia.
- Conservar -5 pp como gatillo: contradice N6.
- Convertir pp a dias con regla de tres: formula inventada.
- Usar solo el ultimo corte: N6 exige sostenimiento.
- Promediar variacion de proyectos: oculta la obra accionable.
- Publicar banda solo con obras disponibles: sesgo.
- Llamar contractual a la teorica: fuente distinta.
- Dibujar curva contractual desde inicio/fin: serie sintetica.
- Usar Titulo=1 como hito: son capitulos/frentes.
- Inferir hitos por actividades de un dia: heuristica sin contrato.
- Reutilizar generic risks/actions como protagonista: ruido y umbral obsoleto.
- Hacer puntos SVG la unica interaccion: inaccesible.
- Añadir Chart.js a React: SVG nativo basta.
- Crear detail endpoint: el snapshot ya contiene puntos.
- Crear accion persistida: hoja read-only.
- Probar con fixtures MySQL/rollback: sigue siendo DML.

## Decisiones pendientes

Ninguna. Si la implementacion descubre una fuente explicita y gobernada de hitos contractuales
intermedios, un cambio aprobado a N6, un consumidor externo que dependa de la semantica exacta del
chart legacy o una divergencia real entre el algoritmo S18 y la fuente publicada, se detiene solo
ese tramo, se aporta evidencia y se enmienda esta spec. No se inventa la semantica.

## Siguiente gate

Invocar `superpowers:writing-plans` para
`docs/superpowers/plans/2026-08-30-s19-bi-curva-s-react.md`, autorrevisarlo, actualizar el atlas y
continuar S20. No implementar S19 en esta sesion.
