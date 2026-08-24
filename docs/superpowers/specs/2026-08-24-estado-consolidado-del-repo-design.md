---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-24
areas: [proceso, design-system, lps, pdc]
fuente: docs/superpowers/specs/2026-08-24-estado-consolidado-del-repo-design.md
resumen: "Spec única del estado del repositorio al 2026-08-24: censo medido de las doce sesiones simultáneas, las ramas con trabajo terminado sin publicar, y el reparto de todo el pendiente vivo en seis planes con orden y dependencias declaradas"
project: lps-aia
---

# Estado consolidado del repositorio — la spec única

**Fecha:** 2026-08-24
**Origen:** Encargo de Felipe — «estoy un poco perdido por todas las sesiones simultáneas de este
repo y sus worktrees»: consultar el estado de cada sesión, ordenarles suspender, consolidar el
estado de planes y specs vigentes, y unificar todo en **una spec y varios planes**.
**Qué sustituye:** no deroga ninguna spec anterior. **Sustituye a `TASKS.md` como punto de entrada
del pendiente** mientras dure el reparto: `TASKS.md` sigue siendo la lista viva, esta spec es el
mapa que dice qué bloquea a qué y quién lo ejecuta.

---

## 1. El problema medido, no el que se sentía

Felipe no estaba perdido por falta de documentación. Estaba perdido porque el repositorio tiene
**tres fuentes de verdad que no se miran entre sí**, y las tres decían cosas distintas:

| Fuente | Qué dice | Por qué engaña |
|---|---|---|
| `TASKS.md` | 22 fases en cuatro programas, con estado | Se escribe desde lo que ve **una** sesión, y una sesión ve **su** worktree. Ya falló así el 2026-08-19: se escribió desde un árbol 114 commits atrasado y dio por activos cinco frentes cerrados |
| Los `goals/<slug>/goal.md` | 60 carpetas, cada una con su condición de hecho | La regla de lectura cuenta como abierto todo goal sin encabezado `## Cierre`, aunque su trabajo esté en producción |
| Las ramas y worktrees | El código que de verdad existe | Nadie las miraba en conjunto. **Es la única fuente que no puede mentir** |

**El hallazgo que ordena todo lo demás:** de las 12 sesiones vivas al momento del censo, **solo 4
trabajaban en este repositorio**. Las otras 8 estaban en `loop-engineering` o sin repo. Y de esas 4,
**3 tenían trabajo terminado y verificado que nunca llegó a `origin/main`**.

La sensación de enjambre incontrolable era, medida, un **atasco de publicación**: no sobra trabajo
en curso, sobra trabajo terminado sin desaguar.

---

## 2. Censo de sesiones (2026-08-24, ~15:50)

Las doce sesiones respondieron. Todas quedaron suspendidas, con el árbol intacto y sin publicar.

### 2.1 Sesiones de este repositorio — 4

| Sesión | Rama | Estado real | Frente a `origin/main` |
|---|---|---|---|
| `bold-neumann-485f23` | `claude/mystifying-bhaskara-a6207f` | Baseline de presupuesto runtime 0.4.0 regenerada + guard de laboratorio alineado + **arreglo del runner de tests PHP**. CI verde (run 32776968532 sobre `79bf91fc`) | **+8 · sin publicar** |
| `reverent-golick-aaf932` | `claude/intelligent-hermann-a4f54a` | Reparto de lienzos de Torre de Control BI por rol. 4/4 tareas, revisión por subagentes, fix wave aplicada, `origin/main` ya integrado y re-verificado | **+9 · sin publicar** |
| `cool-margulis-f9bb27` | `claude/cool-margulis-f9bb27` | Pendientes diferibles del frente de tablas. 5/5 tareas, estático 8/8, `--nivel=http` 80/82, `publicar.sh` verde sobre `1a119c51` | **+11 · sin publicar** |
| `musing-mclaren-4bbb2b` | `claude/musing-mclaren-4bbb2b` | Diagnóstico del golden visual de Programa General. **Publicado** (`aa6f0b74`) | +0 · al día |

### 2.2 Sesiones que no son de este repositorio — 8

