---
capa: fuente
tipo: spec
estado: vigente
id: S16
fecha: 2026-08-31
superficie: indicadores
rutas:
  - "/indicadores"
  - "/api/indicadores/context"
  - "/api/indicadores/generar"
depende_de: [T01, T03, S08, S10, S11, S13, S14, S17, S19, S23, S24]
views: [VIEW-27]
areas: [bi, design-system]
fuente: "auditoria de public/index.php, IndicadoresController, IndicadoresApiController, RbacCatalog, BiAccessComponent, BiProjectScope, schema global, VIEW-27, CSS, manifiesto, historial y pruebas en shell-minimo-react, 2026-08-31"
resumen: "Migracion vertical S16 del wrapper Indicadores LPS a la SPA React: contexto server-authoritative, iframe Power BI publish-to-web global y externo, carga/error/reintento, responsive, oscuro/claro, permisos unificados y preservacion segura del generador semanal project-scoped, sin reimplementar KPIs, tocar RLS/schema/datos ni inventar un boton de generacion."
---

# S16 — Indicadores LPS en React

> **Estado:** diseño tecnico autorrevisado y decision-complete. No quedan decisiones de negocio,
> producto, estrategia o PM que bloqueen su plan. Esta spec no autoriza implementacion, commits,
> DDL/DML, cambios RLS, cambios de permisos, deploy, publicacion ni trabajo en /admin/. Su plan se
> escribe a continuacion con superpowers:writing-plans dentro del programa aprobado de 27 specs y
> 27 planes.

## Relacion con el programa

S16 desarrolla VIEW-27, views/indicadores/indicadores.view.php, y reconcilia las dos rutas de D14.
Consume:

- T01 para sesion, proyecto activo, sidebar, tema, cliente HTTP, errores y contexto de semanas;
- S08/S10 para las fuentes de Programacion Semanal y categorias CNC que el generador agrega;
- S11/S14 para CIC y subcontratistas;
- S13/S24 para CIP y profesionales;
- T03/S17–S24 como destino BI propio futuro;
- S19 para la hoja interna Curva S, sin mezclarla con el iframe actual.

El programa aprobado exige migrar ahora el marco del informe externo, no reimplementar sus KPIs.
La jubilacion futura de Power BI y /indicadores depende del gate F5 de la Torre; no se adelanta en
S16. El corte React conserva esta superficie temporal de forma honesta y reversible.

## Resultado buscado

/indicadores pasa a la SPA principal y:

1. exige la capacidad lps.indicadores.ver en pagina, contexto y generador;
2. elimina las listas de roles duplicadas de PHP y JavaScript;
3. obtiene la configuracion del embed desde servidor solo despues de autorizar;
4. nunca incluye la URL externa en el bundle React ni en respuestas 403;
5. muestra el titulo Indicadores LPS;
6. conserva el informe Power BI publish-to-web como fuente;
7. declara que el informe actual es externo, global y no filtrado por proyecto o semana;
8. no representa el proyecto activo como filtro efectivo del informe;
9. ofrece estados de contexto, carga del iframe, demora, error, offline e indisponibilidad;
10. permite reintentar sin alterar la URL firmada;
11. conserva apertura externa y fullscreen solo para quien ya puede ver;
12. responde al ancho del shell sin observar manualmente la sidebar;
13. evita overflow de pagina en desktop, tablet y movil;
14. adapta su marco a oscuro y claro sin afirmar control del contenido remoto;
15. mantiene una alternativa y limites accesibles alrededor del iframe;
16. conserva POST /api/indicadores/generar para consumidores operativos;
17. hace esa generacion project-scoped, week-validated, CSRF-protected y atomica;
18. no añade un boton de generacion que legacy nunca mostro;
19. no ejecuta generacion en carga, recarga ni reintento del iframe;
20. retira VIEW-27 y vendors exclusivos solo con paridad y cero callers.

## Alcance

### Incluido

- GET /indicadores como ruta SPA al corte.
- GET /api/indicadores/context nuevo.
- POST /api/indicadores/generar adaptado.
- Compatibilidad form/db temporal de generar.
- Configuracion server-side del embed.
- Validacion estricta de host/path del reporte.
- Titulo y descripcion de contenido externo.
- Aviso de alcance global no project/week filtered.
- Loading inicial del contexto.
- Loading del iframe.
- onLoad.
- Error observable del elemento.
- Timeout de carga.
- Offline.
- Configuracion ausente/invalida.
- Reintento.
- Enlace externo seguro.
- allowFullScreen.
- Responsive por CSS/ResizeObserver solo si CSS no basta.
- Oscuro y claro del marco.
- Excepcion documentada para el interior cross-origin.
- Generacion semanal general/CIC/CIP exacta.
- Scope y validacion de semana.
- Transaccion y serializacion sin DDL.
- Contratos PHP, Zod, Vitest/Testing Library y Playwright interceptado.
- Corte strangler, retiro exclusivo y rollback.

