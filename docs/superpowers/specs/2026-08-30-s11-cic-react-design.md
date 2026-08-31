---
capa: fuente
tipo: spec
estado: vigente
id: S11
fecha: 2026-08-30
superficie: cic
rutas: ["/programacion-semanal/cic"]
depende_de: [T01, S08, S09, S10]
views: [VIEW-36]
areas: [lps, design-system]
fuente: "auditoria de public/index.php, ProgramacionSemanalController::cic, CicApiController, RbacCatalog/RbacService, ProjectLandingService, SemanalApiController, verificarCICActualizada.php, ReportProcessor, VIEW-36, legacyCards.js, programacion-semanal.css, schema global, pruebas de CIC/Programacion Semanal y specs T01/S08/S09/S10 en shell-minimo-react, 2026-08-30"
resumen: "Migracion vertical S11 de Calificacion Integral de Contratistas a React: lecturas puras, proveedores persistidos o proyectados, cuestionarios completos por tipo y disciplina, cadencia cada ocho presencias, calculos actual/acumulado, guardado transaccional individual, permisos efectivos, tabla/tarjetas, oscuro/claro y corte strangler, sin cambiar RLS, schema, datos ni KPI BI durante la fase documental."
---

# S11 — Calificacion Integral de Contratistas en React

> **Estado:** diseño tecnico autorrevisado. No quedan decisiones de negocio, producto, estrategia o
> PM que bloqueen el plan. Esta spec no autoriza implementacion, commits, DDL/DML, cambios RLS,
> cambios de permisos, deploy, publicacion ni trabajo en `/admin/`. Su plan se escribe a
> continuacion con `superpowers:writing-plans`, conforme al programa aprobado de 27 specs y 27
> planes.

## Relacion con el programa

Esta spec continua las decisiones de:

- [[docs/superpowers/specs/2026-08-28-migracion-react-typescript-design|Migracion React + TypeScript]];
- [[docs/superpowers/specs/2026-08-28-paridad-shell-react-rls-design|Paridad del shell React y RLS]];
- [[docs/superpowers/specs/2026-08-30-t01-shell-runtime-react-design|T01 — shell/runtime React]];
- [[docs/superpowers/specs/2026-08-30-s08-programacion-semanal-react-design|S08 — Programacion Semanal React]];
- [[docs/superpowers/specs/2026-08-30-s09-cnp-react-design|S09 — CNP React]];
- [[docs/superpowers/specs/2026-08-30-s10-cnc-react-design|S10 — CNC React]];
- [[docs/security/rls-runtime-boundary|Frontera runtime de RLS]].

T01 posee sesion, proyecto, semana, sidebar, temas claro/oscuro y navegacion. Tambien posee la
creacion de semana y su bloqueo por CIC pendiente. S08 posee el cierre semanal, la poblacion de
subcontratistas comprometidos y la materializacion ordinaria de CIC al cerrar. S09 y S10 poseen las
otras vistas satelite y su navegacion React. S11 posee la consulta y calificacion integral de
contratistas, el catalogo de 59 preguntas, el calculo de disciplinas y la regla pura de cadencia que
T01 debe reutilizar. No duplica el shell ni convierte CIC en un catalogo de contratistas.

VIEW-36 es `views/programacion-semanal/CIC.view.php` y pertenece unicamente a S11. Es el ultimo
consumidor auditado de las ramas responsive CIC en
`public/js/modules/programacion_semanal/legacyCards.js`; por eso S11 puede retirar ese adaptador
compartido al corte, pero solo tras demostrar cero consumidores. Los selectores de
`public/css/programacion-semanal.css` se eliminan con la misma prueba de uso, no por semejanza.

## Resultado buscado

`/programacion-semanal/cic` sera una superficie React que conserva la capacidad util y el
comportamiento observable de la vista PHP/JavaScript actual, a la vez que elimina efectos ocultos de
una lectura:

1. usa proyecto y semana activos del shell como corte historico validado por servidor;
2. muestra cada subcontratista elegible con su ultima evaluacion hasta ese corte;
3. representa proveedores aun no materializados como proyecciones de solo lectura hasta su primera
   calificacion explicita o el cierre semanal de S08;
4. muestra semanas de presencia, ultima semana elegible, tipo, alcance, contacto, NIT, PAC, avance,
   cuatro disciplinas, integral, acumulados y observaciones;
5. distingue evaluacion persistida, proyectada, metadata incompleta, tipo no soportado y duplicado;
6. expone la cadencia cada ocho semanas de presencia y si la calificacion requerida esta completa;
7. conserva los dos cuestionarios exactos: Mano de Obra con 26 preguntas y Suministro e Instalacion
   con 33 preguntas;
8. ofrece visiblemente `Sin calificar`, `0 %`, `50 %`, `100 %` y `N/A` para cada pregunta;
9. habilita solo las disciplinas efectivas que el servidor concede al usuario;
10. valida cuestionarios completos por disciplina y guarda una o varias disciplinas modificadas;
11. permite actualizar la observacion compartida cuando existe al menos una disciplina editable;
12. calcula puntaje por disciplina, integral actual y acumulados con la semantica legacy
    caracterizada;
13. guarda con CSRF, scope servidor, lock, version opaca y conflicto recuperable;
14. busca, filtra, cuenta y recarga sin perder el corte o los filtros validos;
15. usa tabla semantica en desktop/tablet y tarjetas nativas equivalentes en movil;
16. conserva tutorial y navegacion interna mediante enlaces seguros y rutas React;
17. maneja carga, vacio, filtros sin resultado, solo lectura, diagnosticos, conflicto y error;
18. ofrece capacidad equivalente en temas oscuro y claro, teclado, lector de pantalla, zoom y touch.

Paridad no obliga a conservar DataTables, jQuery, Bootstrap, modales duplicados, inputs ocultos,
globals de sesion, POST de lectura, `SELECT *`, efectos de autocuracion ni mensajes de excepcion.
React conserva intencion, datos, permisos, calculos, efectos explicitos y recuperacion.

## Alcance

### Incluido

- Ruta piloto y ruta canonica React de CIC.
- VIEW-36, lista, tabla, tarjetas, leyenda, tutorial y editor de cuestionario.
- Contexto tipado de proyecto, semana, acciones, disciplinas, cuestionarios, navegacion y CSRF.
- Poblacion de proveedores derivada de presencias semanales hasta el corte seleccionado.
- Ultima evaluacion persistida hasta el corte o proyeccion no persistida cuando falta.
- Dos tipos editables exactos: `Mano de Obra` y `Suministro e Instalacion`.
- Exclusion exacta de `Suministro de Materiales, Herramientas o Equipos`.
- Diagnostico de tipos desconocidos, metadata incompleta y filas CIC duplicadas, sin autocuracion.
- Conteo de semanas por presencia elegible, no por filas creadas como efecto de lectura.
- Cadencia compartida con T01: cada octava presencia exige cuatro disciplinas distintas de `NR`.
- Los 59 textos y claves legacy en un catalogo canonico de aplicacion, sin nuevas tablas.
- Valores de pregunta normalizados y visibles: `not-rated`, `zero`, `half`, `full`,
  `not-applicable`.
- Puntajes actual/acumulado, PAC, porcentaje completado e integral con calculadores puros.
- Busqueda y filtros por tipo, cadencia, completitud, rendimiento, disciplina y relacion con corte.
- Conteos total, visible, pendiente por cadencia, completo, proyectado y con diagnostico.
- Tabla desktop/tablet y tarjetas moviles con la misma informacion y accion.
- Guardado individual de disciplinas modificadas y observacion, con materializacion explicita.
- Recalculo transaccional del proveedor afectado desde la semana editada hacia delante.
- Temas oscuro/claro, foco, live regions, reduced motion, zoom 200 % y targets tactiles.
- Contratos PHP, Zod, pruebas puras y navegador con red completamente interceptada.
- Convivencia legacy durante piloto y retiro exclusivo despues del corte canonico.

### Fuera de alcance

- `/admin/` y cualquier ruta, permiso, vista, estilo o dependencia administrativa.
- Cambiar RLS, `ProjectScope`, schema, migraciones, tablas, columnas, indices, triggers, grants,
  usuarios, credenciales, membresias, roles, overrides o datos durante esta fase documental.
- Ejecutar DDL/DML en auditoria, autorrevision o verificaciones prescritas por este documento.
- Crear, editar, normalizar o borrar el catalogo maestro de subcontratistas.
- Autocorregir tipo, alcance, correo, NIT o identificadores desde datos semanales.
- Insertar en lote filas CIC al abrir, listar, recargar, filtrar o cambiar de semana.
- Generar CIC de cierre; pertenece a S08.
- Crear una semana; pertenece a T01.
- Reprogramar actividades, editar compromisos, actualizar avance o clasificar CNP/CNC.
- Cambiar formulas, umbrales o poblaciones de indicadores BI/reportes de contratistas.
- Crear un historial cronologico nuevo: legacy solo ofrece ultima fila hasta el corte, semanas de
  presencia y metricas actuales/acumuladas.
- Exportar CSV/XLSX, descargar corte, guardar por lote entre proveedores, eliminar evaluaciones o
  CRUD de preguntas; VIEW-36 no ofrece esas capacidades.
- Integrar el drawer contextual T02: CIC edita un cuestionario propio y no abre detalle de actividad.
- Permitir tipos de proveedor distintos a los dos formularios auditados.
- Introducir una politica general de ventana semanal o confirmacion para CIC; el servidor actual no
  la consulta.
- Redefinir la aprobacion BI a partir de los tonos visuales de esta superficie.
- Regenerar o aprobar goldens visuales sin autorizacion explicita.

## Punto de partida medido

### React

- No existe ruta, pagina, esquema Zod, gateway, dominio ni componentes CIC.
- La sidebar y la navegacion semanal aun envian `/programacion-semanal/cic` a legacy.
- T01 ya dispone de sesion, proyecto, semana, navegacion y tema; S11 no crea otro selector ni
  hardcodea roles.
