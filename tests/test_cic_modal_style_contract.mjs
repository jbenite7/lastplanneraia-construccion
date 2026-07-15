import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const css = readFileSync(new URL('../public/css/programacion-semanal.css', import.meta.url), 'utf8');
const view = readFileSync(new URL('../views/programacion-semanal/CIC.view.php', import.meta.url), 'utf8');
const controller = readFileSync(new URL('../src/Controllers/Api/CicApiController.php', import.meta.url), 'utf8');

assert.match(css, /\.ps-cic-rating-modal \.modal-header\s*\{[^}]*background:\s*var\(--ds-active-bg-page\)/s);
assert.match(css, /\.ps-cic-rating-modal \.cuadroModal\s*\{[^}]*padding:\s*0\s*!important/s);
assert.match(css, /\.ps-cic-rating-modal \.parametro_cic\s*\{[^}]*border-radius:\s*var\(--ds-radius-card\)[^}]*overflow:\s*hidden/s);
assert.match(css, /\.ps-cic-rating-modal fieldset\.pregunta\s*\{[^}]*background:\s*transparent[^}]*width:\s*100%[^}]*border-radius:\s*0/s);
assert.match(css, /\.ps-cic-rating-modal fieldset\.pregunta:last-child\s*\{[^}]*border-bottom:\s*0/s);
assert.match(css, /\.ps-cic-rating-modal \.modal-body > \.row\s*\{[^}]*margin-inline:\s*0/s);
assert.match(css, /\.ps-cic-rating-modal \.form_eval\s*\{[^}]*background:\s*var\(--ds-active-surface-raised\)/s);
assert.match(css, /\.ps-cic-rating-modal \.close\s*\{[^}]*margin:\s*0/s);
assert.doesNotMatch(view, /<p class="modal-body-texto-cic_(?:mdo|si)"/);
assert.match(css, /:is\(#modalcic_mdo\.ps-cic-rating-modal,\s*#modalcic_si\.ps-cic-rating-modal\) \.modal-header\s*\{[^}]*background:\s*var\(--ds-active-bg-page\)/s);
assert.match(css, /\.ps-cic-rating-modal \.modal-content\s*\{[^}]*background:\s*var\(--ds-active-bg-page\)/s);
assert.match(css, /:is\(#modalcic_mdo\.ps-cic-rating-modal,\s*#modalcic_si\.ps-cic-rating-modal\) :is\(\.modal-content, \.modal-body, \.modal-footer\)\s*\{[^}]*background:\s*var\(--ds-active-bg-page\)/s);
assert.match(css, /@media \(max-width: 767px\)[\s\S]*?\.ps-cic-rating-modal \.modal-body\s*\{[^}]*padding:\s*var\(--spacing-xs\)\s*!important/s);
assert.match(css, /\.ps-cic-rating-modal \.parametro_cic\s*\{[^}]*box-sizing:\s*border-box[^}]*border-radius:\s*var\(--ds-radius-card\)/s);
assert.match(controller, /updateMetrics[\s\S]*?beginTransaction\(\)[\s\S]*?commit\(\)[\s\S]*?rollBack\(\)/);

console.log('CIC modal style contract OK');
