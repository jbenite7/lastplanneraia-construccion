import { render, screen } from '@testing-library/react';
import { expect, test } from 'vitest';
import type { Sesion } from '../lib/api/esquemas/sesion';
import { NavegacionLateral } from './NavegacionLateral';

const csrfToken = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

const sesionAdmin: Sesion = {
  authenticated: true,
  user: { username: 'test.A', displayName: 'Ana', role: 'A' },
  project: { id: 1, name: 'Da Porto' },
  capabilities: { canManageWeeks: true, canManageGeneralProgram: true },
  navigation: { bi: { visible: true, href: '/bi/control-tower?project_id=1&semana=8' } },
  csrfToken,
};

const sesionVisualizador: Sesion = {
  ...sesionAdmin,
  user: { username: 'test.V', displayName: 'Víctor', role: 'V' },
  capabilities: { canManageWeeks: false, canManageGeneralProgram: false },
  navigation: { bi: { visible: false, href: null } },
};

test('es un landmark de navegación y muestra el proyecto y usuario activos', () => {
  render(<NavegacionLateral sesion={sesionAdmin} />);

  expect(screen.getByRole('navigation', { name: /navegación del proyecto/i })).toBeInTheDocument();
  expect(screen.getByText('Da Porto')).toBeInTheDocument();
  expect(screen.getByText('Ana')).toBeInTheDocument();
});

test('los módulos aún no migrados enlazan al sitio PHP', () => {
  render(<NavegacionLateral sesion={sesionAdmin} />);

  expect(screen.getByRole('link', { name: /programa general/i })).toHaveAttribute('href', '/programa-general');
  expect(screen.getByRole('link', { name: /plan de compras/i })).toHaveAttribute('href', '/plan-compras');
});

test('usa exclusivamente el destino BI autorizado por el servidor', () => {
  render(<NavegacionLateral sesion={sesionAdmin} />);

  expect(screen.getByRole('link', { name: /control tower/i })).toHaveAttribute(
    'href',
    '/bi/control-tower?project_id=1&semana=8',
  );
});

test('preserva módulos de consulta para V y oculta las entradas legacy restringidas', () => {
  render(<NavegacionLateral sesion={sesionVisualizador} />);

  expect(screen.getByRole('link', { name: /programa general/i })).toBeInTheDocument();
  expect(screen.getByRole('link', { name: /programación intermedia/i })).toBeInTheDocument();
  expect(screen.queryByRole('link', { name: /actualizar cronograma/i })).not.toBeInTheDocument();
  expect(screen.queryByRole('link', { name: /control de cambios/i })).not.toBeInTheDocument();
});

test('oculta el conjunto histórico para roles G sin deducirlo de las capacidades', () => {
  render(<NavegacionLateral sesion={{
    ...sesionAdmin,
    user: { username: 'test.G', displayName: 'Gerencia', role: 'G' },
    capabilities: { canManageGeneralProgram: true },
  }} />);

  expect(screen.queryByRole('link', { name: /programa general/i })).not.toBeInTheDocument();
  expect(screen.queryByRole('link', { name: /profesionales/i })).not.toBeInTheDocument();
  expect(screen.getByRole('link', { name: /programación semanal/i })).toBeInTheDocument();
});
