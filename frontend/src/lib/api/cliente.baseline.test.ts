// Caracterización de línea base (Tarea 1, plan T01 — shell-minimo-react).
//
// Objetivo: congelar el hueco medido en el plan ("cliente.ts already owns fetch and
// validates successful JSON, but discards typed error bodies") con un test que hoy
// PASA documentando ese hueco. La Tarea 2 lo cerrará implementando `ApiError` tipado;
// cuando eso pase, este archivo deja de reflejar la realidad y debe reemplazarse por
// la cobertura de la Tarea 2 — no se toca antes.
//
// No hay `EsquemaError` todavía en `esquemas/` (confirmado leyendo el directorio):
// `pedir()` nunca intenta parsear el cuerpo JSON de una respuesta no-2xx, así que un
// código/mensaje estructurado que el backend sí envía (ver `BaseController::fallar()`,
// `BiConstraintWriteController.php:76`, forma `{error:{codigo,mensaje}}`) se pierde.

/// <reference types="vite/client" />

import { z } from 'zod';
import { pedir } from './cliente';

const esquemaDePrueba = z.object({ nombre: z.string() });

afterEach(() => {
  vi.unstubAllGlobals();
});

test('BASELINE: un 422 con cuerpo estructurado {error:{codigo,mensaje}} se descarta hoy — solo llega el status genérico', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response(
      JSON.stringify({ error: { codigo: 'VALIDATION_ERROR', mensaje: 'La semana es inválida' } }),
      { status: 422 },
    ),
  ));

  const error = await pedir('/api/x', esquemaDePrueba).catch((causa: unknown) => causa);

  expect(error).toBeInstanceOf(Error);
  expect((error as Error).message).toBe('/api/x respondió 422');
  // El hueco medido: ni el código ni el mensaje del backend llegan al llamador hoy.
  expect((error as Error).message).not.toContain('VALIDATION_ERROR');
  expect((error as Error).message).not.toContain('La semana es inválida');
  expect((error as Error as { codigo?: unknown }).codigo).toBeUndefined();
});

test('BASELINE: un 401/403/404/409 hoy caen todos al mismo camino genérico — no hay discriminación por status', async () => {
  for (const status of [401, 403, 404, 409]) {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response('{}', { status })));

    const error = await pedir('/api/x', esquemaDePrueba).catch((causa: unknown) => causa);

    expect(error).toBeInstanceOf(Error);
    expect((error as Error).message).toBe(`/api/x respondió ${status}`);

    vi.unstubAllGlobals();
  }
});

test('BASELINE: hoy no existe un esquema Zod de error tipado en esquemas/ (lo introduce la Tarea 2)', () => {
  // `import.meta.glob` es analizado por Vite en build time sin requerir que el
  // archivo exista (a diferencia de un `import` estático a una ruta ausente, que
  // rompería la transformación de este archivo antes de que el test corriera) y
  // sin depender de tipos de Node, que este proyecto frontend no trae.
  // Si la Tarea 2 ya agregó `esquemas/error.ts`, este test debe fallar a propósito
  // para que se reemplace por su cobertura real.
  const modulosEsquema = import.meta.glob('./esquemas/*.ts');
  expect(Object.keys(modulosEsquema)).not.toContain('./esquemas/error.ts');
});
