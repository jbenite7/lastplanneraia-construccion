# Barrido único de auditoría de diseño — 2026-08-03

Alcance: todas las rutas HTML servidas de la app principal (~25 vistas navegables desde el router,
sesión `test.R` / `test.A` vía dev door) y las vistas de `admin/` (7 vistas HTML; `/usuarios/cargos`
resultó ser un endpoint JSON, no una vista). Viewport 1180×820, dark. Solo lectura + navegador —
no se editó ni un archivo del repo. Sin interacción con toolbars que mutan datos (semanal/cic/cnc/cnp
vistos solo en su estado inicial vacío, sin clicks de acción).

Script suelto (no forma parte de `tests/`): capturó screenshot + errores de consola por ruta,
guardado en `.superpowers/sdd/2026-08-03-cierre-dark-mode-fases-0-3/barrido/` (`results.json` +
PNGs, referenciados por nombre abajo).

## Matriz por superficie

### App principal (sesión test.R, proyecto PDC Sandbox E2E)

| Ruta | Capturable | Consola | Hallazgos |
|---|---|---|---|
| `/dashboard` (Programación Semanal) | sí (`app-_dashboard.png`) | 0 errores | Estado vacío correcto y explicativo. Sin hallazgos nuevos. |
| `/dashboard/escalamientos` | sí (`app-_dashboard_escalamientos.png`) | 0 | Kanban por rol claro; columnas vacías bien resueltas con check verde. Sin hallazgos. |
| `/indicadores` | sí (`app-_indicadores.png`) | 0 | **[a] Severidad 3** — el contenido central es un iframe/gráfico embebido con fondo **blanco sólido** a pantalla casi completa, rompiendo el contrato dark por completo (ver top-1 global). |
| `/internal/design-system` | con `test.R`: 403 (esperado, requiere rol A vía `DesignSystemLabAccessPolicy`); con `test.A`: sí (`app-testA-_internal_design_system.png`) | 0 (con test.A) | Es la superficie más pulida del barrido: jerarquía tipográfica, tokens visibles, sin ruido. Sin hallazgos. |
| `/control-cambios` | sí | 0 | Tabla de filtros + estado vacío correcto. Sin hallazgos. |
| `/pdc` (Plan de Compras) | sí | 0 | Chips de estado con conteos en 0, coherente. Sin hallazgos nuevos. |
| `/plan-compras` | sí | 0 | Onboarding modal "Paso 1 de 6" se ve bien integrado al dark; historial de versiones vacío correcto. |
| `/profesionales` | sí | 0 | Tabla live-edición legible. Sin hallazgos. |
| `/programa-general` | sí | 0 | **[b] Severidad 3** — mojibake: "CapÃ­tulo" en vez de "Capítulo" (doble-encoding UTF-8/Latin1) visible en la celda Actividad. Además iconos de datepicker se superponen a los dígitos de fecha en columnas angostas ("2026-04-3⊙"). |
| `/programacion-intermedia` | sí | 0 | **[c] Severidad 3** — cabeceras de columna parten palabras carácter a carácter en vez de hacer word-wrap ("Sem-anas Inicio", "Diseño s y Especi ficacio nes"), ilegible sin zoom. |
| `/programacion-semanal` | sí | 0 | Igual a `/dashboard`. Sin hallazgos nuevos. |
| `/programacion-semanal/cic` | sí | 0 | Mismo patrón [c] en cabeceras ("Sema-nas en el Proyec-to"). |
| `/programacion-semanal/cnc` | sí | 0 | Limpia, sin datos de ejemplo — sin el patrón [c] porque las cabeceras son cortas. |
| `/programacion-semanal/cnp` | sí | 0 | Igual, limpia. |
| `/subcontratistas` | sí | 0 | Tabla live-edición vacía, correcta. |
| `/proyectos` | sí | 0 | Cards de proyecto bien jerarquizadas, badges de rol legibles. Sin hallazgos. |
| `/bi/contratistas` | sí | 0 | **[d]** ver top-1 (overflow de tabs). Resto de la superficie limpio. |
| `/bi/control-tower` | sí | 0 | **[d]** overflow de tabs. KPIs en 0 bien resueltos. |
| `/bi/curva-s` | sí | 0 | **[d]** overflow de tabs. Gráfico vacío sin datos, ejes limpios. |
| `/bi/intermedia` | sí | 0 | **[d]** overflow de tabs. |
| `/bi/pdc` | sí | 0 | **[d]** overflow de tabs. |
| `/bi/programa-general` | sí | 0 | **[d]** overflow de tabs. Donas en gris monocromo sin diferenciación fuerte entre estados. |
| `/bi/responsables` | sí | 0 | **[d]** overflow de tabs. |
| `/bi/semanal` | sí | 0 | **[d]** overflow de tabs. |
| `/login` | sí | 0 | Bien resuelto, consistente con el resto. |

