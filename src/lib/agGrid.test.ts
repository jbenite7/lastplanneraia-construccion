import { describe, expect, it } from 'vitest'
import { columnaMoneda, columnaNumero, columnaTexto, defaultColDef, moneda } from './agGrid'

describe('moneda', () => {
  it('un cero se muestra como $ 0, no como celda vacía', () => {
    // Hoy el visor y el comparador lo dejan en blanco y las otras tres pantallas lo muestran.
    // Una celda vacía significa «no hay dato»; un cero significa «vale cero». No son lo mismo.
    expect(moneda(0)).toBe('$ 0')
  })

  it('un valor ausente sí deja la celda vacía', () => {
    expect(moneda(null)).toBe('')
    expect(moneda(undefined)).toBe('')
  })

  it('usa separador de miles colombiano', () => {
    expect(moneda(2109795800)).toContain('.')
  })
})

describe('defaultColDef', () => {
  it('las columnas se pueden redimensionar a mano', () => {
    expect(defaultColDef.resizable).toBe(true)
  })
})

describe('columnaMoneda', () => {
  it('el dinero nunca parte en dos renglones', () => {
    // Un importe cortado en dos líneas se lee mal y descuadra la altura de la fila.
    expect(columnaMoneda('valorTotal', 'Valor total').wrapText).toBeFalsy()
  })

  it('formatea con la misma función que el resto del módulo', () => {
    const col = columnaMoneda('valorTotal', 'Valor total')
    const formatear = col.valueFormatter
    if (typeof formatear !== 'function') throw new Error('columnaMoneda debe traer valueFormatter')
    // @ts-expect-error — al formatter solo le importa `value`; el resto del contexto de AG Grid no.
    expect(formatear({ value: 0 })).toBe('$ 0')
  })
})

describe('columnaNumero', () => {
  it('las cifras tampoco envuelven', () => {
    expect(columnaNumero('cantidad', 'Cantidad').wrapText).toBeFalsy()
  })
})

describe('columnaTexto', () => {
  it('el texto largo sí envuelve y crece de alto', () => {
    const c = columnaTexto('descripcion', 'Descripción')
    expect(c.wrapText).toBe(true)
    expect(c.autoHeight).toBe(true)
  })
})
