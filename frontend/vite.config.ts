/// <reference types="vitest/config" />

import { defineConfig, type Plugin } from 'vite';
import react from '@vitejs/plugin-react';

function normalizarLineasVaciasEnChunks(): Plugin {
  return {
    name: 'normalizar-lineas-vacias-en-chunks',
    enforce: 'post',
    generateBundle(_opciones, artefactos) {
      for (const artefacto of Object.values(artefactos)) {
        if (artefacto.type === 'chunk') {
          artefacto.code = artefacto.code.replace(/^[\t ]+$/gm, '');
        }
      }
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
