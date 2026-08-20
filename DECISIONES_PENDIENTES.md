---
capa: fuente
tipo: decision
estado: vigente
fecha: 2026-08-19
areas: [design-system]
fuente: cola de pendientes del trabajo de estados, severidad y color del 2026-08-19
resumen: "Las decisiones de producto, proceso y diseño que la cola de estados y severidad dejó abiertas, cada una con su medición ya hecha"
project: lps-aia
type: decision
status: abierto
updated: 2026-08-19
---

# Decisiones pendientes — estados, severidad y color

**Qué es esto.** La cola del 2026-08-19 traía siete pendientes; tres o cuatro **no son trabajo, son
decisiones de Felipe**. Este documento las deja listas para decidir: cada una con la medición hecha,
las opciones reales, la consecuencia de cada una y una recomendación con su porqué. Lo técnico se
decidió y ejecutó sin preguntar; aquí solo queda lo que es de producto, proceso o diseño.

**Cómo leerlo.** Cada decisión es independiente salvo donde se diga. El orden es el de la cola, no
el de importancia. Las mediciones son de esta jornada, contra la base de **desarrollo**.

**El modelo que gobierna todo esto** (publicado en `c766a338`): tres canales, un eje cada uno — el
**color de fondo** dice QUÉ estado es (identidad), el **filete del borde** dice CUÁN GRAVE (nivel),
y el **orden** desempata. La regla vive en `axisRules` dentro de
`docs/design-system/state-semantics.json`.

---

## D-1 · ¿El contrato debe declarar los realces por condición del dato?

**RESUELTA por Felipe el 2026-08-20: opción (b).** La ruta crítica de Semanal entra al contrato como realce declarado; `r0` NO recibe matiz — sus 4.384 filas se atienden con columna o filtro, porque la pregunta que responden es de trabajo, no de lectura de tabla.

**Estado: RESUELTA (2026-08-20).** Es una sola pregunta con dos casos detrás, y las mediciones dicen que **no son
gemelos**.

### El caso de Programa General: `r0`

`getRestrictionAlertKey` (`public/js/modules/programa_general/hot.js:744`) deriva cuatro cubos
—`r0`, `r1`, `r2-3`, `r4-6`— de las filas que tienen **restricciones duras sin cumplir** y no están
ejecutadas, repartiéndolas por cuántas semanas faltan para su inicio. `r0` significa «debió iniciar
y sigue detenido», y llegó a tener **el único ancla propia de toda la escala**
(`--ds-cell-state-bloqueado-bg`). Hoy está aplanado en ámbar.

**Medido sobre 65.633 filas de `programa_consolidado`:**

| Cubo | Filas |
|---|---:|
| `r0` — debió iniciar y sigue detenido | **4.384** |
| `r1` — inicia en 1 semana | 1.347 |
| `r2-3` | 2.928 |
| `r4-6` | 4.584 |
| con restricciones sin cumplir, en total | 39.320 |
| de esas, **fuera** de los cuatro cubos (>6 semanas o sin dato) | 26.077 |

**El dato que de verdad decide** — qué estado declarado llevan las filas de cada cubo:

| Cubo | Estados que cruza |
|---|---|
| **`r0`** | Atrasada 3.199 · Debe Iniciar 1.047 · En Curso 111 · Fuera de Ventana 18 · Actividad Futura 9 |
| `r1` | Actividad Futura 1.159 · Debe Iniciar 150 · En Curso 32 · Fuera de Ventana 6 |
| `r2-3` | Actividad Futura 2.879 · En Curso 35 · Fuera de Ventana 7 · Debe Iniciar 5 · Atrasada 2 |
| `r4-6` | Actividad Futura 4.543 · En Curso 32 · Fuera de Ventana 9 |

**`r0` no es un estado: es un cruce.** Atraviesa cinco estados distintos. Si se le devolviera un
matiz propio —el `teal` que está libre—, ese color estaría diciendo «esto es un estado» sobre filas
que ya tienen cinco identidades diferentes, y el canal del matiz dejaría de significar lo que el
modelo dice que significa. **La conclusión no es que `r0` no importe: es que no cabe en el canal del
color de fondo.**

### El caso de Semanal: la ruta crítica

`getAlertClassForRow` (`public/js/modules/programacion_semanal/hot.js:1174`) devuelve
`ps-alert-critical-route` cuando el estado es `prog-ejecucion-con-restricciones` **y** `Critica >= 1`.
Se conservó a propósito para no regresionar.

**Medido con el mismo JOIN que usa la API real** (`SemanalApiController::list()`, que une las
restricciones desde `programa_consolidado` por `unique_id`):

