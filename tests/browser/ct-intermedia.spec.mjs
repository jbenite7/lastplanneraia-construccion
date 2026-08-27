import { execFileSync } from 'node:child_process';
import { test, expect, request as apiRequest } from '@playwright/test';
import { BASE_URL } from './fixtures/base-url.mjs';
import { installErrorCollectors, assertNoRuntimeErrors } from './support/assertions.mjs';

/**
 * Task 7 paso 6 (rol A, test writer): e2e final del piloto — recorre la hoja de Intermedia
 * completa en React (ct-app), servida tras CT_PILOTO=1, sobre un proyecto sandbox propio.
 * Ver .superpowers/sdd/2026-08-26-ola1-torre-etapa-piloto/task-7-report.md (sección de este
 * paso) para el reporte completo; resumen de las decisiones no obvias aquí:
 *
 * --- BLOQUEO real: CT_PILOTO no se puede activar desde este archivo ---
 * `BiViewController::ctPilotoEnabled()` lee `$_ENV['CT_PILOTO']`/`getenv('CT_PILOTO')` en CADA
 * request (mismo patrón que `DevDoor::flagEnabled()`). Para una petición HTTP real contra Apache
 * eso viene de Dotenv leyendo el archivo `.env` de la raíz — `docker-compose.yml` NO inyecta
 * `CT_PILOTO` en el bloque `environment:` del servicio `app` (confirmado por lectura directa del
 * archivo), así que la única forma de que un request real vea la bandera prendida es que `.env`
 * (compartido, gitignored) la traiga, o que se reconstruya el contenedor con la variable inyectada
 * — las dos cosas están fuera del mandato de este rol (test writer) sin autorización explícita.
 * Confirmado en vivo antes de escribir este archivo: `GET /bi/intermedia` autenticado devuelve hoy
 * el `<title>BI Control Tower — LPS AIA</title>` de la SPA vieja, no el shell de `ct-app`. Esto es
 * el mismo límite que ya documentaron las entradas 10 y 11 de la Bitácora del plan
 * (`docs/superpowers/plans/2026-08-26-ola1-torre-etapa-piloto.md`), que además señalan que este es
 * justo el momento ("cuando Task 7 la active para construir/probar la pantalla real") en que
 * coordinar la ventana SÍ se justifica — pero esa coordinación es una decisión que le corresponde
 * a quien controla la sesión, no a este archivo.
 *
 * El `test.beforeAll`/`test.beforeEach` de abajo comprueban el estado REAL de la bandera contra el
 * contenedor servido (una petición HTTP, no una suposición) y fallan con un diagnóstico explícito
 * si sigue apagada — así el RED de hoy dice exactamente por qué, en vez de un timeout genérico
 * buscando un `data-testid` que nunca puede aparecer. El resto del archivo está escrito COMO SI la
 * bandera estuviera activa (tal como pide el encargo): en cuanto alguien la prenda de verdad, estos
 * mismos tests empiezan a ejercitar el flujo real sin tocarse.
 *
 * --- Proyecto sandbox: "Prueba" (project_id descubierto en runtime, NO Da Porto/73) ---
 * Investigado en la base de dev: `project_id=27`, `Proyecto_Proceso='Prueba'`. Los 4 usuarios de
 * la puerta de desarrollo (`test.A`, `test.D`, `test.R`, `test.V`) son miembros con su rol obvio
 * (A/D/R/V respectivamente) y, verificado por consulta directa, tenía CERO filas en
 * `pi_shared_constraints` antes de este archivo — a diferencia de Da Porto (73), que ya tiene 191
 * filas reales de producción que ningún test debería tocar. `tests/test_bi_constraint_write.php` y
 * `tests/test_bi_constraint_list.php` (Task 5/7) SÍ usan Da Porto porque son pruebas de API que
 * mutan y restauran filas EXISTENTES con cuidado quirúrgico; este es un e2e de navegador que crea
 * y borra sus propias filas, así que un proyecto vacío y dedicado es la opción de menor riesgo —
 * ninguno de los specs de `tests/browser/` tenía ya un "sandbox" de restricciones BI declarado
 * (el único sandbox existente, `PDC_SANDBOX_PROJECT`/990100 en `fixtures/projects.mjs`, es del
 * Plan de Compras v2 y no tiene member test.V, así que no sirve para el caso "rol denegado").
 *
 * --- Hallazgo (concern crítico, no un bug de este archivo): no existe hoy un rol "lee pero no
 * edita" restricciones ---
 * El encargo original pedía probar que un rol denegado "ve la hoja... pero SIN el panel de
 * gestión". Verificado contra `RbacManager::getCapabilities()`
 * (`src/Security/RbacManager.php:33,39`): `PERM_INTERNAL_BI_PREVIEW` (poder abrir CUALQUIER hoja
 * del BI, incluida Intermedia) = roles A/D/R. `canEditConstraints` (poder gestionar una
 * restricción) = A/D/R/DCV/S/G/SG/OT. El primer conjunto es subconjunto exacto del segundo: TODO
 * rol que puede abrir la hoja también puede gestionar. No hay hoy ningún rol real que satisfaga
 * "lee la lista, no ve/usa el panel" — ese escenario no se puede construir con datos reales sin
 * inventar un rol que no existe. El test de "rol denegado" de este archivo prueba entonces la
 * denegación que SÍ existe: V (y cualquier rol fuera de A/D/R) ni siquiera pasa
 * `BiPreviewAccessPolicy::canOpen()` y recibe 404 en la hoja misma, antes de que exista ninguna
 * fila que ocultar. El 403 del endpoint de escritura ante un intento directo ya lo cubre
 * `tests/test_bi_constraint_write.php` (caso 2) — no se duplica aquí.
 *
 * --- Marcadores de ct-app usados (ya existen, ninguno nuevo que pedirle a rol B) ---
 * `data-testid="alarma-huerfanas"` (`AlarmaHuerfanas.tsx`), `data-testid="lista-restricciones"` y
 * `data-testid="fila-restriccion-{id}"` (`ListaRestricciones.tsx`) — los tres ya están commiteados
 * por los pasos previos de Task 7. Lo único que falta para que este archivo pase es lo que el
 * encargo ya identificó como trabajo de rol B: montar `<Intermedia />` en `App.tsx` (hoy es un
 * placeholder) y hacer que la pestaña "Intermedia" de la SPA vieja navegue de verdad a
 * `/bi/intermedia` en vez de solo alternar un `<div>` oculto (`switchView()` en
 * `public/js/modules/bi-spa.js`, límite documentado en la entrada 10 de la Bitácora) — más,
 * insustituible, CT_PILOTO=1 de verdad en el contenedor servido.
 */

