/// <reference types="vitest/config" />
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// base '/pdc-app/': los assets viven en lps-aia/public/pdc-app/ (nombre distinto
// de la ruta /plan-compras para que Apache no sirva el directorio en vez de rutear).
// Nombres de salida FIJOS (sin hash): el shell PHP los referencia directo y
// cache-busted con ?v=filemtime (no hay manifest que leer en SiteGround).
export default defineConfig({
  plugins: [react()],
  base: '/pdc-app/',
  build: {
    outDir: 'dist',
    rollupOptions: {
      output: {
        entryFileNames: 'assets/pdc.js',
        chunkFileNames: 'assets/chunk-[name].js',
        assetFileNames: 'assets/pdc.[ext]',
      },
    },
  },
  server: {
    proxy: {
      // En dev la API vive en el Docker de lps-aia. Las cookies de sesión de
      // localhost:8081 llegan igual (las cookies ignoran el puerto).
      '/plan-compras/api': 'http://localhost:8081',
    },
  },
  test: {
    environment: 'node',
  },
})
