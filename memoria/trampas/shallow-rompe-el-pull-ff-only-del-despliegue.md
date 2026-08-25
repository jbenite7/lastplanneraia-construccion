---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-24
areas: [deploy, worktrees]
fuente: "Frente C de espacio-cuenta-siteground, ejecutado y revertido en prueba-lps el 2026-08-24 con autorización de Felipe"
resumen: "Un clon shallow en el servidor inutiliza `git pull --ff-only`, que es el comando del despliegue — y encima el `.git` no adelgaza, porque `gc` no poda lo alcanzable desde HEAD"
---
# Un clon shallow rompe el `pull --ff-only` del que depende el despliegue

**El síntoma.** Tras adelgazar el clon del servidor con `git fetch --depth=1`, el despliegue deja de
poder ejecutarse:

```
$ git pull --ff-only origin main
fatal: Not possible to fast-forward, aborting.      # rc=128
```

**Lo que parece.** Un ahorro de disco barato y reversible: el `.git` de un servidor pesa cientos de
megas, la historia completa la tiene `origin`, y `--unshallow` deshace. Suena a que solo se pierde
comodidad de consulta.

**Lo que es.** `git pull --ff-only` es **el comando del despliegue** de este repositorio
(`docs/siteground-deploy-routine.md`). Con un `origin/main` truncado, git no tiene la historia
intermedia para calcular el avance rápido contra el `HEAD` local, y aborta. **No se degrada el
despliegue: deja de poder hacerse.**

Y hay un segundo daño, menos visible, que afecta a la detección de migraciones. La rutina usa:

```bash
git log --name-only --diff-filter=A HEAD@{1}..HEAD -- database/migrations/
```

Ese comando **lee historia**. Medido sobre el mismo rango el 2026-08-24, con una migración real
dentro (`20260819_sembrar_linea_base_contractual.sql`):

| | Con shallow | Con historia completa |
|---|---|---|
| `pull --ff-only` | `rc=128` | `rc=0` |
| detección de migraciones | **ninguna** | detecta la migración |

Un despliegue que no detecta migraciones nuevas las omite en silencio. Es el peor de los dos daños,
porque el primero al menos falla ruidosamente.

**Y el ahorro tampoco llega.** Tras `fetch --depth=1` + `reflog expire --expire=now --all` +
`gc --prune=now`, los tres con `rc=0`, el `.git` **seguía en 366 MB** — ni un byte menos. `gc` no
poda objetos alcanzables, y toda la historia local sigue siéndolo desde `HEAD`. Para que adelgace de
verdad habría que mover `HEAD` al commit truncado, que es exactamente lo que el `pull` roto impide.
**El movimiento pierde por los dos lados: rompe el despliegue y no da el ahorro.**

**Cómo se sale.** `git fetch --unshallow origin`, y después **comprobar el servidor, no el código de
salida del comando**: que `pull --ff-only` vuelva a dar `rc=0`, que `.git/shallow` no exista, y que
`git status --porcelain` salga vacío.

**Cuánto costó.** Nada, y ese es el punto. El frente C de
[[docs/superpowers/specs/2026-08-18-espacio-cuenta-siteground-design]] escribió **tres
comprobaciones** «antes de darlo por bueno, no solo el tamaño», y declaró por adelantado cuál
decidía. Se ejecutó el 2026-08-24 con autorización de Felipe, las comprobaciones lo rechazaron, se
revirtió en minutos y `prueba-lps` quedó sano — y de paso al día, porque el `pull` de la
verificación trajo los 213 commits que llevaba de atraso.

**Descartar por medición es un resultado, no un fracaso.** Lo que hace que este caso valga como
página es que el diseño previó su propio rechazo: si la spec hubiera pedido solo «que el `.git` baje
de 60 MB», el frente habría salido en verde sobre un servidor incapaz de desplegar.

**Ámbito:** vale para cualquier checkout que sirva un despliegue por `git pull`. Producción **nunca
se tocó** — la propia spec la excluía de este frente.

Relacionadas: [[siteground-sin-tunel-ssh]] · [[grep-de-host-no-resuelve-los-include]] ·
[[el-trabajo-hecho-no-vuelve-solo-al-documento]]
