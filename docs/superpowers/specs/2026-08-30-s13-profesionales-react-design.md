---
capa: fuente
tipo: spec
estado: autorrevisado
id: S13
fecha: 2026-08-31
superficie: profesionales
rutas:
  - "/profesionales"
depende_de: [T01, S07, S08, S24]
views: [VIEW-32]
areas: [arquitectura, frontend, profesionales, rbac, accesibilidad, design-system]
fuente: "auditoria de public/index.php, ProfesionalesController, ProfesionalesApiController, ProjectProfessionalsSyncService, RbacCatalog, Database, VIEW-32, CSS, manifiesto y pruebas en shell-minimo-react, 2026-08-31"
resumen: "Migracion vertical S13 de Profesionales a la SPA React principal: lectura pura por proyecto, sincronizacion explicita, CRUD con autosave, reglas de identidad administrada, dependencias, alta, eliminacion, recarga, CSV, tabla desktop/tablet, tarjetas moviles, oscuro/claro y contratos Zod/PHP, sin tocar admin, RLS, schema ni datos."
---

# S13 — Profesionales en React

> **Estado:** diseño tecnico autorrevisado. No quedan decisiones de negocio, producto, estrategia o
> PM que bloqueen el plan. Esta spec no autoriza implementacion, commits, DDL/DML, cambios RLS,
> cambios de permisos, deploy, publicacion ni trabajo en /admin/. Su plan se escribe a continuacion
> con superpowers:writing-plans, conforme al programa aprobado de 27 specs y 27 planes.

## Relacion con el programa

Esta spec desarrolla S13 del atlas de migracion integral y consume:

- la spec maestra de migracion React + TypeScript;
- T01 para sesion, proyecto activo, sidebar, temas, router, cliente HTTP y errores globales;
- S07 y S08 como consumidores del catalogo de profesionales en Programacion Intermedia y Semanal;
- S24 como destino futuro del enlace BI Responsables;
- la frontera RLS ya documentada, que se conserva sin cambios.

VIEW-32 es views/profesionales/profesionales.view.php. S13 posee la pagina, sus tres pares
metodo/ruta actuales y el comportamiento de catalogo que hoy vive en
ProfesionalesApiController y ProjectProfessionalsSyncService. No posee la administracion de
usuarios, membresias o roles de /admin/. Tampoco posee las asignaciones de responsables de S05,
S07 o S08: solo publica un catalogo consistente para que esas superficies lo consuman.

S13 no crea un segundo shell, selector de proyecto, selector de semana, tema, cliente HTTP o matriz
de roles. La pantalla es project-scoped y no es week-scoped. Cambiar la semana global no altera su
coleccion.

## Resultado buscado

Profesionales deja de ser una pagina PHP con jQuery y Handsontable y pasa a ser una ruta de la SPA
principal que:

1. carga exclusivamente los profesionales del proyecto activo;
2. no acepta prefijo de base, project_id ni rol enviados por el navegador;
3. separa la lectura ordinaria de la sincronizacion que hoy escribe durante la carga;
4. muestra la misma identidad, correo, cargo, estado activo y restricciones por fila;
5. conserva alta, edicion, autosave, borrado, recarga y exportacion CSV;
6. normaliza nombre, correo, cargo y booleanos igual en cliente y servidor;
7. conserva los doce cargos permitidos como catalogo entregado por el servidor;
8. impide editar identidad, activar, desactivar o borrar cuando las reglas reales lo prohiben;
9. mantiene sincronizados los nombres dependientes dentro de una transaccion;
10. bloquea el borrado cuando existe cualquier dependencia operativa conocida;
11. conserva los avisos y resumen de sincronizacion en una accion explicita;
12. ofrece tabla editable en desktop/tablet y tarjetas editables en movil;
13. conserva una ruta accesible hacia BI Responsables solo cuando el servidor la autoriza;
14. funciona con capacidad equivalente en tema oscuro y claro;
15. valida cada respuesta consumida con Zod y cada contrato PHP con pruebas sin base mutable;
16. retira VIEW-32 y los assets exclusivos solo despues de probar paridad y rollback.

Paridad no obliga a conservar un boton CSV sin manejador, una fila fantasma como formulario,
escrituras ocultas en una lectura, mensajes con excepciones, permisos derivados de un input oculto,
dependencias comprobadas por listas divergentes ni una tabla inaccesible en movil. Si obliga a
conservar datos, reglas, bloqueos, acciones, resultados, advertencias y recuperacion.

## Alcance

### Incluido

- GET /profesionales como ruta React canonica al cierre.
- GET y POST /api/profesionales/list durante la convivencia y su retiro controlado.
- POST /api/profesionales/save con modo JSON objetivo y modo form legacy transitorio.
- Un contexto React nuevo y una accion explicita de sincronizacion.
- La lista completa project-scoped y sus banderas de bloqueo.
- Los doce cargos actuales.
- Alta de profesional manual.
- Edicion de nombre, email, cargo y activo segun capacidad y estado de fila.
- Autosave serializado por fila.
- Validacion, normalizacion, duplicados y mensajes de campo.
- Renombre de referencias dependientes.
- Borrado con confirmacion y bloqueo por dependencia.
- Recarga pura.
- Exportacion CSV local.
- Acceso autorizado a BI Responsables.
- Tabla desktop/tablet y tarjetas/formularios moviles.
- Carga, vacio, error, acceso denegado, guardando, guardado, conflicto y reintento.
- Teclado, lector de pantalla, foco, touch, zoom y movimiento reducido.
- Tema oscuro y claro usando public/css/tokens.css.
- Contratos PHP, Zod, Vitest/Testing Library y Playwright interceptado.
- Corte strangler y retiro de PHP/JS/CSS exclusivos.

### Fuera de alcance

