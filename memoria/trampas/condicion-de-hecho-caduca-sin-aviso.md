---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-10
areas: [proceso, bi, design-system]
fuente: goals/bi-control-tower-gemini/goal.md, goals/design-system-nucleo-gobernanza/goal.md, Frente 0 (Tasks 5 y 6, 2026-08-10)
resumen: "Dos goals llevaban semanas bloqueados con una condición de hecho que ya no se podía cumplir — parecía «falta que alguien mire» y era «esto es imposible tal como está escrito»"
---
# Una condición de hecho puede caducar sin que nadie lo note

Un goal bloqueado no siempre espera tiempo de alguien. Puede estar pidiendo algo que **ya dejó de
existir**, y nada en el repo avisa de eso — el goal simplemente sigue en la lista de abiertos,
acumulando semanas, con una nota que sonaba razonable el día que se escribió.

Dos casos medidos el mismo día en el Frente 0 (2026-08-10), el mismo patrón por los dos lados:

- **[[goals/bi-control-tower-gemini/goal|bi-control-tower-gemini]]** pedía aprobación visual de una
  matriz de 6 modos (Mobile/Tablet/Desktop × Dark/**Linen**). El tema `linen` se retiró del producto
  el 2026-07-25 (DS-030). El goal llevaba **mes y medio** pidiendo aprobar capturas de un tema que
  ya no existe — nadie podía cumplirlo, no porque nadie lo intentara, sino porque la condición
  citaba un estado del producto que el propio repo ya había abandonado.
- **[[goals/design-system-nucleo-gobernanza/goal|design-system-nucleo-gobernanza]]** exigía los 15
  gates de `closeout-evidence.json` en `passed` con evidencia fresca. La evidencia declarada tenía
  `generatedAt: 2026-07-15`, pero avalaba `designSystemVersion: 1.1.0` — una versión publicada el
  2026-08-07, **tres semanas después** de la fecha que dice certificar. Cerrar el goal citando esa
  evidencia habría sido aprobar una versión con el recibo de otra. Ver también
  [[gate-solo-cuenta-elementos-no-los-lee]]: la evidencia ni siquiera era real.

En los dos casos el síntoma en la wiki y en el `goal.md` era el mismo: «bloqueado, falta algo».
Medido de cerca, la causa no era un pendiente sin dueño sino una condición **incumplible tal como
estaba redactada** — en un caso por un tema retirado, en el otro por evidencia anterior a lo que
debía avalar.

**Why:** una condición de hecho es una foto de un momento. Si el producto cambia por debajo
(se retira un tema, se publica una versión) y nadie revisita la condición, el goal queda
bloqueado por una razón que ya no es la que dice ser. El estado «bloqueado» no distingue entre
«falta trabajo» y «la meta ya no es alcanzable como está escrita».

**How to apply:** al revisar un goal bloqueado hace tiempo, no asumas que solo falta ejecutar lo
pendiente. Relee la condición de hecho contra el estado **actual** del repo: si cita un tema, una
versión, un archivo o un dato concretos, verifica que sigan existiendo. Si la condición citaba algo
que ya no existe, no es "casi cerrado" — hay que **redefinir la condición**, con el usuario, antes
de seguir midiendo contra ella.

Ambos casos se resolvieron el 2026-08-10, no cerrando el goal sino corrigiendo por qué seguía
bloqueado: ver [[estado|Estado de los goals]] para su estado actualizado. (Nota del pase 8,
2026-08-12: `closeout-evidence.json` declaraba entonces **8** gates, no 15 — hoy son **9**, desde que
entró `semanal-roles-phases` el 2026-08-14 —, y aunque su `generatedAt` de
cabecera sigue en 2026-07-15, los `verifiedAt` por gate ya son del 2026-08-11/12 con recibos
reales — el segundo caso quedó saneado por el Frente 1b.) Mapa del área:
[[design-system]] y [[qa-y-gates]].
