// tests/browser/pdc-v2-pasos.spec.mjs — A4.1: la obra arma su propio proceso de contratación.
//
// Contra el sandbox sacrificable, no contra el proyecto real: la configuración de pasos mueve las
// fechas de TODOS los paquetes de la obra, y Da Porto es la línea base con la que se demuestra la
// cero regresión de esta fase. `usarSandboxPdc()` lo resetea antes de cada test.
import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PDC_SANDBOX_PROJECT, sqlEnApp, usarSandboxPdc } from './support/pdc-sandbox.mjs';
import { elegirEnSelector } from './support/pdc-selector.mjs';

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
  await elegirEnSelector(page, 'pdc-pasos-agregar', 'Aprobación del cliente');
  await expect(lista.locator('li')).toHaveCount(8);

  await page.getByTestId('pdc-pasos-guardar').click();
  await expect(page.getByText(/Se recalcularon \d+ paquetes/)).toBeVisible();
  await expect(page.getByTestId('pdc-pasos-por-defecto')).toHaveCount(0);

  // Quitar avisa ANTES de guardar, y con un número: es la garantía que pidió el grilleo.
  await page.getByRole('button', { name: 'Quitar Aprobación del cliente' }).click();
  await expect(page.getByTestId('pdc-pasos-aviso-quitar')).toContainText('Vas a quitar un paso');

  // Y la vuelta atrás, que es lo que hace seguro probar esto en cualquier obra.
  await page.getByTestId('pdc-pasos-restablecer').click();
  // El mensaje de éxito concreto, no un `getByText` suelto: «proceso por defecto» aparece también
  // en el botón y en las entradas del historial (A4.1 · diferido nº 3), y ahí la aserción laxa deja
  // de distinguir el éxito de la propia etiqueta que se acaba de pulsar.
  await expect(page.getByText('La obra vuelve al proceso por defecto de la empresa')).toBeVisible();
  await expect(lista.locator('li')).toHaveCount(7);

  await logout(page);
});

// A4.1 · diferido nº 4 — las duraciones del catálogo, editables sin entrar a la base.
//
// Hasta ahora había que abrir MySQL para cambiar un número que mueve las fechas de toda la obra.
// Lo que este test defiende es el «y solo esa» del hecho nº 2 del spec: cambiar una duración mueve
// el paquete que la usa y no toca al que usa otra.
test('cambiar una duración del catálogo mueve la fecha que dependía de ella, y solo esa', async ({ page }) => {
  const montaje = JSON.parse(sqlEnApp(
    `$crear = function (string $n, array $d) use ($db) { `
    + `$db->query("INSERT INTO general_dias_procesos_contratacion (paqueteContratacion, tipoPaquete, `
    + `diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas, diasCuadrosComparativos, `
    + `diasLegalizacionContrato, diasFabricacion, diasInsumosObra) VALUES (?, 'a_todo_costo', ?, ?, ?, ?, ?, ?, ?)", `
    + `array_merge([$n], $d)); $ref = (int) $db->lastInsertId(); `
    + `$db->query("INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, `
    + `modalidad_contratacion, duracion_ref, activo, creado_por, created_at) `
    + `VALUES (?, ?, 'a_todo_costo', 'contrato', ?, 1, 'e2e-dur', NOW())", [$n, $n, $ref]); `
    + `return ['paquete' => (int) $db->lastInsertId(), 'ref' => $ref]; }; `
    + `$a = $crear('ZZTEST DUR A', [3, 2, 7, 4, 5, 10, 2]); `
    + `$b = $crear('ZZTEST DUR B', [2, 2, 4, 2, 4, 4, 2]); `
    + `$uid = (int) $db->query('SELECT unique_id FROM programa WHERE project_id = ? AND Titulo = 1 ORDER BY Consecutivo LIMIT 1', `
    + `[${project.projectId}])->fetchColumn(); `
    + `$s = new App\\Services\\Pdc\\PlanFechasService($db); `
    + `$s->amarrar(${project.projectId}, $a['paquete'], $uid, 'e2e-dur'); `
    + `$s->amarrar(${project.projectId}, $b['paquete'], $uid, 'e2e-dur'); `
    + `$s->calcular(${project.projectId}, 'e2e-dur'); `
    + `echo json_encode(['a' => $a, 'b' => $b]);`,
  ));

  const totales = (paqueteId) => Number(sqlEnApp(
    `echo (int) $db->query('SELECT dias_totales FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?', `
    + `[${project.projectId}, ${paqueteId}])->fetchColumn();`,
  ));

  try {
    expect(totales(montaje.a.paquete), 'punto de partida A').toBe(33);
    const totalBAntes = totales(montaje.b.paquete);

    await loginAndSelectProject(page, project);
    await page.goto('/plan-compras#/ensamble/plan');
    await page.getByTestId('pdc-plan-configurar-pasos').click();

    await page.getByTestId('pdc-duraciones').locator('summary').click();
    // El aviso de que estas duraciones son de la empresa no es opcional: es la advertencia.
    await expect(page.getByTestId('pdc-duraciones-aviso')).toContainText('son de la empresa');

    const campo = page.getByTestId(`pdc-duracion-${montaje.a.ref}-diasFabricacion`);
    await expect(campo).toHaveValue('10', { timeout: 15000 });
    await campo.fill('15');
    await campo.blur();
    await expect(page.getByText(/Duración guardada/)).toBeVisible({ timeout: 15000 });

    expect(totales(montaje.a.paquete), 'el paquete que usa esa duración se movió cinco días').toBe(38);
    expect(totales(montaje.b.paquete), 'y el que usa otra no se movió').toBe(totalBAntes);

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    sqlEnApp(
      `$db->query("DELETE FROM pdc_plan_paso WHERE project_id = ${project.projectId}"); `
      + `$db->query("DELETE FROM pdc_plan_paquete WHERE project_id = ${project.projectId}"); `
      + `$db->query("DELETE FROM pdc_paquete_frente WHERE project_id = ${project.projectId}"); `
      + `$db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'e2e-dur'"); `
      + `$db->query("DELETE FROM general_dias_procesos_contratacion WHERE paqueteContratacion LIKE 'ZZTEST DUR%'"); echo 'ok';`,
    );
    await logout(page).catch(() => {});
  }
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
    await elegirEnSelector(page, 'pdc-pasos-copiar-origen', 'ZZTEST OBRA ORIGEN');
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
    await elegirEnSelector(page, 'pdc-pasos-agregar', 'Aprobación del cliente');
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
