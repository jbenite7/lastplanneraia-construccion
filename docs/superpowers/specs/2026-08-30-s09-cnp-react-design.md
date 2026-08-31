---
capa: fuente
tipo: spec
estado: vigente
id: S09
fecha: 2026-08-30
superficie: cnp
rutas: ["/programacion-semanal/cnp"]
depende_de: [T01, S08]
views: [VIEW-38]
areas: [lps, design-system]
fuente: "auditoría de public/index.php, ProgramacionSemanalController::cnp, CnpApiController, CncApiController::reasons, LpsWeekEditPolicy, RbacCatalog/RbacService, RestrictionConfigResolver, VIEW-38, legacyCards.js, programacion-semanal.css, general_cnc, parches/fixtures, manifiestos y pruebas CNP en shell-minimo-react, 2026-08-30"
resumen: "Migración vertical S09 de Causas de No Programación a React: contexto y semana del shell, catálogo por área, lista, filtros/conteos, cuatro prioridades, tabla y tarjetas editables, edición individual y reprogramación transaccional, oscuro/claro y corte strangler, sin cambiar RLS, schema ni datos durante la fase documental."
---

# S09 — Causas de No Programación en React

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
- [[docs/security/rls-runtime-boundary|Frontera runtime de RLS]].

T01 posee sesión, proyecto, semana, sidebar, tema y navegación. S08 posee la programación semanal,
la identidad canónica de sus filas y el flujo que envía una actividad a CNP. S09 posee la
superficie satélite que explica por qué una actividad no quedó programada y permite devolverla al
plan. S10 posee CNC y S11 CIC; S09 no adelanta sus contratos ni elimina sus dependencias legacy.

VIEW-38 es `views/programacion-semanal/CNP.view.php` y pertenece únicamente a S09. El archivo
`public/js/modules/programacion_semanal/legacyCards.js` y
`public/css/programacion-semanal.css` son compartidos con S10/S11: al cortar S09 sólo se pueden
retirar sus ramas y selectores CNP después de una búsqueda de consumidores; los archivos completos
permanecen hasta que su último consumidor haya migrado.

## Resultado buscado

`/programacion-semanal/cnp` será una superficie React que conserva toda capacidad útil y
comportamiento observable de la vista PHP/JavaScript actual:

1. usa el proyecto y la semana activos del shell y muestra sus fechas y confirmación;
2. carga exclusivamente las actividades `Activa=0` del proyecto y semana activos;
3. presenta identidad, actividad, descripción, ubicación, empresa, profesional AIA, liberación,
   clasificación CNP y observaciones sin HTML ejecutable;
4. distingue las cuatro combinaciones de atraso y criticidad con texto, tokens y leyenda;
5. permite buscar, combinar filtros y conocer total, visibles y conteos por prioridad;
6. usa tabla semántica en desktop/tablet y tarjetas con las mismas acciones en móvil;
7. edita individualmente profesional, categoría/causa dependiente y observaciones;
8. conserva valores históricos que ya no estén en catálogos sin convertirlos en opciones nuevas;
9. permite reprogramar con confirmación cuando la semana no está confirmada;
10. guarda y reprograma con CSRF, proyecto/semana servidor, política de semana y conflicto
    concurrente;
11. recarga después de éxito o error recuperable sin duplicar peticiones ni perder filtros;
12. navega a Programación Semanal, CNP, CNC y CIC mediante rutas reales, nunca mediante
    `cambiar_pagina.php`;
13. maneja carga, vacío real, filtros sin resultados, catálogo vacío, sólo lectura, 401, 403, 409,
    422 y 500;
14. ofrece capacidad equivalente en oscuro y claro, teclado, zoom, lector de pantalla y touch.

Paridad no obliga a conservar DataTables, jQuery, Bootstrap, Select2, Font Awesome, globals de
sesión, HTML inyectado, prefijos de tabla enviados por el navegador, endpoints con aliases de
campos ni errores que exponen excepciones. React conserva intención, datos, permisos, efectos y
salidas y corrige límites de seguridad, consistencia, accesibilidad y recuperación.

## Alcance

### Incluido

- Ruta piloto y ruta canónica React de CNP.
- VIEW-38, incluido su editor y diálogo de reprogramación.
- Contexto tipado de proyecto, área, semana, acciones, catálogos, navegación y CSRF.
- Lista explícita de campos; no `SELECT *`, HTML ni tipos ambiguos.
- Cuatro estados CNP derivados de `Atrasada` y `Critica`.
- Búsqueda normalizada y filtros por estado, categoría, profesional, empresa y liberación.
- Conteo total, visible y por estado; restablecimiento de filtros.
- Tabla desktop/tablet y tarjetas móviles con paridad de lectura y acciones.
- Profesional editable; empresa y subcontratista informativos y filtrables, no editables.
- Categoría/causa dependiente del área y observaciones.
- Preservación explícita de profesional o clasificación histórica fuera de catálogo.
- Guardado individual con respuesta de fila completa y versión nueva.
- Reprogramación transaccional, confirmación, retiro inmediato de CNP y recarga.
- Navegación interna con rutas React/legacy coexistentes según el corte de S08/S10/S11.
- Oscuro/claro, foco, live regions, reduced motion, zoom 200 % y targets táctiles.
- Contratos PHP, Zod, pruebas puras y navegador con red completamente interceptada.
- Convivencia legacy durante piloto y retiro exclusivo después del corte canónico.

### Fuera de alcance

