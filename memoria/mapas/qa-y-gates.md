---
tipo: mapa
estado: vigente
fecha: 2026-08-02
areas: [qa]
fuente: sesion
resumen: "Qué suites existen, cuáles están rojas de fábrica y cómo no perder una tarde diagnosticando ruido"
---
# Mapa · QA y gates

## Qué manda

[[AGENTS]] §Verificación — primero la prueba enfocada, después se amplía según el riesgo ·
`docs/qa/workflows.md` (no viaja en git) para flujos de extremo a extremo.

## Las suites

- **PHP**: no hay PHPUnit. Los `tests/test_*.php` son scripts autoejecutables; se corren uno a uno
  con `docker compose exec app php tests/<archivo>.php`.
- **Design system**: `npm run test:design-system:static`, `:phpstan` y `:runtime`.
- **`tests/browser/`**: Playwright, orientado al laboratorio y al design system.
- **`e2e/`**: suite **separada**, con su propia configuración y fixtures, para humo, admin y
  flujos. No confundir con la anterior al decidir dónde va una prueba nueva.
- **Análisis estático**: `docker compose exec app vendor/bin/phpstan analyse src admin/src
  --memory-limit=1G`. No hay `phpstan.neon` en la raíz.
- **Frontend**: `npm run check:frontend` (Biome, solo `public/js`, `public/css`,
  `admin/public/css` — no analiza PHP).

## Antes de culpar a tu cambio

Hay rojos que ya estaban ahí. Lee primero:

- [[branch-preexisting-red-gates]] — rojos tolerados de los gates del design system y cómo
  validarlos en un worktree.
- [[suite-php-rojos-preexistentes]] — cuántos `tests/test_*.php` fallan solos, y las dos trampas
  al medirlos en macOS (`timeout` no existe, y `grep "^FAIL"` miente).
- [[visual-baselines-estado-real]] — las baselines del lab están rojas y algunas ni se comparan.
- [[lab-desktop-layout-suite]] — corre fuera del carril `runtime`, así que no figura donde
  esperarías.

## Trampas al añadir o correr pruebas

- [[tests-browser-allowlist]] — un test nuevo en `tests/browser/` no se commitea si no lo
  registras en `.gitignore`.
- [[manifiesto-ds-exige-golden]] — un manifiesto exige un golden real con `sha256` que case.
- [[pdc-e2e-sandbox]] — los e2e del PDC van contra el proyecto 990100.
- [[no-enriquecer-daporto-para-medir]] — no toques el proyecto 73 para tener una línea base ancha.
- [[sesion-cae-en-el-panel]] — caídas de sesión que son del panel, no de la aplicación.
- [[semanal-auto-dispara-mutaciones]] — mutaciones automáticas al cargar la semanal.
- [[captura-playwright-miente]] — capturas de fallo que mienten cuando el spec cierra sesión en
  el `finally`.
- [[gate-visual-tolerancia-enganosa]] — goldens que pasan en verde con un rediseño real.

## Regla de fondo

No se regeneran snapshots ni baselines para forzar un verde, y un cambio visual requiere
aprobación explícita. Si algo pasa a verde sin que sepas por qué, todavía no está verificado.

## Vecinos

[[design-system]] para los gates propios del sistema · [[entorno-y-despliegue]] para levantar lo
que hay que probar.
