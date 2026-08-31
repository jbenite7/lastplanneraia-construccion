---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-28
areas: [design-system]
fuente: encuesta de 24 preguntas con Felipe, sesión 2026-08-28
resumen: "Ambos temas end to end: claro con matices propios armónicos, y las decisiones que arrastra sobre el oscuro. 24 decisiones tomadas; pendiente del visto final."
---

# Temas claro y oscuro end to end — spec en construcción

**Versión 0.4** — 24 decisiones respondidas (23 de la encuesta + 1 destapada por la
auditoría de cobertura); encuesta completa. Pendiente de la revisión final de Felipe sobre
el documento entero: hasta ese visto, nada de aquí autoriza código. El paso siguiente tras
aprobarse es `writing-plans`.

## El problema

El tema claro que existe hoy (`theme-claro.css`, Task 8 del piloto de la Torre) re-vincula
la cáscara — fondos, textos, bordes, acción — pero **el vocabulario de estado quedó
invariante en sus valores de penumbra**. La confesión está en `ct-app/src/lib/tokens.css`:
el sistema «nunca definió un texto invariante equivalente — hasta ahora corría en un solo
tema y el texto oscuro coincidía por causalidad». Con el toggle claro activo, el texto sobre
filas teñidas cae a 1.2:1–1.9:1. La Torre lo tapó clavando texto blanco fijo (fix acotado,
DS-030); esta spec lo resuelve de raíz: **claro tiene sus propios matices, armónicos con su
estilo** (directriz de Felipe que abre este trabajo).

Ventaja estructural: el manual de marca v1.0 ya trae rampa clara (`claro` / `muy_claro` por
línea de negocio). El oscuro tuvo que inventar sus ocho tintes porque el manual no tiene
peldaños oscuros; el claro parte de valores aprobados.

## Números que gobiernan (medidos en esta sesión)

| Medición | Valor |
|---|---|
| Margen de luminancia entre el tinte más oscuro y el fondo, penumbra | 0,0083 |
| El mismo margen en claro (tinte más oscuro que aún da 4.5:1 al texto) | 0,7768 — **93×** |
| Manual `muy_claro` tal cual: peor par | violeta/azul ΔE 0,0194 — bajo el umbral de percepción (~0,02); 12/28 pares limítrofes |
| Punto calibrado t=0,5 (`muy_claro`→`claro`): pares limítrofes | 0/28; texto 11,2:1 |
| Distribución real PG (50.976 actividades) | urgente+atención 10,6 % · Fuera de Ventana 50,6 % |
| Chip sólido oscuro (`#e15a52` etc.) contra fila clara calibrada | 1,30–2,34:1 — todos bajo el 3:1 de WCAG 1.4.11 |
| Azul huérfano `#4a81bd` con texto blanco | 4,06:1 — falla AA; el peldaño `#2a5a8f` da 7,10:1 |

## Decisiones (1–6)

### D1. Carácter del modo claro: «papel de obra»

Fondo blanco puro, tintes de fila pálidos derivados del manual, texto casi negro
(`#1a1a1a`), chips en tono principal de cada familia. Optimiza el caso difícil — tablet a
pleno sol en obra — no el confort de monitor.
**Descartado:** «escritorio sereno» (argumento de monitor, y exige una segunda paleta de
textos oscuros por chip), «pergamino» (velo cálido que degrada contrastes medidos contra
blanco), «plano técnico» sin relleno (rotulado de color a 11px converge entre teal/verde/azul),
«alto contraste AAA» (tres canales gastados en el mismo dato en filas de 24px).

### D2. La gravedad se marca con bandera, no con filete

Pestaña sólida de ~26 px al inicio de la fila, de altura completa, con glifo dibujado
dentro. Sustituye al filete de 6/4/3 px. Motivo: el filete es una marca en un margen por el
que el ojo no pasa — el reclamo de Felipe («necesitan mucha concentración para que el
usuario los vea») aplicado a su causa, no a su síntoma. La bandera además carga el glifo,
que libera al color de ser el único canal (daltonismo, sol).
**Descartado:** engrosar el filete (más grande donde nadie mira), banda horizontal
(confundible con separador), fila entera saturada (re-mezcla identidad y gravedad en un
canal — el fallo que el contrato ya pagó y documentó).

### D3. La bandera rige en AMBOS temas

