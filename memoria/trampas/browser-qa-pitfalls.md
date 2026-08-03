---
tipo: trampa
estado: vigente
fecha: 2026-07-29
areas: [qa, docker]
fuente: memoria-claude
origen: lps-aia-browser-qa-pitfalls
resumen: QA en navegador de lps-aia — la sesión PHP se cae ~60-90s tras login y la vista semanal auto-dispara save/auto-program al cargar
---
Durante QA en navegador contra `http://localhost:8081` (2026-07-22) se observó dos veces por sesión:

1. **La sesión "muere" ~60-90s después del login SOLO en el panel de navegador de Claude (Electron)** — diagnóstico 2026-07-22: el servidor está exonerado (sesión curl viva 8 min con polling y 3 min idle; los archivos de sesión "muertos" seguían en `/tmp` con `usuario` intacto; no hay enforcement de sesión única ni reaper). La cookie `PHPSESSID` (lifetime=0, en memoria) desaparece del jar del panel durante huecos de ~60-90s entre turnos del agente, con la página aún viva: el siguiente request llega sin sid válido → `use_strict_mode` emite sid nuevo vacío → 401 `missing_session` → `SessionTimeoutManager.js` convierte el 401 en `GET /logout?timeout=1` (los hits a /logout son consecuencia, no causa). El panel es compartido entre sesiones de Claude y a veces retiene cookies >20 min — el fallo es del entorno del panel, no de la app. Mitigación QA: re-login al inicio de cada turno de navegador, o validar flujos de sesión con curl/Playwright (nunca mostraron el fallo).
2. **Abrir `/programacion-semanal` dispara mutaciones automáticas**: `POST /api/semanal/save` + `POST /api/semanal/auto-program` en cada carga de página, sin interacción. Para QA de solo lectura del shell/drawer conviene usar `/dashboard/escalamientos` (incluye el mismo drawer LPS vía `views/partials/drawer_unificado.php`, sin autosave).

3. **Servir un worktree con la imagen docker existente** (validado 2026-07-22): `docker run -d --name lps-aia-worktree --network last-planner-aia_default -p 8083:80 -e DB_HOST=db -e DB_NAME=lastplanneraia_dev -e DB_USER=root -e 'DB_PASS=<ver .env>' -e USE_GLOBAL_TABLES=true -v "<worktree>:/var/www/html" -v "<checkout principal>/vendor:/var/www/html/vendor:ro" last-planner-aia-app`. Copiar `.env` del principal al worktree **ajustando `APP_URL` al puerto nuevo** (si apunta a otro puerto, los redirects post-login rebotan y parece sesión caída). Credenciales E2E: `test.A`/`aia2026`, proyecto «Da Porto» (project_id 73).
4. **La bitácora del drawer LPS no se puede sembrar con `test.A`**: general_usuarios id 366 no tiene fila en `profesionales` para project 73 y el INSERT de `/api/lps/comments/add` falla por FK. Para QA visual, interceptar `**/api/lps/comments?*` con `page.route()` de Playwright y una fixture `{respuesta:'OK', data:[...]}`.
5. **Playwright desde un worktree**: vive solo en el node_modules del checkout principal; importarlo con URL absoluta `file:///Volumes/Crucial%20X6/Developer/lps-aia/node_modules/playwright/index.mjs` (la ruta tiene espacio).
6. **Reset legacy pisa adaptadores**: `styles.css` entra como `layer(module)` y su `* {margin/padding:0}` (capa `module.reset`) gana a `@layer components`; el spacing de adaptadores del design system debe ir en `@layer legacy-overrides` (patrón de semi-auto-review.css y bi-figure.css).
7. **El gate visual (maxDiffPixelRatio 0.03) puede pasar en verde con un rediseño real**: el rediseño completo de la toolbar/leyenda del PG (2026-07-22) midió solo 2,6% de píxeles distintos (el fondo oscuro uniforme domina) — un golden obsoleto no siempre falla. Tras un cambio visual intencional, regenerar goldens deliberadamente con `--update-snapshots=all` (el default `changed` NO reescribe si el diff cae dentro de tolerancia) y actualizar los `sha256` del manifiesto.
8. **`pdc-legend-item` es una clase compartida trampa** (revisado el 2026-08-03): la regla
   `html body … {width: 205px !important}` que citaba la línea 6476 de `styles.css` **ya no
   existe** — el archivo tiene hoy 4380 líneas y `205px` no aparece en él. Tras la tokenización,
   `.pdc-legend-item` se define en `styles.css:532-536` con tokens de estado del design system y
   sin `!important` de ancho. Lo que sigue vigente es el fondo del asunto: la clase la comparten
   PG, PI y PS, y `buttons.css` la llena de `!important` en capa `components`, invencibles desde
   CSS de módulo. Para adoptar el design system en una leyenda, desacopla con una clase propia del
   módulo (patrón `pg-filter-chip`) en vez de pelear la cascada.

9. **La captura de fallo de Playwright miente cuando el spec tiene `finally { logout }`** (2026-07-29): los specs del PDC v2 envuelven el cuerpo en `try/finally` con `logout(page)`, y la captura `only-on-failure` se toma **después** de ese teardown → cualquier fallo, sea el que sea, se «ve» como la pantalla de login y parece caída de sesión (punto 1). Costó un diagnóstico entero. Ir al log del contenedor (`docker compose logs app`) y mirar el código/tamaño de la respuesta, no a la imagen: un `POST …/preview 200 693` con un cuerpo sospechosamente pequeño fue lo que delató el bug real.

10. **Un stack de compose propio por worktree es preferible al `docker run` del punto 3** (2026-07-29): el toolchain (`docker compose exec app php tests/…`, phpstan, y el guardarraíl del sandbox e2e en `tests/browser/support/pdc-sandbox.mjs`, que siembra por `docker compose exec` y compara contra `docker compose port app 80`) asume compose. Con `docker run` esos comandos apuntan al stack **de la sesión vecina**. Receta: en el `.env` del worktree, `COMPOSE_PROJECT_NAME` propio y `COMPOSE_FILE` con un override extra que ponga `ports: !override` y un volumen de datos propio (`docker-compose.yml` fija `name:` y monta el volumen externo compartido `htdocs_db_data`; sin el override, dos mysqld escriben el mismo datadir). **`COMPOSE_FILE` hay que entrecomillarlo**: la ruta del repo tiene un espacio y `public/index.php` lee el mismo `.env` con phpdotenv, que revienta con «unexpected whitespace» y deja la web en 500 mientras los tests PHP siguen verdes (toman la config del entorno del contenedor).

**Why:** todo esto rompe o contamina validaciones en navegador y cuesta varios ciclos de re-diagnóstico.

**How to apply:** en QA de navegador, planear verificaciones cortas y agrupadas tras cada login; preferir escalamientos o programación-intermedia para el drawer LPS; no interpretar el bounce a `/login` como bug de la superficie auditada. Relacionado: [[branch-preexisting-red-gates]].
