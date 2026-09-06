import {
  useCallback,
  useEffect,
  useRef,
  useState,
  type FormEvent,
  type KeyboardEvent,
  type SyntheticEvent,
} from 'react';
import { cambiarClave, cancelarCambioClave } from '../../lib/api/auth';
import { ApiError } from '../../lib/api/cliente';
import { CampoClave } from './CampoClave';
import { MarcoAcceso } from './MarcoAcceso';

type PropiedadesCambioClaveObligatorio = {
  csrfToken: string;
  /** Confirmado por el servidor (`next: 'projects'`) — el llamador recarga el bootstrap. */
  alCompletar: () => Promise<void>;
  /**
   * Cancelación confirmada por el servidor (`next: 'login'`) — el llamador recarga el
   * bootstrap anónimo y devuelve el foco al campo de usuario. Nunca se invoca sin que
   * `cancelarCambioClave()` haya resuelto: cancelar destruye la sesión pendiente, así
   * que esta pantalla nunca lo asume de antemano.
   */
  alSalir: () => Promise<void>;
};

type EstadoErrorCambio =
  | { tipo: 'campos'; mensaje: string; campos: Readonly<Record<string, string>> }
  | { tipo: 'csrf'; mensaje: string }
  | { tipo: 'no_pendiente'; mensaje: string }
  | { tipo: 'tecnico'; mensaje: string };

const ID_TITULO = 'titulo-cambio-clave';
const ID_CAMPO_PASSWORD = 'clave-nueva';
const ID_CAMPO_CONFIRMACION = 'clave-confirmacion';

const MENSAJE_CAMPOS = 'Revisa los datos marcados.';
const MENSAJE_CSRF = 'Tu sesión de formulario ya no es válida. Recarga la página e inténtalo de nuevo.';
const MENSAJE_NO_PENDIENTE = 'Ese cambio de contraseña ya no está disponible. Vuelve a iniciar sesión.';
const MENSAJE_TECNICO = 'No pudimos conectar. Intenta de nuevo.';
const MENSAJE_ERROR_CANCELACION = 'No pudimos cerrar el cambio de contraseña. Intenta de nuevo.';

const SELECTOR_FOCABLES =
  'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

/**
 * jsdom (las pruebas) no implementa `HTMLDialogElement.prototype.showModal` — sin
 * detectarlo, un `<dialog open>` estático evita que el navegador real lo suba a la
 * capa superior (top layer): `showModal()` lanza `InvalidStateError` sobre un
 * diálogo que ya trae `open`, así que nunca llega a centrarse ni a aislar el resto
 * de la página. Solo se cae al `open` estático cuando el navegador no ofrece
 * `showModal()` — el mismo caso que ya cubre el efecto de más abajo.
 */
const SOPORTA_DIALOGO_MODAL =
  typeof HTMLDialogElement !== 'undefined' && typeof HTMLDialogElement.prototype.showModal === 'function';

/**
 * Clasifica un fallo de `cambiarClave()`. Las cinco reglas de contraseña las fija
 * `PasswordPolicyService` (Tarea 3): esta pantalla nunca las reescribe, solo muestra
 * el texto que trae `camposInvalidos` — un texto por campo, con las razones que
 * hubieran fallado unidas por el servidor con `'; '`.
 */
function clasificarErrorCambio(causa: unknown): EstadoErrorCambio {
  if (!(causa instanceof ApiError) || causa.tipo !== 'http') {
    return { tipo: 'tecnico', mensaje: MENSAJE_TECNICO };
  }

  switch (causa.status) {
    case 422:
      return { tipo: 'campos', mensaje: MENSAJE_CAMPOS, campos: causa.camposInvalidos ?? {} };
    case 403:
      return { tipo: 'csrf', mensaje: MENSAJE_CSRF };
    case 401:
      return { tipo: 'no_pendiente', mensaje: MENSAJE_NO_PENDIENTE };
    default:
      return { tipo: 'tecnico', mensaje: MENSAJE_TECNICO };
  }
}

/**
 * Cambio obligatorio y cancelación confirmada (Tarea 9, S01). Vive dentro de
 * `MarcoAcceso` (mismo `h1`, marca, tema y pie que `PantallaLogin`) y monta un
 * `<dialog>` nativo con `showModal()` — semántica de foco nativa cuando el
 * navegador la ofrece, reforzada por un atrapa-foco propio (Tab/Shift+Tab) que
 * no depende de ella, porque jsdom no implementa `showModal()`.
 *
 * **Riesgo de la Tarea 9:** cancelar destruye la sesión pendiente. `Escape` y el
 * botón "Salir" solo mueven a `confirmandoSalida` — ninguno de los dos llama a
 * `cancelarCambioClave()`. Únicamente "Confirmar salida" dispara la mutación, y lo
 * hace una sola vez (guardado por `cancelando`, igual que `enviando` guarda
 * "Actualizar y continuar"). El fondo del diálogo es inocuo a propósito: no se le
 * añade ningún manejador de clic, así que un clic fuera no hace nada — perder la
 * sesión por un clic mal puesto sería demasiado fácil.
 *
 * **Tarea 11:** `aia-auth__dialog` es una clase añadida (no sustituta) sobre
 * `aia-modal-surface`, puramente de geometría (`public/css/auth-react.css`) para
 * que el panel se presente a toda pantalla bajo 390px. No toca `showModal()`, la
 * trampa de foco ni el aislamiento del `<dialog>`.
 */
