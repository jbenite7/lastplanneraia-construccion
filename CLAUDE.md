# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

@AGENTS.md is the authoritative contract for this repo (permissions, RBAC, global-tables architecture,
design-system scope, verification routing) — read it in full. It is **versioned since 2026-07-30**
(it used to be in `.gitignore`), so a fresh clone has it. `GEMINI.md` and `README.md` carry the same
rules restated for other assistants/humans; where they overlap, `AGENTS.md` wins. Everything below is
additional orientation that isn't already in those files: commands and where things actually live in
the code.

## Herramientas externas (ECC) — qué aplica aquí y qué no

El entorno global tiene ECC instalado (perfil `developer`, ~/.claude). Buena parte de su catálogo
asume stacks que este repo **no** usa, así que:

- **Esto no es Laravel.** No hay `artisan`, ni Eloquent, ni service container: el routing es FastRoute
  y el acceso a datos es `src/Core/Database.php`, con autoload PSR-4 `App\ -> src/`. Las skills
  `laravel-patterns`, `laravel-tdd`, `laravel-verification` y `laravel-plugin-discovery` **no aplican**;
  no las invoques ni razones desde sus supuestos.
- **No hay PHPUnit ni Pest** (ver `### Tests`). No propongas migrar los `tests/test_*.php` a un runner
  de terceros como parte de otra tarea — eso es un cambio con plan y gate propios.
- **De ECC sí sirve aquí:** las reglas PHP en `~/.claude/rules/ecc/php/` (léelas bajo demanda), los
  agentes `php-reviewer`, `security-reviewer` y `silent-failure-hunter`, y el comando `/security-scan`.
- **El proceso lo manda Superpowers**, no ECC: planificar es `writing-plans` + gate, revisar es
  `requesting-code-review`. No uses `/plan` ni `/code-review` de ECC, que duplican ese flujo.

## Memoria del proyecto (wiki en `memoria/`)

