---
tipo: trampa
estado: vigente
fecha: 2026-08-13
areas: [docker, worktrees, qa]
fuente: sesión del 2026-08-13, cobertura de la composición de LpsWeekEditPolicy
resumen: un contenedor ya en marcha conserva el bind mount con el que se creó, así que `docker compose exec` desde un worktree puede ejecutar el código del working tree principal y dar un verde falso
---
`docker compose config` desde un worktree declara ese worktree como `source` del bind mount —lo
resuelve contra el directorio actual—, pero **el contenedor en marcha conserva el montaje con el
que fue creado**. Si el stack se levantó desde el working tree principal (que es lo normal, ver
[[aislar-stack-docker-por-worktree]] y [[dos-stacks-docker]]), un `docker compose exec app php
tests/…` lanzado desde el worktree ejecuta **el archivo del repo principal**, no el que acabas de
editar.

El síntoma es el peor posible: **verde**. El test existe en las dos copias y ambas imprimen lo
mismo, así que nada delata la sustitución. El 2026-08-13 esto hizo pasar tanto un test nuevo que
el contenedor no podía ver como una **prueba de mutación** —romper la regla a propósito y esperar
rojo— que siguió en verde precisamente porque medía el código intacto del otro árbol. Sin la
mutación, el falso verde habría viajado entero al informe.

**Cómo detectarlo, en una línea:** pregúntale al contenedor por algo que solo exista en tu copia.

```bash
docker compose exec app grep -c "<simbolo-que-acabas-de-escribir>" <archivo-que-editaste>
```

Un `0` significa que estás midiendo otro árbol. `docker compose config | grep -A3 volumes` **no
sirve** para esto: muestra lo declarado, no lo montado.

**Cómo ejecutar de verdad tu worktree sin tocar el stack ajeno** — un contenedor efímero con tu
copia montada aparte:

```bash
docker compose run --rm --no-deps -v "$PWD:/wt" -w /wt app php tests/test_x.php
```

`--no-deps` evita arrastrar `db`, y `--rm` no deja rastro. Para pruebas de navegador no basta,
porque el e2e pega contra `localhost:8081`, que sirve el contenedor compartido: ahí hay que
recrear el `app` desde el worktree (`docker compose up -d --force-recreate --no-deps app`) y
**devolverlo al repo principal al terminar**, o montar un stack propio con la receta de
[[aislar-stack-docker-por-worktree]].

Ver también [[cada-worktree-tiene-su-copia-congelada]], que es el mismo error un piso más abajo
(dependencias), y [[captura-playwright-miente]], otro caso de evidencia que parece medir lo que
no mide.
