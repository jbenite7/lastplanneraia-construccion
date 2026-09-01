---
capa: fuente
tipo: spec
estado: vigente
version: "1.0"
fecha: 2026-09-01
areas: [pdc]
fuente: "auditoría del código en producción (6fa3cff1); decisiones de Felipe del 2026-09-01"
resumen: "Cada obra puede corregir la duración de los pasos de contratación de un paquete sin mover las fechas de las demás obras. El catálogo de la empresa pasa a ser el valor por defecto y deja de ser el único número posible."
---

# Duraciones de contratación por obra — diseño v1.0

> **Estado: aprobado por Felipe el 2026-09-01 para escribir el plan.** No autoriza implementación,
> migración ni deploy: cada uno tiene su propia puerta.

## 1. El problema, medido

En `/plan-compras#/ensamble/plan`, al desplegar un paquete aparece el panel «Pasos de «…»» con los
siete pasos del proceso y sus días. **Esa tabla es texto plano**: `PlanFechas.tsx:863` pinta
`<td>{p.dias}</td>` sin `input` ni manejador. No es un editor roto — nunca hubo editor ahí.

Los días sí se pueden cambiar, pero en otro sitio y con otro alcance:

- La pantalla está en `#/ensamble/plan/pasos`, a la que se llega por el botón «Configurar pasos»
  (`PlanFechas.tsx:685`), **fuera de la barra de pestañas** por decisión de A4.1.
- Dentro de esa pantalla el editor vive plegado en un `<details>` (`PasosContratacion.tsx:248`).
- Y lo que edita es **el catálogo de la empresa**: `guardarDuracion()`
  (`PlanComprasPlanController.php:511`) llama a `DuracionesCatalogoService::actualizar($ref, …)`,
  que escribe `general_dias_procesos_contratacion` por `id`. Su propio aviso en pantalla lo dice:
  «Estas duraciones son de la empresa, no de esta obra: cambiarlas mueve las fechas de todas las
  obras cuyos paquetes las usen.»

Consecuencia: **hoy no existe forma de decir «en Da Porto, Fabricación de BOMBEO DE CONCRETO son
120 días y no 180» sin moverlo para todas las obras que compren lo mismo.** El único eje que falta
es la obra: la granularidad por paquete ya existe, porque el catálogo se llama por
`(paqueteContratacion, tipoPaquete)` y cada paquete apunta a su fila con
`general_paquetes_contratacion.duracion_ref`.

## 2. Resultado buscado

Una obra puede corregir la duración de cualquier paso de cualquiera de sus paquetes, desde el mismo
panel donde hoy los ve, sin tocar el estándar de la empresa ni las fechas de otras obras. Las
correcciones quedan guardadas y acumulan un histórico que un frente posterior podrá administrar
desde `/admin/`.

### 2.1 Decisiones de Felipe (2026-09-01)

| # | Decisión | Descartado, y por qué |
|---|---|---|
| D1 | **La obra corrige; la empresa es el valor por defecto.** Tabla de excepciones; sin excepción manda el catálogo. | *Copiar el catálogo a cada obra*: rompe el objetivo declarado — corregir el estándar desde `/admin/` no llegaría a las obras ya copiadas. |
| D2 | **`/admin/` va en su propio frente.** Aquí solo el eje obra y su edición desde el PDC. | *Todo junto*: `admin/` es una mini-aplicación con router, seguridad y vistas propias; duplica el frente y retrasa lo urgente. |

### 2.2 Decisiones técnicas del asistente

| # | Decisión | Por qué |
|---|---|---|
| T1 | Se edita **en el panel de la pestaña Plan**, no solo en la pantalla de Pasos. | Es donde el usuario fue a buscarlo. La pantalla de Pasos conserva su editor del catálogo. |
| T2 | Permiso **`lps.paquetes_contratacion.editar`**, no `.reglas`. | `.reglas` protege el estándar de la empresa; una excepción de obra no lo toca. Hoy ambos viajan juntos en `$allWrite` (`RbacCatalog.php:191-205`), así que no cambia quién puede — cambia lo que el permiso significa cuando el catálogo se gobierne desde `/admin/`. |
| T3 | Al guardar **se recalcula el plan de esa obra**, no el de las demás. | Es el comportamiento que ya tiene `guardarDuracion()`. No se inventa uno nuevo. |
| T4 | Borrar la excepción devuelve el número de la empresa. | La vuelta atrás no necesita migración inversa: basta borrar filas. |
| T5 | La resolución empresa-vs-obra se hace **en PHP tras la consulta**, no con `COALESCE` en SQL. | Con una fila por columna corregida, el `COALESCE` exigiría siete `LEFT JOIN` o un pivote. En PHP es una línea por columna y se prueba sin base. |

