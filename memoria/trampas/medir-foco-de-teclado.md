---
tipo: trampa
estado: vigente
fecha: 2026-08-11
areas: [qa, design-system]
fuente: sesion-ejecucion
resumen: medir un estado :focus-visible con element.focus() desde la consola lee el estado de reposo y hace concluir que la regla está muerta; el foco hay que dejarlo en el control anterior y pulsar un Tab físico
---
Medido el 2026-08-11 en el frente `focus-visible-verde`, al comprobar el contraste del disparador
del cajón LPS. Estuve a punto de concluir que la regla `:hover, :focus-visible` de
`public/css/handsontable-module.css` **no se aplicaba a 1180px**, y era falso: la regla está viva y
la medición era la rota.

**Lo que falla.** `elemento.focus()` desde la consola deja `elemento.matches(':focus-visible')` en
`true`, así que parece que basta. Pero entre esa llamada y la lectura del estilo computado se cuela
cualquier otra llamada de herramienta —una captura, una pulsación de tecla, un `screenshot`— y
**el foco se va a otro sitio**. Lo que acabas leyendo es el estado de reposo. Peor: si además hay
una `transition` en `background`, una lectura demasiado temprana devuelve un valor intermedio, así
que el mismo comando da tres resultados distintos según cuándo se mire, y ninguno es el bueno.

El síntoma con el que se destapa es inconfundible: `:focus-visible` verdadero pero
`transform: none` cuando la regla declara un `scale`. Si el `transform` de la regla no está, la
regla no se aplicó, y ninguna de las demás propiedades que leas de ahí sirve.

**El procedimiento que sí funciona:** localizar el control **anterior** en orden de tabulación,
enfocarlo, y pulsar **un `Tab` físico**. Eso deja el foco donde quieres, con modalidad de teclado
real, y `document.activeElement === elemento` lo confirma antes de leer nada. Comprobar siempre las
dos cosas juntas —`activeElement` y `:focus-visible`— y esperar a que la transición termine.

**Segundo filo, en la misma medición:** el disparador es `position: fixed`, y para un elemento
fijo **`offsetParent` es `null`**. Filtrar la lista de enfocables con `offsetParent !== null`, que
es el idioma habitual para «visible», lo deja fuera y devuelve índice `-1`. Filtrar por
`getBoundingClientRect()` con ancho y alto mayores que cero, más `visibility`.

**Tercer filo, al documentar:** una captura de pantalla del estado enfocado **no sirve como
evidencia**, porque el propio disparo de la captura roba el foco y el anillo desaparece de la
imagen. Lo reproducible son los valores computados; la imagen hay que grabarla de otra forma si de
verdad hace falta.

Relacionadas: [[captura-playwright-miente]] y [[aislar-stack-docker-por-worktree]] —esta última
porque en el mismo frente la otra mitad del problema era **contra qué árbol** se medía.
