---
capa: wiki
tipo: decision
estado: vigente
fecha: 2026-08-18
areas: [design-system, proceso]
fuente: sesión de coordinación 2026-08-18, conclusión del usuario al revisar el inventario de pendientes
resumen: "El design system no está bien definido, implementado ni controlado: se corrige con un programa en cuatro fases, no parchando módulos"
---
# El design system se rehace por programa, no por parches

**Conclusión del usuario (2026-08-18):** «El design system no está bien definido, ni bien
implementado, ni bien controlado. Debemos corregir esto ya.» La evidencia que la sostiene:
~2.600 hallazgos de deuda, Handsontable entero fuera del sistema (`.ht_master` sin un token),
gobernanza con 2/15 gates reales, y bugs de stacking sin escala definida.

**Las cuatro fases, cada una alimenta la siguiente:**

- **F0 · Auditoría total** — toda la app, módulo por módulo, objeto por objeto, variable por
  variable, escenario por escenario. Absorbe como semilla las 48 decisiones del 3-ago
  (`docs/superpowers/decisiones-pendientes-2026-08-03.md`) y F-4…F-9 de `docs/DESIGN-AUDIT.md`.
  Entregable: inventario por severidad en cascada «Crítico → Sin problema» (y de paso verificar el
  posible bug de ese coloreado que el usuario sospecha).
- **F1 · Redefinición del contrato** — tokens, primitivas `aia-*`, escalas de estado/severidad y
  **escala de stacking** (z-index). Arranca con brainstorming con el usuario: el contrato es
  decisión de negocio.
- **F2 · Reimplementación por adaptadores** — primero los dos vendors que concentran la deuda
  (Handsontable, DataTables), luego módulo a módulo según F0.
- **F3 · Control** — gates nuevos derivados del contrato, bajo cinco principios: pocos y atados a
  contratos que duelan; nunca bloquean el flujo local (solo el merge); actualizar un baseline
  cuesta un comando con diff visible; todo rojo dice qué archivo y qué hacer; cuarentena explícita
  para gates ruidosos. Los 15 gates actuales **se reemplazan, no se arreglan**.

**Consecuencia de secuencia:** la Torre de Control BI no se recaptura — se **reconstruye con
enfoque data storytelling** sobre el contrato de F1 y con el tema claro ya existente; rehacerla
antes sería construirla dos veces.
