---
capa: fuente
tipo: plan
areas: [lps, design-system, qa, proceso]
fuente: docs/superpowers/plans/2026-09-26-s05-deuda-cierre.md
resumen: Informe de ejecución del sprint S05-DEUDA que cerró las cinco deudas de la ronda 1.2 y registró sus aprendizajes
fecha: 2026-09-27
sprint: S05-DEUDA
ejecutor: codex
rama: codex/s05-deuda-cierre
estado: abierto
---

# Informe de sprint S05-DEUDA

## Partida

- Felipe aprobó la spec v1.1 y el plan el 2026-09-26; este sprint se ejecutó el 2026-09-27.
- Worktree: .claude/worktrees/s05-deuda-cierre, rama codex/s05-deuda-cierre, creada desde origin/docs/s05-deuda-cierre en 300c75315167389719726284849a99e696573b5e. El checkout principal no se modificó.
- .env se enlazó desde el checkout raíz, no se copió. Para Playwright se sirvió temporalmente el worktree con un montaje de solo lectura del .env. Al cerrar las pruebas, se comprobó que app volvió a montar /Volumes/Crucial X6/Developer/lps-aia y respondió en localhost:8081; se retiró el overlay temporal.
- Línea de partida sobre 300c753: typecheck RC 0; Vitest 73 archivos y 942 pruebas, RC 0; npm run test:wiki RC 1 con ocho áreas TNP fuera de la lista cerrada y la alarma VERACIDAD en 47 commits desde el pase del 2026-09-21 (umbral 40). T4 corrigió las ocho áreas y T5 cerró la alarma.
- No se tocaron archivos de configuración bajo ~/.claude o ~/.codex.

## Implementación por tarea

| Tarea | Resultado | Commit |
| --- | --- | --- |
| 1 · Tinte crítico oscuro | La prueba fija rgb(67, 20, 20) para el token oscuro y conserva la comparación con el valor computado. La mutación temporal del token fuente y su espejo servido hizo fallar la aserción nueva; ambos archivos se restauraron. La prueba verde quedó en 2/2. | 52fc5498 |
| 2 · Íconos inactivos | React y legado midieron 1.12:1 mínimo en tema claro. La paridad se cumple; no se agregó CSS. El contraste inferior a 3:1 es una condición heredada que queda para un frente de sistema de diseño. | 7fd9c117 |
| 3 · Inicio relativo y CSV | La tabla usa «Inicio rel.» y muestra «Hace N sem», «Esta sem» o «En N sem», con fecha exacta en el título. El CSV conserva el desfase numérico y el cero. El layout de 13 columnas pasó sin overflow en ambos temas y estados del riel; se verificó que no hay golden vigente para ese modo y no se regeneró ninguna referencia. | 20135885 |
| 4 · Áreas TNP | La spec y el plan TNP declaran el área válida lps; solo cambió frontmatter. | a3c30d4a |
| 5 · Veracidad | Revisión manual de 31 páginas en lps, design-system, arquitectura y qa: 15 corregidas, 0 derogadas. Se actualizaron referencias de rutas, temas, tabla PG, TNP, autosave, assets, D-CI-1 y conteos de QA. | a016350d |
| 6 · Ingest | Se registraron con fuentes y enlaces las cuatro lecciones de gates, goldens Linux, semántica de la medición de interacción y transcripciones de sesiones Codex compartidas. | e42690f8 |

## Verificación de la tarea 7

### Suite local

| Comando | Resultado |
| --- | --- |
| npm --prefix frontend run typecheck | RC 0 |
| npm --prefix frontend run test | RC 0 · 75 archivos, 949 pruebas |
| npm run test:design-system:static | RC 0 · 8 de 8 gates |
| npm run test:wiki | RC 0 · 98 pruebas; 174 páginas, 608/611 fuentes declaradas. Corrida final tras escribir el informe. |
| Playwright indicado por el plan, E2E_THEME=light | RC 0 · 24/24 |
| Playwright indicado por el plan, E2E_THEME=dark | RC 0 · 24/24 |
| npm run css:minify:check | RC 0 |

Los dos recorridos Playwright verificaron la tabla de 8 y 13 columnas en ambos temas y ambos estados del riel. La aserción visual fija no modificó ningún golden.

