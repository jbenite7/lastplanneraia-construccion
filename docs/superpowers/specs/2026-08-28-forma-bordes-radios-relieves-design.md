---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-28
areas: [design-system]
fuente: encuesta de 40 preguntas con Felipe sobre forma, bordes, radios, relieves y densidad de tablas, sesión 2026-08-28
resumen: "La capa de forma del design system: radios, bordes, relieves y densidad tipográfica de cada primitiva en cada módulo. 40 decisiones tomadas; pendiente del visto final."
---

# Forma, bordes, radios y relieves — spec en construcción

**Versión 0.7** — las 40 decisiones respondidas (30 de la encuesta original + 10 de la
ampliación pedida por Felipe: densidad, compactación, relleno y tipografía de tablas,
encabezados y celdas). Encuesta completa. Hermana de
[[docs/superpowers/specs/2026-08-28-temas-claro-oscuro-end-to-end-design]] (los temas
deciden el color; esta decide la forma). Pendiente de la revisión final de Felipe: hasta
ese visto, nada de aquí autoriza código. El paso siguiente es `writing-plans` — un solo
plan con la spec hermana, porque comparten fase cero y goldens.

## El terreno

La forma vive hoy en 18 tokens de radio (`--ds-radius-*`: control 12px, tarjeta 16px,
panel/modal 24px, chips píldora), un solo grosor de borde (1px), 5 sombras calibradas para
penumbra, y una capa `legacy-overrides` que fuerza los radios sobre el legado con
`!important`. Pieza nueva sin forma decidida: la bandera de gravedad (spec hermana, D2–D8).

## Decisiones (1–6)

### F1. Personalidad: mixta por jerarquía — «el marco abraza, el dato es recto»

Contenedores (tarjetas, paneles, modales) conservan sus esquinas generosas; los portadores
de datos (chips, celdas, banderas) se endurecen a 3–4 px. Un chip píldora dentro de una
fila de 24 px regala sus dos puntas, y la bandera es rectangular por naturaleza.
**Descartado:** angular total (cambia la cara entera y todos los goldens por estética sin
queja detrás), suave por inercia (deja vivo el conflicto píldora-celda y a la bandera sin
regla formal).

### F2. Frontera mínima: solo lo que vive en la tabla se endurece

Chips, celdas y banderas pasan a 3–4 px — justo las piezas que ya regeneran goldens por
los tintes nuevos y la bandera, así que sale gratis. Botones (12), inputs (12), marco de
tabla (16), tarjetas (16), modales (24) y popovers (16) quedan como hoy. Los selectores
dentro de celdas cuentan como celda, no como input.
**Descartado:** endurecer también los controles (formularios de 15 módulos al diff por
simetría), tres niveles por función (un peldaño nuevo que gobernar para siempre).

### F3. La bandera contra la esquina: la cabecera amortigua

La esquina curva superior del marco de tabla siempre pertenece a la cabecera — que toda
tabla ya tiene por contrato — así que la bandera nunca la toca. En la esquina inferior
rige el recorte del contenedor: mordisco mínimo solo cuando la última fila lleva bandera.
Resuelve el pendiente de diseño fino de la spec hermana.
**Descartado:** recorte en todas partes (la bandera decapitada en la fila más visible),
endurecer el marco de tabla (ceder F1 a la primera dificultad).

### F4. Un grosor de borde; la jerarquía es del color

1 px en todo. La distinción control/separador ya está pagada con color, medida (WCAG
1.4.11) y con guard. Las medias líneas y los 1,5 px se pintan distinto según densidad de
pantalla y zoom — bordes que bailan entre máquinas y goldens que difieren por runner.
**Descartado:** controles a 1,5 px (segundo canal para el mismo dato + render
inconsistente), separadores a 0,5 px (lotería en pantallas de densidad simple).

### F5. Rejilla por función: se edita → rejilla; se lee → renglones

