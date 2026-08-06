import { useCallback, useEffect, useState } from 'react'
import { PdcApiError, apiGet, apiPost } from '../lib/api'
import { moneda } from '../lib/agGrid'
import { Selector } from './Selector'
import {
  avisoAlBorrar,
  motivoNoPartir,
  nombresDePartir,
  sePuedeBorrar,
  textoAvance,
  textoFueraDelPlan,
  textoRango,
  type RespuestaSubpaquetes,
  type Subpaquete,
} from '../lib/subpaquetes'
import type { InsumoPaquete } from '../lib/types'

const mensajeError = (e: unknown) => (e instanceof Error ? e.message : String(e))

/** Las cuatro modalidades, con las palabras del dominio y no las del enum. */
const MODALIDADES: { value: string; label: string }[] = [
  { value: 'contrato', label: 'Contrato' },
  { value: 'orden_compra', label: 'Orden de compra' },
  { value: 'consumo_directo', label: 'Consumo directo' },
  { value: 'no_contratable', label: 'No contratable' },
]

type Props = {
  paqueteId: number
  paqueteNombre: string
  /** Para que la pantalla recargue cobertura y resumen cuando algo cambia aquí. */
  onCambio: () => void
}

/**
 * Partir un paquete de preconstrucción en los lotes que la obra de verdad contrata, y repartirle sus
 * insumos.
 *
 * Vive como panel dentro de la lista de «Paquetes con insumos» y no como pantalla propia: partir es
 * una decisión que se toma mirando los insumos del paquete —«aquí había porcelanato, porcelanato,
 * tableta gres, cerámica»—, y mandar al usuario a otra ruta le quita de delante justo lo que necesita
 * ver para decidir.
 */
