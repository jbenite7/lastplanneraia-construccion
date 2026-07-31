# Plan de Reparación UI End-to-End (Auditoría 10/10)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Elevar el puntaje de diseño y jerarquía visual de todas las 18+ superficies de Last Planner AIA a 10/10 bajo los principios de *Refactoring UI* y el contrato `DESIGN.md`.

**Architecture:** Sistema de diseño modular basado en tokens CSS OKLCH (`tokens.css`), clases utilitarias `aia-*`, capas CSS ordenadas (`core.css`) y entrega mediante `DesignSystemHeadComponent`.

**Tech Stack:** PHP 8.3, Apache, Docker, Vanilla CSS, JS Modules, Playwright (E2E), React (Isla PDC).

## Global Constraints

- **Viewport obligado:** Desktop (≥ 1180 × 820 px). Viewport secundario 1440×900 px.
- **Tema obligado:** Tema Operativo Dark (`#0b100d` / `#111a15`). Prohibido mobile, tablet o tema `linen`.
- **Contraste mínimo:** WCAG AA (≥ 4.5:1 en texto regular).
- **Consistencia visual:** Cero hex sueltos, cero estilos inline o etiquetas `<style>` dentro de archivos de vistas en superficies migradas.
- **Altura de controles:** Mínimo 44px para botones/inputs (excepción registrada de 28px exclusivamente en `/plan-compras`).

---

### Task 1: Remediar Submódulos de Programación Semanal (`/programacion-semanal/cnp`, `cnc`, `cic`)

**Files:**
- Modify: `views/programacion-semanal/cnp.view.php`
- Modify: `views/programacion-semanal/cnc.view.php`
- Modify: `views/programacion-semanal/cic.view.php`
- Modify: `public/css/programacion-semanal.css`
- Test: `tests/browser/full-app-flow.spec.mjs`

**Interfaces:**
- Consumes: Tokens CSS `--ds-active-*`, `.aia-chip`, `data-aia-severity`
- Produces: Submódulos CNP/CNC/CIC con jerarquía 10/10 en dark mode.

- [ ] **Step 1: Escribir la prueba E2E enfocada o verificación de la ruta**

```bash
docker compose exec app php -r "echo file_exists('views/programacion-semanal/cnp.view.php') ? 'OK' : 'FAIL';"
```

- [ ] **Step 2: Verificar que los archivos existen y revisar clases semánticas**

Ejecutar: `docker compose exec app php -r "echo file_exists('views/programacion-semanal/cnp.view.php') ? 'OK' : 'FAIL';"`
Expected: OK

- [ ] **Step 3: Aplicar clases .aia-chip y tokens semánticos en CNP, CNC y CIC**

Actualizar `views/programacion-semanal/cnp.view.php`, `cnc.view.php`, `cic.view.php` eliminando utilidades Bootstrap directas (`badge-warning`, `badge-danger`) y reemplazando por:

```html
<span class="aia-chip" data-aia-severity="critical">Compromiso Atrasado</span>
```

- [ ] **Step 4: Ejecutar Playwright test para verificar rendering**

Run: `npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add views/programacion-semanal/ public/css/programacion-semanal.css
git commit -m "style(ps): estandarizar chips semanticos y tokens en submódulos CNP/CNC/CIC"
```

---

### Task 2: Remediar Zona de Actualización de Cronograma (`/programa-general-actualizar`)

**Files:**
- Modify: `views/programa-general-actualizar/programa-general-actualizar.view.php`
- Modify: `public/css/programa-general-actualizar.css`
- Test: `tests/browser/full-app-flow.spec.mjs`

**Interfaces:**
- Consumes: `.aia-card`, `.aia-btn`, `.aia-input`, anillo de foco de 4px
- Produces: Zona de carga con bordes dashed de separador y área dropzone con 10/10.

- [ ] **Step 1: Inspeccionar la vista de actualización**

```bash
docker compose exec app php -r "echo file_exists('views/programa-general-actualizar/programa-general-actualizar.view.php') ? 'OK' : 'FAIL';"
```

- [ ] **Step 2: Verificar respuesta**

Expected: OK

- [ ] **Step 3: Refactorizar contenedor de upload a patrón .aia-card con dropzone**

