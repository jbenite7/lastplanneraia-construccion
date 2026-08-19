---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-18
areas: [deploy]
fuente: docs/superpowers/plans/2026-08-18-espacio-cuenta-siteground.md
resumen: que la cuenta de SiteGround deje de guardar en disco lo que git y Composer ya reponen, y que no vuelva a llenarse sola.
---

# Espacio de la cuenta de SiteGround — Plan de implementación

> **Para agentes ejecutores:** SUB-SKILL OBLIGATORIA: usa `superpowers:subagent-driven-development`
> (recomendada) o `superpowers:executing-plans` para ejecutar este plan tarea a tarea. Los pasos
> usan casillas (`- [ ]`) para llevar la cuenta.

**Objetivo:** que la cuenta de SiteGround deje de guardar en disco lo que git y Composer ya
reponen, y que no vuelva a llenarse sola.

**Arquitectura:** cuatro frentes independientes bajo un criterio único — *un respaldo guarda lo que
nadie más puede reponer*. Se ejecutan en orden D → A → B → C: primero lo barato e inmediato, luego
el repo, luego la rutina que evita la recaída, y al final el único cambio con riesgo. Cada frente
cierra con su propia verificación; el gate de publicación se aplica una vez al final.

**Stack:** git, tar, ssh a SiteGround (alias `siteground-pruebas-lastplanner` y
`siteground-produccion-lastplanner`), PHP 8.3 dentro del contenedor `app`, Node para los gates
del design system.

**Diseño de origen:** `docs/superpowers/specs/2026-08-18-espacio-cuenta-siteground-design.md`

## Restricciones globales

- **PHP y Composer solo dentro del contenedor `app`.** Nunca un PHP del host.
  Comando canónico: `docker compose exec app php <ruta>`.
- **Nunca tocar `.env`** en ningún servidor, ni copiarlo, ni volcarlo a un log o a un commit.
- **Producción y pruebas comparten cuenta SSH** y solo se distinguen por carpeta. Antes de
  cualquier comando destructivo hay que imprimir la ruta y confirmar contra cuál se trabaja.
- **Nada de `push --force` ni reescritura de historia publicada.**
- **El gate de cierre de frente de `AGENTS.md` es bloqueante** y se aplica en la Tarea 5.
- **Nada se borra en producción sin haber comprobado antes que git no lo tiene.** La comprobación
  es `git status --porcelain --ignored`, no la intuición.

---

### Tarea 1 — Frente D: basura suelta en producción

**Archivos:**
- Borrar en `~/www/lastplanneraia.com/public_html/`: `test_debug_std.php`, `test_log.php`,
  `index.html.bak-20260327-203406`, `.maintenance.disabled-20260704-221448`
- Mover a `~/backups/`: `~/www/lastplanneraia.com/public_html/2026_MASTER_FUSION.sql`
- Borrar en `~/`: `dump_proyectos_seleccionados_20260327_160840.sql`,
  `dump_proyectos_seleccionados_20260327_160933.sql`,
  `dump_proyectos_seleccionados_20260327_161144.sql`
- No se toca nada del repositorio local.

**Interfaces:**
- Consume: nada.
- Produce: nada de lo que dependan las tareas siguientes. Es independiente.

- [ ] **Paso 1: comprobar que git no tiene ninguno de esos archivos**

```bash
ssh siteground-produccion-lastplanner 'cd ~/www/lastplanneraia.com/public_html && pwd && for f in test_debug_std.php test_log.php index.html.bak-20260327-203406 .maintenance.disabled-20260704-221448 2026_MASTER_FUSION.sql; do printf "%-45s %s\n" "$f" "$(git ls-files --error-unmatch "$f" 2>/dev/null && echo RASTREADO || echo "no rastreado")"; done'
```

Esperado: los cinco dicen `no rastreado`. **Si alguno dice `RASTREADO`, para la tarea** — borrarlo
dejaría el árbol del servidor divergiendo de `main` y el siguiente `pull --ff-only` fallaría.

- [ ] **Paso 2: mover el `.sql` que no está en git a ningún lado**

