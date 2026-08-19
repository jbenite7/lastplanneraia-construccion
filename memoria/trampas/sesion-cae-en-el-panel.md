---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-07-29
areas: [qa]
fuente: memoria-claude
origen: lps-aia-browser-qa-pitfalls
resumen: "La sesión \"muere\" ~60-90s tras login solo en el panel Electron del navegador de Claude, no en el servidor"
---
Durante QA en navegador contra `http://localhost:8081` (2026-07-22): **la sesión "muere" ~60-90s
después del login SOLO en el panel de navegador de Claude (Electron)** — diagnóstico 2026-07-22: el
servidor está exonerado (sesión curl viva 8 min con polling y 3 min idle; los archivos de sesión
"muertos" seguían en `/tmp` con `usuario` intacto; no hay enforcement de sesión única ni reaper). La
cookie `PHPSESSID` (lifetime=0, en memoria) desaparece del jar del panel durante huecos de ~60-90s
entre turnos del agente, con la página aún viva: el siguiente request llega sin sid válido →
`use_strict_mode` emite sid nuevo vacío → 401 `missing_session` → `SessionTimeoutManager.js`
convierte el 401 en `GET /logout?timeout=1` (los hits a /logout son consecuencia, no causa). El
panel es compartido entre sesiones de Claude y a veces retiene cookies >20 min — el fallo es del
entorno del panel, no de la app. Mitigación QA: re-login al inicio de cada turno de navegador, o
validar flujos de sesión con curl/Playwright (nunca mostraron el fallo).

**Why:** interpretar el bounce a `/login` como bug de la superficie auditada cuesta ciclos de
re-diagnóstico. **How to apply:** en QA de navegador, planear verificaciones cortas y agrupadas tras
cada login; no acusar a la app de una caída de sesión sin antes descartar el panel.

Relacionado: [[semanal-auto-dispara-mutaciones]], [[captura-playwright-miente]].
