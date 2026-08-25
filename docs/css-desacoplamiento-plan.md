---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-03-25
fuente: docs/css-desacoplamiento-plan.md
resumen: Objetivo: Hacer que un refresh de marca se resuelva principalmente desde CSS, sin tocar lógica ni romper el legacy/frontend ni el admin. Principio rector: cero…
---

# Plan de Implementación: Desacoplamiento Visual CSS

**Objetivo**: Hacer que un refresh de marca se resuelva principalmente desde CSS, sin tocar lógica ni romper el legacy/frontend ni el admin.  
**Principio rector**: cero big-bang, migración aditiva por capas, con compatibilidad temporal.  
**Invariantes**: No mezclar refactor visual con cambios funcionales; no renombrar ids/clases sin alias temporal; JS solo toca geometría runtime (alto, ancho, overflow, posición, zoom).

**Actualización 14 de julio de 2026**: el loader `public/js/cargarDatosGeneralesPaginaNoCC.js` y el entrypoint antiguo `public/js/modules/programa_general/main.js` fueron retirados al confirmar que no tenían consumidores runtime. Las cifras de baseline se conservan como registro histórico; ninguno de los dos queda como archivo foco pendiente.

---

## 1. Baseline Actual

### 1.1 Inventario de Deuda Visual

| Categoría | Cantidad | Archivos Hotspot |
|-----------|----------|------------------|
| Bloques `<style>` en `views/` | 14 | `programacion_semanal.view.php`, `CIC.view.php`, `CNC.view.php`, `CNP.view.php`, `programa_general.view.php`, `programacion_intermedia.view.php`, `pdc.view.php`, `profesionales.view.php`, `subcontratistas.view.php`, `controlCambios.view.php`, `indicadores.view.php`, `listadoActividades.view.php`, `programaGeneralActualizar.view.php`, `login.view.php` |
| Atributos `style=` en `views/` | 391 | Concentrados en vistas legacy y handsontable renderers |
| Atributos `style=` en `admin/views/` | 22 | users/index.php, users/create.php, users/edit.php, projects/index.php |
| Bloques `<style>` en `admin/views/` | 4 | users/create.php, users/edit.php, users/index.php, projects/index.php |
| JS con hardcoded brand colors | 222 (baseline histórica) | `AiaAlertInterceptor.js`, `funcionesGenerales6.js`, el entrypoint PG retirado el 14 de julio de 2026, `hot_actualizar.js`, `programacion_semanal/hot.js` |
| Inyección de `<style>` desde JS | Presente | `AiaAlertInterceptor.js:14` |

### 1.2 Patrones de Entrypoint

| Tipo | Páginas | Carga de Assets |
|------|---------|-----------------|
| **Static-head** | Login frontend, Login admin, Project selector, Admin dashboard, Admin users, Admin projects | CSS/JS en `<head>` de la página PHP |
| **JS-injected head + shell** | 14 vistas legacy (programacion_semanal, programa_general, indicadores, control-cambios, etc.) | `public/js/linksComunesHead2.js` → `head.innerHTML` + `public/js/cargarDatosGeneralesPagina2.js` → `#encabezado` |

### 1.3 Archivos con Mayor Acoplamiento Visual

| Prioridad | Archivo | Tipo de Acoplamiento |
|-----------|---------|----------------------|
| 1 | `views/programacion-semanal/CIC.view.php` | ~297 `style=` inline (formularios) |
| 2 | `views/programa-general-actualizar/programaGeneralActualizar.view.php` | Inline styles + modales con colores hardcodeados |
| 3 | `views/programacion-semanal/programacion_semanal.view.php` | `<style>` + modal CNC con estilos inline |
| 4 | `public/js/core/AiaAlertInterceptor.js` | Inyecta `<style>` completo + hardcoded brand colors |
| 5 | `public/js/funcionesGenerales6.js` | HTML-string modal injector con inline styles |
| 6 | `views/pdc/pdc.view.php` | `<style>` + jQuery `.css()` runtime row coloring |
| 7 | `views/profesionales/profesionales.view.php` | `<style>` + renderer mobile card con estilos |
| 8 | `views/subcontratistas/subcontratistas.view.php` | Same pattern + iOS-blue hardcodeados |
| 9 | `views/programacion-intermedia/programacion_intermedia.view.php` | 2 `<style>` blocks + TomSelect skinning hardcoded |
| 10 | `public/js/modules/programa_actualizar/hot_actualizar.js` | Renderer HTML con `style` + `td.style.backgroundColor` mutations |

