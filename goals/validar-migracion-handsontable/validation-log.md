---
capa: fuente
tipo: evidencia
estado: vigente
fecha: 2026-07-15
areas: [datos]
fuente: goals/validar-migracion-handsontable/validation-log.md
resumen: Registro de validación
---

# Registro de validación

> **Aviso de vigencia (2026-07-13):** las evidencias y resultados anteriores a la sección “Reauditoría del worktree actual” quedaron invalidados por deriva del código y desaparición de pruebas/artefactos. No deben usarse para cerrar Contratos hasta que cada flujo sea repetido contra el worktree y runtime actuales.

## Sprint exclusivo Contratos iniciado 2026-07-11

- Contrato efectivo: `goal.md` con la restricción posterior del usuario a `/contratos`.
- Fuera de alcance: BI, Listado, PDC, PG, PI y PS; no habrá commits, push ni despliegue.
- Worktree inicial: `main` adelantada 40 y atrasada 3; los cambios ajenos se preservan.
- Respaldo vigente: `/tmp/lps-aia-contratos-handsontable-before.sql` (86 MB), recreado al detectar que el archivo inicial había desaparecido; no se reanudarán pruebas destructivas sin comprobar su existencia.
- SHA-256 del respaldo vigente: `a83a4187a6b611a796d97f9f6366062ae409ae727b74376d01cc742689ab816f`.
- Línea base estática: una única construcción `new Handsontable`, pero la API todavía fabrica una fila vacía y el rol readOnly no coincide con la capacidad real del backend.
- Runtime DataTables: la vista excluye recursos principales, pero aún carga `mobile-table-fix.js` y conserva listeners/selectores DataTables compartidos.
- Estados: mobile puede mostrar simultáneamente vacío y error; cargando conserva datos anteriores.
- Modal: peticiones de catálogos no se cancelan ni se vinculan al registro activo; existe riesgo de mezcla al abrir registros distintos rápidamente.
- Pruebas: `contratos-handsontable.mjs` simula readOnly modificando el DOM y el E2E conserva CRUD opcional ajeno al flujo real.
- Navegador integrado: conexión nativa establecida; línea base visible pendiente de levantar `localhost:8081`.
- Viewports/temas: Mobile Dark/Linen, Tablet horizontal Dark/Linen y Desktop Dark/Linen pendientes.
- Evidencia pendiente: respaldo, RED/GREEN, persistencia/restauración, permisos reales, automatización completa y matriz visible.

### Correcciones TDD confirmadas

- RBAC RED: `tests/test_contratos_rbac_contract.php` falló porque `V` heredaba `lps.contratos.editar` y `auto_definir`.
- RBAC GREEN: el fallback de `V` conserva lectura sin escrituras y la UI reconoce `R` como editor de Contratos; test y sintaxis JS pasan.
- RBAC efectivo GREEN: el controlador exige `lps.contratos.ver`, resuelve `editar` y `auto_definir` con `RbacService`, y Handsontable/tarjetas/toolbar consumen esas capacidades emitidas por el servidor en vez de confiar en el rol oculto.
- Semana RED: JMC mostraba semana 5, pero `/api/contratos/list` consultaba la semana máxima 6 y dejaba HOT vacío.
- Semana GREEN: `ContratosHotModule` usa la semana seleccionada; el test JMC 5/6 pasa y el navegador integrado muestra 29 registros reales.
- Cantidad RED: vacío, cero, negativo y decimal se aceptaban; además el backend los convertía silenciosamente a 1 y truncaba valores mayores a 99.
- Cantidad GREEN parcial: HTML exige entero `>=1`, el guard del formulario bloquea el envío y el backend devuelve 422; unidad PHP y sintaxis pasan. Falta revalidar el navegador tras estabilizar la carga del modal.
- Cantidad omitida GREEN: un paquete presente ya no obtiene cantidad `1` por omisión del parámetro; `null`, vacío, cero, negativo y decimal se rechazan antes de escribir.
- Duraciones RED/GREEN parcial: el lote se valida completo (paquete, tipo y las siete duraciones enteras `>=0`) antes de cualquier escritura y se persiste dentro de una transacción con rollback ante fallo. Falta demostrar el modal pendiente, recarga, reapertura y restauración visible.
- Guardado atómico RED/GREEN parcial: actualización de la actividad, auditoría y trazabilidad comparten una transacción; cualquier fallo posterior al `UPDATE` ejecuta rollback. El contrato estático y la sintaxis pasan; falta comprobar una única petición, una traza y rollback forzado en E2E.
- Auto-definir validación RED/GREEN: feedback y apply rechazaban mal `SI` combinado con MO/S/OC y cantidades nulas, vacías, cero, negativas o decimales. Los ocho casos ahora se rechazan antes de mutar sugerencia o actividad; `test_auto_definir_contratos.php` pasa 24 casos, 0 fallos y 2 omisiones por datos locales.
- Permiso Auto-definir RED/GREEN: `/api/contratos/auto-assign` exige `lps.contratos.auto_definir`, igual que la capacidad de toolbar emitida por el servidor.
- Modal repetido RED/corrección en verificación: se eliminaron IDs duplicados, cada apertura usa token e identidad del registro, aborta catálogos anteriores, ignora respuestas tardías y limpia formulario, Select2 y estado al cerrar. Falta GREEN completo de aperturas A/B tras estabilizar la suite.
- Guardar UI RED/GREEN estático: serializa solo `#formularioEditarContratos`, bloquea doble clic mientras hay petición, deshabilita Guardar, libera en `always` y muestra éxito/error. Falta prueba de una petición y una traza reales.
- Resumen seguro RED/GREEN: API distingue modalidad, `Paquete:` e `Insumos/recursos:`; nombres no confiables se escapan y el cliente solo permite `b`/`br` controlados. Unidad de seguridad pasa; falta evidencia visible con registros que tengan recursos.
- Navegador integrado Mobile Linen: JMC semana 5 mostró 29 tarjetas equivalentes, cero overflow horizontal y cero runtime DataTables. Se detectó el badge de semana partido y se corrigió con tokens; repetición visual pendiente tras invalidar caché.
- Navegador integrado Mobile Dark: tarjetas respetan Dark y no tienen overflow, pero toolbar/selector heredaban superficie Linen; se añadieron overrides con `--ds-active-*`. Repetición visible pendiente; no se marca completado.
- Matriz técnica GREEN: Playwright recorrió Mobile Dark/Linen, Tablet horizontal 1024x768 Dark/Linen y Desktop 1440x900 Dark/Linen con 0 overflow de página, 0 holders con scroll-x y 0 controles, celdas o encabezados recortados. Sigue pendiente repetir y presentar las seis combinaciones en el navegador integrado.
- Restauración focal técnica GREEN: el fingerprint de `actividades` y `contratos_trazabilidad` vuelve al valor inicial; `auto_program_log` se excluyó porque no pertenece ni fue mutada por Contratos. El respaldo SQL completo vigente permanece como salvaguarda final.
- Modal GREEN técnico: identidad/familia, SI exclusivo, carrera A/B, Cancelar y Guardar+BIEN pasan. El cierre espera `shown.bs.modal` si Bootstrap aún está en transición y la reapertura espera retirar el backdrop; después de cerrar quedan modal oculto, `aria-hidden=true`, body desbloqueado y cero backdrops.
- RBAC protegido contra drift: una edición concurrente reintrodujo permisos V en el catálogo, el test falló y se corrigió. Además, `RbacService` niega explícitamente `editar`/`auto_definir` de Contratos a V aunque el catálogo vuelva a derivar; el gate está GREEN.
- Análisis estático GREEN: Contratos dejó de cargar dinámicamente el guard RBAC legacy y usa `BaseController::authorizePermission()`. PHPStan sobre controladores de vista/API, SemiAutoController y SemiAutoService finaliza sin errores.
- Auto-definir E2E-lite GREEN: `tests/browser/auto-definir-contratos.mjs` pasa 5/5. Cubre preview real, edición/feedback, selección, apply, recarga, persistencia, re-preview, undo y restauración.
- Auto-definir restauración GREEN: snapshot/fingerprint incluye actividades, runs, suggestions, feedback, decisions, assistant_feedback y trazabilidad. Tras cada caso, proyecto 73 vuelve a 0 actividades, no quedan seeds 990201-203 ni runs nuevos, y el último run vuelve al baseline del 2026-07-09.
- Runtime visible editor: cero recursos, estilos, plugins, wrappers, listeners y eventos DataTables; overflow de página medido en cero sobre JMC semana 5.
- Suite principal Contratos GREEN: `npx playwright test tests/browser/contratos-handsontable.mjs --workers=1` pasa 12/12. Incluye paridad API/HOT/mobile, semana elegida, cuatro estados sin filas sintéticas, filtros combinados y limpieza, fila física correcta, seis combinaciones técnicas, modal/cancelación, SI exclusivo, carrera A/B, guardado con recarga y sesiones reales `D`/`V`.
- Aislamiento reparado: una ejecución interrumpida dejó únicamente las semillas `990101/990102`; se eliminaron junto con su trazabilidad y la preparación ahora es idempotente. La huella posterior de `actividades` y `contratos_trazabilidad` vuelve al baseline después de cada caso.
- Cantidades y duraciones técnicas GREEN: `tests/browser/contratos-slot-quantities.mjs` pasa 3 pruebas y omite solo la ruta de preconstrucción fuera del recorte. Valida controles, rechazo de vacío/cero/negativo/decimal, las siete duraciones, atomicidad y restauración exacta del catálogo.
- Evidencia pendiente antes de revisión: flujo visible del modal de duraciones pendientes, E2E completo, repetición secuencial de las cuatro suites, matriz nativa integrada y comprobación final de restauración.

### Consolidado final Contratos listo para revisión del usuario

- Runtime: una sola construcción `new Handsontable`; `contextMenu: false`; cero scripts, estilos, plugins, wrappers, listeners, DOM o helpers DataTables en `/contratos`.
- Datos/estados: API, HOT y 29 tarjetas JMC semana 5 son equivalentes; loading, vacío, error y ready no crean filas sintéticas. La carga usa token+abort y el test invierte respuestas para impedir que una antigua pise la vigente.
- Tabla/filtros: encabezados y celdas alineados; anchos responsivos, wrap seguro, filtros de columna combinables y limpieza sin condiciones residuales; acciones resuelven la fila física filtrada.
- Contenido: modalidades, paquetes e insumos se distinguen; solo `b`/`br` controlados llegan al render y todo contenido no confiable se escapa. OC guarda el tipo canónico `Orden de Compra` y muestra `Orden de servicio/compra`.
- Modal: familia correcta, cabecera 12% del alto visible, X visible, 5 slots por modalidad, 40 Select2 y 20 cantidades; carrera A/B, aperturas repetidas, Guardar y Cancelar dejan cero mezcla o listeners duplicados.
- Modalidades/permisos: SI exclusiva; cada modalidad exige su propio paquete; códigos desconocidos devuelven 422. Sesiones reales `D` y `V` verificadas; V no ve acciones y backend devuelve 403.
- Guardar/cancelar: una petición por acción, guardado atómico con auditoría/traza, 404 para Id inexistente antes de crear duraciones, cierre y refresco correctos; Cancelar no escribe y restaura valores/Select2.
- Cantidades: vacío, cero, negativos y decimales se rechazan; las 20 columnas se ampliaron a `INT`, una cantidad 128 se guardó, recargó, reabrió y restauró.
- Catálogos reales: navegador integrado abrió Suministro con 64 opciones y búsqueda, sin mojibake ni scroll-x; MO=30, SI=106 y OC=3 por la conexión real de la app. Los 20 recursos son multiselección. Evidencia `/tmp/contratos-catalogo-real-final.png`.
- Duraciones: el modal pendiente muestra exactamente siete campos; vacío/decimal se bloquean sin petición, valores 1..7 se guardan, el contrato se reintenta, recarga y reabre sin pedirlos otra vez. Solo se aceptan cuatro tipos canónicos.
- Auto-definir: preview, revisión, edición/feedback, selección, apply, recarga, persistencia, re-preview y undo pasan; el snapshot incluye `programa`, `programa_consolidado`, actividades, runs, suggestions, feedback, decisions, assistant feedback y trazabilidad.
- Acciones: Contratos no ofrece creación/eliminación manual; por tanto no se presenta una acción que el backend rechazaría. Auto-definir y edición se ocultan según capacidad real.
- Toolbar/selector: misma fila cuando cabe, responsive al apilar, tokens/radius AIA, texto contenido y selector equilibrado. Verificado en navegador integrado.
- Matriz nativa final `sprint6`: Mobile 390x844 Dark/Linen (29 tarjetas), Tablet 1024x768 Dark/Linen y Desktop 1440x900 Dark/Linen. En las seis: cero page overflow/scroll-x, cero controles desbordados, cero HTML crudo, cero DataTables y consola limpia; tablet/desktop con headers/celdas alineados.
- Evidencia visible final: Desktop Linen 1280 con modal de `Implementacion PMT`, cabecera compacta, X, Guardar/Cancelar, 5 slots por sección, labels explicativos y cero overflow; captura `/tmp/contratos-desktop-modal-final.png`.
- Pruebas finales: `contratos-handsontable.mjs` 14/14; `auto-definir-contratos.mjs` 5/5; `contratos-slot-quantities.mjs` 5/5 y 1 skip de preconstrucción fuera del recorte; `contratos-full.spec.mjs` 1/1 con aserción de page/console/HTTP 500+.
- PHP/estático: 7 contratos PHP GREEN; auto-definir PHP 27 pass, 0 fail, 2 skips dependientes de datos; PHPStan 5 archivos sin errores; `git diff --check` limpio.
- Seguridad global: `test_global_table_safety.php` GREEN. Reconciliación general detecta deuda preexistente fuera de alcance en `optimizacionJMC.programacion_semanal` y 30 claves legacy `da_porto.actividades`; no se modificó.
- Restauración final: 0 usuarios `test.V` temporales y 0 semillas Contratos en actividades/programa/consolidado/trazas/duraciones/runs; proyecto 73 vuelve a 0 actividades. Respaldo vigente SHA-256 `a83a4187a6b611a796d97f9f6366062ae409ae727b74376d01cc742689ab816f`.
- Estado del goal: sprint técnico listo para revisión, sin commits/push/deploy. No se declara completo hasta aprobación explícita del usuario.

