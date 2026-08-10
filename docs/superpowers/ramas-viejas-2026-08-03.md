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

---

## Cierre — 2026-08-10

Ejecutado en el Frente 0 (Task 2), con autorización explícita del usuario. Cierra la **Task 28**
de la campaña de dark mode, que llevaba abierta como «papeleo pendiente».

**Las 22 del censo original ya no existían en local.** Al abrir el Frente 0 solo quedaban cuatro
ramas, todas posteriores a aquel censo, y las siete referencias a ramas remotas que el servidor ya
no tiene.

### Qué se borró y con qué medida

| Rama local | Commits fuera de `main` | Resultado |
|---|---|---|
| `claude/cierre-ds-110` | 0 | Borrada |
| `claude/cranky-dhawan-aa8725` | 0 | Borrada en local. **Sigue viva en el remoto**, que no se toca. |
| `feat/marca-construccion` | 0 | Borrada |
| `respaldo/main-pre-merge-20260810` | 0 | Borrada. Era la red del merge de `origin/main` de ese día; el merge salió limpio y está publicado. |

Las cuatro se borraron con `git branch -d`, que **se niega** si una rama tiene contenido propio.
Ninguna se resistió.

### Las siete referencias remotas huérfanas, y por qué `prune` no las veía

`git remote prune origin --dry-run` salía vacío mientras `git branch -r` mostraba ocho referencias
y el servidor solo tenía dos ramas. La causa: este clon es **de una sola rama**, con
`remote.origin.fetch = +refs/heads/main:refs/remotes/origin/main`. Fuera de ese refspec, git no
considera nada prunable, así que las referencias sobrantes quedan invisibles al mantenimiento
normal y se acumulan en silencio. Se borraron a mano con `git update-ref -d`.

Seis de las siete tenían **0 commits fuera de `main`**. La séptima merece su párrafo.

### `wiki/fix-enlace-roto-subir-version-ds`: única no es lo mismo que valiosa

`git cherry main` la marcaba con un commit **sin equivalente por contenido**, y esa referencia era
la última copia: la rama ya no existe en el servidor. Parecía trabajo a punto de perderse.

No lo era. Su commit `9d5556a4` crea `memoria/trampas/subir-la-version-del-ds-cobra-deudas.md`, y
**esa página ya está en `main` en una versión mejor**: 52 líneas con `fuente:` citando los scripts
reales (`design-system-audit.mjs`, `exceptions.json`, `design-system-contracts.mjs`,
`design-system-activation-git.mjs`), frente al borrador de 24 líneas con `fuente: sesion` de la
rama. Alguien rehízo el trabajo mejor y nadie borró el intento previo.

**La lección, que vale para el próximo censo:** `git cherry` responde «¿es este parche
byte-idéntico a alguno de main?», no «¿aporta algo?». Un borrador superado siempre saldrá marcado
como único. Antes de conservar una rama por lo que `cherry` diga, hay que **abrir el commit y
compararlo con lo que main tiene hoy**.

### Qué cobertura se pierde al borrar

Se pierde poder recuperar esas historias **por nombre de rama**. No se pierde ningún commit: los de
las cuatro ramas locales están en `main` (medido, 0 commits fuera), y el único candidato real
resultó superado por una versión mejor ya versionada. La garantía es la medición de arriba, no la
confianza en el censo del 2026-08-03.
