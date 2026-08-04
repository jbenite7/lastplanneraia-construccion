# Cierre de dark mode — Plan de implementación, fases 0–3

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Consolidar el árbol en `main`, hacer que los gates del design system digan la verdad, abrir la puerta de servicio de `admin/` y dejar PG, PI, PS y PDC v2 en verde verificado.

**Architecture:** Se trabaja **directamente en `main`** (decisión del usuario del 2026-08-03) — sin worktrees ni ramas de feature. Primero merges (fase 0), luego los gates (fase 1) porque todo verde posterior depende de ellos, luego en paralelo la puerta de `admin/` (fase 2) y los módulos (fase 3). Los goldens se recapturan al final de la fase 3, porque retirar copias del chip puede mover píxeles.

**Tech Stack:** Node 20 (`node --test`, Playwright), PHP 8.3 en Docker (`docker compose exec app`), gates propios en `scripts/design-system-*.mjs`.

## Global Constraints

- Spec de origen: `docs/superpowers/specs/2026-08-03-cierre-dark-mode-design.md`. Las fases 4–7 NO están en este plan: tendrán planes propios.
- UI solo desktop ≥1180 px, solo dark; viewport canónico **1180×820** (AGENTS.md). Nada de mobile/tablet/`linen`.
- Sesión local siempre por `http://localhost:8081/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E`, nunca por `/login`.
- PHP y herramientas dentro del contenedor: `docker compose exec app …`.
- «Cero» = cero fuera de excepciones inventariadas con razón escrita.
- No recapturar goldens ni baselines sin aprobación explícita del usuario en ese momento.
- Dos archivos sucios en `main` pertenecen a otras sesiones y NO se tocan ni se incluyen en ningún commit de este plan: `tests/design-system/accessibility.test.mjs` y `views/dashboard/escalamientos.php`. Todo `git add` de este plan es por ruta explícita, nunca `git add -A`.
- Mensajes de commit en español, con `Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>`.

---

## FASE 0 · Consolidar el árbol

### Task 1: Fusionar las 4 ramas `claude/*` a `main`

**Files:**
- Modify: historia de git únicamente (merges), ningún archivo a mano.

**Interfaces:**
- Consumes: ramas `claude/admiring-bose-b4ef3c` (3 adelante), `claude/competent-jepsen-dec1c4` (3), `claude/nostalgic-austin-50d4aa` (1), `claude/nostalgic-thompson-dceb00` (3) — las cuatro con 0 commits por detrás del `main` de la medición (`4c46825`); `main` avanzó después (`5b20d4a`), así que serán merges normales, no fast-forward.
- Produces: `main` conteniendo esos 10 commits; los tests de las áreas tocadas en verde.

- [ ] **Step 1: Verificar estado de partida**

```bash
git -C "/Volumes/Crucial X6/Developer/lps-aia" status --short
git -C "/Volumes/Crucial X6/Developer/lps-aia" log --oneline -1
```
Esperado: solo los 2 archivos ajenos (`accessibility.test.mjs`, `escalamientos.php`) modificados; HEAD en `5b20d4a` o posterior. Si aparece un tercer archivo sucio, PARAR y preguntar al usuario.

