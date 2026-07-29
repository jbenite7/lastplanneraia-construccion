import { useCallback, useEffect, useMemo, useState } from 'react'
import { AgGridReact } from 'ag-grid-react'
import { CellStyleModule, ModuleRegistry, RowStyleModule } from 'ag-grid-community'
import type { ColDef, RowClickedEvent } from 'ag-grid-community'
import { MODULOS_TABLA, autoSizeStrategy, columnaTexto, defaultColDef, pdcTheme, vacioTabla } from '../lib/agGrid'
import { PdcApiError, apiGet, apiPost } from '../lib/api'
import { getBootstrap } from '../lib/bootstrap'
import { etiquetaDesfaseDias, etiquetaEstado, filtrarSeguimiento, frentesDeSeguimiento } from '../lib/seguimiento'
import type { FilaSeguimiento, FiltrosSeguimiento, PasoSeguimiento } from '../lib/types'
import { plural } from '../lib/texto'

// Solo lectura en la grilla: el avance se registra en el panel de detalle, no en la celda. Por eso
// no se registra ningun modulo de edicion aqui.
ModuleRegistry.registerModules([...MODULOS_TABLA, CellStyleModule, RowStyleModule])

const mensajeError = (e: unknown) => (e instanceof Error ? e.message : String(e))

const SIN_FILTRO: FiltrosSeguimiento = { soloMios: false, frente: '', estado: '', soloAtrasados: false }

