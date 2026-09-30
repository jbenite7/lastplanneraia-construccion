import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { CodigoActividad } from './CodigoActividad';

describe('CodigoActividad', () => {
  it('pinta el código guardado sin marca de calculado', () => {
    render(<CodigoActividad actividad={{ codigo_actividad: 'EST-01', Id: '1.2' }} />);

    const codigo = screen.getByText('EST-01');
    expect(codigo).toHaveClass('cell-code');
    expect(codigo).not.toHaveClass('cell-code--calculado');
    expect(codigo).not.toHaveAttribute('title');
  });

  it('sin código guardado pinta la numeración WBS como calculada, con su explicación', () => {
    render(<CodigoActividad actividad={{ codigo_actividad: '', Id: '1.2.5.1' }} />);

    const codigo = screen.getByText('1.2.5.1');
    expect(codigo).toHaveClass('cell-code', 'cell-code--calculado');
    expect(codigo).toHaveAttribute('title', expect.stringMatching(/calculado.*WBS/i));
  });

  it('sin código ni WBS pinta un guion sin marca de calculado', () => {
    render(<CodigoActividad actividad={{ codigo_actividad: null, Id: '' }} />);

    const codigo = screen.getByText('-');
    expect(codigo).toHaveClass('cell-code');
    expect(codigo).not.toHaveClass('cell-code--calculado');
  });

  it('acepta clases extra (el cajón usa drawer-act-code)', () => {
    render(<CodigoActividad actividad={{ codigo_actividad: 'X', Id: '' }} className="drawer-act-code" />);

    expect(screen.getByText('X')).toHaveClass('cell-code', 'drawer-act-code');
  });
});
