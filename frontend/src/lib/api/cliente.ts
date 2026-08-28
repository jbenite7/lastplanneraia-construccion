import type { ZodType } from 'zod';

/**
 * El único sitio que llama `fetch`.
 *
 * Valida cada respuesta contra su esquema: si el PHP cambia un campo, esto falla
 * aquí y con nombre propio, en vez de romperse tres pantallas más allá.
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

  const respuesta = await fetch(ruta, {
    ...opciones,
    headers: encabezados,
    // Mismo origen: la cookie de sesión del PHP viaja sola.
    credentials: 'same-origin',
  });

  if (!respuesta.ok) {
    throw new Error(`${ruta} respondió ${respuesta.status}`);
  }

  const crudo = await respuesta.json();
  const resultado = esquema.safeParse(crudo);

  if (!resultado.success) {
    const campos = resultado.error.issues
      .map((issue) => `${issue.path.join('.') || '(raíz)'}: ${issue.message}`)
      .join('; ');
    throw new Error(`${ruta} devolvió una forma inesperada — ${campos}`);
  }

  return resultado.data;
}
