---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-18
areas: [design-system]
fuente: docs/design-system/auditoria/modulos/ds-f0-cronograma.md
resumen: Módulo · Actualizar cronograma
---

# Módulo · Actualizar cronograma

**Estado declarado:** `pilot` (`programa-general-actualizar.json`) · **Pantalla:**
`/programa-general-actualizar` · **Escenario:** sí

Es la vista que **importa cronogramas desde Excel**: una pantalla de escritura, no de consulta.

## Hojas de estilo

| Archivo | Líneas | `!important` | hex en código | `rgb()`/`hsl()` | `@layer` | tokens que consume |
|---|---:|---:|---:|---:|---:|---:|
| `public/css/programa-general-actualizar.css` | 748 | 15 | 0 | 0 | sí | 54 |
| `public/css/tom-select-premium-aia.css` | 230 | 99 | 0 | 0 | sí | 30 |

### A qué apunta cada uno de los 114 `!important`

| Familia del selector | Cuántos | % |
|---|---:|---:|
| handsontable | 113 | 99% |
| propio-del-modulo | 1 | 1% |

## Vistas

| Archivo | Líneas | `style=` | `<style>` | `<main>` | `<h1>` | ids duplicados | primitivas `aia-*` |
|---|---:|---:|---:|:-:|:-:|---:|---:|
| `views/programa-general-actualizar/programaGeneralActualizar.view.php` | 618 | 0 | 0 | ✓ | ✓ | 0 | 14 |

## Selectores de vendor que este módulo toca

- `tom-select` — 29 selectores
- `handsontable` — 27 selectores
- `bootstrap-adminlte` — 5 selectores

## Lectura

Cero hex, cero `rgb()` crudo, cero estilo en línea, `<main>` y `<h1>`, 14 primitivas `aia-*`, y
presupuesto **cero en todas las reglas declaradas**. La hoja propia gasta 15 `!important` en 748
líneas.

**Los 99 restantes son una sola hoja: `tom-select-premium-aia.css`.** El clasificador los atribuye a
Handsontable porque casi todos sus selectores empiezan por `.htTomSelectWrapper`, y conviene decirlo
sin adornos: **son estilos de Tom Select escritos dentro del contenedor de Handsontable**. Dos
proveedores anidados, una piel encima, 99 `!important` en 230 líneas — un `!important` cada 2,3
líneas, la densidad más alta del repositorio. El archivo se declara a sí mismo «piel de vendor» y
entra en `@layer vendor`; el presupuesto `tom-select-skin` de `exceptions.json` está a cero en las
seis reglas que declara, y ninguna de ellas es `unauthorized-important`. → `F0-040`

### El id duplicado de `F-8` sigue en pie

`docs/DESIGN-AUDIT.md` registra `modal-eliminar-semana-body-texto` duplicado. El escáner de esta
auditoría **no lo detecta** y hay que decir por qué en vez de darlo por cerrado: solo cuenta
`id="..."` con comillas dobles literales en el archivo, y este se emite desde PHP. Queda como
**medición pendiente**, no como hallazgo cerrado. → `F0-041`, `estimado: true`

## Lo que no se pudo medir aquí

El flujo de importación tiene estados `cargando`, `error` de validación y confirmación que solo
existen tras subir un archivo. Ninguno es alcanzable por lectura estática ni tiene golden.
→ `bloqueadoPor: runtime-budgets-al-ci`
