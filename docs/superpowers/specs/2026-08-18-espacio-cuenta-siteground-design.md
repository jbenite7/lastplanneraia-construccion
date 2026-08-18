# Espacio de la cuenta de SiteGround: dejar de guardar lo que git ya guarda

- **Fecha:** 2026-08-18
- **Estado:** diseño aprobado
- **Alcance:** cuatro frentes (A, B, C, D). El frente C llega solo hasta `prueba-lps`; producción
  espera a que un despliegue real lo confirme.

## El problema

El 2026-08-18 SiteGround deshabilitó Site Tools de `lastplanneraia.com` por cuota de disco. La
limpieza de ese mismo día liberó 14.4 GB borrando respaldos viejos, y el bloqueo se levantó — pero
**eso fue el paliativo, no el arreglo**. La causa que llenó la cuenta sigue intacta y vuelve a
llenarla.

La causa medida: cada despliegue crea un `tar.gz` de `public_html` entero, y `public_html` pesa
759 MB porque el repositorio carga 370 MB de historia de git y 298 MB de evidencia binaria de QA.
El respaldo, por tanto, duplica en disco lo que git ya conserva. En julio esos tarballs pesaban
540 MB; en agosto, 687 MB. Crecen solos.

## Lo que se midió (2026-08-18, worktree `lastplanneraia-disk-quota-719c1c`)

### La cuenta, antes y después del paliativo

| Momento | `~/backups` | Cuenta total | Cuota reportada por SiteGround |
|---|---|---|---|
| Antes de limpiar | 19 GB (76 entradas) | ~21 GB | 40.90% |
| Después de limpiar | 4.6 GB (29 entradas) | ~6.7 GB | 13.32% |

La cifra de SiteGround se movió acorde, lo que confirma que su medición es en vivo. El bloqueo, en
cambio, siguió puesto un rato después de que el porcentaje ya marcara 13.32%: no se levanta en el
mismo momento en que baja el uso.

### De qué está hecho el peso

| Qué | Tamaño | ¿Lo repone git o Composer? |
|---|---|---|
| `.git` en cada servidor | 365 MB | Sí — es una copia de `origin` |
| `docs/qa/evidence` | 297 MB | Está *dentro* de git, y por eso lo infla |
| `vendor/` | 9.2 MB | Sí — `composer install` |
| `docs`, `tests`, `goals`, `e2e`, `.github` | ~334 MB | Sí — versionado |
| **`public/storage`, `.env`, drift `.bak`** | **~4 MB** | **No. Esto es lo único irremplazable.** |

### Dentro de `docs/qa/evidence`

| Tipo | Nº | Peso | Rastreado en git |
|---|---|---|---|
| `trace.zip` | 20 | 259 MB | Sí |
| `.webm` | 26 | 23 MB | Sí, pese a estar en `.gitignore:212` |
| `.png` + `.json` + `.md` | 95 | 15 MB | Sí |

Los 46 binarios son el 95% de la carpeta y todos son del 1 al 4 de julio de 2026.

### Comprobaciones hechas antes de decidir que se pueden mover

- **Ningún gate los ancla por hash.** `docs/design-system/closeout-evidence.json` no menciona
  `qa/evidence` (0 coincidencias). Los dos `.sha256` que existen en la carpeta protegen archivos
  `.sql`, no traces. Esta comprobación no es opcional: `AGENTS.md` documenta que un contrato que
  fija archivos por hash ya dejó una suite en 6/8 sin que lo viera quien hizo el cambio.
- **Ningún script del repo los referencia.** `scripts/`, `package.json` y `.github/` no nombran
  `qa/evidence`.
- **Las cuatro pruebas PHP que sí dependen de la carpeta** (`test_goal_close_blockers_manifest`,
  `test_human_decision_matrix_coverage`, `test_human_decision_actions_package`,
  `test_human_decision_approval_checklist`) apuntan únicamente a
  `catalog-goal-audit-20260702/` y piden `.md` y `.json`. Ninguna toca un trace ni un video.
