---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-06
areas: [pdc]
fuente: docs/superpowers/plans/2026-08-06-pdc-filtros-y-buscadores.md
resumen: Que las once grillas del Plan de Compras se puedan filtrar por columna y buscar, y que los treinta y un <select> del módulo se puedan buscar escribiendo.
---

# Plan de Compras: filtros de columna, buscadores y selects buscables — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que las once grillas del Plan de Compras se puedan filtrar por columna y buscar, y que los treinta y un `<select>` del módulo se puedan buscar escribiendo.

**Architecture:** Una única lista con buscador (`ListaBuscable`) sirve a los tres usos: desplegable (`Selector`), filtro de columna de AG Grid (`FiltroLista`) y —vía la normalización de texto que ya existe— el buscador rápido. Toda la lógica que se puede probar vive en módulos puros de `lib/`, porque las pruebas del módulo corren en `environment: 'node'` sin DOM. El filtro se cuelga de los presets de columna de `lib/agGrid.ts`, que las once grillas ya comparten, así que lo heredan sin editarlas una a una.

**Tech Stack:** React 19, TypeScript, Vite 8, Vitest 4 (`environment: 'node'`, sin jsdom ni Testing Library), AG Grid Community 36.0.2, Playwright para navegador.

**Spec:** `docs/superpowers/specs/2026-08-06-pdc-filtros-y-buscadores-design.md`

## Global Constraints

- **Desktop ≥1180 px y dark mode únicamente.** Viewport canónico de validación: **1180×820**. Ningún trabajo, prueba ni evidencia para mobile, tablet o tema `linen` (AGENTS.md).
- **Sin hex nuevos.** Los colores salen de tokens `--ds-*` o `--pdc-*` existentes (`DESIGN.md`, `docs/design-system/README.md`).
- **Registro selectivo de módulos AG Grid.** Nunca `AllCommunityModule`: arrastra ~1,3 MB (comentario vigente en `lib/agGrid.ts:10-19`).
- **Todo el texto de interfaz en español.** Identificadores, rutas y comandos en su idioma.
- **Las pruebas unitarias corren sin DOM.** `vite.config.ts` fija `test.environment: 'node'` y el proyecto **no** tiene jsdom ni `@testing-library`. Ninguna tarea puede introducir un test que renderice React. La lógica se extrae a `lib/` y se prueba ahí; lo visual se valida en navegador.
- **Nada se persiste.** Ni `localStorage`, ni `sessionStorage`, ni estado entre pestañas.
- **Comandos:** `cd pdc-app && npm test` (vitest), `cd pdc-app && npm run build` (incluye `tsc`), `npx playwright test tests/browser/<spec> --workers=1` desde la raíz del repo.
- **No commit fuera de los pasos que lo piden explícitamente**, y siempre con `git add` de rutas concretas: el worktree tiene cambios ajenos a esta tarea.

## Estructura de archivos

| Archivo | Responsabilidad |
|---|---|
| `pdc-app/src/lib/texto.ts` (modificar) | Ya tiene `normaliza` y `filtraPorTexto`. Gana `coincide`. **El spec pedía un `lib/coincide.ts` nuevo; sería un duplicado de `normaliza`.** |
| `pdc-app/src/lib/listaBuscable.ts` (crear) | Lógica pura de la lista: recorte por búsqueda, umbral de la lupa, movimiento del resaltado, alternar selección. |
| `pdc-app/src/lib/filtroLista.ts` (crear) | Lógica pura del filtro de columna: modelo, `pasaFiltroLista`, orden de valores distintos. |
| `pdc-app/src/components/ListaBuscable.tsx` (crear) | Render y accesibilidad de la lista. Sin lógica propia: llama a `lib/listaBuscable.ts`. |
| `pdc-app/src/components/Selector.tsx` (crear) | Botón + popup. Sustituto de `<select>`. |
| `pdc-app/src/components/FiltroLista.tsx` (crear) | Adaptador a `useGridFilter` de AG Grid. |
| `pdc-app/src/components/BarraFiltros.tsx` (crear) | Chips de filtros activos y «Limpiar todo». |
| `pdc-app/src/lib/agGrid.ts` (modificar) | Módulos, `localeText` en español, `filter` en los presets, helper de buscador rápido. |
| `pdc-app/src/styles.css` (modificar) | Skin del popup, del gatillo de cabecera y de los chips. |
| `tests/browser/support/pdc-selector.mjs` (crear) | Ayudante Playwright `elegirEnSelector`, sustituto de `selectOption` para el control nuevo. |

## Inventario de `<select>` a migrar (31, medido el 2026-08-06)

`PlanFechas.tsx` 10 · `PaquetesContratacion.tsx` 5 · `Seguimiento.tsx` 4 · `VisorPresupuesto.tsx` 4 · `ComparativoPresupuesto.tsx` 2 · `PaquetesAsistente.tsx` 2 · `PasosContratacion.tsx` 2 · `SubpaquetesPanel.tsx` 2.

Recuéntalo antes de la Task 5 con `grep -c "<select" src/pages/*.tsx src/components/*.tsx`: el módulo se mueve.

---

### Task 1: `coincide` — la comparación de texto que comparten los tres usos

**Files:**
- Modify: `pdc-app/src/lib/texto.ts` (añadir al final)
- Test: `pdc-app/src/lib/texto.test.ts` (añadir un `describe`)

**Interfaces:**
- Consumes: `normaliza(s: string): string`, que ya existe en ese archivo y conserva la ñ a propósito.
- Produces: `coincide(texto: string, busqueda: string): boolean`. La usan las Tasks 2, 4, 7 y 8.

- [ ] **Step 1: Escribe la prueba que falla**

Añade al final de `pdc-app/src/lib/texto.test.ts` (y añade `coincide` a la lista de importaciones de la línea 2):

```ts
describe('coincide', () => {
  it('ignora mayúsculas y acentos', () => {
    expect(coincide('Cementó Gris', 'cemento')).toBe(true)
  })

  it('conserva la ñ: «caño» no es «cano»', () => {
    expect(coincide('CAÑO PVC', 'cano')).toBe(false)
    expect(coincide('CAÑO PVC', 'caño')).toBe(true)
  })

  it('una búsqueda vacía o de solo espacios coincide con todo', () => {
    expect(coincide('lo que sea', '')).toBe(true)
    expect(coincide('lo que sea', '   ')).toBe(true)
  })

  it('busca por subcadena, no solo por prefijo', () => {
    expect(coincide('MAT-ELECTRICOS Y AFINES', 'electri')).toBe(true)
  })
})
```

- [ ] **Step 2: Corre la prueba y comprueba que falla**

```bash
cd pdc-app && npx vitest run src/lib/texto.test.ts
```

Esperado: FALLA con «coincide is not exported» o «coincide is not a function».

- [ ] **Step 3: Implementación mínima**

Añade al final de `pdc-app/src/lib/texto.ts`:

```ts
/**
 * ¿El texto contiene lo buscado? Sin distinguir mayúsculas ni acentos, conservando la ñ.
 *
 * Es el mismo criterio que `filtraPorTexto` aplicado a un solo valor: lo necesitan la lista
 * buscable, el filtro de columna y el buscador rápido, y tenerlo en un solo sitio es lo que
 * garantiza que los tres respondan igual a lo que el usuario teclea.
 */
export function coincide(texto: string, busqueda: string): boolean {
  const q = normaliza(busqueda.trim())
  if (q === '') return true
  return normaliza(texto).includes(q)
}
```

- [ ] **Step 4: Corre la prueba y comprueba que pasa**

```bash
cd pdc-app && npx vitest run src/lib/texto.test.ts
```

Esperado: PASA, incluidos los tests que ya había en el archivo.

- [ ] **Step 5: Commit**

```bash
git add pdc-app/src/lib/texto.ts pdc-app/src/lib/texto.test.ts
git commit -m "feat(pdc): coincide(), la comparación de texto que comparten los tres buscadores"
```

---

### Task 2: `lib/listaBuscable.ts` — la lógica de la lista

**Files:**
- Create: `pdc-app/src/lib/listaBuscable.ts`
- Test: `pdc-app/src/lib/listaBuscable.test.ts`

**Interfaces:**
- Consumes: `coincide` de la Task 1.
- Produces, y de esto dependen las Tasks 3, 4, 6 y 7:
  - `interface Opcion { valor: string; etiqueta: string }`
  - `MINIMO_PARA_BUSCAR: 8`
  - `necesitaBuscador(cuantasOpciones: number): boolean`
  - `opcionesVisibles(opciones: Opcion[], busqueda: string): Opcion[]`
  - `mueveResaltado(actual: number, tecla: string, total: number): number`
  - `alterna(seleccion: string[], valor: string): string[]`

- [ ] **Step 1: Escribe la prueba que falla**

Crea `pdc-app/src/lib/listaBuscable.test.ts`:

```ts
import { describe, expect, it } from 'vitest'
import {
  alterna, MINIMO_PARA_BUSCAR, mueveResaltado, necesitaBuscador, opcionesVisibles,
} from './listaBuscable'
import type { Opcion } from './listaBuscable'

const o = (v: string, e = v): Opcion => ({ valor: v, etiqueta: e })

describe('necesitaBuscador', () => {
  it('el umbral acordado con el dueño del producto son 8 opciones', () => {
    expect(MINIMO_PARA_BUSCAR).toBe(8)
  })

  it('con 7 opciones no aparece la lupa; con 8 sí', () => {
    expect(necesitaBuscador(7)).toBe(false)
    expect(necesitaBuscador(8)).toBe(true)
  })
})

describe('opcionesVisibles', () => {
  // «Arena lavada» no lleva «o» a propósito: es lo que hace que buscar 'o' salte el elemento del
  // medio y el test de orden pruebe algo. Con tres etiquetas que contengan «o» ese test es imposible.
  const opciones = [o('a', 'Cemento gris'), o('b', 'Arena lavada'), o('c', 'CAÑO PVC')]

  it('recorta por la etiqueta, no por el valor', () => {
    expect(opcionesVisibles(opciones, 'arena').map((x) => x.valor)).toEqual(['b'])
  })

  it('sin búsqueda devuelve todas', () => {
    expect(opcionesVisibles(opciones, '')).toHaveLength(3)
  })

  it('conserva el orden original', () => {
    expect(opcionesVisibles(opciones, 'o').map((x) => x.valor)).toEqual(['a', 'c'])
  })
})

describe('mueveResaltado', () => {
  it('baja y sube dentro de la lista', () => {
    expect(mueveResaltado(0, 'ArrowDown', 3)).toBe(1)
    expect(mueveResaltado(2, 'ArrowUp', 3)).toBe(1)
  })

  it('da la vuelta en los extremos: la lista es circular', () => {
    expect(mueveResaltado(2, 'ArrowDown', 3)).toBe(0)
    expect(mueveResaltado(0, 'ArrowUp', 3)).toBe(2)
  })

  it('Home e End van a los extremos', () => {
    expect(mueveResaltado(1, 'Home', 3)).toBe(0)
    expect(mueveResaltado(1, 'End', 3)).toBe(2)
  })

  it('una tecla cualquiera no mueve nada', () => {
    expect(mueveResaltado(1, 'a', 3)).toBe(1)
  })

  it('con la lista vacía se queda en 0 y no devuelve -1', () => {
    expect(mueveResaltado(0, 'ArrowDown', 0)).toBe(0)
    expect(mueveResaltado(0, 'ArrowUp', 0)).toBe(0)
  })
})

describe('alterna', () => {
  it('añade lo que no estaba y quita lo que estaba', () => {
    expect(alterna(['a'], 'b')).toEqual(['a', 'b'])
    expect(alterna(['a', 'b'], 'a')).toEqual(['b'])
  })

  it('no muta el arreglo que recibe', () => {
    const antes = ['a']
    alterna(antes, 'b')
    expect(antes).toEqual(['a'])
  })
})
```

- [ ] **Step 2: Corre la prueba y comprueba que falla**

```bash
cd pdc-app && npx vitest run src/lib/listaBuscable.test.ts
```

Esperado: FALLA con «Failed to resolve import "./listaBuscable"».

- [ ] **Step 3: Implementación mínima**

Crea `pdc-app/src/lib/listaBuscable.ts`:

```ts
import { coincide } from './texto'

/**
 * Lógica de la lista con buscador, sin React.
 *
 * Vive aquí y no dentro del componente porque las pruebas del módulo corren en
 * `environment: 'node'` y el proyecto no tiene jsdom: lo que esté en el .tsx no se puede probar.
 * Es el mismo reparto que ya siguen `paquetesState` y `planFechas`.
 */

/** Una opción de la lista. `valor` es lo que viaja al estado; `etiqueta`, lo que se lee y se busca. */
export interface Opcion {
  valor: string
  etiqueta: string
}

/**
 * A partir de cuántas opciones aparece la caja de búsqueda.
 *
 * Decisión del dueño del producto (2026-08-06): una lupa sobre tres opciones es ruido; sobre
 * trescientas es imprescindible. El control se ve igual en ambos casos, solo cambia si trae caja.
 */
export const MINIMO_PARA_BUSCAR = 8

export function necesitaBuscador(cuantasOpciones: number): boolean {
  return cuantasOpciones >= MINIMO_PARA_BUSCAR
}

/** Las opciones que quedan tras teclear. Conserva el orden que traía la lista. */
export function opcionesVisibles(opciones: Opcion[], busqueda: string): Opcion[] {
  return opciones.filter((o) => coincide(o.etiqueta, busqueda))
}

/**
 * Dónde queda el resaltado al pulsar una tecla. La lista es circular: bajar desde el último
 * lleva al primero, que es lo que hace un `<select>` nativo abierto y lo que la gente espera.
 */
export function mueveResaltado(actual: number, tecla: string, total: number): number {
  if (total <= 0) return 0
  switch (tecla) {
    case 'ArrowDown': return (actual + 1) % total
    case 'ArrowUp': return (actual - 1 + total) % total
    case 'Home': return 0
    case 'End': return total - 1
    default: return actual
  }
}

/** Marca o desmarca un valor en una selección múltiple. Devuelve un arreglo nuevo. */
export function alterna(seleccion: string[], valor: string): string[] {
  return seleccion.includes(valor) ? seleccion.filter((v) => v !== valor) : [...seleccion, valor]
}
```

- [ ] **Step 4: Corre la prueba y comprueba que pasa**

```bash
cd pdc-app && npx vitest run src/lib/listaBuscable.test.ts
```

Esperado: PASA, 13 tests.

- [ ] **Step 5: Commit**

```bash
git add pdc-app/src/lib/listaBuscable.ts pdc-app/src/lib/listaBuscable.test.ts
git commit -m "feat(pdc): lógica de la lista buscable (umbral de 8, recorte, teclado)"
```

---

### Task 3: `components/ListaBuscable.tsx` — el render

**Files:**
- Create: `pdc-app/src/components/ListaBuscable.tsx`

**Interfaces:**
- Consumes: todo lo de la Task 2.
- Produces, y de esto dependen las Tasks 4 y 7:
  ```ts
  interface ListaBuscableProps {
    opciones: Opcion[]
    modo: 'una' | 'varias'
    seleccion: string[]          // en modo 'una', 0 o 1 elemento
    onSeleccion: (s: string[]) => void
    onCerrar?: () => void        // Escape, o elegir en modo 'una'
    idBase: string               // prefijo de los id de las opciones, para aria-activedescendant
  }
  ```

No hay prueba unitaria: renderiza React y el entorno de test no tiene DOM (ver Global Constraints). Se valida en navegador en la Task 10. Esto es una limitación conocida del proyecto, no un descuido; la lógica que sí importa ya quedó probada en la Task 2.

- [ ] **Step 1: Escribe el componente**

Crea `pdc-app/src/components/ListaBuscable.tsx`:

```tsx
import { useEffect, useMemo, useRef, useState } from 'react'
import {
  alterna, mueveResaltado, necesitaBuscador, opcionesVisibles,
} from '../lib/listaBuscable'
import type { Opcion } from '../lib/listaBuscable'

export interface ListaBuscableProps {
  opciones: Opcion[]
  modo: 'una' | 'varias'
  seleccion: string[]
  onSeleccion: (s: string[]) => void
  onCerrar?: () => void
  idBase: string
}

/**
 * Lista de opciones con buscador. Es la única lista del módulo: la usan el desplegable
 * (`Selector`) y el filtro de columna (`FiltroLista`), para que buscar se sienta igual en los dos.
 *
 * La lógica está en `lib/listaBuscable.ts` y aquí solo queda el render — el entorno de pruebas del
 * módulo no tiene DOM, así que todo lo comprobable tiene que vivir fuera de este archivo.
 */
export function ListaBuscable({
  opciones, modo, seleccion, onSeleccion, onCerrar, idBase,
}: ListaBuscableProps) {
  const [busqueda, setBusqueda] = useState('')
  const [resaltado, setResaltado] = useState(0)
  const cajaBusqueda = useRef<HTMLInputElement>(null)
  const visibles = useMemo(() => opcionesVisibles(opciones, busqueda), [opciones, busqueda])
  const conBuscador = necesitaBuscador(opciones.length)

  // Al abrir, el foco va a la caja: quien abre una lista de trescientos insumos viene a escribir.
  useEffect(() => { cajaBusqueda.current?.focus() }, [])
  // Teclear recorta la lista, y el resaltado podría quedar apuntando fuera de ella.
  useEffect(() => { setResaltado(0) }, [busqueda])

  const elegir = (valor: string) => {
    if (modo === 'varias') {
      onSeleccion(alterna(seleccion, valor))
      return
    }
    onSeleccion([valor])
    onCerrar?.()
  }

  const alTeclado = (e: React.KeyboardEvent) => {
    if (e.key === 'Escape') { e.preventDefault(); onCerrar?.(); return }
    if (e.key === 'Enter') {
      e.preventDefault()
      const opcion = visibles[resaltado]
      if (opcion) elegir(opcion.valor)
      return
    }
    const siguiente = mueveResaltado(resaltado, e.key, visibles.length)
    if (siguiente !== resaltado) { e.preventDefault(); setResaltado(siguiente) }
  }

  return (
    <div className="pdc-lista" onKeyDown={alTeclado}>
      {conBuscador && (
        <input
          ref={cajaBusqueda}
          type="search"
          className="pdc-lista-buscar"
          placeholder="Buscar…"
          aria-label="Buscar en la lista"
          aria-controls={`${idBase}-opciones`}
          value={busqueda}
          onChange={(e) => setBusqueda(e.target.value)}
        />
      )}
      {modo === 'varias' && (
        <div className="pdc-lista-masa">
          <button type="button" onClick={() => onSeleccion(visibles.map((o) => o.valor))}>
            Todas
          </button>
          <button type="button" onClick={() => onSeleccion([])}>Ninguna</button>
        </div>
      )}
      <ul
        id={`${idBase}-opciones`}
        className="pdc-lista-opciones"
        role="listbox"
        aria-multiselectable={modo === 'varias' || undefined}
        aria-activedescendant={visibles[resaltado] ? `${idBase}-op-${resaltado}` : undefined}
        tabIndex={conBuscador ? -1 : 0}
      >
        {visibles.map((o, i) => (
          <li
            key={o.valor}
            id={`${idBase}-op-${i}`}
            role="option"
            aria-selected={seleccion.includes(o.valor)}
            className={i === resaltado ? 'pdc-lista-op es-resaltada' : 'pdc-lista-op'}
            onClick={() => elegir(o.valor)}
            onMouseEnter={() => setResaltado(i)}
          >
            {modo === 'varias' && (
              <input type="checkbox" readOnly checked={seleccion.includes(o.valor)} tabIndex={-1} />
            )}
            {o.etiqueta}
          </li>
        ))}
        {visibles.length === 0 && <li className="pdc-lista-vacia">Nada coincide con «{busqueda}»</li>}
      </ul>
    </div>
  )
}
```

