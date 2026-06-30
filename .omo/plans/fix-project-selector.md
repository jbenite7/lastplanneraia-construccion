# fix-project-selector - Work Plan

## TL;DR (For humans)

**What you'll get:** El proyecto "Aeropuerto Regional PC" (da_aeropuerto_pc) aparecerá en el selector de proyectos del usuario jbenitez.

**Why this approach:** La causa raíz es que falta un registro en la tabla `project_members` que asigne al usuario jbenitez (user_id=1) al proyecto da_aeropuerto_pc (project_id=75). No se requieren cambios de código.

**What it will NOT do:** No modificará el código del controlador, la estructura de la base de datos, ni creará nuevos usuarios.

**Effort:** Quick
**Risk:** Low - Solo es una inserción en una tabla de asignación
**Decisions to sanity-check:** Confirmar que el rol 'A' (Administrador) es el correcto para jbenitez en este proyecto

Your next move: Aprobar el plan para proceder con la ejecución.

---

> TL;DR (machine): Quick risk-low task: INSERT project_members row (user_id=1, project_id=75, role='A')

## Scope
### Must have
- Insertar registro en `project_members` para user_id=1, project_id=75, role='A'
- Verificar que el proyecto aparezca en el selector

### Must NOT have (guardrails, anti-slop, scope boundaries)
- No modificar código PHP
- No modificar esquema de base de datos
- No crear nuevos usuarios
- No modificar registros existentes en project_members

## Verification strategy
> Zero human intervention - all verification is agent-executed.
- Test decision: none (inserción directa, verificación manual)
- Evidence: .omo/evidence/task-1-fix-project-selector.sql

## Execution strategy
### Parallel execution waves
> Single wave - tarea atómica sin dependencias

### Dependency matrix
| Todo | Depends on | Blocks | Can parallelize with |
| --- | --- | --- | --- |
| 1 | None | 2 | None |

## Todos
> Implementation + Test = ONE todo. Never separate.
<!-- APPEND TASK BATCHES BELOW THIS LINE WITH edit/apply_patch - never rewrite the headers above. -->
- [ ] 1. Asignar usuario jbenitez al proyecto da_aeropuerto_pc
  What to do / Must NOT do: Ejecutar INSERT en project_members para user_id=1, project_id=75, role='A'. Usar INSERT IGNORE para idempotencia.
  Parallelization: Wave 1 | Blocked by: None | Blocks: 2
  References: 
    - database/patches/001_create_new_tables.sql:136-144 (schema project_members)
    - src/Controllers/Core/ProjectSelectorController.php:29-42 (query que verifica membresía)
  Acceptance criteria (agent-executable): 
    ```sql
    SELECT COUNT(*) FROM project_members WHERE project_id=75 AND user_id=1;
    -- Debe retornar 1
    ```
  QA scenarios: 
    - Happy: Ejecutar INSERT, verificar con SELECT que el registro existe
    - Failure: Si el usuario o proyecto no existen, el INSERT fallará con FK constraint
    Evidence .omo/evidence/task-1-fix-project-selector.sql
  Commit: N/A (cambio en base de datos, no en código)

- [ ] 2. Verificar que el proyecto aparece en el selector
  What to do / Must NOT do: Ejecutar la query exacta del ProjectSelectorController para user_id=1 y verificar que da_aeropuerto_pc aparece en los resultados
  Parallelization: Wave 2 | Blocked by: 1 | Blocks: None
  References:
    - src/Controllers/Core/ProjectSelectorController.php:29-42
  Acceptance criteria (agent-executable):
    ```sql
    SELECT p.ID, p.Proyecto_Proceso 
    FROM project_members pm
    INNER JOIN general_usuarios u ON u.id = pm.user_id
    INNER JOIN general_proyectos_procesos p ON p.ID = pm.project_id
    WHERE u.usuario = 'jbenitez' AND p.Base_de_Datos = 'da_aeropuerto_pc';
    -- Debe retornar: Id=75, Proyecto_Proceso='Aeropuerto Regional PC'
    ```
  QA scenarios:
    - Happy: Query retorna el proyecto con todos los filtros aplicados
    - Failure: Si algún filtro falla (Area, Activo, Acceso), el proyecto no aparecerá
    Evidence .omo/evidence/task-2-fix-project-selector.sql
  Commit: N/A (verificación)

## Final verification wave
> Runs in parallel after ALL todos. ALL must APPROVE. Surface results and wait for the user's explicit okay before declaring complete.
- [ ] F1. Verificar que jbenitez puede ver el proyecto en el selector (manual)
- [ ] F2. Confirmar que el proyecto es accesible ( Area=Pre-Construccion, Activo=1, Acceso=1)
- [ ] F3. Verificar que no hay duplicados en project_members

## Commit strategy
No se requiere commit - los cambios son en la base de datos, no en código fuente.

## Success criteria
1. El usuario jbenitez puede ver "Aeropuerto Regional PC" en el selector de proyectos
2. Al hacer clic en "Ingresar al Proyecto", accede correctamente al proyecto
3. No se rompió el acceso a otros proyectos existentes