- **Lo irremplazable de producción se midió, no se supuso**, con
  `git status --porcelain --ignored` dentro de `public_html`: `public/storage/` (856 KB de
  `cortesProgramacion`, ~3 MB de xlsx de liberación de restricciones, 20 KB de
  `compromisosSemana`), el `.env`, y siete `.bak` de `views/indicadores/indicadores.view.php` del
  23 de julio.

## El criterio

Uno solo, y ordena los cuatro frentes: **un respaldo guarda lo que nadie más puede reponer.**
Todo lo que git o Composer reconstruyen no se respalda, no se empaqueta y no se versiona en
binario.

## Frente A — El repositorio deja de cargar binarios

Los 20 `trace.zip` y 26 `.webm` se copian a `/Volumes/Crucial X6/Developer/lps-aia-evidencia/`
conservando la estructura de `docs/qa/evidence/`, y se sacan del repo con `git rm`.

**No hay que tocar `.gitignore`.** Al escribir el plan se comprobó que la línea 210 ya ignora
`docs/qa/` entera, y la 211 repite el caso de `.webm`. Los binarios seguían dentro por un motivo
distinto del que supuse al diseñar: git no deja de rastrear lo que ya rastreaba, y las dos reglas
se añadieron después de commitearlos. Añadir una tercera no habría cambiado nada.

En `docs/qa/evidence/ARCHIVO.md` queda el índice de lo movido: qué corrida, qué archivo, cuánto
pesaba, su `sha256` y dónde está ahora. Sin ese índice el archivado es una pérdida con pasos
extra.

Los `.md`, `.json` y `.png` se quedan: son 15 MB y de ellos dependen las cuatro pruebas.

**No se destruye nada.** La subida del archivo a Drive queda como tarea aparte, fuera de este
frente.

**Verificación:** las cuatro pruebas PHP en verde después del `git rm`, y
`npm run test:design-system:static` en verde — es el gate que ancla artefactos por hash y el que
avisaría si algo dependía de lo movido sin que lo hubiéramos visto.

## Frente B — El tar de pre-deploy guarda lo irremplazable

Se reescribe el paso 3 de `docs/siteground-deploy-routine.md`. El `tar` excluye `.git`, `vendor`,
`node_modules`, `docs`, `tests`, `goals`, `e2e`, `.github` y `memoria`. Todo lo demás entra,
incluidos `.env`, `public/storage` y los `.bak` de drift. **De ~690 MB a ~45 MB.**

**El manifiesto no es un adorno.** Al excluir `.git`, el tarball por sí solo ya no sabe de qué
commit salió, y un respaldo que no se puede situar en la historia no es un respaldo. Cada tar pasa
a ir acompañado de un `.manifest.txt` con el SHA de `HEAD`, la rama y la fecha. Restaurar es
`git clone` en ese SHA y descomprimir el tar encima.

La rotación va dentro del mismo comando de respaldo: crea el nuevo, borra los sobrantes, deja 3 por
sitio. Se descartó el cron: un proceso invisible que hay que recordar que existe es peor que un
paso que se ejecuta justo cuando se crea el respaldo.

Con 45 MB × 3 × 2 sitios, `~/backups` se estabiliza en ~300 MB contando los dumps de base.

**Verificación:** crear un respaldo con el comando nuevo en `prueba-lps`, comprobar que el tar
contiene `.env` y `public/storage` completos (`tar -tzf | grep`), y que la rotación deja
exactamente 3.

## Frente C — Clones shallow, solo en pruebas

`git fetch --depth=1` seguido de `reflog expire` y `gc --prune=now` en
`~/www/prueba-lps.lastplanneraia.com/public_html`. El `.git` baja de ~362 MB a ~40 MB.

