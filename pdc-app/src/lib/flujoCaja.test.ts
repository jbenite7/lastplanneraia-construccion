import { describe, expect, it } from 'vitest'
import {
  alturaBarra,
  cobertura,
  etiquetaMes,
  mesPico,
  porcentajeConFecha,
  textoExcluidos,
  textoProvisional,
  type ExcluidosFlujo,
  type MesFlujo,
  type RespuestaFlujoCaja,
} from './flujoCaja'

/** Los tres orígenes en cero: la base sobre la que cada test pone solo lo que le importa. */
const SIN_ORIGENES = {
  contratado: { destinos: 0, valor: 0 },
  permanente: { destinos: 0, valor: 0 },
  provisional: { destinos: 0, valor: 0 },
}

const mes = (m: string, previsto: number, acumulado: number, extra: Partial<MesFlujo> = {}): MesFlujo => ({
  mes: m,
  previsto,
  acumulado,
  destinos: 1,
  contratado: previsto,
  permanente: 0,
  provisional: 0,
  ...extra,
})

const valor = (v: number) => `$${v.toLocaleString('es-CO')}`

describe('etiquetaMes', () => {
  it('rotula el mes en español', () => {
    expect(etiquetaMes('2026-02')).toBe('febrero 2026')
    expect(etiquetaMes('2026-12')).toBe('diciembre 2026')
  })

  it('no se corre un mes hacia atrás por el huso horario', () => {
    // `new Date('2026-02')` se interpreta en UTC y en UTC−5 devuelve el 31 de enero: la fila de
    // febrero se rotularía «enero». Este test es el que impide volver a esa implementación.
    expect(etiquetaMes('2026-01')).toBe('enero 2026')
    expect(etiquetaMes('2027-01')).toBe('enero 2027')
  })

  it('un mes que no entiende lo muestra crudo en vez de desaparecer', () => {
    expect(etiquetaMes('cualquier-cosa')).toBe('cualquier-cosa')
    expect(etiquetaMes('2026-13')).toBe('2026-13')
  })
})

describe('cobertura', () => {
  const base: RespuestaFlujoCaja = {
    nota: '',
    duracionObra: null,
    meses: [],
    total: 0,
    porOrigen: SIN_ORIGENES,
    incluidos: { destinos: 0, valor: 0 },
    excluidos: { destinos: 0, valor: 0, motivos: {} },
    valorTotalDelPlan: 0,
    detalle: [],
  }

  it('mide el valor incluido sobre el valor total del plan', () => {
    expect(cobertura({ ...base, incluidos: { destinos: 2, valor: 54 }, valorTotalDelPlan: 100 })).toBe(54)
  })

  it('es null sin plan que medir, no 0 ni 100', () => {
    expect(cobertura(base)).toBeNull()
  })

  it('redondea a un decimal', () => {
    expect(cobertura({ ...base, incluidos: { destinos: 1, valor: 1 }, valorTotalDelPlan: 3 })).toBe(33.3)
  })
})

describe('textoExcluidos', () => {
  it('dice cuántas contrataciones quedan fuera, cuánto valen y por qué', () => {
    const e: ExcluidosFlujo = {
      destinos: 3,
      valor: 12000,
      motivos: {
        'Sin frente amarrado en el cronograma': { destinos: 2, valor: 7000 },
        'Su modalidad no genera contratación (no_contratable)': { destinos: 1, valor: 5000 },
      },
    }
    const t = textoExcluidos(e, valor)
    expect(t).toContain('3 contrataciones')
    expect(t).toContain('$12.000')
    expect(t).toContain('sin frente amarrado')
    // Los motivos van de mayor a menor valor: lo que más plata deja fuera se lee primero.
    expect(t.indexOf('sin frente')).toBeLessThan(t.indexOf('su modalidad'))
  })

  it('usa el singular con una sola', () => {
    const t = textoExcluidos({ destinos: 1, valor: 500, motivos: { 'Sin fechas': { destinos: 1, valor: 500 } } }, valor)
    expect(t).toContain('1 contratación por')
  })

  it('no dice nada cuando no hay nada fuera', () => {
    expect(textoExcluidos({ destinos: 0, valor: 0, motivos: {} }, valor)).toBe('')
  })
})

