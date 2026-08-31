---
capa: fuente
tipo: spec
estado: vigente
id: S05
fecha: 2026-08-30
superficie: programa-general
rutas: ["/programa-general"]
depende_de: [T01, T02, S04]
views: [VIEW-34]
areas: [lps, design-system]
fuente: "auditoría de public/index.php, ProgramaGeneralController, GeneralApiController, ReportController, LpsApiController, hot.js, VIEW-34, contratos, RBAC, T01 y frontend actual en shell-minimo-react, 2026-08-30"
resumen: "Migración vertical S05 de Programa General a React con paridad funcional del legacy, tabla semántica en desktop/tablet, tarjetas editables en móvil, contratos tipados y autorización resuelta por servidor, sin modificar RLS ni datos."
---

# S05 — Programa General en React

> **Estado:** diseño técnico autorrevisado, sin decisiones de negocio, producto, estrategia o PM
> pendientes. Esta spec no autoriza implementación, commits, DDL/DML, cambios RLS, cambios de
> permisos, deploy, publicación ni trabajo en `/admin/`. Su plan se escribe inmediatamente después
> con `superpowers:writing-plans`, conforme al programa aprobado de 27 specs y 27 planes.

## Relación con el frente vigente

Esta spec continúa, no reemplaza, las decisiones de:

- [[docs/superpowers/specs/2026-08-28-migracion-react-typescript-design|Migración a React + TypeScript]];
- [[docs/superpowers/specs/2026-08-28-paridad-shell-react-rls-design|Paridad del shell React y RLS]];
- [[docs/superpowers/specs/2026-08-30-t01-shell-runtime-react-design|T01 — shell/runtime React]];
- [[docs/security/rls-runtime-boundary|Frontera runtime de RLS]].

El shell mínimo ya autentica, selecciona proyecto, presenta navegación, conserva tema y sirve la
SPA en `/app/`. RLS, grants, usuarios runtime y credenciales quedan fuera de este frente y se toman
como una frontera ya cerrada. Programa General es el primer módulo funcional que entra en la SPA.

T01 es dueño del bootstrap, la sidebar, el selector de semana, el tema y el contexto de cuenta; S04
es dueño del cambio de proyecto; T02 gobierna el corte transversal del legacy. S05 consume esos
contratos y sólo posee la ruta, datos, operaciones y presentación de Programa General.

La ruta `/programa-general-actualizar` no forma parte de esta migración: pertenece a S06, con otro
permiso y otro flujo. Esta spec tampoco reintroduce el PDC v1 ni toca Plan de Compras v2.

## Resultado buscado

`/programa-general` será una pantalla React con, como mínimo, toda capacidad útil y comportamiento
observable del módulo PHP/JS actual:

1. carga el programa de la semana y del proyecto activos;
2. aplica permisos server-side y presenta únicamente acciones autorizadas;
3. permite cambiar de semana y reutiliza el selector de proyecto del shell;
4. busca, filtra, cuenta y explica estados y alertas;
5. muestra tabla en desktop/tablet y tarjetas editables en móvil;
6. edita código, fechas, unidad, cantidad y avance real con las mismas reglas de dominio;
7. guarda individualmente, recalcula por lote, recarga, exporta CSV y descarga el corte XLSX;
8. integra el drawer contextual con comentarios, escalamiento y crisis;
9. distingue carga, vacío, error, permiso denegado, guardado y recuperación;
10. funciona en claro y oscuro a 390, 768 y 1180 px sin perder accesibilidad.

Paridad no significa copiar Handsontable ni la composición visual del legacy. React puede mejorar
jerarquía, copy, foco, responsive y recuperación siempre que conserve la intención, el dato, la
autorización y el resultado de cada operación.

## Alcance

### Incluido

- La superficie React de Programa General y su entrada desde el shell.
- Contexto autorizado de proyecto, área, semana, acciones y tokens CSRF.
- Adaptadores tipados para los endpoints legacy que continúen vigentes.
- Endpoint cohesivo de contexto y consumo del endpoint protegido de semana propiedad de T01.
- Tabla semántica, tarjetas móviles, filtros, búsqueda, conteos, leyenda y estados.
- Edición individual y actualización por lote.
- CSV, corte XLSX, recarga y enlace BI cuando el servidor lo autorice.
- Drawer contextual de actividad y todas sus acciones pertinentes.
- Contratos PHP, esquemas Zod, pruebas de dominio/componentes y verificación visual.
- Correcciones mínimas de autorización, validación o contexto que sean necesarias para que React
  no reproduzca contradicciones del cliente legacy.
- Conservación temporal del módulo PHP durante el piloto y retiro de sus piezas exclusivas después
  del gate canónico, con rollback por reversión del corte versionado.

### Fuera de alcance

- Modificar RLS, `ProjectScope`, grants, usuarios MySQL, credenciales, tablas, índices o datos.
- Ejecutar migraciones, backfills, limpieza, DDL o DML durante este frente sin autorización nueva y
  explícita.
- Migrar Programación Intermedia, Programación Semanal, Actualizar Cronograma, PDC o BI.
- Reescribir el algoritmo de estados en TypeScript.
- Introducir otra grilla, librería de estado global, data-fetching framework o CSS-in-JS.
- Agregar control de concurrencia optimista o versionado de filas. El contrato v0 conserva
  last-write-wins del servidor y lo declara como límite conocido.
- Eliminar inmediatamente el PHP, Handsontable o CSS legacy compartido por otros módulos.
- Cambiar la semántica de negocio de estados, restricciones, carryover o reportes.
- Normalizar de una sola vez todas las respuestas legacy del repositorio.

## Punto de partida medido

### React

- `frontend/src/shell/rutas.tsx` termina en el shell y un `<h1>` con el proyecto; no existe una
  página ni dominio de Programa General.
- `NavegacionLateral.tsx` enlaza `/programa-general` mediante `<a>`, lo que abandona la SPA.
- `SpaRouter::RUTAS_MIGRADAS` contiene únicamente `/app`.
- El cliente HTTP único valida Zod, pero fuerza `Content-Type: application/json` para cualquier
  body y no puede consumir correctamente formularios URL-encoded o `FormData`.
- La línea base es 30 pruebas frontend verdes y typecheck verde.

### Legacy

- Vista: `views/programa-general/programa_general.view.php`.
- Controlador de página: `ProgramaGeneralController`.
- Datos y mutaciones: `GeneralApiController`.
- Interacción: `public/js/modules/programa_general/hot.js`, unas 3.800 líneas.
- Presentación: `public/css/programa-general.css` más adaptadores globales de Handsontable.
- Drawer: `LpsApiController`, `LpsService` y el módulo compartido de drawer contextual.

Una lectura real autorizada de un proyecto sembrado devolvió 324 filas, 42 de ellas capítulos, y
45 propiedades por fila. Los tipos llegan mezclados como números, strings numéricos y `null`. Este
volumen no justifica una nueva dependencia de grid; sí exige filtros eficientes y que los cálculos
derivados se memoricen.

La caracterización segura quedó verde antes de escribir esta spec:

- Vitest: 30/30;
- TypeScript: `tsc --noEmit`;
- contrato estático de Programa General;
- 27/27 casos puros de estado legacy/servicio;
- frontera SPA/PHP.

Durante el probe controlado, el HTML legacy devolvió `500` por un alias ambiguo de
`semanas_activas`, mientras lista, códigos y configuración de restricciones respondieron. El plan
de implementación deberá fijar primero un contrato reproducible para esa consulta; no puede usar
la disponibilidad accidental de la página PHP como única evidencia de paridad.

### Inventario HTTP auditado

