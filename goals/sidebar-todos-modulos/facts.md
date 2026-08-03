# Facts — Rollout del shell sidebar a todos los módulos

- Existe un mapa/inventario que, para los 12 módulos con sidebar (PI + los 11 a migrar), indica el estado del shell sidebar: correctamente implementado (declarado en foundation-shell.json + gates verdes + ambos estados) o las brechas pendientes por módulo.
- Los 11 módulos objetivo (Programa General, Prog. Semanal, Actualizar Cronograma, Profesionales, Subcontratistas, Control de Cambios, Familias de Actividades, Contratos, PDC /pdc, Indicadores, Control Tower) renderizan el shell sidebar canónico (mismo partial shell_sidebar.php que PI), con la navbar superior legacy reemplazada.
- En los módulos que hoy tienen cajón LPS derecho ('Concurrencia LPS'), este se conserva y coexiste con el shell sidebar, igual que en PI.
- En cada módulo migrado la sidebar funciona en ambos estados —colapsada (default) y expandida— alternables con el toggle, y el estado persiste en localStorage (aia-sidebar-state).
- En cada módulo migrado, a 1180x820 dark y en ambos estados, la sidebar no hace scroll y la página no tiene overflow horizontal.
- En cada módulo migrado los flyouts de la sidebar (menús de semana y menú de cuenta) se despliegan sin recorte y con paneles opacos, igual que en PI.
- En cada módulo migrado el ítem de nav activo corresponde al módulo actual (aria-current='page') y la visibilidad por RBAC se respeta (un rol permitido ve sus ítems; uno denegado no).
- Cada ruta migrada está declarada en docs/design-system/manifests/foundation-shell.json (routes) y los gates del foundation-shell (contract mjs, shell-navigation, partial php, shell-week-admin) quedan verdes.
- Un test automatizado data-driven recorre las 12 rutas (PI + 11) y verifica: sidebar presente, ambos estados, cero-scroll, sin overflow horizontal, ítem activo y default colapsado.
- En /indicadores el embed de Power BI se ajusta para coexistir con la sidebar sin generar overflow horizontal.
- En Control Tower el shell sidebar reemplaza la navbar de app superior conservando la sub-navegación BI intra-sección (tabs overview/PG/PI/PS).
- El comportamiento del sidebar es idéntico a PI en todos los módulos: default colapsado, toggle compacto junto al logo, marca 'Last Planner AIA' sin truncar, paneles de menú opacos y sin bloque de contexto duplicado en el header.
