# Sprint 04 — Programa General

## Baseline 2026-07-11

- Alcance exclusivo: `/programa-general`; Login, Projects, Actualizar PG y BI quedan fuera.
- Prioridad dirigida por el usuario aunque Sprint 03 Login siga abierto.
- Rama `main`: 40 commits adelante y 3 detrás de `origin/main` al iniciar.
- Worktree con cambios mezclados; solo se stagean rutas y hunks propios de PG.
- `public/index.php` contiene cuatro rutas BI ajenas y queda excluido del sprint.
- La vista PG ya tenía un cambio local de cache-busting; se clasifica por hunk antes del commit.
- Docker `app`, `db` y `adminer` están activos; MySQL reporta estado saludable.
- Auditor global: PASS contra baseline; PG conserva presupuesto cero.
- Runtime adoptable: cards bajo 700px, tabla desde 700px y switch dark/linen funcional.
- Baseline nativo: overflow de página 0 en 390x844, 1180x820 y 1440x900.
- Hallazgo confirmado: los filtros funcionaban, pero no exponían `aria-pressed`.
- TDD inicial: contrato rojo por ausencia de estado accesible y verde tras el fix mínimo.
- La matriz, las capturas y la entrega visible se validan en el navegador nativo de Codex.
- Por ajuste posterior del objetivo, Playwright se admite como apoyo de trabajo; no sustituye la evidencia visible integrada.
