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
| `inventario.md` | La cascada «Crítico → Sin problema», **generada** desde el JSON | 4 |
| `transversal.md` | El sistema, sus hojas compartidas y sus gates. **Es donde está lo crítico** | 2 |
| `modulos/ds-f0-<slug>.md` | Una ficha por módulo, legible | 2 |
| `vendors/ds-f0-<vendor>.md` | Handsontable y DataTables, aparte | 3 |
| `herramientas/` | Los scripts que produjeron las cifras, para que sean reproducibles y no haya que creerlas | — |

**Por qué las fichas llevan el prefijo `ds-f0-`.** El vault de Obsidian es la raíz del repo, así que
`modulos/programa-general.md` y `memoria/arquitectura/programa-general.md` compiten por el mismo
wikilink `[[programa-general]]`. Sin prefijo, `npm run test:wiki` pasó de cero hallazgos a **37**, y
los 37 eran de este directorio: cada nota de la wiki que enlazaba a un módulo quedaba ambigua. El
prefijo es la corrección, y se anota aquí porque el nombre de un archivo dejó de ser una decisión
libre en cuanto el repositorio entero se volvió un vault.

## El resultado, en una tabla

| Severidad | Hallazgos |
|---|---:|
| Crítico | 7 |
| Mayor | 31 |
| Menor | 13 |
| Cosmético | 6 |
| Sin problema | 11 |
| **Total** | **68** |

Diez esperan a `runtime-budgets-al-ci`; dos llevan severidad estimada y lo dicen; veintiuno vienen
de la semilla con su identificador original intacto. El detalle está en `inventario.md`.

**Los siete críticos, en una línea cada uno:**

1. `F0-030` — el baseline tolera 7 161 hallazgos y la deuda real es 3 896: ninguna regresión menor
   del 84% pone nada en rojo.
2. `F0-031` — una regla ausente del presupuesto de un módulo no se evalúa nunca; quince de los
   dieciocho presupuestos no nombran `unauthorized-important`.
3. `F0-051` — el gate estático no escanea `pdc-app/`: cero de los 3 896 hallazgos vienen del Plan
   de Compras.
4. `F0-052` — el gate propio del PDC comprueba dos condiciones y no lo ejecuta ningún script ni CI.
5. `F0-032` — cinco hex de la paleta clara, con `!important`, en un módulo `pilot` de un producto
   que solo tiene tema oscuro.
6. `F0-200` — 63 de los 85 selectores de Handsontable no los alcanza ninguna hoja nuestra.
7. `F0-201` — y el vendor trae su propia paleta clara, con la que pinta esos 63.

**Cuatro de los siete son de mecanismo, no de código**: el sistema no puede ver su propia deuda.
Esa es la parte de «ni bien controlado» de la frase que abrió este frente.

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