- S08 ya define el cierre que debe materializar proveedores y sus metricas semanales.
- `frontend/src/lib/api/cliente.ts` es la unica frontera HTTP permitida.
- No existe un catalogo tipado compartido de preguntas CIC.

### Legacy

| Pieza | Medicion auditada |
|---|---|
| Vista | VIEW-36, 1.544 lineas |
| Controlador API | `CicApiController`, 426 lineas |
| Tarjetas responsive | `legacyCards.js`, 435 lineas compartidas |
| Presentacion | `programacion-semanal.css`, 3.814 lineas compartidas |
| Cuestionarios | MDO 26 preguntas; SI 33 preguntas; 59 en total |
| Grid | DataTables, sin paginacion ni ordenamiento explicito |
| Responsive | tarjetas bajo 1180 px; DataTable permanece montado oculto |
| Lectura | `POST /api/cic/list`, con escrituras y recalculos ocultos |
| Mutacion | `POST /api/cic/save`, form-urlencoded y respuesta `BIEN` |
| Evidencia | tests de rol/subvista que escriben y restauran datos reales |

La vista carga dependencias globales, inyecta proyecto, prefijo, semana, rol, maximo, confirmacion y
CSRF en HTML, monta a la vez dos modales con identificadores repetidos y conserva las 59 respuestas
como columnas ocultas de DataTables. El boton de tutorial abre
`https://youtu.be/OJrd5qlgFm4` en otra ventana. La navegacion usa
`/legacy/cambiar_pagina.php`.

### Defectos y contradicciones observados

1. La pagina solo exige autenticacion; la API de lista exige `lps.cic.ver`. React exigira la
   capacidad desde el primer byte.
2. La operacion llamada `list` no es lectura: recorre todas las semanas hasta el corte, repara
   metadata, inserta proveedores, sincroniza PAC y actualiza integrales.
3. Abrir, recargar, buscar o cambiar semana puede escribir muchas filas sin confirmacion del usuario.
4. `semanasEnProyecto` cuenta filas CIC; solo coincide con presencia real despues de converger los
   efectos ocultos.
5. El query escoge la ultima semana por proveedor, pero duplicados en esa semana se resuelven de
   forma no determinista.
6. La respuesta entrega el almacenamiento casi completo, incluidas las 59 columnas legacy.
7. El servidor trata todo tipo distinto de `Mano de Obra` como formulario SI, mientras la interfaz
   solo abre los dos tipos exactos soportados.
8. Desktop muestra boton para filas cuyo tipo no puede editar; el click simplemente no abre nada.
9. Movil omite `semanasEnProyecto` y `P_Completado`, por lo que pierde informacion frente a desktop.
10. La UI oculta `NR` como radio invisible; la persona no puede elegir de nuevo “sin calificar”.
11. Dos formularios simultaneos repiten IDs y dificultan foco, etiquetas y lector de pantalla.
12. Guardar usa ID y semana enviados por el navegador; el servidor verifica la semana exacta, pero
    no tiene version ni deteccion de edicion concurrente.
13. El formulario envia todas las respuestas visibles y el modal se cierra antes de confirmar exito.
14. Los errores pueden exponer excepciones; el exito no devuelve proveedor ni calculos actualizados.
15. La observacion compartida se envia junto con una disciplina, sin contrato para cambio aislado.
16. La UI movil limita al Residente por una ventana de semanas que el servidor y desktop no aplican.
17. El controlador recalcula historicos al listar, de modo que una edicion antigua solo converge en
    una lectura posterior con efectos.
18. La regla cada ocho semanas depende del numero de filas materializadas, no de presencias
    declaradas como dominio.
19. Una migracion historica puede carecer de `si_adm_6`; el runtime no debe hacer DDL para repararla.
20. `NIT` tiene representaciones incompatibles entre CIC y el maestro; no se puede copiar sin
    comprobar capacidad de almacenamiento.
21. La leyenda de “Falta calificar” mezcla cadencia, incompletitud y rendimiento en un mismo estilo.
22. Los tests de navegador vigentes restauran membresias y datos; restaurar sigue siendo DML.

S11 corrige esos defectos sin ampliar tipos, formulas BI, permisos, schema ni datos durante diseño y
verificacion.

## Comportamiento observable auditado

### Contexto, corte y carga

- La superficie usa la semana activa como corte historico, no como requisito de coincidencia exacta.
- Para cada subcontratista entrega la ultima evaluacion con `Semana <= corte`.
- La lista excluye exactamente el tipo de materiales/herramientas/equipos.
- Si no hay proveedores muestra: “Sin subcontratistas para calificar esta semana. Los proveedores
  aparecen al tener actividades comprometidas en la semana.”
- No hay paginacion, orden de usuario ni carga incremental.
- Recargar vuelve a ejecutar la lista completa y hoy dispara sus efectos ocultos.

### Tabla, busqueda y estados

Desktop muestra accion, semanas en proyecto, ultima semana, subcontratista, alcance, tipo, PAC,
porcentaje completado, Calidad, GSA, SST, Administracion, calificacion integral y observaciones. ID y
59 respuestas existen ocultos en DataTables. La busqueda global se rotula “Buscar subcontratista”.

Los valores de metricas se presentan asi:

- `NA`: “No Aplica”;
- `NR`: “Falta Calificar”;
- numerico: porcentaje redondeado sin decimales;
- `>= 95 %`: cumple objetivo;
- `>= 70 %` y `< 95 %`: bajo objetivo;
- `< 70 %`: critico.

Cada octava fila historica del proveedor, cuando al menos una disciplina es `NR`, recibe estado
retardado. Esa condicion tambien bloquea crear la siguiente semana mediante el verificador legacy.
Los tonos de S11 son orientacion operacional local; no cambian los umbrales BI auditados, donde
aprobacion/seguimiento tienen otra semantica.

### Responsive y edicion

Por debajo de 1180 px el script oculta visualmente la tabla, pero la deja montada, y genera tarjetas
con alcance, tipo, semana, PAC, cuatro disciplinas, integral y observaciones. No incluye semanas en
proyecto ni porcentaje completado. La tarjeta ofrece editar solo para los dos tipos soportados y
segun una funcion local de rol; desktop muestra mas botones, aunque algunos no actuan.

Al editar se copian respuestas y observacion desde la fila a uno de dos modales. Las secciones de
disciplinas no autorizadas se ocultan y deshabilitan. Cancelar vuelve a cargar la lista. Guardar
serializa el formulario entero y cierra el modal inmediatamente.

### Cuestionarios y puntuacion

Cada pregunta tiene valores persistidos `NR`, `0`, `0.5`, `1` y `NA`. El algoritmo por disciplina:

1. ignora `NA` y `NR` al promediar;
2. si todas las respuestas son `NR`, el resultado es `NR`;
3. si no hay ningun numero y no todas son `NR`, el resultado es `NA`;
4. en otro caso promedia valores numericos y redondea a tres decimales.

La integral parte de PAC 30 %, Calidad 20 %, GSA 20 %, SST 20 % y Administracion 10 %. Las
disciplinas no disponibles (`NA`, `NR` o null) salen del denominador y su peso se redistribuye de
forma proporcional entre PAC y dimensiones activas. Si las cuatro estan ausentes, PAC recibe el
100 %. La caracterizacion debe fijar el tratamiento exacto de PAC vacio o no numerico antes de
refactorizar; S11 no corrige reportes BI por aproximacion.

### Permisos observados

La mutacion exige `lps.cic.editar`, CSRF y al menos una disciplina concedida por rol normalizado. La
matriz auditada es:

| Rol canonico | Calidad | Administracion | Socioambiental | SST |
|---|---:|---:|---:|---:|
| A | si | si | si | si |
| D | si | si | si | si |
| R | si | no | no | no |
| G | no | no | si | no |
| SG | no | no | si | si |
| S | no | no | no | si |
| OT | no | si | no | no |
| DCV | no | no | no | no |
| V | no | no | no | no |
| C/otro | no | no | no | no |

DCV conserva capacidad fallback de editar, pero su interseccion con disciplinas es vacia y por ello
es efectivamente solo lectura. V obtiene lectura por `allRead`; C no obtiene CIC en el fallback.
Overrides persistidos siguen siendo autoritativos. React recibe acciones efectivas, nunca roles.

El servidor no consulta `LpsWeekEditPolicy` ni confirmacion para CIC. La restriccion reciente del rol
R aparece solo en movil y contradice desktop/API. La regla canonica de S11 es capacidad efectiva mas
disciplinas permitidas, sin inventar ventana semanal. Cambiarla seria una decision de negocio fuera
de esta migracion.

## Poblacion canonica, proyecciones e historial

### Fuente de presencia

La poblacion se deriva de `programacion_semanal`, siempre con `project_id` explicito, para semanas
`<=` al corte activo y filas de actividad elegibles segun la misma semantica de S08. Por cada nombre
de subcontratista no vacio se cuentan semanas distintas con presencia. Se resuelve el maestro
`subcontratistas` dentro del proyecto y se unen evaluaciones CIC persistidas sin escribir.

No se usa el numero de filas CIC como verdad de presencia. La consulta no crea ausentes, no repara
metadata, no sincroniza PAC y no recalcula almacenamiento. Un ledger de fake store hace fallar el
contrato si cualquier GET invoca update, insert, transaccion o DDL.

### Ultima evaluacion hasta el corte

Para cada proveedor se retorna:

- `providerWeeks`: cantidad de semanas distintas de presencia hasta el corte;
- `latestEligibleWeek`: ultima semana de presencia hasta el corte;
- `evaluationWeek`: semana de la ultima evaluacion persistida hasta el corte, o la ultima elegible
  para una proyeccion;
- `atSelectedWeek`: si `evaluationWeek` coincide con el corte;
- `persistence`: `persisted` o `projected`;
- metricas actuales para `evaluationWeek` y acumuladas hasta esa semana;
- ultima respuesta persistida cuando existe; valores `not-rated` para una proyeccion.

