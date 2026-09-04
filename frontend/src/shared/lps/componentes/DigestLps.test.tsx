import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { expect, test, vi } from 'vitest';
import { configuracionPorDefecto } from '../dominio/restricciones';
import { DigestLps } from './DigestLps';

/**
 * `DigestLps.tsx` — Tarea 6 lo dejó deliberadamente para esta tarea (ver
 * `.superpowers/sdd/2026-08-30-t02-contexto-lps-react/task-6-report.md`). El dominio
 * (`compilarDigestSemanal`) ya tenía su propia suite (Tarea 3); estas pruebas cubren sólo la capa
 * de presentación: copiar con feedback y respaldo de texto seleccionable (T02-AC-134).
 */

const FILA_BLOQUEADA = {
  consecutivo: 7,
  actividad: 'Vaciado de losa',
  subcontratista: 'Contratista X',
  ruta_critica: true,
  Semanas_Inicio: 0,
  atraso: 3,
};

test('T02-AC-130/133: el digest usa sólo las filas que el consumidor entrega — nunca hace red ni recibe una grilla', () => {
  const copiar = vi.fn().mockResolvedValue(undefined);
  render(<DigestLps filas={[FILA_BLOQUEADA]} config={configuracionPorDefecto()} fecha={new Date('2026-08-31')} copiarAlPortapapeles={copiar} />);

  expect(screen.getByText(/1 responsable\(s\) con bloqueos/)).toBeInTheDocument();
  expect(copiar).not.toHaveBeenCalled();
});

test('sin bloqueos: muestra el mensaje "sin datos" del dominio', () => {
  render(<DigestLps filas={[]} config={configuracionPorDefecto()} fecha={new Date('2026-08-31')} copiarAlPortapapeles={vi.fn()} />);
  expect(screen.getByText('Sin bloqueos críticos esta semana.')).toBeInTheDocument();
});

test('T02-AC-134: copiar con éxito da feedback y no muestra el respaldo manual', async () => {
  const copiar = vi.fn().mockResolvedValue(undefined);
  const usuario = userEvent.setup();
  render(<DigestLps filas={[FILA_BLOQUEADA]} config={configuracionPorDefecto()} fecha={new Date('2026-08-31')} copiarAlPortapapeles={copiar} />);

  await usuario.click(screen.getByRole('button', { name: 'Copiar digest' }));

  expect(copiar).toHaveBeenCalledTimes(1);
  expect(await screen.findByText('Digest copiado al portapapeles.')).toBeInTheDocument();
  expect(screen.queryByRole('textbox')).not.toBeInTheDocument();
});

test('T02-AC-134: un fallo de portapapeles conserva texto seleccionable y feedback recuperable', async () => {
  const copiar = vi.fn().mockRejectedValue(new Error('sin permiso'));
  const usuario = userEvent.setup();
  render(<DigestLps filas={[FILA_BLOQUEADA]} config={configuracionPorDefecto()} fecha={new Date('2026-08-31')} copiarAlPortapapeles={copiar} />);

  await usuario.click(screen.getByRole('button', { name: 'Copiar digest' }));

  expect(await screen.findByText(/No se pudo copiar automáticamente/)).toBeInTheDocument();
  const areaManual = screen.getByRole('textbox') as HTMLTextAreaElement;
  expect(areaManual).toHaveAttribute('readOnly');
  expect(areaManual.value).toContain('REPORTE CONSOLIDADO');
});
