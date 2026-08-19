---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-08-04
areas: [lps]
fuente: docs/flujos/soporte.md
resumen: Escenarios SOP-. Los módulos que alimentan la cascada sin gobernarla: contratos, listado de actividades, subcontratistas, profesionales, control de cambios y…
---

# Biblia · Módulos de soporte

Escenarios `SOP-*`. Los módulos que alimentan la cascada sin gobernarla: contratos, listado de
actividades, subcontratistas, profesionales, control de cambios y escalamientos.

Formato y reglas: `docs/flujos/README.md`. Verificado por lectura el **2026-08-04**.

---

## Primero, deshacer un malentendido de nombres

«Contratos» y «Listado de Actividades» **no tienen backend propio**. Sus rutas son todas del PDC:

| Módulo de la wiki | Sus rutas |
|---|---|
| Contratos | las 13 de `/api/pdc/auto/*` |
| Listado de Actividades (PDC v1) | `/pdc`, `/api/pdc/list`, `/api/pdc/save`, `/api/pdc/update-cell`, plantillas y duraciones |

Esto **no es un hallazgo**: es un reparto de criterio declarado y explicado en
`scripts/wiki-arquitectura.modulos.mjs:116-124`, que atribuye `/api/pdc/auto/*` a Contratos «por ser
el contrato auto/preview·apply·undo·feedback·metrics que define contratos» y deja el resto en
Listado. Se comprobó antes de escribirlo para no registrar una alarma falsa.

**Consecuencia para quien trabaje aquí:** los escenarios de esos dos módulos son los de
`docs/flujos/pdc-v2.md`. Lo único propio de Contratos es el flujo semi-automático, y hoy
está atado al PDC (ver `PDC-007` y `memoria/trampas/semi-auto-solo-lo-usa-pdc.md`).

Los módulos con backend propio son cuatro: **subcontratistas, profesionales, control de cambios** y
**escalamientos**.

## SOP-001 · Cada módulo de soporte separa ver de editar

- **Pasos:** los tres controladores verificados exigen `rbac_guard_require_permission` con dos
  claves distintas:

| Controlador | Claves |
|---|---|
| `SubcontratistasApiController` | `lps.subcontratistas.ver` / `.editar` |
| `ProfesionalesApiController` | `lps.profesionales.ver` / `.editar` |
| `ControlCambiosApiController` | `lps.control_cambios.ver` / `.editar` |

- **Resultado esperado:** consultar y modificar son llaves separadas en los tres. Un rol puede leer
  el registro de subcontratistas sin poder alterarlo.
- **Verificación:** lectura — los tres archivos en `src/Controllers/Api/`.

> Nótese que el Residente tiene `lps.subcontratistas.editar` pero solo `lps.profesionales.ver`
> (`RbacCatalog::fallbackPermissionsByRole()`): puede mantener el registro de subcontratistas, no el
> de profesionales.

## SOP-002 · Toda mutación autenticada debe exigir token CSRF

- **Resultado esperado:** una petición de escritura sin token válido se rechaza y no escribe nada.
  Lo exige `AGENTS.md` §Seguridad para **toda** mutación autenticada.

> **Hallazgo del 2026-08-04 — CORREGIDO el 2026-08-06 en `88ba6e0d` (más `ca642189`).** Los seis
> controladores mutaban sin validar CSRF: `CicApiController` (10 sentencias de escritura),
> `ProfesionalesApiController` (5), `SubcontratistasApiController` (4), `ControlCambiosApiController`
> (3), `CnpApiController` (2) y `CncApiController` (1). Todos autorizaban solo con
> `rbac_guard_require_permission()`, que comprueba permiso pero **no valida token**. Verificado el
> 2026-08-07: los seis validan ahora.
>
> **La lección que deja, y por eso no se borra:** la pieza ya existía. `legacy_require_csrf()` vivía
> en `src/Legacy/rbac_guard.php`, en el mismo archivo que los seis ya incluían, y solo la llamaban
> dos scripts legados. No faltaba la herramienta: faltaba llamarla. Cuando una defensa depende de
> que cada autor se acuerde de invocarla, seis módulos pueden olvidarla a la vez sin que nada avise.

## SOP-003 · El registro de subcontratistas alimenta la calificación

- **Precondiciones:** existe el subcontratista en su registro.
- **Resultado esperado:** el CIC califica **por subcontratista**, uniendo contra la tabla de
  subcontratistas del proyecto (`CicApiController:149,154`). Un subcontratista sin registro no puede
  calificarse.
- **Verificación:** lectura — `src/Controllers/Api/CicApiController.php:149-154`; escenarios
  `APR-003` y `APR-004`.

---

## Escenarios pendientes de esta pasada

- **Escalamientos y crisis** (10 rutas): no se verificó ninguno. Es el módulo de soporte más grande
  y queda entero.
- **Control de cambios**: qué se registra exactamente y cómo se relaciona con la línea base del
  programa general.
- **Profesionales**: la trampa `bitacora-drawer-sin-profesional` ya avisa de un caso de dato
  incompleto; merece escenario.
- **Aislamiento por `project_id`** comprobado consulta a consulta en los cuatro módulos propios.
