/**
 * Íconos del acceso (S01 · paridad visual, 2026-09-16). Sustituyen a los de Font Awesome del
 * login legado (`fa-user`, `fa-eye`, `fa-arrow-right`) sin traer el vendor al shell React.
 * Son decorativos: el nombre accesible lo lleva siempre el control que los contiene.
 */
const base = {
  className: 'aia-auth__icono',
  viewBox: '0 0 24 24',
  'aria-hidden': true,
  focusable: false,
} as const;

export function IconoUsuario() {
  return (
    <svg {...base} fill="currentColor">
      <circle cx="12" cy="8" r="4" />
      <path d="M4 20c0-4.4 3.6-7 8-7s8 2.6 8 7v1H4z" />
    </svg>
  );
}

export function IconoOjo() {
  return (
    <svg {...base} fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" />
      <circle cx="12" cy="12" r="3" fill="currentColor" />
    </svg>
  );
}

export function IconoOjoTachado() {
  return (
    <svg {...base} fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" />
      <circle cx="12" cy="12" r="3" fill="currentColor" />
      <path d="M3 3l18 18" />
    </svg>
  );
}

export function IconoFlecha() {
  return (
    <svg {...base} fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
      <path d="M5 12h14M13 6l6 6-6 6" />
    </svg>
  );
}

export function IconoCorreo() {
  return (
    <svg {...base} fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <rect x="3" y="5" width="18" height="14" rx="2" />
      <path d="M3 7l9 6 9-6" />
    </svg>
  );
}

export function IconoEnviar() {
  return (
    <svg {...base} fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" />
    </svg>
  );
}

/** Botón «Actualizar contraseña» de la pantalla de restablecimiento (S03, Tarea 6). */
export function IconoLlave() {
  return (
    <svg {...base} fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="8" cy="15" r="4" />
      <path d="M11 12l9-9M17 3l3 3M14 6l3 3" />
    </svg>
  );
}
