---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-07-29
areas: [pdc, rbac, bi]
fuente: docs/superpowers/specs/2026-07-29-b3-torre-control-pdc-design.md
resumen: goals/pdc-preparar-b1 - Origen: roadmap maestro (fase B3) + decisión del comité del 2026-07-29 de llevar los indicadores a BI después de tener la pestaña…
---

# PDC v2 · Fase B3 — El plan de compras en la Torre de Control — Design

- **Fecha:** 2026-07-29
- **Ola:** 3 (lo grande)
- **Goal:** `goals/pdc-preparar-b1`
- **Origen:** roadmap maestro (fase B3) + decisión del comité del 2026-07-29 de llevar los indicadores a
  BI **después** de tener la pestaña dentro del módulo.
- **Depende de:** `2026-07-29-b2-semaforos-lookahead-design.md` (la pestaña es el día 1; esto es el día 2).
  **Ya en `main`** (`60f8bfe`): existe `SeguimientoService::clasificarVencimiento()` y la pestaña
  Vencimientos.
- **Estado:** **implementado y en `main`** (fila 9 del tablero, `e610fbb`). El panel de compras de la Torre de Control existe, con sus cuatro indicadores y el aislamiento por obra verificado con un rol permitido y uno denegado.

## Problema

La pestaña de vencimientos de la Ola 1 responde «qué se me vence **en esta obra**». Falta la pregunta de
gerencia técnica: **cómo van todas las obras**. Esa es la Torre de Control, que ya existe en
`/bi/control-tower`.

## El obstáculo que había que resolver primero — resuelto

El spec original planteaba este dilema: el BI va por **Power BI publish-to-web** (público, sin filtro por
proyecto, sin API de JavaScript), lo cual **no sirve** para datos de contratación por obra.

**La premisa era falsa para la Torre de Control.** Se verificó en el código antes de decidir:

- El iframe público de Power BI vive **solo** en `views/indicadores/indicadores.view.php:111`, en la
  pantalla `/indicadores`. Sus limitaciones están anotadas en el propio comentario del archivo.
- La Torre de Control es **PHP servido por la aplicación**: `public/index.php:355-375` enruta
  `/bi/control-tower` y `/api/bi/report/*` a `BiControlTowerApiController` → `ControlTowerService`.
- **El filtro por obra ya existe y ya se aplica**: `App\Support\BiProjectScope::resolve()` lanza
  `DomainException` cuando se piden proyectos fuera de los autorizados del usuario
  (`src/Support/BiProjectScope.php:30`).

### Decisión 1 — El dato vive en la aplicación

Los indicadores los sirve la propia aplicación, reusando el BI propio de la Torre de Control. **No se
publica nada en Power BI.**

Por qué esta y no las otras:

- **Power BI Embedded** (app-owns-data + embed-token) resolvería también la privacidad de `/indicadores`,
  pero es un frente propio —licencia, capacidad, autenticación de servicio, publicación del modelo— que
  hoy nadie ha aprovisionado. Metería B3 detrás de una compra. Sigue siendo el camino correcto para
  `/indicadores`; no es de esta fase.
- **Power BI publish-to-web** queda **descartado explícitamente**: publicaría con quién se negocia y por
  cuánto en una URL pública sin filtro por obra.

El riesgo de incidente desaparece **por construcción**: en este camino no hay URL pública. La verificación
con un rol permitido y uno denegado es la que ya se aplica al resto de la Torre.

### Decisión 2 — Quién ve qué

**Solo tus obras autorizadas**, heredando tal cual `BiProjectScope::authorizedProjectIds()`. Un director
con dos obras compara sus dos; gerencia, que está en todas, las ve todas; pedir una obra ajena da error.

No se crea una capacidad RBAC nueva para compras. Se consideró exigir una capacidad específica además de
la membresía —estar en la obra no es lo mismo que poder ver con quién se negocia—, y se descartó para no
inventar una regla de permisos propia de este panel: heredar la que la Torre ya aplica es lo que hace
verificable el punto 2 de la condición de hecho. Si más adelante gerencia quiere ese nivel extra, es un
cambio aditivo.

### Decisión 3 — Cuánto detalle se expone

**Agregado, con drill-down al paquete.** Las tarjetas muestran porcentajes, conteos y valores totales; al
pinchar un número se abre la lista de paquetes con **valor y responsable**, como ya hacen los otros
informes de la Torre (`programaComplianceDetail` y hermanos).

