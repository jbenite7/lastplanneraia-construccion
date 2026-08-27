import { describe, expect, it } from 'vitest'
import { ordenarPorUrgencia } from './urgencia'
import type { RestriccionUrgencia } from './urgencia'

// Paso 1 de Task 7 (rol A, test writer) — fija el contrato de `urgencia.ts` ANTES de que exista
// (Paso 2, rol B, lo implementa). Esta librería es pura: sin fetch, sin DOM, solo ordena un array
// ya resuelto por el backend.
//
// N4 (docs/superpowers/specs/2026-08-26-v0-del-producto-design.md, CT-20.1): «"Urgencia" en la
// lista de restricciones = cuándo golpea, luego cuánto arrastra. Primero la restricción cuya
// actividad bloqueada inicia más pronto; a igual semana, la que más actividades encadena y si
// toca ruta crítica.» Literal a la decisión de la hoja: liberar hoy la que mata el compromiso de
// dentro de tres semanas.
//
// Forma de entrada — investigada, no inventada:
// - `semanaInicioActividadBloqueada` espeja `Semanas_Inicio` de `programa_consolidado` /
//   `bi_pi_restricciones` (database/bi/002_bi_pi_restricciones.sql): la ventana de semanas 0–6 que
//   ya usa `pi_hard_restrictions_ready_rate` (MetricDictionaryService.php:99). Cuando una
//   restricción bloquea VARIAS actividades (una fila de `pi_shared_constraints` puede tener
//   varios `pi_shared_constraint_links`), este campo es la más pronta de todas — la que golpea
//   primero, que es el criterio de N4.
// - `actividadesEncadenadas` es "cuánto arrastra": el conteo de actividades que cuelgan de la
//   restricción. La fuente cruda ya existe (COUNT de `pi_shared_constraint_links` por
//   `SharedConstraintId`), pero NINGÚN método de `ControlTowerService` la agrega hoy — ver el
//   concern en el reporte de este paso.
// - `tocaRutaCritica` espeja la columna `Ruta_Critica` de `programa_consolidado`, ya presente en
//   `bi_pi_restricciones` (línea 17 y 105 del view) — sí existe hoy, a diferencia del conteo de
//   encadenamiento.
//
// Caso borde exigido por el brief: "sin fecha = alarma arriba del todo, como las huérfanas del
// PDC" (pdc-app/src/lib/planFechas.ts: `SimulacionReprogramacion.huerfanos`, y
// `contarSinResponsable` en pdc-app/src/pages/PlanFechas.tsx tratando un responsable huérfano
// como pendiente, nunca como dato que se cae al fondo). Aquí el equivalente es
// `semanaInicioActividadBloqueada === null` — no sabemos cuándo golpea, así que se asume la peor
// urgencia posible y sube al tope, no al fondo del orden.

function restriccion(overrides: Partial<RestriccionUrgencia> & { id: number }): RestriccionUrgencia {
  return {
    semanaInicioActividadBloqueada: 3,
    actividadesEncadenadas: 0,
    tocaRutaCritica: false,
    ...overrides,
  }
}

describe('ordenarPorUrgencia — criterio primario: semana de inicio de la actividad bloqueada', () => {
  it('ordena ascendente por semana — la que golpea más pronto va primero', () => {
    const lista = [
      restriccion({ id: 1, semanaInicioActividadBloqueada: 3 }),
      restriccion({ id: 2, semanaInicioActividadBloqueada: 0 }),
      restriccion({ id: 3, semanaInicioActividadBloqueada: 1 }),
    ]

    const orden = ordenarPorUrgencia(lista).map((r) => r.id)

    expect(orden).toEqual([2, 3, 1])
  })

  it('la semana 0 es una semana válida — no se confunde con "sin fecha"', () => {
    // Bug típico: `if (!semana)` trata 0 como ausente. La semana 0 (esta semana) es la más
    // urgente de las semanas reales y debe quedar por debajo solo de las restricciones sin
    // semana conocida (null), nunca tratada como si tampoco tuviera dato.
    const lista = [
      restriccion({ id: 1, semanaInicioActividadBloqueada: null }),
      restriccion({ id: 2, semanaInicioActividadBloqueada: 0 }),
    ]

    const orden = ordenarPorUrgencia(lista).map((r) => r.id)

    expect(orden).toEqual([1, 2])
  })
})

