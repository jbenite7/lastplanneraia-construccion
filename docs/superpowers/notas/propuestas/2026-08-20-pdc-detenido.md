---
capa: fuente
tipo: reporte
estado: abierto
fecha: 2026-08-20
areas: [pdc, bi]
fuente: "Encargo del chip «pdc-detenido»; `docs/pdc-v2.md`, `src/Services/Pdc/PlanFechasService.php`, `SeguimientoService.php`, `pdc-app/src/pages/PlanFechas.tsx`; conteos por SELECT en dev (`lastplanneraia_dev`) y en producción por SSH en solo lectura, 2026-08-21; motor de sugerencias corrido en producción (solo SELECT)"
resumen: "El PDC v2 de Da Porto no se detuvo por falta de pantalla ni de decisión: toda la cadena paquetes → amarre al cronograma → plan con fechas está construida y desplegada. Se detuvo porque nadie ha pulsado dos botones en `#/ensamble/plan`: «Aceptar sugeridos» (el motor ya propone frente para 55 de los 58 paquetes contratables) y «Recalcular». Cargar el plan son ~2 horas de una persona con permiso de editar paquetes, más 3 ramas que decidir a mano. La apuesta de la semana (D63) es realista si se nombra quién lo hace; si no, la hoja 8.6 nace vacía."
---

# Por qué el Plan de Compras v2 paró antes del calendario

## En una frase

**La pantalla existe, está en producción desde el despliegue del 2026-08-20 (`6fa3cff1`), y nadie la ha
usado.** Es adopción, no trabajo pendiente. El cuello de botella es la captura —trampa 3 del método
`antes-del-almuerzo`—, pero la captura que falta son dos clics y tres decisiones, no una semana de desarrollo.

## Lo medido (2026-08-21, solo lectura)

| Dato | Dev (73) | Producción (73) |
|---|---|---|
| Cronograma: frentes (encabezados) con fecha de inicio | 38 de 38 | **42 de 42** |
| Hojas del cronograma en la semana activa | 277 | 324 |
| Paquetes con insumos asignados | 0 | **62** (304 insumos; el 20-08 eran 58/278: sigue avanzando) |
| · de modalidad `contrato` / `orden_compra` (generan proceso) | — | 48 / 10 = **58 contratables** |
| · `consumo_directo` / `no_contratable` (no llevan fecha por diseño) | — | 1 / 3 |
| Amarres paquete → frente (`pdc_paquete_frente`) | 0 | **0** |
| Plan calculado (`pdc_plan_paquete`, `pdc_plan_paso`) | 0 | 0 |
| Pasos configurados por obra (`pdc_proyecto_pasos`) | 0 | 0 (= los siete de siempre, por diseño) |
| Catálogo `general_pasos_contratacion` | 9 | 9 |
| Catálogo `general_paquetes_contratacion` | 221 (169 con duración de referencia) | 221 |
| `pdc_insumo_actividades.unique_id` en NULL | 820/820 | 820/820 |

**Motor de sugerencias corrido en producción** (`PlanFechasService::sugerenciasYMotivos(73)`, solo SELECT):
**55 paquetes con frente propuesto** (53 por correspondencia curada del catálogo, 2 por similitud) y **3 sin
propuesta** porque su rama no tiene nodo asignado: `MUROS Y CHAPAS`, `TANQUES` e `IMPREVISTOS`. 55 + 3 = 58,
exactamente los contratables. Cuadra sin resto.

## 1. Qué falta exactamente para pasar de paquetes a plan con fechas

La cadena que el código ya implementa es:

```
paquetes con insumos  →  amarrar cada paquete a un frente del cronograma  →  «Recalcular»
(pdc_insumo_paquete)     (pdc_paquete_frente, POST /plan-compras/api/plan/amarrar)   (pdc_plan_paquete + pdc_plan_paso)
```

