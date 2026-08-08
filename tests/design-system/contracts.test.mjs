import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { cpSync, existsSync, mkdirSync, mkdtempSync, readFileSync, rmSync, symlinkSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import test from 'node:test';
import { createHash } from 'node:crypto';
import { deflateSync } from 'node:zlib';
import { referencedTestFiles, requiredViewports as homologationViewports } from './manifest-sources.mjs';

/**
 * PNG valido y minimo de las dimensiones pedidas, en memoria. Existe para poder
 * fabricar dentro de un fixture un golden con dimensiones arbitrarias sin
 * escribir ningun binario en el repositorio.
 */
function syntheticPng(width, height) {
  const crcTable = Array.from({ length: 256 }, (_, n) => {
    let c = n;
    for (let k = 0; k < 8; k += 1) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
    return c >>> 0;
  });
  const crc32 = (buffer) => {
    let c = 0xffffffff;
    for (const byte of buffer) c = crcTable[(c ^ byte) & 0xff] ^ (c >>> 8);
    return (c ^ 0xffffffff) >>> 0;
  };
  const chunk = (type, data) => {
    const head = Buffer.alloc(4);
    head.writeUInt32BE(data.length);
    const body = Buffer.concat([Buffer.from(type, 'ascii'), data]);
    const crc = Buffer.alloc(4);
    crc.writeUInt32BE(crc32(body));
    return Buffer.concat([head, body, crc]);
  };
  const ihdr = Buffer.alloc(13);
  ihdr.writeUInt32BE(width, 0);
  ihdr.writeUInt32BE(height, 4);
  ihdr[8] = 8; // bit depth
  ihdr[9] = 0; // greyscale
  const raw = Buffer.alloc((width + 1) * height); // filtro 0 + scanlines en negro
  return Buffer.concat([
    Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]),
    chunk('IHDR', ihdr),
    chunk('IDAT', deflateSync(raw)),
    chunk('IEND', Buffer.alloc(0)),
  ]);
}

const root = path.resolve(import.meta.dirname, '../..');
const componentContractFields = [
  'id', 'family', 'kind', 'maturity', 'visualApproval', 'purpose', 'doNotUseFor', 'api', 'markup',
  'variants', 'states', 'densities', 'tokens', 'responsive', 'accessibility',
  'testSelector', 'consumers', 'replacement', 'golden',
];

/**
 * `copyScreenshots` copia los 39 goldens reales (6,8 MB) en vez de enlazarlos.
 * Solo hace falta cuando la mutacion necesita *escribir* dentro del directorio
 * de goldens -- sustituir un golden, o fabricar uno nuevo ahi -- porque desde
 * que el gate exige que todo `golden` viva bajo `tests/browser/__screenshots__/`
 * ya no se puede fabricar evidencia sintetica en cualquier otra carpeta del
 * fixture. Escribir a traves del symlink tocaria los PNG reales del repo, que es
 * justo lo que ninguna prueba puede hacer.
 */
function runFixture(mutate, { copyScreenshots = false } = {}) {
  const fixtureRoot = mkdtempSync(path.join(tmpdir(), 'aia-ds-contract-'));
  cpSync(path.join(root, 'docs/design-system'), path.join(fixtureRoot, 'docs/design-system'), {
    recursive: true,
  });
  cpSync(
    path.join(root, 'goals/design-system-nucleo-gobernanza'),
    path.join(fixtureRoot, 'goals/design-system-nucleo-gobernanza'),
    { recursive: true },
  );
  symlinkSync(path.join(root, 'public'), path.join(fixtureRoot, 'public'), 'dir');
  symlinkSync(path.join(root, 'views'), path.join(fixtureRoot, 'views'), 'dir');
  symlinkSync(path.join(root, 'database'), path.join(fixtureRoot, 'database'), 'dir');
  symlinkSync(path.join(root, 'pdc-app'), path.join(fixtureRoot, 'pdc-app'), 'dir');
  for (const file of referencedTestFiles()) {
    const source = path.join(root, file);
    if (!existsSync(source)) continue;
    mkdirSync(path.dirname(path.join(fixtureRoot, file)), { recursive: true });
    cpSync(source, path.join(fixtureRoot, file));
  }
  if (copyScreenshots) {
    cpSync(
      path.join(root, 'tests/browser/__screenshots__'),
      path.join(fixtureRoot, 'tests/browser/__screenshots__'),
      { recursive: true },
    );
  } else {
    symlinkSync(
      path.join(root, 'tests/browser/__screenshots__'),
      path.join(fixtureRoot, 'tests/browser/__screenshots__'),
      'dir',
    );
  }
  mutate(fixtureRoot);
  const result = spawnSync(process.execPath, [path.join(root, 'scripts/design-system-contracts.mjs')], {
    cwd: fixtureRoot,
    encoding: 'utf8',
    // No enlazamos .git dentro del fixture: git deduce el worktree como el
    // directorio padre de .git, y sin `core.worktree` explicito cualquier
    // `git status` que corra el gate refrescaria el indice real (compartido con
    // el repo) contra el subconjunto de archivos del fixture. GIT_DIR/GIT_WORK_TREE
    // le dicen a git donde estan los objetos y donde esta el worktree real, sin
    // mentirle sobre cual es cual.
    env: { ...process.env, GIT_DIR: path.join(root, '.git'), GIT_WORK_TREE: root },
  });
  rmSync(fixtureRoot, { recursive: true, force: true });
  return result;
}

