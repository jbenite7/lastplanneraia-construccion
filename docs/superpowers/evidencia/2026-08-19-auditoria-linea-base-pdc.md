# Auditoría de la línea base contractual en el PDC — con evidencia

**Frente:** `linea-base-contractual`, tarea 6. **Alcance:** auditar, no arreglar. Cada punto que usa
"línea base" en `src/Services/Pdc/` y `pdc-app/src/` se midió con datos reales de la base de
desarrollo (solo lectura) contra el caso difícil declarado en el encargo: un proyecto cuyas
actividades de la primera y la última semana registrada no se solapan.

**Restricción de sesión:** esta auditoría es de solo lectura. No se ejecutó ningún `UPDATE`,
`INSERT` ni `DELETE`, ni siquiera con restauración posterior — otra sesión está a mitad de una
migración y rige la regla de "nadie escribe en la base de desarrollo". Donde un punto solo podía
comprobarse escribiendo, se dice explícitamente que quedó sin cubrir (ver "Qué NO se auditó").

## Paso 1 — inventario

```
grep -rn "LineaBase\|linea_base\|lineaBase" src/Services/Pdc/ pdc-app/src/
```

Salida real:

```
src/Services/Pdc/FlujoCajaService.php:276:            'SELECT fechaInicioLineaBase AS desde, fechaFinLineaBase AS hasta
src/Services/Pdc/FlujoCajaService.php:281:            return ['desde' => (string) $lb['desde'], 'hasta' => (string) $lb['hasta'], 'origen' => 'linea_base'];
```

Ampliando a `grep -rni "lineabase|linea_base|linea base|baseline"` (case-insensitive, sin acento)
aparece un tercer punto que el patrón exacto del brief no capturaba porque está en prosa, no en
identificador:

```
src/Services/Pdc/SeguimientoService.php:85:     * con un plan sembrado. Lo PROGRAMADO no entra ni sale: es la linea base contra la que se mide el
```

El resto de coincidencias de `baseline` en `pdc-app/src/styles.css` son `align-items: baseline` de
CSS — no tienen nada que ver con la línea base contractual. **Ninguna se cuenta como punto.**

No hay más ocurrencias en `pdc-app/src/` (el SPA no toca este concepto directamente; consume la API
que ya sirve las fechas resueltas por el backend).

Investigando desde ahí se destaparon dos puntos más que el grep del brief no encuentra porque no usan
esas palabras — el mecanismo real que sostiene (o no) "lo PROGRAMADO" vive en otro archivo:
`PlanFechasService::calcular()` y `PlanFechasService::aplicarReprogramacion()`. Se documentan como
puntos 3 y 4 porque son la implementación real de la afirmación de `SeguimientoService.php:85`, y sin
mirarlos la auditoría se queda en la superficie del comentario.

---

## Punto 1 — `FlujoCajaService::duracionObra()` (líneas 259–282)

**Qué hace, en una frase:** calcula el rango de fechas de la obra para repartir el gasto sin frente
propio; usa el cronograma vigente (`MAX(Semana)`) como fuente primaria y la línea base almacenada en
`general_proyectos_procesos` como respaldo **solo si el cronograma no devuelve nada**.

**Código relevante (ya citado en el encargo, verificado línea por línea):**

```php
$r = $this->db->query(
    'SELECT MIN(pc.Fecha_Inicio) AS desde, MAX(pc.Fecha_Fin) AS hasta
       FROM programa_consolidado pc
      WHERE pc.project_id = ?
        AND pc.Semana = (SELECT MAX(Semana) FROM semanas_activas WHERE project_id = ?)',
    [$projectId, $projectId],
)->fetch(\PDO::FETCH_ASSOC);
if ($r !== false && $r['desde'] !== null && $r['hasta'] !== null) {
    return ['desde' => ..., 'origen' => 'cronograma'];
}
$lb = $this->db->query(
    'SELECT fechaInicioLineaBase AS desde, fechaFinLineaBase AS hasta
       FROM general_proyectos_procesos WHERE Id = ?',
    [$projectId],
)->fetch(\PDO::FETCH_ASSOC);
if ($lb !== false && !empty($lb['desde']) && !empty($lb['hasta'])) {
    return [..., 'origen' => 'linea_base'];
}
return null;
```

