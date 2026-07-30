# PDC v2 — Subpaquetes: del paquete de preconstrucción al contrato real de la obra — Design

- **Fecha:** 2026-07-29
- **Ola:** 3 (lo grande)
- **Goal:** `goals/pdc-preparar-b1`
- **Origen:** Comité del 2026-07-29 — la observación más de fondo de toda la reunión.
- **Estado:** **implementado** (2026-07-29). El grilleo propio que este spec exigía se hizo antes de
  escribir código; sus cinco decisiones están abajo, en «Decisiones del grilleo del modelo», y una de
  ellas **corrige** el alcance original de este documento.

## Problema

El plan de compras se arma en preconstrucción y con paquetes grandes: 35 paquetes cubren el 86,8 % del
presupuesto de Da Porto. Eso es lo correcto para preconstrucción. Pero no es lo que la obra contrata.
Textual, mirando el paquete de pisos:

> «Aquí había porcelanato, porcelanato, tableta gres, porcelanato, cerámica blanca. Es muy probable que la
> obra no haga un solo subcontrato de suministro, porque posiblemente no son el mismo proveedor, no son la
> misma marca, no son el mismo estilo. Pero para efectos de un plan de compras de etapa de
> preconstrucción, a mí me sirve: son grandes paquetes de contratación.»

Y lo que debería permitir:

> «Que esto empiece a hablarle a la oficina técnica: este paquete que voy a contratar con estas fechas, si
> va esto, si va esto, este no, este crea el nuevo paquete de contratación […] y quien esté manipulando
> esto diga: eso lo puedo contratar en 2 meses, tírelo para dentro de 2 meses; o eso lo necesito ya.»

Hoy el paquete es plano: o se contrata entero o no existe.

## Decisiones cerradas en el grilleo

| Decisión | Valor |
|---|---|
| Forma | **Subpaquetes dentro del paquete grande.** El paquete sombrilla se conserva y por dentro tiene lotes |
| Quién manda | **El subpaquete.** Cada uno tiene su proceso de contratación y sus fechas; el grande **resume** rango y avance |
| El maestro | El paquete grande sigue viniendo del maestro global; los subpaquetes son **de esa obra** y no lo actualizan |

Descartadas: sacar insumos a paquetes nuevos independientes (rompe la lectura de preconstrucción en 35
paquetes) y duplicar el paquete (deja dos con el mismo nombre y ensucia el histórico del que aprende el
motor).

## Consecuencias, dichas de frente

Esto **no es una función más**: añade un nivel de jerarquía y toca casi todo lo construido.

- El plan de fechas pasa a calcularse por subpaquete.
- El tablero de vencimientos trabaja con subpaquetes — es donde de verdad se contrata.
- El seguimiento (fechas reales por paso) cuelga del subpaquete.
- La cobertura y los porcentajes tienen que decidir qué cuentan: paquetes o subpaquetes.
- El motor de sugerencias sigue trabajando a nivel de paquete grande; **no** aprende de subpaquetes,
  porque son casuística de obra.

Por eso lleva grilleo propio antes de implementarse, y por eso está en la Ola 3.

## Alcance

### Entra

- Un paquete puede partirse en **N subpaquetes**; los insumos del paquete se reparten entre ellos.
- Un paquete sin partir **sigue funcionando exactamente como hoy** — sin subpaquetes de una sola fila
  creados por compatibilidad. Cero regresión para las obras que no lo usen.
- Cada subpaquete: nombre, insumos, modalidad, fechas propias, responsable propio, proceso de
  contratación propio.
- El paquete sombrilla muestra el rango de fechas que abarcan sus subpaquetes y su avance agregado.
- ~~Los insumos que no se asignan a ningún subpaquete se quedan en el paquete, que sigue siendo
  contratable.~~ **Corregido en el grilleo:** caen en un lote **«Resto»** que nace solo al partir. Un
  paquete partido no se contrata a sí mismo nunca. Ver la decisión 2 más abajo.

### No entra

- Subpaquetes de subpaquetes.
- Que los subpaquetes suban al maestro global.
- Repartir un mismo insumo entre dos subpaquetes: un insumo, un destino — la regla que sostiene todo el
  módulo desde A3.

