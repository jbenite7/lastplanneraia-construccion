/// <reference types="vitest/config" />
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { configDefaults } from 'vitest/config'

// base '/ct-app/': los assets viven en lps-aia/public/ct-app/ (nombre distinto
// de la ruta /bi/control-tower para que Apache no sirva el directorio en vez de rutear).
// Nombres de salida FIJOS (sin hash): el shell PHP los referencia directo y
// cache-busted con ?v=filemtime (no hay manifest que leer en SiteGround).
export default defineConfig({
  plugins: [react()],
  base: '/ct-app/',
  build: {
    // Directo al destino servido: no hay paso de copia que olvidar (mismo motivo que pdc-app,
    // ver su vite.config.ts).
    outDir: '../public/ct-app',
    // outDir cae fuera de la raíz de Vite, así que hay que autorizar el vaciado.
    emptyOutDir: true,
    rollupOptions: {
      output: {
        entryFileNames: 'assets/ct.js',
        chunkFileNames: 'assets/chunk-[name].js',
        // El CSS del entry mantiene el nombre fijo ct.css (contrato con el shell);
        // cualquier otro asset conserva su nombre con prefijo ct- para no colisionar.
        assetFileNames: (info) =>
          info.names.some((n) => n.endsWith('.css')) ? 'assets/ct.css' : 'assets/ct-[name].[ext]',
      },
    },
  },
  server: {
    proxy: {
      // En dev la API vive en el Docker de lps-aia. Las cookies de sesión llegan
      // igual (las cookies ignoran el puerto). A diferencia de pdc-app, ct-app no se
      // desarrolla en un worktree aparte, así que el default es el puerto del stack
      // principal (8081) y no el 8091 del worktree del PDC. CT_API_PORT lo cambia sin
      // editar este archivo.
      '/api/bi/control-tower': `http://localhost:${process.env.CT_API_PORT ?? '8081'}`,
    },
  },
  test: {
    environment: 'node',
    exclude: [...configDefaults.exclude, '**/.claude/**'],
    // Registra el afterEach(cleanup) de @testing-library/react a mano — ver el comentario de
    // cabecera de src/test-setup.ts para el porqué (test.globals:false hace que el auto-cleanup
    // de la librería no encuentre un `afterEach` global y nunca se active solo).
    setupFiles: ['./src/test-setup.ts'],
  },
})
