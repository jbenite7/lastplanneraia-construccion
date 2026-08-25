---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-08-20
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-20-deuda-ci-frente-1.md
resumen: Anclar por SHA las actions del workflow, vigilarlas con Dependabot, acotar ambos jobs con timeout y lintar el YAML con actionlint — publicado en main sin rojos…
---

# Deuda del CI · Frente 1 (G1+G3+G5) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Anclar por SHA las actions del workflow, vigilarlas con Dependabot, acotar ambos jobs con timeout y lintar el YAML con actionlint — publicado en `main` sin rojos nuevos.

**Architecture:** Todo el frente toca configuración de CI: un workflow (`design-system.yml`), un archivo nuevo de Dependabot, y la wiki del proyecto. La verificación es en dos anillos: local (actionlint + suite estática, que incluye el contrato que fija este workflow) y remota (PR cuyo trigger `pull_request` corre el pipeline entero con el YAML nuevo antes de publicar).

**Tech Stack:** GitHub Actions, Dependabot, actionlint v1.7.12, gh CLI, suite estática de design system (`npm run test:design-system:static`).

**Spec:** `docs/superpowers/specs/2026-08-20-deuda-ci-design.md`

## Global Constraints

- **Verde exigible = cero rojos nuevos:** las últimas 7 corridas de `main` ya fallan en un único paso — «Check runtime budgets against the baseline», por `initializationMs` (deuda de otro frente). Todo paso distinto de ese debe quedar verde; ese paso puede fallar solo por esa misma causa.
- **`tests/design-system/visual-ci-contract.test.mjs` veta palabras dentro de `design-system.yml`** (guard anti-despliegue) y fija otros archivos por hash. La suite estática se corre en local **antes** de cada push. No se ablanda el guard.
- **No tocar** las líneas de `COMPOSER_INSTALL_FLAGS` (fijadas por hash), el orden de los gates del runtime, ni nada de deploy.
- Los SHAs se resuelven **solo** contra `repos/actions/<action>` oficiales (nunca un fork).
- Trabajo en rama propia `claude/deuda-ci-frente-1`; commits atómicos; publicación únicamente vía `bash scripts/publicar.sh` al cierre.
- Comandos PHP corren dentro de Docker (`docker compose exec app …`); en este frente no hacen falta — la verificación local es Node + actionlint.

---

### Task 1: Rama, contrato del frente y pinning por SHA

**Files:**
- Create: `goals/deuda-ci-frente-1/goal.md`
- Modify: `.github/workflows/design-system.yml` (líneas 52, 57, 85, 86, 194, 233, 268, 293 — los 8 `uses:`)

**Interfaces:**
- Produces: workflow sin ningún `@v4` mutable; los SHAs exactos quedan en el diff para que Task 6 los contraste en el PR.

- [ ] **Step 1: Crear la rama y el contrato del frente**

```bash
git checkout -b claude/deuda-ci-frente-1 origin/main 2>/dev/null || git checkout -b claude/deuda-ci-frente-1
mkdir -p goals/deuda-ci-frente-1
```

Contenido de `goals/deuda-ci-frente-1/goal.md`:

```markdown
# Goal: Deuda del CI — Frente 1 (G1+G3+G5)

Anclar por SHA los 8 usos de actions de `design-system.yml`, añadir Dependabot para
`github-actions`, poner `timeout-minutes` a ambos jobs y lintar el workflow con actionlint.

**Condición de hecho:** actionlint y `npm run test:design-system:static` verdes en local;
corrida de PR sin rojos nuevos (única falla admitida: «Check runtime budgets against the
baseline» por `initializationMs`, deuda preexistente de otro frente); publicado en `main`
vía `scripts/publicar.sh` y primera corrida de `main` igualmente sin rojos nuevos.

**Plan:** `docs/superpowers/plans/2026-08-20-deuda-ci-frente-1.md`
**Spec:** `docs/superpowers/specs/2026-08-20-deuda-ci-design.md`

## Archivos de este goal
- [[goal.md]] · estado en [[memoria/goals/estado]]
```

- [ ] **Step 2: Resolver y verificar los SHAs (no confiar en los precalculados)**

