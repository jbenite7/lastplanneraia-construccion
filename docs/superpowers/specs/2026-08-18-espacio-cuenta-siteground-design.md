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

**Nota de lectura:** esta sección se escribió en tres pasadas del mismo día y **se conserva su
orden**, porque el recorrido explica el desenlace mejor que el resultado solo. Primero se declaró
que la spec no cerraba por falta de acceso al servidor —falso, y publicado—; después se midió el
servidor y aparecieron dos frentes ya hechos; y por último Felipe autorizó ejecutar el frente C, que
sus propias comprobaciones rechazaron. **El `## Cierre` está al final**, y ahí manda.

### Lo verificado hoy, con salida real

| Condición | Estado | Evidencia |
|---|---|---|
| 1 · `qa/evidence` ≤ 20 MB con `ARCHIVO.md` | ✅ | **15 MB** medidos; `ARCHIVO.md` presente; `git ls-files` da **0** `trace.zip` y **0** `.webm` rastreados |
| 2 · Cuatro pruebas PHP + suite estática en verde | ⚠️ **con matiz medido** | La suite estática, verde (5 corridas de `publicar.sh` hoy). Las **cuatro pruebas PHP están en rojo, y se demostró que no es por este frente** — ver abajo |
| 3 · Rutina con tar adelgazado y manifiesto | ✅ **y verificada en el servidor** | `docs/siteground-deploy-routine.md:93-110` en el documento, y los tars reales en `~/backups` — ver la tabla de la corrección |
| 4 · `.git` de `prueba-lps` < 60 MB | ❌ **no cumplida** | 366 MB, sin shallow. Es lo único que falta |
| 5 · Producción sin los archivos del frente D | ✅ **cumplida** | Verificado en el servidor — ver la tabla de la corrección |
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

### Corrección del mismo 2026-08-24, media hora después: sí hay acceso, y casi todo estaba hecho

**Una primera versión de esta sección afirmó que no había acceso SSH al servidor y que los frentes C
y D eran «imposibles» desde aquí. Era falso, y llegó a publicarse en `main` (`0a79d905`).** Se
sustituye en vez de borrarse, porque el error es instructivo y su causa vale más que la conclusión.

**Qué pasó:** se comprobó `~/.ssh/config` con `grep -i "^Host "`, salió únicamente `Host *`, y se
concluyó que los alias no existían. El archivo tiene **doce líneas `Include`** por delante, entre
ellas `Include ~/.ssh/config.d/recovered-aliases`, que es donde viven los cinco alias de SiteGround.
**Grepear los `Host` de un `ssh_config` sin resolver sus `Include` da un negativo falso**, y el
negativo era cómodo: explicaba el estado parcial de la spec sin más trabajo. Lo desmintió Felipe en
una línea.

**Qué NO se dañó:** nada. El error era de lectura, no de escritura — no se tocó ningún servidor bajo
la premisa equivocada, y lo publicado eran tres documentos, corregidos aquí.

**Verificado después, con salida real:** las dos llaves existen
(`lps_siteground_deploy`, `siteground_pruebas_id_ed25519`), y
`ssh siteground-pruebas-lastplanner` conecta y devuelve `/home/customer`. Pruebas y producción son
la misma cuenta (`u2440-8uoflwe1kgey`), como ya decía la rutina.

### El estado real de los cuatro frentes, medido en el servidor

| Condición | Estado | Evidencia medida hoy en el servidor |
|---|---|---|
| 3 · Tar de ~45 MB con manifiesto, rotado a 3 | ✅ **cumplida, y mejor de lo estimado** | Los tars nuevos pesan **5,1–6,7 MB**, no 45. El último tar viejo, del 2026-08-13, pesa **687 MB**: la comparación en la misma carpeta es 687 MB → 6,5 MB. Hay **5 `.manifest.txt`**, y la rotación deja **exactamente 3 por sitio** (`lastplanneraia`: 13-ago y dos del 20-ago; `prueba-lps`: 18-ago y dos del 20-ago) |
| 4 · `.git` de `prueba-lps` < 60 MB | ❌ **no cumplida — es lo único que falta** | **366 MB**, y el archivo `.git/shallow` tiene 0 líneas: el clon no es shallow. **El frente C nunca se ejecutó** |
| 5 · Producción sin los archivos del frente D | ✅ **cumplida** | Los cuatro del webroot (`test_debug_std.php`, `test_log.php`, `index.html.bak-20260327-203406`, `.maintenance.disabled-20260704-221448`) ya no están; **0** `dump_proyectos_seleccionados_*.sql` en el home; y `2026_MASTER_FUSION.sql` está **en `~/backups/`** — movido, no borrado, exactamente como mandaba esta spec |

**Hallazgo no buscado: el drift de producción ya no existe.** Los siete `.bak` de
`indicadores.view.php` que esta spec mandaba conservar **ya no están**, y `git status --porcelain`
en el webroot de producción sale **vacío**: el árbol está limpio. Eso cierra de paso el pendiente
de drift residual que la Tarea 2 de
`docs/superpowers/plans/2026-08-24-p5-cierre-hasta-produccion.md` seguía pidiendo resolver
«confirmando con Felipe antes de borrar». **Quién los retiró y cuándo no se determinó** — se dice en
vez de suponerlo; el candidato natural es el despliegue del 2026-08-20, que dejó los dos tars de esa
fecha.

