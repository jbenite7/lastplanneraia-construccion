---
capa: fuente
tipo: spec
estado: derogada
fecha: 2026-08-19
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-19-estados-severidad-contrato-design.md
resumen: Estados, severidad y color — el contrato — diseño
---

# Estados, severidad y color — el contrato — diseño

**Frente:** `ds-f1a-estados-severidad`. **Programa:** DS-F1. **Entrada:** DS-F0 (`docs/design-system/auditoria/`)
y el frente `bug-coloreado-severidad` (`goals/bug-coloreado-severidad/`).

**Aprobado por el usuario el 2026-08-18**, bloque por bloque, tras `brainstorming`.

> **Revisión del 2026-08-20 — este spec queda subordinado al contrato de tres niveles.** Al
> integrar para publicar apareció el frente hermano `ds-f1a-estado`, que había publicado un
> contrato de **la misma escala** medido contra 50.966 actividades reales, y los dos se
> contradecían: cuatro niveles contra tres, y filete en todas las filas contra filete solo en el
> 21,3 % que pide algo. **Decisión de Felipe, 2026-08-19, con las dos propuestas delante:** manda
> [[docs/design-system/ds-f1a-escala-estado]] —tres niveles y su vocabulario—, esta maquinaria se
> adapta (filete apagado en `controlado`, Programa General remapeado a los estados reales, con
> `Fuera de Ventana` incluido), y **se coordina con esa sesión antes de tocar superficie
> compartida**. Esta revisión reescribe las secciones normativas bajo ese contrato; las
> mediciones y la procedencia originales se conservan como historia. La ejecución sigue pausada
> hasta esa coordinación (ver `goals/ds-f1a-estados-severidad/goal.md`, §Estado real).

## De dónde sale

El usuario esperaba que la tabla de `/programacion-intermedia` ordenara el color por severidad de
«Crítico» a «Sin problema» y creía que no estaba pasando. El frente `bug-coloreado-severidad` lo
diagnosticó y midió: **no estaba pasando porque nunca se declaró.** La paleta oscura es **nominal, no
ordinal** por decisión medida del 2026-07-28 —ocho anclas, un tinte por matiz, sin eje de intensidad
sobre este canvas—, y el fondo de la fila lo pinta una escalera ordinal
(`--ds-cell-state-*`, `public/css/styles.css:3664-3725`) que **ningún contrato gobierna y ningún test
cubre**, y que contradice el nivel declarado en `state-semantics.json` en 3 de 8 estados.

Medido en la misma jornada: la luminosidad OKLab de esos cinco peldaños cabe en una banda del **9 %**
y está **prácticamente invertida** —`atencion` es el más claro y `critico` el penúltimo más oscuro—.
Los colores se distinguen bien (ΔE-OK ≥ 0,0487, casi el triple del umbral que el propio repo fijó),
pero **no comunican gravedad**, y tres pares de estados pintan idéntico.

## Por qué este frente va aparte de DS-F1

DS-F0 cerró con 68 hallazgos y cinco delegan explícitamente en DS-F1. Al mirarlos caen en **tres
cajones sin relación**: (1) estados, severidad y color; (2) qué significa «cubierto» —cobertura de
escenarios y manifiestos, F0-003 y F0-005—; (3) el catálogo y el segundo sistema de BI —F0-091 y
F0-112—. Solo el primero tiene dirección decidida y publicada; los otros dos tienen cero decisiones.

**Decisión del usuario, 2026-08-18: partirlo.** DS-F1 deja de ser un frente y pasa a nombrar un
programa de tres. Este spec cubre **solo el primer cajón**. F0-012 —«las dos ramas de la leyenda
nombran los mismos siete estados con dos vocabularios distintos»— entra aquí porque es vocabulario de
estados; los otros cuatro hallazgos delegados **no entran**.

## Qué se decide

### El modelo: tres canales, tres trabajos

| Canal | Qué dice | Cómo |
|---|---|---|
| **Color de fondo de la fila** | *Qué* estado es — identidad | Los ocho tintes de `--ds-state-tint-*`, uno por estado. **Sus hex no se tocan** |
| **Filete del borde izquierdo** | *Cuán grave* es — prioridad | **Dos tratamientos dibujados** (Urgente fuerte, Atención medio); en `Controlado` **se apaga: la ausencia es la señal** *(revisión 2026-08-20)* |
| **Orden de las filas** | Desempata dentro de un mismo escalón | Botón en la barra, **apagado por defecto** |