- [ ] **Step 2: Comprueba que compila**

```bash
cd pdc-app && npx tsc --noEmit
```

Esperado: sin errores. Si `React.KeyboardEvent` da error de tipo, añade `import type { KeyboardEvent } from 'react'` y usa `KeyboardEvent` a secas.

- [ ] **Step 3: Comprueba que no rompiste nada**

```bash
cd pdc-app && npm test
```

Esperado: PASA todo lo que ya pasaba.

- [ ] **Step 4: Commit**

```bash
git add pdc-app/src/components/ListaBuscable.tsx
git commit -m "feat(pdc): componente ListaBuscable, la lista que comparten selects y filtros"
```

---

### Task 4: `Selector` y la primera página migrada (Seguimiento)

Esta tarea prueba el patrón entero de punta a punta en una sola página antes de repetirlo en las otras siete. Incluye el ayudante de Playwright porque sin él los specs existentes se caen.

**Files:**
- Create: `pdc-app/src/components/Selector.tsx`
- Create: `tests/browser/support/pdc-selector.mjs`
- Modify: `pdc-app/src/pages/Seguimiento.tsx` (los 4 `<select>`: líneas ~241, ~254, ~492, ~499)
- Modify: `tests/browser/pdc-v2-vencimientos.spec.mjs:72` (única llamada `selectOption` que apunta a Seguimiento)

**Interfaces:**
- Consumes: `ListaBuscable` (Task 3), `Opcion` (Task 2).
- Produces, y de esto dependen las Tasks 5 y 9:
  ```ts
  interface SelectorProps {
    value: string                       // '' = nada elegido
    onChange: (valor: string) => void   // siempre string, como e.target.value
    opciones: Opcion[]
    etiqueta: string                    // aria-label, obligatorio
    placeholder?: string                // qué se lee con value === ''
    disabled?: boolean
    testid?: string                     // se emite como data-testid
  }
  ```
  Y en `tests/browser/support/pdc-selector.mjs`:
  `elegirEnSelector(page, testid, etiquetaVisible): Promise<void>`

- [ ] **Step 1: Escribe el componente**

Crea `pdc-app/src/components/Selector.tsx`:

```tsx
import { useEffect, useId, useRef, useState } from 'react'
import { ListaBuscable } from './ListaBuscable'
import type { Opcion } from '../lib/listaBuscable'

export interface SelectorProps {
  value: string
  onChange: (valor: string) => void
  opciones: Opcion[]
  etiqueta: string
  placeholder?: string
  disabled?: boolean
  testid?: string
}

/**
 * Sustituto de `<select>`. Misma forma de uso (valor controlado, `onChange` con un string), pero
 * la lista se puede buscar en cuanto pasa de ocho opciones.
 *
 * Por qué no es un `<select>` nativo con `<datalist>`: el nativo no admite buscar dentro de sus
 * opciones y `<datalist>` no restringe a la lista. Aquí el valor siempre sale de las opciones.
 */
export function Selector({
  value, onChange, opciones, etiqueta, placeholder = 'Elegir…', disabled = false, testid,
}: SelectorProps) {
  const [abierto, setAbierto] = useState(false)
  const caja = useRef<HTMLDivElement>(null)
  const idBase = useId()
  const elegida = opciones.find((o) => o.valor === value)

  // Cerrar al hacer clic fuera. Sin esto quedan dos popups abiertos a la vez cuando la página
  // tiene varios selectores seguidos, que es el caso de Paquetes y del Plan.
  useEffect(() => {
    if (!abierto) return
    const fuera = (e: MouseEvent) => {
      if (caja.current && !caja.current.contains(e.target as Node)) setAbierto(false)
    }
    document.addEventListener('mousedown', fuera)
    return () => document.removeEventListener('mousedown', fuera)
  }, [abierto])

  return (
    <div className="pdc-selector-caja" ref={caja}>
      <button
        type="button"
        className="pdc-selector-boton"
        data-testid={testid}
        aria-label={etiqueta}
        aria-haspopup="listbox"
        aria-expanded={abierto}
        disabled={disabled}
        onClick={() => setAbierto((a) => !a)}
      >
        <span className={elegida ? 'pdc-selector-valor' : 'pdc-selector-valor es-vacio'}>
          {elegida ? elegida.etiqueta : placeholder}
        </span>
        <span className="pdc-selector-flecha" aria-hidden="true" />
      </button>
      {abierto && (
        <div className="pdc-selector-popup">
          <ListaBuscable
            opciones={opciones}
            modo="una"
            seleccion={value === '' ? [] : [value]}
            onSeleccion={(s) => onChange(s[0] ?? '')}
            onCerrar={() => setAbierto(false)}
            idBase={idBase}
          />
        </div>
      )}
    </div>
  )
}
```

- [ ] **Step 2: Escribe el ayudante de Playwright**

Los specs del PDC usan `locator(...).selectOption(valor)` en unas veinte llamadas. Contra el control nuevo eso falla con «Element is not a <select> element». Crea `tests/browser/support/pdc-selector.mjs`:

```js
/**
 * Elegir una opción en el `Selector` del PDC, que ya no es un `<select>` nativo.
 *
 * Sustituto directo de `locator('[data-testid=X]').selectOption(v)`: abre el popup, y si trae
 * caja de búsqueda —a partir de ocho opciones— escribe para acotar antes de hacer clic. Por eso
 * recibe la **etiqueta visible**, no el valor interno: es lo único que el usuario ve.
 */
export async function elegirEnSelector(page, testid, etiquetaVisible) {
  await page.locator(`[data-testid="${testid}"]`).click();
  const popup = page.locator('.pdc-selector-popup');
  await popup.waitFor({ state: 'visible' });
  const buscar = popup.locator('.pdc-lista-buscar');
  if (await buscar.count() > 0) await buscar.fill(etiquetaVisible);
  await popup.getByRole('option', { name: etiquetaVisible, exact: false }).first().click();
  await popup.waitFor({ state: 'detached' });
}
```

- [ ] **Step 3: Migra los cuatro `<select>` de Seguimiento**

En `pdc-app/src/pages/Seguimiento.tsx`, sustituye cada `<label className="pdc-selector">…<select>…</select></label>` por `<Selector>`. Patrón, tomándolo del filtro de frente (línea ~492):

```tsx
// Antes:
// <select value={filtros.frente} onChange={(e) => setFiltros((f) => ({ ...f, frente: e.target.value }))}>
//   <option value="">Todos los frentes</option>
//   {frentes.map((f) => <option key={f} value={f}>{f}</option>)}
// </select>

// Después:
<Selector
  etiqueta="Filtrar por frente"
  placeholder="Todos los frentes"
  value={filtros.frente}
  onChange={(v) => setFiltros((f) => ({ ...f, frente: v }))}
  opciones={frentes.map((f) => ({ valor: f, etiqueta: f }))}
/>
```

