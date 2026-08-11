# Decisiones pendientes del usuario

Cola de decisiones que necesitan el criterio del usuario y **no interrumpen el trabajo**. Cualquier
sesión de ejecución añade aquí lo que encuentre, se salta ese punto y sigue. La sesión coordinadora
presenta la cola entera al cerrar cada frente, en una sola tanda de grilleo.

El procedimiento está en [`coordinacion-sesiones.md`](coordinacion-sesiones.md). Regla que lo
sostiene: **una sesión de ejecución nunca para** — anota, salta y continúa.

## Cómo añadir una entrada

Una entrada sirve si el usuario puede decidir **sin abrir el código**. Eso exige haber medido antes
de preguntar. Copia esta forma:

```markdown
### D-<n> · <título en una línea>

- **Quién pregunta:** <sesión / frente / tarea>
- **Fecha:** <AAAA-MM-DD>
- **Qué se decide:** una frase, en lenguaje simple.
- **Qué se midió:** los hechos, con `archivo:línea` o salida de comando. Sin esto la pregunta no
  vale: obliga al usuario a investigar lo que debía traerle resuelto.
- **Opciones:** las reales, con su consecuencia. Si hay una segura o reversible, señálala.
- **Recomendación:** cuál y por qué. Se le pide criterio, no que elija a ciegas.
- **Qué quedó saltado esperando:** qué no se tocó, para poder retomarlo.
- **Estado:** `abierta` · `resuelta <AAAA-MM-DD>: <decisión>`
```

---

## Abiertas

### D-1 · Dos numeraciones de «frente» conviviendo en el repo

- **Quién pregunta:** sesión coordinadora, auditoría del 2026-08-10.
- **Fecha:** 2026-08-10
- **Qué se decide:** cuál de las dos formas de numerar los frentes es la buena, para que los
  documentos dejen de contradecirse.
- **Qué se midió:**
  - `docs/superpowers/specs/2026-08-10-programa-cierre-pendientes-design.md` numera **Frente 0, 1,
    1b, 2, 3, 4 y 5**, donde el 1 son los hallazgos del backlog y el 4 es la matriz diagonal de
    gates.
  - `docs/reportes/estado-desarrollo.html` (commit `0e48bdc8`) llama **«frente 4»** al trabajo de
    RBAC, que en el spec es el **Frente 1, tanda 1A**.
  - Las dos numeraciones están vivas y publicadas a la vez. Quien lea ambas creerá que se habla de
    trabajos distintos, o peor, que el mismo número es dos cosas.
- **Opciones:**
  - **(a)** El informe adopta la numeración del spec. El spec es el contrato del programa y define
    la condición de hecho de cada frente.
  - **(b)** El spec adopta la del informe. Tiene sentido solo si esa numeración es anterior y ya se
    usa en más sitios de los que he visto.
  - **(c)** Cada uno conserva la suya y se añade una tabla de equivalencia. Evita reescribir, pero
    deja dos vocabularios para lo mismo, que es como empezó el problema.
- **Recomendación:** **(a)**. El spec es el documento que gobierna el trabajo y el que las sesiones
  de ejecución leen para saber qué les toca; el informe describe. Cuando una descripción y un
  contrato discrepan, se corrige la descripción. Reversible: renumerar un informe no rompe nada.
- **Qué quedó saltado esperando:** nada. Es documental y no bloquea a nadie.
- **Estado:** `abierta`

---

## Resueltas

### D-F1-1 · La misma falta se pinta como «crítico» en una pantalla y como «aviso» en la otra

- **Quién pregunta:** sesión de ejecución del Frente 1, tanda 1B, Task 5.
- **Fecha:** 2026-08-10
- **Qué se decide:** cuando una actividad no tiene Responsable AIA asignado, ¿eso es un **error
  grave** (rojo) o un **aviso** (ámbar)? Hoy Programación Semanal lo pinta rojo y Programación
  Intermedia lo pinta ámbar, y es exactamente la misma falta.
- **Qué se midió:**
  - Programación Semanal: `.ps-missing-assignment` usa `--ds-color-state-critical-text` y **no
    declara peso de fuente** (`public/css/programacion-semanal.css:1690-1693`).
  - Programación Intermedia: `.pi-missing-resp` usa `--ds-color-state-warning-text` y **pesa 600**
    (`public/css/programacion-intermedia.css:532-537`).
  - Las dos marcan la misma condición y las dos bloquean: en Semanal impide cerrar la semana, en
    Intermedia impide gestionar restricciones.
  - **Esta divergencia no estaba registrada en ninguna parte.** La ficha del backlog (ICE 392)
    afirmaba lo contrario —que Intermedia «no marca esa celda de ninguna forma»—, y es falso desde
    el commit `7ff39b54` del 2026-08-05.