| Método | Ruta | Contrato/uso actual | Disposición S05 |
|---|---|---|---|
| GET | `/programa-general` | `ProgramaGeneralController::index()` rinde VIEW-34 | piloto React en `/app/programa-general`; canónica React al corte; retirar VIEW-34 al cerrar el gate |
| POST | `/programa-general/filtros` | conteos y flags de filtro en sesión; recibe `db`, `semana` | no lo consume React; retirar con el legacy exclusivo |
| GET | `/programa-general/set-filtro` | muta flags de sesión por query y redirige | no lo consume React; retirar con el legacy exclusivo |
| GET/POST | `/api/general/list` | `{data:[fila...]}`; acepta `db`, `semana` y filtros legacy | React usa sólo GET con `semana`; PHP deriva proyecto y rechaza alcance discordante |
| GET | `/api/general/restriction-config` | catálogo por área; sólo exige sesión | conservar, extraer resolver único y exigir `lps.programa_general.ver` |
| GET | `/api/general/codigos` | `{success:true,data:[codigo...]}` | conservar con esquema Zod y permiso de lectura |
| POST | `/api/general/update` | formulario, CSRF `programa_general_save`, edición de una fila | conservar/adaptar; React omite `db`; endurecer fechas, semana confirmada y errores |
| POST | `/api/general/update-batch` | carryover y recálculo de la semana | conservar/adaptar; React omite `db` y body decorativo; sin reintento automático |
| GET/POST | `/reportes/corte-programacion` | genera XLSX y devuelve `{url}`; legacy usa POST con `db`, `semana` | React usa POST sin `db`; servidor toma proyecto/semana activos y valida URL de salida |
| GET | `/api/lps/comments` | `consecutivo`, `escalamiento_id?`; permiso semanal de lectura | conservar y tipar; resolver proyecto activo en ambas áreas |
| POST | `/api/lps/comments/add` | comentario/respuesta/menciones; CSRF drawer | conservar como ruta canónica |
| POST | `/api/lps/crisis/register` | registra alerta con `modulo=PG` | conservar como ruta canónica |
| POST | `/api/lps/crisis/close` | `alerta_id`, justificación mínima de 100 caracteres | conservar como ruta canónica |
| POST | `/api/lps/comments`, `/api/lps/crisis` | aliases de las dos mutaciones anteriores | S05 no los consume; T02 sólo podrá retirarlos tras auditar S07/S08 |
| GET | `/api/session` | bootstrap, navegación y semana compartidos | contrato propiedad de T01; S05 lo consume sin extender otra forma paralela |
| POST | `/context/week` | hoy sólo escribe un número en sesión | T01 lo protege y valida; S05 lo consume, no crea un endpoint duplicado |
| GET | `/bi/programa-general` | destino contextual del botón BI | sólo enlace autorizado; BI no se migra en S05 |

`/api/general/import`, `/api/general/delete-update`, `/api/general/auto-associate`,
`/api/general/decision-log`, los breadcrumbs `/api/pg/*` y `/programa-general-actualizar` pertenecen a
S06. Compartir controlador no los convierte en alcance de S05.

### Permisos y contradicciones observadas

| Perfil canónico | Ver PG | Editar PG | Editar semana pasada | Drawer lectura/escritura | Corte XLSX |
|---|---:|---:|---:|---:|---:|
| A, D | Sí | Sí | Sí | Sí / Sí | Sí |
| R, DCV | Sí | Sí | No | Sí / Sí | Sí |
| OT, G, S, SG, V | Sí | No | No | Sí / No | Sí |
| C | No | No | No | No / No | No |

La tabla refleja `RbacCatalog`, `RbacManager` y las guardas reales, no la visibilidad accidental de
la sidebar. Los overrides configurables de RBAC siguen siendo autoridad: el contexto entrega
acciones efectivas y React no reconstruye esta matriz.

El legacy presenta dos contradicciones que S05 cierra sin ampliar permisos: `hot.js` marca avance
real como editable incluso a perfiles que el endpoint rechaza, y permite a R/DCV intentar editar la
semana inmediatamente anterior aunque la guarda PHP exige A/D para toda semana menor a la máxima.
La superficie React usa las acciones del servidor y no ofrece una mutación destinada a fallar.

## Decisiones de arquitectura

### PG-R1 — React nativo, sin envolver Handsontable

La pantalla usa una tabla HTML semántica y tarjetas React. No monta jQuery, no ejecuta `hot.js`, no
usa un wrapper de Handsontable y no carga la piel CSS del vendor.

Tabla y tarjetas comparten:

- el mismo modelo de filas normalizado;
- las mismas funciones puras de búsqueda, filtros y realce;
- el mismo estado de edición por actividad;
- las mismas validaciones;
- las mismas acciones de API y feedback.

No se crea una abstracción universal de grid en esta primera migración. Los componentes nacen bajo
`frontend/src/modules/programa-general/` y sólo se extraen al design system cuando exista un segundo
consumidor React con el mismo contrato.

### PG-R2 — Migración estranguladora y rollback por frontera de ruta

Durante construcción, React vive en `/app/programa-general` y `/programa-general` continúa en PHP.
La misma página React debe aceptar ambos pathnames para no bifurcar componentes ni estado.

El corte final agrega `/programa-general` a `SpaRouter::RUTAS_MIGRADAS`; la definición legacy en el
router PHP permanece detrás de esa frontera. El rollback consiste en retirar ese prefijo y volver a
servir el PHP. No se crea `/legacy/programa-general` ni otra puerta pública paralela.

VIEW-34, `public/js/modules/programa_general/hot.js`, `public/css/programa-general.css` y los dos
endpoints de filtros de sesión sobreviven durante el piloto. Después de que la ruta canónica supere
la matriz funcional, responsive, temas, RBAC, accesibilidad y rollback, el mismo cierre elimina esas
piezas si el inventario de referencias confirma que son exclusivas. Los controladores y servicios
compartidos se conservan. El rollback posterior es revertir el commit de corte, no mantener dos
interfaces activas indefinidamente.

### PG-R3 — El servidor entrega acciones, React no interpreta roles

El frontend no conserva `ocultasPorRol`, no compara `A`, `D`, `R`, `V` ni deduce permisos desde el
nombre de una capacidad. PHP resuelve toda acción con `RbacService::can()`, rol normalizado mediante
`RbacService::normalizeRole()` y las capacidades específicas de `RbacManager`.

`GET /api/session`, propiedad de T01, incluye Programa General dentro de
`navigation.groups[].items[]` sólo cuando la capacidad efectiva permite abrirlo:

```json
{
  "navigation": {
    "groups": [{
      "id": "obra",
      "label": "Obra",
      "items": [{
        "id": "programa-general",
        "label": "Programa General",
        "href": "/programa-general",
        "icon": "calendar"
      }]
    }]
  }
}
```

Un destino no autorizado no viaja. Esto corrige la divergencia actual: G, S y SG tienen permiso de
lectura pero el blacklist React les oculta el módulo; C no tiene permiso y no debe ver ni cargar la
superficie. S05 no agrega una propiedad `navigation.programaGeneral` ni otra sidebar local.

Las acciones del contexto de Programa General son específicas de la semana seleccionada:

```text
editPlanFields  código, fechas, unidad y cantidad
editProgress    avance real
runBatch        actualizar ejecución/carryover y recalcular estados
downloadCut     generar corte XLSX
readDrawer      abrir diagnóstico y comentarios
writeDrawer     comentar, escalar y cerrar crisis
```

Reglas v0:

| Contexto efectivo | `editPlanFields` | `editProgress` | `runBatch` |
|---|---:|---:|---:|
| Sin `lps.programa_general.editar` | No | No | No |
| Semana máxima, sin confirmar, A/D/R/DCV | Sí | Sí | Sí |
| Semana máxima, confirmada, A/D/R/DCV | No | Sí | Sí |
| Semana pasada, sin confirmar, A/D | Sí | Sí | Sí |
| Semana pasada, confirmada, A/D | No | Sí | Sí |
| Semana pasada, R/DCV | No | No | No |

`downloadCut` depende únicamente de `lps.reportes.generar`. `readDrawer` y `writeDrawer` dependen de
`lps.programacion_semanal.ver/editar`, no de edición de Programa General. Confirmar la semana no
concede permisos; sólo cambia qué campos permite una capacidad ya existente.

### PG-R4 — El cliente nunca envía alcance de base de datos

React no envía `db`, prefijos de tabla ni `project_id` para concederse alcance. Los controladores
derivan el proyecto de la sesión y del `ProjectScope` ya activo. Una discrepancia falla cerrada.

Los endpoints legacy pueden conservar temporalmente el parámetro `db` para el PHP de rollback, pero
el adaptador React lo omite. La generación de corte también usa el proyecto y semana activos; el
cliente sólo solicita la operación.

