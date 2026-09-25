/**
 * Umbral responsive del rail lateral (Tarea 4, T01). Mismo valor que
 * `UMBRAL_FLOTANTE` de `public/js/modules/aia_ui/shell-drawer.js` (spec 2026-08-14,
 * decisiones D1-D4): 1180px es el único corte de toda la app entre tabla/tarjetas
 * y entre rail persistente/drawer flotante. No se importa ese módulo (vive fuera
 * del árbol de `frontend/`, sin tipos, y acoplaría el build de Vite a `public/js`);
 * se replica su contrato — `tests/design-system/shell-drawer.test.mjs` es la fuente
 * de verdad del valor y de los bordes exactos del umbral.
 */
export const UMBRAL_BARRA_LATERAL_FLOTANTE = 1180;

export type EstadoRiel = 'collapsed' | 'expanded';

/** Misma clave del shell PHP para conservar el estado al cruzar entre ambos hosts. */
export const CLAVE_ESTADO_RIEL = 'aia-sidebar-state';

export function leerEstadoRiel(): EstadoRiel {
  try {
    const guardado = window.localStorage.getItem(CLAVE_ESTADO_RIEL);
    return guardado === 'expanded' || guardado === 'collapsed' ? guardado : 'collapsed';
  } catch {
    return 'collapsed';
  }
}

export function guardarEstadoRiel(estado: EstadoRiel): void {
  try {
    window.localStorage.setItem(CLAVE_ESTADO_RIEL, estado);
  } catch {
    // Sin almacenamiento el riel conserva su comportamiento durante esta visita.
  }
}

export function esBarraLateralFlotante(ancho: number, umbral: number = UMBRAL_BARRA_LATERAL_FLOTANTE): boolean {
  const medido = Number(ancho);
  if (!Number.isFinite(medido)) return false;
  return medido < umbral;
}
