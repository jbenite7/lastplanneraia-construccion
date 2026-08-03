---
tipo: trampa
estado: vigente
fecha: 2026-07-28
areas: [docker, datos]
fuente: memoria-claude
origen: lps-aia-dos-stacks-docker
resumen: Hay dos stacks Docker con MySQL propio; conectarse al equivocado escribe en la base de otra sesión
---
Conviven **dos stacks Docker** con su propio MySQL, y el `docker-compose.yml` lleva `name: last-planner-aia` fijo, así que se distinguen por el directorio desde el que se levantaron:

| Stack | Red | MySQL | App | Directorio |
|---|---|---|---|---|
| `lps-aia-pdc` | `lps-aia-pdc_default` | **3308** | 8091 | `/Volumes/Crucial X6/Developer/lps-aia-pdc` |
| `last-planner-aia` | `last-planner-aia_default` | 3307 | — | `/Volumes/Crucial X6/Developer/lps-aia` |

**Por qué importa:** las dos bases se llaman `lastplanneraia_dev`. Un `docker run --network last-planner-aia_default` desde el worktree del PDC conecta sin error a la base equivocada — la de otras sesiones — y falla con `Table 'pdc_...' doesn't exist`, que parece un problema de migraciones y no lo es. Si la tabla sí existiera en ambas, escribiría datos en la base de otra sesión sin avisar.

**Cómo comprobarlo:** `docker ps --format '{{.Names}}\t{{.Ports}}' | grep mysql` — el puerto publicado desanda la ambigüedad.

Además, `docker compose up` desde un worktree distinto **recrea los contenedores del otro stack** por el `name:` fijo. Para correr código de un worktree aislado, usar `docker run --rm` montando el worktree en `/var/www/html` y el `vendor/` del original en solo lectura, sobre `--network lps-aia-pdc_default`.

Relacionado: [[pdc-e2e-sandbox]], [[worktree-compartido-arrastra-commits]]
