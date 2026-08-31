---
capa: fuente
tipo: spec
estado: vigente
id: S10
fecha: 2026-08-30
superficie: cnc
rutas: ["/programacion-semanal/cnc"]
depende_de: [T01, S08, S09]
views: [VIEW-37]
areas: [lps, design-system]
fuente: "auditoría de public/index.php, ProgramacionSemanalController::cnc, CncApiController, SemanalApiController, LpsWeekEditPolicy, RbacCatalog/RbacService, ProjectLandingService, ControlTowerService, MetricDictionaryService, VIEW-37, legacyCards.js, programacion-semanal.css, contratos de estados semanales, pruebas y documentos S08/S09 en shell-minimo-react, 2026-08-30"
resumen: "Migración vertical S10 de Causas de No Cumplimiento a React: población operativa reparable, prioridad y diagnóstico independientes, cantidades y brecha de sólo lectura, catálogo por área, filtros/conteos, tabla y tarjetas editables, guardado individual, oscuro/claro y corte strangler, sin cambiar KPI BI, RLS, schema ni datos durante la fase documental."
---

# S10 — Causas de No Cumplimiento en React

> **Estado:** diseño técnico autorrevisado. No quedan decisiones de negocio, producto, estrategia o
> PM que bloqueen el plan. Esta spec no autoriza implementación, commits, DDL/DML, cambios RLS,
> cambios de permisos, deploy, publicación ni trabajo en `/admin/`. Su plan se escribe a
> continuación con `superpowers:writing-plans`, conforme al programa aprobado de 27 specs y 27
> planes.

## Relación con el programa

Esta spec continúa las decisiones de:

- [[docs/superpowers/specs/2026-08-28-migracion-react-typescript-design|Migración React + TypeScript]];
- [[docs/superpowers/specs/2026-08-28-paridad-shell-react-rls-design|Paridad del shell React y RLS]];
- [[docs/superpowers/specs/2026-08-30-t01-shell-runtime-react-design|T01 — shell/runtime React]];
- [[docs/superpowers/specs/2026-08-30-s08-programacion-semanal-react-design|S08 — Programación Semanal React]];
- [[docs/superpowers/specs/2026-08-30-s09-cnp-react-design|S09 — CNP React]];
- [[docs/security/rls-runtime-boundary|Frontera runtime de RLS]].

T01 posee sesión, proyecto, semana, sidebar, tema y navegación. S08 posee compromisos, avance real,
la identidad canónica de la fila semanal y el momento en que una ejecución menor al compromiso
exige CNC. S09 posee CNP y establece el catálogo causal por área que S10 debe reutilizar. S10 posee
la bandeja donde se consulta y corrige la clasificación de los incumplimientos. S11 posee CIC. S10
no duplica el shell, no vuelve editable el avance y no adelanta contratos de proveedores.

VIEW-37 es `views/programacion-semanal/CNC.view.php` y pertenece únicamente a S10. El archivo
`public/js/modules/programacion_semanal/legacyCards.js` continúa compartido con S11: al cortar S10
se retiran sólo sus ramas CNP/CNC ya sin consumidores y se conserva CIC. Los selectores genéricos de
`public/css/programacion-semanal.css` sólo se eliminan con búsqueda de cero consumidores; el archivo
completo permanece hasta el corte de S11.

## Resultado buscado

`/programacion-semanal/cnc` será una superficie React que conserva toda capacidad útil y todo
comportamiento observable de la vista PHP/JavaScript actual, y hace visibles las inconsistencias que
el dominio semanal ya detecta:

1. usa el proyecto y la semana activos del shell, con fechas y estado de confirmación;
2. muestra incumplimientos actuales, clasificaciones incompletas y CNC registradas que requieren
   revisar consistencia, siempre dentro del proyecto/semana;
3. separa la prioridad operacional —atraso y ruta crítica— del diagnóstico de la CNC;
4. presenta compromiso, ejecución real, cumplimiento y brecha como evidencia de sólo lectura;
5. muestra identidad, actividad, descripción, ubicación, empresa, subcontratista y responsable sin
   permitir que S10 modifique esos campos;
6. permite buscar, combinar filtros y conocer total, visibles y conteos por prioridad/diagnóstico;
7. usa tabla semántica en desktop/tablet y tarjetas nativas con los mismos datos/acciones en móvil;
8. edita individualmente categoría, causa dependiente y observación;
9. usa el catálogo causal aprobado por área, incluye la opción sintética “Otra” y conserva valores
   históricos sin convertirlos en catálogo;
10. exige explicación no vacía para “Otra/Otros” y deja observación opcional para causas de catálogo;
11. guarda con CSRF, capacidad, política de semana, scope servidor, lock y versión opaca;
12. recarga explícitamente, preserva filtros tras éxito/error recuperable y no duplica requests;
13. navega entre Programación Semanal, CNP, CNC y CIC mediante rutas reales;
14. maneja carga, vacío real, filtros sin resultados, catálogo vacío, sólo lectura, conflicto y
    errores estables;
15. ofrece capacidad equivalente en oscuro y claro, teclado, lector de pantalla, zoom y touch.

Paridad no obliga a conservar DataTables, jQuery, Bootstrap, Select2, Font Awesome, globals de
sesión, HTML inyectado, prefijos enviados por el navegador, respuestas `SELECT *` ni errores con
excepciones. React conserva intención, datos, permisos, efectos y recuperación, y corrige límites de
seguridad, consistencia, accesibilidad y responsive.

## Alcance

### Incluido

- Ruta piloto y ruta canónica React de CNC.
- VIEW-37, búsqueda, leyenda, editor desktop y editor móvil.
- Contexto tipado de proyecto, área, semana, acciones, catálogo, navegación y CSRF.
- Población operativa reparable definida en esta spec, sin cambiar el KPI BI.
- Identidad semanal de S08 y DTO explícito; no `SELECT *`, HTML ni tipos ambiguos.
- Cuatro prioridades derivadas de `Atrasada` y `Critica`.
- Tres diagnósticos: documentada, incompleta e inconsistente.
- Cantidades comparables, porcentaje de cumplimiento y brecha derivados en servidor y sólo lectura.
- Búsqueda y filtros por prioridad, diagnóstico, categoría, responsable, empresa/subcontratista y
  presencia de observación.