## 3. Alcance

### 3.1 Incluido

- Tabla nueva de excepciones por obra y su migración.
- Resolución de días con la excepción por delante del catálogo, en `PlanFechasService`.
- Endpoints para guardar y borrar la excepción de una obra.
- Edición en el panel de pasos de la pestaña Plan, con el origen de cada número a la vista.
- Pruebas de contrato, unitarias, de componente y de navegador.

### 3.2 Excluido

- **`/admin/`**: la pantalla de gobierno del catálogo es el frente siguiente (D2). Se anota en `TASKS.md`.
- El catálogo `general_dias_procesos_contratacion` y su editor actual en `#/ensamble/plan/pasos`: no se tocan.
- Los días fijos de pasos sin `col_legacy`, que ya son configurables por obra en `pdc_proyecto_pasos` (A4.1).
- El deploy a producción y la aplicación de la migración: puerta propia y autorización explícita.
- Replicar el trabajo a `main`: necesario, pero es su propia tarea (§8).

## 4. Punto de partida medido

Medido en el worktree `fix/pdc-duraciones-pasos`, anclado al commit **desplegado en producción**
`6fa3cff10b7011ec1cb0001dbd00f4bbd2a8cb0b` (2026-08-20, árbol limpio, leído por SSH en
`~/www/lastplanneraia.com/public_html`).

| Pieza | Estado |
|---|---|
| Panel de pasos del Plan | Solo lectura; `PlanFechas.tsx:851-873` |
| Editor de duraciones | `PasosContratacion.tsx:247-300`, plegado, escribe el catálogo global |
| Endpoint de escritura | `POST /plan-compras/api/plan/duraciones` → `guardarDuracion()`, permiso `.reglas` |
| Resolución de días | `PlanFechasService::…` línea 1327: `LEFT JOIN general_dias_procesos_contratacion d ON d.id = p.duracion_ref` |
| Pasos legacy | Siete, en `PlanFechasService::PASOS` (1262-1270) |
| Distancia a `main` | 457 commits, 870 archivos |

Los siete pasos y sus columnas, que son la lista blanca de todo lo que sigue:

| Paso | Columna |
|---|---|
| Elaboración de pliegos | `diasElaboracionPliegos` |
| Entrega de pliegos | `diasEntregaPliegos` |
| Recibo de propuestas | `diasReciboPropuestas` |
| Cuadros comparativos | `diasCuadrosComparativos` |
| Legalización | `diasLegalizacionContrato` |
| Fabricación | `diasFabricacion` |
| Insumos en obra | `diasInsumosObra` |

## 5. El dato

Tabla nueva `pdc_proyecto_duraciones`. **Una fila por número corregido**, no por paquete:

| Columna | Tipo | Nota |
|---|---|---|
| `project_id` | int | La obra |
| `duracion_ref` | int | La fila del catálogo, igual que `general_paquetes_contratacion.duracion_ref` |
| `columna` | varchar | Una de las siete de §4; validada contra `PasosContratacionService::columnasLegacy()` |
| `dias` | int | ≥ 0 |
| `actualizado_por` | int | Usuario |
| `updated_at` | timestamp | |

Clave única `(project_id, duracion_ref, columna)`.

**Por qué una fila por columna y no siete columnas espejo del catálogo:** la corrección es parcial
por naturaleza —se ajusta Fabricación y nada más—, y con siete columnas habría que distinguir «no
corregido» de «corregido a NULL». Además, si un día el catálogo gana un paso, esta tabla no necesita
migración.

**Sin FK a `general_dias_procesos_contratacion`** por la misma razón que A4.1 aceptó para `paso_id`:
el catálogo es global y su ciclo de vida no lo gobierna esta tabla. La integridad se sostiene en la
validación de escritura (§7) y en que la lectura solo mira excepciones cuyo `duracion_ref` la obra
usa de verdad.