El orden estable es nombre normalizado, identificador maestro y semana descendente. Si hay mas de
una fila CIC para el mismo proveedor/semana, se muestra una eleccion determinista solo para lectura,
se marca `DUPLICATE_CIC_ROWS` y se deshabilita edicion. S11 no borra ni fusiona duplicados.

### Proyeccion sin persistencia

Un proveedor presente sin fila CIC aparece como `projected` si el maestro permite resolver una
identidad valida. Sus respuestas son `not-rated`, sus puntajes de disciplina son `not-rated` y sus
metricas PAC/avance se derivan en memoria. No recibe un ID CIC falso.

La primera mutacion valida de una proyeccion:

1. resuelve nuevamente proyecto, corte, presencia y maestro en servidor;
2. exige uno de los dos tipos soportados y metadata compatible;
3. bloquea el proveedor/semana y comprueba que aun no exista una fila equivalente;
4. asigna ID conforme al mecanismo scoped vigente, dentro de la misma transaccion;
5. inserta una sola fila CIC con datos derivados del maestro;
6. aplica respuestas/observacion autorizadas;
7. calcula puntajes e integrales;
8. devuelve el proveedor ya `persisted`.

Si otra sesion materializo primero, la operacion no duplica: responde conflicto con version/estado
nuevo. S08 continua materializando el resto al cierre semanal. No existe batch oculto.

### Metadata y tipos

- `Mano de Obra`: usa cuestionario MDO.
- `Suministro e Instalacion`: usa cuestionario SI.
- `Suministro de Materiales, Herramientas o Equipos`: no pertenece a la poblacion visible.
- cualquier otro tipo: fila visible de solo lectura con `UNSUPPORTED_PROVIDER_TYPE`.
- maestro ausente, ambiguo o metadata no representable: fila visible con diagnostico y edicion
  denegada; no se inventa `AIA`, alcance ni tipo.
- una evaluacion persistida valida puede seguir leyendose aun si el maestro deriva; solo una nueva
  proyeccion exige maestro completo.

NIT, email y contacto son solo lectura. La materializacion comprueba limites del schema vigente y
rechaza `PROVIDER_METADATA_INCOMPATIBLE`; nunca trunca silenciosamente ni altera columnas.

## Cadencia cada ocho presencias

S11 crea o reutiliza una unica `CicCadencePolicy` pura, consumida tambien por T01. Para cada
proveedor elegible:

- `due` es verdadero cuando `providerWeeks > 0` y `providerWeeks % 8 === 0`;
- `complete` es verdadero si Calidad, GSA, SST y Administracion no son `not-rated`/`NR`;
- `due-incomplete` bloquea la creacion de la siguiente semana en T01;
- `due-complete` comunica cumplimiento de la cadencia;
- `not-due` no bloquea.

`N/A` cuenta como respondido para cadencia, tal como el verificador legacy solo busca `NR`. La
cadencia depende de presencias distintas, no de cuantas filas haya insertado una lectura. Si T01 ya
implemento una politica equivalente, S11 la reutiliza; si quedo embebida, la extrae mecanicamente y
mantiene su contrato. S11 no llama ni simula `POST /api/context/weeks/create`.

El contexto puede devolver el conteo agregado de `due-incomplete`, pero el detalle vive en cada
proveedor. El mensaje explica “cada ocho semanas de presencia” y no promete meses calendario.

## Catalogo canonico de cuestionarios

### Estructura

Un `CicQuestionnaireCatalog` de aplicacion, versionado en codigo servidor, posee:

- tipos exactos soportados y prefijo persistente;
- disciplinas `quality`, `administration`, `socio-environmental`, `safety`;
- clave wire estable, columna legacy, orden y texto exacto por pregunta;
- cinco opciones y su mapeo de almacenamiento;
- pesos de integral y version opaca del catalogo.

El endpoint de contexto devuelve solo el cuestionario aplicable a cada tipo y las opciones. React
no hardcodea textos ni nombres de columnas. El catalogo no crea schema ni tabla. La implementacion
debe verificar que las 59 columnas existen; si falta una, responde
`CIC_SCHEMA_PREREQUISITE_MISSING`, sin DDL de runtime.

### Opciones wire

| Wire | Etiqueta visible | Persistencia legacy | Participa en promedio |
|---|---|---|---:|
| `not-rated` | Sin calificar | `NR` | no |
| `zero` | 0 % | `0` | si, como 0 |
| `half` | 50 % | `0.5` | si |
| `full` | 100 % | `1` | si |
| `not-applicable` | N/A | `NA` | no |

`NR` deja de ser un input escondido: es una opcion visible, seleccionable y anunciada. El servidor
rechaza cualquier valor, pregunta o disciplina fuera del catalogo.

### Catalogo MDO — 26 preguntas

#### Calidad — 3

1. `mdo.quality.1` / `mdo_cal_1`: La calidad del producto suministrado e instalado.
2. `mdo.quality.2` / `mdo_cal_2`: Las condiciones de almacenamiento de los materiales, insumos,
   maquinaria y equipos.
3. `mdo.quality.3` / `mdo_cal_3`: Entrega de certificaciones / procedimientos asociadas a la
   actividad desarrollada.

#### Administracion — 5

1. `mdo.administration.1` / `mdo_adm_1`: Los procedimientos administrativos y legales de AIA.
2. `mdo.administration.2` / `mdo_adm_2`: La competencia y disponibilidad oportuna del personal en
   la obra.
3. `mdo.administration.3` / `mdo_adm_3`: La disponibilidad oportuna y suficiente de los recursos:
   maquinaria, equipo y herramienta.
4. `mdo.administration.4` / `mdo_adm_4`: La atencion a solicitudes, quejas y reclamos.
5. `mdo.administration.5` / `mdo_adm_5`: Los requisitos legales de calidad, ambiental y seguridad
   y salud en el trabajo.

#### Socioambiental — 8

1. `mdo.socio-environmental.1` / `mdo_gsa_1`: Mantener la rotulacion, clasificacion y almacenamiento
   de los residuos peligroso en obra de acuerdo a lo establecido por la organizacion y la normativa
   colombiana.
2. `mdo.socio-environmental.2` / `mdo_gsa_2`: Realizar la adecuada separacion, almacenamiento y
   disposicion interna y externa (cuando aplique) de los residuos generados en obra.
3. `mdo.socio-environmental.3` / `mdo_gsa_3`: Asistir a las capacitaciones programadas desde el
   proceso de gestion ambiental y establecidas por el contratista (sst interno).
4. `mdo.socio-environmental.4` / `mdo_gsa_4`: Realizar jornadas de orden y aseo lideradas por el
   equipo de gestion integral, mantener en perfectas condiciones de orden y aseo el sitio de trabajo
   drurante toda la jornada laboral. Uso adecuado de las instalaciones en comun (Caspete, baños,
   comedor). Sistemas de iluminacion ahorradores en provisionales y optimas condiciones de orden y
   aseo en estas.
5. `mdo.socio-environmental.5` / `mdo_gsa_5`: Almacenar los materiales en los sitios definidos para
   tal fin, pensando siempre en reducir el desperdicio.
6. `mdo.socio-environmental.6` / `mdo_gsa_6`: Realizar mantenimiento de las cajas de sedimentacion
   establecidas para el uso del contratista (Ejemplo: cortadoras, bombeo de concreto, lavallantas,
   planta de concreto).
7. `mdo.socio-environmental.7` / `mdo_gsa_7`: Cumplimiento de actividades de control operacional
   evidenciado durante las inspecciones con el respectivo plan de accion.
8. `mdo.socio-environmental.8` / `mdo_gsa_8`: Acatar las acciones recomendadas durante recorridos de
   obra.

#### Seguridad y salud en el trabajo — 10

1. `mdo.safety.1` / `mdo_sst_1`: Cuenta con el analisis de riesgo de la tarea y el cumplimiento de
   las recomendaciones.
2. `mdo.safety.2` / `mdo_sst_2`: Cumple con los requisitos de entrega, uso y reposicion de los
   equipos de proteccion personal y dotacion.
3. `mdo.safety.3` / `mdo_sst_3`: Cumple normas, procedimientos y/o estandares de seguridad de AIA y
   legales.
4. `mdo.safety.4` / `mdo_sst_4`: Se observa el cumplimiento de comportamientos seguros en la
   ejecucion de tareas contratadas.
5. `mdo.safety.5` / `mdo_sst_5`: Reporta los eventos asociados a salud (accidentes, enfermedades)
   de manera oportuna.
6. `mdo.safety.6` / `mdo_sst_6`: Aporta ideas para la seguridad del lugar de trabajo, reporta
   condiciones de riesgos y posibles soluciones para las mismas.
7. `mdo.safety.7` / `mdo_sst_7`: Cumple con la asistencia a las capacitaciones y charlas de
   seguridad y salud en el trabajo.
8. `mdo.safety.8` / `mdo_sst_8`: Se integra al plan de ayuda mutua en la obra para la prevencion y
   control de emergencias.
9. `mdo.safety.9` / `mdo_sst_9`: Cuenta con una persona de seguridad y salud en el trabajo.
10. `mdo.safety.10` / `mdo_sst_10`: Cumple con el manejo, transporte, manipulacion y disposicion de
    sustancias quimicas suministrando la ficha de datos de seguridad.

### Catalogo Suministro e Instalacion — 33 preguntas

#### Calidad — 3

1. `si.quality.1` / `si_cal_1`: La calidad del producto suministrado e instalado.
2. `si.quality.2` / `si_cal_2`: La entrega de procedimientos y/o protocolos para asegurar el
   cumplimiento de requisitos.
3. `si.quality.3` / `si_cal_3`: La entrega oportuna de los certificados de calibracion de los
   equipos de medicion, certificaciones y permisos ambientales.

#### Administracion — 6

1. `si.administration.1` / `si_adm_1`: El cumplimiento de las necesidades y oportunidad de personal
   en la obra.
2. `si.administration.2` / `si_adm_2`: La disponibilidad, oportunidad y estado de la maquinaria,
   equipo y herramienta de trabajo.
