import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PDC_SANDBOX_PROJECT, sqlEnApp, usarSandboxPdc } from './support/pdc-sandbox.mjs';

const project = PDC_SANDBOX_PROJECT;
const FIXTURE = 'tests/browser/fixtures/pdc/presupuesto-mini.xlsx';
// Debe coincidir con `PDC_SANDBOX_FRENTE_PLAN` en database/seeds/pdc_e2e_sandbox_project.php.
const PAQUETE_PLAN = 'ZZTEST PAQUETE PLAN';

// El segundo test crea un amarre real. Va contra el proyecto sacrificable «PDC Sandbox E2E», que el
// seed resetea antes de cada test y siembra con lo mínimo que necesita esta pestaña: una semana
// activa, dos frentes de cronograma y un paquete del proyecto con insumos asignados (sin eso,
// `sugerirFrentes()` no tiene ni frentes ni paquetes que proponer).
usarSandboxPdc();

function paquetesAmarradosDelProyecto(projectId) {
  const out = sqlEnApp(
    `$rows = $db->query('SELECT paquete_id FROM pdc_paquete_frente WHERE project_id = ?', [${projectId}])`
    + `->fetchAll(PDO::FETCH_COLUMN); echo implode(',', $rows);`,
  );
  return out === '' ? [] : out.split(',').map(Number);
}

// Evidencia directa del fix Crítico: aceptar la propuesta tal cual debe dejar `origen` en
// 'similitud'/'rama' (no 'humano') Y `confirmado_humano = 1` — las dos cosas a la vez, porque son
// ortogonales (ver PlanFechasService::amarrar()). Antes del fix esto era inalcanzable desde la UI:
// el <select> nunca disparaba el POST, así que ninguna fila con estas señas podía existir.
function detalleAmarre(projectId, paqueteId) {
  return JSON.parse(sqlEnApp(
    `$row = $db->query('SELECT origen, confirmado_humano FROM pdc_paquete_frente WHERE project_id = ? AND paquete_id = ?', `
    + `[${projectId}, ${paqueteId}])->fetch(PDO::FETCH_ASSOC); echo json_encode($row);`,
  ));
}

