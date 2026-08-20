---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-20
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-20-deuda-ci-frente-2.md
resumen: La imagen PHP deja de compilar su capa base (apt + extensiones) desde cero en cada corrida: Buildx con cache type=gha en ambos jobs, con mejora medida…
---

# Deuda del CI · Frente 2 (G2 mínimo, cache de capa base) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** La imagen PHP deja de compilar su capa base (apt + extensiones) desde cero en cada corrida: Buildx con cache `type=gha` en ambos jobs, con mejora medida frío/caliente y sin rojos nuevos.

**Architecture:** La imagen se construye con `docker/build-push-action` (cache gha, `load: true`, tag fijo `lps-aia-app-ci:local`) y compose deja de construirla: el servicio `app` declara ese `image:` y los jobs levantan sin `--build` para app (la db de CI sigue construyéndose con compose). Alcance A del spec: **no** se reordena el Dockerfile ni se cachea `composer install`.

**Tech Stack:** GitHub Actions, docker/setup-buildx-action, docker/build-push-action (ambas pineadas por SHA), Docker Compose, gh CLI.

**Spec:** `docs/superpowers/specs/2026-08-20-deuda-ci-design.md` (Frente 2, con la corrección medida del 2026-08-20)

## Global Constraints

- **Línea base medida (corrida `32394566769` de main):** «Build the PHP test runtime» = 81 s · «Start isolated runtime» = 93 s. La condición de hecho exige corrida con cache caliente por debajo, y **cero rojos nuevos** (única falla admitida: «Check runtime budgets against the baseline» por `initializationMs`, si aún no la cerró su frente).
- **No tocar** `docker/php/Dockerfile` ni las líneas de `COMPOSER_INSTALL_FLAGS` (fijadas por hash en `visual-ci-contract.test.mjs:143-145`). El arg `COMPOSER_INSTALL_FLAGS=""` se pasa ahora desde el paso de buildx.
- El contrato del design system fija pasos y palabras del workflow: suite estática local **antes** de cada push; si un guard rechaza algo que este frente edita legítimamente, el ajuste del contrato va en el mismo commit, nunca para forzar verde.
- **El orden CSS→build no se altera:** en el job runtime, el build de la imagen debe seguir DESPUÉS de «Generate comment-free CSS as the live site serves it» (`COPY . .` entra en build; el espejo debe existir antes — regresión ya medida en el propio workflow).
- Actions nuevas pineadas por SHA con comentario de versión (regla del Frente 1; Dependabot las vigilará solo).
- Rama propia `claude/deuda-ci-frente-2` desde `origin/main`; publicación solo vía `bash scripts/publicar.sh`; goal con `## Cierre` y frontmatter v2 **desde el nacimiento** (lección del F1: el gate de la wiki deniega sin eso).

---

### Task 1: Rama, goal y tag fijo de imagen en compose

**Files:**
- Create: `goals/deuda-ci-frente-2/goal.md`
- Modify: `docker-compose.yml:4-7` (servicio `app`: añadir `image:`)

**Interfaces:**
- Produces: el nombre `lps-aia-app-ci:local` que Tasks 2 y 3 usan como tag del build y compose como imagen a correr.

- [ ] **Step 1: Rama desde origin/main**

```bash
git fetch origin
git checkout -b claude/deuda-ci-frente-2 origin/main
```

- [ ] **Step 2: Goal con frontmatter v2 y cierre pendiente**

Crear `goals/deuda-ci-frente-2/goal.md`:

```markdown
---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-20
areas: [proceso]
fuente: goals/deuda-ci-frente-2/goal.md
resumen: Cache buildx type=gha de la capa base de la imagen PHP en ambos jobs del CI, con mejora medida frío/caliente y sin rojos nuevos.
---

# Goal: Deuda del CI — Frente 2 (G2 mínimo)

Cachear la capa base de la imagen PHP (apt + extensiones) con Buildx `type=gha` en ambos
jobs, sin reordenar el Dockerfile (alcance A, decisión de Felipe 2026-08-20).

**Condición de hecho:** suite estática y actionlint verdes en local; corrida de PR sin rojos
nuevos; una corrida con cache caliente donde el build de la imagen baje de la línea base
(81 s estático / 93 s runtime); publicado vía `scripts/publicar.sh` y confirmado en `main`.

**Plan:** `docs/superpowers/plans/2026-08-20-deuda-ci-frente-2.md`
**Spec:** `docs/superpowers/specs/2026-08-20-deuda-ci-design.md`

## Archivos de este goal
- [[goal.md]] · estado en [[memoria/goals/estado]]
```

