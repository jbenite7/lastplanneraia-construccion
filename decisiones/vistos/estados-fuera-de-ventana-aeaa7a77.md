---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-19
areas: [proceso]
tags: [pendiente]
fuente: decisiones/vistos/estados-fuera-de-ventana-aeaa7a77.md
resumen: Visto — frente estados-fuera-de-ventana
---

# Visto — frente estados-fuera-de-ventana

- **Fecha:** 2026-08-19 (emitido por la coordinadora vigente)
- **Sha con visto:** `aeaa7a77` (HEAD de `claude/bold-neumann-485f23`, origin/main integrado, árbol limpio)
- **Verificación re-ejecutada por la coordinadora, con ventana de contenedor propia y paso 0
  comprobado** (el contenedor montaba el worktree bold-neumann-485f23 durante la corrida):
  - `run-php-tests.php --nivel=puro` → RC=0, 24/24 sueltos + PHPUnit 2 clases, OK (45 tests, 68 assertions).
  - `node --test ds-f1a-escala-estado.test.mjs` → 9 pass / 0 fail.
  - `npm run test:wiki` → sin hallazgos, 159 páginas (modo estricto).
  - Diff de producción acotado a los dos calculadores (`src/Legacy/estado_programa_general.php`,
    `src/Core/Lps/LpsService.php`); cero escrituras SQL en todo el diff.
- **Desviaciones del plan:** aceptadas las dos (Task 6 adelantada con autorización; regla de
  LpsService reubicada porque el primer `return 'Actividad Futura'` la dejaba inalcanzable — cazado
  por la prueba de paridad de la Task 1).
- **Autoriza:** publicar `aeaa7a77` con `bash scripts/publicar.sh` desde ese worktree, aprovechando
  que el contenedor ya lo monta; devolverlo a la raíz tras el push.
- **No autoriza:** deploy a producción (decisión goteo-vs-atómico diferida a ese momento); frente B
  (migración de datos, con atención especial a las 24 filas terminadas con fecha futura que un
  recálculo mandaría a Fuera de Ventana perdiendo el dato de terminadas — decisión de Felipe).
