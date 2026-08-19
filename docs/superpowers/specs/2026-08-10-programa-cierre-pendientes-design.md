---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-10
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-10-programa-cierre-pendientes-design.md
resumen: Programa de cierre de pendientes — diseño
---

# Programa de cierre de pendientes — diseño

**Fecha:** 2026-08-10
**Tipo:** spec de programa. No se implementa directamente: ordena seis frentes, cada uno con su
propio spec y su propio plan de `writing-plans`.

## Por qué existe

El repositorio no está perdido, está **disperso**. Una auditoría del 2026-08-10 sobre los 24 goals,
los 34 planes, los 45 specs, `docs/EXPERIMENTS.md`, la wiki y el grafo de ramas encontró 19 goals
cerrados, 3 descartados o absorbidos, 1 abierto, 1 bloqueado y 1 indeterminado — más un backlog de
39 hallazgos y 1.255 commits que nunca llegaron a producción. Lo que falta no es trabajo de
descubrimiento: es **secuencia y cierre**.

Este spec fija esa secuencia. Su valor está en el orden y en los gates, no en el detalle técnico de
cada frente, que vive en el spec de ese frente.

### El dato que cambia cómo se lee todo lo demás

**Los planes no son marcador de avance.** Los 34 planes de `docs/superpowers/plans/` suman **1.243
casillas `- [ ]` y cero `- [x]`**: nadie las marcó nunca. El estado real vive en
`goals/<slug>/goal.md`, en los commits y en `memoria/goals/estado.md`. Cualquier lectura basada en
checkboxes daría «0 % hecho», y es falso. Ninguna tarea de este programa se declara hecha por
marcar una casilla.

## Estado de partida, medido

| Frente | Hecho | Pendiente |
|---|---|---|
| Dark mode + tablas | Goal cerrado 2026-07-31 (G0–G6); 34/38 tareas de la cola de decisiones | Task 28 (papeleo del borrado de 22 ramas) y Task 31 (barrido final) |
| PDC v2 | 7 goals cerrados; v1 retirado; filtros y buscadores en `main` | **Nunca desplegado**: 1.255 commits de brecha |
| Design System | 1.1.0 publicada, suite estática 8/8; F1 y F2a-1/2a de reapertura cerradas | 32 excepciones re-vencidas a `1.2.0`; goal `design-system-nucleo-gobernanza` sin sección de cierre |
| Torre de Control BI | Preflight técnica aprobada | Bloqueado: falta aprobación visual de la matriz de 6 modos |
| Biblia de flujos | T1–T5 cerrados con sus pruebas en verde | — |
| Reapertura móvil / tema claro | F1, F2a-1, F2a-2a cerradas | F2a-2b, F2b, F3, F4 |
| improve-app | Fases 1–6 done, 7 skipped, 8 deferred | Fase 9 (`steve-jobs-design-review`) |

**Backlog `docs/EXPERIMENTS.md`, censado el 2026-08-10:** 48 filas — **39 abiertas** (22 ejecutables
y **17 marcadas `decide: usuario`**) y 9 cerradas.

**Higiene pendiente:**

- `claude/cranky-dhawan-aa8725` está 57 commits por delante de `main`, de los cuales **56 son
  informes de estado repetidos y 1 es código**: `1af1471f`, que arregla que la recuperación de
  contraseña dijera «enviado» aunque el correo fallara (B-10), verificado de punta a punta.
- `claude/cierre-ds-110` y `feat/marca-construccion` están a 0 commits de `main`: ya integradas.
- `tests/browser/programacion-semanal-roles-phases.mjs` tiene 33 líneas sin commitear, de F2a-2b-1.
- `docs/reportes/estado-desarrollo.html` tiene corte del 2026-08-07 y no ve los ~40 commits de
  design system del 08 y el 09. `memoria/goals/estado.md` tiene corte del 2026-08-06.

## Decisiones

