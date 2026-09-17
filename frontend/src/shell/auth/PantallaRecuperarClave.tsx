import { useEffect, useRef, useState, type FormEvent } from 'react';
import { solicitarRecuperacion } from '../../lib/api/auth';
import { ApiError } from '../../lib/api/cliente';
import { EsquemaSolicitudRecuperacion } from '../../lib/api/esquemas/auth';
import { IconoCorreo, IconoEnviar } from './iconos';
import { MarcoAcceso } from './MarcoAcceso';

/**
 * Pantalla pública de recuperación de clave (S02). Clasifica los fallos de
 * `solicitarRecuperacion()` en las cuatro variantes que distingue la spec (§9 «Estados de
 * interfaz»): 422 (formato, asociado al campo), 403 (CSRF vencido — ofrece «Actualizar sesión»
 * sin reenviar el correo), 503 (aviso técnico honesto del propio servidor) y red/contrato
 * (aviso técnico fijo del frontend). Ninguna variante reintenta la mutación por su cuenta.
 */
type Props = {
  csrfToken: string;
  /**
   * Revalida la sesión (recarga el bootstrap) sin reenviar el correo — la acción de
   * recuperación de un 403 `csrf_invalid`: el token quedó viejo, no el correo ingresado.
   */
  alRevalidar: () => Promise<void>;
};

const MENSAJE_FORMATO = 'Ingresa un correo electrónico válido.';
const MENSAJE_TECNICO = 'No pudimos conectar. Intenta nuevamente.';
const MENSAJE_REVALIDAR_FALLIDO = 'No pudimos actualizar la sesión. Intenta nuevamente.';

export function PantallaRecuperarClave({ csrfToken, alRevalidar }: Props) {
  const [email, setEmail] = useState('');
  const [enviando, setEnviando] = useState(false);
  const [exito, setExito] = useState<string | null>(null);
  const [errorEmail, setErrorEmail] = useState<string | null>(null);
  const [errorGeneral, setErrorGeneral] = useState<string | null>(null);
  const [requiereRevalidar, setRequiereRevalidar] = useState(false);
  const [revalidando, setRevalidando] = useState(false);
  // Marca qué destino debe recibir el foco tras el próximo commit (spec §9): el `useEffect`
  // de abajo lo consume una vez y lo limpia, para no robar foco en renders posteriores.
  const [focoPendiente, setFocoPendiente] = useState<'email' | 'alerta' | 'revalidar' | null>(null);
  const emailRef = useRef<HTMLInputElement>(null);
  const alertaRef = useRef<HTMLParagraphElement>(null);
  const revalidarRef = useRef<HTMLButtonElement>(null);

  useEffect(() => {
    if (!focoPendiente) return;

    const destino =
      focoPendiente === 'email' ? emailRef.current : focoPendiente === 'alerta' ? alertaRef.current : revalidarRef.current;
    destino?.focus();
    setFocoPendiente(null);
  }, [focoPendiente]);

  async function enviar(evento: FormEvent<HTMLFormElement>) {
    evento.preventDefault();
    if (enviando) return;

    const validacion = EsquemaSolicitudRecuperacion.safeParse({ email });
    if (!validacion.success) {
      setErrorEmail(MENSAJE_FORMATO);
      setExito(null);
      setErrorGeneral(null);
      setRequiereRevalidar(false);
      return;
    }

    setEnviando(true);
    setExito(null);
    setErrorEmail(null);
    setErrorGeneral(null);
    setRequiereRevalidar(false);

    try {
      const respuesta = await solicitarRecuperacion(validacion.data.email, csrfToken);
      setEmail('');
      setExito(respuesta.message);
    } catch (causa) {
      if (causa instanceof ApiError && causa.tipo === 'http' && causa.status === 422) {
        setErrorEmail(causa.camposInvalidos?.email ?? MENSAJE_FORMATO);
        setFocoPendiente('email');
      } else if (causa instanceof ApiError && causa.tipo === 'http' && causa.status === 403) {
        setRequiereRevalidar(true);
        setErrorGeneral(causa.message);
        setFocoPendiente('revalidar');
      } else if (causa instanceof ApiError && causa.tipo === 'http' && causa.status === 503) {
        setErrorGeneral(causa.message);
        setFocoPendiente('alerta');
      } else {
        setErrorGeneral(MENSAJE_TECNICO);
        setFocoPendiente('alerta');
      }
    } finally {
      setEnviando(false);
    }
  }

  async function actualizarSesion() {
    if (revalidando) return;
    setRevalidando(true);

    try {
      await alRevalidar();
      setRequiereRevalidar(false);
      setErrorGeneral(null);
      setFocoPendiente('email');
    } catch {
      setErrorGeneral(MENSAJE_REVALIDAR_FALLIDO);
      setFocoPendiente('revalidar');
    } finally {
      setRevalidando(false);
    }
  }

  return (
    <MarcoAcceso
      titulo="Restablecer contraseña"
      subtitulo="Ingresa tu correo y te enviaremos un enlace seguro para crear una nueva contraseña."
    >
      <form onSubmit={(evento) => void enviar(evento)} aria-busy={enviando} noValidate>
        <div className="aia-field">
          <label className="aia-label" htmlFor="recuperacion-email">
            Correo electrónico
          </label>
          <div className="aia-auth__campo-icono">
            <input
              ref={emailRef}
              id="recuperacion-email"
              name="email"
              type="email"
              className="aia-input"
              value={email}
              onChange={(evento) => {
                setEmail(evento.target.value);
                setErrorEmail(null);
              }}
              placeholder="nombre@empresa.com"
              autoComplete="email"
              autoCapitalize="none"
              autoCorrect="off"
              spellCheck={false}
              disabled={enviando}
              required
              aria-invalid={errorEmail ? true : undefined}
              aria-describedby={errorEmail ? 'recuperacion-email-error' : undefined}
            />
            <span className="aia-auth__campo-adorno">
              <IconoCorreo />
            </span>
          </div>
          {errorEmail && (
            <p id="recuperacion-email-error" role="alert" className="aia-helper">
              {errorEmail}
            </p>
          )}
        </div>

        {exito && (
          <p role="status" className="aia-alert">
            {exito}
          </p>
        )}

        {errorGeneral && (
          <p role="alert" className="aia-alert" ref={alertaRef} tabIndex={-1}>
            {errorGeneral}
            {requiereRevalidar && (
              <>
                {' '}
                <button
                  ref={revalidarRef}
                  type="button"
                  className="aia-btn aia-btn--secondary"
                  onClick={() => void actualizarSesion()}
                  disabled={revalidando}
                >
                  {revalidando ? 'Actualizando…' : 'Actualizar sesión'}
                </button>
              </>
            )}
          </p>
        )}

        <div className="aia-auth__acciones">
          <button type="submit" className="aia-btn" disabled={enviando}>
            <span>{enviando ? 'Enviando…' : 'Enviar enlace'}</span>
            <span className="aia-auth__boton-flecha">
              <IconoEnviar />
            </span>
          </button>

          <a href="/login">Volver al inicio de sesión</a>
        </div>
      </form>
    </MarcoAcceso>
  );
}
