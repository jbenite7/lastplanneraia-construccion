---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-08-04
areas: [lps]
fuente: docs/flujos/lps-cascada.md
resumen: Escenarios CAS-. Las reglas que atraviesan más de un eslabón del ciclo, descritas aquí una vez para que los demás documentos las citen en vez de repetirlas.
---

# Biblia · Cascada LPS · Invariantes

Escenarios `CAS-*`. Las reglas que atraviesan más de un eslabón del ciclo, descritas aquí una vez
para que los demás documentos las citen en vez de repetirlas.

Formato y reglas: `docs/flujos/README.md`. Verificado por lectura el **2026-08-04**.

---

## El candado de semana

Dos piezas distintas, y confundirlas cuesta caro:

| Pieza | Qué decide | Dónde |
|---|---|---|
| `LpsWeekEditPolicy::allows()` | Si **este rol** puede editar **esta semana** | `src/Security/LpsWeekEditPolicy.php:16` |
| `CommitmentLockGuard::guard()` | Si **esta mutación** puede ejecutarse sobre una semana ya confirmada | `src/Core/CommitmentLockGuard.php:25` |

La primera es una política de rol; la segunda, un torniquete de escritura. Una mutación bien
protegida pasa por las dos.

## CAS-001 · Admin y Director editan cualquier semana; Residente y DCV, solo las dos últimas

- **Roles:** `A` y `D` frente a `R` y `DCV`.
- **Precondiciones:** existe al menos una semana (`maxWeek > 0`) y se pide una semana concreta
  (`week > 0`).
- **Pasos:** `LpsWeekEditPolicy::allows()` resuelve el `project_id`, calcula `maxWeek` como el
  `MAX(Semana)` de `semanas_activas` del proyecto, y llama a
  `RbacCatalog::canEditLpsWeek($role, $week, $maxWeek)`.
- **Resultado esperado:**
  - `A` y `D` → **true para cualquier semana**, pasada o futura.
  - `R` y `DCV` → true **solo si `week > maxWeek - 2`**, es decir la última semana y la anterior.
    Una semana más antigua les queda cerrada.
  - Cualquier otro rol → false.
  - Con `week <= 0` o `maxWeek <= 0` → false.
- **Verificación:** lectura — `src/Security/RbacCatalog.php:66-77`.

> La ventana de dos semanas para el Residente es una regla de negocio fuerte y no evidente: al
> crearse una semana nueva, **la antepenúltima se le cierra automáticamente**. Nadie se lo avisa;
> simplemente deja de poder editar.

## CAS-002 · Entrada inválida o proyecto irresoluble cierran la puerta

- **Precondiciones:** `dbPrefix` vacío, `week <= 0`, o un prefijo del que no se puede resolver
  `project_id`.
- **Resultado esperado:** `allows()` devuelve **false**. Falla hacia el lado cerrado, que es lo
  correcto para una política de edición.
- **Verificación:** lectura — `src/Security/LpsWeekEditPolicy.php:18-26`.

## CAS-003 · Una semana confirmada sigue siendo editable **para calificar**

El escenario contraintuitivo del ciclo, y el que más se presta a error.

- **Roles:** `A`, `D`, `R`, `DCV` (los que pueden calificar).
- **Precondiciones:** la semana tiene `Semanal_Confirmada = 1`, y la operación se declara como
  calificación (`$qualification = true`).
- **Pasos:**
  1. `canEditLpsWeek()` dice que no (por antigüedad o por rol).
  2. `allows()` comprueba `$qualification` y `canQualifyWeeklyCommitment($role)`.
  3. Consulta `Semanal_Confirmada` y exige que valga exactamente `1`.
- **Resultado esperado:** **true**. Confirmar una semana **no** la congela del todo: cierra los
  compromisos, pero deja calificar su cumplimiento, que es justo el paso de aprendizaje del ciclo
  (CIC/CNC).
- **Verificación:** lectura — `src/Security/LpsWeekEditPolicy.php:36-47`,
  `src/Security/RbacCatalog.php:79-82`.

> Léelo al revés para ver la trampa: si la semana **no** está confirmada (`Semanal_Confirmada = 0`),
> la vía de calificación **no** se abre. Es decir, calificar exige haber confirmado antes. El orden
> importa.