```html
<div class="aia-card u-text-center u-p-6" style="border: 2px dashed var(--ds-active-border);">
  <i class="fas fa-file-upload u-mb-3 text-secondary" style="font-size: 2rem;"></i>
  <h3 class="aia-title">Actualizar Cronograma (XML / MS Project)</h3>
  <p class="aia-label u-mb-4">Arrastra aquí el archivo o haz clic para examinar</p>
  <button class="aia-btn aia-btn--primary">Seleccionar Archivo</button>
</div>
```

- [ ] **Step 4: Ejecutar verificación visual / Playwright**

Run: `npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add views/programa-general-actualizar/ public/css/programa-general-actualizar.css
git commit -m "style(pg): refactorizar zona de carga de cronograma a tarjetas AIA"
```

---

### Task 3: Remediar Vistas de Recuperación de Contraseña (`/password/forgot`, `/password/reset`)

**Files:**
- Modify: `views/auth/forgot-password.view.php`
- Modify: `views/auth/reset-password.view.php`
- Test: `tests/browser/full-app-flow.spec.mjs`

**Interfaces:**
- Consumes: `.aia-card`, `.aia-input`, `.aia-btn--primary`
- Produces: Flujo de recuperación de contraseña con la misma calidad 10/10 del Login principal.

- [ ] **Step 1: Verificar existencia de vistas de Auth**

```bash
docker compose exec app php -r "echo file_exists('views/auth/forgot-password.view.php') ? 'OK' : 'FAIL';"
```

- [ ] **Step 2: Verificar respuesta**

Expected: OK

- [ ] **Step 3: Migrar contenedores a .aia-card y controles a .aia-input**

Reemplazar formularios viejos por estructura `.aia-card` centrada en viewport con `max-w-md` y fondo dark `#0b100d`.

- [ ] **Step 4: Ejecutar Playwright test**

Run: `npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add views/auth/
git commit -m "style(auth): migrar recuperacion de contrasena a tarjetas y controles aia"
```

---

### Task 4: Remediar Tablas Operativas Legadas (`/profesionales`, `/subcontratistas`)

**Files:**
- Modify: `views/profesionales/profesionales.view.php`
- Modify: `views/subcontratistas/subcontratistas.view.php`
- Modify: `public/css/profesionales.css`
- Modify: `public/css/subcontratistas.css`
- Test: `tests/browser/full-app-flow.spec.mjs`

**Interfaces:**
- Consumes: `table.css` del DS, `max-w-md` en formularios
- Produces: Tablas legadas de profesionales y subcontratistas integradas a `DESIGN.md`.

- [ ] **Step 1: Verificar existencia de vistas**

```bash
docker compose exec app php -r "echo file_exists('views/profesionales/profesionales.view.php') ? 'OK' : 'FAIL';"
```

- [ ] **Step 2: Verificar respuesta**

Expected: OK

- [ ] **Step 3: Estandarizar padding de celdas a 12px horizontal / 8px vertical y de-enfatizar th**

```css
.aia-table th {
  font-family: var(--ds-font-body);
  font-size: 0.75rem;
  text-transform: uppercase;
  color: var(--ds-active-text-secondary);
  font-weight: 600;
  letter-spacing: 0.05em;
}
```

- [ ] **Step 4: Validar en navegador / Playwright**

Run: `npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add views/profesionales/ views/subcontratistas/ public/css/
git commit -m "style(ops): estandarizar tablas y formularios en profesionales y subcontratistas"
```

---

### Task 5: Refactorizar Tarjetas KPI en Indicadores Operativos (`/indicadores`)

**Files:**
- Modify: `views/indicadores/indicadores.view.php`
- Modify: `public/css/indicadores.css`
- Test: `tests/browser/full-app-flow.spec.mjs`

**Interfaces:**
- Consumes: Montserrat Display (`1.875rem`), Inter Body (`0.875rem`), `.aia-card`
- Produces: Tarjetas de indicadores con jerarquía 10/10.

- [ ] **Step 1: Verificar existencia de la vista de indicadores**

```bash
docker compose exec app php -r "echo file_exists('views/indicadores/indicadores.view.php') ? 'OK' : 'FAIL';"
```

- [ ] **Step 2: Verificar respuesta**

Expected: OK

- [ ] **Step 3: Aplicar escala tipográfica canónica en KPIs**