Referencia resuelta el 2026-08-20 (verificarla, no copiarla a ciegas):
`checkout v4 → 11d5960a326750d5838078e36cf38b85af677262`,
`setup-node v4 → 49933ea5288caeca8642d1e84afbd3f7d6820020`,
`upload-artifact v4 → ea165f8d65b6e75b540449e92b4886f43607fa02`.

```bash
for a in checkout setup-node upload-artifact; do
  sha=$(gh api "repos/actions/$a/git/ref/tags/v4" --jq '.object.sha')
  ver=$(gh api "repos/actions/$a/tags" --paginate --jq ".[] | select(.commit.sha==\"$sha\") | .name" | grep -E '^v4\.[0-9]+\.[0-9]+$' | head -1)
  echo "actions/$a  $sha  ${ver:-v4}"
done
```

Expected: tres líneas, cada una con SHA de 40 hex y un tag `v4.x.y` (si un SHA no tiene tag
`v4.x.y` exacto, el comentario será `# v4`). El `gh api` va contra `repos/actions/...`
oficial — eso descarta el vector de fork.

- [ ] **Step 3: Editar los 8 `uses:`**

En `.github/workflows/design-system.yml`, reemplazar cada uso por su forma pineada usando los
valores del Step 2 (ejemplo con los resueltos el 2026-08-20):

```yaml
# líneas 52 y 85
- uses: actions/checkout@11d5960a326750d5838078e36cf38b85af677262 # v4 (v4.x.y del Step 2)
# líneas 57 y 86
- uses: actions/setup-node@49933ea5288caeca8642d1e84afbd3f7d6820020 # v4 (v4.x.y del Step 2)
# líneas 194, 233, 268 y 293
  uses: actions/upload-artifact@ea165f8d65b6e75b540449e92b4886f43607fa02 # v4 (v4.x.y del Step 2)
```

El comentario de versión al lado del SHA es obligatorio: es lo que Dependabot actualiza y lo
que un humano lee.

- [ ] **Step 4: Verificar que no queda ningún tag mutable**

```bash
grep -nE 'uses:.*@v[0-9]' .github/workflows/design-system.yml && echo "QUEDAN TAGS MUTABLES" || echo "OK: todo pineado"
grep -cE 'uses:.*@[0-9a-f]{40} # v4' .github/workflows/design-system.yml
```

Expected: `OK: todo pineado` y conteo `8`.

- [ ] **Step 5: Suite estática local (el contrato del workflow)**

```bash
npm run test:design-system:static
```

Expected: PASS completo. Si el contrato falla por este diff, leer el test señalado en
`tests/design-system/` y ajustar el contrato **en el mismo commit** solo si fija por hash algo
que este frente edita legítimamente; nunca para forzar verde.

- [ ] **Step 6: Commit**

```bash
git add goals/deuda-ci-frente-1/goal.md .github/workflows/design-system.yml
git commit -m "ci(seguridad): las actions quedan ancladas al commit exacto, no a una etiqueta movible

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 2: Timeouts de ambos jobs

**Files:**
- Modify: `.github/workflows/design-system.yml:17-18` (job `design-system-static`) y `:81-83` (job `design-system-runtime`)

**Interfaces:**
- Consumes: el workflow ya pineado de Task 1.
- Produces: ambos jobs con `timeout-minutes`; Task 6 verifica que ninguna corrida legítima muere por ellos.

- [ ] **Step 1: Añadir `timeout-minutes`**

Calibración medida el 2026-08-20: las corridas completas actuales duran ~8 min de punta a
punta (mueren en el paso de presupuestos, ya avanzado el runtime). Margen holgado sobre eso y
sobre una corrida futura totalmente verde:

```yaml
  design-system-static:
    runs-on: ubuntu-latest
    timeout-minutes: 20
```

```yaml
  design-system-runtime:
    runs-on: ubuntu-latest
    timeout-minutes: 60
    needs: design-system-static
