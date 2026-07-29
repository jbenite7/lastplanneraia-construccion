import { describe, expect, it } from 'vitest'
import { filtraPorTexto, normaliza, plural } from './texto'

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
