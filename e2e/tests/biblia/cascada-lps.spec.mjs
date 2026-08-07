/**
 * Biblia de flujos · T2 cascada LPS — escenarios críticos.
 *
 * Cada test() empieza por el `id` del escenario que cubre. Ver docs/flujos/README.md.
 *
 * Diseño deliberado: estas pruebas verifican los CAMINOS DE RECHAZO (CSRF ausente, contexto
 * incoherente, permiso denegado). Son los que más valor tienen —ahí viven los bugs— y además
 * no escriben nada, así que no ensucian el proyecto de pruebas ni exigen restaurar datos.
 *
 * Cuando una falla hay dos salidas, y ninguna es ajustar la prueba: o la biblia describe mal el
 * comportamiento, o el código incumple (hallazgo a docs/EXPERIMENTS.md).
 */
import { test, expect } from '@playwright/test';

const PROYECTO = 'Da Porto';

test.use({ viewport: { width: 1180, height: 820 }, colorScheme: 'dark' });

async function entrarComo(page, cuenta, proyecto = PROYECTO) {
  await page.goto(`/dev/entrar?u=${encodeURIComponent(cuenta)}&p=${encodeURIComponent(proyecto)}`);
}

/**
 * Reutiliza las cookies de la página para llamar la API como el usuario en sesión.
 *
 * Con `conCsrf` adjunta el token que la página emite en `<meta name="csrf-token">`. Lo necesitan
 * los escenarios que apuntan a un guard POSTERIOR al de CSRF: desde que toda mutación lo exige
 * (2026-08-06), una petición sin token muere en la primera barrera y nunca llega a la que el
 * escenario quiere probar.
 */
async function postComoUsuario(page, url, form, { conCsrf = false } = {}) {
  return page.evaluate(async ({ url, form, conCsrf }) => {
    const campos = { ...form };
    if (conCsrf) {
      campos._csrf_token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    }
    const body = new URLSearchParams(campos).toString();
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body,
    });
    return { status: res.status, texto: (await res.text()).slice(0, 400) };
  }, { url, form, conCsrf });
}

test.describe('T2 · Guardias de Programación Semanal', () => {
  test('PS-002 · una mutación sin token CSRF se rechaza con 403', async ({ page }) => {
    await entrarComo(page, 'test.R');
    await page.goto('/programacion-semanal');

    // `nuevo` está en la lista que exige CSRF (SemanalApiController:128-129).
    const r = await postComoUsuario(page, '/api/semanal/save', {
      opcion: 'nuevo',
      semana: '4',
      db: '',
    });

    expect(r.status).toBe(403);
  });

  test('PS-001 · un prefijo de base distinto al de la sesión se rechaza con 403', async ({ page }) => {
    await entrarComo(page, 'test.R');
    await page.goto('/programacion-semanal');

    // Se manda token CSRF válido para que la petición llegue hasta requireSessionDbPrefix
    // (SemanalApiController:219-226), que es el guard que este escenario prueba. Antes bastaba
    // con elegir `sanear` porque esquivaba la lista de CSRF; ese hueco se cerró el 2026-08-06.
    const r = await postComoUsuario(page, '/api/semanal/save', {
      opcion: 'sanear',
      semana: '4',
      db: 'prefijo_que_no_es_el_de_la_sesion',
    }, { conCsrf: true });

    expect(r.status).toBe(403);
    expect(r.texto).toContain('no coincide con la sesión activa');
  });
});

test.describe('T2 · Guardias de los submódulos de aprendizaje', () => {
  test('APR-003 · un rol sin disciplinas CIC no puede calificar', async ({ page }) => {
    // El Visualizador no aparece en cicDisciplinesForRole (RbacCatalog:55-62) → lista vacía.
    await entrarComo(page, 'test.V');
    await page.goto('/programacion-semanal');

    const r = await postComoUsuario(page, '/api/cic/save', { opcion: 'modificar', Id: '1' });

    // Debe cortar por permiso (rbac_guard) o por disciplinas vacías: en ambos casos, 403.
    expect(r.status).toBe(403);
  });

  test('APR-001 · el Visualizador no puede registrar causas de no cumplimiento', async ({ page }) => {
    await entrarComo(page, 'test.V');
    await page.goto('/programacion-semanal');

    const r = await postComoUsuario(page, '/api/cnc/save', { opcion: 'modificar', Id: '1' });

    expect(r.status).toBe(403);
  });
});

test.describe('T2 · Lectura permitida', () => {
  test('PG-001 · el Visualizador sí puede listar el programa general', async ({ page }) => {
    // Contraparte necesaria: comprobar que la restricción no se pasa de frenada.
    // El Visualizador tiene lps.programa_general.ver, así que listar debe funcionar.
    await entrarComo(page, 'test.V');
    await page.goto('/programa-general');

    const r = await postComoUsuario(page, '/api/general/list', {});

    expect(r.status).toBe(200);
  });
});
