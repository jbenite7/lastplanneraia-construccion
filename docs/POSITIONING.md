---
capa: fuente
tipo: biblia
estado: vigente
fecha: 2026-08-05
fuente: docs/POSITIONING.md
resumen: Artefacto de la fase 6 (made-to-stick) del journey improve-app (docs/IMPROVE-APP-PLAN.md), escrito el 2026-08-05 en el Task 30 (IA-5).
---

# Positioning

Artefacto de la fase 6 (`made-to-stick`) del journey `improve-app` (`docs/IMPROVE-APP-PLAN.md`),
escrito el 2026-08-05 en el Task 30 (IA-5).

**Esqueleto mínimo a propósito.** La skill contempla más secciones (audiencia, categoría, prueba,
diferenciadores), pero Last Planner AIA es una **app interna de empresa**: no tiene paywall, ni
página de producto, ni superficie de venta — por eso la fase 7 (`influence-psychology`) está
`skipped` en el tracker. Lo único que aquí hace trabajo real es el bloque de mensajes, porque es lo
que gobierna el copy in-app. El resto se deja sin poblar en vez de rellenarlo con material
inventado.

## Key Messages

**De dónde salen, y no es de una sesión de redacción.** Los tres mensajes son la traducción directa
de los tres *job statements* por rol de `docs/CUSTOMER.md`, recogidos en entrevista con el usuario el
2026-08-04. Uno por rol, en las palabras del rol: si un mensaje no se puede decir con el vocabulario
que esa persona ya usa en obra, es que no es suyo.

| Rol | Mensaje | El job que traduce (`CUSTOMER.md`) |
|---|---|---|
| **Residente de obra** | «Que nada te frene el lunes: mira la semana que viene antes de comprometerte, y deja por escrito de quién dependía cada cosa.» | «…saber qué me va a frenar antes de que me frene, para no descubrirlo el lunes con la cuadrilla parada» + «que quede registrado por qué y de quién dependía» |
| **Director de obra** | «Enterarte sin llamar a cinco personas: si el ritmo real llega a la fecha comprometida, y qué contratista o qué restricción falla siempre.» | «…saber si el ritmo real me lleva a la fecha comprometida, ver qué contratista o qué restricción falla de forma repetida, y enterarme sin llamar a cinco personas» |
| **Gerencia** | «La misma vara para todas las obras: cifras que se sostienen solas ante el cliente y que enseñan si se planifica mejor con el tiempo.» | «…comparar obras con la misma vara, llevar ante el cliente o la junta datos que se sostengan solos, y saber si la obra planifica mejor con el tiempo» |

**El mensaje que los une, y que decide las prioridades:** los tres cuelgan del mismo hilo. Si el
residente no puede registrar bien y a tiempo, el director no ve patrones y la gerencia no tiene con
qué sostener sus cifras (`CUSTOMER.md`, §Job Dimensions). Por eso la cascada **PG → PI → PS** no es
«el flujo más usado» sino el cuello de botella de los tres jobs a la vez — y por eso el copy de esas
pantallas se audita antes que ningún otro.

**Cómo se usan estos mensajes en la interfaz.** No son eslóganes para pintar en una cabecera: son la
vara con la que se juzga un texto de pantalla. Un mensaje de error, un estado vacío o un tooltip
aprueba si le sirve al rol que está mirando esa pantalla para avanzar en **su** job. La aplicación
concreta —el score SUCCESs superficie a superficie, qué se reescribió y qué se dejó por ser de
dominio— vive en `docs/DESIGN-AUDIT.md`, §Score SUCCESs del copy in-app, entradas `S-1` … `S-6`.

**Regla heredada del Task 30, que sigue vigente:** el vocabulario de dominio lo gobierna
`GLOSARIO.md`, no este documento. Un mensaje puede reescribirse; un término LPS —«compromiso»,
«restricción», «liberación», «CNC»— no, sin decisión del usuario.
