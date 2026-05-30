# PDCA: Unificación de Modales — Brand Manual AIA (Corporativo)

**Inicio:** 2026-05-29
**Actualización de alcance:** 2026-05-29
**Objetivo:** Unificar los 35 modales Bootstrap visibles de la app al diseño `.aia-modal` alineado con el manual de marca AIA corporativo (Verde)

---

## P — PLAN (Planificar)

### Meta
100% de modales Bootstrap visibles en la app usando el patrón `.aia-modal` con paleta corporativa Verde AIA, sin regresiones visuales ni funcionales.

### Estado actual
- 35 modales Bootstrap inventariados
- Solo 4 usan `.aia-modal` (PDC, Listado, Contratos) = 11.4% cobertura
- `--shadow-glass` indefinido en `tokens.css` pero referenciado en `.modal-content`
- IDs repetidos entre vistas separadas no son conflicto por sí solos; solo se corrigen duplicados que coexistan en la misma página renderizada
- Conflicto real detectado: `cm-count-total` duplicado dentro de `_changeMonitorModal.php`
- `modal_spinner` con estructura HTML rota
- `modal_leyenda_colores` definido en varias vistas separadas; se mantiene salvo conflicto probado en una misma página
- 22 modales default Bootstrap sin brand = 62.9% pendiente

### Decisiones de alcance confirmadas

- Paleta final: Verde corporativo AIA (`#1a5633`) como color principal.
- Convención CSS: reutilizar el sistema existente (`.aia-modal`, `.aia-modal__eyebrow`, `.aia-btn-primary`, `.aia-btn-secondary`) antes de crear nuevas clases.
- IDs: corregir únicamente duplicados reales dentro del mismo DOM renderizado, no coincidencias globales entre páginas independientes.
- Validación: crear prueba mínima enfocada en modales (`tests/browser/modal-brand.mjs`) y dejar `change-monitor.mjs` como validación secundaria por su reset destructivo de tracking.

### Diseño target `.aia-modal`

| Elemento | Especificación | CSS |
|----------|---------------|-----|
| Header | `background: linear-gradient(135deg, #1a3c2a, #1a5633)`, texto blanco | `.aia-modal .modal-header` |
| Eyebrow | Badge pill verde claro + texto verde oscuro | `.aia-modal__eyebrow` |
| Título | Montserrat 700, blanco | `.aia-modal .modal-title` |
| Body | Fondo `#F4F1EA` (Linen), texto Inter 400 | `.aia-modal .modal-body` |
| Footer | Fondo `#FAFAFA` (Alabaster), borde verde sutil | `.aia-modal .modal-footer` |
| Botón primario | `bg: #1a5633`, `border-radius: 999px`, texto blanco | `.aia-modal .aia-btn-primary` |
| Botón secundario | Outline verde `#1a5633`, texto verde | `.aia-modal .aia-btn-secondary` |
| Input/Select | Borde `rgba(26,86,51,0.18)`, focus ring verde | `.aia-modal .form-control` |
| Tabla dentro de modal | Head: `#d5e5db`, row hover: `#eef5f1` | `.aia-modal .table` |
| Cierre (×) | Blanco, opacidad 0.7 → hover 1.0 | `.aia-modal .close` |
| Sombra | `box-shadow: 0 8px 32px rgba(30, 30, 30, 0.12)` | usa `--shadow-glass` corregido |

### Checklist PLAN

- [x] **P.1** Definir variable `--shadow-glass` en `tokens.css`
- [x] **P.2** Corregir HTML roto de `modal_spinner` (agregar `modal-dialog` + `modal-content` + `modal-body`)
- [x] **P.3** Corregir solo IDs duplicados dentro del mismo DOM renderizado (`cm-count-total` en `_changeMonitorModal.php`)
- [x] **P.4** Verificar que `.aia-modal` está definido en `styles.css` (línea ~1723), sin dependencia de framework
- [x] **P.5** Adaptar `.aia-modal` a Verde corporativo AIA sin crear API CSS paralela innecesaria
- [x] **P.6** Mapear orden de migración (menor riesgo → mayor riesgo)

---

## D — DO (Ejecutar)

### Fase 1 — Infraestructura (archivos base)

