import { describe, expect, it } from 'vitest'
import {
  AVISO_DESAMARRAR,
  accionDeClic,
  contarSinResponsable, estadoFila, etiquetaDesfase, etiquetaElegible, generaProceso, idPorEtiqueta, mensajeCalculo, opcionFrente,
  opcionesResponsable, paquetesAmarradosSinCalcular,
  paquetesSinFrente, planUiReducer, preseleccionDestinos, procedenciaDeAmarre, resumenPlan, trasGuardarEdicion,
  opcionesFrente,
  uniqueIdPorEtiquetaFrente,
  valorResponsableMostrado,
} from './planFechas'
import type { Desfase, FilaPlan, FrenteDisponible, SugerenciaFrente } from './types'

const fila = (over: Partial<FilaPlan> = {}): FilaPlan => ({
  paqueteId: 1, nombre: 'Suministro CONCRETO', tipoNegociacion: 'suministro', modalidad: 'orden_compra',
  frenteNombre: 'ESTRUCTURA', uniqueId: 9001, fechaAncla: '2026-08-18', fechaArranque: '2026-05-23',
  diasTotales: 87, duracionProvisional: false,
  responsableUserId: null, responsableNombre: '', responsableCargo: '', responsableHuerfano: false,
  diasRetraso: 0, pasos: [],
  ...over,
})

describe('estadoFila', () => {
  it('lo vencido es «vencido», con sus días', () => {
    expect(estadoFila(fila({ diasRetraso: 65 }))).toEqual({ clave: 'vencido', etiqueta: '65 días de retraso' })
  })

  it('sin retraso es «en plazo»', () => {
    expect(estadoFila(fila()).clave).toBe('en-plazo')
  })

  it('la duración provisional se distingue, aunque esté en plazo', () => {
    expect(estadoFila(fila({ duracionProvisional: true })).clave).toBe('provisional')
  })

  it('vencido manda sobre provisional: es lo urgente', () => {
    expect(estadoFila(fila({ diasRetraso: 10, duracionProvisional: true })).clave).toBe('vencido')
  })

  // Importante 3 del review final A4: sin cruzar los desfases, una fila reprogramada se veía «en
  // plazo» en verde con su fecha vieja. El desfase debe mandar sobre vencido y provisional.
  const desfase: Desfase = {
    paqueteId: 1, nombre: 'Suministro CONCRETO', frenteNombre: 'ESTRUCTURA',
    fechaGuardada: '2026-05-23', fechaActual: '2026-06-10', diasMovidos: 18,
  }

  it('un desfase manda sobre "en plazo": la fecha calculada ya no es de fiar', () => {
    expect(estadoFila(fila(), desfase).clave).toBe('desfasado')
  })

  it('un desfase manda incluso sobre vencido', () => {
    expect(estadoFila(fila({ diasRetraso: 10 }), desfase).clave).toBe('desfasado')
  })

  it('un desfase manda incluso sobre provisional', () => {
    expect(estadoFila(fila({ duracionProvisional: true }), desfase).clave).toBe('desfasado')
  })

  it('la etiqueta del desfase explica qué pasó, no solo que pasó algo', () => {
    expect(estadoFila(fila(), desfase).etiqueta).toBe(`Desactualizado: ${etiquetaDesfase(desfase)}`)
  })

  it('sin desfase para esta fila, el estado es el de siempre', () => {
    expect(estadoFila(fila()).clave).toBe('en-plazo')
  })
})

describe('resumenPlan', () => {
  it('cuenta vencidos, provisionales y total', () => {
    const r = resumenPlan([fila({ diasRetraso: 5 }), fila({ duracionProvisional: true }), fila()])
    expect(r).toEqual({ total: 3, vencidos: 1, provisionales: 1 })
  })

  it('un plan vacío no rompe', () => {
    expect(resumenPlan([])).toEqual({ total: 0, vencidos: 0, provisionales: 0 })
  })
})

