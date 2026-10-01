---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-10-01
areas: [qa, design-system]
fuente: "frontend/vite.config.ts (outDir ../public/app), git log -- public/app/assets; sprint del arreglo del SOS, rama fix/s05-sos-semana-y-alerta, 2026-10-01"
resumen: "public/app (el bundle de React) se versiona en git: cambiar frontend/src sin correr npm run build deja el código fuente arreglado y la pantalla servida vieja, y vitest, tsc y las revisiones de código no lo ven"
---
# El bundle de React versionado no se recompila solo

`frontend/` compila a `../public/app` (`vite.config.ts`), y **`public/app` está versionado en git**:
lo que se despliega es ese bundle, no `frontend/src`. Un cambio en `frontend/src` sin
`npm run build` deja el fuente arreglado y la pantalla servida igual que antes.

Ninguna verificación de fuente lo detecta. `vitest` y `tsc` corren sobre `frontend/src`, y las
revisiones de código leen el diff del fuente. En el sprint del SOS (2026-10-01) pasaron 1051
pruebas y seis revisiones con el bundle viejo (`index-Cd39cDxx.js`, del 2026-09-29), y solo lo
destapó una prueba de navegador contra el servidor real: el cliente seguía sin mandar la semana
y el servidor respondía 422. En producción, el SOS habría seguido fallando con el arreglo
«integrado».

**Cómo no caer:** todo cambio en `frontend/src` termina con `npm run build` en `frontend/` y el
commit de `public/app` (el `index.html` apunta al asset nuevo y el viejo se borra). Para comprobar
que el bundle es el del fuente, busca en `public/app/assets/*.js` una cadena que el cambio
introdujo. Esa comprobación todavía no es un gate.

Vecinos: [[qa-y-gates]] · [[programa-general-funciones-y-flujos]] ·
[[el-gate-de-laboratorio-tambien-revisa-pantallas-de-producto]].
