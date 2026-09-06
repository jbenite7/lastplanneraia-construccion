import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { expect, test, vi } from 'vitest';
import { AccionesSos } from './AccionesSos';

function props(overrides: Partial<React.ComponentProps<typeof AccionesSos>> = {}) {
  return {
    simulado: true,
    consecutivo: 4102,
    modulo: 'PG' as const,
    actividad: 'Vaciado losa piso 3',
    subcontratista: 'Concretos AIA',
    restriccion: 'Sin acero disponible',
    nivelActual: 1,
    registrarCrisis: vi.fn().mockResolvedValue(undefined),
    copiarAlPortapapeles: vi.fn().mockResolvedValue(undefined),
    abrirCanal: vi.fn(),
    ...overrides,
  };
}

test('en simulación copia el texto y NO llama la mutación de registro (T02-AC-115)', async () => {
  const usuario = userEvent.setup();
  const registrarCrisis = vi.fn().mockResolvedValue(undefined);
  const copiarAlPortapapeles = vi.fn().mockResolvedValue(undefined);
  const abrirCanal = vi.fn();

  render(<AccionesSos {...props({ simulado: true, registrarCrisis, copiarAlPortapapeles, abrirCanal })} />);

  await usuario.click(screen.getByRole('button', { name: /sos whatsapp/i }));

  await waitFor(() => expect(copiarAlPortapapeles).toHaveBeenCalledOnce());
  expect(registrarCrisis).not.toHaveBeenCalled();
  expect(abrirCanal).not.toHaveBeenCalled();
});

test('en modo operativo registra exactamente una vez y sólo entonces abre el canal (T02-AC-116)', async () => {
  const usuario = userEvent.setup();
  const llamadas: string[] = [];
  const registrarCrisis = vi.fn().mockImplementation(async () => {
    llamadas.push('registrar');
  });
  const abrirCanal = vi.fn().mockImplementation(() => llamadas.push('abrirCanal'));
  const copiarAlPortapapeles = vi.fn().mockResolvedValue(undefined);

  render(
    <AccionesSos
      {...props({
        simulado: false,
        contactos: { telefono: '300 123 4567' },
        registrarCrisis,
        abrirCanal,
        copiarAlPortapapeles,
      })}
    />,
  );

  await usuario.click(screen.getByRole('button', { name: /sos whatsapp/i }));

  await waitFor(() => expect(abrirCanal).toHaveBeenCalledOnce());
  expect(registrarCrisis).toHaveBeenCalledOnce();
  expect(registrarCrisis).toHaveBeenCalledWith({ consecutivo: 4102, modulo: 'PG', trigger: 'SOS-DIR' });
  expect(copiarAlPortapapeles).not.toHaveBeenCalled();
  expect(llamadas, 'registrar debe ocurrir ANTES de abrir el canal').toEqual(['registrar', 'abrirCanal']);
  expect(abrirCanal).toHaveBeenCalledWith(expect.stringContaining('https://api.whatsapp.com/send?phone=3001234567'));
});

test('sin contacto asignado, modo operativo registra y cae a copiar en vez de abrir un canal (T02-AC-117)', async () => {
  const usuario = userEvent.setup();
  const registrarCrisis = vi.fn().mockResolvedValue(undefined);
  const abrirCanal = vi.fn();
  const copiarAlPortapapeles = vi.fn().mockResolvedValue(undefined);

  render(
    <AccionesSos
      {...props({
        simulado: false,
        contactos: {},
        registrarCrisis,
        abrirCanal,
        copiarAlPortapapeles,
      })}
    />,
  );

  await usuario.click(screen.getByRole('button', { name: /sos correo/i }));

  await waitFor(() => expect(copiarAlPortapapeles).toHaveBeenCalledOnce());
  expect(registrarCrisis).toHaveBeenCalledOnce();
  expect(abrirCanal).not.toHaveBeenCalled();
  expect(screen.getByRole('status')).toHaveTextContent(/sin correo asignado/i);
});

test('un fallo de portapapeles deja texto seleccionable y no rompe la interacción (T02-AC-119)', async () => {
  const usuario = userEvent.setup();
  const copiarAlPortapapeles = vi.fn().mockRejectedValue(new Error('permiso denegado'));

  render(
    <AccionesSos
      {...props({
        simulado: true,
        copiarAlPortapapeles,
      })}
    />,
  );

  await usuario.click(screen.getByRole('button', { name: /sos whatsapp/i }));

  await waitFor(() => expect(screen.getByRole('status')).toHaveTextContent(/no se pudo copiar/i));
  const areaTexto = await screen.findByLabelText(/texto sos/i);
  expect((areaTexto as HTMLTextAreaElement).value).toContain('ALERTA SOS - CRISIS AIA');
});

test('deshabilitado (nivel terminal sin superior, T02-AC-121) no dispara ninguna acción', async () => {
  const usuario = userEvent.setup();
  const registrarCrisis = vi.fn().mockResolvedValue(undefined);
  const copiarAlPortapapeles = vi.fn().mockResolvedValue(undefined);

  render(
    <AccionesSos
      {...props({
        simulado: false,
        deshabilitado: true,
        registrarCrisis,
        copiarAlPortapapeles,
      })}
    />,
  );

  const boton = screen.getByRole('button', { name: /sos whatsapp/i });
  expect(boton).toBeDisabled();
  await usuario.click(boton);

  expect(registrarCrisis).not.toHaveBeenCalled();
  expect(copiarAlPortapapeles).not.toHaveBeenCalled();
});