describe('opcionFrente', () => {
  it('siempre lleva la fecha: el cronograma repite nombres de frente', () => {
    const f: FrenteDisponible = { uniqueId: 1, nombre: 'PISOS Y ENCHAPES', capitulo: '05', fechaInicio: '2027-05-12' }
    expect(opcionFrente(f)).toBe('PISOS Y ENCHAPES — 2027-05-12')
  })
})

describe('generaProceso', () => {
  it('contrato y orden de compra entran al plan de fechas', () => {
    expect(generaProceso('contrato')).toBe(true)
    expect(generaProceso('orden_compra')).toBe(true)
  })

  it('consumo directo y no contratable no entran', () => {
    expect(generaProceso('consumo_directo')).toBe(false)
    expect(generaProceso('no_contratable')).toBe(false)
  })

  it('sin modalidad se asume contrato (default del catálogo)', () => {
    expect(generaProceso(undefined)).toBe(true)
  })
})

describe('procedenciaDeAmarre', () => {
  // origen 'similitud'|'rama' (el cierre de tipos de Task 9 detectó que este fixture usaba 'reglas',
  // un valor de SugerenciaPaquete['capa'] que nunca es posible aquí — TypeScript lo señaló al
  // estrechar SugerenciaFrente.origen).
  const sugerencia: SugerenciaFrente = {
    uniqueId: 9001, nombre: 'ESTRUCTURA', fechaInicio: '2026-08-18', origen: 'similitud', confianza: 'alta', evidencia: 'coincide por código',
  }

  it('elegir el frente propuesto cuenta como acierto confirmado', () => {
    expect(procedenciaDeAmarre(sugerencia, 9001)).toEqual({
      origen: 'similitud', confianza: 'alta', evidencia: 'coincide por código', confirmado: true,
    })
  })

  it('elegir otro frente no deja procedencia', () => {
    expect(procedenciaDeAmarre(sugerencia, 9002)).toBeUndefined()
  })

  it('sin propuesta previa no hay procedencia', () => {
    expect(procedenciaDeAmarre(undefined, 9001)).toBeUndefined()
  })
})

describe('paquetesSinFrente', () => {
  const base = { paqueteId: 1, nombre: 'Suministro CONCRETO', tipoNegociacion: 'suministro', modalidad: 'contrato', insumos: 3, subtotal: 100 }

  it('excluye los ya amarrados y los que no generan proceso, y ordena por cuantía', () => {
    const porPaquete = [
      { ...base, paqueteId: 1, subtotal: 100 },
      { ...base, paqueteId: 2, subtotal: 500 },
      { ...base, paqueteId: 3, modalidad: 'consumo_directo', subtotal: 999 },
    ]
    const amarres = { 1: { uniqueId: 9001, nombre: 'ESTRUCTURA', fechaInicio: '2026-08-18' } }
    expect(paquetesSinFrente(porPaquete, amarres).map((p) => p.paqueteId)).toEqual([2])
  })
})

