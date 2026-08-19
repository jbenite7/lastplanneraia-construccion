---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-08-04
areas: [lps]
fuente: docs/flujos/lps-programa-general.md
resumen: Escenarios PG- y CRO-. La línea base del proyecto: qué actividades hay, cuándo deberían pasar, y cómo se actualiza cuando la obra se mueve.
---

# Biblia · Cascada LPS · Programa General y actualización del cronograma

Escenarios `PG-*` y `CRO-*`. La línea base del proyecto: qué actividades hay, cuándo deberían pasar,
y cómo se actualiza cuando la obra se mueve.

Formato y reglas: `docs/flujos/README.md`. Verificado por lectura el **2026-08-04**.

---

## Lo primero que hay que saber: aquí mandan los permisos `lps.*`, no las capacidades

Medido el 2026-08-04 sobre `src/Controllers/Api/GeneralApiController.php`: **ninguno de sus
endpoints consulta `canManageGeneralProgram` (llamada `canEditGeneralProgram` hasta el colapso de
alias del 2026-08-10), `canDeleteRows` ni `canEditPastGeneralProgram`**. Todos
autorizan con `authorizePermission('lps.programa_general.<accion>')`, es decir el catálogo de
permisos por clave de `RbacCatalog`, que es un **sistema distinto** de las capacidades booleanas de
`RbacManager` descritas en `docs/flujos/transversal-rbac.md`.

| Endpoint | Permiso exigido |
|---|---|
| `GET/POST /api/general/list` (`:33`) | `lps.programa_general.ver` |
| `POST /api/general/update` (`:139`) | `lps.programa_general.editar` |
| `POST /api/general/update-batch` (`:382`) | `lps.programa_general.editar` |
| `GET /api/general/restriction-config` (`:506`) | `lps.programa_general.ver` |
| `POST /api/general/auto-associate` (`:524`) | `lps.programa_general.editar` |
| `POST /api/general/import` (`:1175`) | `lps.programa_general.editar` |
| Los dos de actualizar cronograma (`:1298`, `:1478`) | `lps.programa_general_actualizar.editar` |

**Consecuencia práctica:** las capacidades booleanas que la interfaz usa para mostrar u ocultar
botones **no son las que el servidor comprueba**. Quien quiera cambiar quién puede editar el
programa general debe tocar el catálogo de permisos, no `RbacManager`; y cualquier razonamiento
sobre permisos que se apoye solo en las capacidades está mirando la mitad del sistema.

## PG-001 · Solo con `lps.programa_general.ver` se listan actividades

- **Roles permitidos:** `A` (comodín `*`), `D` (todos los de lectura y escritura), `R`, y cualquiera
  con esa clave en `fallbackPermissionsByRole()`.
- **Precondiciones:** sesión y proyecto en sesión (`AUTH-*`, `PROY-005`).
- **Pasos:**
  1. `GET /api/general/list` llega a `GeneralApiController::list()`.
  2. `requireAuth()` y después `authorizePermission('lps.programa_general.ver')`.
- **Resultado esperado:** la lista de actividades **del proyecto en sesión y solo de ese**. Un rol
  sin el permiso recibe el rechazo, no una lista vacía: son cosas distintas y no deben confundirse.
- **Verificación:** lectura — `src/Controllers/Api/GeneralApiController.php:30-33`.

## PG-002 · Editar exige `lps.programa_general.editar`

- **Roles permitidos:** `A`, `D`, `R` (el Residente lo tiene explícito en su lista).
- **Precondiciones:** las de `PG-001`, más la fila existente.
- **Pasos:** `POST /api/general/update` autoriza con `lps.programa_general.editar` antes de tocar
  nada.
- **Resultado esperado:** la actividad cambia solo en el proyecto en sesión. Un rol sin el permiso
  es rechazado **antes** de que se escriba.
- **Verificación:** lectura — `GeneralApiController.php:139`.

## PG-003 · La edición por lotes exige el mismo permiso que la individual

- **Pasos:** `POST /api/general/update-batch` autoriza con `lps.programa_general.editar` (`:382`).
- **Resultado esperado:** **no existe** un permiso separado para cambios masivos. Quien puede editar
  una fila puede editar muchas de golpe.