- `/admin/` y cualquier ruta, permiso, vista, estilo o dependencia administrativa.
- Cambiar RLS, `ProjectScope`, schema, migraciones, tablas, columnas, índices, triggers, grants,
  usuarios, credenciales, membresías, roles, overrides o datos.
- Ejecutar DDL/DML durante esta fase documental o durante las verificaciones prescritas por el plan.
- Crear, reparar, poblar o normalizar `general_cnc`, profesionales o semanas en runtime.
- Cambiar la definición de una actividad CNP (`Activa=0`) o de reprogramación manual.
- Hacer editable `Empresa` o `Sub_Contratista`; sus dueños funcionales son S08/S14.
- Crear profesionales desde el editor; S13 posee el catálogo.
- Migrar CNC o CIC; pertenecen a S10 y S11.
- Integrar el drawer T02: CNP legacy no lo ofrece y S08 ya posee el contexto ampliado de actividad.
- Exportar CSV/XLSX, descargar corte o guardar por lote: VIEW-38 no ofrece esas capacidades y el
  atlas aprobado no las asigna a S09.
- Agregar borrado, deshacer reprogramación, creación de causas o edición del catálogo global.
- Ordenar automáticamente por urgencia: se preserva un orden estable de origen y los filtros hacen
  explícita la prioridad.
- Regenerar o aprobar goldens visuales sin autorización explícita.

## Punto de partida medido

### React

- No existe ruta, página, módulo, esquema Zod, gateway ni dominio CNP.
- La sidebar navega a la vista legacy.
- T01 ya dispone de proyecto, semana, tema y sesión; S09 no crea un segundo selector ni una matriz
  local de roles.
- `frontend/src/lib/api/cliente.ts` es la única frontera HTTP permitida.
- S08 define la identidad semanal que S09 reutiliza: `rowId`, `sourceActivityId` y `activityId` no
  se vuelven a mezclar.

### Legacy

| Pieza | Medición auditada |
|---|---|
| Vista | VIEW-38, 753 líneas |
| Controlador API CNP | 165 líneas |
| Controlador de página compartido | 199 líneas |
| Tarjetas responsive compartidas | `legacyCards.js`, 435 líneas |
| Presentación compartida | `programacion-semanal.css`, 3.814 líneas |
| Grid | DataTables, sin paginación ni ordenamiento |
| Responsive | tarjetas por debajo de 1180 px, tabla por encima |
| Endpoint de causas | `/api/cnc/reasons`, compartido y con nombre CNC |
| Evidencia | tests CNP desktop, tablet, móvil, edición y reprogramación con datos reales |

La vista carga jQuery 1.12, Bootstrap, DataTables, jQuery UI, Google Charts, AnyChart y Select2,
aunque CNP no necesita gráficos. Inyecta proyecto, prefijo, semana, rol, máximo y confirmación en
inputs ocultos; consulta profesionales dentro de la vista con errores silenciados; genera botones y
leyenda como HTML; y usa rutas procedurales para cambiar de sección.

### Defectos y contradicciones observados

1. La página sólo exige autenticación; la API sí exige `lps.cnp.ver`. La ruta React exigirá la
   capacidad de lectura desde el primer byte.
2. La lista usa `SELECT *`, acepta `db` del navegador aunque luego lo ignora y entrega nombres/tipos
   de almacenamiento directamente.
3. Guardar acepta aliases CNC (`Categoria_CNC`, `CNC`) en una mutación CNP.
4. Categoría y causa sólo se validan como texto no vacío; no se comprueba pertenencia al catálogo.
5. El endpoint de causas recibe `area`, pero no la usa.
6. La vista hardcodea ocho categorías de Construcción para todos los proyectos, mientras el
   catálogo global y Programación Semanal distinguen Construcción de Pre-Construcción.
7. El parche histórico usa valores como `Disenos`, `Modelacion` o `Tramites`; la UI hardcodeada usa
   tildes y puede pedir una categoría que no coincide literalmente.
8. Desktop y móvil calculan permisos desde roles/semana en JavaScript; el servidor vuelve a decidir.
9. Los errores incluyen texto de excepción y las respuestas exitosas sólo dicen `BIEN`.
10. La reprogramación sí bloquea la semana confirmada dentro de una transacción; la edición de la
    causa permanece permitida cuando la política histórica de semana la autoriza.
11. La tabla no tiene orden SQL explícito y puede cambiar de orden entre recargas.
12. Empresa existe en la fila y en el atlas aprobado, pero VIEW-38 no la muestra.

S09 corrige esos defectos sin cambiar permisos, significado de estados ni efectos persistentes.

## Comportamiento observable auditado

### Carga, semana y población

`ProgramacionSemanalController::cnp()` sincroniza `semana` desde el request, sanea el contexto y
redirige a `/proyectos` si el prefijo es inválido o a `/programa-general-actualizar` si no hay
semanas. Carga semana máxima, confirmación y semanas del shell.

`POST /api/cnp/list` consulta `programacion_semanal` por `project_id`, `Semana` y `Activa=0`. Una
actividad puede llegar incompleta —sin categoría, causa, profesional u observación— y sigue siendo
visible. El valor `Prog_Sin_Restricciones_100=0` significa **liberada: sí**; `1` significa **no**;
vacío se presenta como desconocido.

### Tabla, tarjetas y búsqueda

Desktop muestra acciones, Id, actividad, liberación, profesional, categoría, causa y observaciones.
Descripción y ubicación están en el contrato pero ocultas. No hay paginación ni ordenamiento.
DataTables muestra información total/filtrada y una búsqueda global con placeholder “Buscar
actividad o causa”.

