---
capa: fuente
tipo: spec
estado: cerrado
fecha: 2026-07-30
areas: [design-system]
fuente: docs/superpowers/specs/2026-07-30-shell-layout-design-system-design.md
resumen: Diseñó — Unificación de Shell, Layout y Design System
---

# Diseñó — Unificación de Shell, Layout y Design System

**Fecha:** 2026-07-30  
**Slug del Goal:** `shell-layout-design-system`  
**Estado:** Propuesto (aprobado por usuario en fase de brainstorming)

---

## 1. Resumen y Objetivo

Este documento unifica en un solo contrato de diseño y gobernanza todas las iniciativas de interfaz visual, arquitectura CSS y layout del repositorio `lps-aia`:

1. **Segmentación CSS y Gobernanza**: Dividir el agregado pesado `aia-design-system.css` en un núcleo ligero más adjuntos por vendor declarados en manifiestos (`foundation-shell.json`).
2. **Rollout del Sidebar Shell Canónico**: Extender el menú lateral colapsable/expandible (patrón `/programacion-intermedia`) a los 11 módulos operativos de la aplicación.
3. **Inversión de Paleta y Chips de Estado**: Adaptar la paleta de semáforos, chips PDC y puntos de nivel al tema oscuro sin perder contraste ni significado.
4. **Cierre de Dark Mode y Tablas**: Garantizar cobertura 100% en modo oscuro desktop sin side-effects globales de grillas (Handsontable / DataTables).

---

## 2. Restricciones y Contratos de Repositorio

- **Entorno de Visualización Canónico**: Vistas desktop con un viewport canónico de **1180 × 820 px** exclusivamente en **Dark Mode**.
- **Exclusiones Explícitas**: No se soporta ni se prueba mobile, tablet ni el tema `linen`.
- **Aislamiento por Proyecto**: Toda superficie respeta aislamiento por `project_id` y permisos RBAC.
- **Absorción de Goals Previos**: Este goal unificado absorbe y reemplaza:
  - `goals/sidebar-todos-modulos`
  - `goals/cierre-dark-mode-y-tablas`
  - `goals/dark-mode-todos-los-modulos`
  - `goals/segmentacion-entrypoint-css`
  - `goals/design-system-nucleo-gobernanza`
  - Planes individuales en `docs/superpowers/plans/` (`2026-07-28-paleta-estado-oscura.md`, `2026-07-28-chips-tonos-pdc-y-punto-de-nivel.md`, `2026-07-24-control-tower-shell-dark.md`, `2026-07-22-lab-colapsado-primitiva.md`, `2026-07-20-sidebar-canonico-laboratorio.md`).

---

## 3. Secuencia de Ejecución (4 Fases)

### Fase 1: Arquitectura CSS y Gobernanza
- Separar vendors masivos de CSS (ej. `handsontable-module.css`) de las superficies que no los utilizan.
- Crear manifiestos de cargador por superficie y validar con el gate runtime `foundation-shell.json`.

### Fase 2: Rollout Sidebar Shell Canónico (11 Módulos)
- Implementar `views/partials/shell_sidebar.php` y `sidebar_navigation.js` en:
  1. Programa General (`/programa-general`)
  2. Programación Semanal (`/programacion-semanal`)
  3. Actualizar Cronograma (`/actualizar-cronograma`)
  4. Profesionales (`/profesionales`)
  5. Subcontratistas (`/subcontratistas`)
  6. Control de Cambios (`/control-cambios`)
  7. Familias / Categorías de Actividades
  8. Paquetes / Contratos
  9. Módulo PDC (`/pdc`)
  10. Indicadores (`/indicadores`)
  11. Torre de Control (`/torre-control`)
- Garantizar sidebar colapsado por defecto, flyouts opacos y cero scroll horizontal en 1180px.

### Fase 3: Paleta Oscura de Estado y Chips
- Actualizar variables CSS `--ds-color-status-*` a versiones calibradas para fondos oscuros.
- Estandarizar chips de estado en PDC v2 y vistas compartidas con punto de nivel indicador.

### Fase 4: Cierre Dark Mode Total y Verificación de Tablas
- Auditoría visual automatizada y navegada de las 11 superficies en 1180×820 dark.
- Verificación de ausencia de bloqueos de scroll o conflictos entre Handsontable y el shell.

---

## 4. Criterios de Aceptación (Condición de Hecho)

- Todos los gates de `foundation-shell.json` responden verde en PHPStan y tests data-driven.
- Los 11 módulos renderizan la sidebar canónica correctamente en viewport 1180×820 dark.
- Cero archivos cargando CSS no declarado o no utilizado.
- Tests de regresión pasando sin errores de consola.

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** goals/shell-layout-design-system/goal.md cierra el 2026-07-31 con las cuatro iniciativas resueltas, cada una con goal dedicado; el plan hermano esta en docs/archive/superpowers/plans/. Artefactos vivos: foundation-shell.json, shell_sidebar.php, navigation.css

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
