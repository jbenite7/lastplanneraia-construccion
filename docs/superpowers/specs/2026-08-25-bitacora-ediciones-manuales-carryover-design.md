---
capa: fuente
tipo: spec
estado: propuesta
fecha: 2026-08-25
areas: [lps, arquitectura]
tags: [carryover, auditoria, brainstorming]
fuente: sesión de brainstorming 2026-08-25
resumen: "Bitácora de ediciones manuales para los cinco campos que el arrastre trata como editables a mano, para que deje de adivinar en el caso ambiguo"
---

# Bitácora de ediciones manuales para el arrastre de avance

## Origen

El 2026-08-25 se corrigió un defecto en `WeeklyRealProgressCarryoverService` (commit `c1e3365e`):
un avance reportado en Programación Semanal después de que el Programa General de la semana
siguiente ya se hubiera abierto una vez no se sumaba nunca. El arreglo introdujo un testigo
(`Ejecutado_Carryover`) que guarda el último valor que el propio servicio escribió, para
distinguir su propia escritura de una edición real del residente.

Ese arreglo dejó un caso sin resolver por diseño: cuando una fila no tiene todavía testigo (filas
anteriores a la migración) y su valor actual no coincide ni con el acumulado de la semana anterior
ni con lo que el servicio calcularía ahora, el código **adivina** que fue una edición manual y la
respeta, sin poder confirmarlo. Medido en producción el mismo día: de 32 actividades con el
acumulado corto, 27 caen en ese caso ambiguo.

La causa de fondo es que el sistema no guarda quién cambió estos campos ni cuándo. Sin esa
evidencia, dos historias distintas —una edición real, o un residuo del defecto de julio— producen
exactamente el mismo dato. Este spec cierra esa brecha hacia adelante.

**Fuera de alcance, por decisión explícita:** las 27 actividades ya congeladas en producción no se
reparan con este trabajo. La bitácora empieza a contar desde que se despliega; las ediciones
anteriores a esa fecha no quedan escritas en ningún lado. Esas 27 se resuelven por revisión manual,
en un frente aparte.

## Alcance

Los cinco campos que `WeeklyRealProgressCarryoverService` ya trata como "editables a mano" hoy:
`Ejecutado`, `unidad`, `cantidad_ppto`, `Responsable_AIA`, `Sub_Contratista`. En las dos tablas
donde se escriben: `programa_consolidado` y `programacion_semanal`.

Tres puntos de escritura confirmados por código, no por supuesto — se verificó con `grep` sobre
`src/Controllers/`, no se asumió que solo hubiera dos:

- `GeneralApiController` — programa general
- `SemanalApiController` — programación semanal, y también escribe en `programa_consolidado`
- `ProgramacionIntermediaController` — escribe `Sub_Contratista` y `Responsable_AIA` en
  `programa_consolidado` al guardar restricciones; es fácil de pasar por alto porque su pantalla
  no es la que uno primero asocia con estos campos

Explícitamente sin pantalla: la bitácora es solo para que `WeeklyRealProgressCarryoverService` la
consulte. No hay vista de historial para el residente ni el director de obra en esta ronda.

## Diseño

### 1. Qué se guarda

Una tabla nueva y angosta, con una sola pregunta en mente: "¿alguien tocó este campo, y a qué?"

| Columna | Qué guarda |
|---|---|
| `tabla` | `programa_consolidado` o `programacion_semanal` |
| `project_id` | el proyecto |
| `Semana` | la semana de la fila editada |
| `unique_id` | el identificador de la actividad |
| `campo` | el nombre del campo que cambió |
| `valor_anterior` | como texto, sin importar el tipo original |
| `valor_nuevo` | como texto |
| `usuario` | quién hizo el cambio |
| `fecha` | cuándo |

`valor_anterior`/`valor_nuevo` se guardan como texto a propósito: los cinco campos mezclan números
y cadenas, y lo único que el consumidor de esta tabla necesita después es comparar igualdad, no
hacer aritmética sobre el historial. Una fila por campo que cambió, no una fila por acción de
guardar — editar dos campos en la misma pantalla deja dos filas.

