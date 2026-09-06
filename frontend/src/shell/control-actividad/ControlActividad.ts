import { ApiError, pedir } from '../../lib/api/cliente';
import { EsquemaRespuestaLogout, EsquemaRespuestaTouch } from '../../lib/api/esquemas/actividad';

const RUTA_TOUCH = '/session/touch';
const RUTA_LOGOUT = '/api/auth/logout';

/** Mismo conjunto que usaba `SessionTimeoutManager.js` para los hosts legacy. */
const EVENTOS_ACTIVIDAD = ['mousemove', 'keydown', 'mousedown', 'click', 'scroll', 'touchstart'] as const;

/** Motivos de cierre — cada uno rastreable a quién decidió cerrar la sesión, para logging/debug. */
export type RazonCierreSesion =
  | 'usuario'
  | 'timeout'
  | 'inactive'
  | 'stale_session'
  | 'session_unverified'
  | 'membership_loss'
  | 'red';

const RAZONES_DESDE_SERVIDOR: ReadonlySet<string> = new Set([
  'timeout',
  'inactive',
  'stale_session',
  'session_unverified',
]);

/**
 * Desenlace del POST a `/api/auth/logout` — nunca bloquea la invalidación local (ver
 * `cerrarSesion()`), pero el llamador necesita distinguir los dos casos:
 * - `confirmado`: el servidor lo dijo explícitamente (200) o ya no había sesión que cerrar (403 —
 *   idempotencia legítima). En ambos casos el servidor "se enteró" o ya no había nada que enterarse.
 * - `red`: la petición ni siquiera llegó a completarse (fallo de red, timeout, 5xx, forma inválida).
 *   El cliente invalida igual, pero no hay confirmación real del servidor — corregido tras hallazgo
 *   de revisión: el código original trataba ambos casos como el mismo "éxito silencioso", pero
 *   "el servidor confirmó que no había sesión" y "no logramos hablar con el servidor" son hechos
 *   distintos que un consumidor (p. ej. el mensaje de `error_recuperable`) puede querer diferenciar.
 */
export type ResultadoCierreSesion = 'confirmado' | 'red';

export interface OpcionesControlActividad {
  ventana?: Window;
  /** Duración del contrato de inactividad — 3600s por defecto (`SessionMiddleware::IDLE_TIMEOUT_SECONDS`). */
  timeoutMs?: number;
  /** Cadencia del heartbeat de fondo (`X-AIA-Idle-Refresh: 0`) — 60s por defecto. */
  intervaloTouchMs?: number;
  /** Ventana de descarte para eventos de actividad repetidos — 1s por defecto. */
  umbralThrottleMs?: number;
  obtenerCsrfToken: () => string;
  /** Único punto de invalidación: se invoca tras cada cierre, exitoso o no. */
  alCerrarSesion: (razon: RazonCierreSesion, resultado: ResultadoCierreSesion) => void;
}

const TIMEOUT_MS_DEFECTO = 3_600_000;
const INTERVALO_TOUCH_MS_DEFECTO = 60_000;
const UMBRAL_THROTTLE_MS_DEFECTO = 1_000;

/**
 * Único dueño de actividad humana, timeout (3600s) y logout del shell React (Tarea 6, T01).
 *
 * Invariantes que el resto del árbol asume:
 * - Un único conjunto de listeners de actividad (`iniciar()` es idempotente: una segunda llamada
 *   sin `detener()` de por medio es un no-op).
 * - Un único temporizador de expiración y un único intervalo de heartbeat — nunca por módulo.
 *   `SesionProvider` es quien la instancia (`useControlActividad`); ningún módulo de S01-S27 debe
 *   crear su propio temporizador de sesión.
 * - `cerrarSesion()` es CSRF-idempotente: llamadas concurrentes comparten la misma promesa y
 *   colapsan en un solo POST. Ni un 403 (CSRF ligado a una sesión que el servidor ya destruyó) ni
 *   un fallo de red bloquean la invalidación local — el POST a `/api/auth/logout` es limpieza del
 *   lado servidor, best-effort; el objetivo de esta llamada es que el cliente jamás se quede
 *   sirviendo una sesión muerta. Sí distingue el desenlace en el valor de retorno
 *   (`ResultadoCierreSesion`): un 403 es confirmación real de que ya no hay sesión, un fallo de
 *   red no lo es — un llamador que necesite avisar "no pudimos confirmar con el servidor" puede
 *   leerlo ahí en vez de que quede conflacionado con el caso idempotente.
 * - El heartbeat de fondo (`tocar()`) nunca reinicia el reloj de inactividad por su cuenta — manda
 *   `X-AIA-Idle-Refresh: 0` a propósito (`SessionMiddleware::shouldRefreshTimeout()`), porque solo
 *   detecta expiración temprana (401), no la produce. Quien sí reinicia el reloj es la actividad
 *   humana real (`registrarActividad()`).
 */
export class ControlActividad {
  private readonly ventana: Window;
  private readonly timeoutMs: number;
  private readonly intervaloTouchMs: number;
  private readonly umbralThrottleMs: number;
  private readonly obtenerCsrfToken: () => string;
  private readonly alCerrarSesion: (razon: RazonCierreSesion, resultado: ResultadoCierreSesion) => void;

  private activo = false;
  private cerrandoPromesa: Promise<ResultadoCierreSesion> | null = null;
  private ultimoRegistroActividad = 0;
  private temporizadorExpiracion: ReturnType<typeof setTimeout> | null = null;
  private temporizadorTouch: ReturnType<typeof setInterval> | null = null;