- **Opciones:**
  - **(a) Las dos en «crítico» (rojo).** Coherente con que la falta bloquea en las dos pantallas.
    Hace Intermedia más alarmante de lo que es hoy.
  - **(b) Las dos en «aviso» (ámbar).** Reserva el rojo para lo que ya no tiene arreglo dentro de la
    pantalla. Baja el tono de Semanal, que es donde más consecuencia tiene.
  - **(c) Dejarlas distintas y escribir por qué.** Defendible si el rojo de Semanal es deliberado
    porque ahí la falta bloquea el cierre del ciclo, y en Intermedia solo bloquea una columna.
  - Las tres son reversibles: es un token de color en una hoja de estilo, sin efecto sobre datos.
- **Recomendación:** **(c)**, escribiendo el motivo. La consecuencia real **no** es la misma en las
  dos pantallas —en Semanal la falta frena el cierre de la semana entera, en Intermedia solo impide
  editar unas celdas—, así que dos tonos distintos comunican algo cierto en vez de una
  inconsistencia. Lo que hoy falta no es que coincidan: es que nadie escribió que la diferencia era
  a propósito.
- **Qué quedó saltado esperando:** solo la elección de severidad. **Sí se hizo** lo que el usuario ya
  había aprobado para el ICE 448 —dar regla a la clase de fondo `.ps-cell-empty-alert`, que no tenía
  ninguna en todo el árbol, y subir el peso del glifo de Semanal—, porque esa disposición dice «es
  solo visual» y no depende de esta decisión.
- **Estado:** `resuelta 2026-08-11: (c) — las dos severidades se quedan distintas (crítico en Semanal,
  aviso en Intermedia) y el motivo queda escrito junto a cada regla (programacion-semanal.css y
  programacion-intermedia.css, junto a .ps-cell-empty-alert y .pi-missing-resp). Cero CSS tocado.`

### D-F1-2 · Ningún token de fondo de estado llega a 3:1 contra la superficie base de la tabla

- **Quién pregunta:** sesión de ejecución del Frente 1, tanda 1B, Task 5 (ronda de arreglo 1/5).
- **Fecha:** 2026-08-10
- **Qué se decide:** si vale la pena crear o ajustar un token de fondo de estado para que el aviso
  «⚠ Sin asignar» de Programación Semanal cumpla el 3:1 de WCAG 1.4.11 **fondo-contra-fondo**, o si
  se acepta que ese canal se queda por debajo y la distinción real corre por otra vía (forma + peso
  + contraste de texto).
- **Qué se midió:** en `/programacion-semanal`, dark, 1180×820, sobre el árbol real de Handsontable
  (`--ds-active-surface` resuelto a `rgba(28,36,31,0.92)`, compuesto contra `--ds-active-bg-page`
  `rgb(17,26,21)`, converge a `rgb(28,36,31)` opaco — verificado además leyendo `backgroundColor`
  real de celdas vecinas ya renderizadas en Programación Intermedia, que comparte el mismo stack de
  CSS: dan literalmente `rgba(28, 36, 31, 0.92)`). Contraste de luminancia WCAG de cada token de
  fondo de estado (`tokens.css:241-244`) contra esa superficie base:
  - `--ds-color-state-critical-bg` `rgb(67,20,20)` → **1,02:1**
  - `--ds-color-state-warning-bg` `rgb(58,58,15)` → **1,36:1**
  - `--ds-color-state-success-bg` `rgb(23,61,38)` → **1,31:1**
  - `--ds-color-state-info-bg` `rgb(19,72,65)` → **1,54:1**
  Ninguno de los cuatro tokens de fondo de estado que existen hoy llega ni cerca del 3:1. El elegido
  para esta tarea (`critical-bg`, el que aprobó el plan) es el **peor** de los cuatro por ese criterio
  específico, aunque es el correcto por severidad (ver `D-F1-1`).
