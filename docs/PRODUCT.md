---
capa: fuente
tipo: biblia
estado: vigente
fecha: 2026-08-05
fuente: docs/PRODUCT.md
resumen: Segunda revisión en frío: 2026-08-11, sobre 66facd23 (fase 9 del IMPROVE-APP-PLAN, cierre del Frente 1). Sustituye —sin borrarla— a la primera, del 2026-08-05…
---

# PRODUCT — la Única Cosa, y qué falta para que se note

**Segunda revisión en frío: 2026-08-11, sobre `66facd23`** (fase 9 del `IMPROVE-APP-PLAN`, cierre del
Frente 1). Sustituye —sin borrarla— a la primera, del 2026-08-05 (Task 31, sobre `58ba25ab`), que
midió la cascada **antes** de los 28 arreglos del Frente 1. Lo que aquí se dice del producto es lo que
se ve hoy; lo que se conserva de la revisión anterior va marcado como tal.

**Este archivo no es contrato.** El contrato de consumo visual es `DESIGN.md`; el de producto vive en
`docs/CUSTOMER.md` (a quién servimos) y `docs/POSITIONING.md` (qué le prometemos). Aquí solo se anota
**qué haría el producto más nítido, en qué orden y por qué**.

**Nada de lo que sigue se ha aplicado.** Los cortes se **proponen**. Esta revisión no tocó código.

**Método y su límite.** Recorrido real en navegador a 1180×820 dark contra este worktree servido en
`http://localhost:8091`, sesión `test.A` por la puerta de servicio, proyecto **Da Porto** (con datos
reales), **solo lectura**. Se recorrieron las cuatro pantallas de la cascada y la página 404. Lo que
**no** se hizo: no se importó un cronograma ni se confirmó una semana, así que los pasos que dependen
de eso siguen derivados del código, no cronometrados. **El navegador fue headless (Playwright MCP):**
un hallazgo de esta pasada —las cabeceras que no se pintan— depende de esa condición y va marcado con
la incertidumbre que le corresponde, no afirmado como cierto.

---

## La Única Cosa

> **Cada semana la obra promete solo lo que de verdad puede cumplir, y al terminar la semana se sabe
> si cumplió.**

**Sin cambios respecto al 2026-08-05, y sigue siendo cierta.** Todo lo demás del producto existe para
que esa promesa semanal sea creíble.

La consecuencia práctica de nombrarla: **si una pantalla no acerca al usuario a esa promesa, compite
con ella.** Ése es el criterio con el que están escritos los cortes de abajo.

### La Única Cosa de cada una de las cuatro pantallas

Lo pidió el encargo de la fase 9, y no estaba en la revisión anterior. Para cada pantalla: qué debe
hacer, y si la pantalla lo deja ver.

| Pantalla | Su Única Cosa | ¿La deja ver? |
|---|---|---|
| **`/programa-general`** | *Qué actividades van tarde contra el contrato.* | **No del todo.** El dato de atraso existe, pero la pantalla abre con 7 chips de estado de los que 4 leen `(0)` y con «Atrasada 0» al lado de «Actividad Futura 238»: el número que importa es el más pequeño de la fila. Y la acción primaria que ganó el Frente 1 —«Actualizar Ejecución»— responde a *otra* pregunta: registrar avance, no leer atraso. |
| **`/programa-general-actualizar`** | *Meter el cronograma nuevo sin romper el viejo.* | **Sí, y es la mejor de las cuatro.** Cuatro botones, cabeceras legibles, y el acuse de recibo al importar (arreglo del Frente 1) cierra el agujero que la hacía irrepresentable. Su defecto es el opuesto al de las otras: con la tabla vacía **no dice nada** — ni estado vacío ni invitación a cargar—, y el segundo botón es «Eliminar Actualización», destructivo, antes de que exista nada que eliminar. |
| **`/programacion-intermedia`** | *Qué le falta a una actividad para poder prometerse.* | **No.** Es la pantalla más cargada del producto: **11 controles de barra en dos filas y 9 chips**, de los que 8 leen `(0)`. Veinte controles por encima de una tabla que en Da Porto tiene **una fila**. La restricción que bloquea sí se marca en la celda (`pi-missing-resp`, arreglado en el Frente 1), y eso funciona — pero está enterrada bajo la barra. |
| **`/programacion-semanal`** | *Prometer lo que se puede cumplir, y firmarlo.* | **Sí, y es donde más se nota el Frente 1.** «Confirmar Compromisos» es la acción primaria y nombra su causa («Faltan N por completar»); los chips declaran `aria-pressed`, filtrar se anuncia en `#psFilterAnnounce`, el vacío del filtro se distingue del de la semana, y el chip de guardado tiene `role="status"`. Verificado en vivo, no de memoria. **Lo que la estorba no es suya:** ver el compromiso exige leer una rejilla cuyas cabeceras hoy no se pintan (abajo). |

