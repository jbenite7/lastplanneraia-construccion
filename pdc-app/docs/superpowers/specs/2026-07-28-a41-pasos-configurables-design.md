# A4.1 — Pasos del proceso de contratación configurables por proyecto

**Fecha:** 2026-07-28
**Fase:** A4.1 (cierra el último requisito escrito de A4)
**Grilleo:** `goals/pdc-a41-pasos-configurables/interview-result.json` (12 preguntas, 12 respondidas)
**Línea base:** `goals/pdc-a41-pasos-configurables/linea-base.txt` (2026-07-28 22:31:30 -05, HEAD 4b5332b)

## El problema

El roadmap exige para A4 «programación hacia atrás con duraciones de
`general_dias_procesos_contratacion` (**pasos configurables por proyecto** — no hardcodear variantes
Licify/Aprobación cliente)». Es lo único de A4 que quedó sin cumplir: hoy los pasos son una constante
de código, `PlanFechasService::PASOS`, siete entradas fijas atadas una a una a una columna del
catálogo legacy.

Esto no es teoría. Midiendo el histórico de AIA (`docs/pdca-automatizacion-plan-compras.md:93`):

- **Variante A** — Elab → **Licify** → Entrega → Recibo → Cuadros → Legal → Fab (JMC, Vetezco, Crysta2).
- **Variante B** — Elab → Entrega → Recibo → Cuadros → **Aprobación Cliente** → Legal → Fab (2 de 6 proyectos).

Licify se borró a propósito del sistema viejo en jun-2026 (`ROADMAP.md:264`) porque valía 0-2 días en
todos los proyectos, y se dropearon sus columnas. O sea: **A4.1 no es «devolver Licify»**, es que una
obra pueda armar su lista sin que nadie tenga que tocar código ni el catálogo legacy compartido.

## Decisiones (del grilleo, todas confirmadas por el usuario)

| # | Decisión | Elegida |
|---|---|---|
| 1 | Origen de los pasos | **Lista maestra de la empresa**; la obra elige, ordena y renombra. Nota del usuario: *«pueden ser más de 7»* |
| 2 | Obras ya calculadas | **Sin configuración = los siete de siempre**, sin escribir una fila |
| 3 | Días de un paso sin columna legacy | **Número fijo por obra**, igual para todos sus paquetes |
| 4 | Agregar un paso | **Alarga** el proceso; la fecha de arranque se corre hacia atrás |
| 5 | Apagar / reordenar | **Los tres** (agregar, reordenar, apagar), con aviso al apagar |
| 6 | Nombre propio de la obra | **Alias** opcional; por dentro sigue siendo el mismo paso |
| 7 | Paquetes sin desglose (provisionales) | **Días fijos aparte + pesos medidos re-normalizados** |
| 8 | Identidad del paso guardado | **Guardarla ahora**, no solo la posición |
| 9 | Permiso | **Reusar** `lps.paquetes_contratacion.reglas` (A3.3) |
| 10 | Al guardar | **Recalcular** y reportar cuántos paquetes cambiaron |
| 11 | Pantalla | Se abre **desde Plan de fechas**, sin pestaña permanente nueva |
| 12 | Fuera de alcance | Listas por modalidad, copiar entre obras, historial, editar el catálogo legacy — **registrados en el roadmap** |

### Tres decisiones derivadas que el grilleo no cubría

**(a) La identidad tiene que ser la clave única, no una columna decorativa.**
Hoy `pdc_plan_paso` es única por `(project_id, paquete_id, orden)` y `calcular()` hace upsert por esa
clave. Añadir una columna `paso_id` sin más no resuelve nada: si alguien mete un paso en la posición
3, el upsert seguiría escribiendo encima de la fila que hoy es «Cuadros comparativos», y la fecha real
que B1 haya colgado ahí pasaría a leerse como si fuera del paso nuevo, en silencio. Por eso la clave
única pasa a ser **`(project_id, paquete_id, paso_id)`** y `orden` queda como columna ordinaria que se
actualiza. Así la fila sigue al paso, no a la posición — que es exactamente lo que la respuesta 8
compró.

**(b) Qué significa «alarga» en un paquete sin desglose.** Las respuestas 4 y 7 dicen cosas distintas
y ambas son correctas en su ámbito:

- Paquete **con desglose real** (8 de los 11 de Da Porto): los siete números legacy son mediciones por
  paso. Agregar «Aprobación cliente: 15 días» suma 15 días de verdad → el proceso se alarga y se
  arranca antes. *(Respuesta 4.)*
