export const MAX_CUADRITOS_VISIBLES = 7;

export function leerRestriccion(valor, umbralRatio) {
  const texto = String(valor == null ? '' : valor).trim();
  if (texto.toUpperCase() === 'N/A') {
    return { relleno: 0, cumple: false, esNoAplica: true };
  }
  const numero = Number.parseFloat(texto.replace('%', ''));
  const relleno = Number.isFinite(numero) ? Math.min(Math.max(numero / 100, 0), 1) : 0;
  const umbral = Number.isFinite(umbralRatio) && umbralRatio > 0 ? umbralRatio : 1;
  return { relleno, cumple: relleno >= umbral, esNoAplica: false };
}

export function repartirCuadritos(lista) {
  const items = Array.isArray(lista) ? lista : [];
  if (items.length <= MAX_CUADRITOS_VISIBLES) {
    return { visibles: items, sobrantes: 0 };
  }
  const tope = MAX_CUADRITOS_VISIBLES - 1;
  return { visibles: items.slice(0, tope), sobrantes: items.length - tope };
}

if (typeof window !== 'undefined') {
  window.AIAReadiness = { leerRestriccion, repartirCuadritos, MAX_CUADRITOS_VISIBLES };
}