- Conteo total, visible, por prioridad y por diagnóstico; restablecimiento de filtros.
- Tabla desktop/tablet y tarjetas móviles con paridad de información y acción.
- Categoría/causa dependiente del área, opción “Otra” y observación condicional.
- Preservación explícita de parejas históricas fuera de catálogo.
- Guardado individual con respuesta de fila completa, conteos y versión nueva.
- Recarga manual y recarga posterior al guardado sin perder filtros válidos.
- Oscuro/claro, foco, live regions, reduced motion, zoom 200 % y targets táctiles.
- Contratos PHP, Zod, pruebas puras y navegador con red completamente interceptada.
- Convivencia legacy durante piloto y retiro exclusivo después del corte canónico.

### Fuera de alcance

- `/admin/` y cualquier ruta, permiso, vista, estilo o dependencia administrativa.
- Cambiar RLS, `ProjectScope`, schema, migraciones, tablas, columnas, índices, triggers, grants,
  usuarios, credenciales, membresías, roles, overrides o datos.
- Ejecutar DDL/DML durante esta fase documental o durante las verificaciones prescritas por el plan.
- Cambiar `pg_cnc_activity_count`, sus filtros, su linaje, gráficos BI o conciliaciones históricas.
- Editar compromiso, ejecución real, PAC, porcentaje, estado semanal, empresa, subcontratista,
  responsable, actividad, ubicación, fechas, criticidad o atraso.
- Reprogramar, desprogramar, crear, duplicar o borrar una actividad; S10 sólo corrige CNC.
- Migrar la calificación semanal principal; pertenece a S08.
- Migrar CNP o CIC; pertenecen a S09 y S11.
- Integrar el drawer T02: VIEW-37 no lo ofrece y S08 posee el detalle/acciones ampliados de la fila.
- Exportar CSV/XLSX, descargar corte o guardar por lote: VIEW-37 no ofrece esas capacidades y el
  atlas aprobado no las asigna a S10.
- Crear o editar categorías/causas globales, insertar “Otra” en `general_cnc` o autocorregir datos.
- Crear profesionales, empresas o subcontratistas desde esta superficie.
- Añadir paginación, virtualización o ordenamiento automático por severidad sin evidencia de carga.
- Regenerar o aprobar goldens visuales sin autorización explícita.

## Punto de partida medido

### React

- No existe ruta, página, módulo, esquema Zod, gateway ni dominio CNC.
- La sidebar y la navegación semanal aún envían esta superficie a legacy.
- T01 ya dispone de proyecto, semana, tema y sesión; S10 no crea un segundo selector ni una matriz
  local de roles.
- `frontend/src/lib/api/cliente.ts` es la única frontera HTTP permitida.
- S08 define `rowId`, `sourceActivityId`, `activityId` y `activityCode`; S10 no vuelve a mezclar
  `Consecutivo`, `Id` y `row_id`.
- S09 define un catálogo causal por área; S10 debe compartirlo, no crear otro catálogo divergente.

### Legacy

| Pieza | Medición auditada |
|---|---|
| Vista | VIEW-37, 586 líneas |
| Controlador API CNC | 122 líneas |
| Controlador de página compartido | 199 líneas |
| Tarjetas responsive compartidas | `legacyCards.js`, 435 líneas |
| Presentación compartida | `programacion-semanal.css`, 3.814 líneas |
| Grid | DataTables, sin paginación ni ordenamiento |
| Responsive | tarjetas por debajo de 1180 px; DataTable continúa montado oculto |
| Leyenda | imagen genérica de Programación Semanal, no específica de CNC |
| Evidencia | tests desktop/móvil y edición que escriben/restauran datos reales |

La vista carga jQuery 1.12, Bootstrap, DataTables, jQuery UI, Google Charts, AnyChart y Select2,
aunque CNC no usa gráficos. Inyecta proyecto, prefijo, semana, rol, máximo y confirmación en inputs
ocultos; hardcodea categorías; consulta causas con AJAX por texto y navega mediante
`/legacy/cambiar_pagina.php`.

### Defectos y contradicciones observados

1. La página sólo exige autenticación; las APIs sí exigen `lps.cnc.ver`. La ruta React exigirá la
   capacidad de lectura desde el primer byte.
2. La lista usa `SELECT *`, entrega almacenamiento directamente y no tiene orden SQL.
3. La lista exige `Activa=1 AND Categoria_CNC IS NOT NULL`: oculta incumplimientos aún sin categoría,
   categorías nulas con causa/observación y CNC registradas en el universo manual `NA`.
4. Una categoría vacía, pero no nula, sí aparece; la semántica depende accidentalmente de null/`''`.
5. Guardar acepta `Consecutivo` o `Id`, semana y textos libres desde el navegador.
6. Categoría/causa sólo se validan como texto no vacío; no se verifica pertenencia al catálogo.
7. `/api/cnc/reasons` recibe área desde la UI pero la ignora y consulta el catálogo global por texto.
8. La vista fija ocho categorías de Construcción y no atiende Pre-Construcción.
9. Valores históricos se agregan al select sólo del editor actual, sin marcarlos como históricos.
10. “Otra”, “Otra...”, “Otros” y “Otros...” exigen observación en servidor, pero esa regla no se
    comunica antes del submit.
11. Desktop distingue cuatro combinaciones prioridad por tres clases y un default; móvil colapsa
    atraso no crítico y crítica no atrasada en un mismo estado de advertencia.
12. La leyenda abre una imagen genérica que explica estados de programación, no diagnóstico CNC.
13. La UI deriva permiso desde rol/semana; el servidor vuelve a decidir con política normal.
14. Los errores incluyen texto de excepción y el éxito sólo devuelve `{"respuesta":"BIEN"}`.
15. El guardado no bloquea fila ni detecta edición concurrente.
16. Los tests legacy de ciclo completo restauran datos; esa restauración también es DML y no es una
    verificación permitida para esta migración.

S10 corrige esos defectos sin ampliar capacidades, cambiar cantidades, redefinir KPI ni alterar
datos durante diseño/verificación.

## Comportamiento observable auditado

### Contexto, semana y carga

`ProgramacionSemanalController::cnc()` sincroniza `semana` desde el request, sanea el contexto y
redirige a `/proyectos` si el prefijo es inválido o a `/programa-general-actualizar` si no existen
semanas. Obtiene semana máxima, confirmación, semanas del shell y un token CSRF `cnc`.

`POST /api/cnc/list` exige `lps.cnc.ver`, toma la semana del request y consulta por proyecto resuelto
desde el prefijo de sesión. Devuelve filas `Activa=1` cuya `Categoria_CNC` no sea null, sin orden
explícito. La migración conserva proyecto/semana activos, pero deja de aceptar prefijo, proyecto,
rol, área o semana del body como autoridad.

### Tabla, búsqueda y leyenda

