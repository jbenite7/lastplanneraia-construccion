---
capa: fuente
tipo: spec
estado: vigente
id: S15
fecha: 2026-08-31
superficie: control-cambios
rutas:
  - "/control-cambios"
  - "/api/control-cambios/context"
  - "/api/control-cambios/list"
  - "/api/control-cambios/save"
  - "/reportes/consolidado-odc"
depende_de: [T01, S13, S16, D16]
views: [VIEW-10]
areas: [lps, rbac, design-system]
fuente: "auditoria de public/index.php, ControlCambiosController, ControlCambiosApiController, ReportController, RbacCatalog, Database, schema global, VIEW-10, CSS, manifiesto, pruebas y frontend actual en shell-minimo-react, 2026-08-31"
resumen: "Migracion vertical S15 de Control de Cambios a la SPA React: lista project-scoped, filtros y conteos, recuperacion aprobada de CRUD, impactos, calculos, fechas, estados, soportes URL, PDF individual, XLSX consolidado, tabla/tarjetas responsive, oscuro/claro y contratos Zod/PHP, sin semanas propias, uploads, RLS, schema ni datos."
---

# S15 — Control de Cambios en React

> **Estado:** diseño tecnico autorrevisado y decision-complete. No quedan decisiones de negocio,
> producto, estrategia o PM que bloqueen su plan. Esta spec no autoriza implementacion, commits,
> DDL/DML, cambios RLS, cambios de permisos, deploy, publicacion ni trabajo en /admin/. Su plan se
> escribe a continuacion con superpowers:writing-plans dentro del programa aprobado de 27 specs y
> 27 planes.

## Relacion con el programa

S15 desarrolla VIEW-10, views/control-cambios/controlCambios.view.php, sus APIs y sus dos salidas
documentales. Consume:

- T01 para sesion, proyecto activo, sidebar, tema, cliente HTTP, limites de ruta y administracion
  de semanas;
- S13 para patrones compartidos de dialogo, cola de estado, recarga y exportacion cuando ya existan;
- S16 solo como siguiente superficie del lote, sin compartir dominio;
- D16 como propietario transversal de reportes y descargas.

Control de Cambios es project-scoped, no week-scoped. Puede mostrar la semana del shell como
contexto general, pero no posee selector, creacion ni eliminacion de semanas. Las opciones legacy
nueva_sem y eliminar_sem salen del API S15 y se transfieren al contrato T01 antes de retirar el
multiplexor.

La ruta de pagina termina protegida por lps.control_cambios.ver. Las acciones de escritura dependen
de lps.control_cambios.editar y la descarga consolidada depende, de manera independiente, de
lps.reportes.generar. React consume acciones resueltas por servidor; nunca interpreta letras de rol.

## Resultado buscado

/control-cambios pasa a la SPA principal y:

1. carga exclusivamente las ordenes del proyecto activo;
2. presenta el resumen de 14 datos que legacy deja visibles;
3. conserva los once filtros conectados y activa el filtro de Observaciones que el markup dejo
   incompleto;
4. muestra total y coincidencias sin fila centinela;
5. abre el detalle completo desde cada fila o tarjeta;
6. mantiene lectura para quien solo puede ver;
7. recupera alta, edicion y eliminacion para quien puede editar;
8. conserva solicitante, prioridad, seis tipos, responsable, ocho impactos, cinco importes, cuatro
   fechas, cinco estados, datos historicos y soportes;
9. valida textos, fechas, decimales, cadena de costos, porcentajes y campos condicionales;
10. calcula porcentajes de cronograma y presupuesto sin divisiones indefinidas;
11. guarda cada orden atomicamente y devuelve su representacion canonica;
12. asigna consecutivos por proyecto en servidor y sin carrera MAX+1;
13. conserva Observaciones historicas al editar;
14. gestiona soportes como enlaces, no como archivos;
15. genera un PDF individual desde el registro canonico;
16. descarga el XLSX Consolidado ODC con permiso de reportes;
17. ofrece tabla contenida en desktop/tablet y tarjetas equivalentes en movil;
18. funciona en oscuro y claro, teclado, touch, zoom y lector de pantalla;
19. valida respuestas con Zod y contratos HTTP con PHP sin datos reales;
20. retira VIEW-10, DataTables y aliases solo despues de paridad y cero consumidores.

## Decision de recuperacion funcional

### Estado observable actual

VIEW-10 lista ordenes, ofrece filtros y abre un modal de consulta. Al abrirlo:

- deshabilita todos los controles;
- oculta Guardar;
- oculta Generar PDF;
- oculta Agregar soporte;
- informa que la edicion no esta disponible.

No hay boton de alta. El JavaScript historico que debia operar el formulario no existe en el arbol
alcanzable del repositorio. Por tanto, CRUD, soportes editables y PDF no son hoy capacidad visible.

### Evidencia de la capacidad diseñada

La recuperacion no inventa un dominio:

- el programa maestro aprobado exige alta, edicion, eliminacion, soportes, validaciones y PDF;
- VIEW-10 conserva el formulario completo, botones, modal de borrado, contadores y tabla de
  soportes;
- ControlCambiosApiController conserva nuevo, modificar y eliminar;
- cambios conserva todos los campos;
- RbacCatalog conserva acciones CREAR, MODIFICAR y ELIMINAR;
- ReportController conserva Consolidado ODC;
- las pruebas actuales ejercen mutaciones legacy, aunque de forma insegura contra datos reales.

### Decision

S15 define dos capas de paridad:

- **paridad visible:** lista, filtros, conteos, detalle readonly, estados y vacio;
- **recuperacion aprobada:** alta, edicion, eliminacion, soportes, PDF individual y XLSX.

La recuperacion usa solo contratos demostrables. Cuando el JavaScript ausente deja una regla sin
evidencia, esta spec la declara como decision explicita y verificable; no la presenta como conducta
legacy medida.

## Alcance

### Incluido

