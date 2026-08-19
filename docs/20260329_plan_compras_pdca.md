---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-03-29
fuente: docs/20260329_plan_compras_pdca.md
resumen: Este documento es la fuente de trabajo para esta sesion.
---

# Plan de ejecucion - Plan de Compras

Este documento es la fuente de trabajo para esta sesion.

## Objetivo

Corregir y estabilizar el flujo `listado-actividades -> contratos -> pdc` con enfoque PDCA, reduciendo riesgo funcional, deuda legacy, errores de permisos, inconsistencias de semana y fallos de UX/UI.

## Alcance

- Modulo `/listado-actividades`
- Modulo `/contratos`
- Modulo `/pdc`
- Puntos legacy que afectan el flujo

## Decisiones base cerradas

- `Semana canonica`: usar siempre la `semana` seleccionada en sesion como contexto real de lectura y escritura. `Max_Semana` queda solo como referencia visual o regla de negocio, no como fuente alterna de guardado.
- `Tabla canonica de paquetes`: usar `general_dias_procesos_contratacion` como fuente unica para contratos y PDC.
- `Identificador canonico de actividadInicio`: guardar un identificador estable del cronograma, recomendado `Consecutivo_en_Programa`, no el nombre libre de la actividad.
- `Guardado canonico de PDC`: consolidar el guardado principal en un flujo unico coherente en `src/Controllers/Api/PdcApiController.php`.
- `Permisos`: toda lectura requiere permiso `ver` y toda mutacion requiere permiso `editar` en los 3 modulos.
- `Salidas`: no renderizar HTML crudo desde base de datos en tablas, mensajes o modales.

## Criterios globales de exito

- [ ] Los 3 modulos usan la misma semana efectiva para listar, editar, eliminar e importar.
- [ ] Ningun endpoint sensible acepta operar fuera del contexto real de sesion.
- [ ] RBAC `ver/editar` aplicado de forma consistente en APIs y UI.
- [ ] `listado-actividades` no destruye historico al importar CSV.
- [ ] `contratos` y `pdc` leen de la misma fuente canonica de paquetes.
- [ ] `pdc` permite editar, guardar, eliminar y regenerar sin perder informacion manual valida.
- [ ] No quedan modales, botones o handlers huerfanos en el flujo.
- [ ] No quedan redirects a rutas inexistentes.

## Orden de ejecucion

1. Fase 0 - baseline y preparacion
2. Fase 1 - contexto, permisos y endurecimiento transversal
3. Fase 2 - estabilizacion de `listado-actividades`
4. Fase 3 - estabilizacion de `contratos`
5. Fase 4 - reparacion funcional de `pdc`
6. Fase 5 - normalizacion de regeneracion y subcontratos
7. Fase 6 - limpieza legacy y UX/UI
8. Fase 7 - documentacion y regresion final

## Fase 0 - Baseline y preparacion

### Objetivo

Crear un baseline verificable antes de tocar codigo para medir mejora y evitar regresiones invisibles.

### Commit

Sin commit funcional obligatorio.

### Checklist

- [ ] Documentar el flujo actual de alta de actividad en `/listado-actividades`.
- [ ] Documentar el flujo actual de edicion de actividad en `/listado-actividades`.
- [ ] Documentar el flujo actual de eliminacion de actividad en `/listado-actividades`.
- [ ] Documentar el flujo actual de importacion CSV en `/listado-actividades`.
- [ ] Documentar el flujo actual de edicion de contratos tipo `1` en `/contratos`.
- [ ] Documentar el flujo actual de edicion de contratos tipo `2` en `/contratos`.
- [ ] Documentar el flujo actual de `actualizar PDC`, edicion de paquete, eliminacion y recarga en `/pdc`.
- [ ] Capturar el comportamiento actual con `semana == Max_Semana`.
- [ ] Capturar el comportamiento actual con `semana < Max_Semana`.
- [ ] Capturar el comportamiento actual para roles `A`, `D`, `OT`, `C`, `V`.
- [ ] Preparar dataset minimo de prueba con al menos una actividad de contrato separado y una de suministro e instalacion.
- [ ] Preparar dataset con un caso de PDC que tenga mas de un subcontrato.
- [ ] Respaldar tablas criticas: `*_actividades`, `*_pdc`, `*_papelera_pdc`, `general_dias_procesos_contratacion`.
- [ ] Anotar mensajes visibles actuales al usuario y mensajes esperados despues.
- [ ] Confirmar y dejar fijas las decisiones base de este documento.