## Pasos hasta el valor

El «valor» sigue siendo: **la primera vez que el producto le dice al residente algo que él no sabía.**

**El dato más duro de esta revisión: los 28 arreglos no quitaron ni un solo paso.** La tabla del
2026-08-05 sigue valiendo íntegra; lo único que cambió es la columna de fricción.

| # | Paso | Fricción, 2026-08-05 | Fricción, 2026-08-11 |
|---|---|---|---|
| 1 | `/login` | — | — |
| 2 | `/proyectos` → «Ingresar al proyecto» | limpio | **igual** |
| 3 | Aterriza en `/programacion-semanal` | vacía | **igual** |
| 4 | Deducir que primero hay que cargar el cronograma | sin ninguna pista | **igual — sin cambio** |
| 5 | Encontrar `/programa-general-actualizar` en el carril | icono 9 de 10, sin etiqueta visible | **igual: medido hoy, los 11 enlaces del carril tienen `aria-label` y ninguno `title`** |
| 6 | Subir el XLSX | sin acuse de recibo (`M-4`) | **arreglado**: spinner, botón bloqueado, un solo POST |
| 7 | Ir a `/programacion-intermedia` | icono 7 de 10 | **igual** |
| 8 | Liberar restricciones | se explica en la celda | **igual, y confirmado** |
| 9 | Volver a `/programacion-semanal` | icono 8 de 10 | **igual** |
| 10 | «Autoprogramar Actividades» | primera vez que la rejilla no está vacía | **igual** |
| 11 | Comprometer cantidades | — | **mejor**: chip «Guardando… (n)» → «Guardado», anunciado |
| 12 | «Confirmar Compromisos» | irreversible; el bloqueo no decía cuáles | **arreglado**: dice «Faltan N», el servidor devuelve los ids, la rejilla los señala, y el cierre remata con «y N más» |
| 13 | *Esperar a que pase la semana* | — | — |
| 14 | El PAC significa algo | **primer valor** | **igual** |

**Trece pasos y una semana entera antes del primer dato útil — exactamente los mismos que hace seis
días.** Cuatro pantallas, más el selector de proyectos. Lo que el Frente 1 hizo fue **mejorar la
calidad de nueve de esos pasos sin eliminar ninguno.**

## Veredicto binario

> ## **NO ESTÁ HECHO — 6/10.**

Cuatro de las siete filas del diagnóstico pasan. Las tres que fallan:

- **«¿Un usuario nuevo llega al valor en ≤3 pasos?»** → No: trece pasos y una semana, sin cambio.
- **«¿Se quitó algo en este ciclo?»** → **No: cero.** Los 28 arreglos son **28 adiciones** — un chip,
  una etiqueta, un sello, un contador, un anuncio, un remate de lista. Ninguno borró una pantalla, un
  botón ni un paso. Un ciclo entero sin una sola resta.
- **«¿Los estados de error y vacío están a la altura de la pantalla principal?»** → No. La `404`
  sigue devolviendo **52 bytes de HTML sin un solo enlace** (`<html><head></head><body>404 Not
  Found</body></html>`, medido hoy). Era la **única fila bloqueante** del roadmap del 2026-08-05 y
  pasaron 28 arreglos por encima sin tocarla.

### Y la pregunta honesta que pidió el encargo

**¿Esto se siente como un producto o como una lista de parches bien hechos?**

**Como una lista de parches bien hechos.** Y conviene decirlo entero, porque las dos mitades importan:

Los parches **son buenos**. No son cosméticos ni están puestos de cualquier manera. Varios se
arreglaron en el sitio correcto y no en el más cercano —el remate «y N más» va en `renderSummaryList`,
por donde pasan las cuatro listas; `aria-pressed` se calcula donde se calcula el estado visual, para
que no puedan divergir; el chip de guardado se **extrajo** a un módulo compartido en vez de copiarse
cuatro veces—. Tres fichas se cerraron **midiendo que el defecto no existía o estaba en otro archivo**,
que es lo contrario de trabajar por inercia. Esa es artesanía real y hay que reconocerla.

Pero un producto no es la suma de sus defectos reparados. **La forma del recorrido no cambió.** Sigue
teniendo cuatro pantallas con tres vocabularios de chips distintos para un mismo ciclo; sigue
aterrizando al recién llegado en la pantalla que no puede usar todavía; sigue enterrando la Única Cosa
en los puestos 6-8 de un carril de diez iconos mudos; y sigue expulsando por la puerta de atrás a
quien teclea mal una URL. El Frente 1 subió el suelo de calidad de cada pantalla **sin decidir nada
sobre el conjunto**. Es exactamente lo que se le pidió, y por eso no es un reproche a quien lo
ejecutó — es el diagnóstico de lo que falta: **el Frente 1 fue una campaña de reparación; lo que el
producto necesita ahora es una decisión de forma.**

Y la otra mitad, igual de cierta que el 2026-08-05: **para un usuario ya entrenado, esto sirve.** Con
el asterisco nuevo de las cabeceras, abajo.

## Qué sobra — la lista de «no»

La revisión anterior no la tenía. **Ninguna de estas propuestas borra una ruta ni quita un permiso a
nadie:** «sacar del camino principal» no es «eliminar».

**Contado hoy, en vivo, en Da Porto:** entre las cuatro pantallas hay **49 controles por encima del
dato** — 28 botones de barra y 21 chips de estado. **Dieciocho de los 21 chips leían `(0)` a la vez.**

1. **Los chips que leen cero.** PG abre con 4 de 7 en `(0)`, PI con 8 de 9. Un chip en cero ocupa el
   mismo espacio y el mismo peso visual que uno con dato, y entrena al ojo a no mirar la fila. **No:**
   que un chip en cero se repliegue a un contador discreto, o desaparezca de la fila principal.
2. **Tres vocabularios para un solo ciclo.** PG habla de «Actividad Futura / En Curso / Atrasada /
   Terminada», PI de «RC inicio vencido / Alistamiento Urgente / Listo para Comprometer», PS de «RC
   con restricciones / Por Comprometer / Lista para Confirmar». Son 21 términos para describir la
   misma actividad en tres momentos. **No:** tres léxicos. Uno, con el estado cambiando de valor.
3. **Los once controles de la barra de PI.** «Seleccionar visibles», «Limpiar selección», «0 selec.»,
   «Ver Todas las Actividades», «Descargar Corte», «Exportar CSV», «Leyenda», «Listas», «BI
   Intermedia», «Recargar» — más la acción primaria. **No:** dos maneras de exportar y dos de
   seleccionar en la barra de arriba. Al menú «Más», que PS ya tiene y PI no.
4. **El selector de semana, por duplicado.** Medido: las cuatro semanas del proyecto se rinden en
   **dos menús distintos** (`shell-week-flyout` en el carril y `ctxWeekMenu` en la cabecera) — 16
   botones en el DOM para 4 semanas. **No:** dos sitios donde cambiar de semana.
5. **Las cinco superficies que compiten en el carril** (`/control-cambios` —que sigue siendo de solo
   consulta—, `/indicadores`, `/profesionales`, `/subcontratistas`, `/plan-compras`). **Sin cambios
   respecto al 2026-08-05:** una sola entrada «Gestión» que las agrupe.
6. **«Eliminar Actualización» en la barra de una pantalla vacía.** Es el segundo botón de Actualizar
   Cronograma y es destructivo. **No:** ofrecer borrar antes de que exista algo que borrar.

## Los cortes, en orden

Los tres cortes de la revisión del 2026-08-05 **siguen vigentes palabra por palabra**, porque ninguno
se aplicó. Se resumen, no se repiten:

- **Corte 1 · La cascada no tiene primera vez.** El residente aterriza en una rejilla vacía que le
  ofrece las dos acciones que en un proyecto nuevo no pueden funcionar. **Sin cambios.**