La hoja editable (PG, PI, PS) delimita sus celdas porque la rejilla es el mapa de
navegación con teclado; el listado de lectura (restricciones, profesionales,
subcontratistas) fluye por renglones horizontales. Formaliza la dirección de facto.
**Descartado:** rejilla en todo (fragmenta los tintes de estado en mosaicos), renglones en
todo (la hoja editable pierde el marco de la celda — regresión funcional).

### F6. El punteado del vacío, solo donde invita

El borde discontinuo queda reservado a los vacíos que aceptan acción (soltar un archivo,
crear el primero); el vacío informativo («sin restricciones esta semana») usa marco sólido
en tono susurro. El trazo dice qué se puede hacer. El marco del vacío se conserva siempre:
es la prueba de que el sistema respondió — «miré y no hay», no «no supe».
**Descartado:** punteado siempre (promesa de interacción donde no la hay), sin marco (un
hueco sin límite no se distingue de una tabla que no cargó).

## Decisiones (7–12)

### F7. Tres pisos de elevación con nombre

Reposo (tarjetas, paneles), flotante (menús, popovers, avisos) y techo (modales). La
sombra dice qué *es* la pieza, con regla escrita; los cinco tokens `--ds-shadow-*` quedan
debajo como materia prima de tres alias semánticos. Encaja con D10 de la spec hermana
(sólido en reposo, vidrio en flotante/techo). **Descartado:** escala libre (la deriva por
módulo que ya costó la unificación de los velos), dos pisos (modal y menú con la misma
sombra pierden la jerarquía de bloqueo).

### F8. Color al señalar, altura al agarrar

Lo señalado responde con fondo y borde un paso más presentes — también en táctil, al
presionar. La elevación queda reservada a lo que de verdad está en el aire: la pieza
mientras se arrastra. La altura nunca miente. **Descartado:** hover que levanta (invisible
en tablet y usurpa el piso flotante), regla sin excepción de arrastre (el primer drag se
improvisaría).

### F9. El botón: plano que se hunde

Reposo pegado al papel; la presión lo baja 1 px con sombra interior. El clic se ve como se
siente — contacto, no vuelo — y el feedback existe completo en tablet. **Descartado:**
despegado en reposo (flota sin ser flotante), plano total (la presión queda en un solo
canal, el tono, que es el que más se lava al sol).

### F10. El campo: pozo

Fondo un paso hundido más el borde de control medido — dos canales que dicen «aquí se
escribe». Completa la física: lo que se presiona se hunde al tocarlo, lo que recibe viene
hundido. **Descartado:** plano con borde (un solo canal en el caso de uso que lo degrada),
relleno sin borde estilo Material (rompe el contraste de componente y desentona).

### F11. Fila fina en táctil, con panel de edición

**Decisión de Felipe contra la recomendación del asistente** (que proponía densidad
adaptativa 24/44 con los tokens existentes). En táctil la fila baja a ~28 px y editar abre
un panel con controles a 44+. Consecuencias asumidas: su regla del «44 sin excepción bajo
1180» (PRODUCT.md, 2026-08-14) gana una excepción formal — la fila como objetivo de
apertura —, y **nace una superficie nueva en el alcance del frente: el panel de edición
táctil**. A cambio la tablet conserva ~28 filas por pantalla — la densidad que motivó la
fila de 24 sobrevive al tacto. **Descartado:** adaptativa 24/44 (la tablet ve la mitad del
programa), conmutador manual (cede la regla a un clic).

### F12. El panel de edición: hoja según orientación

Sube desde abajo en tablet vertical; entra por el costado en apaisado. El pulgar la
alcanza, la fila tocada y sus vecinas siguen visibles — el contexto denso por el que F11
existe. Un solo contenido, dos anclajes. **Descartado:** modal centrado (tapa justo lo que
F11 quiso conservar), hoja inferior fija (en apaisado atraviesa la pantalla tapando media
hoja).

## Decisiones (13–18) — densidad de tablas (a pedido de Felipe) y controles

### F13. El texto de celda se poda a tres líneas