### Auditoría de cierre requisito por requisito — Contratos

| # | Estado | Evidencia autoritativa actual |
|---|---|---|
| 1 | Demostrado | HOT visible y alineado en tablet/desktop Dark/Linen; matriz nativa y test técnico. |
| 2 | Demostrado | 29 tarjetas por registro en mobile Dark/Linen, equivalentes a la API. |
| 3 | Demostrado | Runtime nativo y test: DataTables scripts/styles/plugin/DOM/listeners/helpers = 0; una instancia HOT. |
| 4 | Demostrado | Test compara IDs y `contratosAsociados` API/HOT/cards; capacidades del servidor gobiernan acciones. |
| 5 | Demostrado | Loading/empty/error/ready recorridos; cero filas sintéticas y carrera de cargas invertida cubierta. |
| 6 | Demostrado | Geometría nativa de headers/celdas coincide; cero clipping/overflow en las seis combinaciones. |
| 7 | Demostrado | Menús de filtro abiertos desde UI, combinación por Familia+Descripción y limpieza con 0 condiciones. |
| 8 | Demostrado | Escape servidor + sanitizer cliente; modalidad/paquete/insumos diferenciados y 0 HTML crudo visible. |
| 9 | Demostrado | Registro/familia correctos, header 12%, X visible, Cancelar sin escritura y Guardar+cierre. |
| 10 | Demostrado | MO/S/SI/OC, SI exclusiva, checkboxes AIA y tipos/códigos backend validados. |
| 11 | Demostrado | Cinco slots numerados por modalidad; selector searchable/taggable. Catálogo real abierto nativamente: 64 opciones S, 30 MO, 106 SI y 3 OC. |
| 12 | Demostrado | Ayuda “para este paquete”; inválidos bloqueados; cantidad 128 persiste con 20 columnas INT. |
| 13 | Demostrado | Labels explicativos, 20 selectores `multiple`, tags Select2, escape/wrap y persistencia tras recarga. |
| 14 | Demostrado | Siete duraciones, flujo pendiente, rechazo vacío/decimal, guardado 1..7 y reapertura persistente. |
| 15 | Demostrado | In-flight guard, listener namespaced, una petición por acción, feedback y refresco/cierre. |
| 16 | Demostrado | Cancelar produce 0 POST, restaura cantidad y limpia solicitudes/Select2/estado pendiente. |
| 17 | Demostrado | A/A repetido y carrera A/B invertida sin mezcla; 40 Select2 constantes y sin eventos duplicados. |
| 18 | Demostrado | Asistente visible + preview, edición/feedback, selección, apply, reload, persistencia y undo restaurado. |
| 19 | Demostrado | Auto-definir + selector en una fila cuando cabe y apilado responsive sin texto fuera. |
| 20 | Demostrado | Selector comparte patrón/tokens/radius/posición de los módulos migrados y margen consistente. |
| 21 | Demostrado | Solo edición/auto autorizadas; no existe eliminación manual en Contratos, por lo que no se expone acción inválida. |
| 22 | Demostrado | Login real `test.D` editor y `test.V` readOnly; V sin botones y API 403. Fixture temporal restaurada. |
| 23 | Demostrado | Dump previo con hash; paquetes/cantidad/recurso/duraciones tras reload; fingerprints y auditoría final en 0 semillas. |
| 24 | Demostrado | Mobile/Tablet horizontal/Desktop × Dark/Linen repetidos en navegador integrado, 0 overflow/theme mix/errors. |
| 25 | Demostrado | Cuatro suites objetivo modernizadas y verdes; 0 selectores/descripciones DataTables. |

La única condición no técnica pendiente es la aprobación explícita del usuario; por contrato, el goal sigue activo.

### Revalidación final exclusiva de Contratos — 2026-07-12

- Se estabilizó el harness sin cambiar el producto: el login espera URL `/proyectos` y una tarjeta real; la navegación a Contratos espera `domcontentloaded`, instancia HOT, estado `ready` y superficie visible. Esto retiró carreras que leían la fuente durante `loading` y timeouts falsos por `networkidle`.
- La sesión readOnly es autosuficiente: crea `test.V` y su membresía `V` solo si faltan, autentica por el formulario real, verifica ausencia de edición/Auto-definir y respuesta backend 403, y elimina el fixture en `finally`.
- Catálogos: la API conserva el valor almacenado pero repara solo la etiqueta visible con mojibake; la prueba exige catálogos reales MO/S, búsqueda, recursos múltiples y cero etiquetas `Ã`/`Â`.
- Drift detectado y corregido: una restauración externa había devuelto las 20 columnas `cantidad*` a `TINYINT`, provocando overflow al reintentar una cantidad 128 después de completar duraciones. Se reaplicó `20260711_expand_contratos_quantities_to_int.sql`; auditoría final: 20 `INT`, 0 columnas no `INT`.
- La revisión visual final detectó que un multiselect vacío conservaba `width: 100px` y recortaba la guía “Buscar o escribir insumos y recursos” aunque el contenedor tenía 424 px. TDD RED midió 100 px frente a 267 px requeridos; el CSS mínimo usa todo el ancho solo mientras no hay chips. GREEN focalizado y matriz/modal completos pasan; con chips se conserva el ancho adaptable.
- La vista nativa se recargó con `/css/contratos.css?v=20260712sprint7`: el campo vacío quedó en 414 px para una guía que requiere 267 px. La captura final muestra el texto completo en los cinco slots visibles, catálogo S abierto, 64 opciones reales y `ESTÁNDAR S` sin mojibake.
- Auditoría nativa final: Desktop Linen 1280x720, una superficie maestra HOT, 40 Select2, 20 cantidades, 20 multiselects, overflow de página/modal `0`, DataTables DOM/assets/plugin `0`, consola sin warnings/errores y logs locales sin HTTP 4xx/5xx con referer `/contratos`.
- Gate principal actual: `tests/browser/contratos-handsontable.mjs` pasa 14/14 en 2.3 min.
- Gate Auto-definir actual: `tests/browser/auto-definir-contratos.mjs` pasa 5/5 en 1.2 min, incluido `preview -> feedback/edición -> apply -> reload -> undo`.
- Gate cantidades/duraciones actual: `tests/browser/contratos-slot-quantities.mjs` pasa 5/5 en 26.6 s y omite únicamente la ruta de preconstrucción fuera del proyecto activo.
- Gate E2E actual: `e2e/tests/workflows/contratos-full.spec.mjs` pasa 1/1 en 21.3 s. El cierre ahora cierra la página y espera 500 ms antes de restaurar, igual que Auto-definir, para calcular el hash con el runtime en reposo.
- Restauración final auditada: `test.V=0`, actividades proyecto 73 `=0`, seeds de actividades/programa/consolidado `=0`, trazas `=0` y duraciones E2E `=0`.
- Huella restaurada de las 12 tablas del E2E: `b4541b9bdfe81949767561733df6496eb85b8f3d175ec8f3a804018331760972`.
- El respaldo de emergencia sigue intacto: `/tmp/lps-aia-contratos-handsontable-before.sql`, SHA-256 `a83a4187a6b611a796d97f9f6366062ae409ae727b74376d01cc742689ab816f`.
- Pendiente de entrega: volver a dejar el consolidado visible en el navegador integrado y detenerse para aprobación. El goal continúa activo hasta declaración explícita del usuario.

## Sprint exclusivo Listado de Actividades iniciado 2026-07-11

- Contrato efectivo: `goal.md` con recorte posterior exclusivo a `/listado-actividades`.
- Fuera de alcance: BI, Contratos, PDC, PG, PI y PS; no habrá commits, push ni despliegue.
- Worktree inicial: `main` adelantada 40 y atrasada 3; contiene cambios ajenos que se preservarán.
- Runtime local: `app`, `db` y `adminer` activos; PHP/JS/CSS servidos desde el worktree mediante bind mount.
- Gate: plan exclusivo aprobado en Plannotator; implementación TDD habilitada.
- Respaldo previo: `/tmp/lps-aia-listado-handsontable-before.sql` (94 MB).
- SHA-256: `4a747f5e4a8a345a74e3db8f0a2b1a927157570f42de6c21a25080018ba4499e`.
- Persistencia/restauración: pendiente de comparar contra snapshot y dump inicial.
- Navegador integrado: conexión nativa establecida; línea base visual pendiente.
- Matriz: Mobile Dark/Linen, Tablet horizontal Dark/Linen y Desktop Dark/Linen pendientes.
- Pruebas objetivo: `tests/browser/listado-actividades-handsontable.mjs` y `e2e/tests/workflows/listado-full.spec.mjs`.
- Evidencia pendiente: requisitos 1–21 completos, permisos reales editor/readOnly, cero DataTables y restauración final.

### Auditoría estática inicial — no cuenta como validación funcional

- DataTables residual: `mobile-table-fix.js`, CSS dinámico `table.dataTable`, reglas legacy compartidas y listener `#dt_cliente` para rol C.
- Runtime: `cargaParametros()` puede ejecutarse dos veces y duplicar cargas/listeners, incluida eliminación.
- HOT: filtros bloqueados por `pointer-events: none`; doble cabecera y riesgo de mapear fila visual a registro físico incorrecto tras filtrar.
- Backend: rol `V` hereda editar; CSV borra antes de validar contenido; modalidades no se normalizan en servidor.
- Pruebas: E2E puede omitir CRUD, consulta tabla legacy y no demuestra restauración; suite dedicada está ignorada por `.gitignore`.
- Evidencia navegador, pruebas RED, fixes, persistencia y restauración: pendientes.

### Flujo: semana solicitada y datos disponibles

- Problema: la vista mostraba la semana solicitada, pero la API consultaba siempre la máxima activa y devolvía vacío con registros existentes.
- RED: `tests/test_listado_actividades_project_scope.php` falló al existir una semana activa posterior sin familias.
- Corrección: endpoints de Listado usan `ModuleRequestContext['semana']`; se retiró el helper que sobrescribía la sesión.
- GREEN: `docker compose exec app php tests/test_listado_actividades_project_scope.php` pasa.
- Navegador integrado: sesión editor real, JMC semana 5; loading cambió a datos disponibles con familias, actividades, fechas y modalidades.
- Persistencia/restauración: no aplica; verificación de lectura sin mutación.
- Viewport/tema: Desktop 1440×900 Linen; las otras cinco combinaciones siguen pendientes.
- Evidencia pendiente: paridad profunda API/HOT, filtros, headers, consola/red y matriz completa.

### Correcciones Listado TDD y evidencia parcial

- Runtime DataTables: los cargadores compartidos respetan `__AIA_HANDSONTABLE_ONLY__`; el contrato ejecutable confirma cero plugin, recursos, wrappers, listeners o selectores legacy en Listado. Falta repetir la comprobación visible final tras todos los cambios.
- Estados: loading oculta datos anteriores, aborta la petición previa y diferencia vacío, error y datos sin fabricar filas. Contrato runtime y pruebas de navegador pasan; falta evidencia visible consolidada.
- Filtros: cabecera nativa HOT interactiva, combinación por columna y limpieza con `clearConditions()` pasan en la suite dedicada. Se eliminó la cabecera externa obsoleta y el mapeo de acciones usa fila física.
- Actividad/modalidad mobile: RED mostró dos escrituras independientes; GREEN usa `/update-card` transaccional. Actividad, nombre seguro, fecha derivada y modalidad se guardan juntas; la tarjeta solo se cierra con respuesta `BIEN`.
- CSV: RED demostró que un archivo vacío o con cabeceras inválidas borraba datos; GREEN valida extensión, tamaño, cabeceras y filas antes de abrir la transacción. El backend importa un CSV válido sin pérdida previa.
- Contenido enriquecido: la API ya entrega `nombreActividadInicio` como texto plano seguro y consistente con HOT/tarjetas; el test de alcance rechaza etiquetas crudas.
- Permisos: `V` conserva lectura y el backend rechaza edición incluso ante concesión residual; la sesión real `test.V` no recibe Cargar Excel, Nueva Familia, Auto-generar ni acciones de fila. El editor real JMC usa rol `R`.
- Persistencia restaurable: guardado mobile, Nueva Familia y eliminación pasan con recarga técnica; el helper compara huella antes/después. La huella de tablas semi-auto aún requiere estabilización por volumen antes de marcar restauración completa.
- Geometría visible parcial: Desktop 1440×900 mostró siete encabezados/celdas con mismos `left` y anchos, cero overflow de página/contenedor y cero controles truncados. Tablet y las seis combinaciones siguen pendientes.
- Suite dedicada actual: 12 de 16 flujos pasaron en la primera corrida; paridad y readOnly ya fueron corregidos. Permanecen matriz visual, auto apply/undo y la reescritura del E2E completo.

