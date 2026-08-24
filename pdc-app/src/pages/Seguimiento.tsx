import { useCallback, useEffect, useMemo, useState } from 'react'
import { AgGridReact } from 'ag-grid-react'
import { CellStyleModule, ModuleRegistry, RowStyleModule } from 'ag-grid-community'
import type { ColDef, GridApi, RowClickedEvent } from 'ag-grid-community'
import { MODULOS_TABLA, ajusteDeAncho, autoSizeStrategy, columnaTexto, defaultColDef, pdcTheme, propsBuscador, vacioTabla } from '../lib/agGrid'
import { BarraFiltros } from '../components/BarraFiltros'
import { chipsDeGrilla } from '../lib/barraFiltros'
import { PdcApiError, apiGet, apiPost } from '../lib/api'
import { getBootstrap } from '../lib/bootstrap'
import { etiquetaDesfaseDias, etiquetaEstado, filtrarSeguimiento, frentesDeSeguimiento } from '../lib/seguimiento'
import Pestanas, { PanelPestana } from '../components/Pestanas'
import { CORTES, claseCorte, etiquetaCorte, textoDesfase, textoSinFechas } from '../lib/vencimientos'
import {
  alturaBarra,
  cobertura,
  etiquetaMes,
  mesPico,
  porcentajeConFecha,
  textoExcluidos,
  textoProvisional,
  type RespuestaFlujoCaja,
} from '../lib/flujoCaja'
import { moneda } from '../lib/agGrid'
import type { FilaSeguimiento, FiltrosSeguimiento, FilaVencimiento, PasoSeguimiento, RespuestaVencimientos } from '../lib/types'
import { plural } from '../lib/texto'
import BotonAyuda from '../components/BotonAyuda'
import { Selector } from '../components/Selector'

// Solo lectura en la grilla: el avance se registra en el panel de detalle, no en la celda. Por eso
// no se registra ningun modulo de edicion aqui.
ModuleRegistry.registerModules([...MODULOS_TABLA, CellStyleModule, RowStyleModule])

const mensajeError = (e: unknown) => (e instanceof Error ? e.message : String(e))

const SIN_FILTRO: FiltrosSeguimiento = { soloMios: false, frente: '', estado: '', soloAtrasados: false }

const ETIQUETA_ESTADO_FILTRO: Record<string, string> = { sin_empezar: 'Sin empezar', en_curso: 'En curso', terminado: 'Terminado' }