- **No falta pantalla.** `#/ensamble/plan` (`PlanFechas.tsx`) lista los paquetes sin frente con la propuesta
  del motor preseleccionada, tiene «Aceptar sugeridos» en lote, un desplegable por paquete para los que no
  tienen propuesta, y el botón «Recalcular» que escribe las fechas. El bundle de producción la contiene.
- **No falta dato de entrada.** El cronograma de Da Porto tiene sus 42 frentes con fecha; el presupuesto
  está cargado; los paquetes están confirmados a mano.
- **No falta decisión de negocio** salvo tres: a qué nodo del cronograma amarrar `MUROS Y CHAPAS` y
  `TANQUES`, y confirmar que `IMPREVISTOS` no se amarra (es provisión, no compra — el doc del módulo ya lo
  dejó escrito para B1).
- **El paso no es más difícil de lo que parece.** Es más fácil: el trabajo duro (curar 53 correspondencias
  rama→frente en el catálogo global) ya se hizo en julio.

Lo único no trivial: **19 de los 62 paquetes usan tipos del catálogo sin `duracion_ref`**. Para ellos
`calcular()` no falla: reparte la **mediana por tipo** del histórico de la empresa entre los pasos y marca
`duracion_provisional = 1` (el endpoint devuelve `sinDuracion`). El plan nace completo, con 19 paquetes cuya
duración es estimada y se ve así en pantalla. Aceptable para arrancar; afinarlas es trabajo de compras, después.

## 2. ¿Existe y nadie la usó, o no existe?

**Existe y nadie la usó.** Dos pruebas:

1. El repo de producción está en `6fa3cff1` (2026-08-20 17:05) y `public/pdc-app/assets/pdc.js` contiene la
   ruta `ensamble/plan`.
2. El doc del módulo registra que en julio, en desarrollo, Da Porto llegó a tener 11 cabeceras y 77 filas de
   paso (A4.1, línea base en `goals/pdc-a41-pasos-configurables/`). Ese dato se perdió al resembrar dev; en
   producción nunca se generó porque nadie amarró.

Por qué es plausible que nadie la usara: la persona que confirmó 304 insumos trabajó en `#/ensamble/paquetes`
y en el asistente. El plan está en **otra pestaña** de la misma barra, y amarrar exige el permiso
`lps.paquetes_contratacion.editar` (el mismo de asignar insumos, así que no es RBAC lo que frena). Nadie le
dijo «ya puede seguir». Hipótesis, no medición: **no hay registro de quién entró a `#/ensamble/plan`**, y
preguntarle a la persona cuesta menos que instrumentarlo (sección 7 del método).

## 3. Qué se necesita para cargar el plan de Da Porto

Pasos concretos, con quién y cuánto:

| # | Paso | Quién | Tiempo |
|---|---|---|---|
| 1 | Entrar a `#/ensamble/plan` en producción con un usuario con `lps.paquetes_contratacion.editar` | compras (la misma persona de los paquetes) o Felipe | 1 min |
| 2 | Revisar la lista de 55 propuestas y pulsar «Aceptar sugeridos». Opcional pero recomendado: ojear las 2 que vienen por similitud, no por catálogo | compras | 20–40 min |
| 3 | Elegir frente en el desplegable para `MUROS Y CHAPAS` y `TANQUES`; dejar `IMPREVISTOS` sin amarrar | compras con el residente (es secuencia constructiva, no compras) | 10 min + una llamada |
| 4 | Pulsar «Recalcular». Esperar `calculados = 55–57`, `sinDuracion ≈ 19` | quien hizo el paso 2 | 1 min |
| 5 | Asignar responsable por paquete (columna editable o acción en lote). Sin esto, la hoja de la Torre muestra pasos vencidos **sin dueño**, y un paso sin dueño no pasa el Test de la Decisión | director / compras | 30–60 min |
| 6 | Revisar y, si hace falta, configurar pasos de la obra en `#/ensamble/plan/pasos` (¿Da Porto pasa por Licify o por aprobación del cliente?). Si no, los siete de siempre sirven | director | 15 min, **opcional** |

