# AGENTS.md — Last Planner AIA

## Autoridad y alcance

- Este archivo complementa la guía global con contratos propios del repositorio. La petición actual y el `AGENTS.md` más cercano siguen determinando el alcance efectivo.
- Aplica lecturas, comprobaciones y validaciones solo cuando correspondan al área modificada y de forma proporcional al riesgo. Reutiliza evidencia vigente de la misma sesión cuando sus entradas no hayan cambiado; empieza por la comprobación enfocada y amplía solo si el impacto o los resultados lo justifican.
- Si el usuario nombra un goal o sprint, lee primero `goals/<slug>/goal.md`, `plan.md` y `facts.md` cuando existan y sean pertinentes para la petición. Consulta `validation-log.md`, `checklist.md`, `evidence.md` o `gates.md` únicamente cuando controlen el trabajo solicitado. No mezcles otros goals, módulos o sprints sin autorización.
- Conserva el worktree existente. Antes de editar, revisa estado y diff; no reviertas, limpies ni incluyas cambios ajenos. Evita refactors de cortesía y mantén cada cambio ligado al objetivo activo.
- `memoria/` es la wiki del proyecto: contexto, nunca contrato. Precedencia ante conflictos: **código > este archivo > `memoria/`**. Una nota que contradiga al repo se corrige y se marca `estado: derogada`. La escribe el asistente, no se edita a mano, y su esquema está en `CLAUDE.md`.

## Runtime local

- Docker Compose es la fuente de verdad local. Los servicios declarados son `app`, `db` y `adminer`; la app se sirve en `http://localhost:8081`, Adminer en `http://localhost:8082` y MySQL en el host por el puerto `3307`.
- Comprueba el runtime con `docker compose config --services` y `docker compose ps` una vez por sesión antes de usarlo. Repite la comprobación solo si cambian contenedores, imágenes, configuración o mounts, o si aparece evidencia de estado obsoleto; confirma estado y versión antes de validar en navegador cuando sean relevantes.
- `docker-compose.override.yml` monta el código local para PHP, JS y CSS. No reconstruyas ni recrees servicios por rutina; hazlo solo cuando cambien imagen, dependencias o configuración y el alcance lo autorice.
- Ejecuta PHP y herramientas del proyecto dentro de `app`. No uses MAMP/XAMPP ni un PHP del host como sustituto del runtime integrado.

## Arquitectura y datos

- La aplicación combina MVC moderno y legado. `public/index.php` es el Front Controller y define rutas; el código moderno vive en `src/`, las vistas en `views/` y el panel Admin en `admin/`. `src/Legacy/` es mantenimiento: no agregues allí funcionalidad nueva salvo que el objetivo lo exija.
- La arquitectura vigente usa tablas globales compartidas y todas las consultas operativas deben aislarse por `project_id`. `Base_de_Datos`, `dbPrefix` y `{prefix}_*` son compatibilidad histórica, no permiso para introducir SQL dinámico nuevo.
- Antes de tocar schema, migraciones, backfills, limpieza o lifecycle de proyectos, lee `docs/global-tables-architecture.md`. Empieza con dry-run; cualquier aplicación o borrado exige gate de Plannotator, respaldo verificable, estrategia de restauración y reconciliación posterior.
- Listado de Actividades, Contratos y PDC comparten los contratos `auto/preview`, `auto/apply`, `auto/undo`, `auto/feedback` y `auto/metrics`. Reutiliza `public/js/modules/semi_auto_review.js` y los servicios existentes; no crees un flujo paralelo sin evidencia.

## Seguridad y permisos