| # | Decisión | Motivo |
|---|---|---|
| **D1** | **No hay un plan monolítico.** Este spec de programa ordena seis frentes; cada uno recibe su spec y su plan propios cuando le llega el turno. | Los seis no comparten código ni condición de hecho. Un plan único que los mezclara sería inejecutable y su avance, imposible de leer. |
| **D2** | **Se publica al final, en un solo despliegue.** | Decisión del usuario. Riesgo asumido y declarado abajo. |
| **D3** | **Los 17 hallazgos `decide: usuario` se resuelven en una sesión de decisión al inicio**, presentados en lenguaje simple y con recomendación por cada uno. | Sin ellos, el 44 % del backlog no avanza. Resolverlos en tanda cuesta una lectura y desbloquea todo el frente. |
| **D4** | **Las cuatro fases pendientes de móvil y tema claro entran en el programa** (F2a-2b, F2b, F3, F4). | Decisión del usuario: se cierra el goal `reapertura-movil-y-tema-claro` entero, no por mitades. |
| **D5** | **El tema claro se construye nuevo, derivado token a token del sistema actual.** No se rescata `linen` del historial. | `linen` se retiró el 2026-07-25 (DS-030) y es anterior a la 1.1.0: arrastraría decisiones ya derogadas y habría que auditarlo entero igual. Derivarlo de los tokens vigentes deja un tema que el design system gobierna como al oscuro. |
| **D6** | **B-10 entra por cherry-pick de `1af1471f`, no por merge de la rama.** La rama no se borra hasta que el usuario lo confirme. | Traer los 56 commits de informes metería ruido documental en `main` sin aportar código. |
| **D7** | **Ejecución de corrido, con gate solo entre frentes.** Seis paradas en total. | Equilibrio entre control y avance: sin pausas dentro de un frente, aprobación antes de empezar el siguiente. |
| **D8** | **Los hallazgos van antes que el móvil.** | Son arreglos pequeños sobre el código actual; el móvil reescribe cómo se presentan esas mismas pantallas. Arreglar antes evita rehacer cada arreglo en dos presentaciones distintas. |

### El riesgo de D2, dicho una vez

Publicar al final significa que el día del despliegue irá la brecha actual de 1.255 commits **más
todo este programa**: móvil, tema claro y 39 arreglos. Es el despliegue más grande y más difícil de
diagnosticar posible: si algo se rompe en producción, el espacio de búsqueda es la campaña entera.

Se asume por decisión del usuario y se mitiga en el Frente 5 con validación completa en staging
antes de tocar producción, respaldo verificable y estrategia de restauración escrita. La mitigación
reduce la probabilidad de un fallo silencioso; **no** reduce el coste de diagnosticarlo si ocurre.

## Los seis frentes

### Frente 0 — Higiene y decisiones

Lo barato que está bloqueando lo caro. Es el único frente que no se puede paralelizar con nada,
porque su salida (las 17 decisiones) es la entrada del Frente 1.

- Resolver el diff sin commitear de `tests/browser/programacion-semanal-roles-phases.mjs`.
- Cherry-pick de `1af1471f` (B-10) a `main`, con su prueba.
- Borrar `claude/cierre-ds-110` y `feat/marca-construccion`, previa confirmación del usuario.
- **Sesión de decisión de los 17 hallazgos `decide: usuario`.**
- Aprobación visual de la matriz de 6 modos → desbloquea `bi-control-tower-gemini`.
- Cierre formal de `design-system-nucleo-gobernanza`, **verificado afirmación por afirmación contra
  el código**, no declarado. Si algún hecho de su `facts.md` ya no es cierto, se corrige antes de
  cerrar.
- Cerrar Task 28 y Task 31 de la campaña de dark mode, los dos residuos de 34/38.
- Poner al día `memoria/goals/estado.md` (operación `ingest`) y `docs/reportes/estado-desarrollo.html`.

**Condición de hecho:** `main` con B-10 dentro y el worktree limpio; las 17 decisiones escritas en
`EXPERIMENTS.md` como disposición firme; los dos goals cerrados o desbloqueados con su evidencia;
`npm run test:wiki` en verde.

**→ Gate 1.**

### Frente 1 — Los 39 hallazgos

Tres tandas por naturaleza del cambio, no por puntuación suelta: dentro de cada tanda el orden sí es
por ICE descendente.