| | Filas |
|---|---:|
| activas e incompletas en fase Programación | 249 |
| en estado `prog-ejecucion-con-restricciones` | 66 (26,5 %) |
| **de esas, realzadas por `Critica >= 1`** | **10** (15,2 % del estado, 4,0 % del total) |
| en ruta crítica en toda la fase, para comparar | 65 |

Aquí el realce **sí es un subconjunto de un solo estado**, no un cruce. Y hay un dato más, medido al
cerrar `semanal-fondo-por-matiz`: **en Semanal no existe análogo de «detenido por otro»** — ese
concepto no aparece en `WEEKLY_ALERT_MODEL` ni en `stateMachine.js`, en ninguna de sus dos fases.

### Las opciones

| | Qué implica | Consecuencia |
|---|---|---|
| **(a) El contrato declara los realces, con canal propio** | `state-semantics.json` gana un concepto nuevo: «realce por condición», que no consume matiz sino un cuarto recurso visual (trama, icono, marca en el borde) | Honesto con el modelo. Cuesta diseñar ese cuarto recurso y probarlo en las dos pantallas |
| **(b) El contrato declara solo los realces que son subconjunto de UN estado** | La ruta crítica de Semanal entra al contrato; `r0` no, porque cruza cinco estados | Barato y coherente. Deja `r0` sin representación: las 4.384 filas siguen aplanadas en ámbar |
| **(c) El contrato no declara realces** | Se retira la ruta crítica de Semanal y `r0` se queda como está | El más simple. Pierde información que hoy se ve, sobre 10 filas |

### Recomendación: **(b)**, y `r0` por otra vía

La ruta crítica de Semanal es un realce legítimo —un subconjunto de un estado, 10 filas de 249— y
declararlo cuesta poco. `r0` no cabe ahí, pero **4.384 filas detenidas no son ruido**: lo que pide
no es color, es una **columna o un filtro** que las liste, porque la pregunta que responde («¿qué
está detenido y debió arrancar?») es de trabajo, no de lectura de tabla.

**Qué NO haría: devolverle a `r0` su ancla `teal`.** Es la opción que parece más justa —recuperar
algo que se perdió— y es la que rompe el modelo: pintaría del mismo color filas que son Atrasada, En
Curso y Actividad Futura a la vez, y el color de fondo dejaría de ser fiable para todos los demás
estados, no solo para éste.

---

## D-2 · La excepción crítica del chip: ¿se retira o se activa?

**RESUELTA por Felipe el 2026-08-20: retirar la excepción de la capa canónica.** No se sube su especificidad: activarla fundiría nueve estados en tres rojos, medido en Intermedia, Semanal y PDC.

**Estado: RESUELTA (2026-08-20).**

### Qué pasa hoy

`public/css/design-system/components/states-feedback.css:162` declara una excepción: cuando un chip
es `severity="high"` y `urgency="now"`, su fondo deja de ser el matiz y pasa al rojo crítico. El
comentario que la acompaña la razona bien —«el nivel crítico es el único que no admite ambigüedad».

**Nunca se aplica.** `public/css/design-system/adapters/legacy-bridge.css:104-142` reafirma matiz y
nivel dentro de la capa `legacy-overrides`, que va después de `components`. Las dos familias usan
`:where(...)`, así que pesan 0,0,0 y **decide el orden de fuente**: el matiz va último y gana
siempre. Reproducible con `node goals/bug-coloreado-severidad/evidence/sonda-reglas-chip.mjs`, que
lista las reglas por capa y enseña el matiz ganando en cada estado.

### Dos cosas que hay que saber antes de «arreglarlo»

**1. Hay un guard que afirma lo contrario y está en verde porque la excepción es inerte.**
`tests/browser/ops-state-chip-hue.mjs` exige que «el color pintado sea el de la escalera para ese
matiz» — justo lo que la excepción rompería. El guard y la regla se contradicen; uno de los dos
sobra.

**2. Activarla empeoraría tres pantallas, no una.** Medido contra `state-semantics.json`:

| Módulo | Estados `urgent` | Matices que hoy los distinguen | Al activarla |
|---|---:|---|---|
| **Programación Intermedia** | 4 | red, orange, violet, blue | **1 solo rojo** |
| **Programación Semanal** | 3 | red, orange, red | **1 solo rojo** |
| **Plan de Compras** | 2 | red, orange | **1 solo rojo** |

**Nueve estados fundidos en tres.** Es exactamente lo contrario del modelo publicado, donde el matiz
existe para desempatar dentro de un mismo nivel.

### Recomendación: **retirar la excepción de la capa canónica**

No es una regla que «todavía no funciona»: es una regla cuyo efecto, si funcionara, ya sabemos que
sería malo. Retirarla deja el código diciendo lo que la pantalla hace, y el guard pasa a vigilar algo
real en vez de estar en verde por accidente.

**Qué NO haría: subir su especificidad para que gane.** Es la lectura obvia del hallazgo —«una regla
que no aplica, hagámosla aplicar»— y es la que rompe las tres pantallas de golpe.