### admin/ (sesión test.A vía `/admin/dev/entrar`)

| Ruta | Capturable | Consola | Hallazgos |
|---|---|---|---|
| `/admin/dashboard` | sí | 0 | **[e] Severidad 2** — íconos decorativos de las stat cards se superponen visualmente al número/texto ("17/50" cruzado por el ícono de proyectos, "141.72MB" sobre el ícono de base de datos). Paleta de cards (teal/verde-oliva/naranja/rojo/violeta) no corresponde a los tokens del design system dark de la app principal — es esperable por ser mini-app arquitectónicamente separada (AGENTS.md), pero es un salto de consistencia visual notorio si el usuario navega entre ambas. |
| `/admin/matching/config` | sí | 0 | Formulario claro, buen uso de ayudas inline. Sin hallazgos nuevos. |
| `/admin/matching/family-catalog` | sí | 0 | Tres paneles bien organizados. Sin hallazgos. |
| `/admin/pdc/limpieza` | sí | 0 | Jerarquía de peligro (checkboxes + confirmación por nombre exacto) bien resuelta para una acción destructiva. Sin hallazgos. |
| `/admin/proyectos` | sí | 0 | Tabla densa con toggles y acciones en semáforo de colores brillantes (naranja/verde/rojo) — mismo salto de paleta que [e]. |
| `/admin/usuarios` | sí | 0 | Igual a proyectos. Mojibake también presente en datos: "InnovaciÃ³n" en el nombre de cargo de un usuario — mismo origen que [b], confirma que es un problema de datos/encoding, no solo de una vista. |
| `/admin/usuarios/cargos` | responde 200 pero es JSON crudo, no HTML — no es una "vista" auditable con las tres lentes. El JSON en sí usa escapes `ñ`/`ó` correctos (evidencia de que el mojibake de [b]/[e] ocurre en la capa de salida HTML, no en el JSON). |

## Rutas rotas o inaccesibles

Ninguna. Las 25 rutas de la app principal y las 7 de `admin/` respondieron 200 (o 403 esperado por
RBAC en `/internal/design-system` con rol R). No hubo errores de consola en ninguna superficie.

## Top-5 hallazgos globales

1. **[Severidad 3-4, Refactoring UI/contrato] Iframe blanco en `/indicadores`** — rompe el contrato
   dark-only de punta a punta con un bloque casi a pantalla completa en blanco sólido. Es la
   superficie con peor infracción visual del barrido.
2. **[Severidad 3, contrato "sin overflow horizontal"] Tab bar de `/bi/*` desbordado** — en las 8
   superficies de BI el último tab ("Plan de Compras") se corta a media palabra ("Plan d") fuera del
   viewport de 1180px. Viola explícitamente la regla de AGENTS.md de "ausencia de overflow
   horizontal en el viewport permitido".
