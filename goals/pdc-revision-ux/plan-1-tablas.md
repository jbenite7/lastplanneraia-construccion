---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-07-28
areas: [pdc]
fuente: goals/pdc-revision-ux/plan-1-tablas.md
resumen: Que las tablas del módulo dejen de recortar texto, se ajusten al contenido, se editen con un solo clic, y que las pantallas señalen el trabajo pendiente en vez…
---

# Plan 1 — Arreglos de tabla — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que las tablas del módulo dejen de recortar texto, se ajusten al contenido, se editen con un solo clic, y que las pantallas señalen el trabajo pendiente en vez del hecho. Cumple los hechos **f01–f09** de `facts.md`.

**Architecture:** Hoy hay **9 tablas AG Grid repartidas en 6 archivos y ningún sitio común**: el tema `pdcTheme` está duplicado byte a byte seis veces y la función `moneda()` seis veces con divergencias reales (en el visor y el comparador un valor 0 se muestra vacío; en maestro, paquetes y plan como `$ 0`). Por eso la Task 1 no es un arreglo visible: es crear `src/lib/agGrid.ts` como única fuente de tema, `defaultColDef` y formatos. Sin ese paso, cada hecho de este plan habría que repetirlo nueve veces y la divergencia del cero seguiría viva.

**Tech Stack:** React + TypeScript + Vite, **AG Grid Community 36.0.2** (prohibido Enterprise), Vitest. El bundle se copia a mano a `lps-aia-pdc/public/pdc-app/assets/`.

## Global Constraints

- **Repo:** `/Volumes/Crucial X6/Developer/plan-de-compras`, rama a crear desde `main`.
- **NUNCA** trabajar en `/Volumes/Crucial X6/Developer/lps-aia` — es de otras sesiones.
- **NUNCA** `npm run sync` — apunta a ese worktree ajeno. Copiar el bundle a mano.
- **AG Grid Community, nunca Enterprise.** Ojo: varias APIs de columna están **deprecadas desde la v32.2** (`checkboxSelection`, `headerCheckboxSelection`, `suppressRowClickSelection`). Verificar contra los `.d.ts` instalados antes de usar cualquier API, no contra la memoria.
- `npm run test` y `npm run build` en verde. Línea base: **128 tests**.
- Nada de `any` ni `@ts-ignore`: si TypeScript protesta, se arregla el tipo.
- Cada tarea deja el módulo funcionando: no se rompe una pantalla para arreglar otra.

## File Structure

- Crear `src/lib/agGrid.ts` — tema, `defaultColDef`, formatos de moneda y número, tipos de columna.
- Crear `src/lib/agGrid.test.ts` — Vitest de lo anterior.
- Modificar los 6 archivos de página: `VisorPresupuesto.tsx`, `ImportarPresupuesto.tsx`, `MaestroInsumos.tsx`, `PaquetesContratacion.tsx`, `ComparativoPresupuesto.tsx`, `PlanFechas.tsx`.
- Modificar `src/lib/planFechas.ts` y `src/pages/PaquetesContratacion.tsx` para el filtro inicial y el botón único.

---

### Task 1: El sitio común de las tablas

**Files:**
- Create: `src/lib/agGrid.ts`
- Create: `src/lib/agGrid.test.ts`

**Interfaces:**
- Produces: `pdcTheme`, `defaultColDef`, `columnaMoneda(field, headerName)`, `columnaNumero(...)`, `columnaTexto(...)`, `moneda(v)`.
- Consumes: nada.

- [ ] **Step 1: Escribir los tests que fallan**

En `src/lib/agGrid.test.ts`. El primero es el que importa: hoy el cero se muestra distinto según la pantalla, y eso es un error de datos a la vista del usuario.

```ts
import { describe, expect, it } from 'vitest'
import { moneda, defaultColDef, columnaMoneda, columnaTexto } from './agGrid'

describe('moneda', () => {
  it('un cero se muestra como $ 0, no como celda vacía', () => {
    // Hoy el visor y el comparador lo dejan en blanco y las otras tres pantallas lo muestran.
    // Una celda vacía significa «no hay dato»; un cero significa «vale cero». No son lo mismo.
    expect(moneda(0)).toBe('$ 0')
  })

  it('un valor ausente sí deja la celda vacía', () => {
    expect(moneda(null)).toBe('')
    expect(moneda(undefined)).toBe('')
  })

  it('usa separador de miles colombiano', () => {
    expect(moneda(2109795800)).toContain('.')
  })
})

describe('defaultColDef', () => {
  it('las columnas se pueden redimensionar a mano', () => {
    expect(defaultColDef.resizable).toBe(true)
  })
})

describe('columnaMoneda', () => {
  it('el dinero nunca parte en dos renglones', () => {
    // Un importe cortado en dos líneas se lee mal y descuadra la altura de la fila.
    expect(columnaMoneda('valorTotal', 'Valor total').wrapText).toBeFalsy()
  })
})

describe('columnaTexto', () => {
  it('el texto largo sí envuelve y crece de alto', () => {
    const c = columnaTexto('descripcion', 'Descripción')
    expect(c.wrapText).toBe(true)
    expect(c.autoHeight).toBe(true)
  })
})
```

