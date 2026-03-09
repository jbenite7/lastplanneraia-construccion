# Last Planner - Estados Operativos de Restricciones (Programacion Intermedia)

## Objetivo

Definir una taxonomia unica para seguimiento de restricciones en la ventana de 6 semanas.

Variables operativas:

- `SI`: `Semanas_Inicio`
- `ER`: `Estado_Restricciones` (escala `0..1`)
- `EJ`: `Ejecutado` (escala `0..1`)
- `CR`: `Ruta_Critica`

Umbral de liberacion:

- Una actividad se considera liberada cuando `ER >= 0.999`.

## Matriz Canonica de Estados

### P1 (Choque operativo)

1. `blocked-overdue-critical`
   - Regla: `SI <= 0`, `EJ = 0`, `ER < 0.999`, `CR = si` y/o senal de vencida.
   - Gestion: escalamiento inmediato en reunion diaria.

2. `blocked-overdue`
   - Regla: `SI <= 0`, `EJ = 0`, `ER < 0.999`, no critica.
   - Gestion: destrabe operativo en 24-48h.

3. `blocked-due`
   - Regla: `SI = 0`, `EJ = 0`, `ER < 0.999`.
   - Gestion: no cerrar jornada sin plan de arranque.

4. `execution-blocked`
   - Regla: `0 < EJ < 0.999`, `ER < 0.999`.
   - Gestion: mitigar riesgo de retrabajo y perdida de continuidad.

### P2 (Prevencion inmediata)

1. `alert-1-week`
   - Regla: `SI = 1`, `EJ = 0`, `ER < 0.999`.

2. `alert-2-3-weeks`
   - Regla: `SI in [2..3]`, `EJ = 0`, `ER < 0.999`.

### P3 (Lookahead y control)

1. `alert-4-6-weeks`
   - Regla: `SI in [4..6]`, `EJ = 0`, `ER < 0.999`.

2. `liberated-control`
   - Regla: `ER >= 0.999` (iniciada o no iniciada dentro de 6 semanas).

## Criterio de consistencia

La misma clasificacion debe regir:

- Color de fila en tabla.
- Leyenda/chips y contadores.
- Filtros por estado.
- Exportables (corte de restricciones).