Por debajo de 1180 px, `legacyCards.js` oculta visualmente la tabla aún montada y crea tarjetas con
Id, actividad, profesional, liberación, categoría, causa y observaciones. La búsqueda filtra
DataTables y vuelve a renderizar tarjetas. S09 sustituye la duplicación por un único store normalizado:
tabla a partir de 768 px y tarjetas bajo 768 px.

### Edición

La edición legacy reemplaza celdas o abre un panel móvil. Ofrece un profesional activo, categoría,
causa dependiente y observaciones. Categoría y causa son obligatorias; profesional y observación
son opcionales. Guardar actualiza únicamente:

- `Responsable_AIA`;
- `Categoria_CNP`;
- `CNP`;
- `Observaciones_CNP`.

La fila debe seguir en el mismo proyecto, semana y `Activa=0`. Un guardado sin cambios es válido.
Después de éxito la tabla se recarga conservando su posición. S09 devuelve la fila completa y
actualiza el store sin una segunda petición; una recarga explícita demuestra persistencia.

### Reprogramación

La acción pide confirmación con el nombre de la actividad. El servidor:

1. exige `lps.cnp.editar`, CSRF y `LpsWeekEditPolicy`;
2. inicia transacción;
3. bloquea la fila de `semanas_activas`;
4. rechaza `Semanal_Confirmada=1` con 409;
5. actualiza una sola fila CNP del proyecto/semana;
6. establece `Activa=1` y `Reprogramada_Por_Usuario=1`;
7. limpia categoría, causa y observaciones CNP;
8. conserva profesional, empresa y demás datos;
9. confirma o revierte la transacción.

No exige que la clasificación esté completa. Esa ausencia de bloqueo es intencional: reprogramar
es una decisión manual que devuelve la actividad al plan y `ProgramChangeDetector` respeta el flag
para no desactivarla automáticamente. S09 no añade una precondición nueva.

### Vacío y leyenda

El vacío real dice: “Sin causas de no programación esta semana. Se registran al eliminar o
reprogramar actividades desde Programación Semanal.” El vacío por filtros dice que no hay registros
para los filtros actuales.

La leyenda posee, en este orden observable:

1. Crítica por programar;
2. No crítica por programar;
3. Atrasada crítica por programar;
4. Atrasada no crítica por programar.

Cada elemento incluye explicación y acción sugerida. Los cuatro colores son distintos, pero S09
añade siempre nombre e icono/indicador no cromático.

## Modelo de dominio canónico

### Identidad y campos

| Campo React | Fuente | Regla |
|---|---|---|
| `rowId` | `row_id` | entero positivo; identidad de mutación |
| `sourceActivityId` | `Consecutivo_En_Programa` | identidad compartida con S08 |
| `activityId` | `Id` | texto visible, nunca usado como PK |
| `activityCode` | `codigo_actividad` | texto nullable |
| `activity` | `Actividad` | texto plano; se elimina markup legado |
| `description` | `Descripcion` | texto plano nullable |
| `location` | `Ubicacion` | texto plano nullable |
| `company` | `Empresa` | sólo lectura y filtro |
| `subcontractor` | `Sub_Contratista` | sólo lectura; separado de empresa |
| `responsible` | `Responsable_AIA` | profesional activo, histórico o vacío |
| `released` | `Prog_Sin_Restricciones_100` | `0=true`, `1=false`, otro/null=`null` |
| `critical` | `Critica` | booleano, null equivale a false |
| `overdue` | `Atrasada` | booleano, null equivale a false |
| `classification` | `Categoria_CNP` + `CNP` | catálogo, histórica, incompleta o vacía |
| `observations` | `Observaciones_CNP` | texto nullable |
| `version` | hash de campos mutables/estado | 64 hex, opaco para el cliente |

El servidor elimina etiquetas y normaliza espacios para presentación, pero no reescribe en base de
datos el texto histórico durante una lectura. `company` no cae silenciosamente a `subcontractor`:
ambos valores se conservan para que la UI explique qué fuente está mostrando.

### Cuatro estados CNP

| ID | `overdue` | `critical` | Etiqueta |
|---|---:|---:|---|
| `cnp-critical` | no | sí | Crítica por programar |
| `cnp-non-critical` | no | no | No crítica por programar |
| `cnp-overdue-critical` | sí | sí | Atrasada crítica por programar |
| `cnp-overdue-non-critical` | sí | no | Atrasada no crítica por programar |

`CnpPriorityResolver` en PHP es la autoridad y devuelve ID, etiqueta, descripción, acción sugerida
y token semántico. React sólo presenta el ID validado. La lista conserva orden estable por
`Consecutivo_En_Programa`, `row_id`; no reordena por gravedad. Filtros o una selección explícita
permiten aislar la prioridad.

## Catálogos por área y valores históricos

### Área autoritativa

El navegador no envía `area`. El servidor la resuelve desde el proyecto activo mediante la misma
fuente canónica de S07/S08 (`RestrictionConfigResolver` o su sucesor). Los valores válidos siguen
siendo `Construccion` y `Pre-Construccion`.

`general_cnc` es una taxonomía global, no una tabla operativa por proyecto. El store consulta sus
campos explícitos y filtra por el área existente en el catálogo; si el schema canónico requerido no
está presente, devuelve `CATALOG_SCHEMA_PREREQUISITE_MISSING` y nunca ejecuta DDL de reparación.

### Categorías

Construcción conserva las categorías existentes del catálogo, incluidas las que ya consume legacy:

- Rendimiento;
- Programación;
- Mano de Obra;
- Materiales;
- Equipos;
- Diseños;
- Administrativas;
- Causas Exógenas.

