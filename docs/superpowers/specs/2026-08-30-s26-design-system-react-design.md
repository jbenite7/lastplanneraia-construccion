---
capa: fuente
tipo: spec
estado: vigente
id: S26
fecha: 2026-08-31
superficie: design-system
rutas:
  - "/internal/design-system"
  - "/api/internal/design-system/catalog"
depende_de: [T01, T02, T03, "S01-S25"]
views: [VIEW-13, VIEW-14, VIEW-15, VIEW-16, VIEW-17, VIEW-18, VIEW-19, VIEW-20, VIEW-21, VIEW-22, VIEW-23, VIEW-24, VIEW-25]
areas: [arquitectura, design-system]
fuente: "auditoria de public/index.php, SpaRouter, DesignSystemLabController, DesignSystemLabAccessPolicy, RbacCatalog, 13 vistas/partials design-system, design_system_lab.js, lab.css, homologation.json, family-approvals.json, component-catalog.json, ui-groups-inventory.json, state-semantics.json, operational-fixtures.json, vendors.json, version.json, manifiestos, goldens, pruebas PHP/browser/design-system, T01-T03, S01-S25 y frontend actual en shell-minimo-react, 2026-08-31"
resumen: "Migracion S26 del laboratorio interno del Design System a React: ruta global protegida, catalogo tipado, diez familias, candidatos y aprobaciones no colapsadas, grupos UI, estados, fixtures locales y ledger de adapters vigentes, responsive y oscuro/claro, excluyendo por completo /admin/ y sin tocar RLS ni datos."
---

# S26 — Design System en React

> **Estado:** diseño técnico autorrevisado y decision-complete. Esta spec no autoriza
> implementación, commit, push, PR, publicación, deploy, cambios de RLS, capacidades, schema,
> datos ni trabajo en `/admin/`. Su plan se escribe a continuación con
> `superpowers:writing-plans` como parte del programa aprobado de 27 specs y 27 planes.

## Relación con el programa

S26 migra el laboratorio que documenta y prueba las primitivas compartidas; no vuelve a diseñar los
módulos que ya poseen T01–T03 y S01–S25.

- T01 posee sesión, tema, cuenta, cliente HTTP y el árbol de rutas React.
- T02 y T03 poseen drawer contextual y primitivas BI compartidas.
- S01–S25 son consumidores y determinan qué adapters legacy siguen teniendo callers productivos.
- S26 posee VIEW-13 a VIEW-25, el catálogo navegable y sus fixtures deterministas.
- S27 conserva `/dashboard` como redirect; no es parte del laboratorio.
- `/admin/` queda fuera: ni sus páginas, ni su shell, ni AdminLTE, ni su fixture visual entran aquí.

S26 es la última superficie HTML del programa y su cierre permite retirar el runtime PHP/JS
exclusivo del laboratorio, pero no retirar assets compartidos por consumidores aún vivos.

## Resultado buscado

`/internal/design-system` debe:

1. conservar 404 fuera de development/testing y 403 sin capacidad;
2. abrir para una sesión autorizada aunque no exista proyecto seleccionado;
3. usar React y el tema/cuenta compartidos sin introducir una entrada en la sidebar de producto;
4. navegar diez familias con URL recuperable, historial y foco correcto;
5. mostrar el candidato activo exacto, todos los candidatos y todas las aprobaciones;
6. evitar que “última aprobación” borre aprobaciones compatibles de una misma familia;
7. enumerar componentes, grupos UI, semántica de estados y fixtures desde contratos JSON validados;
8. ejecutar fixtures locales y deterministas, sin peticiones operativas ni persistencia;
9. demostrar sólo adapters con callers productivos no-admin y documentar los retirados sin cargarlos;
10. funcionar en oscuro y claro, desktop, tablet y móvil;
11. conservar evidencia y presupuestos sin regenerar goldens automáticamente;
12. poder volver a PHP por corte de ruta, sin rollback de datos porque no escribe datos.

Paridad significa conservar el conocimiento, la navegación, los estados demostrables y los gates.
No significa conservar PHP, manipulación imperativa del DOM, cargar diez familias ocultas, valores
que parecen credenciales, proyectos reales ni librerías vendor para imitar su apariencia.

## Alcance

### Incluido

- `GET /internal/design-system` como página interna global y protegida.
- Nuevo `GET /api/internal/design-system/catalog`, sólo lectura y tipado.
- Las diez familias de `homologation.json`.
- VIEW-13 a VIEW-22 como renderers React de familia.
- VIEW-23 como shell/página del laboratorio.
- VIEW-24 como runner compartido de fixtures.
- VIEW-25 como índice de grupos UI.
- `version.json`, homologación, aprobaciones, catálogo de componentes, grupos UI, semántica de
  estados, fixtures operacionales, vendors y un caller census de adapters como fuentes versionadas.
- Candidato activo, candidatos aprobados, candidatos en revisión y evidencia de aprobación.
- Navegación por `?family=<id>`.
- Compatibilidad de prueba con `?fixture=approved-family-v1`.
- Fixtures locales no-admin con todos sus estados declarados.
- Ledger de adapters activos/retirados derivado de caller census.
- Densidad Touch/Compacta según viewport.
- Oscuro como fallback y claro con paridad.
- Reflow móvil/tablet, teclado, touch, zoom, reduced motion y lector de pantalla.
- Presupuesto de rendimiento, pruebas PHP/Zod/React/browser y gate humano de goldens.
- Corte, caller census, rollback y retiro de assets exclusivos.
- Promoción coordinada a Design System 1.2.0 sólo al cierre completo.

### Fuera de alcance

- Todo `/admin/`.
- VIEWs, controladores, rutas, estilos, scripts, iconos, plugins o autenticación de Admin.
- AdminLTE, `admin-operations`, `admin-auth` o la demo “Shell y operaciones Admin”.
- Cambiar `internal.design-system.view`, roles, aliases o matriz RBAC.
- Cambiar RLS, `ProjectScope`, `ProjectSqlGuard`, runtime boundary, grants, usuarios, credenciales,
  membresías o proyectos.
