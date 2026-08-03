---
tipo: trampa
estado: vigente
fecha: 2026-07-28
areas: [worktrees]
fuente: memoria-claude
origen: lps-aia-worktree-compartido-arrastra-commits
resumen: Dos sesiones en el mismo worktree se arrastran los cambios sin commitear; aislar exige worktree Y base propios
---
Cuando dos sesiones trabajan a la vez sobre el mismo worktree (`lps-aia-pdc` es worktree de `lps-aia`, comparten repositorio git), **los cambios sin commitear de una acaban dentro de un commit de la otra**. Ocurrió el 2026-07-28: `2357b0a` arrastró trabajo ajeno y `3c3c1ed` tuvo que rescatarlo en un commit aparte.

**Why:** `git add`/`git commit` recogen el árbol entero, no «lo que escribió esta sesión». No hay forma de tener cuidado que lo evite; sí ocurre trabajo duplicado en paralelo sin que ninguna de las dos lo note hasta chocar.

**How to apply:**
- Detectar pronto: si un Edit avisa «file had been modified on disk», comprobar `git status` completo y los mtimes (`find . -mmin -12`) antes de seguir. Si aparecen archivos que no tocaste, hay otra sesión viva.
- Aislar de verdad son **dos** cosas: worktree propio **y base de datos propia**. Solo el worktree es aislamiento ficticio en cuanto la tarea incluye DDL — una migración con `DROP COLUMN` rompe a la otra sesión al instante. Clonar la base cuesta poco (174 MB / 333 tablas → `mysqldump` + restore dentro del contenedor, ~1 min).
- Antes de implementar algo planificado hace rato, **verificar que no está ya hecho**: comprobar el esquema real en la base y `git log` de la rama. Un plan aprobado no garantiza que el trabajo siga pendiente.

Relacionado: [[dos-stacks-docker]]