Migración `database/migrations/20260901_pdc_v2_duraciones_por_obra.php`, con dry-run y `--apply`,
idempotente y convergente, siguiendo la forma de `20260728_pdc_v2_pasos_configurables.php`. **Solo
crea la tabla: no escribe ni una fila de datos**, porque un sistema sin excepciones se comporta
exactamente como hoy.

## 6. La lectura

Un solo punto de cambio. `PlanFechasService` trae hoy los días con:

```sql
SELECT p.id, p.tipo_negociacion, p.duracion_ref, d.diasElaboracionPliegos, …
FROM general_paquetes_contratacion p
LEFT JOIN general_dias_procesos_contratacion d ON d.id = p.duracion_ref
```

**Esa consulta corre por paquete** (`WHERE p.id = ?`), así que las excepciones **no** se traen ahí:
se cargan **una sola vez por obra**, antes de recorrer los paquetes, indexadas por
`(duracion_ref, columna)`. Meterlas dentro de la consulta convertiría un cálculo de cien paquetes en
doscientas consultas. Con el mapa ya en memoria, cada columna se resuelve como `excepción de la obra,
si existe; si no, catálogo`.

**La resolución va antes de todo lo demás**, en particular antes de la comprobación de la línea 1344
que manda un paquete a duración provisional cuando su columna del catálogo es `NULL`: una obra debe
poder dar un número donde la empresa no lo tiene, y ese es justamente uno de los casos útiles.

Lo que **no** cambia: el reparto proporcional entre pasos con peso, los días fijos, el
`total = max(mediana, Σ días fijos)`, la convención de intervalo medio abierto `[inicio, fin)` y la
escritura de `pdc_plan_paso`. La excepción cambia una entrada del cálculo, no el cálculo.

## 7. Los contratos HTTP

| Verbo y ruta | Permiso | Qué hace |
|---|---|---|
| `GET /plan-compras/api/plan/duraciones` | `.reglas` (sin cambio) | **No cambia.** Sigue sirviendo el editor del catálogo de la empresa, tal como está |
| `POST /plan-compras/api/plan/duraciones/obra` | **`.editar`** | `{duracionRef, dias:{columna: dias}}` — guarda la excepción y recalcula esta obra |
| `DELETE /plan-compras/api/plan/duraciones/obra` | **`.editar`** | `{duracionRef, columnas:[…]}` — borra la excepción, vuelve el estándar y recalcula |

Validaciones obligatorias, todas con prueba:

1. Sesión y proyecto activo resueltos por el servidor; `project_id` **nunca** llega del cliente como autoridad.
2. `duracionRef` tiene que ser una fila que **esta obra use de verdad** — la misma comprobación que ya hace `guardarDuracion()` (`PlanComprasPlanController.php:528-533`), que responde 403 `DURACION_NO_DISPONIBLE`. Sin ella, la pantalla de una obra podría escribir excepciones de otra.
3. `columna` contra la lista blanca de `columnasLegacy()`. Va interpolada como nombre de columna en SQL: sin el filtro, es una inyección.
4. `dias` entero ≥ 0.
5. CSRF del ámbito `plan_compras_v2`, como el resto de mutaciones del módulo.

**Corregido el 2026-09-01, al escribir el plan.** La versión aprobada de esta tabla decía que el
`GET` ganaba «el valor vigente y su origen». Sobra: ese endpoint alimenta la pantalla de Pasos, que
por la decisión T1 no cambia, y el origen que la pantalla del Plan necesita viaja en la respuesta
del plan, no aquí. Tocarlo habría sido trabajo sin consumidor.

## 8. La pantalla

En el panel «Pasos de «…»» de la pestaña Plan:

- La columna **Días** pasa de texto a campo editable, con el mismo patrón de guardado al salir del campo que ya usa el editor del catálogo (`PasosContratacion.tsx:285-293`).
- Cada número dice **de dónde sale**. Un número de la obra se distingue del estándar sin depender solo del color (contrato de accesibilidad del repo).
- Cada paso corregido ofrece **volver al de la empresa**.
- El aviso de alcance **cambia de sentido**: donde el editor del catálogo advierte que mueve todas las obras, este dice que mueve solo esta.
- Estados obligatorios: guardando, guardado, error de guardado, y sin permiso (campo deshabilitado con razón accesible, no oculto).