## Condición de hecho

1. Partir «Pisos» en tres subpaquetes por material, darle a cada uno su fecha, y ver los tres en el plan
   con sus fechas distintas.
2. El paquete sombrilla muestra el rango que abarcan y el avance agregado, y coincide con la suma.
3. El tablero de vencimientos lista subpaquetes, no el sombrilla, para los paquetes partidos.
4. Un proyecto sin ningún paquete partido produce **exactamente el mismo plan** que antes del cambio,
   comparado fila a fila.
5. Los porcentajes de cobertura siguen sumando 100 % y está escrito qué unidad cuentan.
6. Ningún subpaquete aparece en el catálogo global.
7. Regresión completa: Vitest, PHP, PHPStan, build y los e2e `pdc-v2-*`.

## Riesgos

- **El punto 4 es el que hay que defender.** Es fácil que un nivel de jerarquía nuevo cambie sutilmente
  los números de las obras que no lo usan. Se compara contra una captura del plan tomada antes.
- **La cobertura se vuelve ambigua**: ¿11 de 96 paquetes o 11 de 130 subpaquetes? Hay que elegir una y
  decirla en pantalla, no dejar que cada vista decida.
- **Es el cambio más grande que le queda al módulo.** Si la Ola 3 tiene que recortarse, este es el que
  debe empezar, no el que debe hacerse a medias.

## Decisiones del grilleo del modelo (2026-07-29)

Este spec fijaba la dirección; el grilleo fijó el diseño fino. Las cinco son decisiones del dueño del
producto, no del implementador.

| # | Pregunta | Decisión |
|---|---|---|
| 1 | ¿De dónde salen las fechas de cada subpaquete? | **Frente propio por subpaquete.** Cada lote se amarra a su propio nodo del cronograma, así que reprogramar la obra sigue moviendo las fechas solo. Se descartó la fecha a mano justamente por eso: quedaría congelada. |
| 2 | ¿Quién contrata los insumos sueltos? | **Un lote «Resto» automático**, con las fechas del padre. Un paquete partido **nunca** se contrata él mismo. Corrige la línea del alcance tachada arriba. |
| 3 | ¿Qué unidad cuenta la cobertura del plan? | **Lo contratable:** paquetes sin partir + lotes de los partidos. La pantalla lo dice con esas palabras («N procesos de contratación»). La cobertura *de insumos* no cambia: se mide sobre insumos y un insumo sigue teniendo un solo destino. |
| 4 | ¿Puede un lote tener modalidad distinta a la del padre? | **Sí, libre**, incluidas las que no generan proceso. A cambio, el paquete sombrilla **declara cuánto de su valor no entra al plan y por qué**. |
| 5 | ¿Quién puede partir? | **`lps.paquetes_contratacion.editar`**, el permiso de la obra. Partir es casuística local y no toca el maestro global. |

## Cómo quedó implementado

- **`pdc_subpaquete`** (por proyecto): nombre, modalidad, responsable, `es_resto`, orden. FK al
  catálogo global; nunca escribe en él.
- **`subpaquete_id BIGINT NOT NULL DEFAULT 0`** en `pdc_insumo_paquete` y en las tres tablas del plan,
  con las claves únicas extendidas. **`0` = el paquete sin partir.** No es nulable a propósito: en un
  índice UNIQUE de MySQL dos `NULL` son distintos, así que el `ON DUPLICATE KEY` de
  `PlanFechasService::calcular()` dejaría de dispararse y cada recálculo insertaría cabeceras nuevas
  —el mismo fallo que A4.1 pagó con `paso_id`—. Precio aceptado: esa columna no lleva FK.
- **`SubpaquetesService::destinos()`** es la única definición de «unidad contratable» del módulo. La
  consumen el plan de fechas, el tablero de vencimientos y el flujo de caja, para que dos pantallas no
  puedan contar distinto. Un lote todavía vacío no aparece ahí (no tiene valor que repartir) aunque sí
  en `listar()`, que es lo que usa la pantalla que reparte insumos.
- **La herencia del frente se materializa al partir:** el amarre del paquete pasa al «Resto» y los
  demás lotes se amarran aparte. Así ninguna consulta necesita un caso especial de herencia.
