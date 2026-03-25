# Last Planner - Estados Operativos de Actividades (Programacion Semanal)

## Objetivo

Definir una clasificacion unica para la semana de compromisos y su fase de calificacion.

Campos base usados en la DB (`project_programacion_semanal`):

- `Compromiso`
- `Ejecutado_Real`
- `PAC`
- `Critica`
- `Atrasada`
- `Prog_Sin_Restricciones_100`
- `Categoria_CNC`
- `CNC`
- `Activa`
- fase de semana en `project_semanas_activas.Semanal_Confirmada`

Definicion de criticidad:

- **Critica** = actividad en ruta critica (`Critica = 1`).
- El campo `Atrasada` no define criticidad; solo refleja desempeno.

## Matriz Canonica por Fase

### Fase Programacion (`Semanal_Confirmada = 0`)

1. `prog-bloqueo-critico-sin-compromiso`
   - Condicion: `Compromiso <= 0` + `Prog_Sin_Restricciones_100 = 1` + `Critica = 1`.
   - Accion: escalar en comite diario, asignar responsable y fecha de destrabe.

2. `prog-sin-compromiso`
   - Condicion: `Compromiso <= 0` o `Responsable_AIA` / `Sub_Contratista` vacios.
   - Accion: bloquear cierre semanal hasta comprometer > 0 y completar las asignaciones obligatorias.

3. `prog-lista-para-confirmar`
   - Condicion: `Compromiso > 0` + `Responsable_AIA` y `Sub_Contratista` definidos.
   - Accion: validar confiabilidad y confirmar compromisos.

### Fase Calificacion (`Semanal_Confirmada = 1`)

1. `cal-sin-calificar`
   - Condicion: `Compromiso <= 0` o `Ejecutado_Real` vacio.
   - Accion: completar dato real para evaluar cumplimiento.

2. `cal-incumplida-critica`
   - Condicion: `Ejecutado_Real < Compromiso` + `Critica = 1`.
   - Accion: ejecutar recuperacion en camino critico y registrar CNC obligatoria.

3. `cal-incumplida`
   - Condicion: `Ejecutado_Real < Compromiso` + `Critica = 0`.
   - Accion: gestionar recuperacion y registrar CNC obligatoria.

4. `cal-cumplida-control`
   - Condicion: `Ejecutado_Real >= Compromiso`.
   - Accion: mantener control y estandarizar buenas practicas.

   - Condicion: `Activa` en `NA/0`.
   - Accion: excluir de semaforo operativo semanal.
