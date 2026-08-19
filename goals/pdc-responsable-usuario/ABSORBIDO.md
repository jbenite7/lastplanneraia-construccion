---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-07-29
areas: [pdc]
fuente: goals/pdc-responsable-usuario/ABSORBIDO.md
resumen: Este goal (responsable de paquete: arreglar el guardado y darle un selector de personas) se cerró y se absorbió.
---

# Goal absorbido — 2026-07-29

Este goal (**responsable de paquete: arreglar el guardado y darle un selector de personas**) se cerró y
se absorbió.

- **Su grilleo se cumplió** y produjo el spec
  `docs/superpowers/specs/2026-07-28-responsable-usuario-proyecto-design.md`, con sus doce decisiones.
- **Está implementado y en `main`**: migración `database/migrations/20260728_pdc_v2_responsable_usuario.sql`
  (`responsable_user_id`, auditoría, `DROP COLUMN responsable`), más asignación en masa (`7bfa165`) y la
  corrección de que cambiar de frente ya no borre al responsable (`3e574d7`).
- **Su continuación vive en** [`goals/pdc-preparar-b1`](../pdc-preparar-b1/goal.md): el tablero de
  vencimientos de la Ola 1 usa ese responsable como columna y como filtro.

La carpeta se conserva —no se borra— porque `interview.json` / `interview-result.json` son el registro de
cómo se tomaron esas doce decisiones.