### Evidencia visible Listado — navegador integrado

- Editor real: `jbenitez`, rol efectivo `R`, proyecto Optimización Aeropuerto JMC, semana 5, con 29 familias reales.
- Desktop 1440×900 Dark/Linen: encabezados y primera fila comparten exactamente posiciones y anchos; página y contenedor reportan overflow 0; ningún control del módulo queda truncado.
- Tablet horizontal 1024×768 Dark/Linen: siete encabezados y siete celdas permanecen alineados; overflow de página/contenedor 0; contenido enriquecido se muestra sin etiquetas crudas.
- Mobile 390×844 Dark/Linen: 29 tarjetas, tabla oculta, 0 overflow, 0 texto fuera de controles y 0 HTML crudo. Inicialmente no existen selects ni tarjetas editando.
- Edición mobile visible: Editar habilitó exactamente una tarjeta, un selector válido y las cuatro modalidades completas; las demás tarjetas permanecieron bloqueadas. Cancelar restituyó actividad `19.1` y fecha `2026-05-25` sin dejar controles activos.
- Filtros visibles: Modalidad `SI` redujo el listado a 14 filas válidas; combinado con Familia `Aire Acondicionado Central` dejó una fila. `Limpiar` ocultó el control activo y recuperó filas de varias familias sin condición residual.
- ReadOnly real: `test.V` autenticado en Da Porto mostró rol `V`, dos tarjetas de lectura y cero Cargar Excel, Nueva Familia, Auto-generar o Eliminar; la página mantuvo overflow 0.
- Consola/red final: pendiente de lectura final tras restaurar la base y dejar la vista consolidada abierta.

### Automatización Listado actualizada

- `tests/browser/listado-actividades-handsontable.mjs`: 17 flujos no semi-auto pasan juntos; cubren paridad, DataTables cero, filtros, estados, rollback inline, una sola tarjeta editable, readOnly real, matriz, CSV, alta y eliminación con huella restaurada.
- Semi-auto dedicado: fixture auditable de alta confianza, cambio visible a pestaña `Listas`, `preview -> selección -> apply -> undo`; el flujo pasa y restaura IDs iniciales.
- `e2e/tests/workflows/listado-full.spec.mjs`: reemplazado el E2E opcional/legacy por cuatro flujos obligatorios y restaurables; paridad, Nueva Familia, eliminación y `preview -> apply -> reload -> undo` pasan de forma independiente.
- Backend/runtime: contratos PHP de alcance, RBAC, CSV, modalidades, actividad-fecha y guardado mobile atómico pasan; contrato JS confirma una sola cabecera HOT, estado seguro, geometría y runtime DataTables cero.
- Persistencia/restauración final: falta ejecutar la corrida consolidada completa, restaurar el dump inicial y comparar la huella completa antes de cierre.

## Sprint exclusivo PDC iniciado 2026-07-11

- Contrato efectivo: `goal.md` con la restricción posterior del usuario a `/pdc` y `dt_definirContratos`.
- Fuera de alcance: BI, Listado, Contratos, PG, PI y PS; no habrá commits, push ni despliegue.
- Worktree inicial: `main` adelantada 40 y atrasada 3; existen cambios ajenos que se preservan.
- Respaldo previo: `/tmp/lps-aia-pdc-handsontable-before.sql` (87 MB).
- SHA-256 del respaldo: `a272e293affeea15bae795c95e913f2c262552a8b81a242aa50e941c4a2a3ecb`.
- Resultado de persistencia: pendiente de pruebas destructivas.
- Resultado de restauración: pendiente; se verificará contra el estado inicial.
- Navegador integrado: conexión nativa establecida; línea base visual pendiente.
- Viewports/temas: Mobile Dark/Linen, Tablet horizontal Dark/Linen y Desktop Dark/Linen pendientes.
- Automatización relacionada: `tests/browser/pdc-handsontable.mjs` y `e2e/tests/workflows/pdc-full.spec.mjs` en auditoría.
- Evidencia pendiente: todos los flujos funcionales, permisos reales, seis combinaciones visuales y matriz final consolidada.

### Correcciones PDC registradas durante el sprint

- Runtime DataTables: el loader ya excluye `mobile-table-fix.js`, `draw.dt` y selectores dinámicos `table.dataTable` en rutas Handsontable-only.
- CSS residual: se retiraron selectores DataTables específicos de estados PDC y reglas muertas del filtro HTML anterior.
- Tabla principal: la API vacía devuelve cero filas reales; no genera un registro sintético.
- Fechas: `diasEntregaPliegos` dejó de contarse dos veces en el estado calculado.
- Filtros: la clasificación prioriza información faltante y existe limpieza total de columna, leyenda y Solo Alertas.
- Filas filtradas: renderers y acciones convierten fila visual a física antes de abrir Editar/Eliminar.
- Modal principal: Guardar usa un único listener namespaced; Cancelar lo retira y la copia dinámica se renderiza como texto seguro.
- ReadOnly: el modal entra en modo consulta, deshabilita campos/Guardar y oculta acciones mutantes de toolbar.
- Desglosar: cliente envía también cantidades `1`; servidor valida enteros `>=1`, sincroniza altas/bajas en transacción y recarga HOT.
- Eliminación: servidor rechaza títulos/filas base y copia el subcontrato adicional a papelera dentro de una transacción.
- Comprobación actual: sintaxis JS/PHP y pruebas unitarias de runtime vacío/fechas pasan; navegador integrado y E2E destructivo siguen pendientes.
- Persistencia/restauración: no se marca completada hasta ejecutar guardado, recarga y restauración visible/automatizada.

### Reanudación verificable del sprint PDC — 2026-07-11

- El respaldo inicial fue creado antes de las pruebas destructivas y quedó registrado con SHA-256 `a272e293affeea15bae795c95e913f2c262552a8b81a242aa50e941c4a2a3ecb`; el archivo temporal desapareció durante la sesión.
- Se detuvieron las mutaciones y se creó `/private/tmp/lps-aia-pdc-handsontable-recovery.sql` (86 MB), SHA-256 `e694c4850a07233a10c866962c03e2404a30cd0954fce1aede16ee699a56699e`.
- Se preservó el snapshot PDC más antiguo disponible en `/private/tmp/lps-aia-pdc-scoped-baseline-earliest.sql`, SHA-256 `139021d11889c2c7c12a3be05947acf8327215bb2105a0b68a87c64b9353a619`; contiene los 66 registros PDC iniciales y las tablas semi-auto del proyecto 73.
- `dt_definirContratos`: se corrigió el ciclo de vida para mostrar el modal antes de medir/renderizar HOT. La prueba repetida abre, cancela, reabre, conserva una única instancia y pasa en 2.8 s.
- Automatización: se añadió una fuente auditable temporal, se ejecutó `preview -> selección -> apply -> recarga -> persistencia -> undo -> restauración`; la prueba focalizada pasa en 15.6 s.
- Eliminación: la petición UI enviaba `Content-Type: charset=utf-8`, por lo que PHP recibía `opcion` vacía. Se corrigió a formulario URL-encoded; el flujo rechaza la fila base, elimina un subcontrato adicional, lo conserva restaurable y recupera la huella inicial. La prueba focalizada pasa en 9.5 s.
- Cantidades: vacío, cero, negativo y decimal devuelven 422; un entero `>=1` se guarda, reaparece tras recargar y se restaura al valor inicial dentro del mismo E2E.
- Navegador integrado: estas correcciones aún requieren repetición visible; no se consideran evidencia final de interfaz.
- Matriz visual actual: las seis combinaciones siguen pendientes de repetición visible consolidada en este sprint.

## Evidencia funcional confirmada

- Listado: HOT desktop/tablet, tarjetas mobile, edición diferida, actividad-fecha, modalidades exclusivas, guardado, recarga y restauración.
- Contratos: HOT desktop/tablet, tarjetas mobile, catálogos del modal, paquetes, cantidades, duraciones, guardado, recarga y restauración.
- PDC: tabla HOT principal, filtros, estados, toolbar, modal de edición, eliminación condicionada y tabla HOT `dt_definirContratos`.
- PDC: Guardar/Cancelar enlazados al módulo; `Content-Type` corregido; cantidad persistida tras recarga y datos restaurados.
- Matriz visual: mobile 390x844, tablet horizontal 1024x768 y desktop 1440x900 en Dark y Linen para los tres módulos.
- Invariantes visuales observados: cero overflow de página, cero scroll-x y encabezados/registros alineados en las vistas recorridas.
- Pruebas: sintaxis JS/PHP, seguridad global, CRUD LACP, PDC moderno, fechas PDC y automatización moderna de Contratos pasan.

## Evidencia runtime 2026-07-11

- Listado, Contratos y PDC se recargaron en el navegador integrado desde `127.0.0.1`.
- Las tres rutas reportaron cero scripts `DataTables`, `global-table-align` o `datatable-height-manager`, cero `.dataTables_wrapper`, cero overflow horizontal y cero errores o advertencias de consola.
- PDC abrió el modal de edición, calculó el diagnóstico y lo cerró mediante Cancelar sin escritura.
- El loader común quedó corregido para excluir también `global-table-align.js` en rutas marcadas con `__AIA_HANDSONTABLE_ONLY__`.
- PDC recuperó el formateador de estados requerido por el modal sin restaurar inicializaciones DataTables.

## Correcciones encontradas durante la validación

- La regla mobile global de tablas quedó limitada a `.dataTables_wrapper`; ya no rompe Handsontable.
- PDC adapta columnas en mobile, usa tema Dark AIA y envuelve leyendas sin scroll horizontal.
- Contratos recuperó catálogos y guardado del modal; sus tarjetas mobile ya respetan Dark/Linen.
- PDC recuperó controles de filtro visibles y operables mediante el patrón global de cabeceras HOT con tokens AIA.
- PDC recuperó el botón de toolbar que abre el asistente de propuestas.
- El asistente compartido separa ahora el análisis actual de la última ejecución aplicada para que Deshacer pueda sobrevivir a una recarga.

## Cierre de este sprint

- El sprint se cierra por decisión del usuario sin declarar completo el goal general.
- El entorno local quedó restaurado y operativo: Docker activo, `db`, `app` y `adminer` en ejecución, 321 tablas y 66 registros PDC de Da Porto.
- No se hicieron commits, push ni despliegue.
- El fix de Deshacer después de recargar quedó implementado y con sintaxis válida, pero su repetición final visible se deriva al goal específico de PDC porque Docker se reinició durante la restauración.

## Evidencia todavía pendiente

- Sustituir el E2E obsoleto de PDC que todavía usa selectores y terminología DataTables, y añadir el contrato dedicado `pdc-handsontable.mjs`.
- Validar con sesiones reales un rol editor y uno readOnly en Listado, Contratos y PDC.
- Completar en PDC: filtros por columna sin condiciones residuales, eliminación restaurable, ciclo semi-auto `preview -> apply -> reload -> undo` y apertura repetida de `dt_definirContratos` sin instancias ni listeners duplicados.
- Completar la regresión acotada de PG, PI y PS con carga, filtros y una edición persistente representativa restaurada para cada módulo.
- Asociar los 57 hechos a evidencia concreta y registrar los resultados reproducibles antes de marcar las condiciones de terminado.

## Trabajo derivado a goals separados

- `cerrar-pdc-handsontable`: ejecutar el contrato nuevo, repetir `preview -> apply -> reload -> undo`, validar eliminación restaurable y ciclos repetidos de `dt_definirContratos`.
- `validar-permisos-handsontable`: completar sesiones reales editor/readOnly por módulo y documentar la política esperada cuando un rol no puede cargar una ruta.
- `regresion-handsontable-pg-pi-ps`: cubrir carga, filtros y edición persistente restaurable en PG, PI y PS sin modificar su diseño.

## Pendiente de aprobación

- Completar la evidencia todavía pendiente y hacer la revisión final visible en el navegador integrado.
- Revisión Plannotator, staging selectivo y commits locales atómicos después de la aprobación.
- Declaración explícita del usuario de que el goal está completo.

## Consolidado exclusivo Listado — 2026-07-11

> Registro histórico superado por las auditorías de 2026-07-12. Los conteos y la restauración vigentes son los del bloque más reciente.

- Alcance: únicamente `/listado-actividades`; no hubo commits, push, despliegue ni acceso a producción.
- Runtime: una sola instancia/cabecera Handsontable; cero recursos, plugins, wrappers, listeners, helpers o selectores DataTables en la ruta.
- Funcional: API/HOT/tarjetas, estados, filtros combinados/limpieza, edición inline con rollback, edición mobile atómica, actividad-fecha, modalidades, CSV, Nueva Familia, auto `preview/apply/reload/undo` y eliminación pasan con persistencia y restauración.
- Permisos reales: editor `jbenitez` rol `R`; readOnly `test.V` rol `V`, sin controles mutantes en DOM y con rechazo backend de edición.
- Matriz visible: Mobile 390x844, Tablet 1024x768 y Desktop 1440x900, cada uno Dark/Linen; cero overflow horizontal, encabezados alineados, cero texto fuera y cero HTML crudo.
- Suite browser histórica: `tests/browser/listado-actividades-handsontable.mjs`, 19/19; posteriormente ampliada y repetida.
- E2E consolidado: `e2e/tests/workflows/listado-full.spec.mjs`, 4/4 en 29.7 s; smoke de ruta, 1/1.
- Contratos: runtime 9/9; loader PASS; backend PHP PASS; alcance/proyecto OK; seguridad de tablas globales OK; PHPStan enfocado sin errores.
- Restauración histórica: JMC semana 5 conservó 29 filas; las 2 filas observadas en Da Porto eran fixtures efímeros y no pertenecían al baseline final.
- Limpieza final: 27 auditorías del sprint, 4 previews y 1.311 sugerencias no aplicadas, y el usuario/membresía temporal `test.V` fueron respaldados y retirados; fixtures 9995001/9995002 y residuos propios quedaron en cero.
- Respaldo previo a limpieza: `/private/tmp/lps-aia-listado-pre-cleanup-final.sql`, SHA-256 `88784fea0ad06c3363bf9acbc86d85de2cbd845753422d3a24e08fea54e892ce`.
- Respaldo del estado restaurado: `/private/tmp/lps-aia-listado-restored-current.sql`, SHA-256 `00ebc5957c26c6a9d3ff0081f102941d32451da0941912f3db537c0bcb8177e1`.
- Salvedad histórica: en ese punto no existía comparación global; una auditoría posterior sí creó y comparó dumps ordenados exactos.
- Evidencia final: Desktop Linen 1440x900 quedó visible en el navegador integrado con 29 familias, siete encabezados/celdas alineados, overflow 0, DataTables 0, HTML crudo 0 y consola sin errores ni advertencias; se detiene para aprobación del usuario.

