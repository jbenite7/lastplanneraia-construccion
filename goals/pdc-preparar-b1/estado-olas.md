# Estado de las olas — tablero de relevos

**Para qué existe.** Las tareas de este goal se ejecutan en sesiones independientes y varias tienen que
esperar a otra. Preguntarle a una sesión «¿ya acabaste?» no es fiable: puede no responder, puede haberse
caído, o puede creer que acabó. Este archivo convierte esa pregunta en algo **comprobable**.

**Cómo se usa.**

- **Al cerrar tu tarea** —y solo cuando su condición de hecho esté cumplida y verificada— cambia tu fila a
  `HECHO`, escribe el `sha` del commit que la cierra y la fecha, y commitea este archivo junto con tu
  trabajo. Si tu tarea queda a medias, escribe `PARADA` y el motivo en una línea: a quien espera le sirve
  más saber que no vas a llegar que seguir esperando.
- **Antes de empezar la tuya**, comprueba a tu predecesora así:

```bash
git fetch origin --quiet && git show origin/main:goals/pdc-preparar-b1/estado-olas.md
```

Lee la fila de tu predecesora. Si dice `HECHO`, estás autorizada. Si dice `PENDIENTE` o `EN CURSO`,
vuelve a mirar más tarde. Si dice `PARADA`, **no arranques**: avísale al usuario, porque el plan cambió.

**Regla que no se rompe:** nadie marca `HECHO` la fila de otra sesión, y nadie se marca `HECHO` a sí misma
sin haber corrido su verificación. Una fila mentida hace arrancar a la siguiente sobre un supuesto falso,
que es peor que no tener este archivo.

---

## Ola 1 — lo que el comité comprometió

| # | Tarea | Espera a | Estado | Commit | Fecha |
|---|---|---|---|---|---|
| 1 | Tablero de vencimientos (B2, look-ahead) | — arranca ya | PENDIENTE | | |
| 2 | Impacto al recargar + tamiz del presupuesto | — arranca ya | PENDIENTE | | |
| 3 | Cierre pre-lanzamiento (los cuatro pendientes) | — arranca ya | PENDIENTE | | |
| 4 | Despliegue a producción | 1, 2 y 3 · **+ comunicado enviado + autorización explícita del usuario** | PENDIENTE | | |

## Ola 2 — lo que el uso va a exigir

| # | Tarea | Espera a | Estado | Commit | Fecha |
|---|---|---|---|---|---|
| 5 | Equipo alquilado vs comprado | 4 | PENDIENTE | | |
| 6 | Ayuda dentro de la aplicación | 1 y 2 (necesita las pantallas terminadas) | PENDIENTE | | |
| 7a | Re-matching al reprogramar (B2, 2ª mitad) | 1 (comparten `PlanFechasService`) | **HECHO** | `b590b5e`, `13e6e31`, `b2859e3`, `c254955`, `87fa7a3` | 2026-07-29 |
| 7b | Los cuatro diferidos de A4.1 (configuración de pasos) | 7a (misma superficie) | **HECHO (3 de 4)** | `efe8d5e`, `20d6acf`, `c725fc7` | 2026-07-29 |

### Nota sobre 7a — la medición recortó el spec a la mitad

El primer entregable era medir, no construir, y cambió el alcance:
[`evidence/medicion-rematching-2026-07-29.md`](evidence/medicion-rematching-2026-07-29.md).

**Ya existía** (retirado del alcance, conservado como regresión): detectar el desfase
(`PlanFechasService::desfases()`), avisarlo en la pantalla del plan, no recalcular solo, conservar
`fecha_real` al recalcular, y no reamarrar solo un frente borrado.

**Apareció un bug que el spec no preveía:** «Recalcular» no recogía la fecha nueva del frente
—`amarres()` lee una copia congelada del ancla—, así que el desfase no se podía aplicar nunca y el
botón que la pestaña «Desfases» ofrecía no arreglaba lo que esa misma pestaña denunciaba. Corregido
en `13e6e31`.

**Construido:** simular la reprogramación sin escribir, aplicarla solo sobre lo confirmado, el panel
de delta con «Aplicar»/«Cancelar», y el aviso en el tablero de vencimientos.

**Verificado:** `tests/test_pdc_v2_reprogramacion.php` (nuevo), la regresión de la zona (9 tests PHP),
phpstan limpio, 267 tests de vitest, y el e2e `tests/browser/pdc-v2-plan.spec.mjs` con el recorrido
completo en navegador (simular no escribe · cancelar no escribe · aplicar corre 21 días · el conteo
de «Desfases» baja a 0). Rojo preexistente no relacionado, comprobado también sobre `1a75b19`:
`tests/browser/pdc-v2-sin-scroll-x.spec.mjs`.

### Nota sobre 7b — tres de cuatro diferidos

| # | Diferido | Estado |
|---|---|---|
| 2 | Copiar la configuración entre obras | **Hecho** (`efe8d5e`). Copia puntual, no vínculo vivo; la pantalla enseña qué trae y marca si el origen está a medias |
| 4 | Duraciones del catálogo editables | **Hecho** (`20d6acf`). Solo las filas que la obra usa, con aviso permanente de que son de la empresa. Recorte: el upsert ya existía en `/contratos`; lo que faltaba era llegar desde el PDC v2, con el permiso de reglas y recalculando |
| 1 | Listas de pasos por modalidad | **NO se construye** — precondición incumplida. Ver [`evidence/listas-por-modalidad-no-se-construye.md`](evidence/listas-por-modalidad-no-se-construye.md). **Pendiente del usuario:** preguntar a las dos obras |
| 3 | Historial de versiones | **Hecho** (`c725fc7`). Tabla de solo anexar; restablecer también deja rastro |

**Verificado:** 14 tests PHP en verde (3 nuevos), phpstan limpio, 267 de vitest, y 3 e2e en
`tests/browser/pdc-v2-pasos.spec.mjs` (2 nuevos, en navegador contra el contenedor servido).
Cero regresión comprobada sobre Da Porto: sigue sin configurar y con los siete pasos por defecto.
De paso se corrigió que el reseteo del sandbox e2e no limpiaba `pdc_proyecto_pasos`.

## Ola 3 — lo grande

| # | Tarea | Espera a | Estado | Commit | Fecha |
|---|---|---|---|---|---|
| 8 | Subpaquetes + flujo de caja | 7 | PENDIENTE | | |
| 9 | Torre de Control (B3) | 1 | PENDIENTE | | |
| 10 | Retiro del PDC viejo (C1) | 4 · **+ una obra trabajando de verdad en producción** | PENDIENTE | | |

---

## Dos avisos para quien espera

**El nº 4 y el nº 10 no se autorizan solos.** Tienen una puerta humana además de la técnica: el
despliegue necesita que el comunicado haya salido y que el usuario lo autorice en el momento; el retiro
necesita una obra trabajando de verdad. Que su predecesora diga `HECHO` **no basta** para arrancarlos.

**El nº 1 y el nº 3 se rozan en un punto:** los 25 paquetes sin `duracion_ref` los resuelve la sesión del
tablero, no la del cierre. Si eres la del cierre, sáltate ese punto.
