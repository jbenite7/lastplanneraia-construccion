---
capa: fuente
tipo: spec
estado: autorrevisado
id: S14
fecha: 2026-08-31
superficie: subcontratistas
rutas:
  - "/subcontratistas"
depende_de: [T01, S07, S08, S11, S13, S23]
views: [VIEW-42]
areas: [arquitectura, frontend, subcontratistas, interesados, rbac, accesibilidad, design-system]
fuente: "auditoria de public/index.php, SubcontratistasController, SubcontratistasApiController, ProgramacionIntermediaController, ProgramacionSemanalController, CicApiController, RbacCatalog, Database, schema global, VIEW-42, CSS, manifiesto y pruebas en shell-minimo-react, 2026-08-31"
resumen: "Migracion vertical S14 del catalogo Subcontratistas/Interesados Externos a la SPA React principal: vocabulario y tipos por area, lectura pura por proyecto, CRUD/autosave, NIT o identificacion, unicidades, renombre transaccional compatible con FK, dependencias CIC/PI/PS, recarga, CSV, tabla/tarjetas, oscuro/claro y contratos Zod/PHP, sin tocar PDC v2, RLS, schema ni datos."
---

# S14 — Subcontratistas e Interesados Externos en React

> **Estado:** diseño tecnico autorrevisado. No quedan decisiones de negocio, producto, estrategia o
> PM que bloqueen el plan. Esta spec no autoriza implementacion, commits, DDL/DML, cambios RLS,
> cambios de permisos, deploy, publicacion ni trabajo en /admin/. Su plan se escribe a continuacion
> con superpowers:writing-plans, conforme al programa aprobado de 27 specs y 27 planes.

## Relacion con el programa

S14 desarrolla VIEW-42, views/subcontratistas/subcontratistas.view.php, y los cuatro pares
metodo/ruta que el atlas agrupa bajo Subcontratistas. Consume:

- T01 para sesion, proyecto, area, sidebar, tema, ruta, cliente HTTP y errores globales;
- S07 para el catalogo activo usado por Programacion Intermedia;
- S08 para el catalogo activo y asignaciones de Programacion Semanal;
- S11 para metadata del proveedor y referencias CIC;
- S13 para el patron de catalogo editable, cola por fila y exportador CSV compartido;
- S23 como propietario futuro de BI Contratistas.

La misma tabla representa dos vocabularios:

- proyecto Construccion: Subcontratistas;
- proyecto Pre-Construccion: Interesados Externos.

La diferencia no se decide por query ni por JavaScript. PHP deriva el area del proyecto activo y
entrega el modo, etiquetas y tipos permitidos. T01 entrega la entrada de sidebar ya rotulada y
autorizada; S14 elimina el parche DOM que espera 500 ms para cambiar Subcontratistas por
Interesados Externos.

S14 no posee Plan de Compras v2. El PDC v1 y su tabla pdc fueron eliminados; ningun servicio PDC v2
referencia el catalogo subcontratistas. La nota del inventario D12 sobre dependencias PDC es deuda
historica y no autoriza reintroducirla. T01 sigue mostrando u ocultando Plan de Compras por sus
capacidades propias, independiente del CRUD S14.

## Resultado buscado

/subcontratistas pasa a la SPA principal y conserva toda capacidad observable:

1. muestra Subcontratistas o Interesados Externos segun el area server-authoritative;
2. carga solo el catalogo del proyecto activo;
3. presenta nombre, correo, NIT/identificacion, alcance/rol, tipo y estado;
4. conserva alta, edicion, autosave, Activo, borrado, recarga y CSV;
5. valida requeridos, email, tipo y las tres unicidades funcionales;
6. trata NIT/identificacion como string decimal para no perder precision en JavaScript;
7. conserva exactamente tres tipos de Construccion y diez de Preconstruccion;
8. lista registros activos e inactivos para administrarlos;
9. expone a S07/S08 solo la proyeccion activa mediante sus contratos propietarios;
10. bloquea el borrado si el nombre aparece en CIC, PI consolidada o Programacion Semanal;
11. renombra referencias atomica y token-aware;
12. conserva el mismo Id aun cuando la FK CIC exige una estrategia de reemplazo;
13. no desactiva FOREIGN_KEY_CHECKS ni cambia la FK;
14. ofrece tabla editable desktop/tablet y tarjetas editables moviles;
15. exporta las seis columnas visibles de negocio;
16. conserva BI Contratistas/Interesados solo cuando el servidor autoriza el destino;
17. funciona igual en oscuro y claro, teclado, touch, zoom y lector de pantalla;
18. valida cada respuesta con Zod y cada contrato HTTP con PHP sin base mutable;
19. retira VIEW-42 y aliases solo despues de paridad, cero consumidores y rollback.

Paridad no exige conservar Handsontable, una fila fantasma, un rol/prefijo oculto, mensajes con
excepciones, un candado imposible de enfocar, mutaciones parcialmente exitosas, el flash de etiqueta
Preconstruccion ni la exportacion de una fila vacia. Si exige conservar reglas, datos, vocabulario,
acciones, dependencias, CSV, estados y recuperacion.

## Alcance

### Incluido