- [ ] **Step 2: Merge de cada rama, una a una**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia"
git merge --no-ff claude/nostalgic-austin-50d4aa -m "merge: ops-state-chip.css del goal pg-chip-de-estado (claude/nostalgic-austin-50d4aa)"
git merge --no-ff claude/admiring-bose-b4ef3c -m "merge: puerto dinamico en scripts sueltos de tests/browser (claude/admiring-bose-b4ef3c)"
git merge --no-ff claude/competent-jepsen-dec1c4 -m "merge: arreglo del listado de control de cambios (claude/competent-jepsen-dec1c4)"
git merge --no-ff claude/nostalgic-thompson-dceb00 -m "merge: FK que bloqueaba el cierre real de crisis LPS (claude/nostalgic-thompson-dceb00)"
```
Si un merge da conflicto: resolverlo conservando ambas intenciones, y si la resolución no es obvia, PARAR y preguntar. Nota: los archivos sin commitear que queden en los worktrees `.claude/worktrees/*` son de sus sesiones dueñas — el merge solo toma commits, no los toca.

- [ ] **Step 3: Correr las pruebas de las áreas tocadas**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia"
docker compose exec app php tests/test_global_table_safety.php
docker compose exec app php tests/test_lps_crisis_close.php 2>/dev/null || echo "AVISO: test_lps_crisis_close.php no llegó con el merge — comprobar si la rama thompson lo traía committeado"
node --test tests/design-system/*.test.mjs 2>&1 | tail -8
```
Esperado: los PHP pasan; en la suite Node el único fallo tolerado es `activation: worktree and index must be clean` (árbol sucio por los 2 archivos ajenos — se arregla en Task 5). Cualquier otro fallo: diagnosticar con `superpowers:systematic-debugging` antes de seguir.

- [ ] **Step 4: No hay commit propio** — los merges ya son commits. Verificar:

```bash
git log --oneline --merges -4
```

### Task 2: Integrar `worktree-usabilidad-altas-y-medias` (11 adelante / 14 atrás)

**Files:**
- Modify: historia de git; conflictos probables en `public/css/*.css` y vistas.

**Interfaces:**
- Consumes: worktree en `.claude/worktrees/usabilidad-altas-y-medias` con 5 archivos sin commitear (`.gitignore`, `public/css/control-cambios.css`, `public/css/design-system/adapters/datatables.css`, `views/control-cambios/controlCambios.view.php`, `tests/browser/control-cambios-listado.spec.mjs`).
- Produces: rama integrada en `main`; su trabajo sin commitear consolidado con mensaje honesto (decisión del spec).

- [ ] **Step 1: Commitear el trabajo suelto del worktree, en el worktree**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia/.claude/worktrees/usabilidad-altas-y-medias"
git status --short
git add .gitignore public/css/control-cambios.css public/css/design-system/adapters/datatables.css views/control-cambios/controlCambios.view.php tests/browser/control-cambios-listado.spec.mjs
git commit -m "wip(usabilidad): estados vacios de control-cambios — consolidado al parar las sesiones el 2026-08-03, sin revision de su sesion dueña

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

- [ ] **Step 2: Traer `main` a la rama y resolver conflictos allí**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia/.claude/worktrees/usabilidad-altas-y-medias"
git merge main -m "merge: main al dia antes de integrar usabilidad-altas-y-medias"
```
Los conflictos se resuelven aquí, donde el contexto es el de la rama. Regla: en CSS del design system gana la versión que use tokens `--ds-*`; ante duda real, PARAR y preguntar.

- [ ] **Step 3: Verificar la rama fusionada antes de llevarla a `main`**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia/.claude/worktrees/usabilidad-altas-y-medias"
node --test tests/design-system/*.test.mjs 2>&1 | tail -5
npx playwright test tests/browser/control-cambios-listado.spec.mjs --workers=1 2>&1 | tail -5 || echo "AVISO: spec nuevo en rojo — anotar y decidir con el usuario si bloquea"
```

- [ ] **Step 4: Merge a `main` y test**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia"
git merge --no-ff worktree-usabilidad-altas-y-medias -m "merge: fase 2 de usabilidad (estados vacios) — worktree-usabilidad-altas-y-medias"
node --test tests/design-system/*.test.mjs 2>&1 | tail -5
```

### Task 3: Push y censo de las 15 ramas viejas

**Files:**
- Create: `docs/superpowers/ramas-viejas-2026-08-03.md`

**Interfaces:**
- Produces: `origin/main` actualizado; lista contenida/única por rama para decisión del usuario. **Este plan no borra ni fusiona ninguna rama vieja.**

- [ ] **Step 1: Push**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia" && git push origin main
```

- [ ] **Step 2: Censar cada rama vieja**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia"
for b in c1-retiro-pdc-viejo docs/rutina-deploy-orden-migraciones claude/vigorous-spence-bb48b9 pdc-a4-fechas pdc-a42-frentes pdc-b1-amarre-cronograma pdc-deudas-datos pdc-dev pdc-revision-ux pdc-unificacion-repos worktree-agent-ac5c40b19109aad58 worktree-lab-preview worktree-pdc-b2-vencimientos worktree-pdc-b3-torre-control worktree-pdc-ola2-ayuda-in-app worktree-pdc-ola2-equipo-alq-comp worktree-pdc-presupuesto-impacto-tamiz; do
  u=$(git cherry main "$b" 2>/dev/null | grep -c '^+');
  echo "$b : $u commits con contenido no presente en main";
done
```
(`git cherry` compara por parche, no por hash: detecta trabajo ya aplicado con otro hash.)

- [ ] **Step 3: Escribir el censo** en `docs/superpowers/ramas-viejas-2026-08-03.md`: tabla rama · commits únicos · qué contienen (`git log --oneline main..RAMA | head -5` por rama con únicos > 0) · veredicto propuesto (borrable / revisar). Commit:

```bash
git add docs/superpowers/ramas-viejas-2026-08-03.md
git commit -m "docs(ramas): censo de las 15 ramas viejas — cuales estan contenidas en main y cuales no

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
git push origin main
```

---

## FASE 1 · Que los gates dejen de mentir

### Task 4: Runner que ejecuta todos los pasos de la suite estática (M-01)

**Files:**
- Create: `scripts/design-system-static-suite.mjs`
- Modify: `package.json` (script `test:design-system:static`)
- Test: la propia salida del runner (ver Step 3)

**Interfaces:**
- Consumes: los 8 comandos que hoy encadena `test:design-system:static` con `&&`.
- Produces: comando único que **siempre** corre los 8, imprime `PASS/FAIL` por paso y un resumen, y sale con código 1 si cualquiera falló. Los planes de fases 4–7 dependen de este contrato.

- [ ] **Step 1: Escribir el runner**

```js
// scripts/design-system-static-suite.mjs
// Corre TODOS los pasos de la suite estática aunque alguno falle.
// Razón: la cadena && ocultaba contracts, consumer-contract y audit tras el
// primer rojo — la trampa documentada el 2026-08-03 en la matriz de dark mode.
import { spawnSync } from 'node:child_process';

const steps = [
  ['entrypoint-partition', ['scripts/design-system-entrypoint-partition.mjs']],
  ['unlayered-delivery', ['scripts/design-system-unlayered-delivery.mjs']],
  ['bi-utilities', ['scripts/design-system-bi-utilities.mjs']],
  ['table-contract', ['scripts/design-system-table-contract.mjs']],
  ['node-tests', ['--test', ...['tests/design-system', 'tests/scripts'].flatMap((d) => globTests(d))]],
  ['contracts', ['scripts/design-system-contracts.mjs']],
  ['consumer-contract', ['scripts/design-system-consumer-contract.mjs']],
  ['audit', ['scripts/design-system-audit.mjs']],
];

function globTests(dir) {
  const { readdirSync } = require('node:fs');
  return readdirSync(dir)
    .filter((f) => f.endsWith('.test.mjs'))
    .filter((f) => dir !== 'tests/scripts' || f === 'design-system-audit.test.mjs')
    .map((f) => `${dir}/${f}`);
}

const results = steps.map(([name, args]) => {
  const r = spawnSync(process.execPath, args, { stdio: 'inherit' });
  const ok = r.status === 0;
  console.log(`\n[static-suite] ${ok ? 'PASS' : 'FAIL'} ${name}`);
  return { name, ok };
});

console.log('\n[static-suite] resumen:');
for (const { name, ok } of results) console.log(`  ${ok ? '✔' : '✘'} ${name}`);
const failed = results.filter((r) => !r.ok);
process.exit(failed.length === 0 ? 0 : 1);
```
Nota para el implementador: `require` no existe en ESM — usa `import { readdirSync } from 'node:fs'` arriba y quita el `require` del ejemplo. El listado de tests debe reproducir exactamente lo que hoy expande el glob del script npm: `tests/design-system/*.test.mjs` más `tests/scripts/design-system-audit.test.mjs`.

- [ ] **Step 2: Apuntar el script npm al runner**

En `package.json`, reemplazar el valor de `test:design-system:static` por:
```json
"test:design-system:static": "node scripts/design-system-static-suite.mjs"
```

- [ ] **Step 3: Verificar que ya no miente**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia" && npm run test:design-system:static; echo "exit=$?"
```
Esperado AHORA (antes de Tasks 5–6): los 8 pasos aparecen en el resumen — incluidos `contracts`, `consumer-contract` y `audit`, que antes no llegaban a correr —, con `node-tests` y/o `audit` en FAIL y `exit=1`. Ver los 8 nombres en el resumen ES la prueba de que M-01 está resuelto.

- [ ] **Step 4: Commit**

```bash
git add scripts/design-system-static-suite.mjs package.json
git commit -m "fix(gates): la suite estatica corre entera aunque un paso falle

La cadena && cortaba en el primer rojo y contracts, consumer-contract y
audit no llegaban a ejecutarse: un verde parcial se leia como verde total.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

### Task 5: El check de árbol limpio deja de enmascarar gates (M-02)

**Files:**
- Modify: `scripts/design-system-activation-git.mjs:39-43`
- Modify: `tests/design-system/closeout-receipts.test.mjs:84,143` (los dos asserts que esperan el fallo)

**Interfaces:**
- Consumes: `activationGitFailures(root, gateIds)` — hoy empuja `'activation: worktree and index must be clean'` ante cualquier árbol sucio.
- Produces: mismo export; el fallo por árbol sucio solo se emite con `DS_ACTIVATION_STRICT=1` (CI y los tests de fixture lo ponen); sin la variable se imprime `[activation] aviso: worktree sucio (no bloquea en local)` por stderr y no se empuja el fallo. Los checks de `activation: <ruta> must match HEAD exactly` **no cambian**.

- [ ] **Step 1: Modificar el gate**

En `scripts/design-system-activation-git.mjs`, dentro de `activationGitFailures`, reemplazar:
```js
  const status = git(root, ['status', '--porcelain=v1', '--untracked-files=all']);
  if (status.status !== 0 || status.stdout.length !== 0) {
    failures.push('activation: worktree and index must be clean');
  }
```
por:
```js
  const status = git(root, ['status', '--porcelain=v1', '--untracked-files=all']);
  if (status.status !== 0 || status.stdout.length !== 0) {
    if (process.env.DS_ACTIVATION_STRICT === '1') {
      failures.push('activation: worktree and index must be clean');
    } else {
      console.error('[activation] aviso: worktree sucio (no bloquea en local; CI usa DS_ACTIVATION_STRICT=1)');
    }
  }
```

- [ ] **Step 2: Actualizar los dos tests de fixture** — en `tests/design-system/closeout-receipts.test.mjs`, los `spawnSync` cuyos asserts (líneas 84 y 143) esperan ese mensaje deben pasar el entorno: añadir `env: { ...process.env, DS_ACTIVATION_STRICT: '1' }` a sus opciones (localizar el spawn que alimenta cada assert; si el assert 143 lee `closeoutFailures()` en proceso, poner y restaurar `process.env.DS_ACTIVATION_STRICT` alrededor con `t.after`).

- [ ] **Step 3: Cablear CI si existe workflow**

```bash
grep -rn "design-system" .github/workflows/ 2>/dev/null | head -5
```
Si algún workflow corre la suite estática, añadir `DS_ACTIVATION_STRICT: "1"` a su bloque `env:`. Si no hay workflows, anotar en el commit que CI no existe aún.

- [ ] **Step 4: Verificar en las dos direcciones**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia"
node --test tests/design-system/contracts.test.mjs tests/design-system/closeout-receipts.test.mjs 2>&1 | tail -5
DS_ACTIVATION_STRICT=1 node scripts/design-system-contracts.mjs; echo "strict exit=$?"
```
Esperado: los tests pasan con el árbol sucio actual; con `DS_ACTIVATION_STRICT=1` y árbol sucio el gate sale rojo (exit≠0) con el mensaje de siempre.

- [ ] **Step 5: Commit**

```bash
git add scripts/design-system-activation-git.mjs tests/design-system/closeout-receipts.test.mjs
git commit -m "fix(gates): el arbol sucio avisa en local y solo bloquea en modo estricto

Un worktree sucio hacia fallar activation y ese rojo enmascaraba los tres
gates siguientes de la cadena. CI conserva el candado con DS_ACTIVATION_STRICT=1.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

### Task 6: Retirar los 2 hex de reserva (M-03)

**Files:**
- Modify: `public/css/profesionales.css:121`, `public/css/subcontratistas.css:117`

**Interfaces:**
- Produces: `node scripts/design-system-audit.mjs` con exit 0 (los presupuestos `profesionales` y `subcontratistas` exigen `hardcoded-hex: 0`).

- [ ] **Step 1: Editar las dos líneas** — en ambos archivos, reemplazar
`color: var(--ds-color-brand-primary, #6c9077) !important;` por
`color: var(--ds-color-brand-primary) !important;`
(El `!important` se queda: es asunto de la fase 6, no de esta.)

- [ ] **Step 2: Verificar el audit y la superficie**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia" && node scripts/design-system-audit.mjs >/dev/null 2>&1; echo "audit exit=$?"
```
Esperado: `audit exit=0`. Además, comprobar que el token existe (si no, el color caería a herencia):
```bash
grep -rn -- "--ds-color-brand-primary:" public/css/ | head -3
```
Esperado: al menos una definición. Después, en navegador: abrir `http://localhost:8081/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E`, navegar a `/profesionales` a 1180×820 dark y confirmar que el elemento afectado (línea 121, contexto de la regla) conserva el color de marca.

- [ ] **Step 3: Commit**

```bash
git add public/css/profesionales.css public/css/subcontratistas.css
git commit -m "fix(design-system): fuera los dos hex de reserva que tenian el audit en rojo

El fallback #6c9077 dentro del var() contaba como hardcoded-hex contra un
presupuesto de cero. El token existe, la reserva era una linea muerta.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

### Task 7: `incomplete` deja de contar como `violation` en axe (M-11)

**Files:**
- Modify: `tests/browser/support/accessibility.mjs` (función `evaluateAccessibility`)
- Test: `tests/design-system/accessibility.test.mjs` — **ATENCIÓN: está sucio en `main`, es de la sesión «Arreglar el conteo de excepciones a11y»**. Antes de tocar nada: `git diff tests/design-system/accessibility.test.mjs`. Si su diff ya implementa esta misma separación, adoptarlo (commitearlo citando su origen) en lugar de duplicar el trabajo; si hace otra cosa, PARAR y preguntar al usuario cómo conciliar.

**Interfaces:**
- Consumes: `evaluateAccessibility(results, { surface, baseline, exceptions, now })` → `{ blocking, reported, excepted, existing, newFindings }`; hoy un `incomplete` con impact en `BLOCKING_IMPACTS` entra a `blocking`.
- Produces: misma firma; **solo `kind === 'violation'` puede bloquear**; todo `incomplete` va a `reported` (salvo que esté en `excepted`). Los fingerprints no cambian de formato.

- [ ] **Step 1: Escribir el test que falla** — añadir a `tests/design-system/accessibility.test.mjs` (o crear junto a los existentes si el diff ajeno se descartó):

```js
test('incomplete never blocks, even with critical impact', () => {
  const results = {
    violations: [],
    incomplete: [{ id: 'color-contrast', impact: 'critical', nodes: [{ target: ['.glass'], failureSummary: 'no se pudo medir sobre fondo translucido' }] }],
  };
  const outcome = evaluateAccessibility(results, { surface: 'lab' });
  assert.equal(outcome.blocking.length, 0);
  assert.equal(outcome.reported.length, 1);
  assert.equal(outcome.reported[0].kind, 'incomplete');
});
```

- [ ] **Step 2: Verificar que falla**

```bash
node --test tests/design-system/accessibility.test.mjs 2>&1 | tail -5
```
Esperado: FAIL — hoy `blocking.length` es 1.

- [ ] **Step 3: Implementación mínima** — en `evaluateAccessibility`, cambiar la condición de bloqueo:

```js
    } else if (finding.kind === 'violation' && BLOCKING_IMPACTS.has(finding.impact)) {
      outcome.blocking.push(finding);
```

- [ ] **Step 4: Verificar que pasa, y que nada más se rompió**

```bash
node --test tests/design-system/accessibility.test.mjs 2>&1 | tail -5
npm run test:design-system:static 2>&1 | tail -12
```
Esperado: el test nuevo PASS; la suite entera con sus 8 pasos y exit 0 (Tasks 4–6 ya aplicadas).

- [ ] **Step 5: Commit** (incluir el test solo si el diff ajeno se adoptó o se escribió aquí; dejar constancia en el mensaje):

```bash
git add tests/browser/support/accessibility.mjs tests/design-system/accessibility.test.mjs
git commit -m "fix(a11y): incomplete se reporta, no bloquea

axe devuelve incomplete garantizado sobre las superficies translucidas del
sistema; aplanarlo con violation fabricaba rojos falsos que enseñaban a
ignorar los rojos de verdad (M-11 de la matriz del 2026-08-03).

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## FASE 2 · Puerta de servicio para `admin/`

### Task 8: Spec de la puerta de `admin/` (gate de revisión humana)

**Files:**
- Create: `docs/superpowers/specs/2026-08-03-admin-dev-door-design.md`

**Interfaces:**
- Consumes: `src/Core/DevDoor.php` (patrón de tres candados), `docs/superpowers/specs/2026-07-30-dev-door-design.md`, `tests/test_dev_door_guard.php`, `admin/src/Core/Security.php` y el front controller `admin/public/index.php` (leerlos antes de escribir).
- Produces: spec corto (una pantalla) con: los mismos TRES candados de `DevDoor` (`APP_ENV` dev/testing con fallback a producción cerrada · petición local/red Docker · `DEV_DOOR=1` + `DEV_DOOR_USERS` no vacío), ruta `/admin/dev/entrar?u=<cuenta>` que abre sesión de `admin/` con el rol real de la cuenta, 404 (no 403) con la puerta cerrada, y sin permisos por encima de la cuenta.

- [ ] **Step 1: Leer las cuatro fuentes** listadas en Consumes; anotar cómo `admin/` establece su sesión (qué escribe `Security.php`/`RoleManager` en `$_SESSION` tras un login válido) — la puerta debe producir exactamente ese estado, ni una clave más.
- [ ] **Step 2: Escribir el spec** con: objetivo, los tres candados calcados, el mapa exacto de `$_SESSION` que produce, qué NO hace (no crea cuentas, no salta RBAC, no existe en producción), y el plan de prueba (guard + rol permitido/denegado).
- [ ] **Step 3: PRESENTARLO AL USUARIO y esperar aprobación explícita.** Es una vía de autenticación nueva: sin ese sí, las Tasks 9–10 no arrancan.
- [ ] **Step 4: Commit del spec aprobado**

```bash
git add docs/superpowers/specs/2026-08-03-admin-dev-door-design.md
git commit -m "docs(admin): spec de la puerta de servicio del panel, aprobado

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

### Task 9: Implementar `Admin\Core\DevDoor` + ruta

**Files:**
- Create: `admin/src/Core/DevDoor.php`
- Modify: front controller de `admin/` (`admin/public/index.php`) — registrar la ruta SOLO si `DevDoor::isOpen()`
- Test: `tests/test_admin_dev_door_guard.php` (Task 10, antes de dar esto por hecho)

**Interfaces:**
- Consumes: el spec aprobado de Task 8; `Admin\Core\Security` para materializar la sesión.
- Produces: `Admin\Core\DevDoor::isOpen(): bool`, `::allows(string $login): bool`, `::allowedUsers(): array` — firmas idénticas a `App\Core\DevDoor` para que el guard test pueda ser un calco. Ruta `GET /admin/dev/entrar?u=<login>`.

- [ ] **Step 1: Copiar el patrón** de `src/Core/DevDoor.php` a `admin/src/Core/DevDoor.php` bajo namespace `Admin\Core`, adaptando la lectura de entorno a como `admin/` lee `.env` (verificarlo en su bootstrap — `admin/` no comparte `src/Core`, así que `AppEnvironment` no está disponible: replicar el check de `APP_ENV` con la misma regla «valor desconocido = producción = cerrada»).
- [ ] **Step 2: Registrar la ruta** en el front controller de `admin/`, detrás de `if (\Admin\Core\DevDoor::isOpen())` — con la puerta cerrada la ruta NO se registra y el router devuelve su 404 natural. El handler: validar `allows($u)`, cargar el usuario por login con el modelo `User` existente, establecer la sesión exactamente como el login real (mapa anotado en Task 8), redirigir al índice del panel.
- [ ] **Step 3: Probar a mano en las dos direcciones**

```bash
curl -s -o /dev/null -w "%{http_code}\n" "http://localhost:8081/admin/dev/entrar?u=test.A"     # esperado: 302 con DEV_DOOR=1
docker compose exec -e DEV_DOOR=0 app php -r 'require "vendor/autoload.php"; var_dump(\Admin\Core\DevDoor::isOpen());'  # esperado: bool(false)
```
Y en navegador: entrar por la puerta, confirmar que `/admin/` abre con el rol real de `test.A`, y que `test.V` (visualizador) NO ve acciones de administración — el par permitido/denegado que exige AGENTS.md.

- [ ] **Step 4: Commit**

```bash
git add admin/src/Core/DevDoor.php admin/public/index.php
git commit -m "feat(admin): puerta de servicio de desarrollo para el panel

Mismos tres candados que la puerta principal; con cualquiera cerrado la
ruta ni se registra. Sin ella las 14 pantallas del panel eran invisibles
para toda revision automatizada (linea D del reparto).

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

### Task 10: Guard test de la puerta de `admin/`

**Files:**
- Create: `tests/test_admin_dev_door_guard.php` (calco de `tests/test_dev_door_guard.php`, apuntando a `Admin\Core\DevDoor`)

**Interfaces:**
- Consumes: `Admin\Core\DevDoor` de Task 9.
- Produces: script autoejecutable (sin runner, como todos los `tests/test_*.php`) que verifica: puerta cerrada con `APP_ENV` desconocido · cerrada con `DEV_DOOR=0` · cerrada con `DEV_DOOR_USERS` vacío · `allows()` rechaza logins fuera de la lista incluso abierta.

- [ ] **Step 1: Escribir el test** copiando la estructura de `tests/test_dev_door_guard.php` (leerlo primero; conservar su forma de fijar/restaurar entorno).
- [ ] **Step 2: Correrlo**

```bash
docker compose exec app php tests/test_admin_dev_door_guard.php
```
Esperado: todas las aserciones en verde.

- [ ] **Step 3: Commit y aviso final** — commitear y recordar al usuario que Task 8–10 constituyen la vía de autenticación nueva que pidió revisar: señalar los tres archivos para su lectura.

```bash
git add tests/test_admin_dev_door_guard.php
git commit -m "test(admin): guard de la puerta de servicio del panel — cerrada por defecto

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## FASE 3 · Los cuatro módulos priorizados

### Task 11: PI — presupuesto de ruta + retirar la copia local del chip

**Files:**
- Modify: `docs/design-system/exceptions.json` (añadir entrada a `pathBudgets` — mismo formato que la entrada `"programa-general"` de ese archivo, línea ~423)
- Modify: `public/css/programacion-intermedia.css:249-…` (bloques `.pi-page .ops-state-chip`)

**Interfaces:**
- Consumes: componente compartido `public/css/design-system/components/ops-state-chip.css`; presupuesto modelo en `exceptions.json:423-431`.
- Produces: entrada `programacion-intermedia` en `pathBudgets` cubriendo `views/programacion-intermedia/…`, `public/css/programacion-intermedia.css`, `public/js/modules/programacion_intermedia/hot.js`, con `maxViolations` fijados al conteo real del audit de HOY (techo que solo baja, no aspiración); CSS del módulo sin reglas propias de `.ops-state-chip` salvo las que el componente no cubra (esas se documentan).

- [ ] **Step 1: Medir el conteo actual del módulo** — correr `node scripts/design-system-audit.mjs` y anotar las violaciones por regla en los 3 paths de PI; esos números son el `maxViolations` inicial.
- [ ] **Step 2: Añadir el presupuesto** a `pathBudgets` en `exceptions.json`, formato calcado de `programa-general`.
- [ ] **Step 3: Retirar la copia local** — en `programacion-intermedia.css`, borrar el bloque base `.pi-page .ops-state-chip { … }` (línea 249). El bloque de línea 278 (variante `blocked-overdue-critical`) se conserva SOLO si expresa un matiz que el componente compartido no tiene; si duplica, fuera también.
- [ ] **Step 4: Verificar en navegador** — `http://localhost:8081/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E`, navegar a `/programacion-intermedia` a 1180×820: los chips se ven (fondo, texto, matiz por `data-aia-hue`), consola sin errores. Comparar contra PG: misma forma de chip.
- [ ] **Step 5: Audit en verde y commit**

```bash
node scripts/design-system-audit.mjs >/dev/null 2>&1; echo "exit=$?"
git add docs/design-system/exceptions.json public/css/programacion-intermedia.css
git commit -m "fix(pi): presupuesto de ruta y chip compartido sin copia local

PI podia regresar a claro sin que nada fallara; ahora tiene techo por regla.
El chip vuelve a una sola fuente de verdad.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

### Task 12: PI — decisión de dominio `pi-state-execution-blocked → OK`

**Files:**
- Modify (según respuesta): `public/js/modules/programacion_intermedia/hot.js` y/o `docs/design-system/state-semantics.json`

- [ ] **Step 1: Reunir el significado real** — leer en `hot.js` cuándo se asigna `pi-state-execution-blocked` (condición exacta) y qué dice el inventario G0 (`goals/cierre-dark-mode-y-tablas/inventario.md:61`: «En ejecución pendiente» mapeada a `ok`).
- [ ] **Step 2: Preguntar al usuario** con AskUserQuestion, mostrando la condición del código: ¿ese estado debe verse `ok` (verde/azul), `atencion` (ámbar) u otro peldaño? Es dominio LPS, no diseño.
- [ ] **Step 3: Aplicar la respuesta** — si cambia el peldaño: actualizar la clase→token en el CSS/JS del módulo y `state-semantics.json`; si se queda en `ok`: dejar constancia escrita en `state-semantics.json` (campo de nota) de que se ratificó. Verificar en navegador la celda afectada. Commit con la decisión en el mensaje.

### Task 13: PS — presupuestos (principal + CIC/CNC/CNP) y retirar las 9 copias del chip

**Files:**
- Modify: `docs/design-system/exceptions.json` (entradas `programacion-semanal-cic`, `-cnc`, `-cnp` — la principal `programacion-semanal` ya existe, verificar sus paths)
- Modify: `public/css/programacion-semanal.css` (líneas 454, 495, 499, 503–505, 2393, 2405) y `public/css/buttons.css:51`

**Interfaces:**
- Consumes: mismo componente compartido y mismo formato de presupuesto que Task 11.
- Produces: CIC/CNC/CNP con techo por regla; `programacion-semanal.css` sin redefiniciones del chip salvo matices no cubiertos, documentados.

- [ ] **Step 1: Medir y declarar los presupuestos** de las 3 vistas satélite (paths: `views/programacion-semanal/CIC.view.php` etc. + su CSS/JS si existe), techo = conteo de hoy.
- [ ] **Step 2: Retirar bloque por bloque** las redefiniciones de `.ops-state-chip` — mismo criterio que Task 11: lo que duplica al componente se va; lo que expresa matiz propio (`ps-alert-critical-route`, etc.) se conserva solo si el componente no lo cubre, con comentario de una línea diciendo por qué.
- [ ] **Step 3: Verificar en navegador** `/programacion-semanal` y sus pestañas CIC/CNC/CNP a 1180×820: chips íntegros, tablas DataTables oscuras, consola limpia.
- [ ] **Step 4: Audit + suite en verde, commit**

```bash
node scripts/design-system-audit.mjs >/dev/null 2>&1; echo "exit=$?"
npm run test:design-system:static 2>&1 | tail -12
git add docs/design-system/exceptions.json public/css/programacion-semanal.css public/css/buttons.css
git commit -m "fix(ps): presupuestos para CIC/CNC/CNP y chip a una sola fuente

Era la mayor concentracion de copias locales del sistema: 9 bloques mas
buttons.css. Lo que sobrevive expresa matiz propio y lo dice en comentario.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

### Task 14: PDC v2 — los 5 hallazgos de `pdc.css:178-190`

**Files:**
- Modify: `public/css/pdc.css:178-190` o `docs/design-system/exceptions.json`

- [ ] **Step 1: Intentar la vía limpia** — el bloque existe porque `#dt_cliente` no hereda el contrato de cabecera de `#hot-container` (dice su propio comentario). Probar: extender el selector del adaptador compartido (`adapters/handsontable.css` o `programa-general-handsontable.css`, donde viva la regla de cabecera) para cubrir también `#dt_cliente`, y borrar el bloque local.
- [ ] **Step 2: Verificar en navegador** `/pdc` a 1180×820: el trigger de filtro sigue encima de la etiqueta, dentro de la cabecera, sin descolocarse. Si la vía limpia rompe la geometría y no hay arreglo razonable en el intento, plan B: dejar el bloque e inventariarlo como excepción justificada en `exceptions.json` con razón «cabecera con filtro fuera del contrato #hot-container».
- [ ] **Step 3: Audit en verde, commit** con la vía elegida explicada en el mensaje.

### Task 15: PG — recapturar goldens con aprobación (SIEMPRE la última de la fase)

**Files:**
- Modify: `tests/browser/__screenshots__/**` (solo los goldens de tabla afectados)

**Interfaces:**
- Consumes: Tasks 11–14 terminadas (mover píxeles después de recapturar invalidaría las capturas).
- Produces: baselines que retratan el aspecto actual, cada una aprobada por el usuario.

- [ ] **Step 1: Correr el visual para listar los rojos**

```bash
npm run test:visual:lab 2>&1 | tail -20
npx playwright test tests/browser/programa-general.visual.mjs --workers=1 2>&1 | tail -10
```
- [ ] **Step 2: Generar los candidatos** con `--update-snapshots` en un directorio temporal o directamente, SIN commitear aún.
- [ ] **Step 3: Mostrar al usuario cada par antes/después** (enviar las imágenes con los dos estados, módulo por módulo) y esperar su sí explícito por par. Un no = se investiga la regresión, no se consagra.
- [ ] **Step 4: Commit solo de lo aprobado**

```bash
git add tests/browser/__screenshots__/
git commit -m "test(visual): goldens de tabla recapturados tras el cambio de colores de estado, con aprobacion par a par

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

- [ ] **Step 5: Cierre de la fase** — correr la suite completa y push:

```bash
npm run test:design-system:static 2>&1 | tail -12
npm run test:design-system:runtime 2>&1 | tail -15
git push origin main
```
Esperado: estática 8/8 en verde; en runtime, cero `violation` bloqueantes (los `incomplete` ya se reportan aparte por Task 7). Reportar al usuario el estado exacto y qué planes siguen (fases 4–7).

---

## FASE 3-BIS · Unificación de tablas (adenda del 2026-08-03 — ver spec §Adenda)

Las decisiones están tomadas en el spec; el detalle paso a paso se refina al despachar cada
task, con las lentes de impeccable (critique), ux-heuristics y refactoring-ui cargadas en la
tarea que corresponda. Orden: 16 → 17 → (18 decide el usuario) → 19, y el task 15 (goldens) se
mueve al FINAL de esta fase.

### Task 16: Paridad del chip PG/PI/PS (asciende M-08)

Reconciliar el componente ops-state-chip con los matices locales supervivientes de PI/PS y el
uso de PG: una sola forma (radio, peso, sombra, tamaño) medida en las tres superficies con
capturas comparadas lado a lado. Lo que sea matiz de módulo justificado se documenta en el
componente, no en el módulo. Verificación: capturas de los 3 módulos + suite estática + runtime
de tabla.

### Task 17: changeType como componente del design system

Inventariar los changeType existentes (Handsontable) y las affordances nativas de DataTables y
AG Grid; crear el skin único (pequeño, sutil, claro — tokens, sin hex) en
public/css/design-system/components/; aplicarlo a las tres librerías vía adaptadores.
Verificación en navegador por librería.

### Task 18: Exploración — bordes solo de fila (gate del usuario)

Prototipo A/B en el laboratorio del design system con las tres librerías: variante actual vs
sin bordes de columna (jerarquía por espaciado/zebra según refactoring-ui). La variante B
incluye además cabecera des-enfatizada (menor tamaño/peso que los datos, estilo label) —
hallazgo Refactoring UI del 2026-08-03: hoy cabeceras y filtros en caja pesan más que los
datos, y quitar bordes sin rebajar la cabecera dejaría el ruido donde estaba. La jerarquía de
toolbar (todos los botones con el mismo peso) queda anotada como candidata aparte, no entra
en este A/B. Critique con
puntuación heurística (impeccable + ux-heuristics), capturas comparadas al usuario, y SU
decisión antes de tocar producción. Si aprueba: cambio en el contrato --ds-table-* y
adaptadores, como task de implementación aparte.

### Task 19: Densidad compacta app-wide (gate del usuario sobre PRODUCT.md)

Extender la escala de /plan-compras (28/13/11) a la familia de tablas: tokens de densidad en el
contrato --ds-table-*, aplicación por adaptador, y actualización de la excepción de
accesibilidad de PRODUCT.md (de superficie única a familia). Criterio añadido (Nielsen sev-3
del 2026-08-03): las cabeceras deben quedar LEGIBLES con la densidad nueva — hoy /pdc muestra
tres columnas truncadas idénticas («INICIO EN OI…») y PI rompe palabras por envoltura;
compactar sin resolver truncado/envoltura de cabecera es empeorar. Antes de aplicar: mostrar al
usuario una captura antes/después de PG con la densidad nueva. Después: task 15 (goldens).

### Task 22: Botones de acción y chips contadores ultra compactos (ver spec §T-5)

Va DESPUÉS del task 19 — comparten los CSS de módulo y el contrato de densidad, y solaparlos
provoca conflictos.

Forma visual compacta con área clicable ampliada a **24 px** por pseudo-elemento — corregido a la
baja desde 32 px por decisión del usuario del 2026-08-03: accesibilidad básica y máximo espacio
para las tablas. 24×24 px es el mínimo EXACTO de WCAG 2.2 SC 2.5.8 (AA): sin margen. Foco visible sobre la forma, no sobre el área. Los chips contadores son
**controles que filtran**: conservan afordancia, foco y estado activo. Componente compartido en
`public/css/design-system/components/`, consumido por los módulos; nada de parchear módulo a
módulo. Sustituir en `PRODUCT.md` la regla de 44 px (que es AAA, SC 2.5.5) por el piso AA de 24 px para
esta familia, con la razón escrita.

Verificación: medir el área clicable real (no solo la visual) con `getBoundingClientRect` del
pseudo-elemento o del propio control, en las toolbars de PG, PI, PS y PDC; foco visible navegando
con teclado; contraste del texto del contador con sonda real. Capturas antes/después de una
toolbar completa. Y jerarquía: aprovechar para que la acción primaria de cada toolbar deje de
pesar lo mismo que las demás (hallazgo Refactoring UI del 2026-08-03).

### Task 23: Decir por qué el estado está retenido (nace del bug reportado el 2026-08-03)

**Origen:** el usuario reportó «los chips de PS no se actualizan al cambiar de estado». Tres rondas
de diagnóstico refutaron el bug con evidencia directa (no hay estado persistido, el renderer
recalcula en cada pintada, el guardado persiste en la misma petición, y `Compromiso` 10→12 en Da
Porto quedó en MySQL con el chip coherente antes, después y tras recargar). La causa real es de
comunicación: **el estado depende de campos que el usuario no está editando** —`Sub_Contratista`
vacío, restricciones abiertas— y la pantalla no dice cuál lo retiene. El usuario decidió tratarlo
como mejora de UX.

Qué construir: cuando el estado de una fila esté retenido por una condición ajena a lo que se
edita, la interfaz debe decir **cuál** y en el punto de uso — no en una leyenda general. Formas
posibles (decidir con `impeccable` + `ux-heuristics`, no inventar): texto en el propio chip al
pasar el cursor o en el panel de detalle, marca en la celda que falta, o una línea en el drawer.
Debe cubrir al menos las condiciones que `PSStateMachine.classifyState()` evalúa además de
`Compromiso`/`Ejecutado_Real`; inventaríalas primero leyendo esa función.

Aplica a Programación Semanal, y comprobar si Programación Intermedia tiene el mismo silencio
(su máquina de estados es hermana). Verificación en navegador con un caso real de estado retenido,
más el ciclo triple. NO recapturar goldens.

### Task 24: La ruptura de palabras sigue viva en Programa General (ciclo del 2026-08-03)

El task 19 corrigió el truncado ambiguo de `/pdc` en el punto compartido, pero el ciclo de
auditoría midió que **en PG las palabras siguen partiéndose**: «Crítica» renderiza 33 px —el ancho
de «Crític»— en dos líneas dentro de una cabecera de 56 px, y las celdas hacen lo mismo
(«HOMECENTE R CALI»). Severidad 3 (Nielsen H6): obliga a reconstruir la palabra al leer.

Ojo al diagnóstico: `word-break` y `overflow-wrap` resuelven `normal` tanto en el `th` como en su
`div.relative`, así que **el mecanismo no es el obvio** — puede ser un ancho de columna menor que
la palabra más un wrapping heredado de otro clon de Handsontable, o un carácter invisible en el
texto. Medir antes de tocar: reproducir el corte, identificar qué regla lo produce, y solo entonces
arreglar. Alcance: cabeceras y celdas de PG; comprobar si PI y PS comparten el defecto.
