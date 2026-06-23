/**
 * Task 4 - Corrección UI: Actividades sin nombre
 * 
 * Archivo: views/listado-actividades/listadoActividades.view.php
 * Línea: 847
 * 
 * Cambio: Mostrar "Sin nombre" cuando s.actividad sea vacío/null/falsy
 * 
 * Antes:
 *   escaparHtml(s.actividad ? s.actividad.replace(/<[^>]+>/g, '').substring(0, 80) : '')
 * 
 * Después:
 *   escaparHtml(s.actividad ? s.actividad.replace(/<[^>]+>/g, '').substring(0, 80) : 'Sin nombre')
 * 
 * Commit: fix(listado-actividades): show placeholder for activities without name
 */

// La línea 847 modificada muestra "Sin nombre" en lugar de cadena vacía
// cuando s.actividad es falsy (null, undefined, empty string)
var actividadRender = escaparHtml(s.actividad ? s.actividad.replace(/<[^>]+>/g, '').substring(0, 80) : 'Sin nombre');
// Esto asegura que la celda de la tabla nunca quede vacía para actividades sin nombre