describe('paquetesAmarradosSinCalcular', () => {
  const base = { paqueteId: 1, nombre: 'Suministro CONCRETO', tipoNegociacion: 'suministro', modalidad: 'contrato', insumos: 3, subtotal: 100 }
  const filaPlan = (paqueteId: number): FilaPlan => ({
    paqueteId, nombre: 'x', tipoNegociacion: 'suministro', modalidad: 'contrato', frenteNombre: 'ESTRUCTURA',
    uniqueId: 9001, fechaAncla: '2026-08-18', fechaArranque: '2026-05-23', diasTotales: 87,
    duracionProvisional: false,
    responsableUserId: null, responsableNombre: '', responsableCargo: '', responsableHuerfano: false,
    diasRetraso: 0, pasos: [],
  })

  // Importante 2 del review final A4: amarrar sin recalcular dejaba el paquete invisible en las
  // dos secciones (ya no está «sin frente», y la grilla solo lee el plan calculado).
  it('un paquete amarrado que todavía no tiene fila en el plan calculado aparece aquí', () => {
    const porPaquete = [{ ...base, paqueteId: 1 }]
    const amarres = { 1: { uniqueId: 9001, nombre: 'ESTRUCTURA', fechaInicio: '2026-08-18' } }
    expect(paquetesAmarradosSinCalcular(porPaquete, amarres, []).map((p) => p.paqueteId)).toEqual([1])
  })

  it('en cuanto el plan lo trae calculado, deja de aparecer', () => {
    const porPaquete = [{ ...base, paqueteId: 1 }]
    const amarres = { 1: { uniqueId: 9001, nombre: 'ESTRUCTURA', fechaInicio: '2026-08-18' } }
    expect(paquetesAmarradosSinCalcular(porPaquete, amarres, [filaPlan(1)])).toEqual([])
  })

  it('sin amarre, no aparece aquí (eso es "Sin frente")', () => {
    const porPaquete = [{ ...base, paqueteId: 1 }]
    expect(paquetesAmarradosSinCalcular(porPaquete, {}, [])).toEqual([])
  })

  it('una modalidad que no genera proceso nunca aparece, aunque quede sin calcular', () => {
    const porPaquete = [{ ...base, paqueteId: 1, modalidad: 'consumo_directo' }]
    const amarres = { 1: { uniqueId: 9001, nombre: 'ESTRUCTURA', fechaInicio: '2026-08-18' } }
    expect(paquetesAmarradosSinCalcular(porPaquete, amarres, [])).toEqual([])
  })

  it('ordena por cuantía descendente, igual que el resto del sembrado', () => {
    const porPaquete = [{ ...base, paqueteId: 1, subtotal: 100 }, { ...base, paqueteId: 2, subtotal: 500 }]
    const amarres = {
      1: { uniqueId: 9001, nombre: 'ESTRUCTURA', fechaInicio: '2026-08-18' },
      2: { uniqueId: 9002, nombre: 'PRELIMINARES', fechaInicio: '2026-05-25' },
    }
    expect(paquetesAmarradosSinCalcular(porPaquete, amarres, []).map((p) => p.paqueteId)).toEqual([2, 1])
  })
})

describe('preseleccionDestinos', () => {
  const sugerencia: SugerenciaFrente = {
    uniqueId: 9001, nombre: 'ESTRUCTURA', fechaInicio: '2026-08-18', origen: 'similitud', confianza: 'alta', evidencia: 'coincide por código',
  }

  // Bloqueante del review final A4: si `sinFrente` llega antes que `sugerencias` (carrera entre las
  // dos peticiones), no debe sembrar '' a ciegas — hay que esperar a que las sugerencias resuelvan.
  it('sin sugerencias cargadas todavía, no siembra nada (evita fijar \'\' por una carrera)', () => {
    const resultado = preseleccionDestinos({}, [{ paqueteId: 1 }], {}, false)
    expect(resultado).toEqual({})
  })

  it('en cuanto las sugerencias cargan, siembra con la propuesta del motor', () => {
    const resultado = preseleccionDestinos({}, [{ paqueteId: 1 }], { 1: sugerencia }, true)
    expect(resultado).toEqual({ 1: 9001 })
  })

  it('sin propuesta para ese paquete (pero ya cargadas las sugerencias), siembra vacío', () => {
    const resultado = preseleccionDestinos({}, [{ paqueteId: 1 }], {}, true)
    expect(resultado).toEqual({ 1: '' })
  })

  it('no pisa lo que el usuario ya eligió a mano', () => {
    const resultado = preseleccionDestinos({ 1: 9002 }, [{ paqueteId: 1 }], { 1: sugerencia }, true)
    expect(resultado).toEqual({ 1: 9002 })
  })

  it('sin cambios, devuelve la misma referencia (sin re-render de balde)', () => {
    const prev = { 1: 9002 }
    expect(preseleccionDestinos(prev, [{ paqueteId: 1 }], { 1: sugerencia }, true)).toBe(prev)
  })

  // Caso central del bloqueante: la carrera se resuelve reprocesando cuando `sugerenciasCargadas`
  // pasa a true, aunque `sinFrente` ya tuviera contenido desde antes.
  it('lo que quedó pendiente por la carrera se siembra en cuanto sugerenciasCargadas pasa a true', () => {
    const trasCarrera = preseleccionDestinos({}, [{ paqueteId: 1 }], {}, false) // sinFrente llegó primero
    expect(trasCarrera).toEqual({})
    const trasSugerencias = preseleccionDestinos(trasCarrera, [{ paqueteId: 1 }], { 1: sugerencia }, true)
    expect(trasSugerencias).toEqual({ 1: 9001 })
  })
})