describe('mesPico y alturaBarra', () => {
  const meses: MesFlujo[] = [
    mes('2026-02', 100, 100),
    mes('2026-03', 400, 500),
    mes('2026-04', 250, 750),
  ]

  it('encuentra el mes que más plata pide', () => {
    expect(mesPico(meses)?.mes).toBe('2026-03')
  })

  it('es null con la curva vacía', () => {
    expect(mesPico([])).toBeNull()
  })

  it('la barra del pico llena el 100 % y las demás son proporcionales', () => {
    expect(alturaBarra(400, 400)).toBe(100)
    expect(alturaBarra(100, 400)).toBe(25)
  })

  it('un mes con valor deja siempre barra visible, y sin pico no dibuja nada', () => {
    // Sin el mínimo de 1, un mes de $3 junto a uno de $4.000 millones sale con altura 0 y parece
    // que no hay desembolso ese mes, cuando lo que hay es un desembolso pequeño.
    expect(alturaBarra(1, 4_000_000_000)).toBe(1)
    expect(alturaBarra(0, 0)).toBe(0)
  })
})

describe('porcentajeConFecha', () => {
  const base: RespuestaFlujoCaja = {
    nota: '',
    duracionObra: { desde: '2026-01-01', hasta: '2027-12-31', origen: 'cronograma' },
    meses: [],
    total: 0,
    porOrigen: SIN_ORIGENES,
    incluidos: { destinos: 0, valor: 0 },
    excluidos: { destinos: 0, valor: 0, motivos: {} },
    valorTotalDelPlan: 0,
    detalle: [],
  }

  it('mide qué parte de la curva son compromisos con fecha propia', () => {
    const r: RespuestaFlujoCaja = {
      ...base,
      total: 1000,
      porOrigen: {
        contratado: { destinos: 2, valor: 900 },
        permanente: { destinos: 1, valor: 100 },
        provisional: { destinos: 0, valor: 0 },
      },
    }
    expect(porcentajeConFecha(r)).toBe(90)
  })

  it('es null con la curva vacía, no 0', () => {
    expect(porcentajeConFecha(base)).toBeNull()
  })
})

describe('textoProvisional', () => {
  const conProvisional = (destinos: number, valor: number, total: number): RespuestaFlujoCaja => ({
    nota: '',
    duracionObra: { desde: '2026-01-01', hasta: '2027-12-31', origen: 'cronograma' },
    meses: [],
    total,
    porOrigen: {
      contratado: { destinos: 1, valor: total - valor },
      permanente: { destinos: 0, valor: 0 },
      provisional: { destinos, valor },
    },
    incluidos: { destinos: destinos + 1, valor: total },
    excluidos: { destinos: 0, valor: 0, motivos: {} },
    valorTotalDelPlan: total,
    detalle: [],
  })

  it('avisa de cuánto de la curva se va a mover, y por qué', () => {
    const t = textoProvisional(conProvisional(3, 300, 1000), valor)
    expect(t).toContain('$300')
    expect(t).toContain('30 %')
    expect(t).toContain('3 contrataciones')
    expect(t).toContain('se moverá')
  })

  it('concuerda en singular', () => {
    const t = textoProvisional(conProvisional(1, 100, 1000), valor)
    expect(t).toContain('1 contratación que todavía no tiene frente')
    expect(t).not.toContain('contrataciones')
  })

  it('no dice nada cuando toda la curva tiene fecha o es gasto permanente', () => {
    expect(textoProvisional(conProvisional(0, 0, 1000), valor)).toBe('')
  })
})