- **Cómo se ve, sin jerga —para poder decidir sin abrir el código:** la celda del aviso **sí** se
  distingue de sus vecinas, pero **no por el fondo**. Lo que se ve es el texto «⚠ Sin asignar» en
  rosa claro y en negrita, sobre un fondo rojo tan oscuro que a simple vista parece el mismo negro
  verdoso que el resto de la tabla. El `1,02:1` significa literalmente eso: entre ese fondo y el de
  al lado hay **un 2 % de diferencia de luminosidad**, y la norma pide un 200 %. El texto en sí se
  lee perfectamente (esa es la otra cifra, `10,99:1`).
  - Traducido a la pregunta que importa: **la celda se nota por su letra, no por su color de fondo.**
    Si alguien mira la tabla entornando los ojos o de lejos, no verá un bloque rojo — verá una fila
    con un texto distinto.
  - Lo que esta tarea **sí** mejoró: antes esa letra no era negrita y el fondo no existía en absoluto
    (la regla de estilo estaba escrita en el código pero no llegaba a aplicarse nunca). Ahora la letra
    pesa y el fondo existe, aunque casi no se aprecie.
- **Opciones:**
  - **(a) Aceptar que este canal no llega a 3:1** y dejar que la distinción de la celda dependa de lo
    que sí mide bien: la marca sobre su propio fondo da 10,99:1 (1.4.3, texto) y el peso 600 añade una
    señal de forma. Es lo que hay hoy tras esta tarea. No toca ningún token.
  - **(b) Subir la saturación/luminancia del propio `critical-bg`** (o crear una variante «surface»
    más clara solo para bordes de componente) hasta que separe 3:1 de la superficie base. Cambia un
    token compartido por otras superficies (`--ds-cell-state-critico-bg` lo reutiliza en
    `tokens.css:635`), así que el efecto se propaga fuera de esta celda — alcance de design system,
    no de este módulo.
  - **(c) Añadir un borde o contorno con un token de línea de estado** (`--ds-color-border-*` si
    existe uno de severidad) en vez de depender del fondo para el 3:1 de componente. No se investigó
    si ese token existe; queda para quien tome la decisión.
  - Todas son reversibles: es CSS/tokens, sin dato de por medio.
- **Recomendación:** **(a)** para esta tarea puntual — es la única que no reabre alcance de design
  system ni afecta otras superficies que ya consumen `critical-bg`/`critico-bg`—, pero **(b)** o
  **(c)** merecen mirarse a nivel de sistema porque el patrón se repite: los cuatro tokens de fondo de
  estado están pensados para tinte de superficie completa, no para el contraste de borde de un
  componente pequeño sobre una superficie ya oscura, y probablemente ningún uso futuro de estos
  tokens en una celda pequeña vaya a llegar a 3:1 tal como están calibrados hoy.
- **Qué quedó saltado esperando:** no se tocó ningún token ni se creó uno nuevo. El CSS de esta tarea
  se queda con `--ds-color-state-critical-bg` tal como está.
- **Estado:** `resuelta 2026-08-11: familia nueva --ds-color-cell-*-bg (critical #c43b3b, warning
  #71711d, success #2e7b4d, info #207a6e), calibrada a ≥3:1 contra rgb(28,36,31) (medidos: 3,054 ·
  3,089 · 3,07 · 3,08). Los cuatro --ds-color-state-*-bg no se tocaron. .ps-cell-empty-alert ahora
  usa --ds-color-cell-critical-bg; fondo-alerta contra fondo-vecino subió de 1,02:1 a 3,054:1, que
  cierra el ICE 448.`

### D-F1-3 · Los cinco tokens con reserva hex en `public/js/` no están definidos en ningún CSS

- **Quién pregunta:** sesión de ejecución del Frente 1, tanda 1C, Task 3.
- **Fecha:** 2026-08-11
- **Qué se decide:** las cinco ocurrencias `var(--token, #hex)` de `public/js/` (frente a las 12 sin
  reserva) iban a perder su reserva porque esa es la política dominante del repo — pero el paso de
  seguridad exigido por la propia tarea (Step 3: quitar la reserva solo si el token existe) dio
  negativo para **las cinco**. No hay ninguna que se pueda tocar sin cambiar el color en pantalla.
