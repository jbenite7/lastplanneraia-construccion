# Revisión de queries SQL — project_id y Unique ID

Realizar una revisión sistemática de todos los queries SQL de la app para garantizar que incluyan `WHERE project_id = ?` explícito (defense-in-depth sobre la auto-inyección existente), corregir 3 vulnerabilidades críticas donde la auto-inyección no funciona (INSERT...SELECT y UNION dinámico), migrar la tabla `cambios` a global con `project_id`, y documentar el estado de Unique IDs / scoped IDs por tabla.

- **Fact sheet**: `goals/revision-queries-project-id/facts.md`
- **Plan detallado**: `goals/revision-queries-project-id/plan.md`

## Done condition

- [ ] Tabla `cambios` migrada a global con `project_id`
- [ ] 3 vulnerabilidades críticas corregidas (`_pdc_functions.php` INSERT...SELECT x2, `verificarCICActualizada.php` UNION)
- [ ] Todos los API Controllers tienen `WHERE project_id = ?` explícito (Subcontratistas, Profesionales, ControlCambios, CNC, CNP, General, Semanal)
- [ ] Todos los Legacy scripts tienen `WHERE project_id = ?` explícito (nueva_semana, eliminar_semana, modificar_sem_estado, actualizarEjecucion, _pdc_functions, autoprogramar, guardar_programacion_intermedia)
- [ ] Views actualizadas (CNP.view, programa-general-actualizar, programacion_semanal.view)
- [ ] Admin panel corregido (Project.php línea 178)
- [ ] Services verificados (ProjectProfessionalsSyncService, SemiAutoService)
- [ ] Documentación de Unique ID / scoped ID por tabla completada
- [ ] Playwright tests ejecutados en todos los workflows
- [ ] Code review completado y desplegado a pruebas + producción