- GET /subcontratistas como ruta SPA al corte.
- GET/POST /api/subcontratistas/list durante convivencia.
- POST /api/subcontratistas/save durante convivencia y como JSON objetivo.
- Un endpoint nuevo de contexto.
- Modos Construccion y Preconstruccion.
- Trece tipos permitidos particionados 3/10.
- Lista completa project-scoped.
- Alta manual.
- Autosave de seis campos mutables.
- Validacion/normalizacion de nombre, correo, NIT, alcance, tipo y Activo.
- Unicidad normalizada de nombre, correo y NIT dentro del proyecto.
- Dependencias CIC, programa_consolidado y programacion_semanal.
- Renombre compatible con la FK cic -> subcontratistas por nombre.
- Borrado revalidado.
- Recarga pura y CSV local.
- BI Contratistas o BI Interesados segun area.
- Tabla desktop/tablet y tarjetas moviles.
- Carga, vacio, error, readonly, stale, guardando, conflicto y reintento.
- Tema oscuro y claro con tokens.
- Contratos PHP, Zod, Vitest/Testing Library y Playwright interceptado.
- Corte strangler, retiro exclusivo y rollback.

### Fuera de alcance

- Todo /admin/.
- Plan de Compras v2, sus proveedores futuros o contratos.
- Reintroducir PDC v1, tabla pdc o /api/pdc.
- Crear una entidad separada para Interesados.
- Cambiar el area de un proyecto.
- Cambiar los trece tipos permitidos.
- Cambiar capacidades lps.subcontratistas.ver/editar.
- Cambiar RLS, ProjectScope, schema, FK, columnas, tipos, indices, grants o datos.
- Agregar foreign keys a PI/PS.
- Cambiar formularios, cadencia, preguntas o formulas CIC.
- Cambiar reglas de asignacion de S07/S08.
- Añadir selector de semana: el catalogo es project-scoped.
- Añadir drawer contextual: VIEW-42 no lo usa.
- Añadir busqueda, filtros u orden de servidor que legacy no expone.
- Migrar /bi/contratistas, que pertenece a S23.
- Regenerar goldens sin aprobacion.
- Implementar, ejecutar DML, commitear o publicar en esta sesion documental.

## Punto de partida medido

### React

- No existe modulo React, Zod, gateway, tabla, tarjetas ni pruebas S14.
- La sidebar enlaza /subcontratistas y aplica ocultasPorRol local.
- La etiqueta es siempre Subcontratistas; el legacy la corrige despues de cargar en Preconstruccion.
- /subcontratistas sigue sirviendo PHP.
- S13 planifica un serializador CSV compartido y la cola por fila que S14 debe reutilizar si ya
  existen al ejecutarse.

### Inventario HTTP legacy

| ID | Metodo y ruta | Guard actual | Efecto |
|---|---|---|---|
| S14-LEG-01 | GET /subcontratistas | solo autenticacion | renderiza VIEW-42 y token |
| S14-LEG-02 | GET /api/subcontratistas/list | subcontratistas.ver | lista pura segun prefijo cliente |
| S14-LEG-03 | POST /api/subcontratistas/list | subcontratistas.ver | misma lista; consumo VIEW-42 |
| S14-LEG-04 | POST /api/subcontratistas/save | subcontratistas.editar + CSRF | multiplexa editar/crear/eliminar |

GET/POST list no escriben, a diferencia de Profesionales. Aun asi aceptan db del navegador y los
errores concatenan excepciones internas.

### VIEW-42

La pagina carga jQuery, Bootstrap, Popper, Font Awesome, Handsontable y scripts legacy. Renderiza:

- h1 oculto Subcontratistas incluso en Preconstruccion;
- inputs ocultos db y rol;
- heading dinamico;
- ayuda solo para Preconstruccion;
- Guardado/Error;
- Exportar con listener inline;
- enlace BI dinamico;
- Handsontable de ocho columnas.

Columnas:

| Posicion | Construccion | Preconstruccion | Campo |
|---|---|---|---|
| 0 oculta | ID | ID | Id |
| 1 | Subcontratista | Interesado | subcontratista |
| 2 | Correo Contacto | Correo Contacto | correo_contacto |
| 3 | NIT | Identificacion | NIT |
| 4 | Alcance | Rol/Interes | alcance |
| 5 | Tipo Proveedor | Tipo de Interesado | tipo_proveedor |
| 6 | Activo | Activo | activo |
| 7 | Acciones | Acciones | accion |

Handsontable activa menu contextual, resize manual, cabeceras navegables, wrap, autoRowSize y fila
vacante. No activa filters, dropdownMenu, search ni sorting. El orden servidor es alfabetico por
subcontratista.

### Modo por area

La vista considera Preconstruccion solo cuando area es exactamente Pre-Construccion. El API tambien
acepta PC y, si falta sesion, query area. El target elimina ambas ambiguedades:

- T01 project.area solo acepta Construccion o Pre-Construccion;
- contexto S14 deriva mode desde ese valor;
- query area se rechaza como autoridad;
- labels y tipos viajan desde PHP;
- sidebar, document title, h1, columnas, dialogos y CSV usan el mismo vocabulario.

#### Construccion

Tipos exactos:

1. Mano de Obra
2. Suministro e Instalación
3. Suministro de Materiales, Herramientas o Equipos

Etiquetas:

- titulo Subcontratistas;
- singular Subcontratista;
- NIT;
- Alcance;
- Tipo Proveedor;
- BI Contratistas.