describe('etiquetaDesfase', () => {
  it('describe el movimiento cuando el frente sigue existiendo', () => {
    const d: Desfase = { paqueteId: 1, nombre: 'Suministro CONCRETO', frenteNombre: 'ESTRUCTURA', fechaGuardada: '2026-05-23', fechaActual: '2026-06-10', diasMovidos: 18 }
    expect(etiquetaDesfase(d)).toBe('se movió de 2026-05-23 a 2026-06-10, 18 día(s)')
  })

  it('avisa distinto cuando el frente desapareció del cronograma', () => {
    const d: Desfase = { paqueteId: 1, nombre: 'Suministro CONCRETO', frenteNombre: 'ESTRUCTURA', fechaGuardada: '2026-05-23', fechaActual: null, diasMovidos: null }
    expect(etiquetaDesfase(d)).toBe('«ESTRUCTURA» ya no está en el cronograma')
  })
})

describe('mensajeCalculo', () => {
  it('reporta calculados y avisa si algunos quedaron sin duración de referencia', () => {
    expect(mensajeCalculo({ calculados: 40, sinDuracion: 3 })).toBe('40 paquete(s) recalculado(s); 3 sin duración de referencia.')
  })

  it('sin pendientes, mensaje simple', () => {
    expect(mensajeCalculo({ calculados: 40, sinDuracion: 0 })).toBe('40 paquete(s) recalculado(s).')
  })
})