### Fuera de alcance

- Todo /admin/.
- Reimplementar, rediseñar o auditar los KPIs dentro de Power BI.
- Leer el DOM, estilos, datos, filtros o errores internos del iframe.
- Power BI Embedded app-owns-data, embed tokens o JS API.
- Agregar filtro por proyecto/semana que publish-to-web no soporta.
- Prometer aislamiento de datos que el informe actual no ofrece.
- Crear dashboards React en /indicadores.
- Duplicar las ocho hojas BI propias.
- Cambiar formulas DAX, dataset, refresh o publicacion de Power BI.
- Introducir selector de proyecto/semana dentro de la pagina.
- Mostrar boton Actualizar/Generar indicadores.
- Llamar generar al montar o reintentar.
- Crear nuevas metricas o campos.
- Limpiar filas historicas CIC/CIP/indicadores.
- Cambiar categorias CNC o calculos actuales.
- Cambiar capacidades o fallback RBAC.
- Cambiar RLS, ProjectScope, schema, indices, grants, usuarios, credenciales o datos.
- Ejecutar DDL, DML o pruebas con rollback real.
- Migrar T03 o retirar Power BI en esta entrega.
- Regenerar goldens sin aprobacion.
- Implementar, commitear o publicar en esta sesion documental.

## Punto de partida medido

### React

- No existe modulo, schema Zod, gateway, wrapper ni pruebas S16.
- NavegacionLateral contiene enlace Indicadores LPS sin manifiesto server-authoritative.
- /indicadores sigue sirviendo PHP.
- T01 ya aporta proyecto y tema, pero S16 no debe inferir que el embed los consume.

### Inventario HTTP

| ID | Metodo y ruta | Guard actual | Efecto |
|---|---|---|---|
| S16-LEG-01 | GET /indicadores | auth + lista cruda G/S/SG/C | renderiza VIEW-27 |
| S16-LEG-02 | POST /api/indicadores/generar | indicadores.ver | recalcula y escribe una semana |

S16 añade:

| ID | Metodo y ruta | Guard objetivo | Efecto |
|---|---|---|---|
| S16-TGT-01 | GET /api/indicadores/context | indicadores.ver | entrega config autorizada del wrapper |

### VIEW-27

La vista actual:

- emite shell/sidebar PHP;
- sincroniza semana solicitada aunque el informe no es week-scoped;
- carga semanas para el flyout;
- contiene h1 visualmente oculto;
- deja el titulo visible a un inyector global;
- monta un div vacio;
- carga jQuery, Popper, Bootstrap, DataTables, datepicker, Google Charts, AnyChart y Select2;
- carga bootstrap global de contexto, permisos, sidebar y BI;
- crea el iframe por innerHTML;
- observa resize y data-sidebar-state;
- calcula width/height inline con ratio 980/600;
- contiene una lista client-side G/S/SG/C como defensa duplicada;
- conserva codigo muerto de DataTables y permisos generales;
- no ofrece loading ni error del iframe.

Ninguno de los vendors de la lista es necesario para el wrapper React.

### Informe externo

El embed actual es Power BI publish-to-web:

- su origen es app.powerbi.com;
- la URL esta hardcodeada en VIEW-27;
- es publica por enlace;
- no acepta el proyecto por query como filtro controlado por esta app;
- no acepta semana;
- no usa la Power BI JavaScript API;
- todos los proyectos autorizados ven el mismo informe;
- el repositorio no controla su tema, contraste, foco, textos, datos ni errores internos;
- el onload del iframe solo demuestra carga del documento, no salud o frescura de sus visuales.

S16 no oculta estas limitaciones. El marco muestra:

> Informe externo compartido. El contenido de Power BI no se filtra por el proyecto ni la semana
> seleccionados en Last Planner AIA.

La frase no implica que cualquier usuario de Internet deba conocer la ruta desde la app: contexto y
pagina siguen autorizados. Tampoco convierte publish-to-web en un control de acceso.

### Permisos actuales

