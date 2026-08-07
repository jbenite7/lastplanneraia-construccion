# Goal: Cierre de la versión 1.1.0 del design system

**Objetivo:** Publicar la versión 1.1.0 del design system: pagar o re-vencer con evidencia las 39
excepciones que expiran en ella (migración de `/proyectos` incluida), flexibilizar los gates de
activación a «al menos 1.0.0» y sincronizar manifiestos + changelog en un commit de activación
atómico.

**Condición de hecho:** `npm run test:design-system:static` en **8/8** con
`docs/design-system/version.json` en `1.1.0/stable`; cero entradas `expiresAtVersion: "1.1.0"` en
`docs/design-system/exceptions.json`; informe una-a-una (pagadas N / re-vencidas M) en el ledger;
ciclo triple de `/proyectos` aprobado.

**Plan:** `docs/superpowers/plans/2026-08-04-cierre-version-1-1-0-design-system.md` (7 tasks).
**Spec:** `docs/superpowers/specs/2026-08-04-cierre-version-1-1-0-design-system-design.md`
(decisiones D1–D4 del usuario, 2026-08-04).

**Precondición dura:** no arranca hasta que la campaña dark mode
(`docs/superpowers/plans/2026-08-04-cierre-dark-mode-campana-decisiones.md`) haya terminado (D4).

### ✅ Precondición D4 CUMPLIDA — 2026-08-07. Este goal está listo para arrancar.

La campaña dark mode cerró: **36 tareas ejecutadas, 2 retiradas por la deprecación del PDC V1, 0
pendientes**. La disposición de las 38, una a una y con su commit, está en la sección «Cierre de la
campaña» al final de su plan. Lo verificó el Step 6 de su Task 31 contra `git log`, no contra las
casillas del plan, que nunca se marcaron y no son fiables.

**Antes de tocar nada, lee `memoria/trampas/subir-la-version-del-ds-cobra-deudas.md`:** subir la
versión del design system vence excepciones y cobra deudas de golpe; son 39, no 38.

**Dos avisos del cierre de la campaña que afectan a este goal:**

1. **La suite estática miente desde un worktree secundario.** `docker-compose.yml:1` fija
   `name: last-planner-aia`, así que las pruebas que ejecutan PHP en el contenedor golpean el
   worktree principal, no el tuyo, y `node-tests` sale en rojo por mtimes ajenos. Exporta
   `COMPOSE_PROJECT_NAME` propio o la condición de hecho («8/8») no significará nada.
2. **El contrato de alcance cambió, y ya está en git:** `72132cbc`, «retirar las prohibiciones de
   movil, tablet y tema claro de los .md normativos». Puedes apoyarte en él sin comprobar nada.
   `AGENTS.md:35` es ahora la redacción vigente. **Pero no lo leas como permiso de alcance gratis:**
   el tema `linen` se retiró del producto el 2026-07-25 (DS-030) y **no hay conmutador**, así que
   trabajar en claro implica **reconstruirlo, no reactivarlo**. Si alguna excepción de las 39 se
   justificaba en el puente móvil o de tema, ese cambio de contrato puede alterar si toca pagarla o
   re-vencerla: compruébalo excepción por excepción, no en bloque.

Lo que queda abierto de la campaña son **decisiones del usuario**, recogidas en `docs/DESIGN-AUDIT.md`
§Pendiente de decisión, no trabajo heredado por este goal.

## Archivos de este goal

- [goal.md](goal.md) — este contrato.
- Plan: [2026-08-04-cierre-version-1-1-0-design-system.md](../../docs/superpowers/plans/2026-08-04-cierre-version-1-1-0-design-system.md)
- Spec: [2026-08-04-cierre-version-1-1-0-design-system-design.md](../../docs/superpowers/specs/2026-08-04-cierre-version-1-1-0-design-system-design.md)
- Estado de goals: [estado.md](../../memoria/goals/estado.md)