Reglas al migrar, que valen también para la Task 5:
- **El rótulo visible se conserva.** El `<Selector>` va dentro del mismo `<label className="pdc-selector">` que ya existía, con su texto delante. `etiqueta` alimenta el `aria-label` del botón y debe decir lo mismo que el rótulo visible o ampliarlo («Filtrar por frente»), nunca sustituirlo: dos cajas que solo dicen «Todos» no se distinguen. Como el control ya no es un `<select>` nativo sino un `<button>`, el `<label>` no le da nombre accesible por sí solo — ese lo pone `etiqueta`; y no dejes un `htmlFor` apuntando a nada.
- La `<option value="">` de «todos» **no** es una opción: pasa a `placeholder`.
- Si el `<select>` guardaba un número (`Number(e.target.value)`), convierte en el `onChange`: `onChange={(v) => setX(v === '' ? '' : Number(v))}` y pasa `value={String(x ?? '')}`.
- El `data-testid` que hubiera se conserva **con el mismo nombre**, ahora en `testid`.
- El `aria-label` que hubiera pasa a `etiqueta`; si no había, escribe uno que diga qué elige.
- Añade `import { Selector } from '../components/Selector'`.

- [ ] **Step 4: Compila y corre las pruebas unitarias**

```bash
cd pdc-app && npx tsc --noEmit && npm test
```

Esperado: sin errores de tipo, y todas las pruebas de `lib/` pasan.

- [ ] **Step 5: Arregla el spec de navegador de Seguimiento**

En `tests/browser/pdc-v2-vencimientos.spec.mjs`, línea ~72, sustituye la llamada `await select.selectOption(valor)` por el ayudante. Lee el contexto de esas líneas para saber qué localizador es `select` y qué etiqueta visible corresponde a `valor`; importa el ayudante arriba:

```js
import { elegirEnSelector } from './support/pdc-selector.mjs';
```

- [ ] **Step 6: Corre el spec contra Docker**

Levanta el stack si no está (`docker compose up -d db app`), publica el bundle (`cd pdc-app && npm run build`) y:

```bash
npx playwright test tests/browser/pdc-v2-vencimientos.spec.mjs --workers=1
```

Esperado: PASA. Si falla por tiempo de espera del popup, comprueba antes que el bundle recién construido es el que sirve Apache (`public/pdc-app/assets/pdc.js`, fecha de modificación de hace segundos).

- [ ] **Step 7: Commit**

```bash
git add pdc-app/src/components/Selector.tsx tests/browser/support/pdc-selector.mjs \
        pdc-app/src/pages/Seguimiento.tsx tests/browser/pdc-v2-vencimientos.spec.mjs
git commit -m "feat(pdc): Selector buscable, estrenado en Seguimiento"
```

---

### Task 5: Migrar los 27 `<select>` restantes

**Files:**
- Modify: `pdc-app/src/pages/PlanFechas.tsx` (10), `PaquetesContratacion.tsx` (5), `VisorPresupuesto.tsx` (4), `ComparativoPresupuesto.tsx` (2), `PaquetesAsistente.tsx` (2), `PasosContratacion.tsx` (2), `pdc-app/src/components/SubpaquetesPanel.tsx` (2)
- Modify: los specs con `selectOption` sobre esos testids — `pdc-v2-plan.spec.mjs`, `pdc-v2-pasos.spec.mjs`, `pdc-v2-paquetes.spec.mjs`, `pdc-v2-desamarrar.spec.mjs`, `pdc-v2-tamiz.spec.mjs`, `pdc-v2-responsable.spec.mjs`, `pdc-v2-visor.spec.mjs`

**Interfaces:**
- Consumes: `Selector` y `elegirEnSelector` (Task 4). Sin interfaces nuevas.

Se hace **página por página, con su commit**, siguiendo las reglas de migración de la Task 4 Step 3. Dos avisos medidos:

- `PlanFechas.tsx` tiene un `<select>` **por fila** en la sección «sin frente» (línea ~1098) y otro por rama en «pendientes de calcular» (~1041). Son los de mayor riesgo: el de frente NO debe disparar el amarre en su `onChange` —solo elegir—, según el crítico del review documentado en `PlanFechas.tsx:463`. Conserva ese comportamiento tal cual.
- `pdc-v2-desamarrar.spec.mjs:79` usa `primero.locator('select').selectOption({ index: 1 })`: localiza por **etiqueta de elemento**, no por testid. Al migrar hay que darle un `testid` a ese selector de fila y usar el ayudante.

- [ ] **Step 1: Recuenta antes de empezar**

```bash
cd pdc-app && grep -c "<select" src/pages/*.tsx src/components/*.tsx
```

Anota el total. Al terminar la tarea debe ser 0 en todos.

- [ ] **Step 2: Migra `VisorPresupuesto.tsx` (los 4) y corre su spec**

Aplica el patrón de la Task 4. Ojo con `pdc-visor-nivel` y `pdc-visor-version`, que guardan números. Luego:

```bash
cd pdc-app && npx tsc --noEmit && npm run build
cd .. && npx playwright test tests/browser/pdc-v2-visor.spec.mjs --workers=1
```

En ese spec, las líneas 51, 57 y 73 usan `selectOption({ label })` y `selectOption({ index })`. La de etiqueta se traduce directa (`elegirEnSelector(page, 'pdc-visor-nivel', 'Capítulo')`); la de índice necesita la etiqueta real, que el propio test puede leer del popup — o bien fíjala explícitamente si el fixture la determina.

Esperado: PASA. Commit:

```bash
git add pdc-app/src/pages/VisorPresupuesto.tsx tests/browser/pdc-v2-visor.spec.mjs
git commit -m "refactor(pdc): el Visor usa Selector buscable"
```

- [ ] **Step 3: Migra `PaquetesContratacion.tsx` (los 5) y corre sus specs**

```bash
cd pdc-app && npx tsc --noEmit && npm run build
cd .. && npx playwright test tests/browser/pdc-v2-paquetes.spec.mjs tests/browser/pdc-v2-tamiz.spec.mjs tests/browser/pdc-v2-desamarrar.spec.mjs --workers=1
```

`pdc-paq-select-paquete` es de los que más se benefician: lista todos los paquetes del catálogo. Esperado: PASA. Commit análogo al anterior.

- [ ] **Step 4: Migra `PlanFechas.tsx` (los 10) y corre sus specs**

```bash
cd pdc-app && npx tsc --noEmit && npm run build
cd .. && npx playwright test tests/browser/pdc-v2-plan.spec.mjs tests/browser/pdc-v2-responsable.spec.mjs tests/browser/pdc-v2-desamarrar.spec.mjs --workers=1
```

Esperado: PASA. Commit.

- [ ] **Step 5: Migra `PasosContratacion.tsx`, `ComparativoPresupuesto.tsx`, `PaquetesAsistente.tsx` y `SubpaquetesPanel.tsx`**

```bash
cd pdc-app && npx tsc --noEmit && npm run build
cd .. && npx playwright test tests/browser/pdc-v2-pasos.spec.mjs tests/browser/pdc-v2-comparar.spec.mjs --workers=1
```

Esperado: PASA. Commit.

- [ ] **Step 6: Comprueba que no queda ningún `<select>` ni ningún `selectOption` huérfano**

```bash
cd pdc-app && grep -rn "<select" src/ ; cd .. && grep -rn "selectOption" tests/browser/pdc-v2-*.mjs
```

Esperado: **ninguna salida en ambos**. Si `grep` de `selectOption` devuelve algo, ese spec se va a caer: arréglalo antes de seguir.

- [ ] **Step 7: Commit final de la tarea**

```bash
git add pdc-app/src tests/browser
git commit -m "refactor(pdc): los 31 select del módulo son ahora Selector buscable"
```

---

### Task 6: Módulos, idioma y filtros en los presets de columna

Es la tarea que hace que las once grillas ganen filtro sin editarlas una a una.

**Files:**
- Modify: `pdc-app/src/lib/agGrid.ts` (`MODULOS_TABLA` línea 19; los presets, líneas 100-166)
- Test: `pdc-app/src/lib/agGrid.test.ts` (añadir un `describe`)

**Interfaces:**
- Produces: `localeTextEs: Record<string, string>` y los presets ya existentes (`CIFRA`, `COLUMNA_FECHA`, `COLUMNA_CATEGORIA`, `COLUMNA_CORTA`, `TEXTO_LARGO`, `columnaMoneda`, `columnaNumero`, `columnaTexto`) con su `filter`. Lo consumen las once páginas y la Task 7.

- [ ] **Step 1: Escribe la prueba que falla**

Añade a `pdc-app/src/lib/agGrid.test.ts` (ajusta las importaciones de la cabecera del archivo):

