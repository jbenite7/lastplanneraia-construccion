---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-07-15
areas: [design-system]
fuente: docs/brand/aia_design_system_web_apple_inspired.md
resumen: AIA Web Design System — Apple-inspired
---

# AIA Web Design System — Apple-inspired

**Versión:** 1.0.0
**Fuente:** `manual_diseno_new.json`
**Uso:** sitios web, portales internos, dashboards, landings comerciales, módulos de analítica, fichas de proyecto y herramientas operativas.

---

## 1. Dirección del sistema

Este sistema toma la disciplina visual de Apple —claridad, jerarquía, profundidad controlada, motion sobrio, capas translúcidas y radio concéntrico— sin copiar su marca. La marca AIA manda: verde corporativo, acentos por unidad de negocio, tipografía Montserrat + Inter, grilla mobile-first, iconografía lineal y contraste WCAG AA.

### Decisión de diseño

> **AIA debe sentirse técnico, confiable, humano y premium, no decorativo.**

La inspiración Apple se aplica en:

- Interfaces limpias donde el contenido respira.
- Capas con profundidad: `canvas → surface → elevated → overlay → modal`.
- Botones y controles con radio suave.
- Material translúcido solo donde ayuda a separar navegación o acciones.
- Motion corto y predecible.
- Componentes consistentes antes que pantallas “bonitas” aisladas.

### Impacto de negocio

| Decisión | Impacto esperado |
|---|---|
| Tokens semánticos | Menos retrabajo cuando cambie una marca, módulo o tema. |
| Componentes cerrados | Menos inconsistencias entre marketing, dashboards y herramientas internas. |
| Contraste y targets táctiles | Menos errores de usuario, mejor uso en obra, móvil y pantallas con luz fuerte. |
| Acentos por dominio | Más claridad comercial sin inventar estilos por proyecto. |
| Motion y glass moderados | Mejor percepción visual sin castigar rendimiento. |

---

## 2. Principios

### 2.1 Contenido primero

La interfaz debe ayudar a leer, decidir y actuar. Gradientes, blur y sombras se usan para jerarquía o separación, no para decorar.

### 2.2 Marca sobria

El verde corporativo es la acción principal. Naranja, turquesa y morado son acentos por dominio; no deben competir en la misma vista.

### 2.3 Profundidad funcional

Usar capas claras:

```text
Canvas       Fondo general
Surface      Contenedor base
Elevated     Card, dropdown, header sticky
Overlay      Sheet, popover, toast
Modal        Tarea focal o bloqueo temporal
```

### 2.4 Mobile-first real

Los estilos base son para `xs/sm`. Desde `md` se agregan columnas, densidad y navegación secundaria.

### 2.5 Accesibilidad medible

- Texto normal: contraste mínimo `4.5:1`.
- Texto grande e iconos informativos: mínimo `3:1`.
- Target táctil: mínimo `44px`.
- Focus visible en teclado.
- `prefers-reduced-motion` respetado.

---

## 3. Tokens de color

### 3.1 Paleta de marca

| Familia | Principal | Medio | Claro | Muy claro | Uso |
|---|---:|---:|---:|---:|---|
| Corporativo | `#1a5633` | `#2e7d57` | `#a7d5c1` | `#ddefe6` | Marca, CTA, navegación, foco, selección. |
| Construcción | `#b55211` | `#e87722` | `#f4ad77` | `#fbe4d3` | Obra, avance, costos, hitos. |
| Inmobiliario | `#00a499` | `#5ec9bd` | `#ace5e0` | `#e3f9f7` | Comercial, ventas, disponibilidad, pipeline. |
| Arquitectura | `#6752bf` | `#9485d6` | `#c7beef` | `#edeafb` | Diseño, BIM, planos, documentación, links. |
| Alertas | `#c62828` | `#e53935` | `#ef9a9a` | `#fdecec` | Error, riesgo, bloqueo. |
| Advertencias | `#f9a825` | `#ffca28` | `#ffe082` | `#fff8e1` | Advertencia, pendiente, revisión. |

### 3.2 Neutrales web

