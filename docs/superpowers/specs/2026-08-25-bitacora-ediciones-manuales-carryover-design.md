---
capa: fuente
tipo: spec
estado: cerrado
fecha: 2026-08-25
areas: [lps, arquitectura]
version: v2
fuente: sesión de brainstorming 2026-08-25 y 2026-08-26
resumen: "Bitácora del avance editado a mano en Programa General, como registro consultable — no cambia decisiones del arrastre, eso se descartó al implementar"
---

# Bitácora del avance editado a mano en Programa General

> Reescrito entero en la cuarta ronda de brainstorming (2026-08-25) tras recortar el alcance. Las
> versiones v0 a v0.95 cubrían cinco campos en dos tablas y tres controladores; ese alcance estaba
> inflado y se redujo a lo que de verdad resuelve el problema medido. El historial está en git.
>
> **Corrección del 2026-08-26, durante la implementación.** Un test candado de la Task 5 encontró
> que la sección 3 —el arrastre consultando la bitácora para decidir— era lógica sin efecto: en el
> único caso donde se consultaría, el resultado ya estaba decidido de antemano y la consulta no
> podía cambiarlo. Se revirtió el código correspondiente y se reescribió la sección 3 con el
> hallazgo. Lo que este spec entrega desde esta versión es la bitácora como registro consultable,
> no como corrector automático.

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

1. **Al entrar**, `capturarAvancePrevio()` lee `Ejecutado` y `programaAnteriorAsociar`. En la
   práctica se amplía el `SELECT Titulo` que `update()` ya hace para validar la fila, así que no
   agrega una consulta nueva.
2. **La lógica existente corre sin cambios** — uno o dos `UPDATE`, con sus condiciones actuales.
3. **Antes de cerrar**, `registrarSiCambio()` relee `Ejecutado`, lo compara contra el capturado y,
   si difiere, inserta la fila en la bitácora. Si quedó igual, no se registra nada: guardar sin
   cambiar no es una edición.

La comparación usa la misma tolerancia de 0.001 que ya usa `WeeklyRealProgressCarryoverService`
para decidir si algo cambió. Dos criterios distintos para "cambió" sobre el mismo dato serían una
inconsistencia, no una elección.

**Sin transacción envolvente, decisión técnica del 2026-08-25 con su límite declarado.** Las
versiones anteriores de este spec daban por hecho que `update()` corría dentro de una transacción y
que la captura podía bloquear la fila (`SELECT ... FOR UPDATE`). Se verificó y es falso:
`GeneralApiController::update()` no abre transacción en ningún punto. Envolverlo entero sería un
cambio de riesgo real sobre el camino crítico de edición del programa —hace varias escrituras,
llama a servicios y recalcula estados— a cambio de atomicidad en un caso poco frecuente. No se
hace.

Consecuencias que se aceptan a sabiendas:

- Si dos personas editan la misma actividad casi al mismo tiempo, las dos capturan el mismo valor
  previo y las dos firman. La bitácora seguirá diciendo lo que el arrastre necesita —"una persona
  puso este número"—, pero un `valor_anterior` puede quedar desactualizado.
- Si el `INSERT` de la bitácora falla, el avance queda guardado **sin** firma, que es justo la
  situación que este trabajo quiere evitar. Por eso ese fallo **no puede pasar en silencio**: se
  registra en el log de errores del servidor, para que se note y se pueda investigar.

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

### 3. El arrastre NO consulta la bitácora — descartado en implementación, 2026-08-26

Esta sección decía que `WeeklyRealProgressCarryoverService` consultaría la bitácora en su caso
ambiguo para dejar de "adivinar". Se implementó, un test candado la puso a prueba, y el candado
encontró dos problemas — uno de orden, corregible, y uno de fondo, no corregible sin cambiar lo que
este trabajo promete entregar.

**El problema de fondo:** en el caso verdaderamente ambiguo —sin testigo, y el valor no coincide ni
con el acumulado de origen ni con el calculado—, el criterio ya existente siempre preserva el valor
(`ante la duda, respetar`, decisión tomada el 2026-08-25 al corregir el defecto del testigo). Con
firma en la bitácora, se preserva. Sin firma, también se preserva. **La consulta no puede cambiar
el resultado**, porque el único resultado posible en esa rama ya era preservar antes de que la
bitácora existiera. Consultarla ahí es lógica sin efecto — código que aparenta decidir algo que ya
estaba decidido.

Y el caso donde sí importaría —sin firma, tratar como sospechoso y recalcular en vez de preservar—
es exactamente la alternativa que se evaluó y se descartó en el diseño original de este spec: haría
que confirmar sin cambiar el número deje de servir, y pisaría ediciones humanas legítimas hechas
antes de que la bitácora existiera, que es el defecto de julio otra vez.

**Decisión de Felipe, 2026-08-26:** se termina la bitácora como registro consultable —la tabla y el
servicio que la escribe, Tareas 1 a 3, ya construidas y revisadas—, sin que el arrastre la use para
decidir nada. Su valor no es corregir automáticamente: es que la próxima vez que un avance no
cuadre, alguien pueda consultar quién lo escribió y cuándo, en vez de reconstruirlo a mano como se
hizo el 2026-08-25 para llegar hasta esta misma bitácora.

El código que se había agregado a `WeeklyRealProgressCarryoverService` fue revertido con
`git revert`; el archivo quedó idéntico al commit `c1e3365e`, el del arreglo del testigo. Verificado
con `git diff c1e3365e -- src/Services/WeeklyRealProgressCarryoverService.php` vacío.

**Si en el futuro se quiere que la bitácora sí cambie una decisión**, la única forma honesta es la
alternativa ya descartada arriba, acotada a partir de la fecha de despliegue: sin firma **y**
posterior al despliegue, tratar como sospechoso. Es diseño nuevo, con su propio grillado — no una
continuación de este trabajo.

### 4. Cómo se prueba

TDD, prueba antes que código, igual que el arreglo del arrastre.

**El servicio que escribe** (nivel `db`):
- Editar el avance deja exactamente una fila en la bitácora, con el antes y el después correctos.
- Guardar el mismo valor no deja ninguna fila.
- Si el registro en la bitácora falla, el fallo queda en el log de errores y no se traga en
  silencio — una edición sin firma es justo lo que este trabajo quiere evitar.
- Con herencia activa **y asociación cambiada** en la misma petición: se registra el valor heredado
  que quedó, no el tecleado.
- Con herencia activa y **asociación sin cambios** —el residente vino a corregir otra cosa—: no se
  registra nada, aunque el avance haya cambiado. Es la prueba que impide que el cuaderno se llene
  de firmas de gente que nunca tocó el avance.

**El arrastre no cambia, y eso se comprueba.** `CarryoverAvanceSemanalTest.php` queda exactamente
igual al del arreglo del testigo (`c1e3365e`) — sus cinco casos originales, sin ningún caso nuevo
relacionado con la bitácora. Es la prueba de que la reversión del 2026-08-26 no dejó residuo.

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