```
▶ P.1 tokens.css         → agregar --shadow-glass
▶ P.2 funcionesGenerales6.js → reparar modal_spinner
▶ P.3 _changeMonitorModal.php → corregir duplicado real cm-count-total
```

### Fase 2 — Migración por vista

| Fase | Vista | Modales | Prioridad |
|------|-------|---------|-----------|
| 2a | `programacion_semanal.view.php` | 8 modales | Alta (más usada) |
| 2b | `programaGeneralActualizar.view.php` | 6 modales | Alta |
| 2c | `programacion_intermedia.view.php` | 3 modales | Alta |
| 2d | `controlCambios.view.php` | 3 modales | Media |
| 2e | `listadoActividades.view.php`, `pdc.view.php`, `contratos.view.php` | 4 modales (ya .aia-modal) | ✅ OK |
| 2f | `CNP.view.php`, `CIC.view.php` | 3 modales | Media |
| 2g | `funcionesGenerales6.js`, `hot.js` | 8 modales dinámicos | Alta |

### Cómo migrar cada modal

1. Agregar clase `aia-modal` al `<div class="modal">` principal
2. Verificar que `<div class="modal-dialog">` tenga `modal-dialog-centered`
3. Agregar eyebrow `<span class="aia-modal__eyebrow">...</span>` dentro del header cuando aporte contexto
4. Remover cualquier `style` inline de background/color en header, body, footer
5. Cambiar botones críticos a la convención existente: `aia-btn-primary` / `aia-btn-secondary` cuando sea seguro
6. Verificar inputs/selects estén dentro de `.aia-modal` para heredar focus ring verde
7. Si hay tablas, aplicar estilos por selector descendiente `.aia-modal .table` antes de crear clases nuevas
8. No renombrar IDs por coincidencia global; solo si el duplicado aparece en el mismo DOM o rompe un selector JS

### Checklist DO

- [x] **D.1** `tokens.css`: agregar `--shadow-glass: 0 8px 32px rgba(30, 30, 30, 0.12)`
- [x] **D.2** `funcionesGenerales6.js`: corregir estructura HTML de `modal_spinner`
- [x] **D.3** `_changeMonitorModal.php`: renombrar el duplicado real `cm-count-total` y actualizar referencias JS si aplica
- [x] **D.4** No renombrar `modalEliminar`, `modalCargarExcel` ni `modal_leyenda_colores` salvo conflicto probado en la misma página
- [x] **D.5** `styles.css`: actualizar `.aia-modal` a paleta Verde corporativo AIA y mantener convención BEM existente
- [x] **D.6** Migrar 8 modales de `programacion_semanal.view.php` a `.aia-modal`
- [x] **D.7** Migrar 6 modales de `programaGeneralActualizar.view.php` a `.aia-modal`
- [x] **D.8** Migrar 3 modales de `programacion_intermedia.view.php` a `.aia-modal`
- [x] **D.9** Migrar 3 modales de `controlCambios.view.php` a `.aia-modal`
- [x] **D.10** Migrar 3 modales de `CNP.view.php` + `CIC.view.php` a `.aia-modal`
- [x] **D.11** Migrar 8 modales dinámicos JS (`funcionesGenerales6.js`, `hot.js`)
- [x] **D.12** Verificar `modalImportacionExitosa`: mantener éxito en Verde AIA y retirar estilos inline innecesarios
- [x] **D.13** Verificar `modal_cnc_hot`: alinear footer con paleta Verde AIA
- [x] **D.14** `_changeMonitorModal.php`: migrar el contenedor a `.aia-modal` sin eliminar clases `.cm-*` usadas por JS/estado visual
- [x] **D.15** Crear `tests/browser/modal-brand.mjs` como verificación mínima no destructiva

---

## C — CHECK (Verificar)

### Pruebas automatizadas (Playwright)

```bash
node tests/browser/modal-brand.mjs
```

Validación secundaria, solo con confirmación porque resetea tracking de cambios:

```bash
node tests/browser/change-monitor.mjs
```

### Checklist CHECK