- Todo /admin/, aunque ProjectProfessionalsSyncService tenga consumidores alli.
- Crear, editar, retirar o reactivar usuarios o membresias administrativas.
- Cambiar ROLE_TO_CARGO como regla de negocio.
- Añadir, quitar o renombrar cargos.
- Cambiar capacidades lps.profesionales.ver o lps.profesionales.editar.
- Cambiar aliases, overrides, roles, grants, usuarios o credenciales.
- Cambiar RLS, ProjectScope, tablas, columnas, indices, migraciones o datos.
- Cambiar relaciones de Programa General, PI, PS, CIP o Indicadores.
- Convertir Profesionales en una superficie semanal.
- Añadir drawer contextual: el legacy no tiene uno y ninguna accion de S13 lo necesita.
- Crear busqueda, filtros u orden de servidor: el legacy no expone esos contratos.
- Migrar BI Responsables, que pertenece a S24.
- Regenerar goldens visuales sin aprobacion explicita.
- Implementar, probar contra datos reales, commitear o publicar en esta sesion documental.

## Punto de partida medido

### React

- SpaRouter solo sirve /app.
- Rutas muestra login, selector de proyecto o un shell con el nombre del proyecto.
- La sidebar enlaza /profesionales, pero la navegacion hace carga completa hacia VIEW-32.
- No existe modulo, esquema Zod, gateway, estado, tabla, tarjeta ni prueba React de Profesionales.
- NavegacionLateral contiene una tabla local ocultasPorRol; T01 debe sustituirla por capacidades
  efectivas antes del corte S13.
- cliente.ts es el unico fetch actual y valida respuestas con Zod.
- El shell ya incluye tokens y soporte claro/oscuro, aunque T01 fija el fallback final oscuro.

### Legacy

Inventario exacto:

| ID | Metodo y ruta | Guard actual | Efecto observable |
|---|---|---|---|
| S13-LEG-01 | GET /profesionales | solo autenticacion en controlador | renderiza VIEW-32 y token profesionales |
| S13-LEG-02 | GET /api/profesionales/list | profesionales.ver | sincroniza con DML y luego lista |
| S13-LEG-03 | POST /api/profesionales/list | profesionales.ver | misma sincronizacion/lista; es el consumo de VIEW-32 |
| S13-LEG-04 | POST /api/profesionales/save | profesionales.editar + CSRF | multiplexa guardar_cambios, crear y eliminar |

La vista carga:

- jQuery desde CDN;
- Bootstrap/Popper desde CDN;
- Font Awesome y design system;
- Handsontable local;
- scripts legacy de navegacion, CSRF, BI y ancho de tabla;
- un input con prefijo de base y otro con rol crudo.

La grilla tiene seis columnas:

1. ID oculto;
2. Nombre;
3. Correo;
4. Cargo como dropdown;
5. Activo como checkbox;
6. Acciones, con eliminar o candado.

Handsontable activa menu contextual, resize manual, cabeceras navegables, ajuste de texto y una fila
vacante. No activa el plugin filters, dropdown de filtros, search ni columnSorting. Por tanto S13 no
declara paridad ficticia de filtros o busqueda. La lista se ordena por id ascendente en servidor.

### Comportamiento de carga

VIEW-32 llama POST /api/profesionales/list?db=... con opcion=listar. El controlador:

1. confia en el prefijo enviado y lo valida por regex;
2. ejecuta syncProjectProfessionals;
3. resuelve project_id desde el prefijo;
4. consulta profesionales no vacios del proyecto, por id ascendente;
5. calcula dependencias;
6. decora cada fila con estado administrativo y membresia;
7. calcula acciones por fila;
8. devuelve filas y, si aplican, sync_warnings y sync_summary.

La respuesta observable de lista contiene por fila:

| Campo | Tipo actual | Significado |
|---|---|---|
| id | numero numerico en JSON | identidad local por proyecto |
| nombre | string | nombre visible y clave textual de varias relaciones legacy |
| email | string | correo normalizado conceptualmente |
| cargo | string | uno de doce cargos o vacio si esta bloqueado por duplicado Admin |
| activo | boolean | disponibilidad operativa |
| has_dependencies | boolean | impide borrar |
| is_admin_managed | boolean | email existe en general_usuarios |
| is_current_member | boolean | miembro elegible del proyecto actual |
| is_blocked | boolean | conflicto Admin o usuario retirado |
| block_reason | string o null | razon visible |
| can_edit_identity | boolean | nombre/email/cargo mutables por regla de fila |
| can_edit_active | boolean | activo mutable por regla de fila |
| identity_edit_reason | string o null | razon de identidad bloqueada |
| active_edit_reason | string o null | razon de activo bloqueado |
| can_delete | boolean | borrado permitido por regla de fila |
| delete_reason | string o null | razon de borrado bloqueado |

Estas banderas no incorporan hoy la capacidad global del usuario. El target combina acciones del
contexto con acciones de fila; React nunca concluye permiso a partir del rol.

### Alta, edicion y autosave

- minSpareRows=1 actua como borrador.
- Una fila completamente vacia no se envia.
- Un borrador incompleto permanece local y no se envia.
- Cuando nombre, email y cargo estan completos, la vista valida y crea automaticamente.
- Los cambios de filas existentes se agrupan por fila.
- Cualquier cambio de identidad no autorizado se revierte localmente y explica la razon.
- Un cambio de Activo no autorizado se revierte localmente.
- Los cambios validos se envian inmediatamente a guardar_cambios.
- Un error de servidor recarga la lista completa.
- El exito muestra Guardado.

El target conserva autosave para filas existentes. Sustituye la fila fantasma por una fila o tarjeta
de alta explicitamente rotulada con boton Agregar profesional. Esto conserva la capacidad y evita
crear por accidente cuando el usuario solo completa parcialmente un formulario.

### Validacion y normalizacion

Cliente y servidor actuales aplican:

- nombre obligatorio;
- trim y colapso de espacios consecutivos;
- correo obligatorio;
- correo a minusculas;
- formato de email;
- cargo obligatorio;
- cargo incluido en el catalogo exacto;
- email unico dentro del proyecto, comparado normalizado;
- Activo convertido a 1 o 0.

Los doce cargos exactos son:

1. Administrador
2. Residente de Obra
3. Residente SST
4. Residente Ambiental
5. Residente Oficina Técnica
6. Profesional Diseño y Construcción Virtual
7. Maestro de Obra
8. Almacenista
9. Director de Obra
10. Residente SST + Ambiental
11. Coordinador de Obras
12. Gerente de Proyecto

El servidor sigue siendo autoridad. La validacion cliente mejora feedback, pero nunca sustituye
revalidacion scoped, duplicados, bloqueos y dependencias.

### Reglas de origen administrativo