- GET /control-cambios como ruta SPA al corte.
- GET /api/control-cambios/context nuevo.
- GET /api/control-cambios/list objetivo.
- POST /api/control-cambios/list temporal durante convivencia.
- POST /api/control-cambios/save JSON discriminado.
- Form mode temporal del mismo save durante convivencia.
- Adaptacion JSON de POST /reportes/consolidado-odc.
- Lista ordenada por consecutivo.
- Catorce columnas de resumen.
- Doce filtros por columna.
- Conteo total y filtrado.
- Detalle completo.
- Alta, edicion y eliminacion.
- Solicitante y responsable con opcion Otro.
- Prioridad.
- Seis tipos de cambio.
- Ocho campos narrativos de impacto.
- Dias base y adicionales.
- Cinco importes.
- Cuatro fechas de negocio.
- Cinco estados de aprobacion.
- Observaciones historicas readonly.
- Soportes por URL.
- PDF individual.
- XLSX consolidado.
- Tabla desktop/tablet y tarjetas moviles.
- Carga, vacio, error, stale, readonly, dirty, guardando y conflicto.
- Oscuro y claro con tokens.
- Contratos PHP, Zod, Vitest/Testing Library y Playwright interceptado.
- Corte strangler, retiro exclusivo y rollback.

### Fuera de alcance

- Todo /admin/.
- Crear, eliminar, confirmar o cambiar semanas.
- Selector de semana dentro de Control de Cambios.
- Drawer contextual unificado: VIEW-10 no lo usa.
- Subir, alojar, borrar o escanear archivos.
- Crear una biblioteca documental.
- Cambiar campos, tabla, indices, claves o tipos de cambios.
- Cambiar capacidades o fallback RBAC.
- Cambiar RLS, ProjectScope, schema, grants, usuarios, credenciales o datos.
- Ejecutar migraciones, backfills, DDL, DML o pruebas con rollback real.
- Edicion por lote: la unidad de guardado es una orden.
- Autosave: el formulario usa guardado explicito.
- Busqueda global, paginacion u orden libre.
- Recalcular AIU o IVA desde porcentajes no almacenados.
- Crear versionado optimista en schema.
- Migrar el framework transversal de reportes D16.
- Cambiar el formato general de los demas reportes.
- Regenerar goldens sin aprobacion.
- Implementar, commitear o publicar en esta sesion documental.

## Punto de partida medido

### React

- No existe modulo, schema Zod, gateway, tabla, tarjetas, formulario ni pruebas S15.
- NavegacionLateral enlaza /control-cambios y oculta por una matriz local de roles.
- /control-cambios sigue sirviendo PHP.
- El shell ya aporta sesion, proyecto y tema; T01 debe sustituir la matriz local por navegacion
  server-authoritative.

### Inventario HTTP legacy

| ID | Metodo y ruta | Guard actual | Efecto |
|---|---|---|---|
| S15-LEG-01 | GET /control-cambios | autenticacion | renderiza VIEW-10 y carga semanas del shell |
| S15-LEG-02 | POST /api/control-cambios/list?db=... | control_cambios.ver | lista 29 campos o fila centinela |
| S15-LEG-03 | POST /api/control-cambios/save?db=... | control_cambios.editar + CSRF | multiplexa ocho opciones |
| S15-LEG-04 | GET/POST /reportes/consolidado-odc | reportes.generar | genera XLSX y devuelve URL |

La ruta de pagina no exige hoy la capacidad de vista. List y save confian en db del navegador.
Reportes acepta db de POST, GET o sesion. El target deriva proyecto exclusivamente de la sesion
activa y rechaza db, project_id o prefijo enviados como autoridad.

### Multiplexor save

| opcion legacy | Estado S15 |
|---|---|
| nuevo | se reemplaza por action=create JSON |
| modificar | se reemplaza por action=update JSON |
| eliminar | se reemplaza por action=delete JSON |
| obtenerNombreDirector | se integra a context |
| obtenerURLCambios | se integra a context |
| actualizarFechaInicio | se retira tras cero callers; no pertenece al modulo actual |
| nueva_sem | se transfiere a T01 |
| eliminar_sem | se transfiere a T01 |

Los modos form legacy permanecen solo durante el piloto. No se retiran hasta probar cero callers y
confirmar que T01 posee las operaciones de semana.

### VIEW-10

La vista carga jQuery, Bootstrap, DataTables y numerosos vendors no usados. Renderiza:

- inputs ocultos de prefijo, rol y semana;
- tabla dt_cliente;
- fila de filtros;
- mensaje Guardando/Error;
- modal de orden de cambio;
- modal de confirmacion de borrado;
- botones de cerrar, guardar, PDF y soportes;
- codigo de adaptacion readonly añadido por ausencia del JavaScript original.

DataTables desactiva paging y ordering. No activa busqueda global. El scroll vertical es propio, pero
scrollX esta desactivado y la tabla se recorta en el viewport canonico 1180x820.

### Columnas de resumen

El listado legacy transporta 29 campos, pero mantiene visibles estos 14:

1. consecutivo;
2. solicitante;
3. fecha de solicitud;
4. prioridad;
5. tipos;
6. responsable;
7. descripcion;
8. costo directo;
9. valor aprobado;
10. fecha tentativa de definicion;
11. fecha de entrega a interventoria;
12. fecha de definicion;
13. aprobacion;
14. accion de apertura.

El target conserva esos datos. Accion se representa como control accesible, no como columna de
negocio exportable.

### Filtros y conteos

La fila actual conecta once filtros:

1. solicitante;
2. fecha de solicitud;
3. prioridad;
4. tipos;
5. responsable;
6. costo directo;
7. valor aprobado;
8. fecha tentativa;
9. fecha de entrega;
10. fecha de definicion;
11. aprobacion.

Existe ademas un input de Observaciones sin label, id ni conexion. S15 lo recupera como filtro 12,
con etiqueta, nombre accesible y coincidencia sobre Observaciones historicas. Todos los filtros son
locales sobre la lista cargada. No se agrega busqueda global.