test('un fixture sin mutar pasa el gate', () => {
  const result = runFixture(() => {});
  assert.equal(result.status, 0, result.stderr || result.stdout);
});

test('canonical design-system contracts pass the executable gate', () => {
  const result = spawnSync(process.execPath, ['scripts/design-system-contracts.mjs'], {
    cwd: root,
    encoding: 'utf8',
  });

  assert.equal(result.status, 0, result.stderr || result.stdout);
  assert.match(result.stdout, /Design system contracts: PASS/);
});

test('homologation covers every governed visual family', () => {
  const contract = JSON.parse(readFileSync(
    path.join(root, 'docs/design-system/homologation.json'), 'utf8',
  ));
  assert.equal(contract.families.length, 10);
  for (const family of contract.families) {
    assert.match(family.label, /^[A-ZÁÉÍÓÚÑ]/, `${family.id} needs a human label`);
    assert.ok(family.description?.length >= 24, `${family.id} needs a description`);
    assert.ok(family.candidates.length >= 1, family.id);
    assert.deepEqual(family.themes, ['dark'], family.id);
    const requiredViewports = ['1180x820', '1440x900'];
    const supportedViewports = [...requiredViewports, '390x844'];
    for (const viewport of requiredViewports) {
      assert.ok(
        family.viewports.includes(viewport),
        `familia ${family.id} no cubre ${viewport}`,
      );
    }
    for (const viewport of family.viewports) {
      assert.ok(
        supportedViewports.includes(viewport),
        `familia ${family.id} declara el viewport no soportado ${viewport}`,
      );
    }
  }
});