La fuente compara email normalizado con general_usuarios y miembros elegibles:

| Estado | Identidad | Activo | Borrar |
|---|---|---|---|
| Manual, no bloqueado | editable | editable | si no tiene dependencias |
| Admin, miembro actual unico | solo lectura | editable | no |
| Admin, ya no es miembro actual | bloqueada | bloqueado | no |
| Email duplicado en Admin | bloqueada | bloqueado | no |

S13 conserva los mensajes funcionales, pero reemplaza la instruccion Gestiona sus cambios alli por
una explicacion neutral. /admin/ esta excluido y React no enlaza ni intenta abrirlo.

### Sincronizacion auditada

ProjectProfessionalsSyncService considera miembros con estos roles exactos:

| Rol canonico | Cargo fallback |
|---|---|
| A | Administrador |
| D | Director de Obra |
| DCV | Profesional Diseño y Construcción Virtual |
| G | Residente Ambiental |
| OT | Residente Oficina Técnica |
| R | Residente de Obra |
| S | Residente SST |
| SG | Residente SST + Ambiental |

La sincronizacion:

- lee miembros y usuarios administrativos;
- valida nombre y email;
- inserta miembros que faltan;
- actualiza nombre canonico y cargo;
- desactiva administrados retirados;
- bloquea correos duplicados en Admin;
- consolida profesionales duplicados por correo;
- elige sobreviviente por cantidad de dependencias y luego menor id;
- reescribe referencias por nombre;
- elimina duplicados;
- usa una transaccion propia cuando no existe otra;
- devuelve inserted, reactivated, updated, blocked, deduplicated y warnings.

reactivated existe en el resumen pero la implementacion auditada no lo incrementa. S13 conserva el
campo por compatibilidad y lo caracteriza; no inventa una reactivacion.

La sincronizacion es DML significativo. Nunca puede ejecutarse al abrir, recargar, exportar, buscar
contexto ni por un usuario con solo profesionales.ver.

### Dependencias auditadas

El borrado actual consulta cuatro referencias por nombre:

- programa.Responsable_AIA;
- cip.profesional;
- programacion_semanal.Responsable_AIA;
- programa_consolidado.Responsable_AIA.

El renombre y la deduplicacion consultan ademas:

- indicadores_generales.subcontratista_profesional.

La diferencia es un defecto de integridad. El target usa una sola fuente canonica con las cinco
referencias para conteo, bloqueo de borrado y renombre. No crea claves, columnas ni migraciones.

El renombre manual actual actualiza la fila y luego dependencias sin transaccion envolvente. El
target hace ambas cosas atomicamente. Un fallo no deja mitad del nombre nuevo y mitad del viejo.

### Exportacion, recarga y BI

- El boton Exportar existe, pero no tiene listener en VIEW-32 ni en un asset cargado por la vista.
- El manifiesto y la ficha maestra exigen CSV; S13 entrega una exportacion local funcional.
- Recargar vuelve a invocar la lista actual y por eso hoy sincroniza; el target separa ambas acciones.
- BiAccessComponent muestra BI Responsables cuando preview, capacidad y alcance de proyecto lo
  permiten; el destino es /bi/responsables con contexto autorizado.

### Responsive y accesibilidad actuales

- El manifiesto declara solo desktop, dark, estado normal y miembro autenticado.
- CSS fija el body sin overflow y entrega el scroll a Handsontable.
- No existe tarjeta movil ni formulario movil.
- El boton bloqueado usa aria-disabled, sigue siendo focalizable y anuncia el motivo.
- navigableHeaders permite llegar a cabeceras.
- No hay evidencia declarada de light, vacio, error, solo lectura o movil.
- Existe un golden dark 1180x820 que no se modifica sin aprobacion.

## Defectos y contradicciones que la migracion debe cerrar

1. La pagina HTML solo exige autenticacion, no profesionales.ver.
2. GET y POST list escriben aunque su nombre y permiso sean de lectura.
3. El navegador decide el prefijo de proyecto.
4. El rol viaja en un input oculto.
5. La respuesta de error concatena excepciones internas.
6. Las acciones de fila no incorporan la capacidad global.
7. El guardado multiplexado acepta arrays flexibles de columnas.
8. Varios cambios pueden producir exito parcial y warning.
9. Renombre de fila y dependencias no es atomico.
10. Borrado y renombre usan inventarios de dependencias distintos.
11. El boton CSV no hace nada.
12. La fila vacante puede crear al completar el ultimo campo sin confirmacion explicita.
13. Error de red al borrar no tiene recuperacion visible equivalente.
14. El bloqueo administrado menciona una superficie /admin/ fuera de alcance.
15. Solo existe tabla desktop; en movil la edicion depende de una grilla comprimida.
16. ID puede ser secuencia por proyecto en el contrato global; no debe asumirse AUTO_INCREMENT.
17. ProjectProfessionalsSyncService tiene consumidores en /admin/ y no puede romperse al extraerlo.

## Decisiones de dominio

### Identidad e ID

- id es entero positivo y solo es significativo dentro del project_id actual.
- React nunca crea ni calcula el id.
- La escritura usa el mecanismo project-scoped vigente de Database.
- No se usa lastInsertId como unica fuente en el contrato de aplicacion.
- nombre sigue siendo la clave textual de dependencias legacy hasta que otro frente autorizado
  cambie el dominio; S13 no cambia esa representacion.

### Normalizacion

La funcion canonica:

- nombre: trim y espacios internos colapsados;
- email: trim y minusculas Unicode cuando esta disponible;
- cargo: trim y espacios internos colapsados;
- activo: boolean estricto en el wire objetivo.

El servidor devuelve la fila canonica despues de crear o actualizar. React reemplaza su copia por
esa fila; no presupone que el valor escrito es el persistido.

### Duplicados

- La unicidad funcional es email normalizado dentro del proyecto.
- Crear o cambiar a un email usado devuelve conflicto estable.
- La comprobacion se repite dentro de la operacion de escritura.
- Un email administrado no puede convertirse en profesional manual.
- La consolidacion de duplicados preexistentes solo ocurre en Sincronizar profesionales.
- Cargar o recargar nunca deduplica.

### Acciones por fila

Las acciones efectivas se calculan asi:

~~~text
canEditIdentity = context.actions.editIdentity
                  AND row_rule.can_edit_identity

