---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-18
areas: [deploy]
fuente: docs/superpowers/specs/2026-08-18-espacio-cuenta-siteground-design.md
resumen: Espacio de la cuenta de SiteGround: dejar de guardar lo que git ya guarda
---

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

## Estado medido — 2026-08-24

**Esta spec NO cierra, y a propósito no lleva `## Cierre`:** en este repo la presencia de esa
sección es el hecho del que derivan el mapa de estado y el aviso de fase previa, así que ponerla sin
cierre real haría mentir a los dos a la vez. Sigue **parcial**, con la mitad ejecutada y la otra
mitad bloqueada por una causa material, no por falta de trabajo.

### Lo verificado hoy, con salida real

| Condición | Estado | Evidencia |
|---|---|---|
| 1 · `qa/evidence` ≤ 20 MB con `ARCHIVO.md` | ✅ | **15 MB** medidos; `ARCHIVO.md` presente; `git ls-files` da **0** `trace.zip` y **0** `.webm` rastreados |
| 2 · Cuatro pruebas PHP + suite estática en verde | ⚠️ **con matiz medido** | La suite estática, verde (5 corridas de `publicar.sh` hoy). Las **cuatro pruebas PHP están en rojo, y se demostró que no es por este frente** — ver abajo |
| 3 · Rutina con tar adelgazado y manifiesto | ✅ *en el documento* | `docs/siteground-deploy-routine.md:93-110`: las diez exclusiones y el `.manifest.txt`. **Su verificación en `prueba-lps` no se pudo hacer** — ver bloqueo |
| 4 · `.git` de `prueba-lps` < 60 MB | ⛔ no medible | Requiere el servidor |
| 5 · Producción sin los archivos del frente D | ⛔ no medible | Requiere el servidor |
| 6 · Publicado en `main` | ✅ *para A y B* | Ambos en el árbol publicado |

### Las cuatro pruebas rojas no son de este frente, y se probó

`test_goal_close_blockers_manifest`, `test_human_decision_matrix_coverage`,
`test_human_decision_actions_package` y `test_human_decision_approval_checklist` dan **RC=1** hoy,
corridas sobre el contenedor con el paso 0 comprobado (`/Users/felipebenitez/Developer/lps-aia ->
/var/www/html`, el árbol correcto).

No las rompió el frente A, y no es una suposición:

- El fallo real es «el catálogo mantiene exactamente las familias con revisión obligatoria
  vigentes» — un catálogo en base de datos. **Cero menciones de `evidence`** en toda la salida.
- La carpeta que estas pruebas leen, `catalog-goal-audit-20260702/`, conserva sus **6 `.md` y
  2 `.json`** intactos, y contiene **0** `.zip` y **0** `.webm`: lo que el frente A movió no estaba
  ahí.
- `git log --diff-filter=D` desde el 2026-08-17 sobre los `.md` y `.json` de esa carpeta: **vacío**.
  El frente A no borró ninguno.

Las cuatro son de nivel `datos-proyecto`, el más alto, y **no corren en CI** (`ci.yml` solo ejecuta
`--nivel=puro` y `--nivel=http`), así que solo se ven al correrlas a mano. Aparecen en la lista de
[[memoria/trampas/suite-php-rojos-preexistentes]], con el aviso de que esa lista es de una rama y no
sirve como línea base de `main`. **Queda declarado como límite:** se probó que no son de este
frente; **no** se diagnosticó de qué sí lo son.

### El bloqueo, medido: no hay acceso al servidor desde esta máquina

Los frentes C y D no están «pendientes de que alguien los haga». **No se pueden ejecutar desde
aquí**, y la causa es concreta:

`docs/siteground-deploy-routine.md:23-24` da por sentados dos alias SSH,
`siteground-pruebas-lastplanner` y `siteground-produccion-lastplanner`. **Ninguno de los dos existe
en `~/.ssh/config` de esta máquina**, que solo declara `Host *`. Sin ellos no hay forma de mirar
`prueba-lps` ni producción, y por tanto tampoco de verificar la condición 3 en el servidor, que es
donde la spec la exige.

Es el mismo tipo de resto que dejó la mudanza del repositorio del 2026-08-18 —igual que el `.env`
enlazado a una ruta del disco viejo que documenta `CLAUDE.md`—, y conviene tratarlo como tal: puede
ser que la configuración se quedara atrás y no que nunca existiera.

**Y aunque hubiera acceso, el frente D no se ejecutaría sin más:** borra archivos del webroot de
producción. `AGENTS.md` lo exige explícitamente y no hay atajo — una publicación aprobada no
autoriza limpiar drift, y este cierre no concede esa autorización.

### Qué falta, y de quién depende

| Frente | Qué falta | Quién puede |
|---|---|---|
| B | Verificar el tar nuevo en `prueba-lps`: que contenga `.env` y `public/storage`, y que la rotación deje 3 | Cualquiera con acceso SSH |
| C | El clon shallow y sus **tres** comprobaciones — la que decide es que `git log --diff-filter=A` siga detectando migraciones nuevas, porque lee historia | Cualquiera con acceso SSH. Reversible con `--unshallow` |
| D | Borrar 4 archivos del webroot y los dumps del home; **mover** `2026_MASTER_FUSION.sql`, no borrarlo | **Solo Felipe**, con autorización explícita en el momento |

Los siete `.bak` de `indicadores.view.php` **se quedan**, como decidió esta misma spec: son drift
real de producción y su destino es otra conversación. El mismo pendiente lo recoge la Tarea 2 de
`docs/superpowers/plans/2026-08-24-p5-cierre-hasta-produccion.md`, que también manda confirmar con
Felipe antes de borrar.
