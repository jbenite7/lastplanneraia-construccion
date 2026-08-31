---
capa: fuente
tipo: spec
estado: vigente
id: S20
fecha: 2026-08-31
superficie: bi-intermedia
rutas:
  - "/bi/intermedia"
  - "/api/bi/report/intermedia"
  - "/api/bi/control-tower/restricciones"
  - "/api/bi/control-tower/restricciones/pareto"
  - "/api/bi/control-tower/restricciones/{id}/gestion"
depende_de: [T01, T03, S07, S17, S18, S19, S21]
views: [VIEW-04, VIEW-05, VIEW-06, VIEW-08]
areas: [bi, design-system]
fuente: "auditoria de public/index.php, BiViewController, BiControlTowerApiController, BiConstraintListController, BiRestrictionParetoController, BiConstraintWriteController, ControlTowerService, MetricDictionaryService, MetricExecutor, LineageService, ActionRecommendationService, bi_pi_restricciones, pi_shared_constraints, pi_shared_constraint_links, VIEW-04/05/06/08, bi-spa.js, ct-app, CSS, pruebas, respuesta read-only servida, specs CT-8.3/CT-9/CT-10/CT-18.3/N4, S07, S17 y frontend actual en shell-minimo-react, 2026-08-31"
resumen: "Migracion vertical S20 de la hoja BI Intermedia a la SPA React principal: alarma de restricciones huerfanas, lista accionable y gestion, titular, semaforo 0-6, Pareto, filtros, alcance, linaje, responsive y oscuro/claro; absorbe ct-app y retira CT_PILOTO solo tras paridad, sin modificar RLS, schema ni datos."
---

# S20 — Hoja BI Intermedia en React

> **Estado:** diseño tecnico autorrevisado y decision-complete. No quedan decisiones de negocio,
> producto, estrategia o PM que impidan escribir el plan. Esta spec no autoriza implementacion,
> commits, DDL/DML, cambios RLS, cambios de permisos, deploy, publicacion ni trabajo en
> `/admin/`. Su plan se escribe a continuacion con `superpowers:writing-plans`, conforme al
> programa aprobado de 27 specs y 27 planes.

## Relacion con el programa

S20 desarrolla la hoja CT-8.3 aprobada para el lienzo de obra:

- Director de Obra D;
- Residente R;
- Administrador A cuando elige una obra.

La hoja responde a una decision concreta de la reunion semanal: identificar la restriccion que
puede impedir el siguiente compromiso, acordar responsable, fecha y estado, y dejar trazabilidad
sin abandonar la Torre.

Consume:

- T01 para sesion, proyecto activo, shell, sidebar, tema oscuro/claro, route outlet, CSRF y cliente
  HTTP unico;
- T03 para politica por hoja, query canonica, filtros, marco BI, estados, drawer contextual y
  linaje;
- S17 para navegacion entre hojas y el acceso desde el panorama;
- S07 como fuente operativa de Programacion Intermedia, sus actividades y restricciones;
- S18/S19 como destinos de contexto cuando la restriccion afecta trayectoria o fecha probable;
- S21 como hoja siguiente del ritual semanal.

S20 no reemplaza S07. S07 programa y edita el look-ahead de seis semanas. S20 selecciona y explica
las restricciones que requieren una conversacion de gestion. Una accion puede navegar a S07, pero
la Torre no duplica la grilla operativa completa.

## Resultado buscado

`/bi/intermedia` pasa a la SPA principal y:

1. muestra el proyecto, semana o rango y filtros efectivos;
2. avisa cuantas restricciones estan huerfanas con una regla verificable;
3. coloca la lista accionable por encima del titular, conforme a CT-18.3;
4. ordena la lista con N4 y expone la evidencia del orden;
5. permite buscar, filtrar, contar y limpiar filtros de presentacion;
6. muestra actividad bloqueada, cadena afectada, responsable, compromiso, atraso y ruta critica;
7. abre el drawer contextual con detalle, linaje y la accion de gestion;
8. permite gestionar responsable, fecha y estado cuando el servidor lo autoriza;
9. valida y guarda exclusivamente esos tres campos por el POST existente;
10. refresca el snapshot completo despues de guardar para evitar indicadores incoherentes;
11. presenta un titular finito, escrito por el servidor y sin umbrales inventados en React;
12. separa la observacion de adherencia de cualquier señal predictiva;
13. presenta el semaforo de semanas 0, 1–2, 3–4 y 5–6 con denominadores;
14. presenta el Pareto de restricciones duras no listas con codigo y etiqueta gobernada;
15. muestra linaje y limitaciones para cada cifra, lista y recomendacion;
16. soporta proyecto unico y lectura multiproyecto sin permitir gestion ambigua;
17. funciona en desktop, tablet y movil, oscuro y claro, teclado, touch, zoom y lector de pantalla;
18. absorbe la isla `ct-app/` y elimina `CT_PILOTO` solo despues de demostrar paridad.

El ritmo de uso se declara en la pantalla: reunion semanal de obra, antes de revisar Programacion
Semanal. La hoja no envia correo ni crea tareas persistidas.

## Alcance

### Incluido

- `GET /bi/intermedia` como ruta SPA al corte.
- `GET /api/bi/report/intermedia` como unico snapshot canonico de lectura.
- A/D/R conforme al gate BI y a `lps.indicadores.ver` por proyecto.
- Proyecto activo autorizado por defecto y seleccion multiproyecto explicita en solo lectura.
- Semana, rango, subcontratista, responsable y etapa de T03.
- Un corte efectivo por proyecto cuando las semanas ancla difieren.
- Alarma de huerfanas.
- Lista de restricciones compartidas con cadena de actividades.
- Orden N4, busqueda, filtros de presentacion, conteos y limpiar filtros.
- Titular de reunion.
- Adherencia observada y estado de disponibilidad predictiva.
- Semaforo de cuatro franjas.
- Pareto de restricciones duras no listas.
- Recomendaciones y persona a contactar resueltas en servidor.
- Drawer contextual de consulta y gestion.
- El POST existente de gestion, su CSRF, validacion, errores y respuesta canonica.
- Estados de carga, listo, parcial, vacio, insuficiente, offline, query invalida y error.
- Tabla semantica en desktop/tablet y tarjetas unicas en movil.
- Oscuro/claro, cinco viewports, zoom 200 por ciento, reduced motion y accesibilidad.
- Contratos PHP, Zod, pruebas puras, componentes y navegador totalmente interceptado.
- Convivencia con los endpoints piloto, corte, rollback y retiro de `ct-app/`.

### Fuera de alcance

- Todo `/admin/`.
- Cambiar RLS, runtime boundary, ProjectScope, grants, usuarios, credenciales, schema, vistas SQL,
  tablas, columnas, indices, triggers, datos o seeds.
