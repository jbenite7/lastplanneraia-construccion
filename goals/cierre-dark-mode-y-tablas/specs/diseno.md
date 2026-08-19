---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-02
areas: [proceso]
fuente: goals/cierre-dark-mode-y-tablas/specs/diseno.md
resumen: Este documento es la fuente de la que cuelgan los specs de cada fase.
---

# Diseño validado — cierre de dark mode y ajuste de tablas

**Fecha:** 2026-07-30 · **Estado:** aprobado por el usuario, sección por sección.
Este documento es la fuente de la que cuelgan los specs de cada fase.

## Pregunta abierta antes de G0

`/contratos` y `/listado-actividades` salieron del goal anterior por decisión del usuario, pero
siguen servidas y montando Handsontable. Entran o no en el alcance según decida el usuario; el
coste marginal de incluirlas es bajo porque comparten adaptador. Ver `goal.md`.

## Problema

El goal `dark-mode-todos-los-modulos` cerró F0 a F4 y dejó abiertas F5, F6 T6.3/T6.4 y un puñado
de deudas contadas. En paralelo, la aplicación sirve **tres librerías de tabla** que resolvieron
su aspecto por separado: Handsontable tiene adaptador, DataTables no tiene ninguno, y AG Grid
inyecta 19 bloques `<style>` sin capa que derrotan al design system en su ruta.

Peor que la divergencia de forma es la de **significado**: tres módulos expresan la misma idea
—va bien, está bloqueado, es crítico— con tres paletas inventadas por separado.

## Enfoque elegido: contrato primero

Se audita todo antes de tocar nada; con ese inventario se escribe **una sola vez** qué es una
tabla AIA; recién entonces los tres adaptadores convergen.

Descartados:

- **Por superficie, de punta a punta.** La tabla canónica se descubriría por acumulación y las
  primeras superficies habría que rehacerlas.
- **Por librería, sin contrato previo.** Produce tres verdades distintas, que es el problema
  actual mejor pintado.

El precedente que decide: en F2 se tokenizó el skin de select2 y T6.3 lo va a rehacer entero.
Definir el destino antes de migrar evita pagar dos veces, y aquí el riesgo es triple.

## El contrato de tabla AIA

Se define **como tokens**, no como CSS de una librería.

### Capa 1 — forma

| Token | Gobierna | Por qué está |
|---|---|---|
| `--ds-table-row-h` | alto de fila | Divergen: el adaptador de Handsontable perdió su relleno vertical el 2026-07-29 y las grillas se compactaron; DataTables usa el de Bootstrap |
| `--ds-table-cell-pad-x` / `-y` | relleno de celda | Es lo que se perdió. En token, es medible |
| `--ds-table-header-bg` / `-fg` | cabecera | `tokens.css:337` documenta una cabecera naranja heredada del PDC viejo; G1 decide si sobrevive |
| `--ds-table-border` | rejilla | Handsontable pinta borde por celda; DataTables sólo horizontal |
| `--ds-table-zebra` | fila alterna | Hoy sólo DataTables |
| `--ds-table-row-hover` / `-selected` | estado de fila | Falta en las tres |
| `--ds-table-cell-focus` | foco de celda | Crítico: F1 midió aquí un control a 1,02:1. Va con par estado/contenido |
| `--ds-table-empty-*` | estado vacío | Ninguna de las tres lo resuelve |

### Capa 2 — reparto de responsabilidad

- **Tokens** — la verdad, definida una sola vez.
- **`.aia-grid-shell`** — el envoltorio: borde exterior, radio (`--ds-radius-table`, ya existe),
  scroll y altura. Común a las tres librerías. Ya existe como clase junto a `.aia-table-shell`
  (`core.css:128`, `aia-design-system.css:67`), así que se extiende, no se inventa.
- **Adaptador por librería** — sólo traduce el contrato al vocabulario del vendor
  (`.ht_master td`, `table.dataTable tbody td`, `.ag-cell`). Ninguna decisión propia.

### Capa 3 — lo que el contrato NO se lleva

Medirlo ya costó defectos; queda fuera a propósito:

1. **La altura de `#hot-container` la resuelve `syncContainerHeight()` en JS**, no CSS. El
   contrato define el alto de *fila*; el del *contenedor* sigue siendo del JS.
2. **El drawer LPS vive en `handsontable-module.css`**, no en `core.css`. G2 no puede mover esa
   hoja sin tumbar el Cajón Contextual, y ningún gate lo atrapa.
3. **El clon de números de fila** (13 px de desalineación abiertos en `/subcontratistas`) es
   geometría de Handsontable. Tarea propia de G2, no token.

### Capa 4 — paleta semántica de celda

Estado medido el 2026-07-30 sobre `public/css/styles.css`: tres vocabularios independientes,
asignados desde JavaScript vía `cellProperties.className`.

