---
capa: fuente
tipo: spec
estado: autorrevisado
id: T03
fecha: 2026-08-31
superficie: marco-bi-compartido
rutas:
  - "/bi/control-tower"
  - "/bi/programa-general"
  - "/bi/intermedia"
  - "/bi/semanal"
  - "/bi/pdc"
  - "/bi/contratistas"
  - "/bi/responsables"
  - "/bi/curva-s"
  - "/api/bi/context"
  - "/api/bi/projects"
  - "/api/bi/weeks"
  - "/api/bi/filter-options"
  - "/api/bi/lineage"
  - "/api/bi/control-tower/metricas/{metricKey}"
depende_de: [T01]
consumido_por: [S16, S17, S18, S19, S20, S21, S22, S23, S24, S26]
views: [VIEW-04, VIEW-05, VIEW-06, VIEW-07, VIEW-08, VIEW-09]
areas: [frontend, react, bi, rutas, filtros, multiobra, rbac, charts, linaje, accesibilidad, responsive, temas]
fuente: "auditoría de VIEW-04/05/06/07/08/09, public/index.php, BiViewController, BiControlTowerApiController, BiAccessComponent, BiProjectScope, BiPreviewAccessPolicy, ControlTowerService, bi-spa.js, bi_filter_drawer.js, bi_chart_theme.js, bi-access.js, CSS BI, ct-app, pruebas y specs S17–S24 en shell-minimo-react, 2026-08-31"
resumen: "Contrato transversal T03 para absorber el layout PHP/JS y la isla ct-app en un único marco BI de la SPA React: manifiesto de ocho hojas, lienzos Gerencia/Obra, acceso por hoja, query URL, multiobra, períodos, filtros, estados, figuras, drawer/linaje, oscuro/claro, responsive y retiro por censo, sin tocar RLS, schema ni datos."
---

# T03 — Marco compartido de Control Tower BI en React

> **Estado:** diseño técnico autorrevisado y decision-complete. Esta spec no autoriza
> implementación, commit, push, PR, publicación, deploy, DDL/DML, cambios de RLS, schema,
> capacidades, usuarios, credenciales, datos ni trabajo en `/admin/`. Su plan de implementación
> se escribe por separado con `superpowers:writing-plans`.

## Relación con el programa

T03 no es una novena hoja. Es la plataforma común que evita que S17–S24 creen ocho routers,
ocho selectores de proyecto, ocho matrices de permisos y ocho drawers incompatibles. S16 consume
su contrato como destino futuro del informe externo y S26 sus primitivas de laboratorio; ninguno
añade una hoja BI.

| Contrato | Propietario |
|---|---|
| AppShell, sesión, proyecto global, sidebar, tema y `cliente.ts` | T01 |
| Marco, manifiesto, política por hoja, query, filtros y drawers BI | T03 |
| Resumen Ejecutivo | S17 |
| Programa General BI | S18 |
| Curva S | S19 |
| Intermedia y gestión de restricciones | S20 |
| Semanal | S21 |
| Plan de Compras BI | S22 |
| Proveedores | S23 |
| Responsables | S24 |
| Drawer contextual LPS | T02 |

T03 posee VIEW-04 a VIEW-09. S17–S24 las consumen hoy, pero no son propietarios individuales.
Una hoja puede entregar el primer incremento de T03; el archivo, API y contrato nacen compartidos.

## Resultado buscado

Después del corte completo:

1. las ocho rutas `/bi/*` viven dentro de la SPA principal;
2. existe un manifiesto único de rutas, labels, canvas, acceso, período y filtros;
3. Gerencia y Obra conservan su composición aprobada;
4. Admin puede elegir canvas; Director y Residente usan Obra; otros roles permanecen ocultos;
5. cada página y API aplican la misma política antes de leer datos;
6. URL, proyecto, período, filtros y focus son estado reproducible, no autoridad;
7. un solo marco gestiona navegación, carga, vacío, error, filtros y evidencia;
8. figuras y detalles tienen alternativas textuales accesibles;
9. oscuro y claro funcionan en desktop, tablet y móvil;
10. `ct-app`, PHP/JS BI y vendors exclusivos desaparecen sólo tras la última hoja.

## Alcance

### Incluido

- VIEW-04 a VIEW-09 y sus callers;
- las ocho rutas HTML BI existentes;
- un GET nuevo y acotado `/api/bi/context`;
- estabilización de `projects`, `weeks`, `filter-options`, `lineage` y `metricas/{metricKey}`;
- manifiesto, canvas, landing y acceso declarativo por hoja;
- query canónica y aliases de convivencia;
- proyecto simple/múltiple, semana/rango y filtros compartidos;
- estado remoto cancelable y prevención de respuestas obsoletas;
- `MarcoBi`, navegación, filtros, estados, figura, leyenda, tabla/tarjeta y drawer;
- integración con AppShell, sidebar y tema T01;
- absorción y retiro posterior de `ct-app`;
- convivencia por hoja, rollback y dos gates T03-A/T03-R;
- pruebas PHP, Zod, componentes y navegador interceptado;
- actualización final del atlas 42↔propietario.

### Excluido

- fórmulas, thresholds, narrativas, filas y mutaciones propias de S17–S24;
- un endpoint `/v2` paralelo;
- editar PG, PI, PS, PDC, proveedores, responsables o cronograma desde el marco;
- crear un sistema de notificaciones BI, outbox, scheduler o canal de entrega;
- elegir una nueva librería global de gráficas;
- copiar `ct-app` dentro de `frontend/src`;
- cambiar `internal.bi.preview`, `bi.control_tower.visible`, roles, aliases o capacidades;
- modificar RLS, DataScope, schema, vistas SQL, grants, datos o credenciales;
- `/admin/` y deploy.

## Auditoría del estado actual

### Dos runtimes y una vista muerta

El módulo opera con dos caminos:

| Camino | Hoja | Host | Runtime |
|---|---|---|---|
| legacy por defecto | siete u ocho según flag | VIEW-05 + VIEW-08 | 4.219 líneas de `bi-spa.js`, Chart.js y JS auxiliar |
| isla piloto | Intermedia con `CT_PILOTO=1` | VIEW-07 | `ct-app` + `public/ct-app/assets/ct.js` |
| archivo sin caller | ninguno | VIEW-09 | redirect PHP 302 no registrado ni incluido |

VIEW-09 sólo aparece en documentación, manifests y tests de inventario. No existe require/include,
render ni ruta runtime. T03 no crea un redirect React para preservar código muerto.

### Las seis VIEW

| ID | Archivo | Hallazgo | Destino |
|---|---|---|---|
| VIEW-04 | `views/bi/_filters.php` | proyectos, semana/rango, sub, resp, etapa y acciones | `FiltrosBi` controlado por URL |
| VIEW-05 | `views/bi/_layout.php` | segundo documento/shell, vendors, bootstrap y footer | route outlet de T01 + `MarcoBi` |
| VIEW-06 | `views/bi/_nav.php` | ocho botones/tab con `onclick=switchView` | links canónicos por canvas |
| VIEW-07 | `views/bi/control-tower-piloto.php` | host separado de Intermedia | desaparece con S20/T03-R |
| VIEW-08 | `views/bi/control-tower.php` | ocho secciones y dialogs siempre presentes | componentes de S17–S24 |
| VIEW-09 | `views/bi/index.php` | redirect huérfano | retirar sin portar |

### Rutas HTML y hojas

| Sheet key | Ruta | Endpoint principal | Canvas |
|---|---|---|---|
| `overview` | `/bi/control-tower` | `/api/bi/control-tower` | Gerencia |
| `programa-general` | `/bi/programa-general` | `/api/bi/report/programa-general` | ambos |
| `curva-s` | `/bi/curva-s` | `/api/bi/report/curva-s` | ambos |
| `intermedia` | `/bi/intermedia` | `/api/bi/report/intermedia` | Obra |
| `semanal` | `/bi/semanal` | `/api/bi/report/semanal` | Obra |
| `pdc` | `/bi/pdc` | `/api/bi/report/pdc` | Obra |
| `cic` | `/bi/contratistas` | `/api/bi/report/cic` | ambos |
| `cip` | `/bi/responsables` | `/api/bi/report/cip` | Obra |

El JS actual cambia secciones sin cambiar URL. Aunque cada hoja tiene ruta real, una persona puede
estar viendo otro contenido que el pathname. Back/Forward y compartir enlace no reproducen la hoja.

### Endpoints comunes existentes

| Método y ruta | Forma actual | Brecha |
|---|---|---|
| GET `/api/bi/projects` | `respuesta + projects` | sin envelope común/Zod |
| GET `/api/bi/weeks` | `respuesta + weeks + multi_project` | intersección común, sin contrato React |
| GET `/api/bi/filter-options` | `respuesta + options` | aliases y errores ad hoc |
| GET `/api/bi/lineage` | `respuesta + lineage` | key desconocida devuelve array vacío |
| GET `/api/bi/control-tower/metricas/{metricKey}` | envelope plano `ok` | no es contrato de marco por sí solo |

Los reportes y drilldowns pertenecen a sus hojas. Lista/Pareto/gestión de restricciones pertenecen
a S20. T03 no absorbe reglas de negocio por proximidad de ruta.

### Query y filtros legacy

El runtime acepta:

- `project_ids[]`, `project_id` y en backend CSV;
- `semana`;
- `desde/hasta`;
- `sub/resp/etapa`;
- aliases `fecha_desde/fecha_hasta/subcontratista/responsable`.

El cliente legacy llega a emitir a la vez `project_id` y `project_ids[]`, no actualiza el pathname al
aplicar y mantiene un objeto global separado de la URL. La semana se muestra para scope simple y el
rango para multi, aunque las hojas no comparten exactamente la misma política temporal.

### Acceso actual

`BiPreviewAccessPolicy` oculta el módulo:

- A entra siempre;
- D/R dependen del flag `bi.control_tower.visible`;
- otros roles reciben 404.

`BiProjectScope`:

- lista proyectos activos de Construcción/Pre-Construcción;
- exige membresía visible y `lps.indicadores.ver`;
- rechaza proyectos solicitados fuera del scope;
- usa proyecto de sesión si no hay query;
- produce `MULTI` con más de un proyecto.

El montaje actual no aplica una política por hoja: cualquiera que pasa el gate global puede pedir
cualquier leaf. La política target añade composición sin tocar RBAC.

### Contradicción documental resuelta

La spec cerrada del 24 de agosto decide que A puede elegir libremente Gerencia u Obra. Algunas specs
S17/S22/S24 posteriores convirtieron la composición del canvas en un 404 para A. Esa lectura
contradice la decisión explícita y el código actual.

T03 fija la interpretación vinculante:

- A puede abrir las ocho hojas;
- el canvas elegido controla la navegación y landing;
- D/R sólo abren las siete hojas de Obra;
- otros roles permanecen fuera;
- el acceso a proyectos sigue separado y puede producir 403.

Las specs/planes hijos se alinean en la auditoría global; no se cambia ningún permiso persistido.

### Responsive, tema y accesibilidad

El legacy usa un tab rail, drawer de filtros, tablas, cards parciales, Chart.js y estilos con
adapters. Sus avances no forman un contrato React reutilizable:

- VIEW-08 monta secciones ocultas y dialogs de otras hojas;
- Chart.js depende de Canvas y de una tabla generada por JS;
- el tema se recalcula destruyendo todos los charts;
- `ct-app` tiene tema/storage/tokens propios;
- algunos detalles sí manejan foco, pero no existe un controlador único;
- el contenido móvil varía por hoja y puede perder equivalencia.

T03 conserva la función observable y reemplaza la implementación por primitivas tipadas.

## Decisiones cerradas

### D-T03-01 — Una plataforma, ocho hojas

La SPA principal es el único runtime target. `ct-app` no se importa, enlaza ni conserva como paquete
interno. S20 reimplementa sus decisiones con los contratos compartidos.