Pre-Construcción usa únicamente las categorías de su catálogo:

- Diseños/`Disenos`;
- Modelación/`Modelacion`;
- Presupuesto;
- Contratación/`Contratacion`;
- Trámites/`Tramites`.

El DTO separa `categoryKey`, `storedValue` y `label`. La clave normalizada agrupa variantes de
acentos y casing; `storedValue` preserva el valor real del catálogo que se persistirá; `label` es el
texto humano. Las causas conservan también `id`, `storedValue` y `label`. El frontend nunca arma una
pareja libre de categoría/causa: selecciona una causa por `id` y PHP deriva ambos textos.

### Preservación histórica

Una fila puede contener un profesional inactivo o una pareja categoría/causa inexistente en el
catálogo actual. S09 muestra el valor con la marca “Histórico” y permite:

- conservarlo sin cambio mediante `mode=keep-current`;
- limpiar o sustituir el profesional;
- sustituir la clasificación por una causa vigente.

No lo añade al catálogo ni lo ofrece para otras filas. `keep-current` sólo es válido si el valor
actual existe y la versión coincide. Una fila sin categoría o causa debe elegir una causa vigente
antes de guardar, igual que la validación legacy de texto no vacío.

## Inventario HTTP auditado

| Método | Ruta | Comportamiento actual | Destino S09 |
|---|---|---|---|
| GET | `/programacion-semanal/cnp` | auth, contexto PHP, VIEW-38 | ruta canónica React con `lps.cnp.ver` |
| POST | `/api/cnp/list` | `SELECT *`, `{data: rows}` | alias temporal de lectura; retirar al corte |
| POST | `/api/cnp/save` | aliases, CSRF, update de cuatro campos | delegar temporalmente; retirar al corte |
| POST | `/api/cnp/reprogramar` | transacción y respuesta `BIEN` | delegar temporalmente; retirar al corte |
| POST | `/api/cnc/reasons` | causas por categoría, ignora área | S10 legacy lo conserva; S09 deja de consumirlo |

No se reutiliza `/api/cnc/reasons` en React: su nombre, forma y ausencia de alcance por área no
representan el contrato CNP.

## Contratos HTTP nuevos

S09 añade exactamente cuatro endpoints. Todos derivan proyecto, prefijo, área y semana de
sesión/scope; rechazan parámetros desconocidos; responden JSON UTF-8 sin mensajes de excepción; y
tienen esquema Zod y prueba PHP de contrato.

| Método | Ruta | Capacidad | CSRF | Efecto |
|---|---|---|---|---|
| GET | `/api/cnp/context` | `lps.cnp.ver` | no | lectura pura |
| GET | `/api/cnp/activities` | `lps.cnp.ver` | no | lectura pura |
| POST | `/api/cnp/activity` | `lps.cnp.editar` | header | update individual |
| POST | `/api/cnp/reprogram` | `lps.cnp.editar` | header | reactivación transaccional |

### Contexto

`GET /api/cnp/context` devuelve:

