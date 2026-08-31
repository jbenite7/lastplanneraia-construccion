import { z } from 'zod';

export const EsquemaUsuario = z.object({
  username: z.string(),
  displayName: z.string(),
  role: z.string(),
});

export const EsquemaProyecto = z.object({
  id: z.number().int(),
  name: z.string(),
  area: z.string(),
});

export const EsquemaNavegacionBi = z.object({
  visible: z.boolean(),
  href: z.string().startsWith('/').nullable(),
}).refine(
  ({ visible, href }) => visible ? href !== null : href === null,
  { message: 'navigation.bi.href debe coincidir con su visibilidad' },
);

// Entrada del manifiesto de navegación (spec T01 §8.2/§10): el servidor ya resolvió rol,
// membresía, área de proyecto y flags — un ítem no autorizado directamente no viaja. React
// no vuelve a decidir nada: o es un enlace ya autorizado (`href`), o es una acción sin
// destino propio (p. ej. abrir el flyout de semanas), nunca ambas cosas.
export const EsquemaEntradaNavegacion = z.object({
  id: z.string(),
  label: z.string(),
  href: z.string().startsWith('/').nullable(),
  icon: z.string().nullable(),
  action: z.boolean(),
}).refine(
  ({ action, href }) => action ? href === null : href !== null,
  { message: 'una entrada de navegación es una acción sin href o un enlace con href, no ambas' },
);

export const EsquemaGrupoNavegacion = z.object({
  id: z.string(),
  label: z.string(),
  items: z.array(EsquemaEntradaNavegacion),
});

export const EsquemaSesion = z.object({
  authenticated: z.boolean(),
  user: EsquemaUsuario.nullable(),
  project: EsquemaProyecto.nullable(),
  // Las capacidades crecen con el tiempo; se aceptan todas las booleanas que
  // lleguen en vez de fijar la lista aquí y romper cada vez que RbacManager suma una.
  capabilities: z.record(z.string(), z.boolean()),
  navigation: z.object({
    bi: EsquemaNavegacionBi.nullable(),
    groups: z.array(EsquemaGrupoNavegacion),
  }),
  csrfToken: z.string().regex(/^[a-f0-9]{64}$/),
});

export type Sesion = z.infer<typeof EsquemaSesion>;
export type Proyecto = z.infer<typeof EsquemaProyecto>;