Esta regla no reabre RLS ni autoriza cambios en su implementación. Sólo impide que la superficie
nueva dependa de identificadores de alcance suministrados por el navegador.

## Contratos backend

### Contexto de Programa General

Nuevo endpoint:

```text
GET /api/programa-general/context
Permiso: lps.programa_general.ver
Cache-Control: no-store
```

Respuesta de éxito:

```json
{
  "success": true,
  "data": {
    "project": {
      "id": 73,
      "name": "Proyecto",
      "area": "Construccion"
    },
    "week": {
      "number": 18,
      "max": 20,
      "confirmed": false
    },
    "actions": {
      "editPlanFields": true,
      "editProgress": true,
      "runBatch": true,
      "downloadCut": true,
      "readDrawer": true,
      "writeDrawer": true
    },
    "csrf": {
      "programaGeneral": "0000000000000000000000000000000000000000000000000000000000000000",
      "drawer": "0000000000000000000000000000000000000000000000000000000000000000"
    },
    "restrictionConfig": {
      "area": "Construccion",
      "restrictions": [],
      "hardRestrictions": [],
      "softRestrictions": []
    },
    "links": {
      "bi": "/bi/programa-general"
    }
  }
}
```

Los ceros anteriores son datos documentales no operativos. Los tokens reales conservan el formato
y la longitud de `CsrfTokenManager` y nunca aparecen en fixture, log o snapshot.

`project` repite el identificador público del bootstrap sólo para detectar respuestas obsoletas tras
un cambio de proyecto; no autoriza consultas. `area` admite exclusivamente `Construccion` y
`Pre-Construccion`. Si no existe semana seleccionable, `number=0`, `max=0`, todas las acciones de
mutación son `false` y `links.bi` es el destino autorizado o `null`. Las opciones y fechas de semana,
el CSRF del shell y la navegación siguen viniendo de T01; S05 no los duplica.

La configuración de restricciones se obtiene de un único resolver compartido. La vista PHP, el
endpoint legacy `/api/general/restriction-config` y el contexto React deben producir el mismo
catálogo. El endpoint legacy añade el permiso de lectura que hoy le falta.

El catálogo canónico conserva umbrales y opciones observadas en ambos contratos actuales:

- Construcción: `D_y_E`, `Materiales`, `MdeO` y `Equipos` son duras con umbral 100 y opciones
  0/33/66/100/N/A; `Predecesora` es dura con umbral 50 y opciones 0/50/100/N/A;
  `Pdto_Cons` y `Modelo` son blandas con umbral 100 y opciones 0/50/100/N/A.
- Preconstrucción: `restriccion_pc_1` es dura, se etiqueta `Predecesora`, tiene umbral 50 y ofrece
  exactamente 0/50/100/N/A; `restriccion_pc_2..4` sólo aparecen cuando el proyecto les asigna
  nombre, son blandas con umbral 100 y opciones 0/50/100/N/A. La inyección de VIEW-34 que todavía
  ofrece 33/66 para `restriccion_pc_1` es la copia divergente que elimina el resolver compartido.

Los umbrales de la respuesta se expresan como porcentajes; `RestrictionConfigResolver` puede seguir
usando ratios 0.5/1.0 internamente siempre que el adaptador no mezcle ambas escalas.

### Selección de semana

S05 consume el endpoint compartido definido por T01:

```text
POST /context/week
Content-Type: application/json
Header: X-CSRF-Token
Body: { "semana": 18 }
```

Precondiciones:

1. sesión y `ProjectScope` válidos;
2. permiso de lectura del destino activo;
3. CSRF válido del shell;
4. entero positivo;
5. la semana existe en `semanas_activas` del proyecto activo.

Una entrada inválida responde `422`; una semana que no pertenece al proyecto responde `404`; la
sesión no cambia en ninguno de los dos casos. El éxito devuelve el bootstrap/contexto T01
actualizado. React descarta filas, códigos sensibles a proyecto, borradores y drawer anteriores,
consulta el contexto S05 y carga la lista de la nueva semana. No actualiza parcialmente acciones ni
fechas desde suposiciones locales.

En un deep link `?semana=<n>`, React compara el valor con `week.options` del bootstrap. Si es válido
y distinto, usa una sola vez `/context/week`; si es inválido, lo elimina de la URL sin mutar sesión.
Atrás/adelante repiten la misma regla y una respuesta obsoleta se descarta con cancelación de lectura.

### Lista

React reutiliza:

```text
GET /api/general/list?semana=<n>
Permiso: lps.programa_general.ver
```

No envía `db`, banderas de filtros de sesión ni `filter=unmapped`. La respuesta legacy `{data: []}`
se valida y se adapta a un modelo de dominio. El esquema acepta los tipos reales observados y
normaliza en la frontera; los componentes nunca reciben strings numéricos ambiguos.

Campos mínimos que el adaptador conserva:

```text
unique_id, Id, Semana, Actividad, Titulo,
Fecha_Inicio, Fecha_Fin, Ruta_Critica,
Ejecutado, Ejecutado_Teorico, Estado, Semanas_Inicio,
Estado_Restricciones, codigo_actividad, unidad, cantidad_ppto,
Sub_Contratista, Responsable_AIA, alerta_crisis,
Telefono, telefono_subcontratista, Correo, correo_responsable,
nivel_actual, escalamiento_id, alerta_id, modulo,
D_y_E, Materiales, MdeO, Equipos, Predecesora, Pdto_Cons, Modelo,
restriccion_pc_1, restriccion_pc_2, restriccion_pc_3, restriccion_pc_4
```

Los campos extra de `SELECT *` se descartan al crear el modelo de dominio. `project_id`, prefijos y
metadatos internos no se propagan a componentes. Reducir el `SELECT *` queda permitido sólo si la
prueba de contrato demuestra que el PHP de rollback no pierde campos.

### Códigos

React reutiliza `GET /api/general/codigos` con permiso de lectura. El contrato validado es:

```json
{
  "success": true,
  "data": [
    {
      "codigo_actividad": "COD-01",
      "actividad": "Actividad",
      "unidad": "m2"
    }
  ]
}
```

La lista se carga en paralelo con las filas y se conserva mientras no cambie el proyecto. Un fallo
de códigos no oculta la tabla: deshabilita el selector de código, muestra error contextual y ofrece
reintento.

### Guardado individual

React reutiliza:

```text
POST /api/general/update?semana=<n>
Content-Type: application/x-www-form-urlencoded
Header: X-CSRF-Token
Permiso: lps.programa_general.editar
```

Payload canónico:

```text
unique_id
Consecutivo_en_Programa
Id
Ejecutado
EjecutadoRatio
codigo_actividad
unidad
cantidad_ppto
Fecha_Inicio
Fecha_Fin
```

React no envía `db`. `unique_id` y `Consecutivo_en_Programa` llevan el mismo identificador por
compatibilidad temporal. La respuesta exitosa legacy se valida al menos con:

```json
{
  "respuesta": "BIEN",
  "estado": "En Curso",
  "Semanas_Inicio": 2,
  "unidad": "m2",
  "cantidad_ppto": 100.0,
  "Ejecutado": 0.25
}
```

El endpoint conserva el servidor como autoridad: React sustituye los valores confirmados con la
respuesta y no recalcula `Estado` ni `Semanas_Inicio`.

La confirmación semanal también se aplica en PHP, no sólo en la interfaz. Antes de actualizar, el
servidor obtiene la fila y la semana. Si está confirmada, compara código, fechas, unidad y cantidad
normalizados contra lo persistido: una diferencia se rechaza con `409 PG_WEEK_CONFIRMED`. Una
petición que sólo cambia avance usa los valores de planificación persistidos, aunque el cliente
mande sus copias por compatibilidad con el payload legacy. Así el avance sigue permitido a un
editor, pero no se puede alterar planificación modificando manualmente la petición.

El mismo guard se aplica al endpoint legacy y queda caracterizado antes de cambiarlo. No se añade
un flag aportado por React para decidir si una semana está confirmada. El guard pertenece al modo
Programa General identificado por `semana`; el contrato `semana_objetivo` que consume Actualizar
Cronograma no cambia en este frente y debe conservar sus pruebas de regresión.

### Actualización por lote

