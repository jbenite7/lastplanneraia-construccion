---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-24
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-24-p1-desague-y-consolidacion.md
resumen: "P1 · Publicar las tres ramas con trabajo terminado sin publicar, resolver la colisión de la baseline 0.4.0, rescatar linea-base-contractual y podar los worktrees residuales"
---

# P1 · Desagüe y consolidación de ramas

> **For agentic workers:** REQUIRED SUB-SKILL: `superpowers:executing-plans`. Este plan **no se
> reparte entre subagentes**: sus tareas se turnan el mismo contenedor compartido y publicar en
> paralelo es exactamente el fallo medido el 2026-08-18.

**Spec:** [[docs/superpowers/specs/2026-08-24-estado-consolidado-del-repo-design]]

> **EJECUTADO EN SU MAYOR PARTE el 2026-08-24**, por orden directa de Felipe: «mergeemos todas las
> ramas y worktrees en main, luego borra todas las ramas y worktrees. Deja main limpio». Se
> consolidaron **trece** ramas —no las tres previstas— y se publicó `6c736d91`. Lo que queda vivo
> está marcado abajo. El detalle de lo ejecutado, en el `## Cierre` al final.

**Goal:** que `origin/main` contenga todo el trabajo verificado que hoy vive en ramas, que el
árbol de worktrees refleje solo frentes vivos, y que el gate bloqueante de `AGENTS.md` deje de
impedir la apertura de frentes nuevos.

**Por qué va primero:** el arreglo del runner de tests PHP —que dejó de juzgar en inglés lo que la
suite anuncia en español— vive solo en `claude/mystifying-bhaskara-a6207f`. Mientras siga ahí,
`main` está en rojo para todas las sesiones y cada sesión nueva gasta turnos diagnosticando un rojo
ya resuelto.

**Precondición dura, para todas las tareas de publicación:** el contenedor `app` debe montar el
árbol que se verifica (regla 7 de [[docs/coordinacion-sesiones]]). Se comprueba **antes** de leer
ningún RC:

```bash
docker inspect app --format '{{range .Mounts}}{{.Source}} -> {{.Destination}}{{"\n"}}{{end}}'
```

Un RC verde sobre el árbol equivocado es la trampa más cara del repo: parece verificación y no mide
nada.

---

## Tarea 1 — Publicar `claude/mystifying-bhaskara-a6207f` (+8)

Es la primera **porque saca `main` del rojo**, no por antigüedad.

- [ ] Reapuntar el contenedor a ese worktree con ventana coordinada, y anunciarlo
- [ ] `git fetch origin` y mirar divergencia (`git status -sb`)
- [ ] Integrar `origin/main` si hay divergencia, **en la rama, dentro del worktree** — nunca en el
      `main` del checkout principal
- [ ] **Re-verificar después de integrar**, no antes. Anotar el sha (`git rev-parse HEAD`)
- [ ] `bash scripts/publicar.sh` — el del **repo**, que no acepta `-v/-p/-m`
- [ ] Confirmar: `git rev-parse origin/main` == el sha anotado
- [ ] Devolver el contenedor a la raíz

**Verificación:** `docker compose exec app php scripts/run-php-tests.php --nivel=puro` →
`RC=0`, `28 corridos: 28 pasaron, 0 sospechosos`.

## Tarea 2 — Resolver la colisión de la baseline 0.4.0

Hay dos intentos del mismo trabajo: el terminado y medido en CI dentro de la rama de la Tarea 1, y
cinco rutas sin seguimiento abandonadas en el worktree `deuda-ci-frente-1`.

- [ ] Contrastar los dos `runtime-baseline-0.4.0.json` — el suelto y el publicado
- [ ] **Si coinciden en contenido:** borrar los sueltos, dejando constancia del contraste
- [ ] **Si difieren:** manda el medido en GitHub Actions. `initializationMs` agrupa por máquina
      antes que por código (local 191–268 ms, Actions 596–1071 ms); una medida local **no es
      comparable** y fue lo que hizo pasar la 0.3.5 por regresión
- [ ] Decidir qué hacer con `goals/runtime-budgets-al-ci/evidence/`: se conserva si documenta el
      camino, se borra si duplica lo publicado

**Verificación:** `git -C .claude/worktrees/deuda-ci-frente-1 status --porcelain` sin salida.

**Nota:** borrar es acción no reversible sobre evidencia. Confirmar con Felipe antes.