| Token | Hex | Uso |
|---|---:|---|
| `neutral-0` | `#ffffff` | Surface principal. |
| `neutral-25` | `#fbfdfc` | Fondo de página. |
| `neutral-50` | `#f7faf8` | Canvas general. |
| `neutral-100` | `#eef4f1` | Fondo sutil. |
| `neutral-200` | `#dde7e1` | Separadores suaves. |
| `neutral-300` | `#c7d4cc` | Bordes fuertes. |
| `neutral-500` | `#72857a` | Texto terciario. |
| `neutral-600` | `#52645a` | Texto secundario. |
| `neutral-900` | `#141c18` | Texto principal. |
| `neutral-950` | `#0b100d` | Overlay oscuro. |

### 3.3 Semánticos

| Token | Valor | Uso |
|---|---:|---|
| `bg.canvas` | `#f7faf8` | Fondo raíz. |
| `bg.page` | `#fbfdfc` | Página / body. |
| `bg.subtle` | `#eef4f1` | Secciones suaves. |
| `surface.default` | `#ffffff` | Cards, formularios. |
| `surface.raised` | `rgba(255,255,255,0.82)` | Header sticky, dropdown. |
| `surface.glass` | `rgba(255,255,255,0.68)` | Glass controlado. |
| `surface.tint` | `rgba(221,239,230,0.72)` | Paneles suaves. |
| `text.primary` | `#141c18` | Lectura principal. |
| `text.secondary` | `#52645a` | Metadatos y soporte. |
| `text.tertiary` | `#72857a` | Labels secundarios. |
| `text.inverse` | `#ffffff` | Sobre verde, rojo, morado oscuro. |
| `link` | `#6752bf` | Links y recursos. |
| `border.subtle` | `rgba(26,86,51,0.12)` | Cards y divisores. |
| `border.default` | `rgba(26,86,51,0.20)` | Inputs, tablas. |
| `border.strong` | `rgba(26,86,51,0.36)` | Estados activos. |
| `focus.ring` | `rgba(46,125,87,0.28)` | Accesibilidad teclado. |

### 3.4 Reglas de contraste

| Caso | Texto recomendado |
|---|---|
| Verde corporativo `#1a5633` | Blanco. |
| Verde medio `#2e7d57` | Blanco. |
| Verde claro / muy claro | Texto oscuro. |
| Naranja principal `#b55211` | Blanco. |
| Naranja medio / claro | Texto oscuro. |
| Turquesa `#00a499` | Texto oscuro para UI; blanco solo en texto grande validado. |
| Morado `#6752bf` | Blanco. |
| Rojo `#c62828` | Blanco. |
| Amarillo `#f9a825` | Texto oscuro. No usar blanco. |

---

## 4. Gradientes

Los gradientes no reemplazan color de marca. Son recursos para hero, cards destacadas y estados de alto valor.

| Token | CSS | Uso |
|---|---|---|
| `gradient.primary` | `linear-gradient(135deg, #1a5633 0%, #2e7d57 52%, #00a499 100%)` | CTA destacado, hero, propuesta comercial. |
| `gradient.surface` | `linear-gradient(180deg, rgba(255,255,255,0.90) 0%, rgba(221,239,230,0.72) 100%)` | Cards premium, empty states. |
| `gradient.hero` | Radial + linear | Home, landing o portada de módulo. |
| `gradient.construccion` | `linear-gradient(135deg, #b55211 0%, #e87722 100%)` | Módulos de obra. |
| `gradient.inmobiliario` | `linear-gradient(135deg, #00a499 0%, #5ec9bd 100%)` | Módulos comerciales. |
| `gradient.arquitectura` | `linear-gradient(135deg, #6752bf 0%, #9485d6 100%)` | Módulos de diseño / BIM. |

### Regla de uso

- Máximo un gradiente protagonista por vista.
- En dashboards, usar gradientes solo en encabezados o métricas destacadas.
- No poner texto largo sobre gradientes.
- Siempre validar contraste.

---

## 5. Bordes

### 5.1 Width

| Token | Valor | Uso |
|---|---:|---|
| `border.width.hairline` | `1px` | Divisores suaves. |
| `border.width.default` | `1px` | Inputs, cards, tablas. |
| `border.width.strong` | `2px` | Focus, active, selección fuerte. |

### 5.2 Color

| Token | Valor | Uso |
|---|---:|---|
| `border.subtle` | `rgba(26,86,51,0.12)` | Cards, secciones, header. |
| `border.default` | `rgba(26,86,51,0.20)` | Inputs, tablas, controles. |
| `border.strong` | `rgba(26,86,51,0.36)` | Hover, active, selección. |
| `border.focus` | `#2e7d57` | Estado focus. |
| `border.danger` | `#ef9a9a` | Error. |
| `border.warning` | `#ffe082` | Advertencia. |