3. **[Severidad 3, Refactoring UI] Cabeceras de tabla que parten palabras carácter a carácter** en
   Programación Intermedia y CIC ("Sem-anas", "Diseño s y Especi ficacio nes") — el contenedor de la
   cabecera no hace word-wrap, hace character-wrap; ilegible sin entrecerrar los ojos.
4. **[Severidad 2-3, Nielsen: match entre el sistema y el mundo real / integridad de datos]
   Mojibake UTF-8/Latin1 doble-codificado** — "CapÃ­tulo" en Programa General y "InnovaciÃ³n" en un
   cargo de `admin/usuarios`. Aparece en al menos dos módulos distintos con datos reales, sugiere un
   problema de encoding en la capa de salida (no en el JSON crudo, que sí escapa bien).
5. **[Severidad 2, Nielsen: consistencia] Salto de lenguaje visual entre `admin/` y la app
   principal** — stat cards con paleta saturada (teal/naranja/violeta/rojo) e íconos que se
   superponen al texto en `/admin/dashboard`, frente al dark minimalista y tokenizado del resto de
   la app. Arquitectónicamente admin/ está fuera del alcance del design system (AGENTS.md), pero el
   contraste es abrupto para quien navega entre ambos paneles.

## Pasada horaria del 2026-08-04 — solo lo nuevo o lo que cambió

No se repite la matriz: las 32 superficies siguen respondiendo y sin errores de consola. Lo que
cambió respecto al barrido del 03:

**Cerrados desde entonces** — [b] mojibake (task 20), [c] cabeceras partidas carácter a carácter
(tasks 24 y 26), y el disparador de menú blanco de PS (task 28).

**Top-1 reclasificado, no resuelto.** El bloque blanco de `/indicadores` **no es CSS nuestro**: es
un `<iframe>` de `app.powerbi.com`, otro origen, cuyo interior ninguna hoja de este repo puede
tematizar. El barrido anterior lo daba a entender como infracción propia y por eso nunca se
arregló. Va como **C-22**; la salida está en el tema del informe en Power BI, fuera del repo.

**Top-2 medido y parcialmente resuelto.** Las 8 pestañas de `/bi/*` suman 1626 px en un carril de
1116: quedan invisibles «Plan de Compras», «Proveedores (CIC)» y «Responsables (CIP)». **No caben**
— sin iconos y a 13 px siguen sumando 1363 px, y acortar etiquetas es tocar texto de navegación.
Queda como **C-23**.

**Hallazgo nuevo, arreglado (`e38be1c`).** Al medir el carril apareció lo que nadie había mirado:
el pulgar del scrollbar de las 8 rutas de BI usaba `--aia-separators`, un gris claro **estático sin
variante oscura**, rindiendo una franja casi blanca sobre el lienzo oscuro — justo bajo las
pestañas, el elemento más brillante de la vista. Retokenizado al par dark-aware del shell. Misma
familia que C-20.

**Hallazgo nuevo, arreglado (`ed8c411`).** Los chips contadores de PI y PS estaban encajonados a
155 px con `!important` desde `legacy-bridge.css`: seis de los ocho de PI partían en dos renglones
y ocupaban tres filas sobre la tabla. Ahora se dimensionan por su contenido — PI baja a dos filas
(98 → 88 px), PS cabe en una. Programa General no usa ese selector y queda intacto, así que sus
goldens pendientes no se mueven.

**Método, dos veces en la misma pasada.** Un desvanecido de borde para anunciar el corte de las
pestañas se probó y **se descartó**: la ruta tiene presupuesto de cero funciones de color y mover
la regla de archivo habría sido esquivar el gate. Y el primer intento del arreglo de BI dejó el
audit en rojo porque **el comentario citaba valores de color literales** — la trampa que la wiki ya
tenía puesta en `audit-ve-color-en-comentarios`. Ambas cosas las atrapó el gate, no yo.

## Segunda pasada horaria del 2026-08-04 — solo lo nuevo