test.use({ viewport: { width: 1180, height: 820 } });

const PROYECTO_SANDBOX = 'Prueba';
const USUARIO_PERMITIDO = 'test.D'; // Director de Obra: PERM_INTERNAL_BI_PREVIEW y canEditConstraints, ambos true.
const USUARIO_DENEGADO = 'test.V';

/** Ejecuta PHP dentro del contenedor `app`, mismo patrón que los tests PHP autoejecutables. */
function phpEnApp(codigo) {
  return execFileSync('docker', ['compose', 'exec', '-T', 'app', 'php', '-r', codigo], {
    cwd: process.cwd(),
    encoding: 'utf8',
    timeout: 60_000,
  });
}

/** project_id real de `nombreProyecto`, descubierto en runtime -- nunca hardcodeado. */
function descubrirProjectId(nombreProyecto) {
  const codigo = `
    require '/var/www/html/vendor/autoload.php';
    require '/var/www/html/src/Core/Database.php';
    $db = Database::getInstance();
    $row = $db->query(
      'SELECT ID FROM general_proyectos_procesos WHERE Proyecto_Proceso = ? LIMIT 1',
      [${JSON.stringify(nombreProyecto)}]
    )->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
      fwrite(STDERR, 'ABORT: proyecto sandbox no encontrado: ${nombreProyecto}');
      exit(2);
    }
    echo (int) $row['ID'];
  `;
  return parseInt(phpEnApp(codigo).trim(), 10);
}

/**
 * Deja el sandbox sin ninguna restricción -- idempotente, seguro de llamar aunque ya esté vacío.
 * Corre una sola vez por archivo (no por test): el proyecto es de uso exclusivo de este spec, así
 * que no hay dato ajeno que proteger, pero sí conviene partir de un estado conocido si una corrida
 * anterior murió a mitad de camino sin limpiar su fila.
 */
function limpiarSandbox(projectId) {
  phpEnApp(`
    require '/var/www/html/vendor/autoload.php';
    require '/var/www/html/src/Core/Database.php';
    $db = Database::getInstance();
    $db->query('DELETE FROM pi_shared_constraints WHERE project_id = ?', [${projectId}]);
  `);
}

/**
 * Siembra UNA restricción huérfana (`estadoLiberacion='sin_gestionar'`, `responsableAsignado`
 * NULL -- el criterio exacto de `esHuerfana()` en `Intermedia.tsx`) sin actividades encadenadas
 * ("sin análisis"). `Id` se calcula como `MAX(Id)+1` del propio proyecto -- nunca un literal fijo.
 * Devuelve el `Id` sembrado.
 */