El resultado anuncia Total y Mostrando. La fila centinela en vacio se elimina. La copia vacia
ratificada es:

> Las solicitudes de cambio nacen en obra: cuando el diseño, el cliente o la interventoría piden
> algo distinto de lo contratado, regístralo aquí para tramitar su aprobación.

Solo se muestra una instancia del estado vacio.

## Dominio

### Identidad y orden

- id es entero positivo y consecutivo dentro del proyecto.
- project_id nunca cruza el wire.
- la clave real es project_id + id.
- lista usa ORDER BY id ASC.
- create no acepta un consecutivo elegido por navegador.
- server asigna el siguiente id dentro de una transaccion.

MAX(id)+1 aislado no es seguro. Sin cambiar schema, el servicio serializa la asignacion bloqueando
FOR UPDATE la fila del proyecto activo en general_proyectos_procesos, calcula MAX(id)+1 para ese
project_id e inserta antes de confirmar. Las pruebas usan un fake que demuestra begin, lock,
consulta, insert, log y commit en ese orden.

### Solicitante y responsable

Codigos exactos compartidos:

| Codigo | Etiqueta |
|---|---|
| 1 | Obra |
| 2 | Cliente |
| 3 | Interventoría |
| 4 | Otro |

Reglas:

- solicitante y responsable son requeridos;
- si el codigo es 4, el detalle correspondiente es requerido, trim y maximo 200 caracteres;
- si no es 4, el servidor persiste el detalle como null;
- un codigo fuera de 1..4 se rechaza;
- datos historicos invalidos se muestran como Desconocido (codigo) con warning, no rompen la lista.

### Prioridad

| Codigo | Etiqueta |
|---|---|
| 1 | Alta |
| 2 | Media |
| 3 | Baja |

Prioridad es requerida. Codigos historicos invalidos se degradan a etiqueta Desconocida con warning.

### Tipos

Los seis tipos exactos son:

- Alcance;
- Cronograma;
- Costo;
- Calidad;
- Riesgo;
- Recurso.

Persistencia conserva el objeto tiposCambio de seis flags. El wire objetivo entrega los seis nombres
seleccionados en orden canonico y el formulario los vuelve a flags al guardar. Debe seleccionarse al
menos uno. JSON historico malformado produce lista vacia mas warning de calidad; no produce HTML ni
ejecuta texto.

### Aprobacion y semantica

| Codigo | Etiqueta | Nivel |
|---|---|---|
| 1 | Aprobado | healthy |
| 2 | Aprobado con Restricciones | attention |
| 3 | No Aprobado | urgent |
| 4 | En Estudio | attention |
| 5 | Desistido | neutral |

La semantica visual consume docs/design-system/state-semantics.json y tokens. No se transmite solo
por color.

### Campos narrativos

Los ocho textareas con contador y limite 500 son:

1. justificacion;
2. descripcion;
3. incidenciaAlcance;
4. incidenciaCronograma;
5. incidenciaPresupuesto;
6. incidenciaCalidad;
7. incidenciaRiesgo;
8. incidenciaRecurso.

Justificacion y descripcion son requeridas. Los seis impactos pueden quedar vacios. Todos reciben
trim, conservan saltos de linea y rechazan mas de 500 caracteres tanto en cliente como servidor.
Nunca se renderizan como HTML.

Observaciones es un campo historico independiente:

- se devuelve como string o null;
- se muestra readonly en Detalle historico;
- participa en el filtro recuperado;
- update no lo incluye en SET;
- guardar jamas lo pone en null;
- React no ofrece editor mientras no exista contrato aprobado.

### Cronograma

Campos:

- tiempoCronograma: dias base;
- tiempoCronogramaAfectado: dias adicionales;
- incidenciaCronograma: explicacion.

Ambos dias son decimales finitos, no negativos y viajan como strings decimales canonicos. El
porcentaje derivado:

- base > 0: adicionales / base * 100;
- base = 0 y adicionales = 0: 0%;
- base = 0 y adicionales > 0: No calculable.

El porcentaje nunca se persiste. Se muestra con maximo dos decimales y no se usa Infinity o NaN.

### Presupuesto

Campos:

- valorPresupuesto;
- costoDirecto;
- costoDirectoAIU;
- costoDirectoAIUIVA;
- valorAprobado;
- incidenciaPresupuesto.

Todos son decimales finitos, no negativos y viajan como strings. El servidor acepta punto decimal
canonico; la UI puede formatear para locale sin enviar simbolos ni separadores ambiguos.

Cadena de costos:

- costoDirecto <= costoDirectoAIU;
- costoDirectoAIU <= costoDirectoAIUIVA.

No se infieren tasas de AIU ni IVA. Valor aprobado no se obliga a ser parte de esa cadena.

Porcentaje de aprobacion presupuestal:

- presupuesto > 0: valorAprobado / valorPresupuesto * 100;
- ambos en cero: 0%;
- presupuesto = 0 y aprobado > 0: No calculable.

El porcentaje es derivado y no se persiste.

### Fechas

Campos:

- fechaSolicitud;
- fechaEntregaInterventoria;
- fechaTentativaDefinicion;
- fechaDefinicion.

Decision explicita de validacion, necesaria porque el JavaScript original no esta:

- create fija fechaSolicitud a la fecha local del servidor y no acepta otra;
- update no cambia fechaSolicitud;
- las otras tres fechas aceptan ISO YYYY-MM-DD o null;
- fechaEntregaInterventoria y fechaTentativaDefinicion no pueden ser anteriores a fechaSolicitud;
- estados finales 1, 2, 3 y 5 requieren fechaDefinicion;
- En Estudio, codigo 4, exige fechaDefinicion null;
- fechaDefinicion no puede ser anterior a fechaSolicitud;
- no se impone orden entre entrega, tentativa y definicion porque el dominio actual no lo demuestra.

