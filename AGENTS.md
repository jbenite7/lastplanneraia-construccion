---
capa: fuente
tipo: contrato
estado: vigente
fecha: 2026-07-30
tags: [leer-antes-de-tocar]
fuente: AGENTS.md
resumen: Contrato del repo para asistentes: runtime Docker, tablas globales con project_id, RBAC, routing por tipo de cambio y el gate de publicacion de nueve pasos.
---

# AGENTS.md — Last Planner AIA

## Autoridad y alcance

- Este archivo complementa la guía global con contratos propios del repositorio. La petición actual y el `AGENTS.md` más cercano siguen determinando el alcance efectivo.
- Aplica lecturas, comprobaciones y validaciones solo cuando correspondan al área modificada y de forma proporcional al riesgo. Reutiliza evidencia vigente de la misma sesión cuando sus entradas no hayan cambiado; empieza por la comprobación enfocada y amplía solo si el impacto o los resultados lo justifican.
- Si el usuario nombra un goal o sprint, lee primero `goals/<slug>/goal.md`, `plan.md` y `facts.md` cuando existan y sean pertinentes para la petición. Consulta `validation-log.md`, `checklist.md`, `evidence.md` o `gates.md` únicamente cuando controlen el trabajo solicitado. No mezcles otros goals, módulos o sprints sin autorización.
- Conserva el worktree existente. Antes de editar, revisa estado y diff; no reviertas, limpies ni incluyas cambios ajenos. Evita refactors de cortesía y mantén cada cambio ligado al objetivo activo.
- `memoria/` es la wiki del proyecto: contexto, nunca contrato. Precedencia ante conflictos: **código > este archivo > `memoria/`**. Una nota que contradiga al repo se corrige y se marca `estado: derogada`. La escribe el asistente, no se edita a mano, y su esquema está en `CLAUDE.md`.

- Las reglas de coordinación entre sesiones (frentes, vistos, relato de autorizaciones, contenedor compartido y efímero, base de dev, paso 0) viven en `docs/coordinacion-sesiones.md`: toda sesión las lee al arrancar.

## Runtime local

- Docker Compose es la fuente de verdad local. Los servicios declarados son `app`, `db` y `adminer`; la app se sirve en `http://localhost:8081`, Adminer en `http://localhost:8082` y MySQL en el host por el puerto `3307`.
- Comprueba el runtime con `docker compose config --services` y `docker compose ps` una vez por sesión antes de usarlo. Repite la comprobación solo si cambian contenedores, imágenes, configuración o mounts, o si aparece evidencia de estado obsoleto; confirma estado y versión antes de validar en navegador cuando sean relevantes.
- `docker-compose.override.yml` monta el código local para PHP, JS y CSS. No reconstruyas ni recrees servicios por rutina; hazlo solo cuando cambien imagen, dependencias o configuración y el alcance lo autorice.
- Ejecuta PHP y herramientas del proyecto dentro de `app`. No uses MAMP/XAMPP ni un PHP del host como sustituto del runtime integrado.

## Arquitectura y datos

- La aplicación combina MVC moderno y legado. `public/index.php` es el Front Controller y define rutas; el código moderno vive en `src/`, las vistas en `views/` y el panel Admin en `admin/`. `src/Legacy/` es mantenimiento: no agregues allí funcionalidad nueva salvo que el objetivo lo exija.
- La arquitectura vigente usa tablas globales compartidas y todas las consultas operativas deben aislarse por `project_id`. `Base_de_Datos`, `dbPrefix` y `{prefix}_*` son compatibilidad histórica, no permiso para introducir SQL dinámico nuevo.
- Antes de tocar schema, migraciones, backfills, limpieza o lifecycle de proyectos, lee `docs/global-tables-architecture.md`. Empieza con dry-run; cualquier aplicación o borrado exige gate de Plannotator, respaldo verificable, estrategia de restauración y reconciliación posterior.
- **El PDC v1 se eliminó el 2026-08-04.** Listado de Actividades, Contratos y el PDC viejo (`/pdc`, `/api/pdc/*`), con su asistente semiautomático (`SemiAutoService`, `semi_auto_review.js`, contratos `auto/preview·apply·undo·feedback·metrics`) y sus 18 tablas, ya no existen en el repo. Su sucesor es **Plan de Compras v2** (`/plan-compras`, `pdc-app/`, `src/Services/Pdc/`): ver `docs/pdc-v2.md`. No reintroduzcas rutas, servicios ni tablas del v1.

