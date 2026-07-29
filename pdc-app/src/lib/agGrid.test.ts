import { describe, expect, it } from 'vitest'
import {
  MIN_WIDTH_CIFRA,
  MIN_WIDTH_PALABRA_LARGA,
  autoSizeStrategy,
  columnaMoneda,
  columnaNumero,
  columnaTexto,
  columnasQueCaben,
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

describe('MIN_WIDTH_PALABRA_LARGA', () => {
  it('deja sitio para la palabra más larga que aparece en el módulo', () => {
    // «SUBCONTRATACION» (15 caracteres) se partía como «SUBCONTRATACIO / N PERSONAL» porque su
    // columna tenía un mínimo de 130 px. A ~9,5 px por carácter en mayúsculas más el padding de la
    // celda, no baja de 165.
    expect(MIN_WIDTH_PALABRA_LARGA).toBeGreaterThanOrEqual(165)
  })
})

describe('columnasQueCaben', () => {
  // Cinco columnas de 100 px = 500, más los 16 de la barra de scroll vertical.
  const cols = [
    { colId: 'insumo', minWidth: 100 },
    { colId: 'agrupacion', minWidth: 100 },
    { colId: 'recurso', minWidth: 100 },
    { colId: 'destino', minWidth: 100 },
    { colId: 'sugerencia', minWidth: 100 },
  ]
  const escondidas = (r: { colId?: string; hide?: boolean }[]) => r.filter((c) => c.hide).map((c) => c.colId)

  it('con hueco de sobra no esconde nada', () => {
    expect(escondidas(columnasQueCaben(cols, 900, ['agrupacion', 'recurso']))).toEqual([])
  })

  it('esconde de una en una, solo mientras haga falta', () => {
    // 460 px: cabe quitando una sola columna (400 + 16 ≤ 460), así que la segunda se queda.
    expect(escondidas(columnasQueCaben(cols, 460, ['agrupacion', 'recurso']))).toEqual(['agrupacion'])
  })

  it('esconde en el orden declarado cuando falta más hueco', () => {
    expect(escondidas(columnasQueCaben(cols, 330, ['agrupacion', 'recurso']))).toEqual(['agrupacion', 'recurso'])
  })

  it('nunca esconde lo que se viene a mirar a esa pantalla', () => {
    // Decisión del grilleo (f30): «Destino» y «Sugerencia» son el motivo de abrir Paquetes, así que
    // no están en la lista de prescindibles ni con el hueco más apretado.
    const r = escondidas(columnasQueCaben(cols, 120, ['agrupacion', 'recurso']))
    expect(r).not.toContain('destino')
    expect(r).not.toContain('sugerencia')
  })

  it('cuenta el ancho de las columnas que AG Grid añade por su cuenta', () => {
    // La casilla de selección múltiple del Plan no vive en `columnDefs`: son 44 px invisibles para
    // el cálculo que dejaban 4 px de scroll lateral.
    expect(escondidas(columnasQueCaben(cols, 560, ['agrupacion', 'recurso']))).toEqual([])
    expect(escondidas(columnasQueCaben(cols, 560, ['agrupacion', 'recurso'], 120))).toEqual(['agrupacion'])
  })

  it('sin medir todavía el contenedor no esconde nada', () => {
    // El primer render no conoce el ancho; esconder a ciegas haría parpadear las columnas.
    expect(escondidas(columnasQueCaben(cols, 0, ['agrupacion', 'recurso']))).toEqual([])
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
  // `fitCellContents` sumaba anchos sin mirar el hueco disponible y sacaba la columna del dinero
  // fuera del borde: la cifra se cortaba sin puntos suspensivos y parecía completa.
  it('el módulo reparte el ancho disponible en vez de pedirlo por contenido', () => {
    expect(autoSizeStrategy).toBeDefined()
    expect(autoSizeStrategy.type).toBe('fitGridWidth')
  })

  it('una columna de cifra reserva lo que mide el importe más ancho de obra', () => {
    expect(columnaMoneda('valorTotal', 'Valor total').minWidth).toBe(MIN_WIDTH_CIFRA)
  })
})
