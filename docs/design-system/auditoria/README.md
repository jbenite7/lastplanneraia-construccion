# DS-F0 · Auditoría total del design system

**Esto es un inventario, no un arreglo.** Nada de lo que hay aquí se ha reparado, y ninguna cifra
de este directorio autoriza a repararlo: reparar es **DS-F2**, y hacerlo antes de que **DS-F1** fije
el contrato sería hacerlo dos veces. Cuando un hallazgo tiene una salida trivial, la salida se
anota en el campo `salidaConocida` y **no se aplica**.

- **Spec:** `docs/superpowers/specs/2026-08-19-ds-f0-auditoria-total-design.md`
- **Plan:** `docs/superpowers/plans/2026-08-19-ds-f0-auditoria-total.md`
- **Frente:** `ds-f0-auditoria` · **Base medida:** `6abe2436`

## Qué hay aquí

| Archivo | Qué es | Tanda |
|---|---|---|
| `censo-modulos.json` / `censo-modulos.md` | El universo a recorrer: módulos, rutas y superficies, cruzados con el estado que cada uno declara en el design system | 1 |
| `escala-severidad.md` | Con qué regla se está clasificando aquí — **operativa, no del producto** | 1 |
| `hallazgos.schema.json` | La forma exacta de un hallazgo | 1 |
| `hallazgos.json` | El inventario acumulado, consultable por máquina | 2–4 |
| `modulos/<slug>.md` | Una ficha por módulo, legible | 2 |
| `vendors/<vendor>.md` | Handsontable y DataTables, aparte | 3 |
| `herramientas/` | Los scripts que produjeron las cifras, para que sean reproducibles y no haya que creerlas | — |

## Por qué el censo se verifica y no se hereda

El censo de módulos sale de `memoria/arquitectura/`, que lo genera `scripts/wiki-arquitectura.mjs`
desde `public/index.php`. Heredarlo sin comprobarlo sería auditar el generador, no el código, así
que el conteo se contrastó de forma independiente:

```
$ node scripts/wiki-arquitectura.mjs --cobertura
 201  TOTAL
Cobertura completa: ninguna ruta queda sin módulo.

$ grep -cE '\$router->(get|post|put|delete|any)\(' public/index.php
201
```

`admin/` no está en ese censo porque tiene **su propio front controller**
(`admin/public/index.php`, 56 rutas) y no aparece en `public/index.php`. Se cuenta aparte, con la
misma definición de superficie. Total: **257 rutas**, **52 superficies HTML**, **22 módulos**.

## Lo que este inventario NO puede medir todavía

Cualquier cifra que salga de **ejecutar los gates** espera al frente `runtime-budgets-al-ci`, que
corre en paralelo. Sin un carril de referencia sano, una medición automatizada no distingue si el
problema es del módulo o del medidor. Esos huecos van marcados con
`"bloqueadoPor": "runtime-budgets-al-ci"` y **no se rellenan con una cifra que no se pueda defender**.

Lo que sí se mide aquí es todo lo estático: lectura de archivos, conteo de ocurrencias con su línea,
y resolución del grafo de `@import` y de tokens. Eso no depende de ningún gate.

## Semilla absorbida

Estas tres fuentes **no son trabajo aparte**: se absorben como entradas de este inventario, y por eso
los planes de auditoría de UI del 3-ago no se archivaron.

| Fuente | Qué aporta |
|---|---|
| `docs/superpowers/decisiones-pendientes-2026-08-03.md` | 48 entradas A-*/B-*/C-* que esperan criterio del usuario |
| `docs/DESIGN-AUDIT.md` | F-4…F-9 del barrido final, todas `backlog ICE`, ninguna aplicada |
| Planes de auditoría de UI del 3-ago | El recorrido que quedó a medias |

Cada hallazgo que viene de la semilla lo declara en su campo `origen`, con el identificador original
(`C-20`, `F-7`, …) intacto, para que se pueda volver a la fuente sin traducir nada.
