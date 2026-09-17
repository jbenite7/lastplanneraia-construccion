import { TOKEN_RESET_PATTERN } from '../../lib/api/esquemas/auth';

/**
 * Resultado de leer el token de restablecimiento de la query (S03, Tarea 4).
 *
 * `candidate` solo dice que el token tiene forma válida; si sigue vigente lo decide el servidor
 * (`validarEnlaceReset`). `invalid` se resuelve en el cliente y nunca llega a la API.
 */
export type EnlaceReset = { kind: 'candidate'; token: string } | { kind: 'invalid' };

/**
 * Extrae el token del enlace de restablecimiento de forma estricta: exactamente un parámetro
 * `token` (nombre exacto, sin variantes como `token[]` o `Token`) con 64 caracteres hex en
 * minúscula. Ausente, vacío, repetido o mal formado → `invalid`.
 *
 * El token es un secreto al portador: quien lo llame no debe pintarlo, registrarlo ni guardarlo
 * en estado global. Esta función no lo hace y no lo incluye en ningún mensaje.
 */
export function leerTokenReset(search: string): EnlaceReset {
  const valores = new URLSearchParams(search).getAll('token');
  if (valores.length !== 1 || !TOKEN_RESET_PATTERN.test(valores[0])) {
    return { kind: 'invalid' };
  }
  return { kind: 'candidate', token: valores[0] };
}
