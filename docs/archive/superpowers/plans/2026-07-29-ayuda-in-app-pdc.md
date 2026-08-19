---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-07-29
areas: [pdc]
tags: [archivo]
fuente: docs/archive/superpowers/plans/2026-07-29-ayuda-in-app-pdc.md
resumen: Que cualquiera que entre a una pantalla del Plan de Compras pueda responderse solo «qué hace esto, qué tengo que hacer yo y qué pasa después», sin preguntarle…
---

# Ayuda dentro de la aplicación (PDC v2) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que cualquiera que entre a una pantalla del Plan de Compras pueda responderse solo «qué hace esto, qué tengo que hacer yo y qué pasa después», sin preguntarle a nadie.

**Architecture:** Un único componente `BotonAyuda` montado en las ocho páginas de la SPA, que recibe el contenido de su pantalla desde un módulo de datos versionado en el repositorio (`lib/ayuda.ts`). Un segundo componente `Recorrido` da la primera vuelta guiada, omitible, y recuerda en `localStorage` que ya se vio. El contenido es datos, no JSX: así se puede verificar con pruebas que ninguna pantalla se queda sin ayuda y que ninguna ayuda deja una de las tres preguntas en blanco.

**Tech Stack:** React 19 + TypeScript + Vite (`pdc-app/`), vitest para unidad, Playwright para navegador (`tests/browser/`), primitivas y tokens del design system (`aia-dialog`, `--ds-*`).

## Global Constraints

- **Alcance visual:** desktop ≥1180px, **dark mode únicamente**, viewport canónico de validación **1180×820**. Prohibido producir cambios, pruebas o evidencia para mobile, tablet o el tema `linen` (AGENTS.md).
- **Tokens y primitivas:** solo `--ds-*` y clases `aia-*` existentes. **Cero hex, cero rgba(), cero estilos inline, cero tipografías propias.** El audit cuenta como infracción un color incluso **dentro de un comentario CSS**: describe los colores con palabras.
- **Escala densa:** cuerpo `13px`, ayudas/notas `12px`, título de pantalla `18px`. Piso duro **11px**.
- **Vocabulario:** el de `GLOSARIO.md`. «Paquete de contratación», «insumo», «frente», «presupuesto», «maestro». Sin jerga técnica (`duracion_ref`, `upsert`, `project_id`, «endpoint», «commit») en texto visible.
- **Contenido en el repositorio.** Ni base de datos ni servicio externo: cambiar una pantalla y su ayuda es un solo commit.
- **Un solo componente de ayuda** reutilizado, que recibe el contenido. Nada de un panel por vista.
- **Persistencia:** `localStorage`, siguiendo la convención de `public/js/modules/aia_ui/sidebar_navigation.js` — clave con prefijo `aia-`, lectura envuelta en `try/catch`, valor validado contra una lista cerrada antes de usarse.
- **Granularidad decidida por el usuario (2026-07-29):** **un botón por página (8)**, y dentro de cada panel un apartado corto por cada pestaña que lo necesite. No 17 botones.
- **Fuera de alcance:** subpaquetes (su pantalla de partir y repartir no existe todavía; documentarla ahora es escribirla dos veces), centro de ayuda, buscador, vídeos, ayuda campo por campo.
- **No hacer commit a `main` ni push.** Commitear en la rama del worktree está autorizado; empujar, no.

## Inventario real de superficies (medido contra el código, no contra el spec)

El spec hablaba de nueve pantallas y mezclaba páginas con pestañas. Lo que hay:

| # | Página | Ruta | Pestañas internas |
|---|---|---|---|
| 1 | Cargar presupuesto | `/ensamble/importar` | — |
| 2 | Maestro | `/ensamble/maestro` | Pendientes por vincular · Catálogo global · Importar SINCO |
| 3 | Presupuesto | `/ensamble/presupuesto` | — |
| 4 | Comparar | `/ensamble/comparar` | — |
| 5 | Paquetes | `/ensamble/paquetes` | Insumos distintos · Asistente paso a paso · Paquetes con insumos |
| 6 | Plan | `/ensamble/plan` | Plan · Sin frente · Pendientes de calcular · Desfases |
| 7 | Pasos de contratación | `/ensamble/plan/pasos` | — (fuera de la barra; se llega desde Plan) |
| 8 | Seguimiento | `/seguimiento/avance` | Paquetes · Vencimientos · Flujo de caja |

Ocho páginas, trece pestañas. `PANTALLAS` en `lib/navegacion.ts` solo lista siete: **Pasos queda fuera de la barra a propósito** y por eso hay que enumerar las ocho a mano en `lib/ayuda.ts`, no derivarlas de `PANTALLAS`.

## Las tres superficies donde la ayuda importa de verdad

No son iguales de graves. En estas tres el usuario decide algo caro:

- **Desfases** (dentro de Plan) — «Aplicar» mueve fechas de toda la obra.
- **Impacto sobre el trabajo ya hecho** (dentro de Cargar presupuesto) — se decide sobre trabajo que se puede perder.
- **Flujo de caja** (dentro de Seguimiento) — la tabla se fotografía y viaja a comité de dirección, donde alguien la va a tratar como presupuesto de tesorería.

La pantalla de Flujo de caja **ya trae su advertencia de método**, servida por el servidor (`FlujoCajaService::NOTA_METODO`) para que pantalla y CSV no puedan decir cosas distintas. La ayuda **no la repite ni la resume**: la señala y dice qué hacer con ella. Repetirla a medias sería crear una segunda versión que envejece por separado.

### Regla general: lo que la pantalla dice en el momento, la ayuda no lo repite

Generalización de lo anterior, y aplica a todo el módulo. Un mensaje que aparece **cuando pasa la cosa** llega mejor que el mismo mensaje guardado detrás de un botón, y mantener las dos versiones sincronizadas es trabajo que nadie va a hacer. Casos concretos hoy:

| Mensaje que ya da la pantalla | Dónde vive | Qué hace la ayuda |
|---|---|---|
| La advertencia de método del flujo de caja | `FlujoCajaService::NOTA_METODO`, servida por el servidor | La señala y dice llevársela; no la reescribe |
| El impacto sobre el trabajo ya hecho al recargar | `ImportarPresupuesto.tsx` | Dice que hay que leerlo y por qué importa; no lo resume |
| Por qué el desplegable de frentes está vacío (sin cronograma en la semana activa · permiso denegado · falló la petición) | `motivoSinAnclas()` en `lib/planFechas.ts` | **Nada.** La pantalla ya lo dice en el momento y con la causa exacta |

### Deuda detectada al planificar: un número que se calcula y no se ve

`reenganchados` —cuántos vínculos que esperaban un insumo lo encontraron en esa carga— **se calcula en el servidor y viaja en la respuesta** (`MaestroSincoImportService.php:145`), pero **la SPA no lo muestra**: lo visible tras cargar SINCO sigue siendo «creados, actualizados, enriquecidos» (`MaestroInsumos.tsx:282`), y no hay ninguna referencia a `reenganch*` en `pdc-app/src/`.

**La ayuda no puede documentarlo**, porque describiría algo que el usuario no va a encontrar en la pantalla — exactamente el modo de fallo que este entregable existe para evitar. Mostrarlo es un cambio de esa pantalla, no de su ayuda, y no entra en este alcance. Queda anotado en la Task 8 para levantarlo como pendiente separado.

## File Structure

- `pdc-app/src/lib/ayuda.ts` — **nuevo.** Tipos `ContenidoAyuda` / `ApartadoAyuda`, la constante `AYUDAS` con los ocho contenidos, y `ayudaDe(id)`. Solo datos y una función de lectura; sin JSX.
- `pdc-app/src/lib/ayuda.test.ts` — **nuevo.** Prueba la integridad del contenido: las ocho pantallas, las tres preguntas sin huecos, apartados que citan pestañas reales, y ausencia de jerga.
- `pdc-app/src/lib/recorrido.ts` — **nuevo.** Los pasos del recorrido, y la persistencia (`leerVisto`, `marcarVisto`, `reiniciar`).
- `pdc-app/src/lib/recorrido.test.ts` — **nuevo.**
- `pdc-app/src/components/BotonAyuda.tsx` — **nuevo.** El único componente de ayuda. Botón + `<dialog class="aia-dialog">`.
- `pdc-app/src/components/Recorrido.tsx` — **nuevo.** La primera vuelta guiada.
- `pdc-app/src/styles.css` — **modificar.** Clases `pdc-ayuda-*` y `pdc-recorrido-*`, solo con tokens.
- Las 8 páginas de `pdc-app/src/pages/` — **modificar.** Una línea cada una: montar `<BotonAyuda pantalla="..." />` junto al `<h1>`.
- `pdc-app/src/App.tsx` — **modificar.** Montar `<Recorrido />` una vez, a nivel de módulo.
- `tests/browser/pdc-v2-ayuda.spec.mjs` — **nuevo.** El recorrido y el teclado, en navegador.
- `.gitignore` — **modificar.** Añadir `!tests/browser/pdc-v2-ayuda.spec.mjs`. **Sin esta línea el test nuevo no se commitea** (`tests/browser/*` está ignorado con allowlist).
- `DESIGN.md` y `docs/pdc-v2.md` — **modificar.** La regla de proceso, por escrito.

---

### Task 1: El contenido de la ayuda, como datos verificables

Primero el contenido, porque es la tarea de verdad; el componente viene después y ya tiene qué mostrar.

**Files:**
- Create: `pdc-app/src/lib/ayuda.ts`
- Test: `pdc-app/src/lib/ayuda.test.ts`

