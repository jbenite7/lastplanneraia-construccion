---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-19
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-19-ds-f0-auditoria-total-design.md
resumen: DS-F0 · Auditoría total del design system — diseño
---

# DS-F0 · Auditoría total del design system — diseño

**Fase:** DS-F0, bloque 1 de [[TASKS|Cola de pendientes]]. **Frente:** `ds-f0-auditoria`.

## De dónde sale

Conclusión del usuario, 2026-08-18: «El design system no está bien definido, ni bien implementado,
ni bien controlado.» La evidencia que la sostiene: ~2.600 hallazgos de deuda, Handsontable entero
fuera del sistema (`.ht_master` sin un token), gobernanza con 2/15 gates reales, y bugs de stacking
sin escala definida.

DS-F0 es la fase que convierte esa frase en un inventario con el que se pueda decidir.

## Qué se decide

**Esta fase produce un inventario, no un arreglo.** Nada se repara aquí: reparar es DS-F2, y
repararlo antes de tener el contrato de DS-F1 sería hacerlo dos veces.

Recorrido: **módulo por módulo, objeto por objeto, variable por variable, escenario por escenario.**

**Entregable:** inventario por severidad en cascada «Crítico → Sin problema», en
`docs/design-system/auditoria/`, en formato consultable por máquina además de legible.

**Absorbe como semilla**, no como trabajo pendiente aparte:

- Las 48 decisiones del 3-ago (`docs/superpowers/decisiones-pendientes-2026-08-03.md`).
- F-4…F-9 de `docs/DESIGN-AUDIT.md`.
- Los planes de auditoría de UI del 3-ago, que por eso **no se archivaron**.

## La parte que se puede hacer ya, y la que no

**Se hace ya (no depende de nada):** todo el recorrido de lectura — censo de módulos, de tokens, de
primitivas `aia-*`, de overrides de vendor, de usos de `!important`, de hex sueltos y estilos en
línea, y la clasificación por severidad.

**Espera al frente `runtime-budgets-al-ci`:** cualquier cifra que salga de ejecutar los gates. Sin
un carril de referencia sano, una medición automatizada no dice si el problema es del módulo o del
medidor. Se deja el hueco marcado, no se rellena con una cifra que no se pueda defender.

## Posture

- **No arreglar nada.** Ni un hex, ni un `!important`, ni un token. Si aparece algo trivial, se
  anota con su severidad y se sigue.
- **No tocar `docs/design-system/closeout-evidence.json`** ni ningún baseline: son de otro frente.
- **No decidir vocabulario ni escalas.** Eso es DS-F1 y arranca con brainstorming con el usuario.
- **Sin dependencias nuevas.**

## Leer primero

- `DESIGN.md` y `docs/design-system/README.md` — el contrato vigente, que es lo que se audita.
- `docs/DESIGN-AUDIT.md` — F-4…F-9, la semilla.
- `docs/superpowers/decisiones-pendientes-2026-08-03.md` — las 48.
- `memoria/mapas/` del área design-system — las trampas ya puestas.
- `AGENTS.md` §Routing por tipo de cambio.

## Condición de hecho

`docs/design-system/auditoria/` con el inventario completo por módulo y por severidad; cada
hallazgo con archivo, línea y por qué se clasificó así; los huecos que dependen del CI marcados como
tales y no rellenados. Cero cambios en código de producto.
