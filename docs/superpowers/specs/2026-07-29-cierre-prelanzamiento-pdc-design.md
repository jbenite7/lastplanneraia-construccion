---
capa: fuente
tipo: spec
estado: cerrado
fecha: 2026-07-29
areas: [pdc]
fuente: docs/superpowers/specs/2026-07-29-cierre-prelanzamiento-pdc-design.md
resumen: goals/pdc-preparar-b1 - Origen: el comité mandó «arreglar los bugs que tengas identificados» antes de lanzar. En la reunión no se nombró ninguno, así que este…
---

# PDC v2 — Cierre pre-lanzamiento: los pendientes que bloquean decir «verificado» — Design

- **Fecha:** 2026-07-29
- **Ola:** 1 (antes del lanzamiento)
- **Goal:** `goals/pdc-preparar-b1`
- **Origen:** el comité mandó «arreglar los bugs que tengas identificados» antes de lanzar. En la reunión
  no se nombró ninguno, así que este spec reúne **los pendientes reales ya registrados** en los planes
  anteriores, y reserva el hueco para lo que salga del piloto de Da Porto.
- **Estado:** **cerrado** (fila 3 del tablero, `88c37b8`). Los cuatro pendientes quedaron cerrados o clasificados por escrito; el hueco del piloto se cerró vacío por decisión de Felipe, y está dicho así en el tablero.

## Problema

El módulo se va a producción. Hay cuatro pendientes registrados que, mientras sigan abiertos, impiden
decir «verificado» delante del comité sin mentir por omisión. Ninguno es una funcionalidad nueva: los
cuatro son deuda de verificación o de datos.

## Los cuatro pendientes

### 1 · El panel de correspondencias nunca se vio en pantalla

De `goals/pdc-a42-frentes-cobertura/goal.md`, cierre del 2026-07-28:

> «Lo único que no se verificó visualmente es el panel renderizado en pantalla: el e2e existente pasa
> contra este bundle (o sea, la pantalla carga y funciona), pero no se llegó a tomar una captura del
> panel abierto. Queda como la primera comprobación de quien retome.»

**Qué se hace:** abrir «Sin frente» en el navegador contra el contenedor servido, desplegar el panel,
comprobar que las 25 correspondencias y el atajo desde la fila sin propuesta se ven y funcionan, y dejar
la captura como evidencia. Si algo está roto, arreglarlo entra aquí.

### 2 · Los 25 paquetes sin `duracion_ref`

Task 5 del plan de A4, anotada y diferida. Un paquete sin duración de referencia **no recibe fechas**, y
sin fechas no puede vencer: sería invisible en el tablero de la Ola 1
(`2026-07-29-b2-semaforos-lookahead-design.md`).

**Qué se hace, en este orden:**

1. Medir cuántos son hoy y por qué les falta —¿no hay equivalente en
   `general_dias_procesos_contratacion`, o el paquete no está en el maestro?
2. Los que tengan equivalente, resolverlos por el camino que ya existe (análisis estadístico por tipo de
   paquete, que A4 ya implementa).
3. Los que no lo tengan, **quedan visiblemente sin fecha**, y el tablero **dice cuántos paquetes no está
   mirando**. Un plan que calla lo que no sabe es peor que uno incompleto que lo declara.

### 3 · Los 16 tests PHP en rojo

De la rama `pdc-a4-fechas`: 16 de 103 `tests/test_*.php` fallan por sí solos. Registrado como
preexistente, nunca diagnosticado.

**Qué se hace:** correr la suite completa sobre el estado integrado actual y clasificar cada rojo en una
de tres cajas — (a) roto de verdad, se arregla; (b) test obsoleto que asierta un contrato que cambió a
propósito, se actualiza **documentando qué cambió y por qué**; (c) ambiental, se anota con su causa.
Prohibido mover un assert para poner algo en verde sin la explicación.

