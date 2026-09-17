import { z } from 'zod';
import cuerposAuth from '../../../../../tests/fixtures/api-auth-error-bodies.json';
import cuerposLps from '../../../../../tests/fixtures/api-lps-error-bodies.json';
import cuerposReset from '../../../../../tests/fixtures/api-password-reset-error-bodies.json';
import { ApiError, pedir } from '../cliente';
import { EsquemaCuerpoErrorApi } from './error';

/**
 * Contrato entre PHP y Zod para los errores de `/api/auth/*` y de `/api/lps/*`.
 *
 * `tests/fixtures/api-auth-error-bodies.json` y `tests/fixtures/api-lps-error-bodies.json` no se
 * escriben a mano: los capturan `tests/test_api_auth_contract.php` y
 * `tests/test_lps_api_contract.php` del servidor real (`LPS_REGENERAR_CUERPOS=1`) y ese
 * mismo test falla si el servidor deja de emitirlos igual. Aquí cada cuerpo pasa por el
 * esquema y por `pedir()` — la extracción que usa la app, no una copia — para que la forma
 * real del servidor y lo que el cliente sabe leer no puedan volver a divergir en silencio:
 * un solo `null` o un `[]` donde el esquema pide objeto invalida el cuerpo entero y el
 * `ApiError` sale con `HTTP_<status>`, mensaje genérico y sin campos.
 */

type CasoReal = {
  ruta: string;
  status: number;
  /** `render-puro`: el cuerpo salió del render real del controlador en proceso, no de la red. */
  origen?: string;
  cuerpo: {
    success?: boolean;
    ok?: boolean;
    code?: string;
    message?: string;
    fieldErrors?: Record<string, string | string[]>;
    error?: {
      codigo?: string;
      code?: string;
      mensaje?: string;
      message?: string;
      campos?: Record<string, string>;
      fields?: Record<string, string>;
    };
  };
};

const casosAuth = Object.entries(cuerposAuth as unknown as Record<string, CasoReal>);
const casosLps = Object.entries(cuerposLps as unknown as Record<string, CasoReal>);
const casosReset = Object.entries(cuerposReset as unknown as Record<string, CasoReal>);

afterEach(() => {
  vi.unstubAllGlobals();
});

test('el archivo de /api/auth trae los cuatro cuerpos que el contrato vigila', () => {
  expect(casosAuth.map(([nombre]) => nombre).sort()).toEqual([
    '401_invalid_credentials',
    '403_csrf_invalid',
    '422_login_validation_error',
    '422_password_change_validation_error',
  ]);
});

test('el archivo de /api/lps trae los cuatro cuerpos que el contrato vigila', () => {
  expect(casosLps.map(([nombre]) => nombre).sort()).toEqual([
    '404_lps_target_not_found',
    '409_lps_target_stale',
    '409_profile_required',
    '422_validation_failed',
  ]);
});

/**
 * `/api/auth/password/reset*` (S03): los captura `tests/test_api_password_reset_contract.php` del
 * render real del controlador en proceso (`origen: render-puro`, sin DB), con el mismo flag
 * `LPS_REGENERAR_CUERPOS=1`.
 */
test('el archivo de /api/auth/password/reset trae los seis cuerpos que el contrato vigila', () => {
  expect(casosReset.map(([nombre]) => nombre).sort()).toEqual([
    '403_csrf_invalid',
    '410_reset_link_invalid',
    '422_reset_confirm_validation_error',
    '422_reset_password_validation_error',
    '503_reset_unavailable',
    '503_reset_validate_unavailable',
  ]);
});

describe.each(casosAuth)('auth %s', (_nombre, caso) => {
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

describe.each(casosReset)('reset %s', (_nombre, caso) => {
  test('el cuerpo real cumple EsquemaCuerpoErrorApi', () => {
    const resultado = EsquemaCuerpoErrorApi.safeParse(caso.cuerpo);

    expect(resultado.success ? [] : resultado.error.issues).toEqual([]);
    expect(caso.cuerpo.success).toBe(false);
    expect(caso.origen).toBe('render-puro');
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
      expect(Object.keys(error.camposInvalidos ?? {})).toHaveLength(1);
    } else {
      expect(error.camposInvalidos).toBeNull();
    }
  });
});

describe.each(casosLps)('lps %s', (_nombre, caso) => {
  test('el cuerpo real cumple EsquemaCuerpoErrorApi', () => {
    const resultado = EsquemaCuerpoErrorApi.safeParse(caso.cuerpo);

    expect(resultado.success ? [] : resultado.error.issues).toEqual([]);
    expect(caso.cuerpo.ok).toBe(false);
  });

  test('pedir() extrae código, mensaje y campos del servidor', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue(new Response(JSON.stringify(caso.cuerpo), { status: caso.status })),
    );

    const causa = await pedir(caso.ruta, z.unknown()).catch((error: unknown) => error);

    expect(causa).toBeInstanceOf(ApiError);
    const error = causa as ApiError;
    expect(error.status).toBe(caso.status);
    expect(error.codigo).toBe(caso.cuerpo.error?.code);
    expect(error.codigo).not.toMatch(/^HTTP_/);
    expect(error.message).toBe(caso.cuerpo.error?.message);

    if (caso.status === 422) {
      expect(error.camposInvalidos).toEqual(caso.cuerpo.error?.fields);
      expect(Object.keys(error.camposInvalidos ?? {}).length).toBeGreaterThan(0);
    } else {
      expect(error.camposInvalidos).toBeNull();
    }
  });
});