Los **valores exactos** de los dos tratamientos dibujados —grosor en px y brillo— no se fijan
aquí a propósito: se eligen contra la fila real de 24 px de alto (la excepción de densidad de
`PRODUCT.md`) y se validan midiendo, no razonando. Lo que sí fija este spec *(revisado
2026-08-20)* es que sean **dos dibujados más la ausencia** —el modelo del contrato hermano: la
barra dibuja dos niveles y la ausencia dibuja el tercero— y que la diferencia entre ambos se
compruebe en pantalla antes de darla por buena.

**La regla dura que hay que escribir en el contrato y que hoy no existe:**

> **Ningún canal codifica dos cosas.** Un canal, un eje.

Es exactamente lo que se rompió: el fondo intentaba decir gravedad e identidad a la vez, y acabó sin
decir ninguna de las dos. La regla es el entregable más duradero de este frente; el CSS es su
consecuencia.

### Por qué la gravedad sale del color

Descartado teñir la fila por gravedad, que era lo que el usuario esperaba al abrir el frente. **Cuatro**
de los ocho estados de Intermedia quedan en `urgent` con los niveles decididos —eran tres cuando se
tomó esta decisión, y el cambio la refuerza—, así que en obra real eso no son cuatro filas sino media
tabla: **un muro rojo no comunica gravedad, la anula.** `PRODUCT.md` lo prohíbe por su nombre en las
anti-referencias («no debe verse decorativa, **saturada de alertas**»). Además obligaba a rehacer la
paleta oscura entera y empeoraba por debajo de 1180 px, alcance que Intermedia ganó el 2026-08-14.

El grosor **sí** es una escala ordinal real, y el orden —lo más grave arriba— es la cascada más
fuerte que existe sin gastar un solo color.

**El filete no es una invención.** Ya es idioma del producto: `admin/public/css/admin-custom.css:85`
documenta que en la bitácora «la severidad se lee por el filete lateral», y `primitives.css:25` ya
publica la forma (`border-inline-start`). Este frente **extiende el componente `state` del catálogo**;
no crea una familia nueva.

### Los tres niveles y el nivel de cada estado *(revisado 2026-08-20)*

Se adopta el eje de **tres niveles** de [[docs/design-system/ds-f1a-escala-estado]] (`Urgente`,
`Atención`, `Controlado`; los estados sin gravedad declaran nivel nulo y tampoco dibujan barra).
El eje de cuatro valores de `state-semantics.json` (`urgent`, `attention`, `healthy`, `neutral`)
**no se deroga aquí** —sanearlo es decisión pendiente del usuario, declarada en el propio contrato
hermano—: se **proyecta**: `urgent` → Urgente (filete fuerte) · `attention` → Atención (filete
medio) · `healthy` y `neutral` → Controlado (**sin filete**). Las correcciones de nivel que
siguen se conservan tal como se decidieron sobre ese eje.

Nivel de los ocho estados de Programación Intermedia, cerrado el 2026-08-18:

| Nivel | Estados |
|---|---|
| `urgent` | RC inicio vencido · Inicio vencido · **Inicio por Habilitar** · **En Ejecución Pendiente** |
| `attention` | **Alistamiento Urgente** · Alistamiento en Riesgo |
| `healthy` | **Alistamiento Pendiente** · Listo para Comprometer |

**Procedencia, que se conserva a propósito** (detalle en
`goals/bug-coloreado-severidad/respuestas-ds-f1.md`): cuatro no estaban en disputa; **«En Ejecución
Pendiente» lo decidió el usuario**; los tres en negrita restantes **los propuse yo y él los
confirmó**. Borrar esa distinción convertiría mi criterio en el suyo sin dejar rastro.

**Cuatro estados cambian de nivel**, así que este frente **edita `state-semantics.json`**:

| Estado | Antes | Ahora |
|---|---|---|
| `blocked-due` (Inicio por Habilitar) | attention | **urgent** |
| `alert-1-week` (Alistamiento Urgente) | urgent | **attention** |
| `alert-4-6-weeks` (Alistamiento Pendiente) | attention | **healthy** |
| `execution-blocked` (En Ejecución Pendiente) | attention | **urgent** |

`execution-blocked` **revierte una ratificación del propietario del producto del 2026-08-03 anotada
dentro del propio archivo**, advertida antes de decidir.

> **Corrección del 2026-08-18, en la misma sesión.** Este spec decía «tres estados cambian» y lo
> repetí varias veces de viva voz. Son **cuatro**: se me caía `blocked-due`, que el usuario subió a
> `urgent` junto con los otros. Contado contra el archivo, no de memoria. La cifra importa porque es
> el tamaño del cambio de contrato, y cuatro de ocho es la mitad del módulo.

### Consecuencia aceptada: cuatro de ocho en el escalón de arriba