Ojo con dos trampas ya medidas: `timeout` no existe en macOS, y `grep "^FAIL"` miente porque el
resultado real va en el código de salida.

### 4 · El indicador se contamina con el sandbox de los e2e

De `goals/pdc-revision-ux/validation-log.md`: los paquetes que crean los specs de Playwright viven en el
catálogo global, y el motor aprende de lo asignado en otros proyectos. Medir la brecha justo después de
correr los e2e da 8 en vez de 7. El seed limpia al empezar cada test, nunca al terminar el último.

**Qué se hace:** limpiar al terminar, no solo al empezar. Es la corrección estructural; el aviso que el
test da hoy es un parche que le pide al humano acordarse.

## Además

- **Verificar el maestro de paquetes gobernado.** En el comité se afirmó que la obra ya no puede tocar
  el maestro global y que solo un administrador lo actualiza. No hay evidencia de eso. Se comprueba con
  **un rol permitido y uno denegado** contra la capacidad `lps.paquetes_contratacion.reglas`, y se deja
  el resultado escrito. Si la afirmación resulta falsa, el arreglo entra en esta ola: es una promesa ya
  hecha al dueño del producto.
- **Hueco reservado para el piloto.** Tomás monta el plan de compras real de Da Porto esta semana. Sus
  hallazgos se registran en `goals/pdc-preparar-b1/hallazgos-piloto.md` y se triagean aquí.

## Condición de hecho

1. Captura del panel de correspondencias abierto, con las correspondencias visibles, en el evidence del
   goal.
2. El conteo de paquetes sin `duracion_ref` está medido y explicado; los resolubles quedan resueltos; el
   tablero declara en pantalla cuántos paquetes no tienen fechas.
3. La suite PHP corre completa y **cada** rojo está clasificado por escrito. Cero rojos sin explicación.
4. Correr los e2e dos veces seguidas y medir la brecha del motor da el mismo número las dos veces.
5. Un rol permitido y uno denegado probados contra el maestro de paquetes, con la salida guardada.
6. Los hallazgos del piloto están registrados y cada uno tiene decisión: se arregla en esta ola, se
   difiere con fecha, o se descarta con motivo.

## Riesgos

- **El punto 6 depende de que Tomás haga el piloto a tiempo.** Si no ocurre, ese hecho queda sin
  contenido: se declara así en la bitácora y **no se da por cumplido**.
- **El punto 3 puede destapar más trabajo del previsto.** Dieciséis rojos sin diagnosticar pueden ser
  dieciséis nimiedades o dos bugs reales. Si aparece un bug real de datos, sube a bloqueante del
  lanzamiento y hay que decirlo el mismo día, no al final de la ola.

---

## Cierre — verificado condición por condición el 2026-08-25

`estado: cerrado` **es una afirmación deliberada y se sostiene**, pero no por la razón que decía la
nota anterior. Esa nota (dos renglones: que spec:19 declaraba cerrado y que `hallazgos-piloto.md`
existía) **no medía ninguna de las seis condiciones**. Esta sí las mide, una por una.

El cierre **no es contradictorio con el punto 6 incumplido**, y conviene entender por qué antes de
leer la tabla: el punto 6 no se cumplió, no se dio por cumplido, y **no se va a cumplir nunca**.
Felipe decidió cerrar el hueco vacío en vez de dejar la fila abierta esperando
(`goals/pdc-preparar-b1/estado-olas.md:211`). Eso **retira el punto del alcance**; no lo declara
satisfecho. La prohibición de la §Riesgos —«se declara así y no se da por cumplido»— se respetó al pie.

