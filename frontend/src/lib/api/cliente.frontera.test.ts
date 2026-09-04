/// <reference types="vite/client" />

// Congela T01-AC-08 ("No hay `fetch` fuera de `frontend/src/lib/api/cliente.ts`")
// del lado de la Tarea 2: un escaneo de fuente, no una convención de código.
// `import.meta.glob` con `?raw` es analizado por Vite en build time, así que
// no necesita `@types/node` (este proyecto frontend no los trae) ni tocar el
// filesystem con `fs`.

const RUTA_PERMITIDA = '/src/lib/api/cliente.ts';

const modulosFuente = import.meta.glob('/src/**/*.{ts,tsx}', {
  query: '?raw',
  import: 'default',
  eager: true,
}) as Record<string, string>;

test('production fetch( solo ocurre en frontend/src/lib/api/cliente.ts', () => {
  const infractores = Object.entries(modulosFuente)
    .filter(([ruta]) => !ruta.endsWith('.test.ts') && !ruta.endsWith('.test.tsx'))
    .filter(([ruta]) => ruta !== RUTA_PERMITIDA)
    .filter(([, contenido]) => /\bfetch\(/.test(contenido))
    .map(([ruta]) => ruta);

  expect(infractores).toEqual([]);
});
