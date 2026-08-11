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
