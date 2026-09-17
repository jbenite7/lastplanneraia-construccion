import { useState, type FormEvent } from 'react';
import { solicitarRecuperacion } from '../../lib/api/auth';
import { EsquemaSolicitudRecuperacion } from '../../lib/api/esquemas/auth';
import { IconoCorreo, IconoEnviar } from './iconos';
import { MarcoAcceso } from './MarcoAcceso';

/**
 * Pantalla pública de recuperación de clave (S02, Tarea 5). `alRevalidar` se conserva en la
 * interfaz sin usarse aquí — la Tarea 6 la conecta al manejo de 403 (`csrf_invalid`), que
 * necesita refrescar el bootstrap sin reenviar el correo. Task 6 también clasifica 403/422/503;
 * aquí solo hay validación local (formato) y un error técnico genérico para cualquier fallo de
 * transporte (spec §"Red/contrato": «No pudimos conectar. Intenta nuevamente.»).
 */
type Props = {
  csrfToken: string;
  alRevalidar: () => Promise<void>;
};

const MENSAJE_FORMATO = 'Ingresa un correo electrónico válido.';
const MENSAJE_TECNICO = 'No pudimos conectar. Intenta nuevamente.';

export function PantallaRecuperarClave({ csrfToken }: Props) {
  const [email, setEmail] = useState('');
  const [enviando, setEnviando] = useState(false);
  const [exito, setExito] = useState<string | null>(null);
  const [errorEmail, setErrorEmail] = useState<string | null>(null);
  const [errorGeneral, setErrorGeneral] = useState<string | null>(null);

  async function enviar(evento: FormEvent<HTMLFormElement>) {
    evento.preventDefault();
    if (enviando) return;

    const validacion = EsquemaSolicitudRecuperacion.safeParse({ email });
    if (!validacion.success) {
      setErrorEmail(MENSAJE_FORMATO);
      setExito(null);
      setErrorGeneral(null);
      return;
    }

    setEnviando(true);
    setExito(null);
    setErrorEmail(null);
    setErrorGeneral(null);

    try {
      const respuesta = await solicitarRecuperacion(validacion.data.email, csrfToken);
      setEmail('');
      setExito(respuesta.message);
    } catch {
      // Task 5 no distingue 403/422/503 (Task 6): cualquier fallo de transporte cae en el mismo
      // aviso técnico genérico, conservando el correo (spec S02-UX-08).
      setErrorGeneral(MENSAJE_TECNICO);
    } finally {
      setEnviando(false);
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
          <p role="alert" className="aia-alert">
            {errorGeneral}
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