- DDL, DML, seeds, backfills, migraciones o fixtures persistentes.
- Leer datos operativos de proyectos, semanas, actividades, BI, notificaciones o LPS.
- Exponer edición de candidatos, aprobaciones, tokens, JSON o decisiones desde la UI.
- Crear un CMS del Design System.
- Aprobar visualmente un candidato por el hecho de implementarlo.
- Cargar jQuery, Bootstrap, Handsontable, DataTables, Select2, Tom Select, SweetAlert2, Toastr,
  jQuery UI, Font Awesome o AdminLTE para renderizar una demo React.
- Modernizar `/admin/` como efecto lateral de retirar deuda de adapters.
- Reescribir T01–T03 o los módulos S01–S25.
- Regenerar, reemplazar o commitear goldens/baselines sin aprobación visual explícita.
- Inventar consumidores para mantener un adapter sin callers.
- Introducir datos que parezcan contraseñas reales, nombres de personas o proyectos reales.
- Añadir búsqueda global, edición de JSON, comparación de versiones o documentación externa:
  legacy no ofrece esas funciones.

## Fuentes y precedencia

1. Código y contratos JSON vigentes del worktree.
2. `public/css/tokens.css`, `DESIGN.md` y contratos ejecutables de `docs/design-system/`.
3. T01–T03 y cierres reales de S01–S25.
4. Esta spec para la migración del laboratorio.
5. Legacy como caracterización observable, nunca como autoridad de seguridad.

La decisión más reciente del programa exige oscuro y claro. Esa decisión supersede el alcance
histórico dark-only donde exista contradicción documental. `linen` no reaparece: el tema claro es
`light`/`claro` del shell React actual.

## Punto de partida medido

### React actual

- `SpaRouter::RUTAS_MIGRADAS` contiene sólo `/app`.
- `frontend/src` tiene shell mínimo, login, proyecto, tema y cliente HTTP.
- No existe módulo, ruta, esquema Zod, gateway, renderer, fixture ni prueba React de S26.
- `Rutas` bloquea todo contenido autenticado sin proyecto; S26 necesita una salida global anterior
  a ese gate.
- `frontend/src/lib/api/cliente.ts` es la única frontera HTTP permitida.
- La sidebar actual es de proyecto y no debe recibir una entrada interna.

### Ruta, ambiente y capacidad

`public/index.php` registra `/internal/design-system` sólo cuando
`AppEnvironment::allowsInternalTools()`. El controlador vuelve a ejecutar
`DesignSystemLabAccessPolicy::status()`:

| Caso | Resultado legacy | Resultado S26 |
|---|---:|---:|
| production u otro ambiente | 404 | 404 antes de servir React |
| development/testing sin capacidad | 403 | 403 antes de servir React |
| development/testing con capacidad | 200 | 200 y host React |
| sesión autorizada sin proyecto | 200 | 200 |
| proyecto distinto/semana distinta | mismo catálogo | mismo catálogo |

La capacidad es `internal.design-system.view`. La política resuelve el rol global por usuario y no
por el proyecto seleccionado. S26 conserva esa propiedad y no añade una capacidad.

La ruta no puede entrar sin más en `SpaRouter::RUTAS_MIGRADAS`: el front controller decidiría servir
el HTML SPA antes de ejecutar la política específica. El controlador protegido permanece como
autoridad y, sólo después de 200, sirve `public/app/index.html`.

### Catálogo y aprobaciones

Medición vigente:

| Contrato | Cantidad | Observación |
|---|---:|---|
| familias | 10 | todas tienen renderer PHP |
| candidatos | 17 | aprobados y candidate |
| aprobaciones | 12 | shell y estados tienen dos |
| componentes | 29 | 10 stable, 18 candidate, 1 compatibility |
| grupos UI | 87 | todos declaran sólo dark hoy |
| fixtures | 11 | una es exclusivamente Admin |
| vendors inventariados | 14 | uno es AdminLTE |
| escenarios del manifiesto | 20 | dark, 1180 y 1440 |
| goldens bloqueantes | 18 | estados usa contratos geométricos |

El controlador actual reduce aprobaciones a un mapa `familyId → candidateId`. En familias con dos
aprobaciones conserva sólo la última. Eso pierde `adaptive-shell` y `tinted-status` aunque sus
aprobaciones siguen vigentes. React no repetirá esa reducción.

Dos familias tienen candidato activo todavía en revisión:

- Fundamentos: `foundation-inventory-action-color`.
- Acciones: `theme-adaptive-primary`.

Cinco familias no declaran candidato activo y usan una referencia aprobada. El estado visible debe
explicar esa diferencia; no puede rotular una familia como aprobada sólo porque exista alguna base
aprobada.

### Vistas y comportamiento legacy

| Pieza | Líneas | Responsabilidad | Sustituto React |
|---|---:|---|---|
| VIEW-13 `actions.php` | 32 | primarias/secundarias/grupos | `FamiliaAcciones` |
| VIEW-14 `bi-primitives.php` | 86 | KPI, curva, dona, radar | `FamiliaBi` |
| VIEW-15 `data-display.php` | 82 | tabla, tarjetas, paginación | `FamiliaDatos` |
| VIEW-16 `forms-filters.php` | 52 | campos, filtros, selects | `FamiliaFormularios` |
| VIEW-17 `foundations.php` | 31 | marca, color, tipografía, espacio | `FamiliaFundamentos` |
| VIEW-18 `overlays.php` | 20 | modal/drawer | `FamiliaOverlays` |
| VIEW-19 `page-structure.php` | 19 | header/canvas/secciones | `FamiliaEstructura` |
| VIEW-20 `shell-navigation.php` | 65 | shell/sidebar/contexto | `FamiliaNavegacion` |
| VIEW-21 `states-feedback.php` | 89 | estados, gravedad y matices | `FamiliaEstados` |
| VIEW-22 `vendor-adapters.php` | 66 | skins/adapters | `FamiliaAdapters` |
| VIEW-23 `lab.view.php` | 108 | documento y rail | `PaginaDesignSystem` |
| VIEW-24 `operational-fixtures.php` | 141 | demos de estados | `FixtureOperacional` |
| VIEW-25 `ui-group-index.php` | 17 | índice por familia | `IndiceGruposUi` |

El JavaScript legacy tiene 483 líneas. Maneja familia/URL/history/foco, densidad, drawer de shell,
estados locales, contraseña, semana, autosave simulado, tabla, notificaciones, drawer LPS, BI,
datepicker, selects y modales. No hace peticiones HTTP. También inserta una fila con `innerHTML`;
React la reemplaza por estado y render declarativo.