Datos historicos que violen estas reglas siguen visibles con warning. La regla se aplica al siguiente
guardado; React no los modifica silenciosamente.

### Soportes

Persistencia legacy usa JSON:

    {"soportes":[{"consecutivo":1,"descripcion":"...","link":"https://..."}]}

El target lo convierte a una lista estricta:

- de 0 a 20 items;
- order entero derivado por servidor, 1..n;
- descripcion requerida, trim, maximo 200;
- link absoluto http o https, maximo 2048;
- no data:, javascript:, file:, credenciales embebidas ni URL relativa;
- enlaces externos usan target seguro y rel noreferrer;
- el usuario puede agregar, reordenar y retirar filas dentro del formulario;
- no hay carga de archivos;
- supportFolderUrl de contexto es enlace de conveniencia readonly, no destino automatico.

JSON historico malformado o enlaces inseguros no rompen el detalle: se muestran como texto no
accionable con warning. El siguiente guardado exige corregirlos o retirarlos.

## Contratos HTTP objetivo

### Convencion

Todos los endpoints:

- exigen sesion y proyecto activo;
- derivan project_id en servidor;
- responden application/json;
- usan success boolean, data y error estable;
- no exponen prefijo, SQL, paths, stack ni mensajes de excepcion;
- rechazan autoridad cliente db, project_id, Base_de_Datos o role;
- tienen schema Zod strict en frontend;
- tienen prueba PHP de request, respuesta, guard y scope;
- no hacen DML en tests de contrato.

Error:

    {
      "success": false,
      "error": {
        "code": "VALIDATION_ERROR",
        "message": "Revisa los campos indicados.",
        "fields": {"description": "Maximo 500 caracteres."},
        "requestId": "..."
      }
    }

fields es opcional. Los codigos previstos son UNAUTHENTICATED, FORBIDDEN, PROJECT_REQUIRED,
VALIDATION_ERROR, NOT_FOUND, CONFLICT y INTERNAL_ERROR.

### GET /api/control-cambios/context

Guard: lps.control_cambios.ver.

Respuesta:

    {
      "success": true,
      "data": {
        "project": {"id": 18, "name": "Proyecto"},
        "directorName": "Nombre" ,
        "supportFolderUrl": "https://..." ,
        "requesters": [{"value": 1, "label": "Obra"}],
        "priorities": [{"value": 1, "label": "Alta"}],
        "changeTypes": ["Alcance", "Cronograma", "Costo", "Calidad", "Riesgo", "Recurso"],
        "approvalStates": [{"value": 4, "label": "En Estudio", "level": "attention"}],
        "limits": {"narrative": 500, "detailOther": 200, "supportDescription": 200, "supports": 20},
        "actions": {
          "view": true,
          "create": true,
          "edit": true,
          "delete": true,
          "generatePdf": true,
          "exportXlsx": true
        },
        "csrfToken": "...",
        "reportCsrfToken": "..."
      }
    }

directorName, supportFolderUrl y tokens pueden ser null. Requesters y responsables comparten el
mismo catalogo. create/edit/delete dependen de editar. generatePdf depende de ver porque es una
representacion local del dato ya visible. exportXlsx depende solo de reportes.generar.

supportFolderUrl se valida como http/https antes de salir. Un valor legado inseguro se vuelve null.
No se consulta por opcion mutante.

### GET /api/control-cambios/list

Guard: lps.control_cambios.ver. Es una lectura pura y sin body.

Respuesta:

    {
      "success": true,
      "data": {
        "items": [],
        "total": 0
      }
    }

Cada item incluye:

- id;
- requester {value,label,detail};
- requestDate;
- priority {value,label};
- changeTypes;
- responsible {value,label,detail};
- justification;
- description;
- impacts {scope,schedule,budget,quality,risk,resource};
- schedule {baseDays,additionalDays,percentage};
- budget {baseline,direct,directAiu,directAiuIva,approved,percentage};
- dates {tentativeDefinition,deliveryToInterventoria,definition};
- approval {value,label,level};
- historicalObservations;
- supports;
- warnings.

Decimales son strings canonicos. Fechas son ISO o null. percentage es un objeto derivado
{kind:"value", value:"12.5"} o {kind:"not_calculable"}. warnings es una lista de codigos conocidos.
No hay fila centinela. total equivale a items.length y la lista usa id ASC.

POST /api/control-cambios/list permanece como alias temporal, con respuesta legacy mientras VIEW-10
sea consumidora. La SPA usa GET.

### POST /api/control-cambios/save

Guard: lps.control_cambios.editar + CSRF. Content-Type JSON.

Union discriminada:

    {"action":"create","change":{...}}
    {"action":"update","id":12,"change":{...}}
    {"action":"delete","id":12}

change usa el dominio anterior y no acepta id, projectId, requestDate, historicalObservations,
percentage, labels ni levels. Create fija id y fecha. Update conserva ambos. Delete exige id entero
positivo.

Respuesta create/update:

    {"success":true,"data":{"item":{...canonico...}}}

Respuesta delete:

    {"success":true,"data":{"deletedId":12}}

Semantica:

- valida antes de abrir transaccion;
- create serializa id por proyecto;
- update/delete revalidan project_id + id;
- no existe update parcial;
- no hay exito si rowCount demuestra ausencia;
- write y logActivity pertenecen a una unidad atomica;
- commit precede la respuesta;
- rollback ocurre en fallo;
- una accion por request;
- doble envio se bloquea en cliente y el servidor no confia en ello.

No se agrega revision porque schema no ofrece version. Si una fila deja de existir, NOT_FOUND. Si
un identificador ya se uso durante la asignacion, CONFLICT y reintento controlado dentro del
servicio, nunca otro proyecto.

El form legacy y opcion permanecen temporalmente, pero pasan por el mismo servicio/validador.

### POST /reportes/consolidado-odc

Guard: lps.reportes.generar + sesion + proyecto activo + CSRF de reportes. La SPA envia body vacio
JSON o un objeto vacio y nunca db.