Método distinto y más barato: en vez de navegar ruta por ruta, se miden las 28 rutas
(20 de la app principal + 8 de `admin/`) desde iframes del mismo origen, con la misma batería por
superficie. Resultado global: **cero fugas de color claro y cero overflow horizontal en las 28**.
Las dos fugas que quedaban vivas —el disparador de menú de PS y «Ver alertas» de PDC— ya no
aparecen: los cerraron `312ba9b` y `be5eae7`.

**Lo que quedaba era accesibilidad, no color.** Cinco controles de solo icono se anunciaban sin
nombre, arreglados en `0471e2f`: los dos botones de **borrar** de `/profesionales` y
`/subcontratistas` —un botón que elimina una fila, anunciado como nada—, el de editar de CIC y CNC,
y el menú lateral de `admin/`, presente en sus 8 rutas. Todo metadato: no cambia comportamiento.

Dos cosas que enseñó el barrido:

- **El patrón correcto ya existía en el repo.** `CNP.view.php` ya traía `aria-label` más
  `aria-hidden` en el icono; CIC y CNC se habían quedado atrás. Se copió en vez de inventar otro.
  Es el mismo hallazgo que con los chips de PG: cuando algo falta, conviene mirar si un módulo
  hermano ya lo resolvió.
- **Una tabla vacía esconde defectos.** CNC salió limpia en la medición porque no tenía filas; su
  botón de editar tiene el mismo defecto que CIC, solo que latente. Medir solo el estado vacío da
  una foto incompleta.

Falsos positivos descartados: los `htFocusCatcher` de Handsontable (`role="presentation"`,
`aria-hidden="true"`) y los radios del laboratorio, que usan etiqueta envolvente y mi sonda solo
miraba `label[for]`.

Al registro: **C-25**, la marca «AIA» de `admin/` a 4,46:1 frente al mínimo de 4,5.

## Tercera pasada horaria del 2026-08-04 — la estructura del documento

Las dos pasadas anteriores dejaron color, overflow y controles anónimos medidos y limpios; repetir
esa batería no habría enseñado nada. Esta barre una dimensión **que ninguna pasada había mirado**:
título de página, jerarquía de encabezados y landmarks.

**Hallazgo principal, arreglado (`e6f7f4c`).** 6 rutas tenían título descriptivo y al menos 9 decían
solo «Last Planner AIA»: en la pestaña, el historial y los marcadores eran indistinguibles entre sí
(WCAG 2.4.2, nivel A). El mecanismo ya estaba bien pensado —`linksComunesHead2.js` dice literalmente
«Respetar el `<title>` definido por la vista; solo aportar el genérico si falta»— y nadie lo
aprovechaba en once vistas. Se declara el título en cada una con el nombre que ya usan sus migas de
pan, sin inventar nomenclatura.

**Al registro (C-30).** La otra mitad del problema es estructural y no es un retoque de barrido:
solo 3 rutas declaran `<main>` y la mayoría no tiene `h1`, así que con lector de pantalla no se
puede saltar al contenido ni recorrer la página por encabezados.

**Vuelve a aparecer el mismo patrón de la pasada anterior:** la solución correcta ya existía en el
repo —seis rutas con título propio, y un comentario explicando el mecanismo— y solo hacía falta
extenderla. Van cuatro veces en la sesión (chips de PG, `aria-label` de CNP, `.pdc-btn-alertas`, y
ahora los títulos).

## Cuarta pasada horaria del 2026-08-04 — la primera con datos reales

Las tres pasadas anteriores midieron el sandbox, donde casi todas las tablas están vacías. Esta
recorre las mismas rutas sobre un proyecto **con datos** («Optimización Aeropuerto JMC», solo
lectura; no Da Porto, que tiene protecciones explícitas). El cambio de método vale más que
cualquier hallazgo anterior de tabla.

