# Unificación de repos (PDC dentro de lps-aia) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mover la SPA del repo `plan-de-compras` a `lastplanneraia-construccion`, bajo `pdc-app/`, conservando los 114 commits de historia y dejando el build escribiendo directo a `public/pdc-app/`.

**Architecture:** `git subtree add` injerta el repo entero bajo `pdc-app/` con las rutas reescritas. Después, tres commits acotados reubican documentación, conocimiento y build. La SPA conserva su propio `package.json`: el de la raíz de lps-aia no se toca. Ningún archivo PHP del módulo se mueve.

**Tech Stack:** git subtree · Vite 8 + React 19 + AG Grid 36 · Vitest 4 · PHP 8.3 / MySQL 8 en Docker.

## Global Constraints

- Spec de referencia: `docs/superpowers/specs/2026-07-29-unificacion-repos-design.md`.
- El bundle reconstruido debe salir **byte a byte idéntico** al publicado hoy: `md5 = 3b51ff54a8523fb8ddda5c881568dd80` para `public/pdc-app/assets/pdc.js`. Si difiere, **PARAR** e investigar antes de seguir.
- Nombres de salida FIJOS: `assets/pdc.js` y `assets/pdc.css`. El shell PHP los referencia por nombre en `views/plan-compras/app.view.php:26` y `:41`.
- `index.html` **no se publica nunca** en `public/pdc-app/`: en producción la página la sirve el shell PHP y dejarlo publicado permitiría servirlo por accidente. Hoy lo borra `scripts/sync-to-lps.sh`; al morir ese script, la protección se traslada al script `build`.
- El `package.json` de la raíz de lps-aia (biome, playwright, `node --test`) **no se modifica**.
- Todo el trabajo ocurre en el worktree `/Volumes/Crucial X6/Developer/lps-aia-pdc`. NO trabajar en `/Volumes/Crucial X6/Developer/lps-aia`: lo comparten otras sesiones.
- Commits en español, imperativo, explicando el porqué. Coautoría: `Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>`.

---

### Task 1: Importar la SPA con su historia

**Files:**
- Create: `pdc-app/**` (118 archivos, vía subtree)
- Test: `git log --follow pdc-app/src/lib/planFechas.ts`

**Interfaces:**
- Consumes: el repo local `/Volumes/Crucial X6/Developer/plan-de-compras`, rama `main`.
- Produces: el directorio `pdc-app/` con `package.json`, `vite.config.ts`, `index.html`, `src/`, `scripts/`, `docs/superpowers/`, `goals/`. Las tareas 2–4 operan sobre esas rutas.

- [ ] **Step 1: Verificar que el worktree está limpio y al día**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git fetch origin
git status --short
git rev-list --count origin/main..HEAD
git rev-list --count HEAD..origin/main
```

Esperado: los dos conteos en `0` (la rama está exactamente en `origin/main`). En `git status` puede aparecer **un solo** archivo ajeno, de otra sesión:
`docs/qa/evidence/catalog-goal-audit-20260702/backup-restore-smoke/lacp-backup-restore-smoke.sql.sha256`.
Si aparece cualquier OTRO archivo modificado, **PARAR**: hay una sesión trabajando ahí.

- [ ] **Step 2: Apartar el archivo ajeno, que bloquea el subtree**

`git subtree add` exige árbol limpio. El `.sha256` es de otra sesión, así que se aparta y se devuelve intacto al final de la tarea.

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git stash push -m "ajeno: sha256 de evidencia QA, se devuelve tras el subtree" \
  docs/qa/evidence/catalog-goal-audit-20260702/backup-restore-smoke/lacp-backup-restore-smoke.sql.sha256
git status --short
```

Esperado: `git status --short` sin salida. Si el archivo ya no estaba modificado, el `stash push` dice `No local changes to save` — no es error, seguir.

- [ ] **Step 3: Crear la rama de trabajo**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git checkout -b pdc-unificacion-repos
git branch --show-current
```

Esperado: `pdc-unificacion-repos`.

- [ ] **Step 4: Guardar la línea base del bundle**

Es el número contra el que se verifica toda la mudanza.

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
md5 -q public/pdc-app/assets/pdc.js
md5 -q public/pdc-app/assets/pdc.css
```