Índice por `(project_id, tabla, Semana, unique_id, campo)` para que la consulta que hace el
arrastre sea directa.

### 2. Quién escribe ahí

Un servicio nuevo — `App\Services\CampoManualAuditoriaService` — que reemplaza el `UPDATE` manual
de estos cinco campos en los tres controladores listados arriba. Cada controlador le entrega:
tabla, proyecto, semana, actividad, los campos que quiere cambiar con sus valores nuevos, y el
usuario de la sesión.

El servicio, envuelto en una transacción (todo o nada):

1. Lee los valores actuales de la fila.
2. Compara campo por campo. Si el valor nuevo es igual al actual, no se registra nada — guardar
   sin cambiar no es una edición.
3. Por cada campo que sí cambió, inserta su fila en la bitácora, y solo entonces aplica el
   `UPDATE` real.

Lo demás que cada controlador escribe hoy — fechas, restricciones, estado — no pasa por este
servicio; solo estos cinco campos.

### 3. Cómo lo consume el arrastre

En `WeeklyRealProgressCarryoverService`, el caso ambiguo (fila sin testigo, valor que no coincide
ni con el acumulado anterior ni con el calculado) deja de resolverse por sospecha:

- Se consulta la bitácora por `(tabla=programa_consolidado, project_id, Semana=semana destino,
  unique_id, campo)`, la más reciente.
- Si existe un registro y su `valor_nuevo` coincide con lo que hay hoy en la fila: es un hecho
  comprobado de edición manual. Se respeta con certeza.
- Si no existe ningún registro para esa actividad y ese campo: la bitácora nunca la vio. Puede ser
  una fila anterior a este despliegue, o un residuo del defecto de julio — no se puede distinguir.
  Se mantiene el criterio actual (comparar contra acumulado anterior y calculado; ante la duda,
  respetar).

El caso con testigo (introducido el 2026-08-25) no cambia: sigue siendo la vía normal para
actividades tocadas después de esa fecha. La bitácora resuelve específicamente el caso que el
testigo no puede: filas que nunca pasaron por el arrastre nuevo.

### 4. Cómo se prueba

TDD, prueba antes que código, igual que el arreglo del arrastre.

**El servicio que escribe** (nivel `db`):
- Editar un campo deja exactamente una fila en la bitácora, con el antes y el después correctos.
- Editar con el mismo valor no deja ninguna fila.
- Editar dos campos a la vez dentro de una misma llamada deja dos filas separadas.
- Un fallo a mitad de la transacción no deja ni la bitácora ni el dato real a medias.

**El arrastre con la bitácora puesta** (nivel `db`):
- Los casos ya cubiertos por `CarryoverAvanceSemanalTest` (con testigo, idempotencia, preservar
  edición manual) siguen pasando sin cambios.
- Caso nuevo: fila sin testigo, valor que no coincide con nada, pero la bitácora confirma la
  edición — debe respetarse con certeza, marcado distinto de "se respetó por sospecha".

**Los tres controladores reales** (nivel `http`):
- Editar desde programa general, desde semanal y desde intermedia efectivamente pasa por el
  servicio nuevo y deja su fila en la bitácora. No basta con probar el servicio aislado: el riesgo
  real es que alguno de los tres siguiera escribiendo por el camino viejo sin que nadie lo notara.

## Decisiones explícitas de esta ronda

- Los cinco campos de una vez, no solo `Ejecutado` — decisión del usuario.
- Sin pantalla de historial en esta ronda — decisión del usuario.
- Centralizado en un único servicio de escritura, no instrumentado en cada controlador por
  separado, y no un trigger de base de datos — decisión del usuario, tras comparar ambas.
- No repara las 27 actividades ya congeladas en producción — decisión explícita del usuario.

## Siguiente paso

Este spec pasa a `writing-plans` para el plan de implementación.
