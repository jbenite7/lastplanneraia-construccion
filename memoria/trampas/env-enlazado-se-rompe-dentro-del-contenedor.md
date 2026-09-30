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

**Lo que quedaba, entonces, era una copia** del `.env` dentro del worktree mientras el
contenedor lo montara. `CLAUDE.md` avisa —con razon, medido el 2026-08-18 con seis copias
viejas sueltas— que las copias se quedan desactualizadas en silencio. Era cierto: la copia era
el precio de poder verificar en navegador desde un worktree, no una mejora. **Ya no es lo
unico que queda: ver la actualizacion de abajo.**

## Actualizacion 2026-09-29: un enlace duro si funciona

Medido al servir el worktree `fix-visual-pg` (PR #89): tras un `ln -s` que fallo por la razon
de arriba, Felipe creo un **enlace duro** y la puerta de servicio abrio sesion con normalidad.

```bash
ln -f "<raiz>/.env" "<worktree>/.env"
```

Un enlace duro no guarda una ruta: **son dos nombres del mismo archivo**, asi que el contenedor
lo lee como un archivo comun y no hay nada que resolver en el host. Y, a diferencia de la copia,
**no envejece**: editar el `.env` de la raiz es editar el del worktree. Se comprueba con el inodo,
que debe ser el mismo en ambos y con dos enlaces (`stat -f "%i %l" <ruta>`; ese dia, inodo
`4117896` y `2` enlaces).

- **Al retirar el worktree solo se va el nombre.** Tras `git worktree remove` el `.env` de la
  raiz quedo con `1` enlace y el mismo inodo: el original no se toca.
- **Lo crea Felipe, no un agente.** El hook de secretos frena cualquier comando que nombre el
  `.env`, y con razon; el agente pide el comando y lo corre la persona.
- **Un limite que no se midio, pero se comprueba en un segundo:** un editor que reemplace el
  archivo en lugar de reescribirlo le da un inodo nuevo a la raiz y el enlace deja de
  compartir contenido. Si `stat` da inodos distintos, es una copia con otro nombre.
- **`CLAUDE.md` sigue diciendo `ln -s`.** Es archivo de reglas de Felipe y esta nota no lo toca:
  para leer el `.env` desde `docker compose` en el host el simbolico sirve; para el contenedor
  montado sobre el worktree, no.

Relacionada: [[suite-estatica-miente-en-worktree-secundario]] y
[[gate-que-mide-dos-arboles-a-la-vez]] (las dos tratan del mismo montaje, desde el lado
del gate); [[worktrees]] es el mapa del area.
