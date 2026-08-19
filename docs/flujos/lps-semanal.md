---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-08-04
areas: [lps]
fuente: docs/flujos/lps-semanal.md
resumen: Escenarios PS-. El compromiso de la semana: qué se va a hacer de verdad, quién lo confirma y qué pasa después. Es el eslabón donde el ciclo se cierra y donde…
---

# Biblia · Cascada LPS · Programación Semanal

Escenarios `PS-*`. El compromiso de la semana: qué se va a hacer de verdad, quién lo confirma y qué
pasa después. Es el eslabón donde el ciclo se cierra y donde vive el candado.

Formato y reglas: `docs/flujos/README.md`. Verificado por lectura el **2026-08-04**.

---

## Este módulo autoriza distinto que los otros dos

Dato medido, y conviene tenerlo presente antes de leer nada más:
**`SemanalApiController` no usa `authorizePermission` ni una sola vez** (0 ocurrencias). Donde
Programa General e Intermedia comprueban claves `lps.*`, Semanal usa dos guardias propias:

| Guardia | Qué exige | Dónde |
|---|---|---|
| `requireSessionDbPrefix()` | Que el prefijo de la petición **sea exactamente** el de la sesión | `:219-226` |
| `requireWeekEditPolicy()` | Que `LpsWeekEditPolicy::allows()` autorice ese rol para esa semana | `:228-234` |

Son tres esquemas de autorización distintos en tres eslabones del mismo ciclo. El de Semanal es
**más directo** que el de Intermedia —compara el prefijo de frente en vez de delegar la coherencia
al script legado— pero deja sin usar la clave `lps.programacion_semanal.editar`, que existe en el
catálogo (`RbacCatalog.php:103`) y **aquí no se consulta**.

## PS-001 · Toda mutación exige sesión, prefijo coherente, semana válida y política de rol

- **Rol permitido:** los que `canEditLpsWeek` autorice para esa semana (ver `CAS-001`).
- **Precondiciones:** sesión con proyecto; `opcion` entre las diez mutantes.
- **Pasos, en este orden:**
  1. CSRF (ver `PS-002`).
  2. `requireSessionDbPrefix($dbPrefix)` → **403** «El proyecto solicitado no coincide con la sesión
     activa» si difiere.
  3. `semana > 0`, si no «Semana inválida».
  4. `requireWeekEditPolicy()` → **403** «La semana histórica no permite esta operación para su
     rol».
- **Resultado esperado:** ninguna escritura si falla cualquiera de las cuatro. **En datos:** nada.
- **Verificación:** lectura — `src/Controllers/Api/SemanalApiController.php:142-156`, `:219-234`.

Las diez operaciones mutantes: `nuevo`, `modificar`, `eliminar`, `duplicar`, `autoprogramar`,
`bloquear_compromisos`, `importar_actividad_no_requerida`, `EstadoEjecucion`, `tnp` y `sanear`
(`:145-148`).

## PS-002 · Toda mutación debe exigir token CSRF

- **Resultado esperado:** una petición sin `X-CSRF-Token` (o `_csrf_token`) válido para el contexto
  `semanal_save` recibe **403** y no escribe nada.
- **Verificación:** lectura — `SemanalApiController.php:128-133`.

> **Hallazgo del 2026-08-04 — CORREGIDO el 2026-08-06 en `32cccddf`.** Las dos listas no
> coincidían: la de CSRF enumeraba nueve opciones y **omitía `sanear`**, que sí estaba en
> `$mutatingOptions` y efectivamente escribe (`DELETE` + `INSERT`). Hoy `sanear` figura en las dos
> (`SemanalApiController:128`), así que las diez mutaciones exigen token.
>
> Queda escrito porque el patrón se repite y conviene reconocerlo: **dos listas paralelas que deben
> decir lo mismo y nada las obliga**. El PDC previó el mismo riesgo con una red —`|| isset($_POST['columna'])`—
> que Semanal no tenía. La prueba `PS-001` de `e2e/tests/biblia/cascada-lps.spec.mjs` tuvo que
> adaptarse al arreglo: antes llegaba al guard de prefijo eligiendo `sanear` para esquivar el CSRF,
> y ahora manda token válido a propósito.

