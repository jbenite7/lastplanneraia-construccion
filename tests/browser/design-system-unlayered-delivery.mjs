// Gate de ENTREGAS SIN CAPA — superficie de RUNTIME.
//
// La superficie estatica (`<link>` crudo en una vista) la cubre
// `scripts/design-system-unlayered-delivery.mjs`. Aqui se mide lo que la
// superficie estatica no puede ver: como llegan REALMENTE las hojas al
// documento. El caso que motivo este gate —`sweetalert2.all.min.js` inyectando
// su CSS en un `<style>` creado en runtime— no estaba en ningun archivo del
// repo y solo se descubrio midiendo un color raro.
//
// Regla: ninguna hoja de autor puede aportar reglas de estilo fuera de capa.
// Sin capa gana a TODAS las capas en declaraciones normales (DS-006 invierte el
// orden solo para `!important`), asi que una sola hoja sin capa derrota al
// design system completo.
//
// FUERA DE ALCANCE: `admin/`. AGENTS.md excluye el panel Admin del design
// system (AdminLTE no se migra); ninguna de sus rutas se audita aqui.
import { mkdir, writeFile } from 'node:fs/promises';
// fileURLToPath y no URL.pathname: la ruta del repo contiene un espacio.
import { fileURLToPath } from 'node:url';
import { readFileSync } from 'node:fs';
import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { compareDeliveries } from '../../scripts/design-system-unlayered-delivery.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const CI_ADMIN = { username: 'test.A', password: 'aia2026' };
const inventory = JSON.parse(
  readFileSync(fileURLToPath(new URL('../../docs/design-system/unlayered-delivery-inventory.json', import.meta.url)), 'utf8'),
);
// test-output/ es el outputDir de Playwright y se limpia entre corridas; la
// evidencia del censo se deja en test-results/, que sobrevive a la limpieza.
const outDir = new URL('../../test-results/', import.meta.url);

/**
 * Recorre `document.styleSheets` y devuelve, por hoja, cuantas reglas de estilo
 * quedan fuera de capa. Se ejecuta dentro del navegador: es la unica forma de
 * ver las hojas que no existen como archivo (vendors que inyectan un `<style>`).
 */
const PROBE = () => {
  const walk = (rules, acc) => {
    for (const rule of rules) {
      const kind = rule.constructor?.name || `type${rule.type}`;
      // Dentro de un bloque de capa todo esta capado; una sentencia `@layer a, b;`
      // no aporta reglas. Ambos se saltan enteros.
      if (kind === 'CSSLayerBlockRule' || kind === 'CSSLayerStatementRule') continue;
      if (kind === 'CSSImportRule') {
        // `layerName` es null solo si el @import no declara capa; '' es capa anonima.
        if (rule.layerName !== null && rule.layerName !== undefined) continue;
        try {
          if (rule.styleSheet) walk(rule.styleSheet.cssRules, acc);
          else acc.opaque = true;
        } catch {
          acc.opaque = true;
        }
        continue;
      }
      // Las reglas de agrupacion conservan el contexto de capa: hay que entrar.
      if (['CSSMediaRule', 'CSSSupportsRule', 'CSSContainerRule', 'CSSScopeRule', 'CSSStartingStyleRule'].includes(kind)) {
        walk(rule.cssRules, acc);
        continue;
      }
      if (kind === 'CSSStyleRule') {
        acc.unlayered += 1;
        if (acc.samples.length < 3) acc.samples.push(String(rule.selectorText || '').slice(0, 80));
      }
    }
  };

  const findings = [];
  // Los `<style>` no tienen URL con la que identificarlos y su ordinal no es
  // una identidad estable (una app empaquetada emite decenas y las reordena en
  // cada build). Se colapsan en una sola entrada por ruta: la pregunta que el
  // gate responde es "entra CSS sin capa por un <style> en esta ruta", que es
  // exactamente lo que fallo con sweetalert2.
  const styles = { sheet: 'style:*', kind: 'style', blocks: 0, unlayeredRules: 0, samples: [] };
  for (const sheet of document.styleSheets) {
    const acc = { unlayered: 0, samples: [], opaque: false };
    let accessible = true;
    try {
      walk(sheet.cssRules, acc);
    } catch {
      // Cross-origin: el navegador no expone cssRules. Un `<link>` no admite
      // `layer`, asi que una hoja remota solo estaria capada si se envolviera a
      // si misma — indecidible desde aqui y por eso se declara en el inventario.
      accessible = false;
    }
    if (accessible && acc.unlayered === 0 && !acc.opaque) continue;
    if (!sheet.href) {
      styles.blocks += 1;
      styles.unlayeredRules += acc.unlayered;
      if (styles.samples.length < 3) styles.samples.push(...acc.samples.slice(0, 1));
      continue;
    }
    const url = new URL(sheet.href);
    findings.push({
      sheet: url.origin === window.location.origin ? url.pathname : `${url.origin}${url.pathname}`,
      kind: accessible ? 'link' : 'cross-origin-opaque',
      unlayeredRules: accessible ? acc.unlayered : null,
      samples: acc.samples,
    });
  }
  if (styles.blocks > 0) findings.push(styles);
  return findings;
};

