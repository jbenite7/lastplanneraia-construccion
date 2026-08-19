# Plan — Diagnóstico del coloreado por severidad

**Spec:** `docs/superpowers/specs/2026-08-19-bug-coloreado-severidad-design.md` · **Estado:**
aprobado el 2026-08-19. **Esfuerzo:** media jornada.

## Fase 1 · Tanda 1 — ¿Existe la escala? (documental)

Antes de mirar la pantalla: encontrar dónde está declarado el orden de severidad. Puede no existir.
Si `GLOSARIO.md` no define las severidades, decirlo — sin autoridad local, el orden «correcto» es
una suposición.

- **Verifica:** la fuente del orden, citada con archivo y línea, o la constancia de que no hay.

## Fase 2 · Tanda 2 — Qué pinta la pantalla (medido)

Sesión real por la puerta de servicio, 1180×820 dark. Sembrar filas con los nueve estados —el
fixture de `programacion-intermedia.visual.mjs` ya lo hace, uno por fila— y **leer el color
computado** de cada celda, no el declarado.

Aquí está la trampa que ya costó una vuelta en `contadores-cero`: comparar el valor *declarado* con
el *computado* y creer que cambió algo. Se compara computado contra computado.

- **Verifica:** tabla de los nueve estados con su color computado y su contraste.

## Fase 3 · Tanda 3 — El veredicto

Contrastar Tanda 1 con Tanda 2 y decidir cuál de las tres respuestas de la spec es. Escribirlo con
la evidencia al lado y entregarlo a la coordinadora.

- **Verifica:** el diagnóstico responde las tres preguntas, no dos.

## Riesgos y reversas

- **Arreglarlo «de paso»** → el arreglo sin contrato es lo que produjo la deuda que DS-F0 está
  inventariando. Si la causa es obvia y trivial, se anota como tal y se deja escrito para DS-F2.
- **Juzgar el color por una captura reescalada** → medir contra el valor computado y a tamaño real;
  ya pasó en `severidad-runtime`, donde una captura fuera de escala habría dado el veredicto contrario.
