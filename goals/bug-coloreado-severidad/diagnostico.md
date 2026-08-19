# Diagnóstico — el coloreado en cascada por severidad en /programacion-intermedia

**Frente:** `bug-coloreado-severidad` · **Alimenta:** DS-F0 · **Spec:**
`docs/superpowers/specs/2026-08-19-bug-coloreado-severidad-design.md`

**Medido el 2026-08-18 sobre `6abe2436`**, a 1180×820, tema `dark`, sesión real por la puerta de
servicio (`/dev/entrar?u=test.R`, proyecto *Da Porto*), con los nueve estados sembrados por el mismo
mock que usa `tests/browser/programacion-intermedia.visual.mjs`. Capturas a `deviceScaleFactor: 1`
—sin reescalar, que es la trampa que ya costó una vuelta en `severidad-runtime`— y **color computado
contra color computado**, nunca contra el declarado en la hoja (la trampa de `contadores-cero`).

**Cero cambios en producto.** Lo único escrito es este directorio.

---

## Veredicto

La spec ofrecía tres respuestas. **No es una sola: son dos, y hay una tercera que existe pero no
causa el síntoma.** Por orden de peso:

| | Respuesta | ¿Aplica? |
|---|---|---|
| **Principal** | **(2) No hay escala que respetar** — hueco de contrato | **Sí.** El fondo de la tabla lo pinta una escalera que **ningún contrato gobierna**, y su asignación contradice el único contrato que sí existe en 3 de 8 estados |
| **Agravante** | **(3) Funciona y se percibe mal** — contraste | **Sí, y medido.** Peldaños consecutivos a ≤1,33:1; **tres pares bit-idénticos**; 8 entradas de leyenda → **5 colores** |
| **Aparte** | **(1) Es un bug** — el render no respeta la escala | **Sí, hay uno real** (`states-feedback.css:162` es letra muerta), **pero no produce este síntoma**: arreglarlo empeoraría la cascada |

**En una línea:** el usuario tiene razón en que la cascada no está pasando, y no está pasando porque
**nunca se declaró**. Lo que hay son dos sistemas de color distintos pintando la misma fila, ninguno
de los dos ordenado por severidad de punta a punta.

---

## Tanda 1 — ¿Dónde está declarado el orden de severidad?

### `GLOSARIO.md` no define ninguna severidad

`grep -i 'severidad|severity|crítico|sin problema' GLOSARIO.md` → **0 coincidencias** (136 líneas).
Tampoco existe en el módulo un estado llamado «Sin problema»: los extremos que el usuario nombra
**no son vocabulario del repo**. Sin autoridad local, «el orden correcto» sería una suposición —y la
spec pedía decirlo, así que queda dicho.

### Sí hay un eje de severidad, en el design system

`docs/design-system/state-semantics.json` declara `dimensions.severity = [none, low, medium, high]`
y cuatro niveles (`neutral`, `healthy`, `attention`, `urgent`). Los ocho estados de
`programacion-intermedia` tienen nivel asignado allí. **Ésta es la única autoridad ejecutable.**

### Pero la paleta que pinta el fondo es NOMINAL, no ordinal — y a propósito

`tests/design-system/state-tint-ladder.test.mjs:9-27` lo documenta con la medición que lo decidió:
hubo una versión con tres pasos por familia y, medida en navegador, **la separación máxima entre dos
pasos consecutivos era 1,012:1 de contraste y ΔE-OK 0,0168** —bajo el umbral de percepción—. Se
colapsó a **ocho anclas, una por matiz**. La regla que quedó, textual en
`state-semantics.json.translationRules`:

> «La paleta publica un solo tinte por matiz —sobre el canvas oscuro no hay eje de intensidad que
> separe dos escalones».

Y la que reparte los ejes:

> «El nivel se pinta en el acento y el matiz en el fondo».

**Es decir: la cascada por severidad en el fondo se diseñó fuera del producto el 2026-07-28.** El
fondo codifica *qué estado es*, no *cuán grave es*.

### Hay una segunda autoridad, más vieja, y contradice a la primera

`docs/matriz-severidad-cajon-contextual-lps.md` (2026-05-22) sí declara una severidad por estado de
PI, con orden de decisión global y colores. Su alcance es el **Cajón Contextual**, su paleta es
**clara** (`#fafafa`, `#d5e5db`, `#fff8e1`), y **discrepa del contrato vigente** en al menos cuatro
estados de PI:

| Estado PI | `matriz-severidad…md` | `state-semantics.json` |
|---|---|---|
| `blocked-overdue` | `attention` | `urgent` |
| `blocked-due` | `critical` si RC, `attention` si no | `attention` |
| `alert-1-week` | `attention` | `urgent` |
| `alert-4-6-weeks` | `normal` preventivo | `attention` |

