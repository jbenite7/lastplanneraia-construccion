import { z } from 'zod';
import cuerposReales from '../../../../../tests/fixtures/api-auth-error-bodies.json';
import { ApiError, pedir } from '../cliente';
import { EsquemaCuerpoErrorApi } from './error';

/**
 * Contrato entre PHP y Zod para los errores de `/api/auth/*`.
 *
 * `tests/fixtures/api-auth-error-bodies.json` no se escribe a mano: lo captura
 * `tests/test_api_auth_contract.php` del servidor real (`LPS_REGENERAR_CUERPOS=1`) y ese
 * mismo test falla si el servidor deja de emitirlos igual. Aquí cada cuerpo pasa por el
 * esquema y por `pedir()` — la extracción que usa la app, no una copia — para que la forma
 * real del servidor y lo que el cliente sabe leer no puedan volver a divergir en silencio:
 * un solo `null` o un `[]` donde el esquema pide objeto invalida el cuerpo entero y el
 * `ApiError` sale con `HTTP_<status>`, mensaje genérico y sin campos.
 */

type CasoReal = {
  ruta: string;
  status: number;
  cuerpo: {
    success: boolean;
    code: string;
    message: string;
    fieldErrors?: Record<string, string>;
    error?: { codigo?: string; mensaje?: string; campos?: Record<string, string> };
  };
};

const casos = Object.entries(cuerposReales as unknown as Record<string, CasoReal>);

afterEach(() => {
  vi.unstubAllGlobals();
});

test('el archivo trae los cuatro cuerpos que el contrato vigila', () => {
  expect(casos.map(([nombre]) => nombre).sort()).toEqual([
    '401_invalid_credentials',
    '403_csrf_invalid',
    '422_login_validation_error',
    '422_password_change_validation_error',
  ]);
});

describe.each(casos)('%s', (_nombre, caso) => {
  test('el cuerpo real cumple EsquemaCuerpoErrorApi', () => {
    const resultado = EsquemaCuerpoErrorApi.safeParse(caso.cuerpo);

    expect(resultado.success ? [] : resultado.error.issues).toEqual([]);
    expect(caso.cuerpo.success).toBe(false);
  });

  test('pedir() extrae código, mensaje y campos del servidor', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue(new Response(JSON.stringify(caso.cuerpo), { status: caso.status })),
    );

    const causa = await pedir(caso.ruta, z.unknown(), { method: 'POST' }).catch((error: unknown) => error);

    expect(causa).toBeInstanceOf(ApiError);
    const error = causa as ApiError;
    expect(error.status).toBe(caso.status);
    expect(error.codigo).toBe(caso.cuerpo.code);
    expect(error.message).toBe(caso.cuerpo.message);

    if (caso.status === 422) {
      expect(error.camposInvalidos).toEqual(caso.cuerpo.error?.campos);
      expect(Object.keys(error.camposInvalidos ?? {}).length).toBeGreaterThan(0);
    } else {
      expect(error.camposInvalidos).toBeNull();
    }
  });
});