test('homologation cannot omit a governed visual family', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/homologation.json');
    const contract = JSON.parse(readFileSync(file, 'utf8'));
    contract.families = contract.families.filter((family) => family.id !== 'states-feedback');
    writeFileSync(file, `${JSON.stringify(contract, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /homologation: missing family states-feedback/);
});

test('homologation test references must exist', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/homologation.json');
    const contract = JSON.parse(readFileSync(file, 'utf8'));
    contract.tests.push('tests/browser/missing-lab.mjs');
    writeFileSync(file, `${JSON.stringify(contract, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /homologation: missing test tests\/browser\/missing-lab\.mjs/);
});

test('an active homologation candidate must exist in its family contract', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/homologation.json');
    const contract = JSON.parse(readFileSync(file, 'utf8'));
    const foundations = contract.families.find(({ id }) => id === 'foundations');
    foundations.activeCandidate = 'missing-foundations-candidate';
    writeFileSync(file, `${JSON.stringify(contract, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /foundations: active candidate missing-foundations-candidate is not declared/);
});

test('a family candidate cannot be approved without recorded evidence', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/family-approvals.json');
    const approvals = JSON.parse(readFileSync(file, 'utf8'));
    approvals.approvals = approvals.approvals.filter(({ familyId }) => familyId !== 'shell-navigation');
    writeFileSync(file, `${JSON.stringify(approvals, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /shell-navigation\/adaptive-shell: approved without approval evidence/);
});

test('duplicate component IDs fail the executable gate', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/component-catalog.json');
    const catalog = JSON.parse(readFileSync(file, 'utf8'));
    catalog.components.push({ ...catalog.components[0] });
    writeFileSync(file, `${JSON.stringify(catalog, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /duplicate component id: shell/);
});

test('component maturity is mandatory and independent from visual approval', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/component-catalog.json');
    const catalog = JSON.parse(readFileSync(file, 'utf8'));
    delete catalog.components[0].maturity;
    catalog.components[1].maturity = 'approved';
    catalog.components[2].visualApproval = {
      status: 'pending',
      familyId: 'actions',
      candidateId: 'theme-adaptive-primary',
    };
    writeFileSync(file, `${JSON.stringify(catalog, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /shell: missing maturity/);
  assert.match(result.stderr, /page-header: invalid maturity approved/);
  assert.doesNotMatch(result.stderr, /toolbar: stable component cannot be pending/);
});

test('vendor adapter maturity uses the adapter taxonomy', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/vendors.json');
    const vendors = JSON.parse(readFileSync(file, 'utf8'));
    vendors.vendors.find(({ id }) => id === 'handsontable').adapterMaturity = 'stable';
    delete vendors.vendors.find(({ id }) => id === 'select2').adapterMaturity;
    writeFileSync(file, `${JSON.stringify(vendors, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /handsontable: invalid adapter maturity stable/);
  assert.match(result.stderr, /select2: missing adapter maturity/);
});

test('canonical goal provenance rejects stale hashes and instructional history', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/goal-provenance.json');
    const provenance = existsSync(file)
      ? JSON.parse(readFileSync(file, 'utf8'))
      : {
          schemaVersion: 1,
          designSystemVersion: '0.3.6',
          goalId: 'design-system-nucleo-gobernanza',
          sourceCommit: '054f395f8f842e7ca20900956761becbfaaba86c',
          canonicalSources: [
            {
              path: 'goals/design-system-nucleo-gobernanza/goal.md',
              sha256: '0'.repeat(64),
            },
          ],
          historicalSources: [
            {
              path: 'goals/design-system-nucleo-gobernanza/interview.json',
              sha256: '0'.repeat(64),
              status: 'superseded',
              instructional: true,
            },
          ],
        };
    provenance.canonicalSources[0].sha256 = '0'.repeat(64);
    provenance.historicalSources[0].instructional = true;
    writeFileSync(file, `${JSON.stringify(provenance, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /goal provenance: hash mismatch .*goal\.md/);
  assert.match(result.stderr, /goal provenance: historical source .* must be superseded and non-instructional/);
});

test('unknown manifest component references fail the executable gate', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/programa-general.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    manifest.components.push('unknown-component');
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /programa-general: unknown component unknown-component/);
});

test('unknown manifest vendor references fail the executable gate', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/programa-general.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    manifest.vendors.push('unknown-vendor');
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /programa-general: unknown vendor unknown-vendor/);
});

