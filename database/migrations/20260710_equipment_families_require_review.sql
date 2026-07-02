-- Las familias de categoria EQUIPOS pueden representar actividad operativa,
-- alquiler, recurso o paquete contractual segun el proyecto.
-- Mientras no exista decision humana por cada una, no deben quedar listas para
-- aplicacion automatica en /listado-actividades/.

UPDATE `general_pdc_familias`
SET `siempre_revision` = 1
WHERE COALESCE(`activa`, 1) = 1
  AND `categoria` = 'EQUIPOS'
  AND COALESCE(`siempre_revision`, 0) = 0;
