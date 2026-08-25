---
capa: fuente
tipo: biblia
estado: vigente
fecha: 2026-08-04
areas: [lps]
fuente: docs/flujos/transversal-proyecto.md
resumen: Escenarios PROY-. Qué proyectos debe ver cada cuenta, qué pasa al entrar en uno, y qué queda en sesión — el estado del que dependen las otras cuatro tandas.
---

# Biblia · Transversal · Selección de proyecto

Escenarios `PROY-*`. Qué proyectos debe ver cada cuenta, qué pasa al entrar en uno, y qué queda en
sesión — el estado del que dependen las otras cuatro tandas.

Formato y reglas: `docs/flujos/README.md`. Verificado por lectura el **2026-08-04**.

---

## PROY-001 · La lista solo muestra proyectos donde la cuenta es miembro

- **Rol:** cualquiera con sesión.
- **Precondiciones:** la cuenta tiene filas en `project_members`.
- **Pasos:**
  1. `GET /proyectos` llega a `ProjectSelectorController::index()`.
  2. La consulta parte de `project_members`, une con `general_usuarios` y
     `general_proyectos_procesos`, y filtra por: el usuario de la sesión, `Area IN ('Construccion',
     'Pre-Construccion')`, `Activo = 1` y `(Acceso = 1 OR pm.role IN ('A','D'))`.
- **Resultado esperado:** se listan **solo** esos proyectos, ordenados por nombre. Un proyecto donde
  la cuenta no es miembro no aparece jamás. **En datos:** nada cambia.
- **Verificación:** lectura — `src/Controllers/Core/ProjectSelectorController.php:30-43`.

> **Aviso para quien lea la pantalla:** la barra de progreso que se muestra por proyecto es
> `rand(0, 100)` (`:49`). **No es un dato**, es relleno visual. Nadie debe tomar decisiones con ese
> número ni construir nada encima de él.

## PROY-002 · Un proyecto cerrado sigue siendo accesible para Admin y Director

- **Rol:** `A` o `D` frente al resto.
- **Precondiciones:** proyecto con `Acceso = 0` y `Activo = 1`.
- **Pasos:** se pide `/proyectos`.
- **Resultado esperado:** `A` y `D` **sí** lo ven en la lista; los demás roles **no**. **En datos:**
  nada.
- **Verificación:** lectura — `ProjectSelectorController.php:41` (`p.Acceso = 1 OR pm.role IN
  ('A','D')`).

## PROY-003 · Entrar a un proyecto donde no se es miembro se rechaza

- **Rol:** cualquiera con sesión.
- **Precondiciones:** el nombre del proyecto existe pero la cuenta no tiene fila en
  `project_members` para él.
- **Pasos:**
  1. `POST /proyecto/seleccionar` (o la puerta de servicio con `p=<nombre>`) llega a
     `enterProject()`.
  2. La consulta no devuelve fila.
- **Resultado esperado:** se guarda en sesión el error «No tienes permiso para acceder a este
  proyecto» y vuelve a `/proyectos`. **En datos:** nada; **la sesión no cambia de proyecto**.
- **Verificación:** lectura — `ProjectSelectorController.php:114-119`.

## PROY-004 · Entrar a un proyecto cerrado se rechaza salvo Admin y Director

- **Rol:** cualquiera menos `A` y `D`.
- **Precondiciones:** el proyecto tiene `Acceso = 0`.
- **Pasos:** `enterProject()` obtiene la fila y **después**, ya en PHP, comprueba
  `Acceso === 0 && !in_array($permiso, ['A','D'])`.
- **Resultado esperado:** error «El proyecto seleccionado se encuentra inactivo para tu perfil» y
  vuelta a `/proyectos`. **En datos:** nada.
- **Verificación:** lectura — `ProjectSelectorController.php:122-126`.

## PROY-005 · Al entrar, la sesión guarda el proyecto y su identificador

- **Rol:** cualquiera admitido.
- **Precondiciones:** las de `PROY-004` superadas.
- **Pasos:** `enterProject()` escribe en sesión.
- **Resultado esperado:** `$_SESSION['proyecto']` con el nombre y `$_SESSION['project_id']` con el
  identificador numérico. **Ese `project_id` es el que aísla todas las consultas operativas del
  resto de tandas**, así que un valor mal puesto aquí se propaga a toda la aplicación.