El CSS del laboratorio tiene 1.965 líneas y el documento monta las diez familias para ocultar nueve.
El target monta sólo la familia activa y lazy-loads su renderer.

### Fixtures y datos de demostración

El contrato tiene 11 fixtures. S26 entrega 10 no-admin tras excluir `admin-operations`:

- `project-selector`;
- `auth-credentials`, sin consumer `admin-auth`;
- `context-week`;
- `editable-grid`, sin PDC v1 ni Contratos retirados;
- `datatables-legacy`, sin Admin;
- `notifications-center`;
- `lps-context-drawer`;
- `bi-runtime-drilldown`;
- `tom-select-advanced`, sin Listado de Actividades retirado;
- `enriched-datepicker`, sin PDC v1.

El nombre histórico de una fixture puede mantenerse para trazabilidad aunque su implementación
React ya no cargue ese vendor. Sus consumers se derivan de rutas/callers vivos, no se copian por
inercia.

### Adapters

`vendors.json` inventaría 14 dependencias y el manifiesto del laboratorio carga ocho. El grupo de
adapters contiene diez entradas, incluida AdminLTE. Esa matriz no representa el cierre React:

- cada adapter necesita caller census productivo no-admin;
- caller > 0: se documenta como “vigente” y se muestra una demo semántica canónica;
- caller = 0: se documenta como “retirado” y no se carga asset, adapter ni demo ejecutable;
- AdminLTE: se excluye completamente de respuesta, UI, manifiesto y tests S26;
- `aia-fonts` es fundamento, no adapter;
- un vendor retirado puede permanecer físicamente mientras otro frente/`/admin/` lo use, pero no
  forma parte del bundle React.

### Tema, responsive y evidencia

El runtime actual del laboratorio está gobernado en dark a 1180×820 y 1440×900. La documentación
más nueva ya declara ambos temas y el programa incluye móvil.

S26 amplía la matriz funcional a:

- 390×844 móvil;
- 768×1024 tablet;
- 1180×820 desktop canónico Touch;
- 1440×900 wide Compacta;
- 320 px y 200% zoom como reflow;
- oscuro y claro.

Los goldens actuales no se regeneran automáticamente. El React pilot corre primero junto al
laboratorio PHP; las capturas nuevas son candidatas no versionadas hasta aprobación humana.

### Pruebas y salud de la base

Evidencia de auditoría del 2026-08-31:

- `test_design_system_lab_access.php`: PASS;
- contratos de laboratorio, fixtures y grupos: 12/12 PASS;
- suite estática completa: PASS;
- detector Impeccable sobre las fuentes del laboratorio: sin hallazgos automáticos;
- Biome amplio: rojo por 14 errores y 74 warnings preexistentes en el árbol compartido, incluido
  AdminLTE y bridges legacy; no es un fallo introducido por S26.

La implementación S26 crea un gate enfocado de presupuesto cero sobre sus nuevas fuentes React/CSS.
No maquilla el rojo amplio ni absorbe deuda fuera de sus archivos.

## Decisiones de arquitectura

### DS-R1 — Controlador protegido antes del host React

`DesignSystemLabController::index()` conserva la política y sirve el index React después de 200.
`/internal/design-system` no se añade a la lista genérica de prefijos SPA.

### DS-R2 — API con la misma política

`GET /api/internal/design-system/catalog` se registra sólo en development/testing y vuelve a llamar
la misma policy. Page y API no pueden divergir: 404/403/200 deben coincidir.

### DS-R3 — Salida global antes del proyecto

T01 incorpora un outlet interno autenticado anterior a `if (!sesion.project)`. Sólo reconoce la
ruta exacta S26; el resto de módulos mantiene el requisito de proyecto.

### DS-R4 — Sin sidebar de producto

No se añade ítem a Información, Obra, Compras, cuenta ni Control Tower. El laboratorio es accesible
por URL directa en ambientes permitidos. Su rail de familias es su única navegación lateral.

### DS-R5 — Snapshot local, versionado y sin base de datos

Un `DesignSystemCatalogService` carga archivos locales versionados, valida referencias y devuelve
un snapshot. El caller census se genera y versiona contra un SHA durante el gate; nunca se escanea
el árbol de código en una petición HTTP. No usa `Database`, `ProjectScope`, sesión de proyecto ni
semana.

### DS-R6 — Aprobaciones como conjunto

Cada familia expone:

- `activeCandidateId` exacto o null;
- `approvedCandidateIds[]` completo y ordenado;
- `referenceCandidateIds[]` para modo aprobado;
- estado derivado visible;
- candidatos con su estado individual.

`fixture=approved-family-v1` renderiza el conjunto aprobado, no un único “último” candidato.

### DS-R7 — Contratos fail-closed

Archivo ausente, JSON inválido, esquema inválido, familia sin renderer, referencia huérfana,
candidato aprobado inexistente, token inexistente o grupo sin clasificación produce error
`CATALOG_INVALID`. La respuesta no filtra ruta de archivo ni stack trace.

### DS-R8 — Registro exhaustivo y lazy

Un registro TypeScript relaciona exactamente diez IDs con diez importaciones lazy. Falta, duplicado
o renderer extra falla un contrato. Sólo se monta la familia activa.

### DS-R9 — Fixtures locales y honestas

Los fixtures viven en memoria, nunca hacen fetch, no persisten, no emiten éxito de negocio y
muestran “Demostración local; no guarda”. Usan nombres sintéticos y valores no reutilizables.

### DS-R10 — Vendors por caller census

La categoría adapter es resultado de evidencia:

- `active` si existe al menos un caller productivo no-admin;
- `retired` si el caller census es cero;
- `excluded` sólo para Admin y no sale al cliente.

El laboratorio React no carga el JS/CSS del vendor en ninguno de los dos primeros estados.

### DS-R11 — Temas compartidos

S26 consume el bootstrap/conmutador de T01. Dark es fallback técnico y claro tiene paridad.
No crea otro storage, atributo, token ni conmutador.

### DS-R12 — Densidad responsive

- ancho ≤1180: Touch obligatoria;
- ancho >1180: Compacta por defecto y Touch opcional;
- la preferencia manual dura sólo la sesión del componente;
- no se persiste una segunda preferencia global.