- Paquete **provisional** (3 de 11): no hay medición por paso; hay una mediana del proceso **completo**
  para su tipo, que ya incluye el tiempo administrativo real de esas obras. Ahí la mediana es el sobre
  entero: los días fijos se respetan y el resto se reparte entre los pasos con peso, re-normalizando.
  *(Respuesta 7.)*

Caso límite: si los días fijos suman más que la mediana, el resto se topa en cero y el total pasa a ser
la suma de los fijos — nunca un total negativo ni pasos con días negativos. Regla explícita:
`total = max(mediana, suma de días fijos)`.

**(c) La mediana y los pesos se siguen midiendo sobre las siete columnas legacy.**
`medianasPorTipo()` y `pesosDelCatalogo()` son estadísticas **de la empresa**, no de una obra: describen
la forma del proceso en el catálogo. Si dependieran de la lista de pasos de cada proyecto, la mediana
de «a todo costo» cambiaría según qué obra la pregunte. Se quedan sobre las siete columnas, y el
centinela de `test_pdc_v2_plan_fechas.php` sigue funcionando sin tocarlo.

## Modelo de datos

### `general_pasos_contratacion` — catálogo global (sin `project_id`)

| Columna | Tipo | Para qué |
|---|---|---|
| `id` | INT PK AI | |
| `clave` | VARCHAR(60) UNIQUE | Identidad estable: `elaboracion_pliegos`, `licify`, `aprobacion_cliente`… Es lo que viaja a `pdc_plan_paso.paso_id` y lo que permitirá comparar obras en B3 |
| `nombre` | VARCHAR(120) | Nombre por defecto en pantalla |
| `col_legacy` | VARCHAR(60) NULL | Columna de `general_dias_procesos_contratacion` de la que saca los días **por paquete**. NULL = paso sin respaldo legacy → usa días fijos |
| `dias_sugeridos` | INT NULL | Valor que la pantalla propone al agregar un paso sin `col_legacy` |
| `peso_reparto` | DECIMAL(9,6) NULL | Peso medido para repartir la mediana. NULL en los pasos sin `col_legacy` (esos no entran al reparto: llevan días fijos) |
| `orden_default` | INT | Orden canónico del proceso completo |
| `activo` | TINYINT(1) | Retiro sin borrar |
| `creado_por`, `updated_at` | | Auditoría, igual que el resto de catálogos PDC |

**Semilla — 9 filas.** Las siete actuales, con su `col_legacy` y su `peso_reparto` copiados tal cual de
`PlanFechasService::PASOS` y `PESOS_REPARTO`, más:

- `licify` — «Ingreso a plataforma Licify», sin `col_legacy`, `dias_sugeridos = 1` (el histórico dice 0-2).
- `aprobacion_cliente` — «Aprobación del cliente», sin `col_legacy`, `dias_sugeridos = 15`, `orden_default`
  entre Cuadros comparativos y Legalización, que es donde lo tenían los dos proyectos de la Variante B.

### `pdc_proyecto_pasos` — la configuración de la obra

| Columna | Tipo | Para qué |
|---|---|---|
| `id` | BIGINT PK AI | |
| `project_id` | INT NOT NULL | |
| `paso_id` | INT NOT NULL | FK → `general_pasos_contratacion` |
| `orden` | INT NOT NULL | Posición en esta obra |
| `alias` | VARCHAR(120) NOT NULL DEFAULT '' | Nombre propio de la obra; vacío = el del catálogo |
| `dias_fijos` | INT NULL | Obligatorio si el paso no tiene `col_legacy`; NULL si los saca del catálogo legacy |
| `actualizado_por`, `updated_at` | | |

`UNIQUE (project_id, paso_id)` · `KEY idx_pps_proyecto_orden (project_id, orden)` · `utf8mb4_unicode_ci`.

**Sin columna `activo`** (corregido al escribir el plan): la lista *es* la configuración y `guardar()`
la reescribe entera en una transacción, así que una bandera de apagado nunca valdría 0. «Apagar» un
paso es sacarlo de la lista. Una columna que nadie pone nunca en 0 es peso muerto y una segunda forma
de decir lo mismo — que es como se desincronizan las cosas.

