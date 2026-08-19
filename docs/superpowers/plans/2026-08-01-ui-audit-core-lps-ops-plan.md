---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-01
areas: [lps]
fuente: docs/superpowers/plans/2026-08-01-ui-audit-core-lps-ops-plan.md
resumen: Estandarizar al 100% las pantallas de Auth, Selector de Proyecto, Core LPS y Operaciones Legadas usando el Design System AIA (harden y polish visual), logrando…
---

# LPS Core & Ops UI Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Estandarizar al 100% las pantallas de Auth, Selector de Proyecto, Core LPS y Operaciones Legadas usando el Design System AIA (harden y polish visual), logrando un Audit Health Score 10/10 en resoluciones Desktop Dark Mode.

**Architecture:** Refactorización vertical pantalla por pantalla. En cada archivo `.view.php` se limpiarán IDs duplicados/anómalos, se removerán estilos inline, se purgará HTML zombi y se reemplazarán primitivas Bootstrap (`.btn`, `.form-control`, `.modal`, `.badge`) por tokens AIA (`.aia-btn`, `.aia-input`, `.aia-modal`, `.aia-chip`).

**Tech Stack:** PHP 8.3 (MVC), HTML5, CSS Nativo (Tokens AIA), jQuery/Vanilla JS.

## Global Constraints

- Restricción de vista: Exclusivo para Desktop (≥ 1180 × 820 px) en Dark Mode. No probar ni arreglar móvil/tablet o tema claro.
- Comandos a utilizar: `/impeccable audit`, `$impeccable harden`, `$impeccable polish` aplicados a nivel conceptual en cada vista.
- Modificaciones deben preservar la funcionalidad y el `unique_id` del DOM/eventos.
- Ejecutar verificación PHP (`docker compose exec app php -l <archivo>`) después de tocar cualquier `.view.php` o `.php` antes de dar por completado un task.
- Validar cargando la vista en el navegador mediante `/dev/entrar`.

---

### Task 1: Auth & Core Navigation

**Files:**
- Modify: `views/auth/login.view.php`
- Modify: `views/auth/password-forgot.view.php`
- Modify: `views/auth/password-reset.view.php`
- Modify: `views/core/project_selector.view.php`
- Modify: `views/partials/shell_sidebar.php`
- Modify: `views/partials/drawer_unificado.php`

**Interfaces:**
- Consumes: N/A
- Produces: Autenticación base y navegación lateral lista bajo el nuevo estándar visual.

- [ ] **Step 1: Auditoría In-Situ (Core)**
  Lee los archivos objetivo buscando clases `.btn`, `.form-control`, e IDs repetidos.
- [ ] **Step 2: Harden (Core)**
  Borra estilos en línea y HTML zombie, elimina IDs duplicados.
- [ ] **Step 3: Polish (Core)**
  Reemplaza `.form-control` por `.aia-input` y botones por `.aia-btn`. Adapta la estructura a `.aia-modal` si hay algún cuadro de diálogo.
- [ ] **Step 4: Verify**
  Ejecuta:
  `docker compose exec app php -l views/auth/login.view.php`
  `docker compose exec app php -l views/core/project_selector.view.php`
  Revisa visualmente entrando a `/dev/entrar?u=test.A&p=Proyecto`.
- [ ] **Step 5: Commit**
  ```bash
  git add views/auth/ views/core/ views/partials/
  git commit -m "style(auth): impeccable polish on auth and core nav surfaces"
  ```

---

### Task 2: Programación Core (LPS)

**Files:**
- Modify: `views/programa-general/programa_general.view.php`
- Modify: `views/programacion-intermedia/programacion_intermedia.view.php`

**Interfaces:**
- Consumes: Nav Shell
- Produces: Vistas principales de cronogramas LPS limpias.

- [ ] **Step 1: Auditoría In-Situ (LPS)**
  Revisa los grandes contenedores envolventes y modales internos de DataTables/Handsontable.
- [ ] **Step 2: Harden (LPS)**
  Normaliza `id=` para no chocar con elementos del DOM padre.
- [ ] **Step 3: Polish (LPS)**
  Cambia `.btn` por `.aia-btn`, `.form-control` por `.aia-input`, `.badge` por `.aia-chip`. Adapta modales a `.aia-modal`.
- [ ] **Step 4: Verify**
  Ejecuta:
  `docker compose exec app php -l views/programa-general/programa_general.view.php`
  Revisa visualmente entrando a la ruta `/programa-general`.
- [ ] **Step 5: Commit**
  ```bash
  git add views/programa-general/ views/programacion-intermedia/
  git commit -m "style(lps-core): impeccable polish on programa general and intermedia"
  ```

---

### Task 3: Programación Semanal

**Files:**
- Modify: `views/programacion-semanal/programacion_semanal.view.php`
- Modify: `views/programacion-semanal/CNP.view.php`
- Modify: `views/programacion-semanal/CNC.view.php`
- Modify: `views/programacion-semanal/CIC.view.php`
- Modify: `views/programacion-semanal/partials/_changeMonitorModal.php`
- Modify: `views/programacion-semanal/partials/modal_reabrir.php`

**Interfaces:**
- Consumes: LPS Core
- Produces: Flujo de reunión semanal estabilizado visualmente, sin desbordes.

- [ ] **Step 1: Auditoría In-Situ (Semanal)**
  Explora el módulo más complejo de LPS en busca de modales anidados.
- [ ] **Step 2: Harden (Semanal)**
  Elimina duplicidad de Modales generada por inyección de parciales. 
- [ ] **Step 3: Polish (Semanal)**
  Pasa los chips de estado a `.aia-chip`, formularios a `.aia-input`, y botones de acción rápida a `.aia-btn`. 
- [ ] **Step 4: Verify**
  Ejecuta:
  `docker compose exec app php -l views/programacion-semanal/programacion_semanal.view.php`
  Navega visualmente hacia `/programacion-semanal` y sus tabs (CNP/CNC/CIC).
- [ ] **Step 5: Commit**
  ```bash
  git add views/programacion-semanal/
  git commit -m "style(semanal): impeccable polish on programacion semanal and submodules"
  ```

---

### Task 4: Operaciones

**Files:**
- Modify: `views/profesionales/profesionales.view.php`
- Modify: `views/subcontratistas/subcontratistas.view.php`
- Modify: `views/indicadores/indicadores.view.php`
- Modify: `views/control-cambios/controlCambios.view.php`

**Interfaces:**
- Consumes: Nav Shell
- Produces: Listados operativos heredados integrados al tema de AIA.

- [ ] **Step 1: Auditoría In-Situ (Ops)**
  Identifica los formularios de filtrado y creación rápida.
- [ ] **Step 2: Harden (Ops)**
  Limpia código HTML comentado histórico en estas vistas más antiguas.
- [ ] **Step 3: Polish (Ops)**
  Reemplaza cajas de selección y búsqueda a `.aia-input`. Transforma modales antiguos a `.aia-modal`.
- [ ] **Step 4: Verify**
  Ejecuta:
  `docker compose exec app php -l views/profesionales/profesionales.view.php`
  `docker compose exec app php -l views/subcontratistas/subcontratistas.view.php`
  Revisa visualmente y sin fallos.
- [ ] **Step 5: Commit**
  ```bash
  git add views/profesionales/ views/subcontratistas/ views/indicadores/ views/control-cambios/
  git commit -m "style(ops): impeccable polish on operational surfaces"
  ```
