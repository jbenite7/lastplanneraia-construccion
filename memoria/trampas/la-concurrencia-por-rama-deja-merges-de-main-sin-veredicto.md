---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-09-28
areas: [qa, proceso]
fuente: "gh run list --workflow=ci.yml (últimas 100 corridas, 2026-09-28), .github/workflows/ci.yml (concurrency), AGENTS.md §Publicación"
resumen: "Con cancel-in-progress agrupado por rama, 5 de los últimos 25 merges a main quedaron sin veredicto; el grupo de un push tiene que ser el SHA"
---
# La concurrencia por rama deja merges de `main` sin veredicto

**El síntoma.** En la lista de corridas del CI, varias de `push` a `main` aparecen `cancelled` a los
0, 1, 5, 7 y 11 minutos. En las últimas 25 corridas eran cinco merges: #79, #70, #69, #56 y #68. En
las últimas 100 hubo 6 `push` y 6 `pull_request` cancelados.

**Lo que parece.** Un timeout, un runner caído o un fallo del propio CI.

**Lo que es.** El workflow agrupaba por `github.ref` con `cancel-in-progress: true`. Para un PR eso es
lo deseado (un push nuevo mata la corrida vieja). Para `main` significa que el merge siguiente cancela
la confirmación del anterior, y `AGENTS.md` §Publicación cuenta con esa confirmación posterior al
merge.

**Cómo se sale.** Grupo distinto por evento: `github.ref` en `pull_request` y `github.sha` en todo lo
demás, con `cancel-in-progress` solo para PR. No basta con poner `false` y dejar el grupo por rama:
según la documentación de GitHub Actions, un grupo solo conserva una corrida pendiente y la más nueva
reemplaza a la anterior. Ese comportamiento es el documentado; no se reprodujo en este repositorio.

**Cuánto costó.** Cinco merges de veinticinco sin el dato que la política de cierre da por hecho.
Nadie lo notó hasta contar las cancelaciones el 2026-09-28.

Relacionadas: [[qa-y-gates]] · [[un-verde-caduca-y-a-veces-no-existe]] ·
[[docs/superpowers/specs/2026-09-28-ci-por-carriles-design|spec del CI por carriles]]