**La regla de cero regresión vive aquí:** *cero filas para un `project_id` ⇒ la obra usa
`PlanFechasService::PASOS`*. Da Porto no recibe ni una fila en la migración, así que sus 11 paquetes dan
exactamente las mismas fechas. La constante de código sigue siendo el respaldo real; el catálogo es lo
que la pantalla ofrece. Un test verifica que catálogo y constante coinciden en esas siete, para que no
puedan divergir en silencio.

### `pdc_plan_paso` — cambio de clave

- `+ paso_id INT NULL` con FK a `general_pasos_contratacion`.
- Backfill: las 77 filas existentes se llenan mapeando `orden` 0…6 a las siete claves por defecto.
- La clave única `uq_pps_proyecto_paquete_orden (project_id, paquete_id, orden)` se reemplaza por
  **`(project_id, paquete_id, paso_id)`**; `orden` conserva un índice no único para poder ordenar.
- `paso` pasa de `VARCHAR(60)` a `VARCHAR(120)`: verificado en el `SHOW CREATE TABLE` real, hoy son 60 y
  un alias de obra de hasta 120 se truncaría en silencio al escribir el plan.
- `orden` es `TINYINT` y **no** hay que tocarlo: aguanta hasta 127 pasos, muy por encima del «pueden ser
  más de 7» del grilleo.
- Convergente con guardas de `information_schema`, igual que `20260728_pdc_v2_plan_fechas.sql`: aplicar
  dos veces no hace nada, y un entorno que ya tuviera parte del cambio termina en el mismo esquema.

## Servicio

**Nuevo — `PasosContratacionService`** (archivo propio; no toca ninguno de los métodos que otra tarea
tiene en curso):

- `catalogo(): list<Paso>` — el catálogo global activo.
- `deProyecto(int $projectId): list<PasoEfectivo>` — **la única fuente de verdad de qué pasos tiene una
  obra**: si no hay filas devuelve los siete de `PASOS` (con su clave, columna y peso); si las hay,
  devuelve las activas ordenadas. Todo lo demás — `calcular()`, la API, la pantalla — pregunta aquí.
- `guardar(int $projectId, list<...> $pasos, string $usuario): array` — valida y escribe:
  - al menos un paso activo;
  - `dias_fijos` obligatorio y ≥ 0 en todo paso sin `col_legacy`;
  - sin claves repetidas; solo claves del catálogo activo;
  - `orden` se normaliza a 0…N-1 en el servidor (la pantalla no decide la numeración).
- `restablecer(int $projectId, string $usuario)` — borra la configuración: la obra vuelve al default.

**`PlanFechasService` — solo lo permitido por la frontera de archivos** (`PASOS`, `calcular()`,
`medianasPorTipo()`, `pesosDelCatalogo()`, `PESOS_REPARTO`, y lo que `plan()` lea de pasos):

- `PASOS` gana una `clave` por entrada y pasa a ser explícitamente *el proceso por defecto*, no *el
  proceso*.
- `calcular()` pide la lista a `PasosContratacionService::deProyecto()`, arma el `SELECT` con las
  columnas legacy que esa lista use (no las siete fijas), y por paso toma
  `col_legacy ? (int) $fila[col] : dias_fijos`.
- «Desglose completo» = todas las columnas legacy **que la lista usa** vienen no-NULL. Los pasos de días
  fijos siempre aportan su número y nunca vuelven provisional a un paquete.
- Provisional: `resto = max(0, mediana − Σ días fijos)`; `resto` se reparte entre los pasos con peso,
  re-normalizados sobre los que estén activos; total = `Σ fijos + resto`.
- `repartirMediana(int $total, ?array $pesos = null)` — acepta los pesos; sin argumento sigue usando
  `PESOS_REPARTO`, así los tests actuales no cambian de forma. Deja de asumir 7 en `count()` y en el
  reparto del residuo.
- El borrado de sobrantes deja de ser `orden >= count(PASOS)` y pasa a ser
  `paso_id NOT IN (los configurados)` — que es lo que funciona cuando la lista crece por encima de
  siete, se acorta, o se reordena.
- `plan()` devuelve `clave` y el nombre efectivo (alias si lo hay) en cada paso.

## API

| Método | Ruta | Permiso |
|---|---|---|
| GET | `/plan-compras/api/plan/pasos` | `lps.paquetes_contratacion.ver` |
| POST | `/plan-compras/api/plan/pasos` | `lps.paquetes_contratacion.reglas` + CSRF `plan_compras_v2` |
| POST | `/plan-compras/api/plan/pasos/restablecer` | `lps.paquetes_contratacion.reglas` + CSRF |

