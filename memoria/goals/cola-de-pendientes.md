---
tipo: goal
estado: abierto
fecha: 2026-08-18
areas: [proceso]
fuente: sesión de coordinación 2026-08-18 (inventario de planes, specs y sesiones + 22 decisiones del usuario)
resumen: "Cola única de pendientes del proyecto, priorizada por el usuario: qué corre ya, qué se replantea y qué espera"
---
# Cola de pendientes (control operativo)

Inventario consolidado el 2026-08-18 y priorizado por el usuario ese mismo día. Esta página es el
tablero: se actualiza al cerrar o reordenar, no se deja derivar. El detalle de cada decisión vive
en `decisiones/<frente>.md` (sin versionar) y en cada `goals/<slug>/goal.md`.

## Primero, antes de ejecutar todo lo demás (orden del usuario, 2026-08-18)

1. **Fase 0 — mudanza del repositorio** (repo + control de versiones + contenedores) fuera del
   SSD externo. Destino propuesto por el usuario: `CloudDocs/Dev/lps-aia` (iCloud). Advertido el
   riesgo de `.git` bajo sync de iCloud; falta que el usuario confirme destino (iCloud / interno
   `~/Developer` / híbrido). La base de datos no se mueve: vive en el volumen Docker
   `htdocs_db_data`. Tras mover: `git worktree repair`, `LPS_CODE_ROOT`, re-crear chips.
2. **Fase 0b — replanteo completo de la wiki** (metodología Karpathy intacta, Obsidian visual,
   todo el vault etiquetado). Decidido el 2026-08-18: **con plugins de comunidad** y **frontmatter
   en todas las fuentes** (solo metadato; el cuerpo sigue intocable). Spec y plan **al gate**:
   `docs/superpowers/specs/2026-08-18-wiki-v2-visual-design.md` +
   `docs/superpowers/plans/2026-08-18-wiki-v2-visual.md` (6 tandas, ~2 jornadas). Primera pasada
   ya hecha: esta página, vista «Abierto ahora», decisiones del día ingeridas.

**Los chips y frentes de abajo no arrancan hasta cerrar estas dos fases.**

## Ejecutar tras las fases 0/0b (aprobado, chips creados)

- [[goals/gates-al-ci/goal|gates-al-ci]] — aplicar `test.C` en CI + baseline nuevo, re-medir 8/8,
  publicar.
- [[goals/contadores-cero/goal|contadores-cero]] — visto concedido; localizar rama, re-verificar,
  publicar.
- **Plan espacio SiteGround** — tareas 1–5 de
  `docs/superpowers/plans/2026-08-18-espacio-cuenta-siteground.md`.
- **Dropdown PS sobre selector de semana** — diagnóstico (`systematic-debugging`) del stacking en
  `/programacion-semanal`.
- **Higiene de coordinación** — sesiones zombi (fundamental), `cas-log.*` de la raíz, triaje de
  los 9 goals esqueleto.

## Replanteo antes de ejecutar

- [[goals/vocabulario-estados-cascada/goal|vocabulario-estados-cascada]] — el usuario pidió
  replantear D-VOC-1; su aclaración clave está en
  [[programa-general-actualizar-es-otra-herramienta]]. D-VOC-4 exige análisis profundo. D-1 de
  contrato-estados se ajusta al censo que salga del replanteo.

## Programa Design System (decisión del 2026-08-18)

Ver [[programa-design-system-en-cuatro-fases]]. Absorbe: gobernanza de los 15 gates, Handsontable
y DataTables fuera del sistema, auditoría total de la app, cascada de severidad sospechosa de bug,
y la optimización del pipeline de gates.

## Apuestas planificadas (tras lo anterior)

Móvil **y tablet** (F2b de [[goals/reapertura-movil-y-tema-claro/goal|reapertura]]) · tema claro
(F3) · candados F4 · **Torre de Control reconstruida con data storytelling** (tras F1 del programa
y el tema claro) · semana fija en el resto de módulos con corte semanal · extensión de
contadores-cero a todos los módulos · backlog del 3-ago (48 decisiones; accesibilidad primero).

## Única decisión abierta que frena la cola

El **destino de la Fase 0**: iCloud tal como se pidió (con mitigaciones), interno `~/Developer`
(recomendado por riesgo de `.git` en iCloud), o híbrido (código interno + pesados en iCloud).
Todo lo demás ya tiene orden.