- [ ] **Step 2: Ejecutar los tests para verlos fallar**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && npm run test 2>&1 | tail -12
```

Esperado: fallan por módulo inexistente.

- [ ] **Step 3: Implementar el módulo común**

Crear `src/lib/agGrid.ts`. Copiar el `pdcTheme` tal cual está hoy (los mismos cuatro valores, para no cambiar el aspecto en esta tarea) y añadir lo nuevo:

```ts
import { themeQuartz } from 'ag-grid-community'
import type { ColDef } from 'ag-grid-community'

/** Tema único del módulo. Estaba copiado byte a byte en los seis archivos de página. */
export const pdcTheme = themeQuartz.withParams({
  backgroundColor: '#1c1c1e',
  foregroundColor: '#f4f1ea',
  accentColor: '#69b578',
  headerBackgroundColor: '#1a3c2a',
})

/**
 * Dinero. Un 0 se muestra como «$ 0» y solo la ausencia deja la celda vacía: hasta ahora dos
 * pantallas mostraban en blanco un valor que sí existía y valía cero, que es información distinta.
 */
export function moneda(v: number | null | undefined): string {
  if (v === null || v === undefined) return ''
  return `$ ${Number(v).toLocaleString('es-CO')}`
}

export const defaultColDef: ColDef = { resizable: true, sortable: true }

/**
 * Columnas de cifra: nunca envuelven. Un importe partido en dos renglones se lee peor y descuadra
 * la altura de la fila. Si no cabe, la columna se ensancha — para eso está el autoSizeStrategy.
 */
export function columnaMoneda(field: string, headerName: string): ColDef {
  return { field, headerName, type: 'rightAligned', valueFormatter: (p) => moneda(p.value), wrapText: false }
}

/** Columnas de texto largo: envuelven y la fila crece. */
export function columnaTexto(field: string, headerName: string): ColDef {
  return { field, headerName, wrapText: true, autoHeight: true, flex: 1 }
}
```

- [ ] **Step 4: Ejecutar los tests para verlos pasar**

```bash
npm run test 2>&1 | tail -6
```

Esperado: 128 + los nuevos, 0 fallos.

- [ ] **Step 5: Commit**

```bash
git add src/lib/agGrid.ts src/lib/agGrid.test.ts
git commit -m "feat(pdc): un solo sitio para el tema y los formatos de las tablas"
```

---

### Task 2: Adoptar el sitio común en las seis páginas

**Files:** los 6 archivos de página.

**Interfaces:** Consumes lo de la Task 1. Produces: cero duplicados de `pdcTheme` y `moneda`.

- [ ] **Step 1: Comprobar el estado de partida**

```bash
grep -c "themeQuartz.withParams" src/pages/*.tsx | grep -v ":0"
grep -rn "toLocaleString('es-CO')" src/pages/ | wc -l
```

Anota los números: son los duplicados que deben quedar en cero al final.

- [ ] **Step 2: Sustituir en cada página**

En los seis archivos: borrar la definición local de `pdcTheme` y la función `moneda` local, e importar de `../lib/agGrid`. **Mantener intacto el `ModuleRegistry.registerModules` de cada página** — cada una registra un juego distinto a propósito, para no arrastrar el bundle completo (~1.3 MB).

**Cuidado con el cambio de comportamiento:** en `VisorPresupuesto.tsx` y `ComparativoPresupuesto.tsx` la función local devolvía `''` para el 0. Al adoptar la común pasarán a mostrar `$ 0`. **Es el arreglo, no una regresión** — pero verifica a ojo que ninguna columna dependiera de esa cadena vacía para maquetar.

- [ ] **Step 3: Verificar que no quedan duplicados**

```bash
grep -c "themeQuartz.withParams" src/pages/*.tsx | grep -v ":0" || echo "  cero duplicados de tema ✅"
npm run test 2>&1 | tail -4
npm run build 2>&1 | tail -3
```

- [ ] **Step 4: Commit**

```bash
git add src/pages/
git commit -m "refactor(pdc): las seis páginas usan el tema y los formatos comunes"
```

---

### Task 3: Ajuste de línea y ancho automático (f01, f02, f03)

**Files:** los 6 archivos de página, `src/lib/agGrid.ts`.

- [ ] **Step 1: Escribir el test que falla**

En `src/lib/agGrid.test.ts`:

```ts
describe('autoSizeStrategy', () => {
  it('el módulo expone una estrategia de ancho que se ajusta al contenido', () => {
    expect(autoSizeStrategy).toBeDefined()
    expect(autoSizeStrategy.type).toBe('fitCellContents')
  })
})
```

- [ ] **Step 2: Ejecutar el test para verlo fallar**

- [ ] **Step 3: Implementar**

Añadir en `src/lib/agGrid.ts`:

```ts
import type { SizeColumnsToContentStrategy } from 'ag-grid-community'

/**
 * El ancho sale del contenido, no de un número escrito a mano. Las columnas de texto largo llevan
 * `flex` y quedan fuera de esta medición: envuelven en vez de ensancharse.
 */