export function CambioClaveObligatorio({ csrfToken, alCompletar, alSalir }: PropiedadesCambioClaveObligatorio) {
  const [password, setPassword] = useState('');
  const [confirmation, setConfirmation] = useState('');
  const [enviando, setEnviando] = useState(false);
  const [cancelando, setCancelando] = useState(false);
  const [confirmandoSalida, setConfirmandoSalida] = useState(false);
  const [error, setError] = useState<EstadoErrorCambio | null>(null);
  const [errorCancelacion, setErrorCancelacion] = useState<string | null>(null);

  const dialogoRef = useRef<HTMLDialogElement>(null);
  const referenciaResumenError = useRef<HTMLParagraphElement>(null);
  const referenciaBotonSeguirEditando = useRef<HTMLButtonElement>(null);

  // Abre el `<dialog>` nativo cuando el navegador lo soporta (jsdom no lo hace: sin
  // `showModal()` el elemento sigue visible vía el atributo `open` declarativo del
  // JSX, así que las pruebas no dependen de esta rama) y devuelve el foco inicial al
  // primer campo — nunca al propio diálogo, que es lo que haría `showModal()` solo.
  useEffect(() => {
    const dialogo = dialogoRef.current;
    if (dialogo && !dialogo.open && typeof dialogo.showModal === 'function') {
      dialogo.showModal();
    }
    document.getElementById(ID_CAMPO_PASSWORD)?.focus();

    return () => {
      if (dialogo?.open && typeof dialogo.close === 'function') dialogo.close();
    };
  }, []);

  useEffect(() => {
    if (!error) return;

    if (error.tipo === 'campos') {
      const idPrimerCampo = error.campos.password
        ? ID_CAMPO_PASSWORD
        : error.campos.confirmation
          ? ID_CAMPO_CONFIRMACION
          : null;
      const nodo = idPrimerCampo ? document.getElementById(idPrimerCampo) : referenciaResumenError.current;
      nodo?.focus();
      return;
    }

    referenciaResumenError.current?.focus();
  }, [error]);

  // Decisión de producto (ronda de arreglos 1): el foco al abrir la confirmación va a
  // "Seguir editando" — la salida SEGURA — nunca a "Confirmar salida". La confirmación
  // existe para meter una pausa antes de perder la sesión; si el foco arrancara en el
  // botón destructivo, alguien que llega aquí por reflejo (`Escape` seguido de `Enter`,
  // el patrón típico de quien navega solo con teclado o va con prisa) tumbaría la sesión
  // sin haber leído la pregunta. En todo diálogo destructivo el foco inicial es la salida
  // segura, no la acción irreversible.
  useEffect(() => {
    if (confirmandoSalida) referenciaBotonSeguirEditando.current?.focus();
  }, [confirmandoSalida]);

  const abrirConfirmacion = useCallback(() => {
    if (enviando || confirmandoSalida) return;
    setConfirmandoSalida(true);
  }, [enviando, confirmandoSalida]);

  // Atrapa Tab/Shift+Tab dentro del diálogo — propio porque jsdom no aplica el
  // "inert" que un `showModal()` real le da al resto del documento (ver comentario
  // de la clase). Nunca deja que el foco se escape hacia el fondo.
  function atraparTab(evento: KeyboardEvent<HTMLDialogElement>) {
    if (evento.key !== 'Tab') return;

    const dialogo = dialogoRef.current;
    if (!dialogo) return;

    const focables = Array.from(dialogo.querySelectorAll<HTMLElement>(SELECTOR_FOCABLES));
    if (focables.length === 0) return;

    const primero = focables[0];
    const ultimo = focables[focables.length - 1];

    if (evento.shiftKey && document.activeElement === primero) {
      evento.preventDefault();
      ultimo.focus();
    } else if (!evento.shiftKey && document.activeElement === ultimo) {
      evento.preventDefault();
      primero.focus();
    }
  }

  function alTeclear(evento: KeyboardEvent<HTMLDialogElement>) {
    atraparTab(evento);

    if (evento.key !== 'Escape') return;
    evento.preventDefault();
    abrirConfirmacion();
  }

  // Defensa en profundidad para el navegador real: si `showModal()` sí aplicó,
  // Escape dispara `cancel` (no solo `keydown`) y su acción por defecto cerraría el
  // `<dialog>` — se previene siempre, nunca se deja que el propio navegador lo cierre.
  function alCancelarNativo(evento: SyntheticEvent<HTMLDialogElement>) {
    evento.preventDefault();
    abrirConfirmacion();
  }

  async function enviar(evento: FormEvent<HTMLFormElement>) {
    evento.preventDefault();
    if (enviando) return;

    setEnviando(true);
    setError(null);

    try {
      await cambiarClave({ password, confirmation }, csrfToken);
      setPassword('');
      setConfirmation('');
      await alCompletar();
    } catch (causa) {
      setPassword('');
      setConfirmation('');
      setError(clasificarErrorCambio(causa));
    } finally {
      setEnviando(false);
    }
  }

  async function confirmarSalida() {
    if (cancelando) return;

    setCancelando(true);
    setErrorCancelacion(null);

    try {
      await cancelarCambioClave(csrfToken);
      await alSalir();
    } catch {
      setErrorCancelacion(MENSAJE_ERROR_CANCELACION);
      setCancelando(false);
    }
  }

  function seguirEditando() {
    if (cancelando) return;
    setConfirmandoSalida(false);
    setErrorCancelacion(null);
  }

  const errorPassword = error?.tipo === 'campos' ? (error.campos.password ?? null) : null;
  const errorConfirmacion = error?.tipo === 'campos' ? (error.campos.confirmation ?? null) : null;
  const mensajeResumen = error && error.tipo !== 'campos' ? error.mensaje : null;

  return (
    <MarcoAcceso titulo="Actualiza tu contraseña" idTitulo={ID_TITULO}>
      <div className="aia-dialog" data-aia-component="dialog">
        <dialog
          ref={dialogoRef}
          open={SOPORTA_DIALOGO_MODAL ? undefined : true}
          className="aia-modal-surface aia-auth__dialog"
          aria-modal="true"
          aria-labelledby={confirmandoSalida ? 'titulo-confirmacion-salida-clave' : ID_TITULO}
          onCancel={alCancelarNativo}
          onKeyDown={alTeclear}
        >
          {confirmandoSalida ? (
            <section aria-labelledby="titulo-confirmacion-salida-clave">
              <h2 id="titulo-confirmacion-salida-clave">¿Salir del cambio de contraseña?</h2>
              <p className="aia-copy">
                Perderás la contraseña que hayas escrito y tu sesión pendiente se cerrará.
              </p>

              {errorCancelacion && (
                <p role="alert" className="aia-alert">
                  {errorCancelacion}
                </p>
              )}

              <div className="shell-week-dialog__actions">
                <button
                  type="button"
                  className="aia-btn"
                  disabled={cancelando}
                  aria-busy={cancelando}
                  onClick={() => void confirmarSalida()}
                >
                  {cancelando ? 'Saliendo…' : 'Confirmar salida'}
                </button>
                <button
                  type="button"
                  className="aia-btn aia-btn--secondary"
                  ref={referenciaBotonSeguirEditando}
                  disabled={cancelando}
                  onClick={seguirEditando}
                >
                  Seguir editando
                </button>
              </div>
            </section>
          ) : (
            <form onSubmit={(evento) => void enviar(evento)} aria-busy={enviando}>
              {mensajeResumen && (
                <p role="alert" className="aia-alert" ref={referenciaResumenError} tabIndex={-1}>
                  {mensajeResumen}
                </p>
              )}

              <CampoClave
                id={ID_CAMPO_PASSWORD}
                name="password"
                label="Nueva contraseña"
                value={password}
                onChange={setPassword}
                autoComplete="new-password"
                disabled={enviando}
                error={errorPassword}
              />

              <CampoClave
                id={ID_CAMPO_CONFIRMACION}
                name="confirmation"
                label="Confirmar contraseña"
                value={confirmation}
                onChange={setConfirmation}
                autoComplete="new-password"
                disabled={enviando}
                error={errorConfirmacion}
              />

              {/* Mismo contenedor que `PantallaLogin` (Tarea 14): sueltos en el flujo del
                  formulario los dos botones quedaban pegados uno junto al otro. */}
              <div className="aia-auth__acciones">
                <button type="submit" className="aia-btn" disabled={enviando}>
                  {enviando ? 'Actualizando…' : 'Actualizar y continuar'}
                </button>
                <button
                  type="button"
                  className="aia-btn aia-btn--secondary"
                  disabled={enviando}
                  onClick={abrirConfirmacion}
                >
                  Salir
                </button>
              </div>
            </form>
          )}
        </dialog>
      </div>
    </MarcoAcceso>
  );
}