- [x] **C.1** Test mínimo `modal-brand.mjs` pasa sin regresiones
- [x] **C.2** Cada modal migrado tiene header `background` Verde AIA (inspeccionar RGB/computed style)
- [x] **C.3** Cada modal migrado tiene body con fondo `#F4F1EA`
- [x] **C.4** Botones primarios: `bg #1a5633`, `border-radius ≥ 8px`, texto blanco
- [x] **C.5** Sin errores en consola JS relacionados con modales
- [x] **C.6** Sin duplicados de ID dentro del DOM renderizado de cada página probada
- [x] **C.7** `modal_spinner` funciona correctamente (verificar backdrop + estructura)
- [x] **C.8** `--shadow-glass` resuelve a valor válido (inspeccionar CSS computed)
- [x] **C.9** Responsive: modales centrados y legibles en viewport móvil (375px)
- [x] **C.10** Sin regresiones en funcionalidad existente de cada modal
- [x] **C.11** Títulos de modal legibles (contraste blanco sobre Verde AIA ≥ 4.5:1)
- [x] **C.12** Inputs dentro de modales tienen focus ring verde visible

### Criterios de aceptación

| Métrica | Target | Método |
|---------|--------|--------|
| Cobertura `.aia-modal` | 100% (35/35 modales) | `rg 'aia-modal' views/ --include '*.php'` |
| Test mínimo Playwright | pass | `node tests/browser/modal-brand.mjs` |
| IDs duplicados en DOM renderizado | 0 | `document.querySelectorAll('[id]')` por página probada |
| Errores JS console | 0 | Playwright console.error assertion |
| Errores PHP | 0 | Log de errores del servidor |

---

## A — ACT (Ajustar / Estandarizar)

### Acciones correctivas detectadas durante CHECK

| # | Problema detectado | Acción |
|---|-------------------|--------|
| A.1 | Modal sin `modal-dialog-centered` | Agregar clase |
| A.2 | Contraste insuficiente en eyebrow | Oscurecer texto o aclarar fondo verde claro |
| A.3 | Input focus ring no visible | Agregar `box-shadow: 0 0 0 3px rgba(26,86,51,0.22)` |
| A.4 | Modal no cierra con ESC | Agregar `data-dismiss="modal"` o JS handler |

### Estandarización

1. **Checklist de código nuevo**: Todo modal nuevo DEBE usar `.aia-modal`
2. **Review de PRs**: Verificar que modales nuevos usen el patrón
3. **Variables CSS**: `--aia-modal-*` para futuras personalizaciones sin tocar HTML
4. **Componente reutilizable**: Evaluar crear helper PHP `renderModal($id, $title, $body, $footer)` que genere estructura `.aia-modal` completa
5. **IDs**: Todo `id` debe ser único dentro de la página renderizada; repeticiones entre vistas independientes no son defecto por sí solas

### Checklist ACT

- [x] **A.1** Documentar patrón `.aia-modal` en `AGENTS.md` (sección Frontend → Modal System)
- [x] **A.2** Agregar entrada en `docs/VISTAS-MODULOS.md` con el estándar de modales
- [x] **A.3** Si hay issues A.1-A.4, corregirlos en ciclo PDCA hijo
- [x] **A.4** Evaluar crear helper PHP `renderAiaModal()` en `src/helpers/modals.php` (no creado: no aporta reutilización suficiente todavía)
- [x] **A.5** Actualizar ROADMAP.md con estado de unificación de modales
- [x] **A.6** Post-sesión: persistir hallazgos en MEMORY.md (no aplica: no existe `MEMORY.md` en el proyecto)

---

## Resumen ejecutivo

```
P: Diseñar estándar .aia-modal Verde AIA → 6 tareas checklist
D: Migrar 35 modales en 7 fases → 15 tareas checklist
C: Verificar test mínimo + 12 criterios → 13 tareas checklist
A: Estandarizar y documentar → 6 tareas checklist
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Total: 40 tareas atómicas verificables
```

**Cobertura inicial:** 11.4% (4/35)
**Cobertura validada:** 100% de modales renderizados en 8 rutas críticas (`modal-brand.mjs`: 59/59 checks OK)
**Target:** 100%
**Riesgo principal:** Modales con JS que dependen de IDs específicos o clases `.cm-*` funcionales
**Mitigación:** Cambiar solo duplicados reales en DOM renderizado + test Playwright mínimo + checklist de verificación por modal

---

*Generado por kaizen-clon · Método PDCA · 2026-05-29*
