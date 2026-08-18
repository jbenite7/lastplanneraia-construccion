<!-- cas:cita-textual — registro de hallazgos: cita comandos defectuosos tal como se dieron -->
# Decisiones pendientes — frente severidad-runtime

<!-- Una entrada por decisión, con estos campos:
**Qué se decide** · **Qué se midió** (con sha) · **Opciones reales** · **Recomendación** ·
**Qué quedó saltado** -->

## PARO: el frente ya está hecho. La premisa caducó hace horas

Verificados los cuatro puntos antes de tocar nada, como se pidió. **No se sostienen.**

- **El commit `82832685` (2026-08-11 13:48) ya aplicó la decisión del usuario**, y está en `main`.
  Su mensaje la nombra literal: «el matiz desempata en todos los niveles menos en el critico».
- **La excepción ya está escrita donde vive el eje:** `states-feedback.css:148-165`, pegada al
  comentario que explica por qué el matiz gana, con el porqué (el nivel crítico es el único que no
  admite ambigüedad) y con la nota de especificidad (0,3,0 para ganar a las reglas de matiz, que
  pesan 0,2,0).
- **El test ya comprueba MÁS, no menos** (`design-system-lab.mjs:287-307`): (1) dos estados del
  mismo nivel con matiz distinto **no** comparten fondo —excluyendo `high:now` a propósito— y
  (2) ningún crítico pierde su fondo. Su propio comentario dice que la vieja no comprobaba (2).

**Por qué el gate sigue `blocked`, que es lo único que queda:** el recibo de
`closeout-evidence.json:26-44` es **anterior al arreglo**. `sourceRef: fe772f09`, registrado a las
**13:08**; el arreglo entró a las **13:48**. Comprobado con `git merge-base --is-ancestor`: sí, es
ancestro. O sea, el gate no está rojo por algo pendiente — está rojo **porque nadie lo volvió a
medir después de arreglarlo**.

**No actúo.** Rehacer trabajo hecho sería peor que no hacer nada, y lo que haría falta —volver a
correr `runtime` y registrar un recibo nuevo— es tocar evidencia registrada, que no me toca.
Queda ofrecida la medición si la coordinadora la quiere.

## Autorizada la medición (no el cambio)

La coordinadora confirma los tres puntos y autoriza correr `npm run test:design-system:runtime`
tal cual. Reponer el recibo **sigue sin estar autorizado**: registrar evidencia es tocar
evidencia, y se decide con el número delante.

**Regla adoptada, y conviene que viaje:** en un frente que nace de un gate rojo, el primer paso es
comparar la **fecha del recibo** con la del **último commit del área**. Un gate rojo puede serlo
porque algo está roto o porque **nadie lo volvió a medir**, y desde fuera las dos cosas se ven
idénticas. Aquí la diferencia eran cuarenta minutos: recibo a las 13:08, arreglo a las 13:48.

Es la misma familia que el resto de lo medido hoy —un estado que **parece** medido y describe un
mundo anterior—, con el agravante de que el formato de un recibo inspira confianza: trae comando,
`exitCode`, sha del artefacto y huella de la fuente. Todo verdadero, y todo de antes.

## Medición ejecutada: 30 de 31, y el que falla no es este frente

`npm run test:design-system:runtime` sobre `b509e90e`, contenedor propio montando el worktree.

- **El test del matiz PASA.** Verificado además en aislado:
  `design-system-lab.mjs:252 › severity and urgency blocks keep distinct semantic backgrounds` →
  `1 passed`. Confirma que `82832685` cerró ese fallo y que el gate estaba rojo por un recibo viejo.
- **Queda 1 fallo:** `design-system-lab.mjs:410 › sidebar shell keeps desktop width, context and
  theme-visible brand mark`, en la línea 448:
  `await expect(logo).not.toHaveCSS('filter', 'none')` → recibido `none`.

### Y es el mismo patrón que el del matiz, otra vez

`4437fcfa feat(marca): adopta el logo «Last Planner · línea Construcción»` cambió a propósito
`filter: var(--ds-active-nav-mark-filter)` por `filter: none` en
`navigation.css:172-178`, con el porqué escrito al lado: *«El ícono Construcción es a color; no se
tiñe con el tema.»*

