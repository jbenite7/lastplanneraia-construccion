---
capa: fuente
tipo: evidencia
estado: vigente
fecha: 2026-08-12
areas: [proceso]
fuente: goals/ci-en-verde/validation-log.md
resumen: Validación en navegador — frente ci-en-verde (D-GAC-3)
---

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

## 2026-08-12 · Cierre del frente publicado y desplegado a pruebas

- **Publicación:** `3bc3a662` (baseline 0.3.4, D-GAC-5(b)) y `e40f113e` (decisiones) en
  `origin/main`; `git status -sb` sin `ahead/behind`.
- **Verificación local previa al push:** `npm run test:design-system:static` 8/8 (`EXIT=0`)
  con el stack Docker levantado. `npm run test:performance:lab` **verde** (`1 passed`,
  `EXIT=0`): el baseline 0.3.4 cierra el rojo de `runtime-budgets`.
- **CI (corrida 31619568581):** `design-system-static` verde; `design-system-runtime` en
  failure **solo** por los 18 goldens del carril visual (D-GAC-4, tolerado y en cola). Matiz
  operativo: el `&&` del job hace que `test:performance:lab` no llegue a correr en CI mientras
  el carril visual esté rojo — el verde de presupuestos quedó demostrado localmente.
- **Despliegue a pruebas** (autorizado por el usuario el 2026-08-12, solo pruebas):
  `prueba-lps` pasó de `5a337f3e` a `e40f113e` (14 commits, sin migraciones, sin drift).
  Respaldo previo `prueba-lps-predeploy-20260812-170514.tar.gz` (~706 MB) y SHA de rollback
  anotado. `composer install` con PHP 8.3 regeneró el autoloader. Smoke: `/` y `/login`
  HTTP 200 interno y externo, `/proyectos` 302 a login (protección intacta),
  `vendor-datatables-legacy.css` 200. `php_errorlog` sin entradas nuevas (las últimas son
  del 2026-07-30). Producción **no** se tocó.