React reutiliza:

```text
POST /api/general/update-batch?semana=<n>
Header: X-CSRF-Token
Permiso: lps.programa_general.editar
```

El body legacy `opcion`, `Id1`, `Ejecutado` e `inicio_semana` no gobierna la operación y React no lo
envía. El servidor usa contexto y semana validados. La respuesta es:

```json
{
  "respuesta": "BIEN",
  "actualizadas": 324,
  "carryover_actualizadas": 12
}
```

Después del éxito o de un error recuperable, React vuelve a consultar la lista. No reintenta la
mutación automáticamente.

### CSV y corte XLSX

El CSV se genera en el navegador a partir del conjunto visible, con:

- las trece columnas contractuales en su orden;
- capítulos incluidos como filas estructurales;
- escaping RFC 4180;
- UTF-8 con BOM para apertura predecible en Excel;
- nombre `programa_general.csv`.

El corte usa `POST /reportes/corte-programacion` sin body de alcance y con el CSRF de Programa
General. El servidor toma proyecto y semana de la sesión/`ProjectScope`; no consulta por un `db`
aportado por navegador. La respuesta válida es estrictamente `{url:string}` con una ruta relativa
del mismo origen bajo `/public/storage/cortesProgramacion/` y sufijo `.xlsx`. `{error:string}` se
adapta como fallo de dominio; cualquier forma o URL distinta se rechaza en Zod antes de navegar.

El endpoint conserva `lps.reportes.generar`; esta spec no amplía permisos ni cambia la generación
de las hojas `Corte Programacion` y `ASSEMBLE`. El GET genérico continúa sólo por compatibilidad de
otros reportes: React no lo usa. La prueba contractual inyecta un generador fake y no escribe en
`public/storage`.

### Drawer

Se conservan los endpoints actuales:

```text
GET  /api/lps/comments?consecutivo=<id>&escalamiento_id=<id?>
POST /api/lps/comments/add
POST /api/lps/crisis/register
POST /api/lps/crisis/close
```

Las mutaciones siguen siendo `application/x-www-form-urlencoded` y usan el CSRF `lps_drawer`.
React valida tanto `HTTP 2xx` como el campo de dominio `respuesta`, porque los controladores legacy
pueden responder `200` con `respuesta="ERROR"`.

Payloads canónicos:

| Acción | Campos enviados por React | Respuesta de éxito mínima |
|---|---|---|
| cargar | query `consecutivo:int positivo`, `escalamiento_id?:int positivo` | `{respuesta:"OK",data:Comentario[]}` |
| comentar/responder | `consecutivo`, `comentario`, `parent_id?`, `escalamiento_id?`, `menciones?` como JSON | `{respuesta:"OK",comment_id:int positivo}` |
| registrar crisis/SOS | `consecutivo`, `modulo:"PG"`, `trigger` en `MANUAL`, `SOS-RES`, `SOS-DIR`, `SOS-COO`, `SOS-GER` | `{respuesta:"OK",mensaje:string}` |
| cerrar crisis | `alerta_id`, `justificacion` | `{respuesta:"OK",mensaje:string}` |

Comentario vacío, actividad no positiva, JSON de menciones inválido, alerta no positiva y cierre de
menos de 100 caracteres fallan antes del servicio. React no envía proyecto, semana, usuario ni
prefijo: el controlador los resuelve del contexto activo.

El contexto del drawer deja de buscar exclusivamente `Area='Construccion'`. Debe resolver el mismo
proyecto activo para Construcción y Preconstrucción, sin aceptar un ID aportado por el cliente.

Cada comentario normalizado conserva `id`, `unique_id`, `consecutivo_en_programa`, `semana`,
`usuario_id`, `comentario`, `escalamiento_id`, `parent_id`, `menciones`, `created_at`,
`autor_nombre`, `autor_cargo` y `respuestas`. Las respuestas usan el mismo esquema recursivo. Los
campos de alcance que todavía devuelva `SELECT c.*` se validan pero no se usan para autorizar ni se
presentan al usuario.

### Errores nuevos

Los endpoints nuevos usan una forma estable:

```json
{
  "success": false,
  "error": {
    "code": "PG_WEEK_INVALID",
    "message": "La semana seleccionada no es válida.",
    "fields": {
      "semana": "Selecciona una semana activa del proyecto."
    },
    "correlationId": null
  }
}
```

`fields` es opcional. `correlationId` puede ser `null` si la infraestructura vigente no lo genera.
No se exponen SQL, prefijos, rutas internas, stack traces ni datos de otro proyecto.

Los adaptadores de endpoints legacy aceptan temporalmente sus formas existentes y las convierten a
errores tipados del frontend. Esta spec no exige una migración transversal de todos los errores
PHP.

## Frontera HTTP de React

`frontend/src/lib/api/cliente.ts` sigue siendo el único archivo que llama `fetch` y se amplía sin
romper los consumidores actuales:

- JSON: serializa y declara `application/json` sólo cuando corresponde;
- formulario: admite `URLSearchParams` y deja que el navegador declare
  `application/x-www-form-urlencoded`;
- `FormData`: nunca fija manualmente el boundary;
- añade `Accept: application/json` y `credentials: same-origin`;
- recibe `AbortSignal` para cancelar lecturas obsoletas;
- parsea el body de error cuando sea JSON;
- diferencia transporte, HTTP, contrato Zod y error de dominio;
- nunca repite mutaciones por su cuenta.

Los módulos usan funciones de dominio como `obtenerContextoPrograma()`, `listarPrograma()`,
`guardarActividad()` o `actualizarEjecucion()`. Los componentes no conocen URLs, headers ni formas
legacy.

Cada endpoint nuevo tiene, antes de implementación, una prueba PHP de contrato fallando y un
esquema Zod `.strict()` fallando. Cada endpoint legacy consumido por React obtiene esquema Zod
estricto en objetos propios y fixture de su forma real antes de conectar componentes. Sólo el
adaptador de fila puede aceptar el `SELECT *` legacy y descarta explícitamente las claves ajenas al
modelo; esa tolerancia no llega a componentes.

## Modelo de dominio frontend

El adaptador transforma cada fila legacy en un modelo con tipos estables:

```text
ProgramaGeneralActividad
  uniqueId: number
  id: string
  week: number
  activity: string
  isChapter: boolean
  startDate: string | null
  endDate: string | null
  criticalPath: boolean
  progressRatio: number | null
  theoreticalProgress: number | null
  state: ProgramaGeneralState
  weeksToStart: number | null
  restrictionRelease: number | null
  activityCode: string
  unit: ProgramaGeneralUnit
  budgetQuantity: number | null
  subcontractor: string | null
  responsible: string | null
  crisisAlert: CrisisAlert | null
  phone: string | null
  email: string | null
  escalationLevel: number | null
  escalationId: number | null
  alertId: number | null
  restrictions: Record<RestrictionKey, number | null>
```

La lista de unidades v0 es la del legacy: `ml`, `m2`, `m3`, `un`, `gl`, `kg`, `%` y `Niveles`.
Vacío es un alias de entrada legacy que el adaptador normaliza a `%`, no una novena unidad visible.
Un valor desconocido recibido del servidor se conserva como texto de solo lectura y no se sustituye
silenciosamente.

Las fechas permanecen como ISO `YYYY-MM-DD`; no se convierten a `Date` para evitar cambios por zona
horaria. Porcentajes internos son ratios `0..1`; la UI convierte a porcentaje o cantidad física
según unidad y presupuesto.

## Estados, alertas y leyenda

PHP continúa siendo la fuente de `Estado`. React no reproduce `pg_calculate_status()` ni
`LpsService::calculateGeneralStatus()`.

La presentación usa el contrato canónico de `docs/design-system/state-semantics.json`:

| Estado | Nivel | Matiz | Significado v0 |
|---|---|---|---|
| Actividad Futura | healthy | green | Planificada dentro del horizonte y sin incumplimiento |
| En Curso | healthy | blue | Tiene avance y no está atrasada |
| Terminada | healthy | neutral | Avance completo |
| Fuera de Ventana | neutral | teal | Inicia a siete o más semanas |
| Debe Iniciar | attention | orange | Debe comenzar en la semana y no tiene avance |
| Atrasada | urgent | red | Debió iniciar o su avance real está por debajo del teórico |
| Sin Datos | neutral | violet | No tiene fechas ni avance suficiente para clasificar |
| Capítulo | estructural | neutral | Agrupador no editable |

