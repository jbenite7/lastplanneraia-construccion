import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { cpSync, existsSync, mkdirSync, mkdtempSync, readFileSync, rmSync, symlinkSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import test from 'node:test';

const root = path.resolve(import.meta.dirname, '../..');
const componentContractFields = [
  'id', 'family', 'kind', 'maturity', 'visualApproval', 'purpose', 'doNotUseFor', 'api', 'markup',
  'variants', 'states', 'densities', 'tokens', 'responsive', 'accessibility',
  'testSelector', 'consumers', 'replacement', 'golden',
];

function contractTestFiles() {
  const dsRoot = path.join(root, 'docs/design-system');
  const homologation = JSON.parse(readFileSync(path.join(dsRoot, 'homologation.json'), 'utf8'));
  const inventory = JSON.parse(readFileSync(path.join(dsRoot, 'manifests/inventory.json'), 'utf8'));
  const files = new Set(homologation.tests || []);
  for (const name of inventory.manifests) {
    const manifest = JSON.parse(readFileSync(path.join(dsRoot, 'manifests', name), 'utf8'));
    for (const file of manifest.tests || []) files.add(file);
  }
  return [...files];
}

function runFixture(mutate) {
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
  for (const file of contractTestFiles()) {
    const source = path.join(root, file);
    if (!existsSync(source)) continue;
    mkdirSync(path.dirname(path.join(fixtureRoot, file)), { recursive: true });
    cpSync(source, path.join(fixtureRoot, file));
  }
  symlinkSync(
    path.join(root, 'tests/browser/__screenshots__'),
    path.join(fixtureRoot, 'tests/browser/__screenshots__'),
    'dir',
  );
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
  assert.equal(laboratory.scenarios.length, 20);
  assert.equal(pilot.scenarios.length, 2);
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