## Tarea 3 — Publicar `claude/intelligent-hermann-a4f54a` (+9)

Reparto de lienzos de Torre de Control BI por rol. La sesión ya integró `origin/main` y re-verificó;
**el merge de la Tarea 1 lo invalida** — el visto caduca con el sha (regla 2).

- [ ] Re-integrar `origin/main` (ya trae la Tarea 1) y **re-verificar sobre el sha nuevo**
- [ ] Publicar con `scripts/publicar.sh`, confirmar, devolver el contenedor

**Verificación:** `--nivel=puro` y `--nivel=db` en verde tras integrar. Los 2 sospechosos
preexistentes de `--nivel=db` están documentados y **no son de este frente**: no se arreglan aquí.

## Tarea 4 — Publicar `claude/cool-margulis-f9bb27` (+11)

Pendientes diferibles del frente de tablas. Igual que la 3: su verde es anterior a las Tareas 1 y 3.

- [ ] Re-integrar, re-verificar, `bash scripts/publicar.sh --con-merges`
- [ ] Confirmar y devolver el contenedor

**Verificación:** `npm run test:design-system:static` 8/8 · `--nivel=http` sin fallos nuevos.

**Advertencia registrada:** hacia las 15:35 del 2026-08-24 un agente tumbó por error el contenedor
compartido al limpiar el suyo. Restaurado en minutos, datos intactos (volumen nombrado), pero
**cualquier verificación en vuelo en esa ventana no es fiable** y se repite.

## Tarea 5 — Rescatar o archivar `linea-base-contractual`

`claude/elated-golick-e27253`: +10 adelante, **292 atrás**. Felipe ordenó sacarlo de `main` hasta
que declare su cierre. Lo que le falta no es código: es la sección `## Cierre`.

- [ ] Verificar los dos hallazgos **relatados y nunca comprobados**: (a) la migración corrió contra
      desarrollo y **no modificó ni una fila** —de 30 proyectos sin línea base, ninguno tiene
      cronograma consolidado usable, así que el `JOIN` no alcanza a ninguno—; (b)
      `test_bi_programa_general_chart_values.php` se pone rojo con el merge
- [ ] Sobre (b): el frente movió el origen de la fecha contractual y el test afirma lo viejo. Si el
      comportamiento nuevo es el correcto, **actualizar el test es parte de cerrar**, no algo aparte
- [ ] Sobre (a): es el patrón de [[el-contador-no-mide-el-archivo]] — una herramienta que ante «no
      hay nada que hacer» devuelve algo con forma de resultado. Decidir si el frente sigue vivo
- [ ] Integrar 292 commits y re-verificar, o **archivar la rama con su porqué escrito**

**Decisión de Felipe requerida:** rescatar o archivar. 292 commits de deriva no es un merge de
trámite. Recomendación: **archivar con el porqué escrito** y reabrir el frente contra `main` actual
si el negocio lo pide — reintegrar código de hace cinco días sobre una base que cambió por completo
cuesta más que rehacerlo, y su propia migración demostró no mover ninguna fila.

## Tarea 6 — Podar worktrees residuales

`interruptor-control-tower` (0/-70), `plan-habilitacion` (0/-78), `deuda-ci-frente-1` (0/-152) y
`deuda-ci-frente-2` (0/-132) están limpios y publicados.

- [ ] Confirmar `+0` contra `origin/main` **después** de las Tareas 1, 3 y 4
- [ ] `git worktree remove` los que no tengan sesión viva. **Conservar el checkout principal**
- [ ] Anotar en [[decisiones/sesiones-historial]] cuáles se retiraron

**Verificación:** `git worktree list` muestra solo el principal y los worktrees con sesión viva.

## Tarea 7 — Anotar el cierre

- [ ] `CHANGELOG.md` y `TASKS.md` en el mismo turno, no al final
- [ ] Línea `ingest` en `memoria/log.md`
- [ ] Registrar en [[docs/coordinacion-sesiones]] la regla nueva: **el censo de sesiones lo hace una
      sola sesión**, y se cuenta por `git rev-parse --show-toplevel`, no por nombre de worktree.
      Tres consolidadoras simultáneas reprodujeron el problema que venían a resolver

---

## Condición de hecho

`git rev-parse origin/main` contiene los tres frentes publicados; `git worktree list` no muestra
worktrees residuales; `--nivel=puro` en verde con 0 sospechosos sobre `origin/main`; y
`linea-base-contractual` tiene decisión escrita, rescatada o archivada.


