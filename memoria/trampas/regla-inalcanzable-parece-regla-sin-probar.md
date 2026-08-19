---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-13
areas: [qa, lps]
fuente: sesión del 2026-08-13, red de caracterización F2a-2b-1 (commits 0e909227..d1b7b1a7)
resumen: una regla del cliente puede ser incomprobable porque el servidor filtra el caso que la activa; distinguir «no hay datos» de «no puede haberlos» antes de dejar un test saltado
---
Al construir la red de caracterización de las 22 reglas de habilitación de Programación
Semanal e Intermedia, la regla **I3** —«las filas cabecera no son editables»— no se pudo
ejercitar: la grilla no traía ni una cabecera. La reacción barata habría sido `test.skip`
con el motivo «el proyecto sembrado no tiene cabeceras», y habría sido **falso**.

La causa medida no son los datos. El listado del servidor filtra `Titulo = 0`
(`src/Controllers/Programacion/ProgramacionIntermediaController.php:182`), y una fila
cabecera es exactamente `Titulo != 0` (`public/js/modules/programacion_intermedia/stateMachine.js:161-167`).
En la base local hay 24 cabeceras en `programa` para ese proyecto: existen, pero **no
pueden llegar a la grilla**. La rama `meta.isHeader` de `buildPICellProperties()`
(`hot.js:956-961`) es, desde esta vista, código inalcanzable.

La diferencia importa porque las dos situaciones piden cosas opuestas:

| Lo que parece | Qué hacer |
|---|---|
| No hay datos sembrados para el caso | Sembrarlos, o elegir otro proyecto/semana. La regla **sigue sin cubrir**. |
| El caso no puede ocurrir por diseño | Fijar **el hecho que lo impide** con una prueba. Si el filtro desaparece, esa prueba se pone roja y obliga a cubrir la regla de verdad. |

Aquí se hizo lo segundo: `tests/browser/programacion-intermedia-enablement.mjs` comprueba
que **cero** filas cabecera llegan a la grilla, con un mensaje que dice qué hacer si algún
día llegan. Un `skip` habría dejado la impresión de una regla probada a medias; esto deja
escrito que hoy no gobierna nada.

**El criterio, generalizado:** antes de saltar una prueba por falta de un caso, busca quién
lo impide. Si es el propio producto, el hallazgo es que la regla está muerta en esa vista —y
eso vale más que la prueba que ibas a escribir. Ver también
[[gate-solo-cuenta-elementos-no-los-lee]] y [[golden-huerfano-no-lo-ve-ningun-gate]]: el
mismo patrón de algo que parece vigilado y no lo está.

Los otros dos hallazgos de la misma red, por si se cruzan con ellos:

- **`Ejecutado_Real` ignora la restricción de semana histórica** en Semanal: su cláusula
  tiene un `return` propio *antes* de `isUserAllowedToEdit()`
  (`programacion_semanal/hot.js:416-418`), así que en fase de calificación un rol `R` edita
  el avance de una semana que no puede tocar en ninguna otra columna. **Revisado el mismo
  día: es deliberado y el servidor implementa la misma regla** — ver
  [[un-if-de-autorizacion-no-es-toda-la-autorizacion]].
- **`editableProps` declara nueve props y la grilla monta ocho**: `Descripcion` no está en
  el array `columns`, así que esa entrada no gobierna ninguna celda.

Detalle completo y tabla de cobertura de las 22 reglas en
[[docs/superpowers/reports/2026-08-13-f2a-2b-1-red-de-pruebas-habilitacion|el informe de cierre]].
