---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-18
areas: [design-system, worktrees, docker]
fuente: medido al investigar el rojo de `stylesheet versions follow nested CSS changes`, 2026-08-18
resumen: "Un test que calcula un valor dentro del contenedor y lo compara contra un statSync del host mide dos copias del repo cuando se lanza desde un worktree: da falso rojo, y en el cruce inverso daría falso verde"
---

# Un gate que mide dos árboles a la vez

`stylesheet versions follow nested CSS changes` (`tests/design-system/foundation.test.mjs`) protege
un invariante real: la versión de caché del entrypoint CSS debe ser **igual o más nueva** que
`tokens.css`, porque si no, cambias los tokens y los navegadores siguen sirviendo el bundle viejo.

Hasta el 2026-08-18 lo medía **cruzando dos copias del repositorio**:

- la versión la calculaba PHP **dentro del contenedor**, que monta
  `${LPS_CODE_ROOT:-/Volumes/Crucial X6/Developer/lps-aia}` — el checkout principal;
- el `tokens.css` lo leía con `statSync` **desde el worktree que lanza la suite**.

Cuando ambos son el mismo árbol no se nota. Desde un worktree, no lo son. Medido ese día:

| Comparación | Valores | Resultado |
|---|---|---|
| Todo en el contenedor | 1787024214 ≥ 1786462575 | cumple |
| Todo en el worktree | 1787062717 = 1787062717 | cumple |
| Cruzado (el que hacía el test) | 1787024214 < 1787062717 | **falla** |

**El falso verde es el peligro de verdad, no el falso rojo.** El rojo se investiga y se descarta; el
cruce inverso —árbol montado con CSS más reciente que el del worktree— habría **aprobado** un
entrypoint desactualizado sin que nadie mirara. Un gate que puede aprobar lo que debería frenar
vale menos que no tener gate, porque además da confianza.

Arreglado leyendo el `mtime` por la misma vía que la versión
(`runPhpInApp('echo filemtime("public/css/tokens.css");')`), de modo que el test mide un solo árbol
se lance desde donde se lance. Verificado 28/28 desde el worktree, desde el checkout principal, y
lanzando el mismo archivo con el `cwd` del otro.

**La regla general, que sobrevive a este test concreto:** si una comprobación toma un valor de
dentro del contenedor y otro de fuera, no está midiendo un invariante — está midiendo si las dos
copias coinciden hoy. Los dos extremos de una comparación salen de la misma vía o la comparación no
significa nada. Aplica a cualquier gate de esta suite que combine `runPhpInApp` con lecturas del
sistema de archivos del host.

Relacionado: [[aislar-stack-docker-por-worktree]], [[verificas-un-arbol-y-publicas-otro]].