**Verificación estructural (medida, no supuesta):** el SELECT de la línea base **no tiene cláusula
`Semana` ni ningún filtro por cohorte** — es `WHERE Id = ?`, un solo valor por proyecto. Por
construcción no puede variar al reprogramar ni al cambiar qué actividades están vigentes: no cruza
nada con `programa_consolidado`. Esto es lo opuesto al patrón que rompió el cronograma
(`ControlTowerService::programaContractualBaselineForCurrentCohort()`, que sí intersectaba cohortes).

**Consulta ejecutada sobre la base de desarrollo (proyecto 68, el caso difícil nombrado en el
encargo):**

```sql
SELECT Id, Proyecto_Proceso, fechaInicioLineaBase, fechaFinLineaBase, pdcActivo
  FROM general_proyectos_procesos WHERE Id = 68;
```

Salida real:

```
[Id] => 68
[Proyecto_Proceso] => Optimización Aeropuerto JMC
[fechaInicioLineaBase] =>
[fechaFinLineaBase] =>
[pdcActivo] => 1
```

**El proyecto 68 de la base de desarrollo NO tiene línea base declarada** (está vacía). El caso
disjunto semana-3/semana-5 que describe el encargo es el del *fixture del CI*
(`database/fixtures/design-system-ci.sql`), no el de la base de desarrollo compartida — son datos
distintos. En la base de desarrollo, el proyecto 68 tiene semanas 1 a 11, con 1611 y 1854 actividades
respectivamente e intersección de 1232 (no es un caso disjunto real):

```sql
SELECT MIN(Semana) minsem, MAX(Semana) maxsem FROM programa_consolidado WHERE project_id = 68;
-- minsem=1 maxsem=11
-- semana 1: 1611 actividades · semana 11: 1854 actividades · interseccion: 1232
```

Como el cronograma del 68 sí devuelve rango (`origen = 'cronograma'`), el camino de línea base nunca
se ejercita para este proyecto en el estado actual de la base. Para medir el camino de línea base con
datos reales se buscó, por SELECT, el caso más cercano al disjunto entre los proyectos que sí tienen
línea base declarada Y cronograma cargado:

```sql
SELECT g.Id, g.Proyecto_Proceso, g.fechaInicioLineaBase, g.fechaFinLineaBase
  FROM general_proyectos_procesos g
 WHERE g.fechaInicioLineaBase IS NOT NULL AND g.fechaFinLineaBase IS NOT NULL
   AND EXISTS (SELECT 1 FROM programa_consolidado pc WHERE pc.project_id = g.Id);
```

Encontró 15 proyectos. El más cercano al caso difícil es el **proyecto 70** (Metrolinea Estación 16 —
Edificio Descendente): semana 1 con 6 actividades, semana 22 con 301, **intersección = 1**
(prácticamente disjunto). Su línea base declarada: `fechaInicioLineaBase = 2025-12-15`,
`fechaFinLineaBase = 2024-09-03`. Como el SELECT de `duracionObra()` para la rama `linea_base` no
depende de `Semana`, ejecutar el mismo SELECT contra el proyecto 70 devuelve el mismo valor
determinístico sin importar cuál semana esté vigente — se comprobó leyendo la consulta (no tiene
parámetro de semana) y ejecutándola:

```sql
SELECT fechaInicioLineaBase AS desde, fechaFinLineaBase AS hasta
  FROM general_proyectos_procesos WHERE Id = 70;
-- desde=2025-12-15  hasta=2024-09-03
```

