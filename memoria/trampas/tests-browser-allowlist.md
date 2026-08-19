---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-07-28
areas: [qa]
fuente: memoria-claude
origen: lps-aia-tests-browser-allowlist
resumen: tests/browser/* está gitignorado con allowlist; un test nuevo ahí no se commitea si no lo registras en .gitignore
---
`.gitignore:237` ignora `tests/browser/*` y lo reabre con una allowlist de `!tests/browser/<archivo>.mjs`, una línea por test (**más de 70 entradas, corregido el 2026-08-10** — la cifra de ~40 quedó corta; el mecanismo sigue siendo el mismo).

Un test nuevo en `tests/browser/` **corre con Playwright pero `git add` lo rechaza**. Hay que añadir su `!` a la allowlist en el mismo commit, o el test se pierde y el guard queda sin efecto.

`tests/design-system/*.test.mjs` no está ignorado: ahí no hace falta registrar nada.

Corolario útil: por eso los ficheros de sonda temporales en `tests/browser/` no ensucian `git status` — pero bórralos igual al terminar, y prefiere `node -e` con `require('playwright')` para sondas de un solo uso en vez de crear archivos en el repo.
