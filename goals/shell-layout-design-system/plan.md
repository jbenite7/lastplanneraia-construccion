# Plan de Implementación — Unificación de Shell, Layout y Design System

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Unificar en una sola unidad de ejecución la segmentación CSS, el rollout del menú lateral canónico en los 11 módulos, la paleta oscura de estados/chips y la cobertura 100% Dark Mode desktop.

**Architecture:** Módulo de layout basado en `views/partials/shell_sidebar.php` + `sidebar_navigation.js` registrado en `foundation-shell.json` con entrypoint CSS segmentado en núcleo ligero y adjuntos por vendor.

**Tech Stack:** PHP 8.3, Apache, Vanilla CSS (`--ds-*`), Vanilla JS, Playwright/PHPUnit para verificación.

## Global Constraints

- **Viewport & Tema:** Exclusivamente Desktop (**1180 × 820 px**) en **Dark Mode**.
- **Prohibiciones:** Cero código/pruebas para mobile, tablet o tema `linen`.
- **Gobernanza:** Registro obligatorio de superficies en `docs/design-system/manifests/foundation-shell.json`.

---

### Task 1: Fase 1 — Segmentación CSS del Entrypoint y Manifiestos de Gobernanza

**Files:**
- Modify: `public/css/aia-design-system.css`
- Modify: `docs/design-system/manifests/foundation-shell.json`
- Test: `tests/test_global_table_safety.php`

**Interfaces:**
- Consumes: Manifiesto `foundation-shell.json`
- Produces: Entrypoint CSS ligero y cargadores segmentados por superficie

- [ ] **Step 1: Auditar e identificar vendors masivos en aia-design-system.css**
- [ ] **Step 2: Extraer handsontable-module.css a un adjunto por vendor**
- [ ] **Step 3: Actualizar foundation-shell.json con los adjuntos correspondientes**
- [ ] **Step 4: Verificar la carga segmentada en el runtime Docker**

---

### Task 2: Fase 2 — Rollout del Shell Sidebar Canónico a los 11 Módulos

**Files:**
- Modify: `views/programa_general.view.php`
- Modify: `views/programacion_semanal.view.php`
- Modify: `views/actualizar_cronograma.view.php`
- Modify: `views/profesionales.view.php`
- Modify: `views/subcontratistas.view.php`
- Modify: `views/control_cambios.view.php`
- Modify: `views/familias_actividades.view.php`
- Modify: `views/contratos.view.php`
- Modify: `views/pdc.view.php`
- Modify: `views/indicadores.view.php`
- Modify: `views/torre_control.view.php`
- Test: `tests/browser/full-app-flow.spec.mjs`

**Interfaces:**
- Consumes: `views/partials/shell_sidebar.php`, `public/js/modules/aia_ui/sidebar_navigation.js`
- Produces: 11 módulos con sidebar canónico funcional (colapsado por defecto)

- [ ] **Step 1: Aplicar la "receta PI" en la primera tanda de módulos (Programa General, Prog. Semanal, Actualizar Cronograma)**
- [ ] **Step 2: Aplicar en la segunda tanda (Profesionales, Subcontratistas, Control de Cambios, Familias)**
- [ ] **Step 3: Aplicar en la tercera tanda (Contratos, PDC, Indicadores, Torre de Control)**
- [ ] **Step 4: Verificar ausencia de scroll horizontal y correcto colapsado en 1180×820 dark**

---

### Task 3: Fase 3 — Inversión de Paleta de Estados y Chips PDC

**Files:**
- Modify: `public/css/design-system/tokens/colors.css`
- Modify: `public/css/design-system/components/chips.css`
- Test: `tests/browser/pdc-v2-plan.spec.mjs`

**Interfaces:**
- Consumes: Tokens CSS `--ds-color-status-*`
- Produces: Chips e indicadores de estado optimizados para contraste en tema oscuro

- [ ] **Step 1: Ajustar variables CSS de estados a tonos contrastados sobre fondo oscuro**
- [ ] **Step 2: Actualizar componentes de chips PDC y puntos de nivel**
- [ ] **Step 3: Validar contraste visual en 1180px dark mode**

---

### Task 4: Fase 4 — Cierre Dark Mode Total y Verificación Visual

**Files:**
- Modify: `docs/design-system/manifests/foundation-shell.json`
- Test: `docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G`

**Interfaces:**
- Consumes: Todo el suite de pruebas y manifiesto de gobernanza
- Produces: Cobertura 100% dark mode verificada y gates en verde

- [ ] **Step 1: Ejecutar PHPStan y pruebas de integridad de tablas**
- [ ] **Step 2: Verificar la consola y red en el navegador integrado contra http://localhost:8081**
- [ ] **Step 3: Actualizar el manifiesto de la aplicación y confirmar gates verdes**
