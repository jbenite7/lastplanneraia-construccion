---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-18
areas: [worktrees, proceso]
fuente: sesión del contrato de login, 2026-08-18, y la corrección del usuario
resumen: "Borrar el worktree no saca a la sesión de los worktrees: el harness crea otro y la mueve allí, con la misma rama, así que hay que volver a comprobar dónde quedó"
---
# Borrar el worktree no cierra la sesión

Al borrar el worktree donde vive una sesión, **el harness crea otro y traslada la sesión allí**,
checkouteado en la misma rama. El borrado se ejecuta y se confirma; la sesión sigue en un worktree,
con otro nombre.

Ocurrió el 2026-08-18: se borró `nifty-hellman-5a2f63` —comprobado limpio, publicado y sin ningún
contenedor montándolo— y la sesión reapareció en `great-kare-4470f3`, con la rama
`claude/nifty-hellman-5a2f63` otra vez checkouteada. **Lo notó el usuario, no la sesión**, que ya
había dado el borrado por cerrado.

## Dos efectos secundarios

- **La rama sobrevive y queda ocupada.** `git branch -d` se niega mientras esté checkouteada en
  algún worktree, aunque sea uno que acaba de nacer. Hay que soltarla primero
  (`git checkout --detach`, que no cambia ningún archivo si se queda en el mismo commit).
- **«Borrado» no es un estado final comprobado.** Confirma con `git worktree list` **y** con dónde
  quedó la sesión, no solo con que el directorio ya no exista.

## Antes de borrar

Tres comprobaciones, todas baratas, y ninguna es teórica en este repo:

- **Trabajo sin publicar**: `git status` limpio y `git log origin/main..HEAD` vacío.
- **Que no lo monte un contenedor vivo.** Borrar el worktree que el stack está sirviendo lo deja
  sirviendo 404 y hace fallar `docker exec` con «container breakout detected» — ya ocurrió, ver
  [[exec-en-contenedor-vivo-corre-el-repo-ajeno]].
- **Los ignorados que se van con él**: `.env` debe ser un **enlace** al de la raíz, no una copia
  (así lo manda `CLAUDE.md`), y entonces borrarlo no toca el archivo real; y el `.claude/sesiones.md`
  del worktree suele ser una copia vieja, nunca la buena —
  [[cada-worktree-tiene-su-copia-congelada]].

Relacionado: [[worktree-compartido-arrastra-commits]], [[verificas-un-arbol-y-publicas-otro]],
[[aislar-stack-docker-por-worktree]].