#### Preconstruccion

Tipos exactos:

1. Socio
2. Ventas
3. Gerencia
4. Diseñador
5. Consultor
6. Entidad
7. Interventoría
8. Cliente
9. Inversionista
10. Promotor

Etiquetas:

- titulo Interesados Externos;
- singular Interesado;
- Identificacion;
- Rol/Interes;
- Tipo de Interesado;
- BI Interesados.

Los acentos anteriores son parte del wire catalog para tipos. Los labels UI se muestran con
ortografia completa: Identificación y Rol/Interés.

### Respuesta de lista

Cada fila actual contiene:

| Campo | Tipo target normalizado | Significado |
|---|---|---|
| Id | entero positivo | identidad local al proyecto |
| subcontratista | string no vacio | nombre canonico |
| correo_contacto | string email minusculas | contacto |
| NIT | string decimal | NIT o identificacion |
| alcance | string no vacio | alcance o rol/interes |
| tipo_proveedor | string no vacio | tipo almacenado |
| activo | boolean | disponible en catalogos operativos |
| has_dependencies | boolean | usado en CIC, PI o PS |

El target conserva los nombres wire para no romper S07/S08/S11. Id deja de oscilar Id/id. NIT nunca
se parsea a number en React: BIGINT puede exceder la precision segura JavaScript.

### Alta y autosave

- minSpareRows=1 crea al completar los cinco campos requeridos;
- vacio/incompleto no se envia;
- cada cambio de fila se agrupa y envia inmediatamente;
- el servidor mezcla cambios con la fila actual y valida todo;
- success muestra Guardado;
- warning/error recarga la lista;
- create recarga para obtener Id;
- delete recarga.

El target conserva autosave para filas existentes, pero usa alta explicitamente rotulada con boton.
La respuesta create/update entrega la fila completa y evita una recarga de exito.

### Validacion y unicidades

Reglas auditadas:

- nombre requerido, trim y espacios colapsados;
- correo requerido, trim, minusculas y formato email;
- NIT/identificacion requerido;
- alcance requerido y espacios colapsados;
- tipo requerido y perteneciente al catalogo del area;
- Activo normalizado a 1/0;
- nombre unico por comparacion case-insensitive/espacios;
- correo unico por minusculas;
- NIT unico ignorando caracteres no alfanumericos.

El schema global almacena NIT como BIGINT. El target:

- acepta entrada con digitos, espacios, puntos o guiones;
- elimina separadores para persistir;
- rechaza letras y otros simbolos;
- exige 1 a 19 digitos y valor <= 9223372036854775807;
- devuelve siempre string decimal canonico;
- compara por ese string;
- en Construccion limita a 10 digitos porque cic.NIT es varchar(10);
- en Preconstruccion permite hasta 19.

Esta decision no cambia schema: hace explicito el rango que los tipos actuales pueden almacenar sin
truncar ni perder precision.

Longitudes canonicas por el consumidor mas estrecho:

- nombre: 1..100 caracteres, porque programa_consolidado.Sub_Contratista es varchar(100);
- correo: hasta 200;
- alcance/rol: hasta 200;
- tipo: catalogo exacto, todos dentro de 200.

### Registros historicos con tipo fuera de catalogo

La lista no falla si una fila preexistente tiene tipo vacio/desconocido. La muestra con advertencia
Metadata por corregir. Cualquier save de esa fila aplica validacion completa y exige elegir un tipo
valido. Zod valida tipo_proveedor como string, no como enum; el dominio lo contrasta con context.

### Catalogos consumidores

- S14 management list muestra activos e inactivos.
- Programacion Intermedia y Semanal solo deben ofrecer activo=true.
- El refresh legacy PI actualmente toma todos los resultados de list y no filtra Activo; es un
  defecto que S07 cierra en su proyeccion.
- S11 resuelve metadata del maestro por proyecto y no la autocorrige.
- Desactivar no elimina asignaciones existentes; solo quita la opcion de nuevas selecciones.
- React S14 no modifica semanas, actividades, compromisos ni evaluaciones.

### Dependencias reales

El controlador actual comprueba:

- cic.subcontratista;
- programacion_semanal.Sub_Contratista.

La auditoria de schema y controladores añade una dependencia omitida:

- programa_consolidado.Sub_Contratista, fuente editable de Programacion Intermedia.

PI admite multiples nombres serializados como lista separada por comas. La igualdad SQL actual no
detecta esos tokens. El target usa:

- CIC: valor exacto;
- PI consolidada: token exacto normalizado dentro de lista;
- PS: token exacto normalizado, aunque normalmente ya sea una fila por proveedor.

No se considera dependencia:

- pdc: eliminado;
- Plan de Compras v2: sin referencia al catalogo;
- indicadores_generales.subcontratista_profesional: su contenido auditado pertenece al catalogo de
  profesionales/consolidado y no se une al maestro S14.

### FK CIC y renombre

El schema global contiene:

~~~text
FOREIGN KEY (project_id, subcontratista)
REFERENCES subcontratistas (project_id, subcontratista)
ON DELETE RESTRICT
~~~

No tiene ON UPDATE CASCADE y MySQL no ofrece constraints diferibles. Actualizar primero padre o
hijo falla cuando existe CIC. S14 preserva la edicion sin tocar schema mediante transaccion shadow:

1. bloquear la fila original y revalidar unicidades;
2. reservar un Id temporal project-scoped;
3. insertar una fila shadow con nombre/metadata final e Id temporal;
4. actualizar CIC del nombre viejo al nuevo, ahora que el padre nuevo existe;
5. reemplazar tokens exactos en PI consolidada y PS;
6. borrar la fila original, ya sin referencias;
7. cambiar el Id shadow al Id original;
8. releer y devolver la fila canonica;
9. commit;
10. ante cualquier fallo, rollback completo.

La operacion siempre preserva el Id publico. Nunca ejecuta SET FOREIGN_KEY_CHECKS, nunca cambia la
FK y nunca expone la fila shadow fuera de la transaccion. Si el nombre no cambia, actualiza metadata
normalmente.

### Borrado

- bloquea si cualquiera de las tres dependencias contiene el nombre;
- vuelve a comprobar dentro de transaccion;
- no confia solo en has_dependencies del GET;
- no borra/inactiva referencias;
- Activo=false sigue siendo la alternativa no destructiva;
- el candado legacy deshabilitado no es focalizable; el target usa aria-disabled con razon.

### CSV y BI

El export legacy funciona mediante Handsontable y exporta columnas 1..6:

1. Subcontratista/Interesado
2. Correo Contacto
3. NIT/Identificacion
4. Alcance/Rol-Interes
5. Tipo Proveedor/Tipo de Interesado
6. Activo

Excluye Id y Acciones, pero incluye potencialmente la fila vacante. El filename siempre es
subcontratistas. El target conserva seis columnas, excluye drafts y usa filename:

- subcontratistas-{proyecto}-{fecha}.csv;
- interesados-externos-{proyecto}-{fecha}.csv.

BiAccessComponent usa /bi/contratistas para ambos modos; cambia solo el label. El destino conserva
project context y solo se muestra con la politica BI efectiva.

### Responsive y evidencia actual

- El manifiesto declara desktop, dark y normal.
- No hay tarjetas moviles.
- El resize solo recalcula columnas por encima de 768; por debajo queda la grilla.
- El body entrega scroll a Handsontable y oculta overflow.
- El candado de dependencia esta disabled y su razon depende de title.
- Existe golden dark 1180x820.
- No hay evidencia light, movil, readonly, vacio o error.

## Defectos y contradicciones que S14 cierra

1. La pagina solo exige autenticacion, no subcontratistas.ver.
2. API acepta prefijo db del navegador.
3. API acepta area de query como fallback.
4. Rol crudo viaja en input oculto.
5. Errores exponen excepciones.
6. Guardado multiplexa aliases y columnas flexibles.
7. Puede devolver exito parcial entre filas.
8. Renombre y dependencias no son atomicos.
9. Renombre con CIC contradice la FK restrictiva.
10. Dependencias omiten PI consolidada.
11. Igualdad SQL omite listas multi-proveedor.
12. NIT se maneja como texto UI sobre BIGINT sin contrato de rango.
13. Id puede ser secuencia por proyecto; no debe asumirse auto increment.
14. Validacion local usa una copia persisted que no se actualiza tras autosave exitoso.
15. PI refresh legacy ofrece inactivos.
16. Preconstruccion cambia sidebar/breadcrumb con timeout y puede producir flash.
17. h1/document title siguen diciendo Subcontratistas en Preconstruccion.
18. Borrado bloqueado no es accesible por teclado.
19. No hay estado vacio ni tarjetas moviles.
20. CSV incluye potencialmente la spare row y no adapta filename al modo.
21. La nota PDC del inventario es stale tras retiro v1.

## Decisiones de dominio

### Identidad y scope

- Id es entero positivo local al project_id.
- React no calcula Id.
- PHP usa el allocator project-scoped vigente.
- El wire conserva Id mayuscula por compatibilidad con S07/S08/S11.
- project_id, db, area y rol no viajan como autoridad.
- mode se deriva de T01 project.area.

### Normalizacion

~~~text
subcontratista = trim + colapso de espacios
correo_contacto = trim + minusculas
NIT = solo digitos, sin separadores
alcance = trim + colapso de espacios
tipo_proveedor = valor exacto del catalogo del modo
activo = boolean estricto
~~~

Nombre y correo se comparan Unicode case-insensitive en dominio. NIT se compara por digits-only.
El servidor vuelve a normalizar y devuelve la fila canonica.

### Acciones

~~~text
canEdit = context.actions.edit
canDelete = context.actions.delete AND NOT row.has_dependencies
~~~

No hay bloqueos de origen administrativo en S14. Una fila dependiente puede editar metadata y
nombre; el servicio de renombre protege la integridad.

### Autosave

- Textos guardan en blur o Enter.
- Tipo y Activo guardan al cambiar.
- Escape restaura ultimo valor confirmado.
- Una fila envia en orden mediante cola compartida con S13.
- Filas distintas pueden guardar en paralelo.
- No hay retry automatico.
- 422 conserva draft y enfoca campo.
- 409 conserva draft, refresca conflicto y explica.
- 500 conserva draft y ofrece reintento.

### Alta

La spare row se reemplaza por fila/tarjeta Nuevo:

- cinco campos requeridos;
- Activo nace true;
- boton explicito Agregar;
- conserva draft ante error;
- evita doble submit;
- respuesta fila completa;
- limpia y devuelve foco tras exito.

## Contratos HTTP objetivo

### Inventario

| ID | Metodo y ruta | Estado objetivo |
|---|---|---|
| S14-API-01 | GET /api/subcontratistas/context | nuevo; puro |
| S14-API-02 | GET /api/subcontratistas/list | adaptado; puro y scoped |
| S14-API-03 | POST /api/subcontratistas/save | adaptado; JSON strict |
| S14-COMP-01 | POST /api/subcontratistas/list | alias legacy hasta corte |
| S14-COMP-02 | POST form /api/subcontratistas/save | modo legacy hasta corte |

Todos exigen sesion y proyecto. Context/list exigen ver. Save exige editar y CSRF
subcontratistas. No existe endpoint sync.

### Scope y transporte

Cada target:

1. resuelve ProjectScope;
2. obtiene projectId y area en servidor;
3. evalua RBAC antes de repository;
4. rechaza db, Base_de_Datos, project_id, area y role como autoridad;
5. usa prepared statements y project_id;
6. responde no-store;
7. usa HTTP status y error estable;
8. no concatena excepciones.

### S14-API-01 — contexto

Respuesta Construccion abreviada:

~~~json
{
  "status": "success",
  "data": {
    "mode": "subcontractors",
    "labels": {
      "title": "Subcontratistas",
      "singular": "Subcontratista",
      "name": "Subcontratista",
      "identifier": "NIT",
      "scope": "Alcance",
      "providerType": "Tipo Proveedor",
      "bi": "BI Contratistas"
    },
    "providerTypes": [
      "Mano de Obra",
      "Suministro e Instalación",
      "Suministro de Materiales, Herramientas o Equipos"
    ],
    "identifier": {
      "maxDigits": 10
    },
    "actions": {
      "view": true,
      "create": true,
      "edit": true,
      "delete": true,
      "exportCsv": true,
      "openBi": true
    },
    "links": {
      "bi": "/bi/contratistas?project_id=73"
    },
    "csrfToken": "opaque-or-null"
  }
}
~~~

Preconstruccion cambia:

- mode=stakeholders;
- las seis labels;
- providerTypes por los diez exactos;
- identifier.maxDigits=19.

Invariantes:

- mode deriva del area server;
- view/exportCsv derivan de ver;
- create/edit/delete derivan de editar;
- openBi/href derivan de politica BI;
- href null si openBi=false;
- csrfToken null sin mutaciones;
- no incluye area cruda, rol, prefijo ni tablas.

### S14-API-02 — lista

Respuesta:

~~~json
{
  "status": "success",
  "data": [
    {
      "Id": 31,
      "subcontratista": "Instalaciones Norte SAS",
      "correo_contacto": "contacto@example.com",
      "NIT": "900123456",
      "alcance": "Redes hidraulicas",
      "tipo_proveedor": "Suministro e Instalación",
      "activo": true,
      "has_dependencies": true
    }
  ]
}
~~~

Reglas:

- sin query de autoridad;
- Id entero;
- NIT string;
- booleanos reales;
- orden alfabetico estable por nombre normalizado y luego Id;
- lista activos e inactivos;
- has_dependencies usa tres fuentes y tokens;
- tipo historico desconocido no rompe la lista;
- vacio es success/data=[];
- no pagina, busca ni filtra.

### S14-API-03 — save JSON

Union estricta:

Crear:

~~~json
{
  "action": "create",
  "entity": {
    "subcontratista": "Instalaciones Norte SAS",
    "correo_contacto": "contacto@example.com",
    "NIT": "900.123.456",
    "alcance": "Redes hidraulicas",
    "tipo_proveedor": "Suministro e Instalación"
  }
}
~~~

Actualizar:

~~~json
{
  "action": "update",
  "Id": 31,
  "changes": {
    "alcance": "Redes hidraulicas y sanitarias",
    "activo": false
  }
}
~~~

Eliminar:

~~~json
{
  "action": "delete",
  "Id": 31
}
~~~

Reglas:

- create no acepta Id/activo;
- update exige al menos una de seis claves;
- no acepta array de filas;
- mezcla con fila actual y valida completa;
- tipo se valida contra mode actual;
- NIT se canonicaliza antes de unicidad/persistencia;
- nombre nuevo activa algoritmo shadow si cambio normalizado;
- delete revalida tres dependencias;
- claves extra fallan;
- no hay exito parcial.

Create/update responden:

~~~json
{
  "status": "success",
  "data": {
    "action": "update",
    "entity": {}
  }
}
~~~

entity cumple exactamente fila list. Delete devuelve action=delete y deletedId.

### Errores

| HTTP | code | Caso |
|---|---|---|
| 400 | INVALID_JSON | cuerpo ilegible |
| 401 | UNAUTHENTICATED | sesion invalida |
| 403 | PROJECT_REQUIRED | sin proyecto |
| 403 | FORBIDDEN | capacidad insuficiente |
| 403 | CSRF_INVALID | token invalido |
| 404 | ENTITY_NOT_FOUND | Id fuera del proyecto |
| 409 | DUPLICATE_NAME | nombre normalizado repetido |
| 409 | DUPLICATE_EMAIL | correo repetido |
| 409 | DUPLICATE_IDENTIFIER | NIT/identificacion repetido |
| 409 | DEPENDENCIES_EXIST | borrado con CIC/PI/PS |
| 422 | INVALID_ENTITY | campos/tipo/rango invalidos |
| 500 | WRITE_FAILED | fallo transaccional registrado |

