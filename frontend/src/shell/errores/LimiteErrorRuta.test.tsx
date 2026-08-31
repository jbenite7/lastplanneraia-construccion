import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { expect, test, vi } from 'vitest';
import { LimiteErrorRuta } from './LimiteErrorRuta';

function Explota(): never {
  throw new Error('boom — detalle interno que nunca debe llegar al DOM');
}

test('un error de render deja un panel accesible en vez de una pantalla en blanco', () => {
  const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});

  render(
    <LimiteErrorRuta>
      <Explota />
    </LimiteErrorRuta>,
  );

  expect(screen.getByRole('alert')).toHaveTextContent(/algo salió mal/i);

  consoleError.mockRestore();
});

test('el mensaje/stack crudo del error nunca se inserta en el DOM', () => {
  const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});

  render(
    <LimiteErrorRuta>
      <Explota />
    </LimiteErrorRuta>,
  );

  expect(document.body.textContent).not.toMatch(/boom/i);

  consoleError.mockRestore();
});

test('"Reintentar" limpia el estado del boundary y vuelve a intentar renderizar los mismos hijos', async () => {
  const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});
  const usuario = userEvent.setup();

  // La inestabilidad vive fuera de React (no en estado de componente): un fallo real que se
  // resuelve entre reintentos —una petición que ya funciona, un dato que llegó— no reinicia el
  // estado del componente que fallaba, así que probarlo con `useState` local sería artificial
  // (el remount que hace el boundary al desmontar el árbol roto ya reinicia ese estado solo).
  let fallar = true;
  function Inestable() {
    if (fallar) throw new Error('fallo transitorio');
    return <p>Recuperado</p>;
  }

  // `alReintentar` es la señal del llamador de que el fallo ya no aplica — aquí, apagar la bandera.
  const alReintentar = vi.fn(() => {
    fallar = false;
  });

  render(
    <LimiteErrorRuta alReintentar={alReintentar}>
      <Inestable />
    </LimiteErrorRuta>,
  );

  expect(screen.getByRole('alert')).toBeInTheDocument();

  await usuario.click(screen.getByRole('button', { name: /reintentar/i }));

  expect(alReintentar).toHaveBeenCalledTimes(1);
  expect(screen.getByText('Recuperado')).toBeInTheDocument();
  expect(screen.queryByRole('alert')).not.toBeInTheDocument();

  consoleError.mockRestore();
});

test('sin error, renderiza los hijos normalmente', () => {
  render(
    <LimiteErrorRuta>
      <p>Contenido normal</p>
    </LimiteErrorRuta>,
  );
  expect(screen.getByText('Contenido normal')).toBeInTheDocument();
  expect(screen.queryByRole('alert')).not.toBeInTheDocument();
});