### DS-R13 — Evidencia por aprobación humana

Los 18 goldens dark vigentes permanecen durante pilot. Reemplazarlos o añadir light/móvil requiere
captura candidata, revisión humana explícita, hashes y manifiesto en el mismo cambio.

### DS-R14 — 1.2.0 sólo en cierre atómico

La ampliación contractual de temas/viewport y el retiro de bridges no-admin corresponde a 1.2.0.
`version.json`, changelog, contratos y manifiestos cambian juntos sólo cuando todos los gates
S01–S26 estén verdes. Antes de eso siguen en 1.1.0.

## Modelo canónico

### DesignSystemCatalogSnapshot

| Campo | Tipo | Regla |
|---|---|---|
| `schemaVersion` | literal `1` | versión del endpoint |
| `designSystemVersion` | SemVer | coincide con fuente vigente |
| `mode` | `active · approved` | normalizado por servidor |
| `sourceHash` | sha256 | hash determinista de fuentes |
| `families` | `FamilyCatalogItem[10]` | orden canónico |
| `stateSemantics` | objeto tipado | sin mutación |
| `vendorLedger` | lista | sin AdminLTE |
| `availableThemes` | `["dark","light"]` | contrato S26 |
| `availableLayouts` | lista | mobile/tablet/desktop/wide |

No incluye usuario, rol, proyecto, semana, CSRF, credencial, path absoluto ni fecha generada.

### FamilyCatalogItem

| Campo | Tipo | Regla |
|---|---|---|
| `id` | ID cerrado | uno de diez |
| `label` | texto | contrato homologation |
| `description` | texto | contrato homologation |
| `status` | `approved · candidate · mixed · reference-only` | derivado |
| `activeCandidateId` | string/null | exacto |
| `approvedCandidateIds` | string[] | todos |
| `referenceCandidateIds` | string[] | modo aprobado |
| `candidates` | Candidate[] | sin colapsar |
| `components` | Component[] | por familia |
| `uiGroups` | UiGroup[] | clasificados |
| `fixtures` | Fixture[] | sólo no-admin |
| `rendererId` | igual a id | gate exhaustivo |

### Candidate

Conserva ID, nombre, propósito, estado, tokens/archivos/evidencia que ya declare el contrato.
`approved` describe ese candidato concreto. Implementación o aparición en la UI nunca lo promueve.

### VendorLedgerItem

| Campo | Tipo | Regla |
|---|---|---|
| `id` | string | no `adminlte` |
| `classification` | contrato | adapter/legacy/inventory/foundation |
| `adapterMaturity` | string/null | inventario |
| `runtimeStatus` | `active · retired · foundation` | caller census |
| `consumerIds` | string[] | callers productivos no-admin |
| `consumerCount` | entero | coincide con IDs |
| `assetsLoadedByLab` | literal false | siempre |
| `note` | texto | retiro o compatibilidad |

## Contrato HTTP objetivo

### GET /api/internal/design-system/catalog

Query permitida:

| Campo | Valores | Default | Regla |
|---|---|---|---|
| `family` | ID de familia | primera familia | hint de presentación, no autorización |
| `mode` | `active · approved` | `active` | modo explícito |
| `fixture` | `approved-family-v1` | ausente | alias de pruebas a `approved` |

Query desconocida o valor inválido devuelve `400 INVALID_QUERY`. Una `family` desconocida en la
página se normaliza a la primera y reemplaza la URL; el endpoint no acepta IDs libres.

Respuesta 200:

```json
{
  "schemaVersion": 1,
  "designSystemVersion": "1.1.0",
  "mode": "active",
  "sourceHash": "<sha256>",
  "selectedFamilyId": "foundations",
  "availableThemes": ["dark", "light"],
  "availableLayouts": ["mobile", "tablet", "desktop", "wide"],
  "families": [],
  "stateSemantics": {},
  "vendorLedger": []
}
```

El ejemplo conserva 1.1.0 porque 1.2.0 sólo aparece al gate final.

Headers:

- `Content-Type: application/json; charset=utf-8`;
- `Cache-Control: no-store`;
- `Vary: Cookie`;
- sin CORS abierto.

Errores:

| HTTP | Código | Caso |
|---:|---|---|
| 400 | `INVALID_QUERY` | query no permitida |
| 401 | `SESSION_REQUIRED` | middleware sin sesión |
| 403 | `CAPABILITY_REQUIRED` | ambiente válido, capacidad ausente |
| 404 | `NOT_FOUND` | ambiente no permitido/ruta no registrada |
| 500 | `CATALOG_INVALID` | fuentes/relaciones inválidas |
| 500 | `INTERNAL_ERROR` | error no clasificable |

La forma de error es `{error:{code,message,requestId,retryable}}`. El mensaje no expone paths,
contenido fuente, rol, ambiente ni stack.

## Arquitectura backend

### DesignSystemCatalogLoader

Lee las nueve autoridades:

1. `version.json`;
2. `homologation.json`;
3. `family-approvals.json`;
4. `component-catalog.json`;
5. `ui-groups-inventory.json`;
6. `state-semantics.json`;
7. `operational-fixtures.json`;
8. `vendors.json`;
9. `adapter-caller-census.json`, creado por S26 con su schema y SHA de procedencia.

Usa lectura inyectable para pruebas puras y valida JSON/esquemas antes de componer.

### DesignSystemCatalogService

- construye aprobaciones como multimap;
- verifica candidate/family/component/group/fixture;
- aplica exclusión Admin;
- aplica caller census versionado;
- calcula estados derivados;
- calcula hash determinista;
- no conoce HTTP ni React.

### DesignSystemCatalogPresenter

Produce una forma JSON estable, elimina campos internos y paths absolutos y ordena arrays de forma
determinista. No modifica los JSON en runtime.

### Integración de ruta

- `DesignSystemLabController`: policy y host React;
- nuevo `DesignSystemCatalogController`: policy, query, presenter y errores;
- `public/index.php`: ambas rutas sólo en ambientes internos;
- `SpaRouter`: sin prefijo S26;
- `SessionMiddleware`: ambas rutas autenticadas.

## Arquitectura frontend

### Estructura propuesta

