# Plan: IA Agile Operative OS 2026

Este plan detalla la adopción de protocolos avanzados de ejecución y planificación (PDCA, Sniper, Pro Planning, SDD) en la constitución del proyecto para maximizar la eficiencia y reducir errores.

## User Review Required

> [!IMPORTANT]
> Estamos cambiando las reglas de ejecución. Una vez aprobado un plan, entraré en **Modo Sniper**: no haré refactorizaciones "de cortesía" ni cambios creativos no solicitados. Me ceñiré 100% al plan.
> Implementaré un **Kill Switch de 5 intentos**: si no logro validar un cambio tras 5 intentos, abortaré automáticamente para evitar bucles de contexto.

## Proposed Changes

### Gobernanza de IA

#### [MODIFY] [GEMINI.md](file:///Users/juanfelipebenitezramos/last-planner-aia-legacy-permisos/GEMINI.md)
- **Protocolo Sniper**: Integrar la filosofía de ejecución ciega y quirúrgica.
- **Kill Switch**: Establecer el límite de 5 intentos de reparación antes de abortar.
- **Validación Unificada**: Cambiar la validación por archivo a una validación por bloque al final de la ejecución.

#### [MODIFY] [SKILL.md (Task Planner)](file:///Users/juanfelipebenitezramos/.gemini/antigravity/skills/task-planner/SKILL.md)
- **Estructura Pro**: Obligar la definición de Fases (Preparación, Producción, Entrega) y Riesgos.
- **Ciclos PDCA**: Integrar hipótesis y criterios de éxito medibles para tareas de mejora continua.
- **Quality Gates**: Adoptar la mentalidad de "juez" interno para validar cada fase antes de proponerla.

### Seguimiento Estratégico

#### [MODIFY] [ROADMAP.md](file:///Users/juanfelipebenitezramos/last-planner-aia-legacy-permisos/ROADMAP.md)
- Registrar la actualización de la arquitectura de planeación como un hito de madurez técnica.

## Open Questions

- ¿Deseas que los ciclos PDCA sean obligatorios para *todas* las tareas o solo para las que impliquen optimización de rendimiento/procesos?

## Verification Plan

### Automated Tests
- No aplica directamente al código de la app, sino a mi comportamiento.
- Verificaré la sintaxis de los archivos `.md` modificados.

### Manual Verification
- Solicitaré al usuario una tarea pequeña después de la actualización para demostrar el **Modo Sniper** en acción.