**Interfaces:**
- Consumes: nada.
- Produces: `type ApartadoAyuda = { etiqueta: string; texto: string }`; `type ContenidoAyuda = { titulo: string; queHace: string; queHagoYo: string[]; quePasaDespues: string; apartados: ApartadoAyuda[] }`; `const AYUDAS: Record<PantallaAyuda, ContenidoAyuda>`; `type PantallaAyuda = 'importar' | 'maestro' | 'presupuesto' | 'comparar' | 'paquetes' | 'plan' | 'pasos' | 'seguimiento'`; `function ayudaDe(id: PantallaAyuda): ContenidoAyuda`; `const PANTALLAS_AYUDA: PantallaAyuda[]`.

- [ ] **Step 1: Write the failing test**

```ts
// pdc-app/src/lib/ayuda.test.ts
import { describe, expect, it } from 'vitest'
import { AYUDAS, PANTALLAS_AYUDA, ayudaDe } from './ayuda'

// La jerga que NO puede aparecer en algo que lee un residente de obra. Son nombres de columnas,
// de tablas y de oficio de programador: si uno se escapa al texto visible, la ayuda deja de
// explicar y empieza a exigir que el lector sepa cómo está hecho el sistema por dentro.
const JERGA = [
  'duracion_ref', 'project_id', 'upsert', 'endpoint', 'commit', 'JOIN', 'SQL',
  'localStorage', 'API', 'backend', 'frontend', 'nullable', 'id',
]

describe('contenido de la ayuda', () => {
  it('cubre las ocho pantallas de la SPA, ni una menos', () => {
    expect(PANTALLAS_AYUDA).toEqual([
      'importar', 'maestro', 'presupuesto', 'comparar',
      'paquetes', 'plan', 'pasos', 'seguimiento',
    ])
    expect(Object.keys(AYUDAS).sort()).toEqual([...PANTALLAS_AYUDA].sort())
  })

  it.each(PANTALLAS_AYUDA)('«%s» responde las tres preguntas sin dejar huecos', (id) => {
    const a = ayudaDe(id)
    expect(a.titulo.trim().length).toBeGreaterThan(0)
    // Umbrales bajos a propósito: no premian la palabrería, solo atrapan el hueco y la
    // frase-de-relleno de tres palabras que no explica nada.
    expect(a.queHace.trim().length).toBeGreaterThan(40)
    expect(a.quePasaDespues.trim().length).toBeGreaterThan(40)
    expect(a.queHagoYo.length).toBeGreaterThan(0)
    a.queHagoYo.forEach((paso) => expect(paso.trim().length).toBeGreaterThan(10))
  })

  it.each(PANTALLAS_AYUDA)('«%s» está escrita sin jerga', (id) => {
    const a = ayudaDe(id)
    const todo = [a.titulo, a.queHace, a.quePasaDespues, ...a.queHagoYo,
      ...a.apartados.flatMap((s) => [s.etiqueta, s.texto])].join(' ')
    // Palabra completa: «id» no debe cazar «identifica», ni «API» cazar «rápido».
    JERGA.forEach((termino) => {
      const suelta = new RegExp(`(^|[^\\p{L}])${termino}([^\\p{L}]|$)`, 'iu')
      expect(suelta.test(todo), `«${termino}» aparece en la ayuda de ${id}`).toBe(false)
    })
  })

  it('las tres superficies costosas tienen apartado propio', () => {
    // Donde el usuario decide algo caro, el apartado no es opcional.
    expect(ayudaDe('plan').apartados.map((s) => s.etiqueta)).toContain('Desfases')
    expect(ayudaDe('seguimiento').apartados.map((s) => s.etiqueta)).toContain('Flujo de caja')
    expect(ayudaDe('importar').apartados.map((s) => s.etiqueta))
      .toContain('Impacto sobre el trabajo ya hecho')
  })

  it('no reescribe la advertencia del flujo de caja: la señala', () => {
    const flujo = ayudaDe('seguimiento').apartados.find((s) => s.etiqueta === 'Flujo de caja')
    expect(flujo).toBeDefined()
    // Si la ayuda repitiera el método, tendríamos dos versiones de la misma advertencia
    // envejeciendo por separado. La de la pantalla la manda el servidor y es la única.
    expect(flujo!.texto).toMatch(/aviso|advertencia/i)
    expect(flujo!.texto).not.toMatch(/prorrata|lineal/i)
  })

  it('no documenta lo que todavía no existe', () => {
    const todo = JSON.stringify(AYUDAS)
    expect(todo).not.toMatch(/subpaquete/i)
  })

  it('ayudaDe falla fuerte si le piden una pantalla que no existe', () => {
    // @ts-expect-error — comprobamos la guarda en tiempo de ejecución, no el tipo
    expect(() => ayudaDe('inventada')).toThrow(/inventada/)
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd pdc-app && npx vitest run src/lib/ayuda.test.ts`
Expected: FAIL — «Failed to resolve import "./ayuda"».

- [ ] **Step 3: Write the content**

Los textos son el entregable. Cada uno responde, en orden: qué hace esta pantalla · qué tengo que hacer yo aquí · qué pasa después.

