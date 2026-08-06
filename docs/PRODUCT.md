# PRODUCT — la Única Cosa, y qué falta para que se note

Cierre de la campaña de dark mode (2026-08-05, Task 31). Este documento es la **revisión en frío**
del flujo núcleo `Programa General → Programación Intermedia → Programación Semanal`, hecha como
usuario nuevo, y el **Outcome Roadmap** que sale de ella.

**Este archivo no es contrato.** El contrato de consumo visual es `DESIGN.md`; el de producto vive en
`docs/CUSTOMER.md` (a quién servimos) y `docs/POSITIONING.md` (qué le prometemos). Aquí solo se anota
**qué haría el producto más nítido, en qué orden y por qué**.

**Nada de lo que sigue se ha aplicado.** Los cortes se **proponen**: no se borró ni se ocultó ninguna
pantalla, ninguna ruta y ninguna acción. La decisión es del usuario.

**Método y su límite.** Recorrido real en navegador a 1180×820 dark contra la rama
`campana/cierre-dark-mode-2` (HEAD `58ba25ab`) servida en un contenedor efímero, sesión `test.R`
(Residente de Obra) en el proyecto `PDC Sandbox E2E`, **solo lectura**. Lo que **no** se hizo: no se
importó un cronograma real ni se confirmó una semana, así que los pasos 6 a 14 de la cuenta de abajo
están derivados del código y de las mediciones ya registradas en `docs/DESIGN-AUDIT.md` (M-4, N-6,
N-7), no cronometrados de punta a punta.

---

## La Única Cosa

> **Cada semana la obra promete solo lo que de verdad puede cumplir, y al terminar la semana se sabe
> si cumplió.**

Todo lo demás del producto —el cronograma, las restricciones, los informes, el plan de compras— existe
para que esa promesa semanal sea creíble. Es la definición operativa de Last Planner y es también,
verificado en `docs/CUSTOMER.md`, el cuello de botella de los tres *jobs* a la vez.

La consecuencia práctica de nombrarla: **si una pantalla no acerca al usuario a esa promesa, compite
con ella.** Ése es el criterio con el que están escritos los cortes de más abajo.

## Pasos hasta el valor

El «valor» aquí es concreto y comprobable: **la primera vez que el producto le dice al residente algo
que él no sabía** — qué actividades están retenidas, o cuál fue su PAC.

| # | Paso | Fricción medida |
|---|---|---|
| 1 | `/login` | — |
| 2 | `/proyectos` → «Ingresar al proyecto» | limpio; la mejor pantalla de entrada de la app |
| 3 | Aterriza en `/programacion-semanal` | **vacía**: «Sin actividades programadas esta semana» |
| 4 | Debe deducir que primero hay que cargar el cronograma | **no hay ninguna pista en pantalla** |
| 5 | Encontrar `/programa-general-actualizar` en el carril | icono 9 de 10, **sin etiqueta visible** |
| 6 | Subir el XLSX | **sin acuse de recibo de ningún tipo** (`M-4`) |
| 7 | Ir a `/programacion-intermedia` | icono 7 de 10 |
| 8 | Liberar restricciones | bloqueado si falta Responsable AIA — **ya se explica en la celda** (Task 38) |
| 9 | Volver a `/programacion-semanal` | icono 8 de 10 |
| 10 | «Autoprogramar Actividades» | primera vez que la rejilla deja de estar vacía |
| 11 | Comprometer cantidades | — |
| 12 | «Confirmar Compromisos» | irreversible para el residente (`N-2`, **doctrina confirmada**) |
| 13 | *Esperar a que pase la semana* y registrar el avance real | — |
| 14 | El PAC significa algo | **primer valor** |

**Trece pasos y una semana entera antes del primer dato útil, sin ninguna guía dentro del producto.**
Y el contraste está en la casa: `/plan-compras` —un módulo secundario— sí tiene un recorrido de seis
paradas que la fase de copy calificó como **el mejor texto de la aplicación** (`S-3`). La cascada, que
es la Única Cosa, no tiene ninguno.

## Veredicto binario

> **NO.** Todavía no se le puede poner delante a un usuario nuevo **sin acompañamiento**.

Y conviene decir con la misma claridad la otra mitad, porque es lo que la campaña compró: **para un
usuario ya entrenado, sí está listo.** Las 28 superficies pasan el contrato entero —cero errores de
consola, cero desbordamiento horizontal, `<main>` y `h1` en las 28, cero celdas y cero cabeceras
recortadas—, el dato se lee bien, la jerarquía es sobria y las redes de prueba volvieron a apretar.
Lo que falta **no está en las pantallas: está en la entrada a las pantallas.**

## Los cortes, en orden