- Ejecutar DDL/DML, aun dentro de una transaccion o rollback.
- Cambiar miembros, roles, aliases, permisos o la bandera global del preview BI.
- Ampliar o reducir `canEditConstraints`.
- Editar actividades, restricciones dinamicas del look-ahead o porcentajes de S07.
- Crear, cerrar o persistir acciones en `bi_action_queue`.
- Inventar dias de atraso del proyecto a partir de una cadena de actividades.
- Inventar una prediccion de incumplimiento sin fuente/modelo aprobado.
- Convertir la adherencia observada en certeza de resultado futuro.
- Mantener el umbral cliente `UMBRAL_SIN_ANALISIS_PCT=30` como regla de producto no aprobada.
- Aceptar HTML del backend en el DOM.
- Crear un endpoint de detalle por restriccion.
- Crear un segundo endpoint GET React paralelo.
- Añadir paginacion remota, libreria de tablas, graficos, estado o formularios.
- Eliminar rutas metricas o de linaje que consuman otras hojas.
- Eliminar piezas BI compartidas antes del gate conjunto T03/S17–S24.
- Regenerar goldens sin aprobacion explicita.

## Punto de partida medido

### React principal

- No existe pagina, modulo, schema, gateway ni ruta React principal de BI Intermedia.
- El frontend contiene shell, sesion, selector de proyecto, sidebar y tema.
- T03 y S17–S19 son specs/planes en este worktree, no codigo ya disponible.
- Ningun componente de `frontend/src` debe importar codigo de `ct-app/`.

### Tres implementaciones actuales

Hoy conviven tres lecturas:

1. `views/bi/control-tower.php` + `public/js/modules/bi-spa.js`, default cuando
   `CT_PILOTO` no vale `1`;
2. `views/bi/control-tower-piloto.php` + el bundle `public/ct-app/assets/ct.js`, servido por URL
   directa cuando la bandera esta activa;
3. `ct-app/`, una aplicacion React/TypeScript separada con su propio Vite, cliente HTTP, tokens,
   tema y suite.

El contenedor auditado tiene `CT_PILOTO` sin definir. La experiencia servida por defecto sigue
siendo legacy.

### Legacy BI

`renderIntermedia()` recibe tres filas de scorecard:

- Restricciones no listas;
- porcentaje de restricciones listas;
- total de restricciones duras.

La vista:

- copia cada valor tanto en Programado como en Ejecutado;
- repite el mismo porcentaje como badge;
- dibuja esas tres magnitudes de unidades incompatibles en una sola grafica de barras;
- no muestra lista, alarma, semaforo, Pareto, gestion ni linaje detallado.

No se conserva esa tabla como verdad semantica. El adaptador legacy puede mantener su payload
mientras tenga consumidores, pero React rotula cada magnitud con su unidad real y no compara
conteos con porcentajes en un mismo eje.

### Isla ct-app

La isla tiene:

- `Intermedia.tsx`;
- `AlarmaHuerfanas`;
- `Titular`;
- `ListaRestricciones`;
- `PanelGestion`;
- `Semaforo`;
- `Pareto`;
- `Linaje`;
- `ToggleTema`;
- helpers `titulares`, `urgencia` y `accionSugerida`.

Brechas medidas:

- `Linaje` existe pero no se monta en `Intermedia`;
- el orden implementado es alarma, titular, lista, aunque CT-18.3 resolvio lista antes del titular;
- la lista usa filas `div` en todos los anchos;
- el panel es contenido inline, no drawer con trap de foco;
- el filtro Ver huerfanas es unidireccional y solo se limpia recargando;
- no existe proteccion uniforme contra respuestas obsoletas;
- no existe un estado canonico parcial/insuficiente;
- el headline decide en cliente con un umbral 30 no aprobado;
- la accion sugerida y el contacto se deciden en cliente;
- el tema usa la clave local `ct-piloto-theme`;
- el CSS introduce tokens `--ct-*`, colores literales y estilos inline;
- el cliente llama `fetch` directamente y duplica interfaces a mano;
- el POST se aplica localmente desde el request y no desde la fila fresca del servidor;
- cada bloque carga de manera independiente y puede representar cortes distintos.

La prueba local del paquete no fue reproducible en esta auditoria: el worktree no tiene las
dependencias de `ct-app`; `npm test` termino con `vitest: command not found` y
`npx tsc --noEmit` no encontro un compilador TypeScript utilizable. No se instalaron dependencias
ni se genero build. El plan historico registra 155 pruebas, pero esa afirmacion no sustituye
evidencia reproducible de la futura implementacion.

### Rutas actuales

| Verbo | Ruta | Uso |
|---|---|---|
| GET | `/bi/intermedia` | vista legacy o piloto |
| GET | `/api/bi/report/intermedia` | brief legacy con scope BI |
| GET | `/api/bi/projects` | selector compartido |
| GET | `/api/bi/weeks` | semanas |
| GET | `/api/bi/filter-options` | opciones de filtros |
| GET | `/api/bi/control-tower/restricciones` | lista piloto, proyecto de sesion |
| GET | `/api/bi/control-tower/restricciones/pareto` | Pareto piloto, proyecto/semana de sesion |
| GET | `/api/bi/control-tower/metricas/{metricKey}` | adherencia y cuatro franjas |
| GET | `/api/bi/lineage?metric_key=...` | linaje por metrica |
| POST | `/api/bi/control-tower/restricciones/{id}/gestion` | gestion piloto |

La isla hace al menos siete GET independientes:

- lista;
- adherencia;
- cuatro franjas;
- Pareto;
- y otro GET por metrica si se montara linaje.

El report soporta scope BI y filtros; lista/Pareto/metricas operan con el proyecto activo y, en
varios casos, con la semana de sesion. El piloto no envia el scope y periodo a todos. No existe una
garantia de corte atomico.

### Acceso y capacidades

El comportamiento vigente es:

- `BiPreviewAccessPolicy`: A entra; D/R dependen de la bandera global
  `bi.control_tower.visible`; otros roles reciben 404;
- `BiProjectScope`: cada proyecto de lectura exige membresia visible y
  `lps.indicadores.ver`; un scope no autorizado produce 403;
- `canEditConstraints`: A, D, R, DCV, S, G, SG y OT;
- el POST resuelve el rol real del proyecto activo;
- un rol sin capacidad de escritura recibe 403;
- un ID inexistente o perteneciente a otro proyecto recibe el mismo 404.

La interseccion actual implica que todo A/D/R admitido a la hoja tambien tiene
`canEditConstraints`. React no codifica esa coincidencia: recibe
`canManageConstraints` del servidor. Si los catalogos cambian en el futuro, la hoja puede quedar
solo lectura sin otra modificacion.