function sembrarRestriccionHuerfana(projectId) {
  const codigo = `
    require '/var/www/html/vendor/autoload.php';
    require '/var/www/html/src/Core/Database.php';
    $db = Database::getInstance();
    $max = $db->query(
      'SELECT COALESCE(MAX(Id), 0) AS m FROM pi_shared_constraints WHERE project_id = ?',
      [${projectId}]
    )->fetch(PDO::FETCH_ASSOC);
    $id = (int) $max['m'] + 1;
    $db->query(
      "INSERT INTO pi_shared_constraints (project_id, Id, Semana, Restriccion, ValorObjetivo, EstadoLiberacion) VALUES (?, ?, ?, ?, ?, 'sin_gestionar')",
      [${projectId}, $id, 1, 'MdeO', '0']
    );
    echo $id;
  `;
  return parseInt(phpEnApp(codigo).trim(), 10);
}

function borrarRestriccion(projectId, id) {
  phpEnApp(`
    require '/var/www/html/vendor/autoload.php';
    require '/var/www/html/src/Core/Database.php';
    $db = Database::getInstance();
    $db->query('DELETE FROM pi_shared_constraints WHERE project_id = ? AND Id = ?', [${projectId}, ${id}]);
  `);
}

// ------------------------------------------------------------- Gate real de CT_PILOTO -----------

let ctPilotoActivo = null;
let ctPilotoDiagnostico = '';

test.beforeAll(async () => {
  const api = await apiRequest.newContext({ baseURL: BASE_URL });
  try {
    await api.get(`/dev/entrar?u=${encodeURIComponent(USUARIO_PERMITIDO)}&p=${encodeURIComponent(PROYECTO_SANDBOX)}`);
    const resp = await api.get('/bi/intermedia');
    const body = await resp.text();
    ctPilotoActivo = body.includes('/ct-app/assets/ct.js');
    if (!ctPilotoActivo) {
      ctPilotoDiagnostico = [
        `CT_PILOTO no está activo en el contenedor servido: GET /bi/intermedia (autenticado como ${USUARIO_PERMITIDO})`,
        `devolvió HTTP ${resp.status()} con la hoja vieja (bi-spa.js), no el shell de ct-app (se buscó`,
        '"/ct-app/assets/ct.js" en el HTML y no apareció). BiViewController::ctPilotoEnabled() lee la bandera',
        'de $_ENV/.env en cada request; docker-compose.yml no la inyecta y el .env local compartido tampoco la',
        'trae hoy. Activarla exige editar ese .env compartido o recrear el contenedor -- ninguna de las dos está',
        'autorizada para este rol (test writer) sin decisión explícita. Ver entradas 10/11 de la Bitácora del',
        'plan (docs/superpowers/plans/2026-08-26-ola1-torre-etapa-piloto.md) y la sección "Bloqueo CT_PILOTO"',
        'de .superpowers/sdd/2026-08-26-ola1-torre-etapa-piloto/task-7-report.md.',
      ].join(' ');
    }
  } finally {
    await api.dispose();
  }
});

test.beforeEach(() => {
  expect(ctPilotoActivo, ctPilotoDiagnostico).toBe(true);
});

// -------------------------------------------------- 1. Navegación desde la SPA vieja -------------

test.describe('Navegación desde la SPA vieja hacia la hoja React (CT_PILOTO)', () => {
  test('clic en la pestaña "Intermedia" desde /bi/control-tower aterriza en ct-app', async ({ page }) => {
    const errors = installErrorCollectors(page);

    await page.goto(`/dev/entrar?u=${encodeURIComponent(USUARIO_PERMITIDO)}&p=${encodeURIComponent(PROYECTO_SANDBOX)}`);
    await page.goto('/bi/control-tower', { waitUntil: 'domcontentloaded' });

    const tab = page.locator('#nav-intermedia');
    await expect(tab).toBeVisible();
    await tab.click();

    // Límite conocido (Bitácora del plan, entrada 10): switchView('intermedia') en
    // public/js/modules/bi-spa.js solo alterna la visibilidad de #view-intermedia -- nunca navega
    // a /bi/intermedia. Mientras eso no se resuelva (Task 7, rol B), este clic deja al usuario
    // viendo la SPA vieja y el marcador de ct-app de abajo nunca aparece.
    await expect(
      page.getByTestId('alarma-huerfanas'),
      'la pestaña "Intermedia" debe aterrizar en la hoja React (ct-app) -- si esto falla por '
        + 'timeout, revisa si switchView() en bi-spa.js ya navega a /bi/intermedia en vez de solo '
        + 'alternar visibilidad (ver entrada 10 de la Bitácora del plan)',
    ).toBeVisible({ timeout: 15000 });

    assertNoRuntimeErrors(errors);
  });
});