**Nota aparte, fuera del alcance de esta auditoría:** esas dos fechas están invertidas (inicio
posterior al fin) en tres proyectos (70, 71, 72), probablemente un error de captura en el panel de
administración o en el seed. No es un defecto de "la línea base se pierde"; es un dato mal cargado
que el código no valida. Se deja anotado, no se toca.

**Veredicto: CONSERVA la línea base.** El SELECT no depende de la semana consultada ni de qué
actividades sigan vigentes; es un valor por proyecto, medido igual antes y después de cualquier
reprogramación posible (no tiene con qué variar). Lo medido es la ausencia estructural de un filtro
de cohorte, más la ejecución real de la consulta en dos proyectos distintos con resultados
consistentes. Lo que **no** se midió empíricamente es un "antes/después" de una reprogramación real
sobre el mismo proyecto —eso requeriría escribir en `programa_consolidado` o `semanas_activas`, y
queda fuera por la restricción de solo lectura.

---

## Punto 2 — `SeguimientoService.php:85` (comentario) y `proyectar()` (líneas 81–130)

**Qué hace, en una frase:** el comentario en la línea 85 llama "línea base" a lo PROGRAMADO
(`pdc_plan_paso.fecha_fin`) que entra como parámetro puro a `proyectar()`, una función aritmética sin
acceso a base de datos que compara avance real contra programado.

**Verificación de que `proyectar()` en sí no deriva nada de cohortes:** es `static`... no, es un
método de instancia pero **sin ninguna consulta SQL dentro** — recorre `$pasos` (ya resuelto por el
llamador) y hace aritmética de fechas. Confirmado leyendo el cuerpo completo (líneas 96–130): no hay
`$this->db->query` en ningún punto de la función.

**De dónde vienen los datos que le pasa el llamador** (`pasosDePaquete()`, líneas 137–186):

```php
$rows = $this->db->query(
    'SELECT paso_id, orden, paso, dias, fecha_inicio, fecha_fin, fecha_real, ...
     FROM pdc_plan_paso
     WHERE project_id = ? AND paquete_id = ? AND subpaquete_id = ?
     ORDER BY orden',
    ...
)->fetchAll(...);
```

Es una lectura directa de `pdc_plan_paso`, sin cruce de cohortes ni intersección de semanas — el
mismo patrón sano del punto 1. **Hasta aquí, el comentario de la línea 85 es cierto en lo que
describe: `proyectar()` no recalcula ni deduce nada por su cuenta.**

**Pero la pregunta que importa no es si `proyectar()` deriva la fecha — es si `pdc_plan_paso.fecha_fin`
en sí se conserva.** Ahí el comentario deja de ser cierto. Ver punto 3.

**Veredicto: NO SE PUEDE JUZGAR AISLADO.** La función en sí es sana (no deduce nada de cohortes), pero
su afirmación de que "lo PROGRAMADO no entra ni sale" describe el dato que consume, y ese dato sí
cambia — ver punto 3.

---

## Punto 3 — `PlanFechasService::calcular()`, el upsert de `pdc_plan_paso` (líneas 1483–1502)

**Qué hace, en una frase:** recalcula el plan de contratación de todos los paquetes amarrados y
sobrescribe `fecha_inicio`/`fecha_fin` de **cada paso** vía `INSERT ... ON DUPLICATE KEY UPDATE`, sin
filtrar por si ese paso ya tiene `fecha_real` registrada (avance real ya ocurrido).

**Código exacto (líneas 1483–1502):**