Brecha del piloto: la vista valida el preview global, mientras sus GET especializados vuelven a
validar el rol del proyecto activo. Un usuario privilegiado en otra obra puede recibir el shell y
luego 404 en todos los datos. T03 debe aplicar un unico gate de hoja antes de montar React.

### Payload report servido

Una lectura read-only de `ControlTowerService::getBrief` para proyecto 73, semana 1 produjo:

- `respuesta`, `project_ids`, `project_id`, `semana`, `report_key`, `role`,
  `filters`;
- `data_source`, `raw_row_count`;
- `executive_brief`, `scorecard`, `charts`, `drivers`, `risks`,
  `recommended_actions`, `lineage`;
- 1.648 filas crudas;
- 1.343 restricciones no listas;
- 5 por ciento de restricciones listas;
- 1.410 restricciones duras;
- cinco drivers, diez riesgos, cinco acciones y dos entradas de linaje.

La fuente declarada fue `bi_pi_restricciones`, con grano
`project_id + Semana + unique_id + restriction_type`.

El brief y las acciones contienen fragmentos HTML `<b>`. React no los interpreta. El presenter
target entrega texto plano o campos estructurados.

### Lista piloto

El GET actual devuelve trece campos por restriccion:

- id;
- restriccion;
- semana;
- actividadBloqueada;
- responsableAsignado;
- fechaCompromiso;
- estadoLiberacion;
- asignadoPor;
- asignadoEn;
- diasVencida;
- semanaInicioActividadBloqueada;
- actividadesEncadenadas;
- tocaRutaCritica.

La fuente base es `pi_shared_constraints`; la cadena usa
`pi_shared_constraint_links + programa_consolidado`, siempre por `project_id`.

Brechas:

- omite `Nota`, fechas de creacion/actualizacion y el conjunto completo de actividades ligadas;
- el nombre de actividad es solo un representante determinista;
- el conteo usa links distintos y puede ser mayor a uno;
- no explica como afecta la entrega;
- no comparte corte/filtros con el report;
- no expone clave global estable; `Id` solo es unico dentro de proyecto.

### Gestion piloto

El POST acepta:

    {
      "responsable": "texto no vacio",
      "fechaCompromiso": "YYYY-MM-DD real",
      "estado": "sin_gestionar|en_gestion|liberada|no_aplica"
    }

Contrato vigente:

- CSRF con form key `ct_piloto`;
- responsable obligatorio y recortado;
- fecha calendario real; una fecha pasada es valida;
- estado en el enum exacto;
- solo escribe `ResponsableAsignado`, `FechaCompromiso`, `EstadoLiberacion`,
  `AsignadoPor` y `AsignadoEn`;
- usa prepared statement y `project_id`;
- lee y responde una fila fresca.

No existe endpoint de responsables para esta accion ni contrato para un ID de usuario. S20
conserva responsable como texto; no inventa un catalogo.

### Regla de huerfana y vencimiento

La isla considera huerfana:

    estadoLiberacion === "sin_gestionar" && responsableAsignado === null

La definicion de producto habla de una restriccion sin analisis/dueno y la interfaz pide responsable
y fecha. Para eliminar ambiguedad, la regla canonica S20 es:

    estado = sin_gestionar
    AND responsable vacio
    AND fecha de compromiso vacia

El servidor devuelve `isOrphan` y evidencia de los tres predicados. React no la recalcula.

Vencida conserva la regla medida:

- fecha anterior a hoy Bogota: entero positivo de dias;
- hoy, fecha futura o sin fecha: `null`;
- `null` no significa cero dias.

### Orden N4

La isla ordena:

1. actividad sin semana de inicio;
2. menor semana de inicio;
3. mayor numero de actividades encadenadas;
4. ruta critica primero;
5. orden estable.

S20 conserva ese orden como N4, pero el servidor devuelve `priorityRank` y
`priorityEvidence`. La UI no replica comparadores.

### Semaforo

Las cuatro metricas actuales son:

- `pi_semaforo_semana_0`;
- `pi_semaforo_semana_1_2`;
- `pi_semaforo_semana_3_4`;
- `pi_semaforo_semana_5_6`.

Cada una observa actividades incompletas `Titulo=0` en la franja, y devuelve tasa lista con
`basis.filas_usadas`. La isla redondea `tasa * filas` para inferir listas.

La semantica aprobada queda en servidor:

| Franja | Pendientes | Estado |
|---|---:|---|
| cualquiera | denominador insuficiente | neutral/insufficient |
| cualquiera | 0 | healthy |
| semana 0 | >0 | urgent |
| semanas 1–2 | >0 | attention |
| semanas 3–4 | >0 | attention |
| semanas 5–6 | >0 | neutral |

El snapshot entrega conteo total, listas, pendientes, tasa y estado ya reconciliados. React no
redondea ni clasifica.

### Pareto

La fuente actual filtra `bi_pi_restricciones` por:

- proyecto;
- semana;
- `is_ready=0`;
- `is_hard=1`;
- agrupacion por `restriction_type`;
- conteo descendente.

No existe un diccionario gobernado de etiquetas. El contrato target devuelve:

- `code` crudo;
- `label` opcional desde un diccionario explicito y probado;
- `count`;
- porcentaje sobre el total;
- orden;
- denominador y corte.

Hasta que un codigo tenga etiqueta gobernada, se muestra el codigo legible y no una traduccion
inventada. Un codigo desconocido nunca desaparece.

### Titular, recomendacion y prediccion

La prioridad cliente actual es huerfana, vencida, adherencia insuficiente, adherencia baja, saludable
y neutral. La bifurcacion baja/saludable depende del 30 por ciento hardcoded.

S20 elimina ese juicio no aprobado:

1. huerfanas, si hay;
2. vencidas, si no hay huerfanas;
3. dato insuficiente;
4. en otro caso, titular factual de adherencia observada con numerador y denominador.

El servidor devuelve `headline.kind`, texto, variables y evidencia. No incluye HTML.

La adherencia observada es un hecho. Una señal predictiva solo puede aparecer si existe un modelo
aprobado, versionado y con confianza/limitaciones. En el estado actual:

- `observedReadiness` puede ser cero valido;
- `predictiveSignal.status` es `unavailable` o `insufficient`;
- la UI explica la limitacion;
- no usa palabras como va a fallar o incumplira.

### Responsive, tema y accesibilidad actuales

- La isla no tiene tabla semantica.
- El panel no gestiona foco ni Escape.
- Hay estilos inline y colores literales.
- El toggle local duplica el tema global.
- El manifiesto solo conserva un golden oscuro vacio 1180x820.
- El e2e actual siembra y borra filas, por lo que no puede ejecutarse en este programa sin violar
  el limite de datos.