```

- [ ] **Step 2: Verificar sintaxis y contrato**

```bash
npm run test:design-system:static
```

Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/design-system.yml
git commit -m "ci(robustez): un job colgado muere en minutos, no en las 6 horas del default

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 3: Dependabot para actions

**Files:**
- Create: `.github/dependabot.yml`

**Interfaces:**
- Consumes: los `uses:` pineados con comentario de versión (Task 1) — es el formato que Dependabot sabe actualizar.
- Produces: vigilancia semanal por PR de los SHAs pineados.

- [ ] **Step 1: Crear el archivo**

Contenido completo de `.github/dependabot.yml`:

```yaml
version: 2
updates:
  - package-ecosystem: "github-actions"
    directory: "/"
    schedule:
      interval: "weekly"
    labels:
      - "dependencias-ci"
```

- [ ] **Step 2: Verificación local posible**

No existe validador local de dependabot.yml; la validación real es que GitHub lo acepte tras
publicar (Task 7 lo comprueba en la pestaña Insights → Dependency graph → Dependabot, o con
`gh api repos/{owner}/{repo}/dependabot/alerts` sin error 403 de config). Aquí solo se
comprueba el YAML:

```bash
node -e "const yaml=require('js-yaml');const fs=require('fs');yaml.load(fs.readFileSync('.github/dependabot.yml','utf8'));console.log('YAML válido')" 2>/dev/null || npx --yes js-yaml .github/dependabot.yml >/dev/null && echo "YAML válido"
```

Expected: `YAML válido`.

- [ ] **Step 3: Commit**

```bash
git add .github/dependabot.yml
git commit -m "ci(seguridad): Dependabot vigila las actions y propone cada actualización por PR

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 4: actionlint — local y como paso del CI

**Files:**
- Modify: `.github/workflows/design-system.yml` (paso nuevo en `design-system-static`, entre el checkout y el fetch de main)

**Interfaces:**
- Consumes: el workflow de Tasks 1–2.
- Produces: paso «Lint the workflow definitions» que Task 6 debe ver verde en el PR.

- [ ] **Step 1: Correr actionlint en local sobre el YAML editado**

```bash
docker run --rm -v "$(pwd):/repo" -w /repo rhysd/actionlint:1.7.12 -color
```

(Si Docker no está arriba: `brew install actionlint && actionlint -color`.)
Expected: sin hallazgos, o una lista de hallazgos concretos.

- [ ] **Step 2: Corregir lo que actionlint señale**

Los `run:` largos de provenance (`:95-116`) y recibos nunca pasaron por shellcheck: es
esperable que aparezcan avisos de quoting. Regla de corrección: **cambiar la forma, nunca el
comportamiento** (añadir comillas, `${var}` → `"${var}"`); cualquier hallazgo que exija
reestructurar un paso se anota textual en `TASKS.md` (Task 5) y no se toca aquí. Iterar Step 1
hasta salida limpia.

- [ ] **Step 3: Añadir el paso al job estático**

En `design-system-static`, inmediatamente después del paso `actions/checkout`:

```yaml
      # Fija versión y checksum a propósito: un linter que se descarga mutable
      # es la misma brecha que vino a cerrar.
      - name: Lint the workflow definitions
        run: |
          set -euo pipefail
          curl -fsSLO https://github.com/rhysd/actionlint/releases/download/v1.7.12/actionlint_1.7.12_linux_amd64.tar.gz
          curl -fsSLO https://github.com/rhysd/actionlint/releases/download/v1.7.12/actionlint_1.7.12_checksums.txt
          grep ' actionlint_1.7.12_linux_amd64.tar.gz$' actionlint_1.7.12_checksums.txt | sha256sum -c -
          tar -xzf actionlint_1.7.12_linux_amd64.tar.gz actionlint
          ./actionlint -color
          rm -f actionlint actionlint_1.7.12_linux_amd64.tar.gz actionlint_1.7.12_checksums.txt
```

- [ ] **Step 4: Re-verificar local (actionlint se lintéa a sí mismo + contrato)**

```bash
docker run --rm -v "$(pwd):/repo" -w /repo rhysd/actionlint:1.7.12 -color
npm run test:design-system:static
```

Expected: ambos PASS. Ojo con el contrato: el paso nuevo no usa ninguna palabra vetada
(des­pliegue/deploy); si el guard protestara igual, leer el veto exacto en
`visual-ci-contract.test.mjs` y renombrar el paso — el guard no se toca.

- [ ] **Step 5: Commit**

