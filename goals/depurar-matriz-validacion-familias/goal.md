# Objetivo: depurar matriz de validación humana de familias

Depurar la matriz de validación humana para que solo contenga casos revisables y familias válidas según la retroalimentación del usuario. La implementación debe eliminar `No es actividad`, retirar del desplegable las familias `Mano de Obra - ...` indicadas, y unificar `Red RCI` y `Red Contra Incendio - Piping` bajo `Red de Extinción`.

La comprensión compartida está en `goals/depurar-matriz-validacion-familias/facts.md`.

El plan aprobado está en `goals/depurar-matriz-validacion-familias/plan.md`.

## Condición de terminado

El objetivo queda terminado cuando:

- `docs/qa/matriz-validacion-humana.xlsx` queda regenerada con 300 casos revisables.
- No aparece `No es actividad` en `decision_humana`.
- No aparecen como opciones las familias:
  - `Mano de Obra - Acabados`
  - `Mano de Obra - Cimentacion`
  - `Mano de Obra - Estructura`
  - `Mano de Obra - Excavaciones`
  - `Mano de Obra - Instalaciones`
  - `Mano de Obra - Mamposteria`
  - `Mano de Obra - Urbanismo`
- `Red RCI` no aparece como opción separada.
- `Red Contra Incendio - Piping` y `Red RCI` quedan representadas en la matriz como `Red de Extinción`.
- `docs/qa/matriz-validacion-humana.summary.json` y `docs/qa/matriz-validacion-humana.summary.md` quedan regenerados.
- Las pruebas automáticas verifican estas reglas y pasan en Docker.