## Decisiones de producto y comportamiento target

### Orden del lienzo

El orden definitivo es:

1. contexto, filtros y estado del corte;
2. alarma de huerfanas;
3. lista accionable;
4. titular factual;
5. semaforo 0–6;
6. Pareto;
7. limitaciones y acceso a linaje.

La resolucion CT-18.3 de lista sobre titular prevalece sobre el orden anterior CT-8.3 y sobre la
isla implementada.

### Alcance y periodo coherentes

Un solo snapshot autoriza y resuelve:

- proyectos;
- corte por proyecto;
- semana o rango;
- sub;
- resp;
- etapa;
- capacidades;
- cobertura.

Cada bloque declara los filtros que pudo aplicar. Si una fuente no puede honrar el scope:

- no cae silenciosamente a la semana o proyecto de sesion;
- devuelve `status=insufficient` o `not_applicable`;
- publica la limitacion;
- no mezcla esa cifra con bloques filtrados.

En proyecto unico, la gestion esta disponible segun capacidad. En multiproyecto:

- la lectura conserva breakdown por obra;
- no suma IDs locales sin `projectId`;
- la lista muestra obra;
- `canManageConstraints=false`;
- el drawer explica que se debe seleccionar exactamente una obra para gestionar.

### Lista y filtros de presentacion

El snapshot contiene la lista autorizada completa del corte. La UI ofrece filtros locales, puros y
reversibles:

- busqueda por restriccion, actividad o responsable;
- todas, huerfanas o vencidas;
- estado de gestion;
- tipo de restriccion;
- ruta critica;
- proyecto en scope multiple.

Los filtros:

- no cambian denominadores de semaforo, Pareto o titular;
- muestran `visibles / total`;
- tienen Limpiar filtros;
- se reflejan en texto accesible;
- preservan el orden N4;
- no persisten como autoridad ni se envian al POST.

La lista muestra inicialmente 25 filas y permite Mostrar mas de forma local. Busqueda/filtros
operan sobre toda la lista recibida, no solo sobre las 25 visibles.

### Cadena e impacto

Cada fila canonica incluye:

- `key = projectId:id`;
- proyecto;
- restriccion, nota y tipo;
- estado;
- responsable y fecha;
- atraso;
- actividad bloqueada representativa;
- resumen y conjunto de actividades enlazadas;
- semana minima;
- ruta critica;
- rank/evidencia;
- recomendacion y contacto;
- auditoria.

El drawer enumera las actividades enlazadas disponibles. Si el sistema solo puede afirmar que una
actividad queda bloqueada o que la cadena toca ruta critica, lo dice asi. No transforma cantidad,
semana o ruta critica en dias de atraso final sin un modelo aprobado.

### Gestion

La accion Gestionar abre el drawer compartido:

- escritorio/tablet: panel lateral;
- movil: superficie de pantalla completa;
- detalle y formulario viven en el mismo contexto;
- cerrar con boton o Escape;
- foco inicial, trap y retorno al disparador;
- al cambiar de fila con cambios sin guardar, se pide confirmar descarte.

El formulario conserva el contrato visible:

- responsable texto obligatorio, trim, limite 120;
- fecha ISO desde un control accesible, fecha pasada permitida;
- estado enum exacto;
- campos no autorizados no existen;
- Cancelar no muta;
- Guardar se deshabilita durante una unica peticion;
- mensajes 422 se asocian al campo cuando sea posible;
- 403 de capacidad no se presenta como fallo de red;
- 403 CSRF pide recargar;
- 404 informa que la fila ya no esta disponible;
- offline conserva valores para reintentar.

Despues de un 200:

1. React valida la fila fresca del POST;
2. anuncia Guardado;
3. solicita de nuevo el snapshot canonico;
4. reemplaza lista, titular, semaforo, Pareto, conteos y auditoria desde servidor;
5. no recalcula indicadores a partir del request.

Si el POST confirma pero falla la recarga, conserva la fila fresca, marca
`saved_refresh_pending` y ofrece Recargar resumen. No vuelve a enviar el POST automaticamente.

### Recarga

Recargar:

- repite solo el GET canonico;
- conserva query T03 y filtros locales compatibles;
- no hace POST;
- aborta la lectura anterior;
- mantiene contenido previo con indicador de actualizacion;
- anuncia resultado sin mover foco.

No se añade exportacion: ni el legacy BI ni el piloto la ofrecen para esta hoja.

### Linaje

El snapshot incluye referencias para:

- `pi_hard_restrictions_ready_rate`;
- `pi_restriction_pareto`;
- cuatro franjas del semaforo;
- lista de restricciones compartidas;
- cadena de links;
- regla de huerfana;
- orden N4;
- recomendacion.

El drawer T03 abre el linaje sin GET por fila. Declara fuente, grano, formula, filtros, corte,
numerador, denominador, filas usadas, cobertura y limitaciones.

## Contrato HTTP target

### Lectura canonica

Se conserva:

    GET /api/bi/report/intermedia

Query T03:

- `project_ids`: enteros positivos repetidos o CSV durante compatibilidad;
- `project_id`: alias simple durante compatibilidad;
- `semana`: entero positivo en scope simple;
- `desde/hasta`: fechas ISO para rango/multi;
- `sub/resp/etapa`: strings recortados con limite;
- aliases legacy exclusivamente en el adapter.

Se rechaza:

- IDs no positivos;
- proyectos no autorizados;
- fechas imposibles;
- desde mayor a hasta;
- semana con rango incompatible;
- arrays inesperados;
- claves de autoridad como `role`, `permiso`, `db`, `dbName`, `prefix`, `user`,
  `username`, `capability` o `canManageConstraints`.

No se crea otro GET React. Los endpoints lista/Pareto/metrica/linaje permanecen temporalmente para
la isla y otros consumidores, pero la pagina React principal no los llama.

### Exito de lectura

Forma conceptual:

    {
      "ok": true,
      "data": {
        "reportKey": "intermedia",
        "scope": {
          "mode": "single|multi",
          "projects": [],
          "effectiveCutoffs": [],
          "week": 6,
          "range": null,
          "filters": {},
          "isFiltered": false
        },
        "capabilities": {
          "canView": true,
          "canManageConstraints": true
        },
        "coverage": {},
        "alarm": {
          "orphanCount": 0,
          "status": "clear|active|insufficient",
          "rule": {}
        },
        "counts": {},
        "constraints": [],
        "headline": {},
        "observedReadiness": {},
        "predictiveSignal": {},
        "trafficLight": [],
        "pareto": {},
        "limitations": [],
        "lineage": []
      },
      "meta": {
        "requestId": "...",
        "generatedAt": "...",
        "schemaVersion": 1
      }
    }