Esperado: la primera línea es exactamente `3b51ff54a8523fb8ddda5c881568dd80`. Si no, **PARAR**: alguien republicó el bundle y la línea base del spec ya no vale.

- [ ] **Step 5: Importar con subtree**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git remote add pdc-spa "/Volumes/Crucial X6/Developer/plan-de-compras"
git fetch pdc-spa
git subtree add --prefix=pdc-app pdc-spa main
```

Esperado: termina con `Added dir 'pdc-app'`. Crea un commit de merge automáticamente.

- [ ] **Step 6: Comprobar que la historia viajó**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git log --oneline --follow pdc-app/src/lib/planFechas.ts | wc -l
ls pdc-app/package.json pdc-app/vite.config.ts pdc-app/index.html
git log --oneline -1
```

Esperado: el conteo de `--follow` es **mayor que 1** (si diera 1, la historia no viajó y hay que deshacer con `git reset --hard origin/main` y revisar). Los tres archivos existen.

- [ ] **Step 7: Devolver el archivo ajeno**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git stash pop
git status --short
```

Esperado: vuelve a aparecer solo el `.sha256`. Si el Step 2 no guardó nada, `git stash pop` dirá `No stash entries found` — seguir.

- [ ] **Step 8: Quitar el remote temporal**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git remote remove pdc-spa
git remote -v
```

Esperado: solo queda `origin`.

---

### Task 2: Fusionar la documentación con la que ya existe

**Files:**
- Modify (mover): `pdc-app/docs/superpowers/specs/*` → `docs/superpowers/specs/`
- Modify (mover): `pdc-app/docs/superpowers/plans/*` → `docs/superpowers/plans/`
- Modify (mover): `pdc-app/goals/pdc-*` → `goals/`
- Delete: `pdc-app/docs/`, `pdc-app/goals/` (quedan vacíos)

**Interfaces:**
- Consumes: el árbol `pdc-app/` de la Task 1.
- Produces: `docs/superpowers/specs/2026-07-29-unificacion-repos-design.md` y `docs/superpowers/plans/2026-07-29-unificacion-repos.md` accesibles desde la raíz — este mismo plan cambia de ruta aquí.

- [ ] **Step 1: Comprobar que no hay choques de nombres**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
for f in pdc-app/docs/superpowers/specs/*.md pdc-app/docs/superpowers/plans/*.md; do
  d="docs/superpowers/$(basename $(dirname "$f"))/$(basename "$f")"
  [ -e "$d" ] && echo "CHOQUE: $d"
done
for g in pdc-app/goals/*/; do
  [ -e "goals/$(basename "$g")" ] && echo "CHOQUE: goals/$(basename "$g")"
done
echo "fin del chequeo"
```

Esperado: ni una línea `CHOQUE:`. Si aparece alguna, **PARAR** y decidir el renombrado con el usuario.

- [ ] **Step 2: Mover specs, planes y goals**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git mv pdc-app/docs/superpowers/specs/* docs/superpowers/specs/
git mv pdc-app/docs/superpowers/plans/* docs/superpowers/plans/
for g in pdc-app/goals/*/; do git mv "$g" "goals/$(basename "$g")"; done
rmdir pdc-app/docs/superpowers/specs pdc-app/docs/superpowers/plans pdc-app/docs/superpowers pdc-app/docs pdc-app/goals 2>/dev/null
ls docs/superpowers/specs | wc -l
ls goals | wc -l
```

Esperado: `docs/superpowers/specs` pasa de 5 a **13** archivos y `goals` de 6 a **11** carpetas.

- [ ] **Step 3: Comprobar que git conserva el rastro de los movidos**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git status --short | grep -c "^R"
```

Esperado: un número **mayor que 20** (`R` = renombrado detectado; git conserva la historia del archivo).

- [ ] **Step 4: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git add -A docs goals pdc-app
git commit -m "docs(pdc): specs, planes y goals del PDC se juntan con los del repo

Un repositorio unificado con dos sitios donde buscar specs no está unificado.
Los 22 documentos y las 5 carpetas de goals que traía la SPA pasan a las carpetas
comunes; ninguno choca de nombre porque los del PDC ya iban prefijados.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 3: El conocimiento del PDC a `docs/pdc-v2.md`

**Files:**
- Create: `docs/pdc-v2.md` (movido desde `pdc-app/CLAUDE.md`)
- Modify: `CLAUDE.md` (sección `## Reference docs`, al final del archivo)
- Delete: `pdc-app/CLAUDE.md`

