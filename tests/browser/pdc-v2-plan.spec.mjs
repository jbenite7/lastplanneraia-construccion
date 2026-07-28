import { execFileSync } from 'node:child_process';
import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');

// La captura/restauración de abajo (phpEval) hablan con el MySQL local vía `docker compose exec`,
// mientras el navegador de Playwright ataca `E2E_BASE_URL`. Si alguien corre esto apuntando a otro
// entorno (staging, otro stack), el navegador mutaría allá y esta función solo sabe leer/restaurar
// aquí — la mutación remota quedaría sin deshacer. Por eso el segundo test (el mutante) exige que
// el navegador y el `docker compose exec` sean el mismo host.
const BASE_URL = process.env.E2E_BASE_URL || 'http://localhost:8081';
const BASE_URL_ES_LOCAL = /^https?:\/\/localhost(:|\/|$)/.test(BASE_URL);

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
// la propuesta tal cual era imposible desde la interfaz. Este test SÍ muta: acepta UNA propuesta real
// con su botón «Amarrar» individual y comprueba que el amarre se guarda. No es destructivo por
// construcción: solo borra, al final, el paquete que ESTE test amarró (nunca los que ya existían), así
// que el proyecto real queda exactamente como lo encontró, gane o pierda la prueba.
//
// Importante 3 del review final (cierre de este mismo test):
// - Acepta UNA propuesta, no todas: el botón masivo «Aceptar N sugerida(s)» amarraría de golpe cada
//   paquete sugerido — hoy son decenas en Da Porto. Basta una para probar el fix; se usa el botón
//   individual de esa fila («Amarrar»), igual que un humano aceptando una sola propuesta.
// - Nunca pulsa «Recalcular»: ese botón recalcula TODOS los amarres del proyecto (no solo el que
//   crea este test) y aplica en silencio cualquier desfase pendiente que ya hubiera — precisamente lo
//   que este módulo, por diseño, no hace sin que alguien lo vea. Si el proyecto tuviera desfases
//   pendientes de otros amarres, pulsarlo aquí los aplicaría como efecto colateral de un test que no
//   tiene por qué tocarlos, y ese efecto no lo deshace el `finally` (que solo borra lo que este test
//   amarró). No pulsarlo nunca es lo que hace innecesario depender del `finally` para no hacer daño.
// - Exige `PDC_E2E_DESTRUCTIVO=1`, igual que `pdc-v2-paquetes.spec.mjs`: sigue siendo mutante sobre
//   el proyecto real aunque el radio de acción ahora sea uno solo, no cincuenta.
test('plan: aceptar una propuesta del motor amarra el paquete (sin recalcular todo el plan)', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');
  test.skip(
    process.env.PDC_E2E_DESTRUCTIVO !== '1',
    'Test mutante: crea un amarre real en el proyecto. Exporta PDC_E2E_DESTRUCTIVO=1 para correrlo.',
  );
  test.skip(
    !BASE_URL_ES_LOCAL,
    `La captura/restauración van contra el MySQL local vía Docker; E2E_BASE_URL (${BASE_URL}) no apunta a localhost, así que restaurar aquí no deshace lo que el navegador mutó allá.`,
  );

  const antes = paquetesAmarradosDelProyecto(project.projectId);

  await loginAndSelectProject(page, project);
  try {
    await page.goto('/plan-compras#/ensamble/plan', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1')).toContainText('Plan de compras', { timeout: 15000 });
    await expect(page.locator('[data-testid="pdc-plan-sin-frente"]')).toBeVisible({ timeout: 20000 });

    // Una sola fila con propuesta del motor (el chip «origen · confianza»), no todas. `isVisible()`
    // no reintenta: las sugerencias llegan por un GET aparte del que ya esperamos arriba, así que hay
    // que darle margen antes de concluir que de verdad no hay ninguna — si no, el test se autoengaña
    // con un skip por una carrera, no por falta real de datos.
    const filaConSugerencia = page.locator('[data-testid="pdc-plan-sin-frente"] li:has(.pdc-paq-tag)').first();
    const hayPropuestas = await filaConSugerencia.waitFor({ state: 'visible', timeout: 10000 }).then(() => true).catch(() => false);
    test.skip(!hayPropuestas, 'No hay ninguna propuesta del motor pendiente ahora mismo (todo ya amarrado o sin señales).');

    // El botón individual de esa fila: ya viene preseleccionado con la propuesta del motor (fix del
    // Bloqueante), así que un solo clic basta — sin tocar el <select> ni el botón masivo.
    const amarrarBtn = filaConSugerencia.locator('button[data-testid^="pdc-plan-amarrar-"]');
    await expect(amarrarBtn).toBeEnabled({ timeout: 10000 });
    await amarrarBtn.click();
    // `.pdc-error` y `.pdc-info` ya no comparten clase (fix del Menor): esta aserción ahora sí
    // distingue un éxito real de un fallo silenciado.
    await expect(page.locator('.pdc-info')).toBeVisible({ timeout: 20000 });

    const despues = paquetesAmarradosDelProyecto(project.projectId);
    const nuevos = despues.filter((id) => !antes.includes(id));
    expect(nuevos.length, `amarrar un paquete debió crear exactamente un amarre nuevo (nuevos: ${JSON.stringify(nuevos)})`).toBe(1);

    // La demostración directa del Crítico: el amarre recién creado lleva la procedencia del motor
    // (no 'humano') Y queda confirmado — aceptar la propuesta tal cual es un acierto contabilizado,
    // no una decisión humana desde cero.
    const detalle = detalleAmarre(project.projectId, nuevos[0]);
    expect(['similitud', 'rama'], `origen del amarre ${nuevos[0]}: ${JSON.stringify(detalle)}`).toContain(detalle.origen);
    expect(Number(detalle.confirmado_humano), `confirmado_humano del amarre ${nuevos[0]}`).toBe(1);

    // Importante 2 del review final: recién amarrado y sin recalcular, el paquete debe verse en el
    // bloque «Amarrados, pendientes de calcular» — nunca desaparecer de la pantalla en silencio. No se
    // recalcula el plan en este test (ver nota arriba), así que se queda justo en este estado.
    await expect(page.locator('[data-testid="pdc-plan-sin-calcular"]')).not.toContainText('Todo lo amarrado ya está calculado', { timeout: 15000 });

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    // Restauración incondicional (gane o pierda la prueba arriba): solo el paquete que este test
    // amarró, nunca los que ya estaban amarrados antes de correrlo. Como nunca se pulsó «Recalcular»,
    // lo único que hay que deshacer es ese amarre (no hay filas de `pdc_plan_paquete`/`pdc_plan_paso`
    // propias que purgar aparte de las que ya cubre `restaurarPlanDe`).
    try {
      const finalConteo = paquetesAmarradosDelProyecto(project.projectId);
      const propios = finalConteo.filter((id) => !antes.includes(id));
      restaurarPlanDe(project.projectId, propios);
    } finally {
      await logout(page).catch(() => {});
    }
  }
});
