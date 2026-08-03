---
tipo: trampa
estado: vigente
fecha: 2026-08-03
areas: [design-system, qa, worktrees]
fuente: repo
resumen: desde un worktree, `test:design-system:static` compara mtimes del worktree con los del árbol principal (que es lo que monta Docker) y da un rojo falso en «stylesheet versions follow nested CSS changes»
---
`npm run test:design-system:static` es **mitad Node y mitad PHP**, y cada mitad lee un árbol
distinto cuando se lanza desde un worktree:

- Node (`node --test`) lee los archivos del **worktree** con `readFile`/`statSync`.
- `runPhpInApp` ejecuta dentro del contenedor `app`, y `docker-compose.override.yml:4` monta
  `./:/var/www/html` del **working tree principal** — un `docker compose` desde un worktree
  opera sobre el stack principal salvo que se le dé identidad propia (ver
  [[aislar-stack-docker-por-worktree]]).

`tests/design-system/foundation.test.mjs:273` («stylesheet versions follow nested CSS changes»)
cruza justo esos dos lados: toma la versión que estampa `DesignSystemHeadComponent` —que es
`filemtime()` **del contenedor**— y la compara contra el mtime de `public/css/tokens.css`
**del worktree**. Como el checkout de un worktree reescribe mtimes, el rojo es seguro y no
significa nada:

    AssertionError: entrypoint 1785782766 is older than tokens 1785792411

Medido el 2026-08-03: el mismo test pasa 28/28 lanzado desde `/Volumes/Crucial X6/Developer/lps-aia`.

**Cómo no perder el tiempo:** este suite se mide desde el árbol principal, o desde un worktree
con stack Docker propio. Un rojo de versiones/mtimes lanzado desde un worktree con stack
compartido se descarta sin investigar. Vale también al revés: no confíes en un verde de PHP
medido así, porque estás validando el código del otro árbol.

Es un primo del caso de [[branch-preexisting-red-gates]] — rojo que no es de quien lo ve — y
suma al inventario de [[dos-stacks-docker]].
