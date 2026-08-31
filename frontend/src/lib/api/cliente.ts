import type { ZodType } from 'zod';
import { EsquemaCuerpoErrorApi } from './esquemas/error';

/**
 * Cómo falló `pedir()`. Distingue "no hablamos con el servidor" (red/abort) de
 * "hablamos pero la forma no era la esperada" (json/html/esquema), y de "habló
 * y dijo que no" (http, con status).
 */
export type TipoErrorApi =
  | 'red'
  | 'abortado'
  | 'json_invalido'
  | 'contenido_inesperado'
  | 'forma_invalida'
  | 'http';

interface DetallesApiError {
  tipo: TipoErrorApi;
  status?: number | null;
  codigo?: string | null;
  camposInvalidos?: Readonly<Record<string, string>> | null;
  redirect?: string | null;
  correlationId?: string | null;
  /** El `reason` crudo de `SessionMiddleware::finishUnauthorized()` (`timeout`, `inactive`, …). */
  razon?: string | null;
}

/**
 * Error tipado de `pedir()`. `status`/`codigo`/`camposInvalidos`/`redirect`/
 * `correlationId`/`razon` quedan en `null` cuando esa respuesta no los trajo — nunca
 * se inventan valores para rellenar el contrato.
 */
export class ApiError extends Error {
  readonly tipo: TipoErrorApi;
  readonly status: number | null;
  readonly codigo: string | null;
  readonly camposInvalidos: Readonly<Record<string, string>> | null;
  readonly redirect: string | null;
  readonly correlationId: string | null;
  readonly razon: string | null;

  constructor(mensaje: string, detalles: DetallesApiError) {
    super(mensaje);
    this.name = 'ApiError';
    this.tipo = detalles.tipo;
    this.status = detalles.status ?? null;
    this.codigo = detalles.codigo ?? null;
    this.camposInvalidos = detalles.camposInvalidos ?? null;
    this.redirect = detalles.redirect ?? null;
    this.correlationId = detalles.correlationId ?? null;
    this.razon = detalles.razon ?? null;
  }
}

function correlationIdDeCabecera(respuesta: Response): string | null {
  return respuesta.headers.get('X-Correlation-Id') ?? respuesta.headers.get('X-Correlation-ID');
}

/**
 * El único sitio que llama `fetch`.
 *
 * Valida cada respuesta contra su esquema: si el PHP cambia un campo, esto falla
 * aquí y con nombre propio, en vez de romperse tres pantallas más allá. Cualquier
 * salida que no sea "JSON válido contra el esquema pedido" se convierte en un
 * `ApiError` tipado — nunca se inserta un cuerpo crudo (HTML, JSON roto) en la UI.
 */
export async function pedir<T>(
  ruta: string,
  esquema: ZodType<T>,
  opciones: RequestInit = {},
): Promise<T> {
  const encabezados = new Headers({ Accept: 'application/json' });

  if (opciones.body) {
    encabezados.set('Content-Type', 'application/json');
  }

  new Headers(opciones.headers).forEach((valor, nombre) => {
    encabezados.set(nombre, valor);
  });

  let respuesta: Response;
  try {
    respuesta = await fetch(ruta, {
      ...opciones,
      headers: encabezados,
      // Mismo origen: la cookie de sesión del PHP viaja sola.
      credentials: 'same-origin',
    });
  } catch (causa) {
    if (causa instanceof DOMException && causa.name === 'AbortError') {
      throw new ApiError(`${ruta} se canceló`, { tipo: 'abortado', codigo: 'ABORTED' });
    }

    throw new ApiError(`${ruta} no respondió — revisa tu conexión`, {
      tipo: 'red',
      codigo: 'NETWORK_ERROR',
    });
  }

  const correlationId = correlationIdDeCabecera(respuesta);
  const texto = await respuesta.text();
  let crudo: unknown;

  if (texto !== '') {
    try {
      crudo = JSON.parse(texto);
    } catch {
      const tipoContenido = (respuesta.headers.get('Content-Type') ?? '').toLowerCase();
      const pareceHtml = tipoContenido.includes('html') || texto.trimStart().startsWith('<');

      throw new ApiError(
        `${ruta} respondió ${respuesta.status} con ${pareceHtml ? 'HTML' : 'JSON inválido'} en vez del contrato esperado`,
        {
          tipo: pareceHtml ? 'contenido_inesperado' : 'json_invalido',
          status: respuesta.status,
          codigo: pareceHtml ? 'UNEXPECTED_CONTENT_TYPE' : 'INVALID_JSON',
          correlationId,
        },
      );
    }
  }

  if (!respuesta.ok) {
    const cuerpoError = EsquemaCuerpoErrorApi.safeParse(crudo);
    const detalle = cuerpoError.success ? cuerpoError.data : {};
    const codigo = detalle.error?.codigo ?? detalle.error?.code ?? `HTTP_${respuesta.status}`;
    const mensaje = detalle.error?.mensaje ?? detalle.error?.message ?? `${ruta} respondió ${respuesta.status}`;
    const camposInvalidos = detalle.error?.campos ?? detalle.error?.fields ?? null;

    throw new ApiError(mensaje, {
      tipo: 'http',
      status: respuesta.status,
      codigo,
      camposInvalidos,
      redirect: detalle.redirect ?? null,
      correlationId: correlationId ?? detalle.correlationId ?? null,
      razon: detalle.reason ?? null,
    });
  }

  const resultado = esquema.safeParse(crudo);

  if (!resultado.success) {
    const campos = resultado.error.issues
      .map((issue) => `${issue.path.join('.') || '(raíz)'}: ${issue.message}`)
      .join('; ');

    throw new ApiError(`${ruta} devolvió una forma inesperada — ${campos}`, {
      tipo: 'forma_invalida',
      status: respuesta.status,
      codigo: 'INVALID_SHAPE',
      correlationId,
    });
  }

  return resultado.data;
}
