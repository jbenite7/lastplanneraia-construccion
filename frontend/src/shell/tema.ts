export type Tema = 'claro' | 'oscuro';

const CLAVE_TEMA = 'aia-theme';

// Decisión de Felipe (2026-09-03), corrigiendo el fallback de la Tarea 7 (T01): claro es el
// fallback cuando falta preferencia, es inválida o el storage está bloqueado. AGENTS.md fija
// «claro es la cara del producto y el tema de entrada» (spec de temas 2026-08-28, previa a este
// plan); T01 se había desviado de esa regla sin quererlo.
const TEMA_FALLBACK: Tema = 'claro';

const temaCss: Record<Tema, 'light' | 'dark'> = {
  claro: 'light',
  oscuro: 'dark',
};

const temaDesdeValorAlmacenado: Record<'light' | 'dark', Tema> = {
  light: 'claro',
  dark: 'oscuro',
};

function esValorValido(valor: string | null): valor is 'light' | 'dark' {
  return valor === 'light' || valor === 'dark';
}

export function leerTemaGuardado(): Tema {
  try {
    const valor = localStorage.getItem(CLAVE_TEMA);
    return esValorValido(valor) ? temaDesdeValorAlmacenado[valor] : TEMA_FALLBACK;
  } catch {
    return TEMA_FALLBACK;
  }
}

export function aplicarTema(tema: Tema): void {
  const valorCss = temaCss[tema];
  document.documentElement.setAttribute('data-aia-theme', valorCss);
  document.documentElement.classList.toggle('aia-theme-dark', tema === 'oscuro');

  try {
    localStorage.setItem(CLAVE_TEMA, valorCss);
  } catch {
    // El tema actual permanece disponible aunque el navegador bloquee storage.
  }
}
