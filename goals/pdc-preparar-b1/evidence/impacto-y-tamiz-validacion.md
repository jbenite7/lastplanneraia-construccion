# Ola 1 · Frente B — Impacto al recargar el presupuesto + tamiz y cifras honestas · bitácora de validación

- **Fecha:** 2026-07-29
- **Goal:** [`goals/pdc-preparar-b1`](../goal.md) · fila **2** del [tablero de relevos](../estado-olas.md)
- **Specs:** [`impacto-reimport-presupuesto`](../../../docs/superpowers/specs/2026-07-29-impacto-reimport-presupuesto-design.md) ·
  [`tamiz-presupuesto`](../../../docs/superpowers/specs/2026-07-29-tamiz-presupuesto-design.md)
- **Plan:** [`2026-07-29-impacto-reimport-y-tamiz-presupuesto`](../../../docs/superpowers/plans/2026-07-29-impacto-reimport-y-tamiz-presupuesto.md)
- **Entorno:** worktree `pdc-presupuesto-impacto-tamiz`, base `1a75b19`. Stack de compose **propio**
  (`pdc-tamiz`: app `:8096`, MySQL `:3320`, volumen `pdc_tamiz_db_data`, copia de la base compartida),
  para no tocar la base de la sesión que trabaja el tablero de vencimientos en paralelo.

---

## 1 · La medición, y cómo se hizo

Todo lo que sigue se midió contra **Da Porto, proyecto 73, versión activa 376** (clase 1), por SQL
directo primero y luego contra la implementación:

| Hecho | Valor | Cómo se obtuvo |
|---|---|---|
| Costo total | $29.492.804.354 | `SUM(valor_total)` de `pdc_presupuesto_apu_insumos` |
| Actividades | 403 | `tipo_fila='actividad'` en `pdc_presupuesto_items` |
| **Apariciones en APU** | **820** | filas de `pdc_presupuesto_apu_insumos` |
| **Insumos distintos** | **396** | `COUNT(DISTINCT descripcion‖unidad)` |
| Actividades con `cantidad = 0` | **47** | `pdc_presupuesto_items` |
| Líneas de insumo que esas 47 arrastran | **102** | join items↔insumos |
| Insumos en cero por su propia línea de APU | **10** | actividad con cantidad > 0 y (`cantidad_total = 0` o `valor_unitario = 0`); todas con `cant_apu = 0` |
| Actividades con ≤ 2 insumos | 297 de 403 | **el criterio solo es inservible: 74 %** |
| Con unidad global (`SG`/`GL`) **y** ≤ 2 insumos | **57** | el conjunto de candidatos real |

Los seis números que la implementación publica se comprobaron uno a uno contra la base y luego en
pantalla (§5).

## 2 · Dos desviaciones del spec, decididas con el dueño del producto

### 2.1 · Los «46 insumos vacíos» del comité no son insumos

La regla literal del spec (`cantidad = 0` **o** `valor_unitario = 0` sobre insumos) da **112 filas /
79 insumos distintos**, no ~46. El desglose explica por qué:

- **47 actividades con cantidad cero** ← *este* es el 46 que recordaba Tomás. Arrastran **102** de
  esas 112 filas: el APU está valorado, pero nadie cuantificó la actividad.
- **10 líneas** de insumo en cero dentro de actividades que **sí** tienen cantidad (MOLDURA CHAFLÁN
  ×6, M.O. VACIADO DE PILAS, M.O. TRANSPORTE INTERNO AGREGADOS, AUXILIAR ADMINISTRATIVO, OPERADOR
  BOMBAS). Es el residuo real.
- **0** insumos con precio cero y cantidad distinta de cero.
- 102 + 10 = 112: la regla del spec queda cubierta entera, sin doble conteo ni fuga.

**Decisión (usuario, 2026-07-29): dos avisos separados.** Un único «112 insumos sin cantidad o sin
precio» sería un número verdadero que **señala mal**: el 91 % es consecuencia de otra cosa, y el 47 es
además el que le cuadra a quien recorrió el presupuesto a mano. Se descartó el aviso único.

### 2.2 · El umbral del «globalazo» lo pone el usuario en la vista

Se le presentó al usuario el reparto medido antes de fijar nada:

| Umbral (% del presupuesto) | Actividades marcadas |
|---|---|
| 1,00 % | 3 |
| 0,50 % | 5 |
| 0,25 % ($73.732.011) | 17 |
| 0,10 % | 34 |
| sin umbral | 57 |

