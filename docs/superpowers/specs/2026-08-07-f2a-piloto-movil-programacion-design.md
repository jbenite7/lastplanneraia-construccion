---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-07
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-07-f2a-piloto-movil-programacion-design.md
resumen: F2a — Precondiciones y piloto móvil (Programación Intermedia y Semanal)
---

# F2a — Precondiciones y piloto móvil (Programación Intermedia y Semanal)

- Fecha: 2026-08-07
- Estado: aprobado en brainstorming, pendiente de plan
- Programa del que cuelga: [`2026-08-07-reapertura-movil-y-tema-claro-design.md`](2026-08-07-reapertura-movil-y-tema-claro-design.md) (decisiones D1–D8)
- Fase previa: F1, cerrada el 2026-08-07 (DS-032). El viewport `390x844` ya es **soportado y no requerido**.

## Por qué esta fase existe y por qué es solo un piloto

F2 completa —las 15 áreas en móvil— no cabe en una spec. Medido en el repo el 2026-08-07:
15 manifiestos de módulo, 12 vistas que montan una grilla o tabla de vendor, y **tres**
motores distintos (DataTables, Handsontable, AG Grid dentro de la SPA React del Plan de
Compras). «Tabla→card» son tres implementaciones, no una.

F2a hace **dos módulos de punta a punta** para que los 13 restantes se planifiquen con un
coste medido en vez de estimado. Los dos elegidos son las dos mitades del flujo Last
Planner, comparten Handsontable, y Semanal arrastra además tres subvistas de tabla HTML
plana: el piloto ejercita los dos patrones de conversión de una vez.

## El hallazgo que reescribió el diseño

Buscando dónde construir la card apareció `public/js/modules/programacion_semanal/legacyCards.js`
—435 líneas— que **ya convierte CNP, CNC y CIC en cards** por debajo de 767px. Y no es una
isla:

- consume tokens `--ds-*` (`--ds-active-surface`, `--ds-radius-card`, `--ds-target-min`, la
  escala de estado), no hex sueltos;
- está declarada en `docs/design-system/manifests/programacion-semanal.json:21`;
- la ejercitan cinco specs de Playwright, todas bajo `tests/browser/`:
  `programacion-semanal-sprint.mjs`, `programacion-semanal-subviews.mjs`,
  `programacion-semanal-dark-density.mjs`, `programacion-semanal-cnp-lifecycle.mjs` y
  `cic-role-disciplines.mjs`;
- expone `window.PSLegacyCards = { attach, plainText }` y reacciona en vivo a
  `matchMedia` (línea 357).

Lo que le falta no es existir: es el umbral (767 en vez de 1180), la evidencia móvil en los
gates, y no saber alimentarse desde una grilla Handsontable. **Construir una card nueva
desde cero habría tirado 435 líneas probadas y cinco specs vivas.**

## Corrección de la premisa (2026-08-08)

**Esta spec se escribió sobre un supuesto falso y se corrige aquí en vez de reescribirla.** El
censo previo al plan —hecho por la lección de las fases anteriores: medir antes de afirmar—
encontró que **las cards móviles ya existen en los dos módulos del piloto**.

| | Cards | ¿Editan? | Umbral | ¿Monta Handsontable en móvil? |
|---|---|---|---|---|
| Semanal | `ps-mobile-card`, completas | **Sí**: Compromiso y Ejecutado Real, con guardado y estado de guardado | CSS a 768 | **Sí** |
| Intermedia | `pi-mobile-card` | No, solo lectura | JS a 768 (`hot.js:4331`) | **Sí** |
| CNP/CNC/CIC | `legacyCards.js` | Sí | JS a 767 | n/a (DataTables) |

Consecuencias, cada una verificada:

- **E6 queda sin objeto tal como estaba escrita.** «Promover `legacyCards` a primitiva
  compartida» partía de que era la única implementación de cards. No lo es, y además está
  acoplada a **DataTables** (`table.rows({search:'applied'}).data().toArray()`), no a un array:
  no puede alimentarse de Handsontable sin un adaptador. Las cards de los módulos del piloto
  ya se alimentan de `masterData`.
- **E4 no está implementada.** Hoy las cards se pintan siempre en el DOM y el CSS esconde la
  grilla, así que Handsontable **se monta igual en el celular**. El ahorro que justificaba la
  decisión sigue sin existir.
