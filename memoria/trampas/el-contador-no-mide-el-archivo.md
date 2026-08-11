---
tipo: trampa
estado: vigente
fecha: 2026-08-11
areas: [qa, proceso]
fuente: sesion-ejecucion
resumen: el recuento de hallazgos del hook de diseño sube y baja sobre un archivo que no cambia, así que no mide el archivo en ninguna dirección: mide el estado de la sesión que pregunta; antes de usar un número como indicador de avance, ejecútalo dos veces sobre el mismo contenido
---
**El recuento de hallazgos del hook de diseño no mide el archivo.** Mide el estado de la sesión que
pregunta, y por eso **sube y baja sin que lo contado cambie**.

Dos series medidas el 2026-08-11, ambas sobre archivos que no se tocaron entre lecturas:

- Sobre `public/css/handsontable-module.css`, a lo largo de tres frentes: **40, 38, 37, 22, 2 y 1**.
- Sobre `public/css/buttons.css`, en el frente `buttons-important-leyenda`: **137, 121 y 16** — y
  después, con el árbol ya limpio y publicado, **121 y 16 otra vez**. Es decir, **volvió a subir**.

**La causa, comprobada:** el hook guarda estado de sesión y **suprime lo que ya señaló**. Ejecutado
dos veces seguidas sobre el mismo contenido, la primera devuelve un número y **la segunda devuelve
vacío**. El total es un artefacto del historial de la sesión, no una propiedad del archivo.

> Esta página se llamaba `contador-que-baja-porque-ya-lo-miraste` y se corrigió el 2026-08-11, al
> aparecer la segunda serie. Aquel título era **direccional**: sugería un techo que se va limando,
> y de ahí se seguía la conclusión tranquilizadora de que basta con no leerlo dos veces. Es peor
> que eso. Un contador que sube y baja sin que cambie lo contado **no es un contador con memoria:
> es otra cosa con forma de contador**, y no sirve ni como cota superior.

Eso lo hace peor que un número equivocado. Un número equivocado se detecta al contrastarlo. Este
**tiene la forma de un dato reproducible** —sale de una herramienta, viene con líneas y reglas
concretas, y las que enseña son ciertas— pero el total no describe nada del archivo.

El daño real no fue el número: fue que se usó como **indicador de avance del programa de cierre** y
viajó en informes hacia arriba. Dos sesiones lo citaron como medición durante un día entero.

## Lo que sí es reproducible

- **Contar literales con `grep`** sobre el archivo. Determinista y comprobable a mano:
  ```bash
  grep -oE '#[0-9a-fA-F]{6}\b' <archivo> | wc -l     # hex
  grep -oE 'rgba?\([^)]*\)'    <archivo> | wc -l     # rgb/rgba
  grep -oE 'oklch\([^)]*\)'    <archivo> | wc -l     # oklch
  ```
  Ojo: cuenta también los que estén **dentro de comentarios**, que es lo que ya avisa
  [[audit-ve-color-en-comentarios]].
- **El audit del gate estático** (`npm run test:design-system:static`), que va contra
  `docs/design-system/exceptions.json` con topes por ruta. Ese sí es contractual y determinista, y
  es el que debe gobernar si algo pasa o no pasa.

## El criterio, que vale más allá de este hook

**Antes de usar un número como indicador de avance, ejecútalo dos veces sobre el mismo contenido y
comprueba que da lo mismo.** Si la segunda pasada no coincide con la primera, ese número no mide el
mundo: mide el estado de quien pregunta, y no sirve para decidir ni para informar.

Y no basta con comprobar que **baja poco**: la segunda serie subió. La pregunta no es «¿cuánto se
ha movido?», es **«¿se ha movido algo de lo contado?»**. Si la respuesta es no y el número cambió,
se descarta entero, en las dos direcciones.

Es la misma familia que [[medir-foco-de-teclado]] y [[captura-playwright-miente]]: herramientas que
responden algo cierto a una pregunta distinta de la que creías hacer. Y el mismo defecto de fondo
que [[gate-solo-cuenta-elementos-no-los-lee]] — contar no es leer.

## La variante que muerde al comparar: `git stash` con el árbol limpio

`git stash` sin cambios locales **no guarda nada y no falla**. Así que la maniobra habitual para
medir un antes —`git stash`, ejecutar, `git stash pop`— devuelve, cuando el trabajo ya está
commiteado, **una segunda medición del mismo árbol presentada como el «antes»**. Sale idéntica al
después, que es justo el resultado que uno esperaba, y confirma la hipótesis sin haberla probado.

Medido el 2026-08-11 en el frente `buttons-important-leyenda`: la comprobación de si un rojo de
`npm run check:frontend` era preexistente dio «863 errores antes y después» y **las dos cifras eran
del mismo código**. El `git stash pop` posterior avisó con un `No stash entries found` que es la
única señal que hubo — y llega al final, cuando el número ya está anotado.

Lo que sí compara: reponer el archivo desde el sha base (`git checkout <sha> -- <ruta>`), medir,
y devolverlo con `git checkout HEAD -- <ruta>`.

El patrón común con el hook: **un instrumento que ante «no hay nada que hacer» devuelve algo con
forma de resultado.** El hook devuelve un total menor; `git stash` devuelve el presente disfrazado
de pasado.
