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