---

## Cierre — 2026-08-24

**Publicado:** `aa6f0b74..6c736d91` en `origin/main`, confirmado con `git rev-parse origin/main`
coincidiendo con el sha verificado.

**Trece ramas consolidadas**, no tres. El censo original solo miró las sesiones vivas; al barrer
`refs/heads/` aparecieron nueve ramas más con trabajo fuera de `main`, algunas de doce días atrás.

**Verificación después de integrar** (contenedor montando `/Users/felipebenitez/Developer/lps-aia`,
que es el árbol verificado):

| Comprobación | Resultado |
|---|---|
| `run-php-tests.php --nivel=puro` | `RC=0` · 29 corridos, 29 pasaron, **0 sospechosos** (antes 27/1) |
| PHPUnit | 52 tests, 76 aserciones, OK |
| `npm run test:design-system:static` | `RC=0` · 8/8 |
| `npm run test:wiki` | forma en verde; alarma de veracidad, que no bloquea |
| `scripts/publicar.sh --con-merges` | los cuatro checks en verde sobre `6c736d91` |

**Tres cosas que los merges destaparon, y que la verificación posterior cazó** — ninguna la habría
visto quien hizo el trabajo original:

1. **La lista blanca de SQL de CI rechazó el Dockerfile.** Las migraciones `general_flags` y
   `sembrar_linea_base_contractual` reclamaban **ambas el slot 121**. No eran rivales: se renumeró
   la segunda a **122** y se declararon las dos. El guardarraíl hizo exactamente su trabajo.
2. **`zealous-archimedes` editaba el baseline `0.3.3` en sitio**, y ese archivo está anclado por
   hash. Es el intento de D-GAC-6 que ya falló el 2026-08-12 — por eso la rama estaba abandonada.
   **Revertido**: su sucesor legítimo son `0.3.4` y `0.4.0`.
3. **El octavo pase de veracidad quedó duplicado** en dos redacciones rivales (131 vs 184 commits)
   al resolver `memoria/log.md` de forma aditiva. Retirada la de la rama, conservada la de `main`.

**Error propio, corregido:** se resolvieron conflictos de prosa y de CSS con una regla aditiva
—quitar marcadores conservando ambos lados— que solo era válida para `CHANGELOG.md` y para el log
append-only. En `vendor-datatables-legacy.css` dejó **dos `@import` de la misma hoja**. Detectado al
auditar la resolución, no por un test. La lección: *aditivo* es una propiedad del archivo, no del
conflicto.

**Rescate previo al borrado.** Los worktrees guardaban artefactos que git ignora y que el borrado
habría destruido en silencio — lo avisó la sesión de `cool-margulis`, no lo detectó el plan.
**63 MB y 1.267 archivos** copiados a `~/Documents/rescate-worktrees-lps-aia-20260824/`, incluidos
los dos `.superpowers/sdd/` con los rulings tomados en nombre de Felipe y toda la evidencia visual
de los goals.

**Estado final:** un worktree (la sesión viva), `main` limpio, y en el remoto solo `main` más las
tres ramas de dependabot.

### Tareas del plan, al cierre

- Tarea 1 (publicar el arreglo del runner) — **HECHA**
- Tarea 2 (colisión de la baseline 0.4.0) — **HECHA**: se resolvió al integrar; los sueltos del
  worktree `deuda-ci-frente-1` desaparecieron con él, y la versión buena es la medida en Actions
- Tarea 3 y 4 (publicar los otros dos frentes) — **HECHAS**
- Tarea 5 (`linea-base-contractual`) — **RESUELTA POR OTRA VÍA**: Felipe ordenó mergear todas las
  ramas, así que se integró en vez de archivarse. **Sus dos hallazgos siguen sin verificar** y pasan
  a P2: la migración que no movió ninguna fila, y `test_bi_programa_general_chart_values.php`
- Tarea 6 (podar worktrees) — **HECHA**
- Tarea 7 (anotar el cierre) — **HECHA** con esta sección

### Lo que quedaba vivo, cerrado el mismo 2026-08-24