Respuesta:

    {
      "success": true,
      "data": {
        "url": "/public/storage/ordenes/archivo.xlsx",
        "filename": "archivo.xlsx",
        "rowCount": 12
      }
    }

El generador conserva 14 columnas:

1. Id;
2. Solicitante;
3. Fecha Solicitud;
4. Prioridad;
5. Tipo de Cambio;
6. Responsable;
7. Descripcion;
8. Dias Afectacion Cronograma;
9. Costo Directo + AIU + IVA;
10. Valor Aprobado;
11. Fecha Tentativa de Definicion;
12. Fecha de Entrega a Interventoria;
13. Fecha de Definicion;
14. Aprobacion.

Se corrige la referencia inexistente inputTiempoCronogramaAfectado por
tiempoCronogramaAfectado. Orden id ASC, cinco estilos de estado y formatos actuales permanecen.
D16 puede luego extraer la infraestructura sin cambiar este contrato.

La compatibilidad db/form/GET se conserva exclusivamente para consumidores legacy hasta cero
callers. La respuesta objetivo no filtra errores SQL. Tests usan exportador y filesystem fake; no
crean public/storage/ordenes ni archivos reales.

## Permisos y capacidades

### Matriz fallback medida

| Rol normalizado | Ver | Editar |
|---|---:|---:|
| A | si | si |
| D | si | si |
| R | si | si |
| DCV | si | si |
| OT | si | no |
| V | si | no |
| G | no | no |
| S | no | no |
| SG | no | no |
| C | no | no |

P normaliza a D. Overrides RBAC son autoritativos. Esta tabla documenta fallback, no se codifica en
React.

lps.reportes.generar se evalua aparte incluso si el fallback actual coincide con roles de vista.
El servidor puede devolver exportXlsx false para un usuario que ve Control de Cambios.

### Comportamiento por capacidad

- sin ver: ruta y APIs 403; T01 omite entrada;
- ver sin editar: lista, detalle y PDF individual; no alta, guardar ni eliminar;
- editar: alta, guardar y eliminar;
- reportes.generar: XLSX visible;
- perder permiso durante la sesion: el siguiente request responde FORBIDDEN, se descarta estado de
  guardado y T01 ofrece salida segura;
- ningun boton oculto sustituye la autorizacion server-side.

## Arquitectura React

Ruta propuesta:

    frontend/src/control-cambios/
      api/
        controlCambiosApi.ts
        controlCambiosSchemas.ts
      components/
        ControlCambiosToolbar.tsx
        ControlCambiosFilters.tsx
        ControlCambiosTable.tsx
        ControlCambiosCards.tsx
        ChangeOrderDialog.tsx
        GeneralSection.tsx
        ImpactSection.tsx
        ApprovalSection.tsx
        SupportsEditor.tsx
        DeleteChangeDialog.tsx
        ChangeOrderPdfButton.tsx
      domain/
        changeOrder.ts
        calculations.ts
        filters.ts
        validation.ts
      hooks/
        useControlCambios.ts
        useChangeOrderForm.ts
      pages/
        ControlCambiosPage.tsx
      control-cambios.css

Limites:

- pagina orquesta contexto/lista;
- gateway es la unica capa que usa cliente.ts;
- schemas validan antes del dominio;
- dominio es puro y reusable por PHP-contract fixtures;
- filtros no hacen requests;
- tabla/tarjetas no guardan;
- dialogo posee draft y validacion;
- PDF recibe un item canonico y no consulta red;
- XLSX llama al gateway de reportes;
- estados compartidos del shell no se duplican.

No se incorpora DataTables, jQuery, Bootstrap, jsPDF, html2canvas, Google Charts, AnyChart, Select2,
Tabulator ni numeral. Para PDF se usa pdfmake ya presente en el repositorio, instalado/declarado de
forma explicita en el paquete frontend durante implementacion y encapsulado tras un generador puro.

## Interfaz

### Encabezado

Incluye:

- titulo Control de Cambios;
- proyecto desde T01;
- total y coincidencias;
- Nueva orden si create;
- Recargar;
- Descargar consolidado si exportXlsx;
- ayuda corta.

No incluye semana editable. Director aparece en Info General, no como selector.

### Tabla desktop/tablet

Desde 768 px:

- tabla semantica con los 13 datos de negocio resumidos y accion;
- cabecera visible;
- filtros asociados a sus columnas o al panel accesible;
- contenedor con overflow-x auto propio;
- la pagina no adquiere overflow horizontal;
- columnas accion e id pueden ser sticky;
- filas no son el unico control: Abrir tiene button y nombre accesible;
- Enter/Space abren sin depender de click de fila;
- estados tienen texto y badge;
- montos conservan valor accesible sin simbolos en el nombre;
- no hay paginacion, sorting ni virtualizacion.

La tabla puede exceder el ancho del contenedor; el scroll pertenece al region con tabindex y nombre
Control de Cambios. En 1180x820 no se recorta contra el viewport.

### Tarjetas moviles

Por debajo de 768 px:

- una tarjeta por orden;
- id, solicitante, fecha, prioridad, tipos, responsable, descripcion, costo directo, aprobado,
  tres fechas y aprobacion;
- boton Ver o Editar segun capacidad;
- mismos filtros en panel;
- mismos conteos;
- no se omite ninguna accion autorizada;
- targets minimo 44x44;
- solo se monta la rama tarjetas.

No se renderiza tabla oculta junto a tarjetas.

### Dialogo de orden

Modos:

- create;
- edit;
- read.

Desktop/tablet usa dialogo grande con scroll interno. Movil usa panel full-screen accesible. Ambos
mantienen:

1. Informacion General;
2. Detalle del cambio;
3. Aprobacion;
4. Soportes;
5. Detalle historico si aplica.

Create muestra consecutivo Pendiente de asignar y fecha del servidor al guardar. Edit/read muestran
id y fecha readonly. Director es readonly.