3. `si.administration.3` / `si_adm_3`: La atencion de solicitudes, quejas y reclamos.
4. `si.administration.4` / `si_adm_4`: Los procedimientos administrativos y legales de la obra.
5. `si.administration.5` / `si_adm_5`: El cumplimiento del procedimiento de facturacion.
6. `si.administration.6` / `si_adm_6`: El tiempo establecido para la liquidacion del contrato.

#### Socioambiental — 14

1. `si.socio-environmental.1` / `si_gsa_1`: Presentar certificados durante los 15 primeros dias del
   mes en donde se relacionen las volquetas con PIN (Para Bogota y cartagena) y Medellin los
   primeros 5 dias del generador y sitio de disposicion final con cantidades. Las volquetas deben
   contar con numero de PIN en Bogota y Cartagena. Suministrar el control de los residuos que se han
   salido de la obra, con placa, fecha, cantidad, sitio de disposicion mensual. Presentar volqueta
   con modelos superiores al año 2012. Contar con auxiliares de transito certificados para facilitar
   el movimiento interno y externo de los vehiculos. El sitio de disposicion final debe estar
   inscrito ante autoridad ambiental de acuerdo a la clasificacion de la resolucion 472 de 2017.
2. `si.socio-environmental.2` / `si_gsa_2`: En caso de contar con la posibilidad de realizar el
   aprovechamiento de este material, notificar antes de realizar la actividad en la obra para
   verificar la legalidad de la situacion.
3. `si.socio-environmental.3` / `si_gsa_3`: Las volquetas deben estar cubiertas con material
   completamente hermetico, y con carpado automatico. En caso de no llegar en estas condiciones no
   se permitira el ingreso a obra, entrar y salir con las volcos cubiertas, compuertas y puertas
   cerradas y demas.
4. `si.socio-environmental.4` / `si_gsa_4`: Presentar los permisos ambientales correspondientes a
   la actividad (Licencias, titulos mineros, Plan de manejo ambiental, rucom, y demas permisos para
   operacion) y los certificados mensuales de la entrega en obra.
5. `si.socio-environmental.5` / `si_gsa_5`: Presentar el certificado en donde se evidencie que el
   material de suministro presenta algun porcentaje (%) de material reciclable, cuando aplique.
6. `si.socio-environmental.6` / `si_gsa_6`: Suministrar en caso de ingreso de maquinaria: SOAT;
   revision tecnicomecanica, hoja de vida (donde se incluya el mantenimiento preventido),
   programacion de mantenimientos y matricula, poliza de terceros.
7. `si.socio-environmental.7` / `si_gsa_7`: Mantener la rotulacion, clasificacion y almacenamiento
   de los residuos peligroso en obra de acuerdo a lo establecido por la organizacion y la normativa
   colombiana.
8. `si.socio-environmental.8` / `si_gsa_8`: Realizar la adecuada separacion, almacenamiento y
   disposicion interna y externa (cuando aplique) de los residuos generados en obra.
9. `si.socio-environmental.9` / `si_gsa_9`: Asistir a las capacitaciones programadas desde el
   proceso de gestion ambiental y establecidas por el contratista (sst interno).
10. `si.socio-environmental.10` / `si_gsa_10`: Realizar jornadas de orden y aseo lideradas por el
    equipo de gestion integral, mantener en perfectas condiciones de orden y aseo el sitio de
    trabajo drurante toda la jornada laboral. Uso adecuado de las instalaciones en comun (Caspete,
    baños, comedor). Sistemas de iluminacion ahorradores en provisionales y optimas condiciones de
    orden y aseo en estas.
11. `si.socio-environmental.11` / `si_gsa_11`: Almacenar los materiales en los sitios definidos para
    tal fin, pensando siempre en reducir el desperdicio.
12. `si.socio-environmental.12` / `si_gsa_12`: Realizar mantenimiento de las cajas de sedimentacion
    establecidas para el uso del contratista (Ejemplo: cortadoras, bombeo de concreto, lavallantas,
    planta de concreto).
13. `si.socio-environmental.13` / `si_gsa_13`: Cumplimiento de actividades de control operacional
    evidenciado durante las inspecciones con el respectivo plan de accion.
14. `si.socio-environmental.14` / `si_gsa_14`: Acatar las acciones recomendadas durante recorridos
    de obra.

#### Seguridad y salud en el trabajo — 10

1. `si.safety.1` / `si_sst_1`: Cuenta con el analisis de riesgo de la tarea y el cumplimiento de las
   recomendaciones.
2. `si.safety.2` / `si_sst_2`: Cumple con los requisitos de entrega, uso y reposicion de los equipos
   de proteccion personal y dotacion.
3. `si.safety.3` / `si_sst_3`: Cumple normas, procedimientos y/o estandares de seguridad de AIA y
   legales.
4. `si.safety.4` / `si_sst_4`: Se observa el cumplimiento de comportamientos seguros en la
   ejecucion de tareas contratadas.
5. `si.safety.5` / `si_sst_5`: Reporta los eventos asociados a salud (accidentes, enfermedades) de
   manera oportuna.
6. `si.safety.6` / `si_sst_6`: Aporta ideas para la seguridad del lugar de trabajo, reporta
   condiciones de riesgos y posibles soluciones para las mismas.
7. `si.safety.7` / `si_sst_7`: Cumple con la asistencia a las capacitaciones y charlas de seguridad
   y salud en el trabajo.
8. `si.safety.8` / `si_sst_8`: Se integra al plan de ayuda mutua en la obra para la prevencion y
   control de emergencias.
9. `si.safety.9` / `si_sst_9`: Cuenta con una persona de seguridad y salud en el trabajo.
10. `si.safety.10` / `si_sst_10`: Cumple con el manejo, transporte, manipulacion y disposicion de
    sustancias quimicas suministrando la ficha de datos de seguridad.

### Anexo normativo — texto literal auditado

Las listas anteriores facilitan lectura y agrupacion. El siguiente TSV es la fuente normativa del
texto, extraida de los `legend` de VIEW-36 el 2026-08-30. El catalogo y su contrato comparan estas
cadenas literalmente (tras normalizar solo finales de linea del archivo, no ortografia, tildes ni
puntuacion):