Aliases legacy conocidos se normalizan sólo para presentación. El valor original no se reescribe
en base por abrir la pantalla.

La alerta de restricciones es un realce secundario, nunca un octavo estado. Se muestra cuando una
actividad incompleta tiene una restricción dura bajo su umbral y está en la ventana de seis semanas:

- R0: inicio inmediato o vencido;
- R1: inicio en una semana;
- R2–3: inicio en dos o tres semanas;
- R4–6: inicio entre cuatro y seis semanas.

La leyenda explica estados, niveles, acciones operativas y las cuatro alertas. En Preconstrucción
adapta el copy (`Por Iniciar`, `En Ejecución`, `Completada`, `Con Restricción Pendiente`) sin cambiar
las claves internas ni crear otro catálogo visual.

## Búsqueda, filtros y conteos

### Búsqueda global

Un campo visible busca sin distinguir mayúsculas, minúsculas ni acentos sobre:

- código;
- actividad;
- estado;
- responsable AIA;
- subcontratista.

La búsqueda se aplica después de normalizar texto y antes de los filtros de estado. No consulta al
servidor mientras el conjunto completo de la semana ya esté cargado.

### Filtros estructurados

La tabla ofrece controles de cabecera para sus columnas operativas. Como mínimo:

- texto: Id, código y actividad;
- fecha: inicio y fin;
- rango o valor: semanas para inicio, cantidad, teórico, real y liberación;
- enum: ruta crítica, unidad y estado.

En tablet, los filtros de columnas ocultas viven en el panel de filtros. En móvil, todos los
filtros estructurados viven en ese panel y las tarjetas sólo muestran el resumen activo.

Los ocho chips visibles son:

1. con alerta de restricciones;
2. debe iniciar;
3. actividad futura;
4. en curso;
5. atrasada;
6. terminada;
7. fuera de ventana;
8. sin datos.

Clic, Enter o Espacio seleccionan un único chip o lo limpian si era el único activo. Ctrl/Cmd más
clic, Enter o Espacio agrega o retira un chip de una combinación, conservando la interacción
legacy.

### Conteos facetados

Los conteos consideran sólo actividades, no capítulos. Se calculan sobre el resultado de búsqueda
y filtros estructurados **antes** de aplicar los chips de estado. Así cada chip conserva el número
de filas que produciría y no cae artificialmente a cero por haber elegido otro.

El conjunto visible se obtiene en este orden:

```text
filas normalizadas
  → búsqueda global
  → filtros de columna
  → conteos facetados
  → chips de estado/alerta
  → tabla o tarjetas
```

### Persistencia

La semana y los filtros se representan en query parameters de la ruta. Las claves canónicas son:

```text
semana, q, estado, semInicio, critica y col.<nombre>
```

`estado` puede repetirse para multiselección. Los valores inválidos se ignoran y se limpian de la
URL sin emitir una petición de mutación. Atrás, adelante y recarga reconstruyen la misma vista.

Los endpoints `/programa-general/filtros` y `/programa-general/set-filtro` no se consumen desde
React; permanecen únicamente para rollback legacy.

## Responsive y composición

### Desktop — 1180 px o más

La tabla muestra las trece columnas contractuales:

```text
Id, Código, Actividad, Sem. inicio, Fecha inicio, Fecha fin, Crítica,
Unidad, Cantidad PPTO, Ejecución teórica, Ejecución real, Estado, Lib. restricciones
```

El header es sticky dentro del scroll vertical del contenido. No se fija la primera columna si eso
crea una segunda superficie de scroll. La página no tiene overflow horizontal; la tabla distribuye
anchos con Actividad como columna flexible.

### Tablet — 768 a 1179 px

Sigue siendo tabla. Muestra inicialmente:

```text
Código, Actividad, Fecha inicio, Fecha fin, Unidad,
Cantidad PPTO, Ejecución real, Estado
```

Cada fila tiene un control accesible de detalles que revela Id, semanas para inicio, ruta crítica,
ejecución teórica y liberación de restricciones. Esos detalles forman parte de la misma fila y no
abren un modal. No hay scroll horizontal de página.

### Móvil — menos de 768 px

Cada actividad es una tarjeta editable con:

- actividad, código, estado y alerta;
- Id, semanas para inicio, ruta crítica y liberación de restricciones;
- fechas, unidad, cantidad y avance real;
- feedback de guardado y acciones contextuales.

Los capítulos se muestran como separadores no editables; no se omiten. Un conjunto compuesto sólo
por capítulos explica por qué no hay actividades operativas.

Tabla y tarjetas no se renderizan simultáneamente. El breakpoint se resuelve con CSS y un hook de
media query testeable; no se construye Handsontable oculto detrás de las tarjetas.

## Edición individual

### Campos

- `codigo_actividad`: selector a partir del catálogo; string vacío permitido.
- `Fecha_Inicio` y `Fecha_Fin`: inputs de fecha ISO.
- `unidad`: selector del catálogo legacy.
- `cantidad_ppto`: número con un decimal; deshabilitado para `%`.
- `EjecutadoDisplay`: porcentaje o cantidad física según contexto.

`Actividad`, Id, estado, avance teórico, semana de inicio, ruta crítica y restricciones son de solo
lectura en v0.

### Validación

Antes de pedir guardado, cliente y servidor aplican el mismo contrato:

1. actividad no capítulo;
2. identificador estable y semana válidos;
3. fechas presentes, ISO reales y `Fecha_Inicio <= Fecha_Fin`;
4. cantidad vacía o no negativa, redondeada a un decimal; cero se normaliza a `null`;
5. unidad `%` implica `cantidad_ppto=null`;
6. avance no negativo;
7. con `%` o sin cantidad, avance visible `0..100`;
8. con unidad física y cantidad positiva, avance visible `0..cantidad_ppto`;
9. el ratio canónico se redondea a seis decimales y queda entre `0..1`.

Cambiar una unidad física con cantidad a `%` abre una confirmación que explica que la cantidad se
eliminará y el ratio de avance se preservará. Cancelar restaura unidad y cantidad sin petición.

Una fila sin fechas no permite editar avance real hasta tener ambas fechas válidas. Esto corrige la
interacción legacy que presenta avance editable pero luego no puede construir un payload válido.

### Commit y feedback

Un campo se confirma por Enter, selección explícita o blur. El adaptador deduplica el mismo valor y
mantiene una sola mutación activa por fila. Mientras guarda:

- la fila muestra `Guardando…` en una región `aria-live`;
- el campo en vuelo no vuelve a emitir otra petición;
- otras filas pueden editarse;
- cambiar de semana o proyecto solicita confirmación si hay una edición sin enviar.

Éxito sustituye la fila con los valores confirmados por servidor y anuncia `Guardado`. Error de
validación conserva el valor editable, muestra mensaje junto al campo y no pierde foco. Error de
red conserva el borrador y ofrece `Reintentar` o `Descartar`.

No hay actualización optimista de `Estado`. Mientras la petición está pendiente se conserva el
estado anterior acompañado por el feedback de guardado.

## Actualización por lote y retorno al módulo

`Actualizar ejecución` aparece sólo con `runBatch=true`. Al activarlo:

1. pide confirmación si existe cualquier borrador sin guardar;
2. bloquea únicamente las acciones de mutación del módulo;
3. llama una sola vez a `update-batch`;
4. anuncia cuántas filas y carryovers se actualizaron;
5. vuelve a cargar contexto y lista;
6. restaura filtros y posición cuando la fila todavía existe.

Para paridad v0 se conserva la actualización automática al regresar:

- la primera entrada a una combinación proyecto/semana no ejecuta lote;
- salir de Programa General marca esa combinación;
- volver a la misma combinación ejecuta exactamente un lote si `runBatch=true`;
- un reload de la misma página no cuenta como salida a otro módulo;
- cambiar de proyecto o semana no hereda la marca anterior;
- un rol sin permiso nunca emite la petición;
- la operación deja feedback visible; no es silenciosa.