**Dos documentos, dos respuestas.** Cualquier decisión de DS-F1 tiene que derogar uno de los dos
explícitamente; hoy conviven.

---

## Tanda 2 — Qué pinta la pantalla, medido

La misma fila la pintan **dos sistemas independientes**:

- el **chip** de la celda «estado operativo» → matiz nominal (`data-aia-hue`, 8 colores)
- **todas las demás celdas de la fila** → escalera ordinal `--ds-cell-state-*`
  (`crítico > riesgo > atención > ok > neutral`), mapeada a mano en
  **`public/css/styles.css:3664-3725`**

Color computado (`goals/bug-coloreado-severidad/evidence/medicion-computada.json`):

| Estado | nivel del contrato | **fondo de la FILA** | **fondo del CHIP** |
|---|---|---|---|
| RC inicio vencido (`blocked-overdue-critical`) | urgent (high/now) | `#431414` crítico | `#431414` rojo |
| Inicio vencido (`blocked-overdue`) | urgent (high/now) | `#452501` riesgo | `#452a0d` naranja |
| Inicio por Habilitar (`blocked-due`) | attention | **`#3a3a0f`** atención | `#33204a` violeta |
| **Alistamiento Urgente** (`alert-1-week`) | **urgent (high/now)** | **`#3a3a0f`** atención | `#3a3a0f` ámbar |
| **Alistamiento en Riesgo** (`alert-2-3-weeks`) | attention | **`#3a3a0f`** atención | `#134841` teal |
| **En Ejecución Pendiente** (`execution-blocked`) | **attention** | **`#173d26`** ok (verde) | `#17334f` azul |
| Listo para Comprometer (`liberated-control`) | healthy | **`#173d26`** ok (verde) | `#173d26` verde |
| Alistamiento Pendiente (`alert-4-6-weeks`) | **attention** | **`#1b231e`** neutral | `#2b2f2d` neutral |
| Control (`neutral`, sin clasificar) | neutral | **`#1b231e`** neutral | `#2b2f2d` neutral |

### 2.1 · La escalera de la fila contradice el nivel declarado en 3 de 8 estados

- **`alert-1-week` («Alistamiento Urgente»)** es `urgent / high / now` en el contrato y la fila lo
  pinta en el peldaño **`atención`**, exactamente el mismo color que `alert-2-3-weeks` («en Riesgo»,
  `attention`) y que `blocked-due`. **Tres estados, un color.**
- **`execution-blocked` («En Ejecución Pendiente»)** es `attention` en el contrato —ratificado por el
  propietario del producto el 2026-08-03, con nota en el propio JSON— y la fila lo pinta en el
  peldaño **`ok`**: el verde de «controlado», idéntico a «Listo para Comprometer». La leyenda lo
  clasifica **P1 – Resolver hoy** y lo pinta verde.
- **`alert-4-6-weeks`** es `attention` y la fila lo pinta **`neutral`**, idéntico a la fila sin
  clasificar.

**Ningún test cubre este mapeo.** `grep -rl 'cell-state-riesgo|pi-state-alert-1-week' tests/ scripts/`
→ 0 archivos. La única mención en todo el repo está en una spec de agosto.

### 2.2 · Donde la escalera sí se respeta, no se percibe

Contraste entre peldaños consecutivos, en el orden de severidad que declara el contrato:

```
RC inicio vencido      vs Inicio vencido        1,126:1
Inicio vencido         vs Inicio por Habilitar  1,180:1
Inicio por Habilitar   vs Alistamiento Urgente  1,000:1   ← idénticos
Alistamiento Urgente   vs Alistamiento Riesgo   1,000:1   ← idénticos
Alistamiento Riesgo    vs En Ejecución Pdte.    1,035:1
En Ejecución Pdte.     vs Alistamiento Pdte.    1,326:1
Alistamiento Pdte.     vs Listo p/ Comprometer  1,326:1
Listo p/ Comprometer   vs Control               1,326:1
```

**El máximo de toda la escalera es 1,33:1.** El propio repo ya declaró que 1,012:1 «es un solo
color»; 1,33:1 no está lejos. No hay eje de intensidad porque la paleta oscura no lo tiene —lo que
`tokens.css` y `state-tint-ladder.test.mjs` dicen literalmente—, así que la «cascada» son cinco
tonos casi equiluminantes que se distinguen por tono, no por gravedad.

### 2.3 · La leyenda es donde más se ve

`evidence/leyenda-guia-operativa-1180x820-dark.png` y `evidence/medicion-leyenda.json`: la Guía
Operativa usa los mismos tokens de fila, así que **sus 8 entradas de estado pintan 5 colores**, y en
un cuadradito de 1 rem los cinco son prácticamente el mismo gris oscuro. La novena entrada
—«Pdto. Constructivo y Modelo BIM»— es **`#fef3c7`, una crema clara sobre tema oscuro**, y es con
diferencia el elemento más llamativo del modal: la única entrada que *no* es un estado grita más que
«RC inicio vencido».