**Si aun así quieres que el nivel crítico se distinga más**, el camino que no rompe nada es
reforzarlo por un canal que esté libre: el filete ya lo hace, y se le puede dar más peso ahí sin
tocar el fondo.

---

## D-3 · Los estados declarados que nadie pinta: ¿se implementan o se retiran?

**RESUELTA por Felipe el 2026-08-20: (c) para los 11 sin rastro, (a) para PDC.** Los aspiracionales pasan a sección no vinculante; PDC se implementa porque tiene pantalla viva esperando. Felipe preguntó expresamente por el impacto en PDC y ratificó esta parte sabiendo que su columna «Estado» gana color en DS-F2, con frente propio y verificación en pantalla.

**Estado: RESUELTA (2026-08-20).** La parte técnica —qué debe medir el guard— **ya está resuelta y en el repo**
(`tests/design-system/state-key-consumption.test.mjs`). Lo que queda es tuyo: qué se hace con lo que
el censo destapó.

### El censo, medido sobre los 55 estados de `state-semantics.json`

| | Estados |
|---|---:|
| con `key` — comprobables, y **los 25 tienen consumidor real** | 25 |
| **sin `key`** — no se pueden comprobar en absoluto | **30** |
| de esos 30, sin rastro ni siquiera por su etiqueta | 11 |

**El problema no era de Plan de Compras.** Lo que se veía como «siete estados de PDC que nadie
pinta» resultó ser un patrón: **siete de los diez módulos no declaran `key`** — auth, bi, pdc,
control-cambios, dashboard, profesionales y subcontratistas. Los tres que sí la declaran son los de
programación, y ahí no hay ni un huérfano.

Los cuatro estados de PDC cuya etiqueta sí aparece en el repo, aparecen **solo en la prosa del
laboratorio** (`views/design-system/families/states-feedback.php`). Ninguna pantalla real. Y la única
columna «Estado» viva en `/plan-compras` (`pdc-app/src/pages/Seguimiento.tsx`) usa tres valores de
texto plano, sin color.

### La respuesta a «¿qué debe medir el guard?» — ya implementada

Un estado sin `key` **no aparece como incumplido: simplemente no aparece**. Por eso el guard nuevo
vigila dos cosas, y no una:

1. **Que ningún estado nuevo nazca sin `key`.** La deuda de hoy queda congelada en
   `docs/design-system/state-key-debt.json` — visible y cerrada por arriba, nunca autorizada.
2. **Que todo estado con `key` tenga al menos un consumidor en el código.** Es lo que habría cazado
   el caso de PDC si PDC declarara claves.

Comprobado que **puede ponerse rojo**: con dos estados de sabotaje —uno sin clave, otro con una clave
que nadie consume— devuelve `RC=1` y los nombra. Verde otra vez al retirarlos.

### Lo que queda por decidir

Los 30 estados sin clave, y en particular los 11 sin rastro alguno:

| | Qué implica |
|---|---|
| **(a) Implementarlos** | Cada módulo declara `key` y pinta sus estados. Es el camino completo y el más caro: toca siete módulos |
| **(b) Retirar del contrato lo que no se pinta** | El contrato queda diciendo la verdad. Se pierde la intención documentada de estados que quizá se quieran algún día |
| **(c) Separar contrato de catálogo** | Los estados con renderer viven en `moduleMappings`; los aspiracionales pasan a una sección declarada como no vinculante |

### Recomendación: **(c) para los 11 sin rastro, (a) para PDC**

Los once que no dejan rastro —auth, bi y dashboard, más tres de PDC— son intención, no producto:
mantenerlos mezclados con los que sí se pintan es lo que hace que el contrato parezca cubrir más de
lo que cubre. Separarlos cuesta poco y deja de mentir.

PDC es distinto: **tiene una pantalla viva con una columna de estado sin color**. Ahí sí hay producto
esperando al contrato, y es el único de los siete módulos donde implementar tiene un destino claro.

**Qué NO haría: retirar los siete de PDC en bloque.** Es lo más rápido y borraría el único caso donde
la declaración iba por delante del código a propósito.

---

## D-4 · DS-F1, cajón 2: qué significa «cubierto»

**RESUELTA por Felipe el 2026-08-20: las tres partes como se recomendaron.** (1) `foundation-shell` cubre el armazón compartido con UN escenario, no veinte. (2) `/` y `/dashboard` se declaran; `/reportes/{tipo}` se declara una vez como plantilla. (3) Las 5 rutas muertas se retiran del contrato.

**Estado: RESUELTA (2026-08-20).** La parte técnica —**el guard que pone en rojo lo descubierto**— ya está en el
repo (`tests/design-system/coverage-closure.test.mjs`). Lo que queda es qué se hace con la brecha.

