// Acción sugerida por tipo de restricción (ct-app, etapa piloto, Task 7 D89).
//
// D89 (docs/superpowers/specs/2026-08-26-v0-del-producto-design.md, CT-8.3): «Cada alerta trae su
// acción sugerida y a quién acudir.» Mismo patrón que `titulares.ts` (N1, CT-20.1): plantillas por
// regla, un juego finito de frases con huecos, elegidas por condición medible (aquí, el tipo de
// restricción) — nunca IA generativa ni redacción manual.
//
// Distinción con el otro D89 (CT-20.1, nota técnica bajo N1-N11): ESE mecanismo es
// `ActionRecommendationService::actionsFromPI()` (PHP), que arma un TOP-5 GLOBAL de acciones para
// el brief ejecutivo (hoja 8.1) con contacto personalizado a la obra concreta. Esta librería es
// OTRO mecanismo, para OTRA hoja (8.3, lista de restricciones): UNA acción por CADA fila, con un
// contacto GENÉRICO por tipo de restricción — no personalizado al proyecto ni derivado de sesión.
// No toca `ActionRecommendationService` ni el backend.
//
// Librería pura: sin fetch, sin DOM.

export interface AccionSugerida {
  texto: string
  contacto: string
}

/** Contacto genérico de última instancia para un `tipoRestriccion` que no calza con ninguna de las 5 plantillas duras. */
const CONTACTO_NEUTRO = 'Residente de Obra'

const PLANTILLAS: Record<string, AccionSugerida> = {
  Materiales: {
    texto: 'Materiales sin liberar: llame al proveedor, o escale a compras.',
    contacto: 'Proveedor / Compras',
  },
  D_y_E: {
    texto: 'Diseño o especificación pendiente: consulte a la Oficina Técnica, o escale al diseñador si no hay respuesta.',
    contacto: 'Oficina Técnica / Diseño',
  },
  MdeO: {
    texto: 'Mano de obra insuficiente: contacte al subcontratista que la aporta, o escale al residente para reasignar cuadrillas.',
    contacto: 'Subcontratista / Residente',
  },
  Equipos: {
    texto: 'Equipo sin disponibilidad: llame al proveedor del equipo, o escale a compras si no libera a tiempo.',
    contacto: 'Proveedor de equipos / Compras',
  },
  Predecesora: {
    texto: 'Actividad predecesora incompleta o no ejecutada: consulte al residente, o a programación si el bloqueo es de otro frente.',
    contacto: 'Residente / Programación',
  },
}

/**
 * Produce la acción sugerida (texto + contacto) para un `tipoRestriccion`. Coincidencia exacta,
 * sin normalizar mayúsculas ni espacios — `tipoRestriccion` llega tal cual desde `restriccion`
 * (columna `Restriccion` de `pi_shared_constraints`), un enum de backend, no texto libre.
 *
 * Nunca lanza excepción ni devuelve texto/contacto vacíos: un tipo que no calce con ninguna de las
 * 5 plantillas duras (dato sucio, tipo futuro) cae en una acción neutra declarada con el residente
 * de obra como contacto genérico de última instancia — mismo principio que N1 en `titulares.ts`.
 */
export function construirAccionSugerida(tipoRestriccion: string): AccionSugerida {
  const plantilla = PLANTILLAS[tipoRestriccion]
  if (plantilla) {
    return plantilla
  }

  return {
    texto: 'Restricción sin clasificar: consulte al residente de obra para definir cómo resolverla.',
    contacto: CONTACTO_NEUTRO,
  }
}
