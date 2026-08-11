---
tipo: trampa
estado: vigente
fecha: 2026-08-11
areas: [qa, design-system]
fuente: sesion-vocabulario-estados-cascada
resumen: "querySelector encuentra igual de bien un elemento dentro y fuera del viewport, asi que una afirmacion sobre lo que el usuario ve no se sostiene leyendo el DOM: se comprueba con getBoundingClientRect() contra el ancho del viewport, y siempre nombrando la resolucion"
---
**El DOM te dice qué existe, no qué se ve.** Un `querySelectorAll` devuelve un elemento situado en
`x=1332` con la misma naturalidad que uno en `x=86`, y la lectura no lleva ninguna marca de que el
primero esté fuera de la pantalla. Si de esa lectura sale una afirmación sobre lo que **el usuario
ve**, la afirmación no está medida: está supuesta.

Medido el 2026-08-11 en `/programacion-intermedia`, viewport canónico 1180×820. El botón de filtro
de la leyenda está en `x=86` y la celda `ops-state-chip` del mismo estado en `x=1332`: **no coexisten
a la vista** sin desplazar la tabla en horizontal. De ahí salió la frase «se contradicen en la misma
pantalla», escrita a partir de un `querySelectorAll` que encontraba las dos cadenas y las daba por
visibles.

Lo que sí comprueba la pregunta: `getBoundingClientRect()` contra `window.innerWidth`, o
directamente una captura. Y **nombrando la resolución**, porque «visible» sin resolución no
significa nada — lo que se sale a 1180 puede caber a 1600.

## Marca también dónde la afirmación sí valía

En el mismo módulo, el modal de guía operativa **se abre encima de la leyenda**, así que ahí la
contradicción entre los dos nombres **sí era** de vista simultánea. El defecto de fondo —tres
superficies nombrando distinto el mismo `key`— era real en los tres sitios, y el recuento del
vocabulario que lo cuantificó se hace **contando términos, no co-visibilidad**, así que no dependía
de esto. Solo el «a la vez» era falso, y solo en uno de los tres casos.

Distinguir eso importa: una trampa que solo cuenta el error enseña a desconfiar de todo, y entonces
no se usa.

## Lo que la vuelve cara: viajó por tres sesiones

La premisa no falló una vez. La midió una sesión, la reenvió la coordinadora al archivar el frente
del término duplicado —su motivo escrito fue que eran «los mismos elementos» en pantalla— y la
heredó una tercera, que la repitió en un spec y en el mensaje de un commit. **Ninguna de las tres la
comprobó**, hasta que alguien miró los límites del viewport.

Y la conclusión aguantó igual: había que fundir los términos, pero **porque el segundo iba a fijar
vocabulario sin saberlo**, no por compartir píxeles. Es decir, se acertó por casualidad, que es
justo lo que impide que el error se note.

De ahí lo que hay que llevarse, y es lo mismo que dice `docs/coordinacion-sesiones.md` con
*quien la repite es quien la afirma*: **una medición mal leída se propaga sin resistencia mientras
su conclusión siga pareciendo correcta.** La prosa que suena bien es la que nadie relee.

Prima de [[valor-declarado-no-es-valor-computado]] —las dos son leer un instrumento que responde a
otra pregunta—, pero aquella es de valores CSS y esta de geometría. Ver también
[[captura-playwright-miente]] y [[aislar-stack-docker-por-worktree]], donde el instrumento correcto
apuntaba al árbol equivocado.
