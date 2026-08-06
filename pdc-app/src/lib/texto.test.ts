import { describe, expect, it } from 'vitest'
import { coincide, contarInsumos, filtraPorTexto, normaliza, PALABRA_INSUMOS, plural } from './texto'

describe('plural', () => {
  it('el singular va sin «s»', () => {
    expect(plural(1, 'paquete')).toBe('1 paquete')
  })

  it('el plural la lleva', () => {
    expect(plural(11, 'paquete')).toBe('11 paquetes')
  })

  it('el cero va en plural, como en español hablado', () => {
    // «0 paquete» no lo dice nadie.
    expect(plural(0, 'paquete')).toBe('0 paquetes')
  })

  it('los conteos largos llevan separador de miles', () => {
    // «1343 fila(s)» era lo que se leía en el visor: ni separador ni plural.
    expect(plural(1343, 'fila')).toBe('1.343 filas')
  })

  it('acepta un plural irregular cuando la «s» no basta', () => {
    expect(plural(2, 'vínculo', 'vínculos')).toBe('2 vínculos')
    expect(plural(1, 'vínculo', 'vínculos')).toBe('1 vínculo')
  })
})

describe('normaliza', () => {
  it('quita acentos y mayúsculas para poder comparar', () => {
    expect(normaliza('CARPINTERÍA')).toBe('carpinteria')
    expect(normaliza('Instalación')).toBe('instalacion')
  })

  it('la ñ no es una n con acento y se conserva', () => {
    // Quitar la tilde de la ñ convertiría «CAÑO» en «cano», que es otra palabra.
    expect(normaliza('CAÑO')).toBe('caño')
  })

  it('proteger la ñ no se lleva por delante los espacios', () => {
    // La primera versión usaba el espacio como centinela: al deshacer el cambio, «tubería de PVC»
    // salía como «tuberiañdeñpvc». Funcionaba para comparar porque los dos lados se rompían igual,
    // pero cualquier otro uso de `normaliza` habría heredado la basura.
    expect(normaliza('CAÑO DE PVC')).toBe('caño de pvc')
    expect(normaliza('TUBERÍA DE PVC')).toBe('tuberia de pvc')
  })
})

describe('filtraPorTexto', () => {
  const filas = [
    { nombre: 'Sum + Inst CARPINTERÍA DE MADERA' },
    { nombre: 'Suministro CONCRETO' },
    { nombre: 'M. de O MAMPOSTERÍA' },
  ]

  it('encuentra sin acentos ni mayúsculas', () => {
    expect(filtraPorTexto(filas, 'carpinteria', (f) => f.nombre)).toHaveLength(1)
    expect(filtraPorTexto(filas, 'CONCRETO', (f) => f.nombre)).toHaveLength(1)
  })

  it('una búsqueda vacía no filtra nada', () => {
    expect(filtraPorTexto(filas, '   ', (f) => f.nombre)).toHaveLength(3)
  })

  it('busca por trozo, no solo por el principio', () => {
    expect(filtraPorTexto(filas, 'madera', (f) => f.nombre)).toHaveLength(1)
  })
})

describe('contarInsumos', () => {
  it('las dos magnitudes tienen una sola palabra cada una', () => {
    expect(PALABRA_INSUMOS.apariciones).toBe('apariciones en APU')
    expect(PALABRA_INSUMOS.distintos).toBe('insumos distintos')
  })

  it('el 820 y el 396 pueden convivir sin parecer un error', () => {
    expect(contarInsumos(820, 'apariciones')).toBe('820 apariciones en APU')
    expect(contarInsumos(396, 'distintos')).toBe('396 insumos distintos')
  })

  it('en singular no dice «1 insumos distintos»', () => {
    expect(contarInsumos(1, 'distintos')).toBe('1 insumo distinto')
    expect(contarInsumos(1, 'apariciones')).toBe('1 aparición en APU')
  })

  it('el cero va en plural', () => {
    expect(contarInsumos(0, 'distintos')).toBe('0 insumos distintos')
  })

  it('los miles se separan como se leen en español', () => {
    expect(contarInsumos(1343, 'apariciones')).toBe('1.343 apariciones en APU')
  })
})

describe('coincide', () => {
  it('ignora mayúsculas y acentos', () => {
    expect(coincide('Cementó Gris', 'cemento')).toBe(true)
  })

  it('conserva la ñ: «caño» no es «cano»', () => {
    expect(coincide('CAÑO PVC', 'cano')).toBe(false)
    expect(coincide('CAÑO PVC', 'caño')).toBe(true)
  })

  it('una búsqueda vacía o de solo espacios coincide con todo', () => {
    expect(coincide('lo que sea', '')).toBe(true)
    expect(coincide('lo que sea', '   ')).toBe(true)
  })

  it('busca por subcadena, no solo por prefijo', () => {
    expect(coincide('MAT-ELECTRICOS Y AFINES', 'electri')).toBe(true)
  })
})