test('manifest inventory cannot omit an existing manifest', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/inventory.json');
    const inventory = JSON.parse(readFileSync(file, 'utf8'));
    inventory.manifests = [];
    writeFileSync(file, `${JSON.stringify(inventory, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /inventory: missing manifest programa-general.json/);
});

test('manifests declare the complete deterministic visual matrix', () => {
  const inventory = JSON.parse(readFileSync(
    path.join(root, 'docs/design-system/manifests/inventory.json'), 'utf8',
  ));
  assert.deepEqual(inventory.manifests.sort(), ['auth.json', 'bi-runtime.json', 'control-cambios.json', 'escalamientos.json', 'foundation-shell.json', 'indicadores.json', 'laboratory.json', 'plan-compras-v2.json', 'profesionales.json', 'programa-general-actualizar.json', 'programa-general.json', 'programacion-intermedia.json', 'programacion-semanal.json', 'project-selector.json', 'subcontratistas.json']);

  const laboratory = JSON.parse(readFileSync(
    path.join(root, 'docs/design-system/manifests/laboratory.json'), 'utf8',
  ));
  const pilot = JSON.parse(readFileSync(
    path.join(root, 'docs/design-system/manifests/programa-general.json'), 'utf8',
  ));
  // Las dos cifras se derivan de homologation.json, no se escriben a mano: si
  // manana una familia declara un viewport mas, el esperado se mueve solo y
  // el manifiesto que no lo cubra es el que falla.
  const homologation = JSON.parse(readFileSync(
    path.join(root, 'docs/design-system/homologation.json'), 'utf8',
  ));
  const viewportsOf = (familyId) => homologation.families
    .find(({ id }) => id === familyId)?.viewports || [];
  const laboratoryFamilies = [...new Set(laboratory.scenarios.map(({ family }) => family))];
  assert.equal(
    laboratory.scenarios.length,
    laboratoryFamilies.reduce((total, family) => total + viewportsOf(family).length, 0),
  );
  // El piloto no declara familia (family: null), asi que su matriz esperada es
  // la union de viewports exigida por homologation, la misma que aplica el
  // gate en REQUIRED_VIEWPORTS.
  assert.equal(pilot.scenarios.length, homologationViewports().length);
  assert.deepEqual(
    [...new Set(pilot.scenarios.map(({ viewport }) => `${viewport.width}x${viewport.height}`))].sort(),
    [...homologationViewports()].sort(),
  );
});

test('golden checksums fail closed when a declared baseline changes', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/programa-general.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    manifest.scenarios[0].sha256 = '0'.repeat(64);
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /programa-general\/.*: golden hash mismatch/);
});

test('manifest sources and scenario matrices fail closed when stale', () => {
  const result = runFixture((fixtureRoot) => {
    const labFile = path.join(fixtureRoot, 'docs/design-system/manifests/laboratory.json');
    const laboratory = JSON.parse(readFileSync(labFile, 'utf8'));
    laboratory.sources.push('views/design-system/missing-specimen.php');
    writeFileSync(labFile, `${JSON.stringify(laboratory, null, 2)}\n`);

    const pilotFile = path.join(fixtureRoot, 'docs/design-system/manifests/programa-general.json');
    const pilot = JSON.parse(readFileSync(pilotFile, 'utf8'));
    pilot.scenarios = pilot.scenarios.filter(({ id }) => id !== 'programa-general-dark-1440x900');
    writeFileSync(pilotFile, `${JSON.stringify(pilot, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /laboratory: missing source views\/design-system\/missing-specimen\.php/);
  assert.match(result.stderr, /programa-general: missing scenario dark\/1440x900/);
});

test('manifest test references must exist', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/programa-general.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    manifest.tests.push('tests/missing-contract.mjs');
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /programa-general: missing test tests\/missing-contract.mjs/);
});

test('schemas must reject undeclared top-level properties', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/module-manifest.schema.json');
    const schema = JSON.parse(readFileSync(file, 'utf8'));
    schema.additionalProperties = true;
    writeFileSync(file, `${JSON.stringify(schema, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /module-manifest.schema.json: additionalProperties must be false/);
});

test('accessibility governance files are mandatory contracts', () => {
  const result = runFixture((fixtureRoot) => {
    rmSync(path.join(fixtureRoot, 'docs/design-system/a11y-baseline.json'));
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /a11y-baseline.json: missing/);
});

test('every catalog entry exposes the complete component contract', () => {
  const catalog = JSON.parse(readFileSync(
    path.join(root, 'docs/design-system/component-catalog.json'), 'utf8',
  ));
  for (const component of catalog.components) {
    assert.deepEqual(Object.keys(component).sort(), [...componentContractFields].sort(), component.id);
  }
});

test('legacy aliases must resolve to catalog entries', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/legacy-aliases.json');
    const aliases = JSON.parse(readFileSync(file, 'utf8'));
    aliases.aliases[0].catalogId = 'unknown-component';
    writeFileSync(file, `${JSON.stringify(aliases, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /legacy alias body.dark-mode: unknown component unknown-component/);
});

test('declared local vendor assets must exist', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/vendors.json');
    const vendors = JSON.parse(readFileSync(file, 'utf8'));
    vendors.vendors.find((vendor) => vendor.id === 'bootstrap').assets.push('public/vendor/missing.css');
    writeFileSync(file, `${JSON.stringify(vendors, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /bootstrap: missing vendor asset public\/vendor\/missing.css/);
});

test('declared vendor hashes must match local assets', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/vendors.json');
    const vendors = JSON.parse(readFileSync(file, 'utf8'));
    const fonts = vendors.vendors.find((vendor) => vendor.id === 'aia-fonts');
    fonts.sha256['inter-latin-v20.woff2'] = '0'.repeat(64);
    writeFileSync(file, `${JSON.stringify(vendors, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /aia-fonts: hash mismatch inter-latin-v20\.woff2/);
});

test('pilot manifest must keep every governance field', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/programa-general.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    delete manifest.roles;
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /programa-general: missing required field roles/);
});