La pagina niega por rol crudo G, S, SG y C. El API usa lps.indicadores.ver. En fallback:

| Rol normalizado | indicadores.ver |
|---|---:|
| A | si |
| D | si |
| R | si |
| DCV | si |
| OT | si |
| V | si |
| G | no |
| S | no |
| SG | no |
| C | no |

P normaliza a D. Overrides son autoritativos. La lista cruda actual ignora ambos; el target la
elimina y usa un unico IndicatorsActionPolicy basado en RbacService.

### Generador actual

POST /api/indicadores/generar:

- acepta semana y db por POST o GET;
- deriva project_id desde el prefijo cliente;
- no valida que la semana exista en el proyecto;
- no exige CSRF;
- filtra solo por indicadores.ver;
- expone excepciones en el mensaje;
- ejecuta multiples escrituras sin transaccion;
- no lo llama VIEW-27;
- lo llaman moduleFlows y el workflow E2E de dos semanas;
- actualiza tres proyecciones.

Proyeccion consolidado general:

- fuente: programacion_semanal del proyecto/semana;
- poblacion: Activa=1 o Activa=NA;
- PAC: AVG(PAC), o NA si no hay filas;
- P_Completado: AVG(P_Completado), o NA si no hay filas;
- cuenta ocho Categoria_CNC exactas;
- inserta o actualiza indicadores_generales para consolidado general.

Categorias persistidas:

1. Rendimiento;
2. Programación;
3. Mano de Obra;
4. Materiales;
5. Equipos;
6. Disenos;
7. Administrativas;
8. Causas Exógenas.

El matching conserva la semantica/collation actual. No normaliza ni agrega categorias en S16.

Proyeccion CIC:

- obtiene Sub_Contratista distintos no vacios de la misma poblacion;
- calcula AVG(P_Completado) y AVG(PAC);
- inserta o actualiza CIC por project_id + Semana + subcontratista;
- toca solo P_Completado y PAC en filas existentes.

Proyeccion CIP:

- obtiene Responsable_AIA distintos no vacios;
- calcula los mismos promedios;
- inserta o actualiza CIP por project_id + Semana + profesional;
- toca solo P_Completado y PAC en filas existentes.

No elimina filas que ya no aparecen, no actualiza acumulados y no crea registros por cada
subcontratista/profesional fuera de la semana. S16 preserva esos limites.

## Decisiones de producto

### Wrapper, no dashboard

La unidad React contiene:

- encabezado;
- aviso de contenido externo/no filtrado;
- estado;
- marco;
- iframe;
- acciones Abrir en otra pestaña y Reintentar cuando correspondan.

No interpreta ni replica el reporte.

### Generacion no visible

La auditoria de historia y arbol actual no encuentra btn_agregar_indicadores ni otro caller de
generar en VIEW-27. El endpoint pertenece al workflow LPS y no a la interaccion del wrapper.

Decision:

- se conserva y endurece el endpoint;
- se documenta en el action policy server-side;
- no se expone como boton;
- la SPA no lo llama;
- los consumidores operativos migran a JSON/CSRF/scope;
- quitarlo exige un programa distinto y cero consumidores.

### Alcance global explicito

El shell conserva proyecto activo porque es contexto global de la aplicacion. S16:

- no añade el nombre del proyecto al titulo del informe;
- no añade chips que digan Filtrado por proyecto/semana;
- no modifica la URL con project_id, db o semana;
- muestra el aviso de informe compartido;
- no guarda un supuesto filtro en URL/localStorage;
- no cambia el iframe al cambiar solo la semana.

Un cambio real a Power BI Embedded o a un informe por proyecto requiere otra spec de seguridad y
contratos.

## Contratos HTTP objetivo

### Convencion

Los endpoints objetivo:

- exigen sesion y proyecto activo;
- derivan project_id/prefix en servidor;
- responden application/json;
- usan success, data y error estable;
- no exponen SQL, paths, stack ni excepciones;
- rechazan db, Base_de_Datos, project_id, prefix o role como autoridad;
- usan no-store para contexto;
- tienen Zod strict y pruebas PHP sin base mutable.

Error:

    {
      "success": false,
      "error": {
        "code": "FORBIDDEN",
        "message": "No tienes acceso a Indicadores LPS.",
        "requestId": "..."
      }
    }

Codigos: UNAUTHENTICATED, FORBIDDEN, PROJECT_REQUIRED, REPORT_UNAVAILABLE, VALIDATION_ERROR,
WEEK_NOT_FOUND, CONFLICT e INTERNAL_ERROR.