### 1.4 Shell Compartido Actual

| Origen | Función | Riesgo |
|--------|---------|--------|
| `src/View/Components/NavbarComponent.php:25` | Carga su propio CSS y JS, navbar PHP-side | Duplica carga con legacy loader |
| `public/js/cargarDatosGeneralesPagina2.js:1` | Inyecta CSS, markup shell, hidrata contexto, permisos, callbacks | No solo layout; es orchestration |
| `public/js/cargarDatosGeneralesPaginaNoCC.js` | Variante legacy más antigua, retirada el 14 de julio de 2026 | Se confirmó sin consumidores runtime |
| `public/js/linksComunesHead2.js:17` | `head.innerHTML = staticContent` | Orden de assets sensible |

### 1.5 CSS Ya Existente

| Archivo | Estado |
|---------|--------|
| `public/css/tokens.css` | **Listo**: primitives OKLCH (verde AIA, naranja construcción, arquitectura, alertas, spacing, radius, z-index, shadows) |
| `public/css/styles.css` | **Conflicto**: redefine otra paleta + sistema semántico, compite con tokens.css |
| `public/css/navbar.css` | **Funcional**: drawer, breakpoints, user island |

---

## 2. Arquitectura Objetivo

```
public/css/
├── tokens.css              # Brand primitives (no borrar, solo ampliar)
├── theme-semantic.css       # Alias semánticos: --color-brand-primary, --surface-page, --text-muted, --radius-card
├── foundation.css          # Reset, tipografía base, espaciado utilitario mínimo
├── navbar-shell.css        # Navbar, drawer, context-bar, notifications (del legacy loader)
├── components/
│   ├── badges-chips.css
│   ├── toolbars.css
│   ├── dialogs.css
│   ├── dropdowns.css
│   ├── tables.css
│   ├── aia-alerts.css      # Para AiaAlertInterceptor
│   ├── modals-legacy.css   # Para funcionesGenerales6
│   └── table-states.css   # Estados visuales de Handsontable
├── modules/
│   ├── programa-general.css
│   ├── programacion-intermedia.css
│   ├── programa-general-actualizar.css
│   ├── programacion-semanal.css
│   ├── indicadores.css
│   ├── control-cambios.css
│   ├── pdc.css
│   ├── profesionales.css
│   ├── subcontratistas.css
│   └── project-selector.css
└── admin/
    ├── admin-custom.css   # Ya existe
    └── modules/
        ├── admin-users.css
        └── admin-projects.css
```

**Contratos JS → CSS**:
- JS solo modifica clases/atributos/variables CSS
- JS no usa `.css()` para theme
- JS no inyecta `<style>` blocks (excepto AiaAlertInterceptor como特例 temporal)

---

## 3. Fases y Lotes

### Fase 1: Contrato y Fundación (PR 1-2)

#### Lote 0: Inventario y Contrato
- **Propósito**: Establecer el mapa de guerra real.
- **Entragas**:
  - Matriz de `id="head"` vs `id="encabezado"` por vista.
  - API pública de shell congelada: `#encabezado`, `#notificationBadge`, `#ctxProyecto`, `#ctxModulo`, `#ctxSemanaBadge`, `#baseDatos`, `#permiso`, `#semana`, hidden inputs.
  - Lista de clases/ids que son contrato público para JS.
  - Separación: páginas `static-head` vs `js-injected-head`.
- **Archivos foco**: Los 14 archivos de vista legacy + loaders.
- **Regla de salida**: Ningún cambio todavía; solo documento de контракт.

#### Lote 1: Tokens + Theme Semántico (PR 1)
- **Propósito**: Una sola fuente de verdad de marca.
- **Trabajo**:
  - Conservar `public/css/tokens.css` como está.
  - Crear `public/css/theme-semantic.css` con alias: `--color-brand-primary` → `--aia-green-primary`, `--surface-page` → `--aia-bg-linen`, etc.
  - Hacer que `styles.css` y `navbar.css` consuman alias semánticos en lugar de duplicar variables crudas.
  - **No borrar** variables viejas en esta fase; dejar alias temporales.