test('pilot manifest routes must exist in the front controller', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/programa-general.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    manifest.routes.push('/missing-design-system-route');
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /programa-general: route not registered \/missing-design-system-route/);
});

// Comparacion diferencial en vez de `status 0`: aunque el fixture ya pasa limpio sin
// mutar, esta prueba compara la lista completa de fallos entre linea base y mutacion
// para blindarse ante cualquier regresion futura del harness. Lo que importa es que
// declarar 390x844 no anada ni un fallo respecto de la linea base.
test('declarar el viewport movil no anade ningun fallo al gate', () => {
  const baseline = runFixture(() => {});
  const withMobile = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/homologation.json');
    const contract = JSON.parse(readFileSync(file, 'utf8'));
    const foundations = contract.families.find(({ id }) => id === 'foundations');
    foundations.viewports = ['1180x820', '1440x900', '390x844'];
    writeFileSync(file, `${JSON.stringify(contract, null, 2)}\n`);
  });

  const failures = (result) => (result.stderr || '')
    .split('\n')
    .filter((line) => line.startsWith('- '));

  assert.deepEqual(
    failures(withMobile), failures(baseline),
    'declarar 390x844 cambio el resultado del gate',
  );
  assert.equal(
    failures(baseline).some((line) => line.includes('390x844')), false,
    'la linea base no deberia mencionar el viewport movil',
  );
});

test('una familia no puede declarar un viewport no soportado', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/homologation.json');
    const contract = JSON.parse(readFileSync(file, 'utf8'));
    const foundations = contract.families.find(({ id }) => id === 'foundations');
    foundations.viewports = ['1180x820', '1440x900', '800x600'];
    writeFileSync(file, `${JSON.stringify(contract, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /foundations: unsupported viewport 800x600/);
});

test('un golden que no corresponde al viewport del escenario falla', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/programa-general.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    const target = manifest.scenarios.find((s) => s.viewport.width === 1180);
    const donor = manifest.scenarios.find((s) => s.viewport.width === 1440);
    target.golden = donor.golden;
    target.sha256 = donor.sha256;
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /golden does not match theme\/viewport/);
});

test('dos escenarios no pueden compartir el mismo golden', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/programa-general.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    const [first, second] = manifest.scenarios;
    second.id = `${second.id}-copia`;
    second.viewport = { ...first.viewport };
    second.golden = first.golden;
    second.sha256 = first.sha256;
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /golden reused by another scenario/);
});

test('una familia no puede dejar de declarar un viewport requerido', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/homologation.json');
    const contract = JSON.parse(readFileSync(file, 'utf8'));
    const foundations = contract.families.find(({ id }) => id === 'foundations');
    foundations.viewports = ['1180x820'];
    writeFileSync(file, `${JSON.stringify(contract, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /foundations: missing required viewport 1440x900/);
});

// Candado contra el propio hallazgo de esta tarea: la lista de manifiestos que
// el gate valida linea por linea se deriva de docs/design-system/manifests/inventory.json,
// no de una lista escrita a mano. subcontratistas.json a proposito NO estaba
// cubierto antes de esa derivacion (foundation-shell.json declaraba /contratos,
// /listado-actividades y /pdc, del PDC v1 ya retirado, y nadie lo vio porque el
// gate no miraba ese archivo). Esta prueba rompe subcontratistas.json de una
// forma que solo se detecta si el gate de verdad lo procesa; si la lista de
// manifiestos volviera a encogerse a mano, esta prueba fallaria (result.status
// !== 0, sin el mensaje esperado sobre subcontratistas.json) y lo delataria.
test('un golden mas estrecho que su viewport falla si la captura es de pantalla completa', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/programa-general.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    const scenario = manifest.scenarios.find((s) => s.viewport.width === 1180);
    // Solo se cambia el viewport declarado, sin tocar `golden`/`id`: si tambien
    // se renombraran a los del escenario 1440x900 real, la ruta colisionaria
    // con el golden ya existente y correctamente dimensionado de ese otro
    // escenario, y el gate nunca veria el golden real de 1180x820 px contra un
    // viewport declarado de 1440x900 -- que es justo el caso que se quiere
    // reproducir aqui.
    scenario.viewport = { width: 1440, height: 900 };
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /golden mide 1180x820 px, no coincide con el viewport declarado 1440x900/);
});

