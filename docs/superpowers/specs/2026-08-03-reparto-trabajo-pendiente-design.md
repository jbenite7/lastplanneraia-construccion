---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-03
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-03-reparto-trabajo-pendiente-design.md
resumen: Reparto del trabajo pendiente tras el saneamiento del goal de tablas
---

# Reparto del trabajo pendiente tras el saneamiento del goal de tablas

**Fecha:** 2026-08-03 · **Última revisión:** 2026-08-03, al cerrar la jornada
**Estado:** A cerrada · B1 a medias · el resto sin empezar
**Origen:** grilleo de doce decisiones acumuladas el 2026-08-03, tras el saneamiento de
`goals/cierre-dark-mode-y-tablas/` y los informes de tres sesiones paralelas.

## Por qué existe este documento

El 3 de agosto se acumularon diez decisiones del usuario procedentes de cuatro frentes: el
saneamiento del goal de tablas, la sesión de consolidación de tintes, la de puesta al día de
`DESIGN.md` y el repaso de usabilidad H-08. Se resolvieron en un solo grilleo, y ejecutar la
primera línea destapó dos decisiones más.

El resultado **no cabe en un spec**: son **ocho líneas** de trabajo, y una de ellas es
funcionalidad nueva. Este documento reparte, ordena y explica las dependencias. Cada línea recibe
después su propio ciclo de spec, plan y ejecución.

Seis vienen del grilleo. Las otras dos aparecieron después y se señalan como tales: **F-bis**, el
autoguardado al entrar, que este documento desgaja por su cuenta; y **G**, el chip de estado que le
falta a Programa General, que no existía al grillear porque el defecto se descubrió al ejecutar B1
— su alcance sí lo decidió el usuario, y se corrigió a la baja al medirlo.

## Las doce decisiones, tal como se resolvieron

| # | Decisión | Resolución |
|---|---|---|
| 1 | Cuarto alias `teal` ↔ `info` | **Sí entra.** Mismo caso que los otros tres: valores idénticos, no reasigna estados |
| 2 | Alcance del guard de emparejamiento | **Acepta el selector hermano inmediato.** Exigir la misma regla mandaría a excepciones un caso hoy correcto |
| 3 | Cambio visual de las reglas huérfanas | **Aprobado**, con la condición de medir en navegador antes y después |
| 4 | Los goldens retratan una grilla vacía | **Que dejen de dar cobertura falsa.** Se aprobó como «sembrar la base»; medido después, el trabajo real es darle filas al mock — ver B1 |
| 5 | Drift del ancho de sidebar | **Investigar cuál es el correcto antes de tocar nada** |
| 6 | `--ds-cell-state-bloqueado-*` fuera de los dos canales | **Pasa al matiz azul existente**, enseñando antes el cambio |
| 7 | Embebido de Power BI en `/indicadores` | **Se conserva**, con estado de carga y de error, enmarcado como contenido externo |
| 8 | `/dashboard` como redirect | **Sí debe existir un panel de inicio**, que además resuelve el efecto colateral |
| 9 | 14 pantallas de `admin/` sin cubrir | **Extender la puerta de servicio a `admin/`** |
| 10 | 39 hallazgos de usabilidad | **Altas y medias**: 30 aprobadas, 26 ejecutables — cuatro ya tienen dueño en otra línea |
| 11 | Alcance del arreglo del canal de matiz | Se aprobó como «los tres módulos». **Medido después: solo PG está roto** — PI y PS ya llevan el chip. Ver G |
| 11-bis | Cómo recupera PG su matiz | **Con chip en la celda, como PI y PS.** Deja las tres pantallas consistentes en vez de inventar un tercer patrón |
| 12 | Cuándo nace el guard que cruza contrato y CSS | **Junto al sembrado de B1.** Antes nacería verde sobre una grilla vacía |

Las decisiones 1, 2 y 3 están **ejecutadas y publicadas**; la sesión que las llevaba cerró. La 4
se reformuló al medirla y la 6 puede quedar absorbida por la 11 — ambas cosas están explicadas en
su línea.

