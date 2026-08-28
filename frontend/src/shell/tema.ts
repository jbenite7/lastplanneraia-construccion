export type Tema = 'claro' | 'oscuro';

const CLAVE_TEMA = 'aia-theme';

const temaCss: Record<Tema, 'light' | 'dark'> = {
  claro: 'light',
  oscuro: 'dark',
};

export function leerTemaGuardado(): Tema {
  try {
    return localStorage.getItem(CLAVE_TEMA) === 'dark' ? 'oscuro' : 'claro';
  } catch {
    return 'claro';
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
