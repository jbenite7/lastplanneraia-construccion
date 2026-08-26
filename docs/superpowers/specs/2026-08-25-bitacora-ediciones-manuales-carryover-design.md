---
capa: fuente
tipo: spec
estado: propuesta
fecha: 2026-08-25
areas: [lps, arquitectura]
tags: [carryover, auditoria, brainstorming]
version: v0.99
fuente: sesión de brainstorming 2026-08-25
resumen: "Bitácora del avance editado a mano en Programa General, para que el arrastre deje de adivinar en el caso ambiguo"
---

# Bitácora del avance editado a mano en Programa General

> Reescrito entero en la cuarta ronda de brainstorming (2026-08-25) tras recortar el alcance. Las
> versiones v0 a v0.95 cubrían cinco campos en dos tablas y tres controladores; ese alcance estaba
> inflado y se redujo a lo que de verdad resuelve el problema medido. El historial está en git.

## El problema

El 2026-08-25 se corrigió un defecto en `WeeklyRealProgressCarryoverService` (commit `c1e3365e`):
un avance reportado en Programación Semanal después de que el Programa General de la semana
siguiente ya se hubiera abierto una vez no se sumaba nunca. El arreglo introdujo un testigo
(`Ejecutado_Carryover`) que guarda el último valor que el propio servicio escribió, para distinguir
su propia escritura de una edición real del residente.

Ese arreglo dejó un caso sin resolver, por diseño: cuando una fila no tiene todavía testigo y su
valor no coincide ni con el acumulado de la semana anterior ni con lo que el servicio calcularía
ahora, el código **adivina** que fue una edición manual y la respeta, sin poder confirmarlo. Medido
en producción el mismo día: de 32 actividades con el acumulado corto, 27 caen en ese caso ambiguo.

La causa de fondo es que el sistema no guarda quién cambió el avance ni cuándo. Sin esa evidencia,
dos historias distintas —una edición real del residente, o un residuo del defecto de julio—
producen exactamente el mismo dato. Este spec cierra esa brecha hacia adelante.

## Alcance

**Un campo, una tabla, un punto de escritura:** `Ejecutado` en `programa_consolidado`, editado por
una persona a través de `GeneralApiController::update()`.

Ese es el campo donde el arrastre tiene la ambigüedad, la tabla donde la evalúa, y el único sitio
donde una persona lo edita — verificado por código en el mapeo del 2026-08-25, no supuesto. El
endpoint `update()` lo comparten dos pantallas: Programa General
(`public/js/modules/programa_general/hot.js`) y Actualizar Programa General
(`public/js/modules/programa_actualizar/hot_actualizar.js`). Instrumentar el endpoint cubre las dos.

### Qué se descartó, y por qué

Se evaluó y se dejó fuera. Está escrito para que nadie lo vuelva a proponer sin conocer la razón:

| Descartado | Por qué |
|---|---|
| Los otros cuatro campos editables (`unidad`, `cantidad_ppto`, `Responsable_AIA`, `Sub_Contratista`) | No aportan nada a la ambigüedad del arrastre: su comparación de "¿esto lo editó una persona?" ya se hace contra el valor que el arrastre calcularía ahora, no contra la semana anterior, así que nunca caen en la trampa que tenía `Ejecutado`. Recortado por decisión de Felipe el 2026-08-25, tras señalar que el alcance se había inflado. |
| La tabla `programacion_semanal` | El arrastre **lee** de ahí para calcular cuánto sumar; no evalúa ambigüedad sobre sus valores. Registrar ediciones ahí no cambiaría ninguna decisión del arrastre. |
| `ProgramacionIntermediaController::applySharedConstraints()` | Solo escribe responsable y subcontratista, ninguno de los cuales llegó al alcance final. |
| `SemanalApiController::autoprogramar()` / `sanear()` / `nuevo()` / `duplicar()` | Sincronización automática que copia valores desde `programa_consolidado` — el valor nunca sale de `$_POST`. No son ediciones humanas y registrarlas ensuciaría la bitácora con falsos positivos. |
| `ProgramChangeDetector` | Verificado el 2026-08-25: sus dos `UPDATE` solo tocan estado de cumplimiento; los campos operativos aparecen únicamente en `INSERT` que crean filas nuevas copiando del programa general. Sincronización automática. |
| `SemanalApiController::estadoEjecucion()` | Toca `Ejecutado_Siguiente_Semana`, una columna distinta de `Ejecutado`. Fácil de confundir; no es el campo auditado. |
| `GeneralApiController::deleteUpdate()` — "Eliminar Actualización" | Deshace la actualización de una semana **completa** (su `WHERE` no lleva actividad). Registrarlo dejaría cientos de filas marcadas como editadas a mano —282 actividades en Da Porto— y el arrastre las respetaría para siempre: congelaría la semana entera, que es el mismo defecto que este trabajo arregla, reintroducido por la puerta de atrás. Si algún día se quiere trazar esa acción, va como **un** evento en `general_auditoria_acciones`, no como cientos de ediciones de campo. |