  constructor(opciones: OpcionesControlActividad) {
    this.ventana = opciones.ventana ?? window;
    this.timeoutMs = opciones.timeoutMs ?? TIMEOUT_MS_DEFECTO;
    this.intervaloTouchMs = opciones.intervaloTouchMs ?? INTERVALO_TOUCH_MS_DEFECTO;
    this.umbralThrottleMs = opciones.umbralThrottleMs ?? UMBRAL_THROTTLE_MS_DEFECTO;
    this.obtenerCsrfToken = opciones.obtenerCsrfToken;
    this.alCerrarSesion = opciones.alCerrarSesion;
  }

  /** Idempotente: una segunda llamada mientras ya está activo no registra un segundo juego de listeners. */
  iniciar(): void {
    if (this.activo) return;
    this.activo = true;

    for (const evento of EVENTOS_ACTIVIDAD) {
      this.ventana.addEventListener(evento, this.manejarActividad, { passive: true });
    }

    this.agendarExpiracion();
    this.temporizadorTouch = setInterval(() => void this.tocar(), this.intervaloTouchMs);
  }

  /** Retira los listeners y limpia ambos temporizadores. Idempotente. */
  detener(): void {
    if (!this.activo) return;
    this.activo = false;

    for (const evento of EVENTOS_ACTIVIDAD) {
      this.ventana.removeEventListener(evento, this.manejarActividad);
    }

    if (this.temporizadorExpiracion) clearTimeout(this.temporizadorExpiracion);
    if (this.temporizadorTouch) clearInterval(this.temporizadorTouch);
    this.temporizadorExpiracion = null;
    this.temporizadorTouch = null;
  }

  /**
   * Cierra sesión de forma CSRF-idempotente: una llamada en curso comparte la MISMA promesa con
   * cualquier otra que llegue mientras tanto (double-click, timer + 401 casi simultáneos, etc.) —
   * así todas ven el desenlace real, no una suposición. El POST a `/api/auth/logout` es
   * best-effort: cualquier desenlace (200, 403 de sesión ya muerta, fallo de red) termina
   * invalidando localmente vía `alCerrarSesion`, pero el `ResultadoCierreSesion` que se devuelve
   * distingue "el servidor confirmó" (200 o 403 idempotente) de "no logramos hablar con el
   * servidor" (`red`) — ver el docblock de `ResultadoCierreSesion`.
   */
  async cerrarSesion(razon: RazonCierreSesion): Promise<ResultadoCierreSesion> {
    if (this.cerrandoPromesa) return this.cerrandoPromesa;

    const promesa = this.ejecutarCierre(razon);
    this.cerrandoPromesa = promesa;

    try {
      return await promesa;
    } finally {
      this.cerrandoPromesa = null;
    }
  }

  private async ejecutarCierre(razon: RazonCierreSesion): Promise<ResultadoCierreSesion> {
    let resultado: ResultadoCierreSesion = 'confirmado';

    try {
      await pedir(RUTA_LOGOUT, EsquemaRespuestaLogout, {
        method: 'POST',
        headers: { 'X-CSRF-Token': this.obtenerCsrfToken() },
      });
    } catch (causa) {
      if (causa instanceof ApiError && causa.tipo === 'http' && causa.status === 403) {
        // "Ya no hay sesión que cerrar" (CSRF atado a una sesión que el servidor ya destruyó, p.
        // ej. por su propio timeout) — logout es idempotente: llegar a ese estado SÍ es una
        // confirmación real, no una suposición.
        resultado = 'confirmado';
      } else {
        // Cualquier otro fallo (red, 5xx, forma inválida) no bloquea la invalidación local — el
        // objetivo de esta llamada es limpiar el cliente igual — pero no podemos afirmar que el
        // servidor se enteró: se reporta como `red`, no como éxito silencioso.
        resultado = 'red';
      }
    } finally {
      this.detener();
      this.alCerrarSesion(razon, resultado);
    }

    return resultado;
  }

  private readonly manejarActividad = (): void => {
    const ahora = Date.now();
    if (ahora - this.ultimoRegistroActividad < this.umbralThrottleMs) return;
    this.ultimoRegistroActividad = ahora;
    this.agendarExpiracion();
  };

  private agendarExpiracion(): void {
    if (this.temporizadorExpiracion) clearTimeout(this.temporizadorExpiracion);
    this.temporizadorExpiracion = setTimeout(() => void this.cerrarSesion('timeout'), this.timeoutMs);
  }

  /**
   * Heartbeat de fondo: detecta una sesión ya inválida en el servidor (401) sin esperar a que el
   * usuario interactúe. A propósito NO reagenda el temporizador de expiración local — ver el
   * docblock de la clase.
   */
  private async tocar(): Promise<void> {
    try {
      await pedir(RUTA_TOUCH, EsquemaRespuestaTouch, {
        method: 'POST',
        headers: { 'X-AIA-Idle-Refresh': '0' },
      });
    } catch (causa) {
      if (causa instanceof ApiError && causa.tipo === 'http' && causa.status === 401) {
        void this.cerrarSesion(this.razonDesde(causa.razon));
      }
      // Cualquier otro fallo (red, 5xx) no cierra sesión por su cuenta: el temporizador de
      // expiración local sigue siendo la única autoridad sobre el timeout de inactividad.
    }
  }

  private razonDesde(razonServidor: string | null): RazonCierreSesion {
    if (razonServidor && RAZONES_DESDE_SERVIDOR.has(razonServidor)) {
      return razonServidor as RazonCierreSesion;
    }

    return 'red';
  }
}