La marca vive en `sessionStorage` con proyecto y semana, no en base de datos. Un error no crea un
loop de reintentos automáticos.

## Drawer contextual

Una fila o tarjeta seleccionada alimenta un modelo pequeño de contexto:

```text
uniqueId, id visible, actividad, estado, alerta, semana y permisos de drawer
```

Los capítulos muestran un estado neutral y ocultan acciones de actividad. Si la fila seleccionada
desaparece por filtros, el drawer conserva el contexto con aviso `Oculta por los filtros`; si la
semana, proyecto o dataset cambian, la selección se limpia.

Capacidades v0:

- diagnóstico y trazabilidad de la actividad;
- comentarios raíz y respuestas;
- menciones por roles admitidos por el contrato actual;
- SOS por WhatsApp, correo o portapapeles según disponibilidad;
- registro manual de crisis con `modulo=PG`;
- cierre de crisis con justificación mínima de 100 caracteres;
- resumen semanal cuando corresponda.

SOS conserva el comportamiento observable: registra el trigger `SOS-*` antes de abrir WhatsApp o
correo; si falta el contacto, copia el texto y lo explica. Con `lps_simulated_mode=true` sólo copia y
no registra crisis. El resumen semanal se calcula localmente sobre el dataset autorizado visible al
módulo y se copia; no crea un endpoint ni envía filas a un tercero.

El drawer conserva:

- `Escape` para cerrar;
- focus trap;
- devolución de foco al disparador;
- contenido de fondo inerte;
- layout que desplaza el contenido en desktop cuando el contrato compartido lo indique;
- overlay en móvil/tablet;
- estados de carga, vacío, error y reintento por sección.

El selector de fila y las acciones del drawer son accesibles por teclado; hover nunca es el único
modo de descubrir una acción.

## Estados de pantalla y recuperación

Programa General distingue:

1. contexto inicial cargando;
2. lista cargando;
3. recarga con datos previos visibles;
4. proyecto sin semanas;
5. semana sin filas;
6. conjunto con sólo capítulos;
7. filtros sin coincidencias;
8. permiso denegado;
9. sesión vencida;
10. error de contexto;
11. error de lista;
12. error parcial de códigos o drawer;
13. guardado en curso, exitoso o fallido;
14. lote en curso, exitoso o fallido.

Proyecto sin semanas muestra una explicación y enlaza `Actualizar Cronograma` sólo si la navegación
server-side lo permite. No redirige de forma automática y opaca.

Una recarga manual cancela la lectura anterior mediante `AbortController`, conserva semana y
filtros, y no borra datos útiles hasta recibir la nueva respuesta. Un error de recarga deja la
última vista con aviso de desactualización.

`401` limpia sesión React y vuelve al acceso. `403` conserva el shell y presenta la denegación.
`404` diferencia semana/fila ausente de una ruta inexistente. `422` muestra errores de campo.
`5xx` presenta mensaje seguro y correlación cuando exista.

## Sistema visual y accesibilidad

La pantalla consume `public/css/tokens.css` y las primitivas canónicas documentadas en `DESIGN.md`.
Su CSS queda contenido bajo una raíz de módulo, sin hex locales, estilos inline, `!important` ni
copias de selectores Handsontable.

Se conservan:

- Montserrat en títulos y marca;
- Inter en controles y datos;
- tema oscuro como valor por defecto/fallback y tema claro completo, ambos con la misma capacidad;
- foco visible;
- targets táctiles mínimos;
- estado principal mediante chip y realce de fila moderado;
- rail/flag para atención y urgencia según los tokens canónicos;
- iconografía acompañada por texto o nombre accesible;
- `prefers-reduced-motion`.

Requisitos de accesibilidad:

- tabla con `caption`, cabeceras asociadas y controles de edición nombrados;
- filas expandibles con `aria-expanded` y destino identificado;
- chips con `aria-pressed`;
- conteos y resultados anunciados sin spam;
- errores de campo conectados con `aria-describedby`;
- modales/confirmaciones con foco contenido;
- tarjetas con heading y grupos de campos;
- navegación completa por teclado;
- axe sin violaciones críticas o serias en los escenarios contractuales.

Los viewports obligatorios son 390×844, 768×1024, 1180×820 y 1440×900 en claro y oscuro. No se
acepta overflow horizontal de página. Los goldens sólo se crean o actualizan con aprobación
explícita; nunca para forzar un gate verde.

## Estructura de módulos

La organización esperada es:

```text
frontend/src/
  lib/api/
    cliente.ts
    esquemas/programa-general.ts
  modules/programa-general/
    ProgramaGeneralPage.tsx
    api/programaGeneralApi.ts
    domain/modelo.ts
    domain/validacion.ts
    domain/filtros.ts
    domain/presentacionEstados.ts
    components/ProgramaToolbar.tsx
    components/ProgramaFilters.tsx
    components/ProgramaTable.tsx
    components/ProgramaCards.tsx
    components/ProgramaEditor.tsx
    components/ProgramaLegend.tsx
    components/ProgramaDrawer.tsx
    programa-general.css
```

Los nombres definitivos pueden ajustarse en el plan si preservan estas fronteras:

- `api`: URLs, transporte y adaptación legacy;
- `domain`: tipos y funciones puras sin React;
- `components`: presentación e interacción;
- `ProgramaGeneralPage`: orquestación de contexto, lecturas, selección y estados de pantalla.

En PHP se espera un controlador y servicio cohesivos de contexto, reutilización del resolver de
restricciones y ajustes mínimos a controladores existentes. El plan deberá nombrar archivos y
firmas después de comprobar las convenciones exactas del repo; no se crean capas genéricas sin un
segundo consumidor.

## Pruebas y evidencia

### Contratos obligatorios

- Contrato PHP de `GET /api/programa-general/context`.
- Integración S05 con `POST /context/week` y navegación PG de `/api/session`, cuyos contratos base
  pertenecen a T01; no se crea otro endpoint de semana.
- Contrato puro del resolver de restricciones y sus dos áreas.
- Contratos seguros de lista, códigos, update, batch, corte y drawer con dependencias fake donde
  haya mutación o escritura de archivo.
- Esquema Zod estricto por cada endpoint consumido.
- Fixtures de Construcción y Preconstrucción.
- Matriz A/D/R/DCV/OT/G/S/SG/V/C y acciones efectivas por semana/confirmación.
- Pruebas puras de normalización de tipos, unidades, ratios, fechas, filtros, conteos y alertas.
- Pruebas de componentes para tabla, tarjetas, permisos, edición y recuperación.
- Pruebas de integración del cliente para JSON, URL-encoded, `FormData`, CSRF y errores.
- Contrato method-aware de `SpaRouter` para no capturar `/api/*` ni POST ajenos.

### Escenarios de navegador

- carga autorizada y denegada;
- selección de semana y cambio de proyecto;
- búsqueda, filtros de columna, chips únicos/múltiples y limpieza;
- tabla desktop/tablet y tarjetas móvil;
- edición exitosa, validación y error recuperable;
- lote manual y retorno con lote único;
- CSV y corte XLSX;
- drawer de lectura y escritura;
- Construcción y Preconstrucción;
- claro/oscuro en cuatro viewports, teclado, axe, consola, red y overflow.

### Prohibición de DML en este frente

Mientras no exista autorización posterior, esta spec exige que la implementación se verifique con:

- pruebas puras;
- pruebas frontend con cliente simulado;
- contratos PHP sin escritura;
- fixtures estáticos;
- navegador en flujos de lectura.

No se ejecutan las pruebas E2E que escriben en la base compartida. Una validación real de
`update`, `update-batch`, comentarios o crisis necesitará autorización separada para una base
descartable y aislada. Hasta entonces se reportará como límite, no se maquillará con una afirmación
de E2E completo.

La selección de proyecto de la auditoría previa registró automáticamente un evento
`ACCESO_PROYECTO`; esa incidencia queda documentada y no se borra. La implementación no repetirá
probes de sesión innecesarios.

## Requisitos UX trazables

- S05-UX-01: Programa General vive dentro del shell T01 con proyecto, semana, cuenta, sidebar y
  tema coherentes; no crea otro encabezado global ni otro selector de proyecto.
