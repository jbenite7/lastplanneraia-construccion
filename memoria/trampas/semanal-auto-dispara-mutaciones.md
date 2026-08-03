---
tipo: trampa
estado: vigente
fecha: 2026-07-29
areas: [qa, lps]
fuente: memoria-claude
origen: lps-aia-browser-qa-pitfalls
resumen: "Abrir /programacion-semanal dispara save y auto-program automáticos; usar /dashboard/escalamientos para QA de solo lectura"
---
**Abrir `/programacion-semanal` dispara mutaciones automáticas**: `POST /api/semanal/save` +
`POST /api/semanal/auto-program` en cada carga de página, sin interacción. Para QA de solo lectura
del shell/drawer conviene usar `/dashboard/escalamientos` (incluye el mismo drawer LPS vía
`views/partials/drawer_unificado.php`, sin autosave).

**Why:** una verificación pensada como "solo mirar" puede escribir en la base sin que el agente lo
pida. **How to apply:** para QA visual o de navegación del drawer/shell, preferir
`/dashboard/escalamientos` a `/programacion-semanal`.

Relacionado: [[sesion-cae-en-el-panel]], [[bitacora-drawer-sin-profesional]].
