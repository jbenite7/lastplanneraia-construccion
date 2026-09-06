import { useEffect, useRef, useState, type FormEvent } from 'react';
import { iniciarSesion } from '../../lib/api/auth';
import { ApiError } from '../../lib/api/cliente';
import { CampoClave } from './CampoClave';
import { MarcoAcceso } from './MarcoAcceso';
import type { AvisoAcceso } from './avisos';

/**
 * `mantenimiento` es la ruta oculta (Tarea 12, S01): el servidor (`MaintenanceLoginController`)
 * inyecta `action`/`error`/`csrfToken` vía `configuracion.ts` — nunca se calculan aquí. Esta
 * pantalla nunca conoce la ruta oculta en sí, solo la recibe ya resuelta por request.
 */
export type ModoPantallaLogin =
  | { tipo: 'normal' }
  | { tipo: 'mantenimiento'; action: string; error: boolean; csrfToken: string };

type PropiedadesPantallaLogin = {
  csrfToken: string;
  /** Aviso ya resuelto por el llamador (ver `avisos.ts`) — consumible una vez: esta
   *  pantalla nunca vuelve a pedirlo ni lo reconstruye a partir de la URL. */
  aviso: AvisoAcceso | null;
  /** `next` de `POST /api/auth/login` en éxito (`'projects' | 'password_change'`). */
  alResolver: (next: 'projects' | 'password_change') => Promise<void>;
  /**
   * Revalida la sesión (recarga el bootstrap) sin reenviar credenciales — la acción
   * de recuperación de un 403 `csrf_invalid`: el token quedó viejo, no la contraseña.
   */
  alRevalidar: () => Promise<void>;
  modo: ModoPantallaLogin;
};

type EstadoErrorLogin =
  | { tipo: 'credenciales'; mensaje: string }
  | { tipo: 'csrf'; mensaje: string }
  | { tipo: 'campos'; mensaje: string; campos: Readonly<Record<string, string>> }
  | { tipo: 'tecnico'; mensaje: string };

const MENSAJE_CREDENCIALES = 'Usuario o contraseña incorrectos.';
const MENSAJE_CSRF = 'Tu sesión de formulario ya no es válida. Actualiza la sesión e inténtalo de nuevo.';
const MENSAJE_CAMPOS = 'Revisa los datos marcados.';
const MENSAJE_TECNICO = 'No pudimos conectar. Intenta de nuevo.';

/**
 * Clasifica un fallo de `iniciarSesion()` en una de las cuatro variantes que esta
 * pantalla distingue. Deliberadamente indistinguible entre usuario inexistente,
 * clave equivocada o cuenta inactiva (401): el servidor ya emite el mismo cuerpo
 * para los tres (Tarea 5), y esta capa no debe reintroducir la diferencia con un
 * texto más "útil".
 */
function clasificarErrorLogin(causa: unknown): EstadoErrorLogin {
  if (!(causa instanceof ApiError) || causa.tipo !== 'http') {
    return { tipo: 'tecnico', mensaje: MENSAJE_TECNICO };
  }

  switch (causa.status) {
    case 401:
      return { tipo: 'credenciales', mensaje: MENSAJE_CREDENCIALES };
    case 403:
      return { tipo: 'csrf', mensaje: MENSAJE_CSRF };
    case 422:
      return { tipo: 'campos', mensaje: MENSAJE_CAMPOS, campos: causa.camposInvalidos ?? {} };
    default:
      return { tipo: 'tecnico', mensaje: MENSAJE_TECNICO };
  }
}

