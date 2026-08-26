import assert from 'node:assert/strict';
import { readFileSync, readdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { join } from 'node:path';
import test from 'node:test';

// Por que existe: hay DOS inventarios del mismo sistema y ningun gate los cruzaba.
// El censo (`censo-modulos.json`) enumera modulos y sus superficies; las fichas
// (`manifests/*.json`) declaran que cubre el sistema de diseno. `coverage-closure`
// cruza RUTAS contra fichas — otro eje — asi que un censo que apunte a una ficha
// inexistente pasaba desapercibido. Medido el 2026-08-26: dos enlaces rotos
// (`projects`, `admin`) llevaban semanas sin que nada se pusiera rojo.

const REPO = fileURLToPath(new URL('../../', import.meta.url));
const leer = (p) => JSON.parse(readFileSync(p, 'utf8'));
const censo = leer(join(REPO, 'docs/design-system/auditoria/censo-modulos.json')).modulos;
const fichas = new Set(
  readdirSync(join(REPO, 'docs/design-system/manifests'))
    .filter((f) => f.endsWith('.json'))
    .map((f) => f.slice(0, -5)),
);

test('todo moduleId declarado en el censo apunta a una ficha que existe', () => {
  const rotos = censo
    .map((m) => [m.slug, m.designSystem?.moduleId])
    .filter(([, id]) => id && !fichas.has(id));
  assert.deepEqual(rotos, [], `enlaces rotos censo -> ficha: ${JSON.stringify(rotos)}`);
});

test('todo modulo con superficies declara su moduleId o su exencion', () => {
  const huerfanos = censo
    .filter((m) => (m.superficies?.length ?? 0) > 0)
    .filter((m) => !m.designSystem?.moduleId && !m.designSystem?.sinFicha)
    .map((m) => m.slug);
  assert.deepEqual(huerfanos, [], `modulos con pantalla y sin ficha ni exencion: ${huerfanos}`);
});

test('un modulo exento declara por que', () => {
  const mudos = censo
    .filter((m) => m.designSystem?.sinFicha)
    .filter((m) => !m.designSystem?.porQue)
    .map((m) => m.slug);
  assert.deepEqual(mudos, [], `exenciones sin motivo escrito: ${mudos}`);
});