- S05-UX-02: La carga inicial mantiene el shell estable, muestra skeleton/texto y anuncia una sola
  vez que se está cargando la semana.
- S05-UX-03: Proyecto, área, semana, rango de fechas y confirmación son visibles sin exponer prefijo
  de base de datos ni exigir interpretar códigos de rol.
- S05-UX-04: Cambiar semana conserva la ruta, invalida datos/borradores anteriores y muestra sólo el
  contexto autorizado de la nueva semana.
- S05-UX-05: Búsqueda global ignora caja y acentos; limpiar restaura el conjunto sin nueva petición.
- S05-UX-06: Filtros estructurados y chips pueden combinarse, reflejan su estado en URL y sobreviven
  recarga/atrás/adelante.
- S05-UX-07: Los ocho chips muestran conteos facetados de actividades, excluyen capítulos y admiten
  selección simple o múltiple por ratón y teclado.
- S05-UX-08: La leyenda explica estados, alertas, niveles, restricciones y acciones con copy acorde
  a Construcción o Preconstrucción.
- S05-UX-09: Desktop muestra tabla semántica de trece columnas con cabecera sticky y sin overflow de
  página.
- S05-UX-10: Tablet conserva tabla, ocho columnas primarias y detalles expandibles dentro de la fila.
- S05-UX-11: Móvil usa tarjetas editables, muestra capítulos como separadores y conserva todos los
  datos/acciones mediante resumen y detalles.
- S05-UX-12: Lectores, capítulos, semanas bloqueadas y campos bloqueados se distinguen con texto o
  ayuda contextual; nunca dependen sólo de color o de un control que falla al guardar.
- S05-UX-13: Editar código, fechas, unidad, cantidad o avance produce el mismo borrador y payload
  desde tabla y tarjetas.
- S05-UX-14: Fechas inválidas/invertidas, cantidades negativas y avances fuera del rango muestran
  error junto al campo, conservan foco y no emiten petición.
- S05-UX-15: Cambiar una unidad física a `%` explica la pérdida de cantidad, conserva el ratio y
  permite cancelar sin mutar.
- S05-UX-16: Cada fila comunica `Guardando…`, `Guardado` o error recuperable; un fallo conserva el
  borrador con `Reintentar` y `Descartar`.
- S05-UX-17: `Actualizar ejecución` confirma borradores, bloquea sólo mutaciones, informa filas y
  carryovers actualizados y recarga desde servidor.
- S05-UX-18: Regresar al mismo proyecto/semana dispara como máximo un lote autorizado; primera
  entrada, reload o perfil lector no lo disparan.
- S05-UX-19: Exportar CSV descarga exactamente el conjunto visible con capítulos, trece cabeceras y
  caracteres compatibles con Excel.
- S05-UX-20: Descargar corte muestra progreso, valida la respuesta antes de navegar y deja la tabla
  intacta si falla.
- S05-UX-21: Recargar conserva filtros y datos previos mientras pide una versión nueva; un error los
  marca como desactualizados y ofrece retry.
- S05-UX-22: Seleccionar una actividad abre el drawer con diagnóstico, comentarios, respuestas,
  menciones, SOS y crisis sólo según acciones efectivas.
- S05-UX-23: El drawer distingue carga, vacío, error y selección filtrada; cambiar dataset limpia la
  selección y capítulos no ofrecen acciones de actividad.
- S05-UX-24: Sin semanas, sin filas, sólo capítulos, filtros sin resultados, 401, 403, 404, 422 y 5xx
  tienen salidas distintas y accionables.
- S05-UX-25: Oscuro por defecto y claro completo conservan contenido, contraste, estados, edición,
  foco y feedback en 390, 768, 1180 y 1440 px.
- S05-UX-26: Teclado, zoom 200 %, targets de 44 px, anuncios, foco de modal/drawer y reduced motion
  conservan toda capacidad sin overflow horizontal.

## Criterios de aceptación trazables

- S05-AC-01: `/app/programa-general` y, tras el corte, `/programa-general` sirven el mismo componente
  React; GET/HEAD se capturan de forma method-aware y `/api/*` nunca recibe HTML SPA.
- S05-AC-02: T01 entrega el único ítem de sidebar `programa-general`; React no contiene blacklist ni
  comparación de roles para mostrar ruta o acción.
- S05-AC-03: A/D/R/DCV/OT/G/S/SG/V pueden leer según capacidad efectiva; C y cualquier perfil sin
  permiso obtienen denegación server-side y no reciben el ítem.
- S05-AC-04: Ninguna petición S05 usa `db`, prefijo, `project_id`, rol, área o confirmación aportados
  por el navegador para autorizar alcance.
- S05-AC-05: `GET /api/programa-general/context` tiene prueba contractual PHP, Zod estricto,
  `no-store`, proyecto/semana coherentes, acciones efectivas, tokens y enlaces seguros.
- S05-AC-06: S05 usa `POST /context/week` de T01; valor inexistente o inválido no cambia sesión y no
  existe `/api/programa-general/week` duplicado.
- S05-AC-07: `GET /api/general/list?semana=n` valida lectura/proyecto/semana, normaliza tipos mixtos y
  descarta claves de alcance antes del modelo de componentes.
- S05-AC-08: El resolver único produce las siete restricciones de Construcción y la Predecesora más
  cero a tres blandas de Preconstrucción con opciones/umbrales exactos auditados.
- S05-AC-09: Códigos cargan en paralelo, quedan cacheados sólo por proyecto y un fallo parcial
  deshabilita su selector sin ocultar filas.
- S05-AC-10: Búsqueda, filtros, conteos y chips aplican el pipeline documentado y reconstruyen estado
  válido desde query parameters.
- S05-AC-11: Desktop/tablet cumplen tabla y detalles; móvil cumple tarjetas editables y separadores;
  tabla y tarjetas nunca se duplican como superficies activas.
- S05-AC-12: Acciones del contexto cumplen la matriz: lectores no mutan; R/DCV sólo semana máxima;
  A/D pueden pasado; confirmada bloquea planificación pero permite avance/lote al editor.
- S05-AC-13: Update envía exactamente los diez campos legacy canónicos, `semana` y CSRF, mediante
  `URLSearchParams`; no envía `db` ni campos de S06.
- S05-AC-14: PHP vuelve a comprobar permiso, proyecto, semana existente, fila no capítulo, pasado y
  confirmación; una petición manual no evita los bloqueos de React.
- S05-AC-15: Cliente y servidor rechazan fechas no ISO/reales o invertidas, cantidad negativa,
  incoherencia `%`/cantidad y ratio fuera de `0..1` sin normalización silenciosa.
- S05-AC-16: Éxito reemplaza estado, semanas para inicio, unidad, cantidad y ratio con la respuesta
  server-authoritative; no calcula estado principal en TypeScript.
- S05-AC-17: Sólo existe una mutación por fila; cambiar semana/proyecto con borrador exige decisión y
  respuestas abortadas/obsoletas no escriben estado nuevo.
- S05-AC-18: Batch usa una petición sin body decorativo, nunca se reintenta, comunica ambos conteos y
  siempre reconcilia contexto/lista.
- S05-AC-19: La marca de retorno queda acotada por proyecto/semana, no dispara en primera entrada o
  reload y se consume una sola vez únicamente con `runBatch=true`.
- S05-AC-20: CSV cumple RFC 4180, BOM UTF-8, orden de trece columnas, conjunto visible y nombre
  `programa_general.csv`.
- S05-AC-21: Corte exige permiso, CSRF y alcance de sesión; Zod acepta sólo una URL same-origin bajo
  el directorio permitido con `.xlsx`; la prueba no escribe un archivo real.
- S05-AC-22: Drawer usa los cuatro endpoints canónicos, permisos semanales, payloads exactos,
  comentarios recursivos, SOS/caídas a portapapeles, simulación, resumen local y cierre de crisis de
  al menos 100 caracteres.
- S05-AC-23: Drawer resuelve el proyecto activo por su alcance para Construcción y Preconstrucción;
  nunca fuerza `Area='Construccion'` ni acepta proyecto/usuario/semana del cliente.
- S05-AC-24: Los ocho estados, alerta de restricciones y leyenda usan el catálogo canónico; aliases
  sólo se normalizan para presentación y abrir no reescribe datos.