Forma:

~~~json
{
  "status": "error",
  "error": {
    "code": "DUPLICATE_IDENTIFIER",
    "message": "Ya existe un registro con esa identificación.",
    "fields": {
      "NIT": "Ya existe un registro con esa identificación."
    }
  }
}
~~~

El message se adapta al mode sin cambiar code. fields solo usa campos editables. No filtra SQL,
tabla, prefix ni excepcion.

## Permisos y acciones efectivas

Capacidades:

- lps.subcontratistas.ver;
- lps.subcontratistas.editar.

Fallback auditado:

| Rol | Ver | Editar |
|---|---|---|
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

P normaliza a D y overrides son autoritativos. Esta tabla vive en pruebas PHP, no React.

| Accion UI | Regla |
|---|---|
| ver/listar/recargar/exportar | ver |
| crear/editar/Activo | editar |
| borrar | editar + sin dependencias |
| abrir BI | politica BI y href |
| abrir Plan de Compras | no es accion S14; T01 resuelve sidebar |

## Arquitectura React

~~~text
RutaSubcontratistas
  -> useSubcontratistas
      -> subcontratistasGateway
          -> frontend/src/lib/api/cliente.ts
  -> BarraCatalogo
  -> TablaSubcontratistas >= 768
  -> TarjetasSubcontratistas < 768
  -> AltaEntidad
  -> DialogoEliminar
  -> EstadoGuardado
~~~

S14 reutiliza, si existen:

- serializador frontend/src/lib/archivos/csv.ts de S13;
- primitiva de cola por fila extraida como compartida.

No importa componentes concretos de S13 con labels hardcoded. Comparte primitivas, no mezcla
dominios.

Estado:

- context;
- serverRows;
- draftsById;
- createDraft;
- saveStateById;
- listState/stale;
- deleteCandidate;
- feedback.

No añade dependencia de grid, query, state, form o CSS-in-JS.

## Composicion y responsive

### Encabezado

- eyebrow Informacion;
- h1 de context.labels.title;
- descripcion por modo;
- total/activos derivado localmente;
- recargar;
- exportar;
- BI autorizado.

No hay Sincronizar, semana ni drawer.

### Tabla 768+

- table semantica;
- caption contextual;
- columnas seis campos + acciones;
- Id oculto;
- inputs/select/checkbox nativos;
- warning de tipo historico invalido;
- candado focalizable con razon;
- alta separada;
- header sticky y scroll de contenedor;
- sin overflow de pagina a 768, 1180 y 1440.

### Tarjetas <768

- una tarjeta por entidad;
- nombre como heading;
- correo, identificador, alcance/rol, tipo, activo y dependencias;
- controles inline segun permiso;
- alta en tarjeta separada;
- acciones min 44px;
- una columna;
- sin tabla montada ni overflow a 390/480.

Ambas ramas comparten estado y callbacks.

### Sin filtros inventados

Legacy no habilita search/filters/sorting. S14 conserva orden alfabetico y conteos locales. Un futuro
filtro requiere spec propia.

## CSV

Usa solo filas persistidas cargadas, nunca drafts. Headers proceden de labels:

1. name
2. Correo Contacto
3. identifier
4. scope
5. providerType
6. Activo

Reglas:

- UTF-8 BOM;
- RFC 4180, coma, CRLF;
- Si/No;
- orden actual alfabetico;
- vacio genera solo cabecera;
- sin Id, acciones, dependencias, projectId o prefijo;
- filename por mode/proyecto/fecha;
- object URL revocado;
- live region confirma descarga;
- no endpoint.

## Estados de experiencia

1. cargando;
2. acceso denegado;
3. contexto fallido;
4. lista fallida sin datos;
5. stale con datos;
6. vacio editable;
7. vacio readonly;
8. normal editable;
9. normal readonly;
10. tipo historico invalido;
11. alta borrador/invalida/guardando/error;
12. fila editando/guardando/guardada;
13. validacion de campo;
14. conflicto nombre/correo/identificador;
15. renombre transaccional pendiente/fallido;
16. delete bloqueado;
17. confirmacion/delete pendiente;
18. CSV;
19. sesion vencida T01.

Error no se muestra como vacio. Inactivo no se confunde con bloqueado por dependencia.

## Accesibilidad

- main/h1 unico con T01;
- caption, thead, th scope;
- labels contextuales;
- fieldset/legend para alta;
- errores aria-describedby y resumen focalizable;
- live polite para guardado/recarga/CSV;
- alert para fallos;
- aria-busy por fila;
- bloqueo focalizable con texto;
- dialogo con trap, Escape y retorno;
- no color-only;
- orden tab estable;
- Enter/blur guardan, Escape revierte;
- touch 44px;
- zoom 200 sin perdida;
- reduced motion.

## Tema y design system

- oscuro default/fallback;
- claro equivalente;
- tokens de public/css/tokens.css;
- CSS @layer module;
- sin hex local, inline styles, important, Bootstrap, jQuery, Font Awesome ni Handsontable;
- labels largos de ambos modos no truncan informacion esencial;
- golden legacy dark es referencia de cobertura;
- no se actualiza sin aprobacion.