La tabla muestra acción, Id, actividad, descripción, categoría, causa y observaciones. Oculta la PK y
ubicación. No pagina ni ordena; ajusta altura vertical. DataTables ofrece búsqueda global y el
adaptador la rotula “Buscar actividad o causa”. El botón limpiar borra la búsqueda. No existen
filtros estructurados, chips ni conteos por estado; la única cifra es el total/filtrado de DataTables.

El vacío real dice: “Sin causas de no cumplimiento esta semana. Se registran al justificar un
avance menor al compromiso en Programación Semanal.” El vacío móvil filtrado dice: “No hay registros
para los filtros actuales.” La nueva superficie preserva ambos significados con componentes
distintos.

La leyenda actual reutiliza `Leyenda_Actividades.png`; sus siete estados pertenecen a programación y
no explican CNC. S10 la reemplaza por texto accesible que describe las cuatro prioridades y los tres
diagnósticos definidos aquí. La imagen permanece si otras vistas legacy la consumen.

### Responsive y edición

Por debajo de 1180 px, `legacyCards.js` oculta visualmente la DataTable, pero ambas representaciones
siguen montadas. La tarjeta muestra descripción, categoría, causa y observación; su encabezado usa Id
y actividad. Ofrece edición cuando el cálculo local cree que la semana/rol lo permite.

Desktop convierte tres celdas en categoría, causa y observación inline. Móvil abre un panel con los
mismos campos. Cambiar categoría vacía/recarga la causa, valores históricos se añaden si faltan,
Enter guarda y Escape cancela en desktop. Cancelar recarga la tabla; guardar exitoso vuelve a pedir
datos. Un error móvil conserva el editor.

React usa tabla desde 768 px y tarjetas por debajo de 768 px para cumplir “desktop/tablet tabla,
móvil tarjetas”. Sólo monta el layout activo. Ambos consumen el mismo row model, editor, validación y
acción; cambiar de breakpoint cierra el editor con aviso si tiene un borrador sucio, o lo traslada
sin pérdida si el componente compartido permite mantenerlo de forma segura.

### Efecto persistente

`POST /api/cnc/save` exige `lps.cnc.editar`, CSRF `cnc` y `LpsWeekEditPolicy` normal
(`qualification=false`). Verifica proyecto, fila, semana, `Activa=1` y categoría no null. Actualiza
exclusivamente:

- `Categoria_CNC`;
- `CNC`;
- `Observaciones_CNC`.

No cambia compromiso, real, PAC, `P_Completado`, estado activo ni asignaciones. S10 preserva ese
efecto estrecho; ampliar la población no amplía las columnas mutables.

## Semántica canónica de la población CNC

### Fuentes que ya existen

Hay cuatro contratos observados que no coinciden exactamente:

1. VIEW-37 lista `Activa=1 AND Categoria_CNC IS NOT NULL`.
2. El estado semanal/S08 exige CNC cuando compromiso `>0`, existe real y
   `real + 0.0001 < compromiso`.
3. `ProjectLandingService` considera pendiente un incumplimiento si falta categoría **o** causa.
4. BI/Control Tower define `pg_cnc_activity_count` como filas `Activa IN ('1','NA')` con `CNC` no
   vacía; además Control Tower advierte cuando faltan cantidades comparables o una CNC queda
   registrada pese a que las cantidades indican cumplimiento.

Copiar el filtro de VIEW-37 perpetuaría un callejón sin salida: la actividad que necesita CNC pero
aún no tiene categoría no sería visible en la bandeja de reparación. Copiar el KPI BI escondería la
misma fila porque todavía no tiene causa. S10 es una bandeja operativa, no el KPI.

### Decisión de población operativa

Una fila pertenece a S10 cuando cumple todas estas condiciones base:

- mismo `project_id` y semana activa del contexto;
- `Activa IN ('1','NA')`;
- no es TNP (`Es_TNP` distinto de `1`);
- y cumple al menos una condición de inclusión:
  - **incumplimiento actual:** compromiso comparable `>0`, real comparable y
    `real + 0.0001 < compromiso`; o
  - **rastro CNC:** categoría, causa u observación CNC contiene texto no vacío.

Los tipos/strings legacy se normalizan antes de evaluar; null, vacío y whitespace son ausencia. El
servidor aplica el epsilon vigente `0.0001`. Una fila TNP no entra aunque conserve accidentalmente
campos CNC: su corrección pertenece a S08/datos y se denuncia como inconsistencia fuera de S10.

Esta unión garantiza:

- incumplimientos todavía sin clasificación visibles y reparables;
- clasificaciones completas visibles;
- registros parciales visibles;
- CNC huérfanas o desactualizadas visibles para revisión;
- ninguna mezcla entre proyectos/semanas;
- ningún cambio automático de datos.

S10 **no cambia** `pg_cnc_activity_count`: BI sigue contando únicamente su universo documentado
`Activa IN ('1','NA') AND TRIM(CNC)<>''`. Los conteos operativos de S10 llevan nombres propios y no
se presentan como ese KPI.

### Diagnóstico CNC

Cada fila recibe exactamente un diagnóstico, derivado en servidor:

| ID | Etiqueta | Regla | Acción esperada |
|---|---|---|---|
| `cnc-documented` | CNC documentada | incumplimiento actual + categoría/causa válidas; “Otra” además tiene explicación | revisar/editar si procede |
| `cnc-incomplete` | CNC incompleta | incumplimiento actual + clasificación ausente, parcial, inválida o “Otra” sin explicación | completar clasificación |
| `cnc-inconsistent` | CNC por revisar | hay rastro CNC, pero las cantidades no demuestran incumplimiento comparable actual | revisar consistencia; no autocorregir |

Una pareja histórica no disponible en el catálogo puede ser completa si categoría y causa tienen
texto y respeta la regla de “Otra/Otros”. Se marca `historical=true`, no `incomplete`, salvo que falte
una pieza o la explicación obligatoria.

El diagnóstico no autoriza mutaciones ni cambia de color crítico por sí solo. Se muestra con texto,
icono y badge; lectores de pantalla reciben la etiqueta completa.

### Prioridad operacional

Se conservan las cuatro combinaciones legacy de `Atrasada` y `Critica` sin el colapso móvil:

| ID | Etiqueta | Atrasada | Crítica | Token semántico |
|---|---|---:|---:|---|
| `cnc-overdue-critical` | Atrasada crítica | sí | sí | urgente |
| `cnc-overdue-non-critical` | Atrasada no crítica | sí | no | atención alta |
| `cnc-critical` | Crítica | no | sí | advertencia crítica |
| `cnc-non-critical` | No crítica | no | no | neutral |