### GET /api/indicadores/context

Guard: lps.indicadores.ver.

Respuesta disponible:

    {
      "success": true,
      "data": {
        "title": "Indicadores LPS",
        "report": {
          "available": true,
          "provider": "power-bi",
          "mode": "publish-to-web",
          "embedUrl": "https://app.powerbi.com/view?...",
          "aspectRatio": {"width": 980, "height": 600},
          "projectScoped": false,
          "weekScoped": false,
          "external": true
        },
        "actions": {
          "view": true,
          "openExternal": true,
          "allowFullscreen": true
        }
      }
    }

Respuesta autorizada pero sin configuracion valida:

    {
      "success": true,
      "data": {
        "title": "Indicadores LPS",
        "report": {
          "available": false,
          "reason": "REPORT_UNAVAILABLE"
        },
        "actions": {
          "view": true,
          "openExternal": false,
          "allowFullscreen": false
        }
      }
    }

Reglas:

- discriminated union available true/false;
- URL solo HTTPS;
- host exacto app.powerbi.com;
- path exacto /view;
- sin username/password/fragment;
- origen de configuracion server-side, no request;
- una respuesta 401/403 nunca contiene URL o fragmento de URL;
- acciones se derivan del mismo policy que T01 y la pagina;
- no incluye projectId, prefix, week ni token de Power BI Embedded;
- no incluye generate como accion visual.

La URL publish-to-web no es una credencial de app-owns-data. Aun asi, no se copia a bundle, logs,
errores, screenshots de denegado ni documentos.

### POST /api/indicadores/generar

Guard: lps.indicadores.ver + CSRF de indicadores. Content-Type objetivo JSON.

Request:

    {"week": 6}

Reglas:

- week entero positivo;
- project_id/prefix salen de sesion;
- semana debe existir en semanas_activas del proyecto;
- db/project_id/prefix/role se rechazan;
- una generacion por request;
- no acepta GET en el contrato objetivo;
- no se llama desde React S16.

Respuesta:

    {
      "success": true,
      "data": {
        "week": 6,
        "status": "generated",
        "updated": {
          "general": 1,
          "subcontractors": 4,
          "professionals": 7
        }
      }
    }

updated cuenta entidades procesadas, no filas afectadas de forma ambigua. La generacion:

1. valida antes de abrir transaccion;
2. bloquea FOR UPDATE la fila del proyecto para serializar la semana sin DDL;
3. calcula todos los agregados con project_id + week;
4. upsert logical de consolidado general;
5. actualiza/inserta CIC;
6. actualiza/inserta CIP;
7. confirma todo o revierte todo;
8. responde solo despues de commit.

No borra filas obsoletas, no toca acumulados, evaluaciones CIC, correos ni otros campos. No usa
INSERT...ON DUPLICATE KEY porque el schema no tiene unicidad compuesta que lo soporte.

Compatibilidad:

- form con semana/db permanece mientras existan callers;
- db se ignora como autoridad y debe coincidir con sesion durante la ventana;
- respuesta respuesta/mensaje legacy se adapta desde el mismo servicio;
- GET legacy se retira, no se conserva como mutacion;
- todos los consumidores migran a token CSRF y JSON;
- zero-caller precede el retiro del adaptador form.

## Servicio de generacion

Arquitectura:

    IndicatorsGenerationService
      -> IndicatorsGenerationStore
         -> PdoIndicatorsGenerationStore

El store ofrece operaciones estrechas:

- weekExists(projectId, week);
- lockProject(projectId);
- aggregateGeneral(projectId, week);
- find/upsertGeneral(projectId, week, values);
- distinctSubcontractors(projectId, week);
- aggregateSubcontractor(projectId, week, name);
- find/insert/updateCic(projectId, week, name, PAC/P_Completado);
- distinctProfessionals(projectId, week);
- aggregateProfessional(projectId, week, name);
- find/insert/updateCip(projectId, week, name, PAC/P_Completado);
- transaction primitives.

No recibe nombres de tabla del cliente. TableResolver usa el proyecto de sesion en la implementacion
PDO. Tests usan FakeIndicatorsGenerationStore y orden de llamadas; no SQL/DML real.

## Arquitectura React

Ruta propuesta:

    frontend/src/modules/indicadores/
      api/
        esquemas.ts
        gateway.ts
      components/
        MarcoInformeExterno.tsx
        EstadoInformeExterno.tsx
      hooks/
        useIndicadoresContext.ts
        useEstadoIframe.ts
      pages/
        RutaIndicadores.tsx
      indicadores.css