```ts
describe('filtros en los presets', () => {
  it('el dinero filtra como número, no como texto', () => {
    expect(columnaMoneda<{ v: number }>('v', 'Valor').filter).toBe('agNumberColumnFilter')
  })

  it('las cantidades también', () => {
    expect(columnaNumero<{ v: number }>('v', 'Cantidad').filter).toBe('agNumberColumnFilter')
  })

  it('el texto largo filtra como texto', () => {
    expect(columnaTexto<{ v: string }>('v', 'Descripción').filter).toBe('agTextColumnFilter')
  })

  it('las fechas filtran como fecha', () => {
    expect(COLUMNA_FECHA.filter).toBe('agDateColumnFilter')
  })

  it('las columnas categóricas usan la lista propia', () => {
    expect(COLUMNA_CATEGORIA.filter).toBe(FiltroLista)
    expect(COLUMNA_CORTA.filter).toBe(FiltroLista)
  })
})

describe('localeTextEs', () => {
  it('traduce lo que se ve en el menú de filtro', () => {
    expect(localeTextEs.contains).toBe('Contiene')
    expect(localeTextEs.applyFilter).toBe('Aplicar')
    expect(localeTextEs.resetFilter).toBe('Restablecer')
  })

  it('no deja ninguna cadena en inglés a medias', () => {
    expect(Object.values(localeTextEs).every((v) => typeof v === 'string' && v.length > 0)).toBe(true)
  })
})
```

- [ ] **Step 2: Corre la prueba y comprueba que falla**

```bash
cd pdc-app && npx vitest run src/lib/agGrid.test.ts
```

Esperado: FALLA — `filter` es `undefined` y `localeTextEs` no existe.

- [ ] **Step 3: Implementa**

En `pdc-app/src/lib/agGrid.ts`:

1. Amplía las importaciones y `MODULOS_TABLA`:

```ts
import {
  ClientSideRowModelModule,
  ColumnAutoSizeModule,
  CustomFilterModule,
  DateFilterModule,
  LocaleModule,
  NumberFilterModule,
  QuickFilterModule,
  RowAutoHeightModule,
  TextFilterModule,
  themeQuartz,
} from 'ag-grid-community'
import { FiltroLista } from '../components/FiltroLista'

/**
 * … (conserva el comentario que ya está)
 *
 * Los cinco módulos de filtro son lo que hace existir el embudo de la cabecera y el buscador
 * rápido. `LocaleModule` no es cosmético: sin él el menú dice «Contains», «Apply» y «Reset» en
 * una aplicación que está entera en español.
 */
export const MODULOS_TABLA = [
  ClientSideRowModelModule, ColumnAutoSizeModule, RowAutoHeightModule,
  TextFilterModule, NumberFilterModule, DateFilterModule, CustomFilterModule,
  QuickFilterModule, LocaleModule,
]
```

2. Añade el idioma. Las claves salen de `filterLocaleText.d.ts` de la propia librería; estas son las que aparecen en los menús que usa el módulo:

```ts
/**
 * Textos del menú de filtro en español. AG Grid Community no publica un paquete de idiomas en esta
 * versión (`ag-grid-community/locale` no está en sus `exports`), así que se declara aquí lo que se
 * ve. Lo que no esté en este mapa sale en inglés: si aparece una cadena nueva en pantalla, se añade.
 */
export const localeTextEs: Record<string, string> = {
  applyFilter: 'Aplicar', clearFilter: 'Limpiar', resetFilter: 'Restablecer',
  cancelFilter: 'Cancelar', textFilter: 'Filtro de texto', numberFilter: 'Filtro de número',
  dateFilter: 'Filtro de fecha', filterOoo: 'Filtrar…', empty: 'Elige una',
  equals: 'Igual a', notEqual: 'Distinto de', lessThan: 'Menor que', greaterThan: 'Mayor que',
  lessThanOrEqual: 'Menor o igual que', greaterThanOrEqual: 'Mayor o igual que',
  inRange: 'Entre', inRangeStart: 'Desde', inRangeEnd: 'Hasta',
  contains: 'Contiene', notContains: 'No contiene',
  startsWith: 'Empieza por', endsWith: 'Termina en',
  blank: 'Vacío', notBlank: 'No vacío', before: 'Antes de', after: 'Después de',
  andCondition: 'Y', orCondition: 'O', dateFormatOoo: 'aaaa-mm-dd',
}
```

3. Cuelga el filtro de cada preset:

```ts
export const CIFRA = {
  type: 'rightAligned', wrapText: false, minWidth: MIN_WIDTH_CIFRA, filter: 'agNumberColumnFilter',
} satisfies ColDef

export const COLUMNA_CORTA = { minWidth: 70, maxWidth: 104, filter: FiltroLista } satisfies ColDef

export const COLUMNA_FECHA = { minWidth: 124, maxWidth: 148, filter: 'agDateColumnFilter' } satisfies ColDef

export const COLUMNA_CATEGORIA = {
  flex: 1, minWidth: MIN_WIDTH_PALABRA_LARGA, wrapText: true, autoHeight: true, filter: FiltroLista,
} satisfies ColDef

export const TEXTO_LARGO = {
  wrapText: true, autoHeight: true, flex: 1, minWidth: 200, suppressAutoSize: true,
  filter: 'agTextColumnFilter',
} satisfies ColDef
```

`columnaMoneda`, `columnaNumero` y `columnaTexto` heredan el suyo al esparcir `CIFRA` y `TEXTO_LARGO`; no hay que tocarlas.

- [ ] **Step 4: Pasa `localeText` a las once grillas**

Añade `localeText` al objeto que ya se esparce en cada `<AgGridReact>`:

```ts
export const ajusteDeAncho = {
  localeText: localeTextEs,
  onFirstDataRendered: (p: { api: { sizeColumnsToFit: () => void } }) => p.api.sizeColumnsToFit(),
  onGridSizeChanged: (p: { api: { sizeColumnsToFit: () => void } }) => p.api.sizeColumnsToFit(),
}
```

Comprueba con `grep -rn "ajusteDeAncho" src/pages src/components` que las once lo esparcen; si alguna no lo hace, añádele `{...ajusteDeAncho}`.

- [ ] **Step 5: Corre las pruebas**

```bash
cd pdc-app && npx vitest run src/lib/agGrid.test.ts && npx tsc --noEmit
```

Esperado: PASA. (Esta tarea depende de que exista `FiltroLista`: si la Task 7 aún no está hecha, hazla antes o crea el archivo con el esqueleto de su Step 3 y vuelve.)

- [ ] **Step 6: Commit**

```bash
git add pdc-app/src/lib/agGrid.ts pdc-app/src/lib/agGrid.test.ts
git commit -m "feat(pdc): filtros de columna y menú en español en los presets de tabla"
```

---

### Task 7: `FiltroLista` — el embudo con casillas

**Files:**
- Create: `pdc-app/src/lib/filtroLista.ts`
- Create: `pdc-app/src/components/FiltroLista.tsx`
- Test: `pdc-app/src/lib/filtroLista.test.ts`

**Interfaces:**
- Consumes: `ListaBuscable` (Task 3), `Opcion` (Task 2).
- Produces, y de esto dependen las Tasks 6 y 9:
  - `interface ModeloFiltroLista { valores: string[] }`
  - `VALOR_VACIO = '(sin valor)'`
  - `pasaFiltroLista(modelo: ModeloFiltroLista | null, valor: unknown): boolean`
  - `valoresDistintos(valores: unknown[]): Opcion[]`
  - `FiltroLista` (componente)

- [ ] **Step 1: Escribe la prueba que falla**

Crea `pdc-app/src/lib/filtroLista.test.ts`:

```ts
import { describe, expect, it } from 'vitest'
import { pasaFiltroLista, VALOR_VACIO, valoresDistintos } from './filtroLista'

describe('pasaFiltroLista', () => {
  it('sin modelo no filtra nada', () => {
    expect(pasaFiltroLista(null, 'lo que sea')).toBe(true)
  })

  it('con la lista vacía tampoco filtra: desmarcar todo no deja la tabla en blanco', () => {
    expect(pasaFiltroLista({ valores: [] }, 'MAT')).toBe(true)
  })

  it('deja pasar solo lo marcado', () => {
    expect(pasaFiltroLista({ valores: ['MAT'] }, 'MAT')).toBe(true)
    expect(pasaFiltroLista({ valores: ['MAT'] }, 'MOB')).toBe(false)
  })

  it('null, undefined y cadena vacía se agrupan bajo un solo valor', () => {
    expect(pasaFiltroLista({ valores: [VALOR_VACIO] }, null)).toBe(true)
    expect(pasaFiltroLista({ valores: [VALOR_VACIO] }, undefined)).toBe(true)
    expect(pasaFiltroLista({ valores: [VALOR_VACIO] }, '')).toBe(true)
    expect(pasaFiltroLista({ valores: [VALOR_VACIO] }, 'MAT')).toBe(false)
  })

  it('compara números por su texto: la columna enseña texto', () => {
    expect(pasaFiltroLista({ valores: ['3'] }, 3)).toBe(true)
  })
})

describe('valoresDistintos', () => {
  it('quita repetidos y ordena en español', () => {
    expect(valoresDistintos(['b', 'a', 'b', 'á']).map((o) => o.valor)).toEqual(['a', 'á', 'b'])
  })

  it('agrupa los vacíos y los pone al final', () => {
    const r = valoresDistintos(['b', null, 'a', '', undefined])
    expect(r.map((o) => o.valor)).toEqual(['a', 'b', VALOR_VACIO])
    expect(r[2].etiqueta).toBe(VALOR_VACIO)
  })

  it('con todo vacío devuelve una sola opción', () => {
    expect(valoresDistintos([null, '', undefined])).toHaveLength(1)
  })
})
```