## El reparto

### A · Tintes — CERRADA

Decisiones 1, 2 y 3. Consolidar `--ds-state-tint-*` sin colapsar el contrato de dos canales.

**Qué quedó hecho**, publicado en `95a1827` y `08fe26c`:

- Los **cuatro** alias aplicados y medidos en navegador: los cuatro `--ds-color-state-*-bg`
  resuelven idénticos a su ancla. Cero píxeles movidos.
- Guard `state-tint-pairing.test.mjs` con `docs/design-system/state-tint-exceptions.json`.
- Las seis reglas huérfanas **inventariadas como `by-design`, no arregladas** — que es el
  desenlace correcto, porque al medirlas ninguna tenía defecto visible: las tres celdas de grilla
  heredan el texto primario del tema (11,54 · 11,15 · 12,92:1), las dos de fila no pintan texto, y
  la píldora hereda su par del contenedor. Declararles color habría sido una línea muerta.

**Dos cosas que este tramo enseñó y que valen para las demás líneas:**

1. **Escribir el guard antes que el arreglo paga.** Su rojo levantó el censo por su cuenta y
   corrigió el recuento a la baja, de ocho a seis: dos reglas de `admin/` sí reciben tinta en una
   regla agrupada que el escaneo manual había pasado por alto.
2. **Reserva medida sobre la sonda compartida:** `measure()` devolvió 1:1 con primer plano igual al
   fondo en dos especímenes **en línea**, mientras `getComputedStyle` reportaba colores distintos.
   En elementos de bloque se portó bien. Sin confirmar a fondo, pero si mides un `span` en línea y
   sale 1:1, sospecha de la sonda antes que del CSS.

### B · Que las pruebas digan la verdad — primero

Decisiones 4 y 5. Dos problemas distintos con la misma consecuencia: hoy ninguna recaptura de
goldens es fiable.

**B1 — Que los goldens retraten estados. NO es sembrar la base de datos.**

> **Corrección de premisa — 2026-08-03, y es la segunda de este documento.** B1 se escribió como
> «sembrar actividades con estado en el proyecto de pruebas», partiendo de que la grilla sale
> vacía por falta de datos. **Falso, y comprobado en tres pasos.**
>
> 1. `Da Porto` tiene **273 filas** en `programa`, consultado contra la base local.
> 2. Navegando a `/programa-general` a 1180×820 la grilla pinta **312 celdas en 26 filas**, con
>    tres clases de estado vivas: `pg-state-actividad-futura` (216), `pg-state-terminada` (36) y
>    `pg-state-en-curso` (12).
> 3. La causa real está en `tests/browser/programa-general.visual.mjs:24`: la función
>    `mockDeterministicData()` intercepta `**/api/general/list**` y devuelve `data: []`. **El test
>    borra los datos a propósito** para que la captura sea determinista, y nunca consulta la base.
>
> Sembrar la base no habría cambiado un solo píxel de esos goldens. La decisión que el usuario
> aprobó sigue siendo válida en su intención —que los goldens dejen de dar cobertura falsa— pero
> el trabajo es otro y mucho más barato.

El trabajo real es **sustituir el `data: []` del mock por un conjunto fijo de filas que cubra los
peldaños**. Sigue siendo determinista —que es lo que la función busca y hay que preservar— pero
deja de retratar un tablero en blanco. Mismo tratamiento en
`programacion-intermedia.visual.mjs` si usa el mismo patrón.

**Ojo con la tentación de quitar el mock.** Está ahí por una razón: sin él, la captura depende del
estado de la base y del momento, y el gate se vuelve inestable. La corrección es darle contenido al
mock, no eliminarlo.

Para la línea A, que necesita medir `pg-match-auto`, `pg-match-review` y `pg-match-new` en
`/programa-general-actualizar`, aplica lo mismo: comprobar antes si esa superficie también mockea
su endpoint, en vez de dar por hecho que le faltan datos.