La fila peor pasa de 173 px (medida en Da Porto) a ~48. Tres renglones distinguen
actividades hermanas con prefijo largo de capítulo; el texto completo vive en tooltip,
panel de edición y exportación — el patrón que el sistema ya usa para los nombres de
estado. No toca la regla «la celda de estado no recorta» (esa habla del chip).
**Descartado:** crecer sin límite (6 filas visibles donde caben 30, medido), una línea
con elipsis (hermanas indistinguibles — densidad visual comprada con lentitud real).

### F14. Cabecera pegajosa en toda tabla

La fila de títulos acompaña el scroll siempre: a tres mil filas de profundidad las
columnas siguen teniendo nombre. Fondo sólido (F7/D10) y sombra sutil solo al despegar.
Refuerza F3: la cabecera dueña de la esquina, con más razón si nunca se va.
**Descartado:** suelta en listados (columnas de memoria + una regla partida en dos casos).

### F15. La zebra se retira

El fondo de fila es el canal de identidad de estado («un canal, un eje» — regla escrita
del contrato) y el gris de la zebra choca de frente con el matiz «silencio» `#e4e4e7`. La
separación la hacen los renglones (F5), la rejilla y la cabecera (F14). **Descartado:**
convivencia (dos grises casi idénticos con significados distintos en la misma tabla).

### F16. La selección intensifica el propio tinte

Cada fila se selecciona hacia sí misma — un paso más presente de su propio fondo — con
borde de control arriba y abajo. Una fórmula (mezcla del fondo hacia el texto) para las
ocho familias y el blanco; la identidad nunca se contamina. **Descartado:** mezclar con el
verde de marca (barro sobre tinte — estado y selección en el mismo canal), solo contorno
(2 px se pierden en filas de 24; la selección múltiple raya la tabla).

### F17. El switch entra, por función

Lo instantáneo desliza (tema, filtros de vista); lo que espera al guardar marca
(formularios). La forma anuncia el comportamiento — la promesa que el conmutador de tema
(spec hermana, D13) necesita. El switch es píldora: es control, no dato, y esa es su
silueta universal. **Descartado:** solo checkbox (cada casilla cargaría la ambigüedad de
si actúa ya o al guardar).

### F18. Chips: píldora se toca, recto se lee

Los filtros clicables de toolbar conservan la píldora; los estados en celda van rectos
(F2). Cuarta aplicación de «la silueta dice qué se puede hacer», y gratis: los filtros no
viven en la tabla, así que F2 ya los dejaba quietos. **Descartado:** todo recto (borra la
única pista no textual de clicabilidad y cuesta goldens de todas las toolbars).

## La regla que gobierna el sistema de forma (emergente de F5, F6, F17, F18)

**La forma anuncia la función.** Rejilla = se edita; renglones = se lee. Punteado = recibe;
sólido = informa. Deslizar = pasa ya; marcar = pasa al guardar. Píldora = se toca;
recto = se lee. Hundido = recibe o está presionado; plano = reposa; flotante = está en el
aire. Toda decisión futura de forma se contrasta primero contra esta regla.

## Decisiones (19–24) — flotantes, foco y especiales

### F19. Pico donde hace falta

El tooltip informativo apunta a su fila con la flecha — el ancla que la poda de F13 vuelve
necesaria entre filas de 24 px. El menú va limpio: su botón queda activo mientras está
abierto, así que el ancla ya existe. **Descartado:** nada con pico (el globo del texto
completo flotando sin decir de cuál fila habla), todo con pico (señal duplicada +
geometría difícil en los bordes).

### F20. Anillo de foco doble: halo + marca

El halo interior del color del fondo del tema separa el anillo de marca de cualquier
superficie: visible sobre los ocho tintes y los dos temas por construcción, sin matriz que
vigilar. **Descartado:** simple (desaparece sobre los fondos de su propia familia — el
usuario de teclado pierde el cursor en algunas filas).

### F21. Celda activa: marco corporativo verde

Marco de 2 px verde AIA con esquinita de arrastre — la convención de Excel en el color de
la casa, 6,2:1 medido sobre su peor fondo. Retira el azul `--ds-color-accent-legacy-dark`,
cerrando la deuda que sus propios tokens documentan («su retiro pide aprobación aparte» —
esta es esa aprobación). Razón nueva y decisiva: la spec hermana resignificó el azul como
familia «En marcha»; conservarlo aquí crearía celda activa azul sobre fila azul de estado.
**Descartado:** azul statu quo (colisión fabricada), doble anillo en celda (4 px del
interior de una celda de 24 + parpadeo al navegar; el doble anillo es para foco que salta,
la celda activa es habitante permanente).

