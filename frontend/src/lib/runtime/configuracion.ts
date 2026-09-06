import { z } from 'zod';

/**
 * Contrato del `<script id="aia-runtime-config">` que `SpaHostRenderer` (PHP) inyecta en el
 * HTML servido. El bundle SOLO conoce esta forma — nunca el valor de la ruta oculta de
 * mantenimiento: el servidor decide `action` en tiempo de request y lo entrega ya resuelto.
 *
 * `mode: 'application'` es el caso normal (sin inyección): la SPA arranca su flujo de
 * siempre, con `/api/session` como fuente de verdad. `mode: 'maintenance'` es lo que inyecta
 * `MaintenanceLoginController` en la ruta oculta — nunca se produce fuera de mantenimiento.
 */
export const EsquemaConfiguracionRuntime = z.discriminatedUnion('mode', [
  z.object({ mode: z.literal('application') }),
  z.object({
    mode: z.literal('maintenance'),
    action: z.string().startsWith('/'),
    error: z.boolean(),
    state: z.enum(['anonymous', 'password_change_required']),
    csrfToken: z.string().regex(/^[a-f0-9]{64}$/),
  }),
]);

export type ConfiguracionRuntimeValida = z.infer<typeof EsquemaConfiguracionRuntime>;

/**
 * `invalid` no es parte del contrato del servidor — es lo que el cliente produce cuando el
 * nodo existe pero su contenido no lo cumple (JSON roto o forma inesperada). Se distingue de
 * `application` a propósito: un runtime ausente es el arranque normal de la SPA, mientras que
 * uno presente-pero-corrupto es una señal de que algo se rompió y merece su propia pantalla
 * recuperable, no un arranque silencioso como si nada se hubiera inyectado.
 */
export type ConfiguracionRuntime = ConfiguracionRuntimeValida | { mode: 'invalid' };

const ID_NODO_CONFIGURACION = 'aia-runtime-config';

/**
 * Único punto de lectura del `<script>` inyectado. Nunca parsea el DOM en otro lado: quien
 * necesite la configuración de runtime pasa por aquí.
 */
export function leerConfiguracionRuntime(documento: Document = document): ConfiguracionRuntime {
  const nodo = documento.getElementById(ID_NODO_CONFIGURACION);

  if (!nodo) {
    return { mode: 'application' };
  }

  try {
    const resultado = EsquemaConfiguracionRuntime.safeParse(JSON.parse(nodo.textContent ?? ''));
    return resultado.success ? resultado.data : { mode: 'invalid' as const };
  } catch {
    return { mode: 'invalid' as const };
  }
}