| Vocabulario | Módulo | Clases |
|---|---|---|
| `pg-state-*` | Programa General | 10: `a-tiempo-en-curso`, `atrasada`, `atrasado`, `debe-iniciar`, `en-curso`, `terminada`, `actividad-futura`, `restr-`, `sin-datos`, `r` |
| `pi-state-*` | Programación Intermedia | 8: `blocked-due`, `blocked-overdue`, `blocked-overdue-critical`, `execution-blocked`, `liberated-control`, `alert-`, `neutral` |
| `ps-alert-*` | Programación Semanal | 7: `critical`, `critical-route`, `high`, `medium`, `control`, `info`, `neutral` |
| `pdc-*` | PDC | `pdc-status-info`, `pdc-critical-delay` |

El contrato añade:

- **Escala semántica** `--ds-cell-state-{neutral, ok, atencion, riesgo, critico, bloqueado,
  sin-datos}`, cada peldaño con **par fondo + texto**, nunca uno solo. Voltear el fondo y dejar
  el texto es el defecto más repetido de F1.
- **Mapeo por dominio.** Las clases de módulo dejan de tener color propio y **apuntan** a la
  escala. El nombre sobrevive —es del glosario LPS—; el color deja de ser suyo.
- **Vocabulario compartido en JS**: un módulo único que traduce significado → clase, para que un
  renderer nuevo no pueda inventar una cuarta paleta.

Dos consecuencias aceptadas explícitamente por el usuario:

1. **Los colores de estado cambiarán de aspecto**, no sólo de origen. Si «atrasada» es hoy un
   rojo en Programa General y otro en Semanal, uno de los dos se mueve. Es visible para el
   usuario final.
2. **Unificar puede borrar un matiz real.** Por eso la escala tiene siete peldaños y no cuatro, y
   por eso **G0 mide cuántos significados se usan de verdad antes de que G1 la congele**. Si
   aparecen más de siete significados vivos, manda el censo y no la escala.

## Gates

**`scripts/design-system-table-contract.mjs`** (nuevo, G1):

- *Runtime*, con la tabla **cargada y con filas** —no en reposo, que es la trampa que más
  defectos destapó en F1—: alto de fila, relleno, cabecera y foco de celda resuelven a
  `--ds-table-*` y no a valores propios.
- *Runtime*: cada clase de estado resuelve a un par de la escala y **cumple AA sobre su propio
  fondo**. F1 encontró cuatro chips bajo AA que sólo aparecían con datos cargados.
- *Estático*: ningún adaptador declara color de tabla fuera de token.

**Gate de reglas sin capa** (G6): el gate actual compara el conjunto de hojas y el número de
bloques, no el de reglas — por eso `pdc.css` pasó de 140 a 268 reglas sin capa sin ponerse rojo.

## Ejecución

Cada fase: spec → plan (`writing-plans`) → gate del usuario → ejecución.

- **G0 y G1 inline.** Son medición y decisión; perder contexto ahí sale caro.
- **G2, G3 y G4 con subagentes**, uno por tanda: independientes y de contexto acotado.
- **TDD donde es verificable**: el gate del contrato se escribe **antes** que el adaptador que
  debe cumplirlo. El aspecto se valida en navegador.

## Verificación

```bash
node scripts/design-system-audit.mjs
node scripts/design-system-table-contract.mjs      # nuevo, G1
npm run test:design-system:static
npx playwright test tests/browser/<enfocada> --workers=1
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
```

Navegador a **1180×820 dark** contra el contenedor, **con la tabla cargada y con filas**, y
ejercitando los estados que en reposo no existen: celda en edición, fila seleccionada, menú
desplegado, modal abierto.

### Rojos preexistentes, declarados al abrir

No son de este goal y no se maquillan:

- `contracts.test.mjs` con «worktree and index must be clean» — falso rojo con trabajo sin
  commitear.
- Los 9 rojos de la suite de BI, entre ellos el de *landscape tablet* que `AGENTS.md` prohíbe
  tocar.
- Los 16 de la suite PHP en `pdc-a4-fechas`.
- La suite `desktop-layout` del laboratorio.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| G1 congela un contrato que no cabe en las tres librerías | Se materializa primero en el laboratorio, con las tres montadas al lado |
| Unificar estados borra un matiz que el usuario usa | G0 mide los significados vivos antes de que G1 fije la escala |
| Mover `handsontable-module.css` tumba el Cajón Contextual | Tarea explícita en G2, con prueba del drawer antes y después |
| Los 19 bloques de AG Grid varían de número | El gate asierta **capa**, no conteo |
| El goal crece sin fondo por los hallazgos de heurísticas | Sólo se arregla lo de tabla y lo de lectura en oscuro |

## Corrección de premisa heredada

`goals/dark-mode-todos-los-modulos/specs/F5-plan-compras.md` describe la SPA de Plan de Compras
como código de un repositorio externo `plan-de-compras`. **Es falso desde entonces:** la SPA vive
en `pdc-app/` de este repositorio, versionada, con `ag-grid-community@^36.0.2` en su
`package.json`, y publica en `public/pdc-app/`. G4 trabaja sobre esa realidad: no hay
coordinación entre repositorios, la fuente es auditable aquí, y `pdc-app/src` puede entrar en
`scanRoots` del audit en vez de depender sólo de un gate de contrato sobre el bundle.
