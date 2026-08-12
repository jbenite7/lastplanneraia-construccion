# Atribución del delta de `cssGzipBytes`: baseline 0.3.3 → medición actual

- **Frente:** `css-presupuesto-57kb` (decisión del usuario D-GAC-5(b), 2026-08-12)
- **Fecha:** 2026-08-12
- **Pregunta:** ¿de dónde salen los ~57 KB de CSS gzip por encima del baseline 0.3.3
  (136.933 B, aprobado 2026-07-17)? ¿Cuánto es alta legítima, cuánto crecimiento real,
  y hay alguna regresión?

## Fuentes, con sha

| Lado | Fuente | Sha / identidad | `cssGzipBytes` |
|---|---|---|---|
| Baseline | `docs/design-system/runtime-measurements/0.3.3-retrospective.json` (`provenance.assets`, 41 hojas CSS) | `sourceTreeHash 7fb389c1…`, registrado 2026-07-14, aprobado 2026-07-17 | 136.933 |
| Actual | muestras `sample-1/2/3` del artefacto `design-system-failure-evidence`, corrida de CI `31563364701`→`31566518358` | `sourceRef c014874c5db7e182879251843e41670d0565bb0e` | 194.553 |

Las tres muestras de la corrida son **idénticas** en total y en inventario por hoja
(verificado programáticamente: `cssmap(sample-2) == cssmap(sample-1)`, ídem sample-3).
Ambas listas suman **exactamente** su métrica declarada, así que la descomposición de abajo
cierra con **error 0 %** (la condición de hecho pedía ±1 %).

> **Corrección a la premisa del encargo:** el goal decía que el artefacto de CI guarda solo
> `assetInventorySha256` y no la lista de activos. Es falso para las **muestras** (`sample-N.json`):
> traen `provenance.assets` completo (path, rawBytes, gzipBytes, sha256 por activo). Lo que no
> trae lista es el **agregado**. Por eso no hizo falta montar runtime nuevo: la medición
> instrumentada con sha ya existía.

## Descomposición del delta (+57.620 B = 194.553 − 136.933)

### Altas: hojas que no existían en el baseline → **+17.367 B**

| gzip B | Hoja | Clasificación |
|---:|---|---|
| 6.040 | `/css/design-system/adapters/shell-sidebar.css` | Alta legítima — sidebar canónico global (`3e2e2963`, `4a6dd143`) |
| 4.109 | `/css/design-system/core.css` | Alta legítima — núcleo del design system |
| 1.720 | `/css/design-system/components/table-filter-trigger.css` | Alta legítima — componente nuevo |
| 1.565 | `/runtime/css/aia-design-system.css` | **Mudanza**, no alta: sustituye a `/css/aia-design-system.css` (baja de 2.368 B); neto −803 B |
| 1.461 | `/public/css/design-system/adapters/datatables.css` | Alta legítima con **ruta anómala** — la única `@import` con prefijo `/public/`, ya corregida en `ef4780b0` (Δ +1 B); en la próxima medición aparecerá como `/css/design-system/adapters/datatables.css` |
| 1.330 | `/css/design-system/components/toolbar-controls.css` | Alta legítima — control compacto de toolbar (`03434f64`) |
| 724 | `/css/design-system/components/ops-state-chip.css` | Alta legítima — chips de estado |
| 418 | `/css/design-system/components/ht-empty-state.css` | Alta legítima — estado vacío de Handsontable |

### Bajas: hojas del baseline que ya no se piden → **−8.314 B**

| gzip B | Hoja | Clasificación |
|---:|---|---|
| −5.191 | `/public/css/navbar.css` | Baja legítima — sustituida por el shell/sidebar canónico |
| −2.368 | `/css/aia-design-system.css` | Mudanza a `/runtime/css/…` (ver arriba) |
| −755 | `/css/design-system/adapters/semi-auto-review.css` | Baja legítima — eliminación del PDC v1 (2026-08-04) |

### Crecimiento de hojas existentes (mismo path) → **+48.567 B**

Las 15 que más crecieron (el resto son deltas ≤ ±260 B, mayormente ruido o ajustes menores):