describe('ordenarPorUrgencia — desempates: encadenamiento y ruta crítica', () => {
  it('a igual semana, desempata por más actividades encadenadas primero', () => {
    const lista = [
      restriccion({ id: 1, semanaInicioActividadBloqueada: 2, actividadesEncadenadas: 1 }),
      restriccion({ id: 2, semanaInicioActividadBloqueada: 2, actividadesEncadenadas: 5 }),
      restriccion({ id: 3, semanaInicioActividadBloqueada: 2, actividadesEncadenadas: 3 }),
    ]

    const orden = ordenarPorUrgencia(lista).map((r) => r.id)

    expect(orden).toEqual([2, 3, 1])
  })

  it('a igual semana y encadenamiento, la que toca ruta crítica va primero', () => {
    const lista = [
      restriccion({ id: 1, semanaInicioActividadBloqueada: 4, actividadesEncadenadas: 2, tocaRutaCritica: false }),
      restriccion({ id: 2, semanaInicioActividadBloqueada: 4, actividadesEncadenadas: 2, tocaRutaCritica: true }),
    ]

    const orden = ordenarPorUrgencia(lista).map((r) => r.id)

    expect(orden).toEqual([2, 1])
  })

  it('aplica los tres criterios en cascada: semana, luego encadenamiento, luego ruta crítica', () => {
    // id 1 = "A" (semana 1), 2 = "B" (semana 0, 1 encadenada), 3 = "C" (semana 0, 4
    // encadenadas, sin ruta crítica), 4 = "D" (semana 0, 4 encadenadas, ruta crítica).
    const lista = [
      restriccion({ id: 1, semanaInicioActividadBloqueada: 1, actividadesEncadenadas: 1, tocaRutaCritica: true }),
      restriccion({ id: 2, semanaInicioActividadBloqueada: 0, actividadesEncadenadas: 1, tocaRutaCritica: false }),
      restriccion({ id: 3, semanaInicioActividadBloqueada: 0, actividadesEncadenadas: 4, tocaRutaCritica: false }),
      restriccion({ id: 4, semanaInicioActividadBloqueada: 0, actividadesEncadenadas: 4, tocaRutaCritica: true }),
    ]

    const orden = ordenarPorUrgencia(lista).map((r) => r.id)

    // id 4: semana 0, 4 encadenadas, ruta crítica — la más urgente de las cuatro.
    // id 3: semana 0, 4 encadenadas, sin ruta crítica — pierde el tercer desempate contra 4.
    // id 2: semana 0, 1 encadenada — pierde el segundo desempate contra 3 y 4.
    // id 1: semana 1 — pierde el criterio primario contra las tres anteriores, aunque sea la
    //   única con ruta crítica: N4 compara semana antes que ruta crítica.
    expect(orden).toEqual([4, 3, 2, 1])
  })
})

describe('ordenarPorUrgencia — caso borde: sin semana conocida sube al tope, no al fondo', () => {
  it('una restricción sin semana de la actividad bloqueada va antes que cualquier semana numérica', () => {
    const lista = [
      restriccion({ id: 1, semanaInicioActividadBloqueada: 0 }),
      restriccion({ id: 2, semanaInicioActividadBloqueada: null }),
      restriccion({ id: 3, semanaInicioActividadBloqueada: 6 }),
    ]

    const orden = ordenarPorUrgencia(lista).map((r) => r.id)

    expect(orden[0]).toBe(2)
    expect(orden).toEqual([2, 1, 3])
  })

  it('entre varias sin semana, sigue aplicando el desempate por encadenamiento y ruta crítica', () => {
    // "Sin fecha" es la peor urgencia posible en el criterio primario, no una excepción a los
    // desempates: dos restricciones huérfanas de semana no son intercambiables entre sí.
    const lista = [
      restriccion({ id: 1, semanaInicioActividadBloqueada: null, actividadesEncadenadas: 1 }),
      restriccion({ id: 2, semanaInicioActividadBloqueada: null, actividadesEncadenadas: 7 }),
      restriccion({ id: 3, semanaInicioActividadBloqueada: 0, actividadesEncadenadas: 99 }),
    ]

    const orden = ordenarPorUrgencia(lista).map((r) => r.id)

    expect(orden).toEqual([2, 1, 3])
  })
})

describe('ordenarPorUrgencia — contrato general', () => {
  it('es estable: dos restricciones idénticas en los tres criterios conservan su orden de entrada', () => {
    const lista = [
      restriccion({ id: 1, semanaInicioActividadBloqueada: 2, actividadesEncadenadas: 2, tocaRutaCritica: true }),
      restriccion({ id: 2, semanaInicioActividadBloqueada: 2, actividadesEncadenadas: 2, tocaRutaCritica: true }),
    ]

    const orden = ordenarPorUrgencia(lista).map((r) => r.id)

    expect(orden).toEqual([1, 2])
  })

  it('no muta el array de entrada — devuelve uno nuevo (inmutabilidad, coding-style.md)', () => {
    const lista = Object.freeze([
      restriccion({ id: 1, semanaInicioActividadBloqueada: 5 }),
      restriccion({ id: 2, semanaInicioActividadBloqueada: 1 }),
    ])

    // Si la implementación hiciera `lista.sort(...)` en vez de copiar primero, este freeze la
    // haría lanzar en modo estricto (todos los módulos ESM lo son) — la prueba lo detecta sin
    // necesidad de comparar referencias a mano.
    expect(() => ordenarPorUrgencia(lista)).not.toThrow()

    const orden = ordenarPorUrgencia(lista).map((r) => r.id)
    expect(orden).toEqual([2, 1])
  })

  it('una lista vacía devuelve una lista vacía, sin lanzar', () => {
    expect(ordenarPorUrgencia([])).toEqual([])
  })
})