export default function Seguimiento() {
  const [filas, setFilas] = useState<FilaSeguimiento[]>([])
  const [filtros, setFiltros] = useState<FiltrosSeguimiento>(SIN_FILTRO)
  const [usuarioId, setUsuarioId] = useState<number | null>(null)
  const [abierto, setAbierto] = useState<FilaSeguimiento | null>(null)
  const [pasos, setPasos] = useState<PasoSeguimiento[]>([])
  const [cargando, setCargando] = useState(true)
  // Que paso tiene un POST en vuelo. Sin esto, dos clics seguidos en el mismo calendario mandan dos
  // peticiones y la segunda pisa la auditoria de la primera: queda registrada una fecha con la hora
  // y el usuario de otra escritura.
  const [guardando, setGuardando] = useState<number | null>(null)
  const [error, setError] = useState('')

  const cargar = useCallback(async () => {
    setCargando(true)
    try {
      const d = await apiGet<{ resumen: FilaSeguimiento[] }>('/plan-compras/api/seguimiento')
      setFilas(d.resumen)
      setError('')
    } catch (e) {
      setError(mensajeError(e))
    } finally {
      setCargando(false)
    }
  }, [])

  useEffect(() => {
    void cargar()
    // El id del usuario sale del bootstrap del modulo: es lo que hace posible el filtro «mis
    // paquetes» sin pedirle al servidor una consulta distinta.
    void getBootstrap().then((b) => setUsuarioId(b.usuarioId)).catch(() => setUsuarioId(null))
  }, [cargar])

  const abrir = useCallback(async (fila: FilaSeguimiento) => {
    setAbierto(fila)
    setPasos([])
    try {
      const d = await apiGet<{ pasos: PasoSeguimiento[] }>(
        `/plan-compras/api/seguimiento/paquete?paqueteId=${fila.paqueteId}`,
      )
      setPasos(d.pasos)
    } catch (e) {
      setError(mensajeError(e))
    }
  }, [])

  const registrar = useCallback(async (paso: PasoSeguimiento, valor: string) => {
    if (!abierto || paso.pasoId === null || guardando !== null) return
    setGuardando(paso.pasoId)
    try {
      await apiPost('/plan-compras/api/seguimiento/paso', {
        paqueteId: abierto.paqueteId,
        pasoId: paso.pasoId,
        fechaReal: valor === '' ? null : valor,
      })
      // Se recarga en vez de mutar en local: la proyeccion de TODOS los pasos siguientes depende de
      // este cambio, y recalcularla aqui seria duplicar en el cliente la aritmetica del servidor.
      const d = await apiGet<{ pasos: PasoSeguimiento[] }>(
        `/plan-compras/api/seguimiento/paquete?paqueteId=${abierto.paqueteId}`,
      )
      setPasos(d.pasos)
      await cargar()
      setError('')
    } catch (e) {
      setError(e instanceof PdcApiError ? e.message : mensajeError(e))
    } finally {
      setGuardando(null)
    }
  }, [abierto, cargar, guardando])

  const visibles = useMemo(
    () => filtrarSeguimiento(filas, filtros, usuarioId),
    [filas, filtros, usuarioId],
  )
  const frentes = useMemo(() => frentesDeSeguimiento(filas), [filas])

  const cols = useMemo<ColDef<FilaSeguimiento>[]>(() => [
    { ...columnaTexto, headerName: 'Paquete', field: 'nombre', flex: 2, minWidth: 240 },
    { ...columnaTexto, headerName: 'Frente', field: 'frenteNombre', flex: 1, minWidth: 160 },
    {
      ...columnaTexto, headerName: 'Responsable', field: 'responsableNombre', flex: 1, minWidth: 180,
      valueFormatter: (p) => {
        const f = p.data
        if (!f || f.responsableUserId === null) return '— sin asignar —'
        return f.responsableHuerfano ? `${f.responsableNombre} (ya no está en el proyecto)` : f.responsableNombre
      },
    },
    { ...columnaTexto, headerName: 'Paso actual', field: 'pasoActual', flex: 1, minWidth: 180 },
    {
      headerName: 'Avance', field: 'cumplidos', width: 110,
      valueFormatter: (p) => (p.data ? `${p.data.cumplidos} / ${p.data.total}` : ''),
    },
    {
      ...columnaTexto, headerName: 'Estado', field: 'estado', width: 130,
      valueFormatter: (p) => etiquetaEstado(String(p.value ?? '')),
    },
    {
      headerName: 'Atraso', field: 'atrasado', width: 100,
      valueFormatter: (p) => (p.value === true ? 'Sí' : ''),
    },
    { ...columnaTexto, headerName: 'Fin programado', field: 'finProgramado', width: 150 },
    { ...columnaTexto, headerName: 'Fin proyectado', field: 'finProyectado', width: 150 },
  ], [])

  return (
    <section className="pdc-page">
      <header className="pdc-header">
        <h1>Seguimiento del plan de compras</h1>
        <p className="pdc-sub">
          {plural(visibles.length, 'paquete', 'paquetes')} de {filas.length}. Haz clic en una fila para
          registrar cuándo ocurrió cada paso.
        </p>
      </header>

      {error !== '' && <p className="pdc-error" role="alert">{error}</p>}

      <div className="pdc-seg-filtros">
        <label>
          <input
            type="checkbox" checked={filtros.soloMios}
            onChange={(e) => setFiltros((f) => ({ ...f, soloMios: e.target.checked }))}
          />{' '}
          Mis paquetes
        </label>
        <label>
          Frente{' '}
          <select value={filtros.frente} onChange={(e) => setFiltros((f) => ({ ...f, frente: e.target.value }))}>
            <option value="">Todos</option>
            {frentes.map((n) => <option key={n} value={n}>{n}</option>)}
          </select>
        </label>
        <label>
          Estado{' '}
          <select
            value={filtros.estado}
            onChange={(e) => setFiltros((f) => ({ ...f, estado: e.target.value as FiltrosSeguimiento['estado'] }))}
          >
            <option value="">Todos</option>
            <option value="sin_empezar">Sin empezar</option>
            <option value="en_curso">En curso</option>
            <option value="terminado">Terminado</option>
          </select>
        </label>
        <label>
          <input
            type="checkbox" checked={filtros.soloAtrasados}
            onChange={(e) => setFiltros((f) => ({ ...f, soloAtrasados: e.target.checked }))}
          />{' '}
          Solo atrasados
        </label>
      </div>

      <div className="pdc-grid">
        <AgGridReact<FilaSeguimiento>
          theme={pdcTheme}
          rowData={visibles}
          columnDefs={cols}
          defaultColDef={defaultColDef}
          autoSizeStrategy={autoSizeStrategy}
          loading={cargando}
          overlayNoRowsTemplate={vacioTabla('No hay paquetes con plan calculado.')}
          onRowClicked={(e: RowClickedEvent<FilaSeguimiento>) => { if (e.data) void abrir(e.data) }}
        />
      </div>

      {abierto && (
        <aside className="pdc-seg-panel" aria-label={`Avance de ${abierto.nombre}`}>
          <header className="pdc-seg-panel-cabecera">
            <h2>{abierto.nombre}</h2>
            <button type="button" onClick={() => setAbierto(null)}>Cerrar</button>
          </header>
          <table className="pdc-seg-panel-tabla">
            <thead>
              <tr>
                <th scope="col">Paso</th>
                <th scope="col">Programado</th>
                <th scope="col">Real</th>
                <th scope="col">Proyectado</th>
                <th scope="col">Desfase</th>
              </tr>
            </thead>
            <tbody>
              {pasos.map((p) => (
                // La identidad del paso desde A4.1 es `pasoId`: la fila sigue al paso aunque se
                // reordene el proceso. `orden` es el recurso para las filas heredadas, que no la
                // tienen.
                <tr key={p.pasoId ?? `orden-${p.orden}`}>
                  <th scope="row">{p.paso}</th>
                  {/* Sin fecha programada = el plan aun no se ha recalculado tras un reamarre. Se
                      muestra el hueco con un guion en vez de esconderlo: el usuario tiene que poder
                      distinguirlo de un cero. */}
                  <td>{p.fechaFin ?? '—'}</td>
                  <td>
                    <input
                      type="date"
                      value={p.fechaReal ?? ''}
                      onChange={(e) => void registrar(p, e.target.value)}
                      disabled={guardando !== null}
                      aria-label={`Fecha real de ${p.paso}`}
                    />
                  </td>
                  <td>{p.proyectadoFin}</td>
                  <td>{etiquetaDesfaseDias(p.desfaseDias)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </aside>
      )}
    </section>
  )
}
