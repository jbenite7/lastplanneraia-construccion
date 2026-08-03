# Goal — Tandas 3 y 4: la primera impresión y el pulido

## El objetivo

Cerrar los once pendientes del informe de revisión UX: lo que se ve al entrar (cargador de Excel en
inglés, pestañas que abren vacías, plurales con «(s)») y el pulido que quedaba (buscadores,
confirmaciones, etiquetas que mienten).

- Entendimiento compartido: [`facts.md`](facts.md) — 26 hechos.
- Plan de ejecución: [`plan.md`](plan.md) — 11 tareas, aprobado en el gate el 2026-07-29.
- Grilleo: [`interview-result.json`](interview-result.json) — 14 preguntas, las 14 recomendaciones
  aceptadas sin divergencias.

## Condición de hecho — cumplida el 2026-07-29

Medido en la app real (Da Porto, `localhost:8091`), consola sin errores:

| Hecho | Medición |
|---|---|
| f01–f03 cargador | «Elegir archivo… · o suelta aquí el Excel», con zona de arrastre |
| f04–f05 Maestro | con 0 pendientes abre en **«Catálogo global (3079)»** |
| f06–f07 cierre de Paquetes | «Por valor está todo asignado. Queda 1 insumo sin destino, de $ 0.» y los tres bloques de controles plegados tras «Asignar insumos» |
| f08–f09 vacíos | **0 apariciones** de «No Rows To Show» en toda la app |
| f10–f11 plurales | **0 apariciones** de «(s)» / «(es)» a la vista |
| f12 tres cifras | nota bajo cada una: «insumos distintos de este presupuesto», «insumos de toda la empresa», y tooltip de cabecera en el historial |
| f13–f14 badges | «Nómina de obra · NO CONTRATABLE» — el CONSUMIBLES falso ya no aparece |
| f15–f17 retirar | confirmación con el impacto, y cabecera «Acción» en la columna |
| f18–f20 buscadores | «concreto» deja 8 de 102 paquetes; «carpinteria» (sin tilde) encuentra CARPINTERÍA, 1 de 85; comparador con el suyo |
| f21 nota | «¿qué conserva?» plegado, con el texto intacto |
| f22 Sin frente | rejilla de columnas fijas |
| f23 asistente | «Omitir» acaba en y=840 de 900: sobre el pliegue |
| f24 acierto | «100 % **sobre 2 decisiones**» |
| f25–f26 regresión | Vitest 215/215 · build ✅ · 14/14 e2e |

## Lo que el e2e cazó y no habría visto un humano

Al aplicar «el Maestro abre por el catálogo cuando no hay pendientes», la decisión automática
llegaba **después** del clic del usuario —el primer resumen encadena un POST y un GET— y devolvía a
quien acabara de elegir «Importar SINCO» a la pestaña de apertura. Es un fallo real de carrera, no
un problema del test. Arreglado marcando la decisión como cerrada en cuanto el usuario toca una
pestaña.

Los otros dos fallos de e2e sí eran el comportamiento nuevo funcionando (los controles plegados y la
confirmación al retirar); en esos dos se ajustó el spec sin tocar lo que comprueba.

## Lo que sigue pendiente a propósito

El dato `tipo_negociacion` de «Nómina de obra» e «Imprevistos y provisiones» sigue diciendo
`consumibles` en `general_paquetes_contratacion`. Se decidió esconderlo, no corregirlo: ese catálogo
lo comparten todos los proyectos de AIA y la migración merece su propio alcance.

---

## Cierre formal

**Estado:** HECHO
**Fecha de cierre:** 2026-07-29 (documentado formalmente 2026-07-31)

Condición de hecho cumplida: 26 hechos verificados. Vitest 215/215, build OK, 14/14 e2e.
0 apariciones de "No Rows To Show", 0 "(s)", buscadores insensibles a tildes funcionando.

---

## Archivos de este goal

[[goals/pdc-tanda34-pulido/facts|facts.md]] · [[goals/pdc-tanda34-pulido/plan|plan.md]]

Estado y relación con los demás goals: [[estado|Estado de los goals]].