(La sección `## Cierre` se escribe en Task 5 con la verificación real — el gate la exige
antes de publicar.)

- [ ] **Step 3: Tag fijo en compose**

En `docker-compose.yml`, servicio `app`, junto al bloque `build:` existente:

```yaml
  app:
    image: lps-aia-app-ci:local
    build:
      context: .
      dockerfile: docker/php/Dockerfile
```

En local no cambia nada funcional: compose seguirá construyendo cuando se le pida `--build`,
solo que etiquetando la imagen con ese nombre. En CI es lo que permite que `up` sin `--build`
use la imagen pre-construida por buildx.

- [ ] **Step 4: Suite estática y commit**

```bash
npm run test:design-system:static
```

Expected: PASS (si un contrato fija `docker-compose.yml` por hash y protesta por la línea
`image:`, ajustarlo en este mismo commit citando este plan).

```bash
git add goals/deuda-ci-frente-2/goal.md docker-compose.yml
git commit -m "ci(cache): la imagen del app queda con nombre fijo para poder pre-construirla

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 2: Buildx con cache gha en el job estático

**Files:**
- Modify: `.github/workflows/design-system.yml` (job `design-system-static`: paso «Build the PHP test runtime»)

**Interfaces:**
- Consumes: tag `lps-aia-app-ci:local` (Task 1).
- Produces: el cache gha que Task 3 reutiliza; el paso conserva su nombre «Build the PHP test runtime» (los contratos leen pasos por nombre).

- [ ] **Step 1: Resolver los SHAs de las dos actions de Docker**

```bash
gh api repos/docker/setup-buildx-action/git/ref/tags/v3 --jq '.object.sha'
gh api repos/docker/build-push-action/git/ref/tags/v6 --jq '.object.sha'
```

Para cada SHA, buscar su tag exacto (mismo procedimiento del Frente 1):

```bash
gh api repos/docker/setup-buildx-action/tags --paginate --jq '.[] | select(.commit.sha=="<sha>") | .name'
gh api repos/docker/build-push-action/tags --paginate --jq '.[] | select(.commit.sha=="<sha>") | .name'
```

Expected: dos SHAs de 40 hex con su `vX.Y.Z`. Se usan en los YAML de abajo donde dice
`<sha-buildx>`/`<sha-build-push>`.

- [ ] **Step 2: Reemplazar el paso de build del job estático**

El paso actual (`run: docker compose -f docker-compose.yml build --build-arg COMPOSER_INSTALL_FLAGS="" app`)
pasa a:

```yaml
      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@<sha-buildx> # vX.Y.Z
      # El arg vacio trae las dependencias de desarrollo (PHPUnit); es el mismo
      # valor que antes pasaba compose. mode=min basta: el Dockerfile es de una
      # sola etapa y todas sus capas viven en la imagen final.
      - name: Build the PHP test runtime
        uses: docker/build-push-action@<sha-build-push> # vX.Y.Z
        with:
          context: .
          file: docker/php/Dockerfile
          load: true
          tags: lps-aia-app-ci:local
          build-args: |
            COMPOSER_INSTALL_FLAGS=
          cache-from: type=gha
          cache-to: type=gha,mode=min
```

El paso siguiente («Correr los tests PHP que no necesitan entorno») no cambia: `compose run`
usa la imagen ya cargada y no reconstruye.

- [ ] **Step 3: actionlint + suite estática**

```bash
docker run --rm -v "$(pwd):/repo" -w /repo rhysd/actionlint:1.7.12 -color
npm run test:design-system:static
```

Expected: ambos PASS (misma regla de ajuste de contrato en el mismo commit si aplica).

- [ ] **Step 4: Commit**

```bash
git add .github/workflows/design-system.yml
git commit -m "ci(cache): el job estatico construye la imagen con buildx y cache de capas

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 3: Buildx con cache gha en el job runtime