O sea: **el test exige que el logo se tiña con el tema, y el diseño decidió que no se tiña.** La
aserción contradice una decisión intencionada, igual que hacía la del matiz. **No la toco:**
cambiar lo que una prueba mide está en la lista de bloqueo incondicional.

### Dos avisos sobre la medición misma

1. **Las otras tres etapas no llegaron a correr.** El script encadena con `&&`
   (`playwright … && test:a11y:lab && test:visual:lab && test:performance:lab`), así que al fallar
   la primera, a11y, visual y rendimiento **no se midieron**. «30 de 31» es el inventario de la
   primera etapa, no del gate. El gate completo sigue sin medirse entero.
2. **El código de salida que vi era mentira.** La corrida se reportó con `exitCode 0` porque la
   salida iba por `| tail`, y `$?` es el del último tramo de la tubería. La suite había fallado.
   Es la trampa que el repo ya tiene escrita, y aquí importa el doble porque el recibo del gate
   **registra un `exitCode`**: si se repone con la tubería puesta, se registra un cero falso.

## La pregunta de `EXPERIMENTS.md:88`, medida: el logo se lee, pero su silueta no llega al piso

Capturas en `goals/severidad-runtime/evidence/` (1180×820, dark, sesión real por la puerta de
servicio): pantalla entera, recorte a tamaño real, y un ampliado ×4 **marcado en el nombre como
no-real** para poder mirarlo sin confundir la escala.

Medido sobre los píxeles reales del SVG dibujados en canvas, contra el fondo del carril
`oklch(0.145 0.003 260)`:

| Qué | Contraste |
|---|---|
| Campo ámbar del cuadro `rgb(181,82,17)` — 1013 px, el grueso de la marca | **1.67:1** |
| Segundo tono `rgb(232,119,34)` | 2.83:1 |
| La parte más clara (la «L») | 8.37:1 |
| Piso AA para elementos no textuales | 3:1 |

**Lectura, sin aplanarla en un sí o un no:**

- **Se lee.** La «L» clara destaca con holgura y la marca se reconoce en la captura a tamaño real.
- **Pero su silueta exterior no llega al piso:** el cuadro ámbar contra el carril está en 1.67:1,
  por debajo de 3:1. Si eso fuera exigible, el logo no cumpliría.
- **No parece exigible, y este es el dato que decide:** el `<img>` viene con `alt=""` y
  `aria-hidden="true"` — está **declarado decorativo**. El piso de 3:1 de WCAG 1.4.11 gobierna
  elementos no textuales *que transmiten información*; un adorno declarado como tal queda fuera.

Es decir: el test **no está defendiendo accesibilidad**, está defendiendo un filtro de tema que el
diseño retiró a propósito. La decisión que queda es de marca —¿se quiere que el ámbar despegue más
del carril?—, no de cumplimiento. **No la tomo.**

## Implementado y mutado

- **Aserción sustituida** en `design-system-lab.mjs`: seis comprobaciones en pantalla donde había
  una en CSS. Con el porqué al lado, `4437fcfa` y la frase del diseño incluidos.
- **Un fallo propio, corregido en vez de rodeado:** la primera versión fallaba en la página sana
  por `destapada`. No era que algo tapara la marca — en el laboratorio el carril está en **y=931
  con un viewport de 900**, o sea **fuera de pantalla**, y `elementFromPoint` solo responde dentro
  del viewport. Mi comprobación confundía «no he llegado a mirar ahí» con «algo la cubre». Se
  arregla con `scrollIntoViewIfNeeded()` antes de medir, y queda escrito por qué.
  Es mi propia trampa al revés: tan malo es leer el DOM y creer que se ve, como leer la pantalla
  sin haber llegado a ella.
- **Mutación (a), marca que no carga** (`icon.svg` → `NO-EXISTE.svg`): **falla**, con el mensaje
  «la marca no cargó». Es el caso que la aserción vieja dejaba pasar en verde.
- **Mutación (b), marca tapada** (un `<span>` opaco encima): **falla**, con «algo tapa la marca».
  Es la que separa «está» de «se ve», y la que el `filter` jamás habría detectado.
- Restaurado sin diff, y el caso en verde.
