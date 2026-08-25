---
capa: wiki
tipo: concepto
estado: vigente
fecha: 2026-08-25
areas: [proceso, pdc]
fuente: verificación de docs/superpowers/specs/2026-07-29-cierre-prelanzamiento-pdc-design.md
resumen: "Una condición de hecho que el dueño retira del alcance no es una condición incumplida que bloquee el cierre; confundirlas deja specs cerradas pareciendo tareas vivas"
---

# Una condición retirada del alcance no es una condición incumplida

`cierre-prelanzamiento-pdc` tenía seis condiciones de hecho. La sexta —triagear los hallazgos del
piloto real— **nunca se cumplió y nunca se va a cumplir**: quien montaba el piloto no reportó nada.
La spec, en su §Riesgos, prohibía expresamente darla por buena: «se declara así y **no se da por
cumplido**». Y así se hizo: `hallazgos-piloto.md` se titula «no cumplido, sin contenido».

Y sin embargo la spec está **cerrada**, y lo está bien.

La diferencia está en quién movió qué. Felipe decidió cerrar el hueco vacío en vez de dejar la fila
abierta esperando (`goals/pdc-preparar-b1/estado-olas.md:211`), con destino escrito para lo que
llegara después: «si reporta más tarde, entra por la Ola 2 y no reabre esta fila». Eso **retira el
punto del alcance**. No lo declara satisfecho.

Son dos actos distintos y conviene no mezclarlos nunca:

| | Quién lo hace | Qué produce |
|---|---|---|
| Dar por cumplido lo incumplido | el ejecutor, para poder cerrar | **evidencia fabricada** — la falta grave del repo |
| Retirar la condición del alcance | **el dueño del trabajo** | una decisión de alcance, auditable y reversible |

## Por qué importa más allá del caso

Confundirlas falla en las dos direcciones, y las dos cuestan:

- Leída como incumplimiento, la spec vuelve al radar como **tarea de alguien** — y no hay tarea: el
  piloto no va a reportar. Fue lo que le pasó a `IMPLEMENTATION_PLAN_INVENTORY.md`, que la tenía como
  «parcial» mientras su frontmatter decía `cerrado`. Al medirla, **el que se contradecía era el
  inventario**.
- Leída como cumplimiento, se pierde el dato de que **ese trabajo nunca se hizo**, que es
  exactamente lo que la spec quería preservar.

La forma correcta es la tercera: cerrada, **con el punto declarado sin contenido y con la firma de
quien lo retiró**. Cuesta un párrafo y es lo único que sobrevive a la relectura.

## Cómo distinguirlas al auditar

Ante una spec cerrada con una condición visiblemente incumplida, no la reabra por eso solo. Busque
**la decisión del dueño y su fecha**. Si existe, el cierre se sostiene y lo que falta es escribirlo
bien. Si no existe, entonces sí: es un cierre prematuro.

Es primo de [[memoria/trampas/cerrado-no-es-lo-mismo-que-derogada]] — mismo vocabulario corto para
estados que no son el mismo — y de la regla que `AGENTS.md` §Verificación ya fija para las casillas
`- [ ]`: el estado de un trabajo se lee de las decisiones escritas, no de las marcas.