- [ ] **Step 2: Corre la prueba y comprueba que falla**

```bash
cd pdc-app && npx vitest run src/lib/filtroLista.test.ts
```

Esperado: FALLA con «Failed to resolve import "./filtroLista"».

- [ ] **Step 3: Implementa la lógica pura**

Crea `pdc-app/src/lib/filtroLista.ts`:

```ts
import type { Opcion } from './listaBuscable'

/**
 * Lógica del filtro de columna con casillas — el equivalente al `changeType` de Programa General.
 *
 * AG Grid Community no trae *set filter* (es Enterprise), así que el modelo y la comparación se
 * definen aquí y el componente solo los pinta. Aparte, así se puede probar sin DOM.
 */

/** Qué guarda el filtro: los valores marcados. `null` (sin modelo) significa que no filtra. */
export interface ModeloFiltroLista {
  valores: string[]
}

/**
 * Etiqueta única para «esta celda no tiene valor». `null`, `undefined` y `''` son lo mismo para
 * quien mira la tabla —una celda en blanco— y ofrecerlos como tres opciones distintas sería ruido.
 */
export const VALOR_VACIO = '(sin valor)'

/** El valor de una celda, como cadena comparable. */
function comoTexto(valor: unknown): string {
  if (valor === null || valor === undefined || valor === '') return VALOR_VACIO
  return String(valor)
}

/**
 * ¿Esta fila pasa el filtro?
 *
 * Una selección vacía **no** filtra: si desmarcar todo dejara la tabla en blanco, el estado
 * intermedio de «voy a marcar tres de cien» sería una pantalla vacía en cada clic.
 */
export function pasaFiltroLista(modelo: ModeloFiltroLista | null, valor: unknown): boolean {
  if (modelo === null || modelo.valores.length === 0) return true
  return modelo.valores.includes(comoTexto(valor))
}

/**
 * Los valores distintos de una columna, ordenados en español y con los vacíos agrupados al final
 * (donde estorban menos: casi nunca son lo que se busca).
 */
export function valoresDistintos(valores: unknown[]): Opcion[] {
  const vistos = new Set(valores.map(comoTexto))
  const hayVacios = vistos.delete(VALOR_VACIO)
  const ordenados = [...vistos].sort((a, b) => a.localeCompare(b, 'es'))
  if (hayVacios) ordenados.push(VALOR_VACIO)
  return ordenados.map((v) => ({ valor: v, etiqueta: v }))
}
```

- [ ] **Step 4: Corre la prueba y comprueba que pasa**

```bash
cd pdc-app && npx vitest run src/lib/filtroLista.test.ts
```

Esperado: PASA, 8 tests.

- [ ] **Step 5: Escribe el adaptador a AG Grid**

Crea `pdc-app/src/components/FiltroLista.tsx`:

```tsx
import { useCallback, useId, useMemo } from 'react'
import { useGridFilter } from 'ag-grid-react'
import type { CustomFilterProps } from 'ag-grid-react'
import { ListaBuscable } from './ListaBuscable'
import { pasaFiltroLista, valoresDistintos } from '../lib/filtroLista'
import type { ModeloFiltroLista } from '../lib/filtroLista'

/**
 * El embudo de la cabecera: lista de los valores que hay en esa columna, con casillas y su propia
 * lupa. Es el equivalente del `changeType` de Programa General, escrito a mano porque el *set
 * filter* de AG Grid es Enterprise.
 *
 * Toda la sustancia —qué deja pasar, cómo se ordenan los valores— está en `lib/filtroLista.ts`.
 */
export function FiltroLista({ model, onModelChange, getValue, api }: CustomFilterProps<unknown, unknown, ModeloFiltroLista>) {
  const idBase = useId()

  // Los valores que ofrece la lista salen de las filas cargadas, no de un catálogo: si una columna
  // solo trae tres agrupaciones, ofrecer las cuarenta del proyecto sería mentir sobre la tabla.
  const opciones = useMemo(() => {
    const valores: unknown[] = []
    api.forEachNode((nodo) => { valores.push(getValue(nodo)) })
    return valoresDistintos(valores)
  }, [api, getValue])

  const doesFilterPass = useCallback(
    ({ node }: { node: Parameters<typeof getValue>[0] }) => pasaFiltroLista(model, getValue(node)),
    [model, getValue],
  )

  useGridFilter({ doesFilterPass })

  return (
    <div className="pdc-filtro-lista">
      <ListaBuscable
        opciones={opciones}
        modo="varias"
        seleccion={model?.valores ?? []}
        onSeleccion={(s) => onModelChange(s.length === 0 ? null : { valores: s })}
        idBase={idBase}
      />
    </div>
  )
}
```

- [ ] **Step 6: Compila y corre todo**

```bash
cd pdc-app && npx tsc --noEmit && npm test
```

Esperado: sin errores. Si `useGridFilter` se queja del tipo de `doesFilterPass`, ajusta la firma al tipo `IDoesFilterPassParams` que exporta `ag-grid-community` — **no** silencies con `any`.

- [ ] **Step 7: Commit**

```bash
git add pdc-app/src/lib/filtroLista.ts pdc-app/src/lib/filtroLista.test.ts pdc-app/src/components/FiltroLista.tsx
git commit -m "feat(pdc): filtro de columna con casillas y buscador, al estilo de Programa General"
```

---

### Task 8: Buscador rápido por tabla

**Files:**
- Modify: `pdc-app/src/lib/agGrid.ts` (añadir el helper al final)
- Modify: las páginas que aún no tengan caja de búsqueda

**Interfaces:**
- Produces: `<BuscadorTabla>` no; se hace con la API de AG Grid, sin componente nuevo. La página guarda un `useState<string>` y lo pasa como `quickFilterText` a `<AgGridReact>`.

**Aviso que ahorra trabajo duplicado:** tres pantallas **ya** tienen su caja y no deben recibir otra.
- `VisorPresupuesto` — `pdc-visor-buscar`, filtra el árbol antes de la grilla (`src/pages/VisorPresupuesto.tsx:223`).
- `ComparativoPresupuesto` — `busca` + `filtraPorTexto` sobre `rowData` (líneas 254 y 262).
- `PaquetesContratacion` — importa `filtraPorTexto` (línea 17).

En esas tres, **deja el buscador como está**: ya cumple lo acordado (busca sin tildes ni mayúsculas y se combina con lo demás). Cambiarlo a `quickFilterText` movería el filtrado del árbol a la grilla y en el Visor eso altera qué ramas se ven, que es otra cosa.

- [ ] **Step 1: Averigua exactamente qué tablas no tienen buscador**

```bash
cd pdc-app && grep -rn "type=\"search\"\|type='search'\|filtraPorTexto" src/pages src/components
```

Anota qué páginas **no** aparecen. Sobre esas actúa el resto de la tarea; hoy son `ImportarPresupuesto` (2 tablas), `MaestroInsumos` (3), `PlanFechas` (1) y `Seguimiento` (1).

- [ ] **Step 2: Añade el helper de estilo compartido**

Al final de `pdc-app/src/lib/agGrid.ts`:

```tsx
/**
 * Caja de búsqueda rápida de una tabla. Devuelve las props que se pasan a `<input>`; el texto lo
 * guarda la página y viaja a `<AgGridReact quickFilterText={...}>`.
 *
 * AG Grid busca sobre las columnas **visibles**: es lo acordado (2026-08-06). Buscar en columnas
 * ocultas encontraría filas donde el texto no se ve por ninguna parte.
 */
export function propsBuscador(etiqueta: string, testid: string) {
  return {
    type: 'search' as const,
    className: 'pdc-buscador-tabla',
    placeholder: 'Buscar…',
    'aria-label': etiqueta,
    'data-testid': testid,
  }
}
```

- [ ] **Step 3: Añádelo a `MaestroInsumos.tsx`, tabla por tabla**

Patrón, para cada una de sus tres grillas (una caja por grilla, con su propio estado):

```tsx
const [buscaCatalogo, setBuscaCatalogo] = useState('')

// … encima de la grilla:
<input
  {...propsBuscador('Buscar en el maestro de insumos', 'pdc-maestro-buscar')}
  value={buscaCatalogo}
  onChange={(e) => setBuscaCatalogo(e.target.value)}
/>

// … en la grilla:
<AgGridReact<MaestroInsumo> quickFilterText={buscaCatalogo} … />
```

Los testids: `pdc-maestro-buscar`, `pdc-maestro-buscar-pendientes`, `pdc-maestro-buscar-equipos`.

- [ ] **Step 4: Repite en `ImportarPresupuesto.tsx`, `PlanFechas.tsx` y `Seguimiento.tsx`**

Testids: `pdc-import-buscar-errores`, `pdc-import-buscar-versiones`, `pdc-plan-buscar`, `pdc-seg-buscar`.

- [ ] **Step 5: Compila y comprueba**

```bash
cd pdc-app && npx tsc --noEmit && npm test && npm run build
```

Esperado: sin errores.

- [ ] **Step 6: Commit**

```bash
git add pdc-app/src
git commit -m "feat(pdc): buscador rápido en las tablas que no lo tenían"
```