```bash
git add .github/workflows/design-system.yml
git commit -m "ci(calidad): el propio workflow pasa por linter en cada corrida, con binario fijado por checksum

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 5: Wiki y diferidos

**Files:**
- Modify: `TASKS.md` (sección de pendientes), `CHANGELOG.md` (entrada nueva bajo Unreleased/fecha)

**Interfaces:**
- Consumes: hallazgos que Task 4 haya diferido.
- Produces: los diferidos del spec con dueño; registro del cambio.

- [ ] **Step 1: Añadir a `TASKS.md`** (respetando el formato existente del archivo; invocar la skill `llm-wiki` si hay duda de plantilla):

```markdown
- [ ] **CI · G4 path filters** — excluir de los triggers lo que ningún gate lee (`memoria/**`, `.md` de raíz); `docs/design-system/` es contractual y NO se excluye. Origen: spec 2026-08-20-deuda-ci-design.
- [ ] **CI · G7 paralelización** — medir duración por paso primero; candidato: PHPStan como job paralelo (no necesita la app levantada). Origen: spec 2026-08-20-deuda-ci-design.
- [ ] **CI · G8 job summaries** — volcar recibos y presupuestos ya generados a `GITHUB_STEP_SUMMARY`. Origen: spec 2026-08-20-deuda-ci-design.
- [ ] **CI · zizmor** — auditoría de seguridad del YAML complementaria a actionlint. Origen: spec 2026-08-20-deuda-ci-design.
- [ ] **DECISIÓN (Felipe) · G6 branch protection / merge queue** — cambia el flujo de publicación de todas las sesiones (`publicar.sh` → PRs). No aplicar sin visto explícito. Origen: spec 2026-08-20-deuda-ci-design.
- [ ] **PROPUESTA (Felipe) · hook `task-completed-verify.sh`** — corre `composer test` en el host, donde composer no existe (repo Docker-only): rojo falso en tareas sin código. Es `~/.claude`: proponer fix, no aplicarlo.
```

(Más los hallazgos de actionlint diferidos en Task 4, textuales, si los hubo.)

- [ ] **Step 2: Añadir a `CHANGELOG.md`** (formato Keep a Changelog del archivo):

```markdown
### Seguridad
- CI: actions ancladas por SHA de commit con Dependabot vigilándolas; timeout en ambos jobs; actionlint como gate del propio workflow (spec 2026-08-20-deuda-ci-design, Frente 1).
```

- [ ] **Step 3: Commit**

```bash
git add TASKS.md CHANGELOG.md
git commit -m "docs(wiki): diferidos del contraste de CI con dueño, y el frente 1 al changelog

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 6: PR de verificación (el YAML corre de verdad)

**Files:**
- Ninguno — verificación remota.

**Interfaces:**
- Consumes: la rama con los 5 commits.
- Produces: URL del PR y corrida verde-sin-rojos-nuevos que Task 7 exige antes de publicar.

- [ ] **Step 1: Push de la rama y PR**

```bash
git push -u origin claude/deuda-ci-frente-1
gh pr create --base main --title "ci: frente 1 de deuda — pinning por SHA, Dependabot, timeouts y actionlint" --body "Verificación del Frente 1 del spec docs/superpowers/specs/2026-08-20-deuda-ci-design.md. Este PR existe como evidencia de que el YAML nuevo corre: la publicación sigue siendo por scripts/publicar.sh.

🤖 Generated with [Claude Code](https://claude.com/claude-code)"
```

- [ ] **Step 2: Esperar la corrida y leer el resultado paso a paso**

```bash
gh run list --workflow=design-system.yml --branch claude/deuda-ci-frente-1 --limit 1
run_id=$(gh run list --workflow=design-system.yml --branch claude/deuda-ci-frente-1 --limit 1 --json databaseId --jq '.[0].databaseId')
gh run watch "$run_id" --exit-status; echo "exit=$?"
gh run view "$run_id" --json jobs --jq '.jobs[] | .name+" => "+.conclusion, (.steps[] | select(.conclusion=="failure") | "  FALLIDO: "+.name)'
```

