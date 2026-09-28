---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-09-27
areas: [proceso]
fuente: docs/superpowers/specs/2026-09-26-s05-deuda-cierre-design.md:81-82; docs/superpowers/plans/2026-09-24-s05-ronda-1-2-informe.md:112
resumen: En una app Codex compartida las ventanas pueden cruzarse y el envío puede requerir contexto de pantalla; revisa la transcripción de la sesión antes de atribuir actividad
---
Durante la supervisión de la ronda 1.2, varias sesiones compartían la app de Codex: las ventanas
se cruzaron y el envío de mensajes requirió control de pantalla. La ventana enfocada no basta para
identificar qué sesión produjo un mensaje o en qué estado quedó.

Antes de atribuir un avance o reconstruir un intercambio entre tareas, identifica la sesión y
contrasta su transcripción bajo ~/.codex/sessions. Para supervisar, esa transcripción es la fuente
fiable del historial (spec S05-DEUDA:81-82; informe de ronda 1.2:112). La coincidencia horaria
por sí sola tampoco prueba autoría; ver [[autoria-por-coincidencia-de-hora]].