### El censo, medido contra `public/index.php`

| | |
|---|---:|
| pantallas reales (rutas GET, sin APIs ni assets) | 32 |
| **sin manifiesto** — `/`, `/dashboard`, `/reportes/{tipo}` | **3** |
| rutas declaradas que **ya no responden a GET** | 5 |
| manifiestos con rutas y **cero escenarios** | 1 |

Ese único manifiesto sin escenarios es `foundation-shell.json`, y no es cualquiera: **declara 20
rutas, el 37 % de todas las rutas declaradas del sistema**. Es el que más abarca y el único que no
prueba nada.

En total el sistema declara 39 escenarios, pero **20 son del laboratorio**: quedan 19 escenarios
repartidos entre las 32 pantallas del producto.

### Lo que estaba realmente roto

No era «hay cosas sin cubrir» — eso se sabía. Era que **lo descubierto no podía ponerse rojo**. Los
guards de manifiesto comprueban que cada manifiesto sea válido, que sus escenarios existan y que sus
hashes cuadren: todo sobre lo que **ya está declarado**. Una pantalla que nadie declaró no incumple
ninguno, porque para el sistema de diseño no existe.

El guard nuevo mide las tres cosas que faltaban: que toda pantalla real esté declarada, que todo
manifiesto con rutas tenga al menos un escenario, y que la deuda congelada siga siendo cierta —una
tolerancia que sobrevive al problema que toleraba miente por omisión. Comprobado que **puede ponerse
rojo**: al quitar `/dashboard` de la deuda devuelve `RC=1` y lo nombra.

### Lo que queda por decidir

**(1) `foundation-shell` y sus 20 rutas sin escenario.** Es el cajón grande. Un escenario por ruta es
caro; un escenario que cubra el armazón compartido (barra, drawer, cabecera) puede bastar para lo que
ese manifiesto realmente gobierna. **Recomiendo lo segundo**: el armazón es una sola cosa repetida en
20 sitios, y probarla 20 veces mide lo mismo veinte veces.

**(2) Las 3 pantallas sin manifiesto.** `/` y `/dashboard` son producto y deberían tenerlo;
`/reportes/{tipo}` es paramétrica y habría que decidir si se declara una vez o por tipo.

**(3) Las 5 rutas declaradas que ya no responden a GET.** Son deuda al revés: el contrato promete
más de lo que hay. **Recomiendo retirarlas** — son POST o desaparecieron, y ninguna decisión de
producto depende de ellas.

**Qué NO haría: exigir un escenario por ruta de golpe.** Serían 20 escenarios nuevos solo en
`foundation-shell`, y el resultado predecible es que alguien los declare vacíos para pasar el gate
—que es exactamente el problema que este cajón vino a cerrar.

---

## D-5 · DS-F1, cajón 3: el segundo sistema de estilos de BI

**RESUELTA por Felipe el 2026-08-20: (a).** Se añade la variante `tabs` a `navigation` — hay consumidor esperándola. Promover las utilidades a capa compartida queda como pregunta aparte, no se decide hoy. Verificado antes de decidir: **esto no toca el PDC** (`bi-utilities` solo lo carga `views/bi/_layout.php`; `bi-tabs-nav` solo vive en `views/bi/_nav.php` y `bi-spa.js`; la SPA no consume ninguno).

**Estado: RESUELTA (2026-08-20).** La pregunta del cajón era: *¿hay primitivas que el catálogo NO tiene y por eso
los módulos improvisan?* **La respuesta medida es sí, pero solo una — y no la que se esperaba.**

### Qué son en realidad las 88 utilidades

Las clasifiqué todas. **Ninguna es un componente**: son maquetación atómica.

| Familia | Cuántas |
|---|---:|
| `text-*` (tamaño y color de texto) | 13 |
| `h-*`, `w-*`, `max-*`, `min-*` | 15 |
| `flex-*`, `items-*`, `justify-*`, `gap-*`, `grid-*` | 14 |
| `m*-`, `p*-` (espaciado) | 17 |
| resto (`overflow`, `rounded`, `truncate`, `z-*`, …) | 29 |

No compiten con el catálogo `aia-*`: **juegan en otro eje**. El catálogo da componentes —botón,
chip, campo, tabla, navegación—; esto da espaciado, tamaño y alineación. Un módulo puede usar las
primitivas correctas y **seguir necesitando** `gap-2` y `px-4`.

Por eso `bi-utilities.css` fue una victoria y no un descuido: sustituyó al Play CDN de Tailwind, que
inyectaba sin capa y derrotaba a las nueve capas del sistema. Lo que queda es que esa capa exista
**solo para BI** y con vocabulario de Tailwind.

### El caso F0-112, mirado de cerca

