# Ocultar Control Tower de la navegación, dejándolo accesible a Admin

**Fecha:** 2026-08-13 · **Estado:** aprobado en chat

## Objetivo

Mientras el módulo BI (Control Tower) se termina de desarrollar, no debe aparecer en
ninguna parte de la interfaz: ni en la barra lateral, ni en el selector de proyectos, ni
en el cajón contextual, ni en los botones «BI …» de los módulos. El módulo **sigue
funcionando** por URL directa para quien lo está desarrollando.

## Decisiones (grilleo 2026-08-13)

1. **Ocultar los accesos, no apagar el módulo.** Corrección del usuario a mitad del
   diseño: la primera versión de este spec bloqueaba las rutas con 404 en los servidores,
   y eso le impedía a él mismo revisar el avance. Las rutas siguen vivas.
2. **Solo Admin (rol `A`) puede abrirlo por URL.** Los demás roles no ven el módulo ni
   pueden entrar aunque tengan permiso de indicadores: el objetivo es que nadie tropiece
   con trabajo a medias.
3. **Sin dependencia del entorno.** El gate es por rol, igual en local, pruebas y
   producción. Se descartó el gate por `APP_ENV` (y con él la trampa de que pruebas
   declara `APP_ENV="testing"`, verificado por SSH el 2026-08-13) porque ya no hace falta:
   el rol resuelve el caso en todos los entornos.
4. **Sin flag en `.env`.** Se descartó `BI_ENABLED` para no añadir otra llave de
   configuración no versionada que derive por máquina.

## Los enlaces se ocultan para todos, incluido Admin

Es deliberado y es lo que pidió el usuario: «ocultar los accesos desde el frontend, pero
dejar el módulo habilitado». Admin entra tecleando `/bi/control-tower`, no desde la
barra lateral. Así la interfaz queda igual de limpia para todos y no hay dos versiones de
la navegación que mantener.

## Cambios

| Pieza | Cambio |
|---|---|
| `src/Security/BiPreviewAccessPolicy.php` (nuevo) | Unidad única del gate. `canOpen(array $session): bool` resuelve el rol como hace `DesignSystemLabAccessPolicy::resolveRole()` (por usuario, no por el proyecto seleccionado) y responde a la nueva capacidad. |
| `src/Security/RbacCatalog.php` | Constante `PERM_INTERNAL_BI_PREVIEW = 'internal.bi.preview'` + su fila en el catálogo de permisos (junto a la del design system, línea ~116). |
| `src/Security/RbacManager.php:29` | La capacidad se concede con `$isSystemAdmin`, misma línea que `PERM_INTERNAL_DESIGN_SYSTEM_VIEW`. |
| `src/View/Components/BiAccessComponent.php` | `canAccess()` y `canAccessAny()` devuelven `false` siempre. Con eso desaparecen: barra lateral (`shell_sidebar.php:76`), selector de proyectos (`project_selector.view.php:30,56`), tarjeta del cajón (`drawer_unificado.php:23`), los 5 botones «BI …» (`renderLink`) y los boot-configs JS (`renderBootConfig`). |
| `src/Controllers/Bi/BiViewController.php` | En `renderView()` —choke point de las 8 vistas `/bi/*`— si `BiPreviewAccessPolicy::canOpen($_SESSION)` es falso, responde 404 vía `ErrorPage::render()` y termina. 404 y no 403, como el laboratorio del design system: no confirma que la pantalla existe. |
| `src/Controllers/Api/BiControlTowerApiController.php` | Mismo gate en el constructor, que es el punto común de las ~20 acciones. `ErrorPage` ya devuelve JSON para rutas `/api/*`. |
| `src/Legacy/datosGeneralesPagina.php:30` | `canAccessBi` deja de calcularse con RBAC directo y pasa por `BiAccessComponent::canAccess()`, para que el JSON que consume el JS diga lo mismo que la vista. |
| `tests/test_bi_preview_gate.php` (nuevo) | Test autónomo estilo `test_dev_door_guard.php`. Declara `// @requiere: db`: `RbacService::__construct()` llama a `Database::getInstance()` aunque `normalizeRole()` no consulte nada. |
| `tests/test_bi_project_scope.php:119-127` | Dos comprobaciones afirman que el acceso global BI **se ve**; con el componente apagado dejan de distinguir nada. Se reescriben contra `BiProjectScope` directamente, que es el dueño del alcance y no cambia. |
| `tests/browser/bi_control_tower_access.spec.mjs` | Suite dedicada a los seis botones «BI …». Se suspende con `test.describe.skip` y un comentario que apunta a este spec; quitar el `.skip` es toda la reversión. No se borra: su contenido sigue siendo el contrato correcto para después. |

**Suites que NO hay que tocar** (comprobado): `bi_control_tower.spec.mjs`, `bi-kpi-copy.spec.mjs`
y `shell-sidebar-rollout.mjs` entran con `test.A` (`tests/browser/fixtures/projects.mjs:16`),
que es Admin, y navegan a `/bi/*` por URL — justo lo que el gate sigue permitiendo.

## Lo que NO cambia

- `BiProjectScope` y `lps.indicadores.ver` siguen aplicando **después** del gate: Admin
  sigue necesitando acceso al proyecto que pide. El gate se suma, no sustituye.
- No se tocan datos ni esquema. No hay migraciones.
- La interfaz interna del módulo BI (sus 8 vistas y sus APIs) no se modifica.

## Verificación

- `docker compose exec app php tests/test_bi_preview_gate.php` en verde. Casos:
  rol `A` abre; roles `R`, `V`, `D` y `C` no; sesión vacía no.
- En el navegador con `test.A`: la barra lateral **no** muestra «Control Tower - Informes»
  y `/bi/control-tower` responde 200.
- En el navegador con `test.R`: no aparece el enlace, no aparece el botón «BI Semanal» en
  `/programacion-semanal`, y `/bi/control-tower` responde 404.
- `docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G` sin
  errores nuevos.

## Reversión

Cuando el módulo esté listo: devolver a `canAccess()`/`canAccessAny()` su cuerpo original
(el `git show` del commit lo tiene), quitar el gate de los dos controladores y borrar
`BiPreviewAccessPolicy` + su test. La capacidad puede quedarse o retirarse del catálogo.

## Archivos de este goal

Sin carpeta `goals/`: frente corto con spec propio. El cierre se anota en `memoria/log.md`.
