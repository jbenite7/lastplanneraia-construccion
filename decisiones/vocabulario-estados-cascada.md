# Decisiones pendientes — frente vocabulario-estados-cascada

<!-- Una entrada por decisión, con estos campos:
**Qué se decide** · **Qué se midió** (con sha) · **Opciones reales** · **Recomendación** ·
**Qué quedó saltado** -->

Censo completo y razonamiento:
`docs/superpowers/specs/2026-08-11-vocabulario-estados-cascada-design.md`.

---

## D-VOC-1 · ¿Un vocabulario único para toda la cascada, o tres explícitamente distintos?

**Qué se decide.** Si los tres vocabularios se funden en uno solo que atraviese Programa General,
Intermedia y Semanal, o si se acepta que cada eslabón nombre un eje distinto (avance / alistamiento
/ compromiso) y lo que se unifica es solo la *forma* de nombrarlos.

**Qué se midió** (sobre `de02471a`, contrato `docs/design-system/state-semantics.json` + literales
del código). 35 cadenas distintas visibles en los cuatro módulos de la cascada. Siete momentos del
ciclo se nombran dos o tres veces: p. ej. «le toca y no puede arrancar» es `Debe Iniciar` (PG),
`Inicio por Habilitar` (PI) y `Condiciones Pendientes` (PS).

**Opciones reales.**
1. **Vocabulario único de ~7 estados** para toda la cascada. Máxima reducción (35 → ~10 con los
   solo-código). Cambia el idioma de tres pantallas a la vez para la obra.
2. **Tres ejes declarados, un vocabulario por eje**, sin solapes entre ellos. Reducción media;
   cada pantalla sigue hablando de lo suyo, pero deja de haber sinónimos dentro de un mismo eje.
3. **Solo higiene**: cerrar desviaciones y quitar duplicados, sin tocar nombres aprendidos.

**Recomendación.** La 2. Los tres ejes son reales en LPS —avance físico, liberación de
restricciones y confiabilidad del compromiso son tres cosas distintas y el `GLOSARIO.md` las
distingue—, así que fundirlos en uno perdería información. Lo que sobra no son los ejes: son los
sinónimos dentro de cada uno.

**Qué quedó saltado.** Cualquier renombrado de A, B o C. Solo se ejecuta la higiene descrita en
D-VOC-2, que no elige vocabulario.

---

## D-VOC-2 · La columna `Estado` de Programa General está persistida

**Qué se decide.** Si el vocabulario A puede renombrarse, dado que sus valores no son solo
etiquetas de pantalla: `pg_calculate_status()` los escribe en la columna `Estado` de
`{prog_consolidado}` en cada guardado.

**Qué se midió** (sobre `de02471a`). `src/Legacy/guardar_programacion_intermedia.php:361` hace
`UPDATE ... SET Semanas_Inicio = ?, Estado = ?`. Los siete valores (`Actividad Futura`, `Debe
Iniciar`, `En Curso`, `Atrasada`, `Terminada`, `Sin Datos`, `Capítulo`) los leen además
`LpsService`, `GeneralApiController`, `SemanalApiController`, `ProgramChangeDetector`,
`ReportProcessor` y `tests/test_weekly_governance.php`.

**Opciones reales.**
1. Renombrar solo la etiqueta y dejar el valor guardado como está (introduce una capa de
   traducción: **suma** un vocabulario en vez de restarlo).
2. Renombrar valor y etiqueta con migración de datos (`UPDATE` sobre histórico, dry-run + respaldo
   + gate de Plannotator según `docs/global-tables-architecture.md`).
3. No tocar A.

**Recomendación.** La 3 en esta pasada, y la 2 como frente propio si D-VOC-1 sale «unificar». La 1
está descartada: contradice la condición de cierre del frente, que exige *menos* términos.

**Qué quedó saltado.** Todo el vocabulario A.

---

## D-VOC-3 · `Bloqueado` en Programa General Actualizar

**Qué se decide.** Si `Bloqueado` (solo en `programa-general-actualizar`) se conserva o se absorbe
en el `Debe Iniciar` / `Con Alerta Restricciones` de Programa General.

**Qué se midió** (sobre `de02471a`). El contrato declara para `programa-general-actualizar` seis
estados: cinco son idénticos a los de PG y el sexto es `Bloqueado`, que no existe en PG. Es la
única divergencia entre dos pantallas que muestran la misma tabla.

**Opciones reales.** (1) Absorberlo y quedar en cinco. (2) Añadirlo también a PG y quedar en dos
listas iguales. (3) Dejarlo.

**Recomendación.** La 1: dos vistas de la misma tabla no deberían clasificar distinto. Pero es un
término que la obra ve, así que no se toca sin el visto.

**Qué quedó saltado.** `views/programa-general-actualizar/programaGeneralActualizar.view.php` y su
entrada en el contrato.

---

## D-VOC-4 · Los tres estados que solo existen en código

**Qué se decide.** Si `Control` (PI), `Capítulo` (PI y PG) y `Programada Manualmente` (PS) se
declaran en el contrato o se retiran de la interfaz.

**Qué se midió** (sobre `de02471a`). Los tres se muestran al usuario pero ninguno está en
`state-semantics.json`; `ops-state-contract.test.mjs` exceptúa `neutral` a mano por eso.
`Capítulo` además es un valor persistido (ver D-VOC-2), no un estado de interfaz.

**Recomendación.** `Capítulo` no es un estado: es un tipo de fila, y mezclarlo con los estados es
parte de por qué hay 35 términos. Separarlo del eje de estado sería la resta más limpia del
frente — y toca datos guardados, así que se consulta.

**Qué quedó saltado.** Los tres términos.