```ts
// pdc-app/src/lib/ayuda.ts
/**
 * El contenido de la ayuda de cada pantalla, versionado al lado de la pantalla que describe.
 *
 * Vive aquí y no en la base de datos por una razón de mantenimiento: cambiar una pantalla y su
 * ayuda tiene que ser un solo cambio y una sola revisión. Una ayuda que miente es peor que
 * ninguna, y la única defensa contra eso es que las dos cosas viajen juntas.
 *
 * Es un objeto de datos y no JSX para poder verificarlo: `ayuda.test.ts` comprueba que ninguna
 * pantalla se queda sin ayuda y que ninguna deja una de las tres preguntas en blanco. Con JSX no
 * se puede afirmar eso.
 *
 * Las ocho pantallas se enumeran a mano —no se derivan de `PANTALLAS` de `navegacion.ts`— porque
 * «Pasos de contratación» está fuera de la barra a propósito y aun así necesita ayuda.
 */

export type PantallaAyuda =
  | 'importar' | 'maestro' | 'presupuesto' | 'comparar'
  | 'paquetes' | 'plan' | 'pasos' | 'seguimiento'

export const PANTALLAS_AYUDA: PantallaAyuda[] = [
  'importar', 'maestro', 'presupuesto', 'comparar',
  'paquetes', 'plan', 'pasos', 'seguimiento',
]

/** Un trozo de la pantalla que merece explicación propia dentro del mismo panel. */
export type ApartadoAyuda = { etiqueta: string; texto: string }

export type ContenidoAyuda = {
  titulo: string
  /** Qué hace esta pantalla. */
  queHace: string
  /** Qué tengo que hacer yo aquí. Uno por acción, en el orden en que se hacen. */
  queHagoYo: string[]
  /** Qué pasa después. */
  quePasaDespues: string
  /** Las pestañas o zonas que necesitan una línea propia. Vacío si la pantalla no las tiene. */
  apartados: ApartadoAyuda[]
}

export const AYUDAS: Record<PantallaAyuda, ContenidoAyuda> = {
  importar: {
    titulo: 'Cargar presupuesto',
    queHace:
      'Trae el presupuesto de la obra desde el Excel del software de presupuestos y lo guarda como '
      + 'una versión. Todo lo demás del módulo cuelga de aquí: sin presupuesto cargado, las otras '
      + 'pantallas no tienen con qué trabajar.',
    queHagoYo: [
      'Sube el Excel del presupuesto. Tiene que traer la hoja «Presupuesto» y pesar menos de 10 MB.',
      'Revisa la previsualización antes de confirmar: es la última oportunidad de ver qué va a entrar.',
      'Si ya habías cargado un presupuesto antes, lee el impacto sobre el trabajo ya hecho.',
      'Confirma. La versión queda guardada y pasa a ser la activa.',
    ],
    quePasaDespues:
      'La obra queda con una versión activa del presupuesto, y las pantallas de Maestro, '
      + 'Presupuesto y Paquetes empiezan a mostrar sus datos. Las versiones anteriores no se '
      + 'borran: quedan en el historial y puedes volver a activar una, o comparar dos en la '
      + 'pantalla de Comparar.',
    apartados: [
      {
        etiqueta: 'Impacto sobre el trabajo ya hecho',
        texto:
          'Si la obra ya tenía presupuesto, aquí se te dice qué le pasa a lo que ya habías '
          + 'armado —los insumos que vinculaste en el Maestro y los paquetes que armaste— antes de '
          + 'que confirmes. Léelo entero: es el único momento en que puedes echarte atrás sin '
          + 'perder nada. Si algo no cuadra, cancela y revisa el Excel en lugar de confirmar y '
          + 'arreglar después.',
      },
      {
        etiqueta: 'Historial de versiones',
        texto:
          'Cada carga queda guardada con su fecha. Puedes volver a activar una versión anterior, y '
          + 'el sistema te avisará también en ese caso de qué se ve afectado.',
      },
    ],
  },

  maestro: {
    titulo: 'Maestro de insumos',
    queHace:
      'Conecta cada insumo que viene en el Excel de la obra con el catálogo único de insumos de la '
      + 'empresa. El Excel de cada obra escribe los nombres a su manera; el catálogo es el que hace '
      + 'que «cemento gris» de una obra y de otra sean el mismo insumo.',
    queHagoYo: [
      'Abre «Pendientes por vincular»: son los insumos del presupuesto que todavía no están en el catálogo.',
      'Haz doble clic en uno para vincularlo a un insumo del catálogo que ya exista.',
      'Si de verdad es nuevo, créalo en el catálogo. Piensa antes: crear un duplicado es el error caro aquí.',
      'Repite hasta que la lista de pendientes quede vacía.',
    ],
    quePasaDespues:
      'Con los insumos vinculados, la pantalla de Paquetes puede agrupar bien y los informes de la '
      + 'empresa pueden sumar la misma cosa entre obras distintas. Mientras queden pendientes, esos '
      + 'insumos siguen apareciendo en el presupuesto de la obra pero no se pueden comparar con '
      + 'nada.',
    apartados: [
      {
        etiqueta: 'Pendientes por vincular',
        texto:
          'La cola de trabajo de esta pantalla. Un clic selecciona; un doble clic abre la ventana '
          + 'para vincular. El sistema te propone parecidos, pero la decisión es tuya.',
      },
      {
        etiqueta: 'Catálogo global',
        texto:
          'El catálogo completo de la empresa, de solo consulta. Búscalo aquí antes de crear un '
          + 'insumo nuevo: casi siempre ya está, escrito de otra forma.',
      },
      {
        etiqueta: 'Importar SINCO',
        texto:
          'Trae el catálogo desde el Excel exportado de SINCO, con la hoja «Maestro Insumos». Se '
          + 'hace de vez en cuando y afecta a toda la empresa, no solo a tu obra.',
      },
    ],
  },

  presupuesto: {
    titulo: 'Presupuesto',
    queHace:
      'Muestra el presupuesto activo de la obra tal como quedó al cargarlo, por capítulos y '
      + 'actividades, y señala lo que conviene mirar dos veces antes de seguir.',
    queHagoYo: [
      'Elige hasta qué nivel de detalle quieres ver, o haz clic en una fila para abrirla.',
      'Atiende los avisos: actividades sin cantidad, insumos en cero y partidas globales.',
      'Si un aviso indica un error del Excel, corrígelo allí y vuelve a cargar el presupuesto.',
    ],
    quePasaDespues:
      'Esta pantalla no cambia nada: es para mirar y decidir. Lo que corrijas aquí se corrige en el '
      + 'Excel y entra volviendo a cargar el presupuesto. Cuando el presupuesto te cuadre, el paso '
      + 'siguiente es agrupar sus insumos en la pantalla de Paquetes.',
    apartados: [
      {
        etiqueta: 'Avisos del presupuesto',
        texto:
          'Una actividad sin cantidad o un insumo en cero casi siempre es un descuido del Excel, y '
          + 'arrastra el error a todo lo que venga después. Una partida global grande no es un '
          + 'error, pero es dinero que no se puede repartir por actividad: conviene saber cuánto es.',
      },
      {
        etiqueta: 'Qué cuenta cada cifra',
        texto:
          'Cada total dice de qué está hecho. Si dos cifras de la pantalla no coinciden, es porque '
          + 'cuentan cosas distintas: lee lo que declara cada una antes de dar por buena una resta.',
      },
    ],
  },

  comparar: {
    titulo: 'Comparativo de versiones',
    queHace:
      'Pone dos versiones del presupuesto una al lado de la otra y muestra qué cambió: qué subió, '
      + 'qué bajó y en qué actividades e insumos.',
    queHagoYo: [
      'Elige las dos versiones que quieres comparar. Necesitas al menos dos cargadas.',
      'Mira primero por actividad para ubicar dónde está el cambio, y luego abre por insumo.',
    ],
    quePasaDespues:
      'Sirve para explicar y para sustentar. No cambia el presupuesto ni la versión activa: es solo '
      + 'consulta. Si lo que ves aquí te hace querer cambiar de versión, eso se hace desde Cargar '
      + 'presupuesto.',
    apartados: [
      {
        etiqueta: 'Cómo leer la diferencia',
        texto:
          'La diferencia que se muestra es lo que subió menos lo que bajó. Una diferencia pequeña '
          + 'puede esconder un sobrecosto grande compensado por un ahorro grande: abre el detalle '
          + 'antes de concluir que «casi no cambió».',
      },
    ],
  },

  paquetes: {
    titulo: 'Paquetes de contratación',
    queHace:
      'Agrupa los insumos del presupuesto en paquetes de contratación: los conjuntos de cosas que '
      + 'se van a contratar juntas, con un mismo tercero. Es la traducción del presupuesto, que está '
      + 'ordenado como se construye, al orden en que se compra.',
    queHagoYo: [
      'Empieza por «Insumos distintos» y asigna cada insumo a un paquete, o márcalo como omitido si no se contrata.',
      'Si no sabes por dónde arrancar, usa el asistente paso a paso: propone agrupaciones y tú decides.',
      'Apunta a que no quede valor sin destino. La meta es 100% asignado u omitido.',
    ],
    quePasaDespues:
      'Cada paquete que genere un proceso de contratación pasa a la pantalla de Plan, donde recibe '
      + 'fechas según el frente de obra al que sirva. Un insumo sin paquete no llega nunca al plan, '
      + 'y por lo tanto nadie va a recordar comprarlo a tiempo.',
    apartados: [
      {
        etiqueta: 'Insumos distintos',
        texto:
          'La lista de trabajo: cada insumo del presupuesto una sola vez, con su valor. De aquí se '
          + 'arrastra a un paquete o se omite.',
      },
      {
        etiqueta: 'Asistente paso a paso',
        texto:
          'Propone agrupaciones a partir de lo que ya se hizo en otras obras. Acierta con frecuencia '
          + 'y se equivoca a veces: revisa lo que propone en lugar de aceptarlo en bloque.',
      },
      {
        etiqueta: 'Paquetes con insumos',
        texto:
          'El resultado: qué paquetes existen y qué entró en cada uno. Úsalo para comprobar que un '
          + 'paquete no quedó con cosas que no se contratan juntas.',
      },
    ],
  },

  plan: {
    titulo: 'Plan de compras',
    queHace:
      'Dice qué hay que empezar a contratar y cuándo. Toma cada paquete, mira el frente de obra al '
      + 'que sirve, cuenta hacia atrás los días que se tarda en contratar y calcula la fecha en que '
      + 'hay que arrancar el proceso para llegar a tiempo.',
    queHagoYo: [
      'Mira la pestaña «Plan»: lo vencido va primero. Es tu lista de esta semana.',
      'Abre una fila para ver sus pasos y quién responde de cada uno.',
      'Si hay paquetes en «Sin frente», amárralos a un nodo del cronograma: sin frente no hay fecha.',
      'Si hay algo en «Pendientes de calcular» o en «Desfases», resuélvelo antes de fiarte de las fechas.',
    ],
    quePasaDespues:
      'Las fechas alimentan la pantalla de Seguimiento, que es donde se marca lo que ya ocurrió y se '
      + 've qué se vence. Si el cronograma de la obra se mueve, este plan queda desactualizado hasta '
      + 'que lo recalcules: por eso existe la pestaña de Desfases.',
    apartados: [
      {
        etiqueta: 'Sin frente',
        texto:
          'Paquetes que van a generar un proceso de contratación pero no están amarrados a ningún '
          + 'frente del cronograma. Sin ese amarre no hay de dónde sacar una fecha, así que estos '
          + 'paquetes no aparecen en el plan y nadie los va a ver venir. Es la lista que hay que '
          + 'dejar vacía.',
      },
      {
        etiqueta: 'Pendientes de calcular',
        texto:
          'Ya tienen frente, pero el plan todavía no se ha recalculado con ese amarre. Aparecen aquí '
          + 'y no en «Plan» hasta que recalcules.',
      },
      {
        etiqueta: 'Desfases',
        texto:
          'Cuando alguien mueve un frente en el cronograma, las fechas que este plan calculó dejan '
          + 'de corresponder. Aquí se ve cuáles. Puedes ver el cambio propuesto antes de aplicarlo: '
          + 'mirarlo no toca nada. «Aplicar» sí mueve las fechas del plan, así que revisa el detalle '
          + 'antes, porque afecta a lo que otras personas ya tenían previsto. Lo que ya ocurrió y '
          + 'quedó registrado no se pierde al recalcular.',
      },
    ],
  },

  pasos: {
    titulo: 'Pasos del proceso de contratación',
    queHace:
      'Define, para esta obra, qué pasos tiene un proceso de contratación y cuántos días toma cada '
      + 'uno. Es de donde el plan saca el tiempo que hay que contar hacia atrás desde la fecha en que '
      + 'se necesita el material.',
    queHagoYo: [
      'Revisa los pasos que trae por defecto y ajústalos a como se contrata en esta obra.',
      'Si otra obra ya tiene una configuración que te sirve, cópiala y ajusta desde ahí.',
      'Ajusta las duraciones que se te queden cortas o largas según lo que pasa de verdad.',
    ],
    quePasaDespues:
      'Cada cambio aquí cambia las fechas de arranque de todo el plan de la obra, así que hay que '
      + 'recalcular para verlo. Todo cambio queda registrado con su fecha y su autor, y se puede '
      + 'volver a una configuración anterior.',
    apartados: [
      {
        etiqueta: 'Copiar la configuración de otra obra',
        texto:
          'Trae los pasos y las duraciones de otra obra. Es una copia de una vez, no un vínculo: si '
          + 'la otra obra cambia después, esta no se entera. La pantalla te enseña qué va a traer '
          + 'antes de traerlo, y te avisa si la obra de origen está a medio configurar.',
      },
      {
        etiqueta: 'Duraciones del catálogo de la empresa',
        texto:
          'Los días que la empresa tiene medidos para cada tipo de paquete. Se pueden ajustar solo '
          + 'en las filas que esta obra usa, y el cambio es de la empresa, no solo tuyo. Un paquete '
          + 'del que la empresa todavía no tiene una duración medida no aparece en esta lista: '
          + 'recibe fechas por el promedio de los de su tipo, y no hay ningún número que editar '
          + 'hasta que alguien mida ese proceso.',
      },
      {
        etiqueta: 'Historial',
        texto:
          'Cada cambio de configuración queda anotado y no se borra. Volver a una versión anterior '
          + 'también deja rastro.',
      },
    ],
  },

  seguimiento: {
    titulo: 'Seguimiento del plan de compras',
    queHace:
      'Es la pantalla del día a día: registra lo que ya ocurrió de cada proceso de contratación, '
      + 'muestra qué se vence y estima cuánto dinero va a salir por mes.',
    queHagoYo: [
      'Entra por «Vencimientos» y mira qué se te vence: es la pregunta de la mañana.',
      'Marca en «Paquetes» los pasos que ya se cumplieron, con su fecha real.',
      'Si un paso se atrasó, márcalo igual con la fecha en que de verdad pasó, no con la planeada.',
    ],
    quePasaDespues:
      'Lo que registras aquí queda guardado y no lo borra un recálculo del plan: lo que ya ocurrió, '
      + 'ocurrió. La clasificación de vencimientos se mueve a medida que marcas, y la curva de '
      + 'desembolsos se recalcula sola cada vez que la pides.',
    apartados: [
      {
        etiqueta: 'Vencimientos',
        texto:
          'Qué se vence, por paso y por responsable. Lo vencido primero, luego lo que vence pronto. '
          + 'Coincide con lo que marca la pantalla de Plan, porque las dos cuentan igual.',
      },
      {
        etiqueta: 'Paquetes',
        texto:
          'El detalle paso a paso de cada paquete, y donde se marca lo cumplido con su fecha real.',
      },
      {
        etiqueta: 'Flujo de caja',
        texto:
          'Estima cuánto dinero sale por mes según el plan. Antes de sacar esta tabla de aquí —una '
          + 'foto, un archivo, una diapositiva— lee el aviso que la pantalla muestra encima de ella '
          + 'y llévalo contigo: dice con qué método está hecha y qué no tiene en cuenta. La misma '
          + 'frase viaja dentro del archivo que exportas. Sin ella, alguien la va a leer como una '
          + 'promesa de tesorería, y no lo es.',
      },
    ],
  },
}

export function ayudaDe(id: PantallaAyuda): ContenidoAyuda {
  const contenido = AYUDAS[id]
  // Falla fuerte y temprano: una pantalla sin ayuda es un incumplimiento de la regla de proceso,
  // no un caso a tolerar con un panel vacío que el usuario no sabría interpretar.
  if (!contenido) throw new Error(`No hay ayuda escrita para la pantalla «${id}»`)
  return contenido
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd pdc-app && npx vitest run src/lib/ayuda.test.ts`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add pdc-app/src/lib/ayuda.ts pdc-app/src/lib/ayuda.test.ts
git commit -m "feat(pdc): la ayuda de las ocho pantallas, escrita y verificable"
```

---

### Task 2: La persistencia y los pasos del recorrido

**Files:**
- Create: `pdc-app/src/lib/recorrido.ts`
- Test: `pdc-app/src/lib/recorrido.test.ts`

**Interfaces:**
- Consumes: `PantallaAyuda` de `lib/ayuda.ts`.
- Produces: `const CLAVE_RECORRIDO = 'aia-pdc-recorrido'`; `type PasoRecorrido = { pantalla: PantallaAyuda; ruta: string; titulo: string; texto: string }`; `const PASOS_RECORRIDO: PasoRecorrido[]`; `function leerVisto(almacen?: Storage | null): boolean`; `function marcarVisto(almacen?: Storage | null): void`; `function olvidarVisto(almacen?: Storage | null): void`.

- [ ] **Step 1: Write the failing test**

```ts
// pdc-app/src/lib/recorrido.test.ts
import { describe, expect, it } from 'vitest'
import { CLAVE_RECORRIDO, PASOS_RECORRIDO, leerVisto, marcarVisto, olvidarVisto } from './recorrido'

