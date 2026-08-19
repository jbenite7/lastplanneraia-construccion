---
capa: wiki
tipo: referencia
estado: vigente
fecha: 2026-07-29
areas: [design-system]
fuente: memoria-claude
origen: lps-aia-artefacto-estado-dark-mode
resumen: Artefacto vivo con el estado del goal dark-mode-todos-los-modulos; se regenera desde el audit y git, no a mano
---
Dashboard publicado del goal `dark-mode-todos-los-modulos`:
https://claude.ai/code/artifact/a5ea961d-beb1-42e7-826f-59d7a48d8861

Para actualizarlo desde **otra** sesión hay que pasar esa URL en el parámetro `url` de la
herramienta Artifact; si no, se crea un artefacto nuevo en otra URL.

Todas sus cifras salen de medición en vivo, nunca escritas a mano: `node
scripts/design-system-audit.mjs` (total + summary por regla + `pathBudgets`), la historia de git,
y `goals/dark-mode-todos-los-modulos/` (specs, plans, `validation-log.md`).

Línea base declarada en `goal.md` para comparar: **7 230 hallazgos** el 2026-07-25 en `8a13ad4`,
con `programacion-semanal` en rojo. Ojo al comparar: esa medición **no escaneaba `admin/`** y las
actuales sí, así que la caída real es mayor que la aparente.

El generador (`build-session-report.mjs`) vivía en el scratchpad de la sesión del 2026-07-28 —
efímero. Si hace falta otra vez y ya no está, se reescribe leyendo el JSON del audit.

Relacionado: [[goal-dark-mode-todos-modulos]], [[css-layer-cascade]].