- **Qué se midió:**
  - Recuento repetido y confirmado: `grep -rn "var(--[a-z-]*, *#" public/js/` → 5 ocurrencias con
    reserva; `grep -ro "var(--[a-z-]*)" public/js/ | wc -l` → 12 sin reserva. La dirección «quitar
    reserva» sigue siendo la que gana.
  - Las cinco ocurrencias con reserva, hoy (los números de línea se movieron otra vez):
    - `public/js/modules/programacion_intermedia/hot.js:2088` → `--aia-text-muted`
    - `public/js/modules/programacion_intermedia/hot.js:2829` → `--aia-warning-soft-bg` y
      `--aia-warning-border` (dos en la misma línea)
    - `public/js/modules/programa_actualizar/hot_actualizar.js:1266` → `--aia-red-primary`
    - `public/js/modules/programa_actualizar/hot_actualizar.js:1374` → `--aia-text-muted`
  - Búsqueda de cada uno en `public/css/` (`grep -rn -- "--<token>:" public/css/` y una pasada sin
    ancla por si el nombre variaba): **ninguno de los cuatro nombres existe** —
    `--aia-text-muted`, `--aia-warning-soft-bg`, `--aia-warning-border` y `--aia-red-primary` no
    aparecen definidos en ningún archivo de `public/css/`. Solo el token de la línea vecina sin
    reserva, `--aia-green-primary`, sí está definido (`public/css/tokens.css:7`,
    `oklch(32% 0.07 148.5)`).
  - Es decir: la asimetría original entre `:1374` (con reserva) y `:1377` (sin reserva) **no era
    arbitraria** — la línea con reserva usa un token que de verdad no existe, y la reserva es lo
    único que le pone color hoy. Lo mismo pasa en los otros tres consumos con reserva.
- **Opciones:**
  - **(a) Dejar las cinco líneas como están.** Es lo seguro: nadie pierde color. La asimetría de
    estilo queda, pero documentada, y ya no es un misterio — tiene una causa medida.
  - **(b) Definir los cuatro tokens que faltan** (`--aia-text-muted`, `--aia-warning-soft-bg`,
    `--aia-warning-border`, `--aia-red-primary`) en `public/css/tokens.css` con un valor equivalente
    al hex que hoy los sustituye, y **entonces sí** quitar la reserva en las cinco líneas. Alinea el
    JS con la política del repo sin cambiar ningún color, pero es una tarea de sistema de diseño (dar
    de alta tokens, decidir su valor definitivo, ver si otras superficies ya los necesitan), no un
    refactor de una línea.
  - **(c) Quitar la reserva igual, aceptando el cambio de color a lo que el navegador resuelva por
    defecto** para una variable CSS no definida (que normalmente es `unset`/heredado, no un color
    visible). Descartada: es exactamente lo que la tarea pedía evitar.
- **Recomendación:** **(a)** para esta tarea. **(b)** es la solución de fondo y probablemente valga
  la pena — cuatro tokens usados como reserva-sin-definición es una señal de que faltan en el sistema
  de tokens, no solo en estas cinco líneas — pero es un frente propio con su propio plan, no algo que
  decidir de paso en un refactor de formato.
- **Qué quedó saltado esperando:** no se tocó ninguna de las cinco líneas con reserva. `hot.js` y
  `hot_actualizar.js` quedan sin cambios de esta tarea.
- **Estado:** `resuelta 2026-08-11: se repitió la comprobación en el navegador (canvas 2D contra
  tokens.css real, ya que Chromium serializa oklch en getComputedStyle en vez de convertir a rgb).
  Ninguno de los cuatro --aia-* tiene un token real con el MISMO color computado: --aia-text-muted
  (fallback #6c757d/#666 → rgb(108,117,125) y rgb(102,102,102)) frente a --ds-active-text-secondary
  → rgb(199,212,204), cambia. --aia-warning-soft-bg (#fef3c7 → rgb(254,243,199)) frente a
  --aia-warning-background → rgb(255,247,226), cambia. --aia-warning-border (#f59e0b →
  rgb(245,158,11)) frente a --aia-warning-high → rgb(255,194,0) o --aia-warning-critical →
  rgb(156,102,0), cambia en ambos casos. --aia-red-primary (#dc3545 → rgb(220,53,69)) frente a
  --aia-alert-high → rgb(212,11,30), cambia. Condición bloqueante de la coordinadora incumplida en
  las cinco líneas: no se forzó ninguna sustitución, las cinco quedan sin tocar. Sigue siendo (b) la
  solución de fondo, ahora con los valores computados que faltaban.`

### D-F1-4 · Programa General y Programación Semanal tampoco marcan una acción primaria