Limites:

- Ruta carga contexto;
- gateway es la unica capa que usa cliente.ts;
- Zod valida antes de renderizar;
- hook iframe modela estado local, no inspecciona contenido;
- marco recibe config estricta;
- no existe gateway React para generar en S16;
- un schema exportado documenta generar para consumidores frontend futuros, pero no se invoca;
- T01 mantiene sidebar/proyecto/tema;
- T03 no se copia.

## Modelo de estados del iframe

Estados:

| Estado | Entrada | Salida |
|---|---|---|
| context-loading | inicia GET context | available/unavailable/context-error |
| unavailable | report.available=false | reintento de contexto |
| iframe-loading | iframe montado | loaded/error/slow/offline |
| loaded | evento load | reload/offline posterior no invalida contenido ya cargado |
| slow | 20 s sin load | load/error/retry |
| error | evento error | retry/open external |
| offline | navigator offline antes de load | online + retry |
| context-error | GET fallo | retry context |

Decision de timeout: 20 segundos. No se presenta como error definitivo; copy:

> El informe está tardando más de lo esperado. Puedes esperar, reintentar o abrirlo en otra pestaña.

Limitacion cross-origin:

- load significa que el documento del iframe cargo;
- onError puede no detectar errores que Power BI renderiza dentro;
- S16 nunca anuncia Datos actualizados o Informe correcto solo por onLoad;
- no consulta postMessage no documentado;
- no lee network del iframe desde produccion;
- si Power BI muestra su propio error interno, pertenece al contenido externo.

Retry:

- incrementa una key para remount;
- no añade cache buster ni altera query firmada;
- cancela timeout anterior;
- conserva config;
- no llama generar;
- devuelve foco al estado/marco.

## Layout responsive

### Desktop y tablet, 768 px o mas

- marco centrado;
- width 100% limitado por contenido;
- aspect-ratio 980/600;
- iframe llena el marco;
- la pagina puede desplazar verticalmente en viewport corto;
- no fuerza todo dentro del primer fold;
- sidebar colapsada/expandida se resuelve por layout CSS;
- ResizeObserver solo se permite si una medicion demuestra que CSS no responde al contenedor;
- no MutationObserver de data-sidebar-state;
- no width/height inline por pixel.

### Movil, menos de 768 px

- marco ocupa 100% del contenido;
- iframe usa altura minima util de 70dvh, limitada entre 320 y 720 px;
- no mantiene ratio rigido si eso vuelve ilegible el informe;
- el proveedor controla el reflow interno;
- la pagina no tiene overflow horizontal;
- Abrir en otra pestaña queda visible como alternativa;
- no se monta una segunda representacion.

La desviacion del ratio en movil es deliberada: conserva area util sin afirmar que el reporte externo
tenga layout movil. Se prueba solo el marco propio.

Viewports:

- 390x844;
- 480x900;
- 768x1024;
- 1180x820;
- 1440x900.

## Interfaz

### Encabezado

- h1 visible Indicadores LPS;
- descripcion breve Informe externo de seguimiento LPS;
- badge Contenido externo;
- aviso global/no filtrado;
- proyecto/semana permanecen solo en el shell T01;
- no selector local;
- no boton Generar;
- no enlaces BI internos no autorizados por T03.

### Marco

- region con aria-label Informe Power BI de Indicadores LPS;
- iframe title exacto Indicadores LPS — Power BI;
- referrerPolicy no-referrer;
- allowFullScreen segun contexto;
- border 0 por CSS;
- sin sandbox inventado que pueda romper Power BI;
- overlay de loading no bloquea para siempre el foco;
- estado y acciones fuera del documento cross-origin;
- Abrir en otra pestaña usa target blank y rel noreferrer;
- URL no se imprime como texto.

### Mensajes

| Caso | Copy |
|---|---|
| loading | Cargando el informe externo… |
| slow | El informe está tardando más de lo esperado. |
| error | No fue posible cargar el informe externo. |
| offline | Sin conexión. El informe externo necesita Internet. |
| unavailable | El informe de Indicadores LPS no está disponible en este momento. |
| forbidden | respuesta 403 del shell; no se monta la ruta |

Reintentar siempre nombra que reintenta el informe o contexto. No hay spinner sin texto.

## Tema y design system