Invariantes:

- `reportKey=intermedia`;
- proyecto unico devuelve una obra; multi conserva todas;
- cada restriccion lleva `projectId` y clave compuesta;
- `counts.total = constraints.length`;
- huerfanas y vencidas reconcilian contra flags de filas;
- traffic light contiene exactamente cuatro franjas ordenadas;
- Pareto reconcilia con su denominador;
- todo porcentaje tiene numerador/denominador;
- cero valido se diferencia de `null`;
- todo texto es plano;
- capacidades y hrefs vienen del servidor;
- una seccion insuficiente no invalida secciones coherentes.

### Escritura canonica

Se conserva:

    POST /api/bi/control-tower/restricciones/{id}/gestion

Headers:

    Content-Type: application/json
    Accept: application/json
    X-AIA-Expect-Json: 1
    X-CSRF-Token: <token de T01 para ct_piloto durante convivencia>

Body:

    {
      "responsable": "María Pérez",
      "fechaCompromiso": "2026-09-04",
      "estado": "en_gestion"
    }

La ruta no acepta `projectId` como autoridad. El proyecto sale de la sesion activa. En modo
multiple el cliente no presenta el formulario.

Exito target:

    {
      "ok": true,
      "data": {
        "constraint": {},
        "savedAt": "...",
        "savedBy": "..."
      },
      "meta": {
        "requestId": "...",
        "schemaVersion": 1
      }
    }

El presenter de compatibilidad puede mantener `restriccion` en la forma antigua mientras
`ct-app` exista. La fuente es la misma fila fresca, no dos lecturas divergentes.

### Errores

| HTTP | code | Uso |
|---:|---|---|
| 400 | BAD_REQUEST | JSON o forma no valida |
| 401 | UNAUTHENTICATED | sesion ausente/expirada |
| 403 | FORBIDDEN | proyecto o capacidad de gestion denegada |
| 403 | CSRF_INVALID | token ausente/expirado |
| 404 | NOT_FOUND | hoja oculta o restriccion fuera de proyecto/inexistente |
| 409 | STALE_CONTEXT | proyecto de sesion cambio durante la accion |
| 422 | VALIDATION_ERROR | query/body valido en forma pero no en reglas |
| 429 | RATE_LIMITED | si el middleware vigente lo produce |
| 500 | INTERNAL_ERROR | fallo no recuperable sin internals |
| 503 | TEMPORARILY_UNAVAILABLE | dependencia temporal |

No se incorpora control de concurrencia basado en una columna nueva. `STALE_CONTEXT` protege el
proyecto de sesion, no simula versionado de fila.

## Arquitectura target

### Backend

Una fachada de lectura compone seams puros:

    BiIntermediaReadService
      -> BiSheetAccessPolicy / BiProjectScope
      -> BiQueryParser
      -> IntermediaRestrictionReader
      -> IntermediaRestrictionProjector
      -> IntermediaUrgencyPolicy
      -> IntermediaTrafficLightBuilder
      -> IntermediaParetoBuilder
      -> IntermediaHeadline
      -> ActionRecommendationService adapter
      -> LineageService
      -> BiIntermediaPresenter

Principios:

- consultas siempre por `project_id`;
- parametros preparados;
- sin SQL dinamico nuevo por tabla/prefix;
- corte y filtros se pasan explicitamente;
- un reloj Bogota inyectable calcula atraso;
- reglas de huerfana, vencimiento, N4, semaforo y titular son puras;
- action/contact se proyectan como texto plano;
- un read model produce canonical y legacy;
- el endpoint no expone excepciones, SQL ni rutas fisicas.

La escritura se separa en:

    BiConstraintWriteController
      -> BiConstraintManagementRequest
      -> BiConstraintManagementPolicy
      -> BiConstraintManagementRepository
      -> BiConstraintPresenter

No se requiere tabla, columna ni migracion.

### Frontend

Estructura propuesta:

    frontend/src/lib/api/esquemas/biIntermedia.ts
    frontend/src/lib/api/biIntermedia.ts
    frontend/src/modulos/bi/IntermediaPagina.tsx
    frontend/src/modulos/bi/intermedia/
      AlarmaHuerfanas.tsx
      FiltrosRestricciones.tsx
      ListaRestricciones.tsx
      TablaRestricciones.tsx
      TarjetasRestricciones.tsx
      TarjetaRestriccion.tsx
      TitularIntermedia.tsx
      SemaforoIntermedia.tsx
      ParetoRestricciones.tsx
      DetalleRestriccion.tsx
      FormularioGestionRestriccion.tsx
      ResumenDisponibilidad.tsx
      filtrarRestricciones.ts
      estadoIntermedia.ts
      useBiIntermedia.ts

Responsabilidades:

- `biIntermedia.ts` llama exclusivamente a `cliente.ts`;
- Zod valida GET, POST y errores;
- `useBiIntermedia` maneja request, abort, cache key, refresh y save;
- filtros locales son funciones puras;
- tabla y tarjetas reciben el mismo modelo;
- el formulario existe una vez dentro del drawer;
- los componentes no deciden permisos, umbrales, rank, status o recomendaciones;
- ninguna pieza usa `dangerouslySetInnerHTML`.

### Estado cliente

Estados:

- `idle`;
- `loading`;
- `ready`;
- `refreshing`;
- `partial`;
- `empty`;
- `insufficient`;
- `offline`;
- `invalid_query`;
- `error`;
- `saving`;
- `save_error`;
- `saved_refresh_pending`.

Reglas:

- un cambio de query aborta el request anterior;
- una respuesta obsoleta no reemplaza el scope actual;
- cache key incluye usuario, proyectos, periodo y filtros;
- no se comparte cache entre sesiones/proyectos;
- refresh conserva datos coherentes anteriores;
- save no se reintenta automaticamente;
- cambiar proyecto cierra el drawer y descarta cache no autorizada.

## Diseño responsive y accesibilidad

### Estructura

- `>=768px`: una tabla semantica con encabezados visibles.
- `<768px`: una lista de tarjetas; la tabla no se monta.
- Nunca se montan ambas representaciones para ocultar una por CSS.
- El orden de lectura sigue el orden visual.
- El drawer es lateral en desktop/tablet y pantalla completa en movil.

Columnas desktop/tablet:

- prioridad;
- restriccion;
- actividad/cadena;
- responsable;
- compromiso/atraso;
- estado;
- ruta critica;
- accion.

La tablet puede compactar auditoria al drawer, pero no oculta restriccion, actividad, responsable,
fecha, estado ni accion.

### Movil

La cara visible muestra:

- proyecto si multi;
- restriccion;
- actividad bloqueada;
- estado;
- responsable;
- fecha/atraso;
- rank o señal de criticidad en texto;
- Gestionar o Ver detalle.