## Consolidado exclusivo PDC — 2026-07-11

- Alcance: únicamente `/pdc`, incluida la tabla secundaria `dt_definirContratos`; no hubo commits, push, despliegue ni acceso a producción.
- Runtime: la tabla principal mantuvo una sola instancia Handsontable; `dt_definirContratos` mantuvo una sola instancia y un solo backdrop durante tres ciclos consecutivos de apertura/cierre. La ruta terminó con cero plugin, scripts, estilos, wrappers o helpers DataTables.
- Filtros visibles: el menú real de la columna Modalidad dejó únicamente `Suministro`; `Limpiar filtros` repuso `Mano de Obra`, `Suministro` y `Suministro e Instalación` seleccionados, sin condición residual. El filtro de alertas activó `is-active` y conservó el conteo de 63 alertas.
- Modal principal: abrió VIGILANCIA, cargó fechas y mostró `Proceso de contratación no iniciado`, etapa esperada y diagnóstico `Contratacion atrasada`. Cancelar descartó `NATIVE CANCEL PDC`; Guardar persistió la observación temporal después de recargar y la restauración final la retiró.
- Eliminación: la interfaz solo ofreció Eliminar para el subcontrato adicional; VIGILANCIA 23 desapareció después de confirmar y recargar, quedó una fila en `papelera_pdc` y fue recuperada por la restauración final. Las filas base permanecieron protegidas.
- Desglosar: cantidades vacías, cero, negativas y decimales quedan rechazadas por cliente/servidor; la prueba visible confirmó que `1.5` no llegó a persistir. El entero `2` reapareció después de recargar; editarlo a `3` y Cancelar conservó `2`. La restauración final devolvió `numeroSubcontratos=1`.
- Automatización visible: una fuente auditable `NATIVE PDC AUTOMATION` produjo preview de alta seguridad, apareció preseleccionada en `Listas`, se aplicó, sobrevivió a recarga, se deshizo y desapareció después de una nueva recarga.
- Permisos reales: `test.A` tuvo Actualizar, Editar, Eliminar y Analizar propuestas. `test.V`, autenticado y con rol Visualizador en Da Porto, tuvo cero Actualizar, Analizar o Eliminar; las acciones fueron `Ver actividad`, todos los campos del modal quedaron deshabilitados y Guardar cantidades quedó deshabilitado.
- Matriz visible nativa: Mobile 390×844 Dark/Linen, Tablet horizontal 1024×768 Dark/Linen y Desktop 1440×900 Dark/Linen. En las seis combinaciones: overflow de página 0, overflow del grid 0, encabezados/celdas alineados, ningún control truncado, una instancia principal, cero DataTables y cero HTML crudo.
- Evidencias visuales: `evidence/pdc-native/mobile-dark.png`, `mobile-linen.png`, `tablet-horizontal-dark.png`, `tablet-horizontal-linen.png`, `desktop-dark.png`, `desktop-linen.png` y `final-restored-editor-linen.png`.
- Suite browser: `npx playwright test tests/browser/pdc-handsontable.mjs --workers=1`, 7/7 en 26.5 s.
- E2E completo: `npx playwright test --config=e2e/playwright.config.mjs e2e/tests/workflows/pdc-full.spec.mjs --workers=1`, 4/4 en 38.2 s.
- Contratos adicionales: `tests/test_pdc_modern_replaces_legacy_update.php` y `tests/test_pdc_projected_dates_reflow.php` pasan sin fallos; sintaxis JS/PHP de los archivos modificados válida.
- Restauración quirúrgica: se usó `/private/tmp/lps-aia-pdc-native-before.sql`, SHA-256 `4373ee7aa2e8f8bcbcac9a4cf58ef8257baad96d2c9c0b7911f302edd6a8017a`. Se restauraron solo PDC y registros semi-auto del módulo `pdc` para proyecto 73, sin sobrescribir datos concurrentes de otros módulos.
- Comprobación de restauración: PDC quedó en 66/66 filas con huella CRC `143315374375`; papelera 0/0; fixture nativo 0; VIGILANCIA 23 recuperada con cantidad 1 y observación vacía; runs PDC 19/19, sugerencias 281/281, decisiones 45/45 y cola 1/1 contra el snapshot.
- Corrección de aislamiento: una edición accidental sobre proyecto 68 durante un cambio concurrente de sesión se comparó contra el respaldo de recuperación y se devolvió exactamente a observación vacía antes del cierre.
- Consola/red: el navegador integrado terminó sin errores ni advertencias; los logs locales no contienen respuestas HTTP 4xx/5xx con referer `/pdc` de la sesión nativa.
- Evidencia final visible: sesión editor `test.A`, Da Porto, Desktop Linen 1280×720 tras restablecer el viewport; una instancia principal, `dt_definirContratos` cerrado, DataTables 0, overflow 0, encabezados alineados, controles dentro de límites, HTML crudo 0 y consola limpia.
- Estado del goal: el sprint PDC queda listo para revisión, pero no se declara completo; se detiene hasta la aprobación explícita del usuario.

### Auditoría posterior al consolidado PDC — 2026-07-11

- La repetición sobre el worktree actual detectó 4/7: una regla compartida eliminaba CSS legítimo de `#dt_cliente`, tres chips se truncaban en tablet y el backend readOnly volvió a aceptar `guardar_DefinirContratos`.
- Causa CSS: el depurador Handsontable usaba `[.#]dt[_-]` y confundía el ID PDC con selectores DataTables. Se reemplazó por marcadores concretos de DataTables y se aisló la leyenda con `body.pdc-page`.
- Causa RBAC: una edición concurrente conservó las denegaciones de Listado/Contratos pero retiró las de PDC. `V` vuelve a negar explícitamente `lps.pdc.editar` y `lps.pdc.auto_generar`.
- Prueba readOnly: crea `test.V` y su membresía real solo si no existen, inicia sesión normalmente, verifica UI y 403 backend, y elimina el fixture al terminar. Resultado final del usuario temporal: 0.
- GREEN focalizado: leyenda/alertas, matriz Dark/Linen y editor/readOnly, 3/3 en 8.8 s; repetición aislada readOnly 1/1 en 4.1 s con fixture final 0.
- Suite browser final actual: `tests/browser/pdc-handsontable.mjs`, 7/7 en 20.4 s.
- E2E final actual: `e2e/tests/workflows/pdc-full.spec.mjs`, 4/4 en 36.6 s.
- Contratos PHP repetidos: endpoint moderno/DataTables 0 fallos; reproyección de fechas 0 fallos; sintaxis JS/PHP válida.
- Matriz nativa repetida tras el fix: Mobile 390×844, Tablet 1024×768 y Desktop 1440×900, Dark/Linen; las seis combinaciones tienen una instancia, alineación correcta, overflow 0, controles sin truncar, DataTables 0 y HTML crudo 0.
- Restauración final repetida: 66 filas, CRC `143315374375`, papelera 0, fixtures 0, `test.V` 0 y VIGILANCIA 23 con cantidad 1/observación vacía.
- Pestaña final limpia: editor `test.A`, Da Porto, Linen 1280×720, consola sin errores/advertencias y sin HTTP 4xx/5xx con referer `/pdc`.
- Evidencia final actualizada: `evidence/pdc-native/final-audit-restored-editor-linen.png`.
- El goal continúa activo: falta exclusivamente la declaración explícita de aprobación del usuario.

### Auditoría de cierre estricta de Listado — 2026-07-12

- La auditoría requisito-evidencia reabrió el sprint: la prueba de HTML hostil, la red final y algunos contratos directos de mobile/toolbar eran insuficientes; no se mantuvo la declaración de cierre con evidencia débil.
- Runtime: se retiró una segunda carga de jQuery UI y se eliminaron todos los IDs duplicados de la vista; el contrato loader falla ante cualquier nueva duplicación.
- Seguridad de contenido: se añadió un fixture adversarial con `b`, `small`, `br`, `img` y `onerror`; HOT y tarjetas convierten campos libres a texto, retiran nodos/atributos inseguros y no ejecutan el payload. Prueba focalizada: 1/1.
- Backend: un código alfanumérico provocaba truncamiento SQL y HTTP 500; ahora se valida como entero positivo y devuelve 422. Contrato backend completo: PASS.
- Desktop: una prueba real edita código y descripción inline, recibe 200, recarga, comprueba persistencia y restaura la huella del snapshot. Resultado focalizado: 1/1.
- Mobile: antes de Editar no existen selectores/checks interactivos; en edición aparecen las cuatro etiquetas completas, opciones de actividad en texto seguro y `SI` desmarca MO/S/OC. Resultado focalizado: PASS.
- Toolbar: en mobile se comprueban iconos, textos contenidos, selector de módulo operable, tres opciones y `Familias de obra` activa. Resultado focalizado: PASS.
- Estados: error 503 exige explícitamente cero filas HOT y cero tarjetas, además del mensaje de error.
- Red: la recarga normal de la ruta registra consola, `pageerror`, `requestfailed` y HTTP >=400; resultado focalizado 1/1, sin fallos.
- ReadOnly: usa un usuario único por proceso, membresía real V y limpieza garantizada; prueba 1/1 y residuos `test.V.listado.*` = 0.
- Restauración tras la edición focalizada: JMC semana 5 = 29, códigos temporales = 0 y descripciones `E2E` = 0.
- Pendiente de esta auditoría: repetir la suite browser completa ampliada y renovar la evidencia final del navegador integrado cuando terminen las suites concurrentes ajenas.

### Auditoría final exclusiva de Listado — 2026-07-12

- Se corrigió la purga CSS del loader compartido: espera la carga real, conserva selectores mixtos y deja cero reglas, assets, wrappers, plugins o globals DataTables en `/listado-actividades`.
- Se corrigieron los guardados idempotentes inline/mobile: una actualización sin cambio ya no se interpreta como fila inexistente ni devuelve 409 falso.
- El endpoint legacy ahora deriva fecha desde la actividad válida, normaliza modalidades y hace `SI` exclusivo; se restauró además la protección compartida `.ps-action-btn` para rol C.
- La paridad browser compara todos los registros y valores entre API, HOT y tarjetas; la prueba readOnly crea una membresía real temporal, inicia sesión normalmente y la elimina al terminar.
- Navegador integrado: edición inline con rollback; mobile Guardar/Cancelar y una sola tarjeta; actividad 12892/fecha `2026-04-24`/SI persistió y luego se restauró a 12933/`2026-05-25`/MO,S.
- Nueva Familia validó campos, guardó, recargó, eliminó con confirmación y restauró; Excel abrió/canceló en navegador y cubrió importación válida/inválida/restauración en la suite técnica.
- Auto-generar recorrió `preview -> selección -> apply -> reload -> undo -> reload`; la familia aplicada persistió y desapareció tras deshacer.
- Suite browser final: `tests/browser/listado-actividades-handsontable.mjs`, 20/20 en 1.1 min; E2E 4/4 en 29.2 s; smoke de ruta 1/1 en 4.7 s.
- Contratos adicionales: runtime 9/9, loader PASS, backend PASS, alcance/proyecto OK, seguridad global OK y PHPStan enfocado sin errores.
- Matriz nativa: Mobile 390×844, Tablet horizontal 1024×768 y Desktop 1440×900, Dark/Linen; overflow 0, alineación correcta, toolbar contenida y HTML crudo 0.
- Restauración exacta comprobada antes de la entrega: 161.408 filas en ambos snapshots, SHA-256 común `1d80bbe55940ac2977be588b08d53098ec5074b1af89f18381a229f982c851db` y diferencia 0.
- Estado restaurado: JMC semana 5 = 29; Da Porto semana 1 = 0; fixtures 9995001/9995002/9995003, familias temporales y `test.V` = 0; auditoría máxima = 12994.
- Vista final: Desktop Dark 1440×900, 29 familias, siete encabezados/celdas con geometría idéntica, overflow 0, DataTables 0 y consola sin errores ni advertencias.
- Un proceso Playwright ajeno (`auto-definir-contratos.mjs`) comenzó después del checkpoint y siguió escribiendo auditoría de Da Porto; se preservó sin detenerlo ni borrar sus nuevas filas.
- No hubo commits, push, despliegue ni producción. El sprint queda listo para revisión, pero el goal continúa activo hasta declaración explícita del usuario.

### Corrección solicitada en navegador y repetición final — 2026-07-12