### 5.3 Regla Apple-inspired

Combinar borde sutil + fondo blanco + sombra baja antes que usar sombras pesadas. Esto da profundidad sin ruido.

```css
.surface-card {
  background: var(--ds-color-surface);
  border: 1px solid var(--ds-color-border-subtle);
  box-shadow: var(--ds-shadow-xs);
}
```

---

## 6. Border radius

| Token | Valor | Uso |
|---|---:|---|
| `radius.none` | `0px` | Tablas densas internas, barras de progreso internas. |
| `radius.xs` | `4px` | Indicadores pequeños. |
| `radius.sm` | `8px` | Badges, tooltips, celdas destacadas. |
| `radius.md` | `12px` | Inputs, botones, controles. |
| `radius.lg` | `16px` | Cards base según marca. |
| `radius.xl` | `20px` | Cards de métricas. |
| `radius.2xl` | `24px` | Hero panels, sheets, modals. |
| `radius.3xl` | `32px` | Contenedores protagonistas. |
| `radius.pill` | `9999px` | Chips, segmented controls, floating actions. |

### Regla concéntrica

Cuando un elemento vive dentro de otro, el radio interno debe sentirse relacionado con el externo:

```text
Panel externo: 24px
Padding: 8px
Media interna: 16px
Botón interno: 12px
```

---

## 7. Sombras

| Token | CSS | Uso |
|---|---|---|
| `shadow.none` | `none` | Superficies planas. |
| `shadow.xs` | `0 1px 2px rgba(20,28,24,0.06)` | Card base. |
| `shadow.sm` | `0 2px 6px rgba(20,28,24,0.08)` | Header sticky, hover leve. |
| `shadow.md` | `0 8px 24px rgba(20,28,24,0.10)` | Dropdown, popover. |
| `shadow.lg` | `0 16px 48px rgba(20,28,24,0.14)` | Sheet, overlay. |
| `shadow.xl` | `0 24px 72px rgba(20,28,24,0.18)` | Modal. |
| `shadow.focus` | `0 0 0 4px rgba(46,125,87,0.28)` | Focus visible. |
| `shadow.accent.corporativo` | `0 12px 32px rgba(26,86,51,0.18)` | CTA protagonista. |

### Regla de interacción

En hover, subir solo un nivel de sombra:

```text
xs → sm
sm → md
md → lg
```

No usar `lg/xl` en cards normales; encarece percepción visual y quita foco a lo importante.

---

## 8. Material “AIA Glass”

No es blur decorativo. Se usa para navegación flotante, sheets, popovers y acciones sobre fondos limpios.

```css
.aia-glass {
  background: var(--ds-color-surface-glass);
  backdrop-filter: blur(var(--ds-blur-glass));
  -webkit-backdrop-filter: blur(var(--ds-blur-glass));
  border: 1px solid var(--ds-color-border-subtle);
  box-shadow: var(--ds-shadow-md);
}
```

### Fallback

```css
@supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
  .aia-glass {
    background: var(--ds-color-surface);
  }
}
```

### No usar glass en

- Tablas densas.
- Formularios largos.
- Texto de lectura prolongada.
- Fondos con foto compleja.
- Estados de error o advertencia.

---

## 9. Tipografía

### Familias

| Token | Fuente | Uso |
|---|---|---|
| `font.display` | Montserrat | Títulos, cifras grandes, hero, llamados visuales. |
| `font.base` | Inter | Texto, tablas, formularios, informes, navegación. |

### Escala

| Token | Mobile |
|---|---:|
| `h1` | `clamp(1.75rem, 5vw + 1rem, 2.5rem)` |
| `h2` | `clamp(1.5rem, 4.5vw + 1rem, 2rem)` |
| `h3` | `clamp(1.25rem, 4vw + 1rem, 1.75rem)` |
| `h4` | `clamp(1.125rem, 3.5vw + 1rem, 1.5rem)` |
| `h5` | `1rem` |
| `body` | `0.9375rem` |
| `caption` | `0.8125rem` |

### Line-height

| Token | Valor | Uso |
|---|---:|---|
| `tight` | `1.2` | Títulos y cifras. |
| `default` | `1.4` | UI y lectura corta. |
| `spacious` | `1.6` | Texto largo. |

