#!/usr/bin/env node
import { execSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import process from 'node:process';
import { pathToFileURL } from 'node:url';

const root = process.cwd();
const UI_PREFIXES = ['views/', 'public/css/', 'public/js/', 'src/View/Components/', 'admin/public/css/'];
const CORE_PATHS = [
  'public/css/tokens.css',
  'public/css/design-system/',
  'public/css/aia-design-system.css',
  'public/js/modules/aia_ui/',
];

function loadManifests() {
  const dir = join(root, 'docs/design-system/manifests');
  const inventory = JSON.parse(readFileSync(join(dir, 'inventory.json'), 'utf8'));
  const manifests = (inventory.manifests || [])
    .map((rel) => JSON.parse(readFileSync(join(dir, rel), 'utf8')));
  return { manifests, sharedHeadConsumers: inventory.sharedHeadConsumers || [] };
}

export function routeChanges(changedFiles, deps = loadManifests()) {
  const { manifests } = deps;
  const uiFiles = changedFiles.filter((f) => UI_PREFIXES.some((p) => f.startsWith(p)));

  const declared = new Set();
  const undeclared = [];
  for (const file of uiFiles) {
    const owner = manifests.find((m) => (m.sources || []).includes(file));
    if (owner) declared.add(owner.moduleId);
    else undeclared.push(file);
  }

  const commands = [];
  const warnings = [];
  if (uiFiles.length > 0) {
    commands.push('npm run test:design-system:static');
    if (uiFiles.some((f) => CORE_PATHS.some((c) => f.startsWith(c)))) {
      commands.push('npm run test:design-system:runtime');
    }
  }
  if (undeclared.length > 0) {
    warnings.push(
      'Superficie(s) UI sin manifiesto declarado. Rige DESIGN.md: usa tokens --ds-*/--aia-* y primitivas aia-*; nada de hex, inline styles, <style> ni tipografías locales.',
      'El cambio NO debe subir docs/design-system/audit-baseline.json (design-system-audit.mjs falla si la deuda visual aumenta).',
    );
  }
  return { uiFiles, declared: [...declared], undeclared, commands, warnings };
}

async function readStdin() {
  if (process.stdin.isTTY) return '';
  const chunks = [];
  try {
    for await (const chunk of process.stdin) chunks.push(chunk);
  } catch {
    return '';
  }
  return Buffer.concat(chunks).toString('utf8');
}

// Extrae la ruta del archivo tocado desde el JSON de un hook PostToolUse
// (Claude Code / Codex entregan el evento por stdin, no por variable de entorno).
function filesFromHookEvent(stdinJson) {
  if (!stdinJson.trim()) return [];
  try {
    const event = JSON.parse(stdinJson);
    const input = event.tool_input || event.toolInput || {};
    const path = input.file_path || input.filePath || input.path;
    return path ? [String(path).replace(`${root}/`, '')] : [];
  } catch {
    return [];
  }
}

if (import.meta.url === pathToFileURL(process.argv[1]).href) {
  let files = process.argv.slice(2);
  if (files.length === 0) files = filesFromHookEvent(await readStdin());
  if (files.length === 0) {
    const out = execSync('git diff --name-only HEAD', { cwd: root, encoding: 'utf8' });
    files = out.split('\n').map((s) => s.trim()).filter(Boolean);
  }
  const report = routeChanges(files);
  if (report.uiFiles.length === 0) {
    console.log('design-system-router: sin cambios de UI relevantes.');
    process.exit(0);
  }
  console.log('design-system-router: cambios de UI detectados.');
  if (report.declared.length) console.log(`  Superficies declaradas: ${report.declared.join(', ')}`);
  if (report.undeclared.length) console.log(`  Superficies sin manifiesto: ${report.undeclared.join(', ')}`);
  for (const w of report.warnings) console.log(`  ⚠ ${w}`);
  console.log('  Gates a ejecutar:');
  for (const c of report.commands) console.log(`    - ${c}`);
}