`festive-bassi`, `superpowers-brainstorming`, `unruffled-lalande`, `cool-wu` y
**`lps-aia-panels-deployment`** trabajan en `loop-engineering`. La última entró en el censo por
**falso positivo de nombre**: se llama así porque el tema consultado era por qué los paneles no
salen *en* lps-aia, pero el diagnóstico se hizo leyendo código de `loop-engineering`.
`felipebenitez-2e` y `felipebenitez-e5` no tienen repositorio (`cwd` en el home).

**Lección de proceso, medida:** el censo por nombre de worktree cuenta mal. El criterio correcto es
`git -C <worktree> rev-parse --show-toplevel`.

### 2.3 El defecto que el propio censo destapó

Tres sesiones distintas barrieron a la vez pidiendo el mismo reporte —`consolidar-sesiones-
simultaneas-fa3283`, `validate-session-coordination-dca393-17` y esta—, dos de ellas **en el mismo
worktree**. Cada sesión suspendida gastó tres turnos respondiendo lo mismo en tres formatos.

Es exactamente el problema que la consolidación venía a resolver, reproducido por la consolidación
misma. **Regla que esta spec incorpora:** un barrido de estado lo hace **una sola sesión**; las
demás le reportan a ella. Es la regla de pluma única que Felipe ya aplica a las specs —muchos
investigan, uno escribe— extendida al censo.

---

## 3. Estado de las ramas y worktrees

`origin/main` = `aa6f0b74`.

| Rama | Adelante | Atrás | Veredicto |
|---|---|---|---|
| `claude/cool-margulis-f9bb27` | +11 | 0 | **Publicable ya.** Verificado tras integrar |
| `claude/intelligent-hermann-a4f54a` | +9 | 0 | **Publicable ya.** Verificado tras integrar |
| `claude/mystifying-bhaskara-a6207f` | +8 | 0 | **Publicable ya, y es la más urgente** — ver §4 |
| `claude/elated-golick-e27253` | +10 | 292 | **Rescate.** Es `linea-base-contractual`: Felipe ordenó sacarlo de `main` hasta que declare su cierre |
| `claude/interruptor-control-tower` | 0 | 70 | Worktree principal, trabajo ya publicado |
| `claude/plan-habilitacion` | 0 | 78 | Publicada. Worktree residual |
| `claude/deuda-ci-frente-1` · `-2` | 0 | 132–152 | Publicadas. Worktrees residuales |

**Un solo worktree está sucio:** `deuda-ci-frente-1`, con 5 rutas sin seguimiento —
`runtime-baseline-0.4.0.json`, tres medidas de `0.4.0` y `goals/runtime-budgets-al-ci/evidence/`.

**Eso es una colisión, no basura.** Son artefactos de la **misma** baseline 0.4.0 que
`bold-neumann` regeneró y verificó en CI dentro de su propia rama. Hay dos intentos del mismo
trabajo, uno terminado y otro abandonado a medias. Antes de borrar hay que contrastarlos: el que
manda es el medido en GitHub Actions, porque `initializationMs` **agrupa por máquina antes que por
código** (local 191–268 ms, Actions 596–1071 ms), y ése es justamente el motivo por el que la 0.3.5
se veía como regresión sin serlo.

---

## 4. El desagüe es lo primero, y hay un motivo duro

`AGENTS.md` §Publicación ya lo dice: **el gate de cierre de frente es bloqueante, no se abre un
frente nuevo mientras el anterior no esté publicado en `main`**. Hoy hay tres frentes terminados sin
publicar. Formalmente, **ningún frente nuevo puede abrirse**.

Y hay una razón que va más allá de la formalidad. El arreglo del runner de tests PHP que vive en
`claude/mystifying-bhaskara-a6207f` corrige que el runner marcaba **SOSPECHOSO** todo test que
anuncia `PASA:` en español, porque buscaba `pass` en inglés. Con `--nivel=puro` pasó de 27/1 a
**28 corridos, 28 pasaron, 0 sospechosos**.

Mientras ese commit siga solo en una rama, **`main` está en rojo para todas las sesiones** — y cada
sesión que arranque va a gastar turnos diagnosticando un rojo que ya está arreglado en otra rama.
Esa es la prioridad real, y es la Tarea 1 del Plan 1.

---

## 5. Consolidación del pendiente vivo

Punto de partida: la auditoría del 2026-08-20 sobre las 61 specs (44 ejecutadas · 16 parciales · 1
pendiente · 12 cerradas), más `TASKS.md`, más las dos specs nuevas del 2026-08-24 que aún viven en
ramas sin publicar (`reparto-lienzos-por-rol`, `pendientes-frente-tablas`).