- **Quién pregunta:** sesión de ejecución del Frente 1, tanda 1C, Task 4.
- **Fecha:** 2026-08-11
- **Qué se decide:** si a las toolbars de Programa General (`/programa-general/actualizar`) y
  Programación Semanal (`/programacion-semanal`) también les conviene declarar una acción primaria,
  como ya se hizo en esta tarea con «Restricción Compartida» de Programación Intermedia por decisión
  explícita del usuario. La ficha original del ICE 180 daba por hecho que «en las otras tres toolbars
  se pudo señalar cuál es la principal» — **eso es falso hoy**, medido al aplicar el cambio.
- **Qué se midió** (dark, código actual, sin tocar ninguno de los dos archivos):
  - **Programa General** (`views/programa-general-actualizar/programaGeneralActualizar.view.php:98-110`,
    `.pg-toolbar-buttons`): **4 botones**. `btn_cargarCronogramaExcel` («Cargar desde Excel») ya lleva
    `aia-btn-primary` (variante guion simple, la misma familia que «Actualizar Cronograma»); los otros
    tres (`btn_eliminarActualizacion`, `btn_toggleFiltroMapeo`, `btn_autoAsociar`) llevan
    `aia-btn-ghost`. Es decir, **Programa General ya tiene una acción marcada como distinta** de las
    demás — no es una toolbar plana como PI lo era. El candidato razonable a «primaria real» sigue
    siendo «Cargar desde Excel»: es la acción que dispara el flujo completo del módulo (las otras tres
    son eliminar, alternar vista y auto-asociar, todas de apoyo).
  - **Programación Semanal** (`views/programacion-semanal/programacion_semanal.view.php:70-110`,
    `.ps-hot-toolbar-actions`, excluyendo el botón de colapso móvil): **7 controles visibles en
    desktop** — `btn_autoprogramar`, `btn_agregar_actividad`, `btn_cerrar_compromisos_semana`,
    `btn_reabrir_semana` (oculto en runtime normal), `btn_tnp` (ídem), `btn-refresh`, el enlace BI
    Semanal, más dos disparadores de dropdown («Más» y «Ver Secciones»). De estos,
    `btn_cerrar_compromisos_semana` («Confirmar Compromisos») **ya lleva `class="aia-btn"` sin
    `--secondary`**, así que hoy ya renderiza con el estilo relleno/primario de `core.css` — es, de
    hecho, el único botón de las tres toolbars que ya se comporta como «primaria» sin que esta tarea
    lo tocara. Los otros seis controles de la barra sí son `aia-btn--secondary`.
  - Conclusión de la medición: la premisa de la ficha («las otras tres ya señalan la suya») es
    correcta a medias — Programa General y Programación Semanal **sí tienen ya, cada una, un botón
    visualmente distinto del resto** (por variante de clase, no por decisión de jerarquía explícita
    documentada), pero Programación Intermedia no tenía ninguno hasta este cambio.
- **Opciones:**
  - **(a) No tocar nada.** PG y PS ya tienen, de hecho, un botón que destaca (aunque llegó ahí por
    variantes de clase preexistentes, no por un rediseño de jerarquía). Aceptar eso como suficiente.
  - **(b) Formalizar la jerarquía en las dos**: migrar `aia-btn-primary` (PG) y el `aia-btn` pelado de
    PS a `aia-btn--primary` (BEM) para que ambas usen la misma convención que PI y evitar la
    convivencia de variantes documentada en `D-F1-`(hallazgo de Task 4, ver informe) sin cambiar el
    botón elegido.
  - **(c) Revisar si el botón que hoy destaca en cada una (`Cargar desde Excel` en PG, `Confirmar
    Compromisos` en PS) es de verdad la acción primaria correcta**, en vez de asumir que la variante
    de clase heredada acertó por accidente.
- **Recomendación:** ninguna — es criterio de negocio sobre cuál debe ser la acción primaria de cada
  pantalla, no algo que esta tarea deba decidir. Sí se anota que **(b)** es de bajo riesgo si se
  decide seguir: no cambia qué botón destaca, solo unifica la clase.
- **Qué quedó saltado esperando:** no se tocó ni `programaGeneralActualizar.view.php` ni
  `programacion_semanal.view.php`. El cambio de esta tarea se limitó a Programación Intermedia.
