import type { ReactNode } from 'react';

/**
 * Catálogo de glifos del rail y su componente decorativo. Vive aparte de `BarraLateral` para que
 * `MenuCuenta` (que `BarraLateral` monta en el pie) pueda usarlo sin un import circular.
 */
export const GLIFOS_ICONO: Record<string, ReactNode> = {
  calendar: <><rect height="15" rx="2" width="16" x="4" y="5" /><path d="M8 3v4M16 3v4M4 10h16" /></>,
  chart: <><path d="M5 20V10M12 20V4M19 20v-7" /><path d="M3 20h18" /></>,
  project: <><path d="M4 7h6l2 2h8v10H4z" /><path d="M4 7V5h6l2 2" /></>,
  program: <><path d="M5 5h14v14H5z" /><path d="M8 9h8M8 13h5M8 17h3" /></>,
  overview: <><rect height="6" rx="1" width="6" x="4" y="4" /><rect height="6" rx="1" width="6" x="14" y="4" /><rect height="6" rx="1" width="6" x="4" y="14" /><rect height="6" rx="1" width="6" x="14" y="14" /></>,
  integration: <><circle cx="7" cy="12" r="3" /><circle cx="17" cy="7" r="3" /><circle cx="17" cy="17" r="3" /><path d="m9.5 10.5 5-2M9.5 13.5l5 2" /></>,
  tasks: <><path d="M5 6h14M5 12h14M5 18h14" /><path d="m7 6 .01 0M7 12 .01 0M7 18 .01 0" /></>,
  clipboard: <><path d="M8 5h8a2 2 0 0 1 2 2v13H6V7a2 2 0 0 1 2-2Z" /><path d="M9 5a3 3 0 0 1 6 0M9 11h6M9 15h4" /></>,
  contract: <><path d="M6 3h9l3 3v15H6z" /><path d="M15 3v4h3M9 12h6M9 16h6" /></>,
  user: <><circle cx="12" cy="8" r="3" /><path d="M5 20a7 7 0 0 1 14 0" /></>,
  sync: <><path d="M20 12a8 8 0 1 1-2.34-5.66" /><path d="M20 3v4h-4" /></>,
};

/** Ícono decorativo con el mismo contrato DOM del componente PHP canónico. */
export function IconoBarraLateral({ nombre }: { nombre: string | null | undefined }) {
  const icono = nombre ?? 'overview';
  return (
    <span className={`aia-icon aia-icon--${icono}`} data-aia-component="icon" aria-hidden="true">
      <svg className="aia-icon__glyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        {GLIFOS_ICONO[icono] ?? <circle cx="12" cy="12" r="7" />}
      </svg>
    </span>
  );
}