## Seguridad y permisos

- Las capacidades se resuelven en `App\Security\RbacManager`; las constantes de recursos y rutas viven en `RbacCatalog`. **Normaliza alias de roles con `App\Security\RbacService::normalizeRole()`** y conserva solo lectura como fallback seguro.
- **Corrección del 2026-08-10:** esta línea mandaba normalizar con `Admin\Core\RoleManager::cleanCargo()`, y era la función equivocada. `cleanCargo()` pasa a minúsculas, quita acentos y normaliza géneros (`admin/src/Core/RoleManager.php:67`): devuelve **texto limpio** como `"director obra"` para emparejar cargos por aproximación dentro de `suggestRoleByCargo()`, y **nunca** un código de rol. Usarla donde se espera un código habría roto `$_SESSION['permiso']` y toda llamada a `hasCapability()`. La que traduce alias a código canónico es `RbacService::normalizeRole()` (`src/Security/RbacService.php:18`), que consulta `RbacCatalog::roleAliases()` y ya usaba `BiProjectScope`. El error lo destapó un implementador que se negó a aplicar la instrucción sin comprobarla.
- Toda ruta protegida debe respetar sesión, proyecto y capacidad. Mantén CSRF en mutaciones autenticadas y usa prepared statements a través de la capa `Database`; no construyas SQL con datos de usuario.
- Obtén credenciales de desarrollo únicamente desde `.env`, seeds o fixtures autorizados cuando una prueba las requiera. Nunca copies secretos a instrucciones, logs, commits, capturas, prompts ni servicios externos.
- **La sesión local se abre siempre por la puerta de servicio, nunca por `/login`.** Usa `http://localhost:8081/dev/entrar?u=<cuenta>&p=<Proyecto_Proceso>` con una de las cuentas sembradas `test.A` (rol A), `test.R` (rol R) o `test.V` (rol V); sin `p` aterriza en `/proyectos`. Además existen `test.C` y `test.D`, sembradas pero no garantizadas: cuáles quedan habilitadas depende de `DEV_DOOR_USERS` en `.env`, que es **configuración local, no versionada, y puede variar por máquina** — no asumas la lista sin mirar el `.env` real. No teclees credenciales en el formulario de login ni le pidas a una persona que inicie sesión por ti. El rol que queda en sesión es el real de `project_members`, así que esta es también la vía para cubrir el rol permitido y el denegado que exige el routing de RBAC. Requiere `DEV_DOOR=1` y `DEV_DOOR_USERS` en `.env`; si redirige a `/login` o responde 404, la puerta está cerrada. Editar `APP_ENV` en `.env` **no** la cierra bajo Docker, porque el contenedor inyecta esa variable y `Dotenv::createImmutable()` no la sobrescribe: usa `DEV_DOOR=0`. Diseño y candado en `docs/superpowers/specs/2026-07-30-dev-door-design.md`, `src/Core/DevDoor.php` y `tests/test_dev_door_guard.php`. Es un camino exclusivo de desarrollo: no existe en producción y no concede permisos por encima de los de la propia cuenta.

## Routing por tipo de cambio

- **Tests o flujos:** revisa `docs/qa/workflows.md` y el flujo real cuando cambies un flujo, su contrato o una prueba de extremo a extremo. Para ajustes aislados de pruebas, consulta solo el contrato directamente afectado. Documenta el comportamiento antes de modificar una prueba cuando aún no esté claro; no adaptes tests para ocultar una regresión.
- **UI, frontend o diseño compartido:** dark es el tema por defecto y el que se valida; 1180×820 es el viewport desktop canónico y principal de validación. Otros viewports y un tema claro son admisibles cuando la petición lo pida, sin prohibición previa. Ten en cuenta el estado real del código antes de prometer alcance: el tema `linen` fue retirado del producto el 2026-07-25 (DS-030) y no existe conmutador, así que trabajar en claro implica reconstruirlo, no reactivarlo.
- **Contratos visuales permitidos:** dentro del alcance desktop dark, lee primero `DESIGN.md` (contrato de consumo) y luego `docs/design-system/README.md` y los contratos pertinentes cuando cambies apariencia, tokens o componentes compartidos. Cambia primero tokens, componentes o capas canónicas; evita hex, estilos inline y variantes locales en módulos migrados. Respeta accesibilidad, focus y ausencia de overflow horizontal en el viewport permitido, sin ampliar la validación a otros temas o viewports.
- **Persistencia:** prueba escribir, recargar y recuperar el estado. En datos compartidos añade aislamiento entre proyectos y prueba restauración cuando el riesgo lo amerite.
- **Rutas, sesión o RBAC:** contrasta `public/index.php`, catálogos y middleware según el área modificada. Consulta el inventario de rutas y la matriz de navegación en `memoria/arquitectura/` (una página por módulo, con las rutas generadas desde `public/index.php`) y en `memoria/flujos/`. Se regeneran con `node scripts/wiki-arquitectura.mjs --escribir`. `docs/ROUTES.md` se retiró el 2026-08-03: no viajaba en git y duplicaba un dato que ahora se genera. Verifica al menos un rol permitido y uno denegado cuando cambien rutas protegidas, sesión, middleware, capacidades o RBAC.
- **Legado:** corrige la causa con el cambio mínimo. No modernices áreas adyacentes ni muevas contratos sin cobertura.