export function PantallaLogin({ csrfToken, aviso, alResolver, alRevalidar, modo }: PropiedadesPantallaLogin) {
  const [usuario, setUsuario] = useState('');
  const [clave, setClave] = useState('');
  const [enviando, setEnviando] = useState(false);
  const [error, setError] = useState<EstadoErrorLogin | null>(null);
  const referenciaResumenError = useRef<HTMLParagraphElement>(null);

  // Foco de entrada al error (spec T01 §14): el resumen para credenciales/csrf/
  // técnico, o el primer campo inválido cuando el 422 trae `campos`.
  useEffect(() => {
    if (!error) return;

    if (error.tipo === 'campos') {
      const idPrimerCampo = error.campos.username ? 'usuario' : error.campos.password ? 'clave' : null;
      const nodo = idPrimerCampo ? document.getElementById(idPrimerCampo) : referenciaResumenError.current;
      nodo?.focus();
      return;
    }

    referenciaResumenError.current?.focus();
  }, [error]);

  if (modo.tipo === 'mantenimiento') {
    // Formulario nativo: `method="post" action={modo.action}` — un POST real de navegador,
    // nunca una llamada a `iniciarSesion()` (que usa `pedir()`/fetch contra `/api/auth/login`).
    // El servidor resuelve la respuesta (200/401/303) y
    // vuelve a servir este mismo host con una configuración de runtime nueva; esta pantalla
    // no interpreta ni retiene nada entre envíos.
    return (
      <MarcoAcceso titulo="Entrar">
        {modo.error && (
          <p role="alert" className="aia-alert">
            {MENSAJE_CREDENCIALES}
          </p>
        )}

        <form method="post" action={modo.action}>
          <input type="hidden" name="csrf_token" value={modo.csrfToken} />

          <div className="aia-field">
            <label className="aia-label" htmlFor="usuario">
              Usuario
            </label>
            <input
              id="usuario"
              name="usuario"
              className="aia-input"
              value={usuario}
              onChange={(evento) => setUsuario(evento.target.value)}
              autoComplete="username"
              autoCapitalize="none"
              autoCorrect="off"
              spellCheck={false}
              required
            />
          </div>

          <CampoClave
            id="clave"
            name="password"
            label="Contraseña"
            value={clave}
            onChange={setClave}
            autoComplete="current-password"
          />

          <div className="aia-auth__acciones">
            <button type="submit" className="aia-btn">
              Entrar
            </button>
          </div>
        </form>
      </MarcoAcceso>
    );
  }

  async function enviar(evento: FormEvent<HTMLFormElement>) {
    evento.preventDefault();
    if (enviando) return;

    setEnviando(true);
    setError(null);

    try {
      const respuesta = await iniciarSesion({ username: usuario, password: clave }, csrfToken);
      setClave('');
      await alResolver(respuesta.next);
    } catch (causa) {
      // Nunca se conserva la contraseña tras un intento fallido, sea cual sea el motivo.
      setClave('');
      setError(clasificarErrorLogin(causa));
    } finally {
      setEnviando(false);
    }
  }

  async function actualizarSesion() {
    setError(null);
    await alRevalidar();
  }

  const errorUsuario = error?.tipo === 'campos' ? (error.campos.username ?? null) : null;
  const errorClave = error?.tipo === 'campos' ? (error.campos.password ?? null) : null;

  return (
    <MarcoAcceso titulo="Entrar">
      {aviso && (
        <p role="status" className="aia-alert">
          {aviso.mensaje}
        </p>
      )}

      <form onSubmit={(evento) => void enviar(evento)} aria-busy={enviando}>
        {error && (
          <p role="alert" className="aia-alert" ref={referenciaResumenError} tabIndex={-1}>
            {error.mensaje}
            {error.tipo === 'csrf' && (
              <>
                {' '}
                <button
                  type="button"
                  className="aia-btn aia-btn--secondary"
                  onClick={() => void actualizarSesion()}
                >
                  Actualizar sesión
                </button>
              </>
            )}
          </p>
        )}

        <div className="aia-field">
          <label className="aia-label" htmlFor="usuario">
            Usuario
          </label>
          <input
            id="usuario"
            name="username"
            className="aia-input"
            value={usuario}
            onChange={(evento) => setUsuario(evento.target.value)}
            autoComplete="username"
            autoCapitalize="none"
            autoCorrect="off"
            spellCheck={false}
            disabled={enviando}
            required
            aria-invalid={errorUsuario ? true : undefined}
            aria-describedby={errorUsuario ? 'usuario-error' : undefined}
          />
          {errorUsuario && (
            <p id="usuario-error" role="alert" className="aia-helper">
              {errorUsuario}
            </p>
          )}
        </div>

        <CampoClave
          id="clave"
          name="password"
          label="Contraseña"
          value={clave}
          onChange={setClave}
          autoComplete="current-password"
          disabled={enviando}
          error={errorClave}
        />

        {/* Tarea 14: el botón y el enlace estaban sueltos en el flujo del formulario, así que
            quedaban pegados en la misma línea y se leían como "Entrar¿Olvidaste tu contraseña?".
            El contenedor los separa y los apila: la acción principal a ancho completo, la salida
            secundaria debajo. */}
        <div className="aia-auth__acciones">
          <button type="submit" className="aia-btn" disabled={enviando}>
            {enviando ? 'Entrando…' : 'Entrar'}
          </button>

          <a href="/password/forgot">¿Olvidaste tu contraseña?</a>
        </div>
      </form>
    </MarcoAcceso>
  );
}
