---
capa: fuente
tipo: evidencia
estado: vigente
fecha: 2026-08-11
areas: [design-system]
fuente: docs/design-system/manifests/programacion-intermedia.goldens.md
resumen: Un golden regenerado sin constancia es indistinguible de uno regenerado para forzar un verde. Este archivo existe para que no lo sea. Acompaña a…
---

# Procedencia de los goldens de `programacion-intermedia`

Un golden regenerado sin constancia es indistinguible de uno regenerado para forzar un verde. Este
archivo existe para que no lo sea. Acompaña a `programacion-intermedia.json`, donde viven las dos
firmas.

## Recaptura del 2026-08-11 — frente `semana-fija-visual`

- **Firmas nuevas:**
  - `programacion-intermedia-dark-1180x820.png` → `21f4be3d13a1194ab999759b1358ffbd47bb3c6b8394d491b13857bff5c9a917`
  - `programacion-intermedia-dark-1440x900.png` → `d70f0e659eac601dfd5ec741e8b9169a7591311d48660ff0b55a055d2322d360`
- **Firmas que sustituyen:** `5333aa68…` y `8e5cd360…`, del 2026-08-07 (`11b8d93c`).
- **Sobre qué sha se recapturó:** `db8a1e6b`, más el cambio del fixture de este frente.
- **Quién lo aprobó:** el usuario, **viendo las tres imágenes** (antes, después y diff), no leyendo
  un resumen. La distinción es deliberada: que quien recaptura entienda el diff no equivale a que
  quien aprueba lo comprenda.

### Qué cambió, y por qué el golden viejo ya no valía

Dos cambios reales, **ninguno de este frente**, ambos ya publicados cuando se recapturó:

1. **`b647499d`** — «la toolbar declara su acción primaria». `#btn-shared-constraint` pasa de
   `aia-btn--secondary` a `aia-btn--primary`: el botón «Restricción Compartida» se ve contorneado en
   el golden viejo y relleno en el nuevo.
2. **`db8a1e6b`** — del frente `vocabulario-estados-cascada`. La etiqueta «Inicio Vencido» pasa a
   «Inicio vencido». El leve corrimiento de los tres chips siguientes **de esa misma fila** es
   consecuencia del ancho de la etiqueta, no un cambio aparte: los de la segunda fila no se mueven.

**La imagen nueva incorpora trabajo de `vocabulario-estados-cascada`, no solo de este frente.** Se
dice explícitamente para que la recaptura no parezca atribuir a `semana-fija-visual` un cambio que
no hizo.

### Lo que este frente sí cambió

Nada de lo que se ve. Cambió **por qué la comparación era estable**: el escenario ya no depende de
la semana en la que esté el proyecto el día que se ejecute. Antes la semana se pintaba en servidor
(`views/partials/shell_sidebar.php:24`) y el test no la controlaba, así que el golden fallaba por
algo que no medía. Ahora la fija con `POST /context/week`
(`tests/browser/programacion-intermedia.visual.mjs`).

**El hallazgo que justificó el frente:** ese rojo permanente llevaba desde el 2026-08-07 **tapando
los dos cambios de arriba**. Una alarma que suena siempre no solo se ignora — oculta las alarmas de
verdad que suenan debajo.

### La prueba sabe fallar, y la primera mutación enseñó algo más

La recaptura se acompañó de una mutación **ejecutada**, no descrita. Y la primera que se probó
descubrió un matiz que conviene dejar escrito antes de que muerda a alguien.

Añadir una letra a «Seleccionar visibles» hizo **fallar 1180×820 (1649 px) y PASAR 1440×900**. No
es que el golden ancho sea ciego: es que en ese ancho ese botón queda **último de su fila**, así
que ensancharlo no desplaza nada detrás y solo cambian su borde y la letra añadida — por debajo del
`maxDiffPixels: 100`. A 1180×820 el mismo botón va en la segunda fila y arrastra todo lo que le
sigue.