**Trampa de método, y es la lección cara de este documento:** «la grilla está vacía» admitía al
menos tres causas —sin datos, sin renderizar, o con los datos interceptados— y se eligió la
primera sin descartar las otras dos. El diagnóstico costó dos correcciones de premisa. Antes de
declarar que falta un dato, hay que comprobar si alguien lo está quitando por el camino.

**Límite que ningún dato resuelve, medido el 2026-08-03.** Aunque el mock devuelva filas, no todo
estado se alcanza con datos. En `/programacion-semanal`, con la tabla ya poblada con dos filas,
`.ps-row-selected` y `.ps-motivo-restriction` **siguen sin existir en el DOM**: la primera es
estado de interacción y la segunda es contenido condicional. Hay que **inducirlas**, no esperarlas.

La consecuencia es de alcance: dar filas al mock cubre los estados **de dato**, y los de
**interacción y contenido condicional** necesitan que la prueba los provoque —seleccionar una
fila, abrir un motivo—. Si B1 se plantea como «que el mock devuelva filas y ya», dos de las seis
reglas huérfanas de la línea A se quedan sin superficie donde medirse y el tramo parecerá cerrado
sin estarlo.

**B2 — Resolver el drift del sidebar.** `--ds-sidebar-width-expanded` vale `15rem` y
`shell-navigation.test.mjs` espera `17.5rem`. Averiguar primero cuál es el correcto: si el token
se cambió a propósito y nadie actualizó el test, o si alguien lo pisó. Cambiar el que no era mueve
el ancho de la barra en toda la aplicación. Hoy este drift contamina cualquier recaptura, porque
se hornearía en la baseline con la firma de quien recapture.

**Va primera de todo.** Sin B, ni A ni C ni E ni G pueden cerrar su verificación visual.

### C · Cerrar la paleta — pequeña

Decisión 6. `--ds-cell-state-bloqueado-*` pasa a `--ds-state-tint-blue`.

Es el último color inventado del sistema. Se ancló el 2026-08-03 razonando solo sobre el canal de
nivel, sin conocer el canal de matiz; con los dos canales a la vista, `--ds-state-tint-blue`
(`#17334f`) es perceptualmente cercano a la coordenada que se le puso. Verificable con el gate de
runtime existente, sin depender de B.

**Condición:** el usuario ve el antes/después antes de fijarlo. La paleta actual tiene aprobación
visual explícita del 2026-08-03 y cambiarla en silencio la invalidaría.

**Solape con G, y conviene resolverlo antes de ejecutar cualquiera de las dos.** C mueve un peldaño
de la escala de celda; G devuelve cada estado a su matiz declarado. Si G se hace primero, es
posible que C se disuelva: `bloqueado` solo lo usan `pg-state-r0` y `pg-state-restr-0`, que son
sub-estados de restricción y no figuran entre los siete estados que el contrato declara para
`programa-general`. Quien abra G debe decidir si C sigue teniendo objeto.

### D · Puerta de servicio para `admin/` — toca seguridad

Decisión 9. `/dev/entrar` solo abre sesión en la aplicación principal; `admin/` valida contra su
propio `/admin/login`, y el repositorio prohíbe teclear credenciales. Es un bloqueo duro: esas 14
pantallas son invisibles para cualquier revisión automatizada.

Se diseña con el mismo candado que la existente —solo desarrollo, sin conceder permisos por
encima de la propia cuenta— siguiendo `docs/superpowers/specs/2026-07-30-dev-door-design.md`,
`src/Core/DevDoor.php` y `tests/test_dev_door_guard.php`.

**Es una vía de autenticación nueva.** Spec propio y revisión, no de pasada.

### E · Usabilidad: altas y medias — grande

Decisión 10, más la 7. De
`goals/repaso-usabilidad-no-tablas/inventario-usabilidad.md`.

**Recuento corregido el 2026-08-03: son 26, no 30.** De las 30 aprobadas salen cuatro que ya
tienen dueño en otra línea: H-24 y H-39 son el panel de inicio (**F**), H-03 es el embebido de
Power BI (decisión 7, resuelta) y H-14 pertenece al goal de tablas ya cerrado. La aritmética la
rectificó la propia sesión de H-08.

