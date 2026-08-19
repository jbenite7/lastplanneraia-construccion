# La línea base contractual deja de deducirse — diseño

**Frente:** `linea-base-contractual`. **Origen:** defecto destapado al desbloquear el CI en el frente
`runtime-budgets-al-ci` (publicado en `afb16946`/`c23b1c6a`). **Encargo de Felipe, 2026-08-19.**

## El defecto, medido y no supuesto

El CI falla en `tests/test_bi_programa_general_chart_values.php` con:

```
FAIL: baseline-drift: contractual finish moved with latest reprogramming
```

**El mensaje miente sobre su propia causa.** La fecha contractual no se desplaza a la última
reprogramación: **sale vacía**. Medido interrogando al servicio directamente sobre el proyecto que
el test elige:

```
BI devuelve contractual_finish = (ninguno)
basis                          = first_available_snapshot_per_project
el test EXIGE (1a semana)      = 2026-07-19
el test PROHIBE (ultima)       = 2026-08-16
```

Salta la aserción `!== first_finish` porque una cadena vacía tampoco es esa fecha. La aserción que
detectaría un desplazamiento real **pasa**. Quien lea solo el mensaje buscará por qué el baseline se
desplaza, y no se desplaza.

**Causa.** `ControlTowerService::programaContractualBaselineForCurrentCohort()`
(`src/Services/ControlTowerService.php:1867`) cruza el baseline contractual —las filas del primer
corte— con la cohorte de la semana consultada, y se queda solo con las que siguen existiendo. Si la
reprogramación cambió las actividades, la intersección es vacía, `programaBaselineDates()` no
devuelve fecha y `contractual_finish` sale nulo. En el fixture del CI, proyecto 68:

```
semana 3: 1 unique_id · semana 5: 2 unique_id · interseccion: 0
```

**El filtro no es un descuido:** existe para que, al filtrar por subcontratista o responsable, la
fecha contractual corresponda a las actividades filtradas y la comparación sea pareja. Lo que no
hace es distinguir «lo excluyó un filtro» de «esa actividad ya no existe porque se reprogramó».

**Es preexistente, con fecha.** Corrida `31760660951` (`5cfa1f99`, sin el commit de fixture
`8a0d5e46`): paso 12 en `success`. Corrida `31827661090` (`ef49b6a0`, con él): `failure`, mismo
mensaje, 2026-08-14T18:21:04Z. Cinco días antes del arreglo del CI que lo destapó.

## La decisión de negocio, que es la que manda

Felipe, 2026-08-19, textual:

> «Al reprogramar y cambiar actividades, el informe **SÍ debe conservar la fecha contractual
> original**. Tratalo como agujero real. Porque parte de los análisis deben ser sobre la línea base,
> y aplica para cronograma, pero **también para presupuesto en PDC**.»

Con eso, el arreglo cómodo —retocar el fixture para que el proyecto 68 comparta actividades entre la
primera semana y la última— **queda descartado**: taparía el defecto y dejaría el test verde midiendo
un escenario que ya no ocurre. Se anota explícitamente porque lo propuso quien escribe esta spec.

**Corolario que invierte el orden natural:** si se arregla el cálculo, el test se pone verde **sin
tocar el fixture**. El fixture monta cohortes disjuntas, que es justamente el caso que debe
funcionar. No estaba mal: estaba destapando esto.

## Qué se decide

### 1. Una sola fuente de verdad

La fecha contractual del cronograma se lee de `general_proyectos_procesos.fechaInicioLineaBase` y
`fechaFinLineaBase` — el mismo dato que el panel de administración ya edita
(`admin/views/pages/projects/edit.php:54,60`), que la migración `20260807_proyectos_lineabase_columns.sql`
creó, y que el PDC ya consume (`src/Services/Pdc/FlujoCajaService.php:275-282`).

Deja de deducirse del primer corte en las dos llamadas afectadas: `ControlTowerService.php:202` y
`:965`. El campo `contractual_finish_basis` deja de declarar `first_available_snapshot_per_project` y
pasa a declarar que la fuente es la línea base declarada del proyecto.

Con varios proyectos seleccionados, el fin contractual del portafolio sigue siendo el máximo de los
declarados (`maxForecastDate`, sin cambios).

**Si un proyecto no tiene línea base declarada, no hay fecha contractual y el informe lo dice.** No
se inventa ninguna.

### 2. El filtro deja de mover la fecha

Filtrar por subcontratista o responsable AIA ya no recalcula la fecha contractual: es la del
proyecto. El gráfico la rotula como línea base **del proyecto**, para que no se lea como el
compromiso de ese subcontratista.

Es una consecuencia aceptada de tener la línea base a nivel proyecto: no existe una línea base por
subcontratista que leer. Se eligió sobre la alternativa de conservar el cálculo por cohorte cuando
hay filtro activo, porque eso haría convivir dos definiciones de línea base en el mismo gráfico — la
ambigüedad que este frente viene a cerrar.

`programaContractualBaselineForCurrentCohort()` queda sin uso para este fin. Si no la llama nadie
más, se retira en lugar de dejarla como código muerto.

### 3. Sembrado automático al cargar el primer cronograma

Un servicio nuevo y acotado, con una operación: dado un proyecto **sin** línea base declarada,
calcularla del primer corte de `programa_consolidado` y escribirla.

