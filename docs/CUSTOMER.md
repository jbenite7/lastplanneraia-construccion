# Customer

Quién usa Last Planner AIA y para qué lo contrata. Producido en la fase 1 (`jobs-to-be-done`) del
journey `improve-app`, el 2026-08-04, en entrevista con el usuario.

**Origen de la evidencia, dicho sin adornos:** quejas directas de obra que le llegan al usuario, sin
registro escrito, más las mediciones de la campaña de dark mode. **No hay analítica de uso, tickets
ni grabaciones.** Todo lo que sigue es criterio del usuario, no dato observado; donde una afirmación
necesite medición para sostenerse, se dice.

## Job Statement

La app no se contrata para «gestionar la programación». Se contrata para tres trabajos distintos,
uno por rol, que compiten entre sí por la misma pantalla:

**Residente de obra** — el que la usa a diario:

> Cuando planifico la semana que viene, quiero saber qué me va a frenar —material, permiso, frente
> liberado— antes de que me frene, para no descubrirlo el lunes con la cuadrilla parada.

> Cuando algo no se cumple, quiero que quede registrado por qué y de quién dependía, para que no me
> carguen a mí un retraso que no era mío.

**Director de obra** — supervisa sin estar en el detalle diario:

> Cuando reviso cómo va la obra, quiero saber si el ritmo real me lleva a la fecha comprometida,
> ver qué contratista o qué restricción falla de forma repetida, y enterarme sin llamar a cinco
> personas ni esperar al lunes.

**Gerencia** — varias obras a la vez:

> Cuando reviso el portafolio, quiero comparar obras con la misma vara, llevar ante el cliente o la
> junta datos que se sostengan solos, y saber si la obra planifica mejor con el tiempo.

Ninguno nombra el producto, y eso importa: los tres describen situaciones que existían antes de que
la app existiera y que hoy se resuelven de otra manera cuando la app no llega.

## Job Dimensions

| Rol | Dimensión | Qué es | Dónde subentrega hoy |
|---|---|---|---|
| Residente | **Funcional** | Anticipar restricciones antes de comprometerse | **Subentrega.** Si el levantamiento de restricciones en Programación Intermedia no llega a tiempo o no se ve, la semanal hereda compromisos imposibles y la app deja de cumplir su razón de ser |
| Residente | **Social (defensiva)** | Que la causa del incumplimiento y su responsable queden registrados | **Subentrega.** Toca CNP, CNC y el registro de responsables: si registrar la causa cuesta trabajo o el responsable no queda claro, el residente pierde su respaldo |
| Residente | **Emocional** | *No señalada como motor por el usuario.* El miedo a «quedar mal al comprometerse» se descartó explícitamente frente a las dos anteriores | — |
| Director | **Funcional** | Proyección contra la línea base y detección de patrones repetidos | **Subentrega.** Toca indicadores y Torre de Control |
| Director | **Emocional** | Dejar de ir a ciegas; no perseguir a nadie para saber el estado | **Subentrega.** Depende de la frescura del dato: un estado desactualizado devuelve al director al teléfono, que es justo el trabajo que quería dejar de hacer |
| Director | **Social** | Gestionar contratistas con datos y no con impresiones | **Subentrega**, por dependencia: sin el patrón detectado (funcional) la conversación vuelve a ser de percepciones |
| Gerencia | **Funcional** | Comparar obras con criterio homogéneo | **Subentrega.** Exige consistencia entre proyectos, no solo dentro de uno |
| Gerencia | **Social** | Datos que se sostengan ante el cliente o la junta | **Subentrega.** Un dato que el residente no pudo registrar bien no se sostiene aguas arriba |
| Gerencia | **Emocional** | Saber si la apuesta por Last Planner está rindiendo | **Subentrega.** Exige serie temporal, no foto |

**El hallazgo de la fase 1, y no es cómodo:** el usuario señaló subentrega en **las cuatro áreas**
que se le ofrecieron. No hay una dimensión sana que sostenga a las demás, y las tres cadenas de
valor comparten origen: **si el residente no puede registrar bien y a tiempo, el director no ve
patrones y la gerencia no tiene con qué sostener sus cifras.** La calidad del dato en la punta —
Programación Intermedia y Semanal — determina si los otros dos trabajos se cumplen.

Eso hace que la cascada PG → PI → PS no sea «el flujo más usado» sino **el cuello de botella de los
tres jobs a la vez**, y confirma la elección de tanda hecha en el diseño de la biblia.

**Big Hire vs Little Hire:** las cuatro subentregas son de **uso repetido**, no de primera vez. La
app está implantada y la gente ya entró; lo que falla es el ciclo semanal, no el arranque. Las fases
siguientes miran el uso diario, no el onboarding.

## Competing Alternatives

Qué hacen los tres roles cuando la app no llega. Ninguna de estas alternativas es un producto rival:
son lo que ya usaban y a lo que vuelven.

| Alternativa | Por qué se contrata | Su debilidad |
|---|---|---|
| **Excel y WhatsApp** | Cero fricción, funciona sin permisos ni conexión, el residente lo controla entero | No deja rastro comparable entre obras ni serie temporal; la gerencia no puede sostener nada con eso |
| **La llamada telefónica** | Respuesta inmediata y con matiz — el director pregunta y entiende el contexto | Es exactamente el trabajo que el director quería dejar de hacer; no escala a varias obras y no queda registrado |
| **La reunión semanal presencial** | Es donde el compromiso se hace social y por eso se cumple | Lo hablado no queda registrado como dato; la causa del incumplimiento se discute y se olvida |
| **No consumo: no registrar la causa** | Registrar cuesta tiempo al final de una jornada larga | Es la alternativa más peligrosa, porque vacía en silencio los tres jobs: sin causa registrada no hay patrón, ni aprendizaje, ni defensa para el residente |

**La alternativa a vigilar es la última.** No compite por ser mejor, sino por ser más fácil, y su
efecto no se ve en la obra que la elige sino aguas arriba, semanas después.
