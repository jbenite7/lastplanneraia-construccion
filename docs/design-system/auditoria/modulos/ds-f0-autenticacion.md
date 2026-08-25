---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-18
areas: [design-system]
fuente: docs/design-system/auditoria/modulos/ds-f0-autenticacion.md
resumen: Módulo · Autenticación
---

# Módulo · Autenticación

**Estado declarado:** `pilot` (`auth.json`) · **Pantallas:** `/login`, `/password/forgot`,
`/password/reset` · **Escenario:** solo `/login`

Su hoja, `login-brand-unified.css`, la comparten **la app y `admin/`**: la cargan
`views/auth/*.view.php` y también `admin/views/pages/{login,password-forgot,password-reset}.php`.

## Hojas de estilo

| Archivo | Líneas | `!important` | hex en código | `rgb()`/`hsl()` | `@layer` | tokens que consume |
|---|---:|---:|---:|---:|---:|---:|
| `public/css/login-brand-unified.css` | 440 | 30 | 0 | 0 | **no** | 34 |

### A qué apunta cada uno de los 30 `!important`

| Familia del selector | Cuántos | % |
|---|---:|---:|
| propio-del-modulo | 14 | 47% |
| sweetalert2 | 12 | 40% |
| primitiva-aia | 4 | 13% |

## Vistas

| Archivo | Líneas | `style=` | `<style>` | `<main>` | `<h1>` | ids duplicados | primitivas `aia-*` |
|---|---:|---:|---:|:-:|:-:|---:|---:|
| `views/auth/login.view.php` | 224 | 0 | 0 | ✓ | ✓ | 0 | 10 |
| `views/auth/password-forgot.view.php` | 70 | 0 | 0 | ✓ | ✓ | 0 | 6 |
| `views/auth/password-reset.view.php` | 91 | 0 | 0 | ✓ | ✓ | 0 | 6 |

## Selectores de vendor que este módulo toca

- `bootstrap-adminlte` — 21 selectores
- `sweetalert2` — 4 selectores

## Lectura

Las tres vistas están limpias —cero hex, cero estilo en línea, `<main>` y `<h1>` en las tres— y el
presupuesto `login` de `exceptions.json` está a cero en las seis reglas que declara. La deuda está
toda en la hoja, y es de dos clases.

### La hoja no entra en ninguna capa

`public/css/login-brand-unified.css` **no declara `@layer` en ninguna de sus 440 líneas**, y llega
por `<link>` directo desde la vista, no por el grafo de `@import` del entrypoint. Una hoja sin capa
gana a **todas** las capas del sistema, así que sus 440 líneas se sientan por encima de `theme`,
`components` y `utilities` enteras. Es la única hoja de módulo del repositorio en esa situación —
`pdc-app/src/styles.css` tampoco lleva capa, pero ahí es una decisión escrita y acotada a declarar
variables (ver `ds-f0-plan-de-compras.md`); aquí son reglas de aspecto. → `F0-070`

Y explica los 30 `!important`: catorce apuntan a selectores propios en una hoja que **ya gana por
estar fuera de capa**. → `F0-071`

### Consume un token que no existe

`login-brand-unified.css:148` usa `color: var(--ds-active-text-tertiary)` **sin fallback**, y ese
token no está definido en ninguna de las 66 hojas del repositorio ni lo escribe ningún JS. Su
hermano `--ds-active-text-secondary` sí existe. El resultado en el navegador es que la declaración
se descarta y el color lo hereda del padre — no un error visible, pero tampoco el color que el autor
escribió. Es el mismo defecto que `memoria/trampas/gate-estatico-no-ve-tokens-rotos` describe: un
gate que lee archivos da verde con un token que apunta a nada. → `F0-072`, y ver `transversal.md`
para las otras cuatro instancias.

## Lo que no se pudo medir aquí

`/password/forgot` y `/password/reset` están declaradas en `auth.json` y **ningún escenario las
cubre**: el único apunta a `/login`. Los estados de error de validación y el de «enlace enviado» no
son alcanzables por lectura estática. → `F0-073`