---

## 10. Espaciado

Base `4px`.

| Token | Valor |
|---|---:|
| `space.1` | `4px` |
| `space.2` | `8px` |
| `space.3` | `12px` |
| `space.4` | `16px` |
| `space.6` | `24px` |
| `space.8` | `32px` |
| `space.10` | `40px` |
| `space.12` | `48px` |
| `space.14` | `56px` |
| `space.16` | `64px` |

### Reglas

- Cards: `24px` padding en desktop, `16px` en mobile si el contenido es denso.
- Secciones marketing: `48–64px` vertical.
- Dashboards: `16–24px` entre bloques.
- Formularios: `12px` entre label/input/help text, `24px` entre grupos.

---

## 11. Breakpoints y grilla

| Token | Width | Columnas | Gutter | Uso |
|---|---:|---:|---:|---|
| `xs` | `0px` | 4 | 16 | Móvil base. |
| `sm` | `414px` | 4 | 16 | Móvil amplio. |
| `md` | `768px` | 8 | 24 | Tablet. |
| `lg` | `1024px` | 12 | 24 | Desktop. |
| `xl` | `1280px` | 12 | 24 | Desktop amplio. |

### Containers

| Token | Valor | Uso |
|---|---:|---|
| `container.content` | `1120px` | Lectura, landings. |
| `container.wide` | `1280px` | Páginas comerciales. |
| `container.dashboard` | `1440px` | Herramientas internas y analítica. |

---

## 12. Motion

| Token | Valor | Uso |
|---|---:|---|
| `duration.fast` | `120ms` | Press, microinteracción. |
| `duration.default` | `200ms` | Hover, focus, cambio de estado. |
| `duration.slow` | `400ms` | Modals, sheets, cambios grandes. |
| `ease.standard` | `cubic-bezier(0.4, 0.0, 0.2, 1)` | UI general. |
| `ease.deceleration` | `cubic-bezier(0.0, 0.0, 0.2, 1)` | Entrada de elementos. |
| `ease.acceleration` | `cubic-bezier(0.4, 0.0, 1, 1)` | Salida de elementos. |

### Regla

Transiciones permitidas:

```text
opacity
transform
background-color
border-color
box-shadow
```

Evitar animar layout (`width`, `height`, `top`, `left`) salvo que se mida rendimiento.

---

## 13. Iconografía

- Estilo lineal.
- Stroke `2px`.
- Esquinas redondeadas `2px`.
- Grid `24px`.
- Sin rellenos sólidos salvo estados de alerta o marca.
- `alt text` conciso si comunica información.
- `aria-hidden="true"` si es decorativo.

### Color por dominio

| Dominio | Color |
|---|---:|
| Corporativo | `#1a5633` |
| Construcción | `#b55211` |
| Inmobiliario | `#00a499` |
| Arquitectura | `#6752bf` |

---

# 14. Componentes

## 14.1 Botones

### Base

| Propiedad | Valor |
|---|---:|
| Height | `44px` |
| Min width | `44px` |
| Padding X | `16px` |
| Gap | `8px` |
| Radius | `12px` |
| Font | Inter `600` |
| Active | `translateY(1px)` |
| Focus | `shadow.focus` |

### Variantes

| Variante | Background | Color | Border | Uso |
|---|---:|---:|---:|---|
| `primary` | `#1a5633` | `#ffffff` | `#1a5633` | Acción principal. |
| `secondary` | `#ddefe6` | `#1a5633` | `border.default` | Acción secundaria. |
| `tonal-glass` | `surface.glass` | `#1a5633` | `border.subtle` | Floating actions, headers. |
| `ghost` | `transparent` | `#1a5633` | `transparent` | Acciones de bajo peso. |
| `link` | `transparent` | `#6752bf` | `transparent` | Navegación textual. |
| `danger` | `#c62828` | `#ffffff` | `#c62828` | Borrar, cancelar, bloqueo. |
| `warning` | `#f9a825` | `#141c18` | `#f9a825` | Acción con cautela. |
| `feature-gradient` | `gradient.primary` | `#ffffff` | blanco 24% | CTA protagonista. |

### CSS base

