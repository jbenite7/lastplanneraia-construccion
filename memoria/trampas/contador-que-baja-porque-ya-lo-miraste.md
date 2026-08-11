---
tipo: trampa
estado: vigente
fecha: 2026-08-11
areas: [qa, proceso]
fuente: sesion-ejecucion
resumen: el recuento de hallazgos del hook de diseño suprime lo ya señalado y devuelve vacío en la segunda pasada, así que baja porque ya lo miraste; antes de usar un número como indicador de avance, ejecútalo dos veces sobre el mismo contenido
---
El 2026-08-11, a lo largo de tres frentes sobre `public/css/handsontable-module.css`, el hook de
diseño reportó **40, 38, 37, 22, 2 y 1** hallazgos. El archivo no cambió acorde: entre varias de
esas cifras no se tocó una línea.

**La causa, comprobada:** el hook guarda estado de sesión y **suprime lo que ya señaló**. Ejecutado
dos veces seguidas sobre el mismo contenido, la primera devuelve un número y **la segunda devuelve
vacío**. El recuento no describe el archivo: describe cuántas veces se ha disparado antes.

Eso lo hace peor que un número equivocado. Un número equivocado se detecta al contrastarlo. Este
**tiene la forma de un dato reproducible** —sale de una herramienta, viene con líneas y reglas
concretas, y las que enseña son ciertas— pero el total es un artefacto del historial de la sesión.
Es un contador que baja porque ya lo miraste.

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

Es la misma familia que [[medir-foco-de-teclado]] y [[captura-playwright-miente]]: herramientas que
responden algo cierto a una pregunta distinta de la que creías hacer. Y el mismo defecto de fondo
que [[gate-solo-cuenta-elementos-no-los-lee]] — contar no es leer.