test('un recorte a elemento declarado no exige coincidencia exacta', () => {
  const result = runFixture(() => {});
  assert.equal(result.status, 0, result.stderr || result.stdout);
  assert.equal(/states-feedback.*golden mide/.test(result.stderr || ''), false);
});

// `capture: "element"` levanta el limite de alto del golden. Las dos pruebas
// siguientes ejercitan la lista blanca que impide que ese levantamiento sea
// auto-servicio: la primera, con un escenario que simplemente se declara
// "element"; la segunda, con el vector real -- reclamar el id de un escenario
// autorizado desde otro modulo, que es lo que dejaba pasar indexar la lista
// blanca solo por `scenario.id`.
test('un escenario fuera de la lista blanca no puede declarar capture "element"', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/programa-general.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    manifest.scenarios.find((s) => s.viewport.width === 1180).capture = 'element';
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(
    result.stderr,
    /programa-general\/programa-general-dark-1180x820: capture "element" no esta en la lista blanca/,
  );
});

test('un modulo no puede heredar la excepcion reclamando el id de un escenario autorizado', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/programa-general.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    const scenario = manifest.scenarios.find((s) => s.viewport.width === 1180);
    // Un PNG sintetico de 390x844: mas bajo que el viewport declarado, algo que
    // solo "element" tolera. Vive dentro del fixture, nunca en el repo -- por eso
    // el fixture copia los goldens en vez de enlazarlos: desde que el gate exige
    // que todo golden viva bajo tests/browser/__screenshots__/, fabricarlo en
    // cualquier otra carpeta lo haria fallar por la regla equivocada.
    const golden = 'tests/browser/__screenshots__/fixture-element-allowlist-dark-1180x820.png';
    const png = syntheticPng(390, 844);
    const goldenPath = path.join(fixtureRoot, golden);
    mkdirSync(path.dirname(goldenPath), { recursive: true });
    writeFileSync(goldenPath, png);
    scenario.id = 'states-feedback-dark-1180x820';
    scenario.capture = 'element';
    scenario.golden = golden;
    scenario.sha256 = createHash('sha256').update(png).digest('hex');
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  }, { copyScreenshots: true });

  assert.notEqual(result.status, 0);
  assert.match(
    result.stderr,
    /programa-general\/states-feedback-dark-1180x820: capture "element" no esta en la lista blanca/,
  );
});

test('el gate valida todos los manifiestos declarados en el inventario, no solo algunos', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/subcontratistas.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    manifest.routes.push('/subcontratistas/ruta-inexistente');
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /subcontratistas: route not registered \/subcontratistas\/ruta-inexistente/);
});

test('todo manifiesto del inventario pasa por el chequeo de version', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/subcontratistas.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    manifest.designSystemVersion = '9.9.9';
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /subcontratistas\.json: designSystemVersion must equal/);
});

// `moduleId` no era unico ni estaba atado al nombre del archivo: un manifiesto
// intruso podia declarar el `moduleId` de otro (p.ej. "laboratory") y colarse
// en el inventario. Sin control de unicidad, `manifests.find(...)` (que
// resuelve `laboratoryManifest`/`programManifest`) toma el primer match, asi
// que un intruso insertado *antes* del legitimo lo vuelve invisible para la
// cobertura de familias mientras sigue aportando escenarios -- incluida la
// lista blanca de `capture: "element"`, que indexa por `moduleId/scenarioId`.
// Reproducido en la re-revision de F2a-2a con un `rogue.json` copiado de
// laboratory.json con un escenario `capture: "element"` y un PNG de 390x844
// declarado bajo 1180x820: sin esta prueba, el gate pasaba en verde.
test('dos manifiestos con el mismo moduleId hacen fallar el gate, sin importar el orden', () => {
  const mutateDuplicating = (insertBeforeLaboratory) => (fixtureRoot) => {
    const inventoryFile = path.join(fixtureRoot, 'docs/design-system/manifests/inventory.json');
    const inventory = JSON.parse(readFileSync(inventoryFile, 'utf8'));
    const laboratoryFile = path.join(fixtureRoot, 'docs/design-system/manifests/laboratory.json');
    const rogue = JSON.parse(readFileSync(laboratoryFile, 'utf8'));
    // El intruso reutiliza el `moduleId` "laboratory" pero es un manifiesto
    // distinto (sin escenarios): lo unico que importa para esta prueba es que
    // el `moduleId` colisiona, no que el contenido sea identico.
    rogue.scenarios = [];
    const rogueFile = path.join(fixtureRoot, 'docs/design-system/manifests/rogue.json');
    writeFileSync(rogueFile, `${JSON.stringify(rogue, null, 2)}\n`);
    const labIndex = inventory.manifests.indexOf('laboratory.json');
    inventory.manifests.splice(insertBeforeLaboratory ? labIndex : labIndex + 1, 0, 'rogue.json');
    writeFileSync(inventoryFile, `${JSON.stringify(inventory, null, 2)}\n`);
  };

  for (const insertBeforeLaboratory of [false, true]) {
    const result = runFixture(mutateDuplicating(insertBeforeLaboratory));
    assert.notEqual(result.status, 0, `insertBeforeLaboratory=${insertBeforeLaboratory}`);
    assert.match(result.stderr, /duplicate module manifest moduleId: laboratory/);
  }
});

