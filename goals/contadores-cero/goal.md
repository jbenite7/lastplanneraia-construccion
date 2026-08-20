---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-18
areas: [proceso]
fuente: goals/contadores-cero/goal.md
resumen: Que las etiquetas contadoras de /programacion-intermedia dejen de ocupar sitio cuando marcan cero, con la vuelta atrás en un solo punto del código. El frente…
---

# Frente: contadores-cero

## Objetivo

Que las etiquetas contadoras de `/programacion-intermedia` dejen de ocupar sitio cuando marcan
cero, con la vuelta atrás en un solo punto del código. El frente existe para **quitar**: sale de
una revisión que dio 6/10 al ciclo anterior porque sus 28 arreglos fueron 28 adiciones y ninguna
resta.

## Condición de hecho

El usuario ve **menos elementos en pantalla**, demostrado con conteo antes/después, no con
capturas. Medido sobre `de02471a`, proyecto Da Porto, 1180×820 dark, sesión real por la puerta de
servicio: **antes 8 etiquetas (7 en cero) y 63 controles visibles**. Después, las 7 en cero no
ocupan sitio; con un filtro activo siguen visibles (esa guarda se verifica, no se supone); y
`OCULTAR_CONTADORES_EN_CERO = false` devuelve el estado anterior — mutación ejecutada, no
descrita. `npm run test:design-system:static` sin regresión contra la base **7/8** del mismo sha
(`node-tests` ya rojo por un test de mtime).

## Archivos declarados

- `public/js/modules/programacion_intermedia/hot.js`
- `public/css/programacion-intermedia.css`
- `docs/superpowers/specs/2026-08-11-contadores-cero-design.md`
- `docs/superpowers/plans/2026-08-11-contadores-cero.md`
- `goals/contadores-cero/goal.md`
- `decisiones/contadores-cero.md`
- `docs/decisiones-pendientes.md` (solo al integrar, justo antes de publicar)

## Contención

| Archivo | Commits hoy | Quién más lo declara |
|---|---|---|
| `public/js/modules/programacion_intermedia/hot.js` | 2 | nadie |
| `public/css/programacion-intermedia.css` | 1 | nadie |
| `docs/decisiones-pendientes.md` | **30** | a187ccda (frente `forma-quitar-pasos`) |

Los dos archivos de código están fríos y no los declara nadie más. `docs/decisiones-pendientes.md`
está muy caliente y lo declara otra sesión viva: se integra justo antes de publicar y se toca en
un commit corto y aparte, nunca al principio del frente.

## Cadena de herramientas

- `skill:coordinating-agent-sessions:coordinating-agent-sessions` — hay 4 sesiones vivas sobre el
  repo; fija el canal único, la cola de decisiones y el gate de cierre.
- `skill:superpowers:brainstorming` + `skill:superpowers:writing-plans` — el encargo exige spec y
  plan con gate antes de tocar código.
- `skill:superpowers:verification-before-completion` — la condición de hecho es un número
  medido, y todo verde va con sha.
- `skill:impeccable:impeccable` (`audit`) — el cambio es visual y cierra una pantalla.
- `mcp:Claude_Browser` — la medición antes/después es en vivo a 1180×820 dark.
- `agente:revisor` — repaso del diff antes de pedir el visto.

## Estado — cerrado, a la espera del visto

**Condición de hecho cumplida y medida**, en contenedor propio montando este worktree (el del repo
sirve el árbol principal y daría un «después» falso):

| | Antes | Después |
|---|---|---|
| Chips de la leyenda visibles | 8 | **1** |
| Controles visibles en pantalla | 64 | **57** |
| Alto del bloque de leyenda | 88px | **44px** |

Guarda del filtro verificada (con filtro activo vuelven los 8 y se puede saltar de uno a otro) y
mutación ejecutada (`OCULTAR_CONTADORES_EN_CERO = false` devuelve 64 y 8, con el atenuado de C-24
intacto). `static` **7/8** con `audit` verde en **170/175**, re-verificado **después** de integrar
`origin/main`; `node-tests` rojo preexistente por un test de mtime.

**Dos hallazgos que sobreviven al frente**, en la wiki:
[[valor-declarado-no-es-valor-computado]] y la ampliación de [[css-layer-cascade]].

### Decisiones

- **D-CERO-1 (goldens):** aprobada la regeneración por el usuario, y **no se usó**. El fixture
  siembra los nueve estados, así que en esa captura ningún contador está en cero y el cambio no la
  altera. El test falla por deriva **preexistente** —selector de semana y un botón—, confirmado
  ejecutando el mismo test contra el árbol principal **sin este código**: falla igual y en las
  mismas zonas. Regenerar habría congelado una deriva ajena con firma nueva.
- **D-CERO-4 (presupuesto):** resuelta como (b1). El módulo baja de 176 a **170** retirando seis
  `!important` que no competían con nadie, confirmados uno a uno.
- **D-CERO-5 (término duplicado):** sale a `vocabulario-estados-cascada`.
- **D-CERO-2 y D-CERO-3:** fuera de alcance, confirmadas.
- `buttons.css`: sus 16 `!important` son causa real pero no cuentan para este presupuesto. Van a
  frente propio con la medición ya hecha: **sobran 12, hacen falta 4**.

## Cierre

**Cerrado el 2026-08-19.** El trabajo estaba hecho y publicado; lo que faltaba era localizar la rama
—está en `main`— y demostrar la condición, que en este frente es **un número, no una captura**.

Medido hoy en `/programacion-intermedia`, Da Porto, 1180×820 dark, sesión por la puerta de servicio
(`evidence/sonda-conteo.mjs`):

| | Etiquetas visibles |
|---|---:|
| con el efecto apagado | **8** |
| con `OCULTAR_CONTADORES_EN_CERO = true` | **5** (0 de ellas en cero) |
| **ahorro real** | **3 etiquetas menos**, 881 px de ancho ocupado |

**La guarda queda verificada, no supuesta**, que es lo que el goal exigía: al activar un filtro
—comprobando `aria-pressed="true"` antes de contar, para que un clic que no activa nada no dé un
falso verde— **vuelven a verse las 8, con 7 en cero**. Es la regla de `setLegendCount`:
`esVacioReal = count === 0 && activeFilters.length === 0`, que distingue «vacío» de «cero porque
estoy mirando otra cosa».

**Un número no cuadra con la medición original y no se disfraza:** el goal decía «8 etiquetas, 7 en
cero» y hoy son 8 con 3 en cero. Los datos del proyecto cambiaron en tres semanas; el mecanismo es el
mismo y su efecto se midió entero. Los 7 en cero reaparecen, de hecho, en cuanto se activa el filtro.

**Alcance, para que no se lea de más:** este frente era solo `/programacion-intermedia`. En Programa
General y Semanal la clase `is-zero` **atenúa pero no oculta**, y eso es lo que allí se decidió.


## Archivos de este goal

- [[decisiones/contadores-cero]]
- [[docs/superpowers/specs/2026-08-11-contadores-cero-design]]
- [[docs/superpowers/plans/2026-08-11-contadores-cero]]
- [[memoria/goals/estado]]