**Hallazgo único y sistémico: 51 truncamientos silenciosos, y cero celdas con elipsis en toda la
aplicación.** El reparto:

| Superficie | Recortes mudos | Qué se corta |
|---|---|---|
| Programación Intermedia | **29** | 26 de sus 27 códigos «Id» (`9.5.1.1` pierde 18 px), más nombres de subcontratista |
| Programación Semanal | 9 | celdas de la rejilla |
| Programa General | 6 | códigos «Id» (`3.5.2.1.1` se ve igual que `3.5.2.1`, otra actividad de la misma tabla) |
| Subcontratistas | 7 | **direcciones de correo** (`proyectos@concreacero.`) |
| Profesionales | **0** | limpia con 24 filas — prueba de que no es inevitable |

No es un defecto de un módulo: es una **laguna del contrato de tabla**, que nunca especificó qué
pasa cuando el contenido no cabe. Va a **C-31**, elevado.

**Nada aplicado, y por qué.** Se probaron dos mitigaciones y se descartaron las dos con evidencia:
la elipsis exige `nowrap`, que rompería el ajuste de «Actividad»; y aunque las celdas que deben
envolver se marcan con `force-wrap`, poner elipsis al resto ocultaría texto que hoy se ve entero en
celdas que envuelven por defecto. La cura es el ancho de columna, que vive en el JS y es decisión
del usuario.

**Lo que este barrido enseña sobre los anteriores:** medir el estado vacío durante toda la sesión
produjo hallazgos correctos pero sesgados, y en un caso —C-24— con la recomendación invertida. El
estado vacío es un caso, no el caso.

## Quinta pasada horaria del 2026-08-04 — los estados vacíos, censados por fin

Dimensión que el mandato nombra desde el principio y que nunca se había barrido de forma
sistemática: solo se habían elogiado tres o cuatro sueltos. Simetría útil: el sandbox vacío, que
para auditar datos no sirve, es **el fixture ideal para esto**.

**El resultado es mayoritariamente bueno, y conviene decirlo.** Los estados vacíos de esta
aplicación explican **por qué** está vacío y **qué hacer**, que es más de lo que se ve en la mayoría
de productos:

- Programación Semanal: «Sin actividades programadas esta semana» + «Usa «Agregar Actividad» para
  programar una, o «Autoprogramar Actividades» para traerlas desde la programación intermedia».
  Nombra los dos botones exactos.
- PDC: «No hay paquetes de contratación» + de dónde se arman.
- Panel lateral de PS/PI/escalamientos: «Ninguna actividad seleccionada» + «Haz clic en cualquier
  celda de la planilla…».

**La excepción, a C-33:** `/control-cambios` dice «No hay solicitudes de cambio registradas para
este proyecto.» y ahí termina — sin explicar de dónde salen y **sin ninguna acción en toda la
vista**. Es el único que deja sin salida, y rompe el estándar que la propia app cumple en el resto.
No se redactó la frase que falta porque explica una regla de dominio.

**Método, una corrección propia:** la primera sonda evaluaba el título del estado vacío **sin su
párrafo**, y daba «no dice qué hacer» en estados que sí lo dicen. Se rehízo midiendo el bloque
entero. Sin esa corrección, este barrido habría reportado como defectuosos los mejores estados
vacíos de la aplicación.

## Sexta pasada horaria del 2026-08-04 — regresión, y un censo mío que estaba mal

Los ejes de auditoría están agotados: reposo, foco, hover, deshabilitado, error, vacío, movimiento,
zoom, color, estructura, datos reales, gráficos, diálogos y elementos nativos. Así que esta pasada
no busca novedad: **comprueba que los siete cambios de la noche no rompieron nada**, midiendo en las
28 rutas los cinco indicadores que esos cambios tocaron.

