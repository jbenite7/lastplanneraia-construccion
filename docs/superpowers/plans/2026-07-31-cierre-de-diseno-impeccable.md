# Plan de Cierre de Diseño e Integración Impeccable

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remediar los fallos estáticos del Design System (`entrypoint-partition`), eliminar la deuda visual de anti-patrones en `programa_actualizar`, `programacion_intermedia`, `access.css` y `buttons.css`, y dejar la aplicación alineada al 100% con los contratos de `DESIGN.md` e Impeccable.

**Architecture:** Corrección canónica a nivel de manifiesto de vendedores (`tom-select`), reemplazo de valores hardcodeados (hex, rgba, inline-styles) por tokens CSS `--ds-*`/`--aia-*` y primitivas `aia-*`, y alineación con las reglas de gobernanza estática en `scripts/design-system-*.mjs`.

**Tech Stack:** Native CSS Tokens (`--ds-*`, `--aia-*`), Handsontable modules (JS), Playwright (E2E), Node test runner, Impeccable CLI / Hook.

## Global Constraints

- **Scope:** Desktop dark ≥1180px únicamente. Sin soporte ni pruebas para mobile, tablet o tema `linen`.
- **Tokens:** Cero hex sueltos, cero inline-styles en JS/CSS, cero `!important` no autorizados en superficies migradas.
- **Fuentes:** Únicamente Montserrat (títulos/métricas) e Inter (cuerpo/navegación/tablas).
- **Pruebas:** Pruebas estáticas `npm run test:design-system:static` deben pasar en verde sin alterar baselines ni snapshots.

---

### Task 1: Corrección de Partición de Vendedores (`tom-select`)

**Files:**
- Modify: `docs/design-system/manifests/inventory.json`
- Modify: `scripts/design-system-entrypoint-partition.mjs`
- Test: `npm run test:design-system:static`

**Interfaces:**
- Consumes: Inventario de manifiestos y generador de adjuntos de vendedores (`renderForModule()`).
- Produces: Mapeo correcto de `tom-select` sin conflicto entre `VIEW_OWNED_VENDORS` y adaptadores.

- [ ] **Step 1: Inspeccionar el error en `design-system-entrypoint-partition.mjs`**

Run: `node scripts/design-system-entrypoint-partition.mjs`
Expected: FAIL con `attachment-url-drift` y `view-owned-with-attachment`.

- [ ] **Step 2: Ajustar la declaración de `tom-select` en el manifiesto / particionador**

Corregir la ruta del adaptador o la pertenencia en `VIEW_OWNED_VENDORS` para `tom-select` de modo que la URL coincida exactamente con la ruta canónica `/public/css/design-system/adapters/tom-select.css`.

- [ ] **Step 3: Ejecutar verificación estática**

Run: `node scripts/design-system-entrypoint-partition.mjs`
Expected: PASS

- [ ] **Step 4: Commit**

```bash
git add docs/design-system/manifests/inventory.json scripts/design-system-entrypoint-partition.mjs
git commit -m "fix(design-system): resolver particion de entrada y adaptador tom-select"
```

---

### Task 2: Remediación de Anti-Patrones en `programa_actualizar`

**Files:**
- Modify: `public/js/modules/programa_actualizar/hot_actualizar.js`
- Test: `node scripts/design-system-audit.mjs`

**Interfaces:**
- Consumes: Tokens `--aia-green-primary`, `--aia-red-primary`, `--aia-text-secondary`, `--ds-radius-sm`.
- Produces: Renderizado de celdas y badges de `hot_actualizar.js` usando tokens en lugar de hex e inline styles.

- [ ] **Step 1: Identificar líneas con hex e inline styles en `hot_actualizar.js`**

Revisar líneas 208, 210, 216, 218, 232, 987, 990, 1171, 1172, 1234, 1235, 1284, 1285, 1342, 1345.

- [ ] **Step 2: Reemplazar hex y rgba por tokens CSS `--aia-*`**

Sustituir `#1a5633` por `var(--aia-green-primary)`, `#dc3545` por `var(--aia-red-primary)`, `#4a4a4d` por `var(--aia-text-secondary)` y `#6c757d` por `var(--aia-text-muted)`.