Con estos niveles, la mitad de los estados de Intermedia llevan el filete máximo, y un escalón que
contiene media tabla no prioriza por sí solo. **Lo resuelven los otros dos canales:** los cuatro
llevan colores de fondo distintos —identidad— y el botón de agrupar los desempata cuando el usuario
lo pide.

*(Nota de la revisión 2026-08-20)*: con la proyección a tres niveles, en Intermedia **seis de ocho
estados dibujan barra** — la proporción inversa a la del contrato hermano, donde la ausencia es
mayoría y por eso es señal. La escasez que hace legible la barra en Programa General no existe
aquí; si al medir en pantalla la tabla se lee saturada, la salida es material de la coordinación
pendiente, no una decisión unilateral de este frente.

**Corolario operativo, y es una dependencia real:** implementar el filete **sin** el botón de orden
no cumple esta decisión, aunque lo parezca.

## Alcance

**Las cuatro superficies de tabla que declaran matiz por estado**, decidido por el usuario:

| Superficie | Estados | Encaje |
|---|---|---|
| `/programacion-intermedia` | 8 | Exacto: ocho estados, ocho matices. **Se diseña y valida aquí primero** |
| `/programa-general` | 7 | Encaja |
| `/plan-compras` (pdc) | 7 declarados | **NO APLICA — ninguna superficie los pinta** (ver abajo) |
| `/programacion-semanal` | 10 → **5 + 5** | Encaja por fase (ver abajo) |

**Programación Semanal cabe, contra lo que parecía.** Sus diez estados son **cinco por fase**:
`stateMachine.js:58` resuelve `calificacion` si la semana está confirmada y `programacion` si no, así
que las dos mitades **nunca conviven en pantalla**. El trabajo real es **desempatar dos parejas** que
hoy comparten ámbar —«Condiciones Pendientes» con «Por Comprometer» en Programación, «Incumplida» con
«Sin Calificar» en Calificación—, y hay cuatro matices libres en cada fase. Las colisiones
**entre** fases son inocuas y la excepción `KNOWN_HUE_COLLISIONS` de
`tests/design-system/state-tint-ladder.test.mjs` se reescribe para reflejar eso, no se borra.

> **Corrección del 2026-08-19, medida al ejecutar la Task 6.** Este spec dio `/plan-compras` por
> dentro del alcance diciendo que «sus siete fondos ya consumen la paleta vía `--pdc-*-bg`». **Es
> falso, y el error es mío: leí el contrato sin comprobar que algo lo pintara.** Medido:
>
> - Las siete etiquetas del módulo `pdc` **no aparecen en ninguna vista ni componente** del repo,
>   solo en la prosa del laboratorio (`views/design-system/families/states-feedback.php:75`).
> - Los tokens `--pdc-*-bg` los consumen `#tabla_excepciones_no_autoprogramadas` y
>   `#formulario_nuevo` (`public/css/styles.css:2358, 2382`), que son superficies de
>   **/programacion-semanal**, no de Compras.
> - El módulo `pdc` es el único de los grandes cuyos estados **no declaran `key`** — justo el campo
>   que existe para unir contrato y renderer. No lo declaran porque no hay renderer.
> - La única columna «Estado» viva en `/plan-compras` (`Seguimiento.tsx`) usa tres valores de texto
>   plano sin color.
>
> **`/plan-compras` sale del alcance como no aplicable.** Quedan tres superficies, no cuatro.
>
> Y queda un hallazgo que este frente no resuelve: **el contrato declara siete estados que no pinta
> nadie**, y el guard `state-tint-ladder` los da por buenos porque comprueba que los tokens nombren
> la paleta —una declaración contra sí misma, la trampa que la wiki ya tiene fichada como
> `guard-valida-declaracion-contra-si-misma`—. Es entrada para el cajón de DS-F1 que decide qué
> significa «cubierto».

Los módulos que declaran nivel pero **no** matiz (`auth`, `bi`, `control-cambios`, `dashboard`,
`profesionales`, `subcontratistas`) **quedan fuera**: no son tablas de estado y el modelo no les
aplica hoy.

## Lo que NO entra

- **Los otros dos cajones de DS-F1**: cobertura de escenarios y manifiestos (F0-003, F0-005), y el
  catálogo frente al segundo sistema de BI (F0-091, F0-112). Son frentes hermanos.
- **Cambiar los hex de los ocho tintes.** El modelo existe precisamente para no tener que tocarlos.
- **Rehacer el tema oscuro.** Al no llevar la gravedad al fondo, deja de hacer falta.
- **`states-feedback.css:162`**, la excepción crítica que `legacy-bridge.css:104-142` deja en letra
  muerta. Está diagnosticada y **se decide aparte**: activarla colapsaría los cuatro estados `urgent`
  de Intermedia en un solo fondo, que es lo contrario de este diseño.
