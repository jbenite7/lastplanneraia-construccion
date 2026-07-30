import { describe, expect, it } from 'vitest'
import { CLAVE_RECORRIDO, PASOS_RECORRIDO, leerVisto, marcarVisto, olvidarVisto } from './recorrido'

/** Un almacén de mentira, para no depender del navegador ni ensuciar entre pruebas. */
function almacenFalso(inicial: Record<string, string> = {}): Storage {
  const datos = new Map(Object.entries(inicial))
  return {
    get length() { return datos.size },
    clear: () => datos.clear(),
    getItem: (k: string) => datos.get(k) ?? null,
    key: (i: number) => [...datos.keys()][i] ?? null,
    removeItem: (k: string) => { datos.delete(k) },
    setItem: (k: string, v: string) => { datos.set(k, v) },
  } as Storage
}

describe('recorrido guiado', () => {
  it('recorre el flujo en el orden en que se trabaja', () => {
    expect(PASOS_RECORRIDO.map((p) => p.pantalla)).toEqual([
      'importar', 'maestro', 'presupuesto', 'paquetes', 'plan', 'seguimiento',
    ])
    PASOS_RECORRIDO.forEach((p) => {
      expect(p.ruta.startsWith('/')).toBe(true)
      expect(p.texto.trim().length).toBeGreaterThan(30)
    })
  })

  it('de entrada, no se ha visto', () => {
    expect(leerVisto(almacenFalso())).toBe(false)
  })

  it('marcarlo lo recuerda', () => {
    const almacen = almacenFalso()
    marcarVisto(almacen)
    expect(leerVisto(almacen)).toBe(true)
    expect(almacen.getItem(CLAVE_RECORRIDO)).toBe('visto')
  })

  it('relanzarlo desde la ayuda lo vuelve a poner en no visto', () => {
    const almacen = almacenFalso({ [CLAVE_RECORRIDO]: 'visto' })
    olvidarVisto(almacen)
    expect(leerVisto(almacen)).toBe(false)
  })

  it('un valor guardado que no reconoce se trata como no visto', () => {
    // Defensa contra basura de una versión anterior o de otra pestaña: solo 'visto' cuenta.
    expect(leerVisto(almacenFalso({ [CLAVE_RECORRIDO]: 'true' }))).toBe(false)
    expect(leerVisto(almacenFalso({ [CLAVE_RECORRIDO]: '' }))).toBe(false)
  })

  it('sin almacén disponible no revienta: asume no visto y sigue', () => {
    // En navegación privada o con las cookies bloqueadas, tocar el almacén del navegador lanza. La
    // ayuda no puede tumbar el módulo por eso; como mucho, el recorrido saldrá otra vez.
    const roto = {
      getItem: () => { throw new Error('bloqueado') },
      setItem: () => { throw new Error('bloqueado') },
      removeItem: () => { throw new Error('bloqueado') },
    } as unknown as Storage
    expect(leerVisto(roto)).toBe(false)
    expect(() => marcarVisto(roto)).not.toThrow()
    expect(() => olvidarVisto(roto)).not.toThrow()
    expect(leerVisto(null)).toBe(false)
  })
})