```text
frontend/src/modules/design-system/
├── api/
│   ├── catalogo.ts
│   ├── esquemas.ts
│   └── *.test.ts
├── dominio/
│   ├── familias.ts
│   ├── candidatos.ts
│   ├── densidad.ts
│   └── *.test.ts
├── estado/
│   ├── useCatalogoDesignSystem.ts
│   └── useFamiliaUrl.ts
├── componentes/
│   ├── InternalShell.tsx
│   ├── CabeceraLaboratorio.tsx
│   ├── RailFamilias.tsx
│   ├── EstadoCandidato.tsx
│   ├── ListaCandidatos.tsx
│   ├── IndiceGruposUi.tsx
│   ├── FixtureOperacional.tsx
│   ├── LedgerAdapters.tsx
│   └── EstadoLaboratorio.tsx
├── familias/
│   ├── acciones.tsx
│   ├── bi.tsx
│   ├── datos.tsx
│   ├── formularios.tsx
│   ├── fundamentos.tsx
│   ├── overlays.tsx
│   ├── estructura.tsx
│   ├── navegacion.tsx
│   ├── estados.tsx
│   ├── adapters.tsx
│   └── registro.ts
├── fixtures/
│   ├── registro.ts
│   └── *.tsx
├── PaginaDesignSystem.tsx
└── design-system.css
```

### Estado remoto

- un único GET por carga;
- AbortController al desmontar o reemplazar;
- sin polling;
- retry manual sólo para GET;
- cache en memoria por `sourceHash/mode`;
- respuesta tardía no reemplaza una solicitud más nueva;
- 401/403/404/500 tienen estados separados;
- ningún componente llama `fetch`.

### Navegación

- `family` vive en URL;
- URL vacía o inválida se reemplaza por la primera familia sin añadir historial;
- clic añade historial;
- back/forward restaura familia;
- el h1 de familia recibe foco programático sólo tras navegación iniciada por usuario/history;
- `aria-current="page"` en el rail;
- en móvil el rail se presenta como disclosure/drawer accesible;
- no hay segundo router ni recarga completa.

### Composición por familia

Cada página monta, en orden:

1. estado y propósito;
2. candidato activo/referencia;
3. todos los candidatos y aprobaciones;
4. specimen semántico;
5. componentes;
6. índice de grupos UI;
7. fixtures de esa familia;
8. fuentes/evidencia relativa cuando aplique.

## Las diez familias

### Fundamentos

- marca AIA, roles de color y escalas semánticas;
- tipografía Montserrat/Inter;
- espaciado, radio, elevación y focus;
- swatches con nombre, token y valor resuelto en ambos temas;
- nunca hex hardcodeado en JSX/CSS.

### Navegación y shell

- sidebar expandida/colapsada;
- contexto proyecto/usuario como dato sintético;
- selector de semana local;
- centro de notificaciones local;
- responsive drawer;
- sin `admin-operations`.

### Estructura de página

- header integrado, acciones y canvas;
- selector de proyectos sintético;
- jerarquía de secciones, vacíos y error;
- sin datos reales.

### Acciones

- primaria, secundaria, destructiva y grupo;
- loading/disabled/focus;
- texto de consecuencia;
- no conserva una fixture retirada de PDC v1.

### Formularios y filtros

- login sintético sin valor de contraseña reutilizable;
- filtros, búsqueda, fecha y selector enriquecido semántico;
- invalid/expired/error/success;
- no `admin-auth`.

### Estados y retroalimentación

- cuatro niveles, matices y reglas de eje;
- texto + icono/label, no color solo;
- múltiples candidatos aprobados visibles;
- tabla/ejemplos generados desde `stateSemantics`.

### Presentación de datos

- misma colección en tabla desktop y cards touch;
- sort/paginación/filtros locales;
- loading/empty/error/success;
- edición sólo donde el specimen la declare.

### Overlays

- modal wide y drawer touch;
- LPS contextual local;
- foco atrapado, Escape, inert y restauración;
- no portal que escape del tema.

### Adapters

- ledger active/retired;
- demos sólo semánticas;
- cero assets vendor cargados;
- AdminLTE ausente;
- cero callers ficticios.

### BI

- KPI, curva, dona, radar y drilldown local;
- SVG/HTML React, sin AnyChart/Chart.js;
- título, resumen, leyenda y tabla equivalente;
- carga incremental local.

## Estados de producto

### Carga

Skeleton/estado de carga conserva estructura sin anunciar éxito. El rail no presenta familias falsas.

### Vacío

Catálogo vacío es `CATALOG_INVALID`, no un vacío normal. Una familia sin fixture o sin caller
vigente muestra un vacío explicativo.

### Error

- conexión/500: alerta con retry;
- contrato Zod: “Catálogo incompatible”, request ID si existe;
- 403: acceso no autorizado sin sugerir elevar permisos;
- 404: página no disponible;
- renderer ausente: error interno bloqueante, nunca fallback silencioso.

### Fixture

Cada fixture tiene estado seleccionado, controles `aria-pressed` y región viva. Cambiar estado sólo
afecta esa demo y nunca el catálogo.

## Responsive, densidad y tema

| Ancho | Navegación | Densidad | Contenido |
|---:|---|---|---|
| 320–479 | drawer/disclosure | Touch | una columna |
| 480–767 | drawer/disclosure | Touch | una columna amplia |
| 768–1180 | rail colapsable | Touch | una/dos columnas |
| >1180 | rail persistente | Compacta default | canvas de dos columnas |

- targets Touch mínimo 44×44;
- controles compactos sólo >1180 y piso accesible;
- sin overflow horizontal de página;
- tablas pueden tener región scroll con nombre y teclado;
- claro y oscuro tienen estructura idéntica;
- el switch T01 conserva preferencia;
- color/background no se anima;
- reduced motion elimina transiciones no esenciales.

## Accesibilidad

- landmarks únicos y skip link;
- h1 único y jerarquía sin saltos;
- familia activa con `aria-current`;
- foco visible tokenizado;
- back/forward no pierde foco;
- drawer/modal con trap, Escape y retorno;
- tooltips no contienen información exclusiva;
- charts con tabla equivalente;
- swatches con texto;
- estados no dependen sólo del color;
- live regions sólo para cambios relevantes;
- no IDs duplicados porque se monta una familia;
- nombres accesibles de controles incluyen efecto;
- reflow a 320 px y 200%;
- Axe sin violaciones serious/critical en ocho combinaciones mínimas;
- revisión manual de teclado, zoom, lector y contraste antes de cierre.

