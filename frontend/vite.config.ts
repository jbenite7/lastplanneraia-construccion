/// <reference types="vitest/config" />

import { defineConfig, type Plugin } from 'vite';
import react from '@vitejs/plugin-react';

function normalizarLineasVaciasEnChunks(): Plugin {
  return {
    name: 'normalizar-lineas-vacias-en-chunks',
    enforce: 'post',
    renderChunk(codigo) {
      const codigoNormalizado = codigo.replace(/^[\t ]+$/gm, '');

      return codigoNormalizado === codigo ? null : { code: codigoNormalizado, map: null };
    },
  };
}

export default defineConfig({
  plugins: [react(), normalizarLineasVaciasEnChunks()],
  base: '/app/',
  build: {
    outDir: '../public/app',
    emptyOutDir: true,
  },
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./src/test-setup.ts'],
  },
});