```text
si_cal_1	La calidad del producto suministrado e instalado:
si_cal_2	La entrega de procedimientos y/o protocolos para asegurar el cumplimiento de requisitos:
si_cal_3	La entrega oportuna de los certificados de calibración de los equipos de medición, certificaciones y permisos ambientales:
si_adm_1	El cumplimiento de las necesidades y oportunidad de personal en la obra:
si_adm_2	La disponibilidad, oportunidad y estado de la maquinaria, equipo y herramienta de trabajo:
si_adm_3	La atención de solicitudes, quejas y reclamos:
si_adm_4	Los procedimientos administrativos y legales de la obra:
si_adm_5	El cumplimiento del procedimiento de facturación:
si_adm_6	El tiempo establecido para la liquidación del contrato:
si_gsa_1	Presentar certificados durante los 15 primeros días del mes en donde se relacionen las volquetas con PIN (Para Bogotá y cartagena) y Medellín los primeros 5 días del generador y sitio de disposición final con cantidades. Las volquetas deben contar con número de PIN en Bogotá y Cartagena. Suministrar el control de los residuos que se han salido de la obra, con placa, fecha, cantidad, sitio de disposición mensual. Presentar volqueta con modelos superiores al año 2012. Contar con auxiliares de tránsito certificados para facilitar el movimiento interno y externo de los vehículos. El sitio de disposición final debe estar inscrito ante autoridad ambiental de acuerdo a la clasificación de la resolución 472 de 2017:
si_gsa_2	En caso de contar con la posibilidad de realizar el aprovechamiento de este material, notificar antes de realizar la actividad en la obra para verificar la legalidad de la situación:
si_gsa_3	Las volquetas deben estar cubiertas con material completamente hermetico, y con carpado automatico. En caso de no llegar en estas condiciones no se permitirá el ingreso a obra, entrar y salir con las volcos cubiertas, compuertas y puertas cerradas y demás:
si_gsa_4	Presentar los permisos ambientales correspondientes a la actividad (Licencias, títulos mineros, Plan de manejo ambiental, rucom, y demás permisos para operación, ) y los certificados mensuales de la entrega en obra:
si_gsa_5	Presentar el certificado en donde se evidencie que el material de suministro presenta algún porcentaje (%) de material reciclable, cuando aplique:
si_gsa_6	Suministrar en caso de ingreso de maquinaria: SOAT; revisión tecnicomecanica, hoja de vida (donde se incluya el mantenimiento preventido), programación de mantenimientos y matricula, poliza de terceros:
si_gsa_7	Mantener la rotulación, clasificación y almacenamiento de los residuos peligroso en obra de acuerdo a lo establecido por la organización y la normativa colombiana:
si_gsa_8	Realizar la adecuada separación, almacenamiento y disposición interna y externa (cuando aplique) de los residuos generados en obra:
si_gsa_9	Asistir a las capacitaciones programadas desde el proceso de gestión ambiental y establecidas por el contratista (sst interno):
si_gsa_10	Realizar jornadas de orden y aseo lideradas por el equipo de gestión integral, mantener en perfectas condiciones de orden y aseo el sitio de trabajo drurante toda la jornada laboral. Uso adecuado de las instalaciones en comun (Caspete, baños, comedor). Sistemas de iluminación ahorradores en provisionales y óptimas condiciones de orden y aseo en estas:
si_gsa_11	Almacenar los materiales en los sitios definidos para tal fin, pensando siempre en reducir el desperdicio:
si_gsa_12	Realizar mantenimiento de las cajas de sedimentación establecidas para el uso del contratista (Ejemplo: cortadoras, bombeo de concreto, lavallantas, planta de concreto):
si_gsa_13	Cumplimiento de actividades de control operacional evidenciado durante las inspecciones con el respectivo plan de acción:
si_gsa_14	Acatar las acciones recomendadas durante recorridos de obra:
si_sst_1	Cuenta con el análisis de riesgo de la tarea y el cumplimiento de las recomendaciones:
si_sst_2	Cumple con los requisitos de entrega, uso y reposición de los equipos de protección personal y dotación:
si_sst_3	Cumple normas, procedimientos y/o estándares de seguridad de AIA y legales:
si_sst_4	Se observa el cumplimiento de comportamientos seguros en la ejecución de tareas contratadas:
si_sst_5	Reporta los eventos asociados a salud (accidentes, enfermedades) de manera oportuna:
si_sst_6	Aporta ideas para la seguridad del lugar de trabajo, reporta condiciones de riesgos y posibles soluciones para las mismas:
si_sst_7	Cumple con la asistencia a las capacitaciones y charlas de seguridad y salud en el trabajo:
si_sst_8	Se integra al plan de ayuda mutua en la obra para la prevención y control de emergencias:
si_sst_9	Cuenta con una persona de seguridad y salud en el trabajo:
si_sst_10	Cumple con el manejo, transporte, manipulación y disposición de sustancias químicas suministrando la ficha de datos de seguridad:
mdo_cal_1	La calidad del producto suministrado e instalado:
mdo_cal_2	Las condiciones de almacenamiento de los materiales, insumos, maquinaria y equipos:
mdo_cal_3	Entrega de certificaciones / procedimientos asociadas a la actividad desarrollada:
mdo_adm_1	Los procedimientos administrativos y legales de AIA:
mdo_adm_2	La competencia y disponibilidad oportuna del personal en la obra:
mdo_adm_3	La disponibilidad oportuna y suficiente de los recursos: maquinaria, equipo y herramienta:
mdo_adm_4	La atención a solicitudes, quejas y reclamos:
mdo_adm_5	Los requisitos legales de calidad, ambiental y seguridad y salud en el trabajo:
mdo_gsa_1	Mantener la rotulación, clasificación y almacenamiento de los residuos peligroso en obra de acuerdo a lo establecido por la organización y la normativa colombiana:
mdo_gsa_2	Realizar la adecuada separación, almacenamiento y disposición interna y externa (cuando aplique) de los residuos generados en obra:
mdo_gsa_3	Asistir a las capacitaciones programadas desde el proceso de gestión ambiental y establecidas por el contratista (sst interno):
mdo_gsa_4	Realizar jornadas de orden y aseo lideradas por el equipo de gestión integral, mantener en perfectas condiciones de orden y aseo el sitio de trabajo drurante toda la jornada laboral. Uso adecuado de las instalaciones en comun (Caspete, baños, comedor). Sistemas de iluminación ahorradores en provisionales y óptimas condiciones de orden y aseo en estas:
mdo_gsa_5	Almacenar los materiales en los sitios definidos para tal fin, pensando siempre en reducir el desperdicio:
mdo_gsa_6	Realizar mantenimiento de las cajas de sedimentación establecidas para el uso del contratista (Ejemplo: cortadoras, bombeo de concreto, lavallantas, planta de concreto):
mdo_gsa_7	Cumplimiento de actividades de control operacional evidenciado durante las inspecciones con el respectivo plan de acción:
mdo_gsa_8	Acatar las acciones recomendadas durante recorridos de obra:
mdo_sst_1	Cuenta con el análisis de riesgo de la tarea y el cumplimiento de las recomendaciones:
mdo_sst_2	Cumple con los requisitos de entrega, uso y reposición de los equipos de protección personal y dotación:
mdo_sst_3	Cumple normas, procedimientos y/o estándares de seguridad de AIA y legales:
mdo_sst_4	Se observa el cumplimiento de comportamientos seguros en la ejecución de tareas contratadas:
mdo_sst_5	Reporta los eventos asociados a salud (accidentes, enfermedades) de manera oportuna:
mdo_sst_6	Aporta ideas para la seguridad del lugar de trabajo, reporta condiciones de riesgos y posibles soluciones para las mismas:
mdo_sst_7	Cumple con la asistencia a las capacitaciones y charlas de seguridad y salud en el trabajo:
mdo_sst_8	Se integra al plan de ayuda mutua en la obra para la prevención y control de emergencias:
mdo_sst_9	Cuenta con una persona de seguridad y salud en el trabajo:
mdo_sst_10	Cumple con el manejo, transporte, manipulación y disposición de sustancias químicas suministrando la ficha de datos de seguridad:
```

Los textos se preservan como contenido funcional, incluidos giros historicos. Correcciones
editoriales que cambien significado requieren aprobacion de negocio; accesibilidad puede añadir
estructura y explicaciones sin reescribir la pregunta.

## Calculos canonicos

### Puntaje de disciplina

El calculador recibe las respuestas completas de una disciplina y devuelve una union explicita:

```text
not-rated | not-applicable | scored(value 0..1)
```

- todas `not-rated` -> `not-rated`;
- cero respuestas numericas y al menos una `not-applicable` -> `not-applicable`;
- una o mas numericas -> promedio de numericas, redondeado a tres decimales;
- `not-rated` y `not-applicable` no se convierten en cero.

La prueba cubre mezclas de NR/NA, cero real, todos los valores y las 59 claves. Una disciplina
incluida en mutacion debe traer todas sus preguntas; las no incluidas conservan su valor.

### PAC, porcentaje completado y acumulados

PAC y porcentaje completado se derivan de la evidencia semanal del mismo proveedor/semana mediante
la semantica que S08 caracterice. Los acumulados usan semanas de presencia hasta la evaluacion:

- PAC/avance acumulados: agregacion ponderada o formula legacy caracterizada, no promedio inventado;
- disciplina acumulada: promedio de puntajes numericos persistidos, excluye `NR` y `NA`;
- historial ausente: estado no calificado/no aplicable, nunca cero;
- recalculo: solo proveedor afectado, desde la semana editada hacia delante, orden estable.

### Integral

Pesos base:

| Dimension | Peso |
|---|---:|
| PAC | 0.30 |
| Calidad | 0.20 |
| Socioambiental | 0.20 |
| SST | 0.20 |
| Administracion | 0.10 |

Las disciplinas sin valor numerico salen del denominador y los pesos restantes se normalizan. Si no
hay disciplina numerica, el resultado integral es PAC. Si PAC es realmente ausente, el calculador
devuelve no disponible conforme a la caracterizacion exacta; no castea texto inesperado a cero sin
prueba. Actual y acumulado usan la misma funcion pura con entradas distintas.

`CicIntegralCalculator` debe demostrar equivalencia con `CicApiController` en todas las 16
combinaciones de disponibilidad de disciplinas y casos limite de PAC. `ReportProcessor` conserva su
contrato BI: S11 no lo modifica salvo que una extraccion mecanica ya probada sea necesaria y no
cambie salida byte por byte.

## Contratos HTTP objetivo

### Inventario legacy y retiro

| Metodo | Ruta legacy | Uso actual | Transicion |
|---|---|---|---|
| GET | `/programacion-semanal/cic` | VIEW-36 | piloto coexistente; SPA al corte |
| POST | `/api/cic/list` | lista con insert/update/recalculo | mantener para legacy durante piloto; retirar al corte |
| POST | `/api/cic/save` | guardado form-urlencoded | mantener para legacy durante piloto; retirar al corte |

S11 agrega exactamente tres endpoints. Todos devuelven JSON, exigen sesion/proyecto, tienen esquema
Zod estricto y prueba PHP de contrato:

| Metodo | Ruta nueva | Capacidad | CSRF | Efecto |
|---|---|---|---|---|
| GET | `/api/cic/context` | `lps.cic.ver` | no | lectura pura |
| GET | `/api/cic/providers` | `lps.cic.ver` | no | lectura pura |
| POST | `/api/cic/evaluation` | `lps.cic.editar` + disciplina efectiva | header | insert/update individual |

Ningun endpoint recibe proyecto, prefijo, area, rol o semana como autoridad. T01 sincroniza el
contexto de semana servidor antes de cargar. Cambiar semana aborta lecturas anteriores y solicita
contexto/proveedores del nuevo corte.

### Contexto

`GET /api/cic/context` devuelve:

```json
{
  "data": {
    "project": { "id": 65, "name": "Proyecto Norte", "area": "Construccion" },
    "week": {
      "number": 18,
      "startDate": "2026-08-24",
      "endDate": "2026-08-30",
      "maxWeek": 19,
      "confirmed": true
    },
    "actions": {
      "edit": { "allowed": true, "reasonCode": null },
      "disciplines": {
        "quality": { "allowed": true, "reasonCode": null },
        "administration": { "allowed": false, "reasonCode": "DISCIPLINE_FORBIDDEN" },
        "socio-environmental": { "allowed": false, "reasonCode": "DISCIPLINE_FORBIDDEN" },
        "safety": { "allowed": false, "reasonCode": "DISCIPLINE_FORBIDDEN" }
      }
    },
    "csrfToken": "opaque-or-null",
    "questionnaireVersion": "sha256-opaque",
    "questionnaires": [
      {
        "providerType": "Mano de Obra",
        "id": "mdo",
        "disciplines": [
          {
            "id": "quality",
            "label": "Calidad",
            "questions": [
              { "id": "mdo.quality.1", "label": "La calidad del producto suministrado e instalado." }
            ]
          }
        ]
      }
    ],
    "options": [
      { "id": "not-rated", "label": "Sin calificar" },
      { "id": "zero", "label": "0 %" },
      { "id": "half", "label": "50 %" },
      { "id": "full", "label": "100 %" },
      { "id": "not-applicable", "label": "N/A" }
    ],
    "cadence": { "intervalProviderWeeks": 8, "dueIncompleteCount": 1 },
    "tutorial": {
      "label": "Ver tutorial de CIC",
      "href": "https://youtu.be/OJrd5qlgFm4",
      "external": true
    },
    "sections": [
      { "id": "weekly", "label": "Programacion Semanal", "href": "/programacion-semanal", "available": true },
      { "id": "cnp", "label": "CNP", "href": "/programacion-semanal/cnp", "available": true },
      { "id": "cnc", "label": "CNC", "href": "/programacion-semanal/cnc", "available": true },
      { "id": "cic", "label": "CIC", "href": "/programacion-semanal/cic", "available": true }
    ]
  }
}
```

