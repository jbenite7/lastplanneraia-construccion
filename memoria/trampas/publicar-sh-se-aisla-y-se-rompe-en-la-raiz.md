---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-19
areas: [proceso, docker]
fuente: publicación de 6abe2436, 2026-08-19
resumen: "El aislamiento de scripts/publicar.sh deja sin contenedor al proyecto compose, y foundation.test.mjs cae al camino lento hasta reventar el tope de 180 s"
---
# `publicar.sh` se aísla, y en el worktree principal eso lo rompe

`scripts/publicar.sh` exporta `COMPOSE_PROJECT_NAME="lps-aia-publicar-<sha>"` y
`LPS_CODE_ROOT="$PWD"` antes de verificar. Lo hace por una razón buena y medida el 2026-08-18: sin
esas dos variables, `docker compose exec app` de cualquier worktree aterriza en el contenedor
compartido, y el gate llegó a dar tres verdes desde un worktree en `06627082` mientras el contenedor
servía el principal en `081a33c8`. Un gate que avala con evidencia de otro árbol es peor que no
tener gate.

**El efecto secundario:** ese nombre de proyecto **no tiene ningún contenedor corriendo**. Y
`tests/design-system/foundation.test.mjs:12-19` decide su camino preguntándoselo a Docker:

```
docker compose ps --status running --services
  → si incluye `app`: compose exec -T app        (1,2 s)
  → si no:            compose run --rm --no-deps  (levanta un contenedor nuevo)
```

Bajo el proyecto aislado siempre cae al segundo. La suite estática tiene un tope de 180 s para
`node-tests`, y lo revienta: **445 tests, 444 pasan, 0 fallan, 1 cancelado por tiempo**. El script
lo reporta como `✘ node-tests` y deniega la publicación.

**Por qué no se arregla levantando el stack aislado:** `docker-compose.yml` publica los puertos
8081, 3307 y 8082 fijos, sin variable de por medio para los dos primeros. Un segundo stack choca.

**Cómo se distingue de una regresión de verdad, que es lo que importa:** un rojo real trae
`fail > 0`. Éste trae `fail 0` y un `cancelled 1` con el mensaje `Promise resolution is still
pending but the event loop has already resolved`. Si el conteo dice que no falló nada, no falló
nada: se acabó el tiempo.

**En el worktree principal el aislamiento no aporta**, y ahí está la asimetría: `LPS_CODE_ROOT`
ya apunta a la raíz, que es justo lo que el contenedor compartido monta. Comprobado el 2026-08-19
antes de publicar `6abe2436`, y comprobado en vivo y no por deducción: `docker inspect` da
`/Users/felipebenitez/Developer/lps-aia -> /var/www/html`, y un archivo testigo escrito en la raíz
se lee dentro del contenedor. La propiedad que el aislamiento quería garantizar se cumple ahí sola.

**Qué se hizo esa vez:** reproducir a mano las tres comprobaciones bloqueantes del script en el
entorno compartido —`design-system:static` RC=0 (8/8), `contrato piloto PG` RC=0, `wiki` RC=0—,
cada código leído en su propia línea, y publicar con la vía manual que `AGENTS.md` §Publicación
admite «cuando el script no aplique»: `git push origin <sha>:refs/heads/main`.

**Lo que queda sin decidir:** arreglar `publicar.sh` toca un gate, y los gates son contrato. La
salida natural es que el aislamiento se aplique **solo en worktrees secundarios** —comparar
`git rev-parse --git-common-dir` con `--git-dir`— pero eso lo decide el usuario, no quien tropieza.
Ver [[suite-estatica-miente-en-worktree-secundario]], que es la trampa hermana y el motivo de que
el aislamiento exista.