- Las capacidades se resuelven en `App\Security\RbacManager`; las constantes de recursos y rutas viven en `RbacCatalog`. Normaliza alias de roles mediante `Admin\Core\RoleManager::cleanCargo()` y conserva solo lectura como fallback seguro.
- Toda ruta protegida debe respetar sesión, proyecto y capacidad. Mantén CSRF en mutaciones autenticadas y usa prepared statements a través de la capa `Database`; no construyas SQL con datos de usuario.
- Obtén credenciales de desarrollo únicamente desde `.env`, seeds o fixtures autorizados cuando una prueba las requiera. Nunca copies secretos a instrucciones, logs, commits, capturas, prompts ni servicios externos.
- **La sesión local se abre siempre por la puerta de servicio, nunca por `/login`.** Usa `http://localhost:8081/dev/entrar?u=<cuenta>&p=<Proyecto_Proceso>` con una de las cuentas sembradas `test.A` (rol A), `test.R` (rol R) o `test.V` (rol V); sin `p` aterriza en `/proyectos`. No teclees credenciales en el formulario de login ni le pidas a una persona que inicie sesión por ti. El rol que queda en sesión es el real de `project_members`, así que esta es también la vía para cubrir el rol permitido y el denegado que exige el routing de RBAC. Requiere `DEV_DOOR=1` y `DEV_DOOR_USERS` en `.env`; si redirige a `/login` o responde 404, la puerta está cerrada. Editar `APP_ENV` en `.env` **no** la cierra bajo Docker, porque el contenedor inyecta esa variable y `Dotenv::createImmutable()` no la sobrescribe: usa `DEV_DOOR=0`. Diseño y candado en `docs/superpowers/specs/2026-07-30-dev-door-design.md`, `src/Core/DevDoor.php` y `tests/test_dev_door_guard.php`. Es un camino exclusivo de desarrollo: no existe en producción y no concede permisos por encima de los de la propia cuenta.

## Routing por tipo de cambio

- **Tests o flujos:** revisa `docs/qa/workflows.md` y el flujo real cuando cambies un flujo, su contrato o una prueba de extremo a extremo. Para ajustes aislados de pruebas, consulta solo el contrato directamente afectado. Documenta el comportamiento antes de modificar una prueba cuando aún no esté claro; no adaptes tests para ocultar una regresión.
- **UI, frontend o diseño compartido:** limita toda implementación, revisión, validación y diseño a vistas desktop de al menos 1180 px y exclusivamente en dark mode. Usa 1180×820 como viewport desktop canónico y principal de validación para esta aplicación. No trabajes ni generes cambios, pruebas o evidencia para mobile, tablet o el tema `linen`. Si una petición futura solicita cualquiera de esos alcances prohibidos, indica explícitamente esta prohibición y no procedas con esa parte de la petición. Esta restricción no bloquea backend, datos ni pruebas no visuales que no dependan de viewport o tema.
- **Contratos visuales permitidos:** dentro del alcance desktop dark, lee primero `DESIGN.md` (contrato de consumo) y luego `docs/design-system/README.md` y los contratos pertinentes cuando cambies apariencia, tokens o componentes compartidos. Cambia primero tokens, componentes o capas canónicas; evita hex, estilos inline y variantes locales en módulos migrados. Respeta accesibilidad, focus y ausencia de overflow horizontal en el viewport permitido, sin ampliar la validación a otros temas o viewports.
- **Persistencia:** prueba escribir, recargar y recuperar el estado. En datos compartidos añade aislamiento entre proyectos y prueba restauración cuando el riesgo lo amerite.
- **Rutas, sesión o RBAC:** contrasta `public/index.php`, `docs/ROUTES.md`, catálogos y middleware según el área modificada. Consulta el inventario canónico y matriz de navegación completa en `docs/ROUTES.md`. Verifica al menos un rol permitido y uno denegado cuando cambien rutas protegidas, sesión, middleware, capacidades o RBAC.
- **Legado:** corrige la causa con el cambio mínimo. No modernices áreas adyacentes ni muevas contratos sin cobertura.

## Verificación

- Ejecuta primero la prueba enfocada y amplía según el riesgo. Comandos canónicos incluyen `docker compose exec app php tests/test_global_table_safety.php`, `docker compose exec app php tests/test_global_table_reconciliation.php`, `npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1` y `docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G`.
- Para cambios frontend observables o bugs visibles, valida el comportamiento en el navegador contra el contenedor servido y revisa la consola. Revisa la red cuando el flujo dependa de peticiones HTTP o presente fallos de carga, y conserva evidencia proporcional al riesgo. No repitas una validación si sus entradas no cambiaron. No regeneres snapshots ni baselines para forzar un resultado verde; los cambios visuales requieren aprobación explícita.
- Reporta qué verificaste, comandos y resultado, límites pendientes y cualquier dato tocado o restaurado.

## Publicación

- No hagas commit, push ni deploy salvo petición explícita. Si se autoriza publicar, usa staging selectivo, diff revisado y commit atómico; nunca incluyas `.env`, evidencia local o trabajo ajeno.
- Sigue `docs/siteground-deploy-routine.md`: pruebas antes que producción, respaldo previo, `pull --ff-only`, Composer ejecutado con PHP 8.3 y smoke funcional del flujo afectado. Una publicación aprobada no autoriza limpiar drift del servidor ni desplegar otros cambios.