Un solo gesto de gravedad: un componente, un contrato, un juego de evidencia. El filete
tiene en penumbra el mismo defecto que en claro.
**Costo aceptado por Felipe:** regenerar los goldens visuales del modo oscuro (en
producción) y retirar los seis tokens `--ds-severity-rail-*` (la bandera trae los suyos).
**Descartado:** claro con bandera y oscuro con filete (dos mecánicas para un significado:
cada estado nuevo se decide dos veces); bandera + filete juntos (dos canales para el mismo dato).

### D4. La bandera se pinta por NIVEL, no por matiz

Rojo=urgente, ámbar=atención — aunque el matiz de la fila sea azul, violeta o teal.
Ratifica la regla vigente de `state-semantics.json`: «Urgencia now siempre usa critical en
el acento aunque el nombre del estado —o su matiz— cambie». La columna de urgentes se lee
de un golpe; fila (identidad) y bandera (gravedad) quedan en canales separados de verdad.
**Descartado:** por matiz (armónico fila a fila, pero deroga una regla vigente, repite
información y deja la gravedad colgando de un glifo de 13 px).

### D5. Solo urgente y atención llevan bandera

La ausencia de bandera es la señal de calma — principio ya medido del sistema (el filete
universal habría marcado media tabla). Una de cada diez filas queda marcada (10,6 % real).
**Deroga a sabiendas** el `rail: ready` del 2026-08-20: con filete, el marcador positivo
era subordinado por construcción (3 px contra 6); la bandera no tiene gradación y lo
ascendería a grito sin que nadie lo decidiera. Lo positivo vive en chip y tinte.
Si «listo para comprometer» necesita más presencia, la respuesta es un filtro o un
contador, no el canal de interrupción.
**Descartado:** sumar listo (pleno o hueco), todas las filas.

### D6. El azul: rescatar la rampa huérfana `--aia-blue-*`

La rampa de 5 peldaños OKLCH (`#2a5a8f` → `#e6f0fa`) existe en `tokens.css` con 30
consumidores vivos, rotulada «Arquitectura» — rótulo caducado desde que el manual movió
Arquitectura al violeta `#6752bf`. Se rebautiza como familia **«En marcha»** y convierte en
familia con nombre la desviación que `brandAudit` ya registraba («azul sin familia AIA»).
**Dos excepciones a documentar:** su tinte claro se empuja a t=0,7 (los demás van a t=0,5)
para despegar del violeta (ΔE 0,0364); su chip usa el peldaño oscuro `#2a5a8f` (7,10:1)
porque el principal `#4a81bd` falla AA con blanco (4,06:1).
**Descartado:** azul nuevo (novena familia sin dueño ni lugar en el manual), retirar el
azul (los siete destinos están ocupados; reasignar rompe «un matiz, un estado» o renombra
un estado ya aprendido en obra).

## La paleta clara de matices (D1+D5+D6, valores candidatos)

Interpolación t=0,5 entre `muy_claro` y `claro` del manual — primer punto medido donde los
28 pares se distinguen (ΔE ≥ 0,035) con texto en 11,2:1:

| Matiz | Tinte de fila claro | Origen |
|---|---|---|
| verde | `#c2e2d3` | Corporativo |
| naranja | `#f8c9a5` | Construcción |
| teal | `#c8efec` | Inmobiliario |
| violeta | `#dad4f5` | Arquitectura |
| rojo | `#f6c3c3` | Alertas |
| ámbar | `#ffecb2` | Advertencias |
| gris | `#e4e4e7` | neutro zinc (aia.com.co) |
| azul | `#c1d5ec` | rampa rescatada, t=0,7 |

**Consecuencia de gobierno:** estos valores no son peldaños del manual (tiene 4 y ninguno
cae ahí). El sistema de marca gana una **rampa clara derivada**, que se documenta como
extensión del manual, con este razonamiento y estas mediciones como procedencia.

## Decisiones (7–12)

### D7. Los chips de estado cambian con el tema

En claro, chip sólido en el tono **principal** de cada familia del manual con texto blanco;
en penumbra, los actuales. Medido: los chips invariantes de hoy fallan **todos** el 3:1 de
WCAG 1.4.11 contra las filas claras calibradas (1,30–2,34:1 — el ámbar es casi invisible).
La invariancia nunca fue principio: era la coincidencia «por causalidad» que confesaba
`ct-app`. Tres familias necesitan un peldaño más oscuro que su principal para aguantar el
texto blanco: ámbar (`#a16207`-rango), teal (`#007a71`) y azul (`#2a5a8f`), documentados
como excepción igual que D6. Costo: ocho pares nuevos de tokens chip-claro/chip-oscuro.
**Descartado:** invariantes (falla WCAG conocida de antemano).

