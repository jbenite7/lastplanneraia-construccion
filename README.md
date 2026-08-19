---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-08-19
areas: [proceso, arquitectura]
tags: [leer-antes-de-tocar]
fuente: README.md
resumen: Puerta de entrada al repo — qué es Last Planner AIA, con qué stack corre, cómo se levanta y dónde está cada cosa.
---

# Last Planner AIA

Plataforma web para operar **Last Planner System (LPS)**: planificación jalada (Programa General →
Programación Intermedia/Lookahead → Programación Semanal) con RBAC, medición PAC/CNC y un módulo de
Plan de Compras (PDC v2) integrado. Reemplaza planificación empujada por compromisos que el terreno
puede sostener.

## Metodología en una línea por fase

- **Programa General:** lo que se DEBERÍA hacer — plan maestro, hitos, cantidades/costos teóricos.
- **Programación Intermedia (Lookahead, 4-6 semanas):** lo que se PUEDE hacer — detecta y libera
  restricciones (diseño, materiales, mano de obra, equipos, trámites).
- **Programación Semanal:** lo que se HARÁ — compromisos reales medibles.
- **PAC** (Porcentaje de Asignaciones Completadas, binario por compromiso) y **CNC** (Causas de No
  Cumplimiento) cierran el ciclo de mejora cada semana.

## Stack

- **Backend:** PHP 8.3, MVC propio sin framework (`src/Core/Router.php`, Front Controller en
  `public/index.php`). Persistencia MySQL 8 / MariaDB, **global-only**: tablas compartidas con
  `project_id`; prohibido agregar consultas runtime nuevas a tablas `{prefix}_*` (son solo metadato
  histórico/migración).
- **Libs clave:** `phpoffice/phpspreadsheet` (reportería), `vlucas/phpdotenv`, `phpmailer/phpmailer`
  (recuperación de contraseña vía MTA local del hosting, no relay externo).
- **Frontend:** Handsontable para grillas LPS, JS ES5/módulos en `public/js/modules/`, design system
  propio en `public/css/design-system/` con tokens `--ds-*`/`--aia-*` (ver [[DESIGN]]).
- **PDC v2:** isla React en `pdc-app/` + `src/Services/Pdc/`, documentada en `docs/pdc-v2.md`. El
  PDC v1 (Listado de Actividades, Contratos, `/pdc`) se eliminó el 2026-08-04; no reintroducir.
- **QA:** PHPUnit + suite propia (`scripts/run-php-tests.php`, niveles `puro`/`http`), PHPStan
  (`phpstan.neon`, `phpstan-pdc.neon`), Playwright E2E (`tests/browser/`), Biome para JS/CSS del
  design system.
- **Runtime:** exclusivamente Docker Compose — nunca MAMP/XAMPP ni PHP del host. Servicios
  declarados: `app`, `db`, `adminer`. App en `http://localhost:8081`, Adminer en
  `http://localhost:8082`, MySQL host en puerto `3307`. El compose monta desde
  `${LPS_CODE_ROOT:-<checkout principal>}` — desde un worktree hay que exportar `LPS_CODE_ROOT`
  propio (ver `docker-compose.override.yml`).

## Cómo correr

```bash
# 1. .env: NO hay .env.example — se copia de un .env existente. Claves en GEMINI.md §Base de Datos
#    y README §3.1 (correo). En un worktree se enlaza el de la raíz, no se copia.
# 2. Levantar el stack
docker compose up -d --build db app adminer

# 3. Correr PHP/tests dentro del contenedor, nunca en el host
docker compose exec app php scripts/run-php-tests.php --nivel=puro
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1
```

**La sesión local se abre por la puerta de servicio, nunca tecleando credenciales en `/login`:**
`http://localhost:8081/dev/entrar?u=test.R&p=<Proyecto_Proceso>` (`test.A` Admin, `test.R`
Residente, `test.V` Visualizador; sin `p` aterriza en `/proyectos`). Necesita `DEV_DOOR=1` y
`DEV_DOOR_USERS` en `.env`. No existe en producción — ver `src/Core/DevDoor.php`.

Recuperación de contraseña necesita `APP_URL`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`,
`MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` y el patch
`database/patches/20260329_create_password_reset_tokens.sql`.

## Gobernanza y coordinación multi-sesión

El desarrollo lo llevan agentes IA en sesiones paralelas sobre worktrees
(`.claude/worktrees/`), coordinadas por las reglas de [[docs/coordinacion-sesiones]]: una sesión
**coordinadora** (audita, autoriza publicaciones, único punto de contacto con el usuario) y
sesiones **de ejecución** (un frente cada una, worktree propio). Publicación a `main` pasa siempre
por `scripts/publicar.sh` (gate de invariante de montaje). Deploy a producción exige autorización
explícita y aparte, siempre — nunca la da un objetivo de sesión por sí solo.

## Mapa

- [[ROADMAP]] — rumbo, fases entregadas y en curso.
- [[TASKS]] — pendientes vivos, incluida la coordinación entre sesiones.
- [[CHANGELOG]] — historial de cambios (Keep a Changelog).
- [[IMPLEMENTATION_PLAN_INVENTORY]] — índice de planes/specs de `docs/superpowers/`.
- [[AGENTS]] / `GEMINI.md` — constitución operativa de los agentes IA (reglas, credenciales, runtime).
- [[DESIGN]] — contrato de consumo del design system; léelo antes de tocar UI.
- [[docs/coordinacion-sesiones]] — cómo se reparten frentes entre sesiones paralelas.
- [[docs/decisiones-pendientes]] — cola de decisiones que esperan criterio del usuario.
- `docs/pdc-v2.md` — Plan de Compras v2 (sucesor del PDC v1, eliminado).
- `docs/global-tables-architecture.md` — regla vigente de BD global con `project_id`.
- `memoria/` — wiki LLM de segunda capa (decisiones, trampas ya pisadas); ver `docs/wiki-operacion.md`
  para su esquema. No confundir con estos 5 archivos raíz, que son la wiki de primer nivel.
- `GLOSARIO.md` — diccionario de términos LPS.
