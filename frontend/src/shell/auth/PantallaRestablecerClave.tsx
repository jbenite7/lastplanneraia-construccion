import { useEffect, useRef, useState, type FormEvent } from 'react';
import { restablecerClave, validarEnlaceReset } from '../../lib/api/auth';
import { ApiError } from '../../lib/api/cliente';
import { EsquemaSolicitudRestablecerClave, MENSAJE_ENLACE_RESET_INVALIDO } from '../../lib/api/esquemas/auth';
import { CampoClave } from './CampoClave';
import { IconoLlave } from './iconos';
import { MarcoAcceso } from './MarcoAcceso';
import type { EnlaceReset } from './tokenReset';

/**
 * Pantalla pública de restablecimiento de clave (S03, Tarea 5). Cubre solo la validación del
 * enlace y sus estados terminales — carga, válido e inválido —, siguiendo el patrón de fallos
 * de `PantallaRecuperarClave` (S02): 403 (CSRF vencido, ofrece revalidar), 503 (aviso técnico
 * del propio servidor) y red/contrato (aviso técnico fijo del frontend). Ninguna variante
 * reintenta la validación por su cuenta — la única repetición ocurre tras una acción humana
 * explícita (spec §7 "no hay retry automático").
 *
 * El estado `valid` (Tarea 6) trae la política visible, dos campos de contraseña con toggles
 * independientes y validación local contra `EsquemaSolicitudRestablecerClave` — el mismo esquema
 * cliente↔servidor de la Tarea 1. La validación se detiene en la primera regla que falla (orden
 * fijo: longitud, mayúscula, carácter especial, coincidencia) y mueve el foco al campo señalado;
 * mientras alguna regla local falle, `restablecerClave()` no se llama.
 *
 * Envío real (Tarea 7): una sola mutación por envío (candado síncrono `enviandoRef` además del
 * `disabled`), sin reintentos automáticos. Tras CUALQUIER liquidación se limpian los dos secretos
 * y los alternadores vuelven a ocultar (los campos se remontan por `key`). 422 marca el campo y lo
 * enfoca; 403 ofrece «Actualizar sesión» sin reenviar; 410 pasa al estado inválido; 503 muestra
 * el mensaje del servidor y red/contrato un aviso fijo que no afirma éxito. El éxito solo navega
 * si `redirect` es exactamente `RUTA_EXITO` — nunca una redirección abierta.
 */
type Props = {
  enlace: EnlaceReset;
  csrfToken: string;
  /**
   * Revalida la sesión (recarga el bootstrap) sin volver a validar el enlace por sí sola — la
   * acción de recuperación de un 403 `csrf_invalid`: el token CSRF quedó viejo, no el enlace.
   */
  alRevalidar: () => Promise<void>;
  /**
   * Navega tras el éxito. Solo recibe `RUTA_EXITO`; la ruta la implementa reemplazando el
   * historial para que la URL con el token no quede atrás.
   */
  alCompletar: (ruta: string) => void;
};

type LinkState =
  | { kind: 'validating' }
  | { kind: 'valid' }
  | { kind: 'invalid'; message: string }
  | { kind: 'error'; message: string; csrf: boolean };

const MENSAJE_TECNICO = 'No pudimos validar el enlace. Intenta nuevamente.';
const MENSAJE_REVALIDAR_FALLIDO = 'No pudimos actualizar la sesión. Intenta nuevamente.';
// Red, respuesta malformada o redirect inesperado: el cambio pudo o no aplicarse, así que no se
// afirma éxito ni fracaso — se orienta a comprobarlo.
const MENSAJE_NO_CONFIRMADO =
  'No pudimos confirmar el cambio. Intenta iniciar sesión; si no funciona, solicita un enlace nuevo.';
export const RUTA_EXITO = '/login?reset=1';
// Paridad visual (correcciones §7, referencia `legacy-{valido,invalido}-*.png`): el subtítulo se
// ve en TODOS los estados, no solo en el formulario — incluido el inválido.
const SUBTITULO = 'Usa al menos 6 caracteres, una mayúscula y un carácter especial.';

function esApiError403(causa: unknown): boolean {
  return causa instanceof ApiError && causa.tipo === 'http' && causa.status === 403;
}

function esApiError503(causa: unknown): boolean {
  return causa instanceof ApiError && causa.tipo === 'http' && causa.status === 503;
}