async function probe(page, route) {
  const response = await page.goto(route, { waitUntil: 'networkidle' });
  expect(response?.status(), `${route} debe responder`).toBeLessThan(400);
  // Los vendors que inyectan su CSS en runtime lo hacen al primer uso o al
  // cargar su bundle; se deja un margen tras networkidle.
  await page.waitForTimeout(500);
  return page.evaluate(PROBE);
}

function routesFor(authenticated) {
  return Object.entries(inventory.runtime)
    .filter(([, spec]) => Boolean(spec.authenticated) === authenticated)
    .map(([route, spec]) => ({ route, spec }));
}

async function assertRoutes(page, entries, observedReport) {
  const failures = [];
  for (const { route, spec } of entries) {
    const findings = await probe(page, route);
    observedReport[route] = findings;
    failures.push(...compareDeliveries({
      scope: route,
      observed: findings.map(({ sheet }) => sheet),
      declared: (spec.unlayered ?? []).map(({ sheet }) => sheet),
    }));
  }
  return failures;
}

async function persist(scope, observedReport) {
  await mkdir(fileURLToPath(outDir), { recursive: true });
  await writeFile(
    new URL(`design-system-unlayered-delivery-${scope}.json`, outDir),
    `${JSON.stringify(observedReport, null, 2)}\n`,
  );
}

const HINT = 'Elimina la entrega sin capa (entrypoints/attach-*.css la importa con layer(vendor))'
  + `; declararla en ${'docs/design-system/unlayered-delivery-inventory.json'} solo si esta revisada.`;

test.describe('entregas sin capa', () => {
  test('rutas publicas: ninguna hoja de autor sin declarar entra sin capa', async ({ page }) => {
    await page.setViewportSize({ width: 1180, height: 820 });
    const observed = {};
    let failures;
    try {
      failures = await assertRoutes(page, routesFor(false), observed);
    } finally {
      await persist('public', observed);
    }
    expect(failures, `${failures.join('\n')}\n${HINT}`).toEqual([]);
  });

  test('rutas autenticadas: ninguna hoja de autor sin declarar entra sin capa', async ({ page }) => {
    test.skip(!project, 'Construction project required');
    await page.setViewportSize({ width: 1180, height: 820 });
    await loginAndSelectProject(page, project, CI_ADMIN);
    const observed = {};
    let failures;
    try {
      failures = await assertRoutes(page, routesFor(true), observed);
    } finally {
      await persist('authenticated', observed);
      await logout(page).catch(() => {});
    }
    expect(failures, `${failures.join('\n')}\n${HINT}`).toEqual([]);
  });
});
