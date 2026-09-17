import { render } from '@testing-library/react';
import { expect, test } from 'vitest';
import { IconoCorreo, IconoEnviar, IconoFlecha, IconoOjo, IconoOjoTachado, IconoUsuario } from './iconos';

test.each([
  ['usuario', IconoUsuario],
  ['ojo', IconoOjo],
  ['ojo tachado', IconoOjoTachado],
  ['flecha', IconoFlecha],
  ['correo', IconoCorreo],
  ['enviar', IconoEnviar],
])('el ícono %s es decorativo y hereda el color del texto', (_nombre, Icono) => {
  const { container } = render(<Icono />);
  const svg = container.querySelector('svg');
  expect(svg).toHaveAttribute('aria-hidden', 'true');
  expect(svg).toHaveAttribute('focusable', 'false');
  expect(svg).toHaveClass('aia-auth__icono');
  expect(container.innerHTML).not.toMatch(/#[0-9a-f]{3,8}\b/i);
  expect(container.innerHTML).toContain('currentColor');
});
