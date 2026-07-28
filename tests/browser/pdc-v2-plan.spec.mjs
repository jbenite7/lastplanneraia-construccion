import { execFileSync } from 'node:child_process';
import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');

// Acceso directo a MySQL vía el contenedor `app` (reusa la conexión ya configurada de Database.php,
// igual que los tests PHP autoejecutables): solo para capturar/restaurar el estado del proyecto
// real alrededor del segundo test (mutante), no para el primero (solo lectura).
function phpEval(code) {
  return execFileSync('docker', ['compose', 'exec', '-T', 'app', 'php', '-r', code], {
    cwd: process.cwd(), encoding: 'utf8', timeout: 30_000,
  });
}

function paquetesAmarradosDelProyecto(projectId) {
  const out = phpEval(`
    require '/var/www/html/vendor/autoload.php';
    require '/var/www/html/src/Core/Database.php';
    $db = Database::getInstance();
    $rows = $db->query('SELECT paquete_id FROM pdc_paquete_frente WHERE project_id = ?', [${projectId}])->fetchAll(PDO::FETCH_COLUMN);
    echo implode(',', $rows);
  `).trim();
  return out === '' ? [] : out.split(',').map(Number);
}

// Evidencia directa del fix Crítico: aceptar la propuesta tal cual debe dejar `origen` en
// 'similitud'/'rama' (no 'humano') Y `confirmado_humano = 1` — las dos cosas a la vez, porque son
// ortogonales (ver PlanFechasService::amarrar()). Antes del fix esto era inalcanzable desde la UI:
// el <select> nunca disparaba el POST, así que ninguna fila con estas señas podía existir.
function detalleAmarre(projectId, paqueteId) {
  const out = phpEval(`
    require '/var/www/html/vendor/autoload.php';
    require '/var/www/html/src/Core/Database.php';
    $db = Database::getInstance();
    $row = $db->query('SELECT origen, confirmado_humano FROM pdc_paquete_frente WHERE project_id = ? AND paquete_id = ?', [${projectId}, ${paqueteId}])->fetch(PDO::FETCH_ASSOC);
    echo json_encode($row);
  `).trim();
  return JSON.parse(out);
}

// Borra SOLO los paquetes indicados (los que este test creó) de las tres tablas de A4 — nunca las
// filas que ya existían antes de correr el test. Así el test queda no destructivo por construcción:
// deja el proyecto real exactamente como lo encontró, gane o pierda la prueba.
function restaurarPlanDe(projectId, paqueteIds) {
  if (paqueteIds.length === 0) return;
  const lista = paqueteIds.map(Number).join(',');
  phpEval(`
    require '/var/www/html/vendor/autoload.php';
    require '/var/www/html/src/Core/Database.php';
    $db = Database::getInstance();
    $db->query('DELETE FROM pdc_plan_paso WHERE project_id = ? AND paquete_id IN (${lista})', [${projectId}]);
    $db->query('DELETE FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id IN (${lista})', [${projectId}]);
    $db->query('DELETE FROM pdc_paquete_frente WHERE project_id = ? AND paquete_id IN (${lista})', [${projectId}]);
    echo 'ok';
  `);
}

// Solo lectura: no amarra paquetes ni recalcula — a diferencia de pdc-v2-paquetes.spec.mjs (que SÍ
// importa un presupuesto de juguete y es destructivo), esta prueba solo navega y lee la pestaña
// «Plan» (A4). Verifica que la tabla trae filas, que los vencidos se ven primero y en rojo, que un
// paquete se puede expandir a sus pasos, y que existen los bloques «Sin frente» y «Desfases».
test('plan: la pestaña Plan carga el plan calculado con vencidos primero', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');

  await loginAndSelectProject(page, project);
  try {
    await page.goto('/plan-compras#/ensamble/plan', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1')).toContainText('Plan de compras', { timeout: 15000 });

    // El resumen (paquete(s) / vencido(s) / con duración estimada) siempre está, haya o no plan.
    await expect(page.locator('[data-testid="pdc-plan-resumen"]')).toBeVisible({ timeout: 20000 });

    const grid = page.locator('[data-testid="pdc-plan-grid"]');
    await expect(grid).toBeVisible();

    // Si hay paquetes con plan calculado (depende de que alguien haya amarrado y recalculado antes),
    // la tabla trae filas y los vencidos van primero y en rojo (clase pdc-plan-fila-vencida).
    const filas = grid.locator('.ag-row');
    const total = await filas.count();
    if (total > 0) {
      await expect(filas.first()).toBeVisible({ timeout: 15000 });

      // Si hay al menos un vencido, la primera fila (ya ordenada por el backend) debe llevar la
      // clase que la pinta en rojo.
      const vencidosTexto = await page.locator('[data-testid="pdc-plan-resumen"]').innerText();
      if (!vencidosTexto.includes('0 vencido')) {
        await expect(filas.first()).toHaveClass(/pdc-plan-fila-vencida/);
      }

      // Un click en una fila expande sus siete pasos del proceso de contratación.
      await filas.first().click();
      await expect(page.locator('[data-testid="pdc-plan-detalle"]')).toBeVisible({ timeout: 15000 });
      await expect(page.locator('[data-testid="pdc-plan-detalle"] table tbody tr')).toHaveCount(7);
    } else {
      await expect(page.locator('.pdc-vacio').first()).toBeVisible();
    }

    // «Sin frente» y «Desfases» son secciones fijas de la vista, con o sin datos.
    await expect(page.locator('[data-testid="pdc-plan-sin-frente"]')).toBeVisible();
    await expect(page.locator('[data-testid="pdc-plan-desfases"]')).toBeVisible();

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');

    if (process.env.PDC_SHOT) {
      await page.screenshot({ path: `${process.env.PDC_SHOT}-plan.png`, fullPage: true });
    }
  } finally {
    await logout(page).catch(() => {});
  }
});

