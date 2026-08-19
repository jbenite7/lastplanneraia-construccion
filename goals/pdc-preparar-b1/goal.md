---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-07-29
areas: [pdc]
fuente: goals/pdc-preparar-b1/goal.md
resumen: Goal — Del comité al lanzamiento: cerrar el PDC v2 y ponerlo a trabajar
---

# Goal — Del comité al lanzamiento: cerrar el PDC v2 y ponerlo a trabajar

## El objetivo

El Plan de Compras v2 está construido y corre en local. El 2026-07-29 el dueño del producto lo recorrió
entero en el Comité Semanal de Innovación, dio el visto bueno, y dejó **seis peticiones y una fecha**: una
semana más antes de lanzar, con los bugs arreglados y una primera versión de indicadores.

Este goal recoge ese feedback **completo** —no una selección— y lo junta con los pendientes vivos del
roadmap, para llevar el módulo desde donde está hasta una obra trabajando con él en producción.

- **Origen del feedback:** [`evidence/comite-2026-07-29.md`](evidence/comite-2026-07-29.md).
- **Grilleo:** [`interview.json`](interview.json) / [`interview-result.json`](interview-result.json)
  (upsert de pasos, previo al comité) + el grilleo del 2026-07-29 sobre el feedback, cuyas decisiones
  están recogidas en los specs de cada ola.
- **Absorbe** `goals/pdc-responsable-usuario`: su grilleo se cumplió y su spec
  (`docs/superpowers/specs/2026-07-28-responsable-usuario-proyecto-design.md`) ya está implementado.

## Lo que cambió mientras se escribía este goal

Dos ítems que este goal iba a abrir **ya aterrizaron en `main` el mismo 2026-07-29**:

- el **upsert de `pdc_plan_paso`** (el grilleo original de esta carpeta), y
- el **responsable como usuario del proyecto**, con su migración y su selector.

Y con ellos entró **la fase B1 completa** (`f7cef87`, `5ee2e49`, `9f2790c`, `92c5c13`, `a4d0c75`,
`bfe7055`): `pdc_plan_paso` ya guarda `fecha_real`, existe `SeguimientoService`, y recalcular ya no borra
lo que sí ocurrió. El tablero que pidió el comité se apoya en datos que **ya existen**.

## Estructura: tres olas

Un solo goal, por decisión explícita del usuario. Tres olas, **cada una con su condición de hecho propia**,
para que la semana comprometida siga siendo cumplible. El goal cierra cuando cierran las tres.

### Ola 1 — lo que el comité comprometió · cierra con el despliegue

| Entregable | Spec |
|---|---|
| Tablero look-ahead de contratación (= fase B2, primera mitad) | [`b2-semaforos-lookahead`](../../docs/superpowers/specs/2026-07-29-b2-semaforos-lookahead-design.md) |
| Informe de impacto al recargar el presupuesto | [`impacto-reimport-presupuesto`](../../docs/superpowers/specs/2026-07-29-impacto-reimport-presupuesto-design.md) |
| Tamiz del presupuesto + cifras honestas | [`tamiz-presupuesto`](../../docs/superpowers/specs/2026-07-29-tamiz-presupuesto-design.md) |
| Los cuatro pendientes que bloquean decir «verificado» + maestro gobernado + hallazgos del piloto | [`cierre-prelanzamiento-pdc`](../../docs/superpowers/specs/2026-07-29-cierre-prelanzamiento-pdc-design.md) |
| Despliegue a producción | [`despliegue-pdc-v2-produccion`](../../docs/superpowers/specs/2026-07-29-despliegue-pdc-v2-produccion-design.md) |

### Ola 2 — lo que el uso va a exigir

| Entregable | Spec |
|---|---|
| Equipo alquilado vs comprado | [`equipo-alquilado-comprado`](../../docs/superpowers/specs/2026-07-29-equipo-alquilado-comprado-design.md) |
| Ayuda dentro de la aplicación | [`ayuda-in-app-pdc`](../../docs/superpowers/specs/2026-07-29-ayuda-in-app-pdc-design.md) |
| Los cuatro diferidos de A4.1 | [`a41-diferidos-configuracion-pasos`](../../docs/superpowers/specs/2026-07-29-a41-diferidos-configuracion-pasos-design.md) |
| Re-matching al reprogramar (= fase B2, segunda mitad) | [`rematching-reprogramacion`](../../docs/superpowers/specs/2026-07-29-rematching-reprogramacion-design.md) |

### Ola 3 — lo grande

| Entregable | Spec |
|---|---|
| Subpaquetes de obra | [`subpaquetes-obra`](../../docs/superpowers/specs/2026-07-29-subpaquetes-obra-design.md) |
| Flujo de caja: curva mensual de desembolsos | [`flujo-caja-desembolsos`](../../docs/superpowers/specs/2026-07-29-flujo-caja-desembolsos-design.md) |
| Fase B3 — el plan de compras en la Torre de Control | [`b3-torre-control-pdc`](../../docs/superpowers/specs/2026-07-29-b3-torre-control-pdc-design.md) |
| Fase C1 — retirar el PDC viejo + deuda de diseño | [`c1-retiro-pdc-viejo`](../../docs/superpowers/specs/2026-07-29-c1-retiro-pdc-viejo-design.md) |

## Condición de hecho

**Ola 1 — se cumple cuando:**

1. La pestaña de vencimientos responde «qué se me vence» por paso y por responsable, y su clasificación
   coincide con el semáforo del plan.
2. Recargar una versión del presupuesto informa del impacto sobre el trabajo hecho antes de confirmar.
3. El visor señala insumos vacíos y partidas globales, y toda cifra de insumos dice qué cuenta.
4. Los cuatro pendientes registrados están cerrados o clasificados por escrito; el maestro gobernado está
   verificado con un rol permitido y uno denegado; los hallazgos del piloto tienen decisión.
