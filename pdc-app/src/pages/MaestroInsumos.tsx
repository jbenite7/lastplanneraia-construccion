import { useCallback, useEffect, useMemo, useReducer, useRef, useState } from 'react'
import { AgGridReact } from 'ag-grid-react'
import { CellStyleModule, ModuleRegistry, RowStyleModule, ValidationModule } from 'ag-grid-community'
import type { CellClickedEvent, ColDef, RowDoubleClickedEvent } from 'ag-grid-community'
import {
  COLUMNA_CATEGORIA, COLUMNA_CORTA, MODULOS_TABLA, ajusteDeAncho, autoSizeStrategy, columnaMoneda, columnaNumero, columnaTexto, defaultColDef, pdcTheme, vacioTabla
} from '../lib/agGrid'
import Pestanas, { PanelPestana } from '../components/Pestanas'
import { PdcApiError, apiGet, apiPost, apiUpload } from '../lib/api'
import { estadoInicialMaestro, maestroReducer, pestanaInicialMaestro } from '../lib/maestroState'
import { estadoInicialMaestroImport, maestroImportReducer } from '../lib/maestroImportState'
import type { EquipoSinClasificar, MaestroImportErrorFila, MaestroImportPreview, MaestroImportResultado, MaestroInsumo, ResumenVinculos, SugerenciaMaestro, VinculoInsumo } from '../lib/types'
import { TIPO_RECURSO_EQUIPO, etiquetaTipoRecurso } from '../lib/tipoRecurso'
import { plural } from '../lib/texto'

// Mismo criterio que ImportarPresupuesto.tsx/VisorPresupuesto.tsx: registro selectivo de módulos
// (no AllCommunityModule, que arrastra ~1.3MB). ValidationModule solo en dev.
ModuleRegistry.registerModules([
  ...MODULOS_TABLA,
  CellStyleModule, // cellClass de la columna de acción del catálogo
  RowStyleModule, // rowClassRules de filas retiradas
  ...(import.meta.env.DEV ? [ValidationModule] : []),
])