El detalle expandido muestra tipo, cadena, nota, auditoria, recomendacion y linaje. La edicion vive
en el drawer full-screen, no dentro de multiples tarjetas.

### Accesibilidad

- un unico `h1`;
- region de contexto y filtros con nombres accesibles;
- alarma con texto, no solo color;
- tabla con `caption`, `th scope` y celdas asociadas;
- tarjetas como lista semantica;
- botones reales para filtro, detalle, mostrar mas, recarga y gestion;
- causa completa disponible, sin truncamiento irrecuperable;
- estados con texto e icono;
- `aria-live` moderado para conteos, carga y guardado;
- error de campo asociado mediante `aria-describedby`;
- foco visible por tokens;
- drawer con foco inicial, trap, Escape y retorno;
- blancos tactiles minimos de 44x44 en movil;
- graficas Pareto/semaforo con alternativa textual visible;
- movimiento reducido elimina transiciones no esenciales;
- zoom 200 por ciento conserva lectura, filtros y gestion.

### Tema y tokens

- oscuro es default/fallback;
- claro tiene la misma informacion, jerarquia y acciones;
- la preferencia viene de T01;
- se elimina el toggle y storage `ct-piloto-theme`;
- solo `public/css/tokens.css` y primitivas compartidas;
- sin hex/rgb/hsl, `!important`, filtros de color o tokens `--ct-*`;
- no se usa color como unica evidencia;
- estados y graficas cumplen contraste en ambos temas.

Viewports:

- 390x844;
- 480x900;
- 768x1024;
- 1180x820;
- 1440x900.

Ninguno admite overflow horizontal de pagina.

## Seguridad, permisos y limite RLS

S20 no modifica RLS. La frontera vigente se consume, no se redefine.

- La sesion y el gate BI se validan antes de montar la pagina.
- Cada proyecto se reautoriza en servidor.
- `project_id` se aplica a lista, links, actividades y escritura.
- El ID de restriccion nunca es global sin proyecto.
- Multi es lectura; gestionar exige el proyecto unico activo.
- Un ID ajeno e inexistente produce el mismo 404.
- El cliente no manda ni decide rol/capacidad/proyecto de escritura.
- CSRF es obligatorio.
- No hay HTML confiable desde drivers/actions.
- Los errores no filtran SQL, tablas, archivos, stack ni IDs ajenos.
- No se cambia `docs/security/rls-runtime-boundary.md`.
- No se ejecuta DDL/DML durante especificacion, plan ni pruebas documentales.

## Convivencia, corte y retiro

### Fase 1 — caracterizacion

- congelar respuestas legacy y piloto con fixtures estaticos;
- probar formulas/reglas puras sin MySQL;
- contar callers de endpoints y assets;
- mantener ruta legacy default.

### Fase 2 — snapshot canonico

- enriquecer el GET report existente;
- preservar presenter legacy;
- estabilizar POST canonico y presenter piloto;
- no cambiar `CT_PILOTO`.

### Fase 3 — React principal

- montar S20 dentro de T01/T03;
- usar solo GET report y POST gestion;
- validar cinco viewports, oscuro/claro, permisos y estados;
- navegar desde S17 y hacia S07/S21.

### Fase 4 — corte

Solo cuando todos los criterios S20 esten verdes:

1. `/bi/intermedia` sirve la SPA principal sin consultar `CT_PILOTO`;
2. se elimina el salto de `bi-spa.js`;
3. se confirma cero caller React a lista/Pareto/metricas/linaje piloto;
4. se elimina la vista piloto, bootstrap y bundle;
5. se elimina `ct-app/` y su toolchain;
6. se elimina la lectura de `CT_PILOTO`;
7. los endpoints compartidos se conservan si otra hoja aun los usa;
8. el retiro del legacy BI compartido espera T03/S17–S24.

La bandera no es el mecanismo permanente de rollback. Despues del corte, rollback revierte
ruta/codigo al commit anterior; no toca datos.

## Estrategia de pruebas

### PHP sin base

- parser de query;
- acceso permitido/oculto/proyecto denegado;
- regla de huerfana;
- vencimiento con reloj fijo Bogota;
- orden N4;
- proyeccion de cadena;
- cuatro franjas y severidad;
- Pareto y denominador;
- titular sin umbral 30;
- señal predictiva unavailable/insufficient;
- texto plano;
- capacidades single/multi;
- validacion del POST;
- proyecto ajeno 404;
- presenter canonical/legacy;
- rutas y ausencia de endpoint nuevo;
- invariantes de fuente/SQL por inspeccion/reflection.

Usar fakes, fixtures PHP estaticos, call logs y servicios puros. No usar MySQL, transacciones,
rollback, seeds ni fixtures mutables.

### Frontend

- schemas GET/POST/error;
- gateway y cliente unico;
- filtros y conteos;
- lista ordenada;
- tabla/cards exclusivas;
- alarma;
- titular factual;
- semaforo;
- Pareto;
- detalle;
- drawer;
- formulario y validacion;
- save/refetch;
- saved_refresh_pending;
- estados parciales;
- stale request/cache;
- dark/light y ausencia de tema local.

### Navegador interceptado

Antes de navegar:

- interceptar session, projects, weeks, filter-options y report;
- fallar ante cualquier request inesperado;
- en lectura, fallar ante cualquier verbo de mutacion;
- en prueba de save, permitir exactamente un POST a la ruta/ID esperados;
- verificar header CSRF y body exacto;
- responder fila fresca y luego snapshot actualizado;
- nunca dejar llegar la peticion al servidor/base.

Escenarios:

- A permitido;
- D/R segun gate;
- rol oculto 404;
- proyecto no autorizado 403;
- single gestion;
- multi solo lectura;
- sin huerfanas;
- con huerfanas/vencidas;
- lista vacia;
- bloque parcial;
- predictive unavailable;
- save 200/422/403-CSRF/404/offline;
- oscuro/claro;
- cinco viewports;
- teclado, foco, Escape, zoom y axe;
- consola y red limpias.

No se ejecuta `tests/browser/ct-intermedia.spec.mjs` vigente como evidencia porque siembra y borra
filas. Primero se sustituye por cobertura interceptada.

## Criterios de aceptacion

