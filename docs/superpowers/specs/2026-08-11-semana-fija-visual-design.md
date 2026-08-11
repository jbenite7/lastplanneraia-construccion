# Fijar la semana en la prueba visual de Programación Intermedia — diseño

- Fecha: 2026-08-11
- Frente: `semana-fija-visual` (sesión de ejecución 06e4383d)
- Sha de arranque: `6dd69bb7`
- Origen: D-CERO-1, decidida por el usuario tras el frente `contadores-cero`

## El problema, y por qué no es «el golden está viejo»

`tests/browser/programacion-intermedia.visual.mjs` compara contra una imagen guardada. Esa imagen
retrata «Semana 1»; la corrida de hoy dice «Semana 4». **La prueba falla por algo que no mide.**

La razón del usuario para arreglarlo, y no para dejarlo: *una alarma que suena siempre es una que
nadie mira*. El coste real no es el rojo: es que un rojo permanente entrena a ignorar el gate, y el
día que la captura detecte una regresión de verdad nadie la distinguirá del ruido de siempre.

**Y eso no es una hipótesis: ya había pasado, medido en este frente.** Al fijar la semana y volver
a comparar, el diff se redujo a 7395 píxeles y aparecieron debajo **dos cambios reales que llevaban
sin retratarse desde el 2026-08-07**: el botón «Restricción Compartida» pasando a acción primaria
(`b647499d`) y la etiqueta «Inicio Vencido» → «Inicio vencido» (`db8a1e6b`). Nadie los vio **porque
el rojo de la semana los tapaba**.

La conclusión es más fuerte que «nadie mira una alarma que suena siempre»: **una alarma que suena
siempre oculta las alarmas de verdad que suenan debajo**. El gate no estaba solo siendo ignorado —
estaba siendo inútil mientras parecía activo.

## La causa está en el escenario, no en la imagen

El número de semana **no lo controla el test**. Se renderiza en servidor:
`views/partials/shell_sidebar.php:24` hace
`$shellSemana = (int) ($semana ?? ($_SESSION['semana'] ?? 0))`, y esa sesión la fija el uso normal
de la aplicación. El mock del test (`page.route` sobre `/api/pi/list` y `/programacion-intermedia/filtros`)
no la alcanza: intercepta llamadas del cliente, y esto llega ya pintado en el HTML.

Por eso **se fija la semana en el fixture, no en el golden**. Regenerar la imagen con «Semana 4»
la haría coincidir hoy y volvería a derivar en cuanto el proyecto avance de semana — el mismo
fallo, aplazado. Y encima dejaría el baseline dependiendo del calendario.

## Cómo se fija

Existe una vía legítima y ya soportada: `POST /context/week`
(`src/Controllers/Core/ContextController.php:9`, ruta en `public/index.php:304`). Solo exige sesión
autenticada —no CSRF—, así que el test puede fijarla después de `loginAndSelectProject` y antes de
navegar, con el contexto de petición ya autenticado de Playwright.

Se fija **la semana 1**, que es la que el golden retrata. Así el escenario pasa a controlar la
variable y la comparación depende solo del diseño.

## La segunda zona del diff: «Restricción Compartida». No se decide aquí

El diff tenía **dos** zonas rojas, y la segunda no está diagnosticada. Lo que se sabe:

- Su marcado es **estático** (`views/…/programacion_intermedia.view.php:50`,
  `class="aia-btn aia-btn--primary"`): no hay condicional que dependa de la semana ni del estado.
- El golden se recapturó el **2026-08-07** (`11b8d93c`); desde entonces hay varios cambios en
  `public/css/design-system/` y en `tokens.css`, pero **ninguno que toque `aia-btn--primary`
  directamente** — buscado, no supuesto.

O sea: **no está establecido que derive por la misma causa que la semana**. Y el experimento que lo
resuelve solo es válido **después** de fijar la semana, porque hasta entonces el diff tiene dos
variables a la vez. Por eso este frente **no toca ese botón**: si al fijar la semana su zona
persiste, se **encola con la evidencia**, no se arregla de paso. Meter dos problemas en un frente
de uno es cómo se acaba sin saber cuál de los dos arreglos funcionó.

## Los goldens solo se regeneran si el diff queda explicado

La aprobación del usuario para regenerar sigue en pie, con su condición intacta: **se le enseña el
antes y el después, en las dos resoluciones, antes de fijar los `sha256`**. Y con un límite que
viene de él: *cubre un cambio comprendido, no un diff limpio*. Si tras fijar la semana quedan
diferencias que no se sepan explicar, **se para otra vez**.

## La prueba tiene que saber fallar

Un golden regenerado que ya no detecta nada es peor que el que había. Antes de dar el frente por
cerrado se introduce un cambio visible a propósito y se comprueba que la captura nueva **lo caza**;
después se revierte. Se entrega con la salida de esa corrida, no con la promesa de haberlo hecho.

**Y la primera mutación que se probó enseñó que no vale cualquiera.** Añadir una letra a
«Seleccionar visibles» hizo fallar 1180×820 (1649 px) y **pasar** 1440×900. La causa no es que el
golden ancho sea ciego: en ese ancho ese botón queda **último de su fila**, así que ensancharlo no
desplaza nada detrás y el cambio se queda por debajo del `maxDiffPixels: 100`; a 1180×820 el mismo
botón va en la segunda fila y arrastra todo lo que le sigue.

Es decir: **una mutación que se apoya en el reflujo de la fila no prueba nada en el viewport donde
ese elemento no reflúe.** La mutación válida se hizo sobre una etiqueta de la leyenda, que arrastra
su fila en ambos anchos: 1180×820 y 1440×900 fallaron los dos (4031 px en el ancho). Con una sola
resolución roja se habría dado por demostrado algo que en la otra no lo estaba.

### La mutación que más vale: ¿el arreglo es la causa?

Cambiar un texto y ver el rojo demuestra que la comparación de píxeles funciona — algo que ya se
sabía. La pregunta buena es otra: **¿este golden vigila la pantalla que dice vigilar?** Su problema
nunca fue fallar, sino ser inútil mientras el ruido lo tapaba, y una comparación que siempre falla
y una que compara lo que no toca son igual de ciegas.

Así que se quitó la llamada a `fijarSemanaDelEscenario` y se volvió a correr: **`2 failed`, con el
rojo únicamente en la esquina del selector de semana** —369 px, ni un píxel en el resto—. Es la
reproducción exacta del fallo original. Si sin la llamada hubiera seguido en verde, la semana la
estaría fijando otra cosa y este arreglo no sería la causa de nada.

Con la llamada restaurada: `2 passed`. Solo con esas dos corridas puede decirse que el golden
**vigila**, y no únicamente que coincide.

## Alcance

Este golden vive en el gate `runtime`, declarado `blocked` por otras causas. **No se pretende
ponerlo verde**: no es de este frente. La condición de hecho es que esa prueba **deje de fallar por
una razón que no mide nada** y que lo que quede del diff sea real.

No se toca `views/programacion-intermedia/programacion_intermedia.view.php` ni el CSS de la
leyenda: el frente `vocabulario-estados-cascada` está a punto de escribir ahí y publica primero.
Si el trabajo acabara necesitándolos, se para y se avisa.

## Condición de hecho

1. La semana del escenario la fija el test, no el estado del proyecto.
2. La zona del selector de semana desaparece del diff.
3. Lo que quede del diff está explicado, o encolado con evidencia si no lo está.
4. Si se regeneran los goldens: antes/después enseñados antes de firmar, y **mutación ejecutada**
   que demuestre que la captura nueva sabe fallar.