```css
.ds-button {
  min-width: 44px;
  height: 44px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--ds-space-2);
  padding-inline: var(--ds-space-4);
  border-radius: var(--ds-radius-md);
  border: 1px solid transparent;
  font-family: var(--ds-font-base);
  font-size: var(--ds-font-size-body);
  font-weight: 600;
  line-height: 1;
  cursor: pointer;
  transition:
    background-color var(--ds-duration-default) var(--ds-ease-standard),
    border-color var(--ds-duration-default) var(--ds-ease-standard),
    box-shadow var(--ds-duration-default) var(--ds-ease-standard),
    transform var(--ds-duration-fast) var(--ds-ease-deceleration);
}

.ds-button:focus-visible {
  outline: none;
  box-shadow: var(--ds-shadow-focus);
}

.ds-button:active {
  transform: translateY(1px);
}

.ds-button[disabled],
.ds-button[aria-disabled="true"] {
  opacity: 0.46;
  cursor: not-allowed;
  transform: none;
}

.ds-button--primary {
  background: var(--ds-color-corporativo-principal);
  color: var(--ds-color-text-inverse);
  border-color: var(--ds-color-corporativo-principal);
  box-shadow: var(--ds-shadow-sm);
}

.ds-button--primary:hover {
  background: var(--ds-color-corporativo-medio);
  border-color: var(--ds-color-corporativo-medio);
  box-shadow: var(--ds-shadow-md);
}

.ds-button--secondary {
  background: var(--ds-color-corporativo-muy-claro);
  color: var(--ds-color-corporativo-principal);
  border-color: var(--ds-color-border-default);
}

.ds-button--ghost {
  background: transparent;
  color: var(--ds-color-corporativo-principal);
}

.ds-button--ghost:hover {
  background: rgba(26, 86, 51, 0.08);
}

.ds-button--feature {
  background: var(--ds-gradient-primary);
  color: var(--ds-color-text-inverse);
  border-color: rgba(255, 255, 255, 0.24);
  box-shadow: var(--ds-shadow-accent-corporativo);
}
```

---

## 14.2 Cards

### Base

```css
.ds-card {
  background: var(--ds-color-surface);
  border: 1px solid var(--ds-color-border-subtle);
  border-radius: var(--ds-radius-lg);
  padding: var(--ds-space-6);
  box-shadow: var(--ds-shadow-xs);
}

.ds-card:hover {
  box-shadow: var(--ds-shadow-sm);
}
```

### Variantes

| Variante | Uso |
|---|---|
| `subtle` | Bloques secundarios o secciones suaves. |
| `elevated` | Cards interactivas, links, módulos destacados. |
| `glass` | Cards sobre hero o fondos con gradiente limpio. |
| `metric` | KPIs, cifras, resumen ejecutivo. |
| `domain-*` | Cards por unidad de negocio. |

---

## 14.3 Header / navegación

```css
.ds-header {
  min-height: 56px;
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(251, 253, 252, 0.74);
  backdrop-filter: blur(var(--ds-blur-glass));
  -webkit-backdrop-filter: blur(var(--ds-blur-glass));
  border-bottom: 1px solid var(--ds-color-border-subtle);
}

@media (min-width: 1024px) {
  .ds-header {
    min-height: 72px;
  }
}
```

Reglas:

- Logo máximo `160px` en mobile y `240px` en desktop.
- Header transparente solo en hero controlado.
- Cuando hay scroll, activar `shadow.sm`.
- En herramientas internas, priorizar navegación persistente sobre hero visual.

---

## 14.4 Inputs

```css
.ds-input {
  height: 44px;
  width: 100%;
  padding-inline: var(--ds-space-3);
  border-radius: var(--ds-radius-md);
  border: 1px solid var(--ds-color-border-default);
  background: var(--ds-color-surface);
  color: var(--ds-color-text-primary);
  font-family: var(--ds-font-base);
  font-size: var(--ds-font-size-body);
}

.ds-input::placeholder {
  color: var(--ds-color-text-tertiary);
}

.ds-input:focus-visible {
  outline: none;
  border-color: var(--ds-color-corporativo-medio);
  box-shadow: var(--ds-shadow-focus);
}

.ds-input[aria-invalid="true"] {
  border-color: var(--ds-color-alerta-critico);
  box-shadow: 0 0 0 4px rgba(198, 40, 40, 0.16);
}
```

---

## 14.5 Labels / badges