Se verifica el ciclo entero antes de darlo por bueno, no solo el tamaño:

1. `git pull --ff-only origin main` trae un commit nuevo.
2. `git log --name-only --diff-filter=A HEAD@{1}..HEAD -- database/migrations/` sigue detectando
   migraciones nuevas. La rutina depende de este comando y **lee historia**: es el que más
   probablemente sufra con un clon shallow, y por eso es la prueba que decide.
3. `git stash push -u` guarda drift del servidor y lo restaura.

**Producción no se toca en este frente.** Espera a que un despliegue real sobre pruebas confirme
los tres puntos.

**Reversible:** `git fetch --unshallow` devuelve la historia completa.

## Frente D — Basura suelta en producción

Se borran del webroot `test_debug_std.php`, `test_log.php`, `index.html.bak-20260327-203406` y
`.maintenance.disabled-20260704-221448`, y del home los `dump_proyectos_seleccionados_*.sql`
(~14 MB).

`2026_MASTER_FUSION.sql` (8.8 MB) **se mueve a `~/backups/`, no se borra**: no está en git en
ninguna parte y es un script de fusión de base.

Los siete `.bak` de `views/indicadores/indicadores.view.php` **se quedan**. Son drift real de
producción del 23 de julio y decidir su destino es otra conversación, no un efecto colateral de una
limpieza de espacio.

Este frente pesa ~25 MB, que es poco. Se hace igual porque `test_debug_std.php` y `test_log.php` en
el webroot de producción hoy solo están tapados por una regla del `.htaccess` (verificado: 403), y
esa protección es un archivo de configuración de distancia.

## Lo que se descartó, y por qué

- **Reescribir la historia con `git-filter-repo`** para expulsar los 259 MB de traces del pack.
  Sería el mayor ahorro en bruto y baja el `.git` en todos los clones a la vez. Se descarta:
  `AGENTS.md` prohíbe reescribir historia publicada, y hay varias sesiones empujando a
  `origin/main` — romperle el repo a otra sesión no compensa 300 MB.
- **Un cron de rotación.** Ver frente B.
- **Borrar los traces sin archivarlos.** La wiki documenta (2026-08-14) que un `trace.zip` sirvió
  para diagnosticar un borrado silencioso en `ProgramChangeDetector` que no se veía a ciegas. Como
  categoría valen; lo que no vale es guardarlos dentro de git.
- **Shallow en producción en este mismo frente.** Es el único cambio que toca el mecanismo de
  despliegue y no hay urgencia de cuota que lo justifique.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| Algo dependía de un trace sin que lo detectáramos | Se archivan, no se destruyen; y siguen en la historia de git |
| El tar adelgazado deja fuera algo irremplazable | La lista de exclusiones sale de `git status --ignored` medido en producción, no de una suposición; se verifica el contenido del tar antes de confiar en él |
| El clon shallow rompe la detección de migraciones | Es el punto 2 de la verificación del frente C, y es reversible con `--unshallow` |
| Producción queda tocada sin poder volver atrás | El frente D solo borra archivos que git no tiene y que no sirven a nadie; el `.sql` se mueve, no se borra |

## Condición de hecho

1. `docs/qa/evidence` pesa ≤ 20 MB en el repo, con `ARCHIVO.md` escrito, y los 282 MB copiados y
   verificados por `sha256` en el disco local.
2. Las cuatro pruebas PHP de evidencia y `npm run test:design-system:static` en verde.
3. `docs/siteground-deploy-routine.md` paso 3 produce un tar de ~45 MB con manifiesto, verificado
   en `prueba-lps`, y rota a 3.
4. El `.git` de `prueba-lps` por debajo de 60 MB, con los tres puntos de verificación del frente C
   comprobados.
5. Producción sin los archivos del frente D y con `2026_MASTER_FUSION.sql` movido.
6. Todo publicado en `main` según el gate de cierre de frente de `AGENTS.md`.
