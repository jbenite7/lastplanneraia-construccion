---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-08-18
areas: [design-system]
fuente: docs/design-system/auditoria/modulos/ds-f0-selector-escalamientos-indicadores.md
resumen: Tres módulos pilot sin deuda de cascada ni de color. Se registran como lo que son —la otra mitad del mapa— con la única salvedad que cada uno tiene.
---

# Módulos · Selector de proyectos, Escalamientos e Indicadores

Tres módulos `pilot` sin deuda de cascada ni de color. Se registran como lo que son —**la otra
mitad del mapa**— con la única salvedad que cada uno tiene.

- **Selector de proyectos** — `projects` (`project-selector.json`), `/proyectos`, con escenario.
- **Escalamientos** — `escalamientos` (`escalamientos.json`), `/dashboard/escalamientos`, con
  escenario. Su otra pantalla, `/dashboard`, no la declara ningún manifiesto (`F0-004`).
- **Indicadores** — `indicadores` (`indicadores.json`), `/indicadores`, con escenario.

## Selector de proyectos

## Hojas de estilo

| Archivo | Líneas | `!important` | hex en código | `rgb()`/`hsl()` | `@layer` | tokens que consume |
|---|---:|---:|---:|---:|---:|---:|
| `public/css/project-selector.css` | 342 | 0 | 0 | 0 | sí | 48 |

## Vistas

| Archivo | Líneas | `style=` | `<style>` | `<main>` | `<h1>` | ids duplicados | primitivas `aia-*` |
|---|---:|---:|---:|:-:|:-:|---:|---:|
| `views/core/project_selector.view.php` | 209 | 0 | 0 | ✓ | ✓ | 0 | 12 |

## Selectores de vendor que este módulo toca

- `bootstrap-adminlte` — 17 selectores

**Cero `!important` en 342 líneas y 48 tokens consumidos.** Es la hoja de módulo mejor tokenizada
del repositorio en proporción a su tamaño. → `F0-080`, `sin-problema`

## Escalamientos

## Hojas de estilo

| Archivo | Líneas | `!important` | hex en código | `rgb()`/`hsl()` | `@layer` | tokens que consume |
|---|---:|---:|---:|---:|---:|---:|
| `public/css/escalamientos.css` | 299 | 0 | 0 | 0 | sí | 53 |

## Vistas

| Archivo | Líneas | `style=` | `<style>` | `<main>` | `<h1>` | ids duplicados | primitivas `aia-*` |
|---|---:|---:|---:|:-:|:-:|---:|---:|
| `views/dashboard/escalamientos.php` | 170 | 1 | 0 | ✓ | ✓ | 0 | 2 |

## Selectores de vendor que este módulo toca

- `bootstrap-adminlte` — 4 selectores

Cero `!important`, cero color crudo, 53 tokens. Dos salvedades:

- **Solo dos primitivas `aia-*` en 170 líneas de vista**, contra 10-16 de los demás módulos `pilot`.
  El módulo está tokenizado pero apenas usa el catálogo de componentes: construye lo suyo con clases
  `.esc-*` propias. Es la diferencia entre consumir los tokens y consumir el sistema. → `F0-081`
- Un `style=` en `views/dashboard/escalamientos.php`. → anotado, `cosmetico`.

## Indicadores

## Hojas de estilo

| Archivo | Líneas | `!important` | hex en código | `rgb()`/`hsl()` | `@layer` | tokens que consume |
|---|---:|---:|---:|---:|---:|---:|
| `public/css/indicadores.css` | 60 | 0 | 0 | 0 | sí | 5 |

## Vistas

| Archivo | Líneas | `style=` | `<style>` | `<main>` | `<h1>` | ids duplicados | primitivas `aia-*` |
|---|---:|---:|---:|:-:|:-:|---:|---:|
| `views/indicadores/indicadores.view.php` | 300 | 0 | 0 | ✓ | ✓ | 0 | 4 |

**60 líneas de CSS, cero `!important`, cinco tokens, cuatro primitivas.** El módulo casi no tiene
CSS propio porque **casi no es nuestro**: `C-22` del registro del 3-ago midió que el contenido
central de `/indicadores` es un `<iframe>` servido desde `app.powerbi.com`. Es otro origen, así que
su interior no se puede tematizar desde aquí y el design system solo gobierna el marco.

Eso lo convierte en el único módulo cuyo «sin deuda» **no significa lo mismo** que en los demás: no
es que cumpla el contrato en toda su superficie, es que casi toda su superficie está fuera del
alcance del contrato. → `F0-082`

## Lo que no se pudo medir aquí

El interior del iframe de Power BI: contraste, foco y tema son del origen remoto.
→ hueco estructural, no medible desde este repositorio ni con gates propios.
