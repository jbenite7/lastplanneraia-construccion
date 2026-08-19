---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-03
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-03-lint-wiki-memoria-design.md
resumen: La wiki se fundó el 2026-08-02 con 42 páginas, 31 de ellas migradas desde la memoria privada del asistente. Un lint —la tercera operación del patrón LLM Wiki…
---

# Pasada de lint sobre la wiki `memoria/`

**Fecha:** 2026-08-03
**Estado:** aprobado
**Alcance:** contenido de `memoria/` y `.obsidian/`. No toca código, schema ni `docs/`.
**Precede a:** `2026-08-03-arquitectura-en-la-wiki-design.md`

## Problema

La wiki se fundó el 2026-08-02 con 42 páginas, 31 de ellas migradas desde la memoria privada del
asistente. Un lint —la tercera operación del patrón LLM Wiki, que hasta ahora no se había
ejercido— encontró tres afirmaciones que el repositorio ya desmiente, más dos problemas de forma
que hoy no molestan y a los 100 documentos sí.

Las tres afirmaciones incorrectas están **verificadas contra el código**, no supuestas.

## Correcciones

### `pdc-legend-item` apunta a una línea muerta

`trampas/browser-qa-pitfalls.md`, punto 8, dice que `styles.css:6476` fija un ancho de 205 px con
`!important` sobre PG/PI/PS.

Medido hoy: `public/css/styles.css` tiene **4380 líneas** y `205px` no aparece en el archivo. Las
reglas actuales de `.pdc-legend-item` (líneas 532–536) usan tokens del design system, sin
`!important` de ancho. Quien siga esa pista hoy busca una línea que no existe.

Se corrige con el estado real y se conserva la recomendación de fondo —desacoplar con una clase
propia del módulo— porque sigue siendo válida.

### La nota sobre Compras acusa al goal de algo que no es

`decisiones/compras-migrado-shell-sidebar.md` (2026-07-29) dice que el `goal.md` de
`sidebar-todos-modulos` «quedó obsoleto» por seguir excluyendo a Compras.

El hecho de fondo es cierto: `/pdc`, `/contratos` y `/listado-actividades` usan el shell sidebar, y
`foundation-shell.json` declara las 22 rutas. Lo que ya no es exacto es la acusación: ese goal
cerró el **2026-07-31**, dos días después de la nota, y su sección «Cierre formal» documenta la
omisión como **excepción deliberada**: «Compras… omitidas — PDC v2 tiene su propia navegación; las
rutas viejas ya están retiradas».

Se reescribe el párrafo para que describa la excepción documentada en lugar de señalar un error
inexistente.

### La cifra de la suite PHP quedó sin fechar

`trampas/suite-php-rojos-preexistentes.md` fija «4 de 108» en `main@1a75b19`. Hoy
`ls tests/test_*.php` da **126** archivos. La nota ya advierte de no citar la cifra sin re-medir,
así que no es un error, pero se marca el dato con su fecha y el universo actual para que la
advertencia no dependa de que alguien lea la prosa entera.

## Partir la nota de diez hechos

`trampas/browser-qa-pitfalls.md` empaqueta diez hechos distintos, contra la regla «una nota, un
hecho» que la propia wiki fija. Y dos de ellos ya tienen página propia, con una recomendación más
reciente que los deroga.

**A nota propia** (seis, cada una un hecho verificable):

| Nueva página | Hecho |
|---|---|
| `sesion-cae-en-el-panel.md` | La sesión muere a los 60–90 s en el panel de navegador, no en la aplicación |
| `semanal-auto-dispara-mutaciones.md` | Abrir `/programacion-semanal` lanza `save` y `auto-program` sin interacción |
| `bitacora-drawer-sin-profesional.md` | La bitácora del cajón no se puede sembrar con `test.A`: falla la clave foránea |
| `reset-legacy-pisa-adaptadores.md` | El `* {margin/padding:0}` de `styles.css` gana a `@layer components` |
| `gate-visual-tolerancia-enganosa.md` | Un rediseño real puede quedar bajo el 3 % de tolerancia y pasar en verde |
| `captura-playwright-miente.md` | Con `finally { logout }`, la captura de fallo muestra el login pase lo que pase |

**Fusionados** (dos): servir un worktree con `docker run` y la identidad de compose por worktree
ya están —y mejor— en `servir-worktree-stack-efimero` y `aislar-stack-docker-por-worktree`. La
versión de `browser-qa-pitfalls` está derogada por ellas.

**Anexado** (uno): importar Playwright desde un worktree con URL absoluta es el mismo hecho que
`path-with-space-esm-guard-noop`.

**Corregido y a nota propia** (uno): `pdc-legend-item-clase-compartida.md`, ya con lo medido hoy.

Todas heredan `origen: lps-aia-browser-qa-pitfalls`. La nota vieja se retira: queda repartida, no
perdida, y el log lo registra.

## Vocabulario de `areas`

El lint no encontró uso divergente, así que esto es prevención, no corrección. Se fija una lista
cerrada de doce, documentada en `index.md`:

`design-system` · `qa` · `docker` · `worktrees` · `pdc` · `lps` · `datos` · `rbac` · `deploy` ·
`bi` · `admin` · `proceso`

Cambios: `ui` y `shell` → `design-system`; `rutas` → `rbac`; `entorno` se reparte entre `docker` y
`deploy`; `sesiones`, `git`, `tooling`, `goals` y `arquitectura` → `proceso`.

`docker` y `worktrees` se mantienen separados a propósito: son las dos trampas que más veces han
mordido y conviene poder filtrarlas por separado.

## Catálogo generado con Bases

`index.md` tiene hoy 110 líneas, de las cuales 31 son filas de catálogo escritas a mano que hay
que tocar en cada ingesta.

Se crea `memoria/paginas.base` con tres vistas —decisiones, trampas, referencias— construidas
desde `tipo`, `resumen`, `areas` y `fecha`, y en `index.md` esas tres tablas se sustituyen por las
vistas embebidas.

**No** se toca la tabla de mapas, la explicación de las tres capas ni la de las tres operaciones:
eso es prosa con criterio, no catálogo.

Bases es una función reciente de Obsidian. Si no renderiza, se revierte solo `index.md`: ninguna de
las 42 páginas depende de la base, y el grafo tampoco.

## Verificación

- Los tres hechos corregidos se vuelven a medir contra el repositorio y se cita el comando.
- Cobertura del reparto: los diez puntos de `browser-qa-pitfalls` aparecen en alguna página; ningún
  hecho se pierde.
- `areas` solo contiene valores de la lista de doce.
- Enlaces: cero rotos y cero ambiguos, también sobre un clon fresco.
- Toda página está en `index.md` o en una vista de la base.
- Bases: comprobar que las tres vistas listan el mismo número de páginas que hay en disco.
- `git status` sin cambios ajenos; `docs/` sin modificar.

## Fuera de alcance

- La arquitectura por módulos: va en su propio spec, después de este.
- Reescribir notas migradas que el lint no señaló.
