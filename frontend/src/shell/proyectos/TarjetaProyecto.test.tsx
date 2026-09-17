import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { expect, test, vi } from 'vitest';
import { TarjetaProyecto } from './TarjetaProyecto';
import type { ProyectoDisponible } from '../../lib/api/esquemas/proyectos';

function proyecto(overrides: Partial<ProyectoDisponible> = {}): ProyectoDisponible {
  return {
    id: 1,
    name: 'Da Porto',
    area: 'Construccion',
    active: true,
    role: 'A',
    roleLabel: 'Administrador',
    ...overrides,
  };
}

test('tarjeta presenta toda la metadata sin depender del color', () => {
  const project = proyecto();
  render(
    <TarjetaProyecto project={project} current busy={false} disabled={false} onSelect={vi.fn()} />,
  );

  expect(screen.getByRole('heading', { name: 'Da Porto' })).toBeVisible();
  expect(screen.getByText('Construcción')).toBeVisible();
  expect(screen.getByText('Activo')).toBeVisible();
  expect(screen.getByText(/Rol:/)).toHaveTextContent('Rol: Administrador');
  expect(screen.getByText('Proyecto actual')).toBeVisible();
  expect(screen.getByRole('button', { name: 'Ingresar al proyecto Da Porto' })).toBeEnabled();
});

test('proyecto que no es el actual no anuncia "Proyecto actual"', () => {
  render(
    <TarjetaProyecto project={proyecto()} current={false} busy={false} disabled={false} onSelect={vi.fn()} />,
  );

  expect(screen.queryByText('Proyecto actual')).not.toBeInTheDocument();
});

test('proyecto de pre-construcción usa su propia etiqueta de área', () => {
  render(
    <TarjetaProyecto
      project={proyecto({ area: 'Pre-Construccion' })}
      current={false}
      busy={false}
      disabled={false}
      onSelect={vi.fn()}
    />,
  );

  expect(screen.getByText('Pre-Construcción')).toBeVisible();
});

test('busy cambia el nombre accesible del botón y lo marca ocupado', () => {
  render(
    <TarjetaProyecto project={proyecto()} current={false} busy disabled onSelect={vi.fn()} />,
  );

  const boton = screen.getByRole('button', { name: 'Abriendo Da Porto…' });
  expect(boton).toBeDisabled();
  expect(boton).toHaveAttribute('aria-busy', 'true');
});

test('disabled por otra selección en curso deshabilita el botón sin cambiar su texto', () => {
  render(
    <TarjetaProyecto project={proyecto()} current={false} busy={false} disabled onSelect={vi.fn()} />,
  );

  expect(screen.getByRole('button', { name: 'Ingresar al proyecto Da Porto' })).toBeDisabled();
});

test('el clic invoca onSelect con el proyecto', async () => {
  const onSelect = vi.fn();
  const usuario = userEvent.setup();
  render(
    <TarjetaProyecto project={proyecto()} current={false} busy={false} disabled={false} onSelect={onSelect} />,
  );

  await usuario.click(screen.getByRole('button', { name: 'Ingresar al proyecto Da Porto' }));

  expect(onSelect).toHaveBeenCalledWith(proyecto(), expect.any(HTMLButtonElement));
});

test('onSelect recibe el propio botón para que el llamador pueda devolverle el foco', async () => {
  const onSelect = vi.fn();
  const usuario = userEvent.setup();
  render(
    <TarjetaProyecto project={proyecto()} current={false} busy={false} disabled={false} onSelect={onSelect} />,
  );

  const boton = screen.getByRole('button', { name: 'Ingresar al proyecto Da Porto' });
  await usuario.click(boton);

  expect(onSelect).toHaveBeenCalledWith(proyecto(), boton);
});