describe('trasGuardarEdicion', () => {
  it('en éxito retira el override si había uno pendiente: gana el dato real', () => {
    expect(trasGuardarEdicion({ 1: 'Ana', 2: 'Luis' }, 1, { ok: true })).toEqual({ 2: 'Luis' })
  })

  it('en éxito sin override previo no toca nada — misma referencia, sin re-render de balde', () => {
    const valores = { 2: 'Luis' }
    expect(trasGuardarEdicion(valores, 1, { ok: true })).toBe(valores)
  })

  it('en fallo fija el valor anterior al intento, sin tocar overrides de otras filas', () => {
    expect(trasGuardarEdicion({ 2: 'Luis' }, 1, { ok: false, anterior: 'Ana' })).toEqual({ 1: 'Ana', 2: 'Luis' })
  })

  it('en fallo repetido sobre la misma fila, el override queda en el último valor anterior', () => {
    const primero = trasGuardarEdicion({}, 1, { ok: false, anterior: 'Ana' })
    const segundo = trasGuardarEdicion(primero, 1, { ok: false, anterior: 'Ana' })
    expect(segundo).toEqual({ 1: 'Ana' })
  })

  // Crítico del review final A4: reproduce a nivel de lógica el mecanismo exacto del bug. AG Grid
  // calcula `newValue` para `onCellValueChanged` llamando al valueGetter de la columna —que ES
  // `valorResponsableMostrado`— DESPUÉS de que el valueSetter ya dejó la fila con los datos nuevos.
  // Si el override de la 1ª edición (Ana) sigue puesto cuando se elige a Luis, el valueGetter no lee
  // la fila (que ya dice Luis) sino el override viejo: AG Grid le pasaría a `onResponsable` "Ana" en
  // vez de "Luis" como `newValue`, así que ni siquiera llegaría a intentar guardar a Luis.
  it('sin soltar el override en éxito, la 2ª edición seguiría "viendo" al primer elegido (el bug)', () => {
    // 1ª edición: el usuario elige a Ana; el POST sale bien pero el override queda puesto (sin el
    // fix de defecto 1: onResponsable no lo suelta tras el éxito).
    const overrideSinSoltar: Record<number, string> = { 1: 'Ana Gómez — Residente' }

    // 2ª edición: el usuario elige a Luis. AG Grid ya corrió el valueSetter —la fila quedó con los
    // datos de Luis— antes de calcular `newValue` para el evento.
    const filaTrasElegirLuis = fila({
      paqueteId: 1, responsableUserId: 9, responsableNombre: 'Luis Paz', responsableCargo: '',
    })
    // Esto es exactamente lo que el valueGetter de la columna Responsable le entrega a AG Grid como
    // `newValue`. Con el override de Ana todavía puesto, gana el override — no la fila.
    expect(valorResponsableMostrado(filaTrasElegirLuis, overrideSinSoltar)).toBe('Ana Gómez — Residente')
  })

  it('con el fix (override suelto en éxito), la 2ª edición sí ve al recién elegido', () => {
    let overrides: Record<number, string> = {}

    // 1ª edición: elegir a Ana, POST exitoso → el fix suelta el override (trasGuardarEdicion ok:true).
    overrides = { ...overrides, 1: 'Ana Gómez — Residente' } // optimista, antes de esperar el POST
    overrides = trasGuardarEdicion(overrides, 1, { ok: true })
    expect(overrides).toEqual({}) // nada queda pendiente: gana el dato real de la fila

    // 2ª edición sobre LA MISMA celda: elegir a Luis. Sin ningún override pendiente, el valueGetter
    // de AG Grid (valorResponsableMostrado) lee la fila —que el valueSetter ya dejó en Luis— y por
    // fin `newValue` es el que el usuario acaba de elegir, no el de la edición anterior.
    const filaTrasElegirLuis = fila({
      paqueteId: 1, responsableUserId: 9, responsableNombre: 'Luis Paz', responsableCargo: '',
    })
    expect(valorResponsableMostrado(filaTrasElegirLuis, overrides)).toBe('Luis Paz')
  })
})

describe('planUiReducer — tipo del mensaje', () => {
  // Menor del review final A4: `.pdc-info` pintaba igual un éxito que un fallo — una aserción de
  // e2e sobre ese selector pasaba aunque el amarre hubiera fallado. `tipo` es lo que distingue cuál fue.
  it('FALLO marca tipo error', () => {
    expect(planUiReducer({ ocupado: true, mensaje: null, tipo: null }, { type: 'FALLO', mensaje: 'algo salió mal' }))
      .toEqual({ ocupado: false, mensaje: 'algo salió mal', tipo: 'error' })
  })

  it('LISTO con mensaje marca tipo éxito', () => {
    expect(planUiReducer({ ocupado: true, mensaje: null, tipo: null }, { type: 'LISTO', mensaje: 'listo' }))
      .toEqual({ ocupado: false, mensaje: 'listo', tipo: 'exito' })
  })

  it('LISTO sin mensaje no deja tipo', () => {
    expect(planUiReducer({ ocupado: true, mensaje: null, tipo: null }, { type: 'LISTO' }))
      .toEqual({ ocupado: false, mensaje: null, tipo: null })
  })

  it('OCUPADO limpia el tipo anterior', () => {
    expect(planUiReducer({ ocupado: false, mensaje: 'algo salió mal', tipo: 'error' }, { type: 'OCUPADO' }))
      .toEqual({ ocupado: true, mensaje: null, tipo: null })
  })
})

