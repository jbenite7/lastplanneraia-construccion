import { useEffect, useRef, useState } from 'react';
import { validarEnlaceReset } from '../../lib/api/auth';
import { ApiError } from '../../lib/api/cliente';
import { MENSAJE_ENLACE_RESET_INVALIDO } from '../../lib/api/esquemas/auth';
import { CampoClave } from './CampoClave';
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
 * El campo de contraseña que se ve en `valid` es un esqueleto mínimo (Tarea 6 lo completa con
 * política, dos toggles independientes, ayuda asociada y el submit real de Tarea 7); aquí no
 * hay mutación ni botón de envío todavía.
 */
type Props = {
  enlace: EnlaceReset;
  csrfToken: string;
  /**
   * Revalida la sesión (recarga el bootstrap) sin volver a validar el enlace por sí sola — la
   * acción de recuperación de un 403 `csrf_invalid`: el token CSRF quedó viejo, no el enlace.
   */
  alRevalidar: () => Promise<void>;
};

type LinkState =
  | { kind: 'validating' }
  | { kind: 'valid' }
  | { kind: 'invalid'; message: string }
  | { kind: 'error'; message: string; csrf: boolean };

const MENSAJE_TECNICO = 'No pudimos validar el enlace. Intenta nuevamente.';
const MENSAJE_REVALIDAR_FALLIDO = 'No pudimos actualizar la sesión. Intenta nuevamente.';
// Paridad visual (correcciones §7, referencia `legacy-{valido,invalido}-*.png`): el subtítulo se
// ve en TODOS los estados, no solo en el formulario — incluido el inválido.
const SUBTITULO = 'Usa al menos 6 caracteres, una mayúscula y un carácter especial.';

function esApiError403(causa: unknown): boolean {
  return causa instanceof ApiError && causa.tipo === 'http' && causa.status === 403;
}

function esApiError503(causa: unknown): boolean {
  return causa instanceof ApiError && causa.tipo === 'http' && causa.status === 503;
}

export function PantallaRestablecerClave({ enlace, csrfToken, alRevalidar }: Props) {
  const [linkState, setLinkState] = useState<LinkState>(
    enlace.kind === 'invalid' ? { kind: 'invalid', message: MENSAJE_ENLACE_RESET_INVALIDO } : { kind: 'validating' },
  );
  const [reintentando, setReintentando] = useState(false);
  // Marca qué destino debe recibir el foco tras el próximo commit, igual que
  // `PantallaRecuperarClave`: se consume una vez y se limpia.
  const [focoPendiente, setFocoPendiente] = useState<'solicitar-enlace' | 'accion-error' | 'alerta' | null>(null);
  const [validationAttempt, setValidationAttempt] = useState(0);
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const csrfTokenRef = useRef(csrfToken);
  csrfTokenRef.current = csrfToken;
  const solicitarEnlaceRef = useRef<HTMLAnchorElement>(null);
  const accionErrorRef = useRef<HTMLButtonElement>(null);
  const alertaRef = useRef<HTMLParagraphElement>(null);

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

    const destino =
      focoPendiente === 'solicitar-enlace'
        ? solicitarEnlaceRef.current
        : focoPendiente === 'accion-error'
          ? accionErrorRef.current
          : alertaRef.current;
    destino?.focus();
    setFocoPendiente(null);
  }, [focoPendiente, linkState]);

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

  // `valid`: esqueleto mínimo para la Tarea 6, que le añade política, dos toggles
  // independientes, ayuda asociada y el submit real (Tarea 7). Aquí no hay mutación ni botón
  // «Actualizar contraseña» todavía — los campos aceptan tecleo pero no se envían.
  return (
    <MarcoAcceso titulo="Define tu nueva contraseña" subtitulo={SUBTITULO}>
      <form aria-busy={false} noValidate>
        <CampoClave
          id="reset-password"
          name="password"
          label="Nueva contraseña"
          value={password}
          onChange={setPassword}
          autoComplete="new-password"
        />
        <CampoClave
          id="reset-password-confirm"
          name="confirmPassword"
          label="Confirmar contraseña"
          value={confirmPassword}
          onChange={setConfirmPassword}
          autoComplete="new-password"
        />
        <div className="aia-auth__acciones">
          <a href="/login">Volver al inicio de sesión</a>
        </div>
      </form>
    </MarcoAcceso>
  );
}
