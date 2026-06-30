# Plan: Create AGENTS.md for Last Planner AIA

## TL;DR (For humans)

Create a compact `AGENTS.md` instruction file that helps future OpenCode sessions avoid mistakes and ramp up quickly. The file emphasizes the new **global tables architecture** for all projects, users, passwords, and permissions. Every line answers: "Would an agent likely miss this without help?"

## Goal

Write `/Users/juanfelipebenitezramos/.local/share/opencode/worktree/43673c394934be7d7e70fb5142c20ad2f0922f9b/curious-orchid/AGENTS.md` with high-signal, repo-specific guidance.

## Key Facts Discovered

### Architecture
- **Hybrid architecture** with Strangler Pattern: Legacy PHP procedural → Modern MVC
- **Docker-only** environment (no MAMP/XAMPP)
- **PHP 8.3 + MySQL 8.0** stack
- **USE_GLOBAL_TABLES=true** flag in `.env` controls migration state

### Global Tables (Critical Focus Area)
- **TableResolver** (`src/Core/TableResolver.php`) resolves table names
- **20 valid table types** for LPS modules
- **Database Singleton** (`src/Core/Database.php`) with project context injection
- Patches in `database/patches/global/` for global schema migration

### RBAC & Permissions
- **10 canonical roles**: A, D, R, DCV, OT, G, S, SG, C, V
- **RbacService** (`src/Security/RbacService.php`) for authorization
- **Permission strings**: `lps.programacion_semanal.editar`, etc.
- **Legacy aliases**: P→D, U→V auto-mapped

### Test Credentials
- Admin: `jbenitez` / `<ADMIN_TEST_PASSWORD>`
- All test users: `test.{ROLE}` / `<TEST_USER_PASSWORD>`

### Docker Services
- App: `:8081`, Adminer: `:8082`, MySQL: `:3307`

### Deployment
- Production: SiteGround (`prueba-lps.lastplanneraia.com`)
- Deploy: `git pull --ff-only origin main`

## Implementation Steps

### Step 1: Create AGENTS.md

Write the following content to `/Users/juanfelipebenitezramos/.local/share/opencode/worktree/43673c394934be7d7e70fb5142c20ad2f0922f9b/curious-orchid/AGENTS.md`:

```markdown
# AGENTS.md — Last Planner AIA

## Project Identity

Construction management platform implementing Last Planner System (LPS) methodology. PHP 8.3 + MySQL 8.0, Docker-only, no commercial frameworks.

## Architecture: Global Tables (Current State)

**Critical**: The system is migrating from per-project prefixed tables (`{prefix}_programa`) to global shared tables with `project_id`. The flag `USE_GLOBAL_TABLES=true` in `.env` controls this.

### Table Resolution

```php
// src/Core/TableResolver.php
TableResolver::resolve($projectId, 'programa')  // Returns 'programa' (global) or 'prefix_programa' (legacy)
```

**20 valid table types**: actividades, auto_contrato_log, auto_program_log, cambios, cic, cip, indicadores_generales, lps_drawer_comentarios, lps_escalamientos, pdc, papelera_pdc, pg_tracking, pi_shared_constraints, pi_shared_constraint_links, profesionales, programa, programa_consolidado, programacion_semanal, semanas_activas, subcontratistas.

### Database Connection

```php
// src/Core/Database.php - Singleton with project context
$db = Database::getInstance();
$db->setProjectContext($projectId);  // Auto-injects project_id into queries
```

**Always use `Database::getInstance()`** — never create new PDO connections.

## RBAC & Permissions

### Canonical Roles (10 total)

| Code | Profile | Key Permissions |
|------|---------|-----------------|
| A | System Admin | Full access (superuser) |
| D | Director | Full project read/write |
| R | Resident | Write: PS, PI, restrictions |
| DCV | Professional DCV | Write: PS, PI, restrictions |
| OT | Technical Office | Read: all, Write: PDC, purchases |
| G | Environmental | Read: PG/PI/PS |
| S | SST | Read: PG/PI/PS |
| SG | Social-Environmental | Read: PG/PI/PS |
| C | Subcontractor | Notifications only (no in-app actions) |
| V | Visitor | Read-only |

### Permission Checks

```php
// src/Security/RbacService.php
authorizePermission('lps.programacion_semanal.editar')  // Throws 403 if denied
```

**Legacy aliases**: `P` → `D`, `U` → `V` (auto-mapped in RbacService).

### Key Permission Strings

- `lps.programacion_semanal.editar` — Edit weekly programming
- `lps.programacion_intermedia.editar` — Edit intermediate programming
- `lps.programa_general.editar` — Edit master program
- `lps.pdc.auto_generar` — Auto-generate procurement plan
- `admin.usuarios.editar` — Edit users in admin panel

## Docker Commands

```bash
# Start stack
docker compose up -d --build db app adminer

