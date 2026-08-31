/// <reference types="vite/client" />

import { createElement } from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, expect, test, vi } from 'vitest';
import htmlIndice from '../../index.html?raw';
import { ConmutadorTema } from './ConmutadorTema';
import { aplicarTema, leerTemaGuardado } from './tema';

function crearAlmacenamientoMemoria() {
  const valores = new Map<string, string>();

  return {
    clear: () => valores.clear(),
    getItem: (clave: string) => valores.get(clave) ?? null,
    setItem: (clave: string, valor: string) => valores.set(clave, valor),
  };
}

beforeEach(() => {
  vi.stubGlobal('localStorage', crearAlmacenamientoMemoria());
});

afterEach(() => {
  localStorage.clear();
  document.documentElement.removeAttribute('data-aia-theme');
  document.documentElement.classList.remove('aia-theme-dark');
  vi.unstubAllGlobals();
});

test('sin preferencia guardada, el tema de entrada es oscuro (Tarea 7)', () => {
  expect(leerTemaGuardado()).toBe('oscuro');
});

test('con "dark" guardado válido, el tema de entrada es oscuro', () => {
  localStorage.setItem('aia-theme', 'dark');

  expect(leerTemaGuardado()).toBe('oscuro');
});

test('con "light" guardado válido, el tema de entrada es claro', () => {
  localStorage.setItem('aia-theme', 'light');

  expect(leerTemaGuardado()).toBe('claro');
});

test('recuerda lo que el usuario eligió', () => {
  aplicarTema('oscuro');

  expect(leerTemaGuardado()).toBe('oscuro');
});

test('escribe el atributo y la clase que el CSS lee', () => {
  aplicarTema('oscuro');

  expect(document.documentElement.getAttribute('data-aia-theme')).toBe('dark');
  expect(document.documentElement).toHaveClass('aia-theme-dark');

  aplicarTema('claro');

  expect(document.documentElement.getAttribute('data-aia-theme')).toBe('light');
  expect(document.documentElement).not.toHaveClass('aia-theme-dark');
});

test('un valor corrupto en el almacenamiento no rompe: cae al oscuro', () => {
  localStorage.setItem('aia-theme', 'fucsia');

  expect(leerTemaGuardado()).toBe('oscuro');
});

test('tolera almacenamiento bloqueado y cae al oscuro, conservando el cambio en el documento', () => {
  const almacenamientoOriginal = localStorage;
  vi.stubGlobal('localStorage', {
    getItem: () => {
      throw new Error('bloqueado');
    },
    setItem: () => {
      throw new Error('bloqueado');
    },
  });

  try {
    expect(leerTemaGuardado()).toBe('oscuro');
    aplicarTema('claro');
    expect(document.documentElement.getAttribute('data-aia-theme')).toBe('light');
  } finally {
    vi.stubGlobal('localStorage', almacenamientoOriginal);
  }
});

test('el bootstrap de tema deja oscuro como fallback y solo el override "light" lo cambia', () => {
  const bootstrap = htmlIndice.match(/<script>\s*[\s\S]*?<\/script>/)?.[0] ?? '';

  expect(bootstrap).toContain("document.documentElement.setAttribute('data-aia-theme', 'dark');");
  expect(bootstrap.indexOf("document.documentElement.setAttribute('data-aia-theme', 'dark');")).toBeLessThan(
    bootstrap.indexOf('try {'),
  );
  expect(bootstrap).toMatch(/if \(tema === 'light'\)\s*\{[\s\S]*setAttribute\('data-aia-theme', 'light'\)/);
  expect(bootstrap).not.toContain("if (tema === 'dark')");
});

test('el bootstrap de tema corre antes que cualquier hoja de estilos y nunca importa el theme.js legado', () => {
  const bootstrap = htmlIndice.match(/<script>\s*[\s\S]*?<\/script>/)?.[0] ?? '';
  const indiceBootstrap = htmlIndice.indexOf(bootstrap);
  const indicePrimerLink = htmlIndice.indexOf('<link rel="stylesheet"');
  const indiceTokens = htmlIndice.indexOf('/css/tokens.css');
  const indiceSistema = htmlIndice.indexOf('/css/aia-design-system.css');
  const indiceClaro = htmlIndice.indexOf('/css/design-system/theme-claro.css');

  expect(indiceBootstrap).toBeGreaterThanOrEqual(0);
  expect(indicePrimerLink).toBeGreaterThan(-1);
  expect(indiceBootstrap).toBeLessThan(indicePrimerLink);
  expect(indiceBootstrap).toBeLessThan(indiceTokens);
  expect(indiceTokens).toBeLessThan(indiceSistema);
  expect(indiceSistema).toBeLessThan(indiceClaro);

  expect(htmlIndice).not.toContain('aia_ui/theme.js');
  expect(htmlIndice).not.toContain('aia_ui/theme-bootstrap.js');
  expect(htmlIndice).not.toContain('aia_ui/theme-toggle.js');
});

test('sin preferencia guardada, el conmutador inicia en oscuro y alterna a claro', async () => {
  const usuario = userEvent.setup();

  render(createElement(ConmutadorTema));

  const boton = screen.getByRole('button', { name: /cambiar a tema claro/i });
  expect(boton).toHaveAttribute('aria-pressed', 'true');
  expect(boton).toHaveTextContent(/tema: oscuro/i);

  await usuario.click(boton);

  expect(screen.getByRole('button', { name: /cambiar a tema oscuro/i })).toHaveAttribute('aria-pressed', 'false');
  expect(screen.getByText(/tema: claro/i)).toBeInTheDocument();
  expect(document.documentElement.getAttribute('data-aia-theme')).toBe('light');
});

test('el conmutador inicia con la preferencia guardada', () => {
  localStorage.setItem('aia-theme', 'dark');

  render(createElement(ConmutadorTema));

  expect(screen.getByRole('button', { name: /cambiar a tema claro/i })).toHaveAttribute('aria-pressed', 'true');
  expect(screen.getByText(/tema: oscuro/i)).toBeInTheDocument();
});

test('el conmutador con "light" guardado inicia en claro', () => {
  localStorage.setItem('aia-theme', 'light');

  render(createElement(ConmutadorTema));

  expect(screen.getByRole('button', { name: /cambiar a tema oscuro/i })).toHaveAttribute('aria-pressed', 'false');
  expect(screen.getByText(/tema: claro/i)).toBeInTheDocument();
});

test('el conmutador es enfocable por teclado y su nombre accesible sincroniza con el estado, en ambos temas', () => {
  aplicarTema('oscuro');
  const { unmount } = render(createElement(ConmutadorTema));
  let boton = screen.getByRole('button', { name: /cambiar a tema claro/i });
  boton.focus();
  expect(document.activeElement).toBe(boton);
  expect(boton).toHaveAccessibleName(/cambiar a tema claro/i);
  unmount();

  aplicarTema('claro');
  render(createElement(ConmutadorTema));
  boton = screen.getByRole('button', { name: /cambiar a tema oscuro/i });
  boton.focus();
  expect(document.activeElement).toBe(boton);
  expect(boton).toHaveAccessibleName(/cambiar a tema oscuro/i);
});
