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
- **Estado:** `abierta`

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
- **Estado:** `abierta`

---

## Resueltas

*(Ninguna todavía.)*