| Variante | Fondo | Texto |
|---|---:|---:|
| `corporativo` | `#ddefe6` | `#1a5633` |
| `construccion` | `#fbe4d3` | `#8b3c09` |
| `inmobiliario` | `#e3f9f7` | `#006d66` |
| `arquitectura` | `#edeafb` | `#4e3aa4` |
| `critical` | `#fdecec` | `#8f1d1d` |
| `warning` | `#fff8e1` | `#5d4200` |

```css
.ds-badge {
  display: inline-flex;
  align-items: center;
  height: 24px;
  padding-inline: var(--ds-space-3);
  border-radius: var(--ds-radius-pill);
  font-size: 0.75rem;
  font-weight: 500;
  line-height: 1;
}
```

---

## 14.6 Tables

```css
.ds-table-wrap {
  overflow: hidden;
  border-radius: var(--ds-radius-lg);
  border: 1px solid var(--ds-color-border-subtle);
  background: var(--ds-color-surface);
}

.ds-table {
  width: 100%;
  border-collapse: collapse;
  font-size: var(--ds-font-size-body);
}

.ds-table th {
  height: 44px;
  padding: var(--ds-space-3);
  background: var(--ds-color-corporativo-medio);
  color: var(--ds-color-text-inverse);
  font-weight: 600;
  text-align: left;
}

.ds-table td {
  padding: var(--ds-space-3);
  border-bottom: 1px solid var(--ds-color-border-subtle);
}

.ds-table tr:nth-child(even) td {
  background: var(--ds-color-bg-canvas);
}

.ds-table tr:hover td {
  background: var(--ds-color-corporativo-muy-claro);
}
```

---

## 14.7 Segmented control

Uso: filtros compactos, tabs de estado, switches visuales.

```css
.ds-segmented {
  display: inline-flex;
  gap: 4px;
  padding: 4px;
  border-radius: var(--ds-radius-pill);
  background: rgba(26, 86, 51, 0.08);
  border: 1px solid var(--ds-color-border-subtle);
}

.ds-segmented__item {
  height: 36px;
  padding-inline: 14px;
  border-radius: var(--ds-radius-pill);
  color: var(--ds-color-text-secondary);
}

.ds-segmented__item[aria-selected="true"] {
  background: var(--ds-color-surface);
  color: var(--ds-color-corporativo-principal);
  box-shadow: var(--ds-shadow-xs);
}
```

---

## 14.8 Alerts

| Variante | Fondo | Borde | Título | Texto |
|---|---:|---:|---:|---:|
| `critical` | `#fdecec` | `#ef9a9a` | `#8f1d1d` | `#5c1717` |
| `warning` | `#fff8e1` | `#ffe082` | `#5d4200` | `#3f2d00` |
| `info` | `#e3f9f7` | `#ace5e0` | `#006d66` | `#173f3d` |
| `success` | `#fbe4d3` | `#f4ad77` | `#8b3c09` | `#4e2408` |

> Nota de producto: el manual usa construcción como `success`. Para interfaces de operación, esto puede confundir porque el naranja se asocia a revisión/alerta. Si el producto usa estados de éxito frecuentes, conviene documentarlo con texto explícito: “Completado”, “Aprobado”, “Entregado”.

---

## 14.9 Modal / sheet

```css
.ds-overlay {
  background: rgba(11, 16, 13, 0.38);
  backdrop-filter: blur(8px);
}

.ds-modal {
  background: var(--ds-color-surface);
  border: 1px solid var(--ds-color-border-subtle);
  border-radius: var(--ds-radius-2xl);
  box-shadow: var(--ds-shadow-xl);
  padding: var(--ds-space-6);
}

@media (max-width: 767px) {
  .ds-modal {
    border-radius: 24px 24px 0 0;
  }
}
```

---

## 14.10 Toast

```css
.ds-toast {
  background: rgba(20, 28, 24, 0.92);
  color: var(--ds-color-text-inverse);
  border-radius: var(--ds-radius-lg);
  padding: 12px 16px;
  box-shadow: var(--ds-shadow-lg);
  backdrop-filter: blur(var(--ds-blur-glass));
}
```

---

## 15. Acentos por dominio

