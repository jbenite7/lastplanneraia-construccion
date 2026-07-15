<!-- plannotator:gate -->
# Plan efectivo: sprint exclusivo de Contratos

## Contrato y límites

- `goal.md` es el contrato principal y este plan aplica exclusivamente a `/contratos`.
- BI, Listado, PDC, PG, PI y PS quedan fuera, salvo componentes compartidos que causen directamente una regresión de Contratos.
- Se preserva el worktree ajeno. No habrá commit, push, despliegue ni cambios en producción.
- El sprint y el goal permanecen activos hasta que el usuario los declare explícitamente completos.

## 1. Línea base, runtime y datos

1. Confirmar Docker Compose, sesión, proyecto, semana, rutas, API y capacidades reales.
2. Conservar respaldo previo verificable y snapshot de tablas afectadas antes de mutaciones.
3. Auditar una fuente Handsontable, cero runtime DataTables y paridad API/HOT/tarjetas.
4. Registrar continuamente problema, corrección, navegador, persistencia, restauración, matriz, prueba y evidencia pendiente.

## 2. Tabla, tarjetas y estados

1. Validar `loading`, `empty`, `error` y `data` sin registros sintéticos.
2. Comprobar encabezados, columnas, filtros combinados y limpieza total.
3. Medir alineación, texto contenido, HTML seguro y cero overflow horizontal.
4. Validar toolbar y selector de módulo responsive con patrones y tokens AIA.

## 3. Modal y persistencia

1. Verificar registro correcto, cabecera compacta, familia, cierre, guardar y cancelar.
2. Validar modalidades, exclusividad SI y controles AIA.
3. Implementar slots progresivos con `+` hasta cinco por modalidad.
4. Probar catálogo real, cantidades enteras `>=1`, recursos múltiples, Select2 y siete duraciones.
5. Repetir aperturas del mismo registro y alternar registros sin mezcla, controles ni listeners duplicados.
6. Validar una petición por guardado, errores visibles, cierre/refresh y cancelación sin escrituras ni residuos.

## 4. Automatización y permisos

1. Recorrer `preview → revisión/edición → selección → apply → reload → persistencia → undo → restauración`.
2. Garantizar que undo nunca informe éxito con cero cambios y que su puntero quede aislado por módulo/proyecto/semana.
3. Contrastar sesiones autenticadas editor y readOnly, UI y backend, sin simular permisos en el navegador.
4. Confirmar explícitamente que no existe acción residual de eliminación; si existiera, cubrir confirmación, persistencia y restauración.

## 5. Evidencia y pruebas

1. Usar solo el navegador integrado para validación manual, capturas y evidencia de aprobación.
2. Ejecutar Playwright CLI como verificación técnica adicional con restauración atómica.
3. Cubrir Mobile 390×844, Tablet 1024×768 y Desktop 1440×900, cada uno Dark/Linen.
4. Vigilar consola, HTTP fallidos, transporte, mezcla de temas, HTML crudo, overflow y runtime legado.
5. Mantener y ejecutar las cuatro suites obligatorias y contratos PHP enfocados.
6. Actualizar `docs/qa/workflows.md` y retirar lenguaje/selectores obsoletos de Contratos.

## 6. Cierre para aprobación

1. Restaurar las tablas al respaldo inicial y verificar huella y conteos.
2. Consolidar la matriz requisito → prueba → navegador → persistencia → restauración → evidencia.
3. Dejar una única pestaña visible del navegador integrado con el resultado final.
4. Explicar las correcciones en lenguaje sencillo y detenerse sin commit para aprobación del usuario.
