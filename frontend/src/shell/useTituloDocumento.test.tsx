import { render } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { expect, test } from 'vitest';
import { useTituloDocumento } from './useTituloDocumento';

function Sonda({ proyecto }: { proyecto?: string }) {
  const titulo = useTituloDocumento(proyecto);
  return <span data-testid="titulo-devuelto">{titulo}</span>;
}

function renderizar(ruta: string, proyecto?: string) {
  return render(
    <MemoryRouter initialEntries={[ruta]}>
      <Sonda proyecto={proyecto} />
    </MemoryRouter>,
  );
}

test('actualiza document.title según el segmento final de la ruta y el proyecto activo', () => {
  renderizar('/programacion-semanal', 'Da Porto');
  expect(document.title).toBe('Programacion semanal · Da Porto · Last Planner AIA');
});

test('sin proyecto activo, omite ese segmento en vez de dejar un separador huérfano', () => {
  renderizar('/proyectos');
  expect(document.title).toBe('Proyectos · Last Planner AIA');
});

test('la raíz sin segmento cae en "Inicio"', () => {
  renderizar('/', 'Da Porto');
  expect(document.title).toBe('Inicio · Da Porto · Last Planner AIA');
});

test('el valor devuelto por el hook es el mismo texto que queda en document.title', () => {
  const { getByTestId } = renderizar('/indicadores', 'Da Porto');
  expect(getByTestId('titulo-devuelto').textContent).toBe(document.title);
});