5. **Una obra entra a producción y llega a la pantalla del plan.**

**Ola 2 — se cumple cuando:** equipo alquilado y comprado existen como tipos de recurso con los antiguos
en cola de clasificación; las nueve pantallas tienen ayuda y un revisor ajeno recorre el flujo sin
preguntar; copiar la configuración de pasos entre obras funciona; y mover un frente en el cronograma avisa
y ofrece el delta antes de aplicarlo.

**Ola 3 — se cumple cuando:** un paquete se puede partir en subpaquetes con fechas propias sin cambiar el
plan de las obras que no lo usen; existe una curva mensual de desembolsos que declara lo que no incluye;
la Torre de Control muestra el plan de compras sin exponer datos de contratación indebidamente; y el PDC
viejo está retirado con decisión escrita sobre sus datos históricos.

## Decisiones del usuario que se apartan de mi recomendación

Se registran para que quien retome sepa que fueron deliberadas:

1. **Un solo goal con las seis peticiones**, en vez de separar lanzamiento y fase B. Mitigado con las tres
   olas y sus condiciones de hecho independientes.
2. **Los equipos ya existentes quedan «sin clasificar»** en vez de migrar por defecto a «comprado». Es más
   honesto y mete un tapón de decisiones; va en la Ola 2 para que no bloquee el lanzamiento.
3. **Se para el dark de `/pdc` y se acelera C1**, para no pulir una pantalla condenada. Al ir a
   aplicarlo se descubrió que **la decisión llegó tarde**: el trabajo ya estaba terminado y commiteado
   ese mismo día (`a3d59e8`, `c5af102`), y de paso corrigió la premisa —`/pdc` no se pintaba en claro—.
   No hubo nada que congelar. Lo que queda vigente es no abrir más trabajo de diseño sobre esa pantalla.

## Riesgos vivos

- **El despliegue arrastra mucho más que el PDC.** Producción va muy por detrás de `main`: cuando la obra
  entre no verá un módulo nuevo, verá otra aplicación. Es una tarea con respaldo, ventana y vuelta atrás
  propios.
- **La lista de bugs del piloto todavía no existe.** Si Tomás no monta Da Porto a tiempo, ese hecho queda
  sin contenido y **no se da por cumplido**.
- **El comunicado no es de este goal pero es precondición del despliegue.** Si se despliega antes de que
  salga, dos obras se encuentran otra aplicación sin aviso.
- **Los subpaquetes son el cambio más grande que le queda al módulo** y tocan el plan, el seguimiento, la
  cobertura y el flujo de caja. Requieren grilleo propio antes de implementarse.

## Fuera de alcance

Comunicado y material de capacitación (los lleva el comité) · extracción de despieces de acero desde
planos DWG (encargo aparte, agosto) · matriz de áreas (la construye Tomás).

---

## Cierre formal

**Estado:** HECHO
**Fecha de cierre:** 2026-07-31

### Lo que se logró

- **Ola 1** (100%): tablero look-ahead, informe de reimportación, tamiz de presupuesto, prelanzamiento
  y despliegue a producción.
- **Ola 2** (100%): equipo alquilado/comprado, ayuda in-app, diferidos de A4.1 y re-matching.
- **Ola 3** (100% salvo tarea 10): subpaquetes de obra, flujo de caja, PDC en Torre de Control.

### Lo que queda diferido

- **Tarea 10 — retiro del PDC viejo (C1):** depende de que una obra real esté operando en
  producción. Capturada en [`goals/retiro-listado-contratos`](../retiro-listado-contratos/goal.md)
  (etapas 3 y 4) y en el spec `c1-retiro-pdc-viejo`.

### Justificación del cierre

El objetivo del goal era llevar el PDC v2 del comité al lanzamiento. Todas las peticiones del
comité están implementadas y desplegadas. La tarea 10 no depende de código sino de un evento
externo (adopción en producción) y ya está rastreada en su goal propio.

---

## Archivos de este goal

[[goals/pdc-preparar-b1/estado-olas|estado-olas.md]] · [[goals/pdc-preparar-b1/hallazgos-piloto|hallazgos-piloto.md]]

[[goals/pdc-preparar-b1/evidence/b3-aislamiento-http|evidence/b3-aislamiento-http.md]] · [[goals/pdc-preparar-b1/evidence/b3-volumen|evidence/b3-volumen.md]] · [[goals/pdc-preparar-b1/evidence/censo-consumidores-pdc-v1|evidence/censo-consumidores-pdc-v1.md]] · [[goals/pdc-preparar-b1/evidence/cierre-prelanzamiento-2026-07-29|evidence/cierre-prelanzamiento-2026-07-29.md]] · [[goals/pdc-preparar-b1/evidence/comite-2026-07-29|evidence/comite-2026-07-29.md]] · [[goals/pdc-preparar-b1/evidence/impacto-y-tamiz-validacion|evidence/impacto-y-tamiz-validacion.md]] · [[goals/pdc-preparar-b1/evidence/listas-por-modalidad-no-se-construye|evidence/listas-por-modalidad-no-se-construye.md]] · [[goals/pdc-preparar-b1/evidence/medicion-rematching-2026-07-29|evidence/medicion-rematching-2026-07-29.md]] · [[goals/pdc-preparar-b1/evidence/paquetes-sin-duracion-ref|evidence/paquetes-sin-duracion-ref.md]] · [[goals/pdc-preparar-b1/evidence/validacion-equipo-alquilado-comprado|evidence/validacion-equipo-alquilado-comprado.md]]

Estado y relación con los demás goals: [[estado|Estado de los goals]].