1. S20-AC-01: el documento excluye admin, RLS, schema, permisos, datos, DDL/DML y deploy.
2. S20-AC-02: `/bi/intermedia` es una ruta de la SPA principal al corte.
3. S20-AC-03: S20 reutiliza T01/T03 y no duplica shell, query, tema, drawer ni cliente.
4. S20-AC-04: la isla `ct-app` no se importa dentro de `frontend/src`.
5. S20-AC-05: A/D/R entran segun gate BI y `lps.indicadores.ver`.
6. S20-AC-06: roles ocultos reciben 404.
7. S20-AC-07: un proyecto no autorizado recibe 403 sin datos.
8. S20-AC-08: role/permiso/project/capability cliente no conceden autoridad.
9. S20-AC-09: el proyecto activo autorizado es default.
10. S20-AC-10: multi requiere seleccion explicita y conserva breakdown por obra.
11. S20-AC-11: multi es solo lectura y explica seleccionar una obra para gestionar.
12. S20-AC-12: semana/rango/sub/resp/etapa se aplican a un snapshot coherente.
13. S20-AC-13: cada bloque declara corte, filtros aplicados y limitaciones.
14. S20-AC-14: una fuente incompatible queda insufficient/not_applicable y no cae a sesion.
15. S20-AC-15: el GET report existente es el unico GET de la pagina React.
16. S20-AC-16: el GET tiene prueba de contrato PHP y schema Zod.
17. S20-AC-17: no se crea endpoint paralelo de detalle o lectura.
18. S20-AC-18: aliases legacy viven solo en el adapter y authority-like keys se rechazan.
19. S20-AC-19: la respuesta canonica contiene scope, capabilities, coverage y meta.
20. S20-AC-20: cada restriccion usa clave compuesta projectId:id.
21. S20-AC-21: la fila conserva restriccion, nota, tipo, estado, responsable, fecha y auditoria.
22. S20-AC-22: la fila conserva actividad representativa y todas las ligadas disponibles.
23. S20-AC-23: cadena, conteo, semana minima y ruta critica reconcilian.
24. S20-AC-24: no se inventan dias de impacto final del proyecto.
25. S20-AC-25: huerfana exige sin_gestionar, sin responsable y sin fecha.
26. S20-AC-26: el servidor devuelve isOrphan y evidencia; React no recalcula.
27. S20-AC-27: vencida exige fecha anterior a hoy Bogota.
28. S20-AC-28: hoy/futuro/sin fecha produce diasVencida null, no cero.
29. S20-AC-29: N4 ordena sin semana, semana ascendente, cadena descendente, critica y estable.
30. S20-AC-30: priorityRank/evidence vienen del servidor.
31. S20-AC-31: counts total/huerfanas/vencidas/por estado reconcilian con filas.
32. S20-AC-32: busqueda cubre restriccion, actividad y responsable.
33. S20-AC-33: filtros locales cubren huerfana/vencida/estado/tipo/critica/proyecto.
34. S20-AC-34: filtros muestran visibles/total, preservan N4 y se pueden limpiar.
35. S20-AC-35: Mostrar mas opera despues de filtrar toda la lista recibida.
36. S20-AC-36: filtros locales no cambian denominadores BI ni viajan al POST.
37. S20-AC-37: el orden del lienzo es contexto, alarma, lista, titular, semaforo, Pareto, linaje.
38. S20-AC-38: la lista aparece por encima del titular.
39. S20-AC-39: alarma activa muestra conteo y regla; cero muestra estado claro.
40. S20-AC-40: el titular viene del servidor como texto plano, variables y evidencia.
41. S20-AC-41: no existe umbral cliente 30 para juzgar adherencia baja/sana.
42. S20-AC-42: prioridad de titular es huerfanas, vencidas, insufficient y hecho observado.
43. S20-AC-43: adherencia observada distingue cero valido de ausencia.
44. S20-AC-44: señal predictiva sin modelo aprobado es unavailable/insufficient.
45. S20-AC-45: la UI no convierte observacion en promesa de incumplimiento.
46. S20-AC-46: scorecard no duplica una cifra como Programado y Ejecutado.
47. S20-AC-47: semaforo contiene exactamente 0, 1–2, 3–4 y 5–6.
48. S20-AC-48: cada franja trae total/listas/pendientes/tasa/estado reconciliados.
49. S20-AC-49: denominador insuficiente es neutral/insufficient.
50. S20-AC-50: cero pendientes es healthy.
51. S20-AC-51: pendiente en semana 0 es urgent.
52. S20-AC-52: pendientes 1–2 y 3–4 son attention.
53. S20-AC-53: pendientes 5–6 son neutral.
54. S20-AC-54: React no redondea conteos ni clasifica severidad.
55. S20-AC-55: Pareto filtra duras no listas por proyecto/corte y ordena conteo descendente.
56. S20-AC-56: Pareto devuelve codigo, etiqueta opcional, conteo, porcentaje y denominador.
57. S20-AC-57: codigo desconocido se muestra y nunca se descarta/traduce inventadamente.
58. S20-AC-58: recomendaciones y contacto vienen del servidor.
59. S20-AC-59: risks/actions se normalizan a texto y no se usa dangerouslySetInnerHTML.
60. S20-AC-60: cada fila abre detalle sin otro GET.
61. S20-AC-61: detalle expone cadena, nota, auditoria, recomendacion y linaje.
62. S20-AC-62: drawer cumple foco inicial, trap, Escape y retorno.
63. S20-AC-63: cambios sin guardar se protegen al cerrar/cambiar fila.
64. S20-AC-64: canManageConstraints viene del servidor.
65. S20-AC-65: la UI no infiere permiso por el rol.
66. S20-AC-66: gestion solo aparece en scope single autorizado.
67. S20-AC-67: se conserva el POST existente y no se crea otra mutacion.
68. S20-AC-68: POST exige CSRF y no acepta projectId como autoridad.
69. S20-AC-69: responsable es texto obligatorio recortado con limite 120.
70. S20-AC-70: fecha exige YYYY-MM-DD real y permite pasado.
71. S20-AC-71: estado acepta solo sin_gestionar/en_gestion/liberada/no_aplica.
72. S20-AC-72: solo responsable/fecha/estado y auditoria server-side se escriben.
73. S20-AC-73: rol sin capacidad recibe 403.
74. S20-AC-74: ID ajeno e inexistente reciben el mismo 404.
75. S20-AC-75: respuesta POST es fila fresca validada por Zod.
76. S20-AC-76: React no aplica optimisticamente el request como fila guardada.
77. S20-AC-77: save exitoso refresca el snapshot y no recalcula indicadores cliente.
78. S20-AC-78: save confirmado + refresh fallido produce saved_refresh_pending sin reenviar POST.
79. S20-AC-79: 422/CSRF/forbidden/not-found/offline tienen mensajes y recuperacion especificos.
80. S20-AC-80: Recargar solo hace GET, conserva query/filtros y no mueve foco.
81. S20-AC-81: >=768 monta tabla y <768 monta cards.
82. S20-AC-82: tabla y cards nunca se montan simultaneamente.
83. S20-AC-83: tabla es semantica; cards forman lista semantica.
84. S20-AC-84: edicion usa un unico formulario en drawer en todos los anchos.
85. S20-AC-85: movil muestra los siete datos operativos minimos y blancos 44x44.
86. S20-AC-86: causa/cadena completa es recuperable sin hover o truncamiento irreversible.
87. S20-AC-87: oscuro/claro tienen informacion y capacidades identicas.
88. S20-AC-88: tema usa T01/tokens.css y elimina toggle/storage/tokens ct locales.
89. S20-AC-89: no hay color literal, important, inline color ni libreria UI/chart.
90. S20-AC-90: cinco viewports y zoom 200 no tienen overflow horizontal de pagina.
91. S20-AC-91: teclado/touch/foco/reduced-motion cumplen y axe serious/critical es cero.
92. S20-AC-92: loading/ready/refreshing/partial/empty/insufficient/offline/invalid/error son visibles.
93. S20-AC-93: stale response se ignora y cambio de query aborta.
94. S20-AC-94: cache no cruza usuario/proyecto/periodo/filtros.
95. S20-AC-95: solo cliente.ts llama fetch y tipos derivan de z.infer.
96. S20-AC-96: linaje de metricas/lista/cadena/reglas/acciones viaja en el snapshot.
97. S20-AC-97: tests PHP nuevos usan fakes y cero MySQL/DDL/DML.
98. S20-AC-98: browser intercepta todo; save permite exactamente un POST esperado.
99. S20-AC-99: no se ejecuta el e2e DML ni se regeneran goldens sin aprobacion.
100. S20-AC-100: RLS/schema/grants/usuarios/credenciales/datos permanecen intactos.
101. S20-AC-101: CT_PILOTO se retira solo despues de paridad completa y corte verde.
102. S20-AC-102: ct-app/vista/bundle/tema se retiran solo con cero callers.
103. S20-AC-103: endpoints piloto compartidos permanecen mientras tengan consumidores.
104. S20-AC-104: legacy BI compartido espera el gate T03/S17–S24.
105. S20-AC-105: rollback es de ruta/codigo y no restaura datos.
106. S20-AC-106: la hoja declara reunion semanal y enlaza S07/S21 con scope preservado.
107. S20-AC-107: no se añade exportacion ni persistencia de acciones inexistente en legacy.
108. S20-AC-108: consola/red quedan limpias y ninguna peticion inesperada se tolera.