**Total honesto: 2–3 horas de una persona, en un día.** Cero desarrollo. Ningún script ni migración: todo
pasa por la interfaz y queda auditado (`calculado_por`, `responsable_asignado_por`).

Lo que **no** hace falta para la hoja 8.6 y conviene no mezclar:

- Correr la migración B1 (`20260729_pdc_v2_amarre_cronograma.php`) en producción para llenar los 820
  `unique_id` de `pdc_insumo_actividades`. Eso alimenta subpaquetes y flujo de caja (Ola 3), no las fechas del
  plan. Es deuda real, pero aparte, y exige su propio gate (dry-run, respaldo, autorización).
- Afinar las 19 duraciones provisionales. Trabajo de compras, después de que el plan exista.

## 4. ¿Es realista la apuesta de la semana (D63)?

**Sí, con una condición: que alguien tenga nombre y fecha.** El trabajo cabe en una mañana. Lo que no es
realista es esperar a que ocurra solo: llevó del 2026-07-29 (pantalla lista) al 2026-08-19 (último
insumo confirmado) sin que nadie cruzara de pestaña, y no hay señal de que eso cambie sin un empujón.

Si el 2026-08-27 el plan sigue en cero, la hoja 8.6 nace vacía y la spec manda declararlo. Pero el texto del
vacío debería decir la verdad completa: no «no hay plan», sino **«58 paquetes esperan fechas: falta amarrar y
recalcular en Ensamble › Plan»**, con enlace. El vacío se convierte en la primera acción de la hoja.

Un matiz sobre la hoja misma: el día que el plan exista, **todos los pasos nacen sin `fecha_real`**. La
regla de vencimiento (`SeguimientoService`, la misma escala de D43: vencido · 1 · 2 · 3 · 6 semanas) marcará
como vencido todo paso cuya fecha planeada ya pasó — y Da Porto arrancó obra hace meses, así que «Elaboración
de pliegos» de estructura aparecerá vencida aunque el contrato esté firmado hace tiempo. **La primera semana
la hoja va a gritar en falso** hasta que compras registre avance real en Seguimiento. Eso no es defecto del
modelo: es la captura de avance, que empieza en cero, y es la segunda mitad del cuello de botella.

## 5. Qué relación tienen `general_pasos_contratacion` y `general_paquetes_contratacion` con el modelo nuevo

Las dos **son** el modelo nuevo, no restos del viejo. La plantilla del calendario ya está conectada:

- **`general_pasos_contratacion` (9 pasos)** es el catálogo que `PasosContratacionService::deProyecto()`
  lee en cada «Recalcular». Siete pasos traen `col_legacy` (sacan sus días del histórico de la empresa por
  tipo de paquete) y dos —Licify (1 día) y Aprobación del cliente (15 días)— llevan días fijos y solo
  entran si la obra los activa en `pdc_proyecto_pasos`. Cero filas por obra = los siete clásicos. Da Porto
  no necesita tocar nada aquí para tener plan.
- **`general_paquetes_contratacion` (221 tipos)** es el catálogo al que apuntan los 62 paquetes de Da Porto
  (`pdc_insumo_paquete.paquete_id`). Aporta tres cosas al calendario: la **modalidad** (decide si el paquete
  genera proceso: 58 sí, 4 no), la **duración de referencia** (169 de 221 la tienen; 19 de los 62 de Da
  Porto caen en los 52 sin ella → provisional) y las **correspondencias curadas rama→frente** que hoy
  resuelven 53 de las 55 propuestas.

No hay una plantilla desconectada esperando cable. Lo que hay es un catálogo con **52 tipos sin duración**;
llenarlos mejora el plan de toda obra futura, y es tarea del área de compras, no de desarrollo.

## Preguntas que habría grilleado (sesión sin interlocutor) y el supuesto tomado

1. *¿Quién es la persona que confirmó los 304 insumos y tiene permiso de editar?* — Supuesto: alguien de
   compras con rol A o D; `project_members` de Da Porto tiene 7 A y 3 D. No se consultaron nombres.