### Cierre de fase

- [ ] Existe un baseline reproducible con escenarios manuales claros.
- [ ] Existe evidencia de los fallos actuales a corregir.

## Fase 1 - Contexto, permisos y endurecimiento transversal

### Objetivo

Eliminar inconsistencias de contexto `db/semana`, endurecer autorizacion y normalizar respuestas antes de corregir logica de negocio.

### Commit

`fix(compras): amarrar contexto y permisos del flujo actividades-contratos-pdc`

### Archivos foco

- `src/Controllers/Api/ListadoActividadesApiController.php`
- `src/Controllers/Api/ContratosApiController.php`
- `src/Controllers/Api/PdcApiController.php`
- `src/Legacy/actualizar_pdc.php`
- `public/js/rbac_capabilities.js`
- `views/listado-actividades/listadoActividades.view.php`
- `views/pdc/pdc.view.php`

### Checklist

- [x] Definir una regla unica para resolver `db` y `semana` desde sesion.
- [x] Revisar todos los endpoints del flujo y clasificar si son lectura, lectura auxiliar o mutacion.
- [x] Aplicar la regla de contexto unica en `/api/listado-actividades/list`.
- [x] Aplicar la regla de contexto unica en `/api/listado-actividades/save`.
- [x] Aplicar la regla de contexto unica en `/api/contratos/list`.
- [x] Aplicar la regla de contexto unica en `/api/contratos/save`.
- [x] Aplicar la regla de contexto unica en `/api/pdc/list`.
- [x] Aplicar la regla de contexto unica en `/api/pdc/save`.
- [x] Aplicar la regla de contexto unica en `/legacy/pdc/actualizar_pdc.php`.
- [x] Definir si los valores `db` y `semana` enviados por request se ignoran o se validan contra sesion.
- [x] Implementar validacion defensiva si llega `db` distinto al proyecto activo.
- [x] Implementar validacion defensiva si llega `semana` distinta a la seleccionada y no hay razon de negocio valida.
- [x] Aplicar permiso `lps.listado_actividades.ver` a lecturas de actividades.
- [x] Aplicar permiso `lps.listado_actividades.editar` a mutaciones de actividades.
- [x] Aplicar permiso `lps.contratos.ver` a lecturas de contratos.
- [x] Aplicar permiso `lps.contratos.editar` a mutaciones de contratos.
- [x] Aplicar permiso `lps.pdc.ver` a lecturas de PDC.
- [x] Aplicar permiso `lps.pdc.editar` a mutaciones de PDC.
- [x] Unificar la estrategia de autorizacion para que los 3 modulos no mezclen vacios de seguridad.
- [x] Revisar todas las ramas `opcion=...` de cada `save()` para que ninguna mutacion quede sin control.
- [ ] Normalizar respuestas JSON en una forma estable: `respuesta`, `mensaje`, `data`, `errores`.
- [x] Dejar de exponer mensajes internos o excepciones crudas al cliente.
- [x] Registrar errores tecnicos completos solo en logs.
- [x] Corregir el desacople entre `window.rbacCapabilities` y `window.RbacCapabilities`.
- [x] Elegir estrategia temporal: alias de compatibilidad o renombre total en vistas.
- [x] Crear una capacidad explicita para gestion de PDC si la semantica actual es ambigua.
- [ ] Revisar bloqueos visuales por rol para que no queden botones visibles sin permiso real.
- [ ] Validar manualmente que una request POST manipulada no edite datos sin permiso.

### Notas de avance

- Se creo `src/Support/ModuleRequestContext.php` para centralizar contexto de sesion, ignorar valores de request en conflicto y dejar trazas en logs.
- Se aplico RBAC `ver/editar` en los endpoints de actividades, contratos, PDC y en `src/Legacy/actualizar_pdc.php`.
- Se endurecieron updates y deletes sensibles para que respeten la semana activa de sesion.
- Se agrego compatibilidad frontend entre `window.RbacCapabilities` y `window.rbacCapabilities`, incluyendo `canManagePdC`.
- Pendiente de esta fase: QA manual por roles y revisar el frente visual completo de bloqueos.

