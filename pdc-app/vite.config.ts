/// <reference types="vitest/config" />
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { configDefaults } from 'vitest/config'

// base '/pdc-app/': los assets viven en lps-aia/public/pdc-app/ (nombre distinto
// de la ruta /plan-compras para que Apache no sirva el directorio en vez de rutear).
// Nombres de salida FIJOS (sin hash): el shell PHP los referencia directo y
// cache-busted con ?v=filemtime (no hay manifest que leer en SiteGround).
export default defineConfig({
  plugins: [react()],
  base: '/pdc-app/',
  build: {
    // Directo al destino servido: no hay paso de copia que olvidar. El 2026-07-29,
    // con dos repos, se publicó un bundle cuya fuente no estaba commiteada.
    outDir: '../public/pdc-app',
    // outDir cae fuera de la raíz de Vite, así que hay que autorizar el vaciado.
    // Es seguro porque public/pdc-app/ pasa a ser 100% generado: BUILD.txt, que era
    // el único archivo a mano, se borró al unificar (ver el commit de esta tarea).
    emptyOutDir: true,
    rollupOptions: {
      output: {
        entryFileNames: 'assets/pdc.js',
        chunkFileNames: 'assets/chunk-[name].js',
        // El CSS del entry mantiene el nombre fijo pdc.css (contrato con el shell);
        // cualquier otro asset conserva su nombre con prefijo pdc- para no colisionar.
        assetFileNames: (info) =>
          info.names.some((n) => n.endsWith('.css')) ? 'assets/pdc.css' : 'assets/pdc-[name].[ext]',
      },
    },
  },
  server: {
    proxy: {
      // En dev la API vive en el Docker de lps-aia. Las cookies de sesión llegan
      // igual (las cookies ignoran el puerto). 8091 es el stack del worktree del
      // PDC; el árbol principal publica 8081. PDC_API_PORT lo cambia sin editar.
      '/plan-compras/api': `http://localhost:${process.env.PDC_API_PORT ?? '8091'}`,
    },
  },
  test: {
    environment: 'node',
    exclude: [...configDefaults.exclude, '**/.claude/**'],
  },
})
