---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-19
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-19-bug-coloreado-severidad-design.md
resumen: El coloreado en cascada por severidad — diseño del diagnóstico
---

# El coloreado en cascada por severidad — diseño del diagnóstico

**Alimenta:** DS-F0. **Frente:** `bug-coloreado-severidad`.

## De dónde sale

Encargo del usuario, 2026-08-18, anotado al cerrar `contadores-cero`: espera que la tabla ordene el
color por severidad de «Crítico» a «Sin problema», y **cree que no está pasando**.

Instrucción suya, literal en su intención: **tratarlo como posible bug, no como preferencia.**
Diagnóstico primero (`systematic-debugging`), no propuesta de rediseño.

## Qué se decide

**Esta tarea no arregla nada. Averigua qué pasa y lo demuestra.**

Tres respuestas posibles, y las tres son un resultado válido:

- **Es un bug** — la escala existe y el render no la respeta. Entregable: la causa raíz, con el
  archivo y la línea, y una reproducción.
- **No hay escala que respetar** — los tintes nunca se definieron en orden de severidad. Entonces
  no es un bug sino un hueco de contrato, y es entrada para DS-F1.
- **Funciona y se percibe mal** — el orden está y se aplica, pero los tintes no se distinguen entre
  sí a la vista. Entonces es un problema de contraste, medible.

Distinguir cuál de las tres es **todo** el trabajo. Elegir la respuesta cómoda sin medir es
exactamente lo que esta ficha existe para impedir.

## Por qué va aparte de DS-F0

Es de lectura, cabe en una sesión y no toca los mismos archivos que la auditoría, así que corre en
paralelo sin estorbar. Su resultado entra en el inventario de DS-F0 como un hallazgo ya diagnosticado
en vez de uno más por revisar.

## Posture

- **No arreglar.** Ni «ya que estoy». El arreglo se decide con el diagnóstico delante.
- **No tocar los tintes ni sus tokens.** Cambiar la escala es DS-F1 y es decisión de negocio.
- **No cambiar lo que mide ninguna prueba**, ni regenerar goldens: lista de bloqueo incondicional.
- **Sin dependencias nuevas.**

## Leer primero

- `docs/design-system/` — los contratos de estado y tinte, y `state-tint-exceptions.json`.
- `tests/design-system/state-tint-ladder.test.mjs` — qué se está comprobando hoy de la escalera.
- `public/js/modules/programacion_intermedia/hot.js` — `stateLabels` (~línea 505) y el render de
  la celda de estado.
- `decisiones/contadores-cero.md` — el contexto en que salió el encargo.
- `GLOSARIO.md` — antes de nombrar cualquier severidad.

## Condición de hecho

Un diagnóstico escrito que responde cuál de las tres es, con evidencia: capturas a 1180×820 dark
por sesión real (puerta de servicio, nunca `/login`), valores computados y, si es bug, la línea que
lo causa y cómo reproducirlo. Cero cambios en producto.