## Seguridad, privacidad y RLS

- page y API comparten `DesignSystemLabAccessPolicy`;
- el ambiente se decide server-side;
- no se confía en `capabilities` del cliente;
- no se acepta project/user/role/week en query;
- no hay mutaciones ni CSRF de negocio;
- no hay SQL ni acceso a Database;
- no cambia RLS;
- no se registran JSON completos ni datos de sesión;
- fixtures no contienen secretos, correos/teléfonos reales ni proyectos reales;
- errores no filtran filesystem;
- `sourceHash` prueba contenido, no path;
- la ruta no aparece en navegación productiva;
- el chunk lazy no sustituye el gate HTTP ni el gate del endpoint.

## Rendimiento

El baseline vigente mide medianas aproximadas de 232 ms FCP en 1180 y 216 ms en 1440, CLS
~0.008, 24 recursos y 19 CSS. El target:

- mantiene los techos vigentes durante pilot;
- añade presupuesto de JS/chunks;
- una carga de catálogo;
- cero assets vendor;
- una familia montada;
- lazy por familia;
- cero long task >250 ms;
- CLS ≤0.1;
- tres cargas frías por viewport canónico;
- artefacto con SHA/diff/untracked como el gate actual;
- no compara contra una medición de otro worktree.

## Gobierno, SemVer y evidencia

S26 añade decisiones explícitas que superseden el alcance histórico cuando se implemente:

- ambos temas contractuales;
- móvil soportado y requerido por módulo;
- laboratorio React protegido;
- aprobaciones múltiples no colapsadas;
- fixtures Admin excluidas;
- adapters gobernados por callers.

No se edita una aprobación por migrar markup. Cualquier cambio visual de candidato conserva el
estado candidate hasta aprobación separada.

El gate de 1.2.0 exige:

1. contratos y schemas consistentes;
2. todos los consumers S01–S25 cortados;
3. caller census vendor;
4. dark/light y matriz responsive;
5. accesibilidad;
6. performance;
7. snapshots aprobados;
8. changelog/decisions/version/manifest atómicos;
9. CI verde.

La promoción actualiza el campo envolvente `designSystemVersion` de todos los documentos vivos que
el gate enumera. No reescribe mediciones históricas. En `stable-api-1.0.0.json` mantiene
`targetVersion`, garantía y componentes byte-equivalentes salvo ese metadato; 1.2.0 no inventa una
nueva API estable. En `closeout-evidence.json` no cambia estado, fecha, hash ni procedencia de un
recibo antiguo para hacerlo parecer fresco. Un recibo S26 sólo se incorpora después de ejecutar su
comando real. El audit baseline sólo puede arrastrarse a 1.2.0 con hashes before/after idénticos y
referencia de aprobación explícita; si cambió, la promoción se bloquea.

## Coexistencia, corte y rollback

### Pilot

- PHP sigue canónico;
- React disponible sólo bajo flag interno o ruta de prueba protegida;
- ambos leen los mismos JSON;
- goldens PHP quedan intactos;
- pruebas comparan familias, candidatos, grupos y fixtures.

### Corte

- controlador autorizado sirve React;
- API se vuelve fuente del cliente;
- manifiesto apunta a React;
- caller census confirma cero referencias exclusivas;
- VIEW-13 a VIEW-25 y `design_system_lab.js` se retiran;
- `lab.css`/entrypoint se retiran sólo si no tienen callers;
- JSON, schemas, evidence, tokens y contratos de gobierno permanecen.

### Rollback

- controlador vuelve a VIEW-23;
- API puede permanecer no enlazada o retirarse;
- no hay rollback de datos;
- versión 1.2.0 no se publica si el corte no cerró;
- assets sólo se restauran desde git, sin tocar `/admin/`.

## Estrategia de pruebas

### PHP y contratos

- ambiente/capacidad para page y API;
- sesión sin proyecto;
- ninguna llamada Database;
- query allowlist;
- JSON ausente/malformado;
- referencias huérfanas;
- multimap de aprobaciones;
- exclusión Admin;
- caller census;
- headers/errores;
- host React sólo después de policy.

### Frontend

- Zod estricto éxito/error;
- gateway sólo por `cliente.ts`;
- abort/stale/retry;
- registro exhaustivo de diez familias;
- URL/history/foco;
- candidato activo y aprobados;
- fixtures locales;
- adapter ledger;
- dark/light/densidad;
- overlays/charts/accesibilidad.

### Navegador

Matriz mínima funcional:

- 390×844 dark/light;
- 768×1024 dark/light;
- 1180×820 dark/light;
- 1440×900 dark/light.

Escenarios:

- autorizado sin proyecto;
- denegado 403;
- ruta inexistente 404 por contrato PHP;
- familia inicial/inválida/history;
- diez familias;
- múltiples aprobaciones;
- diez fixtures no-admin;
- Admin ausente;
- cero requests inesperadas;
- cero asset vendor;
- tema, reflow, teclado, overlay, BI;
- performance;
- consola/page errors cero.

Los tests locales usan la puerta de desarrollo. No escriben credenciales en `/login` ni aceptan una
redirección a login como evidencia.

## Criterios de aceptación

### Acceso y frontera

- **S26-AC-001:** production responde 404 y no sirve el host React.
- **S26-AC-002:** ambiente desconocido falla como production.
- **S26-AC-003:** development/testing sin capacidad responde 403.
- **S26-AC-004:** capacidad autorizada responde 200.
- **S26-AC-005:** la página funciona sin proyecto activo.
- **S26-AC-006:** cambiar proyecto o semana no cambia el catálogo.
- **S26-AC-007:** la capacidad se resuelve en servidor.
- **S26-AC-008:** no se añade una capacidad nueva.
- **S26-AC-009:** page y API comparten la misma policy.
- **S26-AC-010:** la ruta no entra en el prefijo SPA genérico.
- **S26-AC-011:** el controlador sólo sirve index React después de 200.
- **S26-AC-012:** SessionMiddleware sigue exigiendo autenticación.
- **S26-AC-013:** no aparece enlace en la sidebar de producto.
- **S26-AC-014:** el outlet interno ocurre antes del selector de proyecto.
- **S26-AC-015:** ninguna otra ruta elude el gate de proyecto.
- **S26-AC-016:** `/admin/` no se registra, modifica ni renderiza.
- **S26-AC-017:** acceso denegado no descarga datos del catálogo.
- **S26-AC-018:** 404 no revela existencia/capacidad/ambiente.
- **S26-AC-019:** 403 no sugiere cambiar rol.
- **S26-AC-020:** refresh/deep link conserva status y ruta.