**Sin regresiones.** Las 28 rutas dan cero en fugas de color claro, diálogos con cromo del
navegador, `<hr>` invisible, títulos genéricos y overflow horizontal. Único aviso, ya conocido y
descartado: los dos radios del laboratorio, que usan etiqueta envolvente y la sonda no ve.

**Pero contar en vez de truncar destapó 234 controles anónimos** (`e94f430`): 150 en
`/admin/proyectos` —tres por proyecto: activo, acceso y plan de compras, sobre 50— y 84 en
`/admin/usuarios`, uno por usuario. Conceden o revocan acceso a proyectos y activan cuentas, y con
lector de pantalla sonaban todos igual en una tabla de 50 filas. Cada uno recibe ahora su columna y
su fila: «Activo — Paris Campestre», «Usuario activo — Juan Felipe Benitez Ramos».

**Corrige un censo mío.** Al arreglar los dos interruptores del dashboard afirmé que eran «las dos
únicas etiquetas vacías de todo `admin/`». No lo eran: mi `grep` con `[^>]*` **no puede cruzar el
`<?php echo … ?>`** del atributo `for`, porque el bloque PHP contiene `>`. Los 234 quedaron fuera
por una limitación del patrón, no porque no existieran. Es la misma familia de error que los ceros
del ciclo 18 — **un resultado vacío merecía sospecha, no confianza**.

## Documento

`docs/superpowers/barrido-diseno-2026-08-03.md` (este archivo). Capturas en
`.superpowers/sdd/2026-08-03-cierre-dark-mode-fases-0-3/barrido/`.

## Pasada 7 — 2026-08-04 · foco y etiquetado de modales, ahora en TODAS las superficies

Dimensión elegida por ser la que yo mismo declaré sin medir al cerrar el ciclo 28.
Recorridas `/programa-general`, `/programacion-intermedia`, `/pdc`,
`/control-cambios`, `/plan-compras` y `/profesionales` a 1180×820 dark.

**Aplicado y verificado (`9ff6bf6`):** el defecto del ciclo 28 no era de
Programación Semanal, era del repositorio. Tres `aria-labelledby` más apuntando a
ids inexistentes —`modalContrato`, `modalDefinirContratos`, `modalordenDeCambio`,
todos citando una variante con guion bajo del id real— y cuatro modales más sin
`tabindex="-1"`. Totales tras el arreglo: **colgantes 3 → 0, sin `tabindex`
7 → 0**.

**Limpias de origen:** `/plan-compras` (usa `<dialog>` nativo, los 3 con nombre) y
`/profesionales` (sin modales). `/programa-general` ya había quedado limpia por
el arreglo del ciclo 28, porque los modales de semana son compartidos.

**Nuevo, sin aplicar:** `/control-cambios` tiene **12 ids duplicados**, diez de
ellos campos de filtro `buscador*`. Ver C-41.

**Sin hallazgos nuevos** en las lentes 2 y 3 para esta dimensión: los modales ya
consumen la primitiva `aia-modal` y su tipografía y espaciado salen del sistema.

## Pasada 8 — 2026-08-04 · regresión del botón flotante en todas las superficies

El cambio del carril a FAB (`2c39fe0`) tocó **CSS compartido y un partial que
monta toda la app**, y solo se había verificado en tres superficies. Este barrido
es de regresión. Diez superficies medidas a 1180×820 dark, más `admin/`.

**Resultado: sin regresiones.** En las diez, `padding-right` del body a **0**,
**cero overflow horizontal**, y el contenido llega al borde derecho (hueco de 0
a 8 px, que es el respiro propio de cada vista). En `admin/` no hay vía de
regresión posible: se comprobó que **no carga** `aia-design-system.css`, de donde
colgaba la regla.

