import { expect, test } from '@playwright/test';
import { arranqueAutenticadoConProyecto, CSRF_TOKEN } from './support/project-selector-react-fixtures.mjs';

/**
 * S05 Programa General React E2E & Visual Verification Spec.
 * Cubre:
 * 1. Grilla racionalizada de 8 columnas esenciales sin scroll horizontal a 1180×820.
 * 2. Alternancia 8 vs 13 columnas completas.
 * 3. Drawer Contextual LPS (440px) con navegación secuencial (`[` y `]`), plazos,
 *    asignaciones opcionales (Responsable AIA y Subcontratista), dual-gauge con desviación Δ,
 *    matriz de 7 recursos Lean y bitácora SOS.
 * 4. Cierre con tecla Escape.
 */

const MOCK_CONTEXT = {
  proyecto: {
    id: 1,
    nombre: 'Edificio Residencial Parque Central',
    codigo: 'PRQ-CENTRAL',
  },
  semana: {
    numero: 33,
    confirmada: false,
    esPasada: false,
  },
  permisos: {
    puedeVer: true,
    puedeEditar: true,
    puedeCorteXlsx: true,
    puedeLote: true,
    readDrawer: true,
    writeDrawer: true,
  },
  catalogos: {
    unidades: ['m³', 'm²', 'ml', 'kg', 'ton', 'und', 'gl', 'mes', '%'],
    codigos: ['EST-01', 'EST-02', 'EST-03'],
    profesionales: [
      { id: 1, nombre: 'Ing. Carlos Restrepo', cargo: 'Director de Obra' },
      { id: 2, nombre: 'Ing. María Gómez', cargo: 'Residente de Estructura' },
    ],
    subcontratistas: [
      { id: 1, nombre: 'Excavaciones del Norte S.A.S.', especialidad: 'Movimiento de Tierras' },
      { id: 2, nombre: 'Aceros & Concretos de Colombia', especialidad: 'Estructura' },
    ],
  },
  csrf_token: CSRF_TOKEN,
};

const MOCK_ACTIVIDADES = [
  {
    unique_id: 1,
    Consecutivo_en_Programa: 'CAP-01',
    codigo_actividad: 'CAP-01',
    Actividad: '1. Cimentación y Estructura',
    Titulo: 1,
    Fecha_Inicio: '2026-08-01',
    Fecha_Fin: '2026-09-30',
    Ruta_Critica: 0,
    Ejecutado: 0.35,
    Ejecutado_Teorico: 0.50,
    Estado: 'En Curso',
    Semanas_Inicio: 30,
    Estado_Restricciones: '70%',
    cantidad_ppto: null,
    unidad: '%',
    Responsable_AIA: 'Ing. Carlos Restrepo',
    Sub_Contratista: 'Aceros & Concretos de Colombia',
    Observaciones: 'Frente de obra avanzando según reprogramación',
    alerta_crisis: 0,
  },
  {
    unique_id: 101,
    Consecutivo_en_Programa: 'EST-01',
    codigo_actividad: 'EST-01',
    Actividad: 'Excavación mecánica de zapatas eje A-C',
    Titulo: 0,
    Fecha_Inicio: '2026-08-10',
    Fecha_Fin: '2026-08-20',
    Ruta_Critica: 1,
    Ejecutado: 0.25,
    Ejecutado_Teorico: 0.50,
    Estado: 'Atrasada',
    Semanas_Inicio: 31,
    Estado_Restricciones: '40%',
    cantidad_ppto: 450.0,
    unidad: 'm³',
    Responsable_AIA: 'Ing. Carlos Restrepo',
    Sub_Contratista: 'Excavaciones del Norte S.A.S.',
    Observaciones: 'Retraso por nivel freático alto en eje B',
    alerta_crisis: 1,
  },
  {
    unique_id: 102,
    Consecutivo_en_Programa: 'EST-02',
    codigo_actividad: 'EST-02',
    Actividad: 'Colocación de acero de refuerzo zapatas',
    Titulo: 0,
    Fecha_Inicio: '2026-08-21',
    Fecha_Fin: '2026-08-28',
    Ruta_Critica: 1,
    Ejecutado: 0.0,
    Ejecutado_Teorico: 0.10,
    Estado: 'Debe Iniciar',
    Semanas_Inicio: 33,
    Estado_Restricciones: '100%',
    cantidad_ppto: 12.5,
    unidad: 'ton',
    Responsable_AIA: 'Ing. María Gómez',
    Sub_Contratista: 'Aceros & Concretos de Colombia',
    Observaciones: null,
    alerta_crisis: 0,
  },
  {
    unique_id: 103,
    Consecutivo_en_Programa: 'EST-03',
    codigo_actividad: 'EST-03',
    Actividad: 'Vaciado de concreto 4000 PSI zapatas',
    Titulo: 0,
    Fecha_Inicio: '2026-08-29',
    Fecha_Fin: '2026-09-05',
    Ruta_Critica: 0,
    Ejecutado: 0.0,
    Ejecutado_Teorico: 0.0,
    Estado: 'Actividad Futura',
    Semanas_Inicio: 34,
    Estado_Restricciones: '80%',
    cantidad_ppto: 180.0,
    unidad: 'm³',
    Responsable_AIA: null,
    Sub_Contratista: null,
    Observaciones: null,
    alerta_crisis: 0,
  },
];