- Problema visible: en una tarjeta mobile en edición, seleccionar `Suministro e Instalación` desmarcaba las otras modalidades, pero todavía permitía volver a activarlas.
- Corrección: mientras `SI` esté seleccionado, MO, S y OC quedan desmarcados, deshabilitados y con el estado visual AIA de control bloqueado; al retirar `SI`, los tres vuelven a habilitarse. El cambio es exclusivo del editor mobile de Listado.
- Comprobación en navegador integrado: viewport 534×750, tarjeta `Implementacion PMT`, modo edición. El árbol accesible mostró `Suministro e Instalación` checked/active y MO, S y OC disabled. La tarjeta quedó sin guardar, por lo que esta comprobación visible no escribió datos.
- Persistencia y restauración: la suite real volvió a probar guardado/reload y sus snapshots restaurables. Estado final de JMC: 29 familias; descripciones/códigos E2E, usuario readOnly temporal y fuentes 9995001 = 0.
- Viewports y temas: la matriz automatizada volvió a pasar Mobile Dark, Mobile Linen, Tablet horizontal Dark, Tablet horizontal Linen, Desktop Dark y Desktop Linen; la evidencia visible específica de este fix se hizo en mobile 534×750.
- Pruebas relacionadas: `tests/browser/listado-actividades-handsontable.mjs` 24/24 en 1.5 min; `e2e/tests/workflows/listado-full.spec.mjs` 4/4 en 1.1 min; runtime 9/9; loader PASS; backend PASS; alcance/proyecto OK; seguridad global OK; sanitizador PASS; sintaxis JS válida; PHPStan enfocado sin errores.
- Consola/red: la suite ampliada exige cero `console.error`, `pageerror`, `requestfailed` y respuestas HTTP >=400 durante la carga normal.
- Evidencia pendiente: aprobación visual del usuario sobre la tarjeta que permanece abierta con `SI` seleccionado y las modalidades incompatibles bloqueadas.
- No hubo commits, push, despliegue ni producción. El sprint y el goal permanecen activos hasta la declaración explícita del usuario.

### Aprobación final del sprint Listado — 2026-07-12

- El usuario declaró explícitamente `Aprobado` después de revisar el resultado consolidado en el navegador integrado.
- Se acepta la corrección final de exclusividad mobile: con `SI` seleccionado, MO, S y OC permanecen desmarcados y bloqueados.
- El sprint exclusivo de `/listado-actividades` y su goal quedan formalmente completados con la evidencia, restauración y pruebas registradas en las secciones anteriores.
- La aprobación no creó commits, push ni despliegues; esas acciones quedan fuera de este cierre hasta una solicitud posterior.

### Paquetes progresivos solicitados desde el navegador — 2026-07-12

- Problema: el modal mostraba de entrada los cinco espacios de cada modalidad, consumiendo demasiado viewport mobile aunque el usuario solo necesitara un paquete.
- Corrección: cada modalidad muestra inicialmente el Paquete 1 y un botón AIA `+ Agregar paquete 2`; cada pulsación revela exactamente el siguiente espacio y el botón desaparece al llegar al Paquete 5. Los paquetes ya persistidos siguen visibles y no se reordenan ni eliminan.
- Cancelación: se revelaron manualmente los paquetes 2–5, se pulsó Cancelar, se esperó el cierre completo y se reabrió `Implementacion PMT`; volvió a mostrarse solo el Paquete 1 con `Agregar paquete 2`, sin escritura ni estado temporal residual.
- Comprobación en navegador integrado: mobile 534×750, registro `Implementacion PMT`, modalidad Suministro. Secuencia visible 1 → 2 → 3 → 4 → 5, límite efectivo de cinco, botón oculto al alcanzar el límite, overflow de página 0 y overflow del modal 0. Consola final: cero errores o advertencias.
- Evidencia visible: `/tmp/contratos-paquetes-progresivos-final.png`; el navegador queda abierto en el estado restaurado, con Paquete 1 y el botón `+ Agregar paquete 2`.
- TDD: la prueba nueva falló inicialmente porque encontraba cinco espacios visibles y pasó después de la corrección. También se adaptó la prueba Select2 para solicitar primero el segundo paquete.
- Pruebas relacionadas: `tests/browser/contratos-handsontable.mjs` 15/15; `tests/browser/auto-definir-contratos.mjs` 5/5; `tests/browser/contratos-slot-quantities.mjs` 5/5 y 1 omisión esperada; `e2e/tests/workflows/contratos-full.spec.mjs` 1/1. Sintaxis JS/PHP y `git diff --check` válidos.
- Persistencia/restauración final: fixtures `test_v`, actividades proyecto 73, semillas, trazas y duraciones E2E = 0; 20 columnas de cantidades conservan tipo entero. Respaldo `/tmp/lps-aia-contratos-handsontable-before.sql` sin cambios, SHA-256 `a83a4187a6b611a796d97f9f6366062ae409ae727b74376d01cc742689ab816f`.
- No hubo commits, push, despliegue ni producción. El sprint y el goal continúan activos hasta la aprobación explícita del usuario.

### Botón de edición visible en tarjetas mobile — 2026-07-12

- Problema señalado en navegador: la tarjeta tenía un botón de edición activo en la esquina inferior derecha, pero su contenido visible estaba vacío; solo existían el `title` y un icono cuya representación no era reconocible en Dark.
- Corrección: la acción muestra icono y texto explícito `Editar paquetes`, conserva los tokens AIA y recibe un nombre accesible contextual, por ejemplo `Editar paquetes de Implementacion PMT`.
- TDD: la aserción sobre texto visible falló primero con valor recibido vacío; después de la corrección pasó la prueba focalizada 1/1.
- Navegador integrado: mobile 534×750 Dark, primera tarjeta `Implementacion PMT`. Botón visible de 131.8×32 px, texto blanco, fondo AIA, overflow interno 0 y overflow de página 0. El árbol accesible contiene icono, texto y nombre contextual. Consola sin errores ni advertencias.
- Evidencia visible: `/tmp/contratos-mobile-edit-button-dark.png`.
- Suite relacionada final: `tests/browser/contratos-handsontable.mjs`, 15/15 en 48.7 s, incluida restauración automática de las mutaciones de prueba.
- No hubo commits, push, despliegue ni producción. El sprint y el goal permanecen activos hasta la aprobación explícita del usuario.

## Reauditoría del worktree actual — 2026-07-13

### Baseline y evidencia invalidada

- Problema: `tests/browser/contratos-handsontable.mjs`, el respaldo SQL y las capturas citadas por las secciones previas ya no existían; el E2E seguía usando `#dt_cliente` y describiendo DataTables. El source volvió a mostrar cinco paquetes y un botón mobile sin texto.
- Corrección documental: todo resultado previo de Contratos se considera histórico/no vigente. Se reconstruyó la suite principal desde cero y se registrarán únicamente ejecuciones y evidencia posteriores a esta reauditoría.
- Persistencia/restauración: no se ejecutaron pruebas destructivas en este bloque. Un respaldo nuevo sigue pendiente antes de guardar, aplicar, deshacer o modificar duraciones.
- Evidencia pendiente: las cuatro suites completas, respaldo verificado, persistencia/restauración, automatización y seis combinaciones nativas.

### Paquetes progresivos y acción mobile

- Problema: cada modalidad renderizaba cinco filas visibles y las tarjetas mostraban un botón icon-only sin texto.
- Corrección: cada modalidad inicia con Paquete 1, expone `+ Agregar paquete` y revela un slot por acción hasta cinco; los datos persistidos determinan cuántos slots deben reabrirse. La acción mobile muestra `Editar paquetes`, nombre accesible y solo se renderiza con capacidad de edición emitida por el servidor.
- Comprobación navegador integrado: Mobile Linen 534×750, sesión real `test.A`, Da Porto semana 5. La tarjeta mostró el botón completo; el modal abrió `Estructura en Concreto` con un slot por modalidad activa, reveló Paquete 2 y mantuvo `scrollWidth=clientWidth=534`. Se cerró con X sin pulsar Guardar.
- Persistencia/restauración: no hubo petición de escritura ni cambio de datos; la prueba fue exclusivamente de presentación y estado temporal.
- Viewports/temas: Mobile Linen confirmado. Mobile Dark, Tablet horizontal Dark/Linen y Desktop Dark/Linen pendientes.
- Prueba relacionada: `tests/browser/contratos-handsontable.mjs`; RED por texto vacío y 5 filas visibles, GREEN 2/2 focalizadas y luego suite actual 4/4.
- Evidencia pendiente: artefacto durable de navegador nativo y repetición en las otras cinco combinaciones.

### Runtime DataTables y fuente Handsontable

- Problema: `/contratos` cargaba `vendor-datatables-legacy.css`, cuatro imports CSS, `datatable-height-manager.js`, `global-table-align.js` y `mobile-table-fix.js`; el helper compartido podía instalar listener sobre `#dt_cliente` para rol C.
- Corrección: la vista activa `__AIA_HANDSONTABLE_ONLY__` antes del loader, solicita el design system sin CSS DataTables y evita el guard legacy compartido en esta ruta. Handsontable conserva una sola instancia/master.
- Comprobación técnica: la prueba de recursos falló primero con ocho URLs DataTables y pasó después con cero recursos, plugin, wrappers o `#dt_cliente`, un `.ht_master` y una instancia accesible.
- Comprobación navegador integrado: pendiente repetir tras la última recarga y revisar red/DOM/consola nativos.
- Persistencia/restauración: no aplica; no hubo escritura.
- Viewports/temas: prueba técnica mobile; matriz nativa pendiente.
- Prueba relacionada: `tests/browser/contratos-handsontable.mjs`, caso `carga una sola instancia Handsontable y ningun runtime DataTables` GREEN.

### Estados y registros sintéticos

- Problema: carga, vacío y error convergían en una lista vacía sin estado explícito; la API fabricaba una fila vacía cuando no encontraba registros.
- Corrección: estado `loading|empty|error|data` con mensaje dedicado; la API devuelve `data: []` real y no crea `$emptyRow`.
- Comprobación técnica: el test browser simula respuesta demorada, vacía y 500, exige los cuatro estados y cero filas fuente; el contrato PHP impide reintroducir la fila sintética. Ambos GREEN.
- Comprobación navegador integrado: datos reales observados (20 tarjetas en Da Porto semana 5); vacío/error nativos pendientes.
- Persistencia/restauración: no aplica; llamadas de lectura/interceptadas.
- Viewports/temas: datos en Mobile Linen; otros estados y matriz pendientes.
- Pruebas relacionadas: `tests/browser/contratos-handsontable.mjs` y `tests/test_contratos_list_empty_no_synthetic.php`.

### Permisos editor/readOnly

- Problema: el fallback RBAC de `V` contenía `lps.contratos.editar` y `lps.contratos.auto_definir`; la UI usaba comparaciones de rol y no la capacidad efectiva.
- Corrección: `V` conserva `ver` y niega explícitamente las dos escrituras incluso ante drift de base; la ruta exige `ver` y emite capacidades reales de editar/auto-definir para toolbar, HOT y tarjetas.
- Comprobación técnica: el contrato RBAC falló en cuatro aserciones y quedó GREEN 5/5. El browser crea una membresía `V` real temporal, inicia sesión normalmente, verifica cero acciones y obtiene 403 en guardar/preview; el fixture se elimina en `finally`.
- Comprobación navegador integrado: sesión editor `test.A` confirmada. Sesión `V` nativa aún pendiente.
- Persistencia/restauración: el usuario/membresía temporal del test se eliminó; falta incorporar la verificación a un snapshot/respaldo consolidado.
- Viewports/temas: editor Mobile Linen; readOnly y matriz pendientes.
- Pruebas relacionadas: `tests/test_contratos_rbac_contract.php` y `tests/browser/contratos-handsontable.mjs`, caso de sesiones reales GREEN.

### Modal, catálogo y persistencia manual

- Problema: la apertura del modal enviaba `contentType: 'charset=utf-8'`; PHP no interpretaba `opcion=actualizarListadoPaquetesContratacion`, respondía 400 `Opción no válida` y el catálogo/los valores persistidos reaparecían vacíos. También existía riesgo de que una respuesta tardía del registro anterior mezclara datos al abrir otro.
- Corrección: se retiró el `contentType` inválido para usar el formulario URL-encoded de jQuery y se conserva un token de secuencia que descarta respuestas de aperturas ya cerradas o reemplazadas.
- Comprobación técnica: el flujo real guarda un paquete MO único, cantidad 3, dos recursos y las siete duraciones 2–8; comprueba la fila SQL, recarga, abre el mismo registro y verifica paquete/cantidad/recursos en el modal y las siete duraciones en el catálogo. RED con HTTP 400 antes del fix; GREEN 1/1 después.
- Persistencia/restauración: `ProjectDbSnapshot` captura `actividades` y `contratos_trazabilidad`; el catálogo global y la auditoría temporal se limpian en `finally`; la huella final coincide con la inicial y el paquete temporal queda en cero filas.
- Viewports/temas: verificación técnica desktop para edición y mobile 390×844 después de recargar. Evidencia nativa equivalente aún pendiente.
- Prueba relacionada: `tests/browser/contratos-slot-quantities.mjs`, caso `persiste paquete, cantidad, recursos y siete duraciones; luego restaura el estado inicial` GREEN.

### Auto-definir apply/undo

