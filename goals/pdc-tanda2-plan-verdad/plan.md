---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-07-28
areas: [pdc]
fuente: goals/pdc-tanda2-plan-verdad/plan.md
resumen: For agentic workers. Repo: /Volumes/Crucial X6/Developer/plan-de-compras, rama pdc-revision-ux. Bundle al worktree /Volumes/Crucial…
---

# Plan — Tanda 2: que el Plan de compras diga la verdad

For agentic workers. Repo: `/Volumes/Crucial X6/Developer/plan-de-compras`, rama `pdc-revision-ux`.
Bundle al worktree `/Volumes/Crucial X6/Developer/lps-aia-pdc/public/pdc-app/` (nunca a `../lps-aia`).
App real en `localhost:8091`, proyecto Da Porto. Hechos: `facts.md`.

## Enfoque

Cinco cambios independientes entre sí, todos en la SPA. Ninguno necesita endpoint nuevo: la
cobertura sale de `ResumenPaquetes.porPaquete` (trae `subtotal` y `modalidad`) cruzado con
`amarres`, que la pantalla ya tiene en estado. La lógica pura va a `src/lib/` con test primero; la
pintura va a las páginas y se verifica en el navegador.

Orden: 1 → 2 → 3 son del Plan y comparten fichero, así que van seguidos. 4 y 5 son independientes.

---

## Tarea 1 — Cobertura del Plan (f01–f07)

**Archivos:** `src/lib/planFechas.ts`, `src/lib/planFechas.test.ts`, `src/pages/PlanFechas.tsx`,
`src/styles.css`.

- [ ] Test primero en `planFechas.test.ts`: `coberturaPlan(porPaquete, amarres)` devuelve
      `{ conFecha, total, porcentajeConteo, valorConFecha, valorTotal, porcentajeValor }`.
      Casos: solo cuenta `generaProceso` (un `no_contratable` y un `consumo_directo` quedan fuera
      del total, f03); un paquete en `amarres` cuenta como cubierto aunque no tenga plan calculado
      (f04); total 0 no divide por cero y devuelve 0 %.
- [ ] Implementar `coberturaPlan` en `planFechas.ts`, reutilizando `generaProceso`.
- [ ] En `PlanFechas.tsx`, calcular con `useMemo` sobre `porPaquete` y `amarres`.
- [ ] Pintar en el encabezado un bloque con las clases `pdc-paq-cobertura*` que ya existen (f06):
      número grande = `porcentajeValor`, detalle = «{conFecha} de {total} paquetes con fecha»,
      barra con `scaleX(porcentajeValor / 100)`. `data-testid="pdc-plan-cobertura"`.
- [ ] Conservar los tres contadores actuales (f07); si el encabezado queda apretado, moverlos bajo
      el detalle sin cambiar su texto.
- [ ] Aviso de amarrados sin calcular (f05): reutilizar `paquetesAmarradosSinCalcular`, que ya
      existe, y mostrar «N esperando Recalcular» junto a la cobertura. Solo si N > 0.
- [ ] Verificar: `npm run test`.

## Tarea 2 — Franja de vencidos (f08–f14)

**Archivos:** `src/pages/PlanFechas.tsx`, `src/styles.css`, `src/lib/planFechas.test.ts`.

- [ ] Test primero: `resumenVencidos(plan)` → `{ cuantos, diasMaximo }`; 0 vencidos → `cuantos: 0`.
- [ ] Estado local `soloVencidos` (bool) y `franjaCerrada` (bool). Ninguno se persiste (f12).
- [ ] Render de la franja sobre la tabla cuando `cuantos > 0 && !franjaCerrada` (f08, f13), con
      `role="status"`, texto «N paquetes debieron arrancar hace hasta D días» (f09),
      `data-testid="pdc-plan-franja-vencidos"`.
- [ ] Botón dentro de la franja que alterna `soloVencidos` (f10, f11); su etiqueta cambia entre
      «Ver solo los vencidos» y «Ver todos». Botón de cerrar aparte (f12).
- [ ] Filtrar `rowData` por `diasRetraso > 0` cuando `soloVencidos`; **no** tocar el orden (f14).
- [ ] Verificar: `npm run test` y comprobación visual en Da Porto (hay 3 vencidos reales).

## Tarea 3 — Aceptar propuestas por confianza (f15–f23)

**Archivos:** `src/lib/planFechas.ts`, `src/lib/planFechas.test.ts`, `src/pages/PlanFechas.tsx`,
`src/styles.css`.

- [ ] Test primero: `agruparPorConfianza(sinFrente, sugerencias)` → `{ alta: [], media: [], baja: [] }`,
      y `sumaValor(paquetes)`. Un paquete sin propuesta no entra en ningún grupo.