### D-T03-02 — Manifiesto común

Backend y frontend comparten las mismas keys semánticas, pero el servidor es autoridad de acceso.
El frontend conserva un registry de presentación validado contra `/api/bi/context`; una divergencia
es error de contrato, no fallback a mostrar todo.

### D-T03-03 — Canvas no equivale a permiso de Admin

| Actor | Hojas directas | Canvas/landing |
|---|---|---|
| A | ocho | último elegido; Gerencia la primera vez |
| D/R | siete de Obra | Intermedia |
| demás | ninguna | sin entrada BI |

El switch de A navega a `/bi/control-tower` o `/bi/intermedia`. No se crea POST de preferencia ni
capacidad nueva. El servidor conserva el último módulo válido de sesión como hoy.

### D-T03-04 — Policy compartida

`BiSheetAccessPolicy` compone:

1. sesión;
2. rol canónico;
3. `internal.bi.preview`;
4. flag para D/R;
5. pertenencia de la sheet al actor/canvas;
6. después, scope de proyecto y `lps.indicadores.ver`.

Página y API principal usan la misma policy. Un leaf oculto termina antes de construir contexto.

### D-T03-05 — Contexto mínimo nuevo

`GET /api/bi/context?sheet=<key>` entrega únicamente navegación y capacidades del marco:

    {
      "ok": true,
      "data": {
        "activeSheet": "programa-general",
        "activeCanvas": "management",
        "canSwitchCanvas": true,
        "canvases": [
          {"key": "management", "label": "Gerencia", "href": "/bi/control-tower"}
        ],
        "sheets": [
          {
            "key": "overview",
            "label": "Resumen Ejecutivo",
            "href": "/bi/control-tower",
            "projectMode": "single|multi",
            "periodMode": "week|range|server-date|sheet",
            "filters": ["sub", "resp", "etapa"]
          }
        ]
      },
      "meta": {"schemaVersion": 1, "generatedAt": "ISO-8601"}
    }

No incluye datos de reporte, roles crudos ni proyectos. Los proyectos conservan su endpoint
existente. Cada nuevo contrato lleva Zod y prueba PHP.

### D-T03-06 — Query canónica extensible

El núcleo común valida scope, período, `sub/resp/etapa` y `focus`. Cada hoja extiende el schema con
sus filtros locales, orden y paginación; no relaja el núcleo. El serializer descarta keys no
soportadas al navegar entre hojas.

### D-T03-07 — Período honesto

La semana sólo es un atajo cuando una hoja la soporta. En multiobra, el servidor publica cortes
reales por proyecto. PDC puede usar fecha del servidor; Proveedores traduce semana a rango;
Responsables exige una sola obra. El marco muestra la política declarada en vez de imponer una
semana universal.

### D-T03-08 — Endpoints comunes aditivos

Se conservan rutas. React consume bloques canónicos estrictos; legacy puede recibir campos antiguos
hasta censo cero. No se crea `/v2` ni un controller paralelo.

### D-T03-09 — Estado remoto por generación

Cada transición crea una generación y AbortController. Contexto, opciones y reporte se asocian a la
query visible. No se mezclan resultados de proyectos/períodos distintos.

### D-T03-10 — Un marco responsive

`MarcoBi` se monta una vez. La navegación, filtros y drawer cambian composición por CSS/media
queries sin duplicar árboles interactivos. El contenido de hoja entra por outlet.

### D-T03-11 — Figura y evidencia accesibles

`FiguraBi` es un contrato, no una librería de gráficas. Exige lectura textual y datos equivalentes.
Las hojas eligen SVG/HTML según su spec; React BI no carga Chart.js global.

### D-T03-12 — Drawer único

Detalle y linaje comparten un drawer con historial interno, deep link y retorno de foco. La evidencia
embebida no hace red; `/api/bi/lineage` es fallback tipado.

### D-T03-13 — Señales sin side effects

S22 puede producir una señal candidata pura. T03 no crea outbox, scheduler ni entrega. Hasta una
autorización separada, destinatario, canal, dedupe histórico y calibración dicen `unavailable`.
Refrescar una hoja jamás emite una notificación.

### D-T03-14 — Dos gates

- **T03-A:** policy, manifiesto, contexto, query, gateways y primitivas compartidas disponibles para
  entregas verticales. Legacy permanece.
- **T03-R:** sólo tras S17–S24 publicados y censo cero; elimina las seis VIEW, `ct-app`, bundles,
  JS/CSS/vendors exclusivos y branches `CT_PILOTO`.

## Contratos de arquitectura

### Backend

Piezas objetivo:

- `src/Security/BiSheetAccessPolicy.php`;
- `src/Services/Bi/Manifest/BiSheetManifest.php`;
- `src/Services/Bi/Http/BiQuery.php`;
- `src/Services/Bi/Http/BiQueryParser.php`;
- `src/Services/Bi/Http/BiContextPresenter.php`;
- adapters/presenters para projects, weeks, options y lineage;
- `BiProjectScope` como boundary existente, sin reescribir RLS;
- controllers delegando policy → parser → service → presenter.

El manifiesto no consulta tablas. La policy usa roles ya normalizados y capacidades vigentes.

### Frontend

Piezas objetivo:

- `frontend/src/lib/api/esquemas/biComun.ts`;
- `frontend/src/lib/api/biContexto.ts`;
- `frontend/src/lib/api/biFiltros.ts`;
- `frontend/src/modulos/bi/manifiestoBi.ts`;
- `frontend/src/modulos/bi/consultaBi.ts`;
- `frontend/src/modulos/bi/MarcoBi.tsx`;
- `frontend/src/modulos/bi/NavegacionBi.tsx`;
- `frontend/src/modulos/bi/FiltrosBi.tsx`;
- `frontend/src/modulos/bi/EstadoBi.tsx`;
- `frontend/src/modulos/bi/FiguraBi.tsx`;
- `frontend/src/modulos/bi/LeyendaBi.tsx`;
- `frontend/src/modulos/bi/LinajeDrawer.tsx`;
- `frontend/src/modulos/bi/useConsultaBi.ts`;
- `frontend/src/modulos/bi/bi.css`.