- [ ] **Step 3: Extraer estilos inline repetidos a clases de módulo o clases de primitivas**

Remover `border-radius: 4px` e inline styles duros del formateador de celdas.

- [ ] **Step 4: Verificar audit de design system**

Run: `node scripts/design-system-audit.mjs`
Expected: Reducción de hallazgos en `hot_actualizar.js`.

- [ ] **Step 5: Commit**

```bash
git add public/js/modules/programa_actualizar/hot_actualizar.js
git commit -m "style(programa-actualizar): migrar hex e inline styles a tokens de design system"
```

---

### Task 3: Refactorización de Estilos en `programacion_intermedia`

**Files:**
- Modify: `public/js/modules/programacion_intermedia/hot.js`
- Test: `node scripts/design-system-audit.mjs`

**Interfaces:**
- Consumes: Tokens `--aia-text-muted`, `--aia-warning-bg`, `--aia-warning-border`.
- Produces: Celdas y tooltips de `programacion_intermedia` limpios de valores inline estáticos.

- [ ] **Step 1: Localizar los hex `#666`, `#fef3c7`, `#f59e0b` en `hot.js`**

Revisar líneas 1935-1938 y 2638 en `public/js/modules/programacion_intermedia/hot.js`.

- [ ] **Step 2: Reemplazar por variables de token**

Utilizar `var(--aia-text-muted)` y tokens de aviso/warning en lugar de colores hex directos.

- [ ] **Step 3: Probar audit**

Run: `node scripts/design-system-audit.mjs`
Expected: PASS sin violaciones adicionales.

- [ ] **Step 4: Commit**

```bash
git add public/js/modules/programacion_intermedia/hot.js
git commit -m "style(programacion-intermedia): reemplazar hex hardcodeados por tokens"
```

---

### Task 4: Limpieza y Normalización de `public/css/access.css`

**Files:**
- Modify: `public/css/access.css`
- Test: `node scripts/design-system-audit.mjs`

**Interfaces:**
- Consumes: Tokens de espaciado `--ds-spacing-*`.
- Produces: Hoja `access.css` sin `!important` y con espaciados regulados.

- [ ] **Step 1: Eliminar `!important` en `.btn-action-gap` y `.nav-link-custom`**

Retirar la bandera `!important` y reestructurar la especificidad si fuera necesario.

- [ ] **Step 2: Ajustar espaciados fuera de escala**

Reemplazar `margin: 0 5px` y `padding: 16px 0 8px 16px` por valores de la escala del Design System.

- [ ] **Step 3: Verificar con el auditor estático**

Run: `node scripts/design-system-audit.mjs`
Expected: Cero errores en `access.css`.

- [ ] **Step 4: Commit**

```bash
git add public/css/access.css
git commit -m "style(access): eliminar !important no autorizados y normalizar espaciados"
```

---

### Task 5: Validación General e Integración del Design Hook Impeccable

**Files:**
- Modify: `.claude/settings.json`
- Test: `npm run test:design-system:static`
- Test: `npx impeccable detect --quiet public/ views/ src/ pdc-app/`

**Interfaces:**
- Consumes: Configuración del hook de Impeccable y suite completa de tests de design system.
- Produces: Reporte en verde y repositorio 100% conforme con la gobernanza estática.

- [ ] **Step 1: Ejecutar la suite completa de tests estáticos**

Run: `npm run test:design-system:static`
Expected: PASS en todos los verificadores (`partition`, `unlayered-delivery`, `bi-utilities`, `contracts`, `consumer-contract`, `audit`).

- [ ] **Step 2: Ejecutar el detector de Impeccable**

Run: `npx impeccable detect --quiet public/ views/ src/ pdc-app/`
Expected: Reporte de barrido ejecutado y validado.

- [ ] **Step 3: Commit final de cierre de spec**

```bash
git add .claude/settings.json docs/superpowers/plans/2026-07-31-cierre-de-diseno-impeccable.md
git commit -m "chore(design-system): spec de cierre de diseño e integracion de hook de Impeccable"
```
