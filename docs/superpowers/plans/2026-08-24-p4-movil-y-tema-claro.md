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

**Los 13, nombrados** (medido el 2026-08-25 sobre `docs/design-system/manifests/`: 15 manifiestos
declaran `layouts`, uno ya trae `mobile` —`programa-general`, el piloto— y `laboratory` no es módulo
de producto): `auth`, `bi-runtime`, `control-cambios`, `escalamientos`, `foundation-shell`,
`indicadores`, `plan-compras-v2`, `profesionales`, `programa-general-actualizar`,
`programacion-intermedia`, `programacion-semanal`, `project-selector`, `subcontratistas`.

### Trasladado desde `f2a-piloto-movil-programacion` al derogarla (2026-08-25)

- [ ] **E5 · el aviso al cruzar el umbral en caliente.** La decisión existía y **nunca se
      implementó**: verificado el 2026-08-25, `shouldRenderCards()` solo se consulta dentro de
      `applyFiltersAndRender` (`programacion_semanal/hot.js:3276`), y el listener de resize
      (`bindResize`, `:4874-4894`) solo reajusta alto y columnas. Al cruzar 1180 px en caliente con
      Handsontable montado, la vista **queda desactualizada sin avisar**. La decisión original pedía
      aviso visible con botón de recargar. No lo recogía ningún documento vigente

### Trasladado desde `reapertura-movil-y-tema-claro` al derogarla (2026-08-25)

- [ ] **El Plan de Compras no admite la receta del piloto, y está entre los 13.** `plan-compras-v2`
      es una SPA React con AG Grid (`pdc-app/`) que **no comparte código** con el resto de la app: el
      umbral de montaje condicional y el menú flotante del shell no le aplican tal cual. La spec
      derogada pedía «su propia spec dentro de F2» y **no existe ninguna** — comprobado el
      2026-08-25 en `docs/superpowers/specs/`, `goals/` y los dos planes: **cero menciones de `pdc`
      en P3 y en P4**. Decidir al llegar a él: spec propia o receta adaptada, pero no darlo por
      cubierto con los otros doce

## MO-F3 · Tema claro

**Es reconstruir, no reactivar.** El tema `linen` se retiró del producto el 2026-07-25 (DS-030) y no
existe conmutador. Quien lea «reactivar el tema claro» está leyendo mal el alcance.

Orden de Felipe (2026-08-20, revisando D-9): **no queda estacionada, va justo detrás de móvil**.

- [ ] Paleta clara nueva, derivada del contrato de DS-F1 y del manual AIA
- [ ] Conmutador con preferencia guardada
- [ ] **Revalidar todas las superficies** — el viewport canónico de validación sigue siendo
      `1180x820`, pero dark deja de ser el único tema validado

### Los dos candados que hay que reformar aquí — trasladado desde `reapertura-movil-y-tema-claro`

Ambos verificados vigentes el 2026-08-25. No son trabajo opcional: **con el tema claro puesto, los
dos se ponen rojos por hacer bien las cosas**, y quien llegue sin saberlo los va a ablandar en vez
de reformarlos.

- [ ] **`theme-default.test.mjs` cambia de forma, no desaparece.** Fija a mano las **22**
      declaraciones del bloque `:root` (contadas hoy). Con un segundo tema, «las 22 declaraciones
      del `:root`» deja de ser la pregunta correcta: hay que comprobar **qué tema está activo y que
      sus tokens estén completos**, no una lista fija
- [ ] **`linen-removal.test.mjs` debe pasar de comparar cadena a comparar intención.** Hoy hace
      `/linen/i.test(...)` sobre contratos y scripts, así que **no distingue «prometer un tema
      retirado» de «explicar que se retiró»** — el 2026-08-07 puso en rojo una redacción que decía
      justo lo contrario de prometerlo. Al documentar el tema claro nuevo, la palabra `linen` va a
      aparecer legítimamente en las explicaciones históricas

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