- usa public/css/tokens.css;
- marco, aviso, badge, estados, links y foco adaptan oscuro/claro;
- no intenta filter: invert sobre iframe;
- no superpone color para simular tema;
- el fondo remoto blanco puede contrastar con el marco oscuro;
- el passe-partout mantiene espacio y borde canonicos;
- no hay hex, estilos inline ni important;
- reduced motion elimina fade/loader animado no esencial;
- el manifiesto conserva la excepcion third-party-embed;
- la excepcion cambia scope de VIEW-27 al componente React;
- no se marca como conformidad del contenido interno.

## Accesibilidad

Nuestro alcance cumple:

- h1 visible;
- aviso no filtrado asociado al marco;
- iframe con title unico/descriptivo;
- loading/error como status apropiado sin bucle de anuncios;
- acciones con foco visible y target 44x44;
- reintento devuelve foco;
- link externo anuncia nueva pestaña;
- no keyboard trap antes/despues del iframe;
- fullscreen nativo no se oculta;
- 200% zoom conserva titulo, aviso, marco y acciones;
- five viewports sin page overflow;
- axe sin violaciones serias/criticas fuera del iframe;
- consola propia limpia.

No se afirma:

- contraste del reporte;
- orden de foco dentro del iframe;
- textos alternativos internos;
- accesibilidad de visualizaciones;
- tema interno.

La evidencia automatizada excluye explicitamente el documento cross-origin y registra esa
limitacion.

## Seguridad, alcance y RLS

- page/context/generate comparten RbacService y policy.
- denied no recibe embedUrl.
- static bundle no contiene embedUrl.
- config acepta solo HTTPS app.powerbi.com/view.
- no request decide URL.
- React no usa roles.
- generator deriva proyecto/prefix.
- cada consulta usa project_id y week.
- week existe en active project.
- CSRF protege POST.
- transaccion evita exito parcial.
- errores no filtran excepcion/SQL/prefix.
- no logging de URL completa ni payload sensible.
- no project/week query se agrega al embed.
- no se cambia RLS, schema, grants, usuarios, credenciales o datos.

Publish-to-web sigue siendo publico por su propio modelo. Este gate de aplicacion evita que usuarios
denegados reciban el enlace desde LPS, pero no convierte el proveedor externo en privado. Resolverlo
requiere Power BI Embedded o retiro F5, ambos fuera de S16.

## Convivencia, corte y retiro

### Piloto

- /app/indicadores monta React;
- /indicadores conserva VIEW-27;
- React usa GET context;
- generar mantiene compatibilidad form;
- feature routing permite rollback.

### Corte canonico

- /indicadores entra por SpaRouter;
- T01 entrega entrada/autorizacion;
- contexto entrega config;
- React monta un unico iframe;
- VIEW-27 deja de ser camino principal;
- generar permanece.

### Retiro

Solo con cero callers:

- retirar VIEW-27;
- retirar public/css/indicadores.css si es exclusiva;
- retirar jQuery/Bootstrap/DataTables/Google Charts/AnyChart/Select2 propios de la vista;
- retirar roles crudos y codigo ocultos/listar/idioma;
- retirar POST/GET form legacy de generar despues de migrar moduleFlows y workflow dos semanas;
- mantener POST JSON generar;
- mantener excepcion third-party mientras exista Power BI;
- mantener /indicadores hasta F5, aunque su host sea React.

### Rollback

- devolver /indicadores a VIEW-27;
- conservar context/generate compatibles;
- no generar indicadores;
- no revertir datos;
- no cambiar configuracion del informe;
- probar ruta permitida y denegada;
- restaurar route target tras ensayo.

## Estrategia de pruebas

### PHP puro

- policy exacta con overrides y P->D;
- page y context comparten permission;
- denied context no serializa URL;
- config valida esquema/host/path;
- unavailable discriminated response;
- generator rechaza authority keys;
- generator exige CSRF y week;
- week debe pertenecer al proyecto;
- fake call log de transaccion;
- exacto general/PAC/P_Completado/ocho categorias;
- exacto CIC/CIP y campos tocados;
- no delete/acumulados;
- stable errors;
- compatibility adapter.

No conectan a MySQL.

### Frontend unit/component

- Zod context available/unavailable/error;
- Zod generate request/response aunque no tenga caller UI;
- gateway context sin authority keys;
- state machine loading/loaded/slow/error/offline/retry;
- timeout fake 20 s;
- retry no altera URL ni llama generar;
- iframe attributes;
- external link;
- no generate control;
- permission/unavailable states;
- aviso global/no-filter;
- theme class/structure;
- responsive branch unica.

