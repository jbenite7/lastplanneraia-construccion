---
capa: fuente
tipo: evidencia
estado: vigente
fecha: 2026-08-29
areas: [arquitectura, frontend, seguridad]
fuente: goals/paridad-shell-react-rls/facts.md
resumen: "Hechos de arranque medidos para el frente de paridad React y RLS."
---

# Facts — Paridad del shell React y RLS

1. El checkout activo es el worktree enlazado
   `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react`, rama
   `shell-minimo-react`.
2. El contenedor `app` monta ese worktree en `/var/www/html`, verificado el 2026-08-29 mediante el
   ID devuelto por `docker compose ps -q app`.
3. El baseline previo a RLS pasó `tests/test_global_table_safety.php` (RC 0),
   `tests/DatabaseWrapperTest.php` (32/32), PHPUnit `tests/unit` (84 tests, 208 assertions) y
   `npm run test:rbac-parity` (1/1).
4. El baseline de PHPUnit emite un log preexistente por la tabla opcional
   `pg_avance_edicion_manual`; la suite termina en RC 0.
5. RLS es dependencia dura del rollout React. La interfaz nunca introduce un bypass para avanzar.
6. La mutación de schema y grants de la base compartida conserva autorización explícita separada.