- **Hay tres umbrales distintos** (767, 768 en CSS, 768 en JS) donde la spec asumía uno.
- Los dos manifiestos declaran `layouts: ["desktop"]`: móvil no está declarado como layout
  soportado.

Lo que de verdad falta, entonces, no es construir cards: es unificar el umbral en 1180, dejar
de montar la grilla por debajo, hacer que las cards de Intermedia editen, y producir la
evidencia móvil que F1 y F2a-1 dejaron posible.

## Decisiones añadidas el 2026-08-08

| # | Decisión | Alternativas descartadas |
|---|---|---|
| E7 | **Las reglas de habilitación se extraen antes de que las cards de Intermedia editen.** Hoy viven dentro de la configuración de Handsontable: 13 reglas en Semanal, 9 en Intermedia. Una card que edite tendría que replicarlas. | Piloto de solo lectura y editar después; editar solo los campos sin reglas. |
| E8 | **Primero la red de pruebas, después la extracción, después el resto.** Las 22 reglas no tienen hoy cobertura: si la extracción rompiera S3, S4, S11, I3, I5 o I7, nadie se enteraría. Solo I4 tiene red, y es un snapshot visual. | Empezar por las ganancias baratas (umbral, no montar, evidencia); construir solo la red y revaluar. |

### Riesgos medidos que condicionan la extracción

- **La presentación consume la decisión por el DOM**: `piRestrictionRenderer` lee la clase
  `pi-cell-locked-resp` que puso `cells()` para saber qué pintar. Cambiar el nombre o el
  momento de esa clase apaga el candado sin romper ninguna prueba salvo un golden.
- **Handsontable cachea `cellMeta`**: `cells()` no se re-evalúa, y Intermedia lo sortea
  escribiendo meta a mano (`syncRestrictionLockForVisualRow`). Una función extraída que
  devuelva un booleano no elimina ese bypass.
- **El estado se lee del DOM en cada llamada** (`#permiso_canonico`, `#semana`,
  `#Max_Semana`, `#Semanal_Confirmada`), y `cells()` corre por celda visible.
- **Una regla de habilitación desemboca en un borrado**: en Semanal, `beforeChange` rechaza
  un Compromiso entre 0 y 0.001 y dispara `deleteActivity`. Separar decisión de efecto es
  parte del trabajo, no un extra.

## Decisiones

| # | Decisión | Alternativas descartadas |
|---|---|---|
| E1 | Piloto doble: Programación Intermedia y Programación Semanal (10 rutas, 4 vistas). | Programa General; Indicadores; solo Semanal. |
| E2 | Modelo de card **A · Resumen con detalle desplegable**: código, actividad, chip de estado y barra de avance visibles; los ocho campos restantes y la edición, dentro del desplegable. | Ficha completa siempre visible; acción primero; A+C combinados. |
| E3 | Umbral **1180px**: por debajo, cards; por encima, grilla. **La tablet recibe cards.** | 767px (el actual de `legacyCards`); un umbral propio por módulo. |
| E4 | **JS decide y monta solo una vista.** Bajo el umbral, Handsontable no se instancia. | CSS con ambas en el DOM; detección por user-agent en PHP. |
| E5 | Al cruzar el umbral en caliente **con Handsontable de por medio**: no se reorganiza nada, se muestra un aviso con botón de recargar. | Cambiar de vista sola; ignorar el cambio sin avisar. |
| E6 | `legacyCards.js` se **promueve a primitiva compartida**, no se reemplaza. | Card nueva y migrar después; dejarla quieta y construir otra en paralelo. |

### Matiz de E5 que no contradice a E5

`legacyCards` ya conmuta en vivo por `matchMedia`, y ahí **funciona bien**: CNP, CNC y CIC
son tablas HTML planas, así que no hay grilla virtualizada que desmontar en caliente ni
estado que se pierda. La conmutación en vivo **se conserva** en las vistas sin Handsontable.
El aviso-y-recarga de E5 aplica solo donde hay grilla que montar, que es donde el
desmontaje en caliente arrastra filtros, edición a medias y scroll. La razón de E5 es
Handsontable, no el redimensionado en sí, así que la regla sigue a su causa.

## Las tres precondiciones