- **Protección**: Si algo se rompe, revertimos solo el theme-semantic.css, tokens.css sigue intacto.
- **Criterio de éxito**: Cambiar 3-5 decisiones visuales del piloto editando solo CSS base.

#### Lote 2: Entry Points Estáticos de Bajo Riesgo (PR 2)
- **Propósito**: Probar carga estática sin desmontar el legacy.
- **Páginas piloto** (ya son static-head):
  - `views/auth/login.view.php`
  - `views/core/project_selector.view.php`
  - `admin/views/pages/login.php`
  - `admin/views/layouts/main.php` (dashboard, users, projects)
- **Trabajo**:
  - Ordernar CSS por capas en el `<head>`: vendor → tokens → theme → shell → module.
  - **No tocar** `public/js/linksComunesHead2.js` todavía.
- **Protección**: Mantener `linksComunesHead2.js` para las 14 vistas que lo necesitan.
- **Criterio de éxito**: Las 4 rutas funcionan idénticas, cero errores de consola, responsive intacto.

### Fase 2: Shell Compartido (PR 3)

#### Lote 3: Shell y Context Bar
- **Propósito**: Sacar navbar/context-bar del modo "HTML+CSS por JS".
- **Trabajo**:
  - HTML estructural del shell pasa a PHP/partials.
  - JS solo inicializa eventos y estado.
  - Todo `style=""` del shell → clases CSS.
  - Compatibilidad puente: clases legacy y nuevas coexistiendo 1 fase.
- **Archivos foco**:
  - `src/View/Components/NavbarComponent.php` (ya existe)
  - `public/js/cargarDatosGeneralesPagina2.js` (reducir responsabilidades)
  - `public/css/navbar-shell.css` (nuevo o refactorizado)
- **Criterio de éxito**: Navbar, drawer, context-bar, notifications cambian visualmente desde CSS sin tocar JS.

### Fase 3: Admin y Standalone (PR 4)

#### Lote 4: Admin + Páginas Independientes
- **Propósito**: Ganar terreno rápido en superficies menos frágiles.
- **Archivos foco**:
  - `admin/views/pages/users/index.php`
  - `admin/views/pages/users/create.php`
  - `admin/views/pages/users/edit.php`
  - `admin/views/pages/projects/index.php`
  - `views/auth/login.view.php` (refinamiento)
  - `views/core/project_selector.view.php` (refinamiento)
- **CSS nuevos**: `admin/public/css/modules/admin-users.css`, `admin/public/css/modules/admin-projects.css`
- **Trabajo**: Mover `<style>` a CSS externo, reemplazar `style=""` por clases, encapsular por root de página.
- **Criterio de éxito**: Admin y páginas standalone quedan sin estilos embebidos relevantes.

### Fase 4: Legacy Medio (PR 5)

#### Lote 5: Vistas de Complejidad Intermedia
- **Propósito**: Deuda media antes de HOT.
- **Archivos foco**:
  - `views/indicadores/indicadores.view.php`
  - `views/control-cambios/controlCambios.view.php`
  - `views/listado-actividades/listadoActividades.view.php`
  - `views/profesionales/profesionales.view.php`
  - `views/subcontratistas/subcontratistas.view.php`
  - `views/pdc/pdc.view.php`
- **Trabajo**: Extraer bloques `<style>`, convertir `style=""` en clases reutilizables, preservar ids y hooks JS.
- **Criterio de éxito**: Estas vistas conservan funcionalidad y pasan a depender de CSS modular.

### Fase 5: Desacople Visual del JS (PR 6)

#### Lote 6: JS Deja de Dibujar Estilos
- **Propósito**: Branding sale del JS.
- **Archivos foco**:
  - `public/js/core/AiaAlertInterceptor.js`
  - `public/js/funcionesGenerales6.js`
  - `public/js/components/notifications.js`
  - `public/js/datatable-height-manager.js`
- **CSS nuevos**: `public/css/components/aia-alerts.css`, `public/css/components/modals-legacy.css`
- **Trabajo**: Sacar `<style>` inyectado desde JS, reemplazar hex/rgba por clases y tokens, mantener en JS solo comportamiento.
- **Criterio de éxito**: Alertas, modales y estados comunes responden a CSS centralizado.

### Fase 6: HOT y Suite Semanal (PR 7+)

