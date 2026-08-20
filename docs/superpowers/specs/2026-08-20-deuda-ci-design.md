---
capa: fuente
tipo: spec
fecha: 2026-08-20
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-20-deuda-ci-design.md
resumen: Eliminación de la deuda del CI detectada en el contraste con la mejor práctica (2026-08-20) — dos frentes atómicos: seguridad de cadena de suministro (G1+G3+G5) y cache de capas Docker (G2); el resto diferido con dueño.
estado: vigente
---

# Deuda del CI — diseño de eliminación

**Fecha:** 2026-08-20
**Origen:** Contraste del CI con la mejor práctica — Pareto de 8 brechas sobre
`.github/workflows/design-system.yml`, corpus de 50 fuentes curadas (~320 revisadas) en NotebookLM
(cuaderno `0b0117f6-38d5-4b70-affd-8aa638bc0381`), página de lectura
https://claude.ai/code/artifact/e104d651-42ed-4241-8b66-aa1cb512f210 (privada).
**Decisión de alcance (Felipe, 2026-08-20):** Frente 1 = G1+G3+G5 · Frente 2 = G2 ·
G4/G7/G8 diferidas a `TASKS.md` · G6 queda como decisión de proceso pendiente de Felipe.
**Enfoque de verificación (Felipe, 2026-08-20):** opción A — rama + PR de verificación;
el trigger `pull_request` corre el pipeline completo con el YAML nuevo antes de publicar.

## Por qué

Las tres brechas del Frente 1 son las de mejor relación costo/rendimiento del Pareto y la
primera tiene ataque real documentado: en el compromiso de `tj-actions/changed-files`
(CVE-2025-30066, marzo 2025) el atacante movió todas las etiquetas de versión hacia código
malicioso; solo los repositorios anclados por SHA inmutable quedaron a salvo. Nuestro workflow
usa hoy exactamente ese patrón vulnerable (`@v4` mutable) y no tiene vigilancia de actualizaciones.
El corpus (DORA, trunk-based, minimumcd) además manda ejecutar en lotes pequeños: un frente por
vez, verificado y publicado antes del siguiente — coincide con el gate de cierre de frente de
`AGENTS.md`.

## Frente 1 — Cadena de suministro y protecciones triviales (G1 + G3 + G5)

### Cambios

1. **Pinning por SHA (G1).** Los 8 usos de actions en `design-system.yml`
   (`actions/checkout@v4` ×2, `actions/setup-node@v4` ×2, `actions/upload-artifact@v4` ×4)
   pasan al SHA de commit completo con comentario de versión al lado, estilo
   `uses: actions/checkout@<sha40> # v4.x.y`. Cada SHA se resuelve con `gh api` contra el
   repositorio oficial de la action (nunca desde un fork — vector «impostor commit») y se
   verifica que el SHA corresponde al tag anotado.
2. **Dependabot (G1).** `.github/dependabot.yml` nuevo: ecosistema `github-actions`,
   frecuencia semanal. Mantiene los SHAs al día por PR; es lo que vuelve sostenible el pinning.
3. **Timeouts (G3).** `timeout-minutes` en ambos jobs. Valores calibrados contra la duración
   real de corridas recientes (`gh run list --json durationMs` o equivalente) con margen ~2×;
   referencia inicial: ~20 min el job estático, ~60 min el runtime. Si la calibración real
   contradice la referencia, manda la calibración.
4. **actionlint (G5).** Paso nuevo al inicio del job `design-system-static`: descarga del
   binario de una release fijada por versión **y checksum verificado** (no `curl | bash`,
   no tag mutable), y ejecución sobre `.github/workflows/`. Los hallazgos que actionlint
   reporte sobre el YAML actual se corrigen como parte de este frente — para eso se instala.

### Verificación (condición de hecho)

En orden, y cada paso con salida real:

1. Local: actionlint en verde sobre el YAML editado.
2. Local: `npm run test:design-system:static` en verde — el contrato
   (`tests/design-system/visual-ci-contract.test.mjs`) veta palabras dentro del propio
   `design-system.yml` y fija archivos por hash; se comprueba **antes** de empujar.
3. Rama propia + push + PR: el pipeline completo (ambos jobs) corre con el YAML nuevo por el
   trigger `pull_request` y termina **sin rojos nuevos**. Matiz medido el 2026-08-20 al
   escribir el plan: las últimas 7 corridas de `main` ya fallan en un único paso —
   «Check runtime budgets against the baseline», por `initializationMs`, deuda documentada de
   otro frente — así que el verde exigible a este frente es: todos los pasos verdes salvo, a lo
   sumo, ese mismo paso fallando por esa misma causa. Cualquier otro rojo es de este frente y
   lo bloquea. El PR es evidencia, no gate de plataforma: el flujo de publicación a `main`
   no cambia.