// ---------------------------------------------------------------------------
// Los cuatro candados de la re-revision de F2a-2a. Todos nacen de la misma
// raiz: el gate leia `manifestSchema.required` para comprobar presencia de
// campos y nunca aplicaba nada mas del esquema, y `golden` / `moduleId` /
// la lista blanca de `capture: "element"` ataban el nombre pero no el
// contenido. Cada bloque reproduce el vector con el que el revisor paso el
// gate en verde.
// ---------------------------------------------------------------------------

// R1. `theme` solo admite "dark" en el esquema, pero el gate nunca aplicaba el
// enum: un escenario con `theme: "linen"` y su golden nombrado en consecuencia
// pasaba en verde, metiendo por la puerta de la evidencia un tema prohibido por
// contrato (DS-030). linen-removal.test.mjs no lo atrapa: su lista fija cubre
// esquemas y hojas de estilo, no los manifiestos.
test('un escenario no puede declarar un tema fuera del enum del esquema', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/auth.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    const scenario = manifest.scenarios[0];
    scenario.theme = 'linen';
    // El golden se renombra al tema nuevo para que el chequeo de sufijo no sea
    // el que dispare: lo que debe fallar es el enum, no el nombre del archivo.
    const golden = 'tests/browser/__screenshots__/auth/login-linen-1180x820.png';
    const png = syntheticPng(1180, 820);
    mkdirSync(path.dirname(path.join(fixtureRoot, golden)), { recursive: true });
    writeFileSync(path.join(fixtureRoot, golden), png);
    scenario.golden = golden;
    scenario.sha256 = createHash('sha256').update(png).digest('hex');
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  }, { copyScreenshots: true });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /auth: scenarios\[0\]\.theme: valor "linen" fuera del enum del esquema/);
});

// R1 (bis). `additionalProperties: false` estaba escrito en el esquema y no se
// aplicaba: cualquier propiedad inventada, en el manifiesto o en un escenario,
// pasaba en verde.
test('propiedades no declaradas en el esquema hacen fallar el gate', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/auth.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    manifest.propiedadInventada = 'hola';
    manifest.scenarios[0].otraInventada = 42;
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /auth: \(raiz\): propiedad no declarada en el esquema: propiedadInventada/);
  assert.match(result.stderr, /auth: scenarios\[0\]: propiedad no declarada en el esquema: otraInventada/);
});

// R1 (ter). El enum de `capture` vivia solo en el esquema: un valor invalido no
// coincidia con "element", caia en la rama por defecto y el escenario pasaba con
// el chequeo de dimensiones exactas, que su golden cumplia.
test('un valor de capture fuera del enum del esquema no cae en la rama por defecto', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/auth.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    manifest.scenarios[0].capture = 'elemento';
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(
    result.stderr,
    /auth: scenarios\[0\]\.capture: valor "elemento" fuera del enum del esquema \("viewport", "element"\)/,
  );
});