- S05-AC-25: Los catorce estados de pantalla/operación distinguen carga, vacío, parcial, error,
  desactualización y recuperación sin borrar datos útiles.
- S05-AC-26: Todo transporte productivo pasa por `frontend/src/lib/api/cliente.ts`; componentes y
  gateways de dominio no llaman `fetch` directamente.
- S05-AC-27: CSS usa `public/css/tokens.css`, oscuro por defecto y claro completo; los ocho escenarios
  tema/viewport no pierden controles ni crean overflow de página.
- S05-AC-28: Tabla, tarjetas, filtros, modal, drawer y feedback pasan teclado, foco, live regions,
  zoom, targets, reduced motion y axe sin violaciones críticas/serias.
- S05-AC-29: Contratos de mutación, componentes y navegador usan fakes/intercepts; ninguna prueba del
  plan ejecuta DML, selección real, comentarios/crisis reales ni generación real de XLSX.
- S05-AC-30: Tras el gate canónico se retiran VIEW-34 y assets/filtros exclusivos con cero referencias;
  rollback está documentado y no toca APIs/servicios compartidos.
- S05-AC-31: S05 no modifica RLS, schema, grants, usuarios, credenciales, datos ni `/admin/`.

## Entregas verticales

### Entrega 1 — Núcleo de lectura

Incluye ruta de desarrollo, navegación autorizada, contexto, semanas, lista, adaptación de filas,
estados, restricciones, búsqueda, filtros, conteos, leyenda, recarga, tabla desktop/tablet y tarjetas
móviles de solo lectura.

Se acepta cuando:

- A, D, R, DCV, OT, G, S, SG y V pueden leer según RBAC; C recibe 403 y no ve el destino;
- semana inválida no cambia sesión;
- cambiar proyecto no conserva filas, acciones ni tokens del anterior;
- carga, vacío, sólo capítulos, sin coincidencias y error son distintos;
- los conteos facetados coinciden con las funciones puras;
- 1440, 1180, 768 y 390 no tienen overflow de página;
- ambos temas usan tokens y no cargan assets de Handsontable;
- ningún componente llama `fetch`.

### Entrega 2 — Edición individual

Incluye campos editables, catálogo de códigos, validación cliente/servidor, CSRF, acciones por semana,
feedback, reintento y paridad entre tabla y tarjetas.

Se acepta cuando:

- lectores y capítulos no pueden iniciar mutación;
- semana confirmada bloquea planificación pero permite avance al editor;
- R/DCV no editan semanas menores a la máxima y A/D sí;
- fecha inválida, inicio posterior al fin, cantidad negativa y avance fuera de rango no emiten
  petición;
- cambiar a `%` conserva ratio y elimina cantidad sólo tras confirmación;
- éxito usa estado y semana de inicio devueltos por PHP;
- error conserva el borrador y permite reintentar o descartar;
- tabla y tarjetas producen el mismo payload canónico.

### Entrega 3 — Operaciones

Incluye lote manual, lote único al regresar, CSV, corte XLSX y recarga con preservación de vista.

Se acepta cuando:

- el lote manual hace una sola petición y luego consulta datos frescos;
- primera entrada no ejecuta lote y volver a la misma combinación ejecuta exactamente uno;
- un rol no autorizado no muestra control ni emite petición;
- CSV refleja el conjunto visible con capítulos y trece cabeceras;
- la URL del corte se valida antes de navegar;
- fallo de lote o reporte no destruye tabla, filtros ni selección.

### Entrega 4 — Drawer contextual

Incluye selección, diagnóstico, comentarios, respuestas, menciones, SOS, crisis, foco y responsive.

Se acepta cuando:

- permisos de drawer provienen de Programación Semanal;
- capítulos no exponen acciones de actividad;
- filtro oculta una selección sin mezclarla con otra fila;
- cambio de semana/proyecto limpia el contexto;
- Construcción y Preconstrucción resuelven el proyecto correcto;
- cerrar crisis exige 100 caracteres;
- Escape, focus trap, fondo inerte y devolución de foco funcionan.

### Entrega 5 — Corte y retiro del legacy exclusivo

Incluye `/programa-general` en `SpaRouter`, enlace canónico del shell, actualización de manifiesto de
diseño, matriz final y documentación de rollback.

Se acepta cuando:

- `/programa-general` sirve React y `/api/*` conserva JSON;
- retirar el prefijo SPA restaura PHP sin otra migración;
- la navegación ya no contiene blacklist de roles para PG;
- pruebas frontend, typecheck, contratos PHP seguros y gates visuales están verdes;
- matriz A/R/V/C y escenario Preconstrucción están cubiertos;
- consola y red no presentan errores inesperados;
- no se modificaron RLS, schema, grants, usuarios ni credenciales;
- después del gate canónico se retiraron VIEW-34, `hot.js`, el CSS y filtros de sesión exclusivos,
  sin borrar controladores/servicios compartidos ni regenerar goldens sin autorización.

## Criterio global de cierre

Programa General React no se declara migrado por existir una tabla o por cargar filas. El frente
cierra cuando todas las capacidades de esta spec tienen:

1. contrato backend identificado;
2. esquema Zod;
3. presentación React;
4. regla de permiso server-side;
5. estado de carga, vacío, error y éxito pertinente;
6. prueba automatizada proporcional;
7. evidencia visual cuando corresponda;
8. entrada en la matriz de paridad;
9. rollback verificable hacia PHP;
10. ausencia de regresiones en la línea base del shell.

La falta de autorización para DML real no se interpreta como verde. Si sigue vigente al cierre de
código, la comprobación E2E de mutaciones queda registrada como límite bloqueante o como gate
posterior autorizado, según decida Felipe en ese momento.

## Riesgos aceptados y mitigaciones

| Riesgo | Decisión v0 |
|---|---|
| Respuestas legacy amplias y tipos mixtos | Adaptador Zod estricto en la frontera; modelo interno estable |
| Última escritura gana | Se conserva en v0; feedback claro y recarga server-authoritative |
| Dos superficies durante construcción | Ruta `/app/programa-general`, matriz de paridad y corte único al final |
| Lote automático sorprendente | Se conserva por paridad, se hace visible y se limita a una combinación proyecto/semana |
| Dataset mayor al observado | Medir primero; no introducir virtualización sin evidencia |
| Preconstrucción diverge en restricciones/drawer | Un resolver compartido y fixtures de ambas áreas |
| Falta de E2E real por prohibición de DML | Pruebas puras/simuladas ahora; base descartable sólo con autorización posterior |
| Rollback prolonga vida del legacy | Mantenerlo sólo durante piloto; retirarlo en el cierre canónico y revertir el commit si hiciera falta |

## Decisiones descartadas

- Envolver `hot.js` o montar la vista PHP dentro de React.
- Usar iframe o microfrontend.
- Introducir AG Grid, TanStack Table, React Query, Redux o CSS-in-JS en este módulo sin una necesidad
  medida.
- Hacer que React calcule el estado principal.
- Mantener filtros mediante endpoints que mutan sesión por GET.
- Mostrar tarjetas en tablet.
- Omitir capítulos en móvil.
- Enviar `db` desde el navegador.
- Inferir permisos con listas de roles.
- Ejecutar mutaciones reales contra la base compartida para obtener evidencia.
- Mantener el legacy activo de forma indefinida después del gate canónico.

## Decisiones pendientes

No quedan decisiones de negocio, producto, estrategia o PM pendientes para S05. Se preservan el
catálogo de estados/restricciones, la edición de avance en semana confirmada, el bloqueo de pasado,
el lote manual y de retorno, las trece columnas, el corte de dos hojas y las acciones actuales del
drawer. Las contradicciones de UI/servidor se resuelven a favor de las capacidades y guardas PHP
vigentes, sin ampliar acceso.

## Siguiente gate

Invocar `superpowers:writing-plans` y producir el plan S05 por entregas verticales: caracterización y
fronteras; lectura responsive; edición individual; operaciones; drawer; corte y retiro. El plan debe
nombrar archivos, pruebas y comandos exactos, empezar cada comportamiento con prueba fallando, usar
fakes/intercepts para toda mutación, y no implementar ni abrir S06 hasta terminar su autorrevisión.