| # | Condición | Veredicto | Evidencia verificada |
|---|---|---|---|
| 1 | Captura del panel de correspondencias | **cumplida** | `evidence/panel-correspondencias-daporto-2026-07-29.png` y `…-sandbox-2026-07-29.png`, ambas presentes. Bitácora en `evidence/cierre-prelanzamiento-2026-07-29.md` §Punto 1. Destapó un defecto real de CSS (H3) que el e2e verde no veía |
| 2 | Paquetes sin `duracion_ref` medidos y declarados en pantalla | **cumplida, corrigiendo la premisa** | No eran 25 sino **42**; y un paquete sin `duracion_ref` **sí** recibe fechas por la mediana de su tipo (`PlanFechasService`, `duracion_provisional = 1`). El aviso vive en código: `pdc-app/src/lib/vencimientos.ts:65`, con tres casos en `vencimientos.test.ts:47,53,59`. Medición en `evidence/paquetes-sin-duracion-ref.md` |
| 3 | Suite PHP completa, cada rojo clasificado | **cumplida en su fecha** | 4 rojos de 108 sobre `main@1a75b19`, clasificados uno a uno en (a) roto/(b) obsoleto/(c) ambiental. Cero rojos sin explicación |
| 4 | Los e2e dos veces dan el mismo número | **cumplida en el fondo, con otro instrumento** | El arreglo estructural está en `tests/browser/support/pdc-sandbox.mjs:132` (`test.afterAll`). Se midió **residuo del sandbox** (0 en dos pasadas), **no la brecha del motor** que la condición nombraba, porque el trinquete estaba roto. La bitácora lo declara sin adornos: «que nadie lea esto como *la brecha se midió*» |
| 5 | Un rol permitido y uno denegado contra el maestro | **cumplida, y más de lo pedido** | `tests/test_pdc_v2_maestro_gobernado.php` existe y es ejecutable: dos capacidades en vez de una, permisos leídos de BD. Corrigió una afirmación falsa del comité: no es «solo un administrador», son **tres** roles (A, D, OT) |
| 6 | Hallazgos del piloto con decisión | **NO cumplida — sin contenido, y así declarado** | El piloto no reportó hallazgos. Los cuatro de `hallazgos-piloto.md` son observaciones propias de la sesión, con decisión cada una (H3 arreglado · H1 y H2 diferidos · H4 descartado con motivo). Cerrado vacío por decisión de Felipe |

### Por qué no queda trabajo vivo que esta spec gobierne

Los cuatro hallazgos tienen destino, y el que seguía siendo deuda **ya se saldó**:

- **H3** (textos pegados en el panel) — arreglado en la propia ola.
- **H2** (el trinquete de la brecha sin punto de referencia) — **reparado después**. Verificado hoy:
  `tests/test_pdc_v2_brecha_daporto.php:62` ya no fija la versión 292, la resuelve el servicio
  (`$VERSION = null`) y salta limpio si el proyecto no está sembrado. Dejó de ser deuda.
- **H1** (dos e2e que alternan según el dato de la obra) — diferido a la Ola 2 con destino escrito.
- **H4** (tres radios fuera de escala en `/pdc`) — descartado con motivo; esa pantalla se retiró el
  2026-08-04.

### Lo que esta verificación NO cubre, dicho para que nadie lo lea de más

- **No se re-corrieron los e2e dos veces hoy.** Escriben en la base de desarrollo compartida y había
  otra sesión viva en el repo; la regla 6 de `docs/coordinacion-sesiones.md` exige coordinar esas
  escrituras. El punto 4 se dio por verificado sobre el código y la evidencia A/B existente, no sobre
  una corrida nueva.
- **El punto 3 se verificó en su fecha, no hoy.** La suite de hoy es otra: sobre `410ac132`, nivel
  `puro`, da **26 pasan / 3 fallan de 29** (`test_legacy_csrf_guard` rc=1, más
  `test_pdc_v2_import_parser` y `test_pdc_v2_maestro_sinco_parser` rc=255 por `/tmp` de solo lectura
  en el contenedor). **Ninguno de los tres es del PDC-cierre**, y el de CSRF es de otro frente. Exigirle
  a esta spec que mantenga clasificados los rojos de siempre sería una condición imposible de cerrar.

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
