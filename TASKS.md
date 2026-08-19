---
capa: fuente
tipo: goal-doc
estado: abierto
fecha: 2026-08-19
areas: [proceso]
tags: [pendiente]
fuente: TASKS.md
resumen: Pendientes vivos del proyecto — bloqueantes, frentes activos y lo diferido, releído contra origin/main el 2026-08-19.
---

# Tareas

**Fuente única de pendientes.** El trabajo corre en un enjambre de sesiones sobre
`.claude/worktrees/` (ver [[docs/coordinacion-sesiones]]); cada frente tiene su
`goals/<slug>/goal.md` y su registro en `decisiones/`. Esta lista es la vista para retomar sin
releer el chat de cada sesión.

Para **en qué fase va cada programa**, la otra página: [[goals/cola-de-pendientes]]. Para el
**estado de cada goal**, [[goals/estado]].

> Releído el 2026-08-19 contra `origin/main`. La versión anterior de este archivo se escribió desde
> un árbol 114 commits atrasado y daba por activos cinco frentes que ya habían cerrado y publicado.
> **Es el modo de fallo a vigilar aquí:** este archivo se escribe desde lo que una sesión ve, y una
> sesión ve su worktree.

## Bloqueantes

- [ ] **Abrir una coordinadora nueva.** «Coordinadora Intento 3» ya no existe como sesión viva y el
  proyecto quedó sin sesión coordinadora: nadie audita el trabajo de las sesiones de ejecución, da
  el visto antes de publicar, ni es el único punto de contacto con el usuario para decisiones
  (regla en [[docs/coordinacion-sesiones]]). Mientras no haya coordinadora, los frentes activos no
  deberían publicar a `main` sin ese visto.

## Ahora

- [ ] **apply-recalculo-estados** — el de mayor riesgo del repo, y el único autorizado y sin
  ejecutar. Felipe dio el «sí, apply completo» con el informe del dry-run delante: 40.664 filas de
  la columna `Estado` en 16 proyectos. **Antes de migrar hay que correr la captura de las 24 filas
  terminadas con fecha de inicio futura** (`goals/estados-fuera-de-ventana/diagnostico-113-contradictorias.md`);
  después ya no hay forma de saber cuáles eran. Exige los gates de
  `docs/global-tables-architecture.md` y ventana de base exclusiva. **Solo base de desarrollo** —
  producción es deploy y necesita su propia autorización. Su `goal.md` sigue en plantilla.
- [ ] **runtime-budgets-al-ci** — Fase 1 de `docs/superpowers/plans/2026-08-19-runtime-budgets-al-ci.md`,
  sha verificado `c23b1c6a`. Desbloquea el único gate `blocked` de los nueve de
  `closeout-evidence.json`. Andamio declarado, no inversión: DS-F3 lo reemplaza.
- [ ] **DS-F1, lo que queda del contrato** — la escala de estado cerró (F1a). Faltan tokens,
  primitivas `aia-*`, escala de severidad y escala de z-index. Arranca con brainstorming: el
  contrato es decisión de negocio. Entrada lista: los 68 hallazgos de DS-F0.
- [ ] **linea-base-contractual** — sembrado por migración SQL, con `database/migrations/**`
  autorizado explícitamente por Felipe para este frente. El dry-run se pega como evidencia antes de
  commitear; contra producción no lo ejecuta nadie, viaja versionado. **No tiene `goals/<slug>/`
  propio**: su registro vive solo en `decisiones/linea-base-contractual-coordinadora.md`.
- [ ] **Triaje de los nueve goals en plantilla** — `a187ccda`, `buttons-important-leyenda`,
  `contador-no-mide-el-archivo`, `focus-visible-verde`, `forma-quitar-pasos`,
  `reserva-redundante-green-dark`, `reservas-contradictorias-var`, `severidad-runtime`,
  `veracidad-8`. Objetivo sin redactar y sin cierre; decidir cuáles siguen vivos es criterio de
  Felipe, no deducción de que lleven días quietos.
- [ ] **bi-control-tower-gemini** — bloqueado desde el 2026-08-10 por causa mal diagnosticada: no
  es «falta aprobación visual», es que pide aprobar 6 modos y 3 usan el tema `linen`, retirado el
  2026-07-25. Hay que rehacer la condición de hecho, no correr los tests. Depende de MO-F3.