### Browser interceptado

Antes de navegar:

- intercept GET context;
- intercept app.powerbi.com/view con HTML controlado;
- bloquear/contar POST generar;
- permitted available;
- permitted unavailable;
- context 403 sin URL;
- delayed load/timeout/retry;
- external request failure;
- offline;
- sidebar expand/collapse;
- five viewports;
- dark/light;
- keyboard/focus/zoom/reduced motion;
- no overflow;
- axe/consola;
- cero POST generar.

No depende de Power BI real ni captura su contenido como golden.

### Pruebas legacy prohibidas como gate

- test_indicadores_server_gate.php actual requiere dev door/runtime; reemplazar con policy puro antes
  de usarlo como evidencia S16;
- moduleFlows.indicadores llama generar contra datos reales;
- e2e/tests/workflows/lps-two-weeks.spec.mjs genera y restaura datos;
- full-app-flow incluye escenarios mutables adyacentes.

Rollback/snapshot de base sigue siendo DML y no los vuelve admisibles.

## Criterios de aceptacion

1. S16-AC-01: /admin/ queda fuera.
2. S16-AC-02: /indicadores es ruta SPA al corte.
3. S16-AC-03: pagina/context/generator usan indicadores.ver.
4. S16-AC-04: no quedan listas crudas de roles en S16.
5. S16-AC-05: T01 omite entrada sin capacidad efectiva.
6. S16-AC-06: overrides y normalizacion P->D se respetan.
7. S16-AC-07: denied no recibe embedUrl.
8. S16-AC-08: el bundle no contiene embedUrl.
9. S16-AC-09: context GET tiene Zod/PHP contract.
10. S16-AC-10: context deriva config solo en servidor.
11. S16-AC-11: URL exige HTTPS app.powerbi.com/view.
12. S16-AC-12: config invalida produce unavailable sin URL.
13. S16-AC-13: titulo visible es Indicadores LPS.
14. S16-AC-14: se identifica como contenido externo.
15. S16-AC-15: se avisa que no filtra proyecto/semana.
16. S16-AC-16: no se añade project/week al embedUrl.
17. S16-AC-17: no se reimplementan KPIs.
18. S16-AC-18: wrapper modela context loading/error/retry.
19. S16-AC-19: iframe modela loading/loaded.
20. S16-AC-20: 20 s produce slow, no falso error definitivo.
21. S16-AC-21: onError produce error/retry.
22. S16-AC-22: offline tiene estado propio.
23. S16-AC-23: retry no altera URL.
24. S16-AC-24: retry nunca llama generar.
25. S16-AC-25: open external es seguro.
26. S16-AC-26: allowFullscreen se conserva.
27. S16-AC-27: load no afirma salud/frescura interna.
28. S16-AC-28: iframe tiene title y region asociada.
29. S16-AC-29: desktop/tablet usan ratio 980/600.
30. S16-AC-30: movil usa altura util sin overflow.
31. S16-AC-31: sidebar cambia ancho sin observer manual.
32. S16-AC-32: five viewports no tienen page overflow.
33. S16-AC-33: oscuro/claro adaptan todo salvo iframe.
34. S16-AC-34: excepcion third-party queda explicita.
35. S16-AC-35: teclado/foco/touch/zoom/reduced motion/axe cumplen fuera del iframe.
36. S16-AC-36: no se añade selector local de proyecto/semana.
37. S16-AC-37: no se añade boton generar.
38. S16-AC-38: SPA no llama POST generar.
39. S16-AC-39: generar JSON tiene Zod/PHP contract.
40. S16-AC-40: generar exige CSRF.
41. S16-AC-41: generar deriva proyecto/prefix de sesion.
42. S16-AC-42: authority keys se rechazan.
43. S16-AC-43: week es entero positivo y existe en proyecto.
44. S16-AC-44: generacion es transaccional/serializada sin DDL.
45. S16-AC-45: consolidado preserva PAC/P_Completado/ocho CNC.
46. S16-AC-46: CIC preserva promedios/identidad/otros campos.
47. S16-AC-47: CIP preserva promedios/identidad/otros campos.
48. S16-AC-48: no se borran filas ni se tocan acumulados.
49. S16-AC-49: respuesta generated incluye conteos tipados.
50. S16-AC-50: errores no filtran internos.
51. S16-AC-51: pruebas PHP usan fakes, no MySQL.
52. S16-AC-52: browser intercepta contexto/embed antes de navegar.
53. S16-AC-53: browser demuestra cero POST generar.
54. S16-AC-54: solo cliente.ts llama fetch.
55. S16-AC-55: aliases se retiran solo con cero callers.
56. S16-AC-56: VIEW-27/vendors se retiran solo tras paridad.
57. S16-AC-57: rollback no genera ni revierte datos.
58. S16-AC-58: RLS/schema/grants/usuarios/credenciales/datos no cambian.
59. S16-AC-59: no se regenera golden sin aprobacion.
60. S16-AC-60: retiro futuro de Power BI queda fuera y gobernado por F5.