Prioridad y diagnóstico son ejes independientes. Por ejemplo, una fila puede ser “Atrasada crítica”
y “CNC incompleta”. El fondo/acento de fila se deriva sólo de prioridad con tokens; el diagnóstico
se comunica en un badge separado. Nunca se depende únicamente del color.

### Evidencia cuantitativa de sólo lectura

S10 normaliza y muestra:

- `commitment`: número comparable o null;
- `actualExecuted`: número comparable o null;
- `completionPercent`: `actual/commitment × 100` con una decimal cuando aplica;
- `gapQuantity`: `max(commitment-actual, 0)` cuando ambos son comparables;
- `gapPercent`: `gap/commitment × 100` con una decimal cuando compromiso `>0`;
- `unit`: texto normalizado.

No se persiste ninguno de esos derivados. Los números conservan el contrato de S08, incluida entrada
legacy con coma/punto al leer, y la UI usa formato localizado sólo para presentar. Null se muestra
“Sin dato comparable”, no cero. Cumplimiento puede superar 100 %; la brecha queda en cero.

## Catálogo, valores históricos y validación

### Catálogo único compartido con S09

S10 reutiliza el servicio por área diseñado en S09. Si la implementación de S09 conserva el nombre
`CnpCatalogService`, antes de añadir S10 se extrae mecánicamente la lógica compartida a
`CausaSemanalCatalogService` y ambos módulos conservan sus pruebas. No se crea un segundo query,
normalizador o mapa de categorías.

El catálogo se resuelve desde el área del proyecto servidor. Construcción y Pre-Construcción no se
mezclan. Cada causa vigente tiene ID estable, valor almacenado y etiqueta. React envía IDs, nunca
categoría/causa libre.

### Opción “Otra”

“Otra” es una opción sintética por categoría, no una fila que S10 inserta en `general_cnc`. El
payload declara `mode:"other"` y `categoryKey`; PHP verifica que la categoría exista en el catálogo
del área, deriva su valor almacenado y persiste causa canónica `Otra`. La observación trim debe
contener texto.

Las variantes históricas `Otra...`, `Otros` y `Otros...` se muestran como históricas y conservan la
misma obligación de explicación. No se normalizan ni reescriben durante lectura. Si el usuario
cambia a la opción sintética, el nuevo valor se guarda como `Otra`.

### Parejas históricas

Una categoría/causa ya almacenada que no coincide con catálogo se presenta con badge “Histórica”.
El editor permite:

- `keep-current`: conservar exactamente la pareja al editar sólo observación;
- elegir una causa vigente por ID;
- elegir “Otra” dentro de una categoría vigente.

La pareja histórica no aparece como opción disponible para otras filas. Cambiar categoría invalida
la causa anterior y exige escoger causa vigente/Otra. Una fila parcial no puede usar
`keep-current`; debe quedar completa para guardar.

### Reglas de guardado

- `rowId` entero positivo y `version` SHA-256 opaca obligatorios.
- `classification.mode` sólo puede ser `catalog`, `other` o `keep-current`.
- `catalog` exige `causeId` vigente del área; servidor deriva categoría/causa.
- `other` exige `categoryKey` vigente y observación trim no vacía.
- `keep-current` sólo aplica a una pareja almacenada completa y todavía perteneciente a la fila.
- Para causa de catálogo, observación puede quedar vacía; no se inventa obligación global.
- Categoría/causa respetan capacidad real de 100 caracteres; observación respeta la capacidad
  `MEDIUMTEXT` y un límite HTTP/documentado defensivo definido con evidencia de schema.
- Texto se normaliza a trim para validar, pero no se interpreta como HTML.
- Guardar sin cambios es válido, no modifica columnas y devuelve la misma fila/version o una
  respuesta explícita `changed=false`.
- El servidor bloquea fila, revalida scope, población, acción, versión y catálogo antes del update.
- La mutación actualiza sólo las tres columnas CNC y devuelve fila normalizada/conteos.

## Contratos HTTP objetivo

### Inventario legacy y retiro

| Método | Ruta legacy | Uso actual | Transición |
|---|---|---|---|
| GET | `/programacion-semanal/cnc` | VIEW-37 | piloto coexistente; SPA al corte |
| POST | `/api/cnc/list` | lista `SELECT *` | delegar durante piloto; retirar al corte |
| POST | `/api/cnc/save` | guardado form-urlencoded | delegar durante piloto; retirar al corte |
| POST | `/api/cnc/reasons` | causas por categoría | compartido con S08/S09 legacy; retirar sólo con cero consumidores |

S10 agrega exactamente tres endpoints. Todos devuelven JSON, exigen sesión/proyecto y tienen esquema
Zod estricto y prueba PHP de contrato:

| Método | Ruta nueva | Capacidad | CSRF | Efecto |
|---|---|---|---|---|
| GET | `/api/cnc/context` | `lps.cnc.ver` | no | lectura pura |
| GET | `/api/cnc/activities` | `lps.cnc.ver` | no | lectura pura |
| POST | `/api/cnc/activity` | `lps.cnc.editar` | header | update individual estrecho |

Ni GET acepta proyecto, prefijo, área, rol o semana como autoridad. T01 sincroniza el contexto de
semana servidor antes de cargar la superficie. Cambiar semana aborta lecturas anteriores y sólo
después solicita los endpoints del nuevo contexto.

### Contexto

`GET /api/cnc/context` devuelve:

```json
{
  "data": {
    "project": {
      "id": 65,
      "name": "Proyecto Norte",
      "area": "Construccion"
    },
    "week": {
      "number": 18,
      "startDate": "2026-08-24",
      "endDate": "2026-08-30",
      "maxWeek": 19,
      "confirmed": true
    },
    "actions": {
      "edit": { "allowed": true, "reasonCode": null }
    },
    "csrfToken": "opaque-or-null",
    "causeCatalog": [
      {
        "categoryKey": "programacion",
        "storedValue": "Programación",
        "label": "Programación",
        "causes": [
          { "id": 41, "storedValue": "Coordinación pendiente", "label": "Coordinación pendiente" }
        ],
        "other": { "allowed": true, "label": "Otra (explicar)" }
      }
    ],
    "sections": [
      { "id": "weekly", "label": "Programación Semanal", "href": "/programacion-semanal", "available": true },
      { "id": "cnp", "label": "CNP", "href": "/programacion-semanal/cnp", "available": true },
      { "id": "cnc", "label": "CNC", "href": "/programacion-semanal/cnc", "available": true },
      { "id": "cic", "label": "CIC", "href": "/programacion-semanal/cic", "available": true }
    ]
  }
}
```