El ejemplo abrevia preguntas; el contrato real contiene las 59, particionadas 26/33 sin duplicados.
`csrfToken` es null cuando no hay ninguna disciplina mutable. Acciones y secciones se resuelven en
servidor. Confirmacion y ventana semanal no agregan denegaciones en CIC.

### Proveedores

`GET /api/cic/providers` devuelve:

```json
{
  "data": {
    "rows": [
      {
        "providerRef": { "mode": "existing", "evaluationId": 287 },
        "subcontractorId": 44,
        "name": "Instalaciones Norte SAS",
        "email": "contacto@example.test",
        "nit": "900123456",
        "scope": "Redes hidraulicas",
        "providerType": "Suministro e Instalacion",
        "providerWeeks": 8,
        "latestEligibleWeek": 18,
        "evaluationWeek": 18,
        "atSelectedWeek": true,
        "persistence": "persisted",
        "metrics": {
          "pac": { "kind": "scored", "value": 0.92 },
          "completion": { "kind": "scored", "value": 0.84 },
          "quality": { "kind": "scored", "value": 1.0 },
          "socioEnvironmental": { "kind": "scored", "value": 0.75 },
          "safety": { "kind": "not-applicable" },
          "administration": { "kind": "not-rated" },
          "integral": { "kind": "scored", "value": 0.89 }
        },
        "accumulated": {
          "pac": { "kind": "scored", "value": 0.90 },
          "completion": { "kind": "scored", "value": 0.81 },
          "quality": { "kind": "scored", "value": 0.96 },
          "socioEnvironmental": { "kind": "scored", "value": 0.80 },
          "safety": { "kind": "not-applicable" },
          "administration": { "kind": "scored", "value": 0.70 },
          "integral": { "kind": "scored", "value": 0.86 }
        },
        "answers": {
          "questionnaireId": "si",
          "values": [
            { "questionId": "si.quality.1", "value": "full" }
          ]
        },
        "observations": "Pendiente cerrar administracion",
        "cadence": { "status": "due-incomplete", "missingDisciplines": ["administration"] },
        "performance": { "id": "below-target", "label": "Bajo objetivo" },
        "diagnostics": [],
        "actions": {
          "edit": { "allowed": true, "reasonCode": null },
          "disciplines": {
            "quality": { "allowed": true, "reasonCode": null },
            "administration": { "allowed": false, "reasonCode": "DISCIPLINE_FORBIDDEN" },
            "socio-environmental": { "allowed": false, "reasonCode": "DISCIPLINE_FORBIDDEN" },
            "safety": { "allowed": false, "reasonCode": "DISCIPLINE_FORBIDDEN" }
          }
        },
        "version": "cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc"
      }
    ],
    "counts": {
      "total": 1,
      "dueIncomplete": 1,
      "dueComplete": 0,
      "projected": 0,
      "withDiagnostics": 0
    }
  }
}
```

El contrato real exige un valor para cada pregunta del cuestionario aplicable, no solo el elemento
abreviado. `providerRef` es union discriminada:

```json
{ "mode": "existing", "evaluationId": 287 }
```

o:

```json
{ "mode": "projected", "subcontractorId": 44 }
```

No se devuelven `project_id`, prefijo, rol, SQL, HTML, nombres de columna ni respuestas del
cuestionario no aplicable. `rows=[]` es vacio valido. Los conteos son operacionales de S11, no KPI
BI. `version` es SHA-256 opaco sobre scope, corte, presencia, maestro, fila/ausencia, respuestas,
metricas y permisos relevantes; se recalcula bajo lock.

### Guardado de evaluacion

`POST /api/cic/evaluation` recibe JSON estricto y `X-CSRF-Token`:

```json
{
  "target": { "mode": "existing", "evaluationId": 287 },
  "version": "cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc",
  "questionnaireVersion": "sha256-opaque",
  "disciplines": [
    {
      "id": "quality",
      "answers": [
        { "questionId": "si.quality.1", "value": "full" },
        { "questionId": "si.quality.2", "value": "half" },
        { "questionId": "si.quality.3", "value": "not-applicable" }
      ]
    }
  ],
  "observations": "Se adjuntaran certificados actualizados"
}
```

Una proyeccion usa `target.mode=projected` y `subcontractorId`. Cada disciplina incluida debe traer
exactamente todas sus preguntas una vez. Puede incluir una o varias disciplinas sucias autorizadas.
Las ausentes no cambian. `disciplines=[]` solo se acepta para cambiar observacion si el usuario tiene
al menos una disciplina efectiva; un no-op responde `changed=false`.

PHP resuelve proyecto/corte/tipo/presencia, valida versiones y catalogo, bloquea, materializa cuando
corresponde, actualiza solo columnas de respuestas incluidas y observacion, recalcula el proveedor
afectado y devuelve:

```json
{
  "data": {
    "changed": true,
    "row": {},
    "counts": {
      "total": 1,
      "dueIncomplete": 0,
      "dueComplete": 1,
      "projected": 0,
      "withDiagnostics": 0
    }
  }
}
```

`row` cumple el esquema completo de proveedor. La respuesta trae version nueva. No hay retry
automatico ni cierre optimista antes de respuesta. Un conflicto conserva el borrador.

### Errores estables

Todos los errores usan:

```json
{
  "error": {
    "code": "EVALUATION_STALE",
    "message": "La calificacion cambio. Recarga antes de volver a guardar.",
    "fields": {}
  }
}
```

| HTTP | Codigo | Uso |
|---:|---|---|
| 401 | `AUTH_REQUIRED` | sesion ausente o vencida |
| 403 | `CIC_FORBIDDEN` | falta capacidad de ver/editar |
| 403 | `DISCIPLINE_FORBIDDEN` | disciplina fuera de acciones efectivas |
| 403 | `CSRF_INVALID` | token ausente o invalido |
| 404 | `PROVIDER_NOT_FOUND` | target fuera de scope o sin presencia elegible |
| 409 | `EVALUATION_STALE` | version, corte, presencia, maestro o fila cambio |
| 409 | `EVALUATION_ALREADY_MATERIALIZED` | una proyeccion fue creada concurrentemente |
| 409 | `QUESTIONNAIRE_STALE` | version del catalogo cambio |
| 409 | `DUPLICATE_CIC_ROWS` | target persistido ambiguo |
| 422 | `DISCIPLINE_INCOMPLETE` | faltan/sobran/repiten preguntas |
| 422 | `QUESTION_VALUE_INVALID` | valor fuera de enum |
| 422 | `PROVIDER_TYPE_UNSUPPORTED` | no hay formulario para el tipo |
| 422 | `PROVIDER_METADATA_INCOMPATIBLE` | maestro incompleto o no representable |
| 422 | `VALIDATION_FAILED` | JSON, limites o target invalido |
| 503 | `CIC_SCHEMA_PREREQUISITE_MISSING` | columnas/tablas canonicas ausentes |
| 500 | `CIC_UNAVAILABLE` | error interno sin detalles sensibles |

No existe error de ventana semanal o confirmacion en S11. El cliente comun conserva status, codigo y
`fields`; la UI no usa texto como logica. Ningun error expone SQL, tabla, prefijo, excepcion, ruta
interna o datos de otro proyecto.

## Permisos y acciones efectivas

### Resolucion servidor

La pagina, contexto y proveedores exigen `lps.cic.ver`. La mutacion exige `lps.cic.editar` y la
interseccion no vacia entre disciplinas enviadas y `RbacCatalog::cicDisciplinesForRole()` despues de
`RbacService::normalizeRole()`. Overrides persistidos se aplican en la capa RBAC existente.

El DTO ofrece acciones en dos niveles:

- contexto: capacidad general y disciplinas de la persona;
- fila: interseccion con tipo soportado, integridad de datos, ausencia de duplicado, presencia y
  estado del target.

Razones estables: `READ_ONLY`, `DISCIPLINE_FORBIDDEN`, `UNSUPPORTED_PROVIDER_TYPE`,
`MISSING_PROVIDER_METADATA`, `DUPLICATE_CIC_ROWS`, `NOT_ELIGIBLE_AT_CUTOFF` y null. React no ve rol,
no reconstruye matriz y no habilita por nombre de cargo.

### Sin politica de semana inventada

CIC es una evaluacion historica corregible segun capacidad/disciplinas. La semana activa selecciona
el corte y la ultima fila, pero no cierra edicion por antiguedad ni confirmacion. Esta decision
elimina la inconsistencia movil sin reducir capacidad del servidor/desktop. Si negocio desea una
ventana futura, requiere spec propia, migracion de politica y pruebas de todos los roles.

## Filtros, conteos, recarga y orden

La busqueda local normalizada cubre nombre, alcance, tipo, observacion, email y NIT. Los filtros se
combinan por interseccion:

- tipo: MDO, SI, otro/diagnostico;
- cadencia: pendiente, completa en corte, no exigible;
- completitud: todas respondidas, parcial/sin calificar, no aplica;
- rendimiento integral: cumple objetivo, bajo objetivo, critico, no disponible;
- disciplina faltante: Calidad, Administracion, Socioambiental, SST;
- persistencia: persistida o proyectada;
- relacion con corte: evaluacion en corte o anterior;
- calidad de datos: sin diagnostico o con diagnostico.

Los conteos servidor (`total`, `dueIncomplete`, `dueComplete`, `projected`, `withDiagnostics`) no
cambian con filtros locales. React calcula y anuncia `visible` y distribuciones visibles sobre la
misma coleccion normalizada. Restablecer filtros no hace request.

El orden por defecto es estable por nombre, ID y semana. La tabla permite ordenar de forma accesible
por proveedor, semanas, semana, PAC, avance, disciplina e integral sin mutar datos. Cambiar semana
aborta requests previos, reinicia pagina inexistente, conserva solo filtros que sigan validos y
anuncia el nuevo corte. Recarga manual conserva filtros y edicion cerrada; no duplica requests.