`views/bi/_nav.php` declara cero primitivas y cinco utilidades por pestaña. Al abrirlo aparecen **dos
problemas distintos, y conviene no confundirlos**:

1. **Disciplina.** El catálogo **sí tiene** un componente `navigation`, y `_nav.php` no lo usa: monta
   el carril con una clase propia, `bi-tabs-nav`.
2. **Carencia real.** Las siete variantes de `navigation` son `brand-lockup`, `navbar`, `contextual`,
   `drawer`, `account`, `sidebar` y `collapsed`. **Ninguna es un carril de pestañas.** Aunque
   `_nav.php` quisiera hacerlo bien, no tiene qué usar.

Así que la respuesta no es «es indisciplina» ni «falta catálogo»: **son las dos, y son separables**.

### Lo que queda por decidir

| | Qué implica |
|---|---|
| **(a) Añadir una variante `tabs` a `navigation`** | Cierra la carencia. `_nav.php` pasa a consumir catálogo y deja de improvisar el carril |
| **(b) Promover las utilidades a capa compartida** | El resto de módulos deja de reinventar espaciado. Es un cambio de alcance grande: hoy es una hoja de BI y pasaría a ser parte del sistema |
| **(c) Dejarlo como está y declararlo** | El contrato dice que BI tiene su capa propia, y se acaba la ambigüedad |

### Recomendación: **(a) ahora, (b) como pregunta aparte**

La variante de pestañas es concreta, tiene un consumidor esperándola y cierra un hallazgo real.
Empezaría por ahí.

Lo de las utilidades compartidas es una decisión de sistema, no de BI, y merece su propio momento:
promoverlas significa que el sistema de diseño adopta un vocabulario de maquetación tipo Tailwind, y
eso cambia cómo se escribe todo lo demás.

**Qué NO haría: retirar `bi-utilities.css`.** Es la lectura natural del hallazgo —«hay dos sistemas,
quitemos uno»— y reabriría exactamente lo que resolvió: sin esa hoja, BI vuelve al Play CDN o a estilos
sueltos, y ninguno de los dos respeta las capas. Y un guard que exigiera primitivas sin que exista la
variante de pestañas solo produciría incumplimiento declarado.

---

# Segunda tanda — los frentes que quedan abiertos

Estas cuatro salen del repaso de **todos** los specs y frentes del proyecto (2026-08-19). De los 13
`goals` sin cerrar, siete se cerraron con verificación de hoy y **seis quedan**: dos están
encadenados a una corrida de CI y los otros cuatro son tuyos.

## D-6 · `vocabulario-estados-cascada`: el número ya se cumplió, ¿y la consistencia?

**RESUELTA por Felipe el 2026-08-20: (b).** El objetivo del frente pasa de número a consistencia: un solo estilo de mayúsculas y géneros coherentes. Sin riesgo de dato — son cadenas de interfaz.

**Estado: RESUELTA (2026-08-20).** El frente está «en replanteo» desde que lo pediste (D-VOC-1), y mientras tanto
**su objetivo numérico se cumplió solo**.

La spec fijaba **35 → 29 cadenas distintas** que un usuario de obra puede ver en la cascada. Medido
hoy sobre `state-semantics.json`: **25**. Bajaron sin que este frente se ejecutara —
`contrato-estados-modulo-fantasma` retiró un módulo entero con seis estados, y el remapeo de hoy
quitó `Con Alerta Restricciones`.

**Lo que sí queda, y es lo que el número nunca midió — la consistencia:**

| Defecto | Medido |
|---|---|
| Estilo de mayúsculas mezclado | **21 en Title Case, 4 en estilo frase** (`Ejecución con restricciones`, `Inicio vencido`, `RC con restricciones`, `RC inicio vencido`) |
| Género inconsistente para el mismo gesto | `Lista para Confirmar` / `Listo para Comprometer` |

| | Qué implica |
|---|---|
| **(a) Cerrarlo por objetivo cumplido** | Se firma con la medición y se abre otro para consistencia, si se quiere |
| **(b) Redefinir el objetivo a consistencia** | El frente sigue vivo con una condición nueva: un solo estilo de mayúsculas y géneros coherentes |
| **(c) Cerrarlo y no hacer nada más** | 25 términos con cuatro excepciones de estilo es tolerable |

**Recomendación: (b).** Es trabajo de una tarde, toca solo etiquetas y **no hay riesgo de dato**: son
cadenas de interfaz, no valores guardados. Y el número solo, sin consistencia, no era el problema que
el frente venía a resolver.

## D-7 · `bi-control-tower-gemini`: una condición que no se puede cumplir

**RESUELTA por Felipe el 2026-08-20: (a).** La condición se recorta a los tres modos dark. Lo de `linen` vive dentro de D-9, no bloqueando un dashboard que ya funciona.

