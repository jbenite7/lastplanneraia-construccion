<!-- cas:cita-textual — registro de hallazgos: cita comandos defectuosos tal como se dieron -->
# Decisiones pendientes — frente bug-coloreado-severidad

<!-- Una entrada por decisión, con estos campos:
**Qué se decide** · **Qué se midió** (con sha) · **Opciones reales** · **Recomendación** ·
**Qué quedó saltado** -->

---

**Qué se decide.** Si `state-semantics.json` o `docs/matriz-severidad-cajon-contextual-lps.md`
es la autoridad de severidad para Programación Intermedia.
**Qué se midió** (sobre `8841c04e`, base `6abe2436`). Los dos documentos existen y discrepan en al
menos cuatro estados de PI: `blocked-overdue` (attention vs urgent), `blocked-due` (critical-si-RC
vs attention), `alert-1-week` (attention vs urgent) y `alert-4-6-weeks` (normal vs attention). El
segundo es del 2026-05-22, tiene paleta clara y alcance «Cajón Contextual»; el primero es el
contrato ejecutable del design system.
**Opciones reales.** (a) derogar la matriz y dejarla como histórico; (b) reconciliarlas; (c) acotar
explícitamente la matriz al cajón y decir que no gobierna la tabla.
**Recomendación.** (c), y con nota de derogación parcial: es la que no reabre una decisión de
producto para cerrar un hueco documental.
**Qué quedó saltado.** Nada: el diagnóstico las cita a las dos sin elegir.

---

**Qué se decide.** Si la excepción crítica `states-feedback.css:162` se quiere de verdad.
**Qué se midió** (sobre `8841c04e`). Hoy no se aplica nunca: `legacy-bridge.css:104-142` gana desde
`legacy-overrides` con `:where()` y el matiz va después. Leído con `CSS.getMatchedStylesForNode`
sobre el chip real, no razonado sobre la hoja.
**Opciones reales.** (a) copiar la excepción al puente — pondría en rojo
`tests/browser/ops-state-chip-hue.mjs`, que asierta lo contrario, y colapsaría los tres estados
`high/now` de PI en un solo `#431414`; (b) retirar la excepción de la capa canónica y aceptar que el
matiz gana siempre; (c) dejarla y documentar que es letra muerta.
**Recomendación.** (b), pero es decisión de DS-F1: (a) empeora justo el síntoma que abrió el frente.
**Qué quedó saltado.** No se tocó ninguna de las dos hojas ni el test. Lista de bloqueo
incondicional: cambiar lo que mide una prueba.

---

**Qué se decide.** Si el frente se re-declara con plan y fase.
**Qué se midió.** `cas-frente.sh --fase "Tanda 1"` falló: `cas_fases` solo reconoce encabezados
`Task N` o `Fase N`, y `docs/superpowers/plans/2026-08-19-bug-coloreado-severidad.md` usa
`## Tanda 1 — …`. El frente quedó con `Plan: ?` y `Fase: ?`.
**Opciones reales.** (a) renombrar los encabezados del plan; (b) dejarlo en `?`; (c) `--sin-plan`,
que sería falso.
**Recomendación.** (a), pero el plan no es artefacto de este frente: no lo edito sin autorización.
**Qué quedó saltado.** Nada del diagnóstico; solo el trámite del registro.
