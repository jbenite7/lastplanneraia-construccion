---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-19
areas: [lps]
fuente: goals/semanal-fondo-por-matiz/goal.md
resumen: Llevar el fondo de fila de /programacion-semanal del sistema propio de cubos de alerta al modelo de tres canales ya publicado: el matiz dice qué estado es, el…
---

<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: semanal-fondo-por-matiz

## Fase del plan
Plan: docs/superpowers/plans/2026-08-19-semanal-fondo-por-matiz.md
Fase: ?
Sha verificado: ?
Presupuesto: ?

## Objetivo
Llevar el fondo de fila de `/programacion-semanal` del sistema propio de cubos de alerta al modelo
de tres canales ya publicado: **el matiz dice qué estado es, el filete dice cuán grave, el orden
desempata**. Su contrato y su chip ya se arreglaron y publicaron en `c766a338`; lo que falta es el
fondo, que es el único sitio donde sobreviven las colisiones.

## Condición de hecho
Los cinco estados de **cada fase** pintan cinco fondos distintos, medidos con **color computado
contra computado** a 1180×820 dark por sesión real; el filete aparece solo en `urgent` y
`attention`; y ningún par de estados de la misma fase comparte fondo.
Verificación: `bash scripts/publicar.sh --solo-verificar`

## El problema, medido antes de arrancar

`WEEKLY_ALERT_MODEL` (`public/js/modules/programacion_semanal/hot.js:117-191`) asigna a cada estado
una clase de **cubo de alerta**, y **diez estados colapsan en cinco cubos**:

| Estado | Cubo actual |
|---|---|
| `prog-bloqueo-critico-sin-compromiso` | `ps-alert-critical-route` |
| `prog-ejecucion-con-restricciones` | `ps-alert-high` |
| `prog-condiciones-pendientes` | `ps-alert-medium` |
| **`prog-sin-compromiso`** | **`ps-alert-medium`** ← colisión en fase Programación |
| `prog-lista-para-confirmar` | `ps-alert-control` |
| `cal-incumplida-critica` | `ps-alert-critical-route` |
| `cal-incumplida` | `ps-alert-medium` |
| **`cal-sin-calificar`** | **`ps-alert-medium`** ← colisión en fase Calificación |
| `cal-cumplida-control` | `ps-alert-control` |
| `cal-tnp` | `ps-alert-tnp` |

**Las dos colisiones son exactamente las que ya se desempataron en el contrato y en el chip**
(«Por Comprometer» → violeta, «Sin Calificar» → gris, publicado en `37479689`). El fondo de la fila
no se enteró porque no consume matiz: consume cubo. **Hoy el chip y la fila de la misma actividad
dicen cosas distintas.**

Las repeticiones **entre** fases (`critical-route` en las dos, `control` en las dos) son inocuas:
`stateMachine.js:58` resuelve una fase u otra según `semanalConfirmada`, así que nunca conviven.

## Posture
- **No tocar los hex de `--ds-state-tint-*`.** Ocho anclas, cerradas por test.
- **No regenerar ningún golden sin aprobación visual explícita del usuario**, por su nombre.
- **No ablandar ningún test**: si cambia, cambia declarando qué mide ahora.
- **No tocar `/programacion-intermedia` ni `/programa-general`**, ya cerrados o en cola ajena.
- **No tomar el contenedor compartido sin ventana pedida a la coordinadora.**
- Sin dependencias nuevas.

## Leer primero
- `docs/design-system/state-semantics.json` — módulo `programacion-semanal`, con los matices ya
  desempatados y `axisRules`.
- `public/js/modules/programacion_semanal/hot.js` — `WEEKLY_ALERT_MODEL` y `statePresentation`.
- `public/js/modules/programacion_semanal/stateMachine.js:58` — cómo se resuelve la fase.
- `public/css/design-system/components/severity-rail.css` — la primitiva del filete.
- `goals/ds-f1a-estados-severidad/goal.md` — el frente hermano ya publicado, y sus dos trampas.

## Archivos declarados
goals/semanal-fondo-por-matiz/**, docs/superpowers/specs/*-semanal-fondo-por-matiz*,
docs/superpowers/plans/*-semanal-fondo-por-matiz*, public/css/programacion-semanal.css,
public/js/modules/programacion_semanal/hot.js, public/css/styles.css,
tests/design-system/**, tests/browser/__screenshots__/**,
memoria/trampas/important-invierte-el-orden-de-capas.md, memoria/log.md

> Los dos últimos se añadieron el 2026-08-19 **por autorización expresa de la coordinadora**, por
> nombre exacto y no como glob abierto, para escribir la trampa de capas medida al convertir.

## Contención — medida el 2026-08-19 antes de arrancar
- `public/css/programacion-semanal.css` → **0 commits hoy**
- `public/js/modules/programacion_semanal/stateMachine.js` → **0**
- `public/js/modules/programacion_semanal/hot.js` → 1, mío (`37479689`)
- `public/css/styles.css` → 3, míos
- **Ningún otro frente declara archivos de Semanal.** `estados-fuera-de-ventana` declara
  `estado_programa_general.php`, `LpsService.php` y `ds-f1a-escala-estado.*`: cero solape.
- **Archivo caliente: `docs/design-system/state-semantics.json`**, tocado hoy por `ds-f1a-estado` y
  por mí. Si este frente lo edita, se avisa a la coordinadora antes.

## Por qué no lleva spec propia
La dirección ya está decidida, aprobada y **publicada**: el modelo de tres canales y su regla
`axisRules` viven en el contrato desde `c766a338`, y este frente no decide nada de vocabulario ni de
niveles — solo lleva una superficie más al modelo que ya existe. La spec que lo gobierna es
`docs/superpowers/specs/2026-08-19-estados-severidad-contrato-design.md`. Sí lleva **plan**, porque
el gate lo exige y porque hay goldens de por medio.

## Hallazgo encargado
La coordinadora pidió anotar, si aparecen al convertir, **cuántas filas de Semanal serían «detenido
por otro»** — el dato que ayuda a decidir el `r0` de Programa General, hoy en la mesa de Felipe.

## Archivos de este goal
- [[docs/superpowers/plans/2026-08-19-semanal-fondo-por-matiz]]
- [[docs/superpowers/specs/2026-08-19-estados-severidad-contrato-design]]
- [[goals/ds-f1a-estados-severidad/goal]] · [[memoria/goals/estado]]