```json
{
  "data": {
    "project": { "id": 73, "name": "Da Porto", "area": "Construccion" },
    "week": {
      "number": 7,
      "startDate": "2026-08-24",
      "endDate": "2026-08-30",
      "maxNumber": 8,
      "confirmed": false
    },
    "actions": {
      "edit": { "allowed": true, "reasonCode": null },
      "reprogram": { "allowed": true, "reasonCode": null }
    },
    "csrfToken": "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa",
    "professionals": [
      { "id": 17, "name": "Ana Pérez" }
    ],
    "causeCatalog": [
      {
        "categoryKey": "programacion",
        "storedValue": "Programación",
        "label": "Programación",
        "causes": [
          { "id": 41, "storedValue": "Coordinación pendiente", "label": "Coordinación pendiente" }
        ]
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

Cada acción efectiva es `{allowed, reasonCode}`. Razones posibles: `READ_ONLY`,
`EDIT_WINDOW_CLOSED`, `WEEK_CONFIRMED`, `CATALOG_UNAVAILABLE` y `null`. `csrfToken` es `null` si no
hay ninguna acción mutable. Las secciones se resuelven en servidor; React no deduce visibilidad
desde un rol.

### Actividades

`GET /api/cnp/activities` devuelve `rows` y conteos de la población completa:

```json
{
  "data": {
    "rows": [
      {
        "rowId": 311,
        "sourceActivityId": 92,
        "activityId": "4.2.1",
        "activityCode": "EST-001",
        "activity": "Fundir placa nivel 2",
        "description": "Placa sector oriental",
        "location": "Torre 1",
        "company": "AIA",
        "subcontractor": "Concretos SAS",
        "responsible": {
          "name": "Ana Pérez",
          "professionalId": 17,
          "historical": false
        },
        "released": false,
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
        "state": {
          "id": "cnp-overdue-critical",
          "label": "Atrasada crítica por programar",
          "token": "critical"
        },
        "actions": {
          "edit": { "allowed": true, "reasonCode": null },
          "reprogram": { "allowed": true, "reasonCode": null }
        },
        "version": "bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb"
      }
    ],
    "counts": {
      "total": 1,
      "critical": 0,
      "nonCritical": 0,
      "overdueCritical": 1,
      "overdueNonCritical": 0,
      "incomplete": 0
    }
  }
}
```

`rows=[]` es un vacío válido. No se devuelve `project_id`, `db`, rol, SQL, HTML, `Activa` ni los
nombres de columna legacy. `version` es un hash SHA-256 opaco de proyecto, semana, identidad,
estado activo y campos mutables; no reemplaza autorización. El servidor lo recalcula dentro del
lock de cada mutación.

### Guardado individual

`POST /api/cnp/activity` recibe JSON estricto y `X-CSRF-Token`:

```json
{
  "rowId": 311,
  "version": "bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb",
  "responsible": { "mode": "professional", "professionalId": 17 },
  "classification": { "mode": "catalog", "causeId": 41 },
  "observations": "Ajustar secuencia"
}
```

Las uniones válidas son:

- `responsible`: `{mode:"professional", professionalId}`, `{mode:"keep-current"}` o
  `{mode:"clear"}`;
- `classification`: `{mode:"catalog", causeId}` o `{mode:"keep-current"}`.

PHP resuelve profesional y causa dentro del proyecto/área activos, deriva categoría y textos,
verifica límites reales (`Responsable_AIA` 200, categoría/causa 100 y capacidad `MEDIUMTEXT`),
bloquea la fila, comprueba versión/proyecto/semana/`Activa=0`, actualiza cuatro campos y devuelve la
fila normalizada completa con nueva versión y conteos. No acepta nombres libres, semana, área,
proyecto, categoría ni causa desde el navegador.

### Reprogramación

`POST /api/cnp/reprogram` recibe:

```json
{
  "rowId": 311,
  "version": "bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb"
}
```

El servicio bloquea semana y fila en orden fijo, revalida permisos/política/confirmación/versión y
aplica exactamente la mutación legacy. La respuesta es:

```json
{
  "data": {
    "rowId": 311,
    "removedFromCnp": true,
    "reactivated": true,
    "counts": {
      "total": 0,
      "critical": 0,
      "nonCritical": 0,
      "overdueCritical": 0,
      "overdueNonCritical": 0,
      "incomplete": 0
    }
  }
}
```

Una repetición con la misma versión devuelve conflicto, no éxito ficticio. No existe retry
automático de una mutación.

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
| 403 | `CNP_FORBIDDEN` | falta capacidad de ver/editar |
| 403 | `EDIT_WINDOW_CLOSED` | `LpsWeekEditPolicy` deniega |
| 403 | `CSRF_INVALID` | token ausente o inválido |
| 404 | `ROW_NOT_FOUND` | fila fuera del scope o ya no CNP |
| 409 | `ROW_STALE` | versión cambió |
| 409 | `WEEK_CONFIRMED` | reprogramación en semana confirmada |
| 409 | `CATALOG_VALUE_STALE` | causa/profesional dejó de ser válido |
| 422 | `VALIDATION_FAILED` | JSON/campos/modos/límites inválidos |
| 503 | `CATALOG_SCHEMA_PREREQUISITE_MISSING` | catálogo canónico no disponible |
| 500 | `CNP_UNAVAILABLE` | error interno sin detalles sensibles |

El cliente común debe conservar status, código y `fields` para que la UI distinga recuperación,
conflicto, sesión y validación. El mensaje no se usa como lógica.

## Permisos y capacidades

### Matriz fallback observada

| Rol canónico | Ver CNP | Editar CNP | Nota |
|---|---:|---:|---|
| A | sí | sí | toda semana por política LPS |
| D | sí | sí | toda semana por política LPS |
| R | sí | sí | sólo ventana histórica permitida |
| DCV | sí | sí | sólo ventana histórica permitida |
| OT | sí | no | lectura |
| V | sí | no | fallback de lectura global |
| G/S/SG | no | no | sus permisos LPS se concentran en CIC |
| C | no | no | sin CNP en fallback |

Esta matriz documenta el fallback, no se serializa al frontend. Overrides persistidos pueden cambiar
el resultado; `RbacManager/RbacService` y las capacidades efectivas siempre mandan.

### Política de semana y confirmación

- `lps.cnp.ver` protege página, contexto y lista.
- `lps.cnp.editar` protege ambas mutaciones.
- `LpsWeekEditPolicy` se revalida al resolver acciones y dentro de cada mutación.
- A/D pueden editar cualquier semana; R/DCV sólo semanas con `maxWeek - 2 < week` según la política
  vigente; overrides siguen aplicando.
- Editar la clasificación permanece permitido en una semana confirmada si la política histórica
  permite esa semana. Es corrección de información CNP, no reactivación del plan.
- Reprogramar exige además `confirmed=false`; cambia `Activa` y por ello sigue bloqueado en una
  semana confirmada.
- La UI oculta o deshabilita con razón, pero el servidor nunca confía en esa presentación.

## Filtros, conteos y orden

La lista se carga completa para la semana, como legacy. React aplica localmente filtros combinables:

- búsqueda sin distinción de mayúsculas/acentos sobre Id, código, actividad, descripción,
  ubicación, empresa, subcontratista, profesional, categoría, causa y observaciones;
- estado CNP, con selección múltiple;
- categoría vigente o histórica;
- profesional, incluido “Sin profesional”;
- empresa;
- liberación: todas, liberada, no liberada, desconocida;
- clasificación: completa o incompleta.

El encabezado muestra `N de T actividades` y chips de los cuatro estados. Los conteos de servidor
describen el total de la semana; el selector muestra también el conteo visible después de filtros.
“Borrar filtros” restablece búsqueda y filtros, conserva semana y devuelve foco a búsqueda.

No hay paginación ni virtualización en S09. Si la población medida durante implementación demuestra
que el DOM es insuficiente, será una decisión de rendimiento separada; no se inventa paginación
server-side sin contrato legacy.

## Arquitectura React

### Módulo

```text
frontend/src/modules/cnp/
  CnpPage.tsx
  useCnp.ts
  dominio/
    filtrarCnp.ts
    normalizarCnp.ts
  componentes/
    CabeceraCnp.tsx
    NavegacionSemanal.tsx
    FiltrosCnp.tsx
    ConteosCnp.tsx
    LeyendaCnp.tsx
    TablaCnp.tsx
    TarjetasCnp.tsx
    EditorCnp.tsx
    DialogoReprogramarCnp.tsx
    EstadoCnp.tsx
  cnp.css