## PS-003 · `modificar` se trata como calificación, y por eso puede tocar semanas confirmadas

Escenario clave para entender el cierre del ciclo, y nada evidente al leer el código por encima.

- **Pasos:** en `save()`, la llamada es
  `requireWeekEditPolicy($dbPrefix, $semana, $opcion === 'modificar')` (`:155-156`): **solo
  `modificar` pasa `qualification = true`**.
- **Resultado esperado:** `modificar` puede editar una semana confirmada si el rol puede calificar
  y `Semanal_Confirmada = 1` (la quinta salida de `CAS-003`). Las otras nueve operaciones **no**:
  para ellas, semana confirmada significa cerrada.
- **Verificación:** lectura — `SemanalApiController.php:155-156` y `CAS-003`.

> Esto es lo que hace posible calificar el cumplimiento después de cerrar los compromisos, que es el
> paso de aprendizaje del ciclo LPS. También significa que **`modificar` es la operación más
> poderosa del módulo**: es la única con llave para el pasado confirmado.

## PS-004 · Confirmar la semana cierra los compromisos

- **Rol:** el que pase la política para esa semana.
- **Precondiciones:** semana abierta con compromisos.
- **Pasos:** `opcion=bloquear_compromisos` → `bloquearCompromisos()` ejecuta
  `UPDATE …semanas_activas SET Semanal_Confirmada = 1, fechaCierreCompromisos = ?`.
- **Resultado esperado:** la semana queda confirmada y con fecha de cierre. **A partir de ahí**, las
  nueve operaciones que no son `modificar` reciben 409 del candado (`CAS-004`), y cada intento queda
  registrado en la auditoría.
- **Verificación:** lectura — `SemanalApiController.php:923`, `:931`.

## PS-005 · Reabrir una semana confirmada exige motivo escrito

- **Rol:** el que pase `requireWeekEditPolicy`.
- **Precondiciones:** semana con `Semanal_Confirmada = 1`.
- **Pasos:**
  1. `POST /api/semanal/reabrir` valida CSRF para `semanal_save`.
  2. Exige `requireSessionDbPrefix` y `requireWeekEditPolicy`.
  3. Exige que `motivo` tenga **al menos 20 caracteres**.
  4. `UPDATE …semanas_activas SET Semanal_Confirmada = 0, fechaCierreCompromisos = NULL`.
- **Resultado esperado:** la semana vuelve a estar abierta **y queda constancia del motivo**. Un
  motivo corto o vacío se rechaza: deshacer un cierre no puede ser un clic sin explicación.
- **Verificación:** lectura — `SemanalApiController.php:940-990`.

> El mínimo de 20 caracteres es una decisión de diseño que merece estar escrita: obliga a una frase,
> no a un «ok». Encaja con la dimensión social defensiva del Residente en `docs/CUSTOMER.md` — que
> quede registrado quién reabrió y por qué.

---

## Escenarios pendientes de esta pasada

- **Qué dispara la carga de la vista.** La trampa `semanal-auto-dispara-mutaciones` dice que `save`
  y `auto-program` pueden dispararse al abrir la pantalla, con dos guardias condicionales. Merece
  escenario propio verificado de nuevo contra el código.
- **La herencia desde Intermedia**: qué actividades llegan a la semana y con qué criterio.
- **El cálculo de proyecciones** (`calculateWeeklyProjections`) y sus casos borde.
- **Qué ve el usuario ante un 409 o un 403**: si la grilla muestra el mensaje o revierte en
  silencio. No comprobable en lectura, y candidato a prueba ejecutable.
