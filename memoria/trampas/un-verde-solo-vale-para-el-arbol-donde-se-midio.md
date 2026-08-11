---
tipo: trampa
estado: vigente
fecha: 2026-08-10
areas: [qa, worktrees, design-system]
fuente: tests/design-system/foundation.test.mjs, docker-compose.yml, scripts/wiki-lint.mjs, auditoría cruzada del 2026-08-10
resumen: "Dos gates dieron verde y rojo a la vez el mismo día sobre el mismo commit: uno leía archivos que no viajan en git, el otro mezclaba el árbol local con el contenedor del árbol principal"
---
# Un verde solo vale para el árbol donde se midió

El 2026-08-10 la sesión coordinadora informó de que el lint de la wiki estaba **verde** y la sesión
del Frente 1 midió que estaba **rojo**, sobre el mismo commit de `origin/main`. Las dos tenían
razón, y ninguna describía el repositorio.

Es la misma causa en dos gates distintos: **el resultado depende del árbol donde se ejecuta, y nadie
lo declaraba.**

## Caso 1 · El lint de la wiki lee archivos que no viajan en git

`memoria/goals/estado.md` enlazaba dos veces `[[goals/adopcion-logo-construccion/goal]]`, y esa
carpeta está en `.gitignore`.

- En el árbol del coordinador la carpeta **existe en disco**: el enlace resuelve y sale `exit 0`,
  «sin hallazgos».
- En un worktree limpio **no existe**: el linter cae a resolver por nombre de archivo, encuentra los
  25 `goal.md` del repo y sale `exit 1` con dos hallazgos «ambiguo».

Arreglado en `7c1c5d95` pasando los dos wikilinks a código en línea, que es lo que son. **El patrón
sigue vivo:** cualquier enlace de la wiki a algo ignorado por git repite el fallo.

## Caso 2 · La suite estática mezcla dos árboles

`tests/design-system/foundation.test.mjs:10-22` lee fechas de archivo **con Node, en el árbol donde
corre**, pero ejecuta el PHP con `docker compose exec app`. Y `docker-compose.yml:1` fija
`name: last-planner-aia`, así que ese `exec` resuelve **siempre** al contenedor del árbol principal.

Desde un worktree, la prueba compara las fechas de un árbol con los datos de otro. Daba 7/8 con
`entrypoint … is older than tokens …`; apuntando el compose al contenedor propio, 8/8.

**No era una regresión: el gate medía el árbol equivocado.** Y desde que cada sesión de ejecución
trabaja en su propio worktree, esa es la situación normal, no la excepción. Afecta a **toda** prueba
que mezcle lectura de archivos en Node con ejecución en el contenedor.

## Cómo evitarlo

- **Un gate debe declarar sobre qué árbol se pronuncia**, y fallar si no puede garantizarlo. Un
  resultado que cambia según quién lo corre no es un resultado.
- **Nada que el gate lea puede estar fuera de git**, o el gate mide la máquina y no el repositorio.
- **Al leer una salida, mira el código de salida, no solo el texto.** El lint imprime «2 hallazgos en
  104 páginas» y termina sin escribir «FAIL»; leerlo por encima da la impresión contraria a la real.
- **Cuando dos sesiones se contradigan sobre un mismo commit, la respuesta casi nunca es que una se
  equivocó**: es que están midiendo cosas distintas. Buscar la diferencia de entorno antes de buscar
  el error.

Relacionado: [[suite-estatica-miente-en-worktree-secundario]],
[[gate-solo-cuenta-elementos-no-los-lee]], [[suite-estatico-mide-dos-arboles]].
