---
capa: fuente
tipo: spec
estado: propuesta
fecha: 2026-08-25
areas: [lps, arquitectura]
tags: [carryover, auditoria, brainstorming]
version: v0.8
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

**Consecuencia a tener en cuenta para esa revisión manual:** guardar el mismo valor no cuenta como
edición — es una decisión de diseño de este spec, no un detalle menor. Si al revisar una de las 27
alguien concluye que el número que ya está ahí es el correcto, no basta con volver a guardarlo para
dejar esa confirmación registrada: como el valor no cambia, la bitácora no registra nada. Para que
quede prueba de que alguien la revisó y la confirmó, hay que cambiar el número, aunque sea en un
detalle mínimo. No hay hoy una forma de "confirmar sin tocar".

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

### Corrección mayor: la premisa de "tres controladores, un punto de escritura cada uno" era falsa

Un mapeo exhaustivo del 2026-08-25 (agente de exploración, no supuesto) encontró **al menos nueve
sitios de escritura reales** sobre los cinco campos, repartidos en los tres archivos. No todos son
ediciones de una persona: varios son sincronización automática que copia valores de
`programa_consolidado` sin que nadie los teclee. Contar esos como "edición manual" ensuciaría la
bitácora con falsos positivos — el propósito de este trabajo es que el arrastre sepa con certeza si
una persona tocó el dato, no si *cualquier proceso* lo tocó.

Clasificación por sitio, y su decisión:

| Sitio | Qué es | ¿Cuenta como edición manual? |
|---|---|---|
| `GeneralApiController::update()` — escritura directa | La persona teclea el valor | Sí |
| `GeneralApiController::update()` — herencia (activada por la casilla "asociar a actividad anterior") | La persona dispara el reemplazo, pero el valor final viene de la actividad de la semana pasada, no de lo que tecleó | **Sí, decisión 2026-08-25** — se guarda el valor final que quedó en pantalla, no el tecleado antes del reemplazo. Es una sola acción de la persona con efecto en dos pasos, no dos ediciones separadas. |
| `SemanalApiController::modificar()` | La persona teclea el valor (solo Responsable/Subcontratista; este método nunca toca Ejecutado, unidad ni cantidad_ppto) | Sí |
| `ProgramacionIntermediaController::applySharedConstraints()` | La persona teclea el valor, en lote | Sí |
| `SemanalApiController::autoprogramar()` / `sanear()` | Copia automática desde `programa_consolidado`, sin intervención humana — confirmado por código: el valor nunca sale de `$_POST` | No |
| `SemanalApiController::nuevo()` / `duplicar()` | Crea una fila copiando valores de `programa_consolidado`; no edita una fila existente | No |
| `GeneralApiController::deleteUpdate()` | "Eliminar Actualización": deshace la actualización de una semana **completa** (su `WHERE` no lleva actividad) | **No, decisión 2026-08-25.** No es una edición de un valor, es un deshacer masivo. Registrarlo dejaría cientos de filas marcadas como editadas a mano —282 actividades × 3 campos en Da Porto— y el arrastre las respetaría para siempre: congelaría la semana entera, que es el mismo defecto que este trabajo arregla, reintroducido por la puerta de atrás. Si algún día se quiere trazar esta acción, va como **un** evento en `general_auditoria_acciones`, no como cientos de ediciones de campo. |
| `SemanalApiController::estadoEjecucion()` | Toca `Ejecutado_Siguiente_Semana`, una columna distinta de `Ejecutado` | Fuera de alcance — no es uno de los 5 campos |

**Los dos casos de arriba viven en el mismo módulo: Actualizar Programa General**
(`/programa-general-actualizar`). La herencia se dispara desde
`public/js/modules/programa_actualizar/hot_actualizar.js`, y "Eliminar Actualización" desde
`views/programa-general-actualizar/programaGeneralActualizar.view.php`. La pantalla de Programa
General normal (`public/js/modules/programa_general/hot.js`) llama al mismo endpoint `update()`,
pero **nunca** manda `editarActividadAsociar`, así que ahí la herencia no ocurre.

**La herencia se dispara en toda edición de esa pantalla, no solo al cambiar la asociación.**
`hot_actualizar.js:526` hace `formData.append('editarActividadAsociar', '1')` de forma
incondicional, en cualquier edición de celda. Si el residente corrige una fecha de una actividad ya
asociada, el reemplazo de los cinco campos ocurre igual, sin que él lo pida ni lo vea venir. No es
un caso raro: es el comportamiento normal del módulo.

**Restricción de diseño que se deriva de lo anterior, y que no se puede romper al implementar:** la
bitácora se consulta **solo en el caso ambiguo**, nunca antes. El valor que deja la herencia suele
ser exactamente el acumulado de la semana anterior, y el arrastre ya lo reconoce como "no editado"
sin mirar la bitácora, así que recalcula y suma el avance con normalidad. Si alguien cambiara el
orden y consultara la bitácora **siempre**, toda actividad recién asociada quedaría marcada como
editada a mano y dejaría de recibir el avance de la semanal — el defecto original, reintroducido.