**Hallazgo nuevo, y agranda el beneficio del cambio.** El cajón LPS **solo se
monta en tres superficies** —Programación Semanal, Intermedia y Programa
General—. Las otras siete medidas (`/pdc`, `/plan-compras`, `/profesionales`,
`/subcontratistas`, `/control-cambios`, `/bi/control-tower`, `/proyectos`) **no
montan ni el carril ni el cajón**, y sin embargo todas reservaban los mismos
46 px. Es decir: en **7 de 10 superficies el pasillo derecho no protegía nada**,
era ancho perdido a cambio de nada. En el commit esto se dijo solo de `/pdc`;
medido, es el caso mayoritario.

El propio CSS ya avisaba de esto para una vista —«el selector de proyectos no
monta el rail ni el drawer, así que reservarle el ancho solo le deja un margen
derecho muerto»— y se había resuelto con una excepción puntual
(`:not(.project-selector-page)`) en vez de mirar cuántas más estaban igual.

Sin cambios aplicados en esta pasada: no había nada que arreglar.

---

## Pasada final de la campaña — 2026-08-05 (Task 31)

Cierre del barrido. **28 superficies** medidas en navegador a **1180×820 dark** sobre la rama
`campana/cierre-dark-mode-2` (HEAD `58ba25ab`), servida en un contenedor efímero propio (`php -S`
con router de ruta absoluta que replica el `.htaccess`; imagen `last-planner-aia-app`, red
`last-planner-aia_default`, desmontado al terminar). Sesión `test.R` en `PDC Sandbox E2E` para la app
y `test.A` vía `/admin/dev/entrar` para el panel. **Proyectos en solo lectura: no se escribió ni un
dato.** Tres lentes en orden — `impeccable-audit` (contrato: capas, tokens, overflow, consola),
`ux-heuristics` (Nielsen) y `refactoring-ui` (jerarquía, densidad, piso táctil).

Se consolida **contra este mismo documento**: solo se registra lo **nuevo o cambiado** por la campaña.

### Lo que la campaña cerró de este barrido

| Hallazgo del 2026-08-03 | Estado hoy | Evidencia |
|---|---|---|
| **[1]** Iframe blanco de `/indicadores` | **enmarcado** (Task 21, C-22). El blanco interior es del informe y es tarea del usuario dentro de Power BI | passe-partout tokenizado, hairline único |
| **[2]** Tab bar de `/bi/*` desbordada | **cerrado** (C-23) | 8/8 vistas de BI con `scrollWidth == clientWidth == 1180` |
| **[3]** Cabeceras partidas carácter a carácter en PI y CIC | **cerrado en PI y CIC** (C-16/C-31) — **pero sigue vivo en `/control-cambios`**, que no estaba en el censo original → `F-1` | 0 cabeceras recortadas en las 6 superficies de rejilla |
| **[4]** Mojibake `CapÃ­tulo` / `InnovaciÃ³n` | **no reproducible.** Barrido de texto con `/Ã[-¿]\|Â[ -¿]\|â€/` sobre PG, PI, PS, `/proyectos`, `/control-cambios`, `/admin/usuarios` y `/admin/proyectos`: **0 coincidencias** | era dato de un proyecto concreto, no de la capa de salida |
| **[5]** Salto de lenguaje visual de `admin/` | **atenuado, no cerrado** (C-25, C-29, C-38 aplicados). La paleta de las stat cards sigue siendo la de AdminLTE: es mini-app aparte por `AGENTS.md` | contraste del error 11,42:1; marca del pie 7,60:1 |

### Contrato, en las 28 superficies

**Cero regresiones y cero rojos de contrato.** Todas responden 200; **0 errores de consola** en las
28; **0 desbordamiento horizontal** (`scrollWidth == clientWidth == 1180` en las 28); **28/28 con
`<main>` y con un `h1` real** —lo que cierra C-30 medido en producto, no en diff—; **0 celdas y 0
cabeceras recortadas** en las seis superficies de rejilla. Es el resultado más limpio de los cuatro
barridos de la campaña.

### Hallazgos nuevos: 9, ninguno bloqueante

