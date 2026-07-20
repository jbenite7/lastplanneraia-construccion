# Sidebar canónico del laboratorio — Plan de implementación

> **For agentic workers:** ejecutar por tareas y verificar cada checkpoint antes de continuar.

**Goal:** Crear en `/internal/design-system` la plantilla high-fi del sidebar canónico de AIA como reemplazo desktop del navbar, lista para una migración productiva posterior.

**Architecture:** Extender `DesignSystemComponent::navigation()` con una presentación `sidebar`, agrupación operativa, contexto de proyecto y utilidades persistentes. El rail desktop será expandido de 280 px o colapsado de 72 px; el drawer existente debajo de 1200 px se conserva sin cambios.

**Tech Stack:** PHP, CSS por capas y tokens `--ds-*`, JavaScript vanilla, Playwright y Node test runner.

## Global Constraints

- Validar únicamente dark desktop en `1180x820` y `1440x900`.
- No tocar mobile, tablet, linen, snapshots de esos alcances ni consumidores productivos.
- Mantener WCAG AA, foco visible, targets de `44px` y `prefers-reduced-motion`.
- Usar tokens/primitivas existentes; sin hex locales, estilos inline, gradientes decorativos ni nuevos vendors.
- No regenerar goldens sin aprobación visual humana explícita.

## Implementación

1. **Contrato PHP y markup:** actualizar `src/View/Components/DesignSystemComponent.php`, `views/design-system/families/shell-navigation.php` y `tests/test_design_system_components.php` para soportar `presentation => sidebar`, `groups`, contexto de proyecto, utilidades, estados y `aria-current` único.
2. **Tokens/CSS:** añadir tokens de ancho del rail y estilos en `public/css/tokens.css` y `public/css/design-system/components/navigation.css`, con estados default/hover/focus/active/disabled/loading/empty/error y fallback reduced-motion.
3. **Interacción:** crear `public/js/modules/aia_ui/sidebar_navigation.js`, cargarlo desde `views/design-system/lab.view.php` y exponer `window.AiaSidebarNavigation.init(root)` para colapso, foco, Escape y estados deterministas.
4. **Contratos:** registrar el candidato `sidebar-shell` en `docs/design-system/component-catalog.json`, `docs/design-system/homologation.json` y `docs/design-system/decisions.md`; actualizar el manifiesto solo después de aprobación visual.
5. **QA:** añadir `tests/browser/design-system-lab-sidebar.mjs`, extender teclado/layout desktop y ejecutar PHP component test, static design-system gate y Playwright enfocado sin actualizar snapshots.

## Arquitectura visual

- Rail izquierdo persistente: 280 px expandido, 72 px colapsado.
- Cabecera: marca, proyecto `Optimización Aeropuerto JMC`, `Semana 7`, toggle.
- Grupos: Información, Planificación y Ejecución, preservando las rutas actuales.
- Footer: avisos, usuario/rol, cambiar proyecto, tema y cerrar sesión.
- Colapsado conserva iconos, `aria-label`, `title` y foco; el contenido no desaparece semánticamente.

## Criterios de aceptación

- Rail visible y estable en 1180×820 y 1440×900, sin overflow horizontal.
- Todos los objetivos interactivos miden al menos 44×44 px y muestran foco visible.
- Colapso por click/teclado sincroniza `data-sidebar-state` y `aria-expanded`; Escape restaura el foco.
- Loading, empty y error son deterministas y no dependen de red ni localStorage.
- Axe no reporta violaciones serias y la consola permanece limpia.
- `NavbarComponent.php` y consumidores productivos permanecen sin cambios.