```bash
ssh siteground-produccion-lastplanner 'cd ~/www/lastplanneraia.com/public_html && mv -n 2026_MASTER_FUSION.sql ~/backups/ && ls -lh ~/backups/2026_MASTER_FUSION.sql'
```

Esperado: `ls` lo encuentra en `~/backups` con sus 8.8 MB. El `-n` impide pisar un archivo con ese
nombre si ya existiera allí.

- [ ] **Paso 3: borrar los cuatro del webroot y los tres dumps del home**

```bash
ssh siteground-produccion-lastplanner 'cd ~/www/lastplanneraia.com/public_html && pwd && rm -f test_debug_std.php test_log.php index.html.bak-20260327-203406 .maintenance.disabled-20260704-221448 && rm -f ~/dump_proyectos_seleccionados_20260327_*.sql && echo BORRADO'
```

- [ ] **Paso 4: verificar que producción sigue en pie y el árbol de git está limpio**

```bash
ssh siteground-produccion-lastplanner 'curl -s -o /dev/null -w "raiz  HTTP %{http_code}\n" -k -H "Host: lastplanneraia.com" https://127.0.0.1/; curl -s -o /dev/null -w "login HTTP %{http_code}\n" -k -H "Host: lastplanneraia.com" https://127.0.0.1/login; cd ~/www/lastplanneraia.com/public_html && git status --short --branch'
```

Esperado: dos `HTTP 200`, y `git status` sin ninguna línea `D` (borrado) de archivo rastreado.

- [ ] **Paso 5: anotar la medición**

No hay commit en esta tarea: no se tocó el repositorio. Apunta el resultado (espacio liberado y los
dos códigos HTTP) para el registro de la Tarea 5.

---

### Tarea 2 — Frente A: archivar los binarios de QA fuera de git

**Archivos:**
- Crear: `/Volumes/Crucial X6/Developer/lps-aia-evidencia/` (fuera del repositorio)
- Crear: `docs/qa/evidence/ARCHIVO.md`
- Borrar del repo: 20 `trace.zip` y 26 `.webm` bajo `docs/qa/evidence/`
- No se toca: ningún `.md`, `.json`, `.png` ni `.sha256` de esa carpeta

**Interfaces:**
- Consume: nada.
- Produce: `docs/qa/evidence/ARCHIVO.md` como único rastro dentro del repo de lo archivado.

- [ ] **Paso 1: dejar constancia del verde ANTES de tocar nada**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia/.claude/worktrees/lastplanneraia-disk-quota-719c1c"
for t in test_goal_close_blockers_manifest test_human_decision_matrix_coverage test_human_decision_actions_package test_human_decision_approval_checklist; do
  docker compose exec -T app php "tests/$t.php" >/dev/null 2>&1 && echo "PASS $t" || echo "FAIL $t"
done
```

Esperado: los cuatro `PASS`. **Si alguno sale `FAIL` de entrada**, para y averigua por qué antes de
seguir: sin verde previo no se puede atribuir un rojo posterior a este cambio.

- [ ] **Paso 2: copiar los 46 binarios al archivo externo, conservando la estructura**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia/.claude/worktrees/lastplanneraia-disk-quota-719c1c"
DEST="/Volumes/Crucial X6/Developer/lps-aia-evidencia"
mkdir -p "$DEST"
git ls-files docs/qa/evidence | grep -E '\.(zip|webm)$' > /tmp/binarios-qa.txt
wc -l < /tmp/binarios-qa.txt
rsync -a --files-from=/tmp/binarios-qa.txt ./ "$DEST/"
du -sh "$DEST"
```

Esperado: `wc -l` dice `46` y `du` dice ~282M.

- [ ] **Paso 3: verificar la copia por hash, no por tamaño**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia/.claude/worktrees/lastplanneraia-disk-quota-719c1c"
DEST="/Volumes/Crucial X6/Developer/lps-aia-evidencia"
fallos=0
while IFS= read -r f; do
  a=$(shasum -a 256 "$f" | cut -d' ' -f1)
  b=$(shasum -a 256 "$DEST/$f" | cut -d' ' -f1)
  [ "$a" = "$b" ] || { echo "DIFIERE: $f"; fallos=$((fallos+1)); }