**Se ejecuta como goal `usabilidad-altas-y-medias`, en cinco fases por riesgo creciente**, para
que lo barato entre primero y lo arriesgado no bloquee al resto:

| Fase | Qué toca | Riesgo |
|---|---|---|
| F1 · Copia | Tildes en `/pdc`, «0 count» en BI, columnas homónimas | nulo, solo texto |
| F2 · Estados vacíos | Copiar el patrón de `/cnc` a malla semanal, control de cambios, `/pdc` y tarjeta de BI | bajo, añade markup |
| F3 · Consistencia y a11y | Labels y `autocomplete` en admin, contraste de `.bi-chip`, objetivo de 20 px, badge, error de JS | bajo, pero exige specs nuevos |
| F4 · Etiquetas y solapes | Anchos de cabecera y filtro, rail «CONCURRENCIA LPS» | medio, CSS de layout |
| F5 · Navegación y jerarquía | Pestañas de BI, tour de `/plan-compras`, shell de escalamientos, seis `h1` ausentes | el más alto |

Tres criterios que fija esa fase y que conviene respetar: **cada arreglo de F3 lleva su gate**
—`.bi-chip` se arregla en una línea de CSS, pero sin un `expect` de contraste en los specs de BI
vuelve a romperse sin que nadie lo vea—; **verificación por fase, no al final**; y **freno en el
shell de escalamientos**, que sale a goal propio si resulta ser más que envolverlo en el layout
existente.

Los tres de mayor impacto ya identificados: `/control-cambios` sin forma de crear un cambio ni
estado vacío y con filtros truncados; la barra de pestañas de BI con 1626 px en 1116 visibles, que
deja ~3 de 8 módulos inalcanzables; y `/admin/login` sin `<label>` ni `autocomplete`. Incluye el
incumplimiento medido de `.bi-chip` a 3,01:1 (`public/css/bi-control-tower.css:106`), que empareja
`--ds-color-brand-aqua` sobre `--ds-color-state-info-bg`: los tokens están bien, el emparejamiento
no.

La decisión 7 entra aquí: el embebido de Power BI se conserva, con estado de carga y de error, y
enmarcado para que el salto a tema claro se lea como contenido externo. No se puede oscurecer un
iframe ajeno.

**Palanca medida por H-08:** la aplicación no está mal diseñada sino desigualmente terminada.
`/programacion-semanal/cnc`, `/bi/programa-general`, `/proyectos` y `/plan-compras` ya contienen el
patrón correcto de estado vacío; el resto no lo heredó. Copiar el texto de `/cnc` sale más barato
que diseñar nada nuevo. Seis de los ocho puntos heurísticos perdidos se concentran en estados
vacíos y consistencia.

**Depende de D** para la parte de `admin/`, y solo para esa parte.

También absorbe los tres avisos preexistentes de `tokens.css` —un `rgba` y dos tamaños de fuente
fuera de la escala documentada— que hoy se arrastran sin dueño.

### F · Panel de inicio — grande, y es producto

Decisión 8. `/dashboard` es hoy un redirect a `/programacion-semanal`.

**No es un arreglo, es una decisión de producto.** Qué ve un residente al entrar, qué ve un
visualizador, qué ocurre si no hay semana activa. Merece su propio grilleo, no una tarea dentro de
un lote.

### G · Programa General recupera su chip de estado — nueva, y no venía del grilleo

Descubierta el 2026-08-03 al ejecutar B1, y es la razón por la que B1 valía la pena.