**Interfaces:**
- Consumes: `pdc-app/CLAUDE.md` (290 líneas) de la Task 1.
- Produces: `docs/pdc-v2.md`, referenciado desde el `CLAUDE.md` raíz.

- [ ] **Step 1: Mover el archivo conservando su historia**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git mv pdc-app/CLAUDE.md docs/pdc-v2.md
head -3 docs/pdc-v2.md
```

Esperado: el archivo empieza con `# CLAUDE.md`.

- [ ] **Step 2: Cambiar el título y la primera línea**

Ya no es un archivo de instrucciones sino documentación de consulta. Reemplazar las tres primeras líneas de `docs/pdc-v2.md`:

```markdown
# Plan de Compras (PDC) v2 — conocimiento del módulo

Referencia del módulo PDC v2: modelo de dominio, fases A1–A4, decisiones de datos y las trampas ya
medidas. Vive aquí y no en `CLAUDE.md` porque son 290 líneas que solo necesita quien toca el PDC, y
`CLAUDE.md` se carga en todas las sesiones del repo. La SPA está en `pdc-app/`; su PHP en
`src/Services/Pdc/` y `src/Controllers/Api/PlanCompras*`.
```

- [ ] **Step 3: Corregir las nueve líneas que hablaban de dos repos**

Son estas, con su número en el archivo original. Comprobar con
`grep -n "\.\./lps-aia\|npm run sync\|repo hermano\|este repo" docs/pdc-v2.md` que no queda ninguna más.

| Línea | Qué dice hoy | Cómo queda |
|---|---|---|
| 213 | «Este repo **no es autónomo**: es la reimplementación…» | «El PDC v2 es la reimplementación (modelo nuevo, ver abajo) del módulo de Plan de Compras que reemplaza al de familias en esta misma plataforma.» |
| 215 | «**Repo destino:** … en local es el repo hermano `../lps-aia`» | Borrar la viñeta entera: ya no hay repo destino. |
| 216 | «Este repo produce el **reemplazo** de ese módulo…» | «El PDC v2 es el **reemplazo** de ese módulo con el modelo revisado que elimina "familias".» |
| 217 | «este repo desarrolla el **frontend**…, el glue PHP se agrega a lps-aia» | «El frontend es una SPA React + Vite + AG Grid Community en `pdc-app/`; el glue PHP (vista shell, endpoints JSON, migraciones) vive en `src/` y `database/`. **Unificados en un repo el 2026-07-29** (ver `docs/superpowers/specs/2026-07-29-unificacion-repos-design.md`).» |
| 245 | «**Este repo (`plan-de-compras`):** SPA… El build (`dist/`) se despliega a `lps-aia/public/pdc-app/`» | «**La SPA (`pdc-app/`):** … El build escribe directo a `public/pdc-app/` (nombre distinto de la ruta `/plan-compras` para no romper el ruteo de Apache).» |
| 246 | «**En `../lps-aia` (glue PHP):**» | «**El glue PHP:**» |
| 252 | «El working tree principal de lps-aia (`../lps-aia`)…» | Conservar el párrafo tal cual: **sigue siendo cierto** y es la razón de trabajar en el worktree. Solo cambiar `../lps-aia` por `/Volumes/Crucial X6/Developer/lps-aia`. |
| 254 | «NO trabajar PDC en `../lps-aia`» | Igual: sustituir `../lps-aia` por la ruta absoluta. |
| 267–274 | «Comandos de este repo» + los cuatro scripts | Ver el bloque de abajo. |

El bloque de comandos (líneas 267–274) pasa a:

````markdown
Comandos de la SPA (desde `pdc-app/`):

```bash
npm run dev     # Vite dev server con proxy /plan-compras/api → Docker (PDC_API_PORT, por defecto 8091)
npm run build   # tsc + vite build → ../public/pdc-app/assets/{pdc.js,pdc.css}, listo para commitear
npm run test    # Vitest
```
````

- [ ] **Step 4: Añadir el puntero en el CLAUDE.md raíz**

Añadir esta línea al final de la sección `## Reference docs` de `CLAUDE.md`:

```markdown
- `docs/pdc-v2.md` — módulo **Plan de Compras (PDC) v2**: modelo de dominio (presupuesto → maestro de insumos → paquetes → plan con fechas), fases A1–A4, deudas de datos conocidas y trampas ya medidas. La SPA (React + Vite + AG Grid) vive en `pdc-app/` y publica su bundle en `public/pdc-app/`; el PHP, en `src/Services/Pdc/`. Léelo antes de tocar cualquier cosa del PDC.
```

- [ ] **Step 5: Comprobar que el CLAUDE.md raíz no creció de más**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
wc -l CLAUDE.md
```

Esperado: **143 líneas** (142 + 1). Si creció más, se copió contenido que debía quedarse en `docs/pdc-v2.md`.

- [ ] **Step 6: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git add CLAUDE.md docs/pdc-v2.md
git commit -m "docs(pdc): el conocimiento del módulo deja de cargarse en cada sesión

Las 290 líneas del CLAUDE.md de la SPA pasan a docs/pdc-v2.md, con un puntero de
una línea en el CLAUDE.md raíz. Fusionarlas allí lo habría triplicado, y ese
archivo lo pagan también las sesiones que solo tocan CSS del design-system.

No se anida como pdc-app/CLAUDE.md porque buena parte de ese conocimiento es
sobre PHP que vive fuera de esa carpeta, donde no se cargaría.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 4: El build escribe directo a `public/pdc-app/`

**Files:**
- Modify: `pdc-app/vite.config.ts:14` (`outDir`) y `:30` (proxy)
- Modify: `pdc-app/package.json` (scripts, metadatos del repo)
- Modify: `pdc-app/.gitignore`
- Delete: `pdc-app/scripts/sync-to-lps.sh`
- Delete: `public/pdc-app/BUILD.txt`
- Test: reconstrucción y comparación de md5

**Interfaces:**
- Consumes: `pdc-app/` de las tareas anteriores.
- Produces: `cd pdc-app && npm run build` deja `public/pdc-app/assets/pdc.js` y `pdc.css` listos para commitear. Ya no existe `npm run sync` ni `pdc-app/dist/`.

- [ ] **Step 0a: Instalar dependencias con las versiones exactas del lock**

`npm install` puede subir versiones dentro de los rangos `^` y cambiar el bundle por una razón que
no tiene nada que ver con la mudanza. `npm ci` instala exactamente lo que dice `package-lock.json`.

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc/pdc-app"
npm ci
```

Esperado: termina sin errores. Si `npm ci` se queja de desincronización con el lock, **PARAR**: el
lock que viajó no corresponde al `package.json` que viajó, y eso hay que entenderlo antes de seguir.

- [ ] **Step 0b: Reconstruir SIN tocar nada y comprobar el md5**

Este paso separa las dos causas posibles de que el bundle cambie. Aquí la configuración es todavía
la vieja (`outDir: 'dist'`), así que si el md5 ya no coincide, el problema es del entorno —Node,
dependencias— y **no** de la mudanza. Diagnosticarlo ahora vale mucho más que descubrirlo mezclado
con el cambio de configuración.

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc/pdc-app"
npm run build
md5 -q dist/assets/pdc.js
```

Esperado: exactamente `3b51ff54a8523fb8ddda5c881568dd80`.
Si difiere: **PARAR**. Comprobar `node --version` contra la máquina donde se construyó el bundle
publicado y revisar si `npm ci` instaló versiones distintas. No seguir hasta explicarlo.

- [ ] **Step 1: Cambiar el destino del build en `pdc-app/vite.config.ts`**

Reemplazar el bloque `build` completo (líneas 13–25) por:

```ts
  build: {
    // Directo al destino servido: no hay paso de copia que olvidar. El 2026-07-29,
    // con dos repos, se publicó un bundle cuya fuente no estaba commiteada.
    outDir: '../public/pdc-app',
    // outDir cae fuera de la raíz de Vite, así que hay que autorizar el vaciado.
    // Es seguro porque public/pdc-app/ pasa a ser 100% generado: BUILD.txt, que era
    // el único archivo a mano, se borró al unificar (ver el commit de esta tarea).
    emptyOutDir: true,
    rollupOptions: {
      output: {
        entryFileNames: 'assets/pdc.js',
        chunkFileNames: 'assets/chunk-[name].js',
        // El CSS del entry mantiene el nombre fijo pdc.css (contrato con el shell);
        // cualquier otro asset conserva su nombre con prefijo pdc- para no colisionar.
        assetFileNames: (info) =>
          info.names.some((n) => n.endsWith('.css')) ? 'assets/pdc.css' : 'assets/pdc-[name].[ext]',
      },
    },
  },