// Nombres de las columnas con filtro de la grilla de paquetes, para los chips de la barra.
const NOMBRES_COLUMNA_SEG = {
  nombre: 'Paquete', frenteNombre: 'Frente', responsableNombre: 'Responsable',
  pasoActual: 'Paso actual', estado: 'Estado', finProgramado: 'Fin programado', finProyectado: 'Fin proyectado',
}

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
  // B2 · los paquetes cuyo plan se calculó contra un cronograma que ya cambió. Las fechas que esta
  // pestaña muestra para ellos son viejas, y callarlo las hace pasar por buenas.
  const [desactualizados, setDesactualizados] = useState<number[]>([])
  // La pestaña de vencimientos es la vista de un lunes por la mañana; la de paquetes es donde se
  // registra el avance. Son dos preguntas distintas sobre los mismos datos y por eso conviven aquí en
  // vez de en dos pantallas — igual que las cuatro secciones del Plan.
  const [seccion, setSeccion] = useState('paquetes')
  const [venc, setVenc] = useState<RespuestaVencimientos | null>(null)
  const [filtroPaso, setFiltroPaso] = useState('')
  const [filtroResp, setFiltroResp] = useState('')
  const [buscaSeg, setBuscaSeg] = useState('')
  const [flujo, setFlujo] = useState<RespuestaFlujoCaja | null>(null)
  const [gridApi, setGridApi] = useState<GridApi<FilaSeguimiento> | null>(null)
  const [modeloFiltrosGrid, setModeloFiltrosGrid] = useState<Record<string, unknown>>({})

  const cargar = useCallback(async () => {
    setCargando(true)
    try {
      const d = await apiGet<{ resumen: FilaSeguimiento[]; desactualizados: number[] }>(
        '/plan-compras/api/seguimiento',
      )
      setFilas(d.resumen)
      setDesactualizados(d.desactualizados ?? [])
      setError('')
    } catch (e) {
      setError(mensajeError(e))
    } finally {
      setCargando(false)
    }
  }, [])

  // Los filtros se resuelven en el servidor, no aquí: los conteos de cada corte tienen que describir
  // exactamente lo que hay en la tabla, y filtrar en el cliente dejaría los números contando otra cosa
  // que la lista.
  const cargarVencimientos = useCallback(async () => {
    const q = new URLSearchParams()
    if (filtroPaso !== '') q.set('paso', filtroPaso)
    if (filtroResp !== '') q.set('responsable', filtroResp)
    const sufijo = q.toString() === '' ? '' : `?${q.toString()}`
    try {
      setVenc(await apiGet<RespuestaVencimientos>(`/plan-compras/api/seguimiento/vencimientos${sufijo}`))
      setError('')
    } catch (e) {
      setError(mensajeError(e))
    }
  }, [filtroPaso, filtroResp])

  useEffect(() => {
    if (seccion === 'vencimientos') void cargarVencimientos()
  }, [seccion, cargarVencimientos])

  // La curva se pide al abrir su pestaña y no al cargar la pantalla: es un cálculo derivado sobre
  // todos los destinos contratables de la obra, y quien entra a registrar un avance no lo necesita.
  const cargarFlujo = useCallback(async () => {
    try {
      setFlujo(await apiGet<RespuestaFlujoCaja>('/plan-compras/api/seguimiento/flujo-caja'))
      setError('')
    } catch (e) {
      setError(mensajeError(e))
    }
  }, [])

  useEffect(() => {
    if (seccion === 'flujo') void cargarFlujo()
  }, [seccion, cargarFlujo])

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
    { ...columnaTexto('nombre', 'Paquete', 240), flex: 2 },
    { ...columnaTexto('frenteNombre', 'Frente', 160), flex: 1 },
    {
      ...columnaTexto('responsableNombre', 'Responsable', 180), flex: 1,
      valueFormatter: (p) => {
        const f = p.data
        if (!f || f.responsableUserId === null) return '— sin asignar —'
        return f.responsableHuerfano ? `${f.responsableNombre} (ya no está en el proyecto)` : f.responsableNombre
      },
    },
    { ...columnaTexto('pasoActual', 'Paso actual', 180), flex: 1 },
    {
      headerName: 'Avance', field: 'cumplidos', width: 110,
      valueFormatter: (p) => (p.data ? `${p.data.cumplidos} / ${p.data.total}` : ''),
    },
    {
      headerName: 'Estado', field: 'estado', width: 130, filter: 'agTextColumnFilter',
      valueFormatter: (p) => etiquetaEstado(String(p.value ?? '')),
    },
    {
      headerName: 'Atraso', field: 'atrasado', width: 100,
      valueFormatter: (p) => (p.value === true ? 'Sí' : ''),
    },
    { headerName: 'Fin programado', field: 'finProgramado', width: 150, filter: 'agTextColumnFilter' },
    { headerName: 'Fin proyectado', field: 'finProyectado', width: 150, filter: 'agTextColumnFilter' },
  ], [])

  // Los filtros propios de la página (frente y estado) se anuncian junto a los de columna: si no,
  // «Limpiar todo» limpiaría solo la mitad y la tabla seguiría sin enseñar filas.
  const chipsFiltros = [
    ...(filtros.frente !== '' ? [{ id: 'pagina:frente', texto: `Frente: ${filtros.frente}` }] : []),
    ...(filtros.estado !== '' ? [{ id: 'pagina:estado', texto: `Estado: ${ETIQUETA_ESTADO_FILTRO[filtros.estado] ?? filtros.estado}` }] : []),
    ...chipsDeGrilla(modeloFiltrosGrid, NOMBRES_COLUMNA_SEG),
  ]

  const quitarFiltro = (id: string) => {
    if (id === 'pagina:frente') { setFiltros((f) => ({ ...f, frente: '' })); return }
    if (id === 'pagina:estado') { setFiltros((f) => ({ ...f, estado: '' })); return }
    void gridApi?.setColumnFilterModel(id, null).then(() => gridApi.onFilterChanged())
  }

  const limpiarFiltros = () => {
    setFiltros((f) => ({ ...f, frente: '', estado: '' }))
    void gridApi?.setFilterModel(null)
  }

  return (
    <section className="pdc-page">
      <header className="pdc-header">
        <div className="pdc-titulo-fila"><h1>Seguimiento del plan de compras</h1><BotonAyuda pantalla="seguimiento" /></div>
        <p className="pdc-sub">
          {seccion === 'paquetes'
            ? `${plural(visibles.length, 'paquete', 'paquetes')} de ${filas.length}. Haz clic en una fila para registrar cuándo ocurrió cada paso.`
            : 'Qué se vence, por paso y por responsable.'}
        </p>
      </header>

      {error !== '' && <p className="pdc-error" role="alert">{error}</p>}

      {/* Sin esto, quien mira «qué se me vence» ve fechas calculadas contra un cronograma que ya
          cambió, presentadas igual que las buenas. El aviso no bloquea nada: dice dónde arreglarlo. */}
      {desactualizados.length > 0 && (
        <p className="pdc-plan-aviso-recalcular" data-testid="pdc-seg-aviso-cronograma" role="status">
          {plural(desactualizados.length, 'paquete')}{' '}
          {desactualizados.length === 1 ? 'se calculó' : 'se calcularon'} contra un cronograma que ya
          cambió: las fechas de aquí abajo pueden estar viejas. Revísalo en «Plan» → «Desfases».
        </p>
      )}

      <Pestanas
        idBase="pdc-seg"
        etiquetaLista="Secciones del seguimiento"
        activa={seccion}
        onCambiar={setSeccion}
        pestanas={[
          { id: 'paquetes', etiqueta: 'Paquetes', conteo: filas.length },
          { id: 'vencimientos', etiqueta: 'Vencimientos', conteo: venc?.conteos.vencido },
          { id: 'flujo', etiqueta: 'Flujo de caja', conteo: flujo?.meses.length },
        ]}
      />

      {seccion === 'vencimientos' && (
        <PanelPestana idBase="pdc-seg" id="vencimientos">
          <p className="pdc-sub">
            Pasos pendientes de contratación, agrupados por cuándo vencen.{' '}
            {venc && <>Hoy es <strong>{venc.hoy}</strong> según el servidor.</>}
          </p>

          {/* La declaración de lo que NO se está mirando va arriba del todo y antes de los filtros: un
              tablero vacío y un tablero ciego se ven igual, y quien lo lea tiene que poder
              distinguirlos sin bajar. */}
          {venc && textoSinFechas(venc.sinFechas) !== '' && (
            <p className="pdc-venc-ciego" data-testid="pdc-venc-sin-fechas" role="status">
              {textoSinFechas(venc.sinFechas)}
            </p>
          )}

          <div className="pdc-seg-filtros">
            {/* Nunca un <label> envolviendo al Selector: un <label> sin htmlFor etiqueta a su primer
                descendiente etiquetable, y el <button> del Selector lo es. Un clic en una opción del
                popup —que vive dentro de ese mismo <label>— hace que el navegador reenvíe un click
                sintético al botón *además* del que ya manejó React, y ese segundo click alterna
                `abierto` justo después de que `onCerrar` lo puso en falso: el popup se reabre solo.
                (Costó una migración entera diagnosticarlo — ver
                .superpowers/sdd/2026-08-06-pdc-filtros-y-buscadores/diagnostico-vencimientos.md.)
                El rótulo visible va en un <span> neutro; el nombre accesible lo pone `etiqueta` vía
                aria-label, así que el <span> no necesita asociarse con el control. */}
            <span className="pdc-selector">
              <span className="pdc-selector-rotulo">Paso</span>{' '}
              <Selector
                testid="pdc-venc-filtro-paso"
                etiqueta="Filtrar por paso"
                value={filtroPaso}
                onChange={setFiltroPaso}
                opciones={[
                  { valor: '', etiqueta: 'Todos' },
                  ...(venc?.pasos ?? []).map((p) => ({ valor: p.clave, etiqueta: p.paso })),
                ]}
              />
            </span>
            <span className="pdc-selector">
              <span className="pdc-selector-rotulo">Responsable</span>{' '}
              <Selector
                testid="pdc-venc-filtro-responsable"
                etiqueta="Filtrar por responsable"
                value={filtroResp}
                onChange={setFiltroResp}
                opciones={[
                  { valor: '', etiqueta: 'Todos' },
                  ...(usuarioId !== null ? [{ valor: String(usuarioId), etiqueta: 'Los míos' }] : []),
                  { valor: 'sin', etiqueta: 'Sin responsable' },
                ]}
              />
            </span>
          </div>

          {venc && (
            <div className="pdc-venc-conteos" data-testid="pdc-venc-conteos">
              {CORTES.map((c) => (
                <span key={c.id} className={`pdc-venc-chip ${claseCorte(c.id)}`} data-corte={c.id}>
                  <strong>{venc.conteos[c.id] ?? 0}</strong> {c.etiqueta}
                </span>
              ))}
              {/* «Más adelante» se cuenta y no se lista. Enseñar el número es lo que hace que la suma
                  de los cortes cuadre con el total, sin cargar la tabla con la cola lejana. */}
              <span className="pdc-venc-chip pdc-venc--adelante" data-corte="adelante">
                <strong>{venc.conteos.adelante ?? 0}</strong> {etiquetaCorte('adelante')}
              </span>
            </div>
          )}

          {venc && CORTES.map((c) => {
            const delCorte = venc.filas.filter((f) => f.estado === c.id)
            if (delCorte.length === 0) return null
            return (
              <section key={c.id} className="pdc-venc-grupo" data-testid={`pdc-venc-grupo-${c.id}`}>
                <h3 className={`pdc-venc-titulo ${claseCorte(c.id)}`}>
                  {c.etiqueta} <span className="pdc-venc-num">{delCorte.length}</span>
                </h3>
                <table className="pdc-seg-panel-tabla">
                  <thead>
                    <tr>
                      <th scope="col">Paquete</th>
                      <th scope="col">Paso</th>
                      <th scope="col">Programado</th>
                      <th scope="col">Responsable</th>
                      <th scope="col">Desfase</th>
                    </tr>
                  </thead>
                  <tbody>
                    {delCorte.map((f: FilaVencimiento) => (
                      <tr key={`${f.paqueteId}-${f.pasoId ?? f.orden}`}>
                        <th scope="row">{f.paquete}</th>
                        <td>{f.paso}</td>
                        {/* El guion no es adorno: distingue «no tiene fecha» de un cero. */}
                        <td>{f.fechaFin ?? '—'}</td>
                        <td>{f.responsableNombre === '' ? '— sin asignar —' : f.responsableNombre}</td>
                        <td>{textoDesfase(f.diasDesfase)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </section>
            )
          })}

          {venc && venc.filas.length === 0 && (
            <p className="pdc-vacio" data-testid="pdc-venc-vacio">
              No hay pasos pendientes que venzan en las próximas seis semanas.
            </p>
          )}
        </PanelPestana>
      )}

      {seccion === 'flujo' && (
        <PanelPestana idBase="pdc-seg" id="flujo">
          {/* La advertencia del método va ARRIBA y siempre, no en un pie ni tras un despliegue. Esta
              tabla se fotografía y se lleva a comité, y sin esta frase alguien la trata como un
              presupuesto de tesorería. El texto lo manda el servidor —el mismo que va dentro del
              CSV— para que pantalla y archivo no puedan decir cosas distintas. */}
          {flujo && (
            <p className="pdc-flujo-nota" data-testid="pdc-flujo-nota" role="note">
              {flujo.nota}
            </p>
          )}

          {/* Qué parte de la curva se va a mover. Desde que la curva cuenta el presupuesto entero,
              el riesgo dejó de ser «falta plata» y pasó a ser «esta forma parece más firme de lo que
              es»: este aviso es lo que lo evita sin esconder ese dinero. */}
          {flujo && textoProvisional(flujo, moneda) !== '' && (
            <p className="pdc-flujo-provisional" data-testid="pdc-flujo-provisional" role="status">
              {textoProvisional(flujo, moneda)}
            </p>
          )}

          {/* Lo que la curva NO incluye. Ya solo pasa cuando la obra no tiene fechas con las que
              repartir, pero cuando pasa hay que decirlo: una curva que calla lo que deja fuera es una
              curva que miente. */}
          {flujo && textoExcluidos(flujo.excluidos, moneda) !== '' && (
            <p className="pdc-flujo-excluidos" data-testid="pdc-flujo-excluidos" role="status">
              {textoExcluidos(flujo.excluidos, moneda)}
            </p>
          )}

          {flujo && (
            <div className="pdc-flujo-cifras" data-testid="pdc-flujo-cifras">
              <span>
                <strong>{moneda(flujo.total)}</strong> en la curva
              </span>
              <span>
                {plural(flujo.incluidos.destinos, 'contratación', 'contrataciones')} incluida
                {flujo.incluidos.destinos === 1 ? '' : 's'}
              </span>
              {cobertura(flujo) !== null && (
                <span>
                  cubre el <strong>{cobertura(flujo)} %</strong> del valor del plan
                </span>
              )}
              {porcentajeConFecha(flujo) !== null && (
                <span>
                  <strong>{porcentajeConFecha(flujo)} %</strong> con fecha propia
                </span>
              )}
              {mesPico(flujo.meses) !== null && (
                <span>
                  pico en <strong>{etiquetaMes(mesPico(flujo.meses)!.mes)}</strong>
                </span>
              )}
              {/* Descarga directa por enlace y no por fetch: el navegador ya sabe guardar un
                  adjunto, y pasarlo por JavaScript solo añade una forma de que el archivo llegue
                  distinto de lo que el servidor generó. */}
              <a
                className="pdc-flujo-exportar"
                data-testid="pdc-flujo-exportar"
                href="/plan-compras/api/seguimiento/flujo-caja.csv"
              >
                Exportar a Excel
              </a>
            </div>
          )}

          {flujo && flujo.meses.length > 0 && (
            <table className="pdc-flujo-tabla" data-testid="pdc-flujo-tabla">
              <caption className="pdc-sub">
                Desembolso previsto por mes, con el presupuesto completo repartido.{' '}
                {flujo.duracionObra !== null && (
                  <>
                    Lo que no depende de un frente se reparte entre{' '}
                    <strong>{flujo.duracionObra.desde}</strong> y <strong>{flujo.duracionObra.hasta}</strong>,
                    la duración de la obra según{' '}
                    {flujo.duracionObra.origen === 'cronograma' ? 'el cronograma' : 'la línea base del proyecto'}.
                  </>
                )}
              </caption>
              <thead>
                <tr>
                  <th scope="col">Mes</th>
                  <th scope="col" className="pdc-num">
                    Desembolso previsto
                  </th>
                  <th scope="col" className="pdc-num">
                    Acumulado
                  </th>
                  {/* Las tres columnas de origen son lo que permite ver qué parte del mes es un
                      compromiso con fecha y qué parte es un reparto uniforme. Sin ellas la curva se
                      lee toda igual de firme. */}
                  <th scope="col" className="pdc-num">
                    Contratado
                  </th>
                  <th scope="col" className="pdc-num">
                    Nómina y provisiones
                  </th>
                  <th scope="col" className="pdc-num">
                    Provisional
                  </th>
                  <th scope="col" className="pdc-num">
                    Contrataciones
                  </th>
                </tr>
              </thead>
              <tbody>
                {flujo.meses.map((m) => (
                  <tr key={m.mes}>
                    <th scope="row">{etiquetaMes(m.mes)}</th>
                    <td className="pdc-num">
                      {/* La barra vive dentro de la celda del número, no en una columna aparte: es
                          la forma de la curva, no un dato más, y así se lee la cifra y el relieve de
                          un solo golpe sin traer una librería de gráficos. */}
                      <span
                        className="pdc-flujo-barra"
                        style={{ width: `${alturaBarra(m.previsto, mesPico(flujo.meses)!.previsto)}%` }}
                        aria-hidden="true"
                      />
                      <span className="pdc-flujo-monto">{moneda(m.previsto)}</span>
                    </td>
                    <td className="pdc-num">{moneda(m.acumulado)}</td>
                    <td className="pdc-num">{m.contratado > 0 ? moneda(m.contratado) : '—'}</td>
                    <td className="pdc-num">{m.permanente > 0 ? moneda(m.permanente) : '—'}</td>
                    <td className="pdc-num pdc-flujo-prov">
                      {m.provisional > 0 ? moneda(m.provisional) : '—'}
                    </td>
                    <td className="pdc-num">{m.destinos}</td>
                  </tr>
                ))}
              </tbody>
              <tfoot>
                <tr>
                  <th scope="row">Total</th>
                  <td className="pdc-num">
                    <strong>{moneda(flujo.total)}</strong>
                  </td>
                  <td className="pdc-num" />
                  <td className="pdc-num">{moneda(flujo.porOrigen.contratado.valor)}</td>
                  <td className="pdc-num">{moneda(flujo.porOrigen.permanente.valor)}</td>
                  <td className="pdc-num pdc-flujo-prov">{moneda(flujo.porOrigen.provisional.valor)}</td>
                  <td className="pdc-num">{flujo.incluidos.destinos}</td>
                </tr>
              </tfoot>
            </table>
          )}

          {flujo && flujo.meses.length === 0 && (
            <p className="pdc-vacio" data-testid="pdc-flujo-vacio">
              Todavía no hay nada que repartir: ni contrataciones con frente ni fechas de obra con las
              que distribuir el resto. Amarra paquetes a su frente en «Plan» y recalcula.
            </p>
          )}
        </PanelPestana>
      )}

      {seccion === 'paquetes' && (
      <PanelPestana idBase="pdc-seg" id="paquetes">
      <div className="pdc-seg-filtros">
        <label>
          <input
            type="checkbox" checked={filtros.soloMios}
            onChange={(e) => setFiltros((f) => ({ ...f, soloMios: e.target.checked }))}
          />{' '}
          Mis paquetes
        </label>
        {/* Nunca un <label> envolviendo al Selector: ver el comentario largo junto a los filtros de
            Vencimientos más arriba en este archivo — un <label> sin htmlFor reenvía un click
            sintético al <button> del Selector y reabre el popup justo tras cerrarlo. */}
        <span className="pdc-selector">
          <span className="pdc-selector-rotulo">Frente</span>{' '}
          <Selector
            etiqueta="Filtrar por frente"
            value={filtros.frente}
            onChange={(v) => setFiltros((f) => ({ ...f, frente: v }))}
            opciones={[
              { valor: '', etiqueta: 'Todos' },
              ...frentes.map((n) => ({ valor: n, etiqueta: n })),
            ]}
          />
        </span>
        <span className="pdc-selector">
          <span className="pdc-selector-rotulo">Estado</span>{' '}
          <Selector
            etiqueta="Filtrar por estado"
            value={filtros.estado}
            onChange={(v) => setFiltros((f) => ({ ...f, estado: v as FiltrosSeguimiento['estado'] }))}
            opciones={[
              { valor: '', etiqueta: 'Todos' },
              { valor: 'sin_empezar', etiqueta: 'Sin empezar' },
              { valor: 'en_curso', etiqueta: 'En curso' },
              { valor: 'terminado', etiqueta: 'Terminado' },
            ]}
          />
        </span>
        <label>
          <input
            type="checkbox" checked={filtros.soloAtrasados}
            onChange={(e) => setFiltros((f) => ({ ...f, soloAtrasados: e.target.checked }))}
          />{' '}
          Solo atrasados
        </label>
      </div>

      <input
        {...propsBuscador('Buscar en el seguimiento', 'pdc-seg-buscar')}
        value={buscaSeg}
        onChange={(e) => setBuscaSeg(e.target.value)}
      />
      <BarraFiltros chips={chipsFiltros} onQuitar={quitarFiltro} onLimpiar={limpiarFiltros} testid="pdc-seg-barra-filtros" />
      <div className="pdc-grid">
        <AgGridReact<FilaSeguimiento>
          theme={pdcTheme}
          rowData={visibles}
          // Identidad de fila estable: el seguimiento se refresca al cerrar el panel de un
          // paquete, y sin esto la fila que se acaba de tocar no es la misma para AG Grid.
          // Por destino (paquete + lote): un paquete partido trae varias filas con igual paqueteId.
          getRowId={(p) => `${p.data.paqueteId}:${p.data.subpaqueteId}`}
          quickFilterText={buscaSeg}
          columnDefs={cols}
          defaultColDef={defaultColDef}
          autoSizeStrategy={autoSizeStrategy}
          loading={cargando}
          overlayNoRowsTemplate={vacioTabla('No hay paquetes con plan calculado.')}
          onRowClicked={(e: RowClickedEvent<FilaSeguimiento>) => { if (e.data) void abrir(e.data) }}
          onGridReady={(p) => setGridApi(p.api)}
          onFilterChanged={(p) => setModeloFiltrosGrid(p.api.getFilterModel())}
          {...ajusteDeAncho}
        />
      </div>
      </PanelPestana>
      )}

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