Cada acción efectiva es `{allowed, reasonCode}`. Razones: `READ_ONLY`, `EDIT_WINDOW_CLOSED`,
`CATALOG_UNAVAILABLE` y `null`. Confirmar semana no agrega una denegación independiente: el endpoint
legacy usa la política normal y permite corregir CNC si esa política todavía permite la semana.
`csrfToken` es null cuando no existe acción mutable. Las secciones se resuelven en servidor; React no
deduce visibilidad desde rol.

### Actividades

`GET /api/cnc/activities` devuelve la población completa y conteos operativos:

```json
{
  "data": {
    "rows": [
      {
        "rowId": 482,
        "sourceActivityId": 92,
        "activityId": "4.2.1",
        "activityCode": "EST-001",
        "activity": "Fundir placa nivel 2",
        "description": "Placa sector oriental",
        "location": "Torre 1",
        "company": "AIA",
        "subcontractor": "Concretos SAS",
        "responsible": "Ana Pérez",
        "unit": "m3",
        "quantities": {
          "commitment": 120.0,
          "actualExecuted": 72.0,
          "completionPercent": 60.0,
          "gapQuantity": 48.0,
          "gapPercent": 40.0
        },
        "classification": {
          "category": "Programación",
          "cause": "Coordinación pendiente",
          "catalogCauseId": 41,
          "historical": false,
          "complete": true
        },
        "observations": "Ajustar secuencia",
        "critical": true,
        "overdue": true,
        "priority": {
          "id": "cnc-overdue-critical",
          "label": "Atrasada crítica",
          "token": "urgent"
        },
        "diagnosis": {
          "id": "cnc-documented",
          "label": "CNC documentada"
        },
        "actions": {
          "edit": { "allowed": true, "reasonCode": null }
        },
        "version": "bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb"
      }
    ],
    "counts": {
      "total": 1,
      "overdueCritical": 1,
      "overdueNonCritical": 0,
      "critical": 0,
      "nonCritical": 0,
      "documented": 1,
      "incomplete": 0,
      "inconsistent": 0
    }
  }
}
```

`rows=[]` es un vacío válido. Cantidades no comparables son null. No se devuelve `project_id`, `db`,
rol, SQL, HTML, `Activa`, `Es_TNP`, nombres de columna legacy ni campos editables de otros módulos.
`version` es un SHA-256 opaco de proyecto, semana, identidad, elegibilidad, cantidades relevantes y
campos CNC; no reemplaza autorización. El servidor lo recalcula dentro del lock.

El orden es estable por `Consecutivo_En_Programa`, `row_id` (o el identificador canónico disponible
equivalente), nunca por severidad implícita. Los filtros permiten explorar prioridades sin que una
recarga reordene filas arbitrariamente.

### Guardado individual

`POST /api/cnc/activity` recibe JSON estricto y `X-CSRF-Token`. Causa de catálogo:

```json
{
  "rowId": 482,
  "version": "bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb",
  "classification": { "mode": "catalog", "causeId": 41 },
  "observations": "Ajustar secuencia"
}
```

Opción “Otra”:

```json
{
  "rowId": 482,
  "version": "bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb",
  "classification": { "mode": "other", "categoryKey": "programacion" },
  "observations": "Interferencia no prevista con acceso temporal"
}
```

Conservar pareja histórica:

```json
{
  "rowId": 482,
  "version": "bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb",
  "classification": { "mode": "keep-current" },
  "observations": "Se amplió la explicación histórica"
}
```

PHP deriva textos, valida catálogo/área y obligación de observación, bloquea fila, comprueba
proyecto/semana/población/versión/política, actualiza sólo las tres columnas CNC y devuelve:

```json
{
  "data": {
    "changed": true,
    "row": {},
    "counts": {
      "total": 1,
      "overdueCritical": 1,
      "overdueNonCritical": 0,
      "critical": 0,
      "nonCritical": 0,
      "documented": 1,
      "incomplete": 0,
      "inconsistent": 0
    }
  }
}
```

`row` cumple el mismo esquema completo de actividades y trae nueva versión cuando cambió. No existe
retry automático. Un conflicto conserva el borrador y permite recargar deliberadamente.

### Errores estables

Todos los errores usan:

```json
{
  "error": {
    "code": "ROW_STALE",
    "message": "La actividad cambió. Recarga antes de volver a guardar.",
    "fields": {}
  }
}
```

| HTTP | Código | Uso |
|---:|---|---|
| 401 | `AUTH_REQUIRED` | sesión ausente o vencida |
| 403 | `CNC_FORBIDDEN` | falta capacidad de ver/editar |
| 403 | `EDIT_WINDOW_CLOSED` | política semanal normal deniega |
| 403 | `CSRF_INVALID` | token ausente o inválido |
| 404 | `ROW_NOT_FOUND` | fila fuera de scope o ya no pertenece a población CNC |
| 409 | `ROW_STALE` | versión o evidencia cuantitativa cambió |
| 409 | `CATALOG_VALUE_STALE` | causa dejó de ser válida |
| 422 | `CLASSIFICATION_REQUIRED` | pareja ausente/parcial al guardar |
| 422 | `OTHER_OBSERVATION_REQUIRED` | “Otra/Otros” sin explicación |
| 422 | `VALIDATION_FAILED` | JSON, límites o modo inválido |
| 503 | `CATALOG_SCHEMA_PREREQUISITE_MISSING` | catálogo canónico no disponible |
| 500 | `CNC_UNAVAILABLE` | error interno sin detalles sensibles |

El cliente común conserva status, código y `fields`; la UI no usa mensajes como lógica. Ningún
error expone SQL, tabla, prefijo, excepción, ruta interna o dato de otro proyecto.

## Permisos y capacidades

### Matriz fallback observada

| Rol canónico | Ver CNC | Editar CNC | Nota |
|---|---:|---:|---|
| A | sí | sí | toda semana por política LPS |
| D | sí | sí | toda semana por política LPS |
| R | sí | sí | sólo ventana histórica permitida |
| DCV | sí | sí | sólo ventana histórica permitida |
| OT | sí | no | lectura |
| V | sí | no | fallback de lectura global |
| G/S/SG | no | no | sus permisos LPS se concentran en CIC |
| C | no | no | sin CNC en fallback |

La matriz documenta fallback, no se serializa. Overrides persistidos pueden cambiar el resultado;
`RbacManager/RbacService` y capacidades efectivas mandan. Alias de rol se normalizan sólo con
`RbacService::normalizeRole()` cuando una política servidor lo necesita.