# Run PHP CLI inside container
docker compose exec app php [script]

# Install Composer dependencies
docker compose exec app composer install

# Access DB directly
mysql -h 127.0.0.1 -P 3307 -u root -p lastplanneraia_dev
```

**Services**: App (`:8081`), Adminer (`:8082`), MySQL (`:3307`)

## Test Credentials

| Role | Username | Password |
|------|----------|----------|
| Admin System | jbenitez | `<ADMIN_TEST_PASSWORD>` |
| All test users | test.{ROLE} | `<TEST_USER_PASSWORD>` |

Roles: `A`, `D`, `R`, `S`, `G`, `SG`, `OT`, `DCV`, `V`, `C`

## Global Patches

Located in `database/patches/global/`. Apply after `001_create_global_tables.sql`:

```sql
SOURCE database/patches/global/20260525_lps_drawers_construccion_global.sql
SOURCE database/patches/global/20260528_add_fecha_ultimo_saneo_global.sql
-- ... etc (see database/patches/global/README.md for full mapping)
```

**Pattern**: Original patches target `{prefix}_table`, global equivalents target `table` directly with `IF NOT EXISTS` guards.

## Deployment

- **Production**: SiteGround (`prueba-lps.lastplanneraia.com`)
- **Deploy**: `git pull --ff-only origin main` on server
- **Backup before deploy**: `tar -czf ~/backups/predeploy-$(date +%Y%m%d-%H%M%S).tar.gz`
- **Rollback**: Restore from backup tarball

## Agent Rules

1. **Mobile First** — Design/test mobile before desktop
2. **Atomic edits** — Max 20 lines per code block
3. **Use Database singleton** — Never raw PDO connections
4. **RBAC mandatory** — All endpoints must check permissions
5. **No git push without approval**
6. **No browser agent** — Manual or server-side testing only
7. **Kill Switch** — 5 validation error attempts max before abort
8. **Protocol Sniper** — No "courtesy" refactoring beyond approved plan

## Key File Locations

- **Front Controller**: `public/index.php`
- **Router**: `src/Core/Router.php`
- **Database**: `src/Core/Database.php`
- **Table Resolver**: `src/Core/TableResolver.php`
- **RBAC Service**: `src/Security/RbacService.php`
- **Global Patches**: `database/patches/global/`
- **Migration Plan**: `docs/plan-migracion-shared-schema-sin-reporteria.md`
```

## Verification

After creating the file:
1. Confirm file exists at `/Users/juanfelipebenitezramos/.local/share/opencode/worktree/43673c394934be7d7e70fb5142c20ad2f0922f9b/curious-orchid/AGENTS.md`
2. Verify content matches the specification above
3. Check that all high-signal facts are included (global tables, RBAC, Docker, credentials)
4. Ensure no generic advice or fluff is present

## Success Criteria

- File is compact (under 150 lines)
- Every line answers "Would an agent likely miss this without help?"
- Global tables architecture is clearly explained
- RBAC roles and permissions are documented
- Docker commands and test credentials are provided
- No speculative claims or unverifiable content