> **Corrección de diagnóstico — 2026-08-03, la tercera de este documento y la más cara si no se
> hubiera hecho.** Esta línea se escribió como «restaurar el canal de matiz en las **tres** grillas»,
> sobre la tesis de que las tres colapsaban el matiz contra el nivel y de que unas reglas
> `!important` estaban anulando un mecanismo ya montado.
>
> **La tesis era falsa en su mitad importante, y actuar sobre ella habría dejado las celdas sin
> fondo**, porque nada más las pinta.
>
> Lo que hay de verdad, medido en código y en navegador:
>
> | Módulo | Canal de nivel | Canal de matiz | ¿Defecto? |
> |---|---|---|---|
> | `programacion-intermedia` | fondo de fila | **chip dentro de la celda** (`renderOperationalStateCell`) | **No** |
> | `programacion-semanal` | fondo de fila | **chip dentro de la celda** (`hot.js:951` y `:984`) | **No** |
> | `programa-general` | fondo de fila | **ninguno** — la columna Estado es texto plano | **Sí** |
>
> PI y PS **sí implementan los dos canales**, y bien: `stateChipAttrs()` emite `data-aia-hue`,
> `data-aia-severity` y `data-aia-urgency` sobre un `<span class="ops-state-chip">`, y
> `states-feedback.css` los traduce a fondo por matiz con texto pareado. Verificado en
> `/programacion-intermedia`: un chip con `data-aia-hue="green"` resuelve a `#173d26`, que es
> `--ds-state-tint-green`.
>
> Las colisiones de fondo que este documento reportaba para PI y PS **no son defectos**: son el
> canal de nivel funcionando como se diseñó.

#### El defecto real, y es uno solo

**A `programa-general` nunca se le puso el chip.** Su columna Estado se pinta con
`pgGenericTextRenderer` —texto suelto—, así que el canal de matiz no tiene dónde aparecer. Como
*Actividad Futura* y *En Curso* comparten nivel `healthy`, sin chip no queda nada que los separe:
las dos caen en `--ds-cell-state-ok-bg` y se ven idénticas.

`state-semantics.json` sí les declara matices distintos —**green** y **blue**— y la leyenda de la
toolbar sí los distingue, porque `--pg-dot-future` deriva de `success` y `--pg-dot-progress` de
`info`. **La leyenda promete una distinción que la grilla no puede dar.** Eso es peor que no
distinguir en ninguno de los dos sitios, porque el usuario aprende un código de color que falla.

#### Alcance decidido

**Darle a PG el chip que PI y PS ya tienen.** No se inventa mecanismo: se copia uno existente,
probado y medido, y se dejan las tres pantallas consistentes.

Lo que hace falta, sin entrar en el plan:

- Un renderer para la columna Estado que emita `<span class="ops-state-chip">` con los tres
  atributos, siguiendo `stateChipAttrs()` de PI.
- Un mapa `estado → { level, hue }` para los siete estados de PG. **No se inventa**: sale de
  `state-semantics.json`, que ya los declara.
- El CSS del chip. Hoy está duplicado en `programacion-intermedia.css` y `programacion-semanal.css`
  bajo `.pi-page` y `.ps-page`. Una tercera copia bajo `.pg-page` sería la opción perezosa;
  extraerlo a componente compartido es la que corresponde, y el plan debe decidirlo con el coste
  delante. Ojo: el chip se define **sin `background` a propósito** —lo pinta la capa de
  componentes vía el atributo—, y una copia que lo olvide rompe el mecanismo sin dar la cara.

#### El guard que falta

Un assert que cruce `state-semantics.json` contra el color **resuelto en navegador** —no contra el
texto del CSS, porque `--ds-cell-state-riesgo-bg` se calcula con `color-mix()` y su valor no existe
en ningún archivo— y **con filas reales en la grilla**.

**Nace junto al sembrado de B1, no antes.** Escrito sobre una grilla vacía se quedaría verde, que
es exactamente el fallo que viene a corregir.

#### Por qué ningún guard lo vio

`state-tint-ladder.test.mjs:170` recorre `semantics.moduleMappings` y comprueba que ningún módulo
repita `hue` **dentro del propio JSON**. Nunca mira el CSS ni el DOM. **Un assert que valida una
declaración contra sí misma está verde por construcción.** Documentado en
`memoria/trampas/guard-valida-declaracion-contra-si-misma.md`, con la heurística para no repetirlo:
al escribir un guard, pregúntate qué archivo tendría que estar mal para que fallara; si es el mismo
que lee, no vigila nada.