**Files:**
- Modify: `.github/workflows/design-system.yml` (job `design-system-runtime`: pasos entre el CSS y «Start isolated runtime»)

**Interfaces:**
- Consumes: el cache gha sembrado por Task 2 y el tag de Task 1.
- Produces: «Start isolated runtime» sin `--build` para app.

- [ ] **Step 1: Insertar el build después del CSS minificado**

Inmediatamente DESPUÉS del paso «Generate comment-free CSS as the live site serves it» (el
orden es contrato: lo que no esté en el árbol al construir no entra a la imagen):

```yaml
      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@<sha-buildx> # vX.Y.Z
      - name: Build the isolated app image
        uses: docker/build-push-action@<sha-build-push> # vX.Y.Z
        with:
          context: .
          file: docker/php/Dockerfile
          load: true
          tags: lps-aia-app-ci:local
          build-args: |
            COMPOSER_INSTALL_FLAGS=
          cache-from: type=gha
          cache-to: type=gha,mode=min
```

- [ ] **Step 2: El compose del runtime deja de construir app**

El paso «Start isolated runtime» pasa de
`up -d --build db app` a dos líneas — la db de CI se sigue construyendo con compose (su
Dockerfile de fixture no entra en este frente):

```yaml
      - name: Start isolated runtime
        run: |
          docker compose -p "$COMPOSE_PROJECT_NAME" -f docker-compose.yml -f docker-compose.ci.yml build db
          docker compose -p "$COMPOSE_PROJECT_NAME" -f docker-compose.yml -f docker-compose.ci.yml up -d db app
```

Nota: los labels `aia.ci.*` del overlay son de contenedor, no de build — se aplican igual en
el `up`. El `build.args` del overlay para app queda sin efecto (el arg ya lo pasa buildx);
no se borra, porque sus líneas están fijadas por hash en el contrato.

- [ ] **Step 3: actionlint + suite estática + commit**

```bash
docker run --rm -v "$(pwd):/repo" -w /repo rhysd/actionlint:1.7.12 -color
npm run test:design-system:static
git add .github/workflows/design-system.yml
git commit -m "ci(cache): el runtime reutiliza la imagen pre-construida y solo compone la db

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 4: PR de verificación con medición frío/caliente

**Files:** ninguno — verificación remota.

**Interfaces:**
- Consumes: la rama con los 3 commits.
- Produces: dos corridas (cache frío y caliente) y sus números, que Task 5 anota en el cierre.

- [ ] **Step 1: Push + PR**

```bash
git push -u origin claude/deuda-ci-frente-2
gh pr create --head claude/deuda-ci-frente-2 --base main --title "ci: frente 2 de deuda — cache buildx de la capa base de la imagen" --body "Frente 2 (alcance A) del spec docs/superpowers/specs/2026-08-20-deuda-ci-design.md. PR de verificación con medición frío/caliente; la publicación sigue siendo por scripts/publicar.sh.