done < /tmp/binarios-qa.txt
echo "archivos con hash distinto: $fallos"
```

Esperado: `archivos con hash distinto: 0`. **Con un solo fallo, no continúes al paso siguiente** —
borrarías del repo algo cuya copia no está confirmada.

- [ ] **Paso 4: escribir el índice de lo archivado**

Genera `docs/qa/evidence/ARCHIVO.md` con este encabezado y una tabla de las 46 filas:

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia/.claude/worktrees/lastplanneraia-disk-quota-719c1c"
{
  echo "# Evidencia binaria archivada fuera del repositorio"
  echo
  echo "El 2026-08-18 se sacaron de git los 20 \`trace.zip\` y 26 \`.webm\` de esta carpeta: 282 MB"
  echo "que inflaban cada clon y cada respaldo de despliegue. Los \`.md\`, \`.json\` y \`.png\` siguen"
  echo "aquí — de ellos dependen cuatro pruebas PHP."
  echo
  echo "**Dónde están ahora:** \`/Volumes/Crucial X6/Developer/lps-aia-evidencia/\`, con la misma"
  echo "estructura de carpetas. Copia verificada por \`sha256\` archivo a archivo antes de borrar."
  echo "Siguen además en la historia de git mientras no se reescriba."
  echo
  echo "Motivo: ver \`docs/superpowers/specs/2026-08-18-espacio-cuenta-siteground-design.md\`."
  echo
  echo "| Archivo | Tamaño | sha256 |"
  echo "|---|---|---|"
  while IFS= read -r f; do
    printf "| \`%s\` | %s | \`%s\` |\n" "${f#docs/qa/evidence/}" "$(du -h "$f" | cut -f1)" "$(shasum -a 256 "$f" | cut -c1-16)…"
  done < /tmp/binarios-qa.txt
} > docs/qa/evidence/ARCHIVO.md
head -20 docs/qa/evidence/ARCHIVO.md
```

- [ ] **Paso 5: sacarlos del repositorio**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia/.claude/worktrees/lastplanneraia-disk-quota-719c1c"
xargs -d '\n' git rm --quiet < /tmp/binarios-qa.txt
du -sh docs/qa
```

Esperado: `docs/qa` por debajo de 20 MB.

No hace falta tocar `.gitignore`: la línea 210 ya ignora `docs/qa/` entera. Los binarios seguían
dentro porque git no deja de rastrear lo que ya rastreaba — la regla se añadió después de
commitearlos.

- [ ] **Paso 6: verificar que no se rompió nada**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia/.claude/worktrees/lastplanneraia-disk-quota-719c1c"
for t in test_goal_close_blockers_manifest test_human_decision_matrix_coverage test_human_decision_actions_package test_human_decision_approval_checklist; do
  docker compose exec -T app php "tests/$t.php" >/dev/null 2>&1 && echo "PASS $t" || echo "FAIL $t"
done
npm run test:design-system:static
```

Esperado: los cuatro `PASS` y la suite estática en verde. Esa suite es la que ancla artefactos por
hash: es la que avisaría si algo dependía de lo movido sin que lo hubiéramos visto.

- [ ] **Paso 7: commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia/.claude/worktrees/lastplanneraia-disk-quota-719c1c"
git add docs/qa/evidence/ARCHIVO.md
git commit -m "chore(qa): saca de git los 282 MB de traces y videos, archivados fuera

20 trace.zip y 26 .webm del 1-4 de julio inflaban cada clon y cada respaldo de
despliegue. Copiados con verificacion sha256 a lps-aia-evidencia/ y retirados del
arbol; ARCHIVO.md deja el indice de que habia y donde quedo.

Se quedan los .md, .json y .png: de ellos dependen cuatro pruebas PHP, las cuatro
verificadas en verde antes y despues.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Tarea 3 — Frente B: el tar de pre-deploy guarda lo irremplazable