### API y catálogo

- **S26-AC-021:** existe un único GET de catálogo.
- **S26-AC-022:** todo HTTP productivo usa `cliente.ts`.
- **S26-AC-023:** éxito y error tienen Zod estricto.
- **S26-AC-024:** existe prueba de contrato PHP del endpoint.
- **S26-AC-025:** `schemaVersion` es literal 1.
- **S26-AC-026:** versión coincide con `version.json`.
- **S26-AC-027:** sourceHash es determinista.
- **S26-AC-028:** no hay timestamp variable en payload.
- **S26-AC-029:** se devuelven exactamente diez familias.
- **S26-AC-030:** orden de familias sigue homologation.
- **S26-AC-031:** `family` sólo acepta IDs canónicos.
- **S26-AC-032:** `mode` sólo acepta active/approved.
- **S26-AC-033:** fixture legacy normaliza a approved.
- **S26-AC-034:** query desconocida devuelve 400 tipado.
- **S26-AC-035:** headers JSON/no-store/Vary son correctos.
- **S26-AC-036:** errores incluyen requestId y no stack.
- **S26-AC-037:** archivo ausente devuelve CATALOG_INVALID.
- **S26-AC-038:** JSON malformado devuelve CATALOG_INVALID.
- **S26-AC-039:** schema inválido devuelve CATALOG_INVALID.
- **S26-AC-040:** referencia huérfana falla cerrado.
- **S26-AC-041:** token/selector inexistente falla gate.
- **S26-AC-042:** loader es inyectable y testeable sin filesystem real.
- **S26-AC-043:** servicio no instancia Database.
- **S26-AC-044:** payload no contiene paths absolutos.
- **S26-AC-045:** payload no contiene sesión/proyecto/semana/rol.
- **S26-AC-046:** API no acepta autorización desde query/body.
- **S26-AC-047:** no hay POST/PUT/PATCH/DELETE S26.
- **S26-AC-048:** no hay polling.
- **S26-AC-049:** retry es manual y sólo GET.
- **S26-AC-050:** abort y respuestas stale están cubiertos.

### Candidatos y gobierno

- **S26-AC-051:** activeCandidate exacto se conserva.
- **S26-AC-052:** null activo no se inventa.
- **S26-AC-053:** todas las aprobaciones se conservan.
- **S26-AC-054:** shell muestra sus dos aprobaciones.
- **S26-AC-055:** estados muestra sus dos aprobaciones.
- **S26-AC-056:** mode approved no colapsa a última aprobación.
- **S26-AC-057:** candidato active candidate mantiene badge candidate.
- **S26-AC-058:** referencia aprobada no aprueba la familia completa.
- **S26-AC-059:** estado derived es determinista.
- **S26-AC-060:** candidate no cambia por renderizarse.
- **S26-AC-061:** UI no edita candidatos.
- **S26-AC-062:** UI no edita aprobaciones.
- **S26-AC-063:** UI no escribe JSON.
- **S26-AC-064:** 29 componentes quedan trazados.
- **S26-AC-065:** madurez stable/candidate/compatibility se conserva.
- **S26-AC-066:** 87 grupos fuente quedan clasificados.
- **S26-AC-067:** grupos no-admin soportados declaran dark/light al cierre.
- **S26-AC-068:** semántica de estados proviene del contrato.
- **S26-AC-069:** cambios contractuales se registran en decisions/changelog.
- **S26-AC-070:** 1.2.0 no se activa antes del gate.
- **S26-AC-071:** versión/schema/manifiesto cambian atómicamente.
- **S26-AC-072:** goldens no se regeneran automáticamente.
- **S26-AC-073:** captura nueva requiere aprobación humana.
- **S26-AC-074:** hashes y PNG corresponden al escenario.
- **S26-AC-075:** no se presentan checkboxes como evidencia.

### Familias, piezas y navegación

- **S26-AC-076:** registro contiene diez renderers exactos.
- **S26-AC-077:** renderer faltante/extra falla test.
- **S26-AC-078:** sólo se monta la familia activa.
- **S26-AC-079:** cada familia lazy-loads su chunk.
- **S26-AC-080:** VIEW-13 conserva Acciones.
- **S26-AC-081:** VIEW-14 conserva BI accesible.
- **S26-AC-082:** VIEW-15 conserva tabla/tarjetas/paginación.
- **S26-AC-083:** VIEW-16 conserva formularios/filtros.
- **S26-AC-084:** VIEW-17 conserva fundamentos.
- **S26-AC-085:** VIEW-18 conserva modal/drawer.
- **S26-AC-086:** VIEW-19 conserva estructura de página.
- **S26-AC-087:** VIEW-20 conserva shell/navegación sin Admin.
- **S26-AC-088:** VIEW-21 conserva estados/feedback.
- **S26-AC-089:** VIEW-22 conserva ledger adapters.
- **S26-AC-090:** VIEW-23 se sustituye por página React.
- **S26-AC-091:** VIEW-24 se sustituye por runner compartido.
- **S26-AC-092:** VIEW-25 se sustituye por índice React.
- **S26-AC-093:** URL sin family normaliza a primera.
- **S26-AC-094:** URL inválida se reemplaza sin historial extra.
- **S26-AC-095:** clic añade historial.
- **S26-AC-096:** back/forward restaura familia.
- **S26-AC-097:** rail marca aria-current.
- **S26-AC-098:** heading recibe foco correctamente.
- **S26-AC-099:** móvil usa navegación accesible.
- **S26-AC-100:** no hay router/sidebar duplicados.

### Fixtures, adapters y exclusión Admin