canEditActive   = context.actions.editActive
                  AND row_rule.can_edit_active

canDelete       = context.actions.delete
                  AND row_rule.can_delete
~~~

React consume los resultados y presenta razones. El servidor vuelve a resolverlos al mutar.

### Transacciones

- Crear es una operacion atomica.
- Actualizar Activo administrado es una operacion atomica.
- Actualizar identidad manual incluye fila y cinco dependencias en una transaccion.
- Borrar vuelve a comprobar dependencias dentro de la operacion.
- Sincronizar conserva su transaccion actual y no se anida de forma insegura.
- No hay guardado batch de varias filas en S13.
- El cliente serializa autosaves de una misma fila y permite paralelismo entre filas distintas.

## Contratos HTTP objetivo

### Inventario y convivencia

| ID | Metodo y ruta | Estado objetivo |
|---|---|---|
| S13-API-01 | GET /api/profesionales/context | nuevo; contexto puro |
| S13-API-02 | GET /api/profesionales/list | adaptado; lectura pura |
| S13-API-03 | POST /api/profesionales/sync | nuevo; sincronizacion explicita |
| S13-API-04 | POST /api/profesionales/save | adaptado; union JSON create/update/delete |
| S13-COMP-01 | POST /api/profesionales/list | alias legacy durante piloto; se retira al corte |
| S13-COMP-02 | POST form /api/profesionales/save | modo legacy durante piloto; se retira al corte |

Todos exigen sesion y proyecto activo. Context/list exigen lps.profesionales.ver. Sync y save exigen
lps.profesionales.editar. Mutaciones exigen CSRF con purpose profesionales.

Durante el piloto:

- VIEW-32 sigue usando POST list y form save;
- React usa GET context, GET list, POST sync y JSON save;
- GET list ya es puro;
- POST list conserva temporalmente sync+list solo para VIEW-32;
- el alias nunca es consumido por React;
- al cortar /profesionales a SPA se retira POST list y el modo form.

### Scope del servidor

Cada endpoint:

1. valida sesion;
2. exige proyecto activo;
3. resuelve ProjectScope;
4. exige capacidad;
5. deriva project_id y compatibilidad de prefijo en servidor;
6. ignora y rechaza autoridad de project_id, db, Base_de_Datos o role del body/query;
7. usa queries preparadas y scope;
8. devuelve no-store;
9. no filtra excepciones, SQL ni nombres internos.

### S13-API-01 — contexto

Respuesta 200:

~~~json
{
  "status": "success",
  "data": {
    "actions": {
      "view": true,
      "create": true,
      "editIdentity": true,
      "editActive": true,
      "delete": true,
      "sync": true,
      "exportCsv": true,
      "openBi": true
    },
    "cargos": [
      "Administrador",
      "Residente de Obra"
    ],
    "links": {
      "bi": "/bi/responsables?project_id=17"
    },
    "csrfToken": "opaque-or-null"
  }
}
~~~

Reglas:

- cargos contiene los doce valores completos y en el orden auditado;
- view/exportCsv se derivan de profesionales.ver;
- create/editIdentity/editActive/delete/sync se derivan de profesionales.editar;
- openBi y links.bi los resuelve BiAccessComponent para responsables;
- links.bi es null cuando openBi=false;
- csrfToken es null cuando no existe ninguna mutacion permitida;
- no incluye rol, prefijo, Base_de_Datos, tablas ni permisos crudos.

### S13-API-02 — lista pura

Sin query de autoridad. Respuesta 200:

~~~json
{
  "status": "success",
  "data": [
    {
      "id": 24,
      "nombre": "Ana Perez",
      "email": "ana@example.com",
      "cargo": "Residente de Obra",
      "activo": true,
      "has_dependencies": false,
      "is_admin_managed": false,
      "is_current_member": false,
      "is_blocked": false,
      "block_reason": null,
      "can_edit_identity": true,
      "can_edit_active": true,
      "identity_edit_reason": null,
      "active_edit_reason": null,
      "can_delete": true,
      "delete_reason": null
    }
  ]
}
~~~

La forma conserva los nombres auditados para no duplicar adaptadores en S07/S08. Cambios:

- id se serializa siempre como entero;
- email sale normalizado;
- booleanos son booleanos reales;
- razones son string no vacio o null;
- la lista solo lee;
- no devuelve sync_summary ni sync_warnings;
- ordena id ascendente;
- vacio es status success con data=[].

Las cinco dependencias alimentan has_dependencies. No se entregan nombres de tabla ni conteos
internos.

### S13-API-03 — sincronizacion explicita

Peticion:

~~~json
{}
~~~

Con header CSRF administrado por el cliente T01. Respuesta 200:

~~~json
{
  "status": "success",
  "data": {
    "summary": {
      "inserted": 1,
      "reactivated": 0,
      "updated": 2,
      "blocked": 0,
      "deduplicated": 0
    },
    "warnings": []
  }
}
~~~

Reglas:

- solo existe como POST;
- requiere edit;
- ejecuta una vez por activacion confirmada;
- no reintenta automaticamente;
- warnings es una lista deduplicada;
- los cinco contadores son enteros no negativos;
- React llama GET list despues del exito;
- un fallo mantiene las filas anteriores y ofrece reintento manual;
- la accion se rotula Sincronizar profesionales y explica que actualiza desde miembros del proyecto;
- no se ejecuta al montar, recargar, exportar ni volver de otra ruta.

### S13-API-04 — guardado JSON

La peticion es una union discriminada estricta.

Crear:

~~~json
{
  "action": "create",
  "professional": {
    "nombre": "Ana Perez",
    "email": "ana@example.com",
    "cargo": "Residente de Obra"
  }
}
~~~

Actualizar uno o mas campos de una fila:

~~~json
{
  "action": "update",
  "id": 24,
  "changes": {
    "nombre": "Ana Maria Perez",
    "activo": false
  }
}
~~~

Eliminar:

~~~json
{
  "action": "delete",
  "id": 24
}
~~~

Reglas:

- create no acepta id ni activo; nace activo igual que legacy;
- update exige id positivo y al menos una clave;
- changes solo acepta nombre, email, cargo y activo;
- identidad administrada rechaza nombre/email/cargo;
- identidad manual valida la fila completa despues del merge;
- delete vuelve a evaluar origen, bloqueo y cinco dependencias;
- claves extra se rechazan;
- no se acepta un array de filas;
- no hay exito parcial.