**Recontado el 2026-08-10, tras la sesión de decisión del Frente 0.** Los 17 `decide: usuario` se
resolvieron: 16 aprobados y 1 diferido (C-42, el tabulador de la barra lateral). El total sube de 39
a **40 abiertos** porque el Frente 0 destapó uno nuevo al capturar el golden de
`/programa-general-actualizar`: el chip «Auto-Guardado» no se oculta nunca, ICE 320.

**Reparto real: 23 que ya eran ejecutables + 16 aprobados + 1 diferido = 40. Accionables: 39.**

Uno cambió de naturaleza al preguntarlo y ya no pertenece a la tanda donde estaba: «reabrir semana»
se había registrado como duda de producto y resultó un hallazgo de permisos con tres capas en
desacuerdo — cliente, servidor y la regla que el usuario quiere. Su ICE sube de 140 a 400 y entra en
la tanda 1A, no en la 1B.

| Tanda | Cuántos | De ellos, `decide` | Qué agrupa |
|---|---|---|---|
| **1A · Seguridad y RBAC** | 12 | 5 | `canDeleteRows` inerte (490), sesión caducada que pierde trabajo (384), `canSeeReports` inerte (360), `guard(allowIfConfirmed)` sin comprobar (336), RBAC duplicado sin gate (315), los dos criterios divergentes del candado de semana (324), `rand(0,100)` del selector (400), `normalizeRoleCode` privado (288), la invariante incumplida del selector (280), BI-003, RBAC-001, RBAC-A |
| **1B · Cascada LPS** | 13 | 4 | Importar cronograma sin acuse (504), filtrar a cero filas miente (480), acuse de guardado en 1 de 4 rejillas (450), «⚠ Sin asignar» sin peso ni fondo (448), chips sin `aria-pressed` (432), resumen de cierre cortado a 8 (400), paridad PI/PS del Responsable AIA (392), sin estado «guardando» (336), PI avisa después del hecho (280), contadores que cuentan el filtro (270), gate de cierre duplicado (168), reabrir semana solo Admin (140), momento firma (150) |
| **1C · Pulido visual, a11y y texto** | 14 | 8 | Estado vacío de la pantalla que se enseña al cliente (324), Control de Cambios provisional (300), chips a dos líneas (280), ids repetidos en `/control-cambios` (252), densidad de fila (252), botón flotante que tapa datos (216), historial del PDC sin salida (216), motivo del botón bloqueado solo con ratón (192), `Esc` en modales (180), PI sin acción primaria (180), tabulador acolchado (160), fila fantasma del sandbox (140), textos de dominio (125), fase 6 del DS (98) |

La fase 9 de `improve-app` (`steve-jobs-design-review`) se ejecuta **al cerrar este frente**, en
frío, sobre la cascada PG → PI → PS ya arreglada. Es su momento natural: revisar antes sería
revisar defectos ya censados.

**Condición de hecho:** `docs/EXPERIMENTS.md` sin ninguna fila `abierto` que no tenga dueño y motivo
escrito. Un hallazgo puede cerrarse como «no se arregla, y este es el porqué» — lo que no puede es
quedarse mudo.

**→ Gate 2.**

### Frente 1b — Reconstruir los quince gates del design system

**Añadido el 2026-08-10 por decisión del usuario**, tras lo que midió la Task 6 del Frente 0. Va
**después del Frente 1 y antes del móvil**, y el orden es deliberado: los frentes 2, 3 y 4 se van a
apoyar en estos gates para afirmar «esto está bien», y hoy los gates no sirven para eso. Se arregla
la balanza antes de pesar.

**Lo que se midió, y no es «evidencia vieja»:** `closeout-evidence.json` declaraba sus 15 gates
`passed`. Ejecutados contra el HEAD real: **2 pasan, 4 fallaban, 8 no son ejecutables y 1 apunta a
una herramienta que no existe**. Los 14 archivos de `docs/design-system/evidence/` que hacían de
recibo son **stubs de dos claves** —`{"gateId": "…", "result": "passed"}`— sin comando, sin salida,
sin fecha y sin hash. El cierre se avalaba a sí mismo.

