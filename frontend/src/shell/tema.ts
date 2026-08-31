export type Tema = 'claro' | 'oscuro';

const CLAVE_TEMA = 'aia-theme';

// Tarea 7 (T01): oscuro es el fallback cuando falta preferencia, es inválida o el storage
// está bloqueado — contrario al fallback claro que caracterizaba el bootstrap original.
const TEMA_FALLBACK: Tema = 'oscuro';

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