## Entregas verticales

### Entrega 1 — Acceso y contexto

- policy unico;
- page/context guard;
- config validada;
- Zod/PHP;
- denied/unavailable.

### Entrega 2 — Wrapper responsive

- titulo/aviso;
- estado iframe;
- retry/open/fullscreen;
- desktop/tablet/movil;
- oscuro/claro/a11y.

### Entrega 3 — Generador seguro

- JSON/CSRF/scope/week;
- servicio/store;
- transaccion;
- general/CIC/CIP;
- aliases y consumidores.

### Entrega 4 — Evidencia y corte

- browser sin Power BI real;
- roles/themes/viewports;
- canonical route;
- retiro/rollback;
- excepcion F5.

## Riesgos y mitigaciones

| Riesgo | Mitigacion |
|---|---|
| Usuario cree que ve su proyecto | aviso visible global/no filtrado |
| Denegado obtiene URL | policy antes de config y contract 403 |
| URL termina en bundle | context server-only + source search |
| Config maliciosa | allowlist HTTPS host/path |
| onLoad se confunde con datos sanos | copy/estado no afirma frescura |
| Error interno cross-origin invisible | limitacion explicita + open external |
| Mobile queda miniaturizado | altura util, ratio flexible y alternativa |
| Sidebar rompe tamaño | layout CSS/container, no observer de rail |
| Tema oscuro intenta invertir reporte | excepcion, sin filtros |
| Reintento dispara DML | gateway separado y POST tripwire |
| Generador cruza proyecto | session scope + weekExists |
| Generador queda parcial | transaccion + project lock |
| Duplicado concurrente general | serializacion por fila project |
| Update CIC/CIP pisa evaluacion | columnas estrechas PAC/P_Completado |
| Test escribe/depende de Power BI | fakes/interception |
| Retiro temprano rompe workflow | zero callers y compat alias |
| Confundir S16 con T03/F5 | scope y dependencia explicitos |

## Decisiones descartadas

- Reimplementar las cuatro hojas Power BI: pertenece a BI/F5.
- Copiar KPIs a tarjetas React: dos fuentes de verdad.
- Power BI Embedded ahora: requiere identidad/token/config nuevos.
- Fingir filtro por proyecto/semana: publish-to-web no lo soporta.
- Ocultar la limitacion: induce decision errada.
- Seguir con roles G/S/SG/C: ignora overrides.
- Incluir URL en Vite env: termina en bundle.
- Inyectar iframe por innerHTML: innecesario.
- MutationObserver de sidebar: CSS container responde.
- Capturar el iframe como golden: origen externo/inestable.
- filter invert para dark: distorsiona contenido.
- Sandbox restrictivo no probado: puede romper proveedor.
- Boton generar: no existe en la UI medida.
- Generar al cargar/reintentar: DML sorpresivo.
- Usar db cliente: autoridad insegura.
- Mantener GET mutante: semantica/CSRF insegura.
- Cambiar indicadores.ver por permiso nuevo: RBAC fuera de alcance.
- Añadir unique index/upsert SQL: DDL fuera.
- Limpiar filas obsoletas: cambia contrato.
- Probar con snapshot/rollback: sigue siendo DML.

## Decisiones pendientes

Ninguna. Si la implementacion descubre un consumer productivo adicional del generador, una segunda
URL por area/proyecto, una configuracion Power BI Embedded ya existente o una señal documentada de
postMessage, debe detener el tramo afectado, aportar evidencia y enmendar esta spec. No se infiere
soporte nuevo desde el proveedor externo.

## Siguiente gate

Invocar superpowers:writing-plans para
docs/superpowers/plans/2026-08-30-s16-indicadores-react.md, autorrevisarlo, actualizar el atlas y
continuar S17. No implementar S16 en esta sesion.