Van **antes** de declarar el primer escenario móvil. Si se declara antes, la evidencia sale
falsa y los gates dan verde igual — así lo midió la revisión final de F1.

| # | Qué | Dónde |
|---|---|---|
| P1 | Los carriles visual y de accesibilidad descartan en silencio todo escenario móvil. | `tests/browser/design-system-lab.visual.mjs:22`, `tests/browser/programa-general.visual.mjs:12`, `tests/browser/design-system-lab.a11y.mjs:19` |
| P2 | Un golden de escritorio vale hoy como evidencia de un escenario móvil: el gate verifica existencia y hash, no correspondencia con el viewport. | `scripts/design-system-contracts.mjs:344-351` |
| P3 | El harness `runFixture` corre sin `.git` y con una lista de tests desactualizada, así que no admite pruebas de caso positivo. Dos causas, no una: con `.git` enlazado siguen 22 fallos porque copia 9 archivos y los contratos referencian una veintena. | `tests/design-system/contracts.test.mjs:16-53` |

## Arquitectura

### Unidades

| Unidad | Responsabilidad | Depende de |
|---|---|---|
| `public/js/modules/aia_ui/card-list.js` | La primitiva. Recibe un array de registros y una descripción de campos, y renderiza la lista de cards A. No sabe de Handsontable, de jQuery ni de rutas. | tokens `--ds-*` |
| `public/js/modules/aia_ui/view-switch.js` | Mide el ancho contra el umbral y responde a una sola pregunta: `shouldRenderCards()`. Emite el aviso de recarga cuando el umbral se cruza en caliente y hay grilla montada. | nada |
| Adaptador de tabla plana | Lo que hoy es `legacyCards.js`, reducido: extrae registros del `<table>` y delega en `card-list`. Conserva su API `window.PSLegacyCards` mientras las cinco specs existentes la usen. | `card-list` |
| Adaptador de Handsontable | Nuevo. Extrae los registros del mismo array que alimenta la grilla y delega en `card-list`. **No** instancia Handsontable cuando `shouldRenderCards()` es cierto. | `card-list`, `view-switch` |

La frontera que importa: `card-list` no conoce el origen de los datos y los adaptadores no
conocen el HTML de la card. Un módulo nuevo se suma escribiendo un adaptador, no tocando la
primitiva.

### Flujo de datos

Los datos ya llegan por AJAX para alimentar la grilla. El adaptador consume **ese mismo
array**: no hay segunda petición ni segunda fuente de verdad. Si la petición falla, falla
una vez y el estado de error es el que ya existe.

### Los doce campos

De la grilla real (`public/js/modules/programacion_semanal/hot.js`): `Código Actividad`,
`Actividad`, `Sem. Inicio`, `Fecha Inicio`, `Fecha Fin`, `Crítica`, `Unidad`, `Cant. PPTO`,
`Ej. Teórico`, `Ej. Real`, `Estado`, `Lib. Restr.`

En la cara visible de la card: código, actividad, chip de `Estado` y barra de avance
(`Ej. Real` sobre `Cant. PPTO`). Los otros ocho, en el desplegable.

## Adenda del 2026-08-14 — E2 revisada tras medir la tarjeta implementada

**E2 nunca se implementó.** Decidía el modelo **A · Resumen con detalle desplegable**, y lo que se
construyó fue la ficha completa siempre abierta — justo la alternativa que E2 descartó por escrito.
Medido el 2026-08-14 a 390 px: **354×562 px por tarjeta en Semanal** (1,5 por pantalla, 31
tarjetas, ~17.000 px de scroll para recorrerlas) y 342×360-403 px en Intermedia **con 78**. Ninguna
de las dos pliega nada: en Intermedia, `details` (`hot.js:4359`) es el nombre de una variable para
un `<div>` de campos siempre visibles, no un elemento plegable.

**E2-bis (2026-08-14, decisión del usuario).** El modelo A se confirma —resumen con detalle
desplegable— con **dos cambios sobre la cara visible**, ambos ampliándola:

| # | Decisión | Alcance | Efecto sobre E2 |
|---|---|---|---|
| E2-bis-a | La cara visible lleva **cinco** elementos: código, actividad, chip de estado, barra de avance y **Responsable AIA**. | Semanal e Intermedia | **Amplía** E2, que dejaba el responsable entre los ocho plegados. Razón: en obra se busca por persona antes que por actividad, y desplegar solo para saber a quién reclamar es el gesto más repetido. |
| E2-bis-b | En Semanal, **la edición del compromiso vive en la cara visible**, no dentro del desplegable. | Solo Semanal | **Revoca** la parte de E2 que mandaba «y la edición, dentro del desplegable». Razón: capturar el compromiso en obra debe ser de un toque. Intermedia no se ve afectada: sus tarjetas son de solo lectura (`createMobileCard`, `hot.js:4328`, no monta ningún control de escritura). |

**E2-bis-c (2026-08-14, decisión del usuario) — las restricciones de Intermedia.** Destapado al
maquetar: **la tarjeta móvil de Intermedia no muestra ninguna de las siete restricciones**
(`createMobileCard`, `hot.js:4328-4364`, solo pinta Subcontratista, Responsable AIA, Inicio y
Ejecutado), que son editables en escritorio y son *para lo que existe el módulo*. El análisis previo
de esta adenda contó los siete campos presentes y concluyó que «caben todos» sin comprobar si
faltaba algo: lo que faltaba era el núcleo.

Las siete, desde `/api/general/restriction-config`
(`GeneralApiController.php:1563+`): Diseños y Especificaciones, Materiales, Mano de Obra, Equipos y
Herramienta, Actividad Predecesora (duras); Procedimiento Constructivo y Modelación BIM (blandas).
En Pre-Construcción son hasta cuatro, con nombres configurables por proyecto.

**Decisión: resumen visible y edición en el desplegable.** La cara visible lleva un contador de
liberación («3 de 7») y cuáles faltan; al desplegar aparecen las siete con su control. Descartadas:
solo-lectura con edición en escritorio (cierra el caso de liberar una restricción parado frente al
frente de trabajo, que es lo que haría útil el móvil) y mostrar solo las bloqueantes (esconde el
resto y no resuelve qué pasa cuando están todas liberadas).

**Lo que E2-bis-c arrastra, dicho por delante:**

- **La tarjeta desplegada de Intermedia será la más alta de las dos**, del orden de 550-600 px
  estimados —siete controles con etiqueta— frente a los ~440 de Semanal. Sigue siendo ganancia:
  hoy son 78 tarjetas de 360-403 px **sin poder ver ni editar las restricciones**, y solo una
  estaría abierta a la vez.
- **La tarjeta de Intermedia pasa de solo lectura a editable**, así que deja de bastar con
  `createMobileCard`: hay que atarla a las reglas I1–I7, que es exactamente lo que la extracción
  del 2026-08-14 dejó disponible en `enablement-rules.js`
  (`crearReglasIntermedia().puedeEditarCelda()`). Sin esa extracción, esta decisión habría exigido
  replicar las reglas en la card — el desincronizado contra el que la red monta guardia.
- **I4 (candado por Responsable AIA) se ve en móvil por primera vez**: una fila sin responsable
  tiene sus restricciones bloqueadas, y la card debe mostrarlo, no solo impedirlo.
- **La red necesita el equivalente de S13 para Intermedia**: hoy S13 ata la card de Semanal a la
  misma regla que la grilla; no existe su gemela para Intermedia porque hasta ahora no editaba.

**E2-bis-d (2026-08-14) — la forma común, y los tres puntos resueltos contra el código.** Al comparar
las dos tarjetas maquetadas, la de Intermedia resultó mejor por una razón que no es de maquetación:
**responde «qué hago con esto» en vez de «qué es esto»**. Semanal describía una fase que comparten
las 31 tarjetas; Intermedia decía qué falta en *esa*. Las dos adoptan la misma forma:

| Elemento | Semanal | Intermedia |
|---|---|---|
| Chip | `N pend.` — cifra accionable; el **color** conserva la máquina de estados (cumplida, incumplida, TNP, sin calificar) | `N de 7` — restricciones liberadas |
| Línea de foco | El texto real de `getOperationalStateSummary().focus` | Las restricciones sin liberar |
| Desplegable | «Ver fechas y presupuesto» | «Liberar restricciones» |

**No se traslada** la barra segmentada de siete: en Intermedia cada segmento es una restricción y
contarlas significa algo; en Semanal el avance es continuo y segmentarlo inventaría una precisión
que el dato no tiene.