### F22. La grilla del PDC obedece el contrato

Los tokens visten el motor comercial por sus variables de theming: cabecera, rejilla,
altura de fila, selección verde, tintes y celda activa idénticos a las hojas. Se mapea una
vez; la SPA deja de ser isla estética, y los goldens del CI doble capturan el contrato, no
la estética de fábrica. **Descartado:** excepción tolerada (producto de dos fabricantes,
con la selección azul recién retirada, y un precedente que cada módulo futuro citará).

### F23. Barras de gráfica: tope suavizado 2 px

Sin filo y sin mover la lectura; la base queda recta sobre el eje. Misma familia formal
que los chips de dato. **Descartado:** cápsulas (la barra de valor chico pierde media
tinta — el dato pequeño, que suele ser la mala noticia, se ve más pequeño), rectas puras
(única pieza sin el acabado común por una precisión que a 2 px no se percibe).

### F24. Admin: color sí, forma no

El análogo exacto de la D24 de temas: los colores llegan por la capa de tokens; las
esquinas del framework AdminLTE no se tocan. Forzar radios con `!important` sobre CSS de
terceros es media migración — la mitad frágil — para un panel interno que la obra no ve.
**Descartado:** extender los overrides de radio al admin (juego de golpear topos en cada
actualización del framework).

## Decisiones (25–30) — barra, modal, avisos y gobierno

### F25. Pegado lo excluyente, suelto lo independiente

El grupo segmentado anuncia «elige uno» (Semana|Mes|Trimestre); las acciones
independientes conservan su aire. Quinta aplicación de la regla madre; ahorra dos costuras
de ancho por grupo en la barra disputada. **Descartado:** todo suelto (la exclusividad se
descubre probando).

### F26. El pie del modal se adapta al dedo

Derecha en escritorio; apilado a lo ancho en táctil — 44 px por botón, primario arriba.
Mismo patrón que la hoja de edición: la pieza cambia de anclaje, no de contenido.
**Descartado:** fijo a la derecha (dos botones chicos compartiendo esquina — se cancela
queriendo guardar, en la acción más consecuente).

### F27. Avisos: abajo-izquierda en escritorio, arriba-centro en táctil

La esquina muerta de cada contexto — no tapan la barra tras guardar ni la hoja de edición
táctil. El aviso adopta el vocabulario de gravedad del sistema (octágono y tinte por
nivel), no el estilo de la librería. **Descartado:** arriba-derecha (tapa los controles en
el momento de mayor uso), abajo-centro (choca con la hoja de F12).

### F28. Íconos: la familia rellena se queda

La masa sólida sobrevive a los 12–14 px de las celdas y al sol; el contorno fino adelgaza
a hilos en el tamaño dominante. Migrar de familia son cientos de sitios y goldens por una
elegancia que solo existe en tamaños que el producto casi no usa. **Descartado:** contorno
(peor en el tamaño dominante, migración cara), mezcla de familias (el desorden de los dos
mundos).

### F29. Scroll teñido a tamaño nativo

Una línea de CSS estándar tiñe la barra con los tokens del tema en ambos modos; el ancho
de agarre del sistema queda intacto — el pulgar del scroll es el instrumento de posición
en 50 mil filas. Elimina el fogonazo claro en penumbra. **Descartado:** nativa pura (el
fogonazo), fina superpuesta de 8 px (puntería de cirujano en la superficie de más scroll).

### F30. Gobierno: el catálogo de componentes crece, con guard

Radio, borde y piso entran como campos por familia en el inventario de componentes
existente; un guard nuevo compara el CSS real contra lo declarado. Un solo inventario — el
componente y su forma no pueden divergir. **Descartado:** contrato paralelo (dos listas de
las mismas familias — el patrón «fácil de confundir» ya documentado en los dos baselines),
prosa sin guard (29 sugerencias).

