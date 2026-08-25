---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-07-20
areas: [proceso]
fuente: docs/superpowers/plans/2026-07-20-sidebar-canonico-laboratorio.md
resumen: Crear en /internal/design-system la plantilla high-fi del sidebar canónico de AIA como reemplazo desktop del navbar, lista para una migración productiva…
---

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

---

## Cierre

**Estado: EJECUTADO Y SUPERADO.** Acta escrita el 2026-08-24, por orden de Felipe. El trabajo no
estaba pendiente: **estaba hecho desde julio y en producción**. Lo que faltaba era esta sección, y
sin ella la regla de lectura lo contaba como abierto y lo sacaba en cada revisión — 35 días.

### Por qué se cerró sin ejecutar nada

Este plan pedía **una plantilla en el laboratorio, «lista para una migración productiva
posterior»**. Esa migración ya ocurrió, así que ejecutarlo hoy sería construir el prototipo de algo
que la app ya usa: el orden al revés.

- **Felipe lo aprobó el 2026-07-22**, dos días después de escribirse este plan, como «rail lateral
  global colapsable, piloto Programación Intermedia»
  (`docs/design-system/family-approvals.json`, `approvalRef:
  chat-2026-07-22-sidebar-approved-as-global-shell-collapsible-rail-pilot-programacion-intermedia`).
- **La migración productiva cerró el 2026-07-31** en [[goals/sidebar-todos-modulos/goal]]: 11 rutas
  migradas, `shell-rollout` 11/11 (55/55 checks), con sus excepciones documentadas — Compras quedó
  fuera porque PDC v2 trae navegación propia, y Control Tower se difirió a `bi-control-tower-gemini`
  por ser una SPA que pide rediseño, no migración mecánica.
- **Hoy lo consumen 16 vistas productivas** (`grep -rl 'shell-sidebar\|aia-sidebar\|sidebar_navigation' views/`).

### Las cinco tareas, verificadas contra el código el 2026-08-24

| Tarea | Evidencia |
|---|---|
| 1 · Contrato PHP y markup | `DesignSystemComponent.php` soporta `presentation => 'sidebar'`; `views/design-system/families/shell-navigation.php` existe; `tests/test_design_system_components.php` lo cubre en 26 puntos |
| 2 · Tokens y CSS | 8 declaraciones de ancho de rail en `public/css/tokens.css`; 140 menciones en `navigation.css` |
| 3 · Interacción | `public/js/modules/aia_ui/sidebar_navigation.js` expone `window.AiaSidebarNavigation` |
| 4 · Contratos | `sidebar-shell` registrado en `component-catalog.json`, `homologation.json` y `decisions.md` |
| 5 · QA | `tests/browser/design-system-lab-sidebar.mjs`, 120 líneas |

Comando y salida de esta sesión:

```
docker compose exec app php tests/test_design_system_components.php
Design system components: PASS   (RC=0)
```

### La lección, que vale más que el cierre

**El control que abrió esta decisión mide la antigüedad del papel, no la existencia del trabajo.**
Llevó 35 días preguntando «¿se ejecuta o se cierra?» sobre algo que ya estaba en producción y que el
propio Felipe había aprobado. Es la misma familia que este repo ya tiene medida tres veces —
[[memoria/trampas/guard-valida-declaracion-contra-si-misma]],
[[memoria/trampas/guard-de-texto-no-ve-el-parseo]],
[[memoria/trampas/el-contador-no-mide-el-archivo]] —: **un control que verifica lo que un documento
declara en vez de lo que el código hace**.

El coste no fue el plan sin cerrar: fue que la pregunta llegara al gerente cuando la respuesta
estaba en el repositorio, y que compitiera por su atención cada vez.