### D8. Glifos de la bandera: octágono de pare y reloj

**Urgente = octágono de alerta (pare) · atención = reloj.** Mezcla elegida por Felipe sobre
las familias propuestas: siluetas de convención vial/obra que no comparten metáfora y son
inconfundibles a 13 px. El glifo se dibuja con trazos (nunca fuente de emoji — trampa
medida en este repo). **Descartado:** triángulo+reloj (propuesta original), pares de
exclamaciones (misma idea dos veces), flechas de tendencia (lenguaje de tablero, no de obra).

### D9. Nav y sidebar cambian con el tema

**Revierte la decisión de la entrada 23 del piloto** (nav anclada a oscuro, patrón
Linear/Stripe), a conciencia y por decisión de Felipe. En claro, nav y sidebar claros: la
app entera cambia de cara. Consecuencias aceptadas: diseñar completa la cara clara de la
pieza más compartida (estados, foco, hover, tratamiento del logo — el filtro
`invert(1) brightness(1.15)` del mark deja de aplicar), los tokens `--ds-active-nav-*` de
`theme-claro.css` dejan de apuntar a `-dark`, y la nav gana goldens dobles.

### D10. Vidrio solo flotante en claro; sombras de tinta

Cards, paneles y toolbars **sólidos** en claro (borde fino + sombra de tinta suave); el
desenfoque queda para lo que flota — modales, popovers, nav con contenido corriendo debajo —
donde informa («hay algo debajo») y se ve. El oscuro no cambia. Las sombras claras son una
escala nueva de seis tokens (negro a baja opacidad): las actuales están calibradas contra
penumbra (`rgba(20,28,24,0.4–0.8)`) y sobre papel manchan.
**Descartado:** vidrio en todo (imperceptible sobre blanco, cobra GPU en tablet, re-verifica
contraste contra cada fondo), papel plano absoluto (mata el desenfoque también donde trabaja).

### D11. Lienzo neutro: sin velo verde en claro

El fondo del shell claro es blanco→gris humo sin el radial verde corporativo
(`rgba(167,213,193,0.35)`) que hoy vive en `--ds-shell-background` único para ambos temas.
Razón dura: todas las mediciones de esta spec se hicieron contra blanco/#fafafa; un velo al
35 % hace que el contraste dependa de la zona de pantalla, y diluye el tinte verde de
«controlado» contra un fondo del mismo color. El token se divide por tema; en penumbra el
velo se queda. **Descartado:** velo pleno, velo al 12 % (costo de medición completo por un
efecto imperceptible).

### D12. Claro de entrada: el claro es la cara del producto

**Decisión de Felipe contra la recomendación del asistente** (que proponía oscuro por
defecto con elección persistida, para que nadie notara nada el día del despliegue). El tema
claro pasa a ser el default del producto; el oscuro queda como opción, y la elección manual
de cada usuario se guarda y gana. Consecuencias que esta spec debe absorber:
- **Invierte el contrato vigente** («dark es el tema por defecto y el que se valida» —
  AGENTS.md y README del design system): la validación canónica pasa a incluir el claro
  como primera cara. Alimenta las preguntas pendientes de CI/goldens y viewport.
- **Toda la base instalada amanece en claro** el día del despliegue. El cómo quedó
  resuelto en D13–D15: sin aviso previo (D15), con el botón «Volver a oscuro» visible el
  primer mes (D13) y la preferencia por aparato como red (D14).
- `theme-bootstrap.js` invierte su default (hoy aplica dark salvo preferencia persistida).

## La bandera de gravedad (D2+D3+D4+D5+D8, consolidado)

Pestaña sólida de ~26 px, altura completa de fila, al inicio. Color por nivel: rojo urgente,
ámbar atención. Glifo dibujado dentro: octágono de pare (urgente), reloj (atención). Solo
esos dos niveles la llevan; la ausencia es la señal de calma. Rige en ambos temas y
sustituye al filete (`--ds-severity-rail-*` queda derogado; los goldens del oscuro se
regeneran).

## Decisiones (13–18)

### D13. El conmutador: visible el primer mes, luego al menú del usuario

Botón «Volver a oscuro» en la barra durante la transición del estreno — un toque, sin
formación. Pasado el periodo (un mes nominal) se recoge al menú del usuario, donde viven
las preferencias. **Descartado:** permanente en barra (costo eterno de espacio por una
acción de una vez), solo-menú desde el día uno (la base tendría que adivinar la salida el
día del estreno).

### D14. La preferencia es por aparato