Guardar valida todos los campos, lleva foco al resumen y enlaza cada error con su control. Mientras
guarda, deshabilita el submit y mantiene Cancelar. En error conserva draft. En exito reemplaza la
fila por item canonico y cierra solo tras feedback. Delete usa dialogo secundario, nombra el id y
requiere confirmacion explicita.

Cerrar con draft modificado abre confirmacion Descartar cambios / Seguir editando. Escape no
descarta silenciosamente. Read cierra sin confirmacion.

### PDF individual

Disponible para quien puede ver. Se genera desde el item canonico ya cargado, sin request adicional:

- proyecto;
- director si existe;
- id y fechas;
- solicitante, prioridad, tipos y responsable;
- ocho narrativas;
- cronograma y porcentaje;
- cinco importes y porcentaje;
- aprobacion;
- Observaciones historicas si existen;
- soportes como texto/enlaces.

Nombre:

    orden-cambio-{id}-{proyecto-seguro}.pdf

El generador escapa texto, no interpreta HTML, no obtiene imagenes/URLs remotas y no incluye tokens
ni metadatos de sesion. Links inseguros se imprimen como texto. Error de generacion deja el dialogo
abierto y anuncia reintento.

### Recarga y XLSX

Recargar:

- repite context/list GET segun invalidacion;
- no muta;
- conserva filtros si la recarga funciona;
- si hay draft dirty pide confirmacion antes de reemplazar;
- conserva datos anteriores ante error y muestra estado stale.

Descargar consolidado:

- solo aparece con exportXlsx;
- anuncia preparacion;
- evita doble envio;
- navega a la URL validada same-origin;
- muestra rowCount cuando sea util;
- maneja fallo sin abrir popup vacio.

## Estados y feedback

| Estado | Comportamiento |
|---|---|
| loading inicial | skeleton de toolbar y filas/tarjetas; aria-busy |
| vacio | una copia ratificada y CTA solo si create |
| error inicial | mensaje estable y Reintentar |
| stale | conserva lista, banner y Reintentar |
| filtrado vacio | Sin coincidencias + Limpiar filtros |
| readonly | detalle y PDF; controles de escritura ausentes |
| dirty | indicador Cambios sin guardar y guard de cierre |
| invalid | resumen + mensajes por control |
| saving | submit bloqueado, draft estable |
| saved | anuncio polite y item canonico |
| deleting | confirmacion bloqueada |
| deleted | fila retirada, conteos recalculados |
| forbidden | aviso y retorno seguro del shell |
| malformed historical | dato visible, warning no bloqueante hasta editar |

No hay alert() nativo, toast como unica evidencia ni spinner sin nombre.

## Seguridad, alcance y RLS

- ProjectScope de sesion es autoridad.
- project_id aparece en cada SELECT/INSERT/UPDATE/DELETE.
- update/delete usan project_id + id.
- el bloqueo de consecutivo es sobre el proyecto activo.
- URL de soporte se valida y se renderiza segura.
- textos se tratan como texto.
- PDF no resuelve recursos externos.
- URL XLSX debe ser same-origin y bajo ruta aprobada.
- mutaciones y reporte tienen CSRF de su scope.
- errores externos son estables; detalle va solo a log interno.
- no se aceptan db, project_id, role, permisos, fechaSolicitud ni id create del cliente.
- no se cambia RLS ni se agrega dependencia de politicas de base.
- no se toca schema, indices, grants, usuarios, credenciales o datos.

## Tema y design system

- tokens vienen de public/css/tokens.css;
- oscuro y claro tienen igualdad funcional;
- estados usan semantica aprobada, no hex local;
- superficies, bordes, texto, focus, overlays y sombras usan tokens;
- inputs readonly y disabled conservan contraste;
- badges no dependen solo de color;
- montos y fechas usan cifras tabulares donde exista token;
- no hay estilos inline;
- reduced motion elimina transiciones no esenciales;
- no se reactiva linen.

Viewports obligatorios:

- 390x844;
- 480x900;
- 768x1024;
- 1180x820;
- 1440x900.

## Accesibilidad

- h1 unico y jerarquia de secciones;
- filtros tienen label y region;
- conteos usan salida accesible sin anunciar cada tecla;
- tabla tiene caption y cabeceras;
- region scrollable es enfocables y nombrada;
- tarjetas usan headings por id;
- dialogos tienen nombre, descripcion, foco inicial, trap y retorno de foco;
- grupos radio/checkbox tienen fieldset/legend;
- requeridos y errores se relacionan programaticamente;
- contadores no interrumpen cada pulsacion;
- soportes se reordenan con controles accesibles, no solo drag;
- links externos anuncian destino;
- confirmacion de borrado no depende de color;
- teclado cubre alta, filtros, apertura, edicion, soportes, PDF, XLSX y borrado;
- touch minimo 44x44;
- zoom 200% no pierde controles;
- axe sin violaciones serias o criticas;
- console/pageerror limpios.

## Convivencia, corte y retiro

### Piloto

- /app/control-cambios monta React.
- /control-cambios conserva VIEW-10.
- React usa context GET, list GET y save JSON.
- aliases legacy siguen activos.
- report route conserva compatibilidad.
- bandera/routing permite rollback.

### Corte canonico

Despues de paridad:

- /control-cambios entra por SpaRouter;
- T01 entrega sidebar/capacidades;
- assets React cargan desde /app/;
- VIEW-10 deja de ser camino principal;
- reportes permanece ruta compartida.

### Retiro

Solo con cero callers:

- retirar VIEW-10;
- retirar public/css/control-cambios.css si no tiene consumidores;
- retirar DataTables y vendors exclusivos;
- retirar POST list legacy;
- retirar form/opcion legacy de save;
- retirar obtenerNombreDirector y obtenerURLCambios como opciones;
- retirar actualizarFechaInicio si cero callers;
- retirar nueva_sem/eliminar_sem solo cuando T01 ya sea propietario;
- mantener las acciones de audit log;
- mantener /reportes/consolidado-odc y su compatibilidad hasta el gate D16.

