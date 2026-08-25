---
capa: fuente
tipo: biblia
estado: vigente
fecha: 2026-08-04
areas: [lps]
fuente: docs/flujos/lps-intermedia.md
resumen: Escenarios PI-. La ventana de medio plazo: bajar actividades del programa general y levantarles restricciones antes de que lleguen a la semana. Es donde se…
---

# Biblia · Cascada LPS · Programación Intermedia

Escenarios `PI-*`. La ventana de medio plazo: bajar actividades del programa general y levantarles
restricciones **antes** de que lleguen a la semana. Es donde se juega la subentrega funcional que
`docs/CUSTOMER.md` señala para el Residente — saber qué va a frenar antes de que frene.

Formato y reglas: `docs/flujos/README.md`. Verificado por lectura el **2026-08-04**.

---

## Los permisos de este módulo

Como en Programa General, aquí mandan las claves `lps.*`, no las capacidades booleanas:

| Endpoint | Permiso |
|---|---|
| `GET /api/pi/list` (`:131`) | `lps.programacion_intermedia.ver` |
| `POST /api/pi/save` (`:219`) | `lps.programacion_intermedia.editar` |
| `/programacion-intermedia/filtros` (`:252`) | `lps.programacion_intermedia.ver` |
| `shared-constraints/preview` (`:396`) | `lps.programacion_intermedia.ver` |
| `shared-constraints/apply` (`:368`, `:475`) | `lps.programacion_intermedia.editar` |

**Verificación:** lectura — `src/Controllers/Programacion/ProgramacionIntermediaController.php`.

## PI-001 · Guardar exige permiso de edición y semana no confirmada

- **Roles permitidos:** los que tengan `lps.programacion_intermedia.editar` — `A`, `D`, `R`.
- **Precondiciones:** sesión con proyecto y semana; la semana **no** confirmada.
- **Pasos:**
  1. `POST /api/pi/save` autoriza con `lps.programacion_intermedia.editar` (`:219`).
  2. Llama a `CommitmentLockGuard::guard($dbPrefix, $semana, 'modificar_pi')` (`:239`).
  3. Delega en `src/Legacy/guardar_programacion_intermedia.php`.
- **Resultado esperado:** con la semana confirmada, **409** y ninguna escritura (ver `CAS-004`). Sin
  confirmar, se guarda.
- **Verificación:** lectura — `ProgramacionIntermediaController.php:219-241`.

> **Que Intermedia consulte el candado de la semanal no es un error**: lo que se toca aquí alimenta
> compromisos que pueden estar ya cerrados. Por eso el candado vive en `lps-cascada.md` y no en un
> módulo.

## PI-002 · Un contexto que no coincide con la sesión se rechaza con 409

Escenario de seguridad, y el que responde a la pregunta obvia al ver que el prefijo de base viaja en
la petición.

- **Rol:** cualquiera con permiso de edición.
- **Precondiciones:** la petición incluye `db` y/o `semana` **distintos** de los de la sesión.
- **Pasos:**
  1. El script legado lee `$_GET['db']` / `$_POST['db']` como `$dbRequest` y `$_SESSION['db']` como
     `$dbSession`.
  2. Si ambos existen y **difieren**, corta.
  3. Lo mismo con la semana.
- **Resultado esperado:** **409** con «Conflicto de contexto: la base de datos solicitada no
  coincide con la sesion activa» (o el equivalente de semana), y **ninguna escritura**. Un cliente
  no puede operar sobre otro proyecto cambiando un parámetro.
- **Verificación:** lectura — `src/Legacy/guardar_programacion_intermedia.php:48-51` (base) y
  `:74-77` (semana).

> **Comprobado a propósito porque el patrón invita a sospechar:** el prefijo llega del cliente y
> `TableResolver::getProjectIdByPrefix()` (`src/Core/TableResolver.php:150-160`) resuelve
> **cualquier** prefijo activo sin mirar si el usuario es miembro. La barrera no está ahí: está en
> la comprobación de coherencia de arriba. **Es una defensa correcta pero indirecta**: cualquier
> endpoint futuro que acepte `db` del cliente y no replique esa comparación quedaría expuesto.

## PI-003 · Formato de prefijo inválido se rechaza antes de tocar la base

- **Pasos:** el prefijo debe casar `/^[a-zA-Z0-9_]+$/`.
- **Resultado esperado:** **400** con «Base de datos invalida o sesion expirada»; ninguna consulta.
  Cierra la vía de inyección por nombre de tabla.
- **Verificación:** lectura — `guardar_programacion_intermedia.php:53-56`.

## PI-004 · Sin base en sesión, la de la petición la establece

- **Precondiciones:** `$_SESSION['db']` vacío y la petición trae `db` con formato válido.
- **Resultado esperado:** la sesión adopta ese prefijo y la operación continúa. Es lo que permite
  que una petición llegue antes de que la sesión esté completa, sin romper.
- **Verificación:** lectura — `guardar_programacion_intermedia.php:58-60`, y el mismo patrón en el
  controlador (`ProgramacionIntermediaController.php:229-231`).

> Consecuencia que conviene tener escrita: **la primera petición que llegue con `db` fija el
> contexto**. La comprobación de coherencia de `PI-002` solo actúa cuando la sesión **ya** tiene
> base; antes de eso, no hay con qué comparar.

## PI-005 · Las restricciones compartidas se previsualizan antes de aplicarse

- **Roles:** ver con `…ver`, aplicar con `…editar`.
- **Pasos:** `shared-constraints/preview` calcula el efecto; `shared-constraints/apply` lo ejecuta y
  pasa por el candado con la operación `aplicar_restricciones_compartidas_pi` (`:492`).
- **Resultado esperado:** la previsualización **no escribe nada**; la aplicación sí, y se rechaza
  con 409 si la semana está confirmada.
- **Verificación:** lectura — `ProgramacionIntermediaController.php:368`, `:396`, `:475`, `:492`.

---

## Escenarios pendientes de esta pasada

- **La herencia desde Programa General**: cómo bajan las actividades a la ventana intermedia (copia,
  referencia o selección), y **qué ocurre si la actividad de origen cambia después**. Sigue siendo
  el escenario de mayor valor de T2 y exige leer el legado de `estado_programacion_intermedia.php`.
- **Los ocho estados operativos** y la condición de datos que produce cada uno.
- **El levantamiento de restricciones**: si el sistema impide comprometer en semanal una actividad
  con restricción viva. Es exactamente la subentrega funcional del Residente; si no lo impide, es
  hallazgo de producto para decisión del usuario.
- Aislamiento por `project_id` comprobado consulta a consulta dentro del legado.