**Dos de los cuatro fallos ya están pagados** en el Frente 0 (`9011c99c`): eran listas de
excepciones de PHPStan apuntando a código borrado. Quedan dos.

Alcance de este frente:

- **Cada gate deja un recibo real** —comando, salida, fecha y hash del árbol medido— o **se retira
  de la lista con su motivo escrito**. Un gate que no puede probar lo que afirma no es un gate.
- **`git-preservation` se rediseña o se retira.** Compara contra el snapshot del arranque del
  Sprint 00, a 1352 commits de HEAD: no es un gate re-ejecutable, es un candado de un solo uso que
  ya se disparó y que ningún cierre futuro podrá pasar.
- **`accessibility-insights` se resuelve:** su comando declarado no es un binario instalado ni un
  script del repo. O se instala, o se sustituye por el carril de accesibilidad que sí existe, o se
  retira.
- **Los tres gates de datos** (`pg-roles`, `pg-persistence`, `data-restoration`) necesitan una
  fixture aislada y el consentimiento explícito de mutación que hoy los bloquea en seguro. Se les
  da esa fixture o se declaran no automatizables.
- **`runtime-budgets` exige `CI_GIT_SHA` de una corrida de CI real.** Decidir si este repo tiene esa
  CI o si el gate debe medir de otra forma.
- **Cada gate reconstruido se entrega con su mutación en rojo, ejecutada**, igual que en F2a.

**Condición de hecho:** `closeout-evidence.json` refleja el estado real, cada gate vivo tiene un
recibo verificable de una ejecución concreta, y los retirados están fuera con su motivo. El goal
`design-system-nucleo-gobernanza` puede entonces cerrarse o declararse inalcanzable con
fundamento — hoy no se puede hacer ninguna de las dos cosas con honestidad.

**→ Gate 2b.**

### Frente 2 — Móvil (F2a-2b y F2b)

1. **F2a-2b-1** — la red de pruebas sobre las 22 reglas de habilitación de Programación Semanal e
   Intermedia. Plan ya escrito (`2026-08-08-f2a-2b-1-red-de-pruebas-habilitacion.md`) y a medias.
   Caracteriza lo que hoy hacen; **no cambia ni una regla**.
2. **F2a-2b** — el piloto móvil en Programación Intermedia y Semanal, con su evidencia.
3. **Se mide el coste real del piloto**: cuántas horas, cuántos manifiestos, cuántos goldens por
   módulo.
4. **F2b** — los 13 módulos restantes, planificados **con esa cifra en la mano**. El plan de F2b no
   se escribe antes de tener la medición.

**Condición de hecho:** los 15 módulos con evidencia móvil real en los carriles visual y de
accesibilidad; `npm run test:design-system:static` en 8/8.

**→ Gate 3.**

### Frente 3 — Tema claro (F3)

Paleta clara derivada token a token del sistema vigente: cada token oscuro obtiene su pareja clara,
y cada pareja se verifica contra el mismo piso de contraste que rige hoy. Conmutador con preferencia
guardada. Ningún hex suelto, ningún estilo en línea, ninguna variante local en módulo migrado.

**Condición de hecho:** las superficies migradas se ven correctas en claro y en oscuro a 1180×820
con evidencia; el contraste de cada pareja medido, no estimado; el conmutador recuerda la elección
entre sesiones.

**→ Gate 4.**

### Frente 4 — Matriz diagonal (F4)

Los gates adoptan la matriz de D6 del spec de reapertura (tema × viewport) y los candados que hoy
asumen un solo tema y un solo viewport se reinstalan en su forma nueva. Cierra el goal
`reapertura-movil-y-tema-claro` entero.

**Condición de hecho:** el goal cerrado con sus cuatro fases y sin pendientes abiertos sin dueño.

**→ Gate 5.**

### Frente 5 — Publicación

Según `docs/siteground-deploy-routine.md`: respaldo verificable con estrategia de restauración
escrita, pruebas antes que producción, `pull --ff-only`, Composer ejecutado con PHP 8.3, y smoke
funcional de la cascada LPS y del Plan de Compras v2 sobre producción.

