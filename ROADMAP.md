---
project: lps-aia
type: roadmap
status: activo
updated: 2026-08-19
tags: [proyecto, php]
---

# Roadmap

Last Planner AIA va de un núcleo LPS (RBAC + PG/PI/PS + PDC v2) ya en producción hacia un ecosistema
de notificaciones, QA sistemático y shared schema. «Terminado» hoy no es un hito único: el trabajo
corre en frentes paralelos coordinados por sesiones de agentes IA (ver [[docs/coordinacion-sesiones]]),
cada uno con su propio goal y spec en `docs/superpowers/`.

Detalle bullet-por-bullet de cada fix histórico (Feb–Ago 2026): [[docs/archive/roadmap-historial-completo-2026-08-19]].
Cambios liberados: [[CHANGELOG]]. Trabajo en curso: [[TASKS]] e [[IMPLEMENTATION_PLAN_INVENTORY]].

## Regla arquitectónica vigente

- BD **global-only**: tablas globales con `project_id`; prohibido agregar dependencias runtime
  nuevas a tablas `{prefix}_*` (quedan solo como respaldo/migración).
- **PDC v1 eliminado el 2026-08-04** (Listado de Actividades, Contratos, `/pdc`, `SemiAutoService`,
  18 tablas). Sucesor: **Plan de Compras v2** (`/plan-compras`, isla React en `pdc-app/`,
  `src/Services/Pdc/`) — ver `docs/pdc-v2.md`. No reintroducir rutas/servicios/tablas del v1.
- Confianza semi-auto en escala `0-100`: `80-100` listo, `50-79` revisión, `<50` no recomendado.

## Fases

| Fase | Qué entrega | Estado |
|---|---|---|
| 0-3 · RBAC y Autenticación | Catálogo RBAC, `RbacService`, consola admin (47→22 cargos) | hecha |
| 4-5C · Hardening LPS | Paridad frontend 9 roles canónicos, `RbacGuard`, `ReportController`, QA cross-role | hecha |
| 6-6.3 · Limpieza y Kill Switch Legacy | Eliminación de `/construccion/` (~1350 archivos), migración a MVC 2026, accesibilidad, encoding | hecha |
| Migración LPS Core (Fase 4 técnica) | Endpoints legacy PS/CNC/CNP/CIC → controladores API MVC | hecha (2026-03-06) |
| Apple-Style Design System v1 | CSS nativo OKLCH/HSL, tipografías premium, `STITCH.md` | hecha (2026-04-07) |
| Global tables + semi-auto guiado | BD global-only, asistente `preview/apply/undo/feedback/metrics` | hecha (2026-06-30) |
| PDC v1 → retiro, PDC v2 | Eliminación PDC v1, Plan de Compras v2 (React) | hecha (2026-08-04) |
| Escalamiento, Drawers, Dashboard Kanban | Cajón Contextual LPS, matriz de severidad, WhatsApp SOS | hecha (2026-05-22) |
| Recuperación de contraseña | Flujo SMTP vía MTA local del hosting | hecha (2026-08-19, `21243c7e`) |
| DS-F0 / DS-F1 · auditoría y estado del design system | Auditoría total + estados por severidad del DS | en curso |
| Wiki v2 (`memoria/`, `decisiones/`, coordinación versionada) | Registros de coordinación entre sesiones versionados en git | en curso |
| Runtime budgets al CI | Presupuestos de runtime de tests enforced en CI | en curso |
| Fase 7 · Notificaciones por rol | `system_notifications`, `NotificationService`, Asistente de Turno AIA | pendiente |
| Fase 8 · QA sistemático por roles | Barrido con usuarios `test.R`/`test.D` contra `RbacGuard` | pendiente |
| Fase 9 · Despliegue gradual y observabilidad | Feature toggles, rollback scripting, logs server-side | pendiente |
| Fase 10 · Shared schema y Bitácora LPS | `lps_shared_constraints`, `lps_constraint_links`, Inteligencia de Agrupación | pendiente |

## Decisiones de rumbo

- 2026-08-04 — PDC v1 retirado en bloque; PDC v2 (React) es el único camino soportado. Detalle:
  `docs/pdc-v2.md`, [[docs/superpowers/specs/2026-07-29-c1-retiro-pdc-viejo-design]].
- 2026-08-10 — Se declara el reparto coordinadora/ejecución entre sesiones de agentes IA, una sesión
  de frente activa a la vez. Detalle: [[docs/coordinacion-sesiones]].
- 2026-08-19 — «Organizar la casa»: registros de coordinación (`decisiones/*.md`, vistos) pasan de
  memoria de chat/`.claude/` (no versionado) a `decisiones/` versionado en git; reglas de tráfico
  entre sesiones escritas en `docs/coordinacion-sesiones.md`. Detalle:
  [[docs/superpowers/specs/2026-08-19-organizar-la-casa-design]].
- 2026-08-19 — Bootstrap de la wiki LLM de 5 archivos en la raíz (este archivo incluido); el
  historial extenso de ROADMAP.md se offloadea a
  [[docs/archive/roadmap-historial-completo-2026-08-19]] en vez de vivir en la raíz.
- 2026-08-19 — La verificación de tests vía contenedor Docker se retira del gate global de
  `~/.claude` (afecta cómo cada sesión valida antes de publicar); pendiente decidir su reemplazo
  como config por proyecto — ver [[TASKS]].

## Documentos de referencia

- [[AGENTS]] / `GEMINI.md` — constitución operativa de los agentes IA.
- `memoria/arquitectura/` — mapa de subsistemas MVC/Legacy generado desde código.
- `GLOSARIO.md` — diccionario de términos LPS.
- `docs/rbac_event_dictionary.md` — diccionario de eventos canónicos RBAC.
- `docs/plan-migracion-shared-schema-sin-reporteria.md`, `docs/plan-migracion-datos-zero-loss.md` —
  planes de arquitectura para la Fase 10 (shared schema), listos pero no ejecutados.
- `docs/analisis-productividad-feb-jun-2026.md` — resumen no técnico para usuarios finales (Feb-Jun 2026).