### Frente C · ejecutado el 2026-08-24, y **descartado por su propia verificación**

Autorizado por Felipe y ejecutado sobre `prueba-lps` ese mismo día. **Las comprobaciones lo
rechazaron, y eso no es un fracaso: es el procedimiento haciendo exactamente lo que se diseñó para
hacer.** Esta spec escribió tres comprobaciones «antes de darlo por bueno, no solo el tamaño», y
dijo cuál decidía. Decidió.

**Estado previo, capturado antes de tocar nada:** `HEAD=6fa3cff1`, rama `main`, `.git` de 366 MB,
26 585 objetos, sin shallow, árbol limpio.

Ejecutado `git fetch --depth=1 origin main` + `reflog expire --expire=now --all` +
`gc --prune=now`, los tres con `rc=0`. Y entonces:

| Comprobación | Con clon shallow | Con historia completa |
|---|---|---|
| 1 · `git pull --ff-only origin main` | ❌ **`rc=128`** — «fatal: Not possible to fast-forward, aborting» | ✅ `rc=0` |
| 2 · `git log --diff-filter=A HEAD@{1}..HEAD -- database/migrations/` *(la que decide)* | ❌ **ninguna detectada** | ✅ detecta `20260819_sembrar_linea_base_contractual.sql` |
| 3 · `git stash push -u` guarda y restaura drift | no llegó a probarse | ✅ guarda y restaura, árbol limpio después |

**El primer punto es el veredicto:** `git pull --ff-only` es **el comando del que depende la rutina
de despliegue** (`docs/siteground-deploy-routine.md`). Un clon shallow lo inutiliza — sin la
historia intermedia, git no puede calcular el avance rápido entre el `HEAD` local y un
`origin/main` truncado. No es que el despliegue se degrade: **deja de poder ejecutarse**.

El segundo lo confirma por el otro lado, y es el contraste el que prueba la causa: el mismo comando,
sobre el mismo rango, no ve la migración con shallow y sí la ve con historia completa.

**Un detalle que conviene saber si alguien lo reintenta:** tras el `gc`, el `.git` **seguía en
366 MB**. Los 326 MB prometidos no aparecen con `--depth=1` sobre un clon ya completo, porque la
historia local sigue siendo alcanzable desde `HEAD` y `gc` no poda lo alcanzable. Habría que mover
`HEAD` al commit truncado — y eso es justo lo que el punto 1 impide. **El ahorro no solo rompe el
despliegue: además no se materializa por el camino que esta spec describía.**

**Revertido de inmediato** con `git fetch --unshallow` (`rc=0`), y comprobado que el servidor quedó
sano, no solo que el comando devolviera cero: `pull --ff-only` en `rc=0`, `.git/shallow` ausente,
árbol con **0** cambios sueltos, rama `main`.

**Efecto colateral, no buscado y benigno:** el `pull` de la verificación trajo los **213 commits**
que `prueba-lps` llevaba de atraso desde el 2026-08-20. El servidor de pruebas quedó al día en
`1a3372f6`.

**Decisión que deja este resultado:** el frente C **no se reintenta**. El ahorro que perseguía
(~326 MB en un servidor de pruebas) no compensa inutilizar el despliegue, y el propio spec ya lo
había puesto en su tabla de riesgos con la mitigación correcta. La categoría a la que pertenece está
en «Lo que se descartó, y por qué», junto a `git-filter-repo`.

## Cierre

**Ejecutada.** Los cuatro frentes tienen desenlace medido, y el descarte del C es un resultado, no
una omisión:

| Frente | Desenlace |
|---|---|
| A · el repo deja de cargar binarios | ✅ ejecutado — `qa/evidence` en **15 MB**, `ARCHIVO.md` escrito, **0** `trace.zip` y **0** `.webm` rastreados |
| B · el tar guarda lo irremplazable | ✅ ejecutado y verificado en el servidor — tars de **5,1–6,7 MB** contra los **687 MB** del último viejo, 5 manifiestos, rotación exacta a **3 por sitio** |
| C · clones shallow en pruebas | ⛔ **ejecutado y descartado por medición** — rompe `pull --ff-only`, que es el comando del despliegue. Revertido y servidor sano |
| D · basura suelta en producción | ✅ ejecutado — los cuatro archivos fuera del webroot, **0** dumps en el home, `2026_MASTER_FUSION.sql` **movido** a `~/backups/` y no borrado |

**El objetivo de la spec se cumplió, y por el frente que importaba.** El criterio que la ordenaba
—«un respaldo guarda lo que nadie más puede reponer»— produjo su ahorro en el frente B: donde antes
3 tarballs × 2 sitios pesaban ~4,1 GB, hoy pesan ~39 MB. El frente C perseguía 326 MB adicionales en
un servidor de pruebas y resultó incompatible con el despliegue; el D liberó ~25 MB y quitó del
webroot de producción dos archivos de depuración que solo tapaba una regla de `.htaccess`.

**Condición de hecho, contrastada:** se cumplen la 1, la 2 *(con el matiz de las cuatro pruebas PHP
rojas por causa ajena, demostrada arriba)*, la 3, la 5 y la 6. **La 4 no se cumple y no se
perseguirá**: pedía el `.git` de `prueba-lps` bajo 60 MB *«con los tres puntos de verificación del
frente C comprobados»* — se comprobaron, y dos fallaron. La condición se cumplió en su intención,
que era no dar por bueno el tamaño sin verificar el ciclo.