Antes del despliegue, **validación completa en staging** del programa entero — es la mitigación de
D2 y no es opcional.

**Condición de hecho:** producción sirviendo el `main` actual; smoke de la cascada y del PDC en
verde contra producción; brecha de commits en cero.

**→ Gate 6 — condición de hecho global del programa.**

## Condición de hecho del programa

Los seis frentes cerrados, y con ellos:

- `goals/reapertura-movil-y-tema-claro/goal.md` en estado cerrado, sus cuatro fases con evidencia.
- `goals/design-system-nucleo-gobernanza/goal.md` y `goals/bi-control-tower-gemini/goal.md` cerrados
  o con su bloqueo resuelto.
- `docs/EXPERIMENTS.md` sin filas abiertas sin dueño.
- `docs/IMPROVE-APP-PLAN.md` con la fase 9 en `done`.
- Producción sirviendo `main`, con brecha cero.
- `memoria/goals/estado.md` y la wiki al día, `npm run test:wiki` en verde.

## Qué significa «gate» en este programa

Los siete gates no son pausas para informar: son **puertas cerradas**. Un frente no está cerrado
cuando su trabajo funciona, sino cuando funciona **y está publicado en `main`**. Mientras un frente
no haya pasado su gate completo, **el siguiente no empieza**.

El procedimiento exacto —verificar, commitear, `fetch`, integrar, **re-verificar**, publicar,
confirmar, anotar— está en `AGENTS.md` §Publicación y es obligatorio en los siete. El paso que más
se salta es el quinto: re-verificar **después** de integrar. Integrar trabajo ajeno puede romper un
verde propio sin tocar el diff de uno, y en el Frente 0 ocurrió dos veces en la misma jornada.

Añadido el 2026-08-10 por instrucción del usuario, tras cerrar el Frente 0.

## Dos reglas de ejecución, heredadas de F2a

Las dos se pagaron caras en la fase anterior y se aplican a todo este programa:

1. **Todo gate se entrega con una mutación que lo pone rojo, ejecutada.** No basta con que pase: hay
   que ver que sabe fallar. La lección de F2a es que el mismo agujero se cerró siete veces porque
   nadie comprobó que el candado mordía.
2. **Todo paso que quite algo de una lista mide qué cobertura pierde**, no solo qué gana. Los seis
   defectos de plan de F2a comparten causa: afirmar el estado de la infraestructura sin medirlo.

## Lo que este programa no cubre

- **La fase 6 del design system** (~2.600 hallazgos estructurales en
  `docs/design-system/audit-baseline.json`) entra al Frente 1 solo como **decisión** — aprobar o no
  el inventario de excepciones justificadas. La campaña de reducción en sí es un programa aparte.
- **Las 32 excepciones re-vencidas a `1.2.0`** no se pagan aquí: vencen con el siguiente bump del
  design system, que este programa no dispara.
- **`src/Legacy/`** no se moderniza. Si un hallazgo cae ahí, se corrige la causa con el cambio
  mínimo, según `AGENTS.md`.

## Autorrevisión de este spec

**Cobertura:** las ocho decisiones cubren las cuatro preguntas del grilleo y las cuatro del segundo
turno. Cada frente tiene condición de hecho propia y verificable.

**Cifras corregidas durante la redacción:** el censo inicial dijo «40 abiertos, 14 con
`decide: usuario`». El recuento exacto sobre `docs/EXPERIMENTS.md` da **39 abiertos y 17 con
`decide: usuario`** (22 ejecutables). Los tres totales de tanda (12 + 13 + 14) suman 39, y los
`decide` por tanda (5 + 4 + 8) suman 17.

**Dependencia declarada:** el plan del Frente 2 fase F2b **no puede escribirse** antes de que el
piloto F2a-2b entregue su medición de coste. Es la única dependencia dura entre planes del programa.

**Riesgo mayor declarado:** D2, arriba, con su mitigación y con el límite de esa mitigación dicho
explícitamente.