### Rollback

- restaurar routing canonico a VIEW-10;
- desactivar entrada React;
- conservar endpoints compatibles;
- no revertir datos creados validamente;
- no ejecutar DML correctivo;
- probar que lista/read-only legacy sigue abriendo;
- registrar causa y evidencia.

## Estrategia de pruebas

### PHP puro y contratos

- guard de pagina exige ver;
- context deriva proyecto y capacidades;
- list GET usa project_id y ORDER BY id ASC;
- list vacia no devuelve sentinel;
- codecs de enums, tipoCambio, soportes y decimales;
- create valida y asigna id con lock fake;
- update no incluye Observaciones en SET;
- delete usa clave compuesta;
- validaciones condicionales y de fechas;
- report usa tiempoCronogramaAfectado;
- report gate independiente;
- errores no filtran excepciones;
- aliases se conservan durante piloto.

Todos usan Database/exporter/filesystem fakes o inspeccion estructural. Ninguno conecta a MySQL.

### Frontend unit/component

- Zod context/list/save/report;
- formulas con cero y decimales;
- filtros 12, conteos y limpiar;
- mapping de enums/warnings;
- formulario create/edit/read;
- reglas Otro, tipos, textos, costos y fechas;
- soportes seguros y maximo;
- dirty close;
- readonly por capacidad;
- PDF document definition pura;
- XLSX URL same-origin;
- tabla/tarjetas exclusivas;
- estados loading/vacio/error/stale.

### Browser interceptado

Antes de navegar se interceptan context, list, save y report:

- rol editor y solo lectura;
- lista vacia y poblada;
- filtros y conteos;
- create/update/delete;
- error 422, 403, 404, 409 y 500;
- soportes seguros/malformados;
- PDF sin request externo;
- XLSX exitoso/fallido;
- cinco viewports;
- oscuro/claro;
- teclado, focus, touch, zoom, reduced motion;
- no overflow de pagina;
- axe y consola.

### Tripwires de no mutacion

Durante S15 estan prohibidos como evidencia:

- tests/browser/control-cambios-listado.spec.mjs actual, porque crea/elimina datos reales;
- controlCambios.edit de tests/browser/support/moduleFlows.mjs;
- el tramo Control de Cambios de full-app-flow.spec.mjs;
- POST real a /api/control-cambios/save;
- reporte real que escriba public/storage/ordenes;
- DML manual o rollback contra base;
- snapshots regenerados para ocultar cambios.

El plan sustituye esas rutas por interceptacion/fakes antes de incorporarlas a un gate.

## Criterios de aceptacion

1. S15-AC-01: /admin/ queda fuera.
2. S15-AC-02: /control-cambios es ruta SPA al corte.
3. S15-AC-03: el alcance distingue paridad visible de recuperacion aprobada.
4. S15-AC-04: la ruta exige lps.control_cambios.ver.
5. S15-AC-05: T01 omite la entrada sin capacidad.
6. S15-AC-06: el proyecto se deriva solo de sesion.
7. S15-AC-07: db/project_id/role cliente se rechazan como autoridad.
8. S15-AC-08: context es GET puro y tiene Zod/PHP contract.
9. S15-AC-09: list es GET puro y tiene Zod/PHP contract.
10. S15-AC-10: save es JSON discriminado y tiene Zod/PHP contract.
11. S15-AC-11: reporte JSON tiene Zod/PHP contract.
12. S15-AC-12: React nunca interpreta roles.
13. S15-AC-13: ver, editar y reportar se evalúan independientemente.
14. S15-AC-14: lista devuelve exactamente el proyecto activo.
15. S15-AC-15: lista usa id ASC.
16. S15-AC-16: vacio usa data vacia, no sentinel.
17. S15-AC-17: 29 campos persistidos tienen representacion de dominio.
18. S15-AC-18: decimales viajan como strings canonicos.
19. S15-AC-19: fechas viajan ISO o null.
20. S15-AC-20: enums desconocidos no rompen lectura.
21. S15-AC-21: JSON historico malformado produce warning seguro.
22. S15-AC-22: create asigna id en servidor.
23. S15-AC-23: create fija fechaSolicitud en servidor.
24. S15-AC-24: asignacion de id se serializa por proyecto sin DDL.
25. S15-AC-25: solicitante/responsable Otro exigen detalle.
26. S15-AC-26: detalle se limpia cuando no es Otro.
27. S15-AC-27: se exige al menos un tipo.
28. S15-AC-28: los ocho narrativos respetan 500 caracteres.
29. S15-AC-29: dias e importes son finitos y no negativos.
30. S15-AC-30: cadena de costos es no decreciente.
31. S15-AC-31: porcentaje cronograma maneja base cero.
32. S15-AC-32: porcentaje presupuesto maneja base cero.
33. S15-AC-33: porcentajes no se persisten.
34. S15-AC-34: fecha solicitud es inmutable.
35. S15-AC-35: fechas opcionales no preceden solicitud.
36. S15-AC-36: estados finales exigen fecha definicion.
37. S15-AC-37: En Estudio exige fecha definicion null.
38. S15-AC-38: Observaciones es readonly y nunca se borra al actualizar.
39. S15-AC-39: soportes son 0..20 enlaces http/https.
40. S15-AC-40: soportes inseguros se muestran no accionables.
41. S15-AC-41: no se implementan uploads.
42. S15-AC-42: create/update/delete son atomicos con audit log.
43. S15-AC-43: update/delete usan project_id + id.
44. S15-AC-44: no existe edicion por lote ni autosave.
45. S15-AC-45: se conservan 11 filtros y se repara Observaciones.
46. S15-AC-46: total y filtrado se actualizan.
47. S15-AC-47: limpiar filtros restaura lista.
48. S15-AC-48: no se agrega busqueda global, paging ni sorting.
49. S15-AC-49: tabla conserva 14 posiciones de resumen.
50. S15-AC-50: tarjetas conservan los mismos datos y acciones.
51. S15-AC-51: solo tabla o tarjetas se montan.
52. S15-AC-52: cinco viewports no tienen overflow de pagina.
53. S15-AC-53: dialogo soporta create/edit/read.
54. S15-AC-54: dirty close pide confirmacion.
55. S15-AC-55: errores conservan draft.
56. S15-AC-56: respuesta canonica reemplaza estado local.
57. S15-AC-57: PDF individual incluye todas las secciones.
58. S15-AC-58: PDF no hace fetch ni interpreta HTML.
59. S15-AC-59: XLSX conserva 14 columnas y corrige dias afectados.
60. S15-AC-60: URL XLSX se valida same-origin.
61. S15-AC-61: recarga es lectura pura y preserva filtros.
62. S15-AC-62: no se añade selector/CRUD de semana.
63. S15-AC-63: no se añade drawer contextual.
64. S15-AC-64: solo cliente.ts llama fetch.
65. S15-AC-65: errores no filtran internos.
66. S15-AC-66: pruebas de contrato no conectan a MySQL.
67. S15-AC-67: browser intercepta antes de navegar.
68. S15-AC-68: pruebas actuales que mutan quedan fuera del gate.
69. S15-AC-69: oscuro/claro tienen capacidad equivalente.
70. S15-AC-70: teclado/foco/touch/zoom/reduced motion/axe cumplen.
71. S15-AC-71: RLS/schema/grants/usuarios/credenciales/datos no cambian.
72. S15-AC-72: aliases y VIEW-10 se retiran solo con cero callers.
73. S15-AC-73: operaciones de semana se retiran solo tras transferencia T01.
74. S15-AC-74: rollback se ensaya sin revertir datos.
75. S15-AC-75: no se regeneran goldens sin aprobacion.

