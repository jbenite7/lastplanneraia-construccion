<!-- cas:cita-textual — registro de validación: cita comandos y salidas tal como se midieron -->
# Validación en navegador — frente ci-en-verde (D-GAC-3)

## 2026-08-12 · Chips de la leyenda de Programa General, valor computado

Re-medición hecha por la sesión ejecutora del frente `auditoria` (cfa7efc4), por encargo de la
coordinadora: la medición original de la sesión dd520d00 quedó solo en sus decisiones y esa sesión
murió sin escribirla aquí.

- **Sha medido:** `0e45ba1d` (árbol servido por el contenedor local; `git status --porcelain`
  limpio en `public/css/`, `views/` y `src/` al momento de medir). `65c44435` —el sha del cierre
  de F-0— es ancestro de este árbol.
- **Entorno:** contenedor local (`docker compose`, servicio `app`, `http://localhost:8081`),
  sesión por la puerta de servicio `/dev/entrar?u=test.A&p=Da%20Porto`, ruta `/programa-general`,
  viewport 1180×820, `html.aia-theme-dark`.

### Resultado

- **7 elementos `.pdc-legend-item` encontrados** — no cero, así que no hay ambigüedad entre
  «no aplica» y «no lo encontraste».
- En los 7, el valor **computado** de `white-space`, `overflow-wrap` y `word-break` es **`normal`**.
- `overflow-wrap` y `word-break` ganan **sin ningún `!important`**: el barrido de reglas (bajando a
  los `@import`, que es donde un barrido anterior se rompió) no encuentra ninguna declaración
  `!important` de esas dos propiedades sobre el chip. La decisión D-GAC-3 —medir valores, no
  mecanismo— se sostiene para ellas.
- **Matiz que el enunciado del encargo no traía:** `white-space` computa `normal`, pero **sí**
  existen dos declaraciones `normal !important` que lo fijan (`public/css/buttons.css:80` y
  `:979`), ambas **dentro de `@layer components`** (el archivo entero está envuelto en esa capa,
  líneas 1–1227), que es exactamente lo que D-GAC-1 permite. Sin ese `!important` compiten reglas
  `nowrap` del mismo archivo (`.pdc-legend-item` y `.pdc-legend.pg-legend .pdc-legend-item`), así
  que para `white-space` el `!important` no es inerte. Se reporta lo medido, no lo enunciado.
- Dato conocido y no reabierto: `display` computa `flex` (no el `inline-flex` declarado) porque el
  padre `#pgLegend` es `display: flex` y `inline-flex` se blockifica — ya perseguido y disuelto en
  el `goal.md` del frente. D-GAC-4 queda en su cola; aquí no se toca.
