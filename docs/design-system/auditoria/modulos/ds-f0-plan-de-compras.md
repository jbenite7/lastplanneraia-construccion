---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-08-18
areas: [design-system]
fuente: docs/design-system/auditoria/modulos/ds-f0-plan-de-compras.md
resumen: Módulo · Plan de Compras v2
---

# Módulo · Plan de Compras v2

**Estado declarado:** `pilot` (`plan-compras-v2.json`) · **Pantalla:** `/plan-compras`
· **Escenario:** sí (estado vacío del sandbox 990100)

Es una SPA React + Vite + AG Grid: `pdc-app/` como fuente, `public/pdc-app/` como bundle publicado.
70 rutas, de las que **69 son API**: una sola pantalla y muchísimo endpoint.

## Hojas de estilo

| Archivo | Líneas | `!important` | hex en código | `rgb()`/`hsl()` | `@layer` | tokens que consume |
|---|---:|---:|---:|---:|---:|---:|
| `pdc-app/src/styles.css` | 1166 | 2 | 26 | 3 | **no** | 61 |

### A qué apunta cada uno de los 2 `!important`

| Familia del selector | Cuántos | % |
|---|---:|---:|
| propio-del-modulo | 2 | 100% |

## Vistas

| Archivo | Líneas | `style=` | `<style>` | `<main>` | `<h1>` | ids duplicados | primitivas `aia-*` |
|---|---:|---:|---:|:-:|:-:|---:|---:|
| `views/plan-compras/app.view.php` | 47 | 0 | 0 | ✓ | — | 0 | 4 |

## Lectura

**Los 26 hex y los 3 `rgb()` no son deuda, y hay que decirlo antes que nada** para que la tabla de
arriba no se lea al revés. Son los **segundos argumentos de `var()`**: el fallback de cada token
para cuando la SPA corre con `npm run dev` fuera de `lps-aia` y `aia-design-system.css` no está
cargado. La hoja lo declara en su cabecera y cada valor lleva su token al lado:

```css
--pdc-bg: var(--ds-active-bg-canvas, #0b100d);
--pdc-surface: var(--ds-active-surface, #1c241f);
```

Lo mismo vale para el **`@layer` ausente**: no es un olvido, es una decisión escrita en el archivo —
declarar los `--pdc-*` sin capa es lo que impide que redefinan los `--ds-active-*`, que el sistema
declara dentro de `@layer theme` y que una hoja sin capa ganaría. Congelar el tema del módulo en el
oscuro de hoy era el riesgo, y lo evitaron a propósito. → `F0-050`, severidad `sin-problema`

El único matiz que sí queda es que **un fallback es una copia**: si un token deriva, los 26 valores
de respaldo se quedan viejos en silencio y solo se ven en `npm run dev`. Es coste declarado, no
defecto. → anotado dentro de `F0-050`.

### Lo que sí es deuda: el módulo entero está fuera del gate

Dos hechos que se suman:

1. **`scripts/design-system-audit.mjs` no escanea `pdc-app/`.** Su `scanRoots` es
   `['views', 'public/js', 'public/css', 'src/View/Components', 'admin']`. Ni la fuente ni el bundle
   entran, así que **ninguno de los 3 896 hallazgos del gate proviene del Plan de Compras** — no
   porque no tenga, sino porque no se mira. → `F0-051`

2. **El gate propio del módulo existe, comprueba dos cosas y no lo ejecuta nadie.**
   `scripts/design-system-plan-compras-gate.mjs` lee el bundle
   `public/pdc-app/assets/pdc.css` y verifica exactamente: (a) que no redefina
   `--ds-active-bg-canvas` en un `:root` sin capa, y (b) que la cadena `var(--ds-` **aparezca al
   menos una vez**. Un bundle con cuarenta colores sueltos y un solo `var(--ds-x)` pasa. Y no está
   cableado en ningún script de `package.json` ni en `.github/workflows/`: las únicas referencias
   vivas son dos documentos históricos que cuentan que se corrió a mano al cerrar un goal.
   → `F0-052`

### La pantalla no tiene `<h1>`

`views/plan-compras/app.view.php` declara `<main>` y no declara `<h1>`. Es el shell de la SPA, así
que el encabezado real lo pinta React; queda anotado como medición pendiente, no como defecto
confirmado. → `F0-053`, `estimado: true`

## Lo que no se pudo medir aquí

Todo el interior de la SPA: los estados `cargando`, `error`, `selección` y `hover` de AG Grid los
produce React en tiempo de ejecución, y el escenario declarado es el **estado vacío** del sandbox
a propósito (es la única pantalla estable: la obra con datos v2 la escriben otras sesiones).
→ `bloqueadoPor: runtime-budgets-al-ci`
