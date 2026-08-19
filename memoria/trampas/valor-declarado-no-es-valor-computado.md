---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-11
areas: [design-system, qa]
fuente: sesion-contadores-cero
resumen: comparar el valor DECLARADO en el CSS contra el COMPUTADO del navegador finge un cambio que no existe (y esconde uno que sí) — la comparación válida es computado antes contra computado después
---

Al retirar un `!important` de una regla, la tentación es leer el valor que la regla **declara** y
compararlo con lo que `getComputedStyle` devuelve **después** del cambio. Eso no es una
comparación de antes y después: son dos cosas distintas.

Pasó el 2026-08-11 en el frente `contadores-cero`. La regla decía
`.pi-page #hot-container td.ops-state-td { padding: 4px 6px !important }`. Tras quitarle la
prioridad, el computado era `0px 10px`, y la conclusión inmediata fue «lo he roto, revierto».
**Era falso.** Al revertir y medir de verdad la línea base, el computado **ya era `0px 10px`
antes**: otra regla más específica ganaba desde siempre, y ese `!important` no pintaba nada.
El cambio era correcto y estuvo a punto de descartarse.

El error no da síntoma. En ambas direcciones miente:

- **Falso positivo** (el caso medido): parece que rompiste algo y revientes un cambio bueno.
- **Falso negativo**, peor: si la regla declara justo lo mismo que otra que sí gana, el declarado
  y el computado coinciden, se da por bueno y **no se detecta el cambio real** en un tercer sitio.

## Procedimiento correcto

Revertir al estado limpio y **medir ahí la línea base**: `getComputedStyle` de las propiedades
afectadas, con la página cargada y en el estado que se quiere retratar. Después aplicar el cambio,
una retirada por vez, y **comparar computado contra computado** — misma propiedad, mismo elemento,
y sobre **todos** los elementos que casan con el selector, no solo el primero: 34 encabezados
pueden no comportarse como el primero.

Corolario que también costó tiempo el mismo día: **una sonda en el CSSOM no sustituye a mirar la
página**. Probar `setProperty(prop, val, '')` sobre longhands de una abreviada (`transition`,
`border`, `padding`) y restaurarlos uno a uno **no** devuelve la regla a su estado original, y
contamina todas las mediciones posteriores con falsos «hace falta». Si se prueba en el CSSOM, hay
que restaurar `style.cssText` entero y **verificar que la línea base vuelve idéntica** antes de
fiarse del resultado.

Familia: [[el-contador-no-mide-el-archivo]], [[gate-solo-cuenta-elementos-no-los-lee]],
[[captura-playwright-miente]], [[css-layer-cascade]], [[el-dom-dice-que-existe-no-que-se-ve]],
[[gate-visual-tolerancia-enganosa]].

Todas comparten la forma: **el instrumento contesta a lo que le preguntas, no a lo que quieres
saber.** `querySelector` responde «existe», no «se ve»; el CSS declarado responde «pide», no
«gana»; y un gate visual responde «cambió más que el umbral», no «cambió algo que importa».
