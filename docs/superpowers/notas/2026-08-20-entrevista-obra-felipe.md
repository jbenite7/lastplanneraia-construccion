---
capa: fuente
tipo: reporte
estado: abierto
fecha: 2026-08-20
areas: [bi, lps]
fuente: entrevista con Felipe bajo el guion del método antes-del-almuerzo, 2026-08-20
resumen: "Primera entrevista de obra del replanteo: respuestas textuales de Felipe en los roles de residente y director, y el veredicto contra las decisiones que estaban en riesgo"
project: lps-aia
---

# Entrevista de obra — Felipe, roles de residente y director

> **Limitación declarada.** El método pide de tres a cinco personas distintas. Esta es **una sola
> voz cubriendo dos roles**, así que no cierra el supuesto 1 de la spec: sigue faltando un residente
> y un director que no sean Felipe. Lo que sí hace es dar la primera evidencia real, y **tumbar o
> confirmar decisiones que hasta hoy descansaban en suposición**.

## Respuestas textuales

**Pregunta del método:** «La última vez que un compromiso suyo se cayó, ¿cuándo supo que se iba a
caer, y qué habría necesitado saber una semana antes para evitarlo?»

> «Supe después de que se cayó y a veces incluso, sabía que se iba a caer y no hice nada. Habría
> necesitado: 1. sistema de notificación (Correo + notificaciones in-app). 2. Visibilidad de las
> restricciones que necesitaba liberar. 3. Encadenamientos que causan atrasos.»

**Repregunta — ¿por qué no hizo nada esas veces que sí sabía?**

> «Porque no sabía cómo resolverlo, porque desde mis superiores me ordenaban a comprometerme sin
> criterio.»

**Repregunta 5 — ¿qué habría hecho distinto ese mismo día?**

> «Gestionar para poder, al menos, recortar el problema paulatinamente, no necesariamente resolver
> todo en una semana.»

→ **¿Nombra una acción concreta? SÍ.**

**Repregunta 6 — ¿la restricción estaba anotada en el lookahead, con responsable y fecha?**

> «No siempre.»

**Repregunta — ¿imprevisible o sabido y no registrado?**

> «Es falta de hábito y de estar empujando hasta que se vuelva cultura.»

**Repregunta 8, residente — ¿cambiaría la causa que anota si su jefe la viera?**

> «Ha pasado.»

**Repregunta 7, director — ¿con qué preparó el último comité?**

> «Curva S, Valor ganado, PAC, Cumplimiento del cronograma. Todo superficial y datos sin
> interpretación y sin data storytelling.»

## Veredicto por decisión

| Decisión | Veredicto | Evidencia |
|---|---|---|
| **D9 / D59** · Restricciones sin liberar como indicador principal | **Confirmada, con punto ciego** | Pidió «visibilidad de las restricciones que necesitaba liberar» sin que se le sugiriera, y la repregunta 5 cerró en acción concreta. **Pero «no siempre» estaban anotadas**: la lista no puede prometer que muestra todo lo que amenaza la semana |
| **D33** · Asignar responsable y fecha desde la Torre | **Confirmada** | El hueco es de hábito de registro, no de imprevisibilidad: «falta de hábito y de estar empujando hasta que se vuelva cultura». Bajar la fricción de registrar es exactamente lo que D33 hace |
| **D35 / D38** · El director prepara su reunión con la Torre | **Confirmada** | Ya prepara el comité con Curva S, valor ganado, PAC y cumplimiento. La queja no es de qué cifras, es de cómo llegan: «todo superficial, datos sin interpretación y sin data storytelling» |
| **D10 / D19** · Narrativa al centro | **Confirmada de forma directa** | Es la queja literal del director sobre lo que hoy usa |
| **D36 / D37** · Contrapeso al PAC | **En entredicho** | El contrapeso se diseñó contra la trampa de comprometerse a menos. **Al residente le imponen el compromiso**: «desde mis superiores me ordenaban a comprometerme sin criterio». Esa trampa no está a su alcance, y un PAC bajo mide presión de arriba, no su planificación |
| **D46 / D47** · Responsables, y que el jefe vea a su equipo | **En riesgo serio** | «Ha pasado» que se cambie la causa cuando el jefe la va a ver. **La captura envenenada dejó de ser sospecha**; y el propio diseño —el jefe ve a su equipo— es lo que la alimenta |
| **D32** · Declarar el sesgo de captura | **Confirmada y ascendida** | Ya no es contrapeso preventivo: es corrección de un sesgo medido |

## Lo que la entrevista destapó y la spec no tiene

1. **Saber no es poder.** «Sabía que se iba a caer y no hice nada… porque no sabía cómo
   resolverlo.» Todo el replanteo asume que el problema es de información. **En parte es de ruta de
   acción**: señalar el problema no basta si quien lo ve no sabe qué hacer. Eleva la importancia de
   la acción recomendada con dueño (D20), que hoy es un adorno del Resumen Ejecutivo.
2. **El compromiso impuesto.** Un residente al que le ordenan comprometerse sin criterio no puede
   ser evaluado por su cumplimiento, y **no puede registrar la causa real sin señalar a su
   superior**. La causa cómoda no es pereza: es supervivencia.
3. **El avance parcial cuenta.** «Recortar el problema paulatinamente, no necesariamente resolver
   todo en una semana.» El modelo ya guarda valores intermedios de restricción —el «en proceso de
   liberación»— y el diseño actual solo cuenta liberadas. **Un tablero que no da crédito al que
   recortó de tres problemas a uno castiga justamente la gestión que pide.**
4. **Encadenamientos que causan atrasos.** Pedido explícito, y no está en la spec. Es el efecto
   dominó: qué se cae detrás de esto. Pariente de «actividades afectadas por restricción» (D58),
   pero más ambicioso: la cadena, no el conteo.
5. **Notificación in-app, no solo correo.** D76 y D77 solo previeron correo. Pidió las dos.