- **Verificación:** lectura — `GeneralApiController.php:382`.

> Conviene tenerlo escrito porque es una decisión con consecuencias: el riesgo de un lote es mayor
> que el de una fila, y hoy comparten llave.

## PG-004 · La importación de Excel es una edición, no una operación aparte

- **Pasos:** `POST /api/general/import` autoriza con `lps.programa_general.editar` (`:1175`).
- **Resultado esperado:** cualquiera que pueda editar puede **reemplazar la línea base entera desde
  un archivo**. En datos: el efecto es masivo.
- **Verificación:** lectura — `GeneralApiController.php:1175`.
- **No comprobable en lectura:** si la importación es destructiva o incremental, y si hay marcha
  atrás. Es la pregunta más importante de este escenario y queda pendiente de medir.

## PG-005 · Actualizar cronograma tiene su propio permiso

- **Roles permitidos:** los que tengan `lps.programa_general_actualizar.editar` — `A`, `D` y `R` lo
  tienen.
- **Resultado esperado:** actualizar la ejecución es una llave **distinta** de editar el programa.
  Un rol podría tener una y no la otra.
- **Verificación:** lectura — `GeneralApiController.php:1298`, `:1478`;
  `src/Security/RbacCatalog.php:98-99`.

## PG-006 · Editar una semana pasada exige ser Admin o Director

Escenario que nació como hallazgo: la restricción existía **solo en el navegador** hasta el
2026-08-06.

- **Roles permitidos:** `A` y `D`. **Denegados:** todos los demás, incluido el Residente, que sí
  puede editar el programa general en general.
- **Precondiciones:** la semana pedida es anterior a la última existente (`semana < MAX(Semana)`).
- **Pasos:**
  1. El endpoint llama a `assertNotPastWeekOrPrivileged($semana, $dbPrefix, $projectId)`.
  2. Calcula `maxWeek` sobre `semanas_activas` del proyecto. Si `semana >= maxWeek`, deja pasar.
  3. Si no, resuelve el rol y consulta `canEditPastGeneralProgram`.
- **Resultado esperado:** **403** con «Editar semanas pasadas del Programa General requiere rol
  Admin o Director», y ninguna escritura.
- **Verificación:** lectura — `GeneralApiController.php:1739-1757`, y **cableado en tres endpoints**
  (`:165` edición, `:403` por lotes, `:1200` importación). Comprobado el 2026-08-07 que la función
  se llama y no solo se declara.

> **Por qué importaba.** La capacidad `canEditPastGeneralProgram` llevaba tiempo definida en
> `RbacManager` y usada en `public/js/rbac_capabilities.js`, pero ningún archivo PHP la consultaba:
> la interfaz escondía el botón y la API aceptaba la petición igual. Es el mismo patrón que
> `canDeleteRows`, que **sigue sin consumidor** y protege algo destructivo.

## CRO-001 · La grilla ocupa el alto disponible sin scroll doble

- **Rol:** cualquiera con acceso a la vista.
- **Resultado esperado:** en el viewport canónico 1180×820, la grilla de actualizar cronograma llena
  el espacio libre bajo la barra de contexto, **sin una segunda barra de scroll** ni zona muerta.
- **Verificación:** no comprobable en lectura. Requiere navegador.

> Al implementarlo, ojo con la trampa ya medida: el `calc(100vh - 49px)` es correcto sobre
> `.hot-full-bleed` y **falso** sobre `#hot-container`
> (`memoria/trampas/hot-container-height-ownership.md`). Eso es implementación; el escenario solo
> exige el resultado visible.

---

## Escenarios pendientes de esta pasada

- ~~**La edición del pasado.**~~ **Resuelto el 2026-08-06 en `23d27bb7`** — ver `PG-006`.
- **El borrado de filas** (`/api/general/delete-update`): qué permiso exige y qué pasa con los datos
  dependientes — si la actividad borrada estaba comprometida en una semana o bajada a intermedia.
- **Aislamiento por `project_id`** comprobado consulta a consulta; aquí solo se ha verificado la
  ruta de autorización, no cada `WHERE`.
- Los dos endpoints de `breadcrumb` (`preview` y `estandarizar`) y `decision-log`.
