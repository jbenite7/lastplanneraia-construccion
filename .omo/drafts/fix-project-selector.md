---
slug: fix-project-selector
status: awaiting-approval
intent: clear
pending-action: write .omo/plans/fix-project-selector.md
approach: Insertar registro en project_members para asignar usuario jbenitez al proyecto da_aeropuerto_pc (Id=75)
---

# Draft: fix-project-selector

## Components (topology ledger)
| id | outcome | status | evidence |
|----|---------|--------|----------|
| DB | Insertar registro en project_members | active | Verificación SQL confirmó que falta la asignación |
| Code | No se requiere cambios en código | active | ProjectSelectorController query es correcto |

## Open assumptions (announced defaults)
| assumption | adopted default | rationale | reversible? |
|------------|-----------------|-----------|-------------|
| Usuario es jbenitez (user_id=1) | Confirmado por查询 | Único usuario con "Benitez" en nombre | Sí |
| Rol = 'A' (Administrador) | Adoptado | El usuario tiene rol Admin en otros proyectos | Sí |
| Proyecto Id=75 | Confirmado | da_aeropuerto_pc tiene Id=75 | Sí |

## Findings (cited - path:lines)

### Causa Raíz
- **Usuario:** jbenitez (user_id=1) - Juan Felipe Benitez Ramos
- **Proyecto:** da_aeropuerto_pc (Id=75) - "Aeropuerto Regional PC"
- **Estado del proyecto:** Activo=1, Acceso=1, Area='Pre-Construccion'
- **Problema:** No existe registro en `project_members` para user_id=1 + project_id=75
- **Único miembro actual:** user_id=366 (test.A) con role='A'

### Verificación SQL
```sql
-- Proyecto existe
SELECT Id, Proyecto_Proceso, Base_de_Datos, Area, Activo, Acceso 
FROM general_proyectos_procesos 
WHERE Base_de_Datos = 'da_aeropuerto_pc';
-- Resultado: Id=75, Area=Pre-Construccion, Activo=1, Acceso=1 ✅

-- Sin miembros para jbenitez
SELECT * FROM project_members 
WHERE project_id = 75 AND user_id = 1;
-- Resultado: Vacío ❌

-- Solo test.A tiene acceso
SELECT * FROM project_members WHERE project_id = 75;
-- Resultado: user_id=366, role='A'
```

## Decisions (with rationale)
1. **Insertar en project_members** - No hay cambios de código necesarios; el query del controlador ya soporta proyectos Pre-Construccion
2. **Usar role='A'** - Consistente con los otros proyectos donde jbenitez tiene rol Admin

## Scope IN
- Ejecutar INSERT en `project_members` para user_id=1, project_id=75, role='A'
- Verificar que el proyecto aparezca en el selector

## Scope OUT (Must NOT have)
- No modificar el código del controlador
- No modificar la estructura de la base de datos
- No crear nuevos usuarios

## Open questions
- Ninguna - la causa raíz está completamente identificada

## Approval gate
status: awaiting-approval
<!-- Cuando se apruebe el plan, proceder a ejecutar el INSERT -->
