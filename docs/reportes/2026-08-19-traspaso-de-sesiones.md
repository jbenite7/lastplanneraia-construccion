---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-19
areas: [proceso, qa, docker]
tags: [leer-antes-de-tocar]
fuente: traspaso de las sesiones vivas, ordenado por Felipe el 2026-08-19; cada apartado cita a la sesión que lo relató
resumen: Lo que sabían las sesiones de 2026-08-19 y no estaba en ningún archivo — ramas sin publicar, trampas medidas y el gate de rutas caído toda la jornada.
---

# Traspaso de sesiones — 2026-08-19

Felipe ordenó consolidar todas las sesiones en una sola. Este archivo existe porque **lo que se
pierde al cerrar una sesión no es el código —eso está en git— sino lo que solo vivía en su canal**:
decisiones que él dio por chat, callejones ya medidos y trampas que costaron una vuelta.

Ocho sesiones vivas; **dos llevaban frente de este repo**. Las otras seis trabajaban en `~/.claude`,
en `loop-engineering` y en `IG_Extractor`.

## Ramas con trabajo sin publicar — no borrar sin mirar

| Rama | SHA | Qué lleva |
|---|---|---|
| `claude/recursing-shtern-472554` | `b13f879f` | **La fusión de las tres versiones de la wiki LLM**, ya hecha y verde. Ver abajo |
| `claude/distracted-heisenberg-ba9f66` | `11150fee` | Hook de proyecto que corre la suite `puro` antes de cada commit. Felipe autorizó crearlo |
| `prueba/condicion-de-hecho` | `c15a5c6b` | `TableResolverTest` enganchado al runner, **y el arreglo de un bug real del runner** |

`prueba/condicion-de-hecho` tiene además worktree propio en `~/Developer/lps-aia-wt-cdh`. Nació como
prueba de un agente y el trabajo que quedó es real: `tests/TableResolverTest.php` **existía y el
runner nunca lo ejecutaba** —no cumplía ninguno de los dos patrones que `scripts/run-php-tests.php`
recoge—, así que era cobertura que no protegía nada.

**El bug del runner que destapó de paso:** PHPUnit 12 **no separa `--group=a,b` por comas**
(`Builder.php:521-526` hace append literal), así que el string `"a,b"` nunca casa con
`#[Group('a')]`. El runner **no podía correr dos niveles a la vez**. Estaba latente porque todos los
`tests/unit/*Test.php` eran nivel `puro`; el primer test de otro nivel lo destapa.

## El gate de rutas lleva toda la jornada caído

Medido dos veces, por dos sesiones distintas:

```
$ cat .claude/cas-root
/Users/felipebenitez/.claude/plugins/cache/loop-engineering/loop-engineering/0.2.0/cas

$ ls /Users/felipebenitez/.claude/plugins/cache/loop-engineering/
No such file or directory
```

No es que falte el módulo CAS: **el directorio del plugin desapareció entero durante la jornada.**
Empezó existiendo en `0.3.0` sin `cas/`, el caché de `0.2.0` se esfumó a media tarde, y al cierre no
queda ninguno. Con él caen el **gate de rutas**, el de presupuesto y el de push.

**Por qué importa exactamente hoy:** el gate de rutas es el que avisa cuando dos sesiones declaran
los mismos archivos. Hoy hubo dos choques —el contenedor compartido y una triple edición de la
wiki—, y **los dos los destapó `git merge` o un mensaje entre sesiones, ninguno un gate**. En el de
la wiki, el choque salió a la luz hora y media después de producirse.

## Trampas medidas, para no volver a pagarlas

**`CLAUDE.md` manda enlazar un `.env` que ya no existe.** Dice
`ln -s "/Volumes/Crucial X6/Developer/lps-aia/.env" .env` y esa ruta desapareció con la mudanza del
2026-08-18 a `~/Developer/lps-aia`. Verificado: el origen no existe, el destino sí. **El symlink se
crea sin error y apunta a la nada**, así que `docker compose` resuelve `DB_NAME` y `DB_PASS` a
cadena vacía y los tests mueren con «Access denied for user ''». Cuesta una vuelta entera. El enlace
correcto es desde `~/Developer/lps-aia/.env`.

**Los worktrees nacen sin `vendor/`.** Los tests mueren con un fatal de autoload que parece un bug
del test y no lo es: hay que correr `composer install` **dentro del contenedor**. Y ojo —
**enlazar `vendor/` desde la raíz no sirve**, porque el destino del enlace queda fuera de lo que el
contenedor monta. Con `.env` el enlace sí funciona; no son casos iguales.

