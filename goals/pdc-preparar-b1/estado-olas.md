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
| 1 | Tablero de vencimientos (B2, look-ahead) | — arranca ya | HECHO | `60f8bfe` | 2026-07-29 |
| 2 | Impacto al recargar + tamiz del presupuesto | — arranca ya | PENDIENTE | | |
| 3 | Cierre pre-lanzamiento (los cuatro pendientes) | — arranca ya | HECHO | `88c37b8` | 2026-07-29 |
| 4 | Despliegue a producción | 1, 2 y 3 · **+ comunicado enviado + autorización explícita del usuario** | PENDIENTE | | |

## Ola 2 — lo que el uso va a exigir

| # | Tarea | Espera a | Estado | Commit | Fecha |
|---|---|---|---|---|---|
| 5 | Equipo alquilado vs comprado | 4 | PENDIENTE | | |
| 6 | Ayuda dentro de la aplicación | 1 y 2 (necesita las pantallas terminadas) | PENDIENTE | | |
| 7 | Configuración de pasos + re-matching | 1 (comparten `PlanFechasService`) | PENDIENTE | | |

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

**Qué cubre exactamente el `HECHO` del nº 3 (2026-07-29).** Sus puntos 1, 3, 4 y 5 están cerrados y
verificados con salida real de comandos, en
[`evidence/cierre-prelanzamiento-2026-07-29.md`](evidence/cierre-prelanzamiento-2026-07-29.md). Su
punto 2 (los 25 sin `duracion_ref`) es del nº 1, no de esta fila.

**Su punto 6 quedó sin contenido, por decisión explícita de Felipe.** Tomás no reportó hallazgos del
piloto, así que el hueco reservado se cierra vacío en vez de mantener la fila abierta esperándolo. Lo
que hay en [`hallazgos-piloto.md`](hallazgos-piloto.md) son cuatro observaciones propias de la sesión,
cada una con decisión: una arreglada, dos diferidas a la Ola 2, una descartada con motivo. **No es un
triage del piloto real.** Si Tomás reporta después, entra por la Ola 2 y no reabre esta fila.

**Dos cosas que el nº 4 tiene que saber antes de desplegar**, y que no se leen en la palabra `HECHO`:

- **La brecha del motor no la vigila nadie.** `test_pdc_v2_brecha_daporto` está rojo con causa escrita:
  su estado canónico (proyecto 73, versión 292) desapareció al reimportarse el presupuesto de Da Porto.
  Se dejó rojo a propósito en vez de repuntarlo a la versión viva, que lo habría dejado verde y hueco.
- **Dos e2e de Da Porto (`pdc-v2-modalidades`, `pdc-v2-sin-scroll-x`) alternan verde y rojo según lo
  que la obra haya cargado ese día.** Si los ves rojos, es el hallazgo H1, no una regresión.

**El nº 1 y el nº 3 se rozaban en un punto, y ya está resuelto.** Los paquetes sin `duracion_ref` eran de
la nº 1, no de la nº 3. **Cerrado el 2026-07-29:** no eran 25 sino 42, y ya recibían fechas solos por la
mediana de su tipo — ver [`evidence/paquetes-sin-duracion-ref.md`](evidence/paquetes-sin-duracion-ref.md).
El «25» venía del spec y era una cifra vieja: quien lo lea allí, que se fíe de esta medición.