---

### Task 9: `BarraFiltros` — chips de lo activo y «Limpiar todo»

Es lo que evita el problema que el usuario señaló en el diseño: dos maneras de filtrar lo mismo y una tabla vacía sin explicación.

**Files:**
- Create: `pdc-app/src/components/BarraFiltros.tsx`
- Create: `pdc-app/src/lib/barraFiltros.ts`
- Test: `pdc-app/src/lib/barraFiltros.test.ts`
- Modify: `VisorPresupuesto.tsx`, `Seguimiento.tsx`, `PaquetesContratacion.tsx` (las tres con filtros propios encima de la tabla)

**Interfaces:**
- Consumes: `ModeloFiltroLista` (Task 7).
- Produces:
  - `interface Chip { id: string; texto: string }`
  - `chipsDeGrilla(modeloGrilla: Record<string, unknown>, nombres: Record<string, string>): Chip[]`
  - `<BarraFiltros chips={…} onQuitar={(id) => void} onLimpiar={() => void} />`

- [ ] **Step 1: Escribe la prueba que falla**

Crea `pdc-app/src/lib/barraFiltros.test.ts`:

```ts
import { describe, expect, it } from 'vitest'
import { chipsDeGrilla } from './barraFiltros'

const NOMBRES = { agrupacion: 'Agrupación', valorTotal: 'Valor total', descripcion: 'Descripción' }

describe('chipsDeGrilla', () => {
  it('sin filtros no hay chips', () => {
    expect(chipsDeGrilla({}, NOMBRES)).toEqual([])
  })

  it('un filtro de lista se lee con sus valores', () => {
    expect(chipsDeGrilla({ agrupacion: { valores: ['MAT', 'MOB'] } }, NOMBRES))
      .toEqual([{ id: 'agrupacion', texto: 'Agrupación: MAT, MOB' }])
  })

  it('más de tres valores se resumen en vez de desbordar la barra', () => {
    const modelo = { agrupacion: { valores: ['a', 'b', 'c', 'd', 'e'] } }
    expect(chipsDeGrilla(modelo, NOMBRES)[0].texto).toBe('Agrupación: 5 valores')
  })

  it('un filtro de texto se lee con su condición', () => {
    const modelo = { descripcion: { filterType: 'text', type: 'contains', filter: 'cemento' } }
    expect(chipsDeGrilla(modelo, NOMBRES)[0].texto).toBe('Descripción: contiene «cemento»')
  })

  it('un filtro de número se lee con su condición', () => {
    const modelo = { valorTotal: { filterType: 'number', type: 'greaterThan', filter: 1000 } }
    expect(chipsDeGrilla(modelo, NOMBRES)[0].texto).toBe('Valor total: mayor que 1.000')
  })

  it('una columna sin nombre declarado usa su propio id, no queda «undefined»', () => {
    expect(chipsDeGrilla({ rara: { valores: ['x'] } }, NOMBRES)[0].texto).toBe('rara: x')
  })
})
```

- [ ] **Step 2: Corre la prueba y comprueba que falla**

```bash
cd pdc-app && npx vitest run src/lib/barraFiltros.test.ts
```

Esperado: FALLA con «Failed to resolve import "./barraFiltros"».

- [ ] **Step 3: Implementa la lógica**

Crea `pdc-app/src/lib/barraFiltros.ts`:

```ts
/**
 * Traduce el modelo de filtros de AG Grid a texto que se pueda leer en un chip.
 *
 * Existe porque el módulo permite filtrar por dos vías —los controles de arriba y el embudo de
 * cada columna— y sin esto la tabla se queda vacía sin decir por qué.
 */

export interface Chip {
  id: string
  texto: string
}

/** Cuántos valores marcados se enumeran antes de resumir. Cuatro ya no caben en una barra. */
const MAXIMO_VALORES_ENUMERADOS = 3

const CONDICIONES: Record<string, string> = {
  contains: 'contiene', notContains: 'no contiene',
  equals: 'igual a', notEqual: 'distinto de',
  startsWith: 'empieza por', endsWith: 'termina en',
  blank: 'vacío', notBlank: 'no vacío',
  lessThan: 'menor que', greaterThan: 'mayor que',
  lessThanOrEqual: 'menor o igual que', greaterThanOrEqual: 'mayor o igual que',
  inRange: 'entre',
}

function textoDeCondicion(m: { type?: string; filter?: unknown; filterType?: string }): string {
  const condicion = CONDICIONES[m.type ?? ''] ?? (m.type ?? 'filtrado')
  if (m.filter === undefined || m.filter === null) return condicion
  const valor = typeof m.filter === 'number' ? m.filter.toLocaleString('es-CO') : `«${String(m.filter)}»`
  return `${condicion} ${valor}`
}

/**
 * Un chip por columna filtrada. `nombres` mapea id de columna → encabezado; una columna que no esté
 * en el mapa se anuncia con su id, que es feo pero cierto — nunca «undefined».
 */
export function chipsDeGrilla(
  modeloGrilla: Record<string, unknown>,
  nombres: Record<string, string>,
): Chip[] {
  return Object.entries(modeloGrilla).map(([id, modelo]) => {
    const nombre = nombres[id] ?? id
    const m = modelo as { valores?: string[] } & { type?: string; filter?: unknown; filterType?: string }
    if (Array.isArray(m.valores)) {
      const texto = m.valores.length > MAXIMO_VALORES_ENUMERADOS
        ? `${m.valores.length} valores`
        : m.valores.join(', ')
      return { id, texto: `${nombre}: ${texto}` }
    }
    return { id, texto: `${nombre}: ${textoDeCondicion(m)}` }
  })
}
```

- [ ] **Step 4: Corre la prueba y comprueba que pasa**

```bash
cd pdc-app && npx vitest run src/lib/barraFiltros.test.ts
```

Esperado: PASA, 6 tests.

- [ ] **Step 5: Escribe el componente**

Crea `pdc-app/src/components/BarraFiltros.tsx`:

```tsx
import type { Chip } from '../lib/barraFiltros'

export interface BarraFiltrosProps {
  chips: Chip[]
  onQuitar: (id: string) => void
  onLimpiar: () => void
  testid?: string
}

/**
 * Qué está filtrado ahora mismo, y cómo quitarlo.
 *
 * Cuando no hay nada filtrado no se pinta: una barra vacía permanente es cromo que roba altura a
 * la tabla, y el hueco que deja al aparecer es precisamente la señal de que algo cambió.
 */
export function BarraFiltros({ chips, onQuitar, onLimpiar, testid }: BarraFiltrosProps) {
  if (chips.length === 0) return null
  return (
    <div className="pdc-barra-filtros" data-testid={testid} role="status">
      <span className="pdc-barra-filtros-titulo">Filtrado por:</span>
      {chips.map((c) => (
        <button
          key={c.id}
          type="button"
          className="pdc-chip-filtro"
          aria-label={`Quitar filtro ${c.texto}`}
          onClick={() => onQuitar(c.id)}
        >
          {c.texto}
          <span className="pdc-chip-quitar" aria-hidden="true">×</span>
        </button>
      ))}
      <button type="button" className="pdc-barra-filtros-limpiar" onClick={onLimpiar}>
        Limpiar todo
      </button>
    </div>
  )
}
```

- [ ] **Step 6: Engánchalo en las tres páginas con filtros propios**

Patrón, para `PaquetesContratacion.tsx` (que tiene filtro de estado y de agrupación encima de la tabla):

```tsx
const [api, setApi] = useState<GridApi<InsumoPaquete> | null>(null)
const [modeloFiltros, setModeloFiltros] = useState<Record<string, unknown>>({})

const NOMBRES_COLUMNA = { agrupacion: 'Agrupación', descripcion: 'Descripción', valorTotal: 'Valor total' }

// Los filtros propios de la página se anuncian junto a los de columna: si no, «Limpiar todo»
// limpiaría solo la mitad y la tabla seguiría sin enseñar filas.
const chips = [
  ...(filtro !== 'todos' ? [{ id: 'pagina:estado', texto: `Estado: ${etiquetaFiltro(filtro)}` }] : []),
  ...(agrupacion !== '' ? [{ id: 'pagina:agrupacion', texto: `Agrupación: ${agrupacion}` }] : []),
  ...chipsDeGrilla(modeloFiltros, NOMBRES_COLUMNA),
]

const quitar = (id: string) => {
  if (id === 'pagina:estado') { setFiltro('todos'); return }
  if (id === 'pagina:agrupacion') { setAgrupacion(''); return }
  api?.setColumnFilterModel(id, null).then(() => api.onFilterChanged())
}

const limpiar = () => {
  setFiltro('todos')
  setAgrupacion('')
  void api?.setFilterModel(null)
}

// … en el JSX, entre los controles y la tabla:
<BarraFiltros chips={chips} onQuitar={quitar} onLimpiar={limpiar} testid="pdc-paq-barra-filtros" />

// … en la grilla:
<AgGridReact<InsumoPaquete>
  onGridReady={(p) => setApi(p.api)}
  onFilterChanged={(p) => setModeloFiltros(p.api.getFilterModel())}
  …
/>
```