Respuesta create/update:

~~~json
{
  "status": "success",
  "data": {
    "action": "update",
    "professional": {}
  }
}
~~~

professional cumple exactamente el esquema de fila de S13-API-02.

Respuesta delete:

~~~json
{
  "status": "success",
  "data": {
    "action": "delete",
    "deletedId": 24
  }
}
~~~

### Errores estables

| HTTP | code | Caso |
|---|---|---|
| 400 | INVALID_JSON | cuerpo ilegible |
| 401 | UNAUTHENTICATED | sesion ausente o vencida |
| 403 | PROJECT_REQUIRED | sin proyecto activo |
| 403 | FORBIDDEN | capacidad insuficiente |
| 403 | CSRF_INVALID | token ausente o invalido |
| 404 | PROFESSIONAL_NOT_FOUND | id no pertenece al proyecto |
| 409 | DUPLICATE_EMAIL | correo normalizado ya usado |
| 409 | ADMIN_MANAGED | identidad o borrado administrado |
| 409 | PROFESSIONAL_BLOCKED | conflicto Admin o miembro retirado |
| 409 | DEPENDENCIES_EXIST | borrado con referencias |
| 422 | INVALID_PROFESSIONAL | nombre/email/cargo/activo invalido |
| 500 | WRITE_FAILED | fallo transaccional registrado |
| 500 | SYNC_FAILED | fallo de sincronizacion registrado |

Forma:

~~~json
{
  "status": "error",
  "error": {
    "code": "DUPLICATE_EMAIL",
    "message": "Ya existe un profesional con ese correo.",
    "fields": {
      "email": "Ya existe un profesional con ese correo."
    }
  }
}
~~~

fields es opcional y solo contiene claves editables. No hay texto de excepcion, SQL, tabla, prefijo
ni stack trace. 401 activa recuperacion global de sesion. 403 de permiso muestra acceso denegado,
no una tabla vacia.

## Permisos y acciones efectivas

### Capacidades

El catalogo vigente define:

- lps.profesionales.ver;
- lps.profesionales.editar.

La matriz fallback auditada permite:

- A: lectura y edicion por wildcard;
- D: lectura y edicion;
- R: lectura, no edicion;
- DCV: lectura, no edicion;
- OT: lectura, no edicion;
- V: lectura por allRead;
- G, S, SG y C: sin acceso fallback;
- P se normaliza como D;
- overrides efectivos pueden cambiar cualquier fallback.

Esta lista es caracterizacion de pruebas, no logica React. El servidor usa RbacService y
RbacManager efectivos.

### Composicion de acciones

| Accion UI | Capacidad | Regla adicional |
|---|---|---|
| ver pagina/lista | profesionales.ver | proyecto activo |
| recargar | profesionales.ver | lectura pura |
| exportar CSV | profesionales.ver | datos cargados |
| abrir BI | politica BI efectiva | href autorizado |
| crear | profesionales.editar | datos validos y email manual |
| editar identidad | profesionales.editar | fila manual y no bloqueada |
| editar Activo | profesionales.editar | fila no bloqueada; admin solo si miembro actual |
| eliminar | profesionales.editar | manual, no bloqueada y sin dependencias |
| sincronizar | profesionales.editar | confirmacion y CSRF |

Los controles no permitidos se ocultan cuando no aportan explicacion. Los bloqueos propios de una
fila permanecen visibles con aria-disabled y razon accesible.

## Arquitectura React

### Limites

~~~text
RutaProfesionales
  -> useProfessionals
      -> professionalsGateway
          -> frontend/src/lib/api/cliente.ts
              -> PHP context/list/sync/save
  -> ProfessionalsToolbar
  -> ProfessionalsTable >= 768
  -> ProfessionalsCards < 768
  -> CreateProfessional
  -> ConfirmDeleteDialog
  -> SaveStatusAnnouncer
~~~

Solo cliente.ts llama fetch. El gateway conoce rutas, Zod, JSON, CSRF y errores tipados. El hook
orquesta carga, recarga, colas por fila, sincronizacion y recuperacion. Los componentes reciben
modelos y callbacks.

### Estado

El modulo conserva:

- context;
- serverRows, ultima copia confirmada;
- draftsById, edicion local;
- createDraft;
- rowSaveState por id;
- listState;
- syncState;
- deleteCandidate;
- lastFeedback.

No introduce Redux, query library, form library, CSS-in-JS ni grid comercial. El volumen y cinco
columnas visibles no justifican una dependencia de grilla.

### Carga y recarga

1. Carga context y list en paralelo, cancelables al desmontar.
2. No monta el editor hasta validar ambas respuestas.
3. Recargar solo repite GET list.
4. Si hay borradores sucios, pide confirmacion antes de reemplazarlos.
5. Error de contexto bloquea la superficie.
6. Error de lista conserva una lista previamente valida y presenta estado stale.
7. Ninguna lectura llama sync.

### Autosave por fila

- Nombre/email guardan al blur o Enter si cambiaron y la fila es valida.
- Cargo y Activo guardan al cambiar.
- Escape restaura el ultimo valor confirmado del control activo.
- Mientras una fila guarda, nuevos cambios se coalescen en una cola de esa fila.
- Las mutaciones nunca se cancelan ni reintentan automaticamente.
- El exito sustituye serverRows y draft por la fila canonica.
- Un 422 conserva el texto local, asocia el mensaje al campo y enfoca el primer error.
- Un 409 refresca solo la fila/lista y explica el conflicto.
- Un 500 conserva el borrador y ofrece Reintentar.
- Guardados de filas distintas pueden avanzar independientemente.
- Navegar con guardados pendientes muestra el mecanismo preventivo de T01.

### Alta

Desktop/tablet usa una fila de formulario claramente separada de los datos. Movil usa una tarjeta
Nuevo profesional. Ambas:

- tienen labels visibles o accesibles;
- no envian hasta pulsar Agregar profesional;
- validan los tres campos;
- deshabilitan doble envio;
- conservan el borrador tras error;
- limpian el formulario y enfocan Nombre tras exito;
- insertan la fila devuelta en orden por id.

