---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-07-28
areas: [pdc]
fuente: goals/pdc-tanda34-pulido/plan.md
resumen: For agentic workers. Repo /Volumes/Crucial X6/Developer/plan-de-compras, rama pdc-revision-ux. Bundle al worktree /Volumes/Crucial…
---

# Plan — Tandas 3 y 4: la primera impresión y el pulido

For agentic workers. Repo `/Volumes/Crucial X6/Developer/plan-de-compras`, rama `pdc-revision-ux`.
Bundle al worktree `/Volumes/Crucial X6/Developer/lps-aia-pdc/public/pdc-app/`. App en `:8091`,
proyecto Da Porto. Hechos: `facts.md`.

## Enfoque

Once cambios, ninguno con lógica de negocio nueva. Dos son transversales (plurales, vacíos de tabla)
y se hacen una vez en `src/lib/`; el resto son locales a una pantalla. Solo SPA.

Orden: primero lo transversal (1–2), porque toca los mismos ficheros que todo lo demás y hacerlo
después obligaría a repasar; luego pantalla por pantalla.

---

## Tarea 1 — Plurales y separadores, de una vez (f10, f11)

**Archivos:** `src/lib/texto.ts` (nuevo), `src/lib/texto.test.ts` (nuevo), las 6 páginas.

- [ ] Test primero: `plural(1, 'paquete')` → `'1 paquete'`; `plural(11, 'paquete')` → `'11 paquetes'`;
      `plural(1343, 'fila')` → `'1.343 filas'`; plurales irregulares por parámetro
      (`plural(2, 'vínculo', 'vínculos')`); el 0 va en plural (`'0 paquetes'`).
- [ ] Implementar `plural(n, singular, pluralExplicito?)` usando `toLocaleString('es-CO')`.
- [ ] Sustituir los 24 sitios con `(s)`. Lista exacta:
      `ImportarPresupuesto.tsx:294`, `MaestroInsumos.tsx:170,269`,
      `PaquetesContratacion.tsx:224,238,250,262,355,362,379,447`,
      `PlanFechas.tsx:212,353,354,467,468`, `VisorPresupuesto.tsx:244`,
      `PaquetesAsistente.tsx:212,285` y los que aparezcan al buscar `(s)` de nuevo al terminar.
- [ ] Verificar: `grep -rn "(s)" src/pages src/lib | grep -v test` no devuelve plurales.

## Tarea 2 — Vacíos de tabla en español y con sentido (f08, f09)

**Archivos:** `src/lib/agGrid.ts`, las páginas con tabla.

- [ ] `vacioTabla(mensaje)` que devuelva el `overlayNoRowsTemplate` con el estilo del módulo.
- [ ] Un mensaje por tabla, diciendo qué significa el vacío. Borradores:
      - Maestro/pendientes: «Nada pendiente: todos los insumos del presupuesto activo ya están en el maestro.»
      - Maestro/catálogo: «Ningún insumo coincide con la búsqueda.»
      - Visor: «Este presupuesto no tiene filas que mostrar con los filtros puestos.»
      - Comparador: «Las dos versiones no tienen diferencias en este eje.»
      - Paquetes: «No queda ningún insumo en este filtro.»
      - Plan: «Todavía no hay paquetes con plan calculado.» (ya existe fuera de la tabla; unificar)
      - Importar/errores e historial: los suyos.
- [ ] Verificar: recorrer las pantallas y comprobar que no aparece «No Rows To Show».

## Tarea 3 — Cargador de Excel propio (f01, f02, f03)

**Archivos:** `src/pages/ImportarPresupuesto.tsx`, `src/styles.css`.

- [ ] Ocultar el `<input type="file">` sin quitarlo del DOM ni del árbol de accesibilidad
      (`className="pdc-sr-only"`, no `display:none`) — **el e2e lo usa con `setInputFiles` y el
      testid `pdc-import-file` debe seguir funcionando**.
- [ ] `<label>` asociada al input, con aspecto de botón: «Elegir archivo…».
- [ ] Zona de arrastre alrededor: `onDragOver`/`onDragLeave`/`onDrop`, con estado visual al arrastrar
      encima; en `onDrop` tomar `e.dataTransfer.files[0]` y llamar al mismo `onArchivo`.
- [ ] Nombre del archivo elegido a la vista, o «Ningún archivo elegido» en español.
- [ ] Verificar: `npm run test`, e2e `pdc-v2-import` y `pdc-v2-visor` (los dos suben ficheros).

## Tarea 4 — Maestro abre por donde hay trabajo (f04, f05)

**Archivos:** `src/lib/maestro.ts` o el que tenga el estado, `src/pages/MaestroInsumos.tsx`, su test.

- [ ] Test primero: `pestanaInicialMaestro(pendientes)` → `'catalogo'` con 0, `'pendientes'` con >0.
- [ ] Aplicarlo al montar, cuando llega el resumen — no en el `useState` inicial, que corre antes de
      saber cuántos pendientes hay.
- [ ] ⚠ No debe pisar la pestaña que el usuario haya elegido a mano después.
- [ ] Verificar: e2e `pdc-v2-maestro` (abre la pantalla y espera la cobertura).

## Tarea 5 — Paquetes: estado de cierre (f06, f07)

**Archivos:** `src/lib/paquetesState.ts` + test, `src/pages/PaquetesContratacion.tsx`, `src/styles.css`.