Ordenados por cuánto acercan la Única Cosa. **Ninguno borra nada:** cortar aquí significa *sacar del
camino principal*, no eliminar.

### Corte 1 · La cascada no tiene primera vez

El residente aterriza en una rejilla vacía cuyo estado vacío le ofrece **las dos acciones que en un
proyecto nuevo no pueden funcionar** —«Agregar Actividad» y «Autoprogramar Actividades», que trae
desde una Programación Intermedia que todavía está vacía—. El producto le está pidiendo el segundo
paso antes del primero.

**Propuesta:** un recorrido de primera vez para la cascada, con la misma forma que el de
`/plan-compras` (que ya está escrito, medido y funciona), **o**, si se quiere lo barato: que el estado
vacío de PS detecte «proyecto sin cronograma» y ofrezca la única salida real —importar—, en vez de las
dos que no lo son. Lo barato es media hora; lo bueno es el recorrido.

### Corte 2 · El carril es mudo y está en el orden equivocado

Diez iconos en 64 px. Tienen `aria-label` —así que un lector de pantalla los lee bien—, pero **ninguno
tiene `title`**, así que el usuario que ve la pantalla no obtiene nada al pasar el ratón: tiene que
abrir el cajón para aprender qué es cada uno. Y el orden entierra la Única Cosa:

`Control Tower · Profesionales · Subcontratistas · Indicadores · Control de Cambios · **Programa
General · Prog. Intermedia · Prog. Semanal** · Actualizar Cronograma · Plan de Compras`

La cascada ocupa los puestos **6, 7 y 8** de diez. Cinco destinos secundarios se leen antes que el
trabajo diario.

**Propuesta:** (a) etiqueta visible al pasar el ratón, que es un `title` por enlace; (b) reordenar a
**Semanal · Intermedia · General · Actualizar Cronograma** arriba, y el resto debajo de un separador.
Es cambio de navegación: se propone, no se aplica.

### Corte 3 · Cinco superficies compiten con la Única Cosa en el mismo carril

Para el rol Residente, y solo para él:

- **`/control-cambios`** — hoy es de **solo consulta**: la propia pantalla dice «La edición aún no está
  disponible en esta pantalla». Ofrecer en el carril principal una pantalla que no deja hacer nada es
  gastar atención sin devolver nada (`R-5`).
- **`/indicadores`** — es un `iframe` de Power BI que esta campaña enmarcó bien, pero cuyo interior no
  controlamos y que fuera de Power BI llega en blanco. Su valor es para la gerencia, no para el
  residente (`R-6`).
- **`/profesionales`**, **`/subcontratistas`** — maestros que se tocan una vez al mes.
- **`/plan-compras`** — módulo propio, con su propio ciclo.

**Propuesta:** una sola entrada «Gestión» que las agrupe, dejando el carril principal para la cascada.
Repito lo obvio porque importa: **no se borra ninguna ruta ni se le quita el permiso a nadie**.

## Arreglos (no son cortes: son deuda que se paga)

En orden de daño:

1. **Páginas de error.** `404` y `403` responden **13 bytes de texto plano** sin shell, sin navegación
   y sin vuelta atrás. Quien se equivoca de URL o choca con un permiso **sale del producto** (`R-1`).
2. **Acuse de recibo al importar.** La operación más larga de la app no cambia un píxel entre el
   submit y la respuesta, y pulsar dos veces lanza dos importaciones. La receta ya está en el repo
   (`M-4`).
3. **La flecha del selector tapa el último dígito de las fechas en PG** — «2026-04-30» se puede leer
   «2026-04-3» (`F-2`).
4. **Cabeceras partidas en `/control-cambios`** — «Priorid/ad», «Intervento/ría» (`F-1`).
5. **Los gatillos de ayuda de PI**: 8×8 px, sin nombre accesible y sin ruta de teclado a su contenido
   (`F-3`).

## La parte de atrás de la valla

Lo que nadie enseña en una demo y todo el mundo acaba viendo.

