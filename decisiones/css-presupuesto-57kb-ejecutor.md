<!-- cas:cita-textual — registro de hallazgos: cita comandos defectuosos tal como se dieron -->
# Decisiones pendientes — frente css-presupuesto-57kb

<!-- Una entrada por decisión, con estos campos:
**Qué se decide** · **Qué se midió** (con sha) · **Opciones reales** · **Recomendación** ·
**Qué quedó saltado** -->

## 2026-08-12 · Titularidad del frente compartida con cc2c531d — escalada
**Qué se decide:** qué sesión se queda el frente: ed7ffb0f (esta, encargo directo del usuario) o
cc2c531d (viva, declara el mismo frente y `docs/design-system/runtime-measurements/`).
**Qué se midió:** `.claude/sesiones.md` sobre `3c80a002`: ambas filas `viva` con el mismo frente.
**Opciones reales:** (a) ed7ffb0f entrega y cc2c531d descarta; (b) al revés; (c) fusionar hallazgos.
**Recomendación:** (a) — la atribución de ed7ffb0f ya cierra exacta (informe redactado en su worktree).
**Qué quedó saltado:** nada; el informe se redactó en worktree propio, sin publicar. Escalado a la
coordinadora por mensaje 2026-08-12 ~15:58Z.

## 2026-08-12 · Premisa del encargo corregida (dato equivocado, no consulta)
El goal afirmaba que el artefacto de CI guarda solo `assetInventorySha256`. Medido sobre la corrida
`31566518358`: los `sample-N.json` traen `provenance.assets` completo. Corregido en el informe;
no se montó runtime nuevo porque la medición instrumentada con sha ya existía (`c014874c`).

---

## Cierre relatado por la coordinadora — 2026-08-18

La escalada de titularidad quedó resuelta por el usuario el 2026-08-12: ed7ffb0f entregó (informe
de atribución publicado); cc2c531d descartó su copia. El usuario confirmó hoy el cierre. Sin
pendientes en este archivo.
