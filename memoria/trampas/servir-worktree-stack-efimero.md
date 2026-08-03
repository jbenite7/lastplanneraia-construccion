---
tipo: trampa
estado: vigente
fecha: 2026-07-30
areas: [docker, worktrees]
fuente: memoria-claude
origen: lps-aia-servir-worktree-stack-efimero
resumen: Para correr e2e sobre un worktree hay que darle identidad compose (no docker run), y el contenedor necesita el bloque environment o el PHP de CLI no ve la BD
---
Para servir un worktree sin stack propio (2026-07-30, `pdc-ola2-ayuda-in-app` en 8083): un
`docker run` suelto **no basta**. `tests/browser/support/pdc-sandbox.mjs` salta todos los specs del
PDC v2 si `docker compose port app 80` desde el cwd no coincide con `E2E_BASE_URL`, y siembra el
sandbox con `docker compose exec`. Solución: un compose efímero (imagen `last-planner-aia-app`, red
externa `last-planner-aia_default`, worktree en `/var/www/html`, `vendor` del principal montado `:ro`,
symlink a su `node_modules`) apuntado desde el `.env` del worktree con `COMPOSE_PROJECT_NAME` y
`COMPOSE_FILE`. Así `docker compose port/exec` funcionan y el guard queda satisfecho de verdad.

**Trampa medida:** el `.env` copiado hace funcionar la web pero **no** el PHP de CLI —
`Database::getInstance()` fuera de `index.php` no carga Dotenv y muere con
«SQLSTATE[HY000] [2002] No such file or directory» (DB_HOST vacío → socket unix). Hay que replicar el
bloque `environment:` de `docker-compose.yml` (DB_HOST=db, DB_PORT=3306, DB_NAME/USER/PASS,
USE_GLOBAL_TABLES). Borrar el `.env` copiado al terminar: lleva credenciales.

Ver [[dos-stacks-docker]] y [[aislar-stack-docker-por-worktree]].

> Esta página deroga lo que `browser-qa-pitfalls` recomendaba antes: servir un worktree con un
> `docker run` suelto. El toolchain asume compose, así que aquel atajo apunta al stack de la sesión
> vecina. Ver también [[aislar-stack-docker-por-worktree]].