Ninguna pieza conoce `window.__CT_BOOTSTRAP__`, `BI` global, Chart.js o una tabla legacy.

## Flujo target

    URL /bi/<leaf>
      -> servidor: sesión + BiSheetAccessPolicy
      -> SPA principal
      -> GET /api/bi/context?sheet=<key>
      -> consultaBi parsea URL
      -> projects/weeks/options según manifiesto
      -> endpoint principal de S17–S24
      -> Zod
      -> MarcoBi + outlet
      -> drawer/evidencia/focus

Cambiar query aborta la generación anterior. Cambiar hoja navega. Ningún control autoriza por sí
mismo y ningún GET tiene side effects de negocio.

## Diseño responsive

### 1440×900 y 1180×820

- AppShell T01 permanece como único chrome;
- header BI muestra hoja, canvas, proyecto/período, recarga y filtros;
- nav de hojas usa rail accesible con corte/scroll visible;
- filtros pueden estar inline o en panel sin tapar datos;
- drawer ocupa una columna/side-sheet y no desplaza fuera del viewport;
- no hay overflow horizontal de página.

### 768×1024

- nav se compacta a selector/lista de links;
- filtros usan un side-sheet único;
- tablas de hoja conservan columnas prioritarias y detalle accesible;
- acciones no dependen de hover.

### 480×900, 390×844 y 320 px

- contexto se resume sin ocultar scope/corte;
- filtros y drawer usan full/bottom sheet con `dvh` y safe areas;
- tarjetas reemplazan tablas donde cada spec lo exige;
- gráfico, leyenda y tabla de datos mantienen lectura equivalente;
- no hay dos versiones interactivas ocultas.

## Temas y design system

- oscuro es default/fallback;
- claro es equivalente, no una vista secundaria incompleta;
- tokens salen de `public/css/tokens.css`;
- no se crean colores literales, estilos inline, CSS-in-JS o `!important`;
- estados no dependen sólo de color;
- no se reintroduce linen;
- `ct-app` no conserva su key `ct-piloto-theme` después del corte;
- `FiguraBi` usa tokens y datos semánticos, no una paleta hardcoded.

## Accesibilidad

- landmarks y heading hierarchy únicos;
- links de hoja con `aria-current=page`;
- labels e instrucciones persistentes;
- controles de 44×44;
- drawers con foco, trap, Escape, retorno e inert;
- errores asociados a controles;
- live regions para carga/resultado sin ruido;
- alternativa textual y tabular para figuras;
- operación completa con teclado/touch;
- zoom 200%, 320 px, forced colors y reduced motion;
- Axe serious/critical en cero.

## Seguridad, aislamiento y límite RLS

T03 no toca RLS. Conserva:

- policy de preview antes de revelar módulo;
- sheet policy antes de resolver contexto;
- `BiProjectScope` y `MultiProjectScope` antes del reader;
- `project_id` en cada consulta/join;
- prepared statements y `Database`;
- query cliente como intención, no autoridad;
- errores sin internals;
- `no-store` en contexto operacional;
- logs sin PII/payload/SQL.

## Convivencia, corte y rollback

### T03-A

Puede publicarse con todas las piezas legacy. Cada hoja corta de forma independiente mediante su
plan S17–S24. No existe placeholder productivo: una ruta sólo cambia a React cuando su hoja real y
el incremento T03 requerido están verdes.

### T03-R

Requisitos acumulativos:

1. S17–S24 publicados;
2. ocho rutas sirven React;
3. APIs canónicas y adapters tienen callers conocidos;
4. censo cero de VIEW-04…09;
5. censo cero de `bi-spa.js` y módulos auxiliares;
6. S20 confirma retiro de `ct-app` y `CT_PILOTO`;
7. CSS/vendors tienen censo global;
8. regresión de ocho hojas, shell, temas y a11y verde.

Rollback de una hoja vuelve sólo su route host al adapter legacy mientras exista. Después de T03-R,
rollback requiere revertir código/assets; nunca modifica datos.

## Estrategia de pruebas

### PHP puro y contratos

- manifiesto/canvas/orden;
- policy A/D/R/otros y flag;
- página/API misma policy;
- query/aliases/authority keys;
- scope simple/multi/denegado;
- context/projects/weeks/options/lineage/metric contracts;
- envelopes y encabezados;
- cero DML mediante repositories/fakes.

### Frontend unit/component

- Zod estricto y gateway por `cliente.ts`;
- registry ↔ context;
- parse/serialize/back-forward;
- abort/generaciones/cache keys;
- nav/canvas/filter chips;
- loading/empty/partial/stale/error;
- drawer/focus/deep links;
- figuras y tabla equivalente;
- oscuro/claro y responsive;
- ausencia de `fetch`/Chart globals.

### Navegador interceptado

- A en ambos canvas y último módulo;
- D/R Obra e Intermedia;
- rol oculto 404;
- proyecto ajeno 403;
- ocho leaf routes;
- filtros/URL/Back/Forward/recarga;
- error/empty/partial/offline;
- drawers;
- cinco viewports, dos temas, Axe y consola.

Las pruebas runtime autenticadas futuras usan `/dev/entrar` y las cuentas sembradas autorizadas. No
se escriben datos ni se usan credenciales en el formulario.

## Criterios de aceptación