---

## Hallazgo (1): sí hay un bug, y es otro

**`public/css/design-system/components/states-feedback.css:162` es letra muerta.**

La excepción decidida el 2026-08-11 (commit `82832685`) dice que el matiz desempata en todos los
niveles **menos** en el crítico:

```css
[data-aia-hue][data-aia-severity="high"][data-aia-urgency="now"] {
  background: var(--ds-color-state-critical-bg);
}
```

**No se aplica nunca en un módulo.** Medido con `CSS.getMatchedStylesForNode` sobre el chip real
(`evidence/sonda-reglas-chip.mjs`): `blocked-overdue` es `high/now` y rinde **`#452a0d`** (naranja),
no `#431414` (crítico).

**Causa raíz — `public/css/design-system/adapters/legacy-bridge.css:104-142`.** El puente reafirma en
la capa `legacy-overrides` (la última) las cuatro reglas de nivel y las ocho de matiz, todas con
`:where(...)`, es decir **especificidad 0,0,0**; dentro de esa capa decide el orden de fuente y el
matiz va después. La excepción crítica **nunca se copió al puente**. Como `legacy-overrides` va
después de `components`, el puente gana siempre y la excepción de la capa canónica no llega a
evaluarse. El comentario del puente («el matiz —que es la identidad del estado— gana el fondo») es
anterior a la excepción y nadie lo revisó al añadirla.

**Reproducción:** `node goals/bug-coloreado-severidad/evidence/sonda-reglas-chip.mjs` — imprime las
reglas emparejadas por el motor para el chip de `alert-1-week` y `blocked-overdue`.

**Dos cosas que hay que decir antes de tocarlo, y por eso no se toca aquí:**

1. `tests/browser/ops-state-chip-hue.mjs` **asierta lo contrario**: exige que el fondo del chip sea
   el tinte de su matiz. Está en verde precisamente porque la excepción es inerte. Arreglar el
   puente pondría ese test en rojo. Cambiar lo que mide una prueba es lista de bloqueo incondicional.
2. Arreglarlo **empeoraría** el síntoma del usuario: en PI hay **tres** estados `high/now`
   (`blocked-overdue-critical`, `blocked-overdue`, `alert-1-week`), así que los tres pasarían a
   pintar el mismo `#431414` y el chip perdería dos colores más.

---

## Cómo reproducir todo esto

```bash
docker compose up -d db app
node goals/bug-coloreado-severidad/evidence/sonda-severidad.mjs   # colores computados + captura
node goals/bug-coloreado-severidad/evidence/sonda-leyenda.mjs     # leyenda + captura
node goals/bug-coloreado-severidad/evidence/sonda-reglas-chip.mjs # qué regla gana el chip
```

Las tres son de **solo lectura**: no escriben en el producto, no tocan goldens y no viven en
`tests/`. Escriben únicamente en `goals/bug-coloreado-severidad/evidence/`.

## Evidencia

- `evidence/medicion-computada.json` — los nueve estados, fondo de fila y de chip, y los 36 pares
- `evidence/medicion-leyenda.json` — las nueve entradas de leyenda y sus seis colores
- `evidence/pi-nueve-estados-1180x820-dark.png` — la tabla, escala real
- `evidence/leyenda-guia-operativa-1180x820-dark.png` — la Guía Operativa, escala real
- `evidence/sonda-*.mjs` — las tres sondas

## Lo que queda para decidir (no se decide aquí)

1. **¿Debe haber cascada por severidad en el fondo?** Hoy el diseño dice que no (el fondo es
   identidad, el nivel va en el acento) y la tabla hace un tercer cosa distinta de las dos. Es
   decisión de negocio → **DS-F1**.
2. **Qué autoridad manda**: `state-semantics.json` o `matriz-severidad-cajon-contextual-lps.md`.
   Una de las dos hay que derogar.
3. **El mapeo `styles.css:3664-3725`** no tiene contrato ni guard. Contradiga o no al contrato,
   hoy nadie lo vigila.
4. **`states-feedback.css:162` vs `legacy-bridge.css:104`** — decidir si la excepción crítica se
   quiere de verdad, sabiendo que hoy no existe y que activarla colapsa tres chips de PI en uno.
5. **`#fef3c7` en la leyenda** (`hot.js:2857`): hex claro embebido con `var(--aia-warning-soft-bg,
   #fef3c7)` de reserva, sobre tema oscuro. Fuera del alcance de este frente.

## Archivos de este goal

- [[goal]] · [[memoria/goals/estado]]