describe('valorResponsableMostrado', () => {
  const ELEGIBLES = [
    { id: 7, nombre: 'Ana Gómez', cargo: 'Residente' },
    { id: 9, nombre: 'Luis Paz', cargo: '' },
  ]

  it('etiqueta a una persona con su cargo, y sin guion cuando no tiene', () => {
    expect(etiquetaElegible(ELEGIBLES[0])).toBe('Ana Gómez — Residente')
    expect(etiquetaElegible(ELEGIBLES[1])).toBe('Luis Paz')
  })

  it('muestra vacío cuando el paquete no tiene responsable', () => {
    expect(valorResponsableMostrado(
      fila({ paqueteId: 1, responsableUserId: null, responsableNombre: '', responsableCargo: '' }), {},
    )).toBe('')
  })

  it('muestra el nombre y el cargo que mandó el servidor', () => {
    expect(valorResponsableMostrado(
      fila({ paqueteId: 1, responsableUserId: 7, responsableNombre: 'Ana Gómez', responsableCargo: 'Residente' }), {},
    )).toBe('Ana Gómez — Residente')
  })

  // Menor del review final A4: el servidor marca huérfano por DOS causas (la persona salió del
  // proyecto, o su cuenta se desactivó) con un único booleano que no distingue cuál — el texto no
  // puede afirmar «ya no está en el proyecto» cuando la causa real fue la cuenta desactivada.
  it('avisa cuando el responsable ya no está disponible (sin afirmar una causa que el servidor no distingue)', () => {
    expect(valorResponsableMostrado(
      fila({
        paqueteId: 1, responsableUserId: 7, responsableNombre: 'Ana Gómez',
        responsableCargo: 'Residente', responsableHuerfano: true,
      }), {},
    )).toBe('Ana Gómez — Residente (ya no está disponible)')
  })

  it('el override manda sobre el dato del servidor (guardado optimista)', () => {
    expect(valorResponsableMostrado(
      fila({ paqueteId: 1, responsableUserId: 7, responsableNombre: 'Ana Gómez', responsableCargo: 'Residente' }),
      { 1: 'Luis Paz' },
    )).toBe('Luis Paz')
  })

  it('un override de OTRA fila no afecta a esta', () => {
    expect(valorResponsableMostrado(
      fila({ paqueteId: 1, responsableUserId: 7, responsableNombre: 'Ana Gómez', responsableCargo: 'Residente' }),
      { 2: 'Luis Paz' },
    )).toBe('Ana Gómez — Residente')
  })

  it('las opciones arrancan con el vacío para poder dejar el paquete sin responsable', () => {
    const fSin = fila({ paqueteId: 1, responsableUserId: null, responsableNombre: '', responsableCargo: '' })
    expect(opcionesResponsable(ELEGIBLES, fSin)).toEqual(['', 'Ana Gómez — Residente', 'Luis Paz'])
  })

  it('las opciones incluyen al huérfano para que su celda no aparezca en blanco', () => {
    const fHuerfano = fila({
      paqueteId: 1, responsableUserId: 4, responsableNombre: 'Carla Ruiz',
      responsableCargo: 'Compras', responsableHuerfano: true,
    })
    expect(opcionesResponsable(ELEGIBLES, fHuerfano)).toEqual([
      '', 'Ana Gómez — Residente', 'Luis Paz', 'Carla Ruiz — Compras (ya no está disponible)',
    ])
  })

  it('traduce la etiqueta elegida al id que espera el servidor', () => {
    expect(idPorEtiqueta(ELEGIBLES, 'Ana Gómez — Residente')).toBe(7)
    expect(idPorEtiqueta(ELEGIBLES, 'Luis Paz')).toBe(9)
  })

  it('el vacío y cualquier etiqueta desconocida se traducen a «sin responsable»', () => {
    expect(idPorEtiqueta(ELEGIBLES, '')).toBeNull()
    // El huérfano entra aquí: se puede quitar, pero no se le puede volver a elegir.
    expect(idPorEtiqueta(ELEGIBLES, 'Carla Ruiz — Compras (ya no está disponible)')).toBeNull()
  })
})