**Depende de B1**, tanto para verificarse como para que su guard pueda nacer viendo algo.

### F-bis · El autoguardado al entrar — se separa

Abrir `/programacion-semanal` puede disparar `POST /api/semanal/save` y
`POST /api/semanal/auto-program` sin interacción, y la puerta de servicio con `p=<proyecto>`
aterriza justo ahí.

**Corrección de premisa — 2026-08-03, leída del código, no de la wiki.** La trampa
`memoria/trampas/semanal-auto-dispara-mutaciones.md` afirma que ocurre «en cada carga de página,
sin interacción». **Es más estrecho que eso**, y la diferencia cambia el diseño del arreglo:

- `save` con `opcion: 'sanear'` sale de `loadData()` en
  `public/js/modules/programacion_semanal/hot.js:2095-2106`, y va tras un doble guardián:
  `!sanitizedOnLoad && canManageToolbarActions()`. **Depende del rol**: una cuenta sin permiso de
  gestión no lo dispara nunca.
- `auto-program` sale de `run()` en `changeMonitor.js:35-47`, guardado por
  `isRunning || (hasRunOnce && !force)` y exigiendo `db` no vacío y `semana > 0`. **Sin semana
  válida no se dispara.**

Lo midió la sesión de tintes interceptando POST en esa ruta: observó tres a
`datosGeneralesPagina.php` y **ninguno** de los dos que la trampa anuncia. No es que la trampa
mienta: es que describe el peor caso como si fuera el único, y quien la lee sale creyendo que
basta con abrir la página. `memoria/trampas/semanal-auto-dispara-mutaciones.md` debe corregirse
con estas condiciones.

**El fondo sigue en pie: para el rol y la semana adecuados, abrir una pantalla escribe en la base
de datos**, y un residente con permisos de gestión es exactamente ese caso. Se trata como defecto
de integridad con vida propia y no espera a que se diseñe el panel de F.

Es la única línea que este documento crea sin que el usuario la pidiera explícitamente; se separa
porque mezclarla con F la dejaría bloqueada tras una decisión de producto que no tiene fecha.

## Orden y dependencias

```
B  ─────────────────────────────► habilita la verificación visual de A, C, E y G
      │
A  ───┘ (en curso, solo necesita B1 para medir in situ)
G  ────────────► solo PG. Depende de B1: su guard nace con el sembrado o nace ciego
C  ────────────► independiente, verificable con el gate de runtime
D  ────────────► habilita la parte admin/ de E
E  ────────────► depende de D solo para admin/
F  ────────────► independiente, necesita grilleo propio
F-bis ─────────► independiente, no espera a F
```

**G es ahora la más urgente después de B**, y no por tamaño —al medirla encogió de tres módulos a
uno—: es la única que corrige algo que el usuario final ve mal hoy, en una de las pantallas donde
más tiempo pasa.

## Hallazgo posterior al grilleo: hay una cuarta paleta, y está en PHP

Descubierto el 2026-08-03 por la sesión de `DESIGN.md` al preparar una pregunta, y verificado
contra el código.

Los ocho `--ds-color-state-*-light` llevan rotulado un uso de «impresos / XLSX». Ese uso **existe**
—`src/Controllers/Gestion/ReportController.php` exporta con PhpSpreadsheet y pinta el estado de
cada fila— pero **nunca se cableó**. El exportador lleva su propia paleta ARGB escrita a mano en
las líneas 379-384, con pares fondo/fuente y hasta negrita, indexada por los mismos nombres de
dominio que el CSS (`pdc-delayed`, `pdc-ok`, `pdc-critical-delay`…). Y sus valores **no coinciden**
con los `-light`: `FFDCFCE7` frente a `#ddefe6`.

O sea que el sistema tiene cuatro paletas de estado, no tres:

| Paleta | Dónde | Alcance |
|---|---|---|
| `--ds-color-state-*` | `tokens.css` | 4 niveles, oscuro, par fondo/texto |
| `--ds-state-tint-*` | `tokens.css` | 8 matices, oscuro, fondo — ya aliaseada a la anterior |
| `--ds-cell-state-*` | `tokens.css` | 7 peldaños de celda, derivada |
| ARGB del exportador | `ReportController.php` | claro, fondo/fuente/negrita, **inalcanzable desde CSS** |

