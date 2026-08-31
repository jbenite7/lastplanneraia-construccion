import { render, screen } from '@testing-library/react';
import { afterEach, expect, test } from 'vitest';
import type { Sesion } from '../lib/api/esquemas/sesion';
import { NavegacionLateral } from './NavegacionLateral';

const csrfToken = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

/**
 * Fixtures deliberadamente mínimas: representan lo que el servidor ya autorizó, nunca lo que
 * React debería inferir. Ningún caso aquí depende de `sesion.user.role` para decidir qué se
 * ve — eso es justo lo que esta tarea retira del cliente (spec T01 §10.2).
 */
function sesionConGrupos(
  groups: Sesion['navigation']['groups'],
  overrides: Partial<Sesion> = {},
): Sesion {
  return {
    authenticated: true,
    user: { username: 'test.A', displayName: 'Ana', role: 'A' },
    project: { id: 1, name: 'Da Porto', area: 'Construccion' },
    capabilities: {},
    navigation: { bi: { visible: false, href: null }, groups },
    csrfToken,
    ...overrides,
  };
}

afterEach(() => {
  window.history.pushState({}, '', '/');
});

test('es un landmark de navegación y muestra el proyecto y usuario activos', () => {
  render(<NavegacionLateral sesion={sesionConGrupos([])} />);

  expect(screen.getByRole('navigation', { name: /navegación del proyecto/i })).toBeInTheDocument();
  expect(screen.getByText('Da Porto')).toBeInTheDocument();
  expect(screen.getByText('Ana')).toBeInTheDocument();
});

test('renderiza exactamente los grupos y entradas que trae el manifiesto del servidor, en su orden', () => {
  const sesion = sesionConGrupos([
    {
      id: 'informacion',
      label: 'Información',
      items: [
        { id: 'semanas-proyecto', label: 'Semanas del Proyecto', href: null, icon: 'calendar', action: true },
        { id: 'profesionales', label: 'Profesionales', href: '/profesionales', icon: 'user', action: false },
      ],
    },
    {
      id: 'obra',
      label: 'Obra',
      items: [
        { id: 'programa-general', label: 'Programa General', href: '/programa-general', icon: 'program', action: false },
      ],
    },
  ]);

  render(<NavegacionLateral sesion={sesion} />);

  expect(screen.getByRole('heading', { name: 'Información' })).toBeInTheDocument();
  expect(screen.getByRole('heading', { name: 'Obra' })).toBeInTheDocument();
  expect(screen.getByRole('link', { name: /profesionales/i })).toHaveAttribute('href', '/profesionales');
  expect(screen.getByRole('link', { name: /programa general/i })).toHaveAttribute('href', '/programa-general');
  expect(screen.getByRole('button', { name: /semanas del proyecto/i })).toBeDisabled();

  // No hay grupo "Compras" ni nada relacionado con plan de compras: el manifiesto nunca lo
  // trajo, y React no rellena huecos que el servidor dejó vacíos.
  expect(screen.queryByRole('heading', { name: 'Compras' })).not.toBeInTheDocument();
  expect(screen.queryByRole('link', { name: /plan de compras/i })).not.toBeInTheDocument();
});

test('usa exclusivamente el href que el servidor entrega, incluido el destino BI ya autorizado', () => {
  const sesion = sesionConGrupos([
    {
      id: 'informacion',
      label: 'Información',
      items: [
        { id: 'control-tower', label: 'Control Tower - Informes', href: '/bi/control-tower?project_id=1&semana=8', icon: 'chart', action: false },
      ],
    },
  ]);

  render(<NavegacionLateral sesion={sesion} />);

  expect(screen.getByRole('link', { name: /control tower/i })).toHaveAttribute(
    'href',
    '/bi/control-tower?project_id=1&semana=8',
  );
});

test('un ítem denegado por el servidor simplemente no aparece, sin importar el rol en sesión', () => {
  // El rol es 'G', que hoy hace que profesionales/programa-general estén vetados — pero el
  // componente no debe saberlo: si el manifiesto no trae el ítem, no aparece, punto. Si
  // trajera el ítem igual (porque el servidor decidió mostrarlo), React lo pintaría.
  const sesion = sesionConGrupos(
    [
      {
        id: 'informacion',
        label: 'Información',
        items: [
          { id: 'indicadores', label: 'Indicadores LPS', href: '/indicadores', icon: 'overview', action: false },
        ],
      },
    ],
    { user: { username: 'test.G', displayName: 'Gerencia', role: 'G' } },
  );

  render(<NavegacionLateral sesion={sesion} />);

  expect(screen.getByRole('link', { name: /indicadores/i })).toBeInTheDocument();
  expect(screen.queryByRole('link', { name: /profesionales/i })).not.toBeInTheDocument();
  expect(screen.queryByRole('link', { name: /programa general/i })).not.toBeInTheDocument();
});

test('marca aria-current="page" solo en la entrada cuyo href coincide exactamente con la URL actual', () => {
  window.history.pushState({}, '', '/programa-general');

  const sesion = sesionConGrupos([
    {
      id: 'obra',
      label: 'Obra',
      items: [
        { id: 'programa-general', label: 'Programa General', href: '/programa-general', icon: 'program', action: false },
        { id: 'programacion-intermedia', label: 'Programación Intermedia', href: '/programacion-intermedia', icon: 'tasks', action: false },
      ],
    },
  ]);

  render(<NavegacionLateral sesion={sesion} />);

  expect(screen.getByRole('link', { name: /programa general/i })).toHaveAttribute('aria-current', 'page');
  expect(screen.getByRole('link', { name: /programación intermedia/i })).not.toHaveAttribute('aria-current');
});

test('el código fuente no contiene ninguna matriz de autorización propia', async () => {
  const fuente = await import('./NavegacionLateral.tsx?raw').catch(() => null);
  // Si el bundler de Vitest no soporta el sufijo ?raw en este entorno, el resto de pruebas
  // de esta suite ya ejercitan el contrato de comportamiento; el escaneo de fuente
  // persistido y exhaustivo vive en tests/design-system/shell-navigation-authority.test.mjs.
  if (fuente === null) return;

  const texto = (fuente as { default: string }).default;
  expect(texto).not.toMatch(/ocultasPorRol/);
  expect(texto).not.toMatch(/role\s*===\s*['"]/);
  expect(texto).not.toMatch(/user\?\.role/);
});