4. Cierre de frente estándar: `bash scripts/publicar.sh` desde la rama (pasos 3–7 del gate de
   `AGENTS.md`), y la primera corrida de `main` con el YAML nuevo también en verde.

### Riesgos y mitigaciones

- **SHA mal resuelto o de fork** → resolución vía `gh api` contra el repo oficial + doble
  comprobación tag↔SHA; Dependabot lo re-confirma en su primer barrido.
- **actionlint señala deuda preexistente** (los `run:` largos de provenance y recibos nunca
  pasaron por shellcheck) → se corrige en este frente; si algo excede el alcance, se anota en
  `TASKS.md` con el hallazgo textual.
- **Timeout corto mata una corrida legítima** → margen 2× sobre corridas reales y ajuste al
  primer falso positivo.
- **El contrato del design system rechaza el YAML editado** → suite estática local antes del
  push (paso 2); si el contrato fija por hash algo que este frente edita, el ajuste del
  contrato entra en el mismo commit que el cambio que lo motiva, nunca para «forzar verde».

## Frente 2 — Cache de capas Docker (G2)

Entra **solo cuando el Frente 1 esté publicado** (gate de cierre bloqueante). Su plan de
implementación se escribe en ese momento; este spec fija el objetivo y los límites:

- **Objetivo:** la imagen PHP deja de construirse desde cero dos veces por corrida. Buildx con
  cache `type=gha` o mecanismo equivalente, empezando por el job estático.
- **Corrección medida (2026-08-20, al revisar antes de planear):** el Dockerfile
  (`docker/php/Dockerfile`) **no es multi-etapa** — la referencia a `mode=max` para capas
  intermedias no aplica tal cual. Y el premio real es menor que el de los casos del corpus:
  medido sobre la corrida `32394566769`, «Build the PHP test runtime» tarda **81 s** y «Start
  isolated runtime» **93 s** (incluye el fixture de MySQL y el arranque), sobre ~8 min de
  corrida total. La capa cacheable estable es el `apt-get` + `docker-php-ext-install` inicial;
  `composer install` se invalida en cada commit porque `COPY . /var/www/html` lo precede —
  cachearlo exigiría reordenar el Dockerfile y ajustar el contrato que fija sus líneas por hash.
- **Condición de hecho:** medición antes/después de la duración de ambos jobs sobre corridas
  reales, con mejora demostrada y sin regresión funcional (pipeline completo verde).
- **Límites duros:** no se tocan las líneas de `COMPOSER_INSTALL_FLAGS` fijadas por hash en
  `visual-ci-contract.test.mjs:143-145` salvo ajuste explícito del contrato en el mismo commit;
  el guard anti-despliegue del CI no se ablanda.
- **Referencias del corpus:** builds de 8 min → 1 min con cache caliente (HyperDX); setup
  Docker 39 s → 5 s en pipeline PHP dockerizado (Pascal Landau); `mode=max` como clave en
  multi-stage (Blacksmith, TestDriven).

## Diferido (va a `TASKS.md` en el cierre del Frente 1)

- **G4 · Path filters** — exige lista de exclusiones revisada contra lo que cada gate lee
  (`docs/design-system/` es contractual; `memoria/**` y `.md` de raíz no).
- **G7 · Paralelización del runtime** — medir primero; candidato inicial: PHPStan como job
  paralelo (no necesita la app levantada).
- **G8 · Job summaries** — volcar recibos y presupuestos ya generados a `GITHUB_STEP_SUMMARY`.
- **zizmor** — auditoría de seguridad del YAML complementaria a actionlint; exige tooling extra.
- **G6 · Branch protection / merge queue** — **decisión de proceso de Felipe**, no técnica:
  cambia el flujo de publicación de todas las sesiones (`publicar.sh` → PRs). No se aplica sin
  su visto explícito.

## Qué NO hace este esfuerzo

No introduce camino de despliegue al CI (el guard existente se respeta), no reordena los gates
del runtime, no toca OIDC/environments (no aplican sin deploy), y no modifica el procedimiento
de publicación de `AGENTS.md`.

## Archivos de este esfuerzo

- Este spec: `docs/superpowers/specs/2026-08-20-deuda-ci-design.md`
- Plan del Frente 1: `docs/superpowers/plans/` (lo crea `writing-plans`)
- Contraste de origen: artifact privado + cuaderno NotebookLM citados arriba
- Retrato y barrido: scratchpad de la sesión 2026-08-20 (efímero; lo durable está en este spec)