- Problema: `contractProposedFields()` proponía `NULL` para cantidades de slots vacíos y `applyUpdateContracts()` intentaba escribirlo en columnas `cantidad* NOT NULL`; `apply` devolvía una decisión con error SQL y cero aplicaciones.
- Corrección: todos los slots de cantidad propuestos parten en 1 y `apply` normaliza a 1 las sugerencias antiguas que todavía traigan cantidad vacía; los paquetes activos conservan su `cantidadDefault` real.
- Comprobación técnica: `tests/test_contratos_activity_sources_multi_group.php` pasó de 2 fallos por `cantidadSI1 cannot be null` a 7/7 aserciones verdes, incluida aplicación sin errores y ausencia de actividades duplicadas.
- Persistencia/restauración: la prueba browser completa `preview → revisión/edición → selección → apply → recarga → persistencia → undo → recarga → restauración` se ejecutó exclusivamente en serie para evitar solapamiento entre snapshots del mismo proyecto. Aplicó 1/0 errores, revirtió 1/0 errores y terminó con huella idéntica.
- Viewports/temas: automatización técnica; comprobación nativa pendiente.
- Pruebas relacionadas: `tests/browser/auto-definir-contratos.mjs` 3/3 en 32.5 s y `tests/test_contratos_activity_sources_multi_group.php` 7/7 aserciones.

### E2E Handsontable de Contratos

- Problema: el workflow obligatorio todavía describía y seleccionaba `#dt_cliente` como DataTables y permitía omitir acciones centrales.
- Corrección: el E2E exige una fuente HOT, cero runtime DataTables, paridad API/HOT/mobile, filtros combinados y limpieza, registro correcto, slots 1→5, exclusividad SI, cancelar con cero POST y reapertura de otro registro sin mezcla. Los selectores canónicos de Contratos apuntan a HOT, tarjetas y modal actuales.
- Persistencia/restauración: cada caso captura/restaura `actividades` y `contratos_trazabilidad` y compara la huella.
- Comprobación técnica histórica: los 3 casos del E2E se repitieron dos veces, 6 ejecuciones verdes en 37.1 s; sintaxis y `git diff --check` verdes. El archivo actual conserva 3 casos obligatorios.
- Comprobación navegador integrado, viewports y temas: pendiente repetir la matriz final nativa sobre el código ya corregido.

## Revalidación exclusiva PDC posterior a la aprobación — 2026-07-13

- Deriva detectada: la comprobación fresca posterior a la aprobación encontró que `/pdc` había vuelto a cargar e inicializar DataTables, la suite browser fallaba 7/7 y el E2E conservaba selectores legacy. La evidencia anterior quedó suspendida hasta recuperar y volver a ejecutar cada gate contra el worktree servido.
- Runtime corregido: `views/pdc/pdc.view.php` vuelve a marcar la ruta como Handsontable-only y ya no carga scripts, estilos, plugins, wrappers, listeners ni helpers DataTables. La tabla principal y `dt_definirContratos` mantienen exactamente un `.ht_master` cada una.
- Filtros: el disparador de cabecera PDC quedó por encima del texto y operable; el flujo visible abre el menú, selecciona un valor sobre la fuente completa y `Limpiar filtros` recupera todas las filas sin condiciones residuales.
- Encabezados mobile: una regla global heredada posicionaba `thead tr` fuera de pantalla y rompía los anchos. PDC neutraliza esa regla solo en su grid y sincroniza el ancho de cada encabezado con su columna; Mobile Dark/Linen vuelve a estar alineado sin scroll-x.
- `dt_definirContratos`: se retiró el `onclick` inline que coexistía con el listener namespaced. Tres ciclos de abrir/cerrar reutilizan la misma instancia; Guardar produce una sola petición y Cancelar vuelve a leer el valor persistido.
- Automatización: el componente compartido sustituía el `run_id` aplicado por el de un preview nuevo, por lo que Deshacer revertía cero cambios. Ahora conserva de forma acotada la última corrida aplicada por módulo/proyecto/semana incluso tras recargar, la limpia después de undo y deja la base en la huella previa.
- Permisos: `RbacService` niega explícitamente `lps.pdc.editar` y `lps.pdc.auto_generar` a `V`. La suite inicia sesiones autenticadas reales de editor y readOnly, verifica controles/celdas y exige 403 backend sin alterar variables del navegador.
- Modal principal y fechas: edición abre con estado, etapa y diagnóstico calculados; Cancelar no persiste; Guardar persiste tras recarga y el snapshot devuelve la observación original. Los contratos PHP de endpoint moderno y reproyección pasan con 0 fallos.
- Eliminación/restauración: la fila base devuelve 422; un subcontrato adicional se elimina mediante la interfaz, desaparece después de recargar y el snapshot restaura PDC/papelera. El flujo de cantidades rechaza vacío, cero, negativo y decimal; un entero `>=1` persiste tras recargar y luego se restaura.
- Suites actuales: `tests/browser/pdc-handsontable.mjs` pasa 7/7 en 19.9 s; `e2e/tests/workflows/pdc-full.spec.mjs` pasa 4/4 en 45.6 s; sintaxis JS/PHP y `git diff --check` enfocado están limpios.
- Matriz nativa actual: navegador integrado sobre Da Porto semana 1 recorrió Mobile 390×844, Tablet horizontal 1024×768 y Desktop 1440×900 en Dark/Linen. Las seis combinaciones reportaron `pageOverflow=0`, `hotOverflow=0`, una instancia maestra y encabezados/celdas alineados; cero runtime DataTables.
- Consola/red: el navegador nativo detectó que `cargarDatosGeneralesPagina2.js` asumía siempre `data.listadoSemanas`; se añadió un guard para respuestas sin `data`. Tras recarga en pestaña nueva: consola 0 warnings/errores y 82 eventos de red con 0 respuestas HTTP fallidas o `Network.loadingFailed`.
- Datos: las pruebas destructivas capturaron snapshot antes de mutar y compararon la huella después de restaurar. Auditoría posterior: PDC proyecto 73 conserva 67 filas y CRC focal `113367660548`, papelera 0 y fixture de automatización `990001` en 0. La observación `NATIVE PDC 1783824981528` ya pertenecía al baseline vigente documentado por Sprint 05 y se preservó; no se revirtió trabajo concurrente.
- Fixture readOnly concurrente: existe `test.V`/membresía `V` porque otra suite de Contratos sigue ejecutándose en el mismo runtime. PDC no lo creó en esta corrida y no lo elimina para no interferir con trabajo ajeno; su propia prueba reutilizó esa sesión real y restauró exclusivamente las tablas PDC capturadas.
- Evidencia visible actual: la pestaña entregable de Desktop Linen 1440×900 quedó abierta en el navegador integrado, con el modal `Definir cantidades` visible, DataTables 0, overflow 0, alineación correcta, controles contenidos, HTML crudo 0, consola limpia y red limpia. No se presenta una captura headless ni una ruta de evidencia inexistente.
- Publicación: no se hicieron commits, push, despliegue ni cambios en producción. La aprobación del usuario queda registrada; el goal continúa activo hasta que el usuario declare explícitamente que está completo.

## Cierre de riesgos PDC posterior a la aprobación — 2026-07-14

- Permisos reales: la auditoría posterior detectó que el catálogo fallback todavía entregaba `lps.pdc.editar` y `lps.pdc.auto_generar` al rol `V`, aunque el servicio los negara después. Se retiraron ambos permisos del mapa raíz. La prueba crea una cuenta temporal única con membresía real `V`, conserva consulta, no muestra acciones de escritura, deja `dt_definirContratos` readOnly y recibe 403 backend con un token CSRF válido; `test.A` conserva edición. La membresía y la cuenta temporal se eliminan en `finally`, sin depender ni alterar un `test.V` preexistente.
- Protección de mutaciones: `/api/pdc/save`, `/api/pdc/update-cell`, `apply-from-contratos` y las operaciones semi-auto persistentes de PDC ahora usan un token de sesión `pdc_save`. La vista lo envía por `X-CSRF-Token`; una solicitud de editor sin token devuelve 403 y los flujos visibles continúan guardando normalmente.
- Actualización por columna: el helper heredado aceptaba cualquier identificador alfanumérico y construía `UPDATE pdc SET $columna`. La ruta dinámica quedó retirada con respuesta 410; la actualización moderna conserva su lista explícita de propiedades editables y no permite alterar `project_id`, `semana`, `consecutivo`, `titulo` ni otros campos estructurales.
- Eliminación y restauración funcional: toda eliminación adicional y toda reducción de cantidad archivan primero la fila completa en `papelera_pdc`. Se añadió `restaurar_actividad_pdc`, transaccional, con detección de colisión, actualización de la cantidad base y borrado de papelera únicamente después de restaurar con éxito.
- Cierre de permisos y secuencia: crear o eliminar semanas exige además `lps.semana.crear` o `lps.semana.eliminar`, respectivamente. La restauración cuenta las posiciones anteriores del mismo paquete y responde 422 si produciría un hueco entre subcontratos.
- Comprobación destructiva: el E2E creó respaldo de `actividades`, `pdc`, `papelera_pdc` y nueve tablas semi-auto, eliminó un subcontrato adicional, recargó, confirmó ausencia y una fila en papelera, restauró por API, recargó y confirmó presencia y papelera vacía. La huella conjunta de las 12 tablas volvió al valor inicial.
- Corrección mobile de `dt_definirContratos`: la matriz ampliada encontró que una regla global `thead tr { top/left: -9999px }` ocultaba sus encabezados en mobile. El override acotado a PDC restaura semántica de tabla y sincroniza anchos; Mobile Dark y Linen ahora tienen encabezados visibles/alineados, una sola instancia y overflow 0.
- Selectores obsoletos: el shell PDC dejó de llamarse `dt_cliente_wrapper` y ahora es `pdc-hot-shell`; la prueba de design system clasifica `/pdc` como Handsontable y exige cero runtime DataTables; las acciones compartidas usan `aria-label` actuales (`Editar actividad`, `Ver actividad`, `Eliminar`).
- Pruebas técnicas finales: `tests/test_pdc_security_and_restore_contract.php` 17/17 y cubre todas las mutaciones semi-automáticas PDC; contratos PHP de endpoint moderno y fechas proyectadas 0 fallos; `tests/browser/pdc-handsontable.mjs` 7/7 en 21.4 s; `e2e/tests/workflows/pdc-full.spec.mjs` 4/4 en 35.4 s tras el cierre de permisos/restauración. La prueba browser ahora falla ante `console.warn`, HTTP 4xx inesperados y fallos reales de transporte; solo consume los dos 403 deliberados del contrato RBAC. `git diff --check` enfocado y sintaxis JS/PHP limpios.
- Navegador integrado final: Mobile 390×844, Tablet horizontal 1024×768 y Desktop 1440×900, cada uno en Dark/Linen, midieron para ambos grids `pageOverflow=0`, `hotOverflow=0`, una instancia maestra, encabezados/celdas alineados, controles sin texto desbordado, HTML crudo 0 y runtime DataTables 0. El monitor CDP integrado registró 172 respuestas, 0 HTTP >=400 y 0 `Network.loadingFailed`; la consola terminó con 0 errores o warnings.
- Persistencia y estado final: tras todas las pruebas, PDC proyecto 73 conserva 67 filas, `papelera_pdc` 0 y el fixture `actividades.Id=990001` 0. El reinicio del contenedor MySQL fue necesario por un error de E/S de Docker Desktop; se preservó el volumen y se verificaron estos conteos antes de reanudar las pruebas.
- Integración de pruebas: `tests/browser/pdc-handsontable.mjs` tiene ahora una excepción explícita en `.gitignore`, por lo que ya no puede quedar fuera inadvertidamente de la integración selectiva. El log del goal continúa ignorado globalmente y deberá añadirse de forma explícita al crear los commits aprobados.
- Auditoría previa a integración: se añadió CSRF a `assistantAckPdc`, `assistantFeedbackPdc`, `learningApprovePdc` y `learningRejectPdc`; la persistencia browser del identificador de undo quedó limitada a PDC para no cambiar Listado ni Contratos. El reemplazo de respaldos en `papelera_pdc` quedó aislado también por semana. El índice exacto de 22 archivos fue revisado en un worktree temporal contra `main`; Plannotator cerró con «no changes requested».
- Integración local aprobada: rama `codex/pdc-handsontable`; commit funcional `d4110a3` (`✨ feat(pdc): complete Handsontable migration`). El staging fue selectivo y no incluyó cambios ajenos del worktree.
- Evidencia pendiente: no queda evidencia funcional o visual pendiente para el sprint exclusivo PDC. No hubo push, despliegue ni cambios en producción. El goal permanece activo hasta la declaración explícita del usuario.

## Auditoría contractual posterior a commits — 2026-07-14

- Estado servido: Docker Compose mantiene `app`, `db` y `adminer` activos; la aplicación local continúa en `http://localhost:8081` y MySQL está saludable. No existe diff sin integrar en las vistas, módulos, controladores, pruebas obligatorias ni registro propios de PDC.
- Terminología residual: la búsqueda completa encontró un comentario obsoleto en `tests/browser/test-pdc.mjs` que todavía esperaba `DataTable initComplete`. Se corrigió para describir el render de Handsontable, toolbar y leyenda. Las demás apariciones de DataTables en las pruebas PDC son aserciones negativas que demuestran su ausencia; las referencias de PS están fuera del sprint.
- Tokens AIA: la auditoría del hunk que hizo operable el disparador de filtros encontró cinco medidas literales propias del control. Se reemplazaron por `--ds-space-*` y `--ds-border-width`, conservando la geometría mediante `calc()` sin introducir nuevos valores visuales locales.
- Evidencia vigente: el contrato de seguridad continúa 17/17, la suite browser PDC 7/7 y el E2E PDC 4/4. La auditoría de datos posterior conserva PDC proyecto 73 en 67 filas, `papelera_pdc` en 0 y el fixture `990001` en 0.
- Estado al iniciar esta auditoría: la integración local anterior estaba aprobada y sin staging pendiente. No hubo push, despliegue ni producción. La completitud contractual sigue pendiente únicamente de la declaración explícita del usuario exigida por el objetivo.

