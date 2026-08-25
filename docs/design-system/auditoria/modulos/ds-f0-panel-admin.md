---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-18
areas: [design-system, admin]
fuente: docs/design-system/auditoria/modulos/ds-f0-panel-admin.md
resumen: Módulo · Panel de administración
---

# Módulo · Panel de administración

**Estado declarado:** `inventory-only` (`admin`, **sin manifiesto y sin cobertura golden**)
· **Pantallas:** 14 · **Escenario:** ninguno

**Su deuda no se lee contra el mismo contrato que la de los módulos `pilot`, y eso no es una
concesión de esta auditoría: es una decisión del usuario, escrita en el propio inventario.** Por
decisión explícita suya, AdminLTE permanece como framework de `admin/`, las 14 vistas no se
reescriben sobre el shell canónico y el módulo queda «en dark y tokenizado pero NO migrado al
design system». La nota de `inventory.json` lo dice y añade que **no debe leerse como un olvido**.

## Hojas de estilo

| Archivo | Líneas | `!important` | hex en código | `rgb()`/`hsl()` | `@layer` | tokens que consume |
|---|---:|---:|---:|---:|---:|---:|
| `public/css/design-system/adapters/admin-lte.css` | 898 | 36 | 0 | 0 | sí | 54 |
| `admin/public/css/admin-auth-entrypoint.css` | 31 | 0 | 0 | 0 | sí | 0 |
| `admin/public/css/admin-custom.css` | 256 | 0 | 0 | 0 | sí | 24 |
| `admin/public/css/admin-entrypoint.css` | 53 | 0 | 0 | 0 | sí | 0 |
| `admin/public/css/utilities.css` | 29 | 0 | 0 | 0 | sí | 1 |

### A qué apunta cada uno de los 36 `!important`

| Familia del selector | Cuántos | % |
|---|---:|---:|
| propio-del-modulo | 36 | 100% |

## Vistas

| Archivo | Líneas | `style=` | `<style>` | `<main>` | `<h1>` | ids duplicados | primitivas `aia-*` |
|---|---:|---:|---:|:-:|:-:|---:|---:|
| `admin/views/layouts/main.php` | 209 | 0 | 0 | ✓ | ✓ | 0 | 2 |
| `admin/views/pages/dashboard.php` | 934 | 0 | 0 | — | — | 0 | 2 |
| `admin/views/pages/login.php` | 129 | 0 | 0 | — | ✓ | 0 | 4 |
| `admin/views/pages/matching/config.php` | 122 | 0 | 0 | — | — | 0 | 2 |
| `admin/views/pages/matching/family_catalog.php` | 443 | 0 | 0 | — | — | 0 | 2 |
| `admin/views/pages/password-forgot.php` | 63 | 0 | 0 | — | ✓ | 0 | 4 |
| `admin/views/pages/password-reset.php` | 79 | 0 | 0 | — | ✓ | 0 | 4 |
| `admin/views/pages/pdc/limpieza.php` | 234 | 0 | 0 | — | — | 0 | 2 |
| `admin/views/pages/projects/create.php` | 108 | 0 | 0 | — | — | 0 | 2 |
| `admin/views/pages/projects/edit.php` | 119 | 0 | 0 | — | — | 0 | 2 |
| `admin/views/pages/projects/index.php` | 247 | 0 | 0 | — | — | 0 | 3 |
| `admin/views/pages/projects/members.php` | 254 | 0 | 0 | — | — | 0 | 2 |
| `admin/views/pages/users/create.php` | 383 | 0 | 0 | — | — | 0 | 2 |
| `admin/views/pages/users/edit.php` | 351 | 0 | 0 | — | — | 0 | 2 |
| `admin/views/pages/users/index.php` | 262 | 0 | 0 | — | — | 0 | 3 |

## Selectores de vendor que este módulo toca

- `bootstrap-adminlte` — 15 selectores
- `select2` — 8 selectores
- `datatables` — 6 selectores

## Lectura

Medido contra lo que el módulo sí promete —estar en dark y tokenizado—, **cumple**: cero hex, cero
`rgb()` crudo, cero estilo en línea, cero bloques `<style>`, cero ids duplicados en 3 937 líneas de
vista, y presupuesto `admin` a cero salvo un `hardcoded-hex` tolerado. Las 54 variables que consume
`admin-lte.css` son tokens del sistema.

Los 36 `!important` del adaptador apuntan todos a selectores propios según la clasificación, y ahí
hay que ser preciso: **el adaptador de AdminLTE es el caso donde «propio» y «vendor» se confunden a
propósito**, porque su trabajo es remapear clases de Bootstrap (`.btn`, `.card`, `.content-wrapper`)
a tokens, y muchos de sus selectores son híbridos. El contrato exime a los adaptadores justamente
por esto. No se cuenta como deuda. → `F0-100`, `sin-problema`

### Lo que sí queda, y es estructura de documento

**Trece de las catorce vistas no declaran `<main>`, y once no declaran `<h1>`.** Solo
`admin/views/layouts/main.php` declara ambos, y envuelve al resto, así que el HTML rendido tiene un
`<main>` — pero el `<h1>` del layout es el de la marca, no el de la página, de modo que **cada
pantalla de administración se presenta sin encabezado de primer nivel propio**. Es la instancia de
`C-30` que sobrevive: aquel barrido midió tres `<main>` en toda la app y hoy son 21, pero `admin/`
no participó de esa corrección. → `F0-101`

### Dos primitivas por vista, y son las del layout

Cada vista de `admin/` declara exactamente 2 o 3 clases `aia-*`, contra las 10-16 de los módulos
`pilot`. No es un hallazgo contra el módulo —no está migrado y no debe estarlo— sino el **dato que
mide el tamaño de esa excepción**: 14 pantallas, el 34% de las del producto, fuera del catálogo de
componentes. → `F0-102`

### La deuda que comparte con la app

`admin/views/pages/{login,password-forgot,password-reset}.php` cargan
`public/css/login-brand-unified.css`, la hoja **sin `@layer`** de `F0-070`. Esa deuda es de
Autenticación y se paga en las dos aplicaciones a la vez.

Y `C-25`: la marca «AIA» de `admin/` se queda en 4,46:1 donde AA pide 4,5 para texto normal —16 px
en negrita no cuenta como texto grande. Es medición de navegador. → `F0-103`,
`bloqueadoPor: runtime-budgets-al-ci`

## Lo que no se pudo medir aquí

**Las 14 pantallas, enteras**, en cuanto a apariencia: no hay manifiesto, no hay escenario y no hay
golden. Es el hueco de cobertura más grande del repositorio y es deliberado.