Juicio sobre el listado del 0,25 %: **accionable, no ruidoso** — son «todo costo» reales de hidráulica
y eléctrica (RED CONTRA INCENDIO 548 M, SALIDAS ELÉCTRICAS APARTAMENTOS 259 M, DESAGÜES 253 M,
CABLEADO TELEFÓNICOS 122 M, ABASTOS, luminarias…), con **tres excepciones legítimas dentro** que hay
que saber leer y no «arreglar»: **IMPREVISTOS OBRA (890 M)**, **IMPREVISTOS TRANSPORTE PERSONAL
(381 M)** y, discutiblemente, **PRESUPUESTO AMBIENTAL (118 M)**: son globales con razón. Con 0,50 %
se caían CABLEADO y DESAGÜES, que sí hay que mirar; con 0,10 % entraba una cola de maderas y
bioseguridad por debajo de 30 M que parece inventario, no hallazgo.

**Decisión (usuario): «un valor fijo que pueda asignar el usuario en la vista».** El servidor **no
aplica umbral**: manda los 57 candidatos con su valor y el costo total, y la vista filtra. El control
vive en el visor y se recuerda por proyecto en `localStorage` (`pdc-umbral-global:<projectId>`).

**Supuesto declarado sobre el valor por defecto:** el control acepta pesos, y arranca en el
**0,25 % del costo de la versión activa redondeado al millón hacia abajo = $73.000.000**. Se eligió el
número redondo por legibilidad de una casilla que el usuario va a editar; el coste es que marca
**18** partidas en vez de 17 — entra LUMINARIAS PARA ZONAS COMUNES ($73,5 M), cualitativamente
idéntica a su vecina inmediata. Si se prefiere el 17 exacto, basta poner `73732011` en la casilla.

## 3 · Límite honesto: la agrupación de SINCO no se puede diferenciar

El spec pedía «insumos que cambian de agrupación». **No es implementable como está escrito, y se
implementó lo que sí es cierto:**

- La columna `Agrupacion` del export de SINCO **se lee y se descarta**: `PresupuestoExcelParser` no la
  persiste, y `pdc_presupuesto_apu_insumos` no tiene esa columna.
- La `agrupacion` que sí existe vive en `general_maestro_insumos`, indexada por
  `(descripcion_norm, unidad)`: es propiedad de la **identidad** del insumo, así que **no puede
  cambiar entre dos versiones del presupuesto**.
- Lo que sí se persiste y sí alimenta al motor de sugerencias es **`tipo_insumo`**, y es eso lo que se
  compara. La pantalla dice «insumos que **cambian de tipo**», no «de agrupación».
- Diferenciar la agrupación real exigiría una migración, que el spec excluye explícitamente
  («Sin migraciones. Es lectura pura sobre datos que ya están»).

Nota aparte: un insumo que en la versión nueva «se empaqueta de otra forma» cambia de descripción, o
sea de clave de fusión, y por tanto ya aparece como **uno que desaparece + uno nuevo**. Ese caso del
comité sí queda cubierto por las dos primeras cifras.

## 4 · Un defecto que solo el navegador podía encontrar

`PlanComprasImportController::preview()` enumera a mano las claves de su respuesta JSON. El servicio
calculaba el impacto correctamente —los 25 asserts del test PHP en verde— y **el controlador lo
tiraba**: el `POST /plan-compras/api/presupuesto/preview` devolvía 200 con 693 bytes y la pantalla no
mostraba nada. Se detectó al subir el archivo por la pantalla real, no por los tests. Corregido, con
comentario en el sitio.

Segundo hallazgo, en el helper de fixtures (no es un defecto del producto): `fromArray()` de
PhpSpreadsheet **omite las celdas cuyo valor es `== $nullValue`**, y en PHP `0 == null` es verdadero,
así que **los ceros escritos como `int` nunca llegaban al .xlsx** y el parser rechazaba la fila. Los
fixtures de este trabajo escriben los ceros como cadena `'0'`. El presupuesto real de Da Porto trae
ceros numéricos y se importa sin problema — de ahí sus 47 actividades y sus 10 líneas.

## 5 · Verificación en la app real

Contra `http://localhost:8096`, **desktop 1180×820 y solo dark** (no se trabajó ni se validó mobile,
tablet ni tema `linen`).

### 5.1 · Tamiz, contra Da Porto (`pdc-v2-tamiz-daporto.evidencia.mjs`, solo lectura)