**Pendiente de verificar:** `SemanalApiController::autoProgram()` delega a una clase externa,
`ProgramChangeDetector`, no examinada en este mapeo — no se sabe todavía si también escribe alguno
de los 5 campos.

**Nota aparte, sin relación con este trabajo:** `SemanalApiController::eliminar()` filtra su
`UPDATE` solo por `project_id + row_id`, sin `Semana` — asimetría respecto al resto de la clase que
huele a error preexistente. No se toca aquí; queda para revisión aparte.

## Diseño

### 1. Qué se guarda

Una tabla nueva y angosta — `campo_edicion_manual` —, con una sola pregunta en mente: "¿alguien
tocó este campo, y a qué?"

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

**Sin límite de tiempo.** No hay borrado ni ventana de retención — decisión del usuario en la
segunda ronda de brainstorming (2026-08-25). Es una tabla angosta, con pocas filas por semana, y
el arrastre solo mira la más reciente por actividad; guardarla entera no cuesta nada hoy. Si el
volumen algún día lo justifica, se revisa aparte.

La comparación campo por campo reutiliza el mismo criterio que ya usa
`WeeklyRealProgressCarryoverService` para decidir si algo cambió: tolerancia de 0.001 para los
campos numéricos (`Ejecutado`, `cantidad_ppto`), y texto recortado sin distinguir mayúsculas para
`unidad`, `Responsable_AIA`, `Sub_Contratista`. Es una decisión técnica, no de producto: dos
criterios distintos para "cambió" en el mismo dato serían una inconsistencia, no una elección.

**Corregido en esta ronda.** La v0.5 decía que la tabla se agregaba a `TableResolver::$validTables`,
igual que las tablas operativas del proyecto. Es un error: se verificó cómo `Database::logActivity`
nombra `general_auditoria_acciones` —la única tabla de auditoría que ya existe en el repo— y lo hace
por nombre fijo, sin pasar nunca por `TableResolver`, ni siquiera en el modo legado por proyecto.
`campo_edicion_manual` sigue esa misma convención: nombre fijo, siempre, sin importar
`USE_GLOBAL_TABLES`. De paso resuelve una pregunta que no hizo falta subir: el servicio identifica
`usuario` leyendo `$_SESSION['usuario']`, igual que ya hace `Database::logActivity` — se verificó que
los tres controladores exigen sesión autenticada antes de llegar a estas escrituras (dos con
`requireAuth()`, `SemanalApiController` con el helper legado `rbac_guard_require_permission()`), así
que ese valor siempre está disponible.

### 2. Quién escribe ahí

Un servicio nuevo — `App\Services\CampoManualAuditoriaService` — que reemplaza el `UPDATE` manual
de estos cinco campos en los tres controladores listados arriba. Cada controlador le entrega:
tabla, proyecto, semana, actividad, los campos que quiere cambiar con sus valores nuevos, y el
usuario de la sesión.

El servicio, envuelto en una transacción (todo o nada):

1. Bloquea la fila y lee sus valores actuales dentro de la misma transacción (`SELECT ... FOR
   UPDATE`), para que dos ediciones casi simultáneas de la misma actividad no se pisen sin que
   ninguna de las dos vea el cambio de la otra.
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

**Asimetría entre escribir y consultar, confirmada en esta ronda.** Los cinco campos se registran,
pero hoy el arrastre solo consulta la bitácora para `Ejecutado`. Se verificó el código vigente de
`unidad`, `cantidad_ppto`, `Responsable_AIA` y `Sub_Contratista`: su comparación de "¿esto lo editó
una persona?" ya se hace contra el valor que el arrastre calcularía en este momento, no contra el
acumulado de la semana anterior — por eso nunca caen en la trampa que tenía `Ejecutado`, y no
existe ningún caso ambiguo que resolver para ellos hoy. Se decidió igual registrar los cinco: por si
alguno de esos cuatro desarrolla una ambigüedad parecida más adelante, y para no tener que volver a
tocar los tres controladores si ese día llega.

**Las escrituras del propio arrastre no quedan en la bitácora.** Se preguntó explícitamente en la
segunda ronda de brainstorming (2026-08-25) si el servicio debía registrar también sus propias
escrituras, marcadas como hechas por "sistema" — eso habría dado certeza total sobre quién escribió
por última vez, sin depender del testigo. El usuario decidió que no, por ahora: la bitácora se
queda acotada a detectar ediciones humanas, y el testigo sigue siendo el mecanismo para todo lo
demás. Si más adelante se quiere esa certeza total, es una ampliación de alcance, no un ajuste.

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