- **Corte 2 · El carril es mudo y está en el orden equivocado.** Verificado hoy: los 11 enlaces tienen
  `aria-label` y **ninguno `title`**; la cascada ocupa los puestos 6, 7 y 8. **Sin cambios.**
- **Corte 3 · Cinco superficies compiten con la Única Cosa en el mismo carril.** **Sin cambios.**

## Arreglos (no son cortes: son deuda que se paga)

En orden de daño, **actualizado con lo medido hoy**:

1. **Las cabeceras de columna no se pintan en las tres rejillas Handsontable.** Hallazgo nuevo y el de
   mayor impacto de esta pasada. En `/programa-general`, `/programacion-intermedia` y
   `/programacion-semanal` la banda de cabecera sale **en blanco**, y en PG y PS la primera fila
   aparece además cortada por arriba. **Lo que se midió, y es importante para no confundir el
   diagnóstico:** el `<th>` existe, mide `57 px` de alto, está en la posición correcta y su color
   calculado es `rgb(247,250,248)` sobre `rgba(35,48,41,.86)` — es decir, **el layout es correcto y lo
   que falta es el pintado**. Y no es global: **`/programa-general-actualizar`, que no usa
   Handsontable, pinta sus cabeceras perfectamente** en la misma sesión y el mismo viewport.
   **Incertidumbre declarada:** se midió en navegador headless, que es exactamente la condición que
   produjo el falso positivo `P-1` del PDC. **Antes de gastar una hora en arreglarlo, hay que abrirlo
   en un navegador de verdad.** Si se confirma, no hay debate de prioridad: una rejilla sin cabeceras
   no se puede leer, y las tres afectadas son la cascada entera.
2. **Páginas de error.** La `404` responde 52 bytes sin un solo enlace. **Sigue siendo el peor punto
   del producto y sigue sin tocarse** desde que se registró como la única fila bloqueante (`R-1`).
3. **Actualizar Cronograma no tiene estado vacío.** Cabeceras y un vacío enorme, sin decir qué se
   espera de ti. Es la pantalla del **paso 6**: la primera que un residente nuevo tiene que usar.
4. **La flecha del selector tapa el último dígito de las fechas en PG** (`F-2`). Sin cambios.
5. **Cabeceras partidas en `/control-cambios`** (`F-1`). Sin cambios.
6. **Los gatillos de ayuda de PI**: 8×8 px, sin nombre accesible (`F-3`). Sin cambios.

## La parte de atrás de la valla

| Superficie | Estado 2026-08-05 | Estado 2026-08-11 |
|---|---|---|
| **404 / 403** | el peor punto del producto | **igual, medido: 52 bytes, 0 enlaces** |
| **Sesión caducada** | correcto | **mejor**: las cuatro grillas delegan en `SessionExpiredHandler` |
| **Estados vacíos** | buenos casi en todos | **mejor en PS** (el del filtro ya no se disfraza del de la semana); **peor de lo que se creía en PGA: no tiene ninguno** |
| **Errores de red y de carga** | reescritos | **igual, siguen bien** |
| **Error de guardado de celda** | bien resuelto | **mejor**: chip «Guardando… (n)» en las cuatro rejillas, con `role="status"` en las cuatro |
| **Cierre de semana con bloqueos** | el peor momento informado del flujo | **arreglado, y es el mejor trabajo del Frente 1**: cuenta entera, remate «y N más», ids señalados en la rejilla, sello de fase |
| **Chips de filtro de PS** | sin `aria-pressed`, filtrar no se anunciaba | **arreglado, verificado en vivo**: los 5 declaran `aria-pressed`, y existe `#psFilterAnnounce` |
| **Rol denegado por URL** | cae en el 403 pelado | **igual** |
| **Cabeceras de rejilla** | no medido | **nuevo hallazgo, ver arriba** |

**Nota sobre una ficha que quedó desactualizada:** el backlog registra que el chip «Auto-Guardado» de
Actualizar Cronograma «no se oculta nunca» porque su clase no tiene regla en ese ámbito (ICE 320).
Medido hoy sobre `66facd23`, ese `#save-status` computa **`display: none`**, así que el defecto
parece haber caído de rebote con la extracción de `save-status.js`. **No se tocó la ficha** —la está
editando otra sesión—: se registra como fila nueva para que alguien la re-mida y la cierre si procede.