#### Lote 7: Handsontable y Programación Semanal
- **Propósito**: Cerrar la parte más difícil cuando todo lo demás ya esté estable.
- **Archivos foco**:
  - Vistas: `programa_general.view.php`, `programacion_intermedia.view.php`, `programaGeneralActualizar.view.php`, `programacion_semanal.view.php`, `CNP.view.php`, `CNC.view.php`, `CIC.view.php`
  - JS: `public/js/modules/programa_general/hot.js`, `public/js/modules/programacion_intermedia/hot.js`, `public/js/modules/programa_actualizar/hot_actualizar.js`, `public/js/modules/programacion_semanal/hot.js`
- **Actualización**: El entrypoint antiguo de Programa General fue retirado el 14 de julio de 2026 y ya no forma parte de este lote.
- **CSS nuevos**: Módulos por vista
- **Trabajo**: Sacar CSS embebido de vistas, reemplazar renderers con `style` inline por clases (`is-pending`, `is-mapped`, `is-readonly`, `is-critical`), dejar a JS solo composición de estado.
- **Protección**: Empezar por `programa_general` y `programacion_intermedia`; dejar `CIC` y suite semanal para el final.
- **Criterio de éxito**: Un cambio de branding en estos módulos se logra casi completo desde CSS.

### Fase 7: Retiro del Legacy Loader (PR final)

#### Lote 8: Deprecación de Loaders
- **Propósito**: CSS ya no depende de loaders JS.
- **Archivos foco**:
  - `public/js/linksComunesHead2.js`
  - `public/js/cargarDatosGeneralesPagina2.js`
- **Actualización**: La variante NoCC fue retirada el 14 de julio de 2026; no queda como trabajo pendiente de este lote.
- **Trabajo**: Adelgazar o deprecar loaders, dejar carga de estilos en layouts/partials, retirar compatibilidad solo cuando el último consumidor migre.
- **Criterio de éxito**:
  - 0 branding inyectado por JS
  - 0 páginas críticas reescribiendo el `<head>` en runtime

---

## 4. Reglas de PR / Entrega

| PR | Contenido | Mezcla Permitida? |
|----|------------|-------------------|
| PR 1 | Tokens + Theme Semántico + Contrato de capas | NO - debe ser limpio |
| PR 2 | Entry points estáticos para login/selector/admin login | NO - solo esas 4 rutas |
| PR 3 | Shell/Navbar/Context-bar | NO - solo shell |
| PR 4 | Admin + Standalone | NO - solo admin/users/projects/login/selector |
| PR 5 | Legacy medio | NO - solo lote 5 |
| PR 6+ | HOT y Semanal | NO - solo lote 7 |

**Reglas**:
- Nunca mezclar más de una familia de pantallas por PR.
- Nunca borrar el viejo camino en el mismo PR donde nace el nuevo.
- Si PR falla, se corrige el contrato antes de escalar.

---

## 5. Checklists de Verificación

### 5.1 Gates Automatizados

```bash
# Gate A: inline styles trend down
rg -n 'style\s*=' views admin/views | wc -l

# Gate B: runtime style mutation count
rg -n '\.css\(|style\.backgroundColor|style\.[A-Za-z_]+' public/js | wc -l

# Gate C: no injected CSS from JS in migrated scope
rg -n "createElement\(['\"]style['\"]\)|style\.innerHTML|append\('<style>" public/js

# Gate D: renderer HTML should stop carrying style
rg -n "innerHTML\s*=.*style=|\.html\(.*style=" public/js/modules

# Gate E: hardcoded brand colors should move to tokens
rg -n '#1a5633|#b55211|#e87722|#035766|rgba\(\s*181\s*,\s*82\s*,\s*17' public/js

# Gate F: duplicate toolbar in weekly views
rg -n 'ps-dropdown-nav|ps-dropdown-content|btn-dropdown-trigger' views/programacion-semanal
```

### 5.2 Rutas Mínimas a Probar

| Prioridad | Ruta | Tipo |
|-----------|------|------|
| 1 | `/login` | Static-head |
| 2 | `/proyectos` (selector) | Static-head |
| 3 | `/admin/login` | Static-head |
| 4 | `/admin/` (dashboard) | Static-head |
| 5 | `/admin/usuarios` | Static-head |
| 6 | `/admin/proyectos` | Static-head |
| 7 | `/indicadores` | JS-injected |
| 8 | `/control-cambios` | JS-injected |
| 9 | `/pdc` | JS-injected |
| 10 | `/programa-general` | JS-injected |
| 11 | `/programacion-intermedia` | JS-injected |
| 12 | `/programacion-semanal` | JS-injected |

