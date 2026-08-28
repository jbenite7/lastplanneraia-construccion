import { createElement } from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, expect, test, vi } from 'vitest';
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

test('el tema de entrada es claro (D12)', () => {
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

test('un valor corrupto en el almacenamiento no rompe: cae al claro', () => {
  localStorage.setItem('aia-theme', 'fucsia');

  expect(leerTemaGuardado()).toBe('claro');
});

test('tolera almacenamiento bloqueado y conserva el cambio en el documento', () => {
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
    expect(leerTemaGuardado()).toBe('claro');
    aplicarTema('oscuro');
    expect(document.documentElement.getAttribute('data-aia-theme')).toBe('dark');
  } finally {
    vi.stubGlobal('localStorage', almacenamientoOriginal);
  }
});

test('el conmutador anuncia el estado y alterna a oscuro', async () => {
  const usuario = userEvent.setup();

  render(createElement(ConmutadorTema));

  const boton = screen.getByRole('button', { name: /cambiar a tema oscuro/i });
  expect(boton).toHaveAttribute('aria-pressed', 'false');
  expect(boton).toHaveTextContent(/tema: claro/i);

  await usuario.click(boton);

  expect(screen.getByRole('button', { name: /cambiar a tema claro/i })).toHaveAttribute('aria-pressed', 'true');
  expect(screen.getByText(/tema: oscuro/i)).toBeInTheDocument();
  expect(document.documentElement.getAttribute('data-aia-theme')).toBe('dark');
});

test('el conmutador inicia con la preferencia guardada', () => {
  localStorage.setItem('aia-theme', 'dark');

  render(createElement(ConmutadorTema));

  expect(screen.getByRole('button', { name: /cambiar a tema claro/i })).toHaveAttribute('aria-pressed', 'true');
  expect(screen.getByText(/tema: oscuro/i)).toBeInTheDocument();
});
