/**
 * Avisos de acceso (Tarea 8, S01): traduce la razón de sesión que trae el bootstrap
 * (`SessionMiddleware::finishUnauthorized()`) y los parámetros de query legacy/nuevos
 * de `/login` a un único aviso, consumible una vez. Vocabulario y prioridad fijos —
 * ver `task-8-brief.md`, no se reescriben.
 *
 * Prioridad: (1) razón de servidor — `missing_session` no produce aviso, `timeout` /
 * `inactive` / `stale_session|session_unverified` sí; (2) `reset=1`; (3) los legacy
 * `timeout=1` / `inactive=1`. La razón de servidor se resuelve una sola vez por
 * respuesta de `/api/session` (el middleware destruye la sesión al reportarla, así
 * que no reaparece en un recargo); los parámetros de query sí sobreviven un recargo
 * o un "atrás" del navegador — de ahí `limpiarParametrosAviso`.
 */

export type TipoAvisoAcceso = 'sesion_expirada' | 'cuenta_inactiva' | 'sesion_invalida' | 'clave_restablecida';

export interface AvisoAcceso {
  tipo: TipoAvisoAcceso;
  mensaje: string;
}

const AVISO_SESION_EXPIRADA: AvisoAcceso = {
  tipo: 'sesion_expirada',
  mensaje: 'Su sesión expiró por inactividad. Ingresa de nuevo.',
};

const AVISO_CUENTA_INACTIVA: AvisoAcceso = {
  tipo: 'cuenta_inactiva',
  mensaje: 'Tu cuenta está inactiva. Contacta al administrador.',
};

const AVISO_SESION_INVALIDA: AvisoAcceso = {
  tipo: 'sesion_invalida',
  mensaje: 'Tu sesión ya no es válida. Ingresa de nuevo.',
};

const AVISO_CLAVE_RESTABLECIDA: AvisoAcceso = {
  tipo: 'clave_restablecida',
  mensaje: 'Tu contraseña fue restablecida correctamente. Ya puedes iniciar sesión.',
};

/** Únicos parámetros que un aviso consume — nunca se toca ningún otro. */
const PARAMETROS_AVISO = ['timeout', 'inactive', 'reset'] as const;

export function resolverAvisoAcceso(reason: string | null, search: string): AvisoAcceso | null {
  switch (reason) {
    case 'timeout':
      return AVISO_SESION_EXPIRADA;
    case 'inactive':
      return AVISO_CUENTA_INACTIVA;
    case 'stale_session':
    case 'session_unverified':
      return AVISO_SESION_INVALIDA;
  }

  // `missing_session` no tiene aviso propio — es el estado normal de "nunca hubo sesión" y
  // también el que sigue a un cambio de clave exitoso (el usuario queda deslogueado). Por eso
  // cae aquí en vez de devolver `null` de inmediato: el `reset=1` de la URL sigue vivo y es
  // justo el escenario real que este aviso existe para cubrir.
  const parametros = new URLSearchParams(search);

  if (parametros.get('reset') === '1') {
    return AVISO_CLAVE_RESTABLECIDA;
  }
  if (parametros.get('timeout') === '1') {
    return AVISO_SESION_EXPIRADA;
  }
  if (parametros.get('inactive') === '1') {
    return AVISO_CUENTA_INACTIVA;
  }

  return null;
}

/**
 * Quita solo `timeout`/`inactive`/`reset` de `url` — cualquier otro parámetro se
 * conserva intacto. Acepta URLs absolutas o relativas (`/login?reset=1`); para
 * relativas, devuelve también una ruta relativa (nunca inventa un origen).
 */
export function limpiarParametrosAviso(url: string): string {
  const esAbsoluta = /^[a-z][a-z0-9+.-]*:\/\//i.test(url);
  const resuelta = new URL(url, esAbsoluta ? undefined : 'http://localhost.invalid');

  for (const nombre of PARAMETROS_AVISO) {
    resuelta.searchParams.delete(nombre);
  }

  if (esAbsoluta) {
    return resuelta.toString();
  }

  return `${resuelta.pathname}${resuelta.search}${resuelta.hash}`;
}
