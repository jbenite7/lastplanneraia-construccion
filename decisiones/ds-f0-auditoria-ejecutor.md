<!-- cas:cita-textual — registro de hallazgos: cita comandos defectuosos tal como se dieron -->
# Decisiones pendientes — frente ds-f0-auditoria

<!-- Una entrada por decisión, con estos campos:
**Qué se decide** · **Qué se midió** (con sha) · **Opciones reales** · **Recomendación** ·
**Qué quedó saltado** -->

## D1 — El plan usa `Tanda N` y `cas-frente.sh` solo entiende `Task N` / `Fase N`
`cas-frente.sh --plan docs/superpowers/plans/2026-08-19-ds-f0-auditoria-total.md --fase "Tanda 1"`
sale con: «el plan ... no declara ninguna fase (busqué encabezados 'Task N' o 'Fase N')».

Opciones: (a) renombrar los encabezados del plan aprobado `## Tanda N` → `## Fase N`; (b) dejar el
frente con `Plan: ?` / `Fase: ?`. Tomé (b) porque no toco un plan aprobado por mi cuenta y porque
`?` se rellena después sin conflicto (`cas-frente.sh` sustituye `?`, pero se niega a reasignar un
valor ya puesto). No bloquea: si la respuesta es (a), no hay que borrar nada escrito.