export default function SubpaquetesPanel({ paqueteId, paqueteNombre, onCambio }: Props) {
  const [datos, setDatos] = useState<RespuestaSubpaquetes | null>(null)
  const [cargando, setCargando] = useState(true)
  const [error, setError] = useState('')
  const [nombres, setNombres] = useState('')
  const [ocupado, setOcupado] = useState(false)
  const [destino, setDestino] = useState<number | ''>('')
  const [marcados, setMarcados] = useState<Set<string>>(new Set())
  const [delPaquete, setDelPaquete] = useState<InsumoPaquete[]>([])

  const clave = (i: InsumoPaquete) => `${i.descripcionNorm}|${i.unidad}`

  // Los insumos se piden aquí y con el filtro `asignados`, en vez de recibirlos de la pantalla.
  // Recibirlos parecía más barato y estaba mal: la lista de la pantalla depende del filtro que el
  // usuario tenga puesto, y con «sin asignar» —el filtro con el que la pantalla arranca cuando falta
  // trabajo— este panel veía cero insumos en un paquete que tiene siete. Lo que hay que repartir es
  // justo lo que YA está asignado a este paquete.
  const cargar = useCallback(async () => {
    setCargando(true)
    try {
      const [d, ins] = await Promise.all([
        apiGet<RespuestaSubpaquetes>(`/plan-compras/api/subpaquetes?paqueteId=${paqueteId}`),
        apiGet<{ insumos: InsumoPaquete[] }>('/plan-compras/api/paquetes/insumos?filtro=asignados'),
      ])
      setDatos(d)
      setDelPaquete(ins.insumos.filter((i) => i.paqueteId === paqueteId))
      setError('')
    } catch (e) {
      setError(mensajeError(e))
    } finally {
      setCargando(false)
    }
  }, [paqueteId])

  useEffect(() => {
    void cargar()
  }, [cargar])

  // Toda mutación pasa por aquí: recarga los lotes y avisa a la pantalla para que la cobertura y el
  // resumen no se queden contando lo de antes. Un panel que se actualiza solo y deja la cabecera
  // vieja es peor que uno que no se actualiza, porque los dos números se contradicen en pantalla.
  const accion = async (fn: () => Promise<unknown>) => {
    setOcupado(true)
    try {
      await fn()
      await cargar()
      onCambio()
      setError('')
    } catch (e) {
      setError(e instanceof PdcApiError ? e.message : mensajeError(e))
    } finally {
      setOcupado(false)
    }
  }

  const lotes = datos?.subpaquetes ?? []
  const partido = lotes.length > 0
  const resumen = datos?.resumen ?? null

  if (cargando) return <p className="pdc-sub">Cargando lotes…</p>

  return (
    <div className="pdc-sub-panel" data-testid={`pdc-sub-panel-${paqueteId}`}>
      {error !== '' && (
        <div className="pdc-error" role="alert">
          {error}
        </div>
      )}

      {!partido && (
        <>
          <p className="pdc-sub">
            Este paquete no está partido: se contrata entero. Pártelo si la obra va a contratarlo por
            separado —distintos proveedores, marcas o momentos—. Los insumos que no muevas a ningún
            lote quedarán en un lote «Resto», que se sigue contratando.
          </p>
          <div className="pdc-sub-partir">
            <label htmlFor={`pdc-sub-nombres-${paqueteId}`}>
              Nombres de los lotes (uno por línea o separados por coma)
            </label>
            <textarea
              id={`pdc-sub-nombres-${paqueteId}`}
              data-testid="pdc-sub-nombres"
              rows={3}
              value={nombres}
              placeholder={'Porcelanato\nTableta gres\nCerámica'}
              onChange={(e) => setNombres(e.target.value)}
            />
            <div className="pdc-sub-acciones">
              <button
                type="button"
                data-testid="pdc-sub-partir"
                disabled={ocupado || motivoNoPartir(nombres) !== ''}
                title={motivoNoPartir(nombres)}
                onClick={() =>
                  void accion(async () => {
                    await apiPost('/plan-compras/api/subpaquetes/partir', {
                      paqueteId,
                      nombres: nombresDePartir(nombres),
                    })
                    setNombres('')
                  })
                }
              >
                Partir en {nombresDePartir(nombres).length || '…'} lotes
              </button>
              {motivoNoPartir(nombres) !== '' && (
                <span className="pdc-sub-motivo">{motivoNoPartir(nombres)}</span>
              )}
            </div>
          </div>
        </>
      )}

      {partido && resumen !== null && (
        <>
          {/* El sombrilla resume y no se contrata: es la lectura de preconstrucción que el comité
              quería conservar. Rango, avance agregado y —lo que hace honesta la pantalla— cuánto de
              su valor no entra al plan de fechas y por culpa de qué lote. */}
          <dl className="pdc-sub-resumen" data-testid="pdc-sub-resumen">
            <div>
              <dt>Lotes</dt>
              <dd>{resumen.lotes}</dd>
            </div>
            <div>
              <dt>Valor del paquete</dt>
              <dd>{moneda(resumen.valorTotal)}</dd>
            </div>
            <div>
              <dt>Rango de fechas</dt>
              <dd>{textoRango(resumen)}</dd>
            </div>
            {textoAvance(resumen) !== '' && (
              <div>
                <dt>Avance agregado</dt>
                <dd>{textoAvance(resumen)}</dd>
              </div>
            )}
          </dl>

          {textoFueraDelPlan(resumen, moneda) !== '' && (
            <p className="pdc-sub-fuera" data-testid="pdc-sub-fuera" role="status">
              {textoFueraDelPlan(resumen, moneda)}
            </p>
          )}

          <table className="pdc-sub-tabla" data-testid="pdc-sub-tabla">
            <caption className="pdc-sub">
              Cada lote se contrata por separado y tiene su propio frente y sus propias fechas.
              Amárralos en «Plan».
            </caption>
            <thead>
              <tr>
                <th scope="col">Lote</th>
                <th scope="col">Modalidad</th>
                <th scope="col" className="pdc-num">
                  Insumos
                </th>
                <th scope="col" className="pdc-num">
                  Valor
                </th>
                <th scope="col">
                  <span className="pdc-sr">Acciones</span>
                </th>
              </tr>
            </thead>
            <tbody>
              {lotes.map((l: Subpaquete) => (
                <tr key={l.subpaqueteId} className={l.esResto ? 'pdc-sub-resto' : undefined}>
                  <th scope="row">
                    {l.nombre}
                    {l.esResto && <span className="pdc-sub-tag">Resto</span>}
                    {!l.generaProceso && <span className="pdc-sub-tag pdc-sub-tag--fuera">sin proceso</span>}
                  </th>
                  <td>
                    <Selector
                      value={l.modalidad}
                      onChange={(v) =>
                        void accion(() =>
                          apiPost('/plan-compras/api/subpaquetes/actualizar', {
                            subpaqueteId: l.subpaqueteId,
                            modalidad: v,
                          }),
                        )
                      }
                      opciones={MODALIDADES.map((m) => ({ valor: m.value, etiqueta: m.label }))}
                      etiqueta={`Modalidad de ${l.nombre}`}
                      disabled={ocupado}
                    />
                  </td>
                  <td className="pdc-num">{l.insumos}</td>
                  <td className="pdc-num">{moneda(l.valor)}</td>
                  <td>
                    {sePuedeBorrar(l) && (
                      <button
                        type="button"
                        className="pdc-sub-borrar"
                        data-testid={`pdc-sub-borrar-${l.subpaqueteId}`}
                        disabled={ocupado}
                        title={avisoAlBorrar(lotes, l.subpaqueteId)}
                        onClick={() => {
                          // El aviso va ANTES de borrar y dice la consecuencia real, que en el último
                          // lote es deshacer la partición entera.
                          if (!window.confirm(`Borrar «${l.nombre}». ${avisoAlBorrar(lotes, l.subpaqueteId)}`)) {
                            return
                          }
                          void accion(() =>
                            apiPost('/plan-compras/api/subpaquetes/eliminar', { subpaqueteId: l.subpaqueteId }),
                          )
                        }}
                      >
                        Borrar
                      </button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          <div className="pdc-sub-agregar">
            <label htmlFor={`pdc-sub-nuevo-${paqueteId}`}>Agregar otro lote</label>
            <input
              id={`pdc-sub-nuevo-${paqueteId}`}
              data-testid="pdc-sub-nuevo"
              value={nombres}
              placeholder="Nombre del lote"
              onChange={(e) => setNombres(e.target.value)}
            />
            <button
              type="button"
              data-testid="pdc-sub-agregar"
              disabled={ocupado || nombres.trim() === ''}
              onClick={() =>
                void accion(async () => {
                  await apiPost('/plan-compras/api/subpaquetes/agregar', {
                    paqueteId,
                    nombre: nombres.trim(),
                    modalidad: 'contrato',
                  })
                  setNombres('')
                })
              }
            >
              Agregar
            </button>
          </div>

          {/* Repartir los insumos. La lista es la del propio paquete y no la del proyecto entero:
              partir se decide mirando estos insumos, y traer los 800 del presupuesto aquí obligaría a
              buscar en una lista donde el 99 % no viene al caso. */}
          <fieldset className="pdc-sub-repartir" data-testid="pdc-sub-repartir">
            <legend>Repartir insumos ({delPaquete.length} en este paquete)</legend>
            {delPaquete.length === 0 && (
              <p className="pdc-vacio">Este paquete no tiene insumos asignados todavía.</p>
            )}
            {delPaquete.length > 0 && (
              <>
                <ul className="pdc-sub-insumos">
                  {delPaquete.map((i) => (
                    <li key={clave(i)}>
                      <label>
                        <input
                          type="checkbox"
                          checked={marcados.has(clave(i))}
                          onChange={(e) => {
                            const s = new Set(marcados)
                            if (e.target.checked) s.add(clave(i))
                            else s.delete(clave(i))
                            setMarcados(s)
                          }}
                        />
                        {i.descripcion} <span className="pdc-sub-um">{i.unidad}</span>{' '}
                        <span className="pdc-sub-valor">{moneda(i.valorTotal)}</span>
                      </label>
                    </li>
                  ))}
                </ul>
                <div className="pdc-sub-acciones">
                  <span className="pdc-selector">
                    <Selector
                      value={destino === '' ? '' : String(destino)}
                      onChange={(v) => setDestino(v === '' ? '' : Number(v))}
                      opciones={lotes.map((l) => ({ valor: String(l.subpaqueteId), etiqueta: l.nombre }))}
                      etiqueta="Lote destino"
                      placeholder="Mover los marcados a…"
                      testid="pdc-sub-destino"
                    />
                  </span>
                  <button
                    type="button"
                    data-testid="pdc-sub-mover"
                    disabled={ocupado || destino === '' || marcados.size === 0}
                    onClick={() =>
                      void accion(async () => {
                        await apiPost('/plan-compras/api/subpaquetes/mover', {
                          subpaqueteId: destino,
                          insumos: delPaquete
                            .filter((i) => marcados.has(clave(i)))
                            .map((i) => ({ descripcionNorm: i.descripcionNorm, unidad: i.unidad })),
                        })
                        setMarcados(new Set())
                        setDestino('')
                      })
                    }
                  >
                    Mover {marcados.size > 0 ? marcados.size : ''}
                  </button>
                </div>
              </>
            )}
          </fieldset>
        </>
      )}

      <p className="pdc-sub-nota">
        Los lotes son de esta obra: no se agregan al maestro de paquetes de la empresa, y el motor de
        sugerencias sigue aprendiendo a nivel de «{paqueteNombre}».
      </p>
    </div>
  )
}
