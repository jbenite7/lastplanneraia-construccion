---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-18
areas: [proceso]
fuente: goals/semana-fija-visual/goal.md
resumen: Que la prueba visual de /programacion-intermedia deje de fallar por el número de semana, que cambia solo con el calendario. Se fija la semana en el fixture, no…
---

# Frente: semana-fija-visual

## Objetivo

Que la prueba visual de `/programacion-intermedia` deje de fallar por el número de semana, que
cambia solo con el calendario. Se fija la semana **en el fixture**, no en la imagen: regenerar el
golden con la semana de hoy volvería a derivar mañana.

## Condición de hecho

La semana del escenario la fija el test y no el estado del proyecto; la zona del selector
desaparece del diff; lo que quede está explicado o encolado con evidencia; y si se regeneran los
goldens, hay antes/después aprobado por el usuario **antes** de firmar y una **mutación ejecutada**
que demuestre que la captura nueva sabe fallar. El gate `runtime` sigue `blocked` por otras causas
y **no se pretende ponerlo verde**.

## Archivos declarados

- `tests/browser/programacion-intermedia.visual.mjs`
- `docs/superpowers/specs/2026-08-11-semana-fija-visual-design.md`
- `docs/superpowers/plans/2026-08-11-semana-fija-visual.md`
- Condicionados a que el diff quede explicado: los dos `.png` del golden y sus `sha256` en
  `docs/design-system/manifests/programacion-intermedia.json`

## Contención

| Archivo | Commits hoy | Quién más lo declara |
|---|---|---|
| `tests/browser/programacion-intermedia.visual.mjs` | 0 | nadie |
| `tests/browser/support/session.mjs` | 0 | nadie |
| `docs/design-system/manifests/programacion-intermedia.json` | 0 | nadie |

Todo frío. El riesgo de roce no está aquí sino en `vocabulario-estados-cascada`, cuyo último paso
toca `programacion_intermedia.view.php` y el CSS de la leyenda: **publican ellos primero**. Este
frente se queda en `tests/browser/`; si acabara necesitando la vista o ese CSS, se para y se avisa.

## Cadena de herramientas

- `skill:coordinating-agent-sessions:coordinating-agent-sessions` — hay sesiones vivas y este
  frente depende del orden de publicación de otra.
- `skill:superpowers:brainstorming` + `skill:superpowers:writing-plans` — spec y plan con gate.
- `skill:superpowers:verification-before-completion` — nada se declara hecho sin salida real, y
  aquí incluye la mutación que pone la prueba en rojo.
- `mcp:Claude_Browser` — inspección del render servido por el contenedor de este worktree.

## Estado

Spec y plan escritos sobre `6dd69bb7`. **Enviados al gate; no se toca código sin aprobación.**

## Cierre

**Cerrado el 2026-08-19.** Verificado con salida real de hoy:

| Hecho | Medición |
|---|---|
| La semana la fija el test, no el estado del proyecto | `POST /context/week` con `SEMANA_DEL_GOLDEN`, y **comprueba la respuesta**: si el POST no devuelve OK, el test aborta con el motivo en vez de retratar la semana equivocada (`tests/browser/programacion-intermedia.visual.mjs:95`) |
| El escenario no depende de datos vivos | las filas se siembran con `Semanas_Inicio` fijos y las tres rutas de datos van interceptadas |
| Los dos goldens | **2 passed** a 1180×820 y 1440×900 |
| ¿Se regeneraron goldens? | **No.** El último commit sobre `tests/browser/__screenshots__/` es de otro frente, así que la cláusula de aprobación previa no aplica aquí |

**La mutación ejecutada, que era la parte que no se podía suponer:** con un
`letter-spacing: 3px !important` metido a propósito en la tabla de Intermedia, **los dos goldens
fallan**; al revertirlo, los dos vuelven a pasar. La captura sabe fallar — comprobado, no afirmado.

**Límite declarado y respetado:** el gate `runtime` sigue `blocked` por otras causas y este frente
**no pretendía ponerlo verde**.