- T03-AC-001: T03 pertenece al programa documental de 27 superficies y no añade una superficie funcional número 28.
- T03-AC-002: T03 posee exactamente VIEW-04, VIEW-05, VIEW-06, VIEW-07, VIEW-08 y VIEW-09.
- T03-AC-003: S17–S24 poseen el contenido y las reglas de negocio de cada hoja; T03 no recalcula sus métricas.
- T03-AC-004: T01 conserva AppShell, sesión, proyecto activo, tema global, route outlet y cliente HTTP.
- T03-AC-005: T02 no se mezcla con el drawer BI ni con el linaje BI.
- T03-AC-006: La implementación futura queda fuera de esta sesión documental.
- T03-AC-007: `/admin/` queda excluido de diseño, cambios y criterios de cierre.
- T03-AC-008: No se modifican RLS, DataScope, schema, tablas, vistas SQL, grants, usuarios, membresías, credenciales ni datos.
- T03-AC-009: No se ejecutan DDL/DML ni suites que dependan de sembrar o borrar datos.
- T03-AC-010: No se introduce funcionalidad en `src/Legacy/`.
- T03-AC-011: No se crea una segunda SPA ni un tercer cliente HTTP.
- T03-AC-012: Cada hoja puede consumir T03 por incremento vertical sin esperar el retiro conjunto.
- T03-AC-013: VIEW-04 queda caracterizada como filtros compartidos por URL y opciones contextuales.
- T03-AC-014: VIEW-05 queda caracterizada como documento PHP completo que duplica el shell.
- T03-AC-015: VIEW-06 queda caracterizada como ocho tabs imperativas que no actualizan la URL.
- T03-AC-016: VIEW-07 queda caracterizada como host exclusivo de `ct-app` bajo `CT_PILOTO`.
- T03-AC-017: VIEW-08 queda caracterizada como ocho secciones y drilldowns montados en un único documento.
- T03-AC-018: VIEW-09 queda confirmada sin caller runtime y no se porta a React.
- T03-AC-019: `bi-spa.js` queda caracterizado como runtime imperativo compartido con `fetch` directo y estado global.
- T03-AC-020: `bi_filter_drawer.js`, `bi_chart_theme.js` y `bi-access.js` tienen propietario y gate de retiro explícitos.
- T03-AC-021: `ct-app` queda caracterizada como isla Intermedia con cliente, tema, tokens y bundle propios.
- T03-AC-022: Los CSS BI legacy, adapters y vendors se censan antes de cualquier retiro.
- T03-AC-023: El bundle generado `public/ct-app/` nunca se edita manualmente.
- T03-AC-024: Chart.js y Lucide globales permanecen sólo mientras exista un caller legacy demostrado.
- T03-AC-025: Cada alias, flag y asset retirado exige un censo actual de cero callers productivos.
- T03-AC-026: El rollback de T03 es sólo de código y assets; nunca restaura ni revierte datos.
- T03-AC-027: Existe un manifiesto BI único con las ocho claves `overview`, `programa-general`, `intermedia`, `semanal`, `pdc`, `cic`, `cip` y `curva-s`.
- T03-AC-028: Cada clave del manifiesto fija label, href canónico, report key, endpoint principal, canvas, capacidades de filtro y política de período.
- T03-AC-029: Las rutas canónicas siguen siendo las ocho `/bi/*` registradas actualmente.
- T03-AC-030: Gerencia conserva el orden Resumen Ejecutivo, Programa General, Curva S y Proveedores.
- T03-AC-031: Obra conserva el orden Intermedia, Programa General, Semanal, Curva S, Plan de Compras, Proveedores y Responsables.
- T03-AC-032: A puede abrir las ocho hojas y elegir Gerencia u Obra sin depender del flag global.
- T03-AC-033: La preferencia de canvas de A controla navegación y landing, no deniega una ruta que A puede auditar.
- T03-AC-034: A inicia en Gerencia la primera vez y después en su último módulo BI válido de la sesión.
- T03-AC-035: Visitar una hoja exclusiva de un canvas actualiza el último módulo de A mediante el flujo servidor existente.
- T03-AC-036: En una hoja compartida, A conserva el canvas previo; sin preferencia previa usa Gerencia.
- T03-AC-037: D y R sólo reciben el canvas Obra.
- T03-AC-038: D y R aterrizan en Intermedia desde la entrada BI.
- T03-AC-039: D y R dependen de `bi.control_tower.visible` además de `internal.bi.preview`.
- T03-AC-040: D y R reciben 404 en Resumen Ejecutivo y en cualquier hoja fuera de Obra.
- T03-AC-041: Roles fuera de A/D/R reciben 404 antes de revelar módulo, proyectos, filtros o métricas.
- T03-AC-042: La página y su API principal pasan la misma `BiSheetAccessPolicy`.
- T03-AC-043: `BiSheetAccessPolicy` recibe rol canónico confiable y sheet key; React no decide acceso por letra de rol.
- T03-AC-044: La normalización de roles usa `RbacService::normalizeRole()`.
- T03-AC-045: `MULTI` es metadata de reporte y nunca una capacidad o rol autorizador.
- T03-AC-046: Cada proyecto seleccionado exige membresía visible y `lps.indicadores.ver` en el rol real de esa obra.
- T03-AC-047: Proyecto no autorizado produce 403 después del gate de hoja y antes de leer datos.
- T03-AC-048: La lista de navegación contiene sólo hojas del canvas efectivo y hrefs autorizados.
- T03-AC-049: La sidebar global conserva una sola entrada BI resuelta por servidor.
- T03-AC-050: El estado activo usa coincidencia de ruta exacta, no comparación parcial de strings.
- T03-AC-051: El switch de canvas para A navega a la entrada canónica del canvas y no necesita una nueva capacidad.
- T03-AC-052: La política corrige cualquier child spec que haya convertido por error el canvas de A en una prohibición de hoja.
- T03-AC-053: Se añade un único GET `/api/bi/context` para el marco React; no se crea `/v2`.
- T03-AC-054: El contexto recibe sólo `sheet` canónico y no acepta rol, permiso, canvas autorizador, usuario, db o prefijo.
- T03-AC-055: El contexto devuelve `ok`, sheet activo, canvas efectivo, canvases disponibles, navegación autorizada y capacidades de controles.
- T03-AC-056: El contexto no duplica métricas, filas, filtros de negocio ni payloads de las hojas.
- T03-AC-057: El contexto devuelve hrefs ya autorizados y no matrices RBAC crudas.
- T03-AC-058: El contexto usa `Cache-Control: no-store` y no expone nombres de tablas, prefijos o credenciales.
- T03-AC-059: El contexto tiene esquema Zod estricto derivado como única fuente de tipos.
- T03-AC-060: El contexto tiene prueba de contrato PHP para éxito, 404, 403 y campos sensibles ausentes.
- T03-AC-061: `/api/bi/projects` conserva la ruta y se estabiliza con envelope canónico aditivo.
- T03-AC-062: `/api/bi/weeks` conserva la ruta y sólo devuelve semanas comunes del scope autorizado.
- T03-AC-063: `/api/bi/filter-options` conserva la ruta y devuelve opciones según scope, período y filtros validados.
- T03-AC-064: `/api/bi/lineage` conserva la ruta como fallback tipado global o por `metric_key`.
- T03-AC-065: `/api/bi/control-tower/metricas/{metricKey}` permanece mientras tenga callers y no se vuelve un bypass de policy/scope.
- T03-AC-066: Los endpoints de lista, Pareto y gestión de restricciones pertenecen a S20, no al marco genérico.
- T03-AC-067: Los endpoints `/api/bi/report/*` y detalles pertenecen a S17–S24, no a T03.
- T03-AC-068: Todo endpoint nuevo o estabilizado consumido por React tiene esquema Zod y prueba de contrato PHP.
- T03-AC-069: Los envelopes de error comparten `ok:false`, `code`, mensaje seguro, `retryable` y errores de campo opcionales.
- T03-AC-070: La compatibilidad `respuesta=BIEN` puede convivir sólo en adapters PHP hasta cero callers legacy.
- T03-AC-071: `BiQueryParser` es la autoridad única del query compartido en backend.
- T03-AC-072: `consultaBi` es el codec único de parseo/serialización del query compartido en frontend.
- T03-AC-073: La forma canónica usa `project_ids` repetido, `semana`, `desde`, `hasta`, `sub`, `resp`, `etapa` y `focus`.
- T03-AC-074: `project_id`, CSV y aliases `fecha_desde`, `fecha_hasta`, `subcontratista` y `responsable` viven sólo en el adapter de convivencia.
- T03-AC-075: La serialización React emite una sola forma canónica y nunca duplica `project_id` con `project_ids[]`.
- T03-AC-076: IDs de proyecto son enteros positivos, deduplicados y ordenados de forma estable.
- T03-AC-077: Filtros escalares se recortan y respetan límites explícitos del parser.
- T03-AC-078: Fechas usan ISO `YYYY-MM-DD` y `desde` no puede ser posterior a `hasta`.
- T03-AC-079: React nunca envía semana y rango juntos.
- T03-AC-080: Durante convivencia, si llegan semana y rango legacy, el adapter declara que el rango gobierna.
- T03-AC-081: `role`, `permiso`, `user`, `username`, `email`, `db`, `dbName`, `prefix`, `capability` y `canManageConstraints` se rechazan como authority-like.
- T03-AC-082: Arrays se aceptan sólo para `project_ids`; cualquier otro array inesperado se rechaza.
- T03-AC-083: La URL canónica es fuente de estado reproducible, nunca fuente de autorización.
- T03-AC-084: Cambiar hoja conserva sólo query keys soportadas por el destino.
- T03-AC-085: Cambiar filtro actualiza URL navegable y reemplaza o empuja historial según la interacción documentada.
- T03-AC-086: Back y Forward restauran hoja, proyectos, período, filtros y focus sin estado DOM paralelo.
- T03-AC-087: `focus` se valida mediante la extensión de schema de la hoja y no es un string libre.
- T03-AC-088: Cada hoja declara `single`, `multi` o ambos como política de selección.
- T03-AC-089: Overview puede usar todas las obras autorizadas por defecto; las otras hojas no heredan esa excepción.
- T03-AC-090: Curva S, Intermedia y Semanal exigen selección multiobra explícita.
- T03-AC-091: Responsables exige exactamente un proyecto y rechaza `project_ids` múltiples.
- T03-AC-092: PDC y Proveedores conservan proyecto en cada fila cuando el scope es múltiple.
- T03-AC-093: Semana sólo se usa cuando la hoja y el scope la soportan.
- T03-AC-094: Rango publica cortes efectivos por proyecto cuando semanas homónimas no comparten fechas.
- T03-AC-095: Una hoja sin soporte de semana oculta el control; no envía un valor ignorado.
- T03-AC-096: Una hoja sin soporte de rango oculta el control; no lo interpreta localmente.
- T03-AC-097: Las opciones de filtro se recargan con la misma query base y se cancelan al cambiar de scope.
- T03-AC-098: Quitar o limpiar filtros actualiza URL, conteo y datos en una sola transición.
- T03-AC-099: Los chips muestran label y valor, y su botón tiene nombre accesible específico.
- T03-AC-100: Los filtros locales de una hoja viven en su extensión y no contaminan el query de otras hojas.
- T03-AC-101: El contexto de proyecto, período y filtros efectivos siempre se muestra antes del contenido decisional.
- T03-AC-102: `/api/bi/projects` valida proyectos y nombres autorizados sin exponer role crudo.
- T03-AC-103: `/api/bi/weeks` prueba scope simple, multi, intersección vacía y proyecto ajeno.
- T03-AC-104: `/api/bi/filter-options` prueba filtros dependientes y ausencia de opciones de otro proyecto.
- T03-AC-105: `/api/bi/lineage` prueba listado, key válida, key desconocida y gate de acceso.
- T03-AC-106: El linaje canónico contiene metricKey, label, definición, fórmula, fuente, grano, corte, filtros, versión y limitaciones.
- T03-AC-107: Nombres SQL pueden quedar en metadata técnica del drawer, nunca en titulares o acciones de negocio.
- T03-AC-108: `metricas/{metricKey}` distingue métrica ejecutable, descriptiva, desconocida e insuficiente.
- T03-AC-109: Los contratos comunes modelan `complete`, `partial`, `insufficient` y `not_applicable` sin convertir null en cero.
- T03-AC-110: Cada endpoint común envía encabezado de contenido JSON y respuestas UTF-8 válidas.
- T03-AC-111: Sesión ausente/expirada produce respuesta JSON tipada para llamadas React y no HTML de login.
- T03-AC-112: 404 oculto no diferencia rol inexistente de hoja no permitida.
- T03-AC-113: 403 de proyecto no devuelve nombres de proyectos no autorizados.
- T03-AC-114: 409 queda reservado para período o versión no comparable declarados por la hoja.
- T03-AC-115: 429 y 503 se preservan cuando middleware o dependencia los producen.
- T03-AC-116: Los gateways comunes usan exclusivamente `frontend/src/lib/api/cliente.ts`.
- T03-AC-117: Ningún componente o hook T03 llama `fetch`.
- T03-AC-118: Los tipos TypeScript salen de `z.infer` y no de interfaces paralelas.
- T03-AC-119: El cliente común soporta AbortSignal y conserva código/status/error de campo sin perder validación Zod.
- T03-AC-120: Cada cambio de sheet/query incrementa una generación y aborta requests reemplazados.
- T03-AC-121: Una respuesta obsoleta nunca pisa el estado de la URL visible.
- T03-AC-122: Contexto, opciones y reportes tienen estados de carga separados sin montar datos incompatibles.
- T03-AC-123: Un error de opciones no oculta un reporte ya válido; marca sólo el control afectado.
- T03-AC-124: Un error de reporte conserva el marco, query y acción Reintentar.
- T03-AC-125: Un error de contexto no monta navegación ni contenido de hoja.
- T03-AC-126: Una sesión vencida delega al único flujo de sesión T01.
- T03-AC-127: Un 404 ofrece salida segura sin revelar la hoja.
- T03-AC-128: Un 403 ofrece volver a proyectos o al destino BI autorizado, sin mostrar datos anteriores.
- T03-AC-129: Un 422 asocia errores a controles y permite restablecer query.
- T03-AC-130: Modo offline puede conservar snapshot previo sólo si se rotula desactualizado y coincide exactamente con la cache key.
- T03-AC-131: La cache key incluye usuario/sesión, sheet, proyectos ordenados, período, filtros, paginación y focus.
- T03-AC-132: Al cerrar sesión o cambiar generación de proyecto se invalida toda cache BI.
- T03-AC-133: Recargar conserva URL y fuerza revalidación sin mutar datos.
- T03-AC-134: No hay polling común de reportes BI.
- T03-AC-135: `MarcoBi` se monta una sola vez dentro del AppShell de T01.
- T03-AC-136: El marco contiene título, canvas switch autorizado, nav de hojas, resumen de contexto, filtros, estado, outlet y drawer.
- T03-AC-137: La navegación usa links/rutas reales y `aria-current=page`, no botones con `onclick`.
- T03-AC-138: En desktop la nav puede ser horizontal desplazable con indicio de overflow; no recorta la última hoja.
- T03-AC-139: En tablet y móvil la nav se vuelve lista/menu accesible sin duplicar el árbol de enlaces.
- T03-AC-140: Filtros viven inline cuando caben y en un único drawer cuando no caben.
- T03-AC-141: El drawer de filtros tiene nombre, foco inicial, trap, Escape, retorno e inert del fondo.
- T03-AC-142: Aplicar filtros es una acción explícita en móvil; limpiar conserva proyectos cuando la política de la hoja lo exige.
- T03-AC-143: El conteo de filtros activos excluye defaults no elegidos por la persona.
- T03-AC-144: `EstadoBi` distingue loading, empty, insufficient, partial, stale, forbidden, hidden, invalid-query y error.
- T03-AC-145: Un estado vacío explica el scope/período y ofrece limpiar o cambiar contexto cuando aplica.
- T03-AC-146: Un estado insuficiente no se pinta como éxito ni como cero.
- T03-AC-147: `LinajeDrawer` es único para las ocho hojas y soporta evidencia embebida o fallback por endpoint.
- T03-AC-148: Abrir linaje embebido no dispara una petición por fila.
- T03-AC-149: El drawer conserva foco, Escape, retorno, botón volver y deep link `focus`.
- T03-AC-150: No se apilan dos drawers; detalle y linaje usan un único stack/navegación interna.
- T03-AC-151: `FiguraBi` exige título, resumen textual, unidad, leyenda y tabla/lista de datos equivalente.
- T03-AC-152: El contenido decisional nunca depende sólo de Canvas, tooltip, hover, color, patrón o posición.
- T03-AC-153: T03 no obliga una librería de gráficas; las hojas pueden usar SVG/HTML dentro del contrato común.
- T03-AC-154: El runtime React no carga Chart.js ni Lucide globales para nuevas hojas.
- T03-AC-155: Geometría, métricas, narrativas, thresholds y acciones permanecen en S17–S24 y servidor.
- T03-AC-156: Las acciones operativas son hrefs o capabilities server-authored; T03 no infiere permisos.
- T03-AC-157: La señal candidata S22 puede viajar como dato puro, pero cargar/recargar un GET nunca envía, persiste o deduplica una notificación.
- T03-AC-158: Sin contrato autorizado de distribución, canal, destinatario y calibración se publican como unavailable, no se simulan.
- T03-AC-159: Desktop canónico 1180×820 oscuro no tiene overflow horizontal de página.
- T03-AC-160: También se valida 1440×900, 768×1024, 480×900 y 390×844.
- T03-AC-161: A 320 px y zoom 200% el contenido principal refluye sin pérdida de acciones.
- T03-AC-162: Las tablas de hojas que lo requieran se transforman en tarjetas semánticamente equivalentes en móvil.
- T03-AC-163: No se renderizan simultáneamente tabla y tarjetas como dos árboles interactivos duplicados.
- T03-AC-164: Targets interactivos miden al menos 44×44 CSS px.
- T03-AC-165: Orden de foco, landmarks, headings y nombres accesibles permanecen coherentes en los cinco viewports.
- T03-AC-166: Cambios de filtro, orden y carga se anuncian con live regions sin repetir todo el contenido.
- T03-AC-167: `prefers-reduced-motion` elimina transiciones no esenciales y mantiene feedback.
- T03-AC-168: Oscuro es default/fallback y claro ofrece la misma función, jerarquía, foco y estados.
- T03-AC-169: Todos los colores, espacios, radios, sombras, capas y motion salen de `public/css/tokens.css`.
- T03-AC-170: Los archivos T03 nuevos tienen cero colores literales, estilos inline, CSS-in-JS y `!important`.
- T03-AC-171: No se crea una familia `--bi-*` paralela cuando existe un token semántico canónico.
- T03-AC-172: Focus visible y contraste se prueban en oscuro y claro.
- T03-AC-173: Las figuras usan texto/unidad/forma además de color y soportan forced-colors.
- T03-AC-174: El marco respeta safe areas, `dvh` y scroll interno de drawers en móvil.
- T03-AC-175: Axe no reporta violaciones serious/critical en los escenarios canónicos.
- T03-AC-176: Cada lectura aplica `MultiProjectScope` o scope equivalente antes de cualquier reader.
- T03-AC-177: Cada consulta y join operacional conserva `project_id`; `unique_id` nunca cruza obra solo.
- T03-AC-178: Prepared statements y la capa `Database` siguen siendo obligatorios.
- T03-AC-179: No se introduce SQL dinámico por prefijo, tabla, filtro u orden del cliente.
- T03-AC-180: Logs usan requestId, sheet y project IDs autorizados; no registran nombres personales, emails, payloads sensibles ni SQL.
- T03-AC-181: El contexto y los endpoints comunes usan `Cache-Control: no-store` salvo una política explícita más estricta de la hoja.
- T03-AC-182: Las pruebas PHP comunes usan fakes/fixtures estáticos y no modifican MySQL.
- T03-AC-183: Las pruebas frontend cubren manifest, codec, gateways, estados, navegación, filtros, drawer, temas y a11y.
- T03-AC-184: Las pruebas de navegador interceptan red y cubren A, D, R, rol oculto, 403, 404, oscuro/claro y cinco viewports.
- T03-AC-185: La verificación runtime futura usa la puerta dev autorizada y nunca credenciales en el login.
- T03-AC-186: La consola no contiene errores y la red confirma que no hay requests duplicados ni endpoints legacy inesperados.
- T03-AC-187: La evidencia de cada hoja se registra en su `Cierre` y no se deduce de checkboxes.
- T03-AC-188: T03-A puede cerrar con las seis VIEW y assets legacy aún presentes por convivencia.
- T03-AC-189: T03-R sólo empieza cuando S17–S24 están publicados y sus `Cierre` prueban corte React.
- T03-AC-190: T03-R exige censo cero para las seis VIEW, `bi-spa.js`, módulos BI auxiliares, CSS exclusivo, `ct-app`, bundle y `CT_PILOTO`.
- T03-AC-191: T03-R elimina VIEW-09 sin reemplazo porque se confirmó muerto.
- T03-AC-192: T03-R retira `ct-app` y `public/ct-app` sólo después del cierre S20.
- T03-AC-193: T03-R retira Chart.js/Lucide/CDN y adapters sólo si el censo global demuestra cero callers.
- T03-AC-194: T03-R actualiza manifests, tests, documentación de rutas y atlas 42↔propietario.
- T03-AC-195: T03-R ejecuta regresión de las ocho hojas, sidebar, sesión, temas, a11y y respuestas API.
- T03-AC-196: Tras el retiro, ninguna ruta `/bi/*` sirve PHP/JS legacy ni importa código de `ct-app`.

