---
capa: wiki
tipo: mapa
estado: vigente
fecha: 2026-08-02
areas: [rbac]
fuente: sesion
resumen: "Roles, capacidades, rutas protegidas y sesión: dónde se resuelve cada cosa y qué hay que probar al cambiarlas"
---
# Mapa · RBAC y rutas

## Qué manda

**El comportamiento esperado vive en la biblia, no aquí.** Este mapa dice dónde está el código;
[[docs/flujos/transversal-rbac|la biblia de RBAC]] dice qué debe pasar, capacidad por capacidad, y
[[docs/flujos/transversal-autenticacion|la de sesión]] y [[docs/flujos/transversal-proyecto|la de
proyecto]] hacen lo propio con la entrada. Son capas distintas: si la biblia y el código divergen,
es un bug de uno de los dos — regla contraria a la de esta wiki, y deliberada
([[docs/flujos/README|por qué]]).

- `memoria/arquitectura/` y `memoria/flujos/` — inventario de rutas por módulo y matriz de
  navegación. Reemplazan al retirado `docs/ROUTES.md` (2026-08-03), que no viajaba en git.
- [[docs/rbac_roles_reference]] y [[docs/rbac_cargos_roles_dictionary]] — roles y cargos.
- [[AGENTS]] — obliga a verificar **un rol permitido y uno denegado** cuando cambien rutas
  protegidas, sesión, middleware o capacidades.

## Cómo se resuelve un permiso

`App\Security\RbacCatalog` define los códigos de rol (`A` Admin, `D` Director de Obra, `R`
Residente, `DCV`, `OT`, `G` Ambiental, `S` SST, `SG`, `C` Subcontratista, `V` Visualizador), los
alias legados y las constantes de permiso.

`RbacManager::getCapabilities($rol)` devuelve un mapa plano de booleanos calculado con listas
`in_array` escritas a mano. **No hay tabla de permisos en la base de datos.** Normaliza siempre el
rol entrante con `App\Security\RbacService::normalizeRole()` antes de comprobar nada, y deja solo
lectura como respaldo seguro.

**Corregido el 2026-08-10.** Esta página decía `Admin\Core\RoleManager::cleanCargo()` porque lo
copiaba de `AGENTS.md`, y allí era un error: `cleanCargo()` limpia **texto** de cargos —minúsculas,
sin acentos, géneros normalizados— para emparejarlos por aproximación dentro de
`suggestRoleByCargo()`, y devuelve `"director obra"`, nunca `'D'`. La que traduce alias a código
canónico es `RbacService::normalizeRole()`. Se corrigieron a la vez `AGENTS.md`, `CLAUDE.md` y
`GEMINI.md`: el error estaba en los tres contratos y la wiki lo heredó.

Políticas específicas viven aparte: `LpsWeekEditPolicy`, `DesignSystemLabAccessPolicy`,
`CommitmentLockGuard`.

## Sesión

Se aplica en `SessionMiddleware::check()` desde el front controller. Toda mutación autenticada
lleva CSRF, y las consultas van por sentencias preparadas de la capa `Database`.

Para abrir sesión en local se usa **siempre** la puerta de servicio, nunca el formulario de login:
[[dev-door-acceso-local]]. Es también la vía para cubrir el rol permitido y el denegado, porque el
rol que queda en sesión es el real de `project_members`.

Si la sesión parece caerse durante QA en navegador, antes de diagnosticar lee
[[sesion-cae-en-el-panel]]: casi siempre es el panel, no la aplicación.

## Trampas medidas

[[reabrir-semana-asimetria-cliente-servidor]] — **derogada, y aquí se citaba en presente hasta el
2026-08-18.** Describía que reabrir semana escondía el botón en cliente salvo al rol `A` mientras el
servidor solo exigía una capacidad de edición genérica —que el Residente tiene—, y que el log
escribía siempre «reabierta por Admin». `6dcec299` (2026-08-10) lo cerró extrayendo
`SemanalReabrirPolicy`, que decide en el servidor antes de mutar
(`src/Controllers/Api/SemanalApiController.php:1003`). Se conserva por la lección: el cliente puede
esconder, solo el servidor puede impedir.

[[logout-no-limpia-la-sesion-pendiente-de-clave]] — la sesión a medias del cambio obligatorio de
contraseña no se limpia con `/logout`: esa ruta no es pública, así que el middleware la redirige a
`/login` sin destruir nada. Regla general: lo que deba funcionar sin sesión completa va en
`$publicRoutes`.

## El área, en una tabla

<!-- Vista nativa de Obsidian Bases. Si no renderiza, el contenido de arriba sigue siendo
     legible: los plugins y las vistas amplifican, no sostienen. -->
![[area-rbac.base]]

## Vecinos

[[arquitectura]] para el despacho de rutas · [[qa-y-gates]] para cómo probar esto.
