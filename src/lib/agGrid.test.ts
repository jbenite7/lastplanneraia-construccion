import { describe, expect, it } from 'vitest'
import {
  autoSizeStrategy,
  columnaMoneda,
  columnaNumero,
  columnaTexto,
  defaultColDef,
  moneda,
} from './agGrid'

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

  it('nunca muestra decimales, cualesquiera que traiga el valor', () => {
    // El recorrido de usuario encontró estas cuatro cifras en la MISMA tabla: «$ 3.144.138»,
    // «$ 102.290.635,8», «$ 25.430.823.601,77», «$ 1.866.977.292». `toLocaleString` sin opciones
    // da 0, 1 o 2 decimales según el número, así que las columnas de dinero no alineaban y
    // comparar magnitudes de un vistazo era imposible.
    expect(moneda(3144138)).toBe('$ 3.144.138')
    expect(moneda(102290635.8)).toBe('$ 102.290.636')
    expect(moneda(25430823601.77)).toBe('$ 25.430.823.602')
  })

  it('redondea, no trunca', () => {
    expect(moneda(1.5)).toBe('$ 2')
    expect(moneda(1.4)).toBe('$ 1')
  })

  it('los negativos conservan el signo', () => {
    expect(moneda(-46629280886.6)).toBe('$ -46.629.280.887')
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

  it('queda fuera de la medición por contenido', () => {
    // Si se midiera, una descripción de 200 caracteres pediría una columna de 200 caracteres y
    // empujaría fuera de pantalla al resto. El texto largo se resuelve envolviendo, no ensanchando.
    expect(columnaTexto('descripcion', 'Descripción').suppressAutoSize).toBe(true)
  })
})

describe('autoSizeStrategy', () => {
  it('el módulo expone una estrategia de ancho que se ajusta al contenido', () => {
    expect(autoSizeStrategy).toBeDefined()
    expect(autoSizeStrategy.type).toBe('fitCellContents')
  })
})
