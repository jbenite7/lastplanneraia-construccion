---
tipo: mapa
estado: vigente
fecha: 2026-08-02
areas: [rbac, rutas]
fuente: sesion
resumen: "Roles, capacidades, rutas protegidas y sesión: dónde se resuelve cada cosa y qué hay que probar al cambiarlas"
---
# Mapa · RBAC y rutas

## Qué manda

- `docs/ROUTES.md` — inventario canónico de superficies y matriz de navegación. **No viaja en
  git** (está en `.gitignore`), así que en un clon fresco no existe.
- `docs/rbac_roles_reference.md` y `docs/rbac_cargos_roles_dictionary.md` — roles y cargos.
- [[AGENTS]] — obliga a verificar **un rol permitido y uno denegado** cuando cambien rutas
  protegidas, sesión, middleware o capacidades.

## Cómo se resuelve un permiso

`App\Security\RbacCatalog` define los códigos de rol (`A` Admin, `D` Director de Obra, `R`
Residente, `DCV`, `OT`, `G` Ambiental, `S` SST, `SG`, `C` Subcontratista, `V` Visualizador), los
alias legados y las constantes de permiso.

`RbacManager::getCapabilities($rol)` devuelve un mapa plano de booleanos calculado con listas
`in_array` escritas a mano. **No hay tabla de permisos en la base de datos.** Normaliza siempre el
cargo entrante con `Admin\Core\RoleManager::cleanCargo()` antes de comprobar nada, y deja solo
lectura como respaldo seguro.

Políticas específicas viven aparte: `LpsWeekEditPolicy`, `DesignSystemLabAccessPolicy`,
`CommitmentLockGuard`.

## Sesión

Se aplica en `SessionMiddleware::check()` desde el front controller. Toda mutación autenticada
lleva CSRF, y las consultas van por sentencias preparadas de la capa `Database`.

Para abrir sesión en local se usa **siempre** la puerta de servicio, nunca el formulario de login:
[[dev-door-acceso-local]]. Es también la vía para cubrir el rol permitido y el denegado, porque el
rol que queda en sesión es el real de `project_members`.

Si la sesión parece caerse durante QA en navegador, antes de diagnosticar lee
[[browser-qa-pitfalls]]: casi siempre es el panel, no la aplicación.

## Vecinos

[[arquitectura]] para el despacho de rutas · [[qa-y-gates]] para cómo probar esto.