## Verificación

- Ejecuta primero la prueba enfocada y amplía según el riesgo. Comandos canónicos incluyen `docker compose exec app php tests/test_global_table_safety.php`, `docker compose exec app php tests/test_global_table_reconciliation.php`, `npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1` y `docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G`.
- Para cambios frontend observables o bugs visibles, valida el comportamiento en el navegador contra el contenedor servido y revisa la consola. Revisa la red cuando el flujo dependa de peticiones HTTP o presente fallos de carga, y conserva evidencia proporcional al riesgo. No repitas una validación si sus entradas no cambiaron. No regeneres snapshots ni baselines para forzar un resultado verde; los cambios visuales requieren aprobación explícita.
- Reporta qué verificaste, comandos y resultado, límites pendientes y cualquier dato tocado o restaurado.
- **Las casillas `- [ ]` de un plan no son evidencia, ni en un sentido ni en el otro.** El avance se lee de la sección `## Cierre` del plan y del historial de git. Medido el 2026-08-24: de 2.127 casillas en 71 planes vivos solo 162 están marcadas, y hay planes cerrados y en producción con todas sus casillas vacías — contarlas reporta que no se ha hecho nada. **Y no se marcan retroactivamente:** la regla y su porqué los fijó el cierre de la campaña de dark mode el 2026-08-07 (`docs/superpowers/plans/2026-08-04-cierre-dark-mode-campana-decisiones.md`), reescribir casillas sin haber presenciado cada paso «sería fabricar evidencia». Si un plan cerrado tiene casillas sin marcar, eso no es una tarea pendiente: es el estilo del repo.

## Publicación

### El gate de cierre de frente es bloqueante

**No se permite abrir un frente nuevo mientras el anterior no esté publicado en `main`.** Un frente
no está cerrado cuando su trabajo funciona: está cerrado cuando su trabajo funciona **y está en el
remoto**. Autorización y exigencia permanentes del usuario, 2026-08-10.

La unidad es el **frente**, no la tarea ni la sesión. Dentro de un frente se commitea por tarea; el
cierre es lo que se publica.

**Procedimiento del gate, en este orden y sin saltarse pasos:**

1. **Verificar la condición de hecho** del frente con salida real de comandos de esa sesión. Si algo
   está rojo, el frente no cierra y no hay nada que publicar.
2. **Commitear lo que quede suelto**, con staging selectivo y commits atómicos. `git status` debe
   quedar limpio. Nunca `.env`, evidencia local ni trabajo ajeno.
3. **`git fetch origin`** y mirar la divergencia (`git status -sb`). Este repositorio tiene varias
   sesiones escribiendo a `origin/main` a la vez: asumir que nadie más avanzó es la vía rápida a
   pisar trabajo ajeno.
4. **Si hay divergencia, integrar** (`git merge origin/main`) y resolver los conflictos a la vista,
   nunca a ciegas. Jamás `push --force` ni reescritura de historia publicada. **Integra en tu propia
   rama, dentro de tu worktree.** No fusiones en el `main` del worktree principal: está checkouteado
   ahí y es estado compartido que otras sesiones mueven — el porqué está en el paso 6.