- [ ] Test primero: `estaCerradoPorValor(resumen)` → true cuando `coberturaValor === 100`.
- [ ] Mensaje arriba: «Por valor está todo asignado. Queda N insumo sin destino, de $ X.»
- [ ] Las tres barras de controles dentro de un `<details>` cuyo `open` arranca en
      `!estaCerradoPorValor(...)`. Resumen: «Seguir asignando».
- [ ] ⚠ El e2e `pdc-v2-paquetes` usa `pdc-paq-crear-nombre`, `pdc-paq-filtro` y `pdc-paq-asignar`,
      que quedarían dentro del `<details>`. Playwright no interactúa con lo que está colapsado:
      revisar y, si hace falta, abrir el `<details>` en el spec — o dejarlo abierto mientras la
      cobertura por valor no sea 100 (que es el caso del sandbox).
- [ ] Verificar: `npm run test` + e2e.

## Tarea 6 — Las tres cifras de insumos (f12)

**Archivos:** `ImportarPresupuesto.tsx`, `MaestroInsumos.tsx`.

- [ ] Historial (820): cabecera «Insumos» → título ayuda «filas de insumo del presupuesto».
- [ ] Maestro, cobertura (396): «insumos distintos de este presupuesto».
- [ ] Maestro, catálogo (3.079): «insumos de toda la empresa».
- [ ] Texto pequeño junto al número, no tooltip.

## Tarea 7 — Badges que no mienten (f13, f14)

**Archivos:** `src/lib/paquetesState.ts` + test, `src/pages/PaquetesContratacion.tsx`.

- [ ] Test primero: `muestraTipoNegociacion(modalidad)` → false para `no_contratable` y
      `consumo_directo`, true para `contrato` y `orden_compra` y para modalidad ausente.
- [ ] Aplicarlo en la lista «Paquetes con insumos»: sin badge de tipo, con badge de modalidad.
- [ ] ⚠ El e2e `pdc-v2-modalidades` comprueba que la modalidad se ve en el resumen: no tocarlo.

## Tarea 8 — «Retirar» con red (f15, f16, f17)

**Archivos:** `src/pages/MaestroInsumos.tsx`, `src/styles.css`.

- [ ] Cabecera «Acción» en la columna (hoy vacía).
- [ ] Clic en «Retirar» abre un panel de confirmación (patrón `pdc-panel`, como desamarrar) con el
      nombre del insumo y el impacto.
- [ ] El impacto real (`revertidos`) hoy solo se conoce **después** de retirar: el mensaje de
      confirmación debe decir lo que se sabe antes («se revertirán sus vínculos automáticos en todos
      los proyectos») sin inventar un número.
- [ ] «Reactivar» no necesita confirmación: no destruye nada.

## Tarea 9 — Buscadores (f18, f19, f20)

**Archivos:** `PaquetesContratacion.tsx`, `PlanFechas.tsx`, `ComparativoPresupuesto.tsx`, `styles.css`.

- [ ] Mismo patrón que el buscador del catálogo: input con placeholder, filtrado en cliente,
      sin distinguir mayúsculas ni acentos.
- [ ] Poner una función `filtraPorTexto` en `src/lib/texto.ts` con test (acentos incluidos:
      buscar «carpinteria» encuentra «CARPINTERÍA»).

## Tarea 10 — Nota de «Recalcular» plegable (f21)

**Archivos:** `src/pages/PlanFechas.tsx`, `src/styles.css`.

- [ ] `<details>` con `<summary>¿qué conserva?</summary>` y el texto actual **sin cambiar una coma**
      (hay tests PHP que vigilan esa promesa; el texto es la garantía).
- [ ] Mantener `data-testid="pdc-plan-recalcular-nota"` en el elemento del texto.

## Tarea 11 — «Sin frente» alineada + asistente compacto + acierto (f22, f23, f24)

**Archivos:** `src/styles.css`, `src/pages/PlanFechas.tsx`, `src/pages/PaquetesAsistente.tsx`,
`src/pages/PaquetesContratacion.tsx`.

- [ ] `Sin frente`: cada `<li>` a `display: grid` con columnas fijas
      (`minmax(0,1fr) 160px 300px 110px 180px`), para que nombre, cuantía, selector, botón y badge
      caigan siempre en la misma posición.
- [ ] Asistente: reducir el aire vertical de la tarjeta y ensancharla (hoy ocupa media pantalla y
      deja la otra media vacía), hasta que los botones de decidir entren en 900 px de alto.
- [ ] «Acierto del motor»: añadir «sobre N decisiones» bajo el número, con el dato que ya trae
      `acierto.sugerenciasAplicadas`.

## Cierre

- [ ] `npm run test`, `npm run build` (f25).
- [ ] `PDC_E2E_DESTRUCTIVO=1 npx playwright test tests/browser/pdc-v2-*.spec.mjs` — 14/14 (f26).
- [ ] Sincronizar bundle al worktree.
- [ ] Recorrido visual completo en Da Porto a 1440×900, comparando con `test-output/ux-walkthrough/`.
- [ ] Marcar tandas 3 y 4 en `goals/pdc-revision-ux/hallazgos-e2e-usuario.md`.

## Riesgos

1. **El `<details>` de Paquetes puede romper el e2e** (tarea 5): es el riesgo más probable de todos.
2. **Ocultar el `<input type="file">`** (tarea 3) rompe `setInputFiles` si se hace con `display:none`.
   Usar la técnica de sólo-lectores-de-pantalla.
3. **El texto de «Recalcular» es una garantía verificada por tests PHP** (tarea 10): plegarlo sí,
   reescribirlo no.
4. **La confirmación de «Retirar» no puede prometer un número** que solo se conoce al ejecutar.
