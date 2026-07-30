import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { apiGet, apiPost, PdcApiError } from '../lib/api'
import { agregar, aPayload, disponibles, mover, quitar, validar, type PasoEditable } from '../lib/pasosState'
import type { DuracionCatalogo, EntradaHistorialPasos, OrigenCopia, PasoCatalogo, PreviewCopia, RespuestaPasos } from '../lib/types'

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
      await cargarHistorial()
    } catch (e) {
      setError((e as PdcApiError).message)
    } finally {
      setOcupado(false)
    }
  }

  // ── Copiar de otra obra (A4.1 · diferido nº 2) ────────────────────────────
  // Copia puntual, no vínculo vivo: una vez copiada, editar esta obra no toca la de origen.
  const [origenes, setOrigenes] = useState<OrigenCopia[]>([])
  const [origenElegido, setOrigenElegido] = useState<number | ''>('')
  // `null` = no hay nada previsualizado. El diseño exige enseñar QUÉ se copia antes de copiarlo,
  // porque una obra origen a medias contagia su hueco.
  const [preview, setPreview] = useState<PreviewCopia | null>(null)

  useEffect(() => {
    // Un 403 aquí es normal y no es un error que mostrar: significa que este usuario no tiene el
    // permiso de reglas, así que el bloque de copia sencillamente no aparece.
    void apiGet<{ origenes: OrigenCopia[] }>('/plan-compras/api/plan/pasos/origenes')
      .then((d) => setOrigenes(d.origenes))
      .catch(() => setOrigenes([]))
  }, [])

  const onPrevisualizarCopia = async () => {
    if (origenElegido === '') return
    setOcupado(true)
    setError('')
    setMensaje('')
    try {
      setPreview(await apiGet<PreviewCopia>(`/plan-compras/api/plan/pasos/copia-preview?origenId=${origenElegido}`))
    } catch (e) {
      setError((e as PdcApiError).message)
    } finally {
      setOcupado(false)
    }
  }

  const onCopiar = async () => {
    if (origenElegido === '') return
    setOcupado(true)
    setError('')
    setMensaje('')
    try {
      const r = await apiPost<{ pasos: number; calculados: number }>('/plan-compras/api/plan/pasos/copiar', {
        origenId: origenElegido,
      })
      setPreview(null)
      await cargar()
      await cargarHistorial()
      setMensaje(`Copiados ${r.pasos} pasos. Se recalcularon ${r.calculados} paquetes.`)
    } catch (e) {
      setError((e as PdcApiError).message)
    } finally {
      setOcupado(false)
    }
  }

  // ── Duraciones del catálogo (A4.1 · diferido nº 4) ─────────────────────────
  // Hasta ahora había que entrar a la base para cambiar un número que mueve las fechas de toda la
  // obra. Solo se ofrecen las filas que los paquetes de ESTA obra usan.
  const [duraciones, setDuraciones] = useState<DuracionCatalogo[]>([])

  const cargarDuraciones = async () => {
    // Igual que los orígenes de copia: un 403 solo significa que este usuario no tiene el permiso
    // de reglas, y entonces el bloque no aparece. No es un error que mostrar.
    await apiGet<{ duraciones: DuracionCatalogo[] }>('/plan-compras/api/plan/duraciones')
      .then((d) => setDuraciones(d.duraciones))
      .catch(() => setDuraciones([]))
  }

  useEffect(() => {
    void cargarDuraciones()
  }, [])

  const onGuardarDuracion = async (ref: number, columna: string, valor: number) => {
    setOcupado(true)
    setError('')
    setMensaje('')
    try {
      const r = await apiPost<{ calculados: number }>('/plan-compras/api/plan/duraciones', {
        duracionRef: ref,
        dias: { [columna]: valor },
      })
      await cargarDuraciones()
      setMensaje(`Duración guardada. Se recalcularon ${r.calculados} paquetes de esta obra.`)
    } catch (e) {
      setError((e as PdcApiError).message)
      // La pantalla vuelve a lo que hay guardado: dejar el número tecleado sobre un guardado que
      // falló haría creer que la fecha del plan ya se movió.
      await cargarDuraciones()
    } finally {
      setOcupado(false)
    }
  }

  // ── Historial de la configuración (A4.1 · diferido nº 3) ───────────────────
  // Existe para contestar «¿por qué se movieron mis fechas?». Se recarga tras cada cambio.
  const [historial, setHistorial] = useState<EntradaHistorialPasos[]>([])

  const cargarHistorial = async () => {
    await apiGet<{ historial: EntradaHistorialPasos[] }>('/plan-compras/api/plan/pasos/historial')
      .then((d) => setHistorial(d.historial))
      .catch(() => setHistorial([]))
  }

  useEffect(() => {
    void cargarHistorial()
  }, [])

  const onRestablecer = async () => {
    setOcupado(true)
    setError('')
    setMensaje('')
    try {
      const r = await apiPost<{ calculados: number }>('/plan-compras/api/plan/pasos/restablecer', {})
      await cargar()
      await cargarHistorial()
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

      {/* La pregunta que este bloque existe para contestar es «¿por qué se movieron mis fechas?».
          Va con el guard de lectura: enterarse no exige poder cambiar nada. */}
      {historial.length > 0 && (
        <details className="pdc-pasos-historial" data-testid="pdc-pasos-historial">
          <summary>Historial de cambios ({historial.length})</summary>
          <ol className="pdc-paq-lista" data-testid="pdc-pasos-historial-lista">
            {historial.map((h) => (
              <li key={h.id}>
                <strong>{h.cuando}</strong>
                <span className="pdc-paq-meta">{h.usuario}</span>
                <span className="pdc-paq-meta">
                  {h.pasos.length === 0
                    ? 'volvió al proceso por defecto de la empresa'
                    : h.pasos.map((p) => (p.alias !== '' ? p.alias : p.clave)).join(' → ')}
                </span>
              </li>
            ))}
          </ol>
        </details>
      )}

      {/* Cambiar un número de aquí mueve las fechas de todas las obras que usen esa fila, no solo
          de esta. El aviso es permanente a propósito: es la advertencia, no una decoración. */}
      {duraciones.length > 0 && (
        <details className="pdc-pasos-duraciones" data-testid="pdc-duraciones">
          <summary>Duraciones del catálogo de la empresa</summary>
          <p className="pdc-sub" data-testid="pdc-duraciones-aviso">
            Estas duraciones son de la empresa, no de esta obra: cambiarlas mueve las fechas de todas
            las obras cuyos paquetes las usen. Aquí ves {duraciones.length} porque son las que usan
            los paquetes de esta obra.
          </p>
          <table className="pdc-duraciones-tabla">
            <thead>
              <tr>
                <th scope="col">Paquete del catálogo</th>
                {pasos
                  .filter((p) => p.colLegacy !== null)
                  .map((p) => <th scope="col" key={p.clave}>{p.nombre}</th>)}
              </tr>
            </thead>
            <tbody>
              {duraciones.map((d) => (
                <tr key={d.duracionRef} data-testid={`pdc-duracion-${d.duracionRef}`}>
                  <th scope="row">
                    {d.paqueteContratacion}
                    <span className="pdc-paq-meta">
                      {d.paquetesQueLaUsan === 1
                        ? '1 paquete de esta obra la usa'
                        : `${d.paquetesQueLaUsan} paquetes de esta obra la usan`}
                    </span>
                  </th>
                  {pasos
                    .filter((p) => p.colLegacy !== null)
                    .map((p) => (
                      <td key={p.clave}>
                        <input
                          type="number"
                          min={0}
                          aria-label={`${p.nombre} de ${d.paqueteContratacion}`}
                          data-testid={`pdc-duracion-${d.duracionRef}-${p.colLegacy}`}
                          disabled={ocupado}
                          defaultValue={d.dias[p.colLegacy as string] ?? ''}
                          // Se guarda al salir del campo y no en cada tecla: cada guardado recalcula
                          // el plan de la obra entera.
                          onBlur={(e) => {
                            const n = Number(e.target.value)
                            if (e.target.value === '' || !Number.isInteger(n) || n < 0) return
                            if (n === d.dias[p.colLegacy as string]) return
                            void onGuardarDuracion(d.duracionRef, p.colLegacy as string, n)
                          }}
                        />
                      </td>
                    ))}
                </tr>
              ))}
            </tbody>
          </table>
        </details>
      )}

      {/* Montar la segunda obra empieza por querer partir de lo que ya funcionó en la primera. El
          bloque no aparece si no hay ninguna obra configurada que este usuario pueda ver. */}
      {origenes.length > 0 && (
        <details className="pdc-pasos-copiar" data-testid="pdc-pasos-copiar">
          <summary>Copiar la configuración de otra obra</summary>
          <p className="pdc-sub">
            Se copia una vez y se queda quieta: después puedes editarla aquí sin que la obra de
            origen se entere, y sin que lo que hagas allá vuelva a esta.
          </p>
          <label>
            Obra de origen{' '}
            <select
              data-testid="pdc-pasos-copiar-origen"
              value={origenElegido}
              onChange={(e) => {
                setOrigenElegido(e.target.value === '' ? '' : Number(e.target.value))
                setPreview(null)
              }}
            >
              <option value="">Elige una obra…</option>
              {origenes.map((o) => (
                <option key={o.projectId} value={o.projectId}>
                  {o.nombre} ({o.pasos} pasos)
                </option>
              ))}
            </select>
          </label>
          <button
            type="button"
            data-testid="pdc-pasos-copiar-preview"
            disabled={ocupado || origenElegido === ''}
            onClick={() => void onPrevisualizarCopia()}
          >
            Ver qué se copiaría
          </button>

          {preview !== null && (
            <div className="pdc-panel" data-testid="pdc-pasos-preview-copia">
              <p>
                Se copiarían estos {preview.pasos.length} pasos, reemplazando el proceso actual de
                esta obra:
              </p>
              <ol data-testid="pdc-pasos-preview-lista">
                {preview.pasos.map((p) => (
                  <li key={p.clave}>
                    {p.alias !== '' ? `${p.alias} (${p.nombre})` : p.nombre}
                    {p.diasFijos !== null && (
                      <span className="pdc-paq-meta">{p.diasFijos} día(s) fijos</span>
                    )}
                    {!p.tieneCatalogo && p.diasFijos === null && (
                      <span className="pdc-paq-meta">sin duración definida</span>
                    )}
                  </li>
                ))}
              </ol>
              {preview.incompleta && (
                <p role="status" data-testid="pdc-pasos-preview-incompleta">
                  Ojo: esa obra tiene algún paso sin duración definida. Al copiarla, esta obra hereda
                  ese hueco y sus fechas saldrán estimadas hasta que lo llenes.
                </p>
              )}
              <button
                type="button"
                className="pdc-paq-primario"
                data-testid="pdc-pasos-copiar-confirmar"
                disabled={ocupado}
                onClick={() => void onCopiar()}
              >
                Copiar a esta obra
              </button>
              <button type="button" data-testid="pdc-pasos-copiar-cancelar" onClick={() => setPreview(null)}>
                Cancelar
              </button>
            </div>
          )}
        </details>
      )}

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