**Estado: RESUELTA (2026-08-20)**; llevaba mes y medio parada. Su condición pide **aprobación visual de seis modos**
(Mobile/Tablet/Desktop × Dark/Linen), y **tres de esos seis son del tema `linen`, retirado del
producto el 2026-07-25 por DS-030**. Nadie puede aprobar capturas de un tema que ya no existe.

No es un frente parado por falta de trabajo: **está parado por una condición imposible**.

| | Qué implica |
|---|---|
| **(a) Recortar la condición a los tres modos dark** | Se puede cerrar esta semana. Es lo que el producto realmente tiene |
| **(b) Reconstruir `linen` primero** | No hay conmutador de tema: trabajar en claro significa **rehacerlo**, no reactivarlo. Es un frente propio y grande — de hecho es la fase F3 de D-9 |
| **(c) Archivar el frente** | El dashboard queda sin la validación visual que su goal pedía |

**Recomendación: (a).** Y que la parte de `linen` viva donde le corresponde: dentro de la decisión
del tema claro (D-9), no bloqueando un dashboard que ya funciona en dark.

## D-8 · `design-system-nucleo-gobernanza`: la condición envejeció con el artefacto

**RESUELTA por Felipe el 2026-08-20: (a).** La condición se reescribe contra los nueve gates reales de `closeout-evidence.json`.

**Estado: RESUELTA (2026-08-20).** Su condición de hecho exige que **«los quince gates exactos de
`closeout-evidence.json` tengan evidencia fresca y estado `passed`»**.

**Ese archivo declara hoy nueve gates, no quince.** La condición no es difícil de cumplir: es
imposible de leer, porque cuenta algo que ya no existe con ese número. Es la trampa
[[memoria/trampas/condicion-de-hecho-caduca-sin-aviso]] en su forma más pura.

De los nueve de hoy, **ocho están `passed` y uno `blocked`** — y ese uno es el que persigue
`runtime-budgets-al-ci`.

| | Qué implica |
|---|---|
| **(a) Reescribir la condición contra los nueve gates reales** | Queda a un solo gate de cerrar, el mismo que ya se está atacando |
| **(b) Reconstruir los quince** | Habría que averiguar cuáles eran y si siguen teniendo sentido. Nadie ha pedido seis gates más |
| **(c) Archivar el frente y quedarse con los gates** | Los gates siguen vivos y vigilando; lo que se pierde es el paraguas de gobernanza |

**Recomendación: (a).** Un frente cuya condición cuenta artefactos que ya no existen no mide nada; y
reescrita, este está más cerca de cerrar que ningún otro de los seis.

## D-9 · `reapertura-movil-y-tema-claro`: qué queda y hasta dónde llegar

**RESUELTA por Felipe el 2026-08-20: (a).** Solo F2b (los 13 módulos en móvil). El tema claro (F3) sale de este frente y espera a que alguien lo pida de verdad; eso además destraba D-7.

**Estado: RESUELTA (2026-08-20).** **Cuatro de siete fases cerradas** (MO-F1, F2a-1, F2a-2a, F2a-2b). Quedan:

- **F2b** — los 13 módulos restantes en móvil.
- **F3** — el tema claro, que **no es reactivar sino reconstruir**: `linen` se retiró el 2026-07-25 y
  no existe conmutador.
- F4 se absorbió en DS-F3.

Dato de hoy que toca esta decisión: al arreglar el CI se midió el shell a 390 px y **el móvil está
sano** — el carril flotante no tapa contenido, no hay desbordamiento horizontal y el menú abre con
diez destinos alcanzables. F2b parte de una base que funciona.

| | Qué implica |
|---|---|
| **(a) Solo F2b** | Trece módulos, incremental y medible módulo a módulo. El tema claro queda archivado |
| **(b) F2b y luego F3** | Completo, y muy caro: reconstruir un tema es rehacer la paleta entera y volver a validar cada superficie |
| **(c) Cerrar en 4 de 7** | Se declara terminado lo hecho y lo demás pasa a trabajo nuevo, si alguna vez se pide |

**Recomendación: (a), y F3 solo si alguien lo pide de verdad.** El tema claro no lo ha reclamado
ningún usuario en las notas del repo; lo que sí se usa a diario es el móvil.

**Qué NO haría: dejar F3 colgando dentro de este frente.** Es lo que mantiene abierto también a
`bi-control-tower-gemini` (D-7): un tema retirado bloqueando dos frentes a la vez.

---

## D-10 · El presupuesto de CSS está excedido: ¿se sube el techo o se recorta?

**DECIDIDA por Felipe el 2026-08-20: minificar el CSS servido.** Ejecutada en `35ef3059` y
`b289a822`. Resultado medido en CI: **200.488 → 126.885 B gzip**, un ahorro del 36,7 % que deja el
presupuesto con **69.848 B de margen**. El techo no se tocó.