Cada pantalla recuerda su tema (almacenamiento local, completando el patrón que
`theme-bootstrap.js` ya tiene). El caso real que la decide: tablet clara al sol y
computador oscuro en el campamento conviven para el mismo usuario. Cero backend.
**Descartado:** por cuenta (columna+endpoint+sincronización para un comportamiento peor),
híbrido (complejidad doble sin demanda).

### D15. Estreno de golpe, sin aviso

**Decisión de Felipe contra la recomendación del asistente** (que proponía un banner de
una semana anunciando la fecha). El despliegue cambia la cara el mismo día para toda la
base; la red es el botón visible de D13 y la preferencia persistida de D14. Corte limpio
asumido a conciencia.

### D16. CI: todo doble

**Decisión de Felipe contra la recomendación del asistente** (que proponía claro completo
+ oscuro dirigido a estado/banderas/nav). Cada golden visual corre en ambos temas: máxima
red asumiendo el doble de costo permanente. **Nota para el plan:** el carril visual ya roza
su tope de tiempo — necesitará partirse en jobs por tema o paralelizarse para no reventar
el timeout.

### D17. El Excel se unifica con la paleta clara

El exportador (PhpSpreadsheet, `ReportController`) pinta los estados con los tintes
calibrados de pantalla: lo que se ve es lo que se imprime. Cierra la decisión pendiente
del 2026-08-03 — su premisa (no existía tema claro de pantalla) caducó con D12. Retira los
ocho tokens huérfanos `--ds-color-state-*-light` («reserva sin cablear»). Los Excel
exportados tras el cambio difieren de los archivados durante un par de ciclos semanales —
aceptado por efímeros. **Descartado:** conservar dos paletas, bendecir la del exportador.

### D18. La penumbra se conserva tal cual

Los ocho tintes oscuros no se recalibran: su margen físico (0,0083) ya está explotado al
límite — el método del claro fue «explota el margen disponible», y en penumbra no lo hay.
Solo los toca lo ya decidido: la bandera sustituye al filete y sus goldens se regeneran.
**Descartado:** recalibrar por simetría (cambio en producción sin ganancia), derivar del
manual (rehace el trabajo medido para cambiarle la firma).

## Decisiones (19–24)

### D19. «Listo para el golpe» = todo en verde

El estreno ocurre cuando toda la cobertura del frente — los 14 módulos pilot, admin en su
capa de tokens (D24) y las dos SPA — pase el CI doble en verde.
Fecha por gate, no por calendario; el módulo más lento decide cuándo. Coherente con
D15+D16: sin aviso previo, la primera impresión es la única, y el semáforo objetivo ya
existe. **Descartado:** estrenar con el corazón listo y la periferia a medias (costo de
confianza con el estreno sin aviso).

### D20. La rampa derivada entra al manual de marca

Quinto tono oficial de cada familia — «tinte de datos» — con su procedencia medida.
Toda pieza AIA futura (tableros, presentaciones, otros productos) hereda los tintes
calibrados en vez de re-derivarlos: evita repetir la orfandad del azul. **Línea roja:**
el manual vive en la configuración global de Felipe; esta decisión autoriza anotarlo en
el plan, y la edición real pedirá su visto de nuevo en la sesión que la ejecute.
**Descartado:** dejarla solo en el repo (cada consumidor externo re-deriva o copia a mano).

### D21. Escritorio y móvil, juntos

**Decisión de Felipe contra la recomendación del asistente** (que proponía color ahora y
móvil en su programa propio de siete fases). El frente absorbe el programa móvil: cada
módulo sale en claro, oscuro **y móvil**. Consecuencia asumida: el umbral de D19 incluye
móvil y el estreno se aleja en proporción; a cambio llega completo — color y tamaño en un
solo golpe, y el caso fundacional (tablet al sol en obra) queda cubierto de verdad.

### D22. Secuencia por módulo, tras fase cero común

Fase cero transversal primero — rampa de tokens, componente bandera, nav clara, sombras de
tinta, conmutador — porque todos los módulos la consumen. Después, cada módulo se abre una
vez y se cierra completo (claro + oscuro + móvil + goldens dobles + manifiesto).
**Descartado:** por capas (cada puerta se abre dos veces y el móvil desacomoda el color ya
aprobado — la evidencia se regenera dos veces por módulo).

### D23. El primero es Programa General