- [ ] Sustituir el botón único por dos (f16, f17): principal «Aceptar N de confianza alta»
      (`data-testid="pdc-plan-aceptar-alta"`), secundario «Revisar N de confianza media»
      (`pdc-plan-aceptar-media`), con clase visualmente secundaria.
- [ ] El principal se deshabilita cuando `alta.length === 0` (f22).
- [ ] El secundario **no escribe**: abre un panel de confirmación (f18) con el resumen —cuántas y
      cuánta plata (f19)— y un `<details>` plegado con la lista paquete → frente (f20).
      `data-testid="pdc-plan-confirmar-media"`.
- [ ] Desglose por confianza visible siempre que haya propuestas (f15), junto a los botones.
- [ ] Ningún botón masivo toca `baja` (f21): `agruparPorConfianza` las separa y nadie las consume.
- [ ] Reutilizar el bucle de `onAceptarSugeridos` tal cual para ambos caminos, parametrizando la
      lista: eso conserva el respeto al `<select>` de la fila y a `procedenciaDeAmarre` (f23).
- [ ] Verificar: `npm run test`; e2e `pdc-v2-plan.spec.mjs` (usa el botón de aceptar propuesta).

⚠ **Riesgo:** el e2e `pdc-v2-plan.spec.mjs:107` («aceptar una propuesta del motor amarra el
paquete») puede depender del `data-testid` o del texto del botón actual. Revisarlo antes de tocar y
ajustar el spec si hace falta, sin debilitar lo que comprueba.

## Tarea 4 — Comparador: signo de los ahorros (f24–f26)

**Archivos:** `src/pages/ComparativoPresupuesto.tsx`.

- [ ] `Ahorros` con `Math.abs()` (f24); el Δ se deja como está (f25).
- [ ] Línea pequeña bajo el resumen: «Δ = sobrecostos − ahorros» (f26).
- [ ] Verificar visualmente contra `test-output/ux-walkthrough/04-comparar.png`.

## Tarea 5 — Texto cortado y pantallas angostas (f27–f30)

**Archivos:** `src/lib/agGrid.ts`, `src/lib/agGrid.test.ts`, `src/pages/PaquetesContratacion.tsx`,
`src/styles.css`.

- [ ] `.ag-cell-wrap-text { overflow-wrap: normal; word-break: normal; }` en `styles.css` (f27):
      AG Grid trae `overflow-wrap: break-word`, que parte la palabra cuando no cabe sola.
- [ ] Subir el `minWidth` de «Agrupación» de 130 a 170 (f28): «SUBCONTRATACION» necesita ~150 px.
      Revisar de paso el resto de columnas de texto del módulo.
- [ ] Hook `usaPantallaAngosta()` en `agGrid.ts` con `matchMedia('(max-width: 1199px)')`, con test
      de que devuelve el valor inicial correcto.
- [ ] En `PaquetesContratacion.tsx`, cuando sea angosta, marcar `hide: true` en «Agrupación» y
      «Recurso» (f29). «Destino» y «Sugerencia» **nunca** se esconden (f30).
- [ ] Verificar: `npm run test` + capturas a 1024 y a 1440.

## Cierre

- [ ] `npm run test` y `npm run build` en verde (f32).
- [ ] `PDC_E2E_DESTRUCTIVO=1 npx playwright test tests/browser/pdc-v2-*.spec.mjs` — 14/14 (f31).
- [ ] Copiar `dist/assets/pdc.{js,css}` a `lps-aia-pdc/public/pdc-app/assets/`.
- [ ] Recorrido visual en `localhost:8091` sobre Da Porto, comparando contra
      `test-output/ux-walkthrough/`, a 1440×900 y a 1024×768.
- [ ] Actualizar `goals/pdc-revision-ux/hallazgos-e2e-usuario.md` marcando la tanda 2.

## Riesgos y preguntas abiertas

1. **El e2e del Plan puede romperse** al partir el botón de aceptar (ver aviso en la tarea 3).
2. **El encabezado del Plan se llena:** ya lleva título, bajada, tres contadores y el botón
   «Recalcular» con su nota larga. Añadir la cobertura puede apretarlo a 1024 px. Si pasa, la nota
   de «Recalcular» es lo primero que debe ceder — está anotada como H23 para la tanda 4.
3. **Esconder columnas es la decisión del dueño del producto, no mi recomendación.** Si al verlo a
   1024 px se echa en falta «Agrupación», el cambio a scroll lateral es de una línea.
4. **`coberturaPlan` no distingue** un paquete con plan calculado de uno amarrado sin recalcular:
   por decisión (f04) ambos cuentan. El aviso de f05 es lo que evita que eso esconda trabajo.