Expected (condición de hecho del PR): `design-system-static => success` (incluido el paso
«Lint the workflow definitions»), y en `design-system-runtime` **ningún paso fallido distinto
de** «Check runtime budgets against the baseline»; si ese falla, confirmar en su log que la
causa es `initializationMs` (deuda preexistente):

```bash
gh run view "$run_id" --log-failed | grep -i "initializationMs" && echo "misma causa preexistente"
```

- [ ] **Step 3: Si hay rojos nuevos, arreglar y repetir**

Cualquier fallo distinto es de este frente: leer el log (`gh run view "$run_id" --log-failed`),
corregir en la rama, commit atómico, push — el PR re-corre solo. Repetir Step 2 hasta cumplir
la condición. No cerrar el PR en rojo-nuevo.

---

### Task 7: Cierre de frente (gate de AGENTS.md)

**Files:**
- Modify: `goals/deuda-ci-frente-1/goal.md` (anotar cierre), wiki (`memoria/` ingest)

**Interfaces:**
- Consumes: corrida del PR aceptada en Task 6.
- Produces: SHA publicado en `origin/main` idéntico al verificado.

- [ ] **Step 1: Integrar divergencia si la hay**

```bash
git fetch origin && git status -sb
```

Si `origin/main` avanzó: `git merge origin/main` **en esta rama** (nunca en el main del
worktree principal), resolver a la vista, y **re-verificar después de integrar**:

```bash
docker run --rm -v "$(pwd):/repo" -w /repo rhysd/actionlint:1.7.12 -color && npm run test:design-system:static
git rev-parse HEAD
```

Anotar el SHA: es el que se publica.

- [ ] **Step 2: Publicar con el script obligatorio**

```bash
bash scripts/publicar.sh
```

Expected: RC=0 y push de `HEAD:main`. Si deniega (RC=1) por commits entrantes, volver al
Step 1. Si lo rechaza el remoto por carrera, repetir Step 1–2: el rechazo es el guardarraíl
funcionando.

- [ ] **Step 3: Confirmar publicación y corrida de main**

```bash
git fetch origin && git rev-parse origin/main   # debe coincidir con el SHA anotado
run_id=$(gh run list --workflow=design-system.yml --branch main --limit 1 --json databaseId --jq '.[0].databaseId')
gh run watch "$run_id"; gh run view "$run_id" --json jobs --jq '.jobs[] | .name+" => "+.conclusion'
```

Expected: mismo criterio de Task 6 (sin rojos nuevos). Confirmar además que Dependabot quedó
activo: `gh api repos/{owner}/{repo}/contents/.github/dependabot.yml --jq .name` devuelve el
archivo en main.

- [ ] **Step 4: Anotar el cierre**

- `goals/deuda-ci-frente-1/goal.md`: añadir línea «Cerrado 2026-MM-DD, publicado en <sha>».
- Ingest a `memoria/` (skill de la wiki; línea en `memoria/log.md`).
- El PR de verificación queda cerrado automáticamente por el push a main (verificar con
  `gh pr view --json state`); si quedó abierto, cerrarlo con comentario apuntando al SHA.

```bash
git add goals/deuda-ci-frente-1/goal.md memoria/
git commit -m "docs(goal): frente 1 de deuda de CI cerrado y publicado

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
bash scripts/publicar.sh
```

---

## Self-review del plan

- **Cobertura del spec:** G1 → Tasks 1 y 3; G3 → Task 2; G5 → Task 4; diferidos y G6 → Task 5; verificación A (PR) → Task 6; cierre → Task 7. Frente 2 queda explícitamente fuera (su plan se escribe tras el cierre).
- **Placeholders:** ninguno — SHAs, versión y checksum de actionlint, contenidos completos de dependabot.yml, goal.md y entradas de wiki están en el cuerpo.
- **Consistencia:** el nombre del paso nuevo («Lint the workflow definitions») es idéntico en Task 4 y Task 6; la rama `claude/deuda-ci-frente-1` es la misma en Tasks 1, 6 y 7; la condición «sin rojos nuevos» está definida una sola vez (Global Constraints) y referida desde Tasks 6 y 7.

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** ci.yml:71,88,131,134 actions pineadas por SHA; :31,128 timeout-minutes; :77-85 actionlint por checksum; .github/dependabot.yml; CHANGELOG.md:357-363

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