- **El `#fef3c7` embebido en `hot.js:2857`.** Anotado en el diagnóstico, fuera de alcance.

## Posture

- **No tocar los hex de `--ds-state-tint-*`.** Ocho anclas, medidas, cerradas por test.
- **No regenerar ningún golden sin aprobación visual explícita del usuario**, pedida por su nombre y
  para esa captura. Bloqueo incondicional.
- **No cambiar lo que mide una prueba para que pase.** Cuando un test deba cambiar porque el contrato
  cambió —y aquí varios deben—, se cambia **declarándolo** y explicando qué mide ahora.
- **No ampliar a superficies fuera de las cuatro.**
- **Sin dependencias nuevas.**
- **No arreglar «de paso»** nada del inventario de DS-F0 que no sea de estados.

## Leer primero

- `goals/bug-coloreado-severidad/diagnostico.md`, `insumo-ds-f1.md` y `respuestas-ds-f1.md` — el
  diagnóstico medido y la dirección, con su procedencia.
- `docs/design-system/state-semantics.json` — el contrato que este frente edita.
- `tests/design-system/state-tint-ladder.test.mjs` — la paleta nominal y por qué lo es.
- `tests/browser/ops-state-chip-hue.mjs` y `tests/design-system/ops-state-contract.test.mjs` — los
  dos guards que leen el contrato.
- `public/css/styles.css:3664-3725` — el mapeo sin contrato que este frente sustituye.
- `PRODUCT.md` §Anti-references y §Design Principles.
- `DESIGN.md` y `docs/design-system/README.md`.

## Condición de hecho

1. `docs/design-system/state-semantics.json` declara la **regla de un canal por eje** y el nivel
   corregido de los ocho estados de Intermedia.
2. Existe la primitiva del filete en la capa de componentes, con su ficha en el catálogo, sus
   tokens, sus **dos tratamientos dibujados y el apagado en `Controlado`** *(revisión 2026-08-20)*.
3. Las tres superficies vigentes (tras la salida de `/plan-compras`) pintan **identidad en el
   fondo** y **gravedad en el filete solo donde el estado pide algo**, medido con color computado
   —nunca declarado— a 1180×820 dark, sesión real por la puerta de servicio. Programa General
   pinta **los estados reales del contrato hermano**, `Fuera de Ventana` incluido.
4. El botón de agrupar por gravedad existe, arranca apagado, y se puede volver al orden del programa.
5. Programación Semanal no tiene dos estados con el mismo matiz **dentro de una misma fase**.
6. **Verificación:** `bash scripts/publicar.sh --solo-verificar` en verde, y los goldens de las
   superficies vigentes conciliados con aprobación explícita anotada.
7. **Coordinación previa** *(revisión 2026-08-20)*: nada de esto toca superficie compartida sin
   coordinarse antes con la sesión del contrato hermano — es la tercera parte de la decisión de
   Felipe del 2026-08-19, y el precedente de saltársela ya costó un frente entero sin publicar.

## Riesgos y reversas

- **El golden de cuatro superficies se mueve a la vez.** Es el riesgo mayor y la razón de que el plan
  vaya por fases: Intermedia primero, sola, para que el usuario pueda parar ahí.
- **Editar el contrato rompe dos guards a la vez** (`ops-state-contract`, `state-tint-ladder`). Se
  cambian con el contrato en la misma tanda y declarando qué miden ahora, nunca después para que pase.
- **El botón de orden es capacidad nueva**: `columnSorting: false` hoy en Intermedia. Si resulta más
  caro de lo previsto, **el filete sin el orden no cumple** — se para y se consulta, no se entrega a
  medias.
- **`--ds-cell-state-*` queda huérfana** en cuanto el fondo pase a identidad. No se borra en este
  frente: se anota quién más la consume antes de tocarla.
- **Reversa:** cada fase es un commit propio sobre tokens y contrato; volver atrás es revertir la
  fase, no desmontar el modelo.

## Archivos de este goal
- [[goals/ds-f1a-estados-severidad/goal]] · [[goals/bug-coloreado-severidad/respuestas-ds-f1]]
- [[docs/superpowers/specs/2026-08-19-bug-coloreado-severidad-design]]

---

## Estado verificado — derogada

Verificado contra el código el 2026-08-25. La decisión que contiene **dejó de ser cierta**; el documento se conserva con su lápida.

**Qué dejó de ser cierto:** la propia spec:18 se declara subordinada al contrato de tres niveles. La sustituye docs/design-system/ds-f1a-escala-estado.{md,json}

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