**Hallazgo que hizo esto casi gratis:** `focus` —cuál es el asunto pendiente más urgente— **ya se
calcula** (`hot.js:976-996`) y hoy **solo se entrega al lector de pantalla** (`:1020`); la tarjeta
visible pinta únicamente el número (`:3438`). Quien ve la pantalla obtiene menos que quien la
escucha. Publicarlo es exponer dato existente, no producirlo.

**Los tres puntos que quedaban abiertos, resueltos contra el código antes de escribir esto:**

1. **El capítulo se separa, no se trunca.** El dato ya trae `[Capítulo: …]` envuelto en `<small>` y
   existe `ActivityMatcherService::extractChapter()` (`:104`) que lo aísla. El título queda limpio y
   el capítulo baja a una línea secundaria atenuada. **Cuesta ~25 px**: Semanal pasa a ≈325 y
   Intermedia a ≈275.
2. **Las frases de «qué falta» son reales y cambian con la fase.** No son etiquetas sueltas sino
   frases completas (`makeActionItem`, `:681`): en **programación**, restricciones sin liberar más
   «Definir compromiso mayor a cero» / «Asignar responsable»; en **calificación**, avance real y
   causa de no cumplimiento (`getOperationalActionItems`, `:902-924`). La maqueta previa decía
   «Falta liberar materiales · sin sub-contratista»: la forma era correcta, **el texto estaba
   inventado**. Se usa el que da `focus`.
   **Consecuencia:** en Semanal el campo editable de la cara visible también cambia — `Compromiso`
   en programación, `Ejecutado_Real` en calificación—, que es lo que ya decide `isPropReadOnly`
   (regla S3).
3. **En Intermedia conviven contador y estado.** «Listo para comprometer» no se pierde: es lo que
   dice la barra segmentada entera en verde, anticipado por el color del chip. Distinto de Semanal,
   cuya máquina de estados tiene nombre propio y por eso el chip conserva su color como portador del
   estado.

**E2-bis-e (2026-08-14) — el contador cuenta las cinco duras, en los dos módulos.** Al poner las dos
tarjetas juntas apareció que `N pend.` (Semanal) y `N de 7` (Intermedia) no medían lo mismo. La
investigación mostró que **la asimetría no era entre módulos sino dentro de Intermedia**:
`stateMachine.js:150-159` — `isReadyToCommit()` recorre **solo** `getHardRestrictions()`, así que el
estado «listo para comprometer» lo deciden las duras, igual que en Semanal
(`getConfigRestrictions()`, `hot.js:633-650`, lee `hardRestrictions`). El contador «3 de 7» que
proponía la maqueta habría mostrado **un número que no es el que determina el estado de su propia
tarjeta**: podría leerse «faltan 2» en una actividad que sí se puede comprometer.

**Decisión: el chip cuenta las cinco duras** (Diseños y Especificaciones, Materiales, Mano de Obra,
Equipos y Herramienta, Actividad Predecesora). Las dos blandas —Procedimiento Constructivo y
Modelación BIM— **siguen visibles y editables en el desplegable**, con su valor; dejan de contar
como bloqueo, que es lo que el código ya hace. Razón: el chip responde «¿puedo comprometer esto?»,
y eso lo deciden las duras.

**Tensión que esto deja escrita, no resuelta:** `GLOSARIO.md:47` define la liberación de
restricciones como «asegurar la disponibilidad de los **7** recursos previos» — el vocabulario de
obra cuenta siete y el código bloquea con cinco. La tarjeta sigue al código porque es lo que
determina si se puede comprometer, pero **la discrepancia entre el glosario y el comportamiento es
real y precede a este trabajo**. Si el criterio de obra es que las siete bloquean, lo que hay que
cambiar es `isReadyToCommit()`, no el contador de la tarjeta.

**Consecuencia declarada, no disimulada:** ampliar la cara visible reduce el ahorro. La estimación
por composición es **280-320 px** por tarjeta frente a los 562 actuales —de 1,5 a unas 2,8 tarjetas
por pantalla—, no los 120-150 px que daría el modelo A estricto. Es estimación, no medición: se
verifica al implementar y si se desvía, se reporta.