## Sprint exclusivo Contratos — cierre técnico para revisión — 2026-07-14

### Runtime, paridad y estados

- Problema: la ruta aún podía cargar residuos DataTables, duplicar superficies y consultar `preview` para pintar el badge, lo que escribía corridas al abrir la página. La API vacía además había conservado históricamente una fila sintética.
- Corrección: `/contratos` declara runtime Handsontable-only, carga una única instancia/master y cero scripts, estilos, plugins, wrappers, helpers o listeners DataTables. El badge usa `auto/metrics` de solo lectura. Los estados `loading`, `empty`, `error` y `data` no fabrican filas.
- Comprobación navegador integrado: Desktop/Tablet muestran una fuente HOT; mobile muestra 20 tarjetas equivalentes. La carga final presentó 20 registros, una `.ht_master`, wrappers 0, overflow de página/HOT 0, encabezados y primera fila alineados, HTML crudo 0. Consola relevante 0, `Network.loadingFailed` 0 y HTTP inesperados 0.
- Prueba relacionada: `tests/browser/contratos-handsontable.mjs` incluye ausencia total de DataTables, paridad API/HOT/tarjetas, estados y carga sin escrituras en `semi_auto_*` ni `auto_program_log`.

### Tabla, tarjetas, filtros y toolbar

- Problemas: acción mobile invisible, columnas comprimidas, filtros sin prueba combinada y toolbar susceptible a recorte.
- Correcciones: las tarjetas muestran `Editar paquetes` con nombre accesible y solo para roles autorizados; las columnas se ajustan al ancho disponible sin scroll-x; los filtros nativos operan sobre el dataset completo; toolbar y selector de módulo usan controles AIA sin texto fuera del control.
- Comprobación navegador integrado: mobile confirmó 20 tarjetas, botón visible/contenido en la tarjeta, cero overflow y HOT oculto. En tablet se filtró Familia hasta `Ascensores/Aseo`, se combinó Código `3` hasta una fila y se limpiaron ambos filtros recuperando los 20 registros sin residuales.
- Viewports/temas: Mobile 390×844 Dark/Linen, Tablet horizontal 1024×768 Dark/Linen y Desktop 1440×900 Dark/Linen. Capturas durables en `goals/validar-migracion-handsontable/evidence/contratos-native/` (`mobile-*`, `tablet-*`, `desktop-*` y `final-desktop-linen.png`).

### Modal, paquetes, cantidades, recursos y duraciones

- Problemas: los cinco slots aparecían de entrada; el request de catálogo usaba un `contentType` inválido; respuestas tardías podían mezclar registros; existían riesgos de listener duplicado, cantidad inválida y chips Select2 desbordados.
- Correcciones: cada modalidad muestra Paquete 1 y revela exactamente el siguiente con `+ Agregar paquete` hasta cinco; catálogo y valores reabren correctamente; un token descarta respuestas obsoletas; guardar queda namespaced y en vuelo único; cantidad exige entero `>=1`; Select2 se limpia al cancelar y sus chips permanecen contenidos; las siete duraciones se validan y guardan como lote.
- Comprobación navegador integrado: `Estructura en Concreto` abrió con cabecera de 65 px, identidad correcta, secciones S/MO y un slot cada una. Suministro avanzó 1→5, el botón desapareció al límite, modal/página/Select2 reportaron overflow 0. Cancelar cerró sin POST, reabrir volvió a un slot y Select2 abierto quedó en 0. Un guardado real persistió `ADOQUIN x2`, cerró con éxito, refrescó HOT y reapareció igual después de recargar.
- Persistencia ampliada: la suite real guardó paquete único, cantidad 3, dos recursos y duraciones 2–8; recargó, reabrió, comprobó cada valor y restauró la huella inicial. Los valores inválidos vacío/0/negativo/decimal se rechazaron antes de enviar.
- Prueba relacionada: `tests/browser/contratos-slot-quantities.mjs`, 4 casos verdes y 1 omisión esperada por ausencia de proyecto preconstrucción.

### Modalidades, edición repetida, permisos y cancelación

- Correcciones: SI es exclusivo y bloquea MO/S/OC; los checkboxes usan el patrón AIA; Cancelar restaura valores iniciales y elimina estados temporales; abrir/cerrar el mismo registro y alternar registros no duplica controles, listeners ni catálogos.
- Sesiones reales en navegador integrado: `test.A` mostró 20 acciones y auto-definir; `test.V`, después de seleccionar realmente Da Porto, cargó 20 registros con permiso canónico `V`, capacidad editar `0`, cero acciones y cero botón auto. El backend respondió 403 a guardar y preview sin cambiar variables del navegador. Se restauró después la sesión editor.
- Pruebas relacionadas: contrato RBAC 5/5, browser de Contratos con editor/readOnly real y E2E de modal/cancelación/restauración.

### Auto-definir: preview, apply, recarga y undo

- Problema encontrado nativamente: Contratos no persistía el puntero de la última aplicación en `semi_auto_review.js`; tras recargar, un preview reciente podía recibir el undo y responder éxito con cero cambios.
- Corrección: la persistencia del puntero compartido cubre PDC y Contratos, relee el contexto al abrir/renderizar y limpia todas las claves equivalentes de la semana. El backend, si recibe un preview sin decisiones aplicadas, resuelve la última ejecución `applied` real del mismo proyecto/módulo/semana en lugar de marcar un undo vacío.
- Comprobación navegador integrado: se abrió el asistente, preview produjo 20 propuestas/17 listas, se revisaron y seleccionaron 17, `apply` modificó HOT, la recarga confirmó persistencia y `undo` restauró el estado manual anterior. La reproducción deliberada con puntero de preview terminó restaurando `ADOQUIN x2` y deshabilitando nuevamente Deshacer.
- Prueba relacionada: `tests/browser/auto-definir-contratos.mjs` 3/3; el caso principal corrompe deliberadamente el puntero con el preview reciente, recarga, deshace y exige huella idéntica.

### Pruebas, respaldo y restauración final

- Respaldo verificado antes de mutar: `/tmp/lps-aia-contratos-20260713-before.sql`, 95 MB, SHA-256 `3afe758ccb04788d8642369236f7bc472bddb31c5d53d8788fb50ddb458cf204`.
- Suites finales: browser obligatorio 21 passed/1 skipped en conjunto; auto-definir repetido 3/3; `e2e/tests/workflows/contratos-full.spec.mjs` 3/3; contratos PHP de vacío, RBAC, cantidades, duraciones, actividad multi-source, asistente moderno, deduplicación y servicios semi-auto sin fallos. Sintaxis JS/PHP válida.
- Restauración: tras las pruebas se importó nuevamente el respaldo completo con binlog de sesión deshabilitado. El dump sellado `/tmp/lps-aia-contratos-final-sealed.sql` contiene 247 sentencias `INSERT`; hash ordenado inicial y final idéntico `d3d77baef82e493ac05af0255d3c8c7620b8ffc294af00f4f54fcd9652726886`. Proyecto 73 conserva 20 actividades y cero corridas Contratos recientes del sprint.
- Estado visible entregado: navegador integrado abierto en `/contratos?semana=1`, Desktop Linen 1440×900, tabla restaurada con 20 registros, filtros limpios y sin paquetes de prueba.
- Publicación: no se hicieron commits, push, despliegue ni cambios en producción. No se incluyó ni revirtió trabajo ajeno. Este sprint y el goal permanecen activos hasta que el usuario los declare explícitamente completos.

### Ficha final por flujo PDC

| Función o problema validado | Corrección realizada | Cómo se comprobó | Navegador integrado | Persistencia | Restauración | Viewports y temas | Prueba automatizada | Evidencia pendiente |
|---|---|---|---|---|---|---|---|---|
| Grid principal y runtime legacy | Se dejó `#dt_cliente` como una única instancia Handsontable y se retiraron recursos, plugin, wrappers, helpers y listeners DataTables. | DOM, recursos y listeners jQuery; paridad de filas y encabezados. | Grid visible, alineado y operable; DataTables 0. | Lectura completa de la fuente PDC. | No aplica a carga. | Las seis combinaciones. | `pdc-handsontable.mjs` casos 1 y 4; `pdc-full.spec.mjs` caso 1. | Ninguna. |
| Grid secundario `dt_definirContratos` | Reutilización de instancia y listener namespaced único. | Tres aperturas/cierres, identidad estable, una `.ht_master` y una sola petición al guardar. | Modal abierto/cerrado repetidamente sin doble backdrop ni control cortado. | Entero válido reaparece tras recarga. | Snapshot devuelve la cantidad inicial. | Las seis combinaciones con el modal abierto. | `pdc-handsontable.mjs` casos 4, 5 y 7; `pdc-full.spec.mjs` caso 4. | Ninguna. |
| Filtros por columna | Disparador sobre el encabezado y limpieza total del plugin de filtros. | Apertura real, selección de valor calculado contra la fuente completa, aplicación y `Limpiar filtros`. | Menú nativo abrió `ACERO DE REFUERZO`; la vista redujo resultados y volvió al conjunto visible inicial. | No altera datos. | La limpieza restituye el estado sin filtros. | Mobile, tablet y desktop; Dark/Linen. | `pdc-handsontable.mjs` casos 1 y 2. | Ninguna. |
| Leyenda, conteos y alertas | Conteos derivados de la fuente y filtros mutuamente limpiables. | Suma de estados, activación de alertas, selección de estado y limpieza. | Chips visibles y contenidos; conteos sincronizados. | No altera datos. | Limpieza devuelve todas las filas. | Las seis combinaciones. | `pdc-handsontable.mjs` caso 3. | Ninguna. |
| Estados, fechas y modal de edición | Carga moderna del registro y cálculo de estado, etapa y diagnóstico. | Abrir, cargar valores, cancelar, guardar, recargar y reabrir. | Modal mostró cálculos y descartó el texto de cancelación. | Observación temporal sobrevivió a recarga. | Snapshot y segunda recarga recuperaron el valor inicial. | Desktop Linen/Dark y matriz general sin overflow. | `pdc-full.spec.mjs` caso 3 más contratos PHP de fechas. | Ninguna. |
| Eliminación condicionada | Filas base protegidas; subcontratos adicionales se archivan antes de borrar. | 422 para base, eliminación UI, recarga, fila en papelera, restore y segunda recarga. | Confirmación y desaparición del subcontrato adicional verificadas. | Ausencia persistió tras recargar. | Restore transaccional recuperó la fila y vació papelera; snapshot final idéntico. | Desktop y matriz general del grid. | `pdc-full.spec.mjs` caso 4; contrato PHP de restauración. | Ninguna. |
| Desglosar y cantidades | Validación cliente/servidor de enteros `>=1`; rechazo de vacío, cero, negativo y decimal. | Cancelar sin POST, cinco respuestas 422 deliberadas/protegidas, guardado válido y conteo real de subcontratos. | Modal conserva el valor anterior al cancelar y muestra el válido al reabrir. | Cantidad válida y filas derivadas persisten tras recargar. | Snapshot devuelve cantidad y filas iniciales. | Las seis combinaciones para `dt_definirContratos`. | `pdc-handsontable.mjs` casos 5 y 7; `pdc-full.spec.mjs` caso 4. | Ninguna. |
| Automatización | Persistencia PDC del `run_id` aplicado, limitada a este módulo, y undo después de reload. | `preview`, selección, `apply`, huella distinta, recarga, huella persistida, `undo` y huella inicial. | Panel visible operó el flujo completo con propuesta seleccionada. | Aplicación sobrevivió a recarga. | Undo y snapshot eliminaron fixture y cambios. | Desktop visible; panel contenido por la matriz general. | `pdc-full.spec.mjs` caso 2. | Ninguna. |
| Permisos reales | Fallback y servicio niegan edición/auto a `V`; backend exige capacidad y CSRF. | Login real `test.A`; cuenta temporal con membresía real `V`; celdas, botones y 403 backend. | Editor pudo editar; readOnly vio acciones de consulta y controles deshabilitados. | Solicitudes denegadas no mutaron datos. | Cuenta/membresía temporal y snapshot se eliminan/restauran en teardown. | Verificación visible más matriz general. | `pdc-handsontable.mjs` caso 6; contrato PHP 17/17. | Ninguna. |
| Respaldo y restauración integral | Snapshots incluyen PDC, papelera, actividades y nueve tablas semi-auto. | Huella antes/después de cada caso y auditoría SQL final. | Flujos destructivos se comprobaron antes y después de recargar. | PDC proyecto 73 terminó en 67 filas. | Papelera 0 y fixture `990001` 0; huella inicial recuperada. | Aplica a todos los flujos destructivos. | Teardown de ambas suites obligatorias. | Ninguna. |
| Invariantes visuales y de diagnóstico | Medidas del filtro usan tokens AIA; matriz detecta overflow, alineación, controles, HTML crudo, DataTables y listeners legacy. | Medición de ambos grids y colectores de pageerror, consola, HTTP 4xx/5xx y transporte; respuestas 403/422 deliberadas se consumen individualmente. | Seis combinaciones sin mezcla temática, overflow ni contenido crudo; consola/red limpias. | No aplica. | No aplica. | Mobile 390×844, tablet 1024×768 y desktop 1440×900; Dark/Linen. | Matriz de `pdc-handsontable.mjs` y colectores globales de `pdc-full.spec.mjs`. | Ninguna. |