export const autoSizeStrategy: SizeColumnsToContentStrategy = { type: 'fitCellContents' }
```

**Verifica el nombre del tipo contra los `.d.ts` instalados de la 36.0.2 antes de escribirlo.**

Después, en las nueve tablas: pasar `autoSizeStrategy={autoSizeStrategy}` al `<AgGridReact>`, sustituir los `width:` fijos de las columnas de cifra/fecha/unidad por las funciones `columnaMoneda`/`columnaNumero`, y las de texto largo por `columnaTexto`.

**Reparto por columna, según lo decidido:**
- **Nunca envuelven:** código, unidad, cantidad, valor unitario, valor total, días, fechas, estado.
- **Sí envuelven:** descripción, nombre de insumo, nombre de paquete, nombre de archivo, agrupación, destino, sugerencia, frente, motivo de error.

- [ ] **Step 4: Verificar en el navegador**

Levantar el preview contra `http://localhost:8091/plan-compras` y comprobar en el historial de versiones que ya no aparece «102 DAPORTO RIONEGRO PI_Version…» recortado. **El login lo hace el usuario: no introducir credenciales.**

- [ ] **Step 5: Tests, build y commit**

```bash
npm run test 2>&1 | tail -4 && npm run build 2>&1 | tail -3
git add src/lib/agGrid.ts src/lib/agGrid.test.ts src/pages/
git commit -m "feat(pdc): las tablas ajustan el ancho al contenido y envuelven el texto largo"
```

---

### Task 4: Un clic para editar (f04, f05)

**Files:** `src/pages/PlanFechas.tsx`.

**Contexto:** en todo el módulo hay **una sola columna editable** — «Responsable» en el Plan. El alcance es mucho menor de lo que parecía. El conflicto real: el clic sencillo ya abre el detalle de la fila.

- [ ] **Step 1: Escribir el test que falla**

La decisión fue «depende de dónde hagas clic»: en columna editable edita, en el resto abre el detalle. Eso es lógica pura y va en `src/lib/planFechas.ts`:

```ts
describe('accionDeClic', () => {
  it('en la columna de responsable, el clic edita', () => {
    expect(accionDeClic('responsable')).toBe('editar')
  })
  it('en cualquier otra columna, el clic abre el detalle', () => {
    expect(accionDeClic('nombre')).toBe('detalle')
    expect(accionDeClic('estado')).toBe('detalle')
  })
  it('sin columna identificada, abre el detalle: es lo que no destruye nada', () => {
    expect(accionDeClic(undefined)).toBe('detalle')
  })
})
```

- [ ] **Step 2: Ejecutar el test para verlo fallar**

- [ ] **Step 3: Implementar**

En `src/lib/planFechas.ts`:

```ts
/** Qué hace un clic según la columna. Editar solo donde se puede editar; el resto abre el detalle. */
export function accionDeClic(colId: string | undefined): 'editar' | 'detalle' {
  return colId === 'responsable' ? 'editar' : 'detalle'
}
```

En `PlanFechas.tsx`: poner `singleClickEdit` en el grid y, en `onCellClicked`, consultar `accionDeClic(e.column?.getColId())` para decidir si abre el detalle. **La columna «Responsable» ya no debe abrir el detalle al clicarse.**

