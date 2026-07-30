import { useEffect, useMemo, useReducer, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { AgGridReact } from 'ag-grid-react'
import { CellStyleModule, ModuleRegistry, TooltipModule, ValidationModule } from 'ag-grid-community'
import type { CellClickedEvent, ColDef } from 'ag-grid-community'
import {
  COLUMNA_CORTA, MODULOS_TABLA, columnasQueCaben, usaAnchoContenedor, TEXTO_LARGO, ajusteDeAncho, autoSizeStrategy, columnaMoneda, columnaNumero, columnaTexto,
  defaultColDef, moneda, pdcTheme, vacioTabla
} from '../lib/agGrid'
import { PdcApiError, apiGet, apiPost, apiUpload } from '../lib/api'
import { alternarSeleccion, puedeMarcar, rutaComparar, rutaVisor } from '../lib/historialVersiones'
import { estadoInicial, importReducer } from '../lib/importState'
import { hayImpacto, textoConserva } from '../lib/impactoReimport'
import { etiquetaVersion } from '../lib/versionLabel'
import type { Comparativo, GrupoImpacto, ImportConfirmResult, ImportErrorFila, ImportPreview, ImpactoVersion, ResumenDiff, VersionPresupuesto } from '../lib/types'
import { contarInsumos, plural } from '../lib/texto'

// Mismo criterio que MaestroInsumos.tsx: registro selectivo de módulos
// (no AllCommunityModule, que arrastra ~1.3MB). ValidationModule solo en dev.
ModuleRegistry.registerModules([
  ...MODULOS_TABLA,
  CellStyleModule, // cellClass de la casilla de comparar y de la acción de fijar como oficial
  TooltipModule, // el nombre completo del archivo, que en la celda va recortado
  ...(import.meta.env.DEV ? [ValidationModule] : []),
])

const colsErrores: ColDef<ImportErrorFila>[] = [
  columnaNumero('fila', 'Fila'),
  { field: 'columna', headerName: 'Columna' },
  columnaTexto('motivo', 'Motivo', 240),
]

/**
 * Columnas del historial. La casilla y la acción se identifican por `colId` porque el clic hace
 * tres cosas distintas según dónde caiga: marcar para comparar, fijar como oficial, o —en el resto
 * de la fila— abrir esa versión en el visor.
 */
const colsVersiones = (seleccion: number[]): ColDef<VersionPresupuesto>[] => [
  {
    colId: 'comparar', headerName: '⇄', width: 52, sortable: false, suppressAutoSize: true,
    cellClass: 'pdc-celda-accion',
    valueGetter: (p) => (p.data && seleccion.includes(p.data.id) ? 1 : 0),
    valueFormatter: (p) => (p.value === 1 ? '☑' : '☐'),
    headerTooltip: 'Marca hasta dos versiones para compararlas',
  },
  // El nombre de la versión y el del archivo son los dos que la revisión encontró recortados
  // («102 DAPORTO RIONEGRO PI_Version…», «Import Da Po…»): envuelven en vez de cortarse.
  {
    ...TEXTO_LARGO, colId: 'version', headerName: 'Versión', minWidth: 210,
    valueGetter: (p) => (p.data ? etiquetaVersion(p.data) : ''),
  },
  {
    // Una línea con «…» y el nombre entero en el tooltip. Envolviendo, «102 - 2026 09 DAPORTO -
    // RIONEGRO - PI_Version_3 (4).xlsx» ocupaba cuatro renglones y dejaba tres versiones a la vista
    // en toda la pantalla. Aquí el recorte no esconde un dato de negocio: la versión y la fecha, que
    // es con lo que se identifica una importación, están enteras en la columna de al lado.
    field: 'archivoNombre', headerName: 'Archivo', flex: 1, minWidth: 150,
    tooltipValueGetter: (p) => String(p.value ?? ''),
  },
  { ...columnaNumero('totalActividades', 'Actividades'), colId: 'actividades', headerName: 'Activ.', headerTooltip: 'Actividades del presupuesto' },
  // «Aparic. APU» y no «Insumos»: la columna cuenta apariciones (820 en Da Porto), no insumos
  // distintos (396). Llamarla «Insumos» fue lo que hizo falta explicar tres veces en el comité.
  { ...columnaNumero('totalInsumos', 'Insumos'), colId: 'insumos', headerName: 'Aparic. APU', headerTooltip: 'Apariciones en APU: un mismo insumo cuenta una vez por cada actividad que lo usa. No es el número de insumos distintos.' },
  columnaMoneda('costoTotal', 'Costo total'),
  { ...COLUMNA_CORTA, colId: 'importadoPor', field: 'importadoPor', headerName: 'Importó', minWidth: 110, maxWidth: 150, wrapText: true, autoHeight: true },
  { ...COLUMNA_CORTA, colId: 'estado', field: 'activa', headerName: 'Estado', valueFormatter: (p) => (p.value ? 'Activa' : '') },
  {
    colId: 'oficial', headerName: '', width: 152, maxWidth: 152, sortable: false, suppressAutoSize: true,
    wrapText: true, autoHeight: true,
    cellClass: (p) => (p.data?.activa ? undefined : 'pdc-celda-accion'),
    valueGetter: (p) => (p.data?.activa ? '' : 'Fijar como oficial'),
  },
]

export default function ImportarPresupuesto() {
  const [state, dispatch] = useReducer(importReducer, estadoInicial)
  const [versiones, setVersiones] = useState<VersionPresupuesto[]>([])
  const [cmp, setCmp] = useState<ResumenDiff | null>(null)
  // Versiones marcadas para comparar (máximo dos) y la que está esperando confirmación para
  // volverse oficial. Ver historialVersiones.ts.
  const [seleccion, setSeleccion] = useState<number[]>([])
  const [porFijar, setPorFijar] = useState<VersionPresupuesto | null>(null)
  const [impacto, setImpacto] = useState<ImpactoVersion | null>(null)
  const [avisoOficial, setAvisoOficial] = useState<string | null>(null)
  const fileRef = useRef<HTMLInputElement>(null)
  const [arrastrando, setArrastrando] = useState(false)
  const [nombreArchivo, setNombreArchivo] = useState('')
  const navigate = useNavigate()

  const cargarVersiones = () => {
    apiGet<{ versiones: VersionPresupuesto[] }>('/plan-compras/api/presupuesto/versiones')
      .then((d) => setVersiones(d.versiones))
      .catch(() => setVersiones([]))
  }
  useEffect(cargarVersiones, [])

  const cols = useMemo(() => colsVersiones(seleccion), [seleccion])
  // Los dos conteos y quién importó son contexto; lo que identifica una versión es su fecha, su
  // archivo, su costo y si está activa. Ese es el orden en que se sacrifican si falta hueco.
  const [refGrid, anchoGrid] = usaAnchoContenedor()
  const colsVisibles = useMemo(
    () => columnasQueCaben(cols, anchoGrid, ['actividades', 'insumos', 'importadoPor', 'estado']),
    [cols, anchoGrid],
  )
  const destinoComparar = rutaComparar(seleccion)

  /**
   * Un clic hace tres cosas distintas según la columna: marcar para comparar, empezar a fijar como
   * oficial, o abrir esa versión en el visor. El resto de la fila navega directo, sin modal — que
   * es lo que se pidió: ver un presupuesto no cambia nada, así que no hay nada que confirmar.
   */
  const onVersionClick = (e: CellClickedEvent<VersionPresupuesto>) => {
    if (!e.data) return
    const col = e.column?.getColId()
    if (col === 'comparar') {
      if (!puedeMarcar(seleccion, e.data.id)) {
        setAvisoOficial('Solo se pueden comparar dos versiones a la vez: desmarca una para elegir otra.')
        return
      }
      setAvisoOficial(null)
      setSeleccion((prev) => alternarSeleccion(prev, e.data!.id))
      return
    }
    if (col === 'oficial') {
      if (e.data.activa) return // ya es la oficial: no hay nada que fijar
      pedirConfirmacionOficial(e.data)
      return
    }
    navigate(rutaVisor(e.data.id))
  }

  /**
   * Antes de cambiar la versión oficial se pregunta, y se dice qué queda afectado con un número
   * real. Se avisa, no se bloquea: las asignaciones a paquete y el plan de fechas no dependen de la
   * versión y sobreviven solos — lo único atado a una versión concreta son los vínculos del maestro.
   */
  const pedirConfirmacionOficial = (v: VersionPresupuesto) => {
    setPorFijar(v)
    setImpacto(null)
    setAvisoOficial(null)
    apiGet<ImpactoVersion>('/plan-compras/api/presupuesto/impacto-version')
      .then(setImpacto)
      .catch(() => setImpacto(null))
  }

  const onFijarOficial = async () => {
    if (!porFijar) return
    try {
      await apiPost('/plan-compras/api/presupuesto/activar', { versionId: porFijar.id })
      setAvisoOficial(`Ahora rige la ${etiquetaVersion(porFijar)}.`)
      setPorFijar(null)
      setImpacto(null)
      cargarVersiones()
    } catch (e) {
      const mensaje = e instanceof PdcApiError && e.code === 'FORBIDDEN'
        ? 'No tienes permiso para cambiar cuál presupuesto rige (hace falta el mismo permiso que para importar).'
        : e instanceof Error ? e.message : String(e)
      setAvisoOficial(mensaje)
      setPorFijar(null)
    }
  }

  const onArchivo = async (file: File | undefined) => {
    if (!file) return
    dispatch({ type: 'SUBIR' })
    try {
      const preview = await apiUpload<ImportPreview>('/plan-compras/api/presupuesto/preview', file)
      dispatch({ type: 'PREVIEW_OK', preview })
    } catch (e) {
      if (e instanceof PdcApiError && e.code === 'VALIDATION_FAILED') {
        const detalle = e.details as { errores?: ImportErrorFila[] } | undefined
        dispatch({ type: 'PREVIEW_ERRORES', errores: detalle?.errores ?? [] })
      } else {
        dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
      }
    } finally {
      if (fileRef.current) fileRef.current.value = ''
    }
  }

  const onConfirmar = async () => {
    if (!state.preview) return
    dispatch({ type: 'CONFIRMAR' })
    setCmp(null)
    try {
      const resultado = await apiPost<ImportConfirmResult>('/plan-compras/api/presupuesto/confirmar', { importToken: state.preview.importToken })
      dispatch({ type: 'CONFIRMADO', resultado })
      cargarVersiones()
      if (!resultado.sinCambios && resultado.versionIdAnterior != null) {
        apiGet<Comparativo>(`/plan-compras/api/presupuesto/comparar?versionA=${resultado.versionIdAnterior}&versionB=${resultado.versionId}`)
          .then((c) => setCmp(c.resumen))
          .catch(() => setCmp(null))
      }
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const r = state.preview?.resumen

  return (
    <section className="pdc-page">
      <header className="pdc-header">
        <h1>Importar presupuesto</h1>
        <p>Sube el Excel exportado del software de presupuestos (hoja «Presupuesto», máx. 10MB).</p>
      </header>

      {/* El control nativo del navegador decía «Choose File · No file chosen» —en inglés y sin
          estilo— en la primera pantalla del módulo. Se queda en el DOM porque es él quien abre el
          diálogo del sistema y quien recibe el `setInputFiles` de los e2e; solo se saca de la vista
          con `pdc-sr-only`, nunca con `display:none`, que lo dejaría inservible para las dos cosas. */}
      <div
        className={`pdc-dropzone${arrastrando ? ' is-arrastrando' : ''}`}
        onDragOver={(e) => { e.preventDefault(); setArrastrando(true) }}
        onDragLeave={() => setArrastrando(false)}
        onDrop={(e) => {
          e.preventDefault()
          setArrastrando(false)
          const f = e.dataTransfer.files?.[0]
          if (f) { setNombreArchivo(f.name); void onArchivo(f) }
        }}
      >
        <input
          ref={fileRef}
          data-testid="pdc-import-file"
          id="pdc-import-file"
          className="pdc-sr-only"
          type="file"
          accept=".xlsx"
          disabled={state.fase === 'subiendo' || state.fase === 'confirmando'}
          onChange={(e) => {
            const f = e.target.files?.[0]
            setNombreArchivo(f?.name ?? '')
            void onArchivo(f)
          }}
        />
        <label htmlFor="pdc-import-file" className="pdc-dropzone-boton">Elegir archivo…</label>
        <span className="pdc-dropzone-texto">
          {nombreArchivo === '' ? 'o suelta aquí el Excel' : nombreArchivo}
        </span>
      </div>

      {state.fase === 'subiendo' && <p>Analizando el archivo…</p>}
      {state.mensajeError && <div className="pdc-error" role="alert">{state.mensajeError}</div>}

      {state.fase === 'previewErrores' && (
        <div className="pdc-bloque">
          <div className="pdc-error" role="alert">
            El archivo tiene {plural(state.errores.length, 'error', 'errores')}; no se importó nada. Corrige el Excel y vuelve a subirlo.
          </div>
          <div className="pdc-grid-corta">
            <AgGridReact<ImportErrorFila>
              theme={pdcTheme}
              rowData={state.errores}
              columnDefs={colsErrores}
              defaultColDef={defaultColDef}
              autoSizeStrategy={autoSizeStrategy}
          {...ajusteDeAncho}
            />
          </div>
        </div>
      )}

      {(state.fase === 'previewOk' || state.fase === 'confirmando') && r && (
        <div className="pdc-bloque" data-testid="pdc-import-resumen">
          <h2>Previsualización — {state.preview?.versionLabel ?? 'sin versión'}</h2>
          <p>
            {r.capitulos} capítulos · {r.subcapitulos} subcapítulos · {r.grupos} grupos · {r.actividades} actividades ·{' '}
            {contarInsumos(r.insumos, 'apariciones')} · Costo total {moneda(r.costoTotal)}
          </p>
          {state.preview?.advertencias.map((a) => (
            <p key={a} className="pdc-advertencia">⚠ {a}</p>
          ))}
          {state.preview?.sinCambios && state.preview.versionActiva && (
            <p className="pdc-advertencia" data-testid="pdc-import-sincambios">
              ⚠ Este presupuesto es idéntico a la <strong>Versión {state.preview.versionActiva.numero}</strong> (activa). No se creará una versión nueva.
            </p>
          )}
          {/* El impacto va ANTES del botón: hoy el usuario confirma a ciegas y no sabe cuánto de su
              trabajo queda huérfano hasta después de haberlo hecho. Informa; no cambia nada solo. */}
          {hayImpacto(state.preview?.impacto) && state.preview && (
            <div className="pdc-impacto" data-testid="pdc-import-impacto">
              <h3>Impacto sobre el trabajo ya hecho</h3>
              <p className="pdc-ayuda">
                Comparado con la {state.preview.impacto.versionActiva?.label || 'versión activa'}.
                Esto se informa: no se reasigna nada por su cuenta.
              </p>
              <ul className="pdc-impacto-cifras">
                {/* El rótulo concuerda en número: «1 insumos nuevos» es exactamente el tropiezo de
                    lectura que este trabajo viene a quitar de la aplicación. */}
                {([
                  ['nuevos', 'insumo nuevo sin paquete', 'insumos nuevos sin paquete', state.preview.impacto.nuevosSinPaquete,
                    'Aparecen en esta versión y no tienen destino asignado: es trabajo que se suma.'],
                  ['desaparecen', 'insumo con paquete que desaparece', 'insumos con paquete que desaparecen', state.preview.impacto.desaparecenConPaquete,
                    'Estaban asignados a un paquete y ya no existen: es trabajo que se pierde.'],
                  ['cambian', 'insumo que cambia de tipo', 'insumos que cambian de tipo', state.preview.impacto.cambianTipo,
                    'Siguen existiendo, pero el motor los va a sugerir distinto. Se señalan para que los revises a mano.'],
                ] as [string, string, string, GrupoImpacto, string][]).map(([id, uno, varios, grupo, ayuda]) => (
                  <li key={id}>
                    <details data-testid={`pdc-impacto-${id}`}>
                      <summary>
                        <strong>{plural(grupo.cantidad, uno, varios)}</strong> · {moneda(grupo.valor)}
                      </summary>
                      <p className="pdc-ayuda">{ayuda}</p>
                      {grupo.detalle.length === 0 ? (
                        <p className="pdc-vacio">Ninguno.</p>
                      ) : (
                        <table className="pdc-tabla-detalle">
                          <thead>
                            <tr><th>Insumo</th><th>Und</th><th>Tipo</th><th>Paquete actual</th><th>Valor</th></tr>
                          </thead>
                          <tbody>
                            {grupo.detalle.map((f) => (
                              <tr key={`${f.descripcion}|${f.unidad}`}>
                                <td>{f.descripcion}</td>
                                <td>{f.unidad}</td>
                                <td>{f.tipoInsumoAnterior === null ? f.tipoInsumo : `${f.tipoInsumoAnterior} → ${f.tipoInsumo}`}</td>
                                <td>{f.paquete ?? '—'}</td>
                                <td className="pdc-num">{moneda(f.valorTotal)}</td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      )}
                    </details>
                  </li>
                ))}
              </ul>
              <p data-testid="pdc-impacto-valor">
                <strong>Valor afectado: {moneda(state.preview.impacto.valorAfectado)}</strong> — la suma de los tres grupos.
              </p>
            </div>
          )}
          <p className="pdc-ayuda" data-testid="pdc-import-conserva">{textoConserva(state.preview?.impacto)}</p>
          <button
            type="button"
            data-testid="pdc-import-confirmar"
            disabled={state.fase === 'confirmando'}
            onClick={onConfirmar}
          >
            {state.fase === 'confirmando' ? 'Importando…' : 'Confirmar e importar'}
          </button>
        </div>
      )}

      {state.fase === 'confirmado' && state.resultado && (
        <div className="pdc-bloque pdc-exito" role="status" data-testid="pdc-import-confirmado">
          {state.resultado.sinCambios ? (
            <p>Sin cambios: se mantiene la <strong>Versión {state.resultado.versionNumero}</strong> activa.</p>
          ) : (
            <>
              <p>Cargada la <strong>Versión {state.resultado.versionNumero}</strong> — ahora es la versión activa del proyecto.</p>
              {cmp && (
                <div data-testid="pdc-import-comparativo">
                  <p>
                    Cambios vs la versión anterior: {cmp.nuevos} nuevos · {cmp.eliminados} eliminados · {cmp.modificados} modificados ·{' '}
                    <span className="pdc-cmp-sobrecosto">sobrecostos {moneda(cmp.sobrecostos)}</span> ·{' '}
                    <span className="pdc-cmp-ahorro">ahorros {moneda(cmp.ahorros)}</span>
                  </p>
                  <a className="pdc-nav-link" href="#/ensamble/comparar">Ver comparativo completo →</a>
                </div>
              )}
            </>
          )}
        </div>
      )}

      <div className="pdc-bloque" data-testid="pdc-import-versiones">
        <div className="pdc-fila-acciones">
          <h2>Historial de versiones</h2>
          <div className="pdc-selector">
            <span className="pdc-ayuda">
              Clic en una fila para verla · marca hasta dos para comparar
            </span>
            <button
              type="button"
              data-testid="pdc-import-comparar"
              disabled={destinoComparar === null}
              onClick={() => destinoComparar && navigate(destinoComparar)}
            >
              Comparar {seleccion.length}/2
            </button>
          </div>
        </div>

        {avisoOficial && <div className="pdc-info" role="status" data-testid="pdc-import-aviso">{avisoOficial}</div>}

        {porFijar && (
          <div className="pdc-panel" data-testid="pdc-import-confirmar-oficial">
            <p>
              ¿Fijar la <strong>{etiquetaVersion(porFijar)}</strong> como el presupuesto oficial del
              proyecto? Es la base del visor, del maestro y del Pareto.
            </p>
            <p className="pdc-ayuda">
              {impacto === null
                ? 'Comprobando qué queda afectado…'
                : impacto.vinculosAfectados === 0
                  ? 'No hay vínculos del maestro hechos sobre la versión que se abandona.'
                  : `${plural(impacto.vinculosAfectados, 'vínculo', 'vínculos')} del maestro quedarán apuntando a la versión que se abandona`
                    + `${impacto.versionActual ? ` (${impacto.versionActual.label})` : ''}.`}
              {' '}Los paquetes y el plan de fechas no dependen de la versión: se conservan.
            </p>
            <button type="button" data-testid="pdc-import-oficial-confirmar" onClick={onFijarOficial}>
              Sí, que rija esta
            </button>{' '}
            <button type="button" data-testid="pdc-import-oficial-cancelar" onClick={() => { setPorFijar(null); setImpacto(null) }}>
              Cancelar
            </button>
          </div>
        )}

        <div className="pdc-grid-corta" ref={refGrid}>
          <AgGridReact<VersionPresupuesto>
            theme={pdcTheme}
            rowData={versiones}
            columnDefs={colsVisibles}
            defaultColDef={defaultColDef}
            autoSizeStrategy={autoSizeStrategy}
          {...ajusteDeAncho}
            getRowId={(p) => String(p.data.id)}
            overlayNoRowsTemplate={vacioTabla("Todavía no se ha importado ningún presupuesto en este proyecto.")}
            onCellClicked={onVersionClick}
          />
        </div>
      </div>
    </section>
  )
}
