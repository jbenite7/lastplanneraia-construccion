---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-08-04
areas: [lps, rbac]
fuente: docs/flujos/transversal-rbac.md
resumen: Escenarios RBAC-. Qué permite cada capacidad, a qué roles, y dónde se comprueba.
---

# Biblia · Transversal · Capacidades por rol

Escenarios `RBAC-*`. Qué permite cada capacidad, a qué roles, y dónde se comprueba.

Formato y reglas: `docs/flujos/README.md`. **La matriz de abajo se leyó de
`src/Security/RbacManager.php` el 2026-08-04**, no de memoria ni de la documentación, y se
reconcilió el 2026-08-10 al colapsar los alias (ver `RBAC-A`). Las filas tachadas conservan su
número para que las referencias viejas sigan resolviendo; ya no existen en el código.

Los diez roles del catálogo: `A` Admin · `D` Director de Obra · `R` Residente de Obra · `DCV` ·
`OT` · `G` Ambiental · `S` SST · `SG` · `C` Subcontratista · `V` Visualizador.

---

## La matriz, tal como está en el código

| # | Capacidad | Roles permitidos | Roles denegados |
|---|---|---|---|
| RBAC-001 | `canManageWeeks` | A · D · OT · R · DCV | G · S · SG · C · V |
| RBAC-002 | `canDeleteRows` | **A · D** | los otros ocho |
| RBAC-003 | ~~`canEditGeneralProgram`~~ | **colapsada el 2026-08-10** en `canManageGeneralProgram` (RBAC-004): era su alias exacto | — |
| RBAC-004 | `canManageGeneralProgram` | A · D · R · DCV | OT · G · S · SG · C · V |
| RBAC-005 | `canEditPastGeneralProgram` | **A · D** | los otros ocho |
| RBAC-006 | ~~`canEditWeeklyProgram`~~ | **colapsada el 2026-08-10** en `canManageWeeklyProgram` (RBAC-007) | — |
| RBAC-007 | `canManageWeeklyProgram` | A · D · R · S · G · SG | OT · DCV · C · V |
| RBAC-008 | ~~`canEditMediumTerm`~~ | **colapsada el 2026-08-10** en `canManageMediumTermProgram` (RBAC-009) | — |
| RBAC-009 | `canManageMediumTermProgram` | A · D · R · DCV | OT · G · S · SG · C · V |
| RBAC-010 | `canEditConstraints` | A · D · R · DCV · S · G · SG · OT | C · V |
| RBAC-011 | `canEditFinancial` | A · D · OT | los otros siete |
| RBAC-012 | `canEditSST` | **A · S · SG** | los otros siete (incluido D) |
| RBAC-013 | `canEditAmbiental` | **A · G · SG** | los otros siete (incluido D) |
| RBAC-014 | ~~`canManageContracts`~~ | **colapsada el 2026-08-10** en `canManagePdC` (RBAC-016) | — |
| RBAC-015 | ~~`canAutoDefineContracts`~~ | **retirada el 2026-08-10**: alias sin ningún consumidor, borrada de `RbacManager` | — |
| RBAC-016 | `canManagePdC` | A · D · OT · R | DCV · G · S · SG · C · V |
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

## RBAC-A · Los alias no eran capacidades distintas — cerrado el 2026-08-10

Cuatro nombres de la tabla **no tenían lógica propia**: tomaban el valor de otro. El vocabulario
prometía una distinción «editar» vs «gestionar» que el código nunca hizo, así que cada par se
colapsó en un solo nombre.

| Par colapsado | Superviviente | Por qué gana |
|---|---|---|
| `canEditGeneralProgram` / `canManageGeneralProgram` | `canManageGeneralProgram` | es el único con consumidor de runtime (`views/indicadores/indicadores.view.php`); el otro solo lo citaba el generador de la wiki |
| `canEditWeeklyProgram` / `canManageWeeklyProgram` | `canManageWeeklyProgram` | ídem |
| `canEditMediumTerm` / `canManageMediumTermProgram` | `canManageMediumTermProgram` | ídem |
| `canManageContracts` / `canManagePdC` | `canManagePdC` | ninguno tiene consumidor de runtime; «Contratos» es un módulo eliminado con el PDC v1 el 2026-08-04, y `/plan-compras` sigue vivo |

La regla de elección —del plan `2026-08-10-frente-1a-seguridad-y-permisos.md`— era conservar el
nombre con más consumidores. **El conteo crudo no bastó:** los cuatro pares quedaban casi en empate
y hubo que pesar los consumidores, no contarlos. Un `grep` que acierta en
`scripts/wiki-arquitectura.modulos.mjs` (generador de documentación) no vale lo que uno que acierta
en una vista que se le sirve al usuario.

- **Resultado esperado hoy:** existe un solo nombre por capacidad. Quien busque `canEdit*` para
  programa general, semanal, intermedio o compras no lo encontrará: no hay un permiso de «gestionar»
  separado del de «editar», y ahora tampoco lo aparenta.
- **Por qué importaba:** un desarrollador que quisiera dar edición sin gestión —o al revés— creería
  que podía, y no podía sin cambiar la lógica. Cualquier regla de negocio que necesite esa
  distinción hay que implementarla, no reactivarla.
- **Verificación:** `src/Security/RbacManager.php` (doce capacidades, ningún alias) y
  `public/js/rbac_capabilities.js`; `npm run test:rbac-parity` en verde tras el colapso.

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
  (por ejemplo `canManagePdC`, en el objeto `RbacCapabilities` y en `buildLegacyCapabilities()`).
- **Resultado esperado:** las dos implementaciones coinciden siempre. Cualquier divergencia produce
  una interfaz que ofrece acciones que el servidor rechaza, o que esconde acciones permitidas.
- **Riesgo estructural:** son dos fuentes de la misma verdad. Desde el 2026-08-10 **sí hay gate**:
  `npm run test:rbac-parity` (`scripts/rbac-parity.mjs`) compara ambas matrices rol a rol y falla
  ante cualquier divergencia de valores.
- **Verificación:** lectura — `src/Security/RbacManager.php` y `public/js/rbac_capabilities.js`;
  ejecutable — `npm run test:rbac-parity`.

## RBAC-E · Un rol desconocido debe quedarse en solo lectura

- **Rol:** cualquier valor fuera del catálogo.
- **Resultado esperado:** la sesión degrada a Visualizador; nunca se concede una capacidad por
  defecto. `AGENTS.md` lo exige: «conserva solo lectura como fallback seguro».
- **Verificación:** lectura — el camino del selector lo cumple
  (`ProjectSelectorController.php:174-176`), aunque **por su cuenta y no con el normalizador que
  manda el contrato**: ver el hallazgo de `PROY-007`.

---

## Escenarios pendientes de esta pasada

- **Un consumidor real citado por cada una de las doce capacidades vivas.** Se comprobaron
  `canSeeReports` (ninguno), `canManagePdC` (solo cliente) y, al colapsar los alias el 2026-08-10,
  las cuatro parejas de `RBAC-A`. Las restantes están sin rastrear, y cada una sin consumidor sería
  un hallazgo como el de `RBAC-B`.
- **Los permisos con clave `lps.*`** de `RbacCatalog::fallbackPermissionsByRole()`, que es un
  sistema **distinto** de estas capacidades booleanas y el que usa el PDC. Merece su propia sección
  cuando se ejecute T3.
- La comparación automática servidor/cliente de `RBAC-D`.
