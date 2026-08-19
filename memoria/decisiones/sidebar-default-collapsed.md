---
capa: wiki
tipo: decision
estado: vigente
fecha: 2026-07-24
areas: [design-system]
fuente: memoria-claude
origen: lps-aia-sidebar-default-collapsed
resumen: El shell sidebar debe permanecer colapsado por defecto; no cambiar initialState a expanded
---
El shell sidebar canónico (`views/partials/shell_sidebar.php` → `DesignSystemComponent::navigation`) se renderiza con `'initialState' => 'collapsed'`, y ese default NO debe cambiarse a `expanded`, aunque se trabaje en features de la vista expandida.

**Why:** el usuario lo enfatizó tras una sesión larga trabajando sobre la sidebar EXPANDIDA (flyouts desplegables, presupuesto cero-scroll, toggle compacto junto al logo, paneles de menú opacos). Quiere asegurar que el estado por defecto (primer load / sin preferencia guardada) siga siendo colapsado.

**How to apply:** al tocar el sidebar, conserva `'initialState' => 'collapsed'` en el partial. La persistencia opt-in por `localStorage` (`aia-sidebar-state`, ver `public/js/modules/aia_ui/sidebar_navigation.js`) respeta la elección del usuario: si expandió, se recuerda expandida en su próximo load — eso es intencional y no contradice el default. Relacionado: [[css-layer-cascade]].