2. *¿Da Porto pasa por Licify o por aprobación del cliente?* — Supuesto: no; los siete pasos clásicos.
3. *¿Quién puede decir a qué frente van MUROS Y CHAPAS y TANQUES?* — Supuesto: el residente, en una llamada.
4. *¿Felipe quiere cargarlo él mismo para no depender de la obra esta semana?* — Supuesto: sí como plan B;
   puede hacerlo con `test.A`-equivalente de producción, pero las 3 ramas y los responsables son de la obra.

Cualquiera de estas respuestas cambia el «quién», ninguna cambia el «qué» ni el «cuánto».

## Ajustes propuestos a la spec

| Sección | Cambio concreto | Por qué |
|---|---|---|
| 4, supuesto 4 | Reescribir: «El plan con fechas no existe porque **nadie ha amarrado ni recalculado**, no porque falte construir algo. La pantalla está desplegada; el motor propone 55 de 58. Se asume que **[nombre]** lo carga antes del **[fecha]**. Medido el 2026-08-21.» | El supuesto actual deja abierto si es desarrollo o adopción. Es adopción, y un supuesto de adopción sin dueño nombrado es el mismo error que el método señala en la sección 0. |
| 8.6, cuadro del supuesto | Actualizar cifras (62 paquetes, 304 insumos, 58 contratables) y añadir: «Si la hoja nace vacía, el texto del vacío nombra la acción: *58 paquetes esperan fechas — amarrar y recalcular en Ensamble › Plan*, con enlace a `#/ensamble/plan`.» | La spec ya exige declarar el vacío; falta que el vacío sea accionable. Un «no hay datos» es museo; un «falta este botón» es decisión antes del almuerzo. |
| 8.6, lienzo | Añadir un aviso de primera semana: «Pasos con fecha planeada vencida y sin `fecha_real` registrada se muestran como **sin avance registrado**, no como vencidos, hasta que la obra haya registrado al menos un avance real en el paquete.» O, si se prefiere no tocar la regla, declarar en pantalla que el vencido incluye lo no registrado. | El plan nace con `fecha_real` en NULL en todos los pasos; la regla de D43 pintaría de rojo contratos ya firmados. La hoja gritaría en falso y se la dejaría de mirar en la primera semana (trampa 4: el histórico no es confiable hasta que se capture). |
| 8.6, lienzo | Sumar al conteo de cobertura una tercera cifra: **paquetes sin responsable**. | 5 de la carga es asignar responsable; sin eso el «paso vencido» no tiene a quién llamar y falla el Test de la Decisión. El dato ya está (`responsable_user_id`). |
| 13 (fases) o 18 (abierto) | Registrar como tarea de obra, no de desarrollo: «Cargar el plan de Da Porto: 2–3 h, [nombre], antes del [fecha]». Y aparte, como deuda técnica con gate propio: correr B1 (`unique_id` 820→2) en producción. | Separar lo que destraba la hoja (clics) de lo que no la destraba (migración), para que nadie gaste la semana en lo segundo creyendo que es lo primero. |
| 16 (riesgos) | Añadir: «Riesgo de duración provisional: 19 de 62 paquetes de Da Porto usan tipos sin `duracion_ref`; sus fechas salen de la mediana de la empresa. La hoja debe distinguirlos (`duracion_provisional`).» | Sin marcarlo, un vencimiento calculado sobre una mediana se lee con la misma autoridad que uno medido. |

## Archivos relacionados

- [[docs/pdc-v2]] · `src/Services/Pdc/PlanFechasService.php` · `src/Services/Pdc/SeguimientoService.php` ·
  `pdc-app/src/pages/PlanFechas.tsx`
- Spec (no se edita desde aquí): `docs/superpowers/specs/2026-08-20-replanteo-control-tower-design.md`
- [[docs/superpowers/notas/propuestas/2026-08-20-historial-compras-v1]] — la lección del v1 es la misma:
  esqueleto de plan que nunca se llenó. El v2 está a dos botones de repetirla.