- [ ] **Step 4: Comprobar que no se rompió lo de esta mañana**

El fallo de la segunda edición perdida se arregló hace poco (`93162bd`). Verifica a mano en el navegador: asignar a alguien, y **sin recargar**, cambiarlo por otra persona. Debe guardarse la segunda.

- [ ] **Step 5: Tests, build y commit**

---

### Task 5: Que se vea el trabajo pendiente (f06)

**Files:** `src/pages/PaquetesContratacion.tsx`, `src/lib/paquetes*.ts`.

- [ ] **Step 1: Escribir el test que falla**

```ts
describe('filtroInicial', () => {
  it('abre en «sin asignar» cuando queda algo pendiente', () => {
    expect(filtroInicial({ sinAsignar: 1, total: 396 })).toBe('sin_asignar')
  })
  it('abre en «todos» cuando ya no queda nada', () => {
    expect(filtroInicial({ sinAsignar: 0, total: 396 })).toBe('todos')
  })
  it('sin datos todavía, no adivina: se queda en todos', () => {
    expect(filtroInicial(null)).toBe('todos')
  })
})
```

- [ ] **Step 2–3: Verlo fallar, implementar**

El filtro inicial se decide cuando llega el resumen, no en el primer render (que aún no sabe cuántos faltan). Cuidado con no pisar una elección que el usuario ya haya hecho a mano.

- [ ] **Step 4: Tests, build y commit**

---

### Task 6: Un solo botón que propone (f07, f08)

**Files:** `src/pages/PaquetesContratacion.tsx`.

**Decisión tomada:** un solo botón que **solo propone**; nada se guarda hasta que la persona confirma. Desaparece «Auto-asignar lo seguro» como acción separada.

- [ ] **Step 1: Escribir el test que falla**

```ts
describe('botón único de propuestas', () => {
  it('proponer no escribe: no llama a ningún endpoint de asignación', () => {
    // El contrato de esta tarea: proponer es de solo lectura.
  })
})
```

Ajusta el test a la forma real del componente; lo que no puede faltar es la afirmación de que **proponer no escribe**.

- [ ] **Step 2–3: Verlo fallar, implementar**

Retirar el botón de auto-asignación de la barra. El botón único llama a `GET /paquetes/sugerencias` y muestra las propuestas en la columna «Sugerencia»; el botón «Aceptar N sugeridas» que ya existe es el que escribe.

**Nombre del botón:** algo que diga lo que hace sin jerga. «Proponer destinos» describe la acción; evita «sembrar» e «iteración», que son vocabulario interno.

**No borres el endpoint de auto-asignación en el backend** — queda sin usar desde la interfaz, y retirarlo es otra decisión.

- [ ] **Step 4: Tests, build y commit**

---

### Task 7: Decir qué hace «Recalcular» (f09)

**Files:** `src/pages/PlanFechas.tsx`, `src/styles.css`.

- [ ] **Step 1: Implementar**

Una línea de texto fija junto al botón. El contenido tiene que ser **verdad verificable**, no tranquilizador: conserva responsables, amarres y el avance ya registrado; solo recalcula las fechas contra el cronograma vigente.

Texto propuesto: «Actualiza las fechas contra el cronograma. Conserva responsables, amarres y el avance registrado.»

- [ ] **Step 2: Verificar que sigue siendo verdad**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php 2>&1 | grep -c "^PASS:"
```

Hay tests que vigilan justo esa promesa. Si alguno falla, **el texto es mentira y hay que arreglar el código, no el texto**.

- [ ] **Step 3: Commit**

---

### Task 8: Republicar el bundle y verificar

- [ ] **Step 1: Build y copia a mano**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras"
npm run test 2>&1 | tail -4
npm run build 2>&1 | tail -3
cp dist/assets/pdc.js dist/assets/pdc.css "/Volumes/Crucial X6/Developer/lps-aia-pdc/public/pdc-app/assets/"
```

- [ ] **Step 2: Verificación visual**

Abrir el preview en `http://localhost:8091/plan-compras` y recorrer las seis pantallas. **El login lo hace el usuario.** Comprobar: nada recortado, un clic edita el responsable, Paquetes abre en «Sin asignar», un solo botón de propuestas, y el texto junto a «Recalcular».

- [ ] **Step 3: Commit del bundle**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git add public/pdc-app/assets/pdc.js public/pdc-app/assets/pdc.css
git commit -m "chore(pdc): republica el bundle con los arreglos de tabla"
```