- [x] **Pase de veracidad de la wiki (noveno).** Cuatro verificadores de solo lectura por área sobre
      `a4f19884`; las cifras sustantivas re-medidas antes de aplicarlas —y **una no coincidió**: el
      verificador daba 298 tokens `--ds-*` y el conteo anclado a declaración da 295, que es la que
      quedó—. **17 hallazgos, 15 páginas corregidas, ninguna derogada.** `npm run test:wiki` → `RC=0`.
      El hallazgo de más fondo estaba repetido en **cinco** páginas: `closeout-evidence.json` declara
      **nueve** gates desde el 2026-08-14 y todas seguían diciendo ocho — `memoria/mapas/design-system.md`
      llegaba a contradecirse consigo mismo, «8» arriba y «nueve» treinta líneas más abajo.
- [x] **Los dos hallazgos de `linea-base-contractual`, verificados.** Ver abajo.

## Los dos hallazgos, medidos por fin

Llevaban desde el 2026-08-19 **relatados y sin comprobar por nadie**. Los dos eran ciertos, y el
primero es más nítido de lo que decía el relato.

### (a) La migración no mueve ninguna fila — y la causa no era la que se creía

Medido contra la base de desarrollo:

| | |
|---|---|
| Proyectos totales | 90 |
| Sin línea base declarada | 30 |
| Con cronograma consolidado usable | 56 |
| **Filas que la migración actualizaría hoy** | **0** |
| **De los 30 sin línea base, cuántos tienen alguna fila en `programa_consolidado`** | **0** |

El relato decía «el `JOIN` no alcanza a ninguno», lo que sugiere un defecto del `JOIN`. **No lo hay:**
los 30 proyectos sin línea base **no tienen ni una fila de cronograma**. No hay nada de donde
deducirla. La migración es correcta y ya hizo su trabajo con los 60 que sí tenían cronograma.

**Veredicto:** no es el patrón de [[el-contador-no-mide-el-archivo]] —esa trampa es una herramienta
que devuelve algo con forma de resultado sin medir—. Aquí la herramienta es honesta: cero filas
porque hay cero candidatos. **Lo que queda no es deuda de la migración, es un hueco de datos**:
30 proyectos sin cronograma. Cerrarlo es decisión de negocio, no de este frente.

### (b) El test afirmaba el contrato derogado

`tests/test_bi_programa_general_chart_values.php` daba **15 `FAIL`**. Exigía
`contractual_finish_basis === 'first_available_snapshot_per_project'` mientras el servicio produce
`declared_project_baseline` (`src/Services/ControlTowerService.php:1837,1898`).

**Actualizar el test NO fue ocultar una regresión**, y eso hay que poder demostrarlo: la spec
`2026-08-19-linea-base-contractual-design.md` lo decide con todas las letras — «el campo
`contractual_finish_basis` deja de declarar `first_available_snapshot_per_project` y pasa a declarar
que la fuente es la línea base declarada» — a partir de una frase textual de Felipe: «al reprogramar
y cambiar actividades, el informe **SÍ** debe conservar la fecha contractual original». El test
afirmaba lo viejo.

Tres cambios, y el tercero es el que importa:

1. El test lee la fecha de `general_proyectos_procesos.fechaFinLineaBase` en vez de deducirla.
2. El escenario `baseline-drift` compara contra la declarada, conservando intacta la aserción de
   `latest_finish`, que es la que de verdad prueba que una reprogramación no mueve la fecha.
3. **`BiContractFixture` registra sus dos proyectos y les declara la línea base** — no los
   registraba nadie, solo `seedCicScenario`. Y **no se escribió una fecha literal**: se deriva del
   primer corte con el **mismo `UPDATE` de la migración**, acotado a esos proyectos. Una fecha a
   mano habría caducado en silencio en cuanto alguien tocara una `Fecha_Fin` del fixture.

**Resultado:** de 15 `FAIL` a **0**. El test llevaba rojo desde el 2026-08-14.

**Un defecto propio del test, anotado y no arreglado aquí:** imprime `FAIL` y sale con **`RC=0`**.
No propaga su propio fallo, así que un runner que solo mire el código de salida lo da por bueno.
Es de la familia de [[el-codigo-de-salida-se-pierde-en-la-tuberia]] y merece frente propio.

### Verificación final de P1

| Comprobación | Resultado |
|---|---|
| `run-php-tests.php --nivel=puro` | `RC=0` · 29/29, 0 sospechosos |
| `run-php-tests.php --nivel=db` | `RC=0` · 81/81, 0 fallaron · PHPUnit 4 clases en verde |
| `--nivel=datos-proyecto` | 117 corridos, 104 pasaron, **12 fallaron** — los preexistentes documentados. **El 13.º era éste**, y ya no está |
| `npm run test:wiki` | `RC=0`, sin hallazgos |