### Recuperación y revalidación posterior a ENOSPC — 2026-07-14

- Incidente local: una corrida E2E comenzó con el volumen al 100 %. MySQL abortó durante el `fsync` del binlog mientras `ProjectDbSnapshot` restauraba `semi_auto_suggestions`; el reinicio automático dejó parcialmente vacías las 12 tablas del proyecto 73. La corrida se declaró inválida y no se usó como evidencia.
- Liberación segura: se eliminaron exclusivamente 8.57 GB de caché de compilación Docker no utilizada. No se borraron volúmenes, imágenes activas, respaldos, datos de usuario ni archivos del worktree; `app`, `db` y `adminer` permanecieron disponibles y MySQL volvió saludable.
- Respaldo antes de reparar: el estado parcial se guardó en `/private/tmp/lps-aia-pdc-broken-after-enospc-20260714.sql`, SHA-256 `683a4ebaba4a8025a1d985b6c428d20fd88a6a7e88150f89cd4aa937589b4581`.
- Restauración quirúrgica: se cargó `/private/tmp/lps-aia-contratos-final-sealed.sql`, sellado 46 segundos antes del incidente con SHA-256 `640fbc200e9241a03aa4046ae4debbd4aab51a7103dba6701edd3f90d4f38522`, en un esquema temporal. Se reemplazaron solo las filas `project_id=73` de actividades, PDC, papelera y nueve tablas semi-auto; ningún otro proyecto fue tocado.
- Prueba exacta de restauración: los dumps ordenados del esquema temporal y del estado restaurado fueron idénticos byte por byte, SHA-256 `867096a0ec11cb881148fcb8bbd4101d08340ed08eca9eb446354c6d6679b38a`. El esquema temporal se eliminó solo después de `cmp` exitoso.
- Revalidación posterior: contrato PHP 17/17; `tests/browser/pdc-handsontable.mjs` 7/7 en 20.5 s; `e2e/tests/workflows/pdc-full.spec.mjs` 4/4 en 34.4 s. Los nuevos colectores fallan ante pageerror, console error/warn, HTTP 4xx/5xx inesperado y `requestfailed`; los 403/422 deliberados se consumen por evento exacto.
- Estado final tras las suites: un tercer dump de las 12 tablas volvió a ser idéntico al baseline con SHA-256 `867096a0ec11cb881148fcb8bbd4101d08340ed08eca9eb446354c6d6679b38a`. Conteos: actividades 20, PDC 67, papelera 0, runs 116, sugerencias 2480, decisiones 93, feedback 76, assistant feedback 0, learning candidates 18, learning rules 0, project config 0, proactive queue 5 y fixture `990001` 0.
- Integración de este bloque: Plannotator revisó el diff selectivo de siete archivos contra `codex/pdc-handsontable` y cerró sin cambios solicitados. Los endurecimientos y esta recuperación se integran en un commit local separado, sin incluir el staging BI concurrente. No hubo push, despliegue ni producción; el goal continúa activo hasta declaración explícita.

## Reconciliación final exclusiva de Contratos — 2026-07-14

### Incidente de restauración y corrección de causa raíz

- Problema: el helper de snapshot ejecutaba el `DELETE` y la importación en procesos MySQL separados. Un reinicio de MySQL durante la limpieza dejó el proyecto 73 con cero actividades antes de poder importar el respaldo; esa corrida se invalidó inmediatamente y no se usó como evidencia.
- Respaldo y contención: se conservó el respaldo previo `/tmp/lps-aia-contratos-20260713-before.sql` (SHA-256 `3afe758ccb04788d8642369236f7bc472bddb31c5d53d8788fb50ddb458cf204`) y se guardó el estado parcial en `/tmp/lps-aia-contratos-crash-state-20260714.sql` (SHA-256 `7d599abd5cd8aba5a38fcea44321ad819b0760e3c05ca93ac126446f8cc8546c`).
- Corrección: `ProjectDbSnapshot.restore()` ahora envía deshabilitación de binlog, llaves foráneas, `START TRANSACTION`, limpieza, importación y `COMMIT` en una sola sesión y reintenta el bloque completo; la prueba de reintentos exige que cada intento contenga tanto el borrado como la reinserción.
- Restauración quirúrgica: el respaldo se importó en `contratos_restore_tmp` y se repusieron únicamente las filas `project_id=73` de `actividades` y las nueve tablas `semi_auto_*` capturadas por Contratos. Ningún otro proyecto ni módulo se reemplazó.
- Prueba exacta: después de todas las suites, el dump ordenado de esas diez tablas produjo la misma huella en temporal y activo: SHA-256 `ac9770f3f2d1865c40d62805e1bd1f4c8ce1696138023972ae5800261d504800`. Conteos activos: actividades 20, runs 116, sugerencias 2480, decisiones 93, feedback 76, assistant feedback 0, candidatos 18, reglas 0, configuración 0 y cola 5. El esquema temporal se eliminó solo después de confirmar igualdad y la app siguió mostrando 20 registros.

### Cierre de observaciones de interfaz y automatización

- Paquetes progresivos: cada modalidad abre con Paquete 1 y el botón `+ Agregar paquete N` revela uno por vez hasta Paquete 5; al llegar a cinco el botón desaparece. Navegador integrado: 1→5 comprobado sobre `Estructura en Concreto`, sin overflow, y Cancelar devolvió el modal a un slot.
- Acción mobile: `Editar paquetes` tiene texto accesible y contraste visible dentro de cada tarjeta para editor; readOnly no recibe la acción. Se comprobó en Mobile Dark y Linen, además del caso automatizado dedicado.
- Catálogo y recursos: el navegador integrado cargó opciones reales por modalidad, permitió búsqueda y creó temporalmente el chip `Recurso nativo temporal` con overflow 0 y sin HTML crudo. Cancelar cerró sin guardar; al reabrir el recurso no existía.
- Auto-definir: el puntero de undo queda limitado exactamente a módulo/proyecto/semana. `apply` y `undo` ya no reportan éxito cuando hubo cero cambios o errores; el backend acepta `applied_with_errors` para restauración parcial y rechaza con 409 un run sin decisiones aplicadas. La prueba verifica que, aun con el puntero reemplazado por un preview reciente, el request de undo resuelve el `run_id` realmente aplicado y restaura la huella.

### Revalidación automatizada y visible

- Suites browser obligatorias en una sola corrida: 23 passed, 1 skipped en 1.6 min. La omisión corresponde únicamente al bloqueo por URL directa de preconstrucción, no aplicable al proyecto de construcción activo. Incluye 16 casos de runtime/UI, 3 de Auto-definir y 4 de cantidades/duraciones/persistencia.
- Workflow E2E con su configuración propia: `e2e/tests/workflows/contratos-full.spec.mjs`, 3/3 en 10.0 s.
- Contratos PHP: vacío sin sintéticos, RBAC editor/readOnly, cantidades, siete duraciones, actividad multi-source, asistente moderno, deduplicación, reemplazo legacy y servicio semi-auto: 0 fallos; smoke 16 passed/1 data-dependent skipped y servicio 31/31.
- Robustez del helper: `tests/browser/db-snapshot-retry.mjs`, 4/4. Sintaxis de JS/PHP limpia y `git diff --check` sin errores.
- Navegador integrado final: `/contratos?semana=1`, 20 registros, una `.ht_master`, globals/wrappers DataTables 0, overflow página/HOT 0, HTML crudo 0, acciones eliminar 0, modal cerrado y estado `20 registros de contratos cargados.` La consola nativa contiene únicamente mensajes `log`; `warn` y `error` 0. La matriz durable conserva Mobile 390×844, Tablet horizontal 1024×768 y Desktop 1440×900 en Dark y Linen.
- Evidencia pendiente: únicamente revisión y declaración explícita del usuario. No se hizo commit, push, despliegue ni cambio en producción; el sprint y el goal siguen activos.

## Auditoría posterior al objetivo adjunto — Contratos — 2026-07-14

### Evidencias que se reforzaron

- Filtros por columna: el caso browser ya no aplica ni limpia condiciones mediante llamadas directas al plugin. Abre el menú visible de Familia, usa `Borrar`, búsqueda, checkbox y `OK`; abre después Código, combina otro checkbox y finalmente usa `Seleccionar todo` y `OK` en ambos menús. La vista baja desde el conjunto completo, combina hasta una fila y vuelve a 20 sin condiciones residuales.
- Paridad y acciones: cada tarjeta compara semánticamente su resumen de paquetes con la misma fila API/HOT. Editor tiene una acción mobile y una acción HOT por cada registro; readOnly tiene cero en ambas superficies y recibe 403 del backend.
- Columnas y toolbar: se miden ancho asignado/efectivo, espacio sin aprovechar, jerarquía de anchos, truncado horizontal/vertical, una sola fila, contención, tamaño relativo, gap, márgenes y radios derivados de tokens. Contratos, Listado y PDC consumen el mismo `AIAInfoGeneralNav`; no se navegó fuera de `/contratos`.
- Paquetes y ayudas: los cinco slots quedan numerados, cada tarjeta conserva las tres etiquetas y ayudas exigidas y la separación se demuestra mediante gap, borde, radio y sombra del patrón AIA. Evidencia nativa de paquetes/cantidades: `evidence/contratos-native/cantidades-paquetes-dark-20260714.png`.
- Contenido hostil en recursos: el test escribió literalmente `Recurso <img src=x onerror=alert(1)> escrito`; Select2 lo conservó como texto y generó cero `img`, `script` o atributos ejecutables. La primera corrida reveló 21.97 px de desbordamiento con ese chip. Se corrigieron `box-sizing`, límites, salto seguro y flex del botón de borrado dentro del modal; el caso y la corrida completa posteriores quedaron en overflow 0.

### Duraciones en navegador integrado

- Flujo destructivo nativo: sobre `Estructura en Concreto` se creó temporalmente `NATIVE DURACIONES 20260714`, cantidad 2. Guardar abrió automáticamente `Duraciones pendientes` con exactamente siete campos.
- Guardado: se escribieron 2, 3, 4, 5, 6, 7 y 8; `Guardar duraciones` cerró ambos modales, mostró `Los contratos se guardaron correctamente.` y refrescó los 20 registros.
- Persistencia: después de recargar, el modal principal reabrió el mismo registro con paquete y cantidad 2; la base devolvió exactamente `2,3,4,5,6,7,8`. Evidencia nativa: `evidence/contratos-native/duraciones-pendientes-linen.png`.
- Restauración: se cerró sin una escritura adicional, se repusieron atómicamente `actividades` y `contratos_trazabilidad` desde el esquema temporal y se eliminaron solo la duración y la auditoría creadas por este flujo. Después de recargar el paquete temporal no existe.

### Undo y restauración integral

- `undo` ahora es todo-o-nada: cualquier decisión que no pueda revertirse hace rollback de toda la corrida. El servicio rechaza un segundo undo del mismo run, resuelve `applied_with_errors` desde un preview más reciente y conserva 409 para una aplicación sin cambios disponibles.
- El cliente habilita y conserva Deshacer cuando una aplicación fue parcial; el caso browser prueba respuesta 1 aplicada/1 error y la clave exacta módulo/proyecto/semana. El caso principal también demuestra que el puntero natural sobrevivió a recarga antes de forzar el preview reciente para probar el fallback.
- El servicio incluye una decisión inválida después de una reversión válida, verifica rollback total, retira solo esa decisión de prueba, resuelve el run parcial y restaura la actividad. Resultado: 0 fallos.
- Reconciliación final ampliada: se compararon `actividades`, `contratos_trazabilidad`, las nueve tablas `semi_auto_*`, el catálogo completo `general_dias_procesos_contratacion` y la auditoría Contratos/Da Porto. Temporal y activo produjeron la misma huella SHA-256 `3ca9a32a40df154c2aee6a35446680b34455469f095f598d9751631f8e778095` después de todas las suites. Conteos finales: 20 actividades, paquete nativo 0 y auditoría nativa 0. El esquema temporal se eliminó después de la igualdad.

### Gate final actualizado

- Suites browser obligatorias: 25 passed, 1 skipped esperado en 2.2 min: 17 casos de tabla/modal/matriz, 4 de Auto-definir y 4 de cantidades/duraciones; la omisión sigue siendo únicamente la URL de preconstrucción no habilitada para este proyecto.
- Workflow E2E: 3/3 en 17.0 s. Helper transaccional: 4/4. Contratos PHP, asistente moderno y servicio semi-auto: 0 fallos; sintaxis JS/PHP y `git diff --check` limpios.
- Navegador integrado final: `/contratos?semana=1`, Linen, CSS `20260714-dark7`, 20 registros, una `.ht_master`, DataTables DOM/runtime 0, overflow página/HOT 0, HTML crudo 0, modal cerrado y consola `warn/error` 0. Los guardados nativos de paquete y duraciones respondieron éxito visible y persistieron antes de restaurarse.
- Matriz nativa durable: Mobile 390×844, Tablet horizontal 1024×768 y Desktop 1440×900, Dark/Linen. Evidencia adicional de duraciones y entrega final en `evidence/contratos-native/`.
- Evidencia pendiente: únicamente revisión y declaración explícita del usuario. No hubo commit, push, despliegue ni producción; el sprint y el goal continúan activos.
