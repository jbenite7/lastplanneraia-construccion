---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-09-29
areas: [design-system, qa]
fuente: medido en sesion (revision visual de Programa General, PR #89)
resumen: "Las hojas del design system se enlazan con ?v=1.1.0 y ese sello no cambia al editarlas: el navegador sigue con la copia vieja tras una recarga normal, y se mide una hoja que ya no existe"
---

**El sintoma.** Cambias un archivo de `public/css/design-system/`, recargas la pagina y lo que
mides con `getComputedStyle` es el valor **de antes**. Mas de una vez ese dia (2026-09-29) el
resultado parecia decir que el arreglo no funcionaba, cuando la hoja ni siquiera se habia
vuelto a descargar.

**La causa.** Las hojas del design system se enlazan con un sello fijo, del estilo
`aia-design-system.css?v=1.1.0`. El sello lo mueve una publicacion de version, no cada edicion
(ver [[version-escrita-a-mano-rompe-el-bump]]), asi que durante el trabajo la URL no cambia y el
navegador reutiliza la copia que ya tiene. Los archivos que genera Vite para la SPA
(`public/app/assets/index-<hash>.css`) no sufren esto: el hash cambia con el contenido.

**Lo que enganio.** Medir sin sospechar. Un valor viejo se parece mucho a "el CSS no aplica" o
a "otra capa lo pisa" ([[css-layer-cascade]], [[important-invierte-el-orden-de-capas]]), y se
puede perder una vuelta buscando una cascada que no tiene la culpa.

**El remedio, en orden de menos a mas comodo:**

1. Pedir de nuevo cada hoja saltandose la cache y **luego** recargar. Desde la consola o desde
   una herramienta de navegador:

   ```js
   await Promise.all(
     [...document.querySelectorAll('link[rel=stylesheet]')].map((l) => fetch(l.href, { cache: 'reload' })),
   );
   location.reload();
   ```

2. Recarga forzada del navegador, o "Disable cache" en las herramientas de desarrollo con estas
   abiertas.

**Como saber que ya midio la hoja nueva.** Antes de creer un valor, comparalo con el que dice el
archivo en disco (`grep` de la regla). Si no coinciden, la que manda es la copia vieja: se
repite el remedio, no el arreglo. Los tests de Node y de Vitest no pasan por el navegador y no
sufren esto; el problema es de la verificacion visual.

Relacionada: [[el-dom-dice-que-existe-no-que-se-ve]] y [[captura-playwright-miente]] (otras dos
formas de medir algo que no es lo que crees); [[design-system]] es el mapa del area.
