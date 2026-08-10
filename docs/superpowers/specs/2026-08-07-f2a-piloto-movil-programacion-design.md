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
