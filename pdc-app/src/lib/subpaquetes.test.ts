import { describe, expect, it } from 'vitest'
import {
  avisoAlBorrar,
  motivoNoPartir,
  nombresDePartir,
  sePuedeBorrar,
  textoAvance,
  textoFueraDelPlan,
  textoRango,
  type ResumenSombrilla,
  type Subpaquete,
} from './subpaquetes'

const valor = (v: number) => `$${v.toLocaleString('es-CO')}`

const lote = (id: number, nombre: string, esResto = false): Subpaquete => ({
  subpaqueteId: id,
  nombre,
  modalidad: 'contrato',
  responsableUserId: null,
  esResto,
  orden: id * 10,
  insumos: 1,
  valor: 100,
  generaProceso: true,
})

const sombrilla = (extra: Partial<ResumenSombrilla> = {}): ResumenSombrilla => ({
  lotes: 4,
  valorTotal: 1000,
  valorFueraDelPlan: 0,
  lotesFueraDelPlan: [],
  desde: '2026-08-01',
  hasta: '2027-06-10',
  lotesConPlan: 4,
  pasos: 28,
  pasosCumplidos: 7,
  avance: 25,
  ...extra,
})

describe('nombresDePartir', () => {
  it('acepta saltos de línea y comas, porque la gente escribe las dos cosas', () => {
    expect(nombresDePartir('Porcelanato\nTableta gres, Cerámica')).toEqual([
      'Porcelanato',
      'Tableta gres',
      'Cerámica',
    ])
  })

  it('quita vacíos y espacios sobrantes', () => {
    expect(nombresDePartir('  Porcelanato  ,,\n\n  Gres ')).toEqual(['Porcelanato', 'Gres'])
  })

  it('quita duplicados antes de enviarlos: el servidor los rechazaría igual', () => {
    expect(nombresDePartir('Gres, Gres')).toEqual(['Gres'])
  })

  it('un texto vacío no da ningún nombre', () => {
    expect(nombresDePartir('   \n , ')).toEqual([])
  })
})

describe('motivoNoPartir', () => {
  it('explica por qué no se puede, en vez de apagar el botón en silencio', () => {
    expect(motivoNoPartir('')).toContain('al menos un lote')
  })

  it('con un nombre válido no hay motivo', () => {
    expect(motivoNoPartir('Porcelanato')).toBe('')
  })
})

describe('textoRango', () => {
  it('dice el rango que abarcan los lotes', () => {
    expect(textoRango(sombrilla())).toContain('De 2026-08-01 a 2027-06-10')
    expect(textoRango(sombrilla())).toContain('sus 4 lotes')
  })

  it('avisa cuando el rango solo cubre parte de los lotes', () => {
    // Un rango a medias se lee como el rango completo: hay que decir cuántos lotes lo forman.
    expect(textoRango(sombrilla({ lotesConPlan: 2 }))).toContain('2 de sus 4 lotes')
  })

  it('sin fechas dice qué hacer, no solo que no hay', () => {
    const t = textoRango(sombrilla({ desde: null, hasta: null }))
    expect(t).toContain('amárralos a su frente')
  })
})

describe('textoFueraDelPlan', () => {
  it('dice cuánto no entra al plan y qué lotes lo causan', () => {
    const t = textoFueraDelPlan(
      sombrilla({
        valorFueraDelPlan: 25,
        lotesFueraDelPlan: [{ nombre: 'Cerámica', modalidad: 'no_contratable', valor: 25 }],
      }),
      valor,
    )
    expect(t).toContain('$25')
    expect(t).toContain('Cerámica (no_contratable)')
  })

  it('calla cuando no hay nada fuera: no se inventa una advertencia', () => {
    expect(textoFueraDelPlan(sombrilla(), valor)).toBe('')
  })
})

describe('textoAvance', () => {
  it('da el avance agregado del sombrilla', () => {
    expect(textoAvance(sombrilla())).toBe('7 de 28 pasos cumplidos (25 %).')
  })

  it('no dice nada sin pasos: un 0 % sin proceso detrás parece un proceso atascado', () => {
    expect(textoAvance(sombrilla({ avance: null, pasos: 0, pasosCumplidos: 0 }))).toBe('')
  })
})

describe('sePuedeBorrar y avisoAlBorrar', () => {
  it('el «Resto» no se borra por su cuenta', () => {
    expect(sePuedeBorrar(lote(9, 'Resto de Pisos', true))).toBe(false)
    expect(sePuedeBorrar(lote(1, 'Porcelanato'))).toBe(true)
  })

  it('con varios lotes, borrar uno solo devuelve sus insumos al Resto', () => {
    const lotes = [lote(1, 'Porcelanato'), lote(2, 'Gres'), lote(9, 'Resto de Pisos', true)]
    expect(avisoAlBorrar(lotes, 1)).toContain('volverán al lote «Resto»')
  })

  it('avisa ANTES de que borrar el último lote deshaga la partición entera', () => {
    // Si no se avisa, quien borre el último ve desaparecer también el «Resto» y no entiende por qué.
    const lotes = [lote(1, 'Porcelanato'), lote(9, 'Resto de Pisos', true)]
    expect(avisoAlBorrar(lotes, 1)).toContain('deja de estar partido')
  })
})
