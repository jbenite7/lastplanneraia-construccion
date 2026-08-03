---
tipo: trampa
estado: vigente
fecha: 2026-07-29
areas: [qa, lps]
fuente: memoria-claude
origen: lps-aia-browser-qa-pitfalls
resumen: "general_usuarios id 366 no tiene fila en profesionales para project 73: sembrar la bitácora del drawer con test.A falla por FK"
---
**La bitácora del drawer LPS no se puede sembrar con `test.A`**: general_usuarios id 366 no tiene
fila en `profesionales` para project 73 y el INSERT de `/api/lps/comments/add` falla por FK. Para QA
visual, interceptar `**/api/lps/comments?*` con `page.route()` de Playwright y una fixture
`{respuesta:'OK', data:[...]}`.

**Why:** el fallo de FK no es un bug de la superficie, es una limitación del dato sembrado. **How to
apply:** no intentar sembrar la bitácora en vivo con `test.A`; usar la fixture de `page.route()`.

Relacionado: [[semanal-auto-dispara-mutaciones]], [[gate-visual-tolerancia-enganosa]].
