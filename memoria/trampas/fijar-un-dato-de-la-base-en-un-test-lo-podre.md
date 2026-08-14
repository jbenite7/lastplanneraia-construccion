---
tipo: trampa
estado: vigente
fecha: 2026-08-13
areas: [qa, lps, datos]
fuente: tests/browser/programacion-semanal-roles-phases.mjs, programacion_semanal del proyecto 68
resumen: derivar la semana y dejar fija la fila es la misma pudrición un nivel más abajo; la fila se elige por la precondición que el caso necesita, no por su nombre
---
`programacion-semanal-roles-phases.mjs` llevaba un comentario largo explicando por qué la semana
**no** se fija como constante: el proyecto sembrado avanza y un literal caduca. Y dos líneas más
abajo ataba la fila a `'Movilización general'` y `'Descapote'`.

Es el mismo error un nivel más abajo, y se cobró igual. Medido el 2026-08-13 sobre el proyecto 68:
esos dos nombres existen en las semanas 1-4, 10 y 11, pero con `Max_Semana=11` la semana histórica
que la prueba deriva es la 9 — donde no hay ninguno de los dos. El `find` devolvía `undefined` y el
caso moría en `expect(original).toBeTruthy()`. Lo mismo con una semana fija: el caso de CNP pedía la
semana 4 del proyecto «Prueba», que hoy solo tiene las semanas 1 y 2, y ni llegaba a la aserción.

**La fila se elige por la precondición que el caso necesita**, dentro de la semana ya resuelta: que
tenga responsables, que su compromiso sea mayor que cero, que la semana esté confirmada. Y si
ninguna fila la cumple, la prueba **falla diciendo qué precondición falta** — nunca se salta por
dato ausente, que sería tapar una regresión.

Regla corta: en un e2e contra datos vivos, lo único que se puede escribir a mano es la
**propiedad** que el caso necesita. Cualquier nombre, número de semana o identificador escrito en
el test es una fecha de caducidad que nadie apuntó.

Ver también [[el-dato-esta-en-desarrollo-y-el-permiso-en-el-stack-aislado]] y
[[condicion-de-hecho-caduca-sin-aviso]].