En pantalla, verificado: `396 insumos distintos · 820 apariciones en APU` · **47** actividades sin
cantidad *(arrastran 102 líneas de insumo a cero)* · **10** insumos en cero por su propia línea de APU
· **18** actividades resueltas con una partida global, con el umbral por defecto en `73000000` y «de
57 candidatos con unidad global». Umbral a `0` → **57**; a `300000000` → **3**. **Recargar la página
conserva el umbral escrito.** Sin desbordamiento horizontal a 1180 px; sin `Fatal error`.
Captura: [`tamiz-daporto-avisos.png`](tamiz-daporto-avisos.png).

Ajuste de layout hecho tras mirarlo: el bloque cerrado le robaba 156 px a la grilla (que bajaba a
280 px, ocho filas) y abrir los tres avisos estiraba la página a 2 676 px. Con los tres resúmenes en
una fila y las tablas de detalle con su propio scroll a 40 vh: bloque cerrado **65 px**, grilla
**371 px**, página abierta **1 428 px**.

### 5.2 · «No bloquea», demostrado haciéndolo (`pdc-v2-tamiz.spec.mjs`, sandbox 990100)

Con los tres avisos desplegados y el umbral en 0: se importa, se visita el maestro, se **crea un
paquete**, se **asigna un insumo**, se **recalcula el plan** entero, y al volver al visor **los avisos
siguen ahí**. Es la condición de hecho 3 del spec, y no se razonó: se ejecutó.

### 5.3 · Impacto, con una versión «clase 0» construida a mano (`pdc-v2-impacto-daporto.evidencia.mjs`)

> **El clase 0 de Da Porto todavía no existe.** Esta verificación se hizo con una versión construida
> a mano a partir del clase 1 que está en la base. **Es una prueba del MECANISMO, no del caso real:**
> lo que pase de verdad en la transición clase 1 → clase 0 se comprueba cuando llegue el presupuesto,
> y hasta entonces este punto no se da por cumplido para el caso real.

El fixture `tests/browser/fixtures/pdc/daporto-clase0-simulado.xlsx` se generó exportando la versión
376 de la base a Excel con tres cambios deliberados, cada uno elegido para que una cifra dé
exactamente 1:

| Cambio | Cifra que ejercita | Resultado en pantalla |
|---|---|---|
| Se quita ALAMBRE NEGRO (DE AMARRAR), que **tiene** paquete (4 apariciones) | desaparece con paquete | **1** · $71.870.054 · «Suministro ACERO DE REFUERZO» |
| Se añade FIBRA ESTRUCTURAL CLASE 0, que no existía | nuevo sin paquete | **1** · $28.527.000 · paquete «—» |
| CONCRETO DE 3000PSI pasa de tipo `M` a `E`, y tiene paquete (12 apariciones) | cambia de tipo | **1** · $102.457.775 · «M → E» · «Suministro CONCRETO» |

**Valor afectado $202.854.828 = 28.527.000 + 71.870.054 + 102.457.775** (verificado). Ningún insumo
que se conserva aparece en los grupos. El texto previo al botón dice qué se conserva y qué queda «por
revisar a mano», y un assert exige que **nunca** contenga «reasign», «reagrup» ni «automátic».
Captura: [`impacto-daporto-clase0.png`](impacto-daporto-clase0.png).

**Cancelar no escribe nada:** antes y después de previsualizar, Da Porto sigue con
`versiones=1 activa=376` y `asignaciones=12`.

## 6 · Cifras honestas: qué se etiquetó y con qué criterio

Las dos palabras viven en un solo sitio (`pdc-app/src/lib/texto.ts`: `PALABRA_INSUMOS` y
`contarInsumos`): **«apariciones en APU»** y **«insumos distintos»**, con singular propio.

Criterio aplicado, para que sea una regla y no un capricho: se etiquetan las cifras **de inventario**
—cuántos hay en el presupuesto o en el catálogo—, donde las dos magnitudes pueden divergir; **no** las
confirmaciones de una acción («3 insumos asignados», «2 insumos omitidos»), que se refieren a lo que
el usuario acaba de seleccionar y no pueden diverger.

| Sitio | Antes | Ahora |
|---|---|---|
| Importar · columna del historial | «Insumos» (= 820) | «Aparic. APU» + tooltip que dice que no son insumos distintos |
| Importar · renglón de previsualización | «820 insumos» | «820 apariciones en APU» |
| Visor · cabecera | *(no había)* | «396 insumos distintos · 820 apariciones en APU» |
| Paquetes · pestaña | «Insumos» | «Insumos distintos» |
| Paquetes · meta de cada paquete | «11 insumos» | «11 insumos distintos» |
| Paquetes · sin destino | «Queda 1 insumo…» | «Queda 1 insumo distinto…» |
| Paquetes · propuesta del motor | «N insumos» | «N insumos distintos» |
| Asistente · quedan sin asignar | «N insumos» | «N insumos distintos» |
| Maestro · preview del import SINCO | «N insumos activos» | «N insumos distintos activos» |
| Paquetes · cobertura | ya decía «insumos distintos» | sin cambios (misma palabra) |