function esApiErrorHttp(causa: unknown, status: number): causa is ApiError {
  return causa instanceof ApiError && causa.tipo === 'http' && causa.status === status;
}

type Foco = 'solicitar-enlace' | 'accion-error' | 'alerta' | 'password' | 'confirm' | 'revalidar';

export function PantallaRestablecerClave({ enlace, csrfToken, alRevalidar, alCompletar }: Props) {
  const [linkState, setLinkState] = useState<LinkState>(
    enlace.kind === 'invalid' ? { kind: 'invalid', message: MENSAJE_ENLACE_RESET_INVALIDO } : { kind: 'validating' },
  );
  const [reintentando, setReintentando] = useState(false);
  // Marca qué destino debe recibir el foco tras el próximo commit, igual que
  // `PantallaRecuperarClave`: se consume una vez y se limpia.
  const [focoPendiente, setFocoPendiente] = useState<Foco | null>(null);
  const [validationAttempt, setValidationAttempt] = useState(0);
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  // Un campo de error a la vez (spec §"la política se detiene en la primera falla"): nunca los
  // dos a la vez, para que la persona corrija una cosa por vez, igual que en el servidor.
  const [fieldErrors, setFieldErrors] = useState<{ password: string | null; confirmPassword: string | null }>({
    password: null,
    confirmPassword: null,
  });
  const [submitting, setSubmitting] = useState(false);
  // Candado síncrono: dos eventos en el mismo tick (doble click, Enter) llegan antes de que
  // `submitting` se pinte, así que el estado solo no cierra la ventana.
  const enviandoRef = useRef(false);
  // Avanza tras cada liquidación: la `key` remonta los `CampoClave` y sus alternadores vuelven a
  // ocultar, sin exponer un reset imperativo en el componente compartido.
  const [versionSecretos, setVersionSecretos] = useState(0);
  const [errorGeneral, setErrorGeneral] = useState<string | null>(null);
  const [requiereRevalidar, setRequiereRevalidar] = useState(false);
  const [revalidando, setRevalidando] = useState(false);
  const revalidarRef = useRef<HTMLButtonElement>(null);
  const csrfTokenRef = useRef(csrfToken);
  csrfTokenRef.current = csrfToken;
  const solicitarEnlaceRef = useRef<HTMLAnchorElement>(null);
  const accionErrorRef = useRef<HTMLButtonElement>(null);
  const alertaRef = useRef<HTMLParagraphElement>(null);
  const passwordRef = useRef<HTMLInputElement>(null);
  const confirmRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    if (enlace.kind === 'invalid') {
      setLinkState({ kind: 'invalid', message: MENSAJE_ENLACE_RESET_INVALIDO });
      setFocoPendiente('solicitar-enlace');
      return;
    }

    const controller = new AbortController();
    setLinkState({ kind: 'validating' });

    // `csrfTokenRef` (no `csrfToken` en dependencias): tras un 403, `alRevalidar()` trae un
    // token CSRF nuevo y `validationAttempt` avanza en la misma acción — si `csrfToken` también
    // formara parte de las dependencias, este efecto correría dos veces por el mismo click
    // (spec §7 "no hay retry automático").
    validarEnlaceReset(enlace.token, csrfTokenRef.current, controller.signal)
      .then((respuesta) => {
        if (controller.signal.aborted) return;
        if (respuesta.state === 'valid') {
          setLinkState({ kind: 'valid' });
          return;
        }
        setPassword('');
        setConfirmPassword('');
        setLinkState({ kind: 'invalid', message: respuesta.message });
        setFocoPendiente('solicitar-enlace');
      })
      .catch((causa) => {
        if (controller.signal.aborted) return;
        if (causa instanceof ApiError && causa.tipo === 'abortado') return;

        const csrf = esApiError403(causa);
        const mensaje = esApiError503(causa) || csrf ? (causa as ApiError).message : MENSAJE_TECNICO;
        setLinkState({ kind: 'error', message: mensaje, csrf });
        setFocoPendiente('accion-error');
      });

    return () => controller.abort();
    // No hay linter de hooks en este frontend (correcciones §8: aquí el gate es typecheck +
    // vitest) — se deja `csrfToken` fuera de las dependencias a propósito, ver el comentario de
    // `csrfTokenRef` arriba.
  }, [enlace, validationAttempt]);

  useEffect(() => {
    if (!focoPendiente) return;

    const destinos: Record<Foco, { current: HTMLElement | null }> = {
      'solicitar-enlace': solicitarEnlaceRef,
      'accion-error': accionErrorRef,
      alerta: alertaRef,
      password: passwordRef,
      confirm: confirmRef,
      revalidar: revalidarRef,
    };
    destinos[focoPendiente].current?.focus();
    setFocoPendiente(null);
  }, [focoPendiente, linkState, versionSecretos]);

  async function reintentarValidacion() {
    if (reintentando) return;
    setReintentando(true);

    try {
      if (linkState.kind === 'error' && linkState.csrf) {
        await alRevalidar();
      }
      setValidationAttempt((actual) => actual + 1);
    } catch {
      setLinkState({ kind: 'error', message: MENSAJE_REVALIDAR_FALLIDO, csrf: true });
      setFocoPendiente('accion-error');
    } finally {
      setReintentando(false);
    }
  }

  // Valida localmente contra el mismo esquema que usa `restablecerClave()` (Tarea 1) y mueve el
  // foco al campo señalado por el primer issue — nunca los dos campos a la vez. `token` viaja
  // solo para que `superRefine` corra completo; su propio patrón ya lo garantizó `leerTokenReset`
  // antes de llegar aquí, así que nunca es la causa del primer issue en la práctica.
  async function enviarFormulario(evento: FormEvent<HTMLFormElement>) {
    evento.preventDefault();
    if (enviandoRef.current || enlace.kind !== 'candidate') return;

    const resultado = EsquemaSolicitudRestablecerClave.safeParse({
      token: enlace.token,
      password,
      confirmPassword,
    });

    if (resultado.success) {
      await enviarAlServidor(resultado.data);
      return;
    }

    const issue = resultado.error.issues[0];
    const campo = issue.path[0] === 'confirmPassword' ? 'confirmPassword' : 'password';
    setFieldErrors({
      password: campo === 'password' ? issue.message : null,
      confirmPassword: campo === 'confirmPassword' ? issue.message : null,
    });
    requestAnimationFrame(() => {
      (campo === 'confirmPassword' ? confirmRef.current : passwordRef.current)?.focus();
    });
  }

  function limpiarSecretos() {
    setPassword('');
    setConfirmPassword('');
    setVersionSecretos((actual) => actual + 1);
  }

  async function enviarAlServidor(solicitud: Parameters<typeof restablecerClave>[0]) {
    enviandoRef.current = true;
    setSubmitting(true);
    setErrorGeneral(null);
    setRequiereRevalidar(false);
    setFieldErrors({ password: null, confirmPassword: null });

    try {
      const respuesta = await restablecerClave(solicitud, csrfToken);
      limpiarSecretos();
      if (respuesta.redirect !== RUTA_EXITO) {
        setErrorGeneral(MENSAJE_NO_CONFIRMADO);
        setFocoPendiente('alerta');
        return;
      }
      alCompletar(RUTA_EXITO);
    } catch (causa) {
      limpiarSecretos();
      if (esApiErrorHttp(causa, 422)) {
        const campos = causa.camposInvalidos ?? {};
        const errorConfirmacion = campos.confirmPassword ?? null;
        const errorPassword = campos.password ?? (errorConfirmacion ? null : causa.message);
        setFieldErrors({ password: errorPassword, confirmPassword: errorPassword ? null : errorConfirmacion });
        setFocoPendiente(errorPassword ? 'password' : 'confirm');
      } else if (esApiErrorHttp(causa, 403)) {
        setRequiereRevalidar(true);
        setErrorGeneral(causa.message);
        setFocoPendiente('revalidar');
      } else if (esApiErrorHttp(causa, 410)) {
        setLinkState({ kind: 'invalid', message: MENSAJE_ENLACE_RESET_INVALIDO });
        setFocoPendiente('solicitar-enlace');
      } else if (esApiErrorHttp(causa, 503)) {
        setErrorGeneral(causa.message);
        setFocoPendiente('alerta');
      } else {
        setErrorGeneral(MENSAJE_NO_CONFIRMADO);
        setFocoPendiente('alerta');
      }
    } finally {
      enviandoRef.current = false;
      setSubmitting(false);
    }
  }

  // «Actualizar sesión» tras un 403 del envío: solo revalida, nunca reenvía la mutación.
  async function actualizarSesion() {
    if (revalidando) return;
    setRevalidando(true);

    try {
      await alRevalidar();
      setRequiereRevalidar(false);
      setErrorGeneral(null);
      setFocoPendiente('password');
    } catch {
      setErrorGeneral(MENSAJE_REVALIDAR_FALLIDO);
      setFocoPendiente('revalidar');
    } finally {
      setRevalidando(false);
    }
  }

  if (linkState.kind === 'invalid') {
    return (
      <MarcoAcceso titulo="Define tu nueva contraseña" subtitulo={SUBTITULO}>
        <p role="alert" className="aia-alert">
          {linkState.message}
        </p>
        <div className="aia-auth__acciones">
          <a ref={solicitarEnlaceRef} href="/password/forgot">
            Solicitar un nuevo enlace
          </a>
          <a href="/login">Volver al inicio de sesión</a>
        </div>
      </MarcoAcceso>
    );
  }

  if (linkState.kind === 'validating') {
    return (
      <MarcoAcceso titulo="Define tu nueva contraseña" subtitulo={SUBTITULO}>
        <p role="status" className="aia-alert">
          Validando enlace…
        </p>
      </MarcoAcceso>
    );
  }

  if (linkState.kind === 'error') {
    return (
      <MarcoAcceso titulo="Define tu nueva contraseña" subtitulo={SUBTITULO}>
        <p role="alert" className="aia-alert" ref={alertaRef} tabIndex={-1}>
          {linkState.message}
        </p>
        <div className="aia-auth__acciones">
          <button
            ref={accionErrorRef}
            type="button"
            className="aia-btn aia-btn--secondary"
            disabled={reintentando}
            onClick={() => void reintentarValidacion()}
          >
            {linkState.csrf ? 'Actualizar sesión' : 'Intentar nuevamente'}
          </button>
          <a href="/login">Volver al inicio de sesión</a>
        </div>
      </MarcoAcceso>
    );
  }

  // `valid`: formulario accesible con política visible, dos toggles independientes, validación
  // local (Tarea 6) y envío real con sus errores (Tarea 7).
  return (
    <MarcoAcceso titulo="Define tu nueva contraseña" subtitulo={SUBTITULO}>
      <form onSubmit={(evento) => void enviarFormulario(evento)} aria-busy={submitting} noValidate>
        <CampoClave
          key={`password-${versionSecretos}`}
          ref={passwordRef}
          id="reset-password"
          name="password"
          label="Nueva contraseña"
          value={password}
          onChange={(valor) => {
            setPassword(valor);
            if (fieldErrors.password) setFieldErrors((actual) => ({ ...actual, password: null }));
          }}
          autoComplete="new-password"
          placeholder="Nueva contraseña"
          describedBy="reset-password-policy"
          error={fieldErrors.password}
          disabled={submitting}
        />
        {/* Fix ronda 1 (correcciones §7): paridad exacta con el legado
            (`views/auth/password-reset.view.php:50`) — una frase única, tras el campo, no una
            lista con otro texto ni colocada antes de ambos campos. */}
        <p id="reset-password-policy" className="aia-helper">
          Mínimo 6 caracteres, una mayúscula y un carácter especial.
        </p>

        <CampoClave
          key={`confirm-${versionSecretos}`}
          ref={confirmRef}
          id="reset-confirm"
          name="confirmPassword"
          label="Confirmar contraseña"
          value={confirmPassword}
          onChange={(valor) => {
            setConfirmPassword(valor);
            if (fieldErrors.confirmPassword) setFieldErrors((actual) => ({ ...actual, confirmPassword: null }));
          }}
          autoComplete="new-password"
          placeholder="Confirma tu contraseña"
          error={fieldErrors.confirmPassword}
          disabled={submitting}
        />

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
          <button type="submit" className="aia-btn" disabled={submitting}>
            <span>{submitting ? 'Actualizando…' : 'Actualizar contraseña'}</span>
            <span className="aia-auth__boton-flecha">
              <IconoLlave />
            </span>
          </button>
          <a href="/login">Volver al inicio de sesión</a>
        </div>
      </form>
    </MarcoAcceso>
  );
}
