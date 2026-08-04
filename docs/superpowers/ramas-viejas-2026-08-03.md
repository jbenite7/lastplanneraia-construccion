# Censo de ramas viejas — 2026-08-03

**Realizado:** 2026-08-03 tras cierre dark mode fases 0–3 (Task 3)  
**Método:** `git cherry main RAMA` — detecta commits por contenido de parche, no por hash  
**Resultado:** 22 ramas censadas (17 del brief + 5 hallazgos adicionales de Task 1)

## Resumen

| Veredicto | Cantidad |
|-----------|----------|
| Borrable (contenida en main) | 22 |
| Con contenido único | 0 |
| No existe | 0 |

---

## Tabla de detalle

| Rama | Commits únicos | Contenido | Veredicto |
|------|----------------|-----------|-----------|
| c1-retiro-pdc-viejo | 0 | — | **Borrable** |
| docs/rutina-deploy-orden-migraciones | 0 | — | **Borrable** |
| claude/vigorous-spence-bb48b9 | 0 | — | **Borrable** |
| pdc-a4-fechas | 0 | — | **Borrable** |
| pdc-a42-frentes | 0 | — | **Borrable** |
| pdc-b1-amarre-cronograma | 0 | — | **Borrable** |
| pdc-deudas-datos | 0 | — | **Borrable** |
| pdc-dev | 0 | — | **Borrable** |
| pdc-revision-ux | 0 | — | **Borrable** |
| pdc-unificacion-repos | 0 | — | **Borrable** |
| worktree-agent-ac5c40b19109aad58 | 0 | — | **Borrable** |
| worktree-lab-preview | 0 | — | **Borrable** |
| worktree-pdc-b2-vencimientos | 0 | — | **Borrable** |
| worktree-pdc-b3-torre-control | 0 | — | **Borrable** |
| worktree-pdc-ola2-ayuda-in-app | 0 | — | **Borrable** |
| worktree-pdc-ola2-equipo-alq-comp | 0 | — | **Borrable** |
| worktree-pdc-presupuesto-impacto-tamiz | 0 | — | **Borrable** |
| claude/admiring-bose-b4ef3c | 0 | — | **Borrable** ¹ |
| claude/competent-jepsen-dec1c4 | 0 | — | **Borrable** ¹ |
| claude/nostalgic-austin-50d4aa | 0 | — | **Borrable** ¹ |
| claude/nostalgic-thompson-dceb00 | 0 | — | **Borrable** ¹ |
| worktree-usabilidad-altas-y-medias | 0 | — | **Borrable** ¹ |

¹ Hallazgo Task 1: fusionadas en main pero aún ancladas a worktrees activos.

---

## Notas

- **Todas las ramas (22/22) están contenidas en main**: no hay trabajo único que perder.
- Las 5 ramas marcadas con ¹ no figuraban en el brief de Task 3 pero se incluyeron por indicación del contexto (confirmación Task 1 de ramas ya fusionadas).
- Este documento es informativo; **no borra ni fusiona** ninguna rama. La decisión de limpieza queda en manos del usuario.
