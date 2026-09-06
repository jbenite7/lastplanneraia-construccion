import { useCallback, useEffect, useId, useRef, useState } from 'react';
import { esBarraLateralFlotante } from '../../../shell/modoBarraLateral';
// Ronda de arreglos 1 (Tarea 8): la hoja nunca se importaba en ningún punto del árbol de Vite —
// existía en disco, pasaba la guarda de tokens, pero jamás llegaba al bundle ni al DOM. Vite
// bundlea CSS por import de módulo (no hay un `<link>` estático para esto, a diferencia de
// `tokens.css`, que `index.html` sí enlaza); el único consumidor real de estas clases es este
// componente, así que aquí es donde corresponde importarla.
import '../lps-contexto.css';
import { EsquemaTrigger } from '../api/crisis';
import { severidadCajon } from '../dominio/severidad';
import { AccionesSos, type ContactosSos } from './AccionesSos';
import { CierreCrisis } from './CierreCrisis';
import { DiagnosticoLps } from './DiagnosticoLps';
import { DigestLps } from './DigestLps';
import { FormularioComentario } from './FormularioComentario';
import { HiloLps } from './HiloLps';
import { IndicadorRestricciones } from './IndicadorRestricciones';
import { useHiloLps } from '../estado/useHiloLps';
import { useLpsDrawer } from '../estado/useLpsDrawer';

const SELECTOR_ENFOCABLES =
  'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

async function copiarAlPortapaplesReal(texto: string): Promise<void> {
  await navigator.clipboard.writeText(texto);
}

function abrirCanalReal(url: string): void {
  window.open(url, '_blank', 'noopener,noreferrer');
}

export interface AdaptadoresCajonLps {
  copiarAlPortapapeles?: (texto: string) => Promise<void>;
  abrirCanal?: (url: string) => void;
}

/**
 * Cajón contextual LPS: única instancia montada junto al `LpsDrawerProvider` (AC-004, AC-171).
 * Compone diagnóstico, restricciones, hilo, SOS/cierre y digest sobre el estado del provider —
 * ningún componente hijo llama `pedir()`/`fetch` directamente (D-T02-01).
 *
 * Accesibilidad (AC-155..167): `role=dialog` con `aria-modal` sólo cuando bloquea el fondo
 * (bajo el umbral de 1180px, mismo corte que el drawer de navegación de T01); foco de entrada al
 * encabezado, trampa Tab/Shift+Tab, Escape/overlay con protección de borrador, `inert` en el
 * contenido de fondo mientras es modal, y retorno de foco al disparador o al fallback del
 * contexto si éste ya no existe en el DOM (AC-029/030).
 */