> **Corrección del 2026-08-20, al reanalizar: la primera versión no llegaba a producción.**
> El espejo estaba en `.gitignore` como artefacto de build, y el código llega al servidor por
> `git pull`: lo ignorado no viaja. Comprobado contra el sitio real —`lastplanneraia.com/css/styles.css`
> devolvía 132.101 B con 179 comentarios—, **el gate medía 126.885 B mientras el usuario recibía unos
> 200.000**. Un guard midiendo algo distinto de la realidad, que es justo el defecto que esta jornada
> vino a corregir. Se arregló versionando el espejo (decisión de Felipe, misma fecha), y el CI pasó de
> generarlo a **verificarlo**: generar habría enmascarado un desfase en vez de bloquearlo.
>
> **Queda un pendiente que este arreglo destapa:** con el espejo activo el margen es de 69.848 B sobre
> un techo de 198.781 —un 35 %—, y un presupuesto con esa holgura no puede ponerse rojo por nada
> realista. **El techo de `cssGzipBytes` hay que recalibrarlo hacia abajo**, o deja de vigilar. Va
> junto con D-11, que pide lo mismo para la otra métrica.

Lo que sigue es el planteamiento tal como se elevó.

### Lo que pasó, en orden

El gate `runtime-budgets` **llevaba 40 corridas sin llegar a ejecutarse**: el job moría antes, en
`full-app-flow`. Al arreglar eso hoy, el gate corrió por primera vez y falló:

| Métrica | Baseline | Máximo | Real | Exceso |
|---|---:|---:|---:|---:|
| `cssGzipBytes` | 196.733 | 198.781 | **200.488** | **+1.707 B** |
| `initializationMs` | 191,4 | 301,9 | **593** | **+291 ms** |

**De quién es el exceso de CSS**, medido hoja por hoja:

| Origen | Aporte |
|---|---:|
| `semanal-fondo-por-matiz` | **+1.716 B** |
| La cola de estados de hoy (ya con los comentarios recortados) | +527 B |

**El frente de Semanal, solo, se comió el presupuesto entero.** No fue negligencia de nadie: el gate
que lo habría avisado no llegaba a correr. El CI roto ocultó que dos frentes consecutivos publicaron
por encima del techo.

Un dato que conviene saber antes de decidir: **el CSS se sirve sin minificar**, con sus 187
comentarios. La prosa explicativa que este repo cultiva a propósito **pesa en el presupuesto**.
Recortar la de hoy recuperó 799 B.

### Las opciones

| | Qué implica | Consecuencia |
|---|---|---|
| **(a) Subir el baseline a lo medido** | Se acepta que el CSS creció ~1,9 % con trabajo legítimo | Rápido y cierra dos frentes. El techo deja de ser un techo si se sube cada vez que estorba |
| **(b) Recortar CSS hasta volver bajo el techo** | Hay que quitar ~1.700 B gzip de hojas ya publicadas | Honra el contrato. Pero el trabajo a recortar es de otro frente, ya cerrado y aprobado |
| **(c) Minificar el CSS servido** | Los 187 comentarios dejan de viajar al navegador | **Devolvería mucho más de 1.700 B de una vez** y no obliga a tocar ni una decisión de diseño. Es cambio de fontanería, no de producto |

### Recomendación: **(c), y luego volver a medir**

Es la única que no obliga a elegir entre el techo y el trabajo. Los comentarios del CSS existen para
quien lee el repositorio, no para el navegador; hoy viajan a cada usuario en cada carga. Minificar
recupera de golpe mucho más que el exceso, y **deja el presupuesto midiendo lo que debería medir**:
el peso real del sistema de diseño, no el de su documentación.

Si tras minificar sigue por encima, entonces sí es (a) o (b) — pero con el dato limpio.

**Qué NO haría: subir el baseline ahora.** Es lo más rápido y lo que convierte el presupuesto en un
adorno: la primera vez que un techo se sube por incomodidad, deja de ser un techo. Y menos aún
haciéndolo en la misma jornada en que se descubrió que llevaba 40 corridas sin vigilar nada.

**`initializationMs` queda aparte y sin recomendación:** 593 ms contra 301,9 no lo causa el CSS, y
con una sola corrida no se puede distinguir deriva real de ruido del runner. Hasta hoy no había
ninguna con la que comparar. Pide una segunda medición antes de decidir nada.


---

## D-11 · `initializationMs` triplica su baseline, y no es ruido

**RESUELTA por Felipe el 2026-08-20: (a), midiendo antes de fijar nada.** Se recalibra el techo tras 3–4 corridas de CI más, que salen gratis. **No** se sube el techo hoy con las seis muestras que hay: sería bendecir una posible regresión el mismo día que recuperamos la capacidad de verla.

