# Biblia · Transversal · Capacidades por rol

Escenarios `RBAC-*`. Qué permite cada capacidad, a qué roles, y dónde se comprueba.

Formato y reglas: `docs/flujos/README.md`. **La matriz de abajo se leyó de
`src/Security/RbacManager.php` el 2026-08-04**, no de memoria ni de la documentación.

Los diez roles del catálogo: `A` Admin · `D` Director de Obra · `R` Residente de Obra · `DCV` ·
`OT` · `G` Ambiental · `S` SST · `SG` · `C` Subcontratista · `V` Visualizador.

---

## La matriz, tal como está en el código

| # | Capacidad | Roles permitidos | Roles denegados |
|---|---|---|---|
| RBAC-001 | `canManageWeeks` | A · D · OT · R · DCV | G · S · SG · C · V |
| RBAC-002 | `canDeleteRows` | **A · D** | los otros ocho |
| RBAC-003 | `canEditGeneralProgram` | A · D · R · DCV | OT · G · S · SG · C · V |
| RBAC-004 | `canManageGeneralProgram` | **alias exacto de `canEditGeneralProgram`** | ídem |
| RBAC-005 | `canEditPastGeneralProgram` | **A · D** | los otros ocho |
| RBAC-006 | `canEditWeeklyProgram` | A · D · R · S · G · SG | OT · DCV · C · V |
| RBAC-007 | `canManageWeeklyProgram` | **alias exacto de `canEditWeeklyProgram`** | ídem |
| RBAC-008 | `canEditMediumTerm` | A · D · R · DCV | OT · G · S · SG · C · V |
| RBAC-009 | `canManageMediumTermProgram` | **alias exacto de `canEditMediumTerm`** | ídem |
| RBAC-010 | `canEditConstraints` | A · D · R · DCV · S · G · SG · OT | C · V |
| RBAC-011 | `canEditFinancial` | A · D · OT | los otros siete |
| RBAC-012 | `canEditSST` | **A · S · SG** | los otros siete (incluido D) |
| RBAC-013 | `canEditAmbiental` | **A · G · SG** | los otros siete (incluido D) |
| RBAC-014 | `canManageContracts` | A · D · OT · R | DCV · G · S · SG · C · V |
| RBAC-015 | `canAutoDefineContracts` | **alias exacto de `canManageContracts`** | ídem |
| RBAC-016 | `canManagePdC` | **alias exacto de `canManageContracts`** | ídem |
| RBAC-017 | `canSeeReports` | **todos, siempre** | ninguno |

Además, tres banderas derivadas: `isSystemAdmin`, `isExternal` (solo `C`) e `isReadOnly`
(Visualizador o Subcontratista).

**Verificación:** lectura — `src/Security/RbacManager.php`, bloque `getCapabilities()`.

---

## RBAC-001 · Solo quien tiene `canManageWeeks` ve los controles de semana

- **Rol permitido:** `R` (Residente). **Rol denegado:** `V` (Visualizador).
- **Precondiciones:** sesión abierta por la puerta de servicio sobre el proyecto *Da Porto*.
- **Pasos:**
  1. Abrir `/programa-general`.
  2. Pulsar el botón «Semanas del Proyecto», que despliega el flyout del shell.
- **Resultado esperado:** el Residente ve `#shellWeekCreateOpen` («+ Nueva semana») y
  `.shell-week-flyout__delete` («Eliminar Semana N»), más los diálogos `#shellWeekCreateSubmit` y
  `#shellWeekDeleteSubmit`. El Visualizador **no ve ninguno de los cuatro**. En datos: nada, mientras
  no se confirme un diálogo.
- **Verificación:** lectura — `src/Security/RbacManager.php` (`canManageWeeks`: A · D · OT · R ·
  DCV). Ejecutable — `e2e/tests/biblia/transversal.spec.mjs`, test `RBAC-001 · Residente gestiona
  semanas…`, **en verde el 2026-08-04**.

> **Medido al escribir la prueba:** fuera del flyout, **los dos roles ven exactamente los mismos 17
> botones** en `/programa-general`, incluidos «Actualizar Ejecución», «Descargar Corte» y «Exportar
> CSV». La diferencia por rol existe **solo dentro** del panel de semanas. Que un Visualizador —rol
> de solo lectura por definición— vea un botón llamado «Actualizar Ejecución» merece comprobarse:
> registrado como escenario pendiente, porque confirmarlo exige pulsarlo y eso mutaría datos.