## Entregas verticales

### Entrega 1 — Acceso, query y snapshot

- gate de hoja;
- scope single/multi;
- periodo/filtros;
- GET canonico;
- presenter legacy;
- Zod;
- cobertura/linaje.

### Entrega 2 — Lista accionable

- proyeccion de restriccion/cadena;
- huerfana/vencimiento;
- N4;
- counts;
- filtros/busqueda;
- alarma y lista desktop/mobile.

### Entrega 3 — Lectura de reunion

- titular factual;
- observada/predictiva;
- semaforo;
- Pareto;
- recomendacion/contacto;
- detalle/linaje.

### Entrega 4 — Gestion

- policy/capability;
- request/response;
- drawer/formulario;
- save/refetch;
- errores;
- scope multi solo lectura.

### Entrega 5 — Calidad, corte y retiro

- oscuro/claro;
- viewports/zoom/a11y;
- browser interceptado;
- ruta;
- retiro CT_PILOTO/ct-app;
- convivencia y rollback.

## Riesgos y mitigaciones

| Riesgo | Mitigacion |
|---|---|
| siete GET representan semanas distintas | snapshot unico |
| lista usa proyecto de sesion silencioso | scope explicito |
| multi permite editar ID ambiguo | solo lectura |
| shell abre y APIs dan 404 | gate T03 antes de montar |
| huerfana cambia entre PHP/React | regla server unica |
| N4 diverge | rank/evidence server |
| cliente inventa juicio 30 por ciento | titular factual server |
| observada parece prediccion | dos bloques/estados separados |
| cadena parece atraso contractual | limitacion explicita |
| HTML se inyecta | texto estructurado |
| semaforo redondea distinto | conteos/status server |
| codigo Pareto se pierde | code siempre visible |
| POST deja KPI viejo | refetch snapshot |
| refetch reenvia escritura | estado saved_refresh_pending |
| rol se usa como permiso | capability server |
| ID ajeno filtra existencia | 404 indistinguible |
| dos themes/storage compiten | T01 unico |
| tabla/cards duplican controles | montaje exclusivo |
| e2e altera base | interception |
| flag queda permanente | corte con cero callers |
| retiro rompe otra hoja | gate T03 + censo |

## Decisiones descartadas

- Copiar `ct-app` dentro del frontend: conserva sus fronteras defectuosas.
- Mantener siete GET: no hay corte coherente.
- Crear un endpoint React nuevo: duplica contrato.
- Usar lista/Pareto piloto desde React principal: ignoran scope T03.
- Enviar projectId en POST: autoridad cliente.
- Gestionar multi eligiendo una obra arbitraria: riesgo de aislamiento.
- Mantener el umbral 30: no fue decision de producto.
- Inventar un predictor: no hay modelo aprobado.
- Inferir atraso final por longitud de cadena: formula inexistente.
- Recalcular N4, huerfana, semaforo o acciones en React: doble verdad.
- Interpretar HTML del brief: riesgo XSS y presentacion no tipada.
- Añadir catalogo de responsables: no existe contrato.
- Prohibir fecha pasada: cambia validacion observable.
- Añadir estados de gestion: cambia contrato.
- Hacer update optimista: la respuesta fresca y KPI pueden diferir.
- Reintentar POST tras red: duplica escritura.
- Dejar el panel inline: no cumple drawer/accesibilidad.
- Montar tabla y cards y ocultar una: DOM duplicado.
- Mantener toggle local: rompe tema global.
- Quitar todos los endpoints piloto con S20: otros consumidores pueden existir.
- Mantener `CT_PILOTO` como rollback permanente: deuda de producto.
- Ejecutar pruebas con rollback SQL: sigue siendo DML.

## Decisiones pendientes

Ninguna. Si la implementacion descubre un consumidor externo del payload piloto, un modelo
predictivo aprobado, un diccionario gobernado de tipos, una discrepancia entre CT-18.3 y una
decision posterior, o una seccion que no puede aplicar el scope sin cambiar schema, se detiene solo
ese tramo, se aporta evidencia y se enmienda esta spec. No se inventa contrato ni se toca RLS.

## Siguiente gate

Invocar `superpowers:writing-plans` para
`docs/superpowers/plans/2026-08-30-s20-bi-intermedia-react.md`, autorrevisarlo, actualizar el
atlas y continuar S21. No implementar S20 en esta sesion.
