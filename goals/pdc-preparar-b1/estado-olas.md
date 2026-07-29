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
| 1 | Tablero de vencimientos (B2, look-ahead) | — arranca ya | HECHO | `035f1db` | 2026-07-29 |
| 2 | Impacto al recargar + tamiz del presupuesto | — arranca ya | PENDIENTE | | |
| 3 | Cierre pre-lanzamiento (los cuatro pendientes) | — arranca ya | PENDIENTE | | |
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

**El nº 1 y el nº 3 se rozan en un punto:** los paquetes sin `duracion_ref` los resuelve la sesión del
tablero, no la del cierre. Si eres la del cierre, sáltate ese punto. **Cerrado el 2026-07-29:** no eran 25
sino 42, y ya recibían fechas solos por la mediana de su tipo — ver `evidence/paquetes-sin-duracion-ref.md`.