### Borrado

- El boton permitido abre dialogo accesible con nombre y consecuencia.
- Confirmar llama delete una vez.
- Cancelar restaura foco al boton de origen.
- El bloqueo es focalizable, anuncia delete_reason y no abre confirmacion.
- DEPENDENCIES_EXIST actualiza la fila y anuncia que aparecieron referencias.
- El exito elimina la fila y mueve foco al siguiente registro, anterior o encabezado.
- No se ofrece deshacer ficticio porque el servidor borra de inmediato.

## Composicion visual y responsive

### Encabezado y toolbar

- Eyebrow Informacion.
- h1 Profesionales.
- Texto corto: catalogo de responsables disponibles para el proyecto.
- Conteo local total y activos, derivado de filas cargadas.
- Estado stale/sincronizacion cuando aplique.
- Recargar.
- Exportar CSV.
- Sincronizar profesionales solo con accion.
- BI Responsables solo con href autorizado.

No hay selector de semana. El contexto de proyecto pertenece a T01.

### Desktop y tablet

A 768 px o mas:

- tabla HTML semantica, no Handsontable;
- caption accesible;
- encabezado sticky dentro de su contenedor;
- columnas Nombre, Correo, Cargo, Activo y Acciones;
- ID no visible;
- inputs/select/checkbox nativos;
- estado manual/administrado/bloqueado mediante texto y chip;
- razones disponibles sin depender de color o hover;
- fila de alta separada;
- scroll interno solo si la altura lo requiere;
- sin overflow horizontal de pagina a 768x1024, 1180x820 y 1440x900.

### Movil

Por debajo de 768 px:

- no se monta la tabla;
- cada profesional es una tarjeta con nombre como encabezado;
- correo, cargo, origen y estado mantienen la misma informacion;
- los campos permitidos son editables en la propia tarjeta;
- Activo es un switch/checkbox con label;
- acciones y razones permanecen disponibles;
- alta vive en tarjeta separada;
- targets tactiles de al menos 44 por 44 CSS px;
- una sola columna y sin overflow horizontal a 390x844 y 480x900.

Tabla y tarjetas consumen el mismo estado y callbacks. Nunca se montan ambas para ocultar una con
CSS.

### Sin busqueda/filtros inventados

La superficie auditada no habilita search, filters, dropdownMenu de filtros ni orden. El menu
contextual generico no constituye un contrato funcional estable. S13 conserva orden por id y
conteos simples; una futura busqueda o filtros requeriran decision y spec propia si se desean.

## CSV

La exportacion se genera localmente desde la lista cargada y no llama un endpoint.

Columnas, en este orden:

1. ID
2. Nombre
3. Correo
4. Cargo
5. Activo
6. Origen
7. Estado

Reglas:

- UTF-8 con BOM;
- separador coma y escape RFC 4180;
- saltos CRLF;
- Activo usa Si/No;
- Origen usa Manual/Administrado;
- Estado usa Activo/Inactivo/Bloqueado;
- no exporta razones, acciones, project_id, prefijo ni datos de otras obras;
- nombre profesionales-{proyecto-normalizado}-{YYYY-MM-DD}.csv;
- usa la lista actual en memoria;
- si no hay filas, el boton permanece disponible y genera solo cabecera;
- Blob, object URL y revoke se encapsulan fuera del componente;
- el flujo tiene prueba unitaria de caracteres, comas, comillas y saltos.

## Estados de experiencia

1. cargando contexto y lista;
2. acceso denegado;
3. contexto fallido con reintento;
4. lista fallida sin datos;
5. lista stale con datos previos;
6. vacio con alta permitida;
7. vacio solo lectura;
8. normal solo lectura;
9. normal editable;
10. borrador de alta;
11. alta invalida;
12. alta guardando;
13. fila editando;
14. fila guardando;
15. fila guardada;
16. validacion de campo;
17. conflicto por correo;
18. fila administrada;
19. fila bloqueada;
20. borrado bloqueado por dependencia;
21. confirmacion de borrado;
22. borrado en progreso;
23. sincronizacion confirmable;
24. sincronizacion en progreso;
25. sincronizacion con resumen;
26. sincronizacion con warnings;
27. sincronizacion fallida conservando datos;
28. CSV generado;
29. sesion vencida delegada a T01.

Error no se representa como vacio. Solo lectura no se representa deshabilitando toda la pagina.

## Accesibilidad

- Un h1 y un main, provistos en composicion con T01.
- Skip link del shell.
- Tabla con caption, thead, th scope=col y controles etiquetados.
- Tarjetas con headings y regiones distinguibles.
- Alta con fieldset/legend.
- Errores asociados por aria-describedby y resumen focalizable.
- Live region polite para guardado, recarga, CSV y sync.
- Alert para errores que requieren accion.
- aria-busy en fila/tarjeta, no en toda la aplicacion durante autosave.
- Motivos de bloqueo accesibles por foco y no solo tooltip.
- Dialogo con foco atrapado, Escape, Cancelar y retorno de foco.
- No se usa color como unica señal.
- Orden de tab estable.
- Enter guarda texto; Escape revierte control; no se interceptan teclas de lectura.
- Touch targets minimos.
- Zoom 200 por ciento sin perdida ni scroll horizontal de pagina.
- prefers-reduced-motion elimina animacion no esencial.

## Tema y design system

- Dark es default/fallback del programa.
- Light conserva capacidad, jerarquia, focus, errores, bloqueos y contraste.
- Colores, tipografia, espacios, radios, sombras, estados, focus y motion proceden de
  public/css/tokens.css o aliases semanticos canónicos.
- CSS vive en @layer module.
- No se copian hex, estilos inline, important, Bootstrap, jQuery, Font Awesome ni CSS de
  Handsontable.
- Iconos decorativos son aria-hidden; acciones conservan texto accesible.
- El golden legacy dark 1180x820 sirve como referencia de cobertura, no como layout obligatorio.
- Candidatos visuales nuevos requieren aprobacion antes de reemplazar goldens.

## Seguridad, aislamiento y RLS

S13 conserva la frontera vigente:

- no modifica RLS, schema, grants, usuarios, credenciales ni datos por documentacion;
- PHP resuelve ProjectScope desde sesion;
- cada consulta a profesionales y dependencias incluye project_id;
- ningun contrato acepta db, Base_de_Datos, project_id o role como autoridad;
- RBAC se evalua antes de servicio y datos;
- mutaciones exigen CSRF;
- sync exige editar, no solo ver;
- consultas son preparadas;
- columnas mutables tienen allowlist;
- errores externos son estables;
- logging no incluye payload completo ni secretos;
- CSV solo usa datos ya autorizados;
- no hay retry automatico de mutaciones;
- no se consulta /admin/ desde la SPA.

Las pruebas de aislamiento usan stores/fakes o fixtures de lectura. Esta fase no ejecuta DML real ni
usa rollback como permiso para escribir.

## Convivencia, corte y rollback

### Piloto

1. Los contratos y componentes se construyen detras de /app/profesionales.
2. /profesionales sigue sirviendo VIEW-32.
3. GET list se vuelve puro; React lo consume.
4. POST list conserva temporalmente el comportamiento legacy.
5. JSON save convive con form save mediante Content-Type y parser estricto.
6. Sync nuevo usa la misma frontera de dominio mediante dependencia inyectable.
7. S07/S08 adoptan GET puro y su refresh explicito segun sus propios planes.

### Corte

1. Probar paridad permitida/denegada, fila manual, administrada, bloqueada y dependiente.
2. Probar desktop/tablet/movil y oscuro/claro.
3. Probar que /profesionales y deep link sirven la SPA.
4. Añadir la ruta exacta a SpaRouter.
5. Retirar el route handler HTML de ProfesionalesController.
6. Retirar POST list y form save solo con cero consumidores.
7. Retirar VIEW-32 y public/css/profesionales.css solo con cero consumidores.
8. Conservar ProjectProfessionalsSyncService y blockProfessionalByEmail porque /admin/ los usa.
9. Conservar APIs compartidas mientras S07/S08 tengan consumidores.
10. Actualizar manifiesto, inventario y pruebas de rutas.

### Rollback

El rollback quita /profesionales del mapa SPA y restaura la ruta PHP/aliases del mismo commit de
corte. No revierte datos, no toca schema y no relaja permisos. Se ensaya antes del cierre.

## Estrategia de pruebas

### PHP sin base mutable

- inventario exacto de cuatro pares legacy y cuatro contratos target;
- page/API routing y prioridad SPA;
- contexto con view/edit, view-only, denied y overrides;
- doce cargos y orden;
- lista pura con tripwire que falla ante beginTransaction/INSERT/UPDATE/DELETE;
- fila manual, administrada actual, administrada retirada y duplicado Admin;
- cinco dependencias compartidas;
- normalizacion y validacion;
- email duplicado scoped;
- create/update/delete con stores fake;
- renombre atomico y rollback logico fake;
- sync con sincronizador fake y resumen exacto;
- CSRF, metodo, scope y errores;
- ausencia de db/project_id/role como autoridad;
- compatibilidad legacy durante piloto;
- cero consumidor antes de retirar aliases/VIEW.

No se ejecuta ProjectProfessionalsSyncService real en estos contratos.

### Zod, Vitest y Testing Library

- schemas strict de context, row, list, sync, save success y error;
- rechazo de id string, booleano numerico, razones inconsistentes y claves extra;
- gateway usa solo cliente.ts y envia JSON/CSRF correcto;
- normalizacion y validacion;
- exportador CSV;
- cola serial por fila y paralelismo entre filas;
- create draft y doble submit;
- merge de fila canonica;
- error 422, 409 y 500;
- tabla/tarjeta con mismas acciones;
- acciones globales mas acciones de fila;
- readonly;
- confirmacion y retorno de foco;
- stale data;
- sync nunca ocurre durante load/reload;
- BI solo con link autorizado.

### Playwright totalmente interceptado

- viewer permitido y editor permitido;
- rol denegado;
- proyecto activo A y respuesta de proyecto B rechazada por schema/fixture;
- tabla 768, 1180 y 1440;
- tarjetas 390 y 480;
- oscuro y claro;
- alta, autosave, conflicto, bloqueo y borrado;
- sync con resumen/warnings y fallo;
- CSV;
- teclado, zoom, reduced motion y axe;
- refresh/deep link/corte/rollback;
- consola sin errores y red sin POST inesperado.

Toda mutacion se intercepta antes de navegar. No se usa dev door, base compartida ni restauracion
SQL en la verificacion S13 documental o contractual.

### Pruebas reales prohibidas en esta fase

- tests/browser/full-app-flow.spec.mjs;
- tests/browser/support/operationalCycle.mjs;
- POST real a /api/profesionales/list;
- POST real a /api/profesionales/sync;
- POST real a /api/profesionales/save;
- cualquier flujo /admin/ que bloquee o sincronice profesionales;
- suites cuyo comportamiento DML no haya sido clasificado;
- regeneracion de screenshots;
- DDL/DML directo o rollback posterior.

## Criterios de aceptacion

