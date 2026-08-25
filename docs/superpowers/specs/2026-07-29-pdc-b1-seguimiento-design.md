---
capa: fuente
tipo: spec
estado: cerrado
fecha: 2026-07-29
areas: [pdc]
fuente: docs/superpowers/specs/2026-07-29-pdc-b1-seguimiento-design.md
resumen: El plan de compras dice cuándo debería ocurrir cada paso de contratación de cada paquete. Nadie puede decir todavía cuándo ocurrió. Sin ese dato no hay atraso…
---

# PDC v2 · Fase B1 — Seguimiento al Plan de Compras — Design

**Fecha:** 2026-07-29
**Estado:** **implementado y en `main`** (`f7cef87`, `5ee2e49`, `9f2790c`, `92c5c13`, `a4d0c75`, `bfe7055`). `pdc_plan_paso` guarda `fecha_real` y recalcular ya no borra lo que sí ocurrió.
**Depende de:** A4 (plan de fechas), A4.1 (pasos configurables con identidad `paso_id`), preparación de B1
(upsert de `pdc_plan_paso`, responsable como usuario) — todo ya en `main`.

## Problema

El plan de compras dice **cuándo debería** ocurrir cada paso de contratación de cada paquete. Nadie puede
decir todavía **cuándo ocurrió**. Sin ese dato no hay atraso medible, no hay semáforo (B2) y la Torre de
Control (C1) no tiene de qué alimentarse.

B1 registra el avance real y lo enfrenta al plan. No alerta, no reprograma, no notifica: eso es B2 y B3.

## Decisiones cerradas en el grilleo

| Decisión | Valor |
|---|---|
| Dato de avance | **Solo `fecha_real`** por paso. Con fecha = cumplido, sin fecha = pendiente. Ni estado ni nota |
| Forma de la vista | Lista de paquetes + **panel de detalle** con los pasos en vertical |
| Efecto en cadena | Lo programado **no se toca nunca**; se muestra una **proyección** calculada al vuelo, no guardada |
| Quién registra | Cualquiera con permiso de editar, **con auditoría** (`registrado_por`, `registrado_at`) |
| Reamarre | El avance **se conserva**; solo se limpian las fechas programadas |
| Filtros del día 1 | Mis paquetes · frente · estado de avance · atraso |
| Datos de prueba | **Solo DAPORTO (73)**, y **se puede dejar alterado**. Sin proyectos sintéticos |

## Modelo de datos

Ninguna tabla nueva. `pdc_plan_paso` gana tres columnas:

| Columna | Tipo | Significado |
|---|---|---|
| `fecha_real` | `DATE NULL` | El paso ocurrió ese día. `NULL` = pendiente |
| `registrado_por` | `VARCHAR(100) NOT NULL DEFAULT ''` | Usuario que registró la fecha |
| `registrado_at` | `DATETIME NULL` | Momento del registro |

Y `fecha_inicio` / `fecha_fin` pasan de `NOT NULL` a **`NULL` permitido** — necesario para la regla de
reamarre (ver abajo): una fila puede llevar avance real y no tener, de momento, fechas programadas.

**Por qué cuelgan de `pdc_plan_paso` y no de una tabla aparte:** A4.1 ya le dio a esa fila una identidad
estable (`paso_id`, no la posición) y el `calcular()` de la preparación de B1 hace upsert listando solo las
cuatro columnas programadas. Lo que no se lista, MySQL lo conserva. Es decir: **estas tres columnas
sobreviven a cualquier recálculo sin tocar `PlanFechasService`**. Ese contrato ya está probado en
`tests/test_pdc_v2_plan_fechas.php`; B1 lo hereda, no lo reinventa.

**Por qué no se guarda el estado:** «en curso / cumplido» se deduce de la fecha. Un estado persistido se
desincroniza de su fecha el primer día en que alguien corrija una y olvide la otra, y entonces hay dos
verdades y ninguna manda.

Migración `database/migrations/20260729_pdc_v2_seguimiento_avance.sql`, con guardias por
`information_schema` que converjan desde cualquier punto de partida (modelo:
`20260728_pdc_v2_plan_fechas.sql`). No hay backfill: no existe ningún dato de avance que migrar.

## La proyección

Se calcula en cada lectura, nunca se persiste. Recorriendo los pasos del paquete en orden:

1. El cursor arranca en `pdc_plan_paquete.fecha_arranque`.
2. Paso **con** `fecha_real`: su fecha proyectada **es** la real, y el cursor salta a ella.
3. Paso **sin** `fecha_real`: proyectado inicio = cursor, proyectado fin = cursor + `dias`; el cursor avanza.
4. Si al llegar al primer paso pendiente el cursor quedara **antes de hoy**, se adelanta a hoy: proyectar
   hacia el pasado un trabajo que aún no ha ocurrido no informa de nada.