### Política de semana y confirmación

- `lps.cnc.ver` protege página, contexto y actividades.
- `lps.cnc.editar` protege el guardado.
- `LpsWeekEditPolicy` normal (`qualification=false`) se revalida al resolver acción y dentro de la
  mutación.
- A/D pueden editar cualquier semana; R/DCV sólo `maxWeek - 2 < week` según política vigente.
- La excepción histórica de calificación que S08 usa con `qualification=true` no aplica a S10.
- Confirmar semana no bloquea por sí solo la corrección standalone si la política normal permite la
  semana; se preserva el contrato observado.
- React muestra razón de sólo lectura, pero el servidor no confía en esa presentación.

## Filtros, conteos, recarga y orden

React carga la población completa de la semana y aplica filtros combinables localmente:

- búsqueda normalizada sin distinción de mayúsculas/acentos sobre Id, código, actividad,
  descripción, ubicación, empresa, subcontratista, responsable, categoría, causa y observación;
- prioridad, con selección múltiple;
- diagnóstico, con selección múltiple;
- categoría vigente o histórica;
- responsable, incluido “Sin responsable”;
- empresa/subcontratista;
- observación: todas, con observación, sin observación.

El encabezado muestra `N de T actividades`, cuatro conteos de prioridad y tres de diagnóstico. Los
conteos de servidor describen el total antes de filtros; la UI deriva visibles con las mismas
funciones puras. “Borrar filtros” conserva semana y devuelve foco a búsqueda. Un filtro cuyo valor
desaparece tras recarga se limpia con anuncio, no deja una pantalla falsamente vacía.

El botón “Recargar” cancela el GET anterior, conserva filtros válidos y pide contexto/filas una sola
vez. Está deshabilitado durante mutación para evitar carreras. No hay autosondeo, paginación,
virtualización, exportación ni batch.

## Arquitectura React

### Módulo

```text
frontend/src/modules/cnc/
  CncPage.tsx
  useCnc.ts
  dominio/
    diagnosticarCnc.ts
    filtrarCnc.ts
    normalizarCnc.ts
  componentes/
    CabeceraCnc.tsx
    NavegacionSemanal.tsx
    FiltrosCnc.tsx
    ConteosCnc.tsx
    LeyendaCnc.tsx
    EstadoCnc.tsx
    TablaCnc.tsx
    TarjetasCnc.tsx
    EditorCnc.tsx
  cnc.css
```

`frontend/src/lib/api/esquemas/cnc.ts` contiene los únicos tipos wire vía `z.infer`.
`frontend/src/lib/api/cnc.ts` es el gateway y la única capa del módulo que llama `pedir()` de
`cliente.ts`; componentes y hooks nunca llaman `fetch`.

`useCnc` mantiene contexto, filas normalizadas, filtros, editor, petición activa y feedback. Sólo
existe una edición por vez. Cambiar proyecto/semana cancela lecturas, cierra editor, descarta
respuestas del contexto anterior y carga de nuevo. Mutaciones se serializan por fila, no se
reintentan y no hacen optimistic update antes de la respuesta validada.

### Tabla desktop/tablet

Desde 768 px muestra una tabla HTML semántica. Columnas persistentes:

- estado combinado accesible (prioridad + diagnóstico);
- Id/código y actividad;
- compromiso/real/brecha;
- categoría/causa;
- observación resumida;
- acción.

Descripción, ubicación, empresa, subcontratista, responsable, unidad y porcentajes aparecen en un
detalle expandible por fila en tablet/anchos limitados. La expansión usa botón con
`aria-expanded/aria-controls`; no se oculta información indispensable ni aparece overflow
horizontal de página. En 1180/1440 puede distribuir más columnas sin cambiar orden de lectura.

### Tarjetas móviles

Por debajo de 768 px cada tarjeta contiene:

- Id/código, actividad y prioridad textual;
- diagnóstico;
- descripción/ubicación;
- compromiso, real, brecha y unidad;
- categoría, causa y observación;
- empresa, subcontratista y responsable;
- editar o razón de sólo lectura.

El editor se abre inline bajo el encabezado de la tarjeta o en diálogo accesible compartido; nunca
en una DataTable oculta. Targets miden al menos 44×44 px. Ningún campo o acción de tabla se pierde.

### Editor y feedback

El editor identifica la actividad y evidencia cuantitativa sin permitir editarlas. Categoría cambia
la lista de causas. Seleccionar “Otra” revela/indica la obligación de explicar. Pareja histórica se
marca y puede conservarse. Guardar valida localmente para feedback inmediato y PHP vuelve a validar.
Cancelar restaura valores sin request. Escape cancela sólo tras confirmar si hay borrador sucio;
Enter no guarda desde textarea y evita submits accidentales.

Durante guardado, el botón muestra progreso y queda deshabilitado. Éxito anuncia por live region,
reemplaza fila/conteos con respuesta y cierra editor. 422 enfoca primer campo. 409 conserva borrador
y ofrece “Recargar actividad”. 401 deriva al flujo de sesión T01; 403 pasa a sólo lectura; 500/503
permiten reintentar lecturas sin duplicar mutación.

## Estados de experiencia

| Estado | Presentación | Acción |
|---|---|---|
| Carga inicial | skeleton de encabezado y filas/tarjetas | ninguna |
| Recarga | contenido anterior atenuado + progreso anunciado | cancelar implícitamente request previo |
| Vacío real | explicación legacy + enlace a Programación Semanal | cambiar semana/ir S08 |
| Sin resultados | filtros activos y `0 de T` | borrar filtros |
| Sólo lectura | banner con razón servidor | consultar/filtrar/recargar |
| Catálogo vacío | lectura disponible; editar deshabilitado salvo histórico conservable válido | recargar/contactar soporte |
| CNC incompleta | badge + llamada a completar | editar si autorizado |
| CNC inconsistente | badge + explicación cuantitativa | revisar/editar; nunca borrar automático |
| Conflicto | borrador preservado | recargar actividad o cancelar |
| Sesión vencida | aviso T01 y retorno seguro | reautenticar |
| Error de red/servidor | mensaje estable, contenido previo no se presenta como fresco | reintentar GET |

## Seguridad, aislamiento y RLS