describe('contarSinResponsable', () => {
  it('cuenta los paquetes que no tienen a nadie', () => {
    expect(contarSinResponsable([
      { responsableUserId: null }, { responsableUserId: 7 }, { responsableUserId: null },
    ])).toBe(2)
  })

  it('un responsable huérfano cuenta como pendiente: hay que reasignarlo', () => {
    expect(contarSinResponsable([{ responsableUserId: 7, responsableHuerfano: true }])).toBe(1)
  })

  it('un responsable vigente no cuenta', () => {
    expect(contarSinResponsable([{ responsableUserId: 7, responsableHuerfano: false }])).toBe(0)
  })

  it('sin filas da cero, no NaN', () => {
    expect(contarSinResponsable([])).toBe(0)
  })
})

describe('accionDeClic', () => {
  it('en la columna de responsable, el clic edita', () => {
    expect(accionDeClic('responsable')).toBe('editar')
  })

  it('en la columna de frente también edita: cambiar de frente se hace desde la tabla', () => {
    expect(accionDeClic('frente')).toBe('editar')
  })

  it('en la columna de desamarrar dispara la acción, no el detalle', () => {
    expect(accionDeClic('desamarrar')).toBe('accion')
  })

  it('en cualquier otra columna, el clic abre el detalle', () => {
    expect(accionDeClic('nombre')).toBe('detalle')
    expect(accionDeClic('estado')).toBe('detalle')
  })

  it('sin columna identificada, abre el detalle: es lo que no destruye nada', () => {
    expect(accionDeClic(undefined)).toBe('detalle')
  })
})

describe('opcionesFrente', () => {
  const frentes: FrenteDisponible[] = [
    { uniqueId: 9001, nombre: 'ESTRUCTURA', capitulo: '02', fechaInicio: '2026-08-18' },
    { uniqueId: 9002, nombre: 'PISOS Y ENCHAPES', capitulo: '05', fechaInicio: '2027-05-12' },
  ]

  it('ofrece los frentes disponibles del cronograma', () => {
    expect(opcionesFrente(frentes, fila())).toEqual([
      'ESTRUCTURA — 2026-08-18', 'PISOS Y ENCHAPES — 2027-05-12',
    ])
  })

  it('conserva el frente que la fila tiene puesto aunque ya no exista en el cronograma', () => {
    // Si el frente desapareció al reprogramar, sin su propia opción la celda no podría ni mostrar
    // lo que la fila tiene guardado.
    const huerfana = fila({ frenteNombre: 'CUBIERTA', fechaAncla: '2026-03-01' })
    expect(opcionesFrente(frentes, huerfana)).toContain('CUBIERTA — 2026-03-01')
  })

  it('no duplica el frente actual cuando sí sigue disponible', () => {
    const puesta = fila({ frenteNombre: 'ESTRUCTURA', fechaAncla: '2026-08-18' })
    expect(opcionesFrente(frentes, puesta).filter((o) => o.startsWith('ESTRUCTURA'))).toHaveLength(1)
  })
})

describe('uniqueIdPorEtiquetaFrente', () => {
  const frentes: FrenteDisponible[] = [
    { uniqueId: 9001, nombre: 'ESTRUCTURA', capitulo: '02', fechaInicio: '2026-08-18' },
  ]

  it('traduce la etiqueta elegida al uniqueId que espera el servidor', () => {
    expect(uniqueIdPorEtiquetaFrente(frentes, 'ESTRUCTURA — 2026-08-18')).toBe(9001)
  })

  it('una etiqueta que ya no corresponde a ningún frente no inventa un id', () => {
    expect(uniqueIdPorEtiquetaFrente(frentes, 'CUBIERTA — 2026-03-01')).toBeNull()
  })
})

describe('AVISO_DESAMARRAR', () => {
  it('dice las dos verdades: se pierden las fechas, se conserva el responsable', () => {
    expect(AVISO_DESAMARRAR).toContain('fechas')
    expect(AVISO_DESAMARRAR).toContain('responsable')
    expect(AVISO_DESAMARRAR).toContain('Sin frente')
  })
})