// Solo lectura: no amarra paquetes ni recalcula, solo navega y lee la pestaña «Plan» (A4).
// Verifica que la tabla trae filas, que los vencidos se ven primero y en rojo, que un paquete se
// puede expandir a sus pasos, y que existen los bloques «Sin frente» y «Desfases».
test('plan: la pestaña Plan carga el plan calculado con vencidos primero', async ({ page }) => {
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

    // «Sin frente» y «Desfases» son pestañas de la vista, con o sin datos: desde la revisión de UX
    // ya no cuelgan una debajo de otra al final de la página (f28/f29).
    await page.getByRole('tab', { name: /Sin frente/ }).click();
    await expect(page.locator('[data-testid="pdc-plan-sin-frente"]')).toBeVisible();
    await page.getByRole('tab', { name: /Desfases/ }).click();
    await expect(page.locator('[data-testid="pdc-plan-desfases"]')).toBeVisible();
    // La pestaña abierta se anuncia como tal, y las otras no: es lo que hace que la sección activa
    // se pueda saber sin mirar el color.
    await expect(page.getByRole('tab', { name: /Desfases/ })).toHaveAttribute('aria-selected', 'true');
    await expect(page.getByRole('tab', { name: /^Plan/ })).toHaveAttribute('aria-selected', 'false');
    await expect(page.locator('[data-testid="pdc-plan-sin-frente"]')).toHaveCount(0);

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
// la propuesta tal cual era imposible desde la interfaz. Este test SÍ muta: acepta UNA propuesta con
// su botón «Amarrar» individual y comprueba que el amarre se guarda.
//
// El sandbox arranca sin ningún amarre (lo resetea el seed), así que ya no hace falta fotografiar el
// estado previo ni restaurarlo en un `finally`: cualquier fila de `pdc_paquete_frente` que exista al
// terminar la creó este test, y desaparece en el siguiente reseteo.
//
// Sigue sin pulsarse «Recalcular», por fidelidad al recorrido que prueba: ese botón recalcula TODOS
// los amarres del proyecto y aplica en silencio los desfases pendientes — justo lo que este módulo,
// por diseño, no hace sin que alguien lo vea. Lo que se asierta abajo es el estado intermedio
// «amarrado, pendiente de calcular».
test('plan: aceptar una propuesta del motor amarra el paquete (sin recalcular todo el plan)', async ({ page }) => {
  expect(
    paquetesAmarradosDelProyecto(project.projectId),
    'el sandbox debe empezar sin amarres',
  ).toEqual([]);

  await loginAndSelectProject(page, project);
  try {
    // Montaje por la interfaz, no por SQL: el bloque «Sin frente» se alimenta del resumen de
    // paquetes, que solo cuenta insumos de la VERSIÓN ACTIVA. Hacen falta las tres cosas —
    // presupuesto importado, vínculos del maestro generados y un insumo asignado a un paquete—
    // para que exista un paquete del proyecto al que el motor pueda proponerle un frente.
    await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
    await page.locator('[data-testid="pdc-import-file"]').setInputFiles(FIXTURE);
    await expect(page.locator('[data-testid="pdc-import-resumen"]')).toContainText('PI_TEST_1', { timeout: 20000 });
    await page.locator('[data-testid="pdc-import-confirmar"]').click();
    await expect(page.locator('.pdc-exito')).toBeVisible({ timeout: 20000 });

    await page.locator('[aria-label="Submódulos del plan de compras"] >> text=Maestro').click();
    await expect(page.locator('[data-testid="pdc-maestro-cobertura"]')).toBeVisible({ timeout: 15000 });

    await page.locator('[aria-label="Submódulos del plan de compras"] >> text=Paquetes').click();
    await expect(page.locator('h1')).toContainText('Paquetes de contratación', { timeout: 15000 });
    // El nombre debe coincidir con el frente que siembra el seed (PDC_SANDBOX_FRENTE_PLAN): eso es
    // lo que hace que la propuesta por similitud salga con confianza alta.
    await page.locator('[data-testid="pdc-paq-crear-nombre"]').fill(PAQUETE_PLAN);
    await page.locator('[data-testid="pdc-paq-crear-tipo"]').selectOption('a_todo_costo');
    await page.locator('[data-testid="pdc-paq-crear"]').click();
    await expect(page.locator('.pdc-info')).toBeVisible({ timeout: 15000 });

    const gridPaquetes = page.locator('[data-testid="pdc-paq-grid"]');
    await page.locator('[data-testid="pdc-paq-filtro"]').selectOption('sin_asignar');
    await expect(gridPaquetes.locator('.ag-row').first()).toBeVisible({ timeout: 15000 });
    await gridPaquetes.locator('.ag-row').first().click();
    await page.locator('[data-testid="pdc-paq-asignar"]').click();
    await expect(page.locator('.pdc-info')).toContainText('asignado', { timeout: 15000 });

    await page.goto('/plan-compras#/ensamble/plan', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1')).toContainText('Plan de compras', { timeout: 15000 });
    await page.getByRole('tab', { name: /Sin frente/ }).click();
    await expect(page.locator('[data-testid="pdc-plan-sin-frente"]')).toBeVisible({ timeout: 20000 });

    // La fila con propuesta del motor (el chip «origen · confianza»). `isVisible()` no reintenta:
    // las sugerencias llegan por un GET aparte del que ya esperamos arriba, así que hay que darle
    // margen antes de concluir que de verdad no hay ninguna.
    const filaConSugerencia = page.locator('[data-testid="pdc-plan-sin-frente"] li:has(.pdc-paq-tag)').first();
    await expect(filaConSugerencia, 'el motor debe proponer un frente para el paquete recién creado')
      .toBeVisible({ timeout: 15000 });

    // El botón individual de esa fila: ya viene preseleccionado con la propuesta del motor (fix del
    // Bloqueante), así que un solo clic basta — sin tocar el <select> ni el botón masivo.
    const amarrarBtn = filaConSugerencia.locator('button[data-testid^="pdc-plan-amarrar-"]');
    await expect(amarrarBtn).toBeEnabled({ timeout: 10000 });
    await amarrarBtn.click();
    // `.pdc-error` y `.pdc-info` ya no comparten clase (fix del Menor): esta aserción ahora sí
    // distingue un éxito real de un fallo silenciado.
    await expect(page.locator('.pdc-info')).toBeVisible({ timeout: 20000 });

    const nuevos = paquetesAmarradosDelProyecto(project.projectId);
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
    await page.getByRole('tab', { name: /Pendientes de calcular/ }).click();
    await expect(page.locator('[data-testid="pdc-plan-sin-calcular"]')).not.toContainText('Todo lo amarrado ya está calculado', { timeout: 15000 });

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    await logout(page).catch(() => {});
  }
});
