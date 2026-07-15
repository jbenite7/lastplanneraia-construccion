import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const read = (path) => readFileSync(new URL(`../${path}`, import.meta.url), 'utf8');
const loaderSource = read('public/js/linksComunesHead2.js');
const mobileFixSource = read('public/js/mobile-table-fix.js');
const generalRuntime = read('public/js/cargarDatosGeneralesPagina2.js');
const listadoView = read('views/listado-actividades/listadoActividades.view.php');

function runLoader(handsontableOnly) {
  const appended = [];
  const head = { appendChild(node) { node.parentNode = head; appended.push(node); } };
  const document = {
    createElement: (tagName) => ({
      tagName,
      dataset: {},
      listeners: {},
      addEventListener(name, listener) { this.listeners[name] = listener; },
      setAttribute(name, value) { this[name] = value; },
    }),
    getElementById: (id) => id === 'head' ? head : null,
    getElementsByTagName: (tagName) => tagName === 'head' ? [head] : [],
    querySelector: () => null,
  };
  vm.runInNewContext(loaderSource, {
    document,
    window: { __AIA_HANDSONTABLE_ONLY__: handsontableOnly },
  });
  return appended;
}

function registeredDocumentListeners(handsontableOnly) {
  const listeners = [];
  const document = { addEventListener: (name) => listeners.push(name) };
  vm.runInNewContext(mobileFixSource, {
    console,
    document,
    window: { __AIA_HANDSONTABLE_ONLY__: handsontableOnly },
  });
  return listeners;
}

const hotOnlyNodes = runLoader(true);
const hotOnlyAssets = hotOnlyNodes.map((node) => node.src || node.href || '');
const hotOnlyRules = hotOnlyNodes
  .filter((node) => node.tagName === 'style')
  .map((node) => node.innerHTML || '')
  .join('\n');

assert.ok(hotOnlyAssets.every((asset) => !/datatables|datatable-height|global-table-align|mobile-table-fix/i.test(asset)));
assert.doesNotMatch(hotOnlyRules, /table\.dataTable|dataTables_/i);
assert.deepEqual(registeredDocumentListeners(true), []);

const sharedStyles = hotOnlyNodes.find((node) => /\/css\/styles\.css/.test(node.href || ''));
const fakeRules = [
  { selectorText: 'table.dataTable td', style: { cssText: 'color: red;' } },
  { selectorText: '.table td, table.dataTable td', style: { cssText: 'color: blue;' } },
];
sharedStyles.sheet = {
  cssRules: fakeRules,
  deleteRule(index) { fakeRules.splice(index, 1); },
  insertRule(cssText, index) {
    fakeRules.splice(index, 0, { selectorText: cssText.split('{')[0].trim(), style: { cssText: 'color: blue;' } });
  },
};
assert.equal(sharedStyles.media, 'not all');
assert.equal(typeof sharedStyles.listeners.load, 'function');
sharedStyles.listeners.load();
assert.equal(sharedStyles.media, 'all');
assert.equal(sharedStyles.dataset.aiaDataTablesPurged, 'true');
assert.deepEqual(fakeRules.map((rule) => rule.selectorText), ['.table td']);

const legacyNodes = runLoader(false);
const legacyAssets = legacyNodes.map((node) => node.src || node.href || '').join('\n');
const legacyRules = legacyNodes.map((node) => node.innerHTML || '').join('\n');

assert.match(legacyAssets, /jquery\.dataTables\.css/);
assert.match(legacyAssets, /datatable-height-manager\.js/);
assert.match(legacyAssets, /global-table-align\.js/);
assert.match(legacyAssets, /mobile-table-fix\.js/);
assert.match(legacyRules, /table\.dataTable/);
assert.deepEqual(registeredDocumentListeners(false), ['DOMContentLoaded']);
assert.match(mobileFixSource, /\.ps-action-btn\s*\{\s*display:\s*none\s*!important/);

assert.doesNotMatch(generalRuntime, /#dt_cliente tbody tr|draw\.dt|\.DataTable\(/);
assert.match(listadoView, /linksComunesHead2\.js\?v=20260711listadoCssPurge3/);
assert.match(listadoView, /listado_actividades\/hot\.js\?v=20260712listadoAudit3/);
assert.equal((listadoView.match(/src="\/vendor\/jquery-ui\.min\.js"/g) || []).length, 1);
assert.equal((listadoView.match(/id="Id"/g) || []).length, 1);
assert.equal((listadoView.match(/id="opcion"/g) || []).length, 1);
const listadoIds = [...listadoView.matchAll(/\bid="([^"]+)"/g)].map((match) => match[1]).filter(Boolean);
assert.equal(new Set(listadoIds).size, listadoIds.length, 'La vista no debe declarar IDs duplicados.');

console.log('PASS Listado Handsontable runtime loader contract');