**Lección para quien diseñe la próxima mutación de este golden:** un cambio que se apoya en el
reflujo de la fila no prueba nada en el viewport donde ese elemento no reflúe. La mutación válida
se hizo sobre una etiqueta de la leyenda («RC inicio vencido»), que arrastra su fila en **ambos**
anchos, y ahí sí fallaron los dos escenarios.

Un golden que ya no detecta nada es peor que el que había — y uno que solo detecta en un viewport
lo parece sin serlo.

### Y una mutación que vale más que las dos anteriores: ¿el arreglo es la causa?

Cambiar un texto y ver el rojo solo demuestra que la comparación de píxeles funciona, cosa que ya
se sabía. Lo que de verdad había que comprobar es que **este golden vigila la pantalla que dice
vigilar**, porque su problema nunca fue fallar: fue ser inútil mientras el ruido de la semana lo
tapaba.

Así que se quitó la llamada a `fijarSemanaDelEscenario` y se volvió a correr. Resultado: **falla
otra vez, con el rojo únicamente en la esquina del selector de semana** —369 px, y ni un píxel en
el resto de la pantalla—. Es la reproducción exacta del fallo original.

Eso es lo que cierra el argumento: si sin la llamada hubiera seguido en verde, la semana la estaría
fijando **otra cosa** y este arreglo no sería la causa de nada. Con la llamada puesta, `2 passed`.

## Recaptura del 2026-08-24 — frente `habilitacion-en-una-columna`

- **Firmas nuevas:**
  - `programacion-intermedia-dark-1180x820.png` → `90b500fe0e9b532cde22af3a4ebe8e536127a7d2b52e44b497406b3fdf4fd719`
  - `programacion-intermedia-dark-1440x900.png` → `2739424d804c9dc5a9325c301a69bf5799ced3e2547c7824917d39e6c7ed8800`
- **Firmas que sustituyen:** `e679df0c…` y `74843929…`, tal como estaban en `programacion-intermedia.json`
  antes de este cambio. Este documento no tenía registrada una recaptura intermedia entre el
  2026-08-11 y hoy — el sha real de `programacion-intermedia.json` no coincidía con la firma
  `21f4be3d…` de la sección anterior. Es una deuda de constancia preexistente, ajena a este frente,
  que se deja anotada aquí en vez de corregida en silencio.
- **Sobre qué sha se recapturó:** `09dd6443` (base del plan), tras aplicar los 15 commits del frente
  `habilitacion-en-una-columna` (spec: `docs/superpowers/specs/2026-08-20-habilitacion-en-una-columna-design.md`).
- **Quién lo aprobó:** Felipe, viendo las capturas de la tabla, el globo y la tarjeta móvil enviadas
  como archivos en el chat, y confirmando explícitamente («Si») antes de la regeneración.

### Qué cambió

Es un rediseño deliberado, no una deriva accidental: las siete columnas de restricción (Diseños y
Especificaciones, Procedimiento Constructivo, Modelación BIM, Materiales, Mano de Obra, Equipos y
Herramienta, Actividad Predecesora) y la columna `% Liberación` desaparecen de la tabla. En su lugar
queda una sola columna «Habilitación» de 130 px con hasta siete cuadritos por fila (relleno + visto +
tachado para N/A, sin depender solo del color), con embudo de filtro propio en la cabecera. El
`% Liberación` se muda al globo que se abre al hacer clic en la celda. Verificado con la sonda de
ancho: la tabla pasa de pedir 1490 px a caber en 1100 sin scroll horizontal.

### Por qué no hace falta una mutación de golden esta vez

La mutación deliberada («¿el golden detecta un cambio real?») ya se hizo en la recaptura del
2026-08-11 y sigue siendo válida como comprobación del mecanismo: nada de esta obra tocó
`fijarSemanaDelEscenario` ni la forma en que el test controla la semana. Lo que cambió es la
superficie que el golden fotografía, no la fontanería que lo hace estable.