| Dominio | Color sólido | Fondo suave | Uso correcto | Evitar |
|---|---:|---:|---|---|
| Corporativo | `#1a5633` | `#ddefe6` | Navegación, CTA, selección, KPI general. | Usarlo para todo; pierde jerarquía. |
| Construcción | `#b55211` | `#fbe4d3` | Obra, avance, costos, hitos. | Mezclarlo con warning sin texto claro. |
| Inmobiliario | `#00a499` | `#e3f9f7` | Ventas, inmuebles, disponibilidad. | Texto blanco pequeño sobre turquesa. |
| Arquitectura | `#6752bf` | `#edeafb` | Diseño, BIM, documentación, links. | Usarlo como CTA principal global. |
| Alertas | `#c62828` | `#fdecec` | Error, riesgo, bloqueo. | Decoración. |
| Advertencias | `#f9a825` | `#fff8e1` | Revisión, pendiente, cautela. | Texto blanco. |

---

## 16. CSS Custom Properties

```css
:root {
  --ds-color-corporativo-principal: #1a5633;
  --ds-color-corporativo-medio: #2e7d57;
  --ds-color-corporativo-claro: #a7d5c1;
  --ds-color-corporativo-muy-claro: #ddefe6;
  --ds-color-construccion-principal: #b55211;
  --ds-color-construccion-medio: #e87722;
  --ds-color-construccion-claro: #f4ad77;
  --ds-color-construccion-muy-claro: #fbe4d3;
  --ds-color-inmobiliario-principal: #00a499;
  --ds-color-inmobiliario-medio: #5ec9bd;
  --ds-color-inmobiliario-claro: #ace5e0;
  --ds-color-inmobiliario-muy-claro: #e3f9f7;
  --ds-color-arquitectura-principal: #6752bf;
  --ds-color-arquitectura-medio: #9485d6;
  --ds-color-arquitectura-claro: #c7beef;
  --ds-color-arquitectura-muy-claro: #edeafb;
  --ds-color-alerta-critico: #c62828;
  --ds-color-alerta-alto: #e53935;
  --ds-color-alerta-medio: #ef9a9a;
  --ds-color-alerta-fondo: #fdecec;
  --ds-color-advertencia-critico: #f9a825;
  --ds-color-advertencia-alto: #ffca28;
  --ds-color-advertencia-medio: #ffe082;
  --ds-color-advertencia-fondo: #fff8e1;
  --ds-color-neutral-0: #ffffff;
  --ds-color-neutral-25: #fbfdfc;
  --ds-color-neutral-50: #f7faf8;
  --ds-color-neutral-100: #eef4f1;
  --ds-color-neutral-200: #dde7e1;
  --ds-color-neutral-300: #c7d4cc;
  --ds-color-neutral-400: #9daea4;
  --ds-color-neutral-500: #72857a;
  --ds-color-neutral-600: #52645a;
  --ds-color-neutral-700: #38483f;
  --ds-color-neutral-800: #233029;
  --ds-color-neutral-900: #141c18;
  --ds-color-neutral-950: #0b100d;
  --ds-color-bg-canvas: #f7faf8;
  --ds-color-bg-page: #fbfdfc;
  --ds-color-bg-subtle: #eef4f1;
  --ds-color-surface: #ffffff;
  --ds-color-surface-raised: rgba(255,255,255,0.82);
  --ds-color-surface-glass: rgba(255,255,255,0.68);
  --ds-color-surface-tint: rgba(221,239,230,0.72);
  --ds-color-text-primary: #141c18;
  --ds-color-text-secondary: #52645a;
  --ds-color-text-tertiary: #72857a;
  --ds-color-text-inverse: #ffffff;
  --ds-color-border-subtle: rgba(26,86,51,0.12);
  --ds-color-border-default: rgba(26,86,51,0.20);
  --ds-color-border-strong: rgba(26,86,51,0.36);
  --ds-color-focus-ring: rgba(46,125,87,0.28);
  --ds-color-link: #6752bf;
  --ds-color-link-hover: #4e3aa4;
  --ds-font-base: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  --ds-font-display: 'Montserrat', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  --ds-font-size-h1: clamp(1.75rem, 5vw + 1rem, 2.5rem);
  --ds-font-size-h2: clamp(1.5rem, 4.5vw + 1rem, 2rem);
  --ds-font-size-h3: clamp(1.25rem, 4vw + 1rem, 1.75rem);
  --ds-font-size-h4: clamp(1.125rem, 3.5vw + 1rem, 1.5rem);
  --ds-font-size-body: 0.9375rem;
  --ds-font-size-caption: 0.8125rem;
  --ds-line-height-tight: 1.2;
  --ds-line-height-default: 1.4;
  --ds-line-height-spacious: 1.6;
  --ds-space-1: 4px;
  --ds-space-2: 8px;
  --ds-space-3: 12px;
  --ds-space-4: 16px;
  --ds-space-6: 24px;
  --ds-space-8: 32px;
  --ds-space-10: 40px;
  --ds-space-12: 48px;
  --ds-space-14: 56px;
  --ds-space-16: 64px;
  --ds-radius-none: 0px;
  --ds-radius-xs: 4px;
  --ds-radius-sm: 8px;
  --ds-radius-md: 12px;
  --ds-radius-lg: 16px;
  --ds-radius-xl: 20px;
  --ds-radius-2xl: 24px;
  --ds-radius-3xl: 32px;
  --ds-radius-pill: 9999px;
  --ds-border-width-hairline: 1px;
  --ds-border-width-default: 1px;
  --ds-border-width-strong: 2px;
  --ds-shadow-xs: 0 1px 2px rgba(20,28,24,0.06);
  --ds-shadow-sm: 0 2px 6px rgba(20,28,24,0.08);
  --ds-shadow-md: 0 8px 24px rgba(20,28,24,0.10);
  --ds-shadow-lg: 0 16px 48px rgba(20,28,24,0.14);
  --ds-shadow-xl: 0 24px 72px rgba(20,28,24,0.18);
  --ds-shadow-focus: 0 0 0 4px rgba(46,125,87,0.28);
  --ds-shadow-accent-corporativo: 0 12px 32px rgba(26,86,51,0.18);
  --ds-gradient-primary: linear-gradient(135deg, #1a5633 0%, #2e7d57 52%, #00a499 100%);
  --ds-gradient-surface: linear-gradient(180deg, rgba(255,255,255,0.90) 0%, rgba(221,239,230,0.72) 100%);
  --ds-gradient-hero: radial-gradient(circle at 12% 8%, rgba(167,213,193,0.78) 0%, rgba(167,213,193,0) 38%), radial-gradient(circle at 82% 18%, rgba(0,164,153,0.30) 0%, rgba(0,164,153,0) 32%), linear-gradient(180deg, #fbfdfc 0%, #eef4f1 100%);
  --ds-gradient-construccion: linear-gradient(135deg, #b55211 0%, #e87722 100%);
  --ds-gradient-inmobiliario: linear-gradient(135deg, #00a499 0%, #5ec9bd 100%);
  --ds-gradient-arquitectura: linear-gradient(135deg, #6752bf 0%, #9485d6 100%);
  --ds-blur-glass: 18px;
  --ds-blur-overlay: 28px;
  --ds-duration-fast: 120ms;
  --ds-duration-default: 200ms;
  --ds-duration-slow: 400ms;
  --ds-ease-standard: cubic-bezier(0.4, 0.0, 0.2, 1);
  --ds-ease-deceleration: cubic-bezier(0.0, 0.0, 0.2, 1);
  --ds-ease-acceleration: cubic-bezier(0.4, 0.0, 1, 1);
}
```

---

## 17. QA antes de publicar

### Visual

- Cada vista tiene un solo CTA principal.
- Las cards no usan más de una familia de acento a la vez salvo dashboards comparativos.
- Header glass solo sobre fondos simples.
- Sombras no pasan de `md` salvo overlay/modal.
- Gradiente protagonista máximo una vez por vista.

### Accesibilidad

- Contraste WCAG AA validado.
- Focus visible con teclado.
- Inputs tienen error, ayuda y estado disabled.
- Buttons tienen hover, active, focus, disabled y loading.
- Icons con `aria-label` o `aria-hidden` según caso.
- Reduced motion probado.

### Producto

- Estados escritos con texto claro, no solo color.
- Acentos por dominio no reemplazan estado del sistema.
- Tablas densas priorizan lectura sobre decoración.
- En móvil no se ocultan acciones necesarias detrás de menús ambiguos.

---

## 18. Próxima acción recomendada

Crear una librería mínima en código con estos primeros componentes:

1. `Button`
2. `Card`
3. `Badge`
4. `Input`
5. `Alert`
6. `Header`
7. `Table`
8. `Modal`
9. `SegmentedControl`

Con eso cubres la mayoría de interfaces web internas y comerciales sin abrir demasiadas decisiones visuales por pantalla.
