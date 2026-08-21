---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-21
areas: [docker, worktrees, qa]
fuente: docker-compose.override.yml:27, CLAUDE.md seccion "Runtime & commands", medido en sesion
resumen: "El symlink del .env que CLAUDE.md manda crear en cada worktree apunta a una ruta del host; en cuanto se apunta el contenedor a ese worktree, el enlace queda roto dentro y la app responde 500 en todas las rutas"
---

`CLAUDE.md` manda enlazar el `.env` en cada worktree nuevo, y con razon: sin el,
`docker compose` resuelve `${DB_NAME}` y `${DB_PASS}` a cadena vacia.

```bash
ln -s ~/Developer/lps-aia/.env .env
```

**La trampa:** ese enlace lo resuelve `docker compose`, que corre **en el host**, donde la
ruta existe. Pero en cuanto se apunta el contenedor al worktree —lo que
[[suite-estatica-miente-en-worktree-secundario]] obliga a hacer para que el gate no mida
otro arbol— el contenedor monta ese directorio en `/var/www/html`, y ahi dentro el
enlace apunta a `/Users/felipebenitez/...`, una ruta del host que **no existe en el
contenedor**:

```
docker compose exec -T app sh -c 'ls -l /var/www/html/.env; cat /var/www/html/.env'
lrwxr-xr-x 1 root root 43 /var/www/html/.env -> /Users/felipebenitez/Developer/lps-aia/.env
cat: /var/www/html/.env: No such file or directory
```

**Lo que se ve no se parece a la causa.** La app responde **500 en todas las rutas** y el
log habla de Composer, no del `.env`:

```
PHP Fatal error: Uncaught Error: Failed opening required '.../vendor/autoload.php'
```

Ese primer error es real y tiene su propia causa —un worktree nuevo **no trae `vendor/`**,
que esta en `.gitignore`, asi que hay que correr `composer install` dentro del
contenedor—. Pero al resolverlo aparece el segundo, que es el de esta trampa: la app ya
arranca y **la puerta de servicio redirige a `/login`**, como si `DEV_DOOR` estuviera en
cero. No lo esta: el `.env` que la declara es ilegible desde dentro.

**Los dos remedios se pelean, y esa es la parte que hay que saber:**

| Para que funcione | Hace falta | Rompe |
|---|---|---|
| El gate estatico | apuntar el contenedor al worktree (`LPS_CODE_ROOT`) | el `.env` enlazado |
| La sesion de dev en el navegador | un `.env` legible **dentro** del arbol montado | — |

Un enlace relativo tampoco sirve: el worktree vive en `<raiz>/.claude/worktrees/<nombre>`
y cualquier `../` para alcanzar el `.env` de la raiz sale del directorio montado.

**Lo que queda, entonces, es una copia** del `.env` dentro del worktree mientras el
contenedor lo monte. `CLAUDE.md` avisa —con razon, medido el 2026-08-18 con seis copias
viejas sueltas— que las copias se quedan desactualizadas en silencio. Sigue siendo cierto:
la copia es el precio de poder verificar en navegador desde un worktree, no una mejora, y
hay que borrarla al terminar.

Relacionada: [[suite-estatica-miente-en-worktree-secundario]] y
[[gate-que-mide-dos-arboles-a-la-vez]] (las dos tratan del mismo montaje, desde el lado
del gate); [[worktrees]] es el mapa del area.
