import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const hot = readFileSync(new URL('../../public/js/modules/listado_actividades/hot.js', import.meta.url), 'utf8');
const view = readFileSync(new URL('../../views/listado-actividades/listadoActividades.view.php', import.meta.url), 'utf8');
const css = readFileSync(new URL('../../public/css/listado-actividades.css', import.meta.url), 'utf8');

test('Listado usa una sola cabecera Handsontable interactiva', () => {
  assert.doesNotMatch(hot, /syncExternalHeader|la-hot-external-headers/);
  assert.doesNotMatch(css, /\.ht_clone_top[^}]*pointer-events\s*:\s*none/is);
});

test('renderers convierten la fila visual en fila física', () => {
  assert.match(hot, /getSourceDataAtRow\(instance\.toPhysicalRow\(row\)\)/);
  assert.doesNotMatch(hot, /getSourceDataAtRow\(row\)/);
});

test('inicialización y listeners destructivos son idempotentes', () => {
  const finalBootstrap = view.slice(view.lastIndexOf('<!-- Handsontable'));
  assert.doesNotMatch(finalBootstrap, /cargaParametros\(\)/);
  assert.match(view, /#eliminar-usuario[\s\S]*?\.off\(["']click\.laListado["']\)[\s\S]*?\.on\(["']click\.laListado["']/);
  assert.match(view, /#modalNuevaActividad[\s\S]*?\.off\(["']change\.laListado["']/);
});

test('Listado expone limpieza total de filtros', () => {
  assert.match(hot, /clearConditions\(\)/);
  assert.match(hot, /#btn_limpiar_buscador/);
});

test('fecha derivada es solo lectura y modalidad admite combinaciones', () => {
  assert.doesNotMatch(hot, /fechaInicio:\s*true/);
  assert.match(hot, /prop === 'tipoContrato'[\s\S]*normalizeTipoContratoValue\(value\)/);
  assert.match(hot, /data: 'tipoContrato',[\s\S]*type: 'text'/);
  assert.doesNotMatch(hot, /data: 'tipoContrato',[\s\S]*source: TIPO_CONTRATO_OPTIONS/);
});

test('mensajes de servidor se insertan como texto seguro', () => {
  assert.doesNotMatch(hot, /\.html\([^\n]*\+\s*message/);
  assert.match(hot, /messageNode\.textContent\s*=\s*String\(message/);
});

test('Handsontable conserva la geometría calculada de columnas', () => {
  assert.doesNotMatch(hot, /setImportantStyle\(element, 'width', 'auto'\)/);
  assert.doesNotMatch(hot, /setImportantStyle\(element, 'box-sizing', 'content-box'\)/);
  assert.doesNotMatch(hot, /manualColumnResize:\s*true/);
});

test('estados de carga ocultan datos anteriores y cancelan respuestas obsoletas', () => {
  assert.match(hot, /activeLoadRequest\.abort\(\)/);
  assert.match(hot, /state !== 'ready'[\s\S]*display[\s\S]*none/);
  assert.match(hot, /aria-busy/);
  assert.match(hot, /!response \|\| !Array\.isArray\(response\.data\)[\s\S]*setTableState\('error'/);
});

test('guardado mobile es atómico y solo cierra tras confirmación', () => {
  assert.match(hot, /url:\s*'\/api\/listado-actividades\/update-card/);
  assert.doesNotMatch(hot, /function saveActividadInicioFromCard/);
  assert.doesNotMatch(hot, /function saveTipoContratoFromCard/);
  assert.match(hot, /respuesta === 'BIEN'[\s\S]*activeMobileEditRowIndex = null/);
});