- **S26-AC-101:** se renderizan diez fixtures no-admin.
- **S26-AC-102:** admin-operations no sale del servidor.
- **S26-AC-103:** AdminLTE no sale del servidor.
- **S26-AC-104:** admin-auth se elimina de consumers.
- **S26-AC-105:** admin se elimina de DataTables consumers.
- **S26-AC-106:** PDC v1/Contratos/Listado retirados no son consumers.
- **S26-AC-107:** cada consumer restante tiene caller probado.
- **S26-AC-108:** fixture conserva default y error.
- **S26-AC-109:** estados de fixture usan aria-pressed.
- **S26-AC-110:** cambio se anuncia en live region.
- **S26-AC-111:** fixture declara que no guarda.
- **S26-AC-112:** fixture no hace fetch.
- **S26-AC-113:** fixture no persiste local/session storage.
- **S26-AC-114:** datos son sintéticos.
- **S26-AC-115:** no hay valor con apariencia de credencial reutilizable.
- **S26-AC-116:** caller >0 produce adapter active.
- **S26-AC-117:** caller=0 produce retired.
- **S26-AC-118:** ledger concuerda con census.
- **S26-AC-119:** assetsLoadedByLab es false.
- **S26-AC-120:** ningún request carga vendor JS/CSS.
- **S26-AC-121:** demos de adapter son semánticas.
- **S26-AC-122:** aia-fonts se clasifica como foundation.
- **S26-AC-123:** no se borra asset usado fuera de S26.
- **S26-AC-124:** retiro exige cero callers exclusivos.
- **S26-AC-125:** `/admin/` permanece byte-identical.

### Tema, responsive, accesibilidad y performance

- **S26-AC-126:** oscuro es fallback.
- **S26-AC-127:** claro tiene paridad funcional/contraste.
- **S26-AC-128:** usa conmutador/persistencia T01.
- **S26-AC-129:** no reaparece linen.
- **S26-AC-130:** sólo usa tokens canónicos.
- **S26-AC-131:** JSX/CSS nuevo no tiene hex/inline/important.
- **S26-AC-132:** 390×844 funciona en ambos temas.
- **S26-AC-133:** 768×1024 funciona en ambos temas.
- **S26-AC-134:** 1180×820 funciona en ambos temas.
- **S26-AC-135:** 1440×900 funciona en ambos temas.
- **S26-AC-136:** 320 px y 200% reflow sin overflow de página.
- **S26-AC-137:** ≤1180 fuerza Touch.
- **S26-AC-138:** >1180 default Compacta y permite Touch.
- **S26-AC-139:** targets Touch cumplen 44×44.
- **S26-AC-140:** reduced motion se respeta.
- **S26-AC-141:** landmarks/h1/skip link son correctos.
- **S26-AC-142:** foco visible y orden lógico.
- **S26-AC-143:** overlays atrapan/restauran foco y Escape.
- **S26-AC-144:** BI tiene resumen/leyenda/tabla equivalente.
- **S26-AC-145:** significado no depende sólo del color.
- **S26-AC-146:** no hay IDs duplicados.
- **S26-AC-147:** Axe serious/critical es cero.
- **S26-AC-148:** una familia montada y una carga de catálogo.
- **S26-AC-149:** CLS/long tasks/recursos cumplen presupuesto.
- **S26-AC-150:** consola, page errors y requests inesperadas son cero.

## Entregas verticales

### Entrega 1 — Frontera y snapshot

Policy compartida, host React protegido, API read-only, loader/validator y catálogo multiaprobación.

### Entrega 2 — Shell y familia útil

Outlet global, página, rail/URL, Zod/gateway y Fundamentos como primera familia end-to-end.

### Entrega 3 — Diez familias y fixtures

Registro lazy, nueve familias restantes, índice UI, semántica y diez fixtures no-admin.

### Entrega 4 — Adapters, matriz y corte

Caller census, ledger, dark/light/responsive/a11y/performance, evidencia, 1.2.0 y retiro de VIEW-13–25.

## Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| bypass del 404/403 al añadir ruta SPA | controlador protegido antes del index |
| salida global bloqueada por selector de proyecto | outlet interno exacto antes del gate |
| aprobación múltiple perdida | multimap + test shell/estados |
| Admin reingresa por JSON compartido | filtro server-side + contrato de ausencia |
| demos cargan vendors sin querer | network allowlist + assetsLoadedByLab=false |
| caller census queda viejo | generado contra SHA de cierre |
| snapshot React se “aprueba solo” | badge exacto + gate humano |
| light/mobile contradicen contratos viejos | decisión/versionado atómico 1.2.0 |
| diez familias inflan bundle/DOM | lazy + montar sólo activa |
| fixtures parecen operar datos | sintéticas, locales, aviso no guarda |
| retiro rompe caller ajeno | cero callers exclusivos antes de borrar |
| Biome amplio rojo oculta regresión | gate enfocado presupuesto cero |

## Alternativas descartadas

- Añadir `/internal/design-system` a `RUTAS_MIGRADAS` sin guard.
- Exigir proyecto para abrir un catálogo global.
- Poner “Design System” en la sidebar de producción.
- Servir los JSON directamente desde `docs/`.
- Leer JSON con fetch desde componentes.
- Mantener el mapa “última aprobación gana”.
- Portar todas las vistas PHP como JSX literal.
- Montar diez familias y ocultar nueve.
- Usar un iframe con el laboratorio legacy.
- Cargar vendors para que la demo sea “real”.
- Incluir AdminLTE como documentación neutral.
- Usar nombres/proyectos/contraseñas actuales.
- Actualizar snapshots automáticamente.
- Subir a 1.2.0 al comienzo.
- Arreglar RLS o base de datos desde S26.

## Decisiones pendientes

Ninguna. Los cambios de candidato y la aprobación visual de snapshots son gates futuros, no
decisiones abiertas de esta spec.

## Autorrevisión

- alcance coincide con S26, 13 views y exclusión `/admin/`;
- ruta y API conservan 404/403/200;
- no proyecto, no RLS, no DDL/DML;
- contrato nuevo tiene Zod y PHP;
- 10 familias, 17 candidatos, 12 aprobaciones, 29 componentes, 87 grupos y 10 fixtures objetivo
  quedan trazados;
- claro/oscuro, responsive, accesibilidad, performance, evidence y rollback están cerrados;
- 150 criterios tienen resultado verificable;
- no se implementó, no se regeneró evidencia y no se tocó dato.