Repítelo en `VisorPresupuesto.tsx` (tipo de insumo, unidad, texto) y `Seguimiento.tsx` (frente, estado). En el Visor, el botón «Limpiar filtros» que ya existe (línea ~271) **se retira**: lo sustituye este, que además limpia los de columna.

- [ ] **Step 7: Compila y comprueba**

```bash
cd pdc-app && npx tsc --noEmit && npm test && npm run build
```

Esperado: sin errores. Si `setColumnFilterModel` no existe en esta versión, usa `api.setFilterModel({...modeloFiltros, [id]: null})`.

- [ ] **Step 8: Commit**

```bash
git add pdc-app/src
git commit -m "feat(pdc): barra de filtros activos con chips y limpiar todo"
```

---

### Task 10: Skin y verificación en navegador

**Files:**
- Modify: `pdc-app/src/styles.css`

**Interfaces:** ninguna nueva. Es la tarea de cierre.

- [ ] **Step 1: Escribe el skin**

Añade a `pdc-app/src/styles.css`, junto al resto de controles (cerca de `.pdc-selector`, línea ~243). **Sin hex**: solo tokens que ya existen en ese archivo (`--pdc-surface`, `--pdc-ink`, `--pdc-ink-muted`, `--pdc-border-control`, `--pdc-radio-control`, `--pdc-radio-caja`, `--pdc-accent`, `--pdc-focus`, `--pdc-control-pad`, `--pdc-fs-sm`, `--pdc-fs-md`).

```css
/* Selector buscable: sustituye a <select> en todo el módulo. El botón imita al nativo para que
   convivan mientras dure la migración; el popup es lo único nuevo que se ve. */
.pdc-selector-caja { position: relative; display: inline-flex; }
.pdc-selector-boton {
  display: inline-flex; align-items: center; gap: 8px;
  background: var(--pdc-surface); color: var(--pdc-ink);
  border: 1px solid var(--pdc-border-control); border-radius: var(--pdc-radio-control);
  padding: var(--pdc-control-pad); cursor: pointer; text-align: left;
}
.pdc-selector-boton:disabled { opacity: 0.5; cursor: not-allowed; }
.pdc-selector-boton:focus-visible { outline: 2px solid var(--pdc-focus); outline-offset: 2px; }
.pdc-selector-valor.es-vacio { color: var(--pdc-ink-muted); }
.pdc-selector-flecha {
  width: 0; height: 0; margin-left: auto;
  border-left: 4px solid transparent; border-right: 4px solid transparent;
  border-top: 5px solid currentColor;
}
.pdc-selector-popup {
  position: absolute; top: calc(100% + 4px); left: 0; z-index: 20; min-width: 100%;
  background: var(--pdc-surface); border: 1px solid var(--pdc-border-control);
  border-radius: var(--pdc-radio-caja);
}

/* Lista buscable: la misma dentro del popup del selector y dentro del embudo de la cabecera. */
.pdc-lista { display: flex; flex-direction: column; gap: 6px; padding: 8px; min-width: 220px; }
.pdc-lista-buscar {
  background: var(--pdc-surface); color: var(--pdc-ink);
  border: 1px solid var(--pdc-border-control); border-radius: var(--pdc-radio-control);
  padding: var(--pdc-control-pad);
}
.pdc-lista-masa { display: flex; gap: 6px; font-size: var(--pdc-fs-sm); }
.pdc-lista-opciones {
  list-style: none; margin: 0; padding: 0; max-height: 280px; overflow-y: auto;
}
.pdc-lista-op {
  display: flex; align-items: center; gap: 8px;
  padding: 4px 8px; cursor: pointer; border-radius: var(--pdc-radio-control);
  font-size: var(--pdc-fs-md);
}
.pdc-lista-op.es-resaltada { background: color-mix(in srgb, var(--pdc-accent) 22%, transparent); }
.pdc-lista-vacia { padding: 8px; color: var(--pdc-ink-muted); font-size: var(--pdc-fs-sm); }

/* Buscador rápido de tabla: mismo aspecto que el que ya tenía el Visor. */
.pdc-buscador-tabla {
  background: var(--pdc-surface); color: var(--pdc-ink);
  border: 1px solid var(--pdc-border-control); border-radius: var(--pdc-radio-control);
  padding: var(--pdc-control-pad); min-width: 240px;
}

/* Barra de filtros activos. */
.pdc-barra-filtros {
  display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
  margin: 8px 0; font-size: var(--pdc-fs-sm);
}
.pdc-barra-filtros-titulo { color: var(--pdc-ink-muted); }
.pdc-chip-filtro {
  display: inline-flex; align-items: center; gap: 6px;
  background: color-mix(in srgb, var(--pdc-accent) 18%, transparent);
  color: var(--pdc-ink); border: 1px solid var(--pdc-border-control);
  border-radius: 999px; padding: 2px 10px; cursor: pointer;
}
.pdc-chip-quitar { opacity: 0.7; }
.pdc-barra-filtros-limpiar { background: none; border: none; color: var(--pdc-ink); text-decoration: underline; cursor: pointer; }

/* Embudo de la cabecera: el mismo lenguaje que el changeType de Programa General — pequeño y
   sutil, no un control de 32 px colgando de una cabecera de 32 (el defecto C-48 de PS). */
.pdc-grid .ag-header-icon { color: var(--pdc-ink-muted); }
.pdc-grid .ag-header-cell-filtered .ag-header-icon { color: var(--pdc-accent); }
```

- [ ] **Step 2: Publica el bundle y levanta el stack**

```bash
cd pdc-app && npm run build
cd .. && docker compose ps
```

Esperado: `app` y `db` arriba. Si no, `docker compose up -d db app`.

- [ ] **Step 3: Verifica en el navegador integrado, a 1180×820 y en dark**

Abre `preview_start` con `{url: "http://localhost:8081/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E"}`, redimensiona a 1180×820 con `colorScheme: 'dark'` y **recorre las ocho páginas** (Importar, Presupuesto, Comparar, Maestro, Paquetes, Plan, Pasos, Seguimiento). En cada una:

1. Abre un `Selector` y comprueba que la lupa aparece solo si hay ≥8 opciones.
2. Abre el embudo de una columna categórica: la lista debe traer los valores de esa columna.
3. Marca dos valores → la tabla filtra y aparece el chip.
4. Escribe en el buscador rápido → filtra combinándose con el embudo.
5. «Limpiar todo» → vuelve todo, chips incluidos.
6. `read_console_messages`: **sin errores**.
7. Confirma que **no hay scroll horizontal**: `document.querySelector('.pdc-grid .ag-body-horizontal-scroll-viewport').scrollWidth <= clientWidth`. El icono de filtro añade ancho a la cabecera; `columnasQueCaben` estima en 90 px toda columna sin `minWidth`, así que si alguna tabla desborda, empieza por ahí (`lib/agGrid.ts:201`).

Guarda una captura por página en `docs/design-system/evidence/pdc-filtros-2026-08-06/`.

- [ ] **Step 4: Corre toda la suite de navegador del PDC**

```bash
npx playwright test tests/browser/pdc-v2-*.spec.mjs --workers=1
```

Esperado: PASA. Cualquier fallo aquí es una regresión real de esta tarea, no un test que haya que ajustar.

- [ ] **Step 5: Corre la suite estática del design system**

```bash
npm run test:design-system:static
```

Esperado: PASA. Vigila el contrato de tokens: si reprocha un color, es que se coló un hex.

- [ ] **Step 6: Commit**

```bash
git add pdc-app/src/styles.css docs/design-system/evidence/pdc-filtros-2026-08-06
git commit -m "feat(pdc): skin de selectores, embudos y chips con tokens del design system"
```

---

## Autorrevisión de este plan

**Cobertura del spec:** las cinco decisiones están cubiertas — changetype como filtro de cabecera (Tasks 6-7), filtros de página que conviven con chips y «Limpiar todo» (Task 9), buscador sobre columnas visibles (Task 8), un solo control con umbral de 8 (Tasks 2-5), sin persistencia (no hay ningún paso que escriba en `localStorage`). El skin y la validación a 1180×820 en dark son la Task 10.

**Desviación consciente del spec:** el spec pedía crear `lib/coincide.ts`; el plan añade `coincide` a `lib/texto.ts`, que ya tiene `normaliza` y `filtraPorTexto`. Un archivo nuevo sería un duplicado de código que ya existe y funciona.

**Hallazgo posterior al spec, que le añade coste real:** ~20 llamadas `selectOption()` en 10 specs de Playwright apuntan a los `<select>` que se sustituyen. La Task 4 crea el ayudante `elegirEnSelector` y la Task 5 migra las llamadas, spec por spec. Y `<select>` son **31**, no 15: el spec contó por página, no por elemento.

**Dependencia circular entre tareas:** la Task 6 importa `FiltroLista`, que crea la Task 7. Si se ejecutan en orden, haz la 7 antes que la 6, o crea el archivo con el esqueleto y vuelve (queda dicho en el Step 5 de la Task 6).