---

## Outcome Roadmap

**ICE** = Impacto × Confianza ÷ Esfuerzo, en la escala del propio repo (`docs/EXPERIMENTS.md`).
Ninguna fila está aprobada: es una recomendación priorizada.

### Bloqueante — antes de enseñárselo a alguien de fuera

| # | Resultado buscado | Entrada | Por qué ahora | ICE |
|---|---|---|---|---|
| 0 | **Confirmar en navegador real si las cabeceras de las tres rejillas se pintan** | nuevo | Si el hallazgo es real, la cascada entera es ilegible y todo lo demás de esta lista da igual. Comprobarlo cuesta cinco minutos | I 9 · C 5 · E 9 |
| 1 | **Quien se equivoca de URL o choca con un permiso sigue dentro del producto** | `R-1` | **Se propuso como bloqueante el 2026-08-05 y sigue igual seis días y 28 arreglos después.** Dos plantillas dentro del shell existente | I 4 · C 5 · E 1 |

### Ahora — el siguiente trabajo de producto

| # | Resultado buscado | Entrada | Por qué ahora | ICE |
|---|---|---|---|---|
| 2 | **Un residente nuevo llega solo hasta su primer PAC** | `R-2`, `R-4` | **Trece pasos, sin cambio.** Es lo único de esta lista que altera la *forma* del recorrido en vez de su acabado | I 5 · C 4 · E 3 |
| 3 | **El carril dice qué es cada icono y pone la cascada primero** | `R-3` | Verificado hoy: 11 enlaces, 0 `title`, cascada en los puestos 6-8 | I 4 · C 4 · E 2 |
| 4 | **La cascada habla un solo idioma de estado** | nuevo | 21 chips, tres vocabularios, 18 en cero a la vez. Es la decisión de forma que el Frente 1 no tomó | I 6 · C 8 · E 3 |
| 5 | **Actualizar Cronograma dice qué espera de ti cuando está vacía** | nuevo | Es la pantalla del paso 6 y la única de la cascada sin estado vacío | I 5 · C 9 · E 8 |
| 6 | **La fecha se lee entera en Programa General** | `F-2` | Sin cambios | I 3 · C 5 · E 2 |

### Diferible

Las filas 7 a 15 de la revisión del 2026-08-05 **siguen vigentes tal cual** salvo cuatro que el Frente
1 cerró y que aquí se dan de baja: `M-3` (chips con `aria-pressed`), `M-5`/`S-6`(1) (vacío del filtro),
`N-4`/`N-5` (estado «guardando» en escritorio) y `N-6`/`N-7` (cierre de semana), más `C-14` (el aviso
«Sin asignar»), cerrada con un residuo encolado como `D-F1-2`. Se añaden:

| # | Resultado buscado | Entrada | ICE |
|---|---|---|---|
| 16 | Cambiar de semana se hace en un solo sitio, no en dos menús | nuevo | I 4 · C 9 · E 4 |
| 17 | La barra de PI baja de once controles a los que se usan a diario | nuevo | I 5 · C 7 · E 5 |
| 18 | Re-medir la ficha del chip «Auto-Guardado» de PGA, que hoy sí se oculta | ICE 320 | I 3 · C 8 · E 9 |

### Decisiones de dominio que solo puede tomar el usuario

Recogidas en `docs/decisiones-pendientes.md` (`D-F1-1`, `D-F1-2`) y en el §Pendiente de decisión del
usuario de `docs/DESIGN-AUDIT.md`.

## Archivos relacionados

- `docs/CUSTOMER.md` — a quién sirve el producto y cuáles son sus tres *jobs*.
- `docs/POSITIONING.md` — qué le promete a cada rol.
- `docs/DESIGN-AUDIT.md` — las entradas medidas, con su disposición real.
- `docs/EXPERIMENTS.md` — el backlog ICE compartido.
- `docs/IMPROVE-APP-PLAN.md` — el recorrido `improve-app` que produjo las lentes.
- `docs/decisiones-pendientes.md` — lo que espera criterio del usuario.
- `.superpowers/sdd/2026-08-11-frente-1c-pulido-a11y-y-texto/fase-9-report.md` — el informe de esta pasada.