**Estado: RESUELTA (2026-08-20).** Es lo único que queda para que el gate `runtime-budgets` pase, y con él cierren
`runtime-budgets-al-ci` y `gates-al-ci`.

Con el CSS ya resuelto (D-10), el gate falla por una sola métrica: **639,4 ms contra un máximo de
301,9 y un baseline de 191,4**.

### Por qué no se puede despachar como ruido del runner

Seis muestras, dos corridas independientes:

| | muestras | media |
|---|---|---:|
| Sin minificar | 589,4 · 657,2 · 593,0 | ~613 |
| Con CSS minificado | 639,4 · 627,2 · 666,1 | ~644 |

**La dispersión interna es de ±5 %.** Una métrica que varía tan poco entre muestras no está midiendo
azar. Y **minificar el CSS no la mejoró** —subió—, así que el cuello no es el peso de las hojas.

Qué mide: `performance.now()` en el instante en que Handsontable queda montado en `/programa-general`
— tiempo hasta rejilla lista, sensible a la máquina que lo corre.

### El problema de fondo: no hay con qué comparar

**Hasta hoy ninguna corrida de CI llegaba a medir esto** — el job moría antes, en `full-app-flow`,
durante al menos 40 corridas. Así que no existe una serie histórica que diga cuándo empezó. Las dos
explicaciones posibles no se pueden distinguir con los datos que hay:

1. **El producto se volvió ~3× más lento** en algún momento sin que nadie lo midiera.
2. **El baseline de 191,4 ms se tomó en otro entorno** —otra máquina, otro fixture— y nunca describió
   a este.

### Las opciones

| | Qué implica |
|---|---|
| **(a) Recalibrar el baseline en el entorno donde de verdad se mide** | Se toman N corridas de CI y se fija el techo con su dispersión real. Honesto si la causa es la 2 |
| **(b) Investigar la lentitud como bug de rendimiento** | Perfilar el arranque de Programa General. Caro, y podría acabar en «siempre fue así» |
| **(c) Dejar el gate rojo** | Los dos frentes siguen bloqueados |

### Recomendación: **(a), pero midiendo antes de fijar nada**

Ahora que el CI por fin llega a este paso, cada publicación deja una medición. **Con tres o cuatro
corridas más hay serie suficiente** para fijar un techo que describa la realidad en vez de a una
máquina de julio — y eso sale gratis, porque las corridas ocurren igual.

**Qué NO haría: subir el techo hoy con las seis muestras que hay.** Es la tentación evidente —el CSS
ya se arregló, solo queda «este número»— y sería fijar un baseline con dos corridas del mismo día. Si
la causa resulta ser la 1, habríamos bendecido una regresión de rendimiento el mismo día que
recuperamos la capacidad de verla.

---

# Ronda de decisiones del 2026-08-20 — las once, resueltas

Felipe las revisó una por una en sesión dedicada. **Ninguna quedó abierta.** Resumen de lo decidido,
con el detalle y la medición en cada sección de arriba:

| | Decisión | Resultado |
|---|---|---|
| D-1 | Realces por condición del dato | Semanal entra al contrato; `r0` va por filtro, no por color |
| D-2 | Excepción crítica del chip | Se retira la regla muerta |
| D-3 | Estados declarados sin pintar | Aspiracionales aparte; PDC se implementa |
| D-4 | Qué significa «cubierto» | Un escenario para el armazón; 3 pantallas se declaran; 5 rutas muertas se retiran |
| D-5 | Segundo sistema de estilos de BI | Variante `tabs` al catálogo; utilidades, pregunta aparte |
| D-6 | Vocabulario de la cascada | El frente cambia su objetivo a consistencia |
| D-7 | `bi-control-tower-gemini` | Condición recortada a los tres modos dark |
| D-8 | `design-system-nucleo-gobernanza` | Condición reescrita contra los nueve gates reales |
| D-9 | Móvil y tema claro | Solo F2b; el tema claro sale del frente |
| D-10 | Presupuesto de CSS | *(ya estaba decidida: minificar)* |
| D-11 | `initializationMs` | Recalibrar tras 3–4 corridas; no subir el techo hoy |
| — | Plugins de Obsidian | Instalados y verificados en pantalla; **Iconize excluido** por estar declarado como proyecto descontinuado por su autor |

**Lo que esta ronda destraba:** D-11 es el único paso rojo del CI — con él recalibrado,
`runtime-budgets-al-ci` y `gates-al-ci` cierran solos. D-7, D-8 y D-9 sacan de la parálisis a tres
frentes cuyas condiciones contaban artefactos que ya no existen.

**Nada de esto se ejecuta hoy:** son decisiones, y su trabajo entra por los frentes que ya tienen
dueño (DS-F1, DS-F2, DS-F3 y MO-F2b). El deploy a producción sigue exigiendo autorización propia.

