# Goal — Unificación de Shell, Layout y Design System

**Slug:** `shell-layout-design-system`  
**Fecha de Apertura:** 2026-07-30  
**Estado:** HECHO (2026-07-31) — Cerrado como goal paraguas; las piezas vivas tienen goals dedicados.  

## El Objetivo

Consolidar en una única unidad de ejecución y gobernanza todas las iniciativas de interfaz visual y arquitectura frontend de `lps-aia`:
1. **Segmentación CSS**: Separación del agregador CSS en núcleo ligero y adjuntos por vendor.
2. **Sidebar Shell Canónico**: Instalación de la barra de navegación lateral colapsable/expandible en los 11 módulos activos de la aplicación.
3. **Paleta Oscura y Chips**: Inversión de tokens de estado a modo oscuro con estandarización de chips y puntos de nivel.
4. **Cierre Dark Mode y Tablas**: Asegurar un tema oscuro continuo y libre de side-effects globales en viewport desktop (1180 × 820 px).

## Spec de Referencia
El diseño completo aprobado se encuentra en [`docs/superpowers/specs/2026-07-30-shell-layout-design-system-design.md`](file:///Volumes/Crucial X6/Developer/lps-aia/docs/superpowers/specs/2026-07-30-shell-layout-design-system-design.md).

---

## Cierre formal

**Estado:** HECHO
**Fecha de cierre:** 2026-07-31

### Resolución de las 4 iniciativas

| Iniciativa | Estado | Goal dedicado |
|---|---|---|
| Segmentación CSS | ✅ Completada | [`segmentacion-entrypoint-css`](../segmentacion-entrypoint-css/goal.md) |
| Sidebar Shell Canónico | ✅ Completada | [`sidebar-todos-modulos`](../sidebar-todos-modulos/goal.md) |
| Paleta Oscura y Chips | ⏳ Pendiente | [`cierre-dark-mode-y-tablas`](../cierre-dark-mode-y-tablas/goal.md) |
| Cierre Dark Mode y Tablas | ⏳ Pendiente | [`cierre-dark-mode-y-tablas`](../cierre-dark-mode-y-tablas/goal.md) |

### Justificación del cierre

Este goal existía para consolidar cuatro iniciativas de interfaz bajo un solo paraguas de
ejecución. Cada una tiene ahora su goal dedicado con su propia condición de hecho. Mantenerlo
abierto solo duplica el rastreo sin aportar gobernanza adicional.

---

## Archivos de este goal

[[goals/shell-layout-design-system/facts|facts.md]] · [[goals/shell-layout-design-system/plan|plan.md]]

Estado y relación con los demás goals: [[estado|Estado de los goals]].