## Decisiones (31–36) — tipografía y relleno de tabla (ampliación a pedido de Felipe)

### F31. El dato principal baja a 12 px

**Decisión de Felipe contra la recomendación del asistente** (que proponía ratificar los
13 medidos). Gana ~1 fila por pantalla; la escala de tabla se comprime y la jerarquía deja
de poder venir del tamaño — F33 la restaura bajando la cabecera. `--ds-table-cell-font-size`
pasa de 0.8125rem a 0.75rem.

### F32. Cifras tabulares alineadas a la derecha

`font-variant-numeric: tabular-nums` + alineación derecha en toda columna numérica:
unidades bajo unidades, las magnitudes se comparan bajando la columna. Una línea de CSS.
**Descartado:** proporcionales a la izquierda (el defecto de fábrica: comparar exige leer).

### F33. La cabecera baja a 11 px, peso medio, color secundario

Restaura el peldaño que F31 quitó, en la dirección correcta: el dato manda, el rótulo
acompaña. El piso de 11 se declaró «solo secundarios» y la cabecera es el secundario por
excelencia; su fondo y su posición pegajosa (F14) la separan por dos canales más. La
escala queda: capítulo 13 negrilla · dato 12 · cabecera/secundarios 11.
**Descartado:** mayúsculas espaciadas (~15 % más ancho en columnas peleadas por 3 px),
gemelos separados solo por gris (el canal que el sol lava).

### F34. Dos densidades por función: hoja 24, listado 32

La densidad extrema paga donde hay 50 mil filas y no compra nada donde hay cien: el
listado respira y da margen a sus acciones por fila. Dos densidades **nombradas** — densa
y cómoda — sin tercera; misma partición por función que la rejilla (F5).
**Descartado:** 24 uniforme (optimiza la superficie equivocada).

### F35. Perímetro con aire: 16 px contra el marco, 10 interno

El texto de la primera y última columna deja de rozar la curva del marco y se alinea con
el relleno de las tarjetas. Cuesta 12 px de ancho por tabla, una vez.
**Descartado:** 10 uniforme (el roce contra la esquina curva — acabado a medio terminar).

### F36. Capítulo: peso, tamaño y filete — sin fondo

13 px negrilla con raya inferior; ratifica la receta vigente (Task 36) ajustada a la
escala nueva. El único texto de tabla que gana tamaño, justificado por estructurar
decenas de hijas; el canal de fondo sigue entero para los estados.
**Descartado:** fondo gris estructural (el tercer gris — la colisión que retiró a la
zebra), solo sangría (12 px cobrados en cada fila hija).

## Decisiones (37–40) — rótulos, táctil, anchos y el ritual

### F37. Rótulos: dos líneas y abreviatura declarada

Tope de dos líneas en cabecera; donde ni así quepa, la columna declara su nombre corto —
abreviado por humano, distinguible entre hermanas («Inicio progr.» / «Inicio real») — con
el completo en el tooltip. Extiende a rótulos el patrón `displayShort` de los estados.
**Descartado:** envolver sin tope (una columna terca cobra tres renglones a la cabecera
pegajosa en todas las pantallas), elipsis automática («Inicio p…» / «Inicio r…» gemelas).

### F38. La letra táctil queda en 12, uniforme

**Decisión de Felipe contra la recomendación del asistente** (que proponía 13 como
compensación por el medio brazo de distancia y el sol). La densidad manda también aquí, en
línea con F11 y F31: un solo tamaño de dato en todas las pantallas.

### F39. Anchos de columna fijos por el sistema

**Decisión de Felipe contra la recomendación del asistente** (que proponía ajustables y
recordados por aparato). Los presupuestos de ancho medidos (`labelBudgetPx`) quedan
garantizados y verificables por guard — coherente con la cultura de contratos del repo.
Los dieciséis proyectos comparten los anchos declarados.

### F40. Preset proyector para la reunión semanal