```php
$idsVigentes = [];
foreach ($pasos as $i => $p) {
    $ini = $cursor;
    $cursor = $cursor->modify(sprintf('+%d days', $dias[$i]));
    if ($p['pasoId'] !== null) {
        $idsVigentes[] = (int) $p['pasoId'];
    }
    $this->db->query(
        'INSERT INTO pdc_plan_paso (project_id, paquete_id, subpaquete_id, orden, paso_id, paso, dias, fecha_inicio, fecha_fin)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE orden = VALUES(orden), paso = VALUES(paso), dias = VALUES(dias),
            fecha_inicio = VALUES(fecha_inicio), fecha_fin = VALUES(fecha_fin)',
        [$projectId, $paqueteId, $subpaqueteId, $i, $p['pasoId'], $p['nombre'], $dias[$i],
            $ini->format('Y-m-d'), $cursor->format('Y-m-d')],
    );
}
```

El `WHERE` que sí protege algo aparece **después**, en dos sentencias separadas que solo tocan las
filas "sobrantes" (pasos que ya no están en el proceso vigente de la obra):

```php
// borra sobrantes SIN avance real:
"DELETE FROM pdc_plan_paso WHERE ... AND fecha_real IS NULL AND {$sobrante}"
// a las sobrantes CON avance real les borra lo programado (no lo real):
"UPDATE pdc_plan_paso SET fecha_inicio = NULL, fecha_fin = NULL
  WHERE ... AND fecha_real IS NOT NULL AND {$sobrante}"
```

`fecha_real` en sí nunca se toca (no está en el `ON DUPLICATE KEY UPDATE` del INSERT ni en el
`DELETE`) — eso es correcto y está documentado a propósito. **Pero `fecha_fin` (lo PROGRAMADO) del
INSERT principal se sobrescribe para TODOS los pasos vigentes, incluidos los que ya tienen
`fecha_real` registrada.** No hay `WHERE fecha_real IS NULL` en ese INSERT. Leído literal: si un paso
ya ocurrió (tiene fecha real) pero sigue en la lista de pasos vigentes de la obra, y `calcular()`
se vuelve a ejecutar por cualquier motivo (cambiar duraciones del catálogo, cambiar los pasos de
contratación de la obra, copiar pasos de otro proyecto, o confirmar una reprogramación), su
`fecha_fin` PROGRAMADA se reescribe con el nuevo cálculo.

Esto contradice literalmente el comentario de `SeguimientoService.php:85`: *"Lo PROGRAMADO no entra
ni sale: es la linea base contra la que se mide el atraso, y reescribirla dejaria al proyecto sin
forma de decir cuanto se desvio de lo prometido."* El código sí la reescribe, para pasos ya
completados, cada vez que `calcular()` corre.

**Quién dispara `calcular()`:** cinco endpoints POST en `PlanComprasPlanController` (calcular manual,
guardar pasos de contratación, copiar pasos de otro proyecto, restablecer pasos por defecto, y
actualizar una duración del catálogo) — todos gatillados por una acción humana explícita, no por un
cron ni por cada carga de pantalla. No es el mismo patrón del defecto del cronograma (que se disparaba
en cada lectura, silenciosamente). Pero **sigue siendo cierto que el dato "programado" contra el que
se mide el desfase no es estable en el tiempo**: dos personas midiendo el mismo paso completado en dos
momentos distintos, separados por un `calcular()` de por medio, verían un `desfaseDias` distinto para
el mismo hecho histórico, sin que nada se los avise.

**Verificación empírica intentada — bloqueada por la restricción de solo lectura:**

```sql
SELECT project_id, paquete_id, subpaquete_id, orden, paso, fecha_inicio, fecha_fin, fecha_real, registrado_at
  FROM pdc_plan_paso WHERE fecha_real IS NOT NULL ORDER BY registrado_at DESC LIMIT 10;
```

Salida real: **vacía.** No hay ningún paso con avance real registrado en toda la base de desarrollo
hoy. No se pudo construir el caso "paso completado cuyo `fecha_fin` cambió tras un recálculo
posterior" con datos existentes, y esta sesión tiene prohibido escribir para producirlo (ni siquiera
con `register_shutdown_function` restaurando después — la regla de tráfico de esta tarea es más
estricta que la de otros tests del repo, que sí escriben y restauran).