## Entregas verticales

### Entrega 1 — T03-A autoridad y contratos

- censo y caracterización;
- manifiesto/policy;
- contexto y endpoints comunes;
- query backend/frontend;
- Zod/gateways;
- marco/primitivas sin cortar hojas.

### Entrega 2 — Primer consumidor S17

- montar T03 con contenido real S17;
- probar canvas Gerencia;
- mantener siete hojas legacy;
- publicar el seam compartido.

### Entregas 3–9 — S18 a S24

Cada hoja usa el mismo marco y extiende query/contratos sin duplicarlos. Una hoja puede cortar y
revertir independientemente.

### Entrega 10 — T03-R

- censo cero;
- retirar PHP/JS/CSS/isla/vendors exclusivos;
- actualizar manifests y docs;
- regresión completa;
- cierre/publicación por PR.

## Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| canvas usado como permiso de A | policy explícita: A ocho hojas, canvas preferencia |
| página abre y API da 404 | policy compartida y test page/API |
| query duplica proyecto | serializer único |
| semanas homónimas mezclan fechas | cutoffs por proyecto |
| respuesta vieja pisa filtro nuevo | generación + abort |
| filtro local contamina otra hoja | schema extension por leaf |
| gráfico inaccesible | figura + resumen + datos equivalentes |
| retiro rompe una hoja restante | T03-R y censo cero |
| copiar `ct-app` perpetúa isla | reimplementación S20 |
| señal GET produce side effect | candidato puro/unavailable |
| CSS/vendores se retiran antes | censo global |
| rollback toca datos | rollback sólo código/assets |

## Decisiones descartadas

- conservar `switchView()` dentro de React;
- una SPA por hoja;
- importar `ct-app`;
- crear `/api/bi/v2/*`;
- inferir policy en React;
- negar hojas de Obra a A;
- usar una semana universal multiobra;
- guardar filtros en DOM además de URL;
- Chart.js global como contrato;
- un drawer por hoja;
- emitir señales/notificaciones desde GET;
- retirar legacy al cortar S17.

## Decisiones pendientes

No quedan decisiones de producto, estrategia, PM o lógica de negocio abiertas en T03. Las fórmulas,
thresholds, poblaciones y mutaciones permanecen cerradas en S17–S24.

## Siguiente gate

Escribir y autorrevisar `docs/superpowers/plans/2026-08-30-t03-marco-bi-react.md` con trazabilidad
exacta de todos los criterios. No implementar en esta sesión.
