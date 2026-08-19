---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-18
areas: [docker, worktrees, qa]
fuente: medido al reparar el editor de celda Tom Select, 2026-08-18
resumen: "Servir tu rama con LPS_CODE_ROOT dura hasta el primer `docker compose restart app`, que rehace el contenedor con el valor por defecto —la raíz del repo— sin decir nada; y el `.env` enlazado del worktree queda roto dentro del contenedor, porque el enlace apunta a una ruta del host que ahí no existe"
---
# `restart` devuelve el montaje a la raíz

`CLAUDE.md` §Runtime documenta cómo ver tu rama en el navegador:

```bash
LPS_CODE_ROOT="$(pwd)" docker compose up -d app
```

Funciona. Lo que no dice —y cuesta caro— es que **ese montaje no sobrevive a un `restart`**.

## Lo medido (2026-08-18)

Reparando el editor de celda de Programación Intermedia, el navegador seguía ejecutando el archivo
viejo pese a subir la versión del `<script>`. Comprobado dentro del contenedor:

```
host:        mtime Aug 18 13:45
contenedor:  mtime 2026-07-17 08:31   ← un mes atrás
```

No era caché del navegador ni desincronía del disco externo: era **otro árbol**. Entre medias yo
había ejecutado `docker compose restart app` para «forzar el remontaje», y `restart` **no reusa la
configuración con la que creaste el contenedor**: lo rehace resolviendo
`${LPS_CODE_ROOT:-<raíz del repo>}` otra vez, ahora sin la variable, así que vuelve a montar la raíz
—que está en `main` y no tiene tu trabajo—. `docker inspect` lo confirma y es la única forma de
verlo:

```bash
docker inspect last-planner-aia-app-1 --format '{{range .Mounts}}{{.Source}}{{end}}'
```

**Falla del lado peligroso.** No hay error, ni aviso, ni 404: la aplicación responde perfectamente
sirviendo el código de otra rama. Yo di por rotos arreglos que estaban bien y llegué a reescribir
código correcto persiguiendo un fallo que no existía. Lo mismo vale para `docker compose up -d app`
sin la variable.

**El remedio es recrear, no reiniciar, y comprobar el montaje después:**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose up -d --force-recreate app
docker inspect last-planner-aia-app-1 --format '{{range .Mounts}}{{.Source}}{{end}}'
```

Y devolverlo a la raíz al terminar, que es lo que espera cualquier otra sesión.

## El `.env` enlazado no existe dentro del contenedor

Segunda trampa del mismo escenario, y contradice a medias lo que dice `CLAUDE.md`. Ese archivo manda
**enlazar** el `.env` en el worktree —con razón: las copias envejecen en silencio—. Pero cuando el
contenedor monta **el worktree**, el enlace apunta a `/Volumes/Crucial X6/Developer/lps-aia/.env`,
una ruta **del host** que dentro del contenedor no existe. El enlace queda colgando.

Síntoma medido: `GET /dev/entrar?u=test.R&p=Prueba` responde `302` a `/login` y la puerta de
servicio parece cerrada. No lo está — es que `Dotenv` no encuentra `.env`, así que `DEV_DOOR` llega
vacío. Buscarlo en `DEV_DOOR`/`DEV_DOOR_USERS`, que es lo que manda `AGENTS.md`, no lleva a ninguna
parte porque **ahí las claves están bien**.

Mientras sirvas el worktree hace falta una **copia** dentro de él, y devolver el enlace al terminar.
El enlace sigue siendo lo correcto para el uso normal, en el que el contenedor monta la raíz.

Relacionadas: [[servir-worktree-stack-efimero]], [[suite-estatica-miente-en-worktree-secundario]],
[[un-verde-solo-vale-para-el-arbol-donde-se-midio]], [[variable-vacia-tapa-el-env]], [[CLAUDE]].
