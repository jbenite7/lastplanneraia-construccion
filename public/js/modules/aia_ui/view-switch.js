/**
 * Umbral único de cards/grilla (spec 2026-08-07-f2a-piloto-movil-programacion-design.md,
 * decisión E3, y su correlato en el shell: 2026-08-14-shell-menu-flotante-responsive-design.md).
 * Un solo corte para toda la app: por debajo, cards; a partir de él, grilla.
 */
export const UMBRAL_CARDS = 1180;

export function shouldRenderCards(ancho, umbral = UMBRAL_CARDS) {
  const medido = Number(ancho);
  if (!Number.isFinite(medido)) return false;
  return medido < umbral;
}

if (typeof window !== 'undefined') {
  window.AIAViewSwitch = { UMBRAL_CARDS, shouldRenderCards };
}