// R2. La lista blanca de `capture: "element"` protegia el quien pero no el que:
// el escenario autorizado podia presentar cualquier PNG que cupiera a lo ancho.
// Sustituir el golden de states-feedback-dark-1180x820 (1102x1649 reales) por
// uno de 390x844 pasaba en verde -- el agujero original, abierto *dentro* de la
// lista blanca.
test('un escenario autorizado a capture "element" no puede cambiar las dimensiones de su recorte', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/laboratory.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    const scenario = manifest.scenarios.find(({ id }) => id === 'states-feedback-dark-1180x820');
    const png = syntheticPng(390, 844);
    writeFileSync(path.join(fixtureRoot, scenario.golden), png);
    scenario.sha256 = createHash('sha256').update(png).digest('hex');
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  }, { copyScreenshots: true });

  assert.notEqual(result.status, 0);
  assert.match(
    result.stderr,
    /laboratory\/states-feedback-dark-1180x820: golden mide 390x844 px, pero la lista blanca de capture "element" declara 1102x1649/,
  );
});

// R3. `golden` era una ruta libre desde la raiz del repositorio: bastaba con
// dejar un PNG suelto con el nombre y las dimensiones correctas para que el
// escenario lo presentara como evidencia. El sha256 solo lo ataba al archivo que
// el propio manifiesto habia elegido.
test('un golden fuera del directorio de la suite de navegador falla', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/auth.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    const scenario = manifest.scenarios[0];
    const golden = 'golden-suelto-dark-1180x820.png';
    const png = syntheticPng(1180, 820);
    writeFileSync(path.join(fixtureRoot, golden), png);
    scenario.golden = golden;
    scenario.sha256 = createHash('sha256').update(png).digest('hex');
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(
    result.stderr,
    /auth\/auth-login-dark-1180x820: golden golden-suelto-dark-1180x820\.png esta fuera de los directorios de evidencia permitidos/,
  );
});

// R3 (bis). El prefijo por si solo dejaria pasar una travesia con `..`.
test('un golden que escapa del directorio permitido con .. falla', () => {
  const result = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/auth.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    const scenario = manifest.scenarios[0];
    const png = syntheticPng(1180, 820);
    writeFileSync(path.join(fixtureRoot, 'fuga-dark-1180x820.png'), png);
    scenario.golden = 'tests/browser/__screenshots__/../../../fuga-dark-1180x820.png';
    scenario.sha256 = createHash('sha256').update(png).digest('hex');
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  });

  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /auth\/auth-login-dark-1180x820: golden .* esta fuera de los directorios de evidencia permitidos/);
});

// R4. `moduleId` era unico pero no estaba atado a nada. Los dos vectores que
// pasaban en verde: renombrar laboratory.json conservando su moduleId, y que
// auth.json declarara `moduleId: "no-soy-auth"` mientras el inventario lo llama
// auth. Se ata al nombre del archivo, la unica correspondencia que hoy cumplen
// los 15 manifiestos (ver el comentario del gate para por que no se ato a
// inventory.modules[].moduleId).
test('el moduleId de un manifiesto debe corresponder con el nombre de su archivo', () => {
  const renombrado = runFixture((fixtureRoot) => {
    const dir = path.join(fixtureRoot, 'docs/design-system/manifests');
    const laboratory = readFileSync(path.join(dir, 'laboratory.json'), 'utf8');
    writeFileSync(path.join(dir, 'otro-nombre.json'), laboratory);
    rmSync(path.join(dir, 'laboratory.json'));
    const inventoryFile = path.join(dir, 'inventory.json');
    const inventory = JSON.parse(readFileSync(inventoryFile, 'utf8'));
    inventory.manifests = inventory.manifests
      .map((name) => (name === 'laboratory.json' ? 'otro-nombre.json' : name));
    writeFileSync(inventoryFile, `${JSON.stringify(inventory, null, 2)}\n`);
  });

  assert.notEqual(renombrado.status, 0);
  assert.match(
    renombrado.stderr,
    /otro-nombre\.json: moduleId declara "laboratory" pero debe ser "otro-nombre"/,
  );

  const suplantado = runFixture((fixtureRoot) => {
    const file = path.join(fixtureRoot, 'docs/design-system/manifests/auth.json');
    const manifest = JSON.parse(readFileSync(file, 'utf8'));
    manifest.moduleId = 'no-soy-auth';
    writeFileSync(file, `${JSON.stringify(manifest, null, 2)}\n`);
  });

  assert.notEqual(suplantado.status, 0);
  assert.match(suplantado.stderr, /auth\.json: moduleId declara "no-soy-auth" pero debe ser "auth"/);
});