**Write-once, nunca sobrescribe.** Si el proyecto ya tiene fechas —o alguien las corrigió a mano—,
mandan las suyas y el servicio no hace nada.

Se invoca desde el camino que consolida una semana (`src/Legacy/nueva_semana.php` vía
`src/Core/Lps/LpsService.php`) con una sola llamada. `AGENTS.md` manda que `src/Legacy/` sea
mantenimiento: la lógica vive fuera y allí solo entra la invocación.

Nota de implementación: `bi_pg_semana` es una **vista** sobre `programa_consolidado`, no una tabla.
El sembrado lee la fuente, no la vista.

### 4. Migración de una vez para lo ya cargado

Los proyectos que ya tienen cronograma no van a volver a subirlo, así que el sembrado del punto 3 no
los alcanza. Censo medido sobre la base de desarrollo:

| | |
|---|---|
| proyectos totales | 49 |
| con línea base declarada | 16 |
| con cronograma cargado | 15 |
| **con cronograma y SIN línea base** | **3** |

Los tres: `68 Optimización Aeropuerto JMC`, `69 Metrolinea Confinamiento Estación 2`,
`77 Preconstrucción Equipamiento Milán Campestre`. **El 68 es el proyecto con el que el test falla**
— el defecto no salió en cualquier sitio.

Un script de migración recorre esos proyectos, calcula la línea base del primer corte y la escribe,
con la misma regla que el sembrado automático. Decisión de Felipe: se siembran automáticamente, sin
marcarlos como deducidos. **Queda dicho que para esos tres la fecha será la del primer registro y no
la del contrato**; quien la corrija después manda, por la regla write-once.

Exigencias de `docs/global-tables-architecture.md` y `AGENTS.md`, no negociables: **dry-run primero**,
respaldo verificable antes de aplicar, verificación posterior, y registro de qué se escribió en cada
proyecto.

### 5. Auditoría del PDC, con evidencia

Felipe extendió el principio al presupuesto. La revisión se entrega como **auditoría con evidencia,
no como `grep`** (ratificado por la coordinadora como restricción del plan): recorrer uno por uno los
puntos donde el PDC usa línea base —presupuesto, flujo de caja y seguimiento— y comprobar **con datos**
que reprogramar no la altera, adjuntando consultas y salidas.

Lo hallado hasta ahora, y su límite: `FlujoCajaService.php:275-282` lee las fechas almacenadas, no
derivadas, así que no puede vaciarse al reprogramar; y `SeguimientoService.php:85` conserva la línea
base por diseño, con el porqué escrito. **Eso es un `grep`, no una auditoría**: demuestra que el
patrón no está donde se buscó, no que el PDC esté sano.

## Posture

- **No tocar `database/fixtures/design-system-ci.sql` ni `tests/test_bi_programa_general_chart_values.php`.**
  Son el caso que debe funcionar. Si el arreglo es correcto, se ponen verdes solos.
- **No regenerar ningún baseline ni golden.**
- **No modernizar `src/Legacy/`**: solo entra la línea que invoca el sembrado.
- **No ampliar a otros gráficos del BI** aunque compartan el filtro por cohorte: la cohorte sigue
  siendo correcta para lo que no es la línea base contractual.
- Sin dependencias nuevas.

## Leer primero

- `src/Services/ControlTowerService.php` — `:202`, `:965`, `:1750`, `:1867`, y `:1535-1600`
  (de dónde sale `contractual_baseline_by_project`).
- `src/Services/Pdc/FlujoCajaService.php` y `src/Services/Pdc/SeguimientoService.php` — cómo trata el
  PDC la línea base, que es el patrón a seguir.
- `database/migrations/20260807_proyectos_lineabase_columns.sql` — las columnas y por qué se crearon.
- `docs/global-tables-architecture.md` — antes de escribir el script de migración.
- `AGENTS.md` §Arquitectura y datos, §Verificación.

## Pruebas

El fixture y el test existentes no se tocan. Se añaden aserciones propias:

1. Un proyecto con actividades **totalmente distintas** entre el primer corte y el último conserva su
   fecha contractual declarada.
2. Un proyecto **sin** línea base declarada no devuelve fecha contractual inventada.
3. El sembrado **no sobrescribe** una línea base existente.
4. Filtrar por subcontratista **no altera** la fecha contractual.
5. La migración en dry-run **no escribe**, y su salida enumera exactamente los proyectos que tocaría.

**Cada aserción se entrega con la mutación que la pone roja, ejecutada.** Una aserción que pasa por
el motivo equivocado es peor que una que falta: además da confianza. Este frente nace precisamente de
un rojo que llevaba cinco días nombrando mal su causa.

## Condición de hecho

El paso «Correr la suite PHP completa que el CI puede honrar» del job `design-system-runtime` **en
verde sobre `main`**, con `test_bi_programa_general_chart_values.php` pasando **sin haber sido
modificado**.

Ese es además el desbloqueo real de las Fases 2 y 3 de `runtime-budgets-al-ci`: mientras ese paso
esté rojo, GitHub salta todos los posteriores y el recibo de `runtime-budgets` no llega a generarse.

## Archivos de este frente

- [[goals/linea-base-contractual/goal|Goal]]
- [[memoria/goals/estado|Estado de los goals]]