## Seguridad, aislamiento y RLS

- RLS/ProjectScope no cambian.
- projectId/area desde sesion.
- queries scoped y preparadas.
- autoridad cliente rechazada.
- RBAC antes de repository.
- CSRF en save.
- allowlist estricta.
- NIT string no se evalua ni usa como SQL.
- transaccion shadow no desactiva constraints.
- errores estables.
- CSV local autorizado.
- no retries mutables.
- pruebas con fakes/intercepcion.
- cero DDL/DML en esta fase.

## Convivencia, corte y rollback

### Piloto

1. /app/subcontratistas monta React.
2. /subcontratistas conserva VIEW-42.
3. GET list adopta contrato puro scoped.
4. POST list sigue como alias puro para VIEW-42.
5. JSON save convive con form save por Content-Type.
6. S07/S08 consumen proyecciones activas sin depender del alias.

### Corte

1. probar ambos modos y roles;
2. probar dependencias exactas/multitoken y rename con CIC;
3. probar viewports/temas/a11y;
4. añadir /subcontratistas exacta a SpaRouter;
5. retirar route HTML;
6. retirar POST list/form solo con cero callers;
7. retirar VIEW-42/CSS/vendors exclusivos;
8. conservar tabla/API/repository compartidos por S07/S08/S11;
9. actualizar sidebar labels, manifest/inventario;
10. ensayar rollback.

### Rollback

Quita ruta SPA y restaura route/aliases/VIEW del commit de corte. No revierte datos ni relaja FK,
RLS o permisos.

## Estrategia de pruebas

### PHP sin base mutable

- cuatro pares legacy y tres target;
- context Construccion/Preconstruccion;
- 3/10 tipos/labels;
- roles/overrides;
- list pura/vacia/tipos historicos;
- NIT formato/rango/string;
- longitudes;
- unicidad scoped triple;
- create/update/active/delete con fake store;
- tres dependencias exact/token;
- transaccion shadow call log, Id preservado y rollback;
- no FOREIGN_KEY_CHECKS;
- CSRF/metodo/scope/error;
- aliases piloto/cero caller.

### Frontend

- Zod strict context/row/list/save/error;
- modo/labels/catalogos;
- normalizacion/validacion;
- cola por fila;
- alta;
- readonly;
- type mismatch;
- dialogo/bloqueo;
- CSV seis columnas;
- BI;
- states/stale.

### Playwright interceptado

- Construccion editor/viewer/denied;
- Preconstruccion editor/viewer;
- manual active/inactive/dependent/type-invalid;
- create/autosave/conflicts/delete;
- rename success/failure fixtures;
- CSV;
- cinco viewports;
- oscuro/claro;
- teclado/foco/zoom/reduced motion/axe;
- deep link/corte/rollback;
- consola/red.

Todas las mutaciones se interceptan antes de navegar.

### Prohibido

- full-app-flow;
- operationalCycle;
- test_csrf_modulos_api real;
- POST real list/save;
- cualquier insert/update/delete/rollback SQL;
- pruebas CIC/PI/PS que materialicen datos;
- suite sin clasificar;
- golden update.

## Criterios de aceptacion

