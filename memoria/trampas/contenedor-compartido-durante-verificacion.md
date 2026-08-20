---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-19
areas: [proceso, docker, worktrees, qa]
tags: [trampa]
fuente: frentes ds-f1a-estado y estados-fuera-de-ventana, 2026-08-19
resumen: "El contenedor `app` es uno solo para todo el repo: puede estar sirviendo el worktree de otra sesión, y entonces tu verde no es tuyo"
---

# El contenedor es uno solo, así que tu verde puede ser de otro árbol

**El síntoma.** Dos formas, y la segunda es la peligrosa:

1. Una suite falla con algo que no tiene que ver con tu cambio:

```
Error: Command failed: docker compose exec -T app php -r …
service "app" is not running
```

2. **O peor: pasa en verde y las cifras no cuadran.** Un conteo de pruebas que no sube al añadir
   un caso, un archivo que "no existe" para el runner, un baseline que no refleja lo que acabas de
   escribir.

**Lo que parece.** En el primer caso, que rompiste el entorno. En el segundo —y este es el que
cuesta caro— **que todo va bien**.

**Lo que es.** `docker-compose.yml` fija `name: last-planner-aia`, así que **hay un solo contenedor
`app` para todo el repositorio**, y `docker-compose.override.yml` lo monta desde
`${LPS_CODE_ROOT:-<checkout principal>}`. Cualquier sesión puede reapuntarlo a su worktree, y
mientras tanto **todo `docker compose exec app` de todas las demás mide el árbol de esa sesión**.

Los dos incidentes del 2026-08-19, con el mismo mecanismo:

- **Recreación a mitad de suite.** `foundation.test.mjs:273` falló con `service "app" is not
  running` durante una re-verificación previa a publicar. El contenedor llevaba **41 segundos
  arriba** cuando se miró: otra sesión lo estaba recreando. No había ningún defecto.
- **Verde ajeno, sin aviso.** `run-php-tests.php --nivel=puro` devolvió `RC=0` y
  `OK (18 tests, 41 assertions)` con el contenedor sirviendo `reverent-golick-aaf932`. La prueba
  recién escrita **no se ejecutó**, ni esa vez ni la siguiente; los 18 tests eran de la única clase
  que existía en aquel árbol. Lo delató que el conteo **siguiera en 18** al añadir un caso que
  debía subirlo a 21. Si el número hubiera coincidido por casualidad, nadie se entera.

**Cómo se sale.** Comprobar el montaje **antes** de aceptar cualquier resultado, en verde o en rojo:

```bash
docker inspect $(docker compose ps -q app) \
  --format '{{range .Mounts}}{{if eq .Destination "/var/www/html"}}{{.Source}}{{end}}{{end}}'
docker compose ps app          # si lleva segundos arriba, fue una carrera: repite
```

Si no devuelve tu worktree, ese resultado **no mide tu trabajo**. Dos salidas:

- **Apuntar el contenedor aquí** (`LPS_CODE_ROOT="$(pwd)" docker compose up -d app`) — pero eso se
  lo quitas a quien lo esté usando, así que se pide antes y **se devuelve al terminar**.
- **Comprobar que da igual**: si tu árbol y el montado son idénticos en lo que el PHP lee
  (`git diff --name-only <sha-montado> HEAD` filtrado a `public/`, `src/`, `views/`, `admin/`), el
  resultado vale. Sirve para publicar; **no sirve para ejecutar pruebas nuevas**, que por
  definición no existen en el otro árbol.

## La salida que evita casi todos estos turnos: el contenedor efímero

Adoptado el 2026-08-19 tras cuatro incidentes en una jornada. Para ejecutar algo contra **tu propio
árbol** sin tocar el contenedor compartido:

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php <script>
```

Monta tu worktree, alcanza la misma base de datos, y **no le quita el contenedor a nadie**. El
compartido solo se reapunta —con ventana coordinada— para lo que de verdad lo exige: el invariante
de `scripts/publicar.sh` y la verificación en navegador.

### La frontera NO es «línea de comandos contra navegador»

Es **«¿necesita Apache arriba?»**, y la diferencia tiene un contraejemplo que engaña:
`scripts/run-php-tests.php --nivel=http` es un comando de terminal y **no funciona en efímero**,
porque su comprobación previa hace `file_get_contents('http://127.0.0.1/login')` **desde dentro del
contenedor** (`scripts/run-php-tests.php:268`) y `--no-deps` no levanta el servidor web. Fallaría
por el método, no por el código — un rojo que no dice nada.

Quien aplique la regla por la etiqueta «es CLI, va en efímero» se llevará ese rojo y perderá un rato
buscándolo en su diff. La pregunta correcta antes de elegir es: **¿esto necesita que la aplicación
responda por HTTP?** Si sí, ventana. Si no, efímero.

**Cuánto costó.** El 2026-08-19: veinte minutos en el primer incidente, y en el segundo dos
ejecuciones completas de una tarea que se creyó verificada y no lo estaba. **La trampa estaba
escrita desde la mañana de ese mismo día y mordió igual por la tarde**, lo que dice dónde tiene que
vivir la comprobación: en el procedimiento —un paso 0 del plan— y no en la memoria de quien ejecuta.

Relacionadas: [[publicar-sh-choca-con-dos-worktrees-verificando]] · [[suite-estatica-miente-en-worktree-secundario]]