**No entra en este reparto, y la razón importa:** unificarlas cambiaría el aspecto de los Excel que
la gente ya tiene descargados y archivados. Eso no es deuda técnica que se salda de paso, es una
decisión de producto sobre documentos que ya salieron de la aplicación.

Queda registrado aquí y en el comentario de `tokens.css`, que dejó de afirmar un uso vigente y
ahora dice lo que es: reserva sin cablear, con el exportador y su paleta paralela nombrados.

## Pendiente registrado: el arnés de a11y no distingue «no medible» de «mal»

`tests/browser/support/accessibility.mjs:36` aplana los `incomplete` de axe en la misma lista que
las `violations`. Un «no pude comprobarlo» bloquea igual que un defecto probado, y como las
superficies de este repositorio son translúcidas por diseño —`rgba(28, 36, 31, 0.92)`—, axe no
puede calcular contraste sobre ellas y devuelve `incomplete` **sistemáticamente**.

Medido el 2026-08-03: los nueve de `/programa-general` no eran defecto — 55 nodos entre 13,82 y
15,27:1, cero bajo AA. Se registraron como excepción con la cifra dentro y caducidad al
2026-11-30.

**Queda pendiente separar las dos categorías en el arnés.** No entra en ninguna línea de este
reparto: cambia la política de accesibilidad de todo el repositorio y merece decisión propia.
Documentado en `memoria/trampas/axe-incomplete-cuenta-como-violacion.md`.

## Fuera de alcance de todo el reparto

- Mobile, tablet y cualquier viewport bajo 1180 px; el tema `linen` (`AGENTS.md`).
- Migrar `admin/` al design system: sigue siendo mini-app aparte con AdminLTE, decisión vinculante.
- Colapsar los 8 matices contra los 4 niveles. El contrato de dos canales de `tokens.css:234-295`
  lo aparta explícitamente y `state-tint-ladder.test.mjs` lo vigila. Si algún día se hace, será
  decisión de producto con evidencia de uso, no limpieza técnica.
- Los 9 hallazgos de severidad baja del inventario de usabilidad.

## Cómo se verifica cada línea

Además de lo suyo, todas comparten:

```bash
npm run test:design-system:static
npm run test:design-system:runtime
node scripts/design-system-audit.mjs
```

Navegador a 1180×820 dark contra el contenedor, con la superficie **cargada y con datos**.

### Rojos preexistentes, declarados

Verificados con `git stash` el 2026-08-03; no son de ninguna de estas líneas y no se maquillan:

- `design-system-audit.mjs`: `profesionales` y `subcontratistas` con `hardcoded-hex: 1 > 0`.
- `shell-navigation.test.mjs`: espera `--ds-sidebar-width-expanded: 17.5rem`, el token vale
  `15rem`. **Esto sí lo resuelve B2**, y es el único rojo preexistente con dueño en este reparto.

### Trampas que aplican a más de una línea

- `memoria/trampas/audit-ve-color-en-comentarios.md` — el audit cuenta hex y `rgba()` escritos
  dentro de comentarios CSS. Describir colores con palabras.
- `memoria/trampas/gate-estatico-no-ve-tokens-rotos.md` — un gate que lee archivos da verde con un
  token que apunta a una variable inexistente.
- `memoria/trampas/gate-visual-tolerancia-enganosa.md` — recapturar exige
  `--update-snapshots=all`, y **baseline por baseline**, tras confirmar que el delta visible es el
  propio.
- `memoria/trampas/semanal-auto-dispara-mutaciones.md` — no usar `/programacion-semanal` para QA
  de solo lectura.
- `tests/browser/support/contrast.mjs` existe desde el 2026-07-28 y compone alfa sobre los
  ancestros. **No escribir otra sonda de contraste**: varias superficies del sistema son
  translúcidas y medirlas sin componer da cifras inventadas.