- El scope se construye sólo desde sesión/proyecto/semana activos y `project_id` resuelto.
- Cada query operacional incluye `project_id`; la fila se vuelve a localizar dentro del scope.
- El catálogo global se lee por ID/área aprobada; no recibe prefijos ni dispara SQL dinámico.
- GETs son lectura pura: no crean tablas, índices, catálogo, semanas ni datos faltantes.
- La mutación exige sesión, capacidad, CSRF, política, fila, versión y allowlist.
- Se usan prepared statements por `Database`; ningún identificador o valor del request concatena SQL.
- La respuesta omite secretos, rol, prefijo, excepciones y datos de otros proyectos.
- HTML legacy de actividad/descripción se convierte a texto seguro; React no usa
  `dangerouslySetInnerHTML`.
- RLS permanece exactamente como está. S10 no ejecuta ni propone DDL/DML de RLS, grants, usuarios,
  credenciales o backfills.
- Fakes de contrato y red interceptada prueban aislamiento sin tocar MySQL.

## Tema, tokens y accesibilidad

- Sólo se consumen tokens de `public/css/tokens.css`; no hay colores literales, inline styles,
  `!important`, Bootstrap, DataTables ni CSS-in-JS.
- Dark sigue siendo default/fallback; light ofrece idéntica jerarquía, estados y acciones.
- Prioridad usa tokens semánticos existentes o aliases documentados; diagnóstico usa forma/texto,
  no un nuevo arcoíris.
- Foco visible, contraste WCAG AA, orden DOM coherente y `prefers-reduced-motion` respetado.
- Título visible “Causas de No Cumplimiento”; proyecto/semana y conteo quedan asociados.
- Tabla tiene caption accesible, headers/row headers; tarjetas usan article/heading.
- Filtros tienen labels persistentes, fieldsets y resumen anunciado.
- Guardado/errores/conteos usan live region mesurada; no anuncian cada tecla.
- Editor tiene nombre, descripción, foco inicial/retorno y errores asociados con `aria-describedby`.
- Se prueba teclado, lector semántico, touch, zoom 200 %, 390/768/1180/1440 y ausencia de overflow.

## Navegación y convivencia strangler

### Piloto

- Añadir `/app/programacion-semanal/cnc` y APIs nuevas sin reemplazar la ruta canónica.
- Mantener VIEW-37 y aliases legacy para rollback.
- Navegación del piloto usa rutas reales; las superficies aún legacy conservan su destino canónico.
- No retirar `/api/cnc/reasons` hasta que S08, S09, VIEW-37 y cualquier otro caller sean cero.

### Corte

Después de contratos, funcionalidad, RBAC, responsive, a11y, temas y aprobación visual cuando
corresponda:

1. enrutar GET/HEAD canónico a la SPA y protegerlo con `lps.cnc.ver`;
2. buscar consumidores de VIEW-37, `/api/cnc/list`, `/api/cnc/save`, `/api/cnc/reasons` y ramas CNC;
3. retirar exclusivamente aliases/vista sin consumidores;
4. remover de `legacyCards.js` ramas CNP/CNC ya cortadas, conservando CIC;
5. conservar selectores/imagen genéricos si cualquier otra vista los usa;
6. actualizar manifiestos e inventarios sin declarar S11 migrado;
7. demostrar rollback de ruta/vista sin rollback de datos.

El corte no borra filas, no transforma CNC históricas y no modifica BI.

## Estrategia de pruebas

### PHP sin base mutable

- Resolver de población: incumplimiento sin CNC, completa, parcial, stale/inconsistente, TNP excluida,
  `Activa=0` excluida, `NA` con rastro incluida, otro proyecto/semana excluido.
- Resolver de prioridad: cuatro combinaciones exactas.
- Resolver de diagnóstico: catálogo, histórico, “Otra” con/sin explicación y cantidades no
  comparables/cumplidas.
- Resolver cuantitativo: null, coma/punto, epsilon, cero, parcial, cumplimiento y sobrecumplimiento.
- Catálogo compartido: Construcción/Pre-Construcción, IDs, histórico y opción sintética.
- Acción: capacidades, overrides simulados, ventana normal y semana confirmada.
- Contexto: forma exacta, scope, catálogo, secciones, CSRF null en lectura.
- Actividades: DTO explícito, orden, conteos y ausencia de campos sensibles.
- Mutación: modos, límites, CSRF, lock, stale, allowlist y respuesta completa.
- Stores fakes registran calls; un GET falla si invoca write/transaction/DDL.

Cada endpoint nuevo tiene su propia prueba PHP de contrato: contexto, actividades y activity. Ningún
test escribe MySQL; rollback tampoco se considera permitido.

### Vitest y Testing Library

- Esquemas Zod estrictos rechazan keys extra, enum/tipo/version/cantidad inválidos.
- Gateway usa exactamente tres rutas, JSON y header CSRF mediante `cliente.ts`.
- Dominio puro normaliza, diagnostica, filtra y cuenta sin mutar respuesta.
- Hook cancela carreras de semana/recarga y serializa mutación.
- Tabla/tarjetas comparten fila, editor y acciones; sólo un layout está montado.
- Filtros combinados, limpiar, conteos, vacíos y orden estable.
- Editor catálogo/Otra/histórico, observación condicional, cancelar y errores.
- Sólo lectura, catálogo vacío, conflicto, sesión, 500/503 y foco.

### Playwright completamente interceptado

Antes de navegar, cada escenario intercepta `/api/session`, contexto T01, los tres endpoints S10 y
cualquier alias mutable. Un request no esperado falla. Fixtures en memoria simulan guardar/recargar
sin tocar PHP/MySQL.

Escenarios mínimos:

- lector permitido y capacidad denegada;
- semana editable, histórica cerrada y confirmada;
- Construcción/Pre-Construcción con catálogos correctos;
- tres diagnósticos y cuatro prioridades sin colapso móvil;
- búsqueda, filtros, conteos, limpiar y recargar;
- tabla 768/1180/1440 y tarjetas 390;
- editar catálogo, Otra e histórico; recargar mock y conservar;
- cancelación sin request, 422, stale, 403 y error de red;
- navegación S08/S09/S10/S11 sin ruta procedural;
- oscuro/claro, teclado, Axe, zoom 200 %, reduced motion, red y consola limpias.

No se ejecutan los casos mutables de `programacion-semanal-subviews.mjs`,
`programacion-semanal-roles-phases.mjs`, `programacion-semanal-sprint.mjs` ni
`tests/test_csrf_modulos_api.php`: escriben/restauran datos reales y restaurar también es DML.

## Criterios de aceptación

