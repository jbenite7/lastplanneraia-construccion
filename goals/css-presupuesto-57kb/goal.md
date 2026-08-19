---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-18
areas: [proceso]
fuente: goals/css-presupuesto-57kb/goal.md
resumen: Decisión del usuario D-GAC-5(b), 2026-08-12: investigar de dónde salen los ~57 KB de CSS gzip de más (cssGzipBytes medido 194.553–195.402 contra baseline…
---

<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: css-presupuesto-57kb

## Fase del plan
Plan: -
Fase: -
Sha verificado: ?

## Objetivo
Decisión del usuario D-GAC-5(b), 2026-08-12: **investigar de dónde salen los ~57 KB de CSS gzip
de más** (`cssGzipBytes` medido 194.553–195.402 contra baseline 136.933 del 2026-07-17) **antes**
de crear el baseline 0.3.4. Atribuir la divergencia por módulo/hoja: cuánto es crecimiento real
posterior al 2026-07-17 y cuánto es alta legítima (p. ej. `shell-sidebar.css`), y señalar cualquier
regresión. **No se toca ningún baseline, contrato ni CSS**: este frente solo mide y atribuye.

## Condición de hecho
Un informe en `docs/design-system/runtime-measurements/` (medición instrumentada nueva, con sha)
que descomponga el delta de `cssGzipBytes` por hoja/módulo de forma que las partes sumen el total
(±1 %), distinga alta legítima de crecimiento, y termine en una recomendación accionable para el
baseline 0.3.4. Ojo: el artefacto de CI guarda `assetInventorySha256` (un hash), no la lista — la
atribución exige instrumentar, no basta el log (límite ya medido, ver
`decisiones/gates-al-ci-ejecutor.md` · D-5).

## Archivos declarados
docs/design-system/runtime-measurements/

## Contención
<!-- archivo · commits hoy · quién más lo declara -->

## Cadena de herramientas
<!-- ids del arsenal, máx 8, una línea de porqué cada uno -->

## Publicaciones

- 2026-08-12 · `4bf26500` (informe de atribución, único archivo) publicado por la coordinadora
  (01a82dae) vía merge `df4bf7b3`; `main...origin/main` sin ahead/behind. Efecto vivo verificado:
  `git show origin/main:docs/design-system/runtime-measurements/2026-08-12-atribucion-css-gzip-0.3.3.md`
  devuelve el informe.

## Cierre

Anotado el 2026-08-12 por la coordinadora. Condición de hecho cumplida y revisada: atribución con
error 0 % sobre `c014874c` (la condición pedía ±1 %), suma de control exacta (+17.367 − 8.314 +
48.567 = +57.620), sin regresión detectada, y recomendación accionable para el baseline 0.3.4.
Ningún baseline, contrato ni CSS tocado. Titularidad arbitrada: el frente lo ejecutó la sesión
`ed7ffb0f` (chip, worktree `elegant-jones-d4126a`); la fila `terminada` de `cc2c531d` es residuo de
la apertura y no representa trabajo. La corrección a la premisa del goal (los `sample-N.json` sí
traen `provenance.assets`) queda registrada en el propio informe. La decisión que sigue —crear el
baseline 0.3.4— es del usuario (D-GAC-6) y está fuera de este frente.
