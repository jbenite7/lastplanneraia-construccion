---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-06
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-06-adopcion-logo-construccion-design.md
resumen: Adoptar el ícono del kit «Last Planner · línea Construcción» en las cuatro superficies de marca visibles de la app — favicon, sidebar del shell, login y panel…
---

# Adopción del logo «Last Planner · línea Construcción» — Diseño

**Fecha:** 2026-08-06 · **Estado:** aprobado en conversación (enfoque A) · **Alcance:** marca visible, sin cambios de paleta

## Objetivo

Adoptar el ícono del kit «Last Planner · línea Construcción» en las cuatro superficies de marca
visibles de la app — favicon, sidebar del shell, login y panel Admin — manteniendo el wordmark
como texto vivo y sin tocar tokens de color del design system (la paleta del kit ya coincide:
`#B55211` = `--aia-orange-primary` / `--ds-color-brand-construction`).

## Piezas de origen

Fabricadas y validadas el 2026-08-06 (audit 16/20) en
`~/Downloads/last-planner-aia-construction/last-planner-aia/exports/construction/`:

| Pieza | Uso |
|---|---|
| `last-planner-construction-icon.svg` | Ícono a color (contenedor naranja). Sidebar y Admin. |
| `last-planner-construction-glyph-mono.svg` | Glifo `currentColor` sin contenedor. Máscara del login. |
| `last-planner-construction-icon-small.svg` | Fuente de los favicons pequeños (no se sirve en runtime). |
| `favicon.ico` (16/32/48) | `/favicon.ico`. |
| `icon-192.png` | `apple-touch-icon` y `rel=icon` grande. |

Se copian a `public/img/brand/` (y `favicon.ico` a `public/favicon.ico`, donde `/plan-compras`
ya lo referencia y hoy da 404). Los lockups PNG horizontales/verticales del kit **no** se adoptan
en UI (quedan para documentos/impresión).

## Cambios por superficie

1. **Favicon (todas las vistas):** nuevo partial `views/partials/head_brand.php` con
   `<link rel="icon" href="/favicon.ico" sizes="any">`,
   `<link rel="icon" type="image/png" sizes="192x192" href="/img/brand/icon-192.png">` y
   `<link rel="apple-touch-icon" href="/img/brand/icon-192.png">`. Se incluye en el `<head>` de las
   vistas del shell principal, login y selector de proyectos. En Admin (mini-app aislada, rutas
   `/public/...`) se añaden los `<link>` equivalentes directamente en `admin/views/layouts/main.php`
   — no comparte partials con `views/`.
2. **Sidebar del shell:** `DesignSystemComponent.php:431` cambia
   `/public/img/aia-last-planner-mark.svg` → `/img/brand/icon.svg` (ícono a color; deja de teñirse
   con el tema porque lleva contenedor propio). El wordmark «Last Planner AIA» sigue como texto.
3. **Login:** el token `--ds-nav-brand-mark-image` (tokens.css:543) pasa a
   `url("/img/brand/glyph-mono.svg")`; la máscara de `login-brand-unified.css` sigue funcionando
   igual (alpha mask + `currentColor`). El SVG legado `aia-last-planner-mark.svg` queda sin
   consumidores y se retira.
4. **Admin:** `admin/views/layouts/main.php:70` cambia `florAIA.png` → `/public/img/brand/icon.svg`
   y retira las clases `img-circle`/`brand-image` que recortarían el contenedor redondeado
   (ajuste de tamaño con la clase existente `admin-brand-mark`).

## Decisiones

- **Ícono a color como `<img>`, glifo mono como máscara.** El SVG a color no puede usarse como
  máscara (su alpha es el cuadrado completo); cada punto de consumo usa la pieza que corresponde
  a su mecanismo.
- **Un solo token nuevo, ninguno roto:** `--ds-nav-brand-mark-image` conserva su semántica de
  «imagen enmascarable». El sidebar no consume ese token (usa `<img>`), así que no hay conflicto.
- **Nombres cortos en `public/img/brand/`:** `icon.svg`, `glyph-mono.svg`, `icon-192.png` — el
  prefijo largo del kit es de exportación, no de runtime.

## Errores y bordes

- Si un navegador no soporta `mask`, el login ya degrada hoy (mark invisible, wordmark visible);
  sin cambio de comportamiento.
- Cache: los `<link>` de favicon no llevan versión; los navegadores refrescan favicon por su cuenta.
  El `<img>` del sidebar hereda el cache-busting que tenga la vista (ninguno hoy; aceptado).

## Verificación

En dark desktop 1180×820 contra el contenedor (`http://localhost:8081`, sesión por dev door):

1. Login (`/login`): pill con glifo nuevo teñido + wordmark, favicon en pestaña.
2. Un módulo con sidebar (p. ej. `/programa-general`): ícono a color en el header del sidebar,
   colapsado y expandido.
3. Admin: mark nuevo sin recorte circular.
4. `curl -I http://localhost:8081/favicon.ico` → 200.
5. Consola del navegador sin errores nuevos.

Sin cambios de RBAC, datos ni rutas; no aplican pruebas de persistencia.