### CI aislado local, orden y procedencia del workflow

Antes de levantar cada pata se ejecutó design-system-ci-preflight.mjs --print-provenance y luego el preflight real, antes de compilar las imágenes. Ambas patas usaron COMPOSE_FILE=docker-compose.yml:docker-compose.ci.yml, nombres de proyecto y volúmenes independientes, E2E_THEME correspondiente, E2E_BASE_URL=http://127.0.0.1:18081 y E2E_REQUIRE_ISOLATED_DB=1. CI_GIT_SHA fue e42690f84fcf6362332c19e99a1eb8526de89ba1; la huella fue aa9913ba03962ce27dd721b465f2bcf5047db37915639a1f50cee2989938dfba; la huella del fixture fue 0a3617dd17ea739af5025330627d6c73ed5efae260218626400d0a5159789fe8.

| Comando o gate | Claro | Oscuro | Notas |
| --- | --- | --- | --- |
| Captura de procedencia y preflight | RC 0 | RC 0 | La huella coincidió con el checkout limpio. |
| css:minify:check | RC 0 | — | Es el paso previo de CSS del workflow. |
| Compose build app db | RC 0 | RC 0 | Imagen de aplicación y fixture CI aislado. |
| Compose up db app y espera de /login | RC 0 | RC 0 | Runtime local en el puerto aislado 18081. |
| G_LABORATORY_GATES | RC 0 | RC 0 | En cada tema: 31 runtime, 1 a11y, 20 visual y 1 performance. |
| G_PILOT_LAB_GATES | RC 0 | RC 0 | Claro: sin escenarios visuales declarados, 2 a11y y 1 hue; oscuro: 2 visual, 2 a11y y 1 hue. |
| G_PG_PERSISTENCE_RBAC | RC 0 | RC 0 | En cada tema: 2 aprobadas, 2 omitidas por la selección construction. |
| Compose down --volumes | RC 0 | RC 0 | Se eliminaron los dos volúmenes efímeros al terminar cada pata. |

Las pruebas de persistencia tocaron solo las bases efímeras del workflow CI; sus volúmenes quedaron eliminados. No se cambió esquema, migraciones ni RLS. No se editaron datos de producción ni se ejecutaron mutaciones manuales contra la base compartida de desarrollo.

### Revisión, PR y condición de hecho

La revisión independiente de contexto limpio no encontró hallazgos accionables de código, regresión, seguridad ni cumplimiento frente a la spec v1.1 y el plan. No quedaron correcciones pendientes. No se regeneraron snapshots ni baselines.

La condición quedó declarada en el cuerpo del PR antes del primer CI: las 13 variables de estado G_* del paso «Summarize gate results» deben ser `success` tanto para light como para dark. La tabla se leyó directamente en el resumen de la corrida, no desde el color del job.

PR #63: https://github.com/jbenite7/lastplanneraia-construccion/pull/63. Corrida `36298043036`, verificada sobre `3aeb68c0256f03bdf7364db984fec40dba32af1c`: duración total 15m20s; `design-system-static` y ambas patas runtime terminaron en éxito.

| Variable G_* | Light | Dark |
| --- | --- | --- |
| G_PHPSTAN_BASELINE | success | success |
| G_PHPSTAN_PDC | success | success |
| G_RUNTIME_GRANTS | success | success |
| G_PHP_SUITE | success | success |
| G_PHP_ADMIN_DB | success | success |
| G_FULL_APP_FLOW | success | success |
| G_SEMANAL_ROLES_PHASES | success | success |
| G_RUNTIME_BUDGET_MEASURE | success | success |
| G_RUNTIME_BUDGET_CHECK | success | success |
| G_LABORATORY_GATES | success | success |
| G_PILOT_LAB_GATES | success | success |
| G_KEYBOARD_REFLOW_EVIDENCE | success | success |
| G_PG_PERSISTENCE_RBAC | success | success |

La actualización de este informe dispara una segunda corrida sobre el SHA final; sus resultados se comprobarán antes del cierre.

## BLOCKED

Ninguna tarea quedó bloqueada.

## Costo

El costo real de esta sesión no está disponible en el arnés; no se estima.
