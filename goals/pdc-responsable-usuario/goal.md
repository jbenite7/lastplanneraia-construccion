# Goal — Responsable de paquete como usuario del proyecto

**Slug:** `pdc-responsable-usuario`
**Estado:** HECHO — absorbido por `pdc-preparar-b1`

## Objetivo

Sustituir el campo de texto libre para el responsable de paquete por una asociación formal a un
usuario del proyecto, con selector UI, migración de BD y trazabilidad.

---

## Cierre formal

**Estado:** HECHO (absorbido)
**Fecha de cierre:** 2026-07-29 (documentado formalmente 2026-07-31)
**Commits:** `7bfa165`, `3e574d7`

Migración de BD realizada (`responsable_user_id`). Asignación masiva y selector UI funcionando
en `main` sin perder responsables al cambiar de frente. Absorbido por
[`pdc-preparar-b1`](../pdc-preparar-b1/goal.md); el spec original
(`docs/superpowers/specs/2026-07-28-responsable-usuario-proyecto-design.md`) está implementado.
Ver también `ABSORBIDO.md` en este directorio.