/** Un almacén de mentira, para no depender del navegador ni ensuciar entre pruebas. */
function almacenFalso(inicial: Record<string, string> = {}): Storage {
  const datos = new Map(Object.entries(inicial))
  return {
    get length() { return datos.size },
    clear: () => datos.clear(),
    getItem: (k: string) => datos.get(k) ?? null,
    key: (i: number) => [...datos.keys()][i] ?? null,
    removeItem: (k: string) => { datos.delete(k) },
    setItem: (k: string, v: string) => { datos.set(k, v) },
  } as Storage
}

describe('recorrido guiado', () => {
  it('recorre el flujo en el orden en que se trabaja', () => {
    expect(PASOS_RECORRIDO.map((p) => p.pantalla)).toEqual([
      'importar', 'maestro', 'presupuesto', 'paquetes', 'plan', 'seguimiento',
    ])
    PASOS_RECORRIDO.forEach((p) => {
      expect(p.ruta.startsWith('/')).toBe(true)
      expect(p.texto.trim().length).toBeGreaterThan(30)
    })
  })

  it('de entrada, no se ha visto', () => {
    expect(leerVisto(almacenFalso())).toBe(false)
  })

  it('marcarlo lo recuerda', () => {
    const almacen = almacenFalso()
    marcarVisto(almacen)
    expect(leerVisto(almacen)).toBe(true)
    expect(almacen.getItem(CLAVE_RECORRIDO)).toBe('visto')
  })

  it('relanzarlo desde la ayuda lo vuelve a poner en no visto', () => {
    const almacen = almacenFalso({ [CLAVE_RECORRIDO]: 'visto' })
    olvidarVisto(almacen)
    expect(leerVisto(almacen)).toBe(false)
  })

  it('un valor guardado que no reconoce se trata como no visto', () => {
    // Defensa contra basura de una versión anterior o de otra pestaña: solo 'visto' cuenta.
    expect(leerVisto(almacenFalso({ [CLAVE_RECORRIDO]: 'true' }))).toBe(false)
    expect(leerVisto(almacenFalso({ [CLAVE_RECORRIDO]: '' }))).toBe(false)
  })

  it('sin almacén disponible no revienta: asume no visto y sigue', () => {
    // En navegación privada o con las cookies bloqueadas, tocar localStorage lanza. La ayuda no
    // puede tumbar el módulo por eso; como mucho, el recorrido saldrá otra vez.
    const roto = {
      getItem: () => { throw new Error('bloqueado') },
      setItem: () => { throw new Error('bloqueado') },
      removeItem: () => { throw new Error('bloqueado') },
    } as unknown as Storage
    expect(leerVisto(roto)).toBe(false)
    expect(() => marcarVisto(roto)).not.toThrow()
    expect(() => olvidarVisto(roto)).not.toThrow()
    expect(leerVisto(null)).toBe(false)
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd pdc-app && npx vitest run src/lib/recorrido.test.ts`
Expected: FAIL — «Failed to resolve import "./recorrido"».

- [ ] **Step 3: Write the implementation**

```ts
// pdc-app/src/lib/recorrido.ts
/**
 * La primera vuelta guiada por el módulo, y la memoria de que ya se dio.
 *
 * Persistencia como la de la barra lateral (`public/js/modules/aia_ui/sidebar_navigation.js`):
 * clave con prefijo `aia-`, lectura envuelta y valor validado contra una lista cerrada. El motivo
 * de envolverla no es teórico: con las cookies bloqueadas, leer `localStorage` lanza, y la ayuda
 * no puede ser lo que tumba el módulo.
 */
import type { PantallaAyuda } from './ayuda'

export const CLAVE_RECORRIDO = 'aia-pdc-recorrido'

/** El único valor guardado que significa algo. Cualquier otra cosa se trata como no visto. */
const VALOR_VISTO = 'visto'

export type PasoRecorrido = {
  pantalla: PantallaAyuda
  ruta: string
  titulo: string
  texto: string
}

/**
 * Seis paradas, no ocho: el recorrido cuenta el camino, no el inventario. Comparar y Pasos se
 * dejan fuera a propósito —el primero es consulta y el segundo se configura una vez— y las dos
 * tienen su botón de ayuda para quien llegue a ellas.
 */
export const PASOS_RECORRIDO: PasoRecorrido[] = [
  {
    pantalla: 'importar',
    ruta: '/ensamble/importar',
    titulo: 'Todo empieza con el presupuesto',
    texto:
      'Aquí se sube el Excel del presupuesto de la obra. Es el primer paso y el que alimenta a '
      + 'todos los demás: sin presupuesto cargado, el resto del módulo no tiene con qué trabajar.',
  },
  {
    pantalla: 'maestro',
    ruta: '/ensamble/maestro',
    titulo: 'Después, poner los insumos en el idioma de la empresa',
    texto:
      'Cada obra escribe los nombres de los insumos a su manera. Aquí se conectan con el catálogo '
      + 'único de la empresa, para que el mismo material sea el mismo material en todas las obras.',
  },
  {
    pantalla: 'presupuesto',
    ruta: '/ensamble/presupuesto',
    titulo: 'Mirar el presupuesto antes de seguir',
    texto:
      'Esta pantalla no cambia nada: sirve para revisar lo que entró y para que el sistema te '
      + 'señale lo que conviene mirar dos veces, como una actividad sin cantidad.',
  },
  {
    pantalla: 'paquetes',
    ruta: '/ensamble/paquetes',
    titulo: 'Del orden en que se construye al orden en que se compra',
    texto:
      'El presupuesto está ordenado como se construye. Aquí se agrupan sus insumos en paquetes de '
      + 'contratación: lo que se va a contratar junto, con un mismo tercero.',
  },
  {
    pantalla: 'plan',
    ruta: '/ensamble/plan',
    titulo: 'El plan te dice cuándo arrancar cada contratación',
    texto:
      'Con los paquetes armados y amarrados a un frente del cronograma, el sistema cuenta hacia '
      + 'atrás los días que toma contratar y te dice cuándo hay que empezar para llegar a tiempo.',
  },
  {
    pantalla: 'seguimiento',
    ruta: '/seguimiento/avance',
    titulo: 'Y esta es la pantalla del día a día',
    texto:
      'Aquí se marca lo que ya pasó y se ve qué se vence. Es la que vas a abrir todas las mañanas. '
      + 'Puedes volver a ver este recorrido desde el botón de ayuda de cualquier pantalla.',
  },
]

/** El almacén del navegador, o nada si no hay. Se resuelve tarde para poder inyectarlo en pruebas. */
function almacenPorDefecto(): Storage | null {
  try {
    return typeof globalThis.localStorage === 'undefined' ? null : globalThis.localStorage
  } catch {
    return null
  }
}

export function leerVisto(almacen: Storage | null = almacenPorDefecto()): boolean {
  if (!almacen) return false
  try {
    return almacen.getItem(CLAVE_RECORRIDO) === VALOR_VISTO
  } catch {
    return false
  }
}

export function marcarVisto(almacen: Storage | null = almacenPorDefecto()): void {
  if (!almacen) return
  try {
    almacen.setItem(CLAVE_RECORRIDO, VALOR_VISTO)
  } catch {
    // Sin memoria, el recorrido volverá a salir. Molesto, no roto.
  }
}

export function olvidarVisto(almacen: Storage | null = almacenPorDefecto()): void {
  if (!almacen) return
  try {
    almacen.removeItem(CLAVE_RECORRIDO)
  } catch {
    // Igual que arriba: no poder olvidar no justifica tumbar la pantalla.
  }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd pdc-app && npx vitest run src/lib/recorrido.test.ts`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add pdc-app/src/lib/recorrido.ts pdc-app/src/lib/recorrido.test.ts
git commit -m "feat(pdc): los pasos del recorrido guiado y su memoria por usuario"
```

---

### Task 3: El componente de ayuda, uno solo

**Files:**
- Create: `pdc-app/src/components/BotonAyuda.tsx`
- Modify: `pdc-app/src/styles.css`

**Interfaces:**
- Consumes: `ayudaDe`, `PantallaAyuda` de `lib/ayuda.ts`; `olvidarVisto` de `lib/recorrido.ts`.
- Produces: `export default function BotonAyuda({ pantalla, onRelanzarRecorrido }: { pantalla: PantallaAyuda; onRelanzarRecorrido?: () => void })`.

- [ ] **Step 1: Write the component**

Se construye directo, sin test unitario propio: lo que hay que verificar de un diálogo —que abre, que cierra con `Escape`, que devuelve el foco— se comprueba de verdad en navegador (Task 6), no con un DOM simulado. El contenido, que es lo delicado, ya está probado en la Task 1.

```tsx
// pdc-app/src/components/BotonAyuda.tsx
import { useEffect, useRef, useState } from 'react'
import { ayudaDe } from '../lib/ayuda'
import type { PantallaAyuda } from '../lib/ayuda'

/**
 * El botón de ayuda de una pantalla. UNO para las ocho: recibe qué pantalla es y saca su contenido
 * de `lib/ayuda.ts`. Un panel por vista era la forma segura de que ocho paneles se fueran
 * separando entre sí.
 *
 * Usa `<dialog>` nativo con la primitiva `aia-dialog` del design system, y por eso sale gratis lo
 * que más se rompe a mano: el cierre con `Escape`, el foco atrapado dentro y el fondo inerte.
 */
export default function BotonAyuda({
  pantalla,
  onRelanzarRecorrido,
}: {
  pantalla: PantallaAyuda
  onRelanzarRecorrido?: () => void
}) {
  const contenido = ayudaDe(pantalla)
  const dialogo = useRef<HTMLDialogElement>(null)
  const disparador = useRef<HTMLButtonElement>(null)
  const [abierto, setAbierto] = useState(false)

  // `showModal()` no es declarativo, así que hay que llamarlo. Al cerrar devolvemos el foco al
  // botón: sin esto, quien navega con teclado vuelve al principio de la página y pierde el sitio.
  useEffect(() => {
    const el = dialogo.current
    if (!el) return
    if (abierto && !el.open) el.showModal()
    if (!abierto && el.open) el.close()
  }, [abierto])

  return (
    <>
      <button
        ref={disparador}
        type="button"
        className="pdc-ayuda-boton"
        aria-label={`Ayuda de ${contenido.titulo}`}
        data-testid={`pdc-ayuda-boton-${pantalla}`}
        onClick={() => setAbierto(true)}
      >
        {/* El símbolo que la empresa ya reconoce del visor de cronogramas. Decorativo: lo que
            anuncia el botón es su aria-label, y leer «signo de interrogación» no ayuda a nadie. */}
        <i className="fas fa-question-circle" aria-hidden="true" />
      </button>

      <dialog
        ref={dialogo}
        className="aia-dialog pdc-ayuda"
        aria-labelledby={`pdc-ayuda-titulo-${pantalla}`}
        data-testid={`pdc-ayuda-panel-${pantalla}`}
        // Cubre el Escape y el clic en el fondo, que no pasan por nuestro botón de cerrar.
        onClose={() => { setAbierto(false); disparador.current?.focus() }}
      >
        <div className="aia-modal-surface pdc-ayuda-cuerpo">
          <header className="pdc-ayuda-encabezado">
            <h2 id={`pdc-ayuda-titulo-${pantalla}`} className="pdc-ayuda-titulo">
              {contenido.titulo}
            </h2>
            <button
              type="button"
              className="aia-btn aia-btn--secondary"
              data-testid={`pdc-ayuda-cerrar-${pantalla}`}
              onClick={() => setAbierto(false)}
            >
              Cerrar
            </button>
          </header>

          {/* El orden de estos tres bloques es el contrato, no una preferencia de maquetación:
              qué hace esta pantalla · qué tengo que hacer yo · qué pasa después. */}
          <section className="pdc-ayuda-seccion">
            <h3 className="pdc-ayuda-pregunta">Qué hace esta pantalla</h3>
            <p className="pdc-ayuda-texto">{contenido.queHace}</p>
          </section>

          <section className="pdc-ayuda-seccion">
            <h3 className="pdc-ayuda-pregunta">Qué tengo que hacer yo aquí</h3>
            <ol className="pdc-ayuda-pasos">
              {contenido.queHagoYo.map((paso) => (
                <li key={paso} className="pdc-ayuda-texto">{paso}</li>
              ))}
            </ol>
          </section>

          <section className="pdc-ayuda-seccion">
            <h3 className="pdc-ayuda-pregunta">Qué pasa después</h3>
            <p className="pdc-ayuda-texto">{contenido.quePasaDespues}</p>
          </section>

          {contenido.apartados.length > 0 && (
            <section className="pdc-ayuda-seccion">
              <h3 className="pdc-ayuda-pregunta">Las partes de esta pantalla</h3>
              <dl className="pdc-ayuda-apartados">
                {contenido.apartados.map((a) => (
                  <div key={a.etiqueta} className="pdc-ayuda-apartado">
                    <dt className="pdc-ayuda-apartado-etiqueta">{a.etiqueta}</dt>
                    <dd className="pdc-ayuda-texto">{a.texto}</dd>
                  </div>
                ))}
              </dl>
            </section>
          )}

          {onRelanzarRecorrido && (
            <footer className="pdc-ayuda-pie">
              <button
                type="button"
                className="aia-btn aia-btn--secondary"
                data-testid={`pdc-ayuda-relanzar-${pantalla}`}
                onClick={() => { setAbierto(false); onRelanzarRecorrido() }}
              >
                Ver otra vez el recorrido del módulo
              </button>
            </footer>
          )}
        </div>
      </dialog>
    </>
  )
}
```

- [ ] **Step 2: Add the styles**

Añadir al final de `pdc-app/src/styles.css`. **Solo tokens.** Ni un hex, ni un `rgba()`, ni siquiera dentro de un comentario — el audit los cuenta como infracción igual.

```css
/* Ayuda por pantalla. Hereda de la primitiva aia-dialog: aquí solo van la anchura de lectura y
   el ritmo vertical. Los colores los pone el design system, no este archivo. */
.pdc-ayuda-boton {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: var(--ds-space-6);
  min-height: var(--ds-space-6);
  padding: var(--ds-space-1);
  border: 0;
  border-radius: var(--ds-radius-sm);
  background: transparent;
  color: var(--ds-active-text-secondary);
  font-size: var(--ds-font-size-md);
  cursor: pointer;
}

.pdc-ayuda-boton:hover {
  color: var(--ds-active-text-primary);
}

.pdc-ayuda-boton:focus-visible {
  outline: var(--ds-border-width-thick) solid var(--ds-active-focus-ring);
  outline-offset: var(--ds-space-1);
}

/* Más ancho que el diálogo por defecto: esto es texto para leer, no un formulario de confirmar.
   Y con tope de altura, porque el panel del Plan es largo. */
.pdc-ayuda .aia-modal-surface {
  width: min(44rem, calc(100% - var(--ds-space-8)));
  max-width: min(44rem, calc(100% - var(--ds-space-8)));
  max-height: 80dvh;
  overflow-y: auto;
}

.pdc-ayuda-encabezado {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: var(--ds-space-4);
  margin-bottom: var(--ds-space-4);
}

.pdc-ayuda-titulo {
  margin: 0;
  font-size: var(--ds-font-size-xl);
  font-weight: 600;
}

.pdc-ayuda-seccion + .pdc-ayuda-seccion {
  margin-top: var(--ds-space-5);
}

.pdc-ayuda-pregunta {
  margin: 0 0 var(--ds-space-2);
  color: var(--ds-active-text-secondary);
  font-size: var(--ds-font-size-xs);
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

/* Cuerpo a 13px con interlineado ancho: dentro del panel se lee, no se opera una tabla. */
.pdc-ayuda-texto {
  margin: 0;
  color: var(--ds-active-text-primary);
  font-size: var(--ds-font-size-md);
  line-height: 1.55;
}

.pdc-ayuda-pasos {
  margin: 0;
  padding-left: var(--ds-space-5);
}

.pdc-ayuda-pasos > li + li {
  margin-top: var(--ds-space-2);
}

.pdc-ayuda-apartados {
  margin: 0;
}

.pdc-ayuda-apartado + .pdc-ayuda-apartado {
  margin-top: var(--ds-space-3);
}

.pdc-ayuda-apartado-etiqueta {
  margin-bottom: var(--ds-space-1);
  font-size: var(--ds-font-size-md);
  font-weight: 600;
}

.pdc-ayuda-pie {
  margin-top: var(--ds-space-5);
  padding-top: var(--ds-space-4);
  border-top: var(--ds-border-width) solid var(--ds-active-border);
}
```

- [ ] **Step 3: Verify the tokens exist**

Los nombres de token inventados no fallan: se quedan sin valor y la regla no pinta nada. Comprobar cada uno antes de seguir:

```bash
for t in ds-space-1 ds-space-2 ds-space-3 ds-space-4 ds-space-5 ds-space-6 ds-space-8 \
         ds-radius-sm ds-border-width ds-border-width-thick \
         ds-active-text-primary ds-active-text-secondary ds-active-border ds-active-focus-ring \
         ds-font-size-xs ds-font-size-md ds-font-size-xl; do
  grep -rqs -- "--$t:" public/css/tokens.css public/css/design-system/ \
    && echo "OK   $t" || echo "FALTA $t"
done
```

Expected: `OK` en todos. Cualquier `FALTA` se sustituye por el token real que exista para ese uso — **no** se inventa el token ni se pone un valor a mano.

- [ ] **Step 4: Check it compiles**

Run: `cd pdc-app && npx tsc --noEmit`
Expected: sin errores.

- [ ] **Step 5: Commit**

```bash
git add pdc-app/src/components/BotonAyuda.tsx pdc-app/src/styles.css
git commit -m "feat(pdc): un solo componente de ayuda, sobre la primitiva de dialogo del DS"
```

---

### Task 4: El recorrido guiado

**Files:**
- Create: `pdc-app/src/components/Recorrido.tsx`
- Modify: `pdc-app/src/styles.css`

**Interfaces:**
- Consumes: `PASOS_RECORRIDO`, `leerVisto`, `marcarVisto` de `lib/recorrido.ts`; `useNavigate` de `react-router-dom`.
- Produces: `export default function Recorrido({ activo, onCerrar }: { activo: boolean; onCerrar: () => void })`.

- [ ] **Step 1: Write the component**

```tsx
// pdc-app/src/components/Recorrido.tsx
import { useEffect, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { PASOS_RECORRIDO, marcarVisto } from '../lib/recorrido'

/**
 * La primera vuelta por el módulo. Se monta una vez, a nivel de módulo, no por pantalla.
 *
 * Va llevando al usuario por las rutas del flujo mientras explica cada parada, así que lo que se
 * ve detrás del panel es la pantalla de verdad y no una ilustración.
 *
 * Omitible en el primer clic y no vuelve solo: quien ya sabe usar el módulo no debería tener que
 * esquivar esto cada mañana. Se relanza a mano desde cualquier botón de ayuda.
 */
export default function Recorrido({
  activo,
  onCerrar,
}: {
  activo: boolean
  onCerrar: () => void
}) {
  const [indice, setIndice] = useState(0)
  const dialogo = useRef<HTMLDialogElement>(null)
  const navegar = useNavigate()
  const paso = PASOS_RECORRIDO[indice]
  const ultimo = indice === PASOS_RECORRIDO.length - 1

  useEffect(() => {
    const el = dialogo.current
    if (!el) return
    if (activo && !el.open) { setIndice(0); el.showModal() }
    if (!activo && el.open) el.close()
  }, [activo])

  // Llevar la pantalla de fondo a la parada actual. Va en su propio efecto porque depende del
  // índice, no de si el recorrido está activo.
  useEffect(() => {
    if (activo && paso) navegar(paso.ruta)
  }, [activo, paso, navegar])

  // Terminar y omitir hacen lo mismo por dentro —no vuelve a salir— y es deliberado: castigar el
  // omitir haciéndolo reaparecer es lo que convierte una ayuda en una molestia.
  function cerrar() {
    marcarVisto()
    onCerrar()
  }

  if (!paso) return null

  return (
    <dialog
      ref={dialogo}
      className="aia-dialog pdc-recorrido"
      aria-labelledby="pdc-recorrido-titulo"
      data-testid="pdc-recorrido"
      onClose={cerrar}
    >
      <div className="aia-modal-surface pdc-recorrido-cuerpo">
        <p className="pdc-recorrido-progreso" data-testid="pdc-recorrido-progreso">
          Paso {indice + 1} de {PASOS_RECORRIDO.length}
        </p>
        <h2 id="pdc-recorrido-titulo" className="pdc-recorrido-titulo">{paso.titulo}</h2>
        <p className="pdc-recorrido-texto">{paso.texto}</p>

        <footer className="pdc-recorrido-pie">
          {/* Omitir va primero y siempre visible: es la salida, y esconderla en una esquina es la
              diferencia entre una ayuda y un peaje. */}
          <button
            type="button"
            className="aia-btn aia-btn--secondary"
            data-testid="pdc-recorrido-omitir"
            onClick={cerrar}
          >
            Omitir
          </button>
          <div className="pdc-recorrido-avance">
            {indice > 0 && (
              <button
                type="button"
                className="aia-btn aia-btn--secondary"
                data-testid="pdc-recorrido-atras"
                onClick={() => setIndice((i) => i - 1)}
              >
                Atrás
              </button>
            )}
            <button
              type="button"
              className="aia-btn"
              data-testid="pdc-recorrido-siguiente"
              onClick={() => (ultimo ? cerrar() : setIndice((i) => i + 1))}
            >
              {ultimo ? 'Entendido, empezar' : 'Siguiente'}
            </button>
          </div>
        </footer>
      </div>
    </dialog>
  )
}
```

- [ ] **Step 2: Add the styles**

Añadir al final de `pdc-app/src/styles.css`:

```css
/* Recorrido de la primera visita. Panel angosto y abajo, para no tapar la pantalla que explica. */
.pdc-recorrido .aia-modal-surface {
  width: min(30rem, calc(100% - var(--ds-space-8)));
  max-width: min(30rem, calc(100% - var(--ds-space-8)));
  margin-block: auto var(--ds-space-8);
}

.pdc-recorrido-progreso {
  margin: 0 0 var(--ds-space-2);
  color: var(--ds-active-text-secondary);
  font-size: var(--ds-font-size-xs);
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.pdc-recorrido-titulo {
  margin: 0 0 var(--ds-space-3);
  font-size: var(--ds-font-size-lg);
  font-weight: 600;
}

.pdc-recorrido-texto {
  margin: 0;
  color: var(--ds-active-text-primary);
  font-size: var(--ds-font-size-md);
  line-height: 1.55;
}

.pdc-recorrido-pie {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--ds-space-4);
  margin-top: var(--ds-space-5);
}

.pdc-recorrido-avance {
  display: flex;
  gap: var(--ds-space-2);
}
```

- [ ] **Step 3: Verify the tokens exist**

```bash
for t in ds-font-size-lg ds-space-8; do
  grep -rqs -- "--$t:" public/css/tokens.css public/css/design-system/ \
    && echo "OK   $t" || echo "FALTA $t"
done
```

Expected: `OK` en ambos.

- [ ] **Step 4: Check it compiles**

Run: `cd pdc-app && npx tsc --noEmit`
Expected: sin errores.

- [ ] **Step 5: Commit**

```bash
git add pdc-app/src/components/Recorrido.tsx pdc-app/src/styles.css
git commit -m "feat(pdc): recorrido de la primera visita, omitible en el primer clic"
```

---

### Task 5: Montarlo en las ocho pantallas y en el módulo

**Files:**
- Modify: `pdc-app/src/App.tsx`
- Modify: las 8 páginas de `pdc-app/src/pages/`

**Interfaces:**
- Consumes: `BotonAyuda`, `Recorrido`, `leerVisto`, `olvidarVisto`.
- Produces: contexto `AyudaContexto` con `{ relanzarRecorrido: () => void }`, para que las páginas no tengan que recibir el callback por props una a una.

- [ ] **Step 1: Wire the module level in App.tsx**

En `pdc-app/src/App.tsx`: montar el recorrido una vez y publicar el relanzador por contexto.

```tsx
import { createContext, useCallback, useState } from 'react'
import Recorrido from './components/Recorrido'
import { leerVisto, olvidarVisto } from './lib/recorrido'

/**
 * Cómo pide una pantalla que se vuelva a ver el recorrido. Por contexto y no por props porque el
 * botón de ayuda está en ocho páginas, y enhebrar el mismo callback ocho veces por la jerarquía
 * es la clase de cableado que alguien acaba olvidando en la novena.
 */
export const AyudaContexto = createContext<{ relanzarRecorrido: () => void }>({
  relanzarRecorrido: () => {},
})

export default function App() {
  // Se decide una vez al montar. Si se leyera en cada render, marcar «visto» a mitad del
  // recorrido lo cerraría de golpe en el paso siguiente.
  const [recorridoActivo, setRecorridoActivo] = useState(() => !leerVisto())

  const relanzarRecorrido = useCallback(() => {
    olvidarVisto()
    setRecorridoActivo(true)
  }, [])

  return (
    <HashRouter>
      <AyudaContexto.Provider value={{ relanzarRecorrido }}>
        <div className="pdc-shell">
          {/* … nav y Routes tal como están … */}
          <Recorrido activo={recorridoActivo} onCerrar={() => setRecorridoActivo(false)} />
        </div>
      </AyudaContexto.Provider>
    </HashRouter>
  )
}
```

`<Recorrido>` va **dentro** de `HashRouter` porque usa `useNavigate`, y **dentro** de `.pdc-shell` para heredar el tema del módulo.

- [ ] **Step 2: Add the button to each of the eight pages**

En cada página, junto al `<h1>`. Patrón, con `ImportarPresupuesto.tsx` como ejemplo:

```tsx
import { useContext } from 'react'
import { AyudaContexto } from '../App'
import BotonAyuda from '../components/BotonAyuda'

// …dentro del componente:
const { relanzarRecorrido } = useContext(AyudaContexto)

// …y en el marcado, envolviendo el h1 que ya existe:
<div className="pdc-titulo-fila">
  <h1>Importar presupuesto</h1>
  <BotonAyuda pantalla="importar" onRelanzarRecorrido={relanzarRecorrido} />
</div>
```

El `pantalla` de cada archivo, exactamente:

| Archivo | `pantalla` |
|---|---|
| `ImportarPresupuesto.tsx` | `"importar"` |
| `MaestroInsumos.tsx` | `"maestro"` |
| `VisorPresupuesto.tsx` | `"presupuesto"` |
| `ComparativoPresupuesto.tsx` | `"comparar"` |
| `PaquetesContratacion.tsx` | `"paquetes"` |
| `PlanFechas.tsx` | `"plan"` |
| `PasosContratacion.tsx` | `"pasos"` |
| `Seguimiento.tsx` | `"seguimiento"` |

Cuidado con dos: `MaestroInsumos.tsx` y `PaquetesContratacion.tsx` tienen **dos** `<h1>` cada uno (uno para el estado vacío, «El proyecto no tiene un presupuesto importado»). El botón va en **los dos**: el estado vacío es precisamente donde alguien no sabe qué hacer.

Y añadir a `styles.css`:

```css
.pdc-titulo-fila {
  display: flex;
  align-items: center;
  gap: var(--ds-space-2);
}
```

- [ ] **Step 3: Check it compiles and unit tests still pass**

Run: `cd pdc-app && npx tsc --noEmit && npx vitest run`
Expected: sin errores de tipos; todos los tests de vitest en verde (267+ antes de este trabajo, más los nuevos).

- [ ] **Step 4: Build the bundle**

El PHP sirve el bundle compilado desde `public/pdc-app/`, no la fuente. Sin esto, nada de lo anterior se ve en el navegador.

Run: `cd pdc-app && npm run build`
Expected: build sin errores; `public/pdc-app/assets/*` actualizado.

- [ ] **Step 5: Commit**

```bash
git add pdc-app/src/App.tsx pdc-app/src/pages pdc-app/src/styles.css public/pdc-app
git commit -m "feat(pdc): las ocho pantallas montan su ayuda, y el modulo su recorrido"
```

---

### Task 6: Verificación en navegador

**Files:**
- Create: `tests/browser/pdc-v2-ayuda.spec.mjs`
- Modify: `.gitignore`

**Interfaces:**
- Consumes: la app servida, y el proyecto de sandbox e2e `990100` (el mismo que usan los demás `pdc-v2-*.spec.mjs`; **no** Da Porto).

- [ ] **Step 1: Allowlist the new test file first**

`tests/browser/*` está ignorado con lista blanca. Sin esta línea el archivo se escribe, pasa, y **no se commitea** — y nadie se entera hasta que otra sesión no lo encuentra.

Añadir a `.gitignore`, junto a las demás líneas `!tests/browser/pdc-v2-*`:

```
!tests/browser/pdc-v2-ayuda.spec.mjs
```

Verificar que de verdad quedó visible:

```bash
git check-ignore -v tests/browser/pdc-v2-ayuda.spec.mjs; echo "rc=$?  (1 = NO ignorado = correcto)"
```

Expected: `rc=1`.

- [ ] **Step 2: Write the test**

Copiar la cabecera de `tests/browser/pdc-v2-vencimientos.spec.mjs` (base URL, sesión y proyecto sandbox) para no reinventar el arranque, y sobre eso:

```js
// tests/browser/pdc-v2-ayuda.spec.mjs
import { expect, test } from '@playwright/test'
// …reutilizar aquí el helper de sesión/proyecto de pdc-v2-vencimientos.spec.mjs…

const PANTALLAS = [
  ['importar', '/ensamble/importar'],
  ['maestro', '/ensamble/maestro'],
  ['presupuesto', '/ensamble/presupuesto'],
  ['comparar', '/ensamble/comparar'],
  ['paquetes', '/ensamble/paquetes'],
  ['plan', '/ensamble/plan'],
  ['pasos', '/ensamble/plan/pasos'],
  ['seguimiento', '/seguimiento/avance'],
]

test.use({ viewport: { width: 1180, height: 820 } })

test.describe('ayuda in-app del PDC v2', () => {
  test('el recorrido sale la primera vez, se omite, y no vuelve al recargar', async ({ page }) => {
    await abrirPdc(page, '/ensamble/importar')
    const recorrido = page.getByTestId('pdc-recorrido')
    await expect(recorrido).toBeVisible()
    await expect(page.getByTestId('pdc-recorrido-progreso')).toHaveText('Paso 1 de 6')

    await page.getByTestId('pdc-recorrido-omitir').click()
    await expect(recorrido).toBeHidden()

    await page.reload()
    await expect(page.getByTestId('pdc-recorrido')).toBeHidden()
  })

  test('las ocho pantallas tienen su ayuda y abre con las tres preguntas', async ({ page }) => {
    await abrirPdc(page, '/ensamble/importar')
    await page.getByTestId('pdc-recorrido-omitir').click()

    for (const [id, ruta] of PANTALLAS) {
      await irA(page, ruta)
      await page.getByTestId(`pdc-ayuda-boton-${id}`).click()
      const panel = page.getByTestId(`pdc-ayuda-panel-${id}`)
      await expect(panel).toBeVisible()
      await expect(panel.getByText('Qué hace esta pantalla')).toBeVisible()
      await expect(panel.getByText('Qué tengo que hacer yo aquí')).toBeVisible()
      await expect(panel.getByText('Qué pasa después')).toBeVisible()
      await page.getByTestId(`pdc-ayuda-cerrar-${id}`).click()
      await expect(panel).toBeHidden()
    }
  })

  test('se abre y se cierra con teclado, y el foco vuelve donde estaba', async ({ page }) => {
    await abrirPdc(page, '/ensamble/plan')
    await page.getByTestId('pdc-recorrido-omitir').click()

    const boton = page.getByTestId('pdc-ayuda-boton-plan')
    await boton.focus()
    await page.keyboard.press('Enter')
    await expect(page.getByTestId('pdc-ayuda-panel-plan')).toBeVisible()

    await page.keyboard.press('Escape')
    await expect(page.getByTestId('pdc-ayuda-panel-plan')).toBeHidden()
    // El punto de la condición de hecho nº 4: quien navega con teclado no debe perder el sitio.
    await expect(boton).toBeFocused()
  })

  test('el recorrido se puede relanzar desde la ayuda', async ({ page }) => {
    await abrirPdc(page, '/ensamble/plan')
    await page.getByTestId('pdc-recorrido-omitir').click()

    await page.getByTestId('pdc-ayuda-boton-plan').click()
    await page.getByTestId('pdc-ayuda-relanzar-plan').click()
    await expect(page.getByTestId('pdc-recorrido')).toBeVisible()
    await expect(page.getByTestId('pdc-recorrido-progreso')).toHaveText('Paso 1 de 6')
  })

  test('no hay scroll horizontal con el panel abierto', async ({ page }) => {
    await abrirPdc(page, '/ensamble/plan')
    await page.getByTestId('pdc-recorrido-omitir').click()
    await page.getByTestId('pdc-ayuda-boton-plan').click()
    const desborda = await page.evaluate(
      () => document.documentElement.scrollWidth > document.documentElement.clientWidth,
    )
    expect(desborda).toBe(false)
  })
})
```

- [ ] **Step 3: Run it**

Run: `npx playwright test tests/browser/pdc-v2-ayuda.spec.mjs --workers=1`
Expected: 5 passed.

- [ ] **Step 4: Check the console is clean**

Con el navegador abierto en `/plan-compras#/ensamble/plan`, abrir el panel y revisar consola y red. Expected: sin errores nuevos.

- [ ] **Step 5: Commit**

```bash
git add .gitignore tests/browser/pdc-v2-ayuda.spec.mjs
git commit -m "test(pdc): el recorrido, el teclado y las ocho ayudas, en navegador"
```

---

### Task 7: La regla de proceso, por escrito

Sin esto, todo lo anterior es deuda que empieza a envejecer mañana. Es el entregable que evita que la ayuda mienta dentro de un mes.

**Files:**
- Modify: `DESIGN.md` (sección «Do's and Don'ts»)
- Modify: `docs/pdc-v2.md`

- [ ] **Step 1: Add the rule to DESIGN.md**

En la lista de **Do:** de la sección 6:

```markdown
- **Una pantalla no se cierra sin su ayuda, y cambiarla cuenta como cerrarla otra vez.** Si tocas
  una pantalla del Plan de Compras y no tocas su entrada en `pdc-app/src/lib/ayuda.ts`, el cambio
  no está terminado. Una ayuda que miente es peor que ninguna, y la única defensa es que la
  pantalla y su texto viajen en el mismo commit. `pdc-app/src/lib/ayuda.test.ts` atrapa la pantalla
  sin ayuda; que el texto siga siendo **verdad** solo lo puede comprobar quien hace el cambio.
```

- [ ] **Step 2: Add the section to docs/pdc-v2.md**

```markdown
## Ayuda dentro del módulo

Cada una de las ocho pantallas tiene un botón de ayuda que responde tres cosas en este orden: qué
hace esta pantalla · qué tengo que hacer yo aquí · qué pasa después. El contenido vive en
`pdc-app/src/lib/ayuda.ts` y el componente único en `pdc-app/src/components/BotonAyuda.tsx`. La
primera visita lanza un recorrido de seis paradas (`lib/recorrido.ts`), omitible en el primer clic,
que no vuelve solo y se relanza desde cualquier botón de ayuda.

**La regla:** una pantalla no está terminada sin su ayuda, y eso incluye cambiarla. Si modificas
una pantalla, revisa su entrada en `ayuda.ts` en el mismo cambio.

**Granularidad:** un botón por página (ocho), no por pestaña (serían diecisiete). Las pestañas que
necesitan explicación la llevan como apartado dentro del panel de su página. Decisión del usuario,
2026-07-29.

**Dos textos que no se duplican:** la advertencia de método del flujo de caja la sirve el servidor
(`FlujoCajaService::NOTA_METODO`) y viaja también dentro del CSV exportado. La ayuda **la señala y
dice qué hacer con ella; no la reescribe**, para que no haya dos versiones envejeciendo por
separado. Igual con el aviso de impacto al recargar el presupuesto.

**Lo que falta:** subpaquetes no tiene ayuda porque cuando se escribió esto su pantalla de partir y
repartir no existía. Cuando exista, entra aquí — y con ella su apartado en la ayuda del Plan.
```

- [ ] **Step 3: Commit**

```bash
git add DESIGN.md docs/pdc-v2.md
git commit -m "docs(pdc): una pantalla no se cierra sin su ayuda, y cambiarla tambien cuenta"
```

---

### Task 8: El hecho de verdad — el revisor ajeno

Los puntos 1, 2, 4 y 5 de la condición de hecho los cierran las tareas anteriores con comandos. **El punto 3 no se puede cerrar con un comando**, y es el que importa: «un revisor que no conoce el módulo lee las ayudas y logra recorrer el flujo completo sin preguntar».

- [ ] **Step 1: Run the review with a fresh reader**

Despachar un subagente **sin contexto de este trabajo** (`buscador` o `revisor`, herramientas de solo lectura), con este encargo:

> No conoces este módulo. Solo puedes leer `pdc-app/src/lib/ayuda.ts` y `pdc-app/src/lib/recorrido.ts` — **no** leas el código de las pantallas, ni los specs, ni los goals. Con eso, responde: (1) ¿en qué orden hay que usar las ocho pantallas y por qué? (2) ¿qué es un paquete de contratación y de dónde sale su fecha? (3) ¿qué haces si el plan tiene paquetes en «Sin frente»? (4) ¿qué debes tener en cuenta antes de llevar la tabla de flujo de caja a un comité? (5) ¿qué tres cosas te quedaron sin entender? Sé concreto en la 5: es lo que se va a corregir.

- [ ] **Step 2: Fix what the reader could not answer**

Cada hueco de la pregunta 5 se corrige en `ayuda.ts` y se vuelve a correr `npx vitest run src/lib/ayuda.test.ts`. Si el revisor no acertó el orden del flujo o de dónde sale una fecha, el texto está mal, no el revisor.

- [ ] **Step 3: Visual QA at 1180×820, dark**

Abrir el navegador integrado en `/plan-compras#/ensamble/plan` (no la home), abrir el panel, y comprobar: contraste legible, sin desborde horizontal, foco visible, y que el panel no tape lo que explica. Guardar captura como evidencia.

- [ ] **Step 4: Full regression**

```bash
cd pdc-app && npx vitest run
npx playwright test tests/browser/pdc-v2-ayuda.spec.mjs tests/browser/pdc-v2-plan.spec.mjs tests/browser/pdc-v2-vencimientos.spec.mjs --workers=1
```

Expected: todo verde. `tests/browser/pdc-v2-sin-scroll-x.spec.mjs` está **rojo de antes** (comprobado sobre `1a75b19`): si sigue rojo, no es de este trabajo — confirmarlo revirtiendo, no asumirlo.

- [ ] **Step 5: Raise the deferred findings instead of burying them**

Dos cosas que este trabajo descubrió y que **no** se arreglan aquí. Levantarlas con `spawn_task`, con prompt autónomo:

1. **`reenganchados` se calcula y no se ve.** `MaestroSincoImportService.php:145` lo devuelve; `MaestroInsumos.tsx:282` no lo pinta. Mostrarlo es un cambio de la pantalla del Maestro, y arrastra su línea de ayuda en el mismo commit (regla de la Task 7).
2. **Subpaquetes necesitará su ayuda** cuando la fila 8a cierre: un apartado en la ayuda del Plan y, si acaba siendo pantalla propia, su entrada en `AYUDAS`.

- [ ] **Step 6: Mark the relay board and commit**

Solo con las verificaciones corridas y en verde. Fila 6 de `goals/pdc-preparar-b1/estado-olas.md` a `HECHO`, con sha y fecha. **Solo la fila 6** — nadie marca la de otra sesión.

```bash
git add goals/pdc-preparar-b1/estado-olas.md
git commit -m "chore(pdc): la ayuda in-app queda hecha y el tablero lo registra"
```

---

## Self-Review

**Cobertura del spec:**

| Condición de hecho del spec | Tarea |
|---|---|
| 1. Las nueve pantallas tienen su botón y cada texto responde las tres preguntas | Tasks 1, 5 — **ocho** páginas, no nueve: el inventario medido corrigió la cifra, y el usuario decidió la granularidad por página |
| 2. Primera vez lanza el recorrido; omitirlo lo cierra y no reaparece; se relanza desde la ayuda | Tasks 2, 4, 6 |
| 3. Un revisor ajeno recorre el flujo sin preguntar | **Task 8** — el hecho de verdad |
| 4. Se abre y cierra con teclado y el foco vuelve donde estaba | Task 3 (`<dialog>` + `onClose`), verificado en Task 6 |
| 5. Los e2e de las pantallas afectadas siguen verdes con el recorrido desactivado | Task 8, paso 4 |
| Regla de proceso escrita | **Task 7** |
| Contenido en el repositorio, un solo componente, estado en el navegador, tokens del DS | Global Constraints + Tasks 1–4 |

**Huecos declarados, no olvidados:**

- **Subpaquetes sin ayuda**, por instrucción y comprobado: la fila 8a del tablero sigue `EN CURSO` («falta la pantalla de partir y repartir») y en este árbol `pdc-app/src/components/` no contiene ningún `SubpaquetesPanel`. Anotado en `docs/pdc-v2.md` (Task 7) para que se vea que falta, en vez de descubrirse.
- **`reenganchados` sin documentar**, porque no se ve en pantalla (ver arriba). Se levanta como pendiente aparte en la Task 8, no se tapa con texto.
- **«Comparar» y «Pasos» quedan fuera del recorrido** (6 paradas, no 8) aunque sí tienen botón. El recorrido cuenta el camino; esas dos son consulta y configuración de una vez.

**Consistencia de tipos:** `PantallaAyuda` se define en Task 1 y la consumen Tasks 2, 3, 5. `ayudaDe` / `AYUDAS` / `PANTALLAS_AYUDA` se nombran igual en las cuatro. `leerVisto` / `marcarVisto` / `olvidarVisto` se definen en Task 2 y se usan con esos nombres en Tasks 4 y 5. Los ocho `pantalla` de la tabla de Task 5 son exactamente las ocho claves de `AYUDAS`, y `ayuda.test.ts` lo afirma.

**Riesgo que el plan no elimina:** los textos son verdad *hoy*. La Task 7 es lo único que evita que dejen de serlo, y es una regla que cumplen personas, no un gate. Es la debilidad conocida de este entregable y conviene decirlo en el cierre.
