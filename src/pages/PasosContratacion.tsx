import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { apiGet, apiPost, PdcApiError } from '../lib/api'
import { agregar, aPayload, disponibles, mover, quitar, validar, type PasoEditable } from '../lib/pasosState'
import type { PasoCatalogo, RespuestaPasos } from '../lib/types'

/**
 * A4.1 — el proceso de contratación de esta obra.
 *
 * Fuera de la barra de pestañas a propósito: se configura una vez por obra y casi no se vuelve a
 * tocar, así que ocupar una pestaña permanente sería caro. Se llega desde el Plan de compras.
 */
export default function PasosContratacion() {
  const [cat, setCat] = useState<PasoCatalogo[]>([])
  const [pasos, setPasos] = useState<PasoEditable[]>([])
  /** Las claves tal como estaban guardadas, para poder avisar de lo que se está quitando. */
  const [original, setOriginal] = useState<string[]>([])
  const [paquetesConPlan, setPaquetesConPlan] = useState(0)
  const [configurado, setConfigurado] = useState(false)
  const [ocupado, setOcupado] = useState(false)
  const [mensaje, setMensaje] = useState('')
  const [error, setError] = useState('')

  const cargar = async () => {
    const d = await apiGet<RespuestaPasos>('/plan-compras/api/plan/pasos')
    setCat(d.catalogo)
    setConfigurado(d.configurado)
    setPaquetesConPlan(d.paquetesConPlan)
    setOriginal(d.proyecto.map((p) => p.clave))
    setPasos(
      d.proyecto.map((p, i) => ({
        clave: p.clave,
        nombre: p.nombre,
        alias: '',
        colLegacy: p.colLegacy,
        diasFijos: p.diasFijos,
        diasSugeridos: null,
        // El orden canónico vive en el catálogo, no en la lista de la obra: se cruza por clave para
        // que `agregar()` sepa dónde insertar. Si un paso no estuviera en el catálogo, su posición
        // actual sirve de aproximación y no rompe nada.
        ordenDefault: d.catalogo.find((c) => c.clave === p.clave)?.ordenDefault ?? i,
      })),
    )
  }

  useEffect(() => {
    void cargar().catch((e: PdcApiError) => setError(e.message))
  }, [])

  // Quitar un paso borra su fila en cada paquete con plan. El número importa: «se borrarán filas» no
  // le dice a nadie si está a punto de perder tres fechas o trescientas.
  const quitados = original.filter((c) => !pasos.some((p) => p.clave === c))

  const onGuardar = async () => {
    const v = validar(pasos)
    if (!v.ok) {
      setError(v.mensaje ?? '')
      return
    }
    setOcupado(true)
    setError('')
    setMensaje('')
    try {
      const r = await apiPost<{ pasos: number; calculados: number }>('/plan-compras/api/plan/pasos', {
        pasos: aPayload(pasos),
      })
      setMensaje(`Guardado: ${r.pasos} pasos. Se recalcularon ${r.calculados} paquetes.`)
      await cargar()
    } catch (e) {
      setError((e as PdcApiError).message)
    } finally {
      setOcupado(false)
    }
  }

  const onRestablecer = async () => {
    setOcupado(true)
    setError('')
    setMensaje('')
    try {
      const r = await apiPost<{ calculados: number }>('/plan-compras/api/plan/pasos/restablecer', {})
      await cargar()
      setMensaje(`La obra vuelve al proceso por defecto de la empresa. Se recalcularon ${r.calculados} paquetes.`)
    } catch (e) {
      setError((e as PdcApiError).message)
    } finally {
      setOcupado(false)
    }
  }

  return (
    <section className="pdc-bloque pdc-pasos">
      <header className="pdc-paq-header">
        <div>
          <h1>Pasos del proceso de contratación</h1>
          <p className="pdc-sub">
            El camino que recorre cada paquete antes de llegar a obra. Cambiarlo mueve las fechas de
            todos los paquetes de esta obra.
          </p>
        </div>
        <Link to="/ensamble/plan" className="pdc-paq-secundario">
          Volver al plan
        </Link>
      </header>

      {/* El conteo sale de `original`, lo que hay GUARDADO, y no de la lista que se está editando:
          con `pasos.length` el aviso decía «usa el proceso por defecto (8 pasos)» en cuanto alguien
          agregaba uno sin guardar, que es justo cuando deja de ser cierto. */}
      {!configurado && original.length > 0 && (
        <p className="pdc-info" role="status" data-testid="pdc-pasos-por-defecto">
          Esta obra usa el proceso por defecto de la empresa ({original.length} pasos). El primer
          cambio que guardes crea su configuración propia.
        </p>
      )}
      {error !== '' && <div className="pdc-error" role="status">{error}</div>}
      {mensaje !== '' && <div className="pdc-info" role="status">{mensaje}</div>}

      <ol className="pdc-pasos-lista" data-testid="pdc-pasos-lista">
        {pasos.map((p, i) => (
          <li key={p.clave} className="pdc-pasos-fila">
            <span className="pdc-pasos-orden">{i + 1}</span>
            <span className="pdc-pasos-nombre">{p.nombre}</span>
            <input
              className="pdc-pasos-alias"
              type="text"
              value={p.alias}
              placeholder="Nombre en esta obra (opcional)"
              aria-label={`Nombre de «${p.nombre}» en esta obra`}
              onChange={(e) => setPasos(pasos.map((q, j) => (j === i ? { ...q, alias: e.target.value } : q)))}
            />
            {p.colLegacy === null ? (
              <label className="pdc-pasos-dias">
                Días
                <input
                  type="number"
                  min={0}
                  value={p.diasFijos ?? ''}
                  aria-label={`Días que dura «${p.nombre}» en esta obra`}
                  onChange={(e) =>
                    setPasos(
                      pasos.map((q, j) =>
                        j === i ? { ...q, diasFijos: e.target.value === '' ? null : Number(e.target.value) } : q,
                      ),
                    )
                  }
                />
              </label>
            ) : (
              // Los días salen del catálogo de la empresa y cambian por paquete (concreto no tarda lo
              // que unas puertas), así que aquí no hay un número único que mostrar.
              <span className="pdc-pasos-dias-catalogo">Días según el catálogo, por paquete</span>
            )}
            <button
              type="button"
              disabled={i === 0}
              aria-label={`Subir ${p.nombre}`}
              onClick={() => setPasos(mover(pasos, i, i - 1))}
            >
              ↑
            </button>
            <button
              type="button"
              disabled={i === pasos.length - 1}
              aria-label={`Bajar ${p.nombre}`}
              onClick={() => setPasos(mover(pasos, i, i + 1))}
            >
              ↓
            </button>
            <button type="button" aria-label={`Quitar ${p.nombre}`} onClick={() => setPasos(quitar(pasos, p.clave))}>
              Quitar
            </button>
          </li>
        ))}
      </ol>

      {/* El aviso de la respuesta 5 del grilleo, con el número delante. Cuando B1 registre avance
          real, esas mismas filas llevarán fechas reales: por eso se avisa antes de guardar. */}
      {quitados.length > 0 && (
        <p className="pdc-error" role="status" data-testid="pdc-pasos-aviso-quitar">
          Vas a quitar {quitados.length === 1 ? 'un paso' : `${quitados.length} pasos`} (
          {quitados.map((k) => cat.find((c) => c.clave === k)?.nombre ?? k).join(', ')}). Al guardar se
          borrarán {quitados.length * paquetesConPlan} fechas ya calculadas: una por cada uno de los{' '}
          {paquetesConPlan} paquetes con plan.
        </p>
      )}

      <div className="pdc-paq-toolbar">
        <select
          data-testid="pdc-pasos-agregar"
          value=""
          aria-label="Agregar un paso"
          onChange={(e) => {
            const c = cat.find((x) => x.clave === e.target.value)
            if (c) setPasos(agregar(pasos, c))
          }}
        >
          <option value="">Agregar un paso…</option>
          {disponibles(cat, pasos).map((c) => (
            <option key={c.clave} value={c.clave}>
              {c.nombre}
            </option>
          ))}
        </select>
        <button
          type="button"
          className="pdc-paq-primario"
          data-testid="pdc-pasos-guardar"
          disabled={ocupado}
          onClick={() => void onGuardar()}
        >
          Guardar y recalcular
        </button>
        {configurado && (
          <button
            type="button"
            data-testid="pdc-pasos-restablecer"
            disabled={ocupado}
            onClick={() => void onRestablecer()}
          >
            Volver al proceso por defecto
          </button>
        )}
      </div>
    </section>
  )
}