**Archivos:**
- Modificar: `docs/siteground-deploy-routine.md:61-79` (sección «## 3. Backup antes del deploy»)

**Interfaces:**
- Consume: nada de tareas anteriores.
- Produce: el comando de respaldo que la Tarea 4 usa como referencia y que todo despliegue futuro
  ejecuta. Nombres fijados aquí: el tar sigue llamándose
  `<sitio>-predeploy-<STAMP>.tar.gz` y su compañero `<sitio>-predeploy-<STAMP>.manifest.txt`, con
  `STAMP=$(date +%Y%m%d-%H%M%S)`. La rotación depende de que ambos compartan ese prefijo.

- [ ] **Paso 1: probar el comando nuevo en pruebas ANTES de escribirlo en la rutina**

```bash
ssh siteground-pruebas-lastplanner 'set -e; cd ~/www/prueba-lps.lastplanneraia.com; STAMP=$(date +%Y%m%d-%H%M%S); tar -czf ~/backups/prueba-lps-predeploy-$STAMP.tar.gz --exclude="public_html/.git" --exclude="public_html/vendor" --exclude="public_html/node_modules" --exclude="public_html/pdc-app/node_modules" --exclude="public_html/docs" --exclude="public_html/tests" --exclude="public_html/goals" --exclude="public_html/e2e" --exclude="public_html/.github" --exclude="public_html/memoria" -C ~/www/prueba-lps.lastplanneraia.com public_html; echo "STAMP=$STAMP"; ls -lh ~/backups/prueba-lps-predeploy-$STAMP.tar.gz'
```

Esperado: un tar de ~45 MB, frente a los ~677 MB de los anteriores. Apunta el `STAMP`: los pasos 2
y 3 lo necesitan.

- [ ] **Paso 2: verificar que el tar conserva lo irremplazable**

Sustituye `<STAMP>` por el del paso 1:

```bash
ssh siteground-pruebas-lastplanner 'T=~/backups/prueba-lps-predeploy-<STAMP>.tar.gz; echo "storage:  $(tar -tzf $T | grep -c "^public_html/public/storage/")"; echo ".env:     $(tar -tzf $T | grep -cx "public_html/.env")"; echo ".git:     $(tar -tzf $T | grep -c "^public_html/.git/")"; echo "vendor:   $(tar -tzf $T | grep -c "^public_html/vendor/")"'
```

Esperado: `storage` mayor que 0, `.env` exactamente 1, y `.git` y `vendor` exactamente 0. **Si
`.env` sale 0, el respaldo no sirve** — revisa la lista de exclusiones antes de seguir.

- [ ] **Paso 3: generar el manifiesto y comprobar que sitúa el respaldo en la historia**

```bash
ssh siteground-pruebas-lastplanner 'cd ~/www/prueba-lps.lastplanneraia.com/public_html; STAMP=<STAMP>; { echo "commit: $(git rev-parse HEAD)"; echo "rama:   $(git rev-parse --abbrev-ref HEAD)"; echo "fecha:  $(date -Iseconds)"; echo "origen: $(git config --get remote.origin.url)"; } > ~/backups/prueba-lps-predeploy-$STAMP.manifest.txt; cat ~/backups/prueba-lps-predeploy-$STAMP.manifest.txt'
```

Esperado: cuatro líneas, con un SHA de 40 caracteres. Sin esto, un tar sin `.git` no se puede
situar en la historia y deja de ser un respaldo.

- [ ] **Paso 4: probar la rotación**

```bash
ssh siteground-pruebas-lastplanner 'ls -t ~/backups/prueba-lps-predeploy-*.tar.gz | tail -n +4 | while read -r f; do rm -f -- "$f" "${f%.tar.gz}.manifest.txt"; done; ls -lht ~/backups/prueba-lps-predeploy-*.tar.gz | wc -l; du -sh ~/backups'
```

Esperado: quedan exactamente 3 tarballs de pruebas y `~/backups` baja de forma visible.

- [ ] **Paso 5: escribir en la rutina lo que acaba de funcionar**

Sustituye en `docs/siteground-deploy-routine.md` el contenido que hoy ocupan las líneas 61-79 (el
encabezado `## 3. Backup antes del deploy` y sus dos bloques `### Pruebas` y `### Produccion`) por
exactamente esto, cambiando `<SITIO_DIR>` y `<PREFIJO>` por los valores de cada entorno
(`prueba-lps.lastplanneraia.com` / `prueba-lps` y `lastplanneraia.com` / `lastplanneraia`):

````markdown
## 3. Backup antes del deploy

El tar excluye lo que git o Composer reponen (`.git`, `vendor`, `node_modules`, `docs`, `tests`,
`goals`, `e2e`, `.github`, `memoria`) y conserva lo que nadie más puede reponer: `.env`,
`public/storage` y el drift del servidor. De ~690 MB a ~45 MB.

**El `.manifest.txt` no es opcional:** sin `.git` dentro, el tar por sí solo no sabe de qué commit
salió. Restaurar es `git clone` en ese SHA más descomprimir el tar encima.

La rotación va en el mismo comando y deja 3 por sitio. En agosto de 2026 su ausencia llenó la cuota
y bloqueó Site Tools.

```bash
mkdir -p ~/backups
STAMP=$(date +%Y%m%d-%H%M%S)
cd ~/www/<SITIO_DIR>

tar -czf ~/backups/<PREFIJO>-predeploy-$STAMP.tar.gz \
  --exclude='public_html/.git' \
  --exclude='public_html/vendor' \
  --exclude='public_html/node_modules' \
  --exclude='public_html/pdc-app/node_modules' \
  --exclude='public_html/docs' \
  --exclude='public_html/tests' \
  --exclude='public_html/goals' \
  --exclude='public_html/e2e' \
  --exclude='public_html/.github' \
  --exclude='public_html/memoria' \
  -C ~/www/<SITIO_DIR> public_html

cd public_html
{ echo "commit: $(git rev-parse HEAD)"
  echo "rama:   $(git rev-parse --abbrev-ref HEAD)"
  echo "fecha:  $(date -Iseconds)"
  echo "origen: $(git config --get remote.origin.url)"
} > ~/backups/<PREFIJO>-predeploy-$STAMP.manifest.txt

# Comprobar que el respaldo sirve, antes de confiar en el
T=~/backups/<PREFIJO>-predeploy-$STAMP.tar.gz
echo "storage: $(tar -tzf $T | grep -c '^public_html/public/storage/')"   # > 0
echo ".env:    $(tar -tzf $T | grep -cx 'public_html/.env')"              # = 1
echo ".git:    $(tar -tzf $T | grep -c '^public_html/.git/')"             # = 0

# Rotacion: dejar 3, con su manifiesto
ls -t ~/backups/<PREFIJO>-predeploy-*.tar.gz | tail -n +4 | while read -r f; do
  rm -f -- "$f" "${f%.tar.gz}.manifest.txt"
done
ls -lht ~/backups/<PREFIJO>-predeploy-*.tar.gz | head -4
```
````

- [ ] **Paso 6: commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia/.claude/worktrees/lastplanneraia-disk-quota-719c1c"
git add docs/siteground-deploy-routine.md
git commit -m "docs(deploy): el respaldo guarda lo irremplazable, no el repositorio

El tar del paso 3 empaquetaba public_html entero: 690 MB por despliegue, de los
que ~4 MB eran lo unico que git y Composer no reponen. Ahora excluye .git, vendor,
docs, tests, goals, e2e, .github y memoria, y baja a ~45 MB.

Cada tar viaja con un .manifest.txt con el SHA: sin el, un respaldo sin .git no se
puede situar en la historia. La rotacion a 3 por sitio va en el mismo comando.
Verificado en prueba-lps: .env y public/storage dentro, .git y vendor fuera.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Tarea 4 — Frente C: clon shallow en pruebas

**Archivos:**
- Modificar en el servidor: `~/www/prueba-lps.lastplanneraia.com/public_html/.git` (no es un
  archivo del repo; no genera diff)
- Modificar: `docs/siteground-deploy-routine.md` (nota nueva al final, con el efecto sobre el
  reflog)

**Interfaces:**
- Consume: nada.
- Produce: la nota en la rutina. Producción **no** se toca en esta tarea.

- [ ] **Paso 1: comprobar que no hay nada que perder antes de podar**

```bash
ssh siteground-pruebas-lastplanner 'cd ~/www/prueba-lps.lastplanneraia.com/public_html && pwd && du -sh .git && git status -sb | head -3 && echo "--- stashes:" && git stash list'
```

Esperado: `.git` en ~362 MB, la rama sin `ahead`, y **la lista de stashes vacía**. Si hubiera
stashes, para: `gc --prune=now` puede dejarlos inalcanzables y son drift del servidor que nadie
más tiene.

- [ ] **Paso 2: convertir el clon en shallow y podar**

```bash
ssh siteground-pruebas-lastplanner 'cd ~/www/prueba-lps.lastplanneraia.com/public_html && git fetch --depth=1 origin main && git reflog expire --expire=now --all && git gc --prune=now --quiet && du -sh .git'
```

Esperado: `.git` por debajo de 60 MB.

- [ ] **Paso 3: verificar que el ciclo de despliegue sigue funcionando**

Este es el paso que decide la tarea. Los tres puntos, en orden:

```bash
ssh siteground-pruebas-lastplanner 'cd ~/www/prueba-lps.lastplanneraia.com/public_html && git pull --ff-only origin main && echo "--- 1. pull OK" && git log --name-only --diff-filter=A HEAD@{1}..HEAD -- database/migrations/ | head -5 && echo "--- 2. deteccion de migraciones OK" && echo "drift de prueba" > .drift-test && git stash push -u -m verificacion-shallow && git stash pop && rm -f .drift-test && echo "--- 3. stash OK"'
```

Esperado: los tres marcadores. El punto 2 es el que más probablemente sufra, porque
`HEAD@{1}` **lee el reflog** y el paso 2 lo acaba de vaciar: por eso este `pull` va antes, para
crear una entrada nueva. Anota este efecto — inmediatamente después de podar, y solo hasta el
siguiente `pull`, la detección de migraciones de la rutina no tiene contra qué comparar.

- [ ] **Paso 4: si algo del paso 3 falla, revertir**

```bash
ssh siteground-pruebas-lastplanner 'cd ~/www/prueba-lps.lastplanneraia.com/public_html && git fetch --unshallow origin && du -sh .git'
```

Esperado: la historia completa vuelve y `.git` regresa a ~362 MB. Si hay que llegar aquí, la tarea
se cierra como no aplicable y se anota el motivo; producción no se toca de todas formas.

- [ ] **Paso 5: verificar que el sitio de pruebas responde**

```bash
ssh siteground-pruebas-lastplanner 'curl -s -o /dev/null -w "pruebas HTTP %{http_code}\n" -k -H "Host: prueba-lps.lastplanneraia.com" https://127.0.0.1/'
```

Esperado: `HTTP 200`.

- [ ] **Paso 6: documentar el estado y el efecto sobre el reflog**

Añade al final de `docs/siteground-deploy-routine.md`, literalmente:

````markdown
## Clones shallow

`prueba-lps` usa un clon shallow desde el 2026-08-18: su `.git` pasó de 362 MB a menos de 60. Se
hizo con `git fetch --depth=1 origin main`, `git reflog expire --expire=now --all` y
`git gc --prune=now`.

**Producción sigue con la historia completa a propósito.** Espera a que un despliegue real sobre
pruebas confirme el ciclo entero, no solo el tamaño.

Se revierte con `git fetch --unshallow origin`, que devuelve la historia y el peso.

> [!CAUTION]
> **`reflog expire` deja `HEAD@{1}` sin destino hasta el siguiente `pull`.** Y `HEAD@{1}` es justo
> lo que usa el paso 5.1 para detectar migraciones nuevas
> (`git log --name-only --diff-filter=A HEAD@{1}..HEAD -- database/migrations/`). Medido el
> 2026-08-18 al podar pruebas. Consecuencia práctica: si podas un clon, haz un `pull` antes de
> fiarte de esa detección — en un servidor recién podado devolvería vacío y parecería que el
> deploy no trae migraciones.
````

- [ ] **Paso 7: commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia/.claude/worktrees/lastplanneraia-disk-quota-719c1c"
git add docs/siteground-deploy-routine.md
git commit -m "docs(deploy): prueba-lps pasa a clon shallow, produccion espera

El .git del servidor de pruebas baja de 362 MB a menos de 60. Verificado el ciclo
completo, no solo el tamano: pull --ff-only, deteccion de migraciones con
HEAD@{1}..HEAD y stash del drift.

Trampa medida: reflog expire deja HEAD@{1} sin destino hasta el siguiente pull, y
es justo lo que usa la rutina para detectar migraciones nuevas.

Produccion sigue con historia completa a proposito, hasta que un despliegue real
lo confirme. Reversible con git fetch --unshallow.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Tarea 5 — Cierre: verificar, publicar y anotar

**Archivos:**
- Modificar: `memoria/log.md` (una línea de `ingest`)

**Interfaces:**
- Consume: los resultados de las Tareas 1 a 4.
- Produce: el frente cerrado y publicado en `main`.

- [ ] **Paso 1: verificar la condición de hecho completa**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia/.claude/worktrees/lastplanneraia-disk-quota-719c1c"
du -sh docs/qa
du -sh "/Volumes/Crucial X6/Developer/lps-aia-evidencia"
for t in test_goal_close_blockers_manifest test_human_decision_matrix_coverage test_human_decision_actions_package test_human_decision_approval_checklist; do
  docker compose exec -T app php "tests/$t.php" >/dev/null 2>&1 && echo "PASS $t" || echo "FAIL $t"
done
npm run test:design-system:static
ssh siteground-pruebas-lastplanner 'du -sh ~/backups ~/www/prueba-lps.lastplanneraia.com/public_html/.git'
ssh siteground-produccion-lastplanner 'du -sh ~/backups ~/www'
```

Esperado: `docs/qa` ≤ 20 MB, el archivo externo ~282 MB, cuatro `PASS`, suite estática en verde,
`.git` de pruebas < 60 MB. **Si algo sale rojo, el frente no cierra y no hay nada que publicar.**

- [ ] **Paso 2: anotar el ingest en la wiki**

Añade una línea a `memoria/log.md` con la fecha, la operación `ingest`, qué se hizo, qué se midió y
las trampas nuevas (el `docs/qa/` ya ignorado que no impedía el rastreo; el reflog vacío tras
`gc --prune=now`).

- [ ] **Paso 3: dejar el árbol limpio**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia/.claude/worktrees/lastplanneraia-disk-quota-719c1c"
git add memoria/log.md
git commit -m "docs(memoria): ingest del frente de espacio en SiteGround"
git status --short
```

Esperado: `git status` sin salida.

- [ ] **Paso 4: traer lo que hayan publicado otras sesiones**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia/.claude/worktrees/lastplanneraia-disk-quota-719c1c"
git fetch origin
git status -sb | head -1
```

- [ ] **Paso 5: si hay divergencia, integrar**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia/.claude/worktrees/lastplanneraia-disk-quota-719c1c"
git merge origin/main
```

Los conflictos se resuelven a la vista, nunca a ciegas.

- [ ] **Paso 6: re-verificar DESPUÉS de integrar**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia/.claude/worktrees/lastplanneraia-disk-quota-719c1c"
npm run test:design-system:static
echo "codigo de salida: $?"
```

Es el paso que más se salta y el más caro: traer trabajo ajeno puede romper un verde propio sin
tocar tu diff. `AGENTS.md` documenta dos casos medidos el mismo día.

- [ ] **Paso 7: publicar, en un comando aparte**

```bash
git push origin main
```

Nunca encadenado al paso 6 con `&&` ni `;`. Un gate solo gobierna si puede **impedir** la
publicación, y encadenado ya se ejecutó.

- [ ] **Paso 8: confirmar que quedó publicado**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia/.claude/worktrees/lastplanneraia-disk-quota-719c1c"
git status -sb | head -1
```

Esperado: ni `ahead` ni `behind`.