**Sin pantalla.** La bitácora es solo para que `WeeklyRealProgressCarryoverService` la consulte. No
hay vista de historial para el residente ni el director de obra en esta ronda — decisión de Felipe.

**No repara lo viejo.** Las 27 actividades ya congeladas en producción no se arreglan con esto: la
bitácora empieza a contar desde que se despliega. Se resuelven por revisión manual, en un frente
aparte. **Y ojo con esa revisión:** guardar el mismo valor no cuenta como edición (ver sección 2),
así que confirmar un número sin cambiarlo no deja rastro. Para que quede prueba de que alguien
revisó y confirmó, hay que cambiar el número, aunque sea mínimamente.

## Diseño

### 1. Qué se guarda

Tabla nueva `pg_avance_edicion_manual`, de nombre fijo:

| Columna | Qué guarda |
|---|---|
| `project_id` | el proyecto |
| `Semana` | la semana de la fila editada |
| `unique_id` | el identificador de la actividad |
| `valor_anterior` | el ratio que tenía antes |
| `valor_nuevo` | el ratio que quedó |
| `usuario` | quién lo hizo |
| `fecha` | cuándo |

Sin columnas `tabla` ni `campo`: con un solo campo y una sola tabla en alcance, serían constantes.
Si algún día hay que ampliar, agregarlas es una migración aditiva trivial — la misma forma que ya
se usó para `Ejecutado_Carryover`.

**Nombre fijo, no `TableResolver`.** Se verificó cómo `Database::logActivity` nombra
`general_auditoria_acciones` —la única tabla de auditoría que ya existe en el repo— y lo hace por
nombre fijo, sin pasar nunca por `TableResolver`, ni siquiera en el modo legado por proyecto. Esta
tabla sigue esa convención.

Índice por `(project_id, Semana, unique_id)`, que es exactamente como la consulta el arrastre.

**Sin límite de tiempo.** No hay borrado ni ventana de retención — decisión de Felipe. Es una tabla
angosta con pocas filas por semana, y el arrastre solo mira la más reciente por actividad.

### 2. Quién escribe ahí

Un servicio nuevo, `App\Services\PgAvanceEdicionManualService`, con **dos llamadas que envuelven**
la lógica que `GeneralApiController::update()` ya tiene — no una que la reemplace.

El motivo de envolver en vez de reemplazar: `update()` ejecuta **dos** `UPDATE` seguidos en la
misma petición. El primero guarda lo que la persona tecleó; el segundo —la herencia, activada
cuando la actividad está asociada a una de la semana anterior— lo reemplaza con el valor histórico.
Un servicio que envolviera el primero registraría el valor tecleado, no el que quedó. Envolver el
segundo tampoco sirve: es condicional y no siempre se ejecuta.

El flujo:

1. **Al entrar**, `capturarAvancePrevio()` bloquea la fila y lee `Ejecutado` (`SELECT ... FOR
   UPDATE`) dentro de la transacción que ya abarca la petición. El bloqueo evita que dos ediciones
   casi simultáneas de la misma actividad se pisen sin verse.
2. **La lógica existente corre sin cambios** — uno o dos `UPDATE`, con sus condiciones actuales.
3. **Antes de cerrar**, `registrarSiCambio()` relee `Ejecutado`, lo compara contra el capturado y,
   si difiere, inserta la fila en la bitácora. Si quedó igual, no se registra nada: guardar sin
   cambiar no es una edición.

La comparación usa la misma tolerancia de 0.001 que ya usa `WeeklyRealProgressCarryoverService`
para decidir si algo cambió. Dos criterios distintos para "cambió" sobre el mismo dato serían una
inconsistencia, no una elección.

### La herencia: solo se firma cuando la asociación cambió

`hot_actualizar.js:526` manda la bandera de herencia en **toda** edición de esa pantalla, no solo
al cambiar la asociación. Así que si el residente corrige una fecha de una actividad ya asociada,
la herencia le reemplaza el avance igual, sin que él lo pidiera. Registrar eso como "edición
manual" llenaría la bitácora de firmas falsas: quedaría constando que una persona decidió ese
número cuando en realidad nunca lo tocó.

**Decisión de Felipe, 2026-08-25:** el reemplazo por herencia se registra **solo si la asociación
cambió en esa misma petición**. Si la asociación ya estaba puesta de antes, el reemplazo fue un
efecto secundario y no se firma.

Se evaluó la alternativa —cambiar la pantalla para que la herencia se dispare solo al asociar— y se
descartó por ser un cambio al comportamiento de un módulo de uso diario, que merece su propia
decisión y sus propias pruebas en obra, no ir de pasajero en un trabajo de auditoría. Queda anotado
como frente aparte en `TASKS.md`.

**Corregido el 2026-08-25.** Una versión anterior de este párrafo justificaba el descarte diciendo
que `autoAssociate()` "no hereda" y que cambiar el disparo dejaría sin avance a las actividades
asociadas en lote. Eso es falso, y el error fue mirar el botón sin seguir la cadena completa:
`autoAssociate()` escribe `programaAnteriorAsociar`, y el arrastre usa ese campo como su **primera**
vía de mapeo (`WeeklyRealProgressCarryoverService`, `resolveTargetSource()`), así que el avance
llega igual —solo que cuando corre el arrastre, no en el instante de asociar—. Lo que sí difiere
entre los dos caminos son las restricciones, `Estado_Restricciones`, `Observaciones`,
`codigo_actividad` y `medir_productividad`: la herencia manual los copia y el arrastre no. Eso es
una inconsistencia real del producto, anotada como frente aparte; no afecta a este spec, que solo
mira `Ejecutado`.

