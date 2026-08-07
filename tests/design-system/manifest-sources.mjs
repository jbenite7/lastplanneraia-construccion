// Lecturas derivadas de los contratos del design system, compartidas por los
// harness de pruebas. Existe para que no haya dos copias de la misma funcion:
// `referencedTestFiles()` estaba duplicada en contracts.test.mjs y en
// closeout-contract-fixture.mjs, y las copias ya habian divergido.
import { readFileSync } from 'node:fs';
import path from 'node:path';

export const repositoryRoot = path.resolve(import.meta.dirname, '../..');

const designSystemRoot = path.join(repositoryRoot, 'docs/design-system');

const readJson = (...segments) => JSON.parse(
  readFileSync(path.join(designSystemRoot, ...segments), 'utf8'),
);

/**
 * Archivos de prueba referenciados por homologation.json y por cualquiera de
 * los manifiestos declarados en el inventario. Los fixtures copian esta lista
 * porque el gate falla con "missing test" si alguno no existe.
 */
export function referencedTestFiles() {
  const homologation = readJson('homologation.json');
  const inventory = readJson('manifests/inventory.json');
  const files = new Set(homologation.tests || []);
  for (const name of inventory.manifests) {
    if (['inventory.json', 'goal-provenance.json'].includes(name)) continue;
    const manifest = readJson('manifests', name);
    for (const file of manifest.tests || []) files.add(file);
  }
  return [...files];
}

/**
 * Matriz de viewports exigida, derivada de homologation.json en vez de
 * escrita a mano: es la union de los viewports que declaran las familias
 * gobernadas. Piloto y laboratorio comparten esta misma matriz.
 */
export function requiredViewports() {
  const homologation = readJson('homologation.json');
  return [...new Set((homologation.families || []).flatMap(({ viewports }) => viewports || []))];
}
