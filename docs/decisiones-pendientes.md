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

---

## Resueltas

*(Ninguna todavía.)*