- **Estado:** `resuelta 2026-08-11: el usuario eligió sin darle vueltas — «Confirmar Compromisos» en
  Programación Semanal y «Actualizar Ejecución» en Programa General pasan a aia-btn--primary (BEM),
  quitando aia-btn--secondary donde existía. Verificado que ambas siguen funcionando (render con
  clases y estilo primario aplicado, sin error de consola). Contraste medido en /programa-general
  (proyecto Da Porto): texto sobre botón rgb(20,28,24) contra rgb(108,144,119) → 4,87:1 (1.4.3, piso
  4.5, pasa); botón contra fondo rgb(108,144,119) contra rgba(28,36,31,0.92) → 4,46:1 (1.4.11, piso
  3, pasa). Mismos valores de token en ambos botones, misma clase.`

### D-F1-5 · El hueco reservado para el botón flotante no llega a cubrirlo del todo

- **Quién pregunta:** sesión de ejecución del Frente 1, tanda 1C, Task 5.
- **Fecha:** 2026-08-11
- **Qué se decide:** si conviene ampliar el token de espacio del sistema (crear `--ds-space-16` u
  otro mayor) para que el hueco reservado al final de las tablas Handsontable cubra por completo al
  botón flotante `.lps-sidebar-trigger`, o si el solape residual medido es aceptable.
- **Qué se midió** (dark, 1180×820, `public/css/handsontable-module.css:670-693`):
  - El botón mide 50px y se posa a 20px del borde inferior: el hueco necesario es de al menos 70px.
  - `public/css/tokens.css` no define `--ds-space-16`; el mayor token de espacio existente es
    `--ds-space-12` (`3rem` = 48px, `tokens.css:459`). Se usó ese, documentado en el propio CSS
    (líneas 671-683 de `handsontable-module.css`).
  - Con `--ds-space-12` aplicado sobre `.wtHider` (con `box-sizing: border-box !important` forzado en
    `.wtHolder` para que el padding no infle también el alto visible — ver el comentario en el propio
    archivo), el `scrollHeight` de `.wtHolder` crece en 48px de forma consistente en ambos módulos
    medidos. El solape se **reduce pero no se elimina**:
    - `/programa-general` (proyecto "Da Porto", 29 filas): antes, última fila
      `{top: 683.58, bottom: 814.58}` vs botón `{top: 750, bottom: 800}` → 50px de solape. Después,
      última fila `{top: 635.58, bottom: 766.58}` → solape residual de **16.6px** (750 a 766.58).
    - `/programacion-semanal` (proyecto "Optimización Aeropuerto JMC", 9 filas): antes, última fila
      `{top: 725.8, bottom: 814.8}` vs botón `{top: 750, bottom: 800}` → 50px de solape (columna
      «Acciones» en el mismo extremo que señalaba la ficha del Task 5, confirmado que **sí existía**).
      Después, última fila `{top: 677.8, bottom: 766.8}` → solape residual de **16.8px**.
- **Opciones:**
  - **(a) Aceptar el solape residual (~17px)** como mejora suficiente: bajó de 50px a 17px, el botón
    ya no cubre el valor completo de la celda, solo roza su borde inferior.
  - **(b) Crear un token de espacio mayor** (p. ej. `--ds-space-16` = 4rem/64px, o uno específico
    para este caso) en `tokens.css` y usarlo aquí, para eliminar el solape del todo.
  - **(c) Combinar dos tokens** (p. ej. `--ds-space-12` + `--ds-space-4`) en la propiedad, aunque esto
    se aparta de "un token, no un número suelto" que pide la ficha.
- **Recomendación:** (b) si el equipo de diseño ya tiene planeado ampliar la escala de espaciado;
  si no, (a) es razonable como cierre de esta tarea — el solape pasó de cubrir el valor completo de
  la celda a rozar apenas su borde.
- **Qué quedó saltado esperando:** no se creó ningún token nuevo ni se tocó `tokens.css`.
- **Estado:** `resuelta 2026-08-11: (b) — se añadió --ds-space-18 (4.5rem/72px) a tokens.css, que
  respeta la progresión N*4px de la escala (72/4=18) y llega al piso de 70px. Aplicado al
  padding-block-end de #hot-container .wtHolder. Solape vuelto a medir con getBoundingClientRect():
  /programa-general (Da Porto, 34 filas) gap de 7,42px entre última fila y el botón flotante;
  /programacion-semanal (proyecto Prueba, 15 filas) gap de 7,20px. overlap: false en ambas — cero
  solape, cierra el ICE 216.`

---
