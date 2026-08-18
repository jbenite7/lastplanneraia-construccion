---
tipo: trampa
estado: vigente
fecha: 2026-07-29
areas: [design-system, qa]
fuente: memoria-claude
origen: lps-aia-audit-ve-color-en-comentarios
resumen: "Un gate que lee el texto crudo de un archivo mide el texto, no el hecho: el audit del DS cuenta hex escritos en comentarios CSS, y el contrato de login contaba menciones del script en vez de cargas"
---
# Un gate que lee texto crudo mide el texto, no el hecho

Varios gates de este repo comprueban un hecho del código —«este color no está hardcodeado», «este
script se carga una vez»— buscando un patrón sobre el **texto crudo del archivo**. El texto crudo
incluye los comentarios, y un comentario que *habla* del hecho es indistinguible del hecho.

El síntoma siempre es el mismo y siempre desconcierta: **el gate se pone rojo al documentar**, sin
que cambie nada de lo que dice vigilar.

El nombre de esta nota describe la primera vez que ocurrió; el mecanismo es más ancho.

## Instancia 1 — el audit del DS y los colores (2026-07-29)

`scripts/design-system-audit.mjs` cuenta `hardcoded-hex` y `hardcoded-color-function` sobre el texto
crudo, no sobre las declaraciones parseadas. Un `#8f1d1d` o un `rgba(26, 86, 51, 0.12)` escrito
dentro de un `/* comentario */` **cuenta como infracción**. El filtro de comentarios que tiene el hex
solo mira los 8 caracteres previos (`content.slice(Math.max(0, match.index - 8), match.index)`,
`scripts/design-system-audit.mjs:269`), así que no salva un bloque de comentario largo, y el de
funciones de color no filtra nada.

Ocurrió 3+1 veces seguidas al documentar la tokenización de `public/css/pdc.css`.

Seguro por construcción, en cambio: `color-mix(...)` y `oklch(from var(--x) ...)` **no** los cuenta
`colorFunctionPattern` (`scripts/design-system-audit.mjs:32`), y conviene saber por qué, porque son
dos razones distintas: `color-mix` **no está en la lista de nombres** —el patrón exige `color(`, y
ahí viene un guion—, mientras que `oklch(from var(--x) ...)` sí está en la lista pero no arranca con
un valor numérico. Ojo con la versión corta de esa regla: no basta con «exige un dígito tras el
paréntesis», porque el patrón acepta también `palabra + dígito`, y por eso `color(display-p3 0.5 0 0)`
**sí** cuenta. Comprobado ejecutando el patrón sobre los cinco casos (2026-08-18).

## Instancia 2 — el contrato de login y el script (2026-08-18)

`tests/test_login_design_system_contract.mjs` exigía que `views/auth/login.view.php` cargara
`AiaAlertInterceptor.js` exactamente una vez, y lo comprobaba contando el nombre del archivo en todo
el texto: `login.match(/AiaAlertInterceptor\.js/g).length === 1`. Cuando `10d072cd` añadió un
comentario PHP explicando por qué jQuery sigue siendo local —y el comentario nombra al interceptor,
porque es quien lo necesita—, el test pasó a `2 !== 1` con la vista cargando el script **una sola
vez**. Quedó rojo en `main` **doce días** —el comentario entró el 2026-08-06 y se arregló el
2026-08-18—, y lo destapó una sesión que iba a otra cosa.

Se arregló anclando el patrón a la etiqueta (`<script ... src="...AiaAlertInterceptor.js`), no al
nombre suelto.

## Why

El reflejo natural al tocar código gobernado por un gate es **dejar escrito de qué se venía y por
qué**, y eso es justo lo que rompe estos gates. Se paga una vuelta entera de verificación por cada
comentario, y peor: cuando el rojo sobrevive al frente que lo causó, el siguiente que llega no sabe
si es suyo (ver [[branch-preexisting-red-gates]]).

Hay además una salida falsa muy tentadora: **reformular el comentario para contentar al gate**. Deja
el gate igual de ciego y la trampa armada para el próximo que nombre la cosa en prosa, además de
empeorar la documentación por una limitación de la herramienta.

## How to apply

- **Escribiendo comentarios** en un archivo auditado por texto crudo: describe con palabras («un
  verde muy oscuro», «el ancla de crítico») en vez del literal. Si necesitas el valor exacto, ponlo
  en el JSON de excepciones o en la bitácora del goal, que no se auditan.
- **Escribiendo un gate**: ancla el patrón al **constructo que porta el hecho** —una declaración CSS,
  una etiqueta `<script src>`, un nodo parseado— nunca al nombre desnudo. Si el gate afirma «se carga
  una vez», que cuente cargas.
- **Ante un gate rojo por documentar**: arregla el gate, no la prosa. Y antes de darlo por bueno,
  **múta lo medido y comprueba que sabe fallar** (que cuente 2 al duplicar, 0 al borrar); un patrón
  endurecido a ojo puede quedar en verde permanente, que es el fallo caro.

Relacionado: [[css-layer-cascade]], [[manifiesto-ds-exige-golden]],
[[gate-solo-cuenta-elementos-no-los-lee]], [[gate-estatico-no-ve-tokens-rotos]],
[[el-contador-no-mide-el-archivo]], [[branch-preexisting-red-gates]].
