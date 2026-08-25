---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-18
areas: [design-system, lps]
fuente: docs/design-system/auditoria/modulos/ds-f0-programacion-semanal.md
resumen: Módulo · Programación Semanal (y sus tres submódulos)
---

# Módulo · Programación Semanal (y sus tres submódulos)

**Estado declarado:** `pilot` (`programacion-semanal.json`) · **Pantallas:** `/programacion-semanal`
y `/programacion-semanal/{cnp,cnc,cic}` · **Escenario:** solo la primera

Los tres submódulos comparten la hoja del padre y se auditan aquí: `CNP.view.php`, `CNC.view.php` y
`CIC.view.php` cargan `programacion-semanal.css` y nada propio.

## Hojas de estilo

| Archivo | Líneas | `!important` | hex en código | `rgb()`/`hsl()` | `@layer` | tokens que consume |
|---|---:|---:|---:|---:|---:|---:|
| `public/css/change-monitor.css` | 179 | 22 | 0 | 0 | sí | 33 |
| `public/css/programacion-semanal.css` | 3489 | 413 | 0 | 0 | sí | 92 |

### A qué apunta cada uno de los 435 `!important`

| Familia del selector | Cuántos | % |
|---|---:|---:|
| propio-del-modulo | 167 | 38% |
| datatables | 159 | 37% |
| bootstrap/adminlte | 46 | 11% |
| handsontable | 38 | 9% |
| primitiva-aia | 18 | 4% |
| sweetalert2 | 7 | 2% |

## Vistas

| Archivo | Líneas | `style=` | `<style>` | `<main>` | `<h1>` | ids duplicados | primitivas `aia-*` |
|---|---:|---:|---:|:-:|:-:|---:|---:|
| `views/programacion-semanal/programacion_semanal.view.php` | 590 | 0 | 0 | ✓ | ✓ | 0 | 14 |

## Selectores de vendor que este módulo toca

- `bootstrap-adminlte` — 77 selectores
- `handsontable` — 26 selectores
- `datatables` — 17 selectores
- `sweetalert2` — 5 selectores

## Lectura

**Es el módulo con más deuda de cascada del repositorio, y con diferencia.** 435 `!important` sobre
3 668 líneas: uno cada ocho líneas y media. Es el 29% de los 1 520 de todo el repositorio, en un
módulo que es el 8% del CSS.

Clasificados por la familia del selector al que apuntan, **250 (58%) van contra CSS de proveedor** —
159 contra DataTables, 38 contra Handsontable, 46 contra Bootstrap, 7 contra SweetAlert2. Esa parte
es la salida prevista y el contrato la contempla. Los que no la tienen son otros:

- **167 (38%) apuntan a selectores propios del módulo** (`.ps-*`, `#ps*`, `body.ps-page`). Ahí no hay
  proveedor al que ganarle: es cascada rota adentro, y no la arregla ningún adaptador — la arregla
  ordenar el archivo. → `F0-020`
- **18 apuntan a primitivas `aia-*`**, que es el design system pisándose a sí mismo desde un módulo
  que declara consumirlo. → `F0-023`

Que la mayoría sea vendor **no absuelve al módulo**: significa que su deuda tiene dos causas
distintas y que contarlas juntas —como se venía haciendo— esconde cuál de las dos se puede cerrar
sin tocar a ningún proveedor.

Contra eso, el módulo **está limpio en color**: cero hex sueltos, cero `rgb()` crudo, y los tintes
salen de `color-mix()` sobre tokens del sistema, con la derivación de cada matiz explicada en la
cabecera del archivo y vigilada por `tests/browser/programacion-semanal-legend-honesty.mjs`. Es el
mejor ejemplo del repositorio de deuda **concentrada en un eje** en vez de repartida.

### Los ids duplicados de `/programacion-semanal/cic` son reales, y son menos que antes

`F-5` de `docs/DESIGN-AUDIT.md` reportó `cuadroModal×3` más otros seis ids repetidos. Hoy la cuenta
es **×2**, verificada:

```
$ grep -c 'id="cuadroModal"' views/programacion-semanal/CIC.view.php
2
```

Los siete siguen duplicados y **no son ramas excluyentes**: son dos modales que coexisten en el DOM,
`formulario_cic_si` (línea 155) y `formulario_cic_mdo` (línea 594), y ambos declaran `cuadroModal`,
`actualizacion`, `form_calidad`, `form_adm`, `form_GSA`, `form_sst` y `form_obs`. `CNC.view.php` y
`CNP.view.php` no tienen ninguno. → `F0-021`

**La semilla está desactualizada en la cifra, no en el hallazgo**, y eso se anota porque quien lea
`F-5` esperando tres encontrará dos y no sabrá si midió mal o si alguien arregló uno.

## Lo que no se pudo medir aquí

Las tres pantallas de los submódulos **no tienen escenario**: el manifiesto las declara en `routes[]`
y sus dos escenarios apuntan solo a `/programacion-semanal`. Sin golden no hay comparación posible ni
en estático ni en runtime. → `F0-022`
