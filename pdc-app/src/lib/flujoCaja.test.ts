import { describe, expect, it } from 'vitest'
import {
  alturaBarra,
  cobertura,
  etiquetaMes,
  mesPico,
  textoExcluidos,
  type ExcluidosFlujo,
  type MesFlujo,
  type RespuestaFlujoCaja,
} from './flujoCaja'

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
    meses: [],
    total: 0,
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
    { mes: '2026-02', previsto: 100, acumulado: 100, destinos: 1 },
    { mes: '2026-03', previsto: 400, acumulado: 500, destinos: 2 },
    { mes: '2026-04', previsto: 250, acumulado: 750, destinos: 2 },
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