🤖 Generated with [Claude Code](https://claude.com/claude-code)"
```

- [ ] **Step 2: Corrida 1 (cache frío) — sin rojos nuevos**

```bash
run_id=$(gh run list --workflow=design-system.yml --branch claude/deuda-ci-frente-2 --limit 1 --json databaseId --jq '.[0].databaseId')
gh run watch "$run_id" --exit-status --interval 30
gh run view "$run_id" --json jobs --jq '.jobs[] | .name+" => "+.conclusion, (.steps[] | select(.conclusion=="failure") | "  FALLIDO: "+.name)'
```

Expected: mismo criterio del Frente 1 — ningún rojo distinto del paso de presupuestos por
`initializationMs` (verificar la causa con `gh run view "$run_id" --log-failed | grep -i initializationMs`).
En frío el build puede tardar igual o algo más (siembra el cache): no es regresión.

- [ ] **Step 3: Corrida 2 (cache caliente) — medir**

```bash
gh run rerun "$run_id"
gh run watch "$run_id" --exit-status --interval 30
gh run view "$run_id" --json jobs --jq '.jobs[] | .name as $j | .steps[] | select(.name | test("Build the PHP|Build the isolated|Start isolated")) | [$j, .name, ((.completedAt | fromdateiso8601) - (.startedAt | fromdateiso8601) | tostring)+"s"] | @tsv'
```

Expected: «Build the PHP test runtime» **< 81 s** y «Build the isolated app image» + «Start
isolated runtime» sumados **< 93 s**. Si no mejora, STOP: leer el log del paso de build
buscando `CACHED` por capa — si la capa apt no cachea, diagnosticar antes de publicar
(`systematic-debugging`), no publicar un cambio que no paga.

- [ ] **Step 4: Rojos nuevos → arreglar y repetir**

Cualquier fallo distinto es de este frente: log con `--log-failed`, fix, commit atómico,
push (el PR re-corre solo), repetir desde Step 2.

---

### Task 5: Cierre de frente

**Files:**
- Modify: `goals/deuda-ci-frente-2/goal.md` (sección `## Cierre` con los números), `TASKS.md` (marcar el diferible de F2 como hecho), `CHANGELOG.md` (entrada), `memoria/log.md` (ingest)

- [ ] **Step 1: Escribir `## Cierre` en el goal** con: números frío/caliente de Task 4,
      ids de corridas, y el criterio «sin rojos nuevos» citado contra su causa.

- [ ] **Step 2: Wiki** — en `TASKS.md` marcar `[x]` el ítem «CI · Frente 2 (G2, cache de capas
      Docker)» con fecha y los números; en `CHANGELOG.md`, bajo `[Sin publicar]`:

```markdown
### Deuda del CI — Frente 2 (2026-08-20)

#### Changed
- CI: la imagen PHP se pre-construye con buildx y cache `type=gha`; compose ya no la
  reconstruye (antes 81 s + 93 s por corrida). Alcance A del spec
  [[docs/superpowers/specs/2026-08-20-deuda-ci-design]] — el Dockerfile no se tocó.
```

Ingest de una línea a `memoria/log.md` con los números y lo aprendido.

- [ ] **Step 3: Commit de cierre**

```bash
git add goals/deuda-ci-frente-2/goal.md TASKS.md CHANGELOG.md memoria/log.md
git commit -m "docs(goal): cierre del frente 2 con la medicion frio/caliente

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

- [ ] **Step 4: Publicar (gate de AGENTS.md)**

```bash
git fetch origin && git status -sb
```

Si hay divergencia: `git merge origin/main`, resolver a la vista, **re-verificar**
(actionlint + suite estática) y anotar `git rev-parse HEAD`. Luego, con el contenedor
apuntando al worktree si el guard lo exige (`LPS_CODE_ROOT`, y devolverlo a la raíz al
terminar):

```bash
bash scripts/publicar.sh
```

(Con `--con-merges` si integró.) Confirmar `git rev-parse origin/main` == SHA anotado,
vigilar la corrida de `main` con el mismo criterio, y anotar el SHA publicado en el goal.

---

## Self-review del plan

- **Cobertura del spec (F2, alcance A):** cache en ambos jobs → Tasks 2-3; medición
  antes/después → constraint de línea base + Task 4 Step 3; límites duros (Dockerfile y
  `COMPOSER_INSTALL_FLAGS` intactos, guard no se ablanda) → Global Constraints; publicación
  con gate → Task 5.
- **Placeholders:** los dos `<sha-*>` son deliberados — el plan manda resolverlos en Task 2
  Step 1 con el comando exacto, igual que el Frente 1 mandaba re-verificar los suyos. El
  resto (tag de imagen, YAML completos, criterios numéricos) está en el cuerpo.
- **Consistencia:** el tag `lps-aia-app-ci:local` es idéntico en Tasks 1, 2 y 3; los nombres
  de paso «Build the PHP test runtime» (se conserva) y «Build the isolated app image» (nuevo)
  son los que Task 4 mide; la línea base 81 s/93 s aparece una vez en Global Constraints y
  las demás menciones la referencian.