1. `/app/programacion-semanal/cnc` y, tras el gate, la ruta canónica renderizan React.
2. Página y APIs exigen `lps.cnc.ver`; un denegado no recibe datos.
3. Proyecto, área, rol, prefijo y semana nunca se aceptan como autoridad del navegador.
4. La población aplica scope, `Activa IN ('1','NA')`, exclusión TNP y la unión incumplimiento/rastro.
5. Un incumplimiento sin categoría o causa sigue visible como CNC incompleta.
6. Una CNC registrada sin incumplimiento comparable sigue visible como inconsistente.
7. El KPI `pg_cnc_activity_count` y sus filtros no cambian.
8. El DTO no expone `SELECT *`, HTML, prefijo, rol ni columnas innecesarias.
9. Los cuatro estados de prioridad coinciden con legacy en tabla y móvil.
10. Los tres diagnósticos son exclusivos, comprensibles y no dependen sólo del color.
11. Cantidades, cumplimiento y brecha se derivan igual en PHP/TypeScript y son sólo lectura.
12. Identidad, empresa, subcontratista y responsable se muestran y no son editables en S10.
13. Búsqueda, siete grupos de filtros, restablecimiento y conteos funcionan combinados.
14. Tabla desktop/tablet y tarjetas móvil presentan los mismos datos y acciones.
15. Cambiar semana mediante T01 cancela contexto anterior y carga la nueva población.
16. Construcción y Pre-Construcción reciben exclusivamente su catálogo servidor compartido.
17. Una causa de catálogo se envía por ID; servidor deriva categoría/causa.
18. “Otra” exige categoría vigente y observación trim no vacía en cliente y servidor.
19. Observación no es globalmente obligatoria para una causa estándar.
20. Valores históricos se muestran/conservan, pero no aparecen como opciones nuevas.
21. Guardar bloquea fila, valida versión/scope/política y cambia sólo tres campos CNC.
22. Guardar devuelve fila/conteos completos; no-op no altera datos ni dispara efectos adyacentes.
23. Confirmación no amplía ni reduce la política normal observada; la excepción S08 no se usa.
24. Cancelar no dispara request; mutación nunca reintenta ni se duplica.
25. Recargar conserva filtros válidos y no presenta una respuesta vieja como actual.
26. 401, 403, 404, 409, 422, 500 y 503 tienen recuperación estable y no exponen excepciones.
27. Carga, vacío real, sin resultados, sólo lectura, catálogo vacío, incompleta e inconsistente se
    distinguen.
28. Todo HTTP React pasa por `cliente.ts`; tipos wire salen sólo de Zod.
29. Cada uno de los tres endpoints tiene prueba PHP de contrato/scope sin DML real.
30. No hay DDL/DML, cambios RLS/schema/grants/usuarios/credenciales ni modificación de KPI.
31. Oscuro/claro, 390/768/1180/1440, teclado, Axe y zoom 200 % conservan capacidad sin overflow.
32. Navegación interna usa rutas reales y preserva atrás/deep link.
33. El corte retira VIEW-37 y sólo ramas/selectores sin consumidores; S11 sigue funcional.
34. Consola/red quedan limpias y guardar emite exactamente una solicitud.

## Entregas verticales

### Entrega 1 — Dominio, contexto y lectura

Resolver población/prioridad/diagnóstico/cantidades, catálogo compartido, acciones, contexto y lista
tipada. React muestra header, tabla/tarjetas, carga y vacío sin mutaciones.

### Entrega 2 — Exploración operativa

Semana T01, navegación de secciones, búsqueda, filtros, conteos, recarga, detalle responsive y
leyenda específica de CNC.

### Entrega 3 — Edición individual

Catálogo/Otra/histórico/observación, validación, lock/version, feedback y persistencia interceptada.

### Entrega 4 — Calidad y corte

RBAC, aislamiento, a11y, oscuro/claro, viewports, recuperación, rollback, ruta canónica y retiro
exclusivo.

## Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| Confundir bandeja operativa con KPI BI | nombres/conteos propios y prueba que el diccionario BI no cambia |
| Ocultar una CNC incompleta | unión por incumplimiento actual y diagnóstico explícito |
| Incluir TNP accidental | exclusión `Es_TNP=1` probada antes de evaluar rastro |
| Mezclar causas de áreas | área servidor, IDs y servicio compartido S09 |
| Duplicar catálogo S09 | extracción común cubierta por pruebas de ambos módulos |
| Perder valor histórico | `keep-current`, badge y no insertar en catálogo |
| Sobrescribir cambio concurrente | versión opaca, lock y 409 con borrador conservado |
| Confundir Id con PK | `rowId` exclusivo para mutar e identidades separadas |
| Ampliar permisos desde React | acciones servidor y guards repetidos en endpoint/servicio |
| Editar cantidades desde la bandeja | DTO de sólo lectura y payload allowlist sin cantidades |
| Romper S11 | retiro por cero consumidores; mantener CIC/assets compartidos |
| Tests cambian la obra | fakes PHP y red Playwright totalmente interceptada |
| Color ambiguo entre diagnóstico/prioridad | ejes separados, texto/icono y tokens semánticos |

## Decisiones descartadas

- Copiar literalmente `Activa=1 AND Categoria_CNC IS NOT NULL`.
- Usar el KPI BI como población editable y ocultar clasificaciones incompletas.
- Cambiar la fórmula/linaje de `pg_cnc_activity_count` desde S10.
- Reutilizar `/api/cnc/list` con un Zod permisivo sobre `SELECT *`.
- Reutilizar `/api/cnc/reasons` enviando categoría/área libre desde el navegador.
- Crear un segundo catálogo distinto al de S09.
- Mantener ocho categorías fijas para Pre-Construcción.
- Hacer obligatoria toda observación; contradice S08/API observados.
- Normalizar automáticamente “Otra.../Otros...” o categorías históricas durante lectura.
- Permitir edición de avance, PAC, empresa, responsable o asignaciones.
- Añadir reprogramación, drawer, export, batch, borrado o CRUD de catálogo.
- Calcular permisos por rol en TypeScript.
- Mantener DataTables oculto detrás de tarjetas.
- Colapsar móvil a tres prioridades.
- Eliminar `legacyCards.js`/CSS/imagen completos antes de S11/cero consumidores.
- Probar con mutaciones reales y luego “restaurarlas”.

## Decisiones pendientes

Ninguna decisión de negocio, producto, estrategia o PM bloquea la implementación de S10. La
diferencia entre población operativa y KPI está resuelta explícitamente; hallazgos concretos de
datos históricos se presentan como diagnóstico/histórico y no exigen autocorrección ni pregunta.

## Siguiente gate

Invocar `superpowers:writing-plans` para escribir
`docs/superpowers/plans/2026-08-30-s10-cnc-react.md`, autorrevisarlo contra los 34 criterios y no
implementar hasta que el programa documental cierre el plan correspondiente.