- **`PlanFechasService::calcular()`** recorre destinos y no paquetes, y **todos** sus borrados y
  actualizaciones van acotados por `subpaquete_id`: sin eso, recalcular un lote se llevaba los pasos
  de sus hermanos, que comparten `paso_id`.

## Verificación

- `tests/test_pdc_v2_subpaquetes.php` — 30 asserts sobre MySQL real (proyecto 999940) con el caso
  literal del comité: «Pisos» partido en porcelanato, tableta gres y cerámica, cada uno con su fecha.
- **Punto 4, la cero regresión, comprobado dos veces:** dentro del test, el paquete vecino que nadie
  parte conserva su plan carácter por carácter; y sobre el proyecto real de Da Porto comparando
  `tests/foto_plan_fechas.php` contra
  [`evidence/linea-base-plan-antes-subpaquetes.txt`](../../../goals/pdc-preparar-b1/evidence/linea-base-plan-antes-subpaquetes.txt)
  tomada antes de tocar código — `diff` sin diferencias tras recalcular con el modelo nuevo.
- 35 de los 36 tests `test_pdc*` en verde y PHPStan del PDC sin errores (llegó con 2 y quedó en 0).
  El único rojo, `test_pdc_v2_brecha_daporto.php`, falla por falta de la versión 292 del presupuesto
  en la base local y **falla igual con el código original**.

## Las pantallas (2026-07-30)

- **Partir y repartir:** panel «Lotes de obra» dentro de «Paquetes con insumos». Vive ahí y no en una
  ruta propia porque partir se decide mirando los insumos del paquete, y mandar al usuario a otra
  pantalla le quita de delante lo que necesita para decidir. Trae el resumen del sombrilla (rango,
  avance agregado y cuánto de su valor no entra al plan), modalidad por lote, agregar, borrar y el
  reparto de insumos con casillas.
- **Darle a cada lote su fecha:** la lista «Sin frente» del Plan pasó a enumerar **unidades
  contratables** en vez de paquetes, así que cada fila ya *es* un lote y solo le falta su frente. Se
  eligió esta forma sobre añadir un segundo desplegable de lote: esa fila ya lleva el frente, la
  procedencia de la sugerencia y el botón de amarrar, y dos elecciones en una fila obligan a leer dos
  controles para entender una decisión.
- El motor sigue sugiriendo **por paquete**: en un lote no se muestra propuesta, porque preseleccionar
  la del paquete en sus tres lotes les daría a los tres el mismo frente.
- Un lote **sin insumos** no es un destino contratable: no aparece en «Sin frente» ni en la curva de
  caja. No tiene valor que repartir ni nada que contratar.

### Verificado en pantalla, en Da Porto

Con el paquete real «Suministro CONCRETO»: partido en tres desde la pantalla, insumos repartidos con
casillas (los valores suman el total del paquete), cada lote como fila propia en «Sin frente» rotulada
«Suministro CONCRETO › Premezclado 3000», **frentes distintos** a cada uno (al amarrar uno su hermano
no desapareció de la lista), y tras recalcular **tres anclas distintas en el plan: 2026-05-25,
2026-08-18 y 2027-03-16**. Al deshacer la partición, la foto del plan volvió a ser **idéntica** a la
línea base — el punto 4 comprobado de ida y de vuelta, con fechas propias y recálculo en medio.

### Un fallo silencioso que apareció al construirlo

`preseleccionDestinos()` seguía indexando por id de paquete mientras la pantalla ya leía
«paquete:lote». La preselección del motor dejó de aplicarse **sin que TypeScript dijera nada**: un
`Record<number, T>` es asignable a un `Record<string, T>` porque las claves numéricas son un
subconjunto de las de texto. Corregido, y fijado con un test que falla con la clave vieja.

## Lo que queda fuera, dicho

**El volumen sigue sin estresar.** Da Porto tiene 4 paquetes con insumos y 12 asignaciones. La regla de
contar por paquete + lote está **probada** —los tests exigen que no se repitan destinos ni se
multipliquen los pasos— pero no **medida** con los 96 paquetes previstos, porque no hay ningún proyecto
con ese volumen en la base local.