| Δ gzip B | Hoja | antes → ahora | Traza |
|---:|---|---|---|
| +9.072 | `adapters/legacy-bridge.css` | 269 → 9.341 | Reubicación del puente vivo desde `styles.css` (`f8b5730a`) + variante B de bordes, fixes de chips, retirada PDC v1. **Sin duplicación**: las copias de `styles.css` se retiraron en `10ed5846` y `85d68b57` (verificado: el bloque «UNLAYERED OVERRIDE BRIDGE» ya no existe en `styles.css`) |
| +8.646 | `/css/tokens.css` | 2.948 → 11.594 | Expansión del sistema de tokens (`66facd23`, `a6d5d01a`, `4437fcfa`…) |
| +4.631 | `/css/programa-general.css` | 2.129 → 6.760 | Chips de PG, alineación con referencia aprobada (`47dda844`, `20f08dd2`…) |
| +4.130 | `components/navigation.css` | 621 → 4.751 | Colapsado del sidebar como primitiva canónica (`4a6dd143`), marca (`4437fcfa`), a11y |
| +3.722 | `/css/handsontable-module.css` | 5.972 → 9.694 | Cabeceras, foco, reservas de var() (`66facd23`, `6790a3ae`…) |
| +3.110 | `adapters/lps-drawer.css` | 227 → 3.337 | Migración de estilos inline del Cajón LPS al design system (`a8d19ccb`) |
| +1.891 | `/css/handsontable-header-global.css` | 907 → 2.798 | Encabezado sobrio |
| +1.678 | `/css/design-system/foundation.css` | 243 → 1.921 | Fundaciones del sistema |
| +1.510 | `/css/buttons.css` | 5.986 → 7.496 | Botones compactos, área de clic 24px |
| +1.504 | `/public/vendor/jquery-ui.css` | 5.903 → 7.407 | **Subida deliberada de dependencia** (`7c15c67b`: las 22 librerías de vendor entran bajo gestión); toastr bajó −198 B en el mismo commit |
| +1.206 | `components/states-feedback.css` | 535 → 1.741 | Estados y feedback |
| +1.151 | `adapters/programa-general-handsontable.css` | 690 → 1.841 | Adaptador PG |
| +1.068 | `components/bi-figure.css` | 1.030 → 2.098 | Figuras BI |
| +1.015 | `adapters/handsontable.css` | 469 → 1.484 | Adaptador HT |
| +1.015 | `vendor-datatables-legacy.css` | 235 → 1.250 | Partición de DataTables legado |

**Suma de control:** +17.367 − 8.314 + 48.567 = **+57.620 = 194.553 − 136.933** (exacto).

### Nota sobre el rango 194.553–195.402

Los 194.553 B corresponden a `c014874c` (corrida atribuida aquí). Los 195.401/195.402 B son
mediciones posteriores sobre `0e45ba1d`/`ef4780b0` (ver `decisiones/gates-al-ci-ejecutor.md` § D-5):
~0,85 KB de deriva adicional dentro de estas mismas hojas entre ambos shas. Incluso contra 195.402,
esta atribución cubre el 98,5 % del delta (±1 % cumplido).

## Conclusión

1. **No hay señal de regresión.** El 100 % del delta traza a trabajo con nombre y propósito
   posterior al 2026-07-17: migración del design system (tokens, componentes nuevos, adaptadores,
   sidebar canónico), retirada del PDC v1 y una subida deliberada de dependencias vendor. Además
   `duplicateRequestCount` **mejoró** de 3 (baseline) a 0.
2. La única anomalía real encontrada en el camino (la `@import` con prefijo `/public/`) ya estaba
   corregida (`ef4780b0`, autorizada por el usuario) y su efecto en bytes era +1 B.
3. La composición del crecimiento es coherente con la estrategia declarada: mover estilos inline y
   puentes sin capa hacia hojas del design system **traslada** peso hacia `adapters/*` y
   `components/*`; no lo inventa.

## Recomendación accionable para el baseline 0.3.4

- **Crear el baseline 0.3.4 con una medición propia y fresca** (no editar el 0.3.3, que está
  anclado por sha256 a su retrospectiva) sobre un sha ≥ `ef4780b0`, en el runtime aislado de CI y
  con imagen reconstruida (`up --build`; sin eso se mide la imagen vieja — trampa ya medida).
  Cifra esperada: ~195,4 KB ± ruido de gzip.
- **Re-aprobar `adapterAssets` con las 8 rutas canónicas** (entran `shell-sidebar.css` y
  `datatables.css`, sale `semi-auto-review.css`).
- **Adjuntar esta atribución como justificación de la aprobación**: el riesgo que D-GAC-5(a)
  temía —hornear una regresión como normal— queda descartado por esta descomposición.
- **Deuda diferible, no bloqueante del 0.3.4** (candidatas si algún día se quiere dieta):
  `styles.css` sigue siendo la hoja más pesada (31,2 KB gzip) y monolítica; `tokens.css` (11,6 KB)
  y `legacy-bridge.css` (9,3 KB) son los dos crecimientos más grandes y ambos son capas de
  transición que deberían encoger conforme avance la migración. Vigilarlas por hoja en futuras
  mediciones costaría poco: la lista por activo ya viaja en cada `sample-N.json`.

## Reproducción

```bash
gh run download 31566518358 -n design-system-failure-evidence   # sample-1/2/3 con provenance.assets
# comparar provenance.assets (type css) contra docs/design-system/runtime-measurements/0.3.3-retrospective.json
```