**El nombre del proveedor no aparece en ninguna vista de la Torre**, ni en la tarjeta ni en el
drill-down. Para ese dato está el módulo, que ya lo protege. Es el límite que mantiene acotado el daño si
algún día falla un permiso.

## Alcance

### Entra

Cuatro indicadores. **Ninguno introduce un cálculo nuevo**; cada uno tiene un dueño que ya existe:

| Indicador | Dueño del cálculo | Nota |
|---|---|---|
| **Cobertura de asignación**, por valor **y** por conteo | `PaquetesService::resumen()`, que ya devuelve `cobertura` y `coberturaValor` (`src/Services/Pdc/PaquetesService.php:920-921`) | Los dos números siempre juntos: cada uno por separado cuenta media verdad |
| **Paquetes vencidos y en riesgo** por obra | `SeguimientoService::clasificarVencimiento()` | La misma función estática que pinta el semáforo del plan y llena la pestaña |
| **Avance de contratación por paso** | `pdc_plan_paso` vía `SeguimientoService` | Cuántos paquetes han pasado cada paso |
| **Carga por responsable** | ídem, agrupado por `responsable_user_id` | Descartado como fila en la pestaña; a nivel de gerencia sí es la pregunta correcta: quién está sobrecargado |

Además:

- **El panel dice cuántos paquetes no está mirando**, y por qué. Se hereda de la Ola 1: un tablero vacío y
  un tablero ciego se ven igual, y esa frase es la diferencia. Incluye los paquetes con el cronograma
  desactualizado, que ya tienen dueño de cálculo: `SeguimientoService::paquetesDesactualizados()`.
- **Sustitución del informe `pdc` existente** (ver Decisión 4).

### No entra

- Power BI Embedded, y cualquier arreglo de `/indicadores`.
- Notificaciones, correos o recordatorios.
- Nombre de proveedor en la Torre.
- **La curva de desembolsos.** `FlujoCajaService::curva()` ya existe (fila 8b) y es una pregunta legítima
  de gerencia, pero B3 cierra con los cuatro indicadores que el comité nombró. Meterla ahora ampliaría una
  fase que ya sustituye un informe existente. Queda como la extensión natural siguiente, con el cálculo ya
  disponible para consumirlo sin reimplementarlo.
- El retiro del PDC viejo en sí: eso es la fase C1 (`2026-07-29-c1-retiro-pdc-viejo-design.md`).
- Tablas nuevas y migraciones: no hay.

## Decisión 4 — El informe `pdc` que ya existe apunta al PDC viejo

`/api/bi/report/pdc` (`src/Controllers/Api/BiControlTowerApiController.php:195`) ya existe, pero lee
`bi_pdc_general` / `subcontratoPaquete`: **es el PDC viejo**. Hoy la Torre muestra un KPI «PDC en riesgo»
que no viene del plan de compras nuevo.

**Se sustituye** por el del PDC v2. La alternativa —dejar los dos conviviendo hasta C1— haría que la Torre
mostrara dos verdades distintas sobre compras en la misma pantalla, que es exactamente lo que este spec
existe para evitar.

**Riesgo aceptado y registrado:** una obra que todavía trabaje con el PDC viejo pierde ese indicador antes
de que C1 la migre. Se asume porque C1 va detrás en la misma ola y porque un indicador ausente es más
honesto que dos indicadores que se contradicen.

## Decisión 5 — Semana LPS contra fecha de hoy

Todos los informes de la Torre reciben un parámetro `semana` y la pantalla tiene su selector. Los
vencimientos, en cambio, se calculan contra **hoy**.

El panel de compras **ignora el selector de semana y siempre responde «hoy»**, con la fecha rotulada en la
tarjeta («al 29/07/2026») y **puesta por el servidor**, igual que en la pestaña del módulo: dos usuarios en
husos distintos deben ver el mismo vencido.

Es el único panel de la Torre que no obedece al selector, y el rótulo existe para que eso no se lea como
un fallo. A cambio, el punto 3 de la condición de hecho —módulo y Torre coinciden el mismo día— se cumple
sin trabajo extra. Recalcular a la fecha de una semana pasada se consideró y se difirió: exigiría
reconstruir a esa fecha los pasos ya cumplidos, que es superficie de error nueva sin demanda todavía.

## Decisión 6 — Qué cuenta como «un paquete», con subpaquetes en el dominio

Los subpaquetes llegaron a `main` mientras se escribía este spec (`SubpaquetesService`, fila 8a: dominio y
API sí, pantalla todavía no). Una obra puede partir un paquete en varios lotes, cada uno con sus fechas.