## Arquitectura React

### Modulo y estado

S11 crea `frontend/src/modules/cic/` con:

- dominio puro para valores, metricas, filtros, conteos y presentacion;
- hook/controlador de carga con `AbortController`, request IDs y union de estados;
- una coleccion normalizada de proveedores compartida por tabla y tarjetas;
- editor con borrador por disciplina, dirty tracking y version original;
- componentes separados de encabezado, filtros, conteos, leyenda, tabla, tarjetas y dialogo;
- CSS de modulo que consume solo tokens de `public/css/tokens.css`.

Solo `frontend/src/lib/api/cliente.ts` llama `fetch`. El gateway CIC usa ese cliente; esquemas Zod
son `.strict()` y todos los tipos salen de `z.infer`. No se añade store/global grid/query library.

### Tabla desktop/tablet

En `min-width: 768px` se monta una tabla semantica. Columnas primarias: proveedor, semanas, ultima
semana, tipo/alcance, PAC, avance, cuatro disciplinas, integral, cadencia, observacion y accion. En
tablet, metadatos/acumulados se consultan en fila expandible accesible, sin perderlos ni crear una
segunda representacion oculta. Cabeceras ordenables usan boton, `aria-sort` y foco visible.

Metricas muestran etiqueta textual ademas de color. `Sin calificar` y `No aplica` no se confunden
con 0 %. La fila pendiente por cadencia destaca su estado, pero conserva el rendimiento como campo
independiente. Accion denegada expone razon legible, no un boton muerto.

### Tarjetas moviles

Por debajo de 768 px se montan tarjetas nativas; la tabla no existe en DOM. Cada tarjeta contiene
nombre, alcance, tipo, semanas de presencia, ultima/evaluacion, PAC, avance, cuatro disciplinas,
integral, acumulados resumidos, cadencia, diagnosticos, observacion y la misma accion efectiva. Asi
se corrigen las dos omisiones legacy sin ampliar el dominio.

Orden, filtros y conteos son los mismos. El area tactil minima es 44 px. Las metricas tienen nombre
visible y no dependen de posicion/color.

### Editor de evaluacion

El editor es un dialogo accesible en desktop y una hoja/pantalla completa en movil. Contiene:

- identidad, tipo, alcance, semana y cadencia de solo lectura;
- una seccion/accordion por disciplina aplicable;
- secciones no autorizadas visibles como resumen de solo lectura, no escondidas;
- progreso `respondidas / total` por disciplina;
- cada pregunta dentro de `fieldset` con `legend` y cinco radios visibles;
- observacion compartida con limite documentado y contador;
- resumen de cambios y validacion antes de guardar;
- acciones fijas “Cancelar” y “Guardar cambios”.

Cancelar con borrador sucio solicita confirmacion local; no recarga red. Guardar permanece abierto y
ocupado hasta respuesta. Exito reemplaza fila/conteos, anuncia resultado y devuelve foco al
disparador. Error de campo enfoca el primer problema. Conflicto conserva borrador y ofrece recargar o
cancelar; recargar no mezcla respuestas viejas automaticamente.

No existe accion “marcar todo 100 %”, copiar otra disciplina ni guardar todos los proveedores: no
son capacidades legacy auditadas.

## Estados de experiencia

La pagina distingue:

- `loading-context` y `loading-providers` con skeletons estables;
- `ready`;
- `empty-real` sin proveedores elegibles;
- `empty-filtered` con boton para limpiar filtros;
- `read-only` con motivo y valores consultables;
- `schema-prerequisite-missing` sin autocuracion;
- `request-error` recuperable;
- `auth-expired` delegado al shell;
- `editing-pristine`, `editing-dirty`, `saving`, `save-error`, `conflict` y `saved`.

Estados por proveedor se modelan por ejes independientes:

| Eje | Valores |
|---|---|
| Persistencia | `persisted`, `projected` |
| Cadencia | `due-incomplete`, `due-complete`, `not-due` |
| Rendimiento | `meets-target`, `below-target`, `critical`, `not-applicable`, `not-rated` |
| Calidad de datos | normal, metadata faltante, tipo no soportado, duplicado |

La leyenda explica cada eje sin afirmar que un color implica permiso, bloqueo BI o estado de
contrato. `due-incomplete` es el unico estado que T01 usa como bloqueo de nueva semana.

## Seguridad, aislamiento y RLS

- Todas las consultas incluyen `project_id` por construccion del store; ningun ID global basta.
- Contexto de proyecto/semana proviene de sesion y servicios T01, no del body/query del navegador.
- Cada ruta exige auth, proyecto y capacidad antes de leer catalogo o filas.
- La mutacion exige CSRF, allowlist de preguntas, disciplina efectiva, scope, tipo, presencia, lock y
  version.
- La proyeccion se materializa dentro de una transaccion y revalida ausencia bajo lock.
- Consultas usan `Database`/prepared statements y columnas explicitas; no SQL con prefijo de usuario.
- Los GET no abren transaccion ni llaman stores de escritura. Un ledger de efectos lo prueba.
- Errores no filtran existencia de proveedor fuera del proyecto ni detalles internos.
- Observaciones se tratan como texto; React no inyecta HTML.
- No hay retry automatico de mutaciones ni persistencia de borradores sensibles en storage.
- Respuestas no exponen roles, grants, prefijos, `project_id` ajeno ni nombres de columnas.
- Esta spec consume la frontera RLS vigente; no cambia politicas, grants, usuarios o credenciales.
- Se prueba aislamiento con fakes de dos proyectos y el mismo ID/nombre, sin DML real.

## Tema, tokens y accesibilidad

- Oscuro es default/fallback; claro conserva igual jerarquia, estados, cuestionarios y acciones.
- Solo se usan tokens de `public/css/tokens.css`; no hex, rgba literal, inline style, `!important`,
  Bootstrap, Font Awesome o CSS-in-JS.
- Los tonos semanticos reutilizan aliases de estado existentes o se registran centralmente; no se
  crea paleta local CIC.
- `h1`, region de filtros, conteos, tabla/lista, leyenda y dialogo tienen estructura navegable.
- Inputs tienen `label`; preguntas usan `fieldset`/`legend`; errores se relacionan con
  `aria-describedby`.
- Dialogo atrapa foco, cierra con Escape salvo guardado activo y restaura foco al disparador.
- Cambios de semana, filtros, carga, guardado y errores se anuncian en live regions no duplicadas.
- Iconos decorativos se ocultan; iconos accionables tienen nombre accesible.
- Color nunca es la unica señal de rendimiento, cadencia, NR/NA o denegacion.
- Zoom 200 % y reflow 320 CSS px no pierden controles ni crean overflow horizontal de pagina.
- `prefers-reduced-motion` elimina transiciones no esenciales.
- Tutorial externo indica que abre otra pestaña y usa `target=_blank rel=noopener noreferrer`.
- Viewports obligatorios: `390x844`, `768x1024`, `1180x820`, `1440x900` en oscuro y claro.

## Navegacion y convivencia strangler

### Piloto

Una ruta piloto interna sirve `CicPage` dentro del shell sin cambiar la URL canonica legacy. Las tres
APIs nuevas coexisten con los dos POST legacy; sus servicios nuevos no delegan en `list()` porque
esa funcion escribe. La prueba de rollback confirma que la ruta canonica sigue abriendo VIEW-36.

### Corte

Despues de gates funcionales, RBAC, aislamiento, responsive, accesibilidad y visual aprobado:

1. `/programacion-semanal/cic` GET/HEAD pasa al SPA router;
2. sidebar y navegacion semanal apuntan a ruta React normal;
3. se conserva un rollback de ruta probado;
4. se confirma cero llamadas a `/api/cic/list` y `/api/cic/save`;
5. se retiran VIEW-36, sus handlers y APIs legacy exclusivas;
6. se retiran ramas CIC de `legacyCards.js`; si no quedan consumidores, se elimina el archivo;
7. se retiran selectores CSS y assets solo con inventario de cero consumidores;
8. se actualizan manifests/design system/rutas y se verifica que no quede HTML oculto duplicado.

El corte no elimina generacion/cadencia compartida que S08/T01 ya consumen. No toca otras vistas.

## Estrategia de pruebas

### PHP sin base mutable

Pruebas puras caracterizan:

- 59 preguntas, orden, claves, textos, columnas y opciones;
- matriz de disciplinas para todos los roles canonicos y aliases;
- puntajes con NR/NA/numericos;
- integral en 16 combinaciones de disponibilidad y limites PAC;
- presencia distinta, ultima evaluacion, proyeccion y orden estable;
- cadencia 7/8/9/16 y N/A como respondido;
- diagnostico de tipo/metadata/duplicado;
- recalculo hacia delante del unico proveedor;
- version determinista y cambio ante cualquier evidencia relevante.

Cada endpoint tiene prueba PHP de contrato con fake stores. Contexto/proveedores comprueban ledger
sin writes/transactions/DDL. Evaluacion cubre update, materializacion, no-op, disciplina parcial,
disciplina prohibida, scope cruzado, stale, concurrent materialization y error estable. No se conecta
a base real.

### Vitest y Testing Library

- Zod acepta fixtures completos y rechaza claves extra, respuestas faltantes, enums y metricas
  ambiguas.
- Gateway usa exactamente tres rutas y `cliente.ts`; no hay `fetch` en componentes.
- Dominio filtra, cuenta, ordena y formatea NR/NA/0 sin perder distincion.
- Hook aborta respuestas obsoletas, no duplica carga y preserva filtros/borrador.
- Tabla y tarjetas prueban paridad de campos/acciones.
- Editor prueba permisos por disciplina, 26/33 preguntas, validacion, foco, cancelacion, exito, error
  y conflicto.
- Temas consumen tokens; no se afirma contraste solo por presencia de clase.

### Playwright completamente interceptado

