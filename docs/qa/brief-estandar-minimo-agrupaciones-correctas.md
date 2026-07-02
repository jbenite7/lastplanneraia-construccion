# Brief: Estandar minimo para agrupaciones correctas

Fecha: 2026-07-01  
Proyecto base de contraste: Optimizacion Aeropuerto JMC  
Insumos: feedback de Listado de Actividades JMC, feedback ultradetallado de reunion Plan de Compras Da Porto y transcripcion Plan de Compras Da Porto.

## 1. Proposito

Definir el estandar minimo que debe cumplir el motor semi-automatico antes de marcar una propuesta como `Lista`.

El objetivo no es que el sistema agrupe mas, sino que agrupe mejor: cada propuesta debe poder explicar por que todas sus actividades fuente pertenecen al mismo alcance operativo, tecnico y contractual.

## 2. Problema que debemos corregir

El motor actual puede identificar una familia correcta, pero eso no garantiza una agrupacion correcta.

Una familia como `Red Electrica`, `Red Hidrosanitaria`, `Enchapes`, `Cabinas de Bano` o `Telecomunicaciones` puede contener actividades muy distintas: retiros, desmontes, suministros, instalacion, pruebas, equipos, redes provisionales, redes definitivas, accesorios, tableros, aparatos, informes o actividades administrativas.

Cuando todas esas fuentes se agrupan solo porque comparten familia, el resultado parece tecnicamente relacionado, pero puede ser incorrecto para gestion, contratacion, compras, seguimiento y control.

## 3. Principio rector

Una propuesta solo puede quedar `Lista` si el sistema puede demostrar tres cosas:

1. Que cada actividad fuente pertenece al mismo alcance comun.
2. Que no hay conflictos tecnicos, operativos o contractuales entre las fuentes.
3. Que la propuesta resultante es accionable para el usuario.

Si una de esas tres condiciones falla, la propuesta debe quedar `Por revisar` o `No recomendada`.

## 4. Definicion de agrupacion correcta

Una agrupacion es correcta cuando todas sus actividades fuente comparten, como minimo:

- Misma familia normalizada.
- Mismo entregable o alcance principal.
- Misma naturaleza de trabajo: suministro, instalacion, retiro, desmonte, fabricacion, prueba, mantenimiento, alquiler, administracion, aseo, consultoria u otro.
- Misma etapa logica del proceso: preparacion, ejecucion, cierre, entrega, mantenimiento, tramite o soporte.
- Misma responsabilidad operativa: quien suministra, quien instala, quien garantiza, quien asume desperdicio y quien controla calidad.
- Misma modalidad esperada: todo costo, suministro, mano de obra, suministro mas mano de obra, administracion, alquiler, pedido a proveedor, contrato especializado o hibrido.
- Sin incompatibilidades explicitas entre las actividades fuente.

La fecha no debe usarse para validar si la actividad existe. Da Porto deja claro que la revision no debe depender del orden de obra. Sin embargo, la fecha si sirve como senal de coherencia temporal y para seleccionar la actividad de inicio.

## 5. Estandar minimo de una propuesta

Cada propuesta generada por el motor debe incluir:

- Actividad propuesta.
- Estado: `Lista`, `Por revisar` o `No recomendada`.
- Actividad de inicio en formato visible: `Actividad | Fecha Inicio`.
- Descripcion resumida del alcance comun.
- Lista auditada de actividades fuente.
- Razon de agrupacion.
- Riesgos o conflictos detectados.
- Modalidad sugerida cuando aplique a compras o contratos.
- Estado de definicion: confirmado, por confirmar, pendiente diseno, pendiente cotizacion, pendiente decision o manual.
- Siguiente accion recomendada.

La descripcion no debe ser una concatenacion de textos fuente. Debe ser una sintesis del alcance comun validado. Las actividades fuente deben ir en una lista separada y auditable.

## 6. Evidencia obligatoria por actividad fuente

Cada actividad fuente usada en una propuesta debe conservar:

- Identificador o `unique_id`.
- Nombre original de la actividad.
- Fecha de inicio.
- Capitulo, ruta o contexto disponible como evidencia, no como criterio automatico para crear otra actividad.
- Familia detectada.
- Regla o criterio que produjo el match.
- Nivel de confianza.
- Explicacion breve de por que entra en el grupo.
- Senales de riesgo: fallback, baja confianza, ambiguedad, mezcla de acciones, diseno pendiente o posible separacion contractual.

Sin esta evidencia, la propuesta no debe quedar `Lista`.

## 7. Reglas de incompatibilidad

El motor debe bloquear el estado `Lista` cuando una misma agrupacion mezcle cualquiera de estos pares sin justificacion explicita:

- Retiro, desmonte o demolicion con instalacion nueva.
- Suministro con instalacion cuando puedan tener responsables, garantias o impuestos distintos.
- Pedido a proveedor con contrato de obra.
- Actividad provisional con actividad definitiva.
- Obra civil con equipo, alquiler u operacion de maquinaria.
- Gestion, informes, tramites o permisos con ejecucion fisica.
- Aseo, entrega o cierre con construccion.
- Revoque humedo con revoque seco.
- Deteccion contra incendio con extincion contra incendio.
- Aparatos, griferias o accesorios con redes o tuberias.
- Cabinas, espejos, ventaneria, barandas y pasamanos como si fueran una sola carpinteria.
- Actividades con disenos pendientes como si estuvieran confirmadas.

Cuando exista una mezcla de este tipo, el motor puede proponer una revision, pero no debe afirmar que la agrupacion esta lista.

## 8. Criterio contractual incorporado desde Da Porto

El feedback de Da Porto establece una regla critica: la separacion no debe ser solo tecnica, tambien debe ser contractual.

Por tanto, dos actividades tecnicamente parecidas deben separarse si cambian:

- Contratista.
- Proveedor.
- Modalidad de compra.
- Responsable del suministro.
- Responsable de instalacion.
- Garantia.
- Desperdicio o rendimiento.
- IVA, razon social o tratamiento tributario.
- Forma de pago o hito de control.

Ejemplos del criterio:

- Concreto, acero, aparatos sanitarios y griferias pueden ser pedidos u ordenes, no contratos.
- Pintura e impermeabilizacion pueden requerir todo costo por control de desperdicio, rendimiento y garantia.
- Carpinteria de madera puede partirse en fabricacion/suministro e instalacion.
- Contra incendio debe distinguir deteccion y extincion.
- Revoques deben separar humedo y seco.
- Torre grua, malacate y equipos deben tratarse como alquiler, operacion o equipo, no como una actividad de obra tradicional.

## 9. Estados minimos

El motor debe usar estos estados con disciplina:

`Lista`: evidencia completa, sin conflictos, alcance homogeneo y accionable.

`Por revisar`: hay una propuesta util, pero existen dudas de alcance, modalidad, responsable, diseno, proveedor, contrato, garantia o mezcla de actividades.

`No recomendada`: no hay base suficiente para crear una actividad, contrato o compra accionable.

`Manual`: el usuario debe crear o ajustar una actividad porque el programa no trae suficiente informacion o porque el alcance depende de decisiones de diseno o reunion.

## 10. Criterios para marcar una propuesta como Lista

Una propuesta solo puede ser `Lista` si cumple todo lo siguiente:

- Todas las fuentes tienen familia detectada sin fallback riesgoso.
- Todas las fuentes comparten el mismo entregable principal.
- No hay verbos incompatibles entre fuentes.
- No mezcla suministro, instalacion, retiro, desmonte, prueba, gestion o cierre sin una razon valida.
- No mezcla modalidades contractuales.
- No hay diseno pendiente que cambie el alcance.
- La actividad de inicio muestra nombre de actividad y fecha.
- La descripcion resume el alcance comun, no enumera actividades distintas.
- El usuario podria aprobarla sin abrir el detalle tecnico.

## 11. Criterios para enviar a revision

Debe quedar `Por revisar` cuando:

- Hay varias actividades dentro de la descripcion.
- El grupo contiene mas de un tipo de trabajo.
- La familia es correcta pero demasiado amplia.
- La fuente incluye retiros o desmontes junto con instalacion.
- Existen dudas de compra vs contrato.
- El alcance puede dividirse por proveedor, garantia, impuesto o modalidad.
- Hay disenos pendientes.
- La regla encontro coincidencia por fallback.
- El grupo tiene muchas fuentes y no hay resumen comun claro.
- El motor no puede explicar por que cada fuente pertenece al grupo.

## 12. Criterios para no recomendar

Debe quedar `No recomendada` cuando:

- No hay familia confiable.
- La actividad fuente no es accionable.
- El texto es solo codigo, capitulo, titulo o agrupador.
- La fuente corresponde a gestion documental, periodo, hito o referencia sin alcance ejecutable.
- La propuesta depende de informacion externa no disponible.
- La agrupacion propuesta seria enganosa para compras, contratos o seguimiento.

## 13. Estandar de descripcion

La descripcion debe responder: que se hara, sobre que alcance y bajo que condicion.

Debe evitar:

- Listas largas separadas por comas.
- Copiar todas las actividades fuente.
- Mezclar acciones incompatibles.
- Incluir textos que contradicen la actividad propuesta.
- Usar una descripcion de retiro para una actividad de instalacion.

Formato recomendado:

`Alcance comun: [accion principal] de [elemento/sistema] en [contexto], bajo modalidad [modalidad si aplica].`

Si el alcance comun no se puede sintetizar en una frase clara, la propuesta debe quedar `Por revisar`.

## 14. Actividad de inicio

La actividad de inicio debe mostrarse siempre como:

`Actividad fuente | Fecha Inicio`

No basta con guardar el identificador tecnico. El usuario necesita ver que actividad ancla la propuesta y desde cuando inicia.

Cuando haya varias fuentes, el sistema debe explicar por que escogio esa fecha:

- primera fecha del alcance comun;
- primera actividad ejecutable;
- actividad hito;
- fecha por confirmar.

## 15. Aplicacion a casos JMC

### Acero de Refuerzo y Estructural

Si la actividad propuesta es acero, pero la descripcion habla de `Enterrada, Bajantes y punta captadora`, la propuesta no es confiable. Debe quedar `Por revisar` o `No recomendada` hasta que el motor explique la relacion real entre las fuentes y el alcance propuesto.

### Enchapes Ceramicos en Muros

Si la descripcion contiene varias actividades, especialmente retiro/desmonte junto con instalacion o acabado, no puede quedar `Lista`. Debe separarse por accion y alcance.

### Red Electrica

No debe agrupar en una sola propuesta provisionales, canalizaciones, tableros, cableado, aparatos, mastiles, conexiones, accesorios, desmontes y redes definitivas. La familia puede ser correcta, pero la agrupacion no lo es.

### Cabinas de Bano

Si la descripcion hace referencia a retiros y desmontes, pero la propuesta es instalacion de cabinas, hay contradiccion de alcance. Debe bloquearse como `Lista`.

### Red Telecomunicaciones

Un grupo con decenas de fuentes y muchas actividades unicas necesita subagrupacion por sistema, accion, zona, proveedor o estado de diseno. No debe quedar listo solo por pertenecer a telecomunicaciones.

## 16. Modelos que debemos afinar

### Modelo de familia

Debe identificar la familia probable, pero tambien debe devolver evidencia y riesgos. La familia es un punto de partida, no una decision final.

### Modelo de agrupacion

Debe construir grupos por dimensiones operativas, no solo por familia. La llave minima de agrupacion para `/listado-actividades/` debe considerar:

- familia;
- entregable;
- accion principal;
- modalidad;
- etapa;
- responsable probable;
- estado de definicion.

Contexto, capitulo, piso, eje, zona, frente, intervencion o sub-obra no deben crear otra actividad por si solos. Deben conservarse como evidencia de fuentes y alimentar `/contratos/`, donde se decide si una actividad operativa requiere uno o varios contratos, compras o paquetes.

### Modelo contractual y de compras

Debe decidir si el alcance apunta a contrato, compra, pedido, alquiler, administracion, todo costo o modalidad hibrida.

Este modelo debe usar las reglas de Da Porto: separar por garantia, proveedor, responsabilidad, desperdicio, impuesto, razon social y forma de control.

