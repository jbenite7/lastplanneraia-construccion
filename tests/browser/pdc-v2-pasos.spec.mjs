// tests/browser/pdc-v2-pasos.spec.mjs — A4.1: la obra arma su propio proceso de contratación.
//
// Contra el sandbox sacrificable, no contra el proyecto real: la configuración de pasos mueve las
// fechas de TODOS los paquetes de la obra, y Da Porto es la línea base con la que se demuestra la
// cero regresión de esta fase. `usarSandboxPdc()` lo resetea antes de cada test.
import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PDC_SANDBOX_PROJECT, sqlEnApp, usarSandboxPdc } from './support/pdc-sandbox.mjs';

const project = PDC_SANDBOX_PROJECT;

usarSandboxPdc();

test('una obra agrega un paso propio y vuelve al proceso por defecto', async ({ page }) => {
  await loginAndSelectProject(page, project);
  await page.goto('/plan-compras#/ensamble/plan');

  await page.getByTestId('pdc-plan-configurar-pasos').click();

  const lista = page.getByTestId('pdc-pasos-lista');
  await expect(lista.locator('li')).toHaveCount(7);
  await expect(page.getByTestId('pdc-pasos-por-defecto')).toBeVisible();
  // Sin configuración propia no hay nada que restablecer: el botón no debe existir todavía.
  await expect(page.getByTestId('pdc-pasos-restablecer')).toHaveCount(0);

  // Licify y Aprobación del cliente son justamente las dos variantes que el roadmap pedía no
  // hardcodear: si el catálogo no las ofreciera, este selectOption fallaría.
  await page.getByTestId('pdc-pasos-agregar').selectOption('aprobacion_cliente');
  await expect(lista.locator('li')).toHaveCount(8);

  await page.getByTestId('pdc-pasos-guardar').click();
  await expect(page.getByText(/Se recalcularon \d+ paquetes/)).toBeVisible();
  await expect(page.getByTestId('pdc-pasos-por-defecto')).toHaveCount(0);

  // Quitar avisa ANTES de guardar, y con un número: es la garantía que pidió el grilleo.
  await page.getByRole('button', { name: 'Quitar Aprobación del cliente' }).click();
  await expect(page.getByTestId('pdc-pasos-aviso-quitar')).toContainText('Vas a quitar un paso');

  // Y la vuelta atrás, que es lo que hace seguro probar esto en cualquier obra.
  await page.getByTestId('pdc-pasos-restablecer').click();
  await expect(page.getByText('proceso por defecto')).toBeVisible();
  await expect(lista.locator('li')).toHaveCount(7);

  await logout(page);
});

// A4.1 · diferido nº 2 — copiar la configuración de otra obra.
//
// Lo primero que hace quien monta la segunda obra es querer partir de lo que ya funcionó en la
// primera. Lo que este test defiende es que la copia se VE antes de aplicarse: si la obra origen
// quedó a medias, la copia hereda ese hueco, y eso hay que poder verlo antes de decidir.
test('copiar la configuración de otra obra enseña qué trae antes de traerlo', async ({ page }) => {
  // La otra obra es sintética: el usuario de pruebas tiene que ser miembro para poder verla.
  const OTRA = 990101;
  sqlEnApp(
    `$db->query("DELETE FROM pdc_proyecto_pasos WHERE project_id = ${OTRA}"); `
    + `$db->query("DELETE FROM project_members WHERE project_id = ${OTRA}"); `
    + `$db->query("DELETE FROM general_proyectos_procesos WHERE Id = ${OTRA}"); `
    + `$db->query("INSERT INTO general_proyectos_procesos (Id, Proyecto_Proceso, Base_de_Datos, Area, Activo, Acceso) `
    + `VALUES (${OTRA}, 'ZZTEST OBRA ORIGEN', 'zztest_origen', 'Construccion', 1, 1)"); `
    // Todos los miembros del sandbox lo son también de la obra origen: así el usuario que corre el
    // test la ve, sea cual sea el que configuren las credenciales del entorno.
    + `$db->query("INSERT INTO project_members (project_id, user_id, role) `
    + `SELECT ${OTRA}, user_id, 'D' FROM project_members WHERE project_id = ${project.projectId}"); `
    + `$s = new App\\Services\\Pdc\\PasosContratacionService($db); `
    + `$s->guardar(${OTRA}, [['clave' => 'elaboracion_pliegos'], ['clave' => 'legalizacion'], `
    + `['clave' => 'insumos_obra', 'alias' => 'Llegada a obra']], 'e2e-copia'); echo 'ok';`,
  );

  try {
    await loginAndSelectProject(page, project);
    await page.goto('/plan-compras#/ensamble/plan');
    await page.getByTestId('pdc-plan-configurar-pasos').click();

    const lista = page.getByTestId('pdc-pasos-lista');
    await expect(lista.locator('li')).toHaveCount(7);

    await page.getByTestId('pdc-pasos-copiar').locator('summary').click();
    await page.getByTestId('pdc-pasos-copiar-origen').selectOption(String(OTRA));
    await page.getByTestId('pdc-pasos-copiar-preview').click();

    // Se ve QUÉ se copiaría, con el alias incluido, ANTES de tocar nada.
    const preview = page.getByTestId('pdc-pasos-preview-copia');
    await expect(preview).toBeVisible({ timeout: 15000 });
    await expect(page.getByTestId('pdc-pasos-preview-lista').locator('li')).toHaveCount(3);
    await expect(preview).toContainText('Llegada a obra');
    // Previsualizar no escribe: la obra sigue con sus siete.
    await expect(lista.locator('li')).toHaveCount(7);

    await page.getByTestId('pdc-pasos-copiar-cancelar').click();
    await expect(preview).toBeHidden();
    await expect(lista.locator('li')).toHaveCount(7);

    await page.getByTestId('pdc-pasos-copiar-preview').click();
    await expect(preview).toBeVisible({ timeout: 15000 });
    await page.getByTestId('pdc-pasos-copiar-confirmar').click();
    await expect(page.getByText(/Copiados 3 pasos/)).toBeVisible({ timeout: 15000 });
    await expect(lista.locator('li')).toHaveCount(3);

    // Copia PUNTUAL, no vínculo vivo: editar aquí no puede tocar la obra de origen.
    await page.getByTestId('pdc-pasos-agregar').selectOption('aprobacion_cliente');
    await page.getByTestId('pdc-pasos-guardar').click();
    await expect(page.getByText(/Se recalcularon \d+ paquetes/)).toBeVisible({ timeout: 15000 });
    const pasosOrigen = Number(sqlEnApp(
      `echo (int) $db->query('SELECT COUNT(*) FROM pdc_proyecto_pasos WHERE project_id = ?', [${OTRA}])->fetchColumn();`,
    ));
    expect(pasosOrigen, 'editar el destino no puede cambiar el origen').toBe(3);

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    sqlEnApp(
      `$db->query("DELETE FROM pdc_proyecto_pasos WHERE project_id = ${OTRA}"); `
      + `$db->query("DELETE FROM project_members WHERE project_id = ${OTRA}"); `
      + `$db->query("DELETE FROM general_proyectos_procesos WHERE Id = ${OTRA}"); echo 'ok';`,
    );
    await logout(page).catch(() => {});
  }
});