**El contenedor `app` persistente monta un solo worktree, por ruta absoluta**, y varias veces era el
de otra sesión. Un verde medido así no dice nada de tu árbol. Para verificar, contenedor efímero:
`LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app …`. Si reapuntás el persistente,
devolvelo a la raíz al terminar.

**`cip` no tiene la misma clave primaria que `cic`.** `cic` es `(project_id, Id)`; `cip` es solo
`(Id)`, global. Calcular la secuencia de `cip` por proyecto da «Duplicate entry», porque va sobre
toda la tabla.

**`${PIPESTATUS[0]}` bajo zsh devuelve vacío** — es `pipestatus` y va 1-indexado. Ya está en
`AGENTS.md`, y aun así es la trampa que hizo publicar sin gate tres veces. Su pariente: **una
expansión sin comillas no divide palabras en zsh**, lo que produjo un `git log` con 0 commits que
parecía hallazgo y era error de shell.

**`--no-merges` a secas está mal** para contar trabajo: pierde las resoluciones de conflicto, que
son contenido que no existe en ningún otro commit. Se distinguen con `--cc`.

**`publicar.sh` solo enseña 4 líneas de un gate en rojo.** Si un mensaje de error importa, tiene que
caber ahí — por eso el lint imprime el recuento primero y los hallazgos al final.

## Un bug de producción que salió de rebote

`calculatePACConsolidado()` en `ReportProcessor` multiplicaba texto (`'NA' * '0.000'`) y bajo PHP 8
tumbaba `updateCICProyectos()` **entero**, no solo el test. Habría reventado igual en producción.
Arreglado con su prueba unitaria en `c7c34cc2`, ya publicado.

## Decisiones de Felipe que solo vivían en un canal

Ninguna de estas estaba en `decisiones/` al escribirse este archivo:

- Las seis tandas de la wiki v2, aprobadas una por una según se le presentaban.
- **Tanda 4 sin plugins de comunidad** — los decide aparte. Sigue pendiente.
- **`moc` sale del vocabulario de tags**; `tipo: mapa` significa MOC.
- **`resumen` obligatorio en fuentes**, con cascada de cuatro respaldos.
- **El gate de publicación bloquea por la forma de la wiki**, avisando a las sesiones vivas en el
  mismo movimiento.
- **Los tres archivos congelados por `goal-provenance.json` se quedan sin frontmatter**, ratificado.
- **Frontmatter fusionado** (esquema v2 + los cinco campos de `llm-wiki`).
- **«Crearlos de verdad y migrar»**, descartando expresamente «crearlos como punteros» — la decisión
  que desempató la triple edición de la wiki.
- El hook de pre-commit: nivel `puro` (3,3 s) y no `db` (17,4 s), con alcance a **toda** sesión de
  Claude y no solo a subagentes.

## Cómo se resolvió la triple edición de la wiki

Tres versiones del mismo trabajo, ninguna en borrador:

| Hora | Commit | Diseño |
|---|---|---|
| 14:37 | `892b1287` | Los 5 archivos como **nuevos**; las páginas de `memoria/` se quedan y se enlazan |
| 15:01 | `7e2f3ef9` | Lo anterior, más la wiki al día — y **prosa argumentando la coexistencia** |
| 15:57 | `d72ca935` → `b13f879f` | **Migración**: `git mv` de las páginas a la raíz, sin duplicar fuentes |

Ganó la migración, porque es lo que Felipe decidió a las ~15:30. Las otras dos no fueron errores de
disciplina: **fueron encargos suyos anteriores a esa decisión**, hechos por sesiones que no tenían
cómo saber una de la otra.

`b13f879f` es la fusión ya hecha y verificada (`npm run test:wiki` RC=0 en estricto, 159 páginas,
443 de 446 fuentes), **sin publicar**. Conserva el contenido operativo de las tres versiones y
**borra a propósito** —no reacomoda— la prosa que defendía la coexistencia: con la migración
decidida, ese texto dejaba al repo discutiendo consigo mismo.

**Detalle que muerde si alguien rehace la fusión desde cero:** el merge arrastra enlaces a las rutas
viejas en `memoria/goals/estado.md` y `memoria/log.md`. En `b13f879f` ya están reapuntados.