export default function MaestroInsumos() {
  const [state, dispatch] = useReducer(maestroReducer, estadoInicialMaestro)
  const [vinculos, setVinculos] = useState<VinculoInsumo[]>([])
  const [resumen, setResumen] = useState<ResumenVinculos | null>(null)
  const [catalogo, setCatalogo] = useState<MaestroInsumo[]>([])
  const [busqueda, setBusqueda] = useState('')
  const [sugerencias, setSugerencias] = useState<SugerenciaMaestro[]>([])
  const [sinPresupuesto, setSinPresupuesto] = useState(false)
  const [verRetirados, setVerRetirados] = useState(false)
  const [porRetirar, setPorRetirar] = useState<MaestroInsumo | null>(null)
  const [imp, dispatchImp] = useReducer(maestroImportReducer, estadoInicialMaestroImport)
  // Cola de equipos sin clasificar (Ola 2). Vive aquí y no en pantalla propia porque es el mismo
  // trabajo que la cola de vínculos: decidir sobre insumos del maestro.
  const [equipos, setEquipos] = useState<EquipoSinClasificar[]>([])
  const [equiposTotal, setEquiposTotal] = useState(0)
  const [selEquipos, setSelEquipos] = useState<Set<number>>(new Set())
  const [equiposOcupado, setEquiposOcupado] = useState(false)
  const [puedeEscribir, setPuedeEscribir] = useState(true)
  // Las tres tablas de esta pantalla vivían apiladas y el catálogo global (3.079 insumos) tapaba
  // la cola de pendientes, que es el trabajo que de verdad falta. Abre por ahí — salvo que no haya
  // pendientes, y entonces abrir por una tabla vacía sería enseñar una pared en blanco.
  const [seccion, setSeccion] = useState('pendientes')
  // Se decide una sola vez, cuando llega el primer resumen: en el primer render todavía no se sabe
  // cuántos pendientes hay. A partir de ahí manda el usuario, igual que el filtro de Paquetes.
  const seccionDecidida = useRef(false)
  const impFileRef = useRef<HTMLInputElement>(null)

  const cargar = useCallback(async () => {
    try {
      const g = await apiPost<ResumenVinculos & { versionId: number }>('/plan-compras/api/maestro/vinculos/generar', {})
      void g
      const v = await apiGet<{ resumen: ResumenVinculos; vinculos: VinculoInsumo[] }>('/plan-compras/api/maestro/vinculos')
      setResumen(v.resumen)
      setVinculos(v.vinculos)
      setSinPresupuesto(false)
      if (!seccionDecidida.current) {
        seccionDecidida.current = true
        setSeccion(pestanaInicialMaestro(v.resumen.pendientes))
      }
    } catch (e) {
      if (e instanceof PdcApiError && e.code === 'NO_VERSION') setSinPresupuesto(true)
      else if (e instanceof PdcApiError && e.code === 'FORBIDDEN') {
        // Sin permiso de escritura: cargar solo lectura.
        setPuedeEscribir(false)
        try {
          const v = await apiGet<{ resumen: ResumenVinculos; vinculos: VinculoInsumo[] }>('/plan-compras/api/maestro/vinculos')
          setResumen(v.resumen)
          setVinculos(v.vinculos)
        } catch { setSinPresupuesto(true) }
      } else dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }, [])

  const cargarCatalogo = useCallback((q: string, incluirRetirados = false) => {
    const extra = incluirRetirados ? '&incluirInactivos=1' : ''
    apiGet<{ insumos: MaestroInsumo[] }>(`/plan-compras/api/maestro?busqueda=${encodeURIComponent(q)}${extra}`)
      .then((d) => setCatalogo(d.insumos))
      .catch(() => setCatalogo([]))
  }, [])

  const cargarEquipos = useCallback(async () => {
    try {
      const r = await apiGet<{ total: number; items: EquipoSinClasificar[] }>('/plan-compras/api/maestro/equipos')
      setEquipos(r.items)
      setEquiposTotal(r.total)
    } catch {
      // La cola es informativa: si falla, la pestaña simplemente no aparece. No debe tumbar la pantalla.
      setEquipos([])
      setEquiposTotal(0)
    }
  }, [])

  useEffect(() => { void cargar() }, [cargar])
  useEffect(() => { void cargarEquipos() }, [cargarEquipos])
  useEffect(() => { cargarCatalogo(busqueda, verRetirados) }, [busqueda, verRetirados, cargarCatalogo])

  useEffect(() => {
    if (!state.vinculando) { setSugerencias([]); return }
    apiGet<{ sugerencias: SugerenciaMaestro[] }>(`/plan-compras/api/maestro/sugerencias?vinculoId=${state.vinculando.id}`)
      .then((d) => setSugerencias(d.sugerencias))
      .catch(() => setSugerencias([]))
  }, [state.vinculando])

  const onArchivoMaestro = async (file: File | undefined) => {
    if (!file) return
    dispatchImp({ type: 'SUBIR' })
    try {
      const preview = await apiUpload<MaestroImportPreview>('/plan-compras/api/maestro/importar/preview', file)
      dispatchImp({ type: 'PREVIEW_OK', preview })
    } catch (e) {
      if (e instanceof PdcApiError && e.code === 'VALIDATION_FAILED') {
        const d = e.details as { errores?: MaestroImportErrorFila[] } | undefined
        dispatchImp({ type: 'PREVIEW_ERRORES', errores: d?.errores ?? [] })
      } else {
        dispatchImp({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
      }
    } finally {
      if (impFileRef.current) impFileRef.current.value = ''
    }
  }

  const onConfirmarMaestro = async () => {
    if (!imp.preview) return
    dispatchImp({ type: 'CONFIRMAR' })
    try {
      const resultado = await apiPost<MaestroImportResultado>('/plan-compras/api/maestro/importar/confirmar', { importToken: imp.preview.importToken })
      dispatchImp({ type: 'CONFIRMADO', resultado })
      cargarCatalogo(busqueda, verRetirados)
      // Cargar el maestro puede traer equipos nuevos, y esos nacen sin clasificar.
      void cargarEquipos()
    } catch (e) {
      dispatchImp({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const pendientes = useMemo(() => vinculos.filter((v) => v.estado === 'pendiente'), [vinculos])

  // ── Cola de equipos sin clasificar (Ola 2) ───────────────────────────────────────────────────
  const colsEquipos: ColDef<EquipoSinClasificar>[] = useMemo(() => [
    {
      headerName: '✓', width: 60, field: 'id', suppressAutoSize: true,
      valueFormatter: (p) => (selEquipos.has(p.value as number) ? '●' : ''),
    },
    columnaTexto('descripcion', 'Equipo', 300),
    { ...COLUMNA_CORTA, field: 'unidad', headerName: 'Und' },
    // La evidencia, cruda y a la vista: es lo que escribió el equipo de presupuestos en SINCO.
    { ...COLUMNA_CATEGORIA, field: 'agrupacion', headerName: 'SINCO dice', minWidth: 240, valueFormatter: (p) => (p.value as string | null) ?? '—' },
    // Informativa. No hay ninguna celda editable que escriba esto sola.
    {
      ...COLUMNA_CATEGORIA, field: 'pista', headerName: 'Sugerencia', minWidth: 170,
      valueFormatter: (p) => (p.value ? etiquetaTipoRecurso(p.value as string) : '—'),
    },
  ], [selEquipos])

  const onEquipoClick = (e: CellClickedEvent<EquipoSinClasificar>) => {
    const id = e.data?.id
    if (id === undefined) return
    setSelEquipos((prev) => {
      const s = new Set(prev)
      if (s.has(id)) s.delete(id)
      else s.add(id)
      return s
    })
  }

  /** Selecciona (no guarda) el lote que SINCO marca con un destino. La persona sigue confirmando. */
  const seleccionarPorPista = (pista: string) => {
    setSelEquipos(new Set(equipos.filter((eq) => eq.pista === pista).map((eq) => eq.id)))
  }

  const clasificarSeleccion = async (destino: string) => {
    if (selEquipos.size === 0) return
    setEquiposOcupado(true)
    try {
      const r = await apiPost<{ clasificados: number; cola: { total: number; items: EquipoSinClasificar[] } }>(
        '/plan-compras/api/maestro/equipos/clasificar',
        { ids: [...selEquipos], destino },
      )
      setEquipos(r.cola.items)
      setEquiposTotal(r.cola.total)
      setSelEquipos(new Set())
      dispatch({ type: 'LISTO', mensaje: `${r.clasificados} ${plural(r.clasificados, 'equipo', 'equipos')} ${plural(r.clasificados, 'clasificado', 'clasificados')} como ${etiquetaTipoRecurso(destino).toLowerCase()}.` })
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    } finally {
      setEquiposOcupado(false)
    }
  }

  const conPistaAlquiler = useMemo(() => equipos.filter((eq) => eq.pista === TIPO_RECURSO_EQUIPO.ALQUILADO).length, [equipos])
  const conPistaCompra = useMemo(() => equipos.filter((eq) => eq.pista === TIPO_RECURSO_EQUIPO.COMPRADO).length, [equipos])

  const colsPendientes: ColDef<VinculoInsumo>[] = useMemo(() => [
    // La marca de selección se queda con su ancho fijo: medirla por contenido daría una columna de
    // un carácter y el punto quedaría pegado al borde.
    {
      headerName: '✓', width: 60, field: 'id', suppressAutoSize: true,
      valueFormatter: (p) => (state.seleccion.has(p.value as number) ? '●' : ''),
    },
    columnaTexto('descripcionOriginal', 'Insumo', 260),
    { ...COLUMNA_CATEGORIA, field: 'tipoInsumo', headerName: 'Tipo' },
    { ...COLUMNA_CORTA, field: 'unidad', headerName: 'Und' },
    columnaNumero('apariciones', 'Usos'),
    columnaMoneda('valorTotal', 'Valor total'),
  ], [state.seleccion])

  const colsCatalogo: ColDef<MaestroInsumo>[] = useMemo(() => [
    columnaTexto('descripcion', 'Insumo', 280),
    { ...COLUMNA_CORTA, field: 'unidad', headerName: 'Und' },
    { ...COLUMNA_CATEGORIA, field: 'tipoInsumo', headerName: 'Tipo' },
    ...(verRetirados
      ? [{
          field: 'activo', headerName: 'Estado',
          valueFormatter: (p) => (p.value === 0 ? 'Retirado' : 'Activo'),
        } satisfies ColDef<MaestroInsumo>]
      : []),
    {
      colId: 'accion', headerName: 'Acción', width: 110, sortable: false, suppressAutoSize: true,
      cellClass: 'pdc-celda-accion',
      valueGetter: (p) => (p.data?.activo === 0 ? 'Reactivar' : 'Retirar'),
    },
  ], [verRetirados])

  const onPendienteClick = (e: CellClickedEvent<VinculoInsumo>) => {
    if (e.data) dispatch({ type: 'TOGGLE_SEL', id: e.data.id })
  }
  const onPendienteDoble = (e: RowDoubleClickedEvent<VinculoInsumo>) => {
    if (e.data) dispatch({ type: 'ABRIR_VINCULAR', vinculo: e.data })
  }

  const crearMasivo = async () => {
    dispatch({ type: 'OCUPADO' })
    try {
      const r = await apiPost<{ creados: number; vinculados: number }>('/plan-compras/api/maestro/crear-desde-pendientes', {
        vinculoIds: [...state.seleccion],
      })
      dispatch({ type: 'LISTO', mensaje: `${r.creados} insumos creados en el maestro, ${r.vinculados} vinculados.` })
      await cargar()
      cargarCatalogo(busqueda)
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const cambiarEstadoMaestro = async (insumo: MaestroInsumo) => {
    dispatch({ type: 'OCUPADO' })
    try {
      if (insumo.activo === 0) {
        await apiPost('/plan-compras/api/maestro/reactivar', { maestroId: insumo.id })
        dispatch({ type: 'LISTO', mensaje: `«${insumo.descripcion}» reactivado en el catálogo.` })
      } else {
        const r = await apiPost<{ revertidos: number }>('/plan-compras/api/maestro/desactivar', { maestroId: insumo.id })
        dispatch({
          type: 'LISTO',
          mensaje: `«${insumo.descripcion}» retirado del catálogo. ${plural(r.revertidos, 'vínculo automático', 'vínculos automáticos')} vuelven a pendientes.`,
        })
      }
      await cargar()
      cargarCatalogo(busqueda, verRetirados)
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  const onCatalogoClick = (e: CellClickedEvent<MaestroInsumo>) => {
    if (e.colDef.colId !== 'accion' || !e.data || state.ocupado) return
    // Reactivar no destruye nada y va directo. Retirar revierte los vínculos automáticos de ESTE
    // insumo en todos los proyectos, así que pregunta antes.
    if (e.data.activo === 0) { void cambiarEstadoMaestro(e.data); return }
    setPorRetirar(e.data)
  }

  const vincularA = async (maestroId: number) => {
    if (!state.vinculando) return
    dispatch({ type: 'OCUPADO' })
    try {
      await apiPost('/plan-compras/api/maestro/vinculos/confirmar', { vinculoId: state.vinculando.id, maestroId })
      dispatch({ type: 'LISTO', mensaje: 'Insumo vinculado.' })
      await cargar()
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: e instanceof Error ? e.message : String(e) })
    }
  }

  // El bloque vive en una constante porque se usa en DOS ramas del render: la normal y la de «este
  // proyecto no tiene presupuesto». La cola de equipos es del catálogo GLOBAL de la empresa y no
  // depende del presupuesto de ninguna obra — esconderla ahí dejaba el tapón sin puerta de entrada
  // en cualquier obra nueva, que es justo donde alguien va a estar montando el maestro. Lo cazó el
  // e2e en navegador; los tests de PHP no podían verlo.
  const bloqueEquipos = (
  <div className="pdc-bloque">
          <div className="pdc-fila-acciones">
            <h2>Clasificar equipos ({equiposTotal})</h2>
            {puedeEscribir && (
              <div>
                <button
                  type="button"
                  data-testid="pdc-equipos-clasificar-alquilado"
                  disabled={selEquipos.size === 0 || equiposOcupado}
                  onClick={() => void clasificarSeleccion(TIPO_RECURSO_EQUIPO.ALQUILADO)}
                >
                  {equiposOcupado ? 'Procesando…' : `Marcar ${selEquipos.size} como alquilado`}
                </button>{' '}
                <button
                  type="button"
                  data-testid="pdc-equipos-clasificar-comprado"
                  disabled={selEquipos.size === 0 || equiposOcupado}
                  onClick={() => void clasificarSeleccion(TIPO_RECURSO_EQUIPO.COMPRADO)}
                >
                  {equiposOcupado ? 'Procesando…' : `Marcar ${selEquipos.size} como comprado`}
                </button>
              </div>
            )}
          </div>
          <p className="pdc-ayuda">
            Estos equipos venían en una sola categoría. Contabilidad maneja distinto un alquiler que una
            compra, así que hay que decirlo una vez por insumo. La columna «SINCO dice» es lo que escribió
            presupuestos: úsala como pista, no como respuesta.
          </p>
          {puedeEscribir && (conPistaAlquiler > 0 || conPistaCompra > 0) && (
            <p className="pdc-ayuda">
              Atajos que <strong>seleccionan</strong> (no guardan):{' '}
              {conPistaAlquiler > 0 && (
                <button type="button" data-testid="pdc-equipos-sel-alquiler" onClick={() => seleccionarPorPista(TIPO_RECURSO_EQUIPO.ALQUILADO)}>
                  los {conPistaAlquiler} que SINCO marca como alquiler
                </button>
              )}
              {conPistaAlquiler > 0 && conPistaCompra > 0 ? ' · ' : ''}
              {conPistaCompra > 0 && (
                <button type="button" data-testid="pdc-equipos-sel-compra" onClick={() => seleccionarPorPista(TIPO_RECURSO_EQUIPO.COMPRADO)}>
                  los {conPistaCompra} que SINCO marca como compra
                </button>
              )}
            </p>
          )}
          <p className="pdc-ayuda">Clic = seleccionar.</p>
          <div className="pdc-grid" data-testid="pdc-equipos-cola">
            <AgGridReact<EquipoSinClasificar>
              theme={pdcTheme}
              rowData={equipos}
              overlayNoRowsTemplate={vacioTabla('No queda ningún equipo sin clasificar.')}
              columnDefs={colsEquipos}
              defaultColDef={defaultColDef}
              autoSizeStrategy={autoSizeStrategy}
            {...ajusteDeAncho}
              getRowId={(p) => String(p.data.id)}
              onCellClicked={onEquipoClick}
            />
          </div>
        </div>
  )

  if (sinPresupuesto) {
    return (
      <section className="pdc-page">
        <header className="pdc-header"><h1>Maestro de insumos</h1></header>
        <div className="pdc-bloque pdc-vacio" data-testid="pdc-maestro-vacio">
          Este proyecto aún no tiene un presupuesto importado. Ve a <strong>Ensamble → Importar</strong>.
        </div>
        {/* El acuse va aquí también: clasificar equipos funciona en esta rama, y sin esto la acción
            se completaba en silencio. Lo cazó el e2e al clasificar en una obra sin presupuesto. */}
        {state.mensaje && <div className="pdc-exito" role="status">{state.mensaje}</div>}
        {equiposTotal > 0 && bloqueEquipos}
      </section>
    )
  }

  return (
    <section className="pdc-page">
      <header className="pdc-header pdc-header-fila">
        <div>
          <h1>Maestro de insumos</h1>
          <p>Catálogo único de AIA. Vincula o crea los insumos del presupuesto activo.</p>
        </div>
        {resumen && (
          <p data-testid="pdc-maestro-cobertura" className="pdc-cobertura">
            Cobertura: {resumen.cobertura}% · {resumen.pendientes} pendientes de {resumen.total}
            <span className="pdc-cifra-nota">insumos distintos de este presupuesto</span>
          </p>
        )}
      </header>

      {state.mensaje && <div className="pdc-exito" role="status">{state.mensaje}</div>}

      <Pestanas
        idBase="pdc-maestro"
        etiquetaLista="Secciones del maestro de insumos"
        activa={seccion}
        // Elegir pestaña a mano cierra la decisión automática. El primer resumen tarda (un POST y
        // un GET encadenados) y sin esto llegaba DESPUÉS del clic del usuario y lo devolvía a la
        // pestaña de apertura: quien entra y va rápido a «Importar SINCO» se veía rebotado a
        // «Pendientes». Lo cazó el e2e del import SINCO.
        onCambiar={(s) => { seccionDecidida.current = true; setSeccion(s) }}
        pestanas={[
          { id: 'pendientes', etiqueta: 'Pendientes por vincular', conteo: pendientes.length },
          { id: 'catalogo', etiqueta: 'Catálogo global', conteo: catalogo.length },
          // Sólo aparece mientras haya tapón: cuando la cola llega a 0, la pantalla vuelve a como estaba.
          ...(equiposTotal > 0 ? [{ id: 'equipos', etiqueta: 'Clasificar equipos', conteo: equiposTotal }] : []),
          { id: 'importar', etiqueta: 'Importar SINCO' },
        ]}
      />

      {seccion === 'equipos' && (
      <PanelPestana idBase="pdc-maestro" id="equipos">
      {bloqueEquipos}
      </PanelPestana>
      )}

      {seccion === 'importar' && (
      <PanelPestana idBase="pdc-maestro" id="importar">
      <section className="pdc-bloque pdc-maestro-import">
        <h2>Importar maestro (SINCO)</h2>
        <p>Sube el Excel del maestro de insumos exportado de SINCO (hoja «Maestro Insumos», máx. 10MB).</p>
        <input
          ref={impFileRef}
          data-testid="pdc-maestro-import-file"
          type="file"
          accept=".xlsx"
          disabled={imp.fase === 'subiendo' || imp.fase === 'confirmando'}
          onChange={(e) => onArchivoMaestro(e.target.files?.[0])}
        />
        {imp.fase === 'subiendo' && <p>Analizando el archivo…</p>}
        {imp.mensajeError && <div className="pdc-error" role="alert">{imp.mensajeError}</div>}
        {imp.fase === 'previewErrores' && (
          <div className="pdc-error" role="alert">El archivo tiene {plural(imp.errores.length, 'error', 'errores')}; no se importó nada.</div>
        )}
        {(imp.fase === 'previewOk' || imp.fase === 'confirmando') && imp.preview && (
          <div data-testid="pdc-maestro-import-resumen">
            <p>{imp.preview.resumen.activos} insumos activos · {imp.preview.resumen.omitidos} omitidos · {imp.preview.resumen.agrupaciones} agrupaciones · {imp.preview.resumen.tiposRecurso} tipos</p>
            <button type="button" data-testid="pdc-maestro-import-confirmar" disabled={imp.fase === 'confirmando'} onClick={onConfirmarMaestro}>
              {imp.fase === 'confirmando' ? 'Importando…' : 'Confirmar e importar'}
            </button>
          </div>
        )}
        {imp.fase === 'confirmado' && imp.resultado && (
          <div className="pdc-exito" role="status">
            Maestro importado: {imp.resultado.creados} creados, {imp.resultado.actualizados} actualizados, {imp.resultado.enriquecidos} enriquecidos.
          </div>
        )}
        {imp.fase === 'confirmado' && imp.resultado && imp.resultado.conflictos.length > 0 && (
          <div className="pdc-error" role="alert" data-testid="pdc-maestro-import-conflictos">
            <div>
              {plural(imp.resultado.conflictos.length, 'conflicto')}: ya existe otro insumo con la misma descripción y unidad — revísalos manualmente:
            </div>
            {imp.resultado.conflictos.slice(0, 20).map((c, i) => (
              <div key={`${c.codigoSinco}-${i}`}>
                {c.codigoSinco} «{c.descripcion}» choca con {c.chocaCon}
              </div>
            ))}
            {imp.resultado.conflictos.length > 20 && <div>… y {imp.resultado.conflictos.length - 20} más</div>}
          </div>
        )}
      </section>
      </PanelPestana>
      )}

      {seccion === 'pendientes' && (
      <PanelPestana idBase="pdc-maestro" id="pendientes">
      <div className="pdc-bloque">
        <div className="pdc-fila-acciones">
          <h2>Pendientes por vincular ({pendientes.length})</h2>
          <div>
            <button type="button" data-testid="pdc-maestro-sel-todos" onClick={() => dispatch({ type: 'SEL_TODOS', ids: pendientes.map((p) => p.id) })}>
              Seleccionar todos
            </button>{' '}
            <button
              type="button"
              data-testid="pdc-maestro-crear-masivo"
              disabled={state.seleccion.size === 0 || state.ocupado}
              onClick={crearMasivo}
            >
              {state.ocupado ? 'Procesando…' : `Crear ${state.seleccion.size} en el maestro`}
            </button>
          </div>
        </div>
        <p className="pdc-ayuda">Clic = seleccionar · doble clic = vincular a un insumo existente.</p>
        <div className="pdc-grid" data-testid="pdc-maestro-pendientes">
          <AgGridReact<VinculoInsumo>
            theme={pdcTheme}
            rowData={pendientes}
            overlayNoRowsTemplate={vacioTabla("Nada pendiente: todos los insumos de este presupuesto ya están en el maestro.")}
            columnDefs={colsPendientes}
            defaultColDef={defaultColDef}
            autoSizeStrategy={autoSizeStrategy}
          {...ajusteDeAncho}
            getRowId={(p) => String(p.data.id)}
            onCellClicked={onPendienteClick}
            onRowDoubleClicked={onPendienteDoble}
          />
        </div>
      </div>

      {state.vinculando && (
        <div className="pdc-bloque pdc-panel" data-testid="pdc-maestro-panel">
          <h2>Vincular «{state.vinculando.descripcionOriginal}» ({state.vinculando.unidad})</h2>
          {sugerencias.length === 0 ? (
            <p>Sin sugerencias — créalo con la acción masiva o búscalo en el catálogo.</p>
          ) : (
            <ul>
              {sugerencias.map((s) => (
                <li key={s.id}>
                  <button type="button" disabled={state.ocupado} onClick={() => vincularA(s.id)}>
                    {s.descripcion} ({s.unidad})
                  </button>
                </li>
              ))}
            </ul>
          )}
          <button type="button" onClick={() => dispatch({ type: 'CERRAR_VINCULAR' })}>Cerrar</button>
        </div>
      )}

      </PanelPestana>
      )}

      {seccion === 'catalogo' && (
      <PanelPestana idBase="pdc-maestro" id="catalogo">
      <div className="pdc-bloque">
        <div className="pdc-fila-acciones">
          <h2>Catálogo global ({catalogo.length.toLocaleString('es-CO')}{busqueda.trim() ? ' resultados' : ' insumos'})<span className="pdc-cifra-nota">insumos de toda la empresa, no solo de este proyecto</span></h2>
          <div className="pdc-selector">
            <label className="pdc-check">
              <input
                data-testid="pdc-maestro-ver-retirados"
                type="checkbox"
                checked={verRetirados}
                onChange={(e) => setVerRetirados(e.target.checked)}
              />{' '}
              Ver retirados
            </label>
            <input
              data-testid="pdc-maestro-busqueda"
              type="search"
              placeholder="Buscar insumo…"
              value={busqueda}
              onChange={(e) => setBusqueda(e.target.value)}
            />
          </div>
        </div>
        {porRetirar && (
          <div className="pdc-panel" data-testid="pdc-maestro-confirmar-retirar">
            <p>¿Retirar <strong>{porRetirar.descripcion}</strong> del catálogo?</p>
            <p className="pdc-ayuda">
              Deja de estar disponible para vincular y sus vínculos automáticos vuelven a pendientes
              en <strong>todos los proyectos</strong>, no solo en este. Se puede reactivar después.
            </p>
            <button
              type="button"
              data-testid="pdc-maestro-retirar-confirmar"
              disabled={state.ocupado}
              onClick={() => { const i = porRetirar; setPorRetirar(null); void cambiarEstadoMaestro(i) }}
            >
              Sí, retirarlo
            </button>{' '}
            <button type="button" data-testid="pdc-maestro-retirar-cancelar" onClick={() => setPorRetirar(null)}>
              Cancelar
            </button>
          </div>
        )}
        <div className="pdc-grid" data-testid="pdc-maestro-catalogo">
          <AgGridReact<MaestroInsumo>
            theme={pdcTheme}
            rowData={catalogo}
            overlayNoRowsTemplate={vacioTabla("Ningún insumo del catálogo coincide con la búsqueda.")}
            columnDefs={colsCatalogo}
            defaultColDef={defaultColDef}
            autoSizeStrategy={autoSizeStrategy}
          {...ajusteDeAncho}
            getRowId={(p) => String(p.data.id)}
            onCellClicked={onCatalogoClick}
            rowClassRules={{ 'pdc-fila-retirada': (p) => p.data?.activo === 0 }}
          />
        </div>
      </div>
      </PanelPestana>
      )}
    </section>
  )
}
