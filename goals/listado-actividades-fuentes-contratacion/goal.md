# Goal: Listado con fuentes y contratacion claras

Corregir la revision semi-auto de `/listado-actividades` para que el usuario final decida con lenguaje operativo: escoger una fuente real del Programa General y una o varias modalidades de contratacion. La fuente escogida debe derivar automaticamente la actividad de inicio y la fecha de inicio; la semana no debe aparecer como decision editable.

La comprension compartida esta en `facts.md`.

El plan de ejecucion aprobado esta en `plan.md`.

## Done

Este goal queda completo cuando:

- `/listado-actividades` muestra Actividad como selector de fuentes ordenadas por confianza y fecha.
- La fuente inicial es la mas confiable con fecha mas cercana.
- Actividad de inicio y Fecha de inicio se muestran solo como contexto derivado, no como campos editables.
- Semana no aparece como decision editable en la revision.
- Modalidad de contratacion permite seleccionar SI o una combinacion valida de MO/S/OC, tambien en edicion inline.
- Aplicar/guardar persiste la fuente elegida, actividad de inicio, fecha de inicio y modalidades seleccionadas.
- La UI usa lenguaje operativo y oculta conceptos tecnicos salvo en detalle de administrador.
- La verificacion incluye pruebas PHP enfocadas, Playwright en Da Porto semana 1 y captura de la UI corregida.
