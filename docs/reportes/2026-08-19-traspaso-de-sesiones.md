---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-19
areas: [proceso, qa, docker]
tags: [leer-antes-de-tocar]
fuente: traspaso de las sesiones vivas, ordenado por Felipe el 2026-08-19; cada apartado cita a la sesión que lo relató
resumen: Lo que sabían las sesiones de 2026-08-19 y no estaba en ningún archivo — ramas sin publicar, trampas medidas y por qué el gate de rutas dejó de existir.
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

## El recálculo de estados YA SE APLICÓ, y está sin publicar

`aa965bf5`, a las 13:40, en `claude/bold-neumann-485f23`. **40.664 filas migradas sobre la base de
desarrollo**, con acta en `goals/apply-recalculo-estados/acta-del-apply.md`. Se hizo como pedía el
procedimiento: autorización directa de Felipe además de la relatada, ventana de base exclusiva,
dry-run coincidiendo exactamente con el informe autorizado, y reconciliación exacta contra el
respaldo.

**El detalle que vale la pena copiar:** el respaldo probado horas antes **ya no cubría la base** —
`origen=65.565 · respaldo=65.557 · sin_respaldo=8`. Ocho filas nuevas habían entrado entre la
prueba y el apply. Se rehízo el respaldo y se volvió a probar la restauración antes de seguir. Un
respaldo verificado no es un respaldo vigente: **entre verificarlo y usarlo, la base sigue viva.**

Cualquier página que diga que este apply está «autorizado y sin ejecutar» quedó caduca el 2026-08-19
a las 13:40.

## El hook de pre-commit es inerte hasta que se registra a mano

`11150fee` trae `scripts/hooks/pre-commit-tests.sh`, que es **solo la lógica**. Lo que lo activa es
un bloque `PreToolUse` en `.claude/settings.json`, y `.claude/` está en `.gitignore`. El reparto es
deliberado —la lógica viaja, la configuración personal no— pero tiene un filo:

**quien recoja ese commit se encuentra un script que nunca se ejecuta, y puede creer que tiene la
red puesta cuando no la tiene.** Hoy solo está activo en el worktree que lo escribió; en la raíz del
repo, no. Activarlo en la raíz cambia la configuración de las sesiones que estén corriendo allí, así
que el orden importa.

## El gate de rutas no está caído: se retiró a propósito

**Esta sección decía «caído» y era el encuadre equivocado.** Corregido el 2026-08-19 tras el aviso
de una sesión de `loop-engineering`, verificado por mi cuenta en el repo del plugin:

```
$ git -C .../loop-engineering log -1 --format='%h %ad %s' c275c1d
c275c1d 12:13 chore!: barrido a base de evidencia — se retira el motor y CAS completo
$ git -C .../loop-engineering branch --list retiro-cas
  retiro-cas
```

Commit marcado como ruptura (`chore!`), a las 12:13. **CAS no falló: se retiró como decisión de
producto**, y el código sobrevive en el historial y en la rama `retiro-cas`. El paquete instalado
(`1.0.0-alpha.1`) es fiel a ese repo; no le falta nada.

**Lo que confundió, y es un defecto real aunque no el que se creía:** la instalación copia el
directorio de trabajo, no lo versionado, y `.claude/worktrees/` no está en el `.gitignore` del
plugin. Así que dentro del paquete quedan worktrees viejos que **todavía contienen `cas-frente.sh`**.
De ahí la observación de que «el módulo solo sobrevive dentro de un worktree interno del plugin»:
es un fósil de desarrollo empaquetado por error, no la ubicación nueva de la pieza.

**Qué significa en la práctica.** Las sesiones no pueden declarar frentes porque **el mecanismo ya
no existe**, no porque esté roto. No hay arreglo que esperar. La coordinación manual no es un puente
temporal hacia un CAS restaurado: hasta nueva orden es el único mecanismo. Si hace falta registro de
frentes, se plantea como pedido de producto al usuario, no como bug.

**Y aun así, el hecho que motivó todo esto sigue en pie:** hoy hubo dos choques —el contenedor
compartido y una triple edición de la wiki—, y **los dos los destapó `git merge` o un mensaje entre
sesiones, ninguno un gate**. Que la pieza se retirara a propósito explica la causa; no cambia la
consecuencia.

**Un dato relatado que NO pude confirmar:** se me dijo que el plugin estaba deshabilitado entero
desde hacía dos horas. Medido ahora en `~/.claude/settings.json`,
`"loop-engineering@loop-engineering"` está en **`true`**. O se reactivó entre medias, o la
observación era de otra cosa. Se deja anotado sin resolver en vez de repetirlo.

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