### 5.3 Breakpoints a Verificar

- Desktop (1920px+)
- Laptop (1366px - 1919px)
- Tablet horizontal (1024px - 1365px)
- Tablet vertical (768px - 1023px)
- Móvil (320px - 767px)

### 5.4 Estados a Verificar

- Hover
- Active
- Disabled
- Loading (spinner)
- Empty state
- Success (verde)
- Error (rojo)
- Modal open/close
- Dropdowns
- Sticky header / table scroll

---

## 6. Estrategia de Rollback

- **Todo debe ser reversible por lote**.
- **Estrategia**:
  - Introducir CSS nuevo sin borrar el viejo en la primera pasada.
  - Mantener alias temporales.
  - Revertir PR/lote completo si rompe shell o layout.
  - **No tocar DB ni lógica de negocio** — el rollback es solo de código estático.
- **Regla dura**: Si el shell compartido falla, se detiene la expansión y no se entra a HOT.

---

## 7. Definición de Éxito Final

- HTML/PHP sin estilos embebidos relevantes.
- JS sin branding ni bloques `<style>`.
- CSS organizado en: foundation → shell → components → modules.
- Refresh de marca posible tocando solo:
  - `public/css/tokens.css`
  - `public/css/theme-semantic.css`
  - CSS de shell/componentes
- Cambios visuales mayores sin necesidad de editar JS de negocio.

---

## 8. Métricas de Progreso

| Métrica | Baseline | Meta Ciclo 1 | Meta Final |
|---------|----------|--------------|------------|
| Bloques `<style>` en `views/` | 14 | 14 (sin cambio) | 0 |
| Atributos `style=` en `views/` | 391 | < 350 | < 50 (solo geometría) |
| JS con hex/rgba hardcodeados | 222 ocurrencias | 222 (sin cambio) | 0 |
| Páginas con JS-injected head | 14 | 14 | 0 |
| Tokens CSS usados en theme | 0% | 30% | 100% |

---

## 9. Riesgos Identificados

| Riesgo | Mitigación |
|--------|------------|
| `linksComunesHead2.js:17` hace `head.innerHTML`; cambiar orden puede romper legacy | Mantenerloader legacy hasta PR final; no cambiar orden de assets en PR1-2 |
| `cargarDatosGeneralesPagina2.js` no solo pinta navbar; también hidrata contexto, permisos, callbacks | Solo extraer markup visual; mantener lógica de hidratación en JS |
| Dos sistemas de navbar (NavbarComponent vs cargarDatosGeneralesPagina2) | Unificar a uno solo al final del PR3; mantener compatibilidad puente durante migración |
| Duplicación de carga CSS (NavbarComponent + loader) | Prevenir en PR3; solo un sistema de navbar activo por fase |
| Alta deuda en CIC.view (~297 inline styles) | Dejar para PR final; demasiado ruidoso para pilotos |
| AiaAlertInterceptor inyecta estilos globales | Tratar como特例; migrar a CSS estático en PR6 |

---

## 10. Orden de Pilotaje Recomendado

### Pilotos del Ciclo 1
1. `views/auth/login.view.php` — Static-head, bajo riesgo, alto impacto visual
2. `views/core/project_selector.view.php` — Static-head, ya usa NavbarComponent
3. `admin/views/pages/login.php` — Static-head, bajo riesgo

### Pilotaje Posterior (Ciclo 2+)
4. `views/programa-general/programa_general.view.php` — Ya semi-modernizado, bajo volumen de inline styles
5. `views/programacion-semanal/CNP.view.php` + `views/programacion-semanal/CNC.view.php` — Similar, fácil probar deduplicación

### Evitar como Piloto Inicial
- `views/programacion-semanal/CIC.view.php` — Too noisy
- `public/js/core/AiaAlertInterceptor.js` — Too cross-cutting

---

## 11. Próximo Paso

1. **Ejecutar Lote 0**: Generar matriz de contratos y mapa de entrypoints real.
2. **Abrir PR 1**: Tokens + theme-semantic + contrato de capas.
3. **Verificar gates**: Validar que piloto login/selector/admin login pasen sin regresión.