**Lo que sigue plegado en Semanal:** los siete campos de lectura restantes (`Sem. Inicio`,
`Fecha Inicio`, `Fecha Fin`, `Crítica`, `Unidad`, `Cant. PPTO`, `Ej. Teórico`). Siete campos
justifican el desplegable de sobra, aunque la edición haya salido de él.

**Atado a esta decisión, del audit del 2026-08-14:** `DET-2` (la jerarquía tipográfica se aplana a
1.5:1 en móvil) no se ataca por separado. Elegir qué es lo esencial es exactamente lo que dice a
qué darle el tamaño mayor, así que la escala se ajusta al implementar esta tarjeta, no antes.

### Medición final contra la estimación

Medido tras la Task 7 (2026-08-14), en `390x844`, tarjeta cerrada:

| Tarjeta | Estimación | Real | Desviación |
|---|---|---|---|
| Semanal | ≈325 px | **360 px** | +10,8% |
| Intermedia | ≈275 px | **269 px** | -2,2% |

Semanal se desvía por encima del 10% de la estimación (aunque queda cómodamente bajo el
umbral de 380 px fijado en el plan); Intermedia queda dentro del margen. La estimación de
Semanal falló porque **se calculó sumando los bloques de contenido (header, barra, foco,
responsable) sin contar el espaciado entre ellos** — márgenes, paddings y gaps entre bloques,
que en la primera implementación (antes de apretar el espaciado) resultaron ser el mayor
consumidor de altura: 138 px de los 521 px iniciales, más que cualquier bloque de contenido
individual. Apretar ese espaciado —no recortar contenido— fue lo que bajó la tarjeta de 521 a
360 px. La lección para la próxima estimación por composición: el espaciado entre bloques no es
un residuo del cálculo, hay que presupuestarlo explícitamente, no descontarlo del total.

## Pruebas

**Sin navegador (TDD, `node --test`):**
- `shouldRenderCards()` en los bordes del umbral: 1179 da cards, 1180 da grilla.
- El mapeo registro→card: los doce campos, incluidos los casos feos que `legacyCards` ya
  trata (valor nulo, porcentaje mayor que 1, HTML incrustado en un campo de texto).

**Con navegador (Playwright, `390x844`):**
- **La prueba que sostiene el ahorro:** en el DOM móvil no existe ni un nodo de
  Handsontable. Sin ella, «no se instancia» es una afirmación sin respaldo.
- Las cinco specs existentes de CNP/CNC/CIC siguen verdes tras la promoción.
- Goldens `390x844` de los dos módulos y axe en móvil, una vez cerradas P1 y P2.

**Regresión de escritorio:** los escenarios `1180x820` y `1440x900` de ambos manifiestos no
cambian. Si un golden de escritorio se mueve, es una regresión, no un efecto esperado.

## Condición de hecho

1. `npm run test:design-system:static` en sus ocho puertas, y la suite de navegador de los
   dos módulos, en verde.
2. Los dos manifiestos declaran sus escenarios `390x844` **con goldens propios**, y el gate
   rechaza un golden de escritorio reutilizado (P2 cerrada).
3. En `390x844`, ninguna de las cuatro vistas instancia Handsontable, comprobado en el DOM.
4. Ninguna de las cinco specs existentes de CNP/CNC/CIC se debilitó para pasar.
5. Los goldens de escritorio de ambos módulos, sin cambios.

## Fuera de alcance

Los otros 13 módulos. El tema claro (F3). La matriz diagonal de gates (F4). La SPA del Plan
de Compras, que tendrá su propia spec dentro de F2 por no compartir código con el resto.
DataTables: ningún módulo del piloto lo usa.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| Promover `legacyCards` arrastra jQuery y HTML por concatenación de strings a la primitiva compartida. | La promoción conserva el comportamiento y las specs; la limpieza de estilo va después, con la red ya puesta. No se hacen las dos cosas a la vez. |
| Cambiar el umbral de 767 a 1180 altera lo que ven hoy los usuarios de tablet: pasan de grilla a cards. | Es el efecto buscado (E3) y la razón es que la grilla no es usable bajo 1180. Se anota como cambio visible en el cierre, no como efecto colateral. |
| Las cinco specs existentes asumen 767px. | Se revisan una a una al cambiar el umbral. Si alguna fija el ancho, se actualiza su viewport, nunca su aserción. |