De paso se corrigió la concordancia de los rótulos nuevos: «**1** insumo nuevo sin paquete», no
«1 insumos nuevos» — que es exactamente el tropiezo de lectura que este trabajo viene a quitar.

El barrido con grep sobre `pdc-app/src/pages` y `src/components` no deja ninguna cifra de insumos
muda; lo que queda son variables, encabezados de columna («Insumo») y frases sin número.

## 7 · Comandos corridos y resultado (2026-07-29, en esta sesión)

```
docker compose exec -T app php tests/test_pdc_v2_impacto_reimport.php      rc=0  25 PASS  0 FAIL   (nuevo)
docker compose exec -T app php tests/test_pdc_v2_tamiz_presupuesto.php     rc=0  21 PASS  0 FAIL   (nuevo)
docker compose exec -T app php tests/test_pdc_v2_comparar.php              rc=0  22 PASS  0 FAIL
docker compose exec -T app php tests/test_pdc_v2_arbol.php                 rc=0  11 PASS  0 FAIL
docker compose exec -T app php tests/test_pdc_v2_import_flujo.php          rc=0  25 PASS  0 FAIL
docker compose exec -T app php tests/test_pdc_v2_import_parser.php         rc=0  31 PASS  0 FAIL
docker compose exec -T app php tests/test_pdc_v2_maestro.php               rc=0  37 PASS  0 FAIL
docker compose exec -T app php tests/test_pdc_v2_maestro_sinco_import.php  rc=0  13 PASS  0 FAIL
docker compose exec -T app php tests/test_pdc_v2_paquetes.php              rc=0  76 PASS  0 FAIL
docker compose exec -T app php tests/test_pdc_v2_paquetes_motor.php        rc=0  88 PASS  0 FAIL
docker compose exec -T app php tests/test_pdc_v2_contexto.php              rc=0  12 PASS  0 FAIL
docker compose exec -T app php tests/test_pdc_v2_amarre_cronograma.php     rc=0  17 PASS  0 FAIL
docker compose exec -T app php tests/test_pdc_v2_pasos_configurables.php   rc=0  80 PASS  0 FAIL
docker compose exec -T app vendor/bin/phpstan analyse -c phpstan-pdc.neon  [OK] No errors  (nivel 6)
cd pdc-app && npx vitest run                                              20 archivos, 294 tests, 0 fallos
cd pdc-app && npm run build                                               tsc + vite: sin errores
npx playwright test tests/browser/pdc-v2-*.spec.mjs …evidencia.mjs         13 passed (32,9 s)
```

Los 13 e2e: `pdc-v2-tamiz` (nuevo), `pdc-v2-tamiz-daporto.evidencia` (nuevo),
`pdc-v2-impacto-daporto.evidencia` (nuevo), `pdc-v2-import`, `pdc-v2-versionado`, `pdc-v2-comparar`
(×2), `pdc-v2-historial`, `pdc-v2-paquetes`, `pdc-v2-plan` (×2), `pdc-v2-maestro`, `pdc-v2-pasos`.
Ninguno `skipped`.

## 8 · Límites y pendientes

- **`test_pdc_v2_brecha_daporto` falla, y no es de este trabajo:** espera la versión 292 del proyecto
  73 y esta copia de la base tiene la 376. Comprobado guardando mis cambios y corriéndolo sobre el
  árbol limpio: **falla idéntico**. Es preexistente/ambiental.
- **El caso «clase 0» real sigue sin comprobar** (§5.3). Es lo único de las dos condiciones de hecho
  que no se puede cerrar hoy.
- **El umbral se guarda por navegador, no por proyecto en la base.** Si dos personas de la misma obra
  quieren el mismo umbral, cada una lo pone. Se eligió así porque el spec excluye migraciones; si el
  uso pide compartirlo, es una columna en la configuración del proyecto.
- **Los dos specs de evidencia (`*.evidencia.mjs`) dependen de que la base local tenga el clase 1 de
  Da Porto** cargado. No entran en ninguna suite automática por eso.
- Aviso para quien lea capturas de fallo de Playwright en este repo: la captura se toma **después** del
  `finally` que hace `logout`, así que un fallo cualquiera se «ve» como una caída de sesión en la
  pantalla de login. Cuesta un diagnóstico equivocado; mirar el log del contenedor, no la imagen.