1. S13-AC-01: /profesionales es una ruta React al corte y conserva deep link/refresh.
2. S13-AC-02: /admin/ no se modifica.
3. S13-AC-03: abrir y recargar no ejecutan DML.
4. S13-AC-04: sync solo ocurre por POST explicito, edit y CSRF.
5. S13-AC-05: el proyecto proviene de sesion/ProjectScope.
6. S13-AC-06: db, project_id y role cliente no tienen autoridad.
7. S13-AC-07: contexto y lista exigen profesionales.ver.
8. S13-AC-08: create/update/delete/sync exigen profesionales.editar.
9. S13-AC-09: overrides y alias se resuelven en servidor.
10. S13-AC-10: React no contiene matriz de roles.
11. S13-AC-11: los doce cargos y su orden coinciden con legacy.
12. S13-AC-12: lista conserva los dieciseis campos auditados y tipos estrictos.
13. S13-AC-13: vacio es exito con data vacia.
14. S13-AC-14: nombre colapsa espacios y correo se normaliza.
15. S13-AC-15: email invalido y duplicado se rechazan.
16. S13-AC-16: cargo fuera de catalogo se rechaza.
17. S13-AC-17: alta manual conserva borrador y evita doble envio.
18. S13-AC-18: filas existentes guardan sin boton mediante autosave.
19. S13-AC-19: autosaves se serializan por fila.
20. S13-AC-20: respuesta canonica reemplaza la copia local.
21. S13-AC-21: identidad administrada actual es readonly.
22. S13-AC-22: Activo administrado actual puede cambiar solo con edit.
23. S13-AC-23: administrado retirado queda bloqueado.
24. S13-AC-24: duplicado Admin queda bloqueado.
25. S13-AC-25: razones de bloqueo son visibles y accesibles.
26. S13-AC-26: las cinco dependencias bloquean borrado.
27. S13-AC-27: renombre actualiza las cinco dependencias atomicamente.
28. S13-AC-28: delete revalida origen, bloqueo y dependencias.
29. S13-AC-29: no existe exito parcial de varias filas.
30. S13-AC-30: recargar conserva lectura pura.
31. S13-AC-31: sync devuelve cinco contadores y warnings.
32. S13-AC-32: un fallo sync conserva la lista.
33. S13-AC-33: CSV funcional contiene siete columnas y escape correcto.
34. S13-AC-34: CSV nunca consulta otro proyecto.
35. S13-AC-35: BI Responsables solo aparece con destino autorizado.
36. S13-AC-36: no se añade selector semanal ni drawer.
37. S13-AC-37: tabla desktop/tablet conserva todos los campos y acciones.
38. S13-AC-38: tarjetas moviles conservan los mismos campos y acciones.
39. S13-AC-39: tabla y tarjetas no se montan simultaneamente.
40. S13-AC-40: no hay overflow horizontal de pagina en cinco viewports.
41. S13-AC-41: oscuro y claro tienen capacidad y contraste equivalentes.
42. S13-AC-42: teclado, foco, lector, touch, zoom y reduced motion cumplen.
43. S13-AC-43: solo cliente.ts llama fetch.
44. S13-AC-44: cada respuesta consumida tiene Zod strict.
45. S13-AC-45: cada endpoint nuevo/adaptado tiene contrato PHP.
46. S13-AC-46: errores no filtran excepciones, SQL o prefijos.
47. S13-AC-47: pruebas S13 no ejecutan DML real.
48. S13-AC-48: RLS, schema, grants, usuarios, credenciales y datos no cambian.
49. S13-AC-49: aliases/form/VIEW-32 solo se retiran con cero consumidores.
50. S13-AC-50: ProjectProfessionalsSyncService conserva consumidores /admin/ sin modificarlos.
51. S13-AC-51: rollback de ruta se ensaya sin revertir datos.
52. S13-AC-52: no se regeneran goldens sin aprobacion.

## Entregas verticales

### Entrega 1 — Dominio puro y lectura

- caracterizar cargos, locks, dependencias, acciones y normalizacion;
- crear contexto y GET list puro;
- agregar contratos PHP y Zod;
- mostrar tabla/tarjetas solo lectura.

### Entrega 2 — Alta y autosave

- create/update JSON;
- formulario de alta;
- autosave serial por fila;
- validacion, readonly y recuperacion.

### Entrega 3 — Integridad, borrado y sincronizacion

- fuente unica de cinco dependencias;
- renombre atomico;
- delete con confirmacion;
- sync explicito, resumen y warnings.

### Entrega 4 — CSV, accesibilidad y corte

- exportador CSV;
- BI autorizado;
- viewports, temas, teclado y axe;
- corte /profesionales, cero consumidores, rollback y retiro selectivo.

## Riesgos y mitigaciones

| Riesgo | Mitigacion |
|---|---|
| Romper consumidores /admin/ del sync | wrapper/puerto; no editar controladores admin; source contract |
| Hacer DML al probar list | tripwire y fakes; prohibir endpoint real |
| Perder profesional por consolidacion implicita | sync solo explicito y confirmado |
| Referencias partidas al renombrar | una transaccion y fuente unica de cinco dependencias |
| Borrar con dependencia no contada | mismo resolver para list/delete/rename |
| Carrera de autosaves | cola por fila y fila canonica de respuesta |
| Carrera de email duplicado | revalidacion servidor dentro de escritura |
| ID equivocado por contrato global | allocator project-scoped vigente; no asumir auto increment |
| Viewer ve editores por flags de fila | componer contexto global y regla de fila |
| Alias POST list sobrevive indefinidamente | cero-caller gate y prueba de retiro |
| Dos UIs divergentes | tabla/tarjeta sobre un solo estado/callbacks |
| CSV con formulas o caracteres rotos | escape RFC 4180, BOM y pruebas |
| Error expone internos | mapa estable y log server-only |
| Visual gate sobreescribe evidencia | candidatos separados y aprobacion explicita |

## Decisiones descartadas

- Mantener Handsontable: innecesario para cinco columnas y mala base movil.
- Añadir AG Grid: peso y complejidad sin necesidad funcional.
- Usar role en React: contradice RBAC efectivo y overrides.
- Reusar shell_api para mutaciones: el purpose profesionales sigue siendo estrecho.
- Sincronizar en GET: mezcla lectura y DML bajo permiso de vista.
- Sincronizar automaticamente despues de cada alta: no corresponde al flujo.
- Mantener POST list como API React: conserva side effect y forma procedural.
- Crear endpoints separados por campo: fragmenta transacciones de identidad.
- Batch de varias filas: legacy UI guarda por fila y el target evita exito parcial.
- Añadir filtros/busqueda server-side: no existe contrato observable.
- Añadir selector de semana: el modulo no es semanal.
- Añadir drawer: no hay accion contextual de S13 que lo requiera.
- Enlazar /admin/: esta expresamente excluido.
- Cambiar nombres por foreign keys: requiere schema y otro frente.
- Cambiar permisos o cargos: decision de negocio fuera de S13.

## Decisiones pendientes

Ninguna. Las decisiones tecnicas quedan fijadas por esta spec. Si durante la implementacion aparece
una regla real no visible en fuentes auditadas —por ejemplo una sexta dependencia, un cargo
autorizado adicional o una politica distinta para Activo administrado— se detiene el frente, se
documenta evidencia y se enmienda la spec antes de cambiar comportamiento.

## Siguiente gate

Invocar superpowers:writing-plans para producir
docs/superpowers/plans/2026-08-30-s13-profesionales-react.md, autorrevisarlo, actualizar el atlas y
continuar con S14. No implementar S13 en esta sesion.