Un toque en la barra: fila 36 y letra 15 solo en la tabla, sin gastar pared en marcos. Un
valor más (`data-density="proyector"`) sobre la arquitectura de densidades existente; se
apaga al cerrar. La reunión semanal es el momento de máxima audiencia del producto y el
único contexto donde la densidad de escritorio juega en contra.
**Descartado:** zoom del navegador (escala también el cromo y depende de un atajo en plena
reunión), nada (el ritual colaborativo leído solo por quien maneja el mouse).

## La escala tipográfica y de densidad de tabla, consolidada (F31–F40 + F11–F16)

| Pieza | Escritorio | Táctil | Proyector |
|---|---|---|---|
| Fila de hoja | 24 px | ~28 px + panel de edición | 36 px |
| Fila de listado | 32 px | 32 px | — |
| Dato principal | 12 px | 12 px | 15 px |
| Cabecera | 11 px, peso medio, secundario | igual | proporcional |
| Capítulo | 13 px negrilla + filete | igual | proporcional |
| Piso de secundarios | 11 px | igual | — |
| Cifras | tabulares, derecha | igual | igual |
| Relleno de celda | 10 px interno · 16 perímetro · 2 vertical | igual | igual |
| Envoltura | 3 líneas máx (dato) · 2 (cabecera) | igual | igual |
| Anchos de columna | fijos del sistema | fijos | fijos |

## Consecuencias transversales que el plan debe absorber

1. **Tokens nuevos o cambiados:** radio de dato (3–4 px) para chips/celdas/banderas ·
   sombra interior de presión · fondo de pozo para campos · tres alias de elevación ·
   anillo de foco doble · scroll teñido · sombras de tinta claras (compartido con la spec
   hermana).
2. **Retiros aprobados:** el azul `--ds-color-accent-legacy-dark` como marco de celda
   activa (F21 es la aprobación que su comentario pedía) · la zebra `--ds-table-zebra` ·
   el hover/selección hacia el verde de marca (`--ds-table-row-hover/selected` se
   redefinen hacia el propio tinte).
3. **Superficies nuevas:** el panel de edición táctil (F11–F12, hoja según orientación) —
   heredado como alcance por la spec hermana (D21, móvil junto).
4. **Excepción formal a PRODUCT.md:** la fila táctil de ~28 px como objetivo de apertura
   (F11) — anotarla donde vive la regla del 44.
5. **AG Grid:** mapeo único tokens→variables de theming del motor (F22).
6. **El guard de forma** (F30) entra al CI con los demás gates estáticos.
7. **Regla madre documentada** en la guía de consumo: «la forma anuncia la función», con
   sus siete aplicaciones (rejilla, punteado, switch, chips, grupos, elevación, pico).
8. **Diseño fino para el plan (no decisiones de producto):** convivencia en la hoja del
   marco verde de celda activa (F21) con la fila intensificada de selección (F16) —
   definir si la fila de la celda activa se intensifica también · geometría exacta de la
   esquinita de arrastre en ambos temas · el radio 3 vs 4 px del vocabulario de dato se
   fija midiendo en pantalla real.

## Resumen ejecutivo

- **Personalidad:** el marco abraza (16–24 px), el dato es recto (3–4 px); frontera
  mínima — solo el vocabulario de la tabla se endurece.
- **Bordes:** 1 px siempre; jerarquía por color; rejilla si se edita, renglones si se lee;
  punteado solo donde invita.
- **Relieves:** tres pisos (reposo/flotante/techo); color al señalar, altura al agarrar;
  botón que se hunde; campo pozo; foco doble.
- **Densidad:** poda a 3 líneas; cabecera pegajosa; sin zebra; selección hacia el propio
  tinte; fila táctil fina con hoja de edición según orientación.
- **Piezas:** switch por función; píldora se toca, recto se lee; grupos segmentados por
  exclusividad; modal adaptativo; avisos en la esquina muerta; pico donde hace falta.
- **Especiales:** celda activa verde corporativo; PDC vestido con el contrato; barras a
  2 px; admin color-sí-forma-no; íconos rellenos; scroll teñido.
- **Gobierno:** todo en el catálogo de componentes, con guard en CI.