### QA de fase

- [ ] Un usuario solo lectura puede listar y no puede mutar.
- [ ] Un usuario sin permiso no puede forzar POST exitosos por herramientas manuales.
- [ ] Un usuario editor puede operar sin romper su flujo normal.
- [ ] Manipular `db` o `semana` en request no permite tocar otro contexto.

### Cierre de fase

- [ ] Los 3 modulos comparten la misma politica de contexto.
- [ ] Los 3 modulos comparten la misma politica minima de permisos.
- [ ] No se filtran errores internos al frontend.

## Fase 2 - Estabilizacion de listado-actividades

### Objetivo

Volver confiable la fuente upstream del flujo, eliminando inconsistencias de semana, identificadores ambiguos y destruccion de datos en importacion CSV.

### Commit

`fix(listado-actividades): preservar integridad semanal y carga segura`

### Archivos foco

- `views/listado-actividades/listadoActividades.view.php`
- `src/Controllers/Api/ListadoActividadesApiController.php`
- Consumos relacionados en `src/Controllers/Api/ContratosApiController.php`

### Checklist

- [ ] Unificar definitivamente list, save, delete e import sobre la misma semana efectiva.
- [ ] Eliminar el patron actual de listar con `Max_Semana` y guardar con `semana`.
- [ ] Definir el identificador canonico de `actividadInicio`.
- [ ] Cambiar el `value` del selector de nueva actividad para guardar el identificador canonico.
- [ ] Cambiar el `value` del selector de edicion inline para guardar el identificador canonico.
- [ ] Ajustar el render de nombre legible de `actividadInicio` a partir del identificador canonico.
- [ ] Ajustar el consumo de `actividadInicio` en `/contratos` para que use el mismo identificador.
- [ ] Validar server-side `Id` numerico o formato permitido.
- [ ] Validar server-side `actividad` obligatoria y no vacia.
- [ ] Validar server-side `descripcionActividad` segun longitud y formato.
- [ ] Validar server-side `tipoContrato` permitido.
- [ ] Validar server-side `actividadInicio` segun el tipo esperado.
- [ ] Validar server-side `fechaInicio` con formato estricto.
- [ ] Revisar y definir la regla de unicidad por actividad y semana.
- [ ] Corregir la eliminacion para que verifique dependencias aguas abajo antes de borrar.
- [ ] Implementar una respuesta clara cuando no se pueda eliminar por dependencia.
- [ ] Hacer que la UI muestre de verdad el caso de no eliminacion.
- [ ] Reemplazar la fila vacia artificial por un estado vacio real y seguro.
- [ ] Limitar el disparador de edicion inline al boton editar, no a cualquier celda.
- [ ] Eliminar IDs duplicados `Id`, `opcion`, `codigo`, `btn_listar`.
- [ ] Eliminar handlers muertos o ligados a elementos no existentes.
- [ ] Escapar salida de tabla y mensajes para evitar XSS almacenado.
- [ ] Revisar si `nombreActividadInicio` debe quedar como dato derivado o eliminarse.
- [ ] Redisenar importacion CSV para que no use `TRUNCATE` global de `*_actividades`.
- [ ] Validar extension y contenido del archivo CSV antes de procesar.
- [ ] Validar encabezados esperados del CSV.
- [ ] Validar delimitador y codificacion del CSV.
- [ ] Definir estrategia de importacion: `upsert` por clave estable o reemplazo controlado por semana.
- [ ] Si hay reemplazo por semana, bloquear cuando existan dependencias sin confirmacion segura.
- [ ] Preservar columnas derivadas o enlazadas si una fila ya existe y el CSV no las incluye.
- [ ] Reportar filas insertadas, actualizadas, omitidas e invalidas al final de la importacion.

### QA de fase

- [ ] Crear actividad nueva y verla en la misma semana.
- [ ] Editar actividad existente sin cambiar de semana de forma silenciosa.
- [ ] Eliminar una actividad sin dependencias.
- [ ] Intentar eliminar una actividad con dependencias y recibir mensaje correcto.
- [ ] Importar CSV sin perder historico fuera de alcance.
- [ ] Verificar que `/contratos` sigue resolviendo correctamente la actividad de inicio.

