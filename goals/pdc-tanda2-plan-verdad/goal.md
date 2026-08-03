# Goal — Tanda 2: que el Plan de compras diga la verdad

## El objetivo

El Plan de compras es el entregable final del módulo y era el único sitio sin indicador de cuánto
le falta: anunciaba «11 paquete(s)» mientras 85 esperaban frente. Esta tanda le pone cobertura,
saca los vencidos del texto pequeño, y convierte un botón que escribía 40 amarres de un clic en dos
botones que distinguen lo seguro de lo probable.

- Entendimiento compartido: [`facts.md`](facts.md) — 32 hechos verificables.
- Plan de ejecución: [`plan.md`](plan.md) — 5 tareas, aprobado en el gate el 2026-07-29.
- Grilleo: [`interview.json`](interview.json) / [`interview-result.json`](interview-result.json) —
  14 preguntas, 13 recomendaciones aceptadas y una divergencia deliberada.

## Condición de hecho — cumplida el 2026-07-29

Medido en la app real (Da Porto, `localhost:8091`), consola sin errores:

| Hecho | Medición |
|---|---|
| f01–f07 cobertura | **54 %** por valor · «11 de 96 paquetes con fecha» · los tres contadores intactos |
| f08–f14 franja | «3 paquetes debieron arrancar hace hasta 98 días»; filtrar deja 3 filas; deshacer las devuelve; cerrar la quita y recargar la trae |
| f15–f23 confianza | desglose «3 ALTA · 37 MEDIA · 0 BAJA»; «Aceptar 3 de confianza alta» y «Revisar 37 de confianza media»; la confirmación dice $ 5.790.756.244 y cancelar no escribe |
| f24–f26 comparador | «Ahorros $ 46.629.280.887» sin signo; Δ conserva el suyo; «Δ = sobrecostos − ahorros» |
| f27–f30 texto y ancho | 0 palabras partidas a 1440 y a 1024; a 1024 se esconden «Agrupación» y «Recurso», «Destino» y «Sugerencia» siguen visibles |
| f31–f32 regresión | Vitest 197/197 · `npm run build` ✅ · 14/14 e2e `pdc-v2-*` |

## Lo que hay que saber al leer el 54 %

Por **valor** el plan está al 54 %; por **conteo**, al 11 % (11 de 96). No es una contradicción: los
once paquetes ya amarrados son los más caros del proyecto. Por eso el grilleo pidió los dos números
juntos — cada uno solo cuenta media verdad, y el de conteo es el que dice cuánto trabajo queda.

---

## Archivos de este goal

[[goals/pdc-tanda2-plan-verdad/facts|facts.md]] · [[goals/pdc-tanda2-plan-verdad/plan|plan.md]]

Estado y relación con los demás goals: [[estado|Estado de los goals]].