Los cuatro programas de `TASKS.md` siguen siendo el esqueleto correcto y **esta spec no los
rediseña**. Lo que hace es atarlos a un orden ejecutable y resolver los solapes que quedaban:

- **Manda DS-F3** sobre los gates. `MO-F4` se retira como fase propia y entra como requisito;
  `CP-F-AB` se recorta al mínimo que desbloquea el CI. Ratificado, ya estaba decidido el 2026-08-18.
- **`bi-control-tower-gemini` cierra en dark** y no espera al tema claro (D-7, 2026-08-20).
- **MO-F3 va justo detrás de MO-F2b**, no estacionada (orden de Felipe, 2026-08-20).
- **La Torre de Control no se recaptura**, se reconstruye sobre el contrato de DS-F1.

### Lo que NO entra en ningún plan, a propósito

**El despliegue a producción (CP-F-E)** y **el apply de `recalculo-estados` en producción** exigen
autorización propia y explícita de Felipe, siempre. Publicar en `main` no la concede. Quedan
nombrados en el Plan 5 como fase terminal **sin ejecutar**.

---

## 6. Los seis planes

El criterio de partición es **la dependencia real, no el tema**: cada plan arranca solo cuando el
anterior le entrega algo concreto.

| Plan | Nombre | Depende de | Por qué es un plan aparte |
|---|---|---|---|
| **P1** | Desagüe y consolidación de ramas | — | Desbloquea el gate de `AGENTS.md` y saca `main` del rojo. Nada más puede empezar antes |
| **P2** | El CI en verde y los presupuestos | P1 | Sin CI verde no hay forma de **medir** nada de DS-F0 en adelante. Es andamio declarado |
| **P3** | Programa Design System · DS-F1 → DS-F3 | P2 | Es el programa que manda sobre los gates. Arranca con brainstorming: el contrato es decisión de negocio, no técnica |
| **P4** | Móvil y tema claro · MO-F2b → MO-F3 | P3 (contrato) | Reconstruye, no reactiva: `linen` salió del producto el 2026-07-25 |
| **P5** | Cierre hasta producción · CP-F-C → CP-F-E | P2 | Termina en una fase que **no se ejecuta sin la palabra de Felipe** |
| **P6** | Higiene documental y de coordinación | — | **Corre en paralelo a todo.** No toca código de producto, así que no compite por el contenedor compartido |

Rutas:
[[docs/superpowers/plans/2026-08-24-p1-desague-y-consolidacion|P1]] ·
[[docs/superpowers/plans/2026-08-24-p2-ci-en-verde-y-presupuestos|P2]] ·
[[docs/superpowers/plans/2026-08-24-p3-design-system-contrato-y-control|P3]] ·
[[docs/superpowers/plans/2026-08-24-p4-movil-y-tema-claro|P4]] ·
[[docs/superpowers/plans/2026-08-24-p5-cierre-hasta-produccion|P5]] ·
[[docs/superpowers/plans/2026-08-24-p6-higiene-documental-y-coordinacion|P6]]

---

## 7. Condición de hecho de esta spec

1. Las doce sesiones consultadas y suspendidas, con su reporte recogido. **CUMPLIDO** (§2).
2. El estado de ramas y worktrees medido con `git`, no relatado. **CUMPLIDO** (§3).
3. Todo el pendiente vivo repartido en seis planes sin huérfanos ni duplicados, con dependencias
   declaradas. **CUMPLIDO** (§6).
4. Cada plan con sus tareas, su condición de hecho y su verificación. **Ver cada plan.**

## 8. Lo que esta spec no hace

No ejecuta nada. No publica ninguna rama, no borra ningún worktree, no toca el contenedor
compartido. Todo eso es P1, y P1 necesita que el contenedor `app` monte el árbol que se verifica
(regla 7 de [[docs/coordinacion-sesiones]]) — condición que **no se cumple** en el worktree desde el
que se escribió esta spec.

## Archivos de este trabajo

- Planes: los seis de §6
- Reglas de tráfico: [[docs/coordinacion-sesiones]]
- Lista viva de pendientes: [[TASKS]]
- Auditoría de partida: [[docs/superpowers/reports/2026-08-20-auditoria-estado-specs]]