### Cierre de fase

- [ ] `listado-actividades` es consistente por semana.
- [ ] No hay borrado masivo peligroso por importacion.
- [ ] El modulo entrega datos coherentes para `/contratos`.

## Fase 3 - Estabilizacion de contratos

### Objetivo

Corregir la asignacion contractual, eliminar flujos huerfanos y consolidar la fuente maestra de paquetes.

### Commit

`fix(contratos): alinear paquetes y corregir flujos huerfanos`

### Archivos foco

- `views/contratos/contratos.view.php`
- `src/Controllers/Api/ContratosApiController.php`
- `src/Legacy/actualizar_pdc.php`

### Checklist

- [ ] Consolidar `general_dias_procesos_contratacion` como fuente unica de catalogo y duraciones.
- [ ] Reemplazar la lectura residual de `general_paquetes_contratacion` en el generador legacy.
- [ ] Verificar que las columnas de duracion necesarias existen y coinciden con lo que espera PDC.
- [ ] Decidir si se migra historico desde `general_paquetes_contratacion` o si solo se elimina la lectura residual.
- [ ] Revisar la politica de alta de paquetes nuevos desde la UI.
- [ ] Recomendacion minima: desactivar creacion silenciosa de catalogos globales desde tags libres.
- [ ] Si se conserva alta desde UI, moverla a un flujo explicito con validacion y permiso claro.
- [ ] Acotar todos los `UPDATE` por registro y semana efectiva.
- [ ] Revisar las ramas auxiliares `actualizarListadoPaquetesContratacion` y `actualizarInsumosRecursos`.
- [ ] Asegurar que las ramas auxiliares respetan contexto y permisos.
- [ ] Dejar de devolver HTML crudo en `contratosAsociados` si es viable en esta fase.
- [ ] Si no se cambia a estructura, al menos escapar todo valor antes de renderizar.
- [ ] Corregir la visualizacion de errores para que la vista muestre `mensaje` y no solo `respuesta`.
- [ ] Eliminar el boton `Nuevo Contrato` que hoy apunta a `#modal_nuevo_contrato` inexistente.
- [ ] Eliminar cualquier referencia a modales o formularios que no existen realmente.
- [ ] Limitar la edicion al boton editar y no a cualquier click en la fila.
- [ ] Revisar si `actualizarFechaInicio()` sigue siendo necesario o quedo como helper huerfano.
- [ ] Limpiar markup invalido y IDs repetidos en el modal.
- [ ] Revisar que el rol `C` no vea ni pueda activar acciones sin sentido.

### QA de fase

- [ ] Editar actividad con `tipoContrato = 1` y verificar persistencia de `S` y `MO`.
- [ ] Editar actividad con `tipoContrato = 2` y verificar persistencia de `SI`.
- [ ] Confirmar que los paquetes seleccionados salen de la fuente canonica.
- [ ] Confirmar que los mensajes de error son visibles y utiles.
- [ ] Confirmar que `/pdc` consume los mismos paquetes despues de la actualizacion.

### Cierre de fase

- [ ] `contratos` y `pdc` leen de la misma fuente de paquetes.
- [ ] No existen flujos huerfanos visibles en el modulo.
- [ ] La edicion contractual es consistente y segura.

## Fase 4 - Reparacion funcional de pdc

### Objetivo

Recuperar los flujos rotos visibles de edicion, guardado, eliminacion y persistencia del plan de compras.

### Commit

`fix(pdc): restaurar guardado eliminacion y persistencia del plan de compras`

### Archivos foco

- `views/pdc/pdc.view.php`
- `src/Controllers/Api/PdcApiController.php`

### Checklist