// ------------------------------------- 2. Flujo completo: sembrar, gestionar, persistir ----------

test.describe('Flujo completo: huérfana -> gestionar -> persiste -> conteo baja', () => {
  let projectId;
  let restriccionId;

  test.beforeAll(() => {
    projectId = descubrirProjectId(PROYECTO_SANDBOX);
    limpiarSandbox(projectId);
  });

  test.beforeEach(() => {
    restriccionId = sembrarRestriccionHuerfana(projectId);
  });

  test.afterEach(() => {
    if (restriccionId) {
      borrarRestriccion(projectId, restriccionId);
      restriccionId = null;
    }
  });

  test('asignar responsable y fecha persiste tras recargar, y la huérfana sale de la alarma', async ({ page }) => {
    const errors = installErrorCollectors(page);

    await page.goto(`/dev/entrar?u=${encodeURIComponent(USUARIO_PERMITIDO)}&p=${encodeURIComponent(PROYECTO_SANDBOX)}`);
    await page.goto('/bi/intermedia', { waitUntil: 'domcontentloaded' });

    const alarma = page.getByTestId('alarma-huerfanas');
    await expect(alarma).toContainText('1 restricción sin análisis ni responsable asignado.');

    const fila = page.getByTestId(`fila-restriccion-${restriccionId}`);
    await expect(fila).toBeVisible();
    await expect(fila).toContainText('Sin gestionar');

    await fila.getByRole('button', { name: 'Gestionar' }).click();

    const responsable = 'QA Playwright Task 7';
    const fecha = '2026-09-15';

    await page.getByLabel('Responsable').fill(responsable);
    await page.getByLabel('Fecha de compromiso').fill(fecha);
    await page.getByLabel('Estado').selectOption('en_gestion');
    await page.getByRole('button', { name: 'Guardar' }).click();

    // D33: el panel guarda contra el servidor y la fila refleja el nuevo estado sin recargar.
    await expect(fila).toContainText('En gestión');
    await expect(alarma).toContainText('Todas las restricciones están gestionadas');

    // Recarga completa de página -- la prueba real de persistencia (no de caché de React).
    await page.reload({ waitUntil: 'domcontentloaded' });

    const filaTrasRecargar = page.getByTestId(`fila-restriccion-${restriccionId}`);
    await expect(filaTrasRecargar).toContainText('En gestión');
    await expect(page.getByTestId('alarma-huerfanas')).toContainText('Todas las restricciones están gestionadas');

    // Reabre el panel para confirmar que responsable y fecha -- no solo el estado -- persistieron.
    await filaTrasRecargar.getByRole('button', { name: 'Gestionar' }).click();
    await expect(page.getByLabel('Responsable')).toHaveValue(responsable);
    await expect(page.getByLabel('Fecha de compromiso')).toHaveValue(fecha);
    await expect(page.getByLabel('Estado')).toHaveValue('en_gestion');

    assertNoRuntimeErrors(errors);
  });
});

// --------------------------------------------------------------- 3. Rol denegado -----------------

test.describe('Rol denegado', () => {
  test('test.V no llega a ver la hoja Intermedia (bloqueada desde el gate del módulo BI)', async ({ page }) => {
    // Ver el concern largo en la cabecera del archivo: no existe hoy un rol "lee pero no edita"
    // restricciones (PERM_INTERNAL_BI_PREVIEW ⊆ canEditConstraints), así que el escenario literal
    // "ve la hoja sin el panel" no se puede construir con datos reales. Lo que sí se prueba: V
    // (fuera de A/D/R) recibe 404 en la hoja misma -- ni siquiera pasa BiPreviewAccessPolicy::
    // canOpen(), antes de que exista ninguna fila ni panel que ocultar.
    const respuestaLogin = await page.goto(
      `/dev/entrar?u=${encodeURIComponent(USUARIO_DENEGADO)}&p=${encodeURIComponent(PROYECTO_SANDBOX)}`,
    );
    expect(respuestaLogin?.ok() ?? false, 'la puerta de desarrollo debe autenticar a test.V (es miembro del sandbox)').toBe(true);

    const respuestaHoja = await page.goto('/bi/intermedia', { waitUntil: 'domcontentloaded' });
    expect(respuestaHoja?.status()).toBe(404);
    await expect(page.getByText('Esta página no existe')).toBeVisible();
  });
});