### Modelo de revision

Debe explicar por que algo queda `Por revisar`. No basta con bajar la confianza. La razon debe ser entendible por el usuario:

- mezcla retiro e instalacion;
- varias modalidades contractuales;
- diseno pendiente;
- posible pedido sin contrato;
- requiere separacion por proveedor;
- no hay evidencia suficiente.

### Modelo de aprendizaje

Cada feedback del usuario debe convertirse en un candidato a regla:

- regla de separacion;
- regla de union;
- sinonimo;
- incompatibilidad;
- modalidad contractual;
- excepcion por proyecto;
- actividad manual recurrente.

## 17. Campos minimos recomendados

Para cada sugerencia:

- `suggestion_id`
- `project_id`
- `module`
- `family_id`
- `family_name`
- `grouping_key_version`
- `grouping_dimensions`
- `proposed_activity`
- `proposed_description`
- `start_activity_label`
- `start_activity_date`
- `source_items`
- `homogeneity_score`
- `conflicts`
- `definition_status`
- `review_reason`
- `contract_modality`
- `purchase_or_contract`
- `responsibility_summary`
- `next_action`
- `user_feedback_status`

Para cada fuente:

- `unique_id`
- `original_activity`
- `clean_activity`
- `start_date`
- `chapter`
- `matched_rule`
- `matched_text`
- `confidence`
- `why_included`
- `risk_flags`

## 18. Metricas de aceptacion

Antes de considerar el modelo listo, debemos medir:

- 100% de propuestas `Lista` con evidencia de fuentes.
- 100% de propuestas `Lista` con `Actividad | Fecha Inicio`.
- 0 propuestas `Lista` con descripcion basada solo en concatenacion de fuentes.
- 0 propuestas `Lista` con conflictos no resueltos.
- 0 propuestas `Lista` con retiro/desmonte mezclado con instalacion sin justificacion.
- 100% de propuestas `Por revisar` con razon clara para el usuario.
- Tasa de aceptacion humana de propuestas `Lista` superior al 95% en muestra revisada.
- Tasa de correccion recurrente convertida en regla o criterio de aprendizaje.

## 19. Plan de avance recomendado

### Fase 1: Evidencia y transparencia

Sin cambiar todavia la logica de negocio, hacer que cada propuesta muestre sus fuentes, razon de agrupacion, actividad de inicio visible y riesgos detectados.

### Fase 2: Puerta de calidad

Cambiar la regla de estado para que ninguna propuesta sea `Lista` si no pasa homogeneidad, incompatibilidades y evidencia.

### Fase 3: Agrupacion operativa inteligente

Reemplazar la agrupacion por familia por una agrupacion multidimensional operativa: familia + accion + entregable + modalidad + etapa + estado. Contexto, zona, eje, piso o intervencion se mantienen como trazabilidad contractual, no como fragmentadores de listado.

### Fase 4: Aprendizaje desde feedback

Convertir feedbacks recurrentes en reglas versionadas, medibles y reversibles. Cada regla debe tener origen, fecha, proyecto, ejemplo positivo y ejemplo negativo.

## 20. Checklist minimo de revision humana

Para cada propuesta, el revisor debe poder responder:

- Que actividad fuente inicia la propuesta y en que fecha?
- Todas las fuentes hablan del mismo alcance?
- Hay retiros, desmontes o demoliciones mezclados con instalacion?
- Hay compras que no deberian ser contratos?
- Hay contratos que deberian dividirse?
- Hay garantia, desperdicio, impuesto o proveedor que obligue a separar?
- Hay disenos pendientes?
- La descripcion resume un alcance comun o enumera actividades distintas?
- El siguiente paso es aprobar, revisar, pedir dato, cotizar, crear contrato, crear pedido o crear actividad manual?

## 21. Criterio final de exito

El sistema cumple el estandar minimo cuando una propuesta `Lista` puede ser aprobada por el usuario sin descubrir despues que mezclaba alcances, responsables, compras o contratos diferentes.

La meta no es eliminar la revision humana. La meta es que la revision humana se enfoque en decisiones reales, no en corregir agrupaciones que el motor no debio haber marcado como listas.