- **Verificación:** lectura — `ProjectSelectorController.php:128-130`.

## PROY-006 · La puerta de servicio y el selector deben dejar la misma sesión

Escenario de invariante. El propio código lo declara como requisito en `:81-85`: la normalización
del rol y el respeto a `Acceso = 0` «deben ser idénticos» por ambos caminos.

- **Rol:** el real de `project_members`, por los dos caminos.
- **Precondiciones:** misma cuenta, mismo proyecto.
- **Pasos:** entrar por `/dev/entrar?u=…&p=…` y, por separado, por `/proyectos` + selección.
- **Resultado esperado:** **el mismo rol en sesión y el mismo `project_id`**. La puerta de servicio
  no concede permisos por encima de los de la cuenta.
- **Verificación:** lectura — ambos caminos convergen en `enterProject()`
  (`ProjectSelectorController.php:86`).

> **Hallazgo del 2026-08-04 (registrado, no corregido).** La invariante se cumple para el *camino*
> —los dos entran por `enterProject()`— pero **no para el filtro de `Acceso`**, que se aplica en dos
> sitios distintos y con dato distinto:
>
> | | Dónde filtra `Acceso` | Con qué rol compara |
> |---|---|---|
> | `index()` (la lista) | En **SQL**, `:41` | `pm.role` **crudo**, sin normalizar |
> | `enterProject()` (la entrada) | En **PHP**, `:122` | `$permiso` **ya normalizado** |
>
> Consecuencia: para una cuenta cuyo `pm.role` no sea literalmente `A` o `D` pero **normalice** a
> uno de ellos, el proyecto cerrado **no aparece en la lista** y sin embargo **sí deja entrar** si
> se llega por nombre. Es incoherencia, no escalada de privilegios —el rol final es el mismo por
> ambos lados—, pero contradice la invariante que el propio archivo se impone.

## PROY-007 · Un rol que el normalizador no reconoce cae en solo lectura

- **Rol:** cualquiera con un valor raro en `project_members.role`.
- **Precondiciones:** `role` con un valor fuera del catálogo (por ejemplo, un cargo escrito en
  texto).
- **Pasos:** `normalizeRoleCode()` (`:161-179`) aplica sus reglas: `P → D`, `U` o vacío `→ V`, y
  **cualquier valor que no esté en `RoleManager::getAll()` → `V`**.
- **Resultado esperado:** la sesión queda como **Visualizador**. Es la degradación segura correcta:
  ante la duda, solo lectura.
- **Verificación:** lectura — `ProjectSelectorController.php:161-179`.

> **Segundo hallazgo del mismo escenario (registrado, no corregido).** Ese normalizador **no es** el
> que manda `AGENTS.md`. El contrato dice normalizar «mediante `Admin\Core\RoleManager::cleanCargo()`»,
> y aquí hay un `normalizeRoleCode()` privado que solo conoce `P` y `U`. Mientras tanto,
> `RbacCatalog::roleAliases()` (`src/Security/RbacCatalog.php:13-23`) sí traduce cargos escritos en
> texto —`'DIRECTOR DE OBRA' => 'D'`, `'RESIDENTE DE OBRA' => 'R'`, y seis más— y **este camino no
> lo consulta**.
>
> Efecto: una cuenta cuyo `role` sea `'Director de Obra'` entra como **Visualizador**, no como
> Director. Falla hacia el lado seguro, así que no es un agujero; es pérdida silenciosa de permisos
> legítimos, y el usuario afectado no tiene forma de entender por qué ve la aplicación en solo
> lectura.

---

## Escenarios pendientes de esta pasada

- Qué ocurre al pedir una ruta operativa **con sesión pero sin proyecto** seleccionado: es el que
  más rutas afecta y merece su propio escenario verificado.
- Cambio de proyecto en caliente: qué se limpia de la sesión anterior (semana en curso, filtros) y
  qué se arrastra.