El piloto contractual del sistema, con el andamiaje de evidencia más maduro: los tropiezos
del método se detectan con instrumentos, no con usuarios. **Programación Intermedia va
segunda** como prueba de fuego (ocho estados, los ocho matices, donde nivel y matiz más
chocan), con el método ya destilado. La Torre va al final: ya está en claro, y su tema
piloto se reescribe sobre los tokens definitivos como cierre de deuda. **Descartado:**
empezar por PI (examen final el primer día), por PS (máxima visibilidad en el intento con
más probabilidad de tropiezo), por la Torre (consolida, no abre método).

### D24. Admin: tokens claros, sin migrar

Destapada por la auditoría de cobertura de Felipe («¿ya tenemos el 100 % de los
módulos?»): admin/ no estaba en la spec y carga una decisión previa vinculante (goal
dark-mode, 2026-07-29) — AdminLTE permanece, sus 14 vistas no se migran al design system.
Esa decisión prohíbe *migrar*, no *temar*: admin ya quedó tokenizado en dark, así que la
misma capa de tokens se voltea a claro sin tocar AdminLTE ni vista alguna. Elimina el
salto de cara diario del rol que más cruza entre las dos apps. La cobertura completa del
frente queda: **14 módulos pilot + admin (solo tokens) + 2 SPA**; el laboratorio del
design system entra como infraestructura de fase cero (con CI doble debe servir ambos
temas), y las páginas de error caen con las primitivas.
**Descartado:** dejarlo oscuro (con estreno de golpe se lee como cambio a medio hacer,
todos los días), migrarlo completo (revierte una decisión vinculante y suma 14 vistas a un
frente ya engordado con móvil — si algún día se migra, será su propio frente).

## Consecuencias transversales que el plan debe absorber

1. **Contrato invertido:** AGENTS.md y `docs/design-system/README.md` dicen «dark es el
   tema por defecto y el que se valida». Con D12+D16 el claro es la cara del producto y
   ambos temas son contractuales con CI doble. Se reescriben ambas líneas.
2. **`ct-app`:** el fix del texto invariante (`--ct-row-text-primary` fijo) queda derogado
   por diseño — en claro las filas son claras y el texto oscuro. Su `theme-claro.css` de
   pantalla se reescribe sobre los tokens definitivos (D23, al final).
3. **Filete derogado:** los seis tokens `--ds-severity-rail-*` se retiran con la bandera
   (D2–D5). El contrato `ds-f1a-escala-estado.json` («barra: true/false») se reinterpreta:
   la columna pasa a significar bandera. `state-semantics.json` pierde `rail: 'ready'` (D5).
4. **CI:** el carril visual dobla su carga (D16) y suma móvil (D21) — necesita partirse en
   jobs por tema/viewport o paralelizarse antes de la fase cero, o el timeout de 60 min
   revienta.
5. **Exportador Excel:** `ReportController` abandona su ARGB a mano y lee la rampa
   calibrada (D17); los ocho tokens `--ds-color-state-*-light` huérfanos se retiran.
6. **Estados pastel de penumbra:** los textos `--ds-color-state-*-text` (verde `#b7e8c6`,
   etc.) siguen siendo de penumbra; en claro el texto sobre tinte es el normal `#1a1a1a`
   (11,2:1 medido) — no nace una familia de textos pastel claros.
7. **Diseño fino pendiente para el plan (no son decisiones de producto):** dibujo SVG
   definitivo de los dos glifos (octágono, reloj) como trazos; interacción de la bandera
   con el radio de tabla (esquina inicial); comportamiento de la bandera en filas de 24 px
   contra filas envueltas de varias líneas; el peldaño exacto del ámbar oscuro para chip
   claro (candidato `#a16207`-rango, falta medirlo contra los cuatro fondos reales).

## Resumen ejecutivo de la arquitectura

- **Claro** = papel de obra: blanco, tintes calibrados t=0,5 del manual (azul t=0,7),
  texto `#1a1a1a`, chips en tono principal (ámbar/teal/azul en peldaño oscuro), vidrio
  solo flotante, sombras de tinta, lienzo neutro, nav clara.
- **Oscuro** = intacto salvo la bandera; sigue siendo opción de primera clase con CI doble.
- **Gravedad** = bandera de ~26 px con glifo (octágono/reloj), color por nivel, solo
  urgente y atención, en ambos temas. La ausencia es la señal.
- **Interruptor** = claro de entrada, de golpe, sin aviso; botón «Volver a oscuro» visible
  el primer mes y luego al menú; preferencia por aparato.
- **Alcance** = escritorio y móvil juntos, por módulo, PG primero, tras fase cero común;
  estreno cuando todo esté en verde; Excel unificado; rampa al manual de marca.