## Diferibles

- [ ] **Escribir el cierre de dos goals ya ejecutados** — `pdc-tanda2-plan-verdad` y
  `adopcion-logo-construccion` tienen el trabajo hecho y ninguna sección `## Cierre`, así que la
  regla de lectura los cuenta como abiertos. Es escribir el cierre, no re-ejecutar.
- [ ] **Enchufar `--estricto` a `npm run test:wiki`** — hoy el gate corre en estricto por línea de
  comandos, pero la decisión de hacerlo obligatorio es de contrato: a partir de ahí toda fuente
  nueva nace con frontmatter o el gate se pone rojo. El hueco ya se midió: una fuente entró sin
  declarar por un merge y el gate no lo detectó.
- [ ] **Plugins de comunidad de Obsidian** (Dataview, Tasks, Kanban, Excalidraw, Iconize, Homepage,
  tema Minimal) y **grupos de color del grafo** — quedaron fuera de la Fase 0b por decisión del
  usuario y por no poder verificarse sin abrir Obsidian.
- [ ] **Proponer verificación de tests en contenedor como config por proyecto.** La vía Docker se
  quitó del gate global de `~/.claude` el 2026-08-19; este repo es 100% dockerizado y su
  `verify.quick` en `.claude/gate.yaml` evita PHP/Docker por costo, pero el resto de la suite sí
  necesita el contenedor. Afecta config global, no solo este repo.
- [ ] **Fusionar contenido solapado de `AGENTS.md` / `GEMINI.md` / `CLAUDE.md`** con lo que ahora
  vive en [[README]] y [[ROADMAP]]. No se tocó su contenido en el bootstrap, solo se enlazó.
- [ ] **Plan espacio SiteGround** — tareas 1–5 de
  `docs/superpowers/plans/2026-08-18-espacio-cuenta-siteground.md`.
- [ ] **Dropdown PS sobre selector de semana** — diagnóstico del stacking en
  `/programacion-semanal`, con `systematic-debugging`.
- [ ] **Backlog Fase 7-10** (notificaciones por rol, QA sistemático, despliegue gradual, shared
  schema): sin frente abierto. Ver [[ROADMAP]].
- [ ] **Realces sin declarar** (r0 de Programa General y ruta crítica de Programación Semanal) como
  decisión única de producto — en la cola de [[docs/decisiones-pendientes]], sin prisa.

## Lo que no está aquí a propósito

**El despliegue a producción** (CP-F-E, ~1.255 commits de retraso) no es una tarea de esta lista:
necesita autorización propia y explícita de Felipe, siempre, y publicar en `main` no la concede.

## Hechas (últimas 10)

- [x] 2026-08-19 — **DS-F0 cerrada y publicada** (`567e566e`): `docs/design-system/auditoria/` con
  68 hallazgos clasificados sobre un censo de 257 rutas, sin tocar código de producto.
- [x] 2026-08-19 — **Fase 0b, wiki v2**: las seis tandas cerradas y publicadas, lint estricto verde.
- [x] 2026-08-19 — `ds-f1a-estado` (`4a152a54`): la escala de estado del contrato, medida contra
  50.966 actividades reales.
- [x] 2026-08-19 — `estados-fuera-de-ventana` (`aeaa7a77`): los dos calculadores producen
  `Fuera de Ventana` desde la séptima semana, y por primera vez tienen pruebas.
- [x] 2026-08-19 — `migracion-estados`: dry-run, respaldo probado restaurando 2.024 filas, y guarda
  que deniega el `--apply` con `RC=1`. Prepara, no aplica.
- [x] 2026-08-19 — `bug-coloreado-severidad` cerrado.
- [x] 2026-08-19 — Bootstrap de la wiki LLM de 5 archivos en la raíz.
- [x] 2026-08-18 — Fuente única de las 22 fases; lo verificado se archiva (`fc098810`).
- [x] 2026-08-18 — Los goals dejan de escaparse del control de versiones (`9711ae3f`): regla general
  al final del `.gitignore` en vez de lista blanca a mano.
- [x] 2026-08-18 — El correo sale por el MTA local del hosting, no por relay externo (`21243c7e`).
