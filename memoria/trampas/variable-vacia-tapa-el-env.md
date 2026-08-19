---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-18
areas: [docker, worktrees]
fuente: medido en esta sesión al reparar el montaje del contenedor app, 2026-08-18
resumen: "Una variable inyectada VACÍA por compose cuenta como definida y Dotenv no la sobrescribe; y quitarla del bloque environment arregla la web y revienta el PHP de CLI, que no carga Dotenv"
---

# Una variable vacía tapa al `.env`, y quitarla rompe el CLI

Dos filos encadenados. El segundo solo aparece cuando arreglas el primero, y por eso este es el
orden en que muerden.

## Filo 1 — vacía no es lo mismo que ausente

`Dotenv::createImmutable()` **no sobrescribe lo que ya está en el entorno**, y una variable puesta a
**cadena vacía cuenta como puesta**. No es que falte el `.env`: es que hay una inyección vacía
tapándolo.

`docker-compose.yml` inyecta `DB_NAME: ${DB_NAME}` sin valor por defecto. Ejecutar `docker compose`
desde un worktree —que nace sin `.env`, porque está en `.gitignore`; el remedio es enlazarlo, no
copiarlo, y lo documenta [[CLAUDE]] (§Runtime & commands)— resuelve esa
interpolación a cadena vacía, y a partir de ahí el `.env` montado ya no puede rellenarla aunque sea
correcto y esté ahí mismo.

Comprobado dentro del contenedor, que es como se distingue de una teoría:

```
DB_NAME tras dotenv = []                             # inyectada vacía: Dotenv no la toca
MAIL_HOST tras dotenv = [smtp-relay.sendinblue.com]  # no inyectada: Dotenv sí la rellena
```

Esa asimetría es el diagnóstico entero. `MAIL_HOST` funciona **porque** compose no la toca.

Es el mismo mecanismo que ya estaba anotado en [[aislar-stack-docker-por-worktree]] por otra puerta:
allí las `DB_*` pasadas con `-e` llegaban **con las comillas dentro** (`'"root"'`) y ganaban al
`.env` por la misma regla, dando un `Access denied` que no se parecía a su causa. Mismo mecanismo,
tres caras: entrecomillada, vacía, y la de abajo.

## Filo 2 — el arreglo evidente rompe los tests

Visto lo anterior, la conclusión que se impone sola es: quita `DB_NAME`, `DB_USER` y `DB_PASS` del
bloque `environment:` y deja que la app las lea del `.env` montado, como ya hace con las `MAIL_*`.

**Es un error, y se cometió el 2026-08-18 llegando hasta `main`.**

`public/index.php` carga Dotenv. **Los `tests/test_*.php` no.** El PHP de línea de comandos toma esas
credenciales del bloque `environment:` y de ningún otro sitio — ya lo decía
[[servir-worktree-stack-efimero]]. Quitarlas deja:

| Camino | Resultado |
|---|---|
| Web (`/login`, puerta de servicio, `/proyectos`) | **200, todo verde** |
| `docker compose exec app php tests/test_global_table_safety.php` | `Access denied for user ''` |

La web en verde es lo venenoso: se verificó por navegador, salió bien, y se publicó. El camino roto
es justo el que `AGENTS.md` nombra como canónico para verificar.

## La forma del fallo, que es lo que hay que reconocer

Las dos mitades comparten firma: **el síntoma aparece lejos de la causa y solo en una de las dos
vías.** Una configuración correcta y presente que no se aplica; una suite rota mientras el navegador
dice que sí. Pariente de [[el-codigo-de-salida-se-pierde-en-la-tuberia]] y
[[cada-worktree-tiene-su-copia-congelada]]: instrumentos que contestan a una pregunta distinta de la
que creías hacer.

## Qué hacer

- **No quites `DB_NAME`/`DB_USER`/`DB_PASS` del `environment:`.** El comentario en
  `docker-compose.yml` lo dice y explica por qué; está puesto para frenar exactamente esta idea.
- **Enlaza el `.env` de la raíz en cada worktree nuevo** (enlace, nunca copia), como documenta
  `CLAUDE.md`. Eso es lo que hace que la interpolación resuelva bien desde cualquier sitio, y cubre
  a la vez al servicio `db`, que necesita `${DB_PASS}` para su healthcheck.
- **Al tocar configuración del contenedor, verifica las DOS vías**: navegador *y*
  `docker compose exec app php tests/test_*.php`. Una sola no cubre a la otra.
- Si algún día quiere hacerse independiente del bloque, lo que toca es que el CLI cargue Dotenv,
  no que la web deje de recibirlo. Es un cambio en `src/Core/Database.php` con plan y gate propios.

El montaje por ruta absoluta que se introdujo el mismo día —para que ejecutar compose desde un
worktree no secuestre qué código se sirve— **sí quedó en pie**; ver [[servir-worktree-stack-efimero]]
y [[aislar-stack-docker-por-worktree]].