| Superficie | Estado |
|---|---|
| **404 / 403** | **el peor punto del producto.** 13 bytes de texto plano, sin salida (`R-1`) |
| **Sesión caducada** | correcto: redirige a `/login` sin ruido |
| **Estados vacíos** | **buenos casi en todos**: CNC, CNP y CIC explican *cómo se llena* la tabla, que es el patrón correcto. Las excepciones están medidas: `/indicadores` («Ningún dato disponible en esta tabla =(», `S-4`), el historial de Plan de Compras (`S-5`), el vacío de PS al filtrar que se disfraza de vacío de semana (`M-5`) y los dos apilados de `/control-cambios` (`F-6`) |
| **Errores de red y de carga** | **reescritos en esta campaña** (`S-1`): ya dicen qué se perdió y qué hacer |
| **Error de guardado de celda** | bien resuelto: reversión + `ps-cell-shake` + badge, con guard de `prefers-reduced-motion` |
| **Cierre de semana con bloqueos** | el peor momento informado del flujo: el detalle **corta a 8 sin decirlo** (`N-6`) y el rechazo del servidor **no señala ninguna fila** (`N-7`) |
| **Rol denegado dentro de la app** | los controles se ocultan (correcto), pero al llegar por URL cae en el 403 pelado de `R-1` |

---

## Outcome Roadmap

**ICE** = Impacto × Confianza ÷ Esfuerzo, en la escala del propio repo (`docs/EXPERIMENTS.md`).
Ninguna fila está aprobada: es una recomendación priorizada.

### Bloqueante — antes de enseñárselo a alguien de fuera

| # | Resultado buscado | Entrada | Por qué ahora | ICE |
|---|---|---|---|---|
| 1 | **Quien se equivoca de URL o choca con un permiso sigue dentro del producto** | `R-1` | Es la única cosa de esta lista que expulsa al usuario de la aplicación. Dos plantillas dentro del shell existente | I 4 · C 5 · E 1 |

### Ahora — el siguiente trabajo de producto

| # | Resultado buscado | Entrada | Por qué ahora | ICE |
|---|---|---|---|---|
| 2 | **Un residente nuevo llega solo hasta su primer PAC** | `R-2`, `R-4` | Trece pasos y una semana sin ninguna guía. El patrón ya está escrito en `/plan-compras` y calificado como el mejor copy de la app | I 5 · C 4 · E 3 |
| 3 | **El carril dice qué es cada icono y pone la cascada primero** | `R-3` | La Única Cosa ocupa los puestos 6-8 de 10. `title` es barato; reordenar es decisión de navegación | I 4 · C 4 · E 2 |
| 4 | **Importar el cronograma acusa recibo y no se puede lanzar dos veces** | `M-4` | Operación más larga de la app, cero feedback, doble envío posible. La receta está en `funcionesGenerales6.js` | I 4 · C 5 · E 2 |
| 5 | **La fecha se lee entera en Programa General** | `F-2` | Es la clase de defecto que C-31 declaró cerrada: el dato se lee mal. Mueve píxel → necesita aprobar goldens | I 3 · C 5 · E 2 |
| 6 | **El cierre de semana dice cuántas faltan y cuáles** | `N-6`, `N-7` | El momento de mayor consecuencia del ciclo es el peor informado | I 4 · C 4 · E 3 |

### Diferible — vale, pero no compite con lo de arriba

| # | Resultado buscado | Entrada | ICE |
|---|---|---|---|
| 7 | Los maestros y los informes salen del camino diario del residente | `R-5`, `R-6`, Corte 3 | I 3 · C 3 · E 2 |
| 8 | Las cabeceras de `/control-cambios` se leen sin partir palabras | `F-1` | I 2 · C 5 · E 1 |
| 9 | Los gatillos de ayuda de PI tienen nombre, tamaño y teclado | `F-3` | I 3 · C 4 · E 2 |
| 10 | El aviso «Sin asignar» pesa más que el dato que lo rodea | `C-14` | I 3 · C 4 · E 2 |
| 11 | Los chips de PS anuncian su estado (`aria-pressed`) y el filtrado se anuncia | `M-3` | I 3 · C 5 · E 2 |
| 12 | El vacío de un filtro deja de disfrazarse de vacío de semana | `M-5`, `S-6`(1) | I 3 · C 4 · E 2 |
| 13 | El estado «guardando» existe también en escritorio | `N-4`, `N-5` | I 3 · C 4 · E 3 |
| 14 | El piso táctil de 24 px se cumple también en BI y en las casillas de Handsontable | `F-4`, `F-7` | I 2 · C 4 · E 2 |
| 15 | La deuda estructural de CSS (~2.600 hallazgos) entra en plan | `C-2`, `C-11`, `C-15`, `C-20` | I 3 · C 2 · E 5 |

### Decisiones de dominio que solo puede tomar el usuario

No son trabajo pendiente: son preguntas. Están recogidas una a una en el §Pendiente de decisión del
usuario de `docs/DESIGN-AUDIT.md`.

## Archivos relacionados

- `docs/CUSTOMER.md` — a quién sirve el producto y cuáles son sus tres *jobs*.
- `docs/POSITIONING.md` — qué le promete a cada rol.
- `docs/DESIGN-AUDIT.md` — las 88 entradas medidas, con su disposición real.
- `docs/EXPERIMENTS.md` — el backlog ICE compartido.
- `docs/IMPROVE-APP-PLAN.md` — el recorrido `improve-app` que produjo las lentes.
- `docs/superpowers/barrido-diseno-2026-08-03.md` — los cuatro barridos, con la pasada final.
