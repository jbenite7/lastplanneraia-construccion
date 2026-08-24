---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-24
areas: [design-system, lps]
fuente: docs/superpowers/plans/2026-08-24-p4-movil-y-tema-claro.md
resumen: "P4 · Los 13 módulos que faltan del piloto móvil (MO-F2b) y la reconstrucción del tema claro (MO-F3), que no es reactivar linen sino construir una paleta nueva con conmutador"
---

# P4 · Móvil y tema claro · MO-F2b → MO-F3

> **For agentic workers:** REQUIRED SUB-SKILL: `superpowers:executing-plans`. MO-F2b **sí** es
> repartible: son 13 módulos independientes con el coste ya medido en el piloto. MO-F3 no lo es.

**Spec:** [[docs/superpowers/specs/2026-08-24-estado-consolidado-del-repo-design]]
**Depende de:** el contrato de DS-F1 (P3). Migrar 13 módulos contra un contrato que va a cambiar es
migrarlos dos veces.

**Goal:** que la app sea usable en `390x844` en todos sus módulos, y que exista un tema claro con
conmutador y preferencia guardada.

**Cerrado y no se re-litiga:** MO-F1 (`390x844` soportado y no requerido, DS-032) · MO-F2a-1 (el
gate valida los 15 manifiestos, no 4) · MO-F2a-2a (DS-033) · MO-F2a-2b, el piloto — Handsontable
deja de instanciarse bajo el umbral (0 nodos en 390×844) y el sidebar pasa a menú flotante, que era
**la causa raíz de que móvil fuera inusable: se comía 240 de 390 px y nunca colapsaba**.

---

## MO-F2b · Los 13 módulos restantes

- [ ] Aplicar a cada módulo la receta del piloto: umbral de montaje condicional + menú flotante
- [ ] Un golden por módulo, atado a **su** tema, viewport y contenido — la precondición que MO-F2a-1
      dejó puesta
- [ ] Cerrar el diagnóstico del **dropdown de Programación Semanal sobre el selector de semana**
      (`systematic-debugging`): es un problema de stacking, así que **se resuelve con la escala de
      z-index de DS-F1**, no con un `z-index` a ojo

**Condición de hecho:** los 15 manifiestos en verde a 390×844, sin overflow horizontal.

## MO-F3 · Tema claro

**Es reconstruir, no reactivar.** El tema `linen` se retiró del producto el 2026-07-25 (DS-030) y no
existe conmutador. Quien lea «reactivar el tema claro» está leyendo mal el alcance.

Orden de Felipe (2026-08-20, revisando D-9): **no queda estacionada, va justo detrás de móvil**.

- [ ] Paleta clara nueva, derivada del contrato de DS-F1 y del manual AIA
- [ ] Conmutador con preferencia guardada
- [ ] **Revalidar todas las superficies** — el viewport canónico de validación sigue siendo
      `1180x820`, pero dark deja de ser el único tema validado

**No bloquea a `bi-control-tower-gemini`**, que cierra en dark por decisión propia (D-7).

---

## Deuda arrastrada que se cierra aquí

`bi-control-tower-gemini` lleva bloqueado desde el 2026-08-10 **por una causa mal diagnosticada**:
no es «falta aprobación visual», es que pedía aprobar 6 modos y **3 usan el tema `linen`, retirado**.

- [ ] Rehacer la condición de hecho, recortada a los tres modos dark (D-9, 2026-08-20). **No correr
      los tests** — no era eso lo que faltaba

## Condición de hecho

Los 15 manifiestos en verde a 390×844; el conmutador de tema funcionando con preferencia guardada;
y `bi-control-tower-gemini` cerrado con su condición rehecha.
