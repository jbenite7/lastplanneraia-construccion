import { execFileSync } from 'node:child_process';
import { test } from '@playwright/test';
import { BASE_URL, PDC_SANDBOX_PROJECT } from '../fixtures/projects.mjs';

export { PDC_SANDBOX_PROJECT };

/**
 * El reseteo del sandbox habla con el MySQL local vía `docker compose exec`, mientras el navegador
 * ataca `E2E_BASE_URL`. Si alguien apunta los specs a otro entorno (staging, otro stack), el
 * navegador escribiría allá y este script sembraría aquí: el proyecto de destino no existiría o
 * llegaría sucio. Mismo guardarraíl que ya usaba `pdc-v2-plan.spec.mjs` para su restauración.
 */
export const SANDBOX_LOCAL = /^https?:\/\/localhost(:|\/|$)/.test(BASE_URL);

const RAZON_NO_LOCAL = `El sandbox del PDC se siembra por «docker compose exec» contra el MySQL local; `
  + `E2E_BASE_URL (${BASE_URL}) no apunta a localhost, así que el proyecto de pruebas no estaría `
  + `sembrado en el entorno que ataca el navegador.`;

/** Ejecuta PHP dentro del contenedor `app`, igual que los tests PHP autoejecutables. */
export function phpEnApp(argumentos) {
  return execFileSync('docker', ['compose', 'exec', '-T', 'app', 'php', ...argumentos], {
    cwd: process.cwd(),
    encoding: 'utf8',
    timeout: 60_000,
  });
}

/** SQL directo contra la BD del stack, reusando la conexión configurada de Database.php. */
export function sqlEnApp(codigoPhp) {
  return phpEnApp([
    '-r',
    `require '/var/www/html/vendor/autoload.php';`
    + `require '/var/www/html/src/Core/Database.php';`
    + `$db = Database::getInstance();`
    + codigoPhp,
  ]).trim();
}

/**
 * Deja el sandbox en su estado inicial conocido: sin presupuestos, sin vínculos, sin asignaciones
 * ni plan, y con el cronograma mínimo resembrado. Idempotente por construcción.
 */
export function resetearSandboxPdc() {
  phpEnApp(['/var/www/html/database/seeds/pdc_e2e_sandbox_project.php']);
}

/**
 * Registra el ciclo de vida del sandbox para un spec del PDC v2: resetea antes de cada test y
 * salta con un motivo claro si el entorno no permite sembrarlo.
 *
 * Llamar en el cuerpo del módulo, antes de los `test(...)`.
 */
export function usarSandboxPdc() {
  test.beforeEach(() => {
    test.skip(!SANDBOX_LOCAL, RAZON_NO_LOCAL);
    resetearSandboxPdc();
  });
}