Ninguno pertenece a las categorías que la campaña dio por cerradas *en las superficies que auditó*;
los dos de severidad 3 son **residuos en superficies que el censo original no cubrió** (`/control-cambios`)
o **canales que ninguna lente anterior midió** (los gatillos de ayuda de PI). Todos se **registran**
en `docs/DESIGN-AUDIT.md` como `F-1` … `F-9`. **Al medir (2026-08-05) nada se había aplicado**, por la
regla de la campaña: a esa altura un cambio de píxel movía goldens ya aprobados y un cambio de
comportamiento necesitaba al usuario. **Actualización (Task 7 Frente 0, 2026-08-10):** tres de los
nueve sí se aplicaron después, con su golden reaprobado — `F-1`, `F-2` y `F-3` — verificado contra
`docs/DESIGN-AUDIT.md`, que es donde vive la disposición real; el resto sigue sin tocar.

| Id | Severidad | Superficie | Qué se midió | Estado (2026-08-10) |
|---|---|---|---|---|
| `F-1` | 3 | `/control-cambios` | `word-break: normal` + columnas de 55-74 px parten «Priorid/ad», «Responsa/ble», «Intervento/ría»; cabecera de 94 px en 3 líneas | **done** (`d18d168d`) |
| `F-2` | 3 | `/programa-general` | la `htAutocompleteArrow` de 16 px se superpone al último dígito de la fecha: caja interna 84 px para 88-90 px de contenido en 3 de 4 celdas | **done** (`b6d32f3e`), golden reaprobado |
| `F-3` | 3 | `/programacion-intermedia` | 8 `a.pi-help-trigger` de **8×8 px**, `tabIndex 0`, `aria-label` nulo y `title` vacío, con el tooltip atado solo a `mouseenter` | **done** (`146ddf7d`) |
| `F-4` | 2 | 8 vistas de `/bi/*` | el botón «Quitar filtro» del chip mide **28×20 px** | backlog ICE, sin aplicar |
| `F-5` | 2 | `/programacion-semanal/cic` | 7 ids repetidos (`cuadroModal`×3, `actualizacion`, `form_calidad`, `form_adm`, `form_GSA`, `form_sst`, `form_obs`) | backlog ICE, sin aplicar |
| `F-6` | 1 | `/control-cambios` | dos estados vacíos apilados: `sEmptyTable` (C-33) y `sInfoEmpty` («Sin solicitudes») | backlog ICE, sin aplicar — la ratificación de C-33 que lo bloqueaba ya llegó (2026-08-10) |
| `F-7` | 2 | `/profesionales`, `/subcontratistas`, `/programacion-intermedia` | la casilla nativa de Handsontable mide **13×13 px** | backlog ICE, sin aplicar |
| `F-8` | 1 | `/programa-general-actualizar` | `modal-eliminar-semana-body-texto` duplicado | backlog ICE, sin aplicar |
| `F-9` | 1 | `public/css/programacion-intermedia.css:262` | `rgba(245, 158, 11, 0.24)` en crudo dentro de un módulo migrado | backlog ICE, sin aplicar |

**Falsos positivos descartados y por qué**, para que la próxima pasada no los vuelva a levantar: los
`label.sr-only` que el detector de recorte marca en `/proyectos`, CIC, CNC y CNP están
*visualmente ocultos a propósito* (`clip-rect`), y los `input` de 1×1 (`pdc-sr-only`,
`bi-switch-input`) y de 13×13 (`custom-control-input`) son casillas ocultas cuyo control real es la
etiqueta visible. La pastilla « blanda» de PI **no** es hex suelto: sale de `--pi-due-bg`/`--pi-due-text`.

### Límite declarado

El barrido midió **estructura, geometría y contrato**, no contraste píxel a píxel de las 28
superficies: eso ya lo cubren los guards de matiz y los cuatro specs visuales del repo, y repetirlo
aquí habría sido volver a medir entradas que no cambiaron.