El desfase de un paso cumplido es `fecha_real − fecha_fin` en días (positivo = tarde). Lo programado es la
línea base y por eso es intocable: si se reescribiera con lo real, al final del proyecto nadie podría decir
cuánto se atrasó respecto de lo prometido.

Derivados de paquete, todos calculados, ninguno guardado:

- **Estado:** sin empezar (0 pasos con fecha) · en curso · terminado (todos con fecha).
- **Atraso:** hay atraso si algún paso cumplido llegó tarde **o** algún paso pendiente tiene `fecha_fin`
  programada anterior a hoy.
- **Paso actual:** el primero sin `fecha_real`.

Esta lógica es aritmética de fechas sin base de datos: vive en un módulo propio y se prueba con tests
unitarios puros, en PHP (`SeguimientoService`) y en la SPA (`src/lib/seguimiento.ts`).

## Servicio y API

Servicio nuevo `src/Services/Pdc/SeguimientoService.php`. **No** se amplía `PlanFechasService`: ya pasa de
1.600 líneas y hace otra cosa — uno calcula el plan, el otro registra lo que ocurrió. Son responsabilidades
separables y conviene que lo estén.

Controlador nuevo `src/Controllers/Api/PlanComprasSeguimientoController.php`, reusando el trait
`PlanComprasJsonRespuestas` y los guards existentes. **RBAC igual al del plan** —lectura
`lps.paquetes_contratacion.ver`, escritura `lps.paquetes_contratacion.editar` + CSRF `plan_compras_v2`—,
no permisos nuevos: quien puede ver y editar el plan de compras es exactamente quien opera su seguimiento.

| Endpoint | Devuelve / recibe |
|---|---|
| `GET /plan-compras/api/seguimiento` | Una fila por paquete: `paqueteId`, nombre, frente, responsable (id, nombre, huérfano), `pasoActual`, `cumplidos`, `total`, `estado`, `atrasado`, `finProgramado`, `finProyectado` |
| `GET /plan-compras/api/seguimiento/paquete?paqueteId=N` | Los pasos: `pasoId`, `orden`, `paso`, `dias`, `fechaInicio`, `fechaFin`, `fechaReal`, `proyectadoInicio`, `proyectadoFin`, `desfaseDias`, `registradoPor`, `registradoAt` |
| `POST /plan-compras/api/seguimiento/paso` | `{paqueteId, pasoId, fechaReal}` — `null` borra el registro |

Errores del POST: `PAQUETE_INVALIDO` (422), `PASO_INVALIDO` (422, el paso no pertenece al plan de ese
paquete en este proyecto), `FECHA_INVALIDA` (422, no es `YYYY-MM-DD`).

**Sin regla de orden entre pasos.** En obra la orden de compra se firma a veces antes de que alguien archive
el acta del paso anterior. Bloquear el registro fuera de orden no produce disciplina: produce fechas
inventadas para desbloquear la pantalla. `null` es un valor legítimo — deshacer un registro equivocado.

Rutas en `public/index.php`, junto a las de `/plan-compras/api/plan/*` y **antes** de cualquier ruta desnuda,
siguiendo el orden que ya usa el archivo.

## Pantalla

Ruta `#/seguimiento/avance` en la SPA — estrena el submódulo «Seguimiento», que hoy existe en la navegación
conceptual pero no tiene ninguna pantalla.

- **Lista** (AG Grid, patrón de `PlanFechas.tsx`): un paquete por fila, con frente, responsable, paso actual,
  «3 / 7», estado y marca de atraso.
- **Panel de detalle** al hacer clic: los pasos en vertical y, por paso, tres fechas —**programada ·
  real (editable) · proyectada**— más el desfase en días. Editar la real dispara el POST y refresca.
- **Filtros** (los cuatro, en cliente sobre los datos ya cargados; son cientos de filas, no miles): mis
  paquetes · frente · estado · atraso.

Toda la lógica testeable en `pdc-app/src/lib/seguimiento.ts` (proyección, derivados, filtros), no en el
componente — la regla que ya sigue el resto de la SPA.

Alcance visual: desktop ≥1180 px y dark mode, según `AGENTS.md`.

## Reamarre: el avance no se borra

Hoy `limpiarPlanCalculado()` hace `DELETE FROM pdc_plan_paso` para el paquete. Con B1 eso destruiría trabajo
que sí ocurrió: una propuesta ya recibida no deja de haberse recibido porque la obra se reprograme.

Pasa a:

1. `DELETE` solo de las filas **sin** `fecha_real`.
2. `UPDATE` de las que sí la tienen: `fecha_inicio = NULL`, `fecha_fin = NULL` (de ahí la nulabilidad nueva).
   El avance y su auditoría se conservan; lo programado deja de valer porque se calculó contra otro frente.
3. El siguiente `calcular()` reescribe esas fechas contra el frente nuevo, por el upsert que ya existe.

Queda saldada la deuda que A4 dejó anotada en el propio código de `amarrar()`.

Consecuencia de diseño: una fila con `fecha_real` y `fecha_inicio` en `NULL` significa «esto se hizo, pero el
plan aún no se ha recalculado». La pantalla lo muestra tal cual —la fecha real, sin programada ni desfase—,
no lo esconde.

## Precondición: DAPORTO todavía no tiene plan que seguir

Medido en el stack principal (`localhost:3307`, `lastplanneraia_dev`) el 2026-07-29:

| | DAPORTO (73) |
|---|---|
| `pdc_insumo_paquete` | 133 |
| `programa_consolidado` | 1.092 |
| `project_members` | 17 |
| `pdc_rama_frente` · `pdc_paquete_frente` · `pdc_plan_paquete` · `pdc_plan_paso` | **0 · 0 · 0 · 0** |

Los catálogos globales sí están al día (216 paquetes activos, 9 pasos, 3.079 insumos en el maestro). Lo que
falta es **por proyecto**: DAPORTO no tiene ni ramas amarradas ni plan calculado, y `pdc_plan_paso` está
vacía en toda la base. Sin plan no hay pasos de los que colgar una fecha real, así que B1 no tiene sobre qué
correr.

Es un estado del stack principal, no una deuda de código: ese trabajo se hizo en el stack retirado
(`lps-aia-pdc`, puerto 3308), que tenía otra base. **Primera tarea del plan de implementación:** dejar
DAPORTO con plan —correr el amarre de ramas a frentes y `calcular()` sobre sus paquetes— y registrar el
recuento resultante como línea base. Es reproducible con el código ya en `main`; no requiere escribir nada
nuevo.

## Verificación

**PHP** (`tests/test_pdc_v2_seguimiento.php`, autoejecutable, `PASS:`/`FAIL:`, exit 0/1):
registrar y borrar una fecha real · aislamiento por `project_id` · la fecha real **sobrevive a un
recálculo** · sobrevive a un **reamarre** mientras las programadas se limpian · el paso de otro paquete o
proyecto es rechazado · la proyección con y sin pasos cumplidos, incluido el caso «el cursor quedó en el
pasado».

**SPA:** Vitest sobre `src/lib/seguimiento.ts` (proyección, estado, atraso, los cuatro filtros) y
`npm run build`.

**No regresión:** `tests/test_pdc_v2_plan_fechas.php` en verde y
`tests/test_pdc_v2_brecha_daporto.php` **sin moverse de sus 7 diferencias**.

**Datos:** se trabaja **solo sobre DAPORTO (`project_id = 73`)** y **se puede dejar alterado** (decisión
del usuario, 2026-07-29). Esto retira la restricción que rigió en A3/A4 —tests contra los proyectos
sintéticos 999903/999904— y con ella el andamiaje de sembrado que hacía falta para sostenerla: los tests de
B1 escriben en los paquetes reales de la obra y no restauran nada al terminar.

Lo que **sí** se conserva de aquella disciplina, porque no depende del proyecto de pruebas:

- `tests/test_pdc_v2_brecha_daporto.php` sigue siendo el gate del motor de paquetes, en **7 diferencias**.
  B1 escribe en `pdc_plan_paso`, tabla que ese informe no mira, así que no debería moverse; si se mueve, es
  una regresión de verdad y aborta el trabajo.
- Los tests siguen siendo idempotentes: cada corrida deja el mismo estado que la anterior, no acumula.
  «Se puede dejar alterado» significa que no hay que restaurar la foto previa, no que valga dejar basura
  distinta en cada ejecución.

**Navegador:** validación en `http://localhost:8081`, ruta `#/seguimiento/avance`, viewport 1180×820, dark.

## Fuera de alcance (y a dónde va)

- Semáforos, alertas y re-matching al reprogramar → **B2**.
- Notificaciones al responsable → **B3**.
- Torre de Control / BI → **C1**.
- Nota o evidencia por paso, y estado «no aplica» → descartados en el grilleo; se reabren si la operación
  los pide, no antes.

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** SeguimientoService.php y PlanComprasSeguimientoController.php; public/index.php:228-231 registra las cuatro rutas /plan-compras/api/seguimiento*

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