- [ ] Corregir el flujo de eliminacion para que frontend y backend usen el mismo opcode.
- [ ] Unificar el nombre del identificador entre vista y controlador.
- [ ] Decidir si el opcode final sera `eliminar` o un nombre mas explicito, y dejarlo consistente.
- [ ] Eliminar el redirect roto a `pdc.php`.
- [ ] Reemplazar el redirect por recarga de tabla o navegacion correcta a `/pdc`.
- [ ] Alinear lectura y guardado de PDC a la misma semana efectiva.
- [ ] Consolidar el guardado del modal en una sola ruta funcional coherente.
- [ ] Hacer que `modificar()` persista todos los campos realmente editables del modal.
- [ ] Persistir datos de proveedor adjudicado: NIT, nombre, correo, tipo, referencia a proveedor existente.
- [ ] Persistir `fechaInicioProyectada`.
- [ ] Revisar y aclarar el mapping entre `fechaInicioProyectada`, `fechaRealInicioProyectadaContrato` y columnas reales de BD.
- [ ] Revisar si `adjudicarPdc()` debe integrarse al flujo principal o eliminarse.
- [ ] Revisar si `adjudicarContrato()` debe integrarse al flujo principal o eliminarse.
- [ ] Revisar si `guardarActividad()` sigue siendo un endpoint real o huerfano.
- [ ] Revisar si `eliminarActividad()` sigue siendo un endpoint real o huerfano.
- [ ] Persistir correctamente el estado del proceso si debe ser dato guardado y no solo calculo efimero.
- [ ] Escapar textos renderizados en tabla, modal y mensajes.
- [ ] Corregir labels rotos, atributos invalidos e IDs duplicados en la vista.
- [ ] Revisar la logica que oculta y limpia secciones para que no destruya datos validos al cambiar fechas.

### QA de fase

- [ ] Editar un paquete y confirmar persistencia despues de cerrar y reabrir.
- [ ] Registrar proveedor nuevo y reutilizar proveedor existente.
- [ ] Eliminar un subcontrato y confirmar recarga correcta.
- [ ] Confirmar que no hay navegacion a rutas inexistentes.
- [ ] Verificar que el estado visual del proceso coincide con datos persistidos.

### Cierre de fase

- [ ] `pdc` permite editar, guardar y eliminar correctamente.
- [ ] No hay rutas invalidas en el flujo.
- [ ] Los campos del modal no se pierden por falta de persistencia.

## Fase 5 - Normalizacion de regeneracion y subcontratos

### Objetivo

Hacer segura, idempotente y no destructiva la regeneracion del plan y el despiece de subcontratos.

### Commit

`refactor(pdc): normalizar regeneracion y despiece de subcontratos`

### Archivos foco

- `src/Legacy/actualizar_pdc.php`
- `src/Controllers/Api/PdcApiController.php`

### Checklist

- [ ] Inventariar todos los campos recalculados automaticamente.
- [ ] Inventariar todos los campos que son captura manual del usuario.
- [ ] Separar los campos manuales que nunca deben perderse al regenerar.
- [ ] Definir clave estable de preservacion por fila de PDC.
- [ ] Construir un mapa temporal de datos preservables antes de regenerar.
- [ ] Envolver la regeneracion completa en transaccion.
- [ ] Eliminar cualquier `delete + insert` sin red de seguridad transaccional.
- [ ] Reaplicar campos manuales preservables despues del recalculo.
- [ ] Hacer que la regeneracion sea idempotente.
- [ ] Confirmar que ejecutar dos veces seguidas no duplica ni destruye datos validos.
- [ ] Corregir la clonacion de subcontratos para que copie todos los campos necesarios.
- [ ] Soportar aumento de `numeroSubcontratos` sin inconsistencias.
- [ ] Soportar reduccion de `numeroSubcontratos` sin basura residual.
- [ ] Definir politica segura cuando se reduce un numero de subcontratos con informacion manual ya capturada.
- [ ] Reemplazar definitivamente `general_paquetes_contratacion` por `general_dias_procesos_contratacion` en la regeneracion.
- [ ] Revisar si algun `sleep()` o workaround temporal puede eliminarse.
- [ ] Revisar que cambios en nombre de paquete no generen duplicados no controlados.

### QA de fase

- [ ] Regenerar PDC desde cero en semana limpia.
- [ ] Regenerar PDC despues de cambios en contratos.
- [ ] Aumentar subcontratos y validar clonacion completa.
- [ ] Reducir subcontratos y validar limpieza o bloqueo seguro.
- [ ] Confirmar que proveedor, fechas reales y valores no se pierden tras regenerar.

### Cierre de fase

- [ ] La regeneracion es transaccional.
- [ ] La regeneracion es idempotente.
- [ ] La regeneracion no destruye datos manuales validos.

## Fase 6 - Limpieza legacy y UX/UI