async function configurarMocksS05(page) {
  // Bootstrap de sesión autenticada con proyecto
  await page.route('**/api/session', (route) => {
    return route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify(arranqueAutenticadoConProyecto()),
    });
  });

  // Contexto de Programa General
  await page.route('**/api/programa-general/context', (route) => {
    return route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        data: MOCK_CONTEXT,
      }),
    });
  });

  // Listado de actividades
  await page.route('**/api/general/list*', (route) => {
    return route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        data: MOCK_ACTIVIDADES,
      }),
    });
  });

  // Endpoint de guardado
  await page.route('**/api/general/update*', (route) => {
    return route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        success: true,
        respuesta: 'BIEN',
      }),
    });
  });
}

test.describe('S05 Programa General React — Comportamiento y Verificación Visual', () => {
  test.beforeEach(async ({ page }) => {
    await configurarMocksS05(page);
  });

  test('abre Programa General en 1180x820 sin scroll horizontal y verifica las 8 columnas esenciales', async ({ page }) => {
    await page.setViewportSize({ width: 1180, height: 820 });
    await page.goto('/app/programa-general');

    // 1. Verificar visibilidad de la página y toolbar
    await expect(page.getByRole('heading', { level: 1, name: 'Programa General' })).toBeVisible();
    await expect(page.getByText('Semana 33 Vigente')).toBeVisible();

    // 2. Verificar las 8 columnas esenciales
    await expect(page.getByRole('columnheader', { name: 'ID', exact: true })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'CÓDIGO' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'ACTIVIDAD' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'F. INICIO' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'F. FIN' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'PPTO TOTAL' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'AVANCE (REAL / TEÓR)' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'ESTADO' })).toBeVisible();

    // 3. Verificar que no exista scroll horizontal en 1180px
    const scrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
    expect(scrollWidth).toBeLessThanOrEqual(1180);

    // 4. Datos renderizados en celdas combinadas
    await expect(page.locator('.programa-table-pro').getByText('EST-01')).toBeVisible();
    await expect(page.locator('.programa-table-pro').getByText('450.0 m³')).toBeVisible();
    await expect(page.locator('.programa-table-pro').getByText('25.0%')).toBeVisible();
    await expect(page.locator('.programa-table-pro .badge-rc').first()).toBeVisible();
  });

  test('alterna entre 8 columnas esenciales y 13 columnas contractuales', async ({ page }) => {
    await page.setViewportSize({ width: 1180, height: 820 });
    await page.goto('/app/programa-general');

    const btn13Cols = page.getByRole('button', { name: /13 Cols Reales/i });
    const btn8Cols = page.getByRole('button', { name: /8 Cols Esenciales/i });

    // Alternar a 13 columnas
    await btn13Cols.click();
    await expect(page.getByRole('columnheader', { name: 'SEM. INICIO' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'CANTIDAD PPTO' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'LIB. RESTRICCIONES' })).toBeVisible();

    // Alternar de regreso a 8 columnas
    await btn8Cols.click();
    await expect(page.getByRole('columnheader', { name: 'SEM. INICIO' })).not.toBeVisible();
  });

  test('abre Drawer Contextual LPS (440px), navega con [ y ], y cierra con Escape', async ({ page }) => {
    await page.setViewportSize({ width: 1180, height: 820 });
    await page.goto('/app/programa-general');

    // Clic en fila de actividad operativa
    const filaActividad = page.locator('.row-activity').first();
    await filaActividad.click();

    // Drawer visible
    const drawer = page.locator('.drawer-panel-pro');
    await expect(drawer).toBeVisible();
    await expect(drawer.getByText('Plazos y Cronograma')).toBeVisible();
    await expect(drawer.getByText('Responsables & Asignaciones')).toBeVisible();
    await expect(drawer.getByText('Opcional en S05')).toBeVisible();
    await expect(drawer.getByText(/Desviación Física/i)).toBeVisible();
    await expect(drawer.getByText('Matriz de los 7 Recursos Lean')).toBeVisible();
    await expect(drawer.getByText('Bitácora SOS')).toBeVisible();

    // Navegación secuencial con tecla ]
    await page.keyboard.press(']');
    await expect(drawer).toBeVisible();

    // Cerrar con Escape
    await page.keyboard.press('Escape');
    await expect(drawer).not.toBeVisible();
  });

  test('edita datos en el Drawer, guarda cambios y recibe notificación toast', async ({ page }) => {
    await page.setViewportSize({ width: 1180, height: 820 });
    await page.goto('/app/programa-general');

    // Abrir Drawer
    await page.locator('.row-activity').first().click();
    const drawer = page.locator('.drawer-panel-pro');
    await expect(drawer).toBeVisible();

    // Seleccionar Responsable AIA opcional
    const selectProf = drawer.locator('#drawerSelectProfesional');
    await selectProf.selectOption('Ing. Carlos Restrepo');

    // Guardar cambios con atajo ⌘S / Ctrl+S o botón de guardar
    const btnGuardar = drawer.getByRole('button', { name: /Guardar Cambios/i });
    await btnGuardar.click();

    // Toast de éxito
    const toast = page.locator('.pro-toast');
    await expect(toast).toBeVisible();
    await expect(toast).toContainText('Cambios guardados con éxito');
  });

  test('filtra por chips de señales y búsqueda por texto', async ({ page }) => {
    await page.setViewportSize({ width: 1180, height: 820 });
    await page.goto('/app/programa-general');

    // Chip Atrasada
    const chipAtrasada = page.locator('.signal-chip', { hasText: 'Atrasada' });
    await chipAtrasada.click();

    const tabla = page.locator('.programa-table-pro');

    // Verificar que solo aparezcan actividades con estado Atrasada o capítulos
    await expect(tabla.getByText('Excavación mecánica de zapatas eje A-C')).toBeVisible();
    await expect(tabla.getByText('Colocación de acero de refuerzo zapatas')).not.toBeVisible();

    // Desactivar chip
    await chipAtrasada.click();
    await expect(tabla.getByText('Colocación de acero de refuerzo zapatas')).toBeVisible();

    // Buscar por texto
    const inputBusqueda = page.getByPlaceholder(/Buscar actividad, código o responsable/i);
    await inputBusqueda.fill('acero');
    await expect(tabla.getByText('Colocación de acero de refuerzo zapatas')).toBeVisible();
    await expect(tabla.getByText('Excavación mecánica de zapatas eje A-C')).not.toBeVisible();
  });
});

test.describe('S05 Programa General React — Servidor Real Docker', () => {
  test('abre Programa General en 1180x820 sin scroll horizontal autenticado vía Dev Door', async ({ page }) => {
    await page.setViewportSize({ width: 1180, height: 820 });
    await page.goto('http://localhost:8081/dev/entrar?u=test.A');

    // Ingresar al primer proyecto disponible
    const btnIngresar = page.getByRole('button', { name: /^Ingresar al proyecto/i }).first();
    await expect(btnIngresar).toBeVisible({ timeout: 15000 });
    await btnIngresar.click();
    await page.waitForURL((url) => !url.toString().includes('/proyectos'), { timeout: 15000 });

    // Navegar a la SPA de Programa General
    await page.goto('http://localhost:8081/app/programa-general');

    // Comprobar que carga el título de Programa General
    await expect(page.getByRole('heading', { level: 1, name: 'Programa General' })).toBeVisible({ timeout: 15000 });

    // Comprobar que no hay scroll horizontal en 1180px
    const scrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
    expect(scrollWidth).toBeLessThanOrEqual(1180);
  });
});