La fixture instala interceptacion antes de navegar para sesion, T01 y los tres endpoints S11. Carga
cuatro roles efectivos, MDO/SI, proyeccion, octava semana, historial anterior, NR/NA/0, diagnostico,
conflicto y error. Cualquier request no declarado o cualquier alias legacy falla.

Escenarios:

1. lectura, busqueda, filtros, conteos y recarga en corte;
2. tabla en 1180/1440 y tablet 768 sin overflow;
3. tarjetas 390 con todos los campos que desktop muestra;
4. MDO 26 y SI 33, opciones visibles y navegacion de teclado;
5. matriz A/D/R/G/SG/S/OT/DCV/V y razon de solo lectura;
6. guardar una disciplina y varias, observacion-only y no-op;
7. materializar proyeccion y reemplazarla por fila persistida;
8. conflicto conserva borrador y recarga deliberada;
9. cadencia pendiente bloqueable compartida con fixture T01, sin invocar write real;
10. vacio real, filtros sin resultados, schema ausente, 401, 403 y 500;
11. tutorial seguro y navegacion interna React;
12. Axe, foco, live regions, zoom/reflow, reduced motion y oscuro/claro.

Goldens son candidatos hasta aprobacion explicita. Se revisan consola y red en cada escenario.

### Verificaciones reales prohibidas

No ejecutar como evidencia de S11:

- `tests/browser/cic-role-disciplines.mjs`;
- casos CIC mutables de `tests/browser/programacion-semanal-subviews.mjs`;
- `tests/browser/programacion-semanal-sprint.mjs`;
- `tests/test_csrf_modulos_api.php` contra runtime real;
- `POST /api/cic/list`, porque escribe aunque se llame lista;
- `POST /api/cic/save` con datos reales;
- crear/cerrar semana real para probar la cadencia;
- cualquier restauracion por rollback SQL, pues tambien es DML.

## Criterios de aceptacion

1. `/programacion-semanal/cic` exige sesion, proyecto y `lps.cic.ver` desde el primer byte.
2. La SPA no llama `fetch` fuera de `frontend/src/lib/api/cliente.ts`.
3. Existen exactamente tres endpoints nuevos, cada uno con Zod estricto y contrato PHP.
4. Los GET de contexto/proveedores no insertan, actualizan, reparan, recalculan almacenamiento, abren
   transaccion ni ejecutan DDL.
5. La poblacion usa presencias semanales scoped hasta el corte y excluye el tipo material exacto.
6. Cada proveedor muestra la ultima evaluacion `<=` corte o una proyeccion no persistida.
7. `providerWeeks` cuenta semanas distintas de presencia, no filas CIC creadas.
8. Proyecciones no reciben ID CIC falso y solo se materializan al guardar explicitamente o en S08.
9. La materializacion revalida bajo lock, crea una fila y no duplica ante carrera.
10. Tipos desconocidos, metadata incompatible y duplicados son visibles de solo lectura, sin
    autocuracion.
11. El catalogo contiene exactamente 26 preguntas MDO y 33 SI, con 59 claves/columnas unicas.
12. Las opciones `Sin calificar`, `0 %`, `50 %`, `100 %` y `N/A` son visibles y seleccionables.
13. Una disciplina enviada contiene todas y solo sus preguntas una vez.
14. Puntajes distinguen `NR`, `NA` y cero, e ignoran NR/NA en promedios numericos.
15. La integral conserva pesos y redistribucion legacy caracterizados en todas las combinaciones.
16. PAC, avance, disciplinas e integrales actuales/acumuladas se muestran sin cambiar KPI BI.
17. Editar una semana historica recalcula solo el proveedor afectado desde esa semana hacia delante.
18. La cadencia usa cada octava presencia y considera N/A respondido; `due-incomplete` coincide con
    el bloqueo compartido de T01.
19. El servidor entrega acciones generales, por disciplina y por fila; React no contiene matriz de
    roles.
20. A/D, R, G, SG, S y OT reciben solo sus disciplinas auditadas; DCV/V son efectivos de lectura.
21. CIC no aplica una ventana semanal ni confirmacion que el servidor legacy no aplica.
22. Busqueda cubre proveedor, alcance, tipo, observacion, email y NIT.
23. Filtros combinables cubren tipo, cadencia, completitud, rendimiento, disciplina, persistencia,
    relacion con corte y diagnostico.
24. Conteos total/visible, pendiente/completo, proyectado y diagnostico se mantienen coherentes.
25. Tabla `>=768px` y tarjetas `<768px` usan una coleccion y solo una representacion montada.
26. Movil conserva semanas de presencia y porcentaje completado ademas del resto de datos desktop.
27. El editor ofrece 26/33 preguntas, secciones autorizadas, resumen read-only, progreso y dirty
    tracking accesible.
28. Guardar espera respuesta, devuelve fila/conteos completos y no reintenta automaticamente.
29. Conflicto conserva borrador; error enfoca/anuncia y exito restaura foco al disparador.
30. Carga, vacio, sin resultados, solo lectura, schema ausente, 401, 403, 409, 422 y 500 tienen
    estados diferenciados.
31. Tutorial abre de forma segura y navegacion semanal usa rutas React resueltas por servidor.
32. Oscuro y claro tienen igual capacidad, tokens unicos, contraste, foco, zoom, reduced motion y
    ausencia de overflow en los cuatro viewports.
33. Pruebas PHP usan fakes/ledgers y Playwright intercepta toda red antes de navegar; ninguna
    verificacion hace DML real.
34. Tras el corte no quedan VIEW-36, APIs legacy, ramas CIC, HTML duplicado ni assets exclusivos con
    cero consumidores; rollback de ruta fue probado antes.

## Entregas verticales

### Entrega 1 — Dominio, catalogo y lectura pura

Caracteriza 59 preguntas, puntajes, integral, presencia, cadencia, contexto y proveedores con fakes.
Entrega pagina read-only con proyecciones y diagnosticos, sin cortar legacy.

### Entrega 2 — Exploracion responsive

Agrega filtros, conteos, orden, semana, navegacion, tutorial, tabla, tarjetas y leyenda en oscuro y
claro. Demuestra paridad de informacion entre viewports.

### Entrega 3 — Evaluacion transaccional

Agrega editor por disciplina, materializacion explicita, guardado parcial autorizado, recalculo,
version, conflictos y recuperacion. Integra la politica de cadencia compartida con T01.

### Entrega 4 — Calidad y corte

Cierra RBAC, aislamiento, accesibilidad, red, visuales aprobados, rollback, ruta canonica y retiro
selectivo de VIEW-36/APIs/assets legacy.

## Riesgos y mitigaciones

| Riesgo | Mitigacion |
|---|---|
| Cambiar datos al leer | stores separados, GET con fake ledger que falla ante write/transaccion |
| Duplicar proveedores proyectados | lock + revalidacion + conflicto de materializacion |
| Derivar cadencia distinta a T01 | una politica pura compartida y fixtures contractuales comunes |
| Perder una de 59 preguntas | catalogo exhaustivo con conteo, unicidad, hash y test de columnas |
| Tratar NR/NA como cero | union discriminada y tests de todas las combinaciones |
| Cambiar integral/BI | caracterizacion exhaustiva y frontera explicita de ReportProcessor |
| Permitir disciplina indebida | acciones servidor + allowlist por catalogo + fake por cada rol |
| Mutar otra disciplina al guardar | payload dirty-only y update allowlist exacto |
| Romper acumulados historicos | recalculo determinista del proveedor desde semana afectada |
| Truncar NIT/metadata | validacion de compatibilidad y error estable, sin autocuracion |
| Diferencia desktop/movil | una coleccion, matriz de campos compartida y test de paridad |
| Cortar asset aun compartido | `rg`/manifest de consumidores y retiro solo con cero usos |
| Probar con DML real | fakes PHP, red Playwright interceptada y lista de comandos prohibidos |

## Decisiones descartadas

- Conservar `POST /api/cic/list` para React: escribe y viola lectura idempotente.
- Ejecutar autocuracion antes del GET: mantiene el efecto oculto aunque cambie el nombre.
- Crear todas las filas proyectadas al cargar: convierte navegacion en batch no autorizado.
- Omitir proveedores no persistidos: perderia capacidad observable que legacy fabrica al abrir.
- Usar filas CIC como semanas de presencia: perpetua dependencia de efectos historicos.
- Enviar las 59 columnas en cada proveedor: expone storage y respuestas no aplicables.
- Hardcodear preguntas en React: crea dos fuentes que pueden divergir.
- Hacer NR invisible: impide deshacer una respuesta y falla la paridad pedida.
- Tratar todos los tipos no MDO como SI: permite editar datos con formulario equivocado.
- Mantener la ventana movil para R: contradice desktop y servidor.
- Aplicar `LpsWeekEditPolicy`: reduce capacidad sin decision de negocio.
- Guardar formulario completo siempre: arriesga disciplinas no autorizadas/no modificadas.
- Autoguardar cada radio: multiplica mutaciones y no existe en legacy.
- Guardado por lote entre proveedores: no existe en VIEW-36.
- Crear historial cronologico nuevo: amplia producto sin evidencia.
- Reusar umbrales BI para colores CIC: ambos contratos tienen semanticas distintas.
- Modificar schema para `si_adm_6` en runtime: DDL oculto y fuera de alcance.
- Ejecutar tests legacy mutables y restaurar: la restauracion sigue siendo DML.

## Decisiones pendientes

Ninguna. Los limites de producto, arquitectura, permisos, calculo, responsive, accesibilidad,
contratos y pruebas quedan cerrados para planificacion. Cualquier cambio futuro de ventana semanal,
tipos, preguntas, formula, cadencia o historial es una decision de negocio nueva.

## Siguiente gate

Invocar `superpowers:writing-plans` y producir
`docs/superpowers/plans/2026-08-30-s11-cic-react.md` con tareas TDD, archivos exactos, contratos PHP,
Zod, pruebas interceptadas, checkpoints verticales y corte reversible. No implementar hasta que el
programa documental autorice la fase de ejecucion.