## Entregas verticales

### Entrega 1 — Lectura completa y filtros

- ruta/guards/context/list project-scoped;
- codecs de 29 campos;
- tabla/tarjetas readonly;
- doce filtros, conteos, vacio y estados;
- contratos PHP/Zod.

### Entrega 2 — Formulario y calculos

- dialogo create/edit/read;
- secciones y validaciones;
- porcentajes puros;
- soportes URL;
- dirty/error/recuperacion.

### Entrega 3 — Persistencia e integridad

- create/update/delete JSON;
- consecutivo serializado;
- Observaciones preservadas;
- transacciones/log;
- pruebas fake y compatibilidad legacy.

### Entrega 4 — Documentos, accesibilidad y corte

- PDF individual;
- XLSX consolidado;
- roles, temas, viewports y a11y;
- corte, retiro, transferencia T01 y rollback.

## Riesgos y mitigaciones

| Riesgo | Mitigacion |
|---|---|
| Recuperar conducta no visible | separar paridad/recuperacion y anclarla en contratos existentes |
| MAX+1 asigna duplicado | lock de fila project + transaccion + retry controlado |
| Update borra Observaciones | excluir columna de SET y test estructural |
| Proyecto falsificado por db | scope solo sesion y rechazo explicito |
| Decimal JS pierde valor | wire string + parser decimal |
| Division por cero | resultado tipado not_calculable |
| AIU inventado | validar cadena, no tasas |
| Fecha historica invalida bloquea lectura | warning; validar al guardar |
| JSON soporte/tipo invalido rompe Zod | codec tolerante a persistencia, wire estricto |
| Link ejecuta esquema peligroso | allowlist http/https + render texto |
| PDF obtiene recursos externos | document definition pura |
| Report usa nombre de campo incorrecto | contrato de 14 columnas y test |
| Report escribe durante tests | exporter/filesystem fake |
| Tabla recorta pagina | region scroll propia + cinco viewports |
| Dos ramas responsive duplican foco | montaje exclusivo |
| Permiso report confundido con edit | capability separada en context |
| Alias semana se elimina pronto | gate T01 + zero callers |
| Suite legacy muta datos | tripwire e interceptacion previa |

## Decisiones descartadas

- Limitar S15 al readonly actual: contradice el programa aprobado.
- Inventar el JavaScript perdido: se especifican reglas explicitas y comprobables.
- Recuperar source desde una rama no alcanzable: no existe evidencia disponible.
- Confiar en db query: autoridad cliente.
- Dejar page guard solo en APIs: filtra superficie.
- Usar id enviado en create: carrera y autoridad.
- Crear secuencia nueva: requiere DDL.
- Mantener MAX+1 sin lock: carrera.
- Agregar columna version: schema fuera de alcance.
- Mantener Observaciones=NULL: perdida de datos.
- Usar number para importes: precision/formato ambiguo.
- Calcular tasas AIU/IVA: no existe contrato.
- Forzar orden entre todas las fechas: dominio no demostrado.
- Subir soportes: solo hay enlaces.
- Mantener fila centinela: dato falso.
- Agregar filtro global/paging/sort: no es conducta legacy.
- Montar tabla oculta en movil: duplica semantica.
- Generar PDF con captura HTML: inseguro e inaccesible.
- Hacer report client-side: XLSX existente es servidor.
- Llevar nueva_sem/eliminar_sem a S15: pertenecen al shell.
- Reutilizar tests con DML: viola alcance.

## Decisiones pendientes

Ninguna. Si durante implementacion aparece un JavaScript historico verificable, un consumidor real de
actualizarFechaInicio, un formato adicional de soportes, una regla juridica de fechas/costos o un
requisito de PDF no representado aqui, debe detenerse el tramo afectado, aportar evidencia y
enmendar esta spec antes de ampliar el dominio.

## Siguiente gate

Invocar superpowers:writing-plans para
docs/superpowers/plans/2026-08-30-s15-control-cambios-react.md, autorrevisarlo, actualizar el atlas y
continuar S16. No implementar S15 en esta sesion.