5. **Re-verificar después de integrar, no antes.** Es el paso que más se salta y el que más caro
   sale: traer trabajo ajeno puede romper un verde propio sin tocar tu diff. Medido el 2026-08-10
   dos veces en la misma jornada — un merge dejó la suite estática en 6/8 al destapar un módulo sin
   evidencia, y un segundo cierre la volvió a dejar en 6/8 porque el contrato fija por hash unos
   archivos que el frente había editado. Ninguno de los dos lo detectó quien hizo el trabajo: los
   detectó la verificación posterior a la integración. **Anota el SHA que verificas**
   (`git rev-parse HEAD`): es el que debes publicar, y el paso 7 lo compara.
6. **Publicar el commit exacto que verificaste, en un comando aparte**, desde tu worktree.

   **Desde el 2026-08-18 la forma obligatoria es `bash scripts/publicar.sh`** (decisión del usuario,
   D-COORD-3). El script hace los pasos 5, 6 y parte del 3: verifica, lee cada código de salida **en
   su propia línea**, comprueba que no haya commits entrantes ni árbol sucio, y **solo entonces**
   publica con `HEAD:main`. Deniega con `RC=1`. Se obliga porque las dos reglas de abajo llevaban tres
   jornadas incumpliéndose por sesiones distintas: mientras el gate viva solo en esta prosa, se salta
   sin querer. Lo que el script **no** hace, a propósito: no instala nada en `.git/hooks` ni toca
   `core.hooksPath`, así que no cambia el entorno de quien clone el repo — es un comando que se
   invoca, y por eso esta línea existe.

   `git push origin HEAD:main` a mano sigue siendo válido cuando el script no aplique (por ejemplo,
   un repo sin sus dependencias instaladas), y entonces las dos reglas de abajo se cumplen a pulso:

   Dos reglas, cada una con su medida detrás:
   - **Nunca encadenado al paso 5** con `&&`, `;` ni detrás de un `echo`: un gate solo gobierna si
     puede **impedir** la publicación, y encadenado ya se ejecutó. Lee el código de salida de la
     verificación —sin tubería, que `$?` sería del último tramo, y en zsh `PIPESTATUS` no existe:
     es `pipestatus` y va 1-indexado, así que la lectura sale vacía y no gobierna nada— y solo
     entonces publica. Ocurrió tres veces: el 2026-08-10 y el 11 **una misma sesión** encadenó el
     push detrás de un `echo` y publicó pese a una verificación que había devuelto `2` —el árbol
     quedó sano por suerte, no por el procedimiento—; el 2026-08-18 **otra** leyó la suite con
     `${PIPESTATUS[0]}` bajo zsh, obtuvo una lectura vacía y la cazó a tiempo. La causa no es
     despiste: es meter verificación y publicación en la misma línea.
   - **Nunca `git push origin main` después de fusionar en el worktree principal.** Ese comando no
     publica lo que mediste: publica **a dónde apunte `main` en el instante del push**, y `main` es
     estado compartido. Medido el 2026-08-18 — entre la verificación (10:15:32) y el push (~10:16)
     otra sesión fusionó su rama en ese mismo `main`, y el push se llevó dos commits ajenos que
     ninguna verificación había tocado. `HEAD:main` publica el SHA que mediste y nada más.
   Si lo rechazan porque alguien publicó entre tu `fetch` y tu `push`, repetir 3–5. **El rechazo es
   el guardarraíl funcionando**: no es motivo para parar ni para preguntar, es parte del cierre.
7. **Confirmar que quedó publicado**: tras `git fetch origin`, `git rev-parse origin/main` debe
   coincidir con el SHA que anotaste en el paso 5. Si trabajaste en el worktree principal,
   `git status -sb` sin `ahead` ni `behind` dice lo mismo.
8. **Anotar el cierre** donde corresponda (ledger del plan, `memoria/`, el `goal.md` del frente).

Solo entonces puede empezar el frente siguiente.

**Lo que este gate NO autoriza:** el deploy a producción sigue siendo otra cosa y necesita su propia
autorización explícita, siempre. Publicar en `main` y llevarlo a la obra no son lo mismo.

Fuera del cierre de un frente, no hagas commit ni push salvo petición explícita.
- Sigue `docs/siteground-deploy-routine.md`: pruebas antes que producción, respaldo previo, `pull --ff-only`, Composer ejecutado con PHP 8.3 y smoke funcional del flujo afectado. Una publicación aprobada no autoriza limpiar drift del servidor ni desplegar otros cambios.
