# Plan: Terminologia de modulos de gestion

## Enfoque

Cambiar la terminologia visible de `/listado-actividades/`, `/contratos/` y `/pdc/` sin cambiar rutas, nombres de columnas de base de datos ni codigos internos como `tipoContrato`, `tipoPaquete`, `MO`, `S`, `SI` u `OC`. Donde una palabra sea valor de negocio usado para consultas, se conserva internamente y se traduce solo en la interfaz.

## Pasos

1. Centralizar el vocabulario visible de los tres modulos.
   - Tocar: `views/listado-actividades/listadoActividades.view.php`, `views/contratos/contratos.view.php`, `views/pdc/pdc.view.php`.
   - Si conviene por repeticion, agregar un mapa pequeno de etiquetas visible en JS/PHP local al modulo.
   - Verificacion: buscar que los textos aprobados existan y que los textos reemplazados no sigan visibles en esas vistas.

2. Actualizar navegacion, breadcrumbs y pestanas compartidas.
   - Tocar: `public/js/cargarDatosGeneralesPagina2.js` y los switchers embebidos en las tres vistas.
   - Aplicar:
     - `Familias de obra`
     - `Paquetes de contratacion`
     - `Plan de Compras y Contrataciones`
   - Mantener ids/rutas: `info_listadoActividades`, `info_contratos`, `planCompras`, `/listado-actividades`, `/contratos`, `/pdc`.
   - Verificacion: navegador en las tres rutas y prueba E2E de navegacion moderna.

3. Actualizar `/listado-actividades/`.
   - Tocar: `views/listado-actividades/listadoActividades.view.php`.
   - Aplicar:
     - modulo `Familias de obra`
     - registro `Familia`
     - `Auto-generar Familias`
     - `Inicio en obra segun cronograma`
     - `Modalidad de contratacion`
     - `Orden de servicio/compra`
   - Conservar nombres tecnicos y payloads: `actividad`, `tipoContrato`, `actividadInicio`, `fechaInicio`.
   - Verificacion: abrir `/listado-actividades?semana=8`, confirmar encabezados/botones y crear una captura.

4. Actualizar `/contratos/`.
   - Tocar: `views/contratos/contratos.view.php` y, si las etiquetas vienen desde backend, `src/Controllers/Api/ContratosApiController.php`.
   - Aplicar:
     - modulo `Paquetes de contratacion`
     - `Modalidad de contratacion`
     - `Paquetes de contratacion asociados`
     - `Paquetes de mano de obra`
     - `Paquete de contratacion`
     - `Cantidad de contratos`
     - `Insumos y recursos requeridos`
     - `Orden de servicio/compra`
   - Mantener valores internos de catalogo (`Mano de Obra`, `Orden de Compra`) si son necesarios para consultas.
   - Verificacion: abrir `/contratos?semana=8`, abrir modal de edicion, confirmar encabezados y que los paquetes siguen cargando.

5. Actualizar `/pdc/`.
   - Tocar: `views/pdc/pdc.view.php`, `src/Controllers/Api/PdcApiController.php`, `src/Services/SemiAutoService.php` y `src/Legacy/_pdc_functions.php` solo donde generen textos visibles de estado.
   - Aplicar:
     - `Plan de Compras y Contrataciones`
     - `Ver alertas`
     - `Informacion pendiente`
     - `Inicio de contratacion vencido`
     - `Contratacion atrasada`
     - `Contratacion cerrada tarde`
     - `Contratacion cerrada a tiempo`
     - `Contratacion en curso`
     - `Contratacion pendiente de inicio`
     - `Familias asociadas`
     - `Estado del proceso`
     - `Inicio en obra segun cronograma`
     - `Inicio en obra proyectado`
     - `Inicio en obra real`
     - mensajes `Atrasado: contratacion sin iniciar` y `En curso: contratacion sin iniciar`
   - Ajustar clasificadores JS que hoy detectan textos antiguos como `Atrasado!!`, `Terminado con retrasos` o `En Curso`.
   - Verificacion: abrir `/pdc?semana=8&origen=info_contratos`, revisar chips, columnas, filas y modal.

6. Actualizar pruebas y documentacion afectada.
   - Tocar segun falle o segun aserciones directas: `tests/browser/test-pdc.mjs`, `tests/browser/auto-definir-contratos.mjs`, `tests/browser/support/moduleFlows.mjs`, `docs/qa/workflows.md`.
   - Agregar o actualizar una prueba enfocada que valide los textos aprobados en las tres rutas.
   - Verificacion:
     - `npx playwright test tests/browser/test-pdc.mjs`
     - prueba enfocada nueva o existente para `/listado-actividades/` y `/contratos/`

7. Verificacion final en runtime real.
   - Confirmar que Docker esta sirviendo codigo actual.
   - Ejecutar pruebas enfocadas de PHP si se toca backend de estados:
     - `docker compose exec app php tests/test_auto_definir_contratos.php`
     - `docker compose exec app php tests/test_lacp_manual_crud_persistence.php`
   - Ejecutar Playwright enfocado:
     - `npx playwright test tests/browser/test-pdc.mjs`
     - `npx playwright test tests/browser/auto-definir-contratos.mjs`
   - Tomar evidencia visual de las tres rutas principales.

## Riesgos

- Cambiar valores persistidos o usados para filtros puede romper consultas de paquetes, duraciones y PDC. La regla es traducir visualmente, no migrar datos, salvo aprobacion explicita.
- Algunos textos antiguos pueden venir de filas guardadas en `pdc.estado`; si existen estados historicos, el frontend debe mapear ambos formatos durante una transicion.
- “Familias de obra” debe quedar explicado en una ayuda breve o contexto visible porque no es obvio para usuarios nuevos.
- La interfaz tiene tablas anchas; los nuevos textos pueden requerir ajuste responsive para no desbordar en navegador.