export function CajonContextualLps({ copiarAlPortapapeles = copiarAlPortapaplesReal, abrirCanal = abrirCanalReal }: AdaptadoresCajonLps = {}) {
  const { estado, cerrar, disparadorRef, comentar, registrarSos, cerrarCrisisAlerta, marcarOcultaPorFiltros } = useLpsDrawer();
  const vistaHilo = useHiloLps();
  const contenedorRef = useRef<HTMLDivElement>(null);
  const [hayBorrador, setHayBorrador] = useState(false);
  const [confirmarCierre, setConfirmarCierre] = useState(false);
  const [modal, setModal] = useState(() => esBarraLateralFlotante(typeof window === 'undefined' ? Infinity : window.innerWidth));
  const idTitulo = useId();
  const abierto = estado.status !== 'closed';

  useEffect(() => {
    function sincronizar() {
      setModal(esBarraLateralFlotante(window.innerWidth));
    }
    sincronizar();
    window.addEventListener('resize', sincronizar);
    return () => window.removeEventListener('resize', sincronizar);
  }, []);

  const cerrarConProteccion = useCallback(() => {
    if (hayBorrador) {
      setConfirmarCierre(true);
      return;
    }
    cerrar();
  }, [hayBorrador, cerrar]);

  const confirmarDescartar = useCallback(() => {
    setHayBorrador(false);
    setConfirmarCierre(false);
    cerrar();
  }, [cerrar]);

  // AC-158: al abrir, el foco entra al encabezado/primer control útil.
  useEffect(() => {
    if (!abierto) return;
    const primero = contenedorRef.current?.querySelector<HTMLElement>(SELECTOR_ENFOCABLES);
    primero?.focus();
  }, [abierto]);

  // Recuerda el fallback de retorno de foco del último contexto abierto — al cerrar, `estado` ya
  // volvió a `closed` y no lo trae consigo (AC-030).
  const fallbackRetornoFoco = useRef<(() => void) | undefined>(undefined);
  useEffect(() => {
    if (estado.status !== 'closed') {
      fallbackRetornoFoco.current = estado.contexto.retornarFocoAlternativo;
    }
  }, [estado]);

  // AC-163: al cerrar, el foco vuelve al disparador — o al fallback del contexto si desapareció.
  useEffect(() => {
    if (abierto) return;
    const disparador = disparadorRef.current;
    if (disparador && document.contains(disparador)) {
      disparador.focus();
      return;
    }
    fallbackRetornoFoco.current?.();
  }, [abierto, disparadorRef]);

  // AC-159/160/162: trampa de foco + Escape con protección de borrador, sólo en modo modal.
  useEffect(() => {
    if (!abierto || !modal) return;
    function alTeclado(evento: KeyboardEvent) {
      if (evento.key === 'Escape') {
        evento.preventDefault();
        cerrarConProteccion();
        return;
      }
      if (evento.key !== 'Tab') return;
      const contenedor = contenedorRef.current;
      if (!contenedor) return;
      const enfocables = contenedor.querySelectorAll<HTMLElement>(SELECTOR_ENFOCABLES);
      if (enfocables.length === 0) return;
      const primero = enfocables[0];
      const ultimo = enfocables[enfocables.length - 1];
      const activo = document.activeElement;
      if (!contenedor.contains(activo)) {
        evento.preventDefault();
        primero.focus();
      } else if (evento.shiftKey && activo === primero) {
        evento.preventDefault();
        ultimo.focus();
      } else if (!evento.shiftKey && activo === ultimo) {
        evento.preventDefault();
        primero.focus();
      }
    }
    document.addEventListener('keydown', alTeclado);
    return () => document.removeEventListener('keydown', alTeclado);
  }, [abierto, modal, cerrarConProteccion]);

  // AC-162: inert en el contenido de fondo mientras el cajón es modal.
  useEffect(() => {
    if (!abierto || !modal) return;
    const contenido = document.getElementById('contenido');
    contenido?.setAttribute('inert', '');
    return () => {
      contenido?.removeAttribute('inert');
    };
  }, [abierto, modal]);

  if (!abierto) return null;

  const contexto = estado.contexto;
  const severidad = estado.status === 'partial-error' || estado.status === 'opening' || estado.status === 'loading'
    ? 'neutral'
    : severidadCajon({
        stateKey: contexto.activity.state.key,
        stateView: { state: contexto.activity.state.key, label: contexto.activity.state.label, phase: contexto.activity.state.phase, actions: contexto.activity.state.actions },
        isCritical: contexto.activity.critical,
        semanasInicio: null,
        isLiberada: contexto.activity.progress.ratio >= 1,
        isStartedByProgress: contexto.activity.progress.ratio > 0.001,
        isDueOrOverdue: false,
        progressRatio: contexto.activity.progress.ratio,
        deepGap: false,
        isActionableState: true,
        isHeader: contexto.activity.isHeader,
        isSOS: contexto.crisis?.active ?? false,
        moduleKey: contexto.module === 'ESC' ? 'programacion-semanal' : null,
      });

  const acciones = vistaHilo.acciones;
  const consecutivo = 'consecutivo' in contexto.target ? contexto.target.consecutivo : contexto.activity.id;
  const contactos: ContactosSos | undefined = contexto.contacts;

  return (
    <>
      {modal ? (
        <div className="lps-cajon-velo" onClick={cerrarConProteccion} aria-hidden="true" />
      ) : null}
      <div
        ref={contenedorRef}
        className="lps-cajon"
        role="dialog"
        aria-modal={modal}
        aria-labelledby={idTitulo}
      >
        <header className="lps-cajon__encabezado">
          <div className="lps-cajon__titulo-fila">
            <h2 id={idTitulo}>{contexto.activity.label}</h2>
            <button type="button" className="aia-btn aia-btn--secondary lps-cajon__cerrar" onClick={cerrarConProteccion} aria-label="Cerrar">
              Cerrar
            </button>
          </div>
          {estado.ocultaPorFiltros ? <p role="status">Oculta por los filtros</p> : null}
        </header>

        <div className="lps-cajon__cuerpo">
          {vistaHilo.cargandoInicial ? <p role="status">Cargando…</p> : null}

          {vistaHilo.error?.noDisponible ? (
            <p role="alert">Esta actividad o alerta ya no está disponible.</p>
          ) : null}

          {!vistaHilo.cargandoInicial && !vistaHilo.error?.noDisponible ? (
            <>
              <DiagnosticoLps contexto={contexto} ocultaPorFiltros={estado.ocultaPorFiltros} />

              <IndicadorRestricciones fila={contexto.restrictions.values} config={contexto.restrictions.config} />

              {contexto.biHref ? (
                <p>
                  <a href={contexto.biHref} target="_blank" rel="noopener noreferrer">
                    Ver en BI
                  </a>
                </p>
              ) : null}

              <HiloLps comentarios={vistaHilo.comentarios} actualizando={vistaHilo.actualizando} />

              <FormularioComentario
                deshabilitado={acciones ? !acciones.comment : true}
                enviar={async (input) => comentar(input)}
                alCambiarTexto={(texto) => setHayBorrador(texto.trim() !== '')}
              />

              {vistaHilo.errorMutacion ? <p role="status">{vistaHilo.errorMutacion}</p> : null}

              {contexto.module !== 'ESC' ? (
                <AccionesSos
                  simulado={contexto.simulado ?? true}
                  consecutivo={consecutivo}
                  modulo={contexto.module}
                  actividad={contexto.activity.label}
                  subcontratista={contexto.subcontratista ?? ''}
                  restriccion={contexto.restriccionResumen ?? ''}
                  nivelActual={contexto.crisis?.level ?? 1}
                  contactos={contactos}
                  deshabilitado={acciones ? !acciones.notifyNext : true}
                  registrarCrisis={async (input) => registrarSos({ trigger: EsquemaTrigger.parse(input.trigger) })}
                  copiarAlPortapapeles={copiarAlPortapapeles}
                  abrirCanal={abrirCanal}
                />
              ) : null}

              {contexto.crisis?.active && contexto.crisis.alertId ? (
                <CierreCrisis
                  alertaId={contexto.crisis.alertId}
                  deshabilitado={acciones ? !acciones.close : true}
                  cerrarCrisis={async (input) => cerrarCrisisAlerta(input)}
                />
              ) : null}

              {contexto.digestFilas && contexto.digestFilas.length > 0 ? (
                <DigestLps
                  filas={contexto.digestFilas}
                  config={contexto.restrictions.config}
                  fecha={new Date()}
                  copiarAlPortapapeles={copiarAlPortapapeles}
                />
              ) : null}

              <p>Modo {contexto.simulado ?? true ? 'simulación' : 'operativo'}: {contexto.simulado ?? true ? 'las acciones SOS sólo preparan/copian texto, no mutan nada.' : 'las acciones SOS registran la alerta en el servidor.'}</p>
            </>
          ) : null}
        </div>

        {confirmarCierre ? (
          <footer className="lps-cajon__pie" role="alertdialog" aria-label="Confirmar cierre con borrador">
            <p>Tienes un comentario sin enviar. ¿Descartarlo y cerrar?</p>
            <button type="button" onClick={confirmarDescartar}>
              Descartar y cerrar
            </button>
            <button type="button" onClick={() => setConfirmarCierre(false)}>
              Seguir editando
            </button>
          </footer>
        ) : null}
      </div>
    </>
  );
}