## RBAC-A · Los alias no son capacidades distintas

Cuatro nombres de la tabla **no tienen lógica propia**: toman el valor de otro.

- `canManageGeneralProgram` ← `canEditGeneralProgram`
- `canManageWeeklyProgram` ← `canEditWeeklyProgram`
- `canManageMediumTermProgram` ← `canEditMediumTerm`
- `canAutoDefineContracts` y `canManagePdC` ← `canManageContracts`

- **Resultado esperado:** quien comprueba `canManage*` obtiene exactamente lo mismo que quien
  comprueba `canEdit*`. **No existe** un permiso de «gestionar» separado del de «editar»: el
  vocabulario sugiere una distinción que el código no hace.
- **Por qué importa:** un desarrollador que quiera dar edición sin gestión —o al revés— creerá que
  puede, y no puede sin cambiar la lógica. Toda regla de negocio que dependa de esa distinción está
  hoy sin implementar.

## RBAC-B · `canSeeReports` no discrimina nada, y nadie la consulta

- **Resultado esperado según el código:** `true` para los diez roles, incluidos el Visualizador y el
  Subcontratista externo.
- **Además:** `grep -rn "canSeeReports"` sobre todo el repositorio no encuentra **ningún
  consumidor** fuera de la propia declaración.
- **Lectura honesta:** es una capacidad inerte. Ni restringe (siempre `true`) ni se usa. O falta la
  lógica que iba a diferenciar quién ve informes, o sobra la entrada.
- **Verificación:** lectura — `src/Security/RbacManager.php` (`'canSeeReports' => true`) y búsqueda
  en todo el árbol.

## RBAC-C · SST y Ambiental excluyen al Director de Obra

Escenario contraintuitivo y por eso obligatorio.

- `canEditSST` la tienen **A, S y SG**. `canEditAmbiental`, **A, G y SG**.
- **El Director de Obra (`D`) no está en ninguna de las dos**, pese a ser el rol con más permisos en
  casi todo lo demás.
- **Resultado esperado:** es deliberado y correcto en dominio — la información de seguridad y salud
  y la ambiental las firma el profesional responsable, no la jefatura. Pero contradice la intuición
  de «el Director puede todo», y quien no lo sepa lo leerá como un bug.
- **Verificación:** lectura — `src/Security/RbacManager.php`, entradas `canEditSST` y
  `canEditAmbiental`.

## RBAC-D · La capacidad se comprueba en dos sitios y hay que mantenerlos a la par

- **En servidor:** `RbacManager::getCapabilities($role)` / `hasCapability()`.
- **En cliente:** `public/js/rbac_capabilities.js` reimplementa las mismas reglas en JavaScript
  (por ejemplo `canManagePdC` en `:45` y `:156-159`).
- **Resultado esperado:** las dos implementaciones coinciden siempre. Cualquier divergencia produce
  una interfaz que ofrece acciones que el servidor rechaza, o que esconde acciones permitidas.
- **Riesgo estructural:** son dos fuentes de la misma verdad, sin gate que las contraste. **Candidato
  a prueba ejecutable de alto valor**: comparar ambas matrices rol a rol.
- **Verificación:** lectura — `src/Security/RbacManager.php` y `public/js/rbac_capabilities.js:45`,
  `:156-159`.

## RBAC-E · Un rol desconocido debe quedarse en solo lectura

- **Rol:** cualquier valor fuera del catálogo.
- **Resultado esperado:** la sesión degrada a Visualizador; nunca se concede una capacidad por
  defecto. `AGENTS.md` lo exige: «conserva solo lectura como fallback seguro».
- **Verificación:** lectura — el camino del selector lo cumple
  (`ProjectSelectorController.php:174-176`), aunque **por su cuenta y no con el normalizador que
  manda el contrato**: ver el hallazgo de `PROY-007`.

---

## Escenarios pendientes de esta pasada

- **Un consumidor real citado por cada una de las 17 capacidades.** Solo se comprobaron
  `canSeeReports` (ninguno) y `canManagePdC` (cliente). Las quince restantes están sin rastrear, y
  cada una sin consumidor sería un hallazgo como el de `RBAC-B`.
- **Los permisos con clave `lps.*`** de `RbacCatalog::fallbackPermissionsByRole()`, que es un
  sistema **distinto** de estas capacidades booleanas y el que usa el PDC. Merece su propia sección
  cuando se ejecute T3.
- La comparación automática servidor/cliente de `RBAC-D`.