Para implementarlo, `capturarAvancePrevio()` guarda también `programaAnteriorAsociar`, y la regla al
cerrar es:

| Situación | ¿Se firma? |
|---|---|
| `Ejecutado` no cambió | No — guardar sin cambiar no es una edición |
| Cambió, y no hubo herencia (edición directa) | Sí |
| Cambió por herencia, y la asociación **también** cambió en esta petición | Sí — la persona decidió asociar, y ese es el valor que quiso traer |
| Cambió por herencia, y la asociación ya estaba puesta de antes | **No** — efecto secundario, la persona no decidió ese número |

El controlador le informa al servicio si la herencia llegó a aplicarse; esa condición ya existe en
`GeneralApiController::update()` y no hay que inventarla.

### 3. Cómo lo consume el arrastre

En `WeeklyRealProgressCarryoverService`, el caso ambiguo —fila sin testigo, valor que no coincide
ni con el acumulado anterior ni con el calculado— deja de resolverse por sospecha:

- Se consulta la bitácora por `(project_id, Semana de destino, unique_id)`, la más reciente.
- Si existe y su `valor_nuevo` coincide con lo que hay hoy en la fila: hecho comprobado de edición
  manual. Se respeta con certeza.
- Si no existe: la bitácora nunca vio esa fila. Puede ser anterior al despliegue, o un residuo del
  defecto de julio — no se puede distinguir. Se mantiene el criterio actual (comparar contra
  acumulado anterior y calculado; ante la duda, respetar).

El caso con testigo no cambia: sigue siendo la vía normal para actividades tocadas después del
2026-08-25. La bitácora resuelve solo lo que el testigo no puede.

**Restricción que no se puede romper al implementar:** la bitácora se consulta **solo en el caso
ambiguo**, nunca antes. El valor que deja la herencia suele ser exactamente el acumulado de la
semana anterior, y el arrastre ya lo reconoce como "no editado" sin mirar la bitácora, así que
recalcula y suma el avance con normalidad. Si alguien cambiara el orden y consultara la bitácora
siempre, toda actividad recién asociada quedaría marcada como editada a mano y dejaría de recibir
el avance de la semanal — el defecto original, reintroducido por otra vía.

### 4. Cómo se prueba

TDD, prueba antes que código, igual que el arreglo del arrastre.

**El servicio que escribe** (nivel `db`):
- Editar el avance deja exactamente una fila en la bitácora, con el antes y el después correctos.
- Guardar el mismo valor no deja ninguna fila.
- Un fallo a mitad de la transacción no deja ni la bitácora ni el dato real a medias.
- Con herencia activa **y asociación cambiada** en la misma petición: se registra el valor heredado
  que quedó, no el tecleado.
- Con herencia activa y **asociación sin cambios** —el residente vino a corregir otra cosa—: no se
  registra nada, aunque el avance haya cambiado. Es la prueba que impide que el cuaderno se llene
  de firmas de gente que nunca tocó el avance.

**El arrastre con la bitácora puesta** (nivel `db`):
- Los cinco casos de `CarryoverAvanceSemanalTest` siguen pasando sin cambios.
- Caso nuevo: fila sin testigo, valor que no coincide con nada, pero la bitácora confirma la
  edición — se respeta con certeza.
- Caso de no regresión, el más importante: actividad recién asociada cuyo valor heredado coincide
  con el acumulado anterior — el arrastre debe recalcular y sumar el avance, **no** respetarla. Es
  la prueba que impide que alguien mueva la consulta de la bitácora fuera del caso ambiguo.

**El endpoint real** (nivel `http`):
- Editar el avance desde `POST /api/general/update` deja su fila en la bitácora. No basta con
  probar el servicio aislado: el riesgo real es que el endpoint siga escribiendo por el camino
  viejo sin que nadie lo note.

## Decisiones de Felipe registradas

- Los cinco campos → **recortado a solo `Ejecutado`** (2026-08-25), tras señalar que el alcance se
  había inflado respecto al problema original.
- Sin pantalla de historial en esta ronda.
- Centralizado en un servicio, no instrumentado por controlador ni con un trigger de base de datos.
- No repara las 27 actividades ya congeladas.
- Solo ediciones humanas: el arrastre no registra sus propias escrituras.
- Sin límite de retención.
- La herencia cuenta como edición manual **solo si la asociación cambió en esa misma petición**,
  registrando el valor final. Si la asociación ya estaba puesta, no se firma.
- No se toca el comportamiento de la pantalla de Actualizar ni el de "Auto-Asociar": ese defecto
  queda como frente aparte.
- "Eliminar Actualización" no cuenta.

## Siguiente paso

Este spec pasa a `writing-plans` para el plan de implementación.