```html
<div class="aia-card">
  <span class="aia-label u-text-secondary">PPC Acumulado</span>
  <div class="aia-kpi-value u-mt-1 u-mb-2">84.5%</div>
  <span class="aia-helper text-success"><i class="fas fa-arrow-up"></i> +2.3% vs semana anterior</span>
</div>
```

- [ ] **Step 4: Ejecutar Playwright test**

Run: `npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add views/indicadores/ public/css/indicadores.css
git commit -m "style(bi): refactorizar tarjetas kpi de indicadores a tipografia y tarjetas aia"
```

---

### Task 6: Estandarizar Navegación y Shell BI (`/bi/*`)

**Files:**
- Modify: `views/bi/_nav.php`
- Modify: `views/bi/control-tower.view.php`
- Modify: `public/css/bi.css`
- Test: `tests/browser/full-app-flow.spec.mjs`

**Interfaces:**
- Consumes: `.aia-glass`, tokens `--ds-active-*`, píldoras de sub-navegación
- Produces: Barra de navegación BI alineada con el shell en dark mode.

- [ ] **Step 1: Verificar existencia del parcial de navegación BI**

```bash
docker compose exec app php -r "echo file_exists('views/bi/_nav.php') ? 'OK' : 'FAIL';"
```

- [ ] **Step 2: Verificar respuesta**

Expected: OK

- [ ] **Step 3: Aplicar .aia-glass y píldoras activas en _nav.php**

Actualizar `views/bi/_nav.php` para que el contenedor use `.aia-glass` y la pestaña activa consuma `var(--ds-active-surface-raised)`.

- [ ] **Step 4: Validar Playwright test**

Run: `npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add views/bi/ public/css/bi.css
git commit -m "style(bi): estandarizar subnavegacion bi con aia-glass y tokens de estado activo"
```

---

### Task 7: Adaptación de Contraste y Penumbra Dark en Panel Admin (`/admin/*`)

**Files:**
- Modify: `admin/views/layouts/main.php` (o layout de admin equivalente)
- Modify: `admin/public/css/admin-dark-override.css` (o estandarización CSS admin)
- Test: `tests/browser/full-app-flow.spec.mjs`

**Interfaces:**
- Consumes: Variables de penumbra `#0b100d` y contraste de texto ≥ 4.5:1
- Produces: Panel Admin alineado visualmente en dark mode sin alterar AdminLTE.

- [ ] **Step 1: Verificar existencia del layout de admin**

```bash
docker compose exec app php -r "echo file_exists('admin/views/layouts/header.php') || file_exists('admin/views/pages/dashboard.php') ? 'OK' : 'FAIL';"
```

- [ ] **Step 2: Verificar respuesta**

Expected: OK

- [ ] **Step 3: Ajustar hoja de contraste dark sobre AdminLTE**

Garantizar que el sidebar de admin use fondo `#111a15` y texto `#f7faf8` con enlace activo `#6c9077`.

- [ ] **Step 4: Validar Playwright test**

Run: `npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add admin/
git commit -m "style(admin): asegurar contraste minimo AA y penumbra dark en panel de administracion"
```

---

### Task 8: Auditoría y Verificación Global de Cierre

**Files:**
- Test: `scripts/design-system-unlayered-delivery.mjs`
- Test: `tests/browser/full-app-flow.spec.mjs`
- Modify: `docs/superpowers/specs/2026-07-31-ui-audit-and-repair-plan-design.md`

**Interfaces:**
- Consumes: All updated views and CSS files
- Produces: Verificación 100% verde de contraste, entrega sin capa y navegación Playwright.

- [ ] **Step 1: Ejecutar verificación de entregas sin capa**

```bash
node scripts/design-system-unlayered-delivery.mjs
```

- [ ] **Step 2: Verificar respuesta estática**

Expected: 0 errores de entregas sin capa fuera de inventario.

- [ ] **Step 3: Ejecutar suite de pruebas de navegación Playwright en 1180x820 Dark Mode**

```bash
npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1
```

- [ ] **Step 4: Actualizar las notas de la especificación a 10/10 tras verificar cambios**

Actualizar el spec `docs/superpowers/specs/2026-07-31-ui-audit-and-repair-plan-design.md` reflejando el estado final verificado.

- [ ] **Step 5: Commit final de cierre**

```bash
git add docs/superpowers/specs/2026-07-31-ui-audit-and-repair-plan-design.md
git commit -m "docs: actualizar spec de auditoria con verificacion final 10/10 completada"
```
