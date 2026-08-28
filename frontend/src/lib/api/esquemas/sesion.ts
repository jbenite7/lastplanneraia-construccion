import { z } from 'zod';

export const EsquemaUsuario = z.object({
  username: z.string(),
  displayName: z.string(),
  role: z.string(),
});

export const EsquemaProyecto = z.object({
  id: z.number().int(),
  name: z.string(),
});

export const EsquemaSesion = z.object({
  authenticated: z.boolean(),
  user: EsquemaUsuario.nullable(),
  project: EsquemaProyecto.nullable(),
  // Las capacidades crecen con el tiempo; se aceptan todas las booleanas que
  // lleguen en vez de fijar la lista aquí y romper cada vez que RbacManager suma una.
  capabilities: z.record(z.string(), z.boolean()),
  csrfToken: z.string().regex(/^[a-f0-9]{64}$/),
});

export type Sesion = z.infer<typeof EsquemaSesion>;
export type Proyecto = z.infer<typeof EsquemaProyecto>;
