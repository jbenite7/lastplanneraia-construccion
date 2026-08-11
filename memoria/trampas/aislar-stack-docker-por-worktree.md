---
tipo: trampa
estado: vigente
fecha: 2026-07-29
areas: [docker, worktrees]
fuente: memoria-claude
origen: lps-aia-aislar-stack-docker-por-worktree
resumen: receta para que un worktree tenga stack Docker y base propios sin recrear el stack ajeno — COMPOSE_PROJECT_NAME/COMPOSE_FILE en .env, ports !override y volumen no externo
---
`docker-compose.yml` trae `name: last-planner-aia` fijo y el volumen `db_data` es
`external: true, name: htdocs_db_data`. Por eso, desde cualquier worktree, un `docker compose` a
secas opera sobre el stack del working tree principal y sobre **su misma base** (ver
[[dos-stacks-docker]] y [[worktree-compartido-arrastra-commits]]).

Receta que funcionó el 2026-07-29 para aislar de verdad:

1. Un `docker-compose.wt.yml` sin versionar (añadir a `.git/info/exclude` del **git-common-dir**; el
   `info/exclude` del `.git/worktrees/<n>` no se usa):
   ```yaml
   services:
     app:
       ports: !override        # sin !override las listas de puertos se FUSIONAN y choca con 8081
         - "8092:80"
   volumes:
     db_data:
       external: false
       name: pdc_ola2_db_data  # base propia; si no, se escribe en la del otro worktree
   ```
2. En el `.env` del worktree (que ya está gitignoreado), `APP_URL` al puerto nuevo, `DOCKER_DB_PORT`,
   `DOCKER_ADMINER_PORT`, y sobre todo:
   ```
   COMPOSE_PROJECT_NAME=pdc-ola2
   COMPOSE_FILE=docker-compose.yml:docker-compose.override.yml:docker-compose.wt.yml
   ```
   Esas dos líneas son lo que hace que `docker compose` **a secas** apunte al stack propio. Sin
   ellas hay que arrastrar `-p` y tres `-f` en cada comando, y el harness e2e —que deduce el puerto
   con `docker compose port app 80` en `tests/browser/support/pdc-sandbox.mjs`— resuelve al stack
   ajeno y siembra en la base equivocada.
3. Clonar los datos: `mysqldump` del contenedor db del stack principal → `mysql` del propio.

Trampa aparte: `$DC="docker compose -p …"` en zsh da `rc=127` («command not found»); escribe el
comando literal en cada llamada.

## La consecuencia al **verificar**, que es la que muerde de verdad

Lo de arriba se lee como higiene de datos, y el filo peor no es ese. Si trabajas en un worktree y
el stack que corre es el del árbol principal, **`localhost:8081` sirve los archivos del árbol
principal, no los tuyos**. Entonces una captura «del después» muestra el archivo **viejo**: verificas
un cambio que no es el tuyo y sale verde. Es un falso verde silencioso, sin ningún síntoma — el
mismo defecto que [[captura-playwright-miente]], por otra puerta.

Medido el 2026-08-11 en el frente `focus-visible-verde`: el cambio movía píxeles a propósito y la
página en `:8081` seguía pintando el valor anterior.

**Receta ligera cuando solo hace falta ver tu árbol, sin base propia ni aislamiento de datos** —para
un cambio de CSS o de vista, donde clonar la base es gasto puro—: un contenedor suelto con la misma
imagen, montando el worktree y **entrando a la red del stack que ya corre**, para reutilizar su
MySQL sin tocarlo.

```bash
cp "<ruta-del-arbol-principal>/.env" .env      # el .env NO viaja al worktree
docker run -d --name lps-wt-app --network last-planner-aia_default -p 8095:80 \
  -v "$PWD":/var/www/html -e APP_ENV=development last-planner-aia-app
docker exec -w /var/www/html lps-wt-app composer install --no-interaction
```

Sirve para **leer**, que es lo que hace una verificación visual. Si el cambio escribe en la base, no
vale: comparte el MySQL del otro stack y aplica la receta completa de arriba. Bórralo al terminar
(`docker rm -f lps-wt-app`).

Los tres pasos son obligatorios, y los tres se descubrieron a base de `500` sin causa visible el
2026-08-11 en el frente `vocabulario-estados-cascada`. **El `.env` no viaja al worktree** —esta nota
afirmaba lo contrario hasta esa fecha—, así que sin copiarlo no hay conexión ni puerta de servicio;
está gitignoreado y copiarlo no ensucia el árbol. **El worktree tampoco trae `vendor/`**, y sin
`composer install` dentro del contenedor `public/index.php` muere en el `require_once` del autoload.
Y las `DB_*` **no se pasan por `-e`**, aunque la receta original las llevara: los valores del `.env`
vienen entrecomillados (`DB_USER="root"`), el `-e` los propaga con las comillas dentro y el
contenedor gana porque `Dotenv::createImmutable()` no sobrescribe lo que ya está en el entorno, así
que MySQL recibe el usuario `'"root"'` y responde `Access denied` — un mensaje que no se parece en
nada a la causa. Elige además el puerto comprobando antes que está libre: el fallo por puerto
ocupado no dice cuál.