1. S14-AC-01: /subcontratistas es SPA al corte y refresh funciona.
2. S14-AC-02: Construccion muestra vocabulario Subcontratistas.
3. S14-AC-03: Preconstruccion muestra vocabulario Interesados Externos sin flash.
4. S14-AC-04: mode/labels/tipos provienen de area servidor.
5. S14-AC-05: query area no tiene autoridad.
6. S14-AC-06: proyecto proviene de ProjectScope.
7. S14-AC-07: db/project_id/role cliente se rechazan.
8. S14-AC-08: context/list exigen ver.
9. S14-AC-09: save exige editar y CSRF.
10. S14-AC-10: React no contiene matriz de roles.
11. S14-AC-11: catalogo Construccion conserva tres tipos exactos.
12. S14-AC-12: catalogo Preconstruccion conserva diez tipos exactos.
13. S14-AC-13: lista conserva ocho campos con tipos estrictos.
14. S14-AC-14: lista es pura, scoped y no-store.
15. S14-AC-15: vacio es success/data vacia.
16. S14-AC-16: lista incluye activos e inactivos.
17. S14-AC-17: consumidores operativos usan solo activos.
18. S14-AC-18: nombre/correo/alcance se normalizan.
19. S14-AC-19: NIT se persiste/devuelve como string decimal.
20. S14-AC-20: limites NIT/identificacion respetan ambos schemas actuales.
21. S14-AC-21: nombre se limita al consumidor varchar(100).
22. S14-AC-22: nombre, correo y NIT son unicos por proyecto.
23. S14-AC-23: tipo invalido se rechaza al guardar.
24. S14-AC-24: tipo historico invalido no rompe lectura.
25. S14-AC-25: alta explicita conserva draft y evita doble envio.
26. S14-AC-26: filas existentes conservan autosave.
27. S14-AC-27: autosaves se serializan por fila.
28. S14-AC-28: respuesta canonica reemplaza estado local.
29. S14-AC-29: Activo se edita y no borra referencias.
30. S14-AC-30: CIC cuenta como dependencia.
31. S14-AC-31: PI consolidada cuenta tokens como dependencia.
32. S14-AC-32: PS cuenta tokens como dependencia.
33. S14-AC-33: delete revalida las tres dependencias.
34. S14-AC-34: rename reemplaza las tres fuentes atomicamente.
35. S14-AC-35: rename con CIC preserva FK e Id.
36. S14-AC-36: rename nunca desactiva FOREIGN_KEY_CHECKS.
37. S14-AC-37: no existe exito parcial multi-fila.
38. S14-AC-38: recarga solo repite GET puro.
39. S14-AC-39: CSV exporta seis columnas, sin draft/Id/acciones.
40. S14-AC-40: filename/headers CSV se adaptan al mode.
41. S14-AC-41: BI usa label contextual y href autorizado.
42. S14-AC-42: no se crea dependencia PDC v2/v1.
43. S14-AC-43: no se añade semana ni drawer.
44. S14-AC-44: tabla desktop/tablet conserva campos/acciones.
45. S14-AC-45: tarjetas moviles conservan campos/acciones.
46. S14-AC-46: solo tabla o tarjetas se montan.
47. S14-AC-47: cinco viewports no tienen overflow de pagina.
48. S14-AC-48: oscuro/claro tienen capacidad equivalente.
49. S14-AC-49: teclado/foco/touch/zoom/reduced motion/axe cumplen.
50. S14-AC-50: solo cliente.ts llama fetch.
51. S14-AC-51: cada respuesta consumida tiene Zod strict.
52. S14-AC-52: cada endpoint nuevo/adaptado tiene contrato PHP.
53. S14-AC-53: errores no filtran internos.
54. S14-AC-54: pruebas no ejecutan DML real.
55. S14-AC-55: RLS/schema/FK/grants/usuarios/credenciales/datos no cambian.
56. S14-AC-56: aliases/VIEW se retiran solo con cero callers.
57. S14-AC-57: rollback se ensaya sin revertir datos.
58. S14-AC-58: no se regeneran goldens sin aprobacion.

## Entregas verticales

### Entrega 1 — Dominio dual y lectura

- modes, labels, 3/10 tipos y NIT;
- context/list scoped;
- contratos PHP/Zod;
- readonly tabla/tarjetas.

### Entrega 2 — Alta y autosave

- JSON create/update;
- unicidades/validacion;
- alta;
- cola por fila/recuperacion.

### Entrega 3 — Integridad y borrado

- registry CIC/PI/PS;
- token matching;
- rename shadow transaccional;
- delete accesible.

### Entrega 4 — CSV, evidencia y corte

- CSV/BI;
- ambos modos/roles/temas/viewports;
- corte, retiro y rollback.

## Riesgos y mitigaciones

| Riesgo | Mitigacion |
|---|---|
| Renombre rompe FK CIC | algoritmo shadow transaccional; prueba call log |
| Renombre cambia Id | reasignar Id original antes de commit |
| Dependencia multi-provider no detectada | parser token-aware compartido |
| Nombre cabe en maestro pero no PI | limite 100 |
| NIT pierde precision JS | wire string |
| NIT con formato trunca BIGINT | canonical digits/rango |
| Tipo historico rompe Zod | string wire + validacion dominio |
| Inactivo reaparece en PI | proyeccion activa S07/S08 |
| Area query falsifica catalogo | area solo servidor |
| Flash Preconstruccion | labels/sidebar server |
| Confundir PDC v1/v2 | cero endpoint/dependencia PDC |
| Carrera autosave/unicidad | cola por fila + revalidacion server |
| CSV incluye draft | export serverRows |
| Alias queda vivo | zero-caller gate |
| Test escribe | fakes/intercepcion/tripwire |

## Decisiones descartadas

- Duplicar pagina Interesados: una superficie/mismo dominio.
- Confiar en query area: autoridad cliente.
- Mantener DOM timeout: flash y divergencia.
- Mantener Handsontable/añadir AG Grid: innecesario.
- NIT number en TypeScript: precision insegura.
- Permitir letras en Identificacion sobre BIGINT: schema no las almacena.
- Cambiar BIGINT a varchar: schema fuera de alcance.
- SET FOREIGN_KEY_CHECKS=0: rompe integridad/concurrencia.
- Cambiar FK a cascade: DDL fuera de alcance.
- Prohibir todo rename dependiente: perderia capacidad editable.
- Reemplazo SQL por substring: corrompe nombres parecidos.
- Omitir PI consolidada: deja referencias viejas.
- Añadir dependencia PDC: no existe en v2.
- Batch multi-fila: legacy guarda por fila.
- Filtros/busqueda: no hay contrato observable.
- Selector de semana/drawer: no aplican.

## Decisiones pendientes

Ninguna. Si implementacion descubre otro almacenamiento operativo del nombre, otro delimitador
real, un tipo permitido adicional o identificaciones alfanumericas ya soportadas por un schema
distinto, debe detenerse, aportar evidencia y enmendar esta spec antes de cambiar dominio.

## Siguiente gate

Invocar superpowers:writing-plans para
docs/superpowers/plans/2026-08-30-s14-subcontratistas-react.md, autorrevisarlo, actualizar el atlas y
continuar S15. No implementar S14 en esta sesion.