Envelope `{ok, data|error}` como el resto del módulo. El GET devuelve
`{catalogo, proyecto, configurado: bool}`; los dos POST recalculan el plan en la misma llamada y
devuelven `{calculados, sinDuracion}` para poder decir «se recalcularon N paquetes» sin una segunda
vuelta (respuesta 10).

## Pantalla

Ruta nueva `#/ensamble/plan/pasos`, **fuera de `PANTALLAS`** (no aparece en la barra de pestañas), en
archivo nuevo `src/pages/PasosContratacion.tsx`. Se llega desde un enlace «Configurar pasos» en la
barra de herramientas de Plan de fechas, junto a «Recalcular» — el único cambio en `PlanFechas.tsx`:
un import y un enlace. `App.tsx` gana una línea de `<Route>`.

La pantalla muestra la lista efectiva en orden, con: subir/bajar, apagar, alias, días fijos donde
corresponda, y un desplegable para agregar un paso del catálogo que aún no esté. Cuando la obra no
tiene configuración lo dice explícitamente («Esta obra usa el proceso por defecto de la empresa») y el
primer cambio la crea. Apagar un paso avisa cuántas filas de plan se van a borrar antes de guardar.
Botón «Restablecer» que vuelve al default.

La lógica pura (validar, reordenar, detectar si difiere del default, calcular el aviso de borrado) vive
en `src/lib/pasosState.ts` con Vitest, siguiendo el patrón de `paquetesState.ts`.

## Verificación

**Cero regresión (la condición de hecho).** Test PHP autoejecutable
`tests/test_pdc_v2_pasos_configurables.php` contra el MySQL real:

1. Fotografía las 11 filas de `pdc_plan_paquete` y las 77 de `pdc_plan_paso` de Da Porto **dentro de la
   misma corrida**, corre `calcular()` sin configuración, y compara fila a fila: mismas fechas, mismos
   días, mismo total. Tomar la foto en la corrida —y no contra un fichero congelado— hace la prueba
   inmune a que otra sesión mueva datos del proyecto mientras tanto.
2. Con configuración: agrega «Aprobación cliente, 15 días» a un proyecto de prueba y verifica que
   (a) el total sube exactamente 15, (b) `fecha_arranque` retrocede exactamente 15 días, (c) la
   `fecha_fin` del último paso sigue siendo la `fecha_ancla`, (d) la suma de los `dias` es exactamente
   `fecha_ancla − fecha_arranque` — las tres propiedades del docblock de `calcular()`, con fronteras
   `[inicio, fin)` intactas.
3. Lista de más de siete pasos y lista de menos: los sobrantes se borran por `paso_id`, no por posición.
4. Provisional: `Σ fijos + resto = total`, y con fijos > mediana el total es `Σ fijos` sin días negativos.
5. Reordenar no reasigna filas: la fila de una clave conserva su `paso_id` tras mover el paso de sitio.
6. Catálogo ≡ constante en las siete por defecto (clave, columna legacy y peso).
7. RBAC: `.ver` lee, `.reglas` escribe, sin `.reglas` el POST responde 403.

**Resto de gates:** `test_global_table_safety.php` y `test_global_table_reconciliation.php` en verde;
`npm run test` y `npm run build` en la SPA; los e2e `pdc-v2-*` de `tests/browser/`.

## Riesgos

- **Cambiar la clave única de una tabla con datos.** Mitigación: la migración es dry-run → `--apply`,
  informa cuántas filas va a tocar, y el backfill de `paso_id` corre **antes** del cambio de clave; si
  quedara alguna fila sin `paso_id`, aborta sin tocar el índice.
- **Da Porto es el patrón oro y hay tres sesiones más escribiendo en la misma base.** Mitigación: la
  migración no escribe ni una fila en `pdc_proyecto_pasos`, la comparación se toma dentro de la corrida
  del test, y queda además la foto con marca de tiempo de `linea-base.txt`.
- **Apagar un paso destruye filas que mañana tendrán avance real.** No hay datos de B1 todavía, pero el
  aviso en pantalla y el borrado por `paso_id` son justamente lo que hace que esto siga siendo seguro
  cuando los haya.

## Fuera de alcance (a registrar en el roadmap como pendientes)

Listas de pasos distintas por modalidad o tipo de negociación dentro de una misma obra · copiar la
configuración de una obra a otra · historial de versiones de la configuración · editar las duraciones
del catálogo legacy desde esta pantalla.