### Objetivo

Eliminar residuos legacy, corregir problemas de usabilidad y cerrar deuda visual sin reabrir cambios fuertes de negocio.

### Commit

`refactor(compras-ui): limpiar legado y unificar navegacion del flujo`

### Archivos foco

- `views/listado-actividades/listadoActividades.view.php`
- `views/contratos/contratos.view.php`
- `views/pdc/pdc.view.php`
- `src/Legacy/Endpoints/cambiar_pagina.php`
- `public/js/rbac_capabilities.js`

### Checklist

- [ ] Retirar botones huerfanos en los 3 modulos.
- [ ] Retirar modales huerfanos en los 3 modulos.
- [ ] Retirar handlers JS ligados a elementos inexistentes.
- [ ] Eliminar IDs duplicados restantes.
- [ ] Corregir markup invalido en formularios y modales.
- [ ] Hacer accesibles botones con iconos usando `aria-label`.
- [ ] Convertir controles clicables no semanticos a botones o elementos accesibles.
- [ ] Unificar la forma de mostrar mensajes, errores y confirmaciones.
- [ ] Unificar el criterio de edicion para tablas de los 3 modulos.
- [ ] Deduplicar cargas repetidas de librerias obvias.
- [ ] Revisar toolbars para evitar navegacion dependiente de HTML legacy innecesario.
- [ ] Revisar si `legacy/cambiar_pagina.php` puede quedar solo como compatibilidad.
- [ ] Unificar el consumo del helper de RBAC estabilizado en Fase 1.
- [ ] Revisar consistencia terminologica entre `Plan de Compras`, `PDC`, `Contratos` y `Actividades`.
- [ ] Revisar scroll, modales y toolbar en desktop.
- [ ] Revisar scroll, modales y toolbar en mobile.
- [ ] Limpiar funciones JS muertas y comentarios legacy obsoletos.

### QA de fase

- [ ] Recorrido completo entre los 3 modulos sin encontrar botones muertos.
- [ ] Validacion por teclado y mouse.
- [ ] Validacion de layout responsive.
- [ ] Validacion de mensajes y confirmaciones consistentes.

### Cierre de fase

- [ ] No quedan controles huerfanos.
- [ ] La navegacion es coherente.
- [ ] La UX basica del flujo queda estable.

## Fase 7 - Documentacion y regresion final

### Objetivo

Dejar trazabilidad, checklist de regresion y decisiones explicitas para mantenimiento futuro.

### Commit

`docs(qa): documentar regresion del flujo plan de compras`

### Archivos foco

- `CHANGELOG.md`
- `ROADMAP.md`
- Documentacion adicional si aplica

### Checklist

- [ ] Documentar decisiones finales tomadas en esta ejecucion.
- [ ] Documentar la semana canonica adoptada.
- [ ] Documentar la tabla canonica de paquetes adoptada.
- [ ] Documentar el identificador canonico de `actividadInicio`.
- [ ] Documentar el flujo canonico de guardado de PDC.
- [ ] Actualizar changelog con fixes funcionales visibles.
- [ ] Actualizar roadmap con deuda remanente no resuelta.
- [ ] Dejar checklist manual de regresion completo del flujo `actividad -> contrato -> actualizar PDC -> editar -> eliminar`.
- [ ] Incluir matriz minima por rol y por semana.
- [ ] Incluir lista de endpoints sensibles que no deben aceptar contexto externo a sesion.
- [ ] Incorporar casos limite descubiertos durante la implementacion.

### QA de fase

- [ ] Ejecutar el checklist completo de regresion una vez al final.

### Cierre de fase

- [ ] Existe documentacion suficiente para mantener el flujo.
- [ ] Existe un checklist claro para futuras regresiones.

## Puertas entre fases

- [ ] No iniciar Fase 2 hasta cerrar Fase 1.
- [ ] No iniciar Fase 4 hasta que `contratos` y `pdc` usen la misma fuente de paquetes.
- [ ] No iniciar Fase 5 hasta que el guardado basico de `pdc` funcione correctamente.
- [ ] No cerrar Fase 6 sin una pasada completa de regresion manual.

## Fuente de referencia durante la sesion

Usar este archivo como plan maestro y fuente de verdad operativa para las siguientes tareas de esta sesion.