```

- [ ] **Step 2: Hacer configurable el puerto del proxy**

Reemplazar el bloque `server` (líneas 26–32) por:

```ts
  server: {
    proxy: {
      // En dev la API vive en el Docker de lps-aia. Las cookies de sesión llegan
      // igual (las cookies ignoran el puerto). 8091 es el stack del worktree del
      // PDC; el árbol principal publica 8081. PDC_API_PORT lo cambia sin editar.
      '/plan-compras/api': `http://localhost:${process.env.PDC_API_PORT ?? '8091'}`,
    },
  },
```

- [ ] **Step 3: Actualizar `pdc-app/package.json`**

El script `build` conserva el borrado del `index.html`, que hasta hoy hacía el sync: en producción la página la sirve el shell PHP.

```json
{
  "name": "pdc-app",
  "version": "1.0.0",
  "private": true,
  "description": "SPA del módulo Plan de Compras (PDC v2) de lps-aia",
  "scripts": {
    "dev": "vite",
    "build": "tsc && vite build && rm -f ../public/pdc-app/index.html",
    "test": "vitest run"
  },
  "type": "module",
  "license": "ISC"
}
```

Conservar los bloques `dependencies` y `devDependencies` **exactamente como están**. Se eliminan `main`, `directories`, `repository`, `bugs`, `homepage`, `keywords` y `author`: apuntaban al repo viejo o estaban vacíos.

- [ ] **Step 4: Borrar el script de sincronización y el marcador de procedencia**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git rm pdc-app/scripts/sync-to-lps.sh
git rm public/pdc-app/BUILD.txt
rmdir pdc-app/scripts 2>/dev/null
```

`BUILD.txt` decía de qué commit de `plan-de-compras` salía el bundle. Con un solo repo, el bundle y su fuente están en el mismo commit: el marcador ya no informa nada.

- [ ] **Step 5: Ajustar `pdc-app/.gitignore`**

Reemplazar el contenido por:

```
.DS_Store
.omo/
docs/*
!docs/superpowers/
*.mp4
node_modules/
.claude/
```

Se quita `dist/`: ya no existe ese directorio.

- [ ] **Step 6: Borrar el `dist/` que dejó el Step 0b y reconstruir al destino nuevo**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc/pdc-app"
rm -rf dist
npm run build
```

Esperado: termina con `✓ built in ...`. `pdc-app/dist/` no vuelve a aparecer.

- [ ] **Step 7: LA PRUEBA — el bundle debe ser byte a byte idéntico**

Con el Step 0b en verde, este paso ya solo puede fallar por el cambio de configuración, que es
justo lo que se quiere aislar.

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
md5 -q public/pdc-app/assets/pdc.js
git diff --stat public/pdc-app/
ls public/pdc-app/
```

Esperado: exactamente `3b51ff54a8523fb8ddda5c881568dd80`; `git diff --stat` sin cambios en
`assets/` (solo el `BUILD.txt` borrado); y el listado muestra **solo** `assets/` — sin `index.html`
y sin `BUILD.txt`.

Si el md5 difiere: **PARAR**. No commitear. Como el Step 0b pasó, la causa está en lo que cambió
esta tarea: revisar `base`, `outDir` y los nombres de salida en `vite.config.ts`. Un bundle distinto
sin explicación es un cambio de comportamiento que nadie auditó.

Si aparece `index.html` en el listado, el `rm -f` del script `build` no corrió: revisar que
`package.json` tenga el `&& rm -f ../public/pdc-app/index.html` del Step 3.

