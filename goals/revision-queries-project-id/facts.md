# Facts — Revisión de queries SQL (project_id y Unique ID)

- SubcontratistasApiController: agregar WHERE project_id = ? explícito en todos los SELECT/UPDATE/DELETE
- ProfesionalesApiController: agregar WHERE project_id = ? explícito en todos los SELECT/UPDATE/DELETE
- ControlCambiosApiController: agregar WHERE project_id = ? explícito en todos los queries (previa migración de tabla cambios a global con project_id)
- CncApiController y CnpApiController: agregar WHERE project_id = ? explícito en queries que solo filtran por Semana
- Legacy scripts (src/Legacy/): agregar WHERE project_id = ? explícito donde hoy dependen de auto-inyección (nueva_semana, eliminar_semana, _pdc_functions, verificarCIC, etc.)
- Vistas (views/): agregar WHERE project_id = ? explícito en queries de programa-general-actualizar, programacion-semanal, CNP.view
- Admin panel (admin/src/Models/Project.php: línea 178): agregar filtro project_id al SELECT * FROM cualquier tabla
- Services: verificar que todos los queries en src/Services/ tengan WHERE project_id = ? explícito, incluyendo ProjectProfessionalsSyncService e IndirectCostProcessor
- Migrar tabla cambios a tabla global con columna project_id (siguiendo patrón de global_tables architecture)
- Documentar qué tablas globales tienen scoped ID (project_id + secuencial) y cuáles solo confían en auto-increment, con recomendación de migración
- Ejecutar tests Playwright cubriendo todos los workflows: /programa-general/, /programacion-intermedia/, /programacion-semanal/, /cnp/, /cnc/, /cic/, /profesionales/, /subcontratistas/, /listado-actividades/, /contratos/, /pdc/
- Code review exhaustivo de todos los cambios antes de deploy