---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-03
areas: [design-system]
fuente: sesion
resumen: "El ancla por firma (selector+token) de state-token-exceptions.json es estable ante inserciones, salvo el caso de selector+token duplicado: una regla nueva metida ENTRE las dos copias corre el occurrence declarado y el error que sale no dice por que"
---
`scripts/design-system/state-token-locator.mjs` (Task 12-bis) reemplazo el ancla por
numero de linea de `state-token-exceptions.json` por selector exacto + token, con
`occurrence` (1-based) solo para el unico caso de selector+token duplicado literal en
el archivo: `.ps-dropdown-item.is-active` + `--ds-color-state-info-bg`, dos copias en
`public/css/programacion-semanal.css`. `resolveCssException` localiza los atomos cuyo
selector aplanado coincide EXACTO con el declarado, filtra los que contienen el token,
y toma el n-esimo de esos por orden de aparicion en el archivo.

Esa firma es estable ante una insercion en cualquier punto **salvo uno**: si alguien
mete una tercera regla `.ps-dropdown-item.is-active { ... --ds-color-state-info-bg ... }`
**entre** las dos copias existentes, el `occurrence: 2` declarado para la segunda copia
original pasa a resolver contra el bloque nuevo, no contra el que describia. El gate
sigue rompiendo -no hay pase en falso: la regla nueva, sin declarar, aparece como
`sinDeclarar` en `state-token-pairing.test.mjs`- pero el mensaje que ve quien lo pisa es
un "uso sin declarar" sobre la regla intermedia, no algo que diga "tu `occurrence`
apunta al bloque equivocado". Toca leer las tres copias a mano para entender que paso.

**Why:** `occurrence` cuenta posicion RELATIVA dentro del conjunto de bloques que
comparten selector+token, no una identidad propia del bloque. Es exactamente el mismo
problema de fondo que la linea absoluta -un numero que depende de que mas hay
alrededor-, solo que acotado al subconjunto de duplicados en vez de a todo el archivo.
La diferencia con el bug original: antes CUALQUIER insercion en el archivo rompia
TODAS las entradas; ahora solo una insercion ENTRE duplicados existentes desalinea las
entradas de ESE selector+token, y el gate detecta la discrepancia igual, solo que el
mensaje no apunta a la causa.

**How to apply:** si se toca `.ps-dropdown-item.is-active` en
`public/css/programacion-semanal.css` (por ejemplo el Task 13) y aparece un tercer
bloque con el mismo selector y el mismo token, revisar a mano el orden de las tres
copias contra los `occurrence: 1` / `occurrence: 2` declarados antes de asumir que el
error del gate es sobre la regla nueva. Consolidar las dos copias duplicadas en una
sola regla -ya sugerido como pendiente en la propia entrada del inventario- elimina el
riesgo de raiz, porque deja de haber `occurrence` que desalinear.

Emparentada con [[guard-valida-declaracion-contra-si-misma]]: la primera version del
inventario media contra una linea que cualquier insercion desalineaba; esta es la
version residual del mismo defecto, acotada al unico caso de duplicado literal.