**Empieza por `memoria/index.md`.** Es la wiki del proyecto: el porqué de las decisiones, las
trampas que ya costaron tiempo, un mapa por área y el estado real de los goals. Sigue el patrón
[LLM Wiki](https://gist.github.com/karpathy/442a6bf555914893e9891c11519de94f), en tres capas:

| Capa | Dónde | Regla |
|---|---|---|
| Fuentes | `docs/`, `goals/`, los `.md` de la raíz, el código | Se leen. **Su contenido no se edita desde la wiki.** |
| Wiki | `memoria/` | La escribe el asistente. **Nunca se edita a mano.** |
| Esquema | `docs/wiki-operacion.md` | Explica la estructura y las operaciones. Esta sección lo resume. |

Excepción decidida el 2026-08-02: cada `goals/<slug>/goal.md` termina con una sección «Archivos de
este goal» que enlaza a sus hermanos versionados y a `memoria/goals/estado.md`. Es navegación
añadida al pie, no contenido modificado; sin ella los 99 archivos de `goals/` quedan como islas en
el grafo. Al crear un goal nuevo, añade esa sección. `docs/` no se toca.

Precedencia ante conflictos: **código > `AGENTS.md` > `memoria/`**. Nada de lo que hay en la wiki
es contrato. Si una nota contradice al repo, gana el repo: corrígela y márcala `estado: derogada`
en vez de borrarla.

El vault de Obsidian es la **raíz del repo**, no `memoria/`; por eso los wikilinks alcanzan a
`docs/`, `goals/` y a los `.md` de la raíz sin copiarlos. La configuración compartida está en
`.obsidian/` (versionada salvo el estado personal de la ventana).

**Cuatro operaciones**, cada una con su línea en `memoria/log.md`: `ingest` (escribir lo aprendido
al cerrar una tarea), `query` (responder citando páginas), `lint` (`npm run test:wiki` — comprueba
la **forma**, nunca corrige, y **no comprueba la verdad**) y `veracidad` (verificar contra el código
que lo escrito sigue siendo cierto, por rotación de áreas, verificando cada afirmación en vez de
sospecharla). El lint cuenta los commits de código desde el último pase de `veracidad` y sale en
rojo por encima de 40, así que la alarma llega sola.

**El procedimiento completo —operaciones, frontmatter, las trece áreas, los scripts y el umbral—
está en `docs/wiki-operacion.md`.** Léelo antes de escribir en la wiki.

**Antes de tocar un área, lee su mapa** en `memoria/mapas/`: dice qué documentos mandan y qué
trampas hay puestas.

## Runtime & commands

Everything runs in Docker Compose — never use MAMP/XAMPP/a host PHP. Services: `app` (PHP 8.3 +
Apache, `http://localhost:8081`), `db` (MySQL 8.0, host port `3307`), `adminer` (`http://localhost:8082`).

```bash
docker compose up -d --build db app adminer   # start stack
docker compose exec app composer install      # install PHP deps
docker compose exec app php -v                # sanity check
```

There is no `.env.example` — `.env` must be created from scratch or copied from an existing one;
see GEMINI.md §Base de Datos for required keys, and README.md §3.1 for the extra mail vars needed if
enabling password recovery.

**En un worktree nuevo, enlaza el `.env` de la raíz antes de usar Docker:**

```bash
ln -s "/Volumes/Crucial X6/Developer/lps-aia/.env" .env
```

`.env` está en `.gitignore`, así que los worktrees nacen sin él y `docker compose` resuelve
`${DB_NAME}` y `${DB_PASS}` a cadena vacía. **Enlace, no copia:** las copias se quedan viejas en
silencio en cuanto se edita el `.env` de la raíz (el 2026-08-18 había seis copias sueltas).

Esto **no es opcional**: `public/index.php` carga Dotenv, pero los `tests/test_*.php` no, así que el
PHP de línea de comandos toma las credenciales del bloque `environment:` de `docker-compose.yml` y
de ningún otro sitio. Sin el enlace, ese bloque se rellena con cadenas vacías y
`docker compose exec app php tests/test_global_table_safety.php` muere con
«Access denied for user ''», mientras la web sigue en verde porque ella sí lee el `.env`.

El código que sirve `localhost:8081` es **siempre el de la raíz del repo**, no el del worktree
(`docker-compose.override.yml` monta la raíz por ruta absoluta). Para ver tu rama en el navegador:
`LPS_CODE_ROOT="$(pwd)" docker compose up -d app`, y devuélvelo a la raíz al terminar.

### Authenticating locally — always use the dev door

**Never type credentials into `/login`, and never ask a human to log in for you.** To get an
authenticated session locally, always use the development door:

```
http://localhost:8081/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E
```

`u` is one of the seeded test accounts (`test.A` = Admin, `test.R` = Residente, `test.V` =
Visualizador; `test.C` and `test.D` exist too but are not enabled by default). `p` is the
`Proyecto_Proceso` name — omit it to land on `/proyectos` and pick manually. The role that ends up
in the session is the account's **real** role from `project_members`, so this is also how you cover
the "one allowed role, one denied role" requirement for RBAC changes.

Requires `DEV_DOOR=1` and `DEV_DOOR_USERS` in `.env` (untracked). If the URL redirects to `/login`
or 404s, the door is closed — check those two keys. Note that editing `APP_ENV` in `.env` does
**not** close it under Docker: `docker-compose.yml` injects `APP_ENV` as a container variable and
`Dotenv::createImmutable()` won't override it. Use `DEV_DOOR=0` instead.

Implementation and rationale: `src/Core/DevDoor.php`,
`docs/superpowers/specs/2026-07-30-dev-door-design.md`. Guard regressions are caught by
`tests/test_dev_door_guard.php`. This is a development-only path — it does not exist in production,
and it grants no permissions beyond the account's own.

### Tests

There is no PHPUnit. `tests/test_*.php` files are standalone self-executing scripts (no runner) — run
one directly:

```bash
docker compose exec app php tests/test_global_table_safety.php
docker compose exec app php tests/test_global_table_reconciliation.php
```

Node/JS tests use the built-in Node test runner or Playwright, driven from `package.json`:

```bash
npm run test:design-system:static     # node --test tests/design-system/*.test.mjs + contract/audit scripts
npm run test:design-system:phpstan    # phpstan baseline via scripts/design-system-phpstan-baseline.mjs
npm run test:design-system:runtime    # playwright: design-system-lab.mjs + a11y + visual + performance
npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1
```

`e2e/` is a **separate** Playwright suite (own `e2e/playwright.config.mjs`, own fixtures under
`e2e/support/`) covering smoke/admin/workflow tests — distinct from `tests/browser/`, which is
design-system/lab-focused. Don't conflate the two when picking where a new browser test belongs.

Static analysis: `phpstan` is a Composer dependency, and there **are** three neon files at the repo
root — `phpstan.neon` (level 5, paths `src` and `admin/src`, bootstraps
`scripts/phpstan/constantes-entrypoint.php`), `phpstan-baseline.neon` (the tolerated-error list) and
`phpstan-pdc.neon`. (This paragraph used to claim there was no `phpstan.neon`; corrected 2026-08-10
after `phpstan-baseline.neon` turned out to be what was putting two design-system gates in the red.)
There are **two** independent exception lists and they are easy to confuse: `phpstan-baseline.neon`
feeds PHPStan itself, while `docs/design-system/phpstan-baseline.json` feeds the design-system gate
through `scripts/design-system-phpstan-baseline.mjs`. A stale entry in either one fails a different
gate. For ad hoc runs use the command AGENTS.md gives: `docker compose exec app vendor/bin/phpstan
analyse src admin/src --memory-limit=1G`. `biome.json` only covers `public/js`, `public/css`,
`admin/public/css` (no PHP linting there):

```bash
npm run check:frontend        # biome check public/js public/css admin/public/css
npm run lint:frontend
npm run format:frontend
```

## Architecture

### Front controller & routing

`public/index.php` is a flat, plain-PHP front controller (no framework): loads Composer autoload,
applies `MaintenanceMode` and `SessionMiddleware::check()`, then dispatches through
`App\Core\Router` — a thin wrapper around **nikic/FastRoute** (`src/Core/Router.php`). All ~150+
routes are registered inline in `index.php` as one long list grouped by comment headers (Auth,
Programacion, Gestion, APIs, BI, Legacy). Some routes point to closures that `require_once` a
procedural script under `src/Legacy/` instead of a controller method — that's the "Legacy" lane
mentioned in AGENTS.md/GEMINI.md, not a bug.

### `src/` layout

- `Controllers/` — HTTP controllers, grouped by domain: `Api/`, `Auth/`, `Bi/`, `Core/`, `Gestion/`,
  `Integracion/`, `Internal/`, `Programacion/`, plus `BaseController.php`.
- `Services/` — business logic (`SemiAutoService`, `ActivityMatcherService`, `ReportProcessor`,
  `ControlTowerService`, …), with `Auth/`, `Bi/`, `Mail/` subfolders.
- `Core/` — framework primitives: `Router.php`, `Database.php` (singleton, PDO prepared statements
  only), `TableResolver.php`, `SessionMiddleware.php`, `MaintenanceMode.php`, `AppEnvironment.php`,
  `CommitmentLockGuard.php`, plus `Lps/` and `Notifications/`.
- `Security/` — `RbacManager.php`, `RbacCatalog.php`, `RbacService.php`, `CsrfTokenManager.php`,
  `EventService.php`, `LpsWeekEditPolicy.php`, `DesignSystemLabAccessPolicy.php`.
- `Legacy/` — procedural scripts + `Endpoints/`, still wired into the router; maintenance-only per
  AGENTS.md, no new features here.
- `Support/` — cross-cutting helpers (`ActivityMatcher`, `BiProjectScope`, `SemiAutoQualityGate`,
  `OperationalFamilyPolicy`, `ModuleRequestContext`).
- `View/Components/` — template helpers.

### RBAC

`RbacCatalog.php` defines the role codes (`A` Admin, `D` Director de Obra, `R` Residente de Obra,
`DCV`, `OT`, `G` Ambiental, `S` SST, `SG`, `C` Subcontratista, `V` Visualizador), a `roleAliases()`
map for legacy names, and permission constants (`PERM_AUTO_DEFINIR_CONTRATOS`, etc.). `RbacManager`
is intentionally simple: `getCapabilities(string $role)` returns a flat boolean map
(`canManageWeeks`, `canManageGeneralProgram`, `canManagePdC`, …) computed from hardcoded
`in_array($role, [...])` lists — there is no DB-backed permission table. `hasCapability($role, $cap)`
just reads that map. Always normalize an incoming role through
`App\Security\RbacService::normalizeRole()` before checking capabilities.

**Corrected 2026-08-10:** this used to say `Admin\Core\RoleManager::cleanCargo()`, and that is the
wrong function. `cleanCargo()` lowercases, strips accents and normalizes gendered job titles
(`admin/src/Core/RoleManager.php:67`) — it returns cleaned *text* like `"director obra"` for the
fuzzy matching inside `suggestRoleByCargo()`, never a role code. Feeding it where a code is expected
would break `$_SESSION['permiso']` and every `hasCapability()` call. `RbacService::normalizeRole()`
(`src/Security/RbacService.php:18`) is the one that maps aliases to a canonical code via
`RbacCatalog::roleAliases()`.

### `admin/` is a separate mini-app

It does **not** reuse `src/Core` or `src/Security`. It has its own front controller
(`admin/index.php`), its own `admin/src/Core/Router.php`, `RoleManager.php`, `Security.php`, its own
`admin/src/Models/` (User, Project, ProjectMember) and `admin/src/Controllers/`, its own
`admin/views/` and `admin/public/css/`. It shares the same Composer autoloader/vendor and the same
MySQL schema as the main app (that's what `tests/test_global_table_safety.php` cross-checks), but is
architecturally isolated — treat it as its own codebase when tracing a bug, not an extension of
`src/`.

### Data model

Global tables shared across projects, isolated by `project_id` on every operational query.
`{prefix}_*` tables are historical/compat only — don't write new runtime SQL against them. See
`docs/global-tables-architecture.md` before touching schema, migrations, or backfills (dry-run first;
any apply/delete needs a Plannotator gate, verifiable backup, and restore plan per AGENTS.md).

### Design system

`docs/design-system/README.md` describes it as the non-negotiable, contractual layer (tokens, dark
desktop-only scope, accessibility floor, glass-effect rules) — distinct from `docs/brand/` and Stitch
mockups, which are just visual inputs, not contracts. Design-system work defaults to dark, with `1180x820` as the
canonical validation viewport (see AGENTS.md's UI routing section). The `linen` theme was removed
from the product on 2026-07-25 (DS-030) and there is no theme switcher, so light-mode work means
rebuilding it.

### `goals/` workflow

Each `goals/<slug>/` directory holds `goal.md` (objective + links), `facts.md` /
`facts-result*.json` / `facts.meta.json` (accepted facts, with an iterative interview round history —
`interview.json`, `interview-result-round-2.json`, …), `plan.md`, and `validation-log.md`, often with
an `evidence/` subfolder. Read the relevant files under a named goal before acting on it; don't mix
goals.

## Reference docs

- `DESIGN.md` — contrato de consumo para desarrolladores/asistentes: qué tokens y primitivas `aia-*` usar y el flujo obligatorio antes de editar una superficie migrada. Léelo antes de cualquier cambio de UI. La autoridad ejecutable vive en `docs/design-system/`.
- `GLOSARIO.md` — LPS/Lean terminology, consult before naming anything domain-related.
- `memoria/arquitectura/` y `memoria/flujos/` — inventario de rutas por módulo, matriz de
  navegación y los dos flujos de negocio. Generado desde el código con
  `scripts/wiki-arquitectura.mjs`; sustituye al retirado `docs/ROUTES.md`.
- `docs/global-tables-architecture.md` — DB architecture rules.
- `docs/design-system/README.md` and contracts therein — design tokens/component rules.
- `docs/siteground-deploy-routine.md` — deploy checklist (only on explicit publish request).
- `docs/pdc-v2.md` — módulo **Plan de Compras (PDC) v2**: modelo de dominio (presupuesto → maestro de insumos → paquetes → plan con fechas), fases A1–A4, deudas de datos conocidas y trampas ya medidas. La SPA (React + Vite + AG Grid) vive en `pdc-app/` y publica su bundle en `public/pdc-app/`; el PHP, en `src/Services/Pdc/`. Léelo antes de tocar cualquier cosa del PDC.