**Veredicto: NO CONSERVA la línea base, con evidencia de código firme y sin verificación empírica
posible bajo la restricción de solo lectura de esta sesión.** La lectura literal del `INSERT ...
ON DUPLICATE KEY UPDATE` (sin filtro por `fecha_real`) es inequívoca; lo que falta es una corrida real
que lo confirme con datos, y no hay datos en la base de desarrollo para montarla sin escribir.

---

## Punto 4 — `PlanFechasService::aplicarReprogramacion()` (líneas 2116–2172) y el refresco de
`fecha_ancla`

**Qué hace, en una frase:** cuando el usuario confirma en pantalla qué paquetes reprogramar, refresca
`pdc_paquete_frente.fecha_ancla` desde el cronograma vigente y dispara `calcular()`, que en cascada
reescribe `pdc_plan_paso` (punto 3).

**Lectura de su propio comentario, que es honesto sobre el trade-off:**

```
Refresca la fecha_ancla del amarre desde el cronograma en vivo y recalcula. Ese refresco es
el punto: sin el, calcular() vuelve a proyectar contra la copia congelada del ancla y el
desfase no se va nunca — el bug que midio B2, ver
goals/pdc-preparar-b1/evidence/medicion-rematching-2026-07-29.md.
```

Este es un dato clave para el veredicto: **el diseño de PDC ya tuvo, antes de esta auditoría, el
problema simétrico** — un ancla que NUNCA se actualizaba producía un desfase que no se resolvía
jamás. La corrección de ese bug (B2) fue hacer que el ancla se refresque cuando el usuario confirma.
Eso significa que "lo PROGRAMADO" en PDC **nunca tuvo la intención de ser una línea base contractual
congelada** — es un plan vivo que se re-ancla deliberadamente contra el cronograma vigente, a
diferencia de `fechaInicioLineaBase`/`fechaFinLineaBase` en `general_proyectos_procesos`, que sí son
write-once por diseño (`LineaBaseContractualService::sembrarSiFalta()`, ya construido en otra tarea de
este mismo frente).

**Esto es una tensión de nombres, no necesariamente el mismo defecto que el cronograma:** el
cronograma perdía la fecha **sin que nadie lo pidiera**, por una intersección vacía en una consulta
de lectura. Aquí la fecha programada cambia **porque alguien confirmó explícitamente una
reprogramación**, o porque alguien cambió la configuración de pasos/duraciones — son acciones
deliberadas, no una consulta silenciosa. El defecto real está en que ese recálculo **no distingue
pasos ya ocurridos de pasos pendientes** (punto 3): un paso YA CERRADO no debería perder su fecha
programada original solo porque otro paquete, u otro paso del mismo paquete, se recalculó.

**Veredicto: DISEÑO INTENCIONAL, con un defecto de alcance dentro de él.** El re-anclaje en sí es una
decisión de producto documentada y con su propia historia de bug corregido (B2). El defecto que sí
aparece es el del punto 3: el recálculo no protege la fecha programada de un paso que ya tiene
`fecha_real`.

---

## Qué NO se auditó y por qué

- **Un antes/después real de un recálculo sobre un paso con `fecha_real`.** Requeriría escribir en
  `pdc_plan_paso`, `pdc_plan_paquete` o `pdc_paquete_frente` (directamente, o llamando a
  `PlanFechasService::calcular()`/`aplicarReprogramacion()`, que escriben). Prohibido por la regla de
  tráfico de esta sesión. Es el hueco más importante: el punto 3 queda en "evidencia de código firme,
  sin confirmación empírica" en vez de "medido".
- **El camino de `duracionObra()` con `origen = 'linea_base'` ejecutándose de punta a punta contra un
  proyecto real sin cronograma.** Se verificó el SELECT aislado (dos proyectos, incluido el más
  cercano al caso disjunto), pero no se instrumentó `FlujoCajaService::curva()` completo con un
  proyecto cuyo `programa_consolidado` esté vacío — ninguno de los proyectos con línea base declarada
  en la base de desarrollo carece de cronograma, así que la rama de respaldo nunca se ejercita hoy con
  datos reales; solo se confirmó por lectura de código y por ejecución aislada del SELECT.