**La unidad de conteo es el destino: cada lote cuenta.** Es la misma que ya eligió el tablero de la Ola 1,
que une por `paquete_id + subpaquete_id` y lo documenta en la propia consulta
(`SeguimientoService::vencimientos()`, `src/Services/Pdc/SeguimientoService.php:397-400`): con la unión
solo por paquete, un paso de un paquete partido en tres salía tres veces y los conteos quedaban
multiplicados sin que nada lo dijera.

Consecuencia que hay que **rotular**: el total de paquetes de una obra sube cuando parte uno. Sin ese
rótulo parece que aparecieron paquetes de la nada. A cambio, Torre y módulo cuentan igual **por
construcción**, que es lo que sostiene el punto 3 de la condición de hecho.

Se descartó contar la sombrilla (total estable, pero esconde el tamaño del problema y discreparía del
módulo) y mostrar los dos números a la vez (coherente con el criterio de cobertura, pero duplica cada
indicador en una pantalla de gerencia).

## Arquitectura

Sin tablas nuevas y sin migraciones.

- **Backend — el cálculo único.** Un método nuevo en `src/Services/Pdc/SeguimientoService.php` que
  **acepta una lista de proyectos** y devuelve el agregado, reusando la **misma**
  `clasificarVencimiento()` estática que ya consumen la pestaña y el semáforo. Una consulta con
  `IN (...)`, no N consultas. **Une por destino** (`paquete_id + subpaquete_id`), igual que
  `vencimientos()`, según la Decisión 6: unir solo por paquete multiplica los conteos.

  Se descartó llamar al servicio en bucle una vez por obra (imposible que diverja, pero N consultas por
  carga y N crece con las obras activas: comprometería el punto 4 de la condición de hecho). Y se descartó
  una consulta agregada propia en `ControlTowerService`, que es el estilo de los demás informes pero
  reimplementaría los cortes de vencimiento en un segundo sitio.

- **La regla que hereda de la Ola 1:** la clasificación de vencimiento **no se reimplementa aquí**.
  `ControlTowerService` no escribe una sola línea de SQL sobre vencimientos: le pide el agregado al dueño
  del cálculo. Dos definiciones de «vencido» en la misma empresa es peor que no tener ninguna.

- **Endpoint:** `/api/bi/report/pdc` conserva su ruta y su forma de respuesta (`getBrief`), y pasa a
  alimentarse del PDC v2. Los proyectos siguen resolviéndose con `resolveProjectIds()` →
  `BiProjectScope`; el parámetro `semana` se acepta y se ignora para este informe, según la Decisión 5.

- **Frontend:** el panel de la Torre de Control que ya existe, con los tokens y primitivas que la Torre ya
  usa. Sin componentes nuevos ni colores propios. Desktop ≥1180px y dark, viewport canónico 1180×820.

## Condición de hecho

1. Está decidido y escrito dónde vive el dato, con el problema de privacidad resuelto explícitamente
   —cumplido por este documento: Decisiones 1 a 3—.
2. Ninguna obra ve datos de contratación de otra obra sin permiso, **verificado con un rol permitido y uno
   denegado** contra el endpoint.
3. Los números del tablero por obra y los de la Torre de Control **coinciden para la misma obra y el mismo
   día**: cobertura, conteo de vencidos y conteo por corte.
4. Los indicadores cargan con el **volumen real de las obras activas**, no solo con el proyecto de prueba.
5. El nombre del proveedor no aparece en ninguna respuesta del endpoint ni en la pantalla.
6. Regresión: PHPStan sin errores nuevos, los `tests/test_*.php` afectados en verde, y los e2e `pdc-v2-*`
   sin romperse.

## Riesgos

- **Publicar contratación en un tablero público sería un incidente**, no un bug. Resuelto por la Decisión
  1: no hay publicación pública en este camino.
- **Es la fase más fácil de adelantar mal.** Con la pestaña funcionando, la tentación es copiar consultas
  al BI para tener «algo» rápido. La regla del cálculo único está para impedirlo.
- **La sustitución del informe viejo deja sin KPI a quien no haya migrado** (Decisión 4). Aceptado y
  escrito.
- **El volumen real no está medido.** La Ola 1 declaró que Da Porto tiene hoy 4 paquetes y 21 pasos, no
  los 96 del spec: la regla está probada pero **no está estresada a escala**. El punto 4 de la condición
  de hecho es precisamente lo que esta fase no puede dar por supuesto.
