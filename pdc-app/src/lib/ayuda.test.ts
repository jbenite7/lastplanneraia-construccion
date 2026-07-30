import { describe, expect, it } from 'vitest'
import { AYUDAS, PANTALLAS_AYUDA, ayudaDe } from './ayuda'

// La jerga que NO puede aparecer en algo que lee un residente de obra. Son nombres de columnas,
// de tablas y de oficio de programador: si uno se escapa al texto visible, la ayuda deja de
// explicar y empieza a exigir que el lector sepa cómo está hecho el sistema por dentro.
const JERGA = [
  'duracion_ref', 'project_id', 'upsert', 'endpoint', 'commit', 'JOIN', 'SQL',
  'localStorage', 'API', 'backend', 'frontend', 'nullable', 'id',
]

describe('contenido de la ayuda', () => {
  it('cubre las ocho pantallas de la SPA, ni una menos', () => {
    expect(PANTALLAS_AYUDA).toEqual([
      'importar', 'maestro', 'presupuesto', 'comparar',
      'paquetes', 'plan', 'pasos', 'seguimiento',
    ])
    expect(Object.keys(AYUDAS).sort()).toEqual([...PANTALLAS_AYUDA].sort())
  })

  it.each(PANTALLAS_AYUDA)('«%s» responde las tres preguntas sin dejar huecos', (id) => {
    const a = ayudaDe(id)
    expect(a.titulo.trim().length).toBeGreaterThan(0)
    // Umbrales bajos a propósito: no premian la palabrería, solo atrapan el hueco y la
    // frase-de-relleno de tres palabras que no explica nada.
    expect(a.queHace.trim().length).toBeGreaterThan(40)
    expect(a.quePasaDespues.trim().length).toBeGreaterThan(40)
    expect(a.queHagoYo.length).toBeGreaterThan(0)
    a.queHagoYo.forEach((paso) => expect(paso.trim().length).toBeGreaterThan(10))
  })

  it.each(PANTALLAS_AYUDA)('«%s» está escrita sin jerga', (id) => {
    const a = ayudaDe(id)
    const todo = [a.titulo, a.queHace, a.quePasaDespues, ...a.queHagoYo,
      ...a.apartados.flatMap((s) => [s.etiqueta, s.texto])].join(' ')
    // Palabra completa: «id» no debe cazar «identifica», ni «API» cazar «rápido».
    JERGA.forEach((termino) => {
      const suelta = new RegExp(`(^|[^\\p{L}])${termino}([^\\p{L}]|$)`, 'iu')
      expect(suelta.test(todo), `«${termino}» aparece en la ayuda de ${id}`).toBe(false)
    })
  })

  it('las tres superficies costosas tienen apartado propio', () => {
    // Donde el usuario decide algo caro, el apartado no es opcional.
    expect(ayudaDe('plan').apartados.map((s) => s.etiqueta)).toContain('Desfases')
    expect(ayudaDe('seguimiento').apartados.map((s) => s.etiqueta)).toContain('Flujo de caja')
    expect(ayudaDe('importar').apartados.map((s) => s.etiqueta))
      .toContain('Impacto sobre el trabajo ya hecho')
  })

  it('no reescribe la advertencia del flujo de caja: la señala', () => {
    const flujo = ayudaDe('seguimiento').apartados.find((s) => s.etiqueta === 'Flujo de caja')
    expect(flujo).toBeDefined()
    // Si la ayuda repitiera el método, tendríamos dos versiones de la misma advertencia
    // envejeciendo por separado. La de la pantalla la manda el servidor y es la única.
    expect(flujo!.texto).toMatch(/aviso|advertencia/i)
    expect(flujo!.texto).not.toMatch(/prorrata|lineal/i)
  })

  it('no repite el mensaje del desplegable vacío de «Sin frente»', () => {
    // `motivoSinAnclas()` ya nombra la causa exacta en el momento en que pasa. Repetir aquí las
    // tres causas crearía una segunda copia que nadie va a mantener sincronizada.
    const sinFrente = ayudaDe('plan').apartados.find((s) => s.etiqueta === 'Sin frente')
    expect(sinFrente).toBeDefined()
    expect(sinFrente!.texto).not.toMatch(/semana activa|permiso|falló la petición/i)
  })

  it('no documenta lo que todavía no existe', () => {
    const todo = JSON.stringify(AYUDAS)
    expect(todo).not.toMatch(/subpaquete/i)
  })

  it('no usa las palabras de dentro para cosas que sí se ven', () => {
    // «Reenganchado» es como se llama por dentro el vínculo que vuelve de pendiente a automático.
    // Desde que el resumen de la carga del maestro muestra ese número, la ayuda SÍ lo explica —pero
    // con las palabras de la pantalla, «pendientes que se resolvieron solos»—. La regla no es
    // callar el dato: es no obligar al lector a aprender el vocabulario del sistema.
    expect(JSON.stringify(AYUDAS)).not.toMatch(/reenganch/i)
  })

  it('ayudaDe falla fuerte si le piden una pantalla que no existe', () => {
    // @ts-expect-error — comprobamos la guarda en tiempo de ejecución, no el tipo
    expect(() => ayudaDe('inventada')).toThrow(/inventada/)
  })
})