- [ ] **Step 8: Correr los tests de la SPA desde su nueva casa**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc/pdc-app"
npm run test
```

Esperado: `Tests  242 passed (242)`.

- [ ] **Step 9: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git add pdc-app/vite.config.ts pdc-app/package.json pdc-app/package-lock.json pdc-app/.gitignore
git add -A pdc-app/scripts public/pdc-app
git commit -m "build(pdc): compilar y publicar dejan de ser dos pasos

Vite escribe directo a public/pdc-app/. Mueren dist/ y sync-to-lps.sh, que era
el paso que se podía olvidar: el 2026-07-29 se publicó un bundle cuya fuente
seguía sin commitear, y eso solo era posible porque construir y publicar vivían
en repos distintos.

El borrado del index.html no se pierde con el script: pasa al propio build. En
producción la página la sirve el shell PHP y publicarlo permitiría servirlo por
accidente. BUILD.txt se va porque ya no informa nada: el bundle y su fuente
están en el mismo commit.

Verificado: el bundle reconstruido es byte a byte idéntico al publicado
(md5 3b51ff54a8523fb8ddda5c881568dd80) y Vitest sigue en 242/242.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 5: Verificación completa e integración

**Files:**
- Modify: `/Volumes/Crucial X6/Developer/plan-de-compras/CLAUDE.md` (aviso de mudanza, en el repo viejo)

**Interfaces:**
- Consumes: la rama `pdc-unificacion-repos` con las tareas 1–4.
- Produces: `origin/main` con la unificación; el repo viejo archivado.

- [ ] **Step 1: Batería completa en el árbol unificado**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
for t in test_pdc_v2_plan_fechas test_pdc_v2_plan_fechas_correspondencias \
         test_pdc_v2_amarre_cronograma test_pdc_v2_paquetes_motor \
         test_pdc_v2_pasos_configurables test_pdc_v2_rbac_pasos \
         test_global_table_safety test_global_table_reconciliation; do
  echo "== $t"; docker compose exec -T app php tests/$t.php 2>&1 | grep -cE "^FAIL"
done
docker compose exec -T app ./vendor/bin/phpstan analyse --no-progress 2>&1 | tail -3
```

Esperado: un `0` por cada suite y `[OK] No errors` de PHPStan.

- [ ] **Step 2: Comprobar que la app sigue viva en el navegador**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8091/plan-compras
```

Esperado: `302` (redirección al login, igual que antes de la mudanza).

- [ ] **Step 3: Comprobar que `git blame` sobrevivió**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git log --oneline --follow pdc-app/src/pages/PlanFechas.tsx | tail -3
```

Esperado: aparecen commits anteriores a la mudanza, con sus mensajes originales.

- [ ] **Step 4: Integrar a main**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
git fetch origin
git rev-list --count HEAD..origin/main
```

Esperado: `0`. Si no lo es, `origin/main` avanzó: mergearlo y repetir el Step 1 antes de continuar.

```bash
git push origin pdc-unificacion-repos:main
git fetch origin && git log --oneline -1 origin/main
```

- [ ] **Step 5: Dejar el aviso en el repo viejo**

Añadir al principio de `/Volumes/Crucial X6/Developer/plan-de-compras/CLAUDE.md`:

```markdown
> **⚠️ Este repositorio está mudado y archivado (2026-07-29).**
> El módulo vive ahora en `lastplanneraia-construccion`: la SPA en `pdc-app/`, su documentación en
> `docs/superpowers/` y `docs/pdc-v2.md`. No trabajes aquí — los cambios que hagas no llegan a
> ninguna parte. Ver `docs/superpowers/specs/2026-07-29-unificacion-repos-design.md` en aquel repo.
```

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras"
git add CLAUDE.md
git commit -m "docs: este repo está mudado a lastplanneraia-construccion

Aviso al principio del CLAUDE.md para que ninguna sesión futura trabaje aquí por
costumbre: es exactamente así como el trabajo se volvería a partir en dos.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
git push origin main
```

- [ ] **Step 6: Archivar el repo viejo en GitHub**

Requiere confirmación del usuario en el momento (es una acción sobre GitHub, reversible con un clic):

```bash
gh repo archive jbenite7/plan-de-compras --yes
gh repo view jbenite7/plan-de-compras --json isArchived
```

Esperado: `{"isArchived":true}`.

---

## Notas para quien ejecute

- **Si el md5 del Step 7 de la Task 4 no coincide, todo se detiene ahí.** Es el único criterio que demuestra que la mudanza no cambió el comportamiento; sin él, lo demás son buenos deseos.
- Las tareas 2, 3 y 4 son independientes entre sí una vez hecha la 1: si una falla, se revierte su commit sin tocar las otras.
- El worktree es compartido. Antes de cada tarea, `git status --short` no debe mostrar más que el `.sha256` ajeno.