// Crítico del review final A4: el <select> preseleccionaba la propuesta del motor pero solo el
// `onChange` disparaba el amarre — elegir la opción ya elegida no emite `change`, así que aceptar
// la propuesta tal cual era imposible desde la interfaz (50 de 96 paquetes de DAPORTO, en la medición
// del review). Este test SÍ muta: acepta una propuesta real con el botón «Aceptar N sugerida(s)» y
// comprueba que el amarre se guarda y el plan la recalcula. No es destructivo por construcción: solo
// borra, al final, los paquetes que ESTE test amarró (nunca los que ya existían), así que el proyecto
// real queda exactamente como lo encontró, gane o pierda la prueba.
test('plan: aceptar una propuesta del motor amarra el paquete y el plan lo recalcula', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');

  const antes = paquetesAmarradosDelProyecto(project.projectId);

  await loginAndSelectProject(page, project);
  try {
    await page.goto('/plan-compras#/ensamble/plan', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1')).toContainText('Plan de compras', { timeout: 15000 });
    await expect(page.locator('[data-testid="pdc-plan-sin-frente"]')).toBeVisible({ timeout: 20000 });

    // `isVisible()` no reintenta: las sugerencias llegan por un GET aparte del que ya esperamos
    // arriba, así que hay que darle margen antes de concluir que de verdad no hay ninguna — si no,
    // el test se autoengaña con un skip por una carrera, no por falta real de datos.
    const aceptar = page.locator('[data-testid="pdc-plan-aceptar-sugeridos"]');
    const hayPropuestas = await aceptar.waitFor({ state: 'visible', timeout: 10000 }).then(() => true).catch(() => false);
    test.skip(!hayPropuestas, 'No hay ninguna propuesta del motor pendiente ahora mismo (todo ya amarrado o sin señales).');

    // El botón masivo acepta TODAS las propuestas visibles tal cual — un único clic, sin pasar por
    // el <select> (que ya no dispara nada por su cuenta).
    await aceptar.click();
    await expect(page.locator('.pdc-info')).toBeVisible({ timeout: 20000 });

    const despues = paquetesAmarradosDelProyecto(project.projectId);
    const nuevos = despues.filter((id) => !antes.includes(id));
    expect(nuevos.length, 'aceptar sugeridos debió crear al menos un amarre nuevo').toBeGreaterThan(0);

    // La demostración directa del Crítico: el amarre recién creado lleva la procedencia del motor
    // (no 'humano') Y queda confirmado — aceptar la propuesta tal cual es un acierto contabilizado,
    // no una decisión humana desde cero.
    const detalle = detalleAmarre(project.projectId, nuevos[0]);
    expect(['similitud', 'rama'], `origen del amarre ${nuevos[0]}: ${JSON.stringify(detalle)}`).toContain(detalle.origen);
    expect(Number(detalle.confirmado_humano), `confirmado_humano del amarre ${nuevos[0]}`).toBe(1);

    // Importante 2 del review final: recién amarrado y sin recalcular, el paquete debe verse en el
    // bloque «Amarrados, pendientes de calcular» — nunca desaparecer de la pantalla en silencio.
    await expect(page.locator('[data-testid="pdc-plan-sin-calcular"]')).not.toContainText('Todo lo amarrado ya está calculado', { timeout: 15000 });

    // Recalcular trae el plan al día: el paquete recién amarrado debe aparecer en la grilla.
    await page.locator('[data-testid="pdc-plan-recalcular"]').click();
    await expect(page.locator('.pdc-info')).toBeVisible({ timeout: 20000 });
    await expect(page.locator('[data-testid="pdc-plan-grid"] .ag-row').first()).toBeVisible({ timeout: 15000 });

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    // Restauración incondicional (gane o pierda la prueba arriba): solo los paquetes que este test
    // amarró, nunca los que ya estaban amarrados antes de correrlo.
    try {
      const finalConteo = paquetesAmarradosDelProyecto(project.projectId);
      const propios = finalConteo.filter((id) => !antes.includes(id));
      restaurarPlanDe(project.projectId, propios);
    } finally {
      await logout(page).catch(() => {});
    }
  }
});