- **`MaestroInsumosService`, `PresupuestoImportService`, `PaquetesService`, `SubpaquetesService`,
  `PdcResetService`, `AmarreCronogramaService` (fuera de `frentesDeDestinos()`, ya cubierto en el
  contexto del punto 1), `PresupuestoExcelParser`, `MaestroSincoImportService`/`Parser`,
  `DuracionesCatalogoService`, `TipoRecursoEquipo`.** El grep del Paso 1 (incluida su versión
  ampliada case-insensitive) no encontró ninguna ocurrencia de línea base en estos archivos. Se
  entiende que no manejan el concepto — no calculan ni leen ninguna fecha "contractual" o "de
  referencia congelada" — y por eso no se les abrió una sección propia. No se leyeron línea por línea
  en busca de un patrón que no anuncian tener; sería expandir el alcance sin una señal que lo
  justifique.
- **El caso disjunto semana-3/semana-5 del fixture del CI** (`database/fixtures/design-system-ci.sql`)
  no se cargó ni se consultó — esa base es para el pipeline de CI, no para esta sesión, y el encargo
  prohíbe tocar ese fixture. Se usó en su lugar el proyecto 68 real de la base de desarrollo (con
  intersección 1232, no disjunta) y el proyecto 70 (intersección 1, casi disjunto) como sustitutos
  con datos reales.
- **El SPA (`pdc-app/src/`) en ejecución (navegador).** El grep no encontró ocurrencias de línea base
  en el código fuente del SPA, así que no se abrió el navegador para esta auditoría — no hay una
  pantalla que muestre o dependa de "línea base" para verificar visualmente. Si el punto 3 se
  confirma y se arregla, sí ameritaría una revisión visual de la pantalla de seguimiento (columna
  "Programado").

## Qué haría falta para cerrarlo

1. **Confirmar el punto 3 con datos**, en una sesión autorizada a escribir y con la disciplina de
   restaurar (`register_shutdown_function`, como ya hace `tests/test_linea_base_contractual_service.php`):
   crear un paquete de prueba, amarrarlo, calcular su plan, registrar `fecha_real` en un paso,
   guardar su `fecha_fin` programada, disparar `calcular()` de nuevo (por ejemplo cambiando una
   duración del catálogo) y comparar la `fecha_fin` de ese paso antes/después. Si cambia, el defecto
   queda confirmado con la misma disciplina de evidencia que se le exigió al cronograma.
2. **Decidir la regla de negocio primero, no el código.** Antes de tocar `calcular()` hace falta que
   Felipe (o quien tenga el criterio de producto) diga si un paso con `fecha_real` YA registrada debe
   conservar su `fecha_fin` original —el patrón "escribe una vez, no pises lo declarado" que ya se
   aplicó a `LineaBaseContractualService`— o si el rediseño B2 (el re-anclaje) tiene prioridad y el
   defecto es aceptable porque el usuario siempre confirma antes de reprogramar. Son dos posturas de
   producto distintas y no es una decisión técnica.
3. **Si se decide proteger el paso completado**, el cambio mínimo es acotar el `INSERT ... ON
   DUPLICATE KEY UPDATE` de la línea 1497 para que no reescriba `fecha_inicio`/`fecha_fin` cuando la
   fila ya tiene `fecha_real IS NOT NULL` — mismo patrón que ya usan las dos sentencias vecinas
   (`DELETE ... AND fecha_real IS NULL` / `UPDATE ... AND fecha_real IS NOT NULL`). Es un cambio de
   una tarea propia, con su propio test y su propio commit — no entra en el alcance de esta auditoría.