La pantalla `#/ensamble/plan/pasos` y su editor del catálogo **se quedan como están**. Son dos
alcances distintos y conviene que se vean distintos.

## 9. Pruebas

**Unitarias (PHP).** La obra manda sobre la empresa; sin excepción manda la empresa; borrar devuelve
el estándar; una excepción donde el catálogo tiene `NULL` evita la duración provisional; el reparto
proporcional y los días fijos no cambian de resultado cuando no hay excepciones.

**Contrato (PHP).** Rol permitido y rol denegado por cada verbo; sesión ausente; `duracionRef` de
otra obra → 403; columna fuera de la lista blanca → 422; `dias` negativo → 422; CSRF ausente;
`Content-Type` y status correctos.

**Componente (Vitest).** El campo edita, marca el origen, ofrece volver al estándar, y muestra
guardando/guardado/error.

**Navegador (Playwright).** Escribir → recargar → recuperar; que la fecha del paso se mueva de
verdad tras el cambio; y **restaurar el dato de prueba al terminar**.

**Aislamiento entre obras.** Una prueba que corrige la duración en una obra y comprueba que el plan
de otra obra que usa la misma fila del catálogo **no cambió**. Es la afirmación central de esta spec
y no puede quedar sin prueba.

No se regeneran goldens para poner algo en verde.

## 10. Riesgos

| Riesgo | Mitigación |
|---|---|
| Alguien corrige el número de la obra creyendo que corrigió el estándar de la empresa | El origen de cada número se muestra en el panel, y los dos editores tienen avisos de alcance opuestos. Es la complejidad que D1 acepta a cambio de poder tocar una obra sola. |
| La migración entra después del código y el módulo responde 500 | Regla del repo: esquema antes que código. Ya ocurrió con `20260728_pdc_v2_responsable_usuario.sql`, que dejó toda la pestaña Plan en 500. |
| El cambio se despliega y `main` lo borra en el siguiente deploy | §11. No se cierra el frente sin decidirlo. |
| La excepción sobrevive a un paquete que cambió de `duracion_ref` | La lectura solo mira excepciones de filas que la obra usa; una excepción huérfana queda inerte, no rompe. Se anota como limpieza diferida. |

## 11. La deuda que este frente crea

Esta rama sale de **producción**, que va 457 commits detrás de `main`. La funcionalidad construida
aquí **no existe en `main`**, así que un deploy futuro desde `main` la retiraría del servidor.

Replicarla a `main` es trabajo obligatorio y **no cabe en este frente**: `main` tiene el shell React
y 870 archivos de diferencia, así que no es un cherry-pick mecánico. Se anota en `TASKS.md` como
bloqueante del próximo deploy desde `main`, no como diferible.

## 12. Cuándo está hecho

1. Una obra corrige un paso y sus fechas se mueven.
2. Otra obra que usa la misma fila del catálogo no cambia. **Probado, no supuesto.**
3. Borrar la excepción devuelve el número de la empresa y las fechas originales.
4. El catálogo de la empresa no cambió en ninguno de los tres pasos anteriores.
5. Rol permitido y rol denegado cubiertos en los tres verbos.
6. Escribir, recargar y recuperar, con el dato restaurado.
7. Suite PHP, Vitest y navegador en verde, con la salida real en el cierre.
8. `TASKS.md` anota el frente de `/admin/` y la réplica a `main`.

## 13. Preguntas abiertas

Ninguna bloqueante para escribir el plan. Dos que el plan debe resolver al detallar sus tareas:

- Si el panel debe permitir corregir un paso **sin `col_legacy`** (días fijos), que hoy ya se
  configura por obra en `pdc_proyecto_pasos`. La respuesta probable es que no: sería un segundo
  camino al mismo dato.
- Si el histórico de correcciones necesita tabla propia desde ya, o basta `actualizado_por` y
  `updated_at` hasta que el frente de `/admin/` lo pida.

## 14. Siguiente paso

Invocar `superpowers:writing-plans` para esta spec. No se implementa nada antes de que ese plan
exista y esté aprobado.