```

`frontend/src/lib/api/esquemas/cnp.ts` contiene los únicos tipos wire mediante `z.infer`.
`frontend/src/lib/api/cnp.ts` es el gateway y la única capa del módulo que llama `pedir()`; ni
componentes ni hooks llaman `fetch`.

`useCnp` mantiene contexto, filas normalizadas, filtros, selección/editor, petición activa y
feedback. Sólo puede existir una edición por vez. Un cambio de proyecto/semana cancela lecturas,
cierra editores, descarta respuestas del contexto anterior y vuelve a cargar. Las mutaciones se
serializan por fila y no se reintentan automáticamente.

### Layouts

- `>= 1180 px`: tabla completa, toolbar en una fila y detalle secundario accesible.
- `768–1179 px`: tabla compacta sin scroll horizontal; descripción, ubicación, empresa y
  observaciones pasan a un panel de detalle de fila.
- `< 768 px`: tarjetas nativas; no existe una tabla oculta montada.

Tabla y tarjeta usan los mismos DTO, acciones y `EditorCnp`. Editar desde una tarjeta expande el
editor inmediatamente después de ella; en tabla abre un diálogo etiquetado con Id/actividad. Ambas
variantes permiten profesional, categoría/causa, observaciones, guardar y cancelar. Reprogramar usa
el mismo diálogo de confirmación en todos los layouts.

## Flujos de mutación

### Editar clasificación

1. El usuario activa Editar en una fila permitida.
2. El editor precarga profesional y clasificación; un valor fuera de catálogo aparece como
   histórico y no se propaga a otras filas.
3. Cambiar categoría limpia la causa y anuncia que debe elegirse una nueva.
4. Guardar valida forma, causa obligatoria y límites en cliente.
5. El gateway envía la unión por IDs y la versión con CSRF header.
6. Durante la petición sólo esa fila queda ocupada; Cancelar no dispara request.
7. Éxito reemplaza la fila por el DTO servidor, conserva filtros y anuncia guardado.
8. 422 enfoca el primer campo; 409 conserva el borrador y ofrece Recargar/Cancelar; 403 cierra el
   editor y actualiza contexto; 401 delega recuperación de sesión a T01.

### Reprogramar

1. La acción sólo aparece cuando la acción efectiva de fila lo permite.
2. El diálogo explica que la actividad volverá a Programación Semanal y se limpiará su CNP.
3. Cancelar cierra y restaura foco sin request.
4. Confirmar envía una sola petición con rowId/versión/CSRF.
5. Éxito retira la fila del store, actualiza conteos y anuncia el resultado.
6. Si era la última fila, aparece el vacío real con enlace autorizado a Programación Semanal.
7. Conflicto o semana confirmada dejan los datos intactos y ofrecen recarga.

## Estados de experiencia

| Estado | Presentación y recuperación |
|---|---|
| carga inicial | skeletons de toolbar y filas/tarjetas, `aria-busy=true` |
| vacío real | mensaje histórico, explicación y enlace autorizado a S08 |
| sin resultados | filtros activos, conteo 0, botón Borrar filtros |
| sólo lectura | datos completos, badge y acciones mutables ausentes con explicación |
| catálogo vacío | lectura disponible; editor bloqueado, reprogramación independiente |
| sin profesionales | opción Vacío y valores históricos; no se crean profesionales |
| error de lectura | panel con código amistoso y Recargar |
| guardando | fila ocupada y anuncio; resto de lectura disponible |
| éxito | anuncio `role=status`, fila/conteos actualizados |
| validación | errores asociados a controles y resumen accesible |
| conflicto | borrador conservado, Recargar/Cancelar; sin overwrite silencioso |
| sesión vencida | recuperación T01 con retorno a la ruta |
| prohibido | pantalla 403 del shell, sin flash de datos |

## Accesibilidad, tema y design system

- `h1` visible: “Causas de No Programación”.
- Proyecto, semana, fechas y confirmación tienen texto, no sólo chips cromáticos.
- Tabla con `caption`, `scope`, headers y nombre accesible de cada acción.
- Tarjetas como `article` con encabezado, estado textual y grupos semánticos.
- Leyenda abre/cierra por teclado, restaura foco y mantiene el orden histórico.
- Editor y confirmación tienen foco inicial, trampa, Escape, Cancelar y retorno al disparador.
- Errores se enlazan con `aria-describedby`; cambios de causa y feedback usan live regions.
- Targets de al menos 44×44 CSS px; navegación y acciones no dependen de hover.
- A 200 % no hay pérdida ni scroll horizontal de página; el layout puede converger a tarjetas.
- `prefers-reduced-motion` elimina desplazamiento suave y transiciones no esenciales.
- Dark es default/fallback y light conserva contraste, foco, estados y capacidad idéntica.
- Todo color, radio, sombra, espacio y estado sale de `public/css/tokens.css`. Si falta un alias
  semántico CNP, se añade/documenta en tokens y `state-semantics.json`; el módulo no usa hex,
  `rgba()`, estilos inline ni `!important`.

Viewports obligatorios: `390×844`, `768×1024`, `1180×820` y `1440×900`, en oscuro y claro para los
escenarios funcionales principales. No se actualiza un golden aprobado sin autorización.

## Seguridad y RLS

S09 consume la frontera RLS ya documentada; no la modifica.

- Proyecto, prefijo, área y semana provienen del scope/sesión servidor.
- Toda consulta a `programacion_semanal`, `semanas_activas` y `profesionales` incluye
  `project_id = ?` y usa prepared statements.
- `general_cnc` es catálogo global; sólo se devuelve la partición del área autoritativa.
- Página y endpoints fallan cerrados si no se resuelve proyecto/membresía/capacidad.
- Mutaciones bloquean y verifican fila por `project_id + row_id + Semana + Activa=0`.
- No se acepta `db`, `projectId`, `area`, `week`, rol, `Activa`, categoría textual ni causa textual
  del cliente.
- CSRF viaja en header y se valida antes de abrir transacción.
- La versión evita overwrite accidental, pero nunca concede permiso.
- Logs internos pueden registrar código/request ID; respuestas no incluyen SQL, prefijos ni
  excepciones.
- Las lecturas y el montaje React son puros: nunca crean catálogo, corrigen filas ni escriben sesión
  de negocio.

## Estrategia strangler y retiro

1. Construir `/app/programacion-semanal/cnp` con APIs nuevas mientras la ruta canónica sigue en
   VIEW-38.
2. Mantener aliases legacy delegando a los servicios nuevos sólo si las pruebas de paridad los
   necesitan; no mantener dos implementaciones de negocio.
3. Validar contrato, RBAC, semana, cuatro estados, edición, reprogramación, responsive, a11y y
   oscuro/claro con red interceptada.
4. Ejecutar el gate de rollback por ruta.
5. Cambiar `/programacion-semanal/cnp` al host SPA.
6. Retirar VIEW-38, `ProgramacionSemanalController::cnp()`, rutas API legacy y ramas/selectores CNP
   exclusivos después de un `rg` de cero consumidores.
7. Conservar `legacyCards.js`, `programacion-semanal.css`, CncApiController y vistas S10/S11 en las
   partes todavía consumidas.
8. Actualizar manifiestos/inventarios sin marcar S10/S11 como migrados.

El rollback es revertir el commit de corte y devolver la ruta canónica a VIEW-38; los contratos
nuevos son aditivos y no requieren rollback de datos/schema.

## Estrategia de pruebas

### PHP sin DML

- `CnpPriorityResolver`: 4 combinaciones y nulls.
- `CnpActionPolicy`: capacidades, ventana histórica, confirmación y overrides simulados.
- `CnpCatalogService`: áreas, aliases con/sin tildes, IDs, histórico y schema faltante.
- Contexto: shape, proyecto/semana, catálogos, acciones, navegación y ausencia de secretos.
- Actividades: scope, campos explícitos, tipos, estado, versión, orden, vacío y conteos.
- Guardado: profesional/cause IDs, keep/clear, longitudes, no-op, lock, stale, scope y rollback.
- Reprogramación: mutación exacta, semana confirmada, stale, repetición y rollback.
- Rutas: auth, capacidad, método, content type, CSRF y status/código.

Todos usan stores/fakes inyectados y aserciones de llamadas. No conectan a una base real para
escribir. El test existente `tests/test_csrf_modulos_api.php` llama endpoints de mutación reales y
no forma parte de esta verificación documental/segura aunque normalmente falle antes de escribir.

### TypeScript/Vitest

- cuatro esquemas success y esquema de error con `.strict()`;
- gateway: paths, métodos, JSON, header CSRF y cero `fetch` directo;
- normalización, búsqueda sin acentos, filtros y conteos;
- tabla/tarjetas con los mismos datos/acciones;
- valor histórico, dependencias categoría/causa y foco de validación;
- edición success/422/403/409/500, sin retry ni doble submit;
- reprogramación confirmar/cancelar/success/conflict;
- carga, vacíos, sólo lectura, cambio de contexto y respuestas tardías.

### Playwright interceptado

Antes de navegar, cada escenario intercepta `/api/session`, contexto T01, los cuatro endpoints S09
y cualquier alias mutable. Un request no esperado falla la prueba. Fixtures en memoria simulan
guardar, recargar y reprogramar sin tocar MySQL.

Escenarios mínimos:

- lector permitido y rol/capacidad denegada;
- semana editable, histórica cerrada y confirmada;
- Construcción y Pre-Construcción con catálogos correctos;
- cuatro estados, búsqueda, filtros, conteos y leyenda;
- tabla 768/1180/1440 y tarjetas 390;
- editar valor vigente e histórico, recargar mock y conservar;
- cancelar sin request, stale y errores de campo;
- reprogramar una sola vez, retirar fila y vacío final;
- navegación S08/S09/S10/S11 sin ruta procedural;
- oscuro/claro, teclado, Axe, zoom 200 %, reduced motion, red y consola limpias.

No se ejecutan `programacion-semanal-cnp-lifecycle.mjs`, los casos mutables de
`programacion-semanal-subviews.mjs` ni `programacion-semanal-roles-phases.mjs`: escriben/restauran
datos reales y restaurar también es DML.

## Criterios de aceptación

1. `/app/programacion-semanal/cnp` y, tras el gate, la ruta canónica renderizan React.
2. La página y las APIs exigen `lps.cnp.ver`; un denegado no recibe datos.
3. Proyecto, área y semana nunca se aceptan como autoridad del navegador.
4. Sólo se listan filas `Activa=0` del proyecto y semana activos.
5. El DTO no expone `SELECT *`, HTML, prefijo, rol ni columnas innecesarias.
6. Los cuatro estados CNP coinciden con las combinaciones legacy y tienen texto/token/leyenda.
7. Liberación conserva la inversión `0=sí`, `1=no`, null=desconocida.
8. Empresa y subcontratista se muestran por separado y no son editables.
9. Búsqueda, seis grupos de filtros, restablecimiento y conteos funcionan combinados.
10. Tabla desktop/tablet y tarjetas móvil presentan los mismos datos y acciones.
11. Cambiar semana mediante T01 cancela contexto anterior y carga la nueva población.
12. Construcción y Pre-Construcción reciben exclusivamente su catálogo servidor.
13. Una causa se envía por ID; servidor deriva categoría/causa y rechaza parejas inválidas.
14. Valores históricos se muestran y pueden conservarse, pero no aparecen como opciones nuevas.
15. Profesional puede elegirse, conservarse histórico o limpiarse; empresa no cambia.
16. Guardar valida en cliente y servidor, bloquea fila y devuelve DTO/version completos.
17. Guardar sin cambios es válido y no altera otros campos.
18. Editar clasificación sigue permitido en semana confirmada sólo cuando capacidad/política lo
    permiten.
19. Reprogramar está ausente/bloqueado en semana confirmada y se revalida en servidor.
20. Reprogramar aplica `Activa=1`, limpia CNP y fija `Reprogramada_Por_Usuario=1` atómicamente.
21. Cancelar edición o reprogramación no dispara una petición.
22. 401, 403, 404, 409, 422, 500 y 503 tienen recuperación estable y no exponen excepciones.
23. Carga, vacío real, sin resultados, sólo lectura, catálogo vacío y conflicto son distinguibles.
24. Todo HTTP React pasa por `cliente.ts` y los tipos salen sólo de Zod.
25. Cada uno de los cuatro endpoints tiene prueba PHP de contrato y scope.
26. No hay DDL/DML, creación runtime, cambios RLS ni modificación de datos en la verificación.
27. Oscuro/claro, 390/768/1180/1440, teclado, Axe y zoom 200 % conservan capacidad sin overflow.
28. Navegación interna usa rutas reales y preserva atrás/deep link.
29. El corte retira VIEW-38 y sólo ramas/selectores CNP exclusivos; S10/S11 siguen funcionales.
30. Consola/red quedan limpias y cada mutación emite exactamente una solicitud.

## Entregas verticales

### Entrega 1 — Dominio, contexto y lectura

Resolver de cuatro estados, catálogo por área, acciones efectivas, contexto y lista tipada con
scope. React muestra header, tabla/tarjetas, carga y vacío sin mutaciones.

### Entrega 2 — Exploración operativa

Semana T01, navegación de secciones, búsqueda, filtros, conteos, detalle responsive y leyenda.

### Entrega 3 — Edición individual

Profesional/catálogo/histórico/observaciones, validación, lock/version, feedback y persistencia mock.

### Entrega 4 — Reprogramación

Confirmación accesible, política/confirmación, transacción exacta, retiro de fila y conflictos.

### Entrega 5 — Calidad y corte

RBAC, a11y, oscuro/claro, viewports, error recovery, rollback, ruta canónica y retiro exclusivo.

## Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| Mezclar causas de áreas | área servidor, IDs y prueba de catálogo Construcción/PC |
| Perder valores históricos | modo `keep-current` y badge histórico, sin insertarlos al catálogo |
| Reprogramar semana cerrada | lock de semana + confirmación revalidada en transacción |
| Overwrite concurrente | versión opaca, lock y 409 con borrador conservado |
| Confundir `Id` y PK | `rowId` exclusivo para mutar y contratos con identidades separadas |
| Ampliar permisos desde React | acciones servidor y guardas repetidas en endpoint/servicio |
| Romper S10/S11 | retiro selector/rama por cero consumidores, no borrar assets compartidos |
| Depender de schema faltante | error de precondición; nunca DDL/self-heal en request |
| Ocultar datos en tablet | detalle de fila accesible, sin scroll horizontal ni columnas perdidas |
| Tests que cambien la obra | fakes PHP y red Playwright totalmente interceptada |

## Decisiones descartadas

- Reutilizar `/api/cnp/list` y validar `SELECT *` con un esquema permisivo.
- Reutilizar `/api/cnc/reasons` y seguir enviando categoría/área desde el navegador.
- Mantener la lista fija de ocho categorías para Pre-Construcción.
- Convertir automáticamente textos históricos y escribirlos durante la lectura.
- Hacer empresa editable desde CNP.
- Exigir causa completa antes de reprogramar; sería una restricción nueva.
- Bloquear toda edición al confirmar semana; contradice API/UI observadas.
- Calcular permisos por rol en TypeScript.
- Mantener DataTables oculto detrás de tarjetas.
- Añadir drawer, CSV/XLSX, batch, undo o CRUD de causas sin contrato legacy/aprobación.
- Eliminar `legacyCards.js`/CSS completos antes de S10/S11.
- Probar con mutaciones reales y luego “restaurarlas”.

## Decisiones pendientes

Ninguna decisión de negocio, producto, estrategia o PM bloquea la implementación de S09. Hallazgos
de datos concretos fuera del catálogo se manejan con el modo histórico definido; no requieren una
pregunta para construir el módulo.

## Siguiente gate

Invocar `superpowers:writing-plans` para escribir
`docs/superpowers/plans/2026-08-30-s09-cnp-react.md`, autorrevisarlo contra los 30 criterios y no
implementar hasta que el programa documental cierre el plan correspondiente.