## CAS-004 · Una mutación sobre semana confirmada se rechaza con 409 y queda registrada

- **Rol:** cualquiera.
- **Precondiciones:** `Semanal_Confirmada = 1` para esa semana, y la mutación llama al guardián sin
  `allowIfConfirmed`.
- **Pasos:**
  1. `CommitmentLockGuard::guard($dbPrefix, $semana, $operacion)` consulta el estado de la semana.
  2. Al verla confirmada, registra la actividad `BLOQUEO_<OPERACION>` con el usuario y el motivo.
  3. Responde y corta la ejecución.
- **Resultado esperado:** código **409**, `Content-Type: application/json`, y cuerpo
  `{"respuesta":"ERROR","mensaje":"No se puede realizar '<operacion>': los compromisos de la semana
  N ya fueron confirmados."}`. **En datos:** ninguna escritura del negocio, **más una fila de
  auditoría** con el intento.
- **Verificación:** lectura — `src/Core/CommitmentLockGuard.php:45-61`. Ejecutable — pendiente:
  comprobar el 409 exige una semana confirmada en el proyecto de pruebas, y crearla mutaría datos.

Las nueve mutaciones que hoy pasan por el guardián: `modificar_pi` y
`aplicar_restricciones_compartidas_pi` (Programación Intermedia), y `autoprogramar`,
`estado_ejecucion`, `eliminar`, `duplicar`, `nuevo` y `tnp` (Semanal). Verificado con
`grep -rn "CommitmentLockGuard::guard"`.

> **Nota de alcance:** que Programación Intermedia consulte el candado de la semanal es correcto —
> lo que se toca en intermedia alimenta compromisos ya cerrados— y explica por qué este documento es
> de cascada y no de un módulo.

## CAS-005 · `allowIfConfirmed = true` desactiva el candado por completo

- **Precondiciones:** cualquier llamada al guardián con el cuarto parámetro en `true`.
- **Pasos:** `guard()` **retorna en su primera línea**, antes de consultar nada.
- **Resultado esperado según el diseño:** el candado se salta a propósito para las operaciones de
  calificación, cuya autorización corresponde a `LpsWeekEditPolicy::allows(..., qualification: true)`
  — es decir, **la comprobación de rol vive en otra pieza**.
- **Verificación:** lectura — `src/Core/CommitmentLockGuard.php:27-29`.

> **Riesgo estructural, registrado (hoy sin explotar):** el parámetro no comprueba el rol ni
> consulta la política; confía en que quien lo pone en `true` ya haya validado por su cuenta. Un
> `allowIfConfirmed: true` puesto sin la comprobación correspondiente abriría la escritura sobre
> semanas confirmadas a cualquier rol. Medido el 2026-08-04: **ninguna de las nueve llamadas
> actuales lo usa**, así que hoy es un riesgo latente, no un agujero abierto.

## CAS-006 · Si el proyecto no se puede resolver, el guardián **deja pasar**

- **Precondiciones:** `TableResolver::getProjectIdByPrefix($dbPrefix)` no devuelve identificador.
- **Pasos:** `guard()` retorna sin comprobar nada — el propio código lo comenta como «se deja pasar
  por seguridad».
- **Resultado esperado tal como está escrito:** la mutación continúa.
- **Verificación:** lectura — `src/Core/CommitmentLockGuard.php:33-35`.

> **Contraste que conviene tener presente:** ante la misma situación, la política de edición
> (`CAS-002`) devuelve **false** —cierra— y el guardián **abre**. Dos piezas del mismo candado
> fallan hacia lados opuestos. Cuál es la conducta correcta es decisión de producto; que sean
> distintas entre sí es lo que hace difícil razonar sobre el candado. Registrado en
> `docs/EXPERIMENTS.md`.

---

## Escenarios pendientes de esta pasada

- La herencia de actividades entre eslabones (Programa General → Intermedia → Semanal): qué se copia
  y qué pasa cuando el origen cambia después. Es el escenario de mayor valor de T2 y necesita
  lectura del código de cada eslabón.
- Coherencia de fechas entre eslabones.
- Qué ve el usuario cuando recibe el 409: si la grilla muestra el mensaje del guardián o revierte en
  silencio. No comprobable en lectura.